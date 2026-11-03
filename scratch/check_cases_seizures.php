<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sc = \App\Models\SingleSuspectCase::where('case_number', 'NCC-2026-6351')->first();
if ($sc) {
    echo "Single Case NCC-2026-6351:\n";
    print_r($sc->getAttributes());
}

$mc = \App\Models\MultipleSuspectCase::where('case_number', 'CSS-2026-DEBUG1')->first();
if ($mc) {
    echo "\nMultiple Case CSS-2026-DEBUG1:\n";
    print_r($mc->getAttributes());
}
