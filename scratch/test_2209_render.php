<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$viewReview = view('Forensic_Head.show_review', ['case' => $case, 'from' => 'Lab Analysis'])->render();

echo "Is section5LabForm hidden? " . (str_contains($viewReview, '<form id="section5LabForm" onsubmit="submitSection5LabExam(event)" class="space-y-5 hidden">') ? 'YES (hidden)' : 'NO (open/visible)') . "\n";
echo "Dropdown option for Tramadol (Princilla Addo): " . (str_contains(strtolower($viewReview), 'tramadol') ? 'FOUND IN DROPDOWN' : 'NOT IN DROPDOWN') . "\n";
echo "Dropdown option for Cocaine (Atta Budo): " . (str_contains(strtolower($viewReview), 'data-name="cocaine') ? 'FOUND IN DROPDOWN' : 'NOT IN DROPDOWN') . "\n";
