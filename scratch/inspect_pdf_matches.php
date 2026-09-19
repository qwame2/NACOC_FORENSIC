<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$viewPdf = view('Forensic_Head.case_pdf', ['case' => $case])->render();

echo "Is ATTA BUDO in PDF HTML? " . (str_contains(strtolower($viewPdf), 'atta budo') ? 'YES' : 'NO') . "\n";

if (preg_match_all('/LABORATORY EXAMINATION RESULT[^<]*/i', $viewPdf, $matches)) {
    print_r($matches[0]);
} else {
    echo "No matches found!\n";
}
