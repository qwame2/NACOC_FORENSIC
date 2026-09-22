<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;

$singleCase = SingleSuspectCase::create([
    'case_number'        => 'CSS-2026-9999',
    'title'              => 'Republic vs Kwame Mensah',
    'suspect_name'       => 'Kwame Mensah',
    'suspect_alias'      => 'K-One',
    'gender'             => 'Male',
    'nationality'        => 'Ghanaian',
    'mugshot_front'      => 'storage/mugshots/kwame_front.jpg',
    'offense_description'=> 'Unlawful possession of narcotic drugs',
    'priority'           => 'NORMAL',
    'status'             => 'Approved',
]);

$exhibit = $singleCase->exhibits()->create([
    'case_type'        => 'single',
    'exhibit_number'   => 'EXH-9999-01',
    'exhibit_category' => 'Drugs',
    'commodity_name'   => 'Cocaine Hydrochloride',
    'seizure_quantity' => '10 Slabs',
    'seizure_weight'   => '12.5 Kilograms (kg)',
    'seizure_unit'     => 'Kilograms (kg)',
]);

$labResult = $singleCase->labResults()->create([
    'case_type'                => 'single',
    'exhibit_id'               => $exhibit->id,
    'laboratory_number'        => 'Lab No.9999/FS09/2026',
    'exam_reference'           => 'EXAM-LAB-20260920-9999',
    'presumptive_result'       => 'YES (Presence of: Cocaine - Purity/Percentage: 85.4%)',
    'confirmatory_result'      => 'YES',
    'substance_identification' => 'Cocaine Hydrochloride',
    'detected_substances'      => [['substance' => 'Cocaine Hydrochloride', 'percentage' => '85.4%']],
    'has_saved'                => true,
]);

echo "Created SingleSuspectCase ID: {$singleCase->id}\n";
echo "  - Case Number: {$singleCase->case_number}\n";
echo "  - Suspect Name: {$singleCase->suspect_name}\n";
echo "  - Linked Exhibit: {$exhibit->commodity_name} ({$exhibit->seizure_weight})\n";
echo "  - Linked Lab Result: {$labResult->substance_identification} (Purity: 85.4%)\n";

// Cleanup test case
$labResult->delete();
$exhibit->delete();
$singleCase->delete();
echo "Test single-suspect case cleaned up.\n";
