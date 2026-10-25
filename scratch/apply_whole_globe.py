import os

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update OpenGlobus Container HTML to be visible by default (showing the whole 3D globe)
old_globus_container = '<div id="openGlobusContainer" class="hidden absolute inset-0 w-full h-full z-15 bg-slate-950 rounded-2xl overflow-hidden">'
new_globus_container = '<div id="openGlobusContainer" class="absolute inset-0 w-full h-full z-15 bg-slate-950 rounded-2xl overflow-hidden">'

if old_globus_container in code:
    code = code.replace(old_globus_container, new_globus_container)
    print("Made OpenGlobus visible by default!")
else:
    print("Could not find old_globus_container")

# 2. Update toggle button style for OpenGlobus
old_btn_engine = '''          <!-- OpenGlobus 3D Geospatial Engine Toggle -->
          <button type="button" 
                  id="btnToggleEngineMode" 
                  onclick="toggleEngineMode()" 
                  class="px-3 py-1 rounded-full bg-slate-900/90 hover:bg-indigo-600/40 text-indigo-300 hover:text-white border border-indigo-500/50 text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-sm transition-all" 
                  title="Switch between OpenGlobus 3D WebGL Earth and Vector Tactical Radar">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span id="txtEngineModeLabel">3D SATELLITE GLOBE</span>
          </button>'''

new_btn_engine = '''          <!-- OpenGlobus 3D Whole Globe / Vector Switcher -->
          <button type="button" 
                  id="btnToggleEngineMode" 
                  onclick="toggleEngineMode()" 
                  class="px-3.5 py-1 rounded-full bg-indigo-600 text-white border border-indigo-400/80 text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95" 
                  title="Switch between OpenGlobus 3D WebGL Earth and Vector Tactical Radar">
            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-ping"></span>
            <span id="txtEngineModeLabel">🌐 3D WHOLE GLOBE</span>
          </button>'''

if old_btn_engine in code:
    code = code.replace(old_btn_engine, new_btn_engine)
    print("Updated engine toggle button markup!")

# 3. Update HUD controls in OpenGlobus container
old_controls = '''                <div class="pointer-events-auto flex items-center space-x-1.5 font-mono-code text-[10px]">
                  <button type="button" onclick="globusFlyToGhana()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-emerald-400 backdrop-blur-md transition-all shadow-md">
                    🌐 Orbit Ghana
                  </button>
                  <button type="button" onclick="globusToggleBasemap()" id="btnGlobusLayer" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    🛰️ Satellite / Map
                  </button>
                  <button type="button" onclick="globusTiltCamera()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    📐 3D Tilt
                  </button>
                </div>'''

new_controls = '''                <div class="pointer-events-auto flex items-center space-x-1.5 font-mono-code text-[10px]">
                  <button type="button" onclick="globusFlyToWholeGlobe()" class="px-2.5 py-1 rounded-lg bg-indigo-950/90 hover:bg-indigo-900 text-indigo-200 border border-indigo-500/50 backdrop-blur-md transition-all shadow-md font-bold flex items-center space-x-1">
                    <span>🌐 Whole Globe</span>
                  </button>
                  <button type="button" onclick="globusFlyToGhana()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-emerald-300 border border-emerald-500/40 backdrop-blur-md transition-all shadow-md font-bold">
                    🇬🇭 Focus Ghana
                  </button>
                  <button type="button" onclick="globusToggleBasemap()" id="btnGlobusLayer" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    🛰️ Satellite / Streets
                  </button>
                  <button type="button" onclick="globusTiltCamera()" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700 hover:border-indigo-400 backdrop-blur-md transition-all shadow-md">
                    📐 3D Tilt
                  </button>
                </div>'''

if old_controls in code:
    code = code.replace(old_controls, new_controls)
    print("Updated OpenGlobus HUD controls markup!")

# 4. Update indicateCaseOnMap to also call flyToCaseInGlobus(c)
old_indicate = '''    function indicateCaseOnMap(c) {{
      if (!c || !c.region) return;
      selectedRegionName = c.region;
      focusRegionOnMap(c.region, isTacticalModeOpen, c);
      renderDistrictMap(c.region, c);
      renderTacDistrictMap(c.region, c);
    }}'''

