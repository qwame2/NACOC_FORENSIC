<?php
$content = file_get_contents('scratch/pdf_output_5584.html');
$lines = explode("\n", $content);
for ($i = 1140; $i < 1180; $i++) {
    if (isset($lines[$i])) {
        echo ($i + 1) . ': ' . $lines[$i] . "\n";
    }
}
