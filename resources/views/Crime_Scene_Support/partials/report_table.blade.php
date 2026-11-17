<div id="fieldCasesTableMeta" 
     data-total="{{ $cases->total() }}" 
     data-first-item="{{ $cases->firstItem() ?? 0 }}" 
     data-last-item="{{ $cases->lastItem() ?? 0 }}" 
     data-filtered-count="{{ $totalFilteredCount ?? $cases->total() }}" 
     data-cumulative-count="{{ $totalCasesCount ?? 0 }}"
     class="hidden"></div>

@if($cases->isEmpty())
    <!-- EMPTY STATE -->
    <div class="p-12 text-center">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-200/70 text-blue-600 flex items-center justify-center mx-auto mb-4 shadow-xs">
            <i data-lucide="folder-search" class="w-7 h-7"></i>
        </div>
        <h4 class="text-sm font-black text-slate-800 font-mono-code uppercase tracking-wider mb-1">
            No Crime Scene Support Cases Found
        </h4>
        <p class="text-xs text-slate-500 max-w-md mx-auto mb-5">
            No case dockets match your search parameters. Try clearing your filters or creating a new Crime Scene Support case docket.
        </p>
        <div class="flex items-center justify-center gap-3">
            <button type="button" onclick="resetReportFilters(event)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold font-mono-code transition-colors flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Reset Filters</span>
            </button>
            <a href="{{ url('/Crime_Scene_Support/create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Create New Case</span>
            </a>
        </div>
    </div>
