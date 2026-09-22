<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== USERS IN DATABASE ===\n";
$users = User::all();
if ($users->isEmpty()) {
    echo "No users found in database!\n";
} else {
    foreach ($users as $u) {
        echo "ID: {$u->id} | Username: {$u->username} | ServiceID: {$u->service_id} | Email: {$u->email} | FullName: {$u->fullname} | Unit: {$u->unit} | Head: " . ($u->is_head ? 'YES' : 'NO') . " | Approved: " . ($u->is_approved ? 'YES' : 'NO') . "\n";
        echo "   Password Hash in DB: " . substr($u->password, 0, 30) . "...\n";
    }
}
