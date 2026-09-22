<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'LIKE', '%5584%')->first();
if (!$case) {
    $case = \App\Models\ForensicCase::latest()->first();
}

echo "ID: " . $case->id . "\n";
echo "Case Number: " . $case->case_number . "\n";
echo "Suspect Name: " . $case->suspect_name . "\n";
echo "Seizure Name: " . $case->seizure_name . "\n";
echo "Seizure Drug Name: " . $case->seizure_name_drug . "\n";
echo "Confirmatory Substance ID: " . $case->confirmatory_substance_identification . "\n";
echo "Sample Weight Desc: " . $case->sample_weight_description . "\n";
echo "Presumptive Test Result: " . $case->presumptive_test_result . "\n";
echo "Detected Substances: " . json_encode($case->detected_substances) . "\n";

echo "\n--- DOCKET DATA ---\n";
print_r($case->docket_data);

echo "\n--- ASSOCIATE LAB EXAMS ---\n";
$assocLabs = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();
foreach ($assocLabs as $al) {
    echo "Lab ID: {$al->id} | Suspect: {$al->suspect_name} | Lab No: {$al->laboratory_number} | Substance ID: {$al->substance_identification} | Sample Desc: {$al->sample_weight_description}\n";
    print_r($al->toArray());
}
