<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SINGLE CASES ===\n";
foreach (\App\Models\SingleSuspectCase::all() as $sc) {
    $wrapped = \App\Models\ForensicCase::wrapRecord($sc);
    $docket = $wrapped ? $wrapped->docket_data : [];
    $seizures = $docket['seizures'] ?? [];
    $exCount = $sc->exhibits()->count();
    echo "{$sc->case_number} (Unit: {$sc->unit_name}): exhibits={$exCount}, docket_seizures=" . count($seizures) . "\n";
}

echo "\n=== MULTIPLE CASES ===\n";
foreach (\App\Models\MultipleSuspectCase::all() as $mc) {
    $wrapped = \App\Models\ForensicCase::wrapRecord($mc);
    $docket = $wrapped ? $wrapped->docket_data : [];
    $seizures = $docket['seizures'] ?? [];
    $exCount = $mc->exhibits()->count();
    echo "{$mc->case_number} (Unit: {$mc->unit_name}): exhibits={$exCount}, docket_seizures=" . count($seizures) . "\n";
}
