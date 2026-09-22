<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;

echo "=== SINGLE SUSPECT CASES COUNT: " . SingleSuspectCase::count() . " ===\n";
$singleCase = SingleSuspectCase::first();
if ($singleCase) {
    echo "Single Case: {$singleCase->case_number} | Suspect: {$singleCase->suspect_name}\n";
    echo "  - Exhibits Count: " . $singleCase->exhibits()->count() . "\n";
    echo "  - Lab Results Count: " . $singleCase->labResults()->count() . "\n";
}

echo "\n=== MULTIPLE SUSPECT CASES COUNT: " . MultipleSuspectCase::count() . " ===\n";
$multiCase = MultipleSuspectCase::with(['suspects', 'exhibits', 'labResults'])->first();
if ($multiCase) {
    echo "Multiple Case: {$multiCase->case_number} | Title: {$multiCase->title}\n";
    echo "  - Total Suspects Count: " . $multiCase->suspects->count() . "\n";
    foreach ($multiCase->suspects as $sp) {
        echo "    * [#{$sp->suspect_number}] {$sp->suspect_name} ({$sp->role})\n";
        echo "      - Exhibits: " . $sp->exhibits->count() . "\n";
        echo "      - Lab Results: " . $sp->labResults->count() . "\n";
    }
    echo "  - Total Case Exhibits Count: " . $multiCase->exhibits->count() . "\n";
    echo "  - Total Case Lab Results Count: " . $multiCase->labResults->count() . "\n";
}

echo "\n=== TOTAL EXHIBITS COUNT: " . Exhibit::count() . " ===\n";
echo "=== TOTAL LAB RESULTS COUNT: " . LaboratoryExaminationResult::count() . " ===\n";
