<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$multiExs = \App\Models\Exhibit::where('multiple_suspect_case_id', 24)->get();
echo "Exhibits for multiple_suspect_case_id 24: " . $multiExs->count() . "\n";
foreach ($multiExs as $e) {
    echo "ID: {$e->id}, Case Type: {$e->case_type}, multi_id: {$e->multiple_suspect_case_id}, cat: {$e->exhibit_category}, name: {$e->commodity_name}\n";
}

$singleExs = \App\Models\Exhibit::where('single_suspect_case_id', 24)->get();
echo "\nExhibits for single_suspect_case_id 24: " . $singleExs->count() . "\n";
foreach ($singleExs as $e) {
    echo "ID: {$e->id}, Case Type: {$e->case_type}, single_id: {$e->single_suspect_case_id}, cat: {$e->exhibit_category}, name: {$e->commodity_name}\n";
}
