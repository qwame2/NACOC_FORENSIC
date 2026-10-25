<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\CaseController();
$request = \Illuminate\Http\Request::create('/api/dashboard/realtime', 'GET');
$response = $controller->dashboardRealtime($request);

echo "Status: " . $response->getStatusCode() . "\n";
$data = json_decode($response->getContent(), true);
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "Total Cases in Payload: " . count($data['cases']) . "\n";
echo "Metrics: " . json_encode($data['metrics']) . "\n";
if (!empty($data['cases'])) {
    foreach ($data['cases'] as $c) {
        echo "Case: {$c['case_number']} | Suspect: {$c['suspect_name']} | Region: {$c['region']} | Location: {$c['raid_location']}\n";
    }
}
