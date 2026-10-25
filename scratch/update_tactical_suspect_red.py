import os
import re

script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(script_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update tactical_paths_markup to add tactical-region-hotspot & region-hotspot if has_cases
old_tac_paths = """# Same paths for the tactical fullscreen map
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
tactical_paths_html = ''.join(tactical_paths_markup)"""

new_tac_paths = """# Same paths for the tactical fullscreen map
tactical_paths_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    has_cases = r.get('cases', 0) > 0
    extra_cls = 'tactical-region-hotspot region-hotspot' if has_cases else ''
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
tactical_paths_html = ''.join(tactical_paths_markup)"""

if old_tac_paths in content:
    content = content.replace(old_tac_paths, new_tac_paths)
    print("Replaced tactical_paths_markup")
else:
    print("Could not find old_tac_paths")

# 2. Update tactical_labels_markup
old_tac_labels = """# Tactical direct region labels
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
tactical_labels_html = ''.join(tactical_labels_markup)"""

new_tac_labels = """# Tactical direct region labels
tactical_labels_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    has_cases = r.get('cases', 0) > 0
    badge_fill = '#ef4444' if has_cases else 'rgba(52, 211, 153, 0.9)'
    badge_text = f"{r['cases']} DOCKETS [TARGET RED]" if has_cases else f"{r['cases']} DOCKETS"
    name_fill = 'fill-red-300' if has_cases else 'fill-emerald-300'
    tactical_labels_markup.append(f'''
            <g id="tac-lbl-{reg_id}" class="pointer-events-none transition-opacity duration-300">
              <text x="{r['cx']}" y="{r['cy'] - 4}" 
                    text-anchor="middle" 
                    class="font-mono-code font-black text-[10.5px] tracking-widest {name_fill} drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] select-none">{r['name'].upper()}</text>
              <text id="tac-badge-{reg_id}" x="{r['cx']}" y="{r['cy'] + 10}" 
                    text-anchor="middle" 
                    fill="{badge_fill}"
                    font-weight="900"
                    class="font-mono-code text-[9px] select-none">{badge_text}</text>
            </g>''')
tactical_labels_html = ''.join(tactical_labels_markup)"""

if old_tac_labels in content:
    content = content.replace(old_tac_labels, new_tac_labels)
    print("Replaced tactical_labels_markup")
else:
    print("Could not find old_tac_labels")

# 3. Update CSS rules for tactical hotspots & clean up keyframe leftover
old_css_hotspot = """    /* High-Caseload Forensic Red Hotspot Region Polygons */
    .region-polygon.region-hotspot,
    .region-polygon.region-hotspot.region-active {
      fill: #dc2626 !important;
      stroke: #fecaca !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 0 28px rgba(220, 38, 38, 0.9)) !important;
      opacity: 1 !important;
    }
    .region-polygon.region-hotspot:hover {
      fill: #ef4444 !important;
      stroke: #ffffff !important;
      filter: drop-shadow(0 0 40px rgba(239, 68, 68, 1)) !important;
    }
    .tactical-region-polygon.region-hotspot,
    .tactical-region-polygon.region-hotspot.region-active {
      fill: #991b1b !important;
      stroke: #f87171 !important;
      stroke-width: 3px !important;
      filter: drop-shadow(0 0 38px rgba(239, 68, 68, 0.95)) !important;
      opacity: 1 !important;
    }
    .tactical-region-polygon.region-hotspot:hover {
      fill: #b91c1c !important;
      stroke: #fca5a5 !important;
      filter: drop-shadow(0 0 50px rgba(239, 68, 68, 1)) !important;
    }

    /* Standard Active Region (when not a hotspot) */
    .region-polygon.region-active:not(.region-hotspot) {
      fill: #5c56df !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(92, 86, 223, 0.45)) !important;
      opacity: 1 !important;
    }

    .tactical-region-polygon.region-active:not(.region-hotspot) {
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
    /* Radar rings are static (no pulsing) */
      50% { opacity: 0.35; }
      100% { transform: translate(-50%, -50%) scale(1.35); opacity: 0; }
    }"""

# Notice that in the python string, curly braces are doubled: {{ and }}
# Let's check how it's formatted in the python file
pattern_css = r'/\* High-Caseload Forensic Red Hotspot Region Polygons \*/[\s\S]*?/\* SVG Region Transition \*/'
match = re.search(pattern_css, content)
if match:
    new_css = """/* High-Caseload & Suspect-Linked Forensic Red Hotspot Region Polygons */
    .region-polygon.region-hotspot,
    .region-polygon.region-hotspot.region-active,
    .tactical-region-polygon.region-hotspot,
    .tactical-region-polygon.tactical-region-hotspot,
    .tactical-region-polygon.region-active.region-hotspot,
    .tactical-region-polygon.region-active.tactical-region-hotspot,
    .tactical-region-polygon.region-active {
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

    /* Standard Active Region (when not a hotspot) */
    .region-polygon.region-active:not(.region-hotspot) {
      fill: #5c56df !important;
      stroke: #ffffff !important;
      stroke-width: 2.8px !important;
      filter: drop-shadow(0 16px 32px rgba(92, 86, 223, 0.45)) !important;
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
    /* SVG Region Transition */"""
    
    # Make sure curly braces are doubled for python f-string
    new_css_doubled = new_css.replace('{', '{{').replace('}', '}}')
    content = content[:match.start()] + new_css_doubled + content[match.end():]
    print("Replaced CSS rules")
else:
    print("Could not match CSS rules pattern")

# 4. Update tacDistrictNavHeader position to top center so it never overlaps Left HUD
old_tac_nav = """      <!-- Floating Return to Overview Pill inside Tactical Theatre -->
      <div id="tacDistrictNavHeader" class="hidden absolute top-6 left-6 z-40 flex items-center space-x-3">
        <button type="button" 
                onclick="resetMapView(true)" 
                class="px-3.5 py-2 rounded-xl bg-slate-950/95 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl transition-all group">
          <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
          <span>RETURN TO NATIONAL OVERVIEW</span>
        </button>
        <span id="tacDistrictDrillTitleBadge" class="px-3.5 py-2 rounded-xl bg-slate-950/90 text-white border border-emerald-500/40 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-xl">
          <span id="tacDistrictDrillTitle">GREATER ACCRA MMDA DISTRICT RADAR</span>
        </span>
      </div>"""

new_tac_nav = """      <!-- Floating Return to Overview Pill inside Tactical Theatre (Top Center) -->
      <div id="tacDistrictNavHeader" class="hidden absolute top-4 left-1/2 -translate-x-1/2 z-50 flex items-center space-x-3">
        <button type="button" 
                onclick="resetMapView(true)" 
                class="px-4 py-2 rounded-xl bg-slate-950/95 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl transition-all group">
          <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
          <span>RETURN TO NATIONAL RADAR</span>
        </button>
        <span id="tacDistrictDrillTitleBadge" class="px-4 py-2 rounded-xl bg-red-950/90 text-white border border-red-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl">
          <span id="tacDistrictDrillTitle">🚨 TARGET SECTOR: GREATER ACCRA MMDA RADAR</span>
        </span>
      </div>"""

if old_tac_nav in content:
    content = content.replace(old_tac_nav, new_tac_nav)
    print("Replaced tacDistrictNavHeader")
else:
    print("Could not find old_tac_nav")

# 5. Update renderTacticalCasesStream
old_tac_stream = """    // Render live docket stream in tactical mode
    function renderTacticalCasesStream() {{
      const container = document.getElementById('tacCasesListContainer');
      if (!container) return;

      container.innerHTML = liveCases.map(c => `
        <div onclick="selectInvoiceById(${{c.id}}, true); selectRegionByName('${{c.region}}', true);" class="p-2 rounded-xl bg-slate-900/90 hover:bg-emerald-500/20 border border-emerald-500/20 cursor-pointer transition-colors flex items-center justify-between text-xs font-mono-code">
          <div class="flex items-center space-x-2 truncate">
            <div class="truncate">
              <span class="font-bold text-white">${{c.case_number}}</span>
              <span class="text-[10px] text-slate-400 block">${{c.suspect_name}} • ${{c.region}}</span>
            </div>
          </div>
          <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">${{c.status_badge}}</span>
        </div>
      `).join('');
    }}"""

new_tac_stream = """    // Render live docket stream in tactical mode
    function renderTacticalCasesStream() {{
      const container = document.getElementById('tacCasesListContainer');
      if (!container) return;

      container.innerHTML = liveCases.map(c => {{
        const isSelected = (c.id == selectedCaseId);
        const cardStyle = isSelected
          ? 'bg-red-950/80 border-red-500 text-white shadow-[0_0_20px_rgba(239,68,68,0.4)]'
          : 'bg-slate-900/90 hover:bg-red-950/40 border-slate-700/60 hover:border-red-500/40 text-slate-300';
        const badgeStyle = isSelected
          ? 'bg-red-500 text-white font-black'
          : 'bg-red-500/20 text-red-300 font-bold';

        return `
          <div onclick="selectInvoiceById(${{c.id}}, true); selectRegionByName('${{c.region}}', true);" 
               class="p-2.5 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs font-mono-code ${{cardStyle}}">
            <div class="flex items-center space-x-2 truncate">
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-white">${{c.case_number}}</span>
                  ${{isSelected ? '<span class="text-[9px] px-1 bg-red-600 text-white rounded font-black">TARGET</span>' : ''}}
                </div>
                <span class="text-[10.5px] text-red-300 font-bold block truncate mt-0.5">👤 ${{c.suspect_name}}</span>
                <span class="text-[9.5px] text-slate-400 block truncate">📍 ${{c.region}} (${{c.exhibits_count || 1}} Exh)</span>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded ${{badgeStyle}}">${{c.status_badge || 'Active'}}</span>
          </div>
        `;
      }}).join('');
    }}"""

if old_tac_stream in content:
    content = content.replace(old_tac_stream, new_tac_stream)
    print("Replaced renderTacticalCasesStream")
else:
    print("Could not find old_tac_stream")

# 6. Update selectInvoiceById to call renderTacticalCasesStream
old_select_inv = """    function selectInvoiceById(caseId, centerMap = true) {{
      selectedCaseId = caseId;
      const c = liveCases.find(item => item.id == caseId);
      if (!c) return;

      renderInvoicesList();
      renderDossierCard(c);

      if (centerMap && c.region && selectedRegionName !== c.region) {{
        selectRegionByName(c.region, isTacticalModeOpen);
      }}
    }}"""

new_select_inv = """    function selectInvoiceById(caseId, centerMap = true) {{
      selectedCaseId = caseId;
      const c = liveCases.find(item => item.id == caseId);
      if (!c) return;

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

      if (centerMap && c.region && selectedRegionName !== c.region) {{
        selectRegionByName(c.region, isTacticalModeOpen);
      }}
    }}"""

if old_select_inv in content:
    content = content.replace(old_select_inv, new_select_inv)
    print("Replaced selectInvoiceById")
else:
    print("Could not find old_select_inv")

# 7. Update applyRealtimeData region counts logic
old_counts = """      if (data.region_counts) {{
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
      }}"""

new_counts = """      if (data.region_counts) {{
        const counts = data.region_counts || {{}};

        Object.keys(regionsData).forEach(rName => {{
          const count = counts[rName] || 0;
          const regId = rName.toLowerCase().replace(/\\s+/g, '-');
          const isHotspot = (count > 0); // Any region linking to suspects/cases is RED
          
          if (regionsData[rName]) regionsData[rName].cases = count;

          const poly = document.getElementById('path-' + regId);
          const tacPoly = document.getElementById('tac-path-' + regId);
          const badge = document.getElementById('badge-' + regId);
          const tacBadge = document.getElementById('tac-badge-' + regId);

          if (badge) {{
            badge.textContent = isHotspot ? `${{count}} Dockets [TARGET]` : `${{count}} Dockets`;
            if (isHotspot) {{
              badge.setAttribute('fill', '#f87171');
              badge.setAttribute('font-weight', '900');
            }} else {{
              badge.setAttribute('fill', '#818cf8');
              badge.setAttribute('font-weight', 'bold');
            }}
          }}

          if (tacBadge) {{
            tacBadge.textContent = isHotspot ? `${{count}} DOCKETS [TARGET RED]` : `${{count}} DOCKETS`;
            if (isHotspot) {{
              tacBadge.setAttribute('fill', '#ef4444');
              tacBadge.setAttribute('font-weight', '900');
            }} else {{
              tacBadge.setAttribute('fill', '#34d399');
              tacBadge.setAttribute('font-weight', 'bold');
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
      }}"""

if old_counts in content:
    content = content.replace(old_counts, new_counts)
    print("Replaced applyRealtimeData counts")
else:
    print("Could not find old_counts")

# 8. Update focusRegionOnMap: ensure active tactical polygon is red & Left HUD shows target data
old_focus = """      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      document.querySelectorAll(polySelector).forEach(p => {{
        if (p.getAttribute('data-name') === regName) {{
          p.style.display = 'block';
          p.style.opacity = '1';
          p.classList.add('region-active');
        }} else {{
          p.style.display = 'none';
          p.classList.remove('region-active');
        }}
      }});

      // 4. Hide national region labels
      const lblGroup = isTac ? document.getElementById('tacRegionLabelsGroup') : document.getElementById('regionLabelsGroup');
      if (lblGroup) lblGroup.style.display = 'none';

      // 5. Update Navigation Header
      const districts = districtsData[regName] || [];
      const regCases = r.cases || liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase()).length;
      const isHotspot = (regCases >= 2);

      if (!isTac) {{
        const navHeader = document.getElementById('districtNavHeader');
        const drillTitle = document.getElementById('districtDrillTitle');
                const cornerTacBtn = document.getElementById('btnMapCornerTactical');
        const panHint = document.getElementById('mapPanZoomHint');

        if (cornerTacBtn) cornerTacBtn.classList.add('hidden');
        if (panHint) panHint.classList.add('hidden');

        if (navHeader && drillTitle) {{
          navHeader.classList.remove('hidden');
          drillTitle.textContent = `${{regName.toUpperCase()}} DISTRICT MAP (${{districts.length}} MMDAs • ${{regCases}} Cases)`;
          
        }}

        // Show organized MMDA Districts Drawer below map
        const drawer = document.getElementById('districtCardsDrawer');
        if (drawer) {{
          drawer.classList.remove('hidden');
          const badge = document.getElementById('districtDrawerBadge');
          if (badge) {{
            badge.textContent = `${{regCases}} Cases Linked`;
            badge.className = isHotspot 
              ? 'text-[10px] text-red-400 font-bold bg-red-500/20 px-2 py-0.5 rounded border border-red-500/30' 
              : 'text-[10px] text-indigo-400 font-bold bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30';
          }}
        }}
      }} else {{
        const tacNav = document.getElementById('tacDistrictNavHeader');
        const tacTitle = document.getElementById('tacDistrictDrillTitle');
        if (tacNav && tacTitle) {{
          tacNav.classList.remove('hidden');
          tacTitle.textContent = `${{regName.toUpperCase()}} MMDA DISTRICT RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
        }}

        // Update Tactical Left HUD
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
      }}"""

new_focus = """      const districts = districtsData[regName] || [];
      const regCases = r.cases || liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase()).length;
      const isHotspot = (regCases > 0);

      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      document.querySelectorAll(polySelector).forEach(p => {{
        if (p.getAttribute('data-name') === regName) {{
          p.style.display = 'block';
          p.style.opacity = '1';
          p.classList.add('region-active');
          if (isHotspot) {{
            p.classList.add('region-hotspot', 'tactical-region-hotspot');
          }}
        }} else {{
          p.style.display = 'none';
          p.classList.remove('region-active');
        }}
      }});

      // 4. Hide national region labels
      const lblGroup = isTac ? document.getElementById('tacRegionLabelsGroup') : document.getElementById('regionLabelsGroup');
      if (lblGroup) lblGroup.style.display = 'none';

      // 5. Update Navigation Header
      if (!isTac) {{
        const navHeader = document.getElementById('districtNavHeader');
        const drillTitle = document.getElementById('districtDrillTitle');
        const cornerTacBtn = document.getElementById('btnMapCornerTactical');
        const panHint = document.getElementById('mapPanZoomHint');

        if (cornerTacBtn) cornerTacBtn.classList.add('hidden');
        if (panHint) panHint.classList.add('hidden');

        if (navHeader && drillTitle) {{
          navHeader.classList.remove('hidden');
          drillTitle.textContent = `${{regName.toUpperCase()}} DISTRICT MAP (${{districts.length}} MMDAs • ${{regCases}} Cases)`;
        }}

        // Show organized MMDA Districts Drawer below map
        const drawer = document.getElementById('districtCardsDrawer');
        if (drawer) {{
          drawer.classList.remove('hidden');
          const badge = document.getElementById('districtDrawerBadge');
          if (badge) {{
            badge.textContent = `${{regCases}} Cases Linked`;
            badge.className = isHotspot 
              ? 'text-[10px] text-red-400 font-bold bg-red-500/20 px-2 py-0.5 rounded border border-red-500/30' 
              : 'text-[10px] text-indigo-400 font-bold bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30';
          }}
        }}
      }} else {{
        const tacNav = document.getElementById('tacDistrictNavHeader');
        const tacTitle = document.getElementById('tacDistrictDrillTitle');
        if (tacNav && tacTitle) {{
          tacNav.classList.remove('hidden');
          tacTitle.innerHTML = `<span class="text-red-400 font-black">🚨 TARGET SECTOR:</span> ${{regName.toUpperCase()}} MMDA RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
        }}

        // Update Tactical Left HUD with Target Suspect Telemetry
        const tacName = document.getElementById('tacHudRegionName');
        if (tacName) tacName.textContent = regName.toUpperCase();
        const tacCap = document.getElementById('tacHudCapital');
        if (tacCap) tacCap.textContent = `Capital: ${{r.capital || 'Ghana'}}`;
        const tacDoc = document.getElementById('tacHudDockets');
        if (tacDoc) {{
          tacDoc.textContent = `${{regCases}} Dockets Linked`;
          tacDoc.className = isHotspot ? 'text-base font-black text-red-400' : 'text-base font-black text-emerald-400';
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
      }}"""

if old_focus in content:
    content = content.replace(old_focus, new_focus)
    print("Replaced focusRegionOnMap")
else:
    print("Could not find old_focus")

# 9. Update renderTacDistrictMap: show suspect details & target badges on district markers
old_tac_district = """      // Populate Tactical Left HUD MMDA list
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
                <span class="font-bold truncate text-white">${{d.short_name}}</span>
              </span>
              <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-400' : 'text-slate-400'}}">
                ${{isRed ? `🚨 ${{count}} DOCKET` : '0 Active'}}
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
              <rect x="${{d.x - 24}}" y="${{d.y - 4.5}}" width="48" height="8.5" rx="1" fill="#0b0f19" stroke="#ef4444" stroke-width="0.9" />
              <text x="${{d.x}}" y="${{d.y + 1.2}}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#fca5a5" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 8.5}}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#ef4444" stroke="#000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{c.case_number}} [${{c.exhibits_count || 1}} EXH]
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
      }}).join('');"""

new_tac_district = """      // Populate Tactical Left HUD MMDA list
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
      }}).join('');"""

if old_tac_district in content:
    content = content.replace(old_tac_district, new_tac_district)
    print("Replaced renderTacDistrictMap")
else:
    print("Could not find old_tac_district")

# 10. Update resetMapView: restore red hotspots on tactical polygons
old_reset = """      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      document.querySelectorAll(polySelector).forEach(p => {{
        p.style.display = 'block';
        p.style.opacity = '1';
        p.classList.remove('region-active');
      }});"""

new_reset = """      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      document.querySelectorAll(polySelector).forEach(p => {{
        p.style.display = 'block';
        p.style.opacity = '1';
        p.classList.remove('region-active');
        const rName = p.getAttribute('data-name');
        const rCases = (regionsData[rName] ? regionsData[rName].cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (rName || '').toLowerCase()).length;
        if (rCases > 0) {{
          p.classList.add('region-hotspot', 'tactical-region-hotspot');
        }}
      }});"""

if old_reset in content:
    content = content.replace(old_reset, new_reset)
    print("Replaced resetMapView")
else:
    print("Could not find old_reset")

# 11. Update openTacticalMode: zoom to selected suspect's region automatically
old_open_tac = """    function openTacticalMode() {{
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

      if (selectedRegionName) {{
        focusRegionOnMap(selectedRegionName, true);
      }}
    }}"""

new_open_tac = """    function openTacticalMode() {{
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

      // Resolve the suspect that is currently selected, or default to first active case
      const activeCase = liveCases.find(c => c.id == selectedCaseId) || liveCases[0];
      const targetReg = selectedRegionName || (activeCase ? activeCase.region : 'Greater Accra');

      if (targetReg) {{
        selectRegionByName(targetReg, true);
      }} else {{
        resetMapView(true);
      }}
    }}"""

if old_open_tac in content:
    content = content.replace(old_open_tac, new_open_tac)
    print("Replaced openTacticalMode")
else:
    print("Could not find old_open_tac")

# 12. Update onTacRegionHover
old_hover = """    function onTacRegionHover(e, name) {{
      if (selectedRegionName) return;
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
    }}"""

new_hover = """    function onTacRegionHover(e, name) {{
      if (selectedRegionName) return;
      const tt = document.getElementById('tacRegionHoverTooltip');
      const ttName = document.getElementById('tacTooltipRegionName');
      const ttSub = document.getElementById('tacTooltipSub');
      if (!tt || !ttName || !ttSub) return;

      const r = regionsData[name];
      const count = (r ? r.cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === (name || '').toLowerCase()).length;
      ttName.textContent = name.toUpperCase();
      if (count > 0) {{
        ttSub.innerHTML = `<span class="text-red-400 font-bold">🚨 ${{count}} ACTIVE DOCKETS (LINKED TO SUSPECTS)</span>`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-red-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-red-500/60 transition-opacity duration-150";
      }} else {{
        ttSub.textContent = `Capital: ${{r ? r.capital : 'Ghana'}} • 0 Active Cases`;
        tt.className = "absolute pointer-events-none z-50 bg-slate-950/95 text-emerald-300 backdrop-blur-md px-3.5 py-2 rounded-xl text-xs font-mono-code shadow-2xl border border-emerald-500/60 transition-opacity duration-150";
      }}

      const stage = document.getElementById('tacMapStage');
      if (stage) {{
        const rect = stage.getBoundingClientRect();
        tt.style.left = (e.clientX - rect.left + 20) + 'px';
        tt.style.top = (e.clientY - rect.top - 10) + 'px';
        tt.classList.remove('hidden');
      }}
    }}"""

if old_hover in content:
    content = content.replace(old_hover, new_hover)
    print("Replaced onTacRegionHover")
else:
    print("Could not find old_hover")

with open(script_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated build_clean_dashboards.py successfully!")
