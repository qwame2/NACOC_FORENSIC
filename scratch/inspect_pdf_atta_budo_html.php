<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();

$pdfView = view('Forensic_Head.case_pdf', compact('case'))->render();

// Save PDF view HTML to file for inspection
file_put_contents(__DIR__ . '/pdf_output_5584.html', $pdfView);

// Find lines containing ATTA BUDO or LABORATORY EXAMINATION
$lines = explode("\n", $pdfView);
foreach ($lines as $i => $line) {
    if (str_contains(strtoupper($line), 'ATTA BUDO') || str_contains(strtoupper($line), 'ASSOCIATE DOSSIER')) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
