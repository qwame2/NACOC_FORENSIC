import os
import re

script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(script_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update focusRegionOnMap so both Standard and Tactical maps are 100% synchronized and zoomed
pattern_focus = r'    function focusRegionOnMap\(regName, isTac = false\) \{[\s\S]*?    function renderDistrictMap\(regName\) \{'

new_focus = """    function focusRegionOnMap(regName, isTac = false) {{
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

      // 6. Update Tactical Navigation Header, Left HUD & Bottom Deck
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

      // Show Tactical MMDA Districts Deck Drawer
      const tacDeck = document.getElementById('tacDistrictDeckDrawer');
      if (tacDeck) tacDeck.classList.remove('hidden');

      // 7. Render Districts on BOTH Standard and Tactical Maps
      renderDistrictMap(regName);
      renderTacDistrictMap(regName);
    }}

    function renderDistrictMap(regName) {"""

# Replace focusRegionOnMap using regex search
match_focus = re.search(pattern_focus, content)
if match_focus:
    content = content[:match_focus.start()] + new_focus + content[match_focus.end():]
    print("Replaced focusRegionOnMap successfully")
else:
    print("Could not match pattern_focus")

# 2. Update renderTacDistrictMap: fix firstCase.id crash, use onTacDistrictHoverByCode
pattern_render_tac = r'    function renderTacDistrictMap\(regName\) \{[\s\S]*?    function resetMapView\(isTac = false\) \{'

new_render_tac = """    function renderTacDistrictMap(regName) {{
      const group = document.getElementById('tacDistrictLayersGroup');
      if (!group) return;

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
          const isRed = count > 0;
          const firstCase = isRed ? item.cases[0] : null;
          const clickAttr = isRed && firstCase ? `onclick="selectInvoiceById(${{firstCase.id}}, false);"` : '';
          return `
            <div ${{clickAttr}} 
                 class="p-2 rounded-xl border cursor-pointer transition-all ${{isRed ? 'bg-red-950/70 border-red-500/60 hover:bg-red-900/60 text-red-200' : 'bg-slate-900/70 border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/30 text-slate-300'}}">
              <div class="flex items-center justify-between">
                <span class="font-bold text-white text-[11px] truncate">[${{d.code}}] ${{d.short_name}}</span>
                <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded' : 'text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded'}}">
                  ${{isRed ? `🚨 ${{count}} DOCKET` : '0 Active'}}
                </span>
              </div>
              <div class="text-[9.5px] text-slate-400 truncate mt-0.5">Cap: ${{d.capital}}</div>
              ${{isRed && firstCase ? `<div class="text-[9.5px] text-red-300 font-bold truncate mt-0.5">👤 ${{firstCase.suspect_name}} (${{firstCase.case_number}})</div>` : ''}}
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

      // 3. Render High-Clarity 2-Line Tactical SVG Markers on Map with Robust Code-Based Hover
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer group" 
               onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${{d.code}}')"
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

    function resetMapView(isTac = false) {{"""

match_render_tac = re.search(pattern_render_tac, content)
if match_render_tac:
    content = content[:match_render_tac.start()] + new_render_tac + content[match_render_tac.end():]
    print("Replaced renderTacDistrictMap successfully")
else:
    print("Could not match pattern_render_tac")

# 3. Update resetMapView so both maps are reset together
pattern_reset = r'    function resetMapView\(isTac = false\) \{[\s\S]*?    function toggle3DView\(isTac = false\) \{'

new_reset = """    function resetMapView(isTac = false) {{
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

    function toggle3DView(isTac = false) {{"""

match_reset = re.search(pattern_reset, content)
if match_reset:
    content = content[:match_reset.start()] + new_reset + content[match_reset.end():]
    print("Replaced resetMapView successfully")
else:
    print("Could not match pattern_reset")

# 4. Replace onTacDistrictHover with onTacDistrictHoverByCode
old_hover_funcs = r'    function toggleTacDistrictDeck\(\) \{[\s\S]*?    function onTacRegionHover\(e, name\) \{'

new_hover_funcs = """    function toggleTacDistrictDeck() {{
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
          <div class="mt-1 text-red-300 font-bold text-[11px]">🚨 ${{linked.length}} DOCKET(S) ACTIVE IN MMDA</div>
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
    }}

    function onTacDistrictLeave() {{
      const tt = document.getElementById('tacRegionHoverTooltip');
      if (tt) tt.classList.add('hidden');
    }}

    function onTacRegionHover(e, name) {{"""

match_hover = re.search(old_hover_funcs, content)
if match_hover:
    content = content[:match_hover.start()] + new_hover_funcs + content[match_hover.end():]
    print("Replaced hover functions successfully")
else:
    print("Could not match old_hover_funcs")

with open(script_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated build_clean_dashboards.py with complete fix!")
