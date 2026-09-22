<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
if (!$case) {
    echo "Case not found\n";
    exit;
}

echo "=== Case Model Fields ===\n";
echo "detected_substances: " . var_export($case->detected_substances, true) . "\n";
echo "confirmatory_substance_identification: " . var_export($case->confirmatory_substance_identification, true) . "\n";
echo "seizure_name_drug: " . var_export($case->seizure_name_drug, true) . "\n";
echo "seizure_name: " . var_export($case->seizure_name, true) . "\n";

$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
echo "\n=== Docket Data Keys ===\n";
print_r(array_keys($docketData));

if (isset($docketData['lab_examination'])) {
    echo "\n=== lab_examination ===\n";
    print_r($docketData['lab_examination']);
}

if (isset($docketData['lab_examinations_list'])) {
    echo "\n=== lab_examinations_list ===\n";
    print_r($docketData['lab_examinations_list']);
}

$dbAssocLabs = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();
echo "\n=== AssociateLabExaminations Count: " . $dbAssocLabs->count() . " ===\n";
foreach ($dbAssocLabs as $idx => $lab) {
    echo "Lab $idx (Suspect: {$lab->suspect_name}):\n";
    echo "  substance_identification: " . var_export($lab->substance_identification, true) . "\n";
    echo "  detected_substances: " . var_export($lab->detected_substances, true) . "\n";
}
