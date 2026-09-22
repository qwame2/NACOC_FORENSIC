<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$case = \App\Models\ForensicCase::find(69);

$detArr = [
    ['substance' => 'Tramadol', 'percentage' => '100%'],
    ['substance' => 'Tramoo', 'percentage' => '50%'],
];

// 1. Update AssociateLabExamination
DB::table('associate_lab_examinations')
    ->where('case_id', 69)
    ->where('suspect_name', 'LIKE', '%Atta Budo%')
    ->update(['detected_substances' => json_encode($detArr)]);

// 2. Update LaboratoryExaminationResult
$attaSuspectId = DB::table('case_suspects')
    ->where('suspect_name', 'LIKE', '%Atta Budo%')
    ->value('id');

if ($attaSuspectId) {
    DB::table('laboratory_examination_results')
        ->where('suspect_id', $attaSuspectId)
        ->update(['detected_substances' => json_encode($detArr)]);
}

// 3. Update docket_data['associate_lab_examinations']['Atta Budo']
$dd = is_array($case->docket_data) ? $case->docket_data : [];
if (isset($dd['associate_lab_examinations']['Atta Budo'])) {
    $dd['associate_lab_examinations']['Atta Budo']['detected_substances'] = $detArr;
    $case->docket_data = $dd;
    $case->save();
}

echo "Atta Budo detected_substances updated successfully in DB.\n";
