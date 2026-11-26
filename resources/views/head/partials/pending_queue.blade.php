@php
    $passedExtractor = $extractSubmissionMeta ?? null;
    $passedReviewUrl = $reviewUrlFor ?? null;
    $safeUnitKey = $unitKey ?? null;

    $extractMeta = function ($sub) use ($passedExtractor) {
        if ($passedExtractor && is_callable($passedExtractor)) {
            return $passedExtractor($sub);
        }
        if (method_exists($sub, 'getSubmissionMeta')) {
            return $sub->getSubmissionMeta();
        }
        return [
            'suspect_count' => !empty($sub->suspect_name) ? 1 : 0,
            'exhibit_count' => 0,
            'offense'       => '',
            'location'      => '',
            'case_ref'      => $sub->case_number_preview ?? '',
            'mugshot_url'   => null,
            'suspects'      => [],
            'is_multiple_suspects' => false,
        ];
    };
    $resolveUrl = function ($sub) use ($safeUnitKey, $passedReviewUrl) {
        if ($passedReviewUrl && is_callable($passedReviewUrl)) {
            return $passedReviewUrl($sub->id);
        }
        if (method_exists($sub, 'getReviewUrl')) {
            return $sub->getReviewUrl($safeUnitKey);
        }
        return url('/Crime_Scene_Support/reviews-approvals/' . $sub->id);
    };
@endphp

@if($pendingSubmissions->isEmpty())
    <div class="bg-white rounded-2xl p-12 text-center shadow-sm">
        <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h4 class="text-sm font-extrabold text-slate-900">Queue Clear for {{ $unitLabel }}</h4>
        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            No pending staff case submissions are currently waiting for review. Newly submitted dockets from field officers will appear here automatically.
        </p>
    </div>
