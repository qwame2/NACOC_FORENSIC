@php
    $exhibits = $exhibits ?? collect();
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse" id="chainOfCustodyTable">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-600">
                <th class="py-3.5 px-4 whitespace-nowrap">Date &amp; Time</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Exhibit Ref</th>
                <th class="py-3.5 px-4">Case &amp; Suspect</th>
                <th class="py-3.5 px-4">Commodity</th>
                <th class="py-3.5 px-4 min-w-[280px]">Custody Pathway</th>
                <th class="py-3.5 px-4 text-right whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/80 text-xs font-medium text-slate-800 bg-white" id="chainOfCustodyTableBody">
            @forelse($exhibits as $ex)
                @php
                    $caseRef = $ex->case_number ?: ($ex->singleSuspectCase?->case_number ?: ($ex->multipleSuspectCase?->case_number ?: 'N/A'));
                    $caseTitle = $ex->singleSuspectCase?->title ?: ($ex->multipleSuspectCase?->title ?: 'Unassigned Case');
                    $suspectName = $ex->singleSuspectCase?->suspect_name ?: ($ex->associated_person ?: ($ex->suspect?->suspect_name ?: 'N/A'));
                    $reviewUrl = $ex->case_review_url ?: ($caseRef !== 'N/A' ? url('/cases/' . urlencode($caseRef) . '/review?from=Evidence_Management', [], false) : '#');

                    $coc = trim($ex->chain_of_custody ?? '') ?: $ex->resolved_chain_of_custody;
                    $cocSteps = array_values(array_filter(array_map('trim', preg_split('/\s*(?:->|➔|→|\|)\s*/u', $coc) ?: [])));
                    if (empty($cocSteps)) {
                        $cocSteps = [$coc];
                    }

                    $status = $ex->custodial_status ?: 'Seized';

                    $categoryBadgeClass = match(strtolower(trim($ex->exhibit_category ?? 'general'))) {
                        'drugs', 'drug', 'narcotics' => 'bg-red-50 text-red-700 border-red-200',
                        'weapons', 'weapon', 'firearms' => 'bg-orange-50 text-orange-700 border-orange-200',
                        'vehicles', 'vehicle' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'devices', 'device', 'electronics' => 'bg-violet-50 text-violet-700 border-violet-200',
                        'money', 'currency', 'cash' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };
                @endphp
                <tr class="coc-row hover:bg-slate-50/80 transition-colors"
                    data-exhibit-id="{{ $ex->id }}"
                    data-exhibit-number="{{ $ex->exhibit_number }}"
                    data-case-number="{{ $caseRef }}"
                    data-status="{{ strtoupper($status) }}"
                    data-coc="{{ $coc }}">

                    <!-- 1. DATE & TIME -->
                    <td class="py-3.5 px-4 whitespace-nowrap align-top">
                        <div class="font-bold text-slate-900 tnum">
                            {{ $ex->created_at ? $ex->created_at->format('d M Y, H:i') : ($ex->seizure_date ?: 'N/A') }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">
                            {{ $ex->created_at ? $ex->created_at->diffForHumans() : 'Seizure Record' }}
                        </div>
                    </td>

                    <!-- 2. EXHIBIT REF & PROPERTY ID -->
                    <td class="py-3.5 px-4 whitespace-nowrap align-top">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono-code font-bold text-[11px] bg-slate-100 text-slate-900 border border-slate-300">
                                {{ $ex->exhibit_number ?: 'EXH-PENDING' }}
                            </span>
                            @if($ex->lab_exhibit_no)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono-code font-bold bg-purple-50 text-purple-800 border border-purple-200" title="Lab Exhibit No">
                                    Lab #{{ $ex->lab_exhibit_no }}
                                </span>
                            @endif
                        </div>
                        @if($ex->property_id)
                            <div class="text-[10px] font-mono-code text-slate-500 mt-1">
                                Property: <span class="text-slate-700 font-semibold">{{ $ex->property_id }}</span>
                            </div>
                        @endif
                        @if($ex->qr_token)
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 text-[10px] font-mono-code font-semibold text-emerald-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>QR Verified</span>
                                </span>
                            </div>
                        @endif
                    </td>

                    <!-- 3. CASE & SUSPECT -->
                    <td class="py-3.5 px-4 max-w-[230px] align-top">
                        <div class="font-mono-code font-bold text-xs text-emerald-700 truncate" title="{{ $caseRef }}">
                            @if($reviewUrl !== '#')
                                <a href="{{ $reviewUrl }}" class="hover:underline inline-flex items-center gap-1">
                                    <span>{{ $caseRef }}</span>
                                    <svg class="w-3 h-3 opacity-60 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            @else
                                {{ $caseRef }}
                            @endif
                        </div>
                        <div class="font-bold text-slate-900 text-[11px] truncate mt-0.5" title="{{ $caseTitle }}">
                            {{ $caseTitle }}
                        </div>
                        <div class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $suspectName }}">
                            Suspect: <span class="font-semibold text-slate-700">{{ $suspectName }}</span>
                        </div>
                    </td>

                    <!-- 4. COMMODITY -->
                    <td class="py-3.5 px-4 max-w-[200px] align-top">
                        <div class="font-bold text-slate-900 text-xs truncate" title="{{ $ex->commodity_name }}">
                            {{ $ex->commodity_name ?: 'Forensic Exhibit' }}
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap mt-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono-code uppercase font-bold border {{ $categoryBadgeClass }}">
                                {{ $ex->exhibit_category ?: 'General' }}
                            </span>
                            @if($ex->drug_classification)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ $ex->drug_classification }}
                                </span>
                            @endif
                        </div>
                        @if($ex->seizure_weight || $ex->seizure_quantity || $ex->seizure_packaging)
                            <div class="text-[10px] text-slate-500 font-mono-code mt-1 truncate">
                                @if($ex->seizure_weight)
                                    <span>{{ $ex->seizure_weight }} {{ $ex->seizure_unit ?: 'g' }}</span>
                                @elseif($ex->seizure_quantity)
                                    <span>Qty: {{ $ex->seizure_quantity }}</span>
                                @endif
                                @if($ex->seizure_packaging)
                                    <span>&bull; {{ $ex->seizure_packaging }}</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <!-- 5. CUSTODY PATHWAY -->
                    <td class="py-3.5 px-4 min-w-[280px] max-w-md align-top">
                        <div class="flex items-center flex-wrap gap-1.5">
                            @foreach($cocSteps as $stepIdx => $stepLabel)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono-code font-semibold {{ $loop->last ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $stepLabel }}
                                </span>
                                @if(!$loop->last)
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                @endif
                            @endforeach
                        </div>
                        @if($ex->source_of_exhibits || $ex->source_requesting_unit || $ex->seizure_location)
                            <div class="text-[10px] text-slate-500 font-mono-code mt-1.5 flex items-center flex-wrap gap-x-3 gap-y-0.5">
                                @if($ex->source_of_exhibits || $ex->source_requesting_unit)
                                    <span class="truncate" title="Origin Source: {{ $ex->source_of_exhibits ?: $ex->source_requesting_unit }}">
                                        Origin: <span class="font-semibold text-slate-700">{{ $ex->source_of_exhibits ?: $ex->source_requesting_unit }}</span>
                                    </span>
                                @endif
                                @if($ex->seizure_location)
                                    <span class="inline-flex items-center gap-1 text-slate-500 shrink-0" title="Location: {{ $ex->seizure_location }}">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>{{ \Illuminate\Support\Str::limit($ex->seizure_location, 22) }}</span>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <!-- 6. ACTIONS DROPDOWN -->
                    <td class="py-3.5 px-4 text-right whitespace-nowrap align-top">
                        <div class="relative inline-block text-left coc-action-dropdown-wrapper">
                            <button type="button"
                                    onclick="toggleCocActionDropdown(event, {{ $ex->id }})"
                                    class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-300 hover:border-slate-400 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs text-[11px] font-mono-code focus:outline-none"
                                    title="Actions for Exhibit {{ $ex->exhibit_number ?: 'EXH-#' . $ex->id }}"
                                    aria-haspopup="true"
                                    aria-expanded="false">
                                <span>Actions</span>
                                <svg class="w-3 h-3 text-slate-400 transition-transform dropdown-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div id="cocActionMenu-{{ $ex->id }}"
                                 class="coc-action-menu hidden fixed z-[99999] w-48 bg-white rounded-2xl shadow-2xl border border-slate-200/95 p-1.5 space-y-1 text-left font-sans">
                                
                                @php
                                    $detailPayload = json_encode([
                                        'id' => $ex->id,
                                        'exhibit_number' => $ex->exhibit_number,
                                        'property_id' => $ex->property_id,
                                        'lab_exhibit_no' => $ex->lab_exhibit_no,
                                        'case_number' => $caseRef,
                                        'case_title' => $caseTitle,
                                        'suspect_name' => $suspectName,
                                        'commodity_name' => $ex->commodity_name,
                                        'exhibit_category' => $ex->exhibit_category,
                                        'chain_of_custody' => $coc,
                                        'custodial_status' => $status,
                                        'seizure_weight' => $ex->seizure_weight,
                                        'seizure_unit' => $ex->seizure_unit,
                                        'seizure_packaging' => $ex->seizure_packaging,
                                        'seizure_date' => $ex->seizure_date,
                                        'seizure_time' => $ex->seizure_time,
                                        'seizure_officer' => $ex->seizure_officer,
                                        'investigating_officer' => $ex->investigating_officer,
                                        'source_of_exhibits' => $ex->source_of_exhibits,
                                        'source_requesting_unit' => $ex->source_requesting_unit,
                                        'seizure_location' => $ex->seizure_location,
                                        'created_at' => $ex->created_at ? $ex->created_at->format('Y-m-d H:i:s') : 'N/A'
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                @endphp

                                <!-- Details Button -->
                                <button type="button"
                                        data-coc-details="{{ $detailPayload }}"
                                        onclick="openCocDetailModalFromBtn(this); closeAllCocActionDropdowns();"
                                        class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-slate-100 hover:text-slate-900 rounded-xl flex items-center space-x-2.5 transition-colors cursor-pointer group">
                                    <div class="w-6 h-6 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 group-hover:bg-slate-900 group-hover:text-white transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 text-xs">Custody Details</div>
                                        <div class="text-[10px] text-slate-400 font-normal">Full ledger inspection</div>
                                    </div>
                                </button>

                                <!-- Case Review Link -->
                                @if($reviewUrl !== '#')
                                    <a href="{{ $reviewUrl }}"
                                       class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-900 rounded-xl flex items-center space-x-2.5 transition-colors cursor-pointer group">
                                        <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-slate-900 text-xs">Review Case</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Open related docket</div>
                                        </div>
                                    </a>
                                @endif

                                <!-- Live QR Tag Link -->
                                @if($ex->qr_token)
                                    <a href="{{ url('/exhibits/tag/' . $ex->qr_token, [], false) }}"
                                       target="_blank"
                                       class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-purple-50 hover:text-purple-900 rounded-xl flex items-center space-x-2.5 transition-colors cursor-pointer group">
                                        <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-slate-900 text-xs">Live QR Tag</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Digital property tag</div>
                                        </div>
                                    </a>
                                @endif

                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-16 text-center text-slate-500 text-xs font-mono-code">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <div class="font-bold text-slate-700 text-sm">No Chain of Custody Records Found</div>
                            <div class="text-[11px] text-slate-400 max-w-sm">No forensic exhibit movements match your active search terms or custodial status filter.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($exhibits, 'hasPages') && $exhibits->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs coc-pagination">
        <div class="text-slate-500 font-mono-code text-[11px]">
            Showing <span class="font-bold text-slate-800">{{ $exhibits->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-800">{{ $exhibits->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800">{{ $exhibits->total() }}</span> exhibits under chain of custody
        </div>
        <div>
            {{ $exhibits->links() }}
        </div>
    </div>
@endif
