@php
    $authUser = auth()->user();
    $isHof = ($authUser && $authUser->is_head && (
        $authUser->unit === 'Forensic Directorate' ||
        $authUser->unit === 'Forensic Head' ||
        $authUser->unit === 'Directorate Head' ||
        str_contains($authUser->unit ?? '', 'Forensic')
    )) || request()->is('Forensic_Head*') || (session('user_role') === 'HOF');

    $editUserUnit = strtolower(trim($authUser->unit ?? ''));
    $editIsHead = !empty($authUser->is_head);
    $canEditCrimeScene = $editIsHead || str_contains($editUserUnit, 'crime') || str_contains($editUserUnit, 'scene') || str_contains($editUserUnit, 'support') || empty($editUserUnit);
    $canEditLab = $editIsHead || str_contains($editUserUnit, 'lab') || str_contains($editUserUnit, 'chemistry') || str_contains($editUserUnit, 'narcotics');

    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    $isForensicHeadContext = request()->is('Forensic_Head*') || session('user_role') === 'HOF';

    // MULTI-SUSPECT & EXHIBIT EXTRACTION & MATCHING
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
    if (empty($rawSuspects)) {
        $rawSuspects = [['suspect_name' => $case->suspect_name ?: 'Primary Suspect', 'association_role' => 'Primary Suspect']];
    }

    $seizures = $docketData['seizures'] ?? [];
    $associateExhibits = $docketData['associate_exhibits'] ?? [];
    $allExhibits = array_merge($seizures, $associateExhibits);

    $labExamList = $docketData['lab_examinations_list'] ?? [];
    $mainLabExam = $docketData['lab_examination'] ?? [];
    $dbAssociateLabExams = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();

    // Extract case photos array safely
    $casePhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));
    if (!is_array($casePhotos)) $casePhotos = [];

    // Build unique Exhibits List
    $exhibitsList = [];
    foreach ($allExhibits as $exIdx => $ex) {
        $exName = $ex['seizure_name_drug'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ($exIdx === 0 ? ($case->seizure_name_drug ?: $case->seizure_name) : '')));
        $exPhotos = $ex['evidence_photos'] ?? ($ex['photos'] ?? ($exIdx === 0 ? $casePhotos : []));
        if (is_string($exPhotos)) {
            $exPhotos = json_decode($exPhotos, true) ?: [];
        }
        if (!is_array($exPhotos)) $exPhotos = [];

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
            'ammunition_details' => $ex['ammunition_details'] ?? ($exIdx === 0 ? $case->ammunition_details : null),
            'evidence_photos'    => array_values($exPhotos),
        ];
    }
    if (empty($exhibitsList)) {
        $exhibitsList[] = [
            'exhibit_index'      => 0,
            'associated_person'  => $case->suspect_name ?? '',
            'seizure_name_drug'  => $case->seizure_name_drug ?: $case->seizure_name,
            'exhibit_category'   => $case->exhibit_category,
            'seizure_quantity'   => $case->seizure_quantity,
            'seizure_weight'     => $case->seizure_weight,
            'seizure_unit'       => $case->seizure_unit,
            'seizure_packaging'  => $case->seizure_packaging,
            'seizure_reason'     => $case->seizure_reason,
            'presumptive_result' => $case->presumptive_test_result,
            'ammunition_details' => $case->ammunition_details,
            'evidence_photos'    => array_values($casePhotos),
        ];
    }

    if (!function_exists('parseEditDetectedSubstances')) {
        function parseEditDetectedSubstances($raw) {
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }
            if (!is_array($raw)) return [];
            $result = [];
            foreach ($raw as $ds) {
                if (is_array($ds)) {
                    $name = $ds['name'] ?? ($ds['substance'] ?? ($ds['compound'] ?? ''));
                    $pct  = $ds['percentage'] ?? ($ds['purity'] ?? ($ds['pct'] ?? ''));
                    $pctClean = rtrim(trim($pct), '%');
                    if ($name !== '' || $pctClean !== '') {
                        $result[] = [
                            'name'       => $name,
                            'substance'  => $name,
                            'percentage' => $pctClean,
                            'purity'     => $pctClean,
                        ];
                    }
                }
            }
            return $result;
        }
    }

    // Build unique Lab Examinations List
    $labsList = [];
    $dbPrimaryLabRecord = \App\Models\LaboratoryExaminationResult::where(function($q) use ($case) {
        $q->where('single_suspect_case_id', $case->id)
          ->orWhere('multiple_suspect_case_id', $case->id);
    })->latest('id')->first();

    // Primary Suspect Lab Exam (Index 0)
    $swPrimary = $mainLabExam['sample_weight'] ?? [];
    $labsList[] = [
        'lab_index'                 => 0,
        'db_id'                     => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->id : null,
        'exhibit_owner_name'        => $dbPrimaryLabRecord ? ($dbPrimaryLabRecord->suspect->suspect_name ?? ($case->suspect_name ?? 'Primary Suspect')) : ($mainLabExam['exhibit_owner_name'] ?? ($case->suspect_name ?? 'Primary Suspect')),
        'laboratory_number'         => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->laboratory_number : ($mainLabExam['laboratory_number'] ?? ($case->laboratory_number ?? '')),
        'presumptive_result'        => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->presumptive_result : ($mainLabExam['presumptive_result'] ?? ($case->presumptive_test_result ?? '')),
        'confirmatory_test_result'  => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->confirmatory_result : ($mainLabExam['confirmatory_result'] ?? ($case->confirmatory_test_result ?? 'YES')),
        'sample_weight_description' => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->sample_weight_description : ($swPrimary['description'] ?? ($mainLabExam['substance_identification'] ?? ($case->sample_weight_description ?? ''))),
        'sample_weight_units'       => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->sample_weight_units : ($swPrimary['units_packaging'] ?? ($case->sample_weight_units ?? '')),
        'sample_weight_gross'       => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->sample_weight_gross : ($swPrimary['gross_weight'] ?? ($case->sample_weight_gross ?? '')),
        'sample_weight_taken'       => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->sample_weight_taken : ($swPrimary['sample_taken'] ?? ($case->sample_weight_taken ?? '')),
        'sample_weight_reserved'    => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->sample_weight_reserved : ($swPrimary['reserved_samples'] ?? ($case->sample_weight_reserved ?? '')),
        'substance_identification'  => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->substance_identification : ($mainLabExam['substance_identification'] ?? ($case->confirmatory_substance_identification ?? '')),
        'detected_substances'       => parseEditDetectedSubstances($dbPrimaryLabRecord ? $dbPrimaryLabRecord->detected_substances : ($mainLabExam['detected_substances'] ?? ($docketData['confirmatory_detected_substances'] ?? ($case->detected_substances ?? [])))),
        'test_methods'              => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->test_methods : ($mainLabExam['test_methods'] ?? ($case->confirmatory_test_methods ?? '')),
        'specifications'            => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->specifications : ($mainLabExam['specifications'] ?? ($case->confirmatory_specifications ?? '')),
        'findings'                  => $dbPrimaryLabRecord ? $dbPrimaryLabRecord->findings : ($mainLabExam['findings'] ?? ($case->laboratory_analytical_findings ?? '')),
    ];

    // Associate Suspects Lab Exams (Index >= 1)
    if (!$dbAssociateLabExams->isEmpty()) {
        foreach ($dbAssociateLabExams as $dbLab) {
            $assocName = strtolower(trim($dbLab->suspect_name ?? ''));
            if ($assocName === 'primary suspect' || $assocName === strtolower(trim($case->suspect_name ?? ''))) {
                continue; // Do NOT add primary suspect to associate lab exams list
            }
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
                'detected_substances'       => parseEditDetectedSubstances($dbLab->detected_substances ?? []),
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

        $roleRaw = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
        $isCo = str_contains($roleRaw, 'co-') || ($idx === 1 && !str_contains($roleRaw, 'associate'));
        $isAssoc = str_contains($roleRaw, 'associate') || str_contains($roleRaw, 'accomplice') || ($idx > 1 && !$isCo);
        $role = $isCo ? 'Co-Suspect' : ($isAssoc ? 'Associate Suspect' : 'Primary Suspect');

        $suspectData = [
            'index'               => $idx,
            'suspect_name'        => $sName,
            'suspect_alias'       => $s['suspect_alias'] ?? ($s['alias'] ?? ($idx === 0 ? ($case->suspect_alias ?? '') : '')),
            'ghana_card'          => $s['ghana_card'] ?? ($idx === 0 ? ($case->ghana_card ?? '') : ''),
            'gender'              => $s['gender'] ?? ($idx === 0 ? ($case->gender ?? 'Male') : 'Male'),
            'age'                 => $s['age'] ?? ($idx === 0 ? ($case->age ?? '') : ''),
            'phone'               => $s['phone'] ?? ($idx === 0 ? ($case->phone ?? '') : ''),
            'address'             => $s['address'] ?? ($idx === 0 ? ($case->address ?? '') : ''),
            'offense_description' => $s['offense'] ?? ($s['offense_description'] ?? ($idx === 0 ? ($case->offense_description ?? '') : '')),
            'arrest_date'         => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($idx === 0 ? ($case->arrest_date ?? '') : '')),
            'arrest_time'         => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($idx === 0 ? ($case->arrest_time ?? '') : '')),
            'raid_location'       => $s['raid_location'] ?? ($s['location_of_arrest'] ?? ($idx === 0 ? ($case->raid_location ?? '') : '')),
            'holding_facility'    => $s['holding_facility'] ?? ($idx === 0 ? ($case->holding_facility ?? '') : ''),
            'dob'                 => $s['dob'] ?? ($idx === 0 ? ($case->dob ?? '') : ''),
            'height'              => $s['height'] ?? ($idx === 0 ? ($case->height ?? '') : ''),
            'height_unit'         => $s['height_unit'] ?? ($idx === 0 ? ($case->height_unit ?? 'CM') : 'CM'),
            'eye_color'           => $s['eye_color'] ?? ($idx === 0 ? ($case->eye_color ?? '') : ''),
            'nationality'         => $s['nationality'] ?? ($idx === 0 ? ($case->nationality ?? '') : ''),
            'place_of_birth'      => $s['place_of_birth'] ?? ($s['pob'] ?? ($idx === 0 ? ($case->place_of_birth ?: ($case->pob ?: ($docketData['place_of_birth'] ?? ($docketData['pob'] ?? '')))) : '')),
            'pob'                 => $s['place_of_birth'] ?? ($s['pob'] ?? ($idx === 0 ? ($case->place_of_birth ?: ($case->pob ?: ($docketData['place_of_birth'] ?? ($docketData['pob'] ?? '')))) : '')),
            'hometown'            => $s['hometown'] ?? ($idx === 0 ? ($case->hometown ?: ($docketData['hometown'] ?? '')) : ''),
            'marital_status'      => $s['marital_status'] ?? ($s['marital'] ?? ($s['relationship_status'] ?? ($idx === 0 ? ($case->marital_status ?: ($docketData['marital_status'] ?? '')) : ''))),
            'children_count'      => $s['children_count'] ?? ($s['children'] ?? ($s['kids'] ?? ($idx === 0 ? ($case->children_count ?: ($docketData['children_count'] ?? ($docketData['dependents'] ?? ''))) : ''))),
            'has_children'        => !empty($s['has_children']) || (!empty($s['children_count']) && $s['children_count'] !== '0'),
            'spouse_name'         => $s['spouse_name'] ?? ($s['partner_name'] ?? ($idx === 0 ? ($case->spouse_name ?: ($case->partner_name ?: ($docketData['spouse_name'] ?? ($docketData['partner_name'] ?? '')))) : '')),
            'spouse_phone'        => $s['spouse_phone'] ?? ($s['partner_phone'] ?? ($idx === 0 ? ($case->spouse_phone ?: ($case->partner_phone ?: ($docketData['spouse_phone'] ?? ($docketData['partner_phone'] ?? '')))) : '')),
            'spouse_info'         => $s['spouse_info'] ?? ($s['partner_info'] ?? ($idx === 0 ? ($case->spouse_info ?: ($case->partner_info ?: ($docketData['spouse_info'] ?? ($docketData['partner_info'] ?? '')))) : '')),
            'spouse_summary'      => $s['spouse_summary'] ?? ($idx === 0 ? ($case->spouse_summary ?: ($docketData['spouse_summary'] ?? '')) : ''),
            'dependents'          => $s['dependents'] ?? ($idx === 0 ? ($case->dependents ?: ($docketData['dependents'] ?? '')) : ''),
            'mugshots'            => [
                'front'         => isset($s['mugshots']['front']) ? $s['mugshots']['front'] : (isset($s['mugshots']['front-profile']) ? $s['mugshots']['front-profile'] : (isset($s['mugshot_front']) ? $s['mugshot_front'] : ($idx === 0 ? ($case->mugshot_front ?? '') : ''))),
                'left-profile'  => isset($s['mugshots']['left-profile']) ? $s['mugshots']['left-profile'] : (isset($s['mugshots']['left']) ? $s['mugshots']['left'] : (isset($s['mugshot_left']) ? $s['mugshot_left'] : ($idx === 0 ? ($case->mugshot_left ?? '') : ''))),
                'right-profile' => isset($s['mugshots']['right-profile']) ? $s['mugshots']['right-profile'] : (isset($s['mugshots']['right']) ? $s['mugshots']['right'] : (isset($s['mugshot_right']) ? $s['mugshot_right'] : ($idx === 0 ? ($case->mugshot_right ?? '') : ''))),
            ],
            'role'                => $role,
            'is_associate'        => $isAssoc,
            'associate_id'        => $s['associate_id'] ?? ($s['suspect_id'] ?? ''),
        ];

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
                if (str_contains(strtolower($lOwner), strtolower($sName)) || str_contains(strtolower($sName), strtolower($owner ?? ''))) {
                    $matchedLabIdx = $lIdx;
                    break;
                }
            }
        }
        if ($matchedLabIdx === null) {
            $matchedLabIdx = (isset($labsList[$matchedExIdx])) ? $matchedExIdx : 0;
        }

        $suspectData['exhibit_index'] = $matchedExIdx;
        $suspectData['lab_index']     = $matchedLabIdx;
        $suspectData['exhibit']       = $exhibitsList[$matchedExIdx];
        $suspectData['lab']           = $labsList[$matchedLabIdx];

        $suspectsList[] = $suspectData;
    }

    $suspectObj = $suspectsList[0] ?? [];
    $mugshotFront = isset($suspectObj['mugshots']['front']) ? $suspectObj['mugshots']['front'] : ($case->mugshot_front ?? '');
    $mugshotLeft = isset($suspectObj['mugshots']['left-profile']) ? $suspectObj['mugshots']['left-profile'] : (isset($suspectObj['mugshots']['left']) ? $suspectObj['mugshots']['left'] : ($case->mugshot_left ?? ''));
    $mugshotRight = isset($suspectObj['mugshots']['right-profile']) ? $suspectObj['mugshots']['right-profile'] : (isset($suspectObj['mugshots']['right']) ? $suspectObj['mugshots']['right'] : ($case->mugshot_right ?? ''));

    $suspectDob = $suspectObj['dob'] ?? ($case->dob ?: ($docketData['dob'] ?? ''));
    $calcAgeFromDob = null;
    if (!empty($suspectDob)) {
        try {
            $calcAgeFromDob = \Carbon\Carbon::parse($suspectDob)->age;
        } catch (\Exception $e) {
            $calcAgeFromDob = null;
        }
    }
    $suspectAge = $calcAgeFromDob !== null ? $calcAgeFromDob : ($suspectObj['age'] ?? ($case->age ?: ($docketData['age'] ?? '')));
    $suspectHeight = $suspectObj['height'] ?? ($case->height ?? '');
    $suspectHeightUnit = $suspectObj['height_unit'] ?? ($case->height_unit ?? 'CM');
    $suspectEyeColor = $suspectObj['eye_color'] ?? ($case->eye_color ?? '');
    $suspectNationality = $suspectObj['nationality'] ?? ($case->nationality ?? '');
    $suspectPob = $suspectObj['place_of_birth'] ?? ($suspectObj['pob'] ?? ($case->place_of_birth ?: ($case->pob ?: ($docketData['place_of_birth'] ?? ($docketData['pob'] ?? '')))));
    $suspectMarital = $suspectObj['marital_status'] ?? ($case->marital_status ?: ($docketData['marital_status'] ?? ''));
    $suspectChildrenRaw = $suspectObj['children_count'] ?? ($case->children_count ?: ($docketData['children_count'] ?? ($docketData['dependents'] ?? '')));
    $suspectChildren = is_numeric($suspectChildrenRaw) ? $suspectChildrenRaw : preg_replace('/[^0-9]/', '', (string)$suspectChildrenRaw);
    $suspectSpouseName = $suspectObj['spouse_name'] ?? ($case->spouse_name ?: ($case->partner_name ?: ($docketData['spouse_name'] ?? ($docketData['partner_name'] ?? ''))));
    $suspectSpousePhone = $suspectObj['spouse_phone'] ?? ($case->spouse_phone ?: ($case->partner_phone ?: ($docketData['spouse_phone'] ?? ($docketData['partner_phone'] ?? ''))));
    $suspectSpouseInfo = $suspectObj['spouse_info'] ?? ($case->spouse_info ?: ($case->partner_info ?: ($docketData['spouse_info'] ?? ($docketData['partner_info'] ?? ''))));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Case File {{ $case->case_number }} — NACOC Forensic Directorate</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind / Vite -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #0f172a;
            color: #0f172a;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        /* Stylish Input Styling */
        .stylish-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }
        .stylish-input:hover:not([readonly]):not([disabled]) {
            border-color: #94a3b8;
        }
        .stylish-input:focus:not([readonly]):not([disabled]) {
            outline: none;
            border-color: #d97706;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12);
        }
        .stylish-input[readonly], .stylish-input[disabled] {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
            user-select: none;
        }

        /* Ultra-Nicer & Modern Dropdown Select Styling */
        .stylish-select {
            width: 100%;
            padding: 0.6875rem 2.75rem 0.6875rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0f172a;
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%23d97706'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.875rem center;
            background-size: 1.125rem 1.125rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04);
            cursor: pointer;
        }
        .stylish-select:hover:not([disabled]) {
            border-color: #f59e0b;
            background-color: #fffdfa;
            box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.12);
        }
        .stylish-select:focus:not([disabled]) {
            outline: none;
            border-color: #d97706;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.18), 0 2px 4px 0 rgba(0, 0, 0, 0.05);
        }
        .stylish-select[disabled] {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
            border-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
            opacity: 0.85;
        }
        .stylish-select option {
            font-weight: 600;
            color: #0f172a;
            background-color: #ffffff;
            padding: 0.5rem 0.75rem;
        }

        .stylish-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 0.375rem;
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04), 0 8px 10px -6px rgba(15, 23, 42, 0.02);
        }

        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col antialiased bg-slate-100">

