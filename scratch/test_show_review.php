<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$case = App\Models\ForensicCase::where('case_number', 'NCC-2026-5667')->first();
if (!$case) {
    echo "ForensicCase wrapped not found directly via where, let's wrap MultipleSuspectCase\n";
    $m = App\Models\MultipleSuspectCase::where('case_number', 'NCC-2026-5667')->first();
    $case = App\Models\ForensicCase::wrapRecord($m);
}

echo "Case: " . $case->case_number . "\n";
echo "Suspect Name: " . $case->suspect_name . "\n";
echo "Title: " . $case->title . "\n";
echo "Exhibit Category: " . $case->exhibit_category . "\n";

$suspectsList = $case->getSuspectsList();
echo "Suspects Count: " . count($suspectsList) . "\n";
foreach ($suspectsList as $s) {
    echo "  Suspect: " . $s['suspect_name'] . " | role: " . ($s['association_role'] ?? 'none') . "\n";
    $exs = $case->getExhibitsForSuspect($s['suspect_name'], $s['id'] ?? null, $s['associate_id'] ?? null, $s['association_role'] ?? null);
    echo "    Exhibits count: " . count($exs) . "\n";
    foreach ($exs as $e) {
        echo "      Exhibit: " . ($e['seizure_name'] ?? $e['commodity_name'] ?? 'none') . " | Category: " . ($e['category'] ?? $e['exhibit_category'] ?? 'none') . " | Photos: " . json_encode($e['evidence_photos'] ?? $e['photos'] ?? null) . "\n";
    }
}
