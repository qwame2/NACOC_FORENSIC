import os

replacements = [
    # show_review.blade.php
    (
        r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php",
        [
            ("?? 'No Children'", "?? 'N/A'"),
            (": 'No Children'", ": 'N/A'"),
            ("$displaySampleTakenLabel = 'Not Recorded';", "$displaySampleTakenLabel = 'N/A';"),
            (">Not Recorded</span>", ">N/A</span>"),
            (">No individual compound breakdown logged</span>", ">N/A</span>"),
            ("'No specific laboratory analytical findings logged yet.'", "'N/A'"),
            ("'No specific laboratory analytical findings logged.'", "'N/A'"),
            ("uDetectedEl.textContent = 'No individual compound breakdown logged';", "uDetectedEl.textContent = 'N/A';"),
            ("notes || 'No specific laboratory analytical findings logged yet.'", "notes || 'N/A'"),
            ("?? 'No further investigative action required for this suspect.'", "?? 'N/A'"),
        ]
    ),
    # case_pdf.blade.php
    (
        r"c:\xampp\htdocs\ForensicNacoc\resources\views\Forensic_Head\case_pdf.blade.php",
        [
            ("?? 'No Children'", "?? 'N/A'"),
            (": 'No Children'", ": 'N/A'"),
            ("$case->veh_damage ?: 'None'", "$case->veh_damage ?: 'N/A'"),
            ("'Defaced / Unspecified'", "'N/A'"),
            (">No individual compound breakdown logged</span>", ">N/A</span>"),
            ("'No specific laboratory analytical findings logged yet.'", "'N/A'"),
            ("'No specific laboratory analytical findings logged.'", "'N/A'"),
        ]
    ),
    # associate.blade.php
    (
        r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\associate.blade.php",
        [
            ("?: 'No Children'", "?: 'N/A'"),
        ]
    ),
    # edit.blade.php
    (
        r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\edit.blade.php",
        [
            (": 'No Children'", ": 'N/A'"),
            ("$genderVal = 'Unspecified';", "$genderVal = 'N/A';"),
            ("$genderLabel = 'Unspecified';", "$genderLabel = 'N/A';"),
            ("selectEditGender('Unspecified', 'Unspecified'", "selectEditGender('N/A', 'N/A'"),
            ("<span>Unspecified</span>", "<span>N/A</span>"),
            ("val = 'Unspecified'; symbol = ''; label = 'Unspecified';", "val = 'N/A'; symbol = ''; label = 'N/A';"),
        ]
    ),
    # CaseController.php
    (
        r"c:\xampp\htdocs\ForensicNacoc\app\Http\Controllers\CaseController.php",
        [
            (": 'No Children'", ": 'N/A'"),
        ]
    ),
]

# Standard create views
create_views = [
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Evidence_Management\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Digital_Forensics\create.blade.php",
    r"c:\xampp\htdocs\ForensicNacoc\resources\views\Crime_Scene_Support\head\CrimeSceneCreate.blade.php",
]

create_replacements = [
    ("selectGender('Unspecified',", "selectGender('N/A',"),
    ("selectDynamicGender(this, 'Unspecified',", "selectDynamicGender(this, 'N/A',"),
    ("<span>Unspecified</span>", "<span>N/A</span>"),
    ("marital === 'Unspecified'", "marital === 'N/A'"),
    (": 'No Children'", ": 'N/A'"),
]

for cv in create_views:
    replacements.append((cv, create_replacements))

for filepath, reps in replacements:
    if not os.path.exists(filepath):
        print(f"Skipping non-existent {filepath}")
        continue
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()
    
    modified = content
    count = 0
    for target, replacement in reps:
        if target in modified:
            c = modified.count(target)
            modified = modified.replace(target, replacement)
            count += c
            print(f"[{os.path.basename(filepath)}] Replaced '{target}' -> '{replacement}' ({c} times)")
    
    if modified != content:
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(modified)
        print(f"Saved changes to {os.path.basename(filepath)} ({count} total replacements)")
    else:
        print(f"No changes needed for {os.path.basename(filepath)}")
