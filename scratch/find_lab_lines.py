with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines):
    if 'LABORATORY' in line or 'Laboratory' in line or 'FINDINGS' in line:
        if '<h' in line or 'font-bold' in line or 'uppercase' in line or 'div class=' in line:
            print(f"Line {idx+1}: {line.strip()[:100]}")
