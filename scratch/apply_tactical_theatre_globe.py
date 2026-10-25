import os
import sys

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update HTML in Tactical Theatre Modal:
old_tac_stage = '''      <!-- 3D Perspective Map Stage for Fullscreen Tactical -->
      <div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center">'''

new_tac_stage = '''      <!-- OPENGLOBUS 3D HARDWARE-ACCELERATED WHOLE GLOBE IN TACTICAL THEATRE -->
      <div id="tacOpenGlobusContainer" class="absolute inset-0 w-full h-full z-15 bg-[#020617] overflow-hidden">
        <div id="tacGlobusCanvasWrapper" class="w-full h-full"></div>
        
        <!-- 3D Earth Telemetry & Coordinate HUD in Tactical Theatre (Top Center-Left) -->
        <div class="absolute top-20 left-6 z-40 pointer-events-none flex flex-col space-y-1.5">
          <div class="flex items-center space-x-2 bg-slate-950/90 border border-emerald-500/50 px-3.5 py-1.5 rounded-xl backdrop-blur-md text-xs font-mono-code text-emerald-300 shadow-2xl">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="font-extrabold text-white">OPENGLOBUS 3D TACTICAL THEATRE</span>
            <span>•</span>
            <span id="tacGlobusCoordinatesText">WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]</span>
          </div>
          <div id="tacGlobusTargetBadge" class="hidden items-center space-x-1.5 bg-pink-950/90 border border-pink-500/70 px-3.5 py-1.5 rounded-xl backdrop-blur-md text-xs font-mono-code text-pink-200 shadow-2xl">
            <span class="text-pink-400 font-black">🎯 TACTICAL TARGET LOCK:</span>
            <span id="tacGlobusTargetCaseText" class="font-bold text-white">NCC-2026-2572</span>
          </div>
        </div>

        <!-- 3D Flight Controls & Basemap Switcher in Tactical Theatre (Bottom Floating Bar) -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center space-x-2 pointer-events-auto">
          <button type="button" onclick="tacGlobusFlyToWholeGlobe()" class="px-3.5 py-1.5 rounded-xl bg-indigo-950/90 hover:bg-indigo-900 text-indigo-200 border border-indigo-500/60 backdrop-blur-md shadow-2xl font-bold flex items-center space-x-1.5 text-xs font-mono-code transition-all hover:scale-105 active:scale-95">
            <span>🌐 Whole Globe</span>
          </button>
          <button type="button" onclick="tacGlobusFlyToGhana()" class="px-3.5 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-emerald-300 border border-emerald-500/50 backdrop-blur-md shadow-2xl font-bold text-xs font-mono-code transition-all hover:scale-105 active:scale-95">
            🇬🇭 Focus Ghana Sector
          </button>
          <button type="button" onclick="tacGlobusToggleBasemap()" id="btnTacGlobusLayer" class="px-3.5 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md shadow-2xl text-xs font-mono-code transition-all">
            🛰️ Satellite / Streets
          </button>
          <button type="button" onclick="tacGlobusTiltCamera()" class="px-3.5 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md shadow-2xl text-xs font-mono-code transition-all">
            📐 3D Tilt
          </button>
          <button type="button" onclick="toggleTacEngineMode()" id="btnToggleTacEngineMode" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white border border-indigo-400 backdrop-blur-md shadow-2xl font-bold flex items-center space-x-1.5 text-xs font-mono-code transition-all hover:scale-105 active:scale-95" title="Switch between 3D Whole Globe and Vector 2D Radar">
            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-ping"></span>
            <span id="txtTacEngineModeLabel">🌐 3D WHOLE GLOBE</span>
          </button>
        </div>
      </div>

      <!-- 3D Perspective Map Stage for Fullscreen Tactical -->
      <div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center hidden">'''

if old_tac_stage in code:
    code = code.replace(old_tac_stage, new_tac_stage)
    print("Added tacOpenGlobusContainer to Tactical Theatre!")
