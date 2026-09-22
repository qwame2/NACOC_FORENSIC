<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();

echo "Case: {$case->case_number}\n";

$webHtml = view('Forensic_Head.show_review', compact('case'))->render();
$pdfHtml = view('Forensic_Head.case_pdf', compact('case'))->render();

echo "--- WEB REVIEW PAGE CHECK ---\n";
if (str_contains($webHtml, 'LABORATORY EXAMINATION RESULT — ATTA BUDO') || str_contains($webHtml, 'LABORATORY EXAMINATION RESULT &mdash; ATTA BUDO')) {
    echo "  [PASS] Web page renders LABORATORY EXAMINATION RESULT for ATTA BUDO.\n";
} else {
    echo "  [FAIL] Web page MISSING LABORATORY EXAMINATION RESULT for ATTA BUDO!\n";
}

if (!str_contains($webHtml, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU') && !str_contains($webHtml, 'LABORATORY EXAMINATION RESULT &mdash; WAKANDA ASIEDU')) {
    echo "  [PASS] Web page correctly OMITS LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU.\n";
} else {
    echo "  [FAIL] Web page INCORRECTLY SHOWS LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU!\n";
}

echo "--- PDF EXPORT DOSSIER CHECK ---\n";
if (str_contains($pdfHtml, 'LABORATORY EXAMINATION RESULT — ATTA BUDO') || str_contains($pdfHtml, 'LABORATORY EXAMINATION RESULT &mdash; ATTA BUDO')) {
    echo "  [PASS] PDF export renders LABORATORY EXAMINATION RESULT for ATTA BUDO.\n";
} else {
    echo "  [FAIL] PDF export MISSING LABORATORY EXAMINATION RESULT for ATTA BUDO!\n";
}

if (!str_contains($pdfHtml, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU') && !str_contains($pdfHtml, 'LABORATORY EXAMINATION RESULT &mdash; WAKANDA ASIEDU')) {
    echo "  [PASS] PDF export correctly OMITS LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU.\n";
} else {
    echo "  [FAIL] PDF export INCORRECTLY SHOWS LABORATORY EXAMINATION RESULT for WAKANDA ASIEDU!\n";
}
