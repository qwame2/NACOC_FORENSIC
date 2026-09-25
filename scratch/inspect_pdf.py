import re

with open(r"c:\xampp\htdocs\ForensicNacoc\resources\views\Forensic_Head\case_pdf.blade.php", "r", encoding="utf-8", errors="ignore") as f:
    lines = f.readlines()

for i, line in enumerate(lines, 1):
    l_lower = line.lower()
    if any(phrase in l_lower for phrase in ["not recorded", "not specified", "unspecified", "no children", "no specific laboratory", "no individual compound", "defaced / unspecified", "no notes"]):
        print(f"Line {i}: {line.strip()[:140]}")
