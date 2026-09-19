<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$viewReview = view('Forensic_Head.show_review', ['case' => $case, 'from' => 'Lab Analysis'])->render();

echo "Is ATTA BUDO in Review HTML? " . (str_contains(strtolower($viewReview), 'atta budo') ? 'YES' : 'NO') . "\n";

if (preg_match_all('/LABORATORY EXAMINATION RESULT[^<]*/i', $viewReview, $matches)) {
    print_r($matches[0]);
} else {
    echo "No LAB EXAM matches found!\n";
}
