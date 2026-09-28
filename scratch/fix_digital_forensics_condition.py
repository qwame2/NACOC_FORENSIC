import os

sr_path = r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php"
if os.path.exists(sr_path):
    with open(sr_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Update line 3782 condition to strictly exclude Digital Forensics
    old_cond = "@if(!$isDigitalForensics && !$isCrimeSceneContext && !$isEvidenceManagement)"
    new_cond = "@if(!$isDigitalForensics)"

    if old_cond in content:
        content = content.replace(old_cond, new_cond)
    
    with open(sr_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated show_review.blade.php condition to @if(!$isDigitalForensics)")
