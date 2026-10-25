import json
import os
import sys

# Add scratch to path to import towns
sys.path.append(os.path.dirname(__file__))
from generate_towns import towns_data

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Generate common SVG paths for all 16 regions
region_paths_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    region_paths_markup.append(f'''
            <!-- {r['name']} Region -->
            <path id="path-{reg_id}"
                  data-name="{r['name']}"
                  data-capital="{r['capital']}"
                  data-iso="{r['iso']}"
                  data-cases="{r['cases']}"
                  data-active-ops="{r['activeOps']}"
                  data-status="{r['status']}"
                  data-badge="{r['badge']}"
                  data-commander="{r['commander']}"
                  data-officers="{r['officers']}"
                  data-narcotics="{r['narcotics']}"
                  data-firearms="{r['firearms']}"
                  data-devices="{r['devices']}"
                  data-vehicles="{r['vehicles']}"
                  data-cx="{r['cx']}"
                  data-cy="{r['cy']}"
                  d="{r['path']}"
                  class="region-polygon transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('{r['name']}')"
                  onmouseenter="onRegionHover(event, '{r['name']}')"
                  onmouseleave="onRegionLeave()" />''')

region_paths_html = ''.join(region_paths_markup)

# Extruded 3D base depth paths
extruded_paths_markup = []
for r in regions:
    extruded_paths_markup.append(f'''<path d="{r['path']}" fill="#0f111a" opacity="0.9" />''')
extruded_paths_html = ''.join(extruded_paths_markup)

# Direct region labels on map
region_labels_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    region_labels_markup.append(f'''
            <g id="lbl-{reg_id}" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('{r['name']}')">
              <text x="{r['cx']}" y="{r['cy'] - 4}" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">{r['name'].upper()}</text>
              <text id="badge-{reg_id}" x="{r['cx']}" y="{r['cy'] + 9}" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">{r['cases']} Dockets</text>
            </g>''')
region_labels_html = ''.join(region_labels_markup)

# Serialize dataset for JS
regions_dataset_js = json.dumps({r['name']: r for r in regions})
towns_dataset_js = json.dumps(towns_data)

