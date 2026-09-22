<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;

$caseRef = 'CSS-2026-9425';

echo "=== SINGLE SUSPECT CASE RECORD ===\n";
$single = SingleSuspectCase::where('case_number', $caseRef)->first();
if ($single) {
    echo "ID: {$single->id}, Title: {$single->title}, Suspect: {$single->suspect_name}, Status: {$single->status}\n";
} else {
    echo "None found.\n";
}

echo "\n=== MULTIPLE SUSPECT CASE RECORD ===\n";
$multi = MultipleSuspectCase::where('case_number', $caseRef)->first();
if ($multi) {
    echo "ID: {$multi->id}, Title: {$multi->title}, Status: {$multi->status}\n";
    echo "Docket Data Suspects in JSON:\n";
    $dData = is_array($multi->docket_data) ? $multi->docket_data : json_decode($multi->docket_data, true);
    print_r($dData['suspects'] ?? 'No suspects key in docket_data');
    
    echo "\nCaseSuspect models in DB:\n";
    $suspects = CaseSuspect::where('multiple_suspect_case_id', $multi->id)->get();
    foreach ($suspects as $s) {
        echo " - ID: {$s->id}, Number: {$s->suspect_number}, Name: {$s->suspect_name}, Role: '{$s->role}', AssocID: '{$s->associate_id}'\n";
    }

    echo "\nExhibits in DB:\n";
    $exhibits = Exhibit::where('multiple_suspect_case_id', $multi->id)->get();
    foreach ($exhibits as $ex) {
        echo " - ID: {$ex->id}, Exh#: {$ex->exhibit_number}, Name: {$ex->commodity_name}, SuspectID: {$ex->suspect_id}\n";
        if ($ex->suspect) {
            echo "   -> Suspect Relation: ID: {$ex->suspect->id}, Name: {$ex->suspect->suspect_name}, Role: {$ex->suspect->role}\n";
        } else {
            echo "   -> Suspect Relation: NULL\n";
        }
    }
} else {
    echo "None found.\n";
}

echo "\n=== WRAPPED FORENSIC CASE WRAPRECORD ===\n";
$fCase = ForensicCase::findByRef($caseRef);
if ($fCase) {
    echo "Class: " . get_class($fCase->underlyingRecord) . "\n";
    echo "Title: {$fCase->title}\n";
    $d = $fCase->docket_data;
    echo "Wrapped Seizures Array:\n";
    foreach ($d['seizures'] ?? [] as $sz) {
        echo " - Seizure: {$sz['seizure_name']}, Owner: '{$sz['associated_person']}', AssocID: '{$sz['associate_id']}', IsAssoc: " . ($sz['is_associate'] ? 'TRUE' : 'FALSE') . "\n";
    }
}
