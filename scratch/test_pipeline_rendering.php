<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = \App\Models\User::first();
if ($u) \Illuminate\Support\Facades\Auth::login($u);

$singles = \App\Models\SingleSuspectCase::latest('id')->take(5)->get();
foreach ($singles as $s) {
    $case = \App\Models\ForensicCase::wrapRecord($s);
    echo "\n=== Case {$case->case_number} (Creator: {$case->unit_name}, ID: {$case->id}) ===\n";
    $docket = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);
    echo "docket unit_progress: " . json_encode($docket['unit_progress'] ?? null) . "\n";
    $view = view('cases.show_review', ['case' => $case]);
    $html = $view->render();
    
    // Extract unit pipeline count and items
    if (preg_match('/(\d+)\/(\d+) Units Completed/', $html, $m)) {
        echo "Tracker Header: {$m[0]}\n";
    }
    if (preg_match_all('/id="unitBadge-([a-z0-9]+)"[^>]*>\s*([A-Z_ ]+)\s*<\/span>/', $html, $badges)) {
        foreach ($badges[1] as $idx => $slug) {
            echo "  Card [{$slug}]: {$badges[2][$idx]}\n";
        }
    }
}
