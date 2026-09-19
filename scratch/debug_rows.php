<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$viewHtml = view('Forensic_Head.show_review', ['case' => $case, 'case_number' => $case->case_number])->render();

$pos = strpos($viewHtml, 'section5ReadOnlyDisplay');
if ($pos !== false) {
    $sectionHtml = substr($viewHtml, $pos, 15000);
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $sectionHtml, $rows);
    echo "Total rows found in Section 5 read-only display: " . count($rows[0]) . "\n";
    foreach ($rows[0] as $rIdx => $rowHtml) {
        $rowClean = trim(preg_replace('/\s+/', ' ', strip_tags($rowHtml)));
        echo "Row {$rIdx}: {$rowClean}\n";
    }
} else {
    echo "section5ReadOnlyDisplay not found\n";
}
