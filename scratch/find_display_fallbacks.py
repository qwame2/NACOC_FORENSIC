import re
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

target_files = [
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Forensic_Head\case_pdf.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Evidence_Management\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Digital_Forensics\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Crime_Scene_Support\head\CrimeSceneCreate.blade.php",
]

# Patterns for fallbacks in blade:
# 1) {{ $var ?: 'Fallback' }} or {{ $var ?? 'Fallback' }} or {{ $var ?: 'Not Recorded' }}
# 2) $var = ... ?: 'Fallback';
# 3) text like 'Not Recorded', 'Not Specified', 'Unspecified', 'None', 'Nil', 'Not Provided'

for filepath in target_files:
    if not os.path.exists(filepath):
        continue
    fname = os.path.basename(filepath)
    print(f"\n=================== File: {fname} ===================")
    with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()

    # Find {{ ... }} expressions with ?? or ?:
    blade_exprs = re.findall(r"\{\{\s*([^}]+?)\s*\}\}", content)
    for expr in blade_exprs:
        if "??" in expr or "?:" in expr:
            # check what follows ?? or ?:
            match = re.search(r"(\?\?|\?:)\s*['\"]([^'\"]+)['\"]", expr)
            if match:
                fallback = match.group(2)
                if fallback not in ['N/A', 'n/a', '', 'hidden', 'block', 'selected', 'checked', '[]', '{}'] and not fallback.startswith('bg-') and not fallback.startswith('text-') and not fallback.startswith('border-'):
                    print(f"Blade fallback: {expr.strip()} --> '{fallback}'")

    # Find PHP variable assignments with fallbacks: $var = ... ?: 'Fallback';
    php_assigns = re.findall(r"\$([a-zA-Z0-9_]+)\s*=\s*[^;]+?(\?\?|\?:)\s*['\"]([^'\"]+)['\"]", content)
    for var, op, fallback in php_assigns:
        if fallback not in ['N/A', 'n/a', '', 'hidden', 'block', 'selected', 'checked', '[]', '{}'] and not fallback.startswith('bg-') and not fallback.startswith('text-') and not fallback.startswith('border-'):
            print(f"PHP assign fallback: ${var} = ... {op} '{fallback}'")
