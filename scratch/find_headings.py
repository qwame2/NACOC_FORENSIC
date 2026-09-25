import re

with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines[1000:8000], start=1001):
    if re.search(r'Seizures|Commodit|Inventory|EXHIBIT|GALLERY', line, re.IGNORECASE):
        if '<h' in line or 'font-bold' in line or 'uppercase' in line or 'text-xs' in line or 'text-sm' in line:
            print(f"Line {idx}: {line.strip()[:120]}")
