with open('resources/views/cases/show_review.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if 'id="section5LabExamination"' in line:
        print(f'section5LabExamination open: line {i+1}')
    if 'id="section4Seizures"' in line:
        print(f'section4Seizures open: line {i+1}')
    if 'id="section5FormContainer"' in line:
        print(f'section5FormContainer open: line {i+1}')
    if 'id="section5NoDrugExhibitNotice"' in line:
        print(f'section5NoDrugExhibitNotice open: line {i+1}')
    if 'id="section5ReadOnlyDisplay"' in line:
        print(f'section5ReadOnlyDisplay open: line {i+1}')
