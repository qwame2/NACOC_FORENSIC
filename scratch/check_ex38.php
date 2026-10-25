<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ex38 = \App\Models\Exhibit::find(38);
echo "Exhibit 38: single_id=" . ($ex38->single_suspect_case_id ?: 'null') . ", multi_id=" . ($ex38->multiple_suspect_case_id ?: 'null') . ", cat=" . $ex38->exhibit_category . ", comm=" . $ex38->commodity_name . "\n";
