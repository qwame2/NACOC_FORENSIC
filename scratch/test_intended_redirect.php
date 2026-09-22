<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

echo "=== TESTING LOGIN REDIRECT BEHAVIOR ===\n";

$session = app('session')->driver();
$session->start();
session()->put('url.intended', url('/login'));

echo "Session url.intended before login: " . session('url.intended') . "\n";

$request = Request::create('/login', 'POST', [
    'login_id' => 'anna',
    'password' => 'password123',
]);
$request->setLaravelSession($session);

$controller = new AuthController();
$response = $controller->login($request);

echo "Response Status Code: " . $response->getStatusCode() . "\n";
echo "Redirect Target Location: " . $response->headers->get('Location') . "\n";

if ($response->headers->get('Location') === url('/login') || str_ends_with($response->headers->get('Location'), '/login')) {
    echo "❌ BUG CONFIRMED: User redirected back to /login despite correct credentials!\n";
} else {
    echo "✓ Target location is: " . $response->headers->get('Location') . "\n";
}
