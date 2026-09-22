<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== COLUMNS INSPECTOR ===\n\n";
$cols = Schema::getColumnListing('single_suspect_cases');
echo "single_suspect_cases columns count: " . count($cols) . "\n";
echo "Columns matching confirmatory:\n";
foreach ($cols as $c) {
    if (str_contains($c, 'confirmatory') || str_contains($c, 'lab') || str_contains($c, 'sample')) {
        echo " - {$c}\n";
    }
}
