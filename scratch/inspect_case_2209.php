<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
if (!$case) {
    echo "Case CSS-2026-2209 not found in DB!\n";
    exit;
}

echo "Case ID: " . $case->id . "\n";
echo "Title: " . $case->title . "\n";
echo "Suspect Name: " . $case->suspect_name . "\n";
echo "Laboratory Number: " . $case->laboratory_number . "\n";
echo "Lab Status: " . ($case->lab_status ?? 'N/A') . "\n";
echo "\n--- DOCKET DATA KEYS ---\n";
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true);
print_r(array_keys($d));

echo "\n--- LAB EXAMINATION IN DOCKET DATA ---\n";
print_r($d['lab_examination'] ?? 'NONE');

echo "\n--- LAB EXAMINATIONS LIST IN DOCKET DATA ---\n";
print_r($d['lab_examinations_list'] ?? 'NONE');

echo "\n--- SEIZURES IN DOCKET DATA ---\n";
print_r($d['seizures'] ?? 'NONE');

echo "\n--- SUSPECTS IN DOCKET DATA ---\n";
print_r($d['suspects'] ?? 'NONE');
