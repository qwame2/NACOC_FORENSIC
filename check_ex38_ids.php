<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ex = \App\Models\Exhibit::find(38);
echo "id => {$ex->id}\n";
echo "case_type => {$ex->case_type}\n";
echo "single_suspect_case_id => {$ex->single_suspect_case_id}\n";
echo "multiple_suspect_case_id => {$ex->multiple_suspect_case_id}\n";
echo "suspect_id => {$ex->suspect_id}\n";
echo "exhibit_number => {$ex->exhibit_number}\n";
echo "exhibit_category => {$ex->exhibit_category}\n";
echo "commodity_name => {$ex->commodity_name}\n";
