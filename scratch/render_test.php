<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::first();
if (!$case) {
    echo "No case found\n";
    exit;
}

$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
}

$html = view('Forensic_Head.case_pdf', compact('case'))->render();
file_put_contents(__DIR__ . '/rendered_case.html', $html);
echo "Rendered successfully to scratch/rendered_case.html (" . strlen($html) . " bytes)\n";
