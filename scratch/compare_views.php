<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = new App\Http\Controllers\CaseController();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();

// Render Review view
$reviewHtml = view('Forensic_Head.show_review', compact('case'))->render();
// Render PDF view
$pdfHtml = view('Forensic_Head.case_pdf', compact('case'))->render();

file_put_contents('scratch/review_rendered.html', $reviewHtml);
file_put_contents('scratch/pdf_rendered.html', $pdfHtml);

echo "Review HTML length: " . strlen($reviewHtml) . "\n";
echo "PDF HTML length: " . strlen($pdfHtml) . "\n";

echo "\n--- REVIEW OCCURRENCES OF ATTA BUDO ---\n";
preg_match_all('/.*ATTA BUDO.*/i', $reviewHtml, $matchesReview);
foreach ($matchesReview[0] as $m) {
    echo trim(strip_tags($m)) . "\n";
}

echo "\n--- PDF OCCURRENCES OF ATTA BUDO ---\n";
preg_match_all('/.*ATTA BUDO.*/i', $pdfHtml, $matchesPdf);
foreach ($matchesPdf[0] as $m) {
    echo trim(strip_tags($m)) . "\n";
}
