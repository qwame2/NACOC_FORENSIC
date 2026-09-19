<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
echo "=== CASE ATTRIBUTES ===\n";
foreach ($case->getAttributes() as $k => $v) {
    if ($k !== 'docket_data') {
        echo "$k: " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
}
