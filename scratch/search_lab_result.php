<?php
$content = file_get_contents('resources/views/cases/show_review.blade.php');
$lines = explode("\n", $content);

foreach ($lines as $i => $l) {
    if (stripos($l, 'LABORATORY EXAMINATION') !== false || stripos($l, 'EXAMINATION RESULT') !== false || stripos($l, 'EXHIBIT OWNER') !== false || stripos($l, 'exhibit_owner_name') !== false) {
        echo ($i + 1) . ": " . trim($l) . "\n";
    }
}
