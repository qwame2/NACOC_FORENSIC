<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = \App\Models\User::first();
if ($u) \Illuminate\Support\Facades\Auth::login($u);

$single = \App\Models\SingleSuspectCase::latest('id')->first();
if (!$single) {
    echo "No single suspect case found.\n";
    exit;
}
$case = \App\Models\ForensicCase::wrapRecord($single);

echo "Testing Case: {$case->case_number} (ID: {$case->id}, Unit: {$case->unit_name})\n";

try {
    $view = view('cases.show_review', ['case' => $case]);
    $html = $view->render();
    echo "SUCCESS: Rendered " . strlen($html) . " bytes successfully!\n";
} catch (\Throwable $e) {
    echo "ERROR rendering view: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
