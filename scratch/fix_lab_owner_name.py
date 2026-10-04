import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r'\$labOwnerName\s*=\s*\!empty\(\$primarySuspectsCombinedNames\)\s*\?\s*\$primarySuspectsCombinedNames\s*:\s*\$case->suspect_name\s*\?\?\s*\'Primary Suspect\';'

replacement = r'''$labOwnerName = !empty($activeSuspectName)
                                    ? $activeSuspectName
                                    : (!empty($primarySuspectsCombinedNames) ? $primarySuspectsCombinedNames : $case->suspect_name ?? 'Primary Suspect');'''

content, count = re.subn(pattern, replacement, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
