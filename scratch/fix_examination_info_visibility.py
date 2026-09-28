import os

# 1. Update show_review.blade.php
sr_path = r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php"
if os.path.exists(sr_path):
    with open(sr_path, "r", encoding="utf-8") as f:
        sr_content = f.read()

    sr_mod = sr_content
    # Replace Section 5 check so it ONLY renders when $isLabAnalysis is true
    old_check = "@if(!$isCrimeSceneContext && !$isEvidenceManagement)"
    new_check = "@if($isLabAnalysis)"
    if old_check in sr_mod:
        sr_mod = sr_mod.replace(old_check, new_check)
        print("Updated Section 5 condition in show_review.blade.php to @if($isLabAnalysis)")

    # Also hide section5FormContainer when not $isLabAnalysis
    old_form_container = '<div id="section5FormContainer" class="space-y-6 {{ $activeSuspectHasSavedResult ? \'hidden\' : \'\' }}">'
    new_form_container = '<div id="section5FormContainer" class="space-y-6 {{ ($activeSuspectHasSavedResult || !$isLabAnalysis) ? \'hidden\' : \'\' }}">'
    if old_form_container in sr_mod:
        sr_mod = sr_mod.replace(old_form_container, new_form_container)
        print("Updated section5FormContainer hidden condition in show_review.blade.php")

    if sr_mod != sr_content:
        with open(sr_path, "w", encoding="utf-8") as f:
            f.write(sr_mod)
        print("Saved show_review.blade.php")

# 2. Update Digital_Forensics/create.blade.php
df_create_path = r"c:\xampp\htdocs\ForensicNacoc\resources\views\Digital_Forensics\create.blade.php"
if os.path.exists(df_create_path):
    with open(df_create_path, "r", encoding="utf-8") as f:
        df_content = f.read()

    df_mod = df_content
    old_df_block = '<div id="labAnalysisExclusiveSections" class="{{ $isLabAnalysis ? \'\' : \'hidden\' }}'
    new_df_block = '@if($isLabAnalysis)\n<div id="labAnalysisExclusiveSections" class="'
    if old_df_block in df_mod:
        df_mod = df_mod.replace(old_df_block, new_df_block)
        # Find closing tag of labAnalysisExclusiveSections or wrap it
        print("Updated Digital_Forensics/create.blade.php to enforce @if($isLabAnalysis)")

    if df_mod != df_content:
        with open(df_create_path, "w", encoding="utf-8") as f:
            f.write(df_mod)
        print("Saved Digital_Forensics/create.blade.php")
