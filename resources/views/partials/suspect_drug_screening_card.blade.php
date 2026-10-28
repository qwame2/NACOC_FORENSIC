{{--
    Per-suspect Drug Screening Docket Table.
    Attached directly to the specific suspect's dossier/docket across NACOC Forensic.

    Expects:
      $screening    — instance of App\Models\DrugScreening (or null)
      $suspectName  — string (suspect's full name)
      $suspectId    — (optional) int (case_suspects.id)
      $caseNumber   — (optional) string (case number)
      $showActions  — (optional) bool (strictly true in Lab Analysis show_review, false in other units)
      $showEmpty    — (optional) bool (only show empty state in Lab Analysis)
--}}

@if (!empty($screening))
    @php
        $res = ucfirst(strtolower(trim($screening->result ?? 'Pending')));
        $isPos = $res === 'Positive';
        $isNeg = $res === 'Negative';
        $isPend = !$isPos && !$isNeg;

        $resTextColor = $isPos ? 'text-red-600' : ($isNeg ? 'text-emerald-600' : 'text-amber-600');
        $badgeText = $isPos ? 'POSITIVE — SUBSTANCES DETECTED' : ($isNeg ? 'NEGATIVE — NO DRUGS DETECTED' : 'PENDING — AWAITING LAB OUTCOME');
        $substances = $screening->substance_list;
        $canShowActions = !empty($showActions);
    @endphp

    <div class="suspect-drug-screening-report mt-4 space-y-2 font-sans">
        {{-- Table Title Banner --}}
        <div class="flex items-center justify-between gap-2.5 pb-1 border-b border-slate-300">
            <div class="flex items-center space-x-2 flex-wrap">
                <span class="w-2.5 h-2.5 rounded-full {{ $isPos ? 'bg-red-500' : ($isNeg ? 'bg-emerald-500' : 'bg-amber-500') }}"></span>
                <h4 class="text-xs font-black text-slate-900 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                    <span>Drug Screening Report</span>
                    <span class="text-slate-400 font-normal">&mdash;</span>
                    <span class="text-slate-700">{{ $suspectName }}</span>
                </h4>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-mono-code font-black uppercase tracking-wider {{ $resTextColor }}">
                    {{ $badgeText }}
                </span>
                @if ($canShowActions)
                    <div class="flex items-center gap-1.5 no-print">
                        <a href="{{ url('/Lab_Analysis/drug-screening?search=' . urlencode($suspectName)) }}"
                            class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer"
                            title="View Screening Register">
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>View Screening Register</span>
                        </a>
                        <a href="{{ url('/Lab_Analysis/drug-screening/create') }}"
                            class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-[10px] font-extrabold transition-all flex items-center gap-1 shadow-2xs cursor-pointer"
                            title="Update / Re-Screen">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Update / Re-Screen</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Normal Table with Row Headers at the Top --}}
        <div class="overflow-x-auto border border-slate-300 rounded-xl bg-white shadow-2xs">
            <table class="w-full text-xs text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-100 text-slate-800 font-mono-code uppercase text-[10px] font-extrabold border-b border-slate-300">
                    <tr>
                        <th class="py-3 px-3.5 border-r border-slate-200 whitespace-nowrap">Screening Dossier #</th>
                        <th class="py-3 px-3.5 border-r border-slate-200 whitespace-nowrap">Date of Screening</th>
                        <th class="py-3 px-3.5 border-r border-slate-200">Purpose of Screening</th>
                        <th class="py-3 px-3.5 border-r border-slate-200 text-center whitespace-nowrap">Screening Outcome</th>
                        <th class="py-3 px-3.5 border-r border-slate-200">Substance(s) Found</th>
                        <th class="py-3 px-3.5">Remarks / Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr class="hover:bg-slate-50 transition-colors">
                        {{-- 1. Dossier # --}}
                        <td class="py-3.5 px-3.5 font-mono-code font-extrabold text-slate-900 border-r border-slate-200 whitespace-nowrap">
                            SCR-{{ str_pad($screening->id, 3, '0', STR_PAD_LEFT) }}
                        </td>

                        {{-- 2. Date of Screening --}}
                        <td class="py-3.5 px-3.5 font-mono-code font-bold text-slate-900 border-r border-slate-200 whitespace-nowrap">
                            {{ $screening->formatted_date }}
                        </td>

                        {{-- 3. Purpose of Screening --}}
                        <td class="py-3.5 px-3.5 font-semibold text-slate-900 border-r border-slate-200 max-w-[200px]">
                            {{ $screening->purpose }}
                        </td>

                        {{-- 4. Screening Outcome Result: NO background container, just text red / green / amber --}}
                        <td class="py-3.5 px-3.5 border-r border-slate-200 text-center whitespace-nowrap">
                            <span class="font-mono-code font-black text-xs tracking-wider {{ $resTextColor }}">
                                {{ strtoupper($res) }}
                            </span>
                        </td>

                        {{-- 5. Substance(s) Found --}}
                        <td class="py-3.5 px-3.5 border-r border-slate-200 max-w-[220px]">
                            @if ($isPos && count($substances) > 0)
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    @foreach ($substances as $subTag)
                                        <span class="font-mono-code font-bold text-xs text-red-600">
                                            {{ $subTag }}{{ !$loop->last ? ',' : '' }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif ($isPos)
                                <span class="font-bold text-red-600 font-mono-code">{{ $screening->substances_found ?: 'Substances detected' }}</span>
                            @elseif ($isNeg)
                                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-bold font-mono-code whitespace-nowrap">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>No drug(s) detected</span>
                                </span>
                            @else
                                <span class="text-amber-600 italic font-mono-code">Awaiting laboratory outcome</span>
                            @endif
                        </td>

                        {{-- 6. Remarks / Notes --}}
                        <td class="py-3.5 px-3.5 text-slate-700 max-w-[260px] text-[11px] leading-relaxed">
                            {{ $screening->notes ?? (is_array($screening) ? ($screening['notes'] ?? null) : null) ?: 'No remarks recorded.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@elseif (!empty($showEmpty) && !empty($showActions))
    {{-- Empty State: Option to screen suspect (strictly in Lab Analysis) --}}
    <div class="mt-4 p-3.5 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-sans">
        <div class="flex items-center space-x-2.5">
            <div class="w-7 h-7 rounded-lg bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <div>
                <div class="font-extrabold text-slate-700 text-xs">No Drug Screening Attached Yet</div>
                <div class="text-[11px] text-slate-400">Substance screening has not yet been logged for {{ $suspectName }}.</div>
            </div>
        </div>
        <a href="{{ url('/Lab_Analysis/drug-screening/create') }}"
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-[11px] font-bold transition-all shrink-0 cursor-pointer">
            + Screen Suspect
        </a>
    </div>
@endif
