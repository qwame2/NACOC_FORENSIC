import json

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Create extrusion wall path (union or duplicated offset paths)
# For the 3D relief side wall effect, we can combine all paths or render an offset base plate with gradient
extrusion_paths_svg = []
for r in regions:
    extrusion_paths_svg.append(f'<path d="{r["path"]}" fill="#CBD5E1" />')

regions_svg = []
for r in regions:
    reg_id = r['name'].lower().replace(' ', '-')
    regions_svg.append(f'''
      <path id="reg-{reg_id}" 
            data-region="{r['name']}"
            data-capital="{r['capital']}"
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
            class="ghana-region-path cursor-pointer transition-all duration-300 fill-white stroke-slate-200 hover:fill-violet-50 hover:stroke-violet-400"
            stroke-width="1.2"
            stroke-linejoin="round"
            stroke-linecap="round"
            onclick="selectRegion('{r['name']}')"
            onmouseenter="hoverRegion('{r['name']}', {r['cx']}, {r['cy']})"
            onmouseleave="unhoverRegion()" />
    ''')

print("Generated SVG elements for", len(regions), "regions.")
