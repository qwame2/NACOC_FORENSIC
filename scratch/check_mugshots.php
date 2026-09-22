<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::find(69);
$dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
$rawSuspects = $dData['suspects'] ?? [];

echo "=== EXTRACTED MUGSHOTS PER SUSPECT ===\n";

foreach ($rawSuspects as $idx => $s) {
    $sMugFront = $s['mugshots']['front'] ?? ($s['mugshot_front'] ?? ($s['front'] ?? ($s['front_profile'] ?? '')));
    $sMugLeft  = $s['mugshots']['left-profile'] ?? ($s['mugshots']['left'] ?? ($s['mugshot_left'] ?? ($s['left'] ?? ($s['left_profile'] ?? ''))));
    $sMugRight = $s['mugshots']['right-profile'] ?? ($s['mugshots']['right'] ?? ($s['mugshot_right'] ?? ($s['right'] ?? ($s['right_profile'] ?? ''))));

    if ($idx === 0) {
        if (empty($sMugFront)) $sMugFront = $case->mugshot_front ?: ($dData['mugshots']['front'] ?? '');
        if (empty($sMugLeft))  $sMugLeft  = $case->mugshot_left ?: ($dData['mugshots']['left-profile'] ?? ($dData['mugshots']['left'] ?? ''));
        if (empty($sMugRight)) $sMugRight = $case->mugshot_right ?: ($dData['mugshots']['right-profile'] ?? ($dData['mugshots']['right'] ?? ''));
    }

    echo "Suspect $idx (" . ($s['suspect_name'] ?? 'unnamed') . "):\n";
    echo "  Front: " . ($sMugFront ? strlen($sMugFront) . ' bytes' : 'EMPTY') . "\n";
    echo "  Left:  " . ($sMugLeft ? strlen($sMugLeft) . ' bytes' : 'EMPTY') . "\n";
    echo "  Right: " . ($sMugRight ? strlen($sMugRight) . ' bytes' : 'EMPTY') . "\n";
}