new_indicate = '''    function indicateCaseOnMap(c) {{
      if (!c || !c.region) return;
      selectedRegionName = c.region;
      focusRegionOnMap(c.region, isTacticalModeOpen, c);
      renderDistrictMap(c.region, c);
      renderTacDistrictMap(c.region, c);
      if (isGlobusEngineActive) {{
        flyToCaseInGlobus(c);
      }}
    }}'''

if old_indicate in code:
    code = code.replace(old_indicate, new_indicate)
    print("Updated indicateCaseOnMap with OpenGlobus 3D camera flight!")

# 5. Insert OpenGlobus Engine Controller into the client script
target_init_charts = '    function initCharts() {{'

globus_engine_code = '''    // ──────────────────────────────────────────────────────────
    // OPENGLOBUS 3D GEOSPATIAL EARTH ENGINE
    // ──────────────────────────────────────────────────────────
    let globusGlobe = null;
    let globusSatLayer = null;
    let globusOsmLayer = null;
    let globusMarkersLayer = null;
    let isGlobusEngineActive = true; // Show whole 3D Earth globe by default!

    const REGION_COORDS = {{
      "Greater Accra": {{ lon: -0.1969, lat: 5.6037, alt: 75000 }},
      "Ashanti": {{ lon: -1.6244, lat: 6.6885, alt: 95000 }},
      "Western": {{ lon: -1.7554, lat: 4.9340, alt: 90000 }},
      "Eastern": {{ lon: -0.2588, lat: 6.0945, alt: 90000 }},
      "Central": {{ lon: -1.2466, lat: 5.1054, alt: 85000 }},
      "Northern": {{ lon: -0.8393, lat: 9.4008, alt: 130000 }},
      "Volta": {{ lon: 0.4713, lat: 6.6101, alt: 95000 }},
      "Upper East": {{ lon: -0.8514, lat: 10.7856, alt: 95000 }},
      "Upper West": {{ lon: -2.5019, lat: 10.0601, alt: 95000 }},
      "Bono": {{ lon: -2.3268, lat: 7.3399, alt: 90000 }},
      "Bono East": {{ lon: -1.9400, lat: 7.5833, alt: 95000 }},
      "Ahafo": {{ lon: -2.5167, lat: 6.8000, alt: 85000 }},
      "Western North": {{ lon: -2.4833, lat: 6.2000, alt: 90000 }},
      "Oti": {{ lon: 0.1794, lat: 8.0689, alt: 95000 }},
      "Savannah": {{ lon: -1.8258, lat: 9.0833, alt: 120000 }},
      "North East": {{ lon: -0.3700, lat: 10.5300, alt: 95000 }}
    }};

    const MMDA_COORDS = {{
      "ayawaso": {{ lon: -0.1869, lat: 5.6508, alt: 38000, name: "Ayawaso West (Legon)" }},
      "legon": {{ lon: -0.1869, lat: 5.6508, alt: 38000, name: "Ayawaso West (Legon)" }},
      "accra": {{ lon: -0.2057, lat: 5.5560, alt: 38000, name: "Accra Metro (AMA)" }},
      "kumasi": {{ lon: -1.6244, lat: 6.6885, alt: 45000, name: "Kumasi Metro (KMA)" }},
      "tema": {{ lon: -0.0166, lat: 5.6698, alt: 38000, name: "Tema Metro" }},
      "cape coast": {{ lon: -1.2466, lat: 5.1054, alt: 38000, name: "Cape Coast Metro" }},
      "takoradi": {{ lon: -1.7554, lat: 4.9340, alt: 38000, name: "Sekondi-Takoradi Metro" }},
      "tamale": {{ lon: -0.8393, lat: 9.4008, alt: 45000, name: "Tamale Metro" }},
      "sunyani": {{ lon: -2.3268, lat: 7.3399, alt: 42000, name: "Sunyani Municipal" }},
      "koforidua": {{ lon: -0.2588, lat: 6.0945, alt: 38000, name: "New Juaben (Koforidua)" }}
    }};

    async function initOpenGlobusEngine() {{
      const target = document.getElementById('globusCanvasWrapper');
      if (!target || globusGlobe) return;

      try {{
        let ogModule = window.og;
        if (!ogModule) {{
          try {{
            ogModule = await import('/vendor/openglobus/og.es.js');
          }} catch(e) {{
            ogModule = await import('./vendor/openglobus/og.es.js');
          }}
          window.og = ogModule;
        }}

        // 1. High-Resolution Photorealistic Satellite Layer
        globusSatLayer = new ogModule.layer.XYZ("Esri Satellite", {{
          isBaseLayer: true,
          url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
          visibility: true
        }});

        // 2. OpenStreetMap Layer (Toggleable)
        globusOsmLayer = new ogModule.layer.XYZ("OpenStreetMap", {{
          isBaseLayer: false,
          url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
          visibility: false
        }});

        // 3. Vector Layer for Tactical Radar Entities
        globusMarkersLayer = new ogModule.layer.Vector("Tactical Radar", {{
          pickingEnabled: true
        }});

        // 4. Instantiate Globe
        globusGlobe = new ogModule.Globe({
          target: "globusCanvasWrapper",
          name: "NACOC Whole Earth 3D Globe",
          layers: [globusSatLayer, globusOsmLayer, globusMarkersLayer]
        });

        // Configure Ambient & Solar Light for clear planetary illumination
        if (globusGlobe.planet && globusGlobe.planet.light) {{
          globusGlobe.planet.light.ambient = [0.95, 0.95, 0.95];
          globusGlobe.planet.light.diffuse = [1.1, 1.1, 1.1];
        }}

        // 5. Initial View: SHOW WHOLE GLOBE FLOATING IN SPACE!
        // Altitude 22,000,000m (22,000 km) viewing the complete planet Earth
        setTimeout(() => {{
          globusFlyToWholeGlobe();
        }}, 400);

      }} catch (err) {{
        console.warn('OpenGlobus initialization fallback:', err);
      }}
    }}

    function globusFlyToWholeGlobe() {{
      if (!globusGlobe || !globusGlobe.planet || !window.og) return;
      globusGlobe.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000), // Whole Earth from orbital space
        new window.og.LonLat(0.0, 10.0, 0),
        null,
        () => {{
          updateGlobusHudText("WHOLE 3D PLANET EARTH • ORBIT [ALT: 22,000 KM]", null);
        }}
      );
      updateGlobusHudText("ORBITING WHOLE 3D PLANET EARTH...", null);
    }}

    function globusFlyToGhana() {{
      if (!globusGlobe || !globusGlobe.planet || !window.og) return;
      globusGlobe.planet.camera.flyLonLat(
        new window.og.LonLat(-1.0232, 7.9465, 850000), // Ghana National Sector
        new window.og.LonLat(-1.0232, 7.9465, 0),
        null,
        () => {{
          updateGlobusHudText("GHANA NATIONAL COMMAND SECTOR [-1.02°E, 7.95°N]", null);
        }}
      );
      updateGlobusHudText("APPROACHING GHANA SECTOR...", null);
    }}

    function globusFlyToRegion(regName) {{
      if (!globusGlobe || !globusGlobe.planet || !window.og) return;
      const c = REGION_COORDS[regName];
      if (!c) {{
        globusFlyToGhana();
        return;
      }}
      globusGlobe.planet.camera.flyLonLat(
        new window.og.LonLat(c.lon, c.lat, c.alt),
        new window.og.LonLat(c.lon, c.lat, 0),
        null,
        () => {{
          updateGlobusHudText(`3D REGION RADAR: ${{regName.toUpperCase()}} [ALT: ${{(c.alt/1000).toFixed(0)}} KM]`, null);
        }}
      );
      updateGlobusHudText(`APPROACHING ${{regName.toUpperCase()}} SECTOR...`, null);
    }}

    function flyToCaseInGlobus(c) {{
      if (!globusGlobe || !globusGlobe.planet || !window.og || !c) return;

      let targetLon = -1.0232;
      let targetLat = 7.9465;
      let targetAlt = 45000;
      let locName = c.raid_location || c.region || "Ghana";

      const locKey = (c.raid_location || '').toLowerCase();
      let matched = false;
      for (const [k, v] of Object.entries(MMDA_COORDS)) {{
        if (locKey.includes(k)) {{
          targetLon = v.lon;
          targetLat = v.lat;
          targetAlt = v.alt;
          locName = v.name;
          matched = true;
          break;
        }}
      }}

      if (!matched && c.region && REGION_COORDS[c.region]) {{
        targetLon = REGION_COORDS[c.region].lon;
        targetLat = REGION_COORDS[c.region].lat;
        targetAlt = REGION_COORDS[c.region].alt;
      }}

      globusGlobe.planet.camera.flyLonLat(
        new window.og.LonLat(targetLon, targetLat, targetAlt),
        new window.og.LonLat(targetLon, targetLat, 0),
        null,
        () => {{
          updateGlobusHudText(`3D SATELLITE LOCK: ${{c.case_number}} • ${{c.suspect_name}} • ${{locName}} [ALT: ${{(targetAlt/1000).toFixed(0)}} KM]`, c);
        }}
      );

      updateGlobusHudText(`FLYING TO TARGET: ${{c.case_number}} (${{c.suspect_name}})...`, c);
    }}

    function updateGlobusHudText(text, targetCase) {{
      const el = document.getElementById('globusCoordinatesText');
      if (el) el.textContent = text;

      const badge = document.getElementById('globusTargetBadge');
      const badgeText = document.getElementById('globusTargetCaseText');
      if (badge && badgeText) {{
        if (targetCase) {{
          badge.classList.remove('hidden');
          badge.classList.add('flex');
          badgeText.textContent = `${{targetCase.case_number}} • ${{targetCase.suspect_name}}`;
        }} else {{
          badge.classList.add('hidden');
          badge.classList.remove('flex');
        }}
      }}
    }}

    function globusToggleBasemap() {{
      if (!globusSatLayer || !globusOsmLayer) return;
      const isSat = globusSatLayer.getVisibility();
      globusSatLayer.setVisibility(!isSat);
      globusOsmLayer.setVisibility(isSat);
      const btn = document.getElementById('btnGlobusLayer');
      if (btn) btn.textContent = isSat ? '🛰️ Switch to Satellite' : '🗺️ Switch to Street Map';
    }}

    function globusTiltCamera() {{
      if (!globusGlobe || !globusGlobe.planet) return;
      const cam = globusGlobe.planet.camera;
      if (cam && typeof cam.rotatePitch === 'function') {{
        cam.rotatePitch(0.35);
      }}
    }}

    function toggleEngineMode() {{
      isGlobusEngineActive = !isGlobusEngineActive;
      const globusCont = document.getElementById('openGlobusContainer');
      const svgStage = document.querySelector('.map-3d-stage');
      const btn = document.getElementById('btnToggleEngineMode');
      const txt = document.getElementById('txtEngineModeLabel');

      if (isGlobusEngineActive) {{
        if (globusCont) globusCont.classList.remove('hidden');
        if (svgStage) svgStage.classList.add('opacity-0', 'pointer-events-none');
        if (txt) txt.textContent = '🌐 3D WHOLE GLOBE';
        if (btn) {{
          btn.classList.add('bg-indigo-600', 'text-white');
          btn.classList.remove('bg-slate-900/90', 'text-indigo-300');
        }}
        if (!globusGlobe) {{
          initOpenGlobusEngine();
        }}
      }} else {{
        if (globusCont) globusCont.classList.add('hidden');
        if (svgStage) svgStage.classList.remove('opacity-0', 'pointer-events-none');
        if (txt) txt.textContent = '🗺️ VECTOR 2D RADAR';
        if (btn) {{
          btn.classList.remove('bg-indigo-600', 'text-white');
          btn.classList.add('bg-slate-900/90', 'text-indigo-300');
        }}
      }}
    }}

'''

if 'function initOpenGlobusEngine' not in code and target_init_charts in code:
    code = code.replace(target_init_charts, globus_engine_code + target_init_charts, 1)
    print("Added OpenGlobus engine controller functions!")

# 6. Call initOpenGlobusEngine on DOMContentLoaded
old_dom_ready = '''    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();'''

new_dom_ready = '''    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();
      initOpenGlobusEngine();'''

if old_dom_ready in code:
    code = code.replace(old_dom_ready, new_dom_ready, 1)
    print("Added initOpenGlobusEngine call to DOMContentLoaded!")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("Saved updated build_clean_dashboards.py successfully!")
