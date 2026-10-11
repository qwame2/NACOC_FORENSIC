<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$savedReports = \App\Models\DpfReport::latest()->get();
echo "Saved Reports count: " . $savedReports->count() . "\n";
foreach ($savedReports as $r) {
    echo "Report: " . $r->case_number . " | created_at: " . $r->created_at . " | investigation_date: " . $r->investigation_date . "\n";
}

$cases = \App\Models\ForensicCase::allCases()->filter(fn($c) => $c->isDigitalCase())->values();
echo "\nDigital Cases count: " . $cases->count() . "\n";
foreach ($cases as $c) {
    echo "Case: " . $c->case_number . " | created_at: " . $c->created_at . " | arrest_date: " . $c->arrest_date . "\n";
}