elif 'tacOpenGlobusContainer' in code:
    print("tacOpenGlobusContainer already in HTML")

# 2. Add Tactical OpenGlobus Controller JavaScript right before initCharts
target_anchor = '''    // ──────────────────────────────────────────────────────────
    // INITIALIZE CHARTS (Forensic Data)
    // ──────────────────────────────────────────────────────────
    function initCharts() {{'''

tac_globus_js = '''    // ──────────────────────────────────────────────────────────
    // 0.1 TACTICAL THEATRE OPENGLOBUS 3D CONTROLLER
    // ──────────────────────────────────────────────────────────
    let isTacGlobusModeActive = true;
    let tacGlobusInstance = null;
    let tacGlobusSatLayer = null;
    let tacGlobusOsmLayer = null;
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

        tacGlobusMarkersLayer = new window.og.layer.Vector("Tactical Forensic Markers", {{
          pickingEnabled: true
        }});

        tacGlobusInstance = new window.og.Globe({{
          target: "tacGlobusCanvasWrapper",
          name: "Tactical Theatre 3D Earth",
          layers: [tacGlobusSatLayer, tacGlobusOsmLayer, tacGlobusMarkersLayer]
        }});

        if (tacGlobusInstance.planet && tacGlobusInstance.planet.light) {{
          tacGlobusInstance.planet.light.ambient = [0.95, 0.95, 0.95];
          tacGlobusInstance.planet.light.diffuse = [1.1, 1.1, 1.1];
        }}

        refreshTacGlobusEntities();

        setTimeout(() => {{
          if (selectedCaseId) {{
            const activeCase = liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
            if (activeCase) tacGlobusFlyToCase(activeCase);
            else tacGlobusFlyToWholeGlobe();
          }} else {{
            tacGlobusFlyToWholeGlobe();
          }}
        }}, 350);

      }} catch (err) {{
        console.error('[OpenGlobus Tactical] Initialization error:', err);
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
        if (btnTxt) btnTxt.textContent = '🌐 3D WHOLE GLOBE';
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
            else tacGlobusFlyToWholeGlobe();
          }} else {{
            tacGlobusFlyToWholeGlobe();
          }}
        }}
      }} else {{
        if (globusCont) globusCont.classList.add('hidden');
        if (stageCont) stageCont.classList.remove('hidden');
        if (btnTxt) btnTxt.textContent = '🗺️ VECTOR 2D RADAR';
        if (btn) {{
          btn.classList.remove('bg-indigo-600', 'text-white');
          btn.classList.add('bg-slate-900/90', 'text-indigo-300');
        }}
      }}
    }}

    function tacGlobusFlyToWholeGlobe() {{
      if (!tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      tacGlobusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000), // Whole Earth from orbital space (22,000 km)
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
      const locKey = ((c.raid_location || '') + ' ' + (c.suspect_name || '')).toLowerCase();
      let coords = null;
      for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
        if (locKey.includes(k)) {{
          coords = v;
          break;
        }}
      }}
      if (!coords && c.region && GHANA_REGION_GEO[c.region]) {{
        coords = GHANA_REGION_GEO[c.region];
      }}
      if (!coords) coords = GHANA_REGION_GEO['Greater Accra'];

      const targetAlt = coords.alt || 45000;
      tacGlobusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(coords.lon, coords.lat, targetAlt),
        new window.og.LonLat(coords.lon, coords.lat, 0)
      );

      const coordTxt = document.getElementById('tacGlobusCoordinatesText');
      if (coordTxt) {{
        coordTxt.textContent = `${{c.region.toUpperCase()}} • ${{coords.name || c.raid_location || 'TACTICAL SECTOR'}} • LAT ${{coords.lat.toFixed(4)}}°N, LON ${{Math.abs(coords.lon).toFixed(4)}}°W`;
      }}

      const badge = document.getElementById('tacGlobusTargetBadge');
      const targetTxt = document.getElementById('tacGlobusTargetCaseText');
      if (badge && targetTxt) {{
        badge.classList.remove('hidden');
        targetTxt.textContent = `${{c.case_number}} • ${{c.suspect_name}}`;
      }}

      refreshTacGlobusEntities(c);
    }}

    function tacGlobusToggleBasemap() {{
      if (!tacGlobusSatLayer || !tacGlobusOsmLayer) return;
      const btn = document.getElementById('btnTacGlobusLayer');
      if (tacGlobusSatLayer.getVisibility()) {{
        tacGlobusSatLayer.setVisibility(false);
        tacGlobusOsmLayer.setVisibility(true);
        if (btn) btn.textContent = '🗺️ Street / Satellite';
      }} else {{
        tacGlobusSatLayer.setVisibility(true);
        tacGlobusOsmLayer.setVisibility(false);
        if (btn) btn.textContent = '🛰️ Satellite / Street';
      }}
    }}

    function tacGlobusTiltCamera() {{
      if (!tacGlobusInstance || !tacGlobusInstance.planet || !window.og) return;
      tacGlobusCurrentTilt = (tacGlobusCurrentTilt === 0) ? 45 : 0;
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

    function refreshTacGlobusEntities(targetCase = null) {{
      if (!tacGlobusMarkersLayer || !window.og) return;
      try {{
        tacGlobusMarkersLayer.clear();

        let maxCaseload = 0;
        Object.keys(regionsData).forEach(k => {{
          const count = regionsData[k] ? (regionsData[k].cases || 0) : 0;
          if (count > maxCaseload) maxCaseload = count;
        }});

        for (const [rName, rGeo] of Object.entries(GHANA_REGION_GEO)) {{
          const rData = regionsData[rName];
          const cases = rData ? rData.cases : 0;
          const isHotspot = (cases === maxCaseload && maxCaseload > 0);

          const ent = new window.og.Entity({{
            name: `TAC_${{rName}}`,
            lonlat: new window.og.LonLat(rGeo.lon, rGeo.lat, 1000),
            label: {{
              text: `[${{rName.toUpperCase()}}: ${{cases}} DOCKETS]`,
              size: isHotspot ? 14 : 11,
              color: isHotspot ? '#fca5a5' : '#67e8f9',
              outlineColor: isHotspot ? '#991b1b' : '#042f2e',
              outline: isHotspot ? 4 : 2,
              offset: [0, -18]
            }}
          }});
          tacGlobusMarkersLayer.add(ent);
        }}

        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        if (activeCase) {{
          const locKey = ((activeCase.raid_location || '') + ' ' + (activeCase.suspect_name || '')).toLowerCase();
          let coords = null;
          for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
            if (locKey.includes(k)) {{
              coords = v;
              break;
            }}
          }}
          if (!coords && activeCase.region && GHANA_REGION_GEO[activeCase.region]) {{
            coords = GHANA_REGION_GEO[activeCase.region];
          }}
          if (coords) {{
            const targetEnt = new window.og.Entity({{
              name: `TAC_TARGET_${{activeCase.case_number}}`,
              lonlat: new window.og.LonLat(coords.lon, coords.lat, 2500),
              label: {{
                text: `🎯 TARGET: ${{activeCase.case_number}} • ${{activeCase.suspect_name}} [${{coords.name || activeCase.raid_location}}]`,
                size: 15,
                color: '#ffffff',
                outlineColor: '#ec4899',
                outline: 5,
                offset: [0, -28]
              }}
            }});
            tacGlobusMarkersLayer.add(targetEnt);
          }}
        }}
      }} catch (err) {{
        console.warn('[OpenGlobus Tactical] Error refreshing markers:', err);
      }}
    }}

'''

if target_anchor in code:
    code = code.replace(target_anchor, tac_globus_js + target_anchor)
    print("Added Tactical OpenGlobus Controller JavaScript!")
elif 'initTacOpenGlobusEngine' in code:
    print("initTacOpenGlobusEngine already in JS")
else:
    print("Could not match target_anchor!")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py patched successfully!")
