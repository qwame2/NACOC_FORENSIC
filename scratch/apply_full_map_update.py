import os

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Add renderTacticalCasesStream right after renderInvoicesList
target_end_invoices = '''        return `
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
    }}'''

new_end_invoices_and_tac_stream = target_end_invoices + '''

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
              <span class="w-2 h-2 rounded-full ${{isSelected ? 'bg-indigo-300 animate-pulse' : 'bg-emerald-400'}}"></span>
              <div class="truncate">
                <span class="font-bold text-white">${{c.case_number}}</span>
                <span class="text-[10px] ${{isSelected ? 'text-indigo-200 font-semibold' : 'text-slate-400'}} block">${{c.suspect_name}} • ${{c.region}}</span>
              </div>
            </div>
            <span class="text-[10px] px-1.5 py-0.5 rounded ${{isSelected ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-300'}} font-bold">${{c.status_badge}}</span>
          </div>
        `;
      }}).join('');
    }}'''

if target_end_invoices in code:
    code = code.replace(target_end_invoices, new_end_invoices_and_tac_stream, 1)
    print("Added renderTacticalCasesStream successfully!")
else:
    print("Could not find target_end_invoices block")

# 2. Fix setDossierSuspect case lookup by case_number/id
old_suspect_fn = 'const c = liveCases.find(function(item) {{ return item.id == selectedCaseId; }});'
new_suspect_fn = 'const c = liveCases.find(function(item) {{ return item.case_number === selectedCaseId || String(item.id) === String(selectedCaseId); }});'
if old_suspect_fn in code:
    code = code.replace(old_suspect_fn, new_suspect_fn)
    print("Fixed setDossierSuspect lookup!")

# 3. Replace the entire map engine block (selectInvoiceById through renderTacDistrictMap)
old_map_engine_start = '    function selectInvoiceById(caseRef, centerMap = true) {{'
old_map_engine_end = '    function resetMapView(isTac = false) {{'

idx_start = code.find(old_map_engine_start)
idx_end = code.find(old_map_engine_end)

