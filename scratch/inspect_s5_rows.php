<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();
$view = view('cases.show_review', ['case' => $case, 'isLabAnalysis' => true])->render();

if (preg_match('/id="section5ReadOnlyDisplay"[^>]*>(.*?)<\/table>\s*<\/div>/is', $view, $m)) {
    echo "=== TABLE IN SECTION 5 ===\n";
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $m[1], $rows);
    foreach ($rows[1] as $r) {
        echo trim(preg_replace('/\s+/', ' ', strip_tags($r))) . "\n";
    }
}
