with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines):
    if 'Commodities Inventory' in line or 'Master record of confiscated' in line:
        print(f"Line {idx+1}: {line.strip()[:120]}")
