<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\ForensicCase::where('case_number', 'CSS-2026-8876')->first();
$ex = \App\Models\Exhibit::where('single_suspect_case_id', $c->id)->orWhere('multiple_suspect_case_id', $c->id)->first();
if ($ex) {
    foreach ($ex->toArray() as $k => $v) {
        if (!empty($v) && $k !== 'evidence_photos') {
            echo "$k => $v\n";
        }
    }
}

echo "\n--- Case underlying record ---\n";
foreach ($c->underlyingRecord->toArray() as $k => $v) {
    if (!empty($v) && !str_contains($k, 'photo') && !str_contains($k, 'data') && !str_contains($k, 'mugshot')) {
        echo "$k => $v\n";
    }
}
