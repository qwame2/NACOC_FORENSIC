import os, re

views_dir = 'resources/views'
routes = ['Evidence_Management', 'Forensic_Head', 'Lab_Analysis', 'Crime_Scene_Support', 'Digital_Forensics', 'dashboard']
for root, dirs, files in os.walk(views_dir):
    for f in files:
        if f.endswith('.blade.php'):
            fpath = os.path.join(root, f)
            with open(fpath, 'r', encoding='utf-8', errors='ignore') as fp:
                content = fp.read()
            for r in routes:
                matches = re.findall(rf"['\"](?:/|\./|\.\./)*{r}[^'\"]*['\"]", content)
                if matches and not any(k in fpath for k in ['evidence_management.blade.php', 'head.blade.php', 'lab_analysis.blade.php', 'digital_forensics.blade.php']):
                    print(f'{fpath}: {matches[:2]}')
