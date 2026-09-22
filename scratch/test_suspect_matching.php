<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first() ?: App\Models\ForensicCase::first();
if (!$case) { echo "No cases found in DB\n"; exit; }
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);

// Extract suspects list
$rawSuspects = $docketData['suspects'] ?? [];
if (empty($rawSuspects) && !empty($case->suspect_name)) {
    $names = array_map('trim', explode(',', $case->suspect_name));
    foreach ($names as $idx => $n) {
        $rawSuspects[] = [
            'suspect_name'     => $n,
            'association_role' => $idx === 0 ? 'Primary Suspect' : 'Co-Suspect',
        ];
    }
}

$seizures = $docketData['seizures'] ?? [];
$associateExhibits = $docketData['associate_exhibits'] ?? [];
$allExhibits = array_merge($seizures, $associateExhibits);

$labExamList = $docketData['lab_examinations_list'] ?? [];
$mainLabExam = $docketData['lab_examination'] ?? [];
$dbAssociateLabExams = App\Models\AssociateLabExamination::where('case_id', $case->id)->get();

$suspectsList = [];

foreach ($rawSuspects as $idx => $s) {
    $sName = trim($s['suspect_name'] ?? ($s['name'] ?? ''));
    if (empty($sName) && $idx === 0) {
        $sName = $case->suspect_name ?? 'Primary Suspect';
    }
    
    $isAssoc = !empty($s['is_associate']) || str_contains(strtolower($s['association_role'] ?? ''), 'associate');
    $isCo = !$isAssoc && ($idx > 0 || str_contains(strtolower($s['association_role'] ?? ''), 'co-'));
    $role = $isAssoc ? 'Associate Suspect' : ($isCo ? 'Co-Suspect' : 'Primary Suspect');

    // 1. Suspect Personal Details
    $suspectData = [
        'index'               => $idx,
        'suspect_name'        => $sName,
        'suspect_alias'       => $s['suspect_alias'] ?? ($s['alias'] ?? ($idx === 0 ? $case->suspect_alias : '')),
        'ghana_card'          => $s['ghana_card'] ?? ($idx === 0 ? $case->ghana_card : ''),
        'gender'              => $s['gender'] ?? ($idx === 0 ? $case->gender : 'Male'),
        'age'                 => $s['age'] ?? ($idx === 0 ? $case->age : ''),
        'phone'               => $s['phone'] ?? ($idx === 0 ? $case->phone : ''),
        'address'             => $s['address'] ?? ($idx === 0 ? $case->address : ''),
        'offense_description' => $s['offense'] ?? ($s['offense_description'] ?? ($idx === 0 ? $case->offense_description : '')),
        'arrest_date'         => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($idx === 0 ? $case->arrest_date : '')),
        'arrest_time'         => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($idx === 0 ? $case->arrest_time : '')),
        'raid_location'       => $s['raid_location'] ?? ($s['location_of_arrest'] ?? ($idx === 0 ? $case->raid_location : '')),
        'holding_facility'    => $s['holding_facility'] ?? ($idx === 0 ? $case->holding_facility : ''),
        'dob'                 => $s['dob'] ?? ($idx === 0 ? ($case->dob ?? '') : ''),
        'height'              => $s['height'] ?? ($idx === 0 ? ($case->height ?? '') : ''),
        'height_unit'         => $s['height_unit'] ?? ($idx === 0 ? ($case->height_unit ?? 'CM') : 'CM'),
        'eye_color'           => $s['eye_color'] ?? ($idx === 0 ? ($case->eye_color ?? '') : ''),
        'nationality'         => $s['nationality'] ?? ($idx === 0 ? ($case->nationality ?? '') : ''),
        'place_of_birth'      => $s['place_of_birth'] ?? ($idx === 0 ? ($case->place_of_birth ?? '') : ''),
        'marital_status'      => $s['marital_status'] ?? ($idx === 0 ? ($case->marital_status ?? '') : ''),
        'children_count'      => $s['children_count'] ?? ($idx === 0 ? ($case->children_count ?? '') : ''),
        'mugshots'            => $s['mugshots'] ?? ($idx === 0 ? [
            'front'         => $case->mugshot_front ?: ($docketData['mugshots']['front'] ?? ''),
            'left-profile'  => $case->mugshot_left ?: ($docketData['mugshots']['left-profile'] ?? ($docketData['mugshots']['left'] ?? '')),
            'right-profile' => $case->mugshot_right ?: ($docketData['mugshots']['right-profile'] ?? ($docketData['mugshots']['right'] ?? '')),
        ] : []),
        'role'                => $role,
        'is_associate'        => $isAssoc,
        'associate_id'        => $s['associate_id'] ?? ($s['suspect_id'] ?? ''),
    ];

    // 2. Find Linked Exhibit/Seizure
    $matchedExhibit = null;
    foreach ($allExhibits as $ex) {
        $owner = trim($ex['associated_person'] ?? ($ex['suspect_name'] ?? ($ex['owner_name'] ?? '')));
        if (!empty($owner) && !empty($sName)) {
            // Check exact or partial match
            if (str_contains(strtolower($owner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($owner))) {
                $matchedExhibit = $ex;
                break;
            }
        }
    }
    // Fallback: if no owner match, Primary & Co-Suspect get 1st exhibit, Associate gets 2nd exhibit
    if (!$matchedExhibit && !empty($allExhibits)) {
        if ($isAssoc && isset($allExhibits[1])) {
            $matchedExhibit = $allExhibits[1];
        } else {
            $matchedExhibit = $allExhibits[0];
        }
    }

    $exName = $matchedExhibit['seizure_name_drug'] ?? ($matchedExhibit['seizure_name'] ?? ($matchedExhibit['name'] ?? ($idx === 0 ? $case->seizure_name_drug : '')));
    $exCat  = $matchedExhibit['category'] ?? ($matchedExhibit['exhibit_category'] ?? ($idx === 0 ? $case->exhibit_category : ''));
    $exQty  = $matchedExhibit['seizure_quantity'] ?? ($matchedExhibit['quantity'] ?? ($matchedExhibit['units'] ?? ($idx === 0 ? $case->seizure_quantity : '')));
    $exWgt  = $matchedExhibit['seizure_weight'] ?? ($matchedExhibit['weight'] ?? ($idx === 0 ? $case->seizure_weight : ''));
    $exUnit = $matchedExhibit['seizure_unit'] ?? ($matchedExhibit['unit'] ?? ($idx === 0 ? $case->seizure_unit : ''));
    $exPkg  = $matchedExhibit['seizure_packaging'] ?? ($matchedExhibit['packaging'] ?? ($idx === 0 ? $case->seizure_packaging : ''));
    $exRsn  = $matchedExhibit['seizure_reason'] ?? ($matchedExhibit['reason'] ?? ($idx === 0 ? $case->seizure_reason : ''));
    $exPres = $matchedExhibit['presumptive_result'] ?? ($idx === 0 ? $case->presumptive_test_result : '');

    $suspectData['exhibit'] = [
        'seizure_name_drug' => $exName,
        'exhibit_category'  => $exCat,
        'seizure_quantity'  => $exQty,
        'seizure_weight'    => $exWgt,
        'seizure_unit'      => $exUnit,
        'seizure_packaging' => $exPkg,
        'seizure_reason'    => $exRsn,
        'presumptive_result'=> $exPres,
    ];

    // 3. Find Linked Lab Examination
    $matchedLab = null;

    // Check DB AssociateLabExamination records
    foreach ($dbAssociateLabExams as $dbLab) {
        $dbOwner = trim($dbLab->suspect_name ?? '');
        if (!empty($dbOwner) && !empty($sName) && (str_contains(strtolower($dbOwner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($dbOwner)))) {
            $matchedLab = [
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
            break;
        }
    }

    // Check lab_examinations_list
    if (!$matchedLab && !empty($labExamList)) {
        foreach ($labExamList as $lExam) {
            $lOwner = trim($lExam['exhibit_owner_name'] ?? '');
            if (!empty($lOwner) && !empty($sName) && (str_contains(strtolower($lOwner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($lOwner)))) {
                $sw = $lExam['sample_weight'] ?? [];
                $matchedLab = [
                    'laboratory_number'         => $lExam['laboratory_number'] ?? '',
                    'presumptive_result'        => $lExam['presumptive_result'] ?? '',
                    'confirmatory_test_result'  => $lExam['confirmatory_result'] ?? '',
                    'sample_weight_description' => $sw['description'] ?? ($lExam['substance_identification'] ?? ''),
                    'sample_weight_units'       => $sw['units_packaging'] ?? '',
                    'sample_weight_gross'       => $sw['gross_weight'] ?? '',
                    'sample_weight_taken'       => $sw['sample_taken'] ?? '',
                    'sample_weight_reserved'    => $sw['reserved_samples'] ?? '',
                    'substance_identification'  => $lExam['substance_identification'] ?? '',
                    'detected_substances'       => $lExam['detected_substances'] ?? [],
                    'test_methods'              => $lExam['test_methods'] ?? '',
                    'specifications'            => $lExam['specifications'] ?? '',
                    'findings'                  => $lExam['findings'] ?? '',
                ];
                break;
            }
        }
    }

    // Check suspect's internal fields
    if (!$matchedLab && !empty($s['has_saved_lab'])) {
        $matchedLab = [
            'laboratory_number'         => $s['laboratory_number'] ?? ($mainLabExam['laboratory_number'] ?? ''),
            'presumptive_result'        => $s['presumptive_result'] ?? '',
            'confirmatory_test_result'  => $s['confirmatory_result'] ?? 'YES',
            'sample_weight_description' => $s['sample_description'] ?? ($s['substance_identification'] ?? ''),
            'sample_weight_units'       => $s['sample_units'] ?? '',
            'sample_weight_gross'       => $s['sample_gross'] ?? '',
            'sample_weight_taken'       => $s['sample_taken'] ?? '',
            'sample_weight_reserved'    => $s['sample_reserved'] ?? '',
            'substance_identification'  => $s['substance_identification'] ?? '',
            'detected_substances'       => $s['detected_substances'] ?? [],
            'test_methods'              => $s['test_methods'] ?? '',
            'specifications'            => $s['specifications'] ?? '',
            'findings'                  => $s['findings'] ?? '',
        ];
    }

    // Fallback for Primary / Co-Suspect: main docketData['lab_examination'] or root $case fields
    if (!$matchedLab) {
        if (!$isAssoc && !empty($mainLabExam)) {
            $sw = $mainLabExam['sample_weight'] ?? [];
            $matchedLab = [
                'laboratory_number'         => $mainLabExam['laboratory_number'] ?? ($case->laboratory_number ?? ''),
                'presumptive_result'        => $mainLabExam['presumptive_result'] ?? ($case->presumptive_test_result ?? ''),
                'confirmatory_test_result'  => $mainLabExam['confirmatory_result'] ?? ($case->confirmatory_test_result ?? 'YES'),
                'sample_weight_description' => $sw['description'] ?? ($mainLabExam['substance_identification'] ?? ($case->sample_weight_description ?? '')),
                'sample_weight_units'       => $sw['units_packaging'] ?? ($case->sample_weight_units ?? ''),
                'sample_weight_gross'       => $sw['gross_weight'] ?? ($case->sample_weight_gross ?? ''),
                'sample_weight_taken'       => $sw['sample_taken'] ?? ($case->sample_weight_taken ?? ''),
                'sample_weight_reserved'    => $sw['reserved_samples'] ?? ($case->sample_weight_reserved ?? ''),
                'substance_identification'  => $mainLabExam['substance_identification'] ?? ($case->confirmatory_substance_identification ?? ''),
                'detected_substances'       => $mainLabExam['detected_substances'] ?? ($docketData['confirmatory_detected_substances'] ?? []),
                'test_methods'              => $mainLabExam['test_methods'] ?? ($case->confirmatory_test_methods ?? ''),
                'specifications'            => $mainLabExam['specifications'] ?? ($case->confirmatory_specifications ?? ''),
                'findings'                  => $mainLabExam['findings'] ?? ($case->laboratory_analytical_findings ?? ''),
            ];
        } else {
            $matchedLab = [
                'laboratory_number'         => $case->laboratory_number ?? '',
                'presumptive_result'        => $case->presumptive_test_result ?? '',
                'confirmatory_test_result'  => $case->confirmatory_test_result ?? 'YES',
                'sample_weight_description' => $case->sample_weight_description ?? '',
                'sample_weight_units'       => $case->sample_weight_units ?? '',
                'sample_weight_gross'       => $case->sample_weight_gross ?? '',
                'sample_weight_taken'       => $case->sample_weight_taken ?? '',
                'sample_weight_reserved'    => $case->sample_weight_reserved ?? '',
                'substance_identification'  => $case->confirmatory_substance_identification ?? '',
                'detected_substances'       => $docketData['confirmatory_detected_substances'] ?? [],
                'test_methods'              => $case->confirmatory_test_methods ?? '',
                'specifications'            => $case->confirmatory_specifications ?? '',
                'findings'                  => $case->laboratory_analytical_findings ?? '',
            ];
        }
    }

    $suspectData['lab'] = $matchedLab;
    $suspectsList[] = $suspectData;
}

print_r($suspectsList);
