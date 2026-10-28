<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\SingleSuspectCase::where('case_number', 'NCC-2026-9272')->first();
if ($c) {
    echo "ID: {$c->id}\n";
    echo "case_number: {$c->case_number}\n";
    echo "user_id: {$c->user_id}\n";
    echo "submitting_officer: {$c->submitting_officer}\n";
    echo "unit_name: {$c->unit_name}\n";
    echo "created_at: {$c->created_at}\n";
    echo "updated_at: {$c->updated_at}\n";
    $u = App\Models\User::find($c->user_id);
    if ($u) {
        echo "User fullname: {$u->fullname}, username: {$u->username}, unit: {$u->unit}\n";
    }
    echo "docket_data:\n" . json_encode($c->docket_data, JSON_PRETTY_PRINT) . "\n";
}
