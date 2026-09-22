<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COLUMNS IN laboratory_examination_results ===\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'laboratory_examination_results' ORDER BY ordinal_position");
foreach ($cols as $c) {
    echo "  {$c->column_name}  ({$c->data_type})\n";
}

echo "\n=== SAMPLE ROWS ===\n";
$rows = DB::select("SELECT * FROM laboratory_examination_results LIMIT 3");
if (empty($rows)) {
    echo "  (no rows)\n";
} else {
    foreach ($rows as $r) {
        print_r((array) $r);
    }
}
