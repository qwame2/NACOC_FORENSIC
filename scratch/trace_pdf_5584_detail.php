<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

// Let's trace how primary/co-suspect names and associate suspects are built in case_pdf.blade.php!

$suspectsList = $docketData['suspects'] ?? [];

$primarySuspectNamesList = [];
$coSuspectNamesList = [];
$associateSuspectNamesList = [];
foreach ($suspectsList as $sp) {
    $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect');
    $spIsCo = $spIsAssoc && (
        (!empty($sp['association_role']) && (str_contains(strtolower($sp['association_role']), 'co-suspect') || str_contains(strtolower($sp['association_role']), 'co suspect')))
        || (!empty($sp['role']) && (str_contains(strtolower($sp['role']), 'co-suspect') || str_contains(strtolower($sp['role']), 'co suspect')))
        || (!empty($sp['suspect_role']) && (str_contains(strtolower($sp['suspect_role']), 'co-suspect') || str_contains(strtolower($sp['suspect_role']), 'co suspect')))
    );
    if (!$spIsAssoc && !empty($sp['suspect_name'])) {
        $primarySuspectNamesList[] = $sp['suspect_name'];
    } elseif ($spIsCo && !empty($sp['suspect_name'])) {
        $coSuspectNamesList[] = $sp['suspect_name'];
    } elseif ($spIsAssoc && !empty($sp['suspect_name'])) {
        $associateSuspectNamesList[] = $sp['suspect_name'];
    }
}
$primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));

echo "primaryAndCoNamesList: " . implode(', ', $primaryAndCoNamesList) . "\n";
echo "associateSuspectNamesList: " . implode(', ', $associateSuspectNamesList) . "\n";

$savedLabExamOwnerPdf = null;
if (!empty($docketData['lab_examination']['has_saved'])) {
    $savedLabExamOwnerPdf = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
}
if (!$savedLabExamOwnerPdf && !empty($docketData['lab_examinations_list'])) {
    foreach ($docketData['lab_examinations_list'] as $lItem) {
        if (!empty($lItem['has_saved']) && !empty($lItem['exhibit_owner_name'])) {
            $savedLabExamOwnerPdf = $lItem['exhibit_owner_name'];
            break;
        }
    }
}
if (!$savedLabExamOwnerPdf && $case->associateLabExaminations) {
    $savedLabExamOwnerPdf = $case->associateLabExaminations->where('has_saved', true)->pluck('suspect_name')->first();
}

echo "savedLabExamOwnerPdf: " . var_export($savedLabExamOwnerPdf, true) . "\n";

