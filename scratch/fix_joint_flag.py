import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace lines with if (!$isJointLabOwner && !empty($isMultiSuspectCase)) { $isJointLabOwner = true; }
pattern1 = r'\n[ \t]*if\s*\(\s*!\$isJointLabOwner\s*&&\s*!empty\(\$isMultiSuspectCase\)\s*\)\s*\{\s*\$isJointLabOwner\s*=\s*true;\s*\}'
content, count1 = re.subn(pattern1, '', content)
print(f"Replaced count 1: {count1}")

if count1 > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
