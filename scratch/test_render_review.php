<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if (!$case) { echo "Case not found\n"; exit; }

// Render view
try {
    $html = view('Forensic_Head.show_review', [
        'case' => $case,
        'from' => 'Lab Analysis'
    ])->render();

    file_put_contents(__DIR__ . '/review_output.html', $html);
    echo "Rendered review HTML, saved to review_output.html. Length: " . strlen($html) . "\n";

    // Extract LABORATORY EXAMINATION RESULT sections
    preg_match_all('/LABORATORY EXAMINATION RESULT.*?(?=<\/div>\s*<\/div>\s*<\/div>|LABORATORY EXAMINATION RESULT|$)/s', $html, $matches);
    echo "Found " . count($matches[0]) . " matching header snippets:\n";
    
    // Find headers containing LABORATORY EXAMINATION RESULT
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $cards = $xpath->query("//*[contains(text(), 'LABORATORY EXAMINATION RESULT')]");
    echo "XPATH count: " . $cards->length . "\n";
    foreach ($cards as $card) {
        echo "Card header text: " . trim($card->nodeValue) . "\n";
    }

} catch (\Throwable $e) {
    echo "Error rendering view: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
