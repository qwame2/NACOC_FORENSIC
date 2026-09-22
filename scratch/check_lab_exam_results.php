<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;

$cases = ForensicCase::allCases();
echo "Total cases found: " . count($cases) . "\n";

foreach ($cases as $c) {
    $dbLab = LaboratoryExaminationResult::where(function($q) use ($c) {
        $q->where('single_suspect_case_id', $c->id)
          ->orWhere('multiple_suspect_case_id', $c->id);
    })->first();

    echo "Case #{$c->case_number} (ID: {$c->id}, Suspect: {$c->suspect_name}):\n";
    echo "  - Case table sample_weight_taken: " . var_export($c->sample_weight_taken, true) . "\n";
    echo "  - Case table sample_weight_reserved: " . var_export($c->sample_weight_reserved, true) . "\n";
    echo "  - Case table confirmatory_test_result: " . var_export($c->confirmatory_test_result, true) . "\n";
    if ($dbLab) {
        echo "  - DB Lab Exam ID: {$dbLab->id}\n";
        echo "  - DB Lab sample_weight_taken: " . var_export($dbLab->sample_weight_taken, true) . "\n";
        echo "  - DB Lab sample_weight_reserved: " . var_export($dbLab->sample_weight_reserved, true) . "\n";
        echo "  - DB Lab confirmatory_result: " . var_export($dbLab->confirmatory_result, true) . "\n";
    } else {
        echo "  - DB Lab Exam: NONE RECORDED\n";
    }

    $dData = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '{}', true) ?: []);
    if (!empty($dData['lab_examination'])) {
        echo "  - Docket Data lab_examination present\n";
        if (isset($dData['lab_examination']['sample_weight'])) {
            echo "    * sample_weight: " . json_encode($dData['lab_examination']['sample_weight']) . "\n";
        }
    }
    echo "-----------------------------------------------------\n";
}
