import os
import re

script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(script_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add tacDistrictDeckDrawer right before the footer in tactical modal HTML
old_tac_footer = """      <!-- Bottom Tactical Telemetry Bar -->
      <footer class="absolute bottom-4 left-6 right-6 z-40 bg-slate-950/80 border border-emerald-500/30 rounded-2xl px-6 py-2.5 backdrop-blur-xl flex flex-col md:flex-row items-center justify-between text-xs font-mono-code text-slate-400 gap-2">"""

new_tac_footer = """      <!-- Tactical MMDA District Telemetry Deck (Detailed MMDA Cards with Clear Labels) -->
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

      <!-- Bottom Tactical Telemetry Bar -->
      <footer class="absolute bottom-4 left-6 right-6 z-40 bg-slate-950/80 border border-emerald-500/30 rounded-2xl px-6 py-2.5 backdrop-blur-xl flex flex-col md:flex-row items-center justify-between text-xs font-mono-code text-slate-400 gap-2">"""

if old_tac_footer in content:
    content = content.replace(old_tac_footer, new_tac_footer)
    print("Replaced tactical footer with deck drawer")
else:
    print("Could not find old_tac_footer")

# 2. Update Left HUD tacHudDistrictsList max-height from max-h-36 to max-h-48
old_tac_hud_list = """          <div>
            <span class="text-[10px] font-mono-code text-slate-400 uppercase tracking-wider block mb-1.5">MMDA Districts & Assemblies</span>
            <div id="tacHudDistrictsList" class="space-y-1 max-h-36 overflow-y-auto pr-1 text-[11px] font-mono-code">
              <!-- Dynamically populated via JS -->
            </div>
          </div>"""

new_tac_hud_list = """          <div>
            <span class="text-[10px] font-mono-code text-slate-400 uppercase tracking-wider block mb-1.5">MMDA Districts & Assemblies</span>
            <div id="tacHudDistrictsList" class="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-[11px] font-mono-code">
              <!-- Dynamically populated via JS -->
            </div>
          </div>"""

if old_tac_hud_list in content:
    content = content.replace(old_tac_hud_list, new_tac_hud_list)
    print("Replaced Left HUD tacHudDistrictsList height")
else:
    print("Could not find old_tac_hud_list")

# 3. Update renderTacDistrictMap to provide crystal-clear 2-line labels on SVG, rich hover, and populate tacDistrictDeckGrid
old_render_tac = """    // Renders Tactical Radar MMDA Districts & Hotspots
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
          const firstCase = isRed ? item.cases[0] : null;
          return `
            <div onclick="if (${{count}} > 0) selectInvoiceById(${{firstCase.id}}, false);" 
                 class="flex items-center justify-between p-1.5 rounded ${{isRed ? 'bg-red-950/70 border border-red-500/60 hover:bg-red-900/60 text-red-200' : 'bg-slate-900/60 hover:bg-emerald-500/20 text-slate-300'}} cursor-pointer text-[10.5px]">
              <span class="flex items-center space-x-1.5 truncate">
                <span class="font-bold truncate text-white">${{d.short_name}}</span>
              </span>
              <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-300' : 'text-slate-400'}}">
                ${{isRed ? `🚨 ${{firstCase.suspect_name}} (${{firstCase.case_number}})` : '0 Active'}}
              </span>
            </div>
          `;
        }}).join('');
      }}

      // Render Tactical SVG Markers (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);">
              <rect x="${{d.x - 26}}" y="${{d.y - 5.5}}" width="52" height="10.5" rx="1.5" fill="#180404" stroke="#ef4444" stroke-width="1.2" />
              <text x="${{d.x}}" y="${{d.y + 0.8}}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#fca5a5" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 9.8}}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#ef4444" stroke="#000" stroke-width="0.8" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{c.case_number}} • ${{c.suspect_name}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation();">
              <rect x="${{d.x - 18}}" y="${{d.y - 3.5}}" width="36" height="6.5" rx="1" fill="#064e3b" stroke="#10b981" stroke-width="0.5" />
              <text x="${{d.x}}" y="${{d.y + 1.0}}" text-anchor="middle" font-size="3.0" font-weight="bold" fill="#a7f3d0" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');
    }}"""

new_render_tac = """    // Renders Tactical Radar MMDA Districts & Hotspots with High-Clarity Labels & Details
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

      // 1. Populate Tactical Left HUD MMDA list with Complete Details
      const tacDistList = document.getElementById('tacHudDistrictsList');
      if (tacDistList) {{
        tacDistList.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const count = item.cases.length;
          const isRed = count > 0;
          const firstCase = isRed ? item.cases[0] : null;
          return `
            <div onclick="if (${{count}} > 0) selectInvoiceById(${{firstCase.id}}, false);" 
                 class="p-2 rounded-xl border cursor-pointer transition-all ${{isRed ? 'bg-red-950/70 border-red-500/60 hover:bg-red-900/60 text-red-200' : 'bg-slate-900/70 border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/30 text-slate-300'}}">
              <div class="flex items-center justify-between">
                <span class="font-bold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded' : 'text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded'}}">
                  ${{isRed ? `🚨 ${{count}} DOCKET` : '0 Active'}}
                </span>
              </div>
              <div class="text-[9.5px] text-slate-400 truncate mt-0.5">Cap: ${{d.capital}}</div>
              ${{isRed ? `<div class="text-[9.5px] text-red-300 font-bold truncate mt-0.5">👤 ${{firstCase.suspect_name}} (${{firstCase.case_number}})</div>` : ''}}
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

          if (hasCases) {{
            const c = linked[0];
            return `
              <div onclick="selectInvoiceById(${{c.id}}, false)"
                   class="p-2.5 rounded-xl bg-red-950/60 hover:bg-red-900/80 border border-red-500/70 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-lg group">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-bold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-red-600 text-white font-black flex-shrink-0">HOTSPOT</span>
                </div>
                <div class="text-[10px] text-red-200 font-bold truncate">
                  🚨 ${{c.case_number}} • ${{c.suspect_name}}
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
                <div class="text-[9px] text-emerald-400 font-medium truncate">
                  Patrol Sector Clear
                </div>
              </div>
            `;
          }}
        }}).join('');
      }}

      // 3. Render High-Clarity 2-Line Tactical SVG Markers on Map with Hover Inspection
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;
        const dJson = encodeURIComponent(JSON.stringify({{ code: d.code, name: d.name, short_name: d.short_name, capital: d.capital }}));
        const casesJson = encodeURIComponent(JSON.stringify(linked.map(c => ({{ case_number: c.case_number, suspect_name: c.suspect_name, raid_location: c.raid_location, status_badge: c.status_badge, exhibits_count: c.exhibits_count || 1 }}))));

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);"
               onmouseenter="onTacDistrictHover(event, '${{dJson}}', '${{casesJson}}')"
               onmouseleave="onTacDistrictLeave()">
              <rect x="${{d.x - 28}}" y="${{d.y - 6.5}}" width="56" height="13" rx="2" fill="#120404" stroke="#ef4444" stroke-width="1.2" />
              <text x="${{d.x}}" y="${{d.y - 1.2}}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.5}}" text-anchor="middle" font-size="3.1" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{c.case_number}} • ${{c.suspect_name}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation();"
               onmouseenter="onTacDistrictHover(event, '${{dJson}}', '${{casesJson}}')"
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
    }}"""

if old_render_tac in content:
    content = content.replace(old_render_tac, new_render_tac)
    print("Replaced renderTacDistrictMap")
else:
    print("Could not find old_render_tac")

# 4. Update standard renderDistrictMap for crisp 2-line labels as well
old_render_std = """      // 1. Render Clean Rectangular MMDA Badges (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <rect x="${{d.x - 22}}" y="${{d.y - 4.5}}" width="44" height="8.5" rx="1.5" fill="#181926" stroke="#ef4444" stroke-width="0.8" />
              <text x="${{d.x}}" y="${{d.y + 1.2}}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 8.8}}" text-anchor="middle" font-size="3.2" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{primaryCase.case_number}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <rect x="${{d.x - 18}}" y="${{d.y - 3.5}}" width="36" height="6.5" rx="1" fill="#1e293b" stroke="#475569" stroke-width="0.4" class="group-hover:stroke-indigo-400 transition-colors" />
              <text x="${{d.x}}" y="${{d.y + 1.0}}" text-anchor="middle" font-size="3.0" font-weight="bold" fill="#cbd5e1" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');"""

new_render_std = """      // 1. Render Clean Rectangular MMDA Badges (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <rect x="${{d.x - 26}}" y="${{d.y - 6.0}}" width="52" height="12" rx="2" fill="#181926" stroke="#ef4444" stroke-width="0.9" />
              <text x="${{d.x}}" y="${{d.y - 1.2}}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.2}}" text-anchor="middle" font-size="3.0" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{primaryCase.case_number}}
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
      }}).join('');"""

if old_render_std in content:
    content = content.replace(old_render_std, new_render_std)
    print("Replaced standard renderDistrictMap")
else:
    print("Could not find old_render_std")

# 5. In focusRegionOnMap: show tacDistrictDeckDrawer when in tactical mode
old_focus_end = """        const tacStations = document.getElementById('tacHudStations');
        if (tacStations) tacStations.textContent = `${{districts.length}} Districts`;
      }}

      // 6. Render Districts and their Linked Cases
      renderDistrictMap(regName);
      renderTacDistrictMap(regName);
    }}"""

new_focus_end = """        const tacStations = document.getElementById('tacHudStations');
        if (tacStations) tacStations.textContent = `${{districts.length}} Districts`;

        // Show Tactical MMDA Districts Deck Drawer
        const tacDeck = document.getElementById('tacDistrictDeckDrawer');
        if (tacDeck) tacDeck.classList.remove('hidden');
      }}

      // 6. Render Districts and their Linked Cases
      renderDistrictMap(regName);
      renderTacDistrictMap(regName);
    }}"""

if old_focus_end in content:
    content = content.replace(old_focus_end, new_focus_end)
    print("Replaced focusRegionOnMap end")
else:
    print("Could not find old_focus_end")

# 6. In resetMapView: hide tacDistrictDeckDrawer
old_reset_end = """      // 5. Hide District Navigation Headers & Drawers
      if (!isTac) {{
        const navHeader = document.getElementById('districtNavHeader');
        if (navHeader) navHeader.classList.add('hidden');
        const drawer = document.getElementById('districtCardsDrawer');
        if (drawer) drawer.classList.add('hidden');
        const cornerTacBtn = document.getElementById('btnMapCornerTactical');
        if (cornerTacBtn) cornerTacBtn.classList.remove('hidden');
        const panHint = document.getElementById('mapPanZoomHint');
        if (panHint) panHint.classList.remove('hidden');
      }} else {{
        const tacNav = document.getElementById('tacDistrictNavHeader');
        if (tacNav) tacNav.classList.add('hidden');
      }}"""

new_reset_end = """      // 5. Hide District Navigation Headers & Drawers
      if (!isTac) {{
        const navHeader = document.getElementById('districtNavHeader');
        if (navHeader) navHeader.classList.add('hidden');
        const drawer = document.getElementById('districtCardsDrawer');
        if (drawer) drawer.classList.add('hidden');
        const cornerTacBtn = document.getElementById('btnMapCornerTactical');
        if (cornerTacBtn) cornerTacBtn.classList.remove('hidden');
        const panHint = document.getElementById('mapPanZoomHint');
        if (panHint) panHint.classList.remove('hidden');
      }} else {{
        const tacNav = document.getElementById('tacDistrictNavHeader');
        if (tacNav) tacNav.classList.add('hidden');
        const tacDeck = document.getElementById('tacDistrictDeckDrawer');
        if (tacDeck) tacDeck.classList.add('hidden');
      }}"""

if old_reset_end in content:
    content = content.replace(old_reset_end, new_reset_end)
    print("Replaced resetMapView end")
else:
    print("Could not find old_reset_end")

# 7. Add helper functions toggleTacDistrictDeck, onTacDistrictHover, onTacDistrictLeave
functions_marker = """    function onTacRegionHover(e, name) {{"""

new_functions = """    function toggleTacDistrictDeck() {{
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

    function onTacDistrictHover(e, dStr, casesStr) {{
      try {{
        const d = JSON.parse(decodeURIComponent(dStr));
        const cases = JSON.parse(decodeURIComponent(casesStr));
        const tt = document.getElementById('tacRegionHoverTooltip');
        const ttName = document.getElementById('tacTooltipRegionName');
        const ttSub = document.getElementById('tacTooltipSub');
        if (!tt || !ttName || !ttSub) return;

        ttName.innerHTML = `<span class="text-white font-black">[${{d.code}}] ${{d.name || d.short_name}}</span>`;
        if (cases && cases.length > 0) {{
          const c = cases[0];
          ttSub.innerHTML = `
            <div class="mt-1 text-red-300 font-bold text-[11px]">🚨 ${{cases.length}} DOCKET(S) ACTIVE IN MMDA</div>
            <div class="text-[10px] text-white font-bold mt-0.5">Target: ${{c.suspect_name}} (${{c.case_number}})</div>
            <div class="text-[9.5px] text-slate-300 mt-0.5">Capital: ${{d.capital}} • Exhibits: ${{c.exhibits_count || 1}} • Status: ${{c.status_badge || 'Active'}}</div>
          `;
          tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2.5 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/80 transition-opacity duration-150";
        }} else {{
          ttSub.innerHTML = `
            <div class="mt-0.5 text-emerald-300 font-bold text-[11px]">MMDA Classification: Official District Assembly</div>
            <div class="text-[10px] text-slate-200 mt-0.5">Administrative Capital: ${{d.capital}}</div>
            <div class="text-[9.5px] text-emerald-400 mt-0.5">Patrol Status: Sector Clear • 0 Active Dockets</div>
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
      }} catch (err) {{
        console.warn('District hover err:', err);
      }}
    }}

    function onTacDistrictLeave() {{
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }}

    function onTacRegionHover(e, name) {{"""

if functions_marker in content:
    content = content.replace(functions_marker, new_functions)
    print("Added helper functions for tactical districts")
else:
    print("Could not find functions_marker")

with open(script_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated build_clean_dashboards.py with Detailed Tactical Districts successfully!")
