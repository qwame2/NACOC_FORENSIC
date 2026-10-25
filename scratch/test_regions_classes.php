<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
auth()->login(\App\Models\User::first());

$html = view('Evidence_Management.evidence_management')->render();

preg_match('/id="tac-path-greater-accra"[^>]+class="([^"]+)"/', $html, $mGA);
preg_match('/id="tac-path-ashanti"[^>]+class="([^"]+)"/', $html, $mAsh);

echo "Greater Accra (Highest Caseload):\n" . ($mGA[1] ?? 'NOT FOUND') . "\n\n";
echo "Ashanti (Lower Caseload):\n" . ($mAsh[1] ?? 'NOT FOUND') . "\n";
