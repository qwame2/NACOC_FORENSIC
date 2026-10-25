<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$views = [
    'Crime_Scene_Support.head.head',
    'Lab_Analysis.lab_analysis',
    'Digital_Forensics.digital_forensics',
    'Evidence_Management.evidence_management',
    'Forensic_Head.head',
];

foreach ($views as $v) {
    try {
        $html = view($v)->render();
        echo "$v: SUCCESS (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "$v: FAILED -> " . $e->getMessage() . "\n";
    }
}
