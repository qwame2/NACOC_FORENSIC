<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- SINGLE SUSPECT CASES ---\n";
$singles = App\Models\SingleSuspectCase::latest()->take(5)->get();
foreach ($singles as $c) {
    echo "ID: {$c->id} | Case#: {$c->case_number} | Suspect: {$c->suspect_name} | Unit: {$c->unit_name}\n";
    echo "  exhibit_category: {$c->exhibit_category} | seizure_name_drug: {$c->seizure_name_drug} | seizure_name: {$c->seizure_name}\n";
    echo "  evidence_photos (case attr): " . json_encode($c->evidence_photos) . "\n";
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    echo "  docket evidence_photos: " . json_encode($d['evidence_photos'] ?? null) . "\n";
    if (!empty($d['seizures'])) {
        foreach ($d['seizures'] as $idx => $sz) {
            echo "  seizure[{$idx}]: " . ($sz['seizure_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['category'] ?? 'noname'))) 
                . " | assoc: " . ($sz['associated_person'] ?? 'none')
                . " | photos: " . json_encode($sz['evidence_photos'] ?? ($sz['photos'] ?? null)) . "\n";
        }
    }
    $dbEx = App\Models\Exhibit::where('single_suspect_case_id', $c->id)->get();
    foreach ($dbEx as $ex) {
        echo "  db exhibit: id={$ex->id}, cat={$ex->exhibit_category}, name={$ex->commodity_name}, photos=" . json_encode($ex->evidence_photos) . "\n";
    }
    echo "--------------------------------------------------------\n";
}

echo "\n--- MULTIPLE SUSPECT CASES ---\n";
$multis = App\Models\MultipleSuspectCase::latest()->take(5)->get();
foreach ($multis as $c) {
    echo "ID: {$c->id} | Case#: {$c->case_number} | Title: {$c->title} | Unit: {$c->unit_name}\n";
    echo "  exhibit_category: {$c->exhibit_category} | seizure_name_drug: {$c->seizure_name_drug} | seizure_name: {$c->seizure_name}\n";
    echo "  evidence_photos (case attr): " . json_encode($c->evidence_photos) . "\n";
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data, true) ?: []);
    echo "  docket evidence_photos: " . json_encode($d['evidence_photos'] ?? null) . "\n";
    if (!empty($d['seizures'])) {
        foreach ($d['seizures'] as $idx => $sz) {
            echo "  seizure[{$idx}]: " . ($sz['seizure_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['category'] ?? 'noname'))) 
                . " | assoc: " . ($sz['associated_person'] ?? 'none')
                . " | photos: " . json_encode($sz['evidence_photos'] ?? ($sz['photos'] ?? null)) . "\n";
        }
    }
    $dbEx = App\Models\Exhibit::where('multiple_suspect_case_id', $c->id)->get();
    foreach ($dbEx as $ex) {
        echo "  db exhibit: id={$ex->id}, cat={$ex->exhibit_category}, name={$ex->commodity_name}, photos=" . json_encode($ex->evidence_photos) . "\n";
    }
    echo "--------------------------------------------------------\n";
}
