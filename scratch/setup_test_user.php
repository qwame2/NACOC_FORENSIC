<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('username', 'anna')->orWhere('service_id', 'SD-878')->first();
if (!$user) {
    $user = new \App\Models\User();
    $user->fullname = 'Anita Johnson';
    $user->service_id = 'SD-878';
    $user->username = 'anna';
    $user->email = 'anna@nacoc.gov.gh';
}
$user->password = \Illuminate\Support\Facades\Hash::make('password123');
$user->is_approved = true;
$user->is_head = true;
$user->unit = 'Forensic Directorate';
$user->save();

echo "User anna configured cleanly. ServiceID: {$user->service_id}, Password: password123\n";
