<?php
$content = file_get_contents('c:/xampp/htdocs/ForensicNacoc/resources/views/cases/show_review.blade.php');
$lines = explode("\n", $content);

foreach ($lines as $i => $line) {
    if (stripos($line, 'holding_facility') !== false || stripos($line, 'arrest_date') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
