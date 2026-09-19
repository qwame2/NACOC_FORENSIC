<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$viewHtml = view('Forensic_Head.show_review', ['case' => $case, 'case_number' => $case->case_number])->render();

// Extract all tables inside section5ReadOnlyDisplay
if (preg_match('/<div id="section5ReadOnlyDisplay"[^>]*>(.*?)<\/div>\s*<\/div>/s', $viewHtml, $m)) {
    echo "=== SECTION 5 READ-ONLY BLOCK HTML ===\n";
    echo strip_tags($m[1], '<table><tr><td><th><div><span>') . "\n";
} else {
    // Find all blocks with LABORATORY EXAMINATION RESULT
    preg_match_all('/LABORATORY EXAMINATION RESULT — [^\<]+/i', $viewHtml, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        $offset = $match[1];
        echo "=== MATCH AT OFFSET {$offset} ===\n";
        echo substr($viewHtml, $offset - 100, 1500) . "\n\n";
    }
}
