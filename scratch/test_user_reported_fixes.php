<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== USER REPORTED FIXES REGRESSION TEST ===\n\n";

// Login Head User
$user = User::where('service_id', 'HOF-HEAD-001')->first() 
    ?? User::where('email', 'head@nacoc.gov.gh')->first() 
    ?? User::first();
if ($user) {
    Auth::login($user);
}

// 1. Create a Test Case with Kilograms (kg)
$testCaseNumber = 'FSD-REG-TEST-' . time();
$createPayload = [
    'case_number' => $testCaseNumber,
    'title' => 'State vs Test Subject',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Kofi Annan',
    'seizure_name_drug' => 'High Purity Heroin',
    'seizure_quantity' => '50',
    'seizure_weight' => '100',
    'seizure_unit' => 'Kilograms (kg)',
    'seizure_packaging' => '50 Sealed Bricks',
    'presumptive_test_result' => 'YES (Positive Colorimetric Reaction)',
    'confirmatory_test_result' => 'YES',
    'sample_weight_description' => '50 Sealed Bricks',
    'sample_weight_gross' => '100 Kilograms (kg)',
    'sample_weight_taken' => '0.05 Kilograms (kg)',
    'sample_weight_reserved' => '99.95 Kilograms (kg)',
    'confirmatory_substance_identification' => 'Diacetylmorphine (Heroin)',
    'confirmatory_test_methods' => 'GC-MS Spectrometry',
    'confirmatory_specifications' => 'Purity 94.2%',
];

$req = Request::create('/cases', 'POST', $createPayload);
$controller = new CaseController();
$res = $controller->store($req);

$case = ForensicCase::where('case_number', $testCaseNumber)->first();
if (!$case) {
    echo "❌ Failed to create test case!\n";
    exit(1);
}
echo "✓ Test case created: {$case->case_number}\n";

// Test 1: Unit matching verification
echo "\n--- TEST 1: WEIGHT UNIT MATCHING ---\n";
echo "DB seizure_unit: " . ($case->seizure_unit ?? 'N/A') . "\n";
if ($case->seizure_unit === 'Kilograms (kg)') {
    echo "PASSED: Seizure unit stored cleanly as 'Kilograms (kg)'.\n";
} else {
    echo "FAILED: Seizure unit is '{$case->seizure_unit}'. Expected 'Kilograms (kg)'.\n";
}

// Test 2: Directorate Progress & Lab Analysis Cardview Status
echo "\n--- TEST 2: 4-UNIT DIRECTORATE PROGRESS CARDVIEW STATUS ---\n";
$updateProgressReq = Request::create("/cases/{$case->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Samuel Atta',
    'notes' => 'GC-MS Analysis completed successfully.',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Diacetylmorphine (Heroin)',
    'sample_weight_units' => 'Kilograms (kg)',
    'sample_weight_gross' => '100 Kilograms (kg)',
    'sample_weight_taken' => '0.05 Kilograms (kg)',
    'sample_weight_reserved' => '99.95 Kilograms (kg)',
]);
$progressRes = $controller->updateUnitProgress($case->id, $updateProgressReq);
$freshCase = ForensicCase::where('case_number', $testCaseNumber)->first();

$renderedReview = view('Forensic_Head.show_review', ['case' => $freshCase, 'from' => 'Lab Analysis'])->render();

if (str_contains($renderedReview, '✓ COMPLETED') && !str_contains($renderedReview, 'id="unitBadge-labanalysis">⭕ PENDING')) {
    echo "PASSED: Lab Analysis Cardview displays '✓ COMPLETED' in 4-Unit Directorate Progress section!\n";
} else {
    echo "FAILED: Lab Analysis Cardview still displays PENDING or failed to render COMPLETED!\n";
}

// Test 3: Exhibit Photo Upload on Edit Page
echo "\n--- TEST 3: SEIZED EXHIBIT PHOTO UPLOADING ---\n";
$dummyBase64Photo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
$updateCaseReq = Request::create("/cases/{$case->id}/update", 'POST', [
    'title' => 'State vs Kofi Annan',
    'suspect_name' => 'Kofi Annan',
    'seizure_name_drug' => 'High Purity Heroin',
    'seizure_quantity' => '50',
    'seizure_weight' => '100',
    'seizure_unit' => 'Kilograms (kg)',
    'seizure_packaging' => '50 Sealed Bricks',
    'evidence_photos' => json_encode([$dummyBase64Photo]),
    'suspects_data_json' => json_stringify([
        'suspects' => [
            [
                'suspect_name' => 'Kofi Annan',
                'exhibit_index' => 0,
                'lab_index' => 0,
            ]
        ],
        'exhibits' => [
            [
                'seizure_name_drug' => 'High Purity Heroin',
                'seizure_quantity' => '50',
                'seizure_weight' => '100',
                'seizure_unit' => 'Kilograms (kg)',
                'seizure_packaging' => '50 Sealed Bricks',
                'evidence_photos' => [$dummyBase64Photo],
            ]
        ],
        'labs' => []
    ])
]);

function json_stringify($val) { return json_encode($val); }

$updateRes = $controller->updateCase($case->id, $updateCaseReq);
$updatedCase = ForensicCase::where('case_number', $testCaseNumber)->first();

$photosArr = is_array($updatedCase->evidence_photos) ? $updatedCase->evidence_photos : (json_decode($updatedCase->evidence_photos ?? '[]', true) ?: []);
$exModel = Exhibit::where('single_suspect_case_id', $updatedCase->id)->orWhere('multiple_suspect_case_id', $updatedCase->id)->first();

if (!empty($photosArr) && count($photosArr) > 0 && (!empty($exModel->evidence_photos) || !empty($photosArr[0]))) {
    echo "PASSED: Seized Exhibit visual evidence photos uploaded and persisted to DB successfully!\n";
} else {
    echo "FAILED: Evidence photos array is empty or did not persist!\n";
}

echo "\n=== ALL REGRESSION TESTS EXECUTED ===\n";
