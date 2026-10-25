import os
import re

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

original_length = len(code)
print(f"Original length: {original_length}")

# 1. Remove og.css link
code = re.sub(
    r'\s*<!-- OpenGlobus 3D Geospatial Engine CSS -->\s*<link rel="stylesheet" href="\{\{\{\{ asset\(\'vendor/openglobus/og\.css\'\) \}\}\}\}">',
    '',
    code
)

# 2. Remove OpenGlobus toggle button in standard dashboard header
code = re.sub(
    r'\s*<!-- OpenGlobus 3D Whole Globe / Vector Switcher -->\s*<button type="button"\s*id="btnToggleEngineMode"[\s\S]*?</button>',
    '',
    code
)

# 3. Remove openGlobusContainer in standard map wrapper
code = re.sub(
    r'\s*<!-- OPENGLOBUS 3D HARDWARE-ACCELERATED GEOSPATIAL EARTH -->\s*<div id="openGlobusContainer"[\s\S]*?</div>\s*</div>\s*</div>\s*(?=\s*<!-- Tactical Quick Expand Button)',
    '\n',
    code
)

# 4. Remove Tactical Theatre OpenGlobus flight buttons in navbar
code = re.sub(
    r'\s*<!-- Dedicated Tactical Flight & Engine Controls in Navbar -->\s*<div class="flex items-center space-x-1\.5 md:space-x-2 font-mono-code text-xs">[\s\S]*?</div>\s*(?=\s*<div class="flex items-center space-x-3">)',
    '\n',
    code
)

# 5. Remove tacOpenGlobusContainer in Tactical Theatre Modal
code = re.sub(
    r'\s*<!-- OPENGLOBUS 3D HARDWARE-ACCELERATED WHOLE GLOBE IN TACTICAL THEATRE -->\s*<div id="tacOpenGlobusContainer"[\s\S]*?</div>\s*</div>\s*(?=\s*<!-- 3D Perspective Map Stage for Fullscreen Tactical -->)',
    '\n',
    code
)

# 6. Ensure tacMapStage is visible (remove hidden class)
code = code.replace(
    '<div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center hidden">',
    '<div id="tacMapStage" class="map-3d-stage relative w-full h-full flex items-center justify-center">'
)

# 7. Remove OpenGlobus ES Module Loader script
code = re.sub(
    r'\s*<!-- ======================================================== -->\s*<!-- OpenGlobus ES Module Loader -->\s*<script type="module">[\s\S]*?</script>',
    '',
    code
)

# 8. Remove OpenGlobus JS Controllers (Section 0 and 0.1) from client script
code = re.sub(
    r'\s*// ─+\s*// 0\. OPENGLOBUS 3D GEOSPATIAL ENGINE CONTROLLER[\s\S]*?(?=\s*let selectedRegionName = null;)',
    '\n',
    code
)

# 9. Clean up refreshGlobusEntities and refreshTacGlobusEntities calls in selectInvoiceById
code = re.sub(
    r'\s*if \(typeof refreshGlobusEntities === \'function\'\) \{\{\s*refreshGlobusEntities\(c\);\s*\}\}\s*if \(typeof refreshTacGlobusEntities === \'function\' && isTacticalModeOpen\) \{\{\s*refreshTacGlobusEntities\(c\);\s*\}\}',
    '',
    code
)

# 10. Clean up globusFlyToCase and tacGlobusFlyToCase calls in indicateCaseOnMap
code = re.sub(
    r'\s*if \(typeof globusFlyToCase === \'function\'\) \{\{\s*globusFlyToCase\(c\);\s*\}\}\s*if \(typeof tacGlobusFlyToCase === \'function\' && isTacticalModeOpen\) \{\{\s*tacGlobusFlyToCase\(c\);\s*\}\}',
    '',
    code
)

# 11. Clean up OpenGlobus timeouts in openTacticalMode
code = re.sub(
    r'\s*// Allow 50ms layout reflow for full modal width/height, then initialize or resize globe[\s\S]*?// Fly to active selected case or Whole Globe in Tactical Theatre[\s\S]*?\}, 350\);',
    '',
    code
)

# 12. Clean up initOpenGlobusEngine in DOMContentLoaded
code = re.sub(
    r'\s*if \(isGlobusModeActive && typeof initOpenGlobusEngine === \'function\'\) \{\{\s*initOpenGlobusEngine\(\);\s*\}\}\s*initOpenGlobusEngine\(\);',
    '',
    code
)

print(f"New length: {len(code)}")

# Check for any remaining occurrences of openglobus or globus
remaining_matches = re.findall(r'(?i)[a-z0-9_-]*(?:openglobus|globus)[a-z0-9_-]*', code)
print(f"Remaining globus tokens: {set(remaining_matches)}")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("Updated build_clean_dashboards.py successfully!")