blade_content = f'''@php
    $authUser = auth()->user();
    $cases = $cases ?? \\App\\Models\\ForensicCase::allCases();
    $userUnit = 'Forensic Directorate';
    $unitDisplayTitle = 'Forensic Directorate Supreme Command';
    $dbForensicHead = \\App\\Models\\User::where('is_head', true)
        ->where(function($q) {{
            $q->where('unit', 'LIKE', '%Forensic%')
              ->orWhere('unit', 'LIKE', '%Directorate%');
        }})->first() ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbForensicHead ? $dbForensicHead->fullname : 'Anita Addo');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Directorate Head' : 'Commanding Officer';

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $cases->count();
    $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{{{ csrf_token() }}}}">
  <title>FINNOVA | NACOC Forensic Directorate Command & Regional Intelligence</title>
  
  <!-- Styles / Scripts: System Vite Asset Loader & Compiled CSS -->
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
  @endif
  @if (file_exists(public_path('build/assets/app-BL-D8y3S.css')))
      <link rel="stylesheet" href="{{{{ asset('build/assets/app-BL-D8y3S.css') }}}}">
  @endif

  <!-- Tailwind CSS CDN & Extensions -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {{
      theme: {{
        extend: {{
          colors: {{
            finnova: {{
              purple: '#5c56df',
              dark: '#181926',
              darker: '#12131d',
              card: '#2b2a5c',
              light: '#f4f5fa'
            }}
          }},
          spacing: {{
            '4.5': '1.125rem',
          }},
          boxShadow: {{
            'finnova': '0 20px 50px -15px rgba(24, 25, 38, 0.08)',
            'finnova-dark': '0 25px 60px -15px rgba(10, 11, 20, 0.5)',
          }}
        }}
      }}
    }};
  </script>

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {{
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: #f1f3f8;
      color: #1e293b;
    }}
    .font-mono-code {{
      font-family: 'JetBrains Mono', monospace;
    }}

    /* 3D Map Perspective Stage - Interactive Drag & Pan Enabled */
    .map-3d-stage {{
      perspective: 1400px;
      perspective-origin: 50% 50%;
      transform-style: preserve-3d;
      cursor: grab;
      user-select: none;
      -webkit-user-select: none;
      touch-action: none;
    }}
    .map-3d-stage.is-dragging,
    .map-3d-stage.is-dragging * {{
      cursor: grabbing !important;
    }}
    .map-3d-plane {{
      transform-style: preserve-3d;
      will-change: transform;
    }}

    /* Concentric Radar Rings Behind Map */
    .radar-ring {{
      border: 1px dashed rgba(92, 86, 223, 0.25);
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
    }}
    .radar-pulse {{
      animation: radarScan 4.5s ease-out infinite;
    }}
    @keyframes radarScan {{
      0% {{ transform: translate(-50%, -50%) scale(0.65); opacity: 0.8; }}
      50% {{ opacity: 0.35; }}
      100% {{ transform: translate(-50%, -50%) scale(1.35); opacity: 0; }}
    }}

    /* SVG Region Hover & Active States */
    .region-polygon {{
      transition: fill 0.25s ease, stroke 0.25s ease, filter 0.25s ease;
    }}
    .region-polygon.region-active {{
      fill: #5c56df !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(92, 86, 223, 0.45)) !important;
      opacity: 1 !important;
    }}

    /* Custom Scrollbars */
    ::-webkit-scrollbar {{
      width: 5px;
      height: 5px;
    }}
    ::-webkit-scrollbar-track {{
      background: rgba(24, 25, 38, 0.05);
      border-radius: 4px;
    }}
    ::-webkit-scrollbar-thumb {{
      background: rgba(92, 86, 223, 0.3);
      border-radius: 4px;
    }}
    ::-webkit-scrollbar-thumb:hover {{
      background: rgba(92, 86, 223, 0.6);
    }}
  </style>
</head>
<body class="min-h-screen p-2 sm:p-4 lg:p-6 flex flex-col items-center justify-start antialiased selection:bg-[#5c56df] selection:text-white">

  <!-- ======================================================== -->
  <!-- MASTER FINNOVA CONTAINER (Rounded Floating Canvas)       -->
  <!-- ======================================================== -->
  <main class="w-full max-w-[1720px] bg-white rounded-[2rem] sm:rounded-[2.5rem] border border-slate-200/80 shadow-[0_20px_60px_-15px_rgba(15,23,42,0.08)] p-4 sm:p-6 lg:p-8 flex flex-col space-y-6 relative overflow-hidden">
    
    <!-- ====================================================== -->
    <!-- 1. TOP NAVIGATION BAR (Exact Finnova Segmented Layout) -->
    <!-- ====================================================== -->
    <nav class="flex flex-col lg:flex-row items-center justify-between gap-4 pb-2 border-b border-slate-100">
      
      <!-- Left: Finnova / NACOC Directorate Logo -->
      <div class="flex items-center space-x-3 w-full lg:w-auto justify-between lg:justify-start">
        <a href="{{{{ url('/Forensic_Head') }}}}" class="flex items-center space-x-3 group">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#5c56df] via-[#6d67e6] to-[#8b5cf6] flex items-center justify-center text-white shadow-md shadow-indigo-500/25 group-hover:scale-105 transition-transform">
            <!-- Finnova Geometric Glyph -->
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
              <path d="M4 6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zm10 8a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4zM7 14c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zm10-10c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-lg font-black tracking-tight text-slate-900 font-mono-code">FINNOVA</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-[#5c56df] font-bold font-mono-code border border-indigo-100">NACOC</span>
            </div>
            <p class="text-[11px] text-slate-400 font-medium">Smart Forensic Intelligence & National Command</p>
          </div>
        </a>

        <!-- Mobile Live Pulse Indicator -->
        <div class="lg:hidden flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>LIVE</span>
        </div>
      </div>

      <!-- Center: Finnova Dark Pill Navigation Bar -->
      <div class="bg-[#181926] p-1.5 rounded-full flex items-center space-x-1 shadow-lg text-xs font-semibold text-slate-300 overflow-x-auto max-w-full">
        <!-- Count badge pill -->
        <span id="navCaseCountBadge" class="bg-white/10 text-white text-[11px] font-bold font-mono-code px-3 py-1.5 rounded-full">
          {{{{ $totalCases }}}}
        </span>
        
        <a href="{{{{ url('/Forensic_Head') }}}}" class="px-3 py-1.5 rounded-full hover:text-white transition-colors flex items-center space-x-1">
          <span>+ Overview</span>
        </a>
        <a href="{{{{ url('/cases') }}}}?unit=Crime+Scene+Support" class="px-3 py-1.5 rounded-full hover:text-white transition-colors">
          Estimates
        </a>
        <!-- Active Pill -->
        <a href="{{{{ url('/Forensic_Head') }}}}" class="bg-[#5c56df] text-white px-4 py-1.5 rounded-full font-bold shadow-md shadow-indigo-500/25 flex items-center space-x-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
          <span>• Invoices</span>
        </a>
        <a href="{{{{ url('/cases') }}}}?unit=Evidence+Management" class="px-3 py-1.5 rounded-full hover:text-white transition-colors">
          Payments
        </a>
        <a href="{{{{ url('/history') }}}}" class="px-3 py-1.5 rounded-full hover:text-white transition-colors">
          Recurring
        </a>
        <a href="{{{{ url('/Forensic_Head/approvals') }}}}" class="px-3 py-1.5 rounded-full hover:text-white transition-colors">
          Checkouts
        </a>
      </div>

      <!-- Right: Action Icons & User Profile -->
      <div class="flex items-center space-x-2 sm:space-x-3">
        <!-- Live Real-Time Synchronizer Indicator -->
        <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold font-mono-code border border-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
          <span>LIVE SYNCED</span>
          <button type="button" onclick="refreshDashboardLive()" title="Force Refresh" class="hover:rotate-180 transition-transform">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-emerald-600"></i>
          </button>
        </div>

        <!-- Quick utility icons -->
        <div class="flex items-center space-x-1 border border-slate-200 rounded-full p-1 bg-white shadow-xs">
          <a href="{{{{ url('/history') }}}}" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" title="Audit Dossiers">
            <i data-lucide="file-text" class="w-4 h-4"></i>
          </a>
          <a href="{{{{ url('/cases') }}}}?unit=Evidence+Management" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" title="Evidence Vault">
            <i data-lucide="dollar-sign" class="w-4 h-4"></i>
          </a>
          <a href="{{{{ url('/Forensic_Head/approvals') }}}}" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" title="Command Calendar">
            <i data-lucide="calendar" class="w-4 h-4"></i>
          </a>
          <a href="{{{{ url('/cases') }}}}" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" title="Archived Exhibits">
            <i data-lucide="archive" class="w-4 h-4"></i>
          </a>
          <a href="{{{{ url('/profile/settings') }}}}" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" title="Settings">
            <i data-lucide="settings" class="w-4 h-4"></i>
          </a>
        </div>

        <!-- Notification Bell with Dot -->
        <div class="relative">
          <a href="{{{{ url('/Forensic_Head/approvals') }}}}" class="w-9 h-9 rounded-full border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors relative shadow-xs">
            <i data-lucide="bell" class="w-4 h-4"></i>
            <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
          </a>
        </div>

        <!-- Officer Avatar -->
        <div class="flex items-center space-x-2 pl-1 border-l border-slate-200">
          <div class="relative">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-slate-800 to-slate-700 flex items-center justify-center text-white font-bold text-xs ring-2 ring-indigo-500/30">
              {{{{ strtoupper(substr($headName, 0, 1)) }}}}
            </div>
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
          </div>
        </div>

      </div>

    </nav>

    <!-- ====================================================== -->
    <!-- 2. SUBHEADER (Title, Subtitle & Primary Action)         -->
    <!-- ====================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
      
      <!-- Left: Back Button & Title -->
      <div class="flex items-center space-x-3.5">
        <a href="{{{{ url('/cases') }}}}" class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 shadow-xs transition-colors" title="Back to Case Directory">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
          <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-slate-900 leading-tight">
            Invoices
          </h1>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Manage and track all your invoices and forensic case dossiers in one place.
          </p>
        </div>
      </div>

      <!-- Right: Filter & Create Primary Button -->
      <div class="flex items-center space-x-3">
        <!-- Filter sliders button -->
        <button type="button" onclick="toggleFilterToolbar()" class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 shadow-xs transition-colors" title="Filter Options">
          <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
        </button>

        <!-- + Create an invoice / Register Case -->
        <a href="{{{{ url('/arrest-person') }}}}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-full bg-[#5c56df] hover:bg-[#4d47d1] text-white font-bold text-xs shadow-lg shadow-indigo-500/25 transition-all hover:shadow-indigo-500/40 hover:-translate-y-0.5">
          <i data-lucide="plus" class="w-4 h-4 stroke-[2.5]"></i>
          <span>Create an invoice</span>
        </a>
      </div>

    </div>

    <!-- ====================================================== -->
    <!-- 3. FOUR TOP METRIC CARDS (Exact Finnova 1:1 Design)     -->
    <!-- ====================================================== -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5">
      
      <!-- CARD 1: Overdue (with desk photograph illustration) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Overdue</span>
            <span class="w-5 h-5 rounded-full bg-rose-50 border border-rose-200 text-rose-500 flex items-center justify-center text-[10px] font-black">!</span>
          </div>

          <!-- Main Value -->
          <div id="metricOverdueAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            $ 24,850.00
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-rose-500 mt-1 flex items-center space-x-1">
            <span id="metricOverdueTrend">↑ 12.5% from last month</span>
          </div>
        </div>

        <!-- Desk Illustration Image on bottom left / center -->
        <div class="mt-4 pt-3 border-t border-slate-100/80 flex items-center justify-between">
          <div class="w-24 h-16 rounded-xl overflow-hidden shadow-xs border border-slate-200/60 bg-slate-100 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
            <img src="{{{{ asset('images/dashboard/finnova_desk.jpg') }}}}" 
                 alt="Desk Workspace" 
                 class="w-full h-full object-cover object-center" 
                 onerror="this.src='https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=400&q=80'">
          </div>
          <div class="text-right">
            <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100">
              Pending Sign-off
            </span>
            <div class="text-[10px] text-slate-400 mt-1">National Command Registry</div>
          </div>
        </div>
      </div>

      <!-- CARD 2: Due within next month (with Purple Vertical Bar Chart) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Due within next month</span>
            <span class="w-5 h-5 rounded-full bg-indigo-50 border border-indigo-200 text-[#5c56df] flex items-center justify-center text-[10px]">
              <i data-lucide="calendar" class="w-3 h-3"></i>
            </span>
          </div>

          <!-- Main Value -->
          <div id="metricNextMonthAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            $ 142,560.00
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-[#5c56df] mt-1 flex items-center space-x-1">
            <span id="metricNextMonthTrend">↑ 8.2% from last month</span>
          </div>
        </div>

        <!-- 6-Month Bar Chart -->
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaBarChart"></canvas>
        </div>
      </div>

      <!-- CARD 3: Average time to get paid (with Smooth Purple Sparkline) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Average time to get paid</span>
            <span class="w-5 h-5 rounded-full bg-cyan-50 border border-cyan-200 text-cyan-600 flex items-center justify-center text-[10px]">
              <i data-lucide="clock" class="w-3 h-3"></i>
            </span>
          </div>

          <!-- Main Value -->
          <div id="metricTurnaroundDays" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            16 days
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-emerald-500 mt-1 flex items-center space-x-1">
            <span id="metricTurnaroundTrend">↓ 2 days from last month</span>
          </div>
        </div>

        <!-- Smooth Connected Line Sparkline -->
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaSparkline"></canvas>
        </div>
      </div>

      <!-- CARD 4: Available for Instant Payout / Evidence Vault -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Available for Instant Payout</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-[10px]">
                <i data-lucide="lock" class="w-3 h-3"></i>
              </span>
              <span class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </span>
            </div>
          </div>

          <!-- Main Value -->
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              $ 186,540.00
            </span>
            <span class="text-[11px] font-medium text-slate-400">Expects</span>
          </div>
        </div>

        <!-- Accounts / Payment Methods Pill Cards -->
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          <!-- Card 1: Visa -->
          <div class="bg-slate-50 hover:bg-slate-100 p-2 rounded-xl border border-slate-100 text-center flex-1 cursor-pointer transition-colors">
            <div class="font-mono-code font-bold text-slate-600">.... 4242</div>
            <div class="text-[9px] text-slate-400">Visa</div>
          </div>

          <!-- Card 2: Stripe (Active Purple Card) -->
          <div class="bg-[#5c56df] text-white p-2 rounded-xl text-center flex-1 shadow-sm shadow-indigo-500/30 cursor-pointer">
            <div class="font-mono-code font-bold">.... 6789</div>
            <div class="text-[9px] text-indigo-100">Stripe</div>
          </div>

          <!-- Card 3: PayPal -->
          <div class="bg-slate-50 hover:bg-slate-100 p-2 rounded-xl border border-slate-100 text-center flex-1 cursor-pointer transition-colors">
            <div class="font-mono-code font-bold text-slate-600">.... 1234</div>
            <div class="text-[9px] text-slate-400">PayPal</div>
          </div>

          <!-- Payout now button -->
          <a href="{{{{ url('/cases') }}}}?unit=Evidence+Management" class="bg-[#181926] hover:bg-black text-white px-3 py-2.5 rounded-xl font-bold font-mono-code text-[10px] whitespace-nowrap shadow-xs transition-colors">
            Payout now
          </a>
        </div>
      </div>

    </section>

    <!-- ====================================================== -->
    <!-- 4. FILTER TOOLBAR (Exact Finnova Filter Strip)          -->
    <!-- ====================================================== -->
    <div id="filterToolbar" class="flex flex-wrap items-center justify-between gap-3 pt-1">
      
      <!-- Left Filters -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Active filters badge -->
        <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200/70">
          <span>Active filters</span>
          <span id="activeFiltersCount" class="w-4 h-4 rounded-full bg-[#181926] text-white text-[10px] flex items-center justify-center font-mono-code">2</span>
        </div>

        <!-- Dropdown: All customers -->
        <div class="relative">
          <select id="filterCustomerSelect" onchange="applyFilters()" class="appearance-none pl-3 pr-8 py-1.5 rounded-full bg-white border border-slate-200/90 text-xs font-semibold text-slate-700 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer shadow-xs">
            <option value="all">All customers</option>
            <option value="directorate">Forensic Directorate</option>
            <option value="crime_scene">Crime Scene Support</option>
            <option value="lab">Chemical Laboratory</option>
            <option value="digital">Digital & Cyber Forensics</option>
            <option value="evidence">Evidence Management</option>
          </select>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
        </div>

        <!-- Dropdown: All statuses -->
        <div class="relative">
          <select id="filterStatusSelect" onchange="applyFilters()" class="appearance-none pl-3 pr-8 py-1.5 rounded-full bg-white border border-slate-200/90 text-xs font-semibold text-slate-700 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer shadow-xs">
            <option value="all">All statuses</option>
            <option value="Unpaid">Unpaid / Under Investigation</option>
            <option value="Draft">Draft / Pending Approval</option>
            <option value="Approved">Approved / Settled</option>
          </select>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none"></i>
        </div>

        <!-- Date Range Pills -->
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-white border border-slate-200/90 text-xs font-semibold text-slate-700 shadow-xs cursor-pointer hover:border-slate-300">
          <span>November 2026</span>
          <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
        </div>
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-white border border-slate-200/90 text-xs font-semibold text-slate-700 shadow-xs cursor-pointer hover:border-slate-300">
          <span>December 2026</span>
          <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
        </div>
      </div>

      <!-- Right: Search Input -->
      <div class="relative w-full sm:w-72">
        <input type="text" 
               id="invoiceSearchInput" 
               placeholder="Enter invoice #, suspect, or exhibit..." 
               oninput="onSearchInvoiceInput(this.value)"
               class="w-full pl-4 pr-9 py-2 rounded-full bg-white border border-slate-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute right-3.5 top-2.5 pointer-events-none"></i>
      </div>

    </div>

    <!-- ====================================================== -->
    <!-- 5. CENTRAL / BOTTOM MERGED DARK STAGE                  -->
    <!-- (Finnova Dark Curved Shell + 3D Ghana Map + Dossier)    -->
    <!-- ====================================================== -->
    <section class="bg-[#181926] rounded-[2rem] p-4 sm:p-6 text-white shadow-finnova-dark border border-slate-800/80 flex flex-col space-y-4 relative overflow-hidden">
      
      <!-- Top Bar of Dark Stage: Tabs & Command Mode Switchers -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-800/90">
        
        <!-- Left: Curved Tabs -->
        <div class="bg-white/10 p-1 rounded-full inline-flex items-center space-x-1 text-xs font-semibold">
          <button type="button" onclick="setInvoiceTab('all')" id="tabBtnAll" class="px-4 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors">
            All Invoices
          </button>
          <button type="button" onclick="setInvoiceTab('draft')" id="tabBtnDraft" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors flex items-center space-x-1.5">
            <span>Draft</span>
            <span id="tabCountDraft" class="bg-white/10 text-slate-300 px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">0</span>
          </button>
          <!-- Active Tab: Unpaid -->
          <button type="button" onclick="setInvoiceTab('unpaid')" id="tabBtnUnpaid" class="bg-[#5c56df] text-white px-4 py-1.5 rounded-full font-bold shadow-md shadow-indigo-500/25 flex items-center space-x-1.5">
            <span>Unpaid</span>
            <span id="tabCountUnpaid" class="bg-white/20 text-white px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{{{ $totalCases }}}}</span>
          </button>
        </div>

        <!-- Center / Right: Interactive View Modes & Map Controls -->
        <div class="flex items-center space-x-2">
          
          <!-- View Mode Segmented Control -->
          <div class="bg-slate-900/90 p-1 rounded-full border border-slate-700/80 flex items-center space-x-1 text-xs">
            <button type="button" onclick="switchMainView('split')" id="btnViewSplit" class="px-3 py-1 rounded-full font-bold text-white bg-indigo-600 shadow-sm flex items-center space-x-1 transition-all">
              <i data-lucide="columns-2" class="w-3.5 h-3.5"></i>
              <span class="hidden sm:inline">Split View</span>
            </button>
            <button type="button" onclick="switchMainView('dossier')" id="btnViewDossier" class="px-3 py-1 rounded-full text-slate-400 hover:text-white flex items-center space-x-1 transition-all">
              <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
              <span>Dossier Details</span>
            </button>
            <button type="button" onclick="switchMainView('map')" id="btnViewMap" class="px-3 py-1 rounded-full text-slate-400 hover:text-white flex items-center space-x-1 transition-all">
              <i data-lucide="map" class="w-3.5 h-3.5"></i>
              <span>3D Ghana Map</span>
            </button>
          </div>

          <!-- Map Controls (Zoom, 3D Tilt, Reset) -->
          <div class="flex items-center space-x-1 border border-slate-700/80 rounded-full p-1 bg-slate-900/80">
            <button type="button" onclick="zoomMap(1.15)" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-xs" title="Zoom In">+</button>
            <button type="button" onclick="zoomMap(0.85)" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-xs" title="Zoom Out">-</button>
            <button type="button" onclick="resetMapView()" class="w-7 h-7 rounded-full hover:bg-slate-800 text-slate-300 flex items-center justify-center" title="Reset View">
              <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" onclick="toggle3DView()" id="btnToggle3D" class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-black font-mono-code" title="Toggle 3D Relief">3D</button>
          </div>

          <!-- Secondary icon buttons -->
          <button type="button" class="w-8 h-8 rounded-full border border-slate-700/80 hover:bg-slate-800 text-slate-400 flex items-center justify-center">
            <i data-lucide="table" class="w-4 h-4"></i>
          </button>
          <button type="button" class="w-8 h-8 rounded-full border border-slate-700/80 hover:bg-slate-800 text-slate-400 flex items-center justify-center">
            <i data-lucide="more-vertical" class="w-4 h-4"></i>
          </button>

        </div>

      </div>

      <!-- MAIN SPLIT WORKSPACE: Left Case List + Right Dynamic Stage -->
      <div class="grid grid-cols-12 gap-4 lg:gap-6 items-start">
        
        <!-- LEFT SUB-PANEL: Unpaid Invoices List (1/3 Width on Desktop) -->
        <div class="col-span-12 xl:col-span-4 flex flex-col space-y-2">
          
          <div class="flex items-center justify-between px-1 pb-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono-code" id="listHeaderTitle">Unpaid Invoices</span>
            <span class="text-[10px] font-mono-code text-indigo-400" id="listCasesCount">{{{{ $totalCases }}}} Dockets Active</span>
          </div>

          <!-- Real-Time Case Rows Container -->
          <div id="invoicesListContainer" class="space-y-1.5 max-h-[580px] overflow-y-auto pr-1">
            <!-- Dynamically populated via renderInvoicesList() from real-time API -->
            <div class="text-center py-8 text-xs text-slate-500">Loading real-time dossiers from database...</div>
          </div>

        </div>

        <!-- RIGHT SUB-PANEL: Integrated Dossier Card & 3D Ghana Map (2/3 Width on Desktop) -->
        <div class="col-span-12 xl:col-span-8 flex flex-col space-y-4">
          
          <!-- ============================================== -->
          <!-- A. THE FROSTED CASE DETAILS CARD (Finnova UI)   -->
          <!-- ============================================== -->
          <div id="dossierCardWrapper" class="bg-gradient-to-br from-[#2f2e59]/90 to-[#222143]/90 rounded-2xl p-5 border border-white/10 shadow-2xl relative overflow-hidden backdrop-blur-xl transition-all">
            
            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

            <!-- Card Top Row: Invoice details | Company Logo | Customer -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-white/10 relative z-10">
              
              <!-- Left: Invoice # & Status -->
              <div>
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Invoice details</span>
                <div class="flex items-center space-x-2 mt-0.5">
                  <h3 id="cardInvoiceNumber" class="text-xl font-black text-white tracking-tight font-mono-code"># INV-1003</h3>
                  <span id="cardInvoiceStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code bg-white/10 text-indigo-200 border border-white/10">Unsent</span>
                </div>
              </div>

              <!-- Center: Directorate / Company Brand -->
              <div class="text-center sm:text-left">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Company</span>
                <div class="flex items-center space-x-1.5 mt-0.5">
                  <span id="cardCompanyName" class="text-base font-extrabold text-white tracking-tight">BrightWave</span>
                  <!-- Stylized wave glyph -->
                  <svg class="w-4 h-4 text-indigo-400 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M2 12c2.5-3 5-3 7.5 0s5 3 7.5 0 5-3 7.5 0"/>
                  </svg>
                </div>
              </div>

              <!-- Right: Suspect / Customer Profile -->
              <div class="flex items-center space-x-2.5">
                <div id="cardCustomerAvatar" class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden ring-2 ring-indigo-400/40 flex-shrink-0 flex items-center justify-center text-white font-bold text-xs">
                  JC
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Customer</span>
                  <div id="cardCustomerName" class="text-xs font-bold text-white">James Carter</div>
                  <div id="cardCustomerRole" class="text-[10px] text-slate-400">Marketing Director</div>
                </div>
              </div>

            </div>

            <!-- Itemized Cards Row (Finnova 3 frosted cards + Add Item) -->
            <div id="exhibitCardsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 my-4 relative z-10">
              <!-- Dynamically populated per selected invoice -->
            </div>

            <!-- Card Bottom Bar: Totals & Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-white/10 relative z-10">
              
              <!-- Financial / Evidence Totals -->
              <div class="flex items-center space-x-6 text-xs">
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Sub Total</span>
                  <span id="cardSubTotal" class="font-bold text-white font-mono-code">$ 47,980.00</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Total</span>
                  <span id="cardTotal" class="font-bold text-white font-mono-code">$ 47,980.00</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Balance Due</span>
                  <span id="cardBalanceDue" class="font-black text-indigo-300 font-mono-code">$ 47,980.00</span>
                </div>
              </div>

              <!-- Actions: Link, Calendar, Payout Now -->
              <div class="flex items-center space-x-2">
                <a id="cardActionLink" href="#" class="w-8 h-8 rounded-full border border-white/20 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors" title="Copy Docket Link">
                  <i data-lucide="link" class="w-3.5 h-3.5"></i>
                </a>
                <button type="button" class="w-8 h-8 rounded-full border border-white/20 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors" title="Schedule Review">
                  <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                </button>
                <a id="cardActionPrimary" href="{{{{ url('/cases') }}}}" class="px-5 py-2 rounded-full bg-white text-slate-900 font-black text-xs hover:bg-slate-100 shadow-md transition-all flex items-center space-x-1.5 font-mono-code">
                  <span>Payout now</span>
                </a>
              </div>

            </div>

          </div>

          <!-- ============================================== -->
          <!-- B. 3D CLICKABLE GHANA MAP (16 Regions Extruded) -->
          <!-- ============================================== -->
          <div id="mapStageWrapper" class="relative w-full min-h-[460px] lg:min-h-[500px] flex items-center justify-center bg-slate-950/60 rounded-2xl border border-slate-800/80 overflow-hidden select-none">
            
            <!-- Radial Ambient Glow behind Ghana -->
            <div class="absolute w-[520px] h-[520px] rounded-full pointer-events-none opacity-30 blur-3xl bg-gradient-to-tr from-[#5c56df] via-purple-600/40 to-transparent"></div>

            <!-- Concentric Radar / Orbital Rings centered behind Ghana -->
            <div class="radar-ring w-[600px] h-[600px]"></div>
            <div class="radar-ring w-[440px] h-[440px] radar-pulse"></div>
            <div class="radar-ring w-[300px] h-[300px]"></div>

            <!-- Subtle Coordinate Grid Ticks -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-25">
              <svg class="w-[600px] h-[600px]" viewBox="0 0 800 800">
                <circle cx="400" cy="400" r="320" fill="none" stroke="#475569" stroke-width="1" stroke-dasharray="2, 8" />
                <line x1="400" y1="40" x2="400" y2="760" stroke="#475569" stroke-width="0.8" stroke-dasharray="4, 6" />
                <text x="408" y="60" fill="#94a3b8" font-size="10" font-family="'JetBrains Mono', monospace">0° PRIME MERIDIAN</text>
              </svg>
            </div>

            <!-- 3D Perspective Map Stage Container -->
            <div class="map-3d-stage relative w-full h-[460px] lg:h-[500px] flex items-center justify-center">
              
              <div id="ghana3dPlane" class="map-3d-plane relative w-full h-full max-w-[620px] lg:max-w-[680px] flex items-center justify-center">
                
                <svg id="ghanaMapSvg" 
                     viewBox="0 0 800 960" 
                     class="w-full h-full drop-shadow-[0_25px_40px_rgba(0,0,0,0.6)]"
                     style="overflow: visible;">
                  
                  <defs>
                    <linearGradient id="regionGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#1e293b" />
                      <stop offset="100%" stop-color="#0f172a" />
                    </linearGradient>
                  </defs>

                  <!-- 1. 3D Base Extrusion Shadows -->
                  <g id="extrusionShadowPass" transform="translate(0, 16)" pointer-events="none">
                    {extruded_paths_html}
                  </g>

                  <!-- 2. Primary 16 Ghana Regions Polygons -->
                  <g id="regionsPolygonsGroup">
                    {region_paths_html}
                  </g>

                  <!-- 3. Direct Region Labels -->
                  <g id="regionLabelsGroup">
                    {region_labels_html}
                  </g>

                  <!-- 4. Active Detailed Town & Village Markers for Selected Region -->
                  <g id="townMarkersGroup">
                    <!-- Dynamically populated via renderTownMarkers(regionName) -->
                  </g>

                </svg>

              </div>

              <!-- Active Focused Region Header Banner -->
              <div id="regionActiveFocusBanner" class="hidden absolute top-3 left-4 z-20 flex items-center space-x-2.5 bg-slate-950/90 text-white backdrop-blur-md px-4 py-2 rounded-2xl text-xs shadow-2xl border border-slate-700/70 transition-all duration-300">
                <span class="w-2.5 h-2.5 rounded-full bg-[#5c56df] animate-ping"></span>
                <div>
                  <div class="flex items-center space-x-1.5">
                    <span class="font-extrabold text-slate-100" id="focusRegionTitle">Greater Accra Region</span>
                    <span class="text-slate-400">•</span>
                    <span class="text-indigo-300 font-mono-code text-[11px]" id="focusTownsCount">7 Stations Active</span>
                  </div>
                  <div class="text-[9px] text-slate-400 font-medium">Click to inspect outposts • Filters cases list</div>
                </div>
                <button type="button" onclick="resetMapView()" class="ml-2 pl-3 border-l border-slate-700 text-[10px] text-slate-300 hover:text-white flex items-center gap-1.5 font-bold hover:bg-slate-800 px-2 py-1 rounded-lg transition-colors" title="Reset View">
                  <i data-lucide="rotate-ccw" class="w-3 h-3 text-indigo-400"></i>
                  <span>Reset</span>
                </button>
              </div>

              <!-- Dynamic Floating Region Hover Tooltip -->
              <div id="regionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-900/95 text-white backdrop-blur-md px-3.5 py-2 rounded-xl text-xs shadow-2xl border border-slate-700/80 transition-opacity duration-150">
                <div id="tooltipRegionName" class="font-bold text-indigo-300">Region Name</div>
                <div id="tooltipSub" class="text-[10px] text-slate-300">Capital: Kumasi • Click to filter cases</div>
              </div>

              <!-- Subtle Pan/Zoom Hint -->
              <div class="absolute bottom-3 left-4 z-20 pointer-events-none flex items-center space-x-2 bg-slate-900/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-semibold text-slate-400 border border-slate-800 shadow-xs">
                <i data-lucide="move" class="w-3 h-3 text-indigo-400"></i>
                <span>Drag to pan 3D map • Scroll to zoom • Click region to filter</span>
              </div>

            </div>

          </div>

        </div>

      </div>

    </section>

  </main>

  <!-- ======================================================== -->
  <!-- JAVASCRIPT: REAL-TIME ENGINE + 3D MAP + FINNOVA LOGIC    -->
  <!-- ======================================================== -->
  <script>
    lucide.createIcons();

    // Datasets
    const regionsDataset = {regions_dataset_js};
    const townsDataset = {towns_dataset_js};

    // State Variables
    let currentInvoices = [];
    let selectedInvoice = null;
    let selectedRegion = 'Greater Accra';
    let currentFilterTab = 'unpaid';
    let currentFilterCustomer = 'all';
    let currentFilterStatus = 'all';
    let currentSearchTerm = '';
    let currentMainView = 'split'; // 'split', 'dossier', 'map'

    // 3D Map Transform State
    let is3DMode = true;
    let rotX = 38;
    let rotY = 0;
    let rotZ = -12;
    let panX = 0;
    let panY = 0;
    let currentZoom = 1.0;
    let isDragging = false;
    let startX = 0, startY = 0;
    let hasDragged = false;

    // Charts references
    let barChartInstance = null;
    let sparklineInstance = null;

    // 1. Initialize Charts matching Finnova Style
    function initFinnovaCharts(barData = [25, 45, 60, 85, 110, 142], sparkData = [12, 14, 11, 16, 15, 18, 14, 16]) {{
      // Vertical Bar Chart (Jul - Dec)
      const barCtx = document.getElementById('finnovaBarChart');
      if (barCtx) {{
        if (barChartInstance) barChartInstance.destroy();
        barChartInstance = new Chart(barCtx, {{
          type: 'bar',
          data: {{
            labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{{
              data: barData,
              backgroundColor: '#5c56df',
              borderRadius: 6,
              borderSkipped: false,
              barThickness: 12,
            }}]
          }},
          options: {{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {{ legend: {{ display: false }}, tooltip: {{ enabled: true }} }},
            scales: {{
              x: {{ grid: {{ display: false }}, ticks: {{ font: {{ size: 9, family: 'Inter' }}, color: '#94a3b8' }} }},
              y: {{ display: false, beginAtZero: true }}
            }}
          }}
        }});
      }}

      // Smooth Line Sparkline Chart with Nodes
      const sparkCtx = document.getElementById('finnovaSparkline');
      if (sparkCtx) {{
        if (sparklineInstance) sparklineInstance.destroy();
        sparklineInstance = new Chart(sparkCtx, {{
          type: 'line',
          data: {{
            labels: ['', '', '', '', '', '', '', ''],
            datasets: [{{
              data: sparkData,
              borderColor: '#5c56df',
              borderWidth: 2,
              pointBackgroundColor: '#ffffff',
              pointBorderColor: '#5c56df',
              pointBorderWidth: 2,
              pointRadius: 3.5,
              pointHoverRadius: 5,
              tension: 0.4,
              fill: false
            }}]
          }},
          options: {{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {{ legend: {{ display: false }}, tooltip: {{ enabled: false }} }},
            scales: {{
              x: {{ display: false }},
              y: {{ display: false }}
            }}
          }}
        }});
      }}
    }}

    // 2. Real-time API Fetcher Engine
    async function fetchDashboardRealtime() {{
      try {{
        const response = await fetch('/api/dashboard/realtime', {{
          headers: {{ 'Accept': 'application/json' }}
        }});
        if (!response.ok) return;
        const data = await response.json();
        if (data && data.success) {{
          updateDashboardUI(data);
        }}
      }} catch (err) {{
        console.warn('Real-time sync error:', err);
      }}
    }}

    // 3. UI Updater with Smooth Micro-Transitions
    function updateDashboardUI(data) {{
      const metrics = data.metrics || {{}};
      const cases = data.cases || [];

      // Update Nav Badge
      const badge = document.getElementById('navCaseCountBadge');
      if (badge && metrics.total_cases !== undefined) badge.innerText = metrics.total_cases;

      // Update Top 4 Metric Cards
      if (metrics.overdue_amount) document.getElementById('metricOverdueAmount').innerText = metrics.overdue_amount;
      if (metrics.overdue_trend) document.getElementById('metricOverdueTrend').innerText = metrics.overdue_trend + ' from last month';
      
      if (metrics.next_month_amount) document.getElementById('metricNextMonthAmount').innerText = metrics.next_month_amount;
      if (metrics.next_month_trend) document.getElementById('metricNextMonthTrend').innerText = metrics.next_month_trend + ' from last month';
      
      if (metrics.turnaround_days) document.getElementById('metricTurnaroundDays').innerText = metrics.turnaround_days;
      if (metrics.turnaround_trend) document.getElementById('metricTurnaroundTrend').innerText = metrics.turnaround_trend + ' from last month';
      
      if (metrics.vault_amount) document.getElementById('metricVaultAmount').innerText = metrics.vault_amount;

      // Update Tab Counts
      const draftEl = document.getElementById('tabCountDraft');
      if (draftEl && metrics.pending_cases !== undefined) draftEl.innerText = metrics.pending_cases;
      
      const unpaidEl = document.getElementById('tabCountUnpaid');
      if (unpaidEl && metrics.in_progress_cases !== undefined) unpaidEl.innerText = metrics.in_progress_cases;

      // Update Region Dockets Map Badges
      const regCounts = data.region_counts || {{}};
      for (const [rName, count] of Object.entries(regCounts)) {{
        const rId = rName.toLowerCase().replace(/\\s+/g, '-');
        const badgeEl = document.getElementById(`badge-${{rId}}`);
        if (badgeEl) {{
          badgeEl.textContent = `${{count}} Dockets`;
        }}
      }}

      // Store in memory
      currentInvoices = cases;

      // If no invoice is selected, or selected was removed, pick first
      if (!selectedInvoice && cases.length > 0) {{
        selectedInvoice = cases[0];
      }} else if (selectedInvoice) {{
        const stillExists = cases.find(c => c.id === selectedInvoice.id);
        if (stillExists) selectedInvoice = stillExists;
      }}

      // Render Cases List and Selected Case Details
      renderInvoicesList();
      renderSelectedInvoiceDetails();
    }}

    // 4. Render Invoices List (Left Sub-Panel)
    function renderInvoicesList() {{
      const container = document.getElementById('invoicesListContainer');
      if (!container) return;

      let filtered = currentInvoices.filter(c => {{
        // Tab filter
        if (currentFilterTab === 'unpaid' && c.status_badge === 'Approved') return false;
        if (currentFilterTab === 'draft' && c.status_badge !== 'Pending') return false;

        // Customer unit filter
        if (currentFilterCustomer !== 'all') {{
          const uLower = (c.unit_name || '').toLowerCase();
          if (currentFilterCustomer === 'crime_scene' && !uLower.includes('crime')) return false;
          if (currentFilterCustomer === 'lab' && !uLower.includes('lab')) return false;
          if (currentFilterCustomer === 'digital' && !uLower.includes('digital') && !uLower.includes('cyber')) return false;
          if (currentFilterCustomer === 'evidence' && !uLower.includes('evidence')) return false;
        }}

        // Status select filter
        if (currentFilterStatus !== 'all' && c.status_badge !== currentFilterStatus) return false;

        // Search filter
        if (currentSearchTerm) {{
          const term = currentSearchTerm.toLowerCase();
          const matchCase = (c.case_number || '').toLowerCase().includes(term);
          const matchSuspect = (c.suspect_name || '').toLowerCase().includes(term);
          const matchExhibits = (c.exhibits || []).some(e => (e.title || '').toLowerCase().includes(term));
          if (!matchCase && !matchSuspect && !matchExhibits) return false;
        }}

        return true;
      }});

      const listCountEl = document.getElementById('listCasesCount');
      if (listCountEl) listCountEl.innerText = `${{filtered.length}} Dockets Active`;

      if (filtered.length === 0) {{
        container.innerHTML = `
          <div class="text-center py-10 px-4 bg-slate-900/60 rounded-2xl border border-slate-800">
            <i data-lucide="file-question" class="w-8 h-8 text-slate-500 mx-auto mb-2"></i>
            <div class="text-xs font-bold text-slate-300">No matching dockets found</div>
            <div class="text-[10px] text-slate-500 mt-1">Try clearing active filters or search terms.</div>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      container.innerHTML = filtered.map(c => {{
        const isSelected = selectedInvoice && selectedInvoice.id === c.id;
        const rowClass = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/20' 
          : 'bg-slate-900/70 hover:bg-slate-800/80 text-slate-200 border border-slate-800';

        const statusPillClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'));

        const mugshotHtml = c.mugshot_front 
          ? `<img src="${{c.mugshot_front}}" class="w-full h-full object-cover">`
          : `<span class="font-black text-xs font-mono-code">${{c.suspect_name.substring(0, 2)}}</span>`;

        return `
          <div onclick="selectInvoiceById(${{c.id}})" 
               class="p-2.5 rounded-2xl ${{rowClass}} transition-all duration-200 cursor-pointer flex items-center justify-between group">
            
            <!-- Left: Avatar + Case # + Relative Date -->
            <div class="flex items-center space-x-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-slate-800 border border-white/10 overflow-hidden flex-shrink-0 flex items-center justify-center">
                ${{mugshotHtml}}
              </div>
              <div class="min-w-0">
                <div class="flex items-center space-x-2">
                  <span class="font-extrabold text-xs tracking-tight font-mono-code truncate">${{c.case_number}}</span>
                </div>
                <div class="text-[10px] opacity-75 font-medium">${{c.created_at_human}}</div>
              </div>
            </div>

            <!-- Right: Status Pill + Amount -->
            <div class="text-right flex items-center space-x-3 flex-shrink-0">
              <span class="px-2 py-0.5 rounded-full text-[9px] font-bold font-mono-code uppercase tracking-wider ${{statusPillClass}}">
                ${{c.status_badge === 'In Progress' ? 'Unsent' : c.status_badge}}
              </span>
              <div class="font-extrabold text-xs font-mono-code min-w-[75px] text-right">
                ${{c.total_amount}}
              </div>
            </div>

          </div>
        `;
      }}).join('');

      lucide.createIcons();
    }}

    // 5. Select Invoice by ID
    function selectInvoiceById(id) {{
      const found = currentInvoices.find(c => c.id === id);
      if (!found) return;
      selectedInvoice = found;

      renderInvoicesList();
      renderSelectedInvoiceDetails();

      // Pan & Zoom map to region of this case
      if (found.region && regionsDataset[found.region]) {{
        flyMapToRegion(found.region);
      }}
    }}

    // 6. Render Selected Invoice Details Card (Finnova Frosted Design)
    function renderSelectedInvoiceDetails() {{
      const c = selectedInvoice;
      if (!c) return;

      // Header values
      document.getElementById('cardInvoiceNumber').innerText = c.case_number;
      document.getElementById('cardInvoiceStatusBadge').innerText = c.status_badge === 'In Progress' ? 'Unsent' : c.status_badge;
      document.getElementById('cardCompanyName').innerText = c.unit_name || 'NACOC Forensic Directorate';
      
      document.getElementById('cardCustomerName').innerText = c.suspect_name;
      document.getElementById('cardCustomerRole').innerText = c.suspect_role || 'Target / Primary Suspect';

      const avatarEl = document.getElementById('cardCustomerAvatar');
      if (avatarEl) {{
        if (c.mugshot_front) {{
          avatarEl.innerHTML = `<img src="${{c.mugshot_front}}" class="w-full h-full object-cover">`;
        }} else {{
          avatarEl.innerText = c.suspect_name.substring(0, 2);
        }}
      }}

      // Exhibits 3 Cards Grid
      const gridEl = document.getElementById('exhibitCardsGrid');
      if (gridEl) {{
        const exhibits = c.exhibits || [];
        const exhibitCardsHtml = exhibits.slice(0, 3).map((ex, idx) => `
          <div class="bg-white/10 hover:bg-white/15 p-3.5 rounded-xl border border-white/10 shadow-xs transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <span class="font-black text-sm text-white font-mono-code">${{ex.value}}</span>
              <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-indigo-300"></i>
            </div>
            <div class="mt-2">
              <div class="text-[11px] font-bold text-slate-100 truncate">${{ex.title}}</div>
              <div class="text-[9px] text-indigo-200 mt-0.5 truncate">${{ex.quantity}} • ${{ex.category}}</div>
            </div>
          </div>
        `).join('');

        const addItemHtml = `
          <a href="{{{{ url('/cases') }}}}/${{c.id}}/edit" class="border border-dashed border-white/20 hover:border-indigo-400 p-3.5 rounded-xl transition-all flex flex-col items-center justify-center text-slate-300 hover:text-white group">
            <div class="w-6 h-6 rounded-full bg-white/10 group-hover:bg-indigo-600 flex items-center justify-center mb-1 text-xs font-bold transition-colors">+</div>
            <span class="text-[11px] font-bold">Add item</span>
          </a>
        `;

        gridEl.innerHTML = exhibitCardsHtml + addItemHtml;
        lucide.createIcons();
      }}

      // Bottom Totals
      document.getElementById('cardSubTotal').innerText = c.total_amount;
      document.getElementById('cardTotal').innerText = c.total_amount;
      document.getElementById('cardBalanceDue').innerText = c.balance_due;

      const linkEl = document.getElementById('cardActionLink');
      if (linkEl) linkEl.href = `{{{{ url('/cases') }}}}/${{c.id}}/review`;

      const primaryAction = document.getElementById('cardActionPrimary');
      if (primaryAction) primaryAction.href = `{{{{ url('/cases') }}}}/${{c.id}}/review`;
    }}

    // 7. 3D Ghana Map Physics & Camera Transform Engine
    function updateMapTransform(animate = false, duration = 400) {{
      const plane = document.getElementById('ghana3dPlane');
      if (!plane) return;

      if (animate) {{
        plane.style.transition = `transform ${{duration}}ms cubic-bezier(0.16, 1, 0.3, 1)`;
      }} else {{
        plane.style.transition = 'none';
      }}

      plane.style.transform = `
        translate3d(${{panX}}px, ${{panY}}px, 0px)
        scale3d(${{currentZoom}}, ${{currentZoom}}, 1)
        rotateX(${{rotX}}deg)
        rotateY(${{rotY}}deg)
        rotateZ(${{rotZ}}deg)
      `;
    }}

    function toggle3DView() {{
      is3DMode = !is3DMode;
      const btn = document.getElementById('btnToggle3D');
      if (is3DMode) {{
        rotX = 38;
        rotZ = -12;
        if (btn) btn.classList.add('bg-indigo-600', 'text-white');
      }} else {{
        rotX = 0;
        rotZ = 0;
        if (btn) btn.classList.remove('bg-indigo-600');
      }}
      updateMapTransform(true, 600);
    }}

    function zoomMap(factor) {{
      currentZoom = Math.max(0.65, Math.min(3.2, currentZoom * factor));
      updateMapTransform(true, 300);
    }}

    function resetMapView() {{
      rotX = 38;
      rotY = 0;
      rotZ = -12;
      panX = 0;
      panY = 0;
      currentZoom = 1.0;
      is3DMode = true;
      const btn = document.getElementById('btnToggle3D');
      if (btn) btn.classList.add('bg-indigo-600', 'text-white');

      // Clear selection highlight
      document.querySelectorAll('.region-polygon').forEach(p => p.classList.remove('region-active'));
      const banner = document.getElementById('regionActiveFocusBanner');
      if (banner) banner.classList.add('hidden');

      const townsGroup = document.getElementById('townMarkersGroup');
      if (townsGroup) townsGroup.innerHTML = '';

      updateMapTransform(true, 750);
    }}

    function flyMapToRegion(regionName) {{
      const r = regionsDataset[regionName];
      if (!r) return;

      // Highlight polygon
      document.querySelectorAll('.region-polygon').forEach(p => p.classList.remove('region-active'));
      const regId = regionName.toLowerCase().replace(/\\s+/g, '-');
      const poly = document.getElementById(`path-${{regId}}`);
      if (poly) poly.classList.add('region-active');

      // Calculate pan to center region
      const centerX = 400;
      const centerY = 480;
      panX = Math.round((centerX - r.cx) * 0.95);
      panY = Math.round((centerY - r.cy) * 0.95);
      currentZoom = 1.35;

      updateMapTransform(true, 650);
      renderTownMarkers(regionName);
    }}

    function selectRegionByName(regionName) {{
      if (hasDragged) return;
      selectedRegion = regionName;
      flyMapToRegion(regionName);

      // Filter Invoices list to this region
      const matching = currentInvoices.filter(c => (c.region || '').toLowerCase() === regionName.toLowerCase());
      if (matching.length > 0) {{
        selectedInvoice = matching[0];
        renderInvoicesList();
        renderSelectedInvoiceDetails();
      }}
    }}

    function onRegionHover(event, regionName) {{
      if (hasDragged) return;
      const tooltip = document.getElementById('regionHoverTooltip');
      const r = regionsDataset[regionName];
      if (!tooltip || !r) return;

      document.getElementById('tooltipRegionName').innerText = r.name + ' Region';
      document.getElementById('tooltipSub').innerText = `Capital: ${{r.capital}} • ${{r.cases}} Dockets`;

      tooltip.classList.remove('hidden');
      const stageRect = document.querySelector('.map-3d-stage').getBoundingClientRect();
      const x = event.clientX - stageRect.left + 15;
      const y = event.clientY - stageRect.top + 15;
      tooltip.style.left = `${{x}}px`;
      tooltip.style.top = `${{y}}px`;
    }}

    function onRegionLeave() {{
      const tooltip = document.getElementById('regionHoverTooltip');
      if (tooltip) tooltip.classList.add('hidden');
    }}

    function renderTownMarkers(regionName) {{
      const container = document.getElementById('townMarkersGroup');
      if (!container) return;
      container.innerHTML = '';

      const towns = townsDataset[regionName] || [];
      const banner = document.getElementById('regionActiveFocusBanner');
      const bannerTitle = document.getElementById('focusRegionTitle');
      const bannerCount = document.getElementById('focusTownsCount');

      if (banner && bannerTitle && bannerCount) {{
        bannerTitle.innerText = regionName + ' Region';
        bannerCount.innerText = `${{towns.length}} Stations Active`;
        banner.classList.remove('hidden');
      }}

      towns.forEach((t, idx) => {{
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'town-marker');
        g.style.cursor = 'pointer';

        const labelW = Math.max(t.name.length * 6.2 + 16, 54);
        const labelH = 15;
        const labelX = t.x - labelW / 2;
        const labelY = t.y - 23;

        g.innerHTML = `
          <circle cx="${{t.x}}" cy="${{t.y}}" r="8" fill="#5c56df" opacity="0.3" class="town-pulse-ring" pointer-events="none" />
          <circle cx="${{t.x}}" cy="${{t.y}}" r="4.5" fill="#5c56df" stroke="#ffffff" stroke-width="1.8" />
          <circle cx="${{t.x}}" cy="${{t.y}}" r="1.5" fill="#ffffff" pointer-events="none" />
          <rect x="${{labelX}}" y="${{labelY}}" width="${{labelW}}" height="${{labelH}}" rx="4.5" fill="#181926" fill-opacity="0.95" stroke="#ffffff" stroke-width="1.2" />
          <text x="${{t.x}}" y="${{labelY + 10.5}}" text-anchor="middle" fill="#ffffff" font-size="8.5" font-weight="700" font-family="'JetBrains Mono', monospace" pointer-events="none">${{t.name}}</text>
        `;

        container.appendChild(g);
      }});
    }}

    // 8. Drag & Pan Event Listeners for 3D Stage
    const stage = document.querySelector('.map-3d-stage');
    if (stage) {{
      stage.addEventListener('mousedown', (e) => {{
        if (e.button !== 0) return;
        isDragging = true;
        hasDragged = false;
        startX = e.clientX;
        startY = e.clientY;
        stage.classList.add('is-dragging');
      }});

      window.addEventListener('mousemove', (e) => {{
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        if (Math.abs(dx) > 3 || Math.abs(dy) > 3) hasDragged = true;

        panX += dx;
        panY += dy;
        startX = e.clientX;
        startY = e.clientY;
        updateMapTransform(false);
      }});

      window.addEventListener('mouseup', () => {{
        if (isDragging) {{
          isDragging = false;
          stage.classList.remove('is-dragging');
        }}
      }});

      stage.addEventListener('wheel', (e) => {{
        e.preventDefault();
        const factor = e.deltaY < 0 ? 1.08 : 0.92;
        zoomMap(factor);
      }}, {{ passive: false }});
    }}

    // 9. View Mode Switcher ('split', 'dossier', 'map')
    function switchMainView(mode) {{
      currentMainView = mode;
      const dossierCard = document.getElementById('dossierCardWrapper');
      const mapStage = document.getElementById('mapStageWrapper');

      const btnSplit = document.getElementById('btnViewSplit');
      const btnDossier = document.getElementById('btnViewDossier');
      const btnMap = document.getElementById('btnViewMap');

      [btnSplit, btnDossier, btnMap].forEach(b => {{
        if (b) {{
          b.classList.remove('bg-indigo-600', 'text-white', 'shadow-sm');
          b.classList.add('text-slate-400');
        }}
      }});

      if (mode === 'split') {{
        if (dossierCard) dossierCard.classList.remove('hidden');
        if (mapStage) mapStage.classList.remove('hidden');
        if (btnSplit) {{
          btnSplit.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
          btnSplit.classList.remove('text-slate-400');
        }}
      }} else if (mode === 'dossier') {{
        if (dossierCard) dossierCard.classList.remove('hidden');
        if (mapStage) mapStage.classList.add('hidden');
        if (btnDossier) {{
          btnDossier.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
          btnDossier.classList.remove('text-slate-400');
        }}
      }} else if (mode === 'map') {{
        if (dossierCard) dossierCard.classList.add('hidden');
        if (mapStage) mapStage.classList.remove('hidden');
        if (btnMap) {{
          btnMap.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
          btnMap.classList.remove('text-slate-400');
        }}
      }}
    }}

    // 10. Filter Helpers
    function setInvoiceTab(tab) {{
      currentFilterTab = tab;
      const tabAll = document.getElementById('tabBtnAll');
      const tabDraft = document.getElementById('tabBtnDraft');
      const tabUnpaid = document.getElementById('tabBtnUnpaid');

      [tabAll, tabDraft, tabUnpaid].forEach(t => {{
        if (t) {{
          t.classList.remove('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
          t.classList.add('text-slate-300');
        }}
      }});

      if (tab === 'all' && tabAll) {{
        tabAll.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }} else if (tab === 'draft' && tabDraft) {{
        tabDraft.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }} else if (tab === 'unpaid' && tabUnpaid) {{
        tabUnpaid.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }}

      renderInvoicesList();
    }}

    function applyFilters() {{
      const custEl = document.getElementById('filterCustomerSelect');
      const statusEl = document.getElementById('filterStatusSelect');
      if (custEl) currentFilterCustomer = custEl.value;
      if (statusEl) currentFilterStatus = statusEl.value;
      renderInvoicesList();
    }}

    function onSearchInvoiceInput(val) {{
      currentSearchTerm = val.trim();
      renderInvoicesList();
    }}

    function toggleFilterToolbar() {{
      const tb = document.getElementById('filterToolbar');
      if (tb) tb.classList.toggle('hidden');
    }}

    function refreshDashboardLive() {{
      fetchDashboardRealtime();
    }}

    // 11. Initial Startup Engine
    document.addEventListener('DOMContentLoaded', () => {{
      initFinnovaCharts();
      updateMapTransform(false);
      fetchDashboardRealtime();

      // Poll real-time updates every 8 seconds
      setInterval(fetchDashboardRealtime, 8000);
    }});
  </script>

</body>
</html>
'''

with open('resources/views/Forensic_Head/head.blade.php', 'w', encoding='utf-8') as f:
    f.write(blade_content)

print("Merged dashboard built successfully: resources/views/Forensic_Head/head.blade.php")
