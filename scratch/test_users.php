<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all(['id', 'fullname', 'username', 'service_id', 'unit', 'is_head', 'is_staff']);
echo json_encode($users, JSON_PRETTY_PRINT);
