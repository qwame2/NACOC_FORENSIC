<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

$usernames = ['anna', 'sam', 'john', 'SD-789', 'SD-989', 'SD-098'];
$passwords = ['password123', 'password', 'Password123!', 'anna', 'sam', 'john'];

echo "=== TESTING HASH CHECK & LOGIN ===\n";

foreach ($usernames as $id) {
    $user = User::where('service_id', $id)
        ->orWhere('username', $id)
        ->orWhere('email', $id)
        ->first();
    if (!$user) {
        echo "User '{$id}' not found!\n";
        continue;
    }
    echo "\nFound user for '{$id}': username={$user->username}, service_id={$user->service_id}\n";
    foreach ($passwords as $p) {
        $check = Hash::check($p, $user->password);
        if ($check) {
            echo "  ✓ Password '{$p}' MATCHES Hash!\n";
        }
    }
}
