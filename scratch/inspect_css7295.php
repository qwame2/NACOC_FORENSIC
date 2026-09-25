<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$c = ForensicCase::where('case_number', 'CSS-2026-7295')->first();
if (!$c) { echo "Case CSS-2026-7295 not found!\n"; exit; }

echo "=== CASE DETAILS ===\n";
echo "ID: {$c->id}\n";
echo "Case #: {$c->case_number}\n";
echo "Title: {$c->title}\n";
echo "Unit Name: '{$c->unit_name}'\n";
echo "Suspect Name: '{$c->suspect_name}'\n";
echo "Exhibit Cat: '{$c->exhibit_category}'\n";
echo "Seizure Name: '{$c->seizure_name}'\n";
echo "Seizure Drug: '{$c->seizure_name_drug}'\n";
echo "Exhibits Summary: '{$c->exhibits_summary}'\n";
echo "isDrugCase(): " . ($c->isDrugCase() ? 'YES' : 'NO') . "\n";
echo "Workflow Units: " . json_encode($c->getWorkflowUnits()) . "\n";
echo "Mugshots: Front=" . (!empty($c->mugshot_front) ? strlen($c->mugshot_front) : 'NONE') . ", Left=" . (!empty($c->mugshot_left) ? strlen($c->mugshot_left) : 'NONE') . ", Right=" . (!empty($c->mugshot_right) ? strlen($c->mugshot_right) : 'NONE') . "\n";

$d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
echo "Docket Lab Exam: " . json_encode($d['lab_examination'] ?? null) . "\n";
echo "Docket Unit Progress: " . json_encode($d['unit_progress'] ?? null) . "\n";
