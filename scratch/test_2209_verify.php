<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$viewHtml = view('Forensic_Head.show_review', ['case' => $case, 'case_number' => $case->case_number])->render();

preg_match_all('/LABORATORY EXAMINATION RESULT — [^\<]+/i', $viewHtml, $matches);
echo "All Laboratory Examination Result headers in rendered HTML:\n";
print_r($matches[0]);
