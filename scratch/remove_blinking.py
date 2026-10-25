import re
import os

scratch_dir = os.path.dirname(os.path.abspath(__file__))
generator_path = os.path.join(scratch_dir, 'build_clean_dashboards.py')

with open(generator_path, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Remove radarScan animation and radar-pulse
code = re.sub(
    r'\.radar-pulse\s*\{\{[^}]*\}\}\s*@keyframes radarScan\s*\{\{[^}]*\}\}',
    '/* Radar rings are static (no pulsing) */',
    code,
    flags=re.DOTALL
)

# 2. Remove radar-pulse class in HTML
code = code.replace(' radar-pulse', '')

# 3. Remove all animate-ping and animate-pulse from classes
code = code.replace(' animate-ping', '')
code = code.replace('animate-ping ', '')
code = code.replace('animate-ping', '')
code = code.replace(' animate-pulse', '')
code = code.replace('animate-pulse ', '')
code = code.replace('animate-pulse', '')

# 4. Remove any extra SVG ping circles
code = code.replace(
    '<!-- Glowing Red Radar Ping -->\n              <circle cx="${{d.x}}" cy="${{d.y}}" r="5.5" fill="none" stroke="#ef4444" stroke-width="0.8" />\n              ',
    ''
)
code = code.replace(
    '<circle cx="${{d.x}}" cy="${{d.y}}" r="5.5" fill="none" stroke="#ef4444" stroke-width="0.8" />',
    ''
)
code = code.replace(
    '<circle cx="${{d.x}}" cy="${{d.y}}" r="6" fill="none" stroke="#ef4444" stroke-width="0.8" />',
    ''
)

with open(generator_path, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated: all blinking dots, pings, and pulsing animations removed!")
