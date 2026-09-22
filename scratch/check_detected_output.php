<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::find(69);
if (!$case) {
    echo "Case 69 not found\n";
    exit(1);
}

echo "=== REVIEW VIEW == estadísticas\n";
$reviewHtml = view('Forensic_Head.show_review', ['case' => $case])->render();
if (preg_match_all('/Detected Compounds:<\/td>\s*<td[^>]*>(.*?)<\/td>/is', $reviewHtml, $matches)) {
    foreach ($matches[1] as $idx => $m) {
        echo "Review Match [$idx]: " . trim(preg_replace('/\s+/', ' ', strip_tags($m))) . "\n";
    }
} else {
    echo "No Detected Compounds matches found in Review View\n";
}

echo "\n=== PDF VIEW ===\n";
$pdfHtml = view('Forensic_Head.case_pdf', ['case' => $case])->render();
if (preg_match_all('/Detected Compounds:<\/td>\s*<td[^>]*>(.*?)<\/td>/is', $pdfHtml, $matchesPdf)) {
    foreach ($matchesPdf[1] as $idx => $m) {
        echo "PDF Match [$idx]: " . trim(preg_replace('/\s+/', ' ', strip_tags($m))) . "\n";
    }
} else {
    echo "No Detected Compounds matches found in PDF View\n";
}
