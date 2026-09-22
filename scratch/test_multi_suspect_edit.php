<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = \App\Models\ForensicCase::find(69);
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);

$rawSuspects = $docketData['suspects'] ?? [];
$seizures = $docketData['seizures'] ?? [];
$associateExhibits = $docketData['associate_exhibits'] ?? [];
$allExhibits = array_merge($seizures, $associateExhibits);
$labExamList = $docketData['lab_examinations_list'] ?? [];
$mainLabExam = $docketData['lab_examination'] ?? [];
$dbAssociateLabExams = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();

// Build unique Exhibits List
$exhibitsList = [];
foreach ($allExhibits as $exIdx => $ex) {
    $exName = $ex['seizure_name_drug'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ($exIdx === 0 ? ($case->seizure_name_drug ?: $case->seizure_name) : '')));
    $exhibitsList[] = [
        'exhibit_index'      => $exIdx,
        'associated_person'  => $ex['associated_person'] ?? ($ex['suspect_name'] ?? ($ex['owner_name'] ?? '')),
        'seizure_name_drug'  => $exName,
        'exhibit_category'   => $ex['category'] ?? ($ex['exhibit_category'] ?? ($exIdx === 0 ? $case->exhibit_category : '')),
        'seizure_quantity'   => $ex['seizure_quantity'] ?? ($ex['quantity'] ?? ($ex['units'] ?? ($exIdx === 0 ? $case->seizure_quantity : ''))),
        'seizure_weight'     => $ex['seizure_weight'] ?? ($ex['weight'] ?? ($exIdx === 0 ? $case->seizure_weight : '')),
        'seizure_unit'       => $ex['seizure_unit'] ?? ($ex['unit'] ?? ($exIdx === 0 ? $case->seizure_unit : '')),
        'seizure_packaging'  => $ex['seizure_packaging'] ?? ($ex['packaging'] ?? ($exIdx === 0 ? $case->seizure_packaging : '')),
        'seizure_reason'     => $ex['seizure_reason'] ?? ($ex['reason'] ?? ($exIdx === 0 ? $case->seizure_reason : '')),
        'presumptive_result' => $ex['presumptive_result'] ?? ($exIdx === 0 ? $case->presumptive_test_result : ''),
    ];
}

// Build unique Labs List
$labsList = [];
if (!$dbAssociateLabExams->isEmpty()) {
    foreach ($dbAssociateLabExams as $dbIdx => $dbLab) {
        $labsList[] = [
            'lab_index'                 => count($labsList),
            'db_id'                     => $dbLab->id,
            'exhibit_owner_name'        => $dbLab->suspect_name,
            'laboratory_number'         => $dbLab->laboratory_number,
            'presumptive_result'        => $dbLab->presumptive_result,
            'confirmatory_test_result'  => $dbLab->confirmatory_result,
            'sample_weight_description' => $dbLab->sample_weight_description,
            'sample_weight_units'       => $dbLab->sample_weight_units,
            'sample_weight_gross'       => $dbLab->sample_weight_gross,
            'sample_weight_taken'       => $dbLab->sample_weight_taken,
            'sample_weight_reserved'    => $dbLab->sample_weight_reserved,
            'substance_identification'  => $dbLab->substance_identification,
            'detected_substances'       => $dbLab->detected_substances ?? [],
            'test_methods'              => $dbLab->test_methods,
            'specifications'            => $dbLab->specifications,
            'findings'                  => $dbLab->findings,
        ];
    }
}

$suspectsList = [];
foreach ($rawSuspects as $idx => $s) {
    $sName = trim($s['suspect_name'] ?? ($s['name'] ?? ''));
    if (empty($sName) && $idx === 0) {
        $sName = $case->suspect_name ?? 'Primary Suspect';
    }
    if (str_contains($sName, ',') && count($rawSuspects) > 1) {
        $sName = trim(explode(',', $sName)[0]);
    }

    $isAssoc = !empty($s['is_associate']) || str_contains(strtolower($s['association_role'] ?? ''), 'associate');
    $isCo = !$isAssoc && ($idx > 0 || str_contains(strtolower($s['association_role'] ?? ''), 'co-'));
    $role = $isAssoc ? 'Associate Suspect' : ($isCo ? 'Co-Suspect' : 'Primary Suspect');

    // Linked Exhibit Index
    $matchedExIdx = null;
    foreach ($exhibitsList as $eIdx => $ex) {
        $owner = trim($ex['associated_person'] ?? '');
        if (!empty($owner) && !empty($sName)) {
            if (str_contains(strtolower($owner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($owner))) {
                $matchedExIdx = $eIdx;
                break;
            }
        }
    }
    if ($matchedExIdx === null) {
        $matchedExIdx = ($isAssoc && isset($exhibitsList[1])) ? 1 : 0;
    }

    // Linked Lab Examination Index
    $matchedLabIdx = null;
    foreach ($labsList as $lIdx => $lItem) {
        $lOwner = trim($lItem['exhibit_owner_name'] ?? '');
        if (!empty($lOwner) && !empty($sName)) {
            if (str_contains(strtolower($lOwner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($lOwner))) {
                $matchedLabIdx = $lIdx;
                break;
            }
        }
    }
    if ($matchedLabIdx === null) {
        $matchedLabIdx = (isset($labsList[$matchedExIdx])) ? $matchedExIdx : 0;
    }

    $suspectsList[] = [
        'index'         => $idx,
        'suspect_name'  => $sName,
        'alias'         => $s['suspect_alias'] ?? ($s['alias'] ?? ''),
        'role'          => $role,
        'gender'        => $s['gender'] ?? ($idx === 0 ? ($case->gender ?? 'Male') : 'Male'),
        'dob'           => $s['dob'] ?? '',
        'exhibit_index' => $matchedExIdx,
        'exhibit_name'  => $exhibitsList[$matchedExIdx]['seizure_name_drug'],
        'lab_index'     => $matchedLabIdx,
        'lab_desc'      => $labsList[$matchedLabIdx]['sample_weight_description'] ?? 'n/a',
    ];
}

echo "=== EXTRACTED SUSPECTS ===\n";
echo json_encode($suspectsList, JSON_PRETTY_PRINT) . "\n";

echo "=== UNIQUE EXHIBITS ===\n";
echo json_encode($exhibitsList, JSON_PRETTY_PRINT) . "\n";

echo "=== UNIQUE LAB EXAMS ===\n";
echo json_encode($labsList, JSON_PRETTY_PRINT) . "\n";
