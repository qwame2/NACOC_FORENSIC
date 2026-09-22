<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

echo "Seizure #2:\n";
print_r($docketData['seizures'][2] ?? []);
