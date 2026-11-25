<?php

namespace App\Services;

use App\Models\ForensicCase;
use Closure;

class ExhibitOwnershipResolver
{
    /**
     * Fuzzy name comparison between two suspect/owner names.
     */
    public function namesMatchFuzzy(?string $name1, ?string $name2): bool
    {
        if (empty($name1) || empty($name2)) {
            return false;
        }
        $n1 = strtolower(trim($name1));
        $n2 = strtolower(trim($name2));
        if ($n1 === $n2) {
            return true;
        }
        if (str_contains($n1, $n2) || str_contains($n2, $n1)) {
            return true;
        }

        $w1 = array_values(
            array_filter(
                explode(' ', preg_replace('/[^a-z0-9]/', ' ', $n1)),
                fn($w) => strlen($w) >= 2,
            ),
        );
        $w2 = array_values(
            array_filter(
                explode(' ', preg_replace('/[^a-z0-9]/', ' ', $n2)),
                fn($w) => strlen($w) >= 2,
            ),
        );

        if (empty($w1) || empty($w2)) {
            return false;
        }

        if (count($w1) >= 2 && count($w2) >= 2) {
            $last1 = end($w1);
            $last2 = end($w2);
            if ($last1 === $last2) {
                $first1 = $w1[0];
                $first2 = $w2[0];
                if (
                    $first1 === $first2 ||
                    str_starts_with($first1, substr($first2, 0, 3)) ||
                    str_starts_with($first2, substr($first1, 0, 3))
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check whether a candidate suspect name is a duplicate of any primary/co-suspect name.
     */
    public function isDuplicatePrimaryCoEntry(?string $nameToCheck, array $primaryAndCoNamesArray): bool
    {
        if (empty($nameToCheck) || empty($primaryAndCoNamesArray)) {
            return false;
        }
        $clean = strtolower(trim($nameToCheck));

        $parts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', $clean)));
        if (empty($parts)) {
            return false;
        }

        if (count($parts) > 1) {
            $allMatch = true;
            foreach ($parts as $p) {
                $found = false;
                foreach ($primaryAndCoNamesArray as $pName) {
                    if ($this->namesMatchFuzzy($p, $pName)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allMatch = false;
                    break;
                }
            }
            if ($allMatch) {
                return true;
            }
        }

        foreach ($primaryAndCoNamesArray as $pName) {
            if ($this->namesMatchFuzzy($clean, $pName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a name belongs to a primary or co-suspect.
     */
    public function isPrimaryOrCoSuspectName(?string $nameToCheck, array $primarySuspectNamesArray): bool
    {
        if (empty($nameToCheck)) {
            return true;
        }
        $clean = strtolower(trim($nameToCheck));
        if ($clean === 'primary suspect' || $clean === 'associate suspect' || $clean === 'all suspects') {
            return true;
        }

        foreach ($primarySuspectNamesArray as $pName) {
            if ($clean === $pName || str_contains($clean, $pName) || str_contains($pName, $clean)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether an exhibit belongs jointly to multiple suspects.
     */
    public function isJointExhibit(mixed $ex, array $primaryAndCoNamesList = []): bool
    {
        if (empty($ex)) {
            return false;
        }
        $owner = '';
        if (is_array($ex)) {
            $owner = $ex['associated_person'] ??
                ($ex['owner_name'] ??
                    ($ex['owner'] ??
                        ($ex['suspect_name'] ??
                            ($ex['associated_suspect'] ??
                                ($ex['veh_owner'] ??
                                    ($ex['wpn_owner'] ??
                                        ($ex['dev_owner'] ?? '')))))));
        } elseif (is_object($ex)) {
            $owner = $ex->associated_person ??
                ($ex->owner_name ??
                    ($ex->owner ??
                        ($ex->suspect_name ??
                            ($ex->associated_suspect ??
                                ($ex->veh_owner ??
                                    ($ex->wpn_owner ??
                                        ($ex->dev_owner ?? '')))))));
        }
        $cleanOwner = strtolower(trim((string) $owner));
        if (empty($cleanOwner)) {
            return false;
        }

        if (
            str_contains($cleanOwner, 'all suspect') ||
            str_contains($cleanOwner, 'both') ||
            str_contains($cleanOwner, 'joint') ||
            str_contains($cleanOwner, ',') ||
            str_contains($cleanOwner, ';') ||
            str_contains($cleanOwner, '&') ||
            str_contains($cleanOwner, ' and ')
        ) {
            return true;
        }

        if (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1) {
            $matched = 0;
            foreach ($primaryAndCoNamesList as $pName) {
                $pNameClean = strtolower(trim($pName));
                if (!empty($pNameClean) && str_contains($cleanOwner, $pNameClean)) {
                    $matched++;
                }
            }
            if ($matched > 1) {
                return true;
            }
        }
        return false;
    }

    public function getNamesMatchFuzzyClosure(): Closure
    {
        return fn(?string $n1, ?string $n2): bool => $this->namesMatchFuzzy($n1, $n2);
    }

    public function getIsDuplicatePrimaryCoEntryClosure(array $primaryAndCoNamesArray): Closure
    {
        return fn(?string $nameToCheck): bool => $this->isDuplicatePrimaryCoEntry($nameToCheck, $primaryAndCoNamesArray);
    }

    public function getIsPrimaryOrCoSuspectNameClosure(array $primarySuspectNamesArray): Closure
    {
        return fn(?string $nameToCheck): bool => $this->isPrimaryOrCoSuspectName($nameToCheck, $primarySuspectNamesArray);
    }

    public function getIsJointExhibitCheckClosure(array $primaryAndCoNamesList = []): Closure
    {
        return fn(mixed $ex): bool => $this->isJointExhibit($ex, $primaryAndCoNamesList);
    }

    /**
     * Resolve active suspect from docketData['suspects'] and request query parameter.
     */
    public function resolveActiveSuspect(mixed $case, array $docketData, ?string $reqSuspectParam = null): array
    {
        $allSuspectsInCase = [];
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as $sIndex => $sp) {
                if (!empty($sp['suspect_name'])) {
                    $spRole = $sp['association_role'] ?? ($sp['role'] ?? ($sIndex === 0 ? 'Primary Suspect' : 'Associate'));
                    $isPrimary = $sIndex === 0 || strtolower($spRole) === 'primary suspect';
                    $allSuspectsInCase[] = [
                        'id' => $sp['id'] ?? null,
                        'associate_id' => $sp['associate_id'] ?? ($sp['suspect_id'] ?? null),
                        'name' => trim($sp['suspect_name']),
                        'alias' => $sp['suspect_alias'] ?? 'N/A',
                        'role' => $spRole,
                        'is_primary' => $isPrimary,
                        'is_associate' => !$isPrimary,
                    ];
                }
            }
        }
        if (empty($allSuspectsInCase)) {
            $allSuspectsInCase[] = [
                'name' => trim($case->suspect_name ?? 'Primary Suspect'),
                'alias' => $case->suspect_alias ?? 'N/A',
                'role' => 'Primary Suspect',
                'is_primary' => true,
                'is_associate' => false,
            ];
        }

        $activeSuspectItem = $allSuspectsInCase[0];
        if (!empty($reqSuspectParam)) {
            foreach ($allSuspectsInCase as $sCandidate) {
                if (strtolower(trim($sCandidate['name'])) === strtolower(trim($reqSuspectParam))) {
                    $activeSuspectItem = $sCandidate;
                    break;
                }
            }
        }
        $activeSuspectName = $activeSuspectItem['name'];
        $activeSuspectNameLower = strtolower(trim($activeSuspectName));
        $isActiveSuspectPrimary = !empty($activeSuspectItem['is_primary']);

        return [
            'allSuspectsInCase' => $allSuspectsInCase,
            'reqSuspectParam' => $reqSuspectParam,
            'activeSuspectItem' => $activeSuspectItem,
            'activeSuspectName' => $activeSuspectName,
            'activeSuspectNameLower' => $activeSuspectNameLower,
            'isActiveSuspectPrimary' => $isActiveSuspectPrimary,
        ];
    }

    /**
     * Resolve initial suspect name lists from docket_data['suspects'] (used in top @php block).
     */
    public function resolveInitialSuspectNameLists(array $docketData): array
    {
        $primarySuspectNamesList = [];
        $coSuspectNamesList = [];
        $associateSuspectNamesList = [];
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as $sp) {
                $role = strtolower($sp['association_role'] ?? ($sp['role'] ?? ''));
                $spIsAssoc = !empty($sp['is_associate']) || (!empty($role) && $role !== 'primary suspect');
                $spIsCo =
                    $spIsAssoc &&
                    ((!empty($sp['association_role']) &&
                        (str_contains(strtolower($sp['association_role']), 'co-suspect') ||
                            str_contains(strtolower($sp['association_role']), 'co suspect'))) ||
                        (!empty($sp['role']) &&
                            (str_contains(strtolower($sp['role']), 'co-suspect') ||
                                str_contains(strtolower($sp['role']), 'co suspect'))) ||
                        (!empty($sp['suspect_role']) &&
                            (str_contains(strtolower($sp['suspect_role']), 'co-suspect') ||
                                str_contains(strtolower($sp['suspect_role']), 'co suspect'))));
                if (!$spIsAssoc && !empty($sp['suspect_name'])) {
                    $primarySuspectNamesList[] = $sp['suspect_name'];
                } elseif ($spIsCo && !empty($sp['suspect_name'])) {
                    $coSuspectNamesList[] = $sp['suspect_name'];
                } elseif ($spIsAssoc && !empty($sp['suspect_name'])) {
                    $associateSuspectNamesList[] = $sp['suspect_name'];
                }
            }
        }
        $primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));

        return [
            'primarySuspectNamesList' => $primarySuspectNamesList,
            'coSuspectNamesList' => $coSuspectNamesList,
            'associateSuspectNamesList' => $associateSuspectNamesList,
            'primaryAndCoNamesList' => $primaryAndCoNamesList,
        ];
    }

    /**
     * Resolve full deduplicated suspects list and ownership groupings for the dossier section.
     */
    public function resolveSuspectsListAndOwnership(mixed $case, array $docketData): array
    {
        $rawSuspects = is_object($case) && method_exists($case, 'getSuspectsList') ? $case->getSuspectsList() : [];
        if (empty($rawSuspects) && !empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            $rawSuspects = array_values(
                array_filter(
                    $docketData['suspects'],
                    fn($s) => !empty($s['suspect_name']) &&
                        trim($s['suspect_name']) !== 'Unnamed Suspect' &&
                        trim($s['suspect_name']) !== 'Unknown',
                ),
            );
        }

        $primaryAndCoNamesArray = [];
        if (!empty($rawSuspects)) {
            foreach ($rawSuspects as $s) {
                $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                $isAssoc =
                    !empty($s['is_associate']) ||
                    (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                if (!$isAssoc && !empty($s['suspect_name'])) {
                    $pParts = array_filter(
                        array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($s['suspect_name']))),
                    );
                    foreach ($pParts as $pp) {
                        if (!in_array($pp, $primaryAndCoNamesArray)) {
                            $primaryAndCoNamesArray[] = $pp;
                        }
                    }
                }
            }
        }
        if (empty($primaryAndCoNamesArray) && !empty($case->suspect_name)) {
            $pParts = array_filter(
                array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($case->suspect_name))),
            );
            if (!empty($pParts)) {
                $primaryAndCoNamesArray[] = $pParts[0];
            }
        }

        $suspectsList = [];
        if (!empty($rawSuspects)) {
            foreach ($rawSuspects as $s) {
                $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                $isAssoc =
                    !empty($s['is_associate']) ||
                    (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                if (
                    $isAssoc &&
                    !empty($s['suspect_name']) &&
                    $this->isDuplicatePrimaryCoEntry($s['suspect_name'], $primaryAndCoNamesArray)
                ) {
                    continue;
                }
                $suspectsList[] = $s;
            }
        }

        if (empty($suspectsList)) {
            $suspectsList = [
                [
                    'suspect_number' => 1,
                    'suspect_name' => $case->suspect_name,
                    'suspect_alias' => $case->suspect_alias ?? ($docketData['alias'] ?? null),
                    'ghana_card' => $case->ghana_card ?? ($docketData['id_card'] ?? null),
                    'gender' => $case->gender ?? ($docketData['gender'] ?? null),
                    'dob' => $case->dob ?? ($docketData['dob'] ?? null),
                    'age' => $case->age ?? ($docketData['age'] ?? null),
                    'height' => $case->height ?? ($docketData['height'] ?? null),
                    'eye_color' => $case->eye_color ?? ($docketData['eye_color'] ?? null),
                    'nationality' => $case->nationality ?? ($docketData['nationality'] ?? null),
                    'pob' => $case->pob ?? ($docketData['pob'] ?? null),
                    'phone' => $case->phone ?? ($docketData['phone'] ?? null),
                    'phones' => $case->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? null)),
                    'address' => $case->address ?? ($docketData['address'] ?? null),
                    'marital_status' => $case->marital_status ?? null,
                    'spouse_name' => $case->spouse_name ?? null,
                    'spouse_phone' => $case->spouse_phone ?? null,
                    'spouse_info' => $case->spouse_info ?? null,
                    'partner_name' => $case->partner_name ?? null,
                    'partner_phone' => $case->partner_phone ?? null,
                    'partner_info' => $case->partner_info ?? null,
                    'dependents' => $case->dependents ?? ($docketData['dependents'] ?? null),
                    'raid_location' => $case->raid_location ?: ($case->location ?: null),
                    'date_of_arrest' => $case->arrest_date ?? null,
                    'time_of_arrest' => $case->arrest_time ?? null,
                    'holding_facility' => $case->holding_facility ?? null,
                    'offense' => $case->offense_description ?: ($case->description ?: null),
                    'mugshot_front' => $case->mugshot_front ?? null,
                    'mugshot_left' => $case->mugshot_left ?? null,
                    'mugshot_right' => $case->mugshot_right ?? null,
                    'suspect_disposition' => ForensicCase::normalizeSuspectDisposition($case->suspect_disposition ?? ($case->underlyingRecord->suspect_disposition ?? null)),
                    'disposition_reason' => $case->disposition_reason ?? ($case->underlyingRecord->disposition_reason ?? null),
                    'disposition_updated_at' => $case->disposition_updated_at ?? ($case->underlyingRecord->disposition_updated_at ?? null),
                    'disposition_updated_by' => $case->disposition_updated_by ?? ($case->underlyingRecord->disposition_updated_by ?? null),
                ],
            ];
        } else {
            if (empty($suspectsList[0]['mugshot_front'])) {
                $suspectsList[0]['mugshot_front'] = $suspectsList[0]['photo']
                    ?? ($suspectsList[0]['mugshots']['front']
                    ?? ($case->mugshot_front
                    ?? ($case->photo
                    ?? ($docketData['mugshot_front']
                    ?? ($docketData['photo'] ?? null)))));
            }
            if (empty($suspectsList[0]['mugshot_left'])) {
                $suspectsList[0]['mugshot_left'] = $suspectsList[0]['mugshots']['left']
                    ?? ($case->mugshot_left
                    ?? ($docketData['mugshot_left'] ?? null));
            }
            if (empty($suspectsList[0]['mugshot_right'])) {
                $suspectsList[0]['mugshot_right'] = $suspectsList[0]['mugshots']['right']
                    ?? ($case->mugshot_right
                    ?? ($docketData['mugshot_right'] ?? null));
            }
        }

        $primarySuspectNamesList = [];
        $coSuspectNamesList = [];
        $associateSuspectNamesList = [];
        foreach ($suspectsList as $spIdx => $sp) {
            $spIsAssoc =
                !empty($sp['is_associate']) ||
                (!empty($sp['association_role']) &&
                    strtolower($sp['association_role']) !== 'primary suspect') ||
                (!empty($sp['role']) && strtolower($sp['role']) !== 'primary suspect');
            $spIsCo =
                $spIsAssoc &&
                ((!empty($sp['association_role']) &&
                    (str_contains(strtolower($sp['association_role']), 'co-suspect') ||
                        str_contains(strtolower($sp['association_role']), 'co suspect'))) ||
                    (!empty($sp['role']) &&
                        (str_contains(strtolower($sp['role']), 'co-suspect') ||
                            str_contains(strtolower($sp['role']), 'co suspect'))) ||
                    (!empty($sp['suspect_role']) &&
                        (str_contains(strtolower($sp['suspect_role']), 'co-suspect') ||
                            str_contains(strtolower($sp['suspect_role']), 'co suspect'))));
            $spNameRaw = $sp['suspect_name'] ?? '';
            if (str_contains($spNameRaw, ',')) {
                $spParts = array_map('trim', explode(',', $spNameRaw));
                $spNameClean = $spParts[$spIdx] ?? ($spParts[0] ?? $spNameRaw);
            } else {
                $spNameClean = $spNameRaw;
            }
            if (!$spIsAssoc && !empty($spNameClean)) {
                $primarySuspectNamesList[] = $spNameClean;
            } elseif ($spIsCo && !empty($spNameClean)) {
                $coSuspectNamesList[] = $spNameClean;
            } elseif ($spIsAssoc && !empty($spNameClean)) {
                $associateSuspectNamesList[] = $spNameClean;
            }
        }
        $primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));

        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $assocPerson = $sz['associated_person'] ?? null;
                if (
                    !empty($assocPerson) &&
                    strtolower(trim($assocPerson)) !== 'associate suspect' &&
                    strtolower(trim($assocPerson)) !== 'primary suspect' &&
                    strtolower(trim($assocPerson)) !== 'all suspects'
                ) {
                    $parts = array_filter(
                        array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($assocPerson))),
                    );
                    $isPrimaryOrCo = true;
                    foreach ($parts as $p) {
                        $found = false;
                        foreach ($primaryAndCoNamesList as $pName) {
                            if (
                                strtolower(trim($pName)) === $p ||
                                str_contains(strtolower(trim($pName)), $p) ||
                                str_contains($p, strtolower(trim($pName)))
                            ) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $isPrimaryOrCo = false;
                            break;
                        }
                    }
                    if (!$isPrimaryOrCo && !in_array($assocPerson, $associateSuspectNamesList)) {
                        $associateSuspectNamesList[] = $assocPerson;
                    }
                }
            }
        }

        $primarySuspectsCombinedNames = !empty($primaryAndCoNamesList)
            ? implode(', ', $primaryAndCoNamesList)
            : (!empty($primarySuspectNamesList)
                ? implode(', ', array_unique($primarySuspectNamesList))
                : ($case->suspect_name ?? 'Primary Suspect'));
        $associateSuspectsCombinedNames = !empty($associateSuspectNamesList)
            ? implode(', ', array_unique($associateSuspectNamesList))
            : 'Associate Suspect';

        $hasCoSuspectsInCase = false;
        if (!empty($rawSuspects) && is_array($rawSuspects) && count($rawSuspects) > 1) {
            foreach ($rawSuspects as $spCheck) {
                $rCheck = strtolower($spCheck['association_role'] ?? ($spCheck['role'] ?? ''));
                if (str_contains($rCheck, 'co-suspect') || !empty($spCheck['is_co_suspect'])) {
                    $hasCoSuspectsInCase = true;
                    break;
                }
            }
        }
        if (!$hasCoSuspectsInCase && !empty($coSuspectNamesList)) {
            $hasCoSuspectsInCase = true;
        }

        return [
            'rawSuspects' => $rawSuspects,
            'primaryAndCoNamesArray' => $primaryAndCoNamesArray,
            'namesMatchFuzzy' => $this->getNamesMatchFuzzyClosure(),
            'isDuplicatePrimaryCoEntry' => $this->getIsDuplicatePrimaryCoEntryClosure($primaryAndCoNamesArray),
            'suspectsList' => $suspectsList,
            'primarySuspectNamesList' => $primarySuspectNamesList,
            'coSuspectNamesList' => $coSuspectNamesList,
            'associateSuspectNamesList' => $associateSuspectNamesList,
            'primaryAndCoNamesList' => $primaryAndCoNamesList,
            'primarySuspectsCombinedNames' => $primarySuspectsCombinedNames,
            'associateSuspectsCombinedNames' => $associateSuspectsCombinedNames,
            'renderedPrimaryExhibit' => false,
            'hasCoSuspectsInCase' => $hasCoSuspectsInCase,
            'isJointExhibitCheck' => $this->getIsJointExhibitCheckClosure($primaryAndCoNamesList),
        ];
    }
}

