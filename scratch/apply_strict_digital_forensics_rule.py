import os

sr_path = r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php"
if os.path.exists(sr_path):
    with open(sr_path, "r", encoding="utf-8") as f:
        content = f.read()

    if "$isDigitalForensics" not in content:
        old_unit_block = "$isEvidenceManagement = ("
        new_unit_block = "$isDigitalForensics = (\n        str_contains(strtolower($activeUnitContext ?? ''), 'digital') ||\n        str_contains(strtolower($activeUnitContext ?? ''), 'physical') ||\n        str_contains(strtolower($reqFrom ?? ''), 'digital') ||\n        str_contains(strtolower($reqFrom ?? ''), 'physical') ||\n        request()->is('Digital_Forensics*') ||\n        request()->is('digital-forensics*')\n    );\n    $isEvidenceManagement = ("
        content = content.replace(old_unit_block, new_unit_block, 1)

    old_cond1 = "@if($isLabAnalysis)"
    old_cond2 = "@if(!$isCrimeSceneContext && !$isEvidenceManagement)"
    new_cond = "@if(!$isDigitalForensics && !$isCrimeSceneContext && !$isEvidenceManagement)"

    if old_cond1 in content:
        content = content.replace(old_cond1, new_cond)
    elif old_cond2 in content:
        content = content.replace(old_cond2, new_cond)

    with open(sr_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated show_review.blade.php with strict Digital & Physical Forensics exclusion for Examination Information")
