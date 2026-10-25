<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
echo "User: " . ($user ? $user->email : 'Guest') . PHP_EOL;

$cases = App\Models\ForensicCase::allCases();
echo "Cases retrieved: " . count($cases) . PHP_EOL;

$views = [
    'Forensic_Head.head',
    'Lab_Analysis.lab_analysis',
    'Evidence_Management.evidence_management',
    'Crime_Scene_Support.head.head',
    'Digital_Forensics.digital_forensics'
];

foreach ($views as $v) {
    try {
        $out = view($v, [
            'user' => $user,
            'cases' => $cases,
            'totalCases' => count($cases),
            'totalExhibits' => 12,
            'highPriorityCount' => 2,
            'criticalCount' => 1
        ])->render();
        echo "  [SUCCESS] {$v} rendered: " . strlen($out) . " bytes\n";
    } catch (Throwable $e) {
        echo "  [FAILED] {$v}: " . $e->getMessage() . "\n";
    }
}
