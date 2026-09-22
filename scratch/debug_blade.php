<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

// Let's run the exact blade logic from case_pdf.blade.php lines 175-520
$primaryExhibitsCheckPdf = [];
if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
    $primaryExhibitsCheckPdf[] = [
        'category'          => $case->exhibit_category ?? '',
        'seizure_name_drug' => $case->seizure_name_drug ?? '',
        'seizure_name'      => $case->seizure_name ?? '',
    ];
}
if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
    foreach ($docketData['seizures'] as $sz) {
        $isAssoc = !empty($sz['is_associate']) 
            || !empty($sz['associated_person']) 
            || (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
        if (!$isAssoc) {
            $primaryExhibitsCheckPdf[] = $sz;
        }
    }
}

$isPrimaryDrugCase = false;
$drugKeywords = ['drug', 'narcotic', 'opioid', 'cocaine', 'heroin', 'cannabis', 'marijuana', 'weed', 'hashish', 'hash', 'meth', 'methamphetamine', 'fentanyl', 'tramadol', 'codeine', 'morphine', 'opium', 'amphetamine', 'mdma', 'ecstasy'];

$allExhibitsCheckPdf = [];
if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
    $allExhibitsCheckPdf[] = [
        'category'          => $case->exhibit_category ?? '',
        'seizure_name_drug' => $case->seizure_name_drug ?? '',
        'seizure_name'      => $case->seizure_name ?? '',
    ];
}
if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
    foreach ($docketData['seizures'] as $sz) { $allExhibitsCheckPdf[] = $sz; }
}
if (!empty($docketData['associated_seizures']) && is_array($docketData['associated_seizures'])) {
    foreach ($docketData['associated_seizures'] as $aEx) { $allExhibitsCheckPdf[] = $aEx; }
}
if (!empty($docketData['associate_exhibits']) && is_array($docketData['associate_exhibits'])) {
    foreach ($docketData['associate_exhibits'] as $aEx) { $allExhibitsCheckPdf[] = $aEx; }
}

$isAnyDrugCasePdf = false;
foreach ($allExhibitsCheckPdf as $exCheck) {
    $cat   = strtolower($exCheck['category'] ?? ($exCheck['exhibit_category'] ?? ''));
    $dName = strtolower($exCheck['seizure_name_drug'] ?? '');
    $sName = strtolower($exCheck['seizure_name'] ?? ($exCheck['name'] ?? ($exCheck['commodity_name'] ?? ($exCheck['description'] ?? ''))));

    if (!empty($dName)) {
        $isAnyDrugCasePdf = true;
        break;
    }

    foreach ($drugKeywords as $kw) {
        if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
            $isAnyDrugCasePdf = true;
            break 2;
        }
    }
}

$isDrugCase = $isPrimaryDrugCase || $isAnyDrugCasePdf;
echo "isDrugCase: " . ($isDrugCase ? 'true' : 'false') . "\n";
