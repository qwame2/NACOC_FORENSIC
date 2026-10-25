import re
import os

scratch_dir = os.path.dirname(os.path.abspath(__file__))
compiler_path = os.path.join(scratch_dir, 'build_clean_dashboards.py')

with open(compiler_path, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Change dashed radar rings to subtle solid rings (no dotted/dashed circles)
code = code.replace(
    'border: 1px dashed rgba(92, 86, 223, 0.25);',
    'border: 1px solid rgba(92, 86, 223, 0.15);'
)
code = code.replace(
    'border: 1px dashed rgba(16, 185, 129, 0.35);',
    'border: 1px solid rgba(16, 185, 129, 0.2);'
)
code = code.replace('stroke-dasharray="2, 8"', '')

# 2. Remove all dot indicator spans across headers, buttons, cards, and HUDs
# Remove <span class="w-1.5 h-1.5 rounded-full ..."></span>
code = re.sub(r'<span class="w-1\.5 h-1\.5 rounded-full[^>]*></span>\s*', '', code)
# Remove <span class="w-2 h-2 rounded-full ..."></span>
code = re.sub(r'<span class="w-2 h-2 rounded-full[^>]*></span>\s*', '', code)
# Remove <span class="w-2.5 h-2.5 rounded-full ..."></span>
code = re.sub(r'<span class="w-2\.5 h-2\.5 rounded-full[^>]*></span>\s*', '', code)
# Remove <span class="w-3 h-3 rounded-full ..."></span>
code = re.sub(r'<span class="w-3 h-3 rounded-full[^>]*></span>\s*', '', code)
# Remove <span id="districtHotspotIndicator" ...></span>
code = re.sub(r'<span id="districtHotspotIndicator"[^>]*></span>\s*', '', code)

# In JS dynamic strings:
code = code.replace('<span class="w-2 h-2 rounded-full bg-red-500"></span>\n                    ', '')
code = code.replace('<span class="w-2 h-2 rounded-full bg-red-500"></span>', '')
code = re.sub(r'<span class="w-1\.5 h-1\.5 rounded-full[^"]*"\s*>\s*</span>', '', code)
code = re.sub(r'<span class="w-2 h-2 rounded-full[^"]*"\s*>\s*</span>', '', code)

# 3. Remove hotspotInd JS assignments
code = code.replace("const hotspotInd = document.getElementById('districtHotspotIndicator');\n", "")
code = re.sub(r'if \(hotspotInd\)\s*\{\{[^}]*\}\}', '', code)

# 4. Remove all SVG district circles/dots on the map!
# Replace with clean, sharp rectangular badges and text labels (NO DOTS)
old_standard_districts_svg = """      // 1. Render Scaled, Clean SVG Pins directly on the Region Map
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <!-- Red District Core Node -->
              <circle cx="${{d.x}}" cy="${{d.y}}" r="3.2" fill="#ef4444" stroke="#ffffff" stroke-width="0.9" />
              <!-- MMDA District Header Tag -->
              <text x="${{d.x}}" y="${{d.y - 4.5}}" text-anchor="middle" font-size="4.2" font-weight="900" fill="#ffffff" stroke="#000000" stroke-width="0.9" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <!-- Linked Case Docket Pill -->
              <text x="${{d.x}}" y="${{d.y + 6.2}}" text-anchor="middle" font-size="3.4" font-weight="extrabold" fill="#fca5a5" stroke="#000000" stroke-width="0.8" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                🚨 ${{primaryCase.case_number}}
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="2.0" fill="#475569" stroke="#cbd5e1" stroke-width="0.5" class="group-hover:fill-indigo-500 transition-colors" />
              <text x="${{d.x}}" y="${{d.y - 3.2}}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#cbd5e1" stroke="#0f172a" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');"""

new_standard_districts_svg = """      // 1. Render Clean Rectangular MMDA Badges (NO CIRCULAR DOTS)
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <!-- Clean MMDA Sector Header -->
              <rect x="${{d.x - 22}}" y="${{d.y - 4.5}}" width="44" height="8.5" rx="1.5" fill="#181926" stroke="#ef4444" stroke-width="0.8" />
              <text x="${{d.x}}" y="${{d.y + 1.2}}" text-anchor="middle" font-size="3.6" font-weight="900" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <!-- Linked Case Tag -->
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

if old_standard_districts_svg in code:
    code = code.replace(old_standard_districts_svg, new_standard_districts_svg, 1)

# Tactical map SVG district pins: replace circles with clean tactical sector boxes
old_tac_districts_svg = """      // Render Tactical SVG Markers
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="3.2" fill="#ef4444" stroke="#ffffff" stroke-width="0.9" />
              <text x="${{d.x}}" y="${{d.y - 4.5}}" text-anchor="middle" font-size="4.5" font-weight="900" fill="#fca5a5" stroke="#000" stroke-width="1.0" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                TARGET: ${{d.short_name.toUpperCase()}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 6.5}}" text-anchor="middle" font-size="3.6" font-weight="bold" fill="#ef4444" stroke="#000" stroke-width="0.8" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                ${{c.case_number}} [${{c.exhibits_count || 1}} EXH]
              </text>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="2.0" fill="#10b981" stroke="#ffffff" stroke-width="0.5" />
              <text x="${{d.x}}" y="${{d.y - 3.5}}" text-anchor="middle" font-size="3.2" font-weight="bold" fill="#a7f3d0" stroke="#000" stroke-width="0.6" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');"""

new_tac_districts_svg = """      // Render Tactical SVG Markers (NO CIRCULAR DOTS)
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

if old_tac_districts_svg in code:
    code = code.replace(old_tac_districts_svg, new_tac_districts_svg, 1)

with open(compiler_path, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated: ALL dots (spans and circles) purged!")
