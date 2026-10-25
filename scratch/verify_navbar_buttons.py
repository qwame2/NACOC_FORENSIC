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
    
    # Extract nav bar contents
    nav_match = re.search(r'<nav[^>]*>(.*?)</nav>', c, re.DOTALL)
    if nav_match:
        links = re.findall(r'<a[^>]*href="([^"]*)"[^>]*>.*?<span>(.*?)</span>.*?</a>', nav_match.group(1), re.DOTALL)
        print(f"[{label}]")
        for href, title in links:
            print(f"  • {title.strip()} -> {href.strip()}")
    else:
        print(f"[{label}] NAV NOT FOUND")
