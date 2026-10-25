<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = \Illuminate\Support\Facades\Schema::getColumnListing('laboratory_examination_results');
print_r($cols);

$dsCols = \Illuminate\Support\Facades\Schema::getColumnListing('drug_screening');
echo "drug_screening columns:\n";
print_r($dsCols);
