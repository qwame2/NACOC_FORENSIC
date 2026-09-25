import os

files_to_update = [
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Evidence_Management\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Digital_Forensics\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Crime_Scene_Support\head\CrimeSceneCreate.blade.php",
]

create_reps = [
    ("let optionText = `Exhibit #${itemNum}: ${displayName}`;", "let optionText = displayName;"),
    ("let formattedDesc = `Exhibit #${idx}: ${name}`;", "let formattedDesc = name || cat || 'Exhibit';"),
    ("displayName = (cat && cat.trim() !== '') ? cat : `Exhibit #${itemNum}`;", "displayName = (cat && cat.trim() !== '') ? cat : (name || 'Exhibit');"),
]

for filepath in files_to_update:
    if not os.path.exists(filepath):
        continue
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()
    
    modified = content
    for target, rep in create_reps:
        if target in modified:
            modified = modified.replace(target, rep)
    
    if modified != content:
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(modified)
        print(f"Updated {os.path.basename(filepath)}")

# show_review.blade.php
sr_path = r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php"
if os.path.exists(sr_path):
    with open(sr_path, "r", encoding="utf-8") as f:
        sr_content = f.read()
    
    sr_mod = sr_content
    sr_mod = sr_mod.replace("('Exhibit #' . ($loop->index + 1))", "'Exhibit'")
    sr_mod = sr_mod.replace("`Exhibit #${itemNum}`", "'Exhibit'")
    sr_mod = sr_mod.replace("`Exhibit #${idx + 1}`", "'Exhibit'")
    
    if sr_mod != sr_content:
        with open(sr_path, "w", encoding="utf-8") as f:
            f.write(sr_mod)
        print("Updated show_review.blade.php")
