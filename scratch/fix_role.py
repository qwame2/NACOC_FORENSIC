import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r'(\$activeSuspectExhibits\s*=\s*\$case->getExhibitsForSuspect\(\s*\$activeSuspectName,\s*\$activeSuspectItem\[\'id\'\]\s*\?\?\s*null,\s*\$activeSuspectItem\[\'associate_id\'\]\s*\?\?\s*null,\s*)\$activeSuspectItem\[\'role\'\]\s*\?\?\s*null'

replacement = r"\1$activeSuspectItem['role'] ?? ($activeSuspectItem['association_role'] ?? ($activeSuspectIndex === 0 ? 'Primary Suspect' : 'Co-Suspect'))"

new_content, count = re.subn(pattern, replacement, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
