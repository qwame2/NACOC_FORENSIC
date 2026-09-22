<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;

echo "=== SEEDED DATA SUMMARY ===\n";
echo "Single Suspect Cases Count: " . SingleSuspectCase::count() . "\n";
echo "Multiple Suspect Cases Count: " . MultipleSuspectCase::count() . "\n";
echo "Case Suspects Count: " . CaseSuspect::count() . "\n";
echo "Exhibits Count: " . Exhibit::count() . "\n\n";

$allCases = ForensicCase::all();
echo "Total Wrapped Forensic Cases: " . count($allCases) . "\n\n";

foreach ($allCases as $idx => $c) {
    echo "--------------------------------------------------------\n";
    echo "CASE #" . ($idx + 1) . ": ID={$c->id} | Ref={$c->case_number} | Title={$c->title} | Mode={$c->suspect_docket_mode}\n";
    echo "Suspect Name: {$c->suspect_name}\n";
    echo "Seizing Officer: " . var_export($c->seizure_officer, true) . "\n";
    echo "Reason / Authority: " . var_export($c->seizure_reason, true) . "\n";
    echo "Front Mugshot Attached: " . (!empty($c->mugshot_front) ? 'YES (' . strlen($c->mugshot_front) . ' bytes)' : 'NO') . "\n";
    echo "Left Mugshot Attached: " . (!empty($c->mugshot_left) ? 'YES (' . strlen($c->mugshot_left) . ' bytes)' : 'NO') . "\n";
    echo "Right Mugshot Attached: " . (!empty($c->mugshot_right) ? 'YES (' . strlen($c->mugshot_right) . ' bytes)' : 'NO') . "\n";
    
    $photos = $c->evidence_photos ?? [];
    echo "Evidence Photos Count: " . count($photos) . "\n";
    foreach ($photos as $pIdx => $pUrl) {
        echo "  - Photo #".($pIdx+1).": " . (str_starts_with($pUrl, 'data:') ? 'DATA URL (' . strlen($pUrl) . ' bytes)' : $pUrl) . "\n";
    }
}
