<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\ForensicCase;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $caseRef = 'CSS-2026-9425';

    // Clean up any existing test records with this ref
    $multiIds = MultipleSuspectCase::where('case_number', $caseRef)->pluck('id');
    CaseSuspect::whereIn('multiple_suspect_case_id', $multiIds)->delete();
    SingleSuspectCase::where('case_number', $caseRef)->forceDelete();
    MultipleSuspectCase::where('case_number', $caseRef)->forceDelete();

    echo "1. Creating Single Case {$caseRef} with Suspect Ort Adom...\n";
    $singleCase = SingleSuspectCase::create([
        'case_number' => $caseRef,
        'laboratory_number' => 'LAB-2026-9425',
        'title' => 'Republic vs Ort Adom',
        'unit_name' => 'Crime Scene Support',
        'unit_id' => 1,
        'user_id' => 1,
        'suspect_name' => 'Ort Adom',
        'suspect_alias' => 'Ort',
        'gender' => 'MALE',
        'offense_description' => 'Test offense',
        'status' => 'Approved',
    ]);

    // Check count in allCases() before associate
    $casesBefore = ForensicCase::allCases()->filter(fn($c) => $c->case_number === $caseRef);
    echo "Cases count before adding associate: " . $casesBefore->count() . "\n";
    if ($casesBefore->count() !== 1) {
        throw new Exception("Expected 1 case before associate, got " . $casesBefore->count());
    }

    echo "\n2. Adding Associate Wakanda Asiedu via CaseController@associate...\n";
    $controller = new CaseController();
    $requestPayload = [
        'associated_persons' => [
            [
                'full_name' => 'Wakanda Asiedu',
                'alias' => 'Wakanda',
                'gender' => 'MALE',
                'phone' => '0240000000',
                'address' => 'Accra',
                'role' => 'Associate Suspect'
            ]
        ]
    ];
    $request = Request::create("/cases/{$caseRef}/associate", 'POST', $requestPayload);

    // Call associate
    $response = $controller->associate($caseRef, $request);

    // Inspect cases after adding first associate
    $casesAfter1 = ForensicCase::allCases()->filter(fn($c) => $c->case_number === $caseRef);
    echo "Cases count after 1st associate: " . $casesAfter1->count() . "\n";
    
    $case1 = ForensicCase::findByRef($caseRef);
    echo "Case Ref: #" . $case1->case_number . "\n";
    echo "Case Title: " . $case1->title . "\n";
    echo "Case Record Type: " . get_class($case1->underlyingRecord) . "\n";
    
    $suspects1 = CaseSuspect::where('multiple_suspect_case_id', $case1->underlyingRecord->id)->get();
    echo "Suspects in Case:\n";
    foreach ($suspects1 as $s) {
        echo "  - {$s->suspect_name} — {$s->role}\n";
    }

    if ($casesAfter1->count() !== 1) {
        throw new Exception("FAILED: Duplicate case records found! Count: " . $casesAfter1->count());
    }

    echo "\n3. Adding 2nd Associate John Doe...\n";
    $requestPayload2 = [
        'associated_persons' => [
            [
                'full_name' => 'John Doe',
                'alias' => 'JD',
                'gender' => 'MALE',
                'role' => 'Associate Suspect'
            ]
        ]
    ];
    $request2 = Request::create("/cases/{$caseRef}/associate", 'POST', $requestPayload2);
    $response2 = $controller->associate($caseRef, $request2);

    $casesAfter2 = ForensicCase::allCases()->filter(fn($c) => $c->case_number === $caseRef);
    echo "Cases count after 2nd associate: " . $casesAfter2->count() . "\n";
    
    $case2 = ForensicCase::findByRef($caseRef);
    echo "Case Title after 2nd associate: " . $case2->title . "\n";
    
    $suspects2 = CaseSuspect::where('multiple_suspect_case_id', $case2->underlyingRecord->id)->get();
    echo "Suspects in Case after 2nd associate:\n";
    foreach ($suspects2 as $s) {
        echo "  - {$s->suspect_name} — {$s->role}\n";
    }

    if ($casesAfter2->count() !== 1) {
        throw new Exception("FAILED: Duplicate case records found after 2nd associate! Count: " . $casesAfter2->count());
    }

    echo "\nSUCCESS! All associate addition requirements verified cleanly.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    DB::rollBack();
}
