<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::find(69);

$view = view('Forensic_Head.case_pdf', ['case' => $case])->render();

file_put_contents('scratch/rendered_pdf_69.html', $view);

echo "Rendered PDF. Size: " . strlen($view) . "\n";

preg_match_all('/LABORATORY EXAMINATION RESULT[^\<]*/i', $view, $matches);
print_r($matches[0]);

if (preg_match('/LABORATORY EXAMINATION RESULT — ERIC ADDO, LINDA ATTA.*?Substance Identified:.*?<td[^>]*>(.*?)<\/td>/s', $view, $mSub)) {
    echo "PDF Substance Identified for Eric Addo, Linda Atta: " . trim(strip_tags($mSub[1])) . "\n";
} else {
    echo "PDF: Could not match Substance Identified pattern for Eric Addo, Linda Atta.\n";
}

if (preg_match('/LABORATORY EXAMINATION RESULT — ATTA BUDO.*?Substance Identified:.*?<td[^>]*>(.*?)<\/td>/s', $view, $mSub2)) {
    echo "PDF Substance Identified for Atta Budo: " . trim(strip_tags($mSub2[1])) . "\n";
} else {
    echo "PDF: Could not match Substance Identified pattern for Atta Budo.\n";
}
