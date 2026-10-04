<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$latestSingle = App\Models\SingleSuspectCase::latest()->first();
if ($latestSingle) {
    echo "Latest Single Case: " . $latestSingle->case_number . "\n";
    echo "Phone: " . var_export($latestSingle->phone, true) . "\n";
    echo "Phones: " . var_export($latestSingle->phones, true) . "\n";
}

$latestMulti = App\Models\MultipleSuspectCase::latest()->first();
if ($latestMulti) {
    echo "\nLatest Multi Case: " . $latestMulti->case_number . "\n";
    foreach ($latestMulti->suspects as $s) {
        echo "Suspect #" . $s->suspect_number . " (" . $s->suspect_name . "): phone=" . var_export($s->phone, true) . ", phones=" . var_export($s->phones, true) . "\n";
    }
}
