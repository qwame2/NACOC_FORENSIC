<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\MultipleSuspectCase::find(21);
echo "TITLE: " . $c->title . "\n";
echo "UNIT: " . $c->unit_name . "\n";
echo "DOCKET_DATA:\n";
print_r($c->docket_data);
