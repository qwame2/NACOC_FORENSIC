<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = new \App\Models\ForensicCase([
    'case_number' => 'NFC-2026-LAB01',
    'title' => 'State vs Eric Addo',
    'suspect_name' => 'Eric Addo',
    'unit_name' => 'Lab Analysis',
    'status' => 'Pending Approval',
    'docket_data' => [
        'suspects' => [
            ['suspect_name' => 'Eric Addo', 'association_role' => 'Primary Suspect']
        ],
        'seizures' => []
    ]
]);

$req = \Illuminate\Http\Request::create("/cases/{$case->case_number}/review?from=Lab+Analysis", 'GET');
app()->instance('request', $req);

$user = new \App\Models\User([
    'fullname' => 'Test Analyst',
    'unit' => 'Lab Analysis',
    'is_head' => false,
]);
auth()->setUser($user);

$view = view('cases.show_review', ['case' => $case]);
$html = $view->render();

echo "Case: {$case->case_number} | isDrugCase: " . ($case->isDrugCase() ? 'YES' : 'NO') . "\n";
echo "id=section5LabExamination present: " . (str_contains($html, 'id="section5LabExamination"') ? 'YES' : 'NO') . "\n";
echo "id=section5FormContainer present: " . (str_contains($html, 'id="section5FormContainer"') ? 'YES' : 'NO') . "\n";
echo "id=section5FormContainer has 'hidden': " . (preg_match('/id="section5FormContainer"[^>]*class="[^"]*hidden[^"]*"/', $html) ? 'YES' : 'NO') . "\n";
echo "Contains 'Type of Drug': " . (str_contains($html, 'Type of Drug') ? 'YES' : 'NO') . "\n";
echo "Contains 'Select Seized Exhibit to Examine (Manifest Sync)': " . (str_contains($html, 'Select Seized Exhibit to Examine (Manifest Sync)') ? 'YES' : 'NO') . "\n";
echo "id=section5NoDrugExhibitNotice present: " . (str_contains($html, 'id="section5NoDrugExhibitNotice"') ? 'YES' : 'NO') . "\n";
echo "id=section5NoDrugExhibitNotice has 'hidden': " . (preg_match('/id="section5NoDrugExhibitNotice"[^>]*class="[^"]*hidden[^"]*"/', $html) ? 'YES' : 'NO') . "\n";
