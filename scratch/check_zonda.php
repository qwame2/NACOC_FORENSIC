<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

foreach (ForensicCase::all() as $c) {
    echo "CASE {$c->id}: {$c->case_number}\n";
    echo "  seizure_name: {$c->seizure_name}\n";
    echo "  seizure_name_drug: " . ($c->seizure_name_drug ?? 'N/A') . "\n";
    echo "  exhibit_category: {$c->exhibit_category}\n";
    $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
    if (!empty($docket['seizures'])) {
        foreach ($docket['seizures'] as $idx => $s) {
            echo "  Seizure [$idx]: " . json_encode($s) . "\n";
        }
    }
    echo "\n";
}
