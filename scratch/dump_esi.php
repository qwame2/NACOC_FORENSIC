<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\MultipleSuspectCase::where('case_number', 'NCC-2026-5667')->first();
$exs = App\Models\Exhibit::where('multiple_suspect_case_id', $c->id)->get();
echo "Exhibits count: " . count($exs) . "\n";
foreach ($exs as $ex) {
    echo "Exhibit ID: {$ex->id} | Number: {$ex->exhibit_number} | Cat: {$ex->exhibit_category} | Commodity: {$ex->commodity_name}\n";
    echo "  Suspect ID: {$ex->suspect_id}\n";
    echo "  Evidence Photos: " . json_encode($ex->evidence_photos) . "\n";
    echo "  Dev Model: {$ex->dev_model}\n";
}

$d = $c->docket_data ?: [];
echo "\nDocket Data Seizures (" . (isset($d['seizures']) ? count($d['seizures']) : 0) . "):\n";
if (!empty($d['seizures'])) {
    foreach ($d['seizures'] as $i => $sz) {
        echo "  [{$i}] name: " . ($sz['seizure_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['commodity_name'] ?? 'N/A'))) . "\n";
        echo "      category: " . ($sz['category'] ?? ($sz['exhibit_category'] ?? 'N/A')) . "\n";
        echo "      associated_person: " . ($sz['associated_person'] ?? 'N/A') . "\n";
        echo "      photos: " . json_encode($sz['evidence_photos'] ?? ($sz['photos'] ?? null)) . "\n";
    }
}

echo "\nDocket Data Suspects (" . (isset($d['suspects']) ? count($d['suspects']) : 0) . "):\n";
if (!empty($d['suspects'])) {
    foreach ($d['suspects'] as $i => $s) {
        echo "  [{$i}] name: {$s['suspect_name']}\n";
        echo "      exhibits: " . (isset($s['exhibits']) ? count($s['exhibits']) : 0) . "\n";
        echo "      seizures: " . (isset($s['seizures']) ? count($s['seizures']) : 0) . "\n";
        echo "      evidence_photos: " . (isset($s['evidence_photos']) ? json_encode($s['evidence_photos']) : 'none') . "\n";
    }
}
