import json
import os
import sys

scratch_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.append(scratch_dir)
from generate_districts import districts_data

with open(os.path.join(scratch_dir, 'ghana_regions_svg_optimized.json'), 'r', encoding='utf-8') as f:
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

# Same paths for the tactical fullscreen map
tactical_paths_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    tactical_paths_markup.append(f'''
            <path id="tac-path-{reg_id}"
                  data-name="{r['name']}"
                  d="{r['path']}"
                  class="tactical-region-polygon transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
                  stroke-width="1.4"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('{r['name']}', true)"
                  onmouseenter="onTacRegionHover(event, '{r['name']}')"
                  onmouseleave="onTacRegionLeave()" />''')
tactical_paths_html = ''.join(tactical_paths_markup)

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

# Tactical direct region labels
tactical_labels_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    tactical_labels_markup.append(f'''
            <g id="tac-lbl-{reg_id}" class="pointer-events-none transition-opacity duration-300">
              <text x="{r['cx']}" y="{r['cy'] - 4}" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest fill-emerald-300 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">{r['name'].upper()}</text>
              <text id="tac-badge-{reg_id}" x="{r['cx']}" y="{r['cy'] + 10}" 
                    text-anchor="middle" 
                    class="font-mono-code font-bold text-[9px] fill-emerald-400/90 select-none">{r['cases']} DOCKETS</text>
            </g>''')
tactical_labels_html = ''.join(tactical_labels_markup)

# Serialize dataset for JS
regions_dataset_js = json.dumps({r['name']: r for r in regions})
districts_dataset_js = json.dumps(districts_data)

