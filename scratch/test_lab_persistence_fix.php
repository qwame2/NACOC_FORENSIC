<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

echo "=== TESTING DATA PERSISTENCE & CONFIRMATORY FIX ===\n\n";

// 1. Create a Case with Seizure & Initial Lab Data
$caseData = [
    'title' => 'Test Case Confirmatory Persistence',
    'unit_name' => 'Chemistry & Narcotics Unit',
    'commanding_officer' => 'Cmdr. Test Officer',
    'submitting_officer' => 'Officer Test',
    'suspect_name' => 'Kojo Mensah Test',
    'gender' => 'Male',
    'age' => '34',
    'exhibit_category' => 'Drugs',
    'seizure_name_drug' => 'Cocaine Blocks',
    'seizure_quantity' => '5',
    'seizure_weight' => '10',
    'seizure_unit' => 'Kilograms (kg)',
    'seizure_packaging' => 'Slabs wrapped in brown tape',
    'valuation' => 'USD 250,000 / GHS 3,750,000',
    'seizure_value' => 'USD 250,000 / GHS 3,750,000',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride (Pure Grade)',
    'confirmatory_test_methods' => 'GC-MS / FTIR Spectrometry (NIST 2026 standard)',
    'confirmatory_specifications' => 'Purity verified at 94.8% per Directorate Standards.',
    'sample_weight_description' => 'Cocaine Blocks',
    'sample_weight_gross' => '10 Kilograms (kg)',
    'sample_weight_taken' => '0.05 Kilograms (kg)',
    'sample_weight_reserved' => '9.95 Kilograms (kg)',
    'findings' => 'Forensic examination confirms high purity cocaine.',
];

$case = ForensicCase::create($caseData);
echo "1. Case Created Successfully: " . $case->case_number . " (ID: {$case->id})\n";

// Check DB values after creation
$createdCase = ForensicCase::find($case->id);
echo "   - Seizure Packaging: " . ($createdCase->seizure_packaging ?? 'NULL') . "\n";
echo "   - Valuation: " . ($createdCase->valuation ?? $createdCase->seizure_value ?? 'NULL') . "\n";
echo "   - Confirmatory Test Result: " . ($createdCase->confirmatory_test_result ?? 'NULL') . "\n";
echo "   - Substance ID: " . ($createdCase->confirmatory_substance_identification ?? 'NULL') . "\n";
echo "   - Test Methods: " . ($createdCase->confirmatory_test_methods ?? 'NULL') . "\n";
echo "   - Specifications: " . ($createdCase->confirmatory_specifications ?? 'NULL') . "\n";

// Check Exhibit table
$exh = \App\Models\Exhibit::where('multiple_suspect_case_id', $case->id)
    ->orWhere('single_suspect_case_id', $case->id)->first();
if ($exh) {
    echo "   - Exhibit Table Packaging: " . ($exh->seizure_packaging ?? 'NULL') . "\n";
    echo "   - Exhibit Table Value: " . ($exh->seizure_value ?? 'NULL') . "\n";
}

// Check Lab Results table
$labRes = \App\Models\LaboratoryExaminationResult::where('multiple_suspect_case_id', $case->id)
    ->orWhere('single_suspect_case_id', $case->id)->first();
if ($labRes) {
    echo "   - Lab Result Confirmatory: " . ($labRes->confirmatory_result ?? 'NULL') . "\n";
    echo "   - Lab Result Methods: " . ($labRes->test_methods ?? 'NULL') . "\n";
    echo "   - Lab Result Specs: " . ($labRes->specifications ?? 'NULL') . "\n";
}

// 2. Perform Update Unit Progress (as done by review form submit)
$controller = new \App\Http\Controllers\CaseController();
$updateReq = Request::create("/cases/{$case->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Senior Forensic Analyst',
    'notes' => 'Updated analytical findings confirming Cocaine HCL.',
    'exhibit_owner_name' => 'Kojo Mensah Test',
    'exhibit_is_associate' => false,
    'lab_exhibit_number' => 'EXH-01',
    'presumptive_result' => 'YES (Positive Cobalt Thiocyanate Reaction)',
    'confirmatory_test_result' => 'YES',
    'sample_weight_description' => 'Cocaine Blocks',
    'sample_weight_units' => '5 Slabs',
    'sample_weight_gross' => '10 Kilograms (kg)',
    'sample_weight_taken' => '0.05 Kilograms (kg)',
    'sample_weight_reserved' => '9.95 Kilograms (kg)',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride (Pure Grade)',
    'confirmatory_test_methods' => 'GC-MS / FTIR Spectrometry (NIST 2026 standard)',
    'confirmatory_specifications' => 'Purity verified at 94.8% per Directorate Standards.',
    'seizure_value' => 'USD 250,000 / GHS 3,750,000',
    'seizure_packaging' => 'Slabs wrapped in brown tape',
]);

$response = $controller->updateUnitProgress($case->id, $updateReq);
echo "\n2. Update Unit Progress Response: " . ($response->getData()->success ? 'SUCCESS' : 'FAILED') . "\n";

// Check updated DB values
$updatedCase = ForensicCase::find($case->id);
echo "   - Confirmatory Test Result After Save: " . ($updatedCase->confirmatory_test_result ?? 'NULL') . "\n";
echo "   - Substance ID After Save: " . ($updatedCase->confirmatory_substance_identification ?? 'NULL') . "\n";
echo "   - Test Methods After Save: " . ($updatedCase->confirmatory_test_methods ?? 'NULL') . "\n";
echo "   - Specifications After Save: " . ($updatedCase->confirmatory_specifications ?? 'NULL') . "\n";
echo "   - Seizure Packaging After Save: " . ($updatedCase->seizure_packaging ?? 'NULL') . "\n";
echo "   - Seizure Value After Save: " . ($updatedCase->seizure_value ?? 'NULL') . "\n";

// 3. Render Review View to verify NO "NO" overwrites
$viewReq = Request::create("/cases/{$case->case_number}/review", 'GET');
$viewResponse = $controller->showReview($case->case_number, $viewReq);
$html = $viewResponse->render();

if (str_contains($html, 'checked') && str_contains($html, 'confirmatoryTestYesReview')) {
    echo "\n3. Review Blade Check: confirmatoryTestYesReview IS CHECKED!\n";
} else {
    echo "\n3. Review Blade Check: ERROR - confirmatoryTestYesReview not checked!\n";
}

if (str_contains($html, 'Cocaine Hydrochloride (Pure Grade)')) {
    echo "   - Substance ID rendered in Review Blade: YES!\n";
} else {
    echo "   - Substance ID rendered in Review Blade: MISSING!\n";
}

if (str_contains($html, 'GC-MS / FTIR Spectrometry')) {
    echo "   - Test Methods rendered in Review Blade: YES!\n";
} else {
    echo "   - Test Methods rendered in Review Blade: MISSING!\n";
}

if (str_contains($html, 'Purity verified at 94.8%')) {
    echo "   - Specifications rendered in Review Blade: YES!\n";
} else {
    echo "   - Specifications rendered in Review Blade: MISSING!\n";
}

if (str_contains($html, 'USD 250,000 / GHS 3,750,000')) {
    echo "   - Estimated Valuation rendered in Review Blade: YES!\n";
} else {
    echo "   - Estimated Valuation rendered in Review Blade: MISSING!\n";
}

echo "\n=== ALL VERIFICATIONS COMPLETED ===\n";
