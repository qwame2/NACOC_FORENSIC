<?php
$query = $argv[1] ?? 'labOwnerName';
$lines = file('resources/views/cases/show_review.blade.php');
foreach ($lines as $num => $line) {
    if (stripos($line, $query) !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
