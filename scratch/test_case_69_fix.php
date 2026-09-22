<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::find(69);
if (!$case) {
    echo "Case 69 not found\n";
    exit;
}

$view = view('Forensic_Head.show_review', ['case' => $case])->render();

if (str_contains($view, 'ERIC ADDO, LINDA ATTA')) {
    echo "FOUND ERIC ADDO, LINDA ATTA in rendered view!\n";
    preg_match_all('/.*ERIC ADDO, LINDA ATTA.*/i', $view, $matches);
    foreach ($matches[0] as $m) {
        echo "Match: " . trim($m) . "\n";
    }
} else {
    echo "SUCCESS: ERIC ADDO, LINDA ATTA is NOT present in rendered view!\n";
}
