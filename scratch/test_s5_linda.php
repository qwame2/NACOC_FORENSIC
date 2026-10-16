<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

// Simulate request ?suspect=LINDA ADDO
request()->merge(['suspect' => 'LINDA ADDO']);

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();
$view = view('cases.show_review', ['case' => $case, 'isLabAnalysis' => true])->render();

if (preg_match('/<div[^>]*id="section5LabExamination"[^>]*>(.*?)<!-- SECTION 4/is', $view, $m)) {
    echo "=== SECTION 5 CONTENT FOR LINDA ADDO ===\n";
    $content = preg_replace('/\s+/', ' ', strip_tags($m[1]));
    echo $content . "\n";
}
