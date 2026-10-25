<?php
$content = file_get_contents('http://127.0.0.1:8000/finnova-dashboard');
echo "Title:\n";
preg_match('/<title>(.*?)<\/title>/is', $content, $m);
echo ($m[1] ?? 'No title') . "\n";
echo "Length: " . strlen($content) . "\n";
