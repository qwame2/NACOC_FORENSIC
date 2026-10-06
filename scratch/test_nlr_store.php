<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Initial SuspectNlrReport count: " . \App\Models\SuspectNlrReport::count() . "\n";

$case = \App\Models\MultipleSuspectCase::first();
if (!$case) {
    $case = \App\Models\MultipleSuspectCase::create([
        'case_number' => 'CERT-LAB-2026-685A8B',
        'title' => 'Test Case',
        'unit_name' => 'Forensic Laboratory',
    ]);
}

echo "Case Number: " . $case->case_number . " (ID: " . $case->id . ")\n";

$suspect = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $case->id)->first();
if (!$suspect) {
    $suspect = \App\Models\CaseSuspect::create([
        'multiple_suspect_case_id' => $case->id,
        'suspect_number' => 1,
        'suspect_name' => 'KOFI ADU',
    ]);
}

echo "Suspect Name: " . $suspect->suspect_name . " (ID: " . $suspect->id . ")\n";

$req = Illuminate\Http\Request::create('/Lab_Analysis/nlr-reports/store', 'POST', [
    'case_id' => $case->id,
    'case_number' => $case->case_number,
    'laboratory_number' => 'Lab No. 12/FS03/2026',
    'detailed_exhibit_description' => 'Test detailed exhibit description narrative for NLR report test.',
    'condition_of_reserved_sample' => 'good',
    'suspect_id' => $suspect->id,
]);
$req->headers->set('Accept', 'application/json');

$controller = new \App\Http\Controllers\CaseController();
$res = $controller->storeNlrReport($req);

echo "Final SuspectNlrReport count: " . \App\Models\SuspectNlrReport::count() . "\n";

$records = \App\Models\SuspectNlrReport::all();
foreach ($records as $r) {
    echo "Record ID {$r->id}: case_suspect_id={$r->case_suspect_id}, case_number={$r->case_number}, original_filename={$r->original_filename}, file_path={$r->file_path}\n";
}
