<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cases = \App\Models\ForensicCase::all();

$viewHtml = view('cases.landing', ['cases' => $cases])->render();

if (str_contains($viewHtml, 'Associate Suspect') && str_contains($viewHtml, 'ring-2 ring-white') && str_contains($viewHtml, '-space-x-3.5')) {
    echo "SUCCESS: Pure white ring border (ring-2 ring-white) rendering properly!\n";
} else {
    echo "WARNING: Could not find expected CSS classes in rendered HTML.\n";
}
