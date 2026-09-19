<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if ($case) {
    echo "Primary Suspect Name: " . $case->suspect_name . "\n";
    echo "Lab Examination in docket:\n" . json_encode($case->docket_data['lab_examination'] ?? [], JSON_PRETTY_PRINT) . "\n";
}
