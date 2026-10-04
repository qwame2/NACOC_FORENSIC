<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\ForensicCase;
use App\Models\User;

$single = SingleSuspectCase::first();
if (!$single) {
    echo "No case found.\n";
    exit(0);
}

$case = ForensicCase::where('case_number', $single->case_number)->first();
if (!$case) {
    echo "Could not resolve ForensicCase for " . $single->case_number . "\n";
    exit(0);
}

$units = [
    'Lab Analysis' => ['unit' => 'Lab Analysis', 'is_head' => false],
    'Crime Scene Support' => ['unit' => 'Crime Scene Support', 'is_head' => false],
    'Digital Forensics' => ['unit' => 'Digital Forensics', 'is_head' => false],
    'Evidence Management' => ['unit' => 'Evidence Management', 'is_head' => false],
    'Head of Directorate (HOF)' => ['unit' => 'Forensic Directorate', 'is_head' => true],
];

echo "Testing view rendering for Case: " . $case->case_number . " (Unit: " . $case->unit_name . ")\n\n";

foreach ($units as $unitName => $uData) {
    $user = User::where('unit', $uData['unit'])->first();
    if (!$user) {
        $user = new User([
            'id' => 9999,
            'fullname' => 'Test ' . $unitName,
            'username' => 'test_' . strtolower(str_replace(' ', '_', $unitName)),
            'unit' => $uData['unit'],
            'is_head' => $uData['is_head'],
            'role' => $uData['is_head'] ? 'Head of Directorate' : 'Officer'
        ]);
    }
    auth()->login($user);
    session(['user_unit' => $uData['unit']]);

    try {
        $html = view('cases.show_review', [
            'case' => $case,
            'docketData' => is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []),
            'authUser' => $user,
        ])->render();

        $hasS4 = str_contains($html, 'id="section4Seizures"');
        $hasS5 = str_contains($html, 'id="section5LabExamination"');
        $hasS5Form = str_contains($html, 'id="section5FormContainer"');

        echo "[$unitName]\n";
        echo "  - Section 4 (Seized Items & Exhibits): " . ($hasS4 ? "VISIBLE (PASS)" : "HIDDEN (FAIL)") . "\n";
        echo "  - Section 5 (Lab Examination):         " . ($hasS5 ? "VISIBLE" : "HIDDEN") . "\n";

        // Verification according to matrix:
        if ($unitName === 'Lab Analysis') {
            if ($hasS4 && $hasS5) {
                echo "  => RESULT: MATCHES MATRIX (Both Visible)\n";
            } else {
                echo "  => RESULT: MISMATCH (Expected both visible)\n";
            }
        } else {
            if ($hasS4 && !$hasS5) {
                echo "  => RESULT: MATCHES MATRIX (Section 4 Visible, Section 5 Hidden)\n";
            } else {
                echo "  => RESULT: MISMATCH (Expected Section 4 Visible, Section 5 Hidden)\n";
            }
        }
    } catch (\Throwable $e) {
        echo "[$unitName] ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
