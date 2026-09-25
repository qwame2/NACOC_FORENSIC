<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-8946')->first();

echo "=== TESTING getExhibitsForSuspect FOR ERIC ADU (Primary) ===\n";
$ericExhibits = $case->getExhibitsForSuspect("Eric Adu");
print_r(array_column($ericExhibits, 'seizure_name'));

echo "\n=== TESTING getExhibitsForSuspect FOR LINDA ESSEN (Associate) ===\n";
$lindaExhibits = $case->getExhibitsForSuspect("Linda Essen");
print_r(array_column($lindaExhibits, 'seizure_name'));
