<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();
$docketData = $case->docket_data ?? [];

$suspectsList = $case->getSuspectsList();
echo "suspectsList:\n";
foreach ($suspectsList as $i => $s) {
    echo "  $i: " . ($s['suspect_name'] ?? '') . " | role: " . ($s['association_role'] ?? $s['role'] ?? '') . " | is_associate: " . ($s['is_associate'] ?? '') . "\n";
}

$primaryAndCoNamesList = [];
$primarySuspectNamesList = [];
foreach ($suspectsList as $s) {
    $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
    $name = $s['suspect_name'] ?? '';
    if (str_contains($role, 'primary') || str_contains($role, 'co-suspect') || empty($s['is_associate'])) {
        $primaryAndCoNamesList[] = $name;
    }
    if (str_contains($role, 'primary')) {
        $primarySuspectNamesList[] = $name;
    }
}
echo "primaryAndCoNamesList: " . implode(', ', $primaryAndCoNamesList) . "\n";
echo "primarySuspectNamesList: " . implode(', ', $primarySuspectNamesList) . "\n";
