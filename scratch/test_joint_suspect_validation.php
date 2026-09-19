<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;

// Let's create a temporary dummy joint suspects case to test validation
$testCase = ForensicCase::create([
    'case_number' => 'TEST-JOINT-' . rand(1000, 9999),
    'title' => 'Republic vs Primary Suspect A',
    'suspect_name' => 'Primary Suspect A',
    'has_seizures' => true,
    'seizure_name_drug' => 'Tramadol',
    'exhibit_category' => 'Drugs',
    'status' => 'Under Processing',
    'docket_data' => [
        'suspects' => [
            ['suspect_name' => 'Primary Suspect A', 'is_associate' => false],
            ['suspect_name' => 'Associate Suspect B', 'is_associate' => true],
        ],
        'seizures' => [
            [
                'exhibit_number' => 'EXH-01',
                'seizure_name_drug' => 'Tramadol',
                'associated_person' => 'Primary Suspect A',
                'is_associate' => false,
                'has_saved_lab' => false,
            ],
            [
                'exhibit_number' => 'EXH-02',
                'seizure_name_drug' => 'Cocaine',
                'associated_person' => 'Associate Suspect B',
                'is_associate' => true,
                'has_saved_lab' => false,
            ]
        ],
        'unit_progress' => [
            'Lab Analysis' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null]
        ]
    ]
]);

echo "Created test joint suspect case: {$testCase->case_number} (ID: {$testCase->id})\n\n";

$controller = new CaseController();

// 1. Try to save Lab Analysis as COMPLETED when NEITHER suspect has been examined yet
$req1 = Request::create("/cases/{$testCase->case_number}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Analyst Test',
    'notes' => 'Attempting full completion without examining any suspect',
], [], [], ['HTTP_ACCEPT' => 'application/json']);

$res1 = $controller->updateUnitProgress($testCase->case_number, $req1);
echo "Attempt 1 (No suspects examined, unit_status=COMPLETED):\n";
echo "Status Code: " . $res1->getStatusCode() . "\n";
echo "Response: " . $res1->getContent() . "\n\n";

// 2. Save lab examination for Primary Suspect A ONLY, sending unit_status = COMPLETED
$req2 = Request::create("/cases/{$testCase->case_number}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'exhibit_owner_name' => 'Primary Suspect A',
    'exhibit_is_associate' => false,
    'presumptive_result' => 'YES (Presence of: Tramadol)',
    'confirmatory_test_result' => 'YES',
    'officer' => 'Analyst Test',
    'notes' => 'Examined Primary Suspect A',
], [], [], ['HTTP_ACCEPT' => 'application/json']);

$res2 = $controller->updateUnitProgress($testCase->case_number, $req2);
echo "Attempt 2 (Examine Suspect A ONLY, unit_status=COMPLETED):\n";
echo "Status Code: " . $res2->getStatusCode() . "\n";
echo "Response: " . $res2->getContent() . "\n\n";

// 3. Save lab examination for Primary Suspect A sending unit_status = IN_PROGRESS
$req3 = Request::create("/cases/{$testCase->case_number}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'IN_PROGRESS',
    'exhibit_owner_name' => 'Primary Suspect A',
    'exhibit_is_associate' => false,
    'presumptive_result' => 'YES (Presence of: Tramadol)',
    'confirmatory_test_result' => 'YES',
    'officer' => 'Analyst Test',
    'notes' => 'Examined Primary Suspect A',
], [], [], ['HTTP_ACCEPT' => 'application/json']);

$res3 = $controller->updateUnitProgress($testCase->case_number, $req3);
echo "Attempt 3 (Examine Suspect A, unit_status=IN_PROGRESS):\n";
echo "Status Code: " . $res3->getStatusCode() . "\n";
echo "Response: " . $res3->getContent() . "\n\n";

// Clean up test case
$testCase->delete();
echo "Test case cleaned up.\n";
