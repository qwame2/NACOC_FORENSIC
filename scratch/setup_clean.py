import json
import os
import re

scratch_dir = os.path.dirname(os.path.abspath(__file__))

with open(os.path.join(scratch_dir, 'ghana_regions_svg_optimized.json'), 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Compute exact padded viewBox for each region
region_bboxes = {}
for r in regions:
    coords = [float(x) for x in re.findall(r'[-+]?(?:\d*\.\d+|\d+)', r['path'])]
    xs, ys = coords[0::2], coords[1::2]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)
    w = max_x - min_x
    h = max_y - min_y
    pad_x = max(w * 0.18, 12)
    pad_y = max(h * 0.18, 12)
    vb_x = round(min_x - pad_x, 1)
    vb_y = round(min_y - pad_y, 1)
    vb_w = round(w + pad_x * 2, 1)
    vb_h = round(h + pad_y * 2, 1)
    region_bboxes[r['name']] = f"{vb_x} {vb_y} {vb_w} {vb_h}"

from generate_districts import districts_data

# Generate common SVG paths for all 16 regions
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
tactical_paths_html = ''.join(tactical_paths_markup)

# Extruded 3D base depth paths
extruded_paths_markup = []
for r in regions:
    extruded_paths_markup.append(f'''<path d="{r['path']}" fill="#0f111a" opacity="0.9" />''')
extruded_paths_html = ''.join(extruded_paths_markup)

# Direct region labels on map
region_labels_markup = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    region_labels_markup.append(f'''
            <g id="lbl-{reg_id}" class="pointer-events-none transition-opacity duration-300" onclick="selectRegionByName('{r['name']}')">
              <text x="{r['cx']}" y="{r['cy'] - 4}" 
                    text-anchor="middle" 
                    class="font-black text-[10px] tracking-wider fill-slate-200 drop-shadow-md select-none">{r['name'].upper()}</text>
              <text id="badge-{reg_id}" x="{r['cx']}" y="{r['cy'] + 9}" 
                    text-anchor="middle" 
                    class="font-mono font-bold text-[8.5px] fill-indigo-400 drop-shadow select-none">{r['cases']} Dockets</text>
            </g>''')
region_labels_html = ''.join(region_labels_markup)

# Tactical direct region labels
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
tactical_labels_html = ''.join(tactical_labels_markup)

# Serialize datasets for JS
regions_dataset_js = json.dumps({r['name']: r for r in regions})
districts_dataset_js = json.dumps(districts_data)
region_bboxes_js = json.dumps(region_bboxes)

print("Setup completed successfully. Generating full compiler script...")
