<?php

// 1. Update arrest_person.blade.php
$arrestPersonPath = 'c:/Users/USER/Desktop/ForensicNacoc/resources/views/Crime_Scene_Support/staff/events/arrest_person.blade.php';
$content = file_get_contents($arrestPersonPath);

// A. Make all text-black font-bold asterisks red
$content = str_replace('text-black font-bold">*', 'text-red-600 font-bold">*', $content);

// B. Remove 14 Persons Detained span/div
$detainedPattern = '/<div class="flex items-center space-x-3">\s*<span class="px-3 py-1\.5 rounded-xl bg-emerald-100\/80 text-emerald-800 font-mono-code font-bold text-xs border-none shadow-xs">\s*14 Persons Detained\s*<\/span>\s*<\/div>/s';
$content = preg_replace($detainedPattern, '', $content);

// C. Change label Offence / Charge Description -> Brief Facts/ Charge Description
$content = str_replace('Offence / Charge Description', 'Brief Facts/ Charge Description', $content);

file_put_contents($arrestPersonPath, $content);
echo "arrest_person.blade.php updated successfully.\n";

// 2. Update show_review.blade.php
$showReviewPath = 'c:/Users/USER/Desktop/ForensicNacoc/resources/views/Forensic_Head/show_review.blade.php';
$reviewContent = file_get_contents($showReviewPath);
$reviewContent = str_replace('Offence / Charge Description', 'Brief Facts/ Charge Description', $reviewContent);
file_put_contents($showReviewPath, $reviewContent);
echo "show_review.blade.php updated successfully.\n";

