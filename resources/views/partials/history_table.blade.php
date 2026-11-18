@php
    $cases = $cases ?? collect();
@endphp
<!-- DOCKET HISTORY DATA TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700" id="historyTable">
                            <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4 font-semibold">Incident Date &amp; Ref</th>
                                    <th class="py-3.5 px-4 font-semibold">Case Title &amp; Primary Suspect</th>
                                    <th class="py-3.5 px-4 font-semibold">Originating Unit</th>
                                    <th class="py-3.5 px-4 font-semibold">Seizure / Exhibit Summary</th>
                                    <th class="py-3.5 px-4 font-semibold">Handling Officer</th>
                                    <th class="py-3.5 px-4 font-semibold">Current Docket Status</th>
                                    <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 font-medium" id="historyTableBody">
                                @forelse($cases as $cIdx => $c)
                                    @php
                                        $uName = $c->unit_name ?: 'Crime Scene Support';
                                        $dData = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
                                        $sSuspects = !empty($dData['suspects']) ? count($dData['suspects']) : 1;
                                        $sSeizures = !empty($dData['seizures']) ? count($dData['seizures']) : (!empty($c->seizure_name_drug) || !empty($c->seizure_name) ? 1 : 0);
                                        $exCat = $c->exhibit_category ?: ($dData['seizures'][0]['category'] ?? 'General');
                                        
                                        $statusClass = match(strtoupper($c->status ?? '')) {
                                            'APPROVED', 'AUTHORIZED' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                            'AWAITING SIGN-OFF', 'PENDING APPROVAL', 'PENDING' => 'bg-amber-50 text-amber-800 border-amber-300',
                                            'DECLINED' => 'bg-red-50 text-red-800 border-red-300',
                                            default => 'bg-blue-50 text-blue-800 border-blue-300',
                                        };
                                    @endphp
                                    <tr class="history-row hover:bg-slate-50/80 transition-colors" 
                                        data-unit="{{ $uName }}" 
                                        data-status="{{ strtoupper($c->status ?? '') }}"
                                        data-search="{{ strtolower(($c->case_number ?? '') . ' ' . ($c->title ?? '') . ' ' . ($c->suspect_name ?? '') . ' ' . ($c->submitting_officer ?? '') . ' ' . ($c->exhibits_summary ?? '')) }}"
                                    >
                                        <!-- DATE & REF -->
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900 font-mono-code text-xs">{{ $c->case_number }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono-code">{{ $c->created_at ? $c->created_at->format('Y-m-d H:i') : 'N/A' }}</div>
                                        </td>

                                        <!-- TITLE & SUSPECT -->
                                        <td class="py-3.5 px-4 max-w-xs">
                                            <div class="font-extrabold text-slate-900 text-xs truncate" title="{{ $c->title }}">{{ $c->title }}</div>
                                            @php
                                                $allSuspectNamesHist = !empty($dData['suspects']) && is_array($dData['suspects'])
                                                    ? implode(', ', array_filter(array_column($dData['suspects'], 'suspect_name')))
                                                    : ($c->suspect_name ?: 'N/A');
                                            @endphp
                                            <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                                <span class="font-bold text-slate-700">Suspect:</span>
                                                <span class="truncate max-w-[220px]" title="{{ $allSuspectNamesHist }}">{{ $allSuspectNamesHist }}</span>
                                            </div>
                                        </td>

                                        <!-- UNIT -->
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold uppercase tracking-wider {{ str_contains(strtolower($uName), 'lab') ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : (str_contains(strtolower($uName), 'evidence') ? 'bg-indigo-50 text-indigo-800 border border-indigo-200' : (str_contains(strtolower($uName), 'digital') ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-800 border border-slate-200')) }}">
                                                {{ $uName }}
                                            </span>
                                        </td>

                                        <!-- EXHIBIT SUMMARY -->
                                        <td class="py-3.5 px-4 max-w-xs">
                                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code uppercase font-extrabold bg-amber-100 text-amber-900 border border-amber-200">{{ $exCat }}</span>
                                                <span class="truncate">{{ $c->seizure_name_drug ?: ($c->seizure_name ?: ($c->exhibits_summary ?: 'Seized Commodity')) }}</span>
                                            </div>
                                            <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">{{ $sSeizures }} Seizure Item(s) Logged</div>
                                            @php
                                                $cocTrail = $c->chain_of_custody ?? ($dData['seizures'][0]['chain_of_custody'] ?? null);
                                                if (empty($cocTrail) && method_exists($c, 'exhibits') && $c->exhibits) {
                                                    $cocTrail = $c->exhibits->firstWhere('chain_of_custody', '!=', null)?->chain_of_custody;
                                                }
                                            @endphp
                                            @if(!empty($cocTrail))
                                                <div class="text-[10px] text-teal-800 bg-teal-50 border border-teal-200 rounded px-1.5 py-0.5 mt-1 flex items-center gap-1 font-mono-code truncate" title="Chain of Custody: {{ $cocTrail }}">
                                                    <svg class="w-3 h-3 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                    <span class="truncate font-semibold">{{ $cocTrail }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- OFFICER -->
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900 text-xs">{{ $c->submitting_officer ?: 'Handling Officer' }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono-code">Registered Officer</div>
                                        </td>

                                        <!-- STATUS -->
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold uppercase tracking-wider border {{ $statusClass }}">
                                                {{ $c->status ?? 'Active Processing' }}
                                            </span>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td class="py-3.5 px-4 text-right space-x-1.5 shrink-0">
                                            <a href="{{ url('/cases/' . ($c->id ?? $c->case_number) . '/review') . '?from=' . urlencode($activeUnitName) }}" class="inline-flex items-center px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-[11px] font-bold shadow-2xs transition-colors cursor-pointer">
                                                <span>View Review</span>
                                            </a>
                                            <a href="{{ url('/cases/' . ($c->id ?? $c->case_number) . '/pdf') }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 rounded-lg text-[11px] font-bold shadow-2xs transition-colors cursor-pointer" title="Download PDF Docket Report">
                                                <span>PDF</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-500 text-xs font-mono-code">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <p class="font-bold text-slate-700">No case history log entries found</p>
                                                <p class="text-[11px] text-slate-400">No records matching the selected unit or filter criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($cases, 'links') && $cases->hasPages())
                        <div class="border-t border-slate-200">
                            {{ $cases->links() }}
                        </div>
                    @endif