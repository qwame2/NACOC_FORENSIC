import json
import os
import sys

# Import towns data
sys.path.append(os.path.dirname(__file__))
from generate_towns import towns_data

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

def build_dashboard(config):
    unit_slug = config['unit_slug']
    unit_name = config['unit_name']
    unit_code = config['unit_code']
    unit_title = config['unit_title']
    page_title = config['page_title']
    theme_accent = config['theme_accent']       # e.g. emerald, indigo, amber, blue, purple
    theme_hex = config['theme_hex']             # e.g. #059669
    sidebar_include = config['sidebar_include'] # 'Crime_Scene_Support.partials.sidebar' or 'Forensic_Head.partials.sidebar'
    quick_action_url = config['quick_action_url']
    quick_action_label = config['quick_action_label']
    quick_action_icon = config['quick_action_icon']
    hero_count = config['hero_count']
    hero_sub_label = config['hero_sub_label']
    hero_sub_val = config['hero_sub_val']
    hero_growth = config['hero_growth']
    discipline_title = config['discipline_title']
    categories = config['categories'] # list of (name, color, active, seized)
    counters = config['counters']     # list of (label, val, icon, color)
    dossier_m1 = config['dossier_m1']
    dossier_m2 = config['dossier_m2']
    dossier_m3 = config['dossier_m3']
    dossier_m4 = config['dossier_m4']
    dossier_action_label = config['dossier_action_label']
    dossier_action_url = config['dossier_action_url']
    dossier_action_icon = config['dossier_action_icon']
    footer_links = config['footer_links']
    php_head_block = config['php_head_block']

    # Generate crisp, high-visibility region paths markup
    region_paths = []
    for r in regions:
        reg_id = r['name'].lower().replace(' ', '-')
        region_paths.append(f'''
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
                  fill="#ffffff"
                  fill-opacity="0.95"
                  stroke="#94a3b8"
                  stroke-width="1.8"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  class="region-polygon transition-all duration-300 cursor-pointer hover:fill-{theme_accent}-100/90 hover:stroke-{theme_accent}-600 hover:drop-shadow-[0_8px_20px_rgba(0,0,0,0.16)]"
                  onclick="selectRegionByName('{r['name']}')"
                  onmouseenter="onRegionHover(event, '{r['name']}')"
                  onmouseleave="onRegionLeave()" />''')
    region_paths_markup = ''.join(region_paths)

    # 3D Double-pass extrusion depth layers (Shadow + Isometric Wall)
    extruded_pass1 = ''.join([f'''<path d="{r['path']}" fill="#94a3b8" opacity="0.65" />''' for r in regions])
    extruded_pass2 = ''.join([f'''<path d="{r['path']}" fill="#64748b" opacity="0.9" />''' for r in regions])

    # Direct Region Labels and Centroid Beacons for 100% clarity & visibility
    region_labels = []
    for r in regions:
        reg_id = r['name'].lower().replace(' ', '-')
        display_name = r['name'].upper()
        # Custom adjustments for compact areas
        font_size = "10.5px" if len(display_name) > 11 else "11.5px"
        region_labels.append(f'''
            <g id="label-{reg_id}" class="pointer-events-none select-none transition-all duration-200">
              <!-- Center Pulse Beacon -->
              <circle cx="{r['cx']}" cy="{r['cy'] - 9}" r="3.5" fill="{theme_hex}" stroke="#ffffff" stroke-width="1.6" filter="drop-shadow(0 1px 2px rgba(0,0,0,0.35))" />
              <!-- Region Name in Bold with White Text Outline -->
              <text x="{r['cx']}" y="{r['cy'] + 5}" 
                    text-anchor="middle" 
                    style="paint-order: stroke fill; stroke: #ffffff; stroke-width: 3.5px; stroke-linejoin: round; font-size: {font_size};" 
                    class="font-black font-sans fill-slate-900 tracking-wide drop-shadow-xs">{display_name}</text>
              <!-- Region Cases Count -->
              <text x="{r['cx']}" y="{r['cy'] + 17}" 
                    text-anchor="middle" 
                    style="paint-order: stroke fill; stroke: #ffffff; stroke-width: 2.8px; stroke-linejoin: round;" 
                    class="font-bold text-[9px] font-mono fill-slate-600 drop-shadow-xs">{r['cases']} Dockets</text>
            </g>''')
    region_labels_markup = ''.join(region_labels)

    categories_html = []
    for cat in categories:
        categories_html.append(f'''
              <div class="flex items-center justify-between hover:bg-slate-50/80 p-1 rounded-lg transition-colors cursor-pointer">
                <span class="flex items-center space-x-2 font-medium text-slate-700">
                  <span class="w-2 h-2 rounded-full bg-{cat['color']}-500"></span>
                  <span>{cat['name']}</span>
                </span>
                <div class="flex space-x-7 font-mono-code font-bold">
                  <span class="text-slate-400">{cat['active']}</span>
                  <span class="text-slate-800">{cat['metric']}</span>
                </div>
              </div>''')
    categories_markup = ''.join(categories_html)

    counters_html = []
    for idx, c in enumerate(counters):
        border_cls = "pb-2 border-b border-slate-100" if idx < len(counters) - 1 else ""
        counters_html.append(f'''
          <div class="flex items-center justify-between {border_cls}">
            <div>
              <div class="text-[10px] font-medium text-slate-400 leading-none">{c['label']}</div>
              <div class="text-xl font-black text-slate-900 font-mono-code mt-0.5 leading-tight">{c['val']}</div>
            </div>
            <div class="p-1.5 rounded-xl bg-{c['color']}-50 text-{c['color']}-600">
              <i data-lucide="{c['icon']}" class="w-4 h-4"></i>
            </div>
          </div>''')
    counters_markup = ''.join(counters_html)

    footer_links_html = []
    for fl in footer_links:
        active_cls = f"text-{theme_accent}-600 font-bold border-b-2 border-{theme_accent}-600 pb-0.5" if fl.get('active') else f"text-slate-500 hover:text-{theme_accent}-600 transition-colors"
        footer_links_html.append(f'''
        <a href="{fl['url']}" class="{active_cls} flex items-center space-x-1">
          <i data-lucide="{fl['icon']}" class="w-3.5 h-3.5"></i>
          <span>{fl['label']}</span>
        </a>''')
    footer_links_markup = ''.join(footer_links_html)

    blade = f'''{php_head_block}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{page_title}</title>
  
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

    /* High-contrast Sleek Canvas Background */
    .dashboard-bg {{
      background: radial-gradient(circle at 50% 30%, #ffffff 0%, #f1f5f9 55%, #e2e8f0 100%);
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
      border: 1px dashed rgba(148, 163, 184, 0.3);
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
      0% {{ transform: translate(-50%, -50%) scale(0.88); opacity: 0.7; }}
      50% {{ transform: translate(-50%, -50%) scale(1.06); opacity: 0.3; }}
      100% {{ transform: translate(-50%, -50%) scale(0.88); opacity: 0.7; }}
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

    /* Town Pin Markers & Animations */
    .town-marker {{
      transition: transform 0.22s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
    }}
    .town-marker:hover {{
      transform: scale(1.18);
    }}
    .town-marker:hover circle.town-beacon {{
      stroke-width: 2.8px;
      filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
    }}
    @keyframes townBeaconPulse {{
      0% {{ r: 5px; opacity: 0.8; }}
      50% {{ r: 15px; opacity: 0.15; }}
      100% {{ r: 5px; opacity: 0.8; }}
    }}
    .town-pulse-ring {{
      animation: townBeaconPulse 2.4s ease-in-out infinite;
    }}

    /* Continuous 3D-to-Floor Flattening & Smooth Focused Motion */
    .fade-on-region-focus {{
      transition: opacity 0.82s cubic-bezier(0.22, 1, 0.36, 1), 
                  filter 0.82s cubic-bezier(0.22, 1, 0.36, 1), 
                  transform 0.82s cubic-bezier(0.22, 1, 0.36, 1);
    }}
    .region-focus-active .fade-on-region-focus {{
      opacity: 0.10 !important;
      filter: blur(1.8px) !important;
      pointer-events: none !important;
      transform: scale(0.97) translateY(4px) !important;
    }}

    /* Map 3D Plane Smooth Lowering & Settling */
    .map-3d-plane {{
      transform-style: preserve-3d;
      will-change: transform;
      transition: transform 0.85s cubic-bezier(0.22, 1, 0.36, 1);
    }}
    #ghanaMapSvg {{
      transition: filter 0.85s cubic-bezier(0.22, 1, 0.36, 1);
    }}
    .region-focus-active #ghanaMapSvg {{
      filter: drop-shadow(0 10px 24px rgba(15, 23, 42, 0.12)) !important;
    }}

    /* Dim non-selected regions and non-selected labels */
    .region-focus-active .region-polygon:not(.region-active) {{
      fill: #f8fafc !important;
      fill-opacity: 0.22 !important;
      stroke: #cbd5e1 !important;
      stroke-width: 1.2px !important;
      stroke-opacity: 0.45 !important;
      filter: none !important;
      transition: fill 0.65s ease, fill-opacity 0.65s ease, stroke-opacity 0.65s ease;
    }}
    .region-focus-active #regionLabelsGroup g:not(.active-region-label) {{
      opacity: 0 !important;
      pointer-events: none;
      transition: opacity 0.45s ease;
    }}
    .region-focus-active #extrusionShadowPass,
    .region-focus-active #extrusionBasePass {{
      opacity: 0.12 !important;
      transition: opacity 0.8s ease;
    }}
    .region-focus-active #connectionArcsGroup {{
      opacity: 0 !important;
      transition: opacity 0.5s ease;
    }}
    .region-focus-active .radar-ring {{
      opacity: 0.12 !important;
      transition: opacity 0.8s ease;
    }}

    /* SVG Region Hover & Active States */
    .region-polygon {{
      transition: fill 0.25s ease, stroke 0.25s ease, filter 0.25s ease;
    }}
    .region-polygon.region-active {{
      fill: {theme_hex} !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(0, 0, 0, 0.36)) !important;
      opacity: 1 !important;
    }}
  </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen relative overflow-x-hidden selection:bg-{theme_accent}-200 selection:text-{theme_accent}-900">

  <!-- ========================================== -->
  <!-- SYSTEM-WIDE MAIN CONTAINER WITH SIDEBAR     -->
  <!-- ========================================== -->
  <div class="flex h-screen w-full overflow-hidden">
    
    <!-- NORMAL SYSTEM ASIDE BAR USED ACROSS DASHBOARDS -->
    @include('{sidebar_include}')

    <!-- ========================================== -->
    <!-- MAIN APPLICATION STAGE                     -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto dashboard-bg">

    <!-- Top Floating Header Bar -->
    <header class="px-6 py-2.5 flex items-center justify-between border-b border-slate-200/60 bg-white/40 backdrop-blur-md sticky top-0 z-20">
      
      <!-- Left: Breadcrumb / Unit Identification -->
      <div class="flex items-center space-x-3">
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider font-mono-code">
          <span class="text-{theme_accent}-600">NACOC HQ</span>
          <span>/</span>
          <span>{{{{ $unitDisplayTitle ?? '{unit_name}' }}}}</span>
          <span>/</span>
          <span class="text-slate-800 font-bold">3D Operational Theatre</span>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code bg-{theme_accent}-100 text-{theme_accent}-700 border border-{theme_accent}-200">
          {unit_code} LIVE INTEL
        </span>
      </div>

      <!-- Center: Quick Region Switcher Search -->
      <div class="hidden md:flex items-center relative w-80">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none"></i>
        <input type="text" 
               id="regionSearchInput" 
               placeholder="Search 16 Ghana regions or capitals..." 
               oninput="filterRegionsSearch(this.value)"
               class="w-full pl-9 pr-4 py-1.5 bg-white/80 border border-slate-200 rounded-full text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-{theme_accent}-500/20 focus:border-{theme_accent}-500 transition-all shadow-sm">
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

        <a href="{quick_action_url}" class="hidden sm:inline-flex items-center px-3.5 py-1.5 rounded-full bg-gradient-to-r from-{theme_accent}-600 to-{theme_accent}-700 text-white text-xs font-bold shadow-md shadow-{theme_accent}-600/20 hover:from-{theme_accent}-700 hover:to-{theme_accent}-800 transition-all space-x-1.5">
          <i data-lucide="{quick_action_icon}" class="w-3.5 h-3.5"></i>
          <span>{quick_action_label}</span>
        </a>
      </div>
    </header>

    <!-- Content Workspace Grid -->
    <div id="dashboardWorkspace" class="p-4 lg:p-5 grid grid-cols-12 gap-4 lg:gap-5 transition-all">

      <!-- ======================================== -->
      <!-- LEFT COLUMN: GENERAL STATS & BREAKDOWN   -->
      <!-- ======================================== -->
      <aside id="leftStatsColumn" class="col-span-12 xl:col-span-3 flex flex-col justify-start space-y-3 fade-on-region-focus">
        
        <!-- Header Section -->
        <div>
          <h2 class="text-xl font-black tracking-tight text-slate-900 flex items-center justify-between">
            <span>General statistics</span>
          </h2>
          <div class="flex items-center space-x-2 mt-0.5">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">All Cases</span>
            <a href="{{{{ url('/cases') }}}}?from={unit_name.replace(' ', '+')}" class="text-[10px] font-bold text-{theme_accent}-600 hover:text-{theme_accent}-700 tracking-wider flex items-center font-mono-code">
              DETAIL <i data-lucide="chevron-right" class="w-3 h-3 ml-0.5"></i>
            </a>
          </div>
        </div>

        <!-- Hero Metric Card -->
        <div class="bg-white/85 backdrop-blur-xl border border-white/80 rounded-2xl p-4 shadow-[0_10px_25px_rgba(30,41,59,0.04)]">
          <div class="text-3xl font-black text-slate-900 tracking-tight font-mono-code">
            {hero_count}
          </div>
          
          <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-medium text-slate-400 leading-none">{hero_sub_label}</div>
              <div class="text-sm font-extrabold text-slate-800 font-mono-code mt-0.5">{hero_sub_val}</div>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold font-mono-code bg-emerald-50 text-emerald-600 border border-emerald-100">
              ▲ {hero_growth}
            </span>
          </div>
          <div class="text-[10px] text-slate-400 mt-1">Real-time synchronized across 16 regional commands</div>

          <!-- Sleek Colorful Bar Sparklines -->
          <div class="mt-3 h-16 w-full">
            <canvas id="categorySparkChart"></canvas>
          </div>
        </div>

        <!-- Category Breakdown List -->
        <div class="bg-white/85 backdrop-blur-xl border border-white/80 rounded-2xl p-4 shadow-[0_10px_25px_rgba(30,41,59,0.04)] space-y-2">
          <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">
            <span>{discipline_title}</span>
            <div class="flex space-x-6">
              <span>Active</span>
              <span>Logged</span>
            </div>
          </div>

          <div class="space-y-1.5 text-xs">
            {categories_markup}
          </div>
        </div>

      </aside>

      <!-- ======================================== -->
      <!-- CENTER: 3D CLICKABLE GHANA MAP (16 REG)  -->
      <!-- ======================================== -->
      <section class="col-span-12 xl:col-span-6 flex flex-col items-center justify-center relative min-h-[560px] lg:min-h-[620px] xl:min-h-[660px] select-none">
        
        <!-- High-Tech Central Radial Glow for crisp map pop -->
        <div class="absolute w-[580px] h-[580px] rounded-full pointer-events-none opacity-40 blur-3xl bg-gradient-to-tr from-{theme_accent}-200/50 via-sky-100/35 to-transparent"></div>

        <!-- Concentric Radar / Orbital Rings centered behind Ghana -->
        <div class="radar-ring w-[640px] h-[640px]"></div>
        <div class="radar-ring w-[480px] h-[480px] radar-pulse"></div>
        <div class="radar-ring w-[340px] h-[340px]"></div>
        
        <!-- Subtle Coordinates Ring Ticks -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40 fade-on-region-focus">
          <svg class="w-[680px] h-[680px]" viewBox="0 0 800 800">
            <circle cx="400" cy="400" r="350" fill="none" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2, 8" />
            <circle cx="400" cy="400" r="270" fill="none" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="1, 5" />
            <line x1="400" y1="40" x2="400" y2="760" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="4, 6" />
            <text x="408" y="60" fill="#94a3b8" font-size="10" font-family="JetBrains Mono">0° PRIME MERIDIAN</text>
          </svg>
        </div>

        <!-- 3D Perspective Map Stage Container -->
        <div class="map-3d-stage relative w-full h-[540px] lg:h-[600px] xl:h-[640px] flex items-center justify-center">
          
          <div id="ghana3dPlane" class="map-3d-plane relative w-full h-full max-w-[680px] lg:max-w-[740px] xl:max-w-[780px] flex items-center justify-center">
            
            <!-- SVG MAP: 16 REGIONS OF GHANA + 3D EXTRUSION + CONNECTION ARCS + DIRECT LABELS -->
            <svg id="ghanaMapSvg" 
                 viewBox="0 0 800 960" 
                 class="w-full h-full drop-shadow-[0_30px_45px_rgba(15,23,42,0.20)]"
                 style="overflow: visible;">
              
              <defs>
                <linearGradient id="reliefGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#ffffff" />
                  <stop offset="50%" stop-color="#f8fafc" />
                  <stop offset="100%" stop-color="#edf2f9" />
                </linearGradient>

                <filter id="arcGlow" x="-20%" y="-20%" width="140%" height="140%">
                  <feDropShadow dx="0" dy="0" stdDeviation="3" flood-color="{theme_hex}" flood-opacity="0.6" />
                </filter>
              </defs>

              <!-- 1. 3D Extrusion Depth Layers (Shadow + Physical Wall) -->
              <g id="extrusionShadowPass" transform="translate(0, 20)" pointer-events="none">
                {extruded_pass2}
              </g>
              <g id="extrusionBasePass" transform="translate(0, 10)" pointer-events="none">
                {extruded_pass1}
              </g>

              <!-- 2. Main Top Plate: 16 Regions of Ghana -->
              <g id="regionsGroup">
                {region_paths_markup}
              </g>

              <!-- 3. Dynamic Connection Arcs (HQ to Hubs) -->
              <g id="connectionArcsGroup" filter="url(#arcGlow)" pointer-events="none">
                <path d="M 541,784 Q 440,710 355,670" fill="none" stroke="{theme_hex}" stroke-width="2.4" class="flow-arc" opacity="0.85" />
                <path d="M 541,784 Q 580,540 517,312" fill="none" stroke="{theme_hex}" stroke-width="2.4" class="flow-arc" opacity="0.85" />
                <path d="M 541,784 Q 380,850 227,869" fill="none" stroke="{theme_hex}" stroke-width="2.4" class="flow-arc" opacity="0.85" />
                <path d="M 355,670 Q 260,570 202,481" fill="none" stroke="{theme_hex}" stroke-width="2.0" class="flow-arc" opacity="0.75" />
                <path d="M 541,784 Q 590,730 617,650" fill="none" stroke="{theme_hex}" stroke-width="2.0" class="flow-arc" opacity="0.75" />
                <path d="M 517,312 Q 490,200 443,98" fill="none" stroke="{theme_hex}" stroke-width="2.0" class="flow-arc" opacity="0.75" />
              </g>

              <!-- 4. Direct Region Labels on Map (100% Readable & Prominent) -->
              <g id="regionLabelsGroup" pointer-events="none">
                {region_labels_markup}
              </g>

              <!-- 5. Active Detailed Town & Village Markers for Selected Region -->
              <g id="townMarkersGroup">
                <!-- Dynamically populated via renderTownMarkers(regionName) -->
              </g>

            </svg>

          </div>

          <!-- Active Focused Region Header Banner (Shown when region is clicked) -->
          <div id="regionActiveFocusBanner" class="hidden absolute top-3 left-4 z-20 flex items-center space-x-2.5 bg-slate-950/90 text-white backdrop-blur-md px-4 py-2 rounded-2xl text-xs shadow-2xl border border-slate-700/70 transition-all duration-300">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <div>
              <div class="flex items-center space-x-1.5">
                <span class="font-extrabold text-slate-100" id="focusRegionTitle">Greater Accra Region</span>
                <span class="text-slate-400">•</span>
                <span class="text-emerald-300 font-mono-code text-[11px]" id="focusTownsCount">7 Stations Active</span>
              </div>
              <div class="text-[9px] text-slate-400 font-medium">Flat Floor View • Focused Inspection</div>
            </div>
            <button type="button" onclick="resetMapView()" class="ml-2 pl-3 border-l border-slate-700 text-[10px] text-slate-300 hover:text-white flex items-center gap-1.5 font-bold hover:bg-slate-800/80 px-2 py-1 rounded-lg transition-colors" title="Lift map back up to 3D Theatre">
              <i data-lucide="rotate-ccw" class="w-3 h-3 text-{theme_accent}-400"></i>
              <span>3D Overview</span>
            </button>
          </div>

          <!-- Dynamic Floating Hover Tooltip -->
          <div id="regionHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-900/90 text-white backdrop-blur-md px-3.5 py-2 rounded-xl text-xs shadow-2xl border border-slate-700/60 transition-opacity duration-150">
            <div id="tooltipRegionName" class="font-bold text-{theme_accent}-300">Region Name</div>
            <div id="tooltipSub" class="text-[10px] text-slate-300">Capital: Kumasi • Click to inspect</div>
          </div>

          <!-- Dynamic Floating Town Tooltip -->
          <div id="townHoverTooltip" class="hidden absolute pointer-events-none z-50 bg-slate-950/95 text-white backdrop-blur-md p-3 rounded-xl text-xs shadow-2xl border border-slate-700/80 min-w-[210px] transition-opacity duration-150">
            <div class="flex items-center justify-between mb-1">
              <div id="townTooltipName" class="font-black text-sm text-{theme_accent}-300 tracking-tight">Accra Central</div>
              <span id="townTooltipStatus" class="px-1.5 py-0.5 rounded text-[8px] font-bold font-mono-code bg-{theme_accent}-500/20 text-{theme_accent}-300 border border-{theme_accent}-500/30 uppercase">HQ</span>
            </div>
            <div id="townTooltipType" class="text-[10px] text-slate-300 font-medium mb-2">National Headquarters</div>
            <div class="grid grid-cols-2 gap-1.5 pt-1.5 border-t border-slate-800 text-[10px]">
              <div class="bg-slate-900/80 px-2 py-1 rounded border border-slate-800">
                <span class="text-slate-400 block text-[8px]">ACTIVE DOCKETS</span>
                <span id="townTooltipCases" class="font-bold font-mono-code text-white">520</span>
              </div>
              <div class="bg-slate-900/80 px-2 py-1 rounded border border-slate-800">
                <span class="text-slate-400 block text-[8px]">FIELD OFFICERS</span>
                <span id="townTooltipOfficers" class="font-bold font-mono-code text-{theme_accent}-400">46 Officers</span>
              </div>
            </div>
          </div>

          <!-- Subtle Interactive Pan/Zoom Hint -->
          <div class="absolute bottom-3 left-4 z-20 pointer-events-none flex items-center space-x-2 bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-semibold text-slate-500 border border-slate-200/60 shadow-xs">
            <i data-lucide="move" class="w-3 h-3 text-{theme_accent}-500"></i>
            <span>Drag to pan map • Scroll to zoom</span>
          </div>

        </div>

      </section>

      <!-- ======================================== -->
      <!-- RIGHT COLUMN: NATIONAL STATS & REGION HUD -->
      <!-- ======================================== -->
      <aside class="col-span-12 xl:col-span-3 flex flex-col justify-start space-y-2.5">
        
        <!-- Right Quick Controls Rail -->
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5 font-mono-code">
            <i data-lucide="activity" class="w-3.5 h-3.5 text-{theme_accent}-500"></i>
            <span>TELEMETRY</span>
          </span>
          <div class="bg-white/85 backdrop-blur-md border border-slate-200/80 shadow-xs rounded-full p-1 flex items-center space-x-0.5">
            <button type="button" onclick="zoomMap(1.15)" class="w-7 h-7 rounded-full hover:bg-{theme_accent}-50 text-slate-600 hover:text-{theme_accent}-600 flex items-center justify-center transition-colors text-xs font-bold" title="Zoom In">+</button>
            <button type="button" onclick="zoomMap(0.85)" class="w-7 h-7 rounded-full hover:bg-{theme_accent}-50 text-slate-600 hover:text-{theme_accent}-600 flex items-center justify-center transition-colors text-xs font-bold" title="Zoom Out">-</button>
            <button type="button" onclick="resetMapView()" class="w-7 h-7 rounded-full hover:bg-{theme_accent}-50 text-slate-600 hover:text-{theme_accent}-600 flex items-center justify-center transition-colors" title="Reset View">
              <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" onclick="toggle3DView()" id="btnToggle3D" class="w-7 h-7 rounded-full bg-{theme_accent}-100 text-{theme_accent}-700 flex items-center justify-center transition-colors" title="Toggle 3D Relief / 2D Flat Mode">
              <span class="text-[9px] font-black font-mono-code">3D</span>
            </button>
            <button type="button" onclick="toggleFlightArcs()" id="btnToggleArcs" class="w-7 h-7 rounded-full hover:bg-{theme_accent}-50 text-slate-600 hover:text-{theme_accent}-600 flex items-center justify-center transition-colors" title="Toggle Dispatch Arcs">
              <i data-lucide="git-branch" class="w-3.5 h-3.5"></i>
            </button>
          </div>
        </div>

        <!-- Vertical National Counters -->
        <div id="telemetryCountersCard" class="bg-white/85 backdrop-blur-xl border border-white/80 rounded-2xl p-3.5 lg:p-4 shadow-[0_10px_25px_rgba(30,41,59,0.04)] space-y-2 fade-on-region-focus">
          {counters_markup}
        </div>

        <!-- Dynamic Selected Region Intelligence Card -->
        <div id="regionDetailCard" class="bg-gradient-to-b from-white via-white to-{theme_accent}-50/40 backdrop-blur-xl border border-white/90 rounded-2xl p-3.5 lg:p-4 shadow-[0_15px_30px_rgba(0,0,0,0.05)] relative overflow-hidden transition-all">
          
          <div class="absolute top-0 right-0 w-28 h-28 bg-{theme_accent}-400/10 rounded-full blur-2xl pointer-events-none"></div>

          <div class="flex items-start justify-between mb-2 relative z-10">
            <div>
              <span id="cardRegionIso" class="px-2 py-0.5 rounded-full text-[9px] font-bold font-mono-code bg-{theme_accent}-100 text-{theme_accent}-700">GH-AA</span>
              <h3 id="cardRegionName" class="text-base font-black text-slate-900 tracking-tight mt-0.5 leading-snug">Greater Accra Region</h3>
              <div id="cardRegionCapital" class="text-[11px] font-semibold text-slate-400 flex items-center mt-0.5">
                <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-{theme_accent}-500"></i> Capital: Accra (National HQ)
              </div>
            </div>
            
            <span id="cardRegionStatus" class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold font-mono-code bg-{theme_accent}-600 text-white shadow-xs">
              HQ CENTRAL HUB
            </span>
          </div>

          <!-- Commander & Sector Personnel -->
          <div class="bg-white/90 border border-slate-100 rounded-xl p-2.5 my-2 text-[11px] space-y-1">
            <div class="flex justify-between items-center">
              <span class="text-slate-400">Sector Commander:</span>
              <span id="cardRegionCommander" class="font-bold text-slate-800">Chief Supt. K. Mensah</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-400">Dispatched Personnel:</span>
              <span id="cardRegionOfficers" class="font-mono-code font-bold text-{theme_accent}-600">46 Field Officers</span>
            </div>
          </div>

          <!-- Key Region Data Grid -->
          <div class="grid grid-cols-2 gap-1.5 text-xs">
            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
              <div class="text-[9px] text-slate-400">{dossier_m1}</div>
              <div id="cardRegionNarcotics" class="font-mono-code font-bold text-slate-800 text-xs">184.2 kg</div>
            </div>
            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
              <div class="text-[9px] text-slate-400">{dossier_m2}</div>
              <div id="cardRegionFirearms" class="font-mono-code font-bold text-slate-800 text-xs">28 units</div>
            </div>
            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
              <div class="text-[9px] text-slate-400">{dossier_m3}</div>
              <div id="cardRegionDevices" class="font-mono-code font-bold text-slate-800 text-xs">94 items</div>
            </div>
            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
              <div class="text-[9px] text-slate-400">{dossier_m4}</div>
              <div id="cardRegionVehicles" class="font-mono-code font-bold text-slate-800 text-xs">16 vehicles</div>
            </div>
        </div>

        <!-- Dynamic Selected Region Towns & Stations Outposts Card -->
        <div id="regionTownsCard" class="bg-white/85 backdrop-blur-xl border border-white/80 rounded-2xl p-3.5 lg:p-4 shadow-[0_10px_25px_rgba(30,41,59,0.04)] space-y-2 transition-all">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
              <i data-lucide="map-pin" class="w-3.5 h-3.5 text-{theme_accent}-600"></i>
              <span class="text-xs font-bold text-slate-800">Towns & Active Outposts</span>
            </div>
            <span id="cardTownsCount" class="text-[9px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-{theme_accent}-100 text-{theme_accent}-700">7 Stations</span>
          </div>
          <div id="regionTownsList" class="space-y-1.5 max-h-[175px] overflow-y-auto pr-1">
            <!-- Dynamically populated per region -->
          </div>
        </div>

      </aside>

    </div>

    </div>
  </div>

  <!-- ========================================== -->
  <!-- JAVASCRIPT LOGIC & 3D INTERACTION          -->
  <!-- ========================================== -->
  <script>
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

    // 16 Regions Dataset
    const regionsDataset = {json.dumps({r['name']: r for r in regions})};
    
    // 16 Regions Towns Dataset
    const townsDataset = {json.dumps(towns_data)};

    let selectedRegionName = 'Greater Accra';
    let is3DMode = true;
    let isArcsVisible = true;
    let currentZoom = 1;
    let panX = 0;
    let panY = 0;
    let rotX = 13;
    let rotZ = -1.5;
    let hasDragged = false;

    // Apply combined pan, 3D tilt, and zoom to map plane with smooth physics-grade easing
    function updateMapTransform(animate = false, duration = 400) {{
      const plane = document.getElementById('ghana3dPlane');
      if (!plane) return;

      if (animate) {{
        plane.style.transition = `transform ${{duration}}ms cubic-bezier(0.22, 1, 0.36, 1)`;
      }} else {{
        plane.style.transition = 'none';
      }}

      const rx = is3DMode ? rotX : 0;
      const rz = is3DMode ? rotZ : 0;
      const baseScale = is3DMode ? 1.08 : 1.04;
      const scale = (currentZoom * baseScale).toFixed(3);

      plane.style.transform = `translate3d(${{panX}}px, ${{panY}}px, 0px) rotateX(${{rx}}deg) rotateZ(${{rz}}deg) scale(${{scale}})`;
    }}

    // Render Detailed Town Markers for Selected Region
    function renderTownMarkers(regionName) {{
      const container = document.getElementById('townMarkersGroup');
      if (!container) return;
      container.innerHTML = '';

      const towns = townsDataset[regionName] || [];
      const banner = document.getElementById('regionActiveFocusBanner');
      const bannerTitle = document.getElementById('focusRegionTitle');
      const bannerCount = document.getElementById('focusTownsCount');

      if (banner && bannerTitle && bannerCount) {{
        const workspace = document.getElementById('dashboardWorkspace');
        const isFocused = workspace && workspace.classList.contains('region-focus-active');
        if (isFocused && towns.length > 0) {{
          bannerTitle.innerText = regionName + ' Region';
          bannerCount.innerText = `${{towns.length}} Towns & Stations`;
          banner.classList.remove('hidden');
        }} else {{
          banner.classList.add('hidden');
        }}
      }}

      // Populate HUD card list as well
      const hudCount = document.getElementById('cardTownsCount');
      if (hudCount) hudCount.innerText = `${{towns.length}} Outposts`;

      const hudList = document.getElementById('regionTownsList');
      if (hudList) {{
        if (towns.length === 0) {{
          hudList.innerHTML = `<div class="text-center py-3 text-xs text-slate-400">No station outposts recorded</div>`;
        }} else {{
          hudList.innerHTML = towns.map((t, idx) => `
            <div onclick="focusSpecificTown('${{regionName}}', ${{idx}})" 
                 class="group p-2 rounded-xl bg-slate-50/80 hover:bg-{theme_accent}-50/80 border border-slate-100 hover:border-{theme_accent}-200 transition-all cursor-pointer flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-{theme_accent}-600 group-hover:bg-{theme_accent}-600 group-hover:text-white transition-colors">
                  <i data-lucide="map-pin" class="w-3 h-3"></i>
                </div>
                <div>
                  <div class="text-xs font-bold text-slate-800 leading-tight group-hover:text-{theme_accent}-800">${{t.name}}</div>
                  <div class="text-[9px] text-slate-400 truncate max-w-[130px]">${{t.type}}</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-[10px] font-bold font-mono-code text-slate-700">${{t.cases}} Dockets</div>
                <span class="inline-block text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded bg-slate-200/70 text-slate-600 font-mono-code">${{t.status}}</span>
              </div>
            </div>
          `).join('');
        }}
        lucide.createIcons();
      }}

      // Render SVG markers
      towns.forEach((t, idx) => {{
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'town-marker');
        g.setAttribute('id', `town-pin-${{idx}}`);
        g.style.cursor = 'pointer';

        // Label dimensions
        const labelW = Math.max(t.name.length * 6.2 + 16, 54);
        const labelH = 15;
        const labelX = t.x - labelW / 2;
        const labelY = t.y - 23;

        g.innerHTML = `
          <!-- Pulse Ring -->
          <circle cx="${{t.x}}" cy="${{t.y}}" r="8" fill="{theme_hex}" opacity="0.25" class="town-pulse-ring" pointer-events="none" />
          
          <!-- Town Center Beacon -->
          <circle class="town-beacon" cx="${{t.x}}" cy="${{t.y}}" r="4.8" fill="{theme_hex}" stroke="#ffffff" stroke-width="1.8" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.35))" />
          <circle cx="${{t.x}}" cy="${{t.y}}" r="1.6" fill="#ffffff" pointer-events="none" />
          
          <!-- Town Name Pill -->
          <rect x="${{labelX}}" y="${{labelY}}" width="${{labelW}}" height="${{labelH}}" rx="4.5" fill="#0f172a" fill-opacity="0.92" stroke="#ffffff" stroke-width="1.2" filter="drop-shadow(0 3px 6px rgba(0,0,0,0.25))" />
          <text x="${{t.x}}" y="${{labelY + 10.5}}" text-anchor="middle" fill="#ffffff" font-size="8.5" font-weight="700" font-family="'JetBrains Mono', monospace" letter-spacing="0.2px" pointer-events="none">${{t.name}}</text>
          
          <!-- Docket count below beacon -->
          <text x="${{t.x}}" y="${{t.y + 13}}" text-anchor="middle" fill="#0f172a" stroke="#ffffff" stroke-width="2.6" style="paint-order: stroke fill;" font-size="8" font-weight="800" font-family="'JetBrains Mono', monospace" pointer-events="none">${{t.cases}} Dockets</text>
        `;

        g.addEventListener('mouseenter', (e) => onTownHover(e, t));
        g.addEventListener('mouseleave', onTownLeave);
        g.addEventListener('click', (e) => {{
          e.stopPropagation();
          focusSpecificTown(regionName, idx);
        }});

        container.appendChild(g);
      }});
    }}

    function focusSpecificTown(regionName, townIdx) {{
      const towns = townsDataset[regionName] || [];
      const t = towns[townIdx];
      if (!t) return;

      const centerX = 400;
      const centerY = 480;
      panX = Math.round((centerX - t.x) * 1.25);
      panY = Math.round((centerY - t.y) * 1.25);
      currentZoom = 2.0;
      updateMapTransform(true, 500);

      const pin = document.getElementById(`town-pin-${{townIdx}}`);
      if (pin) {{
        pin.style.transform = 'scale(1.4)';
        setTimeout(() => {{
          pin.style.transform = 'scale(1)';
        }}, 450);
      }}
    }}

    function onTownHover(event, town) {{
      if (hasDragged) return;
      const tooltip = document.getElementById('townHoverTooltip');
      if (!tooltip) return;

      document.getElementById('townTooltipName').innerText = town.name;
      document.getElementById('townTooltipType').innerText = town.type;
      document.getElementById('townTooltipStatus').innerText = town.status;
      document.getElementById('townTooltipCases').innerText = `${{town.cases}} Dockets`;
      document.getElementById('townTooltipOfficers').innerText = `${{town.officers}} Officers`;

      tooltip.classList.remove('hidden');

      const stageRect = document.querySelector('.map-3d-stage').getBoundingClientRect();
      const x = event.clientX - stageRect.left + 15;
      const y = event.clientY - stageRect.top + 15;

      tooltip.style.left = x + 'px';
      tooltip.style.top = y + 'px';
    }}

    function onTownLeave() {{
      const tooltip = document.getElementById('townHoverTooltip');
      if (tooltip) tooltip.classList.add('hidden');
    }}

    let lastSelectTime = 0;
    function selectRegionByName(name, shouldZoom = true) {{
      if (hasDragged) return; // Ignore selection if user was dragging map
      const now = Date.now();
      if (now - lastSelectTime < 60 && selectedRegionName === name) return;
      lastSelectTime = now;
      const data = regionsDataset[name];
      if (!data) return;

      selectedRegionName = name;

      // 1. Highlight active polygon with high-contrast accent
      document.querySelectorAll('.region-polygon').forEach(el => {{
        el.classList.remove('region-active');
      }});

      const targetId = 'path-' + name.toLowerCase().replace(/ /g, '-');
      const targetPath = document.getElementById(targetId);
      if (targetPath) {{
        targetPath.classList.add('region-active');
      }}

      // 2. Isolate active region label so other labels fade out cleanly
      const activeLabelId = 'label-' + name.toLowerCase().replace(/ /g, '-');
      document.querySelectorAll('#regionLabelsGroup g').forEach(el => {{
        if (el.id === activeLabelId) {{
          el.classList.add('active-region-label');
        }} else {{
          el.classList.remove('active-region-label');
        }}
      }});

      // 3. Update right-side Dossier Card with live operational data
      document.getElementById('cardRegionIso').innerText = data.iso || 'GH-RG';
      document.getElementById('cardRegionName').innerText = data.name + ' Region';
      document.getElementById('cardRegionCapital').innerHTML = `<i data-lucide="map-pin" class="w-3 h-3 mr-1 text-{theme_accent}-500"></i> Capital: ${{data.capital}}`;
      document.getElementById('cardRegionStatus').innerText = data.badge ? data.badge.toUpperCase() : 'ACTIVE SECTOR';
      document.getElementById('cardRegionCommander').innerText = data.commander;
      document.getElementById('cardRegionOfficers').innerText = `${{data.officers}} Field Officers`;
      document.getElementById('cardRegionNarcotics').innerText = data.narcotics;
      document.getElementById('cardRegionFirearms').innerText = `${{data.firearms}} units`;
      document.getElementById('cardRegionDevices').innerText = `${{data.devices}} items`;
      document.getElementById('cardRegionVehicles').innerText = `${{data.vehicles}} vehicles`;

      // 4. Continuous 3D-to-Floor Flattening & Smooth Focus Motion
      if (shouldZoom) {{
        // Activate focused workspace state (fades out surrounding elements smoothly)
        const workspace = document.getElementById('dashboardWorkspace');
        if (workspace) workspace.classList.add('region-focus-active');

        // Render detailed town markers and unhide active focus banner
        renderTownMarkers(name);

        // Flatten map onto floor (tilt down: rotX -> 0, rotZ -> 0, flat 2D perspective)
        rotX = 0;
        rotZ = 0;
        is3DMode = false;

        // Update 3D toggle indicator button
        const btn3D = document.getElementById('btnToggle3D');
        if (btn3D) {{
          btn3D.classList.remove('bg-{theme_accent}-100', 'text-{theme_accent}-700');
          btn3D.classList.add('bg-white', 'text-slate-600');
          btn3D.querySelector('span').innerText = '2D';
        }}

        // Center on the selected region's centroid
        const centerX = 400;
        const centerY = 480;
        currentZoom = 1.62;
        panX = Math.round((centerX - data.cx) * 0.95);
        panY = Math.round((centerY - data.cy) * 0.95);

        // Continuous smooth transition with coordinated physics-grade easing
        updateMapTransform(true, 850);
      }} else {{
        renderTownMarkers(name);
      }}

      lucide.createIcons();
    }}

    // Region Hover Tooltip Handlers
    function onRegionHover(event, name) {{
      if (hasDragged) return;
      const data = regionsDataset[name];
      if (!data) return;

      const tooltip = document.getElementById('regionHoverTooltip');
      if (!tooltip) return;

      document.getElementById('tooltipRegionName').innerText = data.name + ' Region';
      document.getElementById('tooltipSub').innerText = `Capital: ${{data.capital}} • ${{data.cases}} Dockets Logged`;

      tooltip.classList.remove('hidden');
      
      const stageRect = document.querySelector('.map-3d-stage').getBoundingClientRect();
      const x = event.clientX - stageRect.left + 15;
      const y = event.clientY - stageRect.top + 15;
      
      tooltip.style.left = x + 'px';
      tooltip.style.top = y + 'px';
    }}

    function onRegionLeave() {{
      const tooltip = document.getElementById('regionHoverTooltip');
      if (tooltip) tooltip.classList.add('hidden');
    }}

    // Map View Controls (3D Toggle, Zoom, Arcs)
    function toggle3DView() {{
      const btn = document.getElementById('btnToggle3D');
      is3DMode = !is3DMode;

      if (is3DMode) {{
        rotX = 13;
        rotZ = -1.5;
        if (btn) {{
          btn.classList.add('bg-{theme_accent}-100', 'text-{theme_accent}-700');
          btn.classList.remove('bg-white', 'text-slate-600');
          btn.querySelector('span').innerText = '3D';
        }}
      }} else {{
        rotX = 0;
        rotZ = 0;
        if (btn) {{
          btn.classList.remove('bg-{theme_accent}-100', 'text-{theme_accent}-700');
          btn.classList.add('bg-white', 'text-slate-600');
          btn.querySelector('span').innerText = '2D';
        }}
      }}
      updateMapTransform(true, 650);
    }}

    function toggleFlightArcs() {{
      const arcsGroup = document.getElementById('connectionArcsGroup');
      const btn = document.getElementById('btnToggleArcs');
      isArcsVisible = !isArcsVisible;

      if (isArcsVisible) {{
        arcsGroup.style.display = 'block';
        btn.classList.add('text-{theme_accent}-600', 'bg-{theme_accent}-50');
      }} else {{
        arcsGroup.style.display = 'none';
        btn.classList.remove('text-{theme_accent}-600', 'bg-{theme_accent}-50');
      }}
    }}

    function zoomMap(factor) {{
      currentZoom = Math.min(Math.max(currentZoom * factor, 0.55), 3.0);
      updateMapTransform(true, 300);
    }}

    function resetMapView() {{
      // 1. Remove focused workspace state so surrounding containers smoothly fade back in
      const workspace = document.getElementById('dashboardWorkspace');
      if (workspace) workspace.classList.remove('region-focus-active');

      // 2. Hide active focus banner
      const banner = document.getElementById('regionActiveFocusBanner');
      if (banner) banner.classList.add('hidden');

      // 3. Clear town markers & restore all region labels
      const townGroup = document.getElementById('townMarkersGroup');
      if (townGroup) townGroup.innerHTML = '';

      document.querySelectorAll('#regionLabelsGroup g').forEach(el => {{
        el.classList.remove('active-region-label');
      }});

      // 4. Smoothly lift map back up from floor into 3D theater elevation
      currentZoom = 1;
      panX = 0;
      panY = 0;
      rotX = 13;
      rotZ = -1.5;
      is3DMode = true;

      const btn = document.getElementById('btnToggle3D');
      if (btn) {{
        btn.classList.add('bg-{theme_accent}-100', 'text-{theme_accent}-700');
        btn.classList.remove('bg-white', 'text-slate-600');
        btn.querySelector('span').innerText = '3D';
      }}

      updateMapTransform(true, 850);
      selectRegionByName('Greater Accra', false);
    }}

    // Autocomplete Region Search
    function filterRegionsSearch(query) {{
      const dropdown = document.getElementById('searchDropdown');
      if (!query || query.trim() === '') {{
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
        return;
      }}

      const q = query.toLowerCase().trim();
      const matches = Object.values(regionsDataset).filter(r => 
        r.name.toLowerCase().includes(q) || r.capital.toLowerCase().includes(q)
      );

      if (matches.length === 0) {{
        dropdown.innerHTML = '<div class="px-4 py-2 text-slate-400">No matching region found</div>';
        dropdown.classList.remove('hidden');
        return;
      }}

      dropdown.innerHTML = matches.map(r => `
        <div onclick="selectRegionByName('${{r.name}}'); document.getElementById('searchDropdown').classList.add('hidden'); document.getElementById('regionSearchInput').value='';" 
             class="px-4 py-2 hover:bg-{theme_accent}-50 cursor-pointer flex items-center justify-between text-slate-700 hover:text-{theme_accent}-700">
          <span class="font-bold">${{r.name}} Region</span>
          <span class="text-[10px] text-slate-400 font-mono-code">${{r.capital}}</span>
        </div>
      `).join('');
      dropdown.classList.remove('hidden');
    }}

    // Close search dropdown on click outside
    document.addEventListener('click', function(e) {{
      const searchBox = document.getElementById('regionSearchInput');
      const dropdown = document.getElementById('searchDropdown');
      if (searchBox && dropdown && !searchBox.contains(e.target) && !dropdown.contains(e.target)) {{
        dropdown.classList.add('hidden');
      }}
    }});

    // Drag & Pan Mouse / Pointer Interaction (Smooth Pan with True Click Discrimination)
    const stage = document.querySelector('.map-3d-stage');
    const plane = document.getElementById('ghana3dPlane');
    const svgMap = document.getElementById('ghanaMapSvg');

    if (stage && plane) {{
      let isPointerDown = false;
      let startClientX = 0;
      let startClientY = 0;
      let initialPanX = 0;
      let initialPanY = 0;
      const DRAG_THRESHOLD = 10; // Must move at least 10px to count as drag

      stage.addEventListener('pointerdown', (e) => {{
        // Only trigger on primary button (left-click) or touch
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        
        isPointerDown = true;
        hasDragged = false;
        startClientX = e.clientX;
        startClientY = e.clientY;
        initialPanX = panX;
        initialPanY = panY;
      }});

      window.addEventListener('pointermove', (e) => {{
        if (!isPointerDown) {{
          // If not dragging, subtle 3D tilt tracking cursor when hovering stage
          if (is3DMode) {{
            const rect = stage.getBoundingClientRect();
            if (e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom) {{
              const x = e.clientX - rect.left - rect.width / 2;
              const y = e.clientY - rect.top - rect.height / 2;
              rotX = 13 - (y / rect.height) * 8;
              rotZ = -1.5 + (x / rect.width) * 8;
              updateMapTransform(false);
            }}
          }}
          return;
        }}

        const deltaX = e.clientX - startClientX;
        const deltaY = e.clientY - startClientY;
        const distance = Math.hypot(deltaX, deltaY);

        // Only start pan movement and flag as drag if moved beyond threshold
        if (distance > DRAG_THRESHOLD) {{
          hasDragged = true;
          stage.classList.add('is-dragging');
          onRegionLeave();
          panX = initialPanX + deltaX;
          panY = initialPanY + deltaY;
          updateMapTransform(false);
        }}
      }});

      const onPointerEnd = (e) => {{
        if (!isPointerDown) return;
        isPointerDown = false;
        stage.classList.remove('is-dragging');

        if (hasDragged) {{
          // Clear dragged state after a short tick so click doesn't trigger selection at the end of a pan
          setTimeout(() => {{
            hasDragged = false;
          }}, 100);
        }} else {{
          hasDragged = false;
        }}
      }};

      window.addEventListener('pointerup', onPointerEnd);
      window.addEventListener('pointercancel', onPointerEnd);

      // Direct robust click delegator on SVG map for guaranteed region click responsiveness
      if (svgMap) {{
        svgMap.addEventListener('click', (e) => {{
          if (hasDragged) return;
          const regPath = e.target.closest('.region-polygon');
          if (regPath) {{
            const rName = regPath.getAttribute('data-name');
            if (rName) selectRegionByName(rName, true);
          }}
        }});
      }}

      // Mouse Wheel Zoom (Scroll to zoom in / out)
      stage.addEventListener('wheel', (e) => {{
        e.preventDefault();
        const factor = e.deltaY < 0 ? 1.09 : 0.91;
        zoomMap(factor);
      }}, {{ passive: false }});

      stage.addEventListener('mouseleave', () => {{
        if (isPointerDown) return;
        if (!is3DMode) return;
        rotX = 13;
        rotZ = -1.5;
        updateMapTransform(true);
      }});
    }}

    // Initialize Category Sparkline Chart (Chart.js)
    document.addEventListener('DOMContentLoaded', function() {{
      updateMapTransform(false);
      selectRegionByName('Greater Accra', false);

      const ctx = document.getElementById('categorySparkChart');
      if (ctx) {{
        new Chart(ctx, {{
          type: 'bar',
          data: {{
            labels: {json.dumps([c['name'] for c in categories])},
            datasets: [{{
              label: 'Case Volume',
              data: [320, 280, 240, 210, 190, 160],
              backgroundColor: [
                '{theme_hex}',
                '#3b82f6',
                '#6366f1',
                '#a855f7',
                '#06b6d4',
                '#10b981'
              ],
              borderRadius: 6,
              borderSkipped: false,
              barThickness: 16
            }}]
          }},
          options: {{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {{
              legend: {{ display: false }},
              tooltip: {{
                backgroundColor: '#0f172a',
                titleFont: {{ family: 'Inter', size: 11 }},
                bodyFont: {{ family: 'JetBrains Mono', size: 11 }},
                cornerRadius: 8,
                padding: 8
              }}
            }},
            scales: {{
              x: {{ display: false }},
              y: {{ display: false, beginAtZero: true }}
            }}
          }}
        }});
      }}
    }});
  </script>
</body>
</html>
'''
    return blade

