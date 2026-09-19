<?php

$content = file_get_contents(__DIR__ . '/../resources/views/Forensic_Head/show_review.blade.php');
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[1] as $idx => $match) {
    $js = $match[0];
    $offset = $match[1];
    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
    echo "Script block #$idx starting at line $lineNumber\n";
    
    // Convert Blade code to valid JS syntax so node can check it
    $cleanedJs = preg_replace('/\{\{\s*\$hasSavedLabData\s*\?\s*true\s*:\s*false\s*\}\}/i', 'false', $js);
    $cleanedJs = preg_replace('/\{\{.*?\}\}/s', '""', $cleanedJs);
    $cleanedJs = preg_replace('/\{!!.*?!!\}/s', '""', $cleanedJs);
    $cleanedJs = preg_replace('/@json\(.*?\)/s', '""', $cleanedJs);
    $cleanedJs = preg_replace('/@if\(.*?\)/', 'if(true){', $cleanedJs);
    $cleanedJs = preg_replace('/@else/', '}else{', $cleanedJs);
    $cleanedJs = preg_replace('/@endif/', '}', $cleanedJs);
    $cleanedJs = preg_replace('/@foreach\(.*?\)/', 'for(let x in []){', $cleanedJs);
    $cleanedJs = preg_replace('/@endforeach/', '}', $cleanedJs);
    $cleanedJs = preg_replace('/@php.*?@endphp/s', '', $cleanedJs);

    file_put_contents(__DIR__ . '/temp_script_' . $idx . '.js', $cleanedJs);
}
