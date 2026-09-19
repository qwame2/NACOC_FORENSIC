<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true);

$labExam = $d['lab_examination'] ?? [];
$savedLabExhibitNo = $labExam['exhibit_number'] ?? ($labExam['lab_exhibit_number'] ?? null);
$savedLabSampleDesc = $labExam['sample_weight']['description'] ?? ($labExam['sample_description'] ?? null);
$savedLabSubstanceId = $labExam['substance_identification'] ?? null;
$savedLabOwnerName = $labExam['exhibit_owner_name'] ?? null;
$savedLabIsAssoc = !empty($labExam['exhibit_is_associate']);
$hasAnySavedLabExam = !empty($labExam) && (!empty($labExam['confirmatory_result']) || !empty($labExam['presumptive_result']) || !empty($labExam['substance_identification']));

echo "savedLabExhibitNo: var_dump -> "; var_dump($savedLabExhibitNo);
echo "savedLabSampleDesc: var_dump -> "; var_dump($savedLabSampleDesc);
echo "savedLabSubstanceId: var_dump -> "; var_dump($savedLabSubstanceId);
echo "savedLabOwnerName: var_dump -> "; var_dump($savedLabOwnerName);
echo "savedLabIsAssoc: var_dump -> "; var_dump($savedLabIsAssoc);
echo "hasAnySavedLabExam: var_dump -> "; var_dump($hasAnySavedLabExam);

foreach ($d['seizures'] ?? [] as $idx => $sz) {
    $exNo = $sz['exhibit_number'] ?? '';
    $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
    $exOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? 'PRINCILLA ADDO'));
    $szIsAssoc = !empty($sz['is_associate']);

    $cond1 = !empty($sz['has_saved_lab']);
    $cond2 = !empty($sz['presumptive_result']);
    $cond3 = !empty($sz['confirmatory_result']);
    $cond4a = (!empty($savedLabExhibitNo) && $exNo === $savedLabExhibitNo);
    $cond4b = (!empty($savedLabSampleDesc) && strtolower(trim($sName)) === strtolower(trim($savedLabSampleDesc)));
    $cond4c = (!empty($savedLabSubstanceId) && strtolower(trim($sName)) === strtolower(trim($savedLabSubstanceId)));
    $cond4d = (!empty($savedLabOwnerName) && strtolower(trim($exOwner)) === strtolower(trim($savedLabOwnerName)));
    $cond4e = (!$savedLabIsAssoc && empty($szIsAssoc) && empty($savedLabOwnerName));

    $hasSavedForThis = $cond1 || $cond2 || $cond3 || ($hasAnySavedLabExam && ($cond4a || $cond4b || $cond4c || $cond4d || $cond4e));

    echo "\n--- Seizure #$idx ($sName / Owner: $exOwner) ---\n";
    echo "cond1 (has_saved_lab): " . ($cond1 ? 'T' : 'F') . "\n";
    echo "cond2 (presumptive_result): " . ($cond2 ? 'T' : 'F') . "\n";
    echo "cond3 (confirmatory_result): " . ($cond3 ? 'T' : 'F') . "\n";
    echo "cond4a (exNo): " . ($cond4a ? 'T' : 'F') . "\n";
    echo "cond4b (sampleDesc): " . ($cond4b ? 'T' : 'F') . "\n";
    echo "cond4c (substanceId): " . ($cond4c ? 'T' : 'F') . "\n";
    echo "cond4d (ownerName): " . ($cond4d ? 'T' : 'F') . "\n";
    echo "cond4e (fallbackPrimary): " . ($cond4e ? 'T' : 'F') . "\n";
    echo "FINAL hasSavedForThis: " . ($hasSavedForThis ? 'TRUE' : 'FALSE') . "\n";
}
