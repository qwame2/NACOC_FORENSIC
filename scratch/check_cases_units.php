<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cases = \App\Models\ForensicCase::allCases();
foreach ($cases as $c) {
    echo "Case: " . ($c->case_number ?? $c->id) . "\n";
    echo "  ->unit: " . var_export($c->unit, true) . "\n";
    echo "  ->unit_name: " . var_export($c->unit_name ?? null, true) . "\n";
}
