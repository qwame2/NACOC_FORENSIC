<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::first();
echo "User: " . $u->username . "\n";
foreach(['password', 'password123', 'Passcode123!', '12345678', 'anna123', 'admin', 'admin123', 'secret'] as $p) {
    if (\Illuminate\Support\Facades\Hash::check($p, $u->password)) {
        echo "Password is: " . $p . "\n";
        exit;
    }
}
echo "Not found in common list\n";
