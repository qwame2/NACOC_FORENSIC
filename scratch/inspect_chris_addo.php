<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\Exhibit;
use App\Http\Controllers\CaseController;

echo "=== ALL CASES IN DATABASE ===\n";
$cases = ForensicCase::all();
foreach ($cases as $c) {
    $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
    $suspects = $docket['suspects'] ?? $c->getSuspectsList();
    $hasChris = false;
    foreach ($suspects as $s) {
        $sName = $s['suspect_name'] ?? ($s['name'] ?? '');
        if (str_contains(strtolower($sName), 'chris') || str_contains(strtolower($sName), 'addo')) {
            $hasChris = true;
            break;
        }
    }
    if ($hasChris) {
        echo "Found case for Chris Addo:\n";
        echo "Case ID: {$c->id}, Case No: {$c->case_number}, Title: {$c->title}\n";
        echo "Case Exhibit Category: {$c->exhibit_category}, Seizure Name: {$c->seizure_name}\n";
        echo "Suspects:\n";
        print_r($suspects);
        echo "Docket Seizures:\n";
        print_r($docket['seizures'] ?? []);
        echo "DB Exhibits:\n";
        $dbEx = Exhibit::where('multiple_suspect_case_id', $c->id)->orWhere('single_suspect_case_id', $c->id)->get();
        print_r($dbEx->toArray());

        echo "--- Testing getExhibitsForSuspect for Chris Addo ---\n";
        foreach ($suspects as $s) {
            $sName = $s['suspect_name'] ?? ($s['name'] ?? '');
            $exs = $c->getExhibitsForSuspect($sName, $s['id'] ?? null, $s['associate_id'] ?? null, $s['role'] ?? null);
            echo "Exhibits for '{$sName}': " . count($exs) . "\n";
            print_r($exs);
        }
    }
}

echo "\n=== TESTING labResultPage output for drug records ===\n";
$req = new \Illuminate\Http\Request();
$controller = new CaseController();
$resp = $controller->labResultPage($req);
$data = $resp->getData();
$drugRecords = $data['drugRecords'] ?? collect();
foreach ($drugRecords as $dr) {
    echo "Drug Record Case No: {$dr['case_number']}, Drug: {$dr['drug_name']}\n";
    echo "Suspects in record:\n";
    foreach ($dr['suspects'] as $s) {
        echo " - {$s['suspect_name']} ({$s['role']})\n";
    }
}
