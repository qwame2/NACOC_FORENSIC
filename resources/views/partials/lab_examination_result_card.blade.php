@php
    $sDossierLabExam = $labExam ?? ($sDossierLabExam ?? []);
    $headingName = $labHeadingName ?? ($labExamHeadingName ?? ($sName ?? 'Primary Suspect'));
@endphp

@if (!empty($sDossierLabExam))
    <!-- LABORATORY EXAMINATION RESULT FOR {{ strtoupper($headingName) }} -->
    <div class="p-5 bg-emerald-50/80 border-2 border-emerald-300 rounded-2xl space-y-4 shadow-sm mt-4">
        <div class="flex items-center justify-between pb-3 border-b border-emerald-200">
            <div class="flex items-center space-x-3">
                <div
                    class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($headingName) }}</h3>
                    <p class="text-[11px] text-emerald-800 font-semibold">Official Laboratory Analysis Directorate Findings for {{ $headingName }}</p>
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
                        {{ !empty($sDossierLabExam['exam_reference']) ? str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $sDossierLabExam['exam_reference']) : 'NLR-FM-T35-F' }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">
                        Exhibit Number:</td>
                    <td class="p-2.5 font-black text-emerald-900 font-mono-code">
                        {{ $sDossierLabExam['exhibit_number'] ?? 'N/A' }}</td>
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
                        {{ $sDossierLabExam['laboratory_number'] ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Exhibit Description:</td>
                    <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                        {{ $sDossierLabExam['exhibit_description'] ?? ($sDossierLabExam['description'] ?? ($sDossierLabExam['sample_weight']['description'] ?? 'N/A')) }}
                    </td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Analyst / Examiner:</td>
                    <td class="p-2.5 font-black text-slate-900 border-r border-slate-300">
                        {{ $sDossierLabExam['analyst'] ?? 'N/A' }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Examination Timeline:</td>
                    <td class="p-2 bg-white">
                        <div
                            class="flex items-center gap-2 font-mono-code text-[11px] font-bold">
                            <span>Received: <strong
                                    class="text-blue-900">{{ $sDossierLabExam['date_received'] ?? 'N/A' }}</strong></span>
                            <span>|</span>
                            <span>Started: <strong
                                    class="text-amber-900">{{ $sDossierLabExam['date_started'] ?? 'N/A' }}</strong></span>
                            <span>|</span>
                            <span>Completed: <strong
                                    class="text-emerald-900">{{ $sDossierLabExam['date_completed'] ?? 'N/A' }}</strong></span>
                        </div>
                    </td>
                </tr>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Presumptive Reaction:</td>
                    <td class="p-2 border-r border-slate-300 bg-white">
                        @php
                            $pRawDos = trim($sDossierLabExam['presumptive_result'] ?? '');
                            $pRowsDos = [];
                            if (!empty($pRawDos) && $pRawDos !== 'N/A') {
                                $overallResultDos = 'N/A';
                                if (
                                    preg_match(
                                        '/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i',
                                        $pRawDos,
                                        $mResD,
                                    )
                                ) {
                                    $overallResultDos = strtoupper($mResD[1]);
                                    if (
                                        $overallResultDos === 'POS' ||
                                        $overallResultDos === 'POSITIVE'
                                    ) {
                                        $overallResultDos = 'YES';
                                    }
                                    if (
                                        $overallResultDos === 'NEG' ||
                                        $overallResultDos === 'NEGATIVE'
                                    ) {
                                        $overallResultDos = 'NO';
                                    }
                                }

                                $cleanPDos = $pRawDos;
                                if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRawDos, $mPD)) {
                                    $cleanPDos = $mPD[1];
                                }

                                $segmentsDos = preg_split(
                                    '/;\s*(?=\[\d+\]|Presence of:)/i',
                                    $cleanPDos,
                                );
                                if (empty($segmentsDos)) {
                                    $segmentsDos = [$cleanPDos];
                                }

                                foreach ($segmentsDos as $segD) {
                                    $segD = trim($segD);
                                    if (empty($segD)) {
                                        continue;
                                    }

                                    $presenceD = 'N/A';
                                    $purityD = 'N/A';

                                    if (
                                        preg_match(
                                            '/Presence of:\s*([^\-\;\)]+)/i',
                                            $segD,
                                            $mPresD,
                                        )
                                    ) {
                                        $presenceD = trim($mPresD[1]);
                                    } elseif (
                                        preg_match('/^\[\d+\]\s*([^\-\;\)]+)/i', $segD, $mPresD)
                                    ) {
                                        $presenceD = trim($mPresD[1]);
                                    } elseif (!empty($segD) && !str_contains($segD, 'Purity')) {
                                        $presenceD = trim($segD);
                                    }

                                    if (
                                        $presenceD === 'YES' ||
                                        $presenceD === 'NO' ||
                                        $presenceD === 'POS' ||
                                        $presenceD === 'NEG'
                                    ) {
                                        $presenceD = 'N/A';
                                    }

                                    if (
                                        preg_match(
                                            '/Purity\/Percentage:\s*([^\;\)]+)/i',
                                            $segD,
                                            $mPurD,
                                        )
                                    ) {
                                        $purityD = trim($mPurD[1]);
                                    }

                                    if (
                                        $presenceD !== 'N/A' ||
                                        $purityD !== 'N/A' ||
                                        count($segmentsDos) === 1
                                    ) {
                                        $pRowsDos[] = [
                                            'result' => $overallResultDos,
                                            'presence' => $presenceD,
                                            'purity' => $purityD,
                                        ];
                                    }
                                }
                            }
                            if (empty($pRowsDos)) {
                                $pRowsDos[] = [
                                    'result' => 'N/A',
                                    'presence' => 'N/A',
                                    'purity' => 'N/A',
                                ];
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
                                        Presence of</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">
                                        Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($pRowsDos as $pRowD)
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
                    <td class="p-2.5 font-black text-emerald-900">
                        @php
                            $confVal = strtoupper(
                                trim(
                                    (string) ($sDossierLabExam['confirmatory_result'] ?? 'YES'),
                                ),
                            );
                        @endphp
                        <span class="font-bold {{ $confVal !== 'NO' ? 'text-emerald-700' : 'text-red-700' }}">{{ $confVal !== 'NO' ? 'YES' : 'NO' }}</span>
                    </td>
                </tr>
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Sample Metrology:</td>
                    <td class="p-2.5 bg-white" colspan="3">
                        @php
                            $sw = $sDossierLabExam['sample_weight'] ?? [];
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                            <div
                                class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Description:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $sw['description'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Units:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $sw['units_packaging'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Gross
                                    Wt:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $sw['gross_weight'] ?? 'N/A' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Taken:</span>
                                <span
                                    class="font-bold text-emerald-900 ml-1">{{ $sw['sample_taken'] ?? '—' }}</span>
                            </div>
                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                <span
                                    class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Reserved:</span>
                                <span
                                    class="font-bold text-slate-900 ml-1">{{ $sw['reserved_samples'] ?? '—' }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Substance Identified:</td>
                    <td class="p-2.5 font-black text-slate-950 border-r border-slate-300">
                        {{ $sDossierLabExam['substance_identification'] ?? 'N/A' }}</td>
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Test Specifications:</td>
                    <td class="p-2.5 font-bold text-slate-900">
                        {{ $sDossierLabExam['specifications'] ?? 'N/A' }}</td>
                </tr>
                @php
                    $detSubs = $sDossierLabExam['detected_substances'] ?? [];
                    if (is_string($detSubs)) {
                        $detSubs = json_decode($detSubs, true) ?: [];
                    }
                @endphp
                @if (!empty($detSubs) && is_array($detSubs) && count($detSubs) > 0)
                    <tr class="bg-amber-50/70 border-b border-slate-300">
                        <td
                            class="p-2.5 font-mono-code font-black text-amber-950 uppercase border-r border-slate-300 bg-amber-100/80">
                            Result (Detected Substances & Purity %):</td>
                        <td class="p-2.5" colspan="3">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($detSubs as $cSub)
                                    @php
                                        $cName = is_array($cSub)
                                            ? $cSub['compound'] ??
                                                ($cSub['name'] ??
                                                    ($cSub['substance'] ?? 'Unknown'))
                                            : $cSub;
                                        $cPct = is_array($cSub)
                                            ? $cSub['purity'] ??
                                                ($cSub['percentage'] ??
                                                    ($cSub['concentration'] ?? null))
                                            : null;
                                        $cMeth = is_array($cSub)
                                            ? $cSub['method'] ?? null
                                            : null;
                                    @endphp
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-amber-300 shadow-2xs text-xs font-bold text-amber-950">
                                        <span>{{ $cName }}</span>
                                        @if ($cPct)
                                            <span
                                                class="text-emerald-800 font-mono font-extrabold">({{ rtrim((string)$cPct, '%') }}%)</span>
                                        @endif
                                        @if ($cMeth)
                                            <span
                                                class="text-slate-500 font-mono text-[10px]">[{{ $cMeth }}]</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endif
                <tr class="bg-slate-100/90 border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Test Methods:</td>
                    <td class="p-2.5 font-bold text-slate-900 border-r border-slate-300"
                        colspan="3">{{ $sDossierLabExam['test_methods'] ?? 'N/A' }}</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td
                        class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">
                        Analysis Remark:</td>
                    <td class="p-2.5 font-bold text-slate-900" colspan="3">
                        {{ $sDossierLabExam['remark'] ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-900 text-white">
                    <td
                        class="p-2.5 font-mono-code font-black text-emerald-400 uppercase border-r border-slate-800 bg-slate-950">
                        Analytical Findings:</td>
                    <td class="p-2.5 font-medium text-slate-100 leading-relaxed bg-slate-900"
                        colspan="3">
                        {{ $sDossierLabExam['findings'] ?? 'No specific laboratory analytical findings logged yet.' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endif
