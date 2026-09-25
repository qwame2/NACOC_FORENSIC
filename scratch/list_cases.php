<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "SingleSuspectCases:\n";
foreach (\App\Models\SingleSuspectCase::all() as $s) {
    echo "  - ID: {$s->id} | CaseNo: {$s->case_number} | Title: {$s->title}\n";
}

echo "MultipleSuspectCases:\n";
foreach (\App\Models\MultipleSuspectCase::all() as $m) {
    echo "  - ID: {$m->id} | CaseNo: {$m->case_number} | Title: {$m->title}\n";
}
