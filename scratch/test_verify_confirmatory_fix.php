<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST CONFIRMATORY FIX VERIFICATION ===\n\n";

$user = User::first();
if ($user) Auth::login($user);

// 1. Create case with confirmatory_test_result = YES
$testNum = 'FSD-CONF-TEST-' . time();
$req = Request::create('/cases', 'POST', [
    'case_number' => $testNum,
    'title' => 'Test Confirmatory Flow',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Kwame Nkrumah',
    'seizure_name_drug' => 'Cocaine Powder',
    'seizure_quantity' => '10',
    'seizure_weight' => '15',
    'seizure_unit' => 'Kilograms (kg)',
    'presumptive_test_result' => 'YES',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
    'confirmatory_test_methods' => 'GC-MS Spectrometry',
]);

$controller = new CaseController();
$controller->store($req);

$case = ForensicCase::findByRef($testNum);
echo "Created case #{$case->case_number}:\n";
echo "  - case->confirmatory_test_result: " . var_export($case->confirmatory_test_result, true) . "\n";

$docket = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true);
echo "  - docket['lab_examination']['confirmatory_result']: " . var_export($docket['lab_examination']['confirmatory_result'] ?? null, true) . "\n";

// 2. Update unit progress with YES
$upReq = Request::create("/cases/{$case->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Test Analyst',
    'notes' => 'Lab analysis completed.',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
    'sample_weight_units' => 'Kilograms (kg)',
    'sample_weight_gross' => '15 kg',
    'sample_weight_taken' => '0.01 kg',
    'sample_weight_reserved' => '14.99 kg',
]);

$controller->updateUnitProgress($case->id, $upReq);

$fresh = ForensicCase::findByRef($testNum);
$freshDocket = is_array($fresh->docket_data) ? $fresh->docket_data : json_decode($fresh->docket_data ?? '[]', true);

echo "\nAfter updateUnitProgress:\n";
echo "  - fresh->confirmatory_test_result: " . var_export($fresh->confirmatory_test_result, true) . "\n";
echo "  - freshDocket['lab_examination']['confirmatory_result']: " . var_export($freshDocket['lab_examination']['confirmatory_result'] ?? null, true) . "\n";

$html = view('Forensic_Head.show_review', ['case' => $fresh, 'from' => 'Lab Analysis'])->render();

if (str_contains($html, 'YES</span>') || str_contains($html, 'Confirmatory Test:</td>') && str_contains($html, 'YES')) {
    echo "\n✓ SUCCESS: Review page displays Confirmatory Test as YES!\n";
} else {
    echo "\n❌ FAILED: Review page still says NO or missing YES!\n";
}
