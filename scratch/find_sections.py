import re

with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines):
    if re.search(r'Seizure|Commodit|Inventory|EXHIBIT PHOTO GALLERY', line, re.IGNORECASE):
        print(f"Line {idx+1}: {line.strip()[:120]}")
