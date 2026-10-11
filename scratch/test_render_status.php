<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$html = view('Digital_Forensics.forensic_status')->render();
$pos = strpos($html, 'Digital Forensic &amp; Investigation Directory');
if ($pos !== false) {
    echo "FOUND at pos: $pos\n";
    echo substr($html, $pos - 100, 2000);
} else {
    echo "NOT FOUND\n";
}
