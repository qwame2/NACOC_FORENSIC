<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = glob(storage_path('framework/sessions/*'));
foreach ($sessions as $s) {
    if (basename($s) !== '.gitignore') {
        $content = file_get_contents($s);
        $data = @unserialize($content);
        if ($data) {
            echo basename($s) . ' => ' . json_encode($data) . "\n";
        }
    }
}
