@php
    $authUser = auth()->user();
    $isStaffExpellingReport = $isStaffExpellingReport ?? false;
    $isLabAnalysis = true;
    $sidebarUnit = 'Lab Analysis';
    $baseActionUrl = $isStaffExpellingReport ? url('/Lab_Analysis/staff/expelling-report') : url('/Lab_Analysis/expelling-report');
    $blankExpellingUrl = $isStaffExpellingReport ? url('/Lab_Analysis/staff/expelling-report/blank-pdf') : url('/Lab_Analysis/expelling-report/blank-pdf');
    $createCaseUrl = $isStaffExpellingReport ? url('/Lab_Analysis/staff/create') : url('/Lab_Analysis/create');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Suspect Expelling Report | Lab Analysis | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
        };
    </script>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>
    @include('partials.report_design_system')
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">
    <div class="flex h-screen w-full overflow-hidden">
        @if($isStaffExpellingReport)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Lab Analysis'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden report-print-container">
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="plane-takeoff" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">Laboratory Analysis Unit</span>
                            <span class="text-slate-300">/</span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Transit &amp; Ingestion Ledger</span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">Suspect Expelling Report</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0" id="printActionButtons">
                    <a href="{{ $blankExpellingUrl }}" target="_blank" rel="noopener" class="bg-white hover:bg-slate-100 text-emerald-700 border border-emerald-300 px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-colors no-underline">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        <span>Blank Form (PDF)</span>
                    </a>
                    <button type="button" onclick="window.print()" class="rep-btn-green px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Print Report</span>
                    </button>
                    <a href="{{ $createCaseUrl }}" class="bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>New Case</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">
                <!-- MASTHEAD + KPI STRIP -->
                <section class="rep-masthead">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Suspect Expelling &amp; Aviation Transit Surveillance Report
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Consolidated registry of suspects with recorded Passport Numbers, Flight Numbers, Travel Destinations, and Substances Expelled.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rep-kpi-grid grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-slate-200/80 border-t border-slate-200/80">
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Total Expelling Records</div>
                            <div class="text-2xl font-extrabold font-mono-code text-slate-900 mt-1 tnum">{{ $stats['total'] }}</div>
                        </div>
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Passports Logged</div>
                            <div class="text-2xl font-extrabold font-mono-code text-emerald-700 mt-1 tnum">{{ $stats['passports'] }}</div>
                        </div>
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Flights Tracked</div>
                            <div class="text-2xl font-extrabold font-mono-code text-sky-700 mt-1 tnum">{{ $stats['flights'] }}</div>
                        </div>
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Unique Destinations</div>
                            <div class="text-2xl font-extrabold font-mono-code text-amber-700 mt-1 tnum">{{ $stats['destinations'] }}</div>
                        </div>
                    </div>
                </section>

                <!-- FILTER BAR -->
                <section id="reportFilterConsole" class="rep-surface-card p-4 sm:px-6">
                    <form method="GET" action="{{ $baseActionUrl }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">Search Suspect / Passport / Flight / Destination / Substance</label>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search suspect, case #, passport, flight, destination..." class="rep-input w-full px-3.5 py-2 text-xs font-mono-code">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">From Date</label>
                            <input type="date" name="from" value="{{ $fromDate }}" class="rep-input w-full px-3 py-2 text-xs font-mono-code">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">To Date</label>
                            <input type="date" name="to" value="{{ $toDate }}" class="rep-input w-full px-3 py-2 text-xs font-mono-code">
                        </div>
                        <div class="md:col-span-3 flex items-center gap-2">
                            <button type="submit" class="rep-btn-green flex-1 px-4 py-2 rounded-xl text-xs font-bold">Filter</button>
                            <a href="{{ $baseActionUrl }}" class="px-3.5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 text-xs font-bold transition-colors">Reset</a>
                        </div>
                    </form>
                </section>

                <!-- DATA TABLE -->
                <section class="rep-surface-card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <span class="text-xs font-extrabold font-mono-code uppercase tracking-wider text-slate-800">Suspect Expelling Manifest</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 font-mono-code text-[10.5px] font-bold tnum">{{ $records->count() }} records</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="rep-table w-full text-left border-collapse text-xs">
                            <thead>
                                <tr>
                                    <th class="py-3.5 px-5">Suspect</th>
                                    <th class="py-3.5 px-4">Case #</th>
                                    <th class="py-3.5 px-4">Passport Number</th>
                                    <th class="py-3.5 px-4">Flight Number</th>
                                    <th class="py-3.5 px-4">Destination</th>
                                    <th class="py-3.5 px-4">Substance Expelled</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Arrest Date</th>
                                    <th class="py-3.5 px-5 text-right no-print">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($records as $row)
                                    @php
                                        $initials = strtoupper(substr(trim((string) ($row['suspect_name'] ?? 'SP')), 0, 2));
                                    @endphp
                                    <tr>
                                        <td class="py-3.5 px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center shrink-0">
                                                    @if(!empty($row['mugshot_url']))
                                                        <img src="{{ $row['mugshot_url'] }}" alt="{{ $row['suspect_name'] }}" class="w-full h-full object-cover" onerror="this.style.display='none'; if(this.nextElementSibling){this.nextElementSibling.classList.remove('hidden');}">
                                                        <span class="hidden text-[11px] font-extrabold font-mono-code text-slate-700">{{ $initials }}</span>
                                                    @else
                                                        <span class="text-[11px] font-extrabold font-mono-code text-slate-700">{{ $initials }}</span>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-extrabold text-slate-900 truncate">{{ $row['suspect_name'] }}</div>
                                                    @if(!empty($row['nationality']))
                                                        <div class="text-[10.5px] text-slate-500 font-medium">{{ $row['nationality'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono-code font-bold text-emerald-800 whitespace-nowrap">{{ $row['case_number'] }}</td>
                                        <td class="py-3.5 px-4 font-mono-code font-bold text-slate-900 whitespace-nowrap">{{ $row['passport_number'] }}</td>
                                        <td class="py-3.5 px-4 font-mono-code font-bold text-sky-800 whitespace-nowrap">
                                            <div>{{ $row['flight_number'] }}</div>
                                            @if(!empty($row['airline']) && $row['airline'] !== '—')
                                                <div class="text-[10.5px] font-sans font-semibold text-slate-500">{{ $row['airline'] }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $row['destination'] }}</td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-amber-50 text-amber-900 border border-amber-200 font-bold text-[11px]">
                                                {{ $row['substance_expelled'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono-code text-slate-600 whitespace-nowrap">{{ $row['arrest_date'] ?: 'N/A' }}</td>
                                        <td class="py-3.5 px-5 text-right whitespace-nowrap no-print">
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                @if(!empty($row['pdf_url']))
                                                    <a href="{{ $row['pdf_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition-colors">
                                                        <span>Expelling Report</span>
                                                    </a>
                                                @endif
                                                @if(!empty($row['review_url']))
                                                    <a href="{{ $row['review_url'] }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 text-[11px] font-bold transition-colors">
                                                        <span>View Docket</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-14 px-6 text-center text-slate-500">
                                            <div class="font-extrabold text-slate-800 text-sm">No Suspect Expelling Records Found</div>
                                            <p class="text-xs text-slate-500 mt-1">Enter Passport Number, Flight Number, Destination, or Substance Expelled when registering a Lab Analysis case.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>

