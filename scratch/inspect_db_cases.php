<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cases = \App\Models\ForensicCase::allCases();
echo "Total cases: " . count($cases) . "\n";
foreach ($cases as $c) {
    echo "----------------------------------------\n";
    echo "Case Number: " . $c->case_number . "\n";
    echo "Title: " . $c->title . "\n";
    echo "Unit: " . $c->unit_name . "\n";
    echo "Submitting: " . $c->submitting_officer . "\n";
    echo "Commanding: " . $c->commanding_officer . "\n";
    $dd = is_array($c->docket_data) ? $c->docket_data : json_decode($c->docket_data ?? '[]', true);
    echo "Unit Progress in DB: " . json_encode($dd['unit_progress'] ?? []) . "\n";
    echo "Lab Exam in DB: " . json_encode($dd['lab_examination'] ?? []) . "\n";
}
