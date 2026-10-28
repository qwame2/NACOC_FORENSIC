<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== RECENT AUDIT LOGS ===\n";
foreach (App\Models\AuditLog::latest()->take(10)->get() as $log) {
    echo "ID: {$log->id} | Action: {$log->action} | User: {$log->user_id} | Desc: {$log->description}\n";
    echo "  New vals: " . json_encode($log->new_values) . "\n";
    echo "--------------------------------------------------------\n";
}
