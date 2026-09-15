<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\User::all() as $u) {
    echo "ID: {$u->id} | ServiceID: {$u->service_id} | Username: {$u->username} | Email: {$u->email} | Approved: " . ($u->is_approved ? 'YES' : 'NO') . " | Head: " . ($u->is_head ? 'YES' : 'NO') . "\n";
}
