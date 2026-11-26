@php
    $authUser = auth()->user();
    $sidebarUnit = 'Crime Scene Support';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crime Scene Support Unit | Field Operations &amp; Case Register Report | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts: System Vite Asset Loader & Compiled CSS -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Tailwind CSS Local Asset Fallback & Config -->
    <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
        };
    </script>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>
    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        finnova: {
                            purple: '#5c56df',
                            dark: '#181926',
                            darker: '#12131d',
                            card: '#2b2a5c',
                            light: '#f4f5fa'
                        }
                    },
                    spacing: {
                        '4.5': '1.125rem',
                        '18': '4.5rem',
                    },
                    boxShadow: {
                        'finnova': '0 20px 50px -15px rgba(24, 25, 38, 0.08)',
                        'finnova-dark': '0 25px 60px -15px rgba(10, 11, 20, 0.5)',
                        'tactical-glow': '0 0 50px rgba(16, 185, 129, 0.35)',
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                        '2xs': '0 1px 1px 0 rgba(0, 0, 0, 0.03)'
                    }
                }
            }
        };
    </script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .font-serif-header {
            font-family: 'Cinzel', Georgia, serif;
            letter-spacing: 0.04em;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Direct aside bar styling safeguard */
        aside.w-64 {
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            display: flex !important;
            flex-direction: column !important;
            flex-shrink: 0 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }

        @media print {
            aside, header, #reportFilterConsole, #paginationFooter, #printActionButtons, #reportNotesModal {
                display: none !important;
            }
            body, .report-print-container {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-only-header {
                display: block !important;
            }
            table {
                font-size: 10px !important;
            }
        }
        .print-only-header {
            display: none;
        }
    </style>
</head>

<body class="min-h-screen flex antialiased selection:bg-emerald-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        
        <!-- SHARED SIDEBAR -->
        @include('staff.partials.sidebar', ['sidebarUnit' => 'Digital Forensics'])

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden report-print-container">

            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                Crime Scene Support | Field Operations &amp; Case Report
                            </h1>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Master audit register of all cases, raid operations, warrants, suspect apprehension dossiers &amp; exhibit seizures.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5" id="printActionButtons">
                    <button type="button" 
                            id="printExportPdfBtn"
                            onclick="openReportNotesModal()" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-md shadow-blue-900/30 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print / Export PDF</span>
                    </button>
                </div>
            </header>

            <!-- PRINT-ONLY HEADER BANNER -->
            <div class="print-only-header p-6 border-b border-slate-300 text-center">
                <div class="text-xs uppercase tracking-widest font-extrabold text-slate-600">Republic of Ghana | Narcotics Control Commission</div>
                <h1 class="text-lg font-black text-slate-900 font-mono-code uppercase mt-1">Crime Scene Support Unit | Field Operations &amp; Case Audit Report</h1>
                <div class="text-[11px] text-slate-600 mt-1 font-mono-code">
                    Generated: {{ now()->format('d M Y, H:i') }} | Officer: {{ $authUser ? $authUser->fullname : 'System Generated' }} | Total Registered Cases: {{ $totalCasesCount }}
                </div>
            </div>

            <!-- SCROLLABLE PAGE BODY -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">

                <!-- ==================================================== -->
                <!-- 1. OPERATIONAL KPI METRIC TILES                      -->
                <!-- ==================================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <!-- Total Field Cases -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Field Cases Logged</span>
                            <span class="p-1.5 rounded-lg bg-blue-50 text-blue-700">
                                <i data-lucide="folder-git-2" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono-code">{{ number_format($totalCasesCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Dockets</span>
                        </div>
                        <span class="text-[10px] text-blue-600 font-bold mt-1">Crime Scene Unit Deployments</span>
                    </div>

                    <!-- Active Investigations -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Active Investigations</span>
                            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-amber-700 font-mono-code">{{ number_format($activeCasesCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">In Progress</span>
                        </div>
                        <span class="text-[10px] text-amber-600 font-bold mt-1">Action Needed / Underway</span>
                    </div>

                    <!-- Apprehended Suspects -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Suspects Apprehended</span>
                            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-emerald-700 font-mono-code">{{ number_format($totalSuspectsCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Persons</span>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Primary &amp; Associates</span>
                    </div>

                    <!-- Seized Exhibits -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Exhibits &amp; Seizures</span>
                            <span class="p-1.5 rounded-lg bg-purple-50 text-purple-700">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-purple-700 font-mono-code">{{ number_format($totalExhibitsCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Items Logged</span>
                        </div>
                        <span class="text-[10px] text-purple-600 font-bold mt-1">Drugs, Weapons &amp; Assets</span>
                    </div>

                    <!-- High Priority -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">High Priority</span>
                            <span class="p-1.5 rounded-lg bg-rose-50 text-rose-700">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-rose-700 font-mono-code">{{ number_format($criticalPriorityCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Dockets</span>
                        </div>
                        <span class="text-[10px] text-rose-600 font-bold mt-1">Priority Operations</span>
                    </div>

                </div>

                <!-- ==================================================== -->
                <!-- 2. INTERACTIVE FILTER & SEARCH CONSOLE               -->
                <!-- ==================================================== -->
                <div class="p-5 bg-white border border-slate-200/90 rounded-2xl shadow-xs space-y-4" id="reportFilterConsole">
                    
                    <form method="GET" action="{{ url('/Digital_Forensics/staff/report') }}" id="reportFilterForm" class="space-y-4">
                        <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div>
                                <h2 class="text-xs font-black text-slate-900 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="sliders" class="w-3.5 h-3.5 text-blue-600"></i>
                                    <span>Crime Scene Cases Audit Filter Parameters</span>
                                </h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Filter by case reference, operation code, scene location, arresting officer, suspect name, status, and date range.
                                </p>
                            </div>

                            <!-- PERIOD PRESET BUTTONS: Weekly, Monthly, Annual, General -->
                            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold font-mono-code shrink-0">
                                <button type="button" onclick="setReportPeriod('weekly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['weekly', 'week']) ? 'bg-white text-blue-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="weekly">
                                    Weekly
                                </button>
                                <button type="button" onclick="setReportPeriod('monthly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['monthly', 'month']) ? 'bg-white text-blue-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="monthly">
                                    Monthly
                                </button>
                                <button type="button" onclick="setReportPeriod('annual')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['annual', 'annually', 'year', 'yearly']) ? 'bg-white text-blue-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="annual">
                                    Annual
                                </button>
                                <button type="button" onclick="setReportPeriod('general')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ (in_array(strtolower($period ?? ''), ['all', 'general']) || empty($period)) ? 'bg-white text-blue-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="general">
                                    General
                                </button>
                            </div>
                        </div>

                        <!-- FILTER CONTROLS IN A UNIFIED ROW -->
                        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                            
                            <!-- CONTROLS: SEARCH + STATUS + OPERATION TYPE + PRIORITY + DATES -->
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end min-w-0">
                                
                                <!-- Search Bar -->
                                <div class="lg:col-span-3 space-y-1.5">
                                    <label for="searchInput" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Search Case / Suspect / Officer / Scene
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        </span>
                                        <input type="text" 
                                               name="search" 
                                               id="searchInput" 
                                               value="{{ $search }}" 
                                               placeholder="e.g. CSS-2026, Princilla, Airport, Raid..." 
                                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                    </div>
                                </div>

                                <!-- Status Dropdown -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="statusSelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Case Status
                                    </label>
                                    <select name="status" id="statusSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="ALL" {{ (empty($status) || strtoupper($status) === 'ALL') ? 'selected' : '' }}>All Statuses</option>
                                        <option value="Active Investigation" {{ strcasecmp($status ?? '', 'Active Investigation') === 0 ? 'selected' : '' }}>Active Investigation</option>
                                        <option value="Under Multi-Unit Processing" {{ strcasecmp($status ?? '', 'Under Multi-Unit Processing') === 0 ? 'selected' : '' }}>Under Multi-Unit Processing</option>
                                        <option value="Approved" {{ strcasecmp($status ?? '', 'Approved') === 0 ? 'selected' : '' }}>Approved</option>
                                        <option value="Draft" {{ strcasecmp($status ?? '', 'Draft') === 0 ? 'selected' : '' }}>Draft</option>
                                        <option value="Court Ready" {{ strcasecmp($status ?? '', 'Court Ready') === 0 ? 'selected' : '' }}>Court Ready</option>
                                    </select>
                                </div>

                                <!-- Operation / Event Type -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="operationTypeSelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Operation Type
                                    </label>
                                    <select name="operation_type" id="operationTypeSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="ALL" {{ (empty($operationType) || strtoupper($operationType) === 'ALL') ? 'selected' : '' }}>All Operations</option>
                                        <option value="Raid" {{ strcasecmp($operationType ?? '', 'Raid') === 0 ? 'selected' : '' }}>Raid Operation</option>
                                        <option value="Search Warrant" {{ strcasecmp($operationType ?? '', 'Search Warrant') === 0 ? 'selected' : '' }}>Search Warrant</option>
                                        <option value="Cargo" {{ strcasecmp($operationType ?? '', 'Cargo') === 0 ? 'selected' : '' }}>Cargo / Parcel Interception</option>
                                        <option value="Intel" {{ strcasecmp($operationType ?? '', 'Intel') === 0 ? 'selected' : '' }}>Intel-Led Operation</option>
                                        <option value="Arrest" {{ strcasecmp($operationType ?? '', 'Arrest') === 0 ? 'selected' : '' }}>Arrest &amp; Apprehension</option>
                                        <option value="Transferred" {{ strcasecmp($operationType ?? '', 'Transferred') === 0 ? 'selected' : '' }}>Transferred Case</option>
                                    </select>
                                </div>

                                <!-- Priority Filter -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="prioritySelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Priority
                                    </label>
                                    <select name="priority" id="prioritySelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="ALL" {{ (empty($priority) || strtoupper($priority) === 'ALL') ? 'selected' : '' }}>All Priorities</option>
                                        <option value="HIGH" {{ strtoupper($priority ?? '') === 'HIGH' ? 'selected' : '' }}>High</option>
                                        <option value="LOW" {{ strtoupper($priority ?? '') === 'LOW' ? 'selected' : '' }}>Low</option>
                                    </select>
                                </div>

                                <!-- Date Range: From -->
                                <div class="lg:col-span-1.5 space-y-1.5">
                                    <label for="fromDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        From:
                                    </label>
                                    <input type="date" 
                                           name="from_date" 
                                           id="fromDate" 
                                           value="{{ $fromDate }}" 
                                           class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                </div>

                                <!-- Date Range: To -->
                                <div class="lg:col-span-1.5 space-y-1.5">
                                    <label for="toDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        To:
                                    </label>
                                    <input type="date" 
                                           name="to_date" 
                                           id="toDate" 
                                           value="{{ $toDate }}" 
                                           class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                </div>

                            </div>

                            <!-- ACTION BUTTONS: Reset -->
                            <div class="flex items-center gap-2 shrink-0 pt-2 xl:pt-0">
                                <a href="{{ url('/Digital_Forensics/staff/report') }}" onclick="resetReportFilters(event)" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold font-mono-code uppercase tracking-wider flex items-center gap-1.5 transition-colors cursor-pointer" title="Clear all filters">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                    <span>Reset</span>
                                </a>
                            </div>

                        </div>

                        <!-- SECONDARY ROW: Sort & Items Per Page -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold text-slate-500 font-mono-code uppercase">Sort:</span>
                                    <select name="sort" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:bg-white focus:outline-none">
                                        <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest Incident / Creation First</option>
                                        <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                        <option value="case_asc" {{ ($sort ?? '') === 'case_asc' ? 'selected' : '' }}>Case Number (A–Z)</option>
                                        <option value="case_desc" {{ ($sort ?? '') === 'case_desc' ? 'selected' : '' }}>Case Number (Z–A)</option>
                                        <option value="priority" {{ ($sort ?? '') === 'priority' ? 'selected' : '' }}>Priority (High First)</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold text-slate-500 font-mono-code uppercase">Show:</span>
                                    <select name="per_page" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:bg-white focus:outline-none">
                                        <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / page</option>
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / page</option>
                                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / page</option>
                                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / page</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-[11px] font-mono-code font-bold text-slate-500" id="filteredCountInfo">
                                Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ number_format($totalFilteredCount) }} filtered cases (Cumulative: {{ number_format($totalCasesCount) }})
                            </div>
                        </div>

                    </form>

                </div>

                <!-- ==================================================== -->
                <!-- 3. FIELD CASES LOG TABLE                             -->
                <!-- ==================================================== -->
                <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                    
                    <div class="px-6 py-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div>
                            <h3 class="text-xs font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                Field Cases Log
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Official forensic record of all operations, scenes, suspects, and exhibit property generated by Crime Scene Support.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-mono-code font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                <span id="casesCountBadge">{{ $cases->total() }} Dockets Filed</span>
                            </span>
                        </div>
                    </div>

                    <!-- TABLE CONTENT CONTAINER (AJAX SKELETON REPLACED) -->
                    <div id="fieldCasesTableContainer">
                        @include('Crime_Scene_Support.partials.report_table')
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ========================================================== -->
    <!-- CRIME SCENE SUPPORT REPORT NOTES MODAL (POPOVER)          -->
    <!-- ========================================================== -->
    <div id="reportNotesModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto no-print">
        <div class="bg-white rounded-3xl max-w-4xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col my-auto min-h-[580px] sm:min-h-[660px] max-h-[94vh] animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Modal Header -->
            <div class="px-6 py-4.5 bg-white text-slate-900 flex items-center justify-between shrink-0 border-b border-slate-200">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center shrink-0">
                        <i data-lucide="file-signature" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold tracking-wide uppercase font-serif-header text-slate-900">Crime Scene Report Notes</h2>
                        </div>
                        <p class="text-[11px] text-slate-500 font-sans mt-0.5">
                            Specify operational status, recommendations, and conclusion before generating the official PDF dossier.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeReportNotesModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body with 3 textareas: STATUS, RECOMMENDATION, CONCLUSION -->
            <div class="p-6 sm:p-7 space-y-5 overflow-y-auto flex-1 bg-white text-slate-800 text-xs">
                
                <!-- STATUS -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="reportStatusInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-800 font-mono-code flex items-center gap-1.5">
                            <span>STATUS</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Operational state</span>
                    </div>
                    <textarea id="reportStatusInput" 
                              name="status"
                              rows="4" 
                              class="w-full min-h-[95px] bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500 transition-all placeholder:text-slate-400 font-sans leading-relaxed"
                              placeholder="Enter field operations and investigation status...">{{ $latestReportNote->status ?? '' }}</textarea>
                </div>

                <!-- RECOMMENDATION -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="reportRecommendationInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-800 font-mono-code flex items-center gap-1.5">
                            <span>RECOMMENDATION</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Inter-unit directives</span>
                    </div>
                    <textarea id="reportRecommendationInput" 
                              name="recommendation"
                              rows="4" 
                              class="w-full min-h-[95px] bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 transition-all placeholder:text-slate-400 font-sans leading-relaxed"
                              placeholder="Enter recommendations for prosecution, lab examination, or surveillance...">{{ $latestReportNote->recommendation ?? '' }}</textarea>
                </div>

                <!-- CONCLUSION -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="reportConclusionInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-800 font-mono-code flex items-center gap-1.5">
                            <span>CONCLUSION</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Executive summary</span>
                    </div>
                    <textarea id="reportConclusionInput" 
                              name="conclusion"
                              rows="4" 
                              class="w-full min-h-[95px] bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500 transition-all placeholder:text-slate-400 font-sans leading-relaxed"
                              placeholder="Enter final operational conclusion and case briefing summary...">{{ $latestReportNote->conclusion ?? '' }}</textarea>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <button type="button" 
                        onclick="proceedWithoutNotes()" 
                        class="px-4 py-2 text-slate-600 hover:text-slate-900 font-semibold text-xs rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                    Skip | Generate Without Notes
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            onclick="closeReportNotesModal()" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" 
                            id="submitNotesBtn"
                            onclick="saveReportNotesAndProceed()" 
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-500 active:scale-95 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-blue-700/20 cursor-pointer flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4" id="submitNotesIcon"></i>
                        <span id="submitNotesText">Save &amp; Export PDF</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Initialize Reusable AJAX Table Controller with Skeleton Loader
        function initFieldCasesTable() {
            if (typeof initAjaxTable !== 'function') return;

            window.fieldCasesAjaxTable = initAjaxTable({
                container: '#fieldCasesTableContainer',
                table: '#fieldCasesTable',
                form: '#reportFilterForm',
                endpoint: '{{ url("/Digital_Forensics/staff/report") }}',
                debounce: 300,
                columns: 6,
                rows: 6,
                onAfterUpdate: function (container, responseData) {
                    const meta = container.querySelector('#fieldCasesTableMeta');
                    if (meta) {
                        const total = meta.getAttribute('data-total');
                        const first = meta.getAttribute('data-first-item');
                        const last = meta.getAttribute('data-last-item');
                        const filtered = meta.getAttribute('data-filtered-count');
                        const cumulative = meta.getAttribute('data-cumulative-count');

                        const badge = document.getElementById('casesCountBadge');
                        if (badge && total !== null) {
                            badge.textContent = Number(total).toLocaleString() + ' Dockets Filed';
                        }

                        const filteredInfo = document.getElementById('filteredCountInfo');
                        if (filteredInfo && filtered !== null) {
                            filteredInfo.textContent = `Showing ${first}–${last} of ${Number(filtered).toLocaleString()} filtered cases (Cumulative: ${Number(cumulative).toLocaleString()})`;
                        }
                    }

                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        lucide.createIcons();
                    }
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFieldCasesTable);
        } else {
            initFieldCasesTable();
        }

        function setReportPeriod(period) {
            document.getElementById('periodInput').value = period;
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';

            document.querySelectorAll('.console-period-btn').forEach(btn => {
                if (btn.getAttribute('data-period') === period) {
                    btn.className = 'console-period-btn px-3 py-1 rounded-lg transition-all bg-white text-blue-800 shadow-xs';
                } else {
                    btn.className = 'console-period-btn px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900';
                }
            });

            if (window.fieldCasesAjaxTable) {
                window.fieldCasesAjaxTable.applyFilters();
            } else {
                document.getElementById('reportFilterForm').submit();
            }
        }

        function resetReportFilters(e) {
            if (e) e.preventDefault();
            const form = document.getElementById('reportFilterForm');
            if (form) {
                form.reset();
                document.getElementById('periodInput').value = 'general';
                document.getElementById('searchInput').value = '';
                document.getElementById('statusSelect').value = 'ALL';
                document.getElementById('operationTypeSelect').value = 'ALL';
                document.getElementById('prioritySelect').value = 'ALL';
                document.getElementById('fromDate').value = '';
                document.getElementById('toDate').value = '';
                const sortSelect = form.querySelector('select[name="sort"]');
                if (sortSelect) sortSelect.value = 'newest';
                const perPageSelect = form.querySelector('select[name="per_page"]');
                if (perPageSelect) perPageSelect.value = '20';
            }
            document.querySelectorAll('.console-period-btn').forEach(btn => {
                if (btn.getAttribute('data-period') === 'general') {
                    btn.className = 'console-period-btn px-3 py-1 rounded-lg transition-all bg-white text-blue-800 shadow-xs';
                } else {
                    btn.className = 'console-period-btn px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900';
                }
            });
            if (window.fieldCasesAjaxTable) {
                window.fieldCasesAjaxTable.applyFilters();
            } else {
                window.location.href = '{{ url("/Digital_Forensics/staff/report") }}';
            }
        }

        function openReportNotesModal() {
            const modal = document.getElementById('reportNotesModal');
            if (modal) {
                modal.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            }
        }

        function closeReportNotesModal() {
            const modal = document.getElementById('reportNotesModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function buildBasePdfParams() {
            const params = new URLSearchParams();
            const period = document.getElementById('periodInput')?.value;
            const search = document.getElementById('searchInput')?.value;
            const status = document.getElementById('statusSelect')?.value;
            const opType = document.getElementById('operationTypeSelect')?.value;
            const priority = document.getElementById('prioritySelect')?.value;
            const from = document.getElementById('fromDate')?.value;
            const to = document.getElementById('toDate')?.value;

            if (period && period !== 'general' && period !== 'all') params.set('period', period);
            if (search) params.set('search', search);
            if (status && status !== 'ALL') params.set('status', status);
            if (opType && opType !== 'ALL') params.set('operation_type', opType);
            if (priority && priority !== 'ALL') params.set('priority', priority);
            if (from) params.set('from_date', from);
            if (to) params.set('to_date', to);

            return params;
        }

        function proceedWithoutNotes() {
            closeReportNotesModal();
            const params = buildBasePdfParams();
            window.open('{{ url("/Crime_Scene_Support/report_pdf") }}?' + params.toString(), '_blank');
        }

        async function saveReportNotesAndProceed() {
            const btn = document.getElementById('submitNotesBtn');
            const btnText = document.getElementById('submitNotesText');
            const originalText = btnText ? btnText.innerText : 'Save & Export PDF';

            const statusVal = document.getElementById('reportStatusInput')?.value?.trim() || '';
            const recommendationVal = document.getElementById('reportRecommendationInput')?.value?.trim() || '';
            const conclusionVal = document.getElementById('reportConclusionInput')?.value?.trim() || '';

            const period = document.getElementById('periodInput')?.value || 'general';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const search = document.getElementById('searchInput')?.value || '';
            const priority = document.getElementById('prioritySelect')?.value || '';
            const actionStatus = document.getElementById('statusSelect')?.value || '';

            let batchId = null;

            try {
                if (btnText) btnText.innerText = 'Saving Details...';
                if (btn) btn.disabled = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '{{ csrf_token() }}';

                const response = await fetch('{{ url("/Crime_Scene_Support/report/notes") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        status: statusVal,
                        recommendation: recommendationVal,
                        conclusion: conclusionVal,
                        period: period,
                        from_date: from,
                        to_date: to,
                        search: search,
                        priority: priority,
                        status_filter: actionStatus,
                        total_cases_count: {{ $totalCasesCount ?? 0 }}
                    })
                });

                const data = await response.json();
                if (data && data.success && data.batch_id) {
                    batchId = data.batch_id;
                }
            } catch (err) {
                console.error('Error saving crime scene report details:', err);
            } finally {
                if (btnText) btnText.innerText = originalText;
                if (btn) btn.disabled = false;
            }

            closeReportNotesModal();
            const params = buildBasePdfParams();
            if (batchId) {
                params.set('batch_id', batchId);
            }
            window.open('{{ url("/Crime_Scene_Support/report_pdf") }}?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>

