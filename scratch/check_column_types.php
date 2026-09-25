<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'forensic_nacoc' AND DATA_TYPE = 'text'");

foreach ($cols as $c) {
    echo "{$c->TABLE_NAME} -> {$c->COLUMN_NAME}: {$c->DATA_TYPE}\n";
}
