<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Delete erroneous Lab Examination Result ID 8
$deleted = \App\Models\LaboratoryExaminationResult::where('single_suspect_case_id', 7)->delete();
echo "Deleted lab examination records: {$deleted}\n";

// 2. Clear confirmatory / lab fields on SingleSuspectCase #7
$single = \App\Models\SingleSuspectCase::find(7);
if ($single) {
    $single->update([
        'confirmatory_test_result' => null,
        'confirmatory_substance_identification' => null,
        'sample_weight_description' => null,
    ]);
    echo "Updated SingleSuspectCase #7.\n";
}

// 3. Update MultipleSuspectCase if promoted
$multi = \App\Models\MultipleSuspectCase::where('case_number', 'CSS-2026-7295')->first();
if ($multi && \Schema::hasColumn($multi->getTable(), 'docket_data')) {
    $docketData = is_array($multi->docket_data) ? $multi->docket_data : (json_decode($multi->docket_data ?? '[]', true) ?: []);
    unset($docketData['lab_examination']);
    unset($docketData['lab_examinations_list']);
    $docketData['unit_progress'] = [
        'Crime Scene Support' => [
            'status'     => 'COMPLETED',
            'officer'    => $multi->submitting_officer ?: 'Samuel Atta',
            'updated_at' => date('Y-m-d H:i:s'),
        ],
        'Evidence Management' => [
            'status'     => 'PENDING',
            'officer'    => null,
            'updated_at' => null,
        ],
    ];
    $multi->docket_data = $docketData;
    $multi->save();
    echo "Successfully updated MultipleSuspectCase CSS-2026-7295 docket data.\n";
}

// 4. Verify ForensicCase wrapper output for CSS-2026-7295
$fc = \App\Models\ForensicCase::find('CSS-2026-7295');
if ($fc) {
    echo "Case Number: " . $fc->case_number . "\n";
    echo "isDrugCase: " . ($fc->isDrugCase() ? 'YES' : 'NO') . "\n";
    echo "Workflow Units: " . json_encode($fc->getWorkflowUnits()) . "\n";
}
