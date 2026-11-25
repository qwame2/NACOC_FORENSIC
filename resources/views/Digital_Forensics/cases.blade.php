@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
    $allCases = $allCases ?? \App\Models\ForensicCase::allCases();
    $digitalCases = $digitalCases ?? $allCases->filter(function ($c) {
        if (!$c->isDigitalCase()) {
            return false;
        }
        $elecSuspects = $c->getElectronicDeviceSuspects();
        return !empty($elecSuspects);
    })->values();
    if (!isset($cases) || !($cases instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
        $cases = \App\Models\ForensicCase::paginateCases($digitalCases, (int) request()->input('per_page', 15));
    }
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
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('js/ajax-table.js') }}"></script>
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
                        <input type="text" placeholder="Search Device Serial, IMEI..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Digital &amp; Mobile Cyber Forensics HQ
                    </span>
                </div>
            </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')
                
                <!-- MASTER CASE TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-4 p-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-extrabold text-slate-900 text-sm">Digital Forensics Case Register</h3>
                                
                                <span id="regRowCountBadge" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 font-mono-code">
                                    Total: {{ count($cases) }}
                                </span>
                            </div>
                            
                        </div>
                        <div class="relative w-full sm:max-w-xs">
                            <input type="text" id="regSearchInput" onkeyup="applyCaseFilters()" placeholder="Filter by Case #, User..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- DATE FILTER TOOLBAR 
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
                    </div>-->

                    <div id="digitalCasesTableContainer">
                        @include('Digital_Forensics.partials.cases_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        let currentRegPreset = 'all';

        // Initialize Reusable AJAX Table Controller for Digital Forensics Cases
        function setupDigitalCasesAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.digitalCasesAjaxTable) return;

            window.digitalCasesAjaxTable = initAjaxTable({
                container: '#digitalCasesTableContainer',
                table: '#regMasterCaseTable',
                columns: 5,
                rows: 6,
                onAfterUpdate: (container) => {
                    const rows = container.querySelectorAll('.case-register-row');
                    const badge = document.getElementById('regRowCountBadge');
                    if (badge) badge.textContent = `Showing: ${rows.length}`;
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupDigitalCasesAjaxTable);
        } else {
            setupDigitalCasesAjaxTable();
        }

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
            const fromInput = document.getElementById('regDateFilterFrom');
            if (fromInput) fromInput.value = '';
            const toInput = document.getElementById('regDateFilterTo');
            if (toInput) toInput.value = '';
            updateRegPresetButtonsVisual('all');
            currentRegPreset = 'all';
            if (window.digitalCasesAjaxTable) {
                window.digitalCasesAjaxTable.fetchData('/Digital_Forensics/cases', true);
            }
        }

        function applyCaseFilters() {
            const searchInput = document.getElementById('regSearchInput');
            const query = (searchInput ? searchInput.value : '').trim();

            const fromVal = document.getElementById('regDateFilterFrom') ? document.getElementById('regDateFilterFrom').value : '';
            const toVal = document.getElementById('regDateFilterTo') ? document.getElementById('regDateFilterTo').value : '';

            if (window.digitalCasesAjaxTable) {
                if (window.digitalCasesAjaxTable.debounceTimer) clearTimeout(window.digitalCasesAjaxTable.debounceTimer);
                window.digitalCasesAjaxTable.debounceTimer = setTimeout(() => {
                    window.digitalCasesAjaxTable.applyFilters({
                        search: query || null,
                        from: fromVal || null,
                        to: toVal || null,
                        page: null
                    });
                }, 350);
            }
        }

        // Suspect Mugshot Lightbox / Enlarge Modal Handlers
        function openMugshotModal(imgUrl, suspectName) {
            if (!imgUrl) return;
            const modal = document.getElementById('mugshotModal');
            const img = document.getElementById('modalMugshotImg');
            const nameEl = document.getElementById('modalSuspectName');
            if (img) img.src = imgUrl;
            if (nameEl) nameEl.textContent = suspectName || 'Suspect';
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeMugshotModal() {
            const modal = document.getElementById('mugshotModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function handleMugshotBackdropClick(e) {
            if (e.target && e.target.id === 'mugshotModal') {
                closeMugshotModal();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMugshotModal();
            }
        });
    </script>

            </main>
        </div>
    </div>

    <!-- SUSPECT MUGSHOT ENLARGE PREVIEW MODAL -->
    <div id="mugshotModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs transition-opacity duration-200" onclick="handleMugshotBackdropClick(event)">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-sm w-full overflow-hidden animate-in zoom-in-95 duration-200" onclick="event.stopPropagation()">
            <!-- MODAL HEADER -->
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h4 class="font-mono-code font-bold text-xs uppercase tracking-wider text-slate-100">Custodial Front Portrait</h4>
                </div>
                <button type="button" onclick="closeMugshotModal()" class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-800 cursor-pointer" title="Close Preview">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- MODAL IMAGE BODY -->
            <div class="p-6 flex flex-col items-center bg-slate-50">
                <div class="relative w-56 h-64 rounded-2xl overflow-hidden border-2 border-slate-300 bg-slate-200 shadow-md flex items-center justify-center">
                    <img id="modalMugshotImg" src="" alt="Suspect Mugshot" class="w-full h-full object-cover">
                </div>
                <div class="mt-4 text-center">
                    <div id="modalSuspectName" class="font-mono-code font-black text-sm uppercase text-slate-900 tracking-tight"></div>
                    <div class="text-[10px] font-mono-code text-slate-500 uppercase mt-0.5 tracking-wider">NACOC Custodial Photographic Archive</div>
                </div>
            </div>
            <!-- MODAL FOOTER -->
            <div class="px-5 py-3 bg-white border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="closeMugshotModal()" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-mono-code font-bold rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</body>
</html>
