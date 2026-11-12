<?php

namespace App\Services;

use Closure;

class DrugExhibitClassifier
{
    /**
     * Standard list of controlled substances recognized by the Forensic Directorate.
     */
    public const KNOWN_CONTROLLED_SUBSTANCES = [
        'Tapentadol',
        'Tramadol',
        'Heroin',
        'Cocaine',
        'Cannabis',
        'Methamphetamine',
        'MDMA',
        'Ephedrine',
        'Morphine',
        'Codeine',
        'Fentanyl',
        'Ketamine',
        'Methaqualone',
        'Hashish',
        'Opium',
        'Diazepam',
        'Pregabalin',
        'Amphetamine',
        'Marijuana',
        'Rohypnol',
        'Diacetylmorphine',
        'Ecstasy',
        'Barbiturate',
        'Benzodiazepine',
    ];

    /**
     * Extract a known controlled substance from a descriptive text string.
     */
    public static function extractKnownSubstance(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }
        foreach (self::KNOWN_CONTROLLED_SUBSTANCES as $known) {
            if (stripos($text, $known) !== false) {
                return $known;
            }
        }
        return null;
    }

    /**
     * Determine whether an exhibit/seizure item is a drug exhibit.
     */
    public function isDrugExhibit(mixed $item): bool
    {
        if (empty($item)) {
            return false;
        }
        if ($item instanceof \App\Models\ForensicCase) {
            return $item->isDrugCase();
        }
        if (is_object($item)) {
            $item = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        }
        if (!is_array($item)) {
            return false;
        }
        $cat = strtolower(trim((string) ($item['exhibit_category'] ?? ($item['category'] ?? ($item['seizure_category'] ?? '')))));
        $type = strtolower(trim((string) ($item['exhibit_type'] ?? ($item['type'] ?? ($item['seizure_type'] ?? '')))));
        $dName = strtolower(trim((string) ($item['seizure_name_drug'] ?? ($item['drug_type'] ?? ''))));
        $dClass = strtolower(trim((string) ($item['drug_classification'] ?? '')));
        $substance = strtolower(trim((string) ($item['substance_identification'] ?? '')));

        if (str_contains($cat, 'non-drug') || str_contains($type, 'non-drug')) {
            return false;
        }
        $nonDrugCategories = [
            'electronic',
            'digital',
            'device',
            'phone',
            'laptop',
            'computer',
            'weapon',
            'vehicle',
            'currency',
            'cash',
            'gold',
            'precious',
            'stone',
            'diamond',
            'sim card',
        ];
        foreach ($nonDrugCategories as $ndc) {
            if (str_contains($cat, $ndc) && empty($dName)) {
                return false;
            }
        }
        if (!empty($dName) && !in_array($dName, ['n/a', 'none', 'null', '-', 'unknown'], true)) {
            return true;
        }

        if (!empty($substance) && !in_array($substance, ['n/a', 'none', 'null', '-', 'unknown', 'unspecified'], true)) {
            if (self::extractKnownSubstance($substance) !== null) {
                return true;
            }
        }

        $searchContext = ($item['commodity_name'] ?? '') . ' ' .
            ($item['seizure_name'] ?? '') . ' ' .
            ($item['name'] ?? '') . ' ' .
            ($item['exhibit_name'] ?? '') . ' ' .
            ($item['contents'] ?? '') . ' ' .
            ($item['seizure_contents'] ?? '') . ' ' .
            ($item['description'] ?? '') . ' ' .
            ($item['substance_identification'] ?? '') . ' ' .
            ($item['drug_classification'] ?? '');

        if (self::extractKnownSubstance($searchContext) !== null) {
            return true;
        }

        return str_contains($cat, 'drug')
            || str_contains($cat, 'narcotic')
            || str_contains($dClass, 'drug')
            || str_contains($dClass, 'narcotic')
            || str_contains($dClass, 'psychotropic')
            || (str_contains($type, 'drug') && !str_contains($type, 'non-drug'));
    }

    /**
     * Return a Closure wrapper for backward compatibility with $checkIsDrugExhibit($item).
     */
    public function getClosure(): Closure
    {
        return fn(mixed $item): bool => $this->isDrugExhibit($item);
    }

    /**
     * Classify whether the case as a whole involves drug exhibits and extract top drug exhibits.
     */
    public function classifyCase(mixed $case, array $docketData, bool $isDigitalForensics = false): array
    {
        $exCat = strtolower($case->exhibit_category ?? '');
        $dSeizures = $docketData['seizures'] ?? [];
        $hasDrugSeizureInDocket = false;
        if (is_array($dSeizures)) {
            foreach ($dSeizures as $sz) {
                if ($this->isDrugExhibit($sz)) {
                    $hasDrugSeizureInDocket = true;
                    break;
                }
            }
        }
        $initialIsDrugCase =
            (!str_contains($exCat, 'non-drug') && (str_contains($exCat, 'drug') || str_contains($exCat, 'narcotic'))) ||
            $hasDrugSeizureInDocket;

        $primaryExhibitsCheck = [];
        if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
            $primaryExhibitsCheck[] = [
                'category' => $case->exhibit_category ?? '',
                'seizure_name_drug' => $case->seizure_name_drug ?? '',
                'seizure_name' => $case->seizure_name ?? '',
            ];
        }
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $isAssoc =
                    !empty($sz['is_associate']) ||
                    !empty($sz['associated_person']) ||
                    (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
                if (!$isAssoc) {
                    $primaryExhibitsCheck[] = $sz;
                }
            }
        }

        $isPrimaryDrugCase = false;
        $allExhibitsCheck = [];
        if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
            $allExhibitsCheck[] = [
                'category' => $case->exhibit_category ?? '',
                'exhibit_category' => $case->exhibit_category ?? '',
                'exhibit_type' => $case->exhibit_type ?? '',
                'seizure_name_drug' => $case->seizure_name_drug ?? '',
                'seizure_name' => $case->seizure_name ?? '',
            ];
        }
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $allExhibitsCheck[] = $sz;
            }
        }
        if (!empty($docketData['associated_seizures']) && is_array($docketData['associated_seizures'])) {
            foreach ($docketData['associated_seizures'] as $aEx) {
                $allExhibitsCheck[] = $aEx;
            }
        }
        if (!empty($docketData['associate_exhibits']) && is_array($docketData['associate_exhibits'])) {
            foreach ($docketData['associate_exhibits'] as $aEx) {
                $allExhibitsCheck[] = $aEx;
            }
        }

        $isAnyDrugCase = false;
        foreach ($allExhibitsCheck as $exCheck) {
            if ($this->isDrugExhibit($exCheck)) {
                $isAnyDrugCase = true;
                break;
            }
        }

        foreach ($primaryExhibitsCheck as $exCheck) {
            if ($this->isDrugExhibit($exCheck)) {
                $isPrimaryDrugCase = true;
                break;
            }
        }

        $hasDefinitiveDrugData = $initialIsDrugCase || $isPrimaryDrugCase || $isAnyDrugCase ||
            (!empty($case->seizure_name_drug) && !in_array(strtolower(trim($case->seizure_name_drug)), ['n/a', 'none', 'null', '-'], true)) ||
            (!empty($hasDrugSeizureInDocket) && $hasDrugSeizureInDocket);

        $isDrugCase = $hasDefinitiveDrugData || (
            !$isDigitalForensics
            && empty($allExhibitsCheck)
            && (is_object($case) && method_exists($case, 'isDrugCase') ? $case->isDrugCase() : false)
        );

        $topDrugExhibits = [];
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $idx => $sz) {
                if ($this->isDrugExhibit($sz)) {
                    $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                    $topDrugExhibits[] = [
                        'index' => $idx + 1,
                        'name' => $sName ?: 'Drug Exhibit',
                        'cat' => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                        'qty' => $sz['seizure_quantity'] ?? null,
                        'weight' => $sz['seizure_weight'] ?? null,
                        'unit' => $sz['seizure_unit'] ?? null,
                    ];
                }
            }
        }

        return [
            'exCat' => $exCat,
            'dSeizures' => $dSeizures,
            'hasDrugSeizureInDocket' => $hasDrugSeizureInDocket,
            'exhibitCategory' => strtolower($case->exhibit_category ?? ''),
            'seizureNameDrug' => $case->seizure_name_drug ?? '',
            'seizureName' => strtolower($case->seizure_name ?? ''),
            'exhibitsSummary' => strtolower($case->exhibits_summary ?? ''),
            'offense' => strtolower($case->offense ?? ''),
            'primaryExhibitsCheck' => $primaryExhibitsCheck,
            'allExhibitsCheck' => $allExhibitsCheck,
            'isPrimaryDrugCase' => $isPrimaryDrugCase,
            'isAnyDrugCase' => $isAnyDrugCase,
            'hasDefinitiveDrugData' => $hasDefinitiveDrugData,
            'isDrugCase' => $isDrugCase,
            'topDrugExhibits' => $topDrugExhibits,
        ];
    }

    /**
     * Classify exhibit categories (drug, electronic, crime scene) for a suspect's linked exhibits.
     */
    public function classifySuspectExhibits(array $suspectLinkedExhibits): array
    {
        $suspectHasDrug = false;
        $suspectHasElectronic = false;
        $suspectHasWeaponOrVehicle = false;
        $suspectHasMoneyOrGold = false;
        $suspectHasCrimeScene = false;

        foreach ($suspectLinkedExhibits as $ex) {
            $cat = strtolower($ex['category'] ?? ($ex['exhibit_category'] ?? ''));
            $type = strtolower($ex['exhibit_type'] ?? '');
            $sName = strtolower(
                $ex['seizure_name'] ?? ($ex['name'] ?? ($ex['commodity_name'] ?? ($ex['description'] ?? ''))),
            );

            if ($this->isDrugExhibit($ex)) {
                $suspectHasDrug = true;
            }

            if (
                str_contains($cat, 'electronic') || str_contains($cat, 'digital') || str_contains($cat, 'device')
                || str_contains($type, 'electronic') || str_contains($type, 'phone') || str_contains($type, 'laptop')
                || str_contains($sName, 'phone') || str_contains($sName, 'laptop') || str_contains($sName, 'sim')
                || str_contains($sName, 'hard drive') || str_contains($sName, 'flash drive') || str_contains($sName, 'tablet')
            ) {
                $suspectHasElectronic = true;
            }

            if (
                str_contains($cat, 'weapon') || str_contains($cat, 'ammunition') || str_contains($cat, 'vehicle')
                || str_contains($sName, 'weapon') || str_contains($sName, 'vehicle') || preg_match('/\bcar\b/i', $sName)
            ) {
                $suspectHasWeaponOrVehicle = true;
            }

            if (
                str_contains($cat, 'currency') || str_contains($cat, 'cash') || str_contains($cat, 'money') || str_contains($cat, 'gold')
                || str_contains($cat, 'precious') || str_contains($cat, 'stone')
                || str_contains($sName, 'cash')
            ) {
                $suspectHasMoneyOrGold = true;
            }
        }

        $suspectHasCrimeScene = $suspectHasWeaponOrVehicle || $suspectHasMoneyOrGold;
        $activeSuspectHasDrugExhibit = (bool) !empty($suspectHasDrug);
        $hasAnySpecialistUnit = $suspectHasCrimeScene || $suspectHasDrug || $suspectHasElectronic;

        return [
            'suspectHasDrug' => $suspectHasDrug,
            'suspectHasElectronic' => $suspectHasElectronic,
            'suspectHasCrimeScene' => $suspectHasCrimeScene,
            'suspectHasWeaponOrVehicle' => $suspectHasWeaponOrVehicle,
            'suspectHasMoneyOrGold' => $suspectHasMoneyOrGold,
            'activeSuspectHasDrugExhibit' => $activeSuspectHasDrugExhibit,
            'hasAnySpecialistUnit' => $hasAnySpecialistUnit,
        ];
    }
}

