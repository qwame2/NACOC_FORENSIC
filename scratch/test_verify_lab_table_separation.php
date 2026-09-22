<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST PRIMARY VS ASSOCIATE LAB EXAMINATION TABLE SEPARATION ===\n\n";

$user = User::first();
if ($user) Auth::login($user);

// 1. Create case with primary suspect "Kofi Poku" and associate "Atta Budo"
$testNum = 'FSD-SEP-TEST-' . time();
$req = Request::create('/cases', 'POST', [
    'case_number' => $testNum,
    'title' => 'Test Separation Case',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Kofi Poku',
    'seizure_name_drug' => 'Cocaine Brick',
    'seizure_quantity' => '5',
    'seizure_weight' => '6',
    'seizure_unit' => 'Kilograms (kg)',
    'presumptive_test_result' => 'YES',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
    'confirmatory_test_methods' => 'GC-MS',
]);

$controller = new CaseController();
$controller->store($req);

$case = ForensicCase::findByRef($testNum);
echo "Created Case #{$case->case_number} (ID: {$case->id})\n";

// Check DB tables after store
$primaryLabDb = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
    ->orWhere('multiple_suspect_case_id', $case->id)
    ->get();

$assocLabDb = AssociateLabExamination::where('case_id', $case->id)->get();

echo "After Case Creation:\n";
echo "  - laboratory_examination_results count: " . $primaryLabDb->count() . "\n";
echo "  - associate_lab_examinations count: " . $assocLabDb->count() . "\n";

if ($primaryLabDb->count() >= 1 && $assocLabDb->count() === 0) {
    echo "✓ Store check passed: Primary lab exam saved ONLY in laboratory_examination_results!\n";
} else {
    echo "❌ Store check failed! assocLabDb count is " . $assocLabDb->count() . "\n";
}

// 2. Update Primary Suspect Lab Exam via updateUnitProgress
$upPrimaryReq = Request::create("/cases/{$case->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Chief Chemist',
    'notes' => 'Primary suspect exhibit tested positive.',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Pure Cocaine Hydrochloride',
    'sample_weight_gross' => '6 kg',
    'sample_weight_taken' => '0.01 kg',
    'sample_weight_reserved' => '5.99 kg',
]);

$controller->updateUnitProgress($case->id, $upPrimaryReq);

$primaryLabDbAfter = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
    ->orWhere('multiple_suspect_case_id', $case->id)
    ->get();
$assocLabDbAfter = AssociateLabExamination::where('case_id', $case->id)->get();

echo "\nAfter Primary Suspect updateUnitProgress:\n";
echo "  - laboratory_examination_results count: " . $primaryLabDbAfter->count() . "\n";
echo "  - associate_lab_examinations count: " . $assocLabDbAfter->count() . "\n";

if ($primaryLabDbAfter->count() >= 1 && $assocLabDbAfter->count() === 0) {
    echo "✓ Primary progress check passed: Primary lab exam is strictly in laboratory_examination_results!\n";
} else {
    echo "❌ Primary progress check failed! associate_lab_examinations has records!\n";
}

// 3. Update Associate Suspect Lab Exam via updateUnitProgress
$upAssocReq = Request::create("/cases/{$case->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Chief Chemist',
    'exhibit_owner_name' => 'Atta Budo',
    'notes' => 'Associate suspect exhibit tested positive.',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cannabis Sativa',
    'sample_weight_gross' => '2 kg',
    'sample_weight_taken' => '0.01 kg',
    'sample_weight_reserved' => '1.99 kg',
]);

$controller->updateUnitProgress($case->id, $upAssocReq);

$primaryLabDbFinal = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
    ->orWhere('multiple_suspect_case_id', $case->id)
    ->get();
$assocLabDbFinal = AssociateLabExamination::where('case_id', $case->id)->get();

echo "\nAfter Associate Suspect (Atta Budo) updateUnitProgress:\n";
echo "  - laboratory_examination_results count: " . $primaryLabDbFinal->count() . "\n";
echo "  - associate_lab_examinations count: " . $assocLabDbFinal->count() . "\n";
if ($assocLabDbFinal->count() >= 1) {
    echo "  - Associate suspect name in associate_lab_examinations: " . $assocLabDbFinal->first()->suspect_name . "\n";
}

if ($assocLabDbFinal->count() === 1 && $assocLabDbFinal->first()->suspect_name === 'Atta Budo') {
    echo "✓ Associate progress check passed: Associate lab exam saved in associate_lab_examinations table!\n";
} else {
    echo "❌ Associate progress check failed!\n";
}
