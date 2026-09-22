<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach(App\Models\ForensicCase::take(10)->get() as $c) {
    echo "=== Case ID: {$c->id} | Number: {$c->case_number} | Main Suspect: {$c->suspect_name} ===\n";
    $d = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
    if (!empty($d['suspects'])) {
        echo "SUSPECTS:\n";
        print_r($d['suspects']);
    }
    if (!empty($d['seizures'])) {
        echo "SEIZURES:\n";
        print_r($d['seizures']);
    }
    if (!empty($d['associate_exhibits'])) {
        echo "ASSOCIATE EXHIBITS:\n";
        print_r($d['associate_exhibits']);
    }
    if (!empty($d['lab_examination'])) {
        echo "LAB EXAM:\n";
        print_r($d['lab_examination']);
    }
    if (!empty($d['lab_examinations_list'])) {
        echo "LAB EXAMS LIST:\n";
        print_r($d['lab_examinations_list']);
    }
    $assocExams = App\Models\AssociateLabExamination::where('case_id', $c->id)->get();
    if ($assocExams->count()) {
        echo "ASSOCIATE LAB EXAMS (DB):\n";
        print_r($assocExams->toArray());
    }
    echo "\n------------------------------------------------------------\n";
}
