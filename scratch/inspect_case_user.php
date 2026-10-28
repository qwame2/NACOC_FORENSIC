<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LATEST SINGLE CASES ===\n";
foreach (App\Models\SingleSuspectCase::latest()->take(3)->get() as $c) {
    echo "ID: {$c->id} | Case#: {$c->case_number} | user_id: {$c->user_id} | submitting_officer: {$c->submitting_officer}\n";
    if ($c->user_id) {
        $u = App\Models\User::find($c->user_id);
        echo "  User: ID={$u->id}, name={$u->name}, fullname={$u->fullname}, username={$u->username}, unit={$u->unit}\n";
    }
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    echo "  unit_progress: " . json_encode($d['unit_progress'] ?? null) . "\n";
    echo "--------------------------------------------------------\n";
}

echo "=== LATEST MULTI CASES ===\n";
foreach (App\Models\MultipleSuspectCase::latest()->take(3)->get() as $c) {
    echo "ID: {$c->id} | Case#: {$c->case_number} | user_id: {$c->user_id} | submitting_officer: {$c->submitting_officer}\n";
    if ($c->user_id) {
        $u = App\Models\User::find($c->user_id);
        echo "  User: ID={$u->id}, name={$u->name}, fullname={$u->fullname}, username={$u->username}, unit={$u->unit}\n";
    }
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    echo "  unit_progress: " . json_encode($d['unit_progress'] ?? null) . "\n";
    echo "--------------------------------------------------------\n";
}
