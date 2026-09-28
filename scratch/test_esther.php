<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('fullname', 'Madam Diana')->first();
auth()->login($user);
request()->merge(['from' => 'Evidence Management', 'suspect' => 'Esther']);

$view = (new \App\Http\Controllers\CaseController)->showReview('CSS-2026-9984');
$html = $view->render();

if (preg_match('/id="unitCard-labanalysis"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/', $html, $m)) {
    $content = strip_tags($m[0]);
    $content = preg_replace('/\s+/', ' ', trim($content));
    echo "Suspect Esther Card: " . $content . "\n";
} else {
    echo "Card not found for Esther\n";
}
