import re

with open(r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php", "r", encoding="utf-8", errors="ignore") as f:
    lines = f.readlines()

fallback_keywords = ["Not Recorded", "NOT RECORDED", "Not Specified", "NOT SPECIFIED", "Unspecified", "UNSPECIFIED", "No Children", "No notes", "No specific", "Not Provided", "Nil", "NIL", "None"]

for i, line in enumerate(lines, 1):
    for kw in fallback_keywords:
        if kw in line:
            print(f"Line {i}: {line.strip()[:140]}")
