@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
    $allCases = \App\Models\ForensicCase::allCases();
    $cases = $allCases->filter(fn($c) => $c->isDigitalCase())->values();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Digital &amp; Physical Forensics Command Center | NACOC Forensic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <!-- TOP BAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-20">
                <div class="flex items-center gap-4 flex-1 max-w-2xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" placeholder="Search Device Serial, IMEI, Hash Value, Crypto Wallet..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                </div>
                
            </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- HEADER HERO -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">DIGITAL &amp; PHYSICAL FORENSICS UNIT</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">MOBILE &amp; CYBER EXTRACTION COMMAND</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Digital &amp; Mobile Cyber Forensics HQ</h1>
                        <p class="text-xs text-slate-500 mt-1">Lead Cyber Officer: <strong class="text-slate-800">{{ $headName }}</strong> &bull; Total Devices Examined: <strong class="text-emerald-700 font-mono-code">{{ count($cases) }}</strong></p>
                    </div>
                </div>



                <!-- MASTER CASE TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-4 p-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-slate-900 text-sm">Digital Forensics Case Register</h3>
                                <span id="regRowCountBadge" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 font-mono-code">
                                    Total: {{ count($cases) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">Device imaging, chip-off extractions, and cyber evidence logs</p>
                        </div>
                        <div class="relative w-full sm:max-w-xs">
                            <input type="text" id="regSearchInput" onkeyup="applyCaseFilters()" placeholder="Filter by Case #, Suspect..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- DATE FILTER TOOLBAR -->
                    <div class="p-3 bg-slate-50/80 border border-slate-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 font-mono-code mr-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Period:</span>
                            </span>
                            <button type="button" onclick="setRegDateFilterPreset('all')" id="regBtnPresetAll" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-emerald-600 text-white shadow-xs cursor-pointer">
                                All Time
                            </button>
                            <button type="button" onclick="setRegDateFilterPreset('weekly')" id="regBtnPresetWeekly" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Weekly
                            </button>
                            <button type="button" onclick="setRegDateFilterPreset('monthly')" id="regBtnPresetMonthly" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Monthly
                            </button>
                            <button type="button" onclick="setRegDateFilterPreset('annually')" id="regBtnPresetAnnually" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Annually
                            </button>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-500 font-mono-code uppercase">From:</span>
                                <input type="date" id="regDateFilterFrom" onchange="onRegCustomDateChange()" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-500 font-mono-code uppercase">To:</span>
                                <input type="date" id="regDateFilterTo" onchange="onRegCustomDateChange()" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                            </div>
                            <button type="button" onclick="generateRegCaseFilter()" id="regBtnGenerateFilter" title="Generate Filtered Cases" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white border border-emerald-700 rounded-lg text-xs font-mono-code font-bold transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>Generate</span>
                            </button>
                            <button type="button" onclick="resetRegDateFilters()" title="Reset Filters" class="px-2.5 py-1 bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-mono-code font-bold transition-colors flex items-center gap-1 shadow-2xs cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700" id="regMasterCaseTable">
                            <thead class="bg-slate-100/80 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase">
                                <tr>
                                    <th class="py-3.5 px-5">Case #</th>
                                    <th class="py-3.5 px-5">Logged Date</th>
                                    <th class="py-3.5 px-5">Title</th>
                                    <th class="py-3.5 px-5">Suspect Name</th>
                                    <th class="py-3.5 px-5">Suspect Disposition</th>
                                    <th class="py-3.5 px-5">Status</th>
                                    <th class="py-3.5 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 font-mono-code">
                                @forelse($cases as $c)
                                @php
                                    $cDateRaw = !empty($c->incident_date) ? $c->incident_date : ($c->created_at ? $c->created_at->format('Y-m-d') : date('Y-m-d'));
                                    $cTime = strtotime((string)$cDateRaw);
                                    $cDateIso = ($cTime && $cTime > 0) ? date('Y-m-d', $cTime) : date('Y-m-d');
                                    $cDateDisplay = ($cTime && $cTime > 0) ? date('d M Y', $cTime) : date('d M Y');
                                @endphp
                                <tr class="hover:bg-slate-50 case-register-row" data-date="{{ $cDateIso }}" data-caseref="{{ $c->case_number }}">
                                    <td class="py-3.5 px-5 font-bold font-mono-code text-emerald-700">{{ $c->case_number }}</td>
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        <div class="font-bold text-[11px] text-slate-800">{{ $cDateDisplay }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 font-semibold text-slate-900 font-sans">{{ $c->title }}</td>
                                    <td class="py-3.5 px-5 text-slate-600 font-sans">{{ $c->suspect_name ?? 'N/A' }}</td>
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        @php
                                            $sDispListDfu = $c->getSuspectsList();
                                        @endphp
                                        <div class="space-y-1">
                                            @foreach ($sDispListDfu as $sObj)
                                                @php
                                                    $dispVal = $sObj['suspect_disposition'] ?? 'Active — Under Investigation';
                                                    $dispReason = $sObj['disposition_reason'] ?? '';
                                                    $dispBadge = match (true) {
                                                        str_contains($dispVal, 'Cleared') => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                        str_contains($dispVal, 'Released') => 'bg-blue-100 text-blue-800 border-blue-300',
                                                        str_contains($dispVal, 'Reclassified') => 'bg-teal-100 text-teal-800 border-teal-300',
                                                        str_contains($dispVal, 'Referred') => 'bg-amber-100 text-amber-800 border-amber-300',
                                                        default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                    };
                                                @endphp
                                                <div class="flex items-center gap-1.5" title="{{ !empty($dispReason) ? 'Reason: ' . $dispReason : $dispVal }}">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border {{ $dispBadge }}">
                                                        @if (count($sDispListDfu) > 1)
                                                            <span class="font-extrabold text-slate-900 mr-1">{{ $sObj['suspect_name'] }}:</span>
                                                        @endif
                                                        {{ $dispVal }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono-code bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $c->status }}</span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right">
                                        <a href="{{ url('/cases/' . $c->case_number . '/review?from=Digital_Forensics_Register') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-xs transition-colors">Review Extraction File</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-slate-400 font-semibold">No digital forensics cases registered yet.</td>
                                </tr>
                                @endforelse
                                <tr id="regNoRecordsRow" class="hidden">
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-semibold font-mono-code">
                                        <div class="space-y-2">
                                            <p>No matching digital forensic cases found.</p>
                                            <button type="button" onclick="resetRegDateFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 text-xs transition-colors cursor-pointer">
                                                <span>Reset to All Time</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        let currentRegPreset = 'all';

        function getRegReferenceDate() {
            const rows = document.querySelectorAll('#regMasterCaseTable tbody tr.case-register-row');
            let maxDateStr = null;
            rows.forEach(r => {
                const d = r.getAttribute('data-date');
                if (d && (!maxDateStr || d > maxDateStr)) {
                    maxDateStr = d;
                }
            });

            const now = new Date();
            if (maxDateStr) {
                const dataYear = parseInt(maxDateStr.slice(0, 4), 10);
                if (Math.abs(now.getFullYear() - dataYear) >= 1) {
                    const parts = maxDateStr.split('-');
                    if (parts.length === 3) {
                        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    }
                }
            }
            return now;
        }

        function updateRegPresetButtonsVisual(activePreset) {
            const presets = ['all', 'weekly', 'monthly', 'annually'];
            presets.forEach(p => {
                const btn = document.getElementById('regBtnPreset' + p.charAt(0).toUpperCase() + p.slice(1));
                if (!btn) return;
                if (p === activePreset) {
                    btn.className = 'px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-emerald-600 text-white shadow-xs cursor-pointer';
                    btn.style.backgroundColor = '#059669';
                    btn.style.color = '#ffffff';
                } else {
                    btn.className = 'px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer';
                    btn.style.backgroundColor = '#ffffff';
                    btn.style.color = '#334155';
                }
            });
        }

        function setRegDateFilterPreset(preset) {
            currentRegPreset = preset;
            updateRegPresetButtonsVisual(preset);

            const fromInput = document.getElementById('regDateFilterFrom');
            const toInput = document.getElementById('regDateFilterTo');

            const refDate = getRegReferenceDate();
            const pad = n => String(n).padStart(2, '0');
            const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (preset === 'all') {
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            } else if (preset === 'weekly') {
                const day = refDate.getDay();
                const diffToMon = (day === 0 ? -6 : 1) - day;
                const monday = new Date(refDate);
                monday.setDate(refDate.getDate() + diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);

                if (fromInput) fromInput.value = formatDate(monday);
                if (toInput) toInput.value = formatDate(sunday);
            } else if (preset === 'monthly') {
                const firstDay = new Date(refDate.getFullYear(), refDate.getMonth(), 1);
                const lastDay = new Date(refDate.getFullYear(), refDate.getMonth() + 1, 0);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            } else if (preset === 'annually') {
                const firstDay = new Date(refDate.getFullYear(), 0, 1);
                const lastDay = new Date(refDate.getFullYear(), 11, 31);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            }

            applyCaseFilters();
        }

        function onRegCustomDateChange() {
            currentRegPreset = 'custom';
            updateRegPresetButtonsVisual('custom');
            applyCaseFilters();
        }

        function generateRegCaseFilter() {
            const btn = document.getElementById('regBtnGenerateFilter');
            applyCaseFilters();

            const visibleRows = Array.from(document.querySelectorAll('#regMasterCaseTable tbody tr.case-register-row'))
                .filter(r => r.style.display !== 'none');

            if (visibleRows.length === 0) {
                alert('No cases found matching the selected filters.');
                return;
            }

            const caseList = [];
            visibleRows.forEach(r => {
                const cRef = (r.getAttribute('data-caseref') || '').trim();
                if (cRef && !caseList.includes(cRef)) {
                    caseList.push(cRef);
                }
            });

            if (caseList.length === 0) {
                alert('No case references found in the filtered rows.');
                return;
            }

            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Generating...</span>
                `;
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Printed!</span>
                    `;
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 1200);
                }, 250);
            }

            const params = new URLSearchParams();
            params.set('cases', caseList.join(','));
            params.set('print', '1');

            const fromVal = (document.getElementById('regDateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('regDateFilterTo')?.value || '').trim();
            if (fromVal) params.set('from', fromVal);
            if (toVal) params.set('to', toVal);

            let periodParam = 'general';
            if (currentRegPreset === 'weekly') {
                periodParam = 'weekly';
            } else if (currentRegPreset === 'monthly') {
                periodParam = 'monthly';
            } else if (currentRegPreset === 'annually') {
                periodParam = 'annual';
            } else if (fromVal || toVal || currentRegPreset === 'custom') {
                periodParam = 'date_range';
            } else {
                periodParam = 'general';
            }
            params.set('period', periodParam);

            const targetUrl = '{{ url("/Digital_Forensics/multi_dpf_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function resetRegDateFilters() {
            const searchInput = document.getElementById('regSearchInput');
            if (searchInput) searchInput.value = '';
            setRegDateFilterPreset('all');
        }

        function applyCaseFilters() {
            const searchInput = document.getElementById('regSearchInput');
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

            const fromVal = document.getElementById('regDateFilterFrom') ? document.getElementById('regDateFilterFrom').value : '';
            const toVal = document.getElementById('regDateFilterTo') ? document.getElementById('regDateFilterTo').value : '';

            const rows = document.querySelectorAll('#regMasterCaseTable tbody tr.case-register-row');
            let visibleCount = 0;

            rows.forEach(r => {
                const rowDate = r.getAttribute('data-date') || '';
                const rowText = r.innerText.toLowerCase();

                const matchesText = !query || rowText.includes(query);

                let matchesDate = true;
                if (fromVal && rowDate && rowDate < fromVal) {
                    matchesDate = false;
                }
                if (toVal && rowDate && rowDate > toVal) {
                    matchesDate = false;
                }

                const isVisible = matchesText && matchesDate;
                r.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            const noRecordsRow = document.getElementById('regNoRecordsRow');
            if (noRecordsRow) {
                noRecordsRow.classList.toggle('hidden', visibleCount > 0);
            }

            const badge = document.getElementById('regRowCountBadge');
            if (badge) {
                badge.textContent = `Showing: ${visibleCount}`;
            }
        }
    </script>

            </main>
        </div>
    </div>
</body>
</html>
