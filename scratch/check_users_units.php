<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== UNITS ===\n";
foreach (\App\Models\Unit::all() as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Code: {$u->code}\n";
}

echo "\n=== USERS ===\n";
foreach (\App\Models\User::all() as $usr) {
    echo "ID: {$usr->id} | Username: {$usr->username} | Name: {$usr->fullname} | Unit: {$usr->unit} | Head: " . ($usr->is_head ? 'YES' : 'NO') . "\n";
}
