<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if (!$case) { echo "Case not found\n"; exit; }
echo "Case ID: " . $case->id . "\n";
echo "--- DB AssociateLabExamination records ---\n";
foreach (App\Models\AssociateLabExamination::where('case_id', $case->id)->get() as $a) {
    echo "ID: {$a->id}, Suspect: '{$a->suspect_name}', Presumptive: '{$a->presumptive_result}', Confirmatory: '{$a->confirmatory_result}', Gross: '{$a->sample_weight_gross}', LabNo: '{$a->laboratory_number}', Analyst: '{$a->analyst}', DateRec: '{$a->date_received}'\n";
}
