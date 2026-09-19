<?php

$filePath = "resources/views/Forensic_Head/show_review.blade.php";
$content = file_get_contents($filePath);

// Extract the script tag starting around line 3198
$lines = explode("\n", $content);
$scriptLines = [];
$inScript = false;
$startLineNo = 0;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $lineNo = $i + 1;

    if ($lineNo >= 3195 && strpos($line, '<script>') !== false) {
        $inScript = true;
        $startLineNo = $lineNo;
        continue;
    }
    if ($inScript && strpos($line, '</script>') !== false) {
        $inScript = false;
        break;
    }
    if ($inScript) {
        // Strip Blade expressions like {{ ... }} or {!! ... !!} so node can parse it cleanly
        // Replace Blade tags with valid JS placeholders
        $cleaned = $line;
        $cleaned = preg_replace('/\{\{\s*[\s\S]*?\s*\}\}/', '"BLADE_EXPR"', $cleaned);
        $cleaned = preg_replace('/\{\!\!\s*[\s\S]*?\s*\!\!\}/', '"BLADE_RAW"', $cleaned);
        $cleaned = preg_replace('/@json\([\s\S]*?\)/', '{}', $cleaned);
        $cleaned = preg_replace('/@if\([\s\S]*?\)/', 'if(true){', $cleaned);
        $cleaned = preg_replace('/@elseif\([\s\S]*?\)/', '}else if(true){', $cleaned);
        $cleaned = preg_replace('/@else/', '}else{', $cleaned);
        $cleaned = preg_replace('/@endif/', '}', $cleaned);
        $cleaned = preg_replace('/@foreach\([\s\S]*?\)/', 'for(let x of []){', $cleaned);
        $cleaned = preg_replace('/@endforeach/', '}', $cleaned);
        $scriptLines[$lineNo] = $cleaned;
    }
}

file_put_contents("scratch/extracted_script.js", implode("\n", $scriptLines));
echo "Extracted script from line $startLineNo to " . (count($scriptLines) + $startLineNo) . "\n";
