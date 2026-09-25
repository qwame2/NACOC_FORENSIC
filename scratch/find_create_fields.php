<?php
$content = file_get_contents('c:/xampp/htdocs/ForensicNacoc/resources/views/cases/create.blade.php');
$lines = explode("\n", $content);

foreach ($lines as $i => $line) {
    if (stripos($line, 'holding') !== false || stripos($line, 'facility') !== false || stripos($line, 'arrest') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
