<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\AuthController();
$response = $controller->showRegister();
$content = $response->with('errors', new \Illuminate\Support\ViewErrorBag)->render();

if (str_contains($content, 'DIRECTORATE COMMAND MANDATE') || str_contains($content, 'Directorate Command Established')) {
    echo "Found mandate banner in output!\n";
} else {
    echo "Clean output! Mandate and restriction banners removed when Head of Forensic is registered.\n";
}
