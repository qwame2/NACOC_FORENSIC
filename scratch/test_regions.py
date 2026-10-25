import json

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

print(f"Loaded {len(data['regions'])} regions successfully.")
for r in data['regions']:
    print(f"- {r['name']} ({r['capital']}): center ({r['cx']}, {r['cy']}), path length: {len(r['path'])}")
