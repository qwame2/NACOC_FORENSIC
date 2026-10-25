<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\User::all() as $u) {
    echo $u->id . ' | ' . $u->username . ' | ' . $u->fullname . ' | ' . $u->unit . ' | is_head=' . ($u->is_head ? '1' : '0') . "\n";
}
