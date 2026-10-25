<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'like', '%NCC-2026-8687%')->first();
$d = $case->docket_data;
echo "docket_data type: " . gettype($d) . "\n";
$arr = is_array($d) ? $d : json_decode($d, true);

echo "seizures in arr:\n";
print_r($arr['seizures'] ?? 'no seizures');

echo "\nunderlying record:\n";
print_r($case->underlyingRecord ? $case->underlyingRecord->toArray() : 'no underlying');
