<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();

if (!$case) {
    echo "Case CSS-2026-5584 not found in database. Searching all cases...\n";
    $allCases = ForensicCase::all();
    foreach ($allCases as $c) {
        echo " - ID: {$c->id} | Case #: {$c->case_number} | Title: {$c->title} | Suspect: {$c->suspect_name}\n";
    }
    exit;
}

echo "Found Case ID: {$case->id} | Case #: {$case->case_number}\n";
echo "Suspect Name: {$case->suspect_name}\n";

$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);
echo "Seizures count: " . count($docketData['seizures'] ?? []) . "\n";
foreach ($docketData['seizures'] ?? [] as $i => $sz) {
    $owner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? 'N/A'));
    $cat = $sz['exhibit_category'] ?? ($sz['category'] ?? 'N/A');
    $name = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? 'N/A');
    echo "  Seizure #{$i}: Owner: {$owner} | Category: {$cat} | Name: {$name}\n";
}

echo "Suspects count: " . count($docketData['suspects'] ?? []) . "\n";
foreach ($docketData['suspects'] ?? [] as $i => $sp) {
    $sName = $sp['suspect_name'] ?? ($sp['name'] ?? 'N/A');
    $role = $sp['association_role'] ?? ($sp['role'] ?? 'N/A');
    echo "  Suspect #{$i}: Name: {$sName} | Role: {$role}\n";
}

// Render view
$view = view('Forensic_Head.show_review', compact('case'))->render();

if (str_contains($view, 'WAKANDA ASIEDU')) {
    echo "View contains WAKANDA ASIEDU.\n";
} else {
    echo "View does NOT contain WAKANDA ASIEDU.\n";
}

if (str_contains($view, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU') || str_contains($view, 'LABORATORY EXAMINATION RESULT &mdash; WAKANDA ASIEDU')) {
    echo "FAIL: View contains LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU!\n";
} else {
    echo "PASS: View does NOT contain LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU.\n";
}
