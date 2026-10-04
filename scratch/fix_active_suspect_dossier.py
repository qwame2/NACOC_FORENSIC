import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r'''\$labIsAssoc = false;\s*
\s*\$savedLabOwnerName = \$docketData\['lab_examination'\]\['exhibit_owner_name'\] \?\? null;\s*
\s*if \(\!empty\(\$activeSuspectName\)\) \{\s*
\s*\$labOwnerName = \$activeSuspectName;\s*
\s*\} elseif \(\!empty\(\$savedLabOwnerName\) && strtoupper\(trim\(\$savedLabOwnerName\)\) \!\=\= 'PRIMARY SUSPECT'\) \{\s*
\s*\$labOwnerName = \$savedLabOwnerName;\s*
\s*\} elseif \(\s*
\s*\!empty\(\$allDrugExhibitsList\[0\]\['owner'\]\) &&\s*
\s*strtoupper\(trim\(\$allDrugExhibitsList\[0\]\['owner'\]\)\) \!\=\= 'PRIMARY SUSPECT'\s*
\s*\) \{\s*
\s*\$labOwnerName = \$allDrugExhibitsList\[0\]\['owner'\];\s*
\s*\} else \{\s*
\s*\$labOwnerName = \!empty\(\$primarySuspectsCombinedNames\)\s*
\s*\? \$primarySuspectsCombinedNames\s*
\s*: \(\!empty\(\$primarySuspectsCombinedNames\) \? \$primarySuspectsCombinedNames : \$case->suspect_name \?\? 'Primary Suspect'\);\s*
\s*\}\s*
\s*\$labOwnerLower = strtolower\(trim\(\$labOwnerName\)\);\s*
\s*\$isJointLabOwner ='''

replacement = '''$labIsAssoc = false;
                            $savedLabOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
                            if (!empty($activeSuspectName)) {
                                $labOwnerName = $activeSuspectName;
                                $isJointLabOwner = false;
                            } else {
                                if (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
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
                                }

                                $labOwnerLower = strtolower(trim($labOwnerName));
                                $isJointLabOwner =
                                    str_contains($labOwnerLower, ',') ||
                                    str_contains($labOwnerLower, '&') ||
                                    str_contains($labOwnerLower, 'all suspect') ||
                                    str_contains($labOwnerLower, 'all suspects');
                                if (
                                    !$isJointLabOwner &&
                                    !empty($docketData['suspects']) &&
                                    count($docketData['suspects']) > 1
                                ) {
                                    $mCount = 0;
                                    foreach ($docketData['suspects'] as $sp) {
                                        if (
                                            !empty($sp['suspect_name']) &&
                                            str_contains($labOwnerLower, strtolower(trim($sp['suspect_name'])))
                                        ) {
                                            $mCount++;
                                        }
                                    }
                                    if ($mCount > 1) {
                                        $isJointLabOwner = true;
                                    }
                                }
                            }'''

# Replace the entire block
pattern_block = r'\$labIsAssoc = false;[\s\S]*?if \(\$isJointLabOwner\) \{'
new_block = replacement + '\n\n                            if ($isJointLabOwner) {'

content, count = re.subn(pattern_block, new_block, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
