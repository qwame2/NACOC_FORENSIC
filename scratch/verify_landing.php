<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$html = view('cases.landing', ['cases' => collect([])])->render();
echo "Rendered successfully!\n";
if (strpos($html, 'Case Directory &amp; Field Docket Registry') !== false) {
    echo "Title 'Case Directory & Field Docket Registry' present.\n";
}
if (strpos($html, 'bg-emerald-50 text-emerald-700 border border-emerald-200') !== false) {
    echo "CRIME SCENE SUPPORT badge badge still present!\n";
} else {
    echo "CRIME SCENE SUPPORT header badge removed successfully!\n";
}
