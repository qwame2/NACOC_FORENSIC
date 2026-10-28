<!-- EXAMINATION INFORMATION CONTAINER (ALWAYS VISIBLE AT LAB ANALYSIS STAGE WHEN UNSAVED) -->
<div id="section5FormContainer"
    class= "mt-8 bg-white/90 border border-slate-300/80 rounded-2xl p-6 shadow-sm space-y-6 {{ $activeSuspectHasSavedResult || !$isLabAnalysis ? 'hidden' : '' }}">
    <div
        class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

        <div class="flex items-center space-x-3">
            <div
                class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
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
                        class="px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $activeSuspectHasSavedResult ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                        {{ $activeSuspectHasSavedResult ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? 'LAB IN PROGRESS' : 'LAB PENDING') }}
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

        <!-- EXHIBIT SELECTOR CARD FROM SECTION 4 MANIFEST -->
        <div
            class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-3 shadow-2xs">
            <div class="grid grid-cols-1 gap-4">

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
                        onchange="onReviewLabExhibitChange(this)"
                        class="w-full px-3.5 py-3 bg-white border border-emerald-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                        {{ empty($drugExhibits) ? 'disabled' : '' }}>
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
                                    data-owner="{{ $dEx['owner'] ?? '' }}"
                                    data-qty="{{ $dEx['qty'] ?? '' }}"
                                    data-weight="{{ $dEx['weight'] ?? '' }}"
                                    data-unit="{{ $dEx['unit'] ?? '' }}"
                                    data-pkg="{{ $dEx['pkg'] ?? '' }}"
                                    data-description="{{ $dEx['description'] ?? '' }}"
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
                                    data-findings="{{ $dEx['findings'] ?? '' }}">
                                    {{ $exTitle }}{{ $exMetrics }}{{ $exPkgStr }}{{ $exOwnerStr }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <p class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Select a drug type and exhibit above to load Section 4 manifest details
                    and process forensic examination.</span>
            </p>
        </div>

        <!-- REFERENCE NUMBER FIELDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Analysis/Examination Reference -->
            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                    <span>Analysis / Examination Reference</span>
                    <span
                        class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                </label>
                <input type="text" id="labExamReferenceInput" value="{{ $labExamRef }}"
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
                <input type="text" id="labCaseReferenceInput" value="{{ $labCaseRef }}"
                    readonly
                    class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
            </div>

            <!-- Exhibit Number -->
            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                    <span>Exhibit Number</span>
                    <span
                        class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                </label>
                <input type="text" id="labExhibitNumberInput" value="{{ $labExhibitNo }}"
                    readonly
                    class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
            </div>
        </div>

        <!-- EXHIBIT DESCRIPTION, LAB UNIT & ANALYST / EXAMINER -->
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
                <textarea id="labExhibitDescriptionInput" rows="3"
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
                    value="{{ $labNumber }}" readonly
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
                <input type="text" id="formLabAnalyst" value=""
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
                <input type="date" id="labDateReceivedInput" value="{{ $labDateReceived }}"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
            </div>

            <!-- Date Examination Started -->
            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                    Date Examination Started <span class="text-red-600 font-bold">*</span>
                </label>
                <input type="date" id="labDateStartedInput" value="{{ $labDateStarted }}"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
            </div>

            <!-- Date Examination Completed -->
            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                    Date Examination Completed <span class="text-red-600 font-bold">*</span>
                </label>
                <input type="date" id="labDateCompletedInput" value="{{ $labDateCompleted }}"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
            </div>
        </div>

        <!-- SECTION 6: PRESUMPTIVE TEST CARD -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
            <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Presumptive Test
                        </h3>
                        <p class="text-[11px] text-emerald-700 font-semibold">Initial chemical
                            field screening &amp; colorimetric reaction validation</p>
                    </div>
                </div>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    SCREENING RESULT
                </span>
            </div>

            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                    Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                </label>

                @php
                    $pIsYes =
                        !empty($labPresumptive) &&
                        (str_starts_with(strtoupper($labPresumptive), 'YES') ||
                            str_starts_with(strtoupper($labPresumptive), 'POS'));
                @endphp
                <div class="flex items-center space-x-3">
                    <!-- YES CHECKBOX -->
                    <label id="labelPresumptiveYes"
                        class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ !empty($initialHasSaved) && ($pIsYes ?? false) ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" id="presumptiveTestYesReview"
                            name="form_presumptive_radio" value="YES"
                            {{ !empty($initialHasSaved) && ($pIsYes ?? false) ? 'checked' : '' }}
                            onchange="onPresumptiveRadioChange('YES')"
                            class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        <div class="flex items-center space-x-1.5">
                            <span
                                class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                        </div>
                    </label>

                    <!-- NO CHECKBOX -->
                    <label id="labelPresumptiveNo"
                        class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ !empty($initialHasSaved) && !($pIsYes ?? false) && ($labPresumptive ?? 'N/A') !== 'N/A' ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" id="presumptiveTestNoReview"
                            name="form_presumptive_radio" value="NO"
                            {{ !empty($initialHasSaved) && !($pIsYes ?? false) && ($labPresumptive ?? 'N/A') !== 'N/A' ? 'checked' : '' }}
                            onchange="onPresumptiveRadioChange('NO')"
                            class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer">
                        <div class="flex items-center space-x-1.5">
                            <span
                                class="font-black text-xs text-slate-900 font-mono-code uppercase">NO</span>
                        </div>
                    </label>
                </div>

                <!-- DROPDOWN INPUT BOXES FOR PRESUMPTIVE TEST REACTION DETAILS -->
                <div id="presumptiveReactionDetailsContainerReview"
                    class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ !$pIsYes ? 'hidden' : '' }}">
                    <div
                        class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                        <div
                            class="flex items-center space-x-2 text-emerald-800 font-extrabold text-xs uppercase tracking-wider">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span>Presumptive Test Reaction Result Details &amp; Field
                                Spec</span>
                        </div>
                        <span
                            class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                            MULTIPLE SPECIMEN SUPPORT
                        </span>
                    </div>

                    <!-- DYNAMIC TEST ROWS CONTAINER -->
                    <div id="presumptiveTestRowsContainerReview" class="space-y-3">
                        <div
                            class="presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5">
                            <div
                                class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                <span class="test-row-title-review">Test Result #1</span>
                                <button type="button"
                                    onclick="removePresumptiveTestRowReview(this)"
                                    class="remove-test-row-btn-review hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                                    &times; Remove
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Presence of <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <input type="text"
                                        class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs"
                                        onchange="updatePresumptiveReactionTextReview()"
                                        oninput="updatePresumptiveReactionTextReview()"
                                        placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                                </div>
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Percentage (%) <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div
                                        class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                                        <input type="text"
                                            class="presumptive-percentage-input-review w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code"
                                            onchange="updatePresumptiveReactionTextReview()"
                                            oninput="updatePresumptiveReactionTextReview()"
                                            placeholder="e.g. 85.5 or 80-90">
                                        <span
                                            class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ADD MORE TEST RESULT BUTTON -->
                    <div class="pt-1 flex items-center justify-between">
                        <button type="button" onclick="addPresumptiveTestRowReview()"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Add More Test Result</span>
                        </button>
                        <span class="text-[10px] text-slate-500 font-medium italic">Click to
                            log additional chemical/substance reaction test results</span>
                    </div>

                    <!-- Hidden Presumptive Reaction Result for Form Submission -->
                    <input type="hidden" id="presumptiveReactionResultInputReview"
                        value="{{ $labPresumptive }}">
                </div>
            </div>
        </div>

        <!-- CONFIRMATORY TEST CARD (UNDERNEATH PRESUMPTIVE TEST SECTION) -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
            <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Confirmatory Test
                        </h3>
                        <p class="text-[11px] text-emerald-700 font-semibold">Instrumental
                            &amp; Spectrometry Laboratory Confirmation (GC-MS / FTIR)</p>
                    </div>
                </div>
                <span
                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    LAB VERIFICATION
                </span>
            </div>

            <div>
                <label
                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                    Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                </label>

                @php
                    $rawCurConf = !empty($savedLabConfirmatory)
                        ? $savedLabConfirmatory
                        : (!empty($labConfirmatory)
                            ? $labConfirmatory
                            : (!empty($case->confirmatory_test_result)
                                ? $case->confirmatory_test_result
                                : 'YES'));
                    $isConfNo = strtoupper(trim((string) $rawCurConf)) === 'NO';
                    $isConfYes = !$isConfNo;
                @endphp
                <div class="flex items-center space-x-3">
                    <!-- YES RADIO -->
                    <label id="labelConfirmatoryYesReview"
                        class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $isConfYes ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" id="confirmatoryTestYesReview"
                            name="form_confirmatory_radio" value="YES"
                            {{ $isConfYes ? 'checked' : '' }}
                            onchange="toggleConfirmatoryTestReview('YES')"
                            class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        <div class="flex items-center space-x-1.5">
                            <span
                                class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                        </div>
                    </label>

                    <!-- NO RADIO -->
                    <label id="labelConfirmatoryNoReview"
                        class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $isConfNo ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" id="confirmatoryTestNoReview"
                            name="form_confirmatory_radio" value="NO"
                            {{ $isConfNo ? 'checked' : '' }}
                            onchange="toggleConfirmatoryTestReview('NO')"
                            class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer">
                        <div class="flex items-center space-x-1.5">
                            <span
                                class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                        </div>
                    </label>
                </div>

                <!-- SAMPLE WEIGHT GROUP INPUT CONTAINER (DYNAMICALLY SHOWN WHEN CONFIRMATORY TEST IS YES) -->
                <div id="sampleWeightContainerReview"
                    class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ $isConfYes ? '' : 'hidden' }}">
                    <div
                        class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                        <div
                            class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                            <span>SAMPLE WEIGHT</span>
                        </div>
                        <span
                            class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                            CONFIRMATORY METROLOGY &amp; MASS
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- DESCRIPTION -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Description <span class="text-red-600 font-bold">*</span></span>
                                <span
                                    class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                            </label>
                            <input type="text" id="sampleWeightDescriptionReview"
                                name="sample_weight_description" value="{{ $labSampleDesc }}"
                                readonly placeholder="Fetched from drug manifest"
                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed">
                        </div>

                        <!-- NUMBER OF UNITS -->
                        <div>
                            <label
                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Number of Units <span
                                        class="text-red-600 font-bold">*</span></span>
                                <span
                                    class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                            </label>
                            <input type="text" id="sampleWeightUnitsReview"
                                name="sample_weight_units" value="{{ $labSampleUnits }}" readonly
                                placeholder="Fetched from drug manifest"
                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                        </div>

                        <!-- GROSS WEIGHT -->
                        <div>
                            <label
                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Gross Weight <span
                                        class="text-red-600 font-bold">*</span></span>
                                <span
                                    class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                            </label>
                            <input type="text" id="sampleWeightGrossReview"
                                name="sample_weight_gross" value="{{ $labSampleGross }}" readonly
                                placeholder="Fetched from drug manifest"
                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                        </div>

                        <!-- SAMPLE TAKEN -->
                        <div>
                            <label
                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                Sample Taken <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex rounded-xl shadow-2xs">
                                <input type="text" id="sampleWeightTakenReview"
                                    name="sample_weight_taken" value="{{ $labSampleTaken }}"
                                    placeholder="e.g. 10"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0">
                                <span id="sampleWeightTakenUnitAddonReview"
                                    class="inline-flex items-center px-3 py-2.5 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0 transition-colors duration-200">
                                    {{ $labSampleUnit }}
                                </span>
                            </div>
                            <p id="sampleWeightTakenErrorReview"
                                class="text-[11px] text-red-600 font-extrabold mt-1.5 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Sample taken cannot exceed the gross weight</span>
                            </p>
                        </div>

                        <!-- RESERVED SAMPLES -->
                        <div>
                            <label
                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Reserved Samples <span
                                        class="text-red-600 font-bold">*</span></span>
                                <span
                                    class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-CALCULATED</span>
                            </label>
                            <input type="text" id="sampleWeightReservedReview"
                                name="sample_weight_reserved" value="{{ $labSampleReserved }}"
                                readonly placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed">
                        </div>

                        <!-- RESULT SECTION (UNDERNEATH SAMPLE WEIGHT) -->
                        <div class="md:col-span-2 pt-4 border-t border-emerald-200/80 space-y-4">
                            <div
                                class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                <div
                                    class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                </div>
                                <span
                                    class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                    ANALYTICAL FINDINGS &amp; SPECS
                                </span>
                            </div>

                            <div class="space-y-4">
                                <!-- 1. SUBSTANCE IDENTIFICATION -->
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Substance Identification <span
                                                class="text-red-600 font-bold">*</span></span>
                                        <span
                                            class="text-[9px] font-mono-code font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 uppercase">EDITABLE SPECIFICATION</span>
                                    </label>
                                    <input type="text"
                                        id="confirmatorySubstanceIdentificationReview"
                                        name="confirmatory_substance_identification"
                                        value="{{ $savedLabSubstanceId ?: ($labSubstanceId ?: ($case->confirmatory_substance_identification ?: '')) }}"
                                        placeholder="Enter substance identification (e.g. Heroin, Cocaine, Cannabis)"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans">
                                </div>

                                <!-- 2. RESULT (INPUT BOX WITH ADD MORE) -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                            Result (Detected Substances &amp; Purity %) <span
                                                class="text-red-600 font-bold">*</span>
                                        </label>
                                        <button type="button"
                                            onclick="addDetectedSubstanceRowReview()"
                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Add More</span>
                                        </button>
                                    </div>
                                    <div id="detectedSubstancesListContainerReview"
                                        class="space-y-2.5">
                                        <!-- Dynamic rows added via JS -->
                                    </div>
                                </div>

                                <!-- 3. TEST METHODS INPUT -->
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Test Methods <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <input type="text" id="confirmatoryTestMethodsReview"
                                        name="confirmatory_test_methods"
                                        value="{{ $savedLabTestMethods ?: ($labTestMethods ?: ($case->confirmatory_test_methods ?: '')) }}"
                                        placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                                </div>

                                <!-- 4. SPECIFICATIONS TEXTAREA INPUT -->
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Specifications <span
                                            class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                    </label>
                                    <textarea id="confirmatorySpecificationsReview" name="confirmatory_specifications" rows="3"
                                        placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400">{{ $savedLabSpecifications ?: ($labSpecifications ?: ($case->confirmatory_specifications ?: '')) }}</textarea>
                                </div>

                                <!-- 5. REMARK TEXTAREA INPUT -->
                                <div>
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Remark <span
                                            class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                    </label>
                                    <textarea id="confirmatoryRemarkReview" name="confirmatory_remark" rows="3"
                                        placeholder="Enter any additional remarks or observations regarding the confirmatory analysis..."
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400">{{ $savedLabRemark ?: ($labRemark ?: ($case->confirmatory_remark ?: '')) }}</textarea>
                                </div>
                            </div>
                        </div>
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
            <textarea id="formLabNotes" rows="3"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                placeholder="Enter chemical reaction results, purity percentage, spectrometry verification notes, etc.">{{ $labFindings }}</textarea>
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
