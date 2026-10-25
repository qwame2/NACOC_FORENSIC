<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'like', '%NCC-2026-8687%')->first();
$logs = \Illuminate\Support\Facades\DB::table('audit_logs')
    ->where('case_number', $case->case_number)
    ->orWhere('auditable_id', $case->id)
    ->orderBy('id', 'asc')
    ->get();

foreach ($logs as $l) {
    echo json_encode($l) . "\n";
}
