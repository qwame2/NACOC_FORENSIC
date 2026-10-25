with open('scratch/build_clean_dashboards.py', 'r', encoding='utf-8') as f:
    text = f.read()

import re
print("Found ghanaMapSvg:", "ghanaMapSvg" in text)
print("Found tacGhanaMapSvg:", "tacGhanaMapSvg" in text)
svg_tags = re.findall(r'<svg[^>]+id=[\'"][^\'"]+[\'"][^>]*>', text)
for s in svg_tags:
    print(s[:120])
