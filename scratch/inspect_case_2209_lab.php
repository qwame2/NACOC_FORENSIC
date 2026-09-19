<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true);

echo "=== LAB EXAMINATION ===\n";
var_dump($d['lab_examination'] ?? null);

echo "\n=== LAB EXAMINATIONS LIST ===\n";
var_dump($d['lab_examinations_list'] ?? null);

echo "\n=== SEIZURES ===\n";
foreach ($d['seizures'] ?? [] as $idx => $sz) {
    echo "--- Seizure #$idx ---\n";
    echo "Category: " . ($sz['category'] ?? '') . "\n";
    echo "Drug: " . ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '')) . "\n";
    echo "Owner: " . ($sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ''))) . "\n";
    echo "Exh No: " . ($sz['exhibit_number'] ?? '') . "\n";
    echo "Has Saved Lab: " . (!empty($sz['has_saved_lab']) ? 'YES' : 'NO') . "\n";
    echo "Presumptive: " . ($sz['presumptive_result'] ?? 'N/A') . "\n";
    echo "Confirmatory: " . ($sz['confirmatory_result'] ?? 'N/A') . "\n";
}
