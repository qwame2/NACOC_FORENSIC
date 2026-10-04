<?php

$path = __DIR__ . '/../resources/views/cases/show_review.blade.php';
$content = file_get_contents($path);

// Replace the invalid @php \n @if($isDevEx) \n @php sequence
$search = "@php\r\n                                        @if(\$isDevEx)\r\n                                     @php";
$searchUnix = "@php\n                                        @if(\$isDevEx)\n                                     @php";

$replace = "@if(\$isDevEx)\r\n                                     @php";
$replaceUnix = "@if(\$isDevEx)\n                                     @php";

if (strpos($content, $search) !== false) {
    $content = str_replace($search, $replace, $content);
    echo "Replaced CRLF sequence\n";
} elseif (strpos($content, $searchUnix) !== false) {
    $content = str_replace($searchUnix, $replaceUnix, $content);
    echo "Replaced LF sequence\n";
} else {
    // Regex replace
    $content = preg_replace('/@php\s+@if\(\$isDevEx\)\s+@php/', "@if(\$isDevEx)\n                                     @php", $content);
    echo "Replaced regex sequence\n";
}

file_put_contents($path, $content);
echo "Cleaned up php tag successfully\n";
