import os
import sys

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update initial static HUD coordinate text
code = code.replace(
    '<span id="globusCoordinatesText">GHANA NATIONAL SECTOR [WGS-84]</span>',
    '<span id="globusCoordinatesText">WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]</span>'
)

# 2. Prevent fetchRealtimeTelemetry from auto-zooming into first case on page load
# We want the 3D map to KEEP SHOWING THE WHOLE GLOBE until user explicitly clicks a case!
old_auto_select = """        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {
          selectInvoiceById(liveCases[0].case_number, true);
        } else {"""

new_auto_select = """        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {
          // On initial page load: Select first case for dossier details, but DO NOT zoom map in! Keep Whole Globe visible!
          selectInvoiceById(liveCases[0].case_number, false);
        } else {"""

if old_auto_select in code:
    code = code.replace(old_auto_select, new_auto_select)
    print("Updated auto_select to keep Whole Globe visible on page load!")
else:
    print("Notice: old_auto_select pattern check:")
    # Check if curly braces need doubling for f-string in build_clean_dashboards.py
    old_auto_select_doubled = """        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {{
          selectInvoiceById(liveCases[0].case_number, true);
        }} else {{"""
    
    new_auto_select_doubled = """        if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {{
          // On initial page load: Select first case for dossier details, but DO NOT zoom map in! Keep Whole Globe visible!
          selectInvoiceById(liveCases[0].case_number, false);
        }} else {{"""

    if old_auto_select_doubled in code:
        code = code.replace(old_auto_select_doubled, new_auto_select_doubled)
        print("Updated doubled auto_select to keep Whole Globe visible on page load!")
    else:
        print("Could not match auto_select!")

# 3. Fine-tune globusFlyToWholeGlobe altitude and position
old_whole_globe = """    function globusFlyToWholeGlobe() {{
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
    }}"""

new_whole_globe = """    function globusFlyToWholeGlobe() {{
      if (!globusInstance || !globusInstance.planet || !window.og) return;
      globusInstance.planet.camera.flyLonLat(
        new window.og.LonLat(0.0, 10.0, 22000000), // Whole Earth from orbital space (22,000 km)
        new window.og.LonLat(0.0, 10.0, 0),
        null,
        () => {{
          const coordTxt = document.getElementById('globusCoordinatesText');
          if (coordTxt) coordTxt.textContent = 'WHOLE 3D PLANET EARTH • SPACE ORBIT [ALT: 22,000 KM]';
        }}
      );
      const coordTxt = document.getElementById('globusCoordinatesText');
      if (coordTxt) coordTxt.textContent = 'ORBITING WHOLE 3D PLANET EARTH...';
      const badge = document.getElementById('globusTargetBadge');
      if (badge) badge.classList.add('hidden');
    }}"""

if old_whole_globe in code:
    code = code.replace(old_whole_globe, new_whole_globe)

# 4. In initOpenGlobusEngine, run globusFlyToWholeGlobe immediately at 300ms
old_init_timeout = """        setTimeout(() => {{
          globusFlyToWholeGlobe();
        }}, 500);"""

new_init_timeout = """        setTimeout(() => {{
          globusFlyToWholeGlobe();
        }}, 300);"""

if old_init_timeout in code:
    code = code.replace(old_init_timeout, new_init_timeout)

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated successfully!")
