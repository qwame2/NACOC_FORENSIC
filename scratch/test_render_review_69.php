<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::find(69);

// Mock view render
$view = view('Forensic_Head.show_review', ['case' => $case])->render();

file_put_contents('scratch/rendered_review_69.html', $view);

echo "Rendered show_review. Size: " . strlen($view) . "\n";

if (str_contains($view, 'LABORATORY EXAMINATION RESULT')) {
    echo "Found LABORATORY EXAMINATION RESULT in view\n";
}

// Find occurrence of LABORATORY EXAMINATION RESULT — ERIC ADDO, LINDA ATTA
preg_match_all('/LABORATORY EXAMINATION RESULT[^\<]*/i', $view, $matches);
print_r($matches[0]);

// Find substance identified section around Eric Addo
if (preg_match('/LABORATORY EXAMINATION RESULT — ERIC ADDO, LINDA ATTA.*?Substance Identified:.*?<td[^>]*>(.*?)<\/td>/s', $view, $mSub)) {
    echo "Substance Identified for Eric Addo, Linda Atta: " . trim(strip_tags($mSub[1])) . "\n";
} else {
    echo "Could not match Substance Identified pattern.\n";
}
