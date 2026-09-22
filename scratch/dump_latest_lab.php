<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\LaboratoryExaminationResult;

// Get latest case
$case = SingleSuspectCase::latest('id')->first();
echo "=== LATEST CASE: ID={$case->id}, {$case->suspect_name} ===\n";

$dd = $case->docket_data ?? [];

echo "\n=== LAB_EXAMINATION IN DOCKET_DATA ===\n";
print_r($dd['lab_examination'] ?? []);

echo "\n=== LAB_EXAMINATIONS_LIST IN DOCKET_DATA ===\n";
print_r($dd['lab_examinations_list'] ?? []);

echo "\n=== LABORATORY_EXAMINATION_RESULTS TABLE (for this case) ===\n";
$records = LaboratoryExaminationResult::where('case_id', $case->id)->get();
foreach ($records as $r) {
    echo "  DB Record ID={$r->id}:\n";
    echo "    sample_weight_taken    = " . var_export($r->sample_weight_taken, true) . "\n";
    echo "    sample_weight_reserved = " . var_export($r->sample_weight_reserved, true) . "\n";
    echo "    sample_weight_gross    = " . var_export($r->sample_weight_gross, true) . "\n";
    echo "    substance_identification = " . var_export($r->substance_identification, true) . "\n";
    echo "    suspect_name           = " . var_export($r->suspect_name, true) . "\n";
    echo "    is_primary             = " . var_export($r->is_primary, true) . "\n";
}
if ($records->isEmpty()) echo "  (no records in DB)\n";

echo "\n=== CASE DIRECT COLUMNS ===\n";
echo "sample_weight_taken    = " . var_export($case->sample_weight_taken, true) . "\n";
echo "sample_weight_reserved = " . var_export($case->sample_weight_reserved, true) . "\n";
echo "sample_weight_gross    = " . var_export($case->sample_weight_gross, true) . "\n";
