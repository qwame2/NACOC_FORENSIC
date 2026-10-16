<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();
$view = view('cases.show_review', ['case' => $case])->render();

// Find headings and occurrences of ERIC and LINDA in the rendered HTML
preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $view, $headings);
echo "=== HEADINGS ===\n";
foreach ($headings[1] as $h) {
    $clean = strip_tags($h);
    if (stripos($clean, 'ERIC') !== false || stripos($clean, 'LINDA') !== false || stripos($clean, 'LABORATORY') !== false || stripos($clean, 'EXAMINATION') !== false || stripos($clean, 'EXHIBIT') !== false) {
        echo trim(preg_replace('/\s+/', ' ', $clean)) . "\n";
    }
}

// Find occurrences of LABORATORY EXAMINATION RESULT in view
echo "\n=== LAB EXAM BLOCKS ===\n";
preg_match_all('/.{0,100}LABORATORY EXAMINATION RESULT.{0,100}/is', $view, $matches);
foreach ($matches[0] as $m) {
    echo trim(preg_replace('/\s+/', ' ', strip_tags($m))) . "\n";
}
