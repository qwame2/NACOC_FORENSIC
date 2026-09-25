import os
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

dirs_to_scan = [
    r"c:\xampp\htdocs\ForensicNacoc\resources\views",
    r"c:\xampp\htdocs\ForensicNacoc\app",
]

fallback_exact_patterns = [
    r"['\"]Not Recorded['\"]",
    r"['\"]NOT RECORDED['\"]",
    r"['\"]Not Specified['\"]",
    r"['\"]NOT SPECIFIED['\"]",
    r"['\"]Unspecified['\"]",
    r"['\"]UNSPECIFIED['\"]",
    r"['\"]Not Provided['\"]",
    r"['\"]NOT PROVIDED['\"]",
    r"['\"]None['\"]",
    r"['\"]Nil['\"]",
    r"['\"]NIL['\"]",
    r"['\"]No Children['\"]",
    r"['\"]Defaced / Unspecified['\"]",
    r"['\"]No specific laboratory analytical findings logged yet\.['\"]",
    r"['\"]No specific laboratory analytical findings logged\.['\"]",
    r"['\"]No specific laboratory analytical findings logged['\"]",
    r"['\"]No specific findings logged\.['\"]",
    r"['\"]No notes provided\.['\"]",
    r"['\"]No notes logged\.['\"]",
    r"['\"]No further investigative action required for this suspect\.['\"]",
]

for d in dirs_to_scan:
    for dirpath, _, filenames in os.walk(d):
        for f in filenames:
            if f.endswith('.php') or f.endswith('.blade.php'):
                filepath = os.path.join(dirpath, f)
                rel = os.path.relpath(filepath, r"c:\xampp\htdocs\ForensicNacoc")
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as file:
                    lines = file.readlines()
                for i, line in enumerate(lines, 1):
                    for pat in fallback_exact_patterns:
                        if re.search(pat, line):
                            print(f"{rel}:{i}: {line.strip()[:140]}")
