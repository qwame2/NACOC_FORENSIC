@php
    $authUser = auth()->user();
    $isStaffAccount = $authUser instanceof \App\Models\StaffAccount;
    $isEvidenceContext = true;
    $userUnit = 'Evidence Management';
    $sidebarUnit = 'Evidence Management';

    $officerName = $authUser ? ($authUser->fullname ?? $authUser->name ?? session('user_name') ?? 'Evidence Vault Officer') : 'Evidence Vault Officer';
    $officerParts = preg_split('/\s+/', trim((string) $officerName)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerName, 0, 2));

    $sealedPct = ($totalExhibitsCount ?? 0) > 0
        ? (int) round((($completedCount ?? 0) / $totalExhibitsCount) * 100)
        : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Evidence Management Unit | Exhibit Log &amp; Seized Property Registry | NACOC Forensic</title>

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
    <script src="{{ asset('vendor/qrcode.min.js') }}"></script>

    @include('partials.report_design_system')

    <style>
        /* Category Semantic Pills */
        .badge-category-drug { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-category-weapon { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .badge-category-vehicle { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-category-device { background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
        .badge-category-money { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-category-other { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }

        /* Grey Modal Executive Header */
        .vault-modal-header {
            background: linear-gradient(180deg, #f1f5f9 0%, #e6ecf3 100%);
            border-bottom: 1px solid #cbd5e1;
            color: #0f172a;
        }

        /* Vault Dossier Card Hover Elevation */
        .vault-dossier-card {
            background: #ffffff;
            border: 1px solid rgba(203, 213, 225, 0.9);
            border-radius: 1.15rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 6px 18px -8px rgba(15, 23, 42, 0.06);
            transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
        }
        .vault-dossier-card:hover {
            border-color: #6ee7b7;
            box-shadow: 0 10px 26px -10px rgba(5, 150, 105, 0.16), 0 2px 6px rgba(15, 23, 42, 0.05);
            transform: translateY(-1px);
        }

        /* Active KPI Tile Indicator */
        .vault-kpi-active {
            background: #ecfdf5 !important;
            box-shadow: inset 0 -2px 0 #059669;
        }

        /* Vault Category Filter Pills (Grey Container + Backshadow when Inactive) */
        .vault-pill-active {
            background-color: #059669;
            color: #ffffff;
            border: 1px solid #047857;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.28), 0 2px 4px rgba(15, 23, 42, 0.12);
        }
        .vault-pill-active:hover {
            background-color: #047857;
            color: #ffffff;
        }
        .vault-pill-inactive {
            background-color: #e2e8f0;
            color: #334155;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.12), 0 1px 2px rgba(15, 23, 42, 0.08);
        }
        .vault-pill-inactive:hover {
            background-color: #cbd5e1;
            color: #0f172a;
            border-color: #94a3b8;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.16), 0 2px 4px rgba(15, 23, 42, 0.10);
        }

        /* Responsive 3-Column Exhibit Grid */
        .exhibits-grid-3cols {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.25rem;
            width: 100%;
        }
        @media (max-width: 1180px) {
            .exhibits-grid-3cols {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 680px) {
            .exhibits-grid-3cols {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        <!-- 1. UNIVERSAL COMMAND SIDEBAR -->
        @if($isStaffAccount)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Evidence Management'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- 2. MAIN VAULT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                Evidence Management Unit
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Exhibit Log &amp; Seized Property Registry
                        </h1>
                    </div>
                </div>

                <!-- Right Header Officer Profile -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="text-right hidden md:block">
                            <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[160px]">{{ $officerName }}</div>
                            <div class="text-[10px] font-mono-code font-semibold text-slate-500">{{ $isStaffAccount ? 'Vault Custodian' : 'Vault Command' }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'EM' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                @include('partials.flash')

                <!-- 3. EXECUTIVE GREY COMMAND MASTHEAD + 7-COLUMN INTERACTIVE TELEMETRY MATRIX -->
                <section class="rep-masthead" aria-label="Vault Custodial Overview">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Exhibit Log &amp; Seized Property Vault
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Inspect seized exhibits, generate tamper-evident QR property badges, assign drug classifications, and verify associated suspect dockets.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs" title="Estimated Seizure Value: GH₵ {{ number_format($totalSeizureValue, 2) }}">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Total Vault Valuation</div>
                                    <div class="text-xs sm:text-sm font-mono-code font-extrabold text-slate-900 mt-0.5 tnum">
                                        GH₵ <span data-metric="total_value_formatted">{{ number_format($totalSeizureValue, 0) }}</span>
                                    </div>
                                </div>

                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Tagged &amp; Classified</div>
                                    <div class="text-xs sm:text-sm font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">
                                        <span data-metric="completed">{{ $completedCount }}</span> / <span data-metric="total">{{ $totalExhibitsCount }}</span> ({{ $sealedPct }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7-COLUMN CLICKABLE TELEMETRY STRIP -->
                    <div class="rep-kpi-grid grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 divide-x divide-y xl:divide-y-0 divide-slate-200/80">

                        <!-- Tile 1: All Exhibits -->
                        <button type="button"
                                data-kpi-tile="all"
                                onclick="selectCategory('all'); selectActionStatus('all');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ ($category === 'all' && $actionStatus === 'all') ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-slate-500 group-hover:text-emerald-700 truncate">All Exhibits</span>
                                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="box" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="total_formatted">{{ number_format($totalExhibitsCount) }}</div>
                            <div class="text-[10px] text-emerald-700 font-semibold mt-1 truncate">Active vault items</div>
                        </button>

                        <!-- Tile 2: Pending Intake -->
                        <button type="button"
                                data-kpi-tile="pending"
                                onclick="selectActionStatus('pending');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ $actionStatus === 'pending' ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-amber-700 truncate">Pending Intake</span>
                                <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-amber-700 font-mono-code tnum mt-2" data-metric="pending_formatted">{{ number_format($pendingCount) }}</div>
                            <div class="text-[10px] text-amber-700 font-semibold mt-1 truncate">Needs QR / Classify</div>
                        </button>

                        <!-- Tile 3: Sealed & Classified -->
                        <button type="button"
                                data-kpi-tile="completed"
                                onclick="selectActionStatus('completed');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ $actionStatus === 'completed' ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-teal-700 truncate">Sealed Ready</span>
                                <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="completed">{{ $completedCount }}</div>
                            <div class="text-[10px] text-teal-700 font-semibold mt-1 truncate">QR &amp; Type complete</div>
                        </button>

                        <!-- Tile 4: Narcotics -->
                        <button type="button"
                                data-kpi-tile="drug"
                                onclick="selectCategory('drug');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ str_contains(strtolower($category), 'drug') ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-emerald-700 truncate">Narcotics</span>
                                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="pill" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="drugs">{{ $drugsCount }}</div>
                            <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Drug consignments</div>
                        </button>

                        <!-- Tile 5: Weapons -->
                        <button type="button"
                                data-kpi-tile="weapon"
                                onclick="selectCategory('weapon');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ str_contains(strtolower($category), 'weapon') ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-amber-700 truncate">Weapons</span>
                                <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="weapons">{{ $weaponsCount }}</div>
                            <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Firearms &amp; ammo</div>
                        </button>

                        <!-- Tile 6: Vehicles -->
                        <button type="button"
                                data-kpi-tile="vehicle"
                                onclick="selectCategory('vehicle');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer {{ str_contains(strtolower($category), 'vehicle') ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-blue-700 truncate">Vehicles</span>
                                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 border border-blue-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="vehicles">{{ $vehiclesCount }}</div>
                            <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Cars &amp; transports</div>
                        </button>

                        <!-- Tile 7: Electronics -->
                        <button type="button"
                                data-kpi-tile="device"
                                onclick="selectCategory('device');"
                                class="rep-kpi-tile p-4 text-left group cursor-pointer col-span-2 sm:col-span-1 {{ str_contains(strtolower($category), 'device') ? 'vault-kpi-active' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-purple-700 truncate">Electronics</span>
                                <span class="w-6 h-6 rounded-lg bg-purple-50 text-purple-700 border border-purple-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-xl font-black text-slate-900 font-mono-code tnum mt-2" data-metric="devices">{{ $devicesCount }}</div>
                            <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate">Phones, PCs &amp; media</div>
                        </button>

                    </div>
                </section>

                <!-- 4. PRECISION VAULT SEARCH, FILTER & VIEW WORKBENCH -->
                <section class="rep-surface-card overflow-hidden" aria-label="Exhibit Search and Filter Controls">
                    <form method="GET" action="{{ url('/Evidence_Management/vault') }}" id="vaultFilterForm" onsubmit="event.preventDefault(); fetchVaultContent();">
                        <input type="hidden" name="view" id="viewModeInput" value="{{ $viewMode }}">
                        <input type="hidden" name="category" id="categoryInput" value="{{ $category }}">

                        <!-- Top Filter Row -->
                        <div class="p-4 sm:p-5 space-y-3.5">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">

                                <!-- Search Input -->
                                <div class="lg:col-span-5 relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="search" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text"
                                           name="search"
                                           id="vaultSearchInput"
                                           value="{{ $search }}"
                                           placeholder="Search exhibit #, property ID, commodity, suspect, case #, S/N, reg #, officer..."
                                           oninput="handleSearchInput(this.value)"
                                           autocomplete="off"
                                           class="rep-input w-full pl-10 pr-9 py-2.5 text-xs font-mono-code font-semibold">
                                    <button type="button"
                                            id="vaultClearSearchBtn"
                                            onclick="clearSearch()"
                                            aria-label="Clear search query"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 {{ $search ? '' : 'hidden' }}">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>

                                <!-- Intake / Tagging Status -->
                                <div class="lg:col-span-3">
                                    <select name="action_status"
                                            id="actionStatusSelect"
                                            onchange="fetchVaultContent()"
                                            aria-label="Filter by intake and QR tag status"
                                            class="rep-select w-full px-3 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="all" {{ $actionStatus === 'all' ? 'selected' : '' }}>Status: All Vault Exhibits</option>
                                        <option value="pending" {{ $actionStatus === 'pending' ? 'selected' : '' }}>Needs QR / Classify ({{ $pendingCount }})</option>
                                        <option value="unassigned_qr" {{ $actionStatus === 'unassigned_qr' ? 'selected' : '' }}>Unassigned QR Tag ({{ $unassignedQrCount }})</option>
                                        <option value="unclassified" {{ $actionStatus === 'unclassified' ? 'selected' : '' }}>Unclassified Exhibits ({{ $unclassifiedCount }})</option>
                                        <option value="completed" {{ $actionStatus === 'completed' ? 'selected' : '' }}>Sealed &amp; Classified ({{ $completedCount }})</option>
                                    </select>
                                </div>

                                <!-- Suspect Link Filter -->
                                <div class="lg:col-span-2">
                                    <select name="suspect_filter"
                                            id="suspectFilterSelect"
                                            onchange="fetchVaultContent()"
                                            aria-label="Filter by suspect association"
                                            class="rep-select w-full px-3 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="all" {{ ($suspectFilter ?? 'all') === 'all' ? 'selected' : '' }}>Suspects: All</option>
                                        <option value="with_suspects" {{ ($suspectFilter ?? '') === 'with_suspects' ? 'selected' : '' }}>Linked Suspects</option>
                                        <option value="no_suspects" {{ ($suspectFilter ?? '') === 'no_suspects' ? 'selected' : '' }}>Unassigned Suspect</option>
                                    </select>
                                </div>

                                <!-- Sort Order -->
                                <div class="lg:col-span-2">
                                    <select name="sort"
                                            id="sortSelect"
                                            onchange="fetchVaultContent()"
                                            aria-label="Sort vault exhibits"
                                            class="rep-select w-full px-3 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Sort: Newest First</option>
                                        <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
                                        <option value="value_desc" {{ ($sort ?? '') === 'value_desc' ? 'selected' : '' }}>Value: High &rarr; Low</option>
                                        <option value="value_asc" {{ ($sort ?? '') === 'value_asc' ? 'selected' : '' }}>Value: Low &rarr; High</option>
                                        <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>Commodity: A &rarr; Z</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <!-- Bottom Category Pills & View Mode Bar -->
                        <div class="px-4 py-3 sm:px-5 bg-slate-100/80 border-t border-slate-200/80 flex flex-col xl:flex-row xl:items-center justify-between gap-3">

                            <!-- Category Pills -->
                            @php
                                $vaultPillActiveClass = 'vault-pill-active';
                                $vaultPillInactiveClass = 'vault-pill-inactive';
                                $vaultBadgeActiveClass = 'bg-white/20 text-white';
                                $vaultBadgeInactiveClass = 'bg-slate-300 text-slate-800';
                            @endphp
                            <div class="flex items-center gap-2 overflow-x-auto pb-0.5 custom-scrollbar text-xs font-mono-code font-bold" id="categoryPillsContainer">
                                <button type="button" data-cat="all" onclick="selectCategory('all')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ $category === 'all' ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <span>All</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ $category === 'all' ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="total">{{ $totalExhibitsCount }}</span>
                                </button>
                                <button type="button" data-cat="drug" onclick="selectCategory('drug')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'drug') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="pill" class="w-3.5 h-3.5"></i>
                                    <span>Drugs</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ str_contains(strtolower($category), 'drug') ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="drugs">{{ $drugsCount }}</span>
                                </button>
                                <button type="button" data-cat="weapon" onclick="selectCategory('weapon')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'weapon') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                                    <span>Weapons</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ str_contains(strtolower($category), 'weapon') ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="weapons">{{ $weaponsCount }}</span>
                                </button>
                                <button type="button" data-cat="vehicle" onclick="selectCategory('vehicle')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'vehicle') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                                    <span>Vehicles</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ str_contains(strtolower($category), 'vehicle') ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="vehicles">{{ $vehiclesCount }}</span>
                                </button>
                                <button type="button" data-cat="device" onclick="selectCategory('device')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'device') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                    <span>Devices</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ str_contains(strtolower($category), 'device') ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="devices">{{ $devicesCount }}</span>
                                </button>
                                <button type="button" data-cat="money" onclick="selectCategory('money')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'money') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                                    <span>Currency</span>
                                    <span class="category-count-badge text-[10px] px-1.5 py-0.5 rounded-full {{ str_contains(strtolower($category), 'money') ? $vaultBadgeActiveClass : $vaultBadgeInactiveClass }} font-mono-code font-extrabold tnum" data-metric="currency">{{ $currencyCount }}</span>
                                </button>
                                <button type="button" data-cat="general" onclick="selectCategory('general')" class="category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer {{ str_contains(strtolower($category), 'general') ? $vaultPillActiveClass : $vaultPillInactiveClass }}">
                                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                    <span>General</span>
                                </button>
                            </div>

                            <!-- Right Controls: Per Page + View Switcher + Filter / Reset -->
                            <div class="flex items-center gap-2 flex-wrap shrink-0">
                                <select name="per_page"
                                        id="perPageSelect"
                                        onchange="fetchVaultContent()"
                                        aria-label="Exhibits per page"
                                        class="rep-select px-2.5 py-1.5 text-[11px] font-mono-code font-bold">
                                    <option value="12" {{ request('per_page', 12) == 12 ? 'selected' : '' }}>12 / page</option>
                                    <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24 / page</option>
                                    <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48 / page</option>
                                </select>

                                <!-- Segmented View Switcher (Green) -->
                                <div class="rep-segmented font-mono-code text-xs font-bold">
                                    <button type="button"
                                            id="gridModeBtn"
                                            onclick="switchView('grid')"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all {{ $viewMode === 'grid' ? 'rep-btn-green' : 'rep-btn-green-soft' }}"
                                            title="Dossier Cards View">
                                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                                        <span>Cards</span>
                                    </button>
                                    <button type="button"
                                            id="tableModeBtn"
                                            onclick="switchView('table')"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all {{ $viewMode === 'table' ? 'rep-btn-green' : 'rep-btn-green-soft' }}"
                                            title="Custodial Ledger Table View">
                                        <i data-lucide="table" class="w-3.5 h-3.5"></i>
                                        <span>Table</span>
                                    </button>
                                </div>

                                <button type="submit" class="rep-btn-green px-3.5 py-1.5 rounded-xl text-xs font-extrabold flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                                    <span>Apply</span>
                                </button>

                                <button type="button"
                                        id="resetFiltersBtn"
                                        onclick="resetVaultFilters()"
                                        class="rep-btn-green-soft px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer {{ ($search || $category !== 'all' || $actionStatus !== 'all' || ($suspectFilter ?? 'all') !== 'all' || ($sort ?? 'newest') !== 'newest') ? '' : 'hidden' }}"
                                        title="Reset all vault filters">
                                    Reset
                                </button>
                            </div>

                        </div>
                    </form>
                </section>

                <!-- 5. EXHIBIT VAULT CONTENT AREA (AJAX + SKELETON) -->
                <div id="vaultContentWrapper" class="relative min-h-[420px]">

                    <!-- Skeleton Loader State -->
                    <div id="vaultSkeletonContainer" class="hidden transition-opacity duration-200">
                        <div id="skeletonGridView" class="{{ $viewMode === 'table' ? 'hidden' : '' }} exhibits-grid-3cols animate-pulse">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="vault-dossier-card overflow-hidden flex flex-col">
                                    <div class="p-3.5 bg-slate-100 border-b border-slate-200/80 flex items-center justify-between">
                                        <div class="w-24 h-5 bg-slate-200 rounded-md"></div>
                                        <div class="w-28 h-5 bg-slate-200 rounded-md"></div>
                                    </div>
                                    <div class="p-4 space-y-3.5 flex-1">
                                        <div class="w-3/4 h-5 bg-slate-200 rounded"></div>
                                        <div class="flex gap-2">
                                            <div class="w-24 h-5 bg-slate-200 rounded-md"></div>
                                            <div class="w-20 h-5 bg-slate-200 rounded-md"></div>
                                        </div>
                                        <div class="h-16 bg-slate-100 rounded-xl border border-slate-200/70"></div>
                                        <div class="h-14 bg-slate-100 rounded-xl border border-slate-200/70"></div>
                                    </div>
                                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200/70 flex items-center justify-between">
                                        <div class="w-20 h-4 bg-slate-200 rounded"></div>
                                        <div class="flex gap-1.5">
                                            <div class="w-16 h-7 bg-slate-200 rounded-lg"></div>
                                            <div class="w-16 h-7 bg-slate-200 rounded-lg"></div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div id="skeletonTableView" class="{{ $viewMode === 'table' ? 'block' : 'hidden' }} rep-surface-card overflow-hidden animate-pulse">
                            <div class="p-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                                <div class="w-36 h-4 bg-slate-200 rounded"></div>
                                <div class="w-24 h-4 bg-slate-200 rounded"></div>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @for ($i = 0; $i < 6; $i++)
                                    <div class="p-4 flex items-center gap-4">
                                        <div class="w-10 h-10 bg-slate-200 rounded-xl shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="w-1/3 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-1/4 h-3 bg-slate-200 rounded"></div>
                                        </div>
                                        <div class="w-28 h-5 bg-slate-200 rounded-lg shrink-0 hidden sm:block"></div>
                                        <div class="w-24 h-7 bg-slate-200 rounded-lg shrink-0"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <!-- Live Vault Content -->
                    <div id="vaultExhibitsContent">
                        @include('Evidence_Management.partials.vault_content')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 6. EXPANDED FULL-DETAIL EXHIBIT INSPECTION MODAL           -->
    <!-- ========================================================== -->
    <div id="exhibitModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-7xl xl:max-w-[94vw] w-full h-[94vh] border border-slate-300 shadow-2xl overflow-hidden flex flex-col">

            <!-- Modal Grey Executive Header -->
            <div class="vault-modal-header px-5 sm:px-6 py-4 flex items-center justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="archive" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-extrabold tracking-tight text-slate-900 truncate max-w-md" id="modalExhibitTitle">Exhibit Dossier</h2>
                            <span class="text-[10.5px] px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-mono-code font-bold uppercase border border-emerald-200" id="modalCategoryBadge">
                                General
                            </span>
                            <span class="text-[11px] font-mono-code font-extrabold text-slate-900 bg-white border border-slate-300 px-2.5 py-0.5 rounded-md shadow-2xs" id="modalExhibitNumber">
                                EXH-0000
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-600 font-mono-code">
                            <span class="text-emerald-700 font-bold" id="modalCaseSubtitle">Case Docket: Unassigned</span>
                            <span class="text-slate-300">&bull;</span>
                            <span>Central Custodial Inspection Dossier</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="closeExhibitModal()" class="rep-btn-green-soft w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer" title="Close Inspection">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Scrollable Body -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar bg-[#f3f5f8]">

                <!-- Top 4-Card Telemetry Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <div class="rep-surface-card p-4">
                        <div class="flex items-center justify-between text-slate-500 mb-1.5">
                            <span class="text-[10px] uppercase font-extrabold tracking-wider font-mono-code">Quantity &amp; Weight</span>
                            <i data-lucide="scale" class="w-3.5 h-3.5 text-emerald-600"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 font-mono-code block truncate tnum" id="modalQuantity">N/A</span>
                        <span class="text-[11px] text-slate-500 truncate block mt-0.5 font-medium" id="modalPackaging">N/A</span>
                    </div>

                    <div class="rep-surface-card p-4">
                        <div class="flex items-center justify-between text-slate-500 mb-1.5">
                            <span class="text-[10px] uppercase font-extrabold tracking-wider font-mono-code">Estimated Valuation</span>
                            <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5 text-emerald-600"></i>
                        </div>
                        <span class="text-sm sm:text-base font-black text-emerald-700 font-mono-code block truncate tnum" id="modalValue">N/A</span>
                        <span class="text-[11px] text-slate-500 block mt-0.5 font-medium">Valuation on Seizure</span>
                    </div>

                    <div class="rep-surface-card p-4">
                        <div class="flex items-center justify-between text-slate-500 mb-1.5">
                            <span class="text-[10px] uppercase font-extrabold tracking-wider font-mono-code">Seizure Date &amp; Time</span>
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-emerald-600"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 font-mono-code block truncate tnum" id="modalDateTime">N/A</span>
                        <span class="text-[11px] text-slate-500 truncate block mt-0.5 font-medium" id="modalLocationQuick">N/A</span>
                    </div>

                    <div class="rep-surface-card p-4">
                        <div class="flex items-center justify-between text-slate-500 mb-1.5">
                            <span class="text-[10px] uppercase font-extrabold tracking-wider font-mono-code">Seizing Officer &amp; Source</span>
                            <i data-lucide="shield" class="w-3.5 h-3.5 text-emerald-600"></i>
                        </div>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 truncate block" id="modalOfficer">N/A</span>
                        <span class="text-[11px] text-emerald-700 font-semibold truncate block mt-0.5" id="modalSource">N/A</span>
                    </div>
                </div>

                <!-- Master 12-Column Inspection Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

                    <!-- Left Column (7 Cols): Particulars, Technical Specs, Photos & Remarks -->
                    <div class="lg:col-span-7 space-y-5">

                        <!-- 1. Seizure & Custody Details -->
                        <div class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <h4 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">Seizure &amp; Custody Details</h4>
                                </div>
                                <span class="text-[10px] font-mono-code text-slate-500 uppercase font-bold">Dossier Particulars</span>
                            </div>

                            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-700">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Case Reference</span>
                                    <span class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5 block" id="modalCaseNumberDetail">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Commodity / Item</span>
                                    <span class="font-extrabold text-slate-900 text-xs mt-0.5 block" id="modalCommodityName">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Exhibit Type</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalExhibitType">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Seizure Location</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalLocation">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Concealment Method</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalConcealment">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Seizure Reason</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalReason">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Investigating Officer</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalInvestigatingOfficer">N/A</span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 uppercase font-bold font-mono-code block">Source / Requesting Unit</span>
                                    <span class="font-bold text-slate-800 text-xs mt-0.5 block" id="modalSourceRequestingUnit">N/A</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Category-Specific Forensic Attributes -->
                        <div id="modalCategorySpecsContainer" class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                    <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
                                </span>
                                <h4 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider" id="modalCategorySpecsTitle">
                                    Category Technical Specifications
                                </h4>
                            </div>
                            <div id="modalCategorySpecsBody" class="p-5 text-xs space-y-2"></div>
                        </div>

                        <!-- 3. Exhibit Evidence Photographs & Media Gallery -->
                        <div class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                        <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <h3 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                        Exhibit Evidence Photographs &amp; Media Gallery
                                    </h3>
                                </div>
                                <span class="text-[10.5px] font-mono-code font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200" id="modalPhotosCountBadge">
                                    0 Photos
                                </span>
                            </div>

                            <div class="p-5">
                                <div id="modalPhotosGallery" class="grid grid-cols-2 sm:grid-cols-3 gap-3"></div>
                            </div>
                        </div>

                        <!-- 4. Remarks & Custody Notes -->
                        <div class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                </span>
                                <h4 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">Remarks &amp; Custody Notes</h4>
                            </div>

                            <div class="p-5 space-y-3.5">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 font-mono-code block">Exhibit Lodgement Remark:</span>
                                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 text-xs text-slate-800 italic leading-relaxed" id="modalRemark">
                                        No remark recorded.
                                    </div>
                                </div>

                                <div id="modalLabRemarkContainer" class="space-y-1 hidden">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 font-mono-code block">Laboratory Analyst Remark:</span>
                                    <div class="bg-emerald-50/60 p-3.5 rounded-xl border border-emerald-200/80 text-xs text-slate-800 italic leading-relaxed" id="modalLabRemark">
                                        No laboratory remarks.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column (5 Cols): Official QR Badge, Custodial Management & Suspects -->
                    <div class="lg:col-span-5 space-y-5">

                        <!-- 1. Seized Property Badge Specification Preview Card -->
                        <div id="inspectBadgePreviewCard" class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                                        <i data-lucide="qr-code" class="w-4 h-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider truncate">
                                            Seized Property QR Badge
                                        </h3>
                                        <span id="inspectBadgeStatusPill" class="text-[9.5px] font-mono-code font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-block mt-0.5">
                                            Officially Tagged
                                        </span>
                                    </div>
                                </div>

                                <button type="button"
                                        onclick="openInspectLivePortal()"
                                        class="rep-btn-green-soft px-2.5 py-1.5 rounded-xl font-bold text-[11px] flex items-center gap-1 cursor-pointer shrink-0"
                                        title="Open live scan verification page">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span>Live Portal</span>
                                </button>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="bg-slate-100 p-3 sm:p-4 rounded-xl border border-slate-200/90 flex flex-col items-center justify-center overflow-hidden">
                                    <div class="w-full aspect-[780/330] bg-white rounded-lg shadow-xs border border-slate-300 relative flex items-center justify-center overflow-hidden group">
                                        <canvas id="inspectBadgeCanvas" width="780" height="330" class="w-full h-full object-contain cursor-zoom-in" onclick="openInspectBadgeLightbox()" title="Click to enlarge badge preview"></canvas>
                                        <img id="inspectBadgeImg" src="" class="w-full h-full object-contain cursor-zoom-in hidden" alt="Seized Property Badge Specification" onclick="openInspectBadgeLightbox()">

                                        <div class="absolute bottom-2 right-2 px-2 py-1 bg-slate-800/80 text-white text-[9px] font-semibold rounded-md backdrop-blur-xs flex items-center gap-1 pointer-events-none opacity-80 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="zoom-in" class="w-3 h-3"></i>
                                            <span>Click to enlarge</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center text-[10px] text-slate-500 font-mono-code" id="inspectBadgeNoticeText">
                                        Official QR Code &bull; Scans to display instant exhibit details
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button"
                                            id="inspectOpenQrModalBtn"
                                            onclick="if(window.currentModalExhibit) openVaultQrModal(window.currentModalExhibit)"
                                            class="rep-btn-green col-span-2 px-3.5 py-2.5 rounded-xl font-extrabold text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="tag" class="w-4 h-4"></i>
                                        <span id="inspectOpenQrModalBtnText">Assign / View QR Tag</span>
                                    </button>

                                    <button type="button"
                                            id="inspectDownloadQrBtn"
                                            onclick="downloadInspectQrCodePNG()"
                                            class="rep-btn-green-soft px-3 py-2 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                        <span>Save QR</span>
                                    </button>

                                    <button type="button"
                                            id="inspectDownloadBadgeBtn"
                                            onclick="downloadInspectBadgePNG()"
                                            class="rep-btn-green-soft px-3 py-2 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>Save Badge</span>
                                    </button>

                                    <button type="button"
                                            id="inspectClassifyBtn"
                                            onclick="if(window.currentModalExhibit) openVaultClassifyModal(window.currentModalExhibit)"
                                            class="rep-btn-green-soft col-span-2 px-3 py-2 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="sliders" class="w-4 h-4"></i>
                                        <span>Classify &amp; Set Type</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Unassigned QR Tag Notice -->
                        <div id="inspectBadgeUnassignedCard" class="hidden rep-surface-card p-5 border-dashed border-amber-300 bg-amber-50/30 space-y-4">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                                    <i data-lucide="qr-code" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Tag Not Assigned</h3>
                                        <span class="text-[9.5px] font-mono-code font-bold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300">
                                            Pending QR
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        This exhibit does not have an official QR tag yet. Assign a tag to generate the sealed badge preview.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        id="inspectUnassignedAssignQrBtn"
                                        onclick="if(window.currentModalExhibit) openVaultQrModal(window.currentModalExhibit)"
                                        class="rep-btn-green flex-1 px-4 py-2.5 rounded-xl font-extrabold text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i data-lucide="tag" class="w-4 h-4"></i>
                                    <span>Assign QR Tag</span>
                                </button>

                                <button type="button"
                                        id="inspectUnassignedClassifyBtn"
                                        onclick="if(window.currentModalExhibit) openVaultClassifyModal(window.currentModalExhibit)"
                                        class="rep-btn-green-soft px-3.5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="sliders" class="w-4 h-4"></i>
                                    <span>Classify</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. Vault Custodial State & Chain of Custody -->
                        <div class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <h4 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">Vault Custodial State</h4>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold uppercase text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200" id="inspectCustodialStatusLabel">
                                    In Vault Custody
                                </span>
                            </div>

                            <div class="p-5 space-y-3.5">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs text-slate-600 flex items-start gap-2.5">
                                    <i data-lucide="lock" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                                    <span class="leading-relaxed">Seizure custody is secured under Evidence Management protocols and synchronized with live QR scan verification.</span>
                                </div>

                                <div class="space-y-2.5 pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <label for="inspectCustodyFrom" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                            <i data-lucide="link" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span>Chain of Custody Transfer</span>
                                        </label>
                                        <span class="text-[9.5px] font-mono-code font-bold uppercase text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            Custody Log
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 w-full">
                                        <div class="relative flex-1 min-w-0">
                                            <input type="text"
                                                   id="inspectCustodyFrom"
                                                   class="rep-input w-full px-3 py-2 text-xs font-bold"
                                                   value="Evidence Vault"
                                                   placeholder="From (e.g. Evidence Vault)"
                                                   oninput="updateInspectChainOfCustodyValue(this)">
                                        </div>

                                        <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 shrink-0" title="Custody Transfer">
                                            <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                                        </div>

                                        <div class="relative flex-1 min-w-0">
                                            <input type="text"
                                                   id="inspectCustodyTo"
                                                   class="rep-input w-full px-3 py-2 text-xs font-bold"
                                                   placeholder="Transfer To (Lab, Court...)"
                                                   oninput="updateInspectChainOfCustodyValue(this)">
                                        </div>
                                    </div>

                                    <textarea id="inspectChainOfCustody" class="hidden">Evidence Vault</textarea>

                                    <div class="flex items-center justify-between pt-1">
                                        <span class="text-[10px] text-slate-500">Saved directly to central exhibit registry</span>
                                        <button type="button"
                                                id="inspectSaveChainOfCustodyBtn"
                                                onclick="saveInspectChainOfCustody()"
                                                class="rep-btn-green px-3.5 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 cursor-pointer">
                                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                            <span>Save Chain of Custody</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Associated Suspect(s) Profile -->
                        <div class="rep-surface-card overflow-hidden">
                            <div class="px-5 py-3.5 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shadow-2xs">
                                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <h4 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">Associated Suspect(s)</h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200" id="modalSuspectsCountBadge">0 Suspect(s)</span>
                                    <span class="text-[11px] font-mono-code font-bold text-emerald-700" id="modalCaseLinkContainer"></span>
                                </div>
                            </div>

                            <div class="p-5">
                                <div id="modalSuspectsList" class="space-y-3"></div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Modal Sticky Footer -->
            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
                <div class="hidden sm:flex items-center gap-2 text-[11px] text-slate-500 font-mono-code">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>Evidence Management Unit &bull; Central Custodial Vault</span>
                </div>
                <div class="flex items-center gap-2.5 ml-auto">
                    <button type="button" onclick="closeExhibitModal()" class="rep-btn-green-soft px-4 py-2 font-bold text-xs rounded-xl cursor-pointer">
                        Close
                    </button>
                    <a href="#" id="modalOpenCaseBtn" class="rep-btn-green px-4 py-2 font-extrabold text-xs rounded-xl flex items-center gap-2 cursor-pointer no-underline">
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                        <span>Open Case File</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 7. HIGH-RESOLUTION IMAGE LIGHTBOX ZOOM MODAL               -->
    <!-- ========================================================== -->
    <div id="imageLightboxModal" class="fixed inset-0 z-60 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center">
            <button type="button" onclick="closeImageLightbox()" class="rep-btn-green-soft absolute -top-12 right-0 w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <img id="lightboxImage" src="" alt="Enlarged Preview" class="max-w-full max-h-[80vh] rounded-2xl object-contain shadow-2xl border border-slate-200 bg-white">
            <div id="lightboxCaption" class="mt-3 text-xs text-white font-mono-code font-semibold text-center"></div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 8. INTERACTIVE SEIZED PROPERTY QR CODE TAG MODAL           -->
    <!-- ========================================================== -->
    <div id="vaultQrModal" class="fixed inset-0 z-55 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-4xl w-full border border-slate-300 shadow-2xl overflow-hidden flex flex-col">

            <!-- Modal Grey Executive Header -->
            <div class="vault-modal-header px-6 py-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="qr-code" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-extrabold tracking-tight text-slate-900">Official Seized Property Tag</h2>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-mono-code font-bold uppercase border border-emerald-200">
                                QR Specification
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5" id="vaultQrModalSubtitle">Generate, preview, download, and assign QR tag to exhibit</p>
                    </div>
                </div>
                <button type="button" onclick="closeVaultQrModal()" class="rep-btn-green-soft w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto max-h-[78vh] custom-scrollbar bg-[#f3f5f8]">
                <div id="vaultQrLockedAlert" class="hidden p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs text-emerald-900">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="font-extrabold uppercase tracking-wider text-[10px] text-emerald-800 font-mono-code block">Officially Tagged &amp; Sealed</span>
                            <span class="text-slate-700 text-xs">Assigned Seized Property Badge with Property ID <strong id="vaultQrLockedPropId" class="font-mono-code text-slate-900 font-bold">--</strong>. Specifications are locked and tamper-evident.</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold uppercase border border-emerald-300 shrink-0 ml-2">
                        Sealed
                    </span>
                </div>

                <input type="hidden" id="vaultQrStatus" value="Seized">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div class="rep-surface-card p-4 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="vaultQrPropertyId" class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider block">Property ID</label>
                            <span id="vaultQrLockedBadgePill" class="hidden text-[9px] font-mono-code font-bold uppercase px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">Locked</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="vaultQrPropertyId" class="rep-input w-full px-3 py-1.5 text-xs font-bold font-mono-code" placeholder="NACOC/EMU/#####" oninput="regenerateVaultBadgePreview()">
                            <button type="button" id="vaultQrRandomizeBtn" onclick="randomizeVaultPropertyId()" class="rep-btn-green-soft px-3 py-1.5 rounded-xl shrink-0 text-xs font-bold flex items-center gap-1 cursor-pointer" title="Generate Fresh 5-Digit Unique ID">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">New ID</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 font-mono-code">Format: NACOC/EMU/##### (unique 5-digit)</p>
                    </div>

                    <div class="rep-surface-card p-4 space-y-0.5">
                        <span class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider block">Target Exhibit</span>
                        <div class="font-extrabold text-xs text-slate-900 truncate" id="vaultQrExhibitName">--</div>
                        <div class="text-[10.5px] font-mono-code text-slate-500 truncate" id="vaultQrCaseRef">Case: --</div>
                        <div class="text-[10.5px] text-emerald-700 font-semibold truncate" id="vaultQrSuspectName">Suspect: --</div>
                    </div>
                </div>

                <div class="rep-surface-card p-4 space-y-2.5">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="text-xs font-extrabold text-slate-800 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Live Seized Property Badge Preview</span>
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono-code">780 &times; 330 px &bull; High-Res PNG</span>
                    </div>

                    <div class="bg-slate-100 p-4 rounded-xl border border-slate-200/90 flex items-center justify-center overflow-hidden">
                        <div class="w-full max-w-[720px] aspect-[780/330] bg-white rounded-lg shadow-sm border border-slate-300 relative flex items-center justify-center overflow-hidden">
                            <canvas id="vaultBadgeCanvas" width="780" height="330" class="w-full h-full object-contain"></canvas>
                        </div>
                    </div>
                    <div id="vaultHiddenQrContainer" class="hidden"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-2.5 shrink-0">
                <button type="button" onclick="closeVaultQrModal()" class="rep-btn-green-soft px-4 py-2 text-xs font-bold rounded-xl cursor-pointer">
                    Cancel
                </button>

                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="downloadVaultQrCodePNG()" class="rep-btn-green-soft px-3.5 py-2 text-xs font-bold rounded-xl flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="qr-code" class="w-4 h-4"></i>
                        <span>Save QR (PNG)</span>
                    </button>

                    <button type="button" onclick="downloadVaultBadgePNG()" class="rep-btn-green-soft px-3.5 py-2 text-xs font-bold rounded-xl flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download Badge</span>
                    </button>

                    <button type="button" id="saveVaultQrBtn" onclick="saveVaultQrToExhibit()" class="rep-btn-green px-5 py-2 text-xs font-extrabold rounded-xl flex items-center gap-2 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Assign &amp; Save to Exhibit</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 9. DRUG CLASSIFICATION & EXHIBIT TYPE MODAL                -->
    <!-- ========================================================== -->
    <div id="vaultClassifyModal" class="fixed inset-0 z-55 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full border border-slate-300 shadow-2xl overflow-hidden flex flex-col">

            <!-- Modal Grey Executive Header -->
            <div class="vault-modal-header px-6 py-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900">Classify Exhibit &amp; Set Type</h2>
                        <p class="text-xs text-slate-600 mt-0.5" id="vaultClassifyModalSubtitle">Central Vault Custody &amp; Substance Categorization</p>
                    </div>
                </div>
                <button type="button" onclick="closeVaultClassifyModal()" class="rep-btn-green-soft w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form id="vaultClassifyForm" onsubmit="saveVaultClassification(event)" class="p-5 sm:p-6 space-y-4 overflow-y-auto max-h-[80vh] custom-scrollbar bg-[#f3f5f8]">
                <input type="hidden" id="classifyExhibitId">

                <div class="rep-surface-card p-4 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <span class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider block">Target Exhibit</span>
                        <div class="text-xs font-extrabold text-slate-900 truncate" id="classifyTargetTitle">--</div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider block">Case Number</span>
                        <span class="text-xs font-bold font-mono-code text-emerald-700" id="classifyTargetCase">--</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="rep-surface-card p-4 space-y-1.5">
                        <label for="classifyCategory" class="text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider block">Exhibit Category</label>
                        <select id="classifyCategory" class="rep-select w-full px-3 py-2 text-xs font-semibold" onchange="toggleCategoryClassificationFields()">
                            <option value="Drugs">Drugs &amp; Narcotics</option>
                            <option value="Weapons">Weapons &amp; Firearms</option>
                            <option value="Vehicles">Vehicles &amp; Transport</option>
                            <option value="Electronics">Digital Devices &amp; Media</option>
                            <option value="Currency">Currency / Cash</option>
                            <option value="General">General Exhibit</option>
                        </select>
                    </div>

                    <div class="rep-surface-card p-4 space-y-1.5">
                        <label for="classifyCommodityName" class="text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider block">Commodity / Exhibit Name</label>
                        <input type="text" id="classifyCommodityName" class="rep-input w-full px-3 py-2 text-xs font-bold" placeholder="e.g. Cocaine, Heroin, AK-47, Toyota Hilux...">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="rep-surface-card p-4 space-y-1.5">
                        <label for="classifyExhibitType" class="text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Exhibit Type</span>
                        </label>
                        <select id="classifyExhibitType" onchange="handleExhibitTypeChange()" class="rep-select w-full px-3 py-2 text-xs font-bold">
                            <option value="">-- Select Type --</option>
                            <option value="Drug Exhibit">Drug Exhibit</option>
                            <option value="Non-Drug Exhibit">Non-Drug Exhibit</option>
                        </select>
                    </div>

                    <div class="rep-surface-card p-4 space-y-1.5" id="drugClassificationSection">
                        <label for="classifyDrugSchedule" class="text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="pill" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Drug Classification</span>
                        </label>
                        <select id="classifyDrugSchedule" onchange="handleScheduleChange()" class="rep-select w-full px-3 py-2 text-xs font-bold">
                            <option value="N/A">N/A</option>
                            <option value="Class A">Class A</option>
                            <option value="Class B">Class B</option>
                            <option value="Class C">Class C</option>
                        </select>
                    </div>
                </div>

                <div class="rep-surface-card p-4 space-y-1.5">
                    <label for="classifyRemark" class="text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider block">Custodial Remarks / Notes</label>
                    <textarea id="classifyRemark" rows="2" class="rep-input w-full px-3 py-2 text-xs font-medium" placeholder="Enter any specific vault lodgement or forensic notes..."></textarea>
                </div>

                <div class="pt-1 flex items-center justify-between gap-3">
                    <button type="button" onclick="closeVaultClassifyModal()" class="rep-btn-green-soft px-4 py-2 text-xs font-bold rounded-xl cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="saveVaultClassifyBtn" class="rep-btn-green px-5 py-2 text-xs font-extrabold rounded-xl flex items-center gap-2 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Classification &amp; Type</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="vaultToastContainer" class="fixed bottom-5 right-5 z-70 flex flex-col gap-2 pointer-events-none"></div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
            bindPaginationLinks();
        });

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

            const searchInput = document.getElementById('vaultSearchInput');
            if (searchInput && searchInput.value !== searchVal) searchInput.value = searchVal;

            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !searchVal);

            const actionStatusSelect = document.getElementById('actionStatusSelect');
            if (actionStatusSelect && actionStatusSelect.value !== actionStatusVal) actionStatusSelect.value = actionStatusVal;

            const suspectSelect = document.getElementById('suspectFilterSelect');
            if (suspectSelect && suspectSelect.value !== suspectFilterVal) suspectSelect.value = suspectFilterVal;

            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect && sortSelect.value !== sortVal) sortSelect.value = sortVal;

            const categoryInput = document.getElementById('categoryInput');
            if (categoryInput) categoryInput.value = categoryVal;

            const viewModeInput = document.getElementById('viewModeInput');
            if (viewModeInput) viewModeInput.value = viewModeVal;

            const gridBtn = document.getElementById('gridModeBtn');
            const tableBtn = document.getElementById('tableModeBtn');
            if (gridBtn && tableBtn) {
                if (viewModeVal === 'table') {
                    tableBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all rep-btn-green';
                    gridBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all rep-btn-green-soft';
                } else {
                    gridBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all rep-btn-green';
                    tableBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all rep-btn-green-soft';
                }
            }

            const categoryPills = document.querySelectorAll('.category-pill-btn');
            categoryPills.forEach(pill => {
                const cat = pill.getAttribute('data-cat');
                const badge = pill.querySelector('.category-count-badge');
                const isSelected = (cat === 'all' && (categoryVal === 'all' || !categoryVal)) || (cat !== 'all' && categoryVal.toLowerCase().includes(cat));

                if (isSelected) {
                    pill.className = 'category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer vault-pill-active';
                    if (badge) badge.className = 'category-count-badge text-[10px] px-1.5 py-0.5 rounded-full bg-white/20 text-white font-mono-code font-extrabold tnum';
                } else {
                    pill.className = 'category-pill-btn px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1.5 cursor-pointer vault-pill-inactive';
                    if (badge) badge.className = 'category-count-badge text-[10px] px-1.5 py-0.5 rounded-full bg-slate-300 text-slate-800 font-mono-code font-extrabold tnum';
                }
            });

            document.querySelectorAll('[data-kpi-tile]').forEach(tile => {
                const key = tile.getAttribute('data-kpi-tile');
                let active = false;
                if (key === 'all') active = (categoryVal === 'all' || !categoryVal) && (actionStatusVal === 'all' || !actionStatusVal);
                else if (key === 'pending') active = actionStatusVal === 'pending';
                else if (key === 'completed') active = actionStatusVal === 'completed';
                else active = categoryVal.toLowerCase().includes(key);
                tile.classList.toggle('vault-kpi-active', active);
            });

            if (metrics) {
                document.querySelectorAll('[data-metric]').forEach(el => {
                    const metricKey = el.getAttribute('data-metric');
                    if (metrics[metricKey] !== undefined) {
                        el.textContent = metrics[metricKey];
                    }
                });
            }

            const hasActiveFilters = searchVal || categoryVal !== 'all' || actionStatusVal !== 'all' || suspectFilterVal !== 'all' || sortVal !== 'newest';
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) resetBtn.classList.toggle('hidden', !hasActiveFilters);
        }

        function handleSearchInput(val) {
            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !val);

            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchVaultContent();
            }, 320);
        }

        function clearSearch() {
            const searchInput = document.getElementById('vaultSearchInput');
            if (searchInput) searchInput.value = '';
            const clearBtn = document.getElementById('vaultClearSearchBtn');
            if (clearBtn) clearBtn.classList.add('hidden');
            fetchVaultContent();
        }

        function switchView(mode) {
            const viewInput = document.getElementById('viewModeInput');
            if (viewInput) viewInput.value = mode;
            fetchVaultContent();
        }

        function selectCategory(cat) {
            const catInput = document.getElementById('categoryInput');
            if (catInput) catInput.value = cat;
            fetchVaultContent();
        }

        function selectActionStatus(status) {
            const selectEl = document.getElementById('actionStatusSelect');
            if (selectEl) selectEl.value = status;
            fetchVaultContent();
        }

        function resetVaultFilters() {
            const searchEl = document.getElementById('vaultSearchInput');
            const statusEl = document.getElementById('actionStatusSelect');
            const suspectEl = document.getElementById('suspectFilterSelect');
            const sortEl = document.getElementById('sortSelect');
            const catEl = document.getElementById('categoryInput');
            const clearBtn = document.getElementById('vaultClearSearchBtn');

            if (searchEl) searchEl.value = '';
            if (statusEl) statusEl.value = 'all';
            if (suspectEl) suspectEl.value = 'all';
            if (sortEl) sortEl.value = 'newest';
            if (catEl) catEl.value = 'all';
            if (clearBtn) clearBtn.classList.add('hidden');

            fetchVaultContent();
        }

        function bindPaginationLinks() {
            const container = document.getElementById('vaultExhibitsContent');
            if (!container) return;

            const links = container.querySelectorAll('a[href*="/Evidence_Management/vault"], a[href*="/evidence-management/vault"], a[href*="/exhibit-vault"]');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.getAttribute('href') && !link.getAttribute('onclick')) {
                        e.preventDefault();
                        fetchVaultContent(link.getAttribute('href'));
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });
        }

        window.addEventListener('popstate', () => {
            fetchVaultContent(window.location.href, false);
        });

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

            document.getElementById('modalExhibitTitle').textContent = exhibit.commodity_name || exhibit.exhibit_category || 'Exhibit Dossier';
            document.getElementById('modalExhibitNumber').textContent = exhibit.exhibit_number || ('EXH-#' + String(exhibit.id).padStart(4, '0'));
            document.getElementById('modalCategoryBadge').textContent = exhibit.exhibit_category || 'General';

            const caseNo = exhibit.case_number || 'N/A';
            const caseUrl = exhibit.case_review_url || '#';
            document.getElementById('modalCaseSubtitle').textContent = 'Case Docket: ' + caseNo;

            renderInspectBadgePreview(exhibit);

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

            const statusLabel = document.getElementById('inspectCustodialStatusLabel');
            if (statusLabel) statusLabel.textContent = exhibit.custodial_status || 'In Vault Custody';

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

            // Evidence Photos Gallery
            const gallery = document.getElementById('modalPhotosGallery');
            gallery.innerHTML = '';

            let photos = [];
            if (exhibit.evidence_photos) {
                if (Array.isArray(exhibit.evidence_photos)) {
                    photos = exhibit.evidence_photos;
                } else if (typeof exhibit.evidence_photos === 'string') {
                    try {
                        const parsed = JSON.parse(exhibit.evidence_photos);
                        photos = Array.isArray(parsed) ? parsed : [exhibit.evidence_photos];
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
                    photoCard.className = 'group relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-video shadow-2xs hover:border-emerald-400 transition-all cursor-zoom-in';
                    photoCard.onclick = () => openImageLightbox(resolvedUrl, `Exhibit ${exhibit.exhibit_number || ''} — Photo ${idx + 1}`);

                    photoCard.innerHTML = `
                        <img src="${resolvedUrl}" alt="Evidence Photo ${idx + 1}" class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/65 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                            <span class="text-[10px] text-white font-mono-code font-bold">Click to Enlarge</span>
                        </div>
                        <span class="absolute top-1.5 left-1.5 bg-white/90 text-slate-900 border border-slate-200 text-[9px] font-mono-code px-1.5 py-0.5 rounded font-bold">
                            #${idx + 1}
                        </span>
                    `;
                    gallery.appendChild(photoCard);
                });
            } else {
                gallery.innerHTML = `
                    <div class="col-span-full py-6 text-center text-slate-500 bg-slate-50 rounded-xl border border-dashed border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <span class="text-xs font-semibold text-slate-600">No digital evidence photographs logged</span>
                        <span class="text-[10px] text-slate-400">Scene seizure photographs appear here when attached</span>
                    </div>
                `;
            }

            // Dynamic Category Technical Specs
            const specsBody = document.getElementById('modalCategorySpecsBody');
            const specsTitle = document.getElementById('modalCategorySpecsTitle');
            specsBody.innerHTML = '';

            const cat = (exhibit.exhibit_category || '').toLowerCase();

            if (cat.includes('vehic') || exhibit.veh_make || exhibit.veh_reg_no) {
                specsTitle.textContent = 'Vehicle & Transportation Dossier';
                const makeModelStr = [exhibit.veh_make, exhibit.veh_model].filter(Boolean).join(' ') || 'N/A';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Make &amp; Model</strong><span class="font-bold text-slate-900">${makeModelStr}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Registration No</strong><span class="font-mono-code font-bold text-blue-700">${exhibit.veh_reg_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">VIN / Chassis</strong><span class="font-mono-code font-bold text-slate-800">${exhibit.veh_vin || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Engine Number</strong><span class="font-mono-code text-slate-800">${exhibit.veh_engine_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Year / Colour</strong><span class="text-slate-800">${exhibit.veh_year || 'N/A'} &bull; ${exhibit.veh_colour || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Keys &amp; Fuel</strong><span class="text-slate-800">${exhibit.veh_keys_recv ? ('Keys Recv' + (exhibit.veh_key_count ? ' (' + exhibit.veh_key_count + ')' : '')) : 'No Keys'} &bull; Fuel: ${exhibit.veh_fuel || 'N/A'}</span></div>
                        <div class="col-span-full"><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Registered Owner / Driver</strong><span class="text-slate-800">Owner: ${exhibit.veh_owner || 'N/A'} (${exhibit.veh_owner_contact || 'N/A'}) &bull; Driver: ${exhibit.veh_driver || 'N/A'}</span></div>
                        ${exhibit.veh_damage ? `<div class="col-span-full"><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Damage Notes</strong><span class="text-amber-800 italic">${exhibit.veh_damage}</span></div>` : ''}
                    </div>
                `;
            } else if (cat.includes('weapon') || cat.includes('firearm') || exhibit.wpn_model || exhibit.wpn_serial_no) {
                specsTitle.textContent = 'Firearms & Ballistics Specifications';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Weapon Model</strong><span class="font-bold text-slate-900">${exhibit.wpn_model || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Serial Number</strong><span class="font-mono-code font-bold text-amber-700">${exhibit.wpn_serial_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Calibre / Gauge</strong><span class="font-mono-code text-slate-800">${exhibit.wpn_calibre || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Loaded Status</strong><span class="font-bold ${exhibit.wpn_loaded_status === 'Loaded' ? 'text-rose-600' : 'text-slate-700'}">${exhibit.wpn_loaded_status || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Condition</strong><span class="text-slate-800">${exhibit.wpn_condition || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Location Found</strong><span class="text-slate-800">${exhibit.wpn_location_found || 'N/A'}</span></div>
                    </div>
                `;
            } else if (cat.includes('devic') || cat.includes('electron') || cat.includes('digital') || exhibit.dev_model || exhibit.dev_imei1) {
                specsTitle.textContent = 'Digital & Mobile Forensic Device Profile';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Device Model</strong><span class="font-bold text-slate-900">${exhibit.dev_model || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Serial Number</strong><span class="font-mono-code text-slate-800">${exhibit.dev_serial_no || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Primary IMEI</strong><span class="font-mono-code font-bold text-purple-700">${exhibit.dev_imei1 || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Secondary IMEI</strong><span class="font-mono-code text-purple-700">${exhibit.dev_imei2 || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Storage Capacity</strong><span class="font-mono-code text-slate-800">${exhibit.dev_storage || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">MAC / Network</strong><span class="font-mono-code text-slate-800">${exhibit.dev_mac_address || 'N/A'}</span></div>
                    </div>
                `;
            } else if (cat.includes('drug') || cat.includes('narcotic') || exhibit.drug_classification) {
                specsTitle.textContent = 'Narcotics Classification & Profile';
                specsBody.innerHTML = `
                    <div class="grid grid-cols-2 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Drug Classification</strong><span class="font-bold text-emerald-800 font-mono-code">${exhibit.drug_classification || 'N/A'}</span></div>
                        <div><strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Commodity Profile</strong><span class="font-bold text-slate-800">${exhibit.commodity_name || 'N/A'}</span></div>
                    </div>
                `;
            } else {
                specsTitle.textContent = 'General Exhibit Particulars';
                specsBody.innerHTML = `
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 text-slate-700">
                        <strong class="text-slate-400 font-mono-code uppercase block text-[9.5px]">Extended Description</strong>
                        <span>${exhibit.other_exhibit_description || exhibit.commodity_name || 'N/A'}</span>
                    </div>
                `;
            }

            // Associated Suspects List
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
                    card.className = 'bg-slate-50 rounded-xl p-4 border border-slate-200/80 space-y-3 hover:border-emerald-300 transition-colors';
                    const mugSrc = resolveImageUrl(s.mugshot);

                    const roleLower = (s.role || '').toLowerCase();
                    const isPrimary = roleLower.includes('primary') || roleLower.includes('sole');
                    const roleBadgeClass = isPrimary
                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                        : 'bg-emerald-50 text-emerald-800 border-emerald-200';

                    const dispLower = (s.suspect_disposition || '').toLowerCase();
                    let dispBadgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                    if (dispLower.includes('custody') || dispLower.includes('remand')) {
                        dispBadgeClass = 'bg-rose-50 text-rose-800 border-rose-200';
                    } else if (dispLower.includes('bail') || dispLower.includes('released')) {
                        dispBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                    }

                    card.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-800 font-extrabold text-sm shrink-0 overflow-hidden shadow-2xs cursor-zoom-in">
                                    ${mugSrc ? `
                                        <img src="${mugSrc}" alt="${s.name}" class="w-full h-full object-cover" onclick="openImageLightbox('${mugSrc}', '${s.name} Mugshot')">
                                    ` : `
                                        <span class="text-emerald-800 font-mono-code font-extrabold text-sm">${(s.name || '--').substring(0, 2).toUpperCase()}</span>
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
                                    ${s.alias ? `<div class="text-[11px] text-slate-500 font-mono-code">Alias: <span class="font-bold text-slate-700">"${s.alias}"</span></div>` : ''}
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 font-mono-code block mb-1">Disposition</span>
                                <span class="text-[10px] px-2.5 py-0.5 rounded-full font-mono-code font-bold uppercase inline-block border ${dispBadgeClass}">
                                    ${s.suspect_disposition || 'N/A'}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-xs">
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200/80">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 font-mono-code block">Ghana Card</span>
                                <span class="font-bold text-slate-900 font-mono-code text-[11px] block truncate">${s.ghana_card || 'N/A'}</span>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200/80">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 font-mono-code block">Gender</span>
                                <span class="font-bold text-slate-900 text-[11px] block">${s.gender || 'N/A'}</span>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200/80">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 font-mono-code block">Age</span>
                                <span class="font-bold text-slate-900 text-[11px] block">${s.age ? `${s.age} Yrs` : 'N/A'}</span>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200/80">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 font-mono-code block">Nationality</span>
                                <span class="font-bold text-slate-900 text-[11px] block truncate">${s.nationality || 'N/A'}</span>
                            </div>
                        </div>
                    `;
                    suspectsList.appendChild(card);
                });
            } else {
                suspectsList.innerHTML = `
                    <div class="text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-xs">No Associated Suspects Logged</div>
                            <div class="text-[11px] text-slate-500">This exhibit currently has no linked suspect profile on record.</div>
                        </div>
                    </div>
                `;
            }

            document.getElementById('modalRemark').textContent = exhibit.exhibit_remark ? `"${exhibit.exhibit_remark}"` : 'No lodgement remarks recorded.';

            const labRemarkContainer = document.getElementById('modalLabRemarkContainer');
            if (exhibit.lab_remark) {
                document.getElementById('modalLabRemark').textContent = `"${exhibit.lab_remark}"`;
                labRemarkContainer.classList.remove('hidden');
            } else {
                labRemarkContainer.classList.add('hidden');
            }

            const isAlreadyClassified = Boolean(exhibit.drug_classification && exhibit.drug_classification !== '' && exhibit.exhibit_type && exhibit.exhibit_type !== '');
            const inspectClassifyBtn = document.getElementById('inspectClassifyBtn');
            if (inspectClassifyBtn) {
                inspectClassifyBtn.classList.toggle('hidden', isAlreadyClassified);
            }
            const inspectUnassignedClassifyBtn = document.getElementById('inspectUnassignedClassifyBtn');
            if (inspectUnassignedClassifyBtn) {
                inspectUnassignedClassifyBtn.classList.toggle('hidden', isAlreadyClassified);
            }

            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeExhibitModal() {
            document.getElementById('exhibitModal').classList.add('hidden');
        }

        function buildVaultSeizureQrPayload(exhibit, propertyId, status) {
            if (!exhibit) return 'NACOC SEIZED PROPERTY\nPROPERTY ID: ' + (propertyId || 'N/A');

            const clean = (v, max) => String(v == null ? '' : v)
                .replace(/[^\x20-\x7E]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, max || 120);

            const propId = clean(propertyId || exhibit.property_id || '', 40);
            const liveStatus = 'Seized';
            const caseRef = clean(exhibit.case_number || (exhibit.single_suspect_case && exhibit.single_suspect_case.case_number) || (exhibit.multiple_suspect_case && exhibit.multiple_suspect_case.case_number) || '', 60);

            let exhName = clean(exhibit.exhibit_number || '', 40);
            if (exhibit.commodity_name) {
                exhName = exhName ? `${exhName} (${clean(exhibit.commodity_name, 60)})` : clean(exhibit.commodity_name, 80);
            }

            const category = clean(exhibit.exhibit_category || '', 60);
            const classification = clean(exhibit.drug_classification || exhibit.exhibit_type || '', 60);

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

            let qtyStr = '';
            if (exhibit.seizure_weight) {
                qtyStr = clean(exhibit.seizure_weight + ' ' + (exhibit.seizure_unit || 'kg'), 40);
            } else if (exhibit.amount) {
                qtyStr = clean(String(exhibit.amount), 40);
            } else if (exhibit.seizure_quantity) {
                qtyStr = clean(exhibit.seizure_quantity + ' ' + (exhibit.seizure_unit || 'pcs'), 40);
            }

            let valStr = '';
            const rawValForTag = (exhibit.seizure_value && exhibit.seizure_value !== 'N/A') ? exhibit.seizure_value : exhibit.amount;
            if (rawValForTag) {
                const cleanTagVal = String(rawValForTag).replace(/,/g, '').trim();
                valStr = !isNaN(Number(cleanTagVal)) && cleanTagVal !== ''
                    ? 'GHS ' + Number(cleanTagVal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
                    : 'GHS ' + rawValForTag;
            }

            const packaging = clean(exhibit.seizure_packaging || '', 60);
            const concealment = clean(exhibit.seizure_concealment || '', 100);
            const location = clean(exhibit.source_of_exhibit || exhibit.source_of_exhibits || exhibit.seizure_location || '', 100);
            const officer = clean(exhibit.seizure_officer || '', 120);
            const sDate = exhibit.seizure_date || (exhibit.created_at ? exhibit.created_at.substring(0, 10) : '');
            const sTime = exhibit.seizure_time || '';
            const dateTime = clean((sDate + (sTime ? ' @ ' + sTime : '')).trim(), 40);

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
                if (typeof onReady === 'function') onReady(null);
                return;
            }

            size = size || 180;

            try {
                new QRCode(qrContainer, {
                    text: qrPayload,
                    width: size,
                    height: size,
                    colorDark: "#0B6B3A",
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
                        } catch (e) {}
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

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, w, h);

            ctx.strokeStyle = '#CBD5E1';
            ctx.lineWidth = 2;
            ctx.strokeRect(1, 1, w - 2, h - 2);

            ctx.textAlign = 'center';
            ctx.fillStyle = '#EF4444';
            ctx.font = `900 26px 'Bookman Old Style', Bookman, 'URW Bookman', Georgia, serif`;
            ls('4px');
            ctx.fillText('SEIZED PROPERTY', w / 2, 52);

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

            if (data.qrSource) {
                const drawQr = (src) => {
                    try {
                        const q = 140;
                        ctx.drawImage(src, w - q - 40, 116, q, q);
                    } catch (e) {}
                };

                if (typeof HTMLImageElement !== 'undefined' && data.qrSource instanceof HTMLImageElement && !data.qrSource.complete) {
                    data.qrSource.onload = () => drawQr(data.qrSource);
                } else {
                    drawQr(data.qrSource);
                }
            }

            ctx.fillStyle = '#475569';
            ctx.font = `700 12px ${sans}`;
            ctx.textAlign = 'center';
            ls('3px');
            ctx.fillText('SCAN TO VIEW PROPERTY DETAILS', w / 2, h - 20);
            ls('0px');

            return targetCanvas;
        }

        function renderInspectBadgePreview(exhibit) {
            if (!exhibit) return;
            const previewCard = document.getElementById('inspectBadgePreviewCard');
            const unassignedCard = document.getElementById('inspectBadgeUnassignedCard');
            const statusPill = document.getElementById('inspectBadgeStatusPill');
            const noticeText = document.getElementById('inspectBadgeNoticeText');
            const modalBtnText = document.getElementById('inspectOpenQrModalBtnText');

            const isAlreadyGenerated = Boolean(
                exhibit.tag_image ||
                exhibit.tag_url ||
                (exhibit.property_id && exhibit.property_id !== 'NACOC/EMU/00241' && exhibit.property_id.trim() !== '')
            );

            if (isAlreadyGenerated) {
                if (previewCard) previewCard.classList.remove('hidden');
                if (unassignedCard) unassignedCard.classList.add('hidden');

                const propId = exhibit.property_id;

                if (statusPill) {
                    statusPill.textContent = "Officially Sealed • Seized";
                    statusPill.className = "text-[10px] font-mono-code font-bold uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 inline-block mt-0.5";
                }
                if (noticeText) {
                    noticeText.textContent = `Permanent official QR tag • Property ID: ${propId}`;
                }
                if (modalBtnText) {
                    modalBtnText.textContent = "View Sealed Tag";
                }

                drawDynamicInspectBadge(exhibit, propId);
            } else {
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
                btn.innerHTML = `<span>Saving...</span>`;
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

            if (isAlreadyGenerated) {
                propIdInput.disabled = true;
                propIdInput.readOnly = true;
                propIdInput.className = "w-full px-3 py-1.5 bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold font-mono-code text-slate-500 cursor-not-allowed select-none";

                if (randomizeBtn) {
                    randomizeBtn.disabled = true;
                    randomizeBtn.classList.add('hidden');
                }

                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.className = "px-5 py-2 bg-slate-200 text-slate-500 border border-slate-300 text-xs font-bold rounded-xl cursor-not-allowed flex items-center gap-2";
                    saveBtn.innerHTML = `<i data-lucide="lock" class="w-4 h-4"></i><span>Badge Assigned &amp; Locked</span>`;
                }

                if (lockedAlert) lockedAlert.classList.remove('hidden');
                if (lockedPropId) lockedPropId.textContent = propId;
                if (lockedPill) lockedPill.classList.remove('hidden');
                if (modalSubtitle) modalSubtitle.textContent = 'Official Seized Property tag has already been generated and locked';
            } else {
                propIdInput.disabled = false;
                propIdInput.readOnly = false;
                propIdInput.className = "rep-input w-full px-3 py-1.5 text-xs font-bold font-mono-code";

                if (randomizeBtn) {
                    randomizeBtn.disabled = false;
                    randomizeBtn.classList.remove('hidden');
                }

                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.className = "rep-btn-green px-5 py-2 text-xs font-extrabold rounded-xl flex items-center gap-2 cursor-pointer";
                    saveBtn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Assign &amp; Save to Exhibit</span>`;
                }

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
            btn.innerHTML = `<span>Assigning...</span>`;

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
                    activeVaultQrExhibit.custodial_status = data.exhibit?.custodial_status || 'Seized';
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
                    openVaultQrModal(activeVaultQrExhibit);
                } else {
                    showVaultToast(data.message || 'Failed to update exhibit QR tag', 'error');
                }
            } catch (e) {
                showVaultToast('Network error while saving QR tag', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (window.lucide) window.lucide.createIcons();
            }
        }

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
                    document.getElementById('classifyDrugSchedule')?.focus();
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
            btn.innerHTML = `<span>Saving...</span>`;

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
                showVaultToast('Network error while saving classification', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (window.lucide) window.lucide.createIcons();
            }
        }

        function updateExhibitDomBadges(exhibit) {
            const containers = document.querySelectorAll(`[data-exhibit-id="${exhibit.id}"]`);
            containers.forEach(el => {
                if (exhibit.property_id) {
                    const propBadges = el.querySelectorAll('[data-vault-prop-badge]');
                    propBadges.forEach(btn => {
                        btn.className = 'px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center gap-1 shadow-2xs cursor-pointer';
                        btn.title = 'Seized Property Tag Assigned - Click to view/reprint';
                        btn.innerHTML = `<i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i><span>${exhibit.property_id}</span>`;
                    });

                    const footerQrBtns = el.querySelectorAll('[data-vault-footer-qr-btn]');
                    footerQrBtns.forEach(btn => {
                        btn.innerHTML = `<i data-lucide="qr-code" class="w-3 h-3"></i><span>QR Tag</span>`;
                    });
                }

                if (exhibit.drug_classification && exhibit.drug_classification !== 'N/A') {
                    const classifyBadges = el.querySelectorAll('.vault-drug-badge, [title*="Drug Classification"]');
                    classifyBadges.forEach(badge => {
                        const span = badge.querySelector('span.truncate') || badge.querySelector('span') || badge;
                        span.textContent = exhibit.drug_classification;
                        badge.title = `Drug Classification: ${exhibit.drug_classification} - Click to reclassify`;
                    });

                    const unclassifiedBtns = el.querySelectorAll('.vault-classify-btn');
                    unclassifiedBtns.forEach(btn => {
                        if (btn.textContent.includes('Assign Drug') || btn.title.includes('Assign Drug Classification')) {
                            btn.className = "vault-drug-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[10px] font-mono-code font-bold transition-colors cursor-pointer";
                            btn.title = `Drug Classification: ${exhibit.drug_classification} - Click to reclassify`;
                            btn.onclick = () => openVaultClassifyModal(exhibit, true);
                            btn.innerHTML = `<i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i><span class="truncate max-w-[150px]">${exhibit.drug_classification}</span>`;
                        }
                    });
                }

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

                if (exhibit.commodity_name) {
                    const titleEl = el.querySelector('[data-commodity-title]') || el.querySelector('h3');
                    if (titleEl) titleEl.textContent = exhibit.commodity_name;
                }
            });

            if (window.lucide) window.lucide.createIcons();
        }

        function showVaultToast(message, type = 'success') {
            const container = document.getElementById('vaultToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            const cardClass = isSuccess
                ? 'bg-emerald-700 text-white border-emerald-600'
                : 'bg-rose-700 text-white border-rose-600';

            toast.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold transition-all duration-300 transform translate-y-2 opacity-0 ${cardClass}`;
            toast.innerHTML = `
                <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center font-bold text-xs">${isSuccess ? '✓' : '!'}</span>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3800);
        }

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
