<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);
echo "=== LAB EXAM ===\n";
var_dump($d['lab_examination'] ?? []);
echo "\n=== SEIZURES ===\n";
var_dump($d['seizures'] ?? []);
echo "\n=== ASSOCIATE EXHIBITS ===\n";
var_dump($d['associate_exhibits'] ?? []);
