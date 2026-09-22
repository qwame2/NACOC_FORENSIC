<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SingleSuspectCase;

$case = SingleSuspectCase::find(29);

$dd = $case->docket_data ?? [];

echo "=== ALL DOCKET_DATA KEYS (TOP LEVEL) ===\n";
print_r(array_keys($dd));

echo "\n=== CASE MODEL ATTRIBUTES ===\n";
$attrs = $case->getAttributes();
ksort($attrs);
foreach ($attrs as $k => $v) {
    echo "$k => " . var_export(is_array($v) ? '[ARRAY]' : (strlen((string)$v) > 80 ? substr((string)$v, 0, 80) . '...' : $v), true) . "\n";
}
