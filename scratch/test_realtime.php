<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$allCases = \App\Models\ForensicCase::allCases();

$cityToRegion = [
    'accra' => 'Greater Accra',
    'legon' => 'Greater Accra',
    'tema' => 'Greater Accra',
    'agbogbloshie' => 'Greater Accra',
    'kumasi' => 'Ashanti',
    'obwasi' => 'Ashanti',
    'takoradi' => 'Western',
    'sekondi' => 'Western',
    'cape coast' => 'Central',
    'elmina' => 'Central',
    'koforidua' => 'Eastern',
    'ho' => 'Volta',
    'aflao' => 'Volta',
    'sunyani' => 'Bono',
    'techiman' => 'Bono East',
    'tamale' => 'Northern',
    'wa' => 'Upper West',
    'bolgatanga' => 'Upper East',
    'damongo' => 'Savannah',
    'nalerigu' => 'North East',
    'dambai' => 'Oti',
    'sefwi wiawso' => 'Western North',
    'goaso' => 'Ahafo',
];

$matchedCases = [];
foreach ($allCases as $c) {
    $loc = strtolower($c->raid_location ?? ($c->seizure_location ?? ''));
    $matchedReg = 'Greater Accra'; // default
    foreach ($cityToRegion as $city => $reg) {
        if (str_contains($loc, $city)) {
            $matchedReg = $reg;
            break;
        }
    }
    $matchedCases[] = [
        'case_number' => $c->case_number,
        'suspect' => $c->suspect_name,
        'location' => $c->raid_location,
        'region' => $matchedReg,
        'status' => $c->status,
    ];
}

echo json_encode([
    'total_cases' => $allCases->count(),
    'matched' => $matchedCases,
], JSON_PRETTY_PRINT);
