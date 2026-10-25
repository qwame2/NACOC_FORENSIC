<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('is_head', true)->first();
\Illuminate\Support\Facades\Auth::login($user);

$cases = \App\Models\ForensicCase::allCases();
$pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
$pendingCount = \App\Models\User::where('is_approved', false)->count();

try {
    $rendered = view('Forensic_Head.head', compact('cases', 'pendingCasesCount', 'pendingCount'))->render();
    echo "SUCCESS! Rendered length: " . strlen($rendered) . " bytes\n";
    echo "Contains FINNOVA: " . (str_contains($rendered, 'FINNOVA') ? 'YES' : 'NO') . "\n";
    echo "Contains Invoices: " . (str_contains($rendered, 'Invoices') ? 'YES' : 'NO') . "\n";
    echo "Contains ghanaMapSvg: " . (str_contains($rendered, 'ghanaMapSvg') ? 'YES' : 'NO') . "\n";
    echo "Contains 16 regions: " . (str_contains($rendered, 'path-greater-accra') ? 'YES' : 'NO') . "\n";
    echo "Contains API Polling: " . (str_contains($rendered, '/api/dashboard/realtime') ? 'YES' : 'NO') . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
