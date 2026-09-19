<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true);

$suspectsList = $docketData['suspects'] ?? [];
$associateSuspectsForLab = [];

if (!empty($suspectsList) && is_array($suspectsList)) {
    foreach ($suspectsList as $sIdx => $sItem) {
        $spIsAssoc = !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect');
        if ($spIsAssoc && !empty($sItem['suspect_name'])) {
            $associateSuspectsForLab[] = [
                'index' => $sItem['suspect_number'] ?? ($sIdx + 1),
                'name'  => $sItem['suspect_name'],
                'item'  => $sItem
            ];
        }
    }
}
if (!empty($docketData['associated_suspects']) && is_array($docketData['associated_suspects'])) {
    foreach ($docketData['associated_suspects'] as $aIdx => $aSp) {
        $aName = $aSp['suspect_name'] ?? ($aSp['name'] ?? null);
        if ($aName) {
            $alreadyAdded = false;
            foreach ($associateSuspectsForLab as $existingAssoc) {
                if (strtolower($existingAssoc['name']) === strtolower($aName)) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded) {
                $associateSuspectsForLab[] = [
                    'index' => count($suspectsList) + $aIdx + 1,
                    'name'  => $aName,
                    'item'  => $aSp
                ];
            }
        }
    }
}
if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
    foreach ($docketData['seizures'] as $szItem) {
        $assocP = $szItem['associated_person'] ?? null;
        if (!empty($assocP)) {
            $alreadyAdded = false;
            foreach ($associateSuspectsForLab as $existingAssoc) {
                if (strtolower($existingAssoc['name']) === strtolower($assocP)) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded) {
                $associateSuspectsForLab[] = [
                    'index' => count($associateSuspectsForLab) + 2,
                    'name'  => $assocP,
                    'item'  => ['suspect_name' => $assocP, 'is_associate' => true]
                ];
            }
        }
    }
}

echo "=== ASSOCIATE SUSPECTS FOR LAB ===\n";
print_r($associateSuspectsForLab);
