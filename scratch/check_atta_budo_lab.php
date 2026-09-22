<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::find(69);

echo "=== DB Associate Lab Examination for Atta Budo ===\n";
$dbAtta = \App\Models\AssociateLabExamination::where('case_id', 69)->where('suspect_name', 'LIKE', '%Atta Budo%')->first();
if ($dbAtta) {
    echo "ID: {$dbAtta->id}\n";
    echo "Presumptive: {$dbAtta->presumptive_result}\n";
    echo "Substance ID: {$dbAtta->substance_identification}\n";
    echo "Detected Substances (raw): " . var_export($dbAtta->detected_substances, true) . "\n";
} else {
    echo "No DB Associate Lab Examination found for Atta Budo.\n";
}

echo "\n=== New Relational Table laboratory_examination_results for Atta Budo ===\n";
$relAtta = \App\Models\LaboratoryExaminationResult::where('multiple_suspect_case_id', 1)->get();
foreach ($relAtta as $ra) {
    echo "ID: {$ra->id} | Suspect ID: {$ra->suspect_id} | Substance: {$ra->substance_identification} | Detected: " . json_encode($ra->detected_substances) . "\n";
}

echo "\n=== Docket Data Associate Lab Exams ===\n";
$dd = is_array($case->docket_data) ? $case->docket_data : [];
print_r($dd['associate_lab_examinations'] ?? []);
