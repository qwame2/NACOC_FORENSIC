<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$paths = [
    '/Lab_Analysis',
    '/Lab_Analysis?from=Crime+Scene+Support',
    '/lab-analysis',
    '/lab-analytical/1',
    '/Lab_Analysis/lab-result',
    '/Crime_Scene_Support/head',
    '/Evidence_Management',
    '/Digital_Forensics',
    '/cases?unit=Lab+Analysis',
];

foreach ($paths as $p) {
    $parts = parse_url($p);
    $path = $parts['path'] ?? '/';
    parse_str($parts['query'] ?? '', $query);
    $req = Illuminate\Http\Request::create($path, 'GET', $query);
    app()->instance('request', $req);

    $html = view('Crime_Scene_Support.partials.sidebar')->render();
    preg_match('/<div class="text-\[10px\][^>]*title="([^"]+)"[^>]*>([^<]+)<\/div>/', $html, $m);
    $unitDisplay = $m[2] ?? 'NOT FOUND';
    echo str_pad($p, 45) . " => " . $unitDisplay . PHP_EOL;
}
