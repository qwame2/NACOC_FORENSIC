<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

foreach (ForensicCase::all() as $c) {
    if (str_contains(strtolower(json_encode($c)), 'zonda')) {
        echo "FOUND ZONDA IN CASE {$c->id} ({$c->case_number}):\n";
        echo "Model attributes:\n";
        print_r($c->toArray());
        echo "\nDocket data:\n";
        $d = is_array($c->docket_data) ? $c->docket_data : json_decode($c->docket_data ?? '[]', true);
        print_r($d);
    }
}
