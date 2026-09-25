<?php
$content = file_get_contents('c:/xampp/htdocs/ForensicNacoc/resources/views/cases/show_review.blade.php');
$lines = explode("\n", $content);

foreach ($lines as $i => $line) {
    if (stripos($line, 'Presumptive Reaction') !== false || stripos($line, 'LABORATORY EXAMINATION') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
