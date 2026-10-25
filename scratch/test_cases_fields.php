<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cases = \App\Models\ForensicCase::allCases();
echo 'Total cases: ' . count($cases) . "\n";
foreach ($cases->take(5) as $c) {
    echo "Case: {$c->case_number} | Suspect: {$c->suspect_name} | Region: {$c->region} | Raid: {$c->raid_location} | Status: {$c->status_badge}\n";
}
