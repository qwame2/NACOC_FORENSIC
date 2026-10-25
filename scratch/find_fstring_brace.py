with open('scratch/build_clean_dashboards.py', 'r', encoding='utf-8') as f:
    lines = f.readlines()

in_f = False
for idx, line in enumerate(lines, 1):
    if 'def generate_blade' in line:
        in_f = True
        continue
    if in_f and line.strip() == "'''":
        in_f = False
        continue
    if in_f:
        s = line.replace('{{', '').replace('}}', '')
        # Allowed interpolations
        allowed = [
            '{unit_name}', '{unit_subtitle}', '{unit_head}', '{kpi4_title}',
            '{kpi4_subtitle}', '{kpi4_metric}', '{kpi4_url}', '{kpi4_btn}',
            '{kpi4_bays_html}', '{nav_buttons_html}', '{region_paths_html}',
            '{tactical_paths_html}', '{extruded_paths_html}', '{region_labels_html}',
            '{tactical_labels_html}', '{districts_dataset_js}', '{region_bboxes_js}'
        ]
        for a in allowed:
            s = s.replace(a, '')
        if '{' in s or '}' in s:
            print(f"Line {idx}: {line.strip()}")
