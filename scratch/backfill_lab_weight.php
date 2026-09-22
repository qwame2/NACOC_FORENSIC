<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Backfill exhibit-derived weight fields for lab results with empty values
// Targets all lab records that have confirmatory data but empty weight fields

$labResults = DB::table('laboratory_examination_results')
    ->whereNotNull('confirmatory_result')
    ->where(function($q) {
        $q->whereNull('sample_weight_gross')
          ->orWhere('sample_weight_gross', '');
    })
    ->get(['id', 'single_suspect_case_id', 'multiple_suspect_case_id', 'exhibit_id',
           'sample_weight_description', 'sample_weight_units', 'sample_weight_gross']);

$updated = 0;
foreach ($labResults as $lab) {
    // Find the best exhibit to use
    $exhibit = null;
    if ($lab->exhibit_id) {
        $exhibit = DB::table('exhibits')->where('id', $lab->exhibit_id)->first();
    }
    if (!$exhibit && $lab->single_suspect_case_id) {
        $exhibit = DB::table('exhibits')
            ->where('single_suspect_case_id', $lab->single_suspect_case_id)
            ->whereNotNull('seizure_weight')->where('seizure_weight', '!=', '')->first()
            ?? DB::table('exhibits')->where('single_suspect_case_id', $lab->single_suspect_case_id)->first();
    }
    if (!$exhibit && $lab->multiple_suspect_case_id) {
        $exhibit = DB::table('exhibits')
            ->where('multiple_suspect_case_id', $lab->multiple_suspect_case_id)
            ->whereNotNull('seizure_weight')->where('seizure_weight', '!=', '')->first()
            ?? DB::table('exhibits')->where('multiple_suspect_case_id', $lab->multiple_suspect_case_id)->first();
    }
    if (!$exhibit) continue;

    $updates = [];
    if (empty($lab->sample_weight_description) && !empty($exhibit->commodity_name)) {
        $updates['sample_weight_description'] = $exhibit->commodity_name;
    }
    if (empty($lab->sample_weight_units)) {
        $units = trim(($exhibit->seizure_quantity ?? '') . ' ' . ($exhibit->seizure_packaging ?? ''));
        if (!empty($units)) $updates['sample_weight_units'] = $units;
    }
    if (empty($lab->sample_weight_gross)) {
        $gross = trim(($exhibit->seizure_weight ?? '') . ' ' . ($exhibit->seizure_unit ?? ''));
        if (!empty($gross)) $updates['sample_weight_gross'] = $gross;
    }

    if (!empty($updates)) {
        $updates['updated_at'] = now();
        DB::table('laboratory_examination_results')->where('id', $lab->id)->update($updates);
        $updated++;
        echo "Updated lab result #{$lab->id} → " . implode(', ', array_map(fn($k,$v) => "$k=$v", array_keys($updates), $updates)) . "\n";
    }
}

echo "\nTotal updated: $updated lab records.\n";
