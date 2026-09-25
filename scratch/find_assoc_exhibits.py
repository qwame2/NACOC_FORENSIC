with open(r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\associate.blade.php', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

for idx, line in enumerate(lines):
    if 'seizure' in line.lower() or 'exhibit' in line.lower() or 'weapon' in line.lower() or 'vehicle' in line.lower():
        if 'id=' in line or 'name=' in line or 'function' in line or 'class=' in line and 'font' in line:
            print(f"Line {idx+1}: {line.strip()[:100]}")
