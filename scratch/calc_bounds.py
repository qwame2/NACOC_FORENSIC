import json, re

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

for r in data['regions']:
    coords = re.findall(r'([0-9]+\.[0-9]+),([0-9]+\.[0-9]+)', r['path'])
    xs = [float(x) for x, y in coords]
    ys = [float(y) for x, y in coords]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)
    w = max_x - min_x
    h = max_y - min_y
    pad_x = w * 0.15
    pad_y = h * 0.15
    vb = f"{min_x - pad_x:.1f} {min_y - pad_y:.1f} {w + 2*pad_x:.1f} {h + 2*pad_y:.1f}"
    print(f"'{r['name']}': {{ 'minX': {min_x:.1f}, 'maxX': {max_x:.1f}, 'minY': {min_y:.1f}, 'maxY': {max_y:.1f}, 'viewBox': '{vb}', 'cx': {r['cx']}, 'cy': {r['cy']} }},")
