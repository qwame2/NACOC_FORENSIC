<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\ForensicCase::all() as $c) {
    echo "==========================================\n";
    echo "Case ID: {$c->id} | Case No: {$c->case_number} | Unit: {$c->unit_name}\n";
    echo "Title: {$c->title}\n";
    echo "Suspect Name (model): {$c->suspect_name}\n";
    $d = is_array($c->docket_data) ? $c->docket_data : json_decode($c->docket_data, true);
    if (!empty($d['suspects'])) {
        echo "Suspects in docket_data:\n";
        foreach ($d['suspects'] as $idx => $s) {
            $name = $s['suspect_name'] ?? 'N/A';
            $role = $s['association_role'] ?? ($s['role'] ?? 'N/A');
            $assoc = !empty($s['is_associate']) ? 'true' : 'false';
            echo "  [$idx] Name: '$name' | Role: '$role' | is_associate: $assoc\n";
        }
    }
    if (!empty($d['seizures'])) {
        echo "Seizures in docket_data:\n";
        foreach ($d['seizures'] as $idx => $sz) {
            $name = $sz['seizure_name'] ?? ($sz['name'] ?? 'N/A');
            $assocP = $sz['associated_person'] ?? ($sz['suspect_name'] ?? 'N/A');
            echo "  [$idx] Seizure: '$name' | Owner/Assoc: '$assocP'\n";
        }
    }
}
