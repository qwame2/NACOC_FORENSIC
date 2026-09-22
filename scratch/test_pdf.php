<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = new App\Http\Controllers\CaseController();
$response = $controller->showPdf('CSS-2026-5584');

if ($response instanceof \Illuminate\View\View) {
    $content = $response->render();
} else {
    $content = $response->getContent();
}

file_put_contents('scratch/pdf_output_5584.html', $content);
echo "HTML Length: " . strlen($content) . "\n";
echo "ATTA BUDO occurrences: " . substr_count($content, 'ATTA BUDO') . "\n";
echo "ASSOCIATE DOSSIER occurrences: " . substr_count($content, 'ASSOCIATE DOSSIER') . "\n";
echo "LABORATORY EXAMINATION RESULT occurrences: " . substr_count($content, 'LABORATORY EXAMINATION RESULT') . "\n";

$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'ATTA BUDO') !== false || strpos($line, 'ASSOCIATE DOSSIER') !== false) {
        echo "Line " . ($i + 1) . ": " . trim(strip_tags($line)) . "\n";
    }
}