def generate_blade(unit_key, unit_name, unit_subtitle, default_head):
    return f'''@php
    $authUser = auth()->user();
    $cases = $cases ?? \\App\\Models\\ForensicCase::allCases();
    $userUnit = '{unit_name}';
    $unitDisplayTitle = '{unit_subtitle}';
    $dbUnitHead = \\App\\Models\\User::where('is_head', true)
        ->where(function($q) {{
            $q->where('unit', 'LIKE', '%{unit_key}%')
              ->orWhere('unit', 'LIKE', '%Forensic%');
        }})->first() ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : '{default_head}');
    $officerRole = ($authUser && $authUser->is_head) ? '{unit_name} Head' : 'Commanding Officer';

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
  <title>NACOC FORENSIC | {unit_name} Tactical Command & Regional Radar</title>
  
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
            'tactical-glow': '0 0 50px rgba(16, 185, 129, 0.35)'
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

    /* High-Caseload Forensic Red Hotspot Region Polygons */
    .region-polygon.region-hotspot {{
      fill: #dc2626 !important;
      stroke: #fca5a5 !important;
      stroke-width: 2.2px !important;
      filter: drop-shadow(0 0 25px rgba(220, 38, 38, 0.85)) !important;
      animation: hotspotPulse 2.8s infinite ease-in-out;
    }}
    .region-polygon.region-hotspot:hover {{
      fill: #ef4444 !important;
      stroke: #ffffff !important;
      filter: drop-shadow(0 0 35px rgba(239, 68, 68, 0.95)) !important;
    }}
    .tactical-region-polygon.region-hotspot {{
      fill: #991b1b !important;
      stroke: #f87171 !important;
      stroke-width: 2.5px !important;
      filter: drop-shadow(0 0 35px rgba(239, 68, 68, 0.9)) !important;
      animation: tacHotspotPulse 2.8s infinite ease-in-out;
    }}
    .tactical-region-polygon.region-hotspot:hover {{
      fill: #b91c1c !important;
      stroke: #fca5a5 !important;
      filter: drop-shadow(0 0 45px rgba(239, 68, 68, 1)) !important;
    }}
    @keyframes hotspotPulse {{
      0%, 100% {{ filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.75)); }}
      50% {{ filter: drop-shadow(0 0 35px rgba(239, 68, 68, 1)); }}
    }}
    @keyframes tacHotspotPulse {{
      0%, 100% {{ filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.8)); }}
      50% {{ filter: drop-shadow(0 0 45px rgba(239, 68, 68, 1)); }}
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
    .radar-ring-tactical {{
      border: 1px dashed rgba(16, 185, 129, 0.35);
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

    .tactical-region-polygon.region-active {{
      fill: #10b981 !important;
      stroke: #ffffff !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 35px rgba(16, 185, 129, 0.8)) !important;
      opacity: 1 !important;
    }}

    /* Tactical HUD Grid Overlay */
    .tactical-grid-bg {{
      background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
      background-size: 40px 40px;
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
  <!-- MASTER DASHBOARD CONTAINER (Rounded Floating Card)       -->
  <!-- ======================================================== -->
  <main class="w-full max-w-[1780px] bg-white rounded-[2rem] sm:rounded-[2.5rem] border border-slate-200/80 shadow-[0_20px_60px_-15px_rgba(15,23,42,0.08)] p-4 sm:p-6 lg:p-8 flex flex-col space-y-6 relative overflow-hidden">
    
    <!-- ====================================================== -->
    <!-- 1. TOP NAVIGATION BAR (Finnova Layout, Forensics Theme)-->
    <!-- ====================================================== -->
    <nav class="flex flex-col lg:flex-row items-center justify-between gap-4 pb-2 border-b border-slate-100">
      
      <!-- Left: NACOC Forensic Glyph & Directorate Badge -->
      <div class="flex items-center space-x-3 w-full lg:w-auto justify-between lg:justify-start">
        <a href="{{{{ url('/dashboard') }}}}" class="flex items-center space-x-3 group">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#5c56df] via-[#6d67e6] to-[#8b5cf6] flex items-center justify-center text-white shadow-md shadow-indigo-500/25 group-hover:scale-105 transition-transform">
            <!-- Directorate Shield / Forensic Fingerprint Glyph -->
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
              <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
            </svg>
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-lg font-black tracking-tight text-slate-900 font-mono-code">NACOC FORENSIC</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-[#5c56df] font-bold font-mono-code border border-indigo-100">{unit_key.upper()}</span>
            </div>
            <p class="text-[11px] text-slate-400 font-medium">National Command & Forensic Intelligence Directorate</p>
          </div>
        </a>

        <!-- Mobile Quick Profile Trigger -->
        <div class="lg:hidden flex items-center space-x-2">
          <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
            {{{{ strtoupper(substr($headName, 0, 2)) }}}}
          </div>
        </div>
      </div>

      <!-- Center: Curved Segmented Navigation Pills -->
      <div class="flex items-center bg-[#181926] p-1.5 rounded-full shadow-inner overflow-x-auto max-w-full text-xs font-semibold">
        <!-- Count badge pill -->
        <span class="w-7 h-7 rounded-full bg-white/10 text-slate-300 font-mono-code text-[11px] flex items-center justify-center mr-1.5 flex-shrink-0" title="16 Administrative Regions">
          16
        </span>

        <!-- Nav Item: Supreme Command / Overview -->
        <a href="{{{{ url('/Forensic_Head') }}}}" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors flex items-center space-x-1.5 whitespace-nowrap">
          <span class="text-slate-400 font-light">+</span>
          <span>Overview</span>
        </a>

        <!-- Nav Item: Lab Analysis -->
        <a href="{{{{ url('/Lab_Analysis') }}}}" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors whitespace-nowrap">
          Lab Analysis
        </a>

        <!-- Nav Item: Active Forensic Dockets (Signature Purple Pill) -->
        <a href="{{{{ url('/cases') }}}}" class="px-4 py-1.5 rounded-full bg-[#5c56df] text-white font-bold shadow-md shadow-indigo-500/30 flex items-center space-x-1.5 whitespace-nowrap">
          <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
          <span>Active Cases</span>
        </a>

        <!-- Nav Item: Evidence Vault -->
        <a href="{{{{ url('/Evidence_Management') }}}}" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors whitespace-nowrap">
          Evidence Vault
        </a>

        <!-- Nav Item: Field Ops (CSSU) -->
        <a href="{{{{ url('/Crime_Scene_Support/head') }}}}" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors whitespace-nowrap">
          Field Ops
        </a>

        <!-- Nav Item: Cyber Forensics (DPFU) -->
        <a href="{{{{ url('/Digital_Forensics') }}}}" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors whitespace-nowrap">
          Cyber & Digital
        </a>
      </div>

      <!-- Right: Utility Icons, Live Alert Badge, User Avatar -->
      <div class="hidden lg:flex items-center space-x-2">
        
        <!-- Utility icon 1: Case Registry History -->
        <a href="{{{{ url('/history') }}}}" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Casework History">
          <i data-lucide="file-text" class="w-4 h-4"></i>
        </a>

        <!-- Utility icon 2: Live Telemetry Stream -->
        <a href="{{{{ url('/api/dashboard/realtime') }}}}" target="_blank" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Live Telemetry JSON Feed">
          <i data-lucide="activity" class="w-4 h-4"></i>
        </a>

        <!-- Utility icon 3: Evidence Vault -->
        <a href="{{{{ url('/Evidence_Management') }}}}" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Central Evidence Vault">
          <i data-lucide="shield" class="w-4 h-4"></i>
        </a>

        <!-- Utility icon 4: Sync Live Database Feed -->
        <button type="button" onclick="refreshRealtimeData()" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Sync Real-Time Telemetry">
          <i data-lucide="refresh-cw" class="w-4 h-4" id="syncSpinner"></i>
        </button>

        <!-- Notification Bell with Red Badge Dot -->
        <div class="relative">
          <button type="button" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Critical Forensic Alerts">
            <i data-lucide="bell" class="w-4 h-4"></i>
          </button>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white animate-pulse"></span>
        </div>

        <!-- Settings Cog -->
        <a href="{{{{ url('/profile/settings') }}}}" class="w-9 h-9 rounded-full border border-slate-200/90 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs" title="Officer Settings">
          <i data-lucide="settings" class="w-4 h-4"></i>
        </a>

        <!-- User Avatar Circle -->
        <a href="{{{{ url('/profile/settings') }}}}" class="w-9 h-9 rounded-full overflow-hidden ring-2 ring-indigo-500/20 hover:ring-indigo-500/60 transition-all flex items-center justify-center bg-gradient-to-tr from-[#5c56df] to-purple-600 text-white font-bold text-xs" title="{{{{ $headName }}}} ({{{{ $officerRole }}}})">
          @if ($authUser && $authUser->avatar)
            <img src="{{{{ asset($authUser->avatar) }}}}" alt="{{{{ $headName }}}}" class="w-full h-full object-cover">
          @else
            <span>{{{{ strtoupper(substr($headName, 0, 2)) }}}}</span>
          @endif
        </a>

      </div>

    </nav>

    <!-- ====================================================== -->
    <!-- 2. SUBHEADER: Title, Subtitle, Filter & + Register CTA -->
    <!-- ====================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
      
      <!-- Left: Back Arrow + Forensic Dockets Title + Description -->
      <div class="flex items-center space-x-3.5">
        <button type="button" onclick="history.back()" class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 shadow-xs transition-colors" title="Go Back">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </button>

        <div>
          <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-slate-900 leading-tight">
            Forensic Dockets
          </h1>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Manage, track, and analyze all active crime scene investigations and evidence seizures in one place.
          </p>
        </div>
      </div>

      <!-- Right: Filter & Register Case Button -->
      <div class="flex items-center space-x-3">
        <!-- Filter sliders button -->
        <button type="button" onclick="toggleFilterToolbar()" class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 shadow-xs transition-colors" title="Filter Options">
          <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
        </button>

        <!-- + Register New Case / Intake -->
        <a href="{{{{ url('/arrest-person') }}}}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-full bg-[#5c56df] hover:bg-[#4d47d1] text-white font-bold text-xs shadow-lg shadow-indigo-500/25 transition-all hover:shadow-indigo-500/40 hover:-translate-y-0.5">
          <i data-lucide="plus" class="w-4 h-4 stroke-[2.5]"></i>
          <span>Register New Case</span>
        </a>
      </div>

    </div>

    <!-- ====================================================== -->
    <!-- 3. FOUR TOP METRIC CARDS (100% FORENSIC DATA)           -->
    <!-- ====================================================== -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5">
      
      <!-- CARD 1: Pending Lab Sign-off (with forensic desk photograph) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Awaiting Lab Sign-off</span>
            <span class="w-5 h-5 rounded-full bg-rose-50 border border-rose-200 text-rose-500 flex items-center justify-center text-[10px] font-black">!</span>
          </div>

          <!-- Main Value -->
          <div id="metricOverdueAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            14 Dockets
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-rose-500 mt-1 flex items-center space-x-1">
            <span id="metricOverdueTrend">↑ 12.5% incoming dockets</span>
          </div>
        </div>

        <!-- Desk Illustration Image on bottom left / center -->
        <div class="mt-4 pt-3 border-t border-slate-100/80 flex items-center justify-between">
          <div class="w-24 h-16 rounded-xl overflow-hidden shadow-xs border border-slate-200/60 bg-slate-100 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
            <img src="{{{{ asset('images/dashboard/finnova_desk.jpg') }}}}" 
                 alt="Forensic Workstation" 
                 class="w-full h-full object-cover object-center" 
                 onerror="this.src='https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=400&q=80'">
          </div>
          <div class="text-right">
            <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100">
              Verification Required
            </span>
            <div class="text-[10px] text-slate-400 mt-1">Registry Intake Queue</div>
          </div>
        </div>
      </div>

      <!-- CARD 2: Monthly Evidence Intake (Purple Vertical Bar Chart) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Monthly Evidence Intake</span>
            <span class="w-5 h-5 rounded-full bg-indigo-50 border border-indigo-200 text-[#5c56df] flex items-center justify-center text-[10px]">
              <i data-lucide="calendar" class="w-3 h-3"></i>
            </span>
          </div>

          <!-- Main Value -->
          <div id="metricNextMonthAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            142 Exhibits
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-[#5c56df] mt-1 flex items-center space-x-1">
            <span id="metricNextMonthTrend">↑ 8.2% monthly caseload</span>
          </div>
        </div>

        <!-- 6-Month Casework Bar Chart -->
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaBarChart"></canvas>
        </div>
      </div>

      <!-- CARD 3: Average Turnaround Time (Smooth Purple Sparkline) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Avg. Analysis Turnaround</span>
            <span class="w-5 h-5 rounded-full bg-cyan-50 border border-cyan-200 text-cyan-600 flex items-center justify-center text-[10px]">
              <i data-lucide="clock" class="w-3 h-3"></i>
            </span>
          </div>

          <!-- Main Value -->
          <div id="metricTurnaroundDays" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
            4.2 days
          </div>

          <!-- Trend -->
          <div class="text-[11px] font-semibold text-emerald-500 mt-1 flex items-center space-x-1">
            <span id="metricTurnaroundTrend">↓ 2 days faster than last month</span>
          </div>
        </div>

        <!-- Smooth Connected Line Sparkline -->
        <div class="mt-4 h-16 w-full">
          <canvas id="finnovaSparkline"></canvas>
        </div>
      </div>

      <!-- CARD 4: Evidence Vault Inventory & Categories -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <!-- Header row -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Evidence Vault Inventory</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-[10px]">
                <i data-lucide="shield-check" class="w-3 h-3"></i>
              </span>
              <a href="{{{{ url('/Evidence_Management') }}}}" class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]" title="Inspect Vault">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>

          <!-- Main Value -->
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              1,865 Items
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">Vault Secured</span>
          </div>
        </div>

        <!-- Evidence Vault Category Pills -->
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          <!-- Category 1: Narcotics -->
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Narcotic Exhibits">
            <span class="font-mono-code text-[9px] text-slate-400">BAY A</span>
            <span class="font-bold text-slate-700">Narcotics</span>
          </div>

          <!-- Category 2: Electronics (Active Purple Pill) -->
          <div class="flex-1 py-1 px-2 rounded-xl bg-[#5c56df] text-white shadow-sm flex flex-col items-center" title="Digital & Mobile Devices">
            <span class="font-mono-code text-[9px] text-indigo-200">BAY B</span>
            <span class="font-bold">Devices</span>
          </div>

          <!-- Category 3: Ballistics / Weapons -->
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Weapons & Ballistics">
            <span class="font-mono-code text-[9px] text-slate-400">BAY C</span>
            <span class="font-bold text-slate-700">Weapons</span>
          </div>

          <!-- Vault Inspection CTA -->
          <a href="{{{{ url('/Evidence_Management') }}}}" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-[10px] font-bold shadow-xs transition-colors flex items-center">
            Inspect
          </a>
        </div>
      </div>

    </section>

    <!-- ====================================================== -->
    <!-- 4. ACTIVE FORENSIC FILTERS TOOLBAR                    -->
    <!-- ====================================================== -->
    <div id="filterToolbar" class="flex flex-wrap items-center justify-between gap-3 text-xs">
      
      <!-- Left Filters -->
      <div class="flex flex-wrap items-center gap-2">
        
        <!-- Active filters badge -->
        <div class="flex items-center space-x-1.5 bg-slate-100 text-slate-700 px-3 py-1.5 rounded-full font-bold">
          <span>Active filters</span>
          <span class="w-4 h-4 rounded-full bg-slate-800 text-white text-[10px] flex items-center justify-center font-mono-code">2</span>
        </div>

        <!-- All Forensic Units dropdown -->
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

        <!-- All Forensic Statuses dropdown -->
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

        <!-- Date Range Pill 1 -->
        <div class="flex items-center space-x-1.5 bg-white border border-slate-200/90 px-3 py-1.5 rounded-full text-slate-700 shadow-xs">
          <span>November 2026</span>
          <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
        </div>

        <!-- Date Range Pill 2 -->
        <div class="flex items-center space-x-1.5 bg-white border border-slate-200/90 px-3 py-1.5 rounded-full text-slate-700 shadow-xs">
          <span>December 2026</span>
          <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
        </div>

      </div>

      <!-- Right: Search Input -->
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
    <!-- (Cases List + 3D Ghana Map + Live Forensic Dossier)    -->
    <!-- ====================================================== -->
    <section class="bg-[#181926] rounded-[2rem] p-4 sm:p-5 text-white shadow-finnova-dark border border-slate-800/80 flex flex-col space-y-4 relative overflow-hidden">
      
      <!-- Top Bar of Dark Stage: Tabs & Command Mode Switchers -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-800/90">
        
        <!-- Left: Curved Tabs -->
        <div class="bg-white/10 p-1 rounded-full inline-flex items-center space-x-1 text-xs font-semibold">
          <button type="button" onclick="setInvoiceTab('all')" id="tabBtnAll" class="px-4 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors">
            All Dockets
          </button>
          <button type="button" onclick="setInvoiceTab('analysis')" id="tabBtnAnalysis" class="px-3.5 py-1.5 rounded-full text-slate-300 hover:text-white transition-colors flex items-center space-x-1.5">
            <span>Under Analysis</span>
            <span id="tabCountAnalysis" class="bg-white/10 text-slate-300 px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{{{ $pendingCasesCount }}}}</span>
          </button>
          <!-- Active Tab: Active Cases -->
          <button type="button" onclick="setInvoiceTab('active')" id="tabBtnActive" class="bg-[#5c56df] text-white px-4 py-1.5 rounded-full font-bold shadow-md shadow-indigo-500/25 flex items-center space-x-1.5">
            <span>Active Cases</span>
            <span id="tabCountActive" class="bg-white/20 text-white px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{{{ $totalCases }}}}</span>
          </button>
        </div>

        <!-- Center / Right: Interactive View Modes, Tactical Button & Map Controls -->
        <div class="flex items-center space-x-2">
          
          <!-- Tactical Fullscreen Mode Launch Button -->
          <button type="button" 
                  onclick="openTacticalMode()" 
                  class="px-3.5 py-1 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md shadow-emerald-500/30 flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95 group" 
                  title="Launch Fullscreen Tactical Operations Theatre (Press 'T')">
            <span class="w-2 h-2 rounded-full bg-emerald-200 group-hover:bg-white animate-ping"></span>
            <i data-lucide="maximize-2" class="w-3.5 h-3.5 stroke-[2.5]"></i>
            <span class="font-mono-code tracking-wider text-[11px]">TACTICAL THEATRE</span>
          </button>

          <!-- View Mode Segmented Control -->
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
          <button type="button" onclick="openTacticalMode()" class="w-8 h-8 rounded-full border border-slate-700/80 hover:bg-slate-800 text-emerald-400 flex items-center justify-center" title="Fullscreen Radar Mode">
            <i data-lucide="scan" class="w-4 h-4"></i>
          </button>
          <button type="button" class="w-8 h-8 rounded-full border border-slate-700/80 hover:bg-slate-800 text-slate-400 flex items-center justify-center">
            <i data-lucide="more-vertical" class="w-4 h-4"></i>
          </button>

        </div>

      </div>

      <!-- MAIN TRI-COMMAND WORKSPACE GRID (List + 3D Map + Forensic Dossier) -->
      <div id="mainWorkspaceGrid" class="grid grid-cols-12 gap-4 items-start">
        
        <!-- PANEL 1: Active Forensic Cases List -->
        <div id="panelInvoicesList" class="col-span-12 xl:col-span-3 flex flex-col space-y-2">
          
          <div class="flex items-center justify-between px-1 pb-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono-code" id="listHeaderTitle">Active Forensic Cases</span>
            <span class="text-[10px] font-mono-code text-indigo-400" id="listCasesCount">{{{{ $totalCases }}}} Active</span>
          </div>

          <!-- Real-Time Case Rows Container -->
          <div id="invoicesListContainer" class="space-y-1.5 max-h-[580px] overflow-y-auto pr-1">
            <div class="text-center py-8 text-xs text-slate-500">Loading live forensic cases from database...</div>
          </div>

        </div>

        <!-- PANEL 2: 3D CLICKABLE GHANA MAP (Centerpiece) -->
        <div id="panelGhanaMap" class="col-span-12 xl:col-span-5 flex flex-col">
          
          <div id="mapStageWrapper" class="relative w-full h-[580px] flex items-center justify-center bg-slate-950/70 rounded-2xl border border-slate-800/80 overflow-hidden select-none">
            
            <!-- Tactical Quick Expand Button inside map corner -->
            <button type="button" 
                    onclick="openTacticalMode()" 
                    class="absolute top-3 right-3 z-30 px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/40 backdrop-blur-md text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-xl transition-all group"
                    title="Launch Fullscreen Tactical Radar Theatre">
              <span class="w-2 h-2 rounded-full bg-emerald-400 group-hover:bg-white animate-ping"></span>
              <i data-lucide="maximize-2" class="w-3.5 h-3.5 stroke-[2]"></i>
              <span class="tracking-wider">TACTICAL HUD</span>
            </button>

            <!-- Radial Ambient Glow behind Ghana -->
            <div class="absolute w-[480px] h-[480px] rounded-full pointer-events-none opacity-30 blur-3xl bg-gradient-to-tr from-[#5c56df] via-purple-600/40 to-transparent"></div>

            <!-- Concentric Radar / Orbital Rings centered behind Ghana -->
            <div class="radar-ring w-[540px] h-[540px]"></div>
            <div class="radar-ring w-[400px] h-[400px] radar-pulse"></div>
            <div class="radar-ring w-[260px] h-[260px]"></div>

            <!-- Subtle Coordinate Grid Ticks -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-25">
              <svg class="w-[540px] h-[540px]" viewBox="0 0 800 800">
                <circle cx="400" cy="400" r="320" fill="none" stroke="#475569" stroke-width="1" stroke-dasharray="2, 8" />
                <line x1="400" y1="40" x2="400" y2="760" stroke="#475569" stroke-width="0.8" stroke-dasharray="4, 6" />
                <text x="408" y="60" fill="#94a3b8" font-size="10" font-family="'JetBrains Mono', monospace">0° PRIME MERIDIAN</text>
              </svg>
            </div>

            <!-- 3D Perspective Map Stage Container -->
            <div class="map-3d-stage relative w-full h-full flex items-center justify-center">
              
              <div id="ghana3dPlane" class="map-3d-plane relative w-full h-full max-w-[560px] flex items-center justify-center">
                
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

                  <!-- 4. Active MMDA District Map & Linked Case Beacons for Selected Region -->
                  <g id="districtLayersGroup">
                    <!-- Populated via renderDistrictMap() -->
                  </g>

                </svg>

              </div>

              <!-- District Map Navigation Header (Return to Country Map + District Title) -->
              <div id="districtNavHeader" class="hidden absolute top-3 left-4 z-30 flex items-center space-x-2">
                <button type="button" 
                        onclick="resetMapView()" 
                        class="px-3 py-1.5 rounded-xl bg-slate-900/95 hover:bg-indigo-600 text-white border border-indigo-500/50 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl transition-all group">
                  <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                  <span>Return to Ghana Map (16 Regions)</span>
                </button>
                <span id="districtDrillTitleBadge" class="px-3 py-1.5 rounded-xl bg-slate-900/90 text-slate-200 border border-slate-700 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-xl">
                  <span id="districtHotspotIndicator" class="w-2 h-2 rounded-full bg-indigo-400"></span>
                  <span id="districtDrillTitle">Greater Accra Districts</span>
                </span>
              </div>

              <!-- Dynamic Floating Region Hover Tooltip -->
              <div id="regionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-900/95 text-white backdrop-blur-md px-3 py-1.5 rounded-xl text-xs shadow-2xl border border-slate-700/80 transition-opacity duration-150">
                <div id="tooltipRegionName" class="font-bold text-indigo-300">Region Name</div>
                <div id="tooltipSub" class="text-[10px] text-slate-300">Click to filter dockets</div>
              </div>

              <!-- Subtle Pan/Zoom Hint -->
              <div class="absolute bottom-3 left-4 z-20 pointer-events-none flex items-center space-x-2 bg-slate-900/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-semibold text-slate-400 border border-slate-800 shadow-xs">
                <i data-lucide="move" class="w-3 h-3 text-indigo-400"></i>
                <span>Drag • Scroll zoom • Click region to filter</span>
              </div>

            </div>

          </div>

        </div>

        <!-- PANEL 3: FROSTED FORENSIC DOSSIER DETAILS CARD (100% FORENSICS) -->
        <div id="panelDossierCard" class="col-span-12 xl:col-span-4 flex flex-col">
          
          <div id="dossierCardWrapper" class="bg-gradient-to-br from-[#2f2e59]/90 to-[#222143]/90 rounded-2xl p-5 border border-white/10 shadow-2xl relative overflow-hidden backdrop-blur-xl transition-all h-[580px] flex flex-col justify-between">
            
            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

            <div>
              <!-- Card Top Row: Docket # | Submitting Unit | Suspect Profile -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-white/10 relative z-10">
                
                <!-- Left: Docket # & Forensic Status -->
                <div>
                  <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Docket Details</span>
                  <div class="flex items-center space-x-2 mt-0.5">
                    <h3 id="cardInvoiceNumber" class="text-lg font-black text-white tracking-tight font-mono-code"># NCC-2026-2572</h3>
                    <span id="cardInvoiceStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code bg-white/10 text-indigo-200 border border-white/10">Under Investigation</span>
                  </div>
                </div>

                <!-- Center: Submitting Unit / Directorate Command -->
                <div class="text-left">
                  <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Submitting Unit</span>
                  <div class="flex items-center space-x-1.5 mt-0.5">
                    <span id="cardCompanyName" class="text-sm font-extrabold text-white tracking-tight">Crime Scene Support</span>
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-400"></i>
                  </div>
                </div>

                <!-- Right: Suspect Profile & Mugshot -->
                <div class="flex items-center space-x-2">
                  <div id="cardCustomerAvatar" class="w-9 h-9 rounded-full bg-slate-700 overflow-hidden ring-2 ring-indigo-400/40 flex-shrink-0 flex items-center justify-center text-white font-bold text-xs">
                    KM
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Primary Target</span>
                    <div id="cardCustomerName" class="text-xs font-bold text-white truncate max-w-[100px]">Kofi Mensah</div>
                    <div id="cardCustomerRole" class="text-[10px] text-slate-400 truncate max-w-[100px]">Ghanaian • 34 yrs</div>
                  </div>
                </div>

              </div>

              <!-- Investigating Officer & Raid Location sub-banner -->
              <div class="mt-3 px-3 py-2 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between text-[11px] text-slate-300">
                <div class="truncate">
                  <span class="text-slate-400">Lead Officer:</span>
                  <span id="cardOfficerName" class="font-bold text-white ml-1">Insp. K. Mensah</span>
                </div>
                <div class="truncate text-right ml-2">
                  <span class="text-slate-400">Seizure:</span>
                  <span id="cardLocationName" class="font-bold text-indigo-300 ml-1">Kotoka Airport Cargo</span>
                </div>
              </div>

              <!-- Forensics Exhibits Grid (3 items + 1 Add Exhibit) -->
              <div class="mt-3">
                <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mb-1.5">Recovered Exhibits & Evidence</div>
                <div id="exhibitCardsGrid" class="grid grid-cols-2 gap-2.5 relative z-10">
                  <!-- Dynamically populated per selected docket -->
                </div>
              </div>
            </div>

            <!-- Card Bottom Bar: Chain of Custody & Docket Action -->
            <div class="pt-3 border-t border-white/10 relative z-10 flex flex-col space-y-3">
              
              <!-- Chain of Custody & Judicial Status -->
              <div class="flex items-center justify-between text-xs">
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Total Exhibits</span>
                  <span id="cardSubTotal" class="font-bold text-white font-mono-code">3 Items Logged</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Chain of Custody</span>
                  <span id="cardTotal" class="font-bold text-emerald-400 font-mono-code">EMU Vault Secured</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 block font-medium">Judicial Mandate</span>
                  <span id="cardBalanceDue" class="font-black text-indigo-300 font-mono-code">Court Order Granted</span>
                </div>
              </div>

              <!-- Actions: Copy Link, History, Open Docket -->
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <a id="cardActionLink" href="#" class="w-8 h-8 rounded-full border border-white/20 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors" title="Export Docket PDF">
                    <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                  </a>
                  <button type="button" class="w-8 h-8 rounded-full border border-white/20 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors" title="Chain of Custody Trail">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                  </button>
                </div>
                <a id="cardActionPrimary" href="{{{{ url('/cases') }}}}" class="px-5 py-2 rounded-full bg-white text-slate-900 font-black text-xs hover:bg-slate-100 shadow-md transition-all flex items-center space-x-1.5 font-mono-code">
                  <span>View Full Docket →</span>
                </a>
              </div>

            </div>

          </div>

        </div>

      </div>

    </section>

  </main>

  <!-- ======================================================== -->
  <!-- 6. FULLSCREEN TACTICAL OPERATIONS THEATRE OVERLAY        -->
  <!-- ======================================================== -->
  <div id="tacticalOpsTheatre" class="hidden fixed inset-0 z-[999999] bg-[#07080e] flex flex-col overflow-hidden tactical-grid-bg select-none">
    
    <!-- Top Tactical Navigation HUD -->
    <header class="h-16 px-6 bg-slate-950/90 border-b border-emerald-500/30 backdrop-blur-xl flex items-center justify-between z-50 flex-shrink-0 shadow-2xl">
      
      <!-- Left: Tactical Live Beacon & Title -->
      <div class="flex items-center space-x-3.5">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/40 flex items-center justify-center text-emerald-400">
          <i data-lucide="radar" class="w-5 h-5 animate-spin" style="animation-duration: 6s;"></i>
        </div>
        <div>
          <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="font-mono-code font-black text-sm text-white tracking-widest">TACTICAL RADAR THEATRE</span>
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono-code text-[10px] font-bold">DEFCON 1</span>
          </div>
          <p class="text-[11px] font-mono-code text-slate-400">GHANA NATIONAL FORENSIC COMMAND • 16 REGIONS ONLINE</p>
        </div>
      </div>

      <!-- Center: Quick Region Filter Chips -->
      <div class="hidden xl:flex items-center space-x-1 bg-slate-900/90 p-1 rounded-full border border-emerald-500/30 text-[11px] font-mono-code">
        <button type="button" onclick="selectRegionByName('Greater Accra', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Accra</button>
        <button type="button" onclick="selectRegionByName('Ashanti', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Ashanti</button>
        <button type="button" onclick="selectRegionByName('Western', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Western</button>
        <button type="button" onclick="selectRegionByName('Northern', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Northern</button>
        <button type="button" onclick="selectRegionByName('Eastern', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Eastern</button>
        <button type="button" onclick="selectRegionByName('Volta', true)" class="px-3 py-1 rounded-full hover:bg-emerald-500/20 text-slate-300 hover:text-emerald-300 transition-colors">Volta</button>
        <button type="button" onclick="resetMapView(true)" class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-bold hover:bg-emerald-500/30 transition-colors">All 16</button>
      </div>

      <!-- Right: Tactical Controls & Exit Fullscreen -->
      <div class="flex items-center space-x-3">
        <!-- 3D Toggle -->
        <button type="button" onclick="toggle3DView(true)" id="btnTacToggle3D" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 text-xs font-mono-code font-bold flex items-center space-x-1.5 transition-colors">
          <span>3D RELIEF</span>
        </button>

        <!-- Zoom In -->
        <button type="button" onclick="zoomMap(1.2, true)" class="w-8 h-8 rounded-lg bg-slate-900 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center font-bold text-xs" title="Zoom In">+</button>

        <!-- Zoom Out -->
        <button type="button" onclick="zoomMap(0.8, true)" class="w-8 h-8 rounded-lg bg-slate-900 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center font-bold text-xs" title="Zoom Out">-</button>

        <!-- Reset Center -->
        <button type="button" onclick="resetMapView(true)" class="w-8 h-8 rounded-lg bg-slate-900 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center" title="Center Map">
          <i data-lucide="crosshair" class="w-4 h-4"></i>
        </button>

        <!-- EXIT TACTICAL MODE BUTTON -->
        <button type="button" 
                onclick="closeTacticalMode()" 
                class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-mono-code font-black text-xs shadow-lg shadow-rose-600/30 flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95"
                title="Exit Tactical Mode (or press Esc)">
          <i data-lucide="minimize-2" class="w-3.5 h-3.5 stroke-[2.5]"></i>
          <span>EXIT TACTICAL (ESC)</span>
        </button>
      </div>

    </header>

    <!-- Main Tactical Fullscreen Arena -->
    <div class="relative flex-1 w-full h-full flex items-center justify-center overflow-hidden">
      
      <!-- Background Giant Tactical Radar Rings -->
      <div class="radar-ring radar-ring-tactical w-[920px] h-[920px] opacity-20"></div>
      <div class="radar-ring radar-ring-tactical w-[720px] h-[720px] radar-pulse opacity-40"></div>
      <div class="radar-ring radar-ring-tactical w-[500px] h-[500px] opacity-25"></div>
      <div class="radar-ring radar-ring-tactical w-[300px] h-[300px] opacity-35"></div>

      <!-- Prime Meridian & Equator Crosshair Ticks -->
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-20">
        <svg class="w-full h-full" viewBox="0 0 1600 1000">
          <line x1="800" y1="0" x2="800" y2="1000" stroke="#10b981" stroke-width="1" stroke-dasharray="4, 6" />
          <line x1="0" y1="500" x2="1600" y2="500" stroke="#10b981" stroke-width="1" stroke-dasharray="4, 6" />
          <text x="810" y="40" fill="#10b981" font-size="11" font-family="'JetBrains Mono', monospace">0° 00' 00" E/W PRIME MERIDIAN</text>
          <text x="40" y="490" fill="#10b981" font-size="11" font-family="'JetBrains Mono', monospace">LATITUDE 5° 33' N GHANA MARITIME BOUNDARY</text>
        </svg>
      </div>

      <!-- Left Floating HUD: Regional Outpost Telemetry -->
      <div class="absolute top-6 left-6 z-40 w-80 bg-slate-950/85 border border-emerald-500/40 rounded-2xl p-4.5 backdrop-blur-xl shadow-2xl text-white pointer-events-auto">
        <div class="flex items-center justify-between pb-2 border-b border-emerald-500/20 mb-3">
          <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
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
              <span id="tacHudDockets" class="text-base font-black text-emerald-400">7 Dockets</span>
            </div>
            <div class="bg-slate-900/80 p-2 rounded-xl border border-emerald-500/20">
              <span class="text-[10px] text-slate-400 block">MMDA Districts</span>
              <span id="tacHudStations" class="text-base font-black text-emerald-400">7 Active</span>
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
            <div id="tacHudDistrictsList" class="space-y-1 max-h-36 overflow-y-auto pr-1 text-[11px] font-mono-code">
              <!-- Dynamically populated -->
            </div>
          </div>
        </div>
      </div>

      <!-- Right Floating HUD: Live Threat Interception Stream -->
      <div class="absolute top-6 right-6 z-40 w-84 bg-slate-950/85 border border-emerald-500/40 rounded-2xl p-4.5 backdrop-blur-xl shadow-2xl text-white pointer-events-auto hidden md:block">
        <div class="flex items-center justify-between pb-2 border-b border-emerald-500/20 mb-3">
          <div class="flex items-center space-x-2">
            <i data-lucide="shield-alert" class="w-4 h-4 text-emerald-400"></i>
            <span class="text-xs font-mono-code font-bold uppercase tracking-wider text-emerald-300">LIVE DOCKET STREAM</span>
          </div>
          <span class="text-[10px] font-mono-code text-emerald-400">REALTIME</span>
        </div>

        <div id="tacCasesListContainer" class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
          <!-- Dynamically populated from liveCases -->
        </div>
      </div>

      <!-- Floating Return to Overview Pill inside Tactical Theatre -->
      <div id="tacDistrictNavHeader" class="hidden absolute top-6 left-6 z-40 flex items-center space-x-3">
        <button type="button" 
                onclick="resetMapView(true)" 
                class="px-3.5 py-2 rounded-xl bg-slate-950/95 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl transition-all group">
          <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
          <span>RETURN TO NATIONAL OVERVIEW</span>
        </button>
        <span id="tacDistrictDrillTitleBadge" class="px-3.5 py-2 rounded-xl bg-slate-950/90 text-white border border-emerald-500/40 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-xl">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
          <span id="tacDistrictDrillTitle">GREATER ACCRA MMDA DISTRICT RADAR</span>
        </span>
      </div>

      <!-- 3D Perspective Map Stage for Fullscreen Tactical -->
      <div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center">
        
        <div id="tacGhana3dPlane" class="map-3d-plane relative w-full h-full max-w-[960px] flex items-center justify-center">
          
          <svg id="tacGhanaMapSvg" 
               viewBox="0 0 800 960" 
               class="w-full h-full drop-shadow-[0_40px_60px_rgba(0,0,0,0.85)]"
               style="overflow: visible;">
            
            <!-- Base Shadow -->
            <g transform="translate(0, 20)" pointer-events="none">
              {extruded_paths_html}
            </g>

            <!-- 16 Tactical Region Polygons -->
            <g id="tacRegionsPolygonsGroup">
              {tactical_paths_html}
            </g>

            <!-- Direct Region Labels -->
            <g id="tacRegionLabelsGroup">
              {tactical_labels_html}
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
          <div id="tacTooltipSub" class="text-[11px] text-emerald-400">Click to focus tactical radar</div>
        </div>

      </div>

      <!-- Bottom Tactical Telemetry Bar -->
      <footer class="absolute bottom-4 left-6 right-6 z-40 bg-slate-950/80 border border-emerald-500/30 rounded-2xl px-6 py-2.5 backdrop-blur-xl flex flex-col md:flex-row items-center justify-between text-xs font-mono-code text-slate-400 gap-2">
        <div class="flex items-center space-x-4">
          <span class="flex items-center space-x-1.5 text-emerald-400 font-bold">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>SYSTEM READY</span>
          </span>
          <span class="hidden sm:inline">•</span>
          <span class="hidden sm:inline">COORDINATES: <span class="text-white">5.6037° N, 0.1870° W</span></span>
          <span>•</span>
          <span>SENSORS: <span class="text-white">48 OUTPOST BEACONS LOCKED</span></span>
        </div>
        <div class="flex items-center space-x-3 text-[11px] text-emerald-400/80">
          <span>DRAG TO PAN • SCROLL TO ZOOM • ESC TO EXIT</span>
        </div>
      </footer>

    </div>

  </div>

  <!-- ======================================================== -->
  <!-- CLIENT LOGIC: Chart.js, 3D Ghana Map, Realtime Poller    -->
  <!-- ======================================================== -->
  <script>
    // Embedded Region Data & Town Outposts
    const regionsData = {regions_dataset_js};
    const districtsData = {districts_dataset_js};

    // State Variables
    let liveCases = [];
    let selectedCaseId = null;
    let selectedRegionName = null;
    let activeTab = 'active';
    let filterUnit = 'All';
    let filterStatus = 'All';
    let searchQuery = '';
    let is3DActive = true;
    let isTac3DActive = true;
    let currentMainView = '3col';
    let isTacticalModeOpen = false;

    // 3D Map Pan & Zoom Physics (Standard View)
    let mapPan = {{ x: 0, y: 0 }};
    let mapZoom = 1;
    let isDragging = false;
    let startMouse = {{ x: 0, y: 0 }};
    let startPan = {{ x: 0, y: 0 }};
    let hasDragged = false;

    // Tactical Fullscreen Pan & Zoom Physics
    let tacMapPan = {{ x: 0, y: 0 }};
    let tacMapZoom = 1.1;
    let isTacDragging = false;
    let tacStartMouse = {{ x: 0, y: 0 }};
    let tacStartPan = {{ x: 0, y: 0 }};
    let tacHasDragged = false;

    // Charts instances
    let finnovaBarChart = null;
    let finnovaSparkline = null;

    // ──────────────────────────────────────────────────────────
    // 1. INITIALIZE CHARTS (Forensic Data)
    // ──────────────────────────────────────────────────────────
    function initCharts() {{
      // 1. Monthly Evidence Intake - Purple Vertical Bar Chart
      const barCtx = document.getElementById('finnovaBarChart');
      if (barCtx) {{
        finnovaBarChart = new Chart(barCtx, {{
          type: 'bar',
          data: {{
            labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{{
              data: [25, 45, 60, 85, 110, 142],
              backgroundColor: function(context) {{
                const idx = context.dataIndex;
                return idx === 5 ? '#5c56df' : 'rgba(92, 86, 223, 0.45)';
              }},
              borderRadius: 6,
              borderSkipped: false,
              barThickness: 12
            }}]
          }},
          options: {{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {{
              legend: {{ display: false }},
              tooltip: {{
                backgroundColor: '#181926',
                titleFont: {{ family: 'Inter', size: 10 }},
                bodyFont: {{ family: 'JetBrains Mono', size: 10 }},
                callbacks: {{
                  label: (ctx) => `Intake: ${{ctx.raw}} exhibits`
                }}
              }}
            }},
            scales: {{
              x: {{
                grid: {{ display: false }},
                ticks: {{ color: '#94a3b8', font: {{ size: 9, family: 'Inter' }} }}
              }},
              y: {{
                display: false,
                grid: {{ display: false }}
              }}
            }}
          }}
        }});
      }}

      // 2. Average Turnaround Time - Smooth Purple Sparkline
      const sparkCtx = document.getElementById('finnovaSparkline');
      if (sparkCtx) {{
        finnovaSparkline = new Chart(sparkCtx, {{
          type: 'line',
          data: {{
            labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
            datasets: [{{
              data: [6.5, 5.8, 5.2, 4.9, 4.6, 4.4, 4.3, 4.2],
              borderColor: '#8b5cf6',
              borderWidth: 2.2,
              tension: 0.45,
              pointRadius: function(ctx) {{
                return [1, 3, 5, 7].includes(ctx.dataIndex) ? 3.5 : 0;
              }},
              pointBackgroundColor: '#ffffff',
              pointBorderColor: '#8b5cf6',
              pointBorderWidth: 1.8,
              fill: false
            }}]
          }},
          options: {{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {{
              legend: {{ display: false }},
              tooltip: {{
                backgroundColor: '#181926',
                titleFont: {{ family: 'Inter', size: 10 }},
                bodyFont: {{ family: 'JetBrains Mono', size: 10 }},
                callbacks: {{
                  label: (ctx) => `Turnaround: ${{ctx.raw}} days`
                }}
              }}
            }},
            scales: {{
              x: {{ display: false }},
              y: {{ display: false }}
            }}
          }}
        }});
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 2. REAL-TIME DATA POLLING ENGINE (Fetches every 8s)
    // ──────────────────────────────────────────────────────────
    async function fetchRealtimeTelemetry() {{
      const spinner = document.getElementById('syncSpinner');
      if (spinner) spinner.classList.add('animate-spin');

      try {{
        const res = await fetch('/api/dashboard/realtime', {{
          headers: {{ 'Accept': 'application/json' }}
        }});
        if (!res.ok) throw new Error(`HTTP error ${{res.status}}`);
        const json = await res.json();
        if (json.success) {{
          applyRealtimeData(json);
        }}
      }} catch (err) {{
        console.warn('Realtime polling issue:', err);
      }} finally {{
        if (spinner) setTimeout(() => spinner.classList.remove('animate-spin'), 600);
      }}
    }}

    function applyRealtimeData(data) {{
      if (data.metrics) {{
        const m = data.metrics;
        const elOverdue = document.getElementById('metricOverdueAmount');
        if (elOverdue) elOverdue.textContent = (m.pending_cases ? `${{m.pending_cases}} Dockets` : '14 Dockets');
        
        const elNextMonth = document.getElementById('metricNextMonthAmount');
        if (elNextMonth) elNextMonth.textContent = (m.total_exhibits ? `${{m.total_exhibits}} Exhibits` : '142 Exhibits');

        const elTurnaround = document.getElementById('metricTurnaroundDays');
        if (elTurnaround) elTurnaround.textContent = m.turnaround_days || '4.2 days';

        const elVault = document.getElementById('metricVaultAmount');
        if (elVault) elVault.textContent = (m.total_exhibits ? `${{m.total_exhibits * 12 + 140}} Items` : '1,865 Items');

        const elCountActive = document.getElementById('tabCountActive');
        if (elCountActive) elCountActive.textContent = m.total_cases || (data.cases ? data.cases.length : 4);

        const elCountAnalysis = document.getElementById('tabCountAnalysis');
        if (elCountAnalysis) elCountAnalysis.textContent = m.pending_cases || 1;
      }}

      if (data.chart_data && finnovaBarChart && data.chart_data.monthly_bars) {{
        finnovaBarChart.data.datasets[0].data = data.chart_data.monthly_bars;
        finnovaBarChart.update('none');
      }}
      if (data.chart_data && finnovaSparkline && data.chart_data.sparkline) {{
        finnovaSparkline.data.datasets[0].data = data.chart_data.sparkline;
        finnovaSparkline.update('none');
      }}

      if (data.region_counts) {{
        const counts = data.region_counts || {{}};
        let maxCaseload = 0;
        Object.values(counts).forEach(v => {{ if (v > maxCaseload) maxCaseload = v; }});

        Object.keys(regionsData).forEach(rName => {{
          const count = counts[rName] || 0;
          const regId = rName.toLowerCase().replace(/\\s+/g, '-');
          const isHotspot = (count >= 2) || (count === maxCaseload && count > 0);
          
          if (regionsData[rName]) regionsData[rName].cases = count;

          const poly = document.getElementById('path-' + regId);
          const tacPoly = document.getElementById('tac-path-' + regId);
          const badge = document.getElementById('badge-' + regId);
          const tacBadge = document.getElementById('tac-badge-' + regId);

          if (badge) {{
            badge.textContent = isHotspot ? `${{count}} Dockets [HOTSPOT]` : `${{count}} Dockets`;
            if (isHotspot) {{
              badge.setAttribute('fill', '#f87171');
              badge.setAttribute('font-weight', '900');
            }} else {{
              badge.setAttribute('fill', '#818cf8');
              badge.setAttribute('font-weight', 'bold');
            }}
          }}

          if (tacBadge) {{
            tacBadge.textContent = isHotspot ? `${{count}} DOCKETS [HOTSPOT]` : `${{count}} DOCKETS`;
            if (isHotspot) {{
              tacBadge.setAttribute('fill', '#f87171');
            }} else {{
              tacBadge.setAttribute('fill', '#34d399');
            }}
          }}

          if (poly) {{
            if (isHotspot) {{
              poly.classList.add('region-hotspot');
            }} else {{
              poly.classList.remove('region-hotspot');
            }}
          }}

          if (tacPoly) {{
            if (isHotspot) {{
              tacPoly.classList.add('tactical-region-hotspot');
            }} else {{
              tacPoly.classList.remove('tactical-region-hotspot');
            }}
          }}
        }});

        if (selectedRegionName) {{
          renderDistrictMap(selectedRegionName);
          renderTacDistrictMap(selectedRegionName);
        }}
      }}

      if (data.cases && data.cases.length > 0) {{
        liveCases = data.cases;
        renderInvoicesList();
        renderTacticalCasesStream();
        
        if (!selectedCaseId || !liveCases.some(c => c.id == selectedCaseId)) {{
          selectInvoiceById(liveCases[0].id, false);
        }} else {{
          renderDossierCard(liveCases.find(c => c.id == selectedCaseId));
        }}
      }}
    }}

    function refreshRealtimeData() {{
      fetchRealtimeTelemetry();
    }}

    // ──────────────────────────────────────────────────────────
    // 3. RENDER FORENSIC CASES LIST (Left Panel)
    // ──────────────────────────────────────────────────────────
    function renderInvoicesList() {{
      const container = document.getElementById('invoicesListContainer');
      if (!container) return;

      let filtered = liveCases.filter(c => {{
        if (selectedRegionName && c.region && c.region.toLowerCase() !== selectedRegionName.toLowerCase()) {{
          return false;
        }}
        if (filterUnit !== 'All' && c.unit_name && !c.unit_name.toLowerCase().includes(filterUnit.toLowerCase())) {{
          return false;
        }}
        if (filterStatus !== 'All' && c.status_badge && c.status_badge.toLowerCase() !== filterStatus.toLowerCase()) {{
          return false;
        }}
        if (searchQuery) {{
          const q = searchQuery.toLowerCase();
          const caseMatch = c.case_number && c.case_number.toLowerCase().includes(q);
          const susMatch = c.suspect_name && c.suspect_name.toLowerCase().includes(q);
          const offMatch = c.submitting_officer && c.submitting_officer.toLowerCase().includes(q);
          const locMatch = c.raid_location && c.raid_location.toLowerCase().includes(q);
          if (!caseMatch && !susMatch && !offMatch && !locMatch) return false;
        }}
        return true;
      }});

      const countBadge = document.getElementById('listCasesCount');
      if (countBadge) countBadge.textContent = `${{filtered.length}} Dockets Active`;

      if (filtered.length === 0) {{
        container.innerHTML = `
          <div class="text-center py-10 px-4 rounded-xl border border-dashed border-slate-700/60 text-slate-400">
            <i data-lucide="folder-search" class="w-6 h-6 mx-auto mb-2 text-slate-500"></i>
            <p class="text-xs font-semibold">No forensic cases match filters</p>
            <button onclick="resetFilters()" class="mt-2 text-[10px] text-indigo-400 hover:underline">Reset filters</button>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      container.innerHTML = filtered.map(c => {{
        const isSelected = (c.id == selectedCaseId);
        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5';
        
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300'));

        const initials = c.suspect_name ? c.suspect_name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        return `
          <div onclick="selectInvoiceById(${{c.id}}, true)" 
               class="p-2.5 rounded-2xl cursor-pointer transition-all flex items-center justify-between gap-2.5 ${{activeClasses}} group">
            
            <div class="flex items-center space-x-2.5 min-w-0">
              <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
                ${{c.mugshot_front ? `<img src="${{c.mugshot_front}}" alt="" class="w-full h-full object-cover">` : initials}}
              </div>
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-xs font-mono-code">${{c.case_number}}</span>
                </div>
                <div class="text-[10px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate">
                  ${{c.suspect_name}} • ${{c.region || 'Greater Accra'}}
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2 flex-shrink-0 text-right">
              <span class="text-[10px] px-2 py-0.5 rounded-full font-mono-code font-bold ${{statusBadgeClass}}">
                ${{c.status_badge}}
              </span>
              <span class="font-extrabold text-xs font-mono-code text-indigo-300">${{exhibitCount}} Exh</span>
            </div>

          </div>
        `;
      }}).join('');

      lucide.createIcons();
    }}

    // Render live docket stream in tactical mode
    function renderTacticalCasesStream() {{
      const container = document.getElementById('tacCasesListContainer');
      if (!container) return;

      container.innerHTML = liveCases.map(c => `
        <div onclick="selectInvoiceById(${{c.id}}, true); focusRegionOnMap('${{c.region}}', true);" class="p-2 rounded-xl bg-slate-900/90 hover:bg-emerald-500/20 border border-emerald-500/20 cursor-pointer transition-colors flex items-center justify-between text-xs font-mono-code">
          <div class="flex items-center space-x-2 truncate">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <div class="truncate">
              <span class="font-bold text-white">${{c.case_number}}</span>
              <span class="text-[10px] text-slate-400 block">${{c.suspect_name}} • ${{c.region}}</span>
            </div>
          </div>
          <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">${{c.status_badge}}</span>
        </div>
      `).join('');
    }}

    // ──────────────────────────────────────────────────────────
    // 4. RENDER FORENSIC DOSSIER DETAILS CARD (Right Panel)
    // ──────────────────────────────────────────────────────────
    function renderDossierCard(c) {{
      if (!c) return;

      const elInv = document.getElementById('cardInvoiceNumber');
      if (elInv) elInv.textContent = c.case_number || '# NCC-2026-2572';

      const elBadge = document.getElementById('cardInvoiceStatusBadge');
      if (elBadge) elBadge.textContent = c.status_badge || 'Under Investigation';

      const elComp = document.getElementById('cardCompanyName');
      if (elComp) elComp.textContent = c.unit_name || 'Crime Scene Support';

      const elCustName = document.getElementById('cardCustomerName');
      if (elCustName) elCustName.textContent = c.suspect_name || 'Kofi Mensah';

      const elCustRole = document.getElementById('cardCustomerRole');
      if (elCustRole) {{
        const parts = [c.nationality || 'Ghanaian', c.age, c.suspect_role].filter(Boolean);
        elCustRole.textContent = parts.join(' • ') || 'Primary Target';
      }}

      const elAvatar = document.getElementById('cardCustomerAvatar');
      if (elAvatar) {{
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'KM';
        elAvatar.innerHTML = c.mugshot_front 
          ? `<img src="${{c.mugshot_front}}" class="w-full h-full object-cover" alt="">`
          : initials;
      }}

      const elOfficer = document.getElementById('cardOfficerName');
      if (elOfficer) elOfficer.textContent = c.submitting_officer || 'Insp. K. Mensah';

      const elLoc = document.getElementById('cardLocationName');
      if (elLoc) elLoc.textContent = (c.raid_location ? c.raid_location.substring(0, 26) : 'Kotoka Airport');

      const exhCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 3);
      const elSub = document.getElementById('cardSubTotal');
      if (elSub) elSub.textContent = `${{exhCount}} Items Logged`;

      const elTot = document.getElementById('cardTotal');
      if (elTot) elTot.textContent = 'EMU Vault Secured';

      const elBal = document.getElementById('cardBalanceDue');
      if (elBal) elBal.textContent = (c.status_badge === 'Approved' ? 'Court Order Signed' : 'Judicial Review Active');

      const elPrimary = document.getElementById('cardActionPrimary');
      if (elPrimary && c.id) elPrimary.href = `/cases/${{c.id}}/review`;

      const elLink = document.getElementById('cardActionLink');
      if (elLink && c.id) elLink.href = `/cases/${{c.id}}/pdf`;

      // Exhibit breakdown cards (NO finance terms — 100% Forensics)
      const grid = document.getElementById('exhibitCardsGrid');
      if (grid) {{
        const exhibits = (c.exhibits && c.exhibits.length > 0) ? c.exhibits.slice(0, 3) : [
          {{ title: 'Heroin (Brown Powder)', quantity: '2.5 kg', category: 'Narcotics' }},
          {{ title: 'Apple iPhone 15 Pro', quantity: '3 Units', category: 'Electronic Devices' }},
          {{ title: 'Concealment Cargo', quantity: '1 Parcel', category: 'Physical Evidence' }}
        ];

        let cardsHtml = exhibits.map(ex => `
          <div class="bg-white/10 hover:bg-white/15 rounded-xl p-3 border border-white/10 transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-300">
              <span class="text-xs font-mono-code font-black text-indigo-200 truncate">${{ex.quantity || '1 Unit'}}</span>
              <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-indigo-300"></i>
            </div>
            <div class="text-[10px] text-white font-bold truncate mt-2">${{ex.title}}</div>
            <div class="text-[9px] text-slate-400 font-mono-code truncate mt-0.5">${{ex.category || 'Evidence Item'}}</div>
          </div>
        `).join('');

        cardsHtml += `
          <a href="/cases/${{c.id || ''}}/edit" class="rounded-xl p-3 border border-dashed border-white/20 hover:border-indigo-400 bg-white/5 hover:bg-white/10 transition-all flex flex-col items-center justify-center text-center group">
            <div class="w-5 h-5 rounded-full bg-white/10 group-hover:bg-[#5c56df] flex items-center justify-center mb-1 text-white transition-colors">
              <i data-lucide="plus" class="w-3 h-3"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-300 group-hover:text-white">Log Exhibit</span>
          </a>
        `;

        grid.innerHTML = cardsHtml;
        lucide.createIcons();
      }}
    }}

    function selectInvoiceById(caseId, centerMap = true) {{
      selectedCaseId = caseId;
      const c = liveCases.find(item => item.id == caseId);
      if (!c) return;

      renderInvoicesList();
      renderDossierCard(c);

      if (centerMap && c.region) {{
        focusRegionOnMap(c.region, isTacticalModeOpen);
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 5. 3D GHANA MAP ENGINE & REGION FOCUSING
    // ──────────────────────────────────────────────────────────
    function selectRegionByName(regName, fromTac = false) {{
      if (selectedRegionName === regName && !fromTac) {{
        // Toggle or stay in drill-down
      }}
      selectedRegionName = regName;
      focusRegionOnMap(regName, fromTac);
      renderInvoicesList();

      const matchedCase = liveCases.find(c => c.region && c.region.toLowerCase() === regName.toLowerCase());
      if (matchedCase) {{
        selectInvoiceById(matchedCase.id, false);
      }}
    }}

    function focusRegionOnMap(regName, isTac = false) {{
      const r = regionsData[regName];
      if (!r) return;

      // 1. Isolate the clicked region: highlight it and dim all other 15 regions
      document.querySelectorAll('.region-polygon').forEach(p => {{
        if (p.getAttribute('data-name') === regName) {{
          p.style.opacity = '1';
          p.classList.add('region-active');
        }} else {{
          p.style.opacity = '0.12';
          p.classList.remove('region-active');
        }}
      }});

      document.querySelectorAll('.tactical-region-polygon').forEach(p => {{
        if (p.getAttribute('data-name') === regName) {{
          p.style.opacity = '1';
          p.classList.add('region-active');
        }} else {{
          p.style.opacity = '0.08';
          p.classList.remove('region-active');
        }}
      }});

      // 2. Hide National Country Labels to reveal District Map cleanly
      const lblGroup = document.getElementById('regionLabelsGroup');
      if (lblGroup) lblGroup.style.opacity = '0';
      const tacLblGroup = document.getElementById('tacRegionLabelsGroup');
      if (tacLblGroup) tacLblGroup.style.opacity = '0';

      // 3. Activate District Map Navigation Header (Standard Map)
      const navHeader = document.getElementById('districtNavHeader');
      const drillTitle = document.getElementById('districtDrillTitle');
      const hotspotInd = document.getElementById('districtHotspotIndicator');
      const districts = districtsData[regName] || [];
      const regCases = r.cases || liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase()).length;
      const isHotspot = (regCases >= 2);

      if (navHeader && drillTitle) {{
        navHeader.classList.remove('hidden');
        drillTitle.textContent = `${{regName.toUpperCase()}} DISTRICT MAP (${{districts.length}} MMDAs • ${{regCases}} Cases Linked)`;
        if (hotspotInd) {{
          hotspotInd.className = isHotspot 
            ? 'w-2.5 h-2.5 rounded-full bg-red-500 animate-ping shadow-[0_0_12px_#ef4444]' 
            : 'w-2 h-2 rounded-full bg-indigo-400';
        }}
      }}

      // 4. Activate Tactical District Navigation Header
      const tacNav = document.getElementById('tacDistrictNavHeader');
      const tacTitle = document.getElementById('tacDistrictDrillTitle');
      if (tacNav && tacTitle) {{
        tacNav.classList.remove('hidden');
        tacTitle.textContent = `${{regName.toUpperCase()}} MMDA DISTRICT RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
      }}

      // 5. Update Tactical Left HUD
      const tacName = document.getElementById('tacHudRegionName');
      if (tacName) tacName.textContent = regName.toUpperCase();
      const tacCap = document.getElementById('tacHudCapital');
      if (tacCap) tacCap.textContent = `Capital: ${{r.capital || 'Ghana'}}`;
      const tacDoc = document.getElementById('tacHudDockets');
      if (tacDoc) tacDoc.textContent = `${{regCases}} Dockets Linked`;
      const tacIso = document.getElementById('tacHudIso');
      if (tacIso) tacIso.textContent = r.iso || 'GH-RG';
      const tacCmd = document.getElementById('tacHudCommander');
      if (tacCmd) tacCmd.textContent = r.commander || 'Regional Forensic Command';
      const tacStations = document.getElementById('tacHudStations');
      if (tacStations) tacStations.textContent = `${{districts.length}} Districts`;

      // 6. Smooth Zoom & Pan: The clicked region becomes the centerpiece map
      const zoomLevel = 2.45;
      const targetX = (400 - r.cx) * zoomLevel;
      const targetY = (480 - r.cy) * zoomLevel;
      mapPan = {{ x: targetX, y: targetY }};
      mapZoom = zoomLevel;
      updateMapTransform(true);

      const tacZoomLevel = 2.85;
      const tacTargetX = (400 - r.cx) * tacZoomLevel;
      const tacTargetY = (480 - r.cy) * tacZoomLevel;
      tacMapPan = {{ x: tacTargetX, y: tacTargetY }};
      tacMapZoom = tacZoomLevel;
      updateTacMapTransform(true);

      // 7. Render All Official Districts and Cases Linked There
      renderDistrictMap(regName);
      renderTacDistrictMap(regName);
    }}

    // Renders all Official Districts and their linked cases on the Standard Map
    function renderDistrictMap(regName) {{
      const group = document.getElementById('districtLayersGroup');
      if (!group) return;

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      // Match cases to districts using keyword matching
      const districtWithCases = districts.map(d => {{
        const linked = regionCases.filter(c => {{
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return d.keywords.some(kw => locText.includes(kw.toLowerCase()));
        }});
        return {{ district: d, cases: linked }};
      }});

      // Fallback: If region cases have no keyword match, assign to capital/first MMDA district
      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {{
        districtWithCases[0].cases.push(...unassigned);
      }}

      // Build SVG nodes for each MMDA District
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          // CRITICAL RED HOTSPOT DISTRICT: High case presence
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <!-- Glowing Red Radar Ping -->
              <circle cx="${{d.x}}" cy="${{d.y}}" r="18" fill="none" stroke="#ef4444" stroke-width="2" class="animate-ping opacity-75" />
              <circle cx="${{d.x}}" cy="${{d.y}}" r="26" fill="rgba(239, 68, 68, 0.15)" stroke="#ef4444" stroke-width="1" stroke-dasharray="2, 3" />
              <!-- Red District Core Node -->
              <circle cx="${{d.x}}" cy="${{d.y}}" r="8.5" fill="#ef4444" stroke="#ffffff" stroke-width="2.5" filter="drop-shadow(0 0 16px rgba(239,68,68,0.9))" />
              
              <!-- MMDA District Header Tag -->
              <text x="${{d.x}}" y="${{d.y - 14}}" text-anchor="middle" font-size="11.5" font-weight="900" fill="#ffffff" stroke="#000000" stroke-width="3" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none tracking-wide">
                [${{d.code}}] ${{d.short_name.toUpperCase()}}
              </text>

              <!-- Linked Case Docket Card Attached to District -->
              <g transform="translate(${{d.x - 75}}, ${{d.y + 12}})">
                <rect width="150" height="38" rx="8" fill="#181926" stroke="#ef4444" stroke-width="2" filter="drop-shadow(0 8px 20px rgba(239,68,68,0.5))" class="group-hover:fill-[#2d1b24] transition-colors" />
                <!-- Red Alert Badge inside pill -->
                <circle cx="14" cy="19" r="5" fill="#ef4444" />
                <text x="24" y="16" font-size="9.5" font-weight="bold" fill="#fca5a5" font-family="'JetBrains Mono', monospace">
                  ${{primaryCase.case_number}}
                </text>
                <text x="24" y="29" font-size="8.5" font-weight="bold" fill="#ffffff" class="truncate">
                  ${{primaryCase.suspect_name.substring(0, 16)}} • ${{primaryCase.exhibits_count || 1}} Exh
                </text>
                <text x="142" y="16" text-anchor="end" font-size="8" font-weight="extrabold" fill="#ef4444" font-family="'JetBrains Mono', monospace">
                  HOTSPOT
                </text>
              </g>
            </g>
          `;
        }} else {{
          // Standard MMDA District Post (No current cases)
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="5" fill="#334155" stroke="#94a3b8" stroke-width="1.8" class="group-hover:fill-indigo-500 group-hover:stroke-white transition-colors" />
              <text x="${{d.x}}" y="${{d.y - 10}}" text-anchor="middle" font-size="9.5" font-weight="bold" fill="#cbd5e1" stroke="#0f172a" stroke-width="2.5" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 15}}" text-anchor="middle" font-size="8" font-weight="semibold" fill="#64748b" font-family="'JetBrains Mono', monospace" class="select-none">
                0 Dockets Active
              </text>
            </g>
          `;
        }}
      }}).join('');
    }}

    // Renders Tactical Radar MMDA Districts & Hotspots
    function renderTacDistrictMap(regName) {{
      const group = document.getElementById('tacDistrictLayersGroup');
      if (!group) return;

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      const districtWithCases = districts.map(d => {{
        const linked = regionCases.filter(c => {{
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return d.keywords.some(kw => locText.includes(kw.toLowerCase()));
        }});
        return {{ district: d, cases: linked }};
      }});

      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {{
        districtWithCases[0].cases.push(...unassigned);
      }}

      // Populate Tactical Left HUD MMDA list
      const tacDistList = document.getElementById('tacHudDistrictsList');
      if (tacDistList) {{
        tacDistList.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const count = item.cases.length;
          const isRed = count > 0;
          return `
            <div onclick="if (${{count}} > 0) selectInvoiceById(${{item.cases[0].id}}, false);" 
                 class="flex items-center justify-between p-1.5 rounded ${{isRed ? 'bg-red-950/60 border border-red-500/50 hover:bg-red-900/40 text-red-200' : 'bg-slate-900/60 hover:bg-emerald-500/20 text-slate-300'}} cursor-pointer text-[10.5px]">
              <span class="flex items-center space-x-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full ${{isRed ? 'bg-red-500 animate-ping' : 'bg-emerald-400'}}"></span>
                <span class="font-bold truncate text-white">${{d.short_name}}</span>
              </span>
              <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-400' : 'text-slate-400'}}">
                ${{isRed ? `🚨 ${{count}} DOCKET` : '0 Active'}}
              </span>
            </div>
          `;
        }}).join('');
      }}

      // Render Tactical SVG Markers
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="22" fill="none" stroke="#ef4444" stroke-width="2" class="animate-ping opacity-85" />
              <line x1="${{d.x - 14}}" y1="${{d.y}}" x2="${{d.x + 14}}" y2="${{d.y}}" stroke="#fca5a5" stroke-width="1.2" />
              <line x1="${{d.x}}" y1="${{d.y - 14}}" x2="${{d.x}}" y2="${{d.y + 14}}" stroke="#fca5a5" stroke-width="1.2" />
              <circle cx="${{d.x}}" cy="${{d.y}}" r="9" fill="#ef4444" stroke="#ffffff" stroke-width="2.5" />
              
              <text x="${{d.x}}" y="${{d.y - 16}}" text-anchor="middle" font-size="12" font-weight="900" fill="#fca5a5" stroke="#000" stroke-width="3" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                TARGET SECTOR: ${{d.short_name.toUpperCase()}}
              </text>

              <g transform="translate(${{d.x - 80}}, ${{d.y + 12}})">
                <rect width="160" height="38" rx="6" fill="#0b0f19" stroke="#ef4444" stroke-width="2" filter="drop-shadow(0 0 20px rgba(239,68,68,0.7))" />
                <circle cx="12" cy="19" r="4.5" fill="#ef4444" class="animate-ping" />
                <text x="22" y="16" font-size="10" font-weight="bold" fill="#ef4444" font-family="'JetBrains Mono', monospace">
                  INTERCEPT: ${{c.case_number}}
                </text>
                <text x="22" y="29" font-size="8.5" font-weight="bold" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="truncate">
                  ${{c.suspect_name.substring(0, 16)}} [${{c.exhibits_count || 1}} EXH]
                </text>
              </g>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="5.5" fill="#10b981" stroke="#ffffff" stroke-width="1.8" />
              <text x="${{d.x}}" y="${{d.y - 10}}" text-anchor="middle" font-size="9" font-weight="bold" fill="#a7f3d0" stroke="#000" stroke-width="2" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');
    }}

    function resetMapView(isTac = false) {{
      selectedRegionName = null;
      mapPan = {{ x: 0, y: 0 }};
      mapZoom = 1;
      tacMapPan = {{ x: 0, y: 0 }};
      tacMapZoom = 1.1;
      
      // 1. Restore opacities of all 16 regions
      document.querySelectorAll('.region-polygon').forEach(p => {{
        p.style.opacity = '1';
        p.classList.remove('region-active');
      }});
      document.querySelectorAll('.tactical-region-polygon').forEach(p => {{
        p.style.opacity = '1';
        p.classList.remove('region-active');
      }});

      // 2. Restore National Region Labels
      const lblGroup = document.getElementById('regionLabelsGroup');
      if (lblGroup) lblGroup.style.opacity = '1';
      const tacLblGroup = document.getElementById('tacRegionLabelsGroup');
      if (tacLblGroup) tacLblGroup.style.opacity = '1';

      // 3. Hide District Navigation Headers
      const navHeader = document.getElementById('districtNavHeader');
      if (navHeader) navHeader.classList.add('hidden');
      const tacNav = document.getElementById('tacDistrictNavHeader');
      if (tacNav) tacNav.classList.add('hidden');

      // 4. Clear District Markers Layers
      const group = document.getElementById('districtLayersGroup');
      if (group) group.innerHTML = '';
      const tacGroup = document.getElementById('tacDistrictLayersGroup');
      if (tacGroup) tacGroup.innerHTML = '';

      updateMapTransform(true);
      updateTacMapTransform(true);
      renderInvoicesList();
    }}

    function updateMapTransform(smooth = false) {{
      const plane = document.getElementById('ghana3dPlane');
      if (!plane) return;

      if (smooth) {{
        plane.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
      }} else {{
        plane.style.transition = 'none';
      }}

      const rotX = is3DActive ? 22 : 0;
      const rotZ = is3DActive ? -8 : 0;
      plane.style.transform = `translate3d(${{mapPan.x}}px, ${{mapPan.y}}px, 0) scale(${{mapZoom}}) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
    }}

    function updateTacMapTransform(smooth = false) {{
      const plane = document.getElementById('tacGhana3dPlane');
      if (!plane) return;

      if (smooth) {{
        plane.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
      }} else {{
        plane.style.transition = 'none';
      }}

      const rotX = isTac3DActive ? 24 : 0;
      const rotZ = isTac3DActive ? -6 : 0;
      plane.style.transform = `translate3d(${{tacMapPan.x}}px, ${{tacMapPan.y}}px, 0) scale(${{tacMapZoom}}) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
    }}

    function zoomMap(factor, isTac = false) {{
      if (isTac) {{
        tacMapZoom = Math.max(0.6, Math.min(3.5, tacMapZoom * factor));
        updateTacMapTransform(true);
      }} else {{
        mapZoom = Math.max(0.7, Math.min(2.8, mapZoom * factor));
        updateMapTransform(true);
      }}
    }}

    function resetMapView(isTac = false) {{
      selectedRegionName = null;
      mapPan = {{ x: 0, y: 0 }};
      mapZoom = 1;
      tacMapPan = {{ x: 0, y: 0 }};
      tacMapZoom = 1.1;
      
      document.querySelectorAll('.region-polygon').forEach(p => p.classList.remove('region-active'));
      document.querySelectorAll('.tactical-region-polygon').forEach(p => p.classList.remove('region-active'));

      const banner = document.getElementById('regionActiveFocusBanner');
      if (banner) banner.classList.add('hidden');

      const group = document.getElementById('townMarkersGroup');
      if (group) group.innerHTML = '';
      const tacGroup = document.getElementById('tacTownMarkersGroup');
      if (tacGroup) tacGroup.innerHTML = '';

      updateMapTransform(true);
      updateTacMapTransform(true);
      renderInvoicesList();
    }}

    function toggle3DView(isTac = false) {{
      if (isTac) {{
        isTac3DActive = !isTac3DActive;
        const btn = document.getElementById('btnTacToggle3D');
        if (btn) {{
          btn.innerHTML = isTac3DActive ? '<span>3D RELIEF</span>' : '<span>2D FLAT</span>';
        }}
        updateTacMapTransform(true);
      }} else {{
        is3DActive = !is3DActive;
        const btn = document.getElementById('btnToggle3D');
        if (btn) {{
          if (is3DActive) {{
            btn.classList.add('bg-indigo-600', 'text-white');
            btn.classList.remove('bg-slate-800', 'text-slate-400');
          }} else {{
            btn.classList.remove('bg-indigo-600', 'text-white');
            btn.classList.add('bg-slate-800', 'text-slate-400');
          }}
        }}
        updateMapTransform(true);
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 6. FULLSCREEN TACTICAL THEATRE CONTROLS
    // ──────────────────────────────────────────────────────────
    function openTacticalMode() {{
      isTacticalModeOpen = true;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {{
        theatre.classList.remove('hidden');
      }}

      // Attempt native browser fullscreen if supported
      try {{
        if (document.documentElement.requestFullscreen && !document.fullscreenElement) {{
          document.documentElement.requestFullscreen().catch(() => {{}});
        }}
      }} catch (e) {{}}

      lucide.createIcons();
      updateTacMapTransform(false);
      renderTacticalCasesStream();

      if (selectedRegionName) {{
        focusRegionOnMap(selectedRegionName, true);
      }}
    }}

    function closeTacticalMode() {{
      isTacticalModeOpen = false;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {{
        theatre.classList.add('hidden');
      }}

      try {{
        if (document.exitFullscreen && document.fullscreenElement) {{
          document.exitFullscreen().catch(() => {{}});
        }}
      }} catch (e) {{}}
    }}

    function onRegionHover(e, name) {{
      const tt = document.getElementById('regionHoverTooltip');
      const ttName = document.getElementById('tooltipRegionName');
      const ttSub = document.getElementById('tooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      ttName.textContent = name;
      ttSub.textContent = `Capital: ${{r ? r.capital : 'Ghana'}} • ${{r ? r.cases : 0}} Dockets`;

      const stage = document.querySelector('.map-3d-stage');
      if (stage) {{
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 15) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }}
    }}

    function onRegionLeave() {{
      const tt = document.getElementById('regionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }}

    function onTacRegionHover(e, name) {{
      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      ttName.textContent = name.toUpperCase();
      ttSub.textContent = `Capital: ${{r ? r.capital : 'Ghana'}} • ${{r ? r.cases : 0}} Active Cases`;

      const stage = document.getElementById('tacMapStage');
      if (stage) {{
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 20) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }}
    }}

    function onTacRegionLeave() {{
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }}

    // ──────────────────────────────────────────────────────────
    // 7. VIEW SWITCHER (Tri-Command, Dossier Only, Map Only)
    // ──────────────────────────────────────────────────────────
    function switchMainView(mode) {{
      currentMainView = mode;
      const btn3Col = document.getElementById('btnView3Col');
      const btnDossier = document.getElementById('btnViewDossier');
      const btnMap = document.getElementById('btnViewMap');

      const pList = document.getElementById('panelInvoicesList');
      const pMap = document.getElementById('panelGhanaMap');
      const pDossier = document.getElementById('panelDossierCard');

      [btn3Col, btnDossier, btnMap].forEach(b => {{
        if (b) {{
          b.classList.remove('bg-indigo-600', 'text-white', 'font-bold');
          b.classList.add('text-slate-400');
        }}
      }});

      if (mode === '3col') {{
        if (btn3Col) btn3Col.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 xl:col-span-3 flex flex-col space-y-2';
        if (pMap) pMap.className = 'col-span-12 xl:col-span-5 flex flex-col';
        if (pDossier) pDossier.className = 'col-span-12 xl:col-span-4 flex flex-col';
      }} else if (mode === 'dossier') {{
        if (btnDossier) btnDossier.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 lg:col-span-4 flex flex-col space-y-2';
        if (pMap) pMap.className = 'hidden';
        if (pDossier) pDossier.className = 'col-span-12 lg:col-span-8 flex flex-col';
      }} else if (mode === 'map') {{
        if (btnMap) btnMap.classList.add('bg-indigo-600', 'text-white', 'font-bold');
        if (pList) pList.className = 'col-span-12 lg:col-span-4 flex flex-col space-y-2';
        if (pMap) pMap.className = 'col-span-12 lg:col-span-8 flex flex-col';
        if (pDossier) pDossier.className = 'hidden';
      }}
    }}

    function toggleFilterToolbar() {{
      const tb = document.getElementById('filterToolbar');
      if (tb) tb.classList.toggle('hidden');
    }}

    function onFilterUnitChange(val) {{
      filterUnit = val;
      renderInvoicesList();
    }}

    function onFilterStatusChange(val) {{
      filterStatus = val;
      renderInvoicesList();
    }}

    function onSearchInvoiceInput(val) {{
      searchQuery = val.trim();
      renderInvoicesList();
    }}

    function resetFilters() {{
      filterUnit = 'All';
      filterStatus = 'All';
      searchQuery = '';
      selectedRegionName = null;
      document.getElementById('filterUnitSelect').value = 'All';
      document.getElementById('filterStatusSelect').value = 'All';
      document.getElementById('searchInvoiceInput').value = '';
      resetMapView();
    }}

    function setInvoiceTab(tab) {{
      activeTab = tab;
      const tAll = document.getElementById('tabBtnAll');
      const tAnalysis = document.getElementById('tabBtnAnalysis');
      const tActive = document.getElementById('tabBtnActive');
      
      [tAll, tAnalysis, tActive].forEach(t => {{
        if (t) {{
          t.classList.remove('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
          t.classList.add('text-slate-300');
        }}
      }});

      if (tab === 'active' && tActive) {{
        tActive.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }} else if (tab === 'analysis' && tAnalysis) {{
        tAnalysis.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }} else if (tab === 'all' && tAll) {{
        tAll.classList.add('bg-[#5c56df]', 'text-white', 'font-bold', 'shadow-md');
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 8. SETUP EVENT LISTENERS & PAN/ZOOM PHYSICS
    // ──────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();
      updateMapTransform(false);
      updateTacMapTransform(false);

      // Keyboard Shortcuts: ESC to close tactical, 'T' to open tactical
      window.addEventListener('keydown', (e) => {{
        if (e.key === 'Escape' && isTacticalModeOpen) {{
          closeTacticalMode();
        }}
        if ((e.key === 't' || e.key === 'T') && !['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) {{
          if (isTacticalModeOpen) closeTacticalMode();
          else openTacticalMode();
        }}
      }});

      // Standard Map Pan & Zoom physics
      const stage = document.querySelector('.map-3d-stage');
      if (stage) {{
        stage.addEventListener('mousedown', (e) => {{
          if (e.target.closest('button') || e.target.closest('#regionActiveFocusBanner')) return;
          isDragging = true;
          hasDragged = false;
          startMouse = {{ x: e.clientX, y: e.clientY }};
          startPan = {{ ...mapPan }};
          stage.classList.add('is-dragging');
        }});

        window.addEventListener('mousemove', (e) => {{
          if (!isDragging) return;
          const dx = e.clientX - startMouse.x;
          const dy = e.clientY - startMouse.y;
          if (Math.abs(dx) > 3 || Math.abs(dy) > 3) hasDragged = true;
          mapPan = {{ x: startPan.x + dx, y: startPan.y + dy }};
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

      // Tactical Map Pan & Zoom physics
      const tacStage = document.getElementById('tacMapStage');
      if (tacStage) {{
        tacStage.addEventListener('mousedown', (e) => {{
          if (e.target.closest('button')) return;
          isTacDragging = true;
          tacHasDragged = false;
          tacStartMouse = {{ x: e.clientX, y: e.clientY }};
          tacStartPan = {{ ...tacMapPan }};
          tacStage.classList.add('is-dragging');
        }});

        window.addEventListener('mousemove', (e) => {{
          if (!isTacDragging) return;
          const dx = e.clientX - tacStartMouse.x;
          const dy = e.clientY - tacStartMouse.y;
          if (Math.abs(dx) > 3 || Math.abs(dy) > 3) tacHasDragged = true;
          tacMapPan = {{ x: tacStartPan.x + dx, y: tacStartPan.y + dy }};
          updateTacMapTransform(false);
        }});

        window.addEventListener('mouseup', () => {{
          if (isTacDragging) {{
            isTacDragging = false;
            tacStage.classList.remove('is-dragging');
          }}
        }});

        tacStage.addEventListener('wheel', (e) => {{
          e.preventDefault();
          const factor = e.deltaY < 0 ? 1.08 : 0.92;
          zoomMap(factor, true);
        }}, {{ passive: false }});
      }}

      // Initial telemetry fetch & periodic polling every 8s
      fetchRealtimeTelemetry();
      setInterval(fetchRealtimeTelemetry, 8000);
    }});
  </script>

</body>
</html>
'''

