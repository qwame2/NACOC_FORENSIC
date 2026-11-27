@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
    $allCases = \App\Models\ForensicCase::allCases();
    $cases = $allCases->filter(fn($c) => $c->isDigitalCase())->values();
    $savedReports = \App\Models\DpfReport::latest()->get()->unique('case_number')->values();
    $allCasesByNum = $allCases->keyBy('case_number');

    $resolveSuspectInfo = function($caseItem, $rep = null) use ($allCasesByNum) {
        if (!$caseItem && $rep && !empty($rep->case_number)) {
            $caseItem = $allCasesByNum->get($rep->case_number);
        }

        $name = null;
        $mug = null;
        $alias = null;
        $coCount = 0;

        if ($caseItem) {
            $dData = is_array($caseItem->docket_data) ? $caseItem->docket_data : (json_decode($caseItem->docket_data ?? '[]', true) ?: []);
            $name = $caseItem->suspect_name ?? ($dData['suspects'][0]['suspect_name'] ?? null);
            $alias = $caseItem->suspect_alias ?? ($dData['suspects'][0]['suspect_alias'] ?? null);
            $mug = $caseItem->mugshot_front ?? ($dData['suspects'][0]['mugshot_front'] ?? ($dData['mugshots']['front'] ?? null));

            if (!empty($dData['suspects']) && is_array($dData['suspects'])) {
                $totalSuspects = count($dData['suspects']);
                $coCount = max(0, $totalSuspects - 1);
            } elseif (isset($caseItem->underlyingRecord) && $caseItem->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
                $totalSuspects = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $caseItem->underlyingRecord->id)->count();
                $coCount = max(0, $totalSuspects - 1);
            }
        }

        if (empty($name) && $rep) {
            if ($rep->suspect) {
                $name = $rep->suspect->suspect_name;
                $alias = $rep->suspect->suspect_alias;
                $mug = $rep->suspect->mugshot_front;
            } elseif ($rep->singleSuspectCase) {
                $name = $rep->singleSuspectCase->suspect_name;
                $alias = $rep->singleSuspectCase->suspect_alias;
                $mug = $rep->singleSuspectCase->mugshot_front;
            } elseif ($rep->multipleSuspectCase) {
                $firstS = $rep->multipleSuspectCase->suspects()->first();
                if ($firstS) {
                    $name = $firstS->suspect_name;
                    $alias = $firstS->suspect_alias;
                    $mug = $firstS->mugshot_front;
                }
            }
        }

        if (empty($name)) {
            $name = 'Unknown Suspect';
        }

        if (!empty($mug)) {
            $mug = trim((string)$mug);
            if (!str_starts_with($mug, 'data:image') && !str_starts_with($mug, 'http://') && !str_starts_with($mug, 'https://') && !str_starts_with($mug, '/')) {
                $mug = asset($mug);
            }
        }

        return [
            'name'     => $name,
            'alias'    => ($alias && strtoupper($alias) !== 'N/A') ? $alias : null,
            'mug'      => !empty($mug) ? $mug : null,
            'co_count' => $coCount,
        ];
    };

    // WHY: The Case Reference dropdown is scoped to the Digital & Physical Forensics Unit.
    // It must only list suspects whose linked exhibit category is the electronic-devices
    // category — not entire cases and not suspects tied to other exhibit types.
    //
    // In a multi-suspect case like REF-001 where Suspect A has a drug exhibit and
    // Suspect B has an electronic exhibit, ONLY Suspect B should appear here.
    // Listing the full case would let an examiner attach digital work to the wrong
    // suspect, breaking the chain of custody and audit trail.
    //
    // The category value is read from config('forensic.exhibit_categories.electronic_devices')
    // and is never hardcoded — see config/forensic.php for the canonical list.

    // WHY: The exhibit category is read from config, never hardcoded. If the wording
    // of 'Electronic Devices' is ever changed (renamed, translated, or variant added),
    // only config/forensic.php needs to be updated — not this query, not the view,
    // not any other consumer. A hardcoded string would silently break every filter
    // that depends on it.
    $electronicCategory = config('forensic.exhibit_categories.electronic_devices');
    $digitalUnitName = config('forensics.units.DPFU.name') ?? (\App\Enums\UnitName::DIGITAL_PHYSICAL_FORENSICS->value ?? 'Digital & Physical Forensics');
    $exhibitCategoryColumn = 'exhibit_category';

    $isMatchingCategory = function($cat) use ($electronicCategory) {
        if (empty($cat) || empty($electronicCategory)) return false;
        $cTrim = trim((string)$cat);
        $eTrim = trim((string)$electronicCategory);
        return (strcasecmp($cTrim, $eTrim) === 0) || (stripos($cTrim, $eTrim) !== false);
    };

    $dropdownCandidates = [];
    $caseMapData = [];

    foreach ($allCases as $cItem) {
        $cNo = $cItem->case_number ?? ($cItem['case_number'] ?? '');
        if (empty($cNo)) continue;

        $savedForCase = $savedReports->firstWhere('case_number', $cNo);
        $dData = is_array($cItem->docket_data) ? $cItem->docket_data : (json_decode($cItem->docket_data ?? '[]', true) ?: []);

        $isMulti = (isset($cItem->underlyingRecord) && $cItem->underlyingRecord instanceof \App\Models\MultipleSuspectCase)
            || (method_exists($cItem, 'isMultipleSuspect') && $cItem->isMultipleSuspect())
            || !empty($cItem->is_multiple);

        $multiC = $isMulti
            ? ($cItem->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $cItem->underlyingRecord : \App\Models\MultipleSuspectCase::where('case_number', $cNo)->first())
            : \App\Models\MultipleSuspectCase::where('case_number', $cNo)->first();

        $singleC = !$isMulti
            ? ($cItem->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $cItem->underlyingRecord : \App\Models\SingleSuspectCase::where('case_number', $cNo)->first())
            : \App\Models\SingleSuspectCase::where('case_number', $cNo)->first();

        if ($multiC) {
            $allCaseExhibits = $multiC->exhibits()->get();
            if ($allCaseExhibits->isEmpty() && isset($cItem->underlyingRecord) && isset($cItem->underlyingRecord->exhibits) && count($cItem->underlyingRecord->exhibits) > 0) {
                $allCaseExhibits = collect($cItem->underlyingRecord->exhibits);
            }
            if ($allCaseExhibits->isEmpty() && method_exists($cItem, 'getRelation') && $cItem->relationLoaded('exhibits')) {
                $allCaseExhibits = collect($cItem->getRelation('exhibits') ?: []);
            }

            $suspects = $multiC->suspects()->orderBy('suspect_number')->get();
            if ($suspects->isEmpty() && !empty($dData['suspects']) && is_array($dData['suspects'])) {
                $suspects = collect($dData['suspects'])->map(function($s, $idx) {
                    return (object)[
                        'id'            => $s['id'] ?? null,
                        'suspect_name'  => $s['suspect_name'] ?? ('Suspect ' . ($idx + 1)),
                        'suspect_alias' => $s['suspect_alias'] ?? null,
                        'mugshot_front' => $s['mugshot_front'] ?? null,
                        'role'          => $s['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Co-Suspect'),
                        'raid_location' => $s['raid_location'] ?? null,
                        'arrest_date'   => $s['arrest_date'] ?? null,
                    ];
                });
            }

            foreach ($suspects as $s) {
                $sExhibits = $allCaseExhibits->filter(function($ex) use ($s, $suspects) {
                    if (!empty($ex->suspect_id) && !empty($s->id)) {
                        return (string)$ex->suspect_id === (string)$s->id;
                    }
                    if (!empty($ex->associated_person)) {
                        if (str_contains(strtolower($ex->associated_person), 'all suspect')) {
                            return true;
                        }
                        return strcasecmp(trim($ex->associated_person), trim($s->suspect_name)) === 0;
                    }
                    if (empty($ex->suspect_id) && empty($ex->associated_person)) {
                        return ($s->role ?? '') === 'Primary Suspect' || count($suspects) === 1;
                    }
                    return false;
                });

                $sJsonSeizures = collect();
                if (!empty($dData['seizures']) && is_array($dData['seizures'])) {
                    foreach ($dData['seizures'] as $sz) {
                        $belongs = false;
                        if (!empty($sz['suspect_id']) && !empty($s->id) && (string)$sz['suspect_id'] === (string)$s->id) {
                            $belongs = true;
                        } elseif (!empty($sz['associated_person'])) {
                            if (str_contains(strtolower($sz['associated_person']), 'all suspect')) {
                                $belongs = true;
                            } elseif (strcasecmp(trim($sz['associated_person']), trim($s->suspect_name)) === 0) {
                                $belongs = true;
                            }
                        } elseif (empty($sz['suspect_id']) && empty($sz['associated_person']) && (($s->role ?? '') === 'Primary Suspect' || count($suspects) === 1)) {
                            $belongs = true;
                        }
                        if ($belongs) {
                            $sJsonSeizures->push((object)[
                                $exhibitCategoryColumn => $sz['category'] ?? ($sz[$exhibitCategoryColumn] ?? null),
                                'dev_model'            => $sz['dev_model'] ?? ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? null))),
                                'seizure_quantity'     => $sz['seizure_quantity'] ?? null,
                                'seizure_location'     => $sz['seizure_location'] ?? null,
                                'seizure_date'         => $sz['seizure_date'] ?? null,
                            ]);
                        }
                    }
                }

                $matchingEx = $sExhibits->filter(fn($ex) => $isMatchingCategory($ex->{$exhibitCategoryColumn} ?? ($ex->category ?? null)));
                $matchingJson = $sJsonSeizures->filter(fn($sz) => $isMatchingCategory($sz->{$exhibitCategoryColumn} ?? null));

                if ($matchingEx->isNotEmpty() || $matchingJson->isNotEmpty()) {
                    $devList = [];
                    $qtyList = [];
                    foreach ($matchingEx as $ex) {
                        $devName = !empty($ex->dev_model) ? $ex->dev_model : ($ex->commodity_name ?: ($ex->exhibit_type ?: ($ex->{$exhibitCategoryColumn} ?: '')));
                        $qty = !empty($ex->seizure_quantity) ? trim((string)$ex->seizure_quantity) : '';
                        if (!empty($qty)) $qtyList[] = $qty;
                        if (!empty($qty) && !empty($devName)) {
                            $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
                        } else {
                            $itemStr = $devName ?: $qty;
                        }
                        $itemStr = trim((string)$itemStr);
                        if (!empty($itemStr)) $devList[] = $itemStr;
                    }
                    if (empty($devList)) {
                        foreach ($matchingJson as $sz) {
                            $devName = !empty($sz->dev_model) ? $sz->dev_model : ($sz->commodity_name ?? ($sz->seizure_name ?? ($sz->name ?? ($sz->{$exhibitCategoryColumn} ?? ''))));
                            $qty = !empty($sz->seizure_quantity) ? trim((string)$sz->seizure_quantity) : '';
                            if (!empty($qty)) $qtyList[] = $qty;
                            if (!empty($qty) && !empty($devName)) {
                                $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
                            } else {
                                $itemStr = $devName ?: $qty;
                            }
                            $itemStr = trim((string)$itemStr);
                            if (!empty($itemStr)) $devList[] = $itemStr;
                        }
                    }

                    $sName = $s->suspect_name ?? 'Unknown Suspect';
                    $sAlias = $s->suspect_alias ?? null;
                    $sMug = $s->mugshot_front ?? null;
                    $sRole = $s->role ?? null;
                    $suspectId = $s->id ?? null;

                    $sAliasDisplay = (!empty($sAlias) && strtoupper($sAlias) !== 'N/A') ? ' (' . $sAlias . ')' : '';
                    if (!empty($sRole) && !str_starts_with($sName, $sRole)) {
                        $suspectLabel = $sRole . ' (' . $sName . $sAliasDisplay . ')';
                    } else {
                        $suspectLabel = $sName . $sAliasDisplay;
                    }

                    $optValue = $suspectId ? ($cNo . '|' . $suspectId) : $cNo;
                    $optLabel = $cNo . ' — ' . $suspectLabel;

                    $dropdownCandidates[] = [
                        'value'         => $optValue,
                        'label'         => $optLabel,
                        'case_number'   => $cNo,
                        'suspect_id'    => $suspectId,
                        'suspect_name'  => $sName,
                        'suspect_alias' => $sAlias,
                        'suspect_mug'   => $sMug,
                        'role'          => $sRole,
                        'devices'       => !empty($devList) ? implode(', ', array_unique($devList)) : ($electronicCategory . ' / Mobile Exhibits'),
                        'qty'           => !empty($qtyList) ? implode(', ', array_unique($qtyList)) : '1',
                        'location'      => $s->raid_location ?? ($multiC->raid_location ?? null),
                        'date'          => $s->arrest_date ?? ($multiC->incident_date ?? null),
                        'case_item'     => $cItem,
                        'multi_case'    => $multiC,
                        'single_case'   => null,
                        'is_multi'      => true,
                    ];
                }
            }
        } else {
            $singleExhibits = $singleC ? $singleC->exhibits()->get() : collect();
            if ($singleExhibits->isEmpty() && isset($cItem->underlyingRecord) && isset($cItem->underlyingRecord->exhibits) && count($cItem->underlyingRecord->exhibits) > 0) {
                $singleExhibits = collect($cItem->underlyingRecord->exhibits);
            }
            if ($singleExhibits->isEmpty() && method_exists($cItem, 'getRelation') && $cItem->relationLoaded('exhibits')) {
                $singleExhibits = collect($cItem->getRelation('exhibits') ?: []);
            }

            $matchingSingleEx = $singleExhibits->filter(fn($ex) => $isMatchingCategory($ex->{$exhibitCategoryColumn} ?? ($ex->category ?? null)));
            $matchingSingleJson = collect($dData['seizures'] ?? [])->filter(fn($sz) => $isMatchingCategory($sz['category'] ?? ($sz[$exhibitCategoryColumn] ?? null)));
            $isCaseCatMatch = $isMatchingCategory($cItem->{$exhibitCategoryColumn} ?? null);

            if ($matchingSingleEx->isNotEmpty() || $matchingSingleJson->isNotEmpty() || $isCaseCatMatch) {
                $devList = [];
                $qtyList = [];
                foreach ($matchingSingleEx as $ex) {
                    $devName = !empty($ex->dev_model) ? $ex->dev_model : ($ex->commodity_name ?: ($ex->exhibit_type ?: ($ex->{$exhibitCategoryColumn} ?: '')));
                    $qty = !empty($ex->seizure_quantity) ? trim((string)$ex->seizure_quantity) : '';
                    if (!empty($qty)) $qtyList[] = $qty;
                    if (!empty($qty) && !empty($devName)) {
                        $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
                    } else {
                        $itemStr = $devName ?: $qty;
                    }
                    $itemStr = trim((string)$itemStr);
                    if (!empty($itemStr)) $devList[] = $itemStr;
                }
                if (empty($devList)) {
                    foreach ($matchingSingleJson as $sz) {
                        $devName = !empty($sz['dev_model']) ? $sz['dev_model'] : ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? ($sz[$exhibitCategoryColumn] ?? ($sz['category'] ?? '')))));
                        $qty = !empty($sz['seizure_quantity']) ? trim((string)$sz['seizure_quantity']) : '';
                        if (!empty($qty)) $qtyList[] = $qty;
                        if (!empty($qty) && !empty($devName)) {
                            $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
                        } else {
                            $itemStr = $devName ?: $qty;
                        }
                        $itemStr = trim((string)$itemStr);
                        if (!empty($itemStr)) $devList[] = $itemStr;
                    }
                }
                if (empty($devList)) {
                    $mainDev = $cItem->dev_model ?? ($cItem->seizure_name ?? null);
                    if (!empty($mainDev)) {
                        $qty = !empty($cItem->seizure_quantity) ? trim((string)$cItem->seizure_quantity) : '';
                        if (!empty($qty)) $qtyList[] = $qty;
                        $devList[] = !empty($qty) && !preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $mainDev) ? $qty . ' ' . $mainDev : $mainDev;
                    }
                }

                $linkedSuspect = ($singleC && method_exists($singleC, 'suspects')) ? $singleC->suspects()->first() : null;
                $sName = $linkedSuspect?->suspect_name ?? ($singleC?->suspect_name ?? ($cItem->suspect_name ?? ($dData['suspects'][0]['suspect_name'] ?? 'Unknown Suspect')));
                $sAlias = $linkedSuspect?->suspect_alias ?? ($singleC?->suspect_alias ?? ($cItem->suspect_alias ?? ($dData['suspects'][0]['suspect_alias'] ?? null)));
                $sMug = $linkedSuspect?->mugshot_front ?? ($singleC?->mugshot_front ?? ($cItem->mugshot_front ?? ($dData['suspects'][0]['mugshot_front'] ?? null)));
                $suspectId = $linkedSuspect?->id ?? null;

                $sAliasDisplay = (!empty($sAlias) && strtoupper($sAlias) !== 'N/A') ? ' (' . $sAlias . ')' : '';
                $optValue = $cNo;
                $optLabel = $cNo . ' — ' . $sName . $sAliasDisplay;

                $dropdownCandidates[] = [
                    'value'         => $optValue,
                    'label'         => $optLabel,
                    'case_number'   => $cNo,
                    'suspect_id'    => $suspectId,
                    'suspect_name'  => $sName,
                    'suspect_alias' => $sAlias,
                    'suspect_mug'   => $sMug,
                    'role'          => null,
                    'devices'       => !empty($devList) ? implode(', ', array_unique($devList)) : ($electronicCategory . ' / Mobile Exhibits'),
                    'qty'           => !empty($qtyList) ? implode(', ', array_unique($qtyList)) : '1',
                    'location'      => $singleC?->raid_location ?? ($cItem->raid_location ?? null),
                    'date'          => $singleC?->arrest_date ?? ($cItem->arrest_date ?? null),
                    'case_item'     => $cItem,
                    'multi_case'    => null,
                    'single_case'   => $singleC,
                    'is_multi'      => false,
                ];
            }
        }
    }

    $dropdownOptions = collect($dropdownCandidates)->unique('value')->values()->all();

    // WHY: The Digital Forensic & Investigation Directory table must only display
    // cases and investigation dossiers that relate to electronic devices and digital forensics.
    // In multi-suspect mixed cases (e.g., Suspect A with drugs, Suspect B with mobile phones),
    // the directory table must display Suspect B (the electronic suspect) rather than Suspect A,
    // ensuring the digital examiner is reviewing the actual subject of digital forensics.
    // Non-electronic cases (pure narcotics, weapons, vehicles) must be completely excluded.
    //
    // The category is dynamically resolved via config('forensic.exhibit_categories.electronic_devices')
    // to prevent brittle hardcoded category strings.
    $candidatesByCase = collect($dropdownCandidates)->groupBy('case_number');

    $isElectronicCase = function($cItem, $cNo = null, $rep = null) use ($candidatesByCase, $isMatchingCategory, $allCasesByNum) {
        $caseNo = $cNo ?? ($cItem->case_number ?? ($cItem['case_number'] ?? ($rep->case_number ?? '')));
        if (empty($caseNo)) return false;

        // 1. If candidate was identified with electronic exhibits for this case number
        if ($candidatesByCase->has($caseNo) && $candidatesByCase->get($caseNo)->isNotEmpty()) {
            return true;
        }

        // 2. Check saved report directly
        if ($rep) {
            if ($isMatchingCategory($rep->devices_retrieved ?? null)) return true;
            if ($isMatchingCategory($rep->report_data['exhibit_category'] ?? null)) return true;
            if ($isMatchingCategory($rep->report_data['category'] ?? null)) return true;
        }

        // 3. Check case explicit exhibit category only (strict scoping without loose keyword or unit name guessing)
        $caseObj = $cItem ?? $allCasesByNum->get($caseNo);
        if ($caseObj) {
            $cat = $caseObj->exhibit_category ?? ($caseObj->underlyingRecord->exhibit_category ?? null);
            if ($isMatchingCategory($cat)) return true;
        }

        return false;
    };

    $directorySavedReports = $savedReports->filter(fn($rep) => $isElectronicCase($allCasesByNum->get($rep->case_number), $rep->case_number, $rep))->values();
    $directoryCases = $allCases->filter(fn($cItem) => $isElectronicCase($cItem))->values();

    foreach ($dropdownOptions as $cand) {
        $candVal = $cand['value'];
        $cNo = $cand['case_number'];
        $cItem = $cand['case_item'];
        $savedForCase = null;
        if (!empty($cand['suspect_id'])) {
            $savedForCase = \App\Models\DpfReport::where('case_number', $cNo)
                ->where('suspect_id', $cand['suspect_id'])
                ->latest('id')
                ->first();
        }
        if (!$savedForCase) {
            $savedForCase = $savedReports->firstWhere('case_number', $cNo);
        }

        $loc = $cand['location'];
        if ($savedForCase && !empty(trim((string)$savedForCase->location_post))) {
            $loc = $savedForCase->location_post;
        }
        if (empty($loc)) {
            $loc = $cItem->raid_location ?? ($cItem->seizure_location ?? 'Kotoka International Airport (Cargo Wing)');
        }

        $devicesStr = $cand['devices'];
        if ($savedForCase && !empty(trim((string)$savedForCase->devices_retrieved))) {
            $devicesStr = $savedForCase->devices_retrieved;
        }

        $qtyStr = $cand['qty'];
        if ($savedForCase && !empty(trim((string)$savedForCase->quantity))) {
            $qtyStr = $savedForCase->quantity;
        }

        $rawDate = $cand['date'] ?? ($cItem->arrest_date ?? ($cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d')));
        if ($savedForCase && !empty($savedForCase->investigation_date)) {
            $arrestDateVal = is_object($savedForCase->investigation_date) ? $savedForCase->investigation_date->format('Y-m-d') : date('Y-m-d', strtotime((string)$savedForCase->investigation_date));
        } else {
            $parsedDate = date('Y-m-d', strtotime((string)$rawDate));
            $arrestDateVal = ($parsedDate && $parsedDate !== '1970-01-01') ? $parsedDate : date('Y-m-d');
        }

        $sName = $cand['suspect_name'];
        $dData = is_array($cItem->docket_data) ? $cItem->docket_data : (json_decode($cItem->docket_data ?? '[]', true) ?: []);
        $suspectOp = $cItem->operation_code_name 
            ?? ($dData['operation_code_name'] 
            ?? ($dData['suspects'][0]['operation_code_name'] 
            ?? ($cItem->operation_code ?? 'intelligence-led')));
        $suspectOpType = !str_contains(strtolower($suspectOp), 'operation') ? strtolower($suspectOp) . ' operation' : strtolower($suspectOp);
        $art = preg_match('/^[aeiou]/i', $suspectOpType) ? 'an' : 'a';
        $opLoc = (!empty($loc) && $loc !== 'Kotoka International Airport (Cargo Wing)') ? $loc : 'Agbogbloshie';
        $generatedNarrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at {$opLoc} in the Greater Accra Region to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$sName} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to NACOC Headquarters for digital forensic examination and further investigation.";
        $descStr = ($savedForCase && !empty(trim((string)$savedForCase->brief_case_description))) ? $savedForCase->brief_case_description : $generatedNarrative;

        $mug = $cand['suspect_mug'];
        if (!empty($mug)) {
            $mug = trim((string)$mug);
            if (!str_starts_with($mug, 'data:image') && !str_starts_with($mug, 'http://') && !str_starts_with($mug, 'https://') && !str_starts_with($mug, '/')) {
                $mug = asset($mug);
            }
        }

        $isCourtGranted = $savedForCase && strtoupper(trim($savedForCase->court_order_granted ?? 'NO')) === 'YES';

        $rawExtMsg = $savedForCase ? ($savedForCase->report_data['extraction_analysis_notes'] ?? ($savedForCase->extraction_analysis_message ?? '')) : '';
        $rawExtStatus = $savedForCase ? ($savedForCase->report_data['extraction_analysis_status'] ?? '') : '';
        $cleanExtMsg = trim((string)$rawExtMsg);
        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)(\s*[-–—:]\s*\1)?$/i', $cleanExtMsg)) {
            if (empty($rawExtStatus)) {
                $rawExtStatus = strtoupper(preg_replace('/[^A-Za-z ]/', '', explode('-', $cleanExtMsg)[0]));
            }
            $cleanExtMsg = '';
        } elseif (!empty($rawExtStatus)) {
            $cleanExtMsg = trim(preg_replace('/^' . preg_quote($rawExtStatus, '/') . '\s*[-–—:]\s*/i', '', $cleanExtMsg));
            if (strtoupper($cleanExtMsg) === strtoupper($rawExtStatus) || in_array(strtoupper($cleanExtNotes ?? $cleanExtMsg), ['N/A', 'NONE', 'NO', 'YES'])) {
                $cleanExtMsg = '';
            }
        }

        $rawRwMsg = $savedForCase ? ($savedForCase->report_data['report_witness_statement_notes'] ?? ($savedForCase->report_witness_statement_message ?? '')) : '';
        $rawRwStatus = $savedForCase ? ($savedForCase->report_data['report_witness_statement_status'] ?? '') : '';
        $cleanRwMsg = trim((string)$rawRwMsg);
        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)(\s*[-–—:]\s*\1)?$/i', $cleanRwMsg)) {
            if (empty($rawRwStatus)) {
                $rawRwStatus = strtoupper(preg_replace('/[^A-Za-z ]/', '', explode('-', $cleanRwMsg)[0]));
            }
            $cleanRwMsg = '';
        } elseif (!empty($rawRwStatus)) {
            $cleanRwMsg = trim(preg_replace('/^' . preg_quote($rawRwStatus, '/') . '\s*[-–—:]\s*/i', '', $cleanRwMsg));
            if (strtoupper($cleanRwMsg) === strtoupper($rawRwStatus) || in_array(strtoupper($cleanRwMsg), ['N/A', 'NONE', 'NO', 'YES'])) {
                $cleanRwMsg = '';
            }
        }

        $rawOngExtMsg = $savedForCase ? ($savedForCase->report_data['ongoing_extraction_analysis_notes'] ?? '') : '';
        $rawOngExtStatus = $savedForCase ? ($savedForCase->report_data['ongoing_extraction_analysis_status'] ?? '') : '';

        $coFilesFormatted = [];
        if ($savedForCase && !empty($savedForCase->court_order_files)) {
            foreach ($savedForCase->court_order_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_court_order_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_court_order_/i', '', $cleanName);
                    $cleanName = preg_replace('/^court_order_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $coFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $rwFilesFormatted = [];
        if ($savedForCase && !empty($savedForCase->report_witness_statement_files)) {
            foreach ($savedForCase->report_witness_statement_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_report_witness_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_report_witness_/i', '', $cleanName);
                    $cleanName = preg_replace('/^report_witness_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $rwFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $entry = [
            'location'                  => $loc,
            'devices'                   => $devicesStr,
            'qty'                       => $qtyStr,
            'description'               => $descStr,
            'date'                      => $arrestDateVal,
            'court_order_requested'     => $savedForCase ? $savedForCase->court_order_requested : 'NO',
            'court_order_granted'       => $savedForCase ? $savedForCase->court_order_granted : 'NO',
            'court_order_files'         => $coFilesFormatted,
            'extraction_status'         => $rawExtStatus ?: (!$isCourtGranted && $savedForCase && in_array(strtoupper($savedForCase->extraction_analysis_conducted ?? ''), ['YES', 'NO', 'IN PROGRESS', 'PENDING']) ? strtoupper($savedForCase->extraction_analysis_conducted) : 'NO'),
            'extraction_notes'          => !$isCourtGranted ? $cleanExtMsg : '',
            'ongoing_extraction_status' => $rawOngExtStatus ?: ($isCourtGranted && $savedForCase && in_array(strtoupper($savedForCase->extraction_analysis_conducted ?? ''), ['YES', 'NO', 'IN PROGRESS', 'PENDING']) ? strtoupper($savedForCase->extraction_analysis_conducted) : 'NO'),
            'ongoing_extraction_notes'  => $rawOngExtMsg ?: ($isCourtGranted ? $cleanExtMsg : ''),
            'report_witness_status'     => $rawRwStatus ?: ($savedForCase && in_array(strtoupper($savedForCase->report_witness_statement_sent ?? ''), ['YES', 'NO', 'PENDING']) ? strtoupper($savedForCase->report_witness_statement_sent) : 'NO'),
            'report_witness_notes'      => $cleanRwMsg,
            'report_witness_files'      => $rwFilesFormatted,
            'intel_shared'              => $savedForCase ? $savedForCase->intel_shared : 'NO',
            'remark'                    => $savedForCase ? $savedForCase->remark : '',
            'suspect_name'              => $sName,
            'suspect_alias'             => $cand['suspect_alias'],
            'suspect_mug'               => $mug,
            'co_count'                  => $cand['is_multi'] ? max(0, count($cand['multi_case']->suspects ?? []) - 1) : 0,
        ];

        $caseMapData[$candVal] = $entry;
        if (!isset($caseMapData[$cNo])) {
            $caseMapData[$cNo] = $entry;
        }
    }

    // Fallback: Populate caseMapData for remaining digital cases
    foreach ($cases as $cItem) {
        $cNo = $cItem->case_number ?? ($cItem['case_number'] ?? '');
        if (empty($cNo) || isset($caseMapData[$cNo])) continue;

        $savedForCase = $savedReports->firstWhere('case_number', $cNo);
        $dData = is_array($cItem->docket_data) ? $cItem->docket_data : (json_decode($cItem->docket_data ?? '[]', true) ?: []);

        $singleC = \App\Models\SingleSuspectCase::where('case_number', $cNo)->first();
        $multiC = \App\Models\MultipleSuspectCase::where('case_number', $cNo)->first();

        $caseExhibits = collect();
        if (isset($cItem->underlyingRecord) && isset($cItem->underlyingRecord->exhibits) && count($cItem->underlyingRecord->exhibits) > 0) {
            $caseExhibits = $cItem->underlyingRecord->exhibits;
        }
        if ($caseExhibits->isEmpty() && method_exists($cItem, 'getRelation') && $cItem->relationLoaded('exhibits')) {
            $caseExhibits = $cItem->getRelation('exhibits') ?: collect();
        }
        if ($caseExhibits->isEmpty()) {
            if ($singleC) {
                $caseExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $singleC->id)->get();
            }
            if ($multiC) {
                if ($caseExhibits->isEmpty()) {
                    $caseExhibits = \App\Models\Exhibit::where('multiple_suspect_case_id', $multiC->id)->get();
                }
                if ($caseExhibits->isEmpty()) {
                    $sIds = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiC->id)->pluck('id');
                    if ($sIds->isNotEmpty()) {
                        $caseExhibits = \App\Models\Exhibit::whereIn('suspect_id', $sIds)->get();
                    }
                }
            }
        }

        $exList = [];
        $qtyList = [];
        foreach ($caseExhibits as $ex) {
            $devName = !empty($ex->dev_model) ? $ex->dev_model : ($ex->commodity_name ?: ($ex->exhibit_type ?: ($ex->{$exhibitCategoryColumn} ?: '')));
            $qty = !empty($ex->seizure_quantity) ? trim((string)$ex->seizure_quantity) : '';
            if (!empty($qty)) $qtyList[] = $qty;
            if (!empty($qty) && !empty($devName)) {
                $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
            } else {
                $itemStr = $devName ?: $qty;
            }
            $itemStr = trim((string)$itemStr);
            if (!empty($itemStr)) $exList[] = $itemStr;
        }

        if (empty($exList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
            foreach ($dData['seizures'] as $sz) {
                $devName = !empty($sz['dev_model']) ? $sz['dev_model'] : ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? '')));
                $qty = !empty($sz['seizure_quantity']) ? trim((string)$sz['seizure_quantity']) : '';
                if (!empty($qty)) $qtyList[] = $qty;
                if (!empty($qty) && !empty($devName)) {
                    $itemStr = preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName) ? $devName : ($qty . ' ' . $devName);
                } else {
                    $itemStr = $devName ?: $qty;
                }
                $itemStr = trim((string)$itemStr);
                if (!empty($itemStr)) $exList[] = $itemStr;
            }
        }

        $devicesStr = !empty($exList) ? implode(', ', array_unique($exList)) : ($electronicCategory . ' / Mobile Exhibits');
        if ($savedForCase && !empty(trim((string)$savedForCase->devices_retrieved))) {
            $devicesStr = $savedForCase->devices_retrieved;
        }

        $qtyStr = !empty($qtyList) ? implode(', ', array_unique($qtyList)) : '1';
        if ($savedForCase && !empty(trim((string)$savedForCase->quantity))) {
            $qtyStr = $savedForCase->quantity;
        }

        $loc = null;
        if ($savedForCase && !empty(trim((string)$savedForCase->location_post))) {
            $loc = $savedForCase->location_post;
        } else {
            $loc = $cItem->raid_location 
                ?? ($cItem->seizure_location 
                ?? ($dData['raid_location'] 
                ?? ($dData['seizure_location'] 
                ?? ($dData['seizures'][0]['seizure_location'] 
                ?? ($cItem->location ?? null)))));

            if (empty($loc) && $singleC && !empty($singleC->raid_location)) {
                $loc = $singleC->raid_location;
            }
            if (empty($loc) && $multiC && !empty($multiC->raid_location)) {
                $loc = $multiC->raid_location;
            }
            if (empty($loc)) {
                $loc = 'Kotoka International Airport (Cargo Wing)';
            }
        }

        $sInfo = $resolveSuspectInfo($cItem, $savedForCase);

        $suspectOp = $cItem->operation_code_name 
            ?? ($dData['operation_code_name'] 
            ?? ($dData['suspects'][0]['operation_code_name'] 
            ?? ($cItem->operation_code ?? 'intelligence-led')));
        $suspectOpType = !str_contains(strtolower($suspectOp), 'operation') ? strtolower($suspectOp) . ' operation' : strtolower($suspectOp);
        $art = preg_match('/^[aeiou]/i', $suspectOpType) ? 'an' : 'a';
        $sName = $sInfo['name'];
        $opLoc = (!empty($loc) && $loc !== 'Kotoka International Airport (Cargo Wing)') ? $loc : 'Agbogbloshie';
        $generatedNarrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at {$opLoc} in the Greater Accra Region to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$sName} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to NACOC Headquarters for digital forensic examination and further investigation.";
        $descStr = ($savedForCase && !empty(trim((string)$savedForCase->brief_case_description))) ? $savedForCase->brief_case_description : $generatedNarrative;

        $coFilesFormatted = [];
        if ($savedForCase && !empty($savedForCase->court_order_files)) {
            foreach ($savedForCase->court_order_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_court_order_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_court_order_/i', '', $cleanName);
                    $cleanName = preg_replace('/^court_order_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $coFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $rwFilesFormatted = [];
        if ($savedForCase && !empty($savedForCase->report_witness_statement_files)) {
            foreach ($savedForCase->report_witness_statement_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_report_witness_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_report_witness_/i', '', $cleanName);
                    $cleanName = preg_replace('/^report_witness_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $rwFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $caseMapData[$cNo] = [
            'location'                  => $loc,
            'devices'                   => $devicesStr,
            'qty'                       => $qtyStr,
            'description'               => $descStr,
            'date'                      => $cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d'),
            'court_order_requested'     => $savedForCase ? $savedForCase->court_order_requested : 'NO',
            'court_order_granted'       => $savedForCase ? $savedForCase->court_order_granted : 'NO',
            'court_order_files'         => $coFilesFormatted,
            'extraction_status'         => $savedForCase ? ($savedForCase->extraction_analysis_conducted ?? 'NO') : 'NO',
            'extraction_notes'          => '',
            'ongoing_extraction_status' => 'NO',
            'ongoing_extraction_notes'  => '',
            'report_witness_status'     => $savedForCase ? ($savedForCase->report_witness_statement_sent ?? 'NO') : 'NO',
            'report_witness_notes'      => '',
            'report_witness_files'      => $rwFilesFormatted,
            'intel_shared'              => $savedForCase ? $savedForCase->intel_shared : 'NO',
            'remark'                    => $savedForCase ? $savedForCase->remark : '',
            'suspect_name'              => $sInfo['name'],
            'suspect_alias'             => $sInfo['alias'],
            'suspect_mug'               => $sInfo['mug'],
            'co_count'                  => $sInfo['co_count'],
        ];
    }

    foreach ($directorySavedReports as $repItem) {
        $rCaseNo = $repItem->case_number ?? '';
        if (empty($rCaseNo) || isset($caseMapData[$rCaseNo])) continue;
        $matchedCase = $allCasesByNum->get($rCaseNo);
        $sInfo = $resolveSuspectInfo($matchedCase, $repItem);

        $coFilesFormatted = [];
        if (!empty($repItem->court_order_files)) {
            foreach ($repItem->court_order_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_court_order_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_court_order_/i', '', $cleanName);
                    $cleanName = preg_replace('/^court_order_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $coFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $rwFilesFormatted = [];
        if (!empty($repItem->report_witness_statement_files)) {
            foreach ($repItem->report_witness_statement_files as $fPath) {
                if (!empty($fPath)) {
                    $rawName = basename($fPath);
                    $cleanName = preg_replace('/^\d+_[a-z0-9]+_report_witness_/i', '', $rawName);
                    $cleanName = preg_replace('/^\d+_report_witness_/i', '', $cleanName);
                    $cleanName = preg_replace('/^report_witness_/i', '', $cleanName);
                    $ext = strtoupper(pathinfo($fPath, PATHINFO_EXTENSION));
                    $url = (str_starts_with($fPath, 'http://') || str_starts_with($fPath, 'https://')) ? $fPath : asset($fPath);
                    $rwFilesFormatted[] = [
                        'path'     => $fPath,
                        'name'     => $cleanName ?: $rawName,
                        'raw_name' => $rawName,
                        'ext'      => $ext ?: 'DOC',
                        'url'      => $url,
                    ];
                }
            }
        }

        $caseMapData[$rCaseNo] = [
            'location'                  => $repItem->location_post ?? 'Kotoka International Airport (Cargo Wing)',
            'devices'                   => $repItem->devices_retrieved ?? ($electronicCategory . ' / Mobile Exhibits'),
            'qty'                       => '1',
            'description'               => $repItem->brief_case_description ?? '',
            'date'                      => $repItem->investigation_date ? $repItem->investigation_date->format('Y-m-d') : ($repItem->created_at ? $repItem->created_at->format('Y-m-d') : date('Y-m-d')),
            'court_order_requested'     => $repItem->court_order_requested ?? 'NO',
            'court_order_granted'       => $repItem->court_order_granted ?? 'NO',
            'court_order_files'         => $coFilesFormatted,
            'extraction_status'         => $repItem->extraction_analysis_conducted ?? 'NO',
            'extraction_notes'          => '',
            'ongoing_extraction_status' => 'NO',
            'ongoing_extraction_notes'  => '',
            'report_witness_status'     => $repItem->report_witness_statement_sent ?? 'NO',
            'report_witness_notes'      => '',
            'report_witness_files'      => $rwFilesFormatted,
            'intel_shared'              => $repItem->intel_shared ?? 'NO',
            'remark'                    => $repItem->remark ?? '',
            'suspect_name'              => $sInfo['name'],
            'suspect_alias'             => $sInfo['alias'],
            'suspect_mug'               => $sInfo['mug'],
            'co_count'                  => $sInfo['co_count'],
        ];
    }

    $firstOpt    = count($dropdownOptions) > 0 ? $dropdownOptions[0] : null;
    $firstOptVal = $firstOpt ? $firstOpt['value'] : '';
    $firstCaseNo = $firstOpt ? $firstOpt['case_number'] : (count($directoryCases) > 0 ? ($directoryCases[0]->case_number ?? '') : '');
    $lookupKey   = !empty($firstOptVal) && isset($caseMapData[$firstOptVal]) ? $firstOptVal : $firstCaseNo;
    $defaultLoc  = !empty($lookupKey) ? ($caseMapData[$lookupKey]['location'] ?? '') : '';
    $defaultDev  = !empty($lookupKey) ? ($caseMapData[$lookupKey]['devices'] ?? '') : '';
    $defaultQty  = !empty($lookupKey) ? ($caseMapData[$lookupKey]['qty'] ?? '') : '';
    $defaultDesc = !empty($lookupKey) ? ($caseMapData[$lookupKey]['description'] ?? '') : '';
    $defaultDate = !empty($lookupKey) ? ($caseMapData[$lookupKey]['date'] ?? '') : '';
    $firstSaved  = !empty($firstCaseNo) ? $savedReports->firstWhere('case_number', $firstCaseNo) : null;
    $defaultCourtGranted = !empty($lookupKey) ? ($caseMapData[$lookupKey]['court_order_granted'] ?? 'NO') : ($firstSaved ? $firstSaved->court_order_granted : 'NO');
    $defaultCourtOrderFiles = !empty($lookupKey) ? ($caseMapData[$lookupKey]['court_order_files'] ?? []) : [];
    $defaultReportWitnessStatus = !empty($lookupKey) ? ($caseMapData[$lookupKey]['report_witness_status'] ?? 'NO') : ($firstSaved ? $firstSaved->report_witness_statement_sent : 'NO');
    $defaultReportWitnessFiles = !empty($lookupKey) ? ($caseMapData[$lookupKey]['report_witness_files'] ?? []) : [];

    $isStaffStatusView = $isStaffStatusView ?? false;
    $staffOfficerName = $authUser->fullname ?? session('user_name') ?? 'Cyber Forensic Specialist';
    $activeOfficerName = $isStaffStatusView ? $staffOfficerName : $headName;

    $headParts = preg_split('/\s+/', trim((string) $activeOfficerName)) ?: [];
    $headInitials = count($headParts) >= 2
        ? strtoupper(substr($headParts[0], 0, 1) . substr(end($headParts), 0, 1))
        : strtoupper(substr((string) $activeOfficerName, 0, 2));

    $unloggedDirectoryCases = $directoryCases->filter(fn($c) => !$directorySavedReports->contains('case_number', $c->case_number ?? ($c['case_number'] ?? '')));
    $kpiTotalRecords = count($directorySavedReports) + count($unloggedDirectoryCases);
    $kpiWarrantsGranted = $directorySavedReports->filter(fn($r) => str_contains(strtoupper((string) ($r->court_order_granted ?? '')), 'YES'))->count();
    $kpiExtractionDone = $directorySavedReports->filter(function ($r) {
        $ext = strtoupper(trim((string) ($r->extraction_analysis_message ?: ($r->extraction_analysis_conducted ?: ''))));
        return str_contains($ext, 'YES') || str_contains($ext, 'COMPLET');
    })->count();
    $kpiIntelSharedCount = $directorySavedReports->filter(fn($r) => str_contains(strtoupper((string) ($r->intel_shared ?? '')), 'YES'))->count();
    $kpiPendingExtraction = max(0, $kpiTotalRecords - $kpiExtractionDone);
    $kpiExtractionRate = $kpiTotalRecords > 0 ? (int) round(($kpiExtractionDone / $kpiTotalRecords) * 100) : 0;

    $defaultSuspectName = !empty($lookupKey) ? ($caseMapData[$lookupKey]['suspect_name'] ?? 'Select Case') : 'Select Case';
    $defaultSuspectAlias = !empty($lookupKey) ? ($caseMapData[$lookupKey]['suspect_alias'] ?? null) : null;
    $defaultSuspectMug = !empty($lookupKey) ? ($caseMapData[$lookupKey]['suspect_mug'] ?? null) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isStaffStatusView ? 'Staff ' : '' }}Forensic &amp; Investigation Status | Digital &amp; Physical Forensics Unit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
        };
    </script>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>

    @include('partials.report_design_system')

    <style>
        input[type="date"][readonly]::-webkit-calendar-picker-indicator {
            opacity: 0.3;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-[#f3f5f8] text-slate-900 selection:bg-emerald-600 selection:text-white">
    @include('partials.flash')

    <div class="flex min-h-screen lg:h-screen w-full overflow-hidden">
        @if($isStaffStatusView)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Digital Forensics'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden">

            <!-- STICKY COMMAND TOPBAR -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <nav class="flex items-center gap-1.5 text-[10px] font-mono-code font-extrabold uppercase tracking-widest text-emerald-700">
                            <a href="{{ $isStaffStatusView ? url('/Digital_Forensics/staff/dashboard') : url('/Digital_Forensics') }}" class="hover:underline">
                                Digital &amp; Physical Forensics
                            </a>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">{{ $isStaffStatusView ? 'Staff Status & Reports' : 'Status & SITREP' }}</span>
                        </nav>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Forensic Extraction &amp; Investigation Status Report
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    @if($isStaffStatusView)
                        @php
                            $canHeaderCreateCases = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('create_cases', 'Digital & Physical Forensics') : true);
                            $headerLockedTooltip = 'Locked: Create New Cases permission is disabled by your Head of Unit';
                        @endphp
                        <a @if($canHeaderCreateCases) href="{{ url('/Digital_Forensics/staff/create') }}" @endif
                           data-staff-create-btn="header"
                           data-create-url="{{ url('/Digital_Forensics/staff/create') }}"
                           aria-disabled="{{ $canHeaderCreateCases ? 'false' : 'true' }}"
                           @if(!$canHeaderCreateCases)
                               tabindex="0"
                               title="{{ $headerLockedTooltip }}"
                               data-locked-tooltip="{{ $headerLockedTooltip }}"
                               onclick="event.preventDefault(); event.stopPropagation(); return false;"
                               style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                           @endif
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 {{ $canHeaderCreateCases ? 'rep-btn-green' : 'bg-slate-200 text-slate-400 border border-slate-300 select-none' }} font-extrabold text-xs rounded-xl transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>New Case</span>
                            <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 {{ $canHeaderCreateCases ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </a>
                    @endif

                    <div class="hidden xl:flex items-center gap-2.5 {{ $isStaffStatusView ? 'pl-3 ml-0.5 border-l border-slate-200' : '' }}">
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[140px]">{{ $activeOfficerName }}</div>
                            <div class="text-[10px] font-mono-code font-semibold text-emerald-700">{{ $isStaffStatusView ? 'Cyber Specialist' : 'DPFU Command' }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $headInitials ?: 'DF' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 4-COLUMN TELEMETRY STRIP -->
                <section aria-label="Forensic Investigation Summary" class="rep-masthead">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Extraction Warrants, Report &amp; Intelligence Status
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Track court extraction orders, mobile/media forensic acquisition, witness statement dispatches, and intelligence referrals across electronic device dockets.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Form Reference</div>
                                    <div class="text-xs font-mono-code font-extrabold text-slate-900 mt-0.5">DF-STATUS-{{ date('Y-md') }}</div>
                                </div>
                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Extraction Rate</div>
                                    <div class="text-xs font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">{{ $kpiExtractionRate }}% Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rep-kpi-grid grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/80">
                        <button type="button" onclick="quickFilterDirectory('', '')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-slate-500 group-hover:text-slate-900">Tracked Dockets</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                            </div>
                            <div id="kpiStatTotal" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ $kpiTotalRecords }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">Electronic device dockets &rarr;</div>
                        </button>

                        <button type="button" onclick="quickFilterDirectory('YES', '')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-teal-700">Warrants Granted</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                            </div>
                            <div id="kpiStatWarrants" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ $kpiWarrantsGranted }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">Judicial extraction orders &rarr;</div>
                        </button>

                        <button type="button" onclick="quickFilterDirectory('', 'YES')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-emerald-700">Extraction Complete</span>
                                <span id="kpiStatRateBadge" class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-extrabold tnum">{{ $kpiExtractionRate }}%</span>
                            </div>
                            <div id="kpiStatExtracted" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ $kpiExtractionDone }}</div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2.5">
                                <div id="kpiStatRateBar" class="h-full bg-emerald-600 rounded-full transition-all duration-300" style="width: {{ $kpiExtractionRate }}%;"></div>
                            </div>
                        </button>

                        <button type="button" onclick="quickFilterDirectory('', 'NO')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-amber-700">Pending Extraction</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="kpiStatPending" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ $kpiPendingExtraction }}</div>
                            <div class="text-[10.5px] text-slate-500 mt-1">
                                <span id="kpiStatIntelSub" class="font-mono-code font-extrabold text-emerald-700 tnum">{{ $kpiIntelSharedCount }}</span> shared with Intel Dept
                            </div>
                        </button>
                    </div>
                </section>

                <!-- 2. INTERACTIVE STATUS ENTRY & UPDATE WORKBENCH -->
                <section id="statusWorkbenchSection" class="rep-surface-card overflow-hidden scroll-mt-5">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/80 border-b border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 012.828 0L20 5.828a2 2 0 010 2.828l-8.414 8.414-3.536 1.414 1.414-3.536L16.586 3.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                                    Forensic Extraction &amp; Warrant Workbench
                                </h2>
                                <p class="text-xs text-slate-600">Select an electronic exhibit docket to log court warrant status, forensic extraction findings, witness statements, and intel sharing.</p>
                            </div>
                        </div>

                        <!-- LIVE SELECTED SUBJECT PREVIEW PILL -->
                        <div id="selectedSuspectBanner" class="flex items-center gap-3 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs shrink-0">
                            <div id="selectedSuspectThumbWrap" class="w-9 h-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                @if(!empty($defaultSuspectMug))
                                    <img id="selectedSuspectThumbImg" src="{{ $defaultSuspectMug }}" alt="{{ $defaultSuspectName }}" class="w-full h-full object-cover cursor-pointer" onclick="openMugshotModal(this.src, document.getElementById('selectedSuspectNameLabel')?.textContent || 'Suspect')">
                                @else
                                    <svg id="selectedSuspectFallbackIcon" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-[9.5px] font-mono-code font-bold uppercase tracking-wider text-slate-400">Active Subject</div>
                                <div class="flex items-center gap-1.5">
                                    <span id="selectedSuspectNameLabel" class="text-xs font-mono-code font-extrabold text-slate-900 uppercase truncate max-w-[180px]">{{ $defaultSuspectName }}</span>
                                    @if(!empty($defaultSuspectAlias))
                                        <span id="selectedSuspectAliasLabel" class="text-[10.5px] text-slate-500 italic">({{ $defaultSuspectAlias }})</span>
                                    @else
                                        <span id="selectedSuspectAliasLabel" class="hidden text-[10.5px] text-slate-500 italic"></span>
                                    @endif
                                </div>
                            </div>
                            <span id="selectedStageBadge" class="ml-1 px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold {{ strtoupper(trim($defaultCourtGranted)) === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ strtoupper(trim($defaultCourtGranted)) === 'YES' ? 'Warrant Granted' : 'Stage 1 Intake' }}
                            </span>
                        </div>
                    </div>

                    <form id="forensicStatusForm" action="{{ url('/Digital_Forensics/forensic-status') }}" method="POST" enctype="multipart/form-data" onsubmit="handleStatusSubmit(event)" class="p-5 sm:p-6 space-y-5 bg-slate-50/40">
                        @csrf

                        <!-- STAGE 01: CURRENT / NEW CASES -->
                        <div id="currentCasesSectionContainer" class="p-5 bg-white border border-slate-200/90 rounded-2xl shadow-2xs space-y-5 transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-800 border border-slate-300 font-mono-code text-[10px] font-extrabold">STAGE 01</span>
                                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                        Current / New Electronic Dockets
                                    </h3>
                                </div>
                                <span class="text-[10.5px] font-mono-code text-slate-500">
                                    Docket &amp; device metadata synced read-only from Case Register
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <!-- Case Reference Select -->
                                <div class="space-y-1.5">
                                    <label for="statusCaseRef" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Case Reference <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="statusCaseRef" name="case_number" onchange="onDigitalCaseRefChanged(this.value)" required class="rep-select w-full px-3.5 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="">— Select Digital Case Reference —</option>
                                        @forelse($dropdownOptions as $idx => $opt)
                                            <option value="{{ $opt['value'] }}" {{ $idx === 0 ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                        @empty
                                            <option value="" disabled>No electronic-devices suspects available for this selection</option>
                                        @endforelse
                                    </select>
                                </div>

                                <!-- Location / Post -->
                                <div class="space-y-1.5">
                                    <label for="statusLocation" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Location / Post <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="statusLocation" name="location_post" value="{{ $defaultLoc }}" readonly required placeholder="e.g. Kotoka International Airport" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Date -->
                                <div class="space-y-1.5">
                                    <label for="statusDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Investigation Date <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" id="statusDate" name="investigation_date" value="{{ $defaultDate }}" readonly required tabindex="-1" onkeydown="return false;" onclick="if(this.readOnly){this.blur(); return false;}" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Devices / Exhibits Retrieved -->
                                <div class="space-y-1.5 md:col-span-2 lg:col-span-2">
                                    <label for="statusDevicesRetrieved" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Devices / Exhibits Retrieved <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="statusDevicesRetrieved" name="devices_retrieved" value="{{ $defaultDev }}" readonly required placeholder="e.g. 2x Mobile Phones, 1x Hard Drive, 3x SIM Cards" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Qty -->
                                <div class="space-y-1.5 md:col-span-1 lg:col-span-1">
                                    <label for="statusQty" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Quantity <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="statusQty" name="quantity" value="{{ $defaultQty }}" readonly required placeholder="e.g. 1" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                            </div>

                            <!-- Brief Case Description -->
                            <div class="space-y-1.5">
                                <label for="briefCaseDescription" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Brief Case Description
                                </label>
                                <textarea id="briefCaseDescription" name="brief_case_description" rows="3" placeholder="Enter a concise summary of the case facts, suspect operations, and intelligence context..." class="rep-input w-full px-4 py-3 text-xs font-sans leading-relaxed custom-scrollbar">{{ $defaultDesc }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <!-- Court Order Requested? -->
                                <div class="space-y-1.5">
                                    <label for="courtOrderRequested" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Digital Court Order Requested? <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="courtOrderRequested" name="court_order_requested" onchange="handleCourtOrderRequestedChange(this.value)" required class="rep-select w-full px-3.5 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>
                                </div>

                                <!-- Court Order Granted? -->
                                <div class="space-y-1.5">
                                    <label for="courtOrderGranted" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Digital Court Order Granted? <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="courtOrderGranted" name="court_order_granted" onchange="toggleCourtOrderFile(this.value)" required disabled class="rep-select w-full px-3.5 py-2.5 text-xs font-mono-code font-semibold disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed disabled:border-slate-200">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>

                                    <div id="courtOrderFileWrapper" class="{{ strtoupper(trim($defaultCourtGranted)) === 'YES' ? '' : 'hidden' }} pt-1.5">
                                        <div class="p-3 bg-emerald-50/70 border border-emerald-200/90 rounded-2xl space-y-2.5 transition-all">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-black text-emerald-800 font-mono-code uppercase tracking-wider flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    Upload Court Order Documents
                                                </span>
                                                <span id="courtOrderBadge" class="hidden text-[9px] font-extrabold bg-emerald-600 text-white px-2 py-0.5 rounded-full font-mono-code">ATTACHED</span>
                                            </div>

                                            <div id="courtOrderSavedFilesWrapper" class="{{ !empty($defaultCourtOrderFiles) ? '' : 'hidden' }} space-y-1.5 p-2.5 bg-white/95 border border-emerald-300/80 rounded-xl shadow-2xs">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[9.5px] font-black text-emerald-900 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        Saved Court Order Documents
                                                    </span>
                                                    <span id="courtOrderSavedBadge" class="text-[8.5px] font-bold text-emerald-700 font-mono-code bg-emerald-100 px-2 py-0.5 rounded-full">
                                                        {{ count($defaultCourtOrderFiles) }} SAVED
                                                    </span>
                                                </div>
                                                <div id="courtOrderSavedFilesList" class="space-y-1">
                                                    @foreach($defaultCourtOrderFiles as $savedCoFile)
                                                        @php
                                                            $extBadgeBg = 'bg-emerald-700';
                                                            if ($savedCoFile['ext'] === 'PDF') $extBadgeBg = 'bg-rose-600';
                                                            elseif (in_array($savedCoFile['ext'], ['JPG', 'JPEG', 'PNG', 'WEBP'])) $extBadgeBg = 'bg-sky-600';
                                                            elseif (in_array($savedCoFile['ext'], ['DOC', 'DOCX'])) $extBadgeBg = 'bg-indigo-600';
                                                        @endphp
                                                        <div class="flex items-center justify-between p-1.5 bg-slate-50 border border-slate-200/90 rounded-lg hover:border-emerald-400 transition-all">
                                                            <div class="flex items-center gap-2 min-w-0 pr-2">
                                                                <span class="w-5 h-5 rounded {{ $extBadgeBg }} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                                                    {{ $savedCoFile['ext'] ?: 'DOC' }}
                                                                </span>
                                                                <span class="text-[10px] font-bold text-slate-800 truncate" title="{{ $savedCoFile['name'] }}">
                                                                    {{ $savedCoFile['name'] }}
                                                                </span>
                                                            </div>
                                                            <a href="{{ $savedCoFile['url'] }}" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold font-mono-code flex items-center gap-1 transition-colors shrink-0 no-underline shadow-2xs">
                                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                <span>View</span>
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="relative flex items-center justify-center border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-white rounded-xl p-2.5 transition-all cursor-pointer group">
                                                <input type="file" id="courtOrderFileInput" name="court_order_files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onclick="this.value = null;" oncancel="restoreFileInputFiles('court')" onchange="handleFileSelected(this, 'court')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="flex items-center gap-2 text-center">
                                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    </div>
                                                    <div class="text-left min-w-0">
                                                        <p id="courtOrderFileNameDisplay" class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-800 truncate max-w-[160px]">
                                                            Choose Court Order File(s)
                                                        </p>
                                                        <p class="text-[9px] text-slate-400 font-mono-code">PDF, DOCX, JPG, PNG &bull; Multiple supported</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="courtOrderFileList" class="hidden grid grid-cols-4 gap-1.5 pt-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Extraction & Analysis Conducted? (STAGE 01) -->
                                <div class="space-y-1.5">
                                    <label for="extractionAnalysisConducted" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Extraction &amp; Analysis Conducted?
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-50 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="extractionAnalysisConducted" name="extraction_analysis_status" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="IN PROGRESS">IN PROGRESS</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Status + notes</span>
                                        </div>
                                        <textarea id="extractionAnalysisNotes" name="extraction_analysis_notes" rows="3" placeholder="Message / Details (e.g. Preliminary forensic analysis completed; full extraction pending grant of court order...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- STAGE 02: ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED) -->
                        <div id="ongoingCasesSectionContainer" class="hidden p-5 bg-emerald-50/40 border border-emerald-200 rounded-2xl space-y-5 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-emerald-200/80">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded bg-emerald-700 text-white font-mono-code text-[10px] font-extrabold">STAGE 02</span>
                                    <h3 class="text-xs sm:text-sm font-extrabold text-emerald-950 font-mono-code uppercase tracking-wider">
                                        Ongoing Cases (Extraction Court Orders Received)
                                    </h3>
                                </div>
                                <span class="text-[10.5px] font-mono-code font-semibold text-emerald-800">
                                    Judicial extraction authorization active
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <!-- Ongoing Extraction -->
                                <div class="space-y-1.5">
                                    <label for="ongoingExtractionAnalysisConducted" class="block text-[10.5px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Extraction &amp; Analysis Conducted?
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-50 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="ongoingExtractionAnalysisConducted" name="ongoing_extraction_analysis_status" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="IN PROGRESS">IN PROGRESS</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Status + notes</span>
                                        </div>
                                        <textarea id="ongoingExtractionAnalysisNotes" name="ongoing_extraction_analysis_notes" rows="3" placeholder="Message / Details (e.g. Full extraction and forensic analysis completed under granted court order...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>
                                </div>

                                <!-- Report & Witness Statement -->
                                <div class="space-y-1.5">
                                    <label for="reportWitnessSent" class="block text-[10.5px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Report, Witness Statement &amp; Evidence Sent <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-50 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="reportWitnessSent" name="report_witness_statement_status" onchange="toggleReportWitnessFile(this.value)" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="PENDING">PENDING</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Status + notes</span>
                                        </div>
                                        <textarea id="reportWitnessNotes" name="report_witness_statement_notes" rows="3" placeholder="Message / Details (e.g. Full forensic analytical report and witness statements transmitted to Investigations Directorate...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>

                                    <div id="reportWitnessFileWrapper" class="{{ strtoupper(trim($defaultReportWitnessStatus)) === 'YES' ? '' : 'hidden' }} pt-1.5">
                                        <div class="p-3 bg-white border border-emerald-200/90 rounded-2xl space-y-2.5 transition-all">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-black text-emerald-800 font-mono-code uppercase tracking-wider flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    Upload Report &amp; Statement File(s)
                                                </span>
                                                <span id="reportWitnessBadge" class="hidden text-[9px] font-extrabold bg-emerald-600 text-white px-2 py-0.5 rounded-full font-mono-code">ATTACHED</span>
                                            </div>

                                            <div id="reportWitnessSavedFilesWrapper" class="{{ !empty($defaultReportWitnessFiles) ? '' : 'hidden' }} space-y-1.5 p-2.5 bg-slate-50 border border-emerald-300/80 rounded-xl shadow-2xs">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[9.5px] font-black text-emerald-900 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        Saved Report &amp; Statement Documents
                                                    </span>
                                                    <span id="reportWitnessSavedBadge" class="text-[8.5px] font-bold text-emerald-700 font-mono-code bg-emerald-100 px-2 py-0.5 rounded-full">
                                                        {{ count($defaultReportWitnessFiles) }} SAVED
                                                    </span>
                                                </div>
                                                <div id="reportWitnessSavedFilesList" class="space-y-1">
                                                    @foreach($defaultReportWitnessFiles as $savedRwFile)
                                                        @php
                                                            $extBadgeBg = 'bg-emerald-700';
                                                            if ($savedRwFile['ext'] === 'PDF') $extBadgeBg = 'bg-rose-600';
                                                            elseif (in_array($savedRwFile['ext'], ['JPG', 'JPEG', 'PNG', 'WEBP'])) $extBadgeBg = 'bg-sky-600';
                                                            elseif (in_array($savedRwFile['ext'], ['DOC', 'DOCX'])) $extBadgeBg = 'bg-indigo-600';
                                                        @endphp
                                                        <div class="flex items-center justify-between p-1.5 bg-white border border-slate-200/90 rounded-lg hover:border-emerald-400 transition-all">
                                                            <div class="flex items-center gap-2 min-w-0 pr-2">
                                                                <span class="w-5 h-5 rounded {{ $extBadgeBg }} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                                                    {{ $savedRwFile['ext'] ?: 'DOC' }}
                                                                </span>
                                                                <span class="text-[10px] font-bold text-slate-800 truncate" title="{{ $savedRwFile['name'] }}">
                                                                    {{ $savedRwFile['name'] }}
                                                                </span>
                                                            </div>
                                                            <a href="{{ $savedRwFile['url'] }}" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold font-mono-code flex items-center gap-1 transition-colors shrink-0 no-underline shadow-2xs">
                                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                <span>View</span>
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="relative flex items-center justify-center border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-slate-50/70 rounded-xl p-2.5 transition-all cursor-pointer group">
                                                <input type="file" id="reportWitnessFileInput" name="report_witness_statement_files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onclick="this.value = null;" oncancel="restoreFileInputFiles('witness')" onchange="handleFileSelected(this, 'witness')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="flex items-center gap-2 text-center">
                                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    </div>
                                                    <div class="text-left min-w-0">
                                                        <p id="reportWitnessFileNameDisplay" class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-800 truncate max-w-[160px]">
                                                            Choose Statement / Report File(s)
                                                        </p>
                                                        <p class="text-[9px] text-slate-400 font-mono-code">PDF, DOCX, JPG, PNG &bull; Multiple supported</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="reportWitnessFileList" class="hidden grid grid-cols-4 gap-1.5 pt-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Intelligence Shared -->
                                <div class="space-y-1.5">
                                    <label for="intelShared" class="block text-[10.5px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Intelligence Shared to Intel Dept? <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="intelShared" name="intel_shared" class="rep-select w-full px-3.5 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>
                                </div>

                            </div>

                            <!-- Remark -->
                            <div class="space-y-1.5">
                                <label for="statusRemark" class="block text-[10.5px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Examiner Remarks
                                </label>
                                <textarea id="statusRemark" name="remark" rows="3" placeholder="Enter additional remarks or status details regarding ongoing case analysis..." class="rep-input w-full px-4 py-3 text-xs font-sans leading-relaxed custom-scrollbar"></textarea>
                            </div>
                        </div>

                        <!-- Submit Bar -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-200/80">
                            <div class="flex items-center gap-3 flex-wrap">
                                <button type="button"
                                        onclick="openSelectedCasePdfPreview()"
                                        class="rep-btn-green-soft inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Preview Single-Case DPF PDF</span>
                                </button>
                                <div id="statusAlertMsg" class="hidden items-center gap-2 text-xs font-mono-code font-bold text-emerald-700">
                                    <span>Forensic &amp; Investigation Status updated.</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 justify-end">
                                <button type="reset" class="rep-btn-green-soft px-4 py-2 font-bold text-xs rounded-xl">
                                    Reset Form
                                </button>
                                <button type="submit" id="saveStatusBtn" class="rep-btn-green px-5 py-2.5 font-extrabold text-xs rounded-xl flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Save &amp; Log Investigation Status</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- 3. LIVE FORENSIC INVESTIGATION DIRECTORY TABLE -->
                <section id="investigationDirectorySection" class="rep-surface-card overflow-hidden scroll-mt-5">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/80 border-b border-slate-200/80 flex flex-col xl:flex-row xl:items-center justify-between gap-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                        Digital Forensic &amp; Investigation Directory
                                    </h3>
                                    <span id="directoryRowCountBadge" class="px-2.5 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-800 font-mono-code tnum">
                                        Total: {{ $kpiTotalRecords }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Click <strong class="text-slate-700">Edit</strong> on any row to hydrate the workbench above, or <strong class="text-slate-700">Preview</strong> to open its official PDF report.</p>
                            </div>
                        </div>

                        <div class="relative w-full xl:max-w-sm">
                            <input type="text" id="directorySearchInput" oninput="applyDirectoryFilters()" onkeyup="applyDirectoryFilters()" placeholder="Filter by Suspect, Case Ref, Location, or Status..." class="rep-input w-full pl-9 pr-3 py-2 text-xs font-mono-code font-semibold">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- PRECISION FILTER TOOLBAR -->
                    <div class="px-4 py-3 sm:px-6 bg-slate-50/70 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="rep-segmented font-mono-code text-xs font-bold">
                            <button type="button" onclick="setDateFilterPreset('all')" id="btnPresetAll" class="rep-btn-green px-3 py-1 rounded-lg text-xs font-mono-code font-bold cursor-pointer">
                                All Time
                            </button>
                            <button type="button" onclick="setDateFilterPreset('weekly')" id="btnPresetWeekly" class="rep-btn-green-soft px-3 py-1 rounded-lg text-xs font-mono-code font-bold cursor-pointer">
                                Weekly
                            </button>
                            <button type="button" onclick="setDateFilterPreset('monthly')" id="btnPresetMonthly" class="rep-btn-green-soft px-3 py-1 rounded-lg text-xs font-mono-code font-bold cursor-pointer">
                                Monthly
                            </button>
                            <button type="button" onclick="setDateFilterPreset('annually')" id="btnPresetAnnually" class="rep-btn-green-soft px-3 py-1 rounded-lg text-xs font-mono-code font-bold cursor-pointer">
                                Annually
                            </button>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <select id="directoryCourtFilter" onchange="applyDirectoryFilters()" class="rep-select px-2.5 py-1.5 text-[11px] font-mono-code font-semibold">
                                <option value="">Court Order: All</option>
                                <option value="YES">Granted (YES)</option>
                                <option value="NO">Not Granted (NO)</option>
                            </select>

                            <select id="directoryExtractionFilter" onchange="applyDirectoryFilters()" class="rep-select px-2.5 py-1.5 text-[11px] font-mono-code font-semibold">
                                <option value="">Extraction: All</option>
                                <option value="YES">Completed (YES)</option>
                                <option value="IN PROGRESS">In Progress</option>
                                <option value="NO">Pending / NO</option>
                            </select>

                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase">From</span>
                                <input type="date" id="dateFilterFrom" oninput="onCustomDateChange()" onchange="onCustomDateChange()" class="rep-input px-2 py-1 text-[11px] font-mono-code font-semibold">
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase">To</span>
                                <input type="date" id="dateFilterTo" oninput="onCustomDateChange()" onchange="onCustomDateChange()" class="rep-input px-2 py-1 text-[11px] font-mono-code font-semibold">
                            </div>
                            <button type="button" onclick="generateDirectoryFilter()" id="btnGenerateFilter" title="Export Filtered Directory PDF" class="rep-btn-green px-3 py-1.5 rounded-lg text-[11px] font-mono-code font-bold flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Generate PDF</span>
                            </button>
                            <button type="button" onclick="resetDateFilters()" title="Reset All Filters" class="rep-btn-green-soft px-2.5 py-1.5 rounded-lg text-[11px] font-mono-code font-bold flex items-center gap-1 cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="rep-table w-full min-w-[1040px] text-xs text-left border-collapse" id="forensicDirectoryTable">
                            <thead>
                                <tr>
                                    <th class="p-3.5 pl-5">Case Ref</th>
                                    <th class="p-3.5">Suspect</th>
                                    <th class="p-3.5">Logged Date</th>
                                    <th class="p-3.5">Court Order</th>
                                    <th class="p-3.5">Extraction &amp; Analysis</th>
                                    <th class="p-3.5">Statement &amp; Evidence Sent</th>
                                    <th class="p-3.5">Intel Shared</th>
                                    <th class="p-3.5 pr-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white font-mono-code" id="forensicTableBody">
                                @foreach($directorySavedReports as $rep)
                                    @php
                                        $coFiles = $rep->court_order_files;
                                        $rwFiles = $rep->report_witness_statement_files;
                                        $rawRepDate = !empty($rep->investigation_date) ? (string)$rep->investigation_date : ($rep->created_at ? $rep->created_at->format('Y-m-d') : date('Y-m-d'));
                                        $repTime = strtotime($rawRepDate);
                                        $repDateIso = ($repTime && $repTime > 0) ? date('Y-m-d', $repTime) : date('Y-m-d');
                                        $repDateDisplay = ($repTime && $repTime > 0) ? date('d M Y', $repTime) : date('d M Y');
                                        $candForCase = $candidatesByCase->get($rep->case_number)?->first();
                                        $repLoc = $rep->location_post ?? ($caseMapData[$rep->case_number]['location'] ?? ($candForCase['location'] ?? 'Kotoka International Airport (Cargo Wing)'));
                                        $repCourt = strtoupper(trim($rep->court_order_granted ?? 'NO'));
                                        if ($candForCase) {
                                            $sName = $candForCase['suspect_name'];
                                            $sAlias = $candForCase['suspect_alias'];
                                            $sMug = $candForCase['suspect_mug'];
                                            $sCoCount = $candForCase['is_multi'] ? max(0, count($candForCase['multi_case']->suspects ?? []) - 1) : 0;
                                        } else {
                                            $sInfo = $caseMapData[$rep->case_number] ?? $resolveSuspectInfo($allCasesByNum->get($rep->case_number), $rep);
                                            $sName = $sInfo['suspect_name'] ?? ($sInfo['name'] ?? 'Unknown Suspect');
                                            $sAlias = $sInfo['suspect_alias'] ?? ($sInfo['alias'] ?? null);
                                            $sMug = $sInfo['suspect_mug'] ?? ($sInfo['mug'] ?? null);
                                            $sCoCount = $sInfo['co_count'] ?? 0;
                                        }
                                        if (!empty($sMug)) {
                                            $sMug = \App\Services\CasePhotoResolver::resolveMugshotUrl($sMug);
                                        }

                                        $extRaw = trim($rep->extraction_analysis_message ?: ($rep->extraction_analysis_conducted ?: 'NO'));
                                        $extBadge = null;
                                        $extNote = null;
                                        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.+)$/is', $extRaw, $m)) {
                                            $extBadge = strtoupper($m[1]);
                                            $candidateNote = trim($m[2]);
                                            if (strtoupper($candidateNote) !== $extBadge && !in_array(strtoupper($candidateNote), ['N/A', 'NONE', 'NO', 'YES'])) {
                                                $extNote = $candidateNote;
                                            }
                                        } elseif (in_array(strtoupper($extRaw), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                                            $extBadge = strtoupper($extRaw);
                                        } else {
                                            $extNote = $extRaw;
                                        }
                                        $extBadgeClass = $extBadge === 'YES'
                                            ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                            : ($extBadge === 'NO' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200');
                                        $repExtNormalized = strtoupper(trim($extBadge ?: 'NO'));
                                    @endphp
                                    <tr class="hover:bg-slate-50/90 transition-colors directory-row"
                                        data-date="{{ $repDateIso }}"
                                        data-case="{{ strtolower($rep->case_number) }}"
                                        data-caseref="{{ $rep->case_number }}"
                                        data-code="{{ strtolower($rep->dpf_code ?? '') }}"
                                        data-location="{{ strtolower($repLoc) }}"
                                        data-suspect="{{ strtolower($sName . ' ' . ($sAlias ?? '')) }}"
                                        data-court="{{ $repCourt }}"
                                        data-extraction="{{ $repExtNormalized }}"
                                        data-intel="{{ strtoupper(trim($rep->intel_shared ?? 'NO')) }}">
                                        <td class="p-3.5 pl-5 align-top font-extrabold text-emerald-800">
                                            <div>{{ $rep->case_number }}</div>
                                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                                @if(!empty($rep->dpf_code))
                                                    <span class="text-[9.5px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded">{{ $rep->dpf_code }}</span>
                                                @endif
                                                <span class="text-[10px] text-slate-400 font-sans font-normal truncate max-w-[140px]" title="{{ $repLoc }}">{{ $repLoc }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 align-top min-w-[180px]">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-9 h-11 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs flex items-center justify-center relative">
                                                    @if(!empty($sMug))
                                                        <img src="{{ $sMug }}" alt="{{ $sName }}" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="openMugshotModal('{{ addslashes($sMug) }}', '{{ addslashes($sName) }}')" title="Click to enlarge photo">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400" title="No front mugshot on file">
                                                            <svg class="w-4.5 h-4.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-[11px] text-slate-900 leading-tight uppercase font-mono-code truncate max-w-[145px]" title="{{ $sName }}">
                                                        {{ $sName }}
                                                    </div>
                                                    @if(!empty($sAlias))
                                                        <div class="text-[10px] text-slate-500 font-sans italic truncate max-w-[145px]">a.k.a. {{ $sAlias }}</div>
                                                    @endif
                                                    @if($sCoCount > 0)
                                                        <span class="inline-block px-1.5 py-0.2 mt-0.5 rounded text-[8.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200" title="{{ $sCoCount }} Co-Suspect(s) in case">
                                                            +{{ $sCoCount }} Co-Suspect{{ $sCoCount > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3.5 align-top whitespace-nowrap">
                                            <div class="font-bold text-[11px] text-slate-800">{{ $repDateDisplay }}</div>
                                            <span class="text-[10px] text-slate-400 font-sans block mt-0.5">{{ $rep->created_at ? $rep->created_at->format('h:i A') : 'Saved Status' }}</span>
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $rep->court_order_granted === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($rep->court_order_granted === 'NO' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }} text-[10px] font-bold rounded-md">
                                                {{ $rep->court_order_granted }}
                                            </span>
                                            @if(!empty($coFiles))
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1 max-w-[200px]">
                                                    @foreach($coFiles as $idx => $fPath)
                                                        <a href="{{ asset($fPath) }}" target="_blank" class="px-1.5 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                                            <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span>Order {{ count($coFiles) > 1 ? '#' . ($idx + 1) : '' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3.5 align-top">
                                            @if($extBadge)
                                                <span class="px-2 py-0.5 {{ $extBadgeClass }} text-[10px] font-bold rounded-md">
                                                    {{ $extBadge }}
                                                </span>
                                            @endif
                                            @if($extNote)
                                                <p class="{{ $extBadge ? 'mt-1' : '' }} text-[10.5px] text-slate-600 font-sans leading-snug max-w-[220px] line-clamp-2" title="{{ $extRaw }}">
                                                    {{ $extNote }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="p-3.5 align-top">
                                            @php
                                                $rwRaw = trim($rep->report_witness_statement_message ?: ($rep->report_witness_statement_sent ?: 'NO'));
                                                $rwBadge = null;
                                                $rwNote = null;
                                                if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.+)$/is', $rwRaw, $mRw)) {
                                                    $rwBadge = strtoupper($mRw[1]);
                                                    $candidateRwNote = trim($mRw[2]);
                                                    if (strtoupper($candidateRwNote) !== $rwBadge && !in_array(strtoupper($candidateRwNote), ['N/A', 'NONE', 'NO', 'YES'])) {
                                                        $rwNote = $candidateRwNote;
                                                    }
                                                } elseif (in_array(strtoupper($rwRaw), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                                                    $rwBadge = strtoupper($rwRaw);
                                                } else {
                                                    $rwNote = $rwRaw;
                                                }
                                                $rwBadgeClass = $rwBadge === 'YES'
                                                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                                    : ($rwBadge === 'NO' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200');
                                            @endphp
                                            @if($rwBadge)
                                                <span class="px-2 py-0.5 {{ $rwBadgeClass }} text-[10px] font-bold rounded-md">
                                                    {{ $rwBadge }}
                                                </span>
                                            @endif
                                            @if($rwNote)
                                                <p class="{{ $rwBadge ? 'mt-1' : '' }} text-[10.5px] text-slate-600 font-sans leading-snug max-w-[220px] line-clamp-2" title="{{ $rwRaw }}">
                                                    {{ $rwNote }}
                                                </p>
                                            @endif
                                            @if(!empty($rwFiles))
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1 max-w-[200px]">
                                                    @foreach($rwFiles as $idx => $fPath)
                                                        <a href="{{ asset($fPath) }}" target="_blank" class="px-1.5 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                                            <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                            <span>Evidence / Stmt {{ count($rwFiles) > 1 ? '#' . ($idx + 1) : '' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $rep->intel_shared === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($rep->intel_shared === 'NO' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }} text-[10px] font-bold rounded-md">
                                                {{ $rep->intel_shared }}
                                            </span>
                                        </td>
                                        <td class="p-3.5 pr-5 align-top text-right whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                <button type="button"
                                                        onclick="selectCaseInStatusForm('{{ addslashes($rep->case_number) }}')"
                                                        class="rep-btn-green-soft px-2.5 py-1 rounded-lg text-[10.5px] font-bold cursor-pointer"
                                                        title="Load {{ $rep->case_number }} into the status form above">
                                                    Edit
                                                </button>
                                                <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . rawurlencode($rep->dpf_code ?? $rep->case_number)) }}"
                                                   class="rep-btn-green px-2.5 py-1 rounded-lg text-[10.5px] font-bold no-underline inline-block">
                                                    Preview
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach($directoryCases as $idx => $cItem)
                                    @php
                                        $cRef = $cItem->case_number ?? ($cItem['case_number'] ?? ('NCC-' . date('Y') . '-DF' . sprintf('%02d', $idx + 1)));
                                        if ($directorySavedReports->contains('case_number', $cRef)) continue;
                                        $candForCase = $candidatesByCase->get($cRef)?->first();
                                        $cLoc = $caseMapData[$cRef]['location'] ?? ($candForCase['location'] ?? 'Kotoka International Airport (Cargo Wing)');
                                        $cDev = $caseMapData[$cRef]['devices'] ?? ($candForCase['devices'] ?? ($electronicCategory . ' / Mobile Exhibits'));
                                        $cRawDate = $caseMapData[$cRef]['date'] ?? ($candForCase['date'] ?? ($cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d')));
                                        $cTime = strtotime((string)$cRawDate);
                                        $cDateIso = ($cTime && $cTime > 0) ? date('Y-m-d', $cTime) : date('Y-m-d');
                                        $cDateDisplay = ($cTime && $cTime > 0) ? date('d M Y', $cTime) : date('d M Y');
                                        $cCourt = strtoupper(trim($caseMapData[$cRef]['court_order_granted'] ?? 'NO'));
                                        $cExt = strtoupper(trim($caseMapData[$cRef]['extraction_status'] ?? 'NO'));
                                        $cRw = strtoupper(trim($caseMapData[$cRef]['report_witness_status'] ?? 'NO'));
                                        $cIntel = strtoupper(trim($caseMapData[$cRef]['intel_shared'] ?? 'NO'));
                                        if ($candForCase) {
                                            $sName = $candForCase['suspect_name'];
                                            $sAlias = $candForCase['suspect_alias'];
                                            $sMug = $candForCase['suspect_mug'];
                                            $sCoCount = $candForCase['is_multi'] ? max(0, count($candForCase['multi_case']->suspects ?? []) - 1) : 0;
                                        } else {
                                            $sInfo = $caseMapData[$cRef] ?? $resolveSuspectInfo($cItem);
                                            $sName = $sInfo['suspect_name'] ?? ($sInfo['name'] ?? 'Unknown Suspect');
                                            $sAlias = $sInfo['suspect_alias'] ?? ($sInfo['alias'] ?? null);
                                            $sMug = $sInfo['suspect_mug'] ?? ($sInfo['mug'] ?? null);
                                            $sCoCount = $sInfo['co_count'] ?? 0;
                                        }
                                        if (!empty($sMug)) {
                                            $sMug = \App\Services\CasePhotoResolver::resolveMugshotUrl($sMug);
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50/90 transition-colors directory-row"
                                        data-date="{{ $cDateIso }}"
                                        data-case="{{ strtolower($cRef) }}"
                                        data-caseref="{{ $cRef }}"
                                        data-code=""
                                        data-location="{{ strtolower($cLoc) }}"
                                        data-suspect="{{ strtolower($sName . ' ' . ($sAlias ?? '')) }}"
                                        data-court="{{ $cCourt }}"
                                        data-extraction="{{ $cExt }}"
                                        data-intel="{{ $cIntel }}">
                                        <td class="p-3.5 pl-5 align-top font-extrabold text-slate-900">
                                            <div>{{ $cRef }}</div>
                                            <div class="text-[10px] text-slate-400 font-sans font-normal truncate max-w-[145px] mt-0.5" title="{{ $cLoc }}">{{ $cLoc }}</div>
                                        </td>
                                        <td class="p-3.5 align-top min-w-[180px]">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-9 h-11 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs flex items-center justify-center relative">
                                                    @if(!empty($sMug))
                                                        <img src="{{ $sMug }}" alt="{{ $sName }}" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="openMugshotModal('{{ addslashes($sMug) }}', '{{ addslashes($sName) }}')" title="Click to enlarge photo">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400" title="No front mugshot on file">
                                                            <svg class="w-4.5 h-4.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-[11px] text-slate-900 leading-tight uppercase font-mono-code truncate max-w-[145px]" title="{{ $sName }}">
                                                        {{ $sName }}
                                                    </div>
                                                    @if(!empty($sAlias))
                                                        <div class="text-[10px] text-slate-500 font-sans italic truncate max-w-[145px]">a.k.a. {{ $sAlias }}</div>
                                                    @endif
                                                    @if($sCoCount > 0)
                                                        <span class="inline-block px-1.5 py-0.2 mt-0.5 rounded text-[8.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200" title="{{ $sCoCount }} Co-Suspect(s) in case">
                                                            +{{ $sCoCount }} Co-Suspect{{ $sCoCount > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3.5 align-top whitespace-nowrap">
                                            <div class="font-bold text-[11px] text-slate-800">{{ $cDateDisplay }}</div>
                                            <span class="text-[10px] text-slate-400 font-sans block mt-0.5">Registry Intake</span>
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $cCourt === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }} text-[10px] font-bold rounded-md">
                                                {{ $cCourt === 'YES' ? 'Granted' : 'NO' }}
                                            </span>
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $cExt === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($cExt === 'IN PROGRESS' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200') }} text-[10px] font-bold rounded-md">
                                                {{ $cExt }}
                                            </span>
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $cRw === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($cRw === 'PENDING' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200') }} text-[10px] font-bold rounded-md">
                                                {{ $cRw }}
                                            </span>
                                        </td>
                                        <td class="p-3.5 align-top">
                                            <span class="px-2 py-0.5 {{ $cIntel === 'YES' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }} text-[10px] font-bold rounded-md">
                                                {{ $cIntel }}
                                            </span>
                                        </td>
                                        <td class="p-3.5 pr-5 align-top text-right whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                <button type="button"
                                                        onclick="selectCaseInStatusForm('{{ addslashes($cRef) }}')"
                                                        class="rep-btn-green-soft px-2.5 py-1 rounded-lg text-[10.5px] font-bold cursor-pointer"
                                                        title="Load {{ $cRef }} into the status form above">
                                                    Edit
                                                </button>
                                                <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . rawurlencode($cRef)) }}"
                                                   class="rep-btn-green px-2.5 py-1 rounded-lg text-[10.5px] font-bold no-underline inline-block">
                                                    Preview
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @php
                                    $totalDirectoryRows = $kpiTotalRecords;
                                @endphp
                                <tr id="directoryNoRecordsRow" class="{{ $totalDirectoryRows > 0 ? 'hidden' : '' }}">
                                    <td colspan="8" class="p-10 text-center text-slate-400 font-sans">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <p class="text-xs font-bold text-slate-700 mb-0.5">No Investigation Records Found</p>
                                            <p class="text-[11px] text-slate-500">Adjust your date range, court warrant filter, or search keywords.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION FOOTER PANEL -->
                    <div id="directoryPaginationPanel" class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono-code">
                        <div class="flex items-center space-x-3 text-slate-600">
                            <div class="flex items-center space-x-1.5">
                                <span class="text-slate-500 font-bold uppercase text-[10px]">Rows:</span>
                                <select id="dirPerPageSelect" onchange="changeDirectoryPerPage(this.value)" class="bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 outline-none focus:border-emerald-600">
                                    <option value="10">10</option>
                                    <option value="15" selected>15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <span class="text-slate-300">|</span>
                            <div>
                                Showing <span id="dirPaginationRange" class="font-extrabold text-slate-900 tnum">0 - 0</span> of <span id="dirPaginationTotal" class="font-extrabold text-slate-900 tnum">0</span> records
                            </div>
                        </div>

                        <div class="flex items-center space-x-1.5" id="dirPaginationControls">
                            <button id="dirPrevPageBtn" onclick="goToDirectoryPage(currentDirPage - 1)" class="rep-btn-green-soft px-3 py-1.5 font-bold rounded-lg text-xs disabled:opacity-40 disabled:cursor-not-allowed flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                <span>Prev</span>
                            </button>

                            <div id="dirPageNumbersContainer" class="flex items-center space-x-1"></div>

                            <button id="dirNextPageBtn" onclick="goToDirectoryPage(currentDirPage + 1)" class="rep-btn-green-soft px-3 py-1.5 font-bold rounded-lg text-xs disabled:opacity-40 disabled:cursor-not-allowed flex items-center space-x-1">
                                <span>Next</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <script>
        const caseMapData = @json($caseMapData);

        // Persistent accumulation pools for uploading files one after the other
        let courtOrderDataTransfer = new DataTransfer();
        let reportWitnessDataTransfer = new DataTransfer();

        function handleCourtOrderRequestedChange(val) {
            const coGranted = document.getElementById('courtOrderGranted');
            if (!coGranted) return;

            const isNo = (val || '').toUpperCase() === 'NO';
            if (isNo) {
                coGranted.value = 'NO';
                coGranted.disabled = true;
                coGranted.title = 'Digital Court Order Granted is disabled when Digital Court Order Requested is NO';
                toggleCourtOrderFile('NO');
            } else {
                coGranted.disabled = false;
                coGranted.title = '';
            }
        }

        function toggleCourtOrderFile(val) {
            const wrapper = document.getElementById('courtOrderFileWrapper');
            const ongoingContainer = document.getElementById('ongoingCasesSectionContainer');
            const stageBadge = document.getElementById('selectedStageBadge');

            if (val === 'YES') {
                if (wrapper) wrapper.classList.remove('hidden');
                if (ongoingContainer) {
                    ongoingContainer.classList.remove('hidden');
                }
                if (stageBadge) {
                    stageBadge.textContent = 'Warrant Granted';
                    stageBadge.className = 'ml-1 px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';
                }
            } else {
                if (wrapper) wrapper.classList.add('hidden');
                if (ongoingContainer) {
                    ongoingContainer.classList.add('hidden');
                }
                if (stageBadge) {
                    stageBadge.textContent = 'Stage 1 Intake';
                    stageBadge.className = 'ml-1 px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-slate-100 text-slate-700 border border-slate-200';
                }
                courtOrderDataTransfer = new DataTransfer();
                const fileInput = document.getElementById('courtOrderFileInput');
                if (fileInput) fileInput.value = '';
                renderFileList('court');
            }
        }

        function toggleReportWitnessFile(val) {
            const wrapper = document.getElementById('reportWitnessFileWrapper');
            if (!wrapper) return;
            if (val === 'YES') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
                reportWitnessDataTransfer = new DataTransfer();
                const fileInput = document.getElementById('reportWitnessFileInput');
                if (fileInput) fileInput.value = '';
                renderFileList('witness');
            }
        }

        function restoreFileInputFiles(type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const input = document.getElementById(isCourt ? 'courtOrderFileInput' : 'reportWitnessFileInput');
            if (input) {
                input.files = pool.files;
            }
        }

        function handleFileSelected(input, type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;

            if (input && input.files && input.files.length > 0) {
                // Add new files into accumulation pool without dropping previous files
                Array.from(input.files).forEach(file => {
                    const alreadyExists = Array.from(pool.files).some(f => 
                        f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
                    );
                    if (!alreadyExists) {
                        pool.items.add(file);
                    }
                });
                // Re-sync pool back to the input element
                input.files = pool.files;
            }

            renderFileList(type);
        }

        function removeFileFromPool(type, index) {
            const isCourt = type === 'court';
            const oldPool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const input = document.getElementById(isCourt ? 'courtOrderFileInput' : 'reportWitnessFileInput');

            const newPool = new DataTransfer();
            Array.from(oldPool.files).forEach((file, idx) => {
                if (idx !== index) {
                    newPool.items.add(file);
                }
            });

            if (isCourt) {
                courtOrderDataTransfer = newPool;
                if (input) input.files = courtOrderDataTransfer.files;
            } else {
                reportWitnessDataTransfer = newPool;
                if (input) input.files = reportWitnessDataTransfer.files;
            }

            renderFileList(type);
        }

        function renderFileList(type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const display = document.getElementById(isCourt ? 'courtOrderFileNameDisplay' : 'reportWitnessFileNameDisplay');
            const badge = document.getElementById(isCourt ? 'courtOrderBadge' : 'reportWitnessBadge');
            const fileList = document.getElementById(isCourt ? 'courtOrderFileList' : 'reportWitnessFileList');

            if (!fileList) return;

            fileList.innerHTML = '';
            const files = Array.from(pool.files);
            const count = files.length;

            if (count > 0) {
                if (display) {
                    display.innerText = count === 1 
                        ? '✔ ' + files[0].name + ' (Click to add more)'
                        : '✔ ' + count + ' Files Attached (Click to add more)';
                }
                if (badge) {
                    badge.innerText = count === 1 ? '1 FILE ATTACHED' : count + ' FILES ATTACHED';
                    badge.classList.remove('hidden');
                }

                fileList.classList.remove('hidden');

                files.forEach((file, idx) => {
                    const ext = (file.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
                    const sizeStr = file.size > 1048576 
                        ? (file.size / 1048576).toFixed(1) + ' MB' 
                        : (file.size / 1024).toFixed(0) + ' KB';

                    let extBg = 'bg-emerald-700';
                    if (ext === 'PDF') extBg = 'bg-rose-600';
                    else if (['JPG', 'JPEG', 'PNG', 'WEBP'].includes(ext)) extBg = 'bg-sky-600';
                    else if (['DOC', 'DOCX'].includes(ext)) extBg = 'bg-indigo-600';

                    const card = document.createElement('div');
                    card.className = 'relative bg-white border border-emerald-200/90 hover:border-emerald-500 rounded-lg p-1.5 flex flex-col justify-between shadow-2xs hover:shadow-xs transition-all group min-w-0';
                    card.innerHTML = `
                        <div class="flex items-center justify-between gap-1 w-full mb-1">
                            <span class="w-5 h-5 rounded ${extBg} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                ${ext}
                            </span>
                            <button type="button" onclick="removeFileFromPool('${type}', ${idx})" class="w-4 h-4 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors shrink-0 cursor-pointer" title="Remove this file">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="min-w-0 w-full text-left">
                            <p class="text-[9.5px] font-bold text-slate-800 truncate" title="${file.name}">
                                ${file.name}
                            </p>
                            <p class="text-[8px] font-mono-code text-slate-400 truncate">
                                ${sizeStr}
                            </p>
                        </div>
                    `;
                    fileList.appendChild(card);
                });
            } else {
                if (display) {
                    display.innerText = isCourt ? 'Choose Court Order File(s)' : 'Choose Statement / Report File(s)';
                }
                if (badge) {
                    badge.classList.add('hidden');
                }
                fileList.classList.add('hidden');
            }
        }

        function renderSavedCourtOrderFiles(files) {
            const wrapper = document.getElementById('courtOrderSavedFilesWrapper');
            const list = document.getElementById('courtOrderSavedFilesList');
            const badge = document.getElementById('courtOrderSavedBadge');
            if (!wrapper || !list) return;

            list.innerHTML = '';
            const safeFiles = Array.isArray(files) ? files : [];

            if (safeFiles.length > 0) {
                if (badge) {
                    badge.innerText = safeFiles.length === 1 ? '1 SAVED' : safeFiles.length + ' SAVED';
                }
                safeFiles.forEach(file => {
                    let rawPath = '';
                    let cleanName = '';
                    let ext = 'DOC';
                    let url = '#';

                    if (typeof file === 'string') {
                        rawPath = file;
                        const rawName = file.split('/').pop() || 'Court Order Document';
                        cleanName = rawName.replace(/^\d+_[a-z0-9]+_court_order_/i, '').replace(/^\d+_court_order_/i, '').replace(/^court_order_/i, '');
                        ext = (cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        url = (file.startsWith('http://') || file.startsWith('https://')) ? file : ('/' + file.replace(/^\/+/, ''));
                    } else if (file && typeof file === 'object') {
                        rawPath = file.path || file.url || '';
                        cleanName = file.name || file.raw_name || (rawPath.split('/').pop() || 'Court Order Document');
                        cleanName = cleanName.replace(/^\d+_[a-z0-9]+_court_order_/i, '').replace(/^\d+_court_order_/i, '').replace(/^court_order_/i, '');
                        ext = (file.ext || cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        url = file.url || ((rawPath.startsWith('http://') || rawPath.startsWith('https://')) ? rawPath : ('/' + rawPath.replace(/^\/+/, '')));
                    }

                    let extBg = 'bg-emerald-700';
                    if (ext === 'PDF') extBg = 'bg-rose-600';
                    else if (['JPG', 'JPEG', 'PNG', 'WEBP'].includes(ext)) extBg = 'bg-sky-600';
                    else if (['DOC', 'DOCX'].includes(ext)) extBg = 'bg-indigo-600';

                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between p-1.5 bg-slate-50 border border-slate-200/90 rounded-lg hover:border-emerald-400 transition-all';
                    item.innerHTML = `
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <span class="w-5 h-5 rounded ${extBg} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                ${ext}
                            </span>
                            <span class="text-[10px] font-bold text-slate-800 truncate" title="${cleanName}">
                                ${cleanName}
                            </span>
                        </div>
                        <a href="${url}" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold font-mono-code flex items-center gap-1 transition-colors shrink-0 no-underline shadow-2xs">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>View</span>
                        </a>
                    `;
                    list.appendChild(item);
                });
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        }

        function renderSavedReportWitnessFiles(files) {
            const wrapper = document.getElementById('reportWitnessSavedFilesWrapper');
            const list = document.getElementById('reportWitnessSavedFilesList');
            const badge = document.getElementById('reportWitnessSavedBadge');
            if (!wrapper || !list) return;

            list.innerHTML = '';
            const safeFiles = Array.isArray(files) ? files : [];

            if (safeFiles.length > 0) {
                if (badge) {
                    badge.innerText = safeFiles.length === 1 ? '1 SAVED' : safeFiles.length + ' SAVED';
                }
                safeFiles.forEach(file => {
                    let rawPath = '';
                    let cleanName = '';
                    let ext = 'DOC';
                    let url = '#';

                    if (typeof file === 'string') {
                        rawPath = file;
                        const rawName = file.split('/').pop() || 'Statement / Report Document';
                        cleanName = rawName.replace(/^\d+_[a-z0-9]+_report_witness_/i, '').replace(/^\d+_report_witness_/i, '').replace(/^report_witness_/i, '');
                        ext = (cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        url = (file.startsWith('http://') || file.startsWith('https://')) ? file : ('/' + file.replace(/^\/+/, ''));
                    } else if (file && typeof file === 'object') {
                        rawPath = file.path || file.url || '';
                        cleanName = file.name || file.raw_name || (rawPath.split('/').pop() || 'Statement / Report Document');
                        cleanName = cleanName.replace(/^\d+_[a-z0-9]+_report_witness_/i, '').replace(/^\d+_report_witness_/i, '').replace(/^report_witness_/i, '');
                        ext = (file.ext || cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        url = file.url || ((rawPath.startsWith('http://') || rawPath.startsWith('https://')) ? rawPath : ('/' + rawPath.replace(/^\/+/, '')));
                    }

                    let extBg = 'bg-emerald-700';
                    if (ext === 'PDF') extBg = 'bg-rose-600';
                    else if (['JPG', 'JPEG', 'PNG', 'WEBP'].includes(ext)) extBg = 'bg-sky-600';
                    else if (['DOC', 'DOCX'].includes(ext)) extBg = 'bg-indigo-600';

                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between p-1.5 bg-slate-50 border border-slate-200/90 rounded-lg hover:border-emerald-400 transition-all';
                    item.innerHTML = `
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <span class="w-5 h-5 rounded ${extBg} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                ${ext}
                            </span>
                            <span class="text-[10px] font-bold text-slate-800 truncate" title="${cleanName}">
                                ${cleanName}
                            </span>
                        </div>
                        <a href="${url}" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold font-mono-code flex items-center gap-1 transition-colors shrink-0 no-underline shadow-2xs">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>View</span>
                        </a>
                    `;
                    list.appendChild(item);
                });
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        }

        function onDigitalCaseRefChanged(caseNo) {
            if (!caseNo) return;
            const data = caseMapData[caseNo] || (caseNo.includes('|') ? caseMapData[caseNo.split('|')[0]] : null);
            if (!data) return;
            const locInput = document.getElementById('statusLocation');
            const devInput = document.getElementById('statusDevicesRetrieved');
            const dateInput = document.getElementById('statusDate');
            const qtyInput = document.getElementById('statusQty');
            const descInput = document.getElementById('briefCaseDescription');

            if (locInput) {
                locInput.value = data.location || '';
                locInput.readOnly = true;
            }
            if (devInput) {
                devInput.value = data.devices || '';
                devInput.readOnly = true;
            }
            if (qtyInput) {
                qtyInput.value = data.qty || '1';
                qtyInput.readOnly = true;
            }
            if (dateInput) {
                dateInput.value = data.date || '';
                dateInput.readOnly = true;
            }
            if (descInput) {
                descInput.value = data.description || '';
            }

            const reqVal = data.court_order_requested || 'NO';
            const coReq = document.getElementById('courtOrderRequested');
            if (coReq) coReq.value = reqVal;

            handleCourtOrderRequestedChange(reqVal);

            const coGranted = document.getElementById('courtOrderGranted');
            if (reqVal !== 'NO') {
                if (coGranted) {
                    if (data.court_order_granted) {
                        coGranted.value = data.court_order_granted;
                    }
                    toggleCourtOrderFile(coGranted.value);
                }
            } else {
                if (coGranted) {
                    coGranted.value = 'NO';
                    toggleCourtOrderFile('NO');
                }
            }

            // Render saved Court Order Documents if any exist for this suspect/case
            renderSavedCourtOrderFiles(data.court_order_files || []);
            if (data.extraction_status !== undefined) {
                const curStatus = document.getElementById('extractionAnalysisConducted');
                if (curStatus) curStatus.value = data.extraction_status || 'NO';
            }
            if (data.ongoing_extraction_status !== undefined) {
                const ongStatus = document.getElementById('ongoingExtractionAnalysisConducted');
                if (ongStatus) ongStatus.value = data.ongoing_extraction_status || 'NO';
            }
            if (data.extraction_notes !== undefined) {
                const curNotes = document.getElementById('extractionAnalysisNotes');
                if (curNotes) curNotes.value = data.extraction_notes || '';
            }
            if (data.ongoing_extraction_notes !== undefined) {
                const ongNotes = document.getElementById('ongoingExtractionAnalysisNotes');
                if (ongNotes) ongNotes.value = data.ongoing_extraction_notes || '';
            }
            if (data.report_witness_status !== undefined) {
                const rwStatus = document.getElementById('reportWitnessSent');
                if (rwStatus) {
                    rwStatus.value = data.report_witness_status || 'NO';
                    toggleReportWitnessFile(rwStatus.value);
                }
            }
            // Render saved Report & Witness Statement Documents if any exist for this suspect/case
            renderSavedReportWitnessFiles(data.report_witness_files || []);

            if (data.report_witness_notes !== undefined) {
                const rwNotes = document.getElementById('reportWitnessNotes');
                if (rwNotes) rwNotes.value = data.report_witness_notes;
            }
            if (data.intel_shared !== undefined) {
                const intel = document.getElementById('intelShared');
                if (intel) intel.value = data.intel_shared || 'NO';
            }
            if (data.remark !== undefined) {
                const rem = document.getElementById('statusRemark');
                if (rem) rem.value = data.remark;
            }

            updateSelectedSuspectBanner(data);
        }

        function updateSelectedSuspectBanner(data) {
            if (!data) return;
            const nameEl = document.getElementById('selectedSuspectNameLabel');
            const aliasEl = document.getElementById('selectedSuspectAliasLabel');
            const thumbWrap = document.getElementById('selectedSuspectThumbWrap');
            if (nameEl) nameEl.textContent = data.suspect_name || 'Unknown Suspect';
            if (aliasEl) {
                if (data.suspect_alias && String(data.suspect_alias).toUpperCase() !== 'N/A') {
                    aliasEl.textContent = `(${data.suspect_alias})`;
                    aliasEl.classList.remove('hidden');
                } else {
                    aliasEl.textContent = '';
                    aliasEl.classList.add('hidden');
                }
            }
            if (thumbWrap) {
                if (data.suspect_mug) {
                    const safeMug = String(data.suspect_mug).replace(/"/g, '&quot;');
                    const safeName = String(data.suspect_name || 'Suspect').replace(/"/g, '&quot;').replace(/'/g, "\\'");
                    thumbWrap.innerHTML = `<img id="selectedSuspectThumbImg" src="${safeMug}" alt="${safeName}" class="w-full h-full object-cover cursor-pointer" onclick="openMugshotModal('${safeMug.replace(/'/g, "\\'")}', '${safeName}')">`;
                } else {
                    thumbWrap.innerHTML = `<svg id="selectedSuspectFallbackIcon" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;
                }
            }
        }

        function selectCaseInStatusForm(caseRef) {
            if (!caseRef) return;
            const selectEl = document.getElementById('statusCaseRef');
            if (!selectEl) return;
            const targetLower = String(caseRef).trim().toLowerCase();
            let matchedVal = null;
            Array.from(selectEl.options).forEach(opt => {
                const val = String(opt.value || '').trim();
                const baseCase = val.includes('|') ? val.split('|')[0] : val;
                if (!matchedVal && (val.toLowerCase() === targetLower || baseCase.toLowerCase() === targetLower)) {
                    matchedVal = val;
                }
            });
            if (matchedVal) {
                selectEl.value = matchedVal;
                onDigitalCaseRefChanged(matchedVal);
                document.getElementById('statusWorkbenchSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function openSelectedCasePdfPreview() {
            const selectEl = document.getElementById('statusCaseRef');
            const rawVal = selectEl ? String(selectEl.value || '').trim() : '';
            const caseNo = rawVal.includes('|') ? rawVal.split('|')[0] : rawVal;
            if (!caseNo) return;
            window.location.href = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(caseNo);
        }

        function quickFilterDirectory(courtVal, extVal) {
            const courtSel = document.getElementById('directoryCourtFilter');
            const extSel = document.getElementById('directoryExtractionFilter');
            if (courtSel) courtSel.value = courtVal || '';
            if (extSel) extSel.value = extVal || '';
            applyDirectoryFilters();
            document.getElementById('investigationDirectorySection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function recomputeDirectoryKpis() {
            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            const total = rows.length;
            let warrants = 0;
            let extracted = 0;
            let intel = 0;
            rows.forEach(r => {
                const c = (r.getAttribute('data-court') || '').toUpperCase();
                const e = (r.getAttribute('data-extraction') || '').toUpperCase();
                const i = (r.getAttribute('data-intel') || '').toUpperCase();
                if (c.includes('YES') || c.includes('GRANT')) warrants++;
                if (e.startsWith('YES') || e.includes('COMPLET')) extracted++;
                if (i.includes('YES')) intel++;
            });
            const pending = Math.max(0, total - extracted);
            const rate = total > 0 ? Math.round((extracted / total) * 100) : 0;

            const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = String(v); };
            setTxt('kpiStatTotal', total);
            setTxt('kpiStatWarrants', warrants);
            setTxt('kpiStatExtracted', extracted);
            setTxt('kpiStatPending', pending);
            setTxt('kpiStatIntelSub', intel);
            setTxt('kpiStatRateBadge', rate + '%');
            const bar = document.getElementById('kpiStatRateBar');
            if (bar) bar.style.width = rate + '%';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectEl = document.getElementById('statusCaseRef');
            const queryCase = new URLSearchParams(window.location.search).get('case');
            if (queryCase && selectEl) {
                selectCaseInStatusForm(queryCase);
            } else if (selectEl && selectEl.value) {
                onDigitalCaseRefChanged(selectEl.value);
            } else {
                const coReqEl = document.getElementById('courtOrderRequested');
                handleCourtOrderRequestedChange(coReqEl ? coReqEl.value : 'NO');
                const courtGrantedEl = document.getElementById('courtOrderGranted');
                if (courtGrantedEl) {
                    toggleCourtOrderFile(courtGrantedEl.value);
                }
            }

            const reportWitnessEl = document.getElementById('reportWitnessSent');
            if (reportWitnessEl) {
                toggleReportWitnessFile(reportWitnessEl.value);
            }

            const form = document.getElementById('forensicStatusForm');
            if (form) {
                form.addEventListener('reset', function() {
                    setTimeout(() => {
                        const coReqEl = document.getElementById('courtOrderRequested');
                        handleCourtOrderRequestedChange(coReqEl ? coReqEl.value : 'NO');
                        const courtGrantedEl = document.getElementById('courtOrderGranted');
                        if (courtGrantedEl) toggleCourtOrderFile(courtGrantedEl.value);
                        if (reportWitnessEl) toggleReportWitnessFile(reportWitnessEl.value);
                        renderSavedCourtOrderFiles([]);
                        renderSavedReportWitnessFiles([]);
                    }, 50);
                });
            }

            applyDirectoryFilters();
            recomputeDirectoryKpis();
        });

        function handleStatusSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('saveStatusBtn');
            const alertMsg = document.getElementById('statusAlertMsg');
            const form = document.getElementById('forensicStatusForm');
            const formData = new FormData(form);

            // Split composite case_number (case_number|suspect_id)
            const rawCaseVal = (formData.get('case_number') || '').trim();
            if (rawCaseVal.includes('|')) {
                const parts = rawCaseVal.split('|');
                formData.set('case_number', parts[0]);
                if (parts[1]) {
                    formData.set('suspect_id', parts[1]);
                }
            }

            // Ensure disabled or present select values are captured accurately
            const courtGrantedEl = document.getElementById('courtOrderGranted');
            if (courtGrantedEl) {
                formData.set('court_order_granted', courtGrantedEl.value || 'NO');
            }
            const courtReqEl = document.getElementById('courtOrderRequested');
            if (courtReqEl) {
                formData.set('court_order_requested', courtReqEl.value || 'NO');
            }

            // Compute combined extraction status & message
            const courtGranted = (formData.get('court_order_granted') || '').trim();
            let extStatus = '';
            let extNotes = '';

            if (courtGranted === 'YES') {
                const ongStatusEl = document.getElementById('ongoingExtractionAnalysisConducted');
                const ongNotesEl = document.getElementById('ongoingExtractionAnalysisNotes');
                extStatus = ongStatusEl ? ongStatusEl.value.trim() : '';
                extNotes = ongNotesEl ? ongNotesEl.value.trim() : '';
            }
            if (!extStatus) {
                const curStatusEl = document.getElementById('extractionAnalysisConducted');
                extStatus = curStatusEl ? curStatusEl.value.trim() : (formData.get('extraction_analysis_status') || formData.get('ongoing_extraction_analysis_status') || '').trim();
            }
            if (!extNotes) {
                const curNotesEl = document.getElementById('extractionAnalysisNotes');
                extNotes = curNotesEl ? curNotesEl.value.trim() : (formData.get('extraction_analysis_notes') || formData.get('ongoing_extraction_analysis_notes') || '').trim();
            }

            let cleanExtNotes = extNotes;
            if (extStatus) {
                const prefixRegex = new RegExp('^' + extStatus + '\\s*[-–—:]\\s*', 'i');
                cleanExtNotes = cleanExtNotes.replace(prefixRegex, '').trim();
                if (cleanExtNotes.toUpperCase() === extStatus.toUpperCase()) {
                    cleanExtNotes = '';
                }
            }
            if (/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*\1$/i.test(cleanExtNotes) || ['N/A', 'NONE', 'NO', 'YES'].includes(cleanExtNotes.toUpperCase())) {
                cleanExtNotes = '';
            }

            let combinedExt = '';
            if (extStatus && cleanExtNotes) {
                combinedExt = extStatus + ' - ' + cleanExtNotes;
            } else if (cleanExtNotes) {
                combinedExt = cleanExtNotes;
            } else if (extStatus) {
                combinedExt = extStatus;
            } else {
                combinedExt = 'NO';
            }
            formData.set('extraction_analysis_status', extStatus);
            formData.set('extraction_analysis_notes', cleanExtNotes);
            formData.set('extraction_analysis_conducted', combinedExt);
            formData.set('extraction_analysis_message', combinedExt);

            // Compute combined report witness status & message
            const rwStatus = (formData.get('report_witness_statement_status') || '').trim();
            const rwNotes = (formData.get('report_witness_statement_notes') || '').trim();
            let cleanRwNotes = rwNotes;
            if (rwStatus) {
                const prefixRegex = new RegExp('^' + rwStatus + '\\s*[-–—:]\\s*', 'i');
                cleanRwNotes = cleanRwNotes.replace(prefixRegex, '').trim();
                if (cleanRwNotes.toUpperCase() === rwStatus.toUpperCase()) {
                    cleanRwNotes = '';
                }
            }
            if (/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*\1$/i.test(cleanRwNotes) || ['N/A', 'NONE', 'NO', 'YES'].includes(cleanRwNotes.toUpperCase())) {
                cleanRwNotes = '';
            }

            let combinedRw = '';
            if (rwStatus && cleanRwNotes) {
                combinedRw = rwStatus + ' - ' + cleanRwNotes;
            } else if (cleanRwNotes) {
                combinedRw = cleanRwNotes;
            } else if (rwStatus) {
                combinedRw = rwStatus;
            } else {
                combinedRw = 'NO';
            }
            formData.set('report_witness_statement_status', rwStatus);
            formData.set('report_witness_statement_notes', cleanRwNotes);
            formData.set('report_witness_statement_sent', combinedRw);
            formData.set('report_witness_statement_message', combinedRw);

            if (courtGranted !== 'YES') {
                if (!formData.get('intel_shared')) {
                    formData.set('intel_shared', 'NO');
                }
            }

            const caseSectionLabel = (courtGranted === 'YES')
                ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)'
                : 'CURRENT/NEW CASES';
            formData.set('case_section_label', caseSectionLabel);

            // Explicitly sync all accumulated files from the DataTransfer pools
            formData.delete('court_order_files[]');
            formData.delete('court_order_file[]');
            Array.from(courtOrderDataTransfer.files).forEach(file => {
                formData.append('court_order_files[]', file);
            });

            formData.delete('report_witness_statement_files[]');
            formData.delete('report_witness_statement_file[]');
            Array.from(reportWitnessDataTransfer.files).forEach(file => {
                formData.append('report_witness_statement_files[]', file);
            });

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent mr-1"></span> Saving...';
            }

            fetch('{{ url("/Digital_Forensics/forensic-status") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span>Status Saved!</span>';
                }
                if (alertMsg) {
                    alertMsg.classList.remove('hidden');
                    alertMsg.classList.add('flex');
                }

                // Sync updated court order files into caseMapData and saved files list
                if (data.court_order_files && Array.isArray(data.court_order_files)) {
                    const rawCaseVal = (formData.get('case_number') || '').trim();
                    const targetKeys = [rawCaseVal];
                    if (rawCaseVal.includes('|')) {
                        targetKeys.push(rawCaseVal.split('|')[0]);
                    }
                    const formattedNewFiles = data.court_order_files.map(fPath => {
                        const rawName = fPath.split('/').pop() || 'Court Order Document';
                        const cleanName = rawName.replace(/^\d+_[a-z0-9]+_court_order_/i, '').replace(/^\d+_court_order_/i, '').replace(/^court_order_/i, '');
                        const ext = (cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        const url = (fPath.startsWith('http://') || fPath.startsWith('https://')) ? fPath : ('/' + fPath.replace(/^\/+/, ''));
                        return { path: fPath, name: cleanName, raw_name: rawName, ext: ext, url: url };
                    });
                    targetKeys.forEach(k => {
                        if (caseMapData[k]) {
                            caseMapData[k].court_order_files = formattedNewFiles;
                        }
                    });
                    courtOrderDataTransfer = new DataTransfer();
                    const fileInput = document.getElementById('courtOrderFileInput');
                    if (fileInput) fileInput.value = '';
                    renderFileList('court');
                    renderSavedCourtOrderFiles(formattedNewFiles);
                }

                // Sync updated report witness statement files into caseMapData and saved files list
                if (data.report_witness_statement_files && Array.isArray(data.report_witness_statement_files)) {
                    const rawCaseVal = (formData.get('case_number') || '').trim();
                    const targetKeys = [rawCaseVal];
                    if (rawCaseVal.includes('|')) {
                        targetKeys.push(rawCaseVal.split('|')[0]);
                    }
                    const formattedNewRwFiles = data.report_witness_statement_files.map(fPath => {
                        const rawName = fPath.split('/').pop() || 'Statement / Report Document';
                        const cleanName = rawName.replace(/^\d+_[a-z0-9]+_report_witness_/i, '').replace(/^\d+_report_witness_/i, '').replace(/^report_witness_/i, '');
                        const ext = (cleanName.split('.').pop() || 'DOC').toUpperCase().slice(0, 4);
                        const url = (fPath.startsWith('http://') || fPath.startsWith('https://')) ? fPath : ('/' + fPath.replace(/^\/+/, ''));
                        return { path: fPath, name: cleanName, raw_name: rawName, ext: ext, url: url };
                    });
                    targetKeys.forEach(k => {
                        if (caseMapData[k]) {
                            caseMapData[k].report_witness_files = formattedNewRwFiles;
                        }
                    });
                    reportWitnessDataTransfer = new DataTransfer();
                    const rwFileInput = document.getElementById('reportWitnessFileInput');
                    if (rwFileInput) rwFileInput.value = '';
                    renderFileList('witness');
                    renderSavedReportWitnessFiles(formattedNewRwFiles);
                }

                const repData = data.data || data.report || {
                    case_number: formData.get('case_number'),
                    location_post: formData.get('location_post'),
                    investigation_date: formData.get('investigation_date'),
                    devices_retrieved: formData.get('devices_retrieved'),
                    quantity: formData.get('quantity'),
                    brief_case_description: formData.get('brief_case_description'),
                    court_order_requested: formData.get('court_order_requested'),
                    court_order_granted: formData.get('court_order_granted'),
                    extraction_analysis_conducted: combinedExt,
                    extraction_analysis_message: combinedExt,
                    report_witness_statement_sent: combinedRw,
                    report_witness_statement_message: combinedRw,
                    intel_shared: formData.get('intel_shared'),
                    remark: formData.get('remark'),
                    dpf_code: 'DF-STATUS-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random()*900 + 100)
                };

                updateDirectoryTableWithNewReport(repData);
                openReportPreviewModal(repData);
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span>Save &amp; Log Investigation Status</span>';
                }

                const fallbackData = {
                    case_number: formData.get('case_number'),
                    location_post: formData.get('location_post'),
                    investigation_date: formData.get('investigation_date'),
                    devices_retrieved: formData.get('devices_retrieved'),
                    quantity: formData.get('quantity'),
                    brief_case_description: formData.get('brief_case_description'),
                    court_order_requested: formData.get('court_order_requested'),
                    court_order_granted: formData.get('court_order_granted'),
                    case_section_label: caseSectionLabel,
                    extraction_analysis_conducted: combinedExt,
                    extraction_analysis_message: combinedExt,
                    report_witness_statement_sent: combinedRw,
                    report_witness_statement_message: combinedRw,
                    intel_shared: formData.get('intel_shared'),
                    remark: formData.get('remark'),
                    dpf_code: 'DF-STATUS-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random()*900 + 100)
                };
                updateDirectoryTableWithNewReport(fallbackData);
                openReportPreviewModal(fallbackData);
            });
        }

        function openReportPreviewModal(r) {
            if (!r) return;
            const ref = r.dpf_code || r.case_number || r.id || '';
            window.location.href = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(ref);
        }

        function closeReportPreviewModal() {
            // Deprecated - standalone page model active
        }

        function printReportPreview() {
            window.print();
        }

        function updateDirectoryTableWithNewReport(r) {
            const tbody = document.querySelector('#forensicDirectoryTable tbody');
            if (!tbody) return;

            function getMultiBadgesHtml(val, fileArr, baseLabel) {
                const raw = (val || 'NO').trim();
                const match = raw.match(/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*([\s\S]+)$/i);
                let badge = null;
                let note = null;
                if (match) {
                    badge = match[1].toUpperCase();
                    const candidate = match[2].trim();
                    if (candidate.toUpperCase() !== badge && !['N/A', 'NONE', 'NO', 'YES'].includes(candidate.toUpperCase())) {
                        note = candidate;
                    }
                } else if (['YES', 'NO', 'IN PROGRESS', 'PENDING'].includes(raw.toUpperCase())) {
                    badge = raw.toUpperCase();
                } else {
                    note = raw;
                }

                let bgClass = 'bg-rose-100 text-rose-800';
                if (badge === 'YES') bgClass = 'bg-emerald-100 text-emerald-800';
                else if (badge === 'IN PROGRESS' || badge === 'PENDING') bgClass = 'bg-amber-100 text-amber-800';

                let html = '';
                if (badge) {
                    html += `<span class="px-2 py-0.5 ${bgClass} text-[10px] font-bold rounded">${badge}</span>`;
                }
                if (note) {
                    const escaped = note.replace(/"/g, '&quot;');
                    html += `<p class="${badge ? 'mt-1 ' : ''}text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="${escaped}">${note}</p>`;
                }
                if (!html) {
                    html = `<span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold rounded">NO</span>`;
                }
                if (fileArr && Array.isArray(fileArr) && fileArr.length > 0) {
                    html += `<div class="mt-1 flex flex-wrap items-center gap-1 max-w-[200px]">`;
                    fileArr.forEach((filePath, idx) => {
                        const fullUrl = filePath.startsWith('http') || filePath.startsWith('/') ? filePath : '{{ asset("") }}' + filePath;
                        const label = baseLabel + (fileArr.length > 1 ? ' #' + (idx + 1) : '');
                        html += `
                            <a href="${fullUrl}" target="_blank" class="px-1.5 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>${label}</span>
                            </a>
                        `;
                    });
                    html += `</div>`;
                }
                return html;
            }

            function getExtractionBadgeHtml(val) {
                const raw = (val || 'NO').trim();
                const match = raw.match(/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*([\s\S]+)$/i);
                let badge = null;
                let note = null;
                if (match) {
                    badge = match[1].toUpperCase();
                    const candidate = match[2].trim();
                    if (candidate.toUpperCase() !== badge && !['N/A', 'NONE', 'NO', 'YES'].includes(candidate.toUpperCase())) {
                        note = candidate;
                    }
                } else if (['YES', 'NO', 'IN PROGRESS', 'PENDING'].includes(raw.toUpperCase())) {
                    badge = raw.toUpperCase();
                } else {
                    note = raw;
                }

                let badgeClass = 'bg-rose-100 text-rose-800';
                if (badge === 'YES') badgeClass = 'bg-emerald-100 text-emerald-800';
                else if (badge === 'IN PROGRESS' || badge === 'PENDING') badgeClass = 'bg-amber-100 text-amber-800';

                let html = '';
                if (badge) {
                    html += `<span class="px-2 py-0.5 ${badgeClass} text-[10px] font-bold rounded">${badge}</span>`;
                }
                if (note) {
                    const escaped = note.replace(/"/g, '&quot;');
                    html += `<p class="${badge ? 'mt-1 ' : ''}text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="${escaped}">${note}</p>`;
                }
                return html || `<span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold rounded">NO</span>`;
            }

            // Safely locate if an existing row for this case already exists in the directory table
            const targetCaseNo = (r.case_number || '').trim().toLowerCase();
            let tr = null;
            const directoryRows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            directoryRows.forEach(row => {
                const rowCase = (row.getAttribute('data-case') || '').trim().toLowerCase();
                if (rowCase && rowCase === targetCaseNo) {
                    tr = row;
                }
            });

            const isNewRow = !tr;
            if (isNewRow) {
                tr = document.createElement('tr');
            }
            tr.className = 'hover:bg-slate-50/90 transition-colors directory-row';
            
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const todayIso = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const todayDisplay = `${pad(now.getDate())} ${months[now.getMonth()]} ${now.getFullYear()}`;
            const timeDisplay = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const sInfo = (caseMapData && r.case_number && caseMapData[r.case_number]) ? caseMapData[r.case_number] : {};
            const suspectName = r.suspect_name || sInfo.suspect_name || 'Unknown Suspect';
            const suspectAlias = r.suspect_alias || sInfo.suspect_alias || '';
            const suspectMug = r.mugshot_front || sInfo.suspect_mug || null;
            const coCount = (r.co_count !== undefined) ? r.co_count : (sInfo.co_count || 0);

            const repDate = (r.investigation_date ? String(r.investigation_date).slice(0, 10) : todayIso);
            tr.setAttribute('data-date', repDate);
            tr.setAttribute('data-case', (r.case_number || '').toLowerCase());
            tr.setAttribute('data-caseref', r.case_number || '');
            tr.setAttribute('data-code', (r.dpf_code || '').toLowerCase());
            tr.setAttribute('data-location', (r.location_post || '').toLowerCase());
            tr.setAttribute('data-suspect', (suspectName + ' ' + suspectAlias).toLowerCase());
            tr.setAttribute('data-court', (r.court_order_granted || 'NO').toUpperCase());
            tr.setAttribute('data-extraction', (r.extraction_analysis_conducted || r.extraction_analysis_message || 'NO').toUpperCase());
            tr.setAttribute('data-intel', (r.intel_shared || 'NO').toUpperCase());

            const pdfUrl = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(r.dpf_code || r.case_number || '');

            let courtFiles = [];
            if (Array.isArray(r.court_order_files)) {
                courtFiles = r.court_order_files;
            } else if (r.court_order_file) {
                try {
                    const parsed = JSON.parse(r.court_order_file);
                    courtFiles = Array.isArray(parsed) ? parsed : [r.court_order_file];
                } catch(e) {
                    courtFiles = [r.court_order_file];
                }
            }

            let reportFiles = [];
            if (Array.isArray(r.report_witness_statement_files)) {
                reportFiles = r.report_witness_statement_files;
            } else if (r.report_witness_statement_file) {
                try {
                    const parsed = JSON.parse(r.report_witness_statement_file);
                    reportFiles = Array.isArray(parsed) ? parsed : [r.report_witness_statement_file];
                } catch(e) {
                    reportFiles = [r.report_witness_statement_file];
                }
            }

            const escapedSuspectName = suspectName.replace(/"/g, '&quot;');
            const escapedJsName = suspectName.replace(/'/g, "\\'");
            const escapedJsMug = suspectMug ? suspectMug.replace(/'/g, "\\'") : '';

            const escapedCaseJs = String(r.case_number || '').replace(/'/g, "\\'");

            tr.innerHTML = `
                <td class="p-3.5 pl-5 align-top font-extrabold text-emerald-800">
                    <div>${r.case_number}</div>
                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                        ${r.dpf_code ? `<span class="text-[9.5px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded">${r.dpf_code}</span>` : ''}
                        ${r.location_post ? `<span class="text-[10px] text-slate-400 font-sans font-normal truncate max-w-[140px]" title="${r.location_post}">${r.location_post}</span>` : ''}
                    </div>
                </td>
                <td class="p-3.5 align-top min-w-[180px]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-11 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs flex items-center justify-center relative">
                            ${suspectMug ? `
                                <img src="${suspectMug}" alt="${escapedSuspectName}" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="openMugshotModal('${escapedJsMug}', '${escapedJsName}')" title="Click to enlarge photo">
                            ` : `
                                <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400" title="No front mugshot on file">
                                    <svg class="w-4.5 h-4.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            `}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-[11px] text-slate-900 leading-tight uppercase font-mono-code truncate max-w-[145px]" title="${escapedSuspectName}">
                                ${suspectName}
                            </div>
                            ${suspectAlias ? `<div class="text-[10px] text-slate-500 font-sans italic truncate max-w-[145px]">a.k.a. ${suspectAlias}</div>` : ''}
                            ${coCount > 0 ? `<span class="inline-block px-1.5 py-0.2 mt-0.5 rounded text-[8.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200" title="${coCount} Co-Suspect(s) in case">+${coCount} Co-Suspect${coCount > 1 ? 's' : ''}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td class="p-3.5 align-top whitespace-nowrap">
                    <div class="font-bold text-[11px] text-slate-800">${todayDisplay}</div>
                    <span class="text-[10px] text-slate-400 font-sans block mt-0.5">${timeDisplay}</span>
                </td>
                <td class="p-3.5 align-top">${getMultiBadgesHtml(r.court_order_granted, courtFiles, 'Order')}</td>
                <td class="p-3.5 align-top">${getExtractionBadgeHtml(r.extraction_analysis_message || r.extraction_analysis_conducted)}</td>
                <td class="p-3.5 align-top">${getMultiBadgesHtml(r.report_witness_statement_message || r.report_witness_statement_sent, reportFiles, 'Evidence / Stmt')}</td>
                <td class="p-3.5 align-top">${getMultiBadgesHtml(r.intel_shared)}</td>
                <td class="p-3.5 pr-5 align-top text-right whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-1.5">
                        <button type="button" onclick="selectCaseInStatusForm('${escapedCaseJs}')" class="rep-btn-green-soft px-2.5 py-1 rounded-lg text-[10.5px] font-bold cursor-pointer">
                            Edit
                        </button>
                        <a href="${pdfUrl}" class="rep-btn-green px-2.5 py-1 rounded-lg text-[10.5px] font-bold no-underline inline-block">
                            Preview
                        </a>
                    </div>
                </td>
            `;

            if (isNewRow) {
                tbody.prepend(tr);
            } else {
                // Flash updated row briefly so user knows it was updated in place
                tr.classList.add('bg-emerald-100/50');
                setTimeout(() => {
                    tr.classList.remove('bg-emerald-100/50');
                }, 1000);
            }

            // Sync updated values into caseMapData in memory
            if (caseMapData && r.case_number && caseMapData[r.case_number]) {
                const mapEntry = caseMapData[r.case_number];
                if (r.court_order_requested) mapEntry.court_order_requested = r.court_order_requested;
                if (r.court_order_granted) mapEntry.court_order_granted = r.court_order_granted;
                if (r.report_data && r.report_data.extraction_analysis_status !== undefined) {
                    mapEntry.extraction_status = r.report_data.extraction_analysis_status;
                }
                if (r.report_data && r.report_data.extraction_analysis_notes !== undefined) {
                    mapEntry.extraction_notes = r.report_data.extraction_analysis_notes;
                }
                if (r.report_data && r.report_data.ongoing_extraction_analysis_status !== undefined) {
                    mapEntry.ongoing_extraction_status = r.report_data.ongoing_extraction_analysis_status;
                }
                if (r.report_data && r.report_data.ongoing_extraction_analysis_notes !== undefined) {
                    mapEntry.ongoing_extraction_notes = r.report_data.ongoing_extraction_analysis_notes;
                }
                if (r.report_data && r.report_data.report_witness_statement_status !== undefined) {
                    mapEntry.report_witness_status = r.report_data.report_witness_statement_status;
                }
                if (r.report_data && r.report_data.report_witness_statement_notes !== undefined) {
                    mapEntry.report_witness_notes = r.report_data.report_witness_statement_notes;
                }
                if (r.intel_shared) mapEntry.intel_shared = r.intel_shared;
                if (r.remark !== undefined) mapEntry.remark = r.remark;
            }

            applyDirectoryFilters();
            recomputeDirectoryKpis();
        }

        let currentPreset = 'all';

        function getDirectoryReferenceDate() {
            const now = new Date();
            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            let maxDateStr = null;
            rows.forEach(r => {
                const d = (r.getAttribute('data-date') || '').trim().slice(0, 10);
                if (d && (!maxDateStr || d > maxDateStr)) {
                    maxDateStr = d;
                }
            });

            if (maxDateStr) {
                const parts = maxDateStr.split('-');
                if (parts.length === 3) {
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    const d = parseInt(parts[2], 10);
                    if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                        const maxD = new Date(y, m, d);
                        return (now >= maxD) ? now : maxD;
                    }
                }
            }
            return now;
        }

        function updatePresetButtonsVisual(activePreset) {
            const presets = ['all', 'weekly', 'monthly', 'annually'];
            presets.forEach(p => {
                const btn = document.getElementById('btnPreset' + p.charAt(0).toUpperCase() + p.slice(1));
                if (!btn) return;
                if (p === activePreset) {
                    btn.className = 'rep-btn-green px-3 py-1 rounded-lg text-xs font-mono-code font-bold cursor-pointer';
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                } else {
                    btn.className = 'px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer';
                    btn.style.backgroundColor = '#ffffff';
                    btn.style.color = '#334155';
                }
            });
        }

        function setDateFilterPreset(preset) {
            currentPreset = preset;
            updatePresetButtonsVisual(preset);

            const fromInput = document.getElementById('dateFilterFrom');
            const toInput = document.getElementById('dateFilterTo');

            const refDate = getDirectoryReferenceDate();
            const pad = n => String(n).padStart(2, '0');
            const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (preset === 'all') {
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            } else if (preset === 'weekly') {
                const day = refDate.getDay();
                const diffToMon = (day === 0 ? -6 : 1) - day;
                const monday = new Date(refDate);
                monday.setDate(refDate.getDate() + diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);

                if (fromInput) fromInput.value = formatDate(monday);
                if (toInput) toInput.value = formatDate(sunday);
            } else if (preset === 'monthly') {
                const firstDay = new Date(refDate.getFullYear(), refDate.getMonth(), 1);
                const lastDay = new Date(refDate.getFullYear(), refDate.getMonth() + 1, 0);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            } else if (preset === 'annually') {
                const firstDay = new Date(refDate.getFullYear(), 0, 1);
                const lastDay = new Date(refDate.getFullYear(), 11, 31);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            }

            applyDirectoryFilters();
            recomputeDirectoryKpis();
        }

        function onCustomDateChange() {
            currentPreset = 'custom';
            updatePresetButtonsVisual('custom');
            applyDirectoryFilters();
            recomputeDirectoryKpis();
        }

        function generateDirectoryFilter() {
            const btn = document.getElementById('btnGenerateFilter');
            const table = document.getElementById('forensicDirectoryTable');
            applyDirectoryFilters();

            const visibleRows = Array.from(document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row'))
                .filter(r => r.style.display !== 'none');

            if (visibleRows.length === 0) {
                if (typeof showStatusNotification === 'function') {
                    showStatusNotification('No suspects/cases found matching the selected filters.', 'warning');
                } else {
                    alert('No suspects/cases found matching the selected filters.');
                }
                return;
            }

            const caseList = [];
            visibleRows.forEach(r => {
                const cRef = (r.getAttribute('data-caseref') || r.getAttribute('data-case') || '').trim();
                if (cRef && !caseList.includes(cRef)) {
                    caseList.push(cRef);
                }
            });

            if (caseList.length === 0) {
                if (typeof showStatusNotification === 'function') {
                    showStatusNotification('No case references found in the filtered rows.', 'warning');
                }
                return;
            }

            if (table) {
                table.classList.add('transition-opacity', 'opacity-70');
                setTimeout(() => {
                    table.classList.remove('opacity-70');
                }, 200);
            }

            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Generating...</span>
                `;
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Printed!</span>
                    `;
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 1200);
                }, 400);
            }

            const params = new URLSearchParams();
            params.set('cases', caseList.join(','));
            params.set('print', '1');

            const fromVal = (document.getElementById('dateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('dateFilterTo')?.value || '').trim();
            const courtVal = (document.getElementById('directoryCourtFilter')?.value || '').trim();
            const extVal = (document.getElementById('directoryExtractionFilter')?.value || '').trim();
            const searchVal = (document.getElementById('directorySearchInput')?.value || '').trim();

            if (fromVal) params.set('from', fromVal);
            if (toVal) params.set('to', toVal);
            if (courtVal) params.set('court', courtVal);
            if (extVal) params.set('extraction', extVal);
            if (searchVal) params.set('search', searchVal);

            // Determine filter period (weekly, monthly, annual, date_range, or general)
            let periodParam = 'general';
            if (currentPreset === 'weekly') {
                periodParam = 'weekly';
            } else if (currentPreset === 'monthly') {
                periodParam = 'monthly';
            } else if (currentPreset === 'annually' || currentPreset === 'annual') {
                periodParam = 'annual';
            } else if (fromVal || toVal || currentPreset === 'custom') {
                periodParam = 'date_range';
            } else {
                periodParam = 'general';
            }
            params.set('period', periodParam);

            const targetUrl = '{{ url("/Digital_Forensics/multi_dpf_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function resetDateFilters() {
            const searchInput = document.getElementById('directorySearchInput');
            if (searchInput) searchInput.value = '';

            const courtFilter = document.getElementById('directoryCourtFilter');
            if (courtFilter) courtFilter.value = '';

            const extFilter = document.getElementById('directoryExtractionFilter');
            if (extFilter) extFilter.value = '';

            setDateFilterPreset('all');
        }

        let currentDirPage = 1;
        let dirItemsPerPage = 15;

        function goToDirectoryPage(page) {
            currentDirPage = page;
            applyDirectoryFilters(false);
        }

        function changeDirectoryPerPage(val) {
            dirItemsPerPage = parseInt(val, 10) || 15;
            currentDirPage = 1;
            applyDirectoryFilters(false);
        }

        function applyDirectoryFilters(resetPage = true) {
            if (resetPage) currentDirPage = 1;
            const searchInput = document.getElementById('directorySearchInput');
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

            const fromVal = (document.getElementById('dateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('dateFilterTo')?.value || '').trim();
            const courtVal = (document.getElementById('directoryCourtFilter')?.value || '').toUpperCase().trim();
            const extVal = (document.getElementById('directoryExtractionFilter')?.value || '').toUpperCase().trim();

            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            const totalCount = rows.length;
            const matchingRows = [];

            rows.forEach(r => {
                const rowDate = (r.getAttribute('data-date') || '').trim().slice(0, 10);
                const rowCase = (r.getAttribute('data-case') || '').toLowerCase();
                const rowCode = (r.getAttribute('data-code') || '').toLowerCase();
                const rowLoc = (r.getAttribute('data-location') || '').toLowerCase();
                const rowSuspect = (r.getAttribute('data-suspect') || '').toLowerCase();
                const rowCourt = (r.getAttribute('data-court') || '').toUpperCase();
                const rowExt = (r.getAttribute('data-extraction') || '').toUpperCase();
                const rowText = r.innerText.toLowerCase();

                // 1. Text Query Search
                let matchesText = true;
                if (query) {
                    matchesText = rowText.includes(query) || 
                                  rowCase.includes(query) || 
                                  rowCode.includes(query) || 
                                  rowLoc.includes(query) ||
                                  rowSuspect.includes(query);
                }

                // 2. Date Range Filter
                let matchesDate = true;
                if (fromVal && rowDate && rowDate < fromVal) {
                    matchesDate = false;
                }
                if (toVal && rowDate && rowDate > toVal) {
                    matchesDate = false;
                }

                // 3. Court Order Filter
                let matchesCourt = true;
                if (courtVal) {
                    if (courtVal === 'YES') {
                        matchesCourt = rowCourt === 'YES' || rowCourt.includes('GRANT');
                    } else if (courtVal === 'NO') {
                        matchesCourt = rowCourt === 'NO' || (!rowCourt.includes('YES') && !rowCourt.includes('GRANT'));
                    }
                }

                // 4. Extraction & Analysis Filter
                let matchesExt = true;
                if (extVal) {
                    if (extVal === 'YES') {
                        matchesExt = rowExt === 'YES' || rowExt.startsWith('YES');
                    } else if (extVal === 'IN PROGRESS') {
                        matchesExt = rowExt.includes('PROGRESS');
                    } else if (extVal === 'NO') {
                        matchesExt = rowExt === 'NO' || rowExt.includes('PENDING') || rowExt.startsWith('NO');
                    }
                }

                if (matchesText && matchesDate && matchesCourt && matchesExt) {
                    matchingRows.push(r);
                } else {
                    r.style.display = 'none';
                }
            });

            const totalMatching = matchingRows.length;
            const totalPages = Math.max(1, Math.ceil(totalMatching / dirItemsPerPage));
            if (currentDirPage > totalPages) currentDirPage = totalPages;
            if (currentDirPage < 1) currentDirPage = 1;

            const startIndex = (currentDirPage - 1) * dirItemsPerPage;
            const endIndex = Math.min(startIndex + dirItemsPerPage, totalMatching);

            matchingRows.forEach((r, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });

            // Update pagination UI
            const rangeEl = document.getElementById('dirPaginationRange');
            if (rangeEl) rangeEl.textContent = totalMatching > 0 ? `${startIndex + 1} - ${endIndex}` : '0 - 0';
            const totalEl = document.getElementById('dirPaginationTotal');
            if (totalEl) totalEl.textContent = totalMatching;

            const prevBtn = document.getElementById('dirPrevPageBtn');
            if (prevBtn) prevBtn.disabled = (currentDirPage <= 1);
            const nextBtn = document.getElementById('dirNextPageBtn');
            if (nextBtn) nextBtn.disabled = (currentDirPage >= totalPages || totalMatching === 0);

            const pageContainer = document.getElementById('dirPageNumbersContainer');
            if (pageContainer) {
                let pagesHtml = '';
                const maxButtons = 5;
                let startPage = Math.max(1, currentDirPage - 2);
                let endPage = Math.min(totalPages, startPage + maxButtons - 1);
                if (endPage - startPage < maxButtons - 1) {
                    startPage = Math.max(1, endPage - maxButtons + 1);
                }
                for (let i = startPage; i <= endPage; i++) {
                    const isActive = (i === currentDirPage);
                    pagesHtml += `<button onclick="goToDirectoryPage(${i})" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all ${isActive ? 'rep-btn-green' : 'rep-btn-green-soft'}">${i}</button>`;
                }
                pageContainer.innerHTML = pagesHtml;
            }

            const noRecordsRow = document.getElementById('directoryNoRecordsRow');
            if (noRecordsRow) {
                noRecordsRow.classList.toggle('hidden', totalMatching > 0);
                if (totalMatching === 0) {
                    const filtersApplied = [];
                    if (query) filtersApplied.push(`Search "${query}"`);
                    if (fromVal || toVal) filtersApplied.push(`Date ${fromVal || 'start'} to ${toVal || 'present'}`);
                    if (courtVal) filtersApplied.push(`Court Order: ${courtVal}`);
                    if (extVal) filtersApplied.push(`Extraction: ${extVal}`);

                    const filterDesc = filtersApplied.length > 0 ? ` (${filtersApplied.join(', ')})` : '';
                    noRecordsRow.innerHTML = `
                        <td colspan="8" class="py-8 text-center text-slate-400 font-semibold font-mono-code">
                            <div class="space-y-2">
                                <p>No matching forensic investigations found for the selected filter criteria${filterDesc}.</p>
                                <button type="button" onclick="resetDateFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 text-xs transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Reset All Filters</span>
                                </button>
                            </div>
                        </td>
                    `;
                }
            }

            const badge = document.getElementById('directoryRowCountBadge');
            if (badge) {
                badge.textContent = `Showing: ${totalMatching} of ${totalCount}`;
            }
        }

        function filterDirectoryTable(query) {
            const searchInput = document.getElementById('directorySearchInput');
            if (searchInput && searchInput.value !== query) {
                searchInput.value = query;
            }
            applyDirectoryFilters();
            recomputeDirectoryKpis();
        }

        // Suspect Mugshot Lightbox / Enlarge Modal Handlers
        function openMugshotModal(imgUrl, suspectName) {
            if (!imgUrl) return;
            const modal = document.getElementById('mugshotModal');
            const img = document.getElementById('modalMugshotImg');
            const nameEl = document.getElementById('modalSuspectName');
            if (img) img.src = imgUrl;
            if (nameEl) nameEl.textContent = suspectName || 'Suspect';
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeMugshotModal() {
            const modal = document.getElementById('mugshotModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function handleMugshotBackdropClick(e) {
            if (e.target && e.target.id === 'mugshotModal') {
                closeMugshotModal();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMugshotModal();
            }
        });
    </script>

    <!-- SUSPECT MUGSHOT ENLARGE PREVIEW MODAL -->
    <div id="mugshotModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="mugshotModalTitle"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-500/50 backdrop-blur-xs transition-opacity duration-200"
         onclick="handleMugshotBackdropClick(event)">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-sm w-full overflow-hidden" onclick="event.stopPropagation()">
            <!-- MODAL HEADER -->
            <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 text-slate-900 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h4 id="mugshotModalTitle" class="font-mono-code font-bold text-xs uppercase tracking-wider text-slate-800">Custodial Front Portrait</h4>
                </div>
                <button type="button" onclick="closeMugshotModal()" class="rep-btn-green-soft p-1.5 rounded-lg cursor-pointer" title="Close Preview">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- MODAL IMAGE BODY -->
            <div class="p-6 flex flex-col items-center bg-slate-50">
                <div class="relative w-56 h-64 rounded-2xl overflow-hidden border-2 border-slate-300 bg-slate-200 shadow-md flex items-center justify-center">
                    <img id="modalMugshotImg" src="" alt="Suspect Mugshot" width="224" height="256" class="w-full h-full object-cover">
                </div>
                <div class="mt-4 text-center">
                    <div id="modalSuspectName" class="font-mono-code font-extrabold text-sm uppercase text-slate-900 tracking-tight"></div>
                    <div class="text-[10px] font-mono-code text-slate-500 uppercase mt-0.5 tracking-wider">NACOC Custodial Photographic Archive</div>
                </div>
            </div>
            <!-- MODAL FOOTER -->
            <div class="px-5 py-3 bg-white border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="closeMugshotModal()" class="rep-btn-green px-4 py-1.5 text-xs font-mono-code font-bold rounded-xl cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</body>
</html>
