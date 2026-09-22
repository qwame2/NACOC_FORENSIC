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

echo "=== TEST BOTH YES AND NO CONFIRMATORY SELECTIONS ===\n\n";

$user = User::first();
if ($user) Auth::login($user);

// 1. Create case with YES
$case1Num = 'FSD-CONF-YES-' . time();
$req1 = Request::create('/cases', 'POST', [
    'case_number' => $case1Num,
    'title' => 'Test Confirmatory YES',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Akwasi Mensah',
    'seizure_name_drug' => 'Heroin Bricks',
    'confirmatory_test_result' => 'YES',
]);
$controller = new CaseController();
$controller->store($req1);
$case1 = ForensicCase::findByRef($case1Num);
echo "Case 1 (YES) -> confirmatory_test_result: " . var_export($case1->confirmatory_test_result, true) . "\n";
$html1 = view('Forensic_Head.show_review', ['case' => $case1, 'from' => 'Lab Analysis'])->render();
if (str_contains($html1, 'YES</span>') || str_contains($html1, 'Confirmatory Test:</td>') && str_contains($html1, 'YES')) {
    echo "✓ Case 1 correctly rendered YES on review page.\n";
} else {
    echo "❌ Case 1 failed to render YES!\n";
}

// 2. Update Case 1 to NO
$upReq1 = Request::create("/cases/{$case1->id}/update-unit-progress", 'POST', [
    'unit_name' => 'Lab Analysis',
    'unit_status' => 'COMPLETED',
    'officer' => 'Dr. Test Analyst',
    'notes' => 'Lab analysis completed with negative confirmatory test.',
    'confirmatory_test_result' => 'NO',
]);
$controller->updateUnitProgress($case1->id, $upReq1);
$case1Fresh = ForensicCase::findByRef($case1Num);
echo "\nCase 1 Updated to (NO) -> confirmatory_test_result: " . var_export($case1Fresh->confirmatory_test_result, true) . "\n";
$html1No = view('Forensic_Head.show_review', ['case' => $case1Fresh, 'from' => 'Lab Analysis'])->render();
if (str_contains($html1No, 'NO</span>') || str_contains($html1No, 'Confirmatory Test:</td>') && str_contains($html1No, 'NO')) {
    echo "✓ Case 1 correctly updated and rendered NO on review page.\n";
} else {
    echo "❌ Case 1 failed to render NO after update!\n";
}
