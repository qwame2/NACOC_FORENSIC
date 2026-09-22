<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::find(69);

echo "=== SUSPECTS (" . count($case->docket_data['suspects'] ?? []) . ") ===\n";
foreach (($case->docket_data['suspects'] ?? []) as $i => $s) {
    echo "--- Suspect $i ---\n";
    echo "Name: " . ($s['suspect_name'] ?? $s['name'] ?? '') . "\n";
    echo "Alias: " . ($s['suspect_alias'] ?? $s['alias'] ?? '') . "\n";
    echo "Role: " . ($s['association_role'] ?? $s['role'] ?? '') . "\n";
    echo "Gender: " . ($s['gender'] ?? '') . "\n";
    echo "DOB: " . ($s['dob'] ?? '') . "\n";
    echo "Ghana Card: " . ($s['ghana_card'] ?? '') . "\n";
    echo "Associated Exhibit: " . ($s['associated_exhibit'] ?? $s['exhibit_name'] ?? 'none') . "\n";
}

echo "\n=== SEIZURES (" . count($case->docket_data['seizures'] ?? []) . ") ===\n";
foreach (($case->docket_data['seizures'] ?? []) as $i => $sz) {
    echo "--- Seizure $i ---\n";
    echo "Drug Name: " . ($sz['seizure_name_drug'] ?? $sz['seizure_name'] ?? '') . "\n";
    echo "Owner/Associated Person: " . ($sz['associated_person'] ?? $sz['suspect_name'] ?? '') . "\n";
    echo "Quantity: " . ($sz['seizure_quantity'] ?? '') . "\n";
    echo "Weight: " . ($sz['seizure_weight'] ?? '') . "\n";
}
