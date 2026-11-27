<div id="fieldCasesTableMeta"
     data-total="{{ $cases->total() }}"
     data-first-item="{{ $cases->firstItem() ?? 0 }}"
     data-last-item="{{ $cases->lastItem() ?? 0 }}"
     data-filtered-count="{{ $totalFilteredCount ?? $cases->total() }}"
     data-cumulative-count="{{ $totalCasesCount ?? 0 }}"
     class="hidden"></div>

@php
    $resolvedCreateUrl = $createCaseUrl ?? (request()->is('*/staff*') ? url('/Crime_Scene_Support/staff/create') : url('/Crime_Scene_Support/create'));
@endphp

@if($cases->isEmpty())
    <div class="p-14 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 border border-slate-200/80 text-slate-500 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="folder-search" class="w-6 h-6"></i>
        </div>
        <h4 class="text-sm font-extrabold text-slate-900 font-mono-code uppercase tracking-wider mb-1">
            No Matching Case Dockets Found
        </h4>
        <p class="text-xs text-slate-500 max-w-md mx-auto mb-5 leading-relaxed">
            No field case dockets matched your active audit parameters. Clear your filter console or register a new field case docket.
        </p>
        <div class="flex items-center justify-center gap-2.5">
            <button type="button" onclick="resetReportFilters(event)" class="rep-btn-green-soft px-4 py-2 rounded-xl text-xs font-bold font-mono-code inline-flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Reset Filters</span>
            </button>
            <a href="{{ $resolvedCreateUrl }}" class="rep-btn-green px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Create New Case</span>
            </a>
        </div>
    </div>
