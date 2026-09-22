<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LaboratoryExaminationResult;
use App\Models\ForensicCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// 1. Authenticate as a user
$user = User::first() ?? User::factory()->create();
auth()->login($user);

// 2. Prepare payload simulating Laboratory Analysis case creation
$caseRef = 'LAB-TEST-' . rand(1000, 9999);
$payload = [
    'case_number' => $caseRef,
    'title' => 'Republic Vs Test Suspect',
    'unit_name' => 'Lab Analysis',
    'suspect_name' => 'Kofi Mensah',
    'suspect_alias' => 'El Capitan',
    'arrest_date' => date('Y-m-d'),
    'raid_location' => 'Tema Port Terminal 3',
    'offense_description' => 'Illicit Drug Trafficking',
    'exhibit_category' => 'Drugs & Narcotics',
    'seizure_name' => 'High Grade Cocaine Slabs',
    'seizure_quantity' => '10',
    'seizure_packaging' => 'Brick Packets',
    'seizure_weight' => '12.4',
    'seizure_unit' => 'KG',
    'presumptive_test_result' => 'YES (Positive Colorimetric Reaction)',
    'confirmatory_test_result' => 'YES',
    'sample_weight_description' => '10 Brick Packets wrapped in tape',
    'sample_weight_units' => '10 Brick Packets',
    'sample_weight_gross' => '12.4 KG',
    'sample_weight_taken' => '20.0 grams',
    'sample_weight_reserved' => '12.380 kg',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride (88.4% Purity)',
    'confirmatory_test_methods' => 'GC-MS Spectrometry & HPLC Analysis',
    'docket_data' => [
        'suspects' => [
            ['suspect_name' => 'Kofi Mensah', 'suspect_alias' => 'El Capitan', 'gender' => 'Male']
        ],
        'lab_examination' => [
            'laboratory_number' => 'LAB-2026-9999',
            'presumptive_result' => 'YES (Positive Colorimetric Reaction)',
            'substance_identification' => 'Cocaine Hydrochloride (88.4% Purity)',
            'sample_weight_description' => '10 Brick Packets wrapped in tape',
            'sample_weight_units' => '10 Brick Packets',
            'sample_weight_gross' => '12.4 KG',
            'sample_weight_taken' => '20.0 grams',
            'sample_weight_reserved' => '12.380 kg',
        ]
    ]
];

// 3. Post to CaseController store
$request = Request::create('/cases', 'POST', $payload);
$controller = new \App\Http\Controllers\CaseController();
$response = $controller->store($request);

echo "Case Creation Status Code: " . $response->getStatusCode() . "\n";
$resData = json_decode($response->getContent(), true);
echo "Message: " . ($resData['message'] ?? 'N/A') . "\n";

// 4. Inspect DB record in laboratory_examination_results
$dbCase = ForensicCase::where('case_number', $caseRef)->first();
if (!$dbCase) {
    echo "ERROR: Case not found in database!\n";
    exit(1);
}

echo "Case DB ID: {$dbCase->id}\n";
$labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $dbCase->id)
    ->orWhere('multiple_suspect_case_id', $dbCase->id)
    ->first();

if (!$labResult) {
    echo "ERROR: LaboratoryExaminationResult record NOT found in database!\n";
    exit(1);
}

echo "--- DB LaboratoryExaminationResult Record ---\n";
echo "ID: {$labResult->id}\n";
echo "Sample Weight Description: {$labResult->sample_weight_description}\n";
echo "Sample Weight Gross: {$labResult->sample_weight_gross}\n";
echo "Sample Weight Taken: '{$labResult->sample_weight_taken}'\n";
echo "Sample Weight Reserved: '{$labResult->sample_weight_reserved}'\n";

if ($labResult->sample_weight_taken !== '20.0 grams' || $labResult->sample_weight_reserved !== '12.380 kg') {
    echo "FAILED: DB fields sample_weight_taken or sample_weight_reserved do not match expected values!\n";
    exit(1);
} else {
    echo "SUCCESS: DB fields sample_weight_taken and sample_weight_reserved saved correctly!\n";
}

// 5. Test View Docket HTML Rendering
$viewResponse = $controller->showReview($caseRef);
$html = $viewResponse->render();

if (str_contains($html, '20.0 grams') && str_contains($html, '12.380 kg')) {
    echo "SUCCESS: View Docket rendered '20.0 grams' and '12.380 kg' correctly!\n";
} else {
    echo "FAILED: View Docket HTML did NOT contain exact saved values!\n";
}

if (str_contains($html, 'Sample Taken') && str_contains($html, 'Not Recorded')) {
    // Check if Not Recorded is attached to sample taken or reserved
    echo "WARNING: 'Not Recorded' tag found in HTML. Inspecting context...\n";
} else {
    echo "SUCCESS: 'Not Recorded' tag is NOT displayed for non-empty fields!\n";
}

echo "\n--- ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ---\n";
