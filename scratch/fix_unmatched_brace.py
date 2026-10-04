import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target_segment = '''                                    if ($mCount > 1) {
                                        $isJointLabOwner = true;
                                    }
                                }
                            }
                        }

                        if ($isJointLabOwner) {'''

replacement_segment = '''                                    if ($mCount > 1) {
                                        $isJointLabOwner = true;
                                    }
                                }
                            }

                        if ($isJointLabOwner) {'''

if target_segment in content:
    content = content.replace(target_segment, replacement_segment, 1)
    print("Replaced 1 instance successfully")
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
else:
    print("Target segment not found")
