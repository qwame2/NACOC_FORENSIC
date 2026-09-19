<?php
$content = file_get_contents('resources/views/Crime_Scene_Support/staff/events/arrest_person.blade.php');
if (str_contains($content, chr(0))) {
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
    echo "File was UTF-16LE!\n";
} else {
    echo "File is UTF-8\n";
}

preg_match_all('/[^\n]*associate[^\n]*/i', $content, $matches, PREG_OFFSET_CAPTURE);
echo "Found " . count($matches[0]) . " occurrences:\n";
foreach (array_slice($matches[0], 0, 30) as $m) {
    echo $m[0] . "\n";
}
