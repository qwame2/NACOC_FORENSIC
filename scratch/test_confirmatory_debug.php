<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;

echo "=== CONFIRMATORY TEST DEBUG INSPECTOR ===\n\n";

$singles = SingleSuspectCase::latest()->take(5)->get();
foreach ($singles as $c) {
    $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
    $labExam = $docket['lab_examination'] ?? [];
    echo "Single Case #{$c->case_number} (ID: {$c->id}):\n";
    echo "  - case->confirmatory_test_result: " . var_export($c->confirmatory_test_result, true) . "\n";
    echo "  - docket['lab_examination']['confirmatory_result']: " . var_export($labExam['confirmatory_result'] ?? null, true) . "\n";
    if (!empty($docket['seizures'])) {
        foreach ($docket['seizures'] as $sIdx => $sz) {
            echo "  - seizure #{$sIdx} confirmatory_result: " . var_export($sz['confirmatory_result'] ?? null, true) . "\n";
        }
    }
    echo "\n";
}
