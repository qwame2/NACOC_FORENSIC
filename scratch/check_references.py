import re

with open('resources/views/cases/show_review.blade.php', 'r', encoding='utf-8') as f:
    sr_content = f.read()

funcs = [
    'submitCombinedLabAndSeizureForm',
    'submitSection5LabExam',
    'onReviewLabExhibitChange',
    'parsePresumptiveResultReview',
    'renderPresumptiveTableHTML',
    'evaluateLabExaminationVisibility',
    'addPresumptiveTestRowReview',
    'removePresumptiveTestRowReview',
    'toggleConfirmatoryTestReview',
    'addDetectedSubstanceRowReview',
    'removeDetectedSubstanceRowReview',
    'getDetectedSubstancesDataReview',
    'resetSection5Form',
    'lockSection5Form',
    'toggleLabFormEditMode',
    'toggleSection5',
    'toggleLabFormSections',
]

for fn in funcs:
    matches = [m.start() for m in re.finditer(r'\b' + fn + r'\b', sr_content)]
    print(f'{fn}: count = {len(matches)}')
    # print line numbers
    for idx in matches:
        line_no = sr_content[:idx].count('\n') + 1
        line_text = sr_content.splitlines()[line_no - 1].strip()
        print(f'   Line {line_no}: {line_text[:90]}')
