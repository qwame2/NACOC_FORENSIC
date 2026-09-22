<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if (!$case) {
    $case = App\Models\ForensicCase::first();
}

echo "CASE ID: " . $case->id . " | NUMBER: " . $case->case_number . "\n";
echo "ROOT FIELDS:\n";
echo "Title: {$case->title}\n";
echo "Suspect Name: {$case->suspect_name}\n";
echo "Seizure Drug: {$case->seizure_name_drug}\n";
echo "Lab No: {$case->laboratory_number}\n";
echo "Presumptive: {$case->presumptive_test_result}\n";
echo "Confirmatory: {$case->confirmatory_test_result}\n";
echo "Gross: {$case->sample_weight_gross}\n";
echo "Substance ID: {$case->confirmatory_substance_identification}\n";
echo "Test Methods: {$case->confirmatory_test_methods}\n";

$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);
echo "\nDOCKET DATA SUSPECTS:\n";
print_r($d['suspects'] ?? []);

echo "\nDOCKET DATA SEIZURES:\n";
print_r($d['seizures'] ?? []);

echo "\nDOCKET DATA ASSOCIATE EXHIBITS:\n";
print_r($d['associate_exhibits'] ?? []);

echo "\nDOCKET DATA LAB EXAMINATION:\n";
print_r($d['lab_examination'] ?? []);

echo "\nDOCKET DATA LAB EXAMINATIONS LIST:\n";
print_r($d['lab_examinations_list'] ?? []);

echo "\nASSOCIATE LAB EXAMINATIONS TABLE:\n";
$assoc = App\Models\AssociateLabExamination::where('case_id', $case->id)->get();
print_r($assoc->toArray());
