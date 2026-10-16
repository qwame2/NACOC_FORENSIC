<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$c = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();
foreach ($c->docket_data['suspects'] ?? [] as $i => $s) {
    echo "SUSPECT $i:\n";
    foreach ($s as $k => $v) {
        if (!is_array($v) && strlen((string)$v) < 100) {
            echo "  $k => $v\n";
        }
    }
}
