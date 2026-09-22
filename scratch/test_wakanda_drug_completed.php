<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::create([
    'case_number' => 'CSS-2026-TESTWAK3',
    'title' => 'Republic vs Primary Suspect & Wakanda Asiedu',
    'suspect_name' => 'Primary Suspect',
    'unit_name' => 'Crime Scene Support',
    'status' => 'Draft',
    'docket_data' => json_encode([
        'suspects' => [
            ['suspect_name' => 'Primary Suspect', 'association_role' => 'Primary Suspect', 'is_associate' => false],
            ['suspect_name' => 'Wakanda Asiedu', 'association_role' => 'Accomplice', 'is_associate' => true, 'associate_id' => 'ASC-004'],
        ],
        'seizures' => [
            ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine Slabs', 'associated_person' => 'Primary Suspect', 'is_associate' => false, 'has_saved_lab' => true],
            ['category' => 'Drugs', 'seizure_name_drug' => 'Heroin Powder', 'associated_person' => 'Wakanda Asiedu', 'is_associate' => true, 'associate_id' => 'ASC-004', 'has_saved_lab' => true],
        ],
        'lab_examination' => [
            'has_saved' => true,
            'exhibit_owner_name' => 'Primary Suspect',
            'substance_identification' => 'Cocaine Hydrochloride',
            'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 85%)',
            'confirmatory_result' => 'YES',
        ],
        'associate_lab_examinations' => [
            'Wakanda Asiedu' => [
                'has_saved' => true,
                'exhibit_number' => 'EXH-02',
                'substance_identification' => 'Heroin Diacetylmorphine',
                'presumptive_result' => 'YES (Presence of: Heroin - Purity/Percentage: 70%)',
                'confirmatory_result' => 'YES',
                'analyst' => 'Insp. Lab Examiner',
            ]
        ]
    ])
]);

$view = view('Forensic_Head.show_review', ['case' => $case])->render();
$viewPdf = view('Forensic_Head.case_pdf', ['case' => $case])->render();

echo "=== REVIEW PAGE TEST (COMPLETED DRUG LAB) ===\n";
if (str_contains($view, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU')) {
    echo "PASS: Review page correctly rendered LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU when lab exam is saved!\n";
} else {
    echo "FAIL: Review page failed to render LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU when lab exam is saved!\n";
}

echo "=== PDF TEST (COMPLETED DRUG LAB) ===\n";
if (str_contains($viewPdf, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU')) {
    echo "PASS: PDF correctly rendered LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU when lab exam is saved!\n";
} else {
    echo "FAIL: PDF failed to render LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU when lab exam is saved!\n";
}

// Clean up
$case->delete();
