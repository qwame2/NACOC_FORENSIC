import os
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

root_dir = r"c:\xampp\htdocs\ForensicNacoc\resources\views"

targets = [
    r"['\"](none|not recorded|not specified|unspecified|not provided|nil|no specific [^'\"]*|no notes[^'\"]*|no laboratory[^'\"]*|no analytical[^'\"]*|no [a-z ]+ logged[^'\"]*)['\"]",
]

for dirpath, dirnames, filenames in os.walk(root_dir):
    for f in filenames:
        if f.endswith('.blade.php') or f.endswith('.php'):
            full_path = os.path.join(dirpath, f)
            rel_path = os.path.relpath(full_path, root_dir)
            with open(full_path, 'r', encoding='utf-8', errors='ignore') as file:
                lines = file.readlines()
            for line_idx, line in enumerate(lines, 1):
                for pattern in targets:
                    matches = re.finditer(pattern, line, re.IGNORECASE)
                    for m in matches:
                        print(f"{rel_path}:{line_idx}: matched '{m.group(0)}' in line: {line.strip()[:120]}")
