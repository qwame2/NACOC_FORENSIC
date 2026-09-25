with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines[2080:3500], start=2081):
    if '@endforeach' in line or '@foreach' in line:
        print(f"Line {idx}: {line.strip()[:100]}")