@else
    <!-- CASES TABLE -->
    <div class="overflow-x-auto">
        <table class="w-full table-fixed min-w-[960px] text-left text-xs text-slate-700" id="fieldCasesTable">
            <thead class="bg-slate-50/80 border-b border-slate-200/90 text-[10px] font-black text-slate-500 font-mono-code uppercase tracking-wider">
                <tr>
                    <th scope="col" class="w-1/6 py-3.5 px-4">Case Reference &amp; Date</th>
                    <th scope="col" class="w-1/6 py-3.5 px-4">Case Title &amp; Scene Location</th>
                    <th scope="col" class="w-1/6 py-3.5 px-4">Operation Type</th>
                    <th scope="col" class="w-1/6 py-3.5 px-4">Suspects Apprehended</th>
                    <th scope="col" class="w-1/6 py-3.5 px-4">Seized Exhibits</th>
                    <th scope="col" class="w-1/6 py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($cases as $case)
                    @php
                        $isMulti = !empty($case->is_multiple) || (!empty($case->total_suspects_count) && (int)$case->total_suspects_count > 1) || (count($case->docket_data['suspects'] ?? []) > 1);
                        
                        // Suspects details
                        $suspectsList = collect($case->docket_data['suspects'] ?? []);
                        $suspectCount = $suspectsList->isNotEmpty() ? $suspectsList->count() : (!empty($case->suspect_name) ? 1 : 0);
                        $primarySuspectName = $case->suspect_name ?: ($suspectsList->first()['suspect_name'] ?? '—');
                        $coSuspectsCount = max(0, $suspectCount - 1);

                        // Exhibits details
                        $exhibitsList = $case->relationLoaded('exhibits') && $case->exhibits->isNotEmpty() ? $case->exhibits : collect($case->docket_data['seizures'] ?? []);
                        $exhibitCount = $exhibitsList->count();
                        $exhibitPreviews = $exhibitsList->map(function ($e) {
                            if (is_object($e)) {
                                return $e->commodity_name ?: ($e->exhibit_category ?: '');
                            }
                            return $e['seizure_name'] ?? ($e['category'] ?? '');
                        })->filter()->take(2)->implode(', ');

                        // Operation type badge
                        $opName = $case->operation_code_name ?: ($case->operation_code ?: ($case->docket_data['operation_code_name'] ?? ($case->docket_data['operation_code'] ?? null)));
                        if (empty($opName)) {
                            $tLower = strtolower($case->title ?? '');
                            $opName = match(true) {
                                str_contains($tLower, 'raid') => 'Raid Operation',
                                str_contains($tLower, 'warrant') => 'Search Warrant',
                                str_contains($tLower, 'cargo') || str_contains($tLower, 'parcel') => 'Cargo Interception',
                                str_contains($tLower, 'intel') => 'Intel-Led Op',
                                default => 'CSS Operation',
                            };
                        }

                        // Location
                        $sceneLoc = $case->raid_location ?: ($case->incident_scene ?: ($case->seizure_location ?: '—'));

                        // Case Date
                        $rawDate = $case->incident_date ?: ($case->arrest_date ?: $case->created_at);
                        $formattedDate = $rawDate ? date('d M Y, H:i', strtotime($rawDate)) : '—';
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        
                        <!-- Case Reference & Date -->
                        <td class="w-1/6 py-3.5 px-4 align-top">
                            <div class="font-mono-code font-bold text-slate-900 flex items-center gap-1.5">
                                <span class="text-blue-600 font-extrabold truncate">{{ $case->case_number ?: ('ID #' . $case->id) }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold font-mono-code {{ $isMulti ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $isMulti ? 'MULTI-SUSPECT' : 'SINGLE' }}
                                </span>
                            </div>
                            <div class="mt-1 text-[10px] text-slate-400 font-mono-code truncate">
                                {{ $formattedDate }}
                            </div>
                        </td>

                        <!-- Case Title & Incident Scene -->
                        <td class="w-1/6 py-3.5 px-4 align-top">
                            <div class="font-bold text-slate-900 leading-snug line-clamp-2" title="{{ $case->title }}">
                                {{ $case->title ?: ($case->case_number ?: 'Case Docket') }}
                            </div>
                            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-600">
                                <i data-lucide="map-pin" class="w-3 h-3 text-red-500 shrink-0"></i>
                                <span class="truncate" title="{{ $sceneLoc }}">{{ $sceneLoc }}</span>
                            </div>
                        </td>

                        <!-- Operation Type -->
                        <td class="w-1/6 py-3.5 px-4 align-top">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold font-mono-code bg-slate-100 text-slate-800 border border-slate-200 max-w-full">
                                <i data-lucide="target" class="w-3 h-3 text-blue-600 shrink-0"></i>
                                <span class="truncate" title="{{ $opName }}">{{ $opName }}</span>
                            </span>
                        </td>

                        <!-- Suspects Apprehended -->
                        <td class="w-1/6 py-3.5 px-4 align-top">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="font-bold text-slate-900 truncate" title="{{ $primarySuspectName }}">
                                    {{ $primarySuspectName }}
                                </span>
                                @if($coSuspectsCount > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold font-mono-code bg-blue-100 text-blue-800 shrink-0" title="{{ $coSuspectsCount }} co-suspect(s)">
                                        +{{ $coSuspectsCount }}
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-500 font-mono-code truncate">
                                <span>{{ $suspectCount }} Suspect{{ $suspectCount == 1 ? '' : 's' }}</span>
                                @if(!empty($case->nationality))
                                    <span class="truncate">@if(request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*'))|@else&bull;@endif {{ $case->nationality }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Seized Exhibits -->
                        <td class="w-1/6 py-3.5 px-4 align-top">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-extrabold font-mono-code bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                    {{ $exhibitCount }} Item{{ $exhibitCount == 1 ? '' : 's' }}
                                </span>
                                <span class="text-[11px] text-slate-700 truncate" title="{{ $exhibitPreviews ?: 'No items recorded' }}">
                                    {{ $exhibitPreviews ?: 'None logged' }}
                                </span>
                            </div>
                            @if(!empty($case->exhibits_summary))
                                <div class="mt-1 text-[10px] text-slate-400 truncate" title="{{ $case->exhibits_summary }}">
                                    {{ $case->exhibits_summary }}
                                </div>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="w-1/6 py-3.5 px-4 align-top text-right">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('crime_scene.cases.review', $case->case_number ?: $case->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors text-[11px] font-bold font-mono-code shadow-2xs" title="View Case Dossier &amp; Review File">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Review</span>
                                </a>
                            </div>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- PAGINATION FOOTER -->
    <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs" id="paginationFooter">
        <div class="text-slate-500 font-mono-code text-[11px]">
            Showing <span class="font-bold text-slate-700">{{ $cases->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $cases->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ number_format($cases->total()) }}</span> filtered dockets
        </div>

        <div class="flex items-center gap-1">
            {{ $cases->links() }}
        </div>
    </div>
@endif

