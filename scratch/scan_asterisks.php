<?php
$filePath = 'c:/Users/USER/Desktop/ForensicNacoc/resources/views/Crime_Scene_Support/staff/events/arrest_person.blade.php';
$lines = file($filePath);

foreach ($lines as $index => $line) {
    if (strpos($line, '*') !== false) {
        echo ($index + 1) . ': ' . trim($line) . "\n";
    }
}
