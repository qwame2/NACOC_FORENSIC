<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if ($c) {
    echo "=== LAB EXAMINATION ===\n";
    $lab = $c->docket_data['lab_examination'] ?? [];
    echo "analyst: " . ($lab['analyst'] ?? '') . "\n";
    echo "confirmatory_result: " . ($lab['confirmatory_result'] ?? '') . "\n";
    echo "presumptive_result: " . ($lab['presumptive_result'] ?? '') . "\n";
    echo "substance_identification: " . ($lab['substance_identification'] ?? '') . "\n";
    echo "sample_weight: " . json_encode($lab['sample_weight'] ?? []) . "\n";

    echo "\n=== SEIZURES ===\n";
    foreach ($c->docket_data['seizures'] ?? [] as $i => $sz) {
        echo "#" . ($i+1) . " Name: " . ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '')) 
            . " | Cat: " . ($sz['category'] ?? '') 
            . " | Owner: " . ($sz['suspect_name'] ?? ($sz['owner'] ?? ($sz['seizure_owner'] ?? 'N/A'))) 
            . " | has_saved_lab: " . json_encode($sz['has_saved_lab'] ?? null)
            . " | presumptive_result: " . json_encode($sz['presumptive_result'] ?? null)
            . "\n";
    }
}
