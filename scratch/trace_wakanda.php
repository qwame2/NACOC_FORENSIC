<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();

// We will inspect lines 3300-3450 of show_review.blade.php for CSS-2026-5584
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);
$suspectsList = $docketData['suspects'] ?? [];

$primarySuspectNames = [];
if (!empty($suspectsList) && is_array($suspectsList)) {
    foreach ($suspectsList as $sItem) {
        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
        if ($role === 'primary suspect' || str_contains($role, 'co-suspect') || empty($sItem['is_associate'])) {
            if (!empty($sItem['suspect_name'])) {
                $primarySuspectNames[] = strtolower(trim($sItem['suspect_name']));
            }
        }
    }
}

$isPrimaryOrCoSuspectName = function($name) use ($primarySuspectNames) {
    if (empty($name)) return false;
    $nLow = strtolower(trim($name));
    foreach ($primarySuspectNames as $pName) {
        if ($nLow === $pName || str_contains($nLow, $pName) || str_contains($pName, $nLow)) {
            return true;
        }
    }
    return false;
};

$checkIsDrugExhibit = function($ex) {
    if (empty($ex) || !is_array($ex)) return false;
    $cat = strtolower(trim($ex['exhibit_category'] ?? ($ex['category'] ?? ($ex['seizure_category'] ?? ($ex['type'] ?? '')))));
    $name = strtolower(trim($ex['seizure_name_drug'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ''))));
    $type = strtolower(trim($ex['seizure_type'] ?? ($ex['exhibit_type'] ?? '')));
    
    $nonDrugKws = ['weapon', 'firearm', 'pistol', 'rifle', 'shotgun', 'ammunition', 'vehicle', 'truck', 'car', 'cash', 'currency', 'usd', 'ghs', 'laptop', 'phone', 'electronics', 'document'];
    foreach ($nonDrugKws as $kw) {
        if (str_contains($cat, $kw) || str_contains($name, $kw) || str_contains($type, $kw)) {
            return false;
        }
    }
    if ($cat === 'drugs' || $cat === 'narcotics' || $cat === 'drug' || !empty($ex['seizure_name_drug'])) {
        return true;
    }
    if (!empty($cat) && in_array($cat, ['weapons', 'weapon', 'vehicles', 'vehicle', 'cash', 'currency', 'firearm', 'firearms'])) {
        return false;
    }
    return true;
};

$associateSuspectsForLab = [];
if (!empty($suspectsList) && is_array($suspectsList)) {
    foreach ($suspectsList as $sIdx => $sItem) {
        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
        $spIsAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
        $spName = $sItem['suspect_name'] ?? null;
        if ($spIsAssoc && !empty($spName) && !$isPrimaryOrCoSuspectName($spName)) {
            $associateSuspectsForLab[] = [
                'index' => $sItem['suspect_number'] ?? ($sIdx + 1),
                'name'  => $spName,
                'item'  => $sItem
            ];
        }
    }
}
if (!empty($docketData['associated_suspects']) && is_array($docketData['associated_suspects'])) {
    foreach ($docketData['associated_suspects'] as $aIdx => $aSp) {
        $aName = $aSp['suspect_name'] ?? ($aSp['name'] ?? null);
        if ($aName && !$isPrimaryOrCoSuspectName($aName)) {
            $aNameLower = strtolower(trim($aName));
            $alreadyAdded = false;
            foreach ($associateSuspectsForLab as $existingAssoc) {
                if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
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
        if (!empty($assocP) && !$isPrimaryOrCoSuspectName($assocP) && $checkIsDrugExhibit($szItem)) {
            $aNameLower = strtolower(trim($assocP));
            $alreadyAdded = false;
            foreach ($associateSuspectsForLab as $existingAssoc) {
                if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
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

echo "associateSuspectsForLab count: " . count($associateSuspectsForLab) . "\n";
foreach ($associateSuspectsForLab as $aInfo) {
    echo "  - Name: {$aInfo['name']} (Index: {$aInfo['index']})\n";
    $aSpName = $aInfo['name'];
    $aSpId = $aInfo['item']['associate_id'] ?? ($aInfo['item']['suspect_id'] ?? null);
    $dbAssocExam = $case->associateLabExaminations ? $case->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($aSpName))) : null;
    $docketAssocExam = null;
    if (!empty($docketData['associate_lab_examinations'])) {
        foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
            if (strtolower(trim($kAssoc)) === strtolower(trim($aSpName))) {
                $docketAssocExam = $vAssoc;
                break;
            }
        }
    }

    $assocExhibitsList = array_filter($docketData['seizures'] ?? [], function($sz) use ($aSpName, $aSpId, $checkIsDrugExhibit) {
        if (!$checkIsDrugExhibit($sz)) return false;
        $szOwnerName = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
        if (!empty($szOwnerName) && strtolower(trim($szOwnerName)) !== 'associate suspect' && strtolower(trim($szOwnerName)) !== 'primary suspect') {
            if (strtolower(trim($szOwnerName)) === strtolower(trim($aSpName)) || str_contains(strtolower($szOwnerName), strtolower(trim($aSpName)))) return true;
        }
        $szAssocId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
        if (!empty($szAssocId) && !empty($aSpId)) {
            if (strtolower(trim($szAssocId)) === strtolower(trim($aSpId))) return true;
        }
        return false;
    });
    $assocExhibitsList = array_values($assocExhibitsList);
    $firstAssocEx = !empty($assocExhibitsList) ? $assocExhibitsList[0] : null;

    $assocLabExam = null;
    if (!empty($docketData['lab_examinations_list'])) {
        foreach ($docketData['lab_examinations_list'] as $lItem) {
            if (!empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($aSpName))) {
                $assocLabExam = $lItem;
                break;
            }
        }
    }

    $hasThisAssocSavedLab = false;
    if ($dbAssocExam && $dbAssocExam->has_saved) {
        $hasThisAssocSavedLab = true;
    } elseif ($docketAssocExam && !empty($docketAssocExam['has_saved'])) {
        $hasThisAssocSavedLab = true;
    } elseif (!empty($firstAssocEx) && $checkIsDrugExhibit($firstAssocEx) && !empty($firstAssocEx['has_saved_lab'])) {
        $hasThisAssocSavedLab = true;
    } elseif (!empty($assocLabExam) && !empty($assocLabExam['has_saved'])) {
        $hasThisAssocSavedLab = true;
    }

    echo "    dbAssocExam: " . ($dbAssocExam ? 'YES' : 'NO') . "\n";
    echo "    docketAssocExam: " . ($docketAssocExam ? 'YES' : 'NO') . "\n";
    echo "    firstAssocEx: " . ($firstAssocEx ? 'YES' : 'NO') . "\n";
    echo "    assocLabExam: " . ($assocLabExam ? 'YES' : 'NO') . "\n";
    echo "    hasThisAssocSavedLab: " . ($hasThisAssocSavedLab ? 'TRUE' : 'FALSE') . "\n";
}
