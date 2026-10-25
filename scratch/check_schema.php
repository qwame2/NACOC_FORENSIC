<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('exhibits');
echo "Exhibits Columns:\n" . implode(', ', $columns) . "\n\n";

$suspectColumns = \Illuminate\Support\Facades\Schema::getColumnListing('suspects');
echo "Suspects Columns:\n" . implode(', ', $suspectColumns) . "\n\n";
