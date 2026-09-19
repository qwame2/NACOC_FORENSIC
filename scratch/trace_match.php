<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$docketData = $case->docket_data ?? [];
$labExam = $docketData['lab_examination'] ?? [];
$labSampleWeight = $labExam['sample_weight'] ?? [];

$rawSampleDesc = !empty($labSampleWeight['description']) ? $labSampleWeight['description'] : '';
$rawSubstanceId = !empty($labExam['substance_identification']) ? $labExam['substance_identification'] : '';

echo "rawSampleDesc: " . var_export($rawSampleDesc, true) . "\n";
echo "rawSubstanceId: " . var_export($rawSubstanceId, true) . "\n";

$hasAnySavedLabExam = !empty($labExam) && (
    !empty($labExam['confirmatory_result']) 
    || !empty($labExam['presumptive_result']) 
    || !empty($labExam['substance_identification'])
);

echo "hasAnySavedLabExam: " . var_export($hasAnySavedLabExam, true) . "\n";

foreach ($docketData['seizures'] ?? [] as $idxCheck => $szItemCheck) {
    $sNameCheck = $szItemCheck['seizure_name_drug'] ?? ($szItemCheck['seizure_name'] ?? '');
    echo "\nSeizure #" . ($idxCheck + 1) . ": " . $sNameCheck . "\n";
    echo "  is_associate: " . var_export($szItemCheck['is_associate'] ?? null, true) . "\n";
    echo "  has_saved_lab: " . var_export($szItemCheck['has_saved_lab'] ?? null, true) . "\n";
    echo "  presumptive_result: " . var_export($szItemCheck['presumptive_result'] ?? null, true) . "\n";
    
    $m1 = !empty($rawSampleDesc) && strtolower(trim($sNameCheck)) === strtolower(trim($rawSampleDesc));
    $m2 = !empty($rawSubstanceId) && strtolower(trim($sNameCheck)) === strtolower(trim($rawSubstanceId));
    echo "  Match rawSampleDesc: " . var_export($m1, true) . "\n";
    echo "  Match rawSubstanceId: " . var_export($m2, true) . "\n";
}
