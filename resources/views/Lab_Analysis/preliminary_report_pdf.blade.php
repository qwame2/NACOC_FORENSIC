@php
    $authUser = auth()->user();
    $docketData = is_array($case->docket_data)
        ? $case->docket_data
        : (json_decode($case->docket_data ?? '[]', true) ?: []);

    $caseRef = $case->case_number ?? 'Unspecified';

    $isNoSuspectReport = !empty($isNoSuspect)
        || (method_exists($case, 'isNoSuspectCase') && $case->isNoSuspectCase())
        || empty(trim((string) ($suspectDisplayName ?? '')))
        || \App\Models\ForensicCase::isUnassignedSuspectName($suspectDisplayName ?? null);

    if ($isNoSuspectReport) {
        $suspectDisplayName = 'NO SUSPECT(S)';
    }

    // Case Title / Court Name
    $caseTitle = $isNoSuspectReport
        ? 'NO SUSPECT(S)'
        : (!empty($case->title)
            ? $case->title
            : (!empty($suspectDisplayName) && $suspectDisplayName !== 'N/A' && $suspectDisplayName !== 'Unspecified'
                ? ('REPUBLIC VRS ' . $suspectDisplayName)
                : ($case->case_number ?? 'Unspecified')));

    $isValidRefValue = static function ($value): bool {
        $trimmed = trim((string) ($value ?? ''));
        return $trimmed !== '' && !in_array(strtoupper($trimmed), ['N/A', 'NA', 'NONE', 'NULL', '-', 'UNSPECIFIED'], true);
    };

    // Case Reference in Preliminary Exhibit Analysis Report fetches from exhibits.source_of_exhibits first
    $exhibitCollectionSource = collect($drugExhibits ?? [])
        ->merge($exhibits ?? [])
        ->map(fn($item) => is_object($item) ? ($item->source_of_exhibits ?? null) : ($item['source_of_exhibits'] ?? null))
        ->first($isValidRefValue);

    $caseIncidentRef = collect([
        $exhibitSourceOfExhibits ?? null,
        $primaryExhibit->source_of_exhibits ?? null,
        $exhibitCollectionSource,
        $case->source_of_exhibits ?? null,
        $docketData['source_of_exhibits'] ?? null,
        $docketData['seizures'][0]['source_of_exhibits'] ?? null,
        $case->case_number ?? null,
    ])->first($isValidRefValue) ?? 'Unspecified';

    // Format Report Reference No. strictly in NACOC/FSD/LAU/000/2026 format
    $formatReportReference = function ($rawRef, $case) {
        $year = date('Y');
        if (!empty($case->created_at)) {
            try {
                $year = \Carbon\Carbon::parse($case->created_at)->format('Y');
            } catch (\Exception $e) {}
        } elseif (!empty($case->case_number) && preg_match('/(?:19|20)\d{2}/', $case->case_number, $ym)) {
            $year = $ym[0];
        }

        $raw = trim((string)$rawRef);

        // 1. If rawRef matches NACOC/FSD/LAU/{num}/{year}
        if (preg_match('#^NACOC/FSD/LAU/([0-9A-Za-z\-]+)/([0-9]{4})$#i', $raw, $m)) {
            $numPart = $m[1];
            $yrPart = $m[2];
            if (is_numeric($numPart) && (int)$numPart < 1000) {
                $numPart = sprintf('%03d', (int)$numPart);
            }
            return "NACOC/FSD/LAU/{$numPart}/{$yrPart}";
        }

        // 2. If rawRef matches NACOC/FSD/LAU/{num}
        if (preg_match('#^NACOC/FSD/LAU/([0-9A-Za-z\-]+)#i', $raw, $m)) {
            $numPart = $m[1];
            if (is_numeric($numPart) && (int)$numPart < 1000) {
                $numPart = sprintf('%03d', (int)$numPart);
            }
            return "NACOC/FSD/LAU/{$numPart}/{$year}";
        }

        // 3. Check for 4-digit year in rawRef
        if (preg_match('#(19\d{2}|20\d{2})#', $raw, $fullYear)) {
            $year = $fullYear[1];
        }

        // 4. Extract sequence number from rawRef
        $num = null;
        if (!empty($raw) && $raw !== 'N/A') {
            if (preg_match_all('/\d+/', $raw, $numMatches)) {
                foreach (array_reverse($numMatches[0]) as $n) {
                    if ($n !== $year && strlen($n) <= 6) {
                        $num = $n;
                        break;
                    }
                }
            }
        }

        // 5. Fallback: extract sequence from case_number (e.g. NCC-2026-0099 -> 99)
        if (empty($num) && !empty($case->case_number)) {
            if (preg_match_all('/\d+/', $case->case_number, $cMatches)) {
                foreach (array_reverse($cMatches[0]) as $cn) {
                    if ($cn !== $year) {
                        $num = $cn;
                        break;
                    }
                }
            }
        }

        // 6. Fallback: case id
        if (empty($num) && !empty($case->id)) {
            $num = (string)$case->id;
        }

        if (empty($num)) {
            $num = '001';
        }

        if (is_numeric($num) && (int)$num < 1000) {
            $num = sprintf('%03d', (int)$num);
        }

        return "NACOC/FSD/LAU/{$num}/{$year}";
    };

    $allCaseExhibits = collect($drugExhibits ?? [])->merge($exhibits ?? []);
    $allCaseLabResults = collect($labResults ?? [])->when($primaryLabResult ?? null, fn($c) => $c->prepend($primaryLabResult));

    $pickValid = static function (array $candidates) use ($isValidRefValue): ?string {
        foreach ($candidates as $cand) {
            if (is_array($cand)) {
                $joined = implode(', ', array_filter(array_map('trim', $cand), fn($v) => $v !== ''));
                if ($isValidRefValue($joined)) {
                    return $joined;
                }
                continue;
            }
            if ($isValidRefValue($cand)) {
                return trim((string) $cand);
            }
        }
        return null;
    };

    $pickFromCollection = static function ($collection, array $fields) use ($isValidRefValue): ?string {
        foreach (collect($collection) as $item) {
            foreach ($fields as $field) {
                $val = is_object($item) ? ($item->{$field} ?? null) : (is_array($item) ? ($item[$field] ?? null) : null);
                if (is_array($val)) {
                    $val = implode(', ', array_filter(array_map('trim', $val), fn($v) => $v !== ''));
                }
                if ($isValidRefValue($val)) {
                    return trim((string) $val);
                }
            }
        }
        return null;
    };

    $rawReportRef = $pickValid([
        $primaryLabResult->exam_reference ?? null,
        $pickFromCollection($allCaseLabResults, ['exam_reference', 'laboratory_number']),
        $case->exam_reference ?? null,
        $labDataFromDocket['exam_reference'] ?? null,
        $case->laboratory_number ?? null,
    ]);

    $reportRef = $formatReportReference($rawReportRef, $case);

    // Date of Report & Date of Examination
    $rawExamDate = $pickValid([
        $primaryLabResult->date_started ?? null,
        $primaryLabResult->date_completed ?? null,
        $pickFromCollection($allCaseLabResults, ['date_started', 'date_completed', 'created_at']),
        $labDataFromDocket['date_started'] ?? null,
        $primaryLabResult->created_at ?? null,
        $case->created_at ?? null,
    ]) ?? date('Y-m-d');
    try {
        $dateOfReport = \Carbon\Carbon::parse($rawExamDate)->format('d F Y');
        $dateOfPrelimExam = \Carbon\Carbon::parse($rawExamDate)->format('d F Y');
    } catch (\Exception $e) {
        $dateOfReport = (string)$rawExamDate;
        $dateOfPrelimExam = (string)$rawExamDate;
    }

    // Case Name (from source_of_exhibits)
    $caseName = $caseIncidentRef;

    // Source/Requesting Unit
    $sourceRequestingUnit = $pickValid([
        $exhibitSourceRequestingUnit ?? null,
        $primaryExhibit->source_requesting_unit ?? null,
        $pickFromCollection($allCaseExhibits, ['source_requesting_unit']),
        $case->source_requesting_unit ?? null,
        $docketData['source_requesting_unit'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['source_requesting_unit']),
        $case->unit_name ?? null,
    ]) ?? 'Unspecified';

    // Investigating Officer (strictly investigating officer fields; never fall back to arresting or seizure officers)
    $investigatingOfficer = $pickValid([
        $exhibitInvestigatingOfficer ?? null,
        $primaryExhibit->investigating_officer ?? null,
        $pickFromCollection($allCaseExhibits, ['investigating_officer']),
        $case->investigating_officer ?? null,
        $docketData['investigating_officer'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['investigating_officer']),
    ]) ?? 'Unspecified';

    // Date and Time Received
    $dateRecvRaw = $pickValid([
        $primaryLabResult->date_received ?? null,
        $pickFromCollection($allCaseLabResults, ['date_received']),
        $labDataFromDocket['date_received'] ?? null,
        $primaryExhibit->seizure_date ?? null,
        $pickFromCollection($allCaseExhibits, ['seizure_date']),
        $case->date_received ?? null,
        $case->incident_date ?? null,
    ]);
    if (!empty($dateRecvRaw)) {
        try {
            $dateRecvFormatted = \Carbon\Carbon::parse($dateRecvRaw)->format('d F Y');
        } catch (\Exception $e) {
            $dateRecvFormatted = (string)$dateRecvRaw;
        }
    } else {
        $dateRecvFormatted = 'Unspecified';
    }
    $timeRecv = $pickValid([
        $primaryExhibit->seizure_time ?? null,
        $pickFromCollection($allCaseExhibits, ['seizure_time']),
        $case->seizure_time ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['seizure_time']),
    ]) ?? '';
    if (!empty($timeRecv) && $dateRecvFormatted !== 'Unspecified') {
        $cleanTime = str_replace([':', ' '], '', $timeRecv);
        if (strlen($cleanTime) >= 4) {
            $formattedTime = substr($cleanTime, 0, 4) . ' Hours';
        } else {
            $formattedTime = $timeRecv . ' Hours';
        }
        $dateTimeReceived = $dateRecvFormatted . ' ' . $formattedTime;
    } else {
        $dateTimeReceived = $dateRecvFormatted;
    }

    // Purpose of Examination
    $purposeOfExamination = $pickValid([
        $primaryLabResult->purpose_of_examination ?? null,
        $pickFromCollection($allCaseLabResults, ['purpose_of_examination']),
        $case->purpose_of_examination ?? null,
        $labDataFromDocket['purpose_of_examination'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['purpose_of_examination']),
    ]) ?? 'Unspecified';

    // Description of Exhibits
    $descriptionOfExhibits = $pickValid([
        $primaryExhibit->description_of_exhibits ?? null,
        $pickFromCollection($allCaseExhibits, ['description_of_exhibits', 'other_exhibit_description']),
        $case->description_of_exhibits ?? null,
        $docketData['description_of_exhibits'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['description_of_exhibits', 'other_exhibit_description']),
        $primaryExhibit->contents ?? null,
        $pickFromCollection($allCaseExhibits, ['contents', 'seizure_contents']),
    ]) ?? 'Unspecified';

    // Preliminary Examination Procedure
    $prelimExamProcedure = $pickValid([
        $primaryLabResult->preliminary_exam_procedure ?? null,
        $pickFromCollection($allCaseLabResults, ['preliminary_exam_procedure']),
        $case->preliminary_exam_procedure ?? null,
        $labDataFromDocket['preliminary_exam_procedure'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['preliminary_exam_procedure']),
    ]) ?? 'Unspecified';

    // Screening Method/Instrument Used (decode JSON array when multi-method was stored)
    $rawScreeningValue = $pickValid([
        $primaryLabResult->screening_method ?? null,
        $pickFromCollection($allCaseLabResults, ['screening_method']),
        $case->screening_method ?? null,
        $labDataFromDocket['screening_method'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['screening_method']),
    ]);
    $screeningMethod = 'Unspecified';
    if ($rawScreeningValue !== null) {
        $decodedScreening = json_decode($rawScreeningValue, true);
        if (is_array($decodedScreening)) {
            $joinedScreening = implode(', ', array_filter(array_map('trim', $decodedScreening), fn($v) => $v !== ''));
            $screeningMethod = $joinedScreening !== '' ? $joinedScreening : 'Unspecified';
        } else {
            $screeningMethod = $rawScreeningValue;
        }
    }

    // Remarks, Specific Remarks, Disposition
    $remark = $pickValid([
        $primaryLabResult->remark ?? null,
        $pickFromCollection($allCaseLabResults, ['remark']),
        $case->confirmatory_remark ?? null,
        $case->remark ?? null,
        $labDataFromDocket['remark'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['lab_remark', 'confirmatory_remark', 'remark']),
        $pickFromCollection($allCaseExhibits, ['lab_remark']),
    ]) ?? 'Unspecified';

    $specifications = $pickValid([
        $primaryLabResult->specifications ?? null,
        $pickFromCollection($allCaseLabResults, ['specifications']),
        $case->confirmatory_specifications ?? null,
        $case->specifications ?? null,
        $labDataFromDocket['specifications'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['specifications']),
    ]) ?? 'Unspecified';

    $dispositionOfExhibit = $pickValid([
        $primaryLabResult->disposition_of_exhibit ?? null,
        $pickFromCollection($allCaseLabResults, ['disposition_of_exhibit']),
        $case->disposition_of_exhibit ?? null,
        $labDataFromDocket['disposition_of_exhibit'] ?? null,
        $pickFromCollection($docketData['seizures'] ?? [], ['disposition_of_exhibit']),
    ]) ?? 'Unspecified';


    // Helper to resolve the identified drug name for an exhibit
    $resolveDrugName = function ($matchedLab, $drugEx = null, $case = null) {
        $cleanSubstance = function ($val) {
            if (empty($val) || !is_string($val)) {
                return null;
            }
            $val = trim($val);
            if (in_array(strtolower($val), ['positive', 'yes', 'negative', 'no', 'null', 'n/a', 'none', '-'])) {
                return null;
            }
            // Strip trailing "detected"
            $val = preg_replace('/\s+detected$/i', '', $val);
            // Strip leading "Presumptive indication of the presence of" or "Presence of" or "Test #1:"
            $val = preg_replace('/^(presumptive\s+indication\s+of\s+the\s+presence\s+of|presence\s+of|test\s*#?\d*:\s*presence\s+of)\s+/i', '', $val);
            // Strip trailing purity percentages e.g. "(90%)" or "90%"
            $val = preg_replace('/\s*\(\s*\d+%\s*\)/', '', $val);
            $val = preg_replace('/\s*-\s*\d+%.*$/', '', $val);
            $val = trim($val);
            return $val !== '' ? $val : null;
        };

        // 1. From substance_identification
        $subId = is_object($matchedLab) ? ($matchedLab->substance_identification ?? null) : ($matchedLab['substance_identification'] ?? null);
        if ($cleaned = $cleanSubstance($subId)) {
            return $cleaned;
        }

        // 2. From detected_substances (array, JSON, or string)
        $rawObs = is_object($matchedLab) ? ($matchedLab->detected_substances ?? null) : ($matchedLab['detected_substances'] ?? null);
        if (!empty($rawObs)) {
            if (is_string($rawObs)) {
                $decoded = json_decode($rawObs, true);
                if (is_array($decoded)) {
                    $rawObs = $decoded;
                }
            }
            if (is_array($rawObs)) {
                $names = [];
                foreach ($rawObs as $item) {
                    if (is_array($item)) {
                        $n = $item['substance'] ?? ($item['name'] ?? null);
                        if ($cleaned = $cleanSubstance($n)) {
                            $names[] = $cleaned;
                        }
                    } elseif (is_string($item) && ($cleaned = $cleanSubstance($item))) {
                        $names[] = $cleaned;
                    }
                }
                if (!empty($names)) {
                    return implode(', ', array_unique($names));
                }
            } elseif (is_string($rawObs) && ($cleaned = $cleanSubstance($rawObs))) {
                return $cleaned;
            }
        }

        // 3. From presumptive_presence or presumptive_result (e.g. "YES: Test #1: Presence of Tapentadol (90%)")
        $pPresence = is_object($matchedLab) ? ($matchedLab->presumptive_presence ?? null) : ($matchedLab['presumptive_presence'] ?? null);
        if ($pPresence && ($cleaned = $cleanSubstance($pPresence))) {
            return $cleaned;
        }
        $pRes = is_object($matchedLab) ? ($matchedLab->presumptive_result ?? ($matchedLab->presumptive_test_result ?? null)) : ($matchedLab['presumptive_result'] ?? ($matchedLab['presumptive_test_result'] ?? null));
        if ($pRes && is_string($pRes)) {
            if (preg_match('/presence\s+of:?\s+([^(\n,-]+)/i', $pRes, $m)) {
                if ($cleaned = $cleanSubstance($m[1])) {
                    return $cleaned;
                }
            }
        }

        // 4. From drug exhibit explicit field
        if ($drugEx && !empty($drugEx->seizure_name_drug) && ($cleaned = $cleanSubstance($drugEx->seizure_name_drug))) {
            return $cleaned;
        }

        // 5. Look for known narcotics in commodity_name, contents, etc. via DrugExhibitClassifier
        if ($drugEx) {
            $text = ($drugEx->commodity_name ?? '') . ' ' . ($drugEx->contents ?? '') . ' ' . ($drugEx->seizure_name ?? '') . ' ' . ($drugEx->seizure_contents ?? '');
            if ($matchedSubstance = \App\Services\DrugExhibitClassifier::extractKnownSubstance($text)) {
                return $matchedSubstance;
            }
        }

        // 6. Case level suspected drug
        if ($case) {
            $caseDrug = $case->suspected_drug ?? ($case->drug_type ?? ($case->seizure_type ?? null));
            if ($caseDrug && ($cleaned = $cleanSubstance($caseDrug))) {
                return $cleaned;
            }
        }

        return 'Unspecified';
    };

    // Helper to format drug name with proper capitalization and acronym preservation
    $formatDrugDisplay = function ($drug) {
        $drug = trim((string)$drug);
        if ($drug === '' || strcasecmp($drug, 'Unspecified') === 0) {
            return 'Unspecified';
        }
        $parts = array_map(function ($part) {
            $part = trim($part);
            $upper = strtoupper($part);
            if (in_array($upper, ['MDMA', 'THC', 'LSD', 'PCP', 'CBD', 'GHB'])) {
                return $upper;
            }
            $formatted = ucwords(strtolower($part));
            // Preserve uppercase acronyms e.g. (THC), (CBD)
            $formatted = preg_replace_callback('/\b(thc|cbd|mdma|lsd|pcp|ghb)\b/i', function ($m) {
                return strtoupper($m[1]);
            }, $formatted);
            return $formatted;
        }, preg_split('/\s*[;,]\s*/', $drug) ?: []);
        return implode(', ', array_filter($parts, fn($v) => $v !== ''));
    };

    // Helper to format detected_substances strictly from the detected_substances column in the laboratory_examination_results table
    $formatDetectedSubstances = static function ($matchedLab, $fallbackLab = null) use ($isValidRefValue): string {
        $lab = ($matchedLab instanceof \App\Models\LaboratoryExaminationResult)
            ? $matchedLab
            : (($fallbackLab instanceof \App\Models\LaboratoryExaminationResult) ? $fallbackLab : null);

        if (!$lab && is_object($matchedLab) && isset($matchedLab->detected_substances)) {
            $lab = $matchedLab;
        } elseif (!$lab && is_object($fallbackLab) && isset($fallbackLab->detected_substances)) {
            $lab = $fallbackLab;
        }

        if (!$lab) {
            return 'Unspecified';
        }

        $rawDet = $lab->detected_substances ?? null;
        if (empty($rawDet)) {
            return 'Unspecified';
        }

        if (is_string($rawDet)) {
            $trimmedDet = trim($rawDet);
            if (str_starts_with($trimmedDet, '[') || str_starts_with($trimmedDet, '{')) {
                $decoded = json_decode($trimmedDet, true);
                if (is_array($decoded)) {
                    $rawDet = $decoded;
                }
            } else {
                $split = preg_split('/\s*[,;\n\r]+\s*/', $trimmedDet);
                if (count($split) > 1) {
                    $rawDet = $split;
                }
            }
        }

        $cleanSubstance = static function ($value) use ($isValidRefValue): ?string {
            if (!is_scalar($value)) {
                return null;
            }
            $str = trim((string)$value);
            if (!$isValidRefValue($str)) {
                return null;
            }

            // Strip any parenthetical percentage like (84.6%) or (84%)
            $str = preg_replace('/\s*\(\s*\d+(?:\.\d+)?\s*%?\s*\)/', '', $str);
            // Strip standalone percentage at the end like 84.6% or 84%
            $str = preg_replace('/\s+\d+(?:\.\d+)?\s*%\s*$/', '', $str);
            // Strip existing trailing "detected"
            $str = preg_replace('/\bdetected\s*$/i', '', $str);
            $str = trim($str, " \t\n\r\0\x0B,;:-");

            if ($str === '' || !$isValidRefValue($str)) {
                return null;
            }

            return $str . ' detected';
        };

        $obsList = [];
        if (is_array($rawDet)) {
            foreach (array_values($rawDet) as $dItem) {
                if (is_array($dItem)) {
                    $sName = trim((string)($dItem['substance'] ?? ($dItem['name'] ?? ($dItem['compound'] ?? ''))));
                    $cleaned = $cleanSubstance($sName);
                    if ($cleaned !== null) {
                        $obsList[] = $cleaned;
                    }
                } elseif (is_scalar($dItem)) {
                    $cleaned = $cleanSubstance($dItem);
                    if ($cleaned !== null) {
                        $obsList[] = $cleaned;
                    }
                }
            }
        } elseif (is_scalar($rawDet)) {
            $cleaned = $cleanSubstance($rawDet);
            if ($cleaned !== null) {
                $obsList[] = $cleaned;
            }
        }

        return !empty($obsList) ? implode(', ', $obsList) : 'Unspecified';
    };

    // Helper to format presumptive_presence strictly for Table 5 Interpretation
    $formatPresumptivePresence = static function ($matchedLab, $fallbackLab = null, $docket = null, $case = null) use ($isValidRefValue, $resolveDrugName, $formatDrugDisplay): string {
        $rawPresence = is_object($matchedLab)
            ? ($matchedLab->presumptive_presence ?? null)
            : ($matchedLab['presumptive_presence'] ?? null);

        if (empty($rawPresence) && !empty($fallbackLab)) {
            $rawPresence = is_object($fallbackLab)
                ? ($fallbackLab->presumptive_presence ?? null)
                : ($fallbackLab['presumptive_presence'] ?? null);
        }

        if (empty($rawPresence) && !empty($docket['presumptive_presence'])) {
            $rawPresence = $docket['presumptive_presence'];
        }

        if (empty($rawPresence) && !empty($case->presumptive_presence)) {
            $rawPresence = $case->presumptive_presence;
        }

        if (is_string($rawPresence)) {
            $trimmedPres = trim($rawPresence);
            if (str_starts_with($trimmedPres, '[') || str_starts_with($trimmedPres, '{')) {
                $decoded = json_decode($trimmedPres, true);
                if (is_array($decoded)) {
                    $rawPresence = $decoded;
                }
            }
        }

        $cleanPresenceItem = static function ($text): ?string {
            $str = trim((string)$text);
            if ($str === '' || in_array(strtoupper($str), ['N/A', 'NA', 'NONE', 'NULL', '-', 'UNSPECIFIED'], true)) {
                return null;
            }
            $cleaned = preg_replace('/^(?:yes|no)\s*[:|\-]\s*(?:test\s*#?\d+\s*[:|\-]?\s*)?/i', '', $str);
            $cleaned = preg_replace('/^(?:presumptive\s+(?:positive|presence|indication(?:\s+of\s+the\s+presence(?:\s+of)?)?)\s*[:\-]?\s*|presence\s+of\s+)/i', '', trim($cleaned));
            $cleaned = trim($cleaned);
            return $cleaned !== '' ? $cleaned : null;
        };

        if (is_array($rawPresence)) {
            $presList = [];
            foreach ($rawPresence as $pItem) {
                if (is_array($pItem)) {
                    $val = $pItem['presence'] ?? ($pItem['name'] ?? ($pItem['substance'] ?? null));
                } else {
                    $val = $pItem;
                }
                if ($c = $cleanPresenceItem($val)) {
                    $presList[] = $c;
                }
            }
            if (!empty($presList)) {
                return 'Presumptive indication of the presence of ' . implode(', ', array_unique($presList));
            }
        } elseif (is_scalar($rawPresence)) {
            if ($c = $cleanPresenceItem($rawPresence)) {
                return "Presumptive indication of the presence of {$c}";
            }
        }

        $presResult = is_object($matchedLab) ? ($matchedLab->presumptive_result ?? null) : ($matchedLab['presumptive_result'] ?? null);
        if (is_string($presResult) && (strtoupper(trim($presResult)) === 'NO' || stripos($presResult, 'negative') !== false)) {
            return 'Presumptive test negative';
        }

        $fallbackDrug = $formatDrugDisplay($resolveDrugName($matchedLab, null, $case));
        if ($fallbackDrug !== 'Unspecified') {
            return "Presumptive indication of the presence of {$fallbackDrug}";
        }

        return 'Unspecified';
    };

    // Prepare self-contained base64 logo for zero network dependency
    $logoPath = public_path('NACOC_LOGO.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('NACOC_LOGO.png');

    // Prepare self-contained base64 font for Arial Narrow Regular
    $fontPath = public_path('font/Arial-Narrow-Font.ttf');
    $fontBase64 = file_exists($fontPath) ? 'data:font/truetype;charset=utf-8;base64,' . base64_encode(file_get_contents($fontPath)) : null;
    $fontAssetUrl = asset('font/Arial-Narrow-Font.ttf');

    // Prepare self-contained base64 font for Arial Narrow Bold
    $fontBoldPath = public_path('font/Arial-Narrow-Bold.ttf');
    if (!file_exists($fontBoldPath) && file_exists('C:/Windows/Fonts/arialnb.ttf')) {
        @copy('C:/Windows/Fonts/arialnb.ttf', $fontBoldPath);
    }
    $fontBoldBase64 = file_exists($fontBoldPath) ? 'data:font/truetype;charset=utf-8;base64,' . base64_encode(file_get_contents($fontBoldPath)) : null;
    $fontBoldAssetUrl = asset('font/Arial-Narrow-Bold.ttf');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preliminary Exhibit Analysis Report - {{ $caseRef }}</title>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <style>
        @font-face {
            font-family: 'Arial Narrow';
            src: @if(!empty($fontBase64)) url('{{ $fontBase64 }}') format('truetype'), @endif
                 url('{{ $fontAssetUrl }}') format('truetype'),
                 local('Arial Narrow');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: @if(!empty($fontBase64)) url('{{ $fontBase64 }}') format('truetype'), @endif
                 url('{{ $fontAssetUrl }}') format('truetype'),
                 local('Arial Narrow');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: @if(!empty($fontBoldBase64)) url('{{ $fontBoldBase64 }}') format('truetype'), @endif
                 url('{{ $fontBoldAssetUrl }}') format('truetype'),
                 local('Arial Narrow Bold'),
                 local('Arial-BoldMT'),
                 local('Arial Bold'),
                 @if(!empty($fontBase64)) url('{{ $fontBase64 }}') format('truetype'), @endif
                 url('{{ $fontAssetUrl }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: @if(!empty($fontBoldBase64)) url('{{ $fontBoldBase64 }}') format('truetype'), @endif
                 url('{{ $fontBoldAssetUrl }}') format('truetype'),
                 local('Arial Narrow Bold'),
                 local('Arial-BoldMT'),
                 local('Arial Bold'),
                 @if(!empty($fontBase64)) url('{{ $fontBase64 }}') format('truetype'), @endif
                 url('{{ $fontAssetUrl }}') format('truetype');
            font-weight: 800;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: @if(!empty($fontBoldBase64)) url('{{ $fontBoldBase64 }}') format('truetype'), @endif
                 url('{{ $fontBoldAssetUrl }}') format('truetype'),
                 local('Arial Narrow Bold'),
                 local('Arial-BoldMT'),
                 local('Arial Bold'),
                 @if(!empty($fontBase64)) url('{{ $fontBase64 }}') format('truetype'), @endif
                 url('{{ $fontAssetUrl }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-synthesis: weight style !important;
            -webkit-font-synthesis: weight style !important;
        }

        body {
            margin: 0;
            padding: 24px 0;
            background: #e2e8f0;
            color: #000000;
            font-family: 'Arial Narrow', sans-serif !important;
            font-size: 12pt;
            line-height: 1.35;
        }

        .a4-page,
        .a4-page *,
        .a4-page table,
        .a4-page th,
        .a4-page td,
        .a4-page p,
        .a4-page div,
        .a4-page span,
        .a4-page strong {
            font-family: 'Arial Narrow', sans-serif !important;
        }

        /* Standard A4 Sheet */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 16mm 20mm 14mm 20mm;
            margin: 0 auto 24px auto;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .page-content {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .page-footer-container {
            position: relative;
            z-index: 2;
            width: 100%;
            margin-top: auto;
            padding-top: 10px;
        }

        /* Subtle Center Watermark Crest */
        .watermark-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .watermark-overlay img {
            width: 1080px;
            height: 1080px;
            max-width: 282%;
            max-height: 282%;
            object-fit: contain;
            opacity: 0.12;
        }

        /* Document Top RESTRICTED Header */
        .restricted-header {
            font-size: 10pt;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #000000;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 14px;
        }

        /* Document Bottom RESTRICTED Footer */
        .footer-grid {
            position: relative;
            text-align: center;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .restricted-footer {
            font-size: 10pt;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #000000;
            text-transform: uppercase;
            text-align: center;
            margin: 0 auto;
        }

        /* Page Number in Bottom-Right */
        .page-number-text {
            font-size: 12pt;
            color: #000000;
            text-align: right;
            font-weight: 400;
            position: absolute;
            right: 0;
            bottom: 0;
        }

        /* Clean Formal Document Tables matching Physical Sample */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 14px;
            font-size: 12pt;
            table-layout: fixed;
            word-wrap: break-word;
        }

        .doc-table th, 
        .doc-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: top;
            color: #000000;
            background: transparent;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        /* Bolden all table headers */
        .doc-table th,
        .doc-table th * {
            font-size: 13pt !important;
            font-weight: 800 !important;
            text-align: left;
        }

        /* Unbold all table contents */
        .doc-table tbody td,
        .doc-table tbody td * {
            font-weight: 400 !important;
        }

        /* 2-Column Key-Value Tables: Left cell is header (Bold), Right cell is content (Unbold) */
        .doc-table-2col tbody td:first-child,
        .doc-table-2col tbody td:first-child * {
            width: 38%;
            font-size: 13pt !important;
            font-weight: 800 !important;
        }

        .doc-table-2col tbody td:last-child,
        .doc-table-2col tbody td:last-child * {
            width: 62%;
            font-weight: 400 !important;
        }

        .doc-section-title {
            font-size: 14pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #000000;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        .doc-body-text {
            font-size: 12pt;
            line-height: 1.35;
            color: #000000;
            margin-top: 0;
            margin-bottom: 12px;
        }

        .text-center {
            text-align: center !important;
        }

        /* Screen Only Action Toolbar */
        @media screen {
            .no-print-toolbar {
                max-width: 210mm;
                margin: 0 auto 18px auto;
                padding: 12px 18px;
                background: #0f172a;
                color: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
        }

        /* Strict 1:1 Print Media Formatting matching Screen Preview */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html, body {
                width: 210mm !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
                font-family: 'Arial Narrow', sans-serif !important;
                font-size: 12pt !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print,
            .page-break {
                display: none !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
            }

            .a4-page {
                width: 210mm !important;
                min-height: 296mm !important;
                height: 296mm !important;
                max-height: 296mm !important;
                padding: 16mm 20mm 14mm 20mm !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
                margin: 0 auto !important;
                page-break-before: auto !important;
                break-before: auto !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                position: relative !important;
                overflow: hidden !important;
                background: #ffffff !important;
            }

            .a4-page:first-of-type {
                page-break-before: avoid !important;
                break-before: avoid !important;
            }

            .a4-page:last-child,
            .a4-page:last-of-type {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .page-content {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
            }

            .page-footer-container {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
                margin-top: auto !important;
                padding-top: 10px !important;
            }

            .footer-grid {
                position: relative !important;
                text-align: center !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .restricted-footer {
                font-size: 10pt !important;
                font-weight: 800 !important;
                letter-spacing: 0.06em !important;
                color: #000000 !important;
                text-transform: uppercase !important;
                text-align: center !important;
                margin: 0 auto !important;
            }

            .page-number-text {
                font-size: 12pt !important;
                color: #000000 !important;
                text-align: right !important;
                font-weight: 400 !important;
                position: absolute !important;
                right: 0 !important;
                bottom: 0 !important;
            }

            .page-break {
                page-break-after: always !important;
                break-after: page !important;
            }

            tr, .no-break {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .doc-table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1px solid #000000 !important;
                margin-bottom: 14px !important;
                font-size: 12pt !important;
                table-layout: fixed !important;
                word-wrap: break-word !important;
            }

            .doc-table th, 
            .doc-table td {
                border: 1px solid #000000 !important;
                padding: 5px 8px !important;
                vertical-align: top !important;
                color: #000000 !important;
                background: transparent !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
            }

            .doc-table th,
            .doc-table th * {
                font-size: 13pt !important;
                font-weight: 800 !important;
                text-align: left !important;
            }

            .doc-table tbody td,
            .doc-table tbody td * {
                font-weight: 400 !important;
            }

            .doc-table-2col tbody td:first-child,
            .doc-table-2col tbody td:first-child * {
                width: 38% !important;
                font-size: 13pt !important;
                font-weight: 800 !important;
            }

            .doc-table-2col tbody td:last-child,
            .doc-table-2col tbody td:last-child * {
                width: 62% !important;
                font-weight: 400 !important;
            }

            .watermark-overlay {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                pointer-events: none !important;
                z-index: 1 !important;
                overflow: hidden !important;
            }

            .watermark-overlay img {
                width: 1080px !important;
                height: 1080px !important;
                max-width: 282% !important;
                max-height: 282% !important;
                object-fit: contain !important;
                opacity: 0.12 !important;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN ACTION TOOLBAR (NO-PRINT) -->
    <div class="no-print no-print-toolbar">
        <div class="flex items-center space-x-3">
            <span class="text-xs text-slate-400 font-mono">Case Reference: <strong class="text-emerald-400">{{ $caseRef }}</strong></span>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="window.print()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow transition-all flex items-center space-x-2 cursor-pointer border-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Print / Save Report (PDF)</span>
            </button>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PAGE 1: COVER PAGE                                        -->
    <!-- ======================================================== -->
    <div class="a4-page">
        <!-- Faint NACOC Center Crest Watermark -->
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="page-content">
            <!-- HEADER: RESTRICTED -->
            <div class="restricted-header">
                RESTRICTED
            </div>

            <!-- LOGO & COMMISSION HEADINGS -->
            <div style="display: flex; align-items: center; justify-content: flex-start; margin-top: 10px; margin-bottom: 20px;">
                <div style="width: 96px; height: 96px; flex-shrink: 0;">
                    <img src="{{ $logoBase64 }}" alt="NACOC Crest" style="width: 96px; height: 96px; object-fit: contain;">
                </div>
                <div style="flex: 1; text-align: center; margin-right: 76px;">
                    <div style="font-size: 14pt; font-weight: 800; letter-spacing: 0.02em; line-height: 1.25; color: #000000; text-transform: uppercase;">
                        NARCOTICS CONTROL COMMISSION (NACOC)
                    </div>
                    <div style="font-size: 14pt; font-weight: 800; letter-spacing: 0.03em; margin-top: 4px; line-height: 1.25; color: #000000; text-transform: uppercase;">
                        FORENSICS DEPARTMENT
                    </div>
                    <div style="font-size: 14pt; font-weight: 800; letter-spacing: 0.03em; margin-top: 4px; line-height: 1.25; color: #000000; text-transform: uppercase;">
                        LABORATORY ANALYSIS UNIT
                    </div>
                </div>
            </div>

            <!-- MAIN DOCUMENT TITLE -->
            <div style="text-align: center; margin-top: 24px; margin-bottom: 10px;">
                <div style="font-size: 14pt; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; color: #000000;">
                    PRELIMINARY EXHIBIT ANALYSIS REPORT
                </div>
            </div>

            <!-- TABLE 1 (headers on the left) -->
            <table class="doc-table doc-table-2col">
                <tbody>
                    <tr>
                        <td>Case Name<span style="display:none;">Case Reference</span></td>
                        <td>{{ strtoupper($caseTitle) }}</td>
                    </tr>
                    <tr>
                        <td>Report Reference No.</td>
                        <td>{{ $reportRef }}</td>
                    </tr>
                    <tr>
                        <td>Date of Report</td>
                        <td>{{ $dateOfReport }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- CENTRED SUBJECT HEADING -->
            <div style="text-align: center; margin-top: 20px; margin-bottom: 10px;">
                <div style="font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #000000; line-height: 1.45;">
                    SUBJECT: PRESUMPTIVE TEST OF EXHIBITS CONDUCTED IN THE PRESENCE OF {{ strtoupper($suspectDisplayName) }}
                </div>
            </div>

            <!-- TABLE 2 (headers on the left) -->
            <table class="doc-table doc-table-2col">
                <tbody>
                    <tr>
                        <td>Case Reference<span style="display:none;">Case Name</span></td>
                        <td>{{ $caseIncidentRef }}</td>
                    </tr>
                    <tr>
                        <td>Source/Requesting Unit</td>
                        <td>{{ $sourceRequestingUnit }}</td>
                    </tr>
                    <tr>
                        <td>Investigating Officer</td>
                        <td>{{ $investigatingOfficer }}</td>
                    </tr>
                    <tr>
                        <td>Date and Time Received</td>
                        <td>{{ $dateTimeReceived }}</td>
                    </tr>
                    <tr>
                        <td>Date of Preliminary Examination</td>
                        <td>{{ $dateOfPrelimExam }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- PURPOSE OF EXAMINATION (Paragraph below Table 2) -->
            <div style="margin-top: 38px; font-size: 12pt; line-height: 1.45; color: #000000;">
                <strong>PURPOSE OF EXAMINATION:</strong> {{ $purposeOfExamination }}
            </div>
        </div>

        <!-- PAGE 1 FOOTER -->
        <div class="page-footer-container">
            <div class="footer-grid">
                <div class="restricted-footer">
                    RESTRICTED
                </div>
                <div class="page-number-text">
                    Page 1 of 4
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PAGE 2: EXHIBITS DESCRIPTION & PROCEDURE                  -->
    <!-- ======================================================== -->
    <div class="a4-page">
        <!-- Faint NACOC Center Crest Watermark -->
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="page-content">
            <!-- HEADER: RESTRICTED -->
            <div class="restricted-header">
                RESTRICTED
            </div>

            <!-- SECTION: DESCRIPTION OF EXHIBIT(S) -->
            <div class="doc-section-title" style="margin-top: 24px;">
                DESCRIPTION OF EXHIBIT(S)
            </div>
            <div class="doc-body-text">
                {{ $descriptionOfExhibits }}
            </div>

            <!-- TABLE 3 (headers on top) - STRICTLY DRUG EXHIBIT TYPE -->
            <table class="doc-table doc-table-exhibits" style="width: 100%; table-layout: fixed; margin-top: 8px; margin-bottom: 22px;">
                <thead>
                    <tr>
                        <th style="width: 12%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Exhibit No.</th>
                        <th style="width: 30%; word-break: break-word; overflow-wrap: break-word;">Packaging as received<span style="display:none;">Package as Received</span></th>
                        <th style="width: 26%; word-break: break-word; overflow-wrap: break-word;">Contents (visual)<span style="display:none;">Contents (Visual)</span></th>
                        <th style="width: 12%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Quantity</th>
                        <th style="width: 10%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Gross weight<span style="display:none;">Gross Weight</span></th>
                        <th style="width: 10%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Net weight<span style="display:none;">Net Weight</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drugExhibits as $drugEx)
                        @php
                            $matchedLab = $labResults->firstWhere('exhibit_id', $drugEx->id);
                            if (!$matchedLab && !empty($drugEx->lab_exhibit_no)) {
                                $matchedLab = $labResults->firstWhere('lab_exhibit_no', $drugEx->lab_exhibit_no);
                            }
                            if (!$matchedLab) {
                                $matchedLab = $labResults->get($loop->index) ?? $primaryLabResult;
                            }
                            if (!$matchedLab && !empty($docketData['seizures'][$loop->index])) {
                                $matchedLab = (object) ($docketData['seizures'][$loop->index]);
                            }
                            
                            // Exhibit No. entered by lab officer or fallback sequential letter
                            $labExNo = $pickValid([
                                $drugEx->lab_exhibit_no ?? null,
                                is_object($matchedLab) ? ($matchedLab->lab_exhibit_no ?? null) : ($matchedLab['lab_exhibit_no'] ?? null),
                            ]) ?? chr(65 + $loop->index);

                            // Packaging as received
                            $pkgReceived = $pickValid([
                                $drugEx->commodity_name ?? null,
                                $drugEx->seizure_name_drug ?? null,
                                $drugEx->seizure_name ?? null,
                                $drugEx->seizure_packaging ?? null,
                                is_object($matchedLab) ? ($matchedLab->sample_weight_description ?? null) : ($matchedLab['sample_weight_description'] ?? null),
                            ]) ?? 'Unspecified';

                            // Contents (visual)
                            $visualContents = $pickValid([
                                $drugEx->contents ?? null,
                                $drugEx->seizure_contents ?? null,
                                $drugEx->other_exhibit_description ?? null,
                            ]) ?? 'Unspecified';

                            // Quantity formatted nicely
                            $rawQty = $pickValid([$drugEx->seizure_quantity ?? null]) ?? '1';
                            $formattedQty = is_numeric($rawQty) ? number_format((float)$rawQty) : $rawQty;

                            // Gross weight
                            $rawMatchedGross = $pickValid([
                                data_get($matchedLab, 'sample_weight_gross'),
                                data_get($matchedLab, 'sample_gross'),
                                data_get($matchedLab, 'sample_weight.gross_weight'),
                            ]);
                            $exSeizureWeight = $isValidRefValue($drugEx->seizure_weight ?? null)
                                ? (trim((string)$drugEx->seizure_weight) . ' ' . ($isValidRefValue($drugEx->seizure_unit ?? null) ? trim((string)$drugEx->seizure_unit) : 'g'))
                                : null;
                            $grossWeight = $pickValid([
                                $rawMatchedGross,
                                $case->sample_weight_gross ?? null,
                                $labDataFromDocket['sample_weight_gross'] ?? null,
                                $exSeizureWeight,
                            ]) ?? '-';

                            // Net weight
                            $rawMatchedNet = $pickValid([
                                data_get($matchedLab, 'sample_weight_reserved'),
                                data_get($matchedLab, 'sample_reserved'),
                                data_get($matchedLab, 'sample_weight.reserved_samples'),
                                data_get($matchedLab, 'sample_weight_taken'),
                                data_get($matchedLab, 'sample_taken'),
                            ]);
                            $netWeight = $pickValid([
                                $rawMatchedNet,
                                $case->sample_weight_reserved ?? null,
                                $labDataFromDocket['sample_weight_reserved'] ?? null,
                                $exSeizureWeight,
                            ]) ?? '-';
                        @endphp
                        <tr>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $labExNo }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $pkgReceived }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $visualContents }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $formattedQty }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $grossWeight ?: '-' }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $netWeight ?: '-' }}</td>
                        </tr>
                    @empty
                        @php
                            $fallbackGross = $primaryLabResult->sample_weight_gross ?? ($case->sample_weight_gross ?? '-');
                            $fallbackNet = $primaryLabResult->sample_weight_reserved ?? ($case->sample_weight_reserved ?? '-');
                            $fallbackQty = $primaryExhibit->seizure_quantity ?? '1';
                            $formattedFallbackQty = is_numeric($fallbackQty) ? number_format((float)$fallbackQty) : $fallbackQty;
                        @endphp
                        <tr>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $primaryExhibit->lab_exhibit_no ?? ($primaryExhibit->exhibit_number ?? 'A') }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $primaryExhibit->commodity_name ?? ($primaryExhibit->seizure_name_drug ?? ($primaryExhibit->seizure_name ?? 'Unspecified')) }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $primaryExhibit->contents ?? ($primaryExhibit->seizure_contents ?? 'Unspecified') }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $formattedFallbackQty }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $fallbackGross }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $fallbackNet }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- SECTION: PRELIMINARY EXAMINATION PROCEDURE -->
            <div class="doc-section-title" style="margin-top: 34px; margin-bottom: 0px;">
                PRELIMINARY EXAMINATION PROCEDURE
            </div>
            <div class="doc-body-text" style="white-space: pre-line; margin-top: -20px;">
                {{ $prelimExamProcedure }}
            </div>

            <!-- TABLE 4 (headers on the left) -->
            <table class="doc-table doc-table-2col" style="margin-top: 14px;">
                <tbody>
                    <tr>
                        <td style="width: 40%;">Screening method/Instrument used<span style="display:none;">Screening Method/Instrument Used</span></td>
                        <td>{{ $screeningMethod }}</td>
                    </tr>
                    <tr>
                        <td>Date of Examination</td>
                        <td>{{ $dateOfPrelimExam }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGE 2 FOOTER -->
        <div class="page-footer-container">
            <div class="footer-grid">
                <div class="restricted-footer">
                    RESTRICTED
                </div>
                <div class="page-number-text">
                    Page 2 of 4
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PAGE 3: RESULTS, REMARKS & SIGNATURES                     -->
    <!-- ======================================================== -->
    <div class="a4-page">
        <!-- Faint NACOC Center Crest Watermark -->
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="page-content">
            <!-- HEADER: RESTRICTED -->
            <div class="restricted-header">
                RESTRICTED
            </div>

            <!-- SECTION: RESULTS OF PRELIMINARY EXAMINATION -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 5px;">
                RESULTS OF PRELIMINARY EXAMINATION
            </div>

            <!-- TABLE 5 (headers on top) -->
            <table class="doc-table doc-table-results" style="width: 100%; table-layout: fixed; margin-top: 8px; margin-bottom: 22px;">
                <thead>
                    <tr>
                        <th style="width: 10%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Exhibit No.</th>
                        <th style="width: 15%; word-break: break-word; overflow-wrap: break-word;">Exhibit</th>
                        <th style="width: 27%; word-break: break-word; overflow-wrap: break-word;">Test/Examination Conducted</th>
                        <th style="width: 26%; word-break: break-word; overflow-wrap: break-word;">Observation/Results</th>
                        <th style="width: 22%; text-align: center; word-break: break-word; overflow-wrap: break-word;">Interpretation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drugExhibits as $index => $drugEx)
                        @php
                            $matchedLab = $labResults->firstWhere('exhibit_id', $drugEx->id);
                            if (!$matchedLab && !empty($drugEx->lab_exhibit_no)) {
                                $matchedLab = $labResults->firstWhere('lab_exhibit_no', $drugEx->lab_exhibit_no);
                            }
                            if (!$matchedLab) {
                                $matchedLab = $labResults->get($index) ?? $primaryLabResult;
                            }
                            if (!$matchedLab && !empty($docketData['seizures'][$index])) {
                                $matchedLab = (object) ($docketData['seizures'][$index]);
                            }

                            $testMethods = $pickValid([
                                is_object($matchedLab) ? ($matchedLab->test_methods ?? null) : ($matchedLab['test_methods'] ?? null),
                                $primaryLabResult->test_methods ?? null,
                                $case->confirmatory_test_methods ?? null,
                                $labDataFromDocket['test_methods'] ?? null,
                            ]) ?? 'Unspecified';

                            $obsResults = $formatDetectedSubstances($matchedLab, $primaryLabResult);
                            $interpretation = $formatPresumptivePresence($matchedLab, $primaryLabResult, $docketData['seizures'][$index] ?? ($labDataFromDocket ?? null), $case);

                            $labExIdentifier = $pickValid([
                                $drugEx->lab_exhibit_no ?? null,
                                is_object($matchedLab) ? ($matchedLab->lab_exhibit_no ?? null) : ($matchedLab['lab_exhibit_no'] ?? null),
                            ]) ?? chr(65 + $index);
                        @endphp
                        <tr>
                            <!-- Auto-numbered sequentially (1, 2, 3, 4, …) -->
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $labExIdentifier }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $testMethods }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $obsResults }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $interpretation }}</td>
                        </tr>
                    @empty
                        @php
                            $singleTestMethods = $primaryLabResult->test_methods 
                                ?? ($case->confirmatory_test_methods 
                                ?? ($labDataFromDocket['test_methods'] 
                                ?? 'Unspecified'));
                            if (empty(trim((string)$singleTestMethods))) {
                                $singleTestMethods = 'Unspecified';
                            }
                            $singleObs = $formatDetectedSubstances($primaryLabResult);
                            $singleInterp = $formatPresumptivePresence($primaryLabResult, null, $labDataFromDocket, $case);
                            $singleEx = $primaryExhibit->lab_exhibit_no ?? ($primaryExhibit->exhibit_number ?? 'A');
                        @endphp
                        <tr>
                            <td class="text-center" style="word-break: break-word; overflow-wrap: break-word;">1</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $singleEx }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $singleTestMethods }}</td>
                            <td style="word-break: break-word; overflow-wrap: break-word;">{{ $singleObs }}</td>
                            <td style="text-align: center; word-break: break-word; overflow-wrap: break-word;">{{ $singleInterp }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- SECTION: REMARKS -->
            <div style="margin-bottom: 24px;">
                <div style="font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #000000; margin-bottom: 4px;">
                    REMARKS
                </div>
                <div class="doc-body-text">
                    {{ $remark }}
                </div>
            </div>

            <!-- SECTION: SPECIFIC REMARKS -->
            <div style="margin-bottom: 24px;">
                <div style="font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #000000; margin-bottom: 4px;">
                    Specific Remarks<span style="display:none;">SPECIFIC REMARKS</span>
                </div>
                <div class="doc-body-text">
                    {{ $specifications }}
                </div>
            </div>

            <!-- SECTION: DISPOSITION OF EXHIBIT(S) -->
            <div style="margin-bottom: 24px;">
                <div style="font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #000000; margin-bottom: 4px;">
                    DISPOSITION OF EXHIBIT(S)
                </div>
                <div class="doc-body-text">
                    {{ $dispositionOfExhibit }}
                </div>
            </div>

        </div>

        <!-- PAGE 3 FOOTER -->
        <div class="page-footer-container">
            <div class="footer-grid">
                <div class="restricted-footer">
                    RESTRICTED
                </div>
                <div class="page-number-text">
                    Page 3 of 4
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PAGE 4: SIGNATORY PAGE                                    -->
    <!-- ======================================================== -->
    <div class="a4-page">
        <!-- Faint NACOC Center Crest Watermark -->
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="page-content">
            <!-- HEADER: RESTRICTED -->
            <div class="restricted-header">
                RESTRICTED
            </div>

            <!-- TABLE 1: PREPARED/REPORTED BY -->
            <div class="doc-section-title" style="margin-top: 35px; margin-bottom: 6px;">
                PREPARED/REPORTED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE 2: APPROVED BY -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 6px;">
                APPROVED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE 3: AUTHORIZED BY -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 6px;">
                AUTHORIZED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGE 4 FOOTER -->
        <div class="page-footer-container">
            <div class="footer-grid">
                <div class="restricted-footer">
                    RESTRICTED
                </div>
                <div class="page-number-text">
                    Page 4 of 4
                </div>
            </div>
        </div>
    </div>

    @if (request()->boolean('download') || request()->boolean('autoprint'))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 350);
            });
        </script>
    @endif

</body>
</html>
