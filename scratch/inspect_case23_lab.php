<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();

$out = [];
$out['case_id'] = $case->id;
$out['case_number'] = $case->case_number;

$labResults = \App\Models\LaboratoryExaminationResult::where('multiple_suspect_case_id', $case->id)->get();
$out['lab_results'] = $labResults->toArray();

$assocLabs = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();
$out['assoc_labs'] = $assocLabs->toArray();

$out['docket_lab_examination'] = $case->docket_data['lab_examination'] ?? null;
$out['docket_lab_examinations_list'] = $case->docket_data['lab_examinations_list'] ?? null;
$out['docket_associate_lab_examinations'] = $case->docket_data['associate_lab_examinations'] ?? null;
$out['docket_seizures'] = $case->docket_data['seizures'] ?? null;
$out['docket_suspects'] = $case->docket_data['suspects'] ?? null;

file_put_contents('scratch/case23_lab_data.json', json_encode($out, JSON_PRETTY_PRINT));
echo "Wrote scratch/case23_lab_data.json\n";
