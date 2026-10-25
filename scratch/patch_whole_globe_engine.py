import os

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update OpenGlobus container to be visible by default, and hide map-3d-stage by default
code = code.replace(
    '<div id="openGlobusContainer" class="hidden absolute inset-0 w-full h-full z-15 bg-slate-950 rounded-2xl overflow-hidden">',
    '<div id="openGlobusContainer" class="absolute inset-0 w-full h-full z-15 bg-slate-950 rounded-2xl overflow-hidden">'
)

code = code.replace(
    '<div class="map-3d-stage w-full h-full flex items-center justify-center p-2">',
    '<div class="map-3d-stage w-full h-full flex items-center justify-center p-2 hidden">'
)

# 2. Update engine toggle button
old_toggle_btn = '''          <!-- OpenGlobus 3D Geospatial Engine Toggle -->
          <button type="button" 
                  id="btnToggleEngineMode" 
                  onclick="toggleEngineMode()" 
                  class="px-3 py-1 rounded-full bg-slate-900/90 hover:bg-indigo-600/40 text-indigo-300 hover:text-white border border-indigo-500/50 text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-sm transition-all" 
                  title="Switch between OpenGlobus 3D WebGL Earth and Vector Tactical Radar">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span id="txtEngineModeLabel">3D SATELLITE GLOBE</span>
          </button>'''

new_toggle_btn = '''          <!-- OpenGlobus 3D Whole Globe / Vector Switcher -->
          <button type="button" 
                  id="btnToggleEngineMode" 
                  onclick="toggleEngineMode()" 
                  class="px-3.5 py-1 rounded-full bg-indigo-600 text-white border border-indigo-400/80 text-[11px] font-mono-code font-bold flex items-center space-x-1.5 shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95" 
                  title="Switch between OpenGlobus 3D WebGL Earth and Vector Tactical Radar">
            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-ping"></span>
            <span id="txtEngineModeLabel">🌐 3D WHOLE GLOBE</span>
          </button>'''

if old_toggle_btn in code:
    code = code.replace(old_toggle_btn, new_toggle_btn)
elif 'txtEngineModeLabel' in code:
    print("Toggle button already customized")

# 3. Update HUD flight controls inside OpenGlobus container
old_hud_controls = '''                <div class="pointer-events-auto flex items-center space-x-1.5 font-mono-code text-[10px]">
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

new_hud_controls = '''                <div class="pointer-events-auto flex items-center space-x-1.5 font-mono-code text-[10px]">
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

if old_hud_controls in code:
    code = code.replace(old_hud_controls, new_hud_controls)

# 4. Set isGlobusModeActive = true by default in the JS controller
code = code.replace(
    'let isGlobusModeActive = false;',
    'let isGlobusModeActive = true; // Show whole 3D Earth globe by default!'
)

# 5. In initOpenGlobusEngine(), make the initial camera flight show the WHOLE GLOBE (alt: 22,000,000m)
old_initial_flight = '''        setTimeout(() => {{
          globusFlyToGhana();
        }}, 500);'''

new_initial_flight = '''        setTimeout(() => {{
          globusFlyToWholeGlobe();
        }}, 500);'''

if old_initial_flight in code:
    code = code.replace(old_initial_flight, new_initial_flight)

# 6. Add globusFlyToWholeGlobe function right above globusFlyToGhana
old_fly_ghana = '    function globusFlyToGhana() {{'
new_fly_whole_and_ghana = '''    function globusFlyToWholeGlobe() {{
      if (!globusInstance || !globusInstance.planet || !window.og) return;
      globusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000), // Whole Earth from orbital space
        new window.og.LonLat(0.0, 10.0, 0),
        null,
        () => {{
          const coordTxt = document.getElementById('globusCoordinatesText');
          if (coordTxt) coordTxt.textContent = 'WHOLE 3D PLANET EARTH • ORBIT [ALT: 22,000 KM]';
        }}
      );
      const coordTxt = document.getElementById('globusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'ORBITING WHOLE 3D PLANET EARTH...';
      const badge = document.getElementById('globusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}

    function globusFlyToGhana() {{'''

if 'function globusFlyToWholeGlobe' not in code and old_fly_ghana in code:
    code = code.replace(old_fly_ghana, new_fly_whole_and_ghana, 1)

# 7. Update toggleEngineMode to sync UI correctly
old_toggle_fn = '''    function toggleEngineMode() {{
      isGlobusModeActive = !isGlobusModeActive;
      const globusCont = document.getElementById('openGlobusContainer');
      const stageCont = document.querySelector('.map-3d-stage');
      const btnTxt = document.getElementById('txtEngineModeLabel');

      if (isGlobusModeActive) {{
        if (globusCont) globusCont.classList.remove('hidden');
        if (stageCont) stageCont.classList.add('hidden');
        if (btnTxt) btnTxt.textContent = 'VECTOR 2D RADAR';

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
        if (btnTxt) btnTxt.textContent = '3D SATELLITE GLOBE';
      }}
    }}'''

new_toggle_fn = '''    function toggleEngineMode() {{
      isGlobusModeActive = !isGlobusModeActive;
      const globusCont = document.getElementById('openGlobusContainer');
      const stageCont = document.querySelector('.map-3d-stage');
      const btnTxt = document.getElementById('txtEngineModeLabel');
      const btn = document.getElementById('btnToggleEngineMode');

      if (isGlobusModeActive) {{
        if (globusCont) globusCont.classList.remove('hidden');
        if (stageCont) stageCont.classList.add('hidden');
        if (btnTxt) btnTxt.textContent = '🌐 3D WHOLE GLOBE';
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
            else globusFlyToWholeGlobe();
          }} else {{
            globusFlyToWholeGlobe();
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
    }}'''

if old_toggle_fn in code:
    code = code.replace(old_toggle_fn, new_toggle_fn)

# 8. Ensure initOpenGlobusEngine is called on DOMContentLoaded
old_dom = '''    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();'''

new_dom = '''    document.addEventListener('DOMContentLoaded', () => {{
      lucide.createIcons();
      initCharts();
      if (isGlobusModeActive && typeof initOpenGlobusEngine === 'function') {{
        initOpenGlobusEngine();
      }}'''

if old_dom in code:
    code = code.replace(old_dom, new_dom)

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated with Whole Globe 3D OpenGlobus Engine!")
