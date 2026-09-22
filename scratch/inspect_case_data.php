<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
if (!$case) {
    $case = App\Models\SingleSuspectCase::where('case_number', 'CSS-2026-5584')->first();
}
if (!$case) {
    $case = App\Models\MultipleSuspectCase::where('case_number', 'CSS-2026-5584')->first();
}

if (!$case) {
    echo "CASE NOT FOUND\n";
    exit;
}

echo "=== CASE BASIC INFO ===\n";
echo "ID: " . $case->id . "\n";
echo "Case Number: " . $case->case_number . "\n";
echo "Suspect Name: " . $case->suspect_name . "\n";
echo "Is Drug Case: " . (!empty($case->is_drug_case) ? 'YES' : 'NO') . "\n";

echo "\n=== ASSOCIATE LAB EXAMINATIONS DB RECS ===\n";
if ($case->associateLabExaminations) {
    foreach ($case->associateLabExaminations as $ale) {
        echo "ID: {$ale->id} | Suspect Name: {$ale->suspect_name} | Has Saved: {$ale->has_saved} | Exam Ref: {$ale->exam_reference} | Lab No: {$ale->laboratory_number}\n";
    }
} else {
    echo "None\n";
}

$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

echo "\n=== DOCKET SUSPECTS ===\n";
if (!empty($docketData['suspects'])) {
    foreach ($docketData['suspects'] as $i => $s) {
        echo "Suspect #".($i+1).": " . ($s['suspect_name'] ?? 'N/A') . " | Role: " . ($s['association_role'] ?? ($s['role'] ?? 'N/A')) . " | ID: " . ($s['associate_id'] ?? ($s['suspect_id'] ?? 'N/A')) . "\n";
    }
}

echo "\n=== DOCKET ASSOCIATED SUSPECTS ===\n";
if (!empty($docketData['associated_suspects'])) {
    foreach ($docketData['associated_suspects'] as $i => $s) {
        echo "Assoc Suspect #".($i+1).": " . ($s['suspect_name'] ?? ($s['name'] ?? 'N/A')) . "\n";
    }
}

echo "\n=== DOCKET SEIZURES / EXHIBITS ===\n";
if (!empty($docketData['seizures'])) {
    foreach ($docketData['seizures'] as $i => $sz) {
        echo "Exhibit #".($i+1).": " . ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? 'N/A'))) . " | Cat: " . ($sz['exhibit_category'] ?? ($sz['category'] ?? 'N/A')) . " | Owner: " . ($sz['associated_person'] ?? 'N/A') . " | Has Saved Lab: " . (!empty($sz['has_saved_lab']) ? 'YES' : 'NO') . "\n";
    }
}

echo "\n=== DOCKET ASSOCIATE LAB EXAMINATIONS ===\n";
if (!empty($docketData['associate_lab_examinations'])) {
    print_r($docketData['associate_lab_examinations']);
} else {
    echo "None\n";
}

echo "\n=== DOCKET LAB EXAMINATIONS LIST ===\n";
if (!empty($docketData['lab_examinations_list'])) {
    print_r($docketData['lab_examinations_list']);
} else {
    echo "None\n";
}
