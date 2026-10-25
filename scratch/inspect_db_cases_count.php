<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "SingleSuspectCase count: " . \App\Models\SingleSuspectCase::count() . PHP_EOL;
echo "MultipleSuspectCase count: " . \App\Models\MultipleSuspectCase::count() . PHP_EOL;

echo "\n--- All Single Suspect Cases ---\n";
foreach (\App\Models\SingleSuspectCase::all() as $c) {
    echo "Single #{$c->id} | {$c->case_number} | Suspect: {$c->suspect_name} | Unit: {$c->unit_name} | Status: {$c->status} | Created: {$c->created_at}\n";
}

echo "\n--- All Multiple Suspect Cases ---\n";
foreach (\App\Models\MultipleSuspectCase::all() as $c) {
    echo "Multiple #{$c->id} | {$c->case_number} | Title: {$c->title} | Unit: {$c->unit_name} | Status: {$c->status} | Created: {$c->created_at}\n";
}

echo "\n--- All ForensicCase::allCases() ---\n";
$all = \App\Models\ForensicCase::allCases();
echo "Total in ForensicCase::allCases(): " . $all->count() . PHP_EOL;
foreach ($all as $c) {
    echo "Unified #{$c->id} | {$c->case_number} | Type: {$c->case_type} | Suspect: {$c->suspect_name} | Status: {$c->status}\n";
}
