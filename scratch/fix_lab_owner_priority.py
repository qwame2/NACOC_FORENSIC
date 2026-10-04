import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r'''\$labIsAssoc = false;\s*
\s*\$savedLabOwnerName = \$docketData\['lab_examination'\]\['exhibit_owner_name'\] \?\? null;\s*
\s*if \(\s*\!empty\(\$savedLabOwnerName\) &&\s*
\s*strtoupper\(trim\(\$savedLabOwnerName\)\) \!\=\= 'PRIMARY SUSPECT'\s*
\s*\) \{\s*
\s*\$labOwnerName = \$savedLabOwnerName;\s*
\s*\} elseif \(\s*
\s*\!empty\(\$allDrugExhibitsList\[0\]\['owner'\]\) &&\s*
\s*strtoupper\(trim\(\$allDrugExhibitsList\[0\]\['owner'\]\)\) \!\=\= 'PRIMARY SUSPECT'\s*
\s*\) \{\s*
\s*\$labOwnerName = \$allDrugExhibitsList\[0\]\['owner'\];\s*
\s*\} else \{\s*
\s*\$labOwnerName = \!empty\(\$activeSuspectName\)\s*
\s*\? \$activeSuspectName\s*
\s*: \(\!empty\(\$primarySuspectsCombinedNames\) \? \$primarySuspectsCombinedNames : \$case->suspect_name \?\? 'Primary Suspect'\);\s*
\s*\}'''

replacement = '''$labIsAssoc = false;
                            $savedLabOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
                            if (!empty($activeSuspectName)) {
                                $labOwnerName = $activeSuspectName;
                            } elseif (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
                                $labOwnerName = $savedLabOwnerName;
                            } elseif (
                                !empty($allDrugExhibitsList[0]['owner']) &&
                                strtoupper(trim($allDrugExhibitsList[0]['owner'])) !== 'PRIMARY SUSPECT'
                            ) {
                                $labOwnerName = $allDrugExhibitsList[0]['owner'];
                            } else {
                                $labOwnerName = !empty($primarySuspectsCombinedNames)
                                    ? $primarySuspectsCombinedNames
                                    : $case->suspect_name ?? 'Primary Suspect';
                            }'''

content, count = re.subn(pattern, replacement, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
