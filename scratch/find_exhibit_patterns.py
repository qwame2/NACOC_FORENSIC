import os
import re

root_dir = r"c:\xampp\htdocs\ForensicNacoc\resources\views"

for dirpath, _, filenames in os.walk(root_dir):
    for f in filenames:
        if f.endswith('.blade.php'):
            filepath = os.path.join(dirpath, f)
            rel = os.path.relpath(filepath, root_dir)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as file:
                lines = file.readlines()
            for i, line in enumerate(lines, 1):
                if "Exhibit #" in line or "Exhibit#" in line or "EXH-" in line:
                    print(f"{rel}:{i}: {line.strip()[:140]}")
