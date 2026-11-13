@php
    $authUser = auth()->user();

    // Data collections
    $drugList   = collect($drugExhibits ?? []);
    $assetList  = collect($assetExhibits ?? []);
    $moneyList  = collect($moneyExhibits ?? []);

    // Consolidate all drug log entries into a single unified table ($drugList)
    $dynamicGroups = collect($dynamicExhibitsByCategory ?? []);
    foreach ($dynamicGroups as $catName => $groupItems) {
        $lowerCat = strtolower(trim((string)$catName));
        if (in_array($lowerCat, ['drugs', 'drug', 'narcotics', 'narcotic'])) {
            $existingDrugIds = $drugList->pluck('id')->all();
            foreach ($groupItems as $item) {
                if (!in_array($item->id ?? null, $existingDrugIds)) {
                    $drugList->push($item);
                }
            }
            $dynamicGroups->forget($catName);
        }
    }

    $drugCount  = $drugList->count();
    $assetCount = $assetList->count();
    $moneyCount = $moneyList->count();

    // Signatories
    $isStaffGenerator = $authUser && !$authUser->is_head;
    $authOfficerName = trim((string)($authUser?->fullname ?? ($authUser?->name ?? ''))) ?: 'N/A';
    $evidenceOfficerName = $isStaffGenerator ? $authOfficerName : ($dbEvidenceHead ? $dbEvidenceHead->fullname : $authOfficerName);
    $evidenceAuthorityLabel = $isStaffGenerator ? 'PREPARED BY:' : 'VALIDATION AUTHORITY:';
    $evidenceSignatoryHeading = $isStaffGenerator ? 'PREPARED BY (STAFF OFFICER)' : 'HEAD OF EVIDENCE MANAGEMENT UNIT';
    $evidenceSignatoryRole = $isStaffGenerator ? 'Staff Officer, Evidence Management Unit' : 'Head of Evidence Management Unit';
    $evidenceSignatorySig = $isStaffGenerator ? ($authUser?->signature ?? null) : ($dbEvidenceHead?->signature ?? null);
    $deptHeadName = $dbDeptHead ? $dbDeptHead->fullname : 'N/A';
    $intelDirectorName = 'Name: ____________________';
    $reportGeneratedDate = now()->format('d F Y');

    // Report Period Labeling
    $rawPeriod = strtolower(trim((string)($period ?? 'general')));
    $hasCustomRange = !empty($fromDate) || !empty($toDate);

    $periodLabel = match(true) {
        $hasCustomRange => 'Period: ' . ($fromDate ? date('d F Y', strtotime($fromDate)) : 'Start') . ' – ' . ($toDate ? date('d F Y', strtotime($toDate)) : 'Present'),
        in_array($rawPeriod, ['weekly', 'week']) => 'Period: ' . now()->subDays(7)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['monthly', 'month']) => 'Period: ' . now()->subDays(30)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'Period: ' . now()->subYear()->format('d F Y') . ' – ' . now()->format('d F Y'),
        default => 'PERIOD: ALL REGISTERED EXHIBITS (CUMULATIVE)'
    };

    $reportTypeLabel = match(true) {
        in_array($rawPeriod, ['weekly', 'week']) => 'WEEKLY DRUG & SEIZED PROPERTY REPORT',
        in_array($rawPeriod, ['monthly', 'month']) => 'MONTHLY DRUG & SEIZED PROPERTY REPORT',
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'ANNUAL DRUG & SEIZED PROPERTY REPORT',
        default => $hasCustomRange ? 'DATE RANGE & SEIZED PROPERTY REPORT' : 'GENERAL SITUATIONAL REPORT'
    };

    // Height Budgeting for A4 Portrait (in millimeters) with footer clearance
    $firstPageBudget        = 230.0; // mm available on first body page
    $continuationPageBudget = 260.0; // mm available on continuation body pages

    $sec01HeaderH       = 28.0;  // Section 01 badge + title + counter + thead + table margin-top
    $sec01ContinuationH = 21.0;  // Continuation title + thead + table margin-top
    $sec02HeaderH       = 36.0;  // Section 02 badge + title + intro + thead + table margin-top
    $sec02ContinuationH = 21.0;  // Continuation title + thead + table margin-top
    $sec03HeaderH       = 36.0;  // Section 03 badge + title + intro + thead + table margin-top
    $sec03ContinuationH = 21.0;  // Continuation title + thead + table margin-top
    $secEmptyRowH       = 14.0;  // Empty section message row
    $sectionGapH        = 6.0;   // Vertical space between consecutive sections
    $signaturesH        = 48.0;  // 3 official physical signatures block

    // Row height estimators (strictly calibrated for 11.3pt font & cell padding)
    $calcDrugRowH = function($item) {
        $remarks = (string)($item->exhibit_remark ?: 'N/A');
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        $exType  = (string)($item->commodity_name ?: ($item->drug_classification ?: ($item->exhibit_category ?: 'N/A')));
        $source  = (string)($item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A'));
        $qty     = (string)($item->seizure_quantity ? ($item->seizure_quantity . ($item->seizure_packaging ? ' ' . $item->seizure_packaging : '')) : ($item->packaging_type ?: 'N/A'));
        $weight  = (string)($item->seizure_weight ? ($item->seizure_weight . ($item->seizure_unit ? ' ' . $item->seizure_unit : '')) : 'N/A');

        $remLines    = max(1, (int)ceil(mb_strlen($remarks) / 22));
        $suspLines   = max(1, (int)ceil(mb_strlen($suspect) / 13));
        $exLines     = max(1, (int)ceil(mb_strlen($exType) / 13));
        $sourceLines = max(1, (int)ceil(mb_strlen($source) / 12));
        $qtyLines    = max(1, (int)ceil(mb_strlen($qty) / 8));
        $weightLines = max(1, (int)ceil(mb_strlen($weight) / 8));
        $dateLines   = mb_strlen((string)($item->date_of_seizure ?? ($item->seizure_date ?? ''))) > 10 ? 2 : 1;
        $maxLines    = max($remLines, $suspLines, $exLines, $sourceLines, $qtyLines, $weightLines, $dateLines, 1);
        return 6.0 + ($maxLines * 4.8);
    };

    $calcAssetRowH = function($item) {
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        $cat = strtolower(trim((string)($item->exhibit_category ?? '')));
        $isDevice = str_contains($cat, 'electronic') 
            || str_contains($cat, 'device') 
            || str_contains($cat, 'digital') 
            || str_contains($cat, 'phone') 
            || str_contains($cat, 'laptop') 
            || str_contains($cat, 'computer') 
            || !empty($item->dev_model) 
            || !empty($item->dev_imei1);

        if ($isDevice) {
            $devModel = trim((string)($item->dev_model ?: ($item->device_model ?: '')));
            $name = !empty($devModel) ? $devModel : (string)($item->commodity_name ?: ($item->exhibit_category ?: 'Electronic Device'));
        } else {
            $name = (string)($item->commodity_name ?: ($item->exhibit_category ?: 'N/A'));
        }
        $rawChassis = trim((string)($item->veh_vin ?: ($item->chassis_number ?: ($item->vehicle_chassis_vin ?: ''))));
        $chassis = !empty($rawChassis) ? $rawChassis : 'N/A';
        $reg     = (string)($item->veh_reg_no ?: 'N/A');
        
        $suspLines    = max(1, (int)ceil(mb_strlen($suspect) / 14));
        $nameLines    = max(1, (int)ceil(mb_strlen($name) / 18));
        $chassisLines = max(1, (int)ceil(mb_strlen($chassis) / 14));
        $regLines     = max(1, (int)ceil(mb_strlen($reg) / 14));
        $dateLines    = 2;
        $maxLines     = max($suspLines, $nameLines, $chassisLines, $regLines, $dateLines, 1);
        return 6.0 + ($maxLines * 4.8);
    };

    $calcMoneyRowH = function($item) {
        $desc   = (string)($item->commodity_name ?: '');
        $source = (string)($item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A'));
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        
        $descLines   = max(1, (int)ceil(mb_strlen($desc) / 22));
        $sourceLines = max(1, (int)ceil(mb_strlen($source) / 22));
        $suspLines   = max(1, (int)ceil(mb_strlen($suspect) / 18));
        $dateLines   = 2;
        $maxLines    = max($descLines, $sourceLines, $suspLines, $dateLines, 1);
        return 6.0 + ($maxLines * 4.8);
    };

    $calcDynamicRowH = function($item) {
        $remarks = (string)($item->exhibit_remark ?: ($item->wpn_serial_no ?: 'N/A'));
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        $commodity = (string)($item->commodity_name ?: ($item->wpn_model ?: ($item->exhibit_category ?: 'N/A')));
        $source    = (string)($item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A'));
        $remLines  = max(1, (int)ceil(mb_strlen($remarks) / 22));
        $suspLines = max(1, (int)ceil(mb_strlen($suspect) / 14));
        $comLines  = max(1, (int)ceil(mb_strlen($commodity) / 14));
        $srcLines  = max(1, (int)ceil(mb_strlen($source) / 12));
        $dateLines = 2;
        $maxLines  = max($remLines, $suspLines, $comLines, $srcLines, $dateLines, 1);
        return 6.0 + ($maxLines * 4.8);
    };

    // Prepare Dynamic Categories (Weapons, Ammunition, Precious Stones, and custom categories)
    if ($dynamicGroups->isEmpty() && isset($exhibits)) {
        $allExhibits = collect($exhibits);
        $dIds = $drugList->pluck('id')->all();
        $mIds = $moneyList->pluck('id')->all();
        $aIds = $assetList->pluck('id')->all();
        $dynFallback = $allExhibits->filter(fn($e) => !in_array($e->id, $dIds) && !in_array($e->id, $mIds) && !in_array($e->id, $aIds));
        if ($dynFallback->isNotEmpty()) {
            $dynamicGroups = $dynFallback->groupBy(function($e) {
                $cat = trim((string)($e->exhibit_category ?? ''));
                $lower = strtolower($cat);
                if ($lower === 'gold' || str_contains($lower, 'precious') || str_contains($lower, 'stone') || str_contains($lower, 'diamond') || str_contains($lower, 'gemstone')) {
                    return 'Precious Stones';
                }
                if (str_contains($lower, 'weapon') || str_contains($lower, 'firearm') || str_contains($lower, 'gun') || str_contains($lower, 'pistol') || str_contains($lower, 'rifle')) {
                    return 'Weapons';
                }
                if (str_contains($lower, 'ammunition') || str_contains($lower, 'ammo') || str_contains($lower, 'bullet') || str_contains($lower, 'cartridge')) {
                    return 'Ammunition';
                }
                return !empty($cat) ? ucwords(strtolower($cat)) : 'General Exhibits';
            });
        }
    }

    // Strictly ensure no core category (drugs, money, assets) can ever generate a dynamic table
    $dynamicGroups = $dynamicGroups->filter(function($group, $catName) {
        $lower = strtolower(trim((string)$catName));
        return !in_array($lower, ['drugs', 'drug', 'narcotics', 'narcotic', 'money', 'cash', 'currency', 'vehicles', 'electronic devices', 'other exhibits', 'other']);
    });

    $dynamicColorMap = [
        'weapons'         => 'border-red-700',
        'ammunition'      => 'border-orange-600',
        'precious stones' => 'border-amber-700',
        'gold'            => 'border-amber-700',
    ];
    $fallbackPalette = ['border-purple-700', 'border-indigo-700', 'border-teal-700', 'border-rose-700', 'border-cyan-700'];
    $colorIndex = 0;

    // Prepare table notes lookup from database
    $rawNotesList = collect($reportNotes ?? []);
    $resolveTableNote = function($sec) use ($rawNotesList) {
        if ($rawNotesList->isEmpty()) {
            return null;
        }

        $secKey = strtolower(trim((string)($sec['key'] ?? '')));
        $secType = strtolower(trim((string)($sec['type'] ?? '')));
        $secTitle = strtolower(trim((string)($sec['title'] ?? '')));
        $secCat = strtolower(trim((string)($sec['category'] ?? '')));

        // 1. Direct match by table_key or type
        foreach ($rawNotesList as $n) {
            $nKey = strtolower(trim((string)($n->table_key ?? '')));
            if ($nKey !== '' && (
                $nKey === $secKey 
                || $nKey === $secType 
                || ($secCat !== '' && $nKey === 'dyn_' . \Illuminate\Support\Str::slug($secCat, '_')) 
                || ($secCat !== '' && $nKey === \Illuminate\Support\Str::slug($secCat, '_'))
            )) {
                return $n->note;
            }
        }

        // 2. Exact or substring match by table_title / category
        foreach ($rawNotesList as $n) {
            $nTitle = strtolower(trim((string)($n->table_title ?? '')));
            if ($nTitle !== '') {
                if ($secTitle !== '' && ($secTitle === $nTitle || str_contains($secTitle, $nTitle) || str_contains($nTitle, $secTitle))) {
                    return $n->note;
                }
                if ($secCat !== '' && (str_contains($nTitle, $secCat) || str_contains($secCat, $nTitle))) {
                    return $n->note;
                }
            }
        }

        return null;
    };

    $sections = [];

    // Section 1: Drugs (strictly drug-related logs only)
    $sections[] = [
        'key'            => 'drugs',
        'type'           => 'drugs',
        'title'          => 'Drug exhibit Log',
        'badge'          => 'REGISTERED SEIZURE &bull; ' . $drugCount . ' ENTRIES',
        'count'          => $drugCount,
        'borderColor'    => 'border-emerald-700',
        'headerH'        => $sec01HeaderH,
        'continuationH'  => $sec01ContinuationH,
        'emptyMsg'       => 'No drug exhibits recorded during the reporting period.',
        'showIntro'      => false,
        'introText'      => '',
        'remainingItems' => $drugList->values(),
        'calcRowH'       => $calcDrugRowH,
        'note'           => $resolveTableNote(['key' => 'drugs', 'type' => 'drugs', 'title' => 'Drug exhibit Log']),
    ];

    // Section 2: Physical Assets (strictly vehicles, electronic devices, other exhibits only)
    $sections[] = [
        'key'            => 'assets',
        'type'           => 'assets',
        'title'          => 'Physical Assets & Non-Drug Seized Property Log',
        'badge'          => 'REGISTERED ASSETS &bull; ' . $assetCount . ' ENTRIES',
        'count'          => $assetCount,
        'borderColor'    => 'border-blue-700',
        'headerH'        => $sec03HeaderH,
        'continuationH'  => $sec03ContinuationH,
        'emptyMsg'       => 'No physical non-drug assets lodged during the reporting period.',
        'showIntro'      => true,
        'introText'      => 'Details of seized Assets (Vehicles, Electronic Devices, Other Exhibits) within the Period are provided below:',
        'remainingItems' => $assetList->values(),
        'calcRowH'       => $calcAssetRowH,
        'note'           => $resolveTableNote(['key' => 'assets', 'type' => 'assets', 'title' => 'Physical Assets & Non-Drug Seized Property Log']),
    ];

    // Section 3: Money Received (strictly money only)
    $sections[] = [
        'key'            => 'money',
        'type'           => 'money',
        'title'          => 'Seized Currency & Money Received Register',
        'badge'          => 'MONETARY FUNDS &bull; ' . $moneyCount . ' ENTRIES',
        'count'          => $moneyCount,
        'borderColor'    => 'border-amber-600',
        'headerH'        => $sec03HeaderH,
        'continuationH'  => $sec03ContinuationH,
        'emptyMsg'       => 'No monetary funds or seized currency recorded during the reporting period.',
        'showIntro'      => true,
        'introText'      => 'The details of seized currencies, monetary funds, and cash notes received and lodged in the Central Vault repository:',
        'remainingItems' => $moneyList->values(),
        'calcRowH'       => $calcMoneyRowH,
        'note'           => $resolveTableNote(['key' => 'money', 'type' => 'money', 'title' => 'Seized Currency & Money Received Register']),
    ];

    // Dynamic Sections: Any other commodity category (Weapons, Ammunition, Precious Stones, etc.)
    foreach ($dynamicGroups as $catName => $groupItems) {
        $lowerCat = strtolower(trim((string)$catName));
        // Safely exclude drug, money, and default asset categories from generating dynamic sections
        if (in_array($lowerCat, ['drugs', 'drug', 'narcotics', 'narcotic', 'money', 'cash', 'currency', 'vehicles', 'electronic devices', 'other exhibits', 'other'])) {
            continue;
        }
        $cItems = collect($groupItems)->values();
        if ($cItems->isEmpty()) {
            continue;
        }
        $cCount = $cItems->count();
        $cTitle = (str_ends_with($lowerCat, 'log') ? $catName : $catName . ' Exhibit Log');
        $cBorder = $dynamicColorMap[$lowerCat] ?? $fallbackPalette[$colorIndex++ % count($fallbackPalette)];
        $cKey = 'dyn_' . \Illuminate\Support\Str::slug($catName, '_');

        $sections[] = [
            'key'            => $cKey,
            'type'           => 'dynamic',
            'category'       => $catName,
            'title'          => $cTitle,
            'badge'          => strtoupper($catName) . ' &bull; ' . $cCount . ' ENTRIES',
            'count'          => $cCount,
            'borderColor'    => $cBorder,
            'headerH'        => $sec03HeaderH,
            'continuationH'  => $sec03ContinuationH,
            'emptyMsg'       => 'No ' . strtolower($catName) . ' exhibits recorded during the reporting period.',
            'showIntro'      => true,
            'introText'      => 'Details of registered ' . $catName . ' exhibits lodged within the reporting period:',
            'remainingItems' => $cItems,
            'calcRowH'       => $calcDynamicRowH,
            'note'           => $resolveTableNote(['key' => $cKey, 'type' => 'dynamic', 'category' => $catName, 'title' => $cTitle]),
        ];
    }

    // Partition collections across A4 pages
    $dossierPages = [];
    $isFirst = true;
    $currentSectionIdx = 0;
    $totalSections = count($sections);
    $signaturesPlaced = false;

    // Track status of each section: 'not_started', 'in_progress', 'finished'
    $sectionStatus = array_fill(0, $totalSections, 'not_started');

    while (!$signaturesPlaced) {
        $pageLimit   = $isFirst ? $firstPageBudget : $continuationPageBudget;
        $currentUsed = 0.0;
        $pageSections = [];
        $pageSignatures = false;
        $hasAnyContentOnPage = false;

        $pageDrugsChunk = collect();
        $pageAssetsChunk = collect();
        $pageMoneyChunk = collect();
        $isSec01Cont = false;
        $isSec02Cont = false;
        $isSec03Cont = false;
        $showSec02Intro = false;
        $showAssetEmpty = false;
        $showSec03Intro = false;
        $showMoneyEmpty = false;

        while ($currentSectionIdx < $totalSections) {
            $sec = &$sections[$currentSectionIdx];
            $status = $sectionStatus[$currentSectionIdx];
            $items = $sec['remainingItems'];
            $calcRowH = $sec['calcRowH'];
            $secNote = !empty($sec['note']) ? $sec['note'] : null;
            $noteH = $secNote ? (8.5 + (max(1, (int)ceil(mb_strlen($secNote) / 75)) * 4.0)) : 0.0;

            if ($status === 'not_started') {
                if ($items->isEmpty()) {
                    $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                    $needed = $gap + $sec['headerH'] + $secEmptyRowH + $noteH;
                    if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                        $currentUsed += $needed;
                        $pageSections[] = [
                            'key'         => $sec['key'],
                            'type'        => $sec['type'],
                            'category'    => $sec['category'] ?? null,
                            'title'       => $sec['title'],
                            'badge'       => $sec['badge'],
                            'borderColor' => $sec['borderColor'],
                            'isContinued' => false,
                            'showIntro'   => $sec['showIntro'] ?? false,
                            'introText'   => $sec['introText'] ?? '',
                            'items'       => collect(),
                            'showEmpty'   => true,
                            'emptyMsg'    => $sec['emptyMsg'],
                            'count'       => $sec['count'],
                            'note'        => $secNote,
                        ];
                        if ($sec['type'] === 'assets') {
                            $showAssetEmpty = true;
                            $showSec02Intro = true;
                        } elseif ($sec['type'] === 'money') {
                            $showMoneyEmpty = true;
                            $showSec03Intro = true;
                        }
                        $sectionStatus[$currentSectionIdx] = 'finished';
                        $hasAnyContentOnPage = true;
                        $currentSectionIdx++;
                        continue;
                    } else {
                        break;
                    }
                } else {
                    $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                    $firstRowH = $items->isNotEmpty() ? $calcRowH($items->first()) : 10.5;
                    $minNeeded = $gap + $sec['headerH'] + $firstRowH;

                    if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                        $currentUsed += ($gap + $sec['headerH']);
                        $sectionStatus[$currentSectionIdx] = 'in_progress';
                        $hasAnyContentOnPage = true;

                        $chunkItems = collect();
                        while ($items->isNotEmpty()) {
                            $nextItem = $items->first();
                            $rowH = $calcRowH($nextItem);

                            $isWillBeEmpty = ($items->count() === 1);
                            $extraForLast = ($isWillBeEmpty && $secNote) ? $noteH : 0.0;

                            if (($currentUsed + $rowH + $extraForLast > $pageLimit) && $chunkItems->isNotEmpty()) {
                                break;
                            }

                            $chunkItems->push($items->shift());
                            $currentUsed += $rowH;
                        }

                        $isFinished = $items->isEmpty();
                        $pageSecNote = null;
                        if ($isFinished) {
                            $sectionStatus[$currentSectionIdx] = 'finished';
                            if ($secNote) {
                                $currentUsed += $noteH;
                                $pageSecNote = $secNote;
                            }
                        }

                        $pageSections[] = [
                            'key'         => $sec['key'],
                            'type'        => $sec['type'],
                            'category'    => $sec['category'] ?? null,
                            'title'       => $sec['title'],
                            'badge'       => $sec['badge'],
                            'borderColor' => $sec['borderColor'],
                            'isContinued' => false,
                            'showIntro'   => $sec['showIntro'] ?? false,
                            'introText'   => $sec['introText'] ?? '',
                            'items'       => $chunkItems,
                            'showEmpty'   => false,
                            'emptyMsg'    => $sec['emptyMsg'],
                            'count'       => $sec['count'],
                            'note'        => $pageSecNote,
                        ];

                        if ($sec['type'] === 'drugs') {
                            $pageDrugsChunk = $chunkItems;
                        } elseif ($sec['type'] === 'assets') {
                            $pageAssetsChunk = $chunkItems;
                            $showSec02Intro = true;
                        } elseif ($sec['type'] === 'money') {
                            $pageMoneyChunk = $chunkItems;
                            $showSec03Intro = true;
                        }

                        if ($isFinished) {
                            $currentSectionIdx++;
                            continue;
                        } else {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            } elseif ($status === 'in_progress') {
                $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $firstRowH = $items->isNotEmpty() ? $calcRowH($items->first()) : 10.5;
                $minNeeded = $gap + $sec['continuationH'] + $firstRowH;

                if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                    $currentUsed += ($gap + $sec['continuationH']);
                    $hasAnyContentOnPage = true;

                    $chunkItems = collect();
                    while ($items->isNotEmpty()) {
                        $nextItem = $items->first();
                        $rowH = $calcRowH($nextItem);

                        $isWillBeEmpty = ($items->count() === 1);
                        $extraForLast = ($isWillBeEmpty && $secNote) ? $noteH : 0.0;

                        if (($currentUsed + $rowH + $extraForLast > $pageLimit) && $chunkItems->isNotEmpty()) {
                            break;
                        }

                        $chunkItems->push($items->shift());
                        $currentUsed += $rowH;
                    }

                    $isFinished = $items->isEmpty();
                    $pageSecNote = null;
                    if ($isFinished) {
                        $sectionStatus[$currentSectionIdx] = 'finished';
                        if ($secNote) {
                            $currentUsed += $noteH;
                            $pageSecNote = $secNote;
                        }
                    }

                    $pageSections[] = [
                        'key'         => $sec['key'],
                        'type'        => $sec['type'],
                        'category'    => $sec['category'] ?? null,
                        'title'       => $sec['title'],
                        'badge'       => $sec['badge'],
                        'borderColor' => $sec['borderColor'],
                        'isContinued' => true,
                        'showIntro'   => false,
                        'introText'   => '',
                        'items'       => $chunkItems,
                        'showEmpty'   => false,
                        'emptyMsg'    => $sec['emptyMsg'],
                        'count'       => $sec['count'],
                        'note'        => $pageSecNote,
                    ];

                    if ($sec['type'] === 'drugs') {
                        $pageDrugsChunk = $chunkItems;
                        $isSec01Cont = true;
                    } elseif ($sec['type'] === 'assets') {
                        $pageAssetsChunk = $chunkItems;
                        $isSec02Cont = true;
                    } elseif ($sec['type'] === 'money') {
                        $pageMoneyChunk = $chunkItems;
                        $isSec03Cont = true;
                    }

                    if ($isFinished) {
                        $currentSectionIdx++;
                        continue;
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            } else {
                $currentSectionIdx++;
            }
        }

        $allSectionsFinished = ($currentSectionIdx >= $totalSections);

        if ($allSectionsFinished) {
            $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
            $needed = $gap + $signaturesH;

            // Strict safety limit: only place signatures on this page if table height is low enough to prevent any footer margin overlap
            $safeBudgetForSignatures = $isFirst ? 145.0 : 165.0;

            if (($currentUsed <= $safeBudgetForSignatures && ($pageLimit - $currentUsed >= $needed)) || !$hasAnyContentOnPage) {
                $currentUsed += $needed;
                $pageSignatures   = true;
                $signaturesPlaced = true;
            }
        }

        $dossierPages[] = [
            'isFirst'              => $isFirst,
            'isLast'               => false,
            'sections'             => $pageSections,
            'drugs'                => $pageDrugsChunk,
            'isSection01Continued' => $isSec01Cont,
            'assets'               => $pageAssetsChunk,
            'isSection02Continued' => $isSec02Cont,
            'showSection02Intro'   => $showSec02Intro,
            'showAssetEmpty'       => $showAssetEmpty,
            'money'                => $pageMoneyChunk,
            'isSection03Continued' => $isSec03Cont,
            'showSection03Intro'   => $showSec03Intro,
            'showMoneyEmpty'       => $showMoneyEmpty,
            'signatures'           => $pageSignatures,
        ];

        $isFirst = false;

        if (count($dossierPages) > 60) {
            $signaturesPlaced = true;
            break;
        }
    }

    // Ensure signatures are placed on the final page
    if (!empty($dossierPages)) {
        $lastIdx = count($dossierPages) - 1;
        for ($i = 0; $i < $lastIdx; $i++) {
            $dossierPages[$i]['isLast'] = false;
            $dossierPages[$i]['signatures'] = false;
        }
        $dossierPages[$lastIdx]['isLast'] = true;
        $dossierPages[$lastIdx]['signatures'] = true;
    }

    $totalDossierPages = 1 + count($dossierPages);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Evidence Management Unit &bull; Forensic Seized Property Report</title>
    
    <!-- Scripts & Styles -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&family=Times+New+Roman&display=swap" rel="stylesheet">
    
    <style>
        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Font.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 800;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 900;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: url('{{ asset('font/Arial-Narrow-Font.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial Narrow';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }

        ::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        html, body {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }

        body {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
            font-size: 11.3pt;
            background-color: #ffffff;
            color: #1e293b;
            margin: 0;
            padding: 24px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .font-serif-header {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
            letter-spacing: 0.04em;
        }

        .font-mono-code {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', 'JetBrains Mono', monospace;
        }

        .font-times {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
        }

        body,
        .a4-page,
        .cover-page,
        .dynamic-dossier-page,
        table,
        th,
        td,
        button,
        input,
        select,
        textarea {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif !important;
        }

        .a4-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 10mm 12mm 10mm 12mm;
            margin: 0 auto 24px auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        /* Cover Page: Exactly 1 static A4 sheet matching lab_sitrep_pdf */
        .cover-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 14mm 14mm 12mm 14mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            box-sizing: border-box;
        }

        /* Dynamic Dossier Pages */
        .dynamic-dossier-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 8mm 7mm 8mm 7mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        /* Full page height stretching inner wrapper */
        .page-content-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            min-height: 100%;
            max-height: 100%;
            width: 100%;
            flex: 1 1 0%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .page-header-block {
            flex-shrink: 0;
            width: 100%;
        }

        .report-flow-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            margin-bottom: 6px !important;
            overflow: hidden;
        }

        .section-signatures {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
            margin-bottom: 4px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .page-footer-block {
            flex-shrink: 0 !important;
            margin-top: auto !important;
            width: 100% !important;
            position: relative !important;
            z-index: 30 !important;
            background-color: #ffffff !important;
            padding-top: 2mm !important;
            padding-bottom: 1mm !important;
        }

        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .table-responsive-wrapper {
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            border-radius: 8px !important;
            margin-top: 8px !important;
            margin-bottom: 8px !important;
        }

        table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            box-sizing: border-box !important;
            margin: 0 !important;
        }

        th {
            font-size: 10pt !important;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            line-height: 1.25 !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
            box-sizing: border-box !important;
        }

        td {
            font-size: 11.3pt !important;
            padding-top: 4px !important;
            padding-bottom: 4px !important;
            line-height: 1.28 !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
            box-sizing: border-box !important;
        }

        th:last-child,
        td:last-child {
            padding-right: 12px !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
        }

        .table-responsive-wrapper table,
        .table-responsive-wrapper td,
        .table-responsive-wrapper tr,
        .table-responsive-wrapper td * {
            font-size: 11.3pt !important;
        }

        .table-responsive-wrapper thead tr,
        .table-responsive-wrapper th,
        .table-responsive-wrapper th * {
            font-size: 10pt !important;
        }

        .table-note-box,
        .table-note-box * {
            font-size: 11.3pt !important;
            line-height: 1.35 !important;
        }

        @media print {
            .table-responsive-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
                border-radius: 8px !important;
                margin-top: 8px !important;
                margin-bottom: 8px !important;
            }

            table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                margin: 0 !important;
            }

            th {
                font-size: 10pt !important;
                padding-top: 5px !important;
                padding-bottom: 5px !important;
                line-height: 1.25 !important;
                overflow-wrap: break-word !important;
                word-break: break-word !important;
            }

            td {
                font-size: 11.3pt !important;
                padding-top: 4px !important;
                padding-bottom: 4px !important;
                line-height: 1.28 !important;
                overflow-wrap: break-word !important;
                word-break: break-word !important;
            }

            th:last-child,
            td:last-child {
                padding-right: 12px !important;
                overflow-wrap: break-word !important;
                word-break: break-word !important;
            }

            .table-responsive-wrapper table,
            .table-responsive-wrapper td,
            .table-responsive-wrapper tr,
            .table-responsive-wrapper td * {
                font-size: 11.3pt !important;
            }

            .table-responsive-wrapper thead tr,
            .table-responsive-wrapper th,
            .table-responsive-wrapper th * {
                font-size: 10pt !important;
            }

            .table-note-box,
            .table-note-box * {
                font-size: 11.3pt !important;
                line-height: 1.35 !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .action-bar-container {
                display: none !important;
            }

            .a4-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 10mm 7mm 10mm 7mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .cover-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 14mm 14mm 12mm 14mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .dynamic-dossier-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 8mm 7mm 8mm 7mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .report-flow-body {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                width: 100% !important;
                margin-bottom: 6px !important;
                overflow: hidden !important;
            }

            .page-footer-block {
                flex-shrink: 0 !important;
                margin-top: auto !important;
                width: 100% !important;
                position: relative !important;
                z-index: 30 !important;
                background-color: #ffffff !important;
                padding-top: 2mm !important;
                padding-bottom: 1mm !important;
            }

            .dynamic-dossier-page > .dossier-watermark {
                display: flex !important;
                position: absolute !important;
                inset: 0 !important;
                align-items: center !important;
                justify-content: center !important;
                pointer-events: none !important;
                z-index: 0 !important;
                opacity: 0.07 !important;
                overflow: hidden !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- FLOATING TOP ACTION BAR (SCREEN ONLY) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 flex items-center justify-between select-none">
        <div class="flex items-center gap-3">
            <a href="{{ url('/Evidence_Management/report') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors border border-slate-700 shadow-sm no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Reports</span>
            </a>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-md hover:shadow-lg active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- MAIN DOSSIER SHEETS CONTAINER -->
    <div id="sitrepPrintableDossier" class="printable-document">

        <!-- ============================================================
             PAGE 1: EXACT EXECUTIVE COVER PAGE (IDENTICAL TO LAB SITREP)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[9.5px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED FORENSIC LABORATORY</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                        <p class="mt-0.5 text-[10.5px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            SITREP COVER &bull; PAGE 1 OF <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                        </p>
                    </div>
                </div>

                <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE -->
                <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">
                    
                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-2xl font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            FORENSIC DEPARTMENT
                        </p>
                        <p class="mt-0.5 text-[11.5px] font-semibold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            EVIDENCE MANAGEMENT UNIT (EMU)
                        </p>
                    </div>

                    <!-- INCREASED SIZE EMBLEM LOGO IN THE MIDDLE (EXACT MATCH) -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-680 h-680 max-w-[780px] max-h-[780px] object-contain drop-shadow-md" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL FORENSIC SITUATION REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            EVIDENCE MANAGEMENT UNIT
                        </h2>
                        <p class="mt-1 text-base font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Preliminary Forensic Situational Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-lg font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel }}
                        </p>
                        <p class="mt-2 text-[14px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ $periodLabel }}
                        </p>
                        <p class="mt-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            ALL DRUGS &amp; NON-DRUGS RELATED EXHIBIT 
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[11px] text-slate-700 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official situational report contains drugs, non-drug assets, and chain-of-custody intelligence.
                            </p>
                        </div>
                    </div>

                    <!-- SIGN-OFF AUTHENTICATION ACCREDITATION PREVIEW -->
                    <div class="mt-6 w-full grid grid-cols-2 gap-8 text-left border-t border-slate-200 pt-3">
                        <div class="text-[10px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[9px] font-bold">COMPILING UNIT:</span>
                            <span class="font-bold text-slate-800">Evidence Management Unit (EMU)</span>
                            <span class="block text-slate-600 text-[9px]">NACOC Headquarters, Accra</span>
                        </div>
                        <div class="text-right text-[10px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[9px] font-bold">{{ $evidenceAuthorityLabel }}</span>
                            <span class="font-bold text-slate-800">{{ $evidenceOfficerName }}</span>
                            <span class="block text-slate-600 text-[9px]">{{ $evidenceSignatoryRole }}</span>
                        </div>
                    </div>

                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="page-footer-block mt-auto pt-3 border-t border-slate-200 flex items-center justify-between text-[9.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / EMU UNIT</span>
                        <span>&bull;</span>
                        <span>Executive Directory Cover Page</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page 1 of <span class="doc-total-pages">{{ $totalDossierPages }}</span></span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ============================================================
             PAGE 2+: EXECUTIVE FORENSIC DOSSIER & LOGS
             ============================================================ -->
        @foreach ($dossierPages as $pageIdx => $p)
            @php
                $pageNumber = $pageIdx + 2;
                $isFirstDossierPage = $p['isFirst'];
                $isLastDossierPage = $p['isLast'];

                $drugChunk  = $p['drugs'];
                $assetChunk = $p['assets'];
                $moneyChunk = $p['money'];
            @endphp
        <div class="a4-page dynamic-dossier-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[800px] h-[800px] object-contain" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- DOSSIER HEADER -->
                <div class="page-header-block shrink-0">
                    <div class="pt-1 pb-2 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC FORENSIC &bull; EVIDENCE MANAGEMENT UNIT
                            </span>
                            <span class="ml-2 font-mono-code text-[10.5px] font-semibold text-slate-700">
                                SITREP &bull; {{ date('Y') }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                            <p class="mt-0.5 text-[10.5px] font-bold uppercase text-slate-800">
                                PAGE {{ $pageNumber }} OF {{ $totalDossierPages }}
                            </p>
                        </div>
                    </div>

                    @if ($isFirstDossierPage)
                    <!-- REPORT TITLE BLOCK -->
                    <div class="page-title-block mt-2 pb-1.5 text-center border-b border-slate-200">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                            FORENSIC DEPARTMENT &bull; EVIDENCE MANAGEMENT UNIT
                        </p>
                        <h2 class="mt-0.5 text-lg font-bold uppercase tracking-wide text-slate-950 font-serif-header" id="dossierHeadingText">
                            {{ $reportTypeLabel }}
                        </h2>
                        <p class="mt-0.5 text-[13.5px] font-bold uppercase tracking-wider text-emerald-900 font-times" id="dossierPeriodText">
                            {{ $periodLabel }}
                        </p>
                    </div>

                    <!-- OPERATIONAL INTRODUCTORY PARAGRAPH
                    <div class="page-intro-block mt-2 text-[11px] text-slate-800 leading-relaxed font-sans">
                        <p class="font-medium">
                            During the reporting period, the Evidence Management Unit continued to provide essential preliminary forensic examination and drug testing for the underlisted cases;
                        </p>
                    </div> -->
                    @endif
                </div>

                <!-- MAIN DOSSIER FLOW CONTENT -->
                <div class="report-flow-body flex-1 mt-3 min-h-0 flex flex-col justify-start">
                    <div class="flow-sections-container space-y-4">

                    @foreach ($p['sections'] as $sec)
                        @if ($sec['type'] === 'drugs')
                            <!-- ========================================================
                                 TABLE 1: DRUG EXHIBITS (STRICLY DRUGS ONLY)
                                 ======================================================== -->
                            <div class="section-01">
                                <div class="section-header-block flex items-center justify-between pb-1 mt-2 mb-2 border-b-2 border-emerald-700">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                            Drug exhibit Log{{ !empty($sec['isContinued']) ? ' (Continued)' : '' }}
                                        </h3>
                                    </div>
                                    <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                        REGISTERED SEIZURE &bull; <span>{{ $drugCount }}</span> ENTRIES
                                    </span>
                                </div>

                                <div class="table-responsive-wrapper border border-slate-300 rounded-lg bg-white mt-4">
                                    <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                        <thead>
                                            <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                                <th class="py-2.5 px-2 w-[11%] border-r border-slate-200">Date Of Seizure</th>
                                                <th class="py-2.5 px-2 w-[13%] border-r border-slate-200">Source of Exhibits</th>
                                                <th class="py-2.5 px-2 w-[16%] border-r border-slate-200">Suspect(s)</th>
                                                <th class="py-2.5 px-2 w-[17%] border-r border-slate-200">Exhibit Type</th>
                                                <th class="py-2.5 px-1.5 text-center w-[10.5%] border-r border-slate-200">Quantity</th>
                                                <th class="py-2.5 px-1.5 text-center w-[9.5%] border-r border-slate-200">Weight</th>
                                                <th class="py-2.5 px-2 pr-3.5 w-[23%]">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-slate-800">
                                            @forelse($sec['items'] as $index => $item)
                                                @php
                                                    $isEven = $index % 2 === 0;
                                                    $suspectDisplay = 'N/A';
                                                    if ($item->singleSuspectCase) {
                                                        $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                                    } elseif ($item->suspect) {
                                                        $suspectDisplay = $item->suspect->suspect_name;
                                                    } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                        $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                                    }

                                                    $qtyDisplay = $item->seizure_quantity ? ($item->seizure_quantity . ($item->seizure_packaging ? ' ' . $item->seizure_packaging : '')) : ($item->packaging_type ?: 'N/A');
                                                    $weightDisplay = $item->seizure_weight ? ($item->seizure_weight . ($item->seizure_unit ? ' ' . $item->seizure_unit : '')) : 'N/A';
                                                    $remarksDisplay = $item->exhibit_remark ?: 'N/A';
                                                    $sourceDisplay = $item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A');
                                                    $exTypeDisplay = $item->commodity_name ?: ($item->drug_classification ?: ($item->exhibit_category ?: 'N/A'));
                                                    $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                                @endphp
                                                <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                                    <td class="py-2 px-1.5 font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $dateDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold uppercase text-slate-900 text-[10px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $sourceDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $suspectDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-900 text-[10px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $exTypeDisplay }}
                                                    </td>
                                                    <td class="py-2 px-1 text-center font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $qtyDisplay }}
                                                    </td>
                                                    <td class="py-2 px-1 text-center font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $weightDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 pr-3.5 text-[10px] text-slate-700 align-top leading-snug">
                                                        {{ $remarksDisplay }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="py-3 px-3 text-center text-slate-500 font-mono-code text-xs">
                                                        {{ $sec['emptyMsg'] }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (!empty($sec['note']))
                                <div class="table-note-box mt-2 text-black">
                                    <div class="flex items-center gap-1.5 text-[12px] font-black uppercase font-mono-code text-black mb-1">
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Officer Footnote &bull; Drug Exhibit Log:</span>
                                    </div>
                                    <div class="text-[11.5px] text-black font-sans italic leading-relaxed whitespace-pre-line pl-5">{{ $sec['note'] }}</div>
                                </div>
                                @endif
                            </div>
                        @elseif ($sec['type'] === 'assets')
                            <!-- ========================================================
                                 TABLE 2: PHYSICAL ASSETS & NON-DRUG PROPERTY (VEHICLES, ELECTRONICS, OTHER ONLY)
                                 ======================================================== -->
                            <div class="section-02 mt-4">
                                <div class="section-header-block flex items-center justify-between pb-1 mt-10 mb-2 border-b-2 border-blue-700">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                            Physical Assets &amp; Non-Drug Seized Property Log{{ !empty($sec['isContinued']) ? ' (Continued)' : '' }}
                                        </h3>
                                    </div>
                                    <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                        REGISTERED ASSETS &bull; <span>{{ $assetCount }}</span> ENTRIES
                                    </span>
                                </div>

                                @if (!empty($sec['showIntro']))
                                <div class="text-[11.5px] text-slate-600 mb-2 italic font-sans">
                                    Details of seized Assets within the Period are provided below:
                                </div>
                                @endif

                                <div class="table-responsive-wrapper border border-slate-300 rounded-lg bg-white mt-4">
                                    <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                        <thead>
                                            <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                                <th class="py-2.5 px-2 w-[13%] border-r border-slate-200">Date Of Seizure</th>
                                                <th class="py-2.5 px-2 w-[19%] border-r border-slate-200">Suspect(s)</th>
                                                <th class="py-2.5 px-2 w-[26%] border-r border-slate-200">Asset / Exhibit Type</th>
                                                <th class="py-2.5 px-2 w-[20%] border-r border-slate-200">Chassis Number</th>
                                                <th class="py-2.5 px-2 pr-3.5 w-[22%]">Registration Number</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-slate-800">
                                            @forelse($sec['items'] as $index => $item)
                                                @php
                                                    $isEven = $index % 2 === 0;
                                                    $suspectDisplay = 'N/A';
                                                    if ($item->singleSuspectCase) {
                                                        $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                                    } elseif ($item->suspect) {
                                                        $suspectDisplay = $item->suspect->suspect_name;
                                                    } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                        $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                                    }

                                                    $rawChassis = trim((string)($item->veh_vin ?: ($item->chassis_number ?: ($item->vehicle_chassis_vin ?: ''))));
                                                    $chassisDisplay = !empty($rawChassis) ? $rawChassis : 'N/A';
                                                    $regDisplay = $item->veh_reg_no ?: 'N/A';

                                                    $cat = strtolower(trim((string)($item->exhibit_category ?? '')));
                                                    $isDevice = str_contains($cat, 'electronic') 
                                                        || str_contains($cat, 'device') 
                                                        || str_contains($cat, 'digital') 
                                                        || str_contains($cat, 'phone') 
                                                        || str_contains($cat, 'laptop') 
                                                        || str_contains($cat, 'computer') 
                                                        || !empty($item->dev_model) 
                                                        || !empty($item->dev_imei1);

                                                    if ($isDevice) {
                                                        $devModel = trim((string)($item->dev_model ?: ($item->device_model ?: '')));
                                                        $assetTypeDisplay = !empty($devModel) ? $devModel : ($item->commodity_name ?: ($item->exhibit_category ?: 'Electronic Device'));
                                                    } else {
                                                        $assetTypeDisplay = $item->commodity_name ?: ($item->exhibit_category ?: 'N/A');
                                                    }
                                                    $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                                @endphp
                                                <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                                    <td class="py-2 px-2 font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $dateDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $suspectDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-900 text-[10px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $assetTypeDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-mono-code text-[9.5px] text-slate-800 border-r border-slate-200 align-top">
                                                        {{ $chassisDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 pr-3.5 font-mono-code font-bold text-slate-900 text-[10px] align-top">
                                                        {{ $regDisplay }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-3 px-3 text-center text-slate-500 font-mono-code text-xs">
                                                        {{ $sec['emptyMsg'] }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (!empty($sec['note']))
                                <div class="table-note-box mt-2 text-black">
                                    <div class="flex items-center gap-1.5 text-[12px] font-black uppercase font-mono-code text-black mb-1">
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Officer Footnote &bull; Physical Assets &amp; Non-Drug Seized Property Log:</span>
                                    </div>
                                    <div class="text-[11.5px] text-black font-sans italic leading-relaxed whitespace-pre-line pl-5">{{ $sec['note'] }}</div>
                                </div>
                                @endif
                            </div>
                        @elseif ($sec['type'] === 'money')
                            <!-- ========================================================
                                 TABLE 3: SEIZED CURRENCY & MONEY RECEIVED (MONEY ONLY)
                                 ======================================================== -->
                            <div class="section-03 mt-4">
                                <div class="section-header-block flex items-center justify-between pb-1 mt-10 mb-2 border-b-2 border-amber-600">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                            Seized Currency &amp; Money Received Register{{ !empty($sec['isContinued']) ? ' (Continued)' : '' }}
                                        </h3>
                                    </div>
                                    <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                        MONETARY FUNDS &bull; <span>{{ $moneyCount }}</span> ENTRIES
                                    </span>
                                </div>

                                @if (!empty($sec['showIntro']))
                                <div class="text-[11.5px] text-slate-600 mb-2 italic font-sans">
                                    The details of seized currencies, monetary funds, and cash notes received and lodged in the Central Vault repository:
                                </div>
                                @endif

                                <div class="table-responsive-wrapper border border-slate-300 rounded-lg bg-white mt-4">
                                    <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                        <thead>
                                            <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                                <th class="py-2.5 px-2 w-[15%] border-r border-slate-200">Date</th>
                                                <th class="py-2.5 px-2 w-[27%] border-r border-slate-200">Suspect</th>
                                                <th class="py-2.5 px-2 text-right w-[22%] border-r border-slate-200">Amount</th>
                                                <th class="py-2.5 px-2 pr-3.5 w-[36%]">Location</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-slate-800">
                                            @forelse($sec['items'] as $index => $item)
                                                @php
                                                    $isEven = $index % 2 === 0;
                                                    $suspectDisplay = 'N/A';
                                                    if ($item->singleSuspectCase) {
                                                        $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                                    } elseif ($item->suspect) {
                                                        $suspectDisplay = $item->suspect->suspect_name;
                                                    } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                        $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                                    }

                                                    $rawVal = $item->amount ?: ($item->seizure_value ?? 0);
                                                    $val = (float) preg_replace('/[^\d.]/', '', (string) $rawVal);
                                                    $locationDisplay = $item->seizure_location ?: ($item->source_of_exhibits ?: 'N/A');
                                                    $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                                @endphp
                                                <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                                    <td class="py-2 px-2 font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $dateDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                        <div>{{ $suspectDisplay }}</div>
                                                        @if(!empty($item->commodity_name))
                                                            <div class="text-[9px] font-normal text-slate-500 font-mono-code pt-0.5">{{ $item->commodity_name }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="py-2 px-2 text-right font-mono-code font-black text-emerald-900 border-r border-slate-200 align-top text-[11px]">
                                                        GH₵ {{ $val > 0 ? number_format($val, 2) : '0.00' }}
                                                    </td>
                                                    <td class="py-2 px-2 pr-3.5 font-bold uppercase text-slate-900 text-[10px] align-top leading-tight">
                                                        {{ $locationDisplay }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="py-3 px-3 text-center text-slate-500 font-mono-code text-xs">
                                                        {{ $sec['emptyMsg'] }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (!empty($sec['note']))
                                <div class="table-note-box mt-2 mb-10 text-black">
                                    <div class="flex items-center gap-1.5 text-[12px] font-black uppercase font-mono-code text-black mb-1">
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Officer Footnote &bull; Seized Currency &amp; Money Received Register:</span>
                                    </div>
                                    <div class="text-[11.5px] text-black font-sans italic leading-relaxed whitespace-pre-line pl-5">{{ $sec['note'] }}</div>
                                </div>
                                @endif
                            </div>
                        @elseif ($sec['type'] === 'dynamic')
                            <!-- ========================================================
                                 DYNAMIC TABLE: OTHER COMMODITY CATEGORY
                                 ======================================================== -->
                            <div class="section-dynamic mt-4">
                                <div class="section-header-block flex items-center justify-between pb-1 mt-2 mb-2 border-b-2 {{ $sec['borderColor'] }}">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                            {{ $sec['title'] }}{{ !empty($sec['isContinued']) ? ' (Continued)' : '' }}
                                        </h3>
                                    </div>
                                    <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                        {{ $sec['badge'] }}
                                    </span>
                                </div>

                                @if (!empty($sec['showIntro']) && !empty($sec['introText']))
                                <div class="text-[11.5px] text-slate-600 mb-2 italic font-sans">
                                    {{ $sec['introText'] }}
                                </div>
                                @endif

                                @php
                                    $isPreciousStones = strtolower($sec['category'] ?? '') === 'precious stones'
                                        || str_contains(strtolower($sec['title'] ?? ''), 'precious stones');
                                @endphp
                                <div class="table-responsive-wrapper border border-slate-300 rounded-lg bg-white mt-4">
                                    <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                        <thead>
                                            <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                                @if ($isPreciousStones)
                                                    <th class="py-2.5 px-2 w-[12%] border-r border-slate-200">Date Of Seizure</th>
                                                    <th class="py-2.5 px-2 w-[17%] border-r border-slate-200">Suspect(s)</th>
                                                    <th class="py-2.5 px-2 w-[22%] border-r border-slate-200">Item / Commodity</th>
                                                    <th class="py-2.5 px-1.5 text-center w-[10%] border-r border-slate-200">Quantity</th>
                                                    <th class="py-2.5 px-1.5 text-center w-[12%] border-r border-slate-200">Weight / Spec</th>
                                                    <th class="py-2.5 px-2 pr-3.5 w-[27%]">Remarks / Details</th>
                                                @else
                                                    <th class="py-2.5 px-2 w-[11%] border-r border-slate-200">Date Of Seizure</th>
                                                    <th class="py-2.5 px-2 w-[13%] border-r border-slate-200">Source of Exhibits</th>
                                                    <th class="py-2.5 px-2 w-[16%] border-r border-slate-200">Suspect(s)</th>
                                                    <th class="py-2.5 px-2 w-[17%] border-r border-slate-200">Item / Commodity</th>
                                                    <th class="py-2.5 px-1.5 text-center w-[10.5%] border-r border-slate-200">Quantity</th>
                                                    <th class="py-2.5 px-1.5 text-center w-[9.5%] border-r border-slate-200">Weight / Spec</th>
                                                    <th class="py-2.5 px-2 pr-3.5 w-[23%]">Remarks / Details</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-slate-800">
                                            @forelse($sec['items'] as $index => $item)
                                                @php
                                                    $isEven = $index % 2 === 0;
                                                    $suspectDisplay = 'N/A';
                                                    if ($item->singleSuspectCase) {
                                                        $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                                    } elseif ($item->suspect) {
                                                        $suspectDisplay = $item->suspect->suspect_name;
                                                    } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                        $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                                    }

                                                    $qtyDisplay = $item->seizure_quantity ? ($item->seizure_quantity . ($item->seizure_packaging ? ' ' . $item->seizure_packaging : '')) : ($item->packaging_type ?: '1 Unit');
                                                    $sourceDisplay = $item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A');
                                                    $commodityDisplay = $item->commodity_name ?: ($item->wpn_model ?: ($item->exhibit_category ?: 'N/A'));
                                                    $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');

                                                    $specDisplay = 'N/A';
                                                    if (!empty($item->wpn_calibre)) {
                                                        $specDisplay = $item->wpn_calibre;
                                                    } elseif (!empty($item->seizure_weight)) {
                                                        $specDisplay = $item->seizure_weight . ($item->seizure_unit ? ' ' . $item->seizure_unit : '');
                                                    }

                                                    $detailParts = [];
                                                    if (!empty($item->wpn_serial_no)) {
                                                        $detailParts[] = 'SN: ' . $item->wpn_serial_no;
                                                    }
                                                    if (!empty($item->exhibit_remark)) {
                                                        $detailParts[] = $item->exhibit_remark;
                                                    }
                                                    $remarksDisplay = !empty($detailParts) ? implode(' • ', $detailParts) : 'N/A';
                                                @endphp
                                                <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                                    <td class="py-2 px-1.5 font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $dateDisplay }}
                                                    </td>
                                                    @if (!$isPreciousStones)
                                                    <td class="py-2 px-2 font-bold uppercase text-slate-900 text-[10px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $sourceDisplay }}
                                                    </td>
                                                    @endif
                                                    <td class="py-2 px-2 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $suspectDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 font-bold text-slate-900 text-[10px] border-r border-slate-200 align-top leading-tight">
                                                        {{ $commodityDisplay }}
                                                    </td>
                                                    <td class="py-2 px-1 text-center font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $qtyDisplay }}
                                                    </td>
                                                    <td class="py-2 px-1 text-center font-mono-code text-[9.5px] border-r border-slate-200 align-top">
                                                        {{ $specDisplay }}
                                                    </td>
                                                    <td class="py-2 px-2 pr-3.5 text-[10px] text-slate-700 align-top leading-snug">
                                                        {{ $remarksDisplay }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $isPreciousStones ? 6 : 7 }}" class="py-3 px-3 text-center text-slate-500 font-mono-code text-xs">
                                                        {{ $sec['emptyMsg'] }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (!empty($sec['note']))
                                <div class="table-note-box mt-2 text-black">
                                    <div class="flex items-center gap-1.5 text-[12px] font-black uppercase font-mono-code text-black mb-1">
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Officer Footnote &bull; {{ $sec['title'] }}:</span>
                                    </div>
                                    <div class="text-[11.5px] text-black font-sans italic leading-relaxed whitespace-pre-line pl-5">{{ $sec['note'] }}</div>
                                </div>
                                @endif
                            </div>
                        @endif
                    @endforeach

                    </div>
                </div>

                <!-- ========================================================
                     SECTION SIGNATURES (ON FINAL DOSSIER PAGE - EXACT 3 COLS)
                     ======================================================== -->
                @if (!empty($p['signatures']))
                <div class="section-signatures avoid-break shrink-0 w-full mt-auto pt-2 pb-1.5 border-t-2 border-slate-300">
                    <div class="grid grid-cols-3 gap-6">
                        <!-- LEFT: STAFF OFFICER OR HEAD OF EVIDENCE MANAGEMENT UNIT -->
                        <div class="flex flex-col">
                            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">{{ $evidenceSignatoryHeading }}</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10 flex items-center">
                                @if(!empty($evidenceSignatorySig))
                                    <img src="{{ $evidenceSignatorySig }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight truncate">{{ $evidenceOfficerName }}</p>
                                <p class="text-[9px] text-slate-700 leading-tight">{{ $evidenceSignatoryRole }}, NACOC</p>
                                <p class="text-[9px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- MIDDLE: HEAD OF FORENSIC DEPARTMENT -->
                        <div class="flex flex-col">
                            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">HEAD OF FORENSIC DEPARTMENT</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10 flex items-center">
                                @if(!empty($dbDeptHead?->signature))
                                    <img src="{{ $dbDeptHead->signature }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight truncate">{{ $deptHeadName }}</p>
                                <p class="text-[9px] text-slate-700 leading-tight">Head of Forensic Department, NACOC</p>
                                <p class="text-[9px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- RIGHT: DIRECTOR OF INTELLIGENCE AND INVESTIGATION -->
                        <div class="flex flex-col">
                            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">DIRECTOR OF INTEL &amp; INVESTIGATION</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10"></div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[10px] font-bold text-slate-900 leading-tight font-mono-code">{{ $intelDirectorName }}</p>
                                <p class="text-[9px] text-slate-700 leading-tight">Director of Intelligence &amp; Investigation, NACOC</p>
                                <p class="text-[9px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- DOSSIER FOOTER -->
                <div class="page-footer-block {{ empty($p['signatures']) ? 'mt-auto' : '' }} pt-2 border-t border-slate-200 flex items-center justify-between text-[10.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / EVIDENCE MANAGEMENT UNIT</span>
                        <span>&bull;</span>
                        <span>Official Forensic Dossier</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page {{ $pageNumber }} of {{ $totalDossierPages }}</span>
                    </div>
                </div>

            </div>
        </div>
        @endforeach

    </div>

    <!-- SCRIPT FOR AUTOMATIC PRINT IF REQUESTED -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 500);
            }
        });
    </script>
</body>
</html>
