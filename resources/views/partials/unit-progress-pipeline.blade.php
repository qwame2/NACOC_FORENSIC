{{--
    Partial: partials/unit-progress-pipeline.blade.php
    Description: Renders the multi-unit directorate progress tracker, pipeline cards, and status toolbar.
    Required Inputs (@props):
      - $unitProgress (array): Map of unit name => ['status', 'officer', 'updated_at', 'notes'].
      - $unitsList (array<int, string>): Ordered list of involved unit names.
      - $completedUnitsCount (int): Count of units with COMPLETED status.
      - $totalUnitsCount (int): Total number of involved units.
      - $hofDone (bool): Whether all involved units have completed their work.
      - $isCaseController (bool): Whether current user can change case status.
      - $currentCaseStatus (string): Current case status string.
      - $caseCreatorName (string): Name of the officer who created the case.
      - $caseCreatorUnit (string): Unit of the officer who created the case.
      - $caseCreatedAt (string|null): Formatted creation timestamp.
      - $statusUpdatedBy (string|null): Name of the user who last updated case status.
      - $statusUpdatedByUnit (string|null): Unit of the user who last updated case status.
      - $statusUpdatedAt (string|null): Formatted timestamp of last status update.
--}}@props([
    'unitProgress' => [],
    'unitsList' => [],
    'completedUnitsCount' => 0,
    'totalUnitsCount' => 0,
    'hofDone' => false,
    'isCaseController' => false,
    'currentCaseStatus' => 'Under Investigation',
    'caseCreatorName' => 'Investigating Officer',
    'caseCreatorUnit' => 'NACOC Forensic',
    'caseCreatedAt' => null,
    'statusUpdatedBy' => null,
    'statusUpdatedByUnit' => null,
    'statusUpdatedAt' => null,
])
                <!-- INTER-UNIT WORKFLOW & PROGRESS TRACKER -->
                <div class="dossier-card border border-slate-200 bg-white rounded-2xl shadow-sm overflow-hidden"
                    style="box-shadow: 0 1px 8px 0 rgba(0,0,0,0.06);">

                    {{-- ── HEADER ── --}}
                    <div class="px-6 pt-5 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        style="background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-widest font-mono-code bg-indigo-900 text-indigo-100 border border-indigo-800 shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 17v-6m0 6H5m4 0h4m4-6v6m0 0h-4m4 0h2M9 11V7m6 4V7" />
                                    </svg>
                                    MULTI-UNIT CASE TRACKER
                                </span>
                                <span id="unitCompletedCountBadge"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code border
                                  {{ $completedUnitsCount >= $totalUnitsCount ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300' }}">
                                    {{ $completedUnitsCount }}/{{ $totalUnitsCount }} Units Completed
                                </span>
                            </div>
                            <h2 class="text-base font-black text-slate-900 tracking-tight mt-1">
                                {{ $totalUnitsCount }}-Unit Directorate Progress &amp; Inter-Unit Workflow
                            </h2>
                            <p class="text-[11px] text-slate-500 font-medium leading-snug">
                                All {{ $totalUnitsCount }} units collaborate on this case. Real-time progress updates across specialized directorate units.
                            </p>
                        </div>

                        {{-- Progress pill --}}
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            <div
                                class="text-right text-[10px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">
                                Overall Progress</div>
                            <div class="w-52 bg-slate-200 rounded-full h-5 border border-slate-300 overflow-hidden relative"
                                style="box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
                                <div id="unitProgressBar"
                                    class="h-full rounded-full flex items-center justify-end pr-2 text-[10px] font-mono-code font-extrabold text-white transition-all duration-700"
                                    style="width:{{ max(12, ($completedUnitsCount / max($totalUnitsCount, 1)) * 100) }}%;
                                        background: linear-gradient(90deg, #059669, #10b981);">
                                    {{ round(($completedUnitsCount / max($totalUnitsCount, 1)) * 100) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── PIPELINE FLOW ── --}}
                    <div class="px-6 py-5">
                        {{-- Horizontal scrollable pipeline --}}
                        <div class="overflow-x-auto pb-1 flex justify-center">
                            <div class="flex items-stretch justify-center gap-0 min-w-max mx-auto"
                                id="unitsGridContainer">

                                @foreach ($unitsList as $unitNameKey)
                                    
                                    {{-- Unit card --}}
                                    <div class="flex items-stretch gap-0">
                                        <div id="unitCard-{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey)) }}"
                                            class="w-56 flex flex-col rounded-2xl border-2 transition-all duration-300 overflow-hidden shadow-2xs"
                                            style="{{ strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'COMPLETED' ? 'background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#bbf7d0;' : (strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'IN_PROGRESS' ? 'background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;' : 'background:#f8fafc;border-color:#cbd5e1;') }}">

                                            {{-- Card header stripe --}}
                                            <div class="px-3.5 pt-3.5 pb-2.5 border-b border-white/60 flex items-center justify-between gap-2"
                                                style="background:rgba(255,255,255,0.55);">
                                                <span
                                                    class="text-[9px] font-mono-code font-black uppercase tracking-widest text-slate-600">
                                                    UNIT {{ $loop->iteration }}
                                                </span>
                                                <span id="unitBadge-{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey)) }}"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8.5px] font-extrabold font-mono-code uppercase border {{ strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : (strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-slate-200 text-slate-700 border-slate-400 font-bold') }}">
                                                    {{ strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'COMPLETED' ? 'COMPLETED' : (strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'IN_PROGRESS' ? 'IN PROGRESS' : 'PENDING') }}
                                                </span>
                                            </div>

                                            {{-- Card body --}}
                                            <div class="flex-1 px-3.5 py-3 space-y-2">
                                                {{-- Unit name --}}
                                                <div
                                                    class="{{ in_array(strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING'), ['COMPLETED', 'IN_PROGRESS'], true) ? 'text-slate-900' : 'text-slate-800' }} font-extrabold text-[12px] leading-snug">
                                                    {{ $unitNameKey }}</div>

                                                {{-- Officer display removed from card views --}}
                                                <div id="unitOfficerBox-{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey)) }}" class="hidden"></div>

                                                {{-- Notes snippet --}}
                                                <div id="unitNotesBox-{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey)) }}">
                                                    @if (!empty($unitProgress[$unitNameKey]['notes']))
                                                        <div class="text-[10px] text-slate-800 font-semibold mt-1.5 p-2 rounded-lg border border-slate-200 leading-snug italic"
                                                            style="background:rgba(255,255,255,0.85);">
                                                            "{{ $unitProgress[$unitNameKey]['notes'] }}"
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Card footer: timestamp --}}
                                            <div id="unitUpdatedBox-{{ strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey)) }}"
                                                class="px-3.5 py-2 border-t border-slate-200/60"
                                                style="background:rgba(255,255,255,0.6);">
                                                @if (!empty($unitProgress[$unitNameKey]['updated_at']))
                                                    <div
                                                        class="text-[9.5px] font-mono-code text-slate-600 font-semibold">
                                                        Updated:
                                                        {{ \Carbon\Carbon::parse($unitProgress[$unitNameKey]['updated_at'])->format('M d, Y @ H:i') }}
                                                    </div>
                                                @else
                                                    <div
                                                        class="text-[9.5px] font-mono-code text-slate-600 font-bold italic">
                                                        Awaiting submission</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Arrow connector (not after last unit) --}}
                                        @if (!$loop->last)
                                            <div class="flex items-center justify-center w-8 shrink-0 select-none">
                                                <div class="flex flex-col items-center">
                                                    <div
                                                        class="w-6 border-t-2 border-dashed {{ strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'COMPLETED' ? 'border-emerald-400' : 'border-slate-300' }}">
                                                    </div>
                                                    <svg class="w-3.5 h-3.5 -ml-1 {{ strtoupper($unitProgress[$unitNameKey]['status'] ?? 'PENDING') === 'COMPLETED' ? 'text-emerald-500' : 'text-slate-300' }}"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>{{-- end flex pipeline --}}
                        </div>{{-- end overflow wrapper --}}
                    </div>{{-- end px-6 py-5 --}}

                    <!-- CASE STATUS & CONTROLLER TOOLBAR -->
                    <div
                        class="mx-6 mb-6 p-4 sm:p-5 bg-white text-slate-900 rounded-2xl border-2 border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4 font-sans relative overflow-hidden group">
                        <!-- Subtle Ambient Glow -->
                        <div
                            class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/10 transition-all duration-700">
                        </div>
                        <div
                            class="absolute -left-12 -bottom-12 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none">
                        </div>

                        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-4 flex-wrap">
                            <!-- STATUS LABEL & DROPDOWN CONTAINER -->
                            <div
                                class="flex items-center gap-3 bg-slate-50 p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 shadow-2xs">
                                <div class="flex items-center gap-2 shrink-0">
                                    <span
                                        class="text-[11px] font-black font-mono-code text-slate-600 uppercase tracking-wider shrink-0">CASE
                                        STATUS</span>
                                </div>

                                @if ($isCaseController)
                                    <div class="relative inline-block">
                                        <select id="caseStatusSelect" onchange="updateCaseStatus(this.value)"
                                            class="appearance-none pl-3.5 pr-9 py-1.5 bg-white hover:bg-slate-50 text-slate-950 border border-slate-300 rounded-lg text-xs font-black font-mono-code uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-slate-400/40 focus:border-slate-400 transition-all cursor-pointer shadow-2xs">
                                            @foreach (['Under Investigation', 'On Remand', 'On Bail', 'In Court', 'Verdict Passed — Imprisoned', 'Verdict Passed — Acquitted', 'Case Dismissed', 'Case Closed'] as $stOpt)
                                                <option value="{{ $stOpt }}"
                                                    class="bg-white text-slate-900 font-sans normal-case py-1"
                                                    {{ $currentCaseStatus === $stOpt ? 'selected' : '' }}>
                                                    {{ $stOpt }}</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                @else
                                    <span
                                        class="px-3 py-1 bg-white text-slate-950 border border-slate-300 rounded-lg text-xs font-black font-mono-code uppercase tracking-wider shadow-2xs flex items-center">
                                        {{ $currentCaseStatus }}
                                    </span>
                                @endif
                            </div>

                            <!-- CASE CREATED BY BADGE -->
                            <div
                                class="flex items-center gap-2 text-xs bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 text-slate-700">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span
                                    class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Created By:</span>
                                <span
                                    class="font-black text-slate-900 font-mono-code text-xs px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                                    {{ $caseCreatorName }} <span class="text-emerald-700 font-bold">({{ $caseCreatorUnit }})</span>
                                    @if ($caseCreatedAt)
                                        <span class="text-slate-400 font-normal text-[10px] ml-1">{{ (request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? '|' : '•' }} {{ $caseCreatedAt }}</span>
                                    @endif
                                </span>
                            </div>

                            <!-- CASE STATUS UPDATED BY BADGE -->
                            <div
                                class="flex items-center gap-2 text-xs bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 text-slate-700">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span
                                    class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Status Updated By:</span>
                                <span
                                    class="font-black text-slate-900 font-mono-code text-xs px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                                    @if (!empty($statusUpdatedBy))
                                        {{ $statusUpdatedBy }} <span class="text-amber-700 font-bold">({{ $statusUpdatedByUnit ?: 'Unit Head' }})</span>
                                        @if ($statusUpdatedAt)
                                            <span class="text-slate-400 font-normal text-[10px] ml-1">{{ (request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? '|' : '•' }} {{ $statusUpdatedAt }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-500 font-normal italic">Initial ({{ $caseCreatorName }})</span>
                                    @endif
                                </span>
                            </div>

                            
                        </div>

                        <!-- UNIT HEAD STATUS ACCESS AUTHORIZATION BADGE -->
                        <div class="relative z-10 flex items-center gap-2 shrink-0">
                            @if ($isCaseController)
                                <span
                                    class="text-[11px] text-emerald-800 font-extrabold flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200/80 shadow-2xs">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Unit Head Status Authorized
                                </span>
                            @else
                                <span
                                    class="text-[11px] text-slate-600 font-semibold italic flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                                    Status Modification Reserved for Unit Heads
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- ── ALL COMPLETE BANNER ── --}}
                    <div id="completedUnitsBanner"
                        class="mx-6 mb-6 p-5 rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all duration-500
                            {{ $completedUnitsCount < $totalUnitsCount ? 'hidden' : '' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg shrink-0 bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-2xs">
                                ✓
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code">
                                    Multi-Unit Processing Complete
                                    <span
                                        class="ml-1 text-emerald-600">({{ $completedUnitsCount }}/{{ $totalUnitsCount }}
                                        Units)</span>
                                </h4>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    All {{ $totalUnitsCount }} specialized units have completed and saved their
                                    findings.
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-3 py-1.5 rounded-full text-[10px] font-mono-code font-extrabold uppercase tracking-wider shrink-0 bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">
                            ALL UNITS COMPLETED
                        </span>
                    </div>

                </div>{{-- end dossier-card --}}
