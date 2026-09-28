<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('fullname', 'Madam Diana')->first();
auth()->login($user);

request()->merge(['from' => 'Evidence Management']);
$view = (new \App\Http\Controllers\CaseController)->showReview('CSS-2026-9984');
$html = $view->render();

// Find the unitCard-labanalysis in $html
if (preg_match('/id="unitCard-labanalysis"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/', $html, $m)) {
    echo "Found unitCard-labanalysis:\n" . strip_tags($m[0]) . "\n";
} else {
    echo "unitCard-labanalysis not found in pattern.\n";
    // Search for Lab Analysis in $html
    preg_match_all('/Lab Analysis[\s\S]{0,400}/', $html, $matches);
    foreach ($matches[0] as $match) {
        echo "Match: " . strip_tags($match) . "\n---\n";
    }
}
