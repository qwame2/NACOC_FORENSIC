<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\SingleSuspectCase::whereNotNull('docket_data')->first();
if ($c) {
    echo "Single Case {$c->case_number}:\n";
    $d = is_array($c->docket_data) ? $c->docket_data : json_decode($c->docket_data, true);
    print_r(array_keys($d ?? []));
}

$mc = \App\Models\MultipleSuspectCase::whereNotNull('docket_data')->first();
if ($mc) {
    echo "\nMultiple Case {$mc->case_number}:\n";
    $d = is_array($mc->docket_data) ? $mc->docket_data : json_decode($mc->docket_data, true);
    print_r(array_keys($d ?? []));
}

$ex = \App\Models\Exhibit::first();
if ($ex) {
    echo "\nSample Exhibit:\n";
    print_r($ex->toArray());
}
