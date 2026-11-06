{{-- 
    DRUG SCREENING PARTIAL TABLE & PAGINATION
    Rendered on full page load and returned during AJAX filter/search/pagination updates.
--}}
<div id="screeningTableDataMeta" data-total="{{ method_exists($screenings, 'total') ? $screenings->total() : $screenings->count() }}" class="hidden"></div>
<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse" id="drugScreeningTable">
        <thead>
            <tr class="bg-slate-100/70 text-[11px] font-extrabold uppercase font-mono-code text-slate-600 border-b border-slate-200">
                <th class="py-3 px-4">#</th>
                <th class="py-3 px-4">Person Details</th>
                <th class="py-3 px-4 text-center">Type</th>
                <th class="py-3 px-4">Case Reference</th>
                <th class="py-3 px-4">Purpose of Screening</th>
                <th class="py-3 px-4">Screening Date</th>
                <th class="py-3 px-4 text-center">Result / Status</th>
                <th class="py-3 px-4">Notes / Remarks</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            @php
                $normaliseMugshotUrl = function ($mug) {
                    if (empty($mug) || !is_string($mug)) {
                         return null;
                    }
                    $mug = trim($mug);
                    if ($mug === '' || str_starts_with($mug, 'blob:')) {
                        return null;
                    }
                    if (
                        str_starts_with($mug, 'data:image') ||
                        str_starts_with($mug, 'http://') ||
                        str_starts_with($mug, 'https://')
                    ) {
                        return $mug;
                    }
                    return asset(ltrim($mug, '/'));
                };
            @endphp
            @forelse($screenings as $index => $screening)
            @php
                $isCivilian = $screening->isCivilian();
                $suspect = $screening->suspect;
                $parentCase = $suspect ? ($suspect->case ?? ($suspect->singleCase ?? $suspect->multipleCase)) : null;
                $personName = $screening->person_name;
                $resultLower = strtolower(trim($screening->result));

                $rawMug = $suspect ? $suspect->mugshot_front : null;
                if (empty($rawMug) && $suspect && !empty($suspect->suspect_name)) {
                    $scMatch = \App\Models\SingleSuspectCase::where('suspect_name', $suspect->suspect_name)
                        ->whereNotNull('mugshot_front')
                        ->where('mugshot_front', '!=', '')
                        ->first();
                    if ($scMatch && !empty($scMatch->mugshot_front)) {
                        $rawMug = $scMatch->mugshot_front;
                    }
                }
                $mugshotUrl = $normaliseMugshotUrl($rawMug);
                $initials = strtoupper(substr(trim($personName ?? 'SP'), 0, 2));
            @endphp
            <tr class="hover:bg-slate-50/80 transition-colors screening-row">
                <td class="py-3.5 px-4 font-mono-code font-bold text-slate-500">
                    {{ str_pad($screening->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="py-3.5 px-4">
                    <div class="flex items-center space-x-3">
                        <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shadow-2xs shrink-0 {{ $isCivilian ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-700' }} flex items-center justify-center">
                            @if($mugshotUrl)
                            <img src="{{ $mugshotUrl }}" alt="{{ $personName }}"
                                class="w-full h-full object-cover"
                                onerror="this.style.display='none'; if(this.nextElementSibling){ this.nextElementSibling.classList.remove('hidden'); }">
                            <div class="hidden w-full h-full bg-teal-100 text-teal-700 font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                {{ $initials }}
                            </div>
                            @else
                            <div class="w-full h-full {{ $isCivilian ? 'bg-teal-100 text-teal-800' : 'bg-blue-100 text-blue-800' }} font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                {{ $initials }}
                            </div>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-slate-900 leading-tight">
                                    {{ $personName }}
                                </span>
                                @if($screening->on_medication)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono-code bg-purple-100 text-purple-800 border border-purple-200 shadow-2xs"
                                    title="{{ $screening->medication_summary ?: 'On Medication' }}">
                                    <svg class="w-2.5 h-2.5 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                    <span>On Meds</span>
                                </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                @if($isCivilian)
                                <span class="text-teal-700 font-semibold italic">Civilian Walk-in Screening</span>
                                @else
                                    @if($suspect && $suspect->suspect_alias && $suspect->suspect_alias !== 'N/A')
                                    <span class="text-teal-700 font-semibold italic">"{{ $suspect->suspect_alias }}"</span> &bull;
                                    @endif
                                    <span class="font-mono-code">{{ $suspect->ghana_card ?? 'ID: N/A' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    @if($isCivilian)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-extrabold font-mono-code bg-teal-50 text-teal-800 border border-teal-200">
                        CIVILIAN
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-extrabold font-mono-code bg-blue-50 text-blue-800 border border-blue-200">
                        SUSPECT
                    </span>
                    @endif
                </td>
                <td class="py-3.5 px-4 font-mono-code">
                    @if($isCivilian)
                    <div class="font-bold text-slate-600">
                        N/A (Civilian)
                    </div>
                    <div class="text-[10px] text-slate-400">
                        Non-Docket Intake
                    </div>
                    @else
                    <div class="font-bold text-slate-900">
                        {{ $parentCase->case_number ?? ('NCC-' . date('Y') . '-N/A') }}
                    </div>
                    <div class="text-[10px] text-slate-500 truncate max-w-[140px]" title="{{ $parentCase->title ?? '' }}">
                        {{ $parentCase->title ?? 'General Narcotics Case' }}
                    </div>
                    @endif
                </td>
                <td class="py-3.5 px-4">
                    <div class="font-semibold text-slate-900 max-w-xs">
                        {{ $screening->purpose }}
                    </div>
                </td>
                <td class="py-3.5 px-4 font-mono-code font-bold text-slate-700 whitespace-nowrap">
                    {{ $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A' }}
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    @if($resultLower === 'positive')
                    <div>
                        <span class="font-extrabold font-mono-code text-xs text-red-600 tracking-wider">
                            POSITIVE
                        </span>
                        <div class="text-[10px] text-red-600 font-bold max-w-[140px] truncate mx-auto mt-0.5" title="{{ $screening->substances_found ?? 'Substances detected' }}">
                            {{ $screening->substances_found ?? 'Substances detected' }}
                        </div>
                    </div>
                    @elseif($resultLower === 'negative')
                    <div>
                        <span class="font-extrabold font-mono-code text-xs text-emerald-600 tracking-wider">
                            NEGATIVE
                        </span>
                        <div class="text-[10px] text-emerald-600 font-bold font-mono-code mt-0.5">
                            No drug(s) detected
                        </div>
                    </div>
                    @else
                    <div>
                        <span class="font-extrabold font-mono-code text-xs text-amber-600 tracking-wider">
                            PENDING
                        </span>
                        <div class="text-[10px] text-amber-600 font-medium mt-0.5">
                            Awaiting lab test
                        </div>
                    </div>
                    @endif
                </td>
                <td class="py-3.5 px-4 max-w-xs">
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
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end space-x-1">
                        <button type="button"
                            onclick="openScreeningModal({{ json_encode([
                                'id' => str_pad($screening->id, 3, '0', STR_PAD_LEFT),
                                'raw_id' => $screening->id,
                                'is_civilian' => $isCivilian,
                                'person_type' => $isCivilian ? 'Civilian' : 'Suspect',
                                'suspect_name' => $personName,
                                'suspect_alias' => $isCivilian ? 'N/A' : ($suspect->suspect_alias ?? 'N/A'),
                                'ghana_card' => $isCivilian ? 'N/A' : ($suspect->ghana_card ?? 'N/A'),
                                'gender' => $isCivilian ? 'N/A' : ($suspect->gender ?? 'N/A'),
                                'holding_facility' => $isCivilian ? 'Walk-in / Civilian' : ($suspect->holding_facility ?? 'NACOC Custody'),
                                'case_number' => $isCivilian ? 'N/A (Civilian Walk-in)' : ($parentCase->case_number ?? 'N/A'),
                                'case_title' => $isCivilian ? 'Non-Docket Civilian Intake' : ($parentCase->title ?? 'N/A'),
                                'purpose' => $screening->purpose,
                                'screening_date' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                                'result' => $screening->result,
                                'substances_found' => $screening->substances_found ?? ($resultLower === 'negative' ? 'No drug(s) detected' : ($resultLower === 'positive' ? 'Substances detected' : 'Pending laboratory screening')),
                                'notes' => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                                'note' => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                                'on_medication' => (bool) $screening->on_medication,
                                'medication_summary' => $screening->medication_summary,
                                'medications' => $screening->medications->map(function ($m) {
                                    return [
                                        'medication_name' => $m->medication_name,
                                        'dosage' => $m->dosage ?? '—',
                                        'form' => $m->form ?? '—',
                                        'frequency' => $m->frequency ?? '—',
                                        'route' => $m->route ?? '—',
                                        'source_label' => $m->source_label,
                                        'date_started' => $m->date_started ? $m->date_started->format('d M Y') : '—',
                                        'duration_ongoing' => $m->duration_ongoing ?? '—',
                                        'last_dose_taken' => $m->last_dose_taken ? $m->last_dose_taken->format('d M Y, H:i') : '—',
                                    ];
                                })->values()->toArray(),
                                'created_at' => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
                                'mugshot' => $mugshotUrl,
                                'initials' => $initials,
                            ]) }})"
                            class="p-1.5 text-slate-500 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors cursor-pointer"
                            title="View Full Screening Record">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>

                        <a href="{{ url('/Lab_Analysis/drug-screening/' . $screening->id . '/pdf') }}" target="_blank"
                            class="p-1.5 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer"
                            title="{{ $isCivilian ? 'Print / Save Civilian PDF Report' : 'Print / Save Suspect PDF Report' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div class="text-slate-800 font-extrabold text-sm">No Drug Screening Records Found</div>
                        <p class="text-xs text-slate-500 max-w-sm">No suspects or civilians have had a drug screening recorded matching the active filter.</p>
                        <a href="{{ url('/Lab_Analysis/drug-screening/create') }}"
                            class="mt-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Record First Drug Screening</span>
                        </a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($screenings, 'links'))
    <div class="border-t border-slate-200">
        {{ $screenings->links() }}
    </div>
@endif

