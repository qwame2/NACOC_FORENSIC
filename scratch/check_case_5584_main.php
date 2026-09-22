<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::find(69);

echo "Main seizure_name_drug: " . $case->seizure_name_drug . "\n";
echo "Main confirmatory_substance_identification: " . $case->confirmatory_substance_identification . "\n";
echo "Main sample_weight_description: " . $case->sample_weight_description . "\n";
echo "Main docket lab_examination: " . json_encode($case->docket_data['lab_examination'] ?? [], JSON_PRETTY_PRINT) . "\n";
