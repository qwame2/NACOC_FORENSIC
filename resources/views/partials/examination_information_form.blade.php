<!-- EXAMINATION INFORMATION CONTAINER (ALWAYS VISIBLE AT LAB ANALYSIS STAGE WHEN UNSAVED) -->
@php
    $firstExhibitHasSaved = !empty($drugExhibits[0]['has_saved_lab']);
    $isLabCompleted = $firstExhibitHasSaved;
@endphp
<div id="section5FormContainer"
    class="mt-8 bg-white/90 border border-slate-300/80 rounded-2xl p-6 shadow-sm space-y-6 hidden">
    <div
        class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

        <div class="flex items-center space-x-3">
            <div
                class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-extrabold text-slate-900 text-base">Examination
                        Information</h3>
                    <span id="section5BadgeStatus"
                        class="px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $firstExhibitHasSaved ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                        {{ $firstExhibitHasSaved ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? 'LAB IN PROGRESS' : 'LAB PENDING') }}
                    </span>
                </div>
                <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis
                    Directorate — Forensic Examination &amp; Testing Log</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span
                class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                LAB ANALYSIS EXCLUSIVE
            </span>
        </div>
    </div>

    <form id="section5LabForm" onsubmit="event.preventDefault(); return false;"
        onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); return false; }"
        class="space-y-5">
        <input type="hidden" id="labFormExhibitId" name="exhibit_id"
            value="{{ !empty($drugExhibits) ? $drugExhibits[0]['id'] ?? $drugExhibits[0]['index'] : '' }}">
        <input type="hidden" id="labFormExhibitOwner" name="exhibit_owner_name"
            value="{{ $activeSuspectName }}">

        <!-- SUB-SECTION 5A: EXAMINATION INFORMATION -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-5">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5A</span>
                    <span>Examination Information</span>
                </h4>
            </div>

            <!-- DYNAMIC EXHIBIT SELECTOR CARD FROM SECTION 4 MANIFEST -->
            <div
                class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-3 shadow-2xs">
                <div class="space-y-2">
                    <!-- SEIZED EXHIBIT SELECTOR FROM SECTION 4 MANIFEST -->
                    <div>
                        <label
                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                            <span class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-emerald-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Select Seized Exhibit to Examine (Manifest Sync)</span> <span
                                    class="text-emerald-700 font-semibold text-[10px]">(Type of Drug)</span>
                                <span class="text-red-600 font-bold">*</span>
                            </span>
                            <span id="labExhibitCountBadge"
                                class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">
                                {{ !empty($drugExhibits) ? count($drugExhibits) . ' Drug Exhibit(s) Synced' : '0 Drug Exhibits Linked' }}
                            </span>
                        </label>
                        <select id="labExhibitSelect" name="exhibit_id"
                            onchange="if(typeof onLabExhibitSelected === 'function') { onLabExhibitSelected(); } if(typeof onReviewLabExhibitChange === 'function') { onReviewLabExhibitChange(this); }"
                            class="hidden">
                            <option value="" {{ empty($drugExhibits) ? 'selected' : '' }}>-- Select Seized Exhibit --</option>
                            @if (!empty($drugExhibits))
                                @foreach ($drugExhibits as $dEx)
                                    @php
                                        $exTitle =
                                            $dEx['name'] ??
                                            ($dEx['cat'] ?? 'Exhibit #' . ($loop->index + 1));
                                        $exMetrics = !empty($dEx['qty'])
                                            ? ' — ' .
                                                $dEx['qty'] .
                                                (!empty($dEx['unit']) &&
                                                !str_contains(
                                                    strtolower($dEx['qty']),
                                                    strtolower($dEx['unit']),
                                                )
                                                    ? ' ' . $dEx['unit']
                                                    : '')
                                            : (!empty($dEx['weight'])
                                                ? ' — ' .
                                                    $dEx['weight'] .
                                                    (!empty($dEx['unit']) &&
                                                    !str_contains(
                                                        strtolower($dEx['weight']),
                                                        strtolower($dEx['unit']),
                                                    )
                                                        ? ' ' . $dEx['unit']
                                                        : '')
                                                : '');
                                        $rawDescBlade = $dEx['pkg'] ?? ($dEx['description'] ?? '');
                                        $cleanDescBlade = $rawDescBlade;
                                        if (!empty($cleanDescBlade) && !empty($exTitle)) {
                                            if (
                                                str_starts_with(
                                                    strtolower($cleanDescBlade),
                                                    strtolower($exTitle),
                                                )
                                            ) {
                                                $cleanDescBlade = trim(
                                                    substr($cleanDescBlade, strlen($exTitle)),
                                                );
                                            }
                                            $cleanDescBlade = trim(
                                                preg_replace('/^[\s\—\-\:\(\)]+/', '', $cleanDescBlade),
                                                ') ',
                                            );
                                        }
                                        $exPkgStr = !empty($cleanDescBlade)
                                            ? ' (' . $cleanDescBlade . ')'
                                            : '';
                                        $exOwnerStr = !empty($dEx['owner'])
                                            ? ' [Owner: ' . $dEx['owner'] . ']'
                                            : '';
                                    @endphp
                                    <option value="{{ $dEx['id'] ?? $dEx['index'] }}"
                                        {{ $loop->first ? 'selected' : '' }}
                                        data-ex-id="{{ $dEx['id'] ?? '' }}"
                                        data-index="{{ $dEx['index'] }}"
                                        data-name="{{ $dEx['name'] }}"
                                        data-cat="{{ $dEx['cat'] ?? 'Drugs' }}"
                                        data-owner="{{ $dEx['owner'] ?? '' }}"
                                        data-qty="{{ $dEx['qty'] ?? '' }}"
                                        data-weight="{{ $dEx['weight'] ?? '' }}"
                                        data-unit="{{ $dEx['unit'] ?? '' }}"
                                        data-pkg="{{ $dEx['pkg'] ?? '' }}"
                                        data-contents="{{ $dEx['contents'] ?? ($dEx['contents_visual'] ?? '') }}"
                                        data-description="{{ $dEx['description'] ?? '' }}"
                                        data-exhibit-number="{{ $dEx['exhibit_number'] ?? '' }}"
                                        data-lab-exhibit-no="{{ $dEx['lab_exhibit_no'] ?? '' }}"
                                        data-is-associate="{{ !empty($dEx['is_associate']) ? '1' : '0' }}"
                                        data-has-saved="{{ !empty($dEx['has_saved_lab']) ? 'true' : 'false' }}"
                                        data-confirmatory="{{ $dEx['confirmatory_result'] ?? 'YES' }}"
                                        data-substance-id="{{ $dEx['substance_identification'] ?? '' }}"
                                        data-test-methods="{{ $dEx['test_methods'] ?? '' }}"
                                        data-specifications="{{ $dEx['specifications'] ?? '' }}"
                                        data-remark="{{ $dEx['remark'] ?? '' }}"
                                        data-sample-taken="{{ $dEx['sample_taken'] ?? '' }}"
                                        data-sample-reserved="{{ $dEx['sample_reserved'] ?? '' }}"
                                        data-detected-substances="{{ is_array($dEx['detected_substances'] ?? null) ? json_encode($dEx['detected_substances']) : $dEx['detected_substances'] ?? '' }}"
                                        data-findings="{{ $dEx['findings'] ?? '' }}"
                                        data-purpose-of-examination="{{ $dEx['purpose_of_examination'] ?? ($initialPurposeOfExamination ?? '') }}"
                                        data-preliminary-exam-procedure="{{ $dEx['preliminary_exam_procedure'] ?? ($initialPreliminaryExamProcedure ?? '') }}"
                                        data-screening-method="{{ is_array($dEx['screening_method'] ?? null) ? json_encode($dEx['screening_method']) : ($dEx['screening_method'] ?? (is_array($initialScreeningMethod ?? null) ? json_encode($initialScreeningMethod) : ($initialScreeningMethod ?? ''))) }}"
                                        data-disposition-of-exhibit="{{ $dEx['disposition_of_exhibit'] ?? ($initialDispositionOfExhibit ?? '') }}">
                                        {{ $exTitle }}{{ $exMetrics }}{{ $exPkgStr }}{{ $exOwnerStr }}
                                    </option>
                                @endforeach
                            @endif
                        </select>

                        <!-- DYNAMIC EXHIBIT SELECTION CONTAINERS -->
                        <div id="labExhibitCardsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                            @if (!empty($drugExhibits))
                                @foreach ($drugExhibits as $dEx)
                                    @php
                                        $itemNum = $loop->iteration;
                                        $exTitle = $dEx['name'] ?? ($dEx['cat'] ?? 'Exhibit #' . $itemNum);
                                        $catName = $dEx['cat'] ?? 'Exhibit';
                                        $exMetrics = !empty($dEx['qty'])
                                            ? $dEx['qty'] . (!empty($dEx['pkg']) ? ' ' . $dEx['pkg'] : (!empty($dEx['unit']) ? ' ' . $dEx['unit'] : ''))
                                            : (!empty($dEx['weight']) ? $dEx['weight'] . (!empty($dEx['unit']) ? ' ' . $dEx['unit'] : '') : 'No metrics specified');
                                        $contentsVisual = $dEx['contents'] ?? ($dEx['contents_visual'] ?? ($dEx['pkg'] ?? 'N/A'));
                                        $ownerName = $dEx['owner'] ?? $activeSuspectName;
                                        $currentYear = date('Y');
                                        $paddedIdx = str_pad($itemNum, 3, '0', STR_PAD_LEFT);
                                        $systemExhibitId = !empty($dEx['exhibit_number']) ? $dEx['exhibit_number'] : sprintf('EXH-%05d', $itemNum);
                                        $cardExhNo = $dEx['lab_exhibit_no'] ?? '';
                                    @endphp
                                    <div class="lab-exhibit-card p-3.5 rounded-xl border transition-all cursor-pointer shadow-2xs {{ $loop->first ? 'border-emerald-500 ring-2 ring-emerald-500/30 bg-emerald-50/40' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-slate-50/70' }}"
                                        data-exhibit-index="{{ $itemNum }}"
                                        data-system-exhibit-number="{{ $systemExhibitId }}"
                                        data-lab-exhibit-no="{{ $cardExhNo }}"
                                        onclick="selectLabExhibitCard({{ $itemNum }})">
                                        <div class="flex items-center justify-between mb-1.5 gap-1.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-md uppercase shrink-0">Exhibit #{{ $itemNum }}</span>
                                                <span class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">{{ $catName }}</span>
                                            </div>
                                            <span class="lab-card-system-exhibit-id text-emerald-700 text-[10px] font-mono-code font-extrabold tracking-wider uppercase shrink-0" data-system-exhibit-number="{{ $systemExhibitId }}" title="System-Generated Exhibit ID">{{ $systemExhibitId }}</span>
                                        </div>
                                        <div class="font-extrabold text-slate-900 text-xs truncate mb-1" title="{{ $exTitle }}">{{ $exTitle }}</div>
                                        <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1 mb-1 truncate" title="Visual Contents: {{ $contentsVisual }}">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span class="truncate"><strong class="font-semibold text-slate-700">Contents:</strong> {{ $contentsVisual }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono-code border-t border-slate-100 pt-1.5 mt-1">
                                            <span class="truncate font-semibold text-emerald-800">{{ $exMetrics }}</span>
                                            <span class="truncate text-slate-400 max-w-[90px]">{{ $ownerName }}</span>
                                        </div>
                                        <div class="mt-2.5 pt-2 border-t border-slate-100" onclick="event.stopPropagation()">
                                            <label class="block text-slate-800 font-extrabold text-[10px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Exhibit No.</span>
                                                <span class="text-[8px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Exhibit ID</span>
                                            </label>
                                            <input type="text"
                                                id="labCardExhibitNo_{{ $itemNum }}"
                                                name="lab_exhibit_nos[]"
                                                data-field="lab_exhibit_no"
                                                value="{{ $cardExhNo }}"
                                                placeholder="A, B...Ai, Aii, Bi, Bii...., A1, A2, B1, B2"
                                                class="card-exhibit-no-input w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600/30 shadow-2xs"
                                                onclick="event.stopPropagation()"
                                                oninput="onCardExhibitNoChange(this, {{ $itemNum }})"
                                            >
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">
                                    No exhibits logged in manifest yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <p
                    class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Each seized exhibit is synchronized from the manifest. Click an exhibit card to select it for focus or view examination specifics below.</span>
                </p>
            </div>

            <!-- REFERENCE NUMBER FIELDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Analysis/Examination Reference -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Analysis / Examination Reference</span>
                        <span
                            class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                    </label>
                    <input type="text" id="labExamReferenceInput" name="lab_exam_reference" value="{{ $labExamRef ?? '' }}"
                        readonly
                        class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                </div>

                <!-- Case Reference -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Case Reference</span>
                        <span
                            class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                    </label>
                    <input type="text" id="labCaseReferenceInput" name="lab_case_reference" value="{{ $labCaseRef ?? ($case->case_number ?? '') }}"
                        readonly
                        class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                </div>

                <!-- Hidden Exhibit Number input synced from active card for form submission -->
                <input type="hidden" id="labExhibitNumberInput" name="lab_exhibit_no"
                    value="{{ $drugExhibits[0]['lab_exhibit_no'] ?? '' }}">
            </div>

            <!-- EXHIBIT DESCRIPTION, LAB NUMBER & ANALYST / EXAMINER -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Exhibit Description -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Exhibit Description</span>
                        <span
                            class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Fetched
                            from Manifest</span>
                    </label>
                    <textarea id="labExhibitDescriptionInput" name="lab_exhibit_description" rows="3"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">{{ $initialLabDesc ?? '' }}</textarea>
                </div>

                <!-- Laboratory Number -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Laboratory Number</span>
                        <span
                            class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                    </label>
                    <input type="text" id="formLabNumber" name="laboratory_number"
                        value="{{ $labNumber ?? ($case->laboratory_number ?? '') }}" readonly
                        class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                </div>

                <!-- Analyst / Examiner -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Analyst / Examiner</span>
                        <span
                            class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Examiner
                            Name</span>
                    </label>
                    <input type="text" id="formLabAnalyst" name="lab_analyst"
                        value="{{ auth()->user()->fullname ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : '')) }}"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs text-xs"
                        placeholder="Examiner / Analyst Name">
                </div>
            </div>

            <!-- DATES TIMELINE -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-slate-100">
                <!-- Date Received -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Date Received <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input type="date" id="labDateReceivedInput" name="lab_date_received" value="{{ $labDateReceived ?? date('Y-m-d') }}"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                </div>

                <!-- Date Examination Started -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Date Examination Started <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input type="date" id="labDateStartedInput" name="lab_date_started" value="{{ $labDateStarted ?? date('Y-m-d') }}"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                </div>

                <!-- Date Examination Completed -->
                <div>
                    <label
                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Date Examination Completed <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input type="date" id="labDateCompletedInput" name="lab_date_completed" value="{{ $labDateCompleted ?? date('Y-m-d') }}"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                </div>
            </div>
        </div>

        <!-- LEGACY FALLBACK COMPATIBILITY HIDDEN FIELDS -->
        <div class="hidden" style="display:none;" aria-hidden="true">
            <input type="checkbox" id="presumptiveTestYes" name="presumptive_test_yes" value="YES">
            <input type="checkbox" id="presumptiveTestNo" name="presumptive_test_no" value="NO" checked>
            <input type="hidden" id="presumptiveReactionResultInput" name="presumptive_reaction_result_input" value="">
            <input type="checkbox" id="confirmatoryTestYes" name="confirmatory_test_yes" value="YES">
            <input type="checkbox" id="confirmatoryTestNo" name="confirmatory_test_no" value="NO" checked>
            <input type="hidden" id="confirmatoryTestResultInput" name="confirmatory_test_result" value="NO">
            <input type="text" id="sampleWeightDescription" name="sample_weight_description" value="">
            <input type="text" id="sampleWeightUnits" name="sample_weight_units" value="">
            <input type="text" id="sampleWeightGross" name="sample_weight_gross" value="">
            <input type="text" id="sampleWeightTaken" name="sample_weight_taken" value="">
            <input type="text" id="sampleWeightReserved" name="sample_weight_reserved" value="">
            <input type="text" id="confirmatorySubstanceIdentification" name="confirmatory_substance_identification" value="">
            <div id="presumptiveReactionDetailsContainer"></div>
            <div id="presumptiveTestRowsContainer"></div>
            <div id="sampleWeightContainer" class="hidden"></div>
            <div id="detectedSubstancesListContainer"></div>
            <!-- Review specific legacy aliases -->
            <input type="radio" id="presumptiveTestYesReview" name="form_presumptive_radio" value="YES">
            <input type="radio" id="presumptiveTestNoReview" name="form_presumptive_radio" value="NO" checked>
            <div id="presumptiveReactionDetailsContainerReview"></div>
            <div id="presumptiveTestRowsContainerReview"></div>
            <input type="hidden" id="presumptiveReactionResultInputReview" value="">
            <input type="radio" id="confirmatoryTestYesReview" name="form_confirmatory_radio" value="YES">
            <input type="radio" id="confirmatoryTestNoReview" name="form_confirmatory_radio" value="NO" checked>
            <div id="sampleWeightContainerReview" class="hidden"></div>
            <input type="text" id="sampleWeightDescriptionReview" name="sample_weight_description_review" value="">
            <input type="text" id="sampleWeightUnitsReview" name="sample_weight_units_review" value="">
            <input type="text" id="sampleWeightGrossReview" name="sample_weight_gross_review" value="">
            <input type="text" id="sampleWeightTakenReview" name="sample_weight_taken_review" value="">
            <span id="sampleWeightTakenUnitAddonReview"></span>
            <p id="sampleWeightTakenErrorReview" class="hidden"></p>
            <input type="text" id="sampleWeightReservedReview" name="sample_weight_reserved_review" value="">
            <input type="text" id="confirmatorySubstanceIdentificationReview" name="confirmatory_substance_identification_review" value="">
            <div id="detectedSubstancesListContainerReview"></div>
            <textarea id="labPreliminaryExamProcedureReview" class="hidden">{{ $initialPreliminaryExamProcedure ?? ($savedLabPreliminaryExamProcedure ?? '') }}</textarea>
            <input type="hidden" id="confirmatoryScreeningMethodReview" value="{{ is_array($initialScreeningMethod ?? null) ? json_encode($initialScreeningMethod) : ($initialScreeningMethod ?? ($savedLabScreeningMethod ?? '')) }}">
            <textarea id="confirmatoryDispositionOfExhibitReview" class="hidden">{{ $initialDispositionOfExhibit ?? ($savedLabDispositionOfExhibit ?? ($case->disposition_of_exhibit ?? '')) }}</textarea>
            <input type="text" id="labNumberInput" name="laboratory_number_alias" value="{{ $labNumber ?? ($case->laboratory_number ?? '') }}">
            <input type="text" id="labAnalystInput" name="lab_analyst_alias" value="{{ auth()->user()->fullname ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : '')) }}">
        </div>

        <!-- SUB-SECTION 5B: PURPOSE OF EXAMINATION -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5B</span>
                    <span>Purpose of Examination</span>
                </h4>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    MANDATORY / SCOPE
                </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">State the formal purpose, scope, and specific analytical objectives of the forensic laboratory examination.</p>

            <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                    <span>Purpose of Examination</span>
                    <span
                        class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Mandatory / Scope</span>
                </label>
                <textarea id="labPurposeOfExaminationInput" name="purpose_of_examination" rows="3"
                    placeholder="State the purpose of examination (e.g. Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage)..."
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs resize-none placeholder:text-slate-400">{{ $initialPurposeOfExamination ?? '' }}</textarea>
            </div>
        </div>

        <!-- SUB-SECTION 5C: DYNAMIC PRESUMPTIVE TEST SECTIONS (Requirement 3) -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5C</span>
                    <span>Presumptive Test (Screening Analysis)</span>
                </h4>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    DYNAMIC PER-EXHIBIT SCREENING
                </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Independent presumptive chemical reaction test containers generated dynamically for each seized exhibit in the manifest.</p>

            <!-- DYNAMIC PRESUMPTIVE CONTAINERS FOR EACH SEIZED EXHIBIT -->
            <div id="presumptiveTestExhibitsContainer" class="space-y-6">
                <!-- Populated dynamically via syncSection4ExhibitsToLabSection() -->
            </div>
        </div>

        <!-- SUB-SECTION 5D: DYNAMIC CONFIRMATORY TEST SECTIONS (Requirement 4) -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-6">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5D</span>
                    <span>Confirmatory Test (Laboratory Verification)</span>
                </h4>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    DYNAMIC PER-EXHIBIT LAB VERIFICATION
                </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Independent confirmatory analysis, metrology & mass, and purity determination for each seized exhibit.</p>

            <!-- DYNAMIC CONFIRMATORY CONTAINERS FOR EACH SEIZED EXHIBIT -->
            <div id="confirmatoryTestExhibitsContainer" class="space-y-6">
                <!-- Populated dynamically via syncSection4ExhibitsToLabSection() -->
            </div>
        </div>

        <!-- SUB-SECTION 5E: PRELIMINARY EXAMINATION PROCEDURE -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5E</span>
                    <span>Preliminary Examination Procedure</span>
                </h4>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    PRELIMINARY / PROCEDURE
                </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Record the detailed preliminary examination procedures, physical inspection protocols, and visual screening methodology applied to the exhibit(s).</p>

            <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                    <span>PRELIMINARY EXAMINATION PROCEDURE</span>
                    <span
                        class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Procedure</span>
                </label>
                <textarea id="labPreliminaryExamProcedureInput" name="preliminary_exam_procedure" rows="3"
                    placeholder="Enter the preliminary examination procedure..."
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs resize-none placeholder:text-slate-400">{{ $initialPreliminaryExamProcedure ?? ($savedLabPreliminaryExamProcedure ?? '') }}</textarea>
            </div>
        </div>

        <!-- SUB-SECTION 5F: SHARED CONFIRMATORY TEST INFORMATION (Requirement 5) -->
        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                <h4
                    class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                    <span
                        class="w-5 h-5 rounded-full bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] flex items-center justify-center font-bold">5F</span>
                    <span>Shared Confirmatory Test Information</span>
                </h4>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    APPLICABLE TO ALL EXHIBITS
                </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Standard test methods, specifications, and overall remarks applicable across all examined exhibits.</p>

            <div class="p-5 bg-white rounded-2xl border border-emerald-300 shadow-2xs space-y-4">
                <div class="space-y-4">
                    <!-- 0. SCREENING METHOD / INSTRUMENT USED INPUT (DYNAMIC REPEATABLE) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Screening method/Instrument Used</span>
                            </label>
                            <button type="button" onclick="addScreeningMethodRow()"
                                class="inline-flex items-center px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-lg shadow-xs transition-all cursor-pointer border-none space-x-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add More</span>
                            </button>
                        </div>
                        <div id="screeningMethodsContainer" class="space-y-2">
                            @php
                                $screeningMethodVals = [];
                                if (!empty($initialScreeningMethod)) {
                                    $screeningMethodVals = is_array($initialScreeningMethod) ? $initialScreeningMethod : (is_string($initialScreeningMethod) && str_starts_with(trim($initialScreeningMethod), '[') ? json_decode($initialScreeningMethod, true) : array_filter(array_map('trim', explode(',', $initialScreeningMethod))));
                                } elseif (!empty($savedLabScreeningMethod) && $savedLabScreeningMethod !== 'N/A') {
                                    $screeningMethodVals = is_array($savedLabScreeningMethod) ? $savedLabScreeningMethod : (is_string($savedLabScreeningMethod) && str_starts_with(trim($savedLabScreeningMethod), '[') ? json_decode($savedLabScreeningMethod, true) : array_filter(array_map('trim', explode(',', $savedLabScreeningMethod))));
                                }
                                if (empty($screeningMethodVals)) {
                                    $screeningMethodVals = [''];
                                }
                            @endphp
                            @foreach ($screeningMethodVals as $sVal)
                                <div class="screening-method-row flex items-center space-x-2">
                                    <input type="text" name="screening_method[]" id="screeningMethodInput"
                                        value="{{ $sVal }}"
                                        placeholder="e.g. Gas Chromatography-Mass Spectrometry (GC-MS), FTIR, Raman..."
                                        class="screening-method-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                                    <button type="button" onclick="removeScreeningMethodRow(this)"
                                        class="remove-screening-method-btn {{ count($screeningMethodVals) > 1 ? '' : 'hidden' }} p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Method">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 1. TEST METHODS INPUT -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                            <span>Test Methods <span class="text-red-600 font-bold">*</span></span>
                            <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Standard Protocol</span>
                        </label>
                        <input type="text" id="confirmatoryTestMethods" name="confirmatory_test_methods"
                            value="{{ $savedLabTestMethods ?: ($labTestMethods ?: ($case->confirmatory_test_methods ?: '')) }}"
                            placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                        <input type="hidden" id="confirmatoryTestMethodsReview" value="{{ $savedLabTestMethods ?: ($labTestMethods ?: ($case->confirmatory_test_methods ?: '')) }}">
                    </div>

                    <!-- 2. SPECIFICATIONS TEXTAREA INPUT -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                            <span>Specifications <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                            <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Standards Matched</span>
                        </label>
                        <textarea id="confirmatorySpecifications" name="confirmatory_specifications" rows="2"
                            placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400">{{ $savedLabSpecifications ?: ($labSpecifications ?: ($case->confirmatory_specifications ?: '')) }}</textarea>
                        <textarea id="confirmatorySpecificationsReview" class="hidden">{{ $savedLabSpecifications ?: ($labSpecifications ?: ($case->confirmatory_specifications ?: '')) }}</textarea>
                    </div>

                    <!-- 3. REMARK TEXTAREA INPUT -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                            <span>Remark <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                            <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">General Observations</span>
                        </label>
                        <textarea id="confirmatoryRemark" name="confirmatory_remark" rows="2"
                            placeholder="Enter any overall remarks or observations regarding the confirmatory analysis across all exhibits..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400">{{ $savedLabRemark ?: ($labRemark ?: ($case->confirmatory_remark ?: '')) }}</textarea>
                        <textarea id="confirmatoryRemarkReview" class="hidden">{{ $savedLabRemark ?: ($labRemark ?: ($case->confirmatory_remark ?: '')) }}</textarea>
                    </div>

                    <!-- 4. DISPOSITION OF EXHIBIT(S) TEXTAREA INPUT -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                            <span>Disposition Of Exhibit(s) <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                            <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Chain of Custody / Vault</span>
                        </label>
                        <textarea id="confirmatoryDispositionOfExhibit" name="disposition_of_exhibit" rows="2"
                            placeholder="Enter the disposition of exhibit(s) (e.g. Retained in forensic vault, Returned to seizing agency, Scheduled for destruction)..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400">{{ $initialDispositionOfExhibit ?? ($savedLabDispositionOfExhibit ?? ($case->disposition_of_exhibit ?? '')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- FINDINGS -->
        <div>
            <label
                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                Laboratory Analytical Findings &amp; Examination Log
            </label>
            <textarea id="formLabNotes" name="lab_findings" rows="3"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                placeholder="Enter chemical reaction results, purity percentage, spectrometry verification notes, etc.">{{ $labFindings ?? '' }}</textarea>
        </div>

        <!-- SUBMIT BUTTON -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <button type="button" id="saveSection5LabBtn"
                onclick="submitCombinedLabAndSeizureForm(event)"
                class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                <span id="saveSection5LabBtnText">Save Seizure Item &amp; Examination
                    Information</span>
            </button>
        </div>
    </form>
</div>
