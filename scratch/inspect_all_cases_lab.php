<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\LaboratoryExaminationResult;

echo "=== SINGLE SUSPECT CASES ===\n";
foreach (SingleSuspectCase::all() as $sc) {
    $lab = LaboratoryExaminationResult::where('single_suspect_case_id', $sc->id)->first();
    echo "ID: {$sc->id} | CaseNo: {$sc->case_number} | Suspect: {$sc->suspect_name}\n";
    echo "  - Case sample_weight_taken: " . var_export($sc->sample_weight_taken, true) . " | reserved: " . var_export($sc->sample_weight_reserved, true) . "\n";
    if ($lab) {
        echo "  - Lab Exam ID: {$lab->id} | taken: " . var_export($lab->sample_weight_taken, true) . " | reserved: " . var_export($lab->sample_weight_reserved, true) . " | conf: {$lab->confirmatory_result} | sub: {$lab->substance_identification}\n";
    } else {
        echo "  - Lab Exam: NONE\n";
    }
}

echo "\n=== MULTIPLE SUSPECT CASES ===\n";
foreach (MultipleSuspectCase::all() as $mc) {
    $lab = LaboratoryExaminationResult::where('multiple_suspect_case_id', $mc->id)->first();
    echo "ID: {$mc->id} | CaseNo: {$mc->case_number} | PrimarySuspect: {$mc->suspect_name}\n";
    echo "  - Case sample_weight_taken: " . var_export($mc->sample_weight_taken, true) . " | reserved: " . var_export($mc->sample_weight_reserved, true) . "\n";
    if ($lab) {
        echo "  - Lab Exam ID: {$lab->id} | taken: " . var_export($lab->sample_weight_taken, true) . " | reserved: " . var_export($lab->sample_weight_reserved, true) . " | conf: {$lab->confirmatory_result} | sub: {$lab->substance_identification}\n";
    } else {
        echo "  - Lab Exam: NONE\n";
    }
}
