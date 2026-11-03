<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$beforeCount = \App\Models\Exhibit::count();
echo "Before sync exhibit count: {$beforeCount}\n";

$controller = new \App\Http\Controllers\EvidenceManagementController();
$refMethod = new ReflectionMethod($controller, 'vault');
echo "Vault method is callable\n";
