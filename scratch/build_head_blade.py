import json
import os

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Generate region paths
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
                  class="region-polygon transition-all duration-300 cursor-pointer fill-slate-50/90 stroke-slate-300/80 hover:fill-violet-100/90 hover:stroke-violet-500 hover:drop-shadow-[0_4px_12px_rgba(124,58,237,0.25)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('{r['name']}')"
                  onmouseenter="onRegionHover(event, '{r['name']}')"
                  onmouseleave="onRegionLeave()" />''')

# Extruded 3D base depth paths
extruded_paths_markup = []
for r in regions:
    extruded_paths_markup.append(f'''<path d="{r['path']}" fill="#cbd5e1" opacity="0.85" />''')

blade_content = f'''@php
    $authUser = auth()->user();
    $userUnit = $authUser->unit ?? session('user_unit') ?? 'Crime Scene Support';
    $unitDisplayTitle = (str_contains(strtolower($userUnit), 'unit') || str_contains(strtolower($userUnit), 'directorate'))
        ? $userUnit 
        : $userUnit . ' Unit';
    $dbUnitHead = \\App\\Models\\User::where('unit', 'LIKE', '%' . $userUnit . '%')->where('is_head', true)->first()
               ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Unit Head');

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crime Scene Support Intelligence Dashboard | NACOC Forensic</title>
  
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
          spacing: {{
            '4.5': '1.125rem',
          }},
          boxShadow: {{
            'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            '2xs': '0 1px 1px 0 rgba(0, 0, 0, 0.05)',
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
    }}
    .font-mono-code {{
      font-family: 'JetBrains Mono', monospace;
    }}

    /* System Sidebar Dimension & Icon Guarantees */
    aside.w-64 {{
      width: 16rem !important;
      min-width: 16rem !important;
      max-width: 16rem !important;
      background-color: #f1f5f9 !important;
    }}
    aside svg.w-4\\.5, aside svg[class*="w-4.5"] {{
      width: 1.125rem !important;
      height: 1.125rem !important;
      min-width: 1.125rem !important;
      min-height: 1.125rem !important;
      flex-shrink: 0 !important;
    }}
    aside img {{
      max-width: 100%;
      height: auto;
    }}

    /* Sleek background inspired by modern 3D map dashboards */
    .dashboard-bg {{
      background: radial-gradient(circle at 50% 20%, #ffffff 0%, #f4f6fc 45%, #e9eef7 100%);
    }}

    /* 3D Map Perspective Stage */
    .map-3d-stage {{
      perspective: 1200px;
      perspective-origin: 50% 45%;
      transform-style: preserve-3d;
    }}
    .map-3d-plane {{
      transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), filter 0.3s ease;
      transform: rotateX(24deg) rotateZ(-3deg) translateY(-8px);
      transform-style: preserve-3d;
    }}
    .map-3d-plane.flat-view {{
      transform: rotateX(0deg) rotateZ(0deg) translateY(0px) scale(0.96) !important;
    }}

    /* Concentric Radar Rings Behind Map */
    .radar-ring {{
      border: 1px dashed rgba(168, 85, 247, 0.16);
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
    }}
    .radar-pulse {{
      animation: radarPulse 6s ease-out infinite;
    }}
    @keyframes radarPulse {{
      0% {{ transform: translate(-50%, -50%) scale(0.85); opacity: 0.7; }}
      50% {{ transform: translate(-50%, -50%) scale(1.05); opacity: 0.3; }}
      100% {{ transform: translate(-50%, -50%) scale(0.85); opacity: 0.7; }}
    }}

    /* Flowing flight connection line dash animation */
    @keyframes flowDash {{
      to {{
        stroke-dashoffset: -100;
      }}
    }}
    .flow-arc {{
      stroke-dasharray: 6, 6;
      animation: flowDash 3.5s linear infinite;
    }}

    /* Floating map badges */
    .floating-pin-badge {{
      transform-style: preserve-3d;
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
      backface-visibility: hidden;
    }}
    .floating-pin-badge:hover {{
      transform: translateY(-6px) scale(1.04);
      z-index: 40;
    }}

    /* Active region glow */
    .region-active {{
      fill: #8b5cf6 !important;
      stroke: #6d28d9 !important;
      stroke-width: 2.2 !important;
      filter: drop-shadow(0 8px 18px rgba(124, 58, 237, 0.45));
    }}

    /* Hexagon styling */
    .hex-node {{
      transition: all 0.25s ease;
    }}
    .hex-node:hover {{
      transform: scale(1.3);
      fill: #7c3aed;
    }}

    /* Custom thin scrollbar */
    ::-webkit-scrollbar {{
      width: 5px;
      height: 5px;
    }}
    ::-webkit-scrollbar-track {{
      background: transparent;
    }}
    ::-webkit-scrollbar-thumb {{
      background: #cbd5e1;
      border-radius: 9999px;
    }}
    ::-webkit-scrollbar-thumb:hover {{
      background: #94a3b8;
    }}
  </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen relative overflow-x-hidden selection:bg-purple-200 selection:text-purple-900">

  <!-- ========================================== -->
  <!-- SYSTEM-WIDE MAIN CONTAINER WITH SIDEBAR     -->
  <!-- ========================================== -->
  <div class="flex h-screen w-full overflow-hidden">
    
    <!-- NORMAL SYSTEM ASIDE BAR USED ACROSS DASHBOARDS -->
    @include('Crime_Scene_Support.partials.sidebar')

    <!-- ========================================== -->
    <!-- MAIN APPLICATION STAGE                     -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto dashboard-bg">

    <!-- Top Floating Header Bar -->
    <header class="px-8 py-4 flex items-center justify-between border-b border-slate-200/60 bg-white/40 backdrop-blur-md sticky top-0 z-20">
      
      <!-- Left: Breadcrumb / Unit Identification -->
      <div class="flex items-center space-x-3">
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider font-mono-code">
          <span class="text-purple-600">NACOC HQ</span>
          <span>/</span>
          <span>{{{{ $unitDisplayTitle }}}}</span>
          <span>/</span>
          <span class="text-slate-800 font-bold">3D Operational Theatre</span>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code bg-purple-100 text-purple-700 border border-purple-200">
          LIVE INTEL
        </span>
      </div>

      <!-- Center: Quick Region Switcher Search -->
      <div class="hidden md:flex items-center relative w-80">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none"></i>
        <input type="text" 
               id="regionSearchInput" 
               placeholder="Search 16 Ghana regions or capitals..." 
               oninput="filterRegionsSearch(this.value)"
               class="w-full pl-9 pr-4 py-2 bg-white/80 border border-slate-200 rounded-full text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all shadow-sm">
        <div id="searchDropdown" class="hidden absolute top-full left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-50 max-h-56 overflow-y-auto text-xs">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- Right: Real-Time Clock & Actions -->
      <div class="flex items-center space-x-5">
        <div class="text-right">
          <div id="liveClock" class="text-sm font-extrabold text-slate-900 font-mono-code leading-none">12:00:00 PM</div>
          <div id="liveDate" class="text-[11px] text-slate-500 mt-0.5 font-medium">Friday, October 16, 2026</div>
        </div>

        <a href="{{{{ url('/Crime_Scene_Support/staff/arrest-person') }}}}" class="hidden sm:inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold shadow-md shadow-purple-600/20 hover:from-purple-700 hover:to-indigo-700 transition-all space-x-1.5">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i>
          <span>Log Crime Scene</span>
        </a>
      </div>
    </header>

    <!-- Content Workspace Grid -->
    <div class="p-6 lg:p-8 grid grid-cols-12 gap-6 min-h-[calc(100vh-80px)]">

      <!-- ======================================== -->
      <!-- LEFT COLUMN: GENERAL STATS & BREAKDOWN   -->
      <!-- ======================================== -->
      <aside class="col-span-12 xl:col-span-3 flex flex-col justify-between space-y-6">
        
        <div class="space-y-6">
          <!-- Header Section -->
          <div>
            <h2 class="text-2xl font-black tracking-tight text-slate-900 flex items-center justify-between">
              <span>General statistics</span>
            </h2>
            <div class="flex items-center space-x-2 mt-1">
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">All Cases</span>
              <a href="{{{{ url('/cases') }}}}" class="text-[10px] font-bold text-purple-600 hover:text-purple-700 tracking-wider flex items-center font-mono-code">
                DETAIL <i data-lucide="chevron-right" class="w-3 h-3 ml-0.5"></i>
              </a>
            </div>
          </div>

          <!-- Hero Metric (Total Operations) -->
          <div class="bg-white/80 backdrop-blur-xl border border-white/80 rounded-3xl p-6 shadow-[0_10px_30px_rgba(30,41,59,0.04)]">
            <div class="text-4xl font-black text-slate-900 tracking-tight font-mono-code">
              7,541<span class="text-lg font-bold text-purple-600">,390</span>
            </div>
            
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
              <div>
                <div class="text-[11px] font-medium text-slate-400">Total Seizures Value</div>
                <div class="text-base font-extrabold text-slate-800 font-mono-code">$12,875,400</div>
              </div>
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold font-mono-code bg-emerald-50 text-emerald-600 border border-emerald-100">
                ▲ 14.2%
              </span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Compared to $11,240,000 previous cycle</div>

            <!-- Sleek Colorful Bar Sparklines (Cyan, Violet, Coral, Pink) matching inspiration image -->
            <div class="mt-5 h-20 w-full">
              <canvas id="categorySparkChart"></canvas>
            </div>
          </div>

          <!-- Category Breakdown List (Exact match to inspiration design) -->
          <div class="bg-white/80 backdrop-blur-xl border border-white/80 rounded-3xl p-6 shadow-[0_10px_30px_rgba(30,41,59,0.04)] space-y-3.5">
            <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">
              <span>Exhibit Discipline</span>
              <div class="flex space-x-6">
                <span>Active</span>
                <span>Seized</span>
              </div>
            </div>

            <div class="space-y-2.5 text-xs">
              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                  <span>Narcotics & Drugs</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">324</span>
                  <span class="text-slate-800">1,540 kg</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                  <span>Firearms & Ammo</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">311</span>
                  <span class="text-slate-800">1,320 rds</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                  <span>Digital Extractions</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">300</span>
                  <span class="text-slate-800">1,201 dev</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  <span>Currency & Bullion</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">288</span>
                  <span class="text-slate-800">989 pcs</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                  <span>Vehicles & Motors</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">276</span>
                  <span class="text-slate-800">944 units</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span>Chemical / Toxic</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">273</span>
                  <span class="text-slate-800">897 tests</span>
                </div>
              </div>

              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span>DNA & Bio-trace</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">222</span>
                  <span class="text-slate-800">843 swabs</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Floating Bottom-Left Summary Card (Matching inspiration image) -->
        <div class="bg-white/90 backdrop-blur-xl border border-white/90 rounded-3xl p-5 shadow-[0_15px_35px_rgba(0,0,0,0.05)] flex items-center justify-between">
          <div>
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Field Response Teams</div>
            <div class="text-xl font-black text-slate-800 font-mono-code">84 <span class="text-xs font-semibold text-slate-500">Teams Deployed</span></div>
            <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">● 98.4% Rapid Incident Coverage</div>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
          </div>
        </div>

      </aside>

      <!-- ======================================== -->
      <!-- CENTER: 3D CLICKABLE GHANA MAP (16 REG)  -->
      <!-- ======================================== -->
      <section class="col-span-12 xl:col-span-6 flex flex-col items-center justify-center relative min-h-[600px] lg:min-h-[720px] select-none">
        
        <!-- Concentric Radar / Orbital Rings centered behind Ghana -->
        <div class="radar-ring w-[680px] h-[680px]"></div>
        <div class="radar-ring w-[520px] h-[520px] radar-pulse"></div>
        <div class="radar-ring w-[360px] h-[360px]"></div>
        
        <!-- Subtle Coordinates Ring Ticks -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40">
          <svg class="w-[720px] h-[720px]" viewBox="0 0 800 800">
            <circle cx="400" cy="400" r="340" fill="none" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2, 8" />
            <circle cx="400" cy="400" r="260" fill="none" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="1, 5" />
            <!-- Meridian Line (Greenwich 0° passes right through Ghana) -->
            <line x1="400" y1="50" x2="400" y2="750" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="4, 6" />
            <text x="408" y="70" fill="#94a3b8" font-size="10" font-family="JetBrains Mono">0° PRIME MERIDIAN</text>
          </svg>
        </div>

        <!-- 3D Perspective Map Stage Container -->
        <div class="map-3d-stage relative w-full h-[620px] lg:h-[720px] flex items-center justify-center">
          
          <div id="ghana3dPlane" class="map-3d-plane relative w-full h-full max-w-[680px] flex items-center justify-center">
            
            <!-- SVG MAP: 16 REGIONS OF GHANA + 3D EXTRUSION + CONNECTION ARCS -->
            <svg id="ghanaMapSvg" 
                 viewBox="0 0 800 960" 
                 class="w-full h-full drop-shadow-[0_35px_45px_rgba(30,41,59,0.18)]"
                 style="overflow: visible;">
              
              <defs>
                <!-- 3D Shaded Top Surface Gradient -->
                <linearGradient id="reliefGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#ffffff" />
                  <stop offset="50%" stop-color="#f8fafc" />
                  <stop offset="100%" stop-color="#edf2f9" />
                </linearGradient>

                <!-- 3D Extrusion Side Wall Gradient -->
                <linearGradient id="wallGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="#cbd5e1" />
                  <stop offset="100%" stop-color="#94a3b8" />
                </linearGradient>

                <!-- Connection Arc Glow Filter -->
                <filter id="arcGlow" x="-20%" y="-20%" width="140%" height="140%">
                  <feDropShadow dx="0" dy="0" stdDeviation="3" flood-color="#8b5cf6" flood-opacity="0.6" />
                </filter>
              </defs>

              <!-- ==================================== -->
              <!-- 1. 3D Extrusion Depth Layer (Shadow) -->
              <!-- ==================================== -->
              <g id="extrusionBaseLayer" transform="translate(0, 16)">
                {"".join(extruded_paths_markup)}
              </g>

              <!-- ==================================== -->
              <!-- 2. Main Top Plate: 16 Regions of Ghana -->
              <!-- ==================================== -->
              <g id="regionsGroup">
                {"".join(region_paths_markup)}
              </g>

              <!-- ==================================== -->
              <!-- 3. Dynamic Connection Arcs (HQ to Hubs) -->
              <!-- ==================================== -->
              <g id="connectionArcsGroup" filter="url(#arcGlow)">
                <!-- Greater Accra (cx: 541, cy: 784) to Ashanti (Kumasi cx: 355, cy: 670) -->
                <path d="M 541,784 Q 440,710 355,670" fill="none" stroke="#7c3aed" stroke-width="2.2" class="flow-arc" opacity="0.85" />
                
                <!-- Greater Accra (cx: 541, cy: 784) to Northern (Tamale cx: 517, cy: 312) -->
                <path d="M 541,784 Q 580,540 517,312" fill="none" stroke="#6366f1" stroke-width="2.2" class="flow-arc" opacity="0.85" />

                <!-- Greater Accra (cx: 541, cy: 784) to Western (Takoradi cx: 227, cy: 869) -->
                <path d="M 541,784 Q 380,850 227,869" fill="none" stroke="#3b82f6" stroke-width="2.2" class="flow-arc" opacity="0.85" />

                <!-- Ashanti (Kumasi cx: 355, cy: 670) to Bono (Sunyani cx: 202, cy: 481) -->
                <path d="M 355,670 Q 260,570 202,481" fill="none" stroke="#8b5cf6" stroke-width="1.8" class="flow-arc" opacity="0.75" />

                <!-- Greater Accra (cx: 541, cy: 784) to Volta (Ho cx: 617, cy: 650) -->
                <path d="M 541,784 Q 590,730 617,650" fill="none" stroke="#a855f7" stroke-width="1.8" class="flow-arc" opacity="0.75" />

                <!-- Northern (Tamale cx: 517, cy: 312) to Upper East (Bolgatanga cx: 443, cy: 98) -->
                <path d="M 517,312 Q 490,200 443,98" fill="none" stroke="#ec4899" stroke-width="1.8" class="flow-arc" opacity="0.75" />
              </g>

              <!-- ==================================== -->
              <!-- 4. Clustered Hex Nodes over Hubs     -->
              <!-- ==================================== -->
              <g id="hexNodesGroup">
                <!-- Greater Accra Cluster -->
                <circle cx="541" cy="784" r="5" fill="#7c3aed" class="hex-node animate-pulse" />
                <circle cx="552" cy="776" r="3.5" fill="#a855f7" class="hex-node" />
                <circle cx="532" cy="792" r="3.5" fill="#6366f1" class="hex-node" />

                <!-- Kumasi Cluster -->
                <circle cx="355" cy="670" r="5" fill="#6366f1" class="hex-node animate-pulse" />
                <circle cx="344" cy="660" r="3.5" fill="#8b5cf6" class="hex-node" />
                <circle cx="366" cy="678" r="3.5" fill="#a855f7" class="hex-node" />

                <!-- Tamale Cluster -->
                <circle cx="517" cy="312" r="5" fill="#8b5cf6" class="hex-node animate-pulse" />
                <circle cx="528" cy="302" r="3.5" fill="#ec4899" class="hex-node" />
                
                <!-- Takoradi Cluster -->
                <circle cx="227" cy="869" r="5" fill="#0ea5e9" class="hex-node animate-pulse" />
                <circle cx="218" cy="858" r="3.5" fill="#3b82f6" class="hex-node" />
              </g>

            </svg>



          </div>

          <!-- Dynamic Floating Hover Tooltip -->
          <div id="regionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-900/90 text-white backdrop-blur-md px-3.5 py-2 rounded-xl text-xs shadow-2xl border border-slate-700/60 transition-opacity duration-150">
            <div id="tooltipRegionName" class="font-bold text-purple-300">Region Name</div>
            <div id="tooltipSub" class="text-[10px] text-slate-300">Capital: Kumasi • Click to inspect</div>
          </div>

        </div>

      </section>

      <!-- ======================================== -->
      <!-- RIGHT COLUMN: NATIONAL STATS & REGION HUD -->
      <!-- ======================================== -->
      <aside class="col-span-12 xl:col-span-3 flex flex-col justify-between space-y-6">
        
        <!-- Right Quick Controls Rail (Floating Pills matching inspiration image) -->
        <div class="flex items-center justify-end space-x-2">
          <div class="bg-white/80 backdrop-blur-md border border-white/90 shadow-sm rounded-full p-1.5 flex items-center space-x-1">
            <button type="button" onclick="zoomMap(1.15)" class="w-8 h-8 rounded-full hover:bg-purple-50 text-slate-600 hover:text-purple-600 flex items-center justify-center transition-colors text-sm font-bold" title="Zoom In">+</button>
            <button type="button" onclick="zoomMap(0.85)" class="w-8 h-8 rounded-full hover:bg-purple-50 text-slate-600 hover:text-purple-600 flex items-center justify-center transition-colors text-sm font-bold" title="Zoom Out">-</button>
            <button type="button" onclick="resetMapView()" class="w-8 h-8 rounded-full hover:bg-purple-50 text-slate-600 hover:text-purple-600 flex items-center justify-center transition-colors" title="Reset View">
              <i data-lucide="crosshair" class="w-4 h-4"></i>
            </button>
            <button type="button" onclick="toggle3DView()" id="btnToggle3D" class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center transition-colors" title="Toggle 3D Relief / 2D Flat Mode">
              <span class="text-[10px] font-black font-mono-code">3D</span>
            </button>
            <button type="button" onclick="toggleFlightArcs()" id="btnToggleArcs" class="w-8 h-8 rounded-full hover:bg-purple-50 text-slate-600 hover:text-purple-600 flex items-center justify-center transition-colors" title="Toggle Dispatch Arcs">
              <i data-lucide="git-branch" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <!-- Vertical National Counters (Matching inspiration image: 97,540 / 13,540 / 23,430 / 89%) -->
        <div class="bg-white/80 backdrop-blur-xl border border-white/80 rounded-3xl p-6 shadow-[0_10px_30px_rgba(30,41,59,0.04)] space-y-4">
          
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <div class="text-[11px] font-medium text-slate-400">All Dossiers</div>
              <div class="text-2xl font-black text-slate-900 font-mono-code">97,540</div>
            </div>
            <div class="p-2.5 rounded-2xl bg-purple-50 text-purple-600">
              <i data-lucide="archive" class="w-5 h-5"></i>
            </div>
          </div>

          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <div class="text-[11px] font-medium text-slate-400">Unique Suspects</div>
              <div class="text-2xl font-black text-slate-900 font-mono-code">13,540</div>
            </div>
            <div class="p-2.5 rounded-2xl bg-indigo-50 text-indigo-600">
              <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
          </div>

          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <div class="text-[11px] font-medium text-slate-400">Active Crime Scenes</div>
              <div class="text-2xl font-black text-slate-900 font-mono-code">23,430</div>
            </div>
            <div class="p-2.5 rounded-2xl bg-pink-50 text-pink-600">
              <i data-lucide="flame" class="w-5 h-5"></i>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <div class="text-[11px] font-medium text-slate-400">Resolution Trend</div>
              <div class="text-2xl font-black text-emerald-600 font-mono-code">89%</div>
            </div>
            <div class="p-2.5 rounded-2xl bg-emerald-50 text-emerald-600">
              <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
          </div>

        </div>

        <!-- Dynamic Selected Region Intelligence Card -->
        <div id="regionDetailCard" class="bg-gradient-to-b from-white via-white to-purple-50/40 backdrop-blur-xl border border-white/90 rounded-3xl p-6 shadow-[0_20px_40px_rgba(124,58,237,0.08)] relative overflow-hidden transition-all">
          
          <!-- Top Accent Light -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-purple-400/10 rounded-full blur-2xl pointer-events-none"></div>

          <div class="flex items-start justify-between mb-3 relative z-10">
            <div>
              <span id="cardRegionIso" class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code bg-purple-100 text-purple-700">GH-AA</span>
              <h3 id="cardRegionName" class="text-xl font-black text-slate-900 tracking-tight mt-1">Greater Accra Region</h3>
              <div id="cardRegionCapital" class="text-xs font-semibold text-slate-400 flex items-center mt-0.5">
                <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-purple-500"></i> Capital: Accra (National HQ)
              </div>
            </div>
            
            <span id="cardRegionStatus" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold font-mono-code bg-purple-600 text-white shadow-sm shadow-purple-600/30">
              HQ CENTRAL HUB
            </span>
          </div>

          <!-- Commander & Sector Personnel -->
          <div class="bg-white/80 border border-slate-100 rounded-2xl p-3 my-3 text-xs space-y-1">
            <div class="flex justify-between items-center">
              <span class="text-slate-400">Commanding Officer:</span>
              <span id="cardRegionCommander" class="font-bold text-slate-800">Chief Supt. K. Mensah</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-400">Dispatched Personnel:</span>
              <span id="cardRegionOfficers" class="font-mono-code font-bold text-purple-600">46 Field Agents</span>
            </div>
          </div>

          <!-- Key Region Seizure Grid -->
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
              <div class="text-[10px] text-slate-400">Narcotics Seized</div>
              <div id="cardRegionNarcotics" class="font-mono-code font-bold text-slate-800 text-sm">184.2 kg</div>
            </div>
            <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
              <div class="text-[10px] text-slate-400">Firearms Logged</div>
              <div id="cardRegionFirearms" class="font-mono-code font-bold text-slate-800 text-sm">28 units</div>
            </div>
            <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
              <div class="text-[10px] text-slate-400">Digital Devices</div>
              <div id="cardRegionDevices" class="font-mono-code font-bold text-slate-800 text-sm">94 items</div>
            </div>
            <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
              <div class="text-[10px] text-slate-400">Impounded Vehicles</div>
              <div id="cardRegionVehicles" class="font-mono-code font-bold text-slate-800 text-sm">16 vehicles</div>
            </div>
          </div>

          <!-- Direct Operational Actions -->
          <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col space-y-2">
            <a id="btnLogCrimeSceneRegion" 
               href="{{{{ url('/Crime_Scene_Support/staff/arrest-person') }}}}" 
               class="w-full py-2.5 px-4 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs flex items-center justify-center space-x-1.5 shadow-md shadow-purple-600/25 transition-all">
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              <span id="btnLogRegionLabel">Log Crime Scene in Greater Accra</span>
            </a>

            <a href="{{{{ url('/cases') }}}}" class="w-full py-2 px-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 flex items-center justify-center space-x-1.5 transition-colors">
              <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-500"></i>
              <span>View Regional Case Dossiers</span>
            </a>
          </div>

        </div>

      </aside>

    </div>

    <!-- Bottom Pill Navigation Tab Bar (Inspired by the prototype bottom footer) -->
    <footer class="flex justify-center pb-6 px-6">
      <nav class="bg-white/80 backdrop-blur-xl border border-white/90 shadow-lg shadow-purple-900/5 px-6 py-2.5 rounded-full flex items-center space-x-6 text-xs font-semibold">
        <a href="{{{{ url('/Crime_Scene_Support/head') }}}}" class="text-purple-600 font-bold border-b-2 border-purple-600 pb-0.5 flex items-center space-x-1">
          <i data-lucide="globe-2" class="w-3.5 h-3.5"></i>
          <span>Geospatial Theatre</span>
        </a>
        <a href="{{{{ url('/Crime_Scene_Support/staff/arrest-person') }}}}" class="text-slate-500 hover:text-purple-600 transition-colors flex items-center space-x-1">
          <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
          <span>Crime Scene Intake</span>
        </a>
        <a href="{{{{ url('/Crime_Scene_Support/staff') }}}}" class="text-slate-500 hover:text-purple-600 transition-colors flex items-center space-x-1">
          <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
          <span>Field Staff Stations</span>
        </a>
        <a href="{{{{ url('/Lab_Analysis') }}}}" class="text-slate-500 hover:text-purple-600 transition-colors flex items-center space-x-1">
          <i data-lucide="flask-conical" class="w-3.5 h-3.5"></i>
          <span>Forensic Laboratory</span>
        </a>
        <a href="{{{{ url('/cases') }}}}" class="text-slate-500 hover:text-purple-600 transition-colors flex items-center space-x-1">
          <i data-lucide="database" class="w-3.5 h-3.5"></i>
          <span>National Case Registry</span>
        </a>
      </nav>
    </footer>

    </div>
  </div>

  <!-- ========================================== -->
  <!-- JAVASCRIPT LOGIC & 3D INTERACTION          -->
  <!-- ========================================== -->
  <script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Live Clock & Date
    function updateLiveClock() {{
      const now = new Date();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12;
      const formattedHours = String(hours).padStart(2, '0');
      
      const clockEl = document.getElementById('liveClock');
      if (clockEl) clockEl.innerText = `${{formattedHours}}:${{minutes}}:${{seconds}} ${{ampm}}`;

      const options = {{ weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }};
      const dateEl = document.getElementById('liveDate');
      if (dateEl) dateEl.innerText = now.toLocaleDateString('en-US', options);
    }}
    setInterval(updateLiveClock, 1000);
    updateLiveClock();

    // 16 Regions Metadata Store
    const regionsDataset = {json.dumps({r['name']: r for r in regions})};

    let selectedRegionName = 'Greater Accra';
    let is3DMode = true;
    let isArcsVisible = true;
    let currentZoom = 1;

    // Select Region Function
    function selectRegionByName(name) {{
      const data = regionsDataset[name];
      if (!data) return;

      selectedRegionName = name;

      // Reset previous active styles
      document.querySelectorAll('.region-polygon').forEach(el => {{
        el.classList.remove('region-active');
      }});

      // Set active style on selected
      const targetId = 'path-' + name.toLowerCase().replace(/ /g, '-');
      const targetPath = document.getElementById(targetId);
      if (targetPath) {{
        targetPath.classList.add('region-active');
      }}

      // Update right-side Dossier Card
      document.getElementById('cardRegionIso').innerText = data.iso || 'GH-RG';
      document.getElementById('cardRegionName').innerText = data.name + ' Region';
      document.getElementById('cardRegionCapital').innerHTML = `<i data-lucide="map-pin" class="w-3 h-3 mr-1 text-purple-500"></i> Capital: ${{data.capital}}`;
      document.getElementById('cardRegionStatus').innerText = data.badge ? data.badge.toUpperCase() : 'ACTIVE SECTOR';
      document.getElementById('cardRegionCommander').innerText = data.commander;
      document.getElementById('cardRegionOfficers').innerText = `${{data.officers}} Field Agents`;
      document.getElementById('cardRegionNarcotics').innerText = data.narcotics;
      document.getElementById('cardRegionFirearms').innerText = `${{data.firearms}} units`;
      document.getElementById('cardRegionDevices').innerText = `${{data.devices}} items`;
      document.getElementById('cardRegionVehicles').innerText = `${{data.vehicles}} vehicles`;

      document.getElementById('btnLogRegionLabel').innerText = `Log Crime Scene in ${{data.name}}`;
      const btnLink = document.getElementById('btnLogCrimeSceneRegion');
      if (btnLink) {{
        btnLink.href = `{{{{ url('/Crime_Scene_Support/staff/arrest-person') }}}}?region=${{encodeURIComponent(data.name)}}`;
      }}

      // Refresh icons in card
      lucide.createIcons();
    }}

    // Hover Tooltip
    function onRegionHover(event, name) {{
      const data = regionsDataset[name];
      if (!data) return;

      const tooltip = document.getElementById('regionHoverTooltip');
      document.getElementById('tooltipRegionName').innerText = data.name + ' Region';
      document.getElementById('tooltipSub').innerText = `Capital: ${{data.capital}} • ${{data.activeOps}} Active Operations`;

      tooltip.classList.remove('hidden');
      
      const mapStage = document.querySelector('.map-3d-stage');
      const rect = mapStage.getBoundingClientRect();
      const x = event.clientX - rect.left + 15;
      const y = event.clientY - rect.top - 20;

      tooltip.style.left = `${{x}}px`;
      tooltip.style.top = `${{y}}px`;
    }}

    function onRegionLeave() {{
      const tooltip = document.getElementById('regionHoverTooltip');
      if (tooltip) tooltip.classList.add('hidden');
    }}

    // Zoom Controls
    function zoomMap(factor) {{
      currentZoom = Math.min(Math.max(0.65, currentZoom * factor), 1.8);
      applyMapTransform();
    }}

    function resetMapView() {{
      currentZoom = 1;
      is3DMode = true;
      const plane = document.getElementById('ghana3dPlane');
      plane.classList.remove('flat-view');
      document.getElementById('btnToggle3D').classList.add('bg-purple-100', 'text-purple-700');
      applyMapTransform();
      selectRegionByName('Greater Accra');
    }}

    function toggle3DView() {{
      is3DMode = !is3DMode;
      const plane = document.getElementById('ghana3dPlane');
      const btn = document.getElementById('btnToggle3D');
      if (is3DMode) {{
        plane.classList.remove('flat-view');
        btn.classList.add('bg-purple-100', 'text-purple-700');
      }} else {{
        plane.classList.add('flat-view');
        btn.classList.remove('bg-purple-100', 'text-purple-700');
      }}
      applyMapTransform();
    }}

    function toggleFlightArcs() {{
      isArcsVisible = !isArcsVisible;
      const group = document.getElementById('connectionArcsGroup');
      if (group) group.style.display = isArcsVisible ? 'block' : 'none';
      const btn = document.getElementById('btnToggleArcs');
      if (isArcsVisible) {{
        btn.classList.add('bg-purple-100', 'text-purple-700');
      }} else {{
        btn.classList.remove('bg-purple-100', 'text-purple-700');
      }}
    }}

    function applyMapTransform() {{
      const svg = document.getElementById('ghanaMapSvg');
      if (svg) {{
        svg.style.transform = `scale(${{currentZoom}})`;
        svg.style.transformOrigin = 'center center';
        svg.style.transition = 'transform 0.3s ease';
      }}
    }}

    // Mouse dynamic tilt tracking for realistic 3D feel
    const stage = document.querySelector('.map-3d-stage');
    const plane = document.getElementById('ghana3dPlane');
    if (stage && plane) {{
      stage.addEventListener('mousemove', (e) => {{
        if (!is3DMode) return;
        const rect = stage.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;
        
        const tiltX = 24 - (y * 14);
        const tiltZ = -3 + (x * 10);
        plane.style.transform = `rotateX(${{tiltX}}deg) rotateZ(${{tiltZ}}deg) translateY(-8px)`;
      }});

      stage.addEventListener('mouseleave', () => {{
        if (!is3DMode) return;
        plane.style.transform = 'rotateX(24deg) rotateZ(-3deg) translateY(-8px)';
      }});
    }}

    // Search input filter for 16 regions
    function filterRegionsSearch(query) {{
      const dropdown = document.getElementById('searchDropdown');
      if (!query || query.trim().length === 0) {{
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
        return;
      }}

      const q = query.toLowerCase().trim();
      const matches = Object.values(regionsDataset).filter(r => 
        r.name.toLowerCase().includes(q) || r.capital.toLowerCase().includes(q)
      );

      if (matches.length === 0) {{
        dropdown.innerHTML = '<div class="px-4 py-2 text-slate-400 text-xs">No matching region found</div>';
        dropdown.classList.remove('hidden');
        return;
      }}

      dropdown.innerHTML = matches.map(r => `
        <div onclick="selectRegionByName('${{r.name}}'); document.getElementById('searchDropdown').classList.add('hidden'); document.getElementById('regionSearchInput').value='';" 
             class="px-4 py-2 hover:bg-purple-50 cursor-pointer flex items-center justify-between transition-colors">
          <div class="font-bold text-slate-800">${{r.name}} <span class="text-slate-400 font-normal">(${{r.capital}})</span></div>
          <span class="text-[10px] font-mono-code font-bold text-purple-600">${{r.iso}}</span>
        </div>
      `).join('');
      dropdown.classList.remove('hidden');
    }}

    // Close search dropdown when clicking outside
    document.addEventListener('click', (e) => {{
      const searchBox = document.getElementById('regionSearchInput');
      const dropdown = document.getElementById('searchDropdown');
      if (searchBox && dropdown && !searchBox.contains(e.target) && !dropdown.contains(e.target)) {{
        dropdown.classList.add('hidden');
      }}
    }});

    // Initialize Default Selection
    selectRegionByName('Greater Accra');

    // Chart.js Category Sparkline (Cyan, Purple, Pink pill bars matching the inspiration image)
    const ctxSpark = document.getElementById('categorySparkChart');
    if (ctxSpark) {{
      new Chart(ctxSpark.getContext('2d'), {{
        type: 'bar',
        data: {{
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'],
          datasets: [{{
            data: [45, 68, 52, 90, 74, 85, 62, 95, 78, 88, 56, 72, 84, 92],
            backgroundColor: [
              '#38bdf8', '#818cf8', '#c084fc', '#f472b6', '#fb7185',
              '#818cf8', '#38bdf8', '#c084fc', '#f472b6', '#818cf8',
              '#38bdf8', '#c084fc', '#f472b6', '#a855f7'
            ],
            borderRadius: 6,
            barThickness: 6,
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
  </script>
</body>
</html>
'''

target_path = r'c:\xampp\htdocs\ForensicNacoc\resources\views\Crime_Scene_Support\head\head.blade.php'
with open(target_path, 'w', encoding='utf-8') as out:
    out.write(blade_content)

print(f"Successfully generated {target_path} ({len(blade_content)} characters).")
