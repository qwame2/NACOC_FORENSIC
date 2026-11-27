{{--
    DRUG SCREENING PARTIAL TABLE & PAGINATION
    Rendered on full page load and returned during AJAX filter/search/pagination updates.
--}}
@php
    $isStaffUser = auth()->user() instanceof \App\Models\StaffAccount || request()->is('*/staff*') || (auth()->user() && !auth()->user()->is_head);
    $screeningCreateUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening/create') : url('/Lab_Analysis/drug-screening/create');
    $screeningPdfBase = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
    $totalFilteredNum = method_exists($screenings, 'total') ? $screenings->total() : $screenings->count();
    $hasActiveScreeningFilters = request()->filled('search')
        || request()->filled('type')
        || request()->filled('result')
        || (request()->filled('period') && strtolower((string) request('period')) !== 'general')
        || request()->filled('from')
        || request()->filled('to');
@endphp
<div id="screeningTableDataMeta"
     data-total="{{ $totalFilteredNum }}"
     data-stat-total="{{ $totalCount ?? $totalFilteredNum }}"
     data-stat-positive="{{ $positiveCount ?? 0 }}"
     data-stat-negative="{{ $negativeCount ?? 0 }}"
     data-stat-pending="{{ $pendingCount ?? 0 }}"
     data-stat-suspects="{{ $suspectsCount ?? 0 }}"
     data-stat-civilians="{{ $civiliansCount ?? 0 }}"
     class="hidden"></div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs text-slate-700" id="drugScreeningTable">
        <thead>
            <tr class="bg-slate-50/95 text-[10.5px] font-bold uppercase font-mono-code text-slate-500 border-b border-slate-200/90 tracking-wider select-none">
                <th class="py-3.5 px-5 whitespace-nowrap">Ref #</th>
                <th class="py-3.5 px-4">Subject Details</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Type</th>
                <th class="py-3.5 px-4">Case Reference</th>
                <th class="py-3.5 px-4">Purpose of Screening</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Screening Date</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Result / Findings</th>
                <th class="py-3.5 px-4">Notes / Remarks</th>
                <th class="py-3.5 px-5 text-right whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($screenings as $index => $screening)
                @php
                    $isCivilian = $screening->isCivilian();
                    $suspect = $screening->suspect;
                    $parentCase = $suspect ? ($suspect->case ?? ($suspect->singleCase ?? $suspect->multipleCase)) : null;
                    $personName = $screening->person_name;
                    $resultLower = strtolower(trim($screening->result));

                    $mugshotUrl = $suspect ? $suspect->mugshot_front_url : null;
                    if (empty($mugshotUrl) && !$isCivilian && !empty($personName)) {
                        $scMatch = \App\Models\SingleSuspectCase::where('suspect_name', $personName)
                            ->whereNotNull('mugshot_front')
                            ->where('mugshot_front', '!=', '')
                            ->latest('id')
                            ->first();
                        if ($scMatch && !empty($scMatch->mugshot_front)) {
                            $mugshotUrl = \App\Services\CasePhotoResolver::resolveMugshotUrl($scMatch->mugshot_front);
                        }
                    }
                    $initials = strtoupper(substr(trim($personName ?? 'SP'), 0, 2));

                    $rowModalPayload = [
                        'id'               => str_pad($screening->id, 3, '0', STR_PAD_LEFT),
                        'raw_id'           => $screening->id,
                        'pdf_url'          => $screeningPdfBase . '/' . $screening->id . '/pdf',
                        'is_civilian'      => $isCivilian,
                        'person_type'      => $isCivilian ? 'Civilian' : 'Suspect',
                        'suspect_name'     => $personName,
                        'suspect_alias'    => $isCivilian ? 'N/A' : ($suspect->suspect_alias ?? 'N/A'),
                        'ghana_card'       => $isCivilian ? 'N/A' : ($suspect->ghana_card ?? 'N/A'),
                        'gender'           => $isCivilian ? 'N/A' : ($suspect->gender ?? 'N/A'),
                        'holding_facility' => $isCivilian ? 'Walk-in / Civilian' : ($suspect->holding_facility ?? 'NACOC Custody'),
                        'case_number'      => $isCivilian ? 'N/A (Civilian Walk-in)' : ($parentCase->case_number ?? 'N/A'),
                        'case_title'       => $isCivilian ? 'Non-Docket Civilian Intake' : ($parentCase->title ?? 'N/A'),
                        'purpose'          => $screening->purpose,
                        'screening_date'   => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                        'result'           => $screening->result,
                        'substances_found' => $screening->substances_found ?? ($resultLower === 'negative' ? 'No drug(s) detected' : ($resultLower === 'positive' ? 'Substances detected' : 'Pending laboratory screening')),
                        'notes'            => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                        'note'             => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                        'on_medication'    => (bool) $screening->on_medication,
                        'medication_summary' => $screening->medication_summary,
                        'medications'      => $screening->medications->map(function ($m) {
                            return [
                                'medication_name'  => $m->medication_name,
                                'dosage'           => $m->dosage ?? '—',
                                'form'             => $m->form ?? '—',
                                'frequency'        => $m->frequency ?? '—',
                                'route'            => $m->route ?? '—',
                                'source_label'     => $m->source_label,
                                'date_started'     => $m->date_started ? $m->date_started->format('d M Y') : '—',
                                'duration_ongoing' => $m->duration_ongoing ?? '—',
                                'last_dose_taken'  => $m->last_dose_taken ? $m->last_dose_taken->format('d M Y, H:i') : '—',
                            ];
                        })->values()->toArray(),
                        'created_at'       => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
                        'mugshot'          => $mugshotUrl,
                        'initials'         => $initials,
                    ];
                @endphp
                <tr class="group hover:bg-slate-50/90 transition-colors screening-row">
                    {{-- COL 1: REF # --}}
                    <td class="py-4 px-5 align-middle font-mono-code font-bold text-slate-600 whitespace-nowrap">
                        #{{ str_pad($screening->id, 3, '0', STR_PAD_LEFT) }}
                    </td>

                    {{-- COL 2: SUBJECT DETAILS --}}
                    <td class="py-3.5 px-4 align-middle min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-200/90 shadow-2xs shrink-0 bg-slate-100 flex items-center justify-center">
                                @if($mugshotUrl)
                                    <img src="{{ $mugshotUrl }}"
                                         alt="{{ $personName }}"
                                         width="40"
                                         height="40"
                                         class="w-full h-full object-cover"
                                         onerror="this.style.display='none'; if(this.nextElementSibling){ this.nextElementSibling.classList.remove('hidden'); }">
                                    <div class="hidden w-full h-full bg-slate-200 text-slate-800 font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                        {{ $initials }}
                                    </div>
                                @else
                                    <div class="w-full h-full {{ $isCivilian ? 'bg-teal-50 text-teal-800' : 'bg-slate-200 text-slate-800' }} font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button type="button"
                                            onclick="openScreeningModal({{ json_encode($rowModalPayload) }})"
                                            class="font-extrabold text-slate-900 hover:text-emerald-700 leading-tight text-left transition-colors">
                                        {{ $personName }}
                                    </button>
                                    @if($screening->on_medication)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9.5px] font-bold font-mono-code bg-teal-50 text-teal-800 border border-teal-200"
                                              title="{{ $screening->medication_summary ?: 'On Medication' }}">
                                            <svg class="w-2.5 h-2.5 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                            <span>On Meds</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 font-medium mt-0.5 truncate max-w-[210px]">
                                    @if($isCivilian)
                                        <span class="text-teal-700 font-semibold">Civilian Walk-in Screening</span>
                                    @else
                                        @if($suspect && $suspect->suspect_alias && $suspect->suspect_alias !== 'N/A')
                                            <span class="text-emerald-700 font-semibold">"{{ $suspect->suspect_alias }}"</span> &bull;
                                        @endif
                                        <span class="font-mono-code">{{ $suspect->ghana_card ?? 'ID: N/A' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- COL 3: SUBJECT TYPE --}}
                    <td class="py-4 px-4 align-middle text-center whitespace-nowrap">
                        @if($isCivilian)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold font-mono-code bg-teal-50 text-teal-800 border border-teal-200">
                                CIVILIAN
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold font-mono-code bg-sky-50 text-sky-800 border border-sky-200">
                                SUSPECT
                            </span>
                        @endif
                    </td>

                    {{-- COL 4: CASE REFERENCE --}}
                    <td class="py-4 px-4 align-middle font-mono-code whitespace-nowrap">
                        @if($isCivilian)
                            <div class="font-bold text-slate-600 text-xs">
                                N/A (Civilian)
                            </div>
                            <div class="text-[10.5px] text-slate-400 mt-0.5">
                                Non-Docket Intake
                            </div>
                        @else
                            @php
                                $caseRefVal = $parentCase->case_number ?? ('NCC-' . date('Y') . '-N/A');
                            @endphp
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-emerald-800 text-xs">{{ $caseRefVal }}</span>
                                @if(!empty($parentCase?->case_number))
                                    <button type="button"
                                            onclick="copyScreeningRef('{{ addslashes($parentCase->case_number) }}')"
                                            class="opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-all"
                                            title="Copy case reference {{ $parentCase->case_number }}"
                                            aria-label="Copy case reference {{ $parentCase->case_number }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            <div class="text-[10.5px] text-slate-500 font-sans truncate max-w-[165px] mt-0.5" title="{{ $parentCase->title ?? '' }}">
                                {{ $parentCase->title ?? 'General Narcotics Case' }}
                            </div>
                        @endif
                    </td>

                    {{-- COL 5: PURPOSE OF SCREENING --}}
                    <td class="py-4 px-4 align-middle">
                        <div class="font-semibold text-slate-900 max-w-[230px] leading-snug">
                            {{ $screening->purpose }}
                        </div>
                    </td>

                    {{-- COL 6: SCREENING DATE --}}
                    <td class="py-4 px-4 align-middle font-mono-code font-bold text-slate-700 whitespace-nowrap">
                        {{ $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A' }}
                    </td>

                    {{-- COL 7: RESULT / FINDINGS --}}
                    <td class="py-4 px-4 align-middle text-center whitespace-nowrap">
                        @if($resultLower === 'positive')
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-extrabold font-mono-code text-[10px] tracking-wider">
                                    POSITIVE
                                </span>
                                <div class="text-[10px] text-rose-700 font-bold max-w-[155px] truncate mt-1" title="{{ $screening->substances_found ?? 'Substances detected' }}">
                                    {{ $screening->substances_found ?? 'Substances detected' }}
                                </div>
                            </div>
                        @elseif($resultLower === 'negative')
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-extrabold font-mono-code text-[10px] tracking-wider">
                                    NEGATIVE
                                </span>
                                <div class="text-[10px] text-emerald-700 font-semibold font-mono-code mt-1">
                                    No drug(s) detected
                                </div>
                            </div>
                        @else
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-extrabold font-mono-code text-[10px] tracking-wider">
                                    PENDING
                                </span>
                                <div class="text-[10px] text-amber-700 font-medium mt-1">
                                    Awaiting lab test
                                </div>
                            </div>
                        @endif
                    </td>

                    {{-- COL 8: NOTES / REMARKS --}}
                    <td class="py-4 px-4 align-middle max-w-[220px]">
                        @php
                            $dispNote = $screening->notes ?? $screening->note ?? null;
                        @endphp
                        @if($dispNote)
                            <div class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed" title="{{ $dispNote }}">
                                {{ $dispNote }}
                            </div>
                        @else
                            <span class="text-slate-400 italic text-[11px]">No remarks logged</span>
                        @endif
                    </td>

                    {{-- COL 9: ACTIONS --}}
                    <td class="py-4 px-5 align-middle text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button type="button"
                                    onclick="openScreeningModal({{ json_encode($rowModalPayload) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/90 text-[11px] font-bold transition-colors"
                                    title="View Full Screening Record">
                                <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>Dossier</span>
                            </button>

                            <a href="{{ $screeningPdfBase . '/' . $screening->id . '/pdf' }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-200 text-[11px] font-bold transition-colors"
                               title="{{ $isCivilian ? 'Print / Save Civilian PDF Report' : 'Print / Save Suspect PDF Report' }}">
                                <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>PDF</span>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-16 px-6 text-center bg-white">
                        <div class="max-w-md mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center border border-slate-200 mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div class="text-slate-900 font-extrabold text-sm">No Drug Screening Records Found</div>
                            @if($hasActiveScreeningFilters)
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    No suspect or civilian screening profiles match your active search query, profile type, outcome, or date filter.
                                </p>
                                <div class="pt-1 flex items-center justify-center gap-2">
                                    <button type="button"
                                            onclick="resetReportFilterConsole()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 font-bold text-xs transition-colors">
                                        <span>Clear active filters</span>
                                    </button>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    No suspects or civilians have had a drug screening recorded yet.
                                </p>
                                <div class="pt-1">
                                    <a href="{{ $screeningCreateUrl }}"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>Record First Drug Screening</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($screenings, 'links') && $screenings->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/70">
        {{ $screenings->links() }}
    </div>
@endif
