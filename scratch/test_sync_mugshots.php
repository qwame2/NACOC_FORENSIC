<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Sync any missing mugshots from SingleSuspectCase into CaseSuspect
$singleCases = \App\Models\SingleSuspectCase::all();
foreach ($singleCases as $sc) {
    if ($sc->mugshot_front) {
        $cs = \App\Models\CaseSuspect::where('suspect_name', $sc->suspect_name)
            ->whereNull('mugshot_front')
            ->first();
        if ($cs) {
            $cs->update(['mugshot_front' => $sc->mugshot_front]);
            echo "Updated mugshot for {$cs->suspect_name} (ID: {$cs->id})\n";
        }
    }
}

// Check all CaseSuspects now
$csList = \App\Models\CaseSuspect::all();
foreach ($csList as $cs) {
    echo "CaseSuspect #{$cs->id} {$cs->suspect_name}: mugshot=" . ($cs->mugshot_front ? 'YES (len=' . strlen($cs->mugshot_front) . ')' : 'NO') . "\n";
}
