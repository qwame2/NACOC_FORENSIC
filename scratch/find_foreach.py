with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines):
    if '@foreach' in line and ('suspect' in line.lower() or 'item' in line.lower()):
        print(f"Line {idx+1}: {line.strip()[:100]}")
