<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "=== ROUTE SECURITY VERIFICATION ===\n\n";

$testRoutes = [
    ['GET', '/', 200, 'Public Login'],
    ['GET', '/login', 200, 'Public Login'],
    ['GET', '/register', 200, 'Public Register'],
    ['GET', '/api/check-user-approval?login_id=SD-101', 200, 'Public Approval Check API'],
    ['GET', '/cases', 302, 'Protected Cases Directory (Redirects to Login if unauthenticated)'],
    ['GET', '/Forensic_Head', 302, 'Protected HOF Command Dashboard (Redirects if unauthenticated)'],
    ['POST', '/cases/1/approve', [302, 419, 401, 403], 'Protected Case Approval Action (Blocked if unauthenticated)'],
    ['POST', '/Forensic_Head/users/1/approve', [302, 419, 401, 403], 'Protected User Approval Action (Blocked if unauthenticated)'],
];

$allPassed = true;

foreach ($testRoutes as $t) {
    list($method, $uri, $expectedStatuses, $label) = $t;
    if (!is_array($expectedStatuses)) {
        $expectedStatuses = [$expectedStatuses];
    }
    
    $req = Request::create($uri, $method);
    $res = $app->handle($req);
    $status = $res->getStatusCode();

    $statusOk = in_array($status, $expectedStatuses);
    $mark = $statusOk ? "[PASS]" : "[FAIL]";

    if (!$statusOk) $allPassed = false;

    $expStr = implode('/', $expectedStatuses);
    echo "{$mark} {$method} {$uri} -> Status {$status} (Expected: {$expStr}) - {$label}\n";
}

echo "\nResult: " . ($allPassed ? "ALL ROUTE SECURITY CHECKS PASSED SUCCESSFULLY!" : "SOME CHECKS FAILED.") . "\n";
