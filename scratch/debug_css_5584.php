<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\AssociateLabExamination;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

echo "--- DB AssociateLabExamination records ---\n";
$dbExams = AssociateLabExamination::where('case_id', $case->id)->get();
foreach ($dbExams as $dbEx) {
    echo "  ID: {$dbEx->id} | Suspect Name: {$dbEx->suspect_name} | Has Saved: {$dbEx->has_saved} | Desc: {$dbEx->sample_weight_description} | ExNo: {$dbEx->exhibit_number}\n";
}

echo "--- docket_data['associate_lab_examinations'] ---\n";
print_r($docketData['associate_lab_examinations'] ?? []);

echo "--- docket_data['lab_examinations_list'] ---\n";
print_r($docketData['lab_examinations_list'] ?? []);

echo "--- docket_data['lab_examination'] ---\n";
print_r($docketData['lab_examination'] ?? []);
