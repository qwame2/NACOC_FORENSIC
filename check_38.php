<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ex = \App\Models\Exhibit::find(38);
echo "Exhibit 38:\n";
print_r($ex ? $ex->toArray() : 'not found');

$lab = \App\Models\LaboratoryExaminationResult::find(38);
echo "\nLab Result 38:\n";
print_r($lab ? $lab->toArray() : 'not found');