$checkIsDrugExhibitPdf = function($ex) {
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

$primarySuspectNamesPdf = [];
if (!empty($primaryAndCoNamesList) && is_array($primaryAndCoNamesList)) {
    foreach ($primaryAndCoNamesList as $pn) {
        if (!empty($pn)) $primarySuspectNamesPdf[] = strtolower(trim($pn));
    }
}
if (!empty($suspectsList) && is_array($suspectsList)) {
    foreach ($suspectsList as $sItem) {
        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
        $isAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
        if (!$isAssoc && !empty($sItem['suspect_name'])) {
            $spNameClean = strtolower(trim($sItem['suspect_name']));
            if (!in_array($spNameClean, $primarySuspectNamesPdf)) {
                $primarySuspectNamesPdf[] = $spNameClean;
            }
        }
    }
}

$isPrimaryOrCoSuspectPdfName = function($nameToCheck) use ($primarySuspectNamesPdf) {
    if (empty($nameToCheck)) return true;
    $clean = strtolower(trim($nameToCheck));
    if ($clean === 'primary suspect' || $clean === 'associate suspect' || $clean === 'all suspects') return true;

    foreach ($primarySuspectNamesPdf as $pName) {
        if ($clean === $pName || str_contains($clean, $pName) || str_contains($pName, $clean)) {
            return true;
        }
    }
    return false;
};

$associateSuspectsForPdfLab = [];
if (!empty($suspectsList) && is_array($suspectsList)) {
    foreach ($suspectsList as $sIdx => $sItem) {
        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
        $spIsAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
        $spName = $sItem['suspect_name'] ?? null;
        if ($spIsAssoc && !empty($spName) && !$isPrimaryOrCoSuspectPdfName($spName)) {
            $associateSuspectsForPdfLab[] = [
                'index' => $sItem['suspect_number'] ?? ($sIdx + 1),
                'name'  => $spName,
                'item'  => $sItem
            ];
        }
    }
}

echo "associateSuspectsForPdfLab count: " . count($associateSuspectsForPdfLab) . "\n";
foreach ($associateSuspectsForPdfLab as $aInfo) {
    echo "  - Name: {$aInfo['name']} (Index: {$aInfo['index']})\n";
    $aSpName = $aInfo['name'];
    $aSpIndex = $aInfo['index'];
    $aSpId = $aInfo['item']['associate_id'] ?? ($aInfo['item']['suspect_id'] ?? ($aInfo['associate_id'] ?? ($aInfo['suspect_id'] ?? null)));

    $dbAssocExamPdf = $case->associateLabExaminations ? $case->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($aSpName))) : null;
    $docketAssocExamPdf = null;
    if (!empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
        foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
            if (strtolower(trim($kAssoc)) === strtolower(trim($aSpName))) {
                $docketAssocExamPdf = $vAssoc;
                break;
            }
        }
    }

    $assocExhibitsList = array_filter($docketData['seizures'] ?? [], function($sz) use ($aSpName, $aSpId, $checkIsDrugExhibitPdf) {
        if (!$checkIsDrugExhibitPdf($sz)) {
            return false;
        }
        $szOwnerName = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
        if (!empty($szOwnerName) && strtolower(trim($szOwnerName)) !== 'associate suspect' && strtolower(trim($szOwnerName)) !== 'primary suspect') {
            $ownerLow = strtolower(trim($szOwnerName));
            $aNameLow = strtolower(trim($aSpName));
            if ($ownerLow === $aNameLow || str_contains($ownerLow, $aNameLow) || str_contains($aNameLow, $ownerLow)) {
                return true;
            }
            return false;
        }
        $szAssocId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
        if (!empty($szAssocId) && !empty($aSpId)) {
            if (strtolower(trim($szAssocId)) === strtolower(trim($aSpId))) {
                return true;
            }
        }
        return false;
    });
    $assocExhibitsList = array_values($assocExhibitsList);
    $firstAssocEx = !empty($assocExhibitsList) ? $assocExhibitsList[0] : null;

    $assocLabExamPdf = null;
    if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
        foreach ($docketData['lab_examinations_list'] as $lItem) {
            if (!empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($aSpName))) {
                $assocLabExamPdf = $lItem;
                break;
            }
        }
    }

    $hasThisAssocSavedLabPdf = false;
    if ($dbAssocExamPdf && $dbAssocExamPdf->has_saved) {
        $hasThisAssocSavedLabPdf = true;
    } elseif ($docketAssocExamPdf && !empty($docketAssocExamPdf['has_saved'])) {
        $hasThisAssocSavedLabPdf = true;
    } elseif (!empty($firstAssocEx) && $checkIsDrugExhibitPdf($firstAssocEx) && !empty($firstAssocEx['has_saved_lab'])) {
        $hasThisAssocSavedLabPdf = true;
    } elseif (!empty($assocLabExamPdf) && !empty($assocLabExamPdf['has_saved'])) {
        $hasThisAssocSavedLabPdf = true;
    }

    $isCoveredByMainReportPdf = false;
    if (!empty($savedLabExamOwnerPdf)) {
        $lOwnerLowPdf = strtolower(trim($savedLabExamOwnerPdf));
        $aSpNameLowPdf = strtolower(trim($aSpName));
        if (str_contains($lOwnerLowPdf, 'all suspect') || str_contains($lOwnerLowPdf, $aSpNameLowPdf)) {
            $isCoveredByMainReportPdf = true;
        }
    }

    echo "    hasThisAssocSavedLabPdf: " . ($hasThisAssocSavedLabPdf ? 'TRUE' : 'FALSE') . "\n";
    echo "    isCoveredByMainReportPdf: " . ($isCoveredByMainReportPdf ? 'TRUE' : 'FALSE') . "\n";
    echo "    Will Render Card (@if(\$hasThisAssocSavedLabPdf && !\$isCoveredByMainReportPdf)): " . (($hasThisAssocSavedLabPdf && !$isCoveredByMainReportPdf) ? 'YES' : 'NO') . "\n";
}
