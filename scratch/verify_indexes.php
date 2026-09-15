<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo "       DATABASE INDEX VERIFICATION REPORT (PostgreSQL)  \n";
echo "========================================================\n\n";

echo "--- 1. INDEXES ON 'cases' TABLE ---\n";
$casesIndexes = DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'cases'");
foreach ($casesIndexes as $idx) {
    echo sprintf("  - Name: %-35s\n    Definition: %s\n\n", $idx->indexname, $idx->indexdef);
}

echo "--- 2. INDEXES ON 'users' TABLE ---\n";
$usersIndexes = DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'users'");
foreach ($usersIndexes as $idx) {
    echo sprintf("  - Name: %-35s\n    Definition: %s\n\n", $idx->indexname, $idx->indexdef);
}

echo "--- 3. EXPLAIN QUERY PLAN PROOF (SEARCH BY STATUS & CREATED_AT) ---\n";
$plan1 = DB::select("EXPLAIN SELECT * FROM cases WHERE status = 'Lab Analysis' ORDER BY created_at DESC");
foreach ($plan1 as $p) {
    echo "  " . ($p->{'QUERY PLAN'} ?? json_encode($p)) . "\n";
}

echo "\n--- 4. EXPLAIN QUERY PLAN PROOF (SEARCH BY LABORATORY NUMBER) ---\n";
$plan2 = DB::select("EXPLAIN SELECT * FROM cases WHERE laboratory_number = 'Lab No.42/FS09/2026'");
foreach ($plan2 as $p) {
    echo "  " . ($p->{'QUERY PLAN'} ?? json_encode($p)) . "\n";
}

echo "\n========================================================\n";
