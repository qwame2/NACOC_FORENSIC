<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cases = ['CSS-2026-7295', 'CSS-2026-8876'];
foreach ($cases as $cNum) {
    echo "====================================\n";
    echo "Testing Case: {$cNum}\n";
    $c = \App\Models\ForensicCase::find($cNum);
    if (!$c) {
        $single = \App\Models\SingleSuspectCase::where('case_number', $cNum)->first();
        if ($single) $c = \App\Models\ForensicCase::wrapRecord($single);
        $multi = \App\Models\MultipleSuspectCase::where('case_number', $cNum)->first();
        if ($multi) $c = \App\Models\ForensicCase::wrapRecord($multi);
    }
    if (!$c) {
        echo "Case {$cNum} not found!\n";
        continue;
    }

    echo "Case Title: " . $c->title . "\n";
    echo "Suspect Name: " . $c->suspect_name . "\n";
    echo "Exhibit Category: " . $c->exhibit_category . "\n";
    echo "Seizure Name: " . $c->seizure_name . "\n";

    echo "Exhibits in DB:\n";
    $dbExs = \App\Models\Exhibit::where('single_suspect_case_id', $c->id)->orWhere('multiple_suspect_case_id', $c->id)->get();
    foreach ($dbExs as $e) {
        echo "  - Exh ID: {$e->id} | Cat: {$e->exhibit_category} | Name: {$e->commodity_name} | SuspectID: {$e->suspect_id} | AssocPerson: {$e->associated_person} | VehMake: {$e->veh_make} | VehReg: {$e->veh_reg_no}\n";
    }

    $suspects = $c->getSuspectsList();
    echo "Suspects count: " . count($suspects) . "\n";
    foreach ($suspects as $sp) {
        $spName = $sp['suspect_name'] ?? 'N/A';
        $spId = $sp['id'] ?? null;
        $spAssocId = $sp['associate_id'] ?? null;
        $spRole = $sp['association_role'] ?? ($sp['role'] ?? 'Primary Suspect');

        $exs = $c->getExhibitsForSuspect($spName, $spId, $spAssocId, $spRole);
        echo "Exhibits for suspect '{$spName}': " . count($exs) . "\n";
        print_r($exs);
    }
}
