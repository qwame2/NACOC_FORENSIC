<?php
$content = file_get_contents('resources/views/Crime_Scene_Support/staff/events/arrest_person.blade.php');
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (preg_match('/(associate|co-suspect)/i', $line)) {
        echo ($idx + 1) . ": " . trim($line) . "\n";
    }
}
