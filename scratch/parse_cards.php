<?php
$html = file_get_contents(__DIR__ . '/review_output.html');

// Let's search for "LABORATORY EXAMINATION RESULT" and print 1500 chars after each occurrence
$pos = 0;
$count = 1;
while (($pos = strpos($html, 'LABORATORY EXAMINATION RESULT', $pos)) !== false) {
    echo "=================== MATCH #{$count} ===================\n";
    $snippet = substr($html, $pos, 2500);
    // Strip tags roughly or display formatted snippet
    echo $snippet . "\n\n";
    $pos += strlen('LABORATORY EXAMINATION RESULT');
    $count++;
}
