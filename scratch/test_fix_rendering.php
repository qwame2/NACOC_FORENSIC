<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

// Make sure Atta Budo associate lab exam exists in DB
\App\Models\AssociateLabExamination::updateOrCreate(
    [
        'case_id' => $case->id,
        'suspect_name' => 'Atta Budo',
    ],
    [
        'exhibit_number' => 'EXH-02',
        'laboratory_number' => 'Lab No.31/FS09/2026',
        'exam_reference' => 'NACOC/LAB/2026/09/0002',
        'lab_unit' => 'Chemistry & Narcotics Unit',
        'analyst' => 'John Examiner',
        'date_received' => '2026-09-18',
        'date_started' => '2026-09-18',
        'date_completed' => '2026-09-18',
        'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 90%)',
        'confirmatory_result' => 'YES',
        'sample_weight_description' => 'Cocaine (High-Purity Powder / Slabs)',
        'sample_weight_units' => '89 Wraps / Pellets',
        'sample_weight_gross' => '79 Kilograms (kg)',
        'sample_weight_taken' => '20',
        'sample_weight_reserved' => '59 kg',
        'substance_identification' => 'Cocaine (High-Purity Powder / Slabs)',
        'detected_substances' => [['substance' => 'Cocaine HCI', 'percentage' => '90%']],
        'test_methods' => 'GC-MS Spectrometry',
        'specifications' => 'High Purity Spec',
        'findings' => 'Cocaine verified 90% purity for Atta Budo.',
        'has_saved' => true,
    ]
);

$case->refresh();

$viewHtml = view('Forensic_Head.show_review', ['case' => $case, 'case_number' => $case->case_number])->render();

// Check if Atta Budo's table is present in HTML
$hasPrincilla = str_contains($viewHtml, 'LABORATORY EXAMINATION RESULT — PRINCILLA ADDO');
$hasAtta = str_contains($viewHtml, 'LABORATORY EXAMINATION RESULT — ATTA BUDO');
$hasCocaine = str_contains($viewHtml, 'Cocaine (High-Purity Powder / Slabs)');
$hasTramadol = str_contains($viewHtml, 'Tramadol (High-Dose Tablets / Capsules)');

echo "Has Princilla Header: " . ($hasPrincilla ? 'YES' : 'NO') . "\n";
echo "Has Atta Header: " . ($hasAtta ? 'YES' : 'NO') . "\n";
echo "Has Tramadol (Princilla actual info): " . ($hasTramadol ? 'YES' : 'NO') . "\n";
echo "Has Cocaine (Atta actual info): " . ($hasCocaine ? 'YES' : 'NO') . "\n";
