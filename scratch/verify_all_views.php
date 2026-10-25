<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
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
        echo "View $v OK (" . strlen($rendered) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "View $v ERROR: " . $e->getMessage() . "\n";
    }
}
