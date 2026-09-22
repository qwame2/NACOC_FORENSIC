<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();

$pdfView = view('Forensic_Head.case_pdf', compact('case'))->render();

if (str_contains($pdfView, 'ATTA BUDO')) {
    echo "PDF View contains ATTA BUDO.\n";
} else {
    echo "PDF View does NOT contain ATTA BUDO.\n";
}

if (str_contains($pdfView, 'LABORATORY EXAMINATION RESULT — ATTA BUDO') || str_contains($pdfView, 'LABORATORY EXAMINATION RESULT &mdash; ATTA BUDO')) {
    echo "PASS: PDF View contains LABORATORY EXAMINATION RESULT for ATTA BUDO.\n";
} else {
    echo "FAIL: PDF View does NOT contain LABORATORY EXAMINATION RESULT for ATTA BUDO!\n";
}
