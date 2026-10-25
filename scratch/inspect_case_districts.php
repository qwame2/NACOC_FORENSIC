<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cases = \App\Models\ForensicCase::allCases();
foreach ($cases as $c) {
    echo "ID: {$c->id} | {$c->case_number} | Suspect: {$c->suspect_name} | Raid Loc: {$c->raid_location} | Loc: {$c->location} | Status: {$c->status}\n";
}
