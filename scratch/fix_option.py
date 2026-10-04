import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

old_str = """data-presumptive="{{ $dEx['presumptive_result'] ?? ($savedLabPresumptive ?? '') }}">
                                                            {{ $exTitle }}
                                                        </option>"""

new_str = """data-presumptive="{{ $dEx['presumptive_result'] ?? ($savedLabPresumptive ?? '') }}">
                                                            {{ $exTitle . $exMetrics . $exPkgStr . $exOwnerStr }}
                                                        </option>"""

# Using regex to match exact line regardless of line ending or leading indentation
pattern = r'(data-presumptive="\{\{\s*\$dEx\[\'presumptive_result\'\]\s*\?\?\s*\(\$savedLabPresumptive\s*\?\?\s*\'\'\)\s*\}\}">\s*\n\s*)\{\{\s*\$exTitle\s*\}\}'
replacement = r'\1{{ $exTitle . $exMetrics . $exPkgStr . $exOwnerStr }}'

new_content, count = re.subn(pattern, replacement, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