@else
    <div id="pendingQueueList" class="space-y-3">
        @foreach($pendingSubmissions as $sub)
            @php
                $meta = $extractMeta($sub);
                $prio = \App\Models\ForensicCase::normalizePriority($sub->priority ?: 'LOW');
                $isUrgent = $prio === 'HIGH';
                $prioTextColor = $prio === 'HIGH' ? 'text-red-600' : 'text-emerald-700';
                $reviewUrl = $resolveUrl($sub);
                $subCode = 'SUB-' . str_pad((string) $sub->id, 4, '0', STR_PAD_LEFT);
                $isMulti = !empty($meta['is_multiple_suspects']) && count($meta['suspects'] ?? []) >= 2;
                $sList = $meta['suspects'] ?? [];
                $isEditSub = (!empty($sub->payload['is_edit_submission']) || (!empty($sub->payload['submission_type']) && $sub->payload['submission_type'] === 'edit'));
                $searchHaystack = strtolower(implode(' ', array_filter([
                    $subCode,
                    $sub->suspect_name,
                    $isMulti ? ($sList[0]['name'] ?? '') : '',
                    $isMulti ? ($sList[1]['name'] ?? '') : '',
                    $sub->title,
                    $sub->submitted_by_name,
                    $sub->unit_name,
                    $prio,
                    $meta['case_ref'],
                    $meta['offense'],
                    $meta['location'],
                    $isEditSub ? 'edited case edit' : '',
                ])));
            @endphp
            <article
                class="submission-card rounded-2xl p-4 sm:p-5 pending-item"
                data-priority="{{ $prio }}"
                data-urgent="{{ $isUrgent ? '1' : '0' }}"
                data-status="{{ $sub->status }}"
                data-is-edit="{{ $isEditSub ? '1' : '0' }}"
                data-date="{{ optional($sub->created_at)->format('Y-m-d') }}"
                data-search="{{ $searchHaystack }}"
            >
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                    <!-- COL 1: SUBMISSION REF & PRIORITY (NO BG CONTAINERS, NO DOTS) -->
                    <div class="lg:col-span-2 space-y-1">
                        <a href="{{ $reviewUrl }}" class="font-mono-code font-extrabold text-sm text-slate-900 hover:text-emerald-700 transition-colors text-decoration-none block">
                            {{ $subCode }}
                        </a>
                        <div class="font-mono-code font-extrabold text-[11px] uppercase tracking-wide {{ $prioTextColor }}">
                            {{ $prio }} PRIORITY
                        </div>
                        @if($isEditSub)
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-amber-100 text-amber-950 border border-amber-300">
                                    <svg class="w-3 h-3 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edited Case</span>
                                </span>
                            </div>
                        @endif
                        @if($sub->status === 'Rolled Back')
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-green-100 text-green-950 border-none">
                                    Awaiting Corrections
                                </span>
                            </div>
                        @elseif(!empty($sub->payload['was_corrected']) || !empty($sub->payload['resubmitted_at']))
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-blue-100 text-blue-950 border-none" title="Staff officer has updated the case and returned it for review">
                                    <svg class="w-3 h-3 text-blue-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Corrections Submitted</span>
                                </span>
                            </div>
                        @else
                            <div class="text-[11px] text-slate-400 font-mono-code">
                                {{ optional($sub->created_at)->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>

                    <!-- COL 2: SUSPECT PHOTO / IDENTITY & CASE TITLE -->
                    <div class="lg:col-span-5 flex items-start gap-3.5 min-w-0">
                        @if($isMulti)
                            <!-- DUAL SUSPECT FRONT IMAGES FOR MULTIPLE SUSPECT CASE -->
                            <div class="flex items-center gap-2 shrink-0">
                                @foreach(array_slice($sList, 0, 2) as $sIdx => $sData)
                                    @php
                                        $sBadge = ($sIdx === 0) ? 'S1' : 'S2';
                                        $sBadgeBg = ($sIdx === 0) ? 'bg-slate-900 text-white' : 'bg-amber-600 text-white';
                                        $sRingBorder = ($sIdx === 0) ? 'border-slate-300' : 'border-amber-400';
                                        $sRoleLabel = ($sIdx === 0) ? 'Primary Suspect' : 'Co-Suspect';
                                        $sSuspectName = $sData['name'] ?? "Suspect " . ($sIdx + 1);
                                        $sTitle = "{$sRoleLabel}: {$sSuspectName}";
                                    @endphp
                                    <div class="relative group" title="{{ $sTitle }}">
                                        @if(!empty($sData['mugshot_url']))
                                            <img
                                                src="{{ $sData['mugshot_url'] }}"
                                                alt="{{ $sTitle }}"
                                                width="48"
                                                height="48"
                                                class="w-12 h-12 rounded-xl object-cover border {{ $sRingBorder }} shadow-xs transition-transform group-hover:scale-105"
                                                onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                                            >
                                            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 items-center justify-center border {{ $sRingBorder }}" style="display: none;" aria-hidden="true">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center border {{ $sRingBorder }}" aria-hidden="true">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-1 -right-1 px-1 py-0.2 {{ $sBadgeBg }} text-[8.5px] font-mono-code font-bold rounded shadow-xs leading-none">
                                            {{ $sBadge }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- SINGLE SUSPECT FRONT IMAGE -->
                            @if(!empty($meta['mugshot_url']))
                                <img
                                    src="{{ $meta['mugshot_url'] }}"
                                    alt="{{ $sub->suspect_name ?: 'Suspect Mugshot' }}"
                                    width="48"
                                    height="48"
                                    class="w-12 h-12 rounded-xl object-cover shrink-0"
                                    onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                                >
                                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 items-center justify-center shrink-0" style="display: none;" aria-hidden="true">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                        @endif

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-baseline gap-2.5 flex-wrap">
                                <a href="{{ $reviewUrl }}" class="text-sm sm:text-[15px] font-extrabold text-slate-900 hover:text-emerald-700 transition-colors truncate text-decoration-none">
                                    @if($isMulti && !empty($sList[0]['name']) && !empty($sList[1]['name']) && !str_contains(strtolower((string) $sub->suspect_name), strtolower((string) $sList[1]['name'])))
                                        {{ $sList[0]['name'] }} <span class="text-slate-400 font-normal">&amp;</span> {{ $sList[1]['name'] }}
                                    @else
                                        {{ $sub->suspect_name ?: 'Unnamed Suspect' }}
                                    @endif
                                </a>
                                @if($isEditSub)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-amber-100 text-amber-950 border border-amber-300">
                                        <svg class="w-3 h-3 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edited Case</span>
                                    </span>
                                @endif
                                @if(!empty($meta['case_ref']))
                                    <span class="text-[11px] font-mono-code font-semibold text-slate-500">
                                        Ref: {{ $meta['case_ref'] }}
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs font-medium text-slate-600 flex items-center gap-1.5 flex-wrap">
                                <span class="truncate max-w-[320px]">{{ $sub->title ?: 'Staff Case Submission' }}</span>
                                @if($isEditSub)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-mono-code font-bold bg-amber-50 text-amber-900 border border-amber-300">
                                        <svg class="w-2.5 h-2.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edited Case</span>
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 pt-0.5">
                                <span>{{ $meta['suspect_count'] }} {{ $meta['suspect_count'] === 1 ? 'Suspect' : 'Suspects' }}</span>
                                @if($meta['exhibit_count'] > 0)
                                    <span class="text-slate-300">|</span>
                                    <span>{{ $meta['exhibit_count'] }} {{ $meta['exhibit_count'] === 1 ? 'Exhibit' : 'Exhibits' }}</span>
                                @endif
                                @if(!empty($meta['offense']))
                                    <span class="text-slate-300">|</span>
                                    <span class="truncate max-w-[200px]">{{ $meta['offense'] }}</span>
                                @endif
                                @if(!empty($meta['location']))
                                    <span class="text-slate-300">|</span>
                                    <span class="truncate max-w-[180px]">{{ $meta['location'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- COL 3: SUBMITTED BY & UNIT (PURE TYPOGRAPHY) -->
                    <div class="lg:col-span-2 min-w-0 space-y-0.5 lg:pl-4">
                        <div class="text-[10px] font-mono-code uppercase tracking-wider text-slate-400 font-semibold">Submitted By</div>
                        <div class="text-xs font-bold text-slate-800 truncate">{{ $sub->submitted_by_name ?: 'Staff Officer' }}</div>
                        <div class="text-[11px] font-medium text-slate-500 truncate">{{ $sub->unit_name }}</div>
                    </div>

                    <!-- COL 4: DIRECT ACTIONS -->
                    <div class="lg:col-span-3 flex items-center justify-start lg:justify-end shrink-0">
                        <a
                            href="{{ $reviewUrl }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors text-decoration-none"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>{{ (!empty($sub->payload['was_corrected']) || !empty($sub->payload['resubmitted_at'])) ? 'Review Corrections' : 'Review Dossier' }}</span>
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <!-- FILTERED EMPTY STATE FOR PENDING QUEUE -->
    <div id="pendingFilterEmpty" class="hidden bg-white rounded-2xl shadow-sm p-10 text-center">
        <h4 class="text-sm font-bold text-slate-900">No Matching Pending Submissions</h4>
        <p class="text-xs text-slate-500 mt-1">Adjust your search query or selected priority tab to see staged submissions.</p>
        <button type="button" onclick="resetAllQueueFilters()" class="mt-3 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
            Reset Filters
        </button>
    </div>
@endif