if idx_start != -1 and idx_end != -1:
    new_map_engine = '''    function selectInvoiceById(caseRef, centerMap = true) {{
      const c = liveCases.find(item => item.case_number === caseRef || String(item.id) === String(caseRef));
      if (!c) return;

      selectedCaseId = c.case_number;

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

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
          drillTitle.innerHTML = `<span class="text-pink-400 font-extrabold tracking-wide">🎯 TARGET RADAR:</span> ${{regName.toUpperCase()}} • <span class="text-white font-mono-code font-bold">🚨 ${{activeCase.case_number}}</span> <span class="text-indigo-200 font-semibold">(${{activeCase.suspect_name}})</span>`;
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
        if (activeCase && activeCase.region && activeCase.region.toLowerCase() === regName.toLowerCase()) {{
          tacTitle.innerHTML = `<span class="text-pink-400 font-black">🎯 TARGET RADAR:</span> ${{regName.toUpperCase()}} • <span class="text-white font-mono-code font-bold">🚨 ${{activeCase.case_number}}</span> (${{activeCase.suspect_name}}) • ${{activeCase.raid_location || 'TACTICAL SECTOR'}}`;
          if (tacBadgeBox) tacBadgeBox.className = "px-4 py-2 rounded-xl bg-pink-950/90 text-white border border-pink-500/60 backdrop-blur-md text-xs font-mono-code font-bold flex items-center space-x-2 shadow-2xl";
        }} else if (isHotspot) {{
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
              <circle cx="${{d.x}}" cy="${{d.y}}" r="16" fill="none" stroke="#ec4899" stroke-width="1.0" opacity="0.75" stroke-dasharray="3,2" />
              <rect x="${{d.x - 29}}" y="${{d.y - 7.5}}" width="58" height="15" rx="3" fill="#1b0716" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 6px rgba(244,63,94,0.6))" />
              <text x="${{d.x}}" y="${{d.y - 1.8}}" text-anchor="middle" font-size="3.7" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                🎯 [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.6}}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{activeCase.case_number}} • ${{activeCase.suspect_name.split(' ')[0]}}
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
                    <span class="font-extrabold text-white text-[11px] truncate">🎯 [${{d.code}}] ${{d.short_name}}</span>
                  </div>
                  <div class="text-[10px] text-pink-200 font-mono-code font-bold truncate mt-0.5">
                    🚨 ${{activeCase.case_number}} • ${{activeCase.suspect_name}} (${{activeCase.exhibits_count || 1}} Exh)
                  </div>
                </div>
                <span class="text-[9px] px-2 py-0.5 rounded bg-pink-500 text-white font-black tracking-wider flex-shrink-0 animate-pulse">
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
                    🚨 ${{c.case_number}} • ${{c.suspect_name}} (${{c.exhibits_count || 1}} Exh)
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
          let badgeMarkup = '<span class="text-[9px] font-mono-code font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">0 Active</span>';

          if (isTarget) {{
            cardCls = 'bg-gradient-to-r from-pink-950/90 to-red-950/90 border-2 border-pink-500 text-pink-100 shadow-lg shadow-pink-500/20';
            badgeMarkup = '<span class="text-[9px] font-mono-code font-black text-white bg-pink-500 px-2 py-0.5 rounded animate-pulse">🎯 TARGET</span>';
          }} else if (isRed) {{
            cardCls = 'bg-red-950/70 border-red-500/60 hover:bg-red-900/60 text-red-200';
            badgeMarkup = `<span class="text-[9px] font-mono-code font-bold text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded">🚨 ${{count}} DOCKET</span>`;
          }}

          return `
            <div ${{clickAttr}} class="p-2 rounded-xl border cursor-pointer transition-all ${{cardCls}}">
              <div class="flex items-center justify-between">
                <span class="font-bold text-white text-[11px] truncate">${{isTarget ? '🎯 ' : ''}}[${{d.code}}] ${{d.short_name}}</span>
                ${{badgeMarkup}}
              </div>
              <div class="text-[9.5px] text-slate-400 truncate mt-0.5">Cap: ${{d.capital}}</div>
              ${{firstCase ? `<div class="text-[9.5px] ${{isTarget ? 'text-pink-200 font-extrabold' : 'text-red-300 font-bold'}} truncate mt-0.5">👤 ${{firstCase.suspect_name}} (${{firstCase.case_number}})</div>` : ''}}
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
                  <span class="font-black text-white text-[11px] truncate">🎯 [${{d.code}}] ${{d.short_name}}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-pink-500 text-white font-black flex-shrink-0 animate-pulse">TARGET</span>
                </div>
                <div class="text-[10px] text-pink-200 font-extrabold truncate mt-0.5">
                  🚨 ${{activeCase.case_number}} • ${{activeCase.suspect_name}}
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
        const isTarget = activeCase && linked.some(c => c.case_number === activeCase.case_number);

        if (isTarget) {{
          return `
            <g class="tac-district-marker cursor-pointer group active-target-tac" 
               onclick="event.stopPropagation(); selectInvoiceById('${{activeCase.case_number}}', false);"
               onmouseenter="onTacDistrictHoverByCode(event, '${{d.code}}')"
               onmouseleave="onTacDistrictLeave()">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="16" fill="none" stroke="#ec4899" stroke-width="1.0" opacity="0.8" stroke-dasharray="3,2" />
              <rect x="${{d.x - 30}}" y="${{d.y - 8.0}}" width="60" height="16" rx="3" fill="#20051a" stroke="#f43f5e" stroke-width="1.6" filter="drop-shadow(0 0 8px rgba(244,63,94,0.7))" />
              <text x="${{d.x}}" y="${{d.y - 2.0}}" text-anchor="middle" font-size="3.8" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                🎯 [TARGET] ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 4.8}}" text-anchor="middle" font-size="3.2" font-weight="900" fill="#fecdd3" stroke="#000000" stroke-width="0.7" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{activeCase.case_number}} • ${{activeCase.suspect_name.split(' ')[0]}}
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

    '''
    code = code[:idx_start] + new_map_engine + code[idx_end:]
    print("Replaced map engine successfully!")
else:
    print(f"Could not locate map engine start ({idx_start}) or end ({idx_end})")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("Saved updated build_clean_dashboards.py!")
