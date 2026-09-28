<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('fullname', 'Madam Diana')->first();
auth()->login($user);
request()->merge(['from' => 'Evidence Management']);

$cases = \App\Models\ForensicCase::allCases();

foreach ($cases as $c) {
    echo "=== CASE: " . $c->case_number . " (" . $c->title . ") ===\n";
    $view = (new \App\Http\Controllers\CaseController)->showReview($c->case_number);
    $html = $view->render();

    if (preg_match('/id="unitCard-labanalysis"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/', $html, $m)) {
        $content = strip_tags($m[0]);
        // normalize whitespace
        $content = preg_replace('/\s+/', ' ', trim($content));
        echo "Card: " . $content . "\n";
    } else {
        echo "Card: NOT FOUND (not in units list?)\n";
    }
}