configs = [
    # 0. CRIME SCENE SUPPORT DASHBOARD
    {
        'target_file': 'resources/views/Crime_Scene_Support/head/head.blade.php',
        'unit_slug': 'Crime_Scene_Support',
        'unit_name': 'Crime Scene Support Unit',
        'unit_code': 'CSS',
        'unit_title': 'Crime Scene Support Intelligence Dashboard',
        'page_title': 'Crime Scene Support Intelligence Dashboard | NACOC Forensic',
        'theme_accent': 'purple',
        'theme_hex': '#7c3aed',
        'sidebar_include': 'Crime_Scene_Support.partials.sidebar',
        'quick_action_url': "{{ url('/Crime_Scene_Support/staff/arrest-person') }}",
        'quick_action_label': '+ Log Crime Scene',
        'quick_action_icon': 'plus',
        'hero_count': '7,541<span class="text-lg font-bold text-purple-600">,390</span>',
        'hero_sub_label': 'Total Seizures Value',
        'hero_sub_val': '$12,875,400',
        'hero_growth': '14.2%',
        'discipline_title': 'Exhibit Discipline',
        'categories': [
            {'name': 'Narcotics & Controlled Drugs', 'color': 'purple', 'active': '324', 'metric': '1,540 kg'},
            {'name': 'Firearms & Ballistics', 'color': 'indigo', 'active': '311', 'metric': '1,320 rds'},
            {'name': 'Digital Seizures', 'color': 'pink', 'active': '300', 'metric': '1,201 dev'},
            {'name': 'Currency & Bullion Assets', 'color': 'amber', 'active': '288', 'metric': '989 pcs'},
            {'name': 'Vehicles & Heavy Equipment', 'color': 'cyan', 'active': '276', 'metric': '944 units'},
            {'name': 'Chemical Toxic Substances', 'color': 'emerald', 'active': '273', 'metric': '897 tests'},
        ],
        'counters': [
            {'label': 'All Field Incident Dossiers', 'val': '97,540', 'icon': 'archive', 'color': 'purple'},
            {'label': 'Unique Apprehended Suspects', 'val': '13,540', 'icon': 'user-check', 'color': 'indigo'},
            {'label': 'Active Crime Scenes Logged', 'val': '23,430', 'icon': 'flame', 'color': 'pink'},
            {'label': 'Scene Resolution Trend', 'val': '89%', 'icon': 'trending-up', 'color': 'emerald'},
        ],
        'dossier_m1': 'Narcotics Seized',
        'dossier_m2': 'Firearms Logged',
        'dossier_m3': 'Digital Devices',
        'dossier_m4': 'Impounded Vehicles',
        'dossier_action_label': 'Log Crime Scene',
        'dossier_action_url': "{{ url('/Crime_Scene_Support/staff/arrest-person') }}",
        'dossier_action_icon': 'plus-circle',
        'footer_links': [
            {'label': 'Geospatial Theatre', 'url': "{{ url('/Crime_Scene_Support/head') }}", 'icon': 'globe-2', 'active': True},
            {'label': 'Crime Scene Intake', 'url': "{{ url('/Crime_Scene_Support/staff/arrest-person') }}", 'icon': 'crosshair'},
            {'label': 'Field Staff Stations', 'url': "{{ url('/Crime_Scene_Support/staff') }}", 'icon': 'briefcase'},
            {'label': 'Forensic Laboratory', 'url': "{{ url('/Lab_Analysis') }}", 'icon': 'flask-conical'},
            {'label': 'National Case Registry', 'url': "{{ url('/cases') }}", 'icon': 'database'},
        ],
        'php_head_block': """@php
    $authUser = auth()->user();
    $userUnit = 'Crime Scene Support Unit';
    $unitDisplayTitle = 'Crime Scene Support Unit';
    $dbUnitHead = \\App\\Models\\User::where('unit', 'LIKE', '%Crime Scene%')->where('is_head', true)->first()
               ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Unit Head');

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp"""
    },

    # 1. LAB ANALYSIS DASHBOARD
    {
        'target_file': 'resources/views/Lab_Analysis/lab_analysis.blade.php',
        'unit_slug': 'Lab_Analysis',
        'unit_name': 'Lab Analysis Unit',
        'unit_code': 'LAB',
        'unit_title': 'Laboratory Analysis Headquarters',
        'page_title': 'Lab Analysis Unit Command Center | NACOC Forensic',
        'theme_accent': 'emerald',
        'theme_hex': '#059669',
        'sidebar_include': 'Crime_Scene_Support.partials.sidebar',
        'quick_action_url': "{{ url('/Lab_Analysis/create') }}",
        'quick_action_label': '+ Log Chemical Assay',
        'quick_action_icon': 'flask-conical',
        'hero_count': '2,845<span class="text-lg font-bold text-emerald-600">,120</span>',
        'hero_sub_label': 'Total Seized Narcotic Value Tested',
        'hero_sub_val': '$8,450,000',
        'hero_growth': '16.4%',
        'discipline_title': 'Chemical Commodity',
        'categories': [
            {'name': 'Cocaine Hydrochloride', 'color': 'emerald', 'active': '420', 'metric': '840 kg'},
            {'name': 'Heroin Diacetylmorphine', 'color': 'cyan', 'active': '310', 'metric': '395 kg'},
            {'name': 'Methamphetamine Crystal', 'color': 'teal', 'active': '280', 'metric': '275 kg'},
            {'name': 'Cannabis Herbal & Resin', 'color': 'emerald', 'active': '245', 'metric': '1,840 kg'},
            {'name': 'Synthetic Opioids & Fentanyl', 'color': 'purple', 'active': '190', 'metric': '94 kg'},
            {'name': 'Precursor Solvents & Acids', 'color': 'amber', 'active': '140', 'metric': '520 L'},
        ],
        'counters': [
            {'label': 'Chemical Assays Completed', 'val': '4,820', 'icon': 'test-tube-2', 'color': 'emerald'},
            {'label': 'GC-MS Purity Index', 'val': '84.6%', 'icon': 'gauge', 'color': 'cyan'},
            {'label': 'Confirmatory Sign-Offs', 'val': '1,840', 'icon': 'stamp', 'color': 'teal'},
            {'label': 'Evidentiary Admissibility', 'val': '99.8%', 'icon': 'scale', 'color': 'emerald'},
        ],
        'dossier_m1': 'Narcotic Assays',
        'dossier_m2': 'Average Purity',
        'dossier_m3': 'GC-MS Ingested',
        'dossier_m4': 'Reagent Tests',
        'dossier_action_label': 'Log Chemical Assay',
        'dossier_action_url': "{{ url('/Lab_Analysis/create') }}",
        'dossier_action_icon': 'flask-conical',
        'footer_links': [
            {'label': 'Geospatial Assays', 'url': "{{ url('/Lab_Analysis') }}", 'icon': 'globe-2', 'active': True},
            {'label': 'Assay Intake', 'url': "{{ url('/Lab_Analysis/create') }}", 'icon': 'flask-conical'},
            {'label': 'Analytical Results', 'url': "{{ url('/Lab_Analysis/lab-result') }}", 'icon': 'file-spreadsheet'},
            {'label': 'Evidence Vault', 'url': "{{ url('/Evidence_Management') }}", 'icon': 'box'},
            {'label': 'Case Registry', 'url': "{{ url('/cases') }}?from=Lab+Analysis", 'icon': 'database'},
        ],
        'php_head_block': """@php
    $authUser = auth()->user();
    $userUnit = 'Lab Analysis Unit';
    $unitDisplayTitle = 'Lab Analysis Unit';
    $dbUnitHead = \\App\\Models\\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first()
               ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Supt. Eric Mensah');

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp"""
    },

    # 2. DIGITAL & PHYSICAL FORENSICS DASHBOARD
    {
        'target_file': 'resources/views/Digital_Forensics/digital_forensics.blade.php',
        'unit_slug': 'Digital_Forensics',
        'unit_name': 'Digital & Physical Forensics',
        'unit_code': 'DPF',
        'unit_title': 'Digital & Mobile Cyber Forensics HQ',
        'page_title': 'Digital & Physical Forensics Command Center | NACOC Forensic',
        'theme_accent': 'indigo',
        'theme_hex': '#6366f1',
        'sidebar_include': 'Crime_Scene_Support.partials.sidebar',
        'quick_action_url': "{{ url('/Digital_Forensics/create') }}",
        'quick_action_label': '+ Log Device Seizure',
        'quick_action_icon': 'smartphone',
        'hero_count': '3,412<span class="text-lg font-bold text-indigo-600">,890</span>',
        'hero_sub_label': 'Bitstream Forensic Evidence Imaged',
        'hero_sub_val': '94.8 Terabytes',
        'hero_growth': '21.8%',
        'discipline_title': 'Device Extraction Class',
        'categories': [
            {'name': 'iOS Smartphones & Tablets', 'color': 'indigo', 'active': '480', 'metric': '1,240 dev'},
            {'name': 'Android Mobile Handsets', 'color': 'purple', 'active': '410', 'metric': '1,085 dev'},
            {'name': 'Laptops & NVMe Solid State', 'color': 'cyan', 'active': '310', 'metric': '620 dev'},
            {'name': 'Encrypted Hardware Wallets', 'color': 'violet', 'active': '220', 'metric': '240 dev'},
            {'name': 'Satellite & Burner Phones', 'color': 'sky', 'active': '180', 'metric': '145 dev'},
            {'name': 'SIM & Flash Micro-Media', 'color': 'pink', 'active': '130', 'metric': '82 dev'},
        ],
        'counters': [
            {'label': 'Hardware Devices Processed', 'val': '3,412', 'icon': 'hard-drive', 'color': 'indigo'},
            {'label': 'Cellebrite UFED Reports', 'val': '2,980', 'icon': 'cpu', 'color': 'purple'},
            {'label': 'SHA-256 Hash Integrity', 'val': '100%', 'icon': 'fingerprint', 'color': 'cyan'},
            {'label': 'Decryption Success Rate', 'val': '94.2%', 'icon': 'unlock', 'color': 'emerald'},
        ],
        'dossier_m1': 'Devices Seized',
        'dossier_m2': 'Cellebrite Reports',
        'dossier_m3': 'Crypto Wallets',
        'dossier_m4': 'SIMs Extracted',
        'dossier_action_label': 'Log Device Seizure',
        'dossier_action_url': "{{ url('/Digital_Forensics/create') }}",
        'dossier_action_icon': 'smartphone',
        'footer_links': [
            {'label': 'Geospatial Cyber', 'url': "{{ url('/Digital_Forensics') }}", 'icon': 'globe-2', 'active': True},
            {'label': 'Device Intake', 'url': "{{ url('/Digital_Forensics/create') }}", 'icon': 'smartphone'},
            {'label': 'Extraction Status', 'url': "{{ url('/Digital_Forensics/forensic-status') }}", 'icon': 'activity'},
            {'label': 'Forensic Laboratory', 'url': "{{ url('/Lab_Analysis') }}", 'icon': 'flask-conical'},
            {'label': 'Case Registry', 'url': "{{ url('/cases') }}?from=Digital+Forensics", 'icon': 'database'},
        ],
        'php_head_block': """@php
    $authUser = auth()->user();
    $userUnit = 'Digital & Physical Forensics Unit';
    $unitDisplayTitle = 'Digital & Physical Forensics Unit';
    $dbUnitHead = \\App\\Models\\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Officer');

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp"""
    },

    # 3. EVIDENCE MANAGEMENT DASHBOARD
    {
        'target_file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'unit_slug': 'Evidence_Management',
        'unit_name': 'Evidence Management Unit',
        'unit_code': 'EVD',
        'unit_title': 'Evidence Vault & Custody Headquarters',
        'page_title': 'Evidence Management Command Center | NACOC Forensic',
        'theme_accent': 'amber',
        'theme_hex': '#d97706',
        'sidebar_include': 'Crime_Scene_Support.partials.sidebar',
        'quick_action_url': "{{ url('/Evidence_Management/create') }}",
        'quick_action_label': '+ Lodge Vault Exhibit',
        'quick_action_icon': 'box',
        'hero_count': '5,840<span class="text-lg font-bold text-amber-600">,210</span>',
        'hero_sub_label': 'Total Secured Vault Holdings',
        'hero_sub_val': '$24,680,000',
        'hero_growth': '12.5%',
        'discipline_title': 'Vault Depository Class',
        'categories': [
            {'name': 'Narcotics High-Security Safe', 'color': 'amber', 'active': '520', 'metric': '2,450 pcs'},
            {'name': 'Firearms & Armory Vault', 'color': 'orange', 'active': '380', 'metric': '1,120 rds'},
            {'name': 'Currency & Bullion Depository', 'color': 'yellow', 'active': '310', 'metric': '980 pcs'},
            {'name': 'Digital Media Vault Locker', 'color': 'slate', 'active': '260', 'metric': '740 dev'},
            {'name': 'Hazardous Chemical Storage', 'color': 'red', 'active': '180', 'metric': '350 smp'},
            {'name': 'Impounded Bulky Vehicle Bay', 'color': 'zinc', 'active': '120', 'metric': '200 units'},
        ],
        'counters': [
            {'label': 'Barcoded Exhibits Lodged', 'val': '5,840', 'icon': 'qr-code', 'color': 'amber'},
            {'label': 'Active Vault Lockers', 'val': '86 Lockers', 'icon': 'shield', 'color': 'orange'},
            {'label': 'Court Production Dispatches', 'val': '1,420', 'icon': 'scale', 'color': 'yellow'},
            {'label': 'Chain of Custody Integrity', 'val': '100%', 'icon': 'check-circle-2', 'color': 'emerald'},
        ],
        'dossier_m1': 'Vault Exhibits',
        'dossier_m2': 'Active Lockers',
        'dossier_m3': 'Court Dispatched',
        'dossier_m4': 'Seals Verified',
        'dossier_action_label': 'Lodge Vault Exhibit',
        'dossier_action_url': "{{ url('/Evidence_Management/create') }}",
        'dossier_action_icon': 'box',
        'footer_links': [
            {'label': 'Geospatial Vault', 'url': "{{ url('/Evidence_Management') }}", 'icon': 'globe-2', 'active': True},
            {'label': 'Lodge Exhibit', 'url': "{{ url('/Evidence_Management/create') }}", 'icon': 'box'},
            {'label': 'Custody Ledger', 'url': "{{ url('/cases') }}?from=Evidence+Management", 'icon': 'history'},
            {'label': 'Crime Scene Support', 'url': "{{ url('/Crime_Scene_Support/head') }}", 'icon': 'crosshair'},
            {'label': 'Case Registry', 'url': "{{ url('/cases') }}?from=Evidence+Management", 'icon': 'database'},
        ],
        'php_head_block': """@php
    $authUser = auth()->user();
    $userUnit = 'Evidence Management Unit';
    $unitDisplayTitle = 'Evidence Management Unit';
    $dbUnitHead = \\App\\Models\\User::where('unit', 'LIKE', '%Evidence%')->where('is_head', true)->first()
               ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Chief Vault Custodian');

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp"""
    },

    # 4. FORENSIC DIRECTORATE HEAD DASHBOARD
    {
        'target_file': 'resources/views/Forensic_Head/head.blade.php',
        'unit_slug': 'Forensic_Head',
        'unit_name': 'Forensic Directorate Supreme Command',
        'unit_code': 'DIR',
        'unit_title': 'Forensic Directorate Executive Command Portal',
        'page_title': 'Forensic Department Head Command Portal | NACOC',
        'theme_accent': 'blue',
        'theme_hex': '#2563eb',
        'sidebar_include': 'Forensic_Head.partials.sidebar',
        'quick_action_url': "{{ url('/Forensic_Head/cases') }}",
        'quick_action_label': 'Master Case Directory',
        'quick_action_icon': 'briefcase',
        'hero_count': '12,890<span class="text-lg font-bold text-blue-600">,450</span>',
        'hero_sub_label': 'National Intercepted Contraband Value',
        'hero_sub_val': '$34,250,000',
        'hero_growth': '18.9%',
        'discipline_title': 'Directorate Unit Oversight',
        'categories': [
            {'name': 'Crime Scene Support Unit', 'color': 'purple', 'active': '620', 'metric': '4,210 cases'},
            {'name': 'Chemical Laboratory Analysis', 'color': 'emerald', 'active': '480', 'metric': '3,120 cases'},
            {'name': 'Evidence Vault Management', 'color': 'amber', 'active': '390', 'metric': '2,680 cases'},
            {'name': 'Digital & Cyber Forensics', 'color': 'indigo', 'active': '310', 'metric': '1,880 cases'},
            {'name': 'Joint Inter-Agency Strike Ops', 'color': 'cyan', 'active': '220', 'metric': '1,000 cases'},
            {'name': 'Cross-Border Maritime Intel', 'color': 'rose', 'active': '140', 'metric': '750 cases'},
        ],
        'counters': [
            {'label': 'National Forensic Dockets', 'val': '12,890', 'icon': 'layers', 'color': 'blue'},
            {'label': 'Active Forensic Agents', 'val': '184 Agents', 'icon': 'users', 'color': 'emerald'},
            {'label': 'Director Sign-Offs Pending', 'val': '4 Awaiting', 'icon': 'stamp', 'color': 'amber'},
            {'label': 'Compliance Audit Score', 'val': '99.4%', 'icon': 'shield-check', 'color': 'indigo'},
        ],
        'dossier_m1': 'Crime Scene Cases',
        'dossier_m2': 'Chemical Assays',
        'dossier_m3': 'Cyber Devices',
        'dossier_m4': 'Vault Exhibits',
        'dossier_action_label': 'Review Regional Cases',
        'dossier_action_url': "{{ url('/Forensic_Head/cases') }}",
        'dossier_action_icon': 'eye',
        'footer_links': [
            {'label': 'Geospatial Theatre', 'url': "{{ url('/Forensic_Head') }}", 'icon': 'globe-2', 'active': True},
            {'label': 'Case Management', 'url': "{{ url('/Forensic_Head/cases') }}", 'icon': 'briefcase'},
            {'label': 'Approvals & Sign-off', 'url': "{{ url('/Forensic_Head/approvals') }}", 'icon': 'stamp'},
            {'label': '4 Units Oversight', 'url': "{{ url('/welcome') }}", 'icon': 'layout-grid'},
            {'label': 'National Case Registry', 'url': "{{ url('/cases') }}", 'icon': 'database'},
        ],
        'php_head_block': """@php
    $authUser = auth()->user();
    $cases = $cases ?? collect();
    $userUnit = 'Forensic Directorate';
    $unitDisplayTitle = 'Forensic Directorate Supreme Command';
    $dbForensicHead = \\App\\Models\\User::where('is_head', true)
        ->where(function($q) {
            $q->where('unit', 'LIKE', '%Forensic%')
              ->orWhere('unit', 'LIKE', '%Directorate%');
        })->first() ?? \\App\\Models\\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbForensicHead ? $dbForensicHead->fullname : 'Anita Johnson');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';

    $totalSingleCases = \\App\\Models\\SingleSuspectCase::count();
    $totalMultiCases = \\App\\Models\\MultipleSuspectCase::count();
    $totalExhibits = \\App\\Models\\Exhibit::count();
    $totalCases = $totalSingleCases + $totalMultiCases;
@endphp"""
    }
]

for cfg in configs:
    output_path = cfg['target_file']
    print(f"Building {cfg['unit_name']} -> {output_path}...")
    blade_content = build_dashboard(cfg)
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    with open(output_path, 'w', encoding='utf-8') as out_f:
        out_f.write(blade_content)
    print(f"  [OK] Successfully wrote {len(blade_content)} bytes to {output_path}")

print("ALL 5 DASHBOARDS COMPILED WITH ENHANCED SIZE AND REGIONAL LABELS.")
