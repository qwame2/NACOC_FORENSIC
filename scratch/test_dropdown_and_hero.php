<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\ForensicCase;
use App\Models\User;

$single = SingleSuspectCase::first();
$case = ForensicCase::where('case_number', $single->case_number)->first();

$userLab = User::where('unit', 'Lab Analysis')->first() ?? new User(['unit' => 'Lab Analysis', 'is_head' => false]);
$userCss = User::where('unit', 'Crime Scene Support')->first() ?? new User(['unit' => 'Crime Scene Support', 'is_head' => false]);

// 1. Check Lab
auth()->login($userLab);
session(['user_unit' => 'Lab Analysis']);
$htmlLab = view('cases.show_review', [
    'case' => $case,
    'docketData' => is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []),
    'authUser' => $userLab,
])->render();

// 2. Check CSS
auth()->login($userCss);
session(['user_unit' => 'Crime Scene Support']);
$htmlCss = view('cases.show_review', [
    'case' => $case,
    'docketData' => is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []),
    'authUser' => $userCss,
])->render();

echo "1. Suspect dropdown options in Section 4 for CSS:\n";
preg_match('/<select[^>]*class="[^"]*seizure-associated-person-select[^"]*"[^>]*>(.*?)<\/select>/s', $htmlCss, $matches);
if (!empty($matches[1])) {
    echo trim($matches[1]) . "\n";
    echo "  => PASS: Dropdown populated!\n";
} else {
    echo "  => FAIL: Dropdown not found!\n";
}

echo "\n2. Toggle Button in Hero Header:\n";
$hasToggleLab = str_contains($htmlLab, 'toggleLabFormSections()');
$hasToggleCss = str_contains($htmlCss, 'toggleLabFormSections()');
echo "  - Lab Analysis has toggle: " . ($hasToggleLab ? "YES (PASS)" : "NO (FAIL)") . "\n";
echo "  - CSS has toggle:          " . ($hasToggleCss ? "YES (FAIL)" : "NO (PASS)") . "\n";

echo "\n3. Save buttons in Section 4:\n";
$hasSaveBtnCss = str_contains($htmlCss, 'id="saveSection4SeizureBtn"');
$hasSaveBtnLab = str_contains($htmlLab, 'id="saveSection4SeizureBtn"');
echo "  - CSS Section 4 has Save button: " . ($hasSaveBtnCss ? "YES (PASS)" : "NO (FAIL)") . "\n";
echo "  - Lab Section 4 has Save button: " . ($hasSaveBtnLab ? "YES (PASS)" : "NO (FAIL)") . "\n";
