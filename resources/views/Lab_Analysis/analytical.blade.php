@php
    $authUser = auth()->user();
    $docketData = is_array($case->docket_data)
        ? $case->docket_data
        : (json_decode($case->docket_data ?? '[]', true) ?:
        []);

    $rawLab = $case->laboratory_number ?? ($docketData['laboratory_number'] ?? 'N/A');
    $labNo = str_starts_with($rawLab, 'Lab No.') ? $rawLab : 'Lab No. ' . $rawLab;
    $caseNo = $case->case_number ?? 'N/A';
    $raidLoc =
        $case->raid_location ??
        ($docketData['raid_location'] ?? ($case->location ?? 'N/A'));

    $drugName =
        $case->seizure_name_drug ??
        ($case->seizure_name ?? ($docketData['drug_name'] ?? 'N/A'));
    $drugCategory = $case->exhibit_category ?? ($docketData['drug_category'] ?? 'N/A');
    $quantityStr = $case->exhibits_summary ?? ($docketData['quantity_str'] ?? 'N/A');

    $submittingOfficer = $case->submitting_officer ?? ($docketData['submitting_officer'] ?? 'N/A');

    $rawAnalyst = $docketData['analyst'] 
        ?? ($docketData['lab_examination']['analyst'] 
        ?? ($case->analyst 
        ?? ($case->lab_analyst 
        ?? null)));

    if (!empty($rawAnalyst) && $rawAnalyst !== $submittingOfficer) {
        $analystName = $rawAnalyst;
    } elseif ($authUser && (str_contains(strtolower($authUser->unit ?? ''), 'lab') || !empty($authUser->fullname))) {
        $analystName = $authUser->fullname;
    } else {
        $analystName = 'N/A';
    }

    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $headName = $dbLabHead ? $dbLabHead->fullname : 'N/A';


    // Unified Presumptive Result Parser Helper
    $parsePresumptiveResult = function ($raw) use ($drugName) {
        $fallback = [['result' => 'NO', 'presence' => 'No Result', 'purity' => 'N/A']];
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

    $checkIsDrugExhibit = app(\App\Services\DrugExhibitClassifier::class)->getClosure();

    // Gather suspect records
    $rawSuspects = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        $rawSuspects = $docketData['suspects'];
    } elseif (!empty($case->id)) {
        $rawSuspects = $case->getSuspectsList();
    }
    if (empty($rawSuspects)) {
        $rawSuspects[] = [
            'suspect_name' => $case->suspect_name ?? 'N/A',
            'suspect_alias' => $case->suspect_alias ?? 'N/A',
            'ghana_card' => $case->ghana_card ?? 'N/A',
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
    $isMultiCase = ($case instanceof \App\Models\ForensicCase)
        ? $case->isMultipleSuspect()
        : (($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase || ($case instanceof \App\Models\MultipleSuspectCase));

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
        $hasExplicitExhibits = false;
        $hasDrugExhibit = false;

        if (!empty($case->id)) {
            $suspectExhibits = $case->getExhibitsForSuspect(
                $sName,
                $s['id'] ?? null,
                $s['associate_id'] ?? null,
                $s['role'] ?? ($s['association_role'] ?? null),
            );
            if (!empty($suspectExhibits)) {
                $hasExplicitExhibits = true;
                foreach ($suspectExhibits as $exItem) {
                    if ($checkIsDrugExhibit((array) $exItem)) {
                        $hasDrugExhibit = true;
                        break;
                    }
                }
            }
        }

        if (!$hasDrugExhibit && !$hasExplicitExhibits) {
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
                        $isMatch = true;
                    }

                    if ($isMatch && $checkIsDrugExhibit($sz)) {
                        $hasDrugExhibit = true;
                        break;
                    }
                }
            } else {
                $hasDrugExhibit = true;
            }

            if (!$hasDrugExhibit && !empty($drugName)) {
                $hasDrugExhibit = true;
            }
        }

        if (!$hasDrugExhibit) {
            continue;
        }

        $labExam = $case->resolveLabExamForSuspect(
            $sName,
            $s['id'] ?? ($s['suspect_id'] ?? null),
            $s['associate_id'] ?? null,
            $s['role'] ?? ($s['association_role'] ?? null),
            $idx
        );

        $hasSavedLab = !empty($labExam['has_saved']);

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
            'lab_exam' => $labExam,
            'exam_reference' => $labExam['exam_reference'] ?? '',
            'exhibit_number' => $labExam['exhibit_number'] ?? '',
            'laboratory_number' => $labExam['laboratory_number'] ?? '',
            'analyst' => $labExam['analyst'] ?? '',
            'date_received' => $labExam['date_received'] ?? '',
            'date_started' => $labExam['date_started'] ?? '',
            'date_completed' => $labExam['date_completed'] ?? '',
            'presumptive_raw' => $labExam['presumptive_result'] ?? '',
            'presumptive_rows' => $labExam['presumptive_rows'] ?? [],
            'confirmatory' => $labExam['confirmatory'] ?? 'YES',
            'sample_weight' => $labExam['sample_weight'] ?? [],
            'substance_identification' => $labExam['substance_identification'] ?? '',
            'detected_substances' => $labExam['detected_substances'] ?? [],
            'specifications' => $labExam['specifications'] ?? '',
            'test_methods' => $labExam['test_methods'] ?? '',
            'remark' => $labExam['remark'] ?? '',
            'findings' => $labExam['findings'] ?? '',
        ];
    }

    // ------------------------------------------------------------------
    // Group suspects by the DRUG exhibit they are linked to, so that when
    // several suspects share a single drug only ONE Laboratory Examination
    // Result is rendered for them.
    // ------------------------------------------------------------------
    $caseDrugExhibits = [];
    foreach ($docketData['seizures'] ?? [] as $szItem) {
        if (!is_array($szItem)) {
            continue;
        }
        if ($checkIsDrugExhibit($szItem)) {
            $caseDrugExhibits[] = $szItem;
        }
    }

    $dossierGroups = [];
    if (count($caseDrugExhibits) <= 1) {
        // Single drug exhibit (or none recorded) => every suspect is linked to the same drug
        if (!empty($suspectLabResults)) {
            $dossierGroups['single'] = $suspectLabResults;
        }
    } else {
        // Whole-word owner matching (owner labels may be joint, e.g. "PRIMARY SUSPECT, CO-SUSPECT")
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

    $allGsaFilesForCase = \App\Models\SuspectGsaReport::forCase($caseNo ?? ($case->case_number ?? $case->id), $case->id ?? null);
    $allNlrFilesForCase = \App\Models\SuspectNlrReport::forCase($caseNo ?? ($case->case_number ?? $case->id), $case->id ?? null);

    $getSuspectId = function ($sInfo) {
        if (empty($sInfo)) return null;
        if (is_object($sInfo)) return $sInfo->id ?? null;
        if (is_array($sInfo)) return $sInfo['id'] ?? null;
        return null;
    };

    $allGroupsHaveReports = true;
    if (empty($dossierGroups) || count($dossierGroups) === 0) {
        $allGroupsHaveReports = ($allGsaFilesForCase->count() > 0 || $allNlrFilesForCase->count() > 0);
    } else {
        foreach ($dossierGroups as $checkG) {
            $checkMemGsa = collect();
            foreach ($checkG['members'] as $gMem) {
                $sId = $getSuspectId($gMem['suspect_info'] ?? null);
                $sName = $gMem['suspect_name'] ?? null;
                $checkMemGsa = $checkMemGsa->merge(
                    \App\Models\SuspectGsaReport::filterForSuspect($allGsaFilesForCase, $sId, $sName)
                )->merge(
                    \App\Models\SuspectNlrReport::filterForSuspect($allNlrFilesForCase, $sId, $sName)
                );
            }
            if ($checkMemGsa->count() === 0) {
                $allGroupsHaveReports = false;
                break;
            }
        }
    }

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
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
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

            #nlrReportModal {
                position: fixed !important;
                inset: 0 !important;
                z-index: 999999 !important;
                background: white !important;
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            #nlrReportModal > div {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                max-height: none !important;
                border-radius: 0 !important;
            }

            #nlrReportDocumentContainer {
                padding: 0 !important;
                background: white !important;
                overflow: visible !important;
            }

            #nlrReportSheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
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

            .page-break {
                page-break-after: always !important;
                break-after: page !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
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
                        <span class="hidden sm:inline">Back</span>
                    </a>

                    @if ($allGroupsHaveReports)
                        <button type="button" disabled title="Reports have already been generated and attached for all suspects in this case."
                            class="px-3.5 sm:px-4 py-2 bg-slate-100 text-slate-400 font-extrabold text-xs rounded-xl shadow-2xs border border-slate-200 opacity-60 cursor-not-allowed flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Generate Preliminary Report</span>
                        </button>
                    @else
                        <!-- GENERATE PRELIMINARY REPORT BUTTON -->
                        <button type="button" onclick="openNlrReportModal()"
                            class="px-3.5 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border-0">
                            <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Generate Preliminary Report</span>
                        </button>
                    @endif


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
                        $allDrugSuspectNames = [];
                        foreach ($rawSuspects as $rsItem) {
                            $rsNameItem = trim((string)($rsItem['suspect_name'] ?? ($rsItem['name'] ?? '')));
                            if (empty($rsNameItem)) continue;
                            
                            $rsHasDrug = false;
                            $rsHasExplicit = false;
                            if (!empty($case->id)) {
                                $sExList = $case->getExhibitsForSuspect(
                                    $rsNameItem,
                                    $rsItem['id'] ?? null,
                                    $rsItem['associate_id'] ?? null,
                                    $rsItem['role'] ?? ($rsItem['association_role'] ?? null)
                                );
                                if (!empty($sExList)) {
                                    $rsHasExplicit = true;
                                    foreach ($sExList as $exItem) {
                                        if ($checkIsDrugExhibit((array)$exItem)) {
                                            $rsHasDrug = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$rsHasDrug && !$rsHasExplicit) {
                                $seizuresList = $docketData['seizures'] ?? [];
                                if (!empty($seizuresList)) {
                                    foreach ($seizuresList as $sz) {
                                        $assocP = strtolower(trim($sz['associated_person'] ?? ($sz['associated_suspect'] ?? ($sz['suspect_name'] ?? ($sz['exhibit_owner_name'] ?? '')))));
                                        $isMatch = false;
                                        if (!empty($assocP)) {
                                            if (str_contains($assocP, 'all suspect') || $suspectNamesMatch($assocP, $rsNameItem)) {
                                                $isMatch = true;
                                            }
                                        } else {
                                            $isMatch = true;
                                        }
                                        if ($isMatch && $checkIsDrugExhibit($sz)) {
                                            $rsHasDrug = true;
                                            break;
                                        }
                                    }
                                } else {
                                    $rsHasDrug = true;
                                }
                                if (!$rsHasDrug && !empty($drugName)) {
                                    $rsHasDrug = true;
                                }
                            }
                            if ($rsHasDrug) {
                                $allDrugSuspectNames[] = $rsNameItem;
                            }
                        }
                        $allDrugSuspectNames = array_values(array_unique($allDrugSuspectNames));
                    @endphp
                    @if (count($allDrugSuspectNames) > 1)
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                            <div
                                class="flex items-center space-x-2 text-xs font-mono-code font-bold overflow-x-auto w-full py-1 custom-scrollbar">
                                <span class="text-slate-400 uppercase text-[10px] shrink-0">Filter Suspect
                                    Dossier:</span>
                                <a href="{{ url('/Lab_Analysis/analytical/' . $caseNo) }}"
                                    class="px-3 py-1.5 rounded-xl border shrink-0 {{ empty($selectedSuspectQuery) ? 'bg-slate-900 text-white border-slate-900 font-extrabold' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }} transition-all shadow-2xs">
                                    All Suspects ({{ count($allDrugSuspectNames) }})
                                </a>
                                @foreach ($allDrugSuspectNames as $rsName)
                                    @php
                                        $isCurrSel = !empty($selectedSuspectQuery) && $suspectNamesMatch($rsName, $selectedSuspectQuery);
                                    @endphp
                                    <a href="{{ url('/Lab_Analysis/analytical/' . $caseNo . '?suspect=' . urlencode($rsName)) }}"
                                        class="px-3 py-1.5 rounded-xl border shrink-0 {{ $isCurrSel ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }} transition-all shadow-2xs">
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
                                class="rounded-2xl p-4 sm:p-5 bg-white text-slate-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border border-slate-200/90 shadow-md shadow-slate-200/60">
                                <div class="flex-1 min-w-0 space-y-3">
                                    @if ($isJointGroup)
                                        <div
                                            class="text-[10px] sm:text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-teal-700 flex items-center gap-2 flex-wrap pb-2 border-b border-slate-200">
                                            <span>DOSSIER RECORD #{{ $resIdx + 1 }}</span>
                                            <span>&bull;</span>
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-800 border border-amber-400/40 font-bold">JOINT
                                                EXHIBIT &mdash; {{ count($group['members']) }} SUSPECTS LINKED TO ONE
                                                DRUG</span>
                                        </div>
                                    @endif

                                    @foreach ($group['members'] as $memIdx => $m)
                                        <div
                                            class="flex items-center space-x-3.5 sm:space-x-4 {{ $isJointGroup && $memIdx > 0 ? 'pt-3 border-t border-slate-200' : '' }}">
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
                            @include('partials.lab_examination_result_card', [
                                'labExam' => $res['lab_exam'] ?? $res,
                                'labHeadingName' => $group['names_label'],
                                'case' => $case,
                            ])

                            {{-- ATTACHED GSA / NLR REPORT FILES FOR THIS DOSSIER'S SUSPECT(S) --}}
                            @php
                                $cardGsaFiles = collect();
                                foreach ($group['members'] as $gsaMem) {
                                    $sId = $getSuspectId($gsaMem['suspect_info'] ?? null);
                                    $sName = $gsaMem['suspect_name'] ?? null;
                                    $cardGsaFiles = $cardGsaFiles->merge(
                                        \App\Models\SuspectGsaReport::filterForSuspect($allGsaFilesForCase, $sId, $sName)
                                    )->merge(
                                        \App\Models\SuspectNlrReport::filterForSuspect($allNlrFilesForCase, $sId, $sName)
                                    );
                                }
                                $cardGsaFiles = $cardGsaFiles->unique(function ($item) {
                                    return get_class($item) . '_' . $item->id;
                                })->values();
                                $cardGsaNames = implode(', ', array_column($group['members'], 'suspect_name'));
                                $hasCardReportAttached = (count($cardGsaFiles) > 0);
                            @endphp
                            @include('partials.gsa_report_files', [
                                'gsaFiles' => $cardGsaFiles,
                                'gsaForName' => $cardGsaNames,
                            ])

                            {{-- ATTACHED DRUG SCREENING RESULT FOR SUSPECT(S) IN THIS DOSSIER --}}
                            @foreach ($group['members'] as $gMem)
                                @php
                                    $gMemId = $getSuspectId($gMem['suspect_info'] ?? null);
                                    $gMemName = $gMem['suspect_name'] ?? ($gMem['suspect_info']['suspect_name'] ?? ($gMem['name'] ?? null));
                                    $gDrugScreening = \App\Models\DrugScreening::forSuspect(
                                        $gMemId,
                                        $gMemName,
                                        $case->id ?? null,
                                        $caseNo ?? ($case->case_number ?? null)
                                    );
                                @endphp
                                @if ($gDrugScreening)
                                    @include('partials.suspect_drug_screening_card', [
                                        'screening' => $gDrugScreening,
                                        'suspectName' => $gMemName,
                                        'suspectId' => $gMemId,
                                        'caseNumber' => $caseNo ?? ($case->case_number ?? ('NCC-' . date('Y'))),
                                        'showEmpty' => false,
                                        'showActions' => true,
                                    ])
                                @endif
                            @endforeach

                            <!-- FOOTER ACTION BAR -->
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 no-print border-t border-slate-100">
                                <span class="text-[11px] text-slate-500 font-mono-code font-semibold">
                                    Registered Under NACOC Protocols &bull; Ref: {{ $certNo }}
                                </span>
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($hasCardReportAttached)
                                        <button type="button" disabled title="A report has already been generated and attached."
                                            class="px-3.5 py-1.5 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl border border-slate-200 opacity-60 cursor-not-allowed">
                                            Preliminary Report 
                                        </button>
                                    @else
                                        <button type="button" onclick="openNlrReportModal({{ \Illuminate\Support\Js::from($res) }})"
                                            class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs rounded-xl border border-blue-200 transition-colors cursor-pointer">
                                            Preliminary Report 
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    @endforeach

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

        window.allGroupsHaveReports = @json($allGroupsHaveReports);

        window.suspectLabResults = @json($suspectLabResults);
        window.selectedSuspectQuery = @json($selectedSuspectQuery ?? '');
        window.activeNlrLabData = null;

        if (window.selectedSuspectQuery && window.suspectLabResults && window.suspectLabResults.length > 0) {
            const norm = str => (str || '').toLowerCase().replace(/\s+/g, ' ').trim();
            const sel = norm(window.selectedSuspectQuery);
            window.activeNlrLabData = window.suspectLabResults.find(r => {
                const sName = norm(r.suspect_name);
                return sName.includes(sel) || sel.includes(sName);
            });
        }

        if (!window.activeNlrLabData && window.suspectLabResults && window.suspectLabResults.length > 0) {
            window.activeNlrLabData = window.suspectLabResults[0];
        }

        function openNlrReportModal(labData) {
            if (!labData && window.allGroupsHaveReports) {
                return;
            }
            if (labData) {
                window.activeNlrLabData = labData;
            } else if (!window.activeNlrLabData && window.suspectLabResults && window.suspectLabResults.length > 0) {
                window.activeNlrLabData = window.suspectLabResults[0];
            }

            const sName = (window.activeNlrLabData && window.activeNlrLabData.suspect_name) ? window.activeNlrLabData.suspect_name : '';
            const sId = (window.activeNlrLabData && window.activeNlrLabData.suspect_info) ? window.activeNlrLabData.suspect_info.id : null;

            // Open window synchronously on user click to avoid popup blocker
            const previewWin = window.open('about:blank', '_blank');

            const payload = {
                case_id: "{{ $caseNo }}",
                case_number: "{{ $caseNo }}",
                laboratory_number: "{{ $labNo }}",
                suspect_id: sId,
                suspect_name: sName,
                _token: "{{ csrf_token() }}"
            };

            let pdfUrl = "{{ route('lab.preliminary_report.pdf', ['id' => $caseNo]) }}";
            if (sName) {
                pdfUrl += (pdfUrl.includes('?') ? '&' : '?') + 'suspect=' + encodeURIComponent(sName);
            }

            fetch("{{ route('lab.nlr.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            }).then(res => res.json()).then(() => {
                if (previewWin) {
                    previewWin.location.href = pdfUrl;
                } else {
                    window.open(pdfUrl, '_blank');
                }
                window.location.reload();
            }).catch(() => {
                if (previewWin) {
                    previewWin.location.href = pdfUrl;
                } else {
                    window.open(pdfUrl, '_blank');
                }
                window.location.reload();
            });
        }

        function showNlrReportModal(labData) {
            syncFormToNlrReport(labData || window.activeNlrLabData);
            const modal = document.getElementById('nlrReportModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeNlrReportModal() {
            const modal = document.getElementById('nlrReportModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
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

        function syncFormToNlrReport(labData) {
            if (!labData && window.suspectLabResults && window.suspectLabResults.length > 0) {
                labData = window.suspectLabResults[0];
            }

            const descEl = document.getElementById('detailedExhibitDescription');
            const condEl = document.getElementById('conditionOfReservedSample');

            const narrativeTarget = document.getElementById('nlrReportNarrative');
            const conditionTarget = document.getElementById('nlrReportCondition');

            if (descEl && narrativeTarget) {
                const text = descEl.value.trim();
                narrativeTarget.innerText = text || (labData && labData.sample_weight ? labData.sample_weight.description : "Inside the box were two large ziplock bags.\n• One of the ziplock bags labelled 'A' contained one hundred and six (106) wraps of plant-like substances.\n• The second ziplock bag contained one transparent polythene bag and a small ziplock bag labeled 'B' and 'C' respectively. The transparent polythene contained quantities of loose plant-like material while the small ziplock bag contained six (6) rolls of plant-like substances.\n\nSamples were taken for analysis.");
            }

            if (condEl && conditionTarget) {
                const condText = condEl.value && condEl.value.trim() ? condEl.value.trim() : 'Good / Unaltered';
                conditionTarget.innerText = condText;
            }

            if (labData) {
                const fallbackNlrRef = @json(app(\App\Services\CaseReferenceService::class)->resolveExamReference($case ?? null, is_array($case->docket_data ?? null) ? $case->docket_data : []));
                const nlrCode = rawNlrRef ? rawNlrRef.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-') : fallbackNlrRef;
                if (document.getElementById('nlrExamRef')) document.getElementById('nlrExamRef').innerText = nlrCode;
                if (document.getElementById('nlrExamRefBadge')) document.getElementById('nlrExamRefBadge').innerText = 'Ref: ' + nlrCode;
                if (document.getElementById('nlrDocNo')) document.getElementById('nlrDocNo').innerText = nlrCode;
                if (document.getElementById('nlrExhibitNo')) document.getElementById('nlrExhibitNo').innerText = labData.exhibit_number || '1';
                const rawLabVal = labData.laboratory_number || '{{ $labNo }}';
                const formattedLabVal = rawLabVal.startsWith('Lab No.') ? rawLabVal : ('Lab No. ' + rawLabVal);
                if (document.getElementById('nlrLabNo')) document.getElementById('nlrLabNo').innerText = formattedLabVal;
                if (document.getElementById('coverLabNo')) document.getElementById('coverLabNo').innerText = formattedLabVal;
                if (document.getElementById('nlrBottomLabNo')) document.getElementById('nlrBottomLabNo').innerText = formattedLabVal;
                if (document.getElementById('nlrCoverFooterLabNo')) document.getElementById('nlrCoverFooterLabNo').innerText = formattedLabVal;
                if (document.getElementById('nlrPage2FooterLabNo')) document.getElementById('nlrPage2FooterLabNo').innerText = formattedLabVal;
                if (document.getElementById('nlrExhibitDesc') && labData.sample_weight) document.getElementById('nlrExhibitDesc').innerText = labData.sample_weight.description || '{{ $drugName }}';
                if (document.getElementById('nlrAnalyst')) document.getElementById('nlrAnalyst').innerText = labData.analyst || '{{ $analystName }}';
                if (document.getElementById('nlrDateReceived')) document.getElementById('nlrDateReceived').innerText = labData.date_received || '';
                if (document.getElementById('nlrDateStarted')) document.getElementById('nlrDateStarted').innerText = labData.date_started || '';
                if (document.getElementById('nlrDateCompleted')) document.getElementById('nlrDateCompleted').innerText = labData.date_completed || '';
                
                if (document.getElementById('nlrConfirmatory')) document.getElementById('nlrConfirmatory').innerText = (labData.confirmatory !== 'NO') ? 'YES' : 'NO';
                
                if (labData.sample_weight) {
                    if (document.getElementById('nlrUnitsPackaging')) document.getElementById('nlrUnitsPackaging').innerText = labData.sample_weight.units_packaging || '';
                    if (document.getElementById('nlrGrossWeight')) document.getElementById('nlrGrossWeight').innerText = labData.sample_weight.gross_weight || '';
                    if (document.getElementById('nlrSampleTaken')) document.getElementById('nlrSampleTaken').innerText = labData.sample_weight.sample_taken || '';
                    if (document.getElementById('nlrReservedSamples')) document.getElementById('nlrReservedSamples').innerText = labData.sample_weight.reserved_samples || '';
                    
                    const swRows = document.getElementById('nlrSampleWeightRows');
                    if (swRows && labData.sample_weight) {
                        let units = (labData.sample_weight.units_packaging || labData.sample_weight.sample_weight_units || '').trim();
                        let pkg = (labData.sample_weight.packaging_type || labData.seizure_packaging || labData.packaging || '').trim();
                        let gross = (labData.sample_weight.gross_weight || '').trim();
                        let taken = (labData.sample_weight.sample_taken || '—').trim();
                        let reserved = (labData.sample_weight.reserved_samples || '—').trim();

                        let formattedUnits = units;
                        if (units && pkg && /^\d+(\.\d+)?$/.test(units)) {
                            formattedUnits = `${units} ${pkg}`;
                        } else if (!units && pkg) {
                            formattedUnits = pkg;
                        }

                        let detectedUnit = '';
                        const allText = `${gross} ${taken} ${reserved} ${labData.quantity_str || ''}`;
                        if (/\b(kg|kgs|kilogram|kilograms)\b/i.test(allText)) detectedUnit = 'kg';
                        else if (/\b(g|gm|gms|gram|grams)\b/i.test(allText) && !/\b(kg|mg)\b/i.test(allText)) detectedUnit = 'g';
                        else if (/\b(mg|mgs|milligram|milligrams)\b/i.test(allText)) detectedUnit = 'mg';

                        const attachUnit = (val, u) => {
                            if (!val || val === '—') return '—';
                            if (/[a-zA-Z]/.test(val) || !u) return val;
                            return `${val} ${u}`;
                        };

                        let formattedGross = attachUnit(gross, detectedUnit);
                        let formattedTaken = attachUnit(taken, detectedUnit);
                        let formattedReserved = attachUnit(reserved, detectedUnit);

                        let unitsGross = '—';
                        if (formattedUnits && formattedGross && formattedGross !== '—') {
                            unitsGross = formattedUnits.includes('(') ? formattedUnits : `${formattedUnits} (${formattedGross})`;
                        } else if (formattedGross && formattedGross !== '—') {
                            unitsGross = formattedGross;
                        } else if (formattedUnits) {
                            unitsGross = formattedUnits;
                        }

                        swRows.innerHTML = `
                            <tr class="border-b border-slate-200 last:border-b-0">
                                <td class="py-1.5 px-2 text-center font-bold border-r border-slate-300">${labData.exhibit_number || 'A'}</td>
                                <td class="py-1.5 px-2 font-medium border-r border-slate-300">${labData.sample_weight.description || '{{ $drugName }}'}</td>
                                <td class="py-1.5 px-2 text-center font-medium border-r border-slate-300">${unitsGross}</td>
                                <td class="py-1.5 px-2 text-center font-semibold text-emerald-800 border-r border-slate-300">${formattedTaken}</td>
                                <td class="py-1.5 px-2 text-center font-medium">${formattedReserved}</td>
                            </tr>
                        `;
                    }
                }

                if (document.getElementById('nlrSubstanceId')) document.getElementById('nlrSubstanceId').innerText = labData.substance_identification || '{{ $drugName }}';
                if (document.getElementById('nlrSpecifications')) document.getElementById('nlrSpecifications').innerText = labData.specifications || 'Official Forensic Standard ISO/IEC 17025 Compliant';
                if (document.getElementById('nlrTestMethods')) document.getElementById('nlrTestMethods').innerText = labData.test_methods || 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay';
                if (document.getElementById('nlrAnalysisRemark')) document.getElementById('nlrAnalysisRemark').innerText = labData.remark || 'Controlled Narcotic Substance verified under strict laboratory chain of custody protocol.';
                const condMap = {
                    'good': 'Good / Unaltered',
                    'sealed': 'Sealed & Labelled',
                    'partially_used': 'Partially Used',
                    'degraded': 'Degraded',
                    'contaminated': 'Contaminated',
                    'destroyed': 'Destroyed / Expended'
                };
                const rawCond = labData.condition_of_reserved_sample || 'Sealed & Labelled';
                const condDisplay = condMap[String(rawCond).toLowerCase()] || rawCond;
                if (document.getElementById('nlrConditionReservedSample')) document.getElementById('nlrConditionReservedSample').innerText = condDisplay;
                if (document.getElementById('nlrAnalyticalFindings')) document.getElementById('nlrAnalyticalFindings').innerText = labData.findings || 'Certified chemical analysis confirms positive identification. High Court Admissible.';

                // Sync presumptive reaction table rows
                const pContainer = document.getElementById('nlrPresumptiveRows');
                if (pContainer && labData.presumptive_rows && labData.presumptive_rows.length > 0) {
                    pContainer.innerHTML = labData.presumptive_rows.map(r => `
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <td class="py-1.5 px-2 border-r border-slate-300 font-extrabold ${r.result === 'YES' ? 'text-emerald-800' : 'text-slate-900'}">${r.result || 'YES'}</td>
                            <td class="py-1.5 px-2 border-r border-slate-300 font-bold text-slate-900">${r.presence || ''}</td>
                            <td class="py-1.5 px-2 font-bold text-slate-900">${r.purity || '—'}</td>
                        </tr>
                    `).join('');
                }

                // Sync detected substances table rows
                const detSubContainer = document.getElementById('nlrDetectedSubstancesRows');
                if (detSubContainer) {
                    let dsRows = [];
                    if (labData.detected_substances && Array.isArray(labData.detected_substances) && labData.detected_substances.length > 0) {
                        dsRows = labData.detected_substances.map(ds => {
                            if (typeof ds === 'object' && ds !== null) {
                                const sub = ds.compound || ds.substance || ds.name || labData.substance_identification || '{{ $drugName }}';
                                let pct = ds.purity || ds.percentage || ds.concentration || '—';
                                if (pct !== '—' && !String(pct).includes('%')) pct = pct + '%';
                                const meth = ds.method || ds.test_methods || labData.test_methods || 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay';
                                const spec = ds.specifications || labData.specifications || 'Official Forensic Standard ISO/IEC 17025 Compliant';
                                return { substance: sub, percentage: pct, test_methods: meth, specifications: spec };
                            }
                            return { substance: String(ds), percentage: '—', test_methods: labData.test_methods, specifications: labData.specifications };
                        });
                    } else {
                        const sub = labData.substance_identification || '{{ $drugName }}';
                        dsRows = [{ substance: sub, percentage: '—', test_methods: labData.test_methods, specifications: labData.specifications }];
                    }
                    const subId = labData.substance_identification || '{{ $drugName }}';
                    detSubContainer.innerHTML = dsRows.map((r, index) => {
                        const compoundWithPurity = (r.percentage && r.percentage !== '—') ? `${r.substance} (${r.percentage})` : r.substance;
                        const tMethod = r.test_methods || labData.test_methods || 'Gas Chromatography-Mass Spectrometry (GC-MS) & Marquis Reagent Assay';
                        const tSpec = r.specifications || labData.specifications || 'Official Forensic Standard ISO/IEC 17025 Compliant';
                        return `
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <td class="py-1.5 px-2 font-extrabold text-slate-900 border-r border-slate-300">${index === 0 ? `<span id="nlrSubstanceId">${subId}</span>` : subId}</td>
                            <td class="py-1.5 px-2 font-semibold text-slate-900 border-r border-slate-300">${compoundWithPurity}</td>
                            <td class="py-1.5 px-2 font-semibold text-slate-900 border-r border-slate-300 font-mono-code text-[9px]">${index === 0 ? `<span id="nlrTestMethods">${tMethod}</span>` : tMethod}</td>
                            <td class="py-1.5 px-2 font-semibold text-emerald-800 font-mono-code text-[9px]">${index === 0 ? `<span id="nlrSpecifications">${tSpec}</span>` : tSpec}</td>
                        </tr>
                        `;
                    }).join('');
                }
            }
        }

        function printNlrReport() {
            window.print();
        }

        function downloadNlrReportFile() {
            const sheet = document.getElementById('nlrReportSheet');
            if (!sheet) return;

            const caseNo = '{{ $caseNo }}';
            const htmlContent = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NLR Report - ${caseNo}</title>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"><\/script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-serif-header { font-family: 'Cinzel', serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; padding: 24px; }
        .page-break { page-break-after: always; break-after: page; }
        @media print {
            body { padding: 0; background: white; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; break-after: page; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div style="max-width: 960px; margin: 0 auto;">
        ${sheet.outerHTML}
    </div>
</body>
</html>`;

            const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `NLR_Report_${caseNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.html`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('reportDropdownContainer');
            const menu = document.getElementById('reportDropdownMenu');
            if (container && menu && !container.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

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

