<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\User;

$user = User::first();

$case = ForensicCase::create([
    'case_number' => 'CSS-2026-TEST-MULTI',
    'title' => 'Republic vs Princilla Addo & Atta Budo',
    'suspect_name' => 'Princilla Addo',
    'unit_name' => 'Crime Scene Support',
    'exhibit_category' => 'Drugs',
    'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)',
    'status' => 'Under Multi-Unit Processing',
    'docket_data' => [
        'suspects' => [
            ['suspect_name' => 'Princilla Addo', 'association_role' => 'Primary Suspect', 'is_associate' => false],
            ['suspect_name' => 'Atta Budo', 'association_role' => 'Mastermind', 'is_associate' => true, 'associate_id' => 'ASC-001'],
        ],
        'seizures' => [
            ['category' => 'Drugs', 'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)', 'is_associate' => false, 'exhibit_number' => 'EXH-01', 'has_saved_lab' => true, 'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)', 'confirmatory_result' => 'YES'],
            ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine (High-Purity Powder / Slabs)', 'associated_person' => 'Atta Budo', 'is_associate' => true, 'associate_id' => 'ASC-001', 'exhibit_number' => 'EXH-02'],
        ],
        'lab_examination' => [
            'exhibit_owner_name' => 'Princilla Addo',
            'exhibit_is_associate' => false,
            'confirmatory_result' => 'YES',
            'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)',
            'substance_identification' => 'Tramadol (High-Dose Tablets / Capsules)',
            'exhibit_number' => 'EXH-01',
            'has_saved' => true,
            'sample_weight' => [
                'description' => 'Tramadol (High-Dose Tablets / Capsules)',
                'gross_weight' => '30 Kilograms (kg)',
                'sample_taken' => '20',
                'reserved_samples' => '10 kg'
            ]
        ]
    ]
]);

// Simulate saving Atta Budo's lab exam via CaseController
$controller = app(\App\Http\Controllers\CaseController::class);
$req = \Illuminate\Http\Request::create('/cases/' . $case->id . '/update-unit-progress', 'POST', [
    'unit_name' => 'Lab Analysis',
    'status' => 'COMPLETED',
    'exhibit_owner_name' => 'Atta Budo',
    'exhibit_is_associate' => 1,
    'lab_exhibit_number' => 'EXH-02',
    'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 85%)',
    'confirmatory_test_result' => 'YES',
    'confirmatory_substance_identification' => 'Cocaine (High-Purity Powder / Slabs)',
    'sample_weight_description' => 'Cocaine (High-Purity Powder / Slabs)',
    'sample_weight_gross' => '15 Kilograms (kg)',
    'sample_weight_taken' => '50',
    'sample_weight_reserved' => '500 Grams',
    'confirmatory_test_methods' => 'GC-MS Spectrometry',
    'confirmatory_specifications' => 'High Purity Spec',
    'notes' => 'Cocaine lab analysis completed for Atta Budo.'
]);

Auth::login($user);
$res = $controller->updateUnitProgress($case->id, $req);
$freshCase = ForensicCase::find($case->id);

$viewReview = view('Forensic_Head.show_review', ['case' => $freshCase, 'from' => 'Lab Analysis'])->render();
$viewPdf = view('Forensic_Head.case_pdf', ['case' => $freshCase])->render();

echo "=== REVIEW SCREEN ASSERTIONS ===\n";
echo "Has Princilla Addo Header: " . (str_contains($viewReview, 'LABORATORY EXAMINATION RESULT — PRINCILLA ADDO') ? 'YES' : 'NO') . "\n";
echo "Has Atta Budo Header: " . (str_contains($viewReview, 'LABORATORY EXAMINATION RESULT — ATTA BUDO') ? 'YES' : 'NO') . "\n";
echo "Has Tramadol: " . (str_contains($viewReview, 'Tramadol (High-Dose Tablets / Capsules)') ? 'YES' : 'NO') . "\n";
echo "Has Cocaine: " . (str_contains($viewReview, 'Cocaine (High-Purity Powder / Slabs)') ? 'YES' : 'NO') . "\n";

echo "\n=== PDF EXPORT ASSERTIONS ===\n";
echo "Has Princilla Addo Header (PDF): " . (str_contains($viewPdf, 'PRINCILLA ADDO') ? 'YES' : 'NO') . "\n";
echo "Has Atta Budo Header (PDF): " . (str_contains($viewPdf, 'LABORATORY EXAMINATION RESULT — ASSOCIATE DOSSIER (SUSPECT #2: ATTA BUDO)') ? 'YES' : 'NO') . "\n";
echo "Has Tramadol (PDF): " . (str_contains($viewPdf, 'Tramadol (High-Dose Tablets / Capsules)') ? 'YES' : 'NO') . "\n";
echo "Has Cocaine (PDF): " . (str_contains($viewPdf, 'Cocaine (High-Purity Powder / Slabs)') ? 'YES' : 'NO') . "\n";

$case->delete();
