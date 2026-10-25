<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ex = \App\Models\Exhibit::find(38);
echo "Exhibit 38 attributes:\n";
foreach ($ex->getAttributes() as $k => $v) {
    if ($v !== null && $v !== '') {
        echo "$k => $v\n";
    }
}
