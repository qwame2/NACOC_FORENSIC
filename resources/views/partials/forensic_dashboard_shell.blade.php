@php
    $authUser = auth()->user();
    $cases = $cases ?? \App\Models\ForensicCase::allCases();
    $cfg = $dashboardConfig ?? [];

    $unitKey = $cfg['unitKey'] ?? 'forensic_head';
    $userUnit = $cfg['userUnit'] ?? 'Forensic Directorate';
    $unitBadge = $cfg['unitBadge'] ?? 'FORENSIC DIRECTORATE';
    $unitSubtitle = $cfg['unitSubtitle'] ?? 'Forensic Directorate Supreme Command';
    $locationNoun = $cfg['locationNoun'] ?? 'Forensic Cases';
    $pageTitle = $cfg['pageTitle'] ?? 'NACOC FORENSIC | Forensic Directorate Command Dashboard';
    $homeUrl = $cfg['homeUrl'] ?? url('/Forensic_Head');
    $casesUrl = $cfg['casesUrl'] ?? url('/Forensic_Head/cases');
    $createUrl = $cfg['createUrl'] ?? null;
    $createLabel = $cfg['createLabel'] ?? 'New Case Entry';
    $reportUrl = $cfg['reportUrl'] ?? url('/Report');
    $notificationsUrl = $cfg['notificationsUrl'] ?? url('/Forensic_Head/notifications');
    $permissionsUrl = match ($unitKey) {
        'crime_scene_support' => url('/Crime_Scene_Support/permissions'),
        'lab_analysis'        => url('/Lab_Analysis/permissions'),
        'evidence_management' => url('/Evidence_Management/permissions'),
        'digital_forensics'   => url('/Digital_Forensics/permissions'),
        default               => url('/Forensic_Head/permissions'),
    };
    $tabs = $cfg['tabs'] ?? [];
    $defaultUnitFilter = $cfg['defaultUnitFilter'] ?? '';
    $vaultPendingBadge = $cfg['vaultPendingBadge'] ?? 0;

    $headName = $authUser->fullname ?? session('user_name') ?? ($cfg['defaultHeadName'] ?? 'Commanding Officer');
    $officerRole = ($authUser && $authUser->is_head)
        ? ($cfg['headRoleLabel'] ?? 'Unit Head')
        : ($cfg['officerRoleLabel'] ?? 'Commanding Officer');

    $totalCasesCount = $cases->count();
    $pendingCasesCount = $pendingCasesCount
        ?? $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['awaiting sign-off', 'pending approval', 'pending'], true))->count();
    $inProgressCasesCount = $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['in progress', 'under investigation', 'under multi-unit processing', 'active'], true))->count();
    $approvedCasesCount = $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['approved', 'completed', 'authorized'], true))->count();
    $totalExhibitsCount = \App\Models\Exhibit::count();
    $completionRatePct = $totalCasesCount > 0 ? (int) round(($approvedCasesCount / $totalCasesCount) * 100) : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $pageTitle }}</title>

  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
      <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
  @endif

  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
  <script src="https://unpkg.com/lucide@latest" defer></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --shell-bg: #d7e0db;
      --shell-chrome: #d2dcd7;
      --tab-active: #eef4f1;
      --tab-inactive: #c5d0ca;
      --map-surface-top: #d8ebe6;
      --map-surface-bottom: #d3e5e0;
      --strip-surface: #e4eeeb;
      --card-surface: #f4f7f5;
      --stats-surface: #edf3f0;
      --ink-primary: #0f172a;
      --ink-secondary: #334155;
      --ink-muted: #475569;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: var(--shell-bg);
      color: var(--ink-primary);
    }

    .font-mono-code {
      font-family: 'JetBrains Mono', monospace;
    }

    .tabular-nums {
      font-variant-numeric: tabular-nums;
    }

    /* Subtle coordinate grid in empty map API canvas (zero fake map geometry) */
    .map-api-canvas-grid {
      background-color: var(--map-surface-top);
      background-image:
        linear-gradient(180deg, rgba(216, 235, 230, 0.92) 0%, rgba(211, 229, 224, 0.96) 100%),
        radial-gradient(rgba(71, 85, 105, 0.24) 1px, transparent 1px);
      background-size: 100% 100%, 24px 24px;
    }

    .browser-tab {
      position: relative;
      border-top-left-radius: 14px;
      border-top-right-radius: 14px;
      transition: background-color 0.15s ease, color 0.15s ease;
    }

    .browser-tab-active {
      background-color: var(--tab-active);
      color: #0f172a;
      box-shadow: 0 -2px 10px rgba(15, 23, 42, 0.05);
    }

    .browser-tab-inactive {
      background-color: rgba(203, 215, 209, 0.65);
      color: #334155;
    }

    .browser-tab-inactive:hover {
      background-color: rgba(226, 235, 230, 0.9);
      color: #0f172a;
    }

    /* Explicit keyboard focus ring */
    a:focus-visible,
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
      outline: 2px solid #0f766e;
      outline-offset: 2px;
    }

    button:not(:disabled),
    a[href],
    select:not(:disabled) {
      cursor: pointer;
    }

    button:disabled {
      cursor: not-allowed;
      opacity: 0.55;
    }

    .docket-row {
      transition: background-color 0.14s ease;
    }

    .docket-row.is-selected {
      background-color: #e3eee9;
    }

    /* Hide native scrollbar neatly on horizontal tab strips */
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
  </style>
