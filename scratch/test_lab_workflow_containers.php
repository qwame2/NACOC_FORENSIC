<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST LAB ANALYSIS WORKFLOW CONTAINER VISIBILITY ===\n\n";

$user = User::first();
if ($user) Auth::login($user);

$controller = new CaseController();

// 1. Lab Analysis Created Case with Drug Exhibit
$drugRef = 'LAB-DRUG-' . time();
$req1 = Request::create('/cases', 'POST', [
    'case_number' => $drugRef,
    'title' => 'Lab Narcotics Seizure',
    'unit_name' => 'Lab Analysis',
    'suspect_name' => 'Kofi Drug',
    'exhibit_category' => 'DRUGS',
    'seizure_name_drug' => 'Cocaine',
    'seizure_quantity' => '10',
    'seizure_unit' => 'Kilograms (kg)',
]);
$controller->store($req1);
$case1 = ForensicCase::findByRef($drugRef);

$view1 = view('Forensic_Head.show_review', [
    'case' => $case1,
    'status' => $case1->status ?? 'Under Multi-Unit Processing',
    'priority' => $case1->priority ?? 'NORMAL',
    'docketData' => is_array($case1->docket_data) ? $case1->docket_data : json_decode($case1->docket_data ?? '[]', true),
])->render();

echo "Case 1: Lab Analysis created case + Drug Exhibit (#{$drugRef})\n";
if (str_contains($view1, '2-Unit Directorate Progress') && str_contains($view1, 'Evidence Management') && str_contains($view1, 'Lab Analysis') && !str_contains($view1, 'unitCard-crimescenesupport')) {
    echo "  ✓ PASSED: Shows only Evidence Management & Lab Analysis (2-Unit Directorate Progress)!\n";
} else {
    echo "  ❌ FAILED: Workflow view output incorrect!\n";
}

// 2. Lab Analysis Created Case with Non-Drug Exhibit
$nonDrugRef = 'LAB-NONDRUG-' . time();
$req2 = Request::create('/cases', 'POST', [
    'case_number' => $nonDrugRef,
    'title' => 'Lab Counterfeit Currency Seizure',
    'unit_name' => 'Lab Analysis',
    'suspect_name' => 'Kwame Money',
    'exhibit_category' => 'CURRENCY / CASH',
    'seizure_name' => 'Counterfeit $100 Bills',
    'seizure_quantity' => '50000',
    'seizure_unit' => 'USD',
]);
$controller->store($req2);
$case2 = ForensicCase::findByRef($nonDrugRef);

$view2 = view('Forensic_Head.show_review', [
    'case' => $case2,
    'status' => $case2->status ?? 'Under Multi-Unit Processing',
    'priority' => $case2->priority ?? 'NORMAL',
    'docketData' => is_array($case2->docket_data) ? $case2->docket_data : json_decode($case2->docket_data ?? '[]', true),
])->render();

echo "\nCase 2: Lab Analysis created case + Non-Drug Exhibit (#{$nonDrugRef})\n";
if (str_contains($view2, '1-Unit Directorate Progress') && str_contains($view2, 'Evidence Management') && !str_contains($view2, 'unitCard-labanalysis')) {
    echo "  ✓ PASSED: Hides Lab Analysis container for non-drug exhibit (1-Unit Directorate Progress)!\n";
} else {
    echo "  ❌ FAILED: Lab Analysis container was not hidden for non-drug exhibit!\n";
}

// 3. Crime Scene Support Created Case with Non-Drug Exhibit
$csNonDrugRef = 'CSS-NONDRUG-' . time();
$req3 = Request::create('/cases', 'POST', [
    'case_number' => $csNonDrugRef,
    'title' => 'Field Weapon Seizure',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Yaw Gun',
    'exhibit_category' => 'FIREARMS & AMMUNITION',
    'seizure_name' => 'AK-47 Rifle',
    'seizure_quantity' => '1',
    'seizure_unit' => 'Units',
]);
$controller->store($req3);
$case3 = ForensicCase::findByRef($csNonDrugRef);

$view3 = view('Forensic_Head.show_review', [
    'case' => $case3,
    'status' => $case3->status ?? 'Under Multi-Unit Processing',
    'priority' => $case3->priority ?? 'NORMAL',
    'docketData' => is_array($case3->docket_data) ? $case3->docket_data : json_decode($case3->docket_data ?? '[]', true),
])->render();

echo "\nCase 3: Crime Scene Support created case + Non-Drug Exhibit (#{$csNonDrugRef})\n";
if (str_contains($view3, '3-Unit Directorate Progress') && str_contains($view3, 'Crime Scene Support') && str_contains($view3, 'Evidence Management') && str_contains($view3, 'Digital &amp; Physical Forensics') && !str_contains($view3, 'unitCard-labanalysis')) {
    echo "  ✓ PASSED: Shows Crime Scene, Evidence Management, Digital & Physical (3-Unit Directorate Progress), Lab Analysis hidden!\n";
} else {
    echo "  ❌ FAILED: Workflow view output incorrect for Crime Scene Support non-drug case!\n";
}
