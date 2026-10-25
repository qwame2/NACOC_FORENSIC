<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::where('username', 'anna')->first();
if ($u) {
    echo "User: " . $u->username . "\n";
    echo "Check 'password': " . (\Illuminate\Support\Facades\Hash::check('password', $u->password) ? 'YES' : 'NO') . "\n";
    echo "Check 'password123': " . (\Illuminate\Support\Facades\Hash::check('password123', $u->password) ? 'YES' : 'NO') . "\n";
    echo "Check '12345678': " . (\Illuminate\Support\Facades\Hash::check('12345678', $u->password) ? 'YES' : 'NO') . "\n";
    echo "Check 'admin123': " . (\Illuminate\Support\Facades\Hash::check('admin123', $u->password) ? 'YES' : 'NO') . "\n";
}
