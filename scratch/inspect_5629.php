<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::find('CSS-2026-5629');
if (!$case) {
    $case = ForensicCase::where('case_number', 'CSS-2026-5629')->first();
}

echo "Case Number: {$case->case_number}\n";
echo "Title: {$case->title}\n";
echo "Exhibit Category: {$case->exhibit_category}\n";
echo "Seizure Name: {$case->seizure_name}\n";

echo "\n--- Workflow Units ---\n";
print_r($case->getWorkflowUnits());

echo "\n--- Is Drug Case? ---\n";
var_dump($case->isDrugCase());
