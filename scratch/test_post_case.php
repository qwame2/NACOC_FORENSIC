<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$controller = new \App\Http\Controllers\CaseController();
$request = Request::create('/cases', 'POST', [
    'case_number' => 'CSS-2026-7788',
    'title' => 'Republic Vs Kwame Mensah & Ors',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Kwame Mensah ("Cobra")',
    'priority' => 'HIGH',
    'status' => 'Awaiting Sign-off',
    'location' => 'East Legon Sector 4 (H/No 14 East Legon)',
    'description' => 'Offence: High-Purity Narcotics Trafficking | Raid Ref: RAID-2026-0419 | Custody: NACOC Holding Cell Sector B',
    'exhibits_summary' => '100 Kg High-Purity Cocaine',
    'commanding_officer' => 'Cmdr. Crime Scene Support',
    'submitting_officer' => 'Field Arrest Officer'
]);
$request->headers->set('Accept', 'application/json');

$response = $controller->store($request);
echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getContent() . "\n";
