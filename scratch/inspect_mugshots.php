<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- CaseSuspects Mugshots ---\n";
$cs = \App\Models\CaseSuspect::all();
foreach ($cs as $s) {
    $mug = $s->mugshot_front;
    $mugType = 'null';
    if ($mug) {
        if (str_starts_with($mug, 'data:')) $mugType = 'data-uri (' . substr($mug, 0, 30) . '...) len=' . strlen($mug);
        elseif (str_starts_with($mug, 'http')) $mugType = 'http-url: ' . $mug;
        else $mugType = 'path: ' . $mug;
    }
    echo "Suspect #{$s->id} ({$s->suspect_name}): {$mugType}\n";
}

echo "--- SingleSuspectCase Mugshots ---\n";
$ssc = \App\Models\SingleSuspectCase::all();
foreach ($ssc as $s) {
    $mug = $s->mugshot_front;
    $mugType = 'null';
    if ($mug) {
        if (str_starts_with($mug, 'data:')) $mugType = 'data-uri (' . substr($mug, 0, 30) . '...) len=' . strlen($mug);
        elseif (str_starts_with($mug, 'http')) $mugType = 'http-url: ' . $mug;
        else $mugType = 'path: ' . $mug;
    }
    echo "Single Case #{$s->id} ({$s->suspect_name}): {$mugType}\n";
}

echo "--- DrugScreening records ---\n";
$ds = \App\Models\DrugScreening::with('suspect')->get();
foreach ($ds as $d) {
    $s = $d->suspect;
    $mug = $s ? $s->mugshot_front : null;
    $mugType = 'null';
    if ($mug) {
        if (str_starts_with($mug, 'data:')) $mugType = 'data-uri (' . substr($mug, 0, 30) . '...)';
        else $mugType = $mug;
    }
    echo "Screening #{$d->id} for suspect " . ($s ? $s->suspect_name : 'N/A') . ": mugshot=" . $mugType . "\n";
}
