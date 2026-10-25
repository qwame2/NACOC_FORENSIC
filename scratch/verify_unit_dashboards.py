import re

files = [
    ('EMU', 'resources/views/Evidence_Management/evidence_management.blade.php'),
    ('HEAD', 'resources/views/Forensic_Head/head.blade.php'),
    ('LAB', 'resources/views/Lab_Analysis/lab_analysis.blade.php'),
    ('CRIME', 'resources/views/Crime_Scene_Support/head/head.blade.php'),
    ('DIGITAL', 'resources/views/Digital_Forensics/digital_forensics.blade.php')
]

for label, path in files:
    with open(path, 'r', encoding='utf-8') as f:
        c = f.read()
    title = re.findall(r'<title>(.*?)</title>', c)[0]
    badge_matches = re.findall(r'NACOC FORENSIC.*?<span[^>]*class="[^"]*font-mono-code[^"]*"[^>]*>\s*(.*?)\s*</span>', c, re.DOTALL)
    card4 = re.findall(r'<!-- CARD 4:\s*(.*?)\s*-->', c)[0]
    tac = re.findall(r'NACOC TACTICAL OPERATIONS THEATRE • (.*?)</span>', c)[0]
    print(f'[{label}]')
    print(f'   Title: {title}')
    print(f'   Badge: {badge_matches[0] if badge_matches else "N/A"}')
    print(f'   Card4: {card4}')
    print(f'   Tactical Header: {tac}')
