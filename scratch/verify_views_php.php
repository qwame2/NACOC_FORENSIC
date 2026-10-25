<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$views = [
    'Evidence_Management.evidence_management',
    'Forensic_Head.head',
    'Lab_Analysis.lab_analysis',
    'Crime_Scene_Support.head.head',
    'Digital_Forensics.digital_forensics'
];

foreach ($views as $v) {
    try {
        $rendered = view($v)->render();
        echo "[PASS] $v: rendered " . strlen($rendered) . " bytes\n";
    } catch (\Throwable $e) {
        echo "[FAIL] $v: " . $e->getMessage() . "\n";
    }
}