@else
    <div class="overflow-x-auto custom-scrollbar">
        <table class="rep-table w-full table-fixed min-w-[1000px] text-left text-xs text-slate-700 border-collapse" id="fieldCasesTable">
            <thead>
                <tr>
                    <th scope="col" class="w-[18%] py-3.5 px-5">Case Reference &amp; Priority</th>
                    <th scope="col" class="w-[22%] py-3.5 px-4">Docket Title &amp; Scene</th>
                    <th scope="col" class="w-[16%] py-3.5 px-4">Operation &amp; Status</th>
                    <th scope="col" class="w-[18%] py-3.5 px-4">Suspects Apprehended</th>
                    <th scope="col" class="w-[16%] py-3.5 px-4">Seized Exhibits</th>
                    <th scope="col" class="w-[10%] py-3.5 px-5 text-right no-print">Dossier</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach($cases as $case)
                    @php
                        $isMulti = !empty($case->is_multiple) || (!empty($case->total_suspects_count) && (int) $case->total_suspects_count > 1) || (count($case->docket_data['suspects'] ?? []) > 1);
                        $normPriority = \App\Models\ForensicCase::normalizePriority($case->priority ?? 'HIGH');
                        $isHighPriority = ($normPriority === 'HIGH');

                        $suspectsList = collect($case->docket_data['suspects'] ?? []);
                        $suspectCount = $suspectsList->isNotEmpty() ? $suspectsList->count() : (!empty($case->suspect_name) ? 1 : 0);
                        $primarySuspectName = $case->suspect_name ?: ($suspectsList->first()['suspect_name'] ?? 'Unassigned / No Suspect');
                        $coSuspectsCount = max(0, $suspectCount - 1);
                        $sParts = preg_split('/\s+/', trim((string) $primarySuspectName)) ?: [];
                        $sInit = count($sParts) >= 2
                            ? strtoupper(substr($sParts[0], 0, 1) . substr(end($sParts), 0, 1))
                            : strtoupper(substr((string) $primarySuspectName, 0, 2));

                        $exhibitsList = $case->relationLoaded('exhibits') && $case->exhibits->isNotEmpty() ? $case->exhibits : collect($case->docket_data['seizures'] ?? []);
                        $exhibitCount = $exhibitsList->count();
                        $exhibitPreviews = $exhibitsList->map(function ($e) {
                            if (is_object($e)) {
                                return $e->commodity_name ?: ($e->exhibit_category ?: '');
                            }
                            return $e['seizure_name'] ?? ($e['category'] ?? '');
                        })->filter()->take(2)->implode(', ');

                        $opName = $case->operation_code_name ?: ($case->operation_code ?: ($case->docket_data['operation_code_name'] ?? ($case->docket_data['operation_code'] ?? null)));
                        if (empty($opName)) {
                            $tLower = strtolower($case->title ?? '');
                            $opName = match (true) {
                                str_contains($tLower, 'raid') => 'Raid Operation',
                                str_contains($tLower, 'warrant') => 'Search Warrant',
                                str_contains($tLower, 'cargo') || str_contains($tLower, 'parcel') => 'Cargo Interception',
                                str_contains($tLower, 'intel') => 'Intel-Led Op',
                                default => 'CSS Operation',
                            };
                        }

                        $caseStatusLabel = trim((string) ($case->status ?? $case->case_status ?? 'Active Investigation'));
                        $statusLower = strtolower($caseStatusLabel);
                        $statusChipClass = match (true) {
                            str_contains($statusLower, 'approv') || str_contains($statusLower, 'court') || str_contains($statusLower, 'complet') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            str_contains($statusLower, 'draft') => 'bg-slate-100 text-slate-600 border-slate-200',
                            default => 'bg-amber-50 text-amber-800 border-amber-200',
                        };

                        $sceneLoc = $case->raid_location ?: ($case->incident_scene ?: ($case->seizure_location ?: '—'));
                        $rawDate = $case->incident_date ?: ($case->arrest_date ?: $case->created_at);
                        $formattedDate = $rawDate ? date('d M Y, H:i', strtotime($rawDate)) : '—';
                    @endphp
                    <tr>
                        <!-- Case Reference, Priority & Date -->
                        <td class="py-3.5 px-5 align-top">
                            <div class="font-mono-code font-extrabold text-slate-900 text-xs truncate">
                                {{ $case->case_number ?: ('ID #' . $case->id) }}
                            </div>
                            <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold font-mono-code border {{ $isHighPriority ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-teal-50 text-teal-800 border-teal-200' }}">
                                    {{ $normPriority }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code border {{ $isMulti ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $isMulti ? 'MULTI' : 'SINGLE' }}
                                </span>
                            </div>
                            <div class="mt-1 text-[10px] text-slate-400 font-mono-code tnum truncate">
                                {{ $formattedDate }}
                            </div>
                        </td>

                        <!-- Case Title & Scene -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="font-bold text-slate-900 leading-snug line-clamp-2" title="{{ $case->title }}">
                                {{ $case->title ?: ($case->case_number ?: 'Case Docket') }}
                            </div>
                            <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                                <i data-lucide="map-pin" class="w-3 h-3 text-emerald-600 shrink-0"></i>
                                <span class="truncate" title="{{ $sceneLoc }}">{{ $sceneLoc }}</span>
                            </div>
                        </td>

                        <!-- Operation Type & Status -->
                        <td class="py-3.5 px-4 align-top">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-slate-100 text-slate-800 border border-slate-200 max-w-full">
                                <i data-lucide="crosshair" class="w-3 h-3 text-slate-600 shrink-0"></i>
                                <span class="truncate" title="{{ $opName }}">{{ $opName }}</span>
                            </span>
                            <div class="mt-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9.5px] font-bold font-mono-code border {{ $statusChipClass }} truncate max-w-full" title="{{ $caseStatusLabel }}">
                                    {{ $caseStatusLabel }}
                                </span>
                            </div>
                        </td>

                        <!-- Suspects Apprehended -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200/90 text-slate-700 font-mono-code font-extrabold text-[10px] flex items-center justify-center shrink-0">
                                    {{ $sInit ?: 'NA' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="font-bold text-slate-900 truncate" title="{{ $primarySuspectName }}">
                                            {{ $primarySuspectName }}
                                        </span>
                                        @if($coSuspectsCount > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0" title="{{ $coSuspectsCount }} co-suspect(s)">
                                                +{{ $coSuspectsCount }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono-code truncate mt-0.5">
                                        {{ $suspectCount }} Subject{{ $suspectCount == 1 ? '' : 's' }}@if(!empty($case->nationality)) &bull; {{ $case->nationality }}@endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Seized Exhibits -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-extrabold font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200/80 shrink-0 tnum">
                                    {{ $exhibitCount }} {{ $exhibitCount == 1 ? 'Item' : 'Items' }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-700 truncate" title="{{ $exhibitPreviews ?: 'No items recorded' }}">
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
                        <td class="py-3.5 px-5 align-top text-right no-print">
                            <a href="{{ route('crime_scene.cases.review', $case->case_number ?: $case->id) }}"
                               class="rep-btn-green inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold font-mono-code"
                               title="Open Official Case Review Dossier">
                                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                <span>Review</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="rep-pagination px-6 py-4 border-t border-slate-200/80 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs" id="paginationFooter">
        <div class="text-slate-500 font-mono-code text-[11px] tnum">
            Showing <span class="font-extrabold text-slate-900">{{ $cases->firstItem() ?? 0 }}</span>–<span class="font-extrabold text-slate-900">{{ $cases->lastItem() ?? 0 }}</span> of <span class="font-extrabold text-slate-900">{{ number_format($cases->total()) }}</span> filtered dockets
        </div>

        <div class="flex items-center gap-1">
            {{ $cases->links() }}
        </div>
    </div>
@endif
