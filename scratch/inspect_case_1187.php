<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\Exhibit;

$case = ForensicCase::where('case_number', 'CSS-2026-1187')->first();
if (!$case) {
    echo "Case not found!\n";
    exit;
}

echo "=== CASE CSS-2026-1187 ===\n";
echo "ID: {$case->id}\n";
echo "Case Number: {$case->case_number}\n";
echo "Title: {$case->title}\n";
echo "Exhibit Category: '{$case->exhibit_category}'\n";
echo "Seizure Name: '{$case->seizure_name}'\n";
echo "Seizure Name Drug: '{$case->seizure_name_drug}'\n";
echo "Offense Description: '{$case->offense_description}'\n";
echo "Suspect Name: '{$case->suspect_name}'\n";
echo "Presumptive Test Result: '{$case->presumptive_test_result}'\n";
echo "Confirmatory Substance Identification: '{$case->confirmatory_substance_identification}'\n";
echo "Docket Data:\n";
var_dump($case->docket_data);

echo "\nDB Exhibits:\n";
$exs = Exhibit::where('multiple_suspect_case_id', $case->id)->orWhere('single_suspect_case_id', $case->id)->get();
print_r($exs->toArray());
