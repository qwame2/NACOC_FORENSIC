import re
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

files_to_check = [
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Forensic_Head\case_pdf.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\examination.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\lab_analysis.blade.php",
]

fallback_phrases = [
    "not recorded", "not specified", "unspecified", "none", "not provided",
    "no specific", "no notes", "no laboratory", "no analytical", "no findings",
    "not logged", "not applicable", "nil", "pending", "dash", "n/a"
]

for filepath in files_to_check:
    if not os.path.exists(filepath):
        continue
    fname = os.path.basename(filepath)
    print(f"\n=================== File: {fname} ===================")
    with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
        lines = f.readlines()
    
    for i, line in enumerate(lines, 1):
        # check for ?? '...' or ?: '...' or : '...'
        matches = re.findall(r"(\?\?|\?:)\s*['\"]([^'\"]+)['\"]", line)
        for op, val in matches:
            vlower = val.strip().lower()
            if any(p in vlower for p in ["not ", "none", "unspecified", "no ", "pending", "-", "nil"]) or val.strip() in ["-", "--", "None", "Nil", "Not Recorded", "NOT RECORDED", "Not Specified"]:
                print(f"Line {i}: {op} '{val}'")

        # Also check for hardcoded strings in ternary or direct blade text
        for p in ["NOT RECORDED", "Not Recorded", "No specific", "No notes", "Unspecified", "UNSPECIFIED", "Not Specified"]:
            if p in line and op not in line:
                print(f"Line {i} Text: {line.strip()[:100]}")
