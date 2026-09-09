<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$htmlLanding = view('cases.landing', ['cases' => collect([])])->render();
echo "Landing blade rendered OK (" . strlen($htmlLanding) . " bytes)\n";

$htmlCases = view('Forensic_Head.cases', ['cases' => collect([])])->render();
echo "Forensic_Head.cases blade rendered OK (" . strlen($htmlCases) . " bytes)\n";

if (strpos($htmlLanding, 'STATUS: ONLINE &amp; SYNCHRONIZED') === false && strpos($htmlCases, 'STATUS: ONLINE &amp; SYNCHRONIZED') === false) {
    echo "STATUS: ONLINE & SYNCHRONIZED confirmed removed from all navbars!\n";
}
