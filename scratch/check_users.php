<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$heads = \App\Models\User::all();
foreach ($heads as $u) {
    echo "ID: {$u->id} | Name: {$u->fullname} | Unit: {$u->unit} | IsHead: " . ($u->is_head ? 'YES' : 'NO') . " | IsStaff: " . ($u->is_staff ? 'YES' : 'NO') . "\n";
}
