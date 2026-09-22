<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== EXHIBIT 36 (linked to case 30) ===\n";
$e = DB::table('exhibits')->where('id', 36)->first();
if ($e) { print_r((array) $e); } else { echo "Not found\n"; }

echo "\n=== ALL EXHIBITS FOR SINGLE_SUSPECT_CASE_ID=30 ===\n";
$exs = DB::table('exhibits')->where('single_suspect_case_id', 30)->get();
foreach ($exs as $ex) { print_r((array) $ex); }
