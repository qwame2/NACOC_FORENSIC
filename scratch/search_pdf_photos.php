<?php
$content = file_get_contents('c:/Users/USER/Desktop/ForensicNacoc/resources/views/Forensic_Head/case_pdf.blade.php');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'photo') !== false || stripos($line, 'gallery') !== false || stripos($line, 'img') !== false || stripos($line, 'visual') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
