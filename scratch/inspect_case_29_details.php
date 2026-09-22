<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Exhibit;

$ex = Exhibit::find(35);
foreach ($ex->toArray() as $k => $v) {
    echo "$k => " . var_export($v, true) . "\n";
}
