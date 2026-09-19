<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if ($case) {
    echo "Primary Suspects in case:\n";
    foreach ($case->docket_data['suspects'] ?? [] as $s) {
        $role = $s['association_role'] ?? ($s['role'] ?? 'Primary');
        $isAssoc = !empty($s['is_associate']);
        echo " - " . ($s['suspect_name'] ?? 'N/A') . " (Role: {$role}, IsAssoc: " . ($isAssoc ? 'Yes' : 'No') . ")\n";
    }
}
