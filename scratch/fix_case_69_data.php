<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = \App\Models\ForensicCase::find(69);

// Update forensic_cases columns
$case->seizure_name_drug = 'Heroin (Brown Sugar / Diacetylmorphine)';
$case->confirmatory_substance_identification = 'Heroin (Brown Sugar / Diacetylmorphine)';
$case->sample_weight_description = 'Heroin (Brown Sugar / Diacetylmorphine)';

// Update docket_data['lab_examination']
$dd = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

if (isset($dd['lab_examination'])) {
    $dd['lab_examination']['substance_identification'] = 'Heroin (Brown Sugar / Diacetylmorphine)';
    $dd['lab_examination']['exhibit_owner_name'] = 'Eric Addo, Linda Atta';
    if (isset($dd['lab_examination']['sample_weight'])) {
        $dd['lab_examination']['sample_weight']['description'] = 'Heroin (Brown Sugar / Diacetylmorphine)';
        $dd['lab_examination']['sample_weight']['gross_weight'] = '50 Kilograms (kg)';
        $dd['lab_examination']['sample_weight']['sample_taken'] = '30';
        $dd['lab_examination']['sample_weight']['reserved_samples'] = '20 kg';
    }
}

$case->docket_data = $dd;
$case->save();

echo "Case 69 updated successfully.\n";