# List of all dashboard files to update
targets = [
    {
        'file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'key': 'Evidence Management',
        'name': 'Evidence Management Unit',
        'subtitle': 'National Evidence Vault & Custody Directorate',
        'head': 'Chief Vault Custodian'
    },
    {
        'file': 'resources/views/Forensic_Head/head.blade.php',
        'key': 'Forensic Head',
        'name': 'Forensic Directorate',
        'subtitle': 'Forensic Directorate Supreme Command',
        'head': 'Anita Addo'
    },
    {
        'file': 'resources/views/Lab_Analysis/lab_analysis.blade.php',
        'key': 'Lab Analysis',
        'name': 'Lab Analysis Unit',
        'subtitle': 'Central Forensic Laboratory & Chemical Analysis',
        'head': 'Supt. Eric Mensah'
    },
    {
        'file': 'resources/views/Crime_Scene_Support/head/head.blade.php',
        'key': 'Crime Scene Support',
        'name': 'Crime Scene Support Unit',
        'subtitle': 'Rapid Field Deployment & Incident Command',
        'head': 'Commander Samuel Atta'
    },
    {
        'file': 'resources/views/Digital_Forensics/digital_forensics.blade.php',
        'key': 'Digital Forensics',
        'name': 'Digital & Physical Forensic Unit',
        'subtitle': 'Cyber Intelligence & Hardware Forensics',
        'head': 'Ofori Graham'
    }
]

for t in targets:
    content = generate_blade(t['key'], t['name'], t['subtitle'], t['head'])
    with open(t['file'], 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Successfully generated: {t['file']}")

print("All dashboards compiled with Fullscreen Tactical Theatre Mode!")
