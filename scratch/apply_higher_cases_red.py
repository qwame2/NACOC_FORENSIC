import os
import re

script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(script_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update initial markup generation to only mark the region with the higher number of cases as hotspot
old_paths_section = """# Generate common SVG paths for all 16 regions
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

new_paths_section = """# Maximum caseload among regions to highlight ONLY the region with the higher number of cases as RED
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
tactical_paths_html = ''.join(tactical_paths_markup)"""

if old_paths_section in content:
    content = content.replace(old_paths_section, new_paths_section)
    print("Replaced paths section")
else:
    print("Could not find old_paths_section")

# 2. Update initial tactical labels markup
old_labels_section = """# Tactical direct region labels
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

new_labels_section = """# Tactical direct region labels (Highlighting ONLY higher caseload)
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
tactical_labels_html = ''.join(tactical_labels_markup)"""

if old_labels_section in content:
    content = content.replace(old_labels_section, new_labels_section)
    print("Replaced tactical labels section")
else:
    print("Could not find old_labels_section")

# 3. Update CSS: Only regions with higher caseload hotspot are RED; active regions without hotspot are EMERALD / INDIGO!
old_css = """    /* High-Caseload & Suspect-Linked Forensic Red Hotspot Region Polygons */
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
    }"""

new_css = """    /* High-Caseload Forensic Red Hotspot Region Polygons (ONLY Higher Caseload is RED) */
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
    }"""

# Remember doubled curly braces in the python file
old_css_doubled = old_css.replace('{', '{{').replace('}', '}}')
new_css_doubled = new_css.replace('{', '{{').replace('}', '}}')

if old_css_doubled in content:
    content = content.replace(old_css_doubled, new_css_doubled)
    print("Replaced CSS section")
else:
    print("Could not find old_css_doubled")

# 4. Update applyRealtimeData: Only region with HIGHER caseload gets isHotspot = true
old_realtime_counts = """      if (data.region_counts) {{
        const counts = data.region_counts || {{}};

        Object.keys(regionsData).forEach(rName => {{
          const count = counts[rName] || 0;
          const regId = rName.toLowerCase().replace(/\\s+/g, '-');
          const isHotspot = (count > 0); // Any region linked to suspects/cases is RED!
          
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
        }});"""

new_realtime_counts = """      if (data.region_counts) {{
        const counts = data.region_counts || {{}};
        let maxCaseload = 0;
        Object.values(counts).forEach(v => {{ if (v > maxCaseload) maxCaseload = v; }});

        Object.keys(regionsData).forEach(rName => {{
          const count = counts[rName] || 0;
          const regId = rName.toLowerCase().replace(/\\s+/g, '-');
          // ONLY the region with the HIGHER number of cases is RED
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
        }});"""

if old_realtime_counts in content:
    content = content.replace(old_realtime_counts, new_realtime_counts)
    print("Replaced applyRealtimeData counts")
else:
    print("Could not find old_realtime_counts")

# 5. Update focusRegionOnMap: calculate maxCaseload and only mark hotspot if regCases === maxCaseload
old_focus_reg = """      const districts = districtsData[regName] || [];
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
      }});"""

new_focus_reg = """      const districts = districtsData[regName] || [];
      const regCases = (regionsData[regName] ? regionsData[regName].cases : 0) || liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase()).length;
      
      // Determine if this region has the HIGHER number of cases across all regions
      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      }});
      const isHotspot = (regCases === maxCaseload && maxCaseload > 0);

      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      document.querySelectorAll(polySelector).forEach(p => {{
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
      }});"""

if old_focus_reg in content:
    content = content.replace(old_focus_reg, new_focus_reg)
    print("Replaced focusRegionOnMap logic")
else:
    print("Could not find old_focus_reg")

# 6. Update tactical drill title & Left HUD to distinguish higher caseload vs normal sector
old_drill_hud = """      }} else {{
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

new_drill_hud = """      }} else {{
        const tacNav = document.getElementById('tacDistrictNavHeader');
        const tacTitle = document.getElementById('tacDistrictDrillTitle');
        const tacBadgeBox = document.getElementById('tacDistrictDrillTitleBadge');
        if (tacNav && tacTitle) {{
          tacNav.classList.remove('hidden');
          if (isHotspot) {{
            tacTitle.innerHTML = `<span class="text-red-400 font-black">🚨 HIGHER CASELOAD HOTSPOT:</span> ${{regName.toUpperCase()}} MMDA RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
            if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-red-950/90 text-white border border-red-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
          }} else {{
            tacTitle.innerHTML = `<span class="text-emerald-400 font-black">TACTICAL SECTOR:</span> ${{regName.toUpperCase()}} MMDA RADAR (${{districts.length}} DISTRICTS • ${{regCases}} DOCKETS)`;
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

if old_drill_hud in content:
    content = content.replace(old_drill_hud, new_drill_hud)
    print("Replaced drill HUD section")
else:
    print("Could not find old_drill_hud")

# 7. Update resetMapView: only mark region with higher caseload as hotspot
old_reset_map = """      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
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

new_reset_map = """      const polySelector = isTac ? '.tactical-region-polygon' : '.region-polygon';
      let maxCaseload = 0;
      Object.keys(regionsData).forEach(k => {{
        const cVal = regionsData[k] ? (regionsData[k].cases || 0) : 0;
        if (cVal > maxCaseload) maxCaseload = cVal;
      }});

      document.querySelectorAll(polySelector).forEach(p => {{
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
      }});"""

if old_reset_map in content:
    content = content.replace(old_reset_map, new_reset_map)
    print("Replaced resetMapView section")
else:
    print("Could not find old_reset_map")

# 8. Update openTacticalMode: target region with higher number of cases (e.g. Greater Accra)
old_open_tac_mode = """      // Resolve the suspect that is currently selected, or default to first active case
      const activeCase = liveCases.find(c => c.id == selectedCaseId) || liveCases[0];
      const targetReg = selectedRegionName || (activeCase ? activeCase.region : 'Greater Accra');

      if (targetReg) {{
        selectRegionByName(targetReg, true);
      }} else {{
        resetMapView(true);
      }}"""

new_open_tac_mode = """      // Find region with higher number of cases
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
      }}"""

if old_open_tac_mode in content:
    content = content.replace(old_open_tac_mode, new_open_tac_mode)
    print("Replaced openTacticalMode logic")
else:
    print("Could not find old_open_tac_mode")

# 9. Update onTacRegionHover
old_hover_tac = """    function onTacRegionHover(e, name) {{
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

new_hover_tac = """    function onTacRegionHover(e, name) {{
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
        ttSub.innerHTML = `<span class="text-red-400 font-bold">🚨 HIGHER CASELOAD HOTSPOT (${{count}} DOCKETS)</span>`;
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
    }}"""

if old_hover_tac in content:
    content = content.replace(old_hover_tac, new_hover_tac)
    print("Replaced onTacRegionHover logic")
else:
    print("Could not find old_hover_tac")

with open(script_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated build_clean_dashboards.py for Higher Caseload Red Region successfully!")
