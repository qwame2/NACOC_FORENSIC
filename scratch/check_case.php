<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5341')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);

$topDrugExhibits = [];
if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
    foreach ($docketData['seizures'] as $idx => $sz) {
        $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
        $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
        if (!empty($sz['seizure_name_drug']) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains(strtolower($sName), 'cocaine') || str_contains(strtolower($sName), 'heroin') || str_contains(strtolower($sName), 'cannabis') || str_contains(strtolower($sName), 'drug')) {
            $topDrugExhibits[] = $sz;
        }
    }
}

$firstDrugQty = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['seizure_quantity'] ?? null) : $case->seizure_quantity;
$firstDrugPkg = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['seizure_packaging'] ?? null) : $case->seizure_packaging;
$firstDrugWeight = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['seizure_weight'] ?? null) : $case->seizure_weight;
$firstDrugUnit = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['seizure_unit'] ?? null) : $case->seizure_unit;

$defaultUnits = !empty($firstDrugQty) ? trim($firstDrugQty . ' ' . ($firstDrugPkg ?? '')) : '';
$defaultGrossWeight = !empty($firstDrugWeight) ? trim($firstDrugWeight . ' ' . ($firstDrugUnit ?? '')) : ($defaultUnits ?: ($case->exhibits_summary ?? ''));

echo "UNITS INPUT BOX (defaultUnits): " . var_export($defaultUnits, true) . "\n";
echo "GROSS WEIGHT INPUT BOX (defaultGrossWeight): " . var_export($defaultGrossWeight, true) . "\n";
