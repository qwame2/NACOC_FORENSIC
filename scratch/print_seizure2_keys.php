<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

$sz = $docketData['seizures'][2] ?? [];
echo "Category: " . ($sz['category'] ?? 'null') . "\n";
echo "Exhibit Category: " . ($sz['exhibit_category'] ?? 'null') . "\n";
echo "Seizure Category: " . ($sz['seizure_category'] ?? 'null') . "\n";
echo "Seizure Name: " . ($sz['seizure_name'] ?? 'null') . "\n";
echo "Seizure Name Drug: " . ($sz['seizure_name_drug'] ?? 'null') . "\n";
echo "Seizure Type: " . ($sz['seizure_type'] ?? 'null') . "\n";
echo "Exhibit Type: " . ($sz['exhibit_type'] ?? 'null') . "\n";
echo "Type: " . ($sz['type'] ?? 'null') . "\n";
echo "Associated Person: " . ($sz['associated_person'] ?? 'null') . "\n";
echo "Has Saved Lab: " . (var_export($sz['has_saved_lab'] ?? null, true)) . "\n";
echo "Weapon Model: " . ($sz['weapon_model'] ?? 'null') . "\n";
echo "Weapon Serial: " . ($sz['weapon_serial_number'] ?? 'null') . "\n";
