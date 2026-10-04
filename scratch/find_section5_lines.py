import re

with open('resources/views/cases/show_review.blade.php', 'r', encoding='utf-8') as f:
    sr_lines = f.readlines()

def find_lines(pattern, lines):
    res = []
    for i, l in enumerate(lines):
        if re.search(pattern, l):
            res.append((i+1, l.strip()))
    return res

targets = [
    r'id=["\']section5LabExamination["\']',
    r'id=["\']section5FormContainer["\']',
    r'function submitCombinedLabAndSeizureForm',
    r'function submitSection5LabExam',
    r'function onReviewLabExhibitChange',
    r'function parsePresumptiveResultReview',
    r'function renderPresumptiveTableHTML',
    r'function evaluateLabExaminationVisibility',
    r'function addPresumptiveTestRowReview',
    r'function toggleConfirmatoryTestReview',
    r'function removePresumptiveTestRowReview',
    r'function addDetectedSubstanceRowReview',
    r'function removeDetectedSubstanceRowReview',
    r'function getDetectedSubstancesDataReview',
    r'function resetSection5Form',
    r'function lockSection5Form',
    r'function toggleLabFormEditMode',
    r'function toggleSection5'
]

print("=== SHOW_REVIEW MATCHES ===")
for t in targets:
    matches = find_lines(t, sr_lines)
    for m in matches:
        print(f'{m[0]}: {m[1]}')
