<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$suspects = \App\Models\CaseSuspect::all();
echo "CaseSuspects count: " . $suspects->count() . "\n";
foreach ($suspects as $s) {
    echo "ID: {$s->id} | Case ID: {$s->multiple_suspect_case_id} | Name: {$s->suspect_name} | Role: {$s->role} | Nationality: {$s->nationality} | Age: {$s->age} | Mug: " . ($s->mugshot_front ? 'YES' : 'NO') . "\n";
}
