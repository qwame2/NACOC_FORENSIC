@php
    $authUser = auth()->user();
    $userUnit = 'Evidence Management';
    $sidebarUnit = 'Evidence Management';
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Evidence%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $dbUnitHead ? $dbUnitHead->fullname : 'Chief Evidence Custodian';
    $officerName = $authUser->fullname ?? session('user_name') ?? 'Evidence Vault Officer';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NACOC EXHIBIT LOGS | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif
    <!-- Lucide Icons -->
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>
    <!-- QRCode Generator for Seized Property Badge Tags -->
    <script src="{{ asset('vendor/qrcode.min.js') }}"></script>

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f1f5f9; color: #0f172a; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .badge-category-drug { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-category-weapon { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .badge-category-vehicle { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-category-device { background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
        .badge-category-money { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-category-other { background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .vault-stats-horizontal-row {
            display: grid !important;
            grid-template-columns: repeat(7, minmax(120px, 1fr)) !important;
            gap: 10px !important;
            width: 100% !important;
            align-items: stretch !important;
        }
        .exhibits-grid-3cols {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
        }
        @media (max-width: 768px) {
            .exhibits-grid-3cols {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 520px) {
            .exhibits-grid-3cols {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased flex flex-col">

    <div class="flex h-screen overflow-hidden">
        
        <!-- ========================================== -->
        <!-- 1. UNIVERSAL COMMAND ASIDE BAR            -->
        <!-- ========================================== -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- ========================================== -->
        <!-- 2. MAIN VAULT WORKSPACE                    -->
        <!-- ========================================== -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            
            <!-- TOP WORKSPACE BAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs z-20">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-emerald-600 border border-slate-200/90 shadow-md shadow-slate-200/60">
                        <i data-lucide="archive" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-sm font-extrabold text-slate-900 tracking-tight uppercase">EXHIBIT LOG</h1>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">
                            NACOC Evidence Logs &amp; Associated Suspects Registry
                        </p>
                    </div>
                </div>

                <!-- Right Toolbar Actions -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 pl-1">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr($officerName, 0, 2)) }}
                        </div>
                        <span class="text-xs font-bold text-slate-700 hidden md:inline truncate max-w-[120px]">{{ $officerName }}</span>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE BODY -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scrollbar">

                @include('partials.flash')

                <!-- ==================================================== -->
                <!-- 3. TOP TELEMETRY STATS CARDS (HORIZONTAL ROW)       -->
                <!-- ==================================================== -->
                <div class="w-full overflow-x-auto pb-2 custom-scrollbar" style="overflow-x: auto; width: 100%;">
                    <div class="vault-stats-horizontal-row" style="display: grid !important; grid-template-columns: repeat(7, minmax(120px, 1fr)) !important; gap: 10px !important; width: 100% !important; align-items: stretch !important;">
                        
                        <!-- TOTAL EXHIBITS -->
                        <div onclick="selectCategory('all'); selectActionStatus('all');" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-emerald-300 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to view all vault exhibits">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-emerald-700 transition-colors truncate">All Exhibits</span>
                                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="box" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-slate-900 font-mono-code leading-none">{{ number_format($totalExhibitsCount) }}</div>
                                <div class="text-[10px] text-emerald-600 font-semibold mt-1 truncate">Active Vault Items</div>
                            </div>
                        </div>

                        <!-- NEEDS QR / CLASSIFY (ALERT CARD) -->
                        <div onclick="selectActionStatus('pending');" class="bg-white rounded-2xl p-3.5 border {{ $pendingCount > 0 ? 'border-amber-300 bg-amber-50/20 shadow-amber-500/5' : 'border-slate-200' }} shadow-2xs hover:border-amber-400 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to filter exhibits pending QR code or classification">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 group-hover:text-amber-900 transition-colors truncate">Pending Intake</span>
                                <div class="w-6 h-6 rounded-lg {{ $pendingCount > 0 ? 'bg-amber-100 text-amber-700 animate-pulse' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center shrink-0">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-amber-700 font-mono-code leading-none">{{ number_format($pendingCount) }}</div>
                                <div class="text-[10px] text-amber-700 font-semibold mt-1 truncate">Needs QR / Classify</div>
                            </div>
                        </div>

                        <!-- TOTAL ESTIMATED VALUE -->
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-emerald-300 transition-colors flex flex-col justify-between min-w-0" style="min-width: 0;" title="Estimated Seizure Value: GH₵ {{ number_format($totalSeizureValue, 2) }}">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider truncate">Total Value</span>
                                <div class="w-6 h-6 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-base sm:text-lg font-extrabold text-slate-900 font-mono-code truncate leading-none" title="GH₵ {{ number_format($totalSeizureValue, 2) }}">
                                    GH₵ {{ number_format($totalSeizureValue, 0) }}
                                </div>
                                <div class="text-[10px] text-teal-600 font-semibold mt-1 truncate">Estimated Value</div>
                            </div>
                        </div>

                        <!-- DRUGS -->
                        <div onclick="selectCategory('drug');" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-emerald-300 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to filter narcotics">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-emerald-700 transition-colors truncate">Narcotics</span>
                                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="pill" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-emerald-700 font-mono-code leading-none">{{ $drugsCount }}</div>
                                <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Drug Consignments</div>
                            </div>
                        </div>

                        <!-- WEAPONS -->
                        <div onclick="selectCategory('weapon');" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-amber-300 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to filter weapons">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-amber-700 transition-colors truncate">Weapons</span>
                                <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-amber-700 font-mono-code leading-none">{{ $weaponsCount }}</div>
                                <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Guns &amp; Ammunition</div>
                            </div>
                        </div>

                        <!-- VEHICLES -->
                        <div onclick="selectCategory('vehicle');" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-blue-300 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to filter vehicles">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-blue-700 transition-colors truncate">Vehicles</span>
                                <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-blue-700 font-mono-code leading-none">{{ $vehiclesCount }}</div>
                                <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Cars &amp; Transports</div>
                            </div>
                        </div>

                        <!-- DIGITAL & ELECTRONICS -->
                        <div onclick="selectCategory('device');" class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs hover:border-purple-300 hover:shadow-xs transition-all cursor-pointer group flex flex-col justify-between min-w-0" style="min-width: 0;" title="Click to filter electronics">
                            <div class="flex items-center justify-between text-slate-500 mb-1 gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-purple-700 transition-colors truncate">Electronics</span>
                                <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold text-purple-700 font-mono-code leading-none">{{ $devicesCount }}</div>
                                <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Phones, PCs &amp; Media</div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- 4. ADVANCED FILTER & SEARCH CONTROL BAR              -->
                <!-- ==================================================== -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-3">
                    <form method="GET" action="{{ url('/Evidence_Management/vault') }}" id="vaultFilterForm" class="space-y-3" onsubmit="event.preventDefault(); fetchVaultContent();">
                        <input type="hidden" name="view" id="viewModeInput" value="{{ $viewMode }}">
                        <input type="hidden" name="category" id="categoryInput" value="{{ $category }}">

                        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                            
                            <!-- Search Bar -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       id="vaultSearchInput" 
                                       value="{{ $search }}" 
                                       placeholder="Search exhibit #, property ID, commodity, suspect name, case #, serial #, reg #, officer..." 
                                       oninput="handleSearchInput(this.value)"
                                       autocomplete="off"
                                       class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all font-mono-code">
                                <button type="button" id="vaultClearSearchBtn" onclick="clearSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 {{ $search ? '' : 'hidden' }}">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Dropdown Filters & Actions -->
                            <div class="flex items-center space-x-2 shrink-0 flex-wrap gap-y-2">
                                
                                <!-- Intake / Action Status Filter Dropdown -->
                                <select name="action_status" id="actionStatusSelect" onchange="fetchVaultContent()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500 {{ $actionStatus === 'pending' ? 'border-amber-400 text-amber-900 bg-amber-50/50 font-bold' : '' }}">
                                    <option value="all" {{ $actionStatus === 'all' ? 'selected' : '' }}>🏷️ All Vault Statuses</option>
                                    <option value="pending" {{ $actionStatus === 'pending' ? 'selected' : '' }}>⚠️ Needs QR / Classify ({{ $pendingCount }})</option>
                                    <option value="unassigned_qr" {{ $actionStatus === 'unassigned_qr' ? 'selected' : '' }}>🏷️ Unassigned QR Tag ({{ $unassignedQrCount }})</option>
                                    <option value="unclassified" {{ $actionStatus === 'unclassified' ? 'selected' : '' }}>📋 Unclassified Exhibits ({{ $unclassifiedCount }})</option>
                                    <option value="completed" {{ $actionStatus === 'completed' ? 'selected' : '' }}>✓ Tagged &amp; Classified ({{ $completedCount }})</option>
                                </select>

                               

                                <!-- Items Per Page -->
                                <select name="per_page" id="perPageSelect" onchange="fetchVaultContent()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                                    <option value="12" {{ request('per_page') == 12 ? 'selected' : '' }}>12 per page</option>
                                    <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24 per page</option>
                                    <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48 per page</option>
                                </select>

                                <!-- View Mode Switcher -->
                                <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200">
                                    <button type="button" id="gridModeBtn" onclick="switchView('grid')" class="px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 {{ $viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}" title="Card Grid View">
                                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                                        <span class="hidden sm:inline">Cards</span>
                                    </button>
                                    <button type="button" id="tableModeBtn" onclick="switchView('table')" class="px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 {{ $viewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}" title="Data Table View">
                                        <i data-lucide="table" class="w-3.5 h-3.5"></i>
                                        <span class="hidden sm:inline">Table</span>
                                    </button>
                                </div>

                                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-1">
                                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                                    <span>Filter</span>
                                </button>
                                
                                <button type="button" id="resetFiltersBtn" onclick="resetVaultFilters()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition-colors {{ ($search || $category !== 'all' || $actionStatus !== 'all' || $sort !== 'newest') ? '' : 'hidden' }}" title="Reset all filters">
                                    Reset
                                </button>

                            </div>
                        </div>

                        <!-- Category Filter Pills -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 pt-1 custom-scrollbar text-xs font-semibold" id="categoryPillsContainer">
                            <span class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider shrink-0 mr-1 flex items-center">
                                <i data-lucide="layers" class="w-3 h-3 mr-1 text-slate-400"></i> Category:
                            </span>
                            <button type="button" data-cat="all" onclick="selectCategory('all')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ $category === 'all' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <span>All Categories</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ $category === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }} font-mono-code font-bold" data-metric="total">{{ $totalExhibitsCount }}</span>
                            </button>
                            <button type="button" data-cat="drug" onclick="selectCategory('drug')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'drug') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                <span>Drugs &amp; Narcotics</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ str_contains(strtolower($category), 'drug') ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800' }} font-mono-code font-bold" data-metric="drugs">{{ $drugsCount }}</span>
                            </button>
                            <button type="button" data-cat="weapon" onclick="selectCategory('weapon')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'weapon') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="crosshair" class="w-3 h-3 text-amber-600"></i>
                                <span>Weapons &amp; Firearms</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ str_contains(strtolower($category), 'weapon') ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800' }} font-mono-code font-bold" data-metric="weapons">{{ $weaponsCount }}</span>
                            </button>
                            <button type="button" data-cat="vehicle" onclick="selectCategory('vehicle')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'vehicle') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="truck" class="w-3 h-3 text-blue-600"></i>
                                <span>Vehicles &amp; Transport</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ str_contains(strtolower($category), 'vehicle') ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-800' }} font-mono-code font-bold" data-metric="vehicles">{{ $vehiclesCount }}</span>
                            </button>
                            <button type="button" data-cat="device" onclick="selectCategory('device')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'device') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="smartphone" class="w-3 h-3 text-purple-600"></i>
                                <span>Digital Devices</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ str_contains(strtolower($category), 'device') ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-800' }} font-mono-code font-bold" data-metric="devices">{{ $devicesCount }}</span>
                            </button>
                            <button type="button" data-cat="money" onclick="selectCategory('money')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'money') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="coins" class="w-3 h-3 text-teal-600"></i>
                                <span>Currency / Cash</span>
                                <span class="category-count-badge text-[10px] px-1.5 py-0.2 rounded-full {{ str_contains(strtolower($category), 'money') ? 'bg-white/20 text-white' : 'bg-teal-100 text-teal-800' }} font-mono-code font-bold" data-metric="currency">{{ $currencyCount }}</span>
                            </button>
                            <button type="button" data-cat="general" onclick="selectCategory('general')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 {{ str_contains(strtolower($category), 'general') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                <i data-lucide="package" class="w-3 h-3 text-slate-600"></i>
                                <span>General</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- 5. EXHIBIT LOG CONTENT SECTION (AJAX + SKELETON)   -->
                <!-- ==================================================== -->
                <div id="vaultContentWrapper" class="relative min-h-[450px]">
                    <!-- Skeleton Loader State (shown during AJAX updates) -->
                    <div id="vaultSkeletonContainer" class="hidden transition-opacity duration-200">
                        <!-- Skeleton Grid View -->
                        <div id="skeletonGridView" class="{{ $viewMode === 'table' ? 'hidden' : 'grid' }} exhibits-grid-3cols gap-5 animate-pulse" style="display: {{ $viewMode === 'table' ? 'none' : 'grid' }} !important; grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 1.25rem !important; width: 100% !important;">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col">
                                    <div class="h-48 bg-slate-200/80 relative">
                                        <div class="absolute top-3 left-3 w-24 h-6 bg-slate-300 rounded-lg"></div>
                                        <div class="absolute top-3 right-3 w-20 h-6 bg-slate-300 rounded-full"></div>
                                    </div>
                                    <div class="p-5 space-y-3.5 flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="w-24 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-16 h-4 bg-slate-200 rounded"></div>
                                        </div>
                                        <div class="w-4/5 h-5 bg-slate-200 rounded"></div>
                                        <div class="w-3/5 h-4 bg-slate-200 rounded"></div>
                                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                            <div class="w-24 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-24 h-4 bg-slate-200 rounded"></div>
                                        </div>
                                    </div>
                                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                        <div class="w-24 h-8 bg-slate-200 rounded-xl"></div>
                                        <div class="w-24 h-8 bg-slate-200 rounded-xl"></div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <!-- Skeleton Table View -->
                        <div id="skeletonTableView" class="{{ $viewMode === 'table' ? 'block' : 'hidden' }} bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden animate-pulse">
                            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <div class="w-32 h-4 bg-slate-200 rounded"></div>
                                <div class="w-24 h-4 bg-slate-200 rounded"></div>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @for ($i = 0; $i < 6; $i++)
                                    <div class="p-4 flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-slate-200 rounded-2xl shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="w-1/3 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-1/4 h-3 bg-slate-200 rounded"></div>
                                        </div>
                                        <div class="w-28 h-4 bg-slate-200 rounded shrink-0 hidden sm:block"></div>
                                        <div class="w-24 h-6 bg-slate-200 rounded-full shrink-0"></div>
                                        <div class="w-24 h-8 bg-slate-200 rounded-xl shrink-0"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <!-- Live Content Container -->
                    <div id="vaultExhibitsContent">
                        @include('Evidence_Management.partials.vault_content')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 7. EXPANDED FULL-DETAIL EXHIBIT INSPECTION MODAL           -->
    <!-- ========================================================== -->
    <div id="exhibitModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-7xl xl:max-w-[94vw] 2xl:max-w-[96vw] w-full h-[96vh] border border-slate-200/90 shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-150">
            
            <!-- Modal Header (Sticky) -->
            <div class="px-5 sm:px-6 py-4 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white flex items-center justify-between shrink-0 border-b border-slate-800 shadow-sm">
                <div class="flex items-center space-x-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0 shadow-inner">
                        <i data-lucide="archive" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                            <h2 class="text-base font-black tracking-tight text-white truncate max-w-md" id="modalExhibitTitle">Exhibit Log</h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-mono-code font-bold uppercase border border-emerald-500/30" id="modalCategoryBadge">
                                General
                            </span>
                            <span class="text-[11px] font-mono-code font-extrabold text-emerald-400 bg-emerald-950/80 border border-emerald-500/40 px-2.5 py-0.5 rounded-md" id="modalExhibitNumber">
                                EXH-0000
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 mt-1 text-xs text-slate-400 font-mono-code">
                            <span class="text-blue-400 font-bold" id="modalCaseSubtitle">Case: Unassigned</span>
                            <span>•</span>
                            <span class="text-slate-400">NACOC National Evidence Vault</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <button type="button" onclick="closeExhibitModal()" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition-colors flex items-center justify-center cursor-pointer" title="Close Inspection">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Scrollable Master Workspace) -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6 custom-scrollbar bg-slate-50/60">
                
                <!-- TOP STATS METRIC BANNER (4 Cards) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-colors">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider font-mono-code">Quantity &amp; Weight</span>
                            <i data-lucide="scale" class="w-3.5 h-3.5 text-slate-400"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 font-mono-code block truncate" id="modalQuantity">N/A</span>
                        <span class="text-[11px] text-slate-500 truncate block mt-0.5 font-medium" id="modalPackaging">N/A</span>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-colors">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider font-mono-code">Estimated Valuation</span>
                            <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5 text-emerald-500"></i>
                        </div>
                        <span class="text-sm sm:text-base font-black text-emerald-700 font-mono-code block truncate" id="modalValue">N/A</span>
                        <span class="text-[11px] text-slate-500 block mt-0.5 font-medium">Valuation on Seizure</span>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-colors">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider font-mono-code">Seizure Date &amp; Time</span>
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-500"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 font-mono-code block truncate" id="modalDateTime">N/A</span>
                        <span class="text-[11px] text-slate-500 truncate block mt-0.5 font-medium" id="modalLocationQuick">N/A</span>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-colors">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider font-mono-code">Seizing Officer &amp; Source</span>
                            <i data-lucide="shield" class="w-3.5 h-3.5 text-purple-500"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 truncate block" id="modalOfficer">N/A</span>
                        <span class="text-[11px] text-emerald-700 font-semibold truncate block mt-0.5" id="modalSource">N/A</span>
                    </div>
                </div>

                <!-- MASTER TWO-COLUMN INSPECTION GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT COLUMN (7 Cols): Exhibit Particulars, Technical Specs, Photos & Remarks -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- 1. Seizure & Custody Particulars -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Seizure &amp; Custody Details</h4>
                                </div>
                                <span class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Dossier File</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-700">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Case Reference</span>
                                    <span class="font-extrabold text-blue-600 font-mono-code text-xs mt-0.5 block" id="modalCaseNumberDetail">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Commodity / Item</span>
                                    <span class="font-extrabold text-slate-900 text-xs mt-0.5 block" id="modalCommodityName">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Exhibit Type</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalExhibitType">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Seizure Location</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalLocation">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Concealment Method</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalConcealment">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Seizure Reason</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalReason">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Investigating Officer</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalInvestigatingOfficer">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold font-mono-code block">Source / Requesting Unit</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalSourceRequestingUnit">N/A</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Category-Specific Forensic Attributes -->
                        <div id="modalCategorySpecsContainer" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center space-x-2.5 border-b border-slate-100 pb-3">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="cpu" class="w-4 h-4"></i>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider" id="modalCategorySpecsTitle">
                                    Category Technical Specifications
                                </h4>
                            </div>
                            <div id="modalCategorySpecsBody" class="text-xs space-y-2">
                                <!-- Populated dynamically based on category -->
                            </div>
                        </div>

                        <!-- 3. Exhibit Evidence Photographs & Media Gallery -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </div>
                                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">
                                        Exhibit Evidence Photographs &amp; Media Gallery
                                    </h3>
                                </div>
                                <span class="text-[11px] font-mono-code font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200" id="modalPhotosCountBadge">
                                    0 Photos
                                </span>
                            </div>

                            <div id="modalPhotosGallery" class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>

                        <!-- 4. Remarks & Custody Notes -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center space-x-2.5 border-b border-slate-100 pb-3">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Remarks &amp; Custody Notes</h4>
                            </div>

                            <!-- Exhibit Remark -->
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 font-mono-code block">Exhibit Lodgement Remark:</span>
                                <div class="bg-amber-50/70 p-3.5 rounded-2xl border border-amber-200/90 text-xs text-slate-800 italic leading-relaxed" id="modalRemark">
                                    No remark recorded.
                                </div>
                            </div>

                            <!-- Lab Remark (if any) -->
                            <div id="modalLabRemarkContainer" class="space-y-1 hidden">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800 font-mono-code block">Laboratory Analyst Remark:</span>
                                <div class="bg-teal-50/70 p-3.5 rounded-2xl border border-teal-200/90 text-xs text-slate-800 italic leading-relaxed" id="modalLabRemark">
                                    No laboratory remarks.
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN (5 Cols): Official QR Badge, Custodial Management & Suspects -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- 1. SEIZED PROPERTY BADGE SPECIFICATION PREVIEW CARD -->
                        <div id="inspectBadgePreviewCard" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-4">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3.5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0 shadow-2xs">
                                        <i data-lucide="qr-code" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">
                                                Seized Property Badge Specification Preview
                                            </h3>
                                        </div>
                                        <span id="inspectBadgeStatusPill" class="text-[9.5px] font-mono-code font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-block mt-1">
                                            Officially Tagged
                                        </span>
                                    </div>
                                </div>

                                <button type="button" 
                                        onclick="openInspectLivePortal()" 
                                        class="px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-[11px] border border-slate-200 flex items-center space-x-1 transition-colors cursor-pointer shrink-0" 
                                        title="Open public scan verification page">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-blue-600"></i>
                                    <span>Live Portal</span>
                                </button>
                            </div>

                            <!-- Badge Preview Frame -->
                            <div class="bg-slate-100/90 p-3 sm:p-4 rounded-2xl border border-slate-200 flex flex-col items-center justify-center overflow-hidden">
                                <div class="w-full aspect-[780/330] bg-white rounded-xl shadow-sm border border-slate-300 relative flex items-center justify-center overflow-hidden transition-all duration-200 group">
                                    <canvas id="inspectBadgeCanvas" width="780" height="330" class="w-full h-full object-contain cursor-zoom-in" onclick="openInspectBadgeLightbox()" title="Click to enlarge badge preview"></canvas>
                                    <img id="inspectBadgeImg" src="" class="w-full h-full object-contain cursor-zoom-in hidden" alt="Seized Property Badge Specification" onclick="openInspectBadgeLightbox()" title="Click to enlarge badge preview">
                                    
                                    <!-- Hover zoom hint overlay -->
                                    <div class="absolute bottom-2 right-2 px-2 py-1 bg-slate-900/80 text-white text-[9px] font-semibold rounded-md backdrop-blur-xs flex items-center space-x-1 pointer-events-none opacity-80 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="zoom-in" class="w-3 h-3"></i>
                                        <span>Click to enlarge</span>
                                    </div>
                                </div>
                                <div class="mt-2 text-center text-[10px] text-slate-500 font-mono-code" id="inspectBadgeNoticeText">
                                    Official QR Code • Scans to display instant exhibit details
                                </div>
                            </div>

                            <!-- Action Buttons Toolbar -->
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <button type="button" 
                                        id="inspectOpenQrModalBtn"
                                        onclick="if(window.currentModalExhibit) openVaultQrModal(window.currentModalExhibit)" 
                                        class="col-span-2 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center space-x-1.5 shadow-sm transition-all cursor-pointer"
                                        title="Open Official Seized Property Tag Modal">
                                    <i data-lucide="tag" class="w-4 h-4 text-white"></i>
                                    <span id="inspectOpenQrModalBtnText">Assign / View QR Tag</span>
                                </button>

                                <button type="button" 
                                        id="inspectDownloadQrBtn"
                                        onclick="downloadInspectQrCodePNG()" 
                                        class="px-3 py-2 rounded-xl bg-white hover:bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-300 shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer"
                                        title="Download Standalone QR Code PNG">
                                    <i data-lucide="qr-code" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Save QR</span>
                                </button>

                                <button type="button" 
                                        id="inspectDownloadBadgeBtn"
                                        onclick="downloadInspectBadgePNG()" 
                                        class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 shadow-2xs flex items-center justify-center space-x-1.5 transition-colors cursor-pointer"
                                        title="Download High-Res Seized Property Badge PNG">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Save Badge</span>
                                </button>

                                <button type="button" 
                                        id="inspectClassifyBtn"
                                        onclick="if(window.currentModalExhibit) openVaultClassifyModal(window.currentModalExhibit)" 
                                        class="col-span-2 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs border border-slate-200 flex items-center justify-center space-x-1.5 transition-all cursor-pointer"
                                        title="Classify Drug Schedule & Exhibit Type">
                                    <i data-lucide="sliders" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Classify &amp; Set Type</span>
                                </button>
                            </div>
                        </div>

                        <!-- 2. UNASSIGNED QR TAG NOTICE (WHEN NO QR ASSIGNED YET) -->
                        <div id="inspectBadgeUnassignedCard" class="hidden bg-white rounded-3xl p-5 border border-dashed border-amber-300 bg-gradient-to-r from-amber-50/40 via-white to-amber-50/20 shadow-2xs space-y-4">
                            <div class="flex items-start space-x-3.5">
                                <div class="w-10 h-10 rounded-2xl bg-amber-100/80 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 shadow-2xs">
                                    <i data-lucide="qr-code" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">
                                            Tag Not Assigned
                                        </h3>
                                        <span class="text-[9.5px] font-mono-code font-bold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300">
                                            Pending QR
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        This exhibit does not have an official QR tag yet. Assign a tag to generate the sealed badge preview.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 pt-1">
                                <button type="button" 
                                        id="inspectUnassignedAssignQrBtn"
                                        onclick="if(window.currentModalExhibit) openVaultQrModal(window.currentModalExhibit)" 
                                        class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center space-x-1.5 shadow-sm transition-all cursor-pointer"
                                        title="Assign Official Seized Property QR Tag">
                                    <i data-lucide="tag" class="w-4 h-4 text-white"></i>
                                    <span>Assign QR Tag</span>
                                </button>

                                <button type="button" 
                                        id="inspectUnassignedClassifyBtn"
                                        onclick="if(window.currentModalExhibit) openVaultClassifyModal(window.currentModalExhibit)" 
                                        class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-xs border border-slate-200 shadow-2xs flex items-center space-x-1.5 transition-all cursor-pointer"
                                        title="Classify Drug Schedule & Exhibit Type">
                                    <i data-lucide="sliders" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Classify</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. OFFICIAL CUSTODIAL STATE DISPLAY -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Vault Custodial State</h4>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold uppercase text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200" id="inspectCustodialStatusLabel">
                                    Seized (Active Vault Custody)
                                </span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-600 flex items-center space-x-2.5">
                                <i data-lucide="lock" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                <span class="leading-relaxed">Official Seizure custody is locked and secured under NACOC Evidence Management protocols. Status is automatically synchronized across the central registry and live scan verification.</span>
                            </div>

                            <!-- Chain of Custody Input & Custody Trail -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                        <i data-lucide="link" class="w-3.5 h-3.5 text-teal-600"></i>
                                        <span>Chain of Custody</span>
                                    </label>
                                    <span class="text-[9.5px] font-mono-code font-bold uppercase text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full border border-teal-200">
                                        CUSTODY TRANSFER
                                    </span>
                                </div>

                                <!-- HORIZONTAL LINE CHAIN OF CUSTODY WITH EXCHANGE ICON -->
                                <div class="flex items-center gap-2 sm:gap-2.5 w-full">
                                    <!-- CURRENT LOCATION (FROM) -->
                                    <div class="relative flex-1 min-w-0 flex items-center group">
                                        <span class="absolute left-3 text-slate-400 group-focus-within:text-teal-600 transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </span>
                                        <input type="text" id="inspectCustodyFrom"
                                            class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                                            value="Evidence Vault"
                                            placeholder="Current Location (e.g. Evidence Vault)"
                                            oninput="updateInspectChainOfCustodyValue(this)">
                                    </div>

                                    <!-- EXCHANGE ICON -->
                                    <div class="flex items-center justify-center p-2.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 shadow-2xs shrink-0 self-center" title="Custody Transfer / Exchange">
                                        <svg class="w-4 h-4 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                    </div>

                                    <!-- DESTINATION / TRANSFER TO -->
                                    <div class="relative flex-1 min-w-0 flex items-center group">
                                        <span class="absolute left-3 text-slate-400 group-focus-within:text-teal-600 transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </span>
                                        <input type="text" id="inspectCustodyTo"
                                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 placeholder:text-slate-400"
                                            placeholder="Transfer To (e.g. Central Vault, Forensic Lab, Court...)"
                                            oninput="updateInspectChainOfCustodyValue(this)">
                                    </div>
                                </div>

                                <!-- Hidden textarea synchronized with the inputs -->
                                <textarea id="inspectChainOfCustody" class="hidden">Evidence Vault</textarea>

                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[10px] text-slate-400 italic">Saved directly to central exhibit registry</span>
                                    <button type="button" 
                                            id="inspectSaveChainOfCustodyBtn" 
                                            onclick="saveInspectChainOfCustody()" 
                                            class="px-3.5 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-2xs hover:shadow transition-all cursor-pointer">
                                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                        <span>Save Chain of Custody</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Associated Suspect(s) Profile -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-2xs space-y-3.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Associated Suspect(s) Profile</h4>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200" id="modalSuspectsCountBadge">0 Suspect(s)</span>
                                    <span class="text-[11px] font-mono-code font-bold text-emerald-700" id="modalCaseLinkContainer"></span>
                                </div>
                            </div>

                            <div id="modalSuspectsList" class="space-y-3">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Modal Footer (Sticky) -->
            <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between shrink-0 shadow-xs">
                <div class="flex items-center space-x-2 text-[11px] text-slate-500 font-mono-code hidden sm:flex">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>National Evidence Vault Directorate • NACOC Central Custody</span>
                </div>
                <div class="flex items-center space-x-3 ml-auto">
                    <button type="button" onclick="closeExhibitModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                        Close
                    </button>
                    <a href="#" id="modalOpenCaseBtn" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-2 cursor-pointer no-underline">
                        <i data-lucide="folder-open" class="w-4 h-4 text-white"></i>
                        <span>Open Case File</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 8. HIGH-RESOLUTION IMAGE LIGHTBOX ZOOM MODAL               -->
    <!-- ========================================================== -->
    <div id="imageLightboxModal" class="fixed inset-0 z-60 hidden bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center">
            <button type="button" onclick="closeImageLightbox()" class="absolute -top-12 right-0 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img id="lightboxImage" src="" alt="Enlarged Preview" class="max-w-full max-h-[80vh] rounded-2xl object-contain shadow-2xl border border-white/20">
            <div id="lightboxCaption" class="mt-3 text-xs text-slate-300 font-mono-code text-center"></div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 9. INTERACTIVE SEIZED PROPERTY QR CODE TAG MODAL           -->
    <!-- ========================================================== -->
    <div id="vaultQrModal" class="fixed inset-0 z-55 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-4xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="qr-code" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h2 class="text-base font-extrabold tracking-tight text-white">Official Seized Property Tag</h2>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-mono-code font-bold uppercase border border-emerald-500/30">
                                NACOC Specification
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5" id="vaultQrModalSubtitle">Generate, preview, download, and assign QR tag to exhibit</p>
                    </div>
                </div>
                <button type="button" onclick="closeVaultQrModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto max-h-[80vh] custom-scrollbar bg-slate-50/50">
                <!-- Sealed / Locked Alert Banner when QR Tag is already generated -->
                <div id="vaultQrLockedAlert" class="hidden p-3.5 bg-emerald-50/90 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs text-emerald-900 shadow-2xs">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-sm shadow-slate-200/50">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="font-extrabold uppercase tracking-wider text-[10px] text-emerald-700 block">Officially Tagged &amp; Sealed</span>
                            <span class="text-slate-700 text-xs">This exhibit has already been officially assigned Seized Property Badge with Property ID <strong id="vaultQrLockedPropId" class="font-mono-code text-slate-900 font-bold">--</strong>. Specifications are locked and tamper-evident.</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold uppercase border border-emerald-300 shrink-0 ml-2">
                        Sealed
                    </span>
                </div>

                <!-- Hidden Custodial Status Input (Locked to Seized) -->
                <input type="hidden" id="vaultQrStatus" value="Seized">

                <!-- Controls Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Property ID input with generate button -->
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Property ID</label>
                            <span id="vaultQrLockedBadgePill" class="hidden text-[9px] font-mono-code font-bold uppercase px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">Locked</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <input type="text" id="vaultQrPropertyId" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono-code text-slate-900 focus:outline-none focus:border-emerald-500" placeholder="NACOC/EMU/#####" oninput="regenerateVaultBadgePreview()">
                            <button type="button" id="vaultQrRandomizeBtn" onclick="randomizeVaultPropertyId()" class="px-2.5 py-1.5 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 rounded-xl border border-slate-200 transition-colors shrink-0 text-xs font-bold flex items-center space-x-1" title="Generate Fresh 5-Digit Unique ID">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">New ID</span>
                            </button>
                        </div>
                        <p class="text-[9.5px] text-slate-400">Format: NACOC/EMU/##### (random unique 5-digit)</p>
                    </div>

                    <!-- Exhibit Quick Reference -->
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-0.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Target Exhibit</label>
                        <div class="font-extrabold text-xs text-slate-900 truncate" id="vaultQrExhibitName">--</div>
                        <div class="text-[10px] font-mono-code text-slate-500 truncate" id="vaultQrCaseRef">Case: --</div>
                        <div class="text-[10px] text-emerald-700 font-semibold truncate" id="vaultQrSuspectName">Suspect: --</div>
                    </div>
                </div>

                <!-- Live Specification Badge Preview Container -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Live Seized Property Badge Specification Preview</span>
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono-code">Badge Dimensions: 780 × 330 px • High-Res PNG</span>
                    </div>

                    <div class="bg-slate-200/80 p-4 rounded-3xl border border-slate-300 flex items-center justify-center overflow-hidden">
                        <!-- Wrapper for scale -->
                        <div class="w-full max-w-[720px] aspect-[780/330] bg-white rounded-xl shadow-md border border-slate-300 relative flex items-center justify-center overflow-hidden">
                            <canvas id="vaultBadgeCanvas" width="780" height="330" class="w-full h-full object-contain"></canvas>
                        </div>
                    </div>
                    <!-- Hidden container used for QR code generation -->
                    <div id="vaultHiddenQrContainer" class="hidden"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                <button type="button" onclick="closeVaultQrModal()" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                    Cancel
                </button>

                <div class="flex items-center space-x-3">
                    <button type="button" onclick="downloadVaultQrCodePNG()" class="px-3.5 py-2 bg-white hover:bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold rounded-xl shadow-2xs transition-colors flex items-center space-x-1.5 cursor-pointer" title="Download Standalone QR Code (PNG)">
                        <i data-lucide="qr-code" class="w-4 h-4 text-emerald-600"></i>
                        <span>Save QR (PNG)</span>
                    </button>

                    <button type="button" onclick="downloadVaultBadgePNG()" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl shadow-2xs transition-colors flex items-center space-x-2 cursor-pointer">
                        <i data-lucide="download" class="w-4 h-4 text-slate-600"></i>
                        <span>Download PNG Badge</span>
                    </button>

                    <button type="button" id="saveVaultQrBtn" onclick="saveVaultQrToExhibit()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center space-x-2 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        <span>Assign &amp; Save to Exhibit</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 10. DRUG CLASSIFICATION & EXHIBIT TYPE MODAL               -->
    <!-- ========================================================== -->
    <div id="vaultClassifyModal" class="fixed inset-0 z-55 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-500/20 text-teal-400 border border-teal-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-white">Classify Exhibit &amp; Set Type</h2>
                        <p class="text-xs text-slate-400 mt-0.5" id="vaultClassifyModalSubtitle">Central Vault Custody &amp; Substance Categorization</p>
                    </div>
                </div>
                <button type="button" onclick="closeVaultClassifyModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form id="vaultClassifyForm" onsubmit="saveVaultClassification(event)" class="p-5 sm:p-6 space-y-4 overflow-y-auto max-h-[80vh] custom-scrollbar bg-slate-50/50">
                <input type="hidden" id="classifyExhibitId">

                <!-- Target Exhibit Header -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target Exhibit</span>
                        <div class="text-xs font-extrabold text-slate-900" id="classifyTargetTitle">--</div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Case Number</span>
                        <span class="text-xs font-bold font-mono-code text-indigo-700" id="classifyTargetCase">--</span>
                    </div>
                </div>

                <!-- Exhibit Category -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">Exhibit Category</label>
                    <select id="classifyCategory" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-500" onchange="toggleCategoryClassificationFields()">
                        <option value="Drugs">Drugs &amp; Narcotics</option>
                        <option value="Weapons">Weapons &amp; Firearms</option>
                        <option value="Vehicles">Vehicles &amp; Transport</option>
                        <option value="Electronics">Digital Devices &amp; Media</option>
                        <option value="Currency">Currency / Cash</option>
                        <option value="General">General Exhibit</option>
                    </select>
                </div>

                <!-- Commodity Name -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">Commodity / Exhibit Name</label>
                    <input type="text" id="classifyCommodityName" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500" placeholder="e.g. Cocaine, Heroin, AK-47, Toyota Hilux...">
                </div>

                <!-- Exhibit Type Section -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center space-x-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Exhibit Type</span>
                    </label>
                    <div class="relative">
                        <select id="classifyExhibitType" onchange="handleExhibitTypeChange()"
                            class="exhibit-type-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                            <option value="">-- Select Type --</option>
                            <option value="Drug Exhibit">Drug Exhibit</option>
                            <option value="Non-Drug Exhibit">Non-Drug Exhibit</option>
                        </select>
                    </div>
                </div>

                <!-- DRUG CLASSIFICATION DROPDOWN -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5" id="drugClassificationSection">
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center space-x-1.5">
                        <i data-lucide="pill" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Classify Drugs</span>
                    </label>
                    <div class="relative">
                        <select id="classifyDrugSchedule" onchange="handleScheduleChange()"
                            class="drug-classification-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                            <option value="N/A">N/A</option>
                            <option value="Class A">Class A</option>
                            <option value="Class B">Class B</option>
                            <option value="Class C">Class C</option>
                        </select>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">Custodial Remarks / Notes</label>
                    <textarea id="classifyRemark" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500" placeholder="Enter any specific vault lodgement or chemical notes..."></textarea>
                </div>

                <!-- Form Footer Actions -->
                <div class="pt-2 flex items-center justify-between">
                    <button type="button" onclick="closeVaultClassifyModal()" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="saveVaultClassifyBtn" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center space-x-2">
                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        <span>Save Classification &amp; Type</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="vaultToastContainer" class="fixed bottom-5 right-5 z-70 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
            bindPaginationLinks();
        });

        // ==========================================
        // ASYNC VAULT FILTER & SKELETON LOADING ENGINE
        // ==========================================
        let searchDebounceTimer = null;
        let currentVaultAbortController = null;

        function showVaultSkeleton() {
            const currentMode = document.getElementById('viewModeInput')?.value || 'grid';
            const gridSkeleton = document.getElementById('skeletonGridView');
            const tableSkeleton = document.getElementById('skeletonTableView');
            const skeletonContainer = document.getElementById('vaultSkeletonContainer');
            const contentContainer = document.getElementById('vaultExhibitsContent');

            if (currentMode === 'table') {
                if (gridSkeleton) gridSkeleton.classList.add('hidden');
                if (tableSkeleton) tableSkeleton.classList.remove('hidden');
            } else {
                if (tableSkeleton) tableSkeleton.classList.add('hidden');
                if (gridSkeleton) gridSkeleton.classList.remove('hidden');
            }

            if (contentContainer) contentContainer.classList.add('hidden');
            if (skeletonContainer) skeletonContainer.classList.remove('hidden');
        }

        function hideVaultSkeleton() {
            const skeletonContainer = document.getElementById('vaultSkeletonContainer');
            const contentContainer = document.getElementById('vaultExhibitsContent');

            if (skeletonContainer) skeletonContainer.classList.add('hidden');
            if (contentContainer) contentContainer.classList.remove('hidden');
        }

        async function fetchVaultContent(customUrl = null, updateHistory = true) {
            if (currentVaultAbortController) {
                currentVaultAbortController.abort();
            }
            currentVaultAbortController = new AbortController();

            showVaultSkeleton();

            let targetUrl;
            if (customUrl) {
                targetUrl = new URL(customUrl, window.location.origin);
            } else {
                const form = document.getElementById('vaultFilterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value !== '' && value !== null) {
                        params.append(key, value);
                    }
                }
                targetUrl = new URL(form.action, window.location.origin);
                targetUrl.search = params.toString();
            }

            try {
                const response = await fetch(targetUrl.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: currentVaultAbortController.signal
                });

                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }

                const data = await response.json();

                if (data.success && data.html !== undefined) {
                    const contentContainer = document.getElementById('vaultExhibitsContent');
                    if (contentContainer) {
                        contentContainer.innerHTML = data.html;
                    }

                    if (updateHistory) {
                        window.history.pushState(null, '', targetUrl.pathname + targetUrl.search);
                    }

                    // Update UI Filter Controls & Badges
                    syncFilterControlStates(targetUrl, data.metrics);
                    bindPaginationLinks();

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Failed to load vault content:', error);
                    showVaultToast('Failed to update evidence vault. Please try again.', 'error');
                }
            } finally {
                hideVaultSkeleton();
            }
        }

        function syncFilterControlStates(url, metrics) {
            const params = url.searchParams;
            const searchVal = params.get('search') || '';
            const actionStatusVal = params.get('action_status') || 'all';
            const suspectFilterVal = params.get('suspect_filter') || 'all';
            const sortVal = params.get('sort') || 'newest';
            const categoryVal = params.get('category') || 'all';
            const viewModeVal = params.get('view') || 'grid';

            // Sync inputs if not already matching
            const searchInput = document.getElementById('vaultSearchInput');
            if (searchInput && searchInput.value !== searchVal) searchInput.value = searchVal;

            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !searchVal);

            const actionStatusSelect = document.getElementById('actionStatusSelect');
            if (actionStatusSelect && actionStatusSelect.value !== actionStatusVal) actionStatusSelect.value = actionStatusVal;

            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect && sortSelect.value !== sortVal) sortSelect.value = sortVal;

            const categoryInput = document.getElementById('categoryInput');
            if (categoryInput) categoryInput.value = categoryVal;

            const viewModeInput = document.getElementById('viewModeInput');
            if (viewModeInput) viewModeInput.value = viewModeVal;

            // Update View Switcher Buttons
            const gridBtn = document.getElementById('gridModeBtn');
            const tableBtn = document.getElementById('tableModeBtn');
            if (gridBtn && tableBtn) {
                if (viewModeVal === 'table') {
                    tableBtn.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 bg-white text-slate-900 shadow-xs';
                    gridBtn.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 text-slate-500 hover:text-slate-800';
                } else {
                    gridBtn.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 bg-white text-slate-900 shadow-xs';
                    tableBtn.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center space-x-1 text-slate-500 hover:text-slate-800';
                }
            }

            // Update Category Pill Active Styles
            const categoryPills = document.querySelectorAll('.category-pill-btn');
            categoryPills.forEach(pill => {
                const cat = pill.getAttribute('data-cat');
                const badge = pill.querySelector('.category-count-badge');
                const isSelected = (cat === 'all' && (categoryVal === 'all' || !categoryVal)) || (cat !== 'all' && categoryVal.toLowerCase().includes(cat));

                if (isSelected) {
                    pill.className = 'category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 bg-emerald-600 text-white font-bold shadow-xs';
                    if (badge) badge.className = 'category-count-badge text-[10px] px-1.5 py-0.2 rounded-full bg-white/20 text-white font-mono-code font-bold';
                } else {
                    pill.className = 'category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200';
                    if (badge) badge.className = 'category-count-badge text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-700 font-mono-code font-bold';
                }
            });

            // Update metric count numbers if returned
            if (metrics) {
                document.querySelectorAll('[data-metric]').forEach(el => {
                    const metricKey = el.getAttribute('data-metric');
                    if (metrics[metricKey] !== undefined) {
                        el.textContent = metrics[metricKey];
                    }
                });
            }

            // Toggle Reset Filter Button
            const hasActiveFilters = searchVal || categoryVal !== 'all' || actionStatusVal !== 'all' || sortVal !== 'newest';
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) resetBtn.classList.toggle('hidden', !hasActiveFilters);
        }

        function handleSearchInput(val) {
            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !val);

            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchVaultContent();
            }, 350);
        }

        function clearSearch() {
            const searchInput = document.getElementById('vaultSearchInput');
            if (searchInput) searchInput.value = '';
            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.add('hidden');
            fetchVaultContent();
        }

        function switchView(mode) {
            document.getElementById('viewModeInput').value = mode;
            fetchVaultContent();
        }

        function selectCategory(cat) {
            document.getElementById('categoryInput').value = cat;
            fetchVaultContent();
        }

        function selectActionStatus(status) {
            const selectEl = document.getElementById('actionStatusSelect');
            if (selectEl) selectEl.value = status;
            fetchVaultContent();
        }

        function resetVaultFilters() {
            const form = document.getElementById('vaultFilterForm');
            if (form) {
                document.getElementById('vaultSearchInput').value = '';
                document.getElementById('actionStatusSelect').value = 'all';
                document.getElementById('sortSelect').value = 'newest';
                document.getElementById('categoryInput').value = 'all';
            }
            fetchVaultContent();
        }

        function bindPaginationLinks() {
            const container = document.getElementById('vaultExhibitsContent');
            if (!container) return;

            const links = container.querySelectorAll('a[href*="/Evidence_Management/vault"]');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.getAttribute('href') && !link.getAttribute('onclick')) {
                        e.preventDefault();
                        fetchVaultContent(link.getAttribute('href'));
                        window.scrollTo({ top: container.offsetTop - 100, behavior: 'smooth' });
                    }
                });
            });
        }

        window.addEventListener('popstate', () => {
            fetchVaultContent(window.location.href, false);
        });

        // Image URL Resolver Helper
        function resolveImageUrl(path) {
            if (!path) return '';
            if (path.startsWith('data:') || path.startsWith('http://') || path.startsWith('https://')) {
                return path;
            }
            if (path.startsWith('storage/') || path.startsWith('uploads/') || path.startsWith('images/')) {
                return '/' + path;
            }
            return '/storage/' + path.replace(/^\//, '');
        }

        // Global Vault Context
        window.currentModalExhibit = null;
        window.currentModalSuspects = [];
        let activeVaultQrExhibit = null;
        let activeClassifyExhibit = null;
        let nacocPreloadedLogo = null;
        const NACOC_LOGO_URL = '{{ asset("nacoc.jfif") }}';

        function preloadNacocLogo(onReady) {
            if (!nacocPreloadedLogo) {
                nacocPreloadedLogo = new Image();
                nacocPreloadedLogo.crossOrigin = 'anonymous';
                nacocPreloadedLogo.src = NACOC_LOGO_URL;
            }
            if (onReady) {
                if (nacocPreloadedLogo.complete && nacocPreloadedLogo.naturalWidth > 0) {
                    onReady(nacocPreloadedLogo);
                } else {
                    let called = false;
                    const done = (img) => {
                        if (called) return;
                        called = true;
                        onReady(img);
                    };
                    nacocPreloadedLogo.addEventListener('load', () => done(nacocPreloadedLogo), { once: true });
                    nacocPreloadedLogo.addEventListener('error', () => done(null), { once: true });
                    setTimeout(() => done(nacocPreloadedLogo && nacocPreloadedLogo.complete ? nacocPreloadedLogo : null), 350);
                }
            }
            return nacocPreloadedLogo;
        }
        preloadNacocLogo();

        function generateRandomPropertyId() {
            let cand = '';
            let attempts = 0;
            do {
                const rnd = (window.crypto && crypto.getRandomValues)
                    ? crypto.getRandomValues(new Uint32Array(1))[0] % 90000
                    : Math.floor(Math.random() * 90000);
                const numStr = String(10000 + rnd);
                cand = `NACOC/EMU/${numStr}`;
                attempts++;
            } while (cand === 'NACOC/EMU/00241' && attempts < 100);
            return cand;
        }

        function openExhibitModal(exhibit, suspects) {
            window.currentModalExhibit = exhibit;
            window.currentModalSuspects = suspects || [];
            const modal = document.getElementById('exhibitModal');
            
            // Header
            document.getElementById('modalExhibitTitle').textContent = exhibit.commodity_name || exhibit.exhibit_category || 'Exhibit Dossier';
            document.getElementById('modalExhibitNumber').textContent = exhibit.exhibit_number || ('EXH-#' + String(exhibit.id).padStart(4, '0'));
            document.getElementById('modalCategoryBadge').textContent = exhibit.exhibit_category || 'General';
            
            const caseNo = exhibit.case_number || 'N/A';
            const caseUrl = exhibit.case_review_url || '#';
            document.getElementById('modalCaseSubtitle').textContent = 'Case Docket: ' + caseNo;

            // Render Seized Property Badge Specification Preview in Inspect Container
            renderInspectBadgePreview(exhibit);

            // Metrics
            let qty = 'N/A';
            if (exhibit.seizure_weight) {
                qty = exhibit.seizure_weight + ' ' + (exhibit.seizure_unit || 'kg');
            } else if (exhibit.amount) {
                qty = exhibit.amount;
            } else if (exhibit.seizure_quantity) {
                qty = exhibit.seizure_quantity + ' ' + (exhibit.seizure_unit || 'pcs');
            }
            document.getElementById('modalQuantity').textContent = qty;
            document.getElementById('modalPackaging').textContent = 'Packaging: ' + (exhibit.seizure_packaging || 'N/A');

            const valRaw = (exhibit.seizure_value && exhibit.seizure_value !== 'N/A') ? exhibit.seizure_value : exhibit.amount;
            const cleanVal = valRaw ? String(valRaw).replace(/,/g, '').trim() : '';
            document.getElementById('modalValue').textContent = valRaw
                ? (!isNaN(Number(cleanVal)) && cleanVal !== '' ? 'GH₵ ' + Number(cleanVal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : valRaw)
                : 'Unspecified';

            const seizureDate = exhibit.seizure_date || (exhibit.created_at ? exhibit.created_at.substring(0, 10) : 'N/A');
            const seizureTime = exhibit.seizure_time ? ' @ ' + exhibit.seizure_time : '';
            document.getElementById('modalDateTime').textContent = seizureDate + seizureTime;
            const seizureLocationVal = exhibit.source_of_exhibit || exhibit.source_of_exhibits || exhibit.seizure_location || 'N/A';
            document.getElementById('modalLocationQuick').textContent = seizureLocationVal;
            document.getElementById('modalOfficer').textContent = exhibit.seizure_officer || 'N/A';
            document.getElementById('modalSource').textContent = 'Source: ' + (exhibit.source_of_exhibit || exhibit.source_of_exhibits || 'N/A');

            // Left Column Core Details
            document.getElementById('modalCaseNumberDetail').textContent = caseNo;
            document.getElementById('modalCommodityName').textContent = exhibit.commodity_name || 'N/A';
            document.getElementById('modalExhibitType').textContent = exhibit.exhibit_type || 'N/A';
            document.getElementById('modalLocation').textContent = seizureLocationVal;
            document.getElementById('modalConcealment').textContent = exhibit.seizure_concealment || 'None';
            document.getElementById('modalReason').textContent = exhibit.seizure_reason || 'N/A';

            const invOfficerEl = document.getElementById('modalInvestigatingOfficer');
            if (invOfficerEl) invOfficerEl.textContent = exhibit.investigating_officer || exhibit.investigation_officer || exhibit.seizure_officer || 'N/A';
            const srcUnitEl = document.getElementById('modalSourceRequestingUnit');
            if (srcUnitEl) srcUnitEl.textContent = exhibit.source_requesting_unit || exhibit.source_of_exhibits || 'N/A';

            // Custodial Status Management
            const statusSelect = document.getElementById('inspectQuickStatusSelect');
            if (statusSelect) statusSelect.value = exhibit.custodial_status || 'In Vault Custody';
            const statusLabel = document.getElementById('inspectCustodialStatusLabel');
            if (statusLabel) statusLabel.textContent = exhibit.custodial_status || 'In Vault Custody';

            // Chain of Custody Input in Vault Custodial State
            const cocVal = exhibit.chain_of_custody ? String(exhibit.chain_of_custody).trim() : '';
            const fromInput = document.getElementById('inspectCustodyFrom');
            const toInput = document.getElementById('inspectCustodyTo');
            const cocInput = document.getElementById('inspectChainOfCustody');

            if (cocVal) {
                if (cocVal.includes('->')) {
                    const parts = cocVal.split('->');
                    if (fromInput) fromInput.value = parts[0].trim();
                    if (toInput) toInput.value = parts.slice(1).join(' -> ').trim();
                } else {
                    if (fromInput) fromInput.value = cocVal;
                    if (toInput) toInput.value = '';
                }
            } else {
                if (fromInput) fromInput.value = 'Evidence Vault';
                if (toInput) toInput.value = '';
            }
            if (cocInput) {
                cocInput.value = cocVal || (fromInput ? fromInput.value : 'Evidence Vault');
            }

            // ==========================================
            // EVIDENCE PHOTOS GALLERY POPULATION
            // ==========================================
            const gallery = document.getElementById('modalPhotosGallery');
            gallery.innerHTML = '';

            let photos = [];
            if (exhibit.evidence_photos) {
                if (Array.isArray(exhibit.evidence_photos)) {
                    photos = exhibit.evidence_photos;
                } else if (typeof exhibit.evidence_photos === 'string') {
                    try {
                        const parsed = JSON.parse(exhibit.evidence_photos);
                        if (Array.isArray(parsed)) photos = parsed;
                        else photos = [exhibit.evidence_photos];
                    } catch(e) {
                        photos = [exhibit.evidence_photos];
                    }
                }
            }

            document.getElementById('modalPhotosCountBadge').textContent = photos.length + (photos.length === 1 ? ' Photo' : ' Photos');

            if (photos.length > 0) {
                photos.forEach((photoPath, idx) => {
                    const resolvedUrl = resolveImageUrl(photoPath);
                    const photoCard = document.createElement('div');
                    photoCard.className = 'group relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-video shadow-2xs hover:shadow-md transition-all cursor-zoom-in';
                    photoCard.onclick = () => openImageLightbox(resolvedUrl, `Exhibit ${exhibit.exhibit_number || ''} — Photo ${idx + 1}`);

                    photoCard.innerHTML = `
                        <img src="${resolvedUrl}" alt="Evidence Photo ${idx + 1}" class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                            <span class="text-[10px] text-white font-mono-code font-bold flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                <span>Click to Enlarge</span>
                            </span>
                        </div>
                        <span class="absolute top-1.5 left-1.5 bg-black/60 backdrop-blur-xs text-white text-[9px] font-mono-code px-1.5 py-0.5 rounded-md font-bold">
                            #${idx + 1}
                        </span>
                    `;
                    gallery.appendChild(photoCard);
                });
            } else {
                gallery.innerHTML = `
                    <div class="col-span-full py-6 text-center text-slate-400 bg-slate-50/70 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-semibold text-slate-500">No digital evidence photographs logged</span>
                        <span class="text-[10px] text-slate-400">Photos taken during scene seizure will appear in this gallery</span>
                    </div>
                `;
            }

            // ==========================================
            // DYNAMIC CATEGORY TECHNICAL SPECS
            // ==========================================
            const specsBody = document.getElementById('modalCategorySpecsBody');
            const specsTitle = document.getElementById('modalCategorySpecsTitle');
            specsBody.innerHTML = '';

            const cat = (exhibit.exhibit_category || '').toLowerCase();

            if (cat.includes('vehic') || exhibit.veh_make || exhibit.veh_reg_no) {
                specsTitle.textContent = 'Vehicle & Transportation Dossier';
                const makeModelStr = [exhibit.veh_make, exhibit.veh_model].filter(Boolean).join(' ') || 'N/A';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <div><strong class="text-slate-400 block text-[10px]">Make &amp; Model</strong><span class="font-bold text-slate-900">${makeModelStr}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Registration No</strong><span class="font-mono-code font-bold text-blue-700">${exhibit.veh_reg_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">VIN / Chassis</strong><span class="font-mono-code font-bold text-slate-800">${exhibit.veh_vin || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Engine Number</strong><span class="font-mono-code text-slate-800">${exhibit.veh_engine_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Year / Colour</strong><span class="text-slate-800">${exhibit.veh_year || 'N/A'} • ${exhibit.veh_colour || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Keys &amp; Fuel</strong><span class="text-slate-800">${exhibit.veh_keys_recv ? ('Keys Recv' + (exhibit.veh_key_count ? ' (' + exhibit.veh_key_count + ')' : '')) : 'No Keys'} • Fuel: ${exhibit.veh_fuel || 'N/A'}</span></div>
                        <div class="col-span-full"><strong class="text-slate-400 block text-[10px]">Registered Owner / Driver</strong><span class="text-slate-800">Owner: ${exhibit.veh_owner || 'N/A'} (Contact: ${exhibit.veh_owner_contact || 'N/A'}) • Driver: ${exhibit.veh_driver || 'N/A'}</span></div>
                        ${exhibit.veh_damage ? `<div class="col-span-full"><strong class="text-slate-400 block text-[10px]">Damage Notes</strong><span class="text-amber-800 italic">${exhibit.veh_damage}</span></div>` : ''}
                    </div>
                `;
            } else if (cat.includes('weapon') || cat.includes('firearm') || exhibit.wpn_model || exhibit.wpn_serial_no) {
                specsTitle.textContent = 'Firearms & Ballistics Specifications';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <div><strong class="text-slate-400 block text-[10px]">Weapon Model</strong><span class="font-bold text-slate-900">${exhibit.wpn_model || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Serial Number</strong><span class="font-mono-code font-bold text-amber-700">${exhibit.wpn_serial_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Calibre / Gauge</strong><span class="font-mono-code text-slate-800">${exhibit.wpn_calibre || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Loaded Status</strong><span class="font-bold ${exhibit.wpn_loaded_status === 'Loaded' ? 'text-red-600' : 'text-slate-700'}">${exhibit.wpn_loaded_status || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Condition</strong><span class="text-slate-800">${exhibit.wpn_condition || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Location Found</strong><span class="text-slate-800">${exhibit.wpn_location_found || 'N/A'}</span></div>
                    </div>
                `;
            } else if (cat.includes('devic') || cat.includes('electron') || cat.includes('digital') || exhibit.dev_model || exhibit.dev_imei1) {
                specsTitle.textContent = 'Digital & Mobile Forensic Device Profile';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <div><strong class="text-slate-400 block text-[10px]">Device Model</strong><span class="font-bold text-slate-900">${exhibit.dev_model || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Serial Number</strong><span class="font-mono-code text-slate-800">${exhibit.dev_serial_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Primary IMEI</strong><span class="font-mono-code font-bold text-purple-700">${exhibit.dev_imei1 || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Secondary IMEI</strong><span class="font-mono-code text-purple-700">${exhibit.dev_imei2 || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Storage Capacity</strong><span class="font-mono-code text-slate-800">${exhibit.dev_storage || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">MAC / Network</strong><span class="font-mono-code text-slate-800">${exhibit.dev_mac_address || 'N/A'}</span></div>
                    </div>
                `;
            } else if (cat.includes('drug') || cat.includes('narcotic') || exhibit.drug_classification) {
                specsTitle.textContent = 'Narcotics Classification & Profile';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <div><strong class="text-slate-400 block text-[10px]">Drug Classification</strong><span class="font-bold text-emerald-800 font-mono-code">${exhibit.drug_classification || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 block text-[10px]">Physical State</strong><span class="font-bold text-slate-800">${exhibit.commodity_name || 'N/A'}</span></div>
                    </div>
                `;
            } else {
                specsTitle.textContent = 'General Exhibit Particulars';
                specsBody.innerHTML = `
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 text-slate-700">
                        <strong class="text-slate-400 block text-[10px]">Extended Description</strong>
                        <span>${exhibit.other_exhibit_description || exhibit.commodity_name || 'N/A'}</span>
                    </div>
                `;
            }

            // ==========================================
            // ASSOCIATED SUSPECTS LIST POPULATION
            // ==========================================
            const suspectsList = document.getElementById('modalSuspectsList');
            suspectsList.innerHTML = '';

            if (caseNo !== 'N/A') {
                document.getElementById('modalCaseLinkContainer').innerHTML = `Case: <a href="${caseUrl}" class="underline hover:text-emerald-900">${caseNo}</a>`;
                document.getElementById('modalOpenCaseBtn').href = caseUrl;
                document.getElementById('modalOpenCaseBtn').classList.remove('hidden');
            } else {
                document.getElementById('modalCaseLinkContainer').textContent = 'Case: Unassigned';
                document.getElementById('modalOpenCaseBtn').classList.add('hidden');
            }

            const suspectsCountBadge = document.getElementById('modalSuspectsCountBadge');
            if (suspectsCountBadge) {
                suspectsCountBadge.textContent = `${suspects ? suspects.length : 0} Suspect(s)`;
            }

            if (suspects && suspects.length > 0) {
                suspects.forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'bg-slate-50/90 rounded-2xl p-4 border border-slate-200/90 shadow-2xs space-y-3 hover:border-emerald-300 transition-colors';
                    const mugSrc = resolveImageUrl(s.mugshot);

                    const roleLower = (s.role || '').toLowerCase();
                    const isPrimary = roleLower.includes('primary') || roleLower.includes('sole');
                    const roleBadgeClass = isPrimary 
                        ? 'bg-amber-100 text-amber-900 border-amber-200' 
                        : 'bg-blue-100 text-blue-900 border-blue-200';

                    const dispLower = (s.suspect_disposition || '').toLowerCase();
                    let dispBadgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                    if (dispLower.includes('custody') || dispLower.includes('remand')) {
                        dispBadgeClass = 'bg-rose-50 text-rose-800 border-rose-200';
                    } else if (dispLower.includes('bail') || dispLower.includes('released')) {
                        dispBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                    } else if (dispLower.includes('court') || dispLower.includes('convict')) {
                        dispBadgeClass = 'bg-purple-50 text-purple-800 border-purple-200';
                    }

                    card.innerHTML = `
                        <!-- Suspect Header: Mugshot, Full Name, Alias, Role & Custody Status -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3.5 min-w-0">
                                <!-- Mugshot with zoom lightbox -->
                                <div class="w-13 h-13 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-800 font-extrabold text-sm shrink-0 overflow-hidden shadow-2xs relative group/mug cursor-zoom-in">
                                    ${mugSrc ? `
                                        <img src="${mugSrc}" alt="${s.name}" class="w-full h-full object-cover transition-transform duration-200 group-hover/mug:scale-110" onclick="openImageLightbox('${mugSrc}', '${s.name} Mugshot')">
                                        <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover/mug:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                            <svg class="w-4 h-4 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                        </div>
                                    ` : `
                                        <span class="text-emerald-800 font-extrabold text-base">${(s.name || '--').substring(0, 2).toUpperCase()}</span>
                                    `}
                                </div>
                                <div class="min-w-0 space-y-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[9.5px] font-mono-code font-bold uppercase px-2 py-0.5 rounded border ${roleBadgeClass}">
                                            ${s.role || 'Suspect'}
                                        </span>
                                        ${s.is_directly_linked ? `<span class="text-[9px] font-mono-code font-bold uppercase px-1.5 py-0.5 rounded bg-teal-50 text-teal-800 border border-teal-200">Owner</span>` : ''}
                                    </div>
                                    <div class="font-extrabold text-slate-900 text-sm leading-tight truncate" title="${s.name}">
                                        ${s.name}
                                    </div>
                                    ${s.alias ? `
                                        <div class="text-[11px] text-slate-500 font-mono-code">
                                            <span class="text-slate-400 font-bold uppercase text-[9px]">Alias:</span> 
                                            <span class="font-bold text-slate-700">"${s.alias}"</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            <!-- Custodial Disposition Status -->
                            <div class="shrink-0 text-right">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Custodial Status</span>
                                <span class="text-[10px] px-2.5 py-1 rounded-full font-mono-code font-bold uppercase inline-block border ${dispBadgeClass}">
                                    ${s.suspect_disposition || 'N/A'}
                                </span>
                            </div>
                        </div>

                        <!-- Arranged Particulars Grid: Explicit, Clean Labeled Metadata Tiles -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-xs">
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-3xs">
                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Ghana Card (NIA)</span>
                                <span class="font-bold text-slate-900 font-mono-code text-[11px] block truncate" title="${s.ghana_card || 'N/A'}">
                                    ${s.ghana_card || 'N/A'}
                                </span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-3xs">
                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Gender</span>
                                <span class="font-bold text-slate-900 text-[11px] block">
                                    ${s.gender || 'N/A'}
                                </span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-3xs">
                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Age</span>
                                <span class="font-bold text-slate-900 text-[11px] block">
                                    ${s.age ? `${s.age} Years` : 'N/A'}
                                </span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-3xs">
                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Nationality</span>
                                <span class="font-bold text-slate-900 text-[11px] block truncate">
                                    ${s.nationality || 'N/A'}
                                </span>
                            </div>
                        </div>
                    `;
                    suspectsList.appendChild(card);
                });
            } else {
                suspectsList.innerHTML = `
                    <div class="text-xs text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-200 flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">No Associated Suspects Logged</div>
                            <div class="text-[11px] text-slate-500">This exhibit currently has no linked suspect profile on record.</div>
                        </div>
                    </div>
                `;
            }

            // Remarks
            document.getElementById('modalRemark').textContent = exhibit.exhibit_remark ? `"${exhibit.exhibit_remark}"` : 'No lodgement remarks recorded.';

            const labRemarkContainer = document.getElementById('modalLabRemarkContainer');
            if (exhibit.lab_remark) {
                document.getElementById('modalLabRemark').textContent = `"${exhibit.lab_remark}"`;
                labRemarkContainer.classList.remove('hidden');
            } else {
                labRemarkContainer.classList.add('hidden');
            }

            // Toggle Classify button: don't display if exhibit already has both drug classification and exhibit type
            const isAlreadyClassified = Boolean(exhibit.drug_classification && exhibit.drug_classification !== '' && exhibit.exhibit_type && exhibit.exhibit_type !== '');
            const inspectClassifyBtn = document.getElementById('inspectClassifyBtn');
            if (inspectClassifyBtn) {
                if (isAlreadyClassified) {
                    inspectClassifyBtn.classList.add('hidden');
                } else {
                    inspectClassifyBtn.classList.remove('hidden');
                }
            }
            const inspectUnassignedClassifyBtn = document.getElementById('inspectUnassignedClassifyBtn');
            if (inspectUnassignedClassifyBtn) {
                if (isAlreadyClassified) {
                    inspectUnassignedClassifyBtn.classList.add('hidden');
                } else {
                    inspectUnassignedClassifyBtn.classList.remove('hidden');
                }
            }

            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeExhibitModal() {
            document.getElementById('exhibitModal').classList.add('hidden');
        }

        // ==========================================
        // OFFICIAL SEIZED PROPERTY QR CODE BADGE
        // ==========================================
        function buildVaultSeizureQrPayload(exhibit, propertyId, status) {
            if (!exhibit) return 'NACOC SEIZED PROPERTY\nPROPERTY ID: ' + (propertyId || 'N/A');

            const clean = (v, max) => String(v == null ? '' : v)
                .replace(/[^\x20-\x7E]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, max || 120);

            // Resolve Property ID & Status (Always Seized)
            const propId = clean(propertyId || exhibit.property_id || '', 40);
            const liveStatus = 'Seized';

            // Case Reference
            const caseRef = clean(exhibit.case_number || (exhibit.single_suspect_case && exhibit.single_suspect_case.case_number) || (exhibit.multiple_suspect_case && exhibit.multiple_suspect_case.case_number) || '', 60);

            // Exhibit Number & Commodity Name
            let exhName = clean(exhibit.exhibit_number || '', 40);
            if (exhibit.commodity_name) {
                exhName = exhName ? `${exhName} (${clean(exhibit.commodity_name, 60)})` : clean(exhibit.commodity_name, 80);
            }

            // Category & Classification
            const category = clean(exhibit.exhibit_category || '', 60);
            const classification = clean(exhibit.drug_classification || exhibit.exhibit_type || '', 60);

            // Suspect(s)
            let suspectStr = '';
            if (window.currentModalSuspects && window.currentModalSuspects.length > 0) {
                suspectStr = window.currentModalSuspects.map(s => s.name).filter(Boolean).join(', ');
            } else if (exhibit.associated_suspects && Array.isArray(exhibit.associated_suspects) && exhibit.associated_suspects.length > 0) {
                suspectStr = exhibit.associated_suspects.map(s => s.name).filter(Boolean).join(', ');
            } else if (exhibit.associated_person) {
                suspectStr = exhibit.associated_person;
            } else if (exhibit.suspect && exhibit.suspect.suspect_name) {
                suspectStr = exhibit.suspect.suspect_name;
            } else if (exhibit.single_suspect_case && exhibit.single_suspect_case.suspect_name) {
                suspectStr = exhibit.single_suspect_case.suspect_name;
            }
            suspectStr = clean(suspectStr, 120);

            // Quantity / Weight
            let qtyStr = '';
            if (exhibit.seizure_weight) {
                qtyStr = clean(exhibit.seizure_weight + ' ' + (exhibit.seizure_unit || 'kg'), 40);
            } else if (exhibit.amount) {
                qtyStr = clean(String(exhibit.amount), 40);
            } else if (exhibit.seizure_quantity) {
                qtyStr = clean(exhibit.seizure_quantity + ' ' + (exhibit.seizure_unit || 'pcs'), 40);
            }

            // Value
            let valStr = '';
            const rawValForTag = (exhibit.seizure_value && exhibit.seizure_value !== 'N/A') ? exhibit.seizure_value : exhibit.amount;
            if (rawValForTag) {
                const cleanTagVal = String(rawValForTag).replace(/,/g, '').trim();
                valStr = !isNaN(Number(cleanTagVal)) && cleanTagVal !== ''
                    ? 'GHS ' + Number(cleanTagVal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
                    : 'GHS ' + rawValForTag;
            }

            // Packaging, Concealment, Location, Officer, Date
            const packaging = clean(exhibit.seizure_packaging || '', 60);
            const concealment = clean(exhibit.seizure_concealment || '', 100);
            const location = clean(exhibit.source_of_exhibit || exhibit.source_of_exhibits || exhibit.seizure_location || '', 100);
            const officer = clean(exhibit.seizure_officer || '', 120);
            const sDate = exhibit.seizure_date || (exhibit.created_at ? exhibit.created_at.substring(0, 10) : '');
            const sTime = exhibit.seizure_time || '';
            const dateTime = clean((sDate + (sTime ? ' @ ' + sTime : '')).trim(), 40);

            // Category specific specs if applicable
            let specStr = '';
            const catLower = category.toLowerCase();
            if (catLower.includes('vehic') || exhibit.veh_reg_no || exhibit.veh_vin) {
                const vParts = [];
                const makeModel = [exhibit.veh_make, exhibit.veh_model].filter(Boolean).join(' ');
                if (makeModel) vParts.push(makeModel);
                if (exhibit.veh_reg_no) vParts.push(`Reg: ${exhibit.veh_reg_no}`);
                if (exhibit.veh_vin) vParts.push(`VIN: ${exhibit.veh_vin}`);
                if (vParts.length) specStr = clean(vParts.join(' | '), 120);
            } else if (catLower.includes('weapon') || catLower.includes('firearm') || exhibit.wpn_model || exhibit.wpn_serial_no) {
                const wParts = [];
                if (exhibit.wpn_model) wParts.push(exhibit.wpn_model);
                if (exhibit.wpn_serial_no) wParts.push(`SN: ${exhibit.wpn_serial_no}`);
                if (exhibit.wpn_calibre) wParts.push(`Cal: ${exhibit.wpn_calibre}`);
                if (wParts.length) specStr = clean(wParts.join(' | '), 120);
            } else if (catLower.includes('devic') || catLower.includes('electron') || exhibit.dev_model || exhibit.dev_imei1) {
                const dParts = [];
                if (exhibit.dev_model) dParts.push(exhibit.dev_model);
                if (exhibit.dev_imei1) dParts.push(`IMEI: ${exhibit.dev_imei1}`);
                if (exhibit.dev_serial_no) dParts.push(`SN: ${exhibit.dev_serial_no}`);
                if (dParts.length) specStr = clean(dParts.join(' | '), 120);
            }

            const lines = [
                'SEIZED PROPERTY',
                'NACOC - EVIDENCE MANAGEMENT',
                '----------------------------------------',
                propId ? `PROPERTY ID: ${propId}` : '',
                `STATUS: ${liveStatus}`,
                caseRef ? `CASE REF: ${caseRef}` : '',
                exhName ? `EXHIBIT: ${exhName}` : '',
                category && category !== 'General' ? `CATEGORY: ${category}` : '',
                classification ? `CLASSIFICATION: ${classification}` : '',
                specStr ? `SPECS: ${specStr}` : '',
                suspectStr ? `SUSPECT(S): ${suspectStr}` : '',
                qtyStr ? `QUANTITY/WEIGHT: ${qtyStr}` : '',
                packaging ? `PACKAGING: ${packaging}` : '',
                valStr ? `EST. VALUE: ${valStr}` : '',
                concealment && concealment !== 'None' ? `CONCEALMENT: ${concealment}` : '',
                location ? `LOCATION: ${location}` : '',
                officer ? `SEIZING OFFICER(S): ${officer}` : '',
                dateTime ? `DATE: ${dateTime}` : ''
            ];

            return lines.filter(Boolean).join('\n');
        }


        function renderNacocBrandedQRCode(qrContainer, qrPayload, size, onReady) {
            if (!qrContainer) {
                if (typeof onReady === 'function') onReady(null);
                return;
            }
            qrContainer.innerHTML = '';
            if (typeof QRCode === 'undefined') {
                console.warn('QRCode library is not loaded');
                if (typeof onReady === 'function') onReady(null);
                return;
            }

            size = size || 180;

            try {
                new QRCode(qrContainer, {
                    text: qrPayload,
                    width: size,
                    height: size,
                    colorDark: "#0B6B3A", // Rich NACOC Emerald Green matching specification
                    colorLight: "#FFFFFF",
                    correctLevel: QRCode.CorrectLevel.M
                });

                const overlayLogo = (logoImg) => {
                    const canvas = qrContainer.querySelector('canvas');
                    if (!canvas) {
                        const img = qrContainer.querySelector('img');
                        if (typeof onReady === 'function') onReady(img);
                        return;
                    }

                    if (logoImg && logoImg.complete && logoImg.naturalWidth > 0) {
                        try {
                            const ctx = canvas.getContext('2d');
                            const w = canvas.width;
                            const h = canvas.height;
                            const cx = w / 2;
                            const cy = h / 2;

                            const badgeDiameter = Math.round(w * 0.22);
                            const radius = Math.round(badgeDiameter / 2);

                            ctx.save();
                            ctx.beginPath();
                            ctx.arc(cx, cy, radius, 0, Math.PI * 2);
                            ctx.fillStyle = '#FFFFFF';
                            ctx.fill();
                            ctx.lineWidth = 1.5;
                            ctx.strokeStyle = '#0B6B3A';
                            ctx.stroke();

                            ctx.beginPath();
                            ctx.arc(cx, cy, radius - 1, 0, Math.PI * 2);
                            ctx.clip();

                            const logoAspect = logoImg.naturalWidth / logoImg.naturalHeight;
                            let drawH = radius * 2 - 2;
                            let drawW = drawH * logoAspect;
                            ctx.drawImage(logoImg, cx - (drawW / 2), cy - (drawH / 2), drawW, drawH);
                            ctx.restore();
                        } catch (e) {
                            console.warn('Logo overlay skipped:', e);
                        }
                    }

                    const img = qrContainer.querySelector('img');
                    if (img) {
                        try {
                            img.src = canvas.toDataURL('image/png');
                            img.style.display = 'block';
                            img.style.width = '100%';
                            img.style.height = '100%';
                        } catch (e) {}
                    }
                    canvas.style.display = 'block';

                    if (typeof onReady === 'function') onReady(canvas);
                };

                preloadNacocLogo(overlayLogo);
            } catch (e) {
                console.warn('QRCode generation primary attempt notice:', e);
                // Robust fallback attempt with concise essential payload and Level L
                try {
                    qrContainer.innerHTML = '';
                    const lines = (qrPayload || '').split('\n');
                    const conciseLines = lines.filter(l => 
                        l.startsWith('SEIZED') || 
                        l.startsWith('NACOC') || 
                        l.startsWith('PROPERTY ID') || 
                        l.startsWith('STATUS') || 
                        l.startsWith('CASE') || 
                        l.startsWith('EXHIBIT') || 
                        l.startsWith('QUANTITY') || 
                        l.startsWith('DATE')
                    );
                    const conciseText = (conciseLines.length > 0 ? conciseLines.join('\n') : (qrPayload || '')).slice(0, 320);

                    new QRCode(qrContainer, {
                        text: conciseText,
                        width: size,
                        height: size,
                        colorDark: "#0B6B3A",
                        colorLight: "#FFFFFF",
                        correctLevel: QRCode.CorrectLevel.L
                    });
                    const c = qrContainer.querySelector('canvas') || qrContainer.querySelector('img');
                    if (typeof onReady === 'function') onReady(c);
                } catch (err) {
                    console.error('QRCode generation fallback error:', err);
                    if (typeof onReady === 'function') onReady(null);
                }
            }
        }

        function drawVaultBadgeToCanvas(targetCanvas, data) {
            const w = 780, h = 330;
            targetCanvas.width = w;
            targetCanvas.height = h;
            const ctx = targetCanvas.getContext('2d');
            const sans = 'system-ui, -apple-system, "Inter", sans-serif';
            const ls = (v) => { if ('letterSpacing' in ctx) ctx.letterSpacing = v; };

            // Plain white background
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, w, h);

            // Thin outer border
            ctx.strokeStyle = '#CBD5E1';
            ctx.lineWidth = 2;
            ctx.strokeRect(1, 1, w - 2, h - 2);

            // Header: Bookman Old Style red bold uppercase SEIZED PROPERTY
            ctx.textAlign = 'center';
            ctx.fillStyle = '#EF4444';
            ctx.font = `900 26px 'Bookman Old Style', Bookman, 'URW Bookman', Georgia, serif`;
            ls('4px');
            ctx.fillText('SEIZED PROPERTY', w / 2, 52);

            // Subtitle: NACOC – EVIDENCE MANAGEMENT
            ctx.fillStyle = '#475569';
            ctx.font = `600 13px ${sans}`;
            ls('3px');
            ctx.fillText('NACOC – EVIDENCE MANAGEMENT', w / 2, 78);
            ls('0px');

            const divider = (y) => {
                ctx.strokeStyle = '#CBD5E1';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(w, y);
                ctx.stroke();
            };
            divider(98);
            divider(h - 52);

            // Left details
            ctx.textAlign = 'left';
            ctx.fillStyle = '#64748B';
            ctx.font = `700 12px ${sans}`;
            ls('3px');
            ctx.fillText('PROPERTY ID', 36, 146);
            ls('0px');
            ctx.fillStyle = '#000000';
            ctx.font = 'bold 30px "JetBrains Mono", monospace, system-ui';
            ctx.fillText(data.propertyId || '', 36, 188);

            ctx.fillStyle = '#475569';
            ctx.font = `600 16px ${sans}`;
            ctx.fillText('Status:', 36, 224);
            const sw = ctx.measureText('Status: ').width;
            ctx.fillStyle = '#000000';
            ctx.font = `700 16px ${sans}`;
            ctx.fillText(data.status || 'Seized', 36 + sw, 224);

            if (data.caseRef) {
                ctx.fillStyle = '#64748B';
                ctx.font = `600 12px ${sans}`;
                ctx.fillText(`Case: ${data.caseRef}`, 36, 252);
            }

            // QR code on white badge without background container
            if (data.qrSource) {
                const drawQr = (src) => {
                    try {
                        const q = 140;
                        ctx.drawImage(src, w - q - 40, 116, q, q);
                    } catch (e) {
                        console.error('Error drawing QR onto canvas:', e);
                    }
                };

                if (typeof HTMLImageElement !== 'undefined' && data.qrSource instanceof HTMLImageElement && !data.qrSource.complete) {
                    data.qrSource.onload = () => drawQr(data.qrSource);
                } else {
                    drawQr(data.qrSource);
                }
            }

            // Footer
            ctx.fillStyle = '#475569';
            ctx.font = `700 12px ${sans}`;
            ctx.textAlign = 'center';
            ls('3px');
            ctx.fillText('SCAN TO VIEW PROPERTY DETAILS', w / 2, h - 20);
            ls('0px');

            return targetCanvas;
        }

        // ==========================================
        // INSPECT CONTAINER BADGE PREVIEW LOGIC
        // ==========================================
        function renderInspectBadgePreview(exhibit) {
            if (!exhibit) return;
            const previewCard = document.getElementById('inspectBadgePreviewCard');
            const unassignedCard = document.getElementById('inspectBadgeUnassignedCard');
            const canvas = document.getElementById('inspectBadgeCanvas');
            const img = document.getElementById('inspectBadgeImg');
            const statusPill = document.getElementById('inspectBadgeStatusPill');
            const noticeText = document.getElementById('inspectBadgeNoticeText');
            const modalBtnText = document.getElementById('inspectOpenQrModalBtnText');

            const isAlreadyGenerated = Boolean(
                exhibit.tag_image || 
                exhibit.tag_url || 
                (exhibit.property_id && exhibit.property_id !== 'NACOC/EMU/00241' && exhibit.property_id.trim() !== '')
            );

            if (isAlreadyGenerated) {
                // Exhibit has an assigned QR code tag -> show preview card, hide unassigned placeholder
                if (previewCard) previewCard.classList.remove('hidden');
                if (unassignedCard) unassignedCard.classList.add('hidden');

                const propId = exhibit.property_id;

                if (statusPill) {
                    statusPill.textContent = "Officially Sealed • Seized";
                    statusPill.className = "text-[10px] font-mono-code font-bold uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300";
                }
                if (noticeText) {
                    noticeText.textContent = `Permanent official QR tag • Seized Property • Property ID: ${propId}`;
                }
                if (modalBtnText) {
                    modalBtnText.textContent = "View Sealed Tag";
                }

                // Render dynamic badge canvas with official QR code
                drawDynamicInspectBadge(exhibit, propId);
            } else {
                // If the exhibit has not been assigned QR yet, hide preview card and show unassigned card
                if (previewCard) previewCard.classList.add('hidden');
                if (unassignedCard) unassignedCard.classList.remove('hidden');
            }

            if (window.lucide) window.lucide.createIcons();
        }

        function drawDynamicInspectBadge(exhibit, propId) {
            const canvas = document.getElementById('inspectBadgeCanvas');
            if (!canvas) return;
            const status = 'Seized';
            let hiddenContainer = document.getElementById('vaultHiddenQrContainer');
            if (!hiddenContainer) {
                hiddenContainer = document.createElement('div');
                hiddenContainer.id = 'vaultHiddenQrContainer';
                hiddenContainer.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:220px;height:220px;opacity:0;pointer-events:none;';
                document.body.appendChild(hiddenContainer);
            }
            const qrPayload = buildVaultSeizureQrPayload(exhibit, propId, status);

            renderNacocBrandedQRCode(hiddenContainer, qrPayload, 200, (qrElem) => {
                const qrCanvas = hiddenContainer ? hiddenContainer.querySelector('canvas') : null;
                const qrImg = hiddenContainer ? hiddenContainer.querySelector('img') : null;
                const qrSource = qrElem || (qrCanvas && qrCanvas.width > 0 ? qrCanvas : qrImg);

                drawVaultBadgeToCanvas(canvas, {
                    propertyId: propId,
                    status: status,
                    caseRef: exhibit.case_number || '',
                    qrSource: qrSource
                });
            });
        }

        function downloadInspectBadgePNG() {
            const canvas = document.getElementById('inspectBadgeCanvas');
            if (!canvas || !window.currentModalExhibit) return;
            const propId = (window.currentModalExhibit.property_id || 'TAG').replace(/[\/\\]/g, '-');
            const a = document.createElement('a');
            a.download = `SEIZED_PROPERTY_TAG_${propId}.png`;
            a.href = canvas.toDataURL('image/png');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showVaultToast('Seized Property Tag downloaded successfully', 'success');
        }

        function downloadInspectQrCodePNG() {
            const hiddenContainer = document.getElementById('vaultHiddenQrContainer');
            if (!hiddenContainer || !window.currentModalExhibit) return;
            const qrCanvas = hiddenContainer.querySelector('canvas');
            const qrImg = hiddenContainer.querySelector('img');
            const dataUrl = (qrCanvas && qrCanvas.width > 0) ? qrCanvas.toDataURL('image/png') : (qrImg ? qrImg.src : null);
            if (!dataUrl) {
                showVaultToast('QR code generation is still preparing...', 'warning');
                return;
            }

            const propId = (window.currentModalExhibit.property_id || 'QR').replace(/[\/\\]/g, '_');
            const link = document.createElement('a');
            link.download = `${propId}_QRCode.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showVaultToast('Seized Property QR Code downloaded successfully', 'success');
        }

        function downloadVaultQrCodePNG() {
            const hiddenContainer = document.getElementById('vaultHiddenQrContainer');
            if (!hiddenContainer || !activeVaultQrExhibit) return;
            const qrCanvas = hiddenContainer.querySelector('canvas');
            const qrImg = hiddenContainer.querySelector('img');
            const dataUrl = (qrCanvas && qrCanvas.width > 0) ? qrCanvas.toDataURL('image/png') : (qrImg ? qrImg.src : null);
            if (!dataUrl) {
                showVaultToast('QR code generation is still preparing...', 'warning');
                return;
            }

            const propId = ((document.getElementById('vaultQrPropertyId')?.value || '').trim() || activeVaultQrExhibit.property_id || 'QR').replace(/[\/\\]/g, '_');
            const link = document.createElement('a');
            link.download = `${propId}_QRCode.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showVaultToast('Seized Property QR Code downloaded successfully', 'success');
        }

        function openInspectBadgeLightbox() {
            const canvas = document.getElementById('inspectBadgeCanvas');
            if (!canvas || !window.currentModalExhibit) return;
            const propId = window.currentModalExhibit.property_id || 'Seized Property Tag';
            const dataUrl = canvas.toDataURL('image/png');
            openImageLightbox(dataUrl, `Seized Property Badge Specification Preview (${propId})`);
        }

        function openInspectLivePortal() {
            if (!window.currentModalExhibit) return;
            const ex = window.currentModalExhibit;
            const url = ex.qr_url || (ex.qr_token ? `/exhibits/tag/${ex.qr_token}` : null);
            if (url) {
                window.open(url, '_blank');
            } else {
                showVaultToast('Exhibit live verification link pending token generation', 'warning');
            }
        }

        async function updateInspectCustodialStatus() {
            if (!window.currentModalExhibit) return;
            const ex = window.currentModalExhibit;
            const select = document.getElementById('inspectQuickStatusSelect');
            if (!select) return;
            const newStatus = select.value;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const res = await fetch(`/Evidence_Management/exhibits/${ex.id}/custodial-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ custodial_status: newStatus })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    ex.custodial_status = newStatus;
                    const statusLabel = document.getElementById('inspectCustodialStatusLabel');
                    if (statusLabel) statusLabel.textContent = newStatus;
                    showVaultToast(`Custodial status updated to '${newStatus}'! Live QR scan page is automatically synchronized.`, 'success');
                    renderInspectBadgePreview(ex);
                    updateExhibitDomBadges(ex);
                } else {
                    showVaultToast(data.message || 'Failed to update custodial status', 'error');
                }
            } catch(e) {
                console.error(e);
                showVaultToast('Error communicating with server', 'error');
            }
        }

        // Chain of Custody Dynamic Synchronization in Vault Custodial State
        function updateInspectChainOfCustodyValue() {
            const fromInput = document.getElementById('inspectCustodyFrom');
            const toInput = document.getElementById('inspectCustodyTo');
            const hiddenInput = document.getElementById('inspectChainOfCustody');

            const fromVal = fromInput ? fromInput.value.trim() : '';
            const toVal = toInput ? toInput.value.trim() : '';

            let combined = '';
            if (fromVal && toVal) {
                combined = `${fromVal} -> ${toVal}`;
            } else if (fromVal) {
                combined = fromVal;
            } else if (toVal) {
                combined = toVal;
            }

            if (hiddenInput) {
                hiddenInput.value = combined;
            }
            return combined;
        }

        async function saveInspectChainOfCustody() {
            if (!window.currentModalExhibit || !window.currentModalExhibit.id) return;
            const ex = window.currentModalExhibit;
            updateInspectChainOfCustodyValue();
            const input = document.getElementById('inspectChainOfCustody');
            if (!input) return;
            const val = input.value.trim();
            const btn = document.getElementById('inspectSaveChainOfCustodyBtn');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<svg class="w-3.5 h-3.5 animate-spin mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Saving...</span>`;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const res = await fetch(`/Evidence_Management/exhibits/${ex.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ chain_of_custody: val })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    ex.chain_of_custody = val;
                    if (data.exhibit) {
                        Object.assign(ex, data.exhibit);
                    }
                    input.value = val;
                    showVaultToast('Chain of custody updated successfully!', 'success');
                } else {
                    showVaultToast(data.message || 'Failed to update chain of custody', 'error');
                }
            } catch(e) {
                console.error(e);
                showVaultToast('Error communicating with server', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                if (window.lucide) window.lucide.createIcons();
            }
        }

        function openVaultQrModal(exhibit) {
            if (!exhibit) return;
            activeVaultQrExhibit = exhibit;

            const modal = document.getElementById('vaultQrModal');
            const propIdInput = document.getElementById('vaultQrPropertyId');
            const statusSelect = document.getElementById('vaultQrStatus');
            const exhibitName = document.getElementById('vaultQrExhibitName');
            const caseRef = document.getElementById('vaultQrCaseRef');
            const suspectName = document.getElementById('vaultQrSuspectName');
            const randomizeBtn = document.getElementById('vaultQrRandomizeBtn');
            const saveBtn = document.getElementById('saveVaultQrBtn');
            const lockedAlert = document.getElementById('vaultQrLockedAlert');
            const lockedPropId = document.getElementById('vaultQrLockedPropId');
            const lockedPill = document.getElementById('vaultQrLockedBadgePill');
            const modalSubtitle = document.getElementById('vaultQrModalSubtitle');

            exhibitName.textContent = exhibit.commodity_name || exhibit.exhibit_category || 'Exhibit #' + exhibit.id;
            caseRef.textContent = 'Case: ' + (exhibit.case_number || 'N/A');

            let suspectSummary = 'Suspect: Unassigned';
            if (window.currentModalSuspects && window.currentModalSuspects.length > 0) {
                suspectSummary = 'Suspect: ' + window.currentModalSuspects.map(s => s.name).join(', ');
            } else if (exhibit.associated_suspects && exhibit.associated_suspects.length > 0) {
                suspectSummary = 'Suspect: ' + exhibit.associated_suspects.map(s => s.name).join(', ');
            }
            suspectName.textContent = suspectSummary;

            // Check if Seized Property QR Code badge has already been generated
            const isAlreadyGenerated = Boolean(
                exhibit.tag_image || 
                exhibit.tag_url || 
                (exhibit.property_id && exhibit.property_id !== 'NACOC/EMU/00241' && /^NACOC\/EMU\/\d{5}$/.test(exhibit.property_id))
            );

            let propId = (exhibit.property_id || '').trim();
            if (!propId || propId === 'NACOC/EMU/00241' || !/^NACOC\/EMU\/\d{5}$/.test(propId)) {
                propId = generateRandomPropertyId();
            }
            propIdInput.value = propId;
            if (statusSelect) {
                statusSelect.value = 'Seized';
            }

            // Lock or unlock inputs according to badge generation state
            if (isAlreadyGenerated) {
                // Disable and lock Property ID input
                propIdInput.disabled = true;
                propIdInput.readOnly = true;
                propIdInput.className = "w-full px-3 py-1.5 bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold font-mono-code text-slate-500 cursor-not-allowed select-none";

                // Hide/disable randomize button
                if (randomizeBtn) {
                    randomizeBtn.disabled = true;
                    randomizeBtn.classList.add('hidden');
                }

                // Disable Assign & Save button
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.className = "px-5 py-2 bg-slate-200 text-slate-400 border border-slate-300 text-xs font-bold rounded-xl shadow-none cursor-not-allowed flex items-center space-x-2";
                    saveBtn.innerHTML = `
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400"></i>
                        <span>Badge Already Assigned &amp; Locked</span>
                    `;
                    saveBtn.title = "The official Seized Property QR Code badge has already been generated and assigned. Tag specifications are sealed and tamper-evident.";
                }

                // Show locked alert banner and pill
                if (lockedAlert) lockedAlert.classList.remove('hidden');
                if (lockedPropId) lockedPropId.textContent = propId;
                if (lockedPill) lockedPill.classList.remove('hidden');
                if (modalSubtitle) modalSubtitle.textContent = 'Official Seized Property tag has already been generated and locked';
            } else {
                // Enable Property ID input for new assignment
                propIdInput.disabled = false;
                propIdInput.readOnly = false;
                propIdInput.className = "w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono-code text-slate-900 focus:outline-none focus:border-emerald-500";

                // Enable randomize button
                if (randomizeBtn) {
                    randomizeBtn.disabled = false;
                    randomizeBtn.classList.remove('hidden');
                }

                // Enable Assign & Save button
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.className = "px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center space-x-2";
                    saveBtn.innerHTML = `
                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        <span>Assign &amp; Save to Exhibit</span>
                    `;
                    saveBtn.title = "Assign and save official Seized Property badge to this exhibit";
                }

                // Hide locked alert banner and pill
                if (lockedAlert) lockedAlert.classList.add('hidden');
                if (lockedPill) lockedPill.classList.add('hidden');
                if (modalSubtitle) modalSubtitle.textContent = 'Generate, preview, download, and assign QR tag to exhibit';
            }

            modal.classList.remove('hidden');
            regenerateVaultBadgePreview();
            if (window.lucide) window.lucide.createIcons();
        }

        function closeVaultQrModal() {
            document.getElementById('vaultQrModal').classList.add('hidden');
            activeVaultQrExhibit = null;
        }

        function randomizeVaultPropertyId() {
            if (!activeVaultQrExhibit) return;
            const isAlreadyGenerated = Boolean(
                activeVaultQrExhibit.tag_image || 
                activeVaultQrExhibit.tag_url || 
                (activeVaultQrExhibit.property_id && activeVaultQrExhibit.property_id !== 'NACOC/EMU/00241' && /^NACOC\/EMU\/\d{5}$/.test(activeVaultQrExhibit.property_id))
            );
            if (isAlreadyGenerated) return;

            const input = document.getElementById('vaultQrPropertyId');
            if (input && !input.disabled) {
                input.value = generateRandomPropertyId();
                regenerateVaultBadgePreview();
            }
        }

        function regenerateVaultBadgePreview() {
            if (!activeVaultQrExhibit) return;
            const propId = (document.getElementById('vaultQrPropertyId').value || '').trim() || generateRandomPropertyId();
            const status = document.getElementById('vaultQrStatus').value || 'Seized';

            let hiddenContainer = document.getElementById('vaultHiddenQrContainer');
            if (!hiddenContainer) {
                hiddenContainer = document.createElement('div');
                hiddenContainer.id = 'vaultHiddenQrContainer';
                hiddenContainer.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:220px;height:220px;opacity:0;pointer-events:none;';
                document.body.appendChild(hiddenContainer);
            }
            const previewCanvas = document.getElementById('vaultBadgeCanvas');

            const qrPayload = buildVaultSeizureQrPayload(activeVaultQrExhibit, propId, status);

            renderNacocBrandedQRCode(hiddenContainer, qrPayload, 200, (qrElem) => {
                const qrCanvas = hiddenContainer.querySelector('canvas');
                const qrImg = hiddenContainer.querySelector('img');
                const qrSource = qrElem || (qrCanvas && qrCanvas.width > 0 ? qrCanvas : qrImg);

                drawVaultBadgeToCanvas(previewCanvas, {
                    propertyId: propId,
                    status: status,
                    caseRef: activeVaultQrExhibit.case_number || '',
                    qrSource: qrSource
                });
            });
        }

        function downloadVaultBadgePNG() {
            const canvas = document.getElementById('vaultBadgeCanvas');
            if (!canvas) return;
            const propId = (document.getElementById('vaultQrPropertyId').value || 'TAG').replace(/[\/\\]/g, '-');
            const a = document.createElement('a');
            a.download = `SEIZED_PROPERTY_TAG_${propId}.png`;
            a.href = canvas.toDataURL('image/png');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showVaultToast('Seized Property Tag downloaded successfully', 'success');
        }

        async function saveVaultQrToExhibit() {
            if (!activeVaultQrExhibit) return;

            // Guard against saving again if already generated
            const isAlreadyGenerated = Boolean(
                activeVaultQrExhibit.tag_image || 
                activeVaultQrExhibit.tag_url || 
                (activeVaultQrExhibit.property_id && activeVaultQrExhibit.property_id !== 'NACOC/EMU/00241' && /^NACOC\/EMU\/\d{5}$/.test(activeVaultQrExhibit.property_id))
            );
            if (isAlreadyGenerated) {
                showVaultToast('This exhibit already has an assigned Seized Property Badge. Specifications are locked.', 'warning');
                return;
            }

            const btn = document.getElementById('saveVaultQrBtn');
            const propId = (document.getElementById('vaultQrPropertyId').value || '').trim();
            if (!propId) {
                showVaultToast('Please enter or generate a valid Property ID', 'error');
                return;
            }
            if (propId === 'NACOC/EMU/00241') {
                showVaultToast('Property ID NACOC/EMU/00241 is restricted. Generating new ID...', 'error');
                randomizeVaultPropertyId();
                return;
            }

            const canvas = document.getElementById('vaultBadgeCanvas');
            const tagImageData = canvas ? canvas.toDataURL('image/png') : null;

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Assigning...`;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/Evidence_Management/exhibits/${activeVaultQrExhibit.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        property_id: propId,
                        tag_image: tagImageData,
                        custodial_status: document.getElementById('vaultQrStatus')?.value || 'Seized'
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    showVaultToast(data.message || 'QR Tag assigned successfully to exhibit!', 'success');

                    activeVaultQrExhibit.property_id = propId;
                    activeVaultQrExhibit.custodial_status = data.exhibit?.custodial_status || document.getElementById('vaultQrStatus')?.value || 'Seized';
                    if (data.exhibit && (data.exhibit.tag_image || data.exhibit.tag_url)) {
                        activeVaultQrExhibit.tag_image = data.exhibit.tag_image || data.exhibit.tag_url;
                        activeVaultQrExhibit.tag_url = data.exhibit.tag_url;
                    } else if (tagImageData) {
                        activeVaultQrExhibit.tag_image = tagImageData;
                    }

                    if (window.currentModalExhibit && window.currentModalExhibit.id === activeVaultQrExhibit.id) {
                        window.currentModalExhibit.property_id = propId;
                        window.currentModalExhibit.custodial_status = activeVaultQrExhibit.custodial_status;
                        window.currentModalExhibit.tag_image = activeVaultQrExhibit.tag_image;
                        window.currentModalExhibit.tag_url = activeVaultQrExhibit.tag_url;

                        renderInspectBadgePreview(window.currentModalExhibit);
                    }

                    updateExhibitDomBadges(activeVaultQrExhibit);

                    // Re-render modal in locked state so officer cannot edit again
                    openVaultQrModal(activeVaultQrExhibit);
                } else {
                    showVaultToast(data.message || 'Failed to update exhibit QR tag', 'error');
                }
            } catch (e) {
                console.error('Error saving QR to exhibit:', e);
                showVaultToast('Network error while saving QR tag', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // ==========================================
        // DRUG CLASSIFICATION & EXHIBIT TYPE LOGIC
        // ==========================================
        function openVaultClassifyModal(exhibit, isDrugAssign = false) {
            if (!exhibit) return;
            activeClassifyExhibit = exhibit;

            const modal = document.getElementById('vaultClassifyModal');
            document.getElementById('classifyExhibitId').value = exhibit.id;
            document.getElementById('classifyTargetTitle').textContent = exhibit.commodity_name || exhibit.exhibit_category || 'Exhibit #' + exhibit.id;
            document.getElementById('classifyTargetCase').textContent = exhibit.case_number || 'N/A';

            const categorySelect = document.getElementById('classifyCategory');
            const existingCat = (exhibit.exhibit_category || '').toLowerCase();
            if (isDrugAssign && (!existingCat || existingCat === 'general' || existingCat === 'n/a')) {
                categorySelect.value = 'Drugs';
            } else {
                let matchedCat = false;
                for (let opt of categorySelect.options) {
                    if (opt.value.toLowerCase() === existingCat || (existingCat && existingCat.includes(opt.value.toLowerCase()))) {
                        categorySelect.value = opt.value;
                        matchedCat = true;
                        break;
                    }
                }
                if (!matchedCat) categorySelect.value = isDrugAssign ? 'Drugs' : 'General';
            }

            document.getElementById('classifyCommodityName').value = exhibit.commodity_name || '';

            // Setup Exhibit Type
            const typeSelect = document.getElementById('classifyExhibitType');
            typeSelect.innerHTML = `
                <option value="">-- Select Type --</option>
                <option value="Drug Exhibit">Drug Exhibit</option>
                <option value="Non-Drug Exhibit">Non-Drug Exhibit</option>
            `;
            const currentType = (exhibit.exhibit_type || '').trim();
            let matchedType = false;
            for (let opt of typeSelect.options) {
                if (opt.value && opt.value.toLowerCase() === currentType.toLowerCase()) {
                    typeSelect.value = opt.value;
                    matchedType = true;
                    break;
                }
            }
            if (!matchedType && currentType) {
                const opt = document.createElement('option');
                opt.value = currentType;
                opt.textContent = currentType;
                typeSelect.appendChild(opt);
                typeSelect.value = currentType;
            } else if (!matchedType) {
                if (isDrugAssign || categorySelect.value === 'Drugs' || (exhibit.commodity_name && /drug|cocaine|heroin|cannabis|meth|weed|hashish/i.test(exhibit.commodity_name))) {
                    typeSelect.value = 'Drug Exhibit';
                } else {
                    typeSelect.value = existingCat ? 'Non-Drug Exhibit' : '';
                }
            }

            // Setup Drug Classification
            const drugSelect = document.getElementById('classifyDrugSchedule');
            drugSelect.innerHTML = `
                <option value="N/A">N/A</option>
                <option value="Class A">Class A</option>
                <option value="Class B">Class B</option>
                <option value="Class C">Class C</option>
            `;
            const currentDrugCls = (exhibit.drug_classification || '').trim();
            let matchedDrug = false;
            for (let opt of drugSelect.options) {
                if (opt.value && opt.value.toLowerCase() === currentDrugCls.toLowerCase()) {
                    drugSelect.value = opt.value;
                    matchedDrug = true;
                    break;
                }
            }
            if (!matchedDrug && currentDrugCls && currentDrugCls !== 'N/A') {
                const opt = document.createElement('option');
                opt.value = currentDrugCls;
                opt.textContent = currentDrugCls;
                drugSelect.appendChild(opt);
                drugSelect.value = currentDrugCls;
            } else if (!matchedDrug) {
                drugSelect.value = (isDrugAssign || categorySelect.value === 'Drugs') ? 'Class A' : 'N/A';
            }

            document.getElementById('classifyRemark').value = exhibit.exhibit_remark || '';

            toggleCategoryClassificationFields();

            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();

            if (isDrugAssign) {
                setTimeout(() => {
                    const drugSel = document.getElementById('classifyDrugSchedule');
                    if (drugSel) drugSel.focus();
                }, 100);
            }
        }

        function closeVaultClassifyModal() {
            document.getElementById('vaultClassifyModal').classList.add('hidden');
            activeClassifyExhibit = null;
        }

        function toggleCategoryClassificationFields() {
            const cat = (document.getElementById('classifyCategory').value || '').toLowerCase();
            const exhTypeSelect = document.getElementById('classifyExhibitType');
            const drugClsSelect = document.getElementById('classifyDrugSchedule');
            if (cat.includes('drug') || cat.includes('narcotic')) {
                if (!exhTypeSelect.value) exhTypeSelect.value = 'Drug Exhibit';
            } else {
                if (!exhTypeSelect.value) exhTypeSelect.value = 'Non-Drug Exhibit';
                if (!drugClsSelect.value || drugClsSelect.value === 'N/A') drugClsSelect.value = 'N/A';
            }
        }

        function handleScheduleChange() {
            const drugCls = document.getElementById('classifyDrugSchedule').value;
            const exhTypeSelect = document.getElementById('classifyExhibitType');
            if (drugCls && drugCls !== 'N/A' && exhTypeSelect && !exhTypeSelect.value) {
                exhTypeSelect.value = 'Drug Exhibit';
            }
        }

        function handleExhibitTypeChange() {
            const exhType = document.getElementById('classifyExhibitType').value;
            const drugClsSelect = document.getElementById('classifyDrugSchedule');
            if (exhType === 'Non-Drug Exhibit' && drugClsSelect) {
                drugClsSelect.value = 'N/A';
            }
        }

        async function saveVaultClassification(event) {
            event.preventDefault();
            if (!activeClassifyExhibit) return;

            const btn = document.getElementById('saveVaultClassifyBtn');
            const category = document.getElementById('classifyCategory').value;
            const commodity = document.getElementById('classifyCommodityName').value.trim();

            let drugCls = document.getElementById('classifyDrugSchedule').value || 'N/A';
            let exhType = document.getElementById('classifyExhibitType').value || (category.toLowerCase().includes('drug') ? 'Drug Exhibit' : 'Non-Drug Exhibit');

            const remark = document.getElementById('classifyRemark').value.trim();

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Saving...`;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/Evidence_Management/exhibits/${activeClassifyExhibit.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        exhibit_category: category,
                        commodity_name: commodity,
                        drug_classification: drugCls,
                        exhibit_type: exhType,
                        exhibit_remark: remark
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    showVaultToast(data.message || 'Exhibit classified successfully!', 'success');

                    activeClassifyExhibit.exhibit_category = category;
                    activeClassifyExhibit.commodity_name = commodity || activeClassifyExhibit.commodity_name;
                    activeClassifyExhibit.drug_classification = drugCls;
                    activeClassifyExhibit.exhibit_type = exhType;
                    activeClassifyExhibit.exhibit_remark = remark;

                    if (window.currentModalExhibit && window.currentModalExhibit.id === activeClassifyExhibit.id) {
                        window.currentModalExhibit.drug_classification = drugCls;
                        window.currentModalExhibit.exhibit_type = exhType;
                        window.currentModalExhibit.commodity_name = activeClassifyExhibit.commodity_name;
                        window.currentModalExhibit.exhibit_category = category;
                        window.currentModalExhibit.exhibit_remark = remark;

                        renderInspectBadgePreview(window.currentModalExhibit);
                        document.getElementById('modalCommodityName').textContent = activeClassifyExhibit.commodity_name || 'N/A';
                        document.getElementById('modalExhibitType').textContent = exhType || 'N/A';
                        document.getElementById('modalRemark').textContent = remark ? `"${remark}"` : 'No lodgement remarks recorded.';
                    }

                    updateExhibitDomBadges(activeClassifyExhibit);
                    closeVaultClassifyModal();
                } else {
                    showVaultToast(data.message || 'Failed to save classification', 'error');
                }
            } catch (e) {
                console.error('Error saving classification:', e);
                showVaultToast('Network error while saving classification', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // ==========================================
        // REAL-TIME IN-PLACE DOM BADGE UPDATER
        // ==========================================
        function updateExhibitDomBadges(exhibit) {
            const containers = document.querySelectorAll(`[data-exhibit-id="${exhibit.id}"]`);
            containers.forEach(el => {
                // Update QR Property ID badges if property_id was set
                if (exhibit.property_id) {
                    const propBadges = el.querySelectorAll('[data-vault-prop-badge]');
                    propBadges.forEach(btn => {
                        btn.className = btn.className.replace(/bg-amber-\d+|text-amber-\d+|border-amber-\d+|bg-slate-\d+|text-slate-\d+/g, '');
                        btn.classList.add('bg-emerald-50', 'text-emerald-800', 'border-emerald-300');
                        btn.title = 'Seized Property Tag Assigned - Click to view/reprint';
                        btn.innerHTML = `
                            <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                            <span>${exhibit.property_id}</span>
                        `;
                    });

                    // Ensure card footer QR button stays labeled "QR Tag"
                    const footerQrBtns = el.querySelectorAll('[data-vault-footer-qr-btn]');
                    footerQrBtns.forEach(btn => {
                        btn.innerHTML = `
                            <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                            <span>QR Tag</span>
                        `;
                    });
                }

                // Update classification & type if changed
                if (exhibit.drug_classification && exhibit.drug_classification !== 'N/A') {
                    const classifyBadges = el.querySelectorAll('.vault-drug-badge, [title*="Drug Classification"]');
                    classifyBadges.forEach(badge => {
                        const span = badge.querySelector('span.truncate') || badge.querySelector('span') || badge;
                        span.textContent = exhibit.drug_classification;
                        badge.title = `Drug Classification: ${exhibit.drug_classification} - Click to reclassify`;
                    });

                    // Morph unclassified "Assign Drug Classification" button into the drug badge
                    const unclassifiedBtns = el.querySelectorAll('.vault-classify-btn');
                    unclassifiedBtns.forEach(btn => {
                        if (btn.textContent.includes('Assign Drug Classification') || btn.textContent.includes('Assign Drug Class') || btn.title.includes('Assign Drug Classification')) {
                            btn.className = "vault-drug-badge inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[10px] font-bold transition-colors cursor-pointer";
                            btn.title = `Drug Classification: ${exhibit.drug_classification} - Click to reclassify`;
                            btn.onclick = () => openVaultClassifyModal(exhibit, true);
                            btn.innerHTML = `<i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i><span class="truncate max-w-[160px]">${exhibit.drug_classification}</span>`;
                        }
                    });
                }

                // If exhibit already has both drug classification and exhibit type, remove Classify footer/action buttons
                const hasBothClsAndType = Boolean(exhibit.drug_classification && exhibit.drug_classification !== 'N/A' && exhibit.exhibit_type && exhibit.exhibit_type !== '');
                if (hasBothClsAndType) {
                    const footerClassifyBtns = el.querySelectorAll('.vault-classify-btn');
                    footerClassifyBtns.forEach(btn => {
                        if (btn.textContent.trim() === 'Classify' || btn.title.includes('Classify Drug') || btn.title.includes('Set Exhibit Type')) {
                            btn.remove();
                        }
                    });

                    const inspectBtn = document.getElementById('inspectClassifyBtn');
                    if (inspectBtn && window.currentModalExhibit && window.currentModalExhibit.id === exhibit.id) {
                        inspectBtn.classList.add('hidden');
                    }
                    const inspectUnassignedBtn = document.getElementById('inspectUnassignedClassifyBtn');
                    if (inspectUnassignedBtn && window.currentModalExhibit && window.currentModalExhibit.id === exhibit.id) {
                        inspectUnassignedBtn.classList.add('hidden');
                    }
                }

                // Update commodity title if changed
                if (exhibit.commodity_name) {
                    const h3 = el.querySelector('h3');
                    if (h3) h3.textContent = exhibit.commodity_name;
                }

                // Update dynamic custodial status badge if present
                if (exhibit.custodial_status) {
                    const custodialBadges = el.querySelectorAll(`[data-custodial-badge="${exhibit.id}"]`);
                    custodialBadges.forEach(badge => {
                        const textSpan = badge.querySelector('.custodial-status-text') || badge;
                        textSpan.textContent = exhibit.custodial_status;
                    });
                }
            });

            if (window.lucide) window.lucide.createIcons();
        }

        // ==========================================
        // FLOATING TOAST NOTIFICATIONS
        // ==========================================
        function showVaultToast(message, type = 'success') {
            const container = document.getElementById('vaultToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            const bgClass = isSuccess ? 'bg-emerald-950 text-white border-emerald-500/50' : 'bg-rose-950 text-white border-rose-500/50';
            const icon = isSuccess ? '✓' : '⚠️';

            toast.className = `pointer-events-auto flex items-center space-x-2.5 px-4 py-3 rounded-2xl shadow-xl border text-xs font-semibold backdrop-blur-md transition-all duration-300 transform translate-y-2 opacity-0 ${bgClass}`;
            toast.innerHTML = `
                <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs ${isSuccess ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'}">${icon}</span>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // ==========================================
        // IMAGE LIGHTBOX PREVIEW LOGIC
        // ==========================================
        function openImageLightbox(src, caption) {
            document.getElementById('lightboxImage').src = src;
            document.getElementById('lightboxCaption').textContent = caption || '';
            document.getElementById('imageLightboxModal').classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeImageLightbox() {
            document.getElementById('imageLightboxModal').classList.add('hidden');
            document.getElementById('lightboxImage').src = '';
        }

        // Close on ESC or backdrop click
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageLightbox();
                closeExhibitModal();
                closeVaultQrModal();
                closeVaultClassifyModal();
            }
        });
        document.getElementById('exhibitModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'exhibitModal') closeExhibitModal();
        });
        document.getElementById('imageLightboxModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'imageLightboxModal') closeImageLightbox();
        });
        document.getElementById('vaultQrModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'vaultQrModal') closeVaultQrModal();
        });
        document.getElementById('vaultClassifyModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'vaultClassifyModal') closeVaultClassifyModal();
        });
    </script>
</body>
</html>
