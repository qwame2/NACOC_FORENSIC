import os
import re

scratch_dir = os.path.dirname(os.path.abspath(__file__))

with open(os.path.join(scratch_dir, 'ghana_regions_svg_optimized.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Compute exact padded viewBox for each region
region_bboxes = {}
for r in regions:
    coords = [float(x) for x in re.findall(r'[-+]?(?:\d*\.\d+|\d+)', r['path'])]
    xs, ys = coords[0::2], coords[1::2]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)
    w = max_x - min_x
    h = max_y - min_y
    pad_x = max(w * 0.18, 12)
    pad_y = max(h * 0.18, 12)
    vb_x = round(min_x - pad_x, 1)
    vb_y = round(min_y - pad_y, 1)
    vb_w = round(w + pad_x * 2, 1)
    vb_h = round(h + pad_y * 2, 1)
    region_bboxes[r['name']] = f"{vb_x} {vb_y} {vb_w} {vb_h}"

from generate_districts import districts_data

with open(os.path.join(scratch_dir, 'ghana_regions_geo.json'), 'r', encoding='utf-8') as f:
    regions_geo = json.load(f)
regions_geo_dataset_js = json.dumps(regions_geo)


# Maximum caseload among regions to highlight ONLY the region with the higher number of cases as RED
max_initial_cases = max(r.get('cases', 0) for r in regions) if regions else 0

# Generate common SVG paths for all 16 regions
region_paths_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    is_higher = (r.get('cases', 0) == max_initial_cases and max_initial_cases > 0)
    extra_cls = 'region-hotspot' if is_higher else ''
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
                  class="region-polygon {extra_cls} transition-all duration-300 cursor-pointer fill-slate-800/90 stroke-slate-600/80 hover:fill-indigo-600/80 hover:stroke-indigo-400 hover:drop-shadow-[0_4px_16px_rgba(92,86,223,0.35)]"
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
    is_higher = (r.get('cases', 0) == max_initial_cases and max_initial_cases > 0)
    extra_cls = 'tactical-region-hotspot region-hotspot' if is_higher else ''
    tactical_paths_markup.append(f'''
            <path id="tac-path-{reg_id}"
                  data-name="{r['name']}"
                  d="{r['path']}"
                  class="tactical-region-polygon {extra_cls} transition-all duration-300 cursor-pointer fill-slate-900/95 stroke-emerald-500/50 hover:fill-emerald-600/80 hover:stroke-emerald-300 hover:drop-shadow-[0_0_25px_rgba(16,185,129,0.5)]"
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

# Tactical direct region labels (Highlighting ONLY higher caseload)
tactical_labels_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    is_higher = (r.get('cases', 0) == max_initial_cases and max_initial_cases > 0)
    badge_fill = '#ef4444' if is_higher else 'rgba(52, 211, 153, 0.9)'
    badge_text = f"{r['cases']} DOCKETS [HIGHER CASELOAD]" if is_higher else f"{r['cases']} DOCKETS"
    name_fill = 'fill-red-300 font-black' if is_higher else 'fill-emerald-300'
    tactical_labels_markup.append(f'''
            <g id="tac-lbl-{reg_id}" class="pointer-events-none transition-opacity duration-300">
              <text x="{r['cx']}" y="{r['cy'] - 4}" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest {name_fill} drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">{r['name'].upper()}</text>
              <text id="tac-badge-{reg_id}" x="{r['cx']}" y="{r['cy'] + 10}" 
                    text-anchor="middle" 
                    fill="{badge_fill}"
                    font-weight="{'900' if is_higher else 'normal'}"
                    class="font-mono-code text-[9px] select-none">{badge_text}</text>
            </g>''')
tactical_labels_html = ''.join(tactical_labels_markup)

# Serialize datasets for JS
regions_dataset_js = json.dumps({r['name']: r for r in regions})
districts_dataset_js = json.dumps(districts_data)
region_bboxes_js = json.dumps(region_bboxes)

def generate_blade(unit_key, unit_name, unit_subtitle, default_head, unit_buttons, kpi4_title, kpi4_subtitle, kpi4_metric, kpi4_bays, kpi4_url, kpi4_btn):
    bay_blocks = []
    for i, (badge, title) in enumerate(kpi4_bays):
        if i == 1:
            bay_blocks.append(f'''
          <div class="flex-1 py-1 px-2 rounded-xl bg-[#5c56df] text-white shadow-sm flex flex-col items-center" title="{title}">
            <span class="font-mono-code text-[9px] text-indigo-200">{badge}</span>
            <span class="font-bold">{title}</span>
          </div>''')
        else:
            bay_blocks.append(f'''
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="{title}">
            <span class="font-mono-code text-[9px] text-slate-400">{badge}</span>
            <span class="font-bold text-slate-700">{title}</span>
          </div>''')
    kpi4_bays_html = ''.join(bay_blocks)

    nav_blocks = []
    for b in unit_buttons:
        if b.get('active'):
            cls = 'px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold flex items-center space-x-1.5 shadow-sm'
            icon_cls = 'w-3.5 h-3.5 text-white'
        else:
            cls = 'px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold'
            icon_cls = 'w-3.5 h-3.5 text-indigo-600'
        nav_blocks.append(f'''
        <a href="{b['url']}" class="{cls}">
          <i data-lucide="{b['icon']}" class="{icon_cls}"></i>
          <span>{b['title']}</span>
        </a>''')
    nav_buttons_html = ''.join(nav_blocks)

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
  <!-- OpenGlobus 3D Geospatial Engine CSS -->
  <link rel="stylesheet" href="{{{{ asset('vendor/openglobus/og.css') }}}}">

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

    /* 3D Map Perspective Stage */
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

    /* High-Caseload Forensic Red Hotspot Region Polygons (ONLY Higher Caseload is RED) */
    .region-polygon.region-hotspot,
    .region-polygon.region-hotspot.region-active,
    .tactical-region-polygon.region-hotspot,
    .tactical-region-polygon.tactical-region-hotspot,
    .tactical-region-polygon.region-active.region-hotspot,
    .tactical-region-polygon.region-active.tactical-region-hotspot {{
      fill: #dc2626 !important;
      stroke: #fca5a5 !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 45px rgba(220, 38, 38, 0.95)) !important;
      opacity: 1 !important;
    }}
    .region-polygon.region-hotspot:hover,
    .tactical-region-polygon.region-hotspot:hover,
    .tactical-region-polygon.tactical-region-hotspot:hover {{
      fill: #ef4444 !important;
      stroke: #ffffff !important;
      filter: drop-shadow(0 0 55px rgba(239, 68, 68, 1)) !important;
    }}

    /* Standard Active Region (when NOT a higher-caseload hotspot) */
    .region-polygon.region-active:not(.region-hotspot) {{
      fill: #5c56df !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(92, 86, 223, 0.45)) !important;
      opacity: 1 !important;
    }}

    /* Tactical Active Region (when NOT a higher-caseload hotspot -> emerald tactical active) */
    .tactical-region-polygon.region-active:not(.region-hotspot):not(.tactical-region-hotspot) {{
      fill: #10b981 !important;
      stroke: #ffffff !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 35px rgba(16, 185, 129, 0.8)) !important;
      opacity: 1 !important;
    }}

    /* Concentric Radar Rings Behind Map */
    .radar-ring {{
      border: 1px solid rgba(92, 86, 223, 0.15);
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
    }}
    .radar-ring-tactical {{
      border: 1px solid rgba(16, 185, 129, 0.2);
    }}
    /* SVG Region Transition */
    .region-polygon {{
      transition: fill 0.25s ease, stroke 0.25s ease, filter 0.25s ease, opacity 0.3s ease;
    }}

    /* Tactical HUD Grid Overlay */
    .tactical-grid-bg {{
      background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
      background-size: 24px 24px;
    }}
  </style>
</head>
<body class="bg-[#f1f3f8] text-slate-800 antialiased min-h-screen">

  <!-- ======================================================== -->
  <!-- MASTER WRAPPER CONTAINER                                -->
  <!-- ======================================================== -->
  <div class="max-w-[1720px] mx-auto p-3 sm:p-5 lg:p-6 space-y-5">

    <!-- ====================================================== -->
    <!-- 1. TOP COMMAND BAR: Agency Branding & Unit Telemetry    -->
    <!-- ====================================================== -->
    <header class="bg-white rounded-3xl p-4 sm:p-5 shadow-finnova border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      
      <!-- Left: Logo & Unit Title -->
      <div class="flex items-center space-x-3.5">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#5c56df] to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
          <i data-lucide="shield-alert" class="w-6 h-6 stroke-[2.2]"></i>
        </div>
        <div>
          <div class="flex items-center space-x-2">
            <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">NACOC FORENSIC</h1>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold border border-indigo-200/60 font-mono-code">
              {unit_name}
            </span>
            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold">
              <span>LIVE TELEMETRY</span>
            </span>
          </div>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            {unit_subtitle} • Real-time Ghana Evidence & Case Radar
          </p>
        </div>
      </div>

      <!-- Right: Officer Profile, Quick Sync & Tactical Launcher -->
      <div class="flex items-center space-x-3 self-end md:self-auto">
        
        <!-- Tactical Theatre Mode Trigger Button -->
        <button type="button" 
                onclick="openTacticalMode()" 
                class="px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-lg shadow-emerald-500/25 flex items-center space-x-2 transition-all hover:scale-105 active:scale-95 group"
                title="Open Fullscreen 3D Tactical Theatre (Press 'T')">
          <i data-lucide="maximize-2" class="w-4 h-4 stroke-[2.5]"></i>
          <span class="tracking-wider font-mono-code">TACTICAL THEATRE</span>
          <span class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded font-mono-code ml-1">KEY: T</span>
        </button>

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
              <img src="{{{{ asset($authUser->avatar) }}}}" alt="" class="w-full h-full object-cover">
            @else
              {{{{ strtoupper(substr($headName, 0, 2)) }}}}
            @endif
          </div>
          <div class="hidden sm:block text-left">
            <div class="text-xs font-bold text-slate-900 leading-tight">{{{{ $headName }}}}</div>
            <div class="text-[11px] text-slate-500 font-medium">{{{{ $officerRole }}}}</div>
          </div>
        </div>

      </div>

    </header>

    <!-- ====================================================== -->
    <!-- 2. QUICK NAVIGATION APP BAR (UNIT-SPECIFIC ACTIONS)     -->
    <!-- ====================================================== -->
    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
      <div class="flex items-center space-x-1 sm:space-x-2 text-slate-600">
        {nav_buttons_html}
      </div>

      <div class="flex items-center space-x-2 pl-4 text-slate-400 shrink-0">
        <span class="text-[11px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">{unit_name}</span>
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
              {{{{ $pendingCasesCount }}}} Dockets
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
              {{{{ $totalExhibits }}}} Exhibits
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

      <!-- CARD 4: {kpi4_title} -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">{kpi4_title}</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center text-[10px]">
                <i data-lucide="shield-check" class="w-3 h-3"></i>
              </span>
              <a href="{kpi4_url}" class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]" title="Inspect">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              {kpi4_metric}
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">{kpi4_subtitle}</span>
          </div>
        </div>
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          {kpi4_bays_html}
          <a href="{kpi4_url}" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-[10px] font-bold shadow-xs transition-colors flex items-center">
            {kpi4_btn}
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
            <span id="tabCountAnalysis" class="bg-white/10 text-slate-300 px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{{{ $pendingCasesCount }}}}</span>
          </button>
          <button type="button" onclick="setInvoiceTab('active')" id="tabBtnActive" class="bg-[#5c56df] text-white px-4 py-1.5 rounded-full font-bold shadow-md shadow-indigo-500/25 flex items-center space-x-1.5">
            <span>Active Cases</span>
            <span id="tabCountActive" class="bg-white/20 text-white px-1.5 py-0.2 rounded-full text-[10px] font-mono-code">{{{{ $totalCases }}}}</span>
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

          <!-- OpenGlobus 3D Whole Globe / Vector Switcher -->
          <button type="button" 
                  id="btnToggleEngineMode" 
                  onclick="toggleEngineMode()" 
                  class="px-3.5 py-1 rounded-full bg-indigo-600 text-white border border-indigo-400/80 text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95" 
                  title="Switch between OpenGlobus 3D WebGL Earth and Vector Tactical Radar">
            <span id="txtEngineModeLabel" class="inline-flex items-center space-x-1.5"><i data-lucide="globe" class="w-3.5 h-3.5"></i><span>3D WHOLE GLOBE</span></span>
          </button>

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
            <span class="text-[10px] font-mono-code text-slate-300" id="listCasesCount">{{{{ $totalCases }}}} Active</span>
          </div>

          <!-- Real-Time Case Rows Container -->
          <div id="invoicesListContainer" class="space-y-1.5 max-h-[580px] overflow-y-auto pr-1">
            <div class="text-center py-8 text-xs text-slate-500">Loading live forensic cases from database...</div>
          </div>
        </div>

        <!-- PANEL 2: 3D CLICKABLE GHANA MAP (Centerpiece) -->
        <div id="panelGhanaMap" class="col-span-12 xl:col-span-5 flex flex-col">
          
          <div id="mapStageWrapper" class="relative w-full h-[580px] flex items-center justify-center bg-slate-950/70 rounded-2xl border border-slate-800/80 overflow-hidden select-none">
            
            <!-- OPENGLOBUS 3D HARDWARE-ACCELERATED GEOSPATIAL EARTH -->
            <div id="openGlobusContainer" class="absolute inset-0 w-full h-full z-15 bg-slate-950 rounded-2xl overflow-hidden">
              <div id="globusCanvasWrapper" class="w-full h-full"></div>
              <!-- Interactive Tactical Case Info Popup Card -->
              <div id="globusCasePopup" class="hidden absolute bottom-14 right-4 z-40 w-80 bg-slate-950/95 border border-indigo-500/70 rounded-2xl shadow-2xl p-4 backdrop-blur-xl animate-in fade-in duration-200">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800">
                  <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span>
                    <span id="globusPopupCaseNum" class="font-mono-code font-bold text-xs text-white"></span>
                  </div>
                  <span id="globusPopupBadge" class="px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-red-950/80 text-red-300 border border-red-500/40"></span>
                  <button type="button" onclick="document.getElementById('globusCasePopup').classList.add('hidden')" class="text-slate-400 hover:text-white p-1">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                  </button>
                </div>
                <div class="space-y-1.5 text-[11px] font-mono-code">
                  <div class="flex justify-between"><span class="text-slate-400">Suspect:</span><span id="globusPopupSuspect" class="font-bold text-white"></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">Incident Scene:</span><span id="globusPopupLoc" class="font-bold text-cyan-300"></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">District:</span><span id="globusPopupDistrict" class="font-bold text-indigo-300"></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">Region:</span><span id="globusPopupRegion" class="font-bold text-emerald-300"></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">Status:</span><span id="globusPopupStatus" class="font-bold text-slate-300"></span></div>
                </div>
                <button type="button" id="globusPopupBtnDossier" class="mt-3 w-full py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-mono-code font-bold text-xs shadow-md transition-all flex items-center justify-center space-x-1.5">
                  <i data-lucide="folder-search" class="w-3.5 h-3.5"></i><span>Inspect Case Dossier</span>
                </button>
              </div>
              
              <!-- 3D Earth Telemetry & Coordinate HUD -->
              <div class="absolute top-3 left-3 z-30 pointer-events-none flex flex-col space-y-1">
                <div class="flex items-center space-x-2 bg-slate-950/90 border border-emerald-500/40 px-3 py-1.5 rounded-xl backdrop-blur-md text-[11px] font-mono-code text-emerald-300 shadow-2xl">
                  <span class="font-extrabold text-white">OPENGLOBUS 3D</span>
                  <span>•</span>
                  <span id="globusCoordinatesText">WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]</span>
                </div>
                <div id="globusTargetBadge" class="hidden items-center space-x-1.5 bg-pink-950/90 border border-pink-500/60 px-3 py-1 rounded-xl backdrop-blur-md text-[10.5px] font-mono-code text-pink-200 shadow-2xl">
                  <span class="text-white font-mono-code font-bold inline-flex items-center space-x-1"><i data-lucide="crosshair" class="w-3.5 h-3.5 text-slate-300"></i><span>TARGET LOCK:</span></span>
                  <span id="globusTargetCaseText" class="font-bold text-white">NCC-2026-2572</span>
                </div>
              </div>

              <!-- 3D Flight Controls & Basemap Switcher -->
              <div class="absolute bottom-3 left-3 right-3 z-30 flex items-center justify-between pointer-events-none">
                <div class="pointer-events-auto flex items-center space-x-1.5 font-mono-code text-[10px]">
                  <button type="button" onclick="globusFlyToWholeGlobe()" class="px-2.5 py-1 rounded-lg bg-indigo-950/90 hover:bg-indigo-900 text-indigo-200 border border-indigo-500/50 backdrop-blur-md transition-all shadow-md font-bold flex items-center space-x-1">
                    <i data-lucide="globe" class="w-3.5 h-3.5"></i><span>Whole Globe</span>
                  </button>
                  <button type="button" onclick="globusFlyToGhana()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-emerald-300 border border-emerald-500/40 backdrop-blur-md transition-all shadow-md font-bold">
                    <i data-lucide="crosshair" class="w-3.5 h-3.5"></i><span>Focus Ghana</span>
                  </button>
                  <button type="button" onclick="globusToggleBasemap()" id="btnGlobusLayer" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    <i data-lucide="layers" class="w-3.5 h-3.5"></i><span>Satellite / Streets</span>
                  </button>
                  <button type="button" onclick="globusTiltCamera()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    <i data-lucide="compass" class="w-3.5 h-3.5"></i><span>3D Tilt</span>
                  </button>
                </div>
                <div class="text-[9.5px] font-mono-code text-slate-400 bg-slate-950/80 px-2.5 py-1 rounded-lg border border-slate-800">
                  DRAG: ROTATE • RIGHT-CLICK: PITCH • SCROLL: ZOOM
                </div>
              </div>
            </div>
            
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
        <div id="panelDossierCard" class="col-span-12 xl:col-span-4 flex flex-col">
          
          <div id="dossierCardWrapper" class="bg-gradient-to-br from-[#2f2e59]/90 to-[#222143]/90 rounded-2xl p-5 border border-white/10 shadow-2xl relative overflow-hidden backdrop-blur-xl transition-all h-[580px] flex flex-col justify-between">
            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

            <!-- Top Row: Docket # & Status -->
            <div>
              <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-3 mb-4">
                <div>
                  <span class="text-[10px] font-bold text-indigo-300 font-mono-code uppercase tracking-wider block">Docket Details</span>
                  <div class="flex items-center space-x-2 mt-0.5">
                    <h3 id="cardInvoiceNumber" class="text-xl font-extrabold text-white tracking-tight font-mono-code">
                      # NCC-2026-2572
                    </h3>
                    <span id="cardInvoiceStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                      Under Investigation
                    </span>
                  </div>
                </div>

                <!-- Submitting Forensic Unit Pill -->
                <div class="text-right">
                  <span class="text-[9px] font-mono-code text-slate-400 uppercase tracking-wider block">Submitting Unit</span>
                  <div class="flex items-center space-x-1.5 justify-end mt-0.5">
                    <span id="cardCompanyName" class="text-xs font-bold text-white">{unit_name}</span>
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-slate-300"></i>
                  </div>
                </div>
              </div>

              <!-- Suspect Identity Profile Container (Dynamic Single or Multi-Suspect) -->
              <div id="cardSuspectsProfileContainer" class="mb-4">
                <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
                  <div id="cardCustomerAvatar" class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
                    KM
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">Primary Target</span>
                      <span class="text-[10px] font-mono-code text-slate-400" id="cardSuspectId">GHA-7829104-1</span>
                    </div>
                    <div id="cardCustomerName" class="text-sm font-extrabold text-white truncate mt-0.5">
                      Kofi Mensah
                    </div>
                    <div id="cardCustomerRole" class="text-[10px] text-slate-300 truncate">
                      Ghanaian • 34 yrs • Narcotics Concealment
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operational Case Metadata -->
              <div class="grid grid-cols-2 gap-2 text-xs font-mono-code bg-black/20 p-2.5 rounded-xl border border-white/5 mb-4">
                <div>
                  <span class="text-[9px] text-slate-400 block">Lead Officer:</span>
                  <span id="cardOfficerName" class="font-bold text-slate-200">Insp. K. Mensah</span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block">Seizure:</span>
                  <span id="cardLocationName" class="font-bold text-slate-200 truncate">Kotoka Cargo Wing</span>
                </div>
              </div>

              <!-- Seized Exhibits Grid -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-[10px] font-mono-code text-slate-300 uppercase tracking-wider font-bold">Recovered Exhibits & Evidence</span>
                  <span id="cardSubTotal" class="text-[10px] font-mono-code text-indigo-300 font-bold">3 Items Logged</span>
                </div>

                <div id="exhibitCardsGrid" class="grid grid-cols-2 gap-2">
                  <!-- Dynamically populated via renderDossierCard() -->
                </div>
              </div>
            </div>

            <!-- Bottom Row: Judicial Chain of Custody & Actions -->
            <div class="pt-3 border-t border-white/10 mt-auto">
              <div class="flex items-center justify-between text-xs font-mono-code mb-3">
                <div>
                  <span class="text-[9px] text-slate-400 block">Total Exhibits</span>
                  <span id="cardTotal" class="font-extrabold text-white text-sm">3 Items Logged</span>
                </div>
                <div class="text-center">
                  <span class="text-[9px] text-slate-400 block">Chain of Custody</span>
                  <span id="cardCustodyStatus" class="font-bold text-slate-300">EMU Vault Secured</span>
                </div>
                <div class="text-right">
                  <span class="text-[9px] text-slate-400 block">Judicial Mandate</span>
                  <span id="cardBalanceDue" class="font-bold text-indigo-300">Judicial Review Active</span>
                </div>
              </div>

              <!-- Actions Buttons -->
              <div class="flex items-center space-x-2">
                <a id="cardActionLink" href="{{{{ url('/cases') }}}}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Export Docket PDF">
                  <i data-lucide="file-down" class="w-4 h-4"></i>
                </a>
                <a id="cardActionVault" href="{{{{ url('/history') }}}}?unit={unit_key.replace(' ', '+')}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Case Audit & History">
                  <i data-lucide="history" class="w-4 h-4"></i>
                </a>
                <a id="cardActionPrimary" href="{{{{ url('/cases') }}}}" class="flex-1 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-slate-100 font-extrabold text-xs text-center shadow-lg transition-all flex items-center justify-center space-x-1.5">
                  <span>View Full Docket</span>
                  <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
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
              <span class="font-mono-code font-black text-sm tracking-wider text-white">NACOC TACTICAL OPERATIONS THEATRE • {unit_name.upper()}</span>
            </div>
            <span class="text-[11px] font-mono-code text-slate-400">16 Regions Radar • MMDA Assemblies • Target Interception Network</span>
          </div>
        </div>
      </div>

      <!-- Dedicated Tactical Flight & Engine Controls in Navbar -->
      <div class="flex items-center space-x-1.5 md:space-x-2 font-mono-code text-xs">
        <button type="button" onclick="tacGlobusFlyToWholeGlobe()" class="px-3 py-1.5 rounded-xl bg-indigo-950/90 hover:bg-indigo-900 text-indigo-200 border border-indigo-500/60 backdrop-blur-md shadow-md font-bold flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95" title="View Whole 3D Earth Globe from Space">
          <i data-lucide="globe" class="w-3.5 h-3.5"></i>
          <span>Whole Globe</span>
        </button>
        <button type="button" onclick="tacGlobusFlyToGhana()" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-emerald-300 border border-emerald-500/50 backdrop-blur-md shadow-md font-bold flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95" title="Focus Camera on Ghana Sector">
          <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
          <span>Focus Ghana Sector</span>
        </button>
        <button type="button" onclick="tacGlobusToggleBasemap()" id="btnTacGlobusLayer" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md shadow-md flex items-center space-x-1.5 transition-all" title="Toggle Satellite and Street Basemap">
          <i data-lucide="layers" class="w-3.5 h-3.5"></i>
          <span>Satellite / Streets</span>
        </button>
        <button type="button" onclick="tacGlobusTiltCamera()" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md shadow-md flex items-center space-x-1.5 transition-all" title="Toggle 3D Horizon Tilt">
          <i data-lucide="compass" class="w-3.5 h-3.5"></i>
          <span>3D Tilt</span>
        </button>
        <button type="button" onclick="toggleTacEngineMode()" id="btnToggleTacEngineMode" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white border border-indigo-400 backdrop-blur-md shadow-md font-bold flex items-center space-x-1.5 transition-all hover:scale-105 active:scale-95" title="Switch between 3D Whole Globe and Vector 2D Radar">
          <span id="txtTacEngineModeLabel" class="inline-flex items-center space-x-1.5">
            <i data-lucide="globe" class="w-3.5 h-3.5"></i>
            <span>3D WHOLE GLOBE</span>
          </span>
        </button>
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

      <!-- OPENGLOBUS 3D HARDWARE-ACCELERATED WHOLE GLOBE IN TACTICAL THEATRE -->
      <div id="tacOpenGlobusContainer" class="absolute inset-0 w-full h-full z-15 bg-[#020617] overflow-hidden">
        <div id="tacGlobusCanvasWrapper" class="relative w-full h-full"></div>
        <!-- Interactive Tactical Case Info Popup Card in Tactical Theatre -->
        <div id="tacGlobusCasePopup" class="hidden absolute bottom-16 right-8 z-40 w-84 bg-slate-950/95 border border-indigo-500/70 rounded-2xl shadow-2xl p-4 backdrop-blur-xl animate-in fade-in duration-200">
          <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800">
            <div class="flex items-center space-x-2">
              <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span>
              <span id="tacGlobusPopupCaseNum" class="font-mono-code font-bold text-xs text-white"></span>
            </div>
            <span id="tacGlobusPopupBadge" class="px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-red-950/80 text-red-300 border border-red-500/40"></span>
            <button type="button" onclick="document.getElementById('tacGlobusCasePopup').classList.add('hidden')" class="text-slate-400 hover:text-white p-1">
              <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
          </div>
          <div class="space-y-1.5 text-[11px] font-mono-code">
            <div class="flex justify-between"><span class="text-slate-400">Suspect:</span><span id="tacGlobusPopupSuspect" class="font-bold text-white"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Incident Scene:</span><span id="tacGlobusPopupLoc" class="font-bold text-cyan-300"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">District:</span><span id="tacGlobusPopupDistrict" class="font-bold text-indigo-300"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Region:</span><span id="tacGlobusPopupRegion" class="font-bold text-emerald-300"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Status:</span><span id="tacGlobusPopupStatus" class="font-bold text-slate-300"></span></div>
          </div>
          <button type="button" id="tacGlobusPopupBtnDossier" class="mt-3 w-full py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-mono-code font-bold text-xs shadow-md transition-all flex items-center justify-center space-x-1.5">
            <i data-lucide="folder-search" class="w-3.5 h-3.5"></i><span>Inspect Case Dossier</span>
          </button>
        </div>
        
        <!-- 3D Earth Telemetry & Coordinate HUD in Tactical Theatre (Top Center-Left) -->
        <div class="absolute top-20 left-6 z-40 pointer-events-none flex flex-col space-y-1.5">
          <div class="flex items-center space-x-2 bg-slate-950/90 border border-emerald-500/50 px-3.5 py-1.5 rounded-xl backdrop-blur-md text-xs font-mono-code text-emerald-300 shadow-2xl">
            <span class="font-extrabold text-white">OPENGLOBUS 3D TACTICAL THEATRE</span>
            <span>•</span>
            <span id="tacGlobusCoordinatesText">WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]</span>
          </div>
          <div id="tacGlobusTargetBadge" class="hidden items-center space-x-1.5 bg-pink-950/90 border border-pink-500/70 px-3.5 py-1.5 rounded-xl backdrop-blur-md text-xs font-mono-code text-pink-200 shadow-2xl">
            <span class="text-white font-mono-code font-bold inline-flex items-center space-x-1"><i data-lucide="crosshair" class="w-3.5 h-3.5 text-slate-300"></i><span>TACTICAL TARGET LOCK:</span></span>
            <span id="tacGlobusTargetCaseText" class="font-bold text-white">NCC-2026-2572</span>
          </div>
        </div>


      </div>

      <!-- 3D Perspective Map Stage for Fullscreen Tactical -->
      <div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center hidden">
        
        <div id="tacGhana3dPlane" class="map-3d-plane relative w-full h-full max-w-[960px] flex items-center justify-center">
          
          <svg id="tacGhanaMapSvg" 
               viewBox="0 0 800 960" 
               class="w-full h-full drop-shadow-[0_40px_60px_rgba(0,0,0,0.85)] transition-all duration-500"
               style="overflow: visible;">
            
            <!-- Base Shadow -->
            <g id="tacExtrusionShadowPass" transform="translate(0, 20)" pointer-events="none">
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

  <!-- ======================================================== -->
  <!-- OpenGlobus ES Module Loader -->
  <script type="module">
    try {{
      const og = await import('{{{{ asset("vendor/openglobus/og.es.js") }}}}');
      window.og = og;
      window.isOpenGlobusAvailable = true;
      console.log('[OpenGlobus] 3D Geospatial Engine Loaded Successfully');
      if (typeof window.onGlobusModuleReady === 'function') {{
        window.onGlobusModuleReady();
      }}
    }} catch (e) {{
      console.warn('[OpenGlobus] Local module failed, loading CDN fallback:', e);
      try {{
        const ogCdn = await import('https://cdn.jsdelivr.net/npm/@openglobus/og/lib/og.es.js');
        window.og = ogCdn;
        window.isOpenGlobusAvailable = true;
        if (typeof window.onGlobusModuleReady === 'function') {{
          window.onGlobusModuleReady();
        }}
      }} catch (err2) {{
        console.error('[OpenGlobus] Could not initialize OpenGlobus:', err2);
      }}
    }}
  </script>

  @php
    $dashboardLiveCases = $cases->map(function($c) {{
        $rLoc = $c->raid_location ?? '';
        $reg = !empty($c->region) ? $c->region : '';
        if (empty($reg)) {{
            if (stripos($rLoc, 'kumasi') !== false) $reg = 'Ashanti';
            else if (stripos($rLoc, 'sekondi') !== false || stripos($rLoc, 'takoradi') !== false) $reg = 'Western';
            else if (stripos($rLoc, 'tamale') !== false) $reg = 'Northern';
            else $reg = 'Greater Accra';
        }}
        return [
            'id' => $c->id,
            'case_number' => $c->case_number,
            'title' => $c->title ?? ($c->case_number . ' - ' . ($c->suspect_name ?? 'Forensic Case')),
            'suspect_name' => $c->suspect_name ?? 'Unknown Suspect',
            'unit_name' => $c->unit_name ?? 'Forensic Directorate',
            'region' => $reg,
            'raid_location' => !empty($rLoc) ? $rLoc : ($reg . ' Sector'),
            'status' => $c->status ?? 'Active',
            'status_badge' => $c->status_badge ?? 'In Progress',
            'exhibits_count' => $c->exhibits_count ?? 1,
            'is_multiple' => (bool)($c->is_multiple ?? false)
        ];
    }});
  @endphp

  <!-- 7. REAL-TIME CLIENT JAVASCRIPT ENGINE                    -->
  <!-- ======================================================== -->
  <script>
    // Embedded Region Data, Districts, & ViewBoxes
    const regionsData = {regions_dataset_js};
    const districtsData = {districts_dataset_js};
    const regionViewBoxes = {region_bboxes_js};

    // State Variables: Pre-populated directly from server-side $cases so pins render immediately!
    let liveCases = {{!! json_encode($dashboardLiveCases) !!}};
    let selectedCaseId = liveCases.length > 0 ? liveCases[0].case_number : null;

    // ──────────────────────────────────────────────────────────
    // 0. OPENGLOBUS 3D GEOSPATIAL ENGINE CONTROLLER
    // ──────────────────────────────────────────────────────────
    let isGlobusModeActive = true; // Show whole 3D Earth globe by default!
    let globusInstance = null;
    let globusSatLayer = null;
    let globusOsmLayer = null;
    let globusRegionsLayer = null;
    let globusMarkersLayer = null;
    let globusCurrentTilt = 0;

    const regionsGeoData = {regions_geo_dataset_js};

    const GHANA_REGION_GEO = {{
      'Greater Accra': {{ lon: -0.1969, lat: 5.6037, alt: 75000, name: 'Accra Metro' }},
      'Ashanti': {{ lon: -1.6244, lat: 6.6885, alt: 85000, name: 'Kumasi Metro' }},
      'Western': {{ lon: -1.7554, lat: 4.9340, alt: 85000, name: 'Sekondi-Takoradi' }},
      'Eastern': {{ lon: -0.2588, lat: 6.0945, alt: 85000, name: 'Koforidua' }},
      'Central': {{ lon: -1.2466, lat: 5.1054, alt: 80000, name: 'Cape Coast' }},
      'Northern': {{ lon: -0.8393, lat: 9.4008, alt: 110000, name: 'Tamale Metro' }},
      'Volta': {{ lon: 0.4713, lat: 6.6101, alt: 90000, name: 'Ho Municipal' }},
      'Upper East': {{ lon: -0.8514, lat: 10.7856, alt: 90000, name: 'Bolgatanga' }},
      'Upper West': {{ lon: -2.5019, lat: 10.0601, alt: 90000, name: 'Wa Municipal' }},
      'Bono': {{ lon: -2.3268, lat: 7.3399, alt: 85000, name: 'Sunyani' }},
      'Bono East': {{ lon: -1.9400, lat: 7.5833, alt: 90000, name: 'Techiman' }},
      'Ahafo': {{ lon: -2.5167, lat: 6.8000, alt: 80000, name: 'Goaso' }},
      'Western North': {{ lon: -2.4833, lat: 6.2000, alt: 85000, name: 'Sefwi Wiawso' }},
      'Oti': {{ lon: 0.1794, lat: 8.0689, alt: 90000, name: 'Dambai' }},
      'Savannah': {{ lon: -1.8258, lat: 9.0833, alt: 110000, name: 'Damongo' }},
      'North East': {{ lon: -0.3700, lat: 10.5300, alt: 90000, name: 'Nalerigu' }}
    }};

    const MMDA_DISTRICT_GEO = {{
      // Ashanti Region Districts & Towns
      'kumasi': {{ lon: -1.6244, lat: 6.6885, town: 'Kumasi', district: 'Kumasi Metro (KMA)', region: 'Ashanti' }},
      'kumasi metro': {{ lon: -1.6244, lat: 6.6885, town: 'Kumasi', district: 'Kumasi Metro (KMA)', region: 'Ashanti' }},
      'adum': {{ lon: -1.6250, lat: 6.6900, town: 'Adum', district: 'Kumasi Metro', region: 'Ashanti' }},
      'bantama': {{ lon: -1.6350, lat: 6.7050, town: 'Bantama', district: 'Kumasi Metro', region: 'Ashanti' }},
      'asafo': {{ lon: -1.6180, lat: 6.6850, town: 'Asafo', district: 'Kumasi Metro', region: 'Ashanti' }},
      'oforikrom': {{ lon: -1.5900, lat: 6.6800, town: 'Oforikrom', district: 'Oforikrom Municipal', region: 'Ashanti' }},
      'suame': {{ lon: -1.6380, lat: 6.7200, town: 'Suame', district: 'Suame Municipal', region: 'Ashanti' }},
      'obuasi': {{ lon: -1.6667, lat: 6.2000, town: 'Obuasi', district: 'Obuasi Municipal', region: 'Ashanti' }},
      'ejisu': {{ lon: -1.4800, lat: 6.7200, town: 'Ejisu', district: 'Ejisu Municipal', region: 'Ashanti' }},
      'mampong': {{ lon: -1.4000, lat: 7.0600, town: 'Mampong', district: 'Mampong Municipal', region: 'Ashanti' }},
      'konongo': {{ lon: -1.2167, lat: 6.6167, town: 'Konongo', district: 'Asante Akim Central', region: 'Ashanti' }},
      'bekwai': {{ lon: -1.5833, lat: 6.4500, town: 'Bekwai', district: 'Bekwai Municipal', region: 'Ashanti' }},
      'abuakwa': {{ lon: -1.7200, lat: 6.7000, town: 'Abuakwa', district: 'Atwima Nwabiagya', region: 'Ashanti' }},

      // Greater Accra Region Districts & Towns
      'legon': {{ lon: -0.1869, lat: 5.6508, town: 'Legon', district: 'Ayawaso West (Legon)', region: 'Greater Accra' }},
      'ayawaso west': {{ lon: -0.1869, lat: 5.6508, town: 'Ayawaso West', district: 'Ayawaso West', region: 'Greater Accra' }},
      'east legon': {{ lon: -0.1550, lat: 5.6420, town: 'East Legon', district: 'Ayawaso West', region: 'Greater Accra' }},
      'dzorwulu': {{ lon: -0.1980, lat: 5.6150, town: 'Dzorwulu', district: 'Ayawaso West', region: 'Greater Accra' }},
      'accra': {{ lon: -0.2057, lat: 5.5560, town: 'Accra', district: 'Accra Metro (AMA)', region: 'Greater Accra' }},
      'accra metro': {{ lon: -0.2057, lat: 5.5560, town: 'Accra Metro', district: 'Accra Metro (AMA)', region: 'Greater Accra' }},
      'central': {{ lon: -0.2057, lat: 5.5560, town: 'Accra Central', district: 'Accra Metro (AMA)', region: 'Greater Accra' }},
      'osu': {{ lon: -0.1830, lat: 5.5560, town: 'Osu', district: 'Korle Klottey', region: 'Greater Accra' }},
      'tema': {{ lon: -0.0166, lat: 5.6698, town: 'Tema', district: 'Tema Metro', region: 'Greater Accra' }},
      'madina': {{ lon: -0.1667, lat: 5.6833, town: 'Madina', district: 'La-Nkwantanang-Madina', region: 'Greater Accra' }},
      'adenta': {{ lon: -0.1500, lat: 5.7167, town: 'Adenta', district: 'Adentan Municipal', region: 'Greater Accra' }},
      'ashaiman': {{ lon: -0.0333, lat: 5.7000, town: 'Ashaiman', district: 'Ashaiman Municipal', region: 'Greater Accra' }},
      'ga east': {{ lon: -0.2333, lat: 5.6833, town: 'Dome/Abokobi', district: 'Ga East', region: 'Greater Accra' }},

      // Western Region
      'sekondi': {{ lon: -1.7150, lat: 4.9340, town: 'Sekondi', district: 'Sekondi-Takoradi Metro', region: 'Western' }},
      'takoradi': {{ lon: -1.7554, lat: 4.8870, town: 'Takoradi', district: 'Sekondi-Takoradi Metro', region: 'Western' }},
      'tarkwa': {{ lon: -1.9967, lat: 5.3044, town: 'Tarkwa', district: 'Tarkwa-Nsuaem', region: 'Western' }},
      'elubo': {{ lon: -2.7780, lat: 5.2890, town: 'Elubo', district: 'Jomoro (Elubo Border)', region: 'Western' }},

      // Central Region
      'cape coast': {{ lon: -1.2466, lat: 5.1054, town: 'Cape Coast', district: 'Cape Coast Metro', region: 'Central' }},
      'kasoa': {{ lon: -0.4200, lat: 5.5340, town: 'Kasoa', district: 'Awutu Senya East', region: 'Central' }},
      'winneba': {{ lon: -0.6274, lat: 5.3511, town: 'Winneba', district: 'Effutu Municipal', region: 'Central' }},

      // Eastern Region
      'koforidua': {{ lon: -0.2588, lat: 6.0945, town: 'Koforidua', district: 'New Juaben', region: 'Eastern' }},
      'nkawkaw': {{ lon: -0.7667, lat: 6.5500, town: 'Nkawkaw', district: 'Kwahu West', region: 'Eastern' }},

      // Volta Region
      'ho': {{ lon: 0.4713, lat: 6.6101, town: 'Ho', district: 'Ho Municipal', region: 'Volta' }},
      'aflao': {{ lon: 1.1895, lat: 6.1215, town: 'Aflao', district: 'Ketu South (Aflao Border)', region: 'Volta' }},

      // Northern Region
      'tamale': {{ lon: -0.8393, lat: 9.4008, town: 'Tamale', district: 'Tamale Metro', region: 'Northern' }},
      'yendi': {{ lon: -0.0099, lat: 9.4427, town: 'Yendi', district: 'Yendi Municipal', region: 'Northern' }},

      // Bono & Bono East
      'sunyani': {{ lon: -2.3268, lat: 7.3399, town: 'Sunyani', district: 'Sunyani Municipal', region: 'Bono' }},
      'techiman': {{ lon: -1.9400, lat: 7.5833, town: 'Techiman', district: 'Techiman Municipal', region: 'Bono East' }},

      // Upper East & West
      'bolgatanga': {{ lon: -0.8514, lat: 10.7856, town: 'Bolgatanga', district: 'Bolgatanga Municipal', region: 'Upper East' }},
      'wa': {{ lon: -2.5019, lat: 10.0601, town: 'Wa', district: 'Wa Municipal', region: 'Upper West' }}
    }};

    // Standard Google Maps-Style Location Pin Generator (Compact 34x46px)
    function makeLocationPin(color, stroke, innerColor) {{
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="46" viewBox="0 0 34 46">
          <defs>
            <filter id="pGlow_${{color.replace('#','')}}" x="-30%" y="-30%" width="160%" height="160%">
              <feDropShadow dx="0" dy="2.5" stdDeviation="2.5" flood-color="#000" flood-opacity="0.75"/>
            </filter>
          </defs>
          <path d="M17 1 C7.6 1 0 8.6 0 18 C0 30.5 15.6 44.5 16.3 45.1 C16.7 45.5 17.3 45.5 17.7 45.1 C18.4 44.5 34 30.5 34 18 C34 8.6 26.4 1 17 1 Z" 
                fill="${{color}}" stroke="${{stroke}}" stroke-width="2" filter="url(#pGlow_${{color.replace('#','')}})"/>
          <circle cx="17" cy="17" r="7.5" fill="#ffffff"/>
          <circle cx="17" cy="17" r="4.5" fill="${{innerColor || color}}"/>
        </svg>
      `.trim();

      return {{
        src: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
        width: 34,
        height: 46,
        offset: [0, -23]
      }};
    }}

    // Accurate Geo & District Resolver from Place of Incident / Raid Scene
    function resolveCaseLocation(c) {{
      const locText = ((c.raid_location || '') + ' ' + (c.region || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
      
      // 1. Direct check against MMDA towns/localities dictionary
      for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
        if (locText.includes(k)) {{
          return {{
            lon: v.lon,
            lat: v.lat,
            town: v.town || c.raid_location || 'Incident Location',
            district: v.district || (c.region ? c.region + ' District' : 'MMDA District'),
            region: v.region || c.region || 'Greater Accra'
          }};
        }}
      }}

      // 2. Check against districtsData
      if (typeof districtsData === 'object') {{
        for (const [regName, dList] of Object.entries(districtsData)) {{
          for (const d of (dList || [])) {{
            const terms = [
              (d.short_name || '').toLowerCase(),
              (d.capital || '').toLowerCase(),
              (d.name || '').toLowerCase(),
              ...(d.keywords || []).map(k => String(k).toLowerCase())
            ].filter(t => t.length > 2);

            if (terms.some(t => locText.includes(t))) {{
              let lon = -0.1969, lat = 5.6037;
              if (typeof d.x === 'number' && typeof d.y === 'number') {{
                lon = -3.25 + (d.x / 740.0) * 4.45;
                lat = 11.20 - (d.y / 1000.0) * 6.50;
              }}
              return {{
                lon: lon,
                lat: lat,
                town: d.capital || d.short_name || c.raid_location,
                district: d.name || d.short_name,
                region: regName
              }};
            }}
          }}
        }}
      }}

      // 3. Fallback to Region Centroid if only region is available
      if (c.region && GHANA_REGION_GEO[c.region]) {{
        const rGeo = GHANA_REGION_GEO[c.region];
        return {{
          lon: rGeo.lon,
          lat: rGeo.lat,
          town: c.raid_location || rGeo.name,
          district: c.region + ' Sector',
          region: c.region
        }};
      }}

      // 4. Default to Greater Accra
      return {{
        lon: -0.2057,
        lat: 5.5560,
        town: c.raid_location || 'Accra Metro',
        district: 'Accra Metro (AMA)',
        region: 'Greater Accra'
      }};
    }}

    // Dynamic Regional Caseload Calculation from Database Live Cases
    function getRegionalCaseloads() {{
      const counts = {{}};
      Object.keys(GHANA_REGION_GEO).forEach(r => counts[r] = 0);

      (liveCases || []).forEach(c => {{
        const loc = resolveCaseLocation(c);
        if (counts[loc.region] !== undefined) {{
          counts[loc.region]++;
        }} else if (c.region && counts[c.region] !== undefined) {{
          counts[c.region]++;
        }} else {{
          counts['Greater Accra']++;
        }}
      }});

      const maxCount = Math.max(...Object.values(counts), 1);
      return {{ counts, maxCount }};
    }}

    // Show Interactive Case Info Popup Card
    function showGlobusCasePopup(c, isTactical = false) {{
      const popupId = isTactical ? 'tacGlobusCasePopup' : 'globusCasePopup';
      const prefix = isTactical ? 'tacGlobusPopup' : 'globusPopup';
      const popup = document.getElementById(popupId);
      if (!popup) return;

      const loc = resolveCaseLocation(c);
      const isTarget = (selectedCaseId && (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)));

      const elNum = document.getElementById(prefix + 'CaseNum');
      const elBadge = document.getElementById(prefix + 'Badge');
      const elSuspect = document.getElementById(prefix + 'Suspect');
      const elLoc = document.getElementById(prefix + 'Loc');
      const elDist = document.getElementById(prefix + 'District');
      const elReg = document.getElementById(prefix + 'Region');
      const elStatus = document.getElementById(prefix + 'Status');
      const btnDossier = document.getElementById(prefix + 'BtnDossier');

      if (elNum) elNum.textContent = c.case_number;
      if (elBadge) {{
        elBadge.textContent = isTarget ? 'TARGET CASE' : 'RECORDED CASE';
        elBadge.className = isTarget 
          ? 'px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-red-950/80 text-red-300 border border-red-500/50'
          : 'px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-cyan-950/80 text-cyan-300 border border-cyan-500/50';
      }}
      if (elSuspect) elSuspect.textContent = c.suspect_name || 'Unknown';
      if (elLoc) elLoc.textContent = c.raid_location || loc.town;
      if (elDist) elDist.textContent = loc.district;
      if (elReg) elReg.textContent = loc.region;
      if (elStatus) elStatus.textContent = (c.status_badge || 'Active') + ' (' + (c.exhibits_count || 1) + ' Exh)';

      if (btnDossier) {{
        btnDossier.onclick = () => {{
          selectInvoiceById(c.case_number, false);
          popup.classList.add('hidden');
        }};
      }}

      popup.classList.remove('hidden');
    }}

    window.onGlobusModuleReady = function() {{
      if (isGlobusModeActive) {{
        initOpenGlobusEngine();
      }}
    }};

    function initOpenGlobusEngine() {{
      if (globusInstance || !window.og) return;
      const container = document.getElementById('globusCanvasWrapper');
      if (!container) return;

      try {{
        globusSatLayer = new window.og.layer.XYZ("Esri Satellite", {{
          isBaseLayer: true,
          url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{{z}}/{{y}}/{{x}}",
          visibility: true
        }});

        globusOsmLayer = new window.og.layer.XYZ("OpenStreetMap", {{
          isBaseLayer: false,
          url: "https://tile.openstreetmap.org/{{z}}/{{x}}/{{y}}.png",
          visibility: false
        }});

        globusRegionsLayer = new window.og.layer.Vector("Regional Caseload Boundaries", {{
          pickingEnabled: false
        }});

        globusMarkersLayer = new window.og.layer.Vector("Case Location Indicators", {{
          pickingEnabled: true
        }});

        if (globusMarkersLayer.events && typeof globusMarkersLayer.events.on === 'function') {{
          globusMarkersLayer.events.on("entityclick", (e) => {{
            const ent = e.pickingObject || e;
            if (ent && ent._caseData) {{
              selectInvoiceById(ent._caseData.case_number, false);
              showGlobusCasePopup(ent._caseData, false);
              globusFlyToCase(ent._caseData);
            }}
          }});
        }}

        globusInstance = new window.og.Globe({{
          target: "globusCanvasWrapper",
          name: "Ghana Forensic 3D Earth",
          layers: [globusSatLayer, globusOsmLayer, globusRegionsLayer, globusMarkersLayer]
        }});
        window.globusInstance = globusInstance;
        window.globusRegionsLayer = globusRegionsLayer;
        window.globusMarkersLayer = globusMarkersLayer;

        if (globusInstance.planet && globusInstance.planet.light) {{
          globusInstance.planet.light.ambient = [0.95, 0.95, 0.95];
          globusInstance.planet.light.diffuse = [1.1, 1.1, 1.1];
        }}

        refreshGlobusEntities();

        setTimeout(() => {{
          if (selectedCaseId) {{
            const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
            if (activeCase) {{
              showGlobusCasePopup(activeCase, false);
              globusFlyToCase(activeCase);
            }} else {{
              globusFlyToGhana();
            }}
          }} else {{
            globusFlyToGhana();
          }}
        }}, 350);

      }} catch (err) {{
        console.error('[OpenGlobus] Initialization error:', err);
      }}
    }}

    function refreshGlobusEntities(targetCase = null) {{
      if (!globusMarkersLayer || !globusRegionsLayer || !window.og) return;
      try {{
        // 1. Clear existing entities
        if (typeof globusMarkersLayer.clear === 'function') globusMarkersLayer.clear();
        else {{
          const exM = globusMarkersLayer.getEntities();
          if (exM && exM.length > 0) globusMarkersLayer.removeEntities(exM);
        }}

        if (typeof globusRegionsLayer.clear === 'function') globusRegionsLayer.clear();
        else {{
          const exR = globusRegionsLayer.getEntities();
          if (exR && exR.length > 0) globusRegionsLayer.removeEntities(exR);
        }}

        // 2. Compute dynamic regional caseload from live database cases
        const {{ counts: regionCounts, maxCount: maxCaseload }} = getRegionalCaseloads();

        // Dynamically tint 2D SVG map polygons by real caseload
        Object.entries(regionCounts).forEach(([regName, count]) => {{
          const regId = regName.toLowerCase().replace(/\s+/g, '-');
          const poly = document.getElementById('path-' + regId);
          if (poly) {{
            if (count > 0) {{
              const intensity = count / maxCaseload;
              poly.classList.add('region-hotspot');
              poly.style.stroke = '#ef4444';
              poly.style.strokeWidth = intensity >= 0.8 ? '2.5px' : '1.8px';
            }} else {{
              poly.classList.remove('region-hotspot');
              poly.style.stroke = '';
              poly.style.strokeWidth = '';
            }}
          }}
        }});

        // 3. Highlight regions in RED according to case volume on OpenGlobus 3D
        if (typeof regionsGeoData === 'object') {{
          Object.entries(regionsGeoData).forEach(([regName, rData]) => {{
            const cases = regionCounts[regName] || 0;
            const intensity = cases / maxCaseload;

            let strokeColor = "rgba(100, 116, 139, 0.35)";
            let thickness = 1.4;

            if (cases > 0) {{
              const alpha = Math.min(0.45 + (intensity * 0.55), 1.0);
              strokeColor = `rgba(239, 68, 68, ${{alpha.toFixed(2)}})`;
              thickness = 2.5 + (intensity * 2.0); // 3.2px to 4.5px thickness
            }}

            const boundaryEnt = new window.og.Entity({{
              name: `BOUNDARY_${{regName}}`,
              polyline: {{
                pathLonLat: [rData.boundary],
                thickness: thickness,
                color: strokeColor,
                isClosed: true
              }}
            }});
            globusRegionsLayer.add(boundaryEnt);

            // Regional centroid badge for regions with cases
            if (cases > 0) {{
              const isHighest = cases === maxCaseload;
              const regBadgeEnt = new window.og.Entity({{
                name: `REG_BADGE_${{regName}}`,
                lonlat: new window.og.LonLat(rData.centroid[0], rData.centroid[1], 1200),
                label: {{
                  text: `${{regName.toUpperCase()}}: ${{cases}} ${{cases > 1 ? 'CASES' : 'CASE'}}${{isHighest ? ' [HIGH CASELOAD]' : ''}}`,
                  size: isHighest ? 13 : 11,
                  color: isHighest ? "#fca5a5" : "#fed7aa",
                  outlineColor: "#0b0f19",
                  outline: 4,
                  offset: [0, 0]
                }}
              }});
              globusRegionsLayer.add(regBadgeEnt);
            }}
          }});
        }}

        // 4. Render Google Maps-Style Location Indicators for EVERY recorded case
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedLocations = new Map();

        (liveCases || []).forEach(c => {{
          const loc = resolveCaseLocation(c);
          const isSelected = (c.case_number === activeCaseNum);

          // Coordinate jitter for multiple cases sharing exact same town coordinates
          const geoKey = `${{loc.lon.toFixed(3)}}_${{loc.lat.toFixed(3)}}`;
          const count = renderedLocations.get(geoKey) || 0;
          renderedLocations.set(geoKey, count + 1);

          const lonJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.012 : -0.012) * Math.ceil(count / 2);
          const latJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.008 : -0.008) * Math.ceil(count / 2);

          const finalLon = loc.lon + lonJitter;
          const finalLat = loc.lat + latJitter;

          // Colors: Crimson Red for active target case, Vivid Cyan for other live incident cases
          const pinColor = isSelected ? "#ef4444" : "#06b6d4";
          const pinStroke = "#ffffff";
          const pinInner = isSelected ? "#b91c1c" : "#0891b2";

          const pinBillboard = makeLocationPin(pinColor, pinStroke, pinInner);

          const caseEnt = new window.og.Entity({{
            name: `CASE_${{c.case_number}}`,
            lonlat: new window.og.LonLat(finalLon, finalLat, isSelected ? 500 : 350),
            billboard: pinBillboard,
            label: {{
              text: `${{c.case_number}}: ${{c.suspect_name}} [${{loc.town}}]`,
              size: isSelected ? 13 : 11,
              color: isSelected ? "#fecaca" : "#ffffff",
              outlineColor: "#0b0f19",
              outline: 4,
              offset: [0, -32]
            }}
          }});
          caseEnt._caseData = c;

          if (caseEnt.events && typeof caseEnt.events.on === 'function') {{
            caseEnt.events.on("click", () => {{
              selectInvoiceById(c.case_number, false);
              showGlobusCasePopup(c, false);
              globusFlyToCase(c);
            }});
          }}

          globusMarkersLayer.add(caseEnt);
        }});

      }} catch (err) {{
        console.warn('[OpenGlobus] Error refreshing markers:', err);
      }}
    }}

    function globusFlyToWholeGlobe() {{
      if (!globusInstance || !globusInstance.planet || !window.og) return;
      globusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000),
        new window.og.LonLat(0.0, 10.0, 0),
        null,
        () => {{
          const coordTxt = document.getElementById('globusCoordinatesText');
          if (coordTxt) coordTxt.textContent = 'WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]';
        }}
      );
      const coordTxt = document.getElementById('globusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'ORBITING WHOLE 3D PLANET EARTH...';
      const badge = document.getElementById('globusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}

    function globusFlyToGhana() {{
      if (!globusInstance || !globusInstance.planet || !window.og) return;
      globusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(-1.0232, 7.9465, 880000),
        new window.og.LonLat(-1.0232, 7.9465, 0)
      );
      const coordTxt = document.getElementById('globusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'GHANA NATIONAL SECTOR [ORBIT 880 KM]';
      const badge = document.getElementById('globusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}

    function globusFlyToCase(c) {{
      if (!c || !globusInstance || !globusInstance.planet || !window.og) return;
      const loc = resolveCaseLocation(c);
      const targetAlt = 65000;

      globusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(loc.lon, loc.lat, targetAlt),
        new window.og.LonLat(loc.lon, loc.lat, 0)
      );

      const coordTxt = document.getElementById('globusCoordinatesText');
      if (coordTxt) {{
        coordTxt.textContent = `${{loc.region.toUpperCase()}} • ${{loc.town.toUpperCase()}} • LAT ${{loc.lat.toFixed(4)}}°N, LON ${{Math.abs(loc.lon).toFixed(4)}}°W`;
      }}

      const badge = document.getElementById('globusTargetBadge');
      const targetTxt = document.getElementById('globusTargetCaseText');
      if (badge && targetTxt) {{
        badge.classList.remove('hidden');
        targetTxt.textContent = `${{c.case_number}} • ${{c.suspect_name}} [${{loc.town}}]`;
      }}

      refreshGlobusEntities(c);
    }}

    function globusToggleBasemap() {{
      if (!globusSatLayer || !globusOsmLayer) return;
      const btn = document.getElementById('btnGlobusLayer');
      if (globusSatLayer.getVisibility()) {{
        globusSatLayer.setVisibility(false);
        globusOsmLayer.setVisibility(true);
        if (btn) btn.textContent = 'Street / Satellite';
      }} else {{
        globusSatLayer.setVisibility(true);
        globusOsmLayer.setVisibility(false);
        if (btn) btn.textContent = 'Satellite / Street';
      }}
    }}

    function globusTiltCamera() {{
      if (!globusInstance || !globusInstance.planet || !window.og) return;
      globusCurrentTilt = (globusCurrentTilt === 0) ? 42 : 0;
      const cam = globusInstance.planet.camera;
      const pos = cam.getLonLat();
      if (globusCurrentTilt > 0) {{
        cam.flyLonLat(
          new window.og.LonLat(pos.lon, pos.lat - 0.25, Math.max(pos.height, 35000)),
          new window.og.LonLat(pos.lon, pos.lat, 0)
        );
      }} else {{
        cam.flyLonLat(
          new window.og.LonLat(pos.lon, pos.lat, pos.height),
          new window.og.LonLat(pos.lon, pos.lat, 0)
        );
      }}
    }}

    function toggleEngineMode() {{
      isGlobusModeActive = !isGlobusModeActive;
      const globusCont = document.getElementById('openGlobusContainer');
      const stageCont = document.querySelector('.map-3d-stage');
      const btnTxt = document.getElementById('txtEngineModeLabel');
      const btn = document.getElementById('btnToggleEngineMode');

      if (isGlobusModeActive) {{
        if (globusCont) globusCont.classList.remove('hidden');
        if (stageCont) stageCont.classList.add('hidden');
        if (btnTxt) btnTxt.textContent = '3D WHOLE GLOBE';
        if (btn) {{
          btn.classList.add('bg-indigo-600', 'text-white');
          btn.classList.remove('bg-slate-900/90', 'text-indigo-300');
        }}

        if (!globusInstance) {{
          initOpenGlobusEngine();
        }} else {{
          refreshGlobusEntities();
          if (selectedCaseId) {{
            const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
            if (activeCase) globusFlyToCase(activeCase);
            else globusFlyToGhana();
          }} else {{
            globusFlyToGhana();
          }}
        }}
      }} else {{
        if (globusCont) globusCont.classList.add('hidden');
        if (stageCont) stageCont.classList.remove('hidden');
        if (btnTxt) btnTxt.textContent = 'VECTOR 2D RADAR';
        if (btn) {{
          btn.classList.remove('bg-indigo-600', 'text-white');
          btn.classList.add('bg-slate-900/90', 'text-indigo-300');
        }}
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 0.1 TACTICAL THEATRE OPENGLOBUS 3D ENGINE CONTROLLER
    // ──────────────────────────────────────────────────────────
    let isTacGlobusModeActive = true;
    let tacGlobusInstance = null;
    let tacGlobusSatLayer = null;
    let tacGlobusOsmLayer = null;
    let tacGlobusRegionsLayer = null;
    let tacGlobusMarkersLayer = null;
    let tacGlobusCurrentTilt = 0;

    function initTacOpenGlobusEngine() {{
      if (tacGlobusInstance || !window.og) return;
      const container = document.getElementById('tacGlobusCanvasWrapper');
      if (!container) return;

      try {{
        tacGlobusSatLayer = new window.og.layer.XYZ("Tactical Esri Satellite", {{
          isBaseLayer: true,
          url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{{z}}/{{y}}/{{x}}",
          visibility: true
        }});

        tacGlobusOsmLayer = new window.og.layer.XYZ("Tactical OpenStreetMap", {{
          isBaseLayer: false,
          url: "https://tile.openstreetmap.org/{{z}}/{{x}}/{{y}}.png",
          visibility: false
        }});

        tacGlobusRegionsLayer = new window.og.layer.Vector("Tactical Regional Boundaries", {{
          pickingEnabled: false
        }});

        tacGlobusMarkersLayer = new window.og.layer.Vector("Tactical Case Indicators", {{
          pickingEnabled: true
        }});

        if (tacGlobusMarkersLayer.events && typeof tacGlobusMarkersLayer.events.on === 'function') {{
          tacGlobusMarkersLayer.events.on("entityclick", (e) => {{
            const ent = e.pickingObject || e;
            if (ent && ent._caseData) {{
              selectInvoiceById(ent._caseData.case_number, false);
              showGlobusCasePopup(ent._caseData, true);
              if (typeof tacGlobusFlyToCase === 'function') tacGlobusFlyToCase(ent._caseData);
            }}
          }});
        }}

        tacGlobusInstance = new window.og.Globe({{
          target: "tacGlobusCanvasWrapper",
          name: "Tactical Theatre 3D Earth",
          layers: [tacGlobusSatLayer, tacGlobusOsmLayer, tacGlobusRegionsLayer, tacGlobusMarkersLayer]
        }});
        window.tacGlobusInstance = tacGlobusInstance;
        window.tacGlobusRegionsLayer = tacGlobusRegionsLayer;
        window.tacGlobusMarkersLayer = tacGlobusMarkersLayer;

        if (tacGlobusInstance.planet && tacGlobusInstance.planet.light) {{
          tacGlobusInstance.planet.light.ambient = [0.95, 0.95, 0.95];
          tacGlobusInstance.planet.light.diffuse = [1.1, 1.1, 1.1];
        }}

        refreshTacGlobusEntities();

        setTimeout(() => {{
          if (selectedCaseId) {{
            const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
            if (activeCase) {{
              showGlobusCasePopup(activeCase, true);
              tacGlobusFlyToCase(activeCase);
            }} else {{
              tacGlobusFlyToGhana();
            }}
          }} else {{
            tacGlobusFlyToGhana();
          }}
        }}, 250);

      }} catch (err) {{
        console.error('[OpenGlobus Tactical] Initialization error:', err);
      }}
    }}

    function refreshTacGlobusEntities(targetCase = null) {{
      if (!tacGlobusMarkersLayer || !tacGlobusRegionsLayer || !window.og) return;
      try {{
        if (typeof tacGlobusMarkersLayer.clear === 'function') tacGlobusMarkersLayer.clear();
        else {{
          const exM = tacGlobusMarkersLayer.getEntities();
          if (exM && exM.length > 0) tacGlobusMarkersLayer.removeEntities(exM);
        }}

        if (typeof tacGlobusRegionsLayer.clear === 'function') tacGlobusRegionsLayer.clear();
        else {{
          const exR = tacGlobusRegionsLayer.getEntities();
          if (exR && exR.length > 0) tacGlobusRegionsLayer.removeEntities(exR);
        }}

        const {{ counts: regionCounts, maxCount: maxCaseload }} = getRegionalCaseloads();

        // 1. Regional boundaries with caseload intensity
        if (typeof regionsGeoData === 'object') {{
          Object.entries(regionsGeoData).forEach(([regName, rData]) => {{
            const cases = regionCounts[regName] || 0;
            const intensity = cases / maxCaseload;

            let strokeColor = "rgba(100, 116, 139, 0.35)";
            let thickness = 1.4;

            if (cases > 0) {{
              const alpha = Math.min(0.45 + (intensity * 0.55), 1.0);
              strokeColor = `rgba(239, 68, 68, ${{alpha.toFixed(2)}})`;
              thickness = 2.5 + (intensity * 2.0);
            }}

            const boundaryEnt = new window.og.Entity({{
              name: `TAC_BOUNDARY_${{regName}}`,
              polyline: {{
                pathLonLat: [rData.boundary],
                thickness: thickness,
                color: strokeColor,
                isClosed: true
              }}
            }});
            tacGlobusRegionsLayer.add(boundaryEnt);

            if (cases > 0) {{
              const isHighest = cases === maxCaseload;
              const regBadgeEnt = new window.og.Entity({{
                name: `TAC_REG_BADGE_${{regName}}`,
                lonlat: new window.og.LonLat(rData.centroid[0], rData.centroid[1], 1200),
                label: {{
                  text: `${{regName.toUpperCase()}}: ${{cases}} ${{cases > 1 ? 'CASES' : 'CASE'}}${{isHighest ? ' [HIGH CASELOAD]' : ''}}`,
                  size: isHighest ? 13 : 11,
                  color: isHighest ? "#fca5a5" : "#fed7aa",
                  outlineColor: "#0b0f19",
                  outline: 4,
                  offset: [0, 0]
                }}
              }});
              tacGlobusRegionsLayer.add(regBadgeEnt);
            }}
          }});
        }}

        // 2. Case Indicators
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedLocations = new Map();

        (liveCases || []).forEach(c => {{
          const loc = resolveCaseLocation(c);
          const isSelected = (c.case_number === activeCaseNum);

          const geoKey = `${{loc.lon.toFixed(3)}}_${{loc.lat.toFixed(3)}}`;
          const count = renderedLocations.get(geoKey) || 0;
          renderedLocations.set(geoKey, count + 1);

          const lonJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.012 : -0.012) * Math.ceil(count / 2);
          const latJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.008 : -0.008) * Math.ceil(count / 2);

          const finalLon = loc.lon + lonJitter;
          const finalLat = loc.lat + latJitter;

          const pinColor = isSelected ? "#ef4444" : "#06b6d4";
          const pinStroke = "#ffffff";
          const pinInner = isSelected ? "#b91c1c" : "#0891b2";

          const pinBillboard = makeLocationPin(pinColor, pinStroke, pinInner);

          const caseEnt = new window.og.Entity({{
            name: `TAC_CASE_${{c.case_number}}`,
            lonlat: new window.og.LonLat(finalLon, finalLat, isSelected ? 500 : 350),
            billboard: pinBillboard,
            label: {{
              text: `${{c.case_number}}: ${{c.suspect_name}} [${{loc.town}}]`,
              size: isSelected ? 13 : 11,
              color: isSelected ? "#fecaca" : "#ffffff",
              outlineColor: "#0b0f19",
              outline: 4,
              offset: [0, -32]
            }}
          }});
          caseEnt._caseData = c;

          if (caseEnt.events && typeof caseEnt.events.on === 'function') {{
            caseEnt.events.on("click", () => {{
              selectInvoiceById(c.case_number, false);
              showGlobusCasePopup(c, true);
              if (typeof tacGlobusFlyToCase === 'function') tacGlobusFlyToCase(c);
            }});
          }}

          tacGlobusMarkersLayer.add(caseEnt);
        }});

      }} catch (err) {{
        console.warn('[OpenGlobus Tactical] Error refreshing markers:', err);
      }}
    }}

    function toggleTacEngineMode() {{
      isTacGlobusModeActive = !isTacGlobusModeActive;
      const globusCont = document.getElementById('tacOpenGlobusContainer');
      const stageCont = document.getElementById('tacMapStage');
      const btnTxt = document.getElementById('txtTacEngineModeLabel');
      const btn = document.getElementById('btnToggleTacEngineMode');

      if (isTacGlobusModeActive) {{
        if (globusCont) globusCont.classList.remove('hidden');
        if (stageCont) stageCont.classList.add('hidden');
        if (btnTxt) btnTxt.textContent = '3D WHOLE GLOBE';
        if (btn) {{
          btn.classList.add('bg-indigo-600', 'text-white');
          btn.classList.remove('bg-slate-900/90', 'text-indigo-300');
        }}

        if (!tacGlobusInstance) {{
          initTacOpenGlobusEngine();
        }} else {{
          refreshTacGlobusEntities();
          if (selectedCaseId) {{
            const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
            if (activeCase) tacGlobusFlyToCase(activeCase);
            else tacGlobusFlyToGhana();
          }} else {{
            tacGlobusFlyToGhana();
          }}
        }}
      }} else {{
        if (globusCont) globusCont.classList.add('hidden');
        if (stageCont) stageCont.classList.remove('hidden');
        if (btnTxt) btnTxt.textContent = 'VECTOR 2D RADAR';
        if (btn) {{
          btn.classList.remove('bg-indigo-600', 'text-white');
          btn.classList.add('bg-slate-900/90', 'text-indigo-300');
        }}
      }}
    }}

    function tacGlobusFlyToWholeGlobe() {{
      if (!tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      tacGlobusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000),
        new window.og.LonLat(0.0, 10.0, 0),
        null,
        () => {{
          const coordTxt = document.getElementById('tacGlobusCoordinatesText');
          if (coordTxt) coordTxt.textContent = 'WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]';
        }}
      );
      const coordTxt = document.getElementById('tacGlobusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'ORBITING WHOLE 3D PLANET EARTH...';
      const badge = document.getElementById('tacGlobusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}

    function tacGlobusFlyToGhana() {{
      if (!tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      tacGlobusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(-1.0232, 7.9465, 880000),
        new window.og.LonLat(-1.0232, 7.9465, 0)
      );
      const coordTxt = document.getElementById('tacGlobusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'GHANA NATIONAL SECTOR [ORBIT 880 KM]';
      const badge = document.getElementById('tacGlobusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}

    function tacGlobusFlyToCase(c) {{
      if (!c || !tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      const loc = resolveCaseLocation(c);
      const targetAlt = 65000;

      tacGlobusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(loc.lon, loc.lat, targetAlt),
        new window.og.LonLat(loc.lon, loc.lat, 0)
      );

      const coordTxt = document.getElementById('tacGlobusCoordinatesText');
      if (coordTxt) {{
        coordTxt.textContent = `${{loc.region.toUpperCase()}} • ${{loc.town.toUpperCase()}} • LAT ${{loc.lat.toFixed(4)}}°N, LON ${{Math.abs(loc.lon).toFixed(4)}}°W`;
      }}

      const badge = document.getElementById('tacGlobusTargetBadge');
      const targetTxt = document.getElementById('tacGlobusTargetCaseText');
      if (badge && targetTxt) {{
        badge.classList.remove('hidden');
        targetTxt.textContent = `${{c.case_number}} • ${{c.suspect_name}} [${{loc.town}}]`;
      }}

      refreshTacGlobusEntities(c);
    }}

    function tacGlobusTiltCamera() {{
      if (!tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      tacGlobusCurrentTilt = (tacGlobusCurrentTilt === 0) ? 42 : 0;
      const cam = tacGlobusInstance.planet.camera;
      const pos = cam.getLonLat();
      if (tacGlobusCurrentTilt > 0) {{
        cam.flyLonLat(
          new window.og.LonLat(pos.lon, pos.lat - 0.25, Math.max(pos.height, 35000)),
          new window.og.LonLat(pos.lon, pos.lat, 0)
        );
      }} else {{
        cam.flyLonLat(
          new window.og.LonLat(pos.lon, pos.lat, pos.height),
          new window.og.LonLat(pos.lon, pos.lat, 0)
        );
      }}
    }}
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
                callbacks: {{
                  label: (ctx) => `${{ctx.raw}} days`
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
        const res = await fetch("{{{{ url('/api/dashboard/realtime') }}}}", {{
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
          // ONLY the region with the HIGHER number of cases is RED!
          const isHotspot = (count === maxCaseload && maxCaseload > 0);
          
          if (regionsData[rName]) regionsData[rName].cases = count;

          const poly = document.getElementById('path-' + regId);
          const tacPoly = document.getElementById('tac-path-' + regId);
          const badge = document.getElementById('badge-' + regId);
          const tacBadge = document.getElementById('tac-badge-' + regId);

          if (badge) {{
            badge.textContent = isHotspot ? `${{count}} Dockets [HIGHER CASELOAD]` : `${{count}} Dockets`;
            if (isHotspot) {{
              badge.setAttribute('fill', '#f87171');
              badge.setAttribute('font-weight', '900');
            }} else {{
              badge.setAttribute('fill', '#818cf8');
              badge.setAttribute('font-weight', 'normal');
            }}
          }}

          if (tacBadge) {{
            tacBadge.textContent = isHotspot ? `${{count}} DOCKETS [HIGHER CASELOAD]` : `${{count}} DOCKETS`;
            if (isHotspot) {{
              tacBadge.setAttribute('fill', '#ef4444');
              tacBadge.setAttribute('font-weight', '900');
            }} else {{
              tacBadge.setAttribute('fill', '#34d399');
              tacBadge.setAttribute('font-weight', 'normal');
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
              tacPoly.classList.add('region-hotspot', 'tactical-region-hotspot');
            }} else {{
              tacPoly.classList.remove('region-hotspot', 'tactical-region-hotspot');
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
        
        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {{
          // On initial page load: Select first case for dossier details, but DO NOT zoom map in! Keep Whole Globe visible!
          selectInvoiceById(liveCases[0].case_number, false);
        }} else {{
          renderInvoicesList();
          renderTacticalCasesStream();
          renderDossierCard(liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)));
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
            <button onclick="resetFilters()" class="mt-2 text-[10px] text-slate-300 hover:underline">Reset filters</button>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      container.innerHTML = filtered.map(c => {{
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        const isOnRadar = (selectedRegionName && c.region && c.region.toLowerCase() === selectedRegionName.toLowerCase());

        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : (isOnRadar ? 'bg-slate-800/90 hover:bg-slate-800 text-slate-100 border border-indigo-500/40' : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5');
        
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300'));

        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        // Render stacked avatars if multiple suspects
        let avatarMarkup = '';
        if (isMulti) {{
          avatarMarkup = '<div class="flex -space-x-2 overflow-hidden flex-shrink-0">' +
            c.suspects.slice(0, 3).map(function(s) {{
              const sInit = s.name ? s.name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'S';
              const imgHtml = s.mugshot_front ? '<img src="' + s.mugshot_front + '" alt="" class="w-full h-full object-cover">' : sInit;
              return '<div class="w-8 h-8 rounded-full overflow-hidden bg-slate-700 ring-2 ring-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-xs" title="' + (s.name || '') + ' (' + (s.role || '') + ')">' + imgHtml + '</div>';
            }}).join('') +
            '</div>';
        }} else {{
          avatarMarkup = `
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
              ${{c.mugshot_front ? `<img src="${{c.mugshot_front}}" alt="" class="w-full h-full object-cover">` : initials}}
            </div>
          `;
        }}

        // Region Radar Badge
        const regionBadgeHtml = isOnRadar
          ? `<span class="px-1.5 py-0.2 rounded bg-indigo-500/40 text-indigo-200 border border-indigo-400/50 font-bold font-mono-code text-[8.5px]"><i data-lucide="map-pin" class="w-3 h-3 inline text-slate-400"></i> ${{c.region || 'Greater Accra'}} (ON RADAR)</span>`
          : `<span class="text-[9.5px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate"><i data-lucide="map-pin" class="w-3 h-3 inline text-slate-400"></i> ${{c.region || 'Greater Accra'}}</span>`;

        // Render suspect names
        let suspectsDisplay = '';
        if (isMulti) {{
          const namesHtml = c.suspects.map(function(s, idx) {{
            const isPrim = (idx === 0) ? 'underline decoration-indigo-300' : '';
            return '<span class="' + isPrim + '">' + (s.name || 'Suspect') + '</span>';
          }}).join(' & ');

          suspectsDisplay = `
            <div class="text-[10.5px] font-bold ${{isSelected ? 'text-white' : 'text-slate-100'}} truncate">
              <i data-lucide="users" class="w-3.5 h-3.5 inline text-slate-400"></i> ${{namesHtml}}
            </div>
            <div class="text-[9.5px] truncate flex items-center space-x-1 mt-0.5">
              <span class="px-1.5 py-0.2 rounded bg-indigo-500/30 text-indigo-200 font-mono-code font-bold">${{c.suspects.length}} Suspects</span>
              <span>•</span>
              ${{regionBadgeHtml}}
            </div>
          `;
        }} else {{
          suspectsDisplay = `
            <div class="text-[10px] font-bold ${{isSelected ? 'text-white' : 'text-slate-200'}} truncate">
              <i data-lucide="user" class="w-3.5 h-3.5 inline text-slate-400"></i> ${{c.suspect_name}}
            </div>
            <div class="mt-0.5 truncate">
              ${{regionBadgeHtml}}
            </div>
          `;
        }}

        return `
          <div onclick="selectInvoiceById('${{c.case_number}}', true)" 
               class="p-2.5 rounded-2xl cursor-pointer transition-all flex items-center justify-between gap-2.5 ${{activeClasses}} group">
            
            <div class="flex items-center space-x-2.5 min-w-0">
              ${{avatarMarkup}}
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-xs font-mono-code">${{c.case_number}}</span>
                  ${{isMulti ? '<span class="text-[8.5px] px-1 py-0.2 bg-indigo-400/30 text-indigo-200 rounded font-bold">MULTI</span>' : ''}}
                </div>
                ${{suspectsDisplay}}
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
    }}

    // Render live docket stream in tactical theatre mode
    function renderTacticalCasesStream() {{
      const container = document.getElementById('tacCasesListContainer');
      if (!container) return;

      container.innerHTML = liveCases.map(c => {{
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        return `
          <div onclick="selectInvoiceById('${{c.case_number}}', true);" 
               class="p-2 rounded-xl ${{isSelected ? 'bg-indigo-600/50 border-indigo-400 ring-1 ring-indigo-400 text-white' : 'bg-slate-900/90 hover:bg-emerald-500/20 border-emerald-500/20 text-slate-300'}} border cursor-pointer transition-colors flex items-center justify-between text-xs font-mono-code">
            <div class="flex items-center space-x-2 truncate">
              <div class="truncate">
                <span class="font-bold text-white">${{c.case_number}}</span>
                <span class="text-[10px] ${{isSelected ? 'text-indigo-200 font-semibold' : 'text-slate-400'}} block">${{c.suspect_name}} • ${{c.region}}</span>
              </div>
            </div>
            <span class="text-[10px] px-1.5 py-0.5 rounded ${{isSelected ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-300'}} font-bold">${{c.status_badge}}</span>
          </div>
        `;
      }}).join('');
    }}

    // ──────────────────────────────────────────────────────────
    // 4. RENDER FORENSIC DOSSIER DETAILS CARD (Right Panel)
    // ──────────────────────────────────────────────────────────
    let dossierSelectedSuspectId = 'all';

    function setDossierSuspect(suspectId) {{
      dossierSelectedSuspectId = suspectId;
      const c = liveCases.find(function(item) {{ return item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId); }});
      if (c) {{
        renderSuspectsProfile(c);
        renderDossierExhibits(c);
      }}
    }}

    function renderSuspectsProfile(c) {{
      const container = document.getElementById('cardSuspectsProfileContainer');
      if (!container) return;

      const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;

      if (!isMulti) {{
        const s = (c.suspects && c.suspects.length > 0) ? c.suspects[0] : {{
          name: c.suspect_name || 'Kofi Mensah',
          role: c.suspect_role || 'Primary Target',
          ghana_card: c.ghana_card || 'GHA-XXXXXXXXX-X',
          nationality: c.nationality || 'Ghanaian',
          age: c.age || '34 yrs',
          gender: c.gender || 'Male',
          mugshot_front: c.mugshot_front
        }};
        const initials = s.name ? s.name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'KM';

        container.innerHTML = `
          <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
            <div class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
              ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" class="w-full h-full object-cover" alt="">` : initials}}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">${{s.role}}</span>
                <span class="text-[10px] font-mono-code text-slate-400">${{s.ghana_card || ''}}</span>
              </div>
              <div class="text-sm font-extrabold text-white truncate mt-0.5">${{s.name}}</div>
              <div class="text-[10px] text-slate-300 truncate">${{[s.gender, s.age, s.nationality].filter(Boolean).join(' • ')}}</div>
            </div>
          </div>
        `;
        return;
      }}

      // MULTIPLE SUSPECTS CASE: Show suspect selection bar + cards
      let selectorHtml = '<div class="flex items-center space-x-1.5 mb-2.5 overflow-x-auto pb-1">' +
        '<button type="button" data-suspect="all" onclick="setDossierSuspect(this.dataset.suspect)" class="px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ' +
        (dossierSelectedSuspectId === 'all' ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20') +
        '">ALL TARGETS (' + c.suspects.length + ')</button>' +
        c.suspects.map(function(s) {{
          const isAct = String(dossierSelectedSuspectId) === String(s.id);
          const cls = isAct ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20';
          const shortRole = (s.role || '').replace(' Suspect', '');
          const firstName = (s.name || '').split(' ')[0];
          return '<button type="button" data-suspect="' + s.id + '" onclick="setDossierSuspect(this.dataset.suspect)" class="px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ' + cls + '">' +
            firstName + ' <span class="text-[9px] opacity-75 font-normal">(' + shortRole + ')</span></button>';
        }}).join('') +
        '</div>';

      let cardsHtml = '';
      if (dossierSelectedSuspectId === 'all') {{
        cardsHtml = '<div class="space-y-2">' +
          c.suspects.map(function(s) {{
            const initials = s.name ? s.name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'S';
            const roleColor = (s.role || '').includes('Primary') ? 'text-indigo-300 font-bold' : 'text-slate-400';
            const details = [s.gender, s.age, s.nationality].filter(Boolean).join(' • ');
            const imgHtml = s.mugshot_front ? '<img src="' + s.mugshot_front + '" class="w-full h-full object-cover" alt="">' : initials;
            return '<div class="flex items-center space-x-3 bg-white/5 p-2.5 rounded-2xl border border-white/5 hover:border-white/15 transition-all">' +
              '<div class="w-10 h-10 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-xs ring-1 ring-white/20">' +
              imgHtml +
              '</div>' +
              '<div class="min-w-0 flex-1">' +
              '<div class="flex items-center justify-between">' +
              '<span class="text-[9px] font-mono-code ' + roleColor + ' uppercase">' + (s.role || 'Suspect') + '</span>' +
              '<span class="text-[9px] font-mono-code text-slate-400">' + (s.ghana_card || '') + '</span>' +
              '</div>' +
              '<div class="text-xs font-extrabold text-white truncate mt-0.5">' + (s.name || '') + '</div>' +
              '<div class="text-[10px] text-slate-300 truncate">' + details + '</div>' +
              '</div>' +
              '</div>';
          }}).join('') +
          '</div>';
      }} else {{
        const s = c.suspects.find(function(item) {{ return String(item.id) === String(dossierSelectedSuspectId); }}) || c.suspects[0];
        const initials = s.name ? s.name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'S';
        const details = [s.gender, s.age, s.nationality].filter(Boolean).join(' • ');
        cardsHtml = `
          <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
            <div class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
              ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" class="w-full h-full object-cover" alt="">` : initials}}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">${{s.role}}</span>
                <span class="text-[10px] font-mono-code text-slate-400">${{s.ghana_card || ''}}</span>
              </div>
              <div class="text-sm font-extrabold text-white truncate mt-0.5">${{s.name}}</div>
              <div class="text-[10px] text-slate-300 truncate">${{details}}</div>
            </div>
          </div>
        `;
      }}

      container.innerHTML = selectorHtml + cardsHtml;
    }}

    function renderDossierExhibits(c) {{
      const grid = document.getElementById('exhibitCardsGrid');
      if (!grid) return;

      let allExhibits = (c.exhibits && c.exhibits.length > 0) ? c.exhibits : [];
      let filteredExhibits = allExhibits;
      let filterLabel = '';

      if (c.is_multiple && c.suspects && dossierSelectedSuspectId !== 'all') {{
        const targetSuspect = c.suspects.find(function(s) {{ return String(s.id) === String(dossierSelectedSuspectId); }});
        if (targetSuspect) {{
          filterLabel = '(' + targetSuspect.name.split(' ')[0] + ')';
          filteredExhibits = allExhibits.filter(function(ex) {{
            // Strictly check ownership - do NOT associate exhibits that belong to someone else
            if (ex.suspect_id && String(ex.suspect_id) === String(targetSuspect.id)) return true;
            if (ex.associated_person) {{
              const ap = ex.associated_person.toLowerCase();
              if (ap.includes(targetSuspect.name.toLowerCase()) || ap === 'all suspects') {{
                return true;
              }}
            }}
            return false;
          }});
        }}
      }}

      const elSub = document.getElementById('cardSubTotal');
      if (elSub) elSub.textContent = `${{filteredExhibits.length}} Exhibits ${{filterLabel}}`;

      if (filteredExhibits.length === 0) {{
        grid.innerHTML = `
          <div class="col-span-2 py-5 px-3 text-center rounded-xl bg-white/5 border border-dashed border-white/10 text-slate-400 text-xs font-mono-code">
            <i data-lucide="shield-alert" class="w-5 h-5 mx-auto mb-1 text-slate-500"></i>
            <span>No exhibits associated with this target</span>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      let cardsHtml = filteredExhibits.map(function(ex) {{
        const ownerName = ex.associated_person || (c.is_multiple ? 'All Suspects' : c.suspect_name);
        return `
          <div class="bg-white/10 hover:bg-white/15 rounded-xl p-3 border border-white/10 transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-300">
              <span class="text-xs font-mono-code font-black text-indigo-200 truncate">${{ex.quantity || '1 Unit'}}</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded bg-white/10 text-emerald-300 font-mono-code font-bold truncate max-w-[85px]">${{ex.category || 'Evidence'}}</span>
            </div>
            <div class="text-[11px] text-white font-bold truncate mt-2" title="${{ex.title}}">${{ex.title}}</div>
            <div class="mt-2 pt-1.5 border-t border-white/10 flex items-center justify-between text-[9px] font-mono-code">
              <span class="text-indigo-200 truncate flex items-center space-x-1" title="Belongs to: ${{ownerName}}">
                <i data-lucide="user-check" class="w-3 h-3 text-slate-300 shrink-0"></i>
                <span class="truncate">${{ownerName}}</span>
              </span>
              <span class="text-slate-300 font-bold ml-1">${{ex.value || ''}}</span>
            </div>
          </div>
        `;
      }}).join('');

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

    function renderDossierCard(c) {{
      if (!c) return;

      const elInv = document.getElementById('cardInvoiceNumber');
      if (elInv) elInv.textContent = c.case_number || '# NCC-2026-2572';

      const elBadge = document.getElementById('cardInvoiceStatusBadge');
      if (elBadge) elBadge.textContent = c.status_badge || 'Under Investigation';

      const elComp = document.getElementById('cardCompanyName');
      if (elComp) elComp.textContent = c.unit_name || '{unit_name}';

      const elOfficer = document.getElementById('cardOfficerName');
      if (elOfficer) elOfficer.textContent = c.submitting_officer || 'Insp. K. Mensah';

      const elLoc = document.getElementById('cardLocationName');
      if (elLoc) elLoc.textContent = (c.raid_location ? c.raid_location.substring(0, 26) : 'Kotoka Airport');

      const exhCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 3);

      const elTot = document.getElementById('cardTotal');
      if (elTot) elTot.textContent = `${{exhCount}} Items Logged`;

      const elBal = document.getElementById('cardBalanceDue');
      if (elBal) elBal.textContent = (c.status_badge === 'Approved' ? 'Court Order Signed' : 'Judicial Review Active');

      const elPrimary = document.getElementById('cardActionPrimary');
      if (elPrimary && c.id) elPrimary.href = `/cases/${{c.id}}/review`;

      const elLink = document.getElementById('cardActionLink');
      if (elLink && c.id) elLink.href = `/cases/${{c.id}}/pdf`;

      // Reset suspect filter to 'all' on case change and render
      dossierSelectedSuspectId = 'all';
      renderSuspectsProfile(c);
      renderDossierExhibits(c);
    }}

    function selectInvoiceById(caseRef, centerMap = true) {{
      const c = liveCases.find(item => item.case_number === caseRef || String(item.id) === String(caseRef));
      if (!c) return;

      selectedCaseId = c.case_number;

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

      if (typeof refreshGlobusEntities === 'function') {{
        refreshGlobusEntities(c);
      }}
      if (typeof refreshTacGlobusEntities === 'function' && isTacticalModeOpen) {{
        refreshTacGlobusEntities(c);
      }}

      if (centerMap && c.region) {{
        indicateCaseOnMap(c);
      }}
    }}

    function indicateCaseOnMap(c) {{
      if (!c || !c.region) return;
      selectedRegionName = c.region;
      focusRegionOnMap(c.region, isTacticalModeOpen, c);
      renderDistrictMap(c.region, c);
      renderTacDistrictMap(c.region, c);
      if (typeof globusFlyToCase === 'function') {{
        globusFlyToCase(c);
      }}
      if (typeof tacGlobusFlyToCase === 'function' && isTacticalModeOpen) {{
        tacGlobusFlyToCase(c);
      }}
    }}

    // ──────────────────────────────────────────────────────────
    // 5. 3D GHANA MAP ENGINE & REGION DRILL-DOWN ARCHITECTURE
    // ──────────────────────────────────────────────────────────
    function selectRegionByName(regName, fromTac = false) {{
      selectedRegionName = regName;
      focusRegionOnMap(regName, fromTac);

      const matchedCase = liveCases.find(c => c.region && c.region.toLowerCase() === regName.toLowerCase());
      if (matchedCase) {{
        selectInvoiceById(matchedCase.case_number, false);
      }} else {{
        renderDistrictMap(regName, null);
        renderTacDistrictMap(regName, null);
        renderInvoicesList();
      }}
    }}

    function focusRegionOnMap(regName, isTac = false, targetCase = null) {{
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
      if (stdPlane) {{
        stdPlane.style.transform = 'translate3d(0, 0, 0) scale(1) rotateX(0deg) rotateZ(0deg)';
        stdPlane.style.transition = 'transform 0.4s ease';
      }}
      if (tacPlane) {{
        tacPlane.style.transform = 'translate3d(0, 0, 0) scale(1) rotateX(0deg) rotateZ(0deg)';
        tacPlane.style.transition = 'transform 0.4s ease';
      }}

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
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      }});
      const isHotspot = (regCases === maxCaseload && maxCaseload > 0);

      // Hide all other regions and highlight active region on BOTH Standard and Tactical polygons
      ['.region-polygon', '.tactical-region-polygon'].forEach(selector => {{
        document.querySelectorAll(selector).forEach(p => {{
          if (p.getAttribute('data-name') === regName) {{
            p.style.display = 'block';
            p.style.opacity = '1';
            p.classList.add('region-active');
            if (isHotspot) {{
              p.classList.add('region-hotspot', 'tactical-region-hotspot');
            }} else {{
              p.classList.remove('region-hotspot', 'tactical-region-hotspot');
            }}
          }} else {{
            p.style.display = 'none';
            p.classList.remove('region-active');
          }}
        }});
      }});

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

      if (navHeader && drillTitle) {{
        navHeader.classList.remove('hidden');
        if (activeCase && activeCase.region && activeCase.region.toLowerCase() === regName.toLowerCase()) {{
          drillTitle.innerHTML = `<span class="text-pink-400 font-extrabold tracking-wide">TARGET RADAR:</span> ${{regName.toUpperCase()}} • <span class="text-white font-mono-code font-bold">${{activeCase.case_number}}</span> <span class="text-indigo-200 font-semibold">(${{activeCase.suspect_name}})</span>`;
        }} else {{
          drillTitle.textContent = `${{regName.toUpperCase()}} DISTRICT MAP (${{districts.length}} MMDAs • ${{regCases}} Cases)`;
        }}
      }}

      // Show organized MMDA Districts Drawer below map
      const drawer = document.getElementById('districtCardsDrawer');
      if (drawer) {{
        drawer.classList.remove('hidden');
        const badge = document.getElementById('districtDrawerBadge');
        if (badge) {{
          badge.textContent = `${{regCases}} Cases Linked`;
          badge.className = isHotspot 
            ? 'text-[10px] text-slate-300 font-bold bg-red-500/20 px-2 py-0.5 rounded border border-red-500/30' 
            : 'text-[10px] text-slate-300 font-bold bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30';
        }}
      }}

      // 6. Update Tactical Navigation Header, Left HUD & Bottom Deck
      const tacNav = document.getElementById('tacDistrictNavHeader');
      const tacTitle = document.getElementById('tacDistrictDrillTitle');
      const tacBadgeBox = document.getElementById('tacDistrictDrillTitleBadge');
      if (tacNav && tacTitle) {{
        tacNav.classList.remove('hidden');
        if (activeCase && activeCase.region && activeCase.region.toLowerCase() === regName.toLowerCase()) {{
          tacTitle.innerHTML = `<span class="text-pink-400 font-black">TARGET RADAR:</span> ${{regName.toUpperCase()}} • <span class="text-white font-mono-code font-bold">${{activeCase.case_number}}</span> (${{activeCase.suspect_name}}) • ${{activeCase.raid_location || 'TACTICAL SECTOR'}}`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-pink-950/90 text-white border border-pink-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        }} else if (isHotspot) {{
          tacTitle.innerHTML = `<span class="text-slate-300 font-black">HIGHER CASELOAD HOTSPOT:</span> ${{regName.toUpperCase()}} MMDA RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-red-950/90 text-white border border-red-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        }} else {{
          tacTitle.innerHTML = `<span class="text-slate-300 font-black">TACTICAL SECTOR:</span> ${{regName.toUpperCase()}} MMDA RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-slate-950/90 text-white border border-emerald-500/40 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        }}
      }}

      // Update Tactical Left HUD with Telemetry
      const tacName = document.getElementById('tacHudRegionName');
      if (tacName) tacName.textContent = regName.toUpperCase();
      const tacCap = document.getElementById('tacHudCapital');
      if (tacCap) tacCap.textContent = `Capital: ${{r.capital || 'Ghana'}}`;
      const tacDoc = document.getElementById('tacHudDockets');
      if (tacDoc) {{
        tacDoc.textContent = isHotspot ? `${{regCases}} Dockets [HIGHER CASELOAD]` : `${{regCases}} Dockets Active`;
        tacDoc.className = isHotspot ? 'text-base font-black text-slate-300' : 'text-base font-black text-slate-300';
      }}
      const tacIso = document.getElementById('tacHudIso');
      if (tacIso) {{
        tacIso.textContent = r.iso || 'GH-RG';
        tacIso.className = isHotspot 
          ? 'text-[10px] font-mono-code text-red-300 bg-red-500/20 px-2 py-0.5 rounded border border-red-500/40 font-bold'
          : 'text-[10px] font-mono-code text-slate-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20';
      }}
      const tacCmd = document.getElementById('tacHudCommander');
      if (tacCmd) tacCmd.textContent = r.commander || 'Regional Forensic Command';
      const tacStations = document.getElementById('tacHudStations');
      if (tacStations) tacStations.textContent = `${{districts.length}} Districts`;

      // Show Tactical MMDA Districts Deck Drawer
      const tacDeck = document.getElementById('tacDistrictDeckDrawer');
      if (tacDeck) tacDeck.classList.remove('hidden');

      // 7. Render Districts on BOTH Standard and Tactical Maps
      renderDistrictMap(regName, targetCase);
      renderTacDistrictMap(regName, targetCase);
    }}

    function renderDistrictMap(regName, targetCase = null) {{
      const group = document.getElementById('districtLayersGroup');
      const drawerGrid = document.getElementById('districtDrawerGrid');
      if (!group) return;

      const activeCase = targetCase || (selectedCaseId ? liveCases.find(item => item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId)) : null);

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

      // 1. Render Clean Rectangular MMDA Badges (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;
        const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

        if (isTarget) {{
          return `
            <g class="district-node cursor-pointer group active-target-district" onclick="event.stopPropagation(); selectInvoiceById('${{activeCase.case_number}}', false);">
              <rect x="${{d.x - 29}}" y="${{d.y - 7.5}}" width="58" height="15" rx="3" fill="#1b0716" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 6px rgba(244,63,94,0.6))" />
              <text x="${{d.x}}" y="${{d.y - 1.8}}" text-anchor="middle" font-size="3.7" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.6}}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{activeCase.case_number}} • ${{activeCase.suspect_name.split(' ')[0]}}
              </text>
            </g>
          `;
        }} else if (hasCases) {{
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById('${{primaryCase.case_number}}', false);">
              <rect x="${{d.x - 26}}" y="${{d.y - 6.0}}" width="52" height="12" rx="2" fill="#181926" stroke="#ef4444" stroke-width="0.9" />
              <text x="${{d.x}}" y="${{d.y - 1.2}}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.2}}" text-anchor="middle" font-size="3.0" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{primaryCase.case_number}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <rect x="${{d.x - 22}}" y="${{d.y - 5.0}}" width="44" height="10" rx="1.5" fill="#1e293b" stroke="#475569" stroke-width="0.5" class="group-hover:stroke-indigo-400 transition-colors" />
              <text x="${{d.x}}" y="${{d.y - 0.7}}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#cbd5e1" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 3.6}}" text-anchor="middle" font-size="2.6" font-weight="medium" fill="#94a3b8" font-family="'JetBrains Mono', monospace" class="select-none">
                CAP: ${{d.capital.toUpperCase()}}
              </text>
            </g>
          `;
        }}
      }}).join('');

      // 2. Render Spacious, Elegant MMDA District Cards in the Bottom Drawer
      if (drawerGrid) {{
        drawerGrid.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const linked = item.cases;
          const hasCases = linked.length > 0;
          const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

          if (isTarget) {{
            return `
              <div id="drawer-card-${{d.code}}" onclick="selectInvoiceById('${{activeCase.case_number}}', false)"
                   class="p-2.5 rounded-xl bg-gradient-to-r from-pink-950/90 to-indigo-950/90 border-2 border-pink-500 cursor-pointer transition-all flex items-center justify-between gap-2 shadow-lg shadow-pink-500/25 ring-2 ring-pink-400/50 scale-[1.01] group">
                <div class="truncate">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-extrabold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  </div>
                  <div class="text-[10px] text-pink-200 font-mono-code font-bold truncate mt-0.5">
                    ${{activeCase.case_number}} • ${{activeCase.suspect_name}} (${{activeCase.exhibits_count || 1}} Exh)
                  </div>
                </div>
                <span class="text-[9px] px-2 py-0.5 rounded bg-pink-500 text-white font-black tracking-wider flex-shrink-0">
                  TARGET
                </span>
              </div>
            `;
          }} else if (hasCases) {{
            const c = linked[0];
            return `
              <div id="drawer-card-${{d.code}}" onclick="selectInvoiceById('${{c.case_number}}', false)"
                   class="p-2 rounded-xl bg-red-950/40 hover:bg-red-900/60 border border-red-500/50 cursor-pointer transition-all flex items-center justify-between gap-2 shadow-sm group">
                <div class="truncate">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-bold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  </div>
                  <div class="text-[10px] text-red-300 font-mono-code truncate mt-0.5">
                    ${{c.case_number}} • ${{c.suspect_name}} (${{c.exhibits_count || 1}} Exh)
                  </div>
                </div>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-500 text-white font-extrabold flex-shrink-0">
                  HOTSPOT
                </span>
              </div>
            `;
          }} else {{
            return `
              <div id="drawer-card-${{d.code}}" class="p-2 rounded-xl bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800 text-slate-300 transition-all flex items-center justify-between gap-2">
                <div class="truncate">
                  <div class="font-bold text-slate-200 text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</div>
                  <div class="text-[9.5px] text-slate-500 truncate mt-0.5">Capital: ${{d.capital}}</div>
                </div>
                <span class="text-[9px] text-slate-400 font-mono-code flex-shrink-0">0 Dockets</span>
              </div>
            `;
          }}
        }}).join('');

        // Smooth scroll active target card into view if exists
        setTimeout(() => {{
          const targetCard = drawerGrid.querySelector('.border-pink-500');
          if (targetCard && typeof targetCard.scrollIntoView === 'function') {{
            targetCard.scrollIntoView({{ behavior: 'smooth', block: 'nearest', inline: 'center' }});
          }}
        }}, 50);
      }}
    }}

    // Renders Tactical Radar MMDA Districts & Hotspots with High-Clarity Labels & Details
    function renderTacDistrictMap(regName, targetCase = null) {{
      const group = document.getElementById('tacDistrictLayersGroup');
      if (!group) return;

      const activeCase = targetCase || (selectedCaseId ? liveCases.find(item => item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId)) : null);

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      const districtWithCases = districts.map(d => {{
        const linked = regionCases.filter(c => {{
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return (d.keywords || []).some(kw => locText.includes(kw.toLowerCase()));
        }});
        return {{ district: d, cases: linked }};
      }});

      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {{
        districtWithCases[0].cases.push(...unassigned);
      }}

      // 1. Populate Tactical Left HUD MMDA list (Safe without null crashes)
      const tacDistList = document.getElementById('tacHudDistrictsList');
      if (tacDistList) {{
        tacDistList.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const count = item.cases.length;
          const isTarget = activeCase && item.cases.some(c => c.case_number === activeCase.case_number);
          const isRed = count > 0;
          const firstCase = isTarget ? activeCase : (isRed ? item.cases[0] : null);
          const clickAttr = firstCase ? `onclick="selectInvoiceById('${{firstCase.case_number}}', false);"` : '';

          let cardCls = 'bg-slate-900/70 border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/30 text-slate-300';
          let badgeMarkup = '<span class="text-[9px] font-mono-code font-bold text-slate-300 bg-emerald-500/10 px-1.5 py-0.5 rounded">0 Active</span>';

          if (isTarget) {{
            cardCls = 'bg-gradient-to-r from-pink-950/90 to-red-950/90 border-2 border-pink-500 text-pink-100 shadow-lg shadow-pink-500/20';
            badgeMarkup = '<span class="text-[9px] font-mono-code font-black text-white bg-pink-500 px-2 py-0.5 rounded">TARGET</span>';
          }} else if (isRed) {{
            cardCls = 'bg-red-950/70 border-red-500/60 hover:bg-red-900/60 text-red-200';
            badgeMarkup = `<span class="text-[9px] font-mono-code font-bold text-slate-300 bg-red-500/20 px-1.5 py-0.5 rounded">${{count}} DOCKET</span>`;
          }}

          return `
            <div ${{clickAttr}} class="p-2 rounded-xl border cursor-pointer transition-all ${{cardCls}}">
              <div class="flex items-center justify-between">
                <span class="font-bold text-white text-[11px] truncate">${{isTarget ? '' : ''}}[${{d.code}}] ${{d.short_name}}</span>
                ${{badgeMarkup}}
              </div>
              <div class="text-[9.5px] text-slate-400 truncate mt-0.5">Cap: ${{d.capital}}</div>
              ${{firstCase ? `<div class="text-[9.5px] ${{isTarget ? 'text-pink-200 font-extrabold' : 'text-red-300 font-bold'}} truncate mt-0.5"><i data-lucide="user" class="w-3 h-3 inline text-slate-400"></i> ${{firstCase.suspect_name}} (${{firstCase.case_number}})</div>` : ''}}
            </div>
          `;
        }}).join('');
      }}

      // 2. Populate Bottom Tactical MMDA Districts Deck Drawer
      const deckGrid = document.getElementById('tacDistrictDeckGrid');
      const deckBadge = document.getElementById('tacDistrictDeckBadge');
      if (deckBadge) {{
        deckBadge.textContent = `${{districts.length}} MMDA DISTRICTS • ${{regionCases.length}} DOCKETS`;
      }}
      if (deckGrid) {{
        deckGrid.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const linked = item.cases;
          const count = linked.length;
          const hasCases = count > 0;
          const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

          if (isTarget) {{
            return `
              <div onclick="selectInvoiceById('${{activeCase.case_number}}', false)"
                   class="p-2.5 rounded-xl bg-gradient-to-r from-pink-950/90 to-red-950/90 border-2 border-pink-500 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-lg shadow-pink-500/20 scale-[1.01] group">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-black text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-pink-500 text-white font-black flex-shrink-0">TARGET</span>
                </div>
                <div class="text-[10px] text-pink-200 font-extrabold truncate mt-0.5">
                  ${{activeCase.case_number}} • ${{activeCase.suspect_name}}
                </div>
                <div class="text-[9px] text-pink-300/80 truncate flex items-center justify-between mt-0.5">
                  <span>Location: ${{activeCase.raid_location || d.capital}}</span>
                  <span class="text-pink-200 font-bold">${{activeCase.exhibits_count || 1}} Exhibit(s)</span>
                </div>
              </div>
            `;
          }} else if (hasCases) {{
            const c = linked[0];
            return `
              <div onclick="selectInvoiceById('${{c.case_number}}', false)"
                   class="p-2.5 rounded-xl bg-red-950/60 hover:bg-red-900/80 border border-red-500/70 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-lg group">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-bold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-red-600 text-white font-black flex-shrink-0">HOTSPOT</span>
                </div>
                <div class="text-[10px] text-red-200 font-bold truncate">
                  ${{c.case_number}} • ${{c.suspect_name}}
                </div>
                <div class="text-[9px] text-slate-300 truncate flex items-center justify-between">
                  <span>Cap: ${{d.capital}}</span>
                  <span class="text-red-300 font-bold">${{c.exhibits_count || 1}} Exhibit(s)</span>
                </div>
              </div>
            `;
          }} else {{
            return `
              <div class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-slate-800/90 border border-slate-700/80 hover:border-emerald-500/50 text-slate-300 transition-all flex flex-col justify-between gap-1">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-bold text-slate-100 text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  <span class="text-[9px] text-slate-400 font-mono-code flex-shrink-0">0 Dockets</span>
                </div>
                <div class="text-[9.5px] text-slate-400 truncate">
                  Capital: ${{d.capital}}
                </div>
                <div class="text-[9px] text-slate-300 font-medium truncate">
                  Patrol Sector Clear
                </div>
              </div>
            `;
          }}
        }}).join('');
      }}

      // 3. Render High-Clarity 2-Line Tactical SVG Markers on Map with Robust Code-Based Hover
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;
        const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

        if (isTarget) {{
          return `
            <g class="tac-district-marker cursor-pointer group active-target-tac" 
               onclick="event.stopPropagation(); selectInvoiceById('${{activeCase.case_number}}', false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${{d.code}}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${{d.x - 30}}" y="${{d.y - 8.0}}" width="60" height="16" rx="3" fill="#20051a" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 8px rgba(244,63,94,0.7))" />
              <text x="${{d.x}}" y="${{d.y - 2.0}}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.8}}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{activeCase.case_number}} • ${{activeCase.suspect_name.split(' ')[0]}}
              </text>
            </g>
          `;
        }} else if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation(); selectInvoiceById('${{c.case_number}}', false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${{d.code}}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${{d.x - 28}}" y="${{d.y - 6.5}}" width="56" height="13" rx="2" fill="#120404" stroke="#ef4444" stroke-width="1.2" />
              <text x="${{d.x}}" y="${{d.y - 1.2}}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.5}}" text-anchor="middle" font-size="3.1" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{c.case_number}} • ${{c.suspect_name}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation();"
               onmouseenter="onTacDistrictHoverByCode(event, '${{d.code}}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${{d.x - 23}}" y="${{d.y - 5.0}}" width="46" height="10" rx="1.8" fill="#041611" stroke="#10b981" stroke-width="0.7" class="group-hover:stroke-emerald-300 transition-colors" />
              <text x="${{d.x}}" y="${{d.y - 0.7}}" text-anchor="middle" font-size="3.3" font-weight="bold" fill="#a7f3d0" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 3.6}}" text-anchor="middle" font-size="2.6" font-weight="medium" fill="#6ee7b7" opacity="0.85" font-family="'JetBrains Mono', monospace" class="select-none">
                CAP: ${{d.capital.toUpperCase()}}
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
      
      // 1. Restore National 800x960 ViewBox on BOTH maps
      const stdSvg = document.getElementById('ghanaMapSvg');
      const tacSvg = document.getElementById('tacGhanaMapSvg');
      if (stdSvg) stdSvg.setAttribute('viewBox', '0 0 800 960');
      if (tacSvg) tacSvg.setAttribute('viewBox', '0 0 800 960');

      // 2. Restore 3D Perspective Tilt on BOTH planes
      const stdPlane = document.getElementById('ghana3dPlane');
      const tacPlane = document.getElementById('tacGhana3dPlane');
      if (stdPlane) {{
        const rotX = is3DActive ? 22 : 0;
        const rotZ = is3DActive ? -8 : 0;
        stdPlane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
      }}
      if (tacPlane) {{
        const rotX = isTac3DActive ? 24 : 0;
        const rotZ = isTac3DActive ? -6 : 0;
        tacPlane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
      }}

      // 3. Restore all 16 regions & background effects on BOTH maps
      const stdExtrusion = document.getElementById('extrusionShadowPass');
      const tacExtrusion = document.getElementById('tacExtrusionShadowPass');
      if (stdExtrusion) stdExtrusion.style.display = 'block';
      if (tacExtrusion) tacExtrusion.style.display = 'block';

      const rings = document.getElementById('radarRingsGroup');
      if (rings) rings.style.display = 'block';

      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      }});

      ['.region-polygon', '.tactical-region-polygon'].forEach(selector => {{
        document.querySelectorAll(selector).forEach(p => {{
          p.style.display = 'block';
          p.style.opacity = '1';
          p.classList.remove('region-active');
          const rName = p.getAttribute('data-name');
          const rCases = (regionsData[rName] ? regionsData[rName].cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (rName || '').toLowerCase()).length;
          const isHotspot = (rCases === maxCaseload && maxCaseload > 0);
          if (isHotspot) {{
            p.classList.add('region-hotspot', 'tactical-region-hotspot');
          }} else {{
            p.classList.remove('region-hotspot', 'tactical-region-hotspot');
          }}
        }});
      }});

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
    }}

    function toggle3DView(isTac = false) {{
      if (isTac) {{
        isTac3DActive = !isTac3DActive;
        const btn = document.getElementById('btnTacToggle3D');
        if (btn) {{
          btn.innerHTML = isTac3DActive ? '<span>3D RELIEF</span>' : '<span>2D FLAT</span>';
        }}
        if (!selectedRegionName) {{
          const plane = document.getElementById('tacGhana3dPlane');
          if (plane) {{
            const rotX = isTac3DActive ? 24 : 0;
            const rotZ = isTac3DActive ? -6 : 0;
            plane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
          }}
        }}
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
        if (!selectedRegionName) {{
          const plane = document.getElementById('ghana3dPlane');
          if (plane) {{
            const rotX = is3DActive ? 22 : 0;
            const rotZ = is3DActive ? -8 : 0;
            plane.style.transform = `translate3d(0, 0, 0) scale(1) rotateX(${{rotX}}deg) rotateZ(${{rotZ}}deg)`;
          }}
        }}
      }}
    }}

    function zoomMap(factor, isTac = false) {{
      const svg = isTac ? document.getElementById('tacGhanaMapSvg') : document.getElementById('ghanaMapSvg');
      if (!svg) return;
      const vb = svg.getAttribute('viewBox').split(' ').map(Number);
      if (vb.length === 4) {{
        const newW = vb[2] / factor;
        const newH = vb[3] / factor;
        const newX = vb[0] + (vb[2] - newW) / 2;
        const newY = vb[1] + (vb[3] - newH) / 2;
        svg.setAttribute('viewBox', `${{newX}} ${{newY}} ${{newW}} ${{newH}}`);
      }}
    }}

    function openTacticalMode() {{
      isTacticalModeOpen = true;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {{
        theatre.classList.remove('hidden');
      }}

      try {{
        if (document.documentElement.requestFullscreen && !document.fullscreenElement) {{
          document.documentElement.requestFullscreen().catch(() => {{}});
        }}
      }} catch (e) {{}}

      lucide.createIcons();
      renderTacticalCasesStream();

      // Allow 50ms layout reflow for full modal width/height, then initialize or resize globe
      setTimeout(() => {{
        if (typeof initTacOpenGlobusEngine === 'function') {{
          initTacOpenGlobusEngine();
        }}
        if (tacGlobusInstance) {{
          window.dispatchEvent(new Event('resize'));
          refreshTacGlobusEntities();
        }}
      }}, 50);

      // Fly to active selected case or Whole Globe in Tactical Theatre
      setTimeout(() => {{
        if (selectedCaseId) {{
          const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
          if (activeCase && typeof tacGlobusFlyToCase === 'function') {{
            tacGlobusFlyToCase(activeCase);
          }} else if (typeof tacGlobusFlyToWholeGlobe === 'function') {{
            tacGlobusFlyToWholeGlobe();
          }}
        }} else if (typeof tacGlobusFlyToWholeGlobe === 'function') {{
          tacGlobusFlyToWholeGlobe();
        }}
      }}, 350);

      // Find region with higher number of cases
      let maxCaseload = 0;
      let highestRegion = 'Greater Accra';
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) {{
          maxCaseload = cVal;
          highestRegion = k;
        }}
      }});

      const targetReg = selectedRegionName || highestRegion;

      if (targetReg) {{
        selectRegionByName(targetReg, true);
      }} else {{
        resetMapView(true);
      }}
    }}

    function closeTacticalMode() {{
      isTacticalModeOpen = false;
      const theatre = document.getElementById('tacticalOpsTheatre');
      if (theatre) {{
        theatre.classList.add('hidden');
      }}
      setTimeout(() => {{
        window.dispatchEvent(new Event('resize'));
      }}, 100);

      try {{
        if (document.exitFullscreen && document.fullscreenElement) {{
          document.exitFullscreen().catch(() => {{}});
        }}
      }} catch (e) {{}}
    }}

    function onRegionHover(e, name) {{
      if (selectedRegionName) return; // Don't show tooltip while in district drill-down
      const tt = document.getElementById('regionHoverTooltip');
      const ttName = document.getElementById('tooltipRegionName');
      const ttSub = document.getElementById('tooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      ttName.textContent = name;
      ttSub.textContent = `Capital: ${{r ? r.capital : 'Ghana'}} • ${{r ? r.cases : 0}} Dockets Active`;

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

    function toggleTacDistrictDeck() {{
      const grid = document.getElementById('tacDistrictDeckGrid');
      const txt = document.getElementById('tacDeckToggleText');
      if (!grid) return;
      if (grid.classList.contains('hidden')) {{
        grid.classList.remove('hidden');
        if (txt) txt.textContent = 'Collapse Deck';
      }} else {{
        grid.classList.add('hidden');
        if (txt) txt.textContent = 'Expand Deck';
      }}
    }}

    function onTacDistrictHoverByCode(e, code) {{
      if (!selectedRegionName) return;
      const list = districtsData[selectedRegionName] || [];
      const d = list.find(x => x.code === code);
      if (!d) return;

      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === selectedRegionName.toLowerCase());
      const linked = regionCases.filter(c => {{
        const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
        return (d.keywords || []).some(kw => locText.includes(kw.toLowerCase()));
      }});

      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      ttName.innerHTML = `<span class="text-white font-black">[${{d.code}}] ${{d.name || d.short_name}}</span>`;
      if (linked.length > 0) {{
        const c = linked[0];
        ttSub.innerHTML = `
          <div class="mt-1 text-red-300 font-bold text-[11px]">${{linked.length}} DOCKET(S) ACTIVE IN MMDA</div>
          <div class="text-[10px] text-white font-bold mt-0.5">Target: ${{c.suspect_name}} (${{c.case_number}})</div>
          <div class="text-[9.5px] text-slate-300 mt-0.5">Capital: ${{d.capital}} • Exhibits: ${{c.exhibits_count || 1}} • Status: ${{c.status_badge || 'Active'}}</div>
        `;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2.5 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/80 transition-opacity duration-150";
      }} else {{
        ttSub.innerHTML = `
          <div class="mt-0.5 text-emerald-300 font-bold text-[11px]">MMDA Classification: Official District Assembly</div>
          <div class="text-[10px] text-slate-200 mt-0.5">Administrative Capital: ${{d.capital}}</div>
          <div class="text-[9.5px] text-slate-300 mt-0.5">Patrol Status: Sector Clear • 0 Active Dockets</div>
        `;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2.5 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150";
      }}

      const stage = document.getElementById('tacMapStage');
      if (stage) {{
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 20) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }}
    }}

    function onTacDistrictLeave() {{
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }}

    function onTacRegionHover(e, name) {{
      if (selectedRegionName) return;
      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      const count = (r ? r.cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (name || '').toLowerCase()).length;

      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      }});
      const isHotspot = (count === maxCaseload && maxCaseload > 0);

      ttName.textContent = name.toUpperCase();
      if (isHotspot) {{
        ttSub.innerHTML = `<span class="text-slate-300 font-bold">HIGHER CASELOAD HOTSPOT (${{count}} DOCKETS)</span>`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/60 transition-opacity duration-150";
      }} else if (count > 0) {{
        ttSub.innerHTML = `<span class="text-emerald-300 font-medium">Capital: ${{r ? r.capital : 'Ghana'}} • ${{count}} Dockets Active</span>`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150";
      }} else {{
        ttSub.textContent = `Capital: ${{r ? r.capital : 'Ghana'}} • 0 Active Cases`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-slate-400 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-slate-700/60 transition-opacity duration-150";
      }}

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

    // Setup Event Listeners
    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();
      if (isGlobusModeActive && typeof initOpenGlobusEngine === 'function') {{
        initOpenGlobusEngine();
      }}
      initOpenGlobusEngine();

      window.addEventListener('keydown', (e) => {{
        if (e.key === 'Escape' && isTacticalModeOpen) {{
          closeTacticalMode();
        }}
        if ((e.key === 't' || e.key === 'T') && !['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) {{
          if (isTacticalModeOpen) closeTacticalMode();
          else openTacticalMode();
        }}
      }});

      fetchRealtimeTelemetry();
      setInterval(fetchRealtimeTelemetry, 8000);
    }});
  </script>

</body>
</html>
'''

# List of all 5 dashboard files to update
targets = [
    {
        'file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'key': 'Evidence Management',
        'name': 'Evidence Management Unit',
        'subtitle': 'National Evidence Vault & Custody Directorate',
        'head': 'Chief Vault Custodian',
        'buttons': [
            {'title': 'Vault Overview', 'url': '{{ url("/Evidence_Management") }}', 'icon': 'package-search', 'active': True},
            {'title': 'Exhibit Lodgement (New)', 'url': '{{ url("/Evidence_Management/create") }}', 'icon': 'package-plus', 'active': False},
            {'title': 'Vault Case Directory', 'url': '{{ url("/cases?from=Evidence+Management") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Chain of Custody Logs', 'url': '{{ url("/history?unit=Evidence+Management") }}', 'icon': 'history', 'active': False},
            {'title': 'Vault Audit Report', 'url': '{{ url("/Report?unit=Evidence+Management") }}', 'icon': 'file-text', 'active': False},
        ],
        'kpi4_title': 'Evidence Vault Inventory',
        'kpi4_subtitle': 'Vault Custody Secured',
        'kpi4_metric': '{{ $totalExhibits }} Exhibits Logged',
        'kpi4_bays': [('BAY A', 'Narcotics'), ('BAY B', 'Devices'), ('BAY C', 'Weapons')],
        'kpi4_url': '{{ url("/Evidence_Management") }}',
        'kpi4_btn': 'Inspect'
    },
    {
        'file': 'resources/views/Forensic_Head/head.blade.php',
        'key': 'Forensic Head',
        'name': 'Forensic Directorate',
        'subtitle': 'Forensic Directorate Supreme Command',
        'head': 'Anita Addo',
        'buttons': [
            {'title': 'Executive Command', 'url': '{{ url("/Forensic_Head") }}', 'icon': 'layout-dashboard', 'active': True},
            {'title': 'Case Management', 'url': '{{ url("/Forensic_Head/cases") }}', 'icon': 'folder-kanban', 'active': False},
            {'title': 'Head Approvals', 'url': '{{ url("/Forensic_Head/approvals") }}', 'icon': 'clipboard-check', 'active': False},
            {'title': 'Master Case Directory', 'url': '{{ url("/cases") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Audit & Compliance', 'url': '{{ url("/Report") }}', 'icon': 'file-text', 'active': False},
        ],
        'kpi4_title': 'Directorate Case Portfolio',
        'kpi4_subtitle': 'Directorate Oversight',
        'kpi4_metric': '{{ $totalCases }} Dockets Active',
        'kpi4_bays': [('OPS', 'All Units'), ('16 REGIONS', 'Ghana Radar'), ('STATUS', 'Multi-Agency')],
        'kpi4_url': '{{ url("/Forensic_Head/cases") }}',
        'kpi4_btn': 'Dockets'
    },
    {
        'file': 'resources/views/Lab_Analysis/lab_analysis.blade.php',
        'key': 'Lab Analysis',
        'name': 'Lab Analysis Unit',
        'subtitle': 'Central Forensic Laboratory & Chemical Analysis',
        'head': 'Supt. Eric Mensah',
        'buttons': [
            {'title': 'Lab Command', 'url': '{{ url("/Lab_Analysis") }}', 'icon': 'flask-conical', 'active': True},
            {'title': 'Chemical Assay Intake', 'url': '{{ url("/Lab_Analysis/create") }}', 'icon': 'plus-circle', 'active': False},
            {'title': 'Analysis Results', 'url': '{{ url("/Lab_Analysis/lab-result") }}', 'icon': 'microscope', 'active': False},
            {'title': 'Lab Examination Directory', 'url': '{{ url("/cases?from=Lab+Analysis") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'GSA & Lab Certificates', 'url': '{{ url("/history?unit=Lab+Analysis") }}', 'icon': 'file-text', 'active': False},
        ],
        'kpi4_title': 'Chemical & Specimen Diagnostics',
        'kpi4_subtitle': 'Confirmatory Testing Active',
        'kpi4_metric': '{{ $totalCases }} Lab Tests',
        'kpi4_bays': [('GSA LAB', 'Drug Testing'), ('TOXICOLOGY', 'Specimens'), ('CHAIN', 'Verified')],
        'kpi4_url': '{{ url("/Lab_Analysis/examination") }}',
        'kpi4_btn': 'Exams'
    },
    {
        'file': 'resources/views/Crime_Scene_Support/head/head.blade.php',
        'key': 'Crime Scene Support',
        'name': 'Crime Scene Support Unit',
        'subtitle': 'Rapid Field Deployment & Incident Command',
        'head': 'Commander Samuel Atta',
        'buttons': [
            {'title': 'Incident Command', 'url': '{{ url("/Crime_Scene_Support/head") }}', 'icon': 'map-pin', 'active': True},
            {'title': 'Arrest & Seizure (New)', 'url': '{{ url("/Crime_Scene_Support/create") }}', 'icon': 'plus-circle', 'active': False},
            {'title': 'Field Case Directory', 'url': '{{ url("/cases?from=Crime+Scene+Support") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Field Deployment Logs', 'url': '{{ url("/history?unit=Crime+Scene+Support") }}', 'icon': 'crosshair', 'active': False},
            {'title': 'Incident Reports', 'url': '{{ url("/Report?unit=Crime+Scene+Support") }}', 'icon': 'file-text', 'active': False},
        ],
        'kpi4_title': 'Field Incident Operations',
        'kpi4_subtitle': 'Rapid Deployments',
        'kpi4_metric': '{{ $totalCases }} Field Deployments',
        'kpi4_bays': [('TEAM A', 'Field Raids'), ('TEAM B', 'Interception'), ('CHAIN', 'Evidence')],
        'kpi4_url': '{{ url("/Crime_Scene_Support/head") }}',
        'kpi4_btn': 'Deploy'
    },
    {
        'file': 'resources/views/Digital_Forensics/digital_forensics.blade.php',
        'key': 'Digital Forensics',
        'name': 'Digital & Physical Forensic Unit',
        'subtitle': 'Cyber Intelligence & Hardware Forensics',
        'head': 'Ofori Graham',
        'buttons': [
            {'title': 'Cyber Command', 'url': '{{ url("/Digital_Forensics") }}', 'icon': 'hard-drive', 'active': True},
            {'title': 'Device Intake (New)', 'url': '{{ url("/Digital_Forensics/create") }}', 'icon': 'laptop', 'active': False},
            {'title': 'Forensic & Investigation Status', 'url': '{{ url("/Digital_Forensics/forensic-status") }}', 'icon': 'cpu', 'active': False},
            {'title': 'Cyber Case Directory', 'url': '{{ url("/cases?from=Digital+Forensics") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Hardware & Hash Reports', 'url': '{{ url("/Report?unit=Digital+Forensics") }}', 'icon': 'file-text', 'active': False},
        ],
        'kpi4_title': 'Digital Artifacts & Devices',
        'kpi4_subtitle': 'Write-Block Acquisition',
        'kpi4_metric': '{{ $totalExhibits }} Devices Logged',
        'kpi4_bays': [('BAY D1', 'Mobile Devices'), ('BAY D2', 'Laptops & Media'), ('HASH', 'Verified')],
        'kpi4_url': '{{ url("/Digital_Forensics/forensic-status") }}',
        'kpi4_btn': 'Cyber Hub'
    }
]

for t in targets:
    content = generate_blade(
        t['key'], t['name'], t['subtitle'], t['head'],
        t['buttons'], t['kpi4_title'], t['kpi4_subtitle'], t['kpi4_metric'],
        t['kpi4_bays'], t['kpi4_url'], t['kpi4_btn']
    )
    with open(t['file'], 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Successfully generated: {t['file']}")

print("All dashboards compiled with Crystal-Clear Regional District Drill-Down & Red Hotspots!")
