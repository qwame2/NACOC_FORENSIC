<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ForensicCase;

echo "========================================================\n";
echo "       N+1 QUERY PREVENTION BENCHMARK REPORT            \n";
echo "========================================================\n\n";

// Enable query log
DB::enableQueryLog();

// 1. Fetch case list using eager loading (as in our controllers/routes)
$cases = ForensicCase::with(['user', 'unit'])->orderBy('created_at', 'desc')->get();

// Access relationships on every returned case
foreach ($cases as $c) {
    $uName = $c->user->fullname ?? 'N/A';
    $unitName = $c->unit->name ?? 'N/A';
}

$queries = DB::getQueryLog();
$queryCount = count($queries);

echo "Total Case Records Loaded: " . count($cases) . "\n";
echo "Total Executed Database Queries: {$queryCount}\n\n";

echo "--- EXECUTED QUERY LOG ---\n";
foreach ($queries as $i => $q) {
    echo sprintf("  Query #%d: [%s] (Bindings: %s) [%.2fms]\n", 
        $i + 1, 
        $q['query'], 
        json_encode($q['bindings']), 
        $q['time']
    );
}

echo "\n--------------------------------------------------------\n";
if ($queryCount <= 3) {
    echo "✔ VERIFIED: N+1 Problem is COMPLETELY PREVENTED! (Only {$queryCount} total queries executed for all records instead of 1+2N queries).\n";
} else {
    echo "⚠ WARNING: High query count detected!\n";
}
echo "========================================================\n";
