@php
    $authUser = auth()->user();
    $cases = $cases ?? \App\Models\ForensicCase::allCases();
    $userUnit = 'Crime Scene Support Unit';
    $unitDisplayTitle = 'Rapid Field Deployment & Incident Command';
    $dbUnitHead = \App\Models\User::where('is_head', true)
        ->where(function($q) {
            $q->where('unit', 'LIKE', '%Crime Scene Support%')
              ->orWhere('unit', 'LIKE', '%Forensic%');
        })->first() ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Commander Samuel Atta');
    $officerRole = ($authUser && $authUser->is_head) ? 'Crime Scene Support Unit Head' : 'Commanding Officer';

    $totalSingleCases = \App\Models\SingleSuspectCase::count();
    $totalMultiCases = \App\Models\MultipleSuspectCase::count();
    $totalExhibits = \App\Models\Exhibit::count();
    $totalCases = $cases->count();
    $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>NACOC FORENSIC | Crime Scene Support Unit Tactical Command & Regional Radar</title>
  
  <!-- Styles / Scripts: System Vite Asset Loader & Compiled CSS -->
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
      <!-- Tailwind CSS CDN Fallback (Warning Suppressed for clean console) -->
      <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
          if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
          _origWarn.apply(console, args);
        };
      </script>
      <script src="https://cdn.tailwindcss.com"></script>
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
              },
              boxShadow: {
                'finnova': '0 20px 50px -15px rgba(24, 25, 38, 0.08)',
                'finnova-dark': '0 25px 60px -15px rgba(10, 11, 20, 0.5)',
                'tactical-glow': '0 0 50px rgba(16, 185, 129, 0.35)'
              }
            }
          }
        };
      </script>
  @endif

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: #f1f3f8;
      color: #1e293b;
    }
    .font-mono-code {
      font-family: 'JetBrains Mono', monospace;
    }

    /* 3D Map Perspective Stage */
    .map-3d-stage {
      perspective: 1400px;
      perspective-origin: 50% 50%;
      transform-style: preserve-3d;
      cursor: grab;
      user-select: none;
      -webkit-user-select: none;
      touch-action: none;
    }
    .map-3d-stage.is-dragging,
    .map-3d-stage.is-dragging * {
      cursor: grabbing !important;
    }
    .map-3d-plane {
      transform-style: preserve-3d;
      will-change: transform;
    }

    /* High-Caseload Forensic Red Hotspot Region Polygons (ONLY Higher Caseload is RED) */
    .region-polygon.region-hotspot,
    .region-polygon.region-hotspot.region-active,
    .tactical-region-polygon.region-hotspot,
    .tactical-region-polygon.tactical-region-hotspot,
    .tactical-region-polygon.region-active.region-hotspot,
    .tactical-region-polygon.region-active.tactical-region-hotspot {
      fill: #dc2626 !important;
      stroke: #fca5a5 !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 45px rgba(220, 38, 38, 0.95)) !important;
      opacity: 1 !important;
    }
    .region-polygon.region-hotspot:hover,
    .tactical-region-polygon.region-hotspot:hover,
    .tactical-region-polygon.tactical-region-hotspot:hover {
      fill: #ef4444 !important;
      stroke: #ffffff !important;
      filter: drop-shadow(0 0 55px rgba(239, 68, 68, 1)) !important;
    }

    /* Standard Active Region (when NOT a higher-caseload hotspot) */
    .region-polygon.region-active:not(.region-hotspot) {
      fill: #5c56df !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(92, 86, 223, 0.45)) !important;
      opacity: 1 !important;
    }

    /* Tactical Active Region (when NOT a higher-caseload hotspot -> emerald tactical active) */
    .tactical-region-polygon.region-active:not(.region-hotspot):not(.tactical-region-hotspot) {
      fill: #10b981 !important;
      stroke: #ffffff !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 35px rgba(16, 185, 129, 0.8)) !important;
      opacity: 1 !important;
    }

    /* Concentric Radar Rings Behind Map */
    .radar-ring {
      border: 1px solid rgba(92, 86, 223, 0.15);
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
    }
    .radar-ring-tactical {
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    /* SVG Region Transition */
    .region-polygon {
      transition: fill 0.25s ease, stroke 0.25s ease, filter 0.25s ease, opacity 0.3s ease;
    }

    /* Tactical HUD Grid Overlay */
    .tactical-grid-bg {
      background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
      background-size: 24px 24px;
    }

    /* Custom Sleek Scrollbar for Forensic Panels */
    .custom-scrollbar::-webkit-scrollbar {
      width: 4px;
      height: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.04);
      border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.38);
    }
    .custom-scrollbar {
      scrollbar-width: thin;
      scrollbar-color: rgba(255, 255, 255, 0.2) rgba(255, 255, 255, 0.04);
    }
  </style>
</head>
<body class="bg-[#f1f3f8] text-slate-800 antialiased min-h-screen">

  <!-- ======================================================== -->
  <!-- MASTER WRAPPER CONTAINER                                -->
  <!-- ======================================================== -->
  <div class="w-full max-w-[1920px] 2xl:max-w-none mx-auto px-3 sm:px-5 lg:px-8 py-4 sm:py-5 space-y-5">

    <!-- ====================================================== -->
    <!-- 1. TOP COMMAND BAR: Agency Branding & Unit Telemetry    -->
    <!-- ====================================================== -->
    <header class="bg-white rounded-3xl p-4 sm:p-5 shadow-finnova border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      
      <!-- Left: Logo & Unit Title -->
      <div class="flex items-center space-x-3.5">
        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 p-1 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
          <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
        </div>
        <div>
          <div class="flex items-center space-x-2">
            <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">NACOC FORENSIC</h1>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold border border-indigo-200/60 font-mono-code">
              Crime Scene Support Unit
            </span>
          </div>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Rapid Field Deployment & Incident Command • Real-time Ghana Evidence & Case Radar
          </p>
        </div>
      </div>

      <!-- Right: Officer Profile, Quick Sync & Tactical Launcher -->
      <div class="flex items-center space-x-3 self-end md:self-auto">
        <!-- Live Refresh Trigger -->
        <button type="button" 
                onclick="refreshRealtimeData()" 
                class="w-10 h-10 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 flex items-center justify-center transition-colors shadow-xs" 
                title="Sync Database Telemetry">
          <i data-lucide="refresh-cw" class="w-4 h-4" id="syncSpinner"></i>
        </button>

        <!-- Officer Profile Pill -->
        <div class="flex items-center space-x-3 pl-2 border-l border-slate-200">
          <div class="w-10 h-10 rounded-2xl bg-indigo-100 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-sm shadow-xs overflow-hidden">
            @if ($authUser && $authUser->avatar)
              <img src="{{ asset($authUser->avatar) }}" alt="" class="w-full h-full object-cover">
            @else
              {{ strtoupper(substr($headName, 0, 2)) }}
            @endif
          </div>
          <div class="hidden sm:block text-left">
            <div class="text-xs font-bold text-slate-900 leading-tight">{{ $headName }}</div>
            <div class="text-[11px] text-slate-500 font-medium">{{ $officerRole }}</div>
          </div>
        </div>

      </div>

    </header>

    <!-- ====================================================== -->
    <!-- 2. QUICK NAVIGATION APP BAR (UNIT-SPECIFIC ACTIONS)     -->
    <!-- ====================================================== -->
    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
      <div class="flex items-center space-x-1 sm:space-x-2 text-slate-600">
        
        <a href="{{ url("/Crime_Scene_Support/head") }}" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold flex items-center space-x-1.5 shadow-sm">
          <i data-lucide="map-pin" class="w-3.5 h-3.5 text-white"></i>
          <span>Incident Command</span>
        </a>
        <a href="{{ url("/Crime_Scene_Support/create") }}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold">
          <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span>Arrest & Seizure (New)</span>
        </a>
        <a href="{{ url("/cases?from=Crime+Scene+Support") }}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold">
          <i data-lucide="folder-search" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span>Field Case Directory</span>
        </a>
        <a href="{{ url("/history?unit=Crime+Scene+Support") }}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold">
          <i data-lucide="crosshair" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span>Field Deployment Logs</span>
        </a>
        <a href="{{ url("/Report?unit=Crime+Scene+Support") }}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold">
          <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span>Incident Reports</span>
        </a>
      </div>

      <div class="flex items-center space-x-2 pl-4 text-slate-400 shrink-0">
        <span class="text-[11px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">Crime Scene Support Unit</span>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold border border-emerald-200/60">
          ONLINE
        </span>
      </div>
    </nav>

    <!-- ====================================================== -->
    <!-- 3. TOP KPI CARDS (FORENSIC DATA - 0% FINANCE)          -->
    <!-- ====================================================== -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      <!-- CARD 1: Under Investigation / Pending Sign-off -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Under Investigation</span>
            <span class="w-5 h-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-[10px]">
              <i data-lucide="alert-circle" class="w-3 h-3"></i>
            </span>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricOverdueAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              {{ $pendingCasesCount }} Dockets
            </span>
            <span class="text-[11px] font-medium text-amber-600 font-bold">Action Needed</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Priority dockets:</span>
          <span class="font-bold text-slate-800 font-mono-code">5 High Urgency</span>
        </div>
      </div>

      <!-- CARD 2: Active Monthly Casework Intake (Chart.js Bar) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Active Exhibits Intake</span>
            <span class="w-5 h-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-[10px]">
              <i data-lucide="box" class="w-3 h-3"></i>
            </span>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricNextMonthAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              {{ $totalExhibits }} Exhibits
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">↑ 18% vs Last Qtr</span>
          </div>
        </div>
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaBarChart"></canvas>
        </div>
      </div>

      <!-- CARD 3: Average Turnaround Time (Smooth Purple Sparkline) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Avg. Analysis Turnaround</span>
            <span class="w-5 h-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-[10px]">
              <i data-lucide="clock" class="w-3 h-3"></i>
            </span>
          </div>
          <div id="metricTurnaroundDays" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            4.2 days
          </div>
          <div class="text-[11px] font-semibold text-emerald-500 mt-1 flex items-center space-x-1">
            <span id="metricTurnaroundTrend">↓ 2 days faster than last month</span>
          </div>
        </div>
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaSparkline"></canvas>
        </div>
      </div>

      <!-- CARD 4: Field Incident Operations -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Field Incident Operations</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-[10px]">
                <i data-lucide="shield-check" class="w-3 h-3"></i>
              </span>
              <a href="{{ url("/Crime_Scene_Support/head") }}" class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]" title="Inspect">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              {{ $totalCases }} Field Deployments
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">Rapid Deployments</span>
          </div>
        </div>
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Field Raids">
            <span class="font-mono-code text-[9px] text-slate-400">TEAM A</span>
            <span class="font-bold text-slate-700">Field Raids</span>
          </div>
          <div class="flex-1 py-1 px-2 rounded-xl bg-[#5c56df] text-white shadow-sm flex flex-col items-center" title="Interception">
            <span class="font-mono-code text-[9px] text-indigo-200">TEAM B</span>
            <span class="font-bold">Interception</span>
          </div>
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Evidence">
            <span class="font-mono-code text-[9px] text-slate-400">CHAIN</span>
            <span class="font-bold text-slate-700">Evidence</span>
          </div>
          <a href="{{ url("/Crime_Scene_Support/head") }}" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-[10px] font-bold shadow-xs transition-colors flex items-center">
            Deploy
          </a>
        </div>
      </div>

    </section>

    <!-- ====================================================== -->
    <!-- 4. ACTIVE FORENSIC FILTERS TOOLBAR                    -->
    <!-- ====================================================== -->
    <div id="filterToolbar" class="flex flex-wrap items-center justify-between gap-3 text-xs">
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center space-x-1.5 bg-slate-100 text-slate-700 px-3 py-1.5 rounded-full font-bold">
          <span>Active filters</span>
          <span class="w-4 h-4 rounded-full bg-slate-800 text-white text-[10px] flex items-center justify-center font-mono-code">2</span>
        </div>

        <div class="relative">
          <select id="filterUnitSelect" onchange="onFilterUnitChange(this.value)" class="appearance-none bg-white border border-slate-200/90 hover:border-slate-300 text-slate-700 pl-3.5 pr-8 py-1.5 rounded-full font-medium shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            <option value="All">All Forensic Units</option>
            <option value="Crime Scene Support">Crime Scene Support (CSSU)</option>
            <option value="Lab Analysis">Laboratory Analysis (LAU)</option>
            <option value="Evidence Management">Evidence Management (EMU)</option>
            <option value="Digital & Physical Forensic Unit">Digital & Cyber Forensics (DPFU)</option>
          </select>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
        </div>

        <div class="relative">
          <select id="filterStatusSelect" onchange="onFilterStatusChange(this.value)" class="appearance-none bg-white border border-slate-200/90 hover:border-slate-300 text-slate-700 pl-3.5 pr-8 py-1.5 rounded-full font-medium shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            <option value="All">All Forensic Statuses</option>
            <option value="Under Investigation">Under Investigation</option>
            <option value="Lab Analysis">Lab Analysis Pending</option>
            <option value="Court Ready">Court Ready</option>
            <option value="Approved">Signed-off / Complete</option>
          </select>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
        </div>
      </div>

      <div class="relative w-full sm:w-64">
        <input type="text" 
               id="searchInvoiceInput"
               placeholder="Search docket #, suspect, or officer..." 
               oninput="onSearchInvoiceInput(this.value)"
               class="w-full pl-4 pr-9 py-2 rounded-full bg-white border border-slate-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute right-3.5 top-2.5 pointer-events-none"></i>
      </div>
    </div>

    <!-- ====================================================== -->
    <!-- 5. CENTRAL COMMAND STAGE: 3-PANEL TRI-VIEW & 3D MAP   -->
    <!-- ====================================================== -->
    <section class="bg-[#181926] rounded-[2rem] p-4 sm:p-5 text-white shadow-finnova-dark border border-slate-800/80 flex flex-col space-y-4 relative overflow-hidden">
      
      <!-- Top Bar of Dark Stage: Tabs & Command Switchers -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-800/90">
        
        <!-- Left: Filter Tabs -->
        <div class="bg-white/10 p-1 rounded-full inline-flex items-center space-x-1 text-xs font-semibold">
          <button type="button" onclick="setInvoiceTab('all')" id="tabBtnAll" class="px-4 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors">
            All Dockets
          </button>
          <button type="button" onclick="setInvoiceTab('analysis')" id="tabBtnAnalysis" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors flex items-center space-x-1.5">
            <span>Under Analysis</span>
            <span id="tabCountAnalysis" class="bg-white/10 text-slate-300 px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{ $pendingCasesCount }}</span>
          </button>
          <button type="button" onclick="setInvoiceTab('active')" id="tabBtnActive" class="bg-[#5c56df] text-white px-4 py-1.5 rounded-full font-bold shadow-md shadow-indigo-500/25 flex items-center space-x-1.5">
            <span>Active Cases</span>
            <span id="tabCountActive" class="bg-white/20 text-white px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{ $totalCases }}</span>
          </button>
        </div>

        <!-- Center / Right: Tactical Trigger & Controls -->
        <div class="flex items-center space-x-2">
          
          <button type="button" 
                  onclick="openTacticalMode()" 
                  class="px-3.5 py-1 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md shadow-emerald-500/30 flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95 group" 
                  title="Launch Fullscreen Tactical Operations Theatre (Press 'T')">
            <i data-lucide="maximize-2" class="w-3.5 h-3.5 stroke-[2.5]"></i>
            <span class="font-mono-code tracking-wider text-[11px]">TACTICAL THEATRE</span>
          </button>

          <!-- View Mode Switcher -->
          <div class="bg-slate-900/90 p-1 rounded-full border border-slate-700/80 flex items-center space-x-1 text-xs">
            <button type="button" onclick="switchMainView('3col')" id="btnView3Col" class="px-3 py-1 rounded-full font-bold text-white bg-indigo-600 shadow-sm flex items-center space-x-1.5 transition-all">
              <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
              <span>Tri-Command</span>
            </button>
            <button type="button" onclick="switchMainView('dossier')" id="btnViewDossier" class="px-3 py-1 rounded-full text-slate-400 hover:text-white flex items-center space-x-1 transition-all">
              <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
              <span>Docket Details</span>
            </button>
            <button type="button" onclick="switchMainView('map')" id="btnViewMap" class="px-3 py-1 rounded-full text-slate-400 hover:text-white flex items-center space-x-1 transition-all">
              <i data-lucide="map" class="w-3.5 h-3.5"></i>
              <span>3D Ghana Map</span>
            </button>
          </div>

          <!-- Map Zoom & 3D Controls -->
          <div class="flex items-center space-x-1 border border-slate-700/80 rounded-full p-1 bg-slate-900/80">
            <button type="button" onclick="zoomMap(1.15)" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-xs" title="Zoom In">+</button>
            <button type="button" onclick="zoomMap(0.85)" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-xs" title="Zoom Out">-</button>
            <button type="button" onclick="resetMapView()" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center" title="Reset View">
              <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" onclick="toggle3DView()" id="btnToggle3D" class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-black font-mono-code" title="Toggle 3D Relief">3D</button>
          </div>

        </div>

      </div>

      <!-- MAIN TRI-COMMAND WORKSPACE GRID (List + 3D Map + Forensic Dossier) -->
      <div id="mainWorkspaceGrid" class="grid grid-cols-12 gap-4 items-start">
        
        <!-- PANEL 1: Active Forensic Cases List -->
        <div id="panelInvoicesList" class="col-span-12 xl:col-span-3 flex flex-col space-y-2">
          <div class="flex items-center justify-between px-1 pb-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono-code" id="listHeaderTitle">Active Forensic Cases</span>
            <span class="text-[10px] font-mono-code text-slate-300" id="listCasesCount">{{ $totalCases }} Active</span>
          </div>

          <!-- Real-Time Case Rows Container -->
          <div id="invoicesListContainer" class="space-y-1.5 max-h-[510px] min-h-[380px] overflow-y-auto pr-1 custom-scrollbar">
            <div class="text-center py-8 text-xs text-slate-500">Loading live forensic cases from database...</div>
          </div>

          <!-- Pagination Control Bar -->
          <div id="casesPaginationBar" class="flex items-center justify-between pt-2 border-t border-slate-800/80 text-xs font-mono-code px-1 select-none"></div>
        </div>

        <!-- PANEL 2: 3D CLICKABLE GHANA MAP (Centerpiece) -->
        <div id="panelGhanaMap" class="col-span-12 xl:col-span-5 flex flex-col">
          
          <div id="mapStageWrapper" class="relative w-full h-[580px] flex items-center justify-center bg-slate-950/70 rounded-2xl border border-slate-800/80 overflow-hidden select-none">
<!-- Tactical Quick Expand Button inside map corner (Hidden when drilled in) -->
            <button type="button" 
                    id="btnMapCornerTactical"
                    onclick="openTacticalMode()" 
                    class="absolute top-3 right-3 z-20 px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-emerald-600 text-slate-300 hover:text-white border border-emerald-500/40 backdrop-blur-md text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-xl transition-all group"
                    title="Launch Fullscreen Tactical Radar Theatre">
              <i data-lucide="maximize-2" class="w-3.5 h-3.5 stroke-[2]"></i>
              <span class="tracking-wider">TACTICAL HUD</span>
            </button>

            <!-- District Map Top Navigation Bar (Shows ONLY when drilled in, clean full-width header) -->
            <div id="districtNavHeader" class="hidden absolute top-3 left-3 right-3 z-30 flex items-center justify-between bg-slate-900/95 border border-slate-700/80 rounded-xl px-3 py-2 backdrop-blur-md shadow-2xl transition-all">
              <button type="button" 
                      onclick="resetMapView()" 
                      class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow transition-all group">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                <span>Back to 16 Regions</span>
              </button>
              
              <div class="flex items-center space-x-2 text-xs font-mono-code font-bold text-white px-2 truncate">
                <span id="districtDrillTitle" class="truncate">GREATER ACCRA DISTRICT MAP</span>
              </div>

              <div class="flex items-center space-x-1.5">
                <button type="button" onclick="openTacticalMode()" class="px-2 py-1 rounded-lg bg-emerald-600/30 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/40 text-[10px] font-mono-code font-bold flex items-center space-x-1" title="Tactical Theatre">
                  <i data-lucide="scan" class="w-3 h-3"></i>
                  <span class="hidden sm:inline">Tactical</span>
                </button>
                <button type="button" onclick="resetMapView()" class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center" title="Close District View">
                  <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
              </div>
            </div>

            <!-- Radial Ambient Glow behind Ghana -->
            <div class="absolute w-[480px] h-[480px] rounded-full pointer-events-none opacity-30 blur-3xl bg-gradient-to-tr from-[#5c56df] via-purple-600/40 to-transparent"></div>

            <!-- Concentric Radar / Orbital Rings centered behind Ghana -->
            <div id="radarRingsGroup">
              <div class="radar-ring w-[540px] h-[540px]"></div>
              <div class="radar-ring w-[400px] h-[400px]"></div>
              <div class="radar-ring w-[260px] h-[260px]"></div>
            </div>

            <!-- 3D Perspective Map Stage Container -->
            <div class="map-3d-stage relative w-full h-full flex items-center justify-center">
              
              <div id="ghana3dPlane" class="map-3d-plane relative w-full h-full max-w-[560px] flex items-center justify-center">
                
                <svg id="ghanaMapSvg" 
                     viewBox="0 0 800 960" 
                     class="w-full h-full drop-shadow-[0_25px_40px_rgba(0,0,0,0.6)] transition-all duration-500"
                     style="overflow: visible;">
                  
                  <defs>
                    <linearGradient id="regionGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#1e293b" />
                      <stop offset="100%" stop-color="#0f172a" />
                    </linearGradient>
                  </defs>

                  <!-- 1. 3D Base Extrusion Shadows -->
                  <g id="extrusionShadowPass" transform="translate(0, 16)" pointer-events="none">
                    <path d="M 189.2,781.8 L 190.7,776.0 L 194.5,778.6 L 199.9,779.9 L 202.9,773.2 L 199.3,772.3 L 198.8,769.5 L 198.4,763.9 L 200.7,757.2 L 193.9,754.2 L 196.5,744.1 L 199.9,737.4 L 204.4,733.6 L 214.6,736.3 L 219.8,735.1 L 231.8,735.9 L 236.0,729.1 L 245.7,729.9 L 243.8,727.1 L 243.3,718.2 L 250.4,714.6 L 256.9,708.8 L 251.5,707.9 L 246.2,699.2 L 240.7,698.9 L 237.5,701.3 L 234.5,694.2 L 236.2,685.0 L 232.6,681.4 L 230.6,682.8 L 222.7,678.9 L 220.4,679.8 L 219.6,681.6 L 217.6,695.9 L 211.6,700.2 L 208.3,699.7 L 209.4,694.5 L 195.1,693.6 L 191.9,683.0 L 189.0,680.4 L 181.3,677.0 L 178.0,669.4 L 175.4,668.1 L 167.7,668.3 L 158.0,662.8 L 152.6,661.4 L 151.3,656.6 L 151.0,640.4 L 148.8,633.3 L 143.8,624.1 L 137.1,619.2 L 133.9,618.6 L 124.5,621.0 L 120.6,620.0 L 118.3,617.0 L 115.7,608.7 L 99.0,635.2 L 98.4,641.3 L 101.3,643.7 L 102.1,647.6 L 99.1,651.8 L 98.6,656.3 L 96.8,657.4 L 95.0,662.4 L 98.8,665.4 L 98.4,673.6 L 107.4,707.0 L 106.7,713.0 L 109.6,713.2 L 116.6,726.7 L 120.6,749.7 L 128.0,767.2 L 127.1,787.4 L 137.0,786.4 L 135.3,796.7 L 137.8,797.2 L 139.8,799.6 L 146.2,797.9 L 150.1,794.5 L 150.0,795.4 L 155.2,798.6 L 156.9,798.5 L 157.5,799.9 L 159.5,798.5 L 161.5,800.1 L 161.3,801.7 L 163.3,802.3 L 162.8,810.9 L 165.0,824.5 L 168.4,831.3 L 168.2,837.7 L 174.7,837.3 L 179.8,835.5 L 183.9,830.0 L 188.6,819.6 L 188.6,815.3 L 186.8,813.4 L 191.1,811.9 L 191.5,804.2 L 190.5,799.4 L 193.1,796.6 L 192.8,790.2 L 189.4,786.7 L 189.2,781.8 Z" fill="#0f111a" opacity="0.9" /><path d="M 209.4,694.5 L 209.7,692.0 L 206.1,688.6 L 207.5,687.2 L 206.6,687.1 L 207.0,684.9 L 209.5,683.0 L 209.9,680.4 L 213.2,678.8 L 213.1,676.4 L 215.1,675.6 L 215.9,673.8 L 210.3,662.8 L 209.9,654.5 L 215.4,657.1 L 219.5,656.3 L 221.1,652.5 L 226.7,651.1 L 228.6,638.8 L 233.7,636.5 L 232.8,634.1 L 229.0,632.5 L 214.7,632.9 L 214.9,631.1 L 221.9,625.3 L 228.7,614.2 L 231.5,606.7 L 235.9,603.1 L 239.2,603.0 L 241.5,607.9 L 245.6,609.2 L 251.8,606.4 L 260.7,607.2 L 266.2,604.0 L 277.6,593.1 L 282.1,593.1 L 285.6,591.3 L 286.4,583.5 L 287.8,580.5 L 276.1,577.1 L 272.9,571.3 L 269.1,568.4 L 259.0,568.6 L 255.2,564.3 L 247.4,564.2 L 245.2,567.5 L 239.5,568.2 L 233.1,573.0 L 230.9,578.8 L 221.1,580.3 L 223.0,586.1 L 227.9,590.9 L 229.2,595.4 L 224.9,598.4 L 224.1,595.4 L 220.4,594.1 L 217.9,597.5 L 210.4,598.9 L 213.8,597.3 L 210.6,594.5 L 200.1,593.4 L 200.7,579.9 L 192.8,580.1 L 189.0,582.4 L 187.9,586.8 L 185.8,586.8 L 179.6,593.0 L 173.4,593.2 L 172.7,597.8 L 167.6,598.6 L 164.6,602.3 L 163.3,607.7 L 158.6,610.7 L 157.3,614.8 L 152.8,618.0 L 152.2,627.3 L 148.8,633.3 L 151.6,644.5 L 151.3,656.6 L 152.6,661.4 L 158.0,662.8 L 167.7,668.3 L 175.4,668.1 L 178.0,669.4 L 181.3,677.0 L 189.0,680.4 L 191.9,683.0 L 195.1,693.6 L 209.4,694.5 Z" fill="#0f111a" opacity="0.9" /><path d="M 522.3,498.1 L 521.0,487.4 L 522.5,481.2 L 521.6,479.8 L 517.2,480.0 L 521.2,473.4 L 521.6,471.7 L 520.0,469.8 L 517.2,470.8 L 511.6,469.9 L 508.0,467.3 L 506.9,463.6 L 497.3,456.3 L 488.3,453.2 L 480.2,452.6 L 466.7,448.5 L 453.5,437.3 L 439.6,428.2 L 431.3,427.7 L 423.6,429.2 L 424.3,431.0 L 423.1,436.9 L 417.2,439.2 L 409.8,439.8 L 407.1,443.1 L 405.6,440.5 L 402.8,440.2 L 401.2,438.5 L 398.8,440.9 L 398.6,451.4 L 394.8,456.5 L 398.3,461.3 L 397.6,465.6 L 399.6,466.6 L 401.9,471.7 L 397.0,470.1 L 393.1,471.7 L 389.6,475.2 L 382.6,474.2 L 381.5,465.9 L 382.8,462.0 L 371.8,443.5 L 367.7,438.9 L 365.8,431.9 L 369.6,422.3 L 368.6,418.4 L 369.5,416.1 L 368.2,414.7 L 368.8,411.9 L 370.7,409.8 L 373.9,408.8 L 374.3,407.0 L 377.6,406.6 L 377.4,404.2 L 379.7,403.3 L 380.4,404.7 L 381.7,404.1 L 380.6,401.0 L 384.3,398.8 L 384.8,394.5 L 383.5,386.5 L 386.0,381.0 L 380.2,380.7 L 375.2,382.6 L 368.3,379.7 L 364.9,375.6 L 361.9,375.3 L 355.8,370.4 L 348.1,368.2 L 337.9,370.2 L 335.9,373.1 L 334.5,379.5 L 332.0,381.0 L 320.5,381.7 L 314.4,379.1 L 311.3,380.7 L 309.4,386.2 L 304.1,388.4 L 300.9,387.9 L 292.5,383.5 L 287.3,384.7 L 285.1,387.1 L 282.6,393.8 L 281.6,401.3 L 283.5,409.3 L 279.8,414.3 L 280.1,419.8 L 285.3,428.6 L 295.5,427.8 L 298.9,430.0 L 302.1,436.3 L 301.8,438.8 L 298.8,442.3 L 295.9,443.4 L 289.0,439.7 L 281.7,438.4 L 275.2,449.0 L 272.7,451.1 L 261.6,453.9 L 253.0,452.5 L 250.8,453.5 L 255.7,458.0 L 259.1,465.5 L 265.3,474.2 L 262.6,480.9 L 265.8,481.8 L 268.6,480.2 L 272.2,481.5 L 280.7,493.0 L 282.0,498.4 L 281.8,501.6 L 272.2,502.3 L 265.3,506.4 L 260.2,506.4 L 258.1,510.1 L 259.4,516.4 L 263.0,520.4 L 258.1,523.3 L 255.7,531.3 L 250.8,539.3 L 251.0,541.9 L 253.9,547.5 L 263.5,548.7 L 273.0,547.0 L 278.6,549.4 L 289.9,546.9 L 291.5,557.8 L 297.4,561.1 L 301.0,564.9 L 309.8,563.4 L 313.3,565.2 L 317.1,565.0 L 321.6,562.6 L 322.7,559.5 L 325.5,557.0 L 332.2,557.7 L 331.9,545.0 L 342.5,538.4 L 350.5,526.4 L 356.7,526.0 L 360.9,530.1 L 362.9,544.9 L 366.8,547.3 L 373.3,548.3 L 393.1,545.5 L 398.6,538.7 L 402.2,537.7 L 403.9,538.6 L 406.4,537.8 L 408.0,539.8 L 410.6,539.3 L 414.4,542.0 L 418.8,541.9 L 419.2,540.2 L 422.4,539.7 L 422.8,540.5 L 425.4,540.0 L 425.6,541.2 L 427.7,540.8 L 428.7,542.6 L 430.4,541.0 L 430.5,542.1 L 433.6,542.8 L 436.0,541.3 L 438.7,541.3 L 439.8,539.7 L 445.1,538.6 L 446.7,536.8 L 447.4,539.1 L 448.4,538.5 L 452.0,542.2 L 451.5,552.0 L 459.1,563.2 L 467.0,562.4 L 482.5,566.2 L 486.7,568.3 L 491.4,568.2 L 492.5,570.2 L 495.8,571.1 L 496.4,572.4 L 498.4,571.8 L 499.6,572.9 L 501.4,572.0 L 505.1,574.4 L 505.8,579.7 L 507.8,580.5 L 508.3,583.2 L 511.2,582.0 L 513.5,585.7 L 529.4,586.4 L 533.7,584.0 L 541.5,584.3 L 547.8,587.3 L 552.0,586.0 L 565.9,586.3 L 570.3,568.5 L 569.4,562.9 L 568.1,560.6 L 553.4,552.2 L 551.5,545.0 L 542.2,531.8 L 538.6,522.2 L 538.8,517.3 L 541.3,512.7 L 541.5,509.4 L 538.9,507.4 L 527.2,508.3 L 523.6,503.1 L 522.3,498.1 Z" fill="#0f111a" opacity="0.9" /><path d="M 488.3,453.2 L 492.5,451.4 L 491.9,448.7 L 493.2,446.6 L 488.3,447.5 L 487.9,443.5 L 490.4,442.6 L 497.3,443.1 L 502.5,440.6 L 505.4,444.6 L 510.3,445.0 L 513.8,444.1 L 515.1,442.4 L 517.9,442.5 L 517.7,439.5 L 521.4,436.9 L 521.1,435.0 L 515.7,428.1 L 515.4,425.3 L 514.1,426.6 L 512.2,426.2 L 511.3,427.8 L 505.1,428.1 L 503.2,430.4 L 498.8,430.3 L 496.2,427.7 L 499.0,425.2 L 506.3,426.0 L 511.0,411.8 L 509.0,411.6 L 507.7,409.5 L 510.6,409.1 L 511.8,410.1 L 512.1,407.6 L 514.2,408.0 L 512.4,406.5 L 513.6,402.7 L 512.7,400.5 L 514.0,398.6 L 511.6,398.6 L 510.5,396.2 L 508.9,396.0 L 508.4,397.6 L 505.7,398.2 L 504.0,400.0 L 502.9,397.9 L 499.3,401.0 L 498.4,400.4 L 498.2,402.0 L 497.5,400.9 L 495.0,401.5 L 493.1,397.6 L 493.9,396.4 L 491.2,394.6 L 492.0,392.2 L 490.1,393.9 L 489.9,392.4 L 484.7,392.2 L 483.8,385.8 L 481.5,386.0 L 481.7,384.0 L 479.8,383.5 L 478.9,381.7 L 476.7,382.3 L 475.8,380.7 L 474.6,380.8 L 473.8,376.8 L 475.9,373.8 L 478.5,375.5 L 479.6,374.4 L 482.0,376.2 L 481.9,374.5 L 484.4,373.8 L 484.5,377.3 L 485.2,376.3 L 485.4,377.4 L 487.9,377.2 L 488.8,376.9 L 488.4,375.1 L 490.5,375.3 L 489.9,372.5 L 492.5,371.0 L 494.5,370.8 L 495.9,372.6 L 497.9,371.5 L 499.0,365.3 L 500.5,363.3 L 500.0,362.1 L 497.0,362.1 L 497.0,360.3 L 494.0,358.4 L 493.9,356.2 L 494.6,355.5 L 495.7,356.7 L 499.3,356.8 L 500.6,355.8 L 499.6,353.0 L 501.1,353.0 L 502.5,350.9 L 507.6,349.9 L 506.9,348.7 L 508.4,348.3 L 508.3,345.8 L 510.6,346.2 L 513.4,344.7 L 514.2,340.8 L 511.9,335.8 L 512.4,333.7 L 511.1,332.9 L 513.0,328.8 L 511.8,326.6 L 513.2,322.5 L 502.4,318.5 L 500.6,313.2 L 498.8,312.6 L 496.6,308.8 L 487.3,305.2 L 482.6,306.9 L 474.0,303.3 L 471.6,302.0 L 469.9,298.8 L 466.5,299.7 L 463.5,298.0 L 459.6,292.4 L 457.5,294.8 L 455.2,295.4 L 453.9,294.9 L 454.1,293.8 L 451.5,294.3 L 447.4,292.9 L 446.7,295.0 L 443.2,292.8 L 443.2,296.9 L 446.0,299.3 L 436.4,296.9 L 430.7,293.8 L 427.0,296.7 L 424.9,296.8 L 423.4,295.5 L 420.6,296.4 L 419.9,294.9 L 411.4,297.3 L 409.1,293.8 L 403.8,295.8 L 404.2,293.6 L 401.4,291.9 L 397.3,292.5 L 392.2,296.4 L 388.4,294.7 L 382.1,294.5 L 377.0,296.4 L 374.0,299.9 L 371.7,298.0 L 369.1,293.2 L 372.5,283.2 L 366.5,269.9 L 353.9,256.0 L 360.8,251.5 L 361.6,246.9 L 357.5,242.1 L 357.8,231.9 L 358.8,230.1 L 363.3,227.7 L 367.8,230.1 L 371.0,226.0 L 377.4,230.4 L 381.5,230.1 L 383.0,221.0 L 380.7,216.4 L 380.2,210.6 L 384.9,210.1 L 390.5,212.1 L 387.5,208.8 L 380.0,204.2 L 373.8,202.5 L 372.5,198.3 L 366.7,198.4 L 358.0,192.3 L 342.6,189.4 L 333.5,190.2 L 328.5,193.5 L 318.6,192.6 L 320.3,196.0 L 320.3,199.2 L 313.0,199.5 L 310.6,204.8 L 312.3,210.7 L 311.8,216.2 L 309.2,218.4 L 312.3,229.7 L 311.2,236.2 L 309.5,239.3 L 304.4,243.2 L 290.7,246.9 L 283.5,250.4 L 279.3,250.4 L 277.1,244.6 L 272.6,238.2 L 268.8,236.9 L 256.8,243.0 L 246.1,235.4 L 238.8,227.5 L 229.7,226.8 L 214.0,230.7 L 205.3,230.6 L 197.4,232.5 L 193.3,239.7 L 189.0,239.9 L 186.0,245.6 L 164.9,248.7 L 165.2,250.7 L 161.9,255.3 L 162.0,258.7 L 164.2,262.3 L 170.0,265.9 L 173.8,270.6 L 173.3,278.1 L 175.3,284.4 L 174.0,288.3 L 168.9,291.8 L 168.8,294.5 L 170.4,297.7 L 173.7,298.1 L 177.6,302.2 L 176.6,304.6 L 169.8,309.2 L 166.9,315.7 L 161.6,318.1 L 163.4,325.0 L 160.8,328.8 L 163.3,332.3 L 175.8,334.2 L 177.9,336.3 L 178.3,345.0 L 182.9,348.1 L 182.2,354.0 L 185.8,360.9 L 181.2,365.5 L 183.5,367.1 L 187.0,366.4 L 193.2,369.9 L 194.2,372.9 L 199.4,376.0 L 204.0,384.9 L 208.4,386.5 L 210.4,388.9 L 210.8,395.6 L 216.8,399.3 L 218.4,403.1 L 216.8,404.9 L 217.3,409.1 L 225.2,420.3 L 228.6,433.8 L 234.7,435.6 L 236.6,438.1 L 238.7,438.6 L 238.4,441.1 L 244.6,445.2 L 245.3,450.3 L 248.8,453.2 L 250.4,453.8 L 253.0,452.5 L 261.6,453.9 L 272.7,451.1 L 275.2,449.0 L 281.7,438.4 L 289.0,439.7 L 295.9,443.4 L 298.8,442.3 L 301.8,438.8 L 302.1,436.3 L 298.9,430.0 L 295.5,427.8 L 285.3,428.6 L 280.1,419.8 L 279.8,414.3 L 283.5,409.3 L 281.6,401.3 L 282.6,393.8 L 285.1,387.1 L 287.3,384.7 L 292.5,383.5 L 300.9,387.9 L 304.1,388.4 L 309.4,386.2 L 311.3,380.7 L 314.4,379.1 L 320.5,381.7 L 332.0,381.0 L 334.5,379.5 L 335.9,373.1 L 337.9,370.2 L 348.1,368.2 L 355.8,370.4 L 361.9,375.3 L 364.9,375.6 L 368.3,379.7 L 375.2,382.6 L 380.2,380.7 L 386.0,381.0 L 383.5,386.5 L 384.8,394.5 L 384.3,398.8 L 380.6,401.0 L 381.7,404.1 L 380.4,404.7 L 379.7,403.3 L 377.4,404.2 L 377.6,406.6 L 374.3,407.0 L 373.9,408.8 L 370.7,409.8 L 368.8,411.9 L 368.2,414.7 L 369.5,416.1 L 368.6,418.4 L 369.6,422.3 L 365.8,431.9 L 367.7,438.9 L 371.8,443.5 L 382.8,462.0 L 381.5,465.9 L 382.6,474.2 L 389.6,475.2 L 393.1,471.7 L 397.0,470.1 L 401.9,471.7 L 399.6,466.6 L 397.6,465.6 L 398.3,461.3 L 394.8,456.5 L 398.6,451.4 L 398.8,440.9 L 401.6,438.4 L 402.8,440.2 L 405.6,440.5 L 407.1,443.1 L 409.8,439.8 L 411.2,440.4 L 420.8,438.1 L 423.1,436.9 L 424.3,431.0 L 423.6,429.2 L 427.8,428.2 L 439.6,428.2 L 453.5,437.3 L 466.7,448.5 L 480.2,452.6 L 488.3,453.2 Z" fill="#0f111a" opacity="0.9" /><path d="M 372.5,198.3 L 375.5,192.2 L 380.6,188.6 L 386.9,187.0 L 388.8,184.9 L 388.4,179.9 L 392.6,177.5 L 395.3,179.5 L 392.2,180.1 L 391.4,181.8 L 392.2,184.0 L 395.5,184.0 L 394.8,186.4 L 402.3,187.9 L 402.6,190.0 L 400.9,190.3 L 400.5,192.2 L 403.7,199.3 L 405.7,200.3 L 405.9,202.8 L 403.7,205.3 L 405.2,208.4 L 406.8,208.3 L 406.3,206.0 L 408.3,206.5 L 409.3,202.1 L 412.4,202.0 L 412.7,199.1 L 414.9,200.6 L 415.2,198.9 L 417.0,199.0 L 416.2,199.0 L 416.5,197.4 L 418.5,198.0 L 420.3,192.5 L 422.7,193.0 L 422.0,190.3 L 423.6,188.9 L 422.2,187.6 L 424.9,184.8 L 424.0,183.2 L 426.4,182.6 L 426.5,184.0 L 428.7,184.5 L 430.8,179.5 L 432.2,180.8 L 433.4,179.9 L 433.8,181.3 L 434.2,179.3 L 436.4,178.1 L 440.7,178.8 L 440.9,177.4 L 443.6,178.4 L 442.6,176.3 L 443.5,175.4 L 444.1,176.4 L 447.6,174.7 L 447.9,175.9 L 449.7,176.0 L 451.5,174.3 L 451.6,175.9 L 452.8,174.6 L 452.8,176.4 L 454.2,175.5 L 455.4,178.0 L 457.4,178.2 L 460.0,182.0 L 466.4,176.1 L 468.6,168.1 L 472.8,171.1 L 473.9,168.3 L 472.6,160.5 L 475.6,158.7 L 479.5,162.2 L 489.8,162.8 L 491.0,165.7 L 494.7,166.1 L 499.0,163.9 L 510.7,164.8 L 514.1,166.3 L 525.1,163.5 L 530.2,165.9 L 533.8,163.1 L 538.5,165.2 L 537.9,160.4 L 540.2,158.5 L 548.2,156.7 L 550.1,161.7 L 553.1,164.8 L 554.4,169.4 L 553.9,173.8 L 551.4,175.1 L 550.7,178.7 L 557.3,186.4 L 559.1,196.4 L 558.6,200.3 L 555.8,202.5 L 550.7,204.0 L 552.2,206.8 L 559.5,207.7 L 563.3,205.7 L 568.7,206.4 L 574.0,208.8 L 578.3,208.6 L 581.7,206.4 L 591.0,210.0 L 593.7,209.4 L 593.9,207.6 L 591.4,205.6 L 590.0,202.2 L 590.3,196.5 L 597.0,197.8 L 598.0,192.8 L 595.1,189.6 L 590.8,188.9 L 589.2,187.2 L 590.6,181.8 L 588.9,177.6 L 590.4,165.4 L 593.1,163.3 L 591.5,161.7 L 595.3,159.0 L 595.2,157.9 L 584.2,158.2 L 587.2,156.2 L 586.4,154.5 L 584.1,154.4 L 583.0,149.9 L 580.6,149.2 L 582.0,145.6 L 579.8,142.9 L 576.4,144.9 L 570.7,142.3 L 569.0,143.5 L 569.6,145.2 L 567.0,145.4 L 567.0,143.7 L 564.0,141.8 L 564.5,139.0 L 562.0,137.1 L 562.6,135.7 L 560.5,128.8 L 549.3,124.0 L 546.6,118.4 L 532.8,114.2 L 528.7,103.1 L 524.2,106.9 L 509.5,111.0 L 505.3,114.3 L 502.3,113.2 L 498.5,115.5 L 490.8,115.3 L 487.0,117.5 L 480.3,118.7 L 476.9,116.2 L 472.7,115.4 L 471.5,123.1 L 470.3,124.1 L 464.9,122.5 L 461.4,125.7 L 453.5,124.7 L 448.7,128.9 L 447.0,128.1 L 446.6,126.5 L 444.4,129.2 L 443.1,128.5 L 443.0,127.3 L 447.1,123.1 L 445.9,120.0 L 442.7,117.9 L 442.8,116.4 L 444.3,115.8 L 443.6,113.1 L 441.5,111.5 L 437.7,113.2 L 432.7,111.5 L 432.0,115.9 L 429.1,118.2 L 426.4,118.4 L 425.1,123.8 L 414.8,134.7 L 413.0,134.2 L 416.6,129.7 L 416.0,128.5 L 402.8,127.2 L 398.5,129.5 L 396.6,132.6 L 386.0,138.0 L 379.2,145.1 L 373.3,147.2 L 372.5,150.4 L 375.6,155.8 L 374.6,159.1 L 372.4,160.7 L 373.1,161.5 L 371.4,161.5 L 371.2,162.8 L 370.4,162.2 L 371.0,162.9 L 369.5,162.7 L 369.2,159.2 L 367.0,157.6 L 367.4,156.1 L 365.1,156.7 L 359.5,155.3 L 359.5,154.0 L 356.5,153.1 L 356.5,149.8 L 352.0,147.3 L 351.5,145.2 L 353.9,144.4 L 354.4,142.9 L 353.1,142.4 L 354.6,141.2 L 352.2,138.3 L 352.9,137.1 L 349.1,134.3 L 345.8,137.3 L 344.3,136.4 L 341.6,141.0 L 332.8,147.2 L 323.1,161.4 L 317.0,176.5 L 333.5,190.2 L 342.6,189.4 L 358.0,192.3 L 366.7,198.4 L 372.5,198.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 565.9,586.3 L 573.1,588.0 L 574.1,593.5 L 571.6,597.3 L 585.8,598.7 L 587.2,593.0 L 589.8,592.9 L 591.7,589.8 L 594.5,579.9 L 599.3,572.5 L 602.2,573.0 L 602.3,570.2 L 608.1,571.2 L 610.0,569.7 L 612.4,570.2 L 614.1,569.2 L 615.0,573.8 L 613.9,577.0 L 611.6,578.1 L 611.9,580.7 L 615.1,580.1 L 616.5,576.2 L 617.9,574.2 L 619.1,574.3 L 619.4,571.5 L 623.8,574.4 L 628.9,574.9 L 629.1,571.2 L 630.1,570.9 L 630.9,567.5 L 628.6,560.1 L 629.7,559.0 L 629.0,556.0 L 627.0,556.2 L 625.5,554.4 L 618.7,557.2 L 614.2,552.6 L 613.9,548.8 L 612.0,548.0 L 613.4,545.1 L 611.8,540.5 L 612.7,531.4 L 611.8,529.9 L 621.1,525.8 L 621.6,514.7 L 624.2,513.7 L 626.7,514.6 L 624.5,507.8 L 627.2,505.9 L 627.0,502.4 L 625.3,502.0 L 626.7,492.3 L 624.9,481.7 L 622.5,475.9 L 624.1,474.6 L 622.6,472.2 L 622.8,466.4 L 620.9,461.4 L 621.9,460.0 L 621.8,455.3 L 624.3,454.3 L 623.9,450.5 L 624.9,448.8 L 622.9,448.7 L 621.4,446.4 L 625.6,444.6 L 626.3,441.7 L 628.2,440.5 L 628.2,438.5 L 633.1,438.7 L 635.0,435.7 L 640.9,434.9 L 640.9,430.9 L 640.6,427.2 L 638.1,426.1 L 638.4,422.6 L 636.5,421.0 L 637.4,420.1 L 635.7,420.1 L 636.2,418.8 L 634.7,417.9 L 630.0,416.4 L 630.6,411.8 L 629.1,406.5 L 627.0,406.3 L 626.4,404.2 L 621.7,403.5 L 620.7,402.1 L 619.0,402.5 L 618.0,399.5 L 614.2,397.9 L 613.6,396.3 L 610.9,397.1 L 608.2,392.5 L 605.1,392.7 L 593.2,373.0 L 588.4,372.6 L 590.0,375.9 L 583.9,373.7 L 582.1,374.8 L 581.6,372.6 L 579.5,375.1 L 576.4,372.0 L 574.2,372.8 L 572.6,371.1 L 570.0,370.9 L 566.2,372.8 L 564.1,368.1 L 563.9,371.6 L 565.9,376.4 L 560.0,382.7 L 559.6,384.9 L 561.7,388.6 L 564.3,390.1 L 564.5,392.7 L 560.9,393.6 L 558.6,391.5 L 555.5,393.8 L 552.7,403.3 L 553.7,405.6 L 557.5,407.0 L 557.7,409.3 L 553.7,410.0 L 551.6,411.6 L 554.2,414.0 L 552.6,420.4 L 557.1,422.1 L 563.0,419.7 L 570.8,418.7 L 574.5,419.6 L 575.1,423.0 L 573.7,426.3 L 566.0,429.0 L 568.1,433.9 L 566.4,440.0 L 556.0,442.5 L 551.7,435.6 L 542.4,433.4 L 540.6,430.3 L 535.5,428.3 L 535.4,426.3 L 532.9,425.9 L 531.7,427.7 L 529.0,428.7 L 525.8,428.2 L 523.9,436.8 L 521.0,436.9 L 517.7,439.5 L 517.9,442.5 L 515.1,442.4 L 513.8,444.1 L 510.3,445.0 L 505.4,444.6 L 502.5,440.6 L 497.3,443.1 L 490.4,442.6 L 487.9,443.5 L 487.5,445.3 L 488.3,447.5 L 493.2,446.6 L 491.9,448.7 L 492.5,451.4 L 488.3,453.2 L 500.6,458.1 L 506.9,463.6 L 508.0,467.3 L 511.6,469.9 L 517.2,470.8 L 520.0,469.8 L 521.6,471.7 L 517.2,480.0 L 521.6,479.8 L 522.5,481.2 L 521.0,487.4 L 523.6,503.1 L 527.2,508.3 L 538.9,507.4 L 541.5,509.4 L 541.3,512.7 L 538.8,517.3 L 538.6,522.2 L 542.2,531.8 L 551.5,545.0 L 553.4,552.2 L 568.1,560.6 L 569.4,562.9 L 570.3,568.5 L 565.9,586.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 168.2,837.7 L 164.0,837.3 L 162.2,835.6 L 161.2,837.1 L 162.1,838.5 L 161.1,839.1 L 162.6,842.7 L 160.0,845.3 L 162.3,847.7 L 160.9,848.3 L 164.4,850.1 L 162.7,852.0 L 164.1,852.5 L 164.0,854.5 L 162.9,854.7 L 164.0,856.9 L 165.2,856.7 L 165.5,861.6 L 166.9,862.1 L 166.4,863.8 L 167.6,863.6 L 168.5,865.1 L 166.0,868.7 L 164.3,868.3 L 163.9,870.5 L 162.7,869.3 L 160.0,869.4 L 160.0,868.3 L 159.3,869.8 L 156.4,869.8 L 156.1,868.8 L 155.7,869.9 L 153.5,867.7 L 152.0,869.6 L 147.6,866.9 L 145.7,867.0 L 145.5,868.0 L 137.7,867.0 L 136.6,870.1 L 134.8,870.2 L 129.2,867.6 L 121.3,866.0 L 115.9,867.3 L 116.5,872.0 L 205.2,889.9 L 221.8,894.4 L 233.3,900.3 L 234.5,903.4 L 233.8,904.1 L 239.2,906.7 L 239.8,908.7 L 243.6,909.9 L 244.4,912.9 L 246.4,912.0 L 251.9,913.7 L 253.1,914.9 L 252.2,917.8 L 254.5,918.6 L 254.9,920.0 L 256.6,918.7 L 260.0,919.9 L 261.2,918.1 L 263.8,917.5 L 270.1,917.7 L 276.0,910.8 L 279.0,909.7 L 279.0,908.4 L 289.6,905.0 L 290.6,903.2 L 294.0,901.6 L 303.6,900.0 L 302.4,899.3 L 303.4,898.4 L 301.9,898.5 L 303.4,895.0 L 308.8,892.6 L 307.3,892.6 L 308.6,890.0 L 316.1,888.3 L 318.6,881.8 L 322.9,880.0 L 323.9,867.1 L 325.5,862.2 L 329.7,857.9 L 337.0,856.6 L 339.3,853.0 L 344.1,850.3 L 342.9,845.7 L 331.1,839.2 L 326.5,828.3 L 320.0,823.5 L 321.8,822.9 L 319.6,816.0 L 321.2,808.9 L 313.7,801.4 L 305.7,801.0 L 302.2,799.2 L 292.3,801.3 L 289.4,800.0 L 289.3,791.8 L 291.2,789.0 L 291.7,782.5 L 294.2,778.6 L 292.5,776.5 L 289.3,775.5 L 289.7,764.6 L 278.4,758.1 L 269.5,745.8 L 260.8,741.4 L 258.2,736.1 L 255.3,734.1 L 245.7,729.9 L 236.0,729.1 L 231.8,735.9 L 219.8,735.1 L 214.6,736.3 L 204.4,733.6 L 199.9,737.4 L 196.5,744.1 L 193.9,754.2 L 200.7,757.2 L 198.4,763.9 L 198.8,769.5 L 199.3,772.3 L 202.9,773.2 L 199.9,779.9 L 194.5,778.6 L 190.7,776.0 L 189.2,781.8 L 189.4,786.7 L 192.8,790.2 L 193.1,796.6 L 190.5,799.4 L 191.5,804.2 L 191.1,811.9 L 186.8,813.4 L 188.6,815.3 L 188.6,819.6 L 183.9,830.0 L 179.8,835.5 L 174.7,837.3 L 168.2,837.7 Z" fill="#0f111a" opacity="0.9" /><path d="M 513.5,585.7 L 501.6,586.0 L 496.3,590.3 L 494.2,594.9 L 483.1,598.0 L 477.6,602.5 L 456.0,603.7 L 432.6,638.1 L 427.3,641.8 L 413.7,643.3 L 409.2,647.5 L 409.4,651.9 L 412.0,656.0 L 415.0,658.1 L 418.1,663.8 L 416.8,666.6 L 412.4,668.6 L 410.3,673.6 L 403.3,680.0 L 401.9,683.3 L 394.2,688.5 L 388.9,700.0 L 386.1,702.7 L 383.5,715.3 L 377.9,720.0 L 378.3,722.3 L 379.7,723.0 L 378.8,724.6 L 379.7,727.4 L 376.5,729.4 L 377.9,730.9 L 375.9,732.2 L 377.8,732.2 L 378.6,734.4 L 376.2,735.1 L 377.0,736.5 L 374.9,738.3 L 375.7,740.4 L 372.5,742.4 L 373.2,745.0 L 371.1,746.2 L 370.4,748.9 L 371.4,750.3 L 372.9,749.9 L 374.3,752.5 L 373.4,755.0 L 374.2,755.5 L 373.1,756.8 L 371.0,756.9 L 370.6,759.1 L 374.6,761.1 L 387.6,777.3 L 391.1,778.9 L 452.3,782.8 L 465.2,782.0 L 472.7,785.2 L 476.3,777.4 L 477.8,776.3 L 482.0,776.1 L 488.5,782.7 L 495.2,781.2 L 498.2,777.1 L 503.8,779.2 L 511.0,777.2 L 519.6,772.7 L 522.1,768.6 L 528.5,769.9 L 533.7,764.9 L 539.9,761.7 L 547.9,755.4 L 548.1,750.3 L 552.9,749.8 L 563.4,744.2 L 575.4,743.1 L 580.2,747.3 L 581.2,739.2 L 578.0,733.9 L 573.2,733.2 L 564.9,734.8 L 559.8,732.6 L 552.5,727.3 L 551.4,724.5 L 555.4,716.2 L 554.3,715.8 L 552.8,711.1 L 549.3,709.3 L 548.9,706.8 L 554.2,699.3 L 557.9,689.7 L 554.3,679.1 L 551.2,675.0 L 551.2,671.3 L 556.8,661.3 L 568.5,652.1 L 569.8,648.0 L 568.3,638.1 L 565.6,629.9 L 562.1,605.1 L 565.9,586.3 L 552.0,586.0 L 547.8,587.3 L 541.5,584.3 L 533.7,584.0 L 529.4,586.4 L 513.5,585.7 Z" fill="#0f111a" opacity="0.9" /><path d="M 521.4,436.9 L 523.9,436.8 L 525.8,428.2 L 529.0,428.7 L 531.7,427.7 L 532.1,426.1 L 534.3,426.0 L 535.4,426.3 L 535.9,428.6 L 540.6,430.3 L 542.4,433.4 L 551.7,435.6 L 556.0,442.5 L 565.9,440.5 L 567.3,438.7 L 568.1,433.9 L 566.1,430.5 L 566.2,428.5 L 573.7,426.3 L 575.1,423.0 L 574.5,419.6 L 570.8,418.7 L 563.0,419.7 L 557.1,422.1 L 552.6,420.4 L 554.2,414.0 L 551.6,411.6 L 553.7,410.0 L 557.7,409.3 L 557.5,407.0 L 553.7,405.6 L 552.7,403.3 L 555.5,393.8 L 558.6,391.5 L 560.9,393.6 L 564.5,392.7 L 564.3,390.1 L 561.7,388.6 L 559.6,384.9 L 560.0,382.7 L 565.9,376.4 L 563.6,368.4 L 565.7,370.0 L 566.2,372.8 L 570.4,370.8 L 573.6,371.4 L 574.2,372.8 L 576.4,372.0 L 579.5,375.1 L 581.6,372.6 L 582.1,374.8 L 583.9,373.7 L 590.0,375.9 L 588.4,372.6 L 592.2,373.5 L 593.6,372.2 L 592.1,370.9 L 595.7,370.6 L 592.6,368.6 L 592.8,365.8 L 598.1,365.8 L 599.4,367.6 L 601.2,366.4 L 599.8,365.1 L 602.2,363.2 L 603.8,363.4 L 605.1,365.9 L 605.5,364.8 L 608.1,364.7 L 609.3,362.8 L 608.7,359.3 L 610.5,357.2 L 612.0,357.5 L 611.6,356.0 L 613.1,354.1 L 610.5,348.7 L 611.6,345.8 L 608.5,343.7 L 603.3,331.4 L 603.9,327.6 L 607.5,322.5 L 606.1,317.6 L 610.9,315.0 L 613.9,308.8 L 610.1,302.3 L 614.5,295.6 L 616.5,295.8 L 615.5,290.8 L 618.5,281.9 L 616.8,279.5 L 611.9,277.2 L 609.3,277.5 L 608.6,275.6 L 609.8,274.7 L 609.7,272.5 L 607.8,270.8 L 602.3,269.1 L 597.6,268.9 L 594.1,270.2 L 590.1,269.1 L 587.9,270.6 L 588.8,272.6 L 587.1,275.6 L 578.3,279.0 L 574.7,277.5 L 572.3,274.0 L 572.2,270.9 L 577.8,271.9 L 583.5,268.1 L 581.5,265.9 L 574.2,265.7 L 573.1,262.4 L 573.7,258.8 L 577.4,259.9 L 580.5,258.5 L 589.0,259.2 L 593.4,256.5 L 591.7,252.8 L 589.6,251.9 L 581.4,255.9 L 577.0,247.5 L 579.1,243.6 L 585.1,248.4 L 587.5,247.6 L 588.9,245.1 L 588.1,239.3 L 584.5,237.9 L 586.0,233.3 L 585.4,231.8 L 586.9,230.9 L 586.0,229.0 L 589.8,221.6 L 589.0,213.8 L 589.8,210.6 L 591.0,210.0 L 581.7,206.4 L 578.3,208.6 L 574.0,208.8 L 568.7,206.4 L 563.3,205.7 L 559.5,207.7 L 552.2,206.8 L 550.7,204.0 L 555.8,202.5 L 558.6,200.3 L 559.1,196.4 L 557.3,186.4 L 550.7,178.7 L 551.4,175.1 L 553.9,173.8 L 554.4,169.4 L 553.1,164.8 L 550.1,161.7 L 548.2,156.7 L 540.2,158.5 L 537.9,160.4 L 538.5,165.2 L 533.8,163.1 L 530.2,165.9 L 525.1,163.5 L 514.1,166.3 L 510.7,164.8 L 499.0,163.9 L 494.7,166.1 L 491.0,165.7 L 489.8,162.8 L 479.5,162.2 L 475.6,158.7 L 472.6,160.5 L 473.9,168.3 L 472.8,171.1 L 468.6,168.1 L 466.4,176.1 L 460.0,182.0 L 457.4,178.2 L 455.4,178.0 L 454.2,175.5 L 452.8,176.4 L 452.8,174.6 L 451.6,175.9 L 451.5,174.3 L 449.7,176.0 L 447.9,175.9 L 447.6,174.7 L 444.1,176.4 L 443.5,175.4 L 442.6,176.3 L 443.6,178.4 L 440.9,177.4 L 440.7,178.8 L 436.4,178.1 L 434.2,179.3 L 433.8,181.3 L 433.4,179.9 L 432.2,180.8 L 430.8,179.5 L 428.7,184.5 L 426.5,184.0 L 426.4,182.6 L 424.0,183.2 L 424.9,184.8 L 422.2,187.6 L 423.6,188.9 L 422.0,190.3 L 422.7,193.0 L 420.3,192.5 L 418.5,198.0 L 416.5,197.4 L 416.2,199.0 L 417.0,199.0 L 415.2,198.9 L 414.9,200.6 L 412.7,199.1 L 412.4,202.0 L 409.3,202.1 L 408.3,206.5 L 406.3,206.0 L 406.8,208.3 L 405.2,208.4 L 403.7,205.3 L 405.9,202.8 L 405.7,200.3 L 403.7,199.3 L 400.5,192.2 L 400.9,190.3 L 402.6,190.0 L 402.3,187.9 L 394.8,186.4 L 395.5,184.0 L 392.2,184.0 L 391.4,182.2 L 392.2,180.1 L 395.3,179.5 L 392.6,177.5 L 388.4,179.9 L 388.8,184.9 L 386.9,187.0 L 380.6,188.6 L 375.5,192.2 L 372.5,198.3 L 373.8,202.5 L 380.0,204.2 L 387.5,208.8 L 390.5,212.1 L 384.9,210.1 L 380.2,210.6 L 380.7,216.4 L 383.0,221.0 L 381.5,230.1 L 377.4,230.4 L 371.0,226.0 L 367.8,230.1 L 363.3,227.7 L 358.8,230.1 L 357.8,231.9 L 357.5,242.1 L 361.6,246.9 L 360.8,251.5 L 353.9,256.0 L 366.5,269.9 L 372.5,283.2 L 369.1,293.2 L 374.0,299.9 L 377.0,296.4 L 382.1,294.5 L 388.4,294.7 L 392.2,296.4 L 397.3,292.5 L 401.4,291.9 L 404.2,293.6 L 403.8,295.8 L 409.1,293.8 L 411.4,297.3 L 419.9,294.9 L 420.6,296.4 L 423.4,295.5 L 424.9,296.8 L 427.0,296.7 L 430.7,293.8 L 436.4,296.9 L 446.0,299.3 L 443.2,296.9 L 443.2,292.8 L 446.7,295.0 L 447.4,292.9 L 451.5,294.3 L 454.1,293.8 L 453.9,294.9 L 455.2,295.4 L 457.5,294.8 L 459.6,292.4 L 463.5,298.0 L 466.5,299.7 L 469.9,298.8 L 471.6,302.0 L 474.0,303.3 L 482.6,306.9 L 487.3,305.2 L 496.6,308.8 L 498.8,312.6 L 500.6,313.2 L 502.4,318.5 L 513.2,322.5 L 511.8,326.6 L 513.0,328.8 L 511.1,332.9 L 512.4,333.7 L 511.9,335.8 L 514.2,340.8 L 513.4,344.7 L 510.6,346.2 L 508.3,345.8 L 508.4,348.3 L 506.9,348.7 L 507.6,349.9 L 502.5,350.9 L 501.1,353.0 L 499.6,353.0 L 500.6,355.8 L 499.3,356.8 L 495.7,356.7 L 494.6,355.5 L 493.9,356.2 L 494.0,358.4 L 497.0,360.3 L 497.0,362.1 L 500.0,362.1 L 500.5,363.3 L 499.0,365.3 L 497.9,371.5 L 495.9,372.6 L 494.5,370.8 L 492.5,371.0 L 489.9,372.5 L 490.5,375.3 L 488.4,375.1 L 488.8,376.9 L 487.9,377.2 L 485.4,377.4 L 485.2,376.3 L 484.5,377.3 L 484.4,373.8 L 481.9,374.5 L 482.0,376.2 L 479.6,374.4 L 478.5,375.5 L 475.9,373.8 L 473.8,376.8 L 474.6,380.8 L 475.8,380.7 L 476.7,382.3 L 478.9,381.7 L 479.8,383.5 L 481.7,384.0 L 481.5,386.0 L 483.8,385.8 L 484.7,392.2 L 489.9,392.4 L 490.1,393.9 L 492.0,392.2 L 491.2,394.6 L 493.9,396.4 L 493.1,397.6 L 495.0,401.5 L 497.5,400.9 L 498.2,402.0 L 498.4,400.4 L 499.3,401.0 L 502.9,397.9 L 504.0,400.0 L 505.7,398.2 L 508.4,397.6 L 508.9,396.0 L 510.5,396.2 L 511.6,398.6 L 514.0,398.6 L 512.7,400.5 L 513.6,402.7 L 512.4,406.5 L 514.2,408.0 L 512.1,407.6 L 511.8,410.1 L 510.6,409.1 L 507.7,409.5 L 509.0,411.6 L 511.0,411.8 L 506.3,426.0 L 499.0,425.2 L 496.2,427.7 L 498.8,430.3 L 503.2,430.4 L 505.1,428.1 L 511.3,427.8 L 512.2,426.2 L 514.1,426.6 L 515.4,425.3 L 515.7,428.1 L 521.1,435.0 L 521.4,436.9 Z" fill="#0f111a" opacity="0.9" /><path d="M 245.7,729.9 L 258.2,736.1 L 260.8,741.4 L 269.5,745.8 L 278.4,758.1 L 289.7,764.6 L 289.3,775.5 L 292.5,776.5 L 294.2,778.6 L 291.7,782.5 L 291.2,789.0 L 289.3,791.8 L 289.4,800.0 L 292.3,801.3 L 302.2,799.2 L 305.7,801.0 L 313.7,801.4 L 321.2,808.9 L 319.6,816.0 L 321.8,822.9 L 320.0,823.5 L 326.5,828.3 L 331.1,839.2 L 342.9,845.7 L 344.1,850.3 L 339.3,853.0 L 337.0,856.6 L 329.7,857.9 L 325.5,862.2 L 323.9,867.1 L 322.9,880.0 L 335.1,878.4 L 356.7,873.1 L 356.8,872.1 L 360.6,871.0 L 371.3,870.3 L 392.2,857.6 L 430.2,854.9 L 441.5,845.3 L 440.9,844.4 L 441.9,843.3 L 455.9,838.9 L 458.2,836.1 L 472.8,832.6 L 477.6,827.1 L 477.4,825.6 L 483.4,822.5 L 487.9,818.4 L 481.3,807.3 L 482.0,802.5 L 473.0,791.8 L 473.6,788.1 L 472.7,785.2 L 465.2,782.0 L 452.3,782.8 L 391.1,778.9 L 387.6,777.3 L 374.6,761.1 L 370.9,759.4 L 368.5,759.3 L 368.4,762.5 L 366.6,760.9 L 363.5,763.3 L 362.0,762.7 L 362.1,757.7 L 357.2,759.8 L 358.3,757.7 L 356.7,758.7 L 356.5,756.9 L 352.7,756.5 L 352.2,759.1 L 347.0,758.5 L 346.8,761.0 L 345.7,759.9 L 344.5,761.2 L 342.5,760.5 L 341.9,762.3 L 340.6,760.6 L 341.2,766.5 L 339.1,767.5 L 338.2,765.8 L 335.7,765.7 L 335.7,764.5 L 332.9,764.7 L 333.5,762.7 L 336.1,762.6 L 335.1,762.0 L 335.9,760.9 L 333.6,759.3 L 333.0,757.4 L 331.6,758.1 L 331.7,756.0 L 329.5,756.1 L 330.6,757.3 L 327.7,758.1 L 325.9,756.9 L 324.5,758.8 L 323.5,756.6 L 322.9,758.1 L 316.9,756.8 L 312.5,759.2 L 309.7,757.0 L 309.6,755.4 L 307.4,757.3 L 307.1,753.8 L 305.1,754.1 L 304.1,752.0 L 301.3,752.8 L 299.7,751.7 L 298.9,753.0 L 298.2,750.2 L 295.9,749.2 L 295.2,747.6 L 294.4,748.6 L 293.4,746.8 L 294.6,744.0 L 293.7,743.1 L 292.0,743.9 L 291.6,742.6 L 289.6,742.8 L 287.9,745.6 L 285.9,746.2 L 283.2,743.3 L 282.1,745.4 L 279.3,746.1 L 277.7,742.8 L 274.3,739.8 L 274.0,740.9 L 272.8,739.7 L 271.7,740.4 L 270.9,735.0 L 269.1,733.5 L 270.2,731.6 L 268.8,730.6 L 265.5,732.3 L 265.5,730.1 L 264.1,729.4 L 264.6,724.3 L 261.9,720.5 L 264.5,716.9 L 264.2,712.7 L 266.6,710.4 L 265.3,706.8 L 259.5,707.0 L 250.4,714.6 L 243.3,718.2 L 243.8,727.1 L 245.7,729.9 Z" fill="#0f111a" opacity="0.9" /><path d="M 256.9,708.8 L 259.5,707.0 L 264.9,706.7 L 266.1,707.6 L 266.6,710.4 L 264.2,712.7 L 264.5,716.9 L 261.9,720.5 L 264.6,724.3 L 264.1,729.4 L 265.5,730.1 L 265.5,732.3 L 268.8,730.6 L 270.2,731.6 L 269.1,733.5 L 270.9,735.0 L 271.7,740.4 L 272.8,739.7 L 274.0,740.9 L 274.3,739.8 L 277.7,742.8 L 279.3,746.1 L 282.1,745.4 L 283.2,743.3 L 285.9,746.2 L 287.9,745.6 L 289.6,742.8 L 291.6,742.6 L 292.0,743.9 L 293.7,743.1 L 294.6,744.0 L 293.4,746.8 L 294.4,748.6 L 295.2,747.6 L 295.9,749.2 L 298.2,750.2 L 298.9,753.0 L 299.7,751.7 L 301.3,752.8 L 304.1,752.0 L 305.1,754.1 L 307.1,753.8 L 307.4,757.3 L 309.6,755.4 L 309.7,757.0 L 312.5,759.2 L 316.9,756.8 L 322.9,758.1 L 323.5,756.6 L 324.5,758.8 L 325.9,756.9 L 327.7,758.1 L 330.6,757.3 L 329.5,756.1 L 331.7,756.0 L 331.6,758.1 L 333.0,757.4 L 333.6,759.3 L 335.9,760.9 L 335.1,762.0 L 336.1,762.6 L 333.5,762.7 L 332.9,764.7 L 335.7,764.5 L 335.7,765.7 L 338.2,765.8 L 339.1,767.5 L 341.2,766.5 L 340.6,760.6 L 341.9,762.3 L 342.5,760.5 L 344.5,761.2 L 345.7,759.9 L 346.8,761.0 L 347.0,758.5 L 352.2,759.1 L 352.7,756.5 L 356.5,756.9 L 356.7,758.7 L 358.3,757.7 L 357.2,759.8 L 362.1,757.7 L 362.1,762.9 L 364.3,763.0 L 366.6,760.9 L 368.4,762.5 L 368.5,759.3 L 370.9,759.4 L 371.0,756.9 L 373.1,756.8 L 374.2,755.5 L 373.4,755.0 L 373.8,750.9 L 370.4,748.9 L 371.1,746.2 L 373.2,745.0 L 372.5,742.4 L 375.7,740.4 L 374.9,738.3 L 377.0,736.5 L 376.2,735.1 L 378.6,734.4 L 377.8,732.2 L 375.9,732.2 L 377.9,730.9 L 376.5,729.4 L 379.7,727.4 L 378.8,724.6 L 379.7,723.0 L 378.3,722.3 L 377.9,720.0 L 383.5,715.3 L 386.1,702.7 L 388.9,700.0 L 394.2,688.5 L 401.9,683.3 L 403.3,680.0 L 410.3,673.6 L 412.4,668.6 L 416.8,666.6 L 418.1,663.8 L 415.0,658.1 L 412.0,656.0 L 409.4,651.9 L 409.2,647.5 L 413.7,643.3 L 427.3,641.8 L 432.6,638.1 L 456.0,603.7 L 477.6,602.5 L 483.1,598.0 L 494.2,594.9 L 496.3,590.3 L 501.6,586.0 L 513.5,585.7 L 511.2,582.0 L 508.3,583.2 L 507.8,580.5 L 505.8,579.7 L 505.1,574.4 L 501.4,572.0 L 499.6,572.9 L 498.4,571.8 L 496.4,572.4 L 495.8,571.1 L 492.5,570.2 L 491.4,568.2 L 486.7,568.3 L 482.5,566.2 L 467.0,562.4 L 459.1,563.2 L 451.5,552.0 L 452.0,542.2 L 448.4,538.5 L 447.4,539.1 L 446.7,536.8 L 445.1,538.6 L 439.8,539.7 L 438.7,541.3 L 436.0,541.3 L 433.6,542.8 L 430.5,542.1 L 430.4,541.0 L 428.7,542.6 L 427.7,540.8 L 425.6,541.2 L 425.4,540.0 L 422.8,540.5 L 422.4,539.7 L 419.2,540.2 L 418.8,541.9 L 414.4,542.0 L 410.6,539.3 L 408.0,539.8 L 406.4,537.8 L 404.3,538.6 L 400.8,537.9 L 394.8,542.3 L 393.1,545.5 L 373.3,548.3 L 366.8,547.3 L 362.9,544.9 L 360.9,530.1 L 356.7,526.0 L 350.5,526.4 L 342.5,538.4 L 331.9,545.0 L 332.2,557.7 L 325.5,557.0 L 322.7,559.5 L 321.6,562.6 L 317.1,565.0 L 313.3,565.2 L 309.8,563.4 L 301.0,564.9 L 297.4,561.1 L 291.5,557.8 L 289.9,546.9 L 278.6,549.4 L 273.0,547.0 L 263.5,548.7 L 252.7,547.4 L 246.5,561.0 L 247.4,564.2 L 255.2,564.3 L 259.0,568.6 L 269.1,568.4 L 272.9,571.3 L 276.1,577.1 L 286.9,579.8 L 287.8,580.5 L 286.4,583.5 L 285.6,591.3 L 282.1,593.1 L 277.6,593.1 L 266.2,604.0 L 260.7,607.2 L 251.8,606.4 L 245.6,609.2 L 241.5,607.9 L 239.2,603.0 L 235.9,603.1 L 231.5,606.7 L 228.7,614.2 L 221.9,625.3 L 216.5,628.9 L 214.7,632.9 L 229.0,632.5 L 232.8,634.1 L 233.7,636.5 L 228.6,638.8 L 226.7,651.1 L 221.1,652.5 L 219.5,656.3 L 215.4,657.1 L 209.9,654.5 L 210.3,662.8 L 215.9,673.8 L 215.1,675.6 L 213.1,676.4 L 213.2,678.8 L 212.0,678.6 L 209.3,681.0 L 209.5,683.0 L 207.0,684.9 L 206.6,687.1 L 207.5,687.2 L 206.1,688.6 L 209.7,692.0 L 208.3,699.7 L 211.6,700.2 L 217.6,695.9 L 217.7,690.1 L 220.4,679.8 L 222.7,678.9 L 230.6,682.8 L 232.6,681.4 L 236.2,685.0 L 234.5,694.2 L 237.5,701.3 L 240.7,698.9 L 246.2,699.2 L 251.5,707.9 L 256.9,708.8 Z" fill="#0f111a" opacity="0.9" /><path d="M 148.8,633.3 L 152.2,627.3 L 152.8,618.0 L 157.3,614.8 L 158.6,610.7 L 163.3,607.7 L 164.6,602.3 L 167.6,598.6 L 172.7,597.8 L 173.4,593.2 L 179.6,593.0 L 185.8,586.8 L 187.9,586.8 L 189.0,582.4 L 192.8,580.1 L 200.7,579.9 L 200.1,593.4 L 210.6,594.5 L 213.8,597.3 L 210.4,598.9 L 217.9,597.5 L 220.4,594.1 L 224.1,595.4 L 224.9,598.4 L 229.2,595.4 L 227.9,590.9 L 223.0,586.1 L 221.1,580.3 L 230.9,578.8 L 233.1,573.0 L 239.5,568.2 L 245.2,567.5 L 247.4,564.2 L 246.5,561.0 L 249.1,553.5 L 252.7,547.4 L 253.9,547.5 L 250.8,539.3 L 255.7,531.3 L 258.1,523.3 L 263.0,520.4 L 259.4,516.4 L 258.1,510.1 L 260.2,506.4 L 265.3,506.4 L 272.2,502.3 L 281.8,501.6 L 280.7,493.0 L 272.2,481.5 L 268.6,480.2 L 265.8,481.8 L 262.6,480.9 L 265.3,474.2 L 259.1,465.5 L 255.7,458.0 L 245.3,450.3 L 244.6,445.2 L 239.6,442.5 L 238.4,441.1 L 238.7,438.6 L 236.6,438.1 L 234.7,435.6 L 228.6,433.8 L 225.2,420.3 L 217.3,409.1 L 216.8,404.9 L 218.4,403.1 L 216.8,399.3 L 210.8,395.6 L 210.4,388.9 L 208.4,386.5 L 204.0,384.9 L 199.4,376.0 L 194.2,372.9 L 193.2,369.9 L 187.5,366.6 L 199.7,432.8 L 200.0,445.9 L 192.0,454.2 L 184.3,453.3 L 181.7,457.2 L 181.9,460.4 L 185.7,466.3 L 184.8,470.0 L 174.5,471.3 L 166.6,481.2 L 165.3,484.7 L 162.4,481.1 L 160.6,480.7 L 159.3,492.9 L 153.8,498.7 L 154.4,500.7 L 151.5,503.7 L 151.3,506.5 L 141.3,527.2 L 140.2,543.2 L 134.8,565.5 L 134.1,574.0 L 137.2,578.4 L 127.4,591.5 L 126.3,601.1 L 118.7,603.7 L 115.7,608.7 L 118.3,617.0 L 120.6,620.0 L 124.5,621.0 L 133.9,618.6 L 137.1,619.2 L 143.8,624.1 L 148.8,633.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 565.9,586.3 L 562.1,605.1 L 565.6,629.9 L 568.3,638.1 L 569.8,648.0 L 568.5,652.1 L 556.8,661.3 L 551.2,671.3 L 551.2,675.0 L 554.3,679.1 L 557.9,689.7 L 554.2,699.3 L 548.9,706.2 L 549.3,709.3 L 552.8,711.1 L 554.3,715.8 L 555.4,716.2 L 551.4,724.5 L 551.6,726.1 L 559.8,732.6 L 564.9,734.8 L 576.9,733.3 L 579.6,735.2 L 581.2,739.2 L 580.2,747.3 L 585.2,748.9 L 584.3,756.1 L 585.6,756.7 L 597.0,751.7 L 611.9,748.5 L 626.9,766.3 L 625.7,771.3 L 627.0,776.2 L 629.1,777.5 L 636.0,778.8 L 651.0,778.8 L 666.1,777.1 L 669.5,775.7 L 675.6,768.4 L 677.1,763.4 L 676.8,758.0 L 679.8,752.8 L 688.9,741.2 L 697.8,735.3 L 705.0,732.3 L 704.9,724.5 L 689.0,724.4 L 689.0,722.0 L 686.3,717.1 L 681.0,712.6 L 678.6,707.0 L 678.1,701.7 L 663.0,702.0 L 658.8,695.6 L 655.2,693.3 L 648.8,691.7 L 647.9,688.4 L 643.4,686.9 L 642.7,681.5 L 640.2,680.5 L 638.3,675.3 L 643.1,670.6 L 642.3,667.7 L 640.5,666.4 L 635.5,666.8 L 633.0,663.7 L 632.0,665.0 L 630.8,664.5 L 627.8,659.9 L 629.8,655.4 L 628.6,648.8 L 629.6,646.4 L 625.5,644.4 L 624.2,645.5 L 620.4,643.5 L 618.3,639.8 L 620.2,637.2 L 615.9,633.6 L 614.7,634.8 L 613.7,634.3 L 613.6,630.4 L 615.2,629.4 L 614.1,627.8 L 617.3,626.2 L 618.3,621.7 L 612.4,618.6 L 612.2,614.8 L 616.1,612.0 L 622.3,610.5 L 623.5,607.8 L 622.6,602.3 L 624.9,597.3 L 621.8,595.3 L 623.6,589.8 L 625.2,589.3 L 625.2,586.0 L 626.3,585.9 L 627.6,582.6 L 628.9,574.9 L 623.8,574.4 L 619.4,571.5 L 619.1,574.3 L 617.9,574.2 L 616.5,576.2 L 615.1,580.1 L 611.9,580.7 L 611.6,578.1 L 613.9,577.0 L 615.0,573.8 L 614.1,569.2 L 612.4,570.2 L 610.0,569.7 L 608.1,571.2 L 602.3,570.2 L 602.2,573.0 L 599.3,572.5 L 594.5,579.9 L 591.7,589.8 L 589.8,592.9 L 587.2,593.0 L 585.8,598.7 L 571.6,597.3 L 574.1,593.5 L 573.1,588.0 L 565.9,586.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 333.5,190.2 L 317.0,176.5 L 323.1,161.4 L 332.8,147.2 L 341.6,141.0 L 344.3,136.4 L 340.4,134.1 L 340.9,131.1 L 342.9,130.1 L 341.7,127.8 L 337.8,127.0 L 337.0,125.3 L 333.4,123.9 L 333.5,121.6 L 332.0,120.2 L 328.5,120.5 L 326.7,118.7 L 325.8,109.5 L 327.9,98.7 L 331.2,98.4 L 332.6,94.4 L 335.9,93.7 L 336.6,87.2 L 338.7,87.1 L 339.6,85.2 L 341.5,84.5 L 343.0,68.8 L 345.6,64.2 L 345.9,61.0 L 320.0,61.0 L 319.9,65.9 L 309.7,66.0 L 309.6,67.6 L 301.6,67.7 L 301.6,66.1 L 230.1,64.7 L 199.0,65.3 L 153.4,63.0 L 152.5,68.4 L 155.4,71.9 L 155.7,74.2 L 152.9,78.5 L 149.3,79.3 L 149.3,83.4 L 147.2,87.0 L 145.0,96.1 L 139.2,101.9 L 139.0,103.8 L 142.9,105.0 L 143.6,106.8 L 138.7,113.6 L 138.7,116.3 L 144.3,125.9 L 144.4,129.2 L 147.8,133.6 L 149.0,138.5 L 153.4,140.9 L 161.7,142.9 L 160.8,144.8 L 153.7,147.6 L 151.1,157.1 L 153.5,160.2 L 163.1,164.7 L 164.0,167.0 L 163.4,169.7 L 158.5,173.5 L 158.3,175.0 L 159.8,181.4 L 159.0,184.5 L 160.5,197.0 L 165.8,206.0 L 163.1,212.3 L 163.3,216.5 L 168.1,223.7 L 167.3,226.0 L 163.7,228.0 L 159.4,235.3 L 160.4,238.0 L 160.0,242.6 L 163.8,244.3 L 164.9,248.7 L 186.0,245.6 L 189.0,239.9 L 193.3,239.7 L 197.4,232.5 L 205.3,230.6 L 214.0,230.7 L 229.7,226.8 L 238.8,227.5 L 246.1,235.4 L 256.8,243.0 L 268.8,236.9 L 272.6,238.2 L 277.1,244.6 L 279.3,250.4 L 283.5,250.4 L 290.7,246.9 L 304.4,243.2 L 309.5,239.3 L 311.2,236.2 L 312.3,229.7 L 309.2,218.4 L 311.8,216.2 L 312.3,210.7 L 310.6,204.8 L 313.0,199.5 L 320.3,199.2 L 320.3,196.0 L 318.6,192.6 L 328.5,193.5 L 333.5,190.2 Z" fill="#0f111a" opacity="0.9" /><path d="M 528.7,103.1 L 531.0,102.3 L 531.0,95.4 L 538.0,88.4 L 536.9,83.8 L 539.9,75.6 L 540.1,68.9 L 545.5,66.8 L 544.2,55.3 L 538.9,48.4 L 535.6,49.4 L 533.4,52.2 L 527.9,52.1 L 523.6,49.4 L 521.4,49.4 L 522.3,44.8 L 503.3,40.0 L 501.4,43.6 L 503.8,45.1 L 503.7,46.9 L 495.4,49.0 L 494.5,51.9 L 492.4,52.5 L 491.8,54.4 L 489.6,49.4 L 490.0,47.1 L 485.9,46.5 L 482.5,48.3 L 480.8,55.0 L 481.0,59.8 L 477.8,60.1 L 476.0,59.0 L 475.3,60.7 L 472.3,61.5 L 471.1,65.5 L 468.8,63.9 L 466.4,67.4 L 465.3,65.3 L 463.2,65.0 L 460.8,69.3 L 459.8,74.4 L 456.4,76.1 L 455.3,72.3 L 452.3,70.8 L 451.7,68.9 L 449.2,69.6 L 451.5,65.6 L 449.0,65.2 L 447.8,66.3 L 447.4,64.3 L 430.5,64.2 L 430.5,63.1 L 427.8,63.0 L 427.6,64.2 L 424.0,64.2 L 422.0,68.5 L 418.7,69.1 L 419.4,66.6 L 415.6,66.6 L 415.6,63.6 L 388.5,63.4 L 388.0,65.9 L 351.8,64.4 L 350.1,62.3 L 345.9,61.0 L 345.6,64.2 L 343.0,68.8 L 341.5,84.5 L 339.6,85.2 L 338.7,87.1 L 336.6,87.2 L 335.9,93.7 L 332.6,94.4 L 331.2,98.4 L 327.9,98.7 L 325.8,109.5 L 326.7,118.7 L 328.5,120.5 L 332.0,120.2 L 333.5,121.6 L 333.4,123.9 L 337.0,125.3 L 337.8,127.0 L 341.7,127.8 L 342.9,130.1 L 340.9,131.1 L 340.4,134.1 L 343.2,136.5 L 345.8,137.3 L 349.1,134.3 L 352.9,137.1 L 352.2,138.3 L 354.6,141.2 L 353.1,142.4 L 354.4,142.9 L 353.9,144.4 L 351.5,145.2 L 352.0,147.3 L 356.5,149.8 L 356.5,153.1 L 359.5,154.0 L 359.5,155.3 L 365.1,156.7 L 367.4,156.1 L 367.1,157.9 L 369.2,159.2 L 369.2,162.1 L 370.3,163.1 L 371.4,161.5 L 373.1,161.5 L 372.4,160.7 L 374.6,159.1 L 375.6,155.8 L 372.5,150.4 L 373.3,147.2 L 379.2,145.1 L 386.0,138.0 L 396.6,132.6 L 398.5,129.5 L 402.8,127.2 L 416.0,128.5 L 416.6,129.7 L 413.0,134.2 L 414.8,134.7 L 425.1,123.8 L 426.4,118.4 L 429.1,118.2 L 432.0,115.9 L 432.7,111.5 L 437.7,113.2 L 441.5,111.5 L 443.6,113.1 L 444.3,115.8 L 442.8,116.4 L 442.7,117.9 L 445.9,120.0 L 447.1,123.1 L 443.0,127.3 L 443.1,128.5 L 444.4,129.2 L 446.6,126.5 L 447.0,128.1 L 448.7,128.9 L 453.5,124.7 L 461.4,125.7 L 464.9,122.5 L 470.3,124.1 L 471.5,123.1 L 472.7,115.4 L 476.9,116.2 L 480.3,118.7 L 487.0,117.5 L 490.8,115.3 L 498.5,115.5 L 502.3,113.2 L 505.3,114.3 L 509.5,111.0 L 524.2,106.9 L 528.7,103.1 Z" fill="#0f111a" opacity="0.9" /><path d="M 472.7,785.2 L 473.6,788.1 L 473.0,791.8 L 482.0,802.5 L 481.3,807.3 L 487.9,818.4 L 495.6,815.2 L 512.3,811.5 L 513.0,810.3 L 527.0,805.7 L 532.7,801.4 L 542.4,799.3 L 543.6,798.6 L 541.6,798.9 L 542.9,797.1 L 543.2,798.4 L 543.8,797.6 L 543.1,796.3 L 543.4,797.1 L 550.2,789.4 L 557.9,787.7 L 565.4,782.9 L 571.0,782.2 L 578.9,778.5 L 601.3,776.4 L 632.8,778.5 L 627.0,776.2 L 625.7,771.3 L 626.9,766.3 L 611.9,748.5 L 597.0,751.7 L 585.6,756.7 L 584.3,756.1 L 585.2,748.9 L 578.8,746.5 L 575.4,743.1 L 572.3,742.8 L 563.4,744.2 L 552.9,749.8 L 548.1,750.3 L 547.9,755.4 L 539.9,761.7 L 533.7,764.9 L 528.5,769.9 L 522.1,768.6 L 519.6,772.7 L 511.0,777.2 L 503.8,779.2 L 498.2,777.1 L 495.2,781.2 L 488.5,782.7 L 482.0,776.1 L 477.8,776.3 L 476.3,777.4 L 472.7,785.2 Z" fill="#0f111a" opacity="0.9" />
                  </g>

                  <!-- 2. Primary 16 Ghana Regions Polygons -->
                  <g id="regionsPolygonsGroup">
                    
            <!-- Western North Region -->
            <path id="path-western-north"
                  data-name="Western North"
                  data-capital="Sefwi Wiawso"
                  data-iso="GH-WN"
                  data-cases="134"
                  data-active-ops="1"
                  data-status="Mining Basin Intel"
                  data-badge="Wiawso Station"
                  data-commander="DSP T. Arthur"
                  data-officers="11"
                  data-narcotics="24.6 kg"
                  data-firearms="7"
                  data-devices="9"
                  data-vehicles="2"
                  data-cx="154.5"
                  data-cy="730.7"
                  d="M 189.2,781.8 L 190.7,776.0 L 194.5,778.6 L 199.9,779.9 L 202.9,773.2 L 199.3,772.3 L 198.8,769.5 L 198.4,763.9 L 200.7,757.2 L 193.9,754.2 L 196.5,744.1 L 199.9,737.4 L 204.4,733.6 L 214.6,736.3 L 219.8,735.1 L 231.8,735.9 L 236.0,729.1 L 245.7,729.9 L 243.8,727.1 L 243.3,718.2 L 250.4,714.6 L 256.9,708.8 L 251.5,707.9 L 246.2,699.2 L 240.7,698.9 L 237.5,701.3 L 234.5,694.2 L 236.2,685.0 L 232.6,681.4 L 230.6,682.8 L 222.7,678.9 L 220.4,679.8 L 219.6,681.6 L 217.6,695.9 L 211.6,700.2 L 208.3,699.7 L 209.4,694.5 L 195.1,693.6 L 191.9,683.0 L 189.0,680.4 L 181.3,677.0 L 178.0,669.4 L 175.4,668.1 L 167.7,668.3 L 158.0,662.8 L 152.6,661.4 L 151.3,656.6 L 151.0,640.4 L 148.8,633.3 L 143.8,624.1 L 137.1,619.2 L 133.9,618.6 L 124.5,621.0 L 120.6,620.0 L 118.3,617.0 L 115.7,608.7 L 99.0,635.2 L 98.4,641.3 L 101.3,643.7 L 102.1,647.6 L 99.1,651.8 L 98.6,656.3 L 96.8,657.4 L 95.0,662.4 L 98.8,665.4 L 98.4,673.6 L 107.4,707.0 L 106.7,713.0 L 109.6,713.2 L 116.6,726.7 L 120.6,749.7 L 128.0,767.2 L 127.1,787.4 L 137.0,786.4 L 135.3,796.7 L 137.8,797.2 L 139.8,799.6 L 146.2,797.9 L 150.1,794.5 L 150.0,795.4 L 155.2,798.6 L 156.9,798.5 L 157.5,799.9 L 159.5,798.5 L 161.5,800.1 L 161.3,801.7 L 163.3,802.3 L 162.8,810.9 L 165.0,824.5 L 168.4,831.3 L 168.2,837.7 L 174.7,837.3 L 179.8,835.5 L 183.9,830.0 L 188.6,819.6 L 188.6,815.3 L 186.8,813.4 L 191.1,811.9 L 191.5,804.2 L 190.5,799.4 L 193.1,796.6 L 192.8,790.2 L 189.4,786.7 L 189.2,781.8 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Western North')"
                  onmouseenter="onRegionHover(event, 'Western North')"
                  onmouseleave="onRegionLeave()" />
            <!-- Ahafo Region -->
            <path id="path-ahafo"
                  data-name="Ahafo"
                  data-capital="Goaso"
                  data-iso="GH-AF"
                  data-cases="96"
                  data-active-ops="1"
                  data-status="Forest Reserve Ops"
                  data-badge="Goaso Sector"
                  data-commander="Insp. K. Poku"
                  data-officers="10"
                  data-narcotics="14.5 kg"
                  data-firearms="5"
                  data-devices="6"
                  data-vehicles="2"
                  data-cx="210.7"
                  data-cy="639.4"
                  d="M 209.4,694.5 L 209.7,692.0 L 206.1,688.6 L 207.5,687.2 L 206.6,687.1 L 207.0,684.9 L 209.5,683.0 L 209.9,680.4 L 213.2,678.8 L 213.1,676.4 L 215.1,675.6 L 215.9,673.8 L 210.3,662.8 L 209.9,654.5 L 215.4,657.1 L 219.5,656.3 L 221.1,652.5 L 226.7,651.1 L 228.6,638.8 L 233.7,636.5 L 232.8,634.1 L 229.0,632.5 L 214.7,632.9 L 214.9,631.1 L 221.9,625.3 L 228.7,614.2 L 231.5,606.7 L 235.9,603.1 L 239.2,603.0 L 241.5,607.9 L 245.6,609.2 L 251.8,606.4 L 260.7,607.2 L 266.2,604.0 L 277.6,593.1 L 282.1,593.1 L 285.6,591.3 L 286.4,583.5 L 287.8,580.5 L 276.1,577.1 L 272.9,571.3 L 269.1,568.4 L 259.0,568.6 L 255.2,564.3 L 247.4,564.2 L 245.2,567.5 L 239.5,568.2 L 233.1,573.0 L 230.9,578.8 L 221.1,580.3 L 223.0,586.1 L 227.9,590.9 L 229.2,595.4 L 224.9,598.4 L 224.1,595.4 L 220.4,594.1 L 217.9,597.5 L 210.4,598.9 L 213.8,597.3 L 210.6,594.5 L 200.1,593.4 L 200.7,579.9 L 192.8,580.1 L 189.0,582.4 L 187.9,586.8 L 185.8,586.8 L 179.6,593.0 L 173.4,593.2 L 172.7,597.8 L 167.6,598.6 L 164.6,602.3 L 163.3,607.7 L 158.6,610.7 L 157.3,614.8 L 152.8,618.0 L 152.2,627.3 L 148.8,633.3 L 151.6,644.5 L 151.3,656.6 L 152.6,661.4 L 158.0,662.8 L 167.7,668.3 L 175.4,668.1 L 178.0,669.4 L 181.3,677.0 L 189.0,680.4 L 191.9,683.0 L 195.1,693.6 L 209.4,694.5 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Ahafo')"
                  onmouseenter="onRegionHover(event, 'Ahafo')"
                  onmouseleave="onRegionLeave()" />
            <!-- Bono East Region -->
            <path id="path-bono-east"
                  data-name="Bono East"
                  data-capital="Techiman"
                  data-iso="GH-BE"
                  data-cases="175"
                  data-active-ops="1"
                  data-status="Commercial Transit"
                  data-badge="Techiman Central"
                  data-commander="DSP G. Yeboah"
                  data-officers="13"
                  data-narcotics="26.8 kg"
                  data-firearms="7"
                  data-devices="11"
                  data-vehicles="3"
                  data-cx="404.5"
                  data-cy="488.9"
                  d="M 522.3,498.1 L 521.0,487.4 L 522.5,481.2 L 521.6,479.8 L 517.2,480.0 L 521.2,473.4 L 521.6,471.7 L 520.0,469.8 L 517.2,470.8 L 511.6,469.9 L 508.0,467.3 L 506.9,463.6 L 497.3,456.3 L 488.3,453.2 L 480.2,452.6 L 466.7,448.5 L 453.5,437.3 L 439.6,428.2 L 431.3,427.7 L 423.6,429.2 L 424.3,431.0 L 423.1,436.9 L 417.2,439.2 L 409.8,439.8 L 407.1,443.1 L 405.6,440.5 L 402.8,440.2 L 401.2,438.5 L 398.8,440.9 L 398.6,451.4 L 394.8,456.5 L 398.3,461.3 L 397.6,465.6 L 399.6,466.6 L 401.9,471.7 L 397.0,470.1 L 393.1,471.7 L 389.6,475.2 L 382.6,474.2 L 381.5,465.9 L 382.8,462.0 L 371.8,443.5 L 367.7,438.9 L 365.8,431.9 L 369.6,422.3 L 368.6,418.4 L 369.5,416.1 L 368.2,414.7 L 368.8,411.9 L 370.7,409.8 L 373.9,408.8 L 374.3,407.0 L 377.6,406.6 L 377.4,404.2 L 379.7,403.3 L 380.4,404.7 L 381.7,404.1 L 380.6,401.0 L 384.3,398.8 L 384.8,394.5 L 383.5,386.5 L 386.0,381.0 L 380.2,380.7 L 375.2,382.6 L 368.3,379.7 L 364.9,375.6 L 361.9,375.3 L 355.8,370.4 L 348.1,368.2 L 337.9,370.2 L 335.9,373.1 L 334.5,379.5 L 332.0,381.0 L 320.5,381.7 L 314.4,379.1 L 311.3,380.7 L 309.4,386.2 L 304.1,388.4 L 300.9,387.9 L 292.5,383.5 L 287.3,384.7 L 285.1,387.1 L 282.6,393.8 L 281.6,401.3 L 283.5,409.3 L 279.8,414.3 L 280.1,419.8 L 285.3,428.6 L 295.5,427.8 L 298.9,430.0 L 302.1,436.3 L 301.8,438.8 L 298.8,442.3 L 295.9,443.4 L 289.0,439.7 L 281.7,438.4 L 275.2,449.0 L 272.7,451.1 L 261.6,453.9 L 253.0,452.5 L 250.8,453.5 L 255.7,458.0 L 259.1,465.5 L 265.3,474.2 L 262.6,480.9 L 265.8,481.8 L 268.6,480.2 L 272.2,481.5 L 280.7,493.0 L 282.0,498.4 L 281.8,501.6 L 272.2,502.3 L 265.3,506.4 L 260.2,506.4 L 258.1,510.1 L 259.4,516.4 L 263.0,520.4 L 258.1,523.3 L 255.7,531.3 L 250.8,539.3 L 251.0,541.9 L 253.9,547.5 L 263.5,548.7 L 273.0,547.0 L 278.6,549.4 L 289.9,546.9 L 291.5,557.8 L 297.4,561.1 L 301.0,564.9 L 309.8,563.4 L 313.3,565.2 L 317.1,565.0 L 321.6,562.6 L 322.7,559.5 L 325.5,557.0 L 332.2,557.7 L 331.9,545.0 L 342.5,538.4 L 350.5,526.4 L 356.7,526.0 L 360.9,530.1 L 362.9,544.9 L 366.8,547.3 L 373.3,548.3 L 393.1,545.5 L 398.6,538.7 L 402.2,537.7 L 403.9,538.6 L 406.4,537.8 L 408.0,539.8 L 410.6,539.3 L 414.4,542.0 L 418.8,541.9 L 419.2,540.2 L 422.4,539.7 L 422.8,540.5 L 425.4,540.0 L 425.6,541.2 L 427.7,540.8 L 428.7,542.6 L 430.4,541.0 L 430.5,542.1 L 433.6,542.8 L 436.0,541.3 L 438.7,541.3 L 439.8,539.7 L 445.1,538.6 L 446.7,536.8 L 447.4,539.1 L 448.4,538.5 L 452.0,542.2 L 451.5,552.0 L 459.1,563.2 L 467.0,562.4 L 482.5,566.2 L 486.7,568.3 L 491.4,568.2 L 492.5,570.2 L 495.8,571.1 L 496.4,572.4 L 498.4,571.8 L 499.6,572.9 L 501.4,572.0 L 505.1,574.4 L 505.8,579.7 L 507.8,580.5 L 508.3,583.2 L 511.2,582.0 L 513.5,585.7 L 529.4,586.4 L 533.7,584.0 L 541.5,584.3 L 547.8,587.3 L 552.0,586.0 L 565.9,586.3 L 570.3,568.5 L 569.4,562.9 L 568.1,560.6 L 553.4,552.2 L 551.5,545.0 L 542.2,531.8 L 538.6,522.2 L 538.8,517.3 L 541.3,512.7 L 541.5,509.4 L 538.9,507.4 L 527.2,508.3 L 523.6,503.1 L 522.3,498.1 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Bono East')"
                  onmouseenter="onRegionHover(event, 'Bono East')"
                  onmouseleave="onRegionLeave()" />
            <!-- Savannah Region -->
            <path id="path-savannah"
                  data-name="Savannah"
                  data-capital="Damongo"
                  data-iso="GH-SV"
                  data-cases="116"
                  data-active-ops="1"
                  data-status="Wildlife Corridor"
                  data-badge="Damongo Field Base"
                  data-commander="DSP B. Seidu"
                  data-officers="11"
                  data-narcotics="21.0 kg"
                  data-firearms="8"
                  data-devices="7"
                  data-vehicles="3"
                  data-cx="396.5"
                  data-cy="374.4"
                  d="M 488.3,453.2 L 492.5,451.4 L 491.9,448.7 L 493.2,446.6 L 488.3,447.5 L 487.9,443.5 L 490.4,442.6 L 497.3,443.1 L 502.5,440.6 L 505.4,444.6 L 510.3,445.0 L 513.8,444.1 L 515.1,442.4 L 517.9,442.5 L 517.7,439.5 L 521.4,436.9 L 521.1,435.0 L 515.7,428.1 L 515.4,425.3 L 514.1,426.6 L 512.2,426.2 L 511.3,427.8 L 505.1,428.1 L 503.2,430.4 L 498.8,430.3 L 496.2,427.7 L 499.0,425.2 L 506.3,426.0 L 511.0,411.8 L 509.0,411.6 L 507.7,409.5 L 510.6,409.1 L 511.8,410.1 L 512.1,407.6 L 514.2,408.0 L 512.4,406.5 L 513.6,402.7 L 512.7,400.5 L 514.0,398.6 L 511.6,398.6 L 510.5,396.2 L 508.9,396.0 L 508.4,397.6 L 505.7,398.2 L 504.0,400.0 L 502.9,397.9 L 499.3,401.0 L 498.4,400.4 L 498.2,402.0 L 497.5,400.9 L 495.0,401.5 L 493.1,397.6 L 493.9,396.4 L 491.2,394.6 L 492.0,392.2 L 490.1,393.9 L 489.9,392.4 L 484.7,392.2 L 483.8,385.8 L 481.5,386.0 L 481.7,384.0 L 479.8,383.5 L 478.9,381.7 L 476.7,382.3 L 475.8,380.7 L 474.6,380.8 L 473.8,376.8 L 475.9,373.8 L 478.5,375.5 L 479.6,374.4 L 482.0,376.2 L 481.9,374.5 L 484.4,373.8 L 484.5,377.3 L 485.2,376.3 L 485.4,377.4 L 487.9,377.2 L 488.8,376.9 L 488.4,375.1 L 490.5,375.3 L 489.9,372.5 L 492.5,371.0 L 494.5,370.8 L 495.9,372.6 L 497.9,371.5 L 499.0,365.3 L 500.5,363.3 L 500.0,362.1 L 497.0,362.1 L 497.0,360.3 L 494.0,358.4 L 493.9,356.2 L 494.6,355.5 L 495.7,356.7 L 499.3,356.8 L 500.6,355.8 L 499.6,353.0 L 501.1,353.0 L 502.5,350.9 L 507.6,349.9 L 506.9,348.7 L 508.4,348.3 L 508.3,345.8 L 510.6,346.2 L 513.4,344.7 L 514.2,340.8 L 511.9,335.8 L 512.4,333.7 L 511.1,332.9 L 513.0,328.8 L 511.8,326.6 L 513.2,322.5 L 502.4,318.5 L 500.6,313.2 L 498.8,312.6 L 496.6,308.8 L 487.3,305.2 L 482.6,306.9 L 474.0,303.3 L 471.6,302.0 L 469.9,298.8 L 466.5,299.7 L 463.5,298.0 L 459.6,292.4 L 457.5,294.8 L 455.2,295.4 L 453.9,294.9 L 454.1,293.8 L 451.5,294.3 L 447.4,292.9 L 446.7,295.0 L 443.2,292.8 L 443.2,296.9 L 446.0,299.3 L 436.4,296.9 L 430.7,293.8 L 427.0,296.7 L 424.9,296.8 L 423.4,295.5 L 420.6,296.4 L 419.9,294.9 L 411.4,297.3 L 409.1,293.8 L 403.8,295.8 L 404.2,293.6 L 401.4,291.9 L 397.3,292.5 L 392.2,296.4 L 388.4,294.7 L 382.1,294.5 L 377.0,296.4 L 374.0,299.9 L 371.7,298.0 L 369.1,293.2 L 372.5,283.2 L 366.5,269.9 L 353.9,256.0 L 360.8,251.5 L 361.6,246.9 L 357.5,242.1 L 357.8,231.9 L 358.8,230.1 L 363.3,227.7 L 367.8,230.1 L 371.0,226.0 L 377.4,230.4 L 381.5,230.1 L 383.0,221.0 L 380.7,216.4 L 380.2,210.6 L 384.9,210.1 L 390.5,212.1 L 387.5,208.8 L 380.0,204.2 L 373.8,202.5 L 372.5,198.3 L 366.7,198.4 L 358.0,192.3 L 342.6,189.4 L 333.5,190.2 L 328.5,193.5 L 318.6,192.6 L 320.3,196.0 L 320.3,199.2 L 313.0,199.5 L 310.6,204.8 L 312.3,210.7 L 311.8,216.2 L 309.2,218.4 L 312.3,229.7 L 311.2,236.2 L 309.5,239.3 L 304.4,243.2 L 290.7,246.9 L 283.5,250.4 L 279.3,250.4 L 277.1,244.6 L 272.6,238.2 L 268.8,236.9 L 256.8,243.0 L 246.1,235.4 L 238.8,227.5 L 229.7,226.8 L 214.0,230.7 L 205.3,230.6 L 197.4,232.5 L 193.3,239.7 L 189.0,239.9 L 186.0,245.6 L 164.9,248.7 L 165.2,250.7 L 161.9,255.3 L 162.0,258.7 L 164.2,262.3 L 170.0,265.9 L 173.8,270.6 L 173.3,278.1 L 175.3,284.4 L 174.0,288.3 L 168.9,291.8 L 168.8,294.5 L 170.4,297.7 L 173.7,298.1 L 177.6,302.2 L 176.6,304.6 L 169.8,309.2 L 166.9,315.7 L 161.6,318.1 L 163.4,325.0 L 160.8,328.8 L 163.3,332.3 L 175.8,334.2 L 177.9,336.3 L 178.3,345.0 L 182.9,348.1 L 182.2,354.0 L 185.8,360.9 L 181.2,365.5 L 183.5,367.1 L 187.0,366.4 L 193.2,369.9 L 194.2,372.9 L 199.4,376.0 L 204.0,384.9 L 208.4,386.5 L 210.4,388.9 L 210.8,395.6 L 216.8,399.3 L 218.4,403.1 L 216.8,404.9 L 217.3,409.1 L 225.2,420.3 L 228.6,433.8 L 234.7,435.6 L 236.6,438.1 L 238.7,438.6 L 238.4,441.1 L 244.6,445.2 L 245.3,450.3 L 248.8,453.2 L 250.4,453.8 L 253.0,452.5 L 261.6,453.9 L 272.7,451.1 L 275.2,449.0 L 281.7,438.4 L 289.0,439.7 L 295.9,443.4 L 298.8,442.3 L 301.8,438.8 L 302.1,436.3 L 298.9,430.0 L 295.5,427.8 L 285.3,428.6 L 280.1,419.8 L 279.8,414.3 L 283.5,409.3 L 281.6,401.3 L 282.6,393.8 L 285.1,387.1 L 287.3,384.7 L 292.5,383.5 L 300.9,387.9 L 304.1,388.4 L 309.4,386.2 L 311.3,380.7 L 314.4,379.1 L 320.5,381.7 L 332.0,381.0 L 334.5,379.5 L 335.9,373.1 L 337.9,370.2 L 348.1,368.2 L 355.8,370.4 L 361.9,375.3 L 364.9,375.6 L 368.3,379.7 L 375.2,382.6 L 380.2,380.7 L 386.0,381.0 L 383.5,386.5 L 384.8,394.5 L 384.3,398.8 L 380.6,401.0 L 381.7,404.1 L 380.4,404.7 L 379.7,403.3 L 377.4,404.2 L 377.6,406.6 L 374.3,407.0 L 373.9,408.8 L 370.7,409.8 L 368.8,411.9 L 368.2,414.7 L 369.5,416.1 L 368.6,418.4 L 369.6,422.3 L 365.8,431.9 L 367.7,438.9 L 371.8,443.5 L 382.8,462.0 L 381.5,465.9 L 382.6,474.2 L 389.6,475.2 L 393.1,471.7 L 397.0,470.1 L 401.9,471.7 L 399.6,466.6 L 397.6,465.6 L 398.3,461.3 L 394.8,456.5 L 398.6,451.4 L 398.8,440.9 L 401.6,438.4 L 402.8,440.2 L 405.6,440.5 L 407.1,443.1 L 409.8,439.8 L 411.2,440.4 L 420.8,438.1 L 423.1,436.9 L 424.3,431.0 L 423.6,429.2 L 427.8,428.2 L 439.6,428.2 L 453.5,437.3 L 466.7,448.5 L 480.2,452.6 L 488.3,453.2 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Savannah')"
                  onmouseenter="onRegionHover(event, 'Savannah')"
                  onmouseleave="onRegionLeave()" />
            <!-- North East Region -->
            <path id="path-north-east"
                  data-name="North East"
                  data-capital="Nalerigu"
                  data-iso="GH-NE"
                  data-cases="72"
                  data-active-ops="1"
                  data-status="Scarp Reconnaissance"
                  data-badge="Gambaga Command"
                  data-commander="Insp. S. Tampuri"
                  data-officers="8"
                  data-narcotics="11.8 kg"
                  data-firearms="6"
                  data-devices="4"
                  data-vehicles="1"
                  data-cx="474.7"
                  data-cy="154.8"
                  d="M 372.5,198.3 L 375.5,192.2 L 380.6,188.6 L 386.9,187.0 L 388.8,184.9 L 388.4,179.9 L 392.6,177.5 L 395.3,179.5 L 392.2,180.1 L 391.4,181.8 L 392.2,184.0 L 395.5,184.0 L 394.8,186.4 L 402.3,187.9 L 402.6,190.0 L 400.9,190.3 L 400.5,192.2 L 403.7,199.3 L 405.7,200.3 L 405.9,202.8 L 403.7,205.3 L 405.2,208.4 L 406.8,208.3 L 406.3,206.0 L 408.3,206.5 L 409.3,202.1 L 412.4,202.0 L 412.7,199.1 L 414.9,200.6 L 415.2,198.9 L 417.0,199.0 L 416.2,199.0 L 416.5,197.4 L 418.5,198.0 L 420.3,192.5 L 422.7,193.0 L 422.0,190.3 L 423.6,188.9 L 422.2,187.6 L 424.9,184.8 L 424.0,183.2 L 426.4,182.6 L 426.5,184.0 L 428.7,184.5 L 430.8,179.5 L 432.2,180.8 L 433.4,179.9 L 433.8,181.3 L 434.2,179.3 L 436.4,178.1 L 440.7,178.8 L 440.9,177.4 L 443.6,178.4 L 442.6,176.3 L 443.5,175.4 L 444.1,176.4 L 447.6,174.7 L 447.9,175.9 L 449.7,176.0 L 451.5,174.3 L 451.6,175.9 L 452.8,174.6 L 452.8,176.4 L 454.2,175.5 L 455.4,178.0 L 457.4,178.2 L 460.0,182.0 L 466.4,176.1 L 468.6,168.1 L 472.8,171.1 L 473.9,168.3 L 472.6,160.5 L 475.6,158.7 L 479.5,162.2 L 489.8,162.8 L 491.0,165.7 L 494.7,166.1 L 499.0,163.9 L 510.7,164.8 L 514.1,166.3 L 525.1,163.5 L 530.2,165.9 L 533.8,163.1 L 538.5,165.2 L 537.9,160.4 L 540.2,158.5 L 548.2,156.7 L 550.1,161.7 L 553.1,164.8 L 554.4,169.4 L 553.9,173.8 L 551.4,175.1 L 550.7,178.7 L 557.3,186.4 L 559.1,196.4 L 558.6,200.3 L 555.8,202.5 L 550.7,204.0 L 552.2,206.8 L 559.5,207.7 L 563.3,205.7 L 568.7,206.4 L 574.0,208.8 L 578.3,208.6 L 581.7,206.4 L 591.0,210.0 L 593.7,209.4 L 593.9,207.6 L 591.4,205.6 L 590.0,202.2 L 590.3,196.5 L 597.0,197.8 L 598.0,192.8 L 595.1,189.6 L 590.8,188.9 L 589.2,187.2 L 590.6,181.8 L 588.9,177.6 L 590.4,165.4 L 593.1,163.3 L 591.5,161.7 L 595.3,159.0 L 595.2,157.9 L 584.2,158.2 L 587.2,156.2 L 586.4,154.5 L 584.1,154.4 L 583.0,149.9 L 580.6,149.2 L 582.0,145.6 L 579.8,142.9 L 576.4,144.9 L 570.7,142.3 L 569.0,143.5 L 569.6,145.2 L 567.0,145.4 L 567.0,143.7 L 564.0,141.8 L 564.5,139.0 L 562.0,137.1 L 562.6,135.7 L 560.5,128.8 L 549.3,124.0 L 546.6,118.4 L 532.8,114.2 L 528.7,103.1 L 524.2,106.9 L 509.5,111.0 L 505.3,114.3 L 502.3,113.2 L 498.5,115.5 L 490.8,115.3 L 487.0,117.5 L 480.3,118.7 L 476.9,116.2 L 472.7,115.4 L 471.5,123.1 L 470.3,124.1 L 464.9,122.5 L 461.4,125.7 L 453.5,124.7 L 448.7,128.9 L 447.0,128.1 L 446.6,126.5 L 444.4,129.2 L 443.1,128.5 L 443.0,127.3 L 447.1,123.1 L 445.9,120.0 L 442.7,117.9 L 442.8,116.4 L 444.3,115.8 L 443.6,113.1 L 441.5,111.5 L 437.7,113.2 L 432.7,111.5 L 432.0,115.9 L 429.1,118.2 L 426.4,118.4 L 425.1,123.8 L 414.8,134.7 L 413.0,134.2 L 416.6,129.7 L 416.0,128.5 L 402.8,127.2 L 398.5,129.5 L 396.6,132.6 L 386.0,138.0 L 379.2,145.1 L 373.3,147.2 L 372.5,150.4 L 375.6,155.8 L 374.6,159.1 L 372.4,160.7 L 373.1,161.5 L 371.4,161.5 L 371.2,162.8 L 370.4,162.2 L 371.0,162.9 L 369.5,162.7 L 369.2,159.2 L 367.0,157.6 L 367.4,156.1 L 365.1,156.7 L 359.5,155.3 L 359.5,154.0 L 356.5,153.1 L 356.5,149.8 L 352.0,147.3 L 351.5,145.2 L 353.9,144.4 L 354.4,142.9 L 353.1,142.4 L 354.6,141.2 L 352.2,138.3 L 352.9,137.1 L 349.1,134.3 L 345.8,137.3 L 344.3,136.4 L 341.6,141.0 L 332.8,147.2 L 323.1,161.4 L 317.0,176.5 L 333.5,190.2 L 342.6,189.4 L 358.0,192.3 L 366.7,198.4 L 372.5,198.3 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('North East')"
                  onmouseenter="onRegionHover(event, 'North East')"
                  onmouseleave="onRegionLeave()" />
            <!-- Oti Region -->
            <path id="path-oti"
                  data-name="Oti"
                  data-capital="Dambai"
                  data-iso="GH-OT"
                  data-cases="84"
                  data-active-ops="1"
                  data-status="Riverine Patrol"
                  data-badge="Lake Volta Basin"
                  data-commander="Insp. N. Gyamfi"
                  data-officers="9"
                  data-narcotics="16.2 kg"
                  data-firearms="4"
                  data-devices="5"
                  data-vehicles="2"
                  data-cx="592.7"
                  data-cy="467.7"
                  d="M 565.9,586.3 L 573.1,588.0 L 574.1,593.5 L 571.6,597.3 L 585.8,598.7 L 587.2,593.0 L 589.8,592.9 L 591.7,589.8 L 594.5,579.9 L 599.3,572.5 L 602.2,573.0 L 602.3,570.2 L 608.1,571.2 L 610.0,569.7 L 612.4,570.2 L 614.1,569.2 L 615.0,573.8 L 613.9,577.0 L 611.6,578.1 L 611.9,580.7 L 615.1,580.1 L 616.5,576.2 L 617.9,574.2 L 619.1,574.3 L 619.4,571.5 L 623.8,574.4 L 628.9,574.9 L 629.1,571.2 L 630.1,570.9 L 630.9,567.5 L 628.6,560.1 L 629.7,559.0 L 629.0,556.0 L 627.0,556.2 L 625.5,554.4 L 618.7,557.2 L 614.2,552.6 L 613.9,548.8 L 612.0,548.0 L 613.4,545.1 L 611.8,540.5 L 612.7,531.4 L 611.8,529.9 L 621.1,525.8 L 621.6,514.7 L 624.2,513.7 L 626.7,514.6 L 624.5,507.8 L 627.2,505.9 L 627.0,502.4 L 625.3,502.0 L 626.7,492.3 L 624.9,481.7 L 622.5,475.9 L 624.1,474.6 L 622.6,472.2 L 622.8,466.4 L 620.9,461.4 L 621.9,460.0 L 621.8,455.3 L 624.3,454.3 L 623.9,450.5 L 624.9,448.8 L 622.9,448.7 L 621.4,446.4 L 625.6,444.6 L 626.3,441.7 L 628.2,440.5 L 628.2,438.5 L 633.1,438.7 L 635.0,435.7 L 640.9,434.9 L 640.9,430.9 L 640.6,427.2 L 638.1,426.1 L 638.4,422.6 L 636.5,421.0 L 637.4,420.1 L 635.7,420.1 L 636.2,418.8 L 634.7,417.9 L 630.0,416.4 L 630.6,411.8 L 629.1,406.5 L 627.0,406.3 L 626.4,404.2 L 621.7,403.5 L 620.7,402.1 L 619.0,402.5 L 618.0,399.5 L 614.2,397.9 L 613.6,396.3 L 610.9,397.1 L 608.2,392.5 L 605.1,392.7 L 593.2,373.0 L 588.4,372.6 L 590.0,375.9 L 583.9,373.7 L 582.1,374.8 L 581.6,372.6 L 579.5,375.1 L 576.4,372.0 L 574.2,372.8 L 572.6,371.1 L 570.0,370.9 L 566.2,372.8 L 564.1,368.1 L 563.9,371.6 L 565.9,376.4 L 560.0,382.7 L 559.6,384.9 L 561.7,388.6 L 564.3,390.1 L 564.5,392.7 L 560.9,393.6 L 558.6,391.5 L 555.5,393.8 L 552.7,403.3 L 553.7,405.6 L 557.5,407.0 L 557.7,409.3 L 553.7,410.0 L 551.6,411.6 L 554.2,414.0 L 552.6,420.4 L 557.1,422.1 L 563.0,419.7 L 570.8,418.7 L 574.5,419.6 L 575.1,423.0 L 573.7,426.3 L 566.0,429.0 L 568.1,433.9 L 566.4,440.0 L 556.0,442.5 L 551.7,435.6 L 542.4,433.4 L 540.6,430.3 L 535.5,428.3 L 535.4,426.3 L 532.9,425.9 L 531.7,427.7 L 529.0,428.7 L 525.8,428.2 L 523.9,436.8 L 521.0,436.9 L 517.7,439.5 L 517.9,442.5 L 515.1,442.4 L 513.8,444.1 L 510.3,445.0 L 505.4,444.6 L 502.5,440.6 L 497.3,443.1 L 490.4,442.6 L 487.9,443.5 L 487.5,445.3 L 488.3,447.5 L 493.2,446.6 L 491.9,448.7 L 492.5,451.4 L 488.3,453.2 L 500.6,458.1 L 506.9,463.6 L 508.0,467.3 L 511.6,469.9 L 517.2,470.8 L 520.0,469.8 L 521.6,471.7 L 517.2,480.0 L 521.6,479.8 L 522.5,481.2 L 521.0,487.4 L 523.6,503.1 L 527.2,508.3 L 538.9,507.4 L 541.5,509.4 L 541.3,512.7 L 538.8,517.3 L 538.6,522.2 L 542.2,531.8 L 551.5,545.0 L 553.4,552.2 L 568.1,560.6 L 569.4,562.9 L 570.3,568.5 L 565.9,586.3 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Oti')"
                  onmouseenter="onRegionHover(event, 'Oti')"
                  onmouseleave="onRegionLeave()" />
            <!-- Western Region -->
            <path id="path-western"
                  data-name="Western"
                  data-capital="Sekondi-Takoradi"
                  data-iso="GH-WP"
                  data-cases="512"
                  data-active-ops="4"
                  data-status="Maritime Surveillance"
                  data-badge="Deep Sea Patrol"
                  data-commander="Supt. D. Armah"
                  data-officers="24"
                  data-narcotics="310.8 kg"
                  data-firearms="14"
                  data-devices="31"
                  data-vehicles="11"
                  data-cx="226.6"
                  data-cy="869.2"
                  d="M 168.2,837.7 L 164.0,837.3 L 162.2,835.6 L 161.2,837.1 L 162.1,838.5 L 161.1,839.1 L 162.6,842.7 L 160.0,845.3 L 162.3,847.7 L 160.9,848.3 L 164.4,850.1 L 162.7,852.0 L 164.1,852.5 L 164.0,854.5 L 162.9,854.7 L 164.0,856.9 L 165.2,856.7 L 165.5,861.6 L 166.9,862.1 L 166.4,863.8 L 167.6,863.6 L 168.5,865.1 L 166.0,868.7 L 164.3,868.3 L 163.9,870.5 L 162.7,869.3 L 160.0,869.4 L 160.0,868.3 L 159.3,869.8 L 156.4,869.8 L 156.1,868.8 L 155.7,869.9 L 153.5,867.7 L 152.0,869.6 L 147.6,866.9 L 145.7,867.0 L 145.5,868.0 L 137.7,867.0 L 136.6,870.1 L 134.8,870.2 L 129.2,867.6 L 121.3,866.0 L 115.9,867.3 L 116.5,872.0 L 205.2,889.9 L 221.8,894.4 L 233.3,900.3 L 234.5,903.4 L 233.8,904.1 L 239.2,906.7 L 239.8,908.7 L 243.6,909.9 L 244.4,912.9 L 246.4,912.0 L 251.9,913.7 L 253.1,914.9 L 252.2,917.8 L 254.5,918.6 L 254.9,920.0 L 256.6,918.7 L 260.0,919.9 L 261.2,918.1 L 263.8,917.5 L 270.1,917.7 L 276.0,910.8 L 279.0,909.7 L 279.0,908.4 L 289.6,905.0 L 290.6,903.2 L 294.0,901.6 L 303.6,900.0 L 302.4,899.3 L 303.4,898.4 L 301.9,898.5 L 303.4,895.0 L 308.8,892.6 L 307.3,892.6 L 308.6,890.0 L 316.1,888.3 L 318.6,881.8 L 322.9,880.0 L 323.9,867.1 L 325.5,862.2 L 329.7,857.9 L 337.0,856.6 L 339.3,853.0 L 344.1,850.3 L 342.9,845.7 L 331.1,839.2 L 326.5,828.3 L 320.0,823.5 L 321.8,822.9 L 319.6,816.0 L 321.2,808.9 L 313.7,801.4 L 305.7,801.0 L 302.2,799.2 L 292.3,801.3 L 289.4,800.0 L 289.3,791.8 L 291.2,789.0 L 291.7,782.5 L 294.2,778.6 L 292.5,776.5 L 289.3,775.5 L 289.7,764.6 L 278.4,758.1 L 269.5,745.8 L 260.8,741.4 L 258.2,736.1 L 255.3,734.1 L 245.7,729.9 L 236.0,729.1 L 231.8,735.9 L 219.8,735.1 L 214.6,736.3 L 204.4,733.6 L 199.9,737.4 L 196.5,744.1 L 193.9,754.2 L 200.7,757.2 L 198.4,763.9 L 198.8,769.5 L 199.3,772.3 L 202.9,773.2 L 199.9,779.9 L 194.5,778.6 L 190.7,776.0 L 189.2,781.8 L 189.4,786.7 L 192.8,790.2 L 193.1,796.6 L 190.5,799.4 L 191.5,804.2 L 191.1,811.9 L 186.8,813.4 L 188.6,815.3 L 188.6,819.6 L 183.9,830.0 L 179.8,835.5 L 174.7,837.3 L 168.2,837.7 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Western')"
                  onmouseenter="onRegionHover(event, 'Western')"
                  onmouseleave="onRegionLeave()" />
            <!-- Eastern Region -->
            <path id="path-eastern"
                  data-name="Eastern"
                  data-capital="Koforidua"
                  data-iso="GH-EP"
                  data-cases="310"
                  data-active-ops="2"
                  data-status="Normal Patrol"
                  data-badge="Inland Checkpoint"
                  data-commander="DSP P. Osei"
                  data-officers="18"
                  data-narcotics="45.1 kg"
                  data-firearms="8"
                  data-devices="22"
                  data-vehicles="5"
                  data-cx="453.4"
                  data-cy="713.0"
                  d="M 513.5,585.7 L 501.6,586.0 L 496.3,590.3 L 494.2,594.9 L 483.1,598.0 L 477.6,602.5 L 456.0,603.7 L 432.6,638.1 L 427.3,641.8 L 413.7,643.3 L 409.2,647.5 L 409.4,651.9 L 412.0,656.0 L 415.0,658.1 L 418.1,663.8 L 416.8,666.6 L 412.4,668.6 L 410.3,673.6 L 403.3,680.0 L 401.9,683.3 L 394.2,688.5 L 388.9,700.0 L 386.1,702.7 L 383.5,715.3 L 377.9,720.0 L 378.3,722.3 L 379.7,723.0 L 378.8,724.6 L 379.7,727.4 L 376.5,729.4 L 377.9,730.9 L 375.9,732.2 L 377.8,732.2 L 378.6,734.4 L 376.2,735.1 L 377.0,736.5 L 374.9,738.3 L 375.7,740.4 L 372.5,742.4 L 373.2,745.0 L 371.1,746.2 L 370.4,748.9 L 371.4,750.3 L 372.9,749.9 L 374.3,752.5 L 373.4,755.0 L 374.2,755.5 L 373.1,756.8 L 371.0,756.9 L 370.6,759.1 L 374.6,761.1 L 387.6,777.3 L 391.1,778.9 L 452.3,782.8 L 465.2,782.0 L 472.7,785.2 L 476.3,777.4 L 477.8,776.3 L 482.0,776.1 L 488.5,782.7 L 495.2,781.2 L 498.2,777.1 L 503.8,779.2 L 511.0,777.2 L 519.6,772.7 L 522.1,768.6 L 528.5,769.9 L 533.7,764.9 L 539.9,761.7 L 547.9,755.4 L 548.1,750.3 L 552.9,749.8 L 563.4,744.2 L 575.4,743.1 L 580.2,747.3 L 581.2,739.2 L 578.0,733.9 L 573.2,733.2 L 564.9,734.8 L 559.8,732.6 L 552.5,727.3 L 551.4,724.5 L 555.4,716.2 L 554.3,715.8 L 552.8,711.1 L 549.3,709.3 L 548.9,706.8 L 554.2,699.3 L 557.9,689.7 L 554.3,679.1 L 551.2,675.0 L 551.2,671.3 L 556.8,661.3 L 568.5,652.1 L 569.8,648.0 L 568.3,638.1 L 565.6,629.9 L 562.1,605.1 L 565.9,586.3 L 552.0,586.0 L 547.8,587.3 L 541.5,584.3 L 533.7,584.0 L 529.4,586.4 L 513.5,585.7 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Eastern')"
                  onmouseenter="onRegionHover(event, 'Eastern')"
                  onmouseleave="onRegionLeave()" />
            <!-- Northern Region -->
            <path id="path-northern"
                  data-name="Northern"
                  data-capital="Tamale"
                  data-iso="GH-NP"
                  data-cases="385"
                  data-active-ops="3"
                  data-status="Cross-Border Sector"
                  data-badge="Northern Shield"
                  data-commander="Supt. I. Alhassan"
                  data-officers="22"
                  data-narcotics="64.0 kg"
                  data-firearms="23"
                  data-devices="19"
                  data-vehicles="7"
                  data-cx="517.3"
                  data-cy="311.8"
                  d="M 521.4,436.9 L 523.9,436.8 L 525.8,428.2 L 529.0,428.7 L 531.7,427.7 L 532.1,426.1 L 534.3,426.0 L 535.4,426.3 L 535.9,428.6 L 540.6,430.3 L 542.4,433.4 L 551.7,435.6 L 556.0,442.5 L 565.9,440.5 L 567.3,438.7 L 568.1,433.9 L 566.1,430.5 L 566.2,428.5 L 573.7,426.3 L 575.1,423.0 L 574.5,419.6 L 570.8,418.7 L 563.0,419.7 L 557.1,422.1 L 552.6,420.4 L 554.2,414.0 L 551.6,411.6 L 553.7,410.0 L 557.7,409.3 L 557.5,407.0 L 553.7,405.6 L 552.7,403.3 L 555.5,393.8 L 558.6,391.5 L 560.9,393.6 L 564.5,392.7 L 564.3,390.1 L 561.7,388.6 L 559.6,384.9 L 560.0,382.7 L 565.9,376.4 L 563.6,368.4 L 565.7,370.0 L 566.2,372.8 L 570.4,370.8 L 573.6,371.4 L 574.2,372.8 L 576.4,372.0 L 579.5,375.1 L 581.6,372.6 L 582.1,374.8 L 583.9,373.7 L 590.0,375.9 L 588.4,372.6 L 592.2,373.5 L 593.6,372.2 L 592.1,370.9 L 595.7,370.6 L 592.6,368.6 L 592.8,365.8 L 598.1,365.8 L 599.4,367.6 L 601.2,366.4 L 599.8,365.1 L 602.2,363.2 L 603.8,363.4 L 605.1,365.9 L 605.5,364.8 L 608.1,364.7 L 609.3,362.8 L 608.7,359.3 L 610.5,357.2 L 612.0,357.5 L 611.6,356.0 L 613.1,354.1 L 610.5,348.7 L 611.6,345.8 L 608.5,343.7 L 603.3,331.4 L 603.9,327.6 L 607.5,322.5 L 606.1,317.6 L 610.9,315.0 L 613.9,308.8 L 610.1,302.3 L 614.5,295.6 L 616.5,295.8 L 615.5,290.8 L 618.5,281.9 L 616.8,279.5 L 611.9,277.2 L 609.3,277.5 L 608.6,275.6 L 609.8,274.7 L 609.7,272.5 L 607.8,270.8 L 602.3,269.1 L 597.6,268.9 L 594.1,270.2 L 590.1,269.1 L 587.9,270.6 L 588.8,272.6 L 587.1,275.6 L 578.3,279.0 L 574.7,277.5 L 572.3,274.0 L 572.2,270.9 L 577.8,271.9 L 583.5,268.1 L 581.5,265.9 L 574.2,265.7 L 573.1,262.4 L 573.7,258.8 L 577.4,259.9 L 580.5,258.5 L 589.0,259.2 L 593.4,256.5 L 591.7,252.8 L 589.6,251.9 L 581.4,255.9 L 577.0,247.5 L 579.1,243.6 L 585.1,248.4 L 587.5,247.6 L 588.9,245.1 L 588.1,239.3 L 584.5,237.9 L 586.0,233.3 L 585.4,231.8 L 586.9,230.9 L 586.0,229.0 L 589.8,221.6 L 589.0,213.8 L 589.8,210.6 L 591.0,210.0 L 581.7,206.4 L 578.3,208.6 L 574.0,208.8 L 568.7,206.4 L 563.3,205.7 L 559.5,207.7 L 552.2,206.8 L 550.7,204.0 L 555.8,202.5 L 558.6,200.3 L 559.1,196.4 L 557.3,186.4 L 550.7,178.7 L 551.4,175.1 L 553.9,173.8 L 554.4,169.4 L 553.1,164.8 L 550.1,161.7 L 548.2,156.7 L 540.2,158.5 L 537.9,160.4 L 538.5,165.2 L 533.8,163.1 L 530.2,165.9 L 525.1,163.5 L 514.1,166.3 L 510.7,164.8 L 499.0,163.9 L 494.7,166.1 L 491.0,165.7 L 489.8,162.8 L 479.5,162.2 L 475.6,158.7 L 472.6,160.5 L 473.9,168.3 L 472.8,171.1 L 468.6,168.1 L 466.4,176.1 L 460.0,182.0 L 457.4,178.2 L 455.4,178.0 L 454.2,175.5 L 452.8,176.4 L 452.8,174.6 L 451.6,175.9 L 451.5,174.3 L 449.7,176.0 L 447.9,175.9 L 447.6,174.7 L 444.1,176.4 L 443.5,175.4 L 442.6,176.3 L 443.6,178.4 L 440.9,177.4 L 440.7,178.8 L 436.4,178.1 L 434.2,179.3 L 433.8,181.3 L 433.4,179.9 L 432.2,180.8 L 430.8,179.5 L 428.7,184.5 L 426.5,184.0 L 426.4,182.6 L 424.0,183.2 L 424.9,184.8 L 422.2,187.6 L 423.6,188.9 L 422.0,190.3 L 422.7,193.0 L 420.3,192.5 L 418.5,198.0 L 416.5,197.4 L 416.2,199.0 L 417.0,199.0 L 415.2,198.9 L 414.9,200.6 L 412.7,199.1 L 412.4,202.0 L 409.3,202.1 L 408.3,206.5 L 406.3,206.0 L 406.8,208.3 L 405.2,208.4 L 403.7,205.3 L 405.9,202.8 L 405.7,200.3 L 403.7,199.3 L 400.5,192.2 L 400.9,190.3 L 402.6,190.0 L 402.3,187.9 L 394.8,186.4 L 395.5,184.0 L 392.2,184.0 L 391.4,182.2 L 392.2,180.1 L 395.3,179.5 L 392.6,177.5 L 388.4,179.9 L 388.8,184.9 L 386.9,187.0 L 380.6,188.6 L 375.5,192.2 L 372.5,198.3 L 373.8,202.5 L 380.0,204.2 L 387.5,208.8 L 390.5,212.1 L 384.9,210.1 L 380.2,210.6 L 380.7,216.4 L 383.0,221.0 L 381.5,230.1 L 377.4,230.4 L 371.0,226.0 L 367.8,230.1 L 363.3,227.7 L 358.8,230.1 L 357.8,231.9 L 357.5,242.1 L 361.6,246.9 L 360.8,251.5 L 353.9,256.0 L 366.5,269.9 L 372.5,283.2 L 369.1,293.2 L 374.0,299.9 L 377.0,296.4 L 382.1,294.5 L 388.4,294.7 L 392.2,296.4 L 397.3,292.5 L 401.4,291.9 L 404.2,293.6 L 403.8,295.8 L 409.1,293.8 L 411.4,297.3 L 419.9,294.9 L 420.6,296.4 L 423.4,295.5 L 424.9,296.8 L 427.0,296.7 L 430.7,293.8 L 436.4,296.9 L 446.0,299.3 L 443.2,296.9 L 443.2,292.8 L 446.7,295.0 L 447.4,292.9 L 451.5,294.3 L 454.1,293.8 L 453.9,294.9 L 455.2,295.4 L 457.5,294.8 L 459.6,292.4 L 463.5,298.0 L 466.5,299.7 L 469.9,298.8 L 471.6,302.0 L 474.0,303.3 L 482.6,306.9 L 487.3,305.2 L 496.6,308.8 L 498.8,312.6 L 500.6,313.2 L 502.4,318.5 L 513.2,322.5 L 511.8,326.6 L 513.0,328.8 L 511.1,332.9 L 512.4,333.7 L 511.9,335.8 L 514.2,340.8 L 513.4,344.7 L 510.6,346.2 L 508.3,345.8 L 508.4,348.3 L 506.9,348.7 L 507.6,349.9 L 502.5,350.9 L 501.1,353.0 L 499.6,353.0 L 500.6,355.8 L 499.3,356.8 L 495.7,356.7 L 494.6,355.5 L 493.9,356.2 L 494.0,358.4 L 497.0,360.3 L 497.0,362.1 L 500.0,362.1 L 500.5,363.3 L 499.0,365.3 L 497.9,371.5 L 495.9,372.6 L 494.5,370.8 L 492.5,371.0 L 489.9,372.5 L 490.5,375.3 L 488.4,375.1 L 488.8,376.9 L 487.9,377.2 L 485.4,377.4 L 485.2,376.3 L 484.5,377.3 L 484.4,373.8 L 481.9,374.5 L 482.0,376.2 L 479.6,374.4 L 478.5,375.5 L 475.9,373.8 L 473.8,376.8 L 474.6,380.8 L 475.8,380.7 L 476.7,382.3 L 478.9,381.7 L 479.8,383.5 L 481.7,384.0 L 481.5,386.0 L 483.8,385.8 L 484.7,392.2 L 489.9,392.4 L 490.1,393.9 L 492.0,392.2 L 491.2,394.6 L 493.9,396.4 L 493.1,397.6 L 495.0,401.5 L 497.5,400.9 L 498.2,402.0 L 498.4,400.4 L 499.3,401.0 L 502.9,397.9 L 504.0,400.0 L 505.7,398.2 L 508.4,397.6 L 508.9,396.0 L 510.5,396.2 L 511.6,398.6 L 514.0,398.6 L 512.7,400.5 L 513.6,402.7 L 512.4,406.5 L 514.2,408.0 L 512.1,407.6 L 511.8,410.1 L 510.6,409.1 L 507.7,409.5 L 509.0,411.6 L 511.0,411.8 L 506.3,426.0 L 499.0,425.2 L 496.2,427.7 L 498.8,430.3 L 503.2,430.4 L 505.1,428.1 L 511.3,427.8 L 512.2,426.2 L 514.1,426.6 L 515.4,425.3 L 515.7,428.1 L 521.1,435.0 L 521.4,436.9 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Northern')"
                  onmouseenter="onRegionHover(event, 'Northern')"
                  onmouseleave="onRegionLeave()" />
            <!-- Central Region -->
            <path id="path-central"
                  data-name="Central"
                  data-capital="Cape Coast"
                  data-iso="GH-CP"
                  data-cases="245"
                  data-active-ops="2"
                  data-status="Coastal Monitoring"
                  data-badge="Littoral Watch"
                  data-commander="DSP A. Quayson"
                  data-officers="16"
                  data-narcotics="38.4 kg"
                  data-firearms="6"
                  data-devices="17"
                  data-vehicles="4"
                  data-cx="331.6"
                  data-cy="772.2"
                  d="M 245.7,729.9 L 258.2,736.1 L 260.8,741.4 L 269.5,745.8 L 278.4,758.1 L 289.7,764.6 L 289.3,775.5 L 292.5,776.5 L 294.2,778.6 L 291.7,782.5 L 291.2,789.0 L 289.3,791.8 L 289.4,800.0 L 292.3,801.3 L 302.2,799.2 L 305.7,801.0 L 313.7,801.4 L 321.2,808.9 L 319.6,816.0 L 321.8,822.9 L 320.0,823.5 L 326.5,828.3 L 331.1,839.2 L 342.9,845.7 L 344.1,850.3 L 339.3,853.0 L 337.0,856.6 L 329.7,857.9 L 325.5,862.2 L 323.9,867.1 L 322.9,880.0 L 335.1,878.4 L 356.7,873.1 L 356.8,872.1 L 360.6,871.0 L 371.3,870.3 L 392.2,857.6 L 430.2,854.9 L 441.5,845.3 L 440.9,844.4 L 441.9,843.3 L 455.9,838.9 L 458.2,836.1 L 472.8,832.6 L 477.6,827.1 L 477.4,825.6 L 483.4,822.5 L 487.9,818.4 L 481.3,807.3 L 482.0,802.5 L 473.0,791.8 L 473.6,788.1 L 472.7,785.2 L 465.2,782.0 L 452.3,782.8 L 391.1,778.9 L 387.6,777.3 L 374.6,761.1 L 370.9,759.4 L 368.5,759.3 L 368.4,762.5 L 366.6,760.9 L 363.5,763.3 L 362.0,762.7 L 362.1,757.7 L 357.2,759.8 L 358.3,757.7 L 356.7,758.7 L 356.5,756.9 L 352.7,756.5 L 352.2,759.1 L 347.0,758.5 L 346.8,761.0 L 345.7,759.9 L 344.5,761.2 L 342.5,760.5 L 341.9,762.3 L 340.6,760.6 L 341.2,766.5 L 339.1,767.5 L 338.2,765.8 L 335.7,765.7 L 335.7,764.5 L 332.9,764.7 L 333.5,762.7 L 336.1,762.6 L 335.1,762.0 L 335.9,760.9 L 333.6,759.3 L 333.0,757.4 L 331.6,758.1 L 331.7,756.0 L 329.5,756.1 L 330.6,757.3 L 327.7,758.1 L 325.9,756.9 L 324.5,758.8 L 323.5,756.6 L 322.9,758.1 L 316.9,756.8 L 312.5,759.2 L 309.7,757.0 L 309.6,755.4 L 307.4,757.3 L 307.1,753.8 L 305.1,754.1 L 304.1,752.0 L 301.3,752.8 L 299.7,751.7 L 298.9,753.0 L 298.2,750.2 L 295.9,749.2 L 295.2,747.6 L 294.4,748.6 L 293.4,746.8 L 294.6,744.0 L 293.7,743.1 L 292.0,743.9 L 291.6,742.6 L 289.6,742.8 L 287.9,745.6 L 285.9,746.2 L 283.2,743.3 L 282.1,745.4 L 279.3,746.1 L 277.7,742.8 L 274.3,739.8 L 274.0,740.9 L 272.8,739.7 L 271.7,740.4 L 270.9,735.0 L 269.1,733.5 L 270.2,731.6 L 268.8,730.6 L 265.5,732.3 L 265.5,730.1 L 264.1,729.4 L 264.6,724.3 L 261.9,720.5 L 264.5,716.9 L 264.2,712.7 L 266.6,710.4 L 265.3,706.8 L 259.5,707.0 L 250.4,714.6 L 243.3,718.2 L 243.8,727.1 L 245.7,729.9 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Central')"
                  onmouseenter="onRegionHover(event, 'Central')"
                  onmouseleave="onRegionLeave()" />
            <!-- Ashanti Region -->
            <path id="path-ashanti"
                  data-name="Ashanti"
                  data-capital="Kumasi"
                  data-iso="GH-AH"
                  data-cases="642"
                  data-active-ops="5"
                  data-status="Active Deployment"
                  data-badge="Middle Belt Command"
                  data-commander="Supt. E. Boakye"
                  data-officers="32"
                  data-narcotics="92.5 kg"
                  data-firearms="19"
                  data-devices="45"
                  data-vehicles="9"
                  data-cx="354.5"
                  data-cy="669.7"
                  d="M 256.9,708.8 L 259.5,707.0 L 264.9,706.7 L 266.1,707.6 L 266.6,710.4 L 264.2,712.7 L 264.5,716.9 L 261.9,720.5 L 264.6,724.3 L 264.1,729.4 L 265.5,730.1 L 265.5,732.3 L 268.8,730.6 L 270.2,731.6 L 269.1,733.5 L 270.9,735.0 L 271.7,740.4 L 272.8,739.7 L 274.0,740.9 L 274.3,739.8 L 277.7,742.8 L 279.3,746.1 L 282.1,745.4 L 283.2,743.3 L 285.9,746.2 L 287.9,745.6 L 289.6,742.8 L 291.6,742.6 L 292.0,743.9 L 293.7,743.1 L 294.6,744.0 L 293.4,746.8 L 294.4,748.6 L 295.2,747.6 L 295.9,749.2 L 298.2,750.2 L 298.9,753.0 L 299.7,751.7 L 301.3,752.8 L 304.1,752.0 L 305.1,754.1 L 307.1,753.8 L 307.4,757.3 L 309.6,755.4 L 309.7,757.0 L 312.5,759.2 L 316.9,756.8 L 322.9,758.1 L 323.5,756.6 L 324.5,758.8 L 325.9,756.9 L 327.7,758.1 L 330.6,757.3 L 329.5,756.1 L 331.7,756.0 L 331.6,758.1 L 333.0,757.4 L 333.6,759.3 L 335.9,760.9 L 335.1,762.0 L 336.1,762.6 L 333.5,762.7 L 332.9,764.7 L 335.7,764.5 L 335.7,765.7 L 338.2,765.8 L 339.1,767.5 L 341.2,766.5 L 340.6,760.6 L 341.9,762.3 L 342.5,760.5 L 344.5,761.2 L 345.7,759.9 L 346.8,761.0 L 347.0,758.5 L 352.2,759.1 L 352.7,756.5 L 356.5,756.9 L 356.7,758.7 L 358.3,757.7 L 357.2,759.8 L 362.1,757.7 L 362.1,762.9 L 364.3,763.0 L 366.6,760.9 L 368.4,762.5 L 368.5,759.3 L 370.9,759.4 L 371.0,756.9 L 373.1,756.8 L 374.2,755.5 L 373.4,755.0 L 373.8,750.9 L 370.4,748.9 L 371.1,746.2 L 373.2,745.0 L 372.5,742.4 L 375.7,740.4 L 374.9,738.3 L 377.0,736.5 L 376.2,735.1 L 378.6,734.4 L 377.8,732.2 L 375.9,732.2 L 377.9,730.9 L 376.5,729.4 L 379.7,727.4 L 378.8,724.6 L 379.7,723.0 L 378.3,722.3 L 377.9,720.0 L 383.5,715.3 L 386.1,702.7 L 388.9,700.0 L 394.2,688.5 L 401.9,683.3 L 403.3,680.0 L 410.3,673.6 L 412.4,668.6 L 416.8,666.6 L 418.1,663.8 L 415.0,658.1 L 412.0,656.0 L 409.4,651.9 L 409.2,647.5 L 413.7,643.3 L 427.3,641.8 L 432.6,638.1 L 456.0,603.7 L 477.6,602.5 L 483.1,598.0 L 494.2,594.9 L 496.3,590.3 L 501.6,586.0 L 513.5,585.7 L 511.2,582.0 L 508.3,583.2 L 507.8,580.5 L 505.8,579.7 L 505.1,574.4 L 501.4,572.0 L 499.6,572.9 L 498.4,571.8 L 496.4,572.4 L 495.8,571.1 L 492.5,570.2 L 491.4,568.2 L 486.7,568.3 L 482.5,566.2 L 467.0,562.4 L 459.1,563.2 L 451.5,552.0 L 452.0,542.2 L 448.4,538.5 L 447.4,539.1 L 446.7,536.8 L 445.1,538.6 L 439.8,539.7 L 438.7,541.3 L 436.0,541.3 L 433.6,542.8 L 430.5,542.1 L 430.4,541.0 L 428.7,542.6 L 427.7,540.8 L 425.6,541.2 L 425.4,540.0 L 422.8,540.5 L 422.4,539.7 L 419.2,540.2 L 418.8,541.9 L 414.4,542.0 L 410.6,539.3 L 408.0,539.8 L 406.4,537.8 L 404.3,538.6 L 400.8,537.9 L 394.8,542.3 L 393.1,545.5 L 373.3,548.3 L 366.8,547.3 L 362.9,544.9 L 360.9,530.1 L 356.7,526.0 L 350.5,526.4 L 342.5,538.4 L 331.9,545.0 L 332.2,557.7 L 325.5,557.0 L 322.7,559.5 L 321.6,562.6 L 317.1,565.0 L 313.3,565.2 L 309.8,563.4 L 301.0,564.9 L 297.4,561.1 L 291.5,557.8 L 289.9,546.9 L 278.6,549.4 L 273.0,547.0 L 263.5,548.7 L 252.7,547.4 L 246.5,561.0 L 247.4,564.2 L 255.2,564.3 L 259.0,568.6 L 269.1,568.4 L 272.9,571.3 L 276.1,577.1 L 286.9,579.8 L 287.8,580.5 L 286.4,583.5 L 285.6,591.3 L 282.1,593.1 L 277.6,593.1 L 266.2,604.0 L 260.7,607.2 L 251.8,606.4 L 245.6,609.2 L 241.5,607.9 L 239.2,603.0 L 235.9,603.1 L 231.5,606.7 L 228.7,614.2 L 221.9,625.3 L 216.5,628.9 L 214.7,632.9 L 229.0,632.5 L 232.8,634.1 L 233.7,636.5 L 228.6,638.8 L 226.7,651.1 L 221.1,652.5 L 219.5,656.3 L 215.4,657.1 L 209.9,654.5 L 210.3,662.8 L 215.9,673.8 L 215.1,675.6 L 213.1,676.4 L 213.2,678.8 L 212.0,678.6 L 209.3,681.0 L 209.5,683.0 L 207.0,684.9 L 206.6,687.1 L 207.5,687.2 L 206.1,688.6 L 209.7,692.0 L 208.3,699.7 L 211.6,700.2 L 217.6,695.9 L 217.7,690.1 L 220.4,679.8 L 222.7,678.9 L 230.6,682.8 L 232.6,681.4 L 236.2,685.0 L 234.5,694.2 L 237.5,701.3 L 240.7,698.9 L 246.2,699.2 L 251.5,707.9 L 256.9,708.8 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Ashanti')"
                  onmouseenter="onRegionHover(event, 'Ashanti')"
                  onmouseleave="onRegionLeave()" />
            <!-- Bono Region -->
            <path id="path-bono"
                  data-name="Bono"
                  data-capital="Sunyani"
                  data-iso="GH-BO"
                  data-cases="198"
                  data-active-ops="2"
                  data-status="Transit Intercept"
                  data-badge="Western Corridor"
                  data-commander="DSP M. Baffour"
                  data-officers="15"
                  data-narcotics="34.2 kg"
                  data-firearms="9"
                  data-devices="14"
                  data-vehicles="4"
                  data-cx="202.2"
                  data-cy="481.4"
                  d="M 148.8,633.3 L 152.2,627.3 L 152.8,618.0 L 157.3,614.8 L 158.6,610.7 L 163.3,607.7 L 164.6,602.3 L 167.6,598.6 L 172.7,597.8 L 173.4,593.2 L 179.6,593.0 L 185.8,586.8 L 187.9,586.8 L 189.0,582.4 L 192.8,580.1 L 200.7,579.9 L 200.1,593.4 L 210.6,594.5 L 213.8,597.3 L 210.4,598.9 L 217.9,597.5 L 220.4,594.1 L 224.1,595.4 L 224.9,598.4 L 229.2,595.4 L 227.9,590.9 L 223.0,586.1 L 221.1,580.3 L 230.9,578.8 L 233.1,573.0 L 239.5,568.2 L 245.2,567.5 L 247.4,564.2 L 246.5,561.0 L 249.1,553.5 L 252.7,547.4 L 253.9,547.5 L 250.8,539.3 L 255.7,531.3 L 258.1,523.3 L 263.0,520.4 L 259.4,516.4 L 258.1,510.1 L 260.2,506.4 L 265.3,506.4 L 272.2,502.3 L 281.8,501.6 L 280.7,493.0 L 272.2,481.5 L 268.6,480.2 L 265.8,481.8 L 262.6,480.9 L 265.3,474.2 L 259.1,465.5 L 255.7,458.0 L 245.3,450.3 L 244.6,445.2 L 239.6,442.5 L 238.4,441.1 L 238.7,438.6 L 236.6,438.1 L 234.7,435.6 L 228.6,433.8 L 225.2,420.3 L 217.3,409.1 L 216.8,404.9 L 218.4,403.1 L 216.8,399.3 L 210.8,395.6 L 210.4,388.9 L 208.4,386.5 L 204.0,384.9 L 199.4,376.0 L 194.2,372.9 L 193.2,369.9 L 187.5,366.6 L 199.7,432.8 L 200.0,445.9 L 192.0,454.2 L 184.3,453.3 L 181.7,457.2 L 181.9,460.4 L 185.7,466.3 L 184.8,470.0 L 174.5,471.3 L 166.6,481.2 L 165.3,484.7 L 162.4,481.1 L 160.6,480.7 L 159.3,492.9 L 153.8,498.7 L 154.4,500.7 L 151.5,503.7 L 151.3,506.5 L 141.3,527.2 L 140.2,543.2 L 134.8,565.5 L 134.1,574.0 L 137.2,578.4 L 127.4,591.5 L 126.3,601.1 L 118.7,603.7 L 115.7,608.7 L 118.3,617.0 L 120.6,620.0 L 124.5,621.0 L 133.9,618.6 L 137.1,619.2 L 143.8,624.1 L 148.8,633.3 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Bono')"
                  onmouseenter="onRegionHover(event, 'Bono')"
                  onmouseleave="onRegionLeave()" />
            <!-- Volta Region -->
            <path id="path-volta"
                  data-name="Volta"
                  data-capital="Ho"
                  data-iso="GH-TV"
                  data-cases="268"
                  data-active-ops="3"
                  data-status="Eastern Frontier"
                  data-badge="Aflao Border Hub"
                  data-commander="DSP F. Dzreke"
                  data-officers="20"
                  data-narcotics="112.4 kg"
                  data-firearms="12"
                  data-devices="26"
                  data-vehicles="6"
                  data-cx="616.9"
                  data-cy="649.7"
                  d="M 565.9,586.3 L 562.1,605.1 L 565.6,629.9 L 568.3,638.1 L 569.8,648.0 L 568.5,652.1 L 556.8,661.3 L 551.2,671.3 L 551.2,675.0 L 554.3,679.1 L 557.9,689.7 L 554.2,699.3 L 548.9,706.2 L 549.3,709.3 L 552.8,711.1 L 554.3,715.8 L 555.4,716.2 L 551.4,724.5 L 551.6,726.1 L 559.8,732.6 L 564.9,734.8 L 576.9,733.3 L 579.6,735.2 L 581.2,739.2 L 580.2,747.3 L 585.2,748.9 L 584.3,756.1 L 585.6,756.7 L 597.0,751.7 L 611.9,748.5 L 626.9,766.3 L 625.7,771.3 L 627.0,776.2 L 629.1,777.5 L 636.0,778.8 L 651.0,778.8 L 666.1,777.1 L 669.5,775.7 L 675.6,768.4 L 677.1,763.4 L 676.8,758.0 L 679.8,752.8 L 688.9,741.2 L 697.8,735.3 L 705.0,732.3 L 704.9,724.5 L 689.0,724.4 L 689.0,722.0 L 686.3,717.1 L 681.0,712.6 L 678.6,707.0 L 678.1,701.7 L 663.0,702.0 L 658.8,695.6 L 655.2,693.3 L 648.8,691.7 L 647.9,688.4 L 643.4,686.9 L 642.7,681.5 L 640.2,680.5 L 638.3,675.3 L 643.1,670.6 L 642.3,667.7 L 640.5,666.4 L 635.5,666.8 L 633.0,663.7 L 632.0,665.0 L 630.8,664.5 L 627.8,659.9 L 629.8,655.4 L 628.6,648.8 L 629.6,646.4 L 625.5,644.4 L 624.2,645.5 L 620.4,643.5 L 618.3,639.8 L 620.2,637.2 L 615.9,633.6 L 614.7,634.8 L 613.7,634.3 L 613.6,630.4 L 615.2,629.4 L 614.1,627.8 L 617.3,626.2 L 618.3,621.7 L 612.4,618.6 L 612.2,614.8 L 616.1,612.0 L 622.3,610.5 L 623.5,607.8 L 622.6,602.3 L 624.9,597.3 L 621.8,595.3 L 623.6,589.8 L 625.2,589.3 L 625.2,586.0 L 626.3,585.9 L 627.6,582.6 L 628.9,574.9 L 623.8,574.4 L 619.4,571.5 L 619.1,574.3 L 617.9,574.2 L 616.5,576.2 L 615.1,580.1 L 611.9,580.7 L 611.6,578.1 L 613.9,577.0 L 615.0,573.8 L 614.1,569.2 L 612.4,570.2 L 610.0,569.7 L 608.1,571.2 L 602.3,570.2 L 602.2,573.0 L 599.3,572.5 L 594.5,579.9 L 591.7,589.8 L 589.8,592.9 L 587.2,593.0 L 585.8,598.7 L 571.6,597.3 L 574.1,593.5 L 573.1,588.0 L 565.9,586.3 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Volta')"
                  onmouseenter="onRegionHover(event, 'Volta')"
                  onmouseleave="onRegionLeave()" />
            <!-- Upper West Region -->
            <path id="path-upper-west"
                  data-name="Upper West"
                  data-capital="Wa"
                  data-iso="GH-UW"
                  data-cases="142"
                  data-active-ops="1"
                  data-status="Frontier Intel"
                  data-badge="Hamile Sector"
                  data-commander="DSP C. Der"
                  data-officers="12"
                  data-narcotics="18.7 kg"
                  data-firearms="11"
                  data-devices="8"
                  data-vehicles="2"
                  data-cx="234.4"
                  data-cy="138.6"
                  d="M 333.5,190.2 L 317.0,176.5 L 323.1,161.4 L 332.8,147.2 L 341.6,141.0 L 344.3,136.4 L 340.4,134.1 L 340.9,131.1 L 342.9,130.1 L 341.7,127.8 L 337.8,127.0 L 337.0,125.3 L 333.4,123.9 L 333.5,121.6 L 332.0,120.2 L 328.5,120.5 L 326.7,118.7 L 325.8,109.5 L 327.9,98.7 L 331.2,98.4 L 332.6,94.4 L 335.9,93.7 L 336.6,87.2 L 338.7,87.1 L 339.6,85.2 L 341.5,84.5 L 343.0,68.8 L 345.6,64.2 L 345.9,61.0 L 320.0,61.0 L 319.9,65.9 L 309.7,66.0 L 309.6,67.6 L 301.6,67.7 L 301.6,66.1 L 230.1,64.7 L 199.0,65.3 L 153.4,63.0 L 152.5,68.4 L 155.4,71.9 L 155.7,74.2 L 152.9,78.5 L 149.3,79.3 L 149.3,83.4 L 147.2,87.0 L 145.0,96.1 L 139.2,101.9 L 139.0,103.8 L 142.9,105.0 L 143.6,106.8 L 138.7,113.6 L 138.7,116.3 L 144.3,125.9 L 144.4,129.2 L 147.8,133.6 L 149.0,138.5 L 153.4,140.9 L 161.7,142.9 L 160.8,144.8 L 153.7,147.6 L 151.1,157.1 L 153.5,160.2 L 163.1,164.7 L 164.0,167.0 L 163.4,169.7 L 158.5,173.5 L 158.3,175.0 L 159.8,181.4 L 159.0,184.5 L 160.5,197.0 L 165.8,206.0 L 163.1,212.3 L 163.3,216.5 L 168.1,223.7 L 167.3,226.0 L 163.7,228.0 L 159.4,235.3 L 160.4,238.0 L 160.0,242.6 L 163.8,244.3 L 164.9,248.7 L 186.0,245.6 L 189.0,239.9 L 193.3,239.7 L 197.4,232.5 L 205.3,230.6 L 214.0,230.7 L 229.7,226.8 L 238.8,227.5 L 246.1,235.4 L 256.8,243.0 L 268.8,236.9 L 272.6,238.2 L 277.1,244.6 L 279.3,250.4 L 283.5,250.4 L 290.7,246.9 L 304.4,243.2 L 309.5,239.3 L 311.2,236.2 L 312.3,229.7 L 309.2,218.4 L 311.8,216.2 L 312.3,210.7 L 310.6,204.8 L 313.0,199.5 L 320.3,199.2 L 320.3,196.0 L 318.6,192.6 L 328.5,193.5 L 333.5,190.2 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Upper West')"
                  onmouseenter="onRegionHover(event, 'Upper West')"
                  onmouseleave="onRegionLeave()" />
            <!-- Upper East Region -->
            <path id="path-upper-east"
                  data-name="Upper East"
                  data-capital="Bolgatanga"
                  data-iso="GH-UE"
                  data-cases="188"
                  data-active-ops="2"
                  data-status="Border Gate Vigilance"
                  data-badge="Paga Sector Hub"
                  data-commander="DSP R. Ayambire"
                  data-officers="14"
                  data-narcotics="29.3 kg"
                  data-firearms="15"
                  data-devices="12"
                  data-vehicles="3"
                  data-cx="443.0"
                  data-cy="98.0"
                  d="M 528.7,103.1 L 531.0,102.3 L 531.0,95.4 L 538.0,88.4 L 536.9,83.8 L 539.9,75.6 L 540.1,68.9 L 545.5,66.8 L 544.2,55.3 L 538.9,48.4 L 535.6,49.4 L 533.4,52.2 L 527.9,52.1 L 523.6,49.4 L 521.4,49.4 L 522.3,44.8 L 503.3,40.0 L 501.4,43.6 L 503.8,45.1 L 503.7,46.9 L 495.4,49.0 L 494.5,51.9 L 492.4,52.5 L 491.8,54.4 L 489.6,49.4 L 490.0,47.1 L 485.9,46.5 L 482.5,48.3 L 480.8,55.0 L 481.0,59.8 L 477.8,60.1 L 476.0,59.0 L 475.3,60.7 L 472.3,61.5 L 471.1,65.5 L 468.8,63.9 L 466.4,67.4 L 465.3,65.3 L 463.2,65.0 L 460.8,69.3 L 459.8,74.4 L 456.4,76.1 L 455.3,72.3 L 452.3,70.8 L 451.7,68.9 L 449.2,69.6 L 451.5,65.6 L 449.0,65.2 L 447.8,66.3 L 447.4,64.3 L 430.5,64.2 L 430.5,63.1 L 427.8,63.0 L 427.6,64.2 L 424.0,64.2 L 422.0,68.5 L 418.7,69.1 L 419.4,66.6 L 415.6,66.6 L 415.6,63.6 L 388.5,63.4 L 388.0,65.9 L 351.8,64.4 L 350.1,62.3 L 345.9,61.0 L 345.6,64.2 L 343.0,68.8 L 341.5,84.5 L 339.6,85.2 L 338.7,87.1 L 336.6,87.2 L 335.9,93.7 L 332.6,94.4 L 331.2,98.4 L 327.9,98.7 L 325.8,109.5 L 326.7,118.7 L 328.5,120.5 L 332.0,120.2 L 333.5,121.6 L 333.4,123.9 L 337.0,125.3 L 337.8,127.0 L 341.7,127.8 L 342.9,130.1 L 340.9,131.1 L 340.4,134.1 L 343.2,136.5 L 345.8,137.3 L 349.1,134.3 L 352.9,137.1 L 352.2,138.3 L 354.6,141.2 L 353.1,142.4 L 354.4,142.9 L 353.9,144.4 L 351.5,145.2 L 352.0,147.3 L 356.5,149.8 L 356.5,153.1 L 359.5,154.0 L 359.5,155.3 L 365.1,156.7 L 367.4,156.1 L 367.1,157.9 L 369.2,159.2 L 369.2,162.1 L 370.3,163.1 L 371.4,161.5 L 373.1,161.5 L 372.4,160.7 L 374.6,159.1 L 375.6,155.8 L 372.5,150.4 L 373.3,147.2 L 379.2,145.1 L 386.0,138.0 L 396.6,132.6 L 398.5,129.5 L 402.8,127.2 L 416.0,128.5 L 416.6,129.7 L 413.0,134.2 L 414.8,134.7 L 425.1,123.8 L 426.4,118.4 L 429.1,118.2 L 432.0,115.9 L 432.7,111.5 L 437.7,113.2 L 441.5,111.5 L 443.6,113.1 L 444.3,115.8 L 442.8,116.4 L 442.7,117.9 L 445.9,120.0 L 447.1,123.1 L 443.0,127.3 L 443.1,128.5 L 444.4,129.2 L 446.6,126.5 L 447.0,128.1 L 448.7,128.9 L 453.5,124.7 L 461.4,125.7 L 464.9,122.5 L 470.3,124.1 L 471.5,123.1 L 472.7,115.4 L 476.9,116.2 L 480.3,118.7 L 487.0,117.5 L 490.8,115.3 L 498.5,115.5 L 502.3,113.2 L 505.3,114.3 L 509.5,111.0 L 524.2,106.9 L 528.7,103.1 Z"
                  class="region-polygon  transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Upper East')"
                  onmouseenter="onRegionHover(event, 'Upper East')"
                  onmouseleave="onRegionLeave()" />
            <!-- Greater Accra Region -->
            <path id="path-greater-accra"
                  data-name="Greater Accra"
                  data-capital="Accra"
                  data-iso="GH-AA"
                  data-cases="1248"
                  data-active-ops="8"
                  data-status="High Intel Alert"
                  data-badge="HQ Central Hub"
                  data-commander="Chief Supt. K. Mensah"
                  data-officers="46"
                  data-narcotics="184.2 kg"
                  data-firearms="28"
                  data-devices="94"
                  data-vehicles="16"
                  data-cx="540.8"
                  data-cy="784.3"
                  d="M 472.7,785.2 L 473.6,788.1 L 473.0,791.8 L 482.0,802.5 L 481.3,807.3 L 487.9,818.4 L 495.6,815.2 L 512.3,811.5 L 513.0,810.3 L 527.0,805.7 L 532.7,801.4 L 542.4,799.3 L 543.6,798.6 L 541.6,798.9 L 542.9,797.1 L 543.2,798.4 L 543.8,797.6 L 543.1,796.3 L 543.4,797.1 L 550.2,789.4 L 557.9,787.7 L 565.4,782.9 L 571.0,782.2 L 578.9,778.5 L 601.3,776.4 L 632.8,778.5 L 627.0,776.2 L 625.7,771.3 L 626.9,766.3 L 611.9,748.5 L 597.0,751.7 L 585.6,756.7 L 584.3,756.1 L 585.2,748.9 L 578.8,746.5 L 575.4,743.1 L 572.3,742.8 L 563.4,744.2 L 552.9,749.8 L 548.1,750.3 L 547.9,755.4 L 539.9,761.7 L 533.7,764.9 L 528.5,769.9 L 522.1,768.6 L 519.6,772.7 L 511.0,777.2 L 503.8,779.2 L 498.2,777.1 L 495.2,781.2 L 488.5,782.7 L 482.0,776.1 L 477.8,776.3 L 476.3,777.4 L 472.7,785.2 Z"
                  class="region-polygon region-hotspot transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Greater Accra')"
                  onmouseenter="onRegionHover(event, 'Greater Accra')"
                  onmouseleave="onRegionLeave()" />
                  </g>

                  <!-- 3. Direct Region Labels -->
                  <g id="regionLabelsGroup">
                    
            <g id="lbl-western-north" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Western North')">
              <text x="154.5" y="726.7" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">WESTERN NORTH</text>
              <text id="badge-western-north" x="154.5" y="739.7" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">134 Dockets</text>
            </g>
            <g id="lbl-ahafo" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Ahafo')">
              <text x="210.7" y="635.4" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">AHAFO</text>
              <text id="badge-ahafo" x="210.7" y="648.4" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">96 Dockets</text>
            </g>
            <g id="lbl-bono-east" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Bono East')">
              <text x="404.5" y="484.9" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">BONO EAST</text>
              <text id="badge-bono-east" x="404.5" y="497.9" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">175 Dockets</text>
            </g>
            <g id="lbl-savannah" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Savannah')">
              <text x="396.5" y="370.4" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">SAVANNAH</text>
              <text id="badge-savannah" x="396.5" y="383.4" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">116 Dockets</text>
            </g>
            <g id="lbl-north-east" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('North East')">
              <text x="474.7" y="150.8" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">NORTH EAST</text>
              <text id="badge-north-east" x="474.7" y="163.8" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">72 Dockets</text>
            </g>
            <g id="lbl-oti" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Oti')">
              <text x="592.7" y="463.7" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">OTI</text>
              <text id="badge-oti" x="592.7" y="476.7" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">84 Dockets</text>
            </g>
            <g id="lbl-western" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Western')">
              <text x="226.6" y="865.2" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">WESTERN</text>
              <text id="badge-western" x="226.6" y="878.2" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">512 Dockets</text>
            </g>
            <g id="lbl-eastern" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Eastern')">
              <text x="453.4" y="709.0" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">EASTERN</text>
              <text id="badge-eastern" x="453.4" y="722.0" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">310 Dockets</text>
            </g>
            <g id="lbl-northern" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Northern')">
              <text x="517.3" y="307.8" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">NORTHERN</text>
              <text id="badge-northern" x="517.3" y="320.8" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">385 Dockets</text>
            </g>
            <g id="lbl-central" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Central')">
              <text x="331.6" y="768.2" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">CENTRAL</text>
              <text id="badge-central" x="331.6" y="781.2" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">245 Dockets</text>
            </g>
            <g id="lbl-ashanti" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Ashanti')">
              <text x="354.5" y="665.7" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">ASHANTI</text>
              <text id="badge-ashanti" x="354.5" y="678.7" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">642 Dockets</text>
            </g>
            <g id="lbl-bono" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Bono')">
              <text x="202.2" y="477.4" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">BONO</text>
              <text id="badge-bono" x="202.2" y="490.4" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">198 Dockets</text>
            </g>
            <g id="lbl-volta" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Volta')">
              <text x="616.9" y="645.7" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">VOLTA</text>
              <text id="badge-volta" x="616.9" y="658.7" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">268 Dockets</text>
            </g>
            <g id="lbl-upper-west" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Upper West')">
              <text x="234.4" y="134.6" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">UPPER WEST</text>
              <text id="badge-upper-west" x="234.4" y="147.6" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">142 Dockets</text>
            </g>
            <g id="lbl-upper-east" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Upper East')">
              <text x="443.0" y="94.0" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">UPPER EAST</text>
              <text id="badge-upper-east" x="443.0" y="107.0" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">188 Dockets</text>
            </g>
            <g id="lbl-greater-accra" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('Greater Accra')">
              <text x="540.8" y="780.3" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">GREATER ACCRA</text>
              <text id="badge-greater-accra" x="540.8" y="793.3" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">1248 Dockets</text>
            </g>
                  </g>

                  <!-- 4. Active MMDA District Map & Linked Case Beacons for Selected Region -->
                  <g id="districtLayersGroup">
                    <!-- Populated via renderDistrictMap() -->
                  </g>

                </svg>

              </div>

              <!-- Bottom MMDA Districts & Linked Cases Drawer (Organized Clean Grid) -->
              <div id="districtCardsDrawer" class="hidden absolute bottom-3 left-3 right-3 z-30 bg-slate-950/95 border border-slate-700/80 rounded-xl p-3 backdrop-blur-md shadow-2xl transition-all">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[11px] font-mono-code font-bold">
                  <span class="flex items-center space-x-1.5 text-slate-200">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-slate-300"></i>
                    <span>Official MMDA Districts & Active Linked Cases</span>
                  </span>
                  <span id="districtDrawerBadge" class="text-[10px] text-slate-300 font-bold bg-red-500/20 px-2 py-0.5 rounded border border-red-500/30">3 Cases Linked</span>
                </div>
                <div id="districtDrawerGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-36 overflow-y-auto pr-1 text-[11px] font-mono-code">
                  <!-- Populated dynamically via JS -->
                </div>
              </div>

              <!-- Floating Region Hover Tooltip -->
              <div id="regionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-900/95 text-white backdrop-blur-md px-3 py-1.5 rounded-xl text-xs shadow-2xl border border-slate-700/80 transition-opacity duration-150">
                <div id="tooltipRegionName" class="font-bold text-indigo-300">Region Name</div>
                <div id="tooltipSub" class="text-[10px] text-slate-300">Click to filter dockets</div>
              </div>

              <!-- Subtle Pan/Zoom Hint -->
              <div id="mapPanZoomHint" class="absolute bottom-3 left-4 z-20 pointer-events-none flex items-center space-x-2 bg-slate-900/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-semibold text-slate-400 border border-slate-800 shadow-xs">
                <i data-lucide="move" class="w-3 h-3 text-slate-300"></i>
                <span>Click region to open District Map • Drag to pan</span>
              </div>

            </div>

          </div>

        </div>

        <!-- PANEL 3: FROSTED FORENSIC DOSSIER DETAILS CARD (100% FORENSICS) -->
        <div id="panelDossierCard" class="col-span-12 xl:col-span-4 flex flex-col min-w-0">
          
          <div id="dossierCardWrapper" class="bg-gradient-to-br from-[#2f2e59]/90 to-[#222143]/90 rounded-2xl p-4 sm:p-5 border border-white/10 shadow-2xl relative overflow-hidden backdrop-blur-xl transition-all h-auto min-h-[500px] xl:h-[580px] flex flex-col justify-between">
            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

            <!-- Scrollable Content Container (Adapts cleanly across mobile, tablet, laptop, and ultrawide) -->
            <div class="flex-1 min-h-0 overflow-y-auto pr-1 sm:pr-1.5 space-y-3.5 custom-scrollbar max-h-[460px] xl:max-h-none">
              
              <!-- Top Row: Docket # & Status + Submitting Forensic Unit -->
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 border-b border-white/10 pb-3">
                <div class="min-w-0 flex-1">
                  <span class="text-[10px] font-bold text-indigo-300 font-mono-code uppercase tracking-wider block">Docket Details</span>
                  <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-0.5">
                    <h3 id="cardInvoiceNumber" class="text-lg sm:text-xl font-extrabold text-white tracking-tight font-mono-code truncate">
                      # NCC-2026-2572
                    </h3>
                    <span id="cardInvoiceStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 whitespace-nowrap">
                      Under Investigation
                    </span>
                  </div>
                </div>

                <!-- Submitting Forensic Unit Pill -->
                <div class="sm:text-right shrink-0">
                  <span class="text-[9px] font-mono-code text-slate-400 uppercase tracking-wider block">Submitting Unit</span>
                  <div class="flex items-center space-x-1.5 sm:justify-end mt-0.5">
                    <span id="cardCompanyName" class="text-xs font-bold text-white truncate max-w-[160px]">Crime Scene Support Unit</span>
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
                  </div>
                </div>
              </div>

              <!-- Suspect Identity Profile Container (Dynamic Single or Multi-Suspect) -->
              <div id="cardSuspectsProfileContainer" class="min-w-0">
                <div class="flex items-center space-x-3 bg-white/5 p-2.5 sm:p-3 rounded-2xl border border-white/5">
                  <div id="cardCustomerAvatar" class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-slate-700 overflow-hidden shrink-0 flex items-center justify-center text-white font-bold text-xs sm:text-sm ring-1 ring-white/20">
                    KM
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-1">
                      <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider truncate">Primary Target</span>
                      <span class="text-[10px] font-mono-code text-slate-400 truncate shrink-0" id="cardSuspectId">GHA-7829104-1</span>
                    </div>
                    <div id="cardCustomerName" class="text-xs sm:text-sm font-extrabold text-white truncate mt-0.5">
                      Kofi Mensah
                    </div>
                    <div id="cardCustomerRole" class="text-[10px] text-slate-300 truncate">
                      Ghanaian • 34 yrs • Narcotics Concealment
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operational Case Metadata -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-mono-code bg-black/20 p-2.5 rounded-xl border border-white/5 min-w-0">
                <div class="min-w-0">
                  <span class="text-[9px] text-slate-400 block">Lead Officer:</span>
                  <span id="cardOfficerName" class="font-bold text-slate-200 truncate block">Insp. K. Mensah</span>
                </div>
                <div class="min-w-0">
                  <span class="text-[9px] text-slate-400 block">Seizure:</span>
                  <span id="cardLocationName" class="font-bold text-slate-200 truncate block">Kotoka Cargo Wing</span>
                </div>
              </div>

              <!-- Seized Exhibits Grid -->
              <div class="min-w-0">
                <div class="flex items-center justify-between gap-1 mb-2">
                  <span class="text-[10px] font-mono-code text-slate-300 uppercase tracking-wider font-bold truncate">Recovered Exhibits & Evidence</span>
                  <span id="cardSubTotal" class="text-[10px] font-mono-code text-indigo-300 font-bold shrink-0 whitespace-nowrap">3 Items Logged</span>
                </div>

                <div id="exhibitCardsGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-2 min-w-0">
                  <!-- Dynamically populated via renderDossierCard() -->
                </div>
              </div>

            </div>

            <!-- Bottom Row: Judicial Chain of Custody & Actions -->
            <div class="pt-3 border-t border-white/10 mt-auto shrink-0">
              <div class="grid grid-cols-3 gap-1.5 sm:gap-2 text-xs font-mono-code mb-3">
                <div class="min-w-0">
                  <span class="text-[9px] text-slate-400 block truncate">Total Exhibits</span>
                  <span id="cardTotal" class="font-extrabold text-white text-xs sm:text-sm truncate block">3 Items Logged</span>
                </div>
                <div class="text-center min-w-0">
                  <span class="text-[9px] text-slate-400 block truncate">Chain of Custody</span>
                  <span id="cardCustodyStatus" class="font-bold text-slate-300 text-[10px] sm:text-xs truncate block">EMU Vault Secured</span>
                </div>
                <div class="text-right min-w-0">
                  <span class="text-[9px] text-slate-400 block truncate">Judicial Mandate</span>
                  <span id="cardBalanceDue" class="font-bold text-indigo-300 text-[10px] sm:text-xs truncate block">Judicial Review Active</span>
                </div>
              </div>

              <!-- Actions Buttons -->
              <div class="flex items-center gap-2">
                <a id="cardActionLink" href="{{ url('/cases') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0 transition-colors" title="Export Docket PDF">
                  <i data-lucide="file-down" class="w-4 h-4"></i>
                </a>
                <a id="cardActionVault" href="{{ url('/history') }}?unit=Crime+Scene+Support" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0 transition-colors" title="Case Audit & History">
                  <i data-lucide="history" class="w-4 h-4"></i>
                </a>
                <a id="cardActionPrimary" href="{{ url('/cases') }}" class="flex-1 min-w-0 py-2 sm:py-2.5 px-3 rounded-xl bg-white text-slate-900 hover:bg-slate-100 font-extrabold text-xs text-center shadow-lg transition-all flex items-center justify-center space-x-1.5">
                  <span class="truncate">View Full Docket</span>
                  <i data-lucide="arrow-right" class="w-3.5 h-3.5 shrink-0"></i>
                </a>
              </div>
            </div>

          </div>

        </div>

      </div>

    </section>

  </div>

  <!-- ======================================================== -->
  <!-- 6. FULLSCREEN TACTICAL OPERATIONS THEATRE MODAL          -->
  <!-- ======================================================== -->
  <div id="tacticalOpsTheatre" class="hidden fixed inset-0 z-50 bg-[#0a0b12] text-white flex flex-col overflow-hidden select-none">
    
    <!-- Top HUD Telemetry Bar -->
    <header class="relative z-40 bg-slate-950/90 border-b border-emerald-500/30 px-6 py-3.5 backdrop-blur-xl flex items-center justify-between shadow-2xl">
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2.5">
          <div class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-200 text-sm font-mono-code">
            <i data-lucide="shield" class="w-4 h-4"></i>
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-mono-code font-black text-sm tracking-wider text-white">NACOC TACTICAL OPERATIONS THEATRE • CRIME SCENE SUPPORT UNIT</span>
            </div>
            <span class="text-[11px] font-mono-code text-slate-400">16 Regions Radar • MMDA Assemblies • Target Interception Network</span>
          </div>
        </div>
      </div>
<div class="flex items-center space-x-3">
        <div class="hidden sm:flex items-center space-x-1 border border-emerald-500/30 rounded-xl p-1 bg-slate-900/80">
          <button type="button" onclick="zoomMap(1.2, true)" class="w-8 h-8 rounded-lg hover:bg-emerald-500/20 text-emerald-300 font-bold text-sm" title="Tactical Zoom In">+</button>
          <button type="button" onclick="zoomMap(0.8, true)" class="w-8 h-8 rounded-lg hover:bg-emerald-500/20 text-emerald-300 font-bold text-sm" title="Tactical Zoom Out">-</button>
          <button type="button" onclick="resetMapView(true)" class="w-8 h-8 rounded-lg hover:bg-emerald-500/20 text-emerald-300 flex items-center justify-center" title="Reset View">
            <i data-lucide="crosshair" class="w-4 h-4"></i>
          </button>
          <button type="button" onclick="toggle3DView(true)" id="btnTacToggle3D" class="px-2.5 h-8 rounded-lg bg-emerald-600/30 text-emerald-300 text-[11px] font-mono-code font-bold hover:bg-emerald-600 hover:text-white" title="Toggle Tactical 3D Relief">
            3D RELIEF
          </button>
        </div>

        <button type="button" 
                onclick="closeTacticalMode()" 
                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-red-950 text-slate-300 hover:text-slate-300 border border-slate-700 hover:border-red-500/60 font-mono-code text-xs font-bold transition-all flex items-center space-x-1.5"
                title="Exit Tactical Mode (Press ESC)">
          <i data-lucide="x" class="w-4 h-4"></i>
          <span>EXIT THEATRE</span>
          <span class="text-[9px] bg-white/10 px-1 py-0.5 rounded ml-1">ESC</span>
        </button>
      </div>
    </header>

    <!-- Main Fullscreen Stage Workspace -->
    <main class="relative flex-1 w-full h-full overflow-hidden flex items-center justify-center tactical-grid-bg">
      
      <!-- Concentric Radar Target Rings -->
      <div class="radar-ring radar-ring-tactical w-[850px] h-[850px]"></div>
      <div class="radar-ring radar-ring-tactical w-[620px] h-[620px]"></div>
      <div class="radar-ring radar-ring-tactical w-[380px] h-[380px]"></div>

      <!-- Left Floating Tactical HUD: Regional & MMDA District Telemetry -->
      <div class="absolute top-6 left-6 z-40 w-80 bg-slate-950/85 border border-emerald-500/40 rounded-2xl p-4.5 backdrop-blur-xl shadow-2xl text-white pointer-events-auto hidden md:block">
        <div class="flex items-center justify-between pb-2 border-b border-emerald-500/20 mb-3">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-mono-code font-bold uppercase tracking-wider text-emerald-300">REGIONAL TELEMETRY</span>
          </div>
          <span id="tacHudIso" class="text-[10px] font-mono-code text-slate-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">GH-AA</span>
        </div>

        <div class="space-y-3">
          <div>
            <div id="tacHudRegionName" class="text-lg font-black font-mono-code text-white tracking-wide">GREATER ACCRA</div>
            <div id="tacHudCapital" class="text-xs text-slate-400 mt-0.5">Capital: Accra (National Directorate HQ)</div>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs font-mono-code">
            <div class="bg-slate-900/80 p-2 rounded-xl border border-emerald-500/20">
              <span class="text-[10px] text-slate-400 block">Active Dockets</span>
              <span id="tacHudDockets" class="text-base font-black text-slate-300">3 Dockets</span>
            </div>
            <div class="bg-slate-900/80 p-2 rounded-xl border border-emerald-500/20">
              <span class="text-[10px] text-slate-400 block">MMDA Districts</span>
              <span id="tacHudStations" class="text-base font-black text-slate-300">10 Districts</span>
            </div>
          </div>

          <div>
            <span class="text-[10px] font-mono-code text-slate-400 uppercase tracking-wider block mb-1">Regional Commander</span>
            <div id="tacHudCommander" class="text-xs font-bold text-white bg-slate-900/60 p-2 rounded-xl border border-slate-800">
              Chief Supt. K. Boateng • Forensic Sector Alpha
            </div>
          </div>

          <div>
            <span class="text-[10px] font-mono-code text-slate-400 uppercase tracking-wider block mb-1.5">MMDA Districts & Assemblies</span>
            <div id="tacHudDistrictsList" class="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-[11px] font-mono-code">
              <!-- Dynamically populated via JS -->
            </div>
          </div>
        </div>
      </div>

      <!-- Right Floating HUD: Live Threat Interception Stream -->
      <div class="absolute top-6 right-6 z-40 w-84 bg-slate-950/85 border border-emerald-500/40 rounded-2xl p-4.5 backdrop-blur-xl shadow-2xl text-white pointer-events-auto hidden md:block">
        <div class="flex items-center justify-between pb-2 border-b border-emerald-500/20 mb-3">
          <div class="flex items-center space-x-2">
            <i data-lucide="shield-alert" class="w-4 h-4 text-slate-300"></i>
            <span class="text-xs font-mono-code font-bold uppercase tracking-wider text-emerald-300">LIVE DOCKET STREAM</span>
          </div>
          <span class="text-[10px] font-mono-code text-slate-300">REALTIME</span>
        </div>

        <div id="tacCasesListContainer" class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
          <!-- Dynamically populated from liveCases -->
        </div>
      </div>

      <!-- Floating Return to Overview Pill inside Tactical Theatre (Top Center) -->
      <div id="tacDistrictNavHeader" class="hidden absolute top-4 left-1/2 -translate-x-1/2 z-50 flex items-center space-x-3">
        <button type="button" 
                onclick="resetMapView(true)" 
                class="px-4 py-2 rounded-xl bg-slate-950/95 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl transition-all group">
          <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
          <span>RETURN TO NATIONAL RADAR</span>
        </button>
        <span id="tacDistrictDrillTitleBadge" class="px-4 py-2 rounded-xl bg-red-950/90 text-white border border-red-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl">
          <span id="tacDistrictDrillTitle">TARGET SECTOR: GREATER ACCRA MMDA RADAR</span>
        </span>
      </div>
<!-- 3D Perspective Map Stage for Fullscreen Tactical -->
      <div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center">
        
        <div id="tacGhana3dPlane" class="map-3d-plane relative w-full h-full max-w-[960px] flex items-center justify-center">
          
          <svg id="tacGhanaMapSvg" 
               viewBox="0 0 800 960" 
               class="w-full h-full drop-shadow-[0_40px_60px_rgba(0,0,0,0.85)] transition-all duration-500"
               style="overflow: visible;">
            
            <!-- Base Shadow -->
            <g id="tacExtrusionShadowPass" transform="translate(0, 20)" pointer-events="none">
              <path d="M 189.2,781.8 L 190.7,776.0 L 194.5,778.6 L 199.9,779.9 L 202.9,773.2 L 199.3,772.3 L 198.8,769.5 L 198.4,763.9 L 200.7,757.2 L 193.9,754.2 L 196.5,744.1 L 199.9,737.4 L 204.4,733.6 L 214.6,736.3 L 219.8,735.1 L 231.8,735.9 L 236.0,729.1 L 245.7,729.9 L 243.8,727.1 L 243.3,718.2 L 250.4,714.6 L 256.9,708.8 L 251.5,707.9 L 246.2,699.2 L 240.7,698.9 L 237.5,701.3 L 234.5,694.2 L 236.2,685.0 L 232.6,681.4 L 230.6,682.8 L 222.7,678.9 L 220.4,679.8 L 219.6,681.6 L 217.6,695.9 L 211.6,700.2 L 208.3,699.7 L 209.4,694.5 L 195.1,693.6 L 191.9,683.0 L 189.0,680.4 L 181.3,677.0 L 178.0,669.4 L 175.4,668.1 L 167.7,668.3 L 158.0,662.8 L 152.6,661.4 L 151.3,656.6 L 151.0,640.4 L 148.8,633.3 L 143.8,624.1 L 137.1,619.2 L 133.9,618.6 L 124.5,621.0 L 120.6,620.0 L 118.3,617.0 L 115.7,608.7 L 99.0,635.2 L 98.4,641.3 L 101.3,643.7 L 102.1,647.6 L 99.1,651.8 L 98.6,656.3 L 96.8,657.4 L 95.0,662.4 L 98.8,665.4 L 98.4,673.6 L 107.4,707.0 L 106.7,713.0 L 109.6,713.2 L 116.6,726.7 L 120.6,749.7 L 128.0,767.2 L 127.1,787.4 L 137.0,786.4 L 135.3,796.7 L 137.8,797.2 L 139.8,799.6 L 146.2,797.9 L 150.1,794.5 L 150.0,795.4 L 155.2,798.6 L 156.9,798.5 L 157.5,799.9 L 159.5,798.5 L 161.5,800.1 L 161.3,801.7 L 163.3,802.3 L 162.8,810.9 L 165.0,824.5 L 168.4,831.3 L 168.2,837.7 L 174.7,837.3 L 179.8,835.5 L 183.9,830.0 L 188.6,819.6 L 188.6,815.3 L 186.8,813.4 L 191.1,811.9 L 191.5,804.2 L 190.5,799.4 L 193.1,796.6 L 192.8,790.2 L 189.4,786.7 L 189.2,781.8 Z" fill="#0f111a" opacity="0.9" /><path d="M 209.4,694.5 L 209.7,692.0 L 206.1,688.6 L 207.5,687.2 L 206.6,687.1 L 207.0,684.9 L 209.5,683.0 L 209.9,680.4 L 213.2,678.8 L 213.1,676.4 L 215.1,675.6 L 215.9,673.8 L 210.3,662.8 L 209.9,654.5 L 215.4,657.1 L 219.5,656.3 L 221.1,652.5 L 226.7,651.1 L 228.6,638.8 L 233.7,636.5 L 232.8,634.1 L 229.0,632.5 L 214.7,632.9 L 214.9,631.1 L 221.9,625.3 L 228.7,614.2 L 231.5,606.7 L 235.9,603.1 L 239.2,603.0 L 241.5,607.9 L 245.6,609.2 L 251.8,606.4 L 260.7,607.2 L 266.2,604.0 L 277.6,593.1 L 282.1,593.1 L 285.6,591.3 L 286.4,583.5 L 287.8,580.5 L 276.1,577.1 L 272.9,571.3 L 269.1,568.4 L 259.0,568.6 L 255.2,564.3 L 247.4,564.2 L 245.2,567.5 L 239.5,568.2 L 233.1,573.0 L 230.9,578.8 L 221.1,580.3 L 223.0,586.1 L 227.9,590.9 L 229.2,595.4 L 224.9,598.4 L 224.1,595.4 L 220.4,594.1 L 217.9,597.5 L 210.4,598.9 L 213.8,597.3 L 210.6,594.5 L 200.1,593.4 L 200.7,579.9 L 192.8,580.1 L 189.0,582.4 L 187.9,586.8 L 185.8,586.8 L 179.6,593.0 L 173.4,593.2 L 172.7,597.8 L 167.6,598.6 L 164.6,602.3 L 163.3,607.7 L 158.6,610.7 L 157.3,614.8 L 152.8,618.0 L 152.2,627.3 L 148.8,633.3 L 151.6,644.5 L 151.3,656.6 L 152.6,661.4 L 158.0,662.8 L 167.7,668.3 L 175.4,668.1 L 178.0,669.4 L 181.3,677.0 L 189.0,680.4 L 191.9,683.0 L 195.1,693.6 L 209.4,694.5 Z" fill="#0f111a" opacity="0.9" /><path d="M 522.3,498.1 L 521.0,487.4 L 522.5,481.2 L 521.6,479.8 L 517.2,480.0 L 521.2,473.4 L 521.6,471.7 L 520.0,469.8 L 517.2,470.8 L 511.6,469.9 L 508.0,467.3 L 506.9,463.6 L 497.3,456.3 L 488.3,453.2 L 480.2,452.6 L 466.7,448.5 L 453.5,437.3 L 439.6,428.2 L 431.3,427.7 L 423.6,429.2 L 424.3,431.0 L 423.1,436.9 L 417.2,439.2 L 409.8,439.8 L 407.1,443.1 L 405.6,440.5 L 402.8,440.2 L 401.2,438.5 L 398.8,440.9 L 398.6,451.4 L 394.8,456.5 L 398.3,461.3 L 397.6,465.6 L 399.6,466.6 L 401.9,471.7 L 397.0,470.1 L 393.1,471.7 L 389.6,475.2 L 382.6,474.2 L 381.5,465.9 L 382.8,462.0 L 371.8,443.5 L 367.7,438.9 L 365.8,431.9 L 369.6,422.3 L 368.6,418.4 L 369.5,416.1 L 368.2,414.7 L 368.8,411.9 L 370.7,409.8 L 373.9,408.8 L 374.3,407.0 L 377.6,406.6 L 377.4,404.2 L 379.7,403.3 L 380.4,404.7 L 381.7,404.1 L 380.6,401.0 L 384.3,398.8 L 384.8,394.5 L 383.5,386.5 L 386.0,381.0 L 380.2,380.7 L 375.2,382.6 L 368.3,379.7 L 364.9,375.6 L 361.9,375.3 L 355.8,370.4 L 348.1,368.2 L 337.9,370.2 L 335.9,373.1 L 334.5,379.5 L 332.0,381.0 L 320.5,381.7 L 314.4,379.1 L 311.3,380.7 L 309.4,386.2 L 304.1,388.4 L 300.9,387.9 L 292.5,383.5 L 287.3,384.7 L 285.1,387.1 L 282.6,393.8 L 281.6,401.3 L 283.5,409.3 L 279.8,414.3 L 280.1,419.8 L 285.3,428.6 L 295.5,427.8 L 298.9,430.0 L 302.1,436.3 L 301.8,438.8 L 298.8,442.3 L 295.9,443.4 L 289.0,439.7 L 281.7,438.4 L 275.2,449.0 L 272.7,451.1 L 261.6,453.9 L 253.0,452.5 L 250.8,453.5 L 255.7,458.0 L 259.1,465.5 L 265.3,474.2 L 262.6,480.9 L 265.8,481.8 L 268.6,480.2 L 272.2,481.5 L 280.7,493.0 L 282.0,498.4 L 281.8,501.6 L 272.2,502.3 L 265.3,506.4 L 260.2,506.4 L 258.1,510.1 L 259.4,516.4 L 263.0,520.4 L 258.1,523.3 L 255.7,531.3 L 250.8,539.3 L 251.0,541.9 L 253.9,547.5 L 263.5,548.7 L 273.0,547.0 L 278.6,549.4 L 289.9,546.9 L 291.5,557.8 L 297.4,561.1 L 301.0,564.9 L 309.8,563.4 L 313.3,565.2 L 317.1,565.0 L 321.6,562.6 L 322.7,559.5 L 325.5,557.0 L 332.2,557.7 L 331.9,545.0 L 342.5,538.4 L 350.5,526.4 L 356.7,526.0 L 360.9,530.1 L 362.9,544.9 L 366.8,547.3 L 373.3,548.3 L 393.1,545.5 L 398.6,538.7 L 402.2,537.7 L 403.9,538.6 L 406.4,537.8 L 408.0,539.8 L 410.6,539.3 L 414.4,542.0 L 418.8,541.9 L 419.2,540.2 L 422.4,539.7 L 422.8,540.5 L 425.4,540.0 L 425.6,541.2 L 427.7,540.8 L 428.7,542.6 L 430.4,541.0 L 430.5,542.1 L 433.6,542.8 L 436.0,541.3 L 438.7,541.3 L 439.8,539.7 L 445.1,538.6 L 446.7,536.8 L 447.4,539.1 L 448.4,538.5 L 452.0,542.2 L 451.5,552.0 L 459.1,563.2 L 467.0,562.4 L 482.5,566.2 L 486.7,568.3 L 491.4,568.2 L 492.5,570.2 L 495.8,571.1 L 496.4,572.4 L 498.4,571.8 L 499.6,572.9 L 501.4,572.0 L 505.1,574.4 L 505.8,579.7 L 507.8,580.5 L 508.3,583.2 L 511.2,582.0 L 513.5,585.7 L 529.4,586.4 L 533.7,584.0 L 541.5,584.3 L 547.8,587.3 L 552.0,586.0 L 565.9,586.3 L 570.3,568.5 L 569.4,562.9 L 568.1,560.6 L 553.4,552.2 L 551.5,545.0 L 542.2,531.8 L 538.6,522.2 L 538.8,517.3 L 541.3,512.7 L 541.5,509.4 L 538.9,507.4 L 527.2,508.3 L 523.6,503.1 L 522.3,498.1 Z" fill="#0f111a" opacity="0.9" /><path d="M 488.3,453.2 L 492.5,451.4 L 491.9,448.7 L 493.2,446.6 L 488.3,447.5 L 487.9,443.5 L 490.4,442.6 L 497.3,443.1 L 502.5,440.6 L 505.4,444.6 L 510.3,445.0 L 513.8,444.1 L 515.1,442.4 L 517.9,442.5 L 517.7,439.5 L 521.4,436.9 L 521.1,435.0 L 515.7,428.1 L 515.4,425.3 L 514.1,426.6 L 512.2,426.2 L 511.3,427.8 L 505.1,428.1 L 503.2,430.4 L 498.8,430.3 L 496.2,427.7 L 499.0,425.2 L 506.3,426.0 L 511.0,411.8 L 509.0,411.6 L 507.7,409.5 L 510.6,409.1 L 511.8,410.1 L 512.1,407.6 L 514.2,408.0 L 512.4,406.5 L 513.6,402.7 L 512.7,400.5 L 514.0,398.6 L 511.6,398.6 L 510.5,396.2 L 508.9,396.0 L 508.4,397.6 L 505.7,398.2 L 504.0,400.0 L 502.9,397.9 L 499.3,401.0 L 498.4,400.4 L 498.2,402.0 L 497.5,400.9 L 495.0,401.5 L 493.1,397.6 L 493.9,396.4 L 491.2,394.6 L 492.0,392.2 L 490.1,393.9 L 489.9,392.4 L 484.7,392.2 L 483.8,385.8 L 481.5,386.0 L 481.7,384.0 L 479.8,383.5 L 478.9,381.7 L 476.7,382.3 L 475.8,380.7 L 474.6,380.8 L 473.8,376.8 L 475.9,373.8 L 478.5,375.5 L 479.6,374.4 L 482.0,376.2 L 481.9,374.5 L 484.4,373.8 L 484.5,377.3 L 485.2,376.3 L 485.4,377.4 L 487.9,377.2 L 488.8,376.9 L 488.4,375.1 L 490.5,375.3 L 489.9,372.5 L 492.5,371.0 L 494.5,370.8 L 495.9,372.6 L 497.9,371.5 L 499.0,365.3 L 500.5,363.3 L 500.0,362.1 L 497.0,362.1 L 497.0,360.3 L 494.0,358.4 L 493.9,356.2 L 494.6,355.5 L 495.7,356.7 L 499.3,356.8 L 500.6,355.8 L 499.6,353.0 L 501.1,353.0 L 502.5,350.9 L 507.6,349.9 L 506.9,348.7 L 508.4,348.3 L 508.3,345.8 L 510.6,346.2 L 513.4,344.7 L 514.2,340.8 L 511.9,335.8 L 512.4,333.7 L 511.1,332.9 L 513.0,328.8 L 511.8,326.6 L 513.2,322.5 L 502.4,318.5 L 500.6,313.2 L 498.8,312.6 L 496.6,308.8 L 487.3,305.2 L 482.6,306.9 L 474.0,303.3 L 471.6,302.0 L 469.9,298.8 L 466.5,299.7 L 463.5,298.0 L 459.6,292.4 L 457.5,294.8 L 455.2,295.4 L 453.9,294.9 L 454.1,293.8 L 451.5,294.3 L 447.4,292.9 L 446.7,295.0 L 443.2,292.8 L 443.2,296.9 L 446.0,299.3 L 436.4,296.9 L 430.7,293.8 L 427.0,296.7 L 424.9,296.8 L 423.4,295.5 L 420.6,296.4 L 419.9,294.9 L 411.4,297.3 L 409.1,293.8 L 403.8,295.8 L 404.2,293.6 L 401.4,291.9 L 397.3,292.5 L 392.2,296.4 L 388.4,294.7 L 382.1,294.5 L 377.0,296.4 L 374.0,299.9 L 371.7,298.0 L 369.1,293.2 L 372.5,283.2 L 366.5,269.9 L 353.9,256.0 L 360.8,251.5 L 361.6,246.9 L 357.5,242.1 L 357.8,231.9 L 358.8,230.1 L 363.3,227.7 L 367.8,230.1 L 371.0,226.0 L 377.4,230.4 L 381.5,230.1 L 383.0,221.0 L 380.7,216.4 L 380.2,210.6 L 384.9,210.1 L 390.5,212.1 L 387.5,208.8 L 380.0,204.2 L 373.8,202.5 L 372.5,198.3 L 366.7,198.4 L 358.0,192.3 L 342.6,189.4 L 333.5,190.2 L 328.5,193.5 L 318.6,192.6 L 320.3,196.0 L 320.3,199.2 L 313.0,199.5 L 310.6,204.8 L 312.3,210.7 L 311.8,216.2 L 309.2,218.4 L 312.3,229.7 L 311.2,236.2 L 309.5,239.3 L 304.4,243.2 L 290.7,246.9 L 283.5,250.4 L 279.3,250.4 L 277.1,244.6 L 272.6,238.2 L 268.8,236.9 L 256.8,243.0 L 246.1,235.4 L 238.8,227.5 L 229.7,226.8 L 214.0,230.7 L 205.3,230.6 L 197.4,232.5 L 193.3,239.7 L 189.0,239.9 L 186.0,245.6 L 164.9,248.7 L 165.2,250.7 L 161.9,255.3 L 162.0,258.7 L 164.2,262.3 L 170.0,265.9 L 173.8,270.6 L 173.3,278.1 L 175.3,284.4 L 174.0,288.3 L 168.9,291.8 L 168.8,294.5 L 170.4,297.7 L 173.7,298.1 L 177.6,302.2 L 176.6,304.6 L 169.8,309.2 L 166.9,315.7 L 161.6,318.1 L 163.4,325.0 L 160.8,328.8 L 163.3,332.3 L 175.8,334.2 L 177.9,336.3 L 178.3,345.0 L 182.9,348.1 L 182.2,354.0 L 185.8,360.9 L 181.2,365.5 L 183.5,367.1 L 187.0,366.4 L 193.2,369.9 L 194.2,372.9 L 199.4,376.0 L 204.0,384.9 L 208.4,386.5 L 210.4,388.9 L 210.8,395.6 L 216.8,399.3 L 218.4,403.1 L 216.8,404.9 L 217.3,409.1 L 225.2,420.3 L 228.6,433.8 L 234.7,435.6 L 236.6,438.1 L 238.7,438.6 L 238.4,441.1 L 244.6,445.2 L 245.3,450.3 L 248.8,453.2 L 250.4,453.8 L 253.0,452.5 L 261.6,453.9 L 272.7,451.1 L 275.2,449.0 L 281.7,438.4 L 289.0,439.7 L 295.9,443.4 L 298.8,442.3 L 301.8,438.8 L 302.1,436.3 L 298.9,430.0 L 295.5,427.8 L 285.3,428.6 L 280.1,419.8 L 279.8,414.3 L 283.5,409.3 L 281.6,401.3 L 282.6,393.8 L 285.1,387.1 L 287.3,384.7 L 292.5,383.5 L 300.9,387.9 L 304.1,388.4 L 309.4,386.2 L 311.3,380.7 L 314.4,379.1 L 320.5,381.7 L 332.0,381.0 L 334.5,379.5 L 335.9,373.1 L 337.9,370.2 L 348.1,368.2 L 355.8,370.4 L 361.9,375.3 L 364.9,375.6 L 368.3,379.7 L 375.2,382.6 L 380.2,380.7 L 386.0,381.0 L 383.5,386.5 L 384.8,394.5 L 384.3,398.8 L 380.6,401.0 L 381.7,404.1 L 380.4,404.7 L 379.7,403.3 L 377.4,404.2 L 377.6,406.6 L 374.3,407.0 L 373.9,408.8 L 370.7,409.8 L 368.8,411.9 L 368.2,414.7 L 369.5,416.1 L 368.6,418.4 L 369.6,422.3 L 365.8,431.9 L 367.7,438.9 L 371.8,443.5 L 382.8,462.0 L 381.5,465.9 L 382.6,474.2 L 389.6,475.2 L 393.1,471.7 L 397.0,470.1 L 401.9,471.7 L 399.6,466.6 L 397.6,465.6 L 398.3,461.3 L 394.8,456.5 L 398.6,451.4 L 398.8,440.9 L 401.6,438.4 L 402.8,440.2 L 405.6,440.5 L 407.1,443.1 L 409.8,439.8 L 411.2,440.4 L 420.8,438.1 L 423.1,436.9 L 424.3,431.0 L 423.6,429.2 L 427.8,428.2 L 439.6,428.2 L 453.5,437.3 L 466.7,448.5 L 480.2,452.6 L 488.3,453.2 Z" fill="#0f111a" opacity="0.9" /><path d="M 372.5,198.3 L 375.5,192.2 L 380.6,188.6 L 386.9,187.0 L 388.8,184.9 L 388.4,179.9 L 392.6,177.5 L 395.3,179.5 L 392.2,180.1 L 391.4,181.8 L 392.2,184.0 L 395.5,184.0 L 394.8,186.4 L 402.3,187.9 L 402.6,190.0 L 400.9,190.3 L 400.5,192.2 L 403.7,199.3 L 405.7,200.3 L 405.9,202.8 L 403.7,205.3 L 405.2,208.4 L 406.8,208.3 L 406.3,206.0 L 408.3,206.5 L 409.3,202.1 L 412.4,202.0 L 412.7,199.1 L 414.9,200.6 L 415.2,198.9 L 417.0,199.0 L 416.2,199.0 L 416.5,197.4 L 418.5,198.0 L 420.3,192.5 L 422.7,193.0 L 422.0,190.3 L 423.6,188.9 L 422.2,187.6 L 424.9,184.8 L 424.0,183.2 L 426.4,182.6 L 426.5,184.0 L 428.7,184.5 L 430.8,179.5 L 432.2,180.8 L 433.4,179.9 L 433.8,181.3 L 434.2,179.3 L 436.4,178.1 L 440.7,178.8 L 440.9,177.4 L 443.6,178.4 L 442.6,176.3 L 443.5,175.4 L 444.1,176.4 L 447.6,174.7 L 447.9,175.9 L 449.7,176.0 L 451.5,174.3 L 451.6,175.9 L 452.8,174.6 L 452.8,176.4 L 454.2,175.5 L 455.4,178.0 L 457.4,178.2 L 460.0,182.0 L 466.4,176.1 L 468.6,168.1 L 472.8,171.1 L 473.9,168.3 L 472.6,160.5 L 475.6,158.7 L 479.5,162.2 L 489.8,162.8 L 491.0,165.7 L 494.7,166.1 L 499.0,163.9 L 510.7,164.8 L 514.1,166.3 L 525.1,163.5 L 530.2,165.9 L 533.8,163.1 L 538.5,165.2 L 537.9,160.4 L 540.2,158.5 L 548.2,156.7 L 550.1,161.7 L 553.1,164.8 L 554.4,169.4 L 553.9,173.8 L 551.4,175.1 L 550.7,178.7 L 557.3,186.4 L 559.1,196.4 L 558.6,200.3 L 555.8,202.5 L 550.7,204.0 L 552.2,206.8 L 559.5,207.7 L 563.3,205.7 L 568.7,206.4 L 574.0,208.8 L 578.3,208.6 L 581.7,206.4 L 591.0,210.0 L 593.7,209.4 L 593.9,207.6 L 591.4,205.6 L 590.0,202.2 L 590.3,196.5 L 597.0,197.8 L 598.0,192.8 L 595.1,189.6 L 590.8,188.9 L 589.2,187.2 L 590.6,181.8 L 588.9,177.6 L 590.4,165.4 L 593.1,163.3 L 591.5,161.7 L 595.3,159.0 L 595.2,157.9 L 584.2,158.2 L 587.2,156.2 L 586.4,154.5 L 584.1,154.4 L 583.0,149.9 L 580.6,149.2 L 582.0,145.6 L 579.8,142.9 L 576.4,144.9 L 570.7,142.3 L 569.0,143.5 L 569.6,145.2 L 567.0,145.4 L 567.0,143.7 L 564.0,141.8 L 564.5,139.0 L 562.0,137.1 L 562.6,135.7 L 560.5,128.8 L 549.3,124.0 L 546.6,118.4 L 532.8,114.2 L 528.7,103.1 L 524.2,106.9 L 509.5,111.0 L 505.3,114.3 L 502.3,113.2 L 498.5,115.5 L 490.8,115.3 L 487.0,117.5 L 480.3,118.7 L 476.9,116.2 L 472.7,115.4 L 471.5,123.1 L 470.3,124.1 L 464.9,122.5 L 461.4,125.7 L 453.5,124.7 L 448.7,128.9 L 447.0,128.1 L 446.6,126.5 L 444.4,129.2 L 443.1,128.5 L 443.0,127.3 L 447.1,123.1 L 445.9,120.0 L 442.7,117.9 L 442.8,116.4 L 444.3,115.8 L 443.6,113.1 L 441.5,111.5 L 437.7,113.2 L 432.7,111.5 L 432.0,115.9 L 429.1,118.2 L 426.4,118.4 L 425.1,123.8 L 414.8,134.7 L 413.0,134.2 L 416.6,129.7 L 416.0,128.5 L 402.8,127.2 L 398.5,129.5 L 396.6,132.6 L 386.0,138.0 L 379.2,145.1 L 373.3,147.2 L 372.5,150.4 L 375.6,155.8 L 374.6,159.1 L 372.4,160.7 L 373.1,161.5 L 371.4,161.5 L 371.2,162.8 L 370.4,162.2 L 371.0,162.9 L 369.5,162.7 L 369.2,159.2 L 367.0,157.6 L 367.4,156.1 L 365.1,156.7 L 359.5,155.3 L 359.5,154.0 L 356.5,153.1 L 356.5,149.8 L 352.0,147.3 L 351.5,145.2 L 353.9,144.4 L 354.4,142.9 L 353.1,142.4 L 354.6,141.2 L 352.2,138.3 L 352.9,137.1 L 349.1,134.3 L 345.8,137.3 L 344.3,136.4 L 341.6,141.0 L 332.8,147.2 L 323.1,161.4 L 317.0,176.5 L 333.5,190.2 L 342.6,189.4 L 358.0,192.3 L 366.7,198.4 L 372.5,198.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 565.9,586.3 L 573.1,588.0 L 574.1,593.5 L 571.6,597.3 L 585.8,598.7 L 587.2,593.0 L 589.8,592.9 L 591.7,589.8 L 594.5,579.9 L 599.3,572.5 L 602.2,573.0 L 602.3,570.2 L 608.1,571.2 L 610.0,569.7 L 612.4,570.2 L 614.1,569.2 L 615.0,573.8 L 613.9,577.0 L 611.6,578.1 L 611.9,580.7 L 615.1,580.1 L 616.5,576.2 L 617.9,574.2 L 619.1,574.3 L 619.4,571.5 L 623.8,574.4 L 628.9,574.9 L 629.1,571.2 L 630.1,570.9 L 630.9,567.5 L 628.6,560.1 L 629.7,559.0 L 629.0,556.0 L 627.0,556.2 L 625.5,554.4 L 618.7,557.2 L 614.2,552.6 L 613.9,548.8 L 612.0,548.0 L 613.4,545.1 L 611.8,540.5 L 612.7,531.4 L 611.8,529.9 L 621.1,525.8 L 621.6,514.7 L 624.2,513.7 L 626.7,514.6 L 624.5,507.8 L 627.2,505.9 L 627.0,502.4 L 625.3,502.0 L 626.7,492.3 L 624.9,481.7 L 622.5,475.9 L 624.1,474.6 L 622.6,472.2 L 622.8,466.4 L 620.9,461.4 L 621.9,460.0 L 621.8,455.3 L 624.3,454.3 L 623.9,450.5 L 624.9,448.8 L 622.9,448.7 L 621.4,446.4 L 625.6,444.6 L 626.3,441.7 L 628.2,440.5 L 628.2,438.5 L 633.1,438.7 L 635.0,435.7 L 640.9,434.9 L 640.9,430.9 L 640.6,427.2 L 638.1,426.1 L 638.4,422.6 L 636.5,421.0 L 637.4,420.1 L 635.7,420.1 L 636.2,418.8 L 634.7,417.9 L 630.0,416.4 L 630.6,411.8 L 629.1,406.5 L 627.0,406.3 L 626.4,404.2 L 621.7,403.5 L 620.7,402.1 L 619.0,402.5 L 618.0,399.5 L 614.2,397.9 L 613.6,396.3 L 610.9,397.1 L 608.2,392.5 L 605.1,392.7 L 593.2,373.0 L 588.4,372.6 L 590.0,375.9 L 583.9,373.7 L 582.1,374.8 L 581.6,372.6 L 579.5,375.1 L 576.4,372.0 L 574.2,372.8 L 572.6,371.1 L 570.0,370.9 L 566.2,372.8 L 564.1,368.1 L 563.9,371.6 L 565.9,376.4 L 560.0,382.7 L 559.6,384.9 L 561.7,388.6 L 564.3,390.1 L 564.5,392.7 L 560.9,393.6 L 558.6,391.5 L 555.5,393.8 L 552.7,403.3 L 553.7,405.6 L 557.5,407.0 L 557.7,409.3 L 553.7,410.0 L 551.6,411.6 L 554.2,414.0 L 552.6,420.4 L 557.1,422.1 L 563.0,419.7 L 570.8,418.7 L 574.5,419.6 L 575.1,423.0 L 573.7,426.3 L 566.0,429.0 L 568.1,433.9 L 566.4,440.0 L 556.0,442.5 L 551.7,435.6 L 542.4,433.4 L 540.6,430.3 L 535.5,428.3 L 535.4,426.3 L 532.9,425.9 L 531.7,427.7 L 529.0,428.7 L 525.8,428.2 L 523.9,436.8 L 521.0,436.9 L 517.7,439.5 L 517.9,442.5 L 515.1,442.4 L 513.8,444.1 L 510.3,445.0 L 505.4,444.6 L 502.5,440.6 L 497.3,443.1 L 490.4,442.6 L 487.9,443.5 L 487.5,445.3 L 488.3,447.5 L 493.2,446.6 L 491.9,448.7 L 492.5,451.4 L 488.3,453.2 L 500.6,458.1 L 506.9,463.6 L 508.0,467.3 L 511.6,469.9 L 517.2,470.8 L 520.0,469.8 L 521.6,471.7 L 517.2,480.0 L 521.6,479.8 L 522.5,481.2 L 521.0,487.4 L 523.6,503.1 L 527.2,508.3 L 538.9,507.4 L 541.5,509.4 L 541.3,512.7 L 538.8,517.3 L 538.6,522.2 L 542.2,531.8 L 551.5,545.0 L 553.4,552.2 L 568.1,560.6 L 569.4,562.9 L 570.3,568.5 L 565.9,586.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 168.2,837.7 L 164.0,837.3 L 162.2,835.6 L 161.2,837.1 L 162.1,838.5 L 161.1,839.1 L 162.6,842.7 L 160.0,845.3 L 162.3,847.7 L 160.9,848.3 L 164.4,850.1 L 162.7,852.0 L 164.1,852.5 L 164.0,854.5 L 162.9,854.7 L 164.0,856.9 L 165.2,856.7 L 165.5,861.6 L 166.9,862.1 L 166.4,863.8 L 167.6,863.6 L 168.5,865.1 L 166.0,868.7 L 164.3,868.3 L 163.9,870.5 L 162.7,869.3 L 160.0,869.4 L 160.0,868.3 L 159.3,869.8 L 156.4,869.8 L 156.1,868.8 L 155.7,869.9 L 153.5,867.7 L 152.0,869.6 L 147.6,866.9 L 145.7,867.0 L 145.5,868.0 L 137.7,867.0 L 136.6,870.1 L 134.8,870.2 L 129.2,867.6 L 121.3,866.0 L 115.9,867.3 L 116.5,872.0 L 205.2,889.9 L 221.8,894.4 L 233.3,900.3 L 234.5,903.4 L 233.8,904.1 L 239.2,906.7 L 239.8,908.7 L 243.6,909.9 L 244.4,912.9 L 246.4,912.0 L 251.9,913.7 L 253.1,914.9 L 252.2,917.8 L 254.5,918.6 L 254.9,920.0 L 256.6,918.7 L 260.0,919.9 L 261.2,918.1 L 263.8,917.5 L 270.1,917.7 L 276.0,910.8 L 279.0,909.7 L 279.0,908.4 L 289.6,905.0 L 290.6,903.2 L 294.0,901.6 L 303.6,900.0 L 302.4,899.3 L 303.4,898.4 L 301.9,898.5 L 303.4,895.0 L 308.8,892.6 L 307.3,892.6 L 308.6,890.0 L 316.1,888.3 L 318.6,881.8 L 322.9,880.0 L 323.9,867.1 L 325.5,862.2 L 329.7,857.9 L 337.0,856.6 L 339.3,853.0 L 344.1,850.3 L 342.9,845.7 L 331.1,839.2 L 326.5,828.3 L 320.0,823.5 L 321.8,822.9 L 319.6,816.0 L 321.2,808.9 L 313.7,801.4 L 305.7,801.0 L 302.2,799.2 L 292.3,801.3 L 289.4,800.0 L 289.3,791.8 L 291.2,789.0 L 291.7,782.5 L 294.2,778.6 L 292.5,776.5 L 289.3,775.5 L 289.7,764.6 L 278.4,758.1 L 269.5,745.8 L 260.8,741.4 L 258.2,736.1 L 255.3,734.1 L 245.7,729.9 L 236.0,729.1 L 231.8,735.9 L 219.8,735.1 L 214.6,736.3 L 204.4,733.6 L 199.9,737.4 L 196.5,744.1 L 193.9,754.2 L 200.7,757.2 L 198.4,763.9 L 198.8,769.5 L 199.3,772.3 L 202.9,773.2 L 199.9,779.9 L 194.5,778.6 L 190.7,776.0 L 189.2,781.8 L 189.4,786.7 L 192.8,790.2 L 193.1,796.6 L 190.5,799.4 L 191.5,804.2 L 191.1,811.9 L 186.8,813.4 L 188.6,815.3 L 188.6,819.6 L 183.9,830.0 L 179.8,835.5 L 174.7,837.3 L 168.2,837.7 Z" fill="#0f111a" opacity="0.9" /><path d="M 513.5,585.7 L 501.6,586.0 L 496.3,590.3 L 494.2,594.9 L 483.1,598.0 L 477.6,602.5 L 456.0,603.7 L 432.6,638.1 L 427.3,641.8 L 413.7,643.3 L 409.2,647.5 L 409.4,651.9 L 412.0,656.0 L 415.0,658.1 L 418.1,663.8 L 416.8,666.6 L 412.4,668.6 L 410.3,673.6 L 403.3,680.0 L 401.9,683.3 L 394.2,688.5 L 388.9,700.0 L 386.1,702.7 L 383.5,715.3 L 377.9,720.0 L 378.3,722.3 L 379.7,723.0 L 378.8,724.6 L 379.7,727.4 L 376.5,729.4 L 377.9,730.9 L 375.9,732.2 L 377.8,732.2 L 378.6,734.4 L 376.2,735.1 L 377.0,736.5 L 374.9,738.3 L 375.7,740.4 L 372.5,742.4 L 373.2,745.0 L 371.1,746.2 L 370.4,748.9 L 371.4,750.3 L 372.9,749.9 L 374.3,752.5 L 373.4,755.0 L 374.2,755.5 L 373.1,756.8 L 371.0,756.9 L 370.6,759.1 L 374.6,761.1 L 387.6,777.3 L 391.1,778.9 L 452.3,782.8 L 465.2,782.0 L 472.7,785.2 L 476.3,777.4 L 477.8,776.3 L 482.0,776.1 L 488.5,782.7 L 495.2,781.2 L 498.2,777.1 L 503.8,779.2 L 511.0,777.2 L 519.6,772.7 L 522.1,768.6 L 528.5,769.9 L 533.7,764.9 L 539.9,761.7 L 547.9,755.4 L 548.1,750.3 L 552.9,749.8 L 563.4,744.2 L 575.4,743.1 L 580.2,747.3 L 581.2,739.2 L 578.0,733.9 L 573.2,733.2 L 564.9,734.8 L 559.8,732.6 L 552.5,727.3 L 551.4,724.5 L 555.4,716.2 L 554.3,715.8 L 552.8,711.1 L 549.3,709.3 L 548.9,706.8 L 554.2,699.3 L 557.9,689.7 L 554.3,679.1 L 551.2,675.0 L 551.2,671.3 L 556.8,661.3 L 568.5,652.1 L 569.8,648.0 L 568.3,638.1 L 565.6,629.9 L 562.1,605.1 L 565.9,586.3 L 552.0,586.0 L 547.8,587.3 L 541.5,584.3 L 533.7,584.0 L 529.4,586.4 L 513.5,585.7 Z" fill="#0f111a" opacity="0.9" /><path d="M 521.4,436.9 L 523.9,436.8 L 525.8,428.2 L 529.0,428.7 L 531.7,427.7 L 532.1,426.1 L 534.3,426.0 L 535.4,426.3 L 535.9,428.6 L 540.6,430.3 L 542.4,433.4 L 551.7,435.6 L 556.0,442.5 L 565.9,440.5 L 567.3,438.7 L 568.1,433.9 L 566.1,430.5 L 566.2,428.5 L 573.7,426.3 L 575.1,423.0 L 574.5,419.6 L 570.8,418.7 L 563.0,419.7 L 557.1,422.1 L 552.6,420.4 L 554.2,414.0 L 551.6,411.6 L 553.7,410.0 L 557.7,409.3 L 557.5,407.0 L 553.7,405.6 L 552.7,403.3 L 555.5,393.8 L 558.6,391.5 L 560.9,393.6 L 564.5,392.7 L 564.3,390.1 L 561.7,388.6 L 559.6,384.9 L 560.0,382.7 L 565.9,376.4 L 563.6,368.4 L 565.7,370.0 L 566.2,372.8 L 570.4,370.8 L 573.6,371.4 L 574.2,372.8 L 576.4,372.0 L 579.5,375.1 L 581.6,372.6 L 582.1,374.8 L 583.9,373.7 L 590.0,375.9 L 588.4,372.6 L 592.2,373.5 L 593.6,372.2 L 592.1,370.9 L 595.7,370.6 L 592.6,368.6 L 592.8,365.8 L 598.1,365.8 L 599.4,367.6 L 601.2,366.4 L 599.8,365.1 L 602.2,363.2 L 603.8,363.4 L 605.1,365.9 L 605.5,364.8 L 608.1,364.7 L 609.3,362.8 L 608.7,359.3 L 610.5,357.2 L 612.0,357.5 L 611.6,356.0 L 613.1,354.1 L 610.5,348.7 L 611.6,345.8 L 608.5,343.7 L 603.3,331.4 L 603.9,327.6 L 607.5,322.5 L 606.1,317.6 L 610.9,315.0 L 613.9,308.8 L 610.1,302.3 L 614.5,295.6 L 616.5,295.8 L 615.5,290.8 L 618.5,281.9 L 616.8,279.5 L 611.9,277.2 L 609.3,277.5 L 608.6,275.6 L 609.8,274.7 L 609.7,272.5 L 607.8,270.8 L 602.3,269.1 L 597.6,268.9 L 594.1,270.2 L 590.1,269.1 L 587.9,270.6 L 588.8,272.6 L 587.1,275.6 L 578.3,279.0 L 574.7,277.5 L 572.3,274.0 L 572.2,270.9 L 577.8,271.9 L 583.5,268.1 L 581.5,265.9 L 574.2,265.7 L 573.1,262.4 L 573.7,258.8 L 577.4,259.9 L 580.5,258.5 L 589.0,259.2 L 593.4,256.5 L 591.7,252.8 L 589.6,251.9 L 581.4,255.9 L 577.0,247.5 L 579.1,243.6 L 585.1,248.4 L 587.5,247.6 L 588.9,245.1 L 588.1,239.3 L 584.5,237.9 L 586.0,233.3 L 585.4,231.8 L 586.9,230.9 L 586.0,229.0 L 589.8,221.6 L 589.0,213.8 L 589.8,210.6 L 591.0,210.0 L 581.7,206.4 L 578.3,208.6 L 574.0,208.8 L 568.7,206.4 L 563.3,205.7 L 559.5,207.7 L 552.2,206.8 L 550.7,204.0 L 555.8,202.5 L 558.6,200.3 L 559.1,196.4 L 557.3,186.4 L 550.7,178.7 L 551.4,175.1 L 553.9,173.8 L 554.4,169.4 L 553.1,164.8 L 550.1,161.7 L 548.2,156.7 L 540.2,158.5 L 537.9,160.4 L 538.5,165.2 L 533.8,163.1 L 530.2,165.9 L 525.1,163.5 L 514.1,166.3 L 510.7,164.8 L 499.0,163.9 L 494.7,166.1 L 491.0,165.7 L 489.8,162.8 L 479.5,162.2 L 475.6,158.7 L 472.6,160.5 L 473.9,168.3 L 472.8,171.1 L 468.6,168.1 L 466.4,176.1 L 460.0,182.0 L 457.4,178.2 L 455.4,178.0 L 454.2,175.5 L 452.8,176.4 L 452.8,174.6 L 451.6,175.9 L 451.5,174.3 L 449.7,176.0 L 447.9,175.9 L 447.6,174.7 L 444.1,176.4 L 443.5,175.4 L 442.6,176.3 L 443.6,178.4 L 440.9,177.4 L 440.7,178.8 L 436.4,178.1 L 434.2,179.3 L 433.8,181.3 L 433.4,179.9 L 432.2,180.8 L 430.8,179.5 L 428.7,184.5 L 426.5,184.0 L 426.4,182.6 L 424.0,183.2 L 424.9,184.8 L 422.2,187.6 L 423.6,188.9 L 422.0,190.3 L 422.7,193.0 L 420.3,192.5 L 418.5,198.0 L 416.5,197.4 L 416.2,199.0 L 417.0,199.0 L 415.2,198.9 L 414.9,200.6 L 412.7,199.1 L 412.4,202.0 L 409.3,202.1 L 408.3,206.5 L 406.3,206.0 L 406.8,208.3 L 405.2,208.4 L 403.7,205.3 L 405.9,202.8 L 405.7,200.3 L 403.7,199.3 L 400.5,192.2 L 400.9,190.3 L 402.6,190.0 L 402.3,187.9 L 394.8,186.4 L 395.5,184.0 L 392.2,184.0 L 391.4,182.2 L 392.2,180.1 L 395.3,179.5 L 392.6,177.5 L 388.4,179.9 L 388.8,184.9 L 386.9,187.0 L 380.6,188.6 L 375.5,192.2 L 372.5,198.3 L 373.8,202.5 L 380.0,204.2 L 387.5,208.8 L 390.5,212.1 L 384.9,210.1 L 380.2,210.6 L 380.7,216.4 L 383.0,221.0 L 381.5,230.1 L 377.4,230.4 L 371.0,226.0 L 367.8,230.1 L 363.3,227.7 L 358.8,230.1 L 357.8,231.9 L 357.5,242.1 L 361.6,246.9 L 360.8,251.5 L 353.9,256.0 L 366.5,269.9 L 372.5,283.2 L 369.1,293.2 L 374.0,299.9 L 377.0,296.4 L 382.1,294.5 L 388.4,294.7 L 392.2,296.4 L 397.3,292.5 L 401.4,291.9 L 404.2,293.6 L 403.8,295.8 L 409.1,293.8 L 411.4,297.3 L 419.9,294.9 L 420.6,296.4 L 423.4,295.5 L 424.9,296.8 L 427.0,296.7 L 430.7,293.8 L 436.4,296.9 L 446.0,299.3 L 443.2,296.9 L 443.2,292.8 L 446.7,295.0 L 447.4,292.9 L 451.5,294.3 L 454.1,293.8 L 453.9,294.9 L 455.2,295.4 L 457.5,294.8 L 459.6,292.4 L 463.5,298.0 L 466.5,299.7 L 469.9,298.8 L 471.6,302.0 L 474.0,303.3 L 482.6,306.9 L 487.3,305.2 L 496.6,308.8 L 498.8,312.6 L 500.6,313.2 L 502.4,318.5 L 513.2,322.5 L 511.8,326.6 L 513.0,328.8 L 511.1,332.9 L 512.4,333.7 L 511.9,335.8 L 514.2,340.8 L 513.4,344.7 L 510.6,346.2 L 508.3,345.8 L 508.4,348.3 L 506.9,348.7 L 507.6,349.9 L 502.5,350.9 L 501.1,353.0 L 499.6,353.0 L 500.6,355.8 L 499.3,356.8 L 495.7,356.7 L 494.6,355.5 L 493.9,356.2 L 494.0,358.4 L 497.0,360.3 L 497.0,362.1 L 500.0,362.1 L 500.5,363.3 L 499.0,365.3 L 497.9,371.5 L 495.9,372.6 L 494.5,370.8 L 492.5,371.0 L 489.9,372.5 L 490.5,375.3 L 488.4,375.1 L 488.8,376.9 L 487.9,377.2 L 485.4,377.4 L 485.2,376.3 L 484.5,377.3 L 484.4,373.8 L 481.9,374.5 L 482.0,376.2 L 479.6,374.4 L 478.5,375.5 L 475.9,373.8 L 473.8,376.8 L 474.6,380.8 L 475.8,380.7 L 476.7,382.3 L 478.9,381.7 L 479.8,383.5 L 481.7,384.0 L 481.5,386.0 L 483.8,385.8 L 484.7,392.2 L 489.9,392.4 L 490.1,393.9 L 492.0,392.2 L 491.2,394.6 L 493.9,396.4 L 493.1,397.6 L 495.0,401.5 L 497.5,400.9 L 498.2,402.0 L 498.4,400.4 L 499.3,401.0 L 502.9,397.9 L 504.0,400.0 L 505.7,398.2 L 508.4,397.6 L 508.9,396.0 L 510.5,396.2 L 511.6,398.6 L 514.0,398.6 L 512.7,400.5 L 513.6,402.7 L 512.4,406.5 L 514.2,408.0 L 512.1,407.6 L 511.8,410.1 L 510.6,409.1 L 507.7,409.5 L 509.0,411.6 L 511.0,411.8 L 506.3,426.0 L 499.0,425.2 L 496.2,427.7 L 498.8,430.3 L 503.2,430.4 L 505.1,428.1 L 511.3,427.8 L 512.2,426.2 L 514.1,426.6 L 515.4,425.3 L 515.7,428.1 L 521.1,435.0 L 521.4,436.9 Z" fill="#0f111a" opacity="0.9" /><path d="M 245.7,729.9 L 258.2,736.1 L 260.8,741.4 L 269.5,745.8 L 278.4,758.1 L 289.7,764.6 L 289.3,775.5 L 292.5,776.5 L 294.2,778.6 L 291.7,782.5 L 291.2,789.0 L 289.3,791.8 L 289.4,800.0 L 292.3,801.3 L 302.2,799.2 L 305.7,801.0 L 313.7,801.4 L 321.2,808.9 L 319.6,816.0 L 321.8,822.9 L 320.0,823.5 L 326.5,828.3 L 331.1,839.2 L 342.9,845.7 L 344.1,850.3 L 339.3,853.0 L 337.0,856.6 L 329.7,857.9 L 325.5,862.2 L 323.9,867.1 L 322.9,880.0 L 335.1,878.4 L 356.7,873.1 L 356.8,872.1 L 360.6,871.0 L 371.3,870.3 L 392.2,857.6 L 430.2,854.9 L 441.5,845.3 L 440.9,844.4 L 441.9,843.3 L 455.9,838.9 L 458.2,836.1 L 472.8,832.6 L 477.6,827.1 L 477.4,825.6 L 483.4,822.5 L 487.9,818.4 L 481.3,807.3 L 482.0,802.5 L 473.0,791.8 L 473.6,788.1 L 472.7,785.2 L 465.2,782.0 L 452.3,782.8 L 391.1,778.9 L 387.6,777.3 L 374.6,761.1 L 370.9,759.4 L 368.5,759.3 L 368.4,762.5 L 366.6,760.9 L 363.5,763.3 L 362.0,762.7 L 362.1,757.7 L 357.2,759.8 L 358.3,757.7 L 356.7,758.7 L 356.5,756.9 L 352.7,756.5 L 352.2,759.1 L 347.0,758.5 L 346.8,761.0 L 345.7,759.9 L 344.5,761.2 L 342.5,760.5 L 341.9,762.3 L 340.6,760.6 L 341.2,766.5 L 339.1,767.5 L 338.2,765.8 L 335.7,765.7 L 335.7,764.5 L 332.9,764.7 L 333.5,762.7 L 336.1,762.6 L 335.1,762.0 L 335.9,760.9 L 333.6,759.3 L 333.0,757.4 L 331.6,758.1 L 331.7,756.0 L 329.5,756.1 L 330.6,757.3 L 327.7,758.1 L 325.9,756.9 L 324.5,758.8 L 323.5,756.6 L 322.9,758.1 L 316.9,756.8 L 312.5,759.2 L 309.7,757.0 L 309.6,755.4 L 307.4,757.3 L 307.1,753.8 L 305.1,754.1 L 304.1,752.0 L 301.3,752.8 L 299.7,751.7 L 298.9,753.0 L 298.2,750.2 L 295.9,749.2 L 295.2,747.6 L 294.4,748.6 L 293.4,746.8 L 294.6,744.0 L 293.7,743.1 L 292.0,743.9 L 291.6,742.6 L 289.6,742.8 L 287.9,745.6 L 285.9,746.2 L 283.2,743.3 L 282.1,745.4 L 279.3,746.1 L 277.7,742.8 L 274.3,739.8 L 274.0,740.9 L 272.8,739.7 L 271.7,740.4 L 270.9,735.0 L 269.1,733.5 L 270.2,731.6 L 268.8,730.6 L 265.5,732.3 L 265.5,730.1 L 264.1,729.4 L 264.6,724.3 L 261.9,720.5 L 264.5,716.9 L 264.2,712.7 L 266.6,710.4 L 265.3,706.8 L 259.5,707.0 L 250.4,714.6 L 243.3,718.2 L 243.8,727.1 L 245.7,729.9 Z" fill="#0f111a" opacity="0.9" /><path d="M 256.9,708.8 L 259.5,707.0 L 264.9,706.7 L 266.1,707.6 L 266.6,710.4 L 264.2,712.7 L 264.5,716.9 L 261.9,720.5 L 264.6,724.3 L 264.1,729.4 L 265.5,730.1 L 265.5,732.3 L 268.8,730.6 L 270.2,731.6 L 269.1,733.5 L 270.9,735.0 L 271.7,740.4 L 272.8,739.7 L 274.0,740.9 L 274.3,739.8 L 277.7,742.8 L 279.3,746.1 L 282.1,745.4 L 283.2,743.3 L 285.9,746.2 L 287.9,745.6 L 289.6,742.8 L 291.6,742.6 L 292.0,743.9 L 293.7,743.1 L 294.6,744.0 L 293.4,746.8 L 294.4,748.6 L 295.2,747.6 L 295.9,749.2 L 298.2,750.2 L 298.9,753.0 L 299.7,751.7 L 301.3,752.8 L 304.1,752.0 L 305.1,754.1 L 307.1,753.8 L 307.4,757.3 L 309.6,755.4 L 309.7,757.0 L 312.5,759.2 L 316.9,756.8 L 322.9,758.1 L 323.5,756.6 L 324.5,758.8 L 325.9,756.9 L 327.7,758.1 L 330.6,757.3 L 329.5,756.1 L 331.7,756.0 L 331.6,758.1 L 333.0,757.4 L 333.6,759.3 L 335.9,760.9 L 335.1,762.0 L 336.1,762.6 L 333.5,762.7 L 332.9,764.7 L 335.7,764.5 L 335.7,765.7 L 338.2,765.8 L 339.1,767.5 L 341.2,766.5 L 340.6,760.6 L 341.9,762.3 L 342.5,760.5 L 344.5,761.2 L 345.7,759.9 L 346.8,761.0 L 347.0,758.5 L 352.2,759.1 L 352.7,756.5 L 356.5,756.9 L 356.7,758.7 L 358.3,757.7 L 357.2,759.8 L 362.1,757.7 L 362.1,762.9 L 364.3,763.0 L 366.6,760.9 L 368.4,762.5 L 368.5,759.3 L 370.9,759.4 L 371.0,756.9 L 373.1,756.8 L 374.2,755.5 L 373.4,755.0 L 373.8,750.9 L 370.4,748.9 L 371.1,746.2 L 373.2,745.0 L 372.5,742.4 L 375.7,740.4 L 374.9,738.3 L 377.0,736.5 L 376.2,735.1 L 378.6,734.4 L 377.8,732.2 L 375.9,732.2 L 377.9,730.9 L 376.5,729.4 L 379.7,727.4 L 378.8,724.6 L 379.7,723.0 L 378.3,722.3 L 377.9,720.0 L 383.5,715.3 L 386.1,702.7 L 388.9,700.0 L 394.2,688.5 L 401.9,683.3 L 403.3,680.0 L 410.3,673.6 L 412.4,668.6 L 416.8,666.6 L 418.1,663.8 L 415.0,658.1 L 412.0,656.0 L 409.4,651.9 L 409.2,647.5 L 413.7,643.3 L 427.3,641.8 L 432.6,638.1 L 456.0,603.7 L 477.6,602.5 L 483.1,598.0 L 494.2,594.9 L 496.3,590.3 L 501.6,586.0 L 513.5,585.7 L 511.2,582.0 L 508.3,583.2 L 507.8,580.5 L 505.8,579.7 L 505.1,574.4 L 501.4,572.0 L 499.6,572.9 L 498.4,571.8 L 496.4,572.4 L 495.8,571.1 L 492.5,570.2 L 491.4,568.2 L 486.7,568.3 L 482.5,566.2 L 467.0,562.4 L 459.1,563.2 L 451.5,552.0 L 452.0,542.2 L 448.4,538.5 L 447.4,539.1 L 446.7,536.8 L 445.1,538.6 L 439.8,539.7 L 438.7,541.3 L 436.0,541.3 L 433.6,542.8 L 430.5,542.1 L 430.4,541.0 L 428.7,542.6 L 427.7,540.8 L 425.6,541.2 L 425.4,540.0 L 422.8,540.5 L 422.4,539.7 L 419.2,540.2 L 418.8,541.9 L 414.4,542.0 L 410.6,539.3 L 408.0,539.8 L 406.4,537.8 L 404.3,538.6 L 400.8,537.9 L 394.8,542.3 L 393.1,545.5 L 373.3,548.3 L 366.8,547.3 L 362.9,544.9 L 360.9,530.1 L 356.7,526.0 L 350.5,526.4 L 342.5,538.4 L 331.9,545.0 L 332.2,557.7 L 325.5,557.0 L 322.7,559.5 L 321.6,562.6 L 317.1,565.0 L 313.3,565.2 L 309.8,563.4 L 301.0,564.9 L 297.4,561.1 L 291.5,557.8 L 289.9,546.9 L 278.6,549.4 L 273.0,547.0 L 263.5,548.7 L 252.7,547.4 L 246.5,561.0 L 247.4,564.2 L 255.2,564.3 L 259.0,568.6 L 269.1,568.4 L 272.9,571.3 L 276.1,577.1 L 286.9,579.8 L 287.8,580.5 L 286.4,583.5 L 285.6,591.3 L 282.1,593.1 L 277.6,593.1 L 266.2,604.0 L 260.7,607.2 L 251.8,606.4 L 245.6,609.2 L 241.5,607.9 L 239.2,603.0 L 235.9,603.1 L 231.5,606.7 L 228.7,614.2 L 221.9,625.3 L 216.5,628.9 L 214.7,632.9 L 229.0,632.5 L 232.8,634.1 L 233.7,636.5 L 228.6,638.8 L 226.7,651.1 L 221.1,652.5 L 219.5,656.3 L 215.4,657.1 L 209.9,654.5 L 210.3,662.8 L 215.9,673.8 L 215.1,675.6 L 213.1,676.4 L 213.2,678.8 L 212.0,678.6 L 209.3,681.0 L 209.5,683.0 L 207.0,684.9 L 206.6,687.1 L 207.5,687.2 L 206.1,688.6 L 209.7,692.0 L 208.3,699.7 L 211.6,700.2 L 217.6,695.9 L 217.7,690.1 L 220.4,679.8 L 222.7,678.9 L 230.6,682.8 L 232.6,681.4 L 236.2,685.0 L 234.5,694.2 L 237.5,701.3 L 240.7,698.9 L 246.2,699.2 L 251.5,707.9 L 256.9,708.8 Z" fill="#0f111a" opacity="0.9" /><path d="M 148.8,633.3 L 152.2,627.3 L 152.8,618.0 L 157.3,614.8 L 158.6,610.7 L 163.3,607.7 L 164.6,602.3 L 167.6,598.6 L 172.7,597.8 L 173.4,593.2 L 179.6,593.0 L 185.8,586.8 L 187.9,586.8 L 189.0,582.4 L 192.8,580.1 L 200.7,579.9 L 200.1,593.4 L 210.6,594.5 L 213.8,597.3 L 210.4,598.9 L 217.9,597.5 L 220.4,594.1 L 224.1,595.4 L 224.9,598.4 L 229.2,595.4 L 227.9,590.9 L 223.0,586.1 L 221.1,580.3 L 230.9,578.8 L 233.1,573.0 L 239.5,568.2 L 245.2,567.5 L 247.4,564.2 L 246.5,561.0 L 249.1,553.5 L 252.7,547.4 L 253.9,547.5 L 250.8,539.3 L 255.7,531.3 L 258.1,523.3 L 263.0,520.4 L 259.4,516.4 L 258.1,510.1 L 260.2,506.4 L 265.3,506.4 L 272.2,502.3 L 281.8,501.6 L 280.7,493.0 L 272.2,481.5 L 268.6,480.2 L 265.8,481.8 L 262.6,480.9 L 265.3,474.2 L 259.1,465.5 L 255.7,458.0 L 245.3,450.3 L 244.6,445.2 L 239.6,442.5 L 238.4,441.1 L 238.7,438.6 L 236.6,438.1 L 234.7,435.6 L 228.6,433.8 L 225.2,420.3 L 217.3,409.1 L 216.8,404.9 L 218.4,403.1 L 216.8,399.3 L 210.8,395.6 L 210.4,388.9 L 208.4,386.5 L 204.0,384.9 L 199.4,376.0 L 194.2,372.9 L 193.2,369.9 L 187.5,366.6 L 199.7,432.8 L 200.0,445.9 L 192.0,454.2 L 184.3,453.3 L 181.7,457.2 L 181.9,460.4 L 185.7,466.3 L 184.8,470.0 L 174.5,471.3 L 166.6,481.2 L 165.3,484.7 L 162.4,481.1 L 160.6,480.7 L 159.3,492.9 L 153.8,498.7 L 154.4,500.7 L 151.5,503.7 L 151.3,506.5 L 141.3,527.2 L 140.2,543.2 L 134.8,565.5 L 134.1,574.0 L 137.2,578.4 L 127.4,591.5 L 126.3,601.1 L 118.7,603.7 L 115.7,608.7 L 118.3,617.0 L 120.6,620.0 L 124.5,621.0 L 133.9,618.6 L 137.1,619.2 L 143.8,624.1 L 148.8,633.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 565.9,586.3 L 562.1,605.1 L 565.6,629.9 L 568.3,638.1 L 569.8,648.0 L 568.5,652.1 L 556.8,661.3 L 551.2,671.3 L 551.2,675.0 L 554.3,679.1 L 557.9,689.7 L 554.2,699.3 L 548.9,706.2 L 549.3,709.3 L 552.8,711.1 L 554.3,715.8 L 555.4,716.2 L 551.4,724.5 L 551.6,726.1 L 559.8,732.6 L 564.9,734.8 L 576.9,733.3 L 579.6,735.2 L 581.2,739.2 L 580.2,747.3 L 585.2,748.9 L 584.3,756.1 L 585.6,756.7 L 597.0,751.7 L 611.9,748.5 L 626.9,766.3 L 625.7,771.3 L 627.0,776.2 L 629.1,777.5 L 636.0,778.8 L 651.0,778.8 L 666.1,777.1 L 669.5,775.7 L 675.6,768.4 L 677.1,763.4 L 676.8,758.0 L 679.8,752.8 L 688.9,741.2 L 697.8,735.3 L 705.0,732.3 L 704.9,724.5 L 689.0,724.4 L 689.0,722.0 L 686.3,717.1 L 681.0,712.6 L 678.6,707.0 L 678.1,701.7 L 663.0,702.0 L 658.8,695.6 L 655.2,693.3 L 648.8,691.7 L 647.9,688.4 L 643.4,686.9 L 642.7,681.5 L 640.2,680.5 L 638.3,675.3 L 643.1,670.6 L 642.3,667.7 L 640.5,666.4 L 635.5,666.8 L 633.0,663.7 L 632.0,665.0 L 630.8,664.5 L 627.8,659.9 L 629.8,655.4 L 628.6,648.8 L 629.6,646.4 L 625.5,644.4 L 624.2,645.5 L 620.4,643.5 L 618.3,639.8 L 620.2,637.2 L 615.9,633.6 L 614.7,634.8 L 613.7,634.3 L 613.6,630.4 L 615.2,629.4 L 614.1,627.8 L 617.3,626.2 L 618.3,621.7 L 612.4,618.6 L 612.2,614.8 L 616.1,612.0 L 622.3,610.5 L 623.5,607.8 L 622.6,602.3 L 624.9,597.3 L 621.8,595.3 L 623.6,589.8 L 625.2,589.3 L 625.2,586.0 L 626.3,585.9 L 627.6,582.6 L 628.9,574.9 L 623.8,574.4 L 619.4,571.5 L 619.1,574.3 L 617.9,574.2 L 616.5,576.2 L 615.1,580.1 L 611.9,580.7 L 611.6,578.1 L 613.9,577.0 L 615.0,573.8 L 614.1,569.2 L 612.4,570.2 L 610.0,569.7 L 608.1,571.2 L 602.3,570.2 L 602.2,573.0 L 599.3,572.5 L 594.5,579.9 L 591.7,589.8 L 589.8,592.9 L 587.2,593.0 L 585.8,598.7 L 571.6,597.3 L 574.1,593.5 L 573.1,588.0 L 565.9,586.3 Z" fill="#0f111a" opacity="0.9" /><path d="M 333.5,190.2 L 317.0,176.5 L 323.1,161.4 L 332.8,147.2 L 341.6,141.0 L 344.3,136.4 L 340.4,134.1 L 340.9,131.1 L 342.9,130.1 L 341.7,127.8 L 337.8,127.0 L 337.0,125.3 L 333.4,123.9 L 333.5,121.6 L 332.0,120.2 L 328.5,120.5 L 326.7,118.7 L 325.8,109.5 L 327.9,98.7 L 331.2,98.4 L 332.6,94.4 L 335.9,93.7 L 336.6,87.2 L 338.7,87.1 L 339.6,85.2 L 341.5,84.5 L 343.0,68.8 L 345.6,64.2 L 345.9,61.0 L 320.0,61.0 L 319.9,65.9 L 309.7,66.0 L 309.6,67.6 L 301.6,67.7 L 301.6,66.1 L 230.1,64.7 L 199.0,65.3 L 153.4,63.0 L 152.5,68.4 L 155.4,71.9 L 155.7,74.2 L 152.9,78.5 L 149.3,79.3 L 149.3,83.4 L 147.2,87.0 L 145.0,96.1 L 139.2,101.9 L 139.0,103.8 L 142.9,105.0 L 143.6,106.8 L 138.7,113.6 L 138.7,116.3 L 144.3,125.9 L 144.4,129.2 L 147.8,133.6 L 149.0,138.5 L 153.4,140.9 L 161.7,142.9 L 160.8,144.8 L 153.7,147.6 L 151.1,157.1 L 153.5,160.2 L 163.1,164.7 L 164.0,167.0 L 163.4,169.7 L 158.5,173.5 L 158.3,175.0 L 159.8,181.4 L 159.0,184.5 L 160.5,197.0 L 165.8,206.0 L 163.1,212.3 L 163.3,216.5 L 168.1,223.7 L 167.3,226.0 L 163.7,228.0 L 159.4,235.3 L 160.4,238.0 L 160.0,242.6 L 163.8,244.3 L 164.9,248.7 L 186.0,245.6 L 189.0,239.9 L 193.3,239.7 L 197.4,232.5 L 205.3,230.6 L 214.0,230.7 L 229.7,226.8 L 238.8,227.5 L 246.1,235.4 L 256.8,243.0 L 268.8,236.9 L 272.6,238.2 L 277.1,244.6 L 279.3,250.4 L 283.5,250.4 L 290.7,246.9 L 304.4,243.2 L 309.5,239.3 L 311.2,236.2 L 312.3,229.7 L 309.2,218.4 L 311.8,216.2 L 312.3,210.7 L 310.6,204.8 L 313.0,199.5 L 320.3,199.2 L 320.3,196.0 L 318.6,192.6 L 328.5,193.5 L 333.5,190.2 Z" fill="#0f111a" opacity="0.9" /><path d="M 528.7,103.1 L 531.0,102.3 L 531.0,95.4 L 538.0,88.4 L 536.9,83.8 L 539.9,75.6 L 540.1,68.9 L 545.5,66.8 L 544.2,55.3 L 538.9,48.4 L 535.6,49.4 L 533.4,52.2 L 527.9,52.1 L 523.6,49.4 L 521.4,49.4 L 522.3,44.8 L 503.3,40.0 L 501.4,43.6 L 503.8,45.1 L 503.7,46.9 L 495.4,49.0 L 494.5,51.9 L 492.4,52.5 L 491.8,54.4 L 489.6,49.4 L 490.0,47.1 L 485.9,46.5 L 482.5,48.3 L 480.8,55.0 L 481.0,59.8 L 477.8,60.1 L 476.0,59.0 L 475.3,60.7 L 472.3,61.5 L 471.1,65.5 L 468.8,63.9 L 466.4,67.4 L 465.3,65.3 L 463.2,65.0 L 460.8,69.3 L 459.8,74.4 L 456.4,76.1 L 455.3,72.3 L 452.3,70.8 L 451.7,68.9 L 449.2,69.6 L 451.5,65.6 L 449.0,65.2 L 447.8,66.3 L 447.4,64.3 L 430.5,64.2 L 430.5,63.1 L 427.8,63.0 L 427.6,64.2 L 424.0,64.2 L 422.0,68.5 L 418.7,69.1 L 419.4,66.6 L 415.6,66.6 L 415.6,63.6 L 388.5,63.4 L 388.0,65.9 L 351.8,64.4 L 350.1,62.3 L 345.9,61.0 L 345.6,64.2 L 343.0,68.8 L 341.5,84.5 L 339.6,85.2 L 338.7,87.1 L 336.6,87.2 L 335.9,93.7 L 332.6,94.4 L 331.2,98.4 L 327.9,98.7 L 325.8,109.5 L 326.7,118.7 L 328.5,120.5 L 332.0,120.2 L 333.5,121.6 L 333.4,123.9 L 337.0,125.3 L 337.8,127.0 L 341.7,127.8 L 342.9,130.1 L 340.9,131.1 L 340.4,134.1 L 343.2,136.5 L 345.8,137.3 L 349.1,134.3 L 352.9,137.1 L 352.2,138.3 L 354.6,141.2 L 353.1,142.4 L 354.4,142.9 L 353.9,144.4 L 351.5,145.2 L 352.0,147.3 L 356.5,149.8 L 356.5,153.1 L 359.5,154.0 L 359.5,155.3 L 365.1,156.7 L 367.4,156.1 L 367.1,157.9 L 369.2,159.2 L 369.2,162.1 L 370.3,163.1 L 371.4,161.5 L 373.1,161.5 L 372.4,160.7 L 374.6,159.1 L 375.6,155.8 L 372.5,150.4 L 373.3,147.2 L 379.2,145.1 L 386.0,138.0 L 396.6,132.6 L 398.5,129.5 L 402.8,127.2 L 416.0,128.5 L 416.6,129.7 L 413.0,134.2 L 414.8,134.7 L 425.1,123.8 L 426.4,118.4 L 429.1,118.2 L 432.0,115.9 L 432.7,111.5 L 437.7,113.2 L 441.5,111.5 L 443.6,113.1 L 444.3,115.8 L 442.8,116.4 L 442.7,117.9 L 445.9,120.0 L 447.1,123.1 L 443.0,127.3 L 443.1,128.5 L 444.4,129.2 L 446.6,126.5 L 447.0,128.1 L 448.7,128.9 L 453.5,124.7 L 461.4,125.7 L 464.9,122.5 L 470.3,124.1 L 471.5,123.1 L 472.7,115.4 L 476.9,116.2 L 480.3,118.7 L 487.0,117.5 L 490.8,115.3 L 498.5,115.5 L 502.3,113.2 L 505.3,114.3 L 509.5,111.0 L 524.2,106.9 L 528.7,103.1 Z" fill="#0f111a" opacity="0.9" /><path d="M 472.7,785.2 L 473.6,788.1 L 473.0,791.8 L 482.0,802.5 L 481.3,807.3 L 487.9,818.4 L 495.6,815.2 L 512.3,811.5 L 513.0,810.3 L 527.0,805.7 L 532.7,801.4 L 542.4,799.3 L 543.6,798.6 L 541.6,798.9 L 542.9,797.1 L 543.2,798.4 L 543.8,797.6 L 543.1,796.3 L 543.4,797.1 L 550.2,789.4 L 557.9,787.7 L 565.4,782.9 L 571.0,782.2 L 578.9,778.5 L 601.3,776.4 L 632.8,778.5 L 627.0,776.2 L 625.7,771.3 L 626.9,766.3 L 611.9,748.5 L 597.0,751.7 L 585.6,756.7 L 584.3,756.1 L 585.2,748.9 L 578.8,746.5 L 575.4,743.1 L 572.3,742.8 L 563.4,744.2 L 552.9,749.8 L 548.1,750.3 L 547.9,755.4 L 539.9,761.7 L 533.7,764.9 L 528.5,769.9 L 522.1,768.6 L 519.6,772.7 L 511.0,777.2 L 503.8,779.2 L 498.2,777.1 L 495.2,781.2 L 488.5,782.7 L 482.0,776.1 L 477.8,776.3 L 476.3,777.4 L 472.7,785.2 Z" fill="#0f111a" opacity="0.9" />
            </g>

            <!-- 16 Tactical Region Polygons -->
            <g id="tacRegionsPolygonsGroup">
              
            <path id="tac-path-western-north"
                  data-name="Western North"
                  d="M 189.2,781.8 L 190.7,776.0 L 194.5,778.6 L 199.9,779.9 L 202.9,773.2 L 199.3,772.3 L 198.8,769.5 L 198.4,763.9 L 200.7,757.2 L 193.9,754.2 L 196.5,744.1 L 199.9,737.4 L 204.4,733.6 L 214.6,736.3 L 219.8,735.1 L 231.8,735.9 L 236.0,729.1 L 245.7,729.9 L 243.8,727.1 L 243.3,718.2 L 250.4,714.6 L 256.9,708.8 L 251.5,707.9 L 246.2,699.2 L 240.7,698.9 L 237.5,701.3 L 234.5,694.2 L 236.2,685.0 L 232.6,681.4 L 230.6,682.8 L 222.7,678.9 L 220.4,679.8 L 219.6,681.6 L 217.6,695.9 L 211.6,700.2 L 208.3,699.7 L 209.4,694.5 L 195.1,693.6 L 191.9,683.0 L 189.0,680.4 L 181.3,677.0 L 178.0,669.4 L 175.4,668.1 L 167.7,668.3 L 158.0,662.8 L 152.6,661.4 L 151.3,656.6 L 151.0,640.4 L 148.8,633.3 L 143.8,624.1 L 137.1,619.2 L 133.9,618.6 L 124.5,621.0 L 120.6,620.0 L 118.3,617.0 L 115.7,608.7 L 99.0,635.2 L 98.4,641.3 L 101.3,643.7 L 102.1,647.6 L 99.1,651.8 L 98.6,656.3 L 96.8,657.4 L 95.0,662.4 L 98.8,665.4 L 98.4,673.6 L 107.4,707.0 L 106.7,713.0 L 109.6,713.2 L 116.6,726.7 L 120.6,749.7 L 128.0,767.2 L 127.1,787.4 L 137.0,786.4 L 135.3,796.7 L 137.8,797.2 L 139.8,799.6 L 146.2,797.9 L 150.1,794.5 L 150.0,795.4 L 155.2,798.6 L 156.9,798.5 L 157.5,799.9 L 159.5,798.5 L 161.5,800.1 L 161.3,801.7 L 163.3,802.3 L 162.8,810.9 L 165.0,824.5 L 168.4,831.3 L 168.2,837.7 L 174.7,837.3 L 179.8,835.5 L 183.9,830.0 L 188.6,819.6 L 188.6,815.3 L 186.8,813.4 L 191.1,811.9 L 191.5,804.2 L 190.5,799.4 L 193.1,796.6 L 192.8,790.2 L 189.4,786.7 L 189.2,781.8 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Western North', true)"
                  onmouseenter="onTacRegionHover(event, 'Western North')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-ahafo"
                  data-name="Ahafo"
                  d="M 209.4,694.5 L 209.7,692.0 L 206.1,688.6 L 207.5,687.2 L 206.6,687.1 L 207.0,684.9 L 209.5,683.0 L 209.9,680.4 L 213.2,678.8 L 213.1,676.4 L 215.1,675.6 L 215.9,673.8 L 210.3,662.8 L 209.9,654.5 L 215.4,657.1 L 219.5,656.3 L 221.1,652.5 L 226.7,651.1 L 228.6,638.8 L 233.7,636.5 L 232.8,634.1 L 229.0,632.5 L 214.7,632.9 L 214.9,631.1 L 221.9,625.3 L 228.7,614.2 L 231.5,606.7 L 235.9,603.1 L 239.2,603.0 L 241.5,607.9 L 245.6,609.2 L 251.8,606.4 L 260.7,607.2 L 266.2,604.0 L 277.6,593.1 L 282.1,593.1 L 285.6,591.3 L 286.4,583.5 L 287.8,580.5 L 276.1,577.1 L 272.9,571.3 L 269.1,568.4 L 259.0,568.6 L 255.2,564.3 L 247.4,564.2 L 245.2,567.5 L 239.5,568.2 L 233.1,573.0 L 230.9,578.8 L 221.1,580.3 L 223.0,586.1 L 227.9,590.9 L 229.2,595.4 L 224.9,598.4 L 224.1,595.4 L 220.4,594.1 L 217.9,597.5 L 210.4,598.9 L 213.8,597.3 L 210.6,594.5 L 200.1,593.4 L 200.7,579.9 L 192.8,580.1 L 189.0,582.4 L 187.9,586.8 L 185.8,586.8 L 179.6,593.0 L 173.4,593.2 L 172.7,597.8 L 167.6,598.6 L 164.6,602.3 L 163.3,607.7 L 158.6,610.7 L 157.3,614.8 L 152.8,618.0 L 152.2,627.3 L 148.8,633.3 L 151.6,644.5 L 151.3,656.6 L 152.6,661.4 L 158.0,662.8 L 167.7,668.3 L 175.4,668.1 L 178.0,669.4 L 181.3,677.0 L 189.0,680.4 L 191.9,683.0 L 195.1,693.6 L 209.4,694.5 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Ahafo', true)"
                  onmouseenter="onTacRegionHover(event, 'Ahafo')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-bono-east"
                  data-name="Bono East"
                  d="M 522.3,498.1 L 521.0,487.4 L 522.5,481.2 L 521.6,479.8 L 517.2,480.0 L 521.2,473.4 L 521.6,471.7 L 520.0,469.8 L 517.2,470.8 L 511.6,469.9 L 508.0,467.3 L 506.9,463.6 L 497.3,456.3 L 488.3,453.2 L 480.2,452.6 L 466.7,448.5 L 453.5,437.3 L 439.6,428.2 L 431.3,427.7 L 423.6,429.2 L 424.3,431.0 L 423.1,436.9 L 417.2,439.2 L 409.8,439.8 L 407.1,443.1 L 405.6,440.5 L 402.8,440.2 L 401.2,438.5 L 398.8,440.9 L 398.6,451.4 L 394.8,456.5 L 398.3,461.3 L 397.6,465.6 L 399.6,466.6 L 401.9,471.7 L 397.0,470.1 L 393.1,471.7 L 389.6,475.2 L 382.6,474.2 L 381.5,465.9 L 382.8,462.0 L 371.8,443.5 L 367.7,438.9 L 365.8,431.9 L 369.6,422.3 L 368.6,418.4 L 369.5,416.1 L 368.2,414.7 L 368.8,411.9 L 370.7,409.8 L 373.9,408.8 L 374.3,407.0 L 377.6,406.6 L 377.4,404.2 L 379.7,403.3 L 380.4,404.7 L 381.7,404.1 L 380.6,401.0 L 384.3,398.8 L 384.8,394.5 L 383.5,386.5 L 386.0,381.0 L 380.2,380.7 L 375.2,382.6 L 368.3,379.7 L 364.9,375.6 L 361.9,375.3 L 355.8,370.4 L 348.1,368.2 L 337.9,370.2 L 335.9,373.1 L 334.5,379.5 L 332.0,381.0 L 320.5,381.7 L 314.4,379.1 L 311.3,380.7 L 309.4,386.2 L 304.1,388.4 L 300.9,387.9 L 292.5,383.5 L 287.3,384.7 L 285.1,387.1 L 282.6,393.8 L 281.6,401.3 L 283.5,409.3 L 279.8,414.3 L 280.1,419.8 L 285.3,428.6 L 295.5,427.8 L 298.9,430.0 L 302.1,436.3 L 301.8,438.8 L 298.8,442.3 L 295.9,443.4 L 289.0,439.7 L 281.7,438.4 L 275.2,449.0 L 272.7,451.1 L 261.6,453.9 L 253.0,452.5 L 250.8,453.5 L 255.7,458.0 L 259.1,465.5 L 265.3,474.2 L 262.6,480.9 L 265.8,481.8 L 268.6,480.2 L 272.2,481.5 L 280.7,493.0 L 282.0,498.4 L 281.8,501.6 L 272.2,502.3 L 265.3,506.4 L 260.2,506.4 L 258.1,510.1 L 259.4,516.4 L 263.0,520.4 L 258.1,523.3 L 255.7,531.3 L 250.8,539.3 L 251.0,541.9 L 253.9,547.5 L 263.5,548.7 L 273.0,547.0 L 278.6,549.4 L 289.9,546.9 L 291.5,557.8 L 297.4,561.1 L 301.0,564.9 L 309.8,563.4 L 313.3,565.2 L 317.1,565.0 L 321.6,562.6 L 322.7,559.5 L 325.5,557.0 L 332.2,557.7 L 331.9,545.0 L 342.5,538.4 L 350.5,526.4 L 356.7,526.0 L 360.9,530.1 L 362.9,544.9 L 366.8,547.3 L 373.3,548.3 L 393.1,545.5 L 398.6,538.7 L 402.2,537.7 L 403.9,538.6 L 406.4,537.8 L 408.0,539.8 L 410.6,539.3 L 414.4,542.0 L 418.8,541.9 L 419.2,540.2 L 422.4,539.7 L 422.8,540.5 L 425.4,540.0 L 425.6,541.2 L 427.7,540.8 L 428.7,542.6 L 430.4,541.0 L 430.5,542.1 L 433.6,542.8 L 436.0,541.3 L 438.7,541.3 L 439.8,539.7 L 445.1,538.6 L 446.7,536.8 L 447.4,539.1 L 448.4,538.5 L 452.0,542.2 L 451.5,552.0 L 459.1,563.2 L 467.0,562.4 L 482.5,566.2 L 486.7,568.3 L 491.4,568.2 L 492.5,570.2 L 495.8,571.1 L 496.4,572.4 L 498.4,571.8 L 499.6,572.9 L 501.4,572.0 L 505.1,574.4 L 505.8,579.7 L 507.8,580.5 L 508.3,583.2 L 511.2,582.0 L 513.5,585.7 L 529.4,586.4 L 533.7,584.0 L 541.5,584.3 L 547.8,587.3 L 552.0,586.0 L 565.9,586.3 L 570.3,568.5 L 569.4,562.9 L 568.1,560.6 L 553.4,552.2 L 551.5,545.0 L 542.2,531.8 L 538.6,522.2 L 538.8,517.3 L 541.3,512.7 L 541.5,509.4 L 538.9,507.4 L 527.2,508.3 L 523.6,503.1 L 522.3,498.1 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Bono East', true)"
                  onmouseenter="onTacRegionHover(event, 'Bono East')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-savannah"
                  data-name="Savannah"
                  d="M 488.3,453.2 L 492.5,451.4 L 491.9,448.7 L 493.2,446.6 L 488.3,447.5 L 487.9,443.5 L 490.4,442.6 L 497.3,443.1 L 502.5,440.6 L 505.4,444.6 L 510.3,445.0 L 513.8,444.1 L 515.1,442.4 L 517.9,442.5 L 517.7,439.5 L 521.4,436.9 L 521.1,435.0 L 515.7,428.1 L 515.4,425.3 L 514.1,426.6 L 512.2,426.2 L 511.3,427.8 L 505.1,428.1 L 503.2,430.4 L 498.8,430.3 L 496.2,427.7 L 499.0,425.2 L 506.3,426.0 L 511.0,411.8 L 509.0,411.6 L 507.7,409.5 L 510.6,409.1 L 511.8,410.1 L 512.1,407.6 L 514.2,408.0 L 512.4,406.5 L 513.6,402.7 L 512.7,400.5 L 514.0,398.6 L 511.6,398.6 L 510.5,396.2 L 508.9,396.0 L 508.4,397.6 L 505.7,398.2 L 504.0,400.0 L 502.9,397.9 L 499.3,401.0 L 498.4,400.4 L 498.2,402.0 L 497.5,400.9 L 495.0,401.5 L 493.1,397.6 L 493.9,396.4 L 491.2,394.6 L 492.0,392.2 L 490.1,393.9 L 489.9,392.4 L 484.7,392.2 L 483.8,385.8 L 481.5,386.0 L 481.7,384.0 L 479.8,383.5 L 478.9,381.7 L 476.7,382.3 L 475.8,380.7 L 474.6,380.8 L 473.8,376.8 L 475.9,373.8 L 478.5,375.5 L 479.6,374.4 L 482.0,376.2 L 481.9,374.5 L 484.4,373.8 L 484.5,377.3 L 485.2,376.3 L 485.4,377.4 L 487.9,377.2 L 488.8,376.9 L 488.4,375.1 L 490.5,375.3 L 489.9,372.5 L 492.5,371.0 L 494.5,370.8 L 495.9,372.6 L 497.9,371.5 L 499.0,365.3 L 500.5,363.3 L 500.0,362.1 L 497.0,362.1 L 497.0,360.3 L 494.0,358.4 L 493.9,356.2 L 494.6,355.5 L 495.7,356.7 L 499.3,356.8 L 500.6,355.8 L 499.6,353.0 L 501.1,353.0 L 502.5,350.9 L 507.6,349.9 L 506.9,348.7 L 508.4,348.3 L 508.3,345.8 L 510.6,346.2 L 513.4,344.7 L 514.2,340.8 L 511.9,335.8 L 512.4,333.7 L 511.1,332.9 L 513.0,328.8 L 511.8,326.6 L 513.2,322.5 L 502.4,318.5 L 500.6,313.2 L 498.8,312.6 L 496.6,308.8 L 487.3,305.2 L 482.6,306.9 L 474.0,303.3 L 471.6,302.0 L 469.9,298.8 L 466.5,299.7 L 463.5,298.0 L 459.6,292.4 L 457.5,294.8 L 455.2,295.4 L 453.9,294.9 L 454.1,293.8 L 451.5,294.3 L 447.4,292.9 L 446.7,295.0 L 443.2,292.8 L 443.2,296.9 L 446.0,299.3 L 436.4,296.9 L 430.7,293.8 L 427.0,296.7 L 424.9,296.8 L 423.4,295.5 L 420.6,296.4 L 419.9,294.9 L 411.4,297.3 L 409.1,293.8 L 403.8,295.8 L 404.2,293.6 L 401.4,291.9 L 397.3,292.5 L 392.2,296.4 L 388.4,294.7 L 382.1,294.5 L 377.0,296.4 L 374.0,299.9 L 371.7,298.0 L 369.1,293.2 L 372.5,283.2 L 366.5,269.9 L 353.9,256.0 L 360.8,251.5 L 361.6,246.9 L 357.5,242.1 L 357.8,231.9 L 358.8,230.1 L 363.3,227.7 L 367.8,230.1 L 371.0,226.0 L 377.4,230.4 L 381.5,230.1 L 383.0,221.0 L 380.7,216.4 L 380.2,210.6 L 384.9,210.1 L 390.5,212.1 L 387.5,208.8 L 380.0,204.2 L 373.8,202.5 L 372.5,198.3 L 366.7,198.4 L 358.0,192.3 L 342.6,189.4 L 333.5,190.2 L 328.5,193.5 L 318.6,192.6 L 320.3,196.0 L 320.3,199.2 L 313.0,199.5 L 310.6,204.8 L 312.3,210.7 L 311.8,216.2 L 309.2,218.4 L 312.3,229.7 L 311.2,236.2 L 309.5,239.3 L 304.4,243.2 L 290.7,246.9 L 283.5,250.4 L 279.3,250.4 L 277.1,244.6 L 272.6,238.2 L 268.8,236.9 L 256.8,243.0 L 246.1,235.4 L 238.8,227.5 L 229.7,226.8 L 214.0,230.7 L 205.3,230.6 L 197.4,232.5 L 193.3,239.7 L 189.0,239.9 L 186.0,245.6 L 164.9,248.7 L 165.2,250.7 L 161.9,255.3 L 162.0,258.7 L 164.2,262.3 L 170.0,265.9 L 173.8,270.6 L 173.3,278.1 L 175.3,284.4 L 174.0,288.3 L 168.9,291.8 L 168.8,294.5 L 170.4,297.7 L 173.7,298.1 L 177.6,302.2 L 176.6,304.6 L 169.8,309.2 L 166.9,315.7 L 161.6,318.1 L 163.4,325.0 L 160.8,328.8 L 163.3,332.3 L 175.8,334.2 L 177.9,336.3 L 178.3,345.0 L 182.9,348.1 L 182.2,354.0 L 185.8,360.9 L 181.2,365.5 L 183.5,367.1 L 187.0,366.4 L 193.2,369.9 L 194.2,372.9 L 199.4,376.0 L 204.0,384.9 L 208.4,386.5 L 210.4,388.9 L 210.8,395.6 L 216.8,399.3 L 218.4,403.1 L 216.8,404.9 L 217.3,409.1 L 225.2,420.3 L 228.6,433.8 L 234.7,435.6 L 236.6,438.1 L 238.7,438.6 L 238.4,441.1 L 244.6,445.2 L 245.3,450.3 L 248.8,453.2 L 250.4,453.8 L 253.0,452.5 L 261.6,453.9 L 272.7,451.1 L 275.2,449.0 L 281.7,438.4 L 289.0,439.7 L 295.9,443.4 L 298.8,442.3 L 301.8,438.8 L 302.1,436.3 L 298.9,430.0 L 295.5,427.8 L 285.3,428.6 L 280.1,419.8 L 279.8,414.3 L 283.5,409.3 L 281.6,401.3 L 282.6,393.8 L 285.1,387.1 L 287.3,384.7 L 292.5,383.5 L 300.9,387.9 L 304.1,388.4 L 309.4,386.2 L 311.3,380.7 L 314.4,379.1 L 320.5,381.7 L 332.0,381.0 L 334.5,379.5 L 335.9,373.1 L 337.9,370.2 L 348.1,368.2 L 355.8,370.4 L 361.9,375.3 L 364.9,375.6 L 368.3,379.7 L 375.2,382.6 L 380.2,380.7 L 386.0,381.0 L 383.5,386.5 L 384.8,394.5 L 384.3,398.8 L 380.6,401.0 L 381.7,404.1 L 380.4,404.7 L 379.7,403.3 L 377.4,404.2 L 377.6,406.6 L 374.3,407.0 L 373.9,408.8 L 370.7,409.8 L 368.8,411.9 L 368.2,414.7 L 369.5,416.1 L 368.6,418.4 L 369.6,422.3 L 365.8,431.9 L 367.7,438.9 L 371.8,443.5 L 382.8,462.0 L 381.5,465.9 L 382.6,474.2 L 389.6,475.2 L 393.1,471.7 L 397.0,470.1 L 401.9,471.7 L 399.6,466.6 L 397.6,465.6 L 398.3,461.3 L 394.8,456.5 L 398.6,451.4 L 398.8,440.9 L 401.6,438.4 L 402.8,440.2 L 405.6,440.5 L 407.1,443.1 L 409.8,439.8 L 411.2,440.4 L 420.8,438.1 L 423.1,436.9 L 424.3,431.0 L 423.6,429.2 L 427.8,428.2 L 439.6,428.2 L 453.5,437.3 L 466.7,448.5 L 480.2,452.6 L 488.3,453.2 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Savannah', true)"
                  onmouseenter="onTacRegionHover(event, 'Savannah')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-north-east"
                  data-name="North East"
                  d="M 372.5,198.3 L 375.5,192.2 L 380.6,188.6 L 386.9,187.0 L 388.8,184.9 L 388.4,179.9 L 392.6,177.5 L 395.3,179.5 L 392.2,180.1 L 391.4,181.8 L 392.2,184.0 L 395.5,184.0 L 394.8,186.4 L 402.3,187.9 L 402.6,190.0 L 400.9,190.3 L 400.5,192.2 L 403.7,199.3 L 405.7,200.3 L 405.9,202.8 L 403.7,205.3 L 405.2,208.4 L 406.8,208.3 L 406.3,206.0 L 408.3,206.5 L 409.3,202.1 L 412.4,202.0 L 412.7,199.1 L 414.9,200.6 L 415.2,198.9 L 417.0,199.0 L 416.2,199.0 L 416.5,197.4 L 418.5,198.0 L 420.3,192.5 L 422.7,193.0 L 422.0,190.3 L 423.6,188.9 L 422.2,187.6 L 424.9,184.8 L 424.0,183.2 L 426.4,182.6 L 426.5,184.0 L 428.7,184.5 L 430.8,179.5 L 432.2,180.8 L 433.4,179.9 L 433.8,181.3 L 434.2,179.3 L 436.4,178.1 L 440.7,178.8 L 440.9,177.4 L 443.6,178.4 L 442.6,176.3 L 443.5,175.4 L 444.1,176.4 L 447.6,174.7 L 447.9,175.9 L 449.7,176.0 L 451.5,174.3 L 451.6,175.9 L 452.8,174.6 L 452.8,176.4 L 454.2,175.5 L 455.4,178.0 L 457.4,178.2 L 460.0,182.0 L 466.4,176.1 L 468.6,168.1 L 472.8,171.1 L 473.9,168.3 L 472.6,160.5 L 475.6,158.7 L 479.5,162.2 L 489.8,162.8 L 491.0,165.7 L 494.7,166.1 L 499.0,163.9 L 510.7,164.8 L 514.1,166.3 L 525.1,163.5 L 530.2,165.9 L 533.8,163.1 L 538.5,165.2 L 537.9,160.4 L 540.2,158.5 L 548.2,156.7 L 550.1,161.7 L 553.1,164.8 L 554.4,169.4 L 553.9,173.8 L 551.4,175.1 L 550.7,178.7 L 557.3,186.4 L 559.1,196.4 L 558.6,200.3 L 555.8,202.5 L 550.7,204.0 L 552.2,206.8 L 559.5,207.7 L 563.3,205.7 L 568.7,206.4 L 574.0,208.8 L 578.3,208.6 L 581.7,206.4 L 591.0,210.0 L 593.7,209.4 L 593.9,207.6 L 591.4,205.6 L 590.0,202.2 L 590.3,196.5 L 597.0,197.8 L 598.0,192.8 L 595.1,189.6 L 590.8,188.9 L 589.2,187.2 L 590.6,181.8 L 588.9,177.6 L 590.4,165.4 L 593.1,163.3 L 591.5,161.7 L 595.3,159.0 L 595.2,157.9 L 584.2,158.2 L 587.2,156.2 L 586.4,154.5 L 584.1,154.4 L 583.0,149.9 L 580.6,149.2 L 582.0,145.6 L 579.8,142.9 L 576.4,144.9 L 570.7,142.3 L 569.0,143.5 L 569.6,145.2 L 567.0,145.4 L 567.0,143.7 L 564.0,141.8 L 564.5,139.0 L 562.0,137.1 L 562.6,135.7 L 560.5,128.8 L 549.3,124.0 L 546.6,118.4 L 532.8,114.2 L 528.7,103.1 L 524.2,106.9 L 509.5,111.0 L 505.3,114.3 L 502.3,113.2 L 498.5,115.5 L 490.8,115.3 L 487.0,117.5 L 480.3,118.7 L 476.9,116.2 L 472.7,115.4 L 471.5,123.1 L 470.3,124.1 L 464.9,122.5 L 461.4,125.7 L 453.5,124.7 L 448.7,128.9 L 447.0,128.1 L 446.6,126.5 L 444.4,129.2 L 443.1,128.5 L 443.0,127.3 L 447.1,123.1 L 445.9,120.0 L 442.7,117.9 L 442.8,116.4 L 444.3,115.8 L 443.6,113.1 L 441.5,111.5 L 437.7,113.2 L 432.7,111.5 L 432.0,115.9 L 429.1,118.2 L 426.4,118.4 L 425.1,123.8 L 414.8,134.7 L 413.0,134.2 L 416.6,129.7 L 416.0,128.5 L 402.8,127.2 L 398.5,129.5 L 396.6,132.6 L 386.0,138.0 L 379.2,145.1 L 373.3,147.2 L 372.5,150.4 L 375.6,155.8 L 374.6,159.1 L 372.4,160.7 L 373.1,161.5 L 371.4,161.5 L 371.2,162.8 L 370.4,162.2 L 371.0,162.9 L 369.5,162.7 L 369.2,159.2 L 367.0,157.6 L 367.4,156.1 L 365.1,156.7 L 359.5,155.3 L 359.5,154.0 L 356.5,153.1 L 356.5,149.8 L 352.0,147.3 L 351.5,145.2 L 353.9,144.4 L 354.4,142.9 L 353.1,142.4 L 354.6,141.2 L 352.2,138.3 L 352.9,137.1 L 349.1,134.3 L 345.8,137.3 L 344.3,136.4 L 341.6,141.0 L 332.8,147.2 L 323.1,161.4 L 317.0,176.5 L 333.5,190.2 L 342.6,189.4 L 358.0,192.3 L 366.7,198.4 L 372.5,198.3 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('North East', true)"
                  onmouseenter="onTacRegionHover(event, 'North East')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-oti"
                  data-name="Oti"
                  d="M 565.9,586.3 L 573.1,588.0 L 574.1,593.5 L 571.6,597.3 L 585.8,598.7 L 587.2,593.0 L 589.8,592.9 L 591.7,589.8 L 594.5,579.9 L 599.3,572.5 L 602.2,573.0 L 602.3,570.2 L 608.1,571.2 L 610.0,569.7 L 612.4,570.2 L 614.1,569.2 L 615.0,573.8 L 613.9,577.0 L 611.6,578.1 L 611.9,580.7 L 615.1,580.1 L 616.5,576.2 L 617.9,574.2 L 619.1,574.3 L 619.4,571.5 L 623.8,574.4 L 628.9,574.9 L 629.1,571.2 L 630.1,570.9 L 630.9,567.5 L 628.6,560.1 L 629.7,559.0 L 629.0,556.0 L 627.0,556.2 L 625.5,554.4 L 618.7,557.2 L 614.2,552.6 L 613.9,548.8 L 612.0,548.0 L 613.4,545.1 L 611.8,540.5 L 612.7,531.4 L 611.8,529.9 L 621.1,525.8 L 621.6,514.7 L 624.2,513.7 L 626.7,514.6 L 624.5,507.8 L 627.2,505.9 L 627.0,502.4 L 625.3,502.0 L 626.7,492.3 L 624.9,481.7 L 622.5,475.9 L 624.1,474.6 L 622.6,472.2 L 622.8,466.4 L 620.9,461.4 L 621.9,460.0 L 621.8,455.3 L 624.3,454.3 L 623.9,450.5 L 624.9,448.8 L 622.9,448.7 L 621.4,446.4 L 625.6,444.6 L 626.3,441.7 L 628.2,440.5 L 628.2,438.5 L 633.1,438.7 L 635.0,435.7 L 640.9,434.9 L 640.9,430.9 L 640.6,427.2 L 638.1,426.1 L 638.4,422.6 L 636.5,421.0 L 637.4,420.1 L 635.7,420.1 L 636.2,418.8 L 634.7,417.9 L 630.0,416.4 L 630.6,411.8 L 629.1,406.5 L 627.0,406.3 L 626.4,404.2 L 621.7,403.5 L 620.7,402.1 L 619.0,402.5 L 618.0,399.5 L 614.2,397.9 L 613.6,396.3 L 610.9,397.1 L 608.2,392.5 L 605.1,392.7 L 593.2,373.0 L 588.4,372.6 L 590.0,375.9 L 583.9,373.7 L 582.1,374.8 L 581.6,372.6 L 579.5,375.1 L 576.4,372.0 L 574.2,372.8 L 572.6,371.1 L 570.0,370.9 L 566.2,372.8 L 564.1,368.1 L 563.9,371.6 L 565.9,376.4 L 560.0,382.7 L 559.6,384.9 L 561.7,388.6 L 564.3,390.1 L 564.5,392.7 L 560.9,393.6 L 558.6,391.5 L 555.5,393.8 L 552.7,403.3 L 553.7,405.6 L 557.5,407.0 L 557.7,409.3 L 553.7,410.0 L 551.6,411.6 L 554.2,414.0 L 552.6,420.4 L 557.1,422.1 L 563.0,419.7 L 570.8,418.7 L 574.5,419.6 L 575.1,423.0 L 573.7,426.3 L 566.0,429.0 L 568.1,433.9 L 566.4,440.0 L 556.0,442.5 L 551.7,435.6 L 542.4,433.4 L 540.6,430.3 L 535.5,428.3 L 535.4,426.3 L 532.9,425.9 L 531.7,427.7 L 529.0,428.7 L 525.8,428.2 L 523.9,436.8 L 521.0,436.9 L 517.7,439.5 L 517.9,442.5 L 515.1,442.4 L 513.8,444.1 L 510.3,445.0 L 505.4,444.6 L 502.5,440.6 L 497.3,443.1 L 490.4,442.6 L 487.9,443.5 L 487.5,445.3 L 488.3,447.5 L 493.2,446.6 L 491.9,448.7 L 492.5,451.4 L 488.3,453.2 L 500.6,458.1 L 506.9,463.6 L 508.0,467.3 L 511.6,469.9 L 517.2,470.8 L 520.0,469.8 L 521.6,471.7 L 517.2,480.0 L 521.6,479.8 L 522.5,481.2 L 521.0,487.4 L 523.6,503.1 L 527.2,508.3 L 538.9,507.4 L 541.5,509.4 L 541.3,512.7 L 538.8,517.3 L 538.6,522.2 L 542.2,531.8 L 551.5,545.0 L 553.4,552.2 L 568.1,560.6 L 569.4,562.9 L 570.3,568.5 L 565.9,586.3 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Oti', true)"
                  onmouseenter="onTacRegionHover(event, 'Oti')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-western"
                  data-name="Western"
                  d="M 168.2,837.7 L 164.0,837.3 L 162.2,835.6 L 161.2,837.1 L 162.1,838.5 L 161.1,839.1 L 162.6,842.7 L 160.0,845.3 L 162.3,847.7 L 160.9,848.3 L 164.4,850.1 L 162.7,852.0 L 164.1,852.5 L 164.0,854.5 L 162.9,854.7 L 164.0,856.9 L 165.2,856.7 L 165.5,861.6 L 166.9,862.1 L 166.4,863.8 L 167.6,863.6 L 168.5,865.1 L 166.0,868.7 L 164.3,868.3 L 163.9,870.5 L 162.7,869.3 L 160.0,869.4 L 160.0,868.3 L 159.3,869.8 L 156.4,869.8 L 156.1,868.8 L 155.7,869.9 L 153.5,867.7 L 152.0,869.6 L 147.6,866.9 L 145.7,867.0 L 145.5,868.0 L 137.7,867.0 L 136.6,870.1 L 134.8,870.2 L 129.2,867.6 L 121.3,866.0 L 115.9,867.3 L 116.5,872.0 L 205.2,889.9 L 221.8,894.4 L 233.3,900.3 L 234.5,903.4 L 233.8,904.1 L 239.2,906.7 L 239.8,908.7 L 243.6,909.9 L 244.4,912.9 L 246.4,912.0 L 251.9,913.7 L 253.1,914.9 L 252.2,917.8 L 254.5,918.6 L 254.9,920.0 L 256.6,918.7 L 260.0,919.9 L 261.2,918.1 L 263.8,917.5 L 270.1,917.7 L 276.0,910.8 L 279.0,909.7 L 279.0,908.4 L 289.6,905.0 L 290.6,903.2 L 294.0,901.6 L 303.6,900.0 L 302.4,899.3 L 303.4,898.4 L 301.9,898.5 L 303.4,895.0 L 308.8,892.6 L 307.3,892.6 L 308.6,890.0 L 316.1,888.3 L 318.6,881.8 L 322.9,880.0 L 323.9,867.1 L 325.5,862.2 L 329.7,857.9 L 337.0,856.6 L 339.3,853.0 L 344.1,850.3 L 342.9,845.7 L 331.1,839.2 L 326.5,828.3 L 320.0,823.5 L 321.8,822.9 L 319.6,816.0 L 321.2,808.9 L 313.7,801.4 L 305.7,801.0 L 302.2,799.2 L 292.3,801.3 L 289.4,800.0 L 289.3,791.8 L 291.2,789.0 L 291.7,782.5 L 294.2,778.6 L 292.5,776.5 L 289.3,775.5 L 289.7,764.6 L 278.4,758.1 L 269.5,745.8 L 260.8,741.4 L 258.2,736.1 L 255.3,734.1 L 245.7,729.9 L 236.0,729.1 L 231.8,735.9 L 219.8,735.1 L 214.6,736.3 L 204.4,733.6 L 199.9,737.4 L 196.5,744.1 L 193.9,754.2 L 200.7,757.2 L 198.4,763.9 L 198.8,769.5 L 199.3,772.3 L 202.9,773.2 L 199.9,779.9 L 194.5,778.6 L 190.7,776.0 L 189.2,781.8 L 189.4,786.7 L 192.8,790.2 L 193.1,796.6 L 190.5,799.4 L 191.5,804.2 L 191.1,811.9 L 186.8,813.4 L 188.6,815.3 L 188.6,819.6 L 183.9,830.0 L 179.8,835.5 L 174.7,837.3 L 168.2,837.7 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Western', true)"
                  onmouseenter="onTacRegionHover(event, 'Western')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-eastern"
                  data-name="Eastern"
                  d="M 513.5,585.7 L 501.6,586.0 L 496.3,590.3 L 494.2,594.9 L 483.1,598.0 L 477.6,602.5 L 456.0,603.7 L 432.6,638.1 L 427.3,641.8 L 413.7,643.3 L 409.2,647.5 L 409.4,651.9 L 412.0,656.0 L 415.0,658.1 L 418.1,663.8 L 416.8,666.6 L 412.4,668.6 L 410.3,673.6 L 403.3,680.0 L 401.9,683.3 L 394.2,688.5 L 388.9,700.0 L 386.1,702.7 L 383.5,715.3 L 377.9,720.0 L 378.3,722.3 L 379.7,723.0 L 378.8,724.6 L 379.7,727.4 L 376.5,729.4 L 377.9,730.9 L 375.9,732.2 L 377.8,732.2 L 378.6,734.4 L 376.2,735.1 L 377.0,736.5 L 374.9,738.3 L 375.7,740.4 L 372.5,742.4 L 373.2,745.0 L 371.1,746.2 L 370.4,748.9 L 371.4,750.3 L 372.9,749.9 L 374.3,752.5 L 373.4,755.0 L 374.2,755.5 L 373.1,756.8 L 371.0,756.9 L 370.6,759.1 L 374.6,761.1 L 387.6,777.3 L 391.1,778.9 L 452.3,782.8 L 465.2,782.0 L 472.7,785.2 L 476.3,777.4 L 477.8,776.3 L 482.0,776.1 L 488.5,782.7 L 495.2,781.2 L 498.2,777.1 L 503.8,779.2 L 511.0,777.2 L 519.6,772.7 L 522.1,768.6 L 528.5,769.9 L 533.7,764.9 L 539.9,761.7 L 547.9,755.4 L 548.1,750.3 L 552.9,749.8 L 563.4,744.2 L 575.4,743.1 L 580.2,747.3 L 581.2,739.2 L 578.0,733.9 L 573.2,733.2 L 564.9,734.8 L 559.8,732.6 L 552.5,727.3 L 551.4,724.5 L 555.4,716.2 L 554.3,715.8 L 552.8,711.1 L 549.3,709.3 L 548.9,706.8 L 554.2,699.3 L 557.9,689.7 L 554.3,679.1 L 551.2,675.0 L 551.2,671.3 L 556.8,661.3 L 568.5,652.1 L 569.8,648.0 L 568.3,638.1 L 565.6,629.9 L 562.1,605.1 L 565.9,586.3 L 552.0,586.0 L 547.8,587.3 L 541.5,584.3 L 533.7,584.0 L 529.4,586.4 L 513.5,585.7 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Eastern', true)"
                  onmouseenter="onTacRegionHover(event, 'Eastern')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-northern"
                  data-name="Northern"
                  d="M 521.4,436.9 L 523.9,436.8 L 525.8,428.2 L 529.0,428.7 L 531.7,427.7 L 532.1,426.1 L 534.3,426.0 L 535.4,426.3 L 535.9,428.6 L 540.6,430.3 L 542.4,433.4 L 551.7,435.6 L 556.0,442.5 L 565.9,440.5 L 567.3,438.7 L 568.1,433.9 L 566.1,430.5 L 566.2,428.5 L 573.7,426.3 L 575.1,423.0 L 574.5,419.6 L 570.8,418.7 L 563.0,419.7 L 557.1,422.1 L 552.6,420.4 L 554.2,414.0 L 551.6,411.6 L 553.7,410.0 L 557.7,409.3 L 557.5,407.0 L 553.7,405.6 L 552.7,403.3 L 555.5,393.8 L 558.6,391.5 L 560.9,393.6 L 564.5,392.7 L 564.3,390.1 L 561.7,388.6 L 559.6,384.9 L 560.0,382.7 L 565.9,376.4 L 563.6,368.4 L 565.7,370.0 L 566.2,372.8 L 570.4,370.8 L 573.6,371.4 L 574.2,372.8 L 576.4,372.0 L 579.5,375.1 L 581.6,372.6 L 582.1,374.8 L 583.9,373.7 L 590.0,375.9 L 588.4,372.6 L 592.2,373.5 L 593.6,372.2 L 592.1,370.9 L 595.7,370.6 L 592.6,368.6 L 592.8,365.8 L 598.1,365.8 L 599.4,367.6 L 601.2,366.4 L 599.8,365.1 L 602.2,363.2 L 603.8,363.4 L 605.1,365.9 L 605.5,364.8 L 608.1,364.7 L 609.3,362.8 L 608.7,359.3 L 610.5,357.2 L 612.0,357.5 L 611.6,356.0 L 613.1,354.1 L 610.5,348.7 L 611.6,345.8 L 608.5,343.7 L 603.3,331.4 L 603.9,327.6 L 607.5,322.5 L 606.1,317.6 L 610.9,315.0 L 613.9,308.8 L 610.1,302.3 L 614.5,295.6 L 616.5,295.8 L 615.5,290.8 L 618.5,281.9 L 616.8,279.5 L 611.9,277.2 L 609.3,277.5 L 608.6,275.6 L 609.8,274.7 L 609.7,272.5 L 607.8,270.8 L 602.3,269.1 L 597.6,268.9 L 594.1,270.2 L 590.1,269.1 L 587.9,270.6 L 588.8,272.6 L 587.1,275.6 L 578.3,279.0 L 574.7,277.5 L 572.3,274.0 L 572.2,270.9 L 577.8,271.9 L 583.5,268.1 L 581.5,265.9 L 574.2,265.7 L 573.1,262.4 L 573.7,258.8 L 577.4,259.9 L 580.5,258.5 L 589.0,259.2 L 593.4,256.5 L 591.7,252.8 L 589.6,251.9 L 581.4,255.9 L 577.0,247.5 L 579.1,243.6 L 585.1,248.4 L 587.5,247.6 L 588.9,245.1 L 588.1,239.3 L 584.5,237.9 L 586.0,233.3 L 585.4,231.8 L 586.9,230.9 L 586.0,229.0 L 589.8,221.6 L 589.0,213.8 L 589.8,210.6 L 591.0,210.0 L 581.7,206.4 L 578.3,208.6 L 574.0,208.8 L 568.7,206.4 L 563.3,205.7 L 559.5,207.7 L 552.2,206.8 L 550.7,204.0 L 555.8,202.5 L 558.6,200.3 L 559.1,196.4 L 557.3,186.4 L 550.7,178.7 L 551.4,175.1 L 553.9,173.8 L 554.4,169.4 L 553.1,164.8 L 550.1,161.7 L 548.2,156.7 L 540.2,158.5 L 537.9,160.4 L 538.5,165.2 L 533.8,163.1 L 530.2,165.9 L 525.1,163.5 L 514.1,166.3 L 510.7,164.8 L 499.0,163.9 L 494.7,166.1 L 491.0,165.7 L 489.8,162.8 L 479.5,162.2 L 475.6,158.7 L 472.6,160.5 L 473.9,168.3 L 472.8,171.1 L 468.6,168.1 L 466.4,176.1 L 460.0,182.0 L 457.4,178.2 L 455.4,178.0 L 454.2,175.5 L 452.8,176.4 L 452.8,174.6 L 451.6,175.9 L 451.5,174.3 L 449.7,176.0 L 447.9,175.9 L 447.6,174.7 L 444.1,176.4 L 443.5,175.4 L 442.6,176.3 L 443.6,178.4 L 440.9,177.4 L 440.7,178.8 L 436.4,178.1 L 434.2,179.3 L 433.8,181.3 L 433.4,179.9 L 432.2,180.8 L 430.8,179.5 L 428.7,184.5 L 426.5,184.0 L 426.4,182.6 L 424.0,183.2 L 424.9,184.8 L 422.2,187.6 L 423.6,188.9 L 422.0,190.3 L 422.7,193.0 L 420.3,192.5 L 418.5,198.0 L 416.5,197.4 L 416.2,199.0 L 417.0,199.0 L 415.2,198.9 L 414.9,200.6 L 412.7,199.1 L 412.4,202.0 L 409.3,202.1 L 408.3,206.5 L 406.3,206.0 L 406.8,208.3 L 405.2,208.4 L 403.7,205.3 L 405.9,202.8 L 405.7,200.3 L 403.7,199.3 L 400.5,192.2 L 400.9,190.3 L 402.6,190.0 L 402.3,187.9 L 394.8,186.4 L 395.5,184.0 L 392.2,184.0 L 391.4,182.2 L 392.2,180.1 L 395.3,179.5 L 392.6,177.5 L 388.4,179.9 L 388.8,184.9 L 386.9,187.0 L 380.6,188.6 L 375.5,192.2 L 372.5,198.3 L 373.8,202.5 L 380.0,204.2 L 387.5,208.8 L 390.5,212.1 L 384.9,210.1 L 380.2,210.6 L 380.7,216.4 L 383.0,221.0 L 381.5,230.1 L 377.4,230.4 L 371.0,226.0 L 367.8,230.1 L 363.3,227.7 L 358.8,230.1 L 357.8,231.9 L 357.5,242.1 L 361.6,246.9 L 360.8,251.5 L 353.9,256.0 L 366.5,269.9 L 372.5,283.2 L 369.1,293.2 L 374.0,299.9 L 377.0,296.4 L 382.1,294.5 L 388.4,294.7 L 392.2,296.4 L 397.3,292.5 L 401.4,291.9 L 404.2,293.6 L 403.8,295.8 L 409.1,293.8 L 411.4,297.3 L 419.9,294.9 L 420.6,296.4 L 423.4,295.5 L 424.9,296.8 L 427.0,296.7 L 430.7,293.8 L 436.4,296.9 L 446.0,299.3 L 443.2,296.9 L 443.2,292.8 L 446.7,295.0 L 447.4,292.9 L 451.5,294.3 L 454.1,293.8 L 453.9,294.9 L 455.2,295.4 L 457.5,294.8 L 459.6,292.4 L 463.5,298.0 L 466.5,299.7 L 469.9,298.8 L 471.6,302.0 L 474.0,303.3 L 482.6,306.9 L 487.3,305.2 L 496.6,308.8 L 498.8,312.6 L 500.6,313.2 L 502.4,318.5 L 513.2,322.5 L 511.8,326.6 L 513.0,328.8 L 511.1,332.9 L 512.4,333.7 L 511.9,335.8 L 514.2,340.8 L 513.4,344.7 L 510.6,346.2 L 508.3,345.8 L 508.4,348.3 L 506.9,348.7 L 507.6,349.9 L 502.5,350.9 L 501.1,353.0 L 499.6,353.0 L 500.6,355.8 L 499.3,356.8 L 495.7,356.7 L 494.6,355.5 L 493.9,356.2 L 494.0,358.4 L 497.0,360.3 L 497.0,362.1 L 500.0,362.1 L 500.5,363.3 L 499.0,365.3 L 497.9,371.5 L 495.9,372.6 L 494.5,370.8 L 492.5,371.0 L 489.9,372.5 L 490.5,375.3 L 488.4,375.1 L 488.8,376.9 L 487.9,377.2 L 485.4,377.4 L 485.2,376.3 L 484.5,377.3 L 484.4,373.8 L 481.9,374.5 L 482.0,376.2 L 479.6,374.4 L 478.5,375.5 L 475.9,373.8 L 473.8,376.8 L 474.6,380.8 L 475.8,380.7 L 476.7,382.3 L 478.9,381.7 L 479.8,383.5 L 481.7,384.0 L 481.5,386.0 L 483.8,385.8 L 484.7,392.2 L 489.9,392.4 L 490.1,393.9 L 492.0,392.2 L 491.2,394.6 L 493.9,396.4 L 493.1,397.6 L 495.0,401.5 L 497.5,400.9 L 498.2,402.0 L 498.4,400.4 L 499.3,401.0 L 502.9,397.9 L 504.0,400.0 L 505.7,398.2 L 508.4,397.6 L 508.9,396.0 L 510.5,396.2 L 511.6,398.6 L 514.0,398.6 L 512.7,400.5 L 513.6,402.7 L 512.4,406.5 L 514.2,408.0 L 512.1,407.6 L 511.8,410.1 L 510.6,409.1 L 507.7,409.5 L 509.0,411.6 L 511.0,411.8 L 506.3,426.0 L 499.0,425.2 L 496.2,427.7 L 498.8,430.3 L 503.2,430.4 L 505.1,428.1 L 511.3,427.8 L 512.2,426.2 L 514.1,426.6 L 515.4,425.3 L 515.7,428.1 L 521.1,435.0 L 521.4,436.9 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Northern', true)"
                  onmouseenter="onTacRegionHover(event, 'Northern')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-central"
                  data-name="Central"
                  d="M 245.7,729.9 L 258.2,736.1 L 260.8,741.4 L 269.5,745.8 L 278.4,758.1 L 289.7,764.6 L 289.3,775.5 L 292.5,776.5 L 294.2,778.6 L 291.7,782.5 L 291.2,789.0 L 289.3,791.8 L 289.4,800.0 L 292.3,801.3 L 302.2,799.2 L 305.7,801.0 L 313.7,801.4 L 321.2,808.9 L 319.6,816.0 L 321.8,822.9 L 320.0,823.5 L 326.5,828.3 L 331.1,839.2 L 342.9,845.7 L 344.1,850.3 L 339.3,853.0 L 337.0,856.6 L 329.7,857.9 L 325.5,862.2 L 323.9,867.1 L 322.9,880.0 L 335.1,878.4 L 356.7,873.1 L 356.8,872.1 L 360.6,871.0 L 371.3,870.3 L 392.2,857.6 L 430.2,854.9 L 441.5,845.3 L 440.9,844.4 L 441.9,843.3 L 455.9,838.9 L 458.2,836.1 L 472.8,832.6 L 477.6,827.1 L 477.4,825.6 L 483.4,822.5 L 487.9,818.4 L 481.3,807.3 L 482.0,802.5 L 473.0,791.8 L 473.6,788.1 L 472.7,785.2 L 465.2,782.0 L 452.3,782.8 L 391.1,778.9 L 387.6,777.3 L 374.6,761.1 L 370.9,759.4 L 368.5,759.3 L 368.4,762.5 L 366.6,760.9 L 363.5,763.3 L 362.0,762.7 L 362.1,757.7 L 357.2,759.8 L 358.3,757.7 L 356.7,758.7 L 356.5,756.9 L 352.7,756.5 L 352.2,759.1 L 347.0,758.5 L 346.8,761.0 L 345.7,759.9 L 344.5,761.2 L 342.5,760.5 L 341.9,762.3 L 340.6,760.6 L 341.2,766.5 L 339.1,767.5 L 338.2,765.8 L 335.7,765.7 L 335.7,764.5 L 332.9,764.7 L 333.5,762.7 L 336.1,762.6 L 335.1,762.0 L 335.9,760.9 L 333.6,759.3 L 333.0,757.4 L 331.6,758.1 L 331.7,756.0 L 329.5,756.1 L 330.6,757.3 L 327.7,758.1 L 325.9,756.9 L 324.5,758.8 L 323.5,756.6 L 322.9,758.1 L 316.9,756.8 L 312.5,759.2 L 309.7,757.0 L 309.6,755.4 L 307.4,757.3 L 307.1,753.8 L 305.1,754.1 L 304.1,752.0 L 301.3,752.8 L 299.7,751.7 L 298.9,753.0 L 298.2,750.2 L 295.9,749.2 L 295.2,747.6 L 294.4,748.6 L 293.4,746.8 L 294.6,744.0 L 293.7,743.1 L 292.0,743.9 L 291.6,742.6 L 289.6,742.8 L 287.9,745.6 L 285.9,746.2 L 283.2,743.3 L 282.1,745.4 L 279.3,746.1 L 277.7,742.8 L 274.3,739.8 L 274.0,740.9 L 272.8,739.7 L 271.7,740.4 L 270.9,735.0 L 269.1,733.5 L 270.2,731.6 L 268.8,730.6 L 265.5,732.3 L 265.5,730.1 L 264.1,729.4 L 264.6,724.3 L 261.9,720.5 L 264.5,716.9 L 264.2,712.7 L 266.6,710.4 L 265.3,706.8 L 259.5,707.0 L 250.4,714.6 L 243.3,718.2 L 243.8,727.1 L 245.7,729.9 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Central', true)"
                  onmouseenter="onTacRegionHover(event, 'Central')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-ashanti"
                  data-name="Ashanti"
                  d="M 256.9,708.8 L 259.5,707.0 L 264.9,706.7 L 266.1,707.6 L 266.6,710.4 L 264.2,712.7 L 264.5,716.9 L 261.9,720.5 L 264.6,724.3 L 264.1,729.4 L 265.5,730.1 L 265.5,732.3 L 268.8,730.6 L 270.2,731.6 L 269.1,733.5 L 270.9,735.0 L 271.7,740.4 L 272.8,739.7 L 274.0,740.9 L 274.3,739.8 L 277.7,742.8 L 279.3,746.1 L 282.1,745.4 L 283.2,743.3 L 285.9,746.2 L 287.9,745.6 L 289.6,742.8 L 291.6,742.6 L 292.0,743.9 L 293.7,743.1 L 294.6,744.0 L 293.4,746.8 L 294.4,748.6 L 295.2,747.6 L 295.9,749.2 L 298.2,750.2 L 298.9,753.0 L 299.7,751.7 L 301.3,752.8 L 304.1,752.0 L 305.1,754.1 L 307.1,753.8 L 307.4,757.3 L 309.6,755.4 L 309.7,757.0 L 312.5,759.2 L 316.9,756.8 L 322.9,758.1 L 323.5,756.6 L 324.5,758.8 L 325.9,756.9 L 327.7,758.1 L 330.6,757.3 L 329.5,756.1 L 331.7,756.0 L 331.6,758.1 L 333.0,757.4 L 333.6,759.3 L 335.9,760.9 L 335.1,762.0 L 336.1,762.6 L 333.5,762.7 L 332.9,764.7 L 335.7,764.5 L 335.7,765.7 L 338.2,765.8 L 339.1,767.5 L 341.2,766.5 L 340.6,760.6 L 341.9,762.3 L 342.5,760.5 L 344.5,761.2 L 345.7,759.9 L 346.8,761.0 L 347.0,758.5 L 352.2,759.1 L 352.7,756.5 L 356.5,756.9 L 356.7,758.7 L 358.3,757.7 L 357.2,759.8 L 362.1,757.7 L 362.1,762.9 L 364.3,763.0 L 366.6,760.9 L 368.4,762.5 L 368.5,759.3 L 370.9,759.4 L 371.0,756.9 L 373.1,756.8 L 374.2,755.5 L 373.4,755.0 L 373.8,750.9 L 370.4,748.9 L 371.1,746.2 L 373.2,745.0 L 372.5,742.4 L 375.7,740.4 L 374.9,738.3 L 377.0,736.5 L 376.2,735.1 L 378.6,734.4 L 377.8,732.2 L 375.9,732.2 L 377.9,730.9 L 376.5,729.4 L 379.7,727.4 L 378.8,724.6 L 379.7,723.0 L 378.3,722.3 L 377.9,720.0 L 383.5,715.3 L 386.1,702.7 L 388.9,700.0 L 394.2,688.5 L 401.9,683.3 L 403.3,680.0 L 410.3,673.6 L 412.4,668.6 L 416.8,666.6 L 418.1,663.8 L 415.0,658.1 L 412.0,656.0 L 409.4,651.9 L 409.2,647.5 L 413.7,643.3 L 427.3,641.8 L 432.6,638.1 L 456.0,603.7 L 477.6,602.5 L 483.1,598.0 L 494.2,594.9 L 496.3,590.3 L 501.6,586.0 L 513.5,585.7 L 511.2,582.0 L 508.3,583.2 L 507.8,580.5 L 505.8,579.7 L 505.1,574.4 L 501.4,572.0 L 499.6,572.9 L 498.4,571.8 L 496.4,572.4 L 495.8,571.1 L 492.5,570.2 L 491.4,568.2 L 486.7,568.3 L 482.5,566.2 L 467.0,562.4 L 459.1,563.2 L 451.5,552.0 L 452.0,542.2 L 448.4,538.5 L 447.4,539.1 L 446.7,536.8 L 445.1,538.6 L 439.8,539.7 L 438.7,541.3 L 436.0,541.3 L 433.6,542.8 L 430.5,542.1 L 430.4,541.0 L 428.7,542.6 L 427.7,540.8 L 425.6,541.2 L 425.4,540.0 L 422.8,540.5 L 422.4,539.7 L 419.2,540.2 L 418.8,541.9 L 414.4,542.0 L 410.6,539.3 L 408.0,539.8 L 406.4,537.8 L 404.3,538.6 L 400.8,537.9 L 394.8,542.3 L 393.1,545.5 L 373.3,548.3 L 366.8,547.3 L 362.9,544.9 L 360.9,530.1 L 356.7,526.0 L 350.5,526.4 L 342.5,538.4 L 331.9,545.0 L 332.2,557.7 L 325.5,557.0 L 322.7,559.5 L 321.6,562.6 L 317.1,565.0 L 313.3,565.2 L 309.8,563.4 L 301.0,564.9 L 297.4,561.1 L 291.5,557.8 L 289.9,546.9 L 278.6,549.4 L 273.0,547.0 L 263.5,548.7 L 252.7,547.4 L 246.5,561.0 L 247.4,564.2 L 255.2,564.3 L 259.0,568.6 L 269.1,568.4 L 272.9,571.3 L 276.1,577.1 L 286.9,579.8 L 287.8,580.5 L 286.4,583.5 L 285.6,591.3 L 282.1,593.1 L 277.6,593.1 L 266.2,604.0 L 260.7,607.2 L 251.8,606.4 L 245.6,609.2 L 241.5,607.9 L 239.2,603.0 L 235.9,603.1 L 231.5,606.7 L 228.7,614.2 L 221.9,625.3 L 216.5,628.9 L 214.7,632.9 L 229.0,632.5 L 232.8,634.1 L 233.7,636.5 L 228.6,638.8 L 226.7,651.1 L 221.1,652.5 L 219.5,656.3 L 215.4,657.1 L 209.9,654.5 L 210.3,662.8 L 215.9,673.8 L 215.1,675.6 L 213.1,676.4 L 213.2,678.8 L 212.0,678.6 L 209.3,681.0 L 209.5,683.0 L 207.0,684.9 L 206.6,687.1 L 207.5,687.2 L 206.1,688.6 L 209.7,692.0 L 208.3,699.7 L 211.6,700.2 L 217.6,695.9 L 217.7,690.1 L 220.4,679.8 L 222.7,678.9 L 230.6,682.8 L 232.6,681.4 L 236.2,685.0 L 234.5,694.2 L 237.5,701.3 L 240.7,698.9 L 246.2,699.2 L 251.5,707.9 L 256.9,708.8 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Ashanti', true)"
                  onmouseenter="onTacRegionHover(event, 'Ashanti')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-bono"
                  data-name="Bono"
                  d="M 148.8,633.3 L 152.2,627.3 L 152.8,618.0 L 157.3,614.8 L 158.6,610.7 L 163.3,607.7 L 164.6,602.3 L 167.6,598.6 L 172.7,597.8 L 173.4,593.2 L 179.6,593.0 L 185.8,586.8 L 187.9,586.8 L 189.0,582.4 L 192.8,580.1 L 200.7,579.9 L 200.1,593.4 L 210.6,594.5 L 213.8,597.3 L 210.4,598.9 L 217.9,597.5 L 220.4,594.1 L 224.1,595.4 L 224.9,598.4 L 229.2,595.4 L 227.9,590.9 L 223.0,586.1 L 221.1,580.3 L 230.9,578.8 L 233.1,573.0 L 239.5,568.2 L 245.2,567.5 L 247.4,564.2 L 246.5,561.0 L 249.1,553.5 L 252.7,547.4 L 253.9,547.5 L 250.8,539.3 L 255.7,531.3 L 258.1,523.3 L 263.0,520.4 L 259.4,516.4 L 258.1,510.1 L 260.2,506.4 L 265.3,506.4 L 272.2,502.3 L 281.8,501.6 L 280.7,493.0 L 272.2,481.5 L 268.6,480.2 L 265.8,481.8 L 262.6,480.9 L 265.3,474.2 L 259.1,465.5 L 255.7,458.0 L 245.3,450.3 L 244.6,445.2 L 239.6,442.5 L 238.4,441.1 L 238.7,438.6 L 236.6,438.1 L 234.7,435.6 L 228.6,433.8 L 225.2,420.3 L 217.3,409.1 L 216.8,404.9 L 218.4,403.1 L 216.8,399.3 L 210.8,395.6 L 210.4,388.9 L 208.4,386.5 L 204.0,384.9 L 199.4,376.0 L 194.2,372.9 L 193.2,369.9 L 187.5,366.6 L 199.7,432.8 L 200.0,445.9 L 192.0,454.2 L 184.3,453.3 L 181.7,457.2 L 181.9,460.4 L 185.7,466.3 L 184.8,470.0 L 174.5,471.3 L 166.6,481.2 L 165.3,484.7 L 162.4,481.1 L 160.6,480.7 L 159.3,492.9 L 153.8,498.7 L 154.4,500.7 L 151.5,503.7 L 151.3,506.5 L 141.3,527.2 L 140.2,543.2 L 134.8,565.5 L 134.1,574.0 L 137.2,578.4 L 127.4,591.5 L 126.3,601.1 L 118.7,603.7 L 115.7,608.7 L 118.3,617.0 L 120.6,620.0 L 124.5,621.0 L 133.9,618.6 L 137.1,619.2 L 143.8,624.1 L 148.8,633.3 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Bono', true)"
                  onmouseenter="onTacRegionHover(event, 'Bono')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-volta"
                  data-name="Volta"
                  d="M 565.9,586.3 L 562.1,605.1 L 565.6,629.9 L 568.3,638.1 L 569.8,648.0 L 568.5,652.1 L 556.8,661.3 L 551.2,671.3 L 551.2,675.0 L 554.3,679.1 L 557.9,689.7 L 554.2,699.3 L 548.9,706.2 L 549.3,709.3 L 552.8,711.1 L 554.3,715.8 L 555.4,716.2 L 551.4,724.5 L 551.6,726.1 L 559.8,732.6 L 564.9,734.8 L 576.9,733.3 L 579.6,735.2 L 581.2,739.2 L 580.2,747.3 L 585.2,748.9 L 584.3,756.1 L 585.6,756.7 L 597.0,751.7 L 611.9,748.5 L 626.9,766.3 L 625.7,771.3 L 627.0,776.2 L 629.1,777.5 L 636.0,778.8 L 651.0,778.8 L 666.1,777.1 L 669.5,775.7 L 675.6,768.4 L 677.1,763.4 L 676.8,758.0 L 679.8,752.8 L 688.9,741.2 L 697.8,735.3 L 705.0,732.3 L 704.9,724.5 L 689.0,724.4 L 689.0,722.0 L 686.3,717.1 L 681.0,712.6 L 678.6,707.0 L 678.1,701.7 L 663.0,702.0 L 658.8,695.6 L 655.2,693.3 L 648.8,691.7 L 647.9,688.4 L 643.4,686.9 L 642.7,681.5 L 640.2,680.5 L 638.3,675.3 L 643.1,670.6 L 642.3,667.7 L 640.5,666.4 L 635.5,666.8 L 633.0,663.7 L 632.0,665.0 L 630.8,664.5 L 627.8,659.9 L 629.8,655.4 L 628.6,648.8 L 629.6,646.4 L 625.5,644.4 L 624.2,645.5 L 620.4,643.5 L 618.3,639.8 L 620.2,637.2 L 615.9,633.6 L 614.7,634.8 L 613.7,634.3 L 613.6,630.4 L 615.2,629.4 L 614.1,627.8 L 617.3,626.2 L 618.3,621.7 L 612.4,618.6 L 612.2,614.8 L 616.1,612.0 L 622.3,610.5 L 623.5,607.8 L 622.6,602.3 L 624.9,597.3 L 621.8,595.3 L 623.6,589.8 L 625.2,589.3 L 625.2,586.0 L 626.3,585.9 L 627.6,582.6 L 628.9,574.9 L 623.8,574.4 L 619.4,571.5 L 619.1,574.3 L 617.9,574.2 L 616.5,576.2 L 615.1,580.1 L 611.9,580.7 L 611.6,578.1 L 613.9,577.0 L 615.0,573.8 L 614.1,569.2 L 612.4,570.2 L 610.0,569.7 L 608.1,571.2 L 602.3,570.2 L 602.2,573.0 L 599.3,572.5 L 594.5,579.9 L 591.7,589.8 L 589.8,592.9 L 587.2,593.0 L 585.8,598.7 L 571.6,597.3 L 574.1,593.5 L 573.1,588.0 L 565.9,586.3 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Volta', true)"
                  onmouseenter="onTacRegionHover(event, 'Volta')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-upper-west"
                  data-name="Upper West"
                  d="M 333.5,190.2 L 317.0,176.5 L 323.1,161.4 L 332.8,147.2 L 341.6,141.0 L 344.3,136.4 L 340.4,134.1 L 340.9,131.1 L 342.9,130.1 L 341.7,127.8 L 337.8,127.0 L 337.0,125.3 L 333.4,123.9 L 333.5,121.6 L 332.0,120.2 L 328.5,120.5 L 326.7,118.7 L 325.8,109.5 L 327.9,98.7 L 331.2,98.4 L 332.6,94.4 L 335.9,93.7 L 336.6,87.2 L 338.7,87.1 L 339.6,85.2 L 341.5,84.5 L 343.0,68.8 L 345.6,64.2 L 345.9,61.0 L 320.0,61.0 L 319.9,65.9 L 309.7,66.0 L 309.6,67.6 L 301.6,67.7 L 301.6,66.1 L 230.1,64.7 L 199.0,65.3 L 153.4,63.0 L 152.5,68.4 L 155.4,71.9 L 155.7,74.2 L 152.9,78.5 L 149.3,79.3 L 149.3,83.4 L 147.2,87.0 L 145.0,96.1 L 139.2,101.9 L 139.0,103.8 L 142.9,105.0 L 143.6,106.8 L 138.7,113.6 L 138.7,116.3 L 144.3,125.9 L 144.4,129.2 L 147.8,133.6 L 149.0,138.5 L 153.4,140.9 L 161.7,142.9 L 160.8,144.8 L 153.7,147.6 L 151.1,157.1 L 153.5,160.2 L 163.1,164.7 L 164.0,167.0 L 163.4,169.7 L 158.5,173.5 L 158.3,175.0 L 159.8,181.4 L 159.0,184.5 L 160.5,197.0 L 165.8,206.0 L 163.1,212.3 L 163.3,216.5 L 168.1,223.7 L 167.3,226.0 L 163.7,228.0 L 159.4,235.3 L 160.4,238.0 L 160.0,242.6 L 163.8,244.3 L 164.9,248.7 L 186.0,245.6 L 189.0,239.9 L 193.3,239.7 L 197.4,232.5 L 205.3,230.6 L 214.0,230.7 L 229.7,226.8 L 238.8,227.5 L 246.1,235.4 L 256.8,243.0 L 268.8,236.9 L 272.6,238.2 L 277.1,244.6 L 279.3,250.4 L 283.5,250.4 L 290.7,246.9 L 304.4,243.2 L 309.5,239.3 L 311.2,236.2 L 312.3,229.7 L 309.2,218.4 L 311.8,216.2 L 312.3,210.7 L 310.6,204.8 L 313.0,199.5 L 320.3,199.2 L 320.3,196.0 L 318.6,192.6 L 328.5,193.5 L 333.5,190.2 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Upper West', true)"
                  onmouseenter="onTacRegionHover(event, 'Upper West')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-upper-east"
                  data-name="Upper East"
                  d="M 528.7,103.1 L 531.0,102.3 L 531.0,95.4 L 538.0,88.4 L 536.9,83.8 L 539.9,75.6 L 540.1,68.9 L 545.5,66.8 L 544.2,55.3 L 538.9,48.4 L 535.6,49.4 L 533.4,52.2 L 527.9,52.1 L 523.6,49.4 L 521.4,49.4 L 522.3,44.8 L 503.3,40.0 L 501.4,43.6 L 503.8,45.1 L 503.7,46.9 L 495.4,49.0 L 494.5,51.9 L 492.4,52.5 L 491.8,54.4 L 489.6,49.4 L 490.0,47.1 L 485.9,46.5 L 482.5,48.3 L 480.8,55.0 L 481.0,59.8 L 477.8,60.1 L 476.0,59.0 L 475.3,60.7 L 472.3,61.5 L 471.1,65.5 L 468.8,63.9 L 466.4,67.4 L 465.3,65.3 L 463.2,65.0 L 460.8,69.3 L 459.8,74.4 L 456.4,76.1 L 455.3,72.3 L 452.3,70.8 L 451.7,68.9 L 449.2,69.6 L 451.5,65.6 L 449.0,65.2 L 447.8,66.3 L 447.4,64.3 L 430.5,64.2 L 430.5,63.1 L 427.8,63.0 L 427.6,64.2 L 424.0,64.2 L 422.0,68.5 L 418.7,69.1 L 419.4,66.6 L 415.6,66.6 L 415.6,63.6 L 388.5,63.4 L 388.0,65.9 L 351.8,64.4 L 350.1,62.3 L 345.9,61.0 L 345.6,64.2 L 343.0,68.8 L 341.5,84.5 L 339.6,85.2 L 338.7,87.1 L 336.6,87.2 L 335.9,93.7 L 332.6,94.4 L 331.2,98.4 L 327.9,98.7 L 325.8,109.5 L 326.7,118.7 L 328.5,120.5 L 332.0,120.2 L 333.5,121.6 L 333.4,123.9 L 337.0,125.3 L 337.8,127.0 L 341.7,127.8 L 342.9,130.1 L 340.9,131.1 L 340.4,134.1 L 343.2,136.5 L 345.8,137.3 L 349.1,134.3 L 352.9,137.1 L 352.2,138.3 L 354.6,141.2 L 353.1,142.4 L 354.4,142.9 L 353.9,144.4 L 351.5,145.2 L 352.0,147.3 L 356.5,149.8 L 356.5,153.1 L 359.5,154.0 L 359.5,155.3 L 365.1,156.7 L 367.4,156.1 L 367.1,157.9 L 369.2,159.2 L 369.2,162.1 L 370.3,163.1 L 371.4,161.5 L 373.1,161.5 L 372.4,160.7 L 374.6,159.1 L 375.6,155.8 L 372.5,150.4 L 373.3,147.2 L 379.2,145.1 L 386.0,138.0 L 396.6,132.6 L 398.5,129.5 L 402.8,127.2 L 416.0,128.5 L 416.6,129.7 L 413.0,134.2 L 414.8,134.7 L 425.1,123.8 L 426.4,118.4 L 429.1,118.2 L 432.0,115.9 L 432.7,111.5 L 437.7,113.2 L 441.5,111.5 L 443.6,113.1 L 444.3,115.8 L 442.8,116.4 L 442.7,117.9 L 445.9,120.0 L 447.1,123.1 L 443.0,127.3 L 443.1,128.5 L 444.4,129.2 L 446.6,126.5 L 447.0,128.1 L 448.7,128.9 L 453.5,124.7 L 461.4,125.7 L 464.9,122.5 L 470.3,124.1 L 471.5,123.1 L 472.7,115.4 L 476.9,116.2 L 480.3,118.7 L 487.0,117.5 L 490.8,115.3 L 498.5,115.5 L 502.3,113.2 L 505.3,114.3 L 509.5,111.0 L 524.2,106.9 L 528.7,103.1 Z"
                  class="tactical-region-polygon  transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Upper East', true)"
                  onmouseenter="onTacRegionHover(event, 'Upper East')"
                  onmouseleave="onTacRegionLeave()" />
            <path id="tac-path-greater-accra"
                  data-name="Greater Accra"
                  d="M 472.7,785.2 L 473.6,788.1 L 473.0,791.8 L 482.0,802.5 L 481.3,807.3 L 487.9,818.4 L 495.6,815.2 L 512.3,811.5 L 513.0,810.3 L 527.0,805.7 L 532.7,801.4 L 542.4,799.3 L 543.6,798.6 L 541.6,798.9 L 542.9,797.1 L 543.2,798.4 L 543.8,797.6 L 543.1,796.3 L 543.4,797.1 L 550.2,789.4 L 557.9,787.7 L 565.4,782.9 L 571.0,782.2 L 578.9,778.5 L 601.3,776.4 L 632.8,778.5 L 627.0,776.2 L 625.7,771.3 L 626.9,766.3 L 611.9,748.5 L 597.0,751.7 L 585.6,756.7 L 584.3,756.1 L 585.2,748.9 L 578.8,746.5 L 575.4,743.1 L 572.3,742.8 L 563.4,744.2 L 552.9,749.8 L 548.1,750.3 L 547.9,755.4 L 539.9,761.7 L 533.7,764.9 L 528.5,769.9 L 522.1,768.6 L 519.6,772.7 L 511.0,777.2 L 503.8,779.2 L 498.2,777.1 L 495.2,781.2 L 488.5,782.7 L 482.0,776.1 L 477.8,776.3 L 476.3,777.4 L 472.7,785.2 Z"
                  class="tactical-region-polygon tactical-region-hotspot region-hotspot transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('Greater Accra', true)"
                  onmouseenter="onTacRegionHover(event, 'Greater Accra')"
                  onmouseleave="onTacRegionLeave()" />
            </g>

            <!-- Direct Region Labels -->
            <g id="tacRegionLabelsGroup">
              
            <g id="tac-lbl-western-north" class="pointer-events-none transition-opacity duration-300">
              <text x="154.5" y="726.7" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">WESTERN NORTH</text>
              <text id="tac-badge-western-north" x="154.5" y="740.7" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">134 DOCKETS</text>
            </g>
            <g id="tac-lbl-ahafo" class="pointer-events-none transition-opacity duration-300">
              <text x="210.7" y="635.4" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">AHAFO</text>
              <text id="tac-badge-ahafo" x="210.7" y="649.4" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">96 DOCKETS</text>
            </g>
            <g id="tac-lbl-bono-east" class="pointer-events-none transition-opacity duration-300">
              <text x="404.5" y="484.9" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">BONO EAST</text>
              <text id="tac-badge-bono-east" x="404.5" y="498.9" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">175 DOCKETS</text>
            </g>
            <g id="tac-lbl-savannah" class="pointer-events-none transition-opacity duration-300">
              <text x="396.5" y="370.4" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">SAVANNAH</text>
              <text id="tac-badge-savannah" x="396.5" y="384.4" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">116 DOCKETS</text>
            </g>
            <g id="tac-lbl-north-east" class="pointer-events-none transition-opacity duration-300">
              <text x="474.7" y="150.8" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">NORTH EAST</text>
              <text id="tac-badge-north-east" x="474.7" y="164.8" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">72 DOCKETS</text>
            </g>
            <g id="tac-lbl-oti" class="pointer-events-none transition-opacity duration-300">
              <text x="592.7" y="463.7" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">OTI</text>
              <text id="tac-badge-oti" x="592.7" y="477.7" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">84 DOCKETS</text>
            </g>
            <g id="tac-lbl-western" class="pointer-events-none transition-opacity duration-300">
              <text x="226.6" y="865.2" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">WESTERN</text>
              <text id="tac-badge-western" x="226.6" y="879.2" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">512 DOCKETS</text>
            </g>
            <g id="tac-lbl-eastern" class="pointer-events-none transition-opacity duration-300">
              <text x="453.4" y="709.0" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">EASTERN</text>
              <text id="tac-badge-eastern" x="453.4" y="723.0" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">310 DOCKETS</text>
            </g>
            <g id="tac-lbl-northern" class="pointer-events-none transition-opacity duration-300">
              <text x="517.3" y="307.8" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">NORTHERN</text>
              <text id="tac-badge-northern" x="517.3" y="321.8" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">385 DOCKETS</text>
            </g>
            <g id="tac-lbl-central" class="pointer-events-none transition-opacity duration-300">
              <text x="331.6" y="768.2" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">CENTRAL</text>
              <text id="tac-badge-central" x="331.6" y="782.2" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">245 DOCKETS</text>
            </g>
            <g id="tac-lbl-ashanti" class="pointer-events-none transition-opacity duration-300">
              <text x="354.5" y="665.7" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">ASHANTI</text>
              <text id="tac-badge-ashanti" x="354.5" y="679.7" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">642 DOCKETS</text>
            </g>
            <g id="tac-lbl-bono" class="pointer-events-none transition-opacity duration-300">
              <text x="202.2" y="477.4" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">BONO</text>
              <text id="tac-badge-bono" x="202.2" y="491.4" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">198 DOCKETS</text>
            </g>
            <g id="tac-lbl-volta" class="pointer-events-none transition-opacity duration-300">
              <text x="616.9" y="645.7" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">VOLTA</text>
              <text id="tac-badge-volta" x="616.9" y="659.7" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">268 DOCKETS</text>
            </g>
            <g id="tac-lbl-upper-west" class="pointer-events-none transition-opacity duration-300">
              <text x="234.4" y="134.6" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">UPPER WEST</text>
              <text id="tac-badge-upper-west" x="234.4" y="148.6" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">142 DOCKETS</text>
            </g>
            <g id="tac-lbl-upper-east" class="pointer-events-none transition-opacity duration-300">
              <text x="443.0" y="94.0" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">UPPER EAST</text>
              <text id="tac-badge-upper-east" x="443.0" y="108.0" 
                    text-anchor="middle" 
                    fill="rgba(52, 211, 153, 0.9)"
                    font-weight="normal"
                    class="font-mono-code text-[9px] select-none">188 DOCKETS</text>
            </g>
            <g id="tac-lbl-greater-accra" class="pointer-events-none transition-opacity duration-300">
              <text x="540.8" y="780.3" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-red-300 font-black drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">GREATER ACCRA</text>
              <text id="tac-badge-greater-accra" x="540.8" y="794.3" 
                    text-anchor="middle" 
                    fill="#ef4444"
                    font-weight="900"
                    class="font-mono-code text-[9px] select-none">1248 DOCKETS [HIGHER CASELOAD]</text>
            </g>
            </g>

            <!-- Active MMDA District Radar & Threat Markers -->
            <g id="tacDistrictLayersGroup">
              <!-- Populated via renderTacDistrictMap() -->
            </g>

          </svg>

        </div>

        <!-- Floating Region Tooltip in Tactical Mode -->
        <div id="tacRegionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150">
          <div id="tacTooltipRegionName" class="font-bold text-white text-sm">Region Name</div>
          <div id="tacTooltipSub" class="text-[11px] text-slate-300">Click to focus tactical radar</div>
        </div>

      </div>

      <!-- Tactical MMDA District Telemetry Deck (Detailed MMDA Cards with Clear Labels) -->
      <div id="tacDistrictDeckDrawer" class="hidden absolute bottom-16 left-6 right-6 z-40 max-h-56 bg-slate-950/95 border border-emerald-500/40 rounded-2xl p-3 backdrop-blur-2xl shadow-2xl transition-all flex flex-col pointer-events-auto">
        <div class="flex items-center justify-between pb-2 mb-2 border-b border-emerald-500/20 flex-shrink-0">
          <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded bg-emerald-400"></span>
            <span class="text-xs font-mono-code font-bold uppercase tracking-wider text-white">MMDA DISTRICT RADAR &amp; FORENSIC SECTOR INTELLIGENCE</span>
            <span id="tacDistrictDeckBadge" class="text-[10px] font-mono-code px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">10 DISTRICTS</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="text-[10px] font-mono-code text-slate-400 hidden sm:inline">Click any MMDA to inspect docket</span>
            <button type="button" onclick="toggleTacDistrictDeck()" id="btnToggleTacDeck" class="text-[10px] font-mono-code text-emerald-300 hover:text-white px-2.5 py-1 rounded bg-slate-900 border border-emerald-500/30 hover:bg-emerald-600/30 transition-colors">
              <span id="tacDeckToggleText">Collapse Deck</span>
            </button>
          </div>
        </div>
        <div id="tacDistrictDeckGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 overflow-y-auto pr-1 max-h-40 font-mono-code">
          <!-- Dynamically populated via renderTacDistrictMap() -->
        </div>
      </div>



    </main>

  </div>

  @php
    $dashboardLiveCases = $cases->map(function($c) {
        $rLoc = $c->raid_location ?? '';
        $reg = !empty($c->region) ? $c->region : '';
        if (empty($reg)) {
            if (stripos($rLoc, 'kumasi') !== false) $reg = 'Ashanti';
            else if (stripos($rLoc, 'sekondi') !== false || stripos($rLoc, 'takoradi') !== false) $reg = 'Western';
            else if (stripos($rLoc, 'tamale') !== false) $reg = 'Northern';
            else $reg = 'Greater Accra';
        }
        return [
            'id' => $c->id,
            'case_number' => $c->case_number,
            'title' => $c->title ?? ($c->case_number . ' - ' . ($c->suspect_name ?? 'Forensic Case')),
            'suspect_name' => $c->suspect_name ?? 'Unknown Suspect',
            'unit_name' => $c->unit_name ?? 'Forensic Directorate',
            'region' => $reg,
            'raid_location' => !empty($rLoc) ? $rLoc : ($reg . ' Sector'),
            'status' => $c->status ?? 'Active',
            'case_status' => $c->case_status ?? ($c->status ?? 'Under Investigation'),
            'status_badge' => $c->case_status ?? ($c->status ?? 'Under Investigation'),
            'exhibits_count' => $c->exhibits_count ?? 1,
            'is_multiple' => (bool)($c->is_multiple ?? false)
        ];
    });
  @endphp

  <!-- 7. REAL-TIME CLIENT JAVASCRIPT ENGINE                    -->
  <!-- ======================================================== -->
  <script>
    // Embedded Region Data, Districts, & ViewBoxes
    const regionsData = {"Western North": {"capital": "Sefwi Wiawso", "commander": "DSP T. Arthur", "cases": 134, "activeOps": 1, "status": "Mining Basin Intel", "badge": "Wiawso Station", "color": "#14b8a6", "officers": 11, "narcotics": "24.6 kg", "firearms": 7, "devices": 9, "vehicles": 2, "name": "Western North", "iso": "GH-WN", "cx": 154.5, "cy": 730.7, "path": "M 189.2,781.8 L 190.7,776.0 L 194.5,778.6 L 199.9,779.9 L 202.9,773.2 L 199.3,772.3 L 198.8,769.5 L 198.4,763.9 L 200.7,757.2 L 193.9,754.2 L 196.5,744.1 L 199.9,737.4 L 204.4,733.6 L 214.6,736.3 L 219.8,735.1 L 231.8,735.9 L 236.0,729.1 L 245.7,729.9 L 243.8,727.1 L 243.3,718.2 L 250.4,714.6 L 256.9,708.8 L 251.5,707.9 L 246.2,699.2 L 240.7,698.9 L 237.5,701.3 L 234.5,694.2 L 236.2,685.0 L 232.6,681.4 L 230.6,682.8 L 222.7,678.9 L 220.4,679.8 L 219.6,681.6 L 217.6,695.9 L 211.6,700.2 L 208.3,699.7 L 209.4,694.5 L 195.1,693.6 L 191.9,683.0 L 189.0,680.4 L 181.3,677.0 L 178.0,669.4 L 175.4,668.1 L 167.7,668.3 L 158.0,662.8 L 152.6,661.4 L 151.3,656.6 L 151.0,640.4 L 148.8,633.3 L 143.8,624.1 L 137.1,619.2 L 133.9,618.6 L 124.5,621.0 L 120.6,620.0 L 118.3,617.0 L 115.7,608.7 L 99.0,635.2 L 98.4,641.3 L 101.3,643.7 L 102.1,647.6 L 99.1,651.8 L 98.6,656.3 L 96.8,657.4 L 95.0,662.4 L 98.8,665.4 L 98.4,673.6 L 107.4,707.0 L 106.7,713.0 L 109.6,713.2 L 116.6,726.7 L 120.6,749.7 L 128.0,767.2 L 127.1,787.4 L 137.0,786.4 L 135.3,796.7 L 137.8,797.2 L 139.8,799.6 L 146.2,797.9 L 150.1,794.5 L 150.0,795.4 L 155.2,798.6 L 156.9,798.5 L 157.5,799.9 L 159.5,798.5 L 161.5,800.1 L 161.3,801.7 L 163.3,802.3 L 162.8,810.9 L 165.0,824.5 L 168.4,831.3 L 168.2,837.7 L 174.7,837.3 L 179.8,835.5 L 183.9,830.0 L 188.6,819.6 L 188.6,815.3 L 186.8,813.4 L 191.1,811.9 L 191.5,804.2 L 190.5,799.4 L 193.1,796.6 L 192.8,790.2 L 189.4,786.7 L 189.2,781.8 Z"}, "Ahafo": {"capital": "Goaso", "commander": "Insp. K. Poku", "cases": 96, "activeOps": 1, "status": "Forest Reserve Ops", "badge": "Goaso Sector", "color": "#a855f7", "officers": 10, "narcotics": "14.5 kg", "firearms": 5, "devices": 6, "vehicles": 2, "name": "Ahafo", "iso": "GH-AF", "cx": 210.7, "cy": 639.4, "path": "M 209.4,694.5 L 209.7,692.0 L 206.1,688.6 L 207.5,687.2 L 206.6,687.1 L 207.0,684.9 L 209.5,683.0 L 209.9,680.4 L 213.2,678.8 L 213.1,676.4 L 215.1,675.6 L 215.9,673.8 L 210.3,662.8 L 209.9,654.5 L 215.4,657.1 L 219.5,656.3 L 221.1,652.5 L 226.7,651.1 L 228.6,638.8 L 233.7,636.5 L 232.8,634.1 L 229.0,632.5 L 214.7,632.9 L 214.9,631.1 L 221.9,625.3 L 228.7,614.2 L 231.5,606.7 L 235.9,603.1 L 239.2,603.0 L 241.5,607.9 L 245.6,609.2 L 251.8,606.4 L 260.7,607.2 L 266.2,604.0 L 277.6,593.1 L 282.1,593.1 L 285.6,591.3 L 286.4,583.5 L 287.8,580.5 L 276.1,577.1 L 272.9,571.3 L 269.1,568.4 L 259.0,568.6 L 255.2,564.3 L 247.4,564.2 L 245.2,567.5 L 239.5,568.2 L 233.1,573.0 L 230.9,578.8 L 221.1,580.3 L 223.0,586.1 L 227.9,590.9 L 229.2,595.4 L 224.9,598.4 L 224.1,595.4 L 220.4,594.1 L 217.9,597.5 L 210.4,598.9 L 213.8,597.3 L 210.6,594.5 L 200.1,593.4 L 200.7,579.9 L 192.8,580.1 L 189.0,582.4 L 187.9,586.8 L 185.8,586.8 L 179.6,593.0 L 173.4,593.2 L 172.7,597.8 L 167.6,598.6 L 164.6,602.3 L 163.3,607.7 L 158.6,610.7 L 157.3,614.8 L 152.8,618.0 L 152.2,627.3 L 148.8,633.3 L 151.6,644.5 L 151.3,656.6 L 152.6,661.4 L 158.0,662.8 L 167.7,668.3 L 175.4,668.1 L 178.0,669.4 L 181.3,677.0 L 189.0,680.4 L 191.9,683.0 L 195.1,693.6 L 209.4,694.5 Z"}, "Bono East": {"capital": "Techiman", "commander": "DSP G. Yeboah", "cases": 175, "activeOps": 1, "status": "Commercial Transit", "badge": "Techiman Central", "color": "#8b5cf6", "officers": 13, "narcotics": "26.8 kg", "firearms": 7, "devices": 11, "vehicles": 3, "name": "Bono East", "iso": "GH-BE", "cx": 404.5, "cy": 488.9, "path": "M 522.3,498.1 L 521.0,487.4 L 522.5,481.2 L 521.6,479.8 L 517.2,480.0 L 521.2,473.4 L 521.6,471.7 L 520.0,469.8 L 517.2,470.8 L 511.6,469.9 L 508.0,467.3 L 506.9,463.6 L 497.3,456.3 L 488.3,453.2 L 480.2,452.6 L 466.7,448.5 L 453.5,437.3 L 439.6,428.2 L 431.3,427.7 L 423.6,429.2 L 424.3,431.0 L 423.1,436.9 L 417.2,439.2 L 409.8,439.8 L 407.1,443.1 L 405.6,440.5 L 402.8,440.2 L 401.2,438.5 L 398.8,440.9 L 398.6,451.4 L 394.8,456.5 L 398.3,461.3 L 397.6,465.6 L 399.6,466.6 L 401.9,471.7 L 397.0,470.1 L 393.1,471.7 L 389.6,475.2 L 382.6,474.2 L 381.5,465.9 L 382.8,462.0 L 371.8,443.5 L 367.7,438.9 L 365.8,431.9 L 369.6,422.3 L 368.6,418.4 L 369.5,416.1 L 368.2,414.7 L 368.8,411.9 L 370.7,409.8 L 373.9,408.8 L 374.3,407.0 L 377.6,406.6 L 377.4,404.2 L 379.7,403.3 L 380.4,404.7 L 381.7,404.1 L 380.6,401.0 L 384.3,398.8 L 384.8,394.5 L 383.5,386.5 L 386.0,381.0 L 380.2,380.7 L 375.2,382.6 L 368.3,379.7 L 364.9,375.6 L 361.9,375.3 L 355.8,370.4 L 348.1,368.2 L 337.9,370.2 L 335.9,373.1 L 334.5,379.5 L 332.0,381.0 L 320.5,381.7 L 314.4,379.1 L 311.3,380.7 L 309.4,386.2 L 304.1,388.4 L 300.9,387.9 L 292.5,383.5 L 287.3,384.7 L 285.1,387.1 L 282.6,393.8 L 281.6,401.3 L 283.5,409.3 L 279.8,414.3 L 280.1,419.8 L 285.3,428.6 L 295.5,427.8 L 298.9,430.0 L 302.1,436.3 L 301.8,438.8 L 298.8,442.3 L 295.9,443.4 L 289.0,439.7 L 281.7,438.4 L 275.2,449.0 L 272.7,451.1 L 261.6,453.9 L 253.0,452.5 L 250.8,453.5 L 255.7,458.0 L 259.1,465.5 L 265.3,474.2 L 262.6,480.9 L 265.8,481.8 L 268.6,480.2 L 272.2,481.5 L 280.7,493.0 L 282.0,498.4 L 281.8,501.6 L 272.2,502.3 L 265.3,506.4 L 260.2,506.4 L 258.1,510.1 L 259.4,516.4 L 263.0,520.4 L 258.1,523.3 L 255.7,531.3 L 250.8,539.3 L 251.0,541.9 L 253.9,547.5 L 263.5,548.7 L 273.0,547.0 L 278.6,549.4 L 289.9,546.9 L 291.5,557.8 L 297.4,561.1 L 301.0,564.9 L 309.8,563.4 L 313.3,565.2 L 317.1,565.0 L 321.6,562.6 L 322.7,559.5 L 325.5,557.0 L 332.2,557.7 L 331.9,545.0 L 342.5,538.4 L 350.5,526.4 L 356.7,526.0 L 360.9,530.1 L 362.9,544.9 L 366.8,547.3 L 373.3,548.3 L 393.1,545.5 L 398.6,538.7 L 402.2,537.7 L 403.9,538.6 L 406.4,537.8 L 408.0,539.8 L 410.6,539.3 L 414.4,542.0 L 418.8,541.9 L 419.2,540.2 L 422.4,539.7 L 422.8,540.5 L 425.4,540.0 L 425.6,541.2 L 427.7,540.8 L 428.7,542.6 L 430.4,541.0 L 430.5,542.1 L 433.6,542.8 L 436.0,541.3 L 438.7,541.3 L 439.8,539.7 L 445.1,538.6 L 446.7,536.8 L 447.4,539.1 L 448.4,538.5 L 452.0,542.2 L 451.5,552.0 L 459.1,563.2 L 467.0,562.4 L 482.5,566.2 L 486.7,568.3 L 491.4,568.2 L 492.5,570.2 L 495.8,571.1 L 496.4,572.4 L 498.4,571.8 L 499.6,572.9 L 501.4,572.0 L 505.1,574.4 L 505.8,579.7 L 507.8,580.5 L 508.3,583.2 L 511.2,582.0 L 513.5,585.7 L 529.4,586.4 L 533.7,584.0 L 541.5,584.3 L 547.8,587.3 L 552.0,586.0 L 565.9,586.3 L 570.3,568.5 L 569.4,562.9 L 568.1,560.6 L 553.4,552.2 L 551.5,545.0 L 542.2,531.8 L 538.6,522.2 L 538.8,517.3 L 541.3,512.7 L 541.5,509.4 L 538.9,507.4 L 527.2,508.3 L 523.6,503.1 L 522.3,498.1 Z"}, "Savannah": {"capital": "Damongo", "commander": "DSP B. Seidu", "cases": 116, "activeOps": 1, "status": "Wildlife Corridor", "badge": "Damongo Field Base", "color": "#8b5cf6", "officers": 11, "narcotics": "21.0 kg", "firearms": 8, "devices": 7, "vehicles": 3, "name": "Savannah", "iso": "GH-SV", "cx": 396.5, "cy": 374.4, "path": "M 488.3,453.2 L 492.5,451.4 L 491.9,448.7 L 493.2,446.6 L 488.3,447.5 L 487.9,443.5 L 490.4,442.6 L 497.3,443.1 L 502.5,440.6 L 505.4,444.6 L 510.3,445.0 L 513.8,444.1 L 515.1,442.4 L 517.9,442.5 L 517.7,439.5 L 521.4,436.9 L 521.1,435.0 L 515.7,428.1 L 515.4,425.3 L 514.1,426.6 L 512.2,426.2 L 511.3,427.8 L 505.1,428.1 L 503.2,430.4 L 498.8,430.3 L 496.2,427.7 L 499.0,425.2 L 506.3,426.0 L 511.0,411.8 L 509.0,411.6 L 507.7,409.5 L 510.6,409.1 L 511.8,410.1 L 512.1,407.6 L 514.2,408.0 L 512.4,406.5 L 513.6,402.7 L 512.7,400.5 L 514.0,398.6 L 511.6,398.6 L 510.5,396.2 L 508.9,396.0 L 508.4,397.6 L 505.7,398.2 L 504.0,400.0 L 502.9,397.9 L 499.3,401.0 L 498.4,400.4 L 498.2,402.0 L 497.5,400.9 L 495.0,401.5 L 493.1,397.6 L 493.9,396.4 L 491.2,394.6 L 492.0,392.2 L 490.1,393.9 L 489.9,392.4 L 484.7,392.2 L 483.8,385.8 L 481.5,386.0 L 481.7,384.0 L 479.8,383.5 L 478.9,381.7 L 476.7,382.3 L 475.8,380.7 L 474.6,380.8 L 473.8,376.8 L 475.9,373.8 L 478.5,375.5 L 479.6,374.4 L 482.0,376.2 L 481.9,374.5 L 484.4,373.8 L 484.5,377.3 L 485.2,376.3 L 485.4,377.4 L 487.9,377.2 L 488.8,376.9 L 488.4,375.1 L 490.5,375.3 L 489.9,372.5 L 492.5,371.0 L 494.5,370.8 L 495.9,372.6 L 497.9,371.5 L 499.0,365.3 L 500.5,363.3 L 500.0,362.1 L 497.0,362.1 L 497.0,360.3 L 494.0,358.4 L 493.9,356.2 L 494.6,355.5 L 495.7,356.7 L 499.3,356.8 L 500.6,355.8 L 499.6,353.0 L 501.1,353.0 L 502.5,350.9 L 507.6,349.9 L 506.9,348.7 L 508.4,348.3 L 508.3,345.8 L 510.6,346.2 L 513.4,344.7 L 514.2,340.8 L 511.9,335.8 L 512.4,333.7 L 511.1,332.9 L 513.0,328.8 L 511.8,326.6 L 513.2,322.5 L 502.4,318.5 L 500.6,313.2 L 498.8,312.6 L 496.6,308.8 L 487.3,305.2 L 482.6,306.9 L 474.0,303.3 L 471.6,302.0 L 469.9,298.8 L 466.5,299.7 L 463.5,298.0 L 459.6,292.4 L 457.5,294.8 L 455.2,295.4 L 453.9,294.9 L 454.1,293.8 L 451.5,294.3 L 447.4,292.9 L 446.7,295.0 L 443.2,292.8 L 443.2,296.9 L 446.0,299.3 L 436.4,296.9 L 430.7,293.8 L 427.0,296.7 L 424.9,296.8 L 423.4,295.5 L 420.6,296.4 L 419.9,294.9 L 411.4,297.3 L 409.1,293.8 L 403.8,295.8 L 404.2,293.6 L 401.4,291.9 L 397.3,292.5 L 392.2,296.4 L 388.4,294.7 L 382.1,294.5 L 377.0,296.4 L 374.0,299.9 L 371.7,298.0 L 369.1,293.2 L 372.5,283.2 L 366.5,269.9 L 353.9,256.0 L 360.8,251.5 L 361.6,246.9 L 357.5,242.1 L 357.8,231.9 L 358.8,230.1 L 363.3,227.7 L 367.8,230.1 L 371.0,226.0 L 377.4,230.4 L 381.5,230.1 L 383.0,221.0 L 380.7,216.4 L 380.2,210.6 L 384.9,210.1 L 390.5,212.1 L 387.5,208.8 L 380.0,204.2 L 373.8,202.5 L 372.5,198.3 L 366.7,198.4 L 358.0,192.3 L 342.6,189.4 L 333.5,190.2 L 328.5,193.5 L 318.6,192.6 L 320.3,196.0 L 320.3,199.2 L 313.0,199.5 L 310.6,204.8 L 312.3,210.7 L 311.8,216.2 L 309.2,218.4 L 312.3,229.7 L 311.2,236.2 L 309.5,239.3 L 304.4,243.2 L 290.7,246.9 L 283.5,250.4 L 279.3,250.4 L 277.1,244.6 L 272.6,238.2 L 268.8,236.9 L 256.8,243.0 L 246.1,235.4 L 238.8,227.5 L 229.7,226.8 L 214.0,230.7 L 205.3,230.6 L 197.4,232.5 L 193.3,239.7 L 189.0,239.9 L 186.0,245.6 L 164.9,248.7 L 165.2,250.7 L 161.9,255.3 L 162.0,258.7 L 164.2,262.3 L 170.0,265.9 L 173.8,270.6 L 173.3,278.1 L 175.3,284.4 L 174.0,288.3 L 168.9,291.8 L 168.8,294.5 L 170.4,297.7 L 173.7,298.1 L 177.6,302.2 L 176.6,304.6 L 169.8,309.2 L 166.9,315.7 L 161.6,318.1 L 163.4,325.0 L 160.8,328.8 L 163.3,332.3 L 175.8,334.2 L 177.9,336.3 L 178.3,345.0 L 182.9,348.1 L 182.2,354.0 L 185.8,360.9 L 181.2,365.5 L 183.5,367.1 L 187.0,366.4 L 193.2,369.9 L 194.2,372.9 L 199.4,376.0 L 204.0,384.9 L 208.4,386.5 L 210.4,388.9 L 210.8,395.6 L 216.8,399.3 L 218.4,403.1 L 216.8,404.9 L 217.3,409.1 L 225.2,420.3 L 228.6,433.8 L 234.7,435.6 L 236.6,438.1 L 238.7,438.6 L 238.4,441.1 L 244.6,445.2 L 245.3,450.3 L 248.8,453.2 L 250.4,453.8 L 253.0,452.5 L 261.6,453.9 L 272.7,451.1 L 275.2,449.0 L 281.7,438.4 L 289.0,439.7 L 295.9,443.4 L 298.8,442.3 L 301.8,438.8 L 302.1,436.3 L 298.9,430.0 L 295.5,427.8 L 285.3,428.6 L 280.1,419.8 L 279.8,414.3 L 283.5,409.3 L 281.6,401.3 L 282.6,393.8 L 285.1,387.1 L 287.3,384.7 L 292.5,383.5 L 300.9,387.9 L 304.1,388.4 L 309.4,386.2 L 311.3,380.7 L 314.4,379.1 L 320.5,381.7 L 332.0,381.0 L 334.5,379.5 L 335.9,373.1 L 337.9,370.2 L 348.1,368.2 L 355.8,370.4 L 361.9,375.3 L 364.9,375.6 L 368.3,379.7 L 375.2,382.6 L 380.2,380.7 L 386.0,381.0 L 383.5,386.5 L 384.8,394.5 L 384.3,398.8 L 380.6,401.0 L 381.7,404.1 L 380.4,404.7 L 379.7,403.3 L 377.4,404.2 L 377.6,406.6 L 374.3,407.0 L 373.9,408.8 L 370.7,409.8 L 368.8,411.9 L 368.2,414.7 L 369.5,416.1 L 368.6,418.4 L 369.6,422.3 L 365.8,431.9 L 367.7,438.9 L 371.8,443.5 L 382.8,462.0 L 381.5,465.9 L 382.6,474.2 L 389.6,475.2 L 393.1,471.7 L 397.0,470.1 L 401.9,471.7 L 399.6,466.6 L 397.6,465.6 L 398.3,461.3 L 394.8,456.5 L 398.6,451.4 L 398.8,440.9 L 401.6,438.4 L 402.8,440.2 L 405.6,440.5 L 407.1,443.1 L 409.8,439.8 L 411.2,440.4 L 420.8,438.1 L 423.1,436.9 L 424.3,431.0 L 423.6,429.2 L 427.8,428.2 L 439.6,428.2 L 453.5,437.3 L 466.7,448.5 L 480.2,452.6 L 488.3,453.2 Z"}, "North East": {"capital": "Nalerigu", "commander": "Insp. S. Tampuri", "cases": 72, "activeOps": 1, "status": "Scarp Reconnaissance", "badge": "Gambaga Command", "color": "#ec4899", "officers": 8, "narcotics": "11.8 kg", "firearms": 6, "devices": 4, "vehicles": 1, "name": "North East", "iso": "GH-NE", "cx": 474.7, "cy": 154.8, "path": "M 372.5,198.3 L 375.5,192.2 L 380.6,188.6 L 386.9,187.0 L 388.8,184.9 L 388.4,179.9 L 392.6,177.5 L 395.3,179.5 L 392.2,180.1 L 391.4,181.8 L 392.2,184.0 L 395.5,184.0 L 394.8,186.4 L 402.3,187.9 L 402.6,190.0 L 400.9,190.3 L 400.5,192.2 L 403.7,199.3 L 405.7,200.3 L 405.9,202.8 L 403.7,205.3 L 405.2,208.4 L 406.8,208.3 L 406.3,206.0 L 408.3,206.5 L 409.3,202.1 L 412.4,202.0 L 412.7,199.1 L 414.9,200.6 L 415.2,198.9 L 417.0,199.0 L 416.2,199.0 L 416.5,197.4 L 418.5,198.0 L 420.3,192.5 L 422.7,193.0 L 422.0,190.3 L 423.6,188.9 L 422.2,187.6 L 424.9,184.8 L 424.0,183.2 L 426.4,182.6 L 426.5,184.0 L 428.7,184.5 L 430.8,179.5 L 432.2,180.8 L 433.4,179.9 L 433.8,181.3 L 434.2,179.3 L 436.4,178.1 L 440.7,178.8 L 440.9,177.4 L 443.6,178.4 L 442.6,176.3 L 443.5,175.4 L 444.1,176.4 L 447.6,174.7 L 447.9,175.9 L 449.7,176.0 L 451.5,174.3 L 451.6,175.9 L 452.8,174.6 L 452.8,176.4 L 454.2,175.5 L 455.4,178.0 L 457.4,178.2 L 460.0,182.0 L 466.4,176.1 L 468.6,168.1 L 472.8,171.1 L 473.9,168.3 L 472.6,160.5 L 475.6,158.7 L 479.5,162.2 L 489.8,162.8 L 491.0,165.7 L 494.7,166.1 L 499.0,163.9 L 510.7,164.8 L 514.1,166.3 L 525.1,163.5 L 530.2,165.9 L 533.8,163.1 L 538.5,165.2 L 537.9,160.4 L 540.2,158.5 L 548.2,156.7 L 550.1,161.7 L 553.1,164.8 L 554.4,169.4 L 553.9,173.8 L 551.4,175.1 L 550.7,178.7 L 557.3,186.4 L 559.1,196.4 L 558.6,200.3 L 555.8,202.5 L 550.7,204.0 L 552.2,206.8 L 559.5,207.7 L 563.3,205.7 L 568.7,206.4 L 574.0,208.8 L 578.3,208.6 L 581.7,206.4 L 591.0,210.0 L 593.7,209.4 L 593.9,207.6 L 591.4,205.6 L 590.0,202.2 L 590.3,196.5 L 597.0,197.8 L 598.0,192.8 L 595.1,189.6 L 590.8,188.9 L 589.2,187.2 L 590.6,181.8 L 588.9,177.6 L 590.4,165.4 L 593.1,163.3 L 591.5,161.7 L 595.3,159.0 L 595.2,157.9 L 584.2,158.2 L 587.2,156.2 L 586.4,154.5 L 584.1,154.4 L 583.0,149.9 L 580.6,149.2 L 582.0,145.6 L 579.8,142.9 L 576.4,144.9 L 570.7,142.3 L 569.0,143.5 L 569.6,145.2 L 567.0,145.4 L 567.0,143.7 L 564.0,141.8 L 564.5,139.0 L 562.0,137.1 L 562.6,135.7 L 560.5,128.8 L 549.3,124.0 L 546.6,118.4 L 532.8,114.2 L 528.7,103.1 L 524.2,106.9 L 509.5,111.0 L 505.3,114.3 L 502.3,113.2 L 498.5,115.5 L 490.8,115.3 L 487.0,117.5 L 480.3,118.7 L 476.9,116.2 L 472.7,115.4 L 471.5,123.1 L 470.3,124.1 L 464.9,122.5 L 461.4,125.7 L 453.5,124.7 L 448.7,128.9 L 447.0,128.1 L 446.6,126.5 L 444.4,129.2 L 443.1,128.5 L 443.0,127.3 L 447.1,123.1 L 445.9,120.0 L 442.7,117.9 L 442.8,116.4 L 444.3,115.8 L 443.6,113.1 L 441.5,111.5 L 437.7,113.2 L 432.7,111.5 L 432.0,115.9 L 429.1,118.2 L 426.4,118.4 L 425.1,123.8 L 414.8,134.7 L 413.0,134.2 L 416.6,129.7 L 416.0,128.5 L 402.8,127.2 L 398.5,129.5 L 396.6,132.6 L 386.0,138.0 L 379.2,145.1 L 373.3,147.2 L 372.5,150.4 L 375.6,155.8 L 374.6,159.1 L 372.4,160.7 L 373.1,161.5 L 371.4,161.5 L 371.2,162.8 L 370.4,162.2 L 371.0,162.9 L 369.5,162.7 L 369.2,159.2 L 367.0,157.6 L 367.4,156.1 L 365.1,156.7 L 359.5,155.3 L 359.5,154.0 L 356.5,153.1 L 356.5,149.8 L 352.0,147.3 L 351.5,145.2 L 353.9,144.4 L 354.4,142.9 L 353.1,142.4 L 354.6,141.2 L 352.2,138.3 L 352.9,137.1 L 349.1,134.3 L 345.8,137.3 L 344.3,136.4 L 341.6,141.0 L 332.8,147.2 L 323.1,161.4 L 317.0,176.5 L 333.5,190.2 L 342.6,189.4 L 358.0,192.3 L 366.7,198.4 L 372.5,198.3 Z"}, "Oti": {"capital": "Dambai", "commander": "Insp. N. Gyamfi", "cases": 84, "activeOps": 1, "status": "Riverine Patrol", "badge": "Lake Volta Basin", "color": "#06b6d4", "officers": 9, "narcotics": "16.2 kg", "firearms": 4, "devices": 5, "vehicles": 2, "name": "Oti", "iso": "GH-OT", "cx": 592.7, "cy": 467.7, "path": "M 565.9,586.3 L 573.1,588.0 L 574.1,593.5 L 571.6,597.3 L 585.8,598.7 L 587.2,593.0 L 589.8,592.9 L 591.7,589.8 L 594.5,579.9 L 599.3,572.5 L 602.2,573.0 L 602.3,570.2 L 608.1,571.2 L 610.0,569.7 L 612.4,570.2 L 614.1,569.2 L 615.0,573.8 L 613.9,577.0 L 611.6,578.1 L 611.9,580.7 L 615.1,580.1 L 616.5,576.2 L 617.9,574.2 L 619.1,574.3 L 619.4,571.5 L 623.8,574.4 L 628.9,574.9 L 629.1,571.2 L 630.1,570.9 L 630.9,567.5 L 628.6,560.1 L 629.7,559.0 L 629.0,556.0 L 627.0,556.2 L 625.5,554.4 L 618.7,557.2 L 614.2,552.6 L 613.9,548.8 L 612.0,548.0 L 613.4,545.1 L 611.8,540.5 L 612.7,531.4 L 611.8,529.9 L 621.1,525.8 L 621.6,514.7 L 624.2,513.7 L 626.7,514.6 L 624.5,507.8 L 627.2,505.9 L 627.0,502.4 L 625.3,502.0 L 626.7,492.3 L 624.9,481.7 L 622.5,475.9 L 624.1,474.6 L 622.6,472.2 L 622.8,466.4 L 620.9,461.4 L 621.9,460.0 L 621.8,455.3 L 624.3,454.3 L 623.9,450.5 L 624.9,448.8 L 622.9,448.7 L 621.4,446.4 L 625.6,444.6 L 626.3,441.7 L 628.2,440.5 L 628.2,438.5 L 633.1,438.7 L 635.0,435.7 L 640.9,434.9 L 640.9,430.9 L 640.6,427.2 L 638.1,426.1 L 638.4,422.6 L 636.5,421.0 L 637.4,420.1 L 635.7,420.1 L 636.2,418.8 L 634.7,417.9 L 630.0,416.4 L 630.6,411.8 L 629.1,406.5 L 627.0,406.3 L 626.4,404.2 L 621.7,403.5 L 620.7,402.1 L 619.0,402.5 L 618.0,399.5 L 614.2,397.9 L 613.6,396.3 L 610.9,397.1 L 608.2,392.5 L 605.1,392.7 L 593.2,373.0 L 588.4,372.6 L 590.0,375.9 L 583.9,373.7 L 582.1,374.8 L 581.6,372.6 L 579.5,375.1 L 576.4,372.0 L 574.2,372.8 L 572.6,371.1 L 570.0,370.9 L 566.2,372.8 L 564.1,368.1 L 563.9,371.6 L 565.9,376.4 L 560.0,382.7 L 559.6,384.9 L 561.7,388.6 L 564.3,390.1 L 564.5,392.7 L 560.9,393.6 L 558.6,391.5 L 555.5,393.8 L 552.7,403.3 L 553.7,405.6 L 557.5,407.0 L 557.7,409.3 L 553.7,410.0 L 551.6,411.6 L 554.2,414.0 L 552.6,420.4 L 557.1,422.1 L 563.0,419.7 L 570.8,418.7 L 574.5,419.6 L 575.1,423.0 L 573.7,426.3 L 566.0,429.0 L 568.1,433.9 L 566.4,440.0 L 556.0,442.5 L 551.7,435.6 L 542.4,433.4 L 540.6,430.3 L 535.5,428.3 L 535.4,426.3 L 532.9,425.9 L 531.7,427.7 L 529.0,428.7 L 525.8,428.2 L 523.9,436.8 L 521.0,436.9 L 517.7,439.5 L 517.9,442.5 L 515.1,442.4 L 513.8,444.1 L 510.3,445.0 L 505.4,444.6 L 502.5,440.6 L 497.3,443.1 L 490.4,442.6 L 487.9,443.5 L 487.5,445.3 L 488.3,447.5 L 493.2,446.6 L 491.9,448.7 L 492.5,451.4 L 488.3,453.2 L 500.6,458.1 L 506.9,463.6 L 508.0,467.3 L 511.6,469.9 L 517.2,470.8 L 520.0,469.8 L 521.6,471.7 L 517.2,480.0 L 521.6,479.8 L 522.5,481.2 L 521.0,487.4 L 523.6,503.1 L 527.2,508.3 L 538.9,507.4 L 541.5,509.4 L 541.3,512.7 L 538.8,517.3 L 538.6,522.2 L 542.2,531.8 L 551.5,545.0 L 553.4,552.2 L 568.1,560.6 L 569.4,562.9 L 570.3,568.5 L 565.9,586.3 Z"}, "Western": {"capital": "Sekondi-Takoradi", "commander": "Supt. D. Armah", "cases": 512, "activeOps": 4, "status": "Maritime Surveillance", "badge": "Deep Sea Patrol", "color": "#0ea5e9", "officers": 24, "narcotics": "310.8 kg", "firearms": 14, "devices": 31, "vehicles": 11, "name": "Western", "iso": "GH-WP", "cx": 226.6, "cy": 869.2, "path": "M 168.2,837.7 L 164.0,837.3 L 162.2,835.6 L 161.2,837.1 L 162.1,838.5 L 161.1,839.1 L 162.6,842.7 L 160.0,845.3 L 162.3,847.7 L 160.9,848.3 L 164.4,850.1 L 162.7,852.0 L 164.1,852.5 L 164.0,854.5 L 162.9,854.7 L 164.0,856.9 L 165.2,856.7 L 165.5,861.6 L 166.9,862.1 L 166.4,863.8 L 167.6,863.6 L 168.5,865.1 L 166.0,868.7 L 164.3,868.3 L 163.9,870.5 L 162.7,869.3 L 160.0,869.4 L 160.0,868.3 L 159.3,869.8 L 156.4,869.8 L 156.1,868.8 L 155.7,869.9 L 153.5,867.7 L 152.0,869.6 L 147.6,866.9 L 145.7,867.0 L 145.5,868.0 L 137.7,867.0 L 136.6,870.1 L 134.8,870.2 L 129.2,867.6 L 121.3,866.0 L 115.9,867.3 L 116.5,872.0 L 205.2,889.9 L 221.8,894.4 L 233.3,900.3 L 234.5,903.4 L 233.8,904.1 L 239.2,906.7 L 239.8,908.7 L 243.6,909.9 L 244.4,912.9 L 246.4,912.0 L 251.9,913.7 L 253.1,914.9 L 252.2,917.8 L 254.5,918.6 L 254.9,920.0 L 256.6,918.7 L 260.0,919.9 L 261.2,918.1 L 263.8,917.5 L 270.1,917.7 L 276.0,910.8 L 279.0,909.7 L 279.0,908.4 L 289.6,905.0 L 290.6,903.2 L 294.0,901.6 L 303.6,900.0 L 302.4,899.3 L 303.4,898.4 L 301.9,898.5 L 303.4,895.0 L 308.8,892.6 L 307.3,892.6 L 308.6,890.0 L 316.1,888.3 L 318.6,881.8 L 322.9,880.0 L 323.9,867.1 L 325.5,862.2 L 329.7,857.9 L 337.0,856.6 L 339.3,853.0 L 344.1,850.3 L 342.9,845.7 L 331.1,839.2 L 326.5,828.3 L 320.0,823.5 L 321.8,822.9 L 319.6,816.0 L 321.2,808.9 L 313.7,801.4 L 305.7,801.0 L 302.2,799.2 L 292.3,801.3 L 289.4,800.0 L 289.3,791.8 L 291.2,789.0 L 291.7,782.5 L 294.2,778.6 L 292.5,776.5 L 289.3,775.5 L 289.7,764.6 L 278.4,758.1 L 269.5,745.8 L 260.8,741.4 L 258.2,736.1 L 255.3,734.1 L 245.7,729.9 L 236.0,729.1 L 231.8,735.9 L 219.8,735.1 L 214.6,736.3 L 204.4,733.6 L 199.9,737.4 L 196.5,744.1 L 193.9,754.2 L 200.7,757.2 L 198.4,763.9 L 198.8,769.5 L 199.3,772.3 L 202.9,773.2 L 199.9,779.9 L 194.5,778.6 L 190.7,776.0 L 189.2,781.8 L 189.4,786.7 L 192.8,790.2 L 193.1,796.6 L 190.5,799.4 L 191.5,804.2 L 191.1,811.9 L 186.8,813.4 L 188.6,815.3 L 188.6,819.6 L 183.9,830.0 L 179.8,835.5 L 174.7,837.3 L 168.2,837.7 Z"}, "Eastern": {"capital": "Koforidua", "commander": "DSP P. Osei", "cases": 310, "activeOps": 2, "status": "Normal Patrol", "badge": "Inland Checkpoint", "color": "#8b5cf6", "officers": 18, "narcotics": "45.1 kg", "firearms": 8, "devices": 22, "vehicles": 5, "name": "Eastern", "iso": "GH-EP", "cx": 453.4, "cy": 713.0, "path": "M 513.5,585.7 L 501.6,586.0 L 496.3,590.3 L 494.2,594.9 L 483.1,598.0 L 477.6,602.5 L 456.0,603.7 L 432.6,638.1 L 427.3,641.8 L 413.7,643.3 L 409.2,647.5 L 409.4,651.9 L 412.0,656.0 L 415.0,658.1 L 418.1,663.8 L 416.8,666.6 L 412.4,668.6 L 410.3,673.6 L 403.3,680.0 L 401.9,683.3 L 394.2,688.5 L 388.9,700.0 L 386.1,702.7 L 383.5,715.3 L 377.9,720.0 L 378.3,722.3 L 379.7,723.0 L 378.8,724.6 L 379.7,727.4 L 376.5,729.4 L 377.9,730.9 L 375.9,732.2 L 377.8,732.2 L 378.6,734.4 L 376.2,735.1 L 377.0,736.5 L 374.9,738.3 L 375.7,740.4 L 372.5,742.4 L 373.2,745.0 L 371.1,746.2 L 370.4,748.9 L 371.4,750.3 L 372.9,749.9 L 374.3,752.5 L 373.4,755.0 L 374.2,755.5 L 373.1,756.8 L 371.0,756.9 L 370.6,759.1 L 374.6,761.1 L 387.6,777.3 L 391.1,778.9 L 452.3,782.8 L 465.2,782.0 L 472.7,785.2 L 476.3,777.4 L 477.8,776.3 L 482.0,776.1 L 488.5,782.7 L 495.2,781.2 L 498.2,777.1 L 503.8,779.2 L 511.0,777.2 L 519.6,772.7 L 522.1,768.6 L 528.5,769.9 L 533.7,764.9 L 539.9,761.7 L 547.9,755.4 L 548.1,750.3 L 552.9,749.8 L 563.4,744.2 L 575.4,743.1 L 580.2,747.3 L 581.2,739.2 L 578.0,733.9 L 573.2,733.2 L 564.9,734.8 L 559.8,732.6 L 552.5,727.3 L 551.4,724.5 L 555.4,716.2 L 554.3,715.8 L 552.8,711.1 L 549.3,709.3 L 548.9,706.8 L 554.2,699.3 L 557.9,689.7 L 554.3,679.1 L 551.2,675.0 L 551.2,671.3 L 556.8,661.3 L 568.5,652.1 L 569.8,648.0 L 568.3,638.1 L 565.6,629.9 L 562.1,605.1 L 565.9,586.3 L 552.0,586.0 L 547.8,587.3 L 541.5,584.3 L 533.7,584.0 L 529.4,586.4 L 513.5,585.7 Z"}, "Northern": {"capital": "Tamale", "commander": "Supt. I. Alhassan", "cases": 385, "activeOps": 3, "status": "Cross-Border Sector", "badge": "Northern Shield", "color": "#a855f7", "officers": 22, "narcotics": "64.0 kg", "firearms": 23, "devices": 19, "vehicles": 7, "name": "Northern", "iso": "GH-NP", "cx": 517.3, "cy": 311.8, "path": "M 521.4,436.9 L 523.9,436.8 L 525.8,428.2 L 529.0,428.7 L 531.7,427.7 L 532.1,426.1 L 534.3,426.0 L 535.4,426.3 L 535.9,428.6 L 540.6,430.3 L 542.4,433.4 L 551.7,435.6 L 556.0,442.5 L 565.9,440.5 L 567.3,438.7 L 568.1,433.9 L 566.1,430.5 L 566.2,428.5 L 573.7,426.3 L 575.1,423.0 L 574.5,419.6 L 570.8,418.7 L 563.0,419.7 L 557.1,422.1 L 552.6,420.4 L 554.2,414.0 L 551.6,411.6 L 553.7,410.0 L 557.7,409.3 L 557.5,407.0 L 553.7,405.6 L 552.7,403.3 L 555.5,393.8 L 558.6,391.5 L 560.9,393.6 L 564.5,392.7 L 564.3,390.1 L 561.7,388.6 L 559.6,384.9 L 560.0,382.7 L 565.9,376.4 L 563.6,368.4 L 565.7,370.0 L 566.2,372.8 L 570.4,370.8 L 573.6,371.4 L 574.2,372.8 L 576.4,372.0 L 579.5,375.1 L 581.6,372.6 L 582.1,374.8 L 583.9,373.7 L 590.0,375.9 L 588.4,372.6 L 592.2,373.5 L 593.6,372.2 L 592.1,370.9 L 595.7,370.6 L 592.6,368.6 L 592.8,365.8 L 598.1,365.8 L 599.4,367.6 L 601.2,366.4 L 599.8,365.1 L 602.2,363.2 L 603.8,363.4 L 605.1,365.9 L 605.5,364.8 L 608.1,364.7 L 609.3,362.8 L 608.7,359.3 L 610.5,357.2 L 612.0,357.5 L 611.6,356.0 L 613.1,354.1 L 610.5,348.7 L 611.6,345.8 L 608.5,343.7 L 603.3,331.4 L 603.9,327.6 L 607.5,322.5 L 606.1,317.6 L 610.9,315.0 L 613.9,308.8 L 610.1,302.3 L 614.5,295.6 L 616.5,295.8 L 615.5,290.8 L 618.5,281.9 L 616.8,279.5 L 611.9,277.2 L 609.3,277.5 L 608.6,275.6 L 609.8,274.7 L 609.7,272.5 L 607.8,270.8 L 602.3,269.1 L 597.6,268.9 L 594.1,270.2 L 590.1,269.1 L 587.9,270.6 L 588.8,272.6 L 587.1,275.6 L 578.3,279.0 L 574.7,277.5 L 572.3,274.0 L 572.2,270.9 L 577.8,271.9 L 583.5,268.1 L 581.5,265.9 L 574.2,265.7 L 573.1,262.4 L 573.7,258.8 L 577.4,259.9 L 580.5,258.5 L 589.0,259.2 L 593.4,256.5 L 591.7,252.8 L 589.6,251.9 L 581.4,255.9 L 577.0,247.5 L 579.1,243.6 L 585.1,248.4 L 587.5,247.6 L 588.9,245.1 L 588.1,239.3 L 584.5,237.9 L 586.0,233.3 L 585.4,231.8 L 586.9,230.9 L 586.0,229.0 L 589.8,221.6 L 589.0,213.8 L 589.8,210.6 L 591.0,210.0 L 581.7,206.4 L 578.3,208.6 L 574.0,208.8 L 568.7,206.4 L 563.3,205.7 L 559.5,207.7 L 552.2,206.8 L 550.7,204.0 L 555.8,202.5 L 558.6,200.3 L 559.1,196.4 L 557.3,186.4 L 550.7,178.7 L 551.4,175.1 L 553.9,173.8 L 554.4,169.4 L 553.1,164.8 L 550.1,161.7 L 548.2,156.7 L 540.2,158.5 L 537.9,160.4 L 538.5,165.2 L 533.8,163.1 L 530.2,165.9 L 525.1,163.5 L 514.1,166.3 L 510.7,164.8 L 499.0,163.9 L 494.7,166.1 L 491.0,165.7 L 489.8,162.8 L 479.5,162.2 L 475.6,158.7 L 472.6,160.5 L 473.9,168.3 L 472.8,171.1 L 468.6,168.1 L 466.4,176.1 L 460.0,182.0 L 457.4,178.2 L 455.4,178.0 L 454.2,175.5 L 452.8,176.4 L 452.8,174.6 L 451.6,175.9 L 451.5,174.3 L 449.7,176.0 L 447.9,175.9 L 447.6,174.7 L 444.1,176.4 L 443.5,175.4 L 442.6,176.3 L 443.6,178.4 L 440.9,177.4 L 440.7,178.8 L 436.4,178.1 L 434.2,179.3 L 433.8,181.3 L 433.4,179.9 L 432.2,180.8 L 430.8,179.5 L 428.7,184.5 L 426.5,184.0 L 426.4,182.6 L 424.0,183.2 L 424.9,184.8 L 422.2,187.6 L 423.6,188.9 L 422.0,190.3 L 422.7,193.0 L 420.3,192.5 L 418.5,198.0 L 416.5,197.4 L 416.2,199.0 L 417.0,199.0 L 415.2,198.9 L 414.9,200.6 L 412.7,199.1 L 412.4,202.0 L 409.3,202.1 L 408.3,206.5 L 406.3,206.0 L 406.8,208.3 L 405.2,208.4 L 403.7,205.3 L 405.9,202.8 L 405.7,200.3 L 403.7,199.3 L 400.5,192.2 L 400.9,190.3 L 402.6,190.0 L 402.3,187.9 L 394.8,186.4 L 395.5,184.0 L 392.2,184.0 L 391.4,182.2 L 392.2,180.1 L 395.3,179.5 L 392.6,177.5 L 388.4,179.9 L 388.8,184.9 L 386.9,187.0 L 380.6,188.6 L 375.5,192.2 L 372.5,198.3 L 373.8,202.5 L 380.0,204.2 L 387.5,208.8 L 390.5,212.1 L 384.9,210.1 L 380.2,210.6 L 380.7,216.4 L 383.0,221.0 L 381.5,230.1 L 377.4,230.4 L 371.0,226.0 L 367.8,230.1 L 363.3,227.7 L 358.8,230.1 L 357.8,231.9 L 357.5,242.1 L 361.6,246.9 L 360.8,251.5 L 353.9,256.0 L 366.5,269.9 L 372.5,283.2 L 369.1,293.2 L 374.0,299.9 L 377.0,296.4 L 382.1,294.5 L 388.4,294.7 L 392.2,296.4 L 397.3,292.5 L 401.4,291.9 L 404.2,293.6 L 403.8,295.8 L 409.1,293.8 L 411.4,297.3 L 419.9,294.9 L 420.6,296.4 L 423.4,295.5 L 424.9,296.8 L 427.0,296.7 L 430.7,293.8 L 436.4,296.9 L 446.0,299.3 L 443.2,296.9 L 443.2,292.8 L 446.7,295.0 L 447.4,292.9 L 451.5,294.3 L 454.1,293.8 L 453.9,294.9 L 455.2,295.4 L 457.5,294.8 L 459.6,292.4 L 463.5,298.0 L 466.5,299.7 L 469.9,298.8 L 471.6,302.0 L 474.0,303.3 L 482.6,306.9 L 487.3,305.2 L 496.6,308.8 L 498.8,312.6 L 500.6,313.2 L 502.4,318.5 L 513.2,322.5 L 511.8,326.6 L 513.0,328.8 L 511.1,332.9 L 512.4,333.7 L 511.9,335.8 L 514.2,340.8 L 513.4,344.7 L 510.6,346.2 L 508.3,345.8 L 508.4,348.3 L 506.9,348.7 L 507.6,349.9 L 502.5,350.9 L 501.1,353.0 L 499.6,353.0 L 500.6,355.8 L 499.3,356.8 L 495.7,356.7 L 494.6,355.5 L 493.9,356.2 L 494.0,358.4 L 497.0,360.3 L 497.0,362.1 L 500.0,362.1 L 500.5,363.3 L 499.0,365.3 L 497.9,371.5 L 495.9,372.6 L 494.5,370.8 L 492.5,371.0 L 489.9,372.5 L 490.5,375.3 L 488.4,375.1 L 488.8,376.9 L 487.9,377.2 L 485.4,377.4 L 485.2,376.3 L 484.5,377.3 L 484.4,373.8 L 481.9,374.5 L 482.0,376.2 L 479.6,374.4 L 478.5,375.5 L 475.9,373.8 L 473.8,376.8 L 474.6,380.8 L 475.8,380.7 L 476.7,382.3 L 478.9,381.7 L 479.8,383.5 L 481.7,384.0 L 481.5,386.0 L 483.8,385.8 L 484.7,392.2 L 489.9,392.4 L 490.1,393.9 L 492.0,392.2 L 491.2,394.6 L 493.9,396.4 L 493.1,397.6 L 495.0,401.5 L 497.5,400.9 L 498.2,402.0 L 498.4,400.4 L 499.3,401.0 L 502.9,397.9 L 504.0,400.0 L 505.7,398.2 L 508.4,397.6 L 508.9,396.0 L 510.5,396.2 L 511.6,398.6 L 514.0,398.6 L 512.7,400.5 L 513.6,402.7 L 512.4,406.5 L 514.2,408.0 L 512.1,407.6 L 511.8,410.1 L 510.6,409.1 L 507.7,409.5 L 509.0,411.6 L 511.0,411.8 L 506.3,426.0 L 499.0,425.2 L 496.2,427.7 L 498.8,430.3 L 503.2,430.4 L 505.1,428.1 L 511.3,427.8 L 512.2,426.2 L 514.1,426.6 L 515.4,425.3 L 515.7,428.1 L 521.1,435.0 L 521.4,436.9 Z"}, "Central": {"capital": "Cape Coast", "commander": "DSP A. Quayson", "cases": 245, "activeOps": 2, "status": "Coastal Monitoring", "badge": "Littoral Watch", "color": "#3b82f6", "officers": 16, "narcotics": "38.4 kg", "firearms": 6, "devices": 17, "vehicles": 4, "name": "Central", "iso": "GH-CP", "cx": 331.6, "cy": 772.2, "path": "M 245.7,729.9 L 258.2,736.1 L 260.8,741.4 L 269.5,745.8 L 278.4,758.1 L 289.7,764.6 L 289.3,775.5 L 292.5,776.5 L 294.2,778.6 L 291.7,782.5 L 291.2,789.0 L 289.3,791.8 L 289.4,800.0 L 292.3,801.3 L 302.2,799.2 L 305.7,801.0 L 313.7,801.4 L 321.2,808.9 L 319.6,816.0 L 321.8,822.9 L 320.0,823.5 L 326.5,828.3 L 331.1,839.2 L 342.9,845.7 L 344.1,850.3 L 339.3,853.0 L 337.0,856.6 L 329.7,857.9 L 325.5,862.2 L 323.9,867.1 L 322.9,880.0 L 335.1,878.4 L 356.7,873.1 L 356.8,872.1 L 360.6,871.0 L 371.3,870.3 L 392.2,857.6 L 430.2,854.9 L 441.5,845.3 L 440.9,844.4 L 441.9,843.3 L 455.9,838.9 L 458.2,836.1 L 472.8,832.6 L 477.6,827.1 L 477.4,825.6 L 483.4,822.5 L 487.9,818.4 L 481.3,807.3 L 482.0,802.5 L 473.0,791.8 L 473.6,788.1 L 472.7,785.2 L 465.2,782.0 L 452.3,782.8 L 391.1,778.9 L 387.6,777.3 L 374.6,761.1 L 370.9,759.4 L 368.5,759.3 L 368.4,762.5 L 366.6,760.9 L 363.5,763.3 L 362.0,762.7 L 362.1,757.7 L 357.2,759.8 L 358.3,757.7 L 356.7,758.7 L 356.5,756.9 L 352.7,756.5 L 352.2,759.1 L 347.0,758.5 L 346.8,761.0 L 345.7,759.9 L 344.5,761.2 L 342.5,760.5 L 341.9,762.3 L 340.6,760.6 L 341.2,766.5 L 339.1,767.5 L 338.2,765.8 L 335.7,765.7 L 335.7,764.5 L 332.9,764.7 L 333.5,762.7 L 336.1,762.6 L 335.1,762.0 L 335.9,760.9 L 333.6,759.3 L 333.0,757.4 L 331.6,758.1 L 331.7,756.0 L 329.5,756.1 L 330.6,757.3 L 327.7,758.1 L 325.9,756.9 L 324.5,758.8 L 323.5,756.6 L 322.9,758.1 L 316.9,756.8 L 312.5,759.2 L 309.7,757.0 L 309.6,755.4 L 307.4,757.3 L 307.1,753.8 L 305.1,754.1 L 304.1,752.0 L 301.3,752.8 L 299.7,751.7 L 298.9,753.0 L 298.2,750.2 L 295.9,749.2 L 295.2,747.6 L 294.4,748.6 L 293.4,746.8 L 294.6,744.0 L 293.7,743.1 L 292.0,743.9 L 291.6,742.6 L 289.6,742.8 L 287.9,745.6 L 285.9,746.2 L 283.2,743.3 L 282.1,745.4 L 279.3,746.1 L 277.7,742.8 L 274.3,739.8 L 274.0,740.9 L 272.8,739.7 L 271.7,740.4 L 270.9,735.0 L 269.1,733.5 L 270.2,731.6 L 268.8,730.6 L 265.5,732.3 L 265.5,730.1 L 264.1,729.4 L 264.6,724.3 L 261.9,720.5 L 264.5,716.9 L 264.2,712.7 L 266.6,710.4 L 265.3,706.8 L 259.5,707.0 L 250.4,714.6 L 243.3,718.2 L 243.8,727.1 L 245.7,729.9 Z"}, "Ashanti": {"capital": "Kumasi", "commander": "Supt. E. Boakye", "cases": 642, "activeOps": 5, "status": "Active Deployment", "badge": "Middle Belt Command", "color": "#6366f1", "officers": 32, "narcotics": "92.5 kg", "firearms": 19, "devices": 45, "vehicles": 9, "name": "Ashanti", "iso": "GH-AH", "cx": 354.5, "cy": 669.7, "path": "M 256.9,708.8 L 259.5,707.0 L 264.9,706.7 L 266.1,707.6 L 266.6,710.4 L 264.2,712.7 L 264.5,716.9 L 261.9,720.5 L 264.6,724.3 L 264.1,729.4 L 265.5,730.1 L 265.5,732.3 L 268.8,730.6 L 270.2,731.6 L 269.1,733.5 L 270.9,735.0 L 271.7,740.4 L 272.8,739.7 L 274.0,740.9 L 274.3,739.8 L 277.7,742.8 L 279.3,746.1 L 282.1,745.4 L 283.2,743.3 L 285.9,746.2 L 287.9,745.6 L 289.6,742.8 L 291.6,742.6 L 292.0,743.9 L 293.7,743.1 L 294.6,744.0 L 293.4,746.8 L 294.4,748.6 L 295.2,747.6 L 295.9,749.2 L 298.2,750.2 L 298.9,753.0 L 299.7,751.7 L 301.3,752.8 L 304.1,752.0 L 305.1,754.1 L 307.1,753.8 L 307.4,757.3 L 309.6,755.4 L 309.7,757.0 L 312.5,759.2 L 316.9,756.8 L 322.9,758.1 L 323.5,756.6 L 324.5,758.8 L 325.9,756.9 L 327.7,758.1 L 330.6,757.3 L 329.5,756.1 L 331.7,756.0 L 331.6,758.1 L 333.0,757.4 L 333.6,759.3 L 335.9,760.9 L 335.1,762.0 L 336.1,762.6 L 333.5,762.7 L 332.9,764.7 L 335.7,764.5 L 335.7,765.7 L 338.2,765.8 L 339.1,767.5 L 341.2,766.5 L 340.6,760.6 L 341.9,762.3 L 342.5,760.5 L 344.5,761.2 L 345.7,759.9 L 346.8,761.0 L 347.0,758.5 L 352.2,759.1 L 352.7,756.5 L 356.5,756.9 L 356.7,758.7 L 358.3,757.7 L 357.2,759.8 L 362.1,757.7 L 362.1,762.9 L 364.3,763.0 L 366.6,760.9 L 368.4,762.5 L 368.5,759.3 L 370.9,759.4 L 371.0,756.9 L 373.1,756.8 L 374.2,755.5 L 373.4,755.0 L 373.8,750.9 L 370.4,748.9 L 371.1,746.2 L 373.2,745.0 L 372.5,742.4 L 375.7,740.4 L 374.9,738.3 L 377.0,736.5 L 376.2,735.1 L 378.6,734.4 L 377.8,732.2 L 375.9,732.2 L 377.9,730.9 L 376.5,729.4 L 379.7,727.4 L 378.8,724.6 L 379.7,723.0 L 378.3,722.3 L 377.9,720.0 L 383.5,715.3 L 386.1,702.7 L 388.9,700.0 L 394.2,688.5 L 401.9,683.3 L 403.3,680.0 L 410.3,673.6 L 412.4,668.6 L 416.8,666.6 L 418.1,663.8 L 415.0,658.1 L 412.0,656.0 L 409.4,651.9 L 409.2,647.5 L 413.7,643.3 L 427.3,641.8 L 432.6,638.1 L 456.0,603.7 L 477.6,602.5 L 483.1,598.0 L 494.2,594.9 L 496.3,590.3 L 501.6,586.0 L 513.5,585.7 L 511.2,582.0 L 508.3,583.2 L 507.8,580.5 L 505.8,579.7 L 505.1,574.4 L 501.4,572.0 L 499.6,572.9 L 498.4,571.8 L 496.4,572.4 L 495.8,571.1 L 492.5,570.2 L 491.4,568.2 L 486.7,568.3 L 482.5,566.2 L 467.0,562.4 L 459.1,563.2 L 451.5,552.0 L 452.0,542.2 L 448.4,538.5 L 447.4,539.1 L 446.7,536.8 L 445.1,538.6 L 439.8,539.7 L 438.7,541.3 L 436.0,541.3 L 433.6,542.8 L 430.5,542.1 L 430.4,541.0 L 428.7,542.6 L 427.7,540.8 L 425.6,541.2 L 425.4,540.0 L 422.8,540.5 L 422.4,539.7 L 419.2,540.2 L 418.8,541.9 L 414.4,542.0 L 410.6,539.3 L 408.0,539.8 L 406.4,537.8 L 404.3,538.6 L 400.8,537.9 L 394.8,542.3 L 393.1,545.5 L 373.3,548.3 L 366.8,547.3 L 362.9,544.9 L 360.9,530.1 L 356.7,526.0 L 350.5,526.4 L 342.5,538.4 L 331.9,545.0 L 332.2,557.7 L 325.5,557.0 L 322.7,559.5 L 321.6,562.6 L 317.1,565.0 L 313.3,565.2 L 309.8,563.4 L 301.0,564.9 L 297.4,561.1 L 291.5,557.8 L 289.9,546.9 L 278.6,549.4 L 273.0,547.0 L 263.5,548.7 L 252.7,547.4 L 246.5,561.0 L 247.4,564.2 L 255.2,564.3 L 259.0,568.6 L 269.1,568.4 L 272.9,571.3 L 276.1,577.1 L 286.9,579.8 L 287.8,580.5 L 286.4,583.5 L 285.6,591.3 L 282.1,593.1 L 277.6,593.1 L 266.2,604.0 L 260.7,607.2 L 251.8,606.4 L 245.6,609.2 L 241.5,607.9 L 239.2,603.0 L 235.9,603.1 L 231.5,606.7 L 228.7,614.2 L 221.9,625.3 L 216.5,628.9 L 214.7,632.9 L 229.0,632.5 L 232.8,634.1 L 233.7,636.5 L 228.6,638.8 L 226.7,651.1 L 221.1,652.5 L 219.5,656.3 L 215.4,657.1 L 209.9,654.5 L 210.3,662.8 L 215.9,673.8 L 215.1,675.6 L 213.1,676.4 L 213.2,678.8 L 212.0,678.6 L 209.3,681.0 L 209.5,683.0 L 207.0,684.9 L 206.6,687.1 L 207.5,687.2 L 206.1,688.6 L 209.7,692.0 L 208.3,699.7 L 211.6,700.2 L 217.6,695.9 L 217.7,690.1 L 220.4,679.8 L 222.7,678.9 L 230.6,682.8 L 232.6,681.4 L 236.2,685.0 L 234.5,694.2 L 237.5,701.3 L 240.7,698.9 L 246.2,699.2 L 251.5,707.9 L 256.9,708.8 Z"}, "Bono": {"capital": "Sunyani", "commander": "DSP M. Baffour", "cases": 198, "activeOps": 2, "status": "Transit Intercept", "badge": "Western Corridor", "color": "#6366f1", "officers": 15, "narcotics": "34.2 kg", "firearms": 9, "devices": 14, "vehicles": 4, "name": "Bono", "iso": "GH-BO", "cx": 202.2, "cy": 481.4, "path": "M 148.8,633.3 L 152.2,627.3 L 152.8,618.0 L 157.3,614.8 L 158.6,610.7 L 163.3,607.7 L 164.6,602.3 L 167.6,598.6 L 172.7,597.8 L 173.4,593.2 L 179.6,593.0 L 185.8,586.8 L 187.9,586.8 L 189.0,582.4 L 192.8,580.1 L 200.7,579.9 L 200.1,593.4 L 210.6,594.5 L 213.8,597.3 L 210.4,598.9 L 217.9,597.5 L 220.4,594.1 L 224.1,595.4 L 224.9,598.4 L 229.2,595.4 L 227.9,590.9 L 223.0,586.1 L 221.1,580.3 L 230.9,578.8 L 233.1,573.0 L 239.5,568.2 L 245.2,567.5 L 247.4,564.2 L 246.5,561.0 L 249.1,553.5 L 252.7,547.4 L 253.9,547.5 L 250.8,539.3 L 255.7,531.3 L 258.1,523.3 L 263.0,520.4 L 259.4,516.4 L 258.1,510.1 L 260.2,506.4 L 265.3,506.4 L 272.2,502.3 L 281.8,501.6 L 280.7,493.0 L 272.2,481.5 L 268.6,480.2 L 265.8,481.8 L 262.6,480.9 L 265.3,474.2 L 259.1,465.5 L 255.7,458.0 L 245.3,450.3 L 244.6,445.2 L 239.6,442.5 L 238.4,441.1 L 238.7,438.6 L 236.6,438.1 L 234.7,435.6 L 228.6,433.8 L 225.2,420.3 L 217.3,409.1 L 216.8,404.9 L 218.4,403.1 L 216.8,399.3 L 210.8,395.6 L 210.4,388.9 L 208.4,386.5 L 204.0,384.9 L 199.4,376.0 L 194.2,372.9 L 193.2,369.9 L 187.5,366.6 L 199.7,432.8 L 200.0,445.9 L 192.0,454.2 L 184.3,453.3 L 181.7,457.2 L 181.9,460.4 L 185.7,466.3 L 184.8,470.0 L 174.5,471.3 L 166.6,481.2 L 165.3,484.7 L 162.4,481.1 L 160.6,480.7 L 159.3,492.9 L 153.8,498.7 L 154.4,500.7 L 151.5,503.7 L 151.3,506.5 L 141.3,527.2 L 140.2,543.2 L 134.8,565.5 L 134.1,574.0 L 137.2,578.4 L 127.4,591.5 L 126.3,601.1 L 118.7,603.7 L 115.7,608.7 L 118.3,617.0 L 120.6,620.0 L 124.5,621.0 L 133.9,618.6 L 137.1,619.2 L 143.8,624.1 L 148.8,633.3 Z"}, "Volta": {"capital": "Ho", "commander": "DSP F. Dzreke", "cases": 268, "activeOps": 3, "status": "Eastern Frontier", "badge": "Aflao Border Hub", "color": "#3b82f6", "officers": 20, "narcotics": "112.4 kg", "firearms": 12, "devices": 26, "vehicles": 6, "name": "Volta", "iso": "GH-TV", "cx": 616.9, "cy": 649.7, "path": "M 565.9,586.3 L 562.1,605.1 L 565.6,629.9 L 568.3,638.1 L 569.8,648.0 L 568.5,652.1 L 556.8,661.3 L 551.2,671.3 L 551.2,675.0 L 554.3,679.1 L 557.9,689.7 L 554.2,699.3 L 548.9,706.2 L 549.3,709.3 L 552.8,711.1 L 554.3,715.8 L 555.4,716.2 L 551.4,724.5 L 551.6,726.1 L 559.8,732.6 L 564.9,734.8 L 576.9,733.3 L 579.6,735.2 L 581.2,739.2 L 580.2,747.3 L 585.2,748.9 L 584.3,756.1 L 585.6,756.7 L 597.0,751.7 L 611.9,748.5 L 626.9,766.3 L 625.7,771.3 L 627.0,776.2 L 629.1,777.5 L 636.0,778.8 L 651.0,778.8 L 666.1,777.1 L 669.5,775.7 L 675.6,768.4 L 677.1,763.4 L 676.8,758.0 L 679.8,752.8 L 688.9,741.2 L 697.8,735.3 L 705.0,732.3 L 704.9,724.5 L 689.0,724.4 L 689.0,722.0 L 686.3,717.1 L 681.0,712.6 L 678.6,707.0 L 678.1,701.7 L 663.0,702.0 L 658.8,695.6 L 655.2,693.3 L 648.8,691.7 L 647.9,688.4 L 643.4,686.9 L 642.7,681.5 L 640.2,680.5 L 638.3,675.3 L 643.1,670.6 L 642.3,667.7 L 640.5,666.4 L 635.5,666.8 L 633.0,663.7 L 632.0,665.0 L 630.8,664.5 L 627.8,659.9 L 629.8,655.4 L 628.6,648.8 L 629.6,646.4 L 625.5,644.4 L 624.2,645.5 L 620.4,643.5 L 618.3,639.8 L 620.2,637.2 L 615.9,633.6 L 614.7,634.8 L 613.7,634.3 L 613.6,630.4 L 615.2,629.4 L 614.1,627.8 L 617.3,626.2 L 618.3,621.7 L 612.4,618.6 L 612.2,614.8 L 616.1,612.0 L 622.3,610.5 L 623.5,607.8 L 622.6,602.3 L 624.9,597.3 L 621.8,595.3 L 623.6,589.8 L 625.2,589.3 L 625.2,586.0 L 626.3,585.9 L 627.6,582.6 L 628.9,574.9 L 623.8,574.4 L 619.4,571.5 L 619.1,574.3 L 617.9,574.2 L 616.5,576.2 L 615.1,580.1 L 611.9,580.7 L 611.6,578.1 L 613.9,577.0 L 615.0,573.8 L 614.1,569.2 L 612.4,570.2 L 610.0,569.7 L 608.1,571.2 L 602.3,570.2 L 602.2,573.0 L 599.3,572.5 L 594.5,579.9 L 591.7,589.8 L 589.8,592.9 L 587.2,593.0 L 585.8,598.7 L 571.6,597.3 L 574.1,593.5 L 573.1,588.0 L 565.9,586.3 Z"}, "Upper West": {"capital": "Wa", "commander": "DSP C. Der", "cases": 142, "activeOps": 1, "status": "Frontier Intel", "badge": "Hamile Sector", "color": "#d946ef", "officers": 12, "narcotics": "18.7 kg", "firearms": 11, "devices": 8, "vehicles": 2, "name": "Upper West", "iso": "GH-UW", "cx": 234.4, "cy": 138.6, "path": "M 333.5,190.2 L 317.0,176.5 L 323.1,161.4 L 332.8,147.2 L 341.6,141.0 L 344.3,136.4 L 340.4,134.1 L 340.9,131.1 L 342.9,130.1 L 341.7,127.8 L 337.8,127.0 L 337.0,125.3 L 333.4,123.9 L 333.5,121.6 L 332.0,120.2 L 328.5,120.5 L 326.7,118.7 L 325.8,109.5 L 327.9,98.7 L 331.2,98.4 L 332.6,94.4 L 335.9,93.7 L 336.6,87.2 L 338.7,87.1 L 339.6,85.2 L 341.5,84.5 L 343.0,68.8 L 345.6,64.2 L 345.9,61.0 L 320.0,61.0 L 319.9,65.9 L 309.7,66.0 L 309.6,67.6 L 301.6,67.7 L 301.6,66.1 L 230.1,64.7 L 199.0,65.3 L 153.4,63.0 L 152.5,68.4 L 155.4,71.9 L 155.7,74.2 L 152.9,78.5 L 149.3,79.3 L 149.3,83.4 L 147.2,87.0 L 145.0,96.1 L 139.2,101.9 L 139.0,103.8 L 142.9,105.0 L 143.6,106.8 L 138.7,113.6 L 138.7,116.3 L 144.3,125.9 L 144.4,129.2 L 147.8,133.6 L 149.0,138.5 L 153.4,140.9 L 161.7,142.9 L 160.8,144.8 L 153.7,147.6 L 151.1,157.1 L 153.5,160.2 L 163.1,164.7 L 164.0,167.0 L 163.4,169.7 L 158.5,173.5 L 158.3,175.0 L 159.8,181.4 L 159.0,184.5 L 160.5,197.0 L 165.8,206.0 L 163.1,212.3 L 163.3,216.5 L 168.1,223.7 L 167.3,226.0 L 163.7,228.0 L 159.4,235.3 L 160.4,238.0 L 160.0,242.6 L 163.8,244.3 L 164.9,248.7 L 186.0,245.6 L 189.0,239.9 L 193.3,239.7 L 197.4,232.5 L 205.3,230.6 L 214.0,230.7 L 229.7,226.8 L 238.8,227.5 L 246.1,235.4 L 256.8,243.0 L 268.8,236.9 L 272.6,238.2 L 277.1,244.6 L 279.3,250.4 L 283.5,250.4 L 290.7,246.9 L 304.4,243.2 L 309.5,239.3 L 311.2,236.2 L 312.3,229.7 L 309.2,218.4 L 311.8,216.2 L 312.3,210.7 L 310.6,204.8 L 313.0,199.5 L 320.3,199.2 L 320.3,196.0 L 318.6,192.6 L 328.5,193.5 L 333.5,190.2 Z"}, "Upper East": {"capital": "Bolgatanga", "commander": "DSP R. Ayambire", "cases": 188, "activeOps": 2, "status": "Border Gate Vigilance", "badge": "Paga Sector Hub", "color": "#ec4899", "officers": 14, "narcotics": "29.3 kg", "firearms": 15, "devices": 12, "vehicles": 3, "name": "Upper East", "iso": "GH-UE", "cx": 443.0, "cy": 98.0, "path": "M 528.7,103.1 L 531.0,102.3 L 531.0,95.4 L 538.0,88.4 L 536.9,83.8 L 539.9,75.6 L 540.1,68.9 L 545.5,66.8 L 544.2,55.3 L 538.9,48.4 L 535.6,49.4 L 533.4,52.2 L 527.9,52.1 L 523.6,49.4 L 521.4,49.4 L 522.3,44.8 L 503.3,40.0 L 501.4,43.6 L 503.8,45.1 L 503.7,46.9 L 495.4,49.0 L 494.5,51.9 L 492.4,52.5 L 491.8,54.4 L 489.6,49.4 L 490.0,47.1 L 485.9,46.5 L 482.5,48.3 L 480.8,55.0 L 481.0,59.8 L 477.8,60.1 L 476.0,59.0 L 475.3,60.7 L 472.3,61.5 L 471.1,65.5 L 468.8,63.9 L 466.4,67.4 L 465.3,65.3 L 463.2,65.0 L 460.8,69.3 L 459.8,74.4 L 456.4,76.1 L 455.3,72.3 L 452.3,70.8 L 451.7,68.9 L 449.2,69.6 L 451.5,65.6 L 449.0,65.2 L 447.8,66.3 L 447.4,64.3 L 430.5,64.2 L 430.5,63.1 L 427.8,63.0 L 427.6,64.2 L 424.0,64.2 L 422.0,68.5 L 418.7,69.1 L 419.4,66.6 L 415.6,66.6 L 415.6,63.6 L 388.5,63.4 L 388.0,65.9 L 351.8,64.4 L 350.1,62.3 L 345.9,61.0 L 345.6,64.2 L 343.0,68.8 L 341.5,84.5 L 339.6,85.2 L 338.7,87.1 L 336.6,87.2 L 335.9,93.7 L 332.6,94.4 L 331.2,98.4 L 327.9,98.7 L 325.8,109.5 L 326.7,118.7 L 328.5,120.5 L 332.0,120.2 L 333.5,121.6 L 333.4,123.9 L 337.0,125.3 L 337.8,127.0 L 341.7,127.8 L 342.9,130.1 L 340.9,131.1 L 340.4,134.1 L 343.2,136.5 L 345.8,137.3 L 349.1,134.3 L 352.9,137.1 L 352.2,138.3 L 354.6,141.2 L 353.1,142.4 L 354.4,142.9 L 353.9,144.4 L 351.5,145.2 L 352.0,147.3 L 356.5,149.8 L 356.5,153.1 L 359.5,154.0 L 359.5,155.3 L 365.1,156.7 L 367.4,156.1 L 367.1,157.9 L 369.2,159.2 L 369.2,162.1 L 370.3,163.1 L 371.4,161.5 L 373.1,161.5 L 372.4,160.7 L 374.6,159.1 L 375.6,155.8 L 372.5,150.4 L 373.3,147.2 L 379.2,145.1 L 386.0,138.0 L 396.6,132.6 L 398.5,129.5 L 402.8,127.2 L 416.0,128.5 L 416.6,129.7 L 413.0,134.2 L 414.8,134.7 L 425.1,123.8 L 426.4,118.4 L 429.1,118.2 L 432.0,115.9 L 432.7,111.5 L 437.7,113.2 L 441.5,111.5 L 443.6,113.1 L 444.3,115.8 L 442.8,116.4 L 442.7,117.9 L 445.9,120.0 L 447.1,123.1 L 443.0,127.3 L 443.1,128.5 L 444.4,129.2 L 446.6,126.5 L 447.0,128.1 L 448.7,128.9 L 453.5,124.7 L 461.4,125.7 L 464.9,122.5 L 470.3,124.1 L 471.5,123.1 L 472.7,115.4 L 476.9,116.2 L 480.3,118.7 L 487.0,117.5 L 490.8,115.3 L 498.5,115.5 L 502.3,113.2 L 505.3,114.3 L 509.5,111.0 L 524.2,106.9 L 528.7,103.1 Z"}, "Greater Accra": {"capital": "Accra", "commander": "Chief Supt. K. Mensah", "cases": 1248, "activeOps": 8, "status": "High Intel Alert", "badge": "HQ Central Hub", "color": "#7c3aed", "officers": 46, "narcotics": "184.2 kg", "firearms": 28, "devices": 94, "vehicles": 16, "name": "Greater Accra", "iso": "GH-AA", "cx": 540.8, "cy": 784.3, "path": "M 472.7,785.2 L 473.6,788.1 L 473.0,791.8 L 482.0,802.5 L 481.3,807.3 L 487.9,818.4 L 495.6,815.2 L 512.3,811.5 L 513.0,810.3 L 527.0,805.7 L 532.7,801.4 L 542.4,799.3 L 543.6,798.6 L 541.6,798.9 L 542.9,797.1 L 543.2,798.4 L 543.8,797.6 L 543.1,796.3 L 543.4,797.1 L 550.2,789.4 L 557.9,787.7 L 565.4,782.9 L 571.0,782.2 L 578.9,778.5 L 601.3,776.4 L 632.8,778.5 L 627.0,776.2 L 625.7,771.3 L 626.9,766.3 L 611.9,748.5 L 597.0,751.7 L 585.6,756.7 L 584.3,756.1 L 585.2,748.9 L 578.8,746.5 L 575.4,743.1 L 572.3,742.8 L 563.4,744.2 L 552.9,749.8 L 548.1,750.3 L 547.9,755.4 L 539.9,761.7 L 533.7,764.9 L 528.5,769.9 L 522.1,768.6 L 519.6,772.7 L 511.0,777.2 L 503.8,779.2 L 498.2,777.1 L 495.2,781.2 L 488.5,782.7 L 482.0,776.1 L 477.8,776.3 L 476.3,777.4 L 472.7,785.2 Z"}};
    const districtsData = {"Greater Accra": [{"name": "Accra Metropolitan Assembly (AMA)", "short_name": "Accra Metro", "code": "AMA", "capital": "Accra Central", "x": 522.0, "y": 788.0, "keywords": ["accra", "osu", "central", "ministries", "agbogbloshie", "airport", "kotoka"]}, {"name": "Ayawaso West Municipal Assembly", "short_name": "Ayawaso West (Legon)", "code": "AWMA", "capital": "Dzorwulu / Legon", "x": 536.0, "y": 770.0, "keywords": ["legon", "ayawaso", "dzorwulu", "roman ridge", "airport residential", "east legon"]}, {"name": "Tema Metropolitan Assembly (TMA)", "short_name": "Tema Metro", "code": "TMA", "capital": "Tema Community 1", "x": 565.0, "y": 784.0, "keywords": ["tema", "harbour", "port", "community"]}, {"name": "Ga East Municipal Assembly", "short_name": "Ga East", "code": "GEMA", "capital": "Abokobi", "x": 532.0, "y": 752.0, "keywords": ["abokobi", "madina", "haatso", "dome"]}, {"name": "Ga Central Municipal Assembly", "short_name": "Ga Central", "code": "GCMA", "capital": "Sowutuom", "x": 505.0, "y": 776.0, "keywords": ["sowutuom", "anyaa", "chorkor", "odorkor"]}, {"name": "Ga South Municipal Assembly", "short_name": "Ga South", "code": "GSMA", "capital": "Ngleshie Amanfro", "x": 485.0, "y": 782.0, "keywords": ["weija", "amanfro", "ngleshie"]}, {"name": "Ledzokuku Municipal Assembly", "short_name": "Ledzokuku", "code": "LEKMA", "capital": "Teshie", "x": 546.0, "y": 786.0, "keywords": ["teshie", "nungua", "ledzokuku"]}, {"name": "Kpone-Katamanso Municipal", "short_name": "Kpone-Katamanso", "code": "KKMA", "capital": "Kpone", "x": 578.0, "y": 766.0, "keywords": ["kpone", "katamanso", "michel camp"]}, {"name": "Ningo-Prampram District Assembly", "short_name": "Ningo-Prampram", "code": "NIPDA", "capital": "Prampram", "x": 598.0, "y": 774.0, "keywords": ["prampram", "ningo"]}, {"name": "Ada East District Assembly", "short_name": "Ada East", "code": "AEDA", "capital": "Ada Foah", "x": 622.0, "y": 760.0, "keywords": ["ada", "foah", "estuary"]}], "Ashanti": [{"name": "Kumasi Metropolitan Assembly (KMA)", "short_name": "Kumasi Metro", "code": "KMA", "capital": "Adum / Kumasi Central", "x": 354.0, "y": 668.0, "keywords": ["kumasi", "adum", "bantama", "central", "tafo", "suame"]}, {"name": "Obuasi Municipal Assembly", "short_name": "Obuasi Municipal", "code": "OMA", "capital": "Obuasi", "x": 308.0, "y": 732.0, "keywords": ["obuasi", "anglogold"]}, {"name": "Oforikrom Municipal Assembly", "short_name": "Oforikrom", "code": "OFMA", "capital": "Oforikrom / KNUST", "x": 372.0, "y": 672.0, "keywords": ["oforikrom", "knust", "anloga"]}, {"name": "Ejisu Municipal Assembly", "short_name": "Ejisu Municipal", "code": "EMA", "capital": "Ejisu", "x": 390.0, "y": 658.0, "keywords": ["ejisu", "kwaso"]}, {"name": "Asante Akim Central Municipal", "short_name": "Asante Akim Central", "code": "AACMA", "capital": "Konongo", "x": 420.0, "y": 664.0, "keywords": ["konongo", "odumase"]}, {"name": "Mampong Municipal Assembly", "short_name": "Mampong Municipal", "code": "MMA", "capital": "Mampong", "x": 372.0, "y": 590.0, "keywords": ["mampong", "ashanti mampong"]}, {"name": "Bekwai Municipal Assembly", "short_name": "Bekwai Municipal", "code": "BMA", "capital": "Bekwai", "x": 335.0, "y": 706.0, "keywords": ["bekwai"]}, {"name": "Atwima Nwabiagya North District", "short_name": "Atwima Nwabiagya", "code": "ANNDA", "capital": "Barekese", "x": 328.0, "y": 646.0, "keywords": ["barekese", "atwima"]}], "Western": [{"name": "Sekondi-Takoradi Metropolitan (STMA)", "short_name": "Sekondi-Takoradi", "code": "STMA", "capital": "Sekondi-Takoradi", "x": 275.0, "y": 906.0, "keywords": ["sekondi", "takoradi", "harbour", "market circle"]}, {"name": "Tarkwa-Nsuaem Municipal Assembly", "short_name": "Tarkwa-Nsuaem", "code": "TNMA", "capital": "Tarkwa", "x": 225.0, "y": 842.0, "keywords": ["tarkwa", "nsuaem", "mining"]}, {"name": "Jomoro Municipal Assembly", "short_name": "Jomoro (Elubo)", "code": "JMA", "capital": "Half Assini / Elubo", "x": 135.0, "y": 868.0, "keywords": ["elubo", "jomoro", "half assini"]}, {"name": "Ahanta West Municipal Assembly", "short_name": "Ahanta West", "code": "AWMA", "capital": "Agona Nkwanta", "x": 250.0, "y": 895.0, "keywords": ["agona nkwanta", "busua", "dixcove"]}, {"name": "Prestea-Huni Valley Municipal", "short_name": "Prestea-Huni Valley", "code": "PHVMA", "capital": "Bogoso / Prestea", "x": 210.0, "y": 800.0, "keywords": ["bogoso", "prestea", "huni valley"]}], "Central": [{"name": "Cape Coast Metropolitan Assembly", "short_name": "Cape Coast Metro", "code": "CCMA", "capital": "Cape Coast", "x": 355.0, "y": 870.0, "keywords": ["cape coast", "ucc", "central"]}, {"name": "Awutu Senya East Municipal (Kasoa)", "short_name": "Kasoa Municipal", "code": "ASEMA", "capital": "Kasoa", "x": 475.0, "y": 825.0, "keywords": ["kasoa", "awutu", "ofankor"]}, {"name": "Effutu Municipal Assembly (Winneba)", "short_name": "Effutu (Winneba)", "code": "EMA", "capital": "Winneba", "x": 435.0, "y": 848.0, "keywords": ["winneba", "effutu"]}, {"name": "Komenda-Edina-Eguafo-Abirem (KEEA)", "short_name": "KEEA (Elmina)", "code": "KEEA", "capital": "Elmina", "x": 336.0, "y": 878.0, "keywords": ["elmina", "komenda"]}, {"name": "Mfantseman Municipal Assembly", "short_name": "Mfantseman", "code": "MMA", "capital": "Saltpond", "x": 385.0, "y": 862.0, "keywords": ["saltpond", "mankessim", "mfantseman"]}], "Eastern": [{"name": "New Juaben South Municipal (Koforidua)", "short_name": "Koforidua Municipal", "code": "NJSMA", "capital": "Koforidua", "x": 453.0, "y": 715.0, "keywords": ["koforidua", "new juaben"]}, {"name": "Kwahu West Municipal Assembly", "short_name": "Kwahu West (Nkawkaw)", "code": "KWMA", "capital": "Nkawkaw", "x": 415.0, "y": 660.0, "keywords": ["nkawkaw", "kwahu"]}, {"name": "Suhum Municipal Assembly", "short_name": "Suhum Municipal", "code": "SMA", "capital": "Suhum", "x": 445.0, "y": 745.0, "keywords": ["suhum"]}, {"name": "Asuogyaman District Assembly", "short_name": "Asuogyaman (Akosombo)", "code": "ADA", "capital": "Atimpoku / Akosombo", "x": 545.0, "y": 715.0, "keywords": ["akosombo", "atimpoku", "dam"]}, {"name": "Nsawam Adoagyiri Municipal Assembly", "short_name": "Nsawam Adoagyiri", "code": "NAMA", "capital": "Nsawam", "x": 470.0, "y": 775.0, "keywords": ["nsawam", "adoagyiri"]}], "Volta": [{"name": "Ho Municipal Assembly", "short_name": "Ho Municipal", "code": "HMA", "capital": "Ho Central", "x": 620.0, "y": 650.0, "keywords": ["ho", "central"]}, {"name": "Ketu South Municipal Assembly (Aflao)", "short_name": "Ketu South (Aflao)", "code": "KSMA", "capital": "Denu / Aflao Border", "x": 695.0, "y": 735.0, "keywords": ["aflao", "togo", "border", "ketu south"]}, {"name": "Hohoe Municipal Assembly", "short_name": "Hohoe Municipal", "code": "HHMA", "capital": "Hohoe", "x": 615.0, "y": 600.0, "keywords": ["hohoe"]}, {"name": "Kpando Municipal Assembly", "short_name": "Kpando Municipal", "code": "KPMA", "capital": "Kpando", "x": 570.0, "y": 635.0, "keywords": ["kpando", "lake"]}, {"name": "South Tongu District Assembly", "short_name": "South Tongu (Sogakope)", "code": "STDA", "capital": "Sogakope", "x": 625.0, "y": 765.0, "keywords": ["sogakope", "tongu"]}], "Northern": [{"name": "Tamale Metropolitan Assembly", "short_name": "Tamale Metro", "code": "TMA", "capital": "Tamale Central", "x": 465.0, "y": 285.0, "keywords": ["tamale", "central"]}, {"name": "Savelugu Municipal Assembly", "short_name": "Savelugu Municipal", "code": "SMA", "capital": "Savelugu", "x": 450.0, "y": 245.0, "keywords": ["savelugu"]}, {"name": "Yendi Municipal Assembly", "short_name": "Yendi Municipal", "code": "YMA", "capital": "Yendi", "x": 550.0, "y": 280.0, "keywords": ["yendi"]}, {"name": "Nanumba North Municipal Assembly", "short_name": "Bimbilla Municipal", "code": "NNMA", "capital": "Bimbilla", "x": 590.0, "y": 345.0, "keywords": ["bimbilla", "nanumba"]}], "Upper East": [{"name": "Bolgatanga Municipal Assembly", "short_name": "Bolgatanga Municipal", "code": "BMA", "capital": "Bolgatanga", "x": 445.0, "y": 95.0, "keywords": ["bolgatanga", "bolga"]}, {"name": "Kassena-Nankana Municipal (Navrongo/Paga)", "short_name": "Navrongo / Paga", "code": "KNMA", "capital": "Navrongo / Paga Border", "x": 400.0, "y": 75.0, "keywords": ["navrongo", "paga", "burkina"]}, {"name": "Bawku Municipal Assembly", "short_name": "Bawku Municipal", "code": "BKMA", "capital": "Bawku", "x": 525.0, "y": 75.0, "keywords": ["bawku", "tri-border"]}], "Upper West": [{"name": "Wa Municipal Assembly", "short_name": "Wa Municipal", "code": "WMA", "capital": "Wa Central", "x": 215.0, "y": 175.0, "keywords": ["wa", "central"]}, {"name": "Lambussie District (Hamile Border)", "short_name": "Hamile Border Post", "code": "LDA", "capital": "Hamile", "x": 180.0, "y": 70.0, "keywords": ["hamile", "lambussie"]}, {"name": "Lawra Municipal Assembly", "short_name": "Lawra Municipal", "code": "LMA", "capital": "Lawra", "x": 155.0, "y": 145.0, "keywords": ["lawra", "black volta"]}, {"name": "Sissala East Municipal (Tumu)", "short_name": "Tumu Municipal", "code": "SEMA", "capital": "Tumu", "x": 285.0, "y": 68.0, "keywords": ["tumu", "sissala"]}], "Bono": [{"name": "Sunyani Municipal Assembly", "short_name": "Sunyani Municipal", "code": "SMA", "capital": "Sunyani", "x": 210.0, "y": 480.0, "keywords": ["sunyani"]}, {"name": "Berekum East Municipal Assembly", "short_name": "Berekum Municipal", "code": "BEMA", "capital": "Berekum", "x": 175.0, "y": 485.0, "keywords": ["berekum"]}, {"name": "Dormaa Central Municipal", "short_name": "Dormaa Central", "code": "DCMA", "capital": "Dormaa Ahenkro", "x": 145.0, "y": 525.0, "keywords": ["dormaa", "ahenkro"]}, {"name": "Wenchi Municipal Assembly", "short_name": "Wenchi Municipal", "code": "WMA", "capital": "Wenchi", "x": 245.0, "y": 445.0, "keywords": ["wenchi"]}], "Bono East": [{"name": "Techiman Municipal Assembly", "short_name": "Techiman Municipal", "code": "TMA", "capital": "Techiman", "x": 325.0, "y": 485.0, "keywords": ["techiman"]}, {"name": "Kintampo North Municipal Assembly", "short_name": "Kintampo North", "code": "KNMA", "capital": "Kintampo", "x": 345.0, "y": 415.0, "keywords": ["kintampo"]}, {"name": "Atebubu-Amantin Municipal", "short_name": "Atebubu-Amantin", "code": "AAMA", "capital": "Atebubu", "x": 450.0, "y": 485.0, "keywords": ["atebubu", "amantin"]}], "Ahafo": [{"name": "Asunafo North Municipal Assembly", "short_name": "Asunafo North (Goaso)", "code": "ANMA", "capital": "Goaso", "x": 185.0, "y": 625.0, "keywords": ["goaso", "asunafo"]}, {"name": "Asutifi North District (Kenyasi)", "short_name": "Asutifi North", "code": "ANDA", "capital": "Kenyasi", "x": 230.0, "y": 595.0, "keywords": ["kenyasi", "asutifi"]}, {"name": "Tano South Municipal Assembly", "short_name": "Tano South", "code": "TSMA", "capital": "Bechem", "x": 265.0, "y": 610.0, "keywords": ["bechem", "tano"]}], "Western North": [{"name": "Sefwi Wiawso Municipal Assembly", "short_name": "Sefwi Wiawso", "code": "SWMA", "capital": "Sefwi Wiawso", "x": 175.0, "y": 710.0, "keywords": ["sefwi wiawso", "wiawso"]}, {"name": "Bibiani-Anhwiaso-Bekwai Municipal", "short_name": "Bibiani Municipal", "code": "BABMA", "capital": "Bibiani", "x": 230.0, "y": 665.0, "keywords": ["bibiani", "anhwiaso"]}, {"name": "Bia West District Assembly", "short_name": "Bia West", "code": "BWDA", "capital": "Essam", "x": 120.0, "y": 675.0, "keywords": ["essam", "bia"]}], "Oti": [{"name": "Krachi East Municipal Assembly", "short_name": "Krachi East (Dambai)", "code": "KEMA", "capital": "Dambai", "x": 565.0, "y": 460.0, "keywords": ["dambai", "krachi"]}, {"name": "Nkwanta South Municipal Assembly", "short_name": "Nkwanta South", "code": "NSMA", "capital": "Nkwanta", "x": 605.0, "y": 430.0, "keywords": ["nkwanta"]}, {"name": "Jasikan District Assembly", "short_name": "Jasikan District", "code": "JDA", "capital": "Jasikan", "x": 585.0, "y": 535.0, "keywords": ["jasikan", "buem"]}], "Savannah": [{"name": "West Gonja Municipal Assembly", "short_name": "West Gonja (Damongo)", "code": "WGMA", "capital": "Damongo", "x": 325.0, "y": 320.0, "keywords": ["damongo", "mole", "gonja"]}, {"name": "Central Gonja District Assembly", "short_name": "Central Gonja (Buipe)", "code": "CGDA", "capital": "Buipe", "x": 400.0, "y": 365.0, "keywords": ["buipe"]}, {"name": "Bole District Assembly", "short_name": "Bole District", "code": "BDA", "capital": "Bole", "x": 210.0, "y": 335.0, "keywords": ["bole", "bamboi"]}], "North East": [{"name": "East Mamprusi Municipal Assembly", "short_name": "East Mamprusi (Nalerigu)", "code": "EMMA", "capital": "Nalerigu / Gambaga", "x": 540.0, "y": 145.0, "keywords": ["nalerigu", "gambaga", "east mamprusi"]}, {"name": "West Mamprusi Municipal Assembly", "short_name": "West Mamprusi (Walewale)", "code": "WMMA", "capital": "Walewale", "x": 430.0, "y": 155.0, "keywords": ["walewale", "west mamprusi"]}, {"name": "Bunkpurugu-Nakpanduri District", "short_name": "Bunkpurugu District", "code": "BNDA", "capital": "Bunkpurugu", "x": 585.0, "y": 140.0, "keywords": ["bunkpurugu", "nakpanduri"]}]};
    const regionViewBoxes = {"Western North": "65.9 567.5 220.2 311.4", "Ahafo": "123.8 540.7 189.0 177.2", "Bono East": "193.3 328.8 434.5 298.0", "Savannah": "95.9 138.0 490.4 388.7", "North East": "266.4 83.9 382.2 145.4", "Oti": "459.9 326.6 208.6 313.6", "Western": "74.8 694.7 310.4 259.6", "Eastern": "332.5 547.8 286.7 273.6", "Northern": "306.3 105.3 359.9 388.7", "Central": "199.3 675.6 332.7 235.6", "Ashanti": "150.8 482.5 418.1 328.4", "Bono": "85.8 318.6 225.9 362.7", "Volta": "520.8 531.5 212.3 285.1", "Upper West": "101.4 26.9 281.8 257.6", "Upper East": "286.3 17.8 298.8 167.4", "Greater Accra": "443.9 729.2 217.7 102.8"};

    // State Variables: Pre-populated directly from server-side $cases so pins render immediately!
    let liveCases = {!! json_encode($dashboardLiveCases) !!};
    let selectedCaseId = liveCases.length > 0 ? liveCases[0].case_number : null;

    let selectedRegionName = null;
    let activeTab = 'active';
    let filterUnit = 'All';
    let filterStatus = 'All';
    let searchQuery = '';
    let currentCasesPage = 1;
    const casesPerPage = 7;
    let is3DActive = true;
    let isTac3DActive = true;
    let currentMainView = '3col';
    let isTacticalModeOpen = false;

    // 3D Map Pan & Zoom Physics (Standard View)
    let mapPan = { x: 0, y: 0 };
    let mapZoom = 1;
    let isDragging = false;
    let startMouse = { x: 0, y: 0 };
    let startPan = { x: 0, y: 0 };
    let hasDragged = false;

    // Tactical Fullscreen Pan & Zoom Physics
    let tacMapPan = { x: 0, y: 0 };
    let tacMapZoom = 1.1;
    let isTacDragging = false;
    let tacStartMouse = { x: 0, y: 0 };
    let tacStartPan = { x: 0, y: 0 };
    let tacHasDragged = false;

    // Charts instances
    let finnovaBarChart = null;
    let finnovaSparkline = null;

    // ──────────────────────────────────────────────────────────
    // 1. INITIALIZE CHARTS (Forensic Data)
    // ──────────────────────────────────────────────────────────
    function initCharts() {
      const barCtx = document.getElementById('finnovaBarChart');
      if (barCtx) {
        finnovaBarChart = new Chart(barCtx, {
          type: 'bar',
          data: {
            labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
              data: [25, 45, 60, 85, 110, 142],
              backgroundColor: function(context) {
                const idx = context.dataIndex;
                return idx === 5 ? '#5c56df' : 'rgba(92, 86, 223, 0.45)';
              },
              borderRadius: 6,
              borderSkipped: false,
              barThickness: 12
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#181926',
                titleFont: { family: 'Inter', size: 10 },
                bodyFont: { family: 'JetBrains Mono', size: 10 },
                callbacks: {
                  label: (ctx) => `Intake: ${ctx.raw} exhibits`
                }
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 9, family: 'Inter' } }
              },
              y: {
                display: false,
                grid: { display: false }
              }
            }
          }
        });
      }

      const sparkCtx = document.getElementById('finnovaSparkline');
      if (sparkCtx) {
        finnovaSparkline = new Chart(sparkCtx, {
          type: 'line',
          data: {
            labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
            datasets: [{
              data: [6.5, 5.8, 5.2, 4.9, 4.6, 4.4, 4.3, 4.2],
              borderColor: '#8b5cf6',
              borderWidth: 2.2,
              tension: 0.45,
              pointRadius: function(ctx) {
                return [1, 3, 5, 7].includes(ctx.dataIndex) ? 3.5 : 0;
              },
              pointBackgroundColor: '#ffffff',
              pointBorderColor: '#8b5cf6',
              pointBorderWidth: 1.8,
              fill: false
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#181926',
                callbacks: {
                  label: (ctx) => `${ctx.raw} days`
                }
              }
            },
            scales: {
              x: { display: false },
              y: { display: false }
            }
          }
        });
      }
    }

    // ──────────────────────────────────────────────────────────
    // 2. REAL-TIME DATA POLLING ENGINE (Fetches every 8s)
    // ──────────────────────────────────────────────────────────
    async function fetchRealtimeTelemetry() {
      const spinner = document.getElementById('syncSpinner');
      if (spinner) spinner.classList.add('animate-spin');

      try {
        const res = await fetch("{{ url('/api/dashboard/realtime') }}", {
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(`HTTP error ${res.status}`);
        const json = await res.json();
        if (json.success) {
          applyRealtimeData(json);
        }
      } catch (err) {
        console.warn('Realtime polling issue:', err);
      } finally {
        if (spinner) setTimeout(() => spinner.classList.remove('animate-spin'), 600);
      }
    }

    function applyRealtimeData(data) {
      if (data.metrics) {
        const m = data.metrics;
        const elOverdue = document.getElementById('metricOverdueAmount');
        if (elOverdue) elOverdue.textContent = (m.pending_cases ? `${m.pending_cases} Dockets` : '14 Dockets');
        
        const elNextMonth = document.getElementById('metricNextMonthAmount');
        if (elNextMonth) elNextMonth.textContent = (m.total_exhibits ? `${m.total_exhibits} Exhibits` : '142 Exhibits');

        const elTurnaround = document.getElementById('metricTurnaroundDays');
        if (elTurnaround) elTurnaround.textContent = m.turnaround_days || '4.2 days';

        const elVault = document.getElementById('metricVaultAmount');
        if (elVault) elVault.textContent = (m.total_exhibits ? `${m.total_exhibits * 12 + 140} Items` : '1,865 Items');

        const elCountActive = document.getElementById('tabCountActive');
        if (elCountActive) elCountActive.textContent = m.total_cases || (data.cases ? data.cases.length : 4);

        const elCountAnalysis = document.getElementById('tabCountAnalysis');
        if (elCountAnalysis) elCountAnalysis.textContent = m.pending_cases || 1;
      }

      if (data.chart_data && finnovaBarChart && data.chart_data.monthly_bars) {
        finnovaBarChart.data.datasets[0].data = data.chart_data.monthly_bars;
        finnovaBarChart.update('none');
      }
      if (data.chart_data && finnovaSparkline && data.chart_data.sparkline) {
        finnovaSparkline.data.datasets[0].data = data.chart_data.sparkline;
        finnovaSparkline.update('none');
      }

      if (data.region_counts) {
        const counts = data.region_counts || {};
        let maxCaseload = 0;
        Object.values(counts).forEach(v => { if (v > maxCaseload) maxCaseload = v; });

        Object.keys(regionsData).forEach(rName => {
          const count = counts[rName] || 0;
          const regId = rName.toLowerCase().replace(/\s+/g, '-');
          // ONLY the region with the HIGHER number of cases is RED!
          const isHotspot = (count === maxCaseload && maxCaseload > 0);
          
          if (regionsData[rName]) regionsData[rName].cases = count;

          const poly = document.getElementById('path-' + regId);
          const tacPoly = document.getElementById('tac-path-' + regId);
          const badge = document.getElementById('badge-' + regId);
          const tacBadge = document.getElementById('tac-badge-' + regId);

          if (badge) {
            badge.textContent = isHotspot ? `${count} Dockets [HIGHER CASELOAD]` : `${count} Dockets`;
            if (isHotspot) {
              badge.setAttribute('fill', '#f87171');
              badge.setAttribute('font-weight', '900');
            } else {
              badge.setAttribute('fill', '#818cf8');
              badge.setAttribute('font-weight', 'normal');
            }
          }

          if (tacBadge) {
            tacBadge.textContent = isHotspot ? `${count} DOCKETS [HIGHER CASELOAD]` : `${count} DOCKETS`;
            if (isHotspot) {
              tacBadge.setAttribute('fill', '#ef4444');
              tacBadge.setAttribute('font-weight', '900');
            } else {
              tacBadge.setAttribute('fill', '#34d399');
              tacBadge.setAttribute('font-weight', 'normal');
            }
          }

          if (poly) {
            if (isHotspot) {
              poly.classList.add('region-hotspot');
            } else {
              poly.classList.remove('region-hotspot');
            }
          }

          if (tacPoly) {
            if (isHotspot) {
              tacPoly.classList.add('region-hotspot', 'tactical-region-hotspot');
            } else {
              tacPoly.classList.remove('region-hotspot', 'tactical-region-hotspot');
            }
          }
        });

        if (selectedRegionName) {
          renderDistrictMap(selectedRegionName);
          renderTacDistrictMap(selectedRegionName);
        }
      }

      if (data.cases && data.cases.length > 0) {
        liveCases = data.cases;
        renderInvoicesList();
        renderTacticalCasesStream();
        
        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {
          // On initial page load: Select first case for dossier details, but DO NOT zoom map in! Keep Whole Globe visible!
          selectInvoiceById(liveCases[0].case_number, false);
        } else {
          renderInvoicesList();
          renderTacticalCasesStream();
          renderDossierCard(liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)));
        }
      }
    }

    function refreshRealtimeData() {
      fetchRealtimeTelemetry();
    }

    // ──────────────────────────────────────────────────────────
    // 3. RENDER FORENSIC CASES LIST (Left Panel) & PAGINATION
    // ──────────────────────────────────────────────────────────
    function getFilteredCases() {
      return liveCases.filter(c => {
        if (filterUnit !== 'All' && c.unit_name && !c.unit_name.toLowerCase().includes(filterUnit.toLowerCase())) {
          return false;
        }
        if (filterStatus !== 'All' && c.status_badge && c.status_badge.toLowerCase() !== filterStatus.toLowerCase()) {
          return false;
        }
        if (searchQuery) {
          const q = searchQuery.toLowerCase();
          const caseMatch = c.case_number && c.case_number.toLowerCase().includes(q);
          const susMatch = c.suspect_name && c.suspect_name.toLowerCase().includes(q);
          const offMatch = c.submitting_officer && c.submitting_officer.toLowerCase().includes(q);
          const locMatch = c.raid_location && c.raid_location.toLowerCase().includes(q);
          if (!caseMatch && !susMatch && !offMatch && !locMatch) return false;
        }
        return true;
      });
    }

    function renderInvoicesList() {
      const container = document.getElementById('invoicesListContainer');
      if (!container) return;

      const filtered = getFilteredCases();

      const countBadge = document.getElementById('listCasesCount');
      if (countBadge) countBadge.textContent = `${filtered.length} Dockets Active`;

      if (filtered.length === 0) {
        container.innerHTML = `
          <div class="text-center py-10 px-4 rounded-xl border border-dashed border-slate-700/60 text-slate-400">
            <i data-lucide="folder-search" class="w-6 h-6 mx-auto mb-2 text-slate-500"></i>
            <p class="text-xs font-semibold">No forensic cases match filters</p>
            <button onclick="resetFilters()" class="mt-2 text-[10px] text-slate-300 hover:underline">Reset filters</button>
          </div>
        `;
        const pagBar = document.getElementById('casesPaginationBar');
        if (pagBar) pagBar.innerHTML = '';
        lucide.createIcons();
        return;
      }

      const totalPages = Math.ceil(filtered.length / casesPerPage) || 1;
      if (currentCasesPage > totalPages) currentCasesPage = totalPages;
      if (currentCasesPage < 1) currentCasesPage = 1;

      const startIndex = (currentCasesPage - 1) * casesPerPage;
      const paginatedCases = filtered.slice(startIndex, startIndex + casesPerPage);

      container.innerHTML = paginatedCases.map(c => {
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        const isOnRadar = (selectedRegionName && c.region && c.region.toLowerCase() === selectedRegionName.toLowerCase());

        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : (isOnRadar ? 'bg-slate-800/90 hover:bg-slate-800 text-slate-100 border border-indigo-500/40' : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5');
        
        const caseStatusText = c.case_status || c.status_badge || 'Under Investigation';
        const stLower = caseStatusText.toLowerCase();
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (stLower.includes('approved') || stLower.includes('court') || stLower.includes('closed') || stLower.includes('complete')
              ? 'bg-emerald-500/20 text-emerald-300' 
              : (stLower.includes('pending') || stLower.includes('awaiting')
                  ? 'bg-amber-500/20 text-amber-300' 
                  : 'bg-cyan-500/20 text-cyan-300'));

        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        // Render stacked avatars if multiple suspects
        let avatarMarkup = '';
        if (isMulti) {
          avatarMarkup = '<div class="flex -space-x-2 overflow-hidden flex-shrink-0">' +
            c.suspects.slice(0, 3).map(function(s) {
              const sInit = s.name ? s.name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2) : 'S';
              const imgHtml = s.mugshot_front ? '<img src="' + s.mugshot_front + '" alt="" class="w-full h-full object-cover">' : sInit;
              return '<div class="w-8 h-8 rounded-full overflow-hidden bg-slate-700 ring-2 ring-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-xs" title="' + (s.name || '') + ' (' + (s.role || '') + ')">' + imgHtml + '</div>';
            }).join('') +
            '</div>';
        } else {
          avatarMarkup = `
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
              ${c.mugshot_front ? `<img src="${c.mugshot_front}" alt="" class="w-full h-full object-cover">` : initials}
            </div>
          `;
        }

        // Region Radar Badge
        const regionBadgeHtml = isOnRadar
          ? `<span class="px-1.5 py-0.2 rounded bg-indigo-500/40 text-indigo-200 border border-indigo-400/50 font-bold font-mono-code text-[8.5px]"><i data-lucide="map-pin" class="w-3 h-3 inline text-slate-400"></i> ${c.region || 'Greater Accra'} (ON RADAR)</span>`
          : `<span class="text-[9.5px] ${isSelected ? 'text-indigo-100' : 'text-slate-400'} truncate"><i data-lucide="map-pin" class="w-3 h-3 inline text-slate-400"></i> ${c.region || 'Greater Accra'}</span>`;

        // Render suspect names
        let suspectsDisplay = '';
        if (isMulti) {
          const namesHtml = c.suspects.map(function(s, idx) {
            const isPrim = (idx === 0) ? 'underline decoration-indigo-300' : '';
            return '<span class="' + isPrim + '">' + (s.name || 'Suspect') + '</span>';
          }).join(' & ');

          suspectsDisplay = `
            <div class="text-[10.5px] font-bold ${isSelected ? 'text-white' : 'text-slate-100'} truncate">
              <i data-lucide="users" class="w-3.5 h-3.5 inline text-slate-400"></i> ${namesHtml}
            </div>
            <div class="text-[9.5px] truncate flex items-center space-x-1 mt-0.5">
              <span class="px-1.5 py-0.2 rounded bg-indigo-500/30 text-indigo-200 font-mono-code font-bold">${c.suspects.length} Suspects</span>
              <span>•</span>
              ${regionBadgeHtml}
            </div>
          `;
        } else {
          suspectsDisplay = `
            <div class="text-[10px] font-bold ${isSelected ? 'text-white' : 'text-slate-200'} truncate">
              <i data-lucide="user" class="w-3.5 h-3.5 inline text-slate-400"></i> ${c.suspect_name}
            </div>
            <div class="mt-0.5 truncate">
              ${regionBadgeHtml}
            </div>
          `;
        }

        return `
          <div onclick="selectInvoiceById('${c.case_number}', true)" 
               class="p-2.5 rounded-2xl cursor-pointer transition-all flex items-center justify-between gap-2.5 ${activeClasses} group">
            
            <div class="flex items-center space-x-2.5 min-w-0">
              ${avatarMarkup}
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-xs font-mono-code">${c.case_number}</span>
                  ${isMulti ? '<span class="text-[8.5px] px-1 py-0.2 bg-indigo-400/30 text-indigo-200 rounded font-bold">MULTI</span>' : ''}
                </div>
                ${suspectsDisplay}
              </div>
            </div>

            <div class="flex items-center flex-shrink-0 text-right">
              <span class="text-[10px] px-2 py-0.5 rounded-full font-mono-code font-bold ${statusBadgeClass}">
                ${caseStatusText}
              </span>
            </div>

          </div>
        `;
      }).join('');

      renderCasesPagination(filtered.length, totalPages);
      lucide.createIcons();
    }

    function renderCasesPagination(totalItems, totalPages) {
      const pagBar = document.getElementById('casesPaginationBar');
      if (!pagBar) return;

      if (totalItems === 0) {
        pagBar.innerHTML = '';
        return;
      }

      const startItem = (currentCasesPage - 1) * casesPerPage + 1;
      const endItem = Math.min(currentCasesPage * casesPerPage, totalItems);

      const isPrevDisabled = currentCasesPage <= 1;
      const isNextDisabled = currentCasesPage >= totalPages;

      let pagesHtml = '';
      if (totalPages > 1) {
        const pagesToRender = [];
        if (totalPages <= 5) {
          for (let i = 1; i <= totalPages; i++) pagesToRender.push(i);
        } else {
          pagesToRender.push(1);
          let left = Math.max(2, currentCasesPage - 1);
          let right = Math.min(totalPages - 1, currentCasesPage + 1);
          if (currentCasesPage <= 2) {
            right = 3;
          } else if (currentCasesPage >= totalPages - 1) {
            left = totalPages - 2;
          }
          if (left > 2) pagesToRender.push('...');
          for (let i = left; i <= right; i++) pagesToRender.push(i);
          if (right < totalPages - 1) pagesToRender.push('...');
          pagesToRender.push(totalPages);
        }

        pagesHtml = '<div class="flex items-center space-x-1">' + pagesToRender.map(p => {
          if (p === '...') {
            return '<span class="px-1 text-slate-500 text-[10px]">…</span>';
          }
          const isActive = (p === currentCasesPage);
          return `<button type="button" onclick="changeCasesPage(${p})" class="w-6 h-6 flex items-center justify-center rounded text-[11px] font-mono-code transition-all ${isActive ? 'bg-[#5c56df] text-white font-bold shadow-md shadow-indigo-500/30' : 'bg-slate-800/60 hover:bg-slate-700/80 text-slate-400 hover:text-slate-200 border border-slate-700/40'}">${p}</button>`;
        }).join('') + '</div>';
      }

      const prevBtn = isPrevDisabled
        ? `<button type="button" disabled class="px-2 py-1 rounded bg-slate-900/50 text-slate-600 border border-slate-800/80 cursor-not-allowed opacity-50 flex items-center space-x-1 text-[11px] font-mono-code"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i><span class="hidden sm:inline">Prev</span></button>`
        : `<button type="button" onclick="changeCasesPage(${currentCasesPage - 1})" class="px-2 py-1 rounded bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 hover:text-white border border-slate-700/60 transition-colors flex items-center space-x-1 cursor-pointer text-[11px] font-mono-code"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i><span class="hidden sm:inline">Prev</span></button>`;

      const nextBtn = isNextDisabled
        ? `<button type="button" disabled class="px-2 py-1 rounded bg-slate-900/50 text-slate-600 border border-slate-800/80 cursor-not-allowed opacity-50 flex items-center space-x-1 text-[11px] font-mono-code"><span class="hidden sm:inline">Next</span><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>`
        : `<button type="button" onclick="changeCasesPage(${currentCasesPage + 1})" class="px-2 py-1 rounded bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 hover:text-white border border-slate-700/60 transition-colors flex items-center space-x-1 cursor-pointer text-[11px] font-mono-code"><span class="hidden sm:inline">Next</span><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>`;

      pagBar.innerHTML = `
        <div class="text-[11px] text-slate-400 font-mono-code truncate">
          <span class="text-slate-500">Showing</span> <strong class="text-slate-200">${startItem}–${endItem}</strong> <span class="text-slate-500">of</span> <strong class="text-slate-200">${totalItems}</strong>
        </div>
        <div class="flex items-center space-x-1.5">
          ${prevBtn}
          ${pagesHtml}
          ${nextBtn}
        </div>
      `;
    }

    function changeCasesPage(newPage) {
      currentCasesPage = newPage;
      renderInvoicesList();
      const container = document.getElementById('invoicesListContainer');
      if (container) container.scrollTop = 0;
    }

    // Render live docket stream in tactical theatre mode
    function renderTacticalCasesStream() {
      const container = document.getElementById('tacCasesListContainer');
      if (!container) return;

      container.innerHTML = liveCases.map(c => {
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        return `
          <div onclick="selectInvoiceById('${c.case_number}', true);" 
               class="p-2 rounded-xl ${isSelected ? 'bg-indigo-600/50 border-indigo-400 ring-1 ring-indigo-400 text-white' : 'bg-slate-900/90 hover:bg-emerald-500/20 border-emerald-500/20 text-slate-300'} border cursor-pointer transition-colors flex items-center justify-between text-xs font-mono-code">
            <div class="flex items-center space-x-2 truncate">
              <div class="truncate">
                <span class="font-bold text-white">${c.case_number}</span>
                <span class="text-[10px] ${isSelected ? 'text-indigo-200 font-semibold' : 'text-slate-400'} block">${c.suspect_name} • ${c.region}</span>
              </div>
            </div>
            <span class="text-[10px] px-1.5 py-0.5 rounded ${isSelected ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-300'} font-bold">${c.status_badge}</span>
          </div>
        `;
      }).join('');
    }

    // ──────────────────────────────────────────────────────────
    // 4. RENDER FORENSIC DOSSIER DETAILS CARD (Right Panel)
    // ──────────────────────────────────────────────────────────
    let dossierSelectedSuspectId = 'all';

    function setDossierSuspect(suspectId) {
      dossierSelectedSuspectId = suspectId;
      const c = liveCases.find(function(item) { return item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId); });
      if (c) {
        renderSuspectsProfile(c);
        renderDossierExhibits(c);
      }
    }

    function renderSuspectsProfile(c) {
      const container = document.getElementById('cardSuspectsProfileContainer');
      if (!container) return;

      const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;

      if (!isMulti) {
        const s = (c.suspects && c.suspects.length > 0) ? c.suspects[0] : {
          name: c.suspect_name || 'Kofi Mensah',
          role: c.suspect_role || 'Primary Target',
          ghana_card: c.ghana_card || 'GHA-XXXXXXXXX-X',
          nationality: c.nationality || 'Ghanaian',
          age: c.age || '34 yrs',
          gender: c.gender || 'Male',
          mugshot_front: c.mugshot_front
        };
        const initials = s.name ? s.name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2) : 'KM';

        container.innerHTML = `
          <div class="flex items-center space-x-3 bg-white/5 p-2.5 sm:p-3 rounded-2xl border border-white/5">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-slate-700 overflow-hidden shrink-0 flex items-center justify-center text-white font-bold text-xs sm:text-sm ring-1 ring-white/20">
              ${s.mugshot_front ? `<img src="${s.mugshot_front}" class="w-full h-full object-cover" alt="">` : initials}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider truncate">${s.role}</span>
                <span class="text-[10px] font-mono-code text-slate-400 truncate shrink-0">${s.ghana_card || ''}</span>
              </div>
              <div class="text-xs sm:text-sm font-extrabold text-white truncate mt-0.5">${s.name}</div>
              <div class="text-[10px] text-slate-300 truncate">${[s.gender, s.age, s.nationality].filter(Boolean).join(' • ')}</div>
            </div>
          </div>
        `;
        return;
      }

      // MULTIPLE SUSPECTS CASE: Show suspect selection bar + cards
      let selectorHtml = '<div class="flex items-center space-x-1.5 mb-2.5 overflow-x-auto pb-1 max-w-full scrollbar-none">' +
        '<button type="button" data-suspect="all" onclick="setDossierSuspect(this.dataset.suspect)" class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ' +
        (dossierSelectedSuspectId === 'all' ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20') +
        '">ALL TARGETS (' + c.suspects.length + ')</button>' +
        c.suspects.map(function(s) {
          const isAct = String(dossierSelectedSuspectId) === String(s.id);
          const cls = isAct ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20';
          const shortRole = (s.role || '').replace(' Suspect', '');
          const firstName = (s.name || '').split(' ')[0];
          return '<button type="button" data-suspect="' + s.id + '" onclick="setDossierSuspect(this.dataset.suspect)" class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ' + cls + '">' +
            firstName + ' <span class="text-[9px] opacity-75 font-normal">(' + shortRole + ')</span></button>';
        }).join('') +
        '</div>';

      let cardsHtml = '';
      if (dossierSelectedSuspectId === 'all') {
        cardsHtml = '<div class="space-y-2">' +
          c.suspects.map(function(s) {
            const initials = s.name ? s.name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2) : 'S';
            const roleColor = (s.role || '').includes('Primary') ? 'text-indigo-300 font-bold' : 'text-slate-400';
            const details = [s.gender, s.age, s.nationality].filter(Boolean).join(' • ');
            const imgHtml = s.mugshot_front ? '<img src="' + s.mugshot_front + '" class="w-full h-full object-cover" alt="">' : initials;
            return '<div class="flex items-center space-x-3 bg-white/5 p-2 sm:p-2.5 rounded-2xl border border-white/5 hover:border-white/15 transition-all">' +
              '<div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-700 overflow-hidden shrink-0 flex items-center justify-center text-white font-bold text-xs ring-1 ring-white/20">' +
              imgHtml +
              '</div>' +
              '<div class="min-w-0 flex-1">' +
              '<div class="flex items-center justify-between gap-1">' +
              '<span class="text-[9px] font-mono-code ' + roleColor + ' uppercase truncate">' + (s.role || 'Suspect') + '</span>' +
              '<span class="text-[9px] font-mono-code text-slate-400 truncate shrink-0">' + (s.ghana_card || '') + '</span>' +
              '</div>' +
              '<div class="text-xs font-extrabold text-white truncate mt-0.5">' + (s.name || '') + '</div>' +
              '<div class="text-[10px] text-slate-300 truncate">' + details + '</div>' +
              '</div>' +
              '</div>';
          }).join('') +
          '</div>';
      } else {
        const s = c.suspects.find(function(item) { return String(item.id) === String(dossierSelectedSuspectId); }) || c.suspects[0];
        const initials = s.name ? s.name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2) : 'S';
        const details = [s.gender, s.age, s.nationality].filter(Boolean).join(' • ');
        cardsHtml = `
          <div class="flex items-center space-x-3 bg-white/5 p-2.5 sm:p-3 rounded-2xl border border-white/5">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-slate-700 overflow-hidden shrink-0 flex items-center justify-center text-white font-bold text-xs sm:text-sm ring-1 ring-white/20">
              ${s.mugshot_front ? `<img src="${s.mugshot_front}" class="w-full h-full object-cover" alt="">` : initials}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider truncate">${s.role}</span>
                <span class="text-[10px] font-mono-code text-slate-400 truncate shrink-0">${s.ghana_card || ''}</span>
              </div>
              <div class="text-xs sm:text-sm font-extrabold text-white truncate mt-0.5">${s.name}</div>
              <div class="text-[10px] text-slate-300 truncate">${details}</div>
            </div>
          </div>
        `;
      }

      container.innerHTML = selectorHtml + cardsHtml;
    }

    function renderDossierExhibits(c) {
      const grid = document.getElementById('exhibitCardsGrid');
      if (!grid) return;

      let allExhibits = (c.exhibits && c.exhibits.length > 0) ? c.exhibits : [];
      let filteredExhibits = allExhibits;
      let filterLabel = '';

      if (c.is_multiple && c.suspects && dossierSelectedSuspectId !== 'all') {
        const targetSuspect = c.suspects.find(function(s) { return String(s.id) === String(dossierSelectedSuspectId); });
        if (targetSuspect) {
          filterLabel = '(' + targetSuspect.name.split(' ')[0] + ')';
          filteredExhibits = allExhibits.filter(function(ex) {
            // Strictly check ownership - do NOT associate exhibits that belong to someone else
            if (ex.suspect_id && String(ex.suspect_id) === String(targetSuspect.id)) return true;
            if (ex.associated_person) {
              const ap = ex.associated_person.toLowerCase();
              if (ap.includes(targetSuspect.name.toLowerCase()) || ap === 'all suspects') {
                return true;
              }
            }
            return false;
          });
        }
      }

      const elSub = document.getElementById('cardSubTotal');
      if (elSub) elSub.textContent = `${filteredExhibits.length} Exhibits ${filterLabel}`;

      if (filteredExhibits.length === 0) {
        grid.innerHTML = `
          <div class="col-span-1 sm:col-span-2 py-4 sm:py-5 px-3 text-center rounded-xl bg-white/5 border border-dashed border-white/10 text-slate-400 text-xs font-mono-code">
            <i data-lucide="shield-alert" class="w-5 h-5 mx-auto mb-1 text-slate-500"></i>
            <span>No exhibits associated with this target</span>
          </div>
        `;
        lucide.createIcons();
        return;
      }

      let cardsHtml = filteredExhibits.map(function(ex) {
        const ownerName = ex.associated_person || (c.is_multiple ? 'All Suspects' : c.suspect_name);
        return `
          <div class="bg-white/10 hover:bg-white/15 rounded-xl p-2.5 sm:p-3 border border-white/10 transition-colors flex flex-col justify-between min-w-0">
            <div class="flex items-center justify-between gap-1 text-slate-300">
              <span class="text-xs font-mono-code font-black text-indigo-200 truncate">${ex.quantity || '1 Unit'}</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded bg-white/10 text-emerald-300 font-mono-code font-bold truncate shrink-0 max-w-[90px]">${ex.category || 'Evidence'}</span>
            </div>
            <div class="text-[11px] text-white font-bold truncate mt-1.5" title="${ex.title}">${ex.title}</div>
            <div class="mt-2 pt-1.5 border-t border-white/10 flex items-center justify-between text-[9px] font-mono-code gap-1">
              <span class="text-indigo-200 truncate flex items-center space-x-1 min-w-0" title="Belongs to: ${ownerName}">
                <i data-lucide="user-check" class="w-3 h-3 text-slate-300 shrink-0"></i>
                <span class="truncate">${ownerName}</span>
              </span>
              <span class="text-slate-300 font-bold shrink-0 ml-1">${ex.value || ''}</span>
            </div>
          </div>
        `;
      }).join('');

      cardsHtml += `
        <a href="/cases/${c.id || ''}/edit" class="rounded-xl p-3 border border-dashed border-white/20 hover:border-indigo-400 bg-white/5 hover:bg-white/10 transition-all flex flex-col items-center justify-center text-center group">
          <div class="w-5 h-5 rounded-full bg-white/10 group-hover:bg-[#5c56df] flex items-center justify-center mb-1 text-white transition-colors">
            <i data-lucide="plus" class="w-3 h-3"></i>
          </div>
          <span class="text-[10px] font-bold text-slate-300 group-hover:text-white">Log Exhibit</span>
        </a>
      `;

      grid.innerHTML = cardsHtml;
      lucide.createIcons();
    }

    function renderDossierCard(c) {
      if (!c) return;

      const elInv = document.getElementById('cardInvoiceNumber');
      if (elInv) elInv.textContent = c.case_number || '# NCC-2026-2572';

      const elBadge = document.getElementById('cardInvoiceStatusBadge');
      if (elBadge) elBadge.textContent = c.status_badge || 'Under Investigation';

      const elComp = document.getElementById('cardCompanyName');
      if (elComp) elComp.textContent = c.unit_name || 'Crime Scene Support Unit';

      const elOfficer = document.getElementById('cardOfficerName');
      if (elOfficer) elOfficer.textContent = c.submitting_officer || 'Insp. K. Mensah';

      const elLoc = document.getElementById('cardLocationName');
      if (elLoc) elLoc.textContent = (c.raid_location ? c.raid_location.substring(0, 26) : 'Kotoka Airport');

      const exhCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 3);

      const elTot = document.getElementById('cardTotal');
      if (elTot) elTot.textContent = `${exhCount} Items Logged`;

      const elBal = document.getElementById('cardBalanceDue');
      if (elBal) elBal.textContent = (c.status_badge === 'Approved' ? 'Court Order Signed' : 'Judicial Review Active');

      const elPrimary = document.getElementById('cardActionPrimary');
      if (elPrimary && c.id) elPrimary.href = `/cases/${c.id}/review`;

      const elLink = document.getElementById('cardActionLink');
      if (elLink && c.id) elLink.href = `/cases/${c.id}/pdf`;

      // Reset suspect filter to 'all' on case change and render
      dossierSelectedSuspectId = 'all';
      renderSuspectsProfile(c);
      renderDossierExhibits(c);
    }

    function selectInvoiceById(caseRef, centerMap = true) {
      const c = liveCases.find(item => item.case_number === caseRef || String(item.id) === String(caseRef));
      if (!c) return;

      selectedCaseId = c.case_number;

      // Auto-navigate pagination to the page containing this selected case
      const filtered = getFilteredCases();
      const caseIdx = filtered.findIndex(item => item.case_number === c.case_number || String(item.id) === String(c.id));
      if (caseIdx !== -1) {
        currentCasesPage = Math.floor(caseIdx / casesPerPage) + 1;
      }

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

      if (centerMap && c.region) {
        indicateCaseOnMap(c);
      }
    }

    function indicateCaseOnMap(c) {
      if (!c || !c.region) return;
      selectedRegionName = c.region;
      focusRegionOnMap(c.region, isTacticalModeOpen, c);
      renderDistrictMap(c.region, c);
      renderTacDistrictMap(c.region, c);
    }

    // ──────────────────────────────────────────────────────────
    // 5. 3D GHANA MAP ENGINE & REGION DRILL-DOWN ARCHITECTURE
    // ──────────────────────────────────────────────────────────
    function selectRegionByName(regName, fromTac = false) {
      selectedRegionName = regName;
      focusRegionOnMap(regName, fromTac);

      const matchedCase = liveCases.find(c => c.region && c.region.toLowerCase() === regName.toLowerCase());
      if (matchedCase) {
        selectInvoiceById(matchedCase.case_number, false);
      } else {
        renderDistrictMap(regName, null);
        renderTacDistrictMap(regName, null);
        renderInvoicesList();
      }
    }

    function focusRegionOnMap(regName, isTac = false, targetCase = null) {
      const r = regionsData[regName];
      if (!r) return;

      const bboxStr = regionViewBoxes[regName] || '0 0 800 960';

      // 1. Zoom BOTH Standard & Tactical SVG ViewBoxes into the Region's EXACT Boundaries
      const stdSvg = document.getElementById('ghanaMapSvg');
      const tacSvg = document.getElementById('tacGhanaMapSvg');
      if (stdSvg) stdSvg.setAttribute('viewBox', bboxStr);
      if (tacSvg) tacSvg.setAttribute('viewBox', bboxStr);

      // 2. Flatten 3D perspective to crystal-clear 2D top-down view on BOTH planes
      const stdPlane = document.getElementById('ghana3dPlane');
      const tacPlane = document.getElementById('tacGhana3dPlane');
      if (stdPlane) {
        stdPlane.style.transform = 'translate3d(0, 0, 0) scale(1) rotateX(0deg) rotateZ(0deg)';
        stdPlane.style.transition = 'transform 0.4s ease';
      }
      if (tacPlane) {
        tacPlane.style.transform = 'translate3d(0, 0, 0) scale(1) rotateX(0deg) rotateZ(0deg)';
        tacPlane.style.transition = 'transform 0.4s ease';
      }

      // 3. Hide other 15 regions and background extrusion on BOTH maps
      const stdExtrusion = document.getElementById('extrusionShadowPass');
      const tacExtrusion = document.getElementById('tacExtrusionShadowPass');
      if (stdExtrusion) stdExtrusion.style.display = 'none';
      if (tacExtrusion) tacExtrusion.style.display = 'none';

      const rings = document.getElementById('radarRingsGroup');
      if (rings) rings.style.display = 'none';

      const districts = districtsData[regName] || [];
      const regCases = (regionsData[regName] ? regionsData[regName].cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase()).length;
      
      // Determine if this region has the HIGHER number of cases across all regions
      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      });
      const isHotspot = (regCases === maxCaseload && maxCaseload > 0);

      // Hide all other regions and highlight active region on BOTH Standard and Tactical polygons
      ['.region-polygon', '.tactical-region-polygon'].forEach(selector => {
        document.querySelectorAll(selector).forEach(p => {
          if (p.getAttribute('data-name') === regName) {
            p.style.display = 'block';
            p.style.opacity = '1';
            p.classList.add('region-active');
            if (isHotspot) {
              p.classList.add('region-hotspot', 'tactical-region-hotspot');
            } else {
              p.classList.remove('region-hotspot', 'tactical-region-hotspot');
            }
          } else {
            p.style.display = 'none';
            p.classList.remove('region-active');
          }
        });
      });

      // 4. Hide national region labels on BOTH maps
      const stdLbls = document.getElementById('regionLabelsGroup');
      const tacLbls = document.getElementById('tacRegionLabelsGroup');
      if (stdLbls) stdLbls.style.display = 'none';
      if (tacLbls) tacLbls.style.display = 'none';

      // 5. Update Standard Navigation Header & Drawer
      const navHeader = document.getElementById('districtNavHeader');
      const drillTitle = document.getElementById('districtDrillTitle');
      const cornerTacBtn = document.getElementById('btnMapCornerTactical');
      const panHint = document.getElementById('mapPanZoomHint');

      if (cornerTacBtn) cornerTacBtn.classList.add('hidden');
      if (panHint) panHint.classList.add('hidden');

      const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);

      if (navHeader && drillTitle) {
        navHeader.classList.remove('hidden');
        if (activeCase && activeCase.region && activeCase.region.toLowerCase() === regName.toLowerCase()) {
          drillTitle.innerHTML = `<span class="text-pink-400 font-extrabold tracking-wide">TARGET RADAR:</span> ${regName.toUpperCase()} • <span class="text-white font-mono-code font-bold">${activeCase.case_number}</span> <span class="text-indigo-200 font-semibold">(${activeCase.suspect_name})</span>`;
        } else {
          drillTitle.textContent = `${regName.toUpperCase()} DISTRICT MAP (${districts.length} MMDAs • ${regCases} Cases)`;
        }
      }

      // Show organized MMDA Districts Drawer below map
      const drawer = document.getElementById('districtCardsDrawer');
      if (drawer) {
        drawer.classList.remove('hidden');
        const badge = document.getElementById('districtDrawerBadge');
        if (badge) {
          badge.textContent = `${regCases} Cases Linked`;
          badge.className = isHotspot 
            ? 'text-[10px] text-slate-300 font-bold bg-red-500/20 px-2 py-0.5 rounded border border-red-500/30' 
            : 'text-[10px] text-slate-300 font-bold bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30';
        }
      }

      // 6. Update Tactical Navigation Header, Left HUD & Bottom Deck
      const tacNav = document.getElementById('tacDistrictNavHeader');
      const tacTitle = document.getElementById('tacDistrictDrillTitle');
      const tacBadgeBox = document.getElementById('tacDistrictDrillTitleBadge');
      if (tacNav && tacTitle) {
        tacNav.classList.remove('hidden');
        if (activeCase && activeCase.region && activeCase.region.toLowerCase() === regName.toLowerCase()) {
          tacTitle.innerHTML = `<span class="text-pink-400 font-black">TARGET RADAR:</span> ${regName.toUpperCase()} • <span class="text-white font-mono-code font-bold">${activeCase.case_number}</span> (${activeCase.suspect_name}) • ${activeCase.raid_location || 'TACTICAL SECTOR'}`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-pink-950/90 text-white border border-pink-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        } else if (isHotspot) {
          tacTitle.innerHTML = `<span class="text-slate-300 font-black">HIGHER CASELOAD HOTSPOT:</span> ${regName.toUpperCase()} MMDA RADAR (${districts.length} DISTRICTS • ${regCases} DOCKETS)`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-red-950/90 text-white border border-red-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        } else {
          tacTitle.innerHTML = `<span class="text-slate-300 font-black">TACTICAL SECTOR:</span> ${regName.toUpperCase()} MMDA RADAR (${districts.length} DISTRICTS • ${regCases} DOCKETS)`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-slate-950/90 text-white border border-emerald-500/40 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        }
      }

      // Update Tactical Left HUD with Telemetry
      const tacName = document.getElementById('tacHudRegionName');
      if (tacName) tacName.textContent = regName.toUpperCase();
      const tacCap = document.getElementById('tacHudCapital');
      if (tacCap) tacCap.textContent = `Capital: ${r.capital || 'Ghana'}`;
      const tacDoc = document.getElementById('tacHudDockets');
      if (tacDoc) {
        tacDoc.textContent = isHotspot ? `${regCases} Dockets [HIGHER CASELOAD]` : `${regCases} Dockets Active`;
        tacDoc.className = isHotspot ? 'text-base font-black text-slate-300' : 'text-base font-black text-slate-300';
      }
      const tacIso = document.getElementById('tacHudIso');
      if (tacIso) {
        tacIso.textContent = r.iso || 'GH-RG';
        tacIso.className = isHotspot 
          ? 'text-[10px] font-mono-code text-red-300 bg-red-500/20 px-2 py-0.5 rounded border border-red-500/40 font-bold'
          : 'text-[10px] font-mono-code text-slate-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20';
      }
      const tacCmd = document.getElementById('tacHudCommander');
      if (tacCmd) tacCmd.textContent = r.commander || 'Regional Forensic Command';
      const tacStations = document.getElementById('tacHudStations');
      if (tacStations) tacStations.textContent = `${districts.length} Districts`;

      // Show Tactical MMDA Districts Deck Drawer
      const tacDeck = document.getElementById('tacDistrictDeckDrawer');
      if (tacDeck) tacDeck.classList.remove('hidden');

      // 7. Render Districts on BOTH Standard and Tactical Maps
      renderDistrictMap(regName, targetCase);
      renderTacDistrictMap(regName, targetCase);
    }

    function renderDistrictMap(regName, targetCase = null) {
      const group = document.getElementById('districtLayersGroup');
      const drawerGrid = document.getElementById('districtDrawerGrid');
      if (!group) return;

      const activeCase = targetCase || (selectedCaseId ? liveCases.find(item => item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId)) : null);

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      // Match cases to districts using keyword matching
      const districtWithCases = districts.map(d => {
        const linked = regionCases.filter(c => {
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return d.keywords.some(kw => locText.includes(kw.toLowerCase()));
        });
        return { district: d, cases: linked };
      });

      // Fallback: If region cases have no keyword match, assign to capital/first MMDA district
      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {
        districtWithCases[0].cases.push(...unassigned);
      }

      // 1. Render Clean Rectangular MMDA Badges (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;
        const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

        if (isTarget) {
          return `
            <g class="district-node cursor-pointer group active-target-district" onclick="event.stopPropagation(); selectInvoiceById('${activeCase.case_number}', false);">
              <rect x="${d.x - 29}" y="${d.y - 7.5}" width="58" height="15" rx="3" fill="#1b0716" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 6px rgba(244,63,94,0.6))" />
              <text x="${d.x}" y="${d.y - 1.8}" text-anchor="middle" font-size="3.7" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${d.short_name.toUpperCase()}
              </text>
              <text x="${d.x}" y="${d.y + 4.6}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${activeCase.case_number} • ${activeCase.suspect_name.split(' ')[0]}
              </text>
            </g>
          `;
        } else if (hasCases) {
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById('${primaryCase.case_number}', false);">
              <rect x="${d.x - 26}" y="${d.y - 6.0}" width="52" height="12" rx="2" fill="#181926" stroke="#ef4444" stroke-width="0.9" />
              <text x="${d.x}" y="${d.y - 1.2}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [${d.code}] ${d.short_name}
              </text>
              <text x="${d.x}" y="${d.y + 4.2}" text-anchor="middle" font-size="3.0" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${primaryCase.case_number}
              </text>
            </g>
          `;
        } else {
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <rect x="${d.x - 22}" y="${d.y - 5.0}" width="44" height="10" rx="1.5" fill="#1e293b" stroke="#475569" stroke-width="0.5" class="group-hover:stroke-indigo-400 transition-colors" />
              <text x="${d.x}" y="${d.y - 0.7}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#cbd5e1" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${d.code}] ${d.short_name}
              </text>
              <text x="${d.x}" y="${d.y + 3.6}" text-anchor="middle" font-size="2.6" font-weight="medium" fill="#94a3b8" font-family="'JetBrains Mono', monospace" class="select-none">
                CAP: ${d.capital.toUpperCase()}
              </text>
            </g>
          `;
        }
      }).join('');

      // 2. Render Spacious, Elegant MMDA District Cards in the Bottom Drawer
      if (drawerGrid) {
        drawerGrid.innerHTML = districtWithCases.map(item => {
          const d = item.district;
          const linked = item.cases;
          const hasCases = linked.length > 0;
          const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

          if (isTarget) {
            return `
              <div id="drawer-card-${d.code}" onclick="selectInvoiceById('${activeCase.case_number}', false)"
                   class="p-2.5 rounded-xl bg-gradient-to-r from-pink-950/90 to-indigo-950/90 border-2 border-pink-500 cursor-pointer transition-all flex items-center justify-between gap-2 shadow-lg shadow-pink-500/25 ring-2 ring-pink-400/50 scale-[1.01] group">
                <div class="truncate">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-extrabold text-white text-[11px] truncate">[${d.code}] ${d.short_name}</span>
                  </div>
                  <div class="text-[10px] text-pink-200 font-mono-code font-bold truncate mt-0.5">
                    ${activeCase.case_number} • ${activeCase.suspect_name} (${activeCase.exhibits_count || 1} Exh)
                  </div>
                </div>
                <span class="text-[9px] px-2 py-0.5 rounded bg-pink-500 text-white font-black tracking-wider flex-shrink-0">
                  TARGET
                </span>
              </div>
            `;
          } else if (hasCases) {
            const c = linked[0];
            return `
              <div id="drawer-card-${d.code}" onclick="selectInvoiceById('${c.case_number}', false)"
                   class="p-2 rounded-xl bg-red-950/40 hover:bg-red-900/60 border border-red-500/50 cursor-pointer transition-all flex items-center justify-between gap-2 shadow-sm group">
                <div class="truncate">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-bold text-white text-[11px] truncate">[${d.code}] ${d.short_name}</span>
                  </div>
                  <div class="text-[10px] text-red-300 font-mono-code truncate mt-0.5">
                    ${c.case_number} • ${c.suspect_name} (${c.exhibits_count || 1} Exh)
                  </div>
                </div>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-500 text-white font-extrabold flex-shrink-0">
                  HOTSPOT
                </span>
              </div>
            `;
          } else {
            return `
              <div id="drawer-card-${d.code}" class="p-2 rounded-xl bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800 text-slate-300 transition-all flex items-center justify-between gap-2">
                <div class="truncate">
                  <div class="font-bold text-slate-200 text-[11px] truncate">[${d.code}] ${d.short_name}</div>
                  <div class="text-[9.5px] text-slate-500 truncate mt-0.5">Capital: ${d.capital}</div>
                </div>
                <span class="text-[9px] text-slate-400 font-mono-code flex-shrink-0">0 Dockets</span>
              </div>
            `;
          }
        }).join('');

        // Smooth scroll active target card into view if exists
        setTimeout(() => {
          const targetCard = drawerGrid.querySelector('.border-pink-500');
          if (targetCard && typeof targetCard.scrollIntoView === 'function') {
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
          }
        }, 50);
      }
    }

    // Renders Tactical Radar MMDA Districts & Hotspots with High-Clarity Labels & Details
    function renderTacDistrictMap(regName, targetCase = null) {
      const group = document.getElementById('tacDistrictLayersGroup');
      if (!group) return;

      const activeCase = targetCase || (selectedCaseId ? liveCases.find(item => item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId)) : null);

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      const districtWithCases = districts.map(d => {
        const linked = regionCases.filter(c => {
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return (d.keywords || []).some(kw => locText.includes(kw.toLowerCase()));
        });
        return { district: d, cases: linked };
      });

      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {
        districtWithCases[0].cases.push(...unassigned);
      }

      // 1. Populate Tactical Left HUD MMDA list (Safe without null crashes)
      const tacDistList = document.getElementById('tacHudDistrictsList');
      if (tacDistList) {
        tacDistList.innerHTML = districtWithCases.map(item => {
          const d = item.district;
          const count = item.cases.length;
          const isTarget = activeCase && item.cases.some(c => c.case_number === activeCase.case_number);
          const isRed = count > 0;
          const firstCase = isTarget ? activeCase : (isRed ? item.cases[0] : null);
          const clickAttr = firstCase ? `onclick="selectInvoiceById('${firstCase.case_number}', false);"` : '';

          let cardCls = 'bg-slate-900/70 border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/30 text-slate-300';
          let badgeMarkup = '<span class="text-[9px] font-mono-code font-bold text-slate-300 bg-emerald-500/10 px-1.5 py-0.5 rounded">0 Active</span>';

          if (isTarget) {
            cardCls = 'bg-gradient-to-r from-pink-950/90 to-red-950/90 border-2 border-pink-500 text-pink-100 shadow-lg shadow-pink-500/20';
            badgeMarkup = '<span class="text-[9px] font-mono-code font-black text-white bg-pink-500 px-2 py-0.5 rounded">TARGET</span>';
          } else if (isRed) {
            cardCls = 'bg-red-950/70 border-red-500/60 hover:bg-red-900/60 text-red-200';
            badgeMarkup = `<span class="text-[9px] font-mono-code font-bold text-slate-300 bg-red-500/20 px-1.5 py-0.5 rounded">${count} DOCKET</span>`;
          }

          return `
            <div ${clickAttr} class="p-2 rounded-xl border cursor-pointer transition-all ${cardCls}">
              <div class="flex items-center justify-between">
                <span class="font-bold text-white text-[11px] truncate">${isTarget ? '' : ''}[${d.code}] ${d.short_name}</span>
                ${badgeMarkup}
              </div>
              <div class="text-[9.5px] text-slate-400 truncate mt-0.5">Cap: ${d.capital}</div>
              ${firstCase ? `<div class="text-[9.5px] ${isTarget ? 'text-pink-200 font-extrabold' : 'text-red-300 font-bold'} truncate mt-0.5"><i data-lucide="user" class="w-3 h-3 inline text-slate-400"></i> ${firstCase.suspect_name} (${firstCase.case_number})</div>` : ''}
            </div>
          `;
        }).join('');
      }

      // 2. Populate Bottom Tactical MMDA Districts Deck Drawer
      const deckGrid = document.getElementById('tacDistrictDeckGrid');
      const deckBadge = document.getElementById('tacDistrictDeckBadge');
      if (deckBadge) {
        deckBadge.textContent = `${districts.length} MMDA DISTRICTS • ${regionCases.length} DOCKETS`;
      }
      if (deckGrid) {
        deckGrid.innerHTML = districtWithCases.map(item => {
          const d = item.district;
          const linked = item.cases;
          const count = linked.length;
          const hasCases = count > 0;
          const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

          if (isTarget) {
            return `
              <div onclick="selectInvoiceById('${activeCase.case_number}', false)"
                   class="p-2.5 rounded-xl bg-gradient-to-r from-pink-950/90 to-red-950/90 border-2 border-pink-500 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-lg shadow-pink-500/20 scale-[1.01] group">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-black text-white text-[11px] truncate">[${d.code}] ${d.short_name}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-pink-500 text-white font-black flex-shrink-0">TARGET</span>
                </div>
                <div class="text-[10px] text-pink-200 font-extrabold truncate mt-0.5">
                  ${activeCase.case_number} • ${activeCase.suspect_name}
                </div>
                <div class="text-[9px] text-pink-300/80 truncate flex items-center justify-between mt-0.5">
                  <span>Location: ${activeCase.raid_location || d.capital}</span>
                  <span class="text-pink-200 font-bold">${activeCase.exhibits_count || 1} Exhibit(s)</span>
                </div>
              </div>
            `;
          } else if (hasCases) {
            const c = linked[0];
            return `
              <div onclick="selectInvoiceById('${c.case_number}', false)"
                   class="p-2.5 rounded-xl bg-red-950/60 hover:bg-red-900/80 border border-red-500/70 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-lg group">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-bold text-white text-[11px] truncate">[${d.code}] ${d.short_name}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-red-600 text-white font-black flex-shrink-0">HOTSPOT</span>
                </div>
                <div class="text-[10px] text-red-200 font-bold truncate">
                  ${c.case_number} • ${c.suspect_name}
                </div>
                <div class="text-[9px] text-slate-300 truncate flex items-center justify-between">
                  <span>Cap: ${d.capital}</span>
                  <span class="text-red-300 font-bold">${c.exhibits_count || 1} Exhibit(s)</span>
                </div>
              </div>
            `;
          } else {
            return `
              <div class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-slate-800/90 border border-slate-700/80 hover:border-emerald-500/50 text-slate-300 transition-all flex flex-col justify-between gap-1">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-bold text-slate-100 text-[11px] truncate">[${d.code}] ${d.short_name}</span>
                  <span class="text-[9px] text-slate-400 font-mono-code flex-shrink-0">0 Dockets</span>
                </div>
                <div class="text-[9.5px] text-slate-400 truncate">
                  Capital: ${d.capital}
                </div>
                <div class="text-[9px] text-slate-300 font-medium truncate">
                  Patrol Sector Clear
                </div>
              </div>
            `;
          }
        }).join('');
      }

      // 3. Render High-Clarity 2-Line Tactical SVG Markers on Map with Robust Code-Based Hover
      group.innerHTML = districtWithCases.map(item => {
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;
        const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

        if (isTarget) {
          return `
            <g class="tac-district-marker cursor-pointer group active-target-tac" 
               onclick="event.stopPropagation(); selectInvoiceById('${activeCase.case_number}', false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${d.code}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${d.x - 30}" y="${d.y - 8.0}" width="60" height="16" rx="3" fill="#20051a" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 8px rgba(244,63,94,0.7))" />
              <text x="${d.x}" y="${d.y - 2.0}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${d.short_name.toUpperCase()}
              </text>
              <text x="${d.x}" y="${d.y + 4.8}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${activeCase.case_number} • ${activeCase.suspect_name.split(' ')[0]}
              </text>
            </g>
          `;
        } else if (hasCases) {
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation(); selectInvoiceById('${c.case_number}', false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${d.code}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${d.x - 28}" y="${d.y - 6.5}" width="56" height="13" rx="2" fill="#120404" stroke="#ef4444" stroke-width="1.2" />
              <text x="${d.x}" y="${d.y - 1.2}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${d.short_name.toUpperCase()}
              </text>
              <text x="${d.x}" y="${d.y + 4.5}" text-anchor="middle" font-size="3.1" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${c.case_number} • ${c.suspect_name}
              </text>
            </g>
          `;
        } else {
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation();"
               onmouseenter="onTacDistrictHoverByCode(event, '${d.code}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${d.x - 23}" y="${d.y - 5.0}" width="46" height="10" rx="1.8" fill="#041611" stroke="#10b981" stroke-width="0.7" class="group-hover:stroke-emerald-300 transition-colors" />
              <text x="${d.x}" y="${d.y - 0.7}" text-anchor="middle" font-size="3.3" font-weight="bold" fill="#a7f3d0" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${d.code}] ${d.short_name}
              </text>
              <text x="${d.x}" y="${d.y + 3.6}" text-anchor="middle" font-size="2.6" font-weight="medium" fill="#6ee7b7" opacity="0.85" font-family="'JetBrains Mono', monospace" class="select-none">
                CAP: ${d.capital.toUpperCase()}
              </text>
            </g>
          `;
        }
      }).join('');
    }

        function resetMapView(isTac = false) {
      selectedRegionName = null;
      mapPan = { x: 0, y: 0 };
      mapZoom = 1;
      tacMapPan = { x: 0, y: 0 };
      tacMapZoom = 1.1;
      
      // 1. Restore National 800x960 ViewBox on BOTH maps
      const stdSvg = document.getElementById('ghanaMapSvg');
      const tacSvg = document.getElementById('tacGhanaMapSvg');
      if (stdSvg) stdSvg.setAttribute('viewBox', '0 0 800 960');
      if (tacSvg) tacSvg.setAttribute('viewBox', '0 0 800 960');

      // 2. Restore 3D Perspective Tilt on BOTH planes
      const stdPlane = document.getElementById('ghana3dPlane');
      const tacPlane = document.getElementById('tacGhana3dPlane');
      if (stdPlane) {
        const rotX = is3DActive ? 22 : 0;
        const rotZ = is3DActive ? -8 : 0;
        stdPlane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${rotX}deg) rotateZ(${rotZ}deg)`;
      }
      if (tacPlane) {
        const rotX = isTac3DActive ? 24 : 0;
        const rotZ = isTac3DActive ? -6 : 0;
        tacPlane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${rotX}deg) rotateZ(${rotZ}deg)`;
      }

      // 3. Restore all 16 regions & background effects on BOTH maps
      const stdExtrusion = document.getElementById('extrusionShadowPass');
      const tacExtrusion = document.getElementById('tacExtrusionShadowPass');
      if (stdExtrusion) stdExtrusion.style.display = 'block';
      if (tacExtrusion) tacExtrusion.style.display = 'block';

      const rings = document.getElementById('radarRingsGroup');
      if (rings) rings.style.display = 'block';

      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      });

      ['.region-polygon', '.tactical-region-polygon'].forEach(selector => {
        document.querySelectorAll(selector).forEach(p => {
          p.style.display = 'block';
          p.style.opacity = '1';
          p.classList.remove('region-active');
          const rName = p.getAttribute('data-name');
          const rCases = (regionsData[rName] ? regionsData[rName].cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (rName || '').toLowerCase()).length;
          const isHotspot = (rCases === maxCaseload && maxCaseload > 0);
          if (isHotspot) {
            p.classList.add('region-hotspot', 'tactical-region-hotspot');
          } else {
            p.classList.remove('region-hotspot', 'tactical-region-hotspot');
          }
        });
      });

      // 4. Restore National Labels on BOTH maps
      const stdLbls = document.getElementById('regionLabelsGroup');
      const tacLbls = document.getElementById('tacRegionLabelsGroup');
      if (stdLbls) stdLbls.style.display = 'block';
      if (tacLbls) tacLbls.style.display = 'block';

      // 5. Hide District Navigation Headers & Drawers on BOTH views
      const navHeader = document.getElementById('districtNavHeader');
      if (navHeader) navHeader.classList.add('hidden');
      const drawer = document.getElementById('districtCardsDrawer');
      if (drawer) drawer.classList.add('hidden');
      const cornerTacBtn = document.getElementById('btnMapCornerTactical');
      if (cornerTacBtn) cornerTacBtn.classList.remove('hidden');
      const panHint = document.getElementById('mapPanZoomHint');
      if (panHint) panHint.classList.remove('hidden');

      const tacNav = document.getElementById('tacDistrictNavHeader');
      if (tacNav) tacNav.classList.add('hidden');
      const tacDeck = document.getElementById('tacDistrictDeckDrawer');
      if (tacDeck) tacDeck.classList.add('hidden');

      // 6. Clear District Markers on BOTH maps
      const group = document.getElementById('districtLayersGroup');
      if (group) group.innerHTML = '';
      const tacGroup = document.getElementById('tacDistrictLayersGroup');
      if (tacGroup) tacGroup.innerHTML = '';

      renderInvoicesList();
    }

    function toggle3DView(isTac = false) {
      if (isTac) {
        isTac3DActive = !isTac3DActive;
        const btn = document.getElementById('btnTacToggle3D');
        if (btn) {
          btn.innerHTML = isTac3DActive ? '<span>3D RELIEF</span>' : '<span>2D FLAT</span>';
        }
        if (!selectedRegionName) {
          const plane = document.getElementById('tacGhana3dPlane');
          if (plane) {
            const rotX = isTac3DActive ? 24 : 0;
            const rotZ = isTac3DActive ? -6 : 0;
            plane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${rotX}deg) rotateZ(${rotZ}deg)`;
          }
        }
      } else {
        is3DActive = !is3DActive;
        const btn = document.getElementById('btnToggle3D');
        if (btn) {
          if (is3DActive) {
            btn.classList.add('bg-indigo-600', 'text-white');
            btn.classList.remove('bg-slate-800', 'text-slate-400');
          } else {
            btn.classList.remove('bg-indigo-600', 'text-white');
            btn.classList.add('bg-slate-800', 'text-slate-400');
          }
        }
        if (!selectedRegionName) {
          const plane = document.getElementById('ghana3dPlane');
          if (plane) {
            const rotX = is3DActive ? 22 : 0;
            const rotZ = is3DActive ? -8 : 0;
            plane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${rotX}deg) rotateZ(${rotZ}deg)`;
          }
        }
      }
    }

    function zoomMap(factor, isTac = false) {
      const svg = isTac ? document.getElementById('tacGhanaMapSvg') : document.getElementById('ghanaMapSvg');
      if (!svg) return;
      const vb = svg.getAttribute('viewBox').split(' ').map(Number);
      if (vb.length === 4) {
        const newW = vb[2] / factor;
        const newH = vb[3] / factor;
        const newX = vb[0] + (vb[2] - newW) / 2;
        const newY = vb[1] + (vb[3] - newH) / 2;
        svg.setAttribute('viewBox', `${newX} ${newY} ${newW} ${newH}`);
      }
    }

    function openTacticalMode() {
      isTacticalModeOpen = true;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {
        theatre.classList.remove('hidden');
      }

      try {
        if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
          document.documentElement.requestFullscreen().catch(() => {});
        }
      } catch (e) {}

      lucide.createIcons();
      renderTacticalCasesStream();

      // Find region with higher number of cases
      let maxCaseload = 0;
      let highestRegion = 'Greater Accra';
      Object.keys(regionsData).forEach(k => {
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) {
          maxCaseload = cVal;
          highestRegion = k;
        }
      });

      const targetReg = selectedRegionName || highestRegion;

      if (targetReg) {
        selectRegionByName(targetReg, true);
      } else {
        resetMapView(true);
      }
    }

    function closeTacticalMode() {
      isTacticalModeOpen = false;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {
        theatre.classList.add('hidden');
      }
      setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
      }, 100);

      try {
        if (document.exitFullscreen && document.fullscreenElement) {
          document.exitFullscreen().catch(() => {});
        }
      } catch (e) {}
    }

    function onRegionHover(e, name) {
      if (selectedRegionName) return; // Don't show tooltip while in district drill-down
      const tt = document.getElementById('regionHoverTooltip');
      const ttName = document.getElementById('tooltipRegionName');
      const ttSub = document.getElementById('tooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      ttName.textContent = name;
      ttSub.textContent = `Capital: ${r ? r.capital : 'Ghana'} • ${r ? r.cases : 0} Dockets Active`;

      const stage = document.querySelector('.map-3d-stage');
      if (stage) {
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 15) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }
    }

    function onRegionLeave() {
      const tt = document.getElementById('regionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }

    function toggleTacDistrictDeck() {
      const grid = document.getElementById('tacDistrictDeckGrid');
      const txt = document.getElementById('tacDeckToggleText');
      if (!grid) return;
      if (grid.classList.contains('hidden')) {
        grid.classList.remove('hidden');
        if (txt) txt.textContent = 'Collapse Deck';
      } else {
        grid.classList.add('hidden');
        if (txt) txt.textContent = 'Expand Deck';
      }
    }

    function onTacDistrictHoverByCode(e, code) {
      if (!selectedRegionName) return;
      const list = districtsData[selectedRegionName] || [];
      const d = list.find(x => x.code === code);
      if (!d) return;

      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === selectedRegionName.toLowerCase());
      const linked = regionCases.filter(c => {
        const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
        return (d.keywords || []).some(kw => locText.includes(kw.toLowerCase()));
      });

      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      ttName.innerHTML = `<span class="text-white font-black">[${d.code}] ${d.name || d.short_name}</span>`;
      if (linked.length > 0) {
        const c = linked[0];
        ttSub.innerHTML = `
          <div class="mt-1 text-red-300 font-bold text-[11px]">${linked.length} DOCKET(S) ACTIVE IN MMDA</div>
          <div class="text-[10px] text-white font-bold mt-0.5">Target: ${c.suspect_name} (${c.case_number})</div>
          <div class="text-[9.5px] text-slate-300 mt-0.5">Capital: ${d.capital} • Exhibits: ${c.exhibits_count || 1} • Status: ${c.status_badge || 'Active'}</div>
        `;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2.5 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/80 transition-opacity duration-150";
      } else {
        ttSub.innerHTML = `
          <div class="mt-0.5 text-emerald-300 font-bold text-[11px]">MMDA Classification: Official District Assembly</div>
          <div class="text-[10px] text-slate-200 mt-0.5">Administrative Capital: ${d.capital}</div>
          <div class="text-[9.5px] text-slate-300 mt-0.5">Patrol Status: Sector Clear • 0 Active Dockets</div>
        `;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2.5 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150";
      }

      const stage = document.getElementById('tacMapStage');
      if (stage) {
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 20) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }
    }

    function onTacDistrictLeave() {
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }

    function onTacRegionHover(e, name) {
      if (selectedRegionName) return;
      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      const count = (r ? r.cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (name || '').toLowerCase()).length;

      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      });
      const isHotspot = (count === maxCaseload && maxCaseload > 0);

      ttName.textContent = name.toUpperCase();
      if (isHotspot) {
        ttSub.innerHTML = `<span class="text-slate-300 font-bold">HIGHER CASELOAD HOTSPOT (${count} DOCKETS)</span>`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/60 transition-opacity duration-150";
      } else if (count > 0) {
        ttSub.innerHTML = `<span class="text-emerald-300 font-medium">Capital: ${r ? r.capital : 'Ghana'} • ${count} Dockets Active</span>`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150";
      } else {
        ttSub.textContent = `Capital: ${r ? r.capital : 'Ghana'} • 0 Active Cases`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-slate-400 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-slate-700/60 transition-opacity duration-150";
      }

      const stage = document.getElementById('tacMapStage');
      if (stage) {
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 20) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }
    }

    function onTacRegionLeave() {
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }

    function switchMainView(mode) {
      currentMainView = mode;
      const btn3Col = document.getElementById('btnView3Col');
      const btnDossier = document.getElementById('btnViewDossier');
      const btnMap = document.getElementById('btnViewMap');

      const pList = document.getElementById('panelInvoicesList');
      const pMap = document.getElementById('panelGhanaMap');
      const pDossier = document.getElementById('panelDossierCard');

      [btn3Col, btnDossier, btnMap].forEach(b => {
        if (b) {
          b.classList.remove('bg-indigo-600', 'text-white', 'font-bold');
          b.classList.add('text-slate-400');
        }
      });

      if (mode === '3col') {
        if (btn3Col) btn3Col.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 xl:col-span-3 flex flex-col space-y-2';
        if (pMap) pMap.className = 'col-span-12 xl:col-span-5 flex flex-col';
        if (pDossier) pDossier.className = 'col-span-12 xl:col-span-4 flex flex-col';
      } else if (mode === 'dossier') {
        if (btnDossier) btnDossier.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 lg:col-span-4 flex flex-col space-y-2';
        if (pMap) pMap.className = 'hidden';
        if (pDossier) pDossier.className = 'col-span-12 lg:col-span-8 flex flex-col';
      } else if (mode === 'map') {
        if (btnMap) btnMap.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 lg:col-span-4 flex flex-col space-y-2';
        if (pMap) pMap.className = 'col-span-12 lg:col-span-8 flex flex-col';
        if (pDossier) pDossier.className = 'hidden';
      }
    }

    function onFilterUnitChange(val) {
      filterUnit = val;
      currentCasesPage = 1;
      renderInvoicesList();
    }

    function onFilterStatusChange(val) {
      filterStatus = val;
      currentCasesPage = 1;
      renderInvoicesList();
    }

    function onSearchInvoiceInput(val) {
      searchQuery = val.trim();
      currentCasesPage = 1;
      renderInvoicesList();
    }

    function resetFilters() {
      filterUnit = 'All';
      filterStatus = 'All';
      searchQuery = '';
      currentCasesPage = 1;
      selectedRegionName = null;
      document.getElementById('filterUnitSelect').value = 'All';
      document.getElementById('filterStatusSelect').value = 'All';
      document.getElementById('searchInvoiceInput').value = '';
      resetMapView();
    }

    function setInvoiceTab(tab) {
      activeTab = tab;
      const tAll = document.getElementById('tabBtnAll');
      const tAnalysis = document.getElementById('tabBtnAnalysis');
      const tActive = document.getElementById('tabBtnActive');
      
      [tAll, tAnalysis, tActive].forEach(t => {
        if (t) {
          t.classList.remove('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
          t.classList.add('text-slate-300');
        }
      });

      if (tab === 'active' && tActive) {
        tActive.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      } else if (tab === 'analysis' && tAnalysis) {
        tAnalysis.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      } else if (tab === 'all' && tAll) {
        tAll.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }
    }

    // Setup Event Listeners
    document.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();
      initCharts();

      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isTacticalModeOpen) {
          closeTacticalMode();
        }
        if ((e.key === 't' || e.key === 'T') && !['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) {
          if (isTacticalModeOpen) closeTacticalMode();
          else openTacticalMode();
        }
      });

      fetchRealtimeTelemetry();
      setInterval(fetchRealtimeTelemetry, 8000);
    });
  </script>

</body>
</html>
