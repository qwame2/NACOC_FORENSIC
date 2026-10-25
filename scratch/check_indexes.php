<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['single_suspect_cases', 'multiple_suspect_cases', 'case_suspects', 'exhibits'];
foreach ($tables as $table) {
    echo "=== Indexes for {$table} ===\n";
    $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEXES FROM {$table}");
    foreach ($indexes as $idx) {
        echo "  {$idx->Key_name} ({$idx->Column_name}) - Non_unique: {$idx->Non_unique}\n";
    }
}
