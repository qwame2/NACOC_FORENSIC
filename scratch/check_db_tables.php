<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
foreach ($tables as $t) {
    $tName = $t->table_name;
    if (str_contains($tName, 'case') || str_contains($tName, 'docket') || str_contains($tName, 'single') || str_contains($tName, 'multiple')) {
        $count = DB::table($tName)->count();
        echo "Table: {$tName} -> {$count} rows\n";
    }
}
