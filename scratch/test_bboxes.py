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

print("Computed region bboxes:", region_bboxes)
