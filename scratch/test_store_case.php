<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::create([
    'case_number' => 'CSS-2026-9812',
    'title' => 'Republic vs Kwame Mensah (Test Docket)',
    'unit_name' => 'Crime Scene Support',
    'commanding_officer' => 'Cmdr. Kwame Support',
    'submitting_officer' => 'Officer In-Charge',
    'suspect_name' => 'Kwame Mensah (Cobra)',
    'priority' => 'HIGH',
    'status' => 'Awaiting Sign-off',
    'location' => 'East Legon Sector 4',
    'description' => 'Narcotics Trafficking & Illegal Firearm Possession',
    'exhibits_summary' => '100 Kg High-Purity Cocaine',
    'hash_log' => 'HASH-TEST-9812-VERIFIED',
    'incident_date' => now(),
]);

echo "Created case ID: {$case->id}, Ref: {$case->case_number}, Status: {$case->status}\n";
