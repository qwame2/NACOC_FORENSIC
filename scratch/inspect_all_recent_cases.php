<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ALL SINGLE SUSPECT CASES (LAST 10) ===\n";
foreach (App\Models\SingleSuspectCase::latest()->take(10)->get() as $c) {
    echo "ID: {$c->id} | {$c->case_number} | title: {$c->title} | suspect: {$c->suspect_name} | user_id: {$c->user_id} | submitting_officer: {$c->submitting_officer} | updated_at: {$c->updated_at}\n";
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    if (!empty($d['unit_progress'])) {
        echo "  unit_progress: " . json_encode($d['unit_progress']) . "\n";
    }
}

echo "\n=== ALL MULTIPLE SUSPECT CASES (LAST 10) ===\n";
foreach (App\Models\MultipleSuspectCase::latest()->take(10)->get() as $c) {
    echo "ID: {$c->id} | {$c->case_number} | title: {$c->title} | user_id: {$c->user_id} | submitting_officer: {$c->submitting_officer} | updated_at: {$c->updated_at}\n";
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    if (!empty($d['unit_progress'])) {
        echo "  unit_progress: " . json_encode($d['unit_progress']) . "\n";
    }
}