<div class="flex h-screen overflow-hidden">

    <!-- LEFT SIDEBAR -->
    @if($isForensicHeadContext)
        @include('Forensic_Head.partials.sidebar')
    @else
        @include('Crime_Scene_Support.partials.sidebar')
    @endif

    <!-- MAIN WORKSPACE CONTAINER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-100">

        <!-- STICKY TOP TOOLBAR & ACTION BAR -->
        <header class="hero-banner text-white h-16 px-6 flex items-center justify-between shrink-0 z-30 shadow-lg">
            <div class="flex items-center gap-4 min-w-0">
                <a href="/cases/{{ $case->case_number }}/review" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold rounded-xl border border-slate-700 transition-all text-decoration-none shadow-sm">
                    <span>Back to Case Review</span>
                </a>
                <div class="h-5 w-px bg-slate-700/80 hidden sm:block"></div>
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono-code font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        {{ $case->case_number }}
                    </span>
                    <span class="text-xs font-mono-code font-bold text-slate-400 uppercase tracking-wider hidden lg:block">
                        NACOC FORENSIC DOSSIER EDITOR
                    </span>
                </div>
            </div>

            <!-- TOP RIGHT QUICK SAVE ACTIONS -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="/cases/{{ $case->case_number }}/review" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700 rounded-xl transition-all text-decoration-none">
                    Cancel
                </a>
                <button type="button" onclick="document.getElementById('editCaseForm').submit();" class="px-5 py-2 text-xs font-extrabold text-slate-950 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 rounded-xl shadow-md shadow-amber-500/20 border border-amber-400/50 transition-all cursor-pointer inline-flex items-center gap-2 active:scale-95">
                    <span>Save Changes</span>
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE WORKSPACE -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

            <!-- STYLISH HERO STRIP -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white rounded-2xl p-6 border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-xl font-black text-white tracking-tight">Edit Case Record &amp; Correct Information</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    ACTIVE DOCKET
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                Modify registered case docket metadata, suspect identification facts, exhibit manifests, or chemical laboratory findings with full audit logging.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATUS NOTIFICATIONS -->
            @if(session('status'))
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-900 rounded-xl text-xs font-bold flex items-center gap-3 shadow-xs">
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-900 rounded-xl text-xs font-bold flex items-center gap-3 shadow-xs">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- MAIN FORM CONTAINER CARD -->
            <div class="glass-card overflow-hidden">
                
                <!-- SUSPECT & LINKED EXHIBIT DOCKET SELECTOR BAR -->
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-4 sm:p-5 border-b border-slate-700/80 shadow-inner">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-amber-400 font-extrabold text-xs uppercase font-mono-code flex items-center gap-2">
                                Registered Suspects &amp; Linked Exhibit Dockets
                            </span>
                            <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2.5 py-0.5 rounded-full font-mono-code font-bold border border-amber-500/30">
                                {{ count($suspectsList) }} Suspect(s)
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-300 font-mono-code font-bold bg-slate-800/90 px-3 py-1 rounded-lg border border-slate-700">
                            Select a suspect to switch suspect details, exhibit manifest &amp; lab results across all tabs
                        </span>
                    </div>

                    <div class="flex items-center gap-3 overflow-x-auto pb-1" id="suspectSelectorContainer">
                        @foreach($suspectsList as $sIdx => $sItem)
                            <button type="button" 
                                onclick="switchSuspect({{ $sIdx }})" 
                                id="suspectSelectBtn_{{ $sIdx }}" 
                                class="suspect-select-btn px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer flex items-center gap-3 shrink-0 {{ $sIdx === 0 ? 'bg-amber-500/20 border-amber-500 text-white ring-2 ring-amber-500/30 shadow-md' : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:border-slate-500 hover:text-white' }}">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/30 text-amber-300 font-bold font-mono-code text-xs flex items-center justify-center border border-amber-500/40 shrink-0">
                                    {{ $sIdx + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-white truncate max-w-[160px]">{{ $sItem['suspect_name'] }}</span>
                                        <span class="text-[9px] px-1.5 py-0.2 rounded font-mono-code font-bold uppercase {{ $sItem['is_associate'] ? 'bg-purple-500/30 text-purple-200 border border-purple-500/40' : ($sIdx > 0 ? 'bg-blue-500/30 text-blue-200 border border-blue-500/40' : 'bg-emerald-500/30 text-emerald-200 border border-emerald-500/40') }}">
                                            {{ $sItem['role'] }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-amber-300/90 truncate max-w-[220px] flex items-center gap-1 mt-0.5 font-mono-code">
                                        <span>{{ $sItem['exhibit']['seizure_name_drug'] ?: 'No Exhibit' }}</span>
                                        @if(!empty($sItem['exhibit']['seizure_weight']))
                                            <span>({{ $sItem['exhibit']['seizure_weight'] }} {{ $sItem['exhibit']['seizure_unit'] ?: 'kg' }})</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- STYLISH TAB STRIP -->
                <div class="flex items-center border-b border-slate-200 bg-slate-50/80 px-6 gap-2 pt-3 overflow-x-auto">
                    <button type="button" id="editTabBtn1" onclick="switchEditTab(1)" class="edit-tab-btn px-5 py-3 text-xs font-black border-b-2 border-amber-600 text-slate-900 bg-white rounded-t-xl border-t border-x border-slate-200 shadow-xs flex items-center gap-2.5 cursor-pointer shrink-0 transition-all">
                        <span>1. Case &amp; Suspect Details</span>
                        @if(!$canEditCrimeScene)
                            <span class="text-[9px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-mono-code font-bold">Read-Only</span>
                        @endif
                    </button>
                    <button type="button" id="editTabBtn2" onclick="switchEditTab(2)" class="edit-tab-btn px-5 py-3 text-xs font-bold text-slate-500 hover:text-slate-900 border-b-2 border-transparent hover:bg-white/60 rounded-t-xl flex items-center gap-2.5 cursor-pointer shrink-0 transition-all">
                        <span>2. Seized Exhibits Manifest</span>
                        @if(!$canEditCrimeScene)
                            <span class="text-[9px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-mono-code font-bold">Read-Only</span>
                        @endif
                    </button>
                    <button type="button" id="editTabBtn3" onclick="switchEditTab(3)" class="edit-tab-btn px-5 py-3 text-xs font-bold text-slate-500 hover:text-slate-900 border-b-2 border-transparent hover:bg-white/60 rounded-t-xl flex items-center gap-2.5 cursor-pointer shrink-0 transition-all">
                        <span>3. Laboratory Chemical Results</span>
                        @if(!$canEditLab)
                            <span class="text-[9px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-mono-code font-bold">Read-Only</span>
                        @endif
                    </button>
                </div>

                <!-- EDIT FORM -->
                <form id="editCaseForm" action="/cases/{{ $case->case_number }}/update" method="POST" class="p-6 sm:p-8 space-y-8">
                    @csrf
                    <input type="hidden" name="suspects_data_json" id="suspectsDataJson">
                    <input type="hidden" name="active_suspect_index" id="activeSuspectIndex" value="0">

                    <!-- TAB 1: CASE & SUSPECT DETAILS -->
                    <div id="editTabContent1" class="edit-tab-content space-y-8">
                        @if(!$canEditCrimeScene)
                            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs font-bold flex items-center gap-3 shadow-2xs">
                                <span><strong>Read-Only Mode</strong> — Crime Scene Support fields are restricted for your current unit account. You may review all fields below.</span>
                            </div>
                        @endif

                        <!-- SECTION 1.1: CASE COMMAND -->
                        <div class="space-y-5">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                                    <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">Section 1 — Master Case Title &amp; Classification</h3>
                                </div>
                                <span class="text-[10px] font-mono-code text-slate-400 font-bold uppercase">CRIME SCENE SUPPORT UNIT</span>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div>
                                    <label class="stylish-label">
                                        <span>Case Legal Title / Name</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <input type="text" name="title" value="{{ $case->title }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Republic Vs Atto & Ors">
                                </div>
                                <div>
                                    <label class="stylish-label">
                                        <span>Operation Type</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    @php
                                        $rawOpType = $case->operation_type 
                                            ?: ($case->operation_code_name 
                                            ?: ($case->operation_code 
                                            ?: ($docketData['operation_type'] ?? '')));
                                        $curOpType = strtolower(trim($rawOpType));
                                        $opTypeDisplay = 'Select Operation Type...';
                                        if (str_contains($curOpType, 'cargo') || str_contains($curOpType, 'parcel')) {
                                            $opTypeDisplay = 'Cargo / Parcel';
                                        } elseif (str_contains($curOpType, 'raid')) {
                                            $opTypeDisplay = 'Raid';
                                        } elseif (str_contains($curOpType, 'warrant') || str_contains($curOpType, 'search')) {
                                            $opTypeDisplay = 'Search Warrant';
                                        } elseif (str_contains($curOpType, 'intel')) {
                                            $opTypeDisplay = 'Intel-led Ops';
                                        } elseif (str_contains($curOpType, 'transfer')) {
                                            $opTypeDisplay = 'Transferred Case';
                                        } elseif (!empty($rawOpType)) {
                                            $opTypeDisplay = $rawOpType;
                                        }
                                    @endphp
                                    <div class="relative z-30">
                                        <button 
                                            type="button" 
                                            id="operationTypeDropdownTrigger" 
                                            onclick="toggleEditOperationTypeDropdown(event)" 
                                            {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <span id="selectedOpTypeLabel" class="{{ (empty($rawOpType) || $opTypeDisplay === 'Select Operation Type...') ? 'font-bold text-slate-400 text-xs' : 'font-extrabold text-slate-900 text-xs' }}">
                                                {{ $opTypeDisplay }}
                                            </span>
                                            <input type="hidden" id="operationTypeSelect" name="operation_type" value="{{ $rawOpType ?: ($opTypeDisplay !== 'Select Operation Type...' ? $opTypeDisplay : '') }}">
                                            <svg id="opTypeChevron" class="w-4 h-4 text-amber-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        @if($canEditCrimeScene)
                                            <div id="operationTypeDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                                <button type="button" onclick="selectEditOperationType('', 'Select Operation Type...', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-all border-b border-slate-100 mb-1 cursor-pointer">
                                                    <div>
                                                        <div class="font-bold text-slate-500 hover:text-slate-800">Select Operation Type...</div>
                                                        <div class="text-[10px] text-slate-400 font-normal">Clear operation classification</div>
                                                    </div>
                                                </button>

                                                <button type="button" onclick="selectEditOperationType('Cargo / Parcel', 'Cargo / Parcel', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900 group-hover:text-amber-800">Cargo / Parcel</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Freight, courier &amp; airport cargo seizures</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase">Cargo</span>
                                                </button>

                                                <button type="button" onclick="selectEditOperationType('Raid', 'Raid', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900 group-hover:text-red-700">Raid</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Tactical strike &amp; field arrest ops</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">Raid</span>
                                                </button>

                                                <button type="button" onclick="selectEditOperationType('Search Warrant', 'Search Warrant', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900 group-hover:text-blue-700">Search Warrant</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Court-ordered property search</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase">Warrant</span>
                                                </button>

                                                <button type="button" onclick="selectEditOperationType('Intel-led Ops', 'Intel-led Ops', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900 group-hover:text-emerald-700">Intel-led Ops</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Intelligence &amp; surveillance operation</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">Intel</span>
                                                </button>

                                                <button type="button" onclick="selectEditOperationType('Transferred Case', 'Transferred Case', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900 group-hover:text-purple-700">Transferred Case</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">External agency / police referral</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-mono-code font-bold rounded-lg border border-purple-200 uppercase">Transfer</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <label class="stylish-label">
                                        <span>Case Priority Level</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    @php
                                        $curPriority = strtoupper(trim($case->priority ?? 'NORMAL'));
                                        $priorityLabel = 'Normal Priority';
                                        $priorityDotClass = 'bg-slate-500';
                                        if ($curPriority === 'HIGH') {
                                            $priorityLabel = 'High Priority';
                                            $priorityDotClass = 'bg-red-600';
                                        } elseif ($curPriority === 'LOW') {
                                            $priorityLabel = 'Low Priority';
                                            $priorityDotClass = 'bg-slate-400';
                                        } elseif ($curPriority === 'MEDIUM') {
                                            $priorityLabel = 'Medium Priority';
                                            $priorityDotClass = 'bg-amber-500';
                                        } elseif ($curPriority === 'URGENT') {
                                            $priorityLabel = 'Urgent Priority';
                                            $priorityDotClass = 'bg-rose-600 animate-pulse';
                                        }
                                    @endphp
                                    <div class="relative z-30">
                                        <button 
                                            type="button" 
                                            id="priorityDropdownTrigger" 
                                            onclick="toggleEditPriorityDropdown(event)" 
                                            {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedPriorityDot" class="w-2.5 h-2.5 rounded-full {{ $priorityDotClass }} shrink-0 shadow-xs"></span>
                                                <span id="selectedPriorityLabel" class="font-extrabold text-slate-900 text-xs">
                                                    {{ $priorityLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="casePrioritySelect" name="priority" value="{{ $curPriority }}">
                                            <svg id="priorityChevron" class="w-4 h-4 text-amber-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        @if($canEditCrimeScene)
                                            <div id="priorityDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                                <button type="button" onclick="selectEditPriority('HIGH', 'High Priority', 'bg-red-600', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-red-600 shrink-0"></span>
                                                        <div>
                                                            <div class="font-extrabold text-slate-900 group-hover:text-red-700">High Priority</div>
                                                            <div class="text-[10px] text-slate-500 font-normal">Urgent multi-unit processing &amp; priority review</div>
                                                        </div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">HIGH</span>
                                                </button>

                                                <button type="button" onclick="selectEditPriority('LOW', 'Low Priority', 'bg-slate-400', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                                                        <div>
                                                            <div class="font-extrabold text-slate-900 group-hover:text-slate-800">Low Priority</div>
                                                            <div class="text-[10px] text-slate-500 font-normal">Standard routine docket</div>
                                                        </div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-mono-code font-bold rounded-lg border border-slate-200 uppercase">LOW</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" name="location" value="{{ $case->location }}">
                                <input type="hidden" name="submitting_officer" value="{{ $case->submitting_officer }}">
                                <input type="hidden" name="commanding_officer" value="{{ $case->commanding_officer }}">
                            </div>
                        </div>

                        <!-- SECTION 2: MUGSHOT (MULTI-ANGLE PHOTO CAPTURE) -->
                        <div class="space-y-5 pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                                    <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">Section 2 — Mugshot (Multi-Angle Photo Capture)</h3>
                                </div>
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-900 text-[10px] font-mono-code font-bold rounded-lg uppercase">
                                    3 Angles Required
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- ANGLE 1: FRONTAL VIEW -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-amber-500 transition-colors shadow-2xs">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-amber-600 text-slate-950 uppercase">Angle 1</span>
                                        <span class="text-[10px] font-bold text-slate-700 font-mono-code">Front View (0&deg;)</span>
                                        <span id="badge-front" class="text-[9px] font-mono-code font-bold {{ !empty($mugshotFront) ? 'text-emerald-600' : 'text-slate-400' }} uppercase">
                                            {{ !empty($mugshotFront) ? 'Uploaded' : 'Not Uploaded' }}
                                        </span>
                                    </div>
                                    <div 
                                        id="dropzone-front"
                                        onclick="triggerMugshotUpload('front')"
                                        ondragover="handleArrestMugshotDragOver(event, this)"
                                        ondragleave="handleArrestMugshotDragLeave(event, this)"
                                        ondrop="handleArrestMugshotDrop(event, 'front')"
                                        class="h-48 w-full bg-white hover:bg-amber-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-amber-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                    >
                                        <img id="preview-front" class="{{ !empty($mugshotFront) ? '' : 'hidden' }} w-full h-full object-contain p-1 transition-all duration-200" src="{{ $mugshotFront }}" alt="Front Mugshot">
                                        <div id="placeholder-front" class="{{ !empty($mugshotFront) ? 'hidden' : 'flex' }} flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                            <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                            <span class="text-[9px] font-mono-code text-slate-400">Full Face Direct View (0&deg;)</span>
                                        </div>
                                        <div id="actions-front" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 {{ !empty($mugshotFront) ? 'flex' : 'hidden' }}" onclick="event.stopPropagation()">
                                            <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-front').src, 'Front View (0&deg;) Mugshot')" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-500 text-slate-950 text-[10px] font-extrabold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>View Full</span>
                                            </button>
                                            <button type="button" onclick="clearArrestMugshotPhoto('front')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>Clear</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 pt-1">
                                        <input type="file" id="file-front" accept="image/*" class="hidden" onchange="previewMugshot(event, 'front')">
                                        <button type="button" onclick="triggerMugshotUpload('front')" {{ !$canEditCrimeScene ? 'disabled' : '' }} class="w-full py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 text-[11px] font-extrabold rounded-xl border border-amber-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                            <span>Choose Front Photo</span>
                                        </button>
                                        <input 
                                            type="text" 
                                            id="url-front" 
                                            placeholder="Or paste image URL..." 
                                            value="{{ str_starts_with($mugshotFront, 'http') ? $mugshotFront : '' }}"
                                            oninput="previewMugshotUrlArrest(this.value, 'front')" 
                                            {{ !$canEditCrimeScene ? 'readonly' : '' }}
                                            class="stylish-input text-[10px] font-mono-code"
                                        >
                                        <input type="hidden" name="mugshot_front" id="hidden-front" value="{{ $mugshotFront }}">
                                    </div>
                                </div>

                                <!-- ANGLE 2: LEFT PROFILE -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-amber-500 transition-colors shadow-2xs">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 2</span>
                                        <span class="text-[10px] font-bold text-slate-700 font-mono-code">Left Profile (90&deg;)</span>
                                        <span id="badge-left-profile" class="text-[9px] font-mono-code font-bold {{ !empty($mugshotLeft) ? 'text-emerald-600' : 'text-slate-400' }} uppercase">
                                            {{ !empty($mugshotLeft) ? 'Uploaded' : 'Not Uploaded' }}
                                        </span>
                                    </div>
                                    <div 
                                        id="dropzone-left-profile"
                                        onclick="triggerMugshotUpload('left-profile')"
                                        ondragover="handleArrestMugshotDragOver(event, this)"
                                        ondragleave="handleArrestMugshotDragLeave(event, this)"
                                        ondrop="handleArrestMugshotDrop(event, 'left-profile')"
                                        class="h-48 w-full bg-white hover:bg-amber-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-amber-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                    >
                                        <img id="preview-left-profile" class="{{ !empty($mugshotLeft) ? '' : 'hidden' }} w-full h-full object-contain p-1 transition-all duration-200" src="{{ $mugshotLeft }}" alt="Left Profile Mugshot">
                                        <div id="placeholder-left-profile" class="{{ !empty($mugshotLeft) ? 'hidden' : 'flex' }} flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                            <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                            <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Left Side Profile</span>
                                        </div>
                                        <div id="actions-left-profile" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 {{ !empty($mugshotLeft) ? 'flex' : 'hidden' }}" onclick="event.stopPropagation()">
                                            <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-left-profile').src, 'Left Profile (90&deg;) Mugshot')" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-500 text-slate-950 text-[10px] font-extrabold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>View Full</span>
                                            </button>
                                            <button type="button" onclick="clearArrestMugshotPhoto('left-profile')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>Clear</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 pt-1">
                                        <input type="file" id="file-left-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'left-profile')">
                                        <button type="button" onclick="triggerMugshotUpload('left-profile')" {{ !$canEditCrimeScene ? 'disabled' : '' }} class="w-full py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 text-[11px] font-extrabold rounded-xl border border-amber-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                            <span>Choose Left Profile</span>
                                        </button>
                                        <input 
                                            type="text" 
                                            id="url-left-profile" 
                                            placeholder="Or paste image URL..." 
                                            value="{{ str_starts_with($mugshotLeft, 'http') ? $mugshotLeft : '' }}"
                                            oninput="previewMugshotUrlArrest(this.value, 'left-profile')" 
                                            {{ !$canEditCrimeScene ? 'readonly' : '' }}
                                            class="stylish-input text-[10px] font-mono-code"
                                        >
                                        <input type="hidden" name="mugshot_left" id="hidden-left-profile" value="{{ $mugshotLeft }}">
                                    </div>
                                </div>

                                <!-- ANGLE 3: RIGHT PROFILE -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-amber-500 transition-colors shadow-2xs">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 3</span>
                                        <span class="text-[10px] font-bold text-slate-700 font-mono-code">Right Profile (90&deg;)</span>
                                        <span id="badge-right-profile" class="text-[9px] font-mono-code font-bold {{ !empty($mugshotRight) ? 'text-emerald-600' : 'text-slate-400' }} uppercase">
                                            {{ !empty($mugshotRight) ? 'Uploaded' : 'Not Uploaded' }}
                                        </span>
                                    </div>
                                    <div 
                                        id="dropzone-right-profile"
                                        onclick="triggerMugshotUpload('right-profile')"
                                        ondragover="handleArrestMugshotDragOver(event, this)"
                                        ondragleave="handleArrestMugshotDragLeave(event, this)"
                                        ondrop="handleArrestMugshotDrop(event, 'right-profile')"
                                        class="h-48 w-full bg-white hover:bg-amber-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-amber-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                    >
                                        <img id="preview-right-profile" class="{{ !empty($mugshotRight) ? '' : 'hidden' }} w-full h-full object-contain p-1 transition-all duration-200" src="{{ $mugshotRight }}" alt="Right Profile Mugshot">
                                        <div id="placeholder-right-profile" class="{{ !empty($mugshotRight) ? 'hidden' : 'flex' }} flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                            <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                            <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Right Side Profile</span>
                                        </div>
                                        <div id="actions-right-profile" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 {{ !empty($mugshotRight) ? 'flex' : 'hidden' }}" onclick="event.stopPropagation()">
                                            <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-right-profile').src, 'Right Profile (90&deg;) Mugshot')" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-500 text-slate-950 text-[10px] font-extrabold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>View Full</span>
                                            </button>
                                            <button type="button" onclick="clearArrestMugshotPhoto('right-profile')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                <span>Clear</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 pt-1">
                                        <input type="file" id="file-right-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'right-profile')">
                                        <button type="button" onclick="triggerMugshotUpload('right-profile')" {{ !$canEditCrimeScene ? 'disabled' : '' }} class="w-full py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 text-[11px] font-extrabold rounded-xl border border-amber-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                            <span>Choose Right Profile</span>
                                        </button>
                                        <input 
                                            type="text" 
                                            id="url-right-profile" 
                                            placeholder="Or paste image URL..." 
                                            value="{{ str_starts_with($mugshotRight, 'http') ? $mugshotRight : '' }}"
                                            oninput="previewMugshotUrlArrest(this.value, 'right-profile')" 
                                            {{ !$canEditCrimeScene ? 'readonly' : '' }}
                                            class="stylish-input text-[10px] font-mono-code"
                                        >
                                        <input type="hidden" name="mugshot_right" id="hidden-right-profile" value="{{ $mugshotRight }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: SUSPECT PERSONAL DETAILS & BOOKING DOCKET -->
                        <div class="space-y-5 pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                                    <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">Section 3 — Suspect Personal Details &amp; Arrest Record</h3>
                                </div>
                                <span class="text-[10px] font-mono-code text-slate-400 font-bold uppercase">ARREST FILE</span>
                            </div>

                            <!-- SUB-SECTION 3A: LEGAL IDENTITY -->
                            <div class="space-y-4">
                                <div class="text-xs font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-1 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>3A. Legal Identity &amp; Physical Specifications</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                    <div>
                                        <label class="stylish-label">
                                            <span>Suspect Full Legal Name</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_suspect_name" name="suspect_name" value="{{ $suspectObj['suspect_name'] ?? $case->suspect_name }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Kwame Mensah">
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Alias / Street Name</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_suspect_alias" name="suspect_alias" value="{{ $suspectObj['suspect_alias'] ?? $case->suspect_alias }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input" placeholder="e.g. Cobra / Jaguar">
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Ghana Card / National ID #</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ghana_card" name="ghana_card" value="{{ $suspectObj['ghana_card'] ?? $case->ghana_card }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-mono-code font-bold" placeholder="GHA-8840291-3">
                                    </div>
                                <div>
                                    <label class="stylish-label">
                                        <span>Gender</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    @php
                                        $curGender = strtolower(trim($suspectObj['gender'] ?? ($case->gender ?? 'Male')));
                                        $genderVal = 'Male';
                                        $genderLabel = 'Male';
                                        $genderBadgeClass = 'bg-emerald-100 text-emerald-700';

                                        if ($curGender === 'female' || str_contains($curGender, 'female')) {
                                            $genderVal = 'Female';
                                            $genderLabel = 'Female';
                                            $genderBadgeClass = 'bg-rose-100 text-rose-700';
                                        } elseif (str_contains($curGender, 'other') || str_contains($curGender, 'binary')) {
                                            $genderVal = 'Other';
                                            $genderLabel = 'Other / Non-Binary';
                                            $genderBadgeClass = 'bg-purple-100 text-purple-700';
                                        } elseif ($curGender === 'unspecified') {
                                            $genderVal = 'Unspecified';
                                            $genderLabel = 'Unspecified';
                                            $genderBadgeClass = 'bg-slate-100 text-slate-600';
                                        } elseif (empty($curGender)) {
                                            $genderVal = '';
                                            $genderLabel = 'Select Gender...';
                                            $genderBadgeClass = 'bg-slate-100 text-slate-600';
                                        }
                                    @endphp
                                    <div class="relative z-30">
                                        <button 
                                            type="button" 
                                            id="genderDropdownTrigger" 
                                            onclick="toggleEditGenderDropdown(event)" 
                                            {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedGenderLabel" class="{{ empty($genderVal) ? 'font-bold text-slate-400 text-xs' : 'font-extrabold text-slate-900 text-xs' }}">
                                                    {{ $genderLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="genderSelect" name="gender" value="{{ $genderVal }}">
                                        </button>

                                        @if($canEditCrimeScene)
                                            <div id="genderDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                                <button type="button" onclick="selectEditGender('', 'Select Gender...', '', 'bg-slate-100 text-slate-600', event)" class="w-full text-left p-2 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center space-x-2 transition-all border-b border-slate-100 mb-1 cursor-pointer">
                                                    <span>Select Gender...</span>
                                                </button>
                                                <button type="button" onclick="selectEditGender('Male', 'Male', '', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer">
                                                    <span>Male</span>
                                                </button>
                                                <button type="button" onclick="selectEditGender('Female', 'Female', '', 'bg-rose-100 text-rose-700', event)" class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-rose-50 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer">
                                                    <span>Female</span>
                                                </button>
                                                <button type="button" onclick="selectEditGender('Other', 'Other / Non-Binary', '', 'bg-purple-100 text-purple-700', event)" class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer">
                                                    <span>Other / Non-Binary</span>
                                                </button>
                                                <button type="button" onclick="selectEditGender('Unspecified', 'Unspecified', '', 'bg-slate-100 text-slate-600', event)" class="w-full text-left p-2 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center space-x-2 transition-colors cursor-pointer">
                                                    <span>Unspecified</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Date of Birth</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="date" id="newArrestedDOB" name="dob" value="{{ $suspectDob }}" onchange="calculateAgeFromDOB()" oninput="calculateAgeFromDOB()" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-mono-code">
                                    </div>
                                    <div>
                                         <label class="stylish-label flex items-center justify-between">
                                             <span>Age (Years)</span>
                                             <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Read-Only (Auto-Calc)</span>
                                         </label>
                                         <input type="number" id="newArrestedAge" name="age" value="{{ $suspectAge }}" readonly class="stylish-input font-bold text-slate-800 bg-slate-100/90 cursor-not-allowed select-none" placeholder="Auto-calculated age...">
                                     </div>
                                    <div>
                                         <label class="stylish-label flex items-center justify-between">
                                             <span>Height Specifications</span>
                                             @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                         </label>
                                         <div class="relative flex items-center bg-white border border-slate-300 rounded-xl shadow-2xs focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all overflow-hidden">
                                             <div class="px-3 py-2.5 bg-slate-100/80 border-r border-slate-200 text-slate-600 font-mono-code font-extrabold text-[10px] uppercase tracking-wider flex items-center gap-1 shrink-0">
                                                 <span>HT</span>
                                             </div>
                                             <input 
                                                 type="text" 
                                                 id="input_height" 
                                                 name="height" 
                                                 value="{{ $suspectHeight }}" 
                                                 {{ !$canEditCrimeScene ? 'readonly' : '' }} 
                                                 class="w-full px-3 py-2.5 font-mono-code font-black text-slate-900 text-xs focus:outline-none bg-transparent" 
                                                 placeholder="e.g. 175 or 5'11&quot;"
                                             >
                                             <div class="pr-1 shrink-0 border-l border-slate-200 bg-slate-50/50">
                                                 <select 
                                                     id="input_height_unit" 
                                                     name="height_unit" 
                                                     {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                                     class="px-2.5 py-2 font-mono-code font-black text-[11px] text-amber-900 bg-transparent focus:outline-none cursor-pointer uppercase border-none"
                                                 >
                                                     <option value="CM" {{ (empty($suspectHeightUnit) || strtoupper($suspectHeightUnit) === 'CM') ? 'selected' : '' }}>CM</option>
                                                     <option value="FT" {{ strtoupper($suspectHeightUnit) === 'FT' ? 'selected' : '' }}>FT</option>
                                                     <option value="IN" {{ strtoupper($suspectHeightUnit) === 'IN' ? 'selected' : '' }}>IN</option>
                                                 </select>
                                             </div>
                                         </div>
                                     </div>
                                     <div>
                                         <label class="stylish-label">
                                             <span>Eye Color</span>
                                             @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                         </label>
                                         <input type="text" id="input_eye_color" name="eye_color" value="{{ $suspectEyeColor }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input" placeholder="e.g. Brown / Black / Blue">
                                     </div>
                                     <div>
                                         <label class="stylish-label">
                                             <span>Nationality</span>
                                             @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                         </label>
                                         <input type="text" id="input_nationality" name="nationality" value="{{ $suspectNationality }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Ghanaian">
                                     </div>
                                     <div>
                                         <label class="stylish-label">
                                             <span>Place of Birth</span>
                                             @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                         </label>
                                         <input type="text" id="input_place_of_birth" name="place_of_birth" value="{{ $suspectPob }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input" placeholder="e.g. Kumasi, Ashanti Region">
                                     </div>
                                     <input type="hidden" id="input_hometown" name="hometown" value="{{ $suspectHometown ?? ($suspectObj['hometown'] ?? ($case->hometown ?? '')) }}">
                                    <div>
                                        <label class="stylish-label">
                                            <span>Contact Phone Number(s)</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_phone" name="phone" value="{{ $suspectObj['phone'] ?? $case->phone }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-mono-code" placeholder="+233 24 000 0000">
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Date &amp; Time of Arrest</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="date" id="input_arrest_date" name="arrest_date" value="{{ $suspectObj['arrest_date'] ?? ($case->arrest_date ? (is_string($case->arrest_date) ? substr($case->arrest_date, 0, 10) : $case->arrest_date->format('Y-m-d')) : '') }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-mono-code flex-1">
                                            <input type="time" id="input_arrest_time" name="arrest_time" value="{{ $suspectObj['arrest_time'] ?? $case->arrest_time }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-mono-code w-32">
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="stylish-label">
                                            <span>Residential Address / GPS</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_address" name="address" value="{{ $suspectObj['address'] ?? $case->address }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input" placeholder="e.g. House No. 14, Ring Road Central, Accra">
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Raid / Interception Site</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_raid_location" name="raid_location" value="{{ $suspectObj['raid_location'] ?? $case->raid_location }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input" placeholder="e.g. Tema Port Warehouse 4">
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-SECTION 3B: MARITAL & DEPENDENTS -->
                            <div class="space-y-4 pt-4 border-t border-slate-200">
                                <div class="text-xs font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-1 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>3B. Marital Record, Relationship Status &amp; Dependents</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                    <div>
                                        <label class="stylish-label">
                                            <span>Marital Status</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                        </label>
                                        @php
                                            $curMar = strtolower(trim($suspectMarital));
                                            $maritalVal = 'Single';
                                            $maritalCode = 'SGL';
                                            $maritalLabel = 'Single';
                                            $maritalBadgeClass = 'bg-emerald-100 text-emerald-700';

                                            if (str_contains($curMar, 'married')) {
                                                $maritalVal = 'Married';
                                                $maritalCode = 'MRD';
                                                $maritalLabel = 'Married';
                                                $maritalBadgeClass = 'bg-blue-100 text-blue-700';
                                            } elseif (str_contains($curMar, 'dating') || str_contains($curMar, 'relationship')) {
                                                $maritalVal = 'Dating / In Relationship';
                                                $maritalCode = 'DAT';
                                                $maritalLabel = 'Dating / In Relationship';
                                                $maritalBadgeClass = 'bg-purple-100 text-purple-700';
                                            } elseif (str_contains($curMar, 'divorced')) {
                                                $maritalVal = 'Divorced';
                                                $maritalCode = 'DIV';
                                                $maritalLabel = 'Divorced';
                                                $maritalBadgeClass = 'bg-amber-100 text-amber-700';
                                            } elseif (str_contains($curMar, 'widowed')) {
                                                $maritalVal = 'Widowed';
                                                $maritalCode = 'WID';
                                                $maritalLabel = 'Widowed';
                                                $maritalBadgeClass = 'bg-slate-200 text-slate-700';
                                            } elseif (empty($curMar)) {
                                                $maritalVal = '';
                                                $maritalCode = 'MS';
                                                $maritalLabel = 'Select Status...';
                                                $maritalBadgeClass = 'bg-slate-100 text-slate-600';
                                            }
                                        @endphp
                                        <div class="relative z-30">
                                            <button 
                                                type="button" 
                                                id="maritalDropdownTrigger" 
                                                onclick="toggleEditMaritalDropdown(event)" 
                                                {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                            >
                                                <div class="flex items-center space-x-2">
                                                    <span id="selectedMaritalCode" class="w-6 h-5 rounded-md {{ $maritalBadgeClass }} flex items-center justify-center text-[9px] font-mono-code font-extrabold shrink-0">
                                                        {{ $maritalCode }}
                                                    </span>
                                                    <span id="selectedMaritalLabel" class="{{ empty($maritalVal) ? 'font-bold text-slate-400 text-xs' : 'font-extrabold text-slate-900 text-xs' }}">
                                                        {{ $maritalLabel }}
                                                    </span>
                                                </div>
                                                <input type="hidden" id="maritalSelect" name="marital_status" value="{{ $maritalVal }}">
                                            </button>

                                            @if($canEditCrimeScene)
                                                <div id="maritalDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                                    <button type="button" onclick="selectEditMarital('', 'Select Status...', 'MS', 'bg-slate-100 text-slate-600', event)" class="w-full text-left p-2 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center space-x-2 transition-all border-b border-slate-100 mb-1 cursor-pointer">
                                                        <span class="w-6 h-5 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-[9px] font-mono-code font-bold">MS</span>
                                                        <span>Select Status...</span>
                                                    </button>
                                                    <button type="button" onclick="selectEditMarital('Single', 'Single', 'SGL', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="w-6 h-5 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-[9px] font-mono-code font-bold">SGL</span>
                                                            <span class="font-extrabold text-slate-900">Single</span>
                                                        </div>
                                                        <span class="text-[10px] font-mono-code text-slate-400 font-bold">SGL</span>
                                                    </button>
                                                    <button type="button" onclick="selectEditMarital('Married', 'Married', 'MRD', 'bg-blue-100 text-blue-700', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="w-6 h-5 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center text-[9px] font-mono-code font-bold">MRD</span>
                                                            <span class="font-extrabold text-slate-900">Married</span>
                                                        </div>
                                                        <span class="text-[10px] font-mono-code text-slate-400 font-bold">MRD</span>
                                                    </button>
                                                    <button type="button" onclick="selectEditMarital('Dating / In Relationship', 'Dating / In Relationship', 'DAT', 'bg-purple-100 text-purple-700', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="w-6 h-5 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-[9px] font-mono-code font-bold">DAT</span>
                                                            <span class="font-extrabold text-slate-900">Dating / In Relationship</span>
                                                        </div>
                                                        <span class="text-[10px] font-mono-code text-slate-400 font-bold">DAT</span>
                                                    </button>
                                                    <button type="button" onclick="selectEditMarital('Divorced', 'Divorced', 'DIV', 'bg-amber-100 text-amber-700', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="w-6 h-5 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center text-[9px] font-mono-code font-bold">DIV</span>
                                                            <span class="font-extrabold text-slate-900">Divorced</span>
                                                        </div>
                                                        <span class="text-[10px] font-mono-code text-slate-400 font-bold">DIV</span>
                                                    </button>
                                                    <button type="button" onclick="selectEditMarital('Widowed', 'Widowed', 'WID', 'bg-slate-200 text-slate-700', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="w-6 h-5 rounded-md bg-slate-200 text-slate-700 flex items-center justify-center text-[9px] font-mono-code font-bold">WID</span>
                                                            <span class="font-extrabold text-slate-900">Widowed</span>
                                                        </div>
                                                        <span class="text-[10px] font-mono-code text-slate-400 font-bold">WID</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Number of Children / Dependents</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="number" id="input_children_count" name="children_count" value="{{ $suspectChildren }}" min="0" max="20" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold" placeholder="e.g. 2">
                                    </div>
                                    <div>
                                        <label class="stylish-label">
                                            <span>Holding Facility / Custody Cell</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_holding_facility" name="holding_facility" value="{{ $suspectObj['holding_facility'] ?? $case->holding_facility }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold" placeholder="e.g. NACOC HQ Cell #2">
                                    </div>

                                    <!-- DYNAMIC CONDITIONAL SPOUSE / PARTNER RECORD CARD -->
                                    <div id="relationshipDetailsBox" class="{{ (str_contains(strtolower($suspectMarital), 'married') || str_contains(strtolower($suspectMarital), 'dating') || str_contains(strtolower($suspectMarital), 'relationship')) ? '' : 'hidden' }} sm:col-span-2 lg:col-span-3 p-5 bg-emerald-50/50 rounded-2xl border border-slate-200/90 space-y-4">
                                        <div class="flex items-center space-x-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                            </div>
                                            <div>
                                                <h4 id="relationshipTitle" class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">
                                                    {{ str_contains(strtolower($suspectMarital), 'dating') ? 'Dating / Relationship Partner Details' : 'Spouse Information (Marriage Record)' }}
                                                </h4>
                                                <p id="relationshipSubtitle" class="text-[11px] text-slate-500 font-medium">
                                                    Mandatory record for suspect legal spouse / partner identity, contact phone, and occupation
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <label class="stylish-label">
                                                    <span>Spouse / Partner Full Legal Name</span>
                                                    @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                                </label>
                                                <input type="text" id="input_spouse_name" name="spouse_name" value="{{ $suspectSpouseName }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold" placeholder="e.g. Abena Mensah">
                                            </div>
                                            <div>
                                                <label class="stylish-label">
                                                    <span>Spouse / Partner Contact Phone</span>
                                                    @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                                </label>
                                                <input type="text" id="input_spouse_phone" name="spouse_phone" value="{{ $suspectSpousePhone }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold font-mono-code" placeholder="e.g. +233 20 884 0129">
                                            </div>
                                            <div>
                                                <label class="stylish-label">
                                                    <span>Spouse / Partner Occupation &amp; Info</span>
                                                    @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                                </label>
                                                <input type="text" id="input_spouse_info" name="spouse_info" value="{{ $suspectSpouseInfo }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold" placeholder="e.g. Trader, Tema Sector 1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2 lg:col-span-3">
                                        <label class="stylish-label">
                                            <span>Offense Narrative &amp; Allegations</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <textarea id="input_offense_description" name="offense_description" rows="3" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input leading-relaxed" placeholder="Enter detailed field offense report and arrest narrative...">{{ $suspectObj['offense_description'] ?? $case->offense_description }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SEIZED EXHIBITS MANIFEST -->
                    <div id="editTabContent2" class="edit-tab-content space-y-6 hidden">
                        @if(!$canEditCrimeScene)
                            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs font-bold flex items-center gap-3 shadow-2xs">
                                <span><strong>Read-Only Mode</strong> — Seized exhibit specifications are restricted for your current unit account.</span>
                            </div>
                        @endif

                        <!-- LIVE EXHIBIT DOSSIER HERO BANNER -->
                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-amber-950 p-5 text-white border border-slate-700/60 shadow-xl">
                            <!-- Glowing Background Accents -->
                            <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
                            <div class="absolute left-1/3 -bottom-10 w-48 h-48 rounded-full bg-amber-600/10 blur-2xl pointer-events-none"></div>

                            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <!-- Left Section Info -->
                                <div class="flex items-start gap-3.5">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-black font-mono-code uppercase tracking-widest text-amber-400">EXHIBIT METROLOGY &amp; MANIFEST DOSSIER</span>
                                            <span id="tab2_live_suspect_role" class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
                                                PRIMARY SUSPECT
                                            </span>
                                        </div>
                                        <div class="text-sm font-extrabold text-slate-100 flex items-center gap-1.5 mt-0.5">
                                            <span>Linked Suspect:</span>
                                            <span id="tab2_live_suspect_name" class="text-amber-300 underline decoration-amber-500/40 underline-offset-4">
                                                {{ $suspectObj['suspect_name'] ?? 'PRIMARY SUSPECT' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Dynamic Data Badges -->
                                <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-700/50">
                                    <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 flex flex-col justify-center min-w-[130px]">
                                        <span class="text-[9px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Exhibit Drug</span>
                                        <span id="tab2_live_drug" class="text-xs font-black text-amber-300 font-mono-code truncate max-w-[150px]">
                                            {{ $case->seizure_name_drug ?: ($case->seizure_name ?: 'No Commodity') }}
                                        </span>
                                    </div>

                                    @php
                                        $curCat = strtolower(trim($case->exhibit_category ?: ($docketData['seizures'][0]['category'] ?? '')));
                                        $catVal = 'Drugs / Narcotics';
                                        $catBadge = 'DRG';
                                        $catLabel = 'Drugs / Narcotics';
                                        $catBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';

                                        if (str_contains($curCat, 'currency') || str_contains($curCat, 'cash') || str_contains($curCat, 'money')) {
                                            $catVal = 'Currency / Cash'; $catBadge = 'CASH'; $catLabel = 'Currency / Cash'; $catBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                        } elseif (str_contains($curCat, 'gold') || str_contains($curCat, 'metal') || str_contains($curCat, 'bullion')) {
                                            $catVal = 'Gold / Precious Metals'; $catBadge = 'MET'; $catLabel = 'Gold / Precious Metals'; $catBadgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
                                        } elseif (str_contains($curCat, 'firearm') || str_contains($curCat, 'weapon') || str_contains($curCat, 'gun')) {
                                            $catVal = 'Firearm / Ammunition / Weapon'; $catBadge = 'WPN'; $catLabel = 'Firearm / Ammunition / Weapon'; $catBadgeClass = 'bg-red-100 text-red-800 border-red-200';
                                        } elseif (str_contains($curCat, 'vehicle') || str_contains($curCat, 'car') || str_contains($curCat, 'transport')) {
                                            $catVal = 'Vehicle / Transport'; $catBadge = 'VEH'; $catLabel = 'Vehicle / Transport'; $catBadgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                        } elseif (str_contains($curCat, 'digital') || str_contains($curCat, 'electronic') || str_contains($curCat, 'phone')) {
                                            $catVal = 'Digital Evidence / Electronics'; $catBadge = 'ELEC'; $catLabel = 'Digital Evidence / Electronics'; $catBadgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-200';
                                        } elseif (str_contains($curCat, 'chemical') || str_contains($curCat, 'precursor')) {
                                            $catVal = 'Chemical / Precursor'; $catBadge = 'CHEM'; $catLabel = 'Chemical / Precursor'; $catBadgeClass = 'bg-purple-100 text-purple-800 border-purple-200';
                                        } elseif (str_contains($curCat, 'counterfeit') || str_contains($curCat, 'fake')) {
                                            $catVal = 'Counterfeit Goods'; $catBadge = 'FAKE'; $catLabel = 'Counterfeit Goods'; $catBadgeClass = 'bg-rose-100 text-rose-800 border-rose-200';
                                        } elseif (str_contains($curCat, 'document') || str_contains($curCat, 'identity') || str_contains($curCat, 'passport')) {
                                            $catVal = 'Documents / Identity'; $catBadge = 'DOC'; $catLabel = 'Documents / Identity'; $catBadgeClass = 'bg-slate-200 text-slate-800 border-slate-300';
                                        } elseif (str_contains($curCat, 'other')) {
                                            $catVal = 'Other Seizure'; $catBadge = 'MISC'; $catLabel = 'Other Seizure'; $catBadgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                        } elseif (empty($curCat)) {
                                            $catVal = ''; $catBadge = 'CAT'; $catLabel = 'Select Category...'; $catBadgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                                        }
                                    @endphp

                                    <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 flex flex-col justify-center min-w-[120px]">
                                        <span class="text-[9px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Category</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span id="tab2_live_cat_badge" class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border {{ $catBadgeClass }}">
                                                {{ $catBadge }}
                                            </span>
                                            <span id="tab2_live_cat_text" class="text-[11px] font-bold text-slate-200 truncate max-w-[90px]">
                                                {{ $catLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 flex flex-col justify-center min-w-[110px]">
                                        <span class="text-[9px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Total Metrology</span>
                                        <span id="tab2_live_metrology" class="text-xs font-black text-emerald-400 font-mono-code">
                                            {{ $case->seizure_quantity ?: '0' }} / {{ $case->seizure_weight ?: '0 kg' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2A: COMMODITY & EXHIBIT CLASSIFICATION -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 hover:border-slate-300 transition-all">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                                <div class="flex items-center gap-3">
                                    <div>
                                        <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">2A. Commodity &amp; Exhibit Classification</h3>
                                        <p class="text-[11px] font-medium text-slate-500">Primary contraband specifications, exhibit naming, and legal asset category</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wider">
                                    CLASSIFICATION
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <!-- Seizure / Drug Name -->
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="stylish-label">
                                        <span>Seizure / Drug Name</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <div>
                                        <input type="text" id="input_seizure_name_drug" name="seizure_name_drug" value="{{ $case->seizure_name_drug ?: $case->seizure_name }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} oninput="syncExhibitToLabAndSelector()" onchange="syncExhibitToLabAndSelector()" class="stylish-input font-bold text-slate-900" placeholder="e.g. Suspected Cocaine / White Compressed Slab">
                                    </div>
                                </div>

                                <!-- Exhibit Category -->
                                <div>
                                    <label class="stylish-label">
                                        <span>Exhibit Category</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    <div class="relative z-40">
                                        <button 
                                            type="button" 
                                            id="categoryDropdownTrigger" 
                                            onclick="toggleEditCategoryDropdown(event)" 
                                            {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedCategoryBadge" class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border {{ $catBadgeClass }} shrink-0">
                                                    {{ $catBadge }}
                                                </span>
                                                <span id="selectedCategoryLabel" class="{{ empty($catVal) ? 'font-bold text-slate-400 text-xs' : 'font-extrabold text-slate-900 text-xs' }}">
                                                    {{ $catLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="exhibitCategorySelect" name="exhibit_category" value="{{ $catVal }}">
                                        </button>

                                        @if($canEditCrimeScene)
                                            <div id="categoryDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[60] p-2 space-y-1">
                                                <button type="button" onclick="selectEditCategory('', 'Select Category...', 'CAT', 'bg-slate-100 text-slate-600 border-slate-200', event)" class="w-full text-left p-2 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center space-x-2 transition-all border-b border-slate-100 mb-1 cursor-pointer">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-slate-100 text-slate-600 border border-slate-200">CAT</span>
                                                    <span>Select Category...</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Drugs / Narcotics', 'Drugs / Narcotics', 'DRG', 'bg-emerald-100 text-emerald-800 border-emerald-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Drugs / Narcotics</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids, Cannabis</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">DRG</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Currency / Cash', 'Currency / Cash', 'CASH', 'bg-emerald-100 text-emerald-800 border-emerald-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Currency / Cash</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Seized banknotes, foreign currencies &amp; laundered funds</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">CASH</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Gold / Precious Metals', 'Gold / Precious Metals', 'MET', 'bg-amber-100 text-amber-800 border-amber-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Gold / Precious Metals</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Bullion, smuggled metals &amp; unrefined gold</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase">MET</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Firearm / Ammunition / Weapon', 'Firearm / Ammunition / Weapon', 'WPN', 'bg-red-100 text-red-800 border-red-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Firearm / Ammunition / Weapon</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Firearms, live rounds &amp; offensive weapons</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">WPN</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Vehicle / Transport', 'Vehicle / Transport', 'VEH', 'bg-blue-100 text-blue-800 border-blue-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Vehicle / Transport</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Automobiles, maritime vessels &amp; haulage trucks</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase">VEH</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Digital Evidence / Electronics', 'Digital Evidence / Electronics', 'ELEC', 'bg-indigo-100 text-indigo-800 border-indigo-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-indigo-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Digital Evidence / Electronics</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Mobile phones, laptops &amp; forensic drives</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-mono-code font-bold rounded-lg border border-indigo-200 uppercase">ELEC</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Chemical / Precursor', 'Chemical / Precursor', 'CHEM', 'bg-purple-100 text-purple-800 border-purple-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Chemical / Precursor</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Controlled precursor reagents &amp; laboratory chemicals</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-mono-code font-bold rounded-lg border border-purple-200 uppercase">CHEM</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Counterfeit Goods', 'Counterfeit Goods', 'FAKE', 'bg-rose-100 text-rose-800 border-rose-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-rose-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Counterfeit Goods</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Fake pharmaceuticals, merchandise &amp; contraband</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-mono-code font-bold rounded-lg border border-rose-200 uppercase">FAKE</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Documents / Identity', 'Documents / Identity', 'DOC', 'bg-slate-200 text-slate-800 border-slate-300', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Documents / Identity</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Passports, forged IDs &amp; legal manifests</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-slate-200 text-slate-800 text-[10px] font-mono-code font-bold rounded-lg border border-slate-300 uppercase">DOC</span>
                                                </button>

                                                <button type="button" onclick="selectEditCategory('Other Seizure', 'Other Seizure', 'MISC', 'bg-slate-100 text-slate-700 border-slate-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div>
                                                        <div class="font-extrabold text-slate-900">Other Seizure</div>
                                                        <div class="text-[10px] text-slate-500 font-normal">Miscellaneous seized items &amp; evidence</div>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-mono-code font-bold rounded-lg border border-slate-200 uppercase">MISC</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Seizure Quantity -->
                                <div>
                                    <label class="stylish-label">
                                        <span>Seizure Quantity</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <div class="relative">
                                        
                                        <input type="text" id="input_seizure_quantity" name="seizure_quantity" value="{{ $case->seizure_quantity }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} oninput="syncExhibitToLabAndSelector()" onchange="syncExhibitToLabAndSelector()" class="stylish-input pl-8 font-mono-code font-bold" placeholder="e.g. 50">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2B: PACKAGING METROLOGY & INTERCEPTION RECORD -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 hover:border-slate-300 transition-all">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                                <div>
                                    <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">2B. Packaging Metrology &amp; Interception Record</h3>
                                    <p class="text-[11px] font-medium text-slate-500">Gross weight measurement, packaging materials, and seizure legal justification</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                                    METROLOGY
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <!-- Seizure Weight -->
                                <div>
                                    <label class="stylish-label">
                                        <span>Seizure Weight</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <input type="text" id="input_seizure_weight" name="seizure_weight" value="{{ $case->seizure_weight }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} oninput="syncExhibitToLabAndSelector()" onchange="syncExhibitToLabAndSelector()" class="stylish-input font-mono-code font-bold" placeholder="e.g. 52.4 kg">
                                </div>

                                <!-- Packaging Unit Type Dropdown -->
                                <div>
                                    <label class="stylish-label">
                                        <span>Packaging Unit Type</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    @php
                                        $curUnit = strtolower(trim($case->seizure_unit ?: ($docketData['seizures'][0]['seizure_unit'] ?? '')));
                                        $unitVal = 'Kilograms (kg)';
                                        $unitCode = 'KG';
                                        $unitLabel = 'Kilograms (kg)';
                                        $unitBadgeClass = 'bg-amber-100 text-amber-800 border-amber-200';

                                        if (str_contains($curUnit, 'kilo') || str_contains($curUnit, 'kg')) {
                                            $unitVal = 'Kilograms (kg)'; $unitCode = 'KG'; $unitLabel = 'Kilograms (kg)'; $unitBadgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
                                        } elseif (str_contains($curUnit, 'gram') || $curUnit === 'g') {
                                            $unitVal = 'Grams (g)'; $unitCode = 'G'; $unitLabel = 'Grams (g)'; $unitBadgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
                                        } elseif (str_contains($curUnit, 'sack') || str_contains($curUnit, 'bag')) {
                                            $unitVal = 'Sacks / Bags'; $unitCode = 'BAG'; $unitLabel = 'Sacks / Bags'; $unitBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                        } elseif (str_contains($curUnit, 'slab') || str_contains($curUnit, 'block')) {
                                            $unitVal = 'Slabs / Blocks'; $unitCode = 'SLAB'; $unitLabel = 'Slabs / Blocks'; $unitBadgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                        } elseif (str_contains($curUnit, 'package') || str_contains($curUnit, 'parcel')) {
                                            $unitVal = 'Packages / Parcels'; $unitCode = 'PKG'; $unitLabel = 'Packages / Parcels'; $unitBadgeClass = 'bg-purple-100 text-purple-800 border-purple-200';
                                        } elseif (str_contains($curUnit, 'barrel') || str_contains($curUnit, 'drum')) {
                                            $unitVal = 'Barrels / Drums'; $unitCode = 'DRUM'; $unitLabel = 'Barrels / Drums'; $unitBadgeClass = 'bg-red-100 text-red-800 border-red-200';
                                        } elseif (str_contains($curUnit, 'container') || str_contains($curUnit, 'box')) {
                                            $unitVal = 'Containers / Boxes'; $unitCode = 'BOX'; $unitLabel = 'Containers / Boxes'; $unitBadgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-200';
                                        } elseif (str_contains($curUnit, 'piece') || str_contains($curUnit, 'unit')) {
                                            $unitVal = 'Pieces / Units'; $unitCode = 'PCS'; $unitLabel = 'Pieces / Units'; $unitBadgeClass = 'bg-slate-200 text-slate-800 border-slate-300';
                                        } elseif (empty($curUnit)) {
                                            $unitVal = ''; $unitCode = 'UNIT'; $unitLabel = 'Select Packaging Unit...'; $unitBadgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                                        }
                                    @endphp
                                    <div class="relative z-30">
                                        <button 
                                            type="button" 
                                            id="unitDropdownTrigger" 
                                            onclick="toggleEditUnitDropdown(event)" 
                                            {{ !$canEditCrimeScene ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedUnitBadge" class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border {{ $unitBadgeClass }} shrink-0">
                                                    {{ $unitCode }}
                                                </span>
                                                <span id="selectedUnitLabel" class="{{ empty($unitVal) ? 'font-bold text-slate-400 text-xs' : 'font-extrabold text-slate-900 text-xs' }}">
                                                    {{ $unitLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="seizureUnitSelect" name="seizure_unit" value="{{ $unitVal }}">
                                        </button>

                                        @if($canEditCrimeScene)
                                            <div id="unitDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                                <button type="button" onclick="selectEditUnit('', 'Select Packaging Unit...', 'UNIT', 'bg-slate-100 text-slate-600 border-slate-200', event)" class="w-full text-left p-2 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center space-x-2 transition-all border-b border-slate-100 mb-1 cursor-pointer">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-slate-100 text-slate-600 border border-slate-200">UNIT</span>
                                                    <span>Select Packaging Unit...</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Kilograms (kg)', 'Kilograms (kg)', 'KG', 'bg-amber-100 text-amber-800 border-amber-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200">KG</span>
                                                        <span class="font-extrabold text-slate-900">Kilograms (kg)</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">KG</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Grams (g)', 'Grams (g)', 'G', 'bg-amber-100 text-amber-800 border-amber-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200">G</span>
                                                        <span class="font-extrabold text-slate-900">Grams (g)</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">G</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Sacks / Bags', 'Sacks / Bags', 'BAG', 'bg-emerald-100 text-emerald-800 border-emerald-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">BAG</span>
                                                        <span class="font-extrabold text-slate-900">Sacks / Bags</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">BAG</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Slabs / Blocks', 'Slabs / Blocks', 'SLAB', 'bg-blue-100 text-blue-800 border-blue-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-200">SLAB</span>
                                                        <span class="font-extrabold text-slate-900">Slabs / Blocks</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">SLAB</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Packages / Parcels', 'Packages / Parcels', 'PKG', 'bg-purple-100 text-purple-800 border-purple-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-purple-100 text-purple-800 border border-purple-200">PKG</span>
                                                        <span class="font-extrabold text-slate-900">Packages / Parcels</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">PKG</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Barrels / Drums', 'Barrels / Drums', 'DRUM', 'bg-red-100 text-red-800 border-red-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-red-100 text-red-800 border border-red-200">DRUM</span>
                                                        <span class="font-extrabold text-slate-900">Barrels / Drums</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">DRUM</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Containers / Boxes', 'Containers / Boxes', 'BOX', 'bg-indigo-100 text-indigo-800 border-indigo-200', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-indigo-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">BOX</span>
                                                        <span class="font-extrabold text-slate-900">Containers / Boxes</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">BOX</span>
                                                </button>

                                                <button type="button" onclick="selectEditUnit('Pieces / Units', 'Pieces / Units', 'PCS', 'bg-slate-200 text-slate-800 border-slate-300', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-slate-200 text-slate-800 border border-slate-300">PCS</span>
                                                        <span class="font-extrabold text-slate-900">Pieces / Units</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">PCS</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Seizure Packaging Description -->
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="stylish-label">
                                        <span>Seizure Packaging Description</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <input type="text" id="input_seizure_packaging" name="seizure_packaging" value="{{ $case->seizure_packaging }}" {{ !$canEditCrimeScene ? 'readonly' : '' }} oninput="syncExhibitToLabAndSelector()" onchange="syncExhibitToLabAndSelector()" class="stylish-input" placeholder="e.g. Wrapped in brown masking tape with black waterproof foil">
                                </div>

                                <!-- Reason for Seizure -->
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="stylish-label">
                                        <span>Reason for Seizure &amp; Interception Narrative</span>
                                        @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <textarea id="input_seizure_reason" name="seizure_reason" rows="3" {{ !$canEditCrimeScene ? 'readonly' : '' }} oninput="syncExhibitToLabAndSelector()" onchange="syncExhibitToLabAndSelector()" class="stylish-input leading-relaxed" placeholder="Explain field interception justification and legal basis for seizure...">{{ $case->seizure_reason }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2D: AMMUNITION METROLOGY & SPECIFICATIONS -->
                        <div id="card_ammunition_details" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 hover:border-slate-300 transition-all hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center font-bold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">2D. Dedicated Ammunition Metrology &amp; Specifications</h3>
                                        <p class="text-[11px] font-medium text-slate-500">Cartridge physical appearance, bullet types, case materials, condition, and tamper-evident packaging</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-orange-50 text-orange-800 border border-orange-200 uppercase tracking-wider">
                                    AMMUNITION DETAILS
                                </span>
                            </div>

                            <!-- 1. Physical Description -->
                            <div class="space-y-3">
                                <h4 class="text-[10px] font-extrabold text-orange-700 uppercase tracking-widest bg-orange-50 px-2 py-1 rounded-md inline-block">1. Physical Description</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                    <div>
                                        <label class="stylish-label">
                                            <span>Cartridge Appearance</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_cartridge_appearance" name="ammo_cartridge_appearance" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Brass casing, FMJ, Nickel-plated">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Projectile / Bullet Type</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_projectile_bullet_type" name="ammo_projectile_bullet_type" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Full Metal Jacket (FMJ), Hollow Point">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Cartridge Case Material</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_cartridge_case_material" name="ammo_cartridge_case_material" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Brass, Steel, Aluminium, Plastic">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Colour / Markings</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_colour_markings" name="ammo_colour_markings" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Headstamp CBC 9mm LUGER, Red primer sealant">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Condition</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <select id="input_ammo_condition" name="ammo_condition" onchange="toggleEditAmmoOtherCondition(this)" {{ !$canEditCrimeScene ? 'disabled' : '' }} class="stylish-select">
                                            <option value="">Select Condition...</option>
                                            <option value="Intact">Intact</option>
                                            <option value="Damaged">Damaged</option>
                                            <option value="Corroded">Corroded</option>
                                            <option value="Deformed">Deformed</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div id="wrapper_edit_ammo_other_condition" class="hidden">
                                        <label class="stylish-label">
                                            <span>Other Condition Description</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_other_condition" name="ammo_other_condition" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="Specify condition details...">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Packaging & Sealing -->
                            <div class="space-y-3 pt-3 border-t border-slate-100">
                                <h4 class="text-[10px] font-extrabold text-orange-700 uppercase tracking-widest bg-orange-50 px-2 py-1 rounded-md inline-block">2. Packaging &amp; Sealing</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-5">
                                    <div>
                                        <label class="stylish-label">
                                            <span>Packaging Type</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_packaging_type" name="ammo_packaging_type" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Cardboard Box, Plastic Ammo Can, Loose Bag">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Seal / Tamper-Evident Condition</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_seal_condition" name="ammo_seal_condition" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Intact Tamper-Evident Bag #SEAL-90812">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Package / Reference Number</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_package_reference_number" name="ammo_package_reference_number" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. PKG-2026-AM01">
                                    </div>

                                    <div>
                                        <label class="stylish-label">
                                            <span>Condition When Received</span>
                                            @if(!$canEditCrimeScene)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                        </label>
                                        <input type="text" id="input_ammo_condition_when_received" name="ammo_condition_when_received" {{ !$canEditCrimeScene ? 'readonly' : '' }} class="stylish-input font-bold text-slate-900" placeholder="e.g. Sealed and untampered">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2C: SEIZED CONTRABAND & DRUG VISUAL EVIDENCE GALLERY -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 hover:border-slate-300 transition-all">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 flex-wrap gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">2C. Seized Contraband &amp; Drug Visual Evidence Gallery</h3>
                                        <span id="tab2_exhibit_photo_count_badge" class="px-2 py-0.5 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                                            0 Photos
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500">High-resolution contraband photographs, field seizure physical evidence &amp; drug packaging visuals</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wider">
                                    VISUAL EVIDENCE
                                </span>
                            </div>

                            <!-- UPLOAD & CONTROLS TOOLBAR -->
                            @if($canEditCrimeScene)
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                    <!-- File Upload Button -->
                                    <div class="flex items-center gap-2">
                                        <label for="fileExhibitPhotoUpload" class="px-3.5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span>Upload Drug Photo</span>
                                        </label>
                                        <input type="file" id="fileExhibitPhotoUpload" accept="image/*" multiple class="hidden" onchange="handleExhibitPhotoUpload(event)">
                                        <span class="text-[11px] font-medium text-slate-500">Supports JPG, PNG, WEBP</span>
                                    </div>

                                    <!-- Direct URL Upload Input -->
                                    <div class="flex items-center gap-2 flex-1 max-w-md">
                                        <input type="url" id="inputExhibitPhotoUrl" class="stylish-input text-xs py-1.5 font-mono-code" placeholder="Or paste image URL (https://...)">
                                        <button type="button" onclick="addExhibitPhotoFromUrl()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl shrink-0 transition-colors cursor-pointer">
                                            Add URL
                                        </button>
                                        <input type="hidden" id="input_evidence_photos_json" name="evidence_photos" value="{{ json_encode($casePhotos) }}">
                                    </div>
                                </div>
                            @endif

                            <!-- GALLERY GRID -->
                            <div id="exhibitPhotosGridContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 min-h-[120px]">
                                <!-- Dynamically populated by renderExhibitPhotosGrid() -->
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: LABORATORY CHEMICAL RESULTS -->
                    <div id="editTabContent3" class="edit-tab-content space-y-6 hidden">
                        @if(!$canEditLab)
                            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs font-bold flex items-center gap-3 shadow-2xs">
                                <span><strong>Read-Only Mode</strong> — Laboratory examination findings are restricted for Crime Scene Support officers.</span>
                            </div>
                        @endif

                        <!-- LIVE LAB DOSSIER HERO BANNER -->
                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-5 text-white border border-slate-700/60 shadow-xl">
                            <!-- Glowing Background Accents -->
                            <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
                            <div class="absolute left-1/3 -bottom-10 w-48 h-48 rounded-full bg-teal-600/10 blur-2xl pointer-events-none"></div>

                            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <!-- Left Section Info -->
                                <div class="flex items-start gap-3.5">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-black font-mono-code uppercase tracking-widest text-emerald-400">LABORATORY CHEMICAL EXAMINATION DOSSIER</span>
                                            <span id="tab3_live_suspect_role" class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase">
                                                PRIMARY SUSPECT
                                            </span>
                                        </div>
                                        <div class="text-sm font-extrabold text-slate-100 flex items-center gap-1.5 mt-0.5">
                                            <span>Linked Suspect:</span>
                                            <span id="tab3_live_suspect_name" class="text-emerald-300 underline decoration-emerald-500/40 underline-offset-4">
                                                {{ $suspectObj['suspect_name'] ?? 'PRIMARY SUSPECT' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Dynamic Data Badges -->
                                <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-700/50">
                                    <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 flex flex-col justify-center min-w-[130px]">
                                        <span class="text-[9px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Lab Docket No.</span>
                                        <span id="tab3_live_lab_no" class="text-xs font-black text-emerald-300 font-mono-code truncate max-w-[150px]">
                                            {{ $case->laboratory_number ?: 'Pending Assignment' }}
                                        </span>
                                    </div>

                                    <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 flex flex-col justify-center min-w-[120px]">
                                        <span class="text-[9px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Confirmatory Status</span>
                                        <span id="tab3_live_conf_status" class="text-xs font-black text-emerald-400 font-mono-code">
                                            {{ strtoupper($case->confirmatory_test_result) === 'NO' ? 'UNCONFIRMED' : 'CONFIRMED POSITIVE' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 3A: LABORATORY REGISTRATION & PRESUMPTIVE TESTING -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 hover:border-slate-300 transition-all">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                                <div>
                                    <h3 class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">3A. Laboratory Registration &amp; Presumptive Colorimetric Screening</h3>
                                    <p class="text-[11px] font-medium text-slate-500">Official lab reference tracking, preliminary spot test reaction, and sample purity specs</p>
                                </div>
                                @if($canEditLab)
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-xl border border-amber-200 transition-all shadow-2xs">
                                        <input type="checkbox" id="modalUnlockSec5" name="unlock_section_5" value="1" class="rounded text-amber-600 focus:ring-amber-500">
                                        <span class="font-mono-code text-[11px]">Unlock Section 5 Form on Review Page</span>
                                    </label>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div>
                                    <label class="stylish-label">
                                        <span>Laboratory Reference Number</span>
                                        @if(!$canEditLab)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <input type="text" id="input_laboratory_number" name="laboratory_number" value="{{ $case->laboratory_number ?: ($docketData['lab_examination']['laboratory_number'] ?? '') }}" {{ !$canEditLab ? 'readonly' : '' }} class="stylish-input font-mono-code font-extrabold text-slate-900" placeholder="Lab No. 12/FS09/2026">
                                </div>
                                <div>
                                    <label class="stylish-label">
                                        <span>Confirmatory Test Status</span>
                                        @if(!$canEditLab)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    @php
                                        $curConf = strtoupper(trim($case->confirmatory_test_result ?? ($docketData['lab_examination']['confirmatory_result'] ?? '')));
                                        $confVal = ($curConf === 'YES') ? 'YES' : (($curConf === 'NO') ? 'NO' : 'YES');
                                        $confDotClass = ($confVal === 'YES') ? 'bg-emerald-500' : 'bg-slate-400';
                                        $confLabel = ($confVal === 'YES') ? 'YES — Confirmed Positive' : 'NO — Negative / Unconfirmed';
                                    @endphp
                                    <div class="relative z-40">
                                        <button 
                                            type="button" 
                                            id="confirmatoryStatusDropdownTrigger" 
                                            onclick="toggleEditConfirmatoryStatusDropdown(event)" 
                                            {{ !$canEditLab ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedConfirmatoryStatusDot" class="w-2.5 h-2.5 rounded-full {{ $confDotClass }} shrink-0 shadow-xs"></span>
                                                <span id="selectedConfirmatoryStatusLabel" class="font-extrabold text-slate-900 text-xs">
                                                    {{ $confLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="confirmatoryStatusSelect" name="confirmatory_test_result" value="{{ $confVal }}">
                                        </button>

                                        @if($canEditLab)
                                            <div id="confirmatoryStatusDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[60] p-2 space-y-1">
                                                <button type="button" onclick="selectEditConfirmatoryStatus('YES', 'YES — Confirmed Positive', 'bg-emerald-500', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        <span class="font-extrabold text-slate-900">YES — Confirmed Positive</span>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-mono-code font-bold rounded border border-emerald-200 uppercase">CONFIRMED</span>
                                                </button>

                                                <button type="button" onclick="selectEditConfirmatoryStatus('NO', 'NO — Negative / Unconfirmed', 'bg-slate-400', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                                                        <span class="font-extrabold text-slate-900">NO — Negative / Unconfirmed</span>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[9px] font-mono-code font-bold rounded border border-slate-200 uppercase">UNCONFIRMED</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @php
                                    $rawPresumptive = $case->presumptive_test_result ?: ($docketData['lab_examination']['presumptive_result'] ?? ($docketData['seizures'][0]['presumptive_result'] ?? ''));
                                    $upperPres = strtoupper(trim($rawPresumptive));
                                    $canEditPresumptive = $canEditLab || $canEditCrimeScene;

                                    // 1. Presumptive Test Reaction Status
                                    $presReactionVal = 'YES (Positive Colorimetric Reaction)';
                                    $presDotClass = 'bg-emerald-500';
                                    $presReactionLabel = 'YES — Positive Reaction';

                                    if (str_contains($upperPres, 'NO') || str_contains($upperPres, 'NEGATIVE')) {
                                        $presReactionVal = 'NO (Negative Reaction)';
                                        $presDotClass = 'bg-slate-400';
                                        $presReactionLabel = 'NO — Negative Reaction';
                                    } elseif (empty($rawPresumptive)) {
                                        $presReactionVal = '';
                                        $presDotClass = 'bg-slate-300';
                                        $presReactionLabel = 'Select Reaction Result...';
                                    }

                                    // 2. Parse Multiple Presumptive Test Specimen Rows
                                    $parsedPresumptiveRows = [];

                                    if (!empty($rawPresumptive) && !str_contains($upperPres, 'NO (') && !str_contains($upperPres, 'NEGATIVE')) {
                                        $cleanStr = preg_replace('/^YES\s*\((.*)\)$/i', '$1', trim($rawPresumptive));
                                        $rawSegments = explode(';', $cleanStr);
                                        foreach ($rawSegments as $segment) {
                                            $segment = trim($segment);
                                            if (empty($segment)) continue;

                                            $pPresence = '';
                                            $pPurity = '';

                                            if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $segment, $mP)) {
                                                $pPresence = trim($mP[1]);
                                            }
                                            if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $segment, $mPur)) {
                                                $pPurity = trim($mPur[1]);
                                            }

                                            if (!empty($pPresence) || !empty($pPurity)) {
                                                $parsedPresumptiveRows[] = [
                                                    'presence' => $pPresence,
                                                    'purity'   => $pPurity
                                                ];
                                            }
                                        }
                                    }

                                    if (empty($parsedPresumptiveRows) && !empty($rawPresumptive) && !str_contains($upperPres, 'NO (') && !str_contains($upperPres, 'NEGATIVE')) {
                                        $parsedPresumptiveRows[] = [
                                            'presence' => trim($rawPresumptive),
                                            'purity'   => ''
                                        ];
                                    }

                                    if (empty($parsedPresumptiveRows)) {
                                        $parsedPresumptiveRows[] = [
                                            'presence' => '',
                                            'purity'   => ''
                                        ];
                                    }
                                @endphp

                                <div>
                                    <label class="stylish-label">
                                        <span>Presumptive Test Reaction</span>
                                        @if(!$canEditPresumptive)<span class="text-[9px] text-amber-700 font-mono-code font-bold">READ-ONLY</span>@endif
                                    </label>
                                    <div class="relative z-40">
                                        <button 
                                            type="button" 
                                            id="presReactionDropdownTrigger" 
                                            onclick="toggleEditPresReactionDropdown(event)" 
                                            {{ !$canEditPresumptive ? 'disabled' : '' }} 
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span id="selectedPresReactionDot" class="w-2.5 h-2.5 rounded-full {{ $presDotClass }} shrink-0 shadow-xs"></span>
                                                <span id="selectedPresReactionLabel" class="font-extrabold text-slate-900 text-xs">
                                                    {{ $presReactionLabel }}
                                                </span>
                                            </div>
                                            <input type="hidden" id="presReactionSelect" name="presumptive_reaction_val" value="{{ $presReactionVal }}">
                                        </button>

                                        @if($canEditPresumptive)
                                            <div id="presReactionDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[60] p-2 space-y-1">
                                                <button type="button" onclick="selectEditPresReaction('YES (Positive Colorimetric Reaction)', 'YES — Positive Reaction', 'bg-emerald-500', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        <span class="font-extrabold text-slate-900">YES — Positive Reaction</span>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-mono-code font-bold rounded border border-emerald-200 uppercase">POSITIVE</span>
                                                </button>

                                                <button type="button" onclick="selectEditPresReaction('NO (Negative Reaction)', 'NO — Negative Reaction', 'bg-slate-400', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-colors cursor-pointer">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                                                        <span class="font-extrabold text-slate-900">NO — Negative Reaction</span>
                                                    </div>
                                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[9px] font-mono-code font-bold rounded border border-slate-200 uppercase">NEGATIVE</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div id="editPresumptiveDetailsContainer" class="sm:col-span-2 lg:col-span-3 bg-emerald-50/60 p-4 rounded-2xl border border-emerald-200/90 space-y-3.5 {{ str_contains($presReactionVal, 'NO') ? 'hidden' : '' }}">
                                    <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                        <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase font-mono-code tracking-wider">
                                            <span>Presumptive Test Reaction Specimen Results &amp; Purity</span>
                                        </div>
                                        <span class="text-[10px] font-mono-code font-extrabold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                            MULTIPLE SPECIMEN SUPPORT
                                        </span>
                                    </div>

                                    <div id="editPresumptiveTestRowsContainer" class="space-y-3">
                                        @foreach($parsedPresumptiveRows as $rIdx => $pRow)
                                            <div class="edit-presumptive-row p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5">
                                                <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                                    <span class="edit-test-row-title">Test Result #{{ $rIdx + 1 }}</span>
                                                    @if($canEditPresumptive)
                                                        <button type="button" onclick="removeEditPresumptiveTestRow(this)" class="remove-edit-test-row-btn {{ count($parsedPresumptiveRows) <= 1 ? 'hidden' : '' }} px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                                                            Remove
                                                        </button>
                                                    @endif
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="stylish-label mb-1">
                                                            <span>Presence of: (Presumptive)</span>
                                                        </label>
                                                        <input 
                                                            type="text" 
                                                            class="edit-pres-presence-input stylish-input font-bold text-slate-900 text-xs" 
                                                            value="{{ $pRow['presence'] }}" 
                                                            {{ !$canEditPresumptive ? 'readonly' : '' }} 
                                                            oninput="syncPresumptiveSummaryText()" 
                                                            onchange="syncPresumptiveSummaryText()" 
                                                            placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="stylish-label mb-1">
                                                            <span>Purity / Percentage: (Presumptive)</span>
                                                        </label>
                                                        <input 
                                                            type="text" 
                                                            class="edit-pres-purity-input stylish-input font-bold text-slate-900 text-xs font-mono-code" 
                                                            value="{{ $pRow['purity'] }}" 
                                                            {{ !$canEditPresumptive ? 'readonly' : '' }} 
                                                            oninput="syncPresumptiveSummaryText()" 
                                                            onchange="syncPresumptiveSummaryText()" 
                                                            placeholder="e.g. 85.5% or N/A"
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($canEditPresumptive)
                                        <div class="pt-1 flex items-center justify-between">
                                            <button type="button" onclick="addEditPresumptiveTestRow()" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-2xs transition-all cursor-pointer border-none space-x-1.5">
                                                <span>+ Add More Test Result</span>
                                            </button>
                                            <span class="text-[10px] text-slate-500 font-medium italic">Log additional chemical specimen reaction results</span>
                                        </div>
                                    @endif
                                </div>

                                <input type="hidden" id="presumptiveResultTextarea" name="presumptive_result" value="{{ $rawPresumptive }}">
                                @php
                                    $rawDetSubs = $case->detected_substances ?: ($docketData['lab_examination']['detected_substances'] ?? []);
                                    $existingDetectedSubstances = parseEditDetectedSubstances($rawDetSubs);
                                    if (empty($existingDetectedSubstances)) {
                                        $defaultSubName = $case->confirmatory_substance_identification ?: ($docketData['lab_examination']['substance_identification'] ?? ($case->seizure_name_drug ?: $case->seizure_name ?: 'Cocaine Hydrochloride'));
                                        $existingDetectedSubstances[] = [
                                            'name'       => $defaultSubName,
                                            'substance'  => $defaultSubName,
                                            'percentage' => ''
                                        ];
                                    }
                                @endphp

                                <div id="sampleWeightContainer" class="sm:col-span-2 lg:col-span-3 p-4 bg-emerald-50/70 rounded-2xl border border-emerald-200 space-y-4 shadow-2xs">
                                    <!-- SAMPLE WEIGHT / METROLOGY HEADER -->
                                    <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                        <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                            <span>SAMPLE WEIGHT</span>
                                        </div>
                                        <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                            CONFIRMATORY METROLOGY &amp; MASS
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- DESCRIPTION -->
                                        <div class="md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Description <span class="text-red-600 font-bold">*</span></span>
                                                <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="sampleWeightDescription" 
                                                name="sample_weight_description" 
                                                value="{{ $case->sample_weight_description ?: ($docketData['lab_examination']['sample_weight_description'] ?? ($case->seizure_name_drug ?: $case->seizure_name)) }}"
                                                readonly
                                                placeholder="Fetched from drug manifest"
                                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed"
                                            >
                                        </div>

                                        <!-- NUMBER OF UNITS -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Number of Units <span class="text-red-600 font-bold">*</span></span>
                                                <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="sampleWeightUnits" 
                                                name="sample_weight_units" 
                                                value="{{ $case->sample_weight_units ?: ($docketData['lab_examination']['sample_weight_units'] ?? $case->seizure_quantity) }}"
                                                readonly
                                                placeholder="Fetched from drug manifest"
                                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed"
                                            >
                                        </div>

                                        <!-- GROSS WEIGHT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Gross Weight <span class="text-red-600 font-bold">*</span></span>
                                                <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="sampleWeightGross" 
                                                name="sample_weight_gross" 
                                                value="{{ $case->sample_weight_gross ?: ($docketData['lab_examination']['sample_weight_gross'] ?? $case->seizure_weight) }}"
                                                readonly
                                                placeholder="Fetched from drug manifest"
                                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed"
                                            >
                                        </div>

                                        <!-- SAMPLE TAKEN -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Sample Taken <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <div class="relative flex rounded-xl shadow-2xs">
                                                <input 
                                                    type="text" 
                                                    id="sampleWeightTaken" 
                                                    name="sample_weight_taken" 
                                                    value="{{ $case->sample_weight_taken ?: ($docketData['lab_examination']['sample_weight_taken'] ?? '') }}"
                                                    placeholder="e.g. 10"
                                                    {{ !$canEditLab ? 'readonly' : '' }}
                                                    oninput="updateSampleWeightReserved()"
                                                    onchange="updateSampleWeightReserved()"
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0"
                                                >
                                                <span 
                                                    id="sampleWeightTakenUnitAddon"
                                                    class="inline-flex items-center px-3 py-2.5 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0 transition-colors duration-200"
                                                >
                                                    {{ $case->seizure_unit ?: 'Kilograms (kg)' }}
                                                </span>
                                            </div>
                                            <p id="sampleWeightTakenError" class="text-[11px] text-red-600 font-extrabold mt-1.5 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                                <span>Sample taken cannot exceed the gross weight</span>
                                            </p>
                                        </div>

                                        <!-- RESERVED SAMPLES -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Reserved Samples <span class="text-red-600 font-bold">*</span></span>
                                                <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-CALCULATED</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="sampleWeightReserved" 
                                                name="sample_weight_reserved" 
                                                readonly
                                                placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                                value="{{ $case->sample_weight_reserved ?: ($docketData['lab_examination']['sample_weight_reserved'] ?? '0') }}"
                                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed"
                                            >
                                        </div>

                                        <!-- RESULT / CONFIRMATORY ANALYSIS SECTION -->
                                        <div class="md:col-span-2 pt-4 border-t border-emerald-200/80 space-y-4">
                                            <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                                <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                                    <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                                </div>
                                                <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                                    ANALYTICAL FINDINGS &amp; SPECS
                                                </span>
                                            </div>

                                            <div class="space-y-4">
                                                <input type="hidden" id="confirmatorySubstanceIdentification" name="confirmatory_substance_identification" value="{{ $case->confirmatory_substance_identification ?: ($docketData['lab_examination']['substance_identification'] ?? ($case->seizure_name_drug ?: $case->seizure_name)) }}">

                                                <!-- 2. RESULT (INPUT BOX WITH ADD MORE) -->
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between">
                                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                            Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                                        </label>
                                                        @if($canEditLab)
                                                            <button 
                                                                type="button" 
                                                                onclick="addDetectedSubstanceRow()" 
                                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer"
                                                            >
                                                                <span>Add More</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div id="detectedSubstancesListContainer" class="space-y-2.5">
                                                        @foreach($existingDetectedSubstances as $dIdx => $dSub)
                                                            <div class="detected-substance-row flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs">
                                                                <div class="flex-1 w-full">
                                                                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected <span class="text-red-600 font-bold">*</span></label>
                                                                    <input 
                                                                        type="text" 
                                                                        name="detected_substances[][name]" 
                                                                        class="detected-substance-name w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" 
                                                                        placeholder="e.g. Cocaine Hydrochloride" 
                                                                        value="{{ $dSub['name'] }}"
                                                                        {{ !$canEditLab ? 'readonly' : '' }}
                                                                    >
                                                                </div>
                                                                <div class="w-full sm:w-48">
                                                                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage <span class="text-red-600 font-bold">*</span></label>
                                                                    <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                                                                        <input 
                                                                            type="text" 
                                                                            name="detected_substances[][percentage]" 
                                                                            class="detected-substance-percent w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" 
                                                                            placeholder="e.g. 85.4" 
                                                                            value="{{ $dSub['percentage'] }}"
                                                                            {{ !$canEditLab ? 'readonly' : '' }}
                                                                        >
                                                                        <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                                                    </div>
                                                                </div>
                                                                @if($canEditLab)
                                                                    <button 
                                                                        type="button" 
                                                                        onclick="removeDetectedSubstanceRow(this)" 
                                                                        class="mt-2 sm:mt-5 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 font-bold text-xs transition-colors shrink-0 cursor-pointer" 
                                                                        title="Remove Row"
                                                                    >
                                                                        Remove
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <!-- 3. TEST METHODS INPUT -->
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Test Methods <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        id="confirmatoryTestMethods" 
                                                        name="confirmatory_test_methods" 
                                                        value="{{ $case->confirmatory_test_methods ?: ($docketData['lab_examination']['test_methods'] ?? '') }}"
                                                        placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                                                        {{ !$canEditLab ? 'readonly' : '' }}
                                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400"
                                                    >
                                                </div>

                                                <!-- 4. SPECIFICATIONS TEXTAREA INPUT -->
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Specifications <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                    </label>
                                                    <textarea 
                                                        id="confirmatorySpecifications" 
                                                        name="confirmatory_specifications" 
                                                        rows="3" 
                                                        placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                                        {{ !$canEditLab ? 'readonly' : '' }}
                                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                    >{{ $case->confirmatory_specifications ?: ($docketData['lab_examination']['specifications'] ?? '') }}</textarea>
                                                </div>

                                                <!-- 5. REMARK TEXTAREA INPUT -->
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Remark <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                    </label>
                                                    <textarea 
                                                        id="confirmatoryRemark" 
                                                        name="confirmatory_remark" 
                                                        rows="3" 
                                                        placeholder="Enter any additional remarks or observations regarding the confirmatory analysis..."
                                                        {{ !$canEditLab ? 'readonly' : '' }}
                                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                    >{{ $case->confirmatory_remark ?: ($docketData['lab_examination']['remark'] ?? '') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="stylish-label">
                                        <span>Laboratory Analytical Findings &amp; Official Conclusion</span>
                                        @if(!$canEditLab)<span class="text-[9px] text-amber-700">READ-ONLY</span>@endif
                                    </label>
                                    <textarea id="input_findings" name="findings" rows="4" {{ !$canEditLab ? 'readonly' : '' }} class="stylish-input leading-relaxed font-normal" placeholder="Enter official forensic chemical laboratory opinion and analytical conclusions...">{{ $case->laboratory_analytical_findings }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="pt-6 border-t border-slate-200 flex items-center justify-between shrink-0">
                        <a href="/cases/{{ $case->case_number }}/review" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-all text-decoration-none">
                            Cancel &amp; Return to Review
                        </a>
                        <button type="submit" class="px-6 py-2.5 text-xs font-extrabold text-slate-950 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 rounded-xl shadow-md shadow-amber-500/20 border border-amber-400/50 transition-all cursor-pointer inline-flex items-center gap-2 active:scale-95">
                            <span>Save Changes &amp; Synchronize Case</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<!-- MUGSHOT LIGHTBOX MODAL -->
<div id="arrestMugshotLightboxModal" class="hidden fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[99999] flex items-center justify-center p-4" onclick="closeArrestMugshotModal()">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl space-y-4 relative border border-slate-200" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h4 id="arrestMugshotModalTitle" class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">Facial Biometric High-Res View</h4>
            <button type="button" onclick="closeArrestMugshotModal()" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold border border-slate-300 transition-colors cursor-pointer">
                Close
            </button>
        </div>
        <div class="flex items-center justify-center bg-slate-900 rounded-xl p-3 min-h-[350px] max-h-[70vh] overflow-hidden">
            <img id="arrestMugshotModalImage" src="" alt="Full Mugshot View" class="max-h-[65vh] max-w-full object-contain rounded-lg shadow-lg">
        </div>
        <div class="flex justify-end pt-2">
            <button type="button" onclick="closeArrestMugshotModal()" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                Close Preview
            </button>
        </div>
    </div>
</div>

<script>
    function switchEditTab(tabNum) {
        document.querySelectorAll('.edit-tab-btn').forEach((btn, idx) => {
            if (idx + 1 === tabNum) {
                btn.classList.add('border-amber-600', 'text-slate-900', 'font-black', 'bg-white', 'shadow-xs', 'border-t', 'border-x', 'rounded-t-xl');
                btn.classList.remove('border-transparent', 'text-slate-500', 'font-bold', 'hover:bg-white/60');
            } else {
                btn.classList.remove('border-amber-600', 'text-slate-900', 'font-black', 'bg-white', 'shadow-xs', 'border-t', 'border-x', 'rounded-t-xl');
                btn.classList.add('border-transparent', 'text-slate-500', 'font-bold', 'hover:bg-white/60');
            }
        });

        document.querySelectorAll('.edit-tab-content').forEach((content, idx) => {
            if (idx + 1 === tabNum) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
    }

    function calculateAgeFromDOB() {
        const dobInput = document.getElementById('newArrestedDOB');
        const ageInput = document.getElementById('newArrestedAge');
        if (dobInput && ageInput) {
            if (dobInput.value) {
                const birthDate = new Date(dobInput.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age >= 0 && !isNaN(age)) {
                    ageInput.value = age;
                }
            } else if (!ageInput.value) {
                ageInput.value = '';
            }
        }
    }

    function handleArrestMugshotDragOver(e, dropzoneEl) {
        e.preventDefault();
        e.stopPropagation();
        if (dropzoneEl) dropzoneEl.classList.add('border-amber-500', 'bg-amber-50/50');
    }

    function handleArrestMugshotDragLeave(e, dropzoneEl) {
        e.preventDefault();
        e.stopPropagation();
        if (dropzoneEl) dropzoneEl.classList.remove('border-amber-500', 'bg-amber-50/50');
    }

    function handleArrestMugshotDrop(e, angleKey) {
        e.preventDefault();
        e.stopPropagation();
        const dropzoneEl = e.currentTarget;
        if (dropzoneEl) dropzoneEl.classList.remove('border-amber-500', 'bg-amber-50/50');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const fileInput = document.getElementById(`file-${angleKey}`);
            if (fileInput) {
                fileInput.files = e.dataTransfer.files;
                previewMugshotFileArrest(fileInput, angleKey);
            }
        }
    }

    function triggerMugshotUpload(angleKey) {
        const input = document.getElementById(`file-${angleKey}`);
        if (input) input.click();
    }

    function previewMugshot(event, angleKey) {
        previewMugshotFileArrest(event.target, angleKey);
    }

    function previewMugshotFileArrest(fileInput, angleKey) {
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                applyArrestMugshotPreview(dataUrl, angleKey);
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
    }

    function previewMugshotUrlArrest(urlStr, angleKey) {
        const val = (urlStr || '').trim();
        if (val) {
            applyArrestMugshotPreview(val, angleKey);
        } else {
            clearArrestMugshotState(angleKey);
        }
    }

    function applyArrestMugshotPreview(srcUrl, angleKey) {
        const img = document.getElementById(`preview-${angleKey}`);
        const placeholder = document.getElementById(`placeholder-${angleKey}`);
        const actions = document.getElementById(`actions-${angleKey}`);
        const badge = document.getElementById(`badge-${angleKey}`);
        const hidden = document.getElementById(`hidden-${angleKey}`);

        if (img) {
            img.src = srcUrl;
            img.classList.remove('hidden');
        }
        if (placeholder) placeholder.classList.add('hidden');
        if (actions) actions.classList.remove('hidden');
        if (hidden) hidden.value = srcUrl;
        if (badge) {
            badge.innerText = 'Uploaded';
            badge.className = 'text-[9px] font-mono-code font-bold text-emerald-600 uppercase';
        }

        if (typeof activeSuspectIndex !== 'undefined' && activeSuspectIndex >= 0 && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
            const s = suspectsData[activeSuspectIndex];
            s.mugshots = s.mugshots || {};
            if (angleKey === 'front') {
                s.mugshots.front = srcUrl;
                s.mugshots['front-profile'] = srcUrl;
                s.mugshot_front = srcUrl;
            } else if (angleKey === 'left' || angleKey === 'left-profile') {
                s.mugshots.left = srcUrl;
                s.mugshots['left-profile'] = srcUrl;
                s.mugshot_left = srcUrl;
            } else if (angleKey === 'right' || angleKey === 'right-profile') {
                s.mugshots.right = srcUrl;
                s.mugshots['right-profile'] = srcUrl;
                s.mugshot_right = srcUrl;
            }
        }
    }

    function clearArrestMugshotPhoto(angleKey) {
        const fileInput = document.getElementById(`file-${angleKey}`);
        const urlInput = document.getElementById(`url-${angleKey}`);
        if (fileInput) fileInput.value = '';
        if (urlInput) urlInput.value = '';
        clearArrestMugshotState(angleKey);
    }

    function clearArrestMugshotState(angleKey) {
        const img = document.getElementById(`preview-${angleKey}`);
        const placeholder = document.getElementById(`placeholder-${angleKey}`);
        const actions = document.getElementById(`actions-${angleKey}`);
        const badge = document.getElementById(`badge-${angleKey}`);
        const hidden = document.getElementById(`hidden-${angleKey}`);

        if (img) {
            img.src = '';
            img.classList.add('hidden');
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (actions) actions.classList.add('hidden');
        if (hidden) hidden.value = '';
        if (badge) {
            badge.innerText = 'Not Uploaded';
            badge.className = 'text-[9px] font-mono-code font-bold text-slate-400 uppercase';
        }

        if (typeof activeSuspectIndex !== 'undefined' && activeSuspectIndex >= 0 && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
            const s = suspectsData[activeSuspectIndex];
            s.mugshots = s.mugshots || {};
            if (angleKey === 'front') {
                s.mugshots.front = '';
                s.mugshots['front-profile'] = '';
                s.mugshot_front = '';
            } else if (angleKey === 'left' || angleKey === 'left-profile') {
                s.mugshots.left = '';
                s.mugshots['left-profile'] = '';
                s.mugshot_left = '';
            } else if (angleKey === 'right' || angleKey === 'right-profile') {
                s.mugshots.right = '';
                s.mugshots['right-profile'] = '';
                s.mugshot_right = '';
            }
        }
    }

    function openArrestMugshotModal(imgSrc, titleText) {
        const modal = document.getElementById('arrestMugshotLightboxModal');
        const modalImg = document.getElementById('arrestMugshotModalImage');
        const modalTitle = document.getElementById('arrestMugshotModalTitle');
        if (modal && modalImg) {
            modalImg.src = imgSrc;
            if (modalTitle) modalTitle.innerText = titleText || 'Facial Biometric High-Res View';
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
    }

    function closeArrestMugshotModal() {
        const modal = document.getElementById('arrestMugshotLightboxModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    function toggleEditOperationTypeDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('operationTypeDropdownMenu');
        const chevron = document.getElementById('opTypeChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('operationTypeDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditOperationType(val, label, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('operationTypeSelect');
        const labelEl = document.getElementById('selectedOpTypeLabel');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            if (!val) {
                labelEl.className = 'font-bold text-slate-400 text-xs';
            } else {
                labelEl.className = 'font-extrabold text-slate-900 text-xs';
            }
        }
        
        closeAllEditCustomDropdowns();
    }

    /* PRIORITY DROPDOWN HANDLERS */
    function toggleEditPriorityDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('priorityDropdownMenu');
        const chevron = document.getElementById('priorityChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('priorityDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditPriority(val, label, dotClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('casePrioritySelect');
        const labelEl = document.getElementById('selectedPriorityLabel');
        const dotEl = document.getElementById('selectedPriorityDot');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) labelEl.textContent = label;
        if (dotEl) dotEl.className = `w-2.5 h-2.5 rounded-full ${dotClass} shrink-0 shadow-xs`;
        
        closeAllEditCustomDropdowns();
    }

    /* GENDER DROPDOWN HANDLERS */
    function toggleEditGenderDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('genderDropdownMenu');
        const chevron = document.getElementById('genderChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('genderDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditGender(val, label, symbol, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('genderSelect');
        const labelEl = document.getElementById('selectedGenderLabel');
        const symbolEl = document.getElementById('selectedGenderSymbol');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (symbolEl) {
            symbolEl.textContent = symbol;
            symbolEl.className = `w-5 h-5 rounded-full ${badgeClass} flex items-center justify-center text-[10px] font-bold shrink-0`;
        }
        
        closeAllEditCustomDropdowns();
    }

    /* MARITAL STATUS DROPDOWN HANDLERS */
    function toggleEditMaritalDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('maritalDropdownMenu');
        const chevron = document.getElementById('maritalChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('maritalDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditMarital(val, label, code, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('maritalSelect');
        const labelEl = document.getElementById('selectedMaritalLabel');
        const codeEl = document.getElementById('selectedMaritalCode');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (codeEl) {
            codeEl.textContent = code;
            codeEl.className = `w-6 h-5 rounded-md ${badgeClass} flex items-center justify-center text-[9px] font-mono-code font-extrabold shrink-0`;
        }

        const relBox = document.getElementById('relationshipDetailsBox');
        const relTitle = document.getElementById('relationshipTitle');
        const relSubtitle = document.getElementById('relationshipSubtitle');
        const mLower = (val || '').toLowerCase();

        if (relBox) {
            if (mLower.includes('married') || mLower.includes('dating') || mLower.includes('relationship')) {
                relBox.classList.remove('hidden');
                if (relTitle) {
                    relTitle.innerText = (mLower.includes('dating') || mLower.includes('relationship')) ? 'Dating / Relationship Partner Details' : 'Spouse Information (Marriage Record)';
                }
                if (relSubtitle) {
                    relSubtitle.innerText = (mLower.includes('dating') || mLower.includes('relationship')) ? 'Mandatory record for suspect dating partner identity, phone contact, and residence' : 'Mandatory record for suspect legal spouse identity, phone contact, and occupation';
                }
            } else {
                relBox.classList.add('hidden');
            }
        }
        
        closeAllEditCustomDropdowns();
    }

    /* EXHIBIT CATEGORY DROPDOWN HANDLERS */
    function toggleEditCategoryDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('categoryDropdownMenu');
        const chevron = document.getElementById('categoryChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('categoryDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditCategory(val, label, badge, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('exhibitCategorySelect');
        const labelEl = document.getElementById('selectedCategoryLabel');
        const badgeEl = document.getElementById('selectedCategoryBadge');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (badgeEl) {
            badgeEl.textContent = badge;
            badgeEl.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass} shrink-0`;
        }

        const liveCatBadge = document.getElementById('tab2_live_cat_badge');
        const liveCatText = document.getElementById('tab2_live_cat_text');
        if (liveCatBadge) {
            liveCatBadge.textContent = badge;
            liveCatBadge.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass}`;
        }
        if (liveCatText) {
            liveCatText.textContent = label;
        }
        
        closeAllEditCustomDropdowns();
    }

    /* PACKAGING UNIT TYPE DROPDOWN HANDLERS */
    function toggleEditUnitDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('unitDropdownMenu');
        const chevron = document.getElementById('unitChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('unitDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditUnit(val, label, code, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('seizureUnitSelect');
        const labelEl = document.getElementById('selectedUnitLabel');
        const badgeEl = document.getElementById('selectedUnitBadge');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (badgeEl) {
            badgeEl.textContent = code;
            badgeEl.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass} shrink-0`;
        }
        
        closeAllEditCustomDropdowns();
        if (typeof syncExhibitToLabAndSelector === 'function') {
            syncExhibitToLabAndSelector();
        }
    }

    /* CONFIRMATORY TEST STATUS DROPDOWN HANDLERS */
    function toggleEditConfirmatoryStatusDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('confirmatoryStatusDropdownMenu');
        const chevron = document.getElementById('confirmatoryStatusChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('confirmatoryStatusDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditConfirmatoryStatus(val, label, dotClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('confirmatoryStatusSelect');
        const labelEl = document.getElementById('selectedConfirmatoryStatusLabel');
        const dotEl = document.getElementById('selectedConfirmatoryStatusDot');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) labelEl.textContent = label;
        if (dotEl) dotEl.className = `w-2.5 h-2.5 rounded-full ${dotClass} shrink-0 shadow-xs`;
        
        closeAllEditCustomDropdowns();
    }

    /* CONFIRMED SUBSTANCE DROPDOWN HANDLERS */
    function toggleEditSubstanceDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('substanceDropdownMenu');
        const chevron = document.getElementById('substanceChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('substanceDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditSubstance(val, label, code, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('confirmatorySubstanceSelect');
        const labelEl = document.getElementById('selectedSubstanceLabel');
        const badgeEl = document.getElementById('selectedSubstanceBadge');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (badgeEl) {
            badgeEl.textContent = code;
            badgeEl.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass} shrink-0`;
        }
        
        closeAllEditCustomDropdowns();
    }

    /* PRESUMPTIVE TEST REACTION DROPDOWN HANDLERS */
    function toggleEditPresReactionDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('presReactionDropdownMenu');
        const chevron = document.getElementById('presReactionChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('presReactionDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditPresReaction(val, label, dotClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('presReactionSelect');
        const labelEl = document.getElementById('selectedPresReactionLabel');
        const dotEl = document.getElementById('selectedPresReactionDot');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) labelEl.textContent = label;
        if (dotEl) dotEl.className = `w-2.5 h-2.5 rounded-full ${dotClass} shrink-0 shadow-xs`;
        
        closeAllEditCustomDropdowns();
        syncPresumptiveSummaryText();
    }

    /* PRESUMPTIVE PRESENCE OF DROPDOWN HANDLERS */
    function toggleEditPresPresenceDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('presPresenceDropdownMenu');
        const chevron = document.getElementById('presPresenceChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('presPresenceDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditPresPresence(val, label, code, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('presPresenceSelect');
        const labelEl = document.getElementById('selectedPresPresenceLabel');
        const badgeEl = document.getElementById('selectedPresPresenceBadge');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (badgeEl) {
            badgeEl.textContent = code;
            badgeEl.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass} shrink-0`;
        }
        
        closeAllEditCustomDropdowns();
        syncPresumptiveSummaryText();
    }

    /* MULTI-SPECIMEN PRESUMPTIVE TEST ROW HANDLERS */
    function addEditPresumptiveTestRow() {
        const container = document.getElementById('editPresumptiveTestRowsContainer');
        if (!container) return;

        const rows = container.querySelectorAll('.edit-presumptive-row');
        const newIdx = rows.length + 1;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'edit-presumptive-row p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5';
        rowDiv.innerHTML = `
            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                <span class="edit-test-row-title">Test Result #${newIdx}</span>
                <button type="button" onclick="removeEditPresumptiveTestRow(this)" class="remove-edit-test-row-btn px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                    Remove
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="stylish-label mb-1">
                        <span>Presence of: (Presumptive)</span>
                    </label>
                    <input 
                        type="text" 
                        class="edit-pres-presence-input stylish-input font-bold text-slate-900 text-xs" 
                        oninput="syncPresumptiveSummaryText()" 
                        onchange="syncPresumptiveSummaryText()" 
                        placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                    >
                </div>
                <div>
                    <label class="stylish-label mb-1">
                        <span>Purity / Percentage: (Presumptive)</span>
                    </label>
                    <input 
                        type="text" 
                        class="edit-pres-purity-input stylish-input font-bold text-slate-900 text-xs font-mono-code" 
                        oninput="syncPresumptiveSummaryText()" 
                        onchange="syncPresumptiveSummaryText()" 
                        placeholder="e.g. 85.5% or N/A"
                    >
                </div>
            </div>
        `;
        container.appendChild(rowDiv);
        updateEditPresumptiveRemoveButtons();
        syncPresumptiveSummaryText();
    }

    function removeEditPresumptiveTestRow(btn) {
        const row = btn.closest('.edit-presumptive-row');
        if (row) {
            row.remove();
            reindexEditPresumptiveTestRows();
            updateEditPresumptiveRemoveButtons();
            syncPresumptiveSummaryText();
        }
    }

    function reindexEditPresumptiveTestRows() {
        const container = document.getElementById('editPresumptiveTestRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.edit-presumptive-row');
        rows.forEach((row, idx) => {
            const titleSpan = row.querySelector('.edit-test-row-title');
            if (titleSpan) titleSpan.innerText = `Test Result #${idx + 1}`;
        });
    }

    function updateEditPresumptiveRemoveButtons() {
        const container = document.getElementById('editPresumptiveTestRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.edit-presumptive-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-edit-test-row-btn');
            if (btn) {
                if (rows.length > 1) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });
    }

    function syncPresumptiveSummaryText() {
        const reactionInput = document.getElementById('presReactionSelect');
        const reaction = reactionInput ? reactionInput.value : 'YES (Positive Colorimetric Reaction)';
        const textarea = document.getElementById('presumptiveResultTextarea');
        const detailsContainer = document.getElementById('editPresumptiveDetailsContainer');
        
        if (reaction.startsWith('NO')) {
            if (textarea) textarea.value = 'NO (Negative Reaction)';
            if (detailsContainer) detailsContainer.classList.add('hidden');
            return;
        } else {
            if (detailsContainer) detailsContainer.classList.remove('hidden');
        }

        if (!textarea) return;

        const container = document.getElementById('editPresumptiveTestRowsContainer');
        if (!container) return;

        const rows = container.querySelectorAll('.edit-presumptive-row');
        let results = [];

        rows.forEach((row, idx) => {
            const presenceInput = row.querySelector('.edit-pres-presence-input');
            const purityInput = row.querySelector('.edit-pres-purity-input');

            const presence = presenceInput ? presenceInput.value.trim() : '';
            let purity = purityInput ? purityInput.value.trim() : '';

            let parts = [];
            if (presence) parts.push(`Presence of: ${presence}`);
            if (purity) parts.push(`Purity/Percentage: ${purity}`);

            if (parts.length > 0) {
                if (rows.length > 1) {
                    results.push(`[${idx + 1}] ${parts.join(' - ')}`);
                } else {
                    results.push(parts.join(' - '));
                }
            }
        });

        if (results.length > 0) {
            textarea.value = `YES (${results.join('; ')})`;
        } else {
            textarea.value = `YES (Positive Colorimetric Reaction)`;
        }
    }

    /* TEST METHODS USED DROPDOWN HANDLERS */
    function toggleEditTestMethodDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('testMethodDropdownMenu');
        const chevron = document.getElementById('testMethodChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        closeAllEditCustomDropdowns('testMethodDropdownMenu');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function selectEditTestMethod(val, label, code, badgeClass, e) {
        if (e) e.stopPropagation();
        const hiddenInput = document.getElementById('testMethodSelect');
        const labelEl = document.getElementById('selectedTestMethodLabel');
        const badgeEl = document.getElementById('selectedTestMethodBadge');
        
        if (hiddenInput) hiddenInput.value = val;
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.className = val ? 'font-extrabold text-slate-900 text-xs' : 'font-bold text-slate-400 text-xs';
        }
        if (badgeEl) {
            badgeEl.textContent = code;
            badgeEl.className = `px-1.5 py-0.5 rounded text-[9px] font-mono-code font-extrabold border ${badgeClass} shrink-0`;
        }
        
        closeAllEditCustomDropdowns();
    }

    function closeAllEditCustomDropdowns(exceptId = null) {
        const dropdowns = ['operationTypeDropdownMenu', 'priorityDropdownMenu', 'genderDropdownMenu', 'maritalDropdownMenu', 'categoryDropdownMenu', 'unitDropdownMenu', 'confirmatoryStatusDropdownMenu', 'substanceDropdownMenu', 'presReactionDropdownMenu', 'testMethodDropdownMenu'];
        const chevrons = {
            'operationTypeDropdownMenu': 'opTypeChevron', 
            'priorityDropdownMenu': 'priorityChevron', 
            'genderDropdownMenu': 'genderChevron', 
            'maritalDropdownMenu': 'maritalChevron',
            'categoryDropdownMenu': 'categoryChevron',
            'unitDropdownMenu': 'unitChevron',
            'confirmatoryStatusDropdownMenu': 'confirmatoryStatusChevron',
            'substanceDropdownMenu': 'substanceChevron',
            'presReactionDropdownMenu': 'presReactionChevron',
            'testMethodDropdownMenu': 'testMethodChevron'
        };
        
        dropdowns.forEach(id => {
            if (id !== exceptId) {
                const menu = document.getElementById(id);
                const chevron = document.getElementById(chevrons[id]);
                if (menu) menu.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        });
    }

    /* DETECTED SUBSTANCES DYNAMIC ROWS & AUTO-CALCULATED RESERVED WEIGHT HANDLERS */
    function addDetectedSubstanceRow(substance = '', percentage = '') {
        const container = document.getElementById('detectedSubstancesListContainer');
        if (!container) return;
        const canEdit = @json($canEditLab);
        const readonlyAttr = canEdit ? '' : 'readonly';
        const removeBtnHtml = canEdit ? `
            <button type="button" onclick="removeDetectedSubstanceRow(this)" class="mt-2 sm:mt-5 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 font-bold text-xs transition-colors shrink-0 cursor-pointer" title="Remove Row">
                Remove
            </button>
        ` : '';

        const rowDiv = document.createElement('div');
        rowDiv.className = 'detected-substance-row flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
        rowDiv.innerHTML = `
            <div class="flex-1 w-full">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected <span class="text-red-600 font-bold">*</span></label>
                <input type="text" name="detected_substances[][name]" class="detected-substance-name w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}" ${readonlyAttr}>
            </div>
            <div class="w-full sm:w-48">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage <span class="text-red-600 font-bold">*</span></label>
                <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                    <input type="text" name="detected_substances[][percentage]" class="detected-substance-percent w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}" ${readonlyAttr}>
                    <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                </div>
            </div>
            ${removeBtnHtml}
        `;
        container.appendChild(rowDiv);
    }

    function removeDetectedSubstanceRow(btn) {
        const row = btn.closest('.detected-substance-row');
        if (row) row.remove();
    }

    function toKg(num, unit) {
        if (!unit) return null;
        const u = unit.toLowerCase();
        if (u === 'kg') return num;
        if (u === 'g') return num / 1000;
        if (u === 'mg') return num / 1000000;
        if (u === 'lb') return num * 0.453592;
        return null;
    }

    function formatNum(num) {
        if (num < 0) num = 0;
        const rounded = Math.round(num * 10000) / 10000;
        return rounded.toString();
    }

    function findWeightChunk(parts) {
        if (!parts || parts.length === 0) return '';
        for (let i = parts.length - 1; i >= 0; i--) {
            const lower = parts[i].toLowerCase();
            if (/\b(kg|kgs|kilogram|kilograms|g|gm|gms|gram|grams|mg|mgs|milligram|milligrams|lb|lbs|pound|pounds)\b/i.test(lower) || /\d\s*(kg|g|mg|lb)\b/i.test(lower)) {
                return parts[i];
            }
        }
        for (let i = parts.length - 1; i >= 0; i--) {
            if (/[\d]+/.test(parts[i])) {
                return parts[i];
            }
        }
        return parts[parts.length - 1];
    }

    function parseParts(str) {
        return str.split('/').map(p => p.trim()).filter(p => p.length > 0);
    }

    function parseQtyAndUnit(chunk) {
        if (!chunk) return null;
        const numMatch = chunk.match(/([\d]+(?:\.[\d]+)?)/);
        if (!numMatch) return null;
        const num = parseFloat(numMatch[1]);
        if (isNaN(num)) return null;

        const lower = chunk.toLowerCase();
        let unit = '';

        if (/\b(kg|kgs|kilogram|kilograms)\b/i.test(lower) || /\d\s*kg\b/i.test(lower)) {
            unit = 'kg';
        } else if (/\b(mg|mgs|milligram|milligrams)\b/i.test(lower) || /\d\s*mg\b/i.test(lower)) {
            unit = 'mg';
        } else if (/\b(g|gm|gms|gram|grams)\b/i.test(lower) || /\d\s*g\b/i.test(lower)) {
            unit = 'g';
        } else if (/\b(lb|lbs|pound|pounds)\b/i.test(lower) || /\d\s*lb\b/i.test(lower)) {
            unit = 'lb';
        } else {
            const idx = chunk.indexOf(numMatch[1]);
            if (idx !== -1) {
                const afterNum = chunk.substring(idx + numMatch[1].length).trim();
                if (afterNum) unit = afterNum;
            }
        }
        return { num, unit };
    }

    function computeReservedSample(grossStr, takenStr) {
        if (!grossStr || typeof grossStr !== 'string' || !grossStr.trim()) {
            return { reserved: '0', isOverflow: false };
        }

        const grossParts = parseParts(grossStr);
        let targetGrossChunk = findWeightChunk(grossParts);
        const gParsed = parseQtyAndUnit(targetGrossChunk);

        const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

        if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        const takenParts = parseParts(takenStr);
        let targetTakenChunk = findWeightChunk(takenParts);
        const tParsed = parseQtyAndUnit(targetTakenChunk);

        if (!gParsed || !tParsed) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        let gKg = toKg(gParsed.num, gParsed.unit);
        let tKg = toKg(tParsed.num, tParsed.unit);

        if (gKg !== null && tKg === null && !tParsed.unit) {
            tKg = toKg(tParsed.num, gParsed.unit);
        }

        if (gKg !== null && tKg !== null) {
            if (tKg > gKg) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diffKg = gKg - tKg;
            const gU = gParsed.unit.toLowerCase();
            let resVal = '0';
            if (gU === 'g' || gU === 'gm' || gU === 'gram' || gU === 'grams') {
                resVal = formatNum(diffKg * 1000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else if (gU === 'mg' || gU === 'milligram' || gU === 'milligrams') {
                resVal = formatNum(diffKg * 1000000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else {
                resVal = formatNum(diffKg) + (gParsed.unit ? ' ' + gParsed.unit : '');
            }
            return { reserved: resVal, isOverflow: false };
        } else {
            if (tParsed.num > gParsed.num) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diff = gParsed.num - tParsed.num;
            const unit = gParsed.unit || tParsed.unit;
            return { reserved: formatNum(diff) + (unit ? ' ' + unit : ''), isOverflow: false };
        }
    }

    function updateSampleWeightReserved() {
        const grossInput = document.getElementById('sampleWeightGross');
        const takenInput = document.getElementById('sampleWeightTaken');
        const reservedInput = document.getElementById('sampleWeightReserved');
        const errorMsgEl = document.getElementById('sampleWeightTakenError');

        if (!grossInput || !reservedInput) return;

        const grossStr = grossInput.value || '';
        const takenStr = takenInput ? takenInput.value : '';

        const result = computeReservedSample(grossStr, takenStr);

        reservedInput.value = result.reserved;

        if (result.isOverflow) {
            if (errorMsgEl) errorMsgEl.classList.remove('hidden');
            if (takenInput) {
                takenInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
            }
        } else {
            if (errorMsgEl) errorMsgEl.classList.add('hidden');
            if (takenInput) {
                takenInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
            }
        }
    }

    document.addEventListener('click', function(e) {
        closeAllEditCustomDropdowns();
    });

    /* MULTI-SUSPECT & LINKED EXHIBIT STATE MANAGEMENT */
    const suspectsData = @json($suspectsList);
    const exhibitsData = @json($exhibitsList);
    const labsData = @json($labsList);
    let activeSuspectIndex = -1;

    function saveCurrentSuspectState() {
        if (!suspectsData[activeSuspectIndex]) return;
        const s = suspectsData[activeSuspectIndex];

        // 1. Personal details (independent per suspect)
        const newName = document.getElementById('input_suspect_name')?.value || '';
        const titleInput = document.querySelector('input[name="title"]');

        if (titleInput && s.last_known_name && s.last_known_name !== newName) {
            const oldName = s.last_known_name;
            let curTitle = titleInput.value;
            if (curTitle && oldName) {
                const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const re = new RegExp('\\b' + escapeRegex(oldName) + '\\b', 'g');
                if (re.test(curTitle)) {
                    curTitle = curTitle.replace(re, newName);
                } else if (curTitle.includes(oldName)) {
                    curTitle = curTitle.replaceAll(oldName, newName);
                }
                titleInput.value = curTitle;
            }
        }

        s.suspect_name = newName;
        s.last_known_name = newName;
        s.suspect_alias = document.getElementById('input_suspect_alias')?.value || '';
        s.ghana_card = document.getElementById('input_ghana_card')?.value || '';
        s.gender = document.getElementById('genderSelect')?.value || 'Male';
        s.dob = document.getElementById('newArrestedDOB')?.value || '';
        s.age = document.getElementById('newArrestedAge')?.value || '';
        s.height = document.getElementById('input_height')?.value || '';
        s.height_unit = document.getElementById('input_height_unit')?.value || 'CM';
        s.eye_color = document.getElementById('input_eye_color')?.value || '';
        s.nationality = document.getElementById('input_nationality')?.value || '';
        s.place_of_birth = document.getElementById('input_place_of_birth')?.value || '';
        s.pob = document.getElementById('input_place_of_birth')?.value || '';
        s.hometown = document.getElementById('input_hometown')?.value || '';
        s.phone = document.getElementById('input_phone')?.value || '';
        s.arrest_date = document.getElementById('input_arrest_date')?.value || '';
        s.arrest_time = document.getElementById('input_arrest_time')?.value || '';
        s.address = document.getElementById('input_address')?.value || '';
        s.raid_location = document.getElementById('input_raid_location')?.value || '';
        s.marital_status = document.getElementById('maritalSelect')?.value || 'Single';
        s.spouse_name = document.getElementById('input_spouse_name')?.value || '';
        s.spouse_phone = document.getElementById('input_spouse_phone')?.value || '';
        s.spouse_info = document.getElementById('input_spouse_info')?.value || '';
        s.partner_name = s.spouse_name;
        s.partner_phone = s.spouse_phone;
        s.partner_info = s.spouse_info;
        s.children_count = document.getElementById('input_children_count')?.value || '0';
        s.has_children = parseInt(s.children_count) > 0;
        s.dependents = parseInt(s.children_count) > 0 ? (s.children_count + ' Kids') : 'No Children';
        s.spouse_summary = s.spouse_name ? (`Spouse: ${s.spouse_name}` + (s.spouse_phone ? ` (${s.spouse_phone})` : '') + (s.spouse_info ? ` - ${s.spouse_info}` : '')) : 'N/A';
        s.holding_facility = document.getElementById('input_holding_facility')?.value || '';
        s.offense_description = document.getElementById('input_offense_description')?.value || '';

        // Mugshots
        s.mugshots = s.mugshots || {};
        const frontVal = document.getElementById('hidden-front')?.value || '';
        const leftVal = document.getElementById('hidden-left-profile')?.value || '';
        const rightVal = document.getElementById('hidden-right-profile')?.value || '';

        s.mugshots.front = frontVal;
        s.mugshots['front-profile'] = frontVal;
        s.mugshot_front = frontVal;

        s.mugshots.left = leftVal;
        s.mugshots['left-profile'] = leftVal;
        s.mugshot_left = leftVal;

        s.mugshots.right = rightVal;
        s.mugshots['right-profile'] = rightVal;
        s.mugshot_right = rightVal;

        // 2. Exhibit details (saved to shared exhibitsData[exIdx])
        const exIdx = typeof s.exhibit_index !== 'undefined' ? s.exhibit_index : 0;
        if (exhibitsData[exIdx]) {
            const ex = exhibitsData[exIdx];
            ex.seizure_name_drug = document.getElementById('input_seizure_name_drug')?.value || '';
            ex.exhibit_category = document.getElementById('exhibitCategorySelect')?.value || '';
            ex.seizure_quantity = document.getElementById('input_seizure_quantity')?.value || '';
            ex.seizure_weight = document.getElementById('input_seizure_weight')?.value || '';
            ex.seizure_unit = document.getElementById('seizureUnitSelect')?.value || '';
            ex.seizure_packaging = document.getElementById('input_seizure_packaging')?.value || '';
            ex.seizure_reason = document.getElementById('input_seizure_reason')?.value || '';
            ex.evidence_photos = ex.evidence_photos || [];
            ex.ammunition_details = {
                cartridge_appearance: document.getElementById('input_ammo_cartridge_appearance')?.value || '',
                projectile_bullet_type: document.getElementById('input_ammo_projectile_bullet_type')?.value || '',
                cartridge_case_material: document.getElementById('input_ammo_cartridge_case_material')?.value || '',
                colour_markings: document.getElementById('input_ammo_colour_markings')?.value || '',
                condition: document.getElementById('input_ammo_condition')?.value || '',
                other_condition: document.getElementById('input_ammo_other_condition')?.value || '',
                packaging_type: document.getElementById('input_ammo_packaging_type')?.value || '',
                seal_tamper_evident_condition: document.getElementById('input_ammo_seal_condition')?.value || '',
                package_reference_number: document.getElementById('input_ammo_package_reference_number')?.value || '',
                condition_when_received: document.getElementById('input_ammo_condition_when_received')?.value || ''
            };

            // Mirror shared exhibit to all suspects linking to exIdx
            suspectsData.forEach(sp => {
                if ((sp.exhibit_index ?? 0) === exIdx) {
                    sp.exhibit = Object.assign({}, ex);
                }
            });
            syncEvidencePhotosHiddenInput();
        }

        // 3. Lab details (saved to shared labsData[labIdx])
        const labIdx = typeof s.lab_index !== 'undefined' ? s.lab_index : 0;
        if (labsData[labIdx]) {
            const lab = labsData[labIdx];
            lab.laboratory_number = document.getElementById('input_laboratory_number')?.value || '';
            lab.confirmatory_test_result = document.getElementById('confirmatoryStatusSelect')?.value || '';
            lab.presumptive_reaction_val = document.getElementById('presReactionSelect')?.value || '';
            lab.presumptive_result = document.getElementById('presumptiveResultTextarea')?.value || '';
            lab.sample_weight_description = document.getElementById('sampleWeightDescription')?.value || '';
            lab.sample_weight_units = document.getElementById('sampleWeightUnits')?.value || '';
            lab.sample_weight_gross = document.getElementById('sampleWeightGross')?.value || '';
            lab.sample_weight_taken = document.getElementById('sampleWeightTaken')?.value || '';
            lab.sample_weight_reserved = document.getElementById('sampleWeightReserved')?.value || '';
            lab.confirmatory_substance_identification = document.getElementById('confirmatorySubstanceIdentification')?.value || '';
            lab.confirmatory_test_methods = document.getElementById('confirmatoryTestMethods')?.value || '';
            lab.confirmatory_specifications = document.getElementById('confirmatorySpecifications')?.value || '';
            lab.laboratory_analytical_findings = document.getElementById('input_findings')?.value || '';

            // Detected substances
            const detSubRows = document.querySelectorAll('#detectedSubstancesListContainer .detected-substance-row');
            const detSubs = [];
            detSubRows.forEach(row => {
                const name = row.querySelector('.detected-substance-name')?.value || '';
                const pct = row.querySelector('.detected-substance-percent')?.value || '';
                if (name || pct) {
                    detSubs.push({ name: name, substance: name, percentage: pct, purity: pct });
                }
            });
            lab.detected_substances = detSubs;

            // Mirror shared lab to all suspects linking to labIdx
            suspectsData.forEach(sp => {
                if ((sp.lab_index ?? 0) === labIdx) {
                    sp.lab = Object.assign({}, lab);
                }
            });
        }
    }

    function toggleEditAmmoOtherCondition(el) {
        const wrapper = document.getElementById('wrapper_edit_ammo_other_condition');
        if (wrapper) {
            if (el && el.value === 'Other') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        }
    }

    /* REAL-TIME TAB 2 EXHIBIT SYNC & UI DROPDOWN MAP HELPERS */
    function updateCategoryDropdownUI(catVal) {
        const curCat = (catVal || '').toLowerCase().trim();
        let val = 'Drugs / Narcotics';
        let badge = 'DRG';
        let label = 'Drugs / Narcotics';
        let badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';

        if (curCat.includes('currency') || curCat.includes('cash') || curCat.includes('money')) {
            val = 'Currency / Cash'; badge = 'CASH'; label = 'Currency / Cash'; badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        } else if (curCat.includes('gold') || curCat.includes('metal') || curCat.includes('bullion')) {
            val = 'Gold / Precious Metals'; badge = 'MET'; label = 'Gold / Precious Metals'; badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        } else if (curCat.includes('ammo') || curCat.includes('ammunition')) {
            val = 'Ammunition'; badge = 'AMMO'; label = 'Ammunition'; badgeClass = 'bg-orange-100 text-orange-800 border-orange-200';
        } else if (curCat.includes('firearm') || curCat.includes('weapon') || curCat.includes('gun')) {
            val = 'Firearm / Ammunition / Weapon'; badge = 'WPN'; label = 'Firearm / Ammunition / Weapon'; badgeClass = 'bg-red-100 text-red-800 border-red-200';
        } else if (curCat.includes('vehicle') || curCat.includes('car') || curCat.includes('transport')) {
            val = 'Vehicle / Transport'; badge = 'VEH'; label = 'Vehicle / Transport'; badgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
        } else if (curCat.includes('digital') || curCat.includes('electronic') || curCat.includes('phone')) {
            val = 'Digital Evidence / Electronics'; badge = 'ELEC'; label = 'Digital Evidence / Electronics'; badgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-200';
        } else if (curCat.includes('chemical') || curCat.includes('precursor')) {
            val = 'Chemical / Precursor'; badge = 'CHEM'; label = 'Chemical / Precursor'; badgeClass = 'bg-purple-100 text-purple-800 border-purple-200';
        } else if (curCat.includes('counterfeit') || curCat.includes('fake')) {
            val = 'Counterfeit Goods'; badge = 'FAKE'; label = 'Counterfeit Goods'; badgeClass = 'bg-rose-100 text-rose-800 border-rose-200';
        } else if (curCat.includes('document') || curCat.includes('identity') || curCat.includes('passport')) {
            val = 'Documents / Identity'; badge = 'DOC'; label = 'Documents / Identity'; badgeClass = 'bg-slate-200 text-slate-800 border-slate-300';
        } else if (curCat.includes('other')) {
            val = 'Other Seizure'; badge = 'MISC'; label = 'Other Seizure'; badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
        } else if (!curCat) {
            val = ''; badge = 'CAT'; label = 'Select Category...'; badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
        } else {
            val = catVal; badge = 'DRG'; label = catVal; badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        }

        const ammoCard = document.getElementById('card_ammunition_details');
        if (ammoCard) {
            if (curCat.includes('ammo') || curCat.includes('ammunition')) {
                ammoCard.classList.remove('hidden');
            } else {
                ammoCard.classList.add('hidden');
            }
        }

        selectEditCategory(val, label, badge, badgeClass);
    }

    function updateUnitDropdownUI(unitVal) {
        const curUnit = (unitVal || '').toLowerCase().trim();
        let val = 'Kilograms (kg)';
        let code = 'KG';
        let label = 'Kilograms (kg)';
        let badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';

        if (curUnit.includes('kilo') || curUnit.includes('kg')) {
            val = 'Kilograms (kg)'; code = 'KG'; label = 'Kilograms (kg)'; badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        } else if (curUnit.includes('gram') || curUnit === 'g') {
            val = 'Grams (g)'; code = 'G'; label = 'Grams (g)'; badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        } else if (curUnit.includes('sack') || curUnit.includes('bag')) {
            val = 'Sacks / Bags'; code = 'BAG'; label = 'Sacks / Bags'; badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        } else if (curUnit.includes('slab') || curUnit.includes('block')) {
            val = 'Slabs / Blocks'; code = 'SLAB'; label = 'Slabs / Blocks'; badgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
        } else if (curUnit.includes('package') || curUnit.includes('parcel')) {
            val = 'Packages / Parcels'; code = 'PKG'; label = 'Packages / Parcels'; badgeClass = 'bg-purple-100 text-purple-800 border-purple-200';
        } else if (curUnit.includes('barrel') || curUnit.includes('drum')) {
            val = 'Barrels / Drums'; code = 'DRUM'; label = 'Barrels / Drums'; badgeClass = 'bg-red-100 text-red-800 border-red-200';
        } else if (curUnit.includes('container') || curUnit.includes('box')) {
            val = 'Containers / Boxes'; code = 'BOX'; label = 'Containers / Boxes'; badgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-200';
        } else if (curUnit.includes('piece') || curUnit.includes('unit')) {
            val = 'Pieces / Units'; code = 'PCS'; label = 'Pieces / Units'; badgeClass = 'bg-slate-200 text-slate-800 border-slate-300';
        } else if (!curUnit) {
            val = ''; code = 'UNIT'; label = 'Select Packaging Unit...'; badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
        } else {
            val = unitVal; code = 'KG'; label = unitVal; badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        }

        selectEditUnit(val, label, code, badgeClass);
    }

    function updateGenderDropdownUI(genderVal) {
        const g = (genderVal || '').toLowerCase().trim();
        let val = 'Male'; let symbol = ''; let label = 'Male'; let badgeClass = 'bg-emerald-100 text-emerald-700';
        if (g === 'female' || g.includes('female')) {
            val = 'Female'; symbol = ''; label = 'Female'; badgeClass = 'bg-rose-100 text-rose-700';
        } else if (g.includes('other') || g.includes('binary')) {
            val = 'Other'; symbol = ''; label = 'Other / Non-Binary'; badgeClass = 'bg-purple-100 text-purple-700';
        } else if (g === 'unspecified') {
            val = 'Unspecified'; symbol = ''; label = 'Unspecified'; badgeClass = 'bg-slate-100 text-slate-600';
        } else if (!g) {
            val = ''; symbol = ''; label = 'Select Gender...'; badgeClass = 'bg-slate-100 text-slate-600';
        }
        selectEditGender(val, label, symbol, badgeClass);
    }

    function updateMaritalDropdownUI(maritalVal) {
        const m = (maritalVal || '').toLowerCase().trim();
        let val = 'Single'; let code = 'SGL'; let label = 'Single'; let badgeClass = 'bg-emerald-100 text-emerald-700';
        if (m.includes('married')) {
            val = 'Married'; code = 'MRD'; label = 'Married'; badgeClass = 'bg-blue-100 text-blue-700';
        } else if (m.includes('dating') || m.includes('relationship')) {
            val = 'Dating / In Relationship'; code = 'DAT'; label = 'Dating / In Relationship'; badgeClass = 'bg-purple-100 text-purple-700';
        } else if (m.includes('divorced')) {
            val = 'Divorced'; code = 'DIV'; label = 'Divorced'; badgeClass = 'bg-amber-100 text-amber-700';
        } else if (m.includes('widowed')) {
            val = 'Widowed'; code = 'WID'; label = 'Widowed'; badgeClass = 'bg-slate-200 text-slate-700';
        } else if (!m) {
            val = ''; code = 'MS'; label = 'Select Status...'; badgeClass = 'bg-slate-100 text-slate-600';
        }
        selectEditMarital(val, label, code, badgeClass);
    }

    function syncExhibitToLabAndSelector() {
        const nameVal = document.getElementById('input_seizure_name_drug')?.value || '';
        const qtyVal  = document.getElementById('input_seizure_quantity')?.value || '';
        const wgtVal  = document.getElementById('input_seizure_weight')?.value || '';
        const unitVal = document.getElementById('seizureUnitSelect')?.value || '';

        // Update Tab 3 auto-fetched inputs
        const swDesc = document.getElementById('sampleWeightDescription');
        const swUnits = document.getElementById('sampleWeightUnits');
        const swGross = document.getElementById('sampleWeightGross');
        const confSub = document.getElementById('confirmatorySubstanceIdentification');
        const unitAddon = document.getElementById('sampleWeightTakenUnitAddon');

        if (swDesc) swDesc.value = nameVal;
        if (swUnits) swUnits.value = qtyVal;
        if (swGross) swGross.value = wgtVal;
        if (confSub) confSub.value = nameVal;
        if (unitAddon) unitAddon.innerText = unitVal || 'Kilograms (kg)';

        // Update Tab 2 Live Dossier Hero Banner
        const liveDrug = document.getElementById('tab2_live_drug');
        const liveMet = document.getElementById('tab2_live_metrology');
        const liveSuspectName = document.getElementById('tab2_live_suspect_name');
        const liveSuspectRole = document.getElementById('tab2_live_suspect_role');

        if (liveDrug) liveDrug.innerText = nameVal || 'No Commodity';
        if (liveMet) liveMet.innerText = `${qtyVal || '0'} / ${wgtVal || '0 kg'}`;
        if (liveSuspectName && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
            liveSuspectName.innerText = suspectsData[activeSuspectIndex].suspect_name || `Suspect #${activeSuspectIndex + 1}`;
        }
        if (liveSuspectRole) {
            liveSuspectRole.innerText = activeSuspectIndex === 0 ? 'PRIMARY SUSPECT' : (activeSuspectIndex === 1 ? 'CO-SUSPECT' : 'ASSOCIATE SUSPECT');
        }

        // Recalculate reserved sample in Tab 3
        if (typeof updateSampleWeightReserved === 'function') {
            updateSampleWeightReserved();
        }

        // Update top suspect selector button badge for active suspect
        const activeBtn = document.getElementById(`suspectSelectBtn_${activeSuspectIndex}`);
        if (activeBtn) {
            const badgeEl = activeBtn.querySelector('.text-amber-300\\/90');
            if (badgeEl) {
                let labelText = `${nameVal || 'No Exhibit'}`;
                if (wgtVal) {
                    labelText += ` (${wgtVal} ${unitVal || 'kg'})`;
                }
                badgeEl.innerText = labelText;
            }
        }
    }

    function switchSuspect(newIdx) {
        if (newIdx === activeSuspectIndex) return;
        
        saveCurrentSuspectState();

        activeSuspectIndex = newIdx;
        const activeIdxEl = document.getElementById('activeSuspectIndex');
        if (activeIdxEl) activeIdxEl.value = newIdx;

        const s = suspectsData[newIdx];
        if (!s) return;

        // Update selector button styles
        document.querySelectorAll('.suspect-select-btn').forEach((btn, i) => {
            if (i === newIdx) {
                btn.className = 'suspect-select-btn px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer flex items-center gap-3 shrink-0 bg-amber-500/20 border-amber-500 text-white ring-2 ring-amber-500/30 shadow-md';
            } else {
                btn.className = 'suspect-select-btn px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer flex items-center gap-3 shrink-0 bg-slate-800/80 border-slate-700 text-slate-300 hover:border-slate-500 hover:text-white';
            }
        });

        // Tab 1 fields (Biodata)
        if (document.getElementById('input_suspect_name')) document.getElementById('input_suspect_name').value = s.suspect_name || '';
        if (document.getElementById('input_suspect_alias')) document.getElementById('input_suspect_alias').value = s.suspect_alias || '';
        if (document.getElementById('input_ghana_card')) document.getElementById('input_ghana_card').value = s.ghana_card || '';
        
        // Gender dropdown
        updateGenderDropdownUI(s.gender || 'Male');

        if (document.getElementById('newArrestedDOB')) document.getElementById('newArrestedDOB').value = s.dob || '';
        if (document.getElementById('newArrestedAge')) document.getElementById('newArrestedAge').value = s.age || '';
        if (document.getElementById('input_height')) document.getElementById('input_height').value = s.height || '';
        if (document.getElementById('input_height_unit')) document.getElementById('input_height_unit').value = s.height_unit || 'CM';
        if (document.getElementById('input_eye_color')) document.getElementById('input_eye_color').value = s.eye_color || '';
        if (document.getElementById('input_nationality')) document.getElementById('input_nationality').value = s.nationality || '';
        if (document.getElementById('input_place_of_birth')) document.getElementById('input_place_of_birth').value = s.place_of_birth || (s.pob || '');
        if (document.getElementById('input_hometown')) document.getElementById('input_hometown').value = s.hometown || '';
        if (document.getElementById('input_phone')) document.getElementById('input_phone').value = s.phone || '';
        if (document.getElementById('input_arrest_date')) document.getElementById('input_arrest_date').value = s.arrest_date || '';
        if (document.getElementById('input_arrest_time')) document.getElementById('input_arrest_time').value = s.arrest_time || '';
        if (document.getElementById('input_address')) document.getElementById('input_address').value = s.address || '';
        if (document.getElementById('input_raid_location')) document.getElementById('input_raid_location').value = s.raid_location || '';
        
        // Spouse / Partner fields
        if (document.getElementById('input_spouse_name')) document.getElementById('input_spouse_name').value = s.spouse_name || (s.partner_name || '');
        if (document.getElementById('input_spouse_phone')) document.getElementById('input_spouse_phone').value = s.spouse_phone || (s.partner_phone || '');
        if (document.getElementById('input_spouse_info')) document.getElementById('input_spouse_info').value = s.spouse_info || (s.partner_info || '');

        // Marital status dropdown
        updateMaritalDropdownUI(s.marital_status || 'Single');
        
        let childVal = (s.children_count || '0').toString();
        if (!/^\d+$/.test(childVal)) {
            const digits = childVal.replace(/\D/g, '');
            childVal = digits || '0';
        }
        if (document.getElementById('input_children_count')) document.getElementById('input_children_count').value = childVal;
        if (document.getElementById('input_holding_facility')) document.getElementById('input_holding_facility').value = s.holding_facility || '';
        if (document.getElementById('input_offense_description')) document.getElementById('input_offense_description').value = s.offense_description || '';

        // Mugshots
        const mug = s.mugshots || {};
        function getDefinedMugUrl(...vals) {
            for (let v of vals) {
                if (typeof v === 'string') return v;
            }
            return '';
        }

        const frontUrl = getDefinedMugUrl(mug.front, mug['front-profile'], mug.mugshot_front, s.mugshot_front);
        const leftUrl = getDefinedMugUrl(mug['left-profile'], mug.left, mug.mugshot_left, s.mugshot_left);
        const rightUrl = getDefinedMugUrl(mug['right-profile'], mug.right, mug.mugshot_right, s.mugshot_right);

        if (frontUrl && frontUrl.trim()) {
            applyArrestMugshotPreview(frontUrl, 'front');
        } else {
            clearArrestMugshotState('front');
        }

        if (leftUrl && leftUrl.trim()) {
            applyArrestMugshotPreview(leftUrl, 'left-profile');
        } else {
            clearArrestMugshotState('left-profile');
        }

        if (rightUrl && rightUrl.trim()) {
            applyArrestMugshotPreview(rightUrl, 'right-profile');
        } else {
            clearArrestMugshotState('right-profile');
        }

        // Tab 2 fields (Exhibit loaded from shared exhibitsData[s.exhibit_index])
        const exIdx = typeof s.exhibit_index !== 'undefined' ? s.exhibit_index : 0;
        const ex = exhibitsData[exIdx] || s.exhibit || {};
        if (document.getElementById('input_seizure_name_drug')) document.getElementById('input_seizure_name_drug').value = ex.seizure_name_drug || '';
        updateCategoryDropdownUI(ex.exhibit_category || 'Drugs / Narcotics');
        if (document.getElementById('input_seizure_quantity')) document.getElementById('input_seizure_quantity').value = ex.seizure_quantity || '';
        if (document.getElementById('input_seizure_weight')) document.getElementById('input_seizure_weight').value = ex.seizure_weight || '';
        updateUnitDropdownUI(ex.seizure_unit || 'Kilograms (kg)');
        if (document.getElementById('input_seizure_packaging')) document.getElementById('input_seizure_packaging').value = ex.seizure_packaging || '';
        if (document.getElementById('input_seizure_reason')) document.getElementById('input_seizure_reason').value = ex.seizure_reason || '';
        
        let ammo = ex.ammunition_details || {};
        if (typeof ammo === 'string') {
            try { ammo = JSON.parse(ammo); } catch (e) { ammo = {}; }
        }
        if (document.getElementById('input_ammo_cartridge_appearance')) document.getElementById('input_ammo_cartridge_appearance').value = ammo.cartridge_appearance || '';
        if (document.getElementById('input_ammo_projectile_bullet_type')) document.getElementById('input_ammo_projectile_bullet_type').value = ammo.projectile_bullet_type || '';
        if (document.getElementById('input_ammo_cartridge_case_material')) document.getElementById('input_ammo_cartridge_case_material').value = ammo.cartridge_case_material || '';
        if (document.getElementById('input_ammo_colour_markings')) document.getElementById('input_ammo_colour_markings').value = ammo.colour_markings || '';
        if (document.getElementById('input_ammo_condition')) {
            document.getElementById('input_ammo_condition').value = ammo.condition || '';
            toggleEditAmmoOtherCondition(document.getElementById('input_ammo_condition'));
        }
        if (document.getElementById('input_ammo_other_condition')) document.getElementById('input_ammo_other_condition').value = ammo.other_condition || '';
        if (document.getElementById('input_ammo_packaging_type')) document.getElementById('input_ammo_packaging_type').value = ammo.packaging_type || '';
        if (document.getElementById('input_ammo_seal_condition')) document.getElementById('input_ammo_seal_condition').value = ammo.seal_tamper_evident_condition || ammo.seal_condition || '';
        if (document.getElementById('input_ammo_package_reference_number')) document.getElementById('input_ammo_package_reference_number').value = ammo.package_reference_number || '';
        if (document.getElementById('input_ammo_condition_when_received')) document.getElementById('input_ammo_condition_when_received').value = ammo.condition_when_received || '';

        renderExhibitPhotosGrid(ex.evidence_photos || []);

        // Tab 3 fields (Lab Results loaded from shared labsData[s.lab_index])
        const labIdx = typeof s.lab_index !== 'undefined' ? s.lab_index : 0;
        const lab = labsData[labIdx] || s.lab || {};
        if (document.getElementById('input_laboratory_number')) document.getElementById('input_laboratory_number').value = lab.laboratory_number || '';
        const confRes = lab.confirmatory_test_result || 'YES';
        selectEditConfirmatoryStatus(confRes, (confRes === 'NO' ? 'NO — Negative / Unconfirmed' : 'YES — Confirmed Positive'), (confRes === 'NO' ? 'bg-slate-400' : 'bg-emerald-500'));
        
        const presVal = lab.presumptive_reaction_val || 'YES (Positive Colorimetric Reaction)';
        selectEditPresReaction(presVal, (presVal.startsWith('NO') ? 'NO — Negative Reaction' : 'YES — Positive Reaction'), (presVal.startsWith('NO') ? 'bg-slate-400' : 'bg-emerald-500'));

        if (document.getElementById('presumptiveResultTextarea')) document.getElementById('presumptiveResultTextarea').value = lab.presumptive_result || '';
        
        // Sample weight metrology
        if (document.getElementById('sampleWeightDescription')) document.getElementById('sampleWeightDescription').value = lab.sample_weight_description || ex.seizure_name_drug || '';
        if (document.getElementById('sampleWeightUnits')) document.getElementById('sampleWeightUnits').value = lab.sample_weight_units || ex.seizure_quantity || '';
        if (document.getElementById('sampleWeightGross')) document.getElementById('sampleWeightGross').value = lab.sample_weight_gross || ex.seizure_weight || '';
        if (document.getElementById('sampleWeightTaken')) document.getElementById('sampleWeightTaken').value = lab.sample_weight_taken || '';
        if (document.getElementById('sampleWeightReserved')) document.getElementById('sampleWeightReserved').value = lab.sample_weight_reserved || '0';
        
        // Confirmatory details
        if (document.getElementById('confirmatorySubstanceIdentification')) document.getElementById('confirmatorySubstanceIdentification').value = lab.confirmatory_substance_identification || lab.substance_identification || ex.seizure_name_drug || '';
        if (document.getElementById('confirmatoryTestMethods')) document.getElementById('confirmatoryTestMethods').value = lab.confirmatory_test_methods || lab.test_methods || '';
        if (document.getElementById('confirmatorySpecifications')) document.getElementById('confirmatorySpecifications').value = lab.confirmatory_specifications || lab.specifications || '';
        if (document.getElementById('input_findings')) document.getElementById('input_findings').value = lab.laboratory_analytical_findings || lab.findings || '';

        // Detected substances
        renderDetectedSubstances(lab.detected_substances || []);
        
        // Real-time sync for exhibit to lab and selector badge
        syncExhibitToLabAndSelector();
    }

    function renderDetectedSubstances(list) {
        const container = document.getElementById('detectedSubstancesListContainer');
        if (!container) return;
        container.innerHTML = '';
        
        if (typeof list === 'string') {
            try {
                list = JSON.parse(list);
            } catch (e) {
                list = [];
            }
        }

        if (!Array.isArray(list) || list.length === 0) {
            const defaultDrug = document.getElementById('input_seizure_name_drug')?.value || 'Cocaine Hydrochloride';
            list = [{ name: defaultDrug, substance: defaultDrug, percentage: '' }];
        }

        list.forEach(item => {
            if (!item) return;
            const subName = item.name || item.substance || item.compound || item.drug_name || '';
            let subPct = item.percentage || item.purity || item.pct || '';
            if (typeof subPct === 'string') {
                subPct = subPct.replace('%', '').trim();
            }
            addDetectedSubstanceRow(subName, subPct);
        });
    }

    const canEditCrimeSceneJs = @json($canEditCrimeScene);

    function getActiveExhibitIndex() {
        if (activeSuspectIndex >= 0 && suspectsData[activeSuspectIndex]) {
            return typeof suspectsData[activeSuspectIndex].exhibit_index !== 'undefined' ? suspectsData[activeSuspectIndex].exhibit_index : 0;
        }
        return 0;
    }

    function syncEvidencePhotosHiddenInput() {
        const hiddenEl = document.getElementById('input_evidence_photos_json');
        if (!hiddenEl) return;
        const exIdx = getActiveExhibitIndex();
        const photos = (exhibitsData[exIdx] && exhibitsData[exIdx].evidence_photos) ? exhibitsData[exIdx].evidence_photos : [];
        hiddenEl.value = JSON.stringify(photos);
    }

    function renderExhibitPhotosGrid(photos) {
        const container = document.getElementById('exhibitPhotosGridContainer');
        const badge = document.getElementById('tab2_exhibit_photo_count_badge');
        if (!container) return;

        if (typeof photos === 'string') {
            try { photos = JSON.parse(photos); } catch (e) { photos = []; }
        }
        if (!Array.isArray(photos)) photos = [];

        if (badge) {
            badge.innerText = `${photos.length} Photo${photos.length === 1 ? '' : 's'}`;
        }

        if (photos.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-8 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-center bg-slate-50/50 space-y-3">
                    <div class="w-12 h-12 rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase font-mono-code text-slate-800">No Seized Drug / Exhibit Photos Uploaded</h4>
                        <p class="text-[11px] text-slate-500 max-w-sm mt-0.5">Upload photos of the intercepted contraband packaging, drug slabs, field test reaction, or evidence seals.</p>
                    </div>
                </div>
            `;
            syncEvidencePhotosHiddenInput();
            return;
        }

        let html = '';
        photos.forEach((src, pIdx) => {
            if (!src) return;
            const safeSrc = src.replace(/"/g, '&quot;');
            html += `
                <div class="group relative bg-slate-900 rounded-xl overflow-hidden border border-slate-700/80 shadow-md aspect-square flex items-center justify-center hover:border-amber-500 transition-all">
                    <img src="${safeSrc}" alt="Seized Contraband Drug Photo #${pIdx + 1}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                        <button type="button" onclick="openArrestMugshotModal('${safeSrc}', 'Seized Exhibit Contraband Visual #${pIdx + 1}')" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-black rounded-lg transition-all shadow-md cursor-pointer flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>View</span>
                        </button>
                        ${canEditCrimeSceneJs ? `
                            <button type="button" onclick="removeExhibitPhoto(${pIdx})" class="px-2 py-1 bg-red-600/90 hover:bg-red-500 text-white text-[10px] font-extrabold rounded-lg transition-all shadow-md cursor-pointer flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                <span>Delete</span>
                            </button>
                        ` : ''}
                    </div>
                    <div class="absolute bottom-1 left-1 bg-slate-950/80 text-amber-400 font-mono-code text-[9px] font-bold px-1.5 py-0.5 rounded border border-amber-500/30">
                        #${pIdx + 1}
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        syncEvidencePhotosHiddenInput();
    }

    function handleExhibitPhotoUpload(event) {
        const files = event.target.files;
        if (!files || files.length === 0) return;

        const exIdx = getActiveExhibitIndex();
        if (!exhibitsData[exIdx]) exhibitsData[exIdx] = {};
        if (!exhibitsData[exIdx].evidence_photos) exhibitsData[exIdx].evidence_photos = [];

        let loadedCount = 0;
        let lastUploadedUrl = null;
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                lastUploadedUrl = e.target.result;
                exhibitsData[exIdx].evidence_photos.push(lastUploadedUrl);
                loadedCount++;
                if (loadedCount === files.length) {
                    if (typeof activeSuspectIndex !== 'undefined' && activeSuspectIndex >= 0 && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
                        if (!suspectsData[activeSuspectIndex].exhibit) suspectsData[activeSuspectIndex].exhibit = {};
                        suspectsData[activeSuspectIndex].exhibit.evidence_photos = exhibitsData[exIdx].evidence_photos;
                    }
                    if (typeof suspectsData !== 'undefined' && Array.isArray(suspectsData)) {
                        suspectsData.forEach(sp => {
                            if ((sp.exhibit_index ?? 0) === exIdx) {
                                if (!sp.exhibit) sp.exhibit = {};
                                sp.exhibit.evidence_photos = exhibitsData[exIdx].evidence_photos;
                            }
                        });
                    }
                    renderExhibitPhotosGrid(exhibitsData[exIdx].evidence_photos);
                    syncEvidencePhotosHiddenInput();
                    if (lastUploadedUrl) {
                        openArrestMugshotModal(lastUploadedUrl, 'Uploaded Drug Exhibit Visual Evidence Preview');
                    }
                }
            };
            reader.readAsDataURL(file);
        });
        event.target.value = '';
    }

    function addExhibitPhotoFromUrl() {
        const input = document.getElementById('inputExhibitPhotoUrl');
        if (!input) return;
        const url = input.value.trim();
        if (!url) return;

        const exIdx = getActiveExhibitIndex();
        if (!exhibitsData[exIdx]) exhibitsData[exIdx] = {};
        if (!exhibitsData[exIdx].evidence_photos) exhibitsData[exIdx].evidence_photos = [];

        exhibitsData[exIdx].evidence_photos.push(url);
        if (typeof activeSuspectIndex !== 'undefined' && activeSuspectIndex >= 0 && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
            if (!suspectsData[activeSuspectIndex].exhibit) suspectsData[activeSuspectIndex].exhibit = {};
            suspectsData[activeSuspectIndex].exhibit.evidence_photos = exhibitsData[exIdx].evidence_photos;
        }
        if (typeof suspectsData !== 'undefined' && Array.isArray(suspectsData)) {
            suspectsData.forEach(sp => {
                if ((sp.exhibit_index ?? 0) === exIdx) {
                    if (!sp.exhibit) sp.exhibit = {};
                    sp.exhibit.evidence_photos = exhibitsData[exIdx].evidence_photos;
                }
            });
        }
        input.value = '';
        renderExhibitPhotosGrid(exhibitsData[exIdx].evidence_photos);
        syncEvidencePhotosHiddenInput();
        openArrestMugshotModal(url, 'Attached Drug Exhibit Visual Evidence Preview');
    }

    function removeExhibitPhoto(pIdx) {
        const exIdx = getActiveExhibitIndex();
        if (!exhibitsData[exIdx] || !exhibitsData[exIdx].evidence_photos) return;

        exhibitsData[exIdx].evidence_photos.splice(pIdx, 1);
        renderExhibitPhotosGrid(exhibitsData[exIdx].evidence_photos);
        syncEvidencePhotosHiddenInput();

        if (typeof activeSuspectIndex !== 'undefined' && activeSuspectIndex >= 0 && typeof suspectsData !== 'undefined' && suspectsData[activeSuspectIndex]) {
            if (!suspectsData[activeSuspectIndex].exhibit) suspectsData[activeSuspectIndex].exhibit = {};
            suspectsData[activeSuspectIndex].exhibit.evidence_photos = exhibitsData[exIdx].evidence_photos;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        suspectsData.forEach(s => {
            s.last_known_name = s.suspect_name || '';
        });

        switchSuspect(0);

        const nameInput = document.getElementById('input_suspect_name');
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                if (activeSuspectIndex < 0 || !suspectsData[activeSuspectIndex]) return;
                const s = suspectsData[activeSuspectIndex];
                const newName = this.value || '';
                const titleInput = document.querySelector('input[name="title"]');

                if (titleInput && s.last_known_name && s.last_known_name !== newName) {
                    const oldName = s.last_known_name;
                    let curTitle = titleInput.value;
                    if (curTitle && oldName) {
                        const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const re = new RegExp('\\b' + escapeRegex(oldName) + '\\b', 'g');
                        if (re.test(curTitle)) {
                            curTitle = curTitle.replace(re, newName);
                        } else if (curTitle.includes(oldName)) {
                            curTitle = curTitle.replaceAll(oldName, newName);
                        }
                        titleInput.value = curTitle;
                    }
                }
                s.suspect_name = newName;
                s.last_known_name = newName;

                // Sync live labels
                const liveSuspectName = document.getElementById('tab2_live_suspect_name');
                if (liveSuspectName) liveSuspectName.innerText = newName || `Suspect #${activeSuspectIndex + 1}`;

                const activeBtn = document.getElementById(`suspectSelectBtn_${activeSuspectIndex}`);
                if (activeBtn) {
                    const nameSpan = activeBtn.querySelector('.truncate');
                    if (nameSpan) nameSpan.innerText = newName || `Suspect #${activeSuspectIndex + 1}`;
                }
            });
        }

        const form = document.getElementById('editCaseForm');
        if (form) {
            form.addEventListener('submit', function() {
                saveCurrentSuspectState();
                const jsonInput = document.getElementById('suspectsDataJson');
                if (jsonInput) {
                    jsonInput.value = JSON.stringify({
                        suspects: suspectsData,
                        exhibits: exhibitsData,
                        labs: labsData
                    });
                }
            });
        }
    });
</script>

</body>
</html>
