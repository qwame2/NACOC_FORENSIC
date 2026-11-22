{{--
    Partial: partials/lab-examination-card.blade.php
    Description: Renders the read-only Laboratory Examination Result card for a suspect or joint exhibit dossier.
    Required Inputs (@props):
      - $labExam (array): Resolved laboratory examination array (from CaseLabExamResolver::formatLabExamForCard).
      - $labHeadingName (string): Display name of the suspect or joint owners for the card header.
      - $case (object|null): The ForensicCase instance (used for case_number display).
--}}@props([
    'labExam' => [],
    'labHeadingName' => 'Primary Suspect',
    'case' => null,
])

@if (!empty($labExam['card_has_lab_data']) || (!empty($labExam) && (!empty($labExam['has_saved']) || !empty($labExam['has_saved_lab']) || !empty($labExam['exam_reference']) || !empty($labExam['laboratory_number']) || !empty($labExam['substance_identification']) || !empty($labExam['presumptive_result']))))
    <!-- LABORATORY EXAMINATION RESULT FOR {{ strtoupper($labHeadingName) }} -->
    <div id="labCard-{{ $labExam['card_slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $labHeadingName)) }}" data-lab-card="true" class="p-5 bg-emerald-50/80 border-2 border-emerald-300 rounded-2xl space-y-4 shadow-sm mt-4">
        <div class="flex items-center justify-between pb-3 border-b border-emerald-200">
            <div class="flex items-center space-x-3">
                <div
                    class="w-8 h-8 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($labHeadingName) }}</h3>
                    <p class="text-[11px] text-emerald-800 font-semibold">Official Laboratory Analysis Directorate Findings for {{ $labHeadingName }}</p>
                </div>
            </div>
            <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-200 text-emerald-950 border border-emerald-400">
                ✓ VERIFIED RESULT LOGGED
            </span>
        </div>

        <table
            class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden shadow-2xs bg-white">
            <tbody>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">
                        Analysis Reference:</td>
                    <td
                        class="p-2.5 font-black text-emerald-900 font-mono-code border-r border-slate-300 w-3/10">
                        {{ $labExam['card_exam_reference'] ?? ($labExam['exam_reference'] ?? 'N/A') }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">
                        Exhibit Number:</td>
                    <td class="p-2.5 font-black text-emerald-900 font-mono-code">
                        {{ $labExam['exhibit_number'] ?? 'N/A' }}</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Case Reference:</td>
                    <td
                        class="p-2.5 font-black text-blue-900 font-mono-code border-r border-slate-300">
                        {{ $case->case_number ?? 'N/A' }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Laboratory Number:</td>
                    <td class="p-2.5 font-black text-slate-900 font-mono-code">
                        {{ $labExam['laboratory_number'] ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Exhibit Description:</td>
                    <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                        {{ $labExam['card_exhibit_description'] ?? ($labExam['exhibit_description'] ?? ($labExam['description'] ?? ($labExam['sample_weight']['description'] ?? 'N/A'))) }}
                    </td>
                </tr>
                @if (!empty($labExam['purpose_of_examination']) && $labExam['purpose_of_examination'] !== 'N/A')
                    <tr class="bg-slate-100/90 border-b border-slate-300">
                        <td
                            class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                            Purpose of Examination:</td>
                        <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3" id="displayLabPurposeOfExamination">
                            {{ $labExam['purpose_of_examination'] }}
                        </td>
                    </tr>
                @endif
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Analyst / Examiner:</td>
                    <td class="p-2.5 font-black text-slate-900 border-r border-slate-300">
                        {{ $labExam['analyst'] ?? 'N/A' }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Examination Timeline:</td>
                    <td class="p-2 bg-white">
                                                <div
                            class="flex items-center gap-2 font-mono-code text-[11px] font-bold">
                            <span>Received: <strong class="text-blue-900">{{ $labExam['card_timeline_received'] ?? 'N/A' }}</strong></span>
                            <span>|</span>
                            <span>Started: <strong class="text-amber-900">{{ $labExam['card_timeline_started'] ?? 'N/A' }}</strong></span>
                            <span>|</span>
                            <span>Completed: <strong class="text-emerald-900">{{ $labExam['card_timeline_completed'] ?? 'N/A' }}</strong></span>
                        </div>
                    </td>
                </tr>
                @if (!empty($labExam['preliminary_exam_procedure']) && $labExam['preliminary_exam_procedure'] !== 'N/A')
                    <tr class="border-b border-slate-300">
                        <td
                            class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                            Preliminary Exam Procedure:</td>
                        <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                            {{ $labExam['preliminary_exam_procedure'] }}
                        </td>
                    </tr>
                @endif
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Presumptive Reaction:</td>
                    <td class="p-2 border-r border-slate-300 bg-white">
                                                <table
                            class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                            <thead
                                class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                <tr class="border-b border-slate-300">
                                    <th
                                        class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                        Result</th>
                                    <th
                                        class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                        Presence of</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">
                                        Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach (($labExam['card_presumptive_rows'] ?? [['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A']]) as $pRowD)
                                    <tr class="border-b border-slate-200 last:border-b-0">
                                        <td
                                            class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pRowD['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">
                                            {{ $pRowD['result'] }}</td>
                                        <td
                                            class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">
                                            {{ $pRowD['presence'] }}</td>
                                        <td class="px-2 py-1 font-bold text-slate-900">
                                            {{ $pRowD['purity'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Confirmatory Test:</td>
                    <td class="p-2 bg-white">
                        @php
                            $cRowsD = $labExam['card_confirmatory_rows'] ?? null;
                            if (empty($cRowsD)) {
                                $cVal = $labExam['card_confirmatory_val'] ?? ($labExam['confirmatory_result'] ?? ($labExam['confirmatory'] ?? null));
                                $dSubs = $labExam['detected_substances'] ?? ($labExam['card_detected_substances'] ?? []);
                                $dPcts = $labExam['detected_subtances_percentage'] ?? ($labExam['detected_substances_percentage'] ?? []);
                                $cRowsD = \App\Models\ForensicCase::parseConfirmatoryRows($cVal, $dSubs, $dPcts);
                            }
                        @endphp
                        <table
                            class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                            <thead
                                class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                <tr class="border-b border-slate-300">
                                    <th
                                        class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                        Result</th>
                                    <th
                                        class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">
                                        Substance Detected</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">
                                        Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($cRowsD as $cRowD)
                                    <tr class="border-b border-slate-200 last:border-b-0">
                                        <td
                                            class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $cRowD['result'] === 'YES' ? 'text-emerald-800' : ($cRowD['result'] === 'NO' ? 'text-red-700' : 'text-slate-900') }}">
                                            {{ $cRowD['result'] }}</td>
                                        <td
                                            class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">
                                            {{ $cRowD['presence'] }}</td>
                                        <td class="px-2 py-1 font-bold text-slate-900">
                                            {{ $cRowD['purity'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Sample Metrology:</td>
                    <td class="p-2.5 bg-white" colspan="3">
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                            <div
                                class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Description:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $labExam['sample_weight']['description'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Units:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $labExam['sample_weight']['units_packaging'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Gross
                                    Wt:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $labExam['sample_weight']['gross_weight'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Taken:</span>
                                <span
                                    class="font-bold text-emerald-900 ml-1">{{ $labExam['sample_weight']['sample_taken'] ?? '—' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Reserved:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $labExam['sample_weight']['reserved_samples'] ?? '—' }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Test Specifications:</td>
                    <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                        {{ $labExam['specifications'] ?? 'N/A' }}</td>
                </tr>
                @php
                    $screeningMethodsVal = $labExam['screening_method'] ?? null;
                    if (is_string($screeningMethodsVal) && (str_starts_with(trim($screeningMethodsVal), '[') || str_starts_with(trim($screeningMethodsVal), '{'))) {
                        $decoded = json_decode($screeningMethodsVal, true);
                        if (is_array($decoded)) {
                            $screeningMethodsVal = implode(', ', array_filter(array_map('trim', $decoded)));
                        }
                    } elseif (is_array($screeningMethodsVal)) {
                        $screeningMethodsVal = implode(', ', array_filter(array_map('trim', $screeningMethodsVal)));
                    }
                @endphp
                @if (!empty($screeningMethodsVal) && $screeningMethodsVal !== 'N/A')
                    <tr class="bg-slate-100/90 border-b border-slate-300">
                        <td
                            class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                            Screening Method / Instrument:</td>
                        <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                            {{ $screeningMethodsVal }}
                        </td>
                    </tr>
                @endif
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Test Methods:</td>
                    <td class="p-2.5 font-bold text-slate-900 border-r border-slate-300"
                        colspan="3">{{ $labExam['test_methods'] ?? 'N/A' }}</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Analysis Remark:</td>
                    <td class="p-2.5 font-bold text-slate-900" colspan="3">
                        {{ $labExam['remark'] ?? 'N/A' }}</td>
                </tr>
                @if (!empty($labExam['disposition_of_exhibit']) && $labExam['disposition_of_exhibit'] !== 'N/A')
                    <tr class="border-b border-slate-300">
                        <td
                            class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                            Disposition of Exhibit:</td>
                        <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                            {{ $labExam['disposition_of_exhibit'] }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
@endif