</head>
<body class="min-h-screen w-full flex flex-col antialiased selection:bg-teal-100 selection:text-slate-900 bg-[#e9f0ed]">

  <div class="w-full min-h-screen flex flex-col bg-[#e9f0ed]">
    <!-- Full-Viewport Application Shell -->
    <div class="w-full flex-1 flex flex-col overflow-x-hidden bg-[#e9f0ed]">

      <!-- ====================================================== -->
      <!-- 1. TOP CHROME HEADER & BROWSER-STYLE COMMAND TABS      -->
      <!-- ====================================================== -->
      <header class="bg-[#d2dcd7] border-b border-[#bfcbc5] px-3 sm:px-5 pt-2.5">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 lg:pb-0">

          <!-- Left Brand & Quick Icon Actions -->
          <div class="flex items-center gap-2 shrink-0 lg:pb-2">
            <a href="{{ $homeUrl }}"
               class="w-11 h-11 rounded-2xl bg-white border border-slate-300/80 p-1 flex items-center justify-center shadow-2xs shrink-0"
               title="NACOC Forensic Home"
               aria-label="NACOC Forensic Command Home">
              <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" width="36" height="36" class="w-9 h-9 object-contain">
            </a>

            <div class="hidden sm:flex items-center gap-1.5">
              <a href="{{ $casesUrl }}"
                 class="w-11 h-11 rounded-full bg-white/80 hover:bg-white text-slate-800 border border-slate-300/60 flex items-center justify-center transition-colors shadow-2xs"
                 title="Case Register"
                 aria-label="Open Case Register">
                <i data-lucide="folder-search" class="w-4 h-4"></i>
              </a>
              <a href="{{ $reportUrl }}"
                 class="w-11 h-11 rounded-full bg-white/80 hover:bg-white text-slate-800 border border-slate-300/60 flex items-center justify-center transition-colors shadow-2xs"
                 title="Reports &amp; Audit"
                 aria-label="Open Reports and Audit">
                <i data-lucide="file-text" class="w-4 h-4"></i>
              </a>
              <button type="button"
                      onclick="refreshRealtimeData()"
                      class="w-11 h-11 rounded-full bg-white/80 hover:bg-white text-slate-800 border border-slate-300/60 flex items-center justify-center transition-colors shadow-2xs"
                      title="Sync Forensic Database Telemetry"
                      aria-label="Sync Forensic Database Telemetry">
                <i data-lucide="refresh-cw" class="w-4 h-4" id="syncSpinner"></i>
              </button>
            </div>
          </div>

          <!-- Center Browser-Style Navigation Tabs -->
          <nav aria-label="Unit Command Navigation" class="order-3 lg:order-2 w-full lg:w-auto flex items-end gap-1.5 overflow-x-auto no-scrollbar pt-1">
            @foreach($tabs as $tab)
              @php $isActiveTab = !empty($tab['active']); @endphp
              <a href="{{ $tab['url'] }}"
                 class="browser-tab {{ $isActiveTab ? 'browser-tab-active font-semibold' : 'browser-tab-inactive font-medium' }} min-h-[44px] px-4 py-2.5 text-xs sm:text-[13px] whitespace-nowrap flex items-center gap-2 shrink-0">
                <i data-lucide="{{ $tab['icon'] ?? 'layout-dashboard' }}" class="w-3.5 h-3.5 text-slate-700 shrink-0"></i>
                <span>{{ $tab['label'] }}</span>
                @if(!empty($tab['badge']) && (int)$tab['badge'] > 0)
                  <span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-mono-code font-bold leading-none">
                    {{ $tab['badge'] }}
                  </span>
                @endif
              </a>
            @endforeach

            @if($createUrl)
              <a href="{{ $createUrl }}"
                 class="mb-1 w-11 h-11 rounded-full bg-white/80 hover:bg-white text-slate-900 border border-slate-300/70 flex items-center justify-center transition-colors shrink-0 shadow-2xs"
                 title="{{ $createLabel }}"
                 aria-label="{{ $createLabel }}">
                <i data-lucide="plus" class="w-4 h-4"></i>
              </a>
            @endif
          </nav>

          <!-- Right Alerts, Profile & Sign Out -->
          <div class="order-2 lg:order-3 flex items-center gap-2 shrink-0 lg:pb-2">
            @if($authUser && $authUser->is_head)
              <a href="{{ $permissionsUrl }}"
                 class="min-h-[44px] px-3 rounded-full bg-white/85 hover:bg-white text-slate-800 border border-slate-300/70 flex items-center gap-1.5 text-xs font-semibold transition-colors shadow-2xs"
                 title="Unit Staff Permissions"
                 aria-label="Open Unit Staff Permissions">
                <i data-lucide="shield" class="w-4 h-4 text-emerald-700 shrink-0"></i>
                <span class="hidden md:inline">Permissions</span>
              </a>
            @endif
            <a href="{{ $notificationsUrl }}"
               class="min-h-[44px] px-3 rounded-full bg-white/85 hover:bg-white text-slate-800 border border-slate-300/70 flex items-center gap-1.5 text-xs font-semibold transition-colors shadow-2xs"
               title="Unit Notifications"
               aria-label="Open Unit Notifications">
              <i data-lucide="bell" class="w-4 h-4 text-amber-700 shrink-0"></i>
              <span class="hidden md:inline">Notifications</span>
            </a>

            <div class="flex items-center gap-2.5 pl-1">
              <div class="w-11 h-11 rounded-full bg-slate-900 text-white border-2 border-white flex items-center justify-center font-bold text-xs shadow-2xs overflow-hidden shrink-0">
                @if ($authUser && $authUser->avatar)
                  <img src="{{ asset($authUser->avatar) }}" alt="{{ $headName }}" width="44" height="44" class="w-full h-full object-cover">
                @else
                  {{ strtoupper(substr(trim($headName), 0, 2)) ?: 'NC' }}
                @endif
              </div>
              <div class="hidden xl:block text-left leading-tight">
                <div class="text-xs font-semibold text-slate-900">{{ $headName }}</div>
                <div class="text-[11px] font-medium text-slate-700">{{ $officerRole }}</div>
              </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
              @csrf
              <button type="submit"
                      class="w-11 h-11 rounded-full bg-white/80 hover:bg-rose-50 hover:text-rose-800 text-slate-800 border border-slate-300/70 flex items-center justify-center transition-colors shadow-2xs"
                      title="Sign Out"
                      aria-label="Sign Out">
                <i data-lucide="log-out" class="w-4 h-4"></i>
              </button>
            </form>
          </div>
        </div>

        <!-- Compact Unit Identity & Live Sync Strip (Preserves exact HQ Identity) -->
        <div class="bg-[#eef4f1] -mx-3 sm:-mx-5 px-4 sm:px-6 py-2 border-t border-white/70 flex flex-wrap items-center justify-between gap-2 text-xs">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 text-white text-[10px] font-mono-code font-bold uppercase tracking-wider">
              {{ $unitBadge }}
            </span>
            <h1 class="text-xs sm:text-sm font-semibold text-slate-900 tracking-tight">
              {{ $unitSubtitle }}
            </h1>
          </div>
          <div class="flex items-center gap-3 text-[11px] font-medium text-slate-700">
            <span id="telemetrySyncBadge" class="inline-flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
              <span id="telemetryLastUpdated">Live telemetry ready</span>
            </span>
          </div>
        </div>
      </header>

      <main class="flex-1 w-full flex flex-col">
        <!-- ====================================================== -->
        <!-- 2. UPPER MAP STAGE (EMPTY CONTAINER FOR USER MAP API)  -->
        <!-- ====================================================== -->
        <section aria-label="Forensic Cases by Location Map Stage"
                 class="relative map-api-canvas-grid flex-1 min-h-[440px] sm:min-h-[480px] flex flex-col justify-between p-4 sm:p-5 overflow-hidden">

          <!-- TODO: Mount external Map API inside #forensicMapContainer.
               Example later integration:
                 const map = new MapProvider.Map('forensicMapContainer', { ... });
                 window.ForensicMapStage.mount(map, { zoomIn: () => map.zoomIn(), zoomOut: () => map.zoomOut(), resetView: () => map.reset() });
          -->
          <div id="forensicMapContainer"
               data-map-api-slot="pending"
               role="region"
               aria-label="External Map API Container (Ready for Integration)"
               class="absolute inset-0 z-0">
          </div>

          <!-- Top Floating Overlay: Title Banner Left + Filter Pills Right -->
          <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
            <!-- Glassmorphic Element #1 (R-10 compliant) -->
            <div class="inline-flex items-center gap-3 self-start px-5 py-2.5 rounded-full backdrop-blur-md bg-white/75 border border-white/85 shadow-xs">
              <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                {{ $locationNoun }} <span class="font-normal text-slate-700">by Location</span>
              </h2>
              <span id="mappedCaseCountPill" class="px-2.5 py-0.5 rounded-full bg-slate-900 text-white text-[11px] font-mono-code font-semibold tabular-nums">
                {{ $totalCasesCount }} {{ $totalCasesCount === 1 ? 'Docket' : 'Dockets' }}
              </span>
            </div>

            <!-- Floating Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
              <button type="button"
                      id="resetFiltersBtn"
                      onclick="resetDashboardFilters()"
                      class="w-11 h-11 rounded-full bg-white/95 hover:bg-white text-slate-800 border border-slate-300/80 shadow-2xs flex items-center justify-center transition-colors"
                      title="Reset all location and docket filters"
                      aria-label="Reset all location and docket filters">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
              </button>

              <label class="sr-only" for="filterPeriod">Filter by timeframe</label>
              <select id="filterPeriod"
                      onchange="applyDashboardFilters()"
                      class="min-h-[44px] px-3.5 py-2 rounded-full bg-white/95 border border-slate-300/80 text-xs font-medium text-slate-900 shadow-2xs">
                <option value="all">All Periods</option>
                <option value="today">Logged Today</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
              </select>

              <label class="sr-only" for="filterRegion">Filter by Ghana region</label>
              <select id="filterRegion"
                      onchange="applyDashboardFilters()"
                      class="min-h-[44px] px-3.5 py-2 rounded-full bg-white/95 border border-slate-300/80 text-xs font-medium text-slate-900 shadow-2xs">
                <option value="all">All Regions</option>
                <option value="Greater Accra">Greater Accra</option>
                <option value="Ashanti">Ashanti</option>
                <option value="Western">Western</option>
                <option value="Eastern">Eastern</option>
                <option value="Central">Central</option>
                <option value="Volta">Volta</option>
                <option value="Northern">Northern</option>
                <option value="Bono">Bono</option>
                <option value="Upper East">Upper East</option>
                <option value="Upper West">Upper West</option>
              </select>

              <label class="sr-only" for="filterUnit">Filter by forensic unit</label>
              <select id="filterUnit"
                      onchange="applyDashboardFilters()"
                      class="min-h-[44px] px-3.5 py-2 rounded-full bg-white/95 border border-slate-300/80 text-xs font-medium text-slate-900 shadow-2xs">
                <option value="all">All Units</option>
                <option value="Crime Scene Support">Crime Scene Support</option>
                <option value="Evidence Management">Evidence Management</option>
                <option value="Lab Analysis">Lab Analysis</option>
                <option value="Digital &amp; Physical Forensics">Digital &amp; Physical Forensics</option>
                <option value="Forensic Directorate">Forensic Directorate</option>
              </select>

              <label class="sr-only" for="filterStatus">Filter by status</label>
              <select id="filterStatus"
                      onchange="applyDashboardFilters()"
                      class="min-h-[44px] px-3.5 py-2 rounded-full bg-white/95 border border-slate-300/80 text-xs font-medium text-slate-900 shadow-2xs">
                <option value="all">All Statuses</option>
                <option value="pending">Pending / Awaiting Sign-off</option>
                <option value="in_progress">Under Investigation / Active</option>
                <option value="approved">Approved / Completed</option>
              </select>

              <label class="sr-only" for="filterPriority">Filter by priority</label>
              <select id="filterPriority"
                      onchange="applyDashboardFilters()"
                      class="min-h-[44px] px-3.5 py-2 rounded-full bg-white/95 border border-slate-300/80 text-xs font-medium text-slate-900 shadow-2xs">
                <option value="all">All Priorities</option>
                <option value="CRITICAL">CRITICAL</option>
                <option value="HIGH">HIGH</option>
                <option value="MEDIUM">MEDIUM</option>
                <option value="LOW">LOW</option>
              </select>
            </div>
          </div>

          <!-- Center Stage: Honest Empty Map API Slot + Floating Selected Docket Card + Zoom Stack -->
          <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5 my-5">

            <!-- Left-Center Empty Map Integration Notice (Hides automatically when Map API mounts) -->
            <div id="forensicMapPlaceholder"
                 class="flex-1 flex items-center justify-center py-6 sm:py-10 pointer-events-none">
              <div class="max-w-md px-5 py-4 rounded-2xl border border-dashed border-slate-500/50 bg-white/65 text-center shadow-2xs">
                <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-[#dce9e4] text-slate-800 flex items-center justify-center border border-slate-300/80">
                  <i data-lucide="map-pin" class="w-4 h-4"></i>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                  Map API Container Ready
                </p>
                <p class="text-xs text-slate-700 mt-1">
                  This map section is intentionally left empty for your external Map API (<code class="font-mono-code text-[11px] bg-white/80 px-1 rounded">#forensicMapContainer</code>).
                </p>
              </div>
            </div>

            <!-- Right-Center Floating Selected Case Preview + Vertical Zoom Controls -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 self-stretch lg:self-auto lg:ml-auto">

              <!-- Glassmorphic Element #2: Selected Case Card (R-10 compliant) -->
              <article aria-label="Selected Forensic Docket Preview"
                       class="w-full sm:w-[340px] rounded-[24px] backdrop-blur-md bg-[#eef4ed]/90 border border-white/90 shadow-[0_18px_40px_-14px_rgba(15,23,42,0.18)] p-4 sm:p-5 flex flex-col justify-between gap-4">
                <div class="flex items-center justify-between gap-2">
                  <div class="inline-flex items-center gap-2">
                    <span id="previewPriorityDot" class="w-2.5 h-2.5 rounded-full bg-amber-600 shrink-0"></span>
                    <span id="previewStatusLabel" class="text-xs font-semibold text-slate-900">Awaiting Data</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <a id="previewHistoryLink"
                       href="{{ url('/history') }}"
                       class="w-11 h-11 rounded-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300/70 flex items-center justify-center transition-colors shadow-2xs"
                       title="Open Case Audit History"
                       aria-label="Open Case Audit History">
                      <i data-lucide="history" class="w-4 h-4"></i>
                    </a>
                    <a id="previewRegisterLink"
                       href="{{ $casesUrl }}"
                       class="w-11 h-11 rounded-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300/70 flex items-center justify-center transition-colors shadow-2xs"
                       title="Open Case Directory"
                       aria-label="Open Case Directory">
                      <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                  </div>
                </div>

                <div class="flex items-baseline justify-between gap-3">
                  <div class="min-w-0">
                    <h3 id="previewSuspectTitle" class="text-xl font-semibold text-slate-900 truncate">
                      No Docket Selected
                    </h3>
                    <p id="previewCaseSubline" class="text-xs font-medium text-slate-700 truncate mt-0.5">
                      Select a case row below to inspect
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <div class="flex items-center justify-end gap-1.5">
                      <span id="previewExhibitCount" class="text-2xl sm:text-3xl font-normal text-slate-900 tabular-nums leading-none">0</span>
                      <span id="previewExhibitDot" class="w-2 h-2 rounded-full bg-teal-600"></span>
                    </div>
                    <div class="text-[11px] font-medium text-slate-700 mt-1">Exhibits</div>
                  </div>
                </div>

                <div class="flex items-center justify-between gap-3 pt-1">
                  <div class="flex items-center gap-2 min-w-0 text-xs text-slate-800">
                    <i data-lucide="map-pin" class="w-4 h-4 text-slate-900 shrink-0"></i>
                    <span id="previewLocationLabel" class="truncate font-medium">Location pending</span>
                  </div>
                  <a id="previewReviewBtn"
                     href="{{ $casesUrl }}"
                     class="min-h-[44px] px-5 py-2.5 rounded-full bg-white hover:bg-slate-900 hover:text-white text-slate-900 border border-slate-300/80 text-xs font-semibold flex items-center justify-center transition-colors shrink-0 shadow-2xs">
                    Inspect
                  </a>
                </div>
              </article>

              <!-- Vertical Zoom Placeholder Stack (Disabled visibly until Map API connects - R-26) -->
              <div class="flex sm:flex-col items-center justify-center gap-1.5 bg-white/80 p-1.5 rounded-full border border-white/90 shadow-2xs self-start sm:self-center"
                   title="Map zoom controls activate once external Map API is connected">
                <button type="button"
                        id="mapZoomInBtn"
                        disabled
                        aria-disabled="true"
                        onclick="window.ForensicMapStage.zoomIn()"
                        class="w-11 h-11 rounded-full bg-white text-slate-800 border border-slate-200 flex items-center justify-center"
                        title="Zoom In (Awaiting Map API)"
                        aria-label="Zoom In (Awaiting Map API)">
                  <i data-lucide="plus" class="w-4 h-4"></i>
                </button>
                <button type="button"
                        id="mapZoomOutBtn"
                        disabled
                        aria-disabled="true"
                        onclick="window.ForensicMapStage.zoomOut()"
                        class="w-11 h-11 rounded-full bg-white text-slate-800 border border-slate-200 flex items-center justify-center"
                        title="Zoom Out (Awaiting Map API)"
                        aria-label="Zoom Out (Awaiting Map API)">
                  <i data-lucide="minus" class="w-4 h-4"></i>
                </button>
                <button type="button"
                        id="mapResetViewBtn"
                        disabled
                        aria-disabled="true"
                        onclick="window.ForensicMapStage.resetView()"
                        class="w-11 h-11 rounded-full bg-white text-slate-800 border border-slate-200 flex items-center justify-center"
                        title="Reset Map Viewport (Awaiting Map API)"
                        aria-label="Reset Map Viewport (Awaiting Map API)">
                  <i data-lucide="maximize" class="w-4 h-4"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom-Left Mini Overview Inset Placeholder -->
          <div class="relative z-10 flex items-end justify-between">
            <div id="forensicMiniMapContainer"
                 data-minimap-slot="pending"
                 class="hidden md:flex w-36 h-20 rounded-2xl border border-dashed border-slate-400/70 bg-[#c8dad6]/75 items-center justify-center px-3 text-center">
              <span class="text-[11px] font-medium text-slate-800">Overview Map Slot</span>
            </div>
            <div id="mapIntegrationHint" class="text-[11px] font-medium text-slate-700 ml-auto">
              Geospatial layer awaiting Map API configuration
            </div>
          </div>
        </section>

        <!-- ====================================================== -->
        <!-- 3. MIDDLE 6-METRIC FORENSIC TELEMETRY STRIP            -->
        <!-- ====================================================== -->
        <section aria-label="Forensic Key Performance Metrics"
                 class="bg-[#e3edeb] border-t border-b border-white/80 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-300/60">

          <!-- Metric 1: Avg Turnaround -->
          <div class="p-4 sm:p-5 flex flex-col justify-between gap-3">
            <div class="flex items-center justify-between gap-2">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkTurnaround" aria-label="Completed docket 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <div class="flex items-baseline gap-1">
                <span id="kpiTurnaroundValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">N/A</span>
                <span id="kpiTurnaroundUnit" class="text-xs font-medium text-slate-700"></span>
              </div>
              <span class="w-2 h-2 rounded-full bg-amber-600 shrink-0" title="Real turnaround indicator"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Avg Turnaround</div>
          </div>

          <!-- Metric 2: Completion Rate -->
          <div class="p-4 sm:p-5 flex flex-col justify-between gap-3">
            <div class="flex items-center justify-between gap-2">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkCompletion" aria-label="Completion rate 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <div class="flex items-baseline gap-1">
                <span id="kpiCompletionValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">{{ $completionRatePct }}</span>
                <span class="text-xs font-medium text-slate-700">%</span>
              </div>
              <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Completion Rate</div>
          </div>

          <!-- Metric 3: Under Investigation -->
          <button type="button"
                  onclick="quickFilterByStatus('in_progress')"
                  class="p-4 sm:p-5 flex flex-col justify-between gap-3 text-left hover:bg-white/40 transition-colors">
            <div class="flex items-center justify-between gap-2 w-full">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="microscope" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkInProgress" aria-label="Active investigations 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2 w-full">
              <div class="flex items-baseline gap-1">
                <span id="kpiInProgressValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">{{ $inProgressCasesCount }}</span>
                <span class="text-xs font-medium text-slate-700">Cases</span>
              </div>
              <span class="w-2 h-2 rounded-full bg-sky-600 shrink-0"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Active Investigation</div>
          </button>

          <!-- Metric 4: Pending Sign-off -->
          <button type="button"
                  onclick="quickFilterByStatus('pending')"
                  class="p-4 sm:p-5 flex flex-col justify-between gap-3 text-left hover:bg-white/40 transition-colors">
            <div class="flex items-center justify-between gap-2 w-full">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkPending" aria-label="Pending sign-off 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2 w-full">
              <div class="flex items-baseline gap-1">
                <span id="kpiPendingValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">{{ $pendingCasesCount }}</span>
                <span class="text-xs font-medium text-slate-700">Cases</span>
              </div>
              <span class="w-2 h-2 rounded-full bg-amber-600 shrink-0"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Pending Sign-off</div>
          </button>

          <!-- Metric 5: Seized Exhibits -->
          <div class="p-4 sm:p-5 flex flex-col justify-between gap-3">
            <div class="flex items-center justify-between gap-2">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="package" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkExhibits" aria-label="Logged exhibits 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
              <div class="flex items-baseline gap-1">
                <span id="kpiExhibitsValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">{{ $totalExhibitsCount }}</span>
                <span class="text-xs font-medium text-slate-700">Items</span>
              </div>
              <span class="w-2 h-2 rounded-full bg-teal-700 shrink-0"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Exhibits Seized</div>
          </div>

          <!-- Metric 6: Total Registered Dockets -->
          <button type="button"
                  onclick="quickFilterByStatus('all')"
                  class="p-4 sm:p-5 flex flex-col justify-between gap-3 text-left hover:bg-white/40 transition-colors">
            <div class="flex items-center justify-between gap-2 w-full">
              <span class="w-8 h-8 rounded-full bg-white/85 border border-slate-300/60 text-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="layers" class="w-4 h-4"></i>
              </span>
              <div class="w-24 h-8">
                <canvas id="sparkTotalCases" aria-label="Total forensic dockets 6-month trend"></canvas>
              </div>
            </div>
            <div class="flex items-baseline justify-between gap-2 w-full">
              <div class="flex items-baseline gap-1">
                <span id="kpiTotalCasesValue" class="text-2xl sm:text-3xl font-normal tracking-tight text-slate-900 tabular-nums">{{ $totalCasesCount }}</span>
                <span class="text-xs font-medium text-slate-700">Dockets</span>
              </div>
              <span class="w-2 h-2 rounded-full bg-slate-900 shrink-0"></span>
            </div>
            <div class="text-xs font-medium text-slate-700">Total Dockets</div>
          </button>
        </section>

        <!-- ====================================================== -->
        <!-- 4. BOTTOM SPLIT WORKSPACE: DOCKETS TABLE + STATS CARD  -->
        <!-- ====================================================== -->
        <section aria-label="Forensic Dockets and Summary Statistics"
                 class="p-4 sm:p-5 bg-[#dce7e3]/75 grid grid-cols-1 lg:grid-cols-12 gap-4">

          <!-- Left 8 Cols: Forensic Dockets Table -->
          <div class="lg:col-span-8 bg-[#f4f7f5] rounded-3xl border border-white shadow-2xs p-4 sm:p-5 flex flex-col justify-between gap-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <h2 class="text-lg sm:text-xl font-normal text-slate-900 tracking-tight">
                  My <strong class="font-bold">Dockets</strong>
                </h2>
                <span id="tableShowingBadge" class="px-2.5 py-0.5 rounded-full bg-[#e3ece8] text-slate-800 text-[11px] font-medium tabular-nums">
                  Loading...
                </span>
              </div>

              <div class="flex items-center gap-2 flex-1 sm:max-w-sm justify-end">
                <div class="relative flex-1">
                  <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-600 pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4"></i>
                  </span>
                  <input type="search"
                         id="docketSearchInput"
                         oninput="applyDashboardFilters()"
                         placeholder="Search dockets, suspects, locations..."
                         aria-label="Search forensic dockets"
                         class="w-full min-h-[44px] pl-10 pr-4 py-2 rounded-full bg-white border border-slate-300/80 text-xs text-slate-900 placeholder:text-slate-500">
                </div>
                <button type="button"
                        onclick="resetDashboardFilters()"
                        class="w-11 h-11 rounded-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300/80 flex items-center justify-center shrink-0 transition-colors"
                        title="Clear search and filters"
                        aria-label="Clear search and filters">
                  <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
              </div>
            </div>

            <!-- Table / Loading / Empty / Error Container (R-27) -->
            <div id="docketStateContainer" class="overflow-x-auto">
              <!-- Initial Loading State -->
              <div id="docketLoadingState" class="py-10 text-center text-xs text-slate-700 font-medium">
                Loading forensic telemetry from database...
              </div>

              <!-- Error State (Hidden by default) -->
              <div id="docketErrorState" class="hidden py-10 px-4 text-center rounded-2xl bg-rose-50 border border-rose-200">
                <p id="docketErrorMessage" class="text-sm font-semibold text-rose-900">Unable to load live forensic telemetry.</p>
                <button type="button"
                        onclick="refreshRealtimeData()"
                        class="mt-3 min-h-[44px] px-5 py-2 rounded-full bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors">
                  Retry Sync
                </button>
              </div>

              <!-- Empty State (Hidden by default) -->
              <div id="docketEmptyState" class="hidden py-10 px-4 text-center rounded-2xl bg-white/80 border border-slate-200">
                <p id="docketEmptyTitle" class="text-sm font-semibold text-slate-900">No forensic dockets match your current filters.</p>
                <p class="text-xs text-slate-700 mt-1">Adjust search terms or reset filters to inspect all registered cases.</p>
                <button type="button"
                        onclick="resetDashboardFilters()"
                        class="mt-3 min-h-[44px] px-5 py-2 rounded-full bg-white border border-slate-300 text-slate-900 text-xs font-semibold hover:bg-slate-50 transition-colors">
                  Clear Filters
                </button>
              </div>

              <!-- Live Forensic Dockets Table -->
              <table id="docketsTable" class="hidden w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 text-[11px] font-semibold text-slate-600">
                    <th class="py-2.5 pr-3 whitespace-nowrap">Docket &amp; Suspect</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Sector &amp; Exhibits</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Status</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Logged</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Age</th>
                    <th class="py-2.5 px-3 whitespace-nowrap">Unit</th>
                    <th class="py-2.5 pl-3 text-right whitespace-nowrap">Action</th>
                  </tr>
                </thead>
                <tbody id="docketsTableBody" class="divide-y divide-slate-200/70 text-xs text-slate-800">
                </tbody>
              </table>
            </div>

            <!-- Footer Toggle / View All Link -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200/70 text-xs">
              <span id="tablePaginationNote" class="text-slate-700 font-medium"></span>
              <div class="flex items-center gap-2 ml-auto">
                <button type="button"
                        id="toggleShowAllBtn"
                        onclick="toggleShowAllRows()"
                        class="hidden min-h-[44px] px-4 py-1.5 rounded-full bg-white hover:bg-slate-50 text-slate-900 border border-slate-300 text-xs font-semibold transition-colors">
                  Show All
                </button>
                <a href="{{ $casesUrl }}"
                   class="min-h-[44px] px-4 py-1.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                  <span>Full Directory</span>
                  <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
              </div>
            </div>
          </div>

          <!-- Right 4 Cols: Forensic Stats Card -->
          <aside aria-label="Forensic Breakdown Statistics"
                 class="lg:col-span-4 bg-[#eef4f1] rounded-3xl border border-white shadow-2xs p-4 sm:p-5 flex flex-col justify-between gap-5">
            <div class="space-y-4">
              <div class="flex items-center justify-between gap-2">
                <h2 class="text-lg sm:text-xl font-normal text-slate-900 tracking-tight">
                  Forensic <strong class="font-bold">Stats</strong>
                </h2>
                <span id="statsScopeCount" class="text-xs font-semibold text-slate-700 tabular-nums">0 Dockets</span>
              </div>

              <!-- Mode Switcher Pills -->
              <div class="inline-flex flex-wrap items-center gap-1.5 p-1 rounded-full bg-[#dde7e2] border border-slate-300/60" role="tablist" aria-label="Statistics breakdown dimension">
                <button type="button"
                        role="tab"
                        id="statTabSeverity"
                        aria-selected="true"
                        onclick="setStatsDimension('severity')"
                        class="min-h-[44px] px-4 py-1.5 rounded-full bg-white text-slate-900 text-xs font-semibold shadow-2xs transition-colors">
                  Severity
                </button>
                <button type="button"
                        role="tab"
                        id="statTabUnit"
                        aria-selected="false"
                        onclick="setStatsDimension('unit')"
                        class="min-h-[44px] px-4 py-1.5 rounded-full text-slate-800 hover:bg-white/60 text-xs font-medium transition-colors">
                  Unit
                </button>
                <button type="button"
                        role="tab"
                        id="statTabStatus"
                        aria-selected="false"
                        onclick="setStatsDimension('status')"
                        class="min-h-[44px] px-4 py-1.5 rounded-full text-slate-800 hover:bg-white/60 text-xs font-medium transition-colors">
                  Status
                </button>
              </div>
            </div>

            <!-- 3 Prominent Metric Columns + Multi-Segment Bar -->
            <div class="space-y-5 my-auto">
              <div id="statsThreeCols" class="grid grid-cols-3 gap-3 items-end">
                <div class="space-y-1">
                  <div class="flex items-baseline gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 shrink-0"></span>
                    <span id="statVal1" class="text-2xl sm:text-3xl font-normal text-slate-900 tabular-nums leading-none">0</span>
                  </div>
                  <div id="statLbl1" class="text-xs font-medium text-slate-700 truncate">High / Critical</div>
                </div>

                <div class="space-y-1">
                  <div class="flex items-baseline gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-600 shrink-0"></span>
                    <span id="statVal2" class="text-2xl sm:text-3xl font-normal text-slate-900 tabular-nums leading-none">0</span>
                  </div>
                  <div id="statLbl2" class="text-xs font-medium text-slate-700 truncate">Medium</div>
                </div>

                <div class="space-y-1">
                  <div class="flex items-baseline gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-700 shrink-0"></span>
                    <span id="statVal3" class="text-2xl sm:text-3xl font-normal text-slate-900 tabular-nums leading-none">0</span>
                  </div>
                  <div id="statLbl3" class="text-xs font-medium text-slate-700 truncate">Low</div>
                </div>
              </div>

              <!-- Horizontal Segmented Ratio Bar -->
              <div>
                <div class="w-full h-3 rounded-full bg-slate-200 overflow-hidden flex" aria-hidden="true">
                  <div id="statBar1" class="h-full bg-rose-600 transition-all duration-300" style="width: 0%"></div>
                  <div id="statBar2" class="h-full bg-amber-500 transition-all duration-300" style="width: 0%"></div>
                  <div id="statBar3" class="h-full bg-teal-600 transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="statBarCaption" class="text-[11px] font-medium text-slate-700 mt-2">
                  Proportional distribution across active forensic dockets
                </p>
              </div>
            </div>
          </aside>
        </section>
      </main>
    </div>
  </div>

  <script>
    (function () {
      const REALTIME_ENDPOINT = @json(url('/api/dashboard/realtime'));
      const REVIEW_BASE_URL = @json(url('/cases'));
      const HISTORY_BASE_URL = @json(url('/history'));
      const DEFAULT_UNIT_FILTER = @json($defaultUnitFilter);

      let telemetryPayload = null;
      let filteredCases = [];
      let selectedCaseId = null;
      let statsDimension = 'severity';
      let showAllRows = false;
      const sparkCharts = {};

      // Clean public hook so user-provided Map API can plug straight into #forensicMapContainer later
      window.ForensicMapStage = {
        containerId: 'forensicMapContainer',
        miniMapContainerId: 'forensicMiniMapContainer',
        instance: null,
        handlers: {},
        mount(mapInstance, handlers = {}) {
          this.instance = mapInstance;
          this.handlers = handlers || {};
          const placeholder = document.getElementById('forensicMapPlaceholder');
          if (placeholder) placeholder.classList.add('hidden');
          const hint = document.getElementById('mapIntegrationHint');
          if (hint) hint.textContent = 'External Map API Connected';
          const slot = document.getElementById(this.containerId);
          if (slot) slot.setAttribute('data-map-api-slot', 'connected');

          ['mapZoomInBtn', 'mapZoomOutBtn', 'mapResetViewBtn'].forEach((btnId) => {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            btn.disabled = false;
            btn.removeAttribute('aria-disabled');
          });
        },
        getFilteredCases() {
          return filteredCases.slice();
        },
        zoomIn() {
          if (typeof this.handlers.zoomIn === 'function') this.handlers.zoomIn();
        },
        zoomOut() {
          if (typeof this.handlers.zoomOut === 'function') this.handlers.zoomOut();
        },
        resetView() {
          if (typeof this.handlers.resetView === 'function') this.handlers.resetView();
        }
      };

      function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      }

      function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
      }

      function normalizeStatusBucket(statusText) {
        const s = String(statusText || '').toLowerCase();
        if (s.includes('pending') || s.includes('awaiting')) return 'pending';
        if (s.includes('approved') || s.includes('completed') || s.includes('authorized')) return 'approved';
        return 'in_progress';
      }

      function statusBadgeClasses(statusText) {
        const bucket = normalizeStatusBucket(statusText);
        if (bucket === 'pending') return 'bg-amber-100 text-amber-900 border border-amber-300/70';
        if (bucket === 'approved') return 'bg-emerald-100 text-emerald-900 border border-emerald-300/70';
        return 'bg-sky-100 text-sky-900 border border-sky-300/70';
      }

      function priorityIconSpec(priorityText) {
        const p = String(priorityText || '').toUpperCase();
        if (p === 'CRITICAL' || p === 'HIGH') {
          return { dot: 'bg-rose-600', icon: 'alert-circle', color: 'text-rose-700' };
        }
        if (p === 'MEDIUM') {
          return { dot: 'bg-amber-600', icon: 'alert-triangle', color: 'text-amber-700' };
        }
        return { dot: 'bg-teal-700', icon: 'shield-check', color: 'text-teal-700' };
      }

      function renderSparkline(canvasId, series, strokeColor, fillColor) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof window.Chart === 'undefined') return;
        const points = Array.isArray(series) && series.length ? series : [0, 0, 0, 0, 0, 0];

        if (sparkCharts[canvasId]) {
          sparkCharts[canvasId].data.datasets[0].data = points;
          sparkCharts[canvasId].update('none');
          return;
        }

        const ctx = canvas.getContext('2d');
        sparkCharts[canvasId] = new window.Chart(ctx, {
          type: 'line',
          data: {
            labels: points.map((_, idx) => idx + 1),
            datasets: [{
              data: points,
              borderColor: strokeColor,
              backgroundColor: fillColor,
              borderWidth: 1.8,
              fill: true,
              tension: 0.35,
              pointRadius: 0,
              pointHoverRadius: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
              x: { display: false },
              y: { display: false, beginAtZero: true }
            }
          }
        });
      }

      function renderTelemetryStrip(data) {
        const m = (data && data.metrics) ? data.metrics : {};
        const c = (data && data.chart_data) ? data.chart_data : {};

        const turnaroundEl = document.getElementById('kpiTurnaroundValue');
        const turnaroundUnitEl = document.getElementById('kpiTurnaroundUnit');
        if (turnaroundEl && turnaroundUnitEl) {
          if (m.turnaround_days_raw !== null && m.turnaround_days_raw !== undefined) {
            turnaroundEl.textContent = String(m.turnaround_days_raw);
            turnaroundUnitEl.textContent = m.turnaround_days_raw === 1 ? 'Day' : 'Days';
          } else {
            turnaroundEl.textContent = 'N/A';
            turnaroundUnitEl.textContent = '';
          }
        }

        const completionEl = document.getElementById('kpiCompletionValue');
        if (completionEl) completionEl.textContent = String(m.completion_rate ?? 0);

        const inProgEl = document.getElementById('kpiInProgressValue');
        if (inProgEl) inProgEl.textContent = String(m.in_progress_cases ?? 0);

        const pendingEl = document.getElementById('kpiPendingValue');
        if (pendingEl) pendingEl.textContent = String(m.pending_cases ?? 0);

        const exhibitsEl = document.getElementById('kpiExhibitsValue');
        if (exhibitsEl) exhibitsEl.textContent = String(m.total_exhibits ?? 0);

        const totalEl = document.getElementById('kpiTotalCasesValue');
        if (totalEl) totalEl.textContent = String(m.total_cases ?? 0);

        renderSparkline('sparkTurnaround', c.monthly_approved, '#d97706', 'rgba(217, 119, 6, 0.14)');
        renderSparkline('sparkCompletion', c.monthly_approved, '#059669', 'rgba(5, 150, 105, 0.14)');
        renderSparkline('sparkInProgress', c.monthly_in_progress, '#0284c7', 'rgba(2, 132, 199, 0.14)');
        renderSparkline('sparkPending', c.monthly_pending, '#b45309', 'rgba(180, 83, 9, 0.14)');
        renderSparkline('sparkExhibits', c.monthly_exhibits, '#0f766e', 'rgba(15, 118, 110, 0.14)');
        renderSparkline('sparkTotalCases', c.monthly_bars, '#334155', 'rgba(51, 65, 85, 0.12)');
      }

      function renderPreviewCard(caseItem) {
        const dotEl = document.getElementById('previewPriorityDot');
        const statusEl = document.getElementById('previewStatusLabel');
        const titleEl = document.getElementById('previewSuspectTitle');
        const subEl = document.getElementById('previewCaseSubline');
        const countEl = document.getElementById('previewExhibitCount');
        const locEl = document.getElementById('previewLocationLabel');
        const reviewBtn = document.getElementById('previewReviewBtn');
        const historyLink = document.getElementById('previewHistoryLink');

        if (!caseItem) {
          if (dotEl) dotEl.className = 'w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0';
          if (statusEl) statusEl.textContent = 'No Matching Dockets';
          if (titleEl) titleEl.textContent = 'No Docket Selected';
          if (subEl) subEl.textContent = 'Clear filters or log a new case';
          if (countEl) countEl.textContent = '0';
          if (locEl) locEl.textContent = 'No location selected';
          return;
        }

        const pSpec = priorityIconSpec(caseItem.priority);
        if (dotEl) dotEl.className = 'w-2.5 h-2.5 rounded-full shrink-0 ' + pSpec.dot;
        if (statusEl) statusEl.textContent = (caseItem.status_badge || 'Under Investigation') + ' (' + (caseItem.priority || 'HIGH') + ')';
        if (titleEl) titleEl.textContent = caseItem.suspect_name || caseItem.title || 'Forensic Docket';
        if (subEl) subEl.textContent = (caseItem.case_number || 'Docket') + ' : ' + (caseItem.unit_name || 'Forensic Directorate');
        if (countEl) countEl.textContent = String(caseItem.exhibits_count ?? 0);
        if (locEl) locEl.textContent = (caseItem.raid_location || 'Location pending') + (caseItem.region ? ', ' + caseItem.region : '');

        const encodedRef = encodeURIComponent(caseItem.case_number || caseItem.id);
        if (reviewBtn) reviewBtn.href = REVIEW_BASE_URL + '/' + encodedRef + '/review';
        if (historyLink) historyLink.href = HISTORY_BASE_URL + '?unit=' + encodeURIComponent(caseItem.unit_name || '');
      }

      function renderStatsPanel() {
        const list = filteredCases;
        const total = list.length;
        const scopeEl = document.getElementById('statsScopeCount');
        if (scopeEl) scopeEl.textContent = total + (total === 1 ? ' Docket' : ' Dockets');

        let buckets = [];
        if (statsDimension === 'status') {
          let pending = 0, inProg = 0, approved = 0;
          list.forEach((c) => {
            const b = normalizeStatusBucket(c.status_badge);
            if (b === 'pending') pending++;
            else if (b === 'approved') approved++;
            else inProg++;
          });
          buckets = [
            { label: 'Pending', count: pending, dot: 'bg-amber-600', bar: 'bg-amber-500' },
            { label: 'In Progress', count: inProg, dot: 'bg-sky-600', bar: 'bg-sky-600' },
            { label: 'Approved', count: approved, dot: 'bg-emerald-600', bar: 'bg-emerald-600' }
          ];
        } else if (statsDimension === 'unit') {
          const map = {};
          list.forEach((c) => {
            const u = String(c.unit_name || 'Forensic Directorate');
            let key = 'Other Units';
            if (u.toLowerCase().includes('lab')) key = 'Lab Analysis';
            else if (u.toLowerCase().includes('digital') || u.toLowerCase().includes('cyber')) key = 'Digital Forensics';
            else if (u.toLowerCase().includes('evidence')) key = 'Evidence Vault';
            else if (u.toLowerCase().includes('crime')) key = 'Crime Scene';
            map[key] = (map[key] || 0) + 1;
          });
          const sorted = Object.entries(map).sort((a, b) => b[1] - a[1]);
          const palette = [
            { dot: 'bg-teal-700', bar: 'bg-teal-600' },
            { dot: 'bg-sky-700', bar: 'bg-sky-600' },
            { dot: 'bg-slate-700', bar: 'bg-slate-600' }
          ];
          buckets = [0, 1, 2].map((idx) => ({
            label: sorted[idx] ? sorted[idx][0] : 'Unassigned',
            count: sorted[idx] ? sorted[idx][1] : 0,
            dot: palette[idx].dot,
            bar: palette[idx].bar
          }));
        } else {
          let high = 0, med = 0, low = 0;
          list.forEach((c) => {
            const p = String(c.priority || 'HIGH').toUpperCase();
            if (p === 'CRITICAL' || p === 'HIGH') high++;
            else if (p === 'MEDIUM') med++;
            else low++;
          });
          buckets = [
            { label: 'High / Critical', count: high, dot: 'bg-rose-600', bar: 'bg-rose-600' },
            { label: 'Medium', count: med, dot: 'bg-amber-600', bar: 'bg-amber-500' },
            { label: 'Low', count: low, dot: 'bg-teal-700', bar: 'bg-teal-600' }
          ];
        }

        const sumTop3 = buckets.reduce((acc, b) => acc + b.count, 0);
        buckets.forEach((b, idx) => {
          const i = idx + 1;
          const valEl = document.getElementById('statVal' + i);
          const lblEl = document.getElementById('statLbl' + i);
          const barEl = document.getElementById('statBar' + i);
          if (valEl) valEl.textContent = String(b.count);
          if (lblEl) {
            lblEl.textContent = b.label;
            const dotSibling = valEl ? valEl.previousElementSibling : null;
            if (dotSibling) dotSibling.className = 'w-2.5 h-2.5 rounded-full shrink-0 ' + b.dot;
          }
          if (barEl) {
            const pct = sumTop3 > 0 ? ((b.count / sumTop3) * 100).toFixed(1) : '0';
            barEl.style.width = pct + '%';
            barEl.className = 'h-full transition-all duration-300 ' + b.bar;
          }
        });

        const caption = document.getElementById('statBarCaption');
        if (caption) {
          caption.textContent = sumTop3 > 0
            ? 'Proportional share across ' + total + ' matching forensic ' + (total === 1 ? 'docket' : 'dockets')
            : 'No dockets available for breakdown under current filters';
        }
      }

      function renderDocketsTable() {
        const loadingEl = document.getElementById('docketLoadingState');
        const errorEl = document.getElementById('docketErrorState');
        const emptyEl = document.getElementById('docketEmptyState');
        const tableEl = document.getElementById('docketsTable');
        const tbody = document.getElementById('docketsTableBody');
        const badgeEl = document.getElementById('tableShowingBadge');
        const noteEl = document.getElementById('tablePaginationNote');
        const toggleBtn = document.getElementById('toggleShowAllBtn');
        const mapPill = document.getElementById('mappedCaseCountPill');

        if (loadingEl) loadingEl.classList.add('hidden');
        if (errorEl) errorEl.classList.add('hidden');

        if (mapPill) {
          mapPill.textContent = filteredCases.length + (filteredCases.length === 1 ? ' Docket' : ' Dockets');
        }

        if (filteredCases.length === 0) {
          if (tableEl) tableEl.classList.add('hidden');
          if (emptyEl) emptyEl.classList.remove('hidden');
          if (badgeEl) badgeEl.textContent = '0 Dockets';
          if (noteEl) noteEl.textContent = 'Showing 0 matching dockets';
          if (toggleBtn) toggleBtn.classList.add('hidden');
          renderPreviewCard(null);
          return;
        }

        if (emptyEl) emptyEl.classList.add('hidden');
        if (tableEl) tableEl.classList.remove('hidden');

        if (!selectedCaseId || !filteredCases.some((c) => String(c.case_number || c.id) === String(selectedCaseId))) {
          selectedCaseId = filteredCases[0].case_number || filteredCases[0].id;
        }
        const selectedCase = filteredCases.find((c) => String(c.case_number || c.id) === String(selectedCaseId)) || filteredCases[0];
        renderPreviewCard(selectedCase);

        const maxVisible = showAllRows ? filteredCases.length : 5;
        const rows = filteredCases.slice(0, maxVisible);

        if (badgeEl) badgeEl.textContent = filteredCases.length + ' Total';
        if (noteEl) noteEl.textContent = 'Showing ' + rows.length + ' of ' + filteredCases.length + ' matching dockets';
        if (toggleBtn) {
          if (filteredCases.length > 5) {
            toggleBtn.classList.remove('hidden');
            toggleBtn.textContent = showAllRows ? 'Show Compact (5)' : 'Show All (' + filteredCases.length + ')';
          } else {
            toggleBtn.classList.add('hidden');
          }
        }

        if (tbody) {
          tbody.innerHTML = rows.map((c) => {
            const key = String(c.case_number || c.id);
            const isSel = key === String(selectedCaseId);
            const pSpec = priorityIconSpec(c.priority);
            const badgeCls = statusBadgeClasses(c.status_badge);
            const reviewHref = REVIEW_BASE_URL + '/' + encodeURIComponent(c.case_number || c.id) + '/review';
            const ageText = Number(c.days_open) === 0 ? 'Today' : (Number(c.days_open) === 1 ? '1 day' : (c.days_open + ' days'));

            return `
              <tr class="docket-row cursor-pointer hover:bg-white/80 ${isSel ? 'is-selected' : ''}"
                  data-case-key="${escapeHtml(key)}"
                  onclick="selectDashboardDocket('${escapeHtml(key)}')">
                <td class="py-3 pr-3">
                  <div class="flex items-center gap-2.5 min-w-[180px]">
                    <span class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 ${pSpec.color}" title="${escapeHtml(c.priority || 'HIGH')} priority">
                      <i data-lucide="${pSpec.icon}" class="w-3.5 h-3.5"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="font-semibold text-slate-900 truncate max-w-[200px]">${escapeHtml(c.suspect_name || c.title)}</div>
                      <div class="text-[11px] font-mono-code text-slate-600">${escapeHtml(c.case_number || '')}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3">
                  <div class="font-medium text-slate-900">${escapeHtml(String(c.exhibits_count ?? 0))} Exhibit(s)</div>
                  <div class="text-[11px] text-slate-600 truncate max-w-[160px]">${escapeHtml(c.region || c.raid_location || 'N/A')}</div>
                </td>
                <td class="py-3 px-3 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold ${badgeCls}">
                    ${escapeHtml(c.status_badge || 'Under Investigation')}
                  </span>
                </td>
                <td class="py-3 px-3 whitespace-nowrap font-mono-code text-[11px] text-slate-700">${escapeHtml(c.incident_date || 'N/A')}</td>
                <td class="py-3 px-3 whitespace-nowrap text-slate-800 font-medium">${escapeHtml(ageText)}</td>
                <td class="py-3 px-3 whitespace-nowrap text-slate-800">${escapeHtml(c.unit_name || 'Forensic Directorate')}</td>
                <td class="py-3 pl-3 text-right whitespace-nowrap">
                  <a href="${reviewHref}"
                     onclick="event.stopPropagation()"
                     class="inline-flex items-center justify-center min-h-[40px] px-4 py-1.5 rounded-full bg-white hover:bg-slate-900 hover:text-white text-slate-900 border border-slate-300 text-xs font-semibold transition-colors shadow-2xs">
                    Review
                  </a>
                </td>
              </tr>
            `;
          }).join('');
        }

        refreshIcons();
      }

      window.applyDashboardFilters = function () {
        if (!telemetryPayload) return;
        const allCases = Array.isArray(telemetryPayload.cases) ? telemetryPayload.cases : [];

        const period = (document.getElementById('filterPeriod')?.value || 'all').toLowerCase();
        const region = document.getElementById('filterRegion')?.value || 'all';
        const unit = (document.getElementById('filterUnit')?.value || 'all').toLowerCase();
        const status = (document.getElementById('filterStatus')?.value || 'all').toLowerCase();
        const priority = (document.getElementById('filterPriority')?.value || 'all').toUpperCase();
        const query = (document.getElementById('docketSearchInput')?.value || '').trim().toLowerCase();

        filteredCases = allCases.filter((c) => {
          const daysOpen = Number(c.days_open ?? 0);
          if (period === 'today' && daysOpen > 0) return false;
          if (period === '7d' && daysOpen > 7) return false;
          if (period === '30d' && daysOpen > 30) return false;

          if (region !== 'all' && String(c.region || '').toLowerCase() !== region.toLowerCase()) return false;

          if (unit !== 'all') {
            const caseUnit = String(c.unit_name || '').toLowerCase();
            if (!caseUnit.includes(unit.split(' ')[0])) return false;
          }

          if (status !== 'all') {
            if (normalizeStatusBucket(c.status_badge) !== status) return false;
          }

          if (priority !== 'ALL' && String(c.priority || '').toUpperCase() !== priority) return false;

          if (query) {
            const haystack = [
              c.case_number,
              c.suspect_name,
              c.title,
              c.raid_location,
              c.region,
              c.unit_name,
              c.status_badge,
              c.submitting_officer
            ].join(' ').toLowerCase();
            if (!haystack.includes(query)) return false;
          }
          return true;
        });

        renderDocketsTable();
        renderStatsPanel();
      };

      window.quickFilterByStatus = function (statusVal) {
        const statusSel = document.getElementById('filterStatus');
        if (statusSel) {
          statusSel.value = statusVal;
          window.applyDashboardFilters();
        }
      };

      window.resetDashboardFilters = function () {
        const ids = ['filterPeriod', 'filterRegion', 'filterStatus', 'filterPriority'];
        ids.forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.value = 'all';
        });
        const unitSel = document.getElementById('filterUnit');
        if (unitSel) unitSel.value = DEFAULT_UNIT_FILTER || 'all';
        const searchEl = document.getElementById('docketSearchInput');
        if (searchEl) searchEl.value = '';
        showAllRows = false;
        window.applyDashboardFilters();
      };

      window.selectDashboardDocket = function (caseKey) {
        selectedCaseId = caseKey;
        renderDocketsTable();
      };

      window.toggleShowAllRows = function () {
        showAllRows = !showAllRows;
        renderDocketsTable();
      };

      window.setStatsDimension = function (dim) {
        statsDimension = dim;
        const tabs = {
          severity: document.getElementById('statTabSeverity'),
          unit: document.getElementById('statTabUnit'),
          status: document.getElementById('statTabStatus')
        };
        Object.entries(tabs).forEach(([key, btn]) => {
          if (!btn) return;
          const active = key === dim;
          btn.setAttribute('aria-selected', active ? 'true' : 'false');
          btn.className = active
            ? 'min-h-[44px] px-4 py-1.5 rounded-full bg-white text-slate-900 text-xs font-semibold shadow-2xs transition-colors'
            : 'min-h-[44px] px-4 py-1.5 rounded-full text-slate-800 hover:bg-white/60 text-xs font-medium transition-colors';
        });
        renderStatsPanel();
      };

      window.refreshRealtimeData = async function () {
        const spinner = document.getElementById('syncSpinner');
        const loadingEl = document.getElementById('docketLoadingState');
        const errorEl = document.getElementById('docketErrorState');

        if (spinner) spinner.classList.add('animate-spin');
        if (!telemetryPayload && loadingEl) loadingEl.classList.remove('hidden');
        if (errorEl) errorEl.classList.add('hidden');

        try {
          const resp = await fetch(REALTIME_ENDPOINT, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
          });
          if (!resp.ok) throw new Error('HTTP ' + resp.status);
          const data = await resp.json();
          if (!data || data.success === false) throw new Error('Invalid telemetry payload');

          telemetryPayload = data;
          renderTelemetryStrip(data);
          window.applyDashboardFilters();

          const updatedEl = document.getElementById('telemetryLastUpdated');
          if (updatedEl) {
            const now = new Date();
            updatedEl.textContent = 'Synced ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
          }
        } catch (err) {
          if (loadingEl) loadingEl.classList.add('hidden');
          if (errorEl) errorEl.classList.remove('hidden');
          const msgEl = document.getElementById('docketErrorMessage');
          if (msgEl) msgEl.textContent = 'Unable to load forensic telemetry (' + err.message + ').';
        } finally {
          if (spinner) spinner.classList.remove('animate-spin');
        }
      };

      document.addEventListener('DOMContentLoaded', function () {
        refreshIcons();
        const unitSel = document.getElementById('filterUnit');
        if (unitSel && DEFAULT_UNIT_FILTER) {
          unitSel.value = DEFAULT_UNIT_FILTER;
        }
        window.refreshRealtimeData();
        setInterval(window.refreshRealtimeData, 30000);
      });
    })();
  </script>
</body>
</html>

