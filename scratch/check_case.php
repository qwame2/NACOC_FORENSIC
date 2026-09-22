<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
echo "Case number: " . $case->case_number . "\n";
echo "case->suspect_name: " . $case->suspect_name . "\n";
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '{}', true);
foreach ($d['suspects'] ?? [] as $i => $s) {
    echo "--- Suspect $i ---\n";
    echo "suspect_number: " . ($s['suspect_number'] ?? 'N/A') . "\n";
    echo "suspect_name: " . ($s['suspect_name'] ?? 'N/A') . "\n";
    echo "association_role: " . ($s['association_role'] ?? 'N/A') . "\n";
    echo "role: " . ($s['role'] ?? 'N/A') . "\n";
}
