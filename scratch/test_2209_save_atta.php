<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\User;

$user = User::first();
Auth::login($user);

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$controller = app(\App\Http\Controllers\CaseController::class);

$req = \Illuminate\Http\Request::create('/cases/' . $case->id . '/update-unit-progress', 'POST', [
    'unit_name' => 'Lab Analysis',
    'status' => 'COMPLETED',
    'exhibit_owner_name' => 'Atta Budo',
    'exhibit_is_associate' => 1,
    'lab_exhibit_number' => 'EXH-02',
    'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 90%)',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine (High-Purity Powder / Slabs)',
    'sample_weight_description' => 'Cocaine (High-Purity Powder / Slabs)',
    'sample_weight_units' => '89 Wraps / Pellets',
    'sample_weight_gross' => '79 Kilograms (kg)',
    'sample_weight_taken' => '20',
    'sample_weight_reserved' => '59 kg',
    'confirmatory_test_methods' => 'GC-MS Spectrometry',
    'confirmatory_specifications' => 'High Purity Spec',
    'notes' => 'Cocaine confirmed for Atta Budo.'
]);

$res = $controller->updateUnitProgress($case->id, $req);
$freshCase = ForensicCase::find($case->id);

$viewReview = view('Forensic_Head.show_review', ['case' => $freshCase, 'from' => 'Lab Analysis'])->render();
$viewPdf = view('Forensic_Head.case_pdf', ['case' => $freshCase])->render();

echo "=== AFTER SAVING ATTA BUDO LAB EXAM ===\n";
echo "Has Princilla Addo Header (Review): " . (str_contains($viewReview, 'LABORATORY EXAMINATION RESULT — PRINCILLA ADDO') ? 'YES' : 'NO') . "\n";
echo "Has Atta Budo Header (Review): " . (str_contains($viewReview, 'LABORATORY EXAMINATION RESULT — ATTA BUDO') ? 'YES' : 'NO') . "\n";
echo "Has Tramadol (Review): " . (str_contains($viewReview, 'Tramadol (High-Dose Tablets / Capsules)') ? 'YES' : 'NO') . "\n";
echo "Has Cocaine (Review): " . (str_contains($viewReview, 'Cocaine (High-Purity Powder / Slabs)') ? 'YES' : 'NO') . "\n";

echo "\n=== PDF EXPORT ASSERTIONS ===\n";
echo "Has Princilla Addo Header (PDF): " . (str_contains($viewPdf, 'PRINCILLA ADDO') ? 'YES' : 'NO') . "\n";
echo "Has Atta Budo Header (PDF): " . (str_contains($viewPdf, 'LABORATORY EXAMINATION RESULT — ASSOCIATE DOSSIER (SUSPECT #2: ATTA BUDO)') ? 'YES' : 'NO') . "\n";
echo "Has Tramadol (PDF): " . (str_contains($viewPdf, 'Tramadol (High-Dose Tablets / Capsules)') ? 'YES' : 'NO') . "\n";
echo "Has Cocaine (PDF): " . (str_contains($viewPdf, 'Cocaine (High-Purity Powder / Slabs)') ? 'YES' : 'NO') . "\n";
