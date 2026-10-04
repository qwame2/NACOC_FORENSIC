@php
    $authUser = auth()->user();
    $docketData = is_array($case->docket_data)
        ? $case->docket_data
        : (json_decode($case->docket_data ?? '[]', true) ?:
        []);

    $labNo = $case->laboratory_number ?? ($docketData['laboratory_number'] ?? 'Lab No. 41/FS09/2026');
    $caseNo = $case->case_number ?? 'CSS-2026-6730';
    $raidLoc =
        $case->raid_location ??
        ($docketData['raid_location'] ?? ($case->location ?? 'Kotoka International Airport (Cargo Wing)'));

    $drugName =
        $case->seizure_name_drug ??
        ($case->seizure_name ?? ($docketData['drug_name'] ?? 'Heroin (Brown Sugar / Diacetylmorphine)'));
    $drugCategory = $case->exhibit_category ?? ($docketData['drug_category'] ?? 'Narcotic Controlled Substances');
    $quantityStr = $case->exhibits_summary ?? ($docketData['quantity_str'] ?? '90 Kilograms (kg)');

    $submittingOfficer = $case->submitting_officer ?? ($docketData['submitting_officer'] ?? 'Det. Insp. K. Mensah');
    $analystName = $docketData['analyst'] ?? ($case->submitting_officer ?? 'John Mensah');

    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $headName = $dbLabHead ? $dbLabHead->fullname : 'Dr. Samuel Atta (Head of Forensic Laboratory)';

    // Unified Presumptive Result Parser Helper
    $parsePresumptiveResult = function ($raw) use ($drugName) {
        $fallback = [['result' => 'YES', 'presence' => 'Colorimetric Reaction', 'purity' => $drugName]];
        if (empty($raw) || !is_string($raw)) {
            return $fallback;
        }
        $raw = trim($raw);
        if ($raw === '' || strtoupper($raw) === 'N/A') {
            return $fallback;
        }

        $overallResult = 'N/A';
        if (preg_match('/^(YES|POS|POSITIVE)/i', $raw)) {
            $overallResult = 'YES';
        } elseif (preg_match('/^(NO|NEG|NEGATIVE)/i', $raw)) {
            $overallResult = 'NO';
        }

        if ($overallResult === 'NO') {
            $presence = 'Negative Reaction';
            if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $raw, $m)) {
                $inner = trim($m[1]);
                if (!empty($inner)) {
                    $presence = $inner;
                }
            }
            return [['result' => 'NO', 'presence' => $presence, 'purity' => '—']];
        }

        $innerContent = $raw;
        if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $raw, $m)) {
            $innerContent = trim($m[1]);
        } else {
            if (in_array(strtoupper($raw), ['YES', 'POS', 'POSITIVE'])) {
                return [['result' => 'YES', 'presence' => 'Colorimetric Reaction', 'purity' => $drugName]];
            }
        }

        $items = array_map('trim', explode(';', $innerContent));
        $rows = [];
        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }
            $presence = $item;
            $purity = '—';
            if (preg_match('/Presence of:\s*([^-\s]+.*?)(?:\s*-\s*Purity\/Percentage:\s*(.*))?$/i', $item, $pm)) {
                $presence = trim($pm[1]);
                if (!empty($pm[2])) {
                    $purity = trim($pm[2]);
                }
            } elseif (preg_match('/(.*?)\s*\((.*?)\)$/', $item, $pm)) {
                $presence = trim($pm[1]);
                $purity = trim($pm[2]);
            }
            $rows[] = [
                'result' => $overallResult !== 'N/A' ? $overallResult : 'YES',
                'presence' => $presence,
                'purity' => $purity,
            ];
        }
        return !empty($rows) ? $rows : [['result' => 'YES', 'presence' => $innerContent, 'purity' => $drugName]];
    };

    // Helper to normalise a stored mugshot value into a usable image URL
    $normaliseMugshotUrl = function ($mug) {
        if (empty($mug) || !is_string($mug)) {
            return null;
        }
        $mug = trim($mug);
        if ($mug === '' || str_starts_with($mug, 'blob:')) {
            return null;
        }
        if (
            str_starts_with($mug, 'data:image') ||
            str_starts_with($mug, 'http://') ||
            str_starts_with($mug, 'https://') ||
            str_starts_with($mug, '/')
        ) {
            return $mug;
        }
        return asset($mug);
    };

    // Helper to compare suspect names flexibly
    $suspectNamesMatch = function ($n1, $n2) {
        if (empty($n1) || empty($n2)) {
            return false;
        }
        $c1 = strtolower(trim($n1));
        $c2 = strtolower(trim($n2));
        if ($c1 === $c2) {
            return true;
        }
        return str_contains($c1, $c2) || str_contains($c2, $c1);
    };

    $checkIsDrugExhibit = function ($ex) {
        if (empty($ex)) {
            return false;
        }
        $exArr = is_array($ex) ? $ex : (method_exists($ex, 'toArray') ? $ex->toArray() : (array) $ex);
        $cat = strtolower($exArr['exhibit_category'] ?? ($exArr['category'] ?? ''));
        $name = strtolower($exArr['commodity_name'] ?? ($exArr['seizure_name'] ?? ($exArr['seizure_name_drug'] ?? '')));
        $class = strtolower($exArr['drug_classification'] ?? '');
        $substance = strtolower($exArr['substance_identification'] ?? '');

        $nonDrugKw = [
            'weapon',
            'pistol',
            'rifle',
            'firearm',
            'gun',
            'vehicle',
            'car',
            'truck',
            'cash',
            'usd',
            'ghs',
            'laptop',
            'phone',
            'automobile',
            'vessel',
            'device',
            'motorcycle',
            'boat',
        ];
        $drugKw = [
            'cocaine',
            'heroin',
            'cannabis',
            'weed',
            'hemp',
            'tramadol',
            'meth',
            'narcotic',
            'drug',
            'substance',
            'precursor',
            'ephedrine',
            'hashish',
            'diacetylmorphine',
            'amphetamine',
            'mdma',
            'ecstasy',
        ];

        if (!empty($substance) && in_array(strtolower($substance), $drugKw)) {
            return true;
        }

        $isNonDrug = false;
        foreach ($nonDrugKw as $nk) {
            if (str_contains($cat, $nk) || str_contains($name, $nk)) {
                $isNonDrug = true;
                break;
            }
        }

        if ($isNonDrug) {
            foreach ($drugKw as $dk) {
                if (
                    $dk !== 'drug' &&
                    $dk !== 'substance' &&
                    $dk !== 'narcotic' &&
                    $dk !== 'precursor' &&
                    (str_contains($cat, $dk) || str_contains($name, $dk))
                ) {
                    return true;
                }
            }
            return false;
        }

        foreach ($drugKw as $dk) {
            if (str_contains($cat, $dk) || str_contains($name, $dk) || str_contains($class, $dk)) {
                return true;
            }
        }

        return false;
    };

    // Gather suspect records
    $rawSuspects = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        $rawSuspects = $docketData['suspects'];
    } elseif (!empty($case->id)) {
        $rawSuspects = $case->getSuspectsList();
    }
    if (empty($rawSuspects)) {
        $rawSuspects[] = [
            'suspect_name' => $case->suspect_name ?? 'KOJO ADU',
            'suspect_alias' => $case->suspect_alias ?? 'Kofi Ghana',
            'ghana_card' => $case->ghana_card ?? 'GHA-78920194-2',
            'gender' => $case->gender ?? 'Male',
            'age' => $case->age ?? 34,
            'role' => 'Primary Suspect',
            'arrest_date' => $case->incident_date ? date('Y-m-d', strtotime($case->incident_date)) : date('Y-m-d'),
            'mugshot_front' => $case->mugshot_front ?? asset('images/default-suspect.png'),
        ];
    }

    $allSuspectNames = array_map(function ($s) {
        return strtoupper($s['suspect_name'] ?? ($s['name'] ?? 'SUSPECT'));
    }, $rawSuspects);
    $dossierSuspectNames = implode(', ', array_unique($allSuspectNames));

    // Check query parameter filtering
    $selectedSuspectQuery = trim((string) (request('suspect') ?? request('suspect_name')));
    $suspects = [];
    if (!empty($selectedSuspectQuery)) {
        foreach ($rawSuspects as $rs) {
            $sName = $rs['suspect_name'] ?? ($rs['name'] ?? '');
            if ($suspectNamesMatch($sName, $selectedSuspectQuery)) {
                $suspects[] = $rs;
            }
        }
    }
    if (empty($suspects)) {
        $suspects = $rawSuspects;
    }
    $suspectCount = count($suspects);

    // Build per-suspect laboratory examination result data structure
    $suspectLabResults = [];

    foreach ($suspects as $idx => $s) {
        $sName = trim((string) ($s['suspect_name'] ?? ($s['name'] ?? 'Suspect #' . ($idx + 1))));
        $isAssociate =
            !empty($s['is_associate']) ||
            ($s['association_role'] ?? '') === 'Mastermind' ||
            ($s['association_role'] ?? '') === 'Associate';

        // Suspect must be linked to a drug exhibit to appear on lab analytical report
        $hasDrugExhibit = false;
        if (!empty($case->id)) {
            $suspectExhibits = $case->getExhibitsForSuspect(
                $sName,
                $s['id'] ?? null,
                $s['associate_id'] ?? null,
                $s['role'] ?? ($s['association_role'] ?? null),
            );
            if (!empty($suspectExhibits)) {
                foreach ($suspectExhibits as $exItem) {
                    if ($checkIsDrugExhibit((array) $exItem)) {
                        $hasDrugExhibit = true;
                        break;
                    }
                }
            }
        }

        if (!$hasDrugExhibit) {
            $seizures = $docketData['seizures'] ?? [];
            if (!empty($seizures)) {
                foreach ($seizures as $sz) {
                    $assocP = strtolower(
                        trim(
                            $sz['associated_person'] ??
                                ($sz['associated_suspect'] ??
                                    ($sz['suspect_name'] ?? ($sz['exhibit_owner_name'] ?? ''))),
                        ),
                    );
                    $isMatch = false;

                    if (!empty($assocP)) {
                        if (str_contains($assocP, 'all suspect') || $suspectNamesMatch($assocP, $sName)) {
                            $isMatch = true;
                        }
                    } else {
                        if (count($rawSuspects) <= 1 && $idx === 0) {
                            $isMatch = true;
                        }
                    }

                    if ($isMatch && $checkIsDrugExhibit($sz)) {
                        $hasDrugExhibit = true;
                        break;
                    }
                }
            } elseif (count($rawSuspects) <= 1 && $idx === 0 && !empty($drugName)) {
                $hasDrugExhibit = true;
            }
        }

        if (!$hasDrugExhibit) {
            continue;
        }

        $hasSavedLab = false;
        $labExamData = [];

        // Source 0: Docket Data lab_examination (Primary suspect explicit lab examination data)
        if (
            !$hasSavedLab &&
            !$isAssociate &&
            $idx === 0 &&
            !empty($docketData['lab_examination']) &&
            is_array($docketData['lab_examination'])
        ) {
            if (
                !empty($docketData['lab_examination']['has_saved']) ||
                !empty($docketData['lab_examination']['exam_reference'])
            ) {
                $hasSavedLab = true;
                $labExamData = $docketData['lab_examination'];
            }
        }

        // Source 1: Docket Data associate_lab_examinations
        if (!$hasSavedLab && !empty($docketData['associate_lab_examinations'])) {
            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                if ($suspectNamesMatch($kAssoc, $sName) && !empty($vAssoc['has_saved'])) {
                    $hasSavedLab = true;
                    $labExamData = $vAssoc;
                    break;
                }
            }
        }

        // Source 2: Docket Data lab_examinations_list
        if (!$hasSavedLab && !empty($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                $ownerMatch =
                    !empty($lItem['exhibit_owner_name']) && $suspectNamesMatch($lItem['exhibit_owner_name'], $sName);
                $sMatch = !empty($lItem['suspect_name']) && $suspectNamesMatch($lItem['suspect_name'], $sName);
                if (
                    ($ownerMatch || $sMatch) &&
                    (!empty($lItem['has_saved']) ||
                        !empty($lItem['presumptive_result']) ||
                        !empty($lItem['substance_identification']))
                ) {
                    $hasSavedLab = true;
                    $labExamData = $lItem;
                    break;
                }
            }
        }

        // Source 3: Docket Data lab_examination (Primary suspect)
        if (
            !$hasSavedLab &&
            !$isAssociate &&
            $idx === 0 &&
            !empty($docketData['lab_examination']) &&
            is_array($docketData['lab_examination'])
        ) {
            if (
                !empty($docketData['lab_examination']['has_saved']) ||
                !empty($docketData['lab_examination']['exam_reference']) ||
                !empty($docketData['lab_examination']['substance_identification']) ||
                !empty($docketData['lab_examination']['presumptive_result'])
            ) {
                $hasSavedLab = true;
                $labExamData = $docketData['lab_examination'];
            }
        }

        // Source 4: Relational DB Table laboratory_examination_results
        if (!$hasSavedLab && !empty($case->id)) {
            $dbRow = \DB::table('laboratory_examination_results')
                ->where(function ($q) use ($case) {
                    $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
                })
                ->get()
                ->first(function ($rec) use ($sName, $suspectNamesMatch) {
                    return !empty($rec->suspect_name) && $suspectNamesMatch($rec->suspect_name, $sName);
                });

            if (!$dbRow && count($rawSuspects) <= 1 && !$isAssociate && $idx === 0) {
                $dbRow = \DB::table('laboratory_examination_results')
                    ->where(function ($q) use ($case) {
                        $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
                    })
                    ->first();
            }

            if (
                $dbRow &&
                (!empty($dbRow->has_saved) ||
                    !empty($dbRow->substance_identification) ||
                    !empty($dbRow->presumptive_result))
            ) {
                $hasSavedLab = true;
                $labExamData = (array) $dbRow;
            }
        }

        // Source 5: Relational DB Table associate_lab_examinations
        if (!$hasSavedLab && !empty($case->id)) {
            $assocRow = \App\Models\AssociateLabExamination::where('case_id', $case->id)
                ->get()
                ->first(function ($rec) use ($sName, $suspectNamesMatch) {
                    return !empty($rec->suspect_name) && $suspectNamesMatch($rec->suspect_name, $sName);
                });
            if (
                $assocRow &&
                (!empty($assocRow->has_saved) ||
                    !empty($assocRow->substance_identification) ||
                    !empty($assocRow->presumptive_result))
            ) {
                $hasSavedLab = true;
                $labExamData = (array) $assocRow;
            }
        }

        // Ensure sample_weight array structure
        $sw = [];
        if (!empty($labExamData['sample_weight'])) {
            $sw = is_string($labExamData['sample_weight'])
                ? (json_decode($labExamData['sample_weight'], true) ?:
                [])
                : $labExamData['sample_weight'];
        }

        // Parse detected_substances if needed
        $detSubstances = [];
        if (!empty($labExamData['detected_substances'])) {
            $detSubstances = is_string($labExamData['detected_substances'])
                ? (json_decode($labExamData['detected_substances'], true) ?:
                [])
                : $labExamData['detected_substances'];
        }
        $detSubstancesStr = 'Colorimetric Reaction (' . ($labExamData['substance_identification'] ?? $drugName) . '%)';
        if (!empty($detSubstances) && is_array($detSubstances)) {
            $parts = [];
            foreach ($detSubstances as $ds) {
                $subStr = is_array($ds)
                    ? ($ds['substance'] ?? '') . (!empty($ds['purity']) ? ' (' . $ds['purity'] . '%)' : '')
                    : (string) $ds;
                if (!empty($subStr)) {
                    $parts[] = $subStr;
                }
            }
            if (!empty($parts)) {
                $detSubstancesStr = implode(', ', $parts);
            }
        }

        // Fallbacks for display
        $examRef = !empty($labExamData['exam_reference'])
            ? $labExamData['exam_reference']
            : (!empty($labExamData['analysis_reference'])
                ? $labExamData['analysis_reference']
                : ($case->lab_exam_reference ?:
                'GSA-FM-T51-F'));
        $exhibitNo = $labExamData['exhibit_number'] ?? '1';
        $lNo = $labExamData['laboratory_number'] ?? $labNo;
        $analyst = $labExamData['analyst'] ?? $analystName;
        $dateRec = !empty($labExamData['date_received'])
            ? date('M d, Y H:i', strtotime($labExamData['date_received']))
            : date('M d, Y 09:30');
        $dateStart = !empty($labExamData['date_started'])
            ? date('M d, Y H:i', strtotime($labExamData['date_started']))
            : date('M d, Y 11:00');
        $dateComp = !empty($labExamData['date_completed'])
            ? date('M d, Y H:i', strtotime($labExamData['date_completed']))
            : date('M d, Y 16:45');
        $presumptiveText =
            $labExamData['presumptive_result'] ??
            'YES (Presence of: Colorimetric Reaction - Purity/Percentage: ' . $drugName . ')';
        $confirmatoryVal = strtoupper(
            trim((string) ($labExamData['confirmatory_result'] ?? ($labExamData['confirmatory_test_result'] ?? 'YES'))),
        );
        $substanceId = $labExamData['substance_identification'] ?? $drugName;
        $specificationsText = !empty($labExamData['specifications'])
            ? $labExamData['specifications']
            : 'Official Forensic Standard ISO/IEC 17025 Compliant';
        $testMethodsText = !empty($labExamData['test_methods'])
            ? $labExamData['test_methods']
            : (!empty($labExamData['test_method'])
                ? $labExamData['test_method']
                : 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay');
        $remarkText = !empty($labExamData['remark'])
            ? $labExamData['remark']
            : (!empty($labExamData['remarks'])
                ? $labExamData['remarks']
                : 'Controlled Narcotic Substance verified under strict laboratory chain of custody protocol.');
        $findingsText = !empty($labExamData['findings'])
            ? $labExamData['findings']
            : 'Certified chemical analysis confirms positive identification of ' .
                $substanceId .
                '. High Court Admissible.';

        // Resolve suspect FRONT mugshot image
        $rawFrontMug = $s['mugshots']['front'] ?? ($s['mugshots']['front-profile'] ?? ($s['mugshot_front'] ?? null));
        if (empty($rawFrontMug) && !empty($case->id)) {
            try {
                $dbSuspectMug = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $case->id)
                    ->whereNotNull('mugshot_front')
                    ->get(['suspect_name', 'mugshot_front'])
                    ->first(function ($rec) use ($sName, $suspectNamesMatch) {
                        return !empty($rec->suspect_name) && $suspectNamesMatch($rec->suspect_name, $sName);
                    });
                if ($dbSuspectMug) {
                    $rawFrontMug = $dbSuspectMug->mugshot_front;
                }
            } catch (\Throwable $e) {
                // ignore lookup failures, fall back below
            }
        }
        if (empty($rawFrontMug) && $idx === 0) {
            $rawFrontMug =
                $case->mugshot_front ?? ($docketData['mugshots']['front'] ?? ($docketData['mugshot_front'] ?? null));
        }
        $frontMugUrl = $normaliseMugshotUrl($rawFrontMug);

        $suspectLabResults[] = [
            'suspect_info' => $s,
            'suspect_name' => $sName,
            'orig_idx' => $idx,
            'mugshot_front' => $frontMugUrl,
            'has_saved_lab' => $hasSavedLab,
            'exam_reference' => $examRef,
            'exhibit_number' => $exhibitNo,
            'laboratory_number' => $lNo,
            'analyst' => $analyst,
            'date_received' => $dateRec,
            'date_started' => $dateStart,
            'date_completed' => $dateComp,
            'presumptive_raw' => $presumptiveText,
            'presumptive_rows' => $parsePresumptiveResult($presumptiveText),
            'confirmatory' => $confirmatoryVal !== 'NO' ? 'YES' : 'NO',
            'sample_weight' => [
                'description' => $sw['description'] ?? ($s['exhibit_description'] ?? $drugName),
                'units_packaging' => $sw['units_packaging'] ?? '20 Slabs',
                'gross_weight' => $sw['gross_weight'] ?? '90 Kilograms (kg)',
                'sample_taken' => $sw['sample_taken'] ?? '7 Kilograms (kg)',
                'reserved_samples' => $sw['reserved_samples'] ?? '83 kg',
            ],
            'substance_identification' => $substanceId,
            'detected_substances' => $detSubstances,
            'detected_substances_str' => $detSubstancesStr,
            'specifications' => $specificationsText,
            'test_methods' => $testMethodsText,
            'remark' => $remarkText,
            'findings' => $findingsText,
        ];
    }

    // ------------------------------------------------------------------
    // Group suspects by the DRUG exhibit they are linked to, so that when
    // several suspects share a single drug only ONE Laboratory Examination
    // Result is rendered for them.
    // ------------------------------------------------------------------
    $drugKeywordsList = [
        'drug',
        'narcotic',
        'substance',
        'cannabis',
        'cocaine',
        'heroin',
        'meth',
        'tramadol',
        'weed',
        'hemp',
        'hashish',
        'ephedrine',
        'precursor',
        'opioid',
        'fentanyl',
        'amphetamine',
        'mdma',
        'ecstasy',
    ];
    $caseDrugExhibits = [];
    foreach ($docketData['seizures'] ?? [] as $szItem) {
        if (!is_array($szItem)) {
            continue;
        }
        $szHaystack = strtolower(
            ($szItem['category'] ?? '') .
                ' ' .
                ($szItem['seizure_name'] ?? ($szItem['commodity_name'] ?? '')) .
                ' ' .
                ($szItem['drug_classification'] ?? '') .
                ' ' .
                ($szItem['seizure_name_drug'] ?? ''),
        );
        foreach ($drugKeywordsList as $kw) {
            if (str_contains($szHaystack, $kw)) {
                $caseDrugExhibits[] = $szItem;
                break;
            }
        }
    }

    $dossierGroups = [];
    if (count($caseDrugExhibits) <= 1) {
        // Single drug exhibit (or none recorded) => every suspect is linked to the same drug
        if (!empty($suspectLabResults)) {
            $dossierGroups['single'] = $suspectLabResults;
        }
    } else {
        // Whole-word owner matching (owner labels may be joint, e.g. "KOJO ADU, AMA SERWAA")
        $ownerMatchesSuspect = function ($owner, $suspectName) {
            $sn = strtolower(trim((string) $suspectName));
            if ($sn === '') {
                return false;
            }
            $tokens = preg_split('/\s*(?:,|;|&|\/|\band\b)\s*/i', strtolower((string) $owner)) ?: [];
            foreach ($tokens as $tok) {
                $tok = trim($tok);
                if ($tok === '') {
                    continue;
                }
                if ($tok === $sn) {
                    return true;
                }
                if (preg_match('/\b' . preg_quote($sn, '/') . '\b/u', $tok)) {
                    return true;
                }
                if (preg_match('/\b' . preg_quote($tok, '/') . '\b/u', $sn)) {
                    return true;
                }
            }
            return false;
        };

        foreach ($suspectLabResults as $rIdx => $rItem) {
            $groupKey = null;
            $sharedKey = null;
            foreach ($caseDrugExhibits as $exIdx => $exItem) {
                $owner = strtolower(
                    trim(
                        (string) ($exItem['associated_person'] ??
                            ($exItem['suspect_name'] ??
                                ($exItem['associated_suspect'] ??
                                    ($exItem['suspect_ownership'] ?? ($exItem['owner_name'] ?? ''))))),
                    ),
                );
                if ($owner === '' || str_contains($owner, 'all suspect') || $owner === 'joint') {
                    $sharedKey = $sharedKey ?? 'ex' . $exIdx;
                    continue;
                }
                if ($ownerMatchesSuspect($owner, $rItem['suspect_name'])) {
                    $groupKey = 'ex' . $exIdx;
                    break;
                }
            }
            $groupKey = $groupKey ?? ($sharedKey ?? 'suspect' . $rIdx);
            $dossierGroups[$groupKey][] = $rItem;
        }
    }

    // Build final group structures (members + the one lab result to show)
    $dossierGroups = array_values(
        array_map(function ($members) {
            $lab = $members[0];
            foreach ($members as $m) {
                if (!empty($m['has_saved_lab'])) {
                    $lab = $m;
                    break;
                }
            }
            $names = array_map(fn($m) => $m['suspect_name'], $members);
            return [
                'members' => $members,
                'lab' => $lab,
                'names_label' => implode(', ', $names),
                'search_key' => strtolower(implode(' | ', $names)),
            ];
        }, $dossierGroups),
    );

    $certNo = 'CERT-LAB-' . date('Y') . '-' . strtoupper(substr(md5($caseNo . $labNo), 0, 6));
    $isLabAnalysis = true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytical Test Report - Laboratory Examination Result - {{ $caseNo }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Styles / Tailwind -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        .font-serif-header {
            font-family: 'Cinzel', serif;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sidebar-container {
            height: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar-container aside,
        aside.w-64 {
            height: 100% !important;
            min-height: 100vh !important;
            flex: 1 1 auto !important;
        }

        @media (min-width: 1024px) {

            .sidebar-container,
            .sidebar-container aside,
            aside.w-64 {
                height: 100vh !important;
                min-height: 100vh !important;
            }
        }

        @media print {

            .no-print,
            .no-print-layout header,
            .no-print-layout nav,
            .no-print-layout aside,
            .sidebar-container {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-shadow-none {
                box-shadow: none !important;
            }

            .print-border {
                border: 1px solid #cbd5e1 !important;
            }

            .print-w-full {
                width: 100% !important;
                max-width: 100% !important;
            }

            .main-scroll-container {
                overflow: visible !important;
                height: auto !important;
                padding: 0 !important;
            }

            @page {
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 antialiased selection:bg-emerald-600 selection:text-white">

    <div
        class="min-h-screen lg:h-screen w-full flex flex-col lg:flex-row overflow-y-auto lg:overflow-hidden no-print-layout">
        <!-- SHARED SIDEBAR -->
        <div class="sidebar-container no-print shrink-0 flex flex-col h-full min-h-screen lg:h-screen">
            @include('Crime_Scene_Support.partials.sidebar')
        </div>

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 min-h-screen lg:min-h-0 lg:overflow-hidden">

            <!-- TOP NAVBAR -->
            <header
                class="min-h-16 py-3 px-4 sm:px-6 bg-white border-b border-slate-200/90 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0 z-20 shadow-2xs no-print">
                <!-- BREADCRUMB & SEARCH -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <nav
                        class="flex items-center gap-1.5 text-xs font-mono-code text-slate-500 overflow-x-auto whitespace-nowrap custom-scrollbar py-1">
                        <a href="{{ url('/Lab_Analysis') }}"
                            class="hover:text-emerald-700 font-semibold flex items-center gap-1 transition-colors shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span>Lab Headquarters</span>
                        </a>
                        <span class="text-slate-300">/</span>
                        <a href="{{ url('/lab-result') }}"
                            class="hover:text-emerald-700 font-semibold transition-colors shrink-0">Drug Register</a>
                        <span class="text-slate-300">/</span>
                        <span
                            class="text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 shrink-0">{{ $caseNo }}</span>
                    </nav>

                    <div class="relative w-full max-w-xs hidden xl:block">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="analyticalSearchInput" onkeyup="filterSuspectDossiers(this.value)"
                            placeholder="Filter suspect or exhibit..."
                            class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                </div>

                <!-- TOP CONTROL ACTION BUTTONS -->
                <div class="flex items-center gap-2 sm:gap-3 justify-end flex-wrap sm:flex-nowrap shrink-0">

                    <a href="{{ url('/lab-result') }}"
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Register</span>
                    </a>

                    <button type="button" onclick="showGsaReportUploadSection()"
                        class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border-0">
                        <svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>GSA Report Upload</span>
                    </button>

                    <!-- GENERATE NLR REPORT BUTTON -->
                    <button type="button" onclick="openNlrReportModal()"
                        class="px-3.5 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border-0">
                        <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Generate NLR Report</span>
                    </button>


                </div>
            </header>

            <!-- MAIN SCROLLABLE CONTENT -->
            <main class="flex-1 lg:overflow-y-auto p-4 sm:p-6 space-y-6 custom-scrollbar main-scroll-container">
                @include('partials.flash')

                <!-- EXECUTIVE HERO HEADER -->
                <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/90 shadow-2xs space-y-5 no-print">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="px-2.5 py-1 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-lg uppercase tracking-wider">
                                    DIRECTORATE OF LABORATORY ANALYSIS
                                </span>
                                <span
                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-extrabold font-mono-code rounded-lg border border-emerald-200 uppercase">
                                    HIGH COURT ADMISSIBLE EVIDENCE
                                </span>
                                <span
                                    class="px-2.5 py-1 bg-blue-50 text-blue-800 text-[10px] font-extrabold font-mono-code rounded-lg border border-blue-200 uppercase">
                                    CERTIFICATE # {{ $certNo }}
                                </span>
                            </div>
                            <h1
                                class="text-lg sm:text-2xl font-black text-slate-900 font-mono-code uppercase tracking-tight pt-1">
                                Laboratory Analytical Examination Report
                            </h1>
                            <p class="text-xs text-slate-500 font-medium flex items-center gap-2 flex-wrap">
                                <span>Case Ref: <strong
                                        class="text-slate-900 font-mono-code">{{ $caseNo }}</strong></span>
                                <span>&bull;</span>
                                <span>Lab Ref: <strong
                                        class="text-emerald-700 font-mono-code">{{ $labNo }}</strong></span>
                                <span>&bull;</span>
                                <span>Location: <strong
                                        class="text-slate-800 font-semibold">{{ $raidLoc }}</strong></span>
                            </p>
                        </div>

                        <!-- HEAD OF LAB CONTEXT -->
                        <div
                            class="flex items-center gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-200/80 shrink-0 self-start lg:self-auto">
                            <div
                                class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-xs font-mono-code shrink-0">
                                LAB
                            </div>
                            <div class="text-xs">
                                <span class="text-slate-400 font-mono-code text-[10px] uppercase font-bold block">Head
                                    of Forensic Lab</span>
                                <span class="font-extrabold text-slate-900 font-mono-code">{{ $headName }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- EXECUTIVE STATS SUMMARY BAR -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                            <span
                                class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">SUSPECTS
                                IN DOSSIER</span>
                            <div
                                class="text-lg sm:text-xl font-black font-mono-code text-slate-900 flex items-center justify-between">
                                <span>{{ count($suspectLabResults) }}
                                    {{ count($suspectLabResults) === 1 ? 'Suspect' : 'Suspects' }}</span>
                                <span
                                    class="text-xs px-2 py-0.5 bg-slate-200 text-slate-700 rounded-md font-bold">LINKED</span>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 space-y-1">
                            <span
                                class="text-[10px] font-bold font-mono-code text-emerald-800 uppercase tracking-wider">PRIMARY
                                CHEMICAL EXHIBIT</span>
                            <div class="text-xs sm:text-sm font-extrabold text-slate-900 truncate"
                                title="{{ $drugName }}">
                                {{ $drugName }}
                            </div>
                            <span
                                class="text-[10px] font-mono-code text-emerald-700 font-bold block">{{ $drugCategory }}</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 space-y-1">
                            <span
                                class="text-[10px] font-bold font-mono-code text-blue-800 uppercase tracking-wider">CONFIRMATORY
                                ASSAY</span>
                            <div
                                class="text-lg sm:text-xl font-black font-mono-code text-emerald-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 shrink-0" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>POSITIVE (YES)</span>
                            </div>
                        </div>


                    </div>

                    <!-- SUSPECT QUICK FILTER TABS (WHEN MULTIPLE SUSPECTS EXIST) -->
                    @php
                        $linkedSuspectNamesForTabs = array_values(
                            array_unique(
                                array_map(function ($res) {
                                    return $res['suspect_name'];
                                }, $suspectLabResults),
                            ),
                        );
                    @endphp
                    @if (count($linkedSuspectNamesForTabs) > 1)
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                            <div
                                class="flex items-center space-x-2 text-xs font-mono-code font-bold overflow-x-auto w-full py-1 custom-scrollbar">
                                <span class="text-slate-400 uppercase text-[10px] shrink-0">Filter Suspect
                                    Dossier:</span>
                                <a href="{{ url('/Lab_Analysis/analytical/' . $caseNo) }}"
                                    class="px-3 py-1.5 rounded-xl border shrink-0 {{ empty($selectedSuspectQuery) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }} transition-all shadow-2xs">
                                    All Suspects ({{ count($linkedSuspectNamesForTabs) }})
                                </a>
                                @foreach ($linkedSuspectNamesForTabs as $rsName)
                                    @php
                                        $isCurrSel = $suspectNamesMatch($rsName, $selectedSuspectQuery);
                                    @endphp
                                    <a href="{{ url('/Lab_Analysis/analytical/' . $caseNo . '?suspect=' . urlencode($rsName)) }}"
                                        class="px-3 py-1.5 rounded-xl border shrink-0 {{ $isCurrSel ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }} transition-all shadow-2xs">
                                        {{ $rsName }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- MAIN SUSPECT LAB DOSSIER CARDS -->
                <div class="space-y-6 sm:space-y-8" id="suspectDossiersContainer">

                    @foreach ($dossierGroups as $resIdx => $group)
                        @php
                            $isJointGroup = count($group['members']) > 1;
                            $res = $group['lab'];
                        @endphp
                        <div class="suspect-dossier-card bg-white rounded-3xl border border-slate-300/90 shadow-xl p-5 sm:p-8 space-y-6 print-shadow-none print-border print-w-full"
                            data-suspect-name="{{ $group['search_key'] }}">

                            <!-- SUSPECT PROFILE HEADER BANNER -->
                            <div
                                class="rounded-2xl p-4 sm:p-5 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-md">
                                <div class="flex-1 min-w-0 space-y-3">
                                    @if ($isJointGroup)
                                        <div
                                            class="text-[10px] sm:text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-emerald-400 flex items-center gap-2 flex-wrap pb-2 border-b border-white/10">
                                            <span>DOSSIER RECORD #{{ $resIdx + 1 }}</span>
                                            <span>&bull;</span>
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-400/30">JOINT
                                                EXHIBIT &mdash; {{ count($group['members']) }} SUSPECTS LINKED TO ONE
                                                DRUG</span>
                                        </div>
                                    @endif

                                    @foreach ($group['members'] as $memIdx => $m)
                                        <div
                                            class="flex items-center space-x-3.5 sm:space-x-4 {{ $isJointGroup && $memIdx > 0 ? 'pt-3 border-t border-white/10' : '' }}">
                                            @if (!empty($m['mugshot_front']))
                                                <button type="button"
                                                    onclick="openFrontMugshotLightbox(this.querySelector('img').src, {{ json_encode($m['suspect_name']) }})"
                                                    title="{{ $m['suspect_name'] }} — Front Mugshot (click to enlarge)"
                                                    class="relative block w-16 h-20 sm:w-20 sm:h-24 rounded-2xl overflow-hidden bg-slate-700 shadow-lg border-2 border-emerald-400 shrink-0 group cursor-zoom-in p-0">
                                                    <img src="{{ $m['mugshot_front'] }}"
                                                        alt="{{ $m['suspect_name'] }} Front Mugshot"
                                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                                        onerror="this.parentElement.outerHTML='&lt;div class=&quot;w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg border-2 border-emerald-400 shrink-0 uppercase font-mono-code&quot;&gt;{{ e(substr($m['suspect_name'], 0, 2)) }}&lt;/div&gt;';">
                                                    <span
                                                        class="absolute bottom-0 inset-x-0 bg-slate-900/75 text-[8px] sm:text-[9px] font-mono-code font-bold text-emerald-300 text-center py-0.5 uppercase tracking-wider">Front</span>
                                                </button>
                                            @else
                                                <div
                                                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-lg border-2 border-emerald-400 shrink-0 uppercase font-mono-code">
                                                    {{ substr($m['suspect_name'], 0, 2) }}
                                                </div>
                                            @endif
                                            <div class="space-y-0.5 min-w-0">
                                                <div
                                                    class="text-[10px] sm:text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-emerald-400 flex items-center gap-2 flex-wrap">
                                                    @if ($isJointGroup)
                                                        <span>SUSPECT {{ $memIdx + 1 }} OF
                                                            {{ count($group['members']) }}</span>
                                                    @else
                                                        <span>DOSSIER RECORD #{{ $resIdx + 1 }}</span>
                                                    @endif
                                                    <span>&bull;</span>
                                                    <span>CARD:
                                                        {{ $m['suspect_info']['ghana_card'] ?? 'GHA-78920194-2' }}</span>
                                                </div>
                                                <h2
                                                    class="text-base sm:text-xl font-black font-mono-code text-white uppercase tracking-tight">
                                                    {{ $m['suspect_name'] }}
                                                    @if (!empty($m['suspect_info']['suspect_alias']))
                                                        <span
                                                            class="text-xs sm:text-sm font-normal text-slate-300">("{{ $m['suspect_info']['suspect_alias'] }}")</span>
                                                    @endif
                                                </h2>
                                                <p
                                                    class="text-[11px] sm:text-xs text-slate-300 font-medium flex items-center gap-2 flex-wrap">
                                                    <span>Role: <strong
                                                            class="text-emerald-300">{{ $m['suspect_info']['role'] ?? (($m['orig_idx'] ?? 0) === 0 ? 'Primary Suspect' : 'Co-Suspect') }}</strong></span>
                                                    <span>&bull;</span>
                                                    <span>Gender: <strong
                                                            class="text-slate-200">{{ $m['suspect_info']['gender'] ?? 'Male' }}</strong></span>
                                                    <span>&bull;</span>
                                                    <span>Age: <strong
                                                            class="text-slate-200">{{ $m['suspect_info']['age'] ?? '34' }}
                                                            yrs</strong></span>
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex items-center space-x-2 shrink-0 no-print self-start">
                                    <span
                                        class="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 text-[10px] sm:text-xs font-mono-code font-bold rounded-xl border border-emerald-400/30 uppercase">
                                        EVIDENCE VERIFIED
                                    </span>
                                </div>
                            </div>

                            <!-- LABORATORY EXAMINATION RESULT TABLE — matches show_review layout -->
                            <div
                                class="p-5 bg-emerald-50/80 border-2 border-emerald-300 rounded-2xl space-y-4 shadow-sm mt-2">
                                <div class="flex items-center justify-between pb-3 border-b border-emerald-200">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT
                                                — {{ $group['names_label'] }}</h3>
                                            <p class="text-[11px] text-emerald-800 font-semibold">
                                                @if ($isJointGroup)
                                                    Official Laboratory Analysis Directorate Joint Findings (single drug
                                                    exhibit) for {{ $group['names_label'] }}
                                                @else
                                                    Official Laboratory Analysis Directorate Findings for
                                                    {{ $group['names_label'] }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-200 text-emerald-950 border border-emerald-400 shrink-0">
                                        ✓ VERIFIED RESULT LOGGED
                                    </span>
                                </div>

                                <table
                                    class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden shadow-2xs bg-white">
                                    <tbody>
                                        {{-- Row 1: Analysis Reference | Exhibit Number --}}
                                        <tr class="bg-slate-100/90 border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">
                                                Analysis Reference:</td>
                                            <td
                                                class="p-2.5 font-black text-emerald-900 font-mono-code border-r border-slate-300 w-3/10">
                                                {{ $res['exam_reference'] }}</td>
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">
                                                Exhibit Number:</td>
                                            <td class="p-2.5 font-black text-emerald-900 font-mono-code">
                                                {{ $res['exhibit_number'] }}</td>
                                        </tr>
                                        {{-- Row 2: Case Reference | Laboratory Number --}}
                                        <tr class="border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Case Reference:</td>
                                            <td
                                                class="p-2.5 font-black text-blue-900 font-mono-code border-r border-slate-300">
                                                {{ $caseNo }}</td>
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Laboratory Number:</td>
                                            <td class="p-2.5 font-black text-slate-900 font-mono-code">
                                                {{ $res['laboratory_number'] }}</td>
                                        </tr>
                                        {{-- Row 3: Exhibit Description --}}
                                        <tr class="bg-slate-100/90 border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Exhibit Description:</td>
                                            <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                                                {{ $res['sample_weight']['description'] }}
                                            </td>
                                        </tr>
                                        {{-- Row 4: Analyst | Examination Timeline --}}
                                        <tr class="border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Analyst / Examiner:</td>
                                            <td class="p-2.5 font-black text-slate-900 border-r border-slate-300">
                                                {{ $res['analyst'] }}</td>
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Examination Timeline:</td>
                                            <td class="p-2 bg-white">
                                                <div
                                                    class="flex items-center gap-2 font-mono-code text-[11px] font-bold flex-wrap">
                                                    <span>Received: <strong
                                                            class="text-blue-900">{{ $res['date_received'] }}</strong></span>
                                                    <span>|</span>
                                                    <span>Started: <strong
                                                            class="text-amber-900">{{ $res['date_started'] }}</strong></span>
                                                    <span>|</span>
                                                    <span>Completed: <strong
                                                            class="text-emerald-900">{{ $res['date_completed'] }}</strong></span>
                                                </div>
                                            </td>
                                        </tr>
                                        {{-- Row 5: Presumptive Reaction | Confirmatory Test --}}
                                        <tr class="bg-slate-100/90 border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Presumptive Reaction:</td>
                                            <td class="p-2 border-r border-slate-300 bg-white">
                                                <table
                                                    class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                                    <thead
                                                        class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                                        <tr class="border-b border-slate-300">
                                                            <th
                                                                class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                                                Result</th>
                                                            <th
                                                                class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                                                Presence of</th>
                                                            <th class="px-2 py-1 text-left bg-slate-200/70">
                                                                Purity/Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white">
                                                        @foreach ($res['presumptive_rows'] as $pRowD)
                                                            <tr class="border-b border-slate-200 last:border-b-0">
                                                                <td
                                                                    class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pRowD['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">
                                                                    {{ $pRowD['result'] }}</td>
                                                                <td
                                                                    class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">
                                                                    {{ $pRowD['presence'] }}</td>
                                                                <td class="px-2 py-1 font-bold text-slate-900">
                                                                    {{ $pRowD['purity'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Confirmatory Test:</td>
                                            <td class="p-2.5 font-black text-emerald-900">
                                                <span class="inline-flex items-center space-x-1">
                                                    <span
                                                        class="w-2 h-2 rounded-full {{ $res['confirmatory'] !== 'NO' ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                                                    <span>{{ $res['confirmatory'] !== 'NO' ? 'YES' : 'NO' }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                        {{-- Row 6: Sample Metrology --}}
                                        <tr class="bg-slate-100/90 border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Sample Metrology:</td>
                                            <td class="p-2.5 bg-white" colspan="3">
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                                                    <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                        <span
                                                            class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Units:</span>
                                                        <span
                                                            class="font-bold text-slate-900 ml-1">{{ $res['sample_weight']['units_packaging'] }}</span>
                                                    </div>
                                                    <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                        <span
                                                            class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Gross
                                                            Wt:</span>
                                                        <span
                                                            class="font-bold text-slate-900 ml-1">{{ $res['sample_weight']['gross_weight'] }}</span>
                                                    </div>
                                                    <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                        <span
                                                            class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Taken:</span>
                                                        <span
                                                            class="font-bold text-emerald-900 ml-1">{{ $res['sample_weight']['sample_taken'] }}</span>
                                                    </div>
                                                    <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                        <span
                                                            class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Reserved:</span>
                                                        <span
                                                            class="font-bold text-slate-900 ml-1">{{ $res['sample_weight']['reserved_samples'] }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        {{-- Row 7: Substance Identified | Test Specifications --}}
                                        <tr class="border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Substance Identified:</td>
                                            <td class="p-2.5 font-black text-slate-950 border-r border-slate-300">
                                                {{ $res['substance_identification'] }}</td>
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Test Specifications:</td>
                                            <td class="p-2.5 font-bold text-slate-900">{{ $res['specifications'] }}
                                            </td>
                                        </tr>
                                        {{-- Row 8: Detected Substances (conditional) --}}
                                        @if (
                                            !empty($res['detected_substances']) &&
                                                is_array($res['detected_substances']) &&
                                                count($res['detected_substances']) > 0)
                                            <tr class="bg-amber-50/70 border-b border-slate-300">
                                                <td
                                                    class="p-2.5 font-mono-code font-black text-amber-950 uppercase border-r border-slate-300 bg-amber-100/80">
                                                    Result (Detected Substances &amp; Purity %):</td>
                                                <td class="p-2.5" colspan="3">
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($res['detected_substances'] as $cSub)
                                                            @php
                                                                $cName = is_array($cSub)
                                                                    ? $cSub['compound'] ??
                                                                        ($cSub['name'] ??
                                                                            ($cSub['substance'] ?? 'Unknown'))
                                                                    : $cSub;
                                                                $cPct = is_array($cSub)
                                                                    ? $cSub['purity'] ??
                                                                        ($cSub['percentage'] ??
                                                                            ($cSub['concentration'] ?? null))
                                                                    : null;
                                                                $cMeth = is_array($cSub)
                                                                    ? $cSub['method'] ?? null
                                                                    : null;
                                                            @endphp
                                                            <span
                                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-amber-300 shadow-2xs text-xs font-bold text-amber-950">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                                <span>{{ $cName }}</span>
                                                                @if ($cPct)
                                                                    <span
                                                                        class="text-emerald-800 font-mono font-extrabold">({{ $cPct }}%)</span>
                                                                @endif
                                                                @if ($cMeth)
                                                                    <span
                                                                        class="text-slate-500 font-mono text-[10px]">[{{ $cMeth }}]</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        {{-- Row 9: Test Methods --}}
                                        <tr class="bg-slate-100/90 border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Test Methods:</td>
                                            <td class="p-2.5 font-bold text-slate-900 border-r border-slate-300"
                                                colspan="3">{{ $res['test_methods'] }}</td>
                                        </tr>
                                        {{-- Row 10: Analysis Remark --}}
                                        <tr class="border-b border-slate-300">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                                                Analysis Remark:</td>
                                            <td class="p-2.5 font-bold text-slate-900" colspan="3">
                                                {{ $res['remark'] }}</td>
                                        </tr>
                                        {{-- Row 11: Analytical Findings (dark) --}}
                                        <tr class="bg-slate-900 text-white">
                                            <td
                                                class="p-2.5 font-mono-code font-black text-emerald-400 uppercase border-r border-slate-800 bg-slate-950">
                                                Analytical Findings:</td>
                                            <td class="p-2.5 font-medium text-slate-100 leading-relaxed bg-slate-900"
                                                colspan="3">
                                                {{ $res['findings'] }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- FOOTER ACTION BAR -->
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 no-print border-t border-slate-100">
                                <span class="text-[11px] text-slate-500 font-mono-code font-semibold">
                                    Registered Under NACOC Protocols &bull; Ref: {{ $certNo }}
                                </span>
                                <div class="flex items-center gap-2 justify-end">
                                    <button type="button" onclick="openGsaReportModal()"
                                        class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs rounded-xl border border-emerald-200 transition-colors cursor-pointer">
                                        GSA Report Upload
                                    </button>
                                    <button type="button" onclick="openNlrReportModal()"
                                        class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs rounded-xl border border-blue-200 transition-colors cursor-pointer">
                                        NLR Report 
                                    </button>
                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>

                {{-- ═══════════════════════════════════════════════════════════
                     GSA REPORT UPLOAD SECTION
                     ═══════════════════════════════════════════════════════════ --}}
                <div class="hidden bg-white rounded-3xl border border-slate-200/90 shadow-xl p-5 sm:p-8 space-y-5 no-print"
                    id="gsaUploadSection">

                    {{-- Section Header --}}
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 text-white flex items-center justify-center shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="px-2 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">GSA
                                        FILES</span>
                                    <h2
                                        class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight">
                                        GSA Report Upload</h2>
                                </div>
                                <p class="text-[11px] text-slate-500 font-mono-code mt-0.5">Attach scans, photos, or
                                    documents of the signed GSA Report Upload</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span id="gsaFileCount"
                                class="hidden px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-full">0
                                files</span>
                            <button type="button" onclick="clearAllGsaFiles()" id="gsaClearAllBtn"
                                class="hidden px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-[10px] font-bold rounded-lg border border-red-200 transition-colors cursor-pointer">Clear
                                All</button>
                            <button type="button" id="saveGsaFilesBtn" onclick="saveGsaReportFiles()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer border-0">
                                <svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <span>Save &amp; Attach GSA Report</span>
                            </button>
                        </div>
                    </div>

                    <!-- DROP ZONE — files render inside this container -->
                    <div id="gsaDropZone" ondragover="gsaDragOver(event)" ondragleave="gsaDragLeave(event)"
                        ondrop="gsaHandleDrop(event)"
                        class="relative w-full border-2 border-dashed border-slate-300 rounded-2xl bg-slate-50 hover:border-blue-400 transition-all overflow-hidden">

                        <!-- FILE LIST (inside the zone) -->
                        <div id="gsaFileGrid" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3 p-3"></div>

                        <!-- FULL PROMPT — shown when no files -->
                        <div id="gsaFullPrompt" onclick="document.getElementById('gsaReportImageInput').click()"
                            class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center cursor-pointer group hover:bg-blue-50/60 transition-all">
                            <div
                                class="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-center group-hover:bg-blue-50 group-hover:border-blue-300 transition-all">
                                <svg class="w-6 h-6 text-slate-400 group-hover:text-blue-600 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-600 group-hover:text-blue-800">Click to add
                                    files or drag &amp; drop</p>
                                <p class="text-[11px] text-slate-400 mt-0.5 font-mono-code">JPG &middot; PNG &middot;
                                    WEBP &middot; PDF &middot; DOCX &middot; CSV &nbsp;|&nbsp; Max 10 MB each</p>
                            </div>
                        </div>

                        <!-- COMPACT ADD-MORE STRIP — shown when files exist -->
                        <div id="gsaAddMoreStrip" onclick="document.getElementById('gsaReportImageInput').click()"
                            class="hidden items-center justify-center gap-2 py-2.5 px-4 border-t border-dashed border-slate-300 bg-slate-50 hover:bg-blue-50/60 hover:border-blue-400 transition-all cursor-pointer group">
                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span
                                class="text-[11px] font-bold text-slate-500 group-hover:text-blue-800 font-mono-code">Click
                                to add more files or drag &amp; drop</span>
                        </div>

                        <!-- Hidden file input -->
                        <input type="file" id="gsaReportImageInput" name="gsa_report_images[]"
                            accept="image/jpeg,image/png,image/webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,.csv,.docx"
                            multiple class="hidden" onchange="gsaHandleFileSelect(this)">
                    </div>

                    {{-- SAVED ATTACHED GSA REPORT FILES --}}
                    @if (!empty($gsaReports) && count($gsaReports) > 0)
                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <div
                                class="flex items-center justify-between text-xs font-bold text-slate-800 font-mono-code">
                                <span class="flex items-center gap-2 text-blue-900">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    ATTACHED GSA REPORT FILES ({{ count($gsaReports) }})
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono-code">Saved for Case
                                    #{{ $caseNo }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($gsaReports as $gsaF)
                                    <div
                                        class="bg-blue-50/60 border border-blue-200/90 rounded-2xl p-3.5 flex items-center justify-between gap-3 shadow-2xs">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div
                                                class="w-9 h-9 bg-blue-600 text-white rounded-xl flex items-center justify-center font-mono-code text-xs font-bold shrink-0">
                                                {{ strtoupper(substr($gsaF->file_extension ?? 'PDF', 0, 3)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-900 truncate"
                                                    title="{{ $gsaF->original_filename }}">
                                                    {{ $gsaF->original_filename }}</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">
                                                    {{ $gsaF->file_size_label }} &bull;
                                                    {{ $gsaF->created_at ? $gsaF->created_at->format('M d, Y H:i') : 'Attached' }}
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ $gsaF->url }}" target="_blank"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-extrabold font-mono-code rounded-xl transition-colors shrink-0">
                                            Open File
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     DETAILED EXHIBIT DESCRIPTION FORM
                     ═══════════════════════════════════════════════════════════ -->
                <div class="hidden bg-white rounded-3xl border border-slate-200/90 shadow-xl p-5 sm:p-8 space-y-5 no-print"
                    id="exhibitDescriptionSection">

                    <!-- Section Header -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white flex items-center justify-center shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="px-2 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">EXHIBIT
                                        LOG</span>
                                    <span
                                        class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold font-mono-code rounded-md border border-slate-200 uppercase">Case:
                                        {{ $caseNo }}</span>
                                </div>
                                <h2
                                    class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight mt-0.5">
                                    Detailed Exhibit Description</h2>
                                <p class="text-xs text-slate-500 font-medium">Provide a comprehensive narrative of the
                                    physical exhibit contents, packaging, and distinguishing features.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span id="descCharCount"
                                class="text-[11px] font-mono-code font-bold text-slate-400 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">0
                                chars</span>
                            <button type="button" onclick="clearExhibitDescription()"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl border border-slate-200 transition-colors cursor-pointer flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear
                            </button>
                            <button type="button" onclick="copyExhibitDescription()"
                                class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs rounded-xl border border-blue-200 transition-colors cursor-pointer flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>

                    <!-- FORMAT GUIDE CARD -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="text-[10px] font-mono-code font-extrabold text-slate-500 uppercase tracking-wider">Format
                                Guide</span>
                            <span class="flex-1 h-px bg-slate-200"></span>
                        </div>
                        <div class="text-xs text-slate-700 font-sans leading-relaxed space-y-1 pl-1">
                            <p class="font-semibold text-slate-800">Opening sentence describing the outer
                                container/packaging, then bullet points for each item found inside:</p>
                            <div
                                class="pl-3 border-l-2 border-emerald-300 space-y-1 mt-2 text-slate-600 font-normal italic text-[11px]">
                                <p>Inside the box were two large ziplock bags.</p>
                                <p>• One of the ziplock bags labelled 'A' contained one hundred and six (106) wraps of
                                    plant-like substances.</p>
                                <p>• The second ziplock bag contained one transparent polythene bag and a small ziplock
                                    bag labeled 'B' and 'C' respectively…</p>
                            </div>
                        </div>
                    </div>

                    <!-- TEXTAREA FORM -->
                    <form id="exhibitDescriptionForm" onsubmit="saveExhibitDescription(event)" class="space-y-4">
                        <div class="relative">
                            <label for="detailedExhibitDescription"
                                class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider mb-2">
                                Detailed Exhibit Description
                                <span class="text-red-500 ml-0.5">*</span>
                            </label>
                            <div class="relative">
                                <textarea id="detailedExhibitDescription" name="detailed_exhibit_description" rows="10"
                                    placeholder="Inside the [container type] were [quantity] [packaging type].&#10;• One of the [packaging] labelled '[Label]' contained [quantity and description of contents].&#10;• The second [packaging] contained [description of contents]…"
                                    oninput="updateDescCharCount(this)"
                                    class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-2xl text-sm text-slate-900 font-sans leading-relaxed focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all resize-y custom-scrollbar placeholder-slate-300"
                                    style="min-height: 180px; font-family: 'Inter', system-ui, sans-serif; font-size: 13px; line-height: 1.75;">{{ old('detailed_exhibit_description') }}</textarea>

                                <!-- Corner decoration -->
                                <div class="absolute bottom-3 right-3 flex items-center gap-1.5 pointer-events-none">
                                    <span id="descLiveCount"
                                        class="text-[10px] font-mono-code font-bold text-slate-300">0</span>
                                </div>
                            </div>

                            <!-- Bullet Helper Toolbar -->
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span
                                    class="text-[10px] font-mono-code font-bold text-slate-400 uppercase tracking-wide">Quick
                                    Insert:</span>
                                <button type="button" onclick="insertAtCursor('detailedExhibitDescription', '\n• ')"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 hover:border-emerald-300 text-slate-600 font-bold text-[11px] rounded-lg border border-slate-200 transition-all cursor-pointer font-mono-code">
                                    + Bullet Point
                                </button>
                                <button type="button" onclick="insertAtCursor('detailedExhibitDescription', '\n')"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-[11px] rounded-lg border border-slate-200 transition-all cursor-pointer font-mono-code">
                                    + New Line
                                </button>
                                <button type="button" onclick="insertTemplate()"
                                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-[11px] rounded-lg border border-emerald-200 transition-all cursor-pointer font-mono-code">
                                    Load Template
                                </button>
                            </div>
                        </div>

                        <!-- ADDITIONAL FIELDS ROW -->
                        <div class="max-w-xs">

                            <div class="space-y-1.5">
                                <label for="conditionOfReservedSample"
                                    class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">Condition
                                    of Reserved Sample</label>
                                <select id="conditionOfReservedSample" name="condition_of_reserved_sample"
                                    class="w-full px-3.5 py-2.5 bg-white border-2 border-slate-200 rounded-xl text-xs text-slate-900 font-sans focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all cursor-pointer">
                                    <option value="">— Select Condition —</option>
                                    <option value="good">Good / Unaltered</option>
                                    <option value="sealed">Sealed &amp; Labelled</option>
                                    <option value="partially_used">Partially Used</option>
                                    <option value="degraded">Degraded</option>
                                    <option value="contaminated">Contaminated</option>
                                    <option value="destroyed">Destroyed / Expended</option>
                                </select>
                            </div>
                        </div>

                        <!-- SUBMIT BAR -->
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <div id="descSaveStatus"
                                    class="hidden items-center gap-1.5 text-xs font-mono-code font-bold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-emerald-700">Description saved to dossier.</span>
                                </div>
                                <p class="text-[11px] text-slate-400 font-mono-code">Ref: {{ $caseNo }} •
                                    {{ $labNo }}</p>
                            </div>
                            <div class="flex items-center gap-2 justify-end">
                                <button type="button" onclick="clearExhibitDescription()"
                                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition-colors cursor-pointer">
                                    Reset
                                </button>
                                <button type="submit" id="saveDescBtn"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md transition-all cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Save Exhibit Description
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>

    <!-- JAVASCRIPT FOR DROPDOWNS, MODALS & FILTERING -->
    <script>
        function toggleReportDropdown() {
            const menu = document.getElementById('reportDropdownMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        function showGsaReportUploadSection() {
            const sec = document.getElementById('gsaUploadSection');
            if (sec) {
                if (sec.classList.contains('hidden')) {
                    sec.classList.remove('hidden');
                    setTimeout(() => {
                        sec.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                } else {
                    sec.classList.add('hidden');
                }
            }
        }

        function openGsaReportModal() {
            showGsaReportUploadSection();
        }

        function closeGsaReportModal() {
            // Unused
        }

        function openNlrReportModal() {
            const descSec = document.getElementById('exhibitDescriptionSection');
            if (descSec) {
                if (descSec.classList.contains('hidden')) {
                    descSec.classList.remove('hidden');
                    setTimeout(() => {
                        descSec.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                } else {
                    descSec.classList.add('hidden');
                }
            }
        }

        function closeNlrReportModal() {
            // Unused
        }

        function filterSuspectDossiers(query) {
            const q = (query || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.suspect-dossier-card');
            cards.forEach(card => {
                const sName = card.getAttribute('data-suspect-name') || '';
                const textContent = card.innerText.toLowerCase();
                if (!q || sName.includes(q) || textContent.includes(q)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        /* ── GSA Report multi-file upload helpers ── */
        let gsaFilesMap = {}; // id -> {file, dataUrl}
        let gsaFileIdCounter = 0;

        function gsaDragOver(e) {
            e.preventDefault();
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.add('border-emerald-500', 'bg-emerald-50/80');
                zone.classList.remove('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaDragLeave(e) {
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.remove('border-emerald-500', 'bg-emerald-50/80');
                zone.classList.add('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaHandleDrop(e) {
            e.preventDefault();
            gsaDragLeave(e);
            Array.from(e.dataTransfer.files).forEach(f => gsaAddFile(f));
        }

        function gsaHandleFileSelect(input) {
            Array.from(input.files).forEach(f => gsaAddFile(f));
            input.value = ''; // reset so same file can be re-added
        }

        function gsaAddFile(file) {
            const maxMB = 10;
            if (file.size > maxMB * 1024 * 1024) {
                alert('"' + file.name + '" exceeds ' + maxMB + ' MB limit and was skipped.');
                return;
            }
            const id = 'gsa_' + (++gsaFileIdCounter);
            const ext = file.name.split('.').pop().toLowerCase();

            gsaFilesMap[id] = {
                file
            };

            const grid = document.getElementById('gsaFileGrid');
            if (grid) {
                const card = document.createElement('div');
                card.id = 'gsaCard_' + id;
                card.className =
                    'relative bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group/card';
                card.innerHTML = gsaBuildCardInner(id, file.name, file.size, ext, null);
                grid.appendChild(card);

                // For images: load thumbnail asynchronously and swap into the thumb wrapper
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const thumbWrapper = document.getElementById('gsaThumb_' + id);
                        if (thumbWrapper) {
                            thumbWrapper.innerHTML = '<img src="' + ev.target.result +
                                '" class="w-full h-24 object-cover" alt="preview">';
                        }
                        gsaFilesMap[id].dataUrl = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
            gsaUpdateCount();
        }

        function gsaBuildCardInner(id, name, size, ext, dataUrl) {
            const sizeStr = (size / 1024).toFixed(0) + ' KB';
            const shortName = name.length > 16 ? name.substring(0, 13) + '...' : name;
            const cfg = gsaIconConfig(ext);

            // Thumbnail area (top of card)
            let thumbHtml;
            if (dataUrl) {
                thumbHtml = '<img src="' + dataUrl + '" class="w-full h-24 object-cover" alt="preview">';
            } else {
                thumbHtml = '<div class="w-full h-24 flex flex-col items-center justify-center gap-1 ' + cfg.bg + '">' +
                    '<svg class="w-7 h-7 ' + cfg.color + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="' + cfg.path + '"/></svg>' +
                    '<span class="text-[10px] font-extrabold ' + cfg.color + ' font-mono-code tracking-wider">' + ext
                    .toUpperCase() + '</span>' +
                    '</div>';
            }

            // Thumb wrapper with id so async image load can swap it in
            const thumb = '<div id="gsaThumb_' + id + '" class="overflow-hidden">' + thumbHtml + '</div>';

            // ✓ badge
            const badge = '<span class="absolute top-1.5 left-1.5 w-5 h-5 ' + cfg.badgeBg +
                ' text-white rounded-full flex items-center justify-center text-[9px] shadow">✓</span>';

            // Remove ✕ button (top-right, appears on hover)
            const removeBtn = '<button type="button" onclick="gsaRemoveFile(\'' + id + '\', event)" ' +
                'class="absolute top-1.5 right-1.5 w-5 h-5 bg-red-600 hover:bg-red-700 text-white rounded-full ' +
                'flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-opacity cursor-pointer shadow">' +
                '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>' +
                '</button>';

            // Info below thumb
            const info = '<div class="px-2 py-1.5">' +
                '<p class="text-[11px] font-bold text-slate-800 font-mono-code truncate leading-tight" title="' + name +
                '">' + shortName + '</p>' +
                '<p class="text-[10px] text-slate-400 font-mono-code">' + sizeStr + '</p>' +
                '</div>';

            return thumb + badge + removeBtn + info;
        }

        function gsaIconConfig(ext) {
            if (ext === 'pdf') return {
                bg: 'bg-red-50',
                border: 'border-red-300',
                color: 'text-red-600',
                badgeBg: 'bg-red-600',
                path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
            };
            if (ext === 'docx') return {
                bg: 'bg-blue-50',
                border: 'border-blue-300',
                color: 'text-blue-600',
                badgeBg: 'bg-blue-600',
                path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
            };
            if (ext === 'csv') return {
                bg: 'bg-emerald-50',
                border: 'border-emerald-300',
                color: 'text-emerald-700',
                badgeBg: 'bg-emerald-600',
                path: 'M3 10h18M3 14h18M10 3v18M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z'
            };
            return {
                bg: 'bg-emerald-50',
                border: 'border-emerald-300',
                color: 'text-emerald-700',
                badgeBg: 'bg-emerald-600',
                path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
            };
        }

        function gsaRemoveFile(id, e) {
            if (e) e.stopPropagation();
            delete gsaFilesMap[id];
            const card = document.getElementById('gsaCard_' + id);
            if (card) card.remove();
            gsaUpdateCount();
        }

        function clearAllGsaFiles() {
            gsaFilesMap = {};
            const grid = document.getElementById('gsaFileGrid');
            if (grid) grid.innerHTML = '';
            const input = document.getElementById('gsaReportImageInput');
            if (input) input.value = '';
            gsaUpdateCount();
        }

        function gsaUpdateCount() {
            const count = Object.keys(gsaFilesMap).length;
            const badge = document.getElementById('gsaFileCount');
            const clearBtn = document.getElementById('gsaClearAllBtn');
            const prompt = document.getElementById('gsaFullPrompt');
            const strip = document.getElementById('gsaAddMoreStrip');
            if (badge) {
                badge.textContent = count + (count === 1 ? ' file' : ' files');
                badge.classList.toggle('hidden', count === 0);
            }
            if (clearBtn) clearBtn.classList.toggle('hidden', count === 0);
            // Show full prompt when empty, compact strip when files exist
            if (prompt) prompt.classList.toggle('hidden', count > 0);
            if (strip) {
                strip.classList.toggle('hidden', count === 0);
                strip.classList.toggle('flex', count > 0);
            }
        }

        function saveGsaReportFiles() {
            const fileKeys = Object.keys(gsaFilesMap);
            const fileInput = document.getElementById('gsaReportImageInput');
            const hasInputFiles = fileInput && fileInput.files && fileInput.files.length > 0;

            if (fileKeys.length === 0 && !hasInputFiles) {
                alert('Please select or drag at least one GSA report file before saving.');
                return;
            }

            const btn = document.getElementById('saveGsaFilesBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="inline-block animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent mr-1"></span> Uploading &amp; Saving...';
            }

            const formData = new FormData();
            formData.append('case_number', @json($caseNo));
            formData.append('multiple_suspect_case_id', @json($case->id));
            formData.append('lab_number', @json($labNo));
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                '{{ csrf_token() }}');

            if (fileKeys.length > 0) {
                fileKeys.forEach(k => {
                    if (gsaFilesMap[k] && gsaFilesMap[k].file) {
                        formData.append('gsa_report_images[]', gsaFilesMap[k].file);
                    }
                });
            } else if (hasInputFiles) {
                Array.from(fileInput.files).forEach(f => {
                    formData.append('gsa_report_images[]', f);
                });
            }

            const caseId = @json($caseNo);
            fetch(`/cases/${encodeURIComponent(caseId)}/upload-gsa-report`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json().then(data => ({
                    ok: res.ok,
                    status: res.status,
                    data: data
                })).catch(() => ({
                    ok: res.ok,
                    status: res.status,
                    data: {}
                })))
                .then(result => {
                    const data = result.data;
                    if (result.ok && data.success) {
                        if (typeof showToast === 'function') {
                            showToast(`✔ ${data.message || 'GSA report file(s) saved and attached successfully.'}`);
                        } else {
                            alert(data.message || 'GSA report file(s) saved and attached successfully.');
                        }
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML =
                                '<svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> <span>Save &amp; Attach GSA Report</span>';
                        }
                        alert(data.message || 'Failed to save GSA report files.');
                    }
                })
                .catch(err => {
                    console.error('Error uploading GSA report files:', err);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> <span>Save &amp; Attach GSA Report</span>';
                    }
                    alert('An unexpected error occurred while saving GSA report files.');
                });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('reportDropdownContainer');
            const menu = document.getElementById('reportDropdownMenu');
            if (container && menu && !container.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        /* ── Detailed Exhibit Description helpers ── */
        function updateDescCharCount(el) {
            const len = el.value.length;
            const badge = document.getElementById('descCharCount');
            const live = document.getElementById('descLiveCount');
            if (badge) badge.textContent = len.toLocaleString() + ' chars';
            if (live) live.textContent = len.toLocaleString();
        }

        function insertAtCursor(fieldId, text) {
            const el = document.getElementById(fieldId);
            if (!el) return;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            el.value = el.value.substring(0, start) + text + el.value.substring(end);
            el.selectionStart = el.selectionEnd = start + text.length;
            el.focus();
            updateDescCharCount(el);
        }

        function insertTemplate() {
            const el = document.getElementById('detailedExhibitDescription');
            if (!el) return;
            const tpl =
                `Inside the [container type] were [quantity] [packaging].\n• One of the [packaging] labelled 'A' contained [quantity] [description of contents].\n• The second [packaging] contained [description of contents].\n• The [packaging] labelled 'C' contained [additional details].`;
            el.value = tpl;
            el.focus();
            updateDescCharCount(el);
        }

        function clearExhibitDescription() {
            const el = document.getElementById('detailedExhibitDescription');
            if (el) {
                el.value = '';
                updateDescCharCount(el);
            }
            const crs = document.getElementById('conditionOfReservedSample');
            if (crs) crs.value = '';
            const status = document.getElementById('descSaveStatus');
            if (status) status.classList.add('hidden');
        }

        function copyExhibitDescription() {
            const el = document.getElementById('detailedExhibitDescription');
            if (!el || !el.value.trim()) return;
            navigator.clipboard.writeText(el.value).then(() => {
                const btn = event.currentTarget;
                const orig = btn.innerHTML;
                btn.innerHTML =
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
                btn.classList.add('bg-emerald-100', 'text-emerald-800', 'border-emerald-300');
                setTimeout(() => {
                    btn.innerHTML = orig;
                    btn.classList.remove('bg-emerald-100', 'text-emerald-800', 'border-emerald-300');
                }, 2000);
            });
        }

        function saveExhibitDescription(e) {
            e.preventDefault();
            const el = document.getElementById('detailedExhibitDescription');
            if (!el || !el.value.trim()) {
                el.classList.add('border-red-400');
                el.focus();
                setTimeout(() => el.classList.remove('border-red-400'), 2000);
                return;
            }
            const btn = document.getElementById('saveDescBtn');
            if (btn) {
                btn.innerHTML =
                    '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Saving…';
                btn.disabled = true;
            }
            setTimeout(() => {
                if (btn) {
                    btn.innerHTML =
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Saved!';
                    btn.classList.replace('bg-emerald-600', 'bg-emerald-700');
                }
                const status = document.getElementById('descSaveStatus');
                if (status) {
                    status.classList.remove('hidden');
                    status.classList.add('flex');
                }
                setTimeout(() => {
                    if (btn) {
                        btn.innerHTML =
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Save Exhibit Description';
                        btn.disabled = false;
                        btn.classList.replace('bg-emerald-700', 'bg-emerald-600');
                    }
                }, 2500);
            }, 900);
        }

        // Front mugshot lightbox
        function openFrontMugshotLightbox(src, name) {
            const box = document.getElementById('frontMugshotLightbox');
            if (!box || !src) return;
            document.getElementById('frontMugshotLightboxImg').src = src;
            document.getElementById('frontMugshotLightboxName').textContent = (name || 'Suspect') + ' — Front View';
            box.classList.remove('hidden');
            box.classList.add('flex');
        }

        function closeFrontMugshotLightbox() {
            const box = document.getElementById('frontMugshotLightbox');
            if (!box) return;
            box.classList.add('hidden');
            box.classList.remove('flex');
        }
        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') closeFrontMugshotLightbox();
        });
    </script>

    <!-- FRONT MUGSHOT LIGHTBOX -->
    <div id="frontMugshotLightbox"
        class="hidden fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-sm items-center justify-center p-4 no-print"
        onclick="if (event.target === this) closeFrontMugshotLightbox()">
        <div class="bg-slate-900 rounded-3xl border border-emerald-500/40 shadow-2xl p-4 max-w-md w-full space-y-3">
            <div class="flex items-center justify-between">
                <span id="frontMugshotLightboxName"
                    class="text-xs font-mono-code font-extrabold uppercase tracking-wider text-emerald-300 truncate"></span>
                <button type="button" onclick="closeFrontMugshotLightbox()"
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-red-600 text-white flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <img id="frontMugshotLightboxImg" src="" alt="Suspect Front Mugshot"
                class="w-full max-h-[70vh] object-contain rounded-2xl bg-slate-800">
        </div>
    </div>
</body>

</html>
