import json
import os

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Generate common SVG paths for all 16 regions
def get_region_paths_markup(unit_accent):
    paths = []
    for r in regions:
        reg_id = r['name'].lower().replace(' ', '-')
        paths.append(f'''
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
                  class="region-polygon transition-all duration-300 cursor-pointer fill-slate-50/90 stroke-slate-300/80 hover:fill-{unit_accent}-100/90 hover:stroke-{unit_accent}-500 hover:drop-shadow-[0_4px_12px_rgba(0,0,0,0.15)]"
                  stroke-width="1.3"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  onclick="selectRegionByName('{r['name']}')"
                  onmouseenter="onRegionHover(event, '{r['name']}')"
                  onmouseleave="onRegionLeave()" />''')
    return ''.join(paths)

def get_extruded_paths():
    return ''.join([f'''<path d="{r['path']}" fill="#cbd5e1" opacity="0.85" />''' for r in regions])

extruded_paths_markup = get_extruded_paths()

print("Base setup ready, regions count:", len(regions))
