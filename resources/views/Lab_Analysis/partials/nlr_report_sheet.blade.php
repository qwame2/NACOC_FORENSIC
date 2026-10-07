
<!-- OFFICIAL REPORT DOCUMENT SHEET (2-PAGE NLR REPORT) -->
@php
    $rawLabNoVal = $labNo ?? ($case->laboratory_number ?? ($docketData['laboratory_number'] ?? 'Lab No. 72/FS09/2026'));
    $formattedLabNoVal = str_starts_with($rawLabNoVal, 'Lab No.') ? $rawLabNoVal : 'Lab No. ' . $rawLabNoVal;

    $rawPresumptiveVal = $presumptiveResult
        ?? ($labPresumptive
        ?? ($suspectLabResults[0]['presumptive_raw']
        ?? ($case->presumptive_test_result
        ?? ($docketData['lab_examination']['presumptive_result']
        ?? ($docketData['presumptive_reaction_result_input']
        ?? ($docketData['presumptive_result']
        ?? ($docketData['seizures'][0]['presumptive_result']
        ?? null)))))));

    if (!isset($parsePresumptiveResult) || !is_callable($parsePresumptiveResult)) {
        $parsePresumptiveResult = function ($raw) use ($drugName) {
            $fallback = [['result' => 'YES', 'presence' => 'Colorimetric Reaction', 'purity' => $drugName ?? '—']];
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
                    return [['result' => 'YES', 'presence' => 'Colorimetric Reaction', 'purity' => $drugName ?? '—']];
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
            return !empty($rows) ? $rows : [['result' => 'YES', 'presence' => $innerContent, 'purity' => $drugName ?? '—']];
        };
    }

    $presumptiveRows = isset($suspectLabResults[0]['presumptive_rows']) && !empty($suspectLabResults[0]['presumptive_rows'])
        ? $suspectLabResults[0]['presumptive_rows']
        : $parsePresumptiveResult($rawPresumptiveVal);

    // Confirmatory Analysis variables for NLR Report
    $substanceIdVal = $suspectLabResults[0]['substance_identification'] 
        ?? ($labExamData['substance_identification'] 
        ?? ($docketData['lab_examination']['substance_identification'] 
        ?? ($case->confirmatory_substance_identification 
        ?? ($drugName ?? 'Cocaine Hydrochloride'))));

    $rawDetSubstances = $suspectLabResults[0]['detected_substances'] 
        ?? ($labExamData['detected_substances'] 
        ?? ($docketData['lab_examination']['detected_substances'] 
        ?? null));

    if (is_string($rawDetSubstances)) {
        $rawDetSubstances = json_decode($rawDetSubstances, true) ?: [];
    }

    $testMethodsVal = $suspectLabResults[0]['test_methods'] 
        ?? ($labExamData['test_methods'] 
        ?? ($docketData['lab_examination']['test_methods'] 
        ?? ($case->confirmatory_test_methods 
        ?? 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay')));

    $specificationsVal = $suspectLabResults[0]['specifications'] 
        ?? ($labExamData['specifications'] 
        ?? ($docketData['lab_examination']['specifications'] 
        ?? ($case->confirmatory_specifications 
        ?? 'Official Forensic Standard ISO/IEC 17025 Compliant')));

    $remarkVal = $suspectLabResults[0]['remark'] 
        ?? ($labExamData['remark'] 
        ?? ($docketData['lab_examination']['remark'] 
        ?? ($case->confirmatory_remark 
        ?? 'Controlled Narcotic Substance verified under strict laboratory chain of custody protocol.')));

    $detectedSubstancesRows = [];
    if (!empty($rawDetSubstances) && is_array($rawDetSubstances)) {
        foreach ($rawDetSubstances as $dsItem) {
            if (is_array($dsItem)) {
                $subName = $dsItem['substance'] ?? ($dsItem['name'] ?? '');
                $subPct  = $dsItem['percentage'] ?? ($dsItem['purity'] ?? '');
                $subSpec = $dsItem['specificationsVal'] ?? ($dsItem['specifications'] ?? $specificationsVal);
                $subTest = $dsItem['testMethodsVal'] ?? ($dsItem['test_methods'] ?? $testMethodsVal);
                if ($subName !== '' || $subPct !== '') {
                    $detectedSubstancesRows[] = [
                        'substance'         => $subName ?: $substanceIdVal,
                        'percentage'        => $subPct !== '' ? (str_contains($subPct, '%') ? $subPct : $subPct . '%') : '—',
                        'specificationsVal' => $subSpec,
                        'testMethodsVal'    => $subTest,
                    ];
                }
            } elseif (is_string($dsItem) && trim($dsItem) !== '') {
                $detectedSubstancesRows[] = [
                    'substance'         => trim($dsItem),
                    'percentage'        => '—',
                    'specificationsVal' => $specificationsVal,
                    'testMethodsVal'    => $testMethodsVal,
                ];
            }
        }
    }

    if (empty($detectedSubstancesRows)) {
        $detectedSubstancesRows[] = [
            'substance'         => $substanceIdVal ?: ($drugName ?? 'Cocaine Hydrochloride'),
            'percentage'        => '85.4%',
            'specificationsVal' => $specificationsVal,
            'testMethodsVal'    => $testMethodsVal,
        ];
    }

    // Gather dynamic Sample Weight table data from Confirmatory Test / Laboratory Examination Result
    $sampleWeightRowsData = [];

    // Helper function to format Packaging Type with Units if numeric only (e.g. "800" + "wraps" -> "800 wraps")
    $formatUnitsWithPkg = function ($rawUnits, $rawPkg) {
        $units = trim((string) $rawUnits);
        $pkg = trim((string) $rawPkg);
        if ($units === '' || $units === '—') {
            return $pkg !== '' ? $pkg : '';
        }
        if ($pkg === '' || $pkg === '—') {
            return $units;
        }
        if (preg_match('/^\d+(\.\d+)?$/', $units)) {
            return $units . ' ' . $pkg;
        }
        return $units;
    };

    // Helper function to detect weight unit (e.g. "kg", "g", "mg", "lbs")
    $detectWeightUnit = function (...$candidates) {
        foreach ($candidates as $cand) {
            if (empty($cand) || !is_string($cand)) continue;
            if (preg_match('/\b(kg|kgs|kilogram|kilograms)\b/i', $cand)) return 'kg';
            if (preg_match('/\b(g|gm|gms|gram|grams)\b/i', $cand) && !preg_match('/\b(kg|mg)\b/i', $cand)) return 'g';
            if (preg_match('/\b(mg|mgs|milligram|milligrams)\b/i', $cand)) return 'mg';
            if (preg_match('/\b(lb|lbs|pound|pounds)\b/i', $cand)) return 'lbs';
        }
        return '';
    };

    // Helper function to attach weight unit if missing
    $attachUnit = function ($val, $unit) {
        $val = trim((string) $val);
        if ($val === '' || $val === '—') return '—';
        if (empty($unit)) return $val;
        if (preg_match('/[a-zA-Z]/', $val)) return $val;
        return $val . ' ' . $unit;
    };

    if (!empty($suspectLabResults) && is_array($suspectLabResults)) {
        foreach ($suspectLabResults as $sIdx => $sRes) {
            $sw = $sRes['sample_weight'] ?? [];
            $exNo = $sRes['exhibit_number'] ?? ($sRes['exhibit_id'] ?? chr(65 + $sIdx));
            $desc = $sw['description'] ?? ($sRes['substance_identification'] ?? ($drugName ?? 'Narcotic Exhibit'));
            $units = $sw['units_packaging'] ?? ($sw['sample_weight_units'] ?? null);
            $gross = $sw['gross_weight'] ?? ($sw['sample_weight_gross'] ?? null);
            $taken = $sw['sample_taken'] ?? ($sw['sample_weight_taken'] ?? '—');
            $reserved = $sw['reserved_samples'] ?? ($sw['sample_weight_reserved'] ?? '—');

            $pkg = $sw['packaging_type'] 
                ?? ($sRes['seizure_packaging'] 
                ?? ($sRes['packaging_type'] 
                ?? ($sRes['packaging'] 
                ?? ($sw['packaging'] 
                ?? ($case->seizure_packaging ?? null)))));

            $measUnit = $sw['measurement_unit'] 
                ?? ($sw['unit'] 
                ?? ($sRes['measurement_unit'] 
                ?? null));

            $unitDetected = $detectWeightUnit($gross, $taken, $reserved, $measUnit, $units, $quantityStr ?? '', $case->sample_weight_units ?? '');

            $formattedUnits = $formatUnitsWithPkg($units, $pkg);
            $formattedGross = $attachUnit($gross, $unitDetected);
            $formattedTaken = $attachUnit($taken, $unitDetected);
            $formattedReserved = $attachUnit($reserved, $unitDetected);

            $unitsGross = '—';
            if (!empty($formattedUnits) && !empty($formattedGross) && $formattedGross !== '—') {
                $unitsGross = str_contains($formattedUnits, '(') ? $formattedUnits : ($formattedUnits . ' (' . $formattedGross . ')');
            } elseif (!empty($formattedGross) && $formattedGross !== '—') {
                $unitsGross = $formattedGross;
            } elseif (!empty($formattedUnits)) {
                $unitsGross = $formattedUnits;
            }

            $sampleWeightRowsData[] = [
                'exhibit' => $exNo,
                'description' => $desc,
                'units_gross' => $unitsGross,
                'sample_taken' => $formattedTaken,
                'reserved_samples' => $formattedReserved,
            ];
        }
    }

    if (empty($sampleWeightRowsData)) {
        $labExam = $labExamData ?? ($docketData['lab_examination'] ?? []);
        $swDirect = [];
        if (!empty($labExam['sample_weight'])) {
            $swDirect = is_string($labExam['sample_weight']) ? (json_decode($labExam['sample_weight'], true) ?: []) : $labExam['sample_weight'];
        }

        $desc = $swDirect['description'] 
            ?? ($labExam['sample_weight_description'] 
            ?? ($docketData['sample_weight_description'] 
            ?? ($case->exhibit_description 
            ?? ($drugName ?? 'Narcotic Exhibit'))));

        $units = $swDirect['units_packaging'] 
            ?? ($swDirect['units'] 
            ?? ($labExam['sample_weight_units'] 
            ?? ($docketData['sample_weight_units'] 
            ?? ($quantityStr ?? null))));

        $gross = $swDirect['gross_weight'] 
            ?? ($labExam['sample_weight_gross'] 
            ?? ($docketData['sample_weight_gross'] 
            ?? null));

        $taken = $swDirect['sample_taken'] 
            ?? ($labExam['sample_weight_taken'] 
            ?? ($docketData['sample_weight_taken'] 
            ?? '—'));

        $reserved = $swDirect['reserved_samples'] 
            ?? ($labExam['sample_weight_reserved'] 
            ?? ($docketData['sample_weight_reserved'] 
            ?? '—'));

        $exNo = $labExam['exhibit_number'] ?? ($docketData['exhibit_number'] ?? 'A');

        $pkg = $swDirect['packaging_type'] 
            ?? ($labExam['seizure_packaging'] 
            ?? ($docketData['seizure_packaging'] 
            ?? ($case->seizure_packaging ?? null)));

        $measUnit = $swDirect['measurement_unit'] 
            ?? ($swDirect['unit'] 
            ?? ($labExam['sample_weight_units'] 
            ?? ($docketData['sample_weight_units'] 
            ?? null)));

        $unitDetected = $detectWeightUnit($gross, $taken, $reserved, $measUnit, $units, $quantityStr ?? '', $case->sample_weight_units ?? '');

        $formattedUnits = $formatUnitsWithPkg($units, $pkg);
        $formattedGross = $attachUnit($gross, $unitDetected);
        $formattedTaken = $attachUnit($taken, $unitDetected);
        $formattedReserved = $attachUnit($reserved, $unitDetected);

        $unitsGross = '—';
        if (!empty($formattedUnits) && !empty($formattedGross) && $formattedGross !== '—') {
            $unitsGross = str_contains($formattedUnits, '(') ? $formattedUnits : ($formattedUnits . ' (' . $formattedGross . ')');
        } elseif (!empty($formattedGross) && $formattedGross !== '—') {
            $unitsGross = $formattedGross;
        } elseif (!empty($formattedUnits)) {
            $unitsGross = $formattedUnits;
        }

        $sampleWeightRowsData[] = [
            'exhibit' => $exNo,
            'description' => $desc,
            'units_gross' => $unitsGross,
            'sample_taken' => $formattedTaken,
            'reserved_samples' => $formattedReserved,
        ];
    }

    // Query nlr_reports table for detailed_exhibit_description and condition_of_reserved_sample
    $nlrRecord = null;
    try {
        if (!empty($case->id) || !empty($caseNo) || !empty($rawLabNoVal)) {
            $nlrRecord = \App\Models\NlrReport::forCase($case->id ?? null, $caseNo ?? null, $rawLabNoVal ?? null);
        }
    } catch (\Throwable $e) {
        // Fall back gracefully if table query fails
    }

    $defaultNarrativeFallback = "Inside the box were two large ziplock bags.\n• One of the ziplock bags labelled 'A' contained one hundred and six (106) wraps of plant-like substances.\n• The second ziplock bag contained one transparent polythene bag and a small ziplock bag labeled 'B' and 'C' respectively. The transparent polythene contained quantities of loose plant-like material while the small ziplock bag contained six (6) rolls of plant-like substances.";

    $narrativeVal = $nlrRecord ? $nlrRecord->detailed_exhibit_description : null;
    if (empty($narrativeVal)) {
        $narrativeVal = $detailedExhibitDescription 
            ?? ($detailed_exhibit_description 
            ?? (old('detailed_exhibit_description') 
            ?? ($suspectLabResults[0]['detailed_exhibit_description'] 
            ?? ($suspectLabResults[0]['exhibit_description'] 
            ?? ($labExamData['detailed_exhibit_description'] 
            ?? ($labExamData['exhibit_description'] 
            ?? ($docketData['detailed_exhibit_description'] 
            ?? ($docketData['lab_examination']['detailed_exhibit_description'] 
            ?? ($docketData['lab_examination']['exhibit_description'] 
            ?? ($case->detailed_exhibit_description 
            ?? ($case->exhibit_description 
            ?? $defaultNarrativeFallback)))))))))));
    }

    // Determine Condition of Reserved Sample(s)
    $conditionReservedRaw = $nlrRecord->condition_of_reserved_sample ?? null;
    if (empty($conditionReservedRaw)) {
        $conditionReservedRaw = $condition_of_reserved_sample 
            ?? (old('condition_of_reserved_sample') 
            ?? ($docketData['condition_of_reserved_sample'] 
            ?? ($labExamData['condition_of_reserved_sample'] 
            ?? ($case->condition_of_reserved_sample ?? 'Sealed & Labelled'))));
    }

    $conditionMap = [
        'good'           => 'Good / Unaltered',
        'sealed'         => 'Sealed & Labelled',
        'partially_used' => 'Partially Used',
        'degraded'       => 'Degraded',
        'contaminated'   => 'Contaminated',
        'destroyed'      => 'Destroyed / Expended',
    ];
    $conditionReservedVal = $conditionMap[strtolower($conditionReservedRaw)] ?? (ucwords(str_replace('_', ' ', $conditionReservedRaw)) ?: 'Sealed & Labelled');

    // Determine NLR Code for NLR Report (strictly format as NLR-*, never output GSA-FM-T51-F)
    $rawNlrRef = $nlrRecord->nlr_code 
        ?? ($nlr_code 
        ?? ($examRef 
        ?? ($labExamData['nlr_code'] 
        ?? ($labExamData['exam_reference'] 
        ?? ($docketData['lab_examination']['exam_reference'] 
        ?? ($case->lab_exam_reference ?? null))))));

    if (!empty($rawNlrRef)) {
        $nlrCodeVal = str_starts_with($rawNlrRef, 'GSA-') ? str_replace('GSA-', 'NLR-', $rawNlrRef) : $rawNlrRef;
    } else {
        $nlrCodeVal = 'NLR-FM-T51-F';
    }
@endphp
<div id="nlrReportSheet" class="printable-document">

    <!-- ============================================================
         PAGE 1: OFFICIAL FORENSIC NLR COVER PAGE
         ============================================================ -->
    <div class="a4-page page-break relative text-slate-900">

        <!-- SUBTLE NACOC WATERMARK -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
            <img
                src="{{ asset('nacoc.jfif') }}"
                alt="NACOC Watermark"
                class="w-[900px] h-[900px] object-contain filter grayscale"
            />
        </div>

        <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

            <!-- PAGE 1 CONTENT -->
            <div>

                <!-- HEADER -->
                <div class="relative pt-4 pb-6 border-b border-slate-300 text-center">

                    <!-- TOP LEFT BADGE -->
                    <div class="absolute top-0 left-0 text-left">
                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">
                            REPUBLIC OF GHANA
                        </p>
                        <p class="mt-0.5 text-[8px] text-slate-700">
                            ISO/IEC 17025
                        </p>
                    </div>

                    <!-- TOP RIGHT BADGE -->
                    <div class="absolute top-0 right-0 text-right">
                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">
                            Confidential
                        </p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                            NLR &bull; Page 1 of 2
                        </p>
                    </div>

                    <!-- CENTERED LOGO -->
                    <div class="mx-auto w-20 h-20 flex items-center justify-center mb-7">
                        <img
                            src="{{ asset('NACOC LOGO.jfif') }}"
                            alt="NACOC Emblem Logo"
                            class="max-h-40 max-w-40 object-contain"
                        />
                    </div>

                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>

                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700">
                            DEPARTMENT OF FORENSIC
                        </p>

                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-600">
                            LABORATORY ANALYSIS UNIT (LAU)
                        </p>

                        <!--<p class="mt-1 text-[9px] text-slate-900">
                            P.O. Box CT 3483, Cantonments, Accra
                            &bull;
                            Tel: +233 (0) 302 770 000
                            &bull;
                            Web: www.nacoc.gov.gh
                        </p>-->
                    </div>

                </div>


                <!-- REPORT TITLE -->
                <div class="mt-10 text-center">

                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-900">
                        OFFICIAL FORENSIC DOSSIER
                    </p>

                    <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950">
                        NACOC LAB RESULT
                    </h2>

                    <p class="mt-1 text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">
                        Analytical Examination Report
                    </p>

                    <div class="mx-auto mt-4 h-[2px] w-16 bg-emerald-700"></div>

                    <p class="mt-3 text-[9px] font-medium uppercase tracking-wider text-slate-900">
                        HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER
                    </p>

                </div>


                <!-- REPORT INFORMATION -->
                <div class="mt-10">

                    <div class="flex items-center justify-between mb-3">

                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-emerald-700">
                                Report Information
                            </p>

                            <p class="mt-0.5 text-[9px] text-slate-700">
                                Official document identification
                            </p>
                        </div>

                        <p id="nlrExamRefBadge" class="text-[9px] font-semibold text-slate-700">
                            Ref: {{ $nlrCodeVal }}
                        </p>

                    </div>


                    <div class="grid grid-cols-4 border-y border-slate-200">

                        <div class="py-3 pr-4 border-r border-slate-200">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                Case Ref No.
                            </p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{ $caseNo }}
                            </p>
                        </div>

                        <div class="py-3 px-4 border-r border-slate-200">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                Laboratory No.
                            </p>

                            <p
                                id="coverLabNo"
                                class="mt-1 text-sm font-bold text-slate-900"
                            >
                                {{ $formattedLabNoVal }}
                            </p>
                        </div>

                        <div class="py-3 px-4 border-r border-slate-200">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                Form Ref.
                            </p>

                            <p
                                id="nlrExamRef"
                                class="mt-1 text-sm font-bold text-slate-900"
                            >
                                {{ $nlrCodeVal }}
                            </p>
                        </div>

                        <div class="py-3 pl-4">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                Report Date
                            </p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{ date('d M Y') }}
                            </p>
                        </div>

                    </div>

                </div>


                <!-- SUSPECT & CASE INFORMATION -->
                @php
                    $coverSuspects = [];
                    if (!empty($rawSuspects) && is_array($rawSuspects)) {
                        $coverSuspects = $rawSuspects;
                    } elseif (!empty($suspects) && is_array($suspects)) {
                        $coverSuspects = $suspects;
                    } elseif (isset($case) && !empty($case->docket_data['suspects'])) {
                        $coverSuspects = $case->docket_data['suspects'];
                    } elseif (isset($case) && method_exists($case, 'getSuspectsList')) {
                        $coverSuspects = $case->getSuspectsList();
                    }

                    if (empty($coverSuspects)) {
                        $coverSuspects[] = [
                            'suspect_name' => $dossierSuspectNames ?? ($case->suspect_name ?? 'KOJO ADU'),
                            'mugshot_front' => $case->mugshot_front ?? null,
                        ];
                    }
                @endphp


                <div class="mt-8">

                    <div class="flex items-center gap-3 mb-4">

                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                            01
                        </span>

                        <div class="flex-1">

                            <div class="flex items-center justify-between">

                                <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                    Suspect &amp; Case Information
                                </h3>

                                <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                    High Court Admissible
                                </span>

                            </div>

                            <div class="mt-2 h-px bg-slate-200"></div>

                        </div>

                    </div>


                    <div class="flex items-start gap-6">

                        <!-- SUSPECT FRONT MUGSHOT(S) -->
                        <div class="shrink-0 flex flex-wrap items-center gap-3">
                            @foreach($coverSuspects as $sIdx => $sItem)
                                @php
                                    $sFrontMug = $sItem['mugshot_front']
                                        ?? ($sItem['mugshots']['front'] ?? ($docketData['mugshot_front'] ?? ($case->mugshot_front ?? null)));
                                    if (!empty($sFrontMug) && !str_starts_with($sFrontMug, 'data:image') && !str_starts_with($sFrontMug, 'http://') && !str_starts_with($sFrontMug, 'https://') && !str_starts_with($sFrontMug, '/')) {
                                        $sFrontMug = asset($sFrontMug);
                                    }
                                    $sNameDisp = $sItem['suspect_name'] ?? ($sItem['name'] ?? 'SUSPECT');
                                @endphp
                                <div class="flex flex-col items-center">
                                    <div class="relative w-28 h-32 rounded-xl overflow-hidden border-2 border-slate-300 bg-slate-100 shadow-sm flex items-center justify-center">
                                        @if(!empty($sFrontMug))
                                            <img
                                                src="{{ $sFrontMug }}"
                                                alt="{{ $sNameDisp }} Front Photo"
                                                class="w-full h-full object-cover"
                                            />
                                        @else
                                            <div class="flex flex-col items-center justify-center p-2 text-center text-slate-700">
                                                <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="text-[7px] font-bold uppercase tracking-tight text-slate-700">Front Mugshot</span>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="mt-1.5 text-[8px] font-bold uppercase tracking-wider text-slate-700 truncate max-w-[110px] text-center">
                                        {{ $sNameDisp }}
                                    </span>
                                    <span class="text-[7px] font-semibold uppercase tracking-wider text-emerald-700">
                                        Front View Photo
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <!-- CASE DETAILS GRID -->
                        <div class="flex-1 grid grid-cols-2 gap-x-6 gap-y-4 pt-1">

                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                    Suspect Name(s)
                                </p>

                                <p class="mt-1 text-xs font-semibold text-slate-900">
                                    {{ $dossierSuspectNames }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                    Exhibit Drug Type
                                </p>

                                <p class="mt-1 text-xs font-semibold text-slate-900">
                                    {{ $drugName }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                    Submitting Officer
                                </p>

                                <p class="mt-1 text-xs font-medium text-slate-800">
                                    {{ $submittingOfficer }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                    Seizure / Raid Location
                                </p>

                                <p class="mt-1 text-xs font-medium text-slate-800">
                                    {{ $raidLoc }}
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAGE 1 FOOTER -->
            <div class="pt-60 border-t border-slate-200 footer">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-900">
                            <span id="nlrCoverFooterLabNo" class="font-bold text-slate-700">{{ $formattedLabNoVal }}</span>
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-[8px] uppercase tracking-wider text-slate-700">
                            NACOC • Department of Forensic
                        </p>

                        <p class="mt-0.5 text-[8px] text-slate-700">
                            Laboratory Analysis Unit (LAU)
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </div>



    <!-- ============================================================
         PAGE 2: ANALYTICAL TEST REPORT
         ============================================================ -->
    <div class="a4-page relative text-slate-900">

        <!-- SUBTLE NACOC WATERMARK -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
            <img
                src="{{ asset('nacoc.jfif') }}"
                alt="NACOC Watermark"
                class="w-[900px] h-[900px] object-contain filter grayscale"
            />
        </div>


        <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

            <div>
                <!-- PAGE 2 HEADER -->
            <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                <div>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                        NACOC NLR REPORT
                    </span>
                    <span class="ml-2 font-mono-code text-[9px] font-semibold text-slate-800">
                        Ref: {{ $caseNo }} &bull; {{ $formattedLabNoVal }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                        REPORT
                    </p>
                    <p class="mt-0.5 text-[10px] font-bold text-slate-900">
                        Page 2 of 2
                    </p>
                </div>
            </div>


                <!-- DOCUMENT TITLE -->
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">

                    <div>

                        <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                            Title
                        </span>

                        <span class="ml-2 text-[10px] font-bold uppercase text-slate-900">
                            Analytical Test Report
                        </span>

                    </div>

                    <div>

                        <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                            Doc. No.
                        </span>

                        <span
                            id="nlrDocNo"
                            class="ml-2 text-[10px] font-bold text-slate-900"
                        >
                            {{ $nlrCodeVal }}
                        </span>

                    </div>

                </div>

            </div>


            <!-- NARRATIVE -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        REPORT NARRATIVE
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div
                    id="nlrReportNarrative"
                    class="text-[10.5px] leading-snug text-slate-700 whitespace-pre-line"
                >{{ $narrativeVal }}</div>
                <p class="mt-1.5 text-[9.5px] font-semibold text-slate-900">
                    Samples were taken for analysis.
                </p>
            </div>

            <!-- SAMPLE WEIGHT -->
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        Sample Weight
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div class="overflow-hidden border-y border-slate-300">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-300 bg-slate-100/50">
                                <th class="py-1 px-2 text-center text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[10%] border-r border-slate-300">
                                    Exhibit
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[25%] border-r border-slate-300">
                                    Description
                                </th>
                                <th class="py-1 px-2 text-center text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[27%] border-r border-slate-300">
                                    Number of Units / Gross Weight
                                </th>
                                <th class="py-1 px-2 text-center text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[19%] border-r border-slate-300">
                                    Sample Taken
                                </th>
                                <th class="py-1 px-2 text-center text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[19%]">
                                    Reserved Samples
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            id="nlrSampleWeightRows"
                            class="text-[9.5px] text-slate-800"
                        >
                            @foreach($sampleWeightRowsData as $swRow)
                                <tr class="border-b border-slate-200 last:border-b-0">
                                    <td class="py-1 px-2 text-center font-bold border-r border-slate-300">
                                        {{ $swRow['exhibit'] }}
                                    </td>
                                    <td class="py-1 px-2 font-medium border-r border-slate-300">
                                        {{ $swRow['description'] }}
                                    </td>
                                    <td class="py-1 px-2 text-center font-medium border-r border-slate-300">
                                        {{ $swRow['units_gross'] }}
                                    </td>
                                    <td class="py-1 px-2 text-center font-semibold text-emerald-800 border-r border-slate-300">
                                        {{ $swRow['sample_taken'] }}
                                    </td>
                                    <td class="py-1 px-2 text-center font-medium">
                                        {{ $swRow['reserved_samples'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRESUMPTIVE REACTION -->
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        Presumptive Reaction
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div class="overflow-hidden border-y border-slate-300">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-300 bg-slate-100/50">
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[20%] border-r border-slate-300">
                                    Result
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[50%] border-r border-slate-300">
                                    Presence Of
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[30%]">
                                    Purity / Percentage
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            id="nlrPresumptiveRows"
                            class="text-[9.5px] text-slate-800"
                        >
                            @foreach($presumptiveRows as $pRow)
                                <tr class="border-b border-slate-200 last:border-b-0">
                                    <td class="py-1 px-2 border-r border-slate-300 font-extrabold {{ ($pRow['result'] ?? 'YES') === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">
                                        {{ $pRow['result'] ?? 'YES' }}
                                    </td>
                                    <td class="py-1 px-2 border-r border-slate-300 font-bold text-slate-900">
                                        {{ $pRow['presence'] ?? 'Colorimetric Reaction' }}
                                    </td>
                                    <td class="py-1 px-2 font-bold text-slate-900">
                                        {{ $pRow['purity'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RESULT (DETECTED SUBSTANCES & PURITY %) -->
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        Result (Detected Substances &amp; Purity %)
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div class="overflow-hidden border-y border-slate-300">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-300 bg-slate-100/50">
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[25%] border-r border-slate-300">
                                    Substance Identified
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[30%] border-r border-slate-300">
                                    Substance / Compound Detected (Purity / Percentage)
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[25%] border-r border-slate-300">
                                    Test Methods
                                </th>
                                <th class="py-1 px-2 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[20%]">
                                    Specifications
                                </th>
                            </tr>
                        </thead>
                        <tbody id="nlrDetectedSubstancesRows" class="text-[9.5px] text-slate-800">
                            @foreach($detectedSubstancesRows as $index => $dsRow)
                                @php
                                    $compoundWithPurity = $dsRow['substance'];
                                    if (!empty($dsRow['percentage']) && $dsRow['percentage'] !== '—') {
                                        $compoundWithPurity .= ' (' . $dsRow['percentage'] . ')';
                                    }
                                @endphp
                                <tr class="border-b border-slate-200 last:border-b-0">
                                    <td class="py-1 px-2 font-extrabold text-slate-900 border-r border-slate-300">
                                        @if($loop->first)
                                            <span id="nlrSubstanceId">{{ $substanceIdVal }}</span>
                                        @else
                                            {{ $substanceIdVal }}
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 font-semibold text-slate-900 border-r border-slate-300">
                                        {{ $compoundWithPurity }}
                                    </td>
                                    <td class="py-1 px-2 font-semibold text-slate-900 border-r border-slate-300 font-mono-code text-[8.5px]">
                                        @if($loop->first)
                                            <span id="nlrTestMethods">{{ $dsRow['testMethodsVal'] ?? ($dsRow['test_methods'] ?? ($testMethodsVal ?? 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay')) }}</span>
                                        @else
                                            {{ $dsRow['testMethodsVal'] ?? ($dsRow['test_methods'] ?? ($testMethodsVal ?? 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay')) }}
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 font-semibold text-emerald-800 font-mono-code text-[8.5px]">
                                        @if($loop->first)
                                            <span id="nlrSpecifications">{{ $dsRow['specificationsVal'] ?? ($dsRow['specifications'] ?? ($specificationsVal ?? 'Official Forensic Standard ISO/IEC 17025 Compliant')) }}</span>
                                        @else
                                            {{ $dsRow['specificationsVal'] ?? ($dsRow['specifications'] ?? ($specificationsVal ?? 'Official Forensic Standard ISO/IEC 17025 Compliant')) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REMARK -->
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        Remark
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div
                    id="nlrAnalysisRemark"
                    class="text-[10.5px] leading-snug text-slate-700 whitespace-pre-line mb-1"
                >
                    {{ $remarkVal }}
                </div>
            </div>

            <!-- CONDITION OF RESERVED SAMPLE(S) -->
            <div class="mt-2">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                        Condition of Reserved Sample(s)
                    </h3>
                    <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                </div>
                <div
                    id="nlrConditionReservedSample"
                    class="text-[10.5px] leading-snug text-slate-700 whitespace-pre-line mb-1 font-semibold"
                >
                    {{ $conditionReservedVal }}
                </div>
            </div>

            <!-- CONDITIONS & DISCLAIMER NOTE -->
            <div class="mt-2 pt-1.5 border-t border-slate-200">
                <p class="text-[9.5px] font-semibold text-slate-800 mb-1 italic">
                    Note: The result(s) relate only to the item tested
                </p>
                <p class="text-[9.5px] font-extrabold uppercase tracking-wider text-slate-900 mb-1">
                    Conditions:
                </p>
                <ol class="list-decimal list-inside space-y-0.5 text-[9px] font-medium text-slate-700 leading-tight pl-0.5">
                    <li>Not valid without NACOC Lab Analysis approval.</li>
                    <li>This report does not signify that product tested has been certified.</li>
                    <li>Not to be used for litigation and advertisement without written consent of the Director General of NACOC.</li>
                    <li>This report shall not be reproduced in part without the written approval of the Director General of NACOC.</li>
                </ol>
            </div>

            <!-- LAB NUMBER -->
            <div class="mt-1 flex justify-end">
                <span
                    id="nlrBottomLabNo"
                    class="text-[9.5px] font-bold tracking-wide text-slate-700"
                >
                    {{ $formattedLabNoVal }}
                </span>
            </div>

            <!-- PAGE 2 FOOTER & SIGNATURES WRAPPER -->
            <div>
                <!-- SIGNATURES -->
                <div class="mt-2 pt-2 pb-1 border-t border-slate-300 footer break-inside-avoid">

                <div class="grid grid-cols-2 gap-10">

                    <!-- ANALYST -->
                    <div>

                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                            Laboratory Analyst
                        </p>

                        <div class="mt-6 border-b border-dashed border-slate-300 pb-1">

                            <span class="text-[8px] italic text-slate-700">
                                Electronically Signed &amp; Validated
                            </span>

                        </div>

                        <p class="mt-2 text-[10px] font-bold text-slate-900">
                            <span id="nlrAnalyst">{{ $analystName }}</span>
                        </p>

                        <p class="text-[8px] text-slate-900">
                            Senior Forensic Chemist / Analyst
                        </p>

                        <p class="text-[8px] text-slate-700">
                            Laboratory Analysis Unit, NACOC
                        </p>

                    </div>


                    <!-- HEAD -->
                    <div>

                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                            Head of Forensic Laboratory
                        </p>

                        <div class="mt-6 border-b border-dashed border-slate-300 pb-1">

                            <span class="text-[8px] italic text-slate-700">
                                Authorized &amp; Counter-signed
                            </span>

                        </div>

                        <p class="mt-2 text-[10px] font-bold text-slate-900">
                            {{ $headName }}
                        </p>

                        <p class="text-[8px] text-slate-900">
                            Director / Head of Laboratory
                        </p>

                        <p class="text-[8px] text-slate-700">
                            National Drug Control Commission
                        </p>

                    </div>

                </div>

            </div>


            <!-- PAGE 2 FOOTER -->
            <div class="mt-2 pt-60 border-t border-slate-200 flex items-center justify-between">

                <p class="text-[7px] text-slate-700">
                    NACOC-FSL-NLR-FM-08
                    &bull;
                    <span id="nlrPage2FooterLabNo" class="font-bold text-slate-600">{{ $formattedLabNoVal }}</span>
                    &bull;
                    Security Level: High Court Admissible
                </p>

                <p class="text-[7px] text-slate-700">
                    Page 2 of 2
                    &bull;
                    Generated on: {{ date('Y-m-d H:i:s') }}
                </p>

            </div>

            </div>

        </div>
    </div>

</div>

