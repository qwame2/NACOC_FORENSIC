@php
    $authUser = auth()->user();
    $officerName = $authUser->fullname ?? session('user_name') ?? (\App\Models\User::where('is_head', true)->first()->fullname ?? 'Officer');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';

    $status = $case->status ?? 'Approved';
    $priority = strtoupper($case->priority ?? 'HIGH');
    $caseNumericId = $case->id ?? 1;
    
    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    
    $primarySuspectName = strtolower(trim($case->suspect_name ?? ''));

    $allAssocPhotos = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szItem) {
            $assocPerson = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? '')));
            $assocRole   = strtolower(trim($szItem['association_role'] ?? ($szItem['role'] ?? '')));
            $isExplicitAssoc = !empty($szItem['is_associate']);

            $isPrimaryOwner = empty($assocPerson) 
                || (!empty($primarySuspectName) && $assocPerson === $primarySuspectName)
                || $assocPerson === 'primary suspect'
                || $assocPerson === 'primary'
                || str_contains($assocPerson, 'all suspect')
                || str_contains($assocPerson, 'joint');

            $isAssocRole = !empty($assocRole) && str_contains($assocRole, 'associate');
            $isAssocSz = ($isExplicitAssoc || $isAssocRole || (!$isPrimaryOwner && !empty($assocPerson))) && !$isPrimaryOwner;

            if ($isAssocSz) {
                $aPhotos = $szItem['evidence_photos'] ?? ($szItem['photos'] ?? []);
                if (is_array($aPhotos)) {
                    foreach ($aPhotos as $ap) {
                        if ($ap && !in_array($ap, $allAssocPhotos)) $allAssocPhotos[] = $ap;
                    }
                }
                $apSingle = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
                if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                    $allAssocPhotos[] = $apSingle;
                }
            }
        }
    }
    if (!empty($docketData['associated_seizures']) && is_array($docketData['associated_seizures'])) {
        foreach ($docketData['associated_seizures'] as $aEx) {
            $aPhotos = $aEx['evidence_photos'] ?? ($aEx['photos'] ?? []);
            if (is_array($aPhotos)) {
                foreach ($aPhotos as $ap) {
                    if ($ap && !in_array($ap, $allAssocPhotos)) $allAssocPhotos[] = $ap;
                }
            }
            $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
            if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                $allAssocPhotos[] = $apSingle;
            }
        }
    }
    if (!empty($docketData['associate_exhibits']) && is_array($docketData['associate_exhibits'])) {
        foreach ($docketData['associate_exhibits'] as $aEx) {
            $aPhotos = $aEx['evidence_photos'] ?? ($aEx['photos'] ?? []);
            if (is_array($aPhotos)) {
                foreach ($aPhotos as $ap) {
                    if ($ap && !in_array($ap, $allAssocPhotos)) $allAssocPhotos[] = $ap;
                }
            }
            $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
            if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                $allAssocPhotos[] = $apSingle;
            }
        }
    }

    $rawPhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));
    if (!is_array($rawPhotos)) $rawPhotos = [];

    $primaryPhotos = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szItem) {
            $assocPerson = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? '')));
            $assocRole   = strtolower(trim($szItem['association_role'] ?? ($szItem['role'] ?? '')));
            $isExplicitAssoc = !empty($szItem['is_associate']);

            $isPrimaryOwner = empty($assocPerson) 
                || (!empty($primarySuspectName) && $assocPerson === $primarySuspectName)
                || $assocPerson === 'primary suspect'
                || $assocPerson === 'primary'
                || str_contains($assocPerson, 'all suspect')
                || str_contains($assocPerson, 'joint');

            $isAssocRole = !empty($assocRole) && str_contains($assocRole, 'associate');
            $isAssocSz = ($isExplicitAssoc || $isAssocRole || (!$isPrimaryOwner && !empty($assocPerson))) && !$isPrimaryOwner;

            if (!$isAssocSz) {
                $pPhotos = $szItem['evidence_photos'] ?? ($szItem['photos'] ?? []);
                if (is_array($pPhotos)) {
                    foreach ($pPhotos as $p) {
                        if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
                            $primaryPhotos[] = $p;
                        }
                    }
                }
                $singleP = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
                if ($singleP && !in_array($singleP, $primaryPhotos) && !in_array($singleP, $allAssocPhotos)) {
                    $singleP && !in_array($singleP, $primaryPhotos) && $primaryPhotos[] = $singleP;
                }
            }
        }
    }

    foreach ($rawPhotos as $p) {
        if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
            $primaryPhotos[] = $p;
        }
    }
    if (!empty($docketData['evidence_photos']) && is_array($docketData['evidence_photos'])) {
        foreach ($docketData['evidence_photos'] as $p) {
            if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
                $primaryPhotos[] = $p;
            }
        }
    }

    $primaryPhotos = array_values(array_filter($primaryPhotos, function($p) use ($allAssocPhotos) {
        return !in_array($p, $allAssocPhotos);
    }));

    if (empty($primaryPhotos)) {
        $primaryPhotos = [];
    }

    $evidenceGalleryItems = [];
    foreach ($primaryPhotos as $p) {
        if (!empty($p)) {
            $evidenceGalleryItems[] = [
                'url' => $p,
                'label' => 'Primary Exhibit',
                'type' => 'primary'
            ];
        }
    }
    foreach ($allAssocPhotos as $ap) {
        if (!empty($ap)) {
            $evidenceGalleryItems[] = [
                'url' => $ap,
                'label' => 'Associate Exhibit',
                'type' => 'associate'
            ];
        }
    }

    $evidencePhotos = array_map(fn($item) => $item['url'], $evidenceGalleryItems);

    $getImgSrc = function($path) {
        if (empty($path)) return null;
        return \App\Services\CasePhotoResolver::resolveMugshotUrl($path);
    };

    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'N/A';

    $unitProgress = $docketData['unit_progress'] ?? [];

    // Gather exhibit items belonging to Primary Suspect ONLY
    $primaryExhibitsCheckPdf = [];
    if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
        $primaryExhibitsCheckPdf[] = [
            'category'          => $case->exhibit_category ?? '',
            'seizure_name_drug' => $case->seizure_name_drug ?? '',
            'seizure_name'      => $case->seizure_name ?? '',
        ];
    }
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $sz) {
            $isAssoc = !empty($sz['is_associate']) 
                || !empty($sz['associated_person']) 
                || (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
            if (!$isAssoc) {
                $primaryExhibitsCheckPdf[] = $sz;
            }
        }
    }

    $drugKeywords = config('forensic.drug_keywords', []);
    $drugClassifier = app(\App\Services\DrugExhibitClassifier::class);
    $caseClassification = $drugClassifier->classifyCase($case, $docketData);
    $isPrimaryDrugCase = $caseClassification['isPrimaryDrugCase'];
    $isAnyDrugCasePdf = $caseClassification['isAnyDrugCase'];
    $isDrugCase = $caseClassification['isDrugCase'];

    $isLabCreatedCasePdf = !empty($case->unit_name) && (
        strtolower(trim($case->unit_name)) === 'lab analysis' ||
        str_contains(strtolower($case->unit_name), 'lab analysis') ||
        str_contains(strtolower($case->unit_name), 'laboratory')
    );

    $unitsList = $case->getWorkflowUnits();
    $totalUnitsCount = count($unitsList);

    if ($isDrugCase) {
        $defaultProgress = [
            'Lab Analysis'        => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            'Evidence Management' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        ];
    } elseif ($isLabCreatedCasePdf) {
        $defaultProgress = [
            'Evidence Management' => ['status' => 'COMPLETED', 'officer' => $case->submitting_officer ?? 'Evidence Officer', 'updated_at' => optional($case->created_at)->toDateTimeString()],
        ];
    } else {
        $defaultProgress = [
            'Crime Scene Support'          => ['status' => 'COMPLETED', 'officer' => $case->user?->fullname ?? ($case->user_id ? \App\Models\User::find($case->user_id)?->fullname : ($case->submitting_officer ?? 'Crime Scene Officer')), 'updated_at' => optional($case->created_at)->toDateTimeString()],
            'Evidence Management'          => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        ];
    }


    $rawProgress = $docketData['unit_progress'] ?? $defaultProgress;
    $unitProgress = [];
    foreach ($unitsList as $uKey) {
        if (isset($rawProgress[$uKey])) {
            $unitProgress[$uKey] = $rawProgress[$uKey];
        } else {
            $unitProgress[$uKey] = $defaultProgress[$uKey] ?? ['status' => 'PENDING', 'officer' => null, 'updated_at' => null];
        }
    }
    $completedUnitsCount = 0;
    foreach ($unitsList as $uKey) {
        if (isset($unitProgress[$uKey]['status']) && strtoupper($unitProgress[$uKey]['status']) === 'COMPLETED') {
            $completedUnitsCount++;
        }
    }


    $primarySuspectNamesList = [];
    $coSuspectNamesList = [];
    $associateSuspectNamesList = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        foreach ($docketData['suspects'] as $sp) {
            $role = strtolower($sp['association_role'] ?? ($sp['role'] ?? ''));
            $spIsAssoc = !empty($sp['is_associate']) || (!empty($role) && $role !== 'primary suspect');
            $spIsCo = $spIsAssoc && (
                (!empty($sp['association_role']) && (str_contains(strtolower($sp['association_role']), 'co-suspect') || str_contains(strtolower($sp['association_role']), 'co suspect')))
                || (!empty($sp['role']) && (str_contains(strtolower($sp['role']), 'co-suspect') || str_contains(strtolower($sp['role']), 'co suspect')))
                || (!empty($sp['suspect_role']) && (str_contains(strtolower($sp['suspect_role']), 'co-suspect') || str_contains(strtolower($sp['suspect_role']), 'co suspect')))
            );
            if (!$spIsAssoc && !empty($sp['suspect_name'])) {
                $primarySuspectNamesList[] = $sp['suspect_name'];
            } elseif ($spIsCo && !empty($sp['suspect_name'])) {
                $coSuspectNamesList[] = $sp['suspect_name'];
            } elseif ($spIsAssoc && !empty($sp['suspect_name'])) {
                $associateSuspectNamesList[] = $sp['suspect_name'];
            }
        }
    }
    $primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));

    $labExam = $docketData['lab_examination'] ?? [];

    if (empty($labExam['has_saved']) && empty($labExam['presumptive_result'])) {
        if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                if (!empty($lItem['has_saved']) || !empty($lItem['presumptive_result'])) {
                    $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
                    $isPrimaryOrCo = false;
                    if (empty($lOwner) || $lOwner === 'primary suspect' || str_contains($lOwner, 'primary')) {
                        $isPrimaryOrCo = true;
                    } elseif (!empty($primaryAndCoNamesList)) {
                        foreach ($primaryAndCoNamesList as $pn) {
                            $pnLow = strtolower(trim($pn));
                            if ($lOwner === $pnLow || str_contains($lOwner, $pnLow) || str_contains($pnLow, $lOwner)) {
                                $isPrimaryOrCo = true;
                                break;
                            }
                        }
                    } else {
                        $isPrimaryOrCo = true;
                    }

                    if ($isPrimaryOrCo) {
                        $labExam = array_merge($labExam, $lItem);
                        break;
                    }
                }
            }
        }
    }

    if (empty($labExam['has_saved']) && empty($labExam['presumptive_result'])) {
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                if (!empty($szItem['has_saved_lab']) || !empty($szItem['presumptive_result'])) {
                    $szOwner = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? ''))));
                    $szIsAssoc = !empty($szItem['is_associate']) || (!empty($szItem['association_role']) && str_contains(strtolower($szItem['association_role']), 'associate'));
                    if (!$szIsAssoc) {
                        $labExam = array_merge($labExam, [
                            'exhibit_owner_name'  => $szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? null)),
                            'laboratory_number'   => $szItem['laboratory_number'] ?? null,
                            'exam_reference'      => $szItem['exam_reference'] ?? null,
                            'exhibit_number'      => $szItem['exhibit_number'] ?? null,
                            'analyst'             => $szItem['analyst'] ?? null,
                            'date_received'       => $szItem['date_received'] ?? null,
                            'date_started'        => $szItem['date_started'] ?? null,
                            'date_completed'      => $szItem['date_completed'] ?? null,
                            'presumptive_result'  => $szItem['presumptive_result'] ?? null,
                            'confirmatory_result' => $szItem['confirmatory_result'] ?? null,
                            'sample_weight'       => [
                                'description'      => $szItem['sample_description'] ?? null,
                                'units_packaging'  => $szItem['sample_units'] ?? null,
                                'gross_weight'     => $szItem['sample_gross'] ?? null,
                                'sample_taken'     => $szItem['sample_taken'] ?? null,
                                'reserved_samples' => $szItem['sample_reserved'] ?? null,
                            ],
                            'substance_identification' => $szItem['substance_identification'] ?? null,
                            'detected_substances'      => $szItem['detected_substances'] ?? [],
                            'test_methods'             => $szItem['test_methods'] ?? null,
                            'specifications'           => $szItem['specifications'] ?? null,
                            'findings'                 => $szItem['findings'] ?? null,
                            'has_saved'                => true,
                        ]);
                        break;
                    }
                }
            }
        }
    }

    $isMultiCasePdf = ($case instanceof \App\Models\ForensicCase)
        ? $case->isMultipleSuspect()
        : (($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase || ($case instanceof \App\Models\MultipleSuspectCase));

    $dbPrimaryLabExamPdf = \App\Models\LaboratoryExaminationResult::where(function($q) use ($case, $isMultiCasePdf) {
            if ($isMultiCasePdf) {
                $q->where('multiple_suspect_case_id', $case->id);
            } else {
                $q->where('single_suspect_case_id', $case->id);
            }
        })->where('has_saved', true)->latest('id')->first();

    if (!$dbPrimaryLabExamPdf) {
        $dbPrimaryLabExamPdf = \App\Models\AssociateLabExamination::where('case_id', $case->id)
            ->where('has_saved', true)
            ->get()
            ->first(function($aRec) use ($primaryAndCoNamesList) {
                $aName = strtolower(trim($aRec->suspect_name ?? ''));
                if (empty($aName) || $aName === 'primary suspect' || str_contains($aName, 'primary')) {
                    return true;
                }
                if (!empty($primaryAndCoNamesList)) {
                    foreach ($primaryAndCoNamesList as $pn) {
                        $pnLow = strtolower(trim($pn));
                        if ($aName === $pnLow || str_contains($aName, $pnLow) || str_contains($pnLow, $aName)) {
                            return true;
                        }
                    }
                }
                return false;
            });
    }

    if ($dbPrimaryLabExamPdf) {
        $labExam = array_merge($labExam, [
            'exhibit_owner_name'  => $dbPrimaryLabExamPdf->suspect_name,
            'laboratory_number'   => $dbPrimaryLabExamPdf->laboratory_number,
            'exam_reference'      => $dbPrimaryLabExamPdf->exam_reference,
            'exhibit_number'      => $dbPrimaryLabExamPdf->exhibit_number,
            'analyst'             => $dbPrimaryLabExamPdf->analyst,
            'date_received'       => is_object($dbPrimaryLabExamPdf->date_received) ? $dbPrimaryLabExamPdf->date_received->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_received, 0, 10),
            'date_started'        => is_object($dbPrimaryLabExamPdf->date_started) ? $dbPrimaryLabExamPdf->date_started->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_started, 0, 10),
            'date_completed'      => is_object($dbPrimaryLabExamPdf->date_completed) ? $dbPrimaryLabExamPdf->date_completed->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_completed, 0, 10),
            'presumptive_result'  => $dbPrimaryLabExamPdf->presumptive_result,
            'confirmatory_result' => $dbPrimaryLabExamPdf->confirmatory_result,
            'sample_weight'       => [
                'description'      => $dbPrimaryLabExamPdf->sample_weight_description,
                'units_packaging'  => $dbPrimaryLabExamPdf->sample_weight_units,
                'gross_weight'     => $dbPrimaryLabExamPdf->sample_weight_gross,
                'sample_taken'     => $dbPrimaryLabExamPdf->sample_weight_taken,
                'reserved_samples' => $dbPrimaryLabExamPdf->sample_weight_reserved,
            ],
            'substance_identification' => $dbPrimaryLabExamPdf->substance_identification,
            'detected_substances'      => $dbPrimaryLabExamPdf->detected_substances,
            'test_methods'             => $dbPrimaryLabExamPdf->test_methods,
            'specifications'           => $dbPrimaryLabExamPdf->specifications,
            'findings'                 => $dbPrimaryLabExamPdf->findings,
            'has_saved'                => true,
        ]);
    }

    $labOwnerNamePdf = $labExam['exhibit_owner_name'] ?? null;
    $labIsAssocPdf = !empty($labExam['exhibit_is_associate']);

    if (empty($labOwnerNamePdf) || strtoupper(trim($labOwnerNamePdf)) === 'ASSOCIATE SUSPECT' || strtoupper(trim($labOwnerNamePdf)) === 'PRIMARY SUSPECT') {
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                $szIsAssoc = !empty($sz['is_associate']) || !empty($szOwner) || (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
                $szName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                $szNo = $sz['exhibit_number'] ?? '';
                $szId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
                
                if ((!empty($labExam['exhibit_number']) && $szNo === $labExam['exhibit_number']) || (!empty($labExam['sample_weight']['description']) && strtolower($szName) === strtolower($labExam['sample_weight']['description']))) {
                    if ($szIsAssoc) {
                        $labIsAssocPdf = true;
                        $matchedOwner = null;
                        if (!empty($szId) && !empty($docketData['suspects'])) {
                            foreach ($docketData['suspects'] as $sp) {
                                $spId = $sp['associate_id'] ?? ($sp['suspect_id'] ?? null);
                                if (!empty($spId) && strtolower(trim($spId)) === strtolower(trim($szId))) {
                                    $matchedOwner = $sp['suspect_name'] ?? null;
                                    break;
                                }
                            }
                        }
                        $labOwnerNamePdf = (!empty($szOwner) && strtoupper(trim($szOwner)) !== 'ASSOCIATE SUSPECT') ? $szOwner : ($matchedOwner ?? null);
                    }
                    break;
                }
            }
        }
    }

    if ((empty($labOwnerNamePdf) || strtoupper(trim($labOwnerNamePdf)) === 'ASSOCIATE SUSPECT' || strtoupper(trim($labOwnerNamePdf)) === 'PRIMARY SUSPECT') && $labIsAssocPdf) {
        $firstAssoc = array_values(array_filter($docketData['suspects'] ?? [], function($s) {
            return !empty($s['suspect_name']) && (!empty($s['is_associate']) || (!empty($s['association_role']) && strtolower($s['association_role']) !== 'primary suspect'));
        }));
        $labOwnerNamePdf = !empty($firstAssoc) ? ($firstAssoc[0]['suspect_name'] ?? 'Associate Suspect') : 'Associate Suspect';
    }

    $rawLabExamRef = $labExam['exam_reference'] ?? ($labExam['lab_exam_reference'] ?? ($case->lab_exam_reference ?? ($case->exam_reference ?? null)));
    if (empty($rawLabExamRef) && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szRef) {
            if (!empty($szRef['exam_reference'])) {
                $rawLabExamRef = $szRef['exam_reference'];
                break;
            } elseif (!empty($szRef['lab_exam_reference'])) {
                $rawLabExamRef = $szRef['lab_exam_reference'];
                break;
            }
        }
    }
    $labExamRef = !empty($rawLabExamRef)
        ? app(\App\Services\CaseReferenceService::class)->normalizeExamReference($rawLabExamRef)
        : app(\App\Services\CaseReferenceService::class)->resolveExamReference($case, $docketData);

    $labCaseRef = $case->case_number;

    $rawLabExhibitNo = $labExam['exhibit_number'] ?? ($labExam['lab_exhibit_number'] ?? ($case->exhibit_number ?? null));
    if (empty($rawLabExhibitNo) && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szRef) {
            if (!empty($szRef['exhibit_number'])) {
                $rawLabExhibitNo = $szRef['exhibit_number'];
                break;
            } elseif (!empty($szRef['exhibit_no'])) {
                $rawLabExhibitNo = $szRef['exhibit_no'];
                break;
            }
        }
    }
    $labExhibitNo = !empty($rawLabExhibitNo) ? $rawLabExhibitNo : ('EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . ($caseNumericId ?? ($case->id ?? 1)));

    $rawLabNumber = $case->laboratory_number ?? ($labExam['laboratory_number'] ?? ($labExam['lab_number'] ?? null));
    $labNumber = !empty($rawLabNumber) ? $rawLabNumber : 'Pending Assignment';
    $labUnit = !empty($labExam['lab_unit']) ? $labExam['lab_unit'] : (!empty($unitProgress['Lab Analysis']['unit']) ? $unitProgress['Lab Analysis']['unit'] : 'Chemistry & Narcotics Unit');
    $currentLoggedInName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? '');
    $labAnalyst = !empty($labExam['analyst']) ? $labExam['analyst'] : (!empty($unitProgress['Lab Analysis']['officer']) ? $unitProgress['Lab Analysis']['officer'] : (!empty($currentLoggedInName) ? $currentLoggedInName : ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : 'N/A')));
    $labDateReceived = (!empty($labExam['date_received']) && $labExam['date_received'] !== 'N/A') ? $labExam['date_received'] : (!empty($docketData['lab_examination']['date_received']) && $docketData['lab_examination']['date_received'] !== 'N/A' ? $docketData['lab_examination']['date_received'] : (!empty($docketData['seizures'][0]['date_received']) && $docketData['seizures'][0]['date_received'] !== 'N/A' ? $docketData['seizures'][0]['date_received'] : (!empty($docketData['seizures'][0]['seizure_date']) && $docketData['seizures'][0]['seizure_date'] !== 'N/A' ? $docketData['seizures'][0]['seizure_date'] : (!empty($case->incident_date) ? substr((string)$case->incident_date, 0, 10) : (is_object($case->created_at) ? $case->created_at->format('Y-m-d') : substr((string)$case->created_at, 0, 10))))));
    $labDateStarted = (!empty($labExam['date_started']) && $labExam['date_started'] !== 'N/A') ? $labExam['date_started'] : (!empty($docketData['lab_examination']['date_started']) && $docketData['lab_examination']['date_started'] !== 'N/A' ? $docketData['lab_examination']['date_started'] : (!empty($docketData['seizures'][0]['date_started']) && $docketData['seizures'][0]['date_started'] !== 'N/A' ? $docketData['seizures'][0]['date_started'] : $labDateReceived));
    $labDateCompleted = (!empty($labExam['date_completed']) && $labExam['date_completed'] !== 'N/A') ? $labExam['date_completed'] : (!empty($docketData['lab_examination']['date_completed']) && $docketData['lab_examination']['date_completed'] !== 'N/A' ? $docketData['lab_examination']['date_completed'] : (!empty($docketData['seizures'][0]['date_completed']) && $docketData['seizures'][0]['date_completed'] !== 'N/A' ? $docketData['seizures'][0]['date_completed'] : $labDateStarted));
    $labPresumptive = !empty($labExam['presumptive_result']) ? $labExam['presumptive_result'] : (!empty($case->presumptive_test_result) ? $case->presumptive_test_result : 'N/A');
    $labConfirmatory = !empty($labExam['confirmatory_result']) ? $labExam['confirmatory_result'] : (!empty($case->confirmatory_test_result) ? $case->confirmatory_test_result : 'N/A');
    $labSampleWeight = $labExam['sample_weight'] ?? [];
    
    // Extract top drug exhibits first
    $topDrugExhibits = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $idx => $sz) {
            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
            $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
            $isDrugSz = $drugClassifier->isDrugExhibit($sz);
            if ($isDrugSz) {
                $topDrugExhibits[] = [
                    'index' => $idx + 1,
                    'name'  => $sName ?: 'Drug Exhibit',
                    'cat'   => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                    'qty'   => $sz['seizure_quantity'] ?? null,
                    'weight'=> $sz['seizure_weight'] ?? null,
                    'unit'  => $sz['seizure_unit'] ?? null,
                ];
            }
        }
    }
    
    $firstDrugFound = !empty($topDrugExhibits) ? $topDrugExhibits[0]['name'] : null;
    $defaultDrugName = $firstDrugFound ?: ($case->seizure_name_drug ?: ($isDrugCase ? ($case->exhibits_summary ?: 'Drug Exhibit') : 'Drug Exhibit'));
    
    $firstDrugQty = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['qty'] ?? ($topDrugExhibits[0]['seizure_quantity'] ?? null)) : $case->seizure_quantity;
    $firstDrugPkg = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['pkg'] ?? ($topDrugExhibits[0]['seizure_packaging'] ?? null)) : $case->seizure_packaging;
    $firstDrugWeight = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['weight'] ?? ($topDrugExhibits[0]['seizure_weight'] ?? null)) : $case->seizure_weight;
    $firstDrugUnit = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['unit'] ?? ($topDrugExhibits[0]['seizure_unit'] ?? null)) : $case->seizure_unit;

    $defaultUnits = !empty($firstDrugQty) ? trim($firstDrugQty . ' ' . ($firstDrugPkg ?? '')) : '';
    $defaultGrossWeight = !empty($firstDrugWeight) ? trim($firstDrugWeight . ' ' . ($firstDrugUnit ?? '')) : ($defaultUnits ?: ($case->exhibits_summary ?? ''));

    $nonDrugKeywords = ['truck', 'vehicle', 'car', 'pistol', 'rifle', 'weapon', 'currency', 'zonda', 'cash', 'usd', 'ghs', 'laptop', 'phone'];

    $rawSampleDesc = !empty($labSampleWeight['description']) ? $labSampleWeight['description'] : (!empty($case->sample_weight_description) ? $case->sample_weight_description : '');
    $isNonDrugDesc = false;
    if (!empty($rawSampleDesc)) {
        $lDesc = strtolower($rawSampleDesc);
        foreach ($nonDrugKeywords as $kw) {
            if (str_contains($lDesc, $kw)) { $isNonDrugDesc = true; break; }
        }
        if (!empty($case->seizure_name) && strtolower($case->exhibit_category) !== 'drugs' && strtolower($rawSampleDesc) === strtolower($case->seizure_name)) {
            $isNonDrugDesc = true;
        }
    }
    $labSampleDesc = (!empty($rawSampleDesc) && !$isNonDrugDesc) ? $rawSampleDesc : $defaultDrugName;

    $rawSampleUnits = !empty($labSampleWeight['units_packaging']) ? $labSampleWeight['units_packaging'] : (!empty($case->sample_weight_units) ? $case->sample_weight_units : '');
    $labSampleUnits = !empty($rawSampleUnits) ? $rawSampleUnits : $defaultUnits;

    $rawSampleGross = !empty($labSampleWeight['gross_weight']) ? $labSampleWeight['gross_weight'] : (!empty($case->sample_weight_gross) ? $case->sample_weight_gross : '');
    $isNonDrugGross = false;
    if (!empty($rawSampleGross)) {
        if (!empty($case->seizure_quantity) && strtolower($case->exhibit_category) !== 'drugs' && (trim($rawSampleGross) === trim((string)$case->seizure_quantity) || str_starts_with(trim($rawSampleGross), trim((string)$case->seizure_quantity) . ' '))) {
            $isNonDrugGross = true;
        }
    }
    $labSampleGross = (!empty($rawSampleGross) && !$isNonDrugGross) ? $rawSampleGross : $defaultGrossWeight;
    $labSampleTaken = !empty($labSampleWeight['sample_taken']) ? $labSampleWeight['sample_taken'] : (!empty($labSampleWeight['sample_weight_taken']) ? $labSampleWeight['sample_weight_taken'] : (!empty($labExam['sample_weight_taken']) ? $labExam['sample_weight_taken'] : (!empty($labExam['sample_taken']) ? $labExam['sample_taken'] : (!empty($case->sample_weight_taken) ? $case->sample_weight_taken : (!empty($docketData['seizures'][0]['sample_taken']) ? $docketData['seizures'][0]['sample_taken'] : (!empty($docketData['seizures'][0]['sample_weight_taken']) ? $docketData['seizures'][0]['sample_weight_taken'] : (!empty($dbPrimaryLabExamPdf->sample_weight_taken) ? $dbPrimaryLabExamPdf->sample_weight_taken : '')))))));
    $labSampleReserved = !empty($labSampleWeight['reserved_samples']) ? $labSampleWeight['reserved_samples'] : (!empty($labSampleWeight['sample_weight_reserved']) ? $labSampleWeight['sample_weight_reserved'] : (!empty($labExam['sample_weight_reserved']) ? $labExam['sample_weight_reserved'] : (!empty($labExam['reserved_samples']) ? $labExam['reserved_samples'] : (!empty($case->sample_weight_reserved) ? $case->sample_weight_reserved : (!empty($docketData['seizures'][0]['sample_reserved']) ? $docketData['seizures'][0]['sample_reserved'] : (!empty($docketData['seizures'][0]['sample_weight_reserved']) ? $docketData['seizures'][0]['sample_weight_reserved'] : (!empty($dbPrimaryLabExamPdf->sample_weight_reserved) ? $dbPrimaryLabExamPdf->sample_weight_reserved : '')))))));
    $labSampleUnit = !empty($firstDrugUnit) ? $firstDrugUnit : (!empty($case->seizure_unit) ? $case->seizure_unit : 'Kilograms (kg)');

    $rawSubstanceId = !empty($labExam['substance_identification']) ? $labExam['substance_identification'] : (!empty($case->confirmatory_substance_identification) ? $case->confirmatory_substance_identification : '');
    $isNonDrugSub = false;
    if (!empty($rawSubstanceId)) {
        $lSub = strtolower($rawSubstanceId);
        foreach ($nonDrugKeywords as $kw) {
            if (str_contains($lSub, $kw)) { $isNonDrugSub = true; break; }
        }
        if (!empty($case->seizure_name) && strtolower($case->exhibit_category) !== 'drugs' && strtolower($rawSubstanceId) === strtolower($case->seizure_name)) {
            $isNonDrugSub = true;
        }
    }
    $labSubstanceId = (!empty($rawSubstanceId) && !$isNonDrugSub) ? $rawSubstanceId : $defaultDrugName;
    $labDetectedSubstances = !empty($labExam['detected_substances']) ? $labExam['detected_substances'] : (!empty($case->detected_substances) ? $case->detected_substances : []);
    if (is_string($labDetectedSubstances)) {
        $labDetectedSubstances = json_decode($labDetectedSubstances, true) ?: [];
    }
    $labTestMethods = !empty($labExam['test_methods']) ? $labExam['test_methods'] : (!empty($case->confirmatory_test_methods) ? $case->confirmatory_test_methods : '');
    $labSpecifications = !empty($labExam['specifications']) ? $labExam['specifications'] : (!empty($case->confirmatory_specifications) ? $case->confirmatory_specifications : '');
    $labRemark = !empty($labExam['remark']) ? $labExam['remark'] : (!empty($case->confirmatory_remark) ? $case->confirmatory_remark : '');
    if (!empty($labSampleGross) && preg_match('/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i', $labSampleGross, $uMatch)) {
        $labSampleUnit = $uMatch[1];
    }
    $labFindings = !empty($labExam['findings']) ? $labExam['findings'] : (!empty($unitProgress['Lab Analysis']['notes']) ? $unitProgress['Lab Analysis']['notes'] : (!empty($case->laboratory_analytical_findings) ? $case->laboratory_analytical_findings : ''));
    $checkIsDrugExhibitPdf = app(\App\Services\DrugExhibitClassifier::class)->getClosure();

    $hasAnySavedLabExam = !empty($docketData['lab_examination']['has_saved'])
        || \App\Models\AssociateLabExamination::where('case_id', $case->id)->where('has_saved', true)->exists()
        || (!empty($docketData['associate_lab_examinations']) && count(array_filter($docketData['associate_lab_examinations'], fn($v) => !empty($v['has_saved']))) > 0)
        || (!empty($docketData['lab_examinations_list']) && count(array_filter($docketData['lab_examinations_list'], fn($v) => !empty($v['has_saved']))) > 0)
        || (!empty($docketData['seizures']) && count(array_filter($docketData['seizures'], fn($s) => $checkIsDrugExhibitPdf($s) && !empty($s['has_saved_lab']))) > 0);

    if (isset($unitProgress['Lab Analysis']) && !$hasAnySavedLabExam) {
        $unitProgress['Lab Analysis']['status'] = 'PENDING';
        $unitProgress['Lab Analysis']['officer'] = null;
        $unitProgress['Lab Analysis']['updated_at'] = null;
    }
    $labStatus = $hasAnySavedLabExam ? 'COMPLETED' : 'PENDING';
    $hasSavedLabData = $isDrugCase && $hasAnySavedLabExam;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Forensic Case File — {{ $case->case_number }} | NACOC Ghana</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #ffffff;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-official { font-family: 'Cinzel', serif; }

        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .pdf-table th, .pdf-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 11px;
            line-height: 1.4;
            vertical-align: middle;
        }
        .pdf-label-cell {
            background-color: #f8fafc;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: #020617;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.03em;
        }
        .pdf-value-cell {
            font-weight: 700;
            color: #0f172a;
            background-color: #ffffff;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print { display: none !important; }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }
            .pdf-watermark-container {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 480px !important;
                height: 480px !important;
                opacity: 0.08 !important;
                z-index: 0 !important;
                pointer-events: none !important;
                user-select: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm 12mm 12mm;
            }
            .avoid-break {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
        .pdf-watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 520px;
            height: 520px;
            pointer-events: none;
            user-select: none;
            z-index: 0;
            opacity: 0.07;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="p-4 sm:p-8 min-h-screen flex flex-col items-center justify-start bg-white text-slate-900">

    <!-- TOP TOOLBAR (EXCLUDED FROM PRINT) -->
    <div class="w-full max-w-4xl flex items-center justify-between mb-6 no-print">
        <a href="/cases/{{ $case->case_number }}/review" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-800 rounded-xl text-xs font-bold border border-slate-300 transition-all flex items-center gap-2 cursor-pointer no-underline shadow-sm">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Case Review</span>
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-600 text-white rounded-xl text-xs font-extrabold shadow-md transition-all flex items-center gap-2 cursor-pointer border border-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Official A4 Case File</span>
            </button>
        </div>
    </div>

    <!-- MAIN A4 PRINT CONTAINER -->
    <div class="print-container w-full max-w-4xl p-6 sm:p-8 space-y-6 text-slate-900 relative overflow-hidden">

        <!-- OFFICIAL NACOC WATERMARK LOGO BACKGROUND -->
        <div class="pdf-watermark-container pointer-events-none select-none" aria-hidden="true">
            <img src="{{ asset('nacoc.jfif') }}" 
                 onerror="this.onerror=null; this.src='{{ asset('NACOC_LOGO.PNG') }}';" 
                 class="w-full h-full object-contain" 
                 alt="NACOC Official Watermark Logo">
        </div>

        <!-- OFFICIAL GHANA EMBLEM & LETTERHEAD (TOP CENTER) -->
        <div class="text-center space-y-2 border-b-2 border-slate-900 pb-4 relative z-10">
            <div class="flex justify-center mb-2">
                <div class="relative w-24 h-24 flex items-center justify-center">
                    <img src="{{ asset('NACOC_LOGO.PNG') }}" 
                         onerror="this.onerror=null; this.src='{{ asset('nacoc.jfif') }}';" 
                         class="max-h-24 max-w-24 object-contain mx-auto" 
                         alt="NACOC Emblem Logo">
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-xs font-extrabold font-mono-code tracking-[0.2em] text-slate-700 uppercase">REPUBLIC OF GHANA</div>
                <div class="text-lg font-black font-official tracking-wide text-slate-950 uppercase">NARCOTICS CONTROL COMMISSION (NACOC)</div>
                <div class="text-sm font-extrabold text-blue-900 font-mono-code uppercase tracking-wider">FORENSIC DEPARTMENT</div>
            </div>
            <div class="pt-2">
                <div class="w-full h-0.5 bg-slate-900"></div>
                <div class="w-full h-px bg-slate-400 mt-0.5"></div>
            </div>
        </div>

        <!-- CASE TITLE & LEGAL REFERENCE BANNER -->
        <div class="p-4 bg-slate-100/95 rounded-2xl border border-slate-300 text-center space-y-2 avoid-break relative z-10">
            <div class="text-[10px] font-mono-code font-bold text-blue-900 uppercase tracking-widest">OFFICIAL CASE FILE • CONFIDENTIAL</div>
            <h1 class="text-xl font-black text-slate-900 uppercase tracking-tight text-center mx-auto">
                {{ $case->title }}
            </h1>
            <div class="text-xs font-mono-code text-slate-600 font-bold flex flex-wrap items-center justify-center gap-2">
                <span>CASE REF NO: <span class="text-blue-700">{{ $case->case_number }}</span></span>
                @if(!empty($case->operation_code_name) || !empty($case->operation_code))
                    <span class="text-slate-400">•</span>
                    <span>OPERATION TYPE: <span class="text-slate-900">{{ $case->operation_code_name ?: $case->operation_code }}</span></span>
                @endif
            </div>
            <div class="pt-1 flex items-center justify-center">
                <div class="text-[10px] font-mono-code text-slate-500 font-bold">
                    DATE: {{ date('Y-m-d H:i') }} UTC
                </div>
            </div>
        </div>

        @php
            $rawSuspectsPdf = !empty($docketData['suspects']) && is_array($docketData['suspects']) ? array_values(array_filter($docketData['suspects'], fn($s) => !empty($s['suspect_name']) && trim($s['suspect_name']) !== 'Unnamed Suspect' && trim($s['suspect_name']) !== 'Unknown')) : [];

            $primaryAndCoNamesArrayPdf = [];
            if (!empty($rawSuspectsPdf)) {
                foreach ($rawSuspectsPdf as $s) {
                    $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                    $isAssoc = !empty($s['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                    if (!$isAssoc && !empty($s['suspect_name'])) {
                        $pParts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($s['suspect_name']))));
                        foreach ($pParts as $pp) {
                            if (!in_array($pp, $primaryAndCoNamesArrayPdf)) {
                                $primaryAndCoNamesArrayPdf[] = $pp;
                            }
                        }
                    }
                }
            }
            if (empty($primaryAndCoNamesArrayPdf) && !empty($case->suspect_name)) {
                $pParts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($case->suspect_name))));
                if (!empty($pParts)) {
                    $primaryAndCoNamesArrayPdf[] = $pParts[0];
                }
            }

            $namesMatchFuzzyPdf = function($name1, $name2) {
                if (empty($name1) || empty($name2)) return false;
                $n1 = strtolower(trim($name1));
                $n2 = strtolower(trim($name2));
                if ($n1 === $n2) return true;
                if (str_contains($n1, $n2) || str_contains($n2, $n1)) return true;

                $w1 = array_values(array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', $n1)), fn($w) => strlen($w) >= 2));
                $w2 = array_values(array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', $n2)), fn($w) => strlen($w) >= 2));

                if (empty($w1) || empty($w2)) return false;

                if (count($w1) >= 2 && count($w2) >= 2) {
                    $last1 = end($w1);
                    $last2 = end($w2);
                    if ($last1 === $last2) {
                        $first1 = $w1[0];
                        $first2 = $w2[0];
                        if ($first1 === $first2 || str_starts_with($first1, substr($first2, 0, 3)) || str_starts_with($first2, substr($first1, 0, 3))) {
                            return true;
                        }
                    }
                }
                return false;
            };

            $isDuplicatePrimaryCoEntryPdf = function($nameToCheck) use ($primaryAndCoNamesArrayPdf, $namesMatchFuzzyPdf) {
                if (empty($nameToCheck) || empty($primaryAndCoNamesArrayPdf)) return false;
                $clean = strtolower(trim($nameToCheck));
                
                $parts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', $clean)));
                if (empty($parts)) return false;

                if (count($parts) > 1) {
                    $allMatch = true;
                    foreach ($parts as $p) {
                        $found = false;
                        foreach ($primaryAndCoNamesArrayPdf as $pName) {
                            if ($namesMatchFuzzyPdf($p, $pName)) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $allMatch = false;
                            break;
                        }
                    }
                    if ($allMatch) return true;
                }

                foreach ($primaryAndCoNamesArrayPdf as $pName) {
                    if ($namesMatchFuzzyPdf($clean, $pName)) {
                        return true;
                    }
                }

                return false;
            };

            $suspectsList = [];
            if (!empty($rawSuspectsPdf)) {
                foreach ($rawSuspectsPdf as $s) {
                    $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                    $isAssoc = !empty($s['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                    if ($isAssoc && !empty($s['suspect_name']) && $isDuplicatePrimaryCoEntryPdf($s['suspect_name'])) {
                        continue;
                    }
                    $suspectsList[] = $s;
                }
            }

            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_number'   => 1,
                    'suspect_name'     => $case->suspect_name,
                    'suspect_alias'    => $case->suspect_alias ?? ($docketData['alias'] ?? null),
                    'ghana_card'       => $case->ghana_card ?? ($docketData['id_card'] ?? null),
                    'gender'           => $case->gender ?? ($docketData['gender'] ?? null),
                    'dob'              => $case->dob ?? ($docketData['dob'] ?? null),
                    'age'              => $case->age ?? ($docketData['age'] ?? null),
                    'height'           => $case->height ?? ($docketData['height'] ?? null),
                    'eye_color'        => $case->eye_color ?? ($docketData['eye_color'] ?? null),
                    'nationality'      => $case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian'),
                    'pob'              => $case->pob ?? ($docketData['pob'] ?? null),
                    'phone'            => $case->phone ?? ($docketData['phone'] ?? null),
                    'address'          => $case->address ?? ($docketData['address'] ?? null),
                    'marital_status'   => $case->marital_status ?? null,
                    'spouse_name'      => $case->spouse_name ?? null,
                    'spouse_phone'     => $case->spouse_phone ?? null,
                    'spouse_info'      => $case->spouse_info ?? null,
                    'partner_name'     => $case->partner_name ?? null,
                    'partner_phone'    => $case->partner_phone ?? null,
                    'partner_info'     => $case->partner_info ?? null,
                    'dependents'       => $case->dependents ?? ($docketData['dependents'] ?? null),
                    'raid_location'    => $case->raid_location ?: ($case->location ?: null),
                    'date_of_arrest'   => $case->arrest_date ?? null,
                    'time_of_arrest'   => $case->arrest_time ?? null,
                    'holding_facility' => $case->holding_facility ?? null,
                    'offense'          => $case->offense_description ?: ($case->description ?: null),
                    'mugshot_front'    => $case->mugshot_front ?? null,
                    'mugshot_left'     => $case->mugshot_left ?? null,
                    'mugshot_right'    => $case->mugshot_right ?? null,
                ]];
            }
        @endphp

        @php
            $primarySuspectNamesList = [];
            $coSuspectNamesList = [];
            $associateSuspectNamesList = [];
            foreach ($suspectsList as $sp) {
                $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect');
                $spIsCo = $spIsAssoc && (
                    (!empty($sp['association_role']) && (str_contains(strtolower($sp['association_role']), 'co-suspect') || str_contains(strtolower($sp['association_role']), 'co suspect')))
                    || (!empty($sp['role']) && (str_contains(strtolower($sp['role']), 'co-suspect') || str_contains(strtolower($sp['role']), 'co suspect')))
                    || (!empty($sp['suspect_role']) && (str_contains(strtolower($sp['suspect_role']), 'co-suspect') || str_contains(strtolower($sp['suspect_role']), 'co suspect')))
                );
                if (!$spIsAssoc && !empty($sp['suspect_name'])) {
                    $primarySuspectNamesList[] = $sp['suspect_name'];
                } elseif ($spIsCo && !empty($sp['suspect_name'])) {
                    $coSuspectNamesList[] = $sp['suspect_name'];
                } elseif ($spIsAssoc && !empty($sp['suspect_name'])) {
                    $associateSuspectNamesList[] = $sp['suspect_name'];
                }
            }
            $primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));
            if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                foreach ($docketData['seizures'] as $sz) {
                    $assocPerson = $sz['associated_person'] ?? null;
                    if (!empty($assocPerson) && strtolower(trim($assocPerson)) !== 'associate suspect' && strtolower(trim($assocPerson)) !== 'primary suspect' && strtolower(trim($assocPerson)) !== 'all suspects') {
                        $parts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($assocPerson))));
                        $isPrimaryOrCo = true;
                        foreach ($parts as $p) {
                            $found = false;
                            foreach ($primaryAndCoNamesList as $pName) {
                                if (strtolower(trim($pName)) === $p || str_contains(strtolower(trim($pName)), $p) || str_contains($p, strtolower(trim($pName)))) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $isPrimaryOrCo = false;
                                break;
                            }
                        }
                        if (!$isPrimaryOrCo && !in_array($assocPerson, $associateSuspectNamesList)) {
                            $associateSuspectNamesList[] = $assocPerson;
                        }
                    }
                }
            }
            $primarySuspectsCombinedNames = !empty($primaryAndCoNamesList) ? implode(', ', $primaryAndCoNamesList) : (!empty($primarySuspectNamesList) ? implode(', ', array_unique($primarySuspectNamesList)) : ($case->suspect_name ?? 'Primary Suspect'));
            $associateSuspectsCombinedNames = !empty($associateSuspectNamesList) ? implode(', ', array_unique($associateSuspectNamesList)) : 'Associate Suspect';
            $renderedPrimaryExhibit = false;

            $targetPrimaryIndex = 0;
            foreach ($suspectsList as $idx => $sp) {
                $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect');
                if (!$spIsAssoc) {
                    $targetPrimaryIndex = $idx;
                }
            }
        @endphp

        @foreach($suspectsList as $sIdx => $sItem)
            @php
                $sNum   = $sItem['suspect_number'] ?? ($sIdx + 1);
                $sName  = $sItem['suspect_name'] ?? ('Suspect #' . $sNum);
                $sAlias = $sItem['suspect_alias'] ?? ($sNum == 1 ? ($case->suspect_alias ?? ($docketData['alias'] ?? 'N/A')) : 'N/A');
                $sCard  = $sItem['ghana_card'] ?? ($sNum == 1 ? ($case->ghana_card ?? ($docketData['id_card'] ?? 'N/A')) : 'N/A');
                $sGender= $sItem['gender'] ?? ($sNum == 1 ? ($case->gender ?? ($docketData['gender'] ?? 'N/A')) : 'N/A');
                $sAge   = $sItem['age'] ?? ($sNum == 1 ? ($case->age ?? ($docketData['age'] ?? null)) : null);
                $sDOB   = $sItem['dob'] ?? ($sNum == 1 ? ($case->dob ?? ($docketData['dob'] ?? null)) : null);
                $sHeight= $sItem['height'] ?? ($sNum == 1 ? ($case->height ?? ($docketData['height'] ?? null)) : null);
                $sEye   = $sItem['eye_color'] ?? ($sNum == 1 ? ($case->eye_color ?? ($docketData['eye_color'] ?? null)) : null);
                $sNat   = $sItem['nationality'] ?? ($sNum == 1 ? ($case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian')) : 'Ghanaian');
                $sPOB   = $sItem['place_of_birth'] ?? ($sItem['hometown'] ?? ($sItem['pob'] ?? ($sNum == 1 ? ($case->pob ?? ($docketData['pob'] ?? 'N/A')) : 'N/A')));
                $sPhone = $sItem['phone_number'] ?? ($sItem['contact_phone'] ?? ($sItem['phone'] ?? ($sNum == 1 ? ($case->phone ?? ($docketData['phone'] ?? 'N/A')) : 'N/A')));
                $rawPhones = $sItem['phones'] ?? ($sItem['additional_phones'] ?? ($sNum == 1 ? ($case->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? []))) : []));
                if (is_string($rawPhones)) {
                    $rawPhones = json_decode($rawPhones, true) ?: [];
                }
                $sPhones = [];
                if (is_array($rawPhones)) {
                    foreach ($rawPhones as $rItem) {
                        if (is_array($rItem)) {
                            $rNum = trim((string)($rItem['number'] ?? ($rItem['phone'] ?? '')));
                            if ($rNum !== '') {
                                $sPhones[] = [
                                    'label'  => trim((string)($rItem['label'] ?? 'Alternate')) ?: 'Alternate',
                                    'number' => $rNum,
                                ];
                            }
                        } elseif (is_string($rItem) && trim($rItem) !== '') {
                            $sPhones[] = [
                                'label'  => 'Alternate',
                                'number' => trim($rItem),
                            ];
                        }
                    }
                }
                if (!empty($sPhone) && $sPhone !== 'N/A') {
                    $hasPrim = false;
                    foreach ($sPhones as $spEntry) {
                        if ($spEntry['number'] === $sPhone) {
                            $hasPrim = true;
                            break;
                        }
                    }
                    if (!$hasPrim) {
                        array_unshift($sPhones, [
                            'label'  => 'Primary',
                            'number' => $sPhone,
                        ]);
                    }
                }
                $sAddr  = $sItem['residential_address'] ?? ($sItem['address'] ?? ($sNum == 1 ? ($case->address ?? ($docketData['address'] ?? 'N/A')) : 'N/A'));
                $sMarital = $sItem['marital_status'] ?? ($sNum == 1 ? $case->marital_status : null);
                $sSpouseN = $sItem['spouse_name'] ?? ($sNum == 1 ? $case->spouse_name : null);
                $sSpouseP = $sItem['spouse_phone'] ?? ($sNum == 1 ? $case->spouse_phone : null);
                $sSpouseI = $sItem['spouse_info'] ?? ($sNum == 1 ? $case->spouse_info : null);
                $sPartnerN= $sItem['partner_name'] ?? ($sNum == 1 ? $case->partner_name : null);
                $sPartnerP= $sItem['partner_phone'] ?? ($sNum == 1 ? $case->partner_phone : null);
                $sPartnerI= $sItem['partner_info'] ?? ($sNum == 1 ? $case->partner_info : null);
                $sDependents = $sItem['dependents'] ?? ($sNum == 1 ? ($case->dependents ?? ($docketData['dependents'] ?? 'N/A')) : 'N/A');
                $sRaid    = $sItem['raid_location'] ?? ($sNum == 1 ? ($case->raid_location ?: ($case->location ?: 'N/A')) : 'N/A');
                $sArrestDate = $sItem['date_of_arrest'] ?? ($sNum == 1 ? $case->arrest_date : null);
                $sArrestTime = $sItem['time_of_arrest'] ?? ($sNum == 1 ? $case->arrest_time : null);
                $sFacility   = $sItem['holding_facility'] ?? ($sNum == 1 ? ($case->holding_facility ?? 'N/A') : 'N/A');
                $sOffense    = $sItem['offense'] ?? ($sNum == 1 ? ($case->offense_description ?: ($case->description ?: 'N/A')) : 'N/A');

                $sFront = $sItem['mugshot_front']
                    ?? ($sItem['photo']
                    ?? ($sItem['mugshots']['front']
                    ?? ($sNum == 1 ? ($case->mugshot_front ?? ($case->photo ?? ($docketData['mugshot_front'] ?? null))) : null)));
                $sLeft  = $sItem['mugshot_left']
                    ?? ($sItem['mugshots']['left']
                    ?? ($sNum == 1 ? ($case->mugshot_left ?? ($docketData['mugshot_left'] ?? null)) : null));
                $sRight = $sItem['mugshot_right']
                    ?? ($sItem['mugshots']['right']
                    ?? ($sNum == 1 ? ($case->mugshot_right ?? ($docketData['mugshot_right'] ?? null)) : null));

                $isAssociate = !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect') || (!empty($sItem['role']) && strtolower($sItem['role']) !== 'primary suspect');
                $isCoSuspect = $isAssociate && (
                    (!empty($sItem['association_role']) && (str_contains(strtolower($sItem['association_role']), 'co-suspect') || str_contains(strtolower($sItem['association_role']), 'co suspect')))
                    || (!empty($sItem['role']) && (str_contains(strtolower($sItem['role']), 'co-suspect') || str_contains(strtolower($sItem['role']), 'co suspect')))
                    || (!empty($sItem['suspect_role']) && (str_contains(strtolower($sItem['suspect_role']), 'co-suspect') || str_contains(strtolower($sItem['suspect_role']), 'co suspect')))
                );
                $sRoleLabel = $sItem['association_role'] ?? ($sItem['role'] ?? ($isCoSuspect ? 'CO-SUSPECT' : ($isAssociate ? 'ASSOCIATE SUSPECT' : 'PRIMARY SUSPECT')));
                $assocNum = 0;
                if ($isAssociate) {
                    foreach ($suspectsList as $cIdx => $cSp) {
                        $cAssoc = !empty($cSp['is_associate']) || (!empty($cSp['association_role']) && strtolower($cSp['association_role']) !== 'primary suspect');
                        if ($cAssoc) {
                            $assocNum++;
                            if ($cIdx === $sIdx) break;
                        }
                    }
                }
                $sId = $sItem['associate_id'] ?? ($sItem['suspect_id'] ?? ($isAssociate ? ('ASC-' . str_pad($assocNum ?: 1, 3, '0', STR_PAD_LEFT)) : 'PRIM-001'));
            @endphp

            <!-- MUGSHOT PHOTOS (3-ANGLE BIOMETRIC CAPTURE FOR SUSPECT #{{ $sNum }}) -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>SUSPECT #{{ $sNum }} BIOMETRIC MUGSHOT RECORD ({{ strtoupper($sName) }})</span>
                    @if($isAssociate)
                        <span class="text-[10px] text-amber-900 bg-amber-100 px-2 py-0.5 rounded border border-amber-300 font-black">{{ strtoupper($sRoleLabel) }} (ID: {{ $sId }})</span>
                    @else
                        <span class="text-[10px] text-slate-700 font-bold">BOOKING FACILITY CAPTURE</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-4 pt-1">
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 1</span>
                            <span class="text-slate-700 uppercase">Frontal View</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sFront))
                                <img src="{{ $getImgSrc($sFront) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Frontal Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>No Front Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 2</span>
                            <span class="text-slate-700 uppercase">Left Profile</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sLeft))
                                <img src="{{ $getImgSrc($sLeft) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Left Profile Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    <span>No Left Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 3</span>
                            <span class="text-slate-700 uppercase">Right Profile</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sRight))
                                <img src="{{ $getImgSrc($sRight) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Right Profile Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    <span>No Right Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: SUSPECT BOOKING & PERSONAL IDENTITY FOR SUSPECT #{{ $sNum }} -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>SUSPECT #{{ $sNum }} PERSONAL IDENTITY ({{ strtoupper($sName) }})</span>
                    @if($isAssociate)
                        <span class="text-[10px] text-amber-900 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">{{ strtoupper($sRoleLabel) }}</span>
                    @endif
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Suspect Role / Type:</td>
                            <td class="pdf-value-cell font-black {{ $isAssociate ? 'text-amber-900 bg-amber-50' : 'text-slate-950' }} w-3/10">
                                {{ strtoupper($sRoleLabel) }}
                            </td>
                            <td class="pdf-label-cell w-1/5">Full Name:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $sName }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Alias / Street Name:</td>
                            <td class="pdf-value-cell font-black text-slate-900">{{ $sAlias }}</td>
                            <td class="pdf-label-cell">Ghana Card / ID #:</td>
                            <td class="pdf-value-cell font-mono-code font-black text-blue-900">{{ $sCard }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Gender:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ !empty($sGender) ? $sGender : 'N/A' }}</td>
                            <td class="pdf-label-cell">Age:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ !empty($sAge) ? $sAge . ' Yrs' : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Date of Birth (DOB):</td>
                            <td class="pdf-value-cell font-bold font-mono-code text-slate-900">
                                @if(!empty($sDOB))
                                    {{ strtotime($sDOB) !== false ? date('d/m/Y', strtotime($sDOB)) : $sDOB }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="pdf-label-cell">Height:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ !empty($sHeight) ? $sHeight : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Eye Color:</td>
                            <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ !empty($sEye) ? $sEye : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Nationality:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sNat }}</td>
                            <td class="pdf-label-cell">Hometown / POB:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sPOB }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Contact Phone:</td>
                            <td class="pdf-value-cell font-mono-code font-bold text-slate-900">
                                @if(!empty($sPhones) && count($sPhones) > 0)
                                    <div class="space-y-0.5">
                                        @foreach($sPhones as $pItem)
                                            <div>
                                                <span class="text-[9px] uppercase px-1 py-0.5 bg-slate-100 border border-slate-300 rounded font-bold text-slate-700">{{ $pItem['label'] }}</span>
                                                <span class="ml-1 text-xs font-bold text-slate-900">{{ $pItem['number'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $sPhone }}
                                @endif
                            </td>
                            <td class="pdf-label-cell">Residential Address:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sAddr }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Marital Status:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sMarital ?: 'N/A' }}</td>
                            <td class="pdf-label-cell">Spouse Details:</td>
                            <td class="pdf-value-cell text-slate-900">
                                @if(!empty($sSpouseN))
                                    <span class="font-extrabold text-slate-950">{{ $sSpouseN }}</span>
                                    @if(!empty($sSpouseP))
                                        <span class="font-mono-code text-slate-700 font-semibold text-[11px]">({{ $sSpouseP }})</span>
                                    @endif
                                    @if(!empty($sSpouseI))
                                        <span class="text-slate-600 font-normal italic text-[10px] block mt-0.5">— {{ $sSpouseI }}</span>
                                    @endif
                                @else
                                    <span class="font-bold text-slate-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Partner Details:</td>
                            <td class="pdf-value-cell text-slate-900">
                                @if(!empty($sPartnerN))
                                    <span class="font-extrabold text-slate-950">{{ $sPartnerN }}</span>
                                    @if(!empty($sPartnerP))
                                        <span class="font-mono-code text-slate-700 font-semibold text-[11px]">({{ $sPartnerP }})</span>
                                    @endif
                                    @if(!empty($sPartnerI))
                                        <span class="text-slate-600 font-normal italic text-[10px] block mt-0.5">— {{ $sPartnerI }}</span>
                                    @endif
                                @else
                                    <span class="font-bold text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="pdf-label-cell">Children &amp; Dependents:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sDependents }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION: OFFENCE CHARGES & ARREST DETAILS FOR SUSPECT #{{ $sNum }} -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1">
                    <span>OFFENCE CHARGES &amp; ARREST DETAILS (SUSPECT #{{ $sNum }}: {{ strtoupper($sName) }})</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Arrest Scene Location:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $sRaid }}</td>
                            <td class="pdf-label-cell w-1/5">Arrest Date:</td>
                            <td class="pdf-value-cell font-bold font-mono-code w-3/10">
                                {{ $sArrestDate ? \Carbon\Carbon::parse($sArrestDate)->format('d/m/Y') : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Arrest Time:</td>
                            <td class="pdf-value-cell font-bold font-mono-code text-slate-900" colspan="3">
                                {{ !empty($sArrestTime) ? $sArrestTime : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Custody Facility:</td>
                            <td class="pdf-value-cell font-black text-slate-900" colspan="3">{{ $sFacility }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Brief Facts &amp; Description:</td>
                            <td class="pdf-value-cell font-medium text-slate-900 leading-relaxed" colspan="3">
                                {{ $sOffense }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- SUSPECT CLINICAL DRUG SCREENING RECORD --}}
            @php
                $pdfDrugScreening = \App\Models\DrugScreening::forSuspect(
                    $sItem['id'] ?? null,
                    $sName,
                    $case->id ?? null,
                    $case->case_number ?? null
                );
            @endphp
            @if(!empty($pdfDrugScreening))
            <div class="space-y-1.5 avoid-break relative z-10 pt-2">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>DRUG SCREENING REPORT &mdash; {{ strtoupper($sName) }}</span>
                    @php
                        $pdfRes = strtoupper($pdfDrugScreening->result ?? 'PENDING');
                        $pdfTextCls = $pdfRes === 'POSITIVE' ? 'text-rose-700' : ($pdfRes === 'NEGATIVE' ? 'text-emerald-700' : 'text-amber-700');
                    @endphp
                    <span class="text-[10px] font-mono-code font-black {{ $pdfTextCls }}">
                        SCREENING OUTCOME: {{ $pdfRes }}
                    </span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Screening Dossier #:</td>
                            <td class="pdf-value-cell font-mono-code font-black text-slate-900 w-3/10">
                                SCR-{{ str_pad($pdfDrugScreening->id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="pdf-label-cell w-1/5">Date of Screening:</td>
                            <td class="pdf-value-cell font-mono-code font-bold text-slate-900 w-3/10">
                                {{ $pdfDrugScreening->formatted_date }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Purpose of Screening:</td>
                            <td class="pdf-value-cell font-bold text-slate-900" colspan="3">
                                {{ $pdfDrugScreening->purpose }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Screening Outcome:</td>
                            <td class="pdf-value-cell font-mono-code font-black {{ $pdfRes === 'POSITIVE' ? 'text-rose-800' : ($pdfRes === 'NEGATIVE' ? 'text-emerald-800' : 'text-amber-800') }}">
                                {{ $pdfRes }}
                            </td>
                            <td class="pdf-label-cell">Substance(s) Found:</td>
                            <td class="pdf-value-cell font-bold font-mono-code text-slate-900">
                                {{ $pdfDrugScreening->substances_found ?: ($pdfRes === 'NEGATIVE' ? 'No drug(s) detected' : 'None specified') }}
                            </td>
                        </tr>
                        @if(!empty($pdfDrugScreening->notes) || !empty($pdfDrugScreening->note))
                        <tr>
                            <td class="pdf-label-cell">Clinical Remarks:</td>
                            <td class="pdf-value-cell font-medium text-slate-800 leading-relaxed" colspan="3">
                                {{ $pdfDrugScreening->notes ?? $pdfDrugScreening->note }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="pdf-label-cell">Examining Authority:</td>
                            <td class="pdf-value-cell text-[10px] text-slate-600" colspan="3">
                                NACOC Forensic Chemistry &amp; Toxicology Laboratory &bull; Logged: {{ $pdfDrugScreening->created_at ? $pdfDrugScreening->created_at->format('d M Y, H:i') : 'Active' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            @if(!$isAssociate || $isCoSuspect)
                @php 
                    $primaryExhibitsFromDocket = $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel);
                    if (empty($primaryExhibitsFromDocket)) {
                        $directEx = $sItem['exhibits'] ?? ($sItem['seizures'] ?? ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                        if (!empty($directEx) && is_array($directEx)) {
                            $primaryExhibitsFromDocket = $directEx;
                        }
                    }
                @endphp
                @if(!empty($primaryExhibitsFromDocket) && count($primaryExhibitsFromDocket) > 0)
                <div class="space-y-2 avoid-break relative z-10 pt-1">
                    <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                        @php
                            $hasCoSuspectsPdf = !empty($suspectsList) && count($suspectsList) > 1;
                            $pdfExhibitTitle = $hasCoSuspectsPdf ? "PRIMARY & CO-SUSPECT LOGGED EXHIBITS (" . strtoupper($sName) . ")" : "PRIMARY LOGGED EXHIBITS (" . strtoupper($sName) . ")";
                            $pdfDossierBadge = $hasCoSuspectsPdf ? "PRIMARY & CO-SUSPECT DOSSIER" : "PRIMARY EXHIBIT DOSSIER";
                        @endphp
                        <span>{{ $pdfExhibitTitle }}</span>
                        <span class="text-[10px] text-slate-900 font-bold bg-slate-200 px-2 py-0.5 rounded border border-slate-300">{{ $pdfDossierBadge }}</span>
                    </div>

                    @foreach($primaryExhibitsFromDocket as $pIdx => $pEx)
                        @php
                            $pCat = $pEx['category'] ?? ($pEx['exhibit_category'] ?? ($case->exhibit_category ?: ($firstDrugFound ?: 'General Exhibit')));
                            $pName = (!empty($pEx['commodity_name']) && !in_array(strtolower(trim((string)$pEx['commodity_name'])), ['exhibit', 'exhibits']))
                                ? $pEx['commodity_name']
                                : ($pEx['seizure_name_currency'] ?? ($pEx['seizure_name_drug'] ?? ($pEx['seizure_name'] ?? ($pEx['name'] ?? ($case->commodity_name ?: ($case->seizure_name ?: ($case->seizure_name_drug ?: 'N/A')))))));
                            $pExNo = $pEx['exhibit_number'] ?? null;
                            $pQty = (in_array(strtolower(trim((string)$pCat)), ['money', 'currency', 'cash']) || !empty($pEx['amount']))
                                ? ($pEx['amount'] ?? ($pEx['seizure_quantity'] ?? ($pEx['quantity'] ?? null)))
                                : ($pEx['seizure_quantity'] ?? ($pEx['quantity'] ?? null));
                            $pPkg = $pEx['seizure_packaging'] ?? ($pEx['packaging'] ?? null);
                            $pWt = $pEx['seizure_weight'] ?? ($pEx['weight'] ?? null);
                            $pUnit = $pEx['seizure_unit'] ?? ($pEx['unit'] ?? '');
                            $pVal = $pEx['seizure_value'] ?? ($pEx['value'] ?? ($case->seizure_value ?: ($case->valuation ?: 'N/A')));
                            $pConceal = $pEx['seizure_concealment'] ?? ($pEx['concealment'] ?? ($case->seizure_concealment ?: 'N/A'));
                            $pDate = $pEx['seizure_date'] ?? ($pEx['date'] ?? ($case->seizure_date ?: ($case->incident_date ?: ($case->arrest_date ?? null))));
                            $pTime = $pEx['seizure_time'] ?? ($pEx['time'] ?? ($case->seizure_time ?: ($case->arrest_time ?? null)));
                            $pLoc = $pEx['seizure_location'] ?? ($pEx['location'] ?? ($case->seizure_location ?: ($case->raid_location ?: 'N/A')));
                            $pOfficer = !empty($pEx['seizure_officer']) ? $pEx['seizure_officer'] : (!empty($pEx['seizer']) ? $pEx['seizer'] : (!empty($case->seizure_officer) ? $case->seizure_officer : ((!empty($case->submitting_officer) && $case->submitting_officer !== 'Field Arrest Officer') ? $case->submitting_officer : 'N/A')));
                            $pReason = !empty($pEx['seizure_reason']) ? $pEx['seizure_reason'] : (!empty($pEx['reason']) ? $pEx['reason'] : (!empty($case->seizure_reason) ? $case->seizure_reason : ((!empty($case->operation_code_name) && $case->operation_code_name !== 'Raid') ? $case->operation_code_name : (!empty($case->operation_code) && $case->operation_code !== 'Raid' ? $case->operation_code : ($case->offense_description ?: 'N/A')))));

                            // Vehicle Specs
                            $pVehMake = $pEx['veh_make'] ?? ($pEx['make'] ?? null);
                            $pVehModel = $pEx['veh_model'] ?? ($pEx['model'] ?? null);
                            $pVehType = $pEx['veh_type'] ?? ($pEx['type'] ?? null);
                            $pVehReg = $pEx['veh_reg_no'] ?? ($pEx['reg_no'] ?? null);
                            $pVehVin = $pEx['veh_vin'] ?? ($pEx['vin'] ?? null);
                            $pVehOwner = $pEx['veh_owner'] ?? ($pEx['owner'] ?? null);

                            // Device Specs
                            $pDevModel = $pEx['dev_model'] ?? null;
                            $pDevSerial = $pEx['dev_serial_no'] ?? null;
                            $pDevImei = $pEx['dev_imei1'] ?? null;
                            $pDevStorage = $pEx['dev_storage'] ?? null;
                            $pDevColor = $pEx['dev_colour'] ?? null;

                            $hasTechSpecs = !empty($pVehMake) || !empty($pVehModel) || !empty($pVehReg) || !empty($pVehVin) || !empty($pDevModel) || !empty($pDevSerial) || !empty($pDevImei);
                        @endphp
                        <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                            <tbody>
                                <tr>
                                    <td class="pdf-label-cell w-1/5">Exhibit # &amp; Category:</td>
                                    <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">
                                        {{ $pExNo ? "{$pExNo} • " : '' }}{{ $pCat }}
                                    </td>
                                    <td class="pdf-label-cell w-1/5">Seizure / Commodity Name:</td>
                                    <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $pName }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Quantity:</td>
                                    <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                                        {{ !empty($pQty) ? $pQty : 'N/A' }}
                                    </td>
                                    <td class="pdf-label-cell">Gross Weight:</td>
                                    <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                                        {{ !empty($pWt) ? trim("{$pWt} {$pUnit}") : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Estimated Valuation:</td>
                                    <td class="pdf-value-cell font-mono-code font-black text-emerald-800">{{ $pVal }}</td>
                                    <td class="pdf-label-cell">Concealment Notes:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $pConceal }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Seizure Date:</td>
                                    <td class="pdf-value-cell font-bold font-mono-code text-slate-900">
                                        {{ $pDate ? \Carbon\Carbon::parse($pDate)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="pdf-label-cell">Seizure Time:</td>
                                    <td class="pdf-value-cell font-bold font-mono-code text-slate-900">
                                        {{ !empty($pTime) ? $pTime : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Seizing Officer:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $pOfficer }}</td>
                                    <td class="pdf-label-cell">Reason / Authority:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $pReason }}</td>
                                </tr>
                                @if($hasTechSpecs)
                                <tr>
                                    <td class="pdf-label-cell bg-slate-100">Technical Specs:</td>
                                    <td class="pdf-value-cell font-mono-code text-xs font-bold text-slate-900" colspan="3">
                                        @if(!empty($pVehMake) || !empty($pVehModel) || !empty($pVehReg))
                                            Vehicle: {{ implode(' | ', array_filter([$pVehType, "{$pVehMake} {$pVehModel}", $pVehReg ? "REG: {$pVehReg}" : null, $pVehVin ? "VIN: {$pVehVin}" : null, $pVehOwner ? "Owner: {$pVehOwner}" : null])) }}
                                        @elseif(!empty($pDevModel) || !empty($pDevSerial) || !empty($pDevImei))
                                            Device: {{ implode(' | ', array_filter([$pDevModel, $pDevSerial ? "S/N: {$pDevSerial}" : null, $pDevImei ? "IMEI: {$pDevImei}" : null, $pDevStorage ? "Storage: {$pDevStorage}" : null, $pDevColor ? "Color: {$pDevColor}" : null])) }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    @endforeach

                    <!-- SEIZED VEHICLE DETAILS RECORD -->
                    @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']))
                    <div class="pt-2 avoid-break">
                        <div class="text-[10.5px] font-mono-code font-extrabold text-slate-900 uppercase pb-1 flex items-center justify-between">
                            <span>SEIZED VEHICLE DETAILS RECORD</span>
                            <span class="text-[9px] font-mono-code font-black text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded border border-amber-300">VEHICLE LOG</span>
                        </div>
                        <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                            <tbody>
                                <tr>
                                    <td class="pdf-label-cell w-1/5">Make &amp; Model:</td>
                                    <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A') }} {{ $case->veh_model ?: ($docketData['vehicle']['model'] ?? '') }}</td>
                                    <td class="pdf-label-cell w-1/5">Registration No:</td>
                                    <td class="pdf-value-cell font-mono-code font-black text-emerald-800 w-3/10">{{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Chassis / VIN:</td>
                                    <td class="pdf-value-cell font-mono-code font-bold text-slate-900">{{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</td>
                                    <td class="pdf-label-cell">Engine Number:</td>
                                    <td class="pdf-value-cell font-mono-code font-bold text-slate-900">{{ $case->veh_engine_no ?: ($docketData['vehicle']['engine'] ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Registered Owner:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}</td>
                                    <td class="pdf-label-cell">Driver:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_driver ?: ($docketData['vehicle']['driver'] ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Owner Contact:</td>
                                    <td class="pdf-value-cell font-mono-code font-bold text-slate-900">{{ $case->veh_owner_contact ?: 'N/A' }}</td>
                                    <td class="pdf-label-cell">Color &amp; Year:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_colour ?: ($docketData['vehicle']['color'] ?? 'N/A') }} {{ $case->veh_year ? "({$case->veh_year})" : '' }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Docs Available:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_docs_available ?: 'N/A' }} {{ $case->veh_doc_ref ? "({$case->veh_doc_ref})" : '' }}</td>
                                    <td class="pdf-label-cell">Condition / Fuel:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_condition ?: 'N/A' }} {{ $case->veh_fuel ? "({$case->veh_fuel})" : '' }}</td>
                                </tr>
                                <tr>
                                    <td class="pdf-label-cell">Keys Received:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_keys_recv ?: 'N/A' }} {{ $case->veh_key_count !== null ? "({$case->veh_key_count} keys)" : '' }}</td>
                                    <td class="pdf-label-cell">Existing Damage:</td>
                                    <td class="pdf-value-cell font-bold text-slate-900">{{ $case->veh_damage ?: 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @php
                        $primaryPhotosPdf = array_values(array_filter($primaryPhotos, function($p) use ($allAssocPhotos) {
                            return !in_array($p, $allAssocPhotos);
                        }));
                    @endphp
                    @if(!empty($primaryPhotosPdf) && count($primaryPhotosPdf) > 0)
                    <div class="pt-2">
                        <div class="text-[10px] font-mono-code font-bold text-slate-800 uppercase pb-1">Primary &amp; Co-Suspect Exhibit Evidence Photos ({{ $primarySuspectsCombinedNames }})</div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($primaryPhotosPdf as $idx => $photoUrl)
                                <div class="p-1 bg-slate-50 border border-slate-300 rounded-lg space-y-1 text-center">
                                    <div class="w-full h-20 bg-slate-200 rounded overflow-hidden border border-slate-300">
                                        <img src="{{ $getImgSrc($photoUrl) }}" class="w-full h-full object-cover" alt="Primary Exhibit Photo {{ $idx + 1 }}">
                                    </div>
                                    <div class="text-[8px] font-mono-code font-bold text-slate-700 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            @endif

            <!-- SECTION: ASSOCIATE OR PRIMARY LOGGED EXHIBITS FOR SUSPECT #{{ $sNum }} -->
                @php
                    $pdfAssocExhibits = $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel);

                    if (empty($pdfAssocExhibits)) {
                        $directEx = $sItem['exhibits'] ?? ($sItem['seizures'] ?? ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                        if (!empty($directEx) && is_array($directEx)) {
                            $pdfAssocExhibits = $directEx;
                        }
                    }
                @endphp
                @if($isAssociate && !$isCoSuspect && !empty($pdfAssocExhibits) && count($pdfAssocExhibits) > 0)
                <div class="space-y-2 avoid-break relative z-10 pt-1">
                    <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                        <span>{{ $isCoSuspect ? 'CO-SUSPECT DOSSIER' : 'ASSOCIATE DOSSIER' }} (SUSPECT #{{ $sNum }}: {{ strtoupper($sName) }})</span>
                        <span class="text-[10px] text-amber-900 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">LOGGED EXHIBITS</span>
                    </div>
                    @foreach($pdfAssocExhibits as $aEx)
                    <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                        <tbody>
                            <tr>
                                @php
                                    $aCatValPdf = $aEx['category'] ?? ($aEx['exhibit_category'] ?? 'Associate Exhibit');
                                    $isMoneyPdfA = in_array(strtolower(trim((string)$aCatValPdf)), ['money', 'currency', 'cash']);
                                    $rawSzPdfA = $aEx['seizure_name'] ?? null;
                                    $rawCommPdfA = $aEx['commodity_name'] ?? ($aEx['seizure_name_currency'] ?? null);
                                    if (!empty($rawCommPdfA) && (empty($rawSzPdfA) || in_array(strtolower(trim((string)$rawSzPdfA)), ['exhibit', 'exhibits', 'general exhibit', 'confiscated exhibit']))) {
                                        $aNamePdf = $rawCommPdfA;
                                    } else {
                                        $aNamePdf = (!empty($rawCommPdfA) && !in_array(strtolower(trim((string)$rawCommPdfA)), ['exhibit', 'exhibits']))
                                            ? $rawCommPdfA
                                            : ($rawSzPdfA ?? ($aEx['name'] ?? 'N/A'));
                                    }
                                    $aQtyPdf = ($isMoneyPdfA || !empty($aEx['amount']))
                                        ? ($aEx['amount'] ?? ($aEx['seizure_quantity'] ?? ($aEx['quantity'] ?? null)))
                                        : null;
                                @endphp
                                <td class="pdf-label-cell w-1/5">Exhibit Category:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $aCatValPdf }}</td>
                                <td class="pdf-label-cell w-1/5">Exhibit / Commodity Name:</td>
                                <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $aNamePdf }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell">{{ $isMoneyPdfA || !empty($aEx['amount']) ? 'Amount / Quantity:' : 'Quantity / Weight:' }}</td>
                                <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                                    @if($isMoneyPdfA || !empty($aEx['amount']))
                                        {{ !empty($aQtyPdf) ? "Amount: {$aQtyPdf}" : 'N/A' }}
                                    @elseif(!empty($aEx['seizure_quantity']) || !empty($aEx['seizure_weight']))
                                        {{ implode(' • ', array_filter([!empty($aEx['seizure_quantity']) ? "Qty: {$aEx['seizure_quantity']}" : null, !empty($aEx['seizure_weight']) ? "Wt: {$aEx['seizure_weight']} " . ($aEx['seizure_unit'] ?? '') : null])) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="pdf-label-cell">Estimated Valuation:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-emerald-800">{{ $aEx['seizure_value'] ?? ($aEx['value'] ?? 'N/A') }}</td>
                            </tr>
                            @if(!empty($aEx['seizure_packaging']))
                            <tr>
                                <td class="pdf-label-cell">Packaging &amp; Format:</td>
                                <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $aEx['seizure_packaging'] }}</td>
                            </tr>
                            @endif

                            @php
                                $aRawOfficer = $aEx['seizure_officer'] ?? ($aEx['seizer'] ?? ($case->seizure_officer ?: ($case->submitting_officer ?: null)));
                                $aOfficer = (!empty($aRawOfficer) && $aRawOfficer !== 'Field Arrest Officer') ? $aRawOfficer : ($case->submitting_officer ?: 'N/A');

                                $aRawReason = $aEx['seizure_reason'] ?? ($aEx['reason'] ?? ($case->seizure_reason ?: ($case->operation_code_name ?: ($case->operation_code ?: null))));
                                $aReason = (!empty($aRawReason) && $aRawReason !== 'Raid') ? $aRawReason : ($case->offense_description ?: 'N/A');
                            @endphp
                            <tr>
                                <td class="pdf-label-cell">Seizing Officer:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aOfficer }}</td>
                                <td class="pdf-label-cell">Reason / Authority:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aReason }}</td>
                            </tr>

                            @php
                                $catLower = strtolower($aEx['category'] ?? ($aEx['exhibit_category'] ?? ''));
                                $nameLower = strtolower($aEx['seizure_name'] ?? ($aEx['name'] ?? ''));
                                $hasWeaponData = !empty($aEx['weapon_model']) || !empty($aEx['weapon_serial_number']) || !empty($aEx['weapon_calibre']) || !empty($aEx['weapon_loaded_status']) || !empty($aEx['weapon_condition_at_recovery']) || !empty($aEx['weapon_location_found']) || !empty($aEx['weapon_distinguishing_marks']);
                                $isWeaponEx = str_contains($catLower, 'weapon') || str_contains($nameLower, 'ak-47') || str_contains($nameLower, 'pistol') || str_contains($nameLower, 'rifle') || str_contains($nameLower, 'shotgun') || str_contains($nameLower, 'firearm') || $hasWeaponData;
                            @endphp

                            @if($isWeaponEx)
                            <tr>
                                <td class="pdf-label-cell font-mono-code text-red-900 font-extrabold uppercase" colspan="4" style="background:#fef2f2; border-top:2px solid #ef4444;">
                                    FIREARM &amp; WEAPON DETAILED SPECIFICATIONS:
                                </td>
                            </tr>
                            @if(!empty($aEx['weapon_model']) || !empty($aEx['weapon_serial_number']))
                            <tr>
                                <td class="pdf-label-cell">Model / Type:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aEx['weapon_model'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Serial Number:</td>
                                <td class="pdf-value-cell font-mono-code font-extrabold text-amber-900">{{ $aEx['weapon_serial_number'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aEx['weapon_calibre']) || !empty($aEx['weapon_color']))
                            <tr>
                                <td class="pdf-label-cell">Calibre / Gauge:</td>
                                <td class="pdf-value-cell font-mono-code font-bold text-slate-900">{{ $aEx['weapon_calibre'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Colour / Finish:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aEx['weapon_color'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aEx['weapon_loaded_status']) || !empty($aEx['weapon_condition_at_recovery']))
                            <tr>
                                <td class="pdf-label-cell">Loaded Status:</td>
                                <td class="pdf-value-cell font-bold text-red-800">{{ $aEx['weapon_loaded_status'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Recovery Condition:</td>
                                <td class="pdf-value-cell font-bold text-emerald-800">{{ $aEx['weapon_condition_at_recovery'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aEx['weapon_size_length']) || !empty($aEx['weapon_location_found']))
                            <tr>
                                <td class="pdf-label-cell">Size / Length:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aEx['weapon_size_length'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Location Found:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aEx['weapon_location_found'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aEx['weapon_distinguishing_marks']))
                            <tr>
                                <td class="pdf-label-cell">Distinguishing Marks:</td>
                                <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $aEx['weapon_distinguishing_marks'] }}</td>
                            </tr>
                            @endif
                            @endif

                            @php
                                $aAmmo = $aEx['ammunition_details'] ?? null;
                                if (is_string($aAmmo)) {
                                    $aAmmo = json_decode($aAmmo, true) ?: [];
                                }
                                $isAmmoEx = str_contains($catLower, 'ammo') || str_contains($catLower, 'ammunition') || (!empty($aAmmo) && is_array($aAmmo) && array_filter($aAmmo));
                            @endphp

                            @if($isAmmoEx && !empty($aAmmo) && is_array($aAmmo) && array_filter($aAmmo))
                            <tr>
                                <td class="pdf-label-cell font-mono-code text-amber-900 font-extrabold uppercase" colspan="4" style="background:#fffbeb; border-top:2px solid #f59e0b;">
                                    AMMUNITION DETAILED SPECIFICATIONS:
                                </td>
                            </tr>
                            @if(!empty($aAmmo['cartridge_appearance']) || !empty($aAmmo['projectile_bullet_type']))
                            <tr>
                                <td class="pdf-label-cell">Cartridge Appearance:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['cartridge_appearance'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Projectile / Bullet Type:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['projectile_bullet_type'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aAmmo['cartridge_case_material']) || !empty($aAmmo['colour_markings']))
                            <tr>
                                <td class="pdf-label-cell">Case Material:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['cartridge_case_material'] ?? 'N/A' }}</td>
                                <td class="pdf-label-cell">Colour / Markings:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['colour_markings'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aAmmo['condition']) || !empty($aAmmo['packaging_type']))
                            <tr>
                                <td class="pdf-label-cell">Condition:</td>
                                <td class="pdf-value-cell font-bold text-emerald-800">{{ $aAmmo['condition'] ?? 'N/A' }} {{ !empty($aAmmo['other_condition']) ? "({$aAmmo['other_condition']})" : '' }}</td>
                                <td class="pdf-label-cell">Packaging Type:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['packaging_type'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aAmmo['seal_tamper_evident_condition']) || !empty($aAmmo['seal_condition']) || !empty($aAmmo['package_reference_number']))
                            <tr>
                                <td class="pdf-label-cell">Seal / Tamper Condition:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">{{ $aAmmo['seal_tamper_evident_condition'] ?? ($aAmmo['seal_condition'] ?? 'N/A') }}</td>
                                <td class="pdf-label-cell">Package / Ref Number:</td>
                                <td class="pdf-value-cell font-mono-code font-extrabold text-amber-900">{{ $aAmmo['package_reference_number'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(!empty($aAmmo['condition_when_received']))
                            <tr>
                                <td class="pdf-label-cell">Condition When Received:</td>
                                <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $aAmmo['condition_when_received'] }}</td>
                            </tr>
                            @endif
                            @endif
                        </tbody>
                    </table>
                    @endforeach

                    @php
                        $assocPhotosPdf = [];
                        foreach ($pdfAssocExhibits as $aEx) {
                            if (!empty($aEx['photos'])) {
                                $pArr = is_array($aEx['photos']) ? $aEx['photos'] : (json_decode($aEx['photos'], true) ?: []);
                                if (is_array($pArr)) {
                                    foreach ($pArr as $ap) { if ($ap && !in_array($ap, $assocPhotosPdf)) $assocPhotosPdf[] = $ap; }
                                }
                            }
                            if (!empty($aEx['exhibit_photos'])) {
                                $pArr = is_array($aEx['exhibit_photos']) ? $aEx['exhibit_photos'] : (json_decode($aEx['exhibit_photos'], true) ?: []);
                                if (is_array($pArr)) {
                                    foreach ($pArr as $ap) { if ($ap && !in_array($ap, $assocPhotosPdf)) $assocPhotosPdf[] = $ap; }
                                }
                            }
                            if (!empty($aEx['evidence_photos'])) {
                                $pArr = is_array($aEx['evidence_photos']) ? $aEx['evidence_photos'] : (json_decode($aEx['evidence_photos'], true) ?: []);
                                if (is_array($pArr)) {
                                    foreach ($pArr as $ap) { if ($ap && !in_array($ap, $assocPhotosPdf)) $assocPhotosPdf[] = $ap; }
                                }
                            }
                            foreach (['photo', 'exhibit_photo', 'photo_url', 'image'] as $pKey) {
                                $apSingle = $aEx[$pKey] ?? null;
                                if ($apSingle && !in_array($apSingle, $assocPhotosPdf)) $assocPhotosPdf[] = $apSingle;
                            }
                        }
                        if (empty($assocPhotosPdf) && !empty($rawPhotos)) {
                            $assocPhotosPdf = $rawPhotos;
                        }
                        if (empty($assocPhotosPdf) && !empty($docketData['evidence_photos'])) {
                            foreach ($docketData['evidence_photos'] as $p) {
                                if ($p && !in_array($p, $assocPhotosPdf)) $assocPhotosPdf[] = $p;
                            }
                        }
                    @endphp
                    @if(!empty($assocPhotosPdf) && count($assocPhotosPdf) > 0)
                    <div class="pt-2">
                        <div class="text-[10px] font-mono-code font-bold text-amber-900 uppercase pb-1 flex items-center justify-between">
                            <span>Exhibit Visual Evidence Gallery ({{ $sName }})</span>
                            <span class="text-[9px] bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded border border-amber-300 font-mono-code font-bold">{{ count($assocPhotosPdf) }} Attached Photo(s)</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($assocPhotosPdf as $idx => $photoUrl)
                                <div class="p-1 bg-amber-50/50 border border-amber-300 rounded-lg space-y-1 text-center">
                                    <div class="w-full h-24 bg-white rounded overflow-hidden border border-amber-300">
                                        <img src="{{ $getImgSrc($photoUrl) }}" class="w-full h-full object-cover" alt="Exhibit Photo {{ $idx + 1 }}">
                                    </div>
                                    <div class="text-[8px] font-mono-code font-bold text-amber-900 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
        @endforeach

        @if($hasSavedLabData)
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                @php
                    $savedLabExamOwnerPdf = !empty($docketData['lab_examination']['exhibit_owner_name']) 
                        ? $docketData['lab_examination']['exhibit_owner_name'] 
                        : ($primarySuspectsCombinedNames ?: 'PRIMARY SUSPECT');

                    $ownerPdfLower = strtolower(trim($savedLabExamOwnerPdf));
                    $isJointPdfOwner = str_contains($ownerPdfLower, ',') || str_contains($ownerPdfLower, '&') || str_contains($ownerPdfLower, 'all suspect') || str_contains($ownerPdfLower, 'all suspects');
                    if (!$isJointPdfOwner && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
                        $mCountPdf = 0;
                        foreach ($docketData['suspects'] as $sp) {
                            if (!empty($sp['suspect_name']) && str_contains($ownerPdfLower, strtolower(trim($sp['suspect_name'])))) {
                                $mCountPdf++;
                            }
                        }
                        if ($mCountPdf > 1) {
                            $isJointPdfOwner = true;
                        }
                    }
                    if (!$isJointPdfOwner && (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1 || (!empty($docketData['suspects']) && count($docketData['suspects']) > 1))) {
                        $isJointPdfOwner = true;
                    }

                    $displayLabExamOwnerPdf = $isJointPdfOwner ? ($primarySuspectsCombinedNames ?: $savedLabExamOwnerPdf) : $savedLabExamOwnerPdf;
                @endphp
                <span>LABORATORY EXAMINATION RESULT — {{ strtoupper($displayLabExamOwnerPdf) }}</span>
                <span class="text-[10px] font-mono-code font-black {{ $hasSavedLabData ? 'text-emerald-900' : 'text-slate-600' }} uppercase">
                    {{ $hasSavedLabData ? 'OFFICIAL VERIFIED CERTIFICATE (✓ LAB COMPLETED)' : ($labStatus === 'IN_PROGRESS' ? '⏳ LAB IN PROGRESS' : '⭕ LAB PENDING — AWAITING LAB ANALYSIS') }}
                </span>
            </div>
            <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <tbody>
                    <tr>
                        <td class="pdf-label-cell w-1/5">Analysis Reference:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-emerald-900 w-3/10">{{ $labExamRef }}</td>
                        <td class="pdf-label-cell w-1/5">Exhibit Number:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-emerald-900 w-3/10">{{ $labExhibitNo }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Laboratory Number:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-slate-950">{{ $labNumber }}</td>
                        <td class="pdf-label-cell">Analyst / Examiner:</td>
                        <td class="pdf-value-cell font-black text-slate-950">{{ $labAnalyst }}</td>
                    </tr>
                    @php
                        $pdfPurposeVal = $docketData['lab_examination']['purpose_of_examination'] ?? ($case->purpose_of_examination ?? 'N/A');
                    @endphp
                    <tr>
                        <td class="pdf-label-cell">Purpose of Examination:</td>
                        <td class="pdf-value-cell font-bold text-slate-950" colspan="3">{{ !empty($pdfPurposeVal) ? $pdfPurposeVal : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Examination Timeline:</td>
                        <td class="pdf-value-cell p-1.5" colspan="3">
                            <table class="w-full text-[10.5px] border border-slate-300 rounded overflow-hidden">
                                <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9px]">
                                    <tr class="border-b border-slate-300">
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Received</th>
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Started</th>
                                        <th class="px-2 py-1 text-left bg-slate-200/70">Completed</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                    <tr>
                                        <td class="px-2 py-1 border-r border-slate-300 text-blue-900">{{ $labDateReceived }}</td>
                                        <td class="px-2 py-1 border-r border-slate-300 text-amber-900">{{ $labDateStarted }}</td>
                                        <td class="px-2 py-1 text-emerald-900">{{ $labDateCompleted }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @php
                        $pdfPrelimProcVal = $docketData['lab_examination']['preliminary_exam_procedure'] ?? ($case->preliminary_exam_procedure ?? 'N/A');
                    @endphp
                    <tr>
                        <td class="pdf-label-cell">PRELIMINARY EXAMINATION PROCEDURE:</td>
                        <td class="pdf-value-cell font-bold text-slate-950" colspan="3">{{ !empty($pdfPrelimProcVal) ? $pdfPrelimProcVal : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Presumptive Reaction:</td>
                        <td class="pdf-value-cell p-1.5">
                            @php
                                $pRawPdf = trim($labPresumptive ?? '');
                                $pRowsPdf = [];
                                if (!empty($pRawPdf) && $pRawPdf !== 'N/A') {
                                    $overallResultPdf = 'N/A';
                                    if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRawPdf, $mRes)) {
                                        $overallResultPdf = strtoupper($mRes[1]);
                                        if ($overallResultPdf === 'POS' || $overallResultPdf === 'POSITIVE') $overallResultPdf = 'YES';
                                        if ($overallResultPdf === 'NEG' || $overallResultPdf === 'NEGATIVE') $overallResultPdf = 'NO';
                                    }

                                    $cleanPPdf = $pRawPdf;
                                    if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRawPdf, $mP)) {
                                        $cleanPPdf = $mP[1];
                                    }

                                    $segmentsPdf = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanPPdf);
                                    if (empty($segmentsPdf)) $segmentsPdf = [$cleanPPdf];

                                    foreach ($segmentsPdf as $seg) {
                                        $seg = trim($seg);
                                        if (empty($seg)) continue;

                                        $presence = 'N/A';
                                        $purity = 'N/A';

                                        if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $seg, $mPres)) {
                                            $presence = trim($mPres[1]);
                                        } elseif (preg_match('/^\[\d+\]\s*([^\-\;\)]+)/i', $seg, $mPres)) {
                                            $presence = trim($mPres[1]);
                                        } elseif (!empty($seg) && !str_contains($seg, 'Purity')) {
                                            $presence = trim($seg);
                                        }

                                        if ($presence === 'YES' || $presence === 'NO' || $presence === 'POS' || $presence === 'NEG') {
                                            $presence = 'N/A';
                                        }

                                        if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $seg, $mPur)) {
                                            $purity = trim($mPur[1]);
                                        }

                                        if ($presence !== 'N/A' || $purity !== 'N/A' || count($segmentsPdf) === 1) {
                                            $pRowsPdf[] = [
                                                'result'   => $overallResultPdf,
                                                'presence' => $presence,
                                                'purity'   => $purity,
                                            ];
                                        }
                                    }
                                }
                                if (empty($pRowsPdf)) {
                                    $pRowsPdf[] = ['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A'];
                                }
                            @endphp
                            <table class="w-full text-[10.5px] border border-slate-300 rounded overflow-hidden">
                                <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9px]">
                                    <tr class="border-b border-slate-300">
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                        <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach($pRowsPdf as $pRowPdf)
                                    <tr class="border-b border-slate-200 last:border-b-0">
                                        <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pRowPdf['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pRowPdf['result'] }}</td>
                                        <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $pRowPdf['presence'] }}</td>
                                        <td class="px-2 py-1 font-bold text-slate-900">{{ $pRowPdf['purity'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td class="pdf-label-cell">Confirmatory Test:</td>
                        <td class="pdf-value-cell p-1.5">
                            @php
                                $cRowsPdf = \App\Models\ForensicCase::parseConfirmatoryRows(
                                    $labConfirmatory ?? null,
                                    $primaryLabExam->detected_substances ?? ($primaryLabResult->detected_substances ?? ($docketData['lab_examination']['detected_substances'] ?? ($labDetectedSubstances ?? []))),
                                    $primaryLabExam->detected_subtances_percentage ?? ($primaryLabExam->detected_substances_percentage ?? ($primaryLabResult->detected_subtances_percentage ?? ($primaryLabResult->detected_substances_percentage ?? ($docketData['lab_examination']['detected_subtances_percentage'] ?? ($docketData['lab_examination']['detected_substances_percentage'] ?? [])))))
                                );
                            @endphp
                            <table class="w-full text-[10.5px] border border-slate-300 rounded overflow-hidden">
                                <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9px]">
                                    <tr class="border-b border-slate-300">
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Substance Detected</th>
                                        <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach($cRowsPdf as $cRowPdf)
                                    <tr class="border-b border-slate-200 last:border-b-0">
                                        <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $cRowPdf['result'] === 'YES' ? 'text-emerald-800' : ($cRowPdf['result'] === 'NO' ? 'text-red-700' : 'text-slate-900') }}">{{ $cRowPdf['result'] }}</td>
                                        <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $cRowPdf['presence'] }}</td>
                                        <td class="px-2 py-1 font-bold text-slate-900">{{ $cRowPdf['purity'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Sample Metrology:</td>
                        <td class="pdf-value-cell p-2.5" colspan="3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Description</div>
                                    <div class="font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleDesc ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Number of Units</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleUnits ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Gross Weight</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleGross ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Sample Taken</div>
                                    @php
                                        $pdfDisplaySampleTaken = 'N/A';
                                        if (!empty($labSampleTaken) && $labSampleTaken !== 'N/A') {
                                            if (preg_match('/(kg|g|mg|lbs|grams|kilograms)/i', (string)$labSampleTaken)) {
                                                $pdfDisplaySampleTaken = $labSampleTaken;
                                            } else {
                                                $pdfDisplaySampleTaken = trim($labSampleTaken . ' ' . ($labSampleUnit ?? ''));
                                            }
                                        }
                                    @endphp
                                    <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5">{{ $pdfDisplaySampleTaken }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Reserved Samples</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleReserved ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Test Specifications:</td>
                        <td class="pdf-value-cell font-bold" colspan="3">{{ $labSpecifications ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Test Method(s):</td>
                        <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $labTestMethods ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Analysis Remark:</td>
                        <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $labRemark ?: 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- ASSOCIATE SUSPECT LABORATORY EXAMINAT        @php
                $primarySuspectNamesPdf = [];
                if (!empty($primaryAndCoNamesList) && is_array($primaryAndCoNamesList)) {
                    foreach ($primaryAndCoNamesList as $pn) {
                        if (!empty($pn)) $primarySuspectNamesPdf[] = strtolower(trim($pn));
                    }
                }
                if (!empty($suspectsList) && is_array($suspectsList)) {
                    foreach ($suspectsList as $sItem) {
                        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                        $isAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                        if (!$isAssoc && !empty($sItem['suspect_name'])) {
                            $spNameClean = strtolower(trim($sItem['suspect_name']));
                            if (!in_array($spNameClean, $primarySuspectNamesPdf)) {
                                $primarySuspectNamesPdf[] = $spNameClean;
                            }
                        }
                    }
                }

                $isPrimaryOrCoSuspectPdfName = function($nameToCheck) use ($primarySuspectNamesPdf) {
                    if (empty($nameToCheck)) return true;
                    $clean = strtolower(trim($nameToCheck));
                    if ($clean === 'primary suspect' || $clean === 'associate suspect' || $clean === 'all suspects') return true;

                    foreach ($primarySuspectNamesPdf as $pName) {
                        if ($clean === $pName || str_contains($clean, $pName) || str_contains($pName, $clean)) {
                            return true;
                        }
                    }
                    return false;
                };

                $checkIsDrugExhibitPdf = app(\App\Services\DrugExhibitClassifier::class)->getClosure();

                $associateSuspectsForPdfLab = [];
                if (!empty($suspectsList) && is_array($suspectsList)) {
                    foreach ($suspectsList as $sIdx => $sItem) {
                        $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                        $spIsAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                        $spName = $sItem['suspect_name'] ?? null;
                        if ($spIsAssoc && !empty($spName) && !$isPrimaryOrCoSuspectPdfName($spName)) {
                            $associateSuspectsForPdfLab[] = [
                                'index' => $sItem['suspect_number'] ?? ($sIdx + 1),
                                'name'  => $spName,
                                'item'  => $sItem
                            ];
                        }
                    }
                }
                if (!empty($docketData['associated_suspects']) && is_array($docketData['associated_suspects'])) {
                    foreach ($docketData['associated_suspects'] as $aIdx => $aSp) {
                        $aName = $aSp['suspect_name'] ?? ($aSp['name'] ?? null);
                        if ($aName && !$isPrimaryOrCoSuspectPdfName($aName)) {
                            $aNameLower = strtolower(trim($aName));
                            $alreadyAdded = false;
                            foreach ($associateSuspectsForPdfLab as $existingAssoc) {
                                if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                                    $alreadyAdded = true;
                                    break;
                                }
                            }
                            if (!$alreadyAdded) {
                                $associateSuspectsForPdfLab[] = [
                                    'index' => count($suspectsList) + $aIdx + 1,
                                    'name'  => $aName,
                                    'item'  => $aSp
                                ];
                            }
                        }
                    }
                }
                if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                    foreach ($docketData['seizures'] as $szItem) {
                        $assocP = $szItem['associated_person'] ?? null;
                        if (!empty($assocP) && !$isPrimaryOrCoSuspectPdfName($assocP) && $checkIsDrugExhibitPdf($szItem)) {
                            $aNameLower = strtolower(trim($assocP));
                            $alreadyAdded = false;
                            foreach ($associateSuspectsForPdfLab as $existingAssoc) {
                                if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                                    $alreadyAdded = true;
                                    break;
                                }
                            }
                            if (!$alreadyAdded) {
                                $associateSuspectsForPdfLab[] = [
                                    'index' => count($associateSuspectsForPdfLab) + 2,
                                    'name'  => $assocP,
                                    'item'  => ['suspect_name' => $assocP, 'is_associate' => true]
                                ];
                            }
                        }
                    }
                }
        @endphp

        @foreach($associateSuspectsForPdfLab as $assocLabInfo)
                @php
                    $aSpName = $assocLabInfo['name'];
                    $aSpIndex = $assocLabInfo['index'];
                    $aSpId = $assocLabInfo['item']['associate_id'] ?? ($assocLabInfo['item']['suspect_id'] ?? ($assocLabInfo['associate_id'] ?? ($assocLabInfo['suspect_id'] ?? null)));

                    $dbAssocExamPdf = $case->associateLabExaminations ? $case->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($aSpName))) : null;
                    $docketAssocExamPdf = null;
                    if (!empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
                        foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                            if (strtolower(trim($kAssoc)) === strtolower(trim($aSpName))) {
                                $docketAssocExamPdf = $vAssoc;
                                break;
                            }
                        }
                    }

                    // Resolve associate exhibits via the model's authoritative suspect-scoped method.
                    $assocExhibitsList = array_values(array_filter(
                        $case->getExhibitsForSuspect($aSpName, $assocLabInfo['item']['id'] ?? null, $aSpId, 'Associate Suspect'),
                        $checkIsDrugExhibitPdf
                    ));
                    $firstAssocEx = !empty($assocExhibitsList) ? $assocExhibitsList[0] : null;

                    $assocLabExamPdf = null;
                    if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
                        foreach ($docketData['lab_examinations_list'] as $lItem) {
                            if (!empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($aSpName))) {
                                $assocLabExamPdf = $lItem;
                                break;
                            }
                        }
                    }

                    $hasThisAssocSavedLabPdf = false;
                    if ($dbAssocExamPdf && $dbAssocExamPdf->has_saved) {
                        $hasThisAssocSavedLabPdf = true;
                    } elseif ($docketAssocExamPdf && !empty($docketAssocExamPdf['has_saved']) && !empty($firstAssocEx) && $checkIsDrugExhibitPdf($firstAssocEx)) {
                        $hasThisAssocSavedLabPdf = true;
                    } elseif (!empty($firstAssocEx) && $checkIsDrugExhibitPdf($firstAssocEx) && !empty($firstAssocEx['has_saved_lab'])) {
                        $hasThisAssocSavedLabPdf = true;
                    } elseif (!empty($assocLabExamPdf) && !empty($assocLabExamPdf['has_saved']) && !empty($firstAssocEx) && $checkIsDrugExhibitPdf($firstAssocEx)) {
                        $hasThisAssocSavedLabPdf = true;
                    }

                    $assocExamRefRaw = $dbAssocExamPdf ? $dbAssocExamPdf->exam_reference : (!empty($docketAssocExamPdf['exam_reference']) ? $docketAssocExamPdf['exam_reference'] : (!empty($firstAssocEx['exam_reference']) ? $firstAssocEx['exam_reference'] : (!empty($assocLabExamPdf['exam_reference']) ? $assocLabExamPdf['exam_reference'] : null)));
                    $assocExamRef = !empty($assocExamRefRaw) ? app(\App\Services\CaseReferenceService::class)->normalizeExamReference($assocExamRefRaw) : app(\App\Services\CaseReferenceService::class)->generateExamReference('Lab Analysis');

                    $assocExhibitNo = $dbAssocExamPdf ? $dbAssocExamPdf->exhibit_number : (!empty($docketAssocExamPdf['exhibit_number']) ? $docketAssocExamPdf['exhibit_number'] : (!empty($firstAssocEx['exhibit_number']) ? $firstAssocEx['exhibit_number'] : (!empty($assocLabExamPdf['exhibit_number']) ? $assocLabExamPdf['exhibit_number'] : 'N/A')));

                    $assocLabNumberPdf = $dbAssocExamPdf && !empty($dbAssocExamPdf->laboratory_number) ? $dbAssocExamPdf->laboratory_number : (!empty($docketAssocExamPdf['laboratory_number']) ? $docketAssocExamPdf['laboratory_number'] : (!empty($assocLabExamPdf['laboratory_number']) ? $assocLabExamPdf['laboratory_number'] : (!empty($firstAssocEx['laboratory_number']) ? $firstAssocEx['laboratory_number'] : 'N/A')));

                    $assocAnalystPdf = $dbAssocExamPdf && !empty($dbAssocExamPdf->analyst) ? $dbAssocExamPdf->analyst : (!empty($docketAssocExamPdf['analyst']) ? $docketAssocExamPdf['analyst'] : (!empty($assocLabExamPdf['analyst']) ? $assocLabExamPdf['analyst'] : (!empty($firstAssocEx['analyst']) ? $firstAssocEx['analyst'] : 'N/A')));

                    $assocDateReceivedPdf = ($dbAssocExamPdf && !empty($dbAssocExamPdf->date_received) && $dbAssocExamPdf->date_received !== 'N/A') ? (is_object($dbAssocExamPdf->date_received) ? $dbAssocExamPdf->date_received->format('Y-m-d') : substr((string)$dbAssocExamPdf->date_received, 0, 10)) : (!empty($docketAssocExamPdf['date_received']) && $docketAssocExamPdf['date_received'] !== 'N/A' ? $docketAssocExamPdf['date_received'] : (!empty($assocLabExamPdf['date_received']) && $assocLabExamPdf['date_received'] !== 'N/A' ? $assocLabExamPdf['date_received'] : (!empty($firstAssocEx['date_received']) && $firstAssocEx['date_received'] !== 'N/A' ? $firstAssocEx['date_received'] : (!empty($firstAssocEx['seizure_date']) && $firstAssocEx['seizure_date'] !== 'N/A' ? $firstAssocEx['seizure_date'] : $labDateReceived))));

                    $assocDateStartedPdf = ($dbAssocExamPdf && !empty($dbAssocExamPdf->date_started) && $dbAssocExamPdf->date_started !== 'N/A') ? (is_object($dbAssocExamPdf->date_started) ? $dbAssocExamPdf->date_started->format('Y-m-d') : substr((string)$dbAssocExamPdf->date_started, 0, 10)) : (!empty($docketAssocExamPdf['date_started']) && $docketAssocExamPdf['date_started'] !== 'N/A' ? $docketAssocExamPdf['date_started'] : (!empty($assocLabExamPdf['date_started']) && $assocLabExamPdf['date_started'] !== 'N/A' ? $assocLabExamPdf['date_started'] : (!empty($firstAssocEx['date_started']) && $firstAssocEx['date_started'] !== 'N/A' ? $firstAssocEx['date_started'] : (!empty($labDateStarted) && $labDateStarted !== 'N/A' ? $labDateStarted : $assocDateReceivedPdf))));

                    $assocDateCompletedPdf = ($dbAssocExamPdf && !empty($dbAssocExamPdf->date_completed) && $dbAssocExamPdf->date_completed !== 'N/A') ? (is_object($dbAssocExamPdf->date_completed) ? $dbAssocExamPdf->date_completed->format('Y-m-d') : substr((string)$dbAssocExamPdf->date_completed, 0, 10)) : (!empty($docketAssocExamPdf['date_completed']) && $docketAssocExamPdf['date_completed'] !== 'N/A' ? $docketAssocExamPdf['date_completed'] : (!empty($assocLabExamPdf['date_completed']) && $assocLabExamPdf['date_completed'] !== 'N/A' ? $assocLabExamPdf['date_completed'] : (!empty($firstAssocEx['date_completed']) && $firstAssocEx['date_completed'] !== 'N/A' ? $firstAssocEx['date_completed'] : (!empty($labDateCompleted) && $labDateCompleted !== 'N/A' ? $labDateCompleted : $assocDateStartedPdf))));

                    $assocDrugName = $dbAssocExamPdf ? $dbAssocExamPdf->sample_weight_description : (!empty($docketAssocExamPdf['sample_weight']['description']) ? $docketAssocExamPdf['sample_weight']['description'] : (!empty($firstAssocEx['seizure_name_drug']) ? $firstAssocEx['seizure_name_drug'] : (!empty($firstAssocEx['seizure_name']) ? $firstAssocEx['seizure_name'] : (!empty($assocLabExamPdf['substance_identification']) ? $assocLabExamPdf['substance_identification'] : (!empty($assocLabExamPdf['sample_weight']['description']) ? $assocLabExamPdf['sample_weight']['description'] : 'Drug Exhibit')))));

                    $assocUnits = $dbAssocExamPdf ? $dbAssocExamPdf->sample_weight_units : (!empty($docketAssocExamPdf['sample_weight']['units_packaging']) ? $docketAssocExamPdf['sample_weight']['units_packaging'] : (!empty($firstAssocEx['sample_units']) ? $firstAssocEx['sample_units'] : (!empty($firstAssocEx['seizure_quantity']) ? trim($firstAssocEx['seizure_quantity'] . ' ' . ($firstAssocEx['seizure_packaging'] ?? '')) : (!empty($assocLabExamPdf['sample_weight']['units_packaging']) ? $assocLabExamPdf['sample_weight']['units_packaging'] : 'N/A'))));

                    $assocGross = $dbAssocExamPdf ? $dbAssocExamPdf->sample_weight_gross : (!empty($docketAssocExamPdf['sample_weight']['gross_weight']) ? $docketAssocExamPdf['sample_weight']['gross_weight'] : (!empty($firstAssocEx['sample_gross']) ? $firstAssocEx['sample_gross'] : (!empty($firstAssocEx['seizure_weight']) ? trim($firstAssocEx['seizure_weight'] . ' ' . ($firstAssocEx['seizure_unit'] ?? '')) : (!empty($assocLabExamPdf['sample_weight']['gross_weight']) ? $assocLabExamPdf['sample_weight']['gross_weight'] : 'N/A'))));

                    $assocTaken = $dbAssocExamPdf ? $dbAssocExamPdf->sample_weight_taken : (!empty($docketAssocExamPdf['sample_weight']['sample_taken']) ? $docketAssocExamPdf['sample_weight']['sample_taken'] : (!empty($firstAssocEx['sample_taken']) ? $firstAssocEx['sample_taken'] : (!empty($assocLabExamPdf['sample_weight']['sample_taken']) ? $assocLabExamPdf['sample_weight']['sample_taken'] : 'N/A')));

                    $assocReserved = $dbAssocExamPdf ? $dbAssocExamPdf->sample_weight_reserved : (!empty($docketAssocExamPdf['sample_weight']['reserved_samples']) ? $docketAssocExamPdf['sample_weight']['reserved_samples'] : (!empty($firstAssocEx['sample_reserved']) ? $firstAssocEx['sample_reserved'] : (!empty($assocLabExamPdf['sample_weight']['reserved_samples']) ? $assocLabExamPdf['sample_weight']['reserved_samples'] : 'N/A')));

                    $assocPresumptiveText = $dbAssocExamPdf ? $dbAssocExamPdf->presumptive_result : (!empty($docketAssocExamPdf['presumptive_result']) ? $docketAssocExamPdf['presumptive_result'] : (!empty($firstAssocEx['presumptive_result']) ? $firstAssocEx['presumptive_result'] : (!empty($assocLabExamPdf['presumptive_result']) ? $assocLabExamPdf['presumptive_result'] : 'N/A')));

                    $assocConfirmatoryVal = $dbAssocExamPdf ? $dbAssocExamPdf->confirmatory_result : (!empty($docketAssocExamPdf['confirmatory_result']) ? $docketAssocExamPdf['confirmatory_result'] : (!empty($firstAssocEx['confirmatory_result']) ? $firstAssocEx['confirmatory_result'] : (!empty($assocLabExamPdf['confirmatory_result']) ? $assocLabExamPdf['confirmatory_result'] : 'N/A')));

                    $assocSubstanceIdVal = $dbAssocExamPdf ? $dbAssocExamPdf->substance_identification : (!empty($docketAssocExamPdf['substance_identification']) ? $docketAssocExamPdf['substance_identification'] : (!empty($firstAssocEx['substance_identification']) ? $firstAssocEx['substance_identification'] : (!empty($assocLabExamPdf['substance_identification']) ? $assocLabExamPdf['substance_identification'] : 'N/A')));

                    $assocSpecsVal = $dbAssocExamPdf ? $dbAssocExamPdf->specifications : (!empty($docketAssocExamPdf['specifications']) ? $docketAssocExamPdf['specifications'] : (!empty($firstAssocEx['specifications']) ? $firstAssocEx['specifications'] : (!empty($assocLabExamPdf['specifications']) ? $assocLabExamPdf['specifications'] : 'N/A')));

                    $assocMethodsVal = $dbAssocExamPdf ? $dbAssocExamPdf->test_methods : (!empty($docketAssocExamPdf['test_methods']) ? $docketAssocExamPdf['test_methods'] : (!empty($firstAssocEx['test_methods']) ? $firstAssocEx['test_methods'] : (!empty($assocLabExamPdf['test_methods']) ? $assocLabExamPdf['test_methods'] : 'N/A')));

                    $assocFindingsVal = $dbAssocExamPdf ? $dbAssocExamPdf->findings : (!empty($docketAssocExamPdf['findings']) ? $docketAssocExamPdf['findings'] : (!empty($firstAssocEx['findings']) ? $firstAssocEx['findings'] : (!empty($firstAssocEx['notes']) ? $firstAssocEx['notes'] : (!empty($assocLabExamPdf['findings']) ? $assocLabExamPdf['findings'] : 'N/A'))));

                    $pResultAssocPdf = 'N/A';
                    $pPresenceAssocPdf = 'N/A';
                    $pPurityAssocPdf = 'N/A';
                    if (!empty($assocPresumptiveText) && $assocPresumptiveText !== 'N/A') {
                        if (str_starts_with(strtoupper($assocPresumptiveText), 'YES') || str_starts_with(strtoupper($assocPresumptiveText), 'POS')) {
                            $pResultAssocPdf = 'YES';
                        } elseif (str_starts_with(strtoupper($assocPresumptiveText), 'NO') || str_starts_with(strtoupper($assocPresumptiveText), 'NEG')) {
                            $pResultAssocPdf = 'NO';
                        } else {
                            $pResultAssocPdf = strtok($assocPresumptiveText, ' (');
                        }

                        if (preg_match('/Presence of:\s*([^-\)]+)/i', $assocPresumptiveText, $mPresAssocPdf)) {
                            $pPresenceAssocPdf = trim($mPresAssocPdf[1]);
                        } elseif (preg_match('/\(([^-\)]+)\)/', $assocPresumptiveText, $mPresAssocPdf)) {
                            $pPresenceAssocPdf = trim($mPresAssocPdf[1]);
                        }

                        if (preg_match('/Purity\/Percentage:\s*([^\)]+)/i', $assocPresumptiveText, $mPurAssocPdf)) {
                            $pPurityAssocPdf = trim($mPurAssocPdf[1]);
                        }
                    }

                    $isCoveredByMainReportPdf = false;
                    if (!empty($savedLabExamOwnerPdf)) {
                        $lOwnerLowPdf = strtolower(trim($savedLabExamOwnerPdf));
                        if (str_contains($lOwnerLowPdf, 'all suspect')) {
                            $isCoveredByMainReportPdf = true;
                        }
                    }

                    $isCoSuspectPdf = false;
                    if (!empty($docketData['suspects'])) {
                        foreach ($docketData['suspects'] as $sp) {
                            if (!empty($sp['suspect_name']) && strtolower(trim($sp['suspect_name'])) === strtolower(trim($aSpName))) {
                                if (!empty($sp['association_role']) && str_contains(strtolower($sp['association_role']), 'co-suspect')) {
                                    $isCoSuspectPdf = true;
                                }
                                break;
                            }
                        }
                    }
                    $secondaryPdfBadge = $isCoSuspectPdf ? 'CO-SUSPECT DOSSIER' : 'ASSOCIATE DOSSIER';
                @endphp

                @if($hasThisAssocSavedLabPdf && !$isCoveredByMainReportPdf)
                <div class="space-y-2 avoid-break relative z-10 mt-4">
                    <div class="text-xs font-black text-amber-950 uppercase font-mono-code border-b border-amber-400 pb-1 flex items-center justify-between bg-amber-50 p-1 rounded">
                        <span>{{ $secondaryPdfBadge }} &bull; SUSPECT #{{ $aSpIndex }}: {{ strtoupper($aSpName) }} &bull; LABORATORY EXAMINATION RESULT — {{ strtoupper($aSpName) }}</span>
                        <span class="text-[10px] font-mono-code font-black text-amber-900 uppercase">OFFICIAL {{ $isCoSuspectPdf ? 'CO-SUSPECT' : 'ASSOCIATE' }} VERIFIED CERTIFICATE</span>
                    </div>
                    <table class="pdf-table border border-amber-300 rounded-xl overflow-hidden shadow-2xs">
                        <tbody>
                            <tr>
                                <td class="pdf-label-cell w-1/5 bg-amber-50">Analysis Reference:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $assocExamRef }}</td>
                                <td class="pdf-label-cell w-1/5 bg-amber-50">Exhibit Number:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $assocExhibitNo }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Laboratory Number:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-slate-950">{{ $assocLabNumberPdf }}</td>
                                <td class="pdf-label-cell bg-amber-50">Analyst / Examiner:</td>
                                <td class="pdf-value-cell font-black text-slate-950">{{ $assocAnalystPdf }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Examination Timeline:</td>
                                <td class="pdf-value-cell p-1.5" colspan="3">
                                    <table class="w-full text-[10.5px] border border-amber-200 rounded overflow-hidden">
                                        <thead class="bg-amber-100 text-amber-950 font-mono-code uppercase font-black text-[9px]">
                                            <tr class="border-b border-amber-200">
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Received</th>
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Started</th>
                                                <th class="px-2 py-1 text-left">Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                            <tr>
                                                <td class="px-2 py-1 border-r border-amber-200 text-blue-900">{{ $assocDateReceivedPdf }}</td>
                                                <td class="px-2 py-1 border-r border-amber-200 text-amber-900">{{ $assocDateStartedPdf }}</td>
                                                <td class="px-2 py-1 text-emerald-900">{{ $assocDateCompletedPdf }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Presumptive Reaction:</td>
                                <td class="pdf-value-cell p-1.5">
                                    @php
                                        $pRawAssocPdf = trim($assocPresumptiveText ?? '');
                                        $pRowsAssocPdf = [];
                                        if (!empty($pRawAssocPdf) && $pRawAssocPdf !== 'N/A') {
                                            $overallResultAssocPdf = 'N/A';
                                            if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRawAssocPdf, $mRes)) {
                                                $overallResultAssocPdf = strtoupper($mRes[1]);
                                                if ($overallResultAssocPdf === 'POS' || $overallResultAssocPdf === 'POSITIVE') $overallResultAssocPdf = 'YES';
                                                if ($overallResultAssocPdf === 'NEG' || $overallResultAssocPdf === 'NEGATIVE') $overallResultAssocPdf = 'NO';
                                            }

                                            $cleanPAssocPdf = $pRawAssocPdf;
                                            if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRawAssocPdf, $mP)) {
                                                $cleanPAssocPdf = $mP[1];
                                            }

                                            $segmentsAssocPdf = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanPAssocPdf);
                                            if (empty($segmentsAssocPdf)) $segmentsAssocPdf = [$cleanPAssocPdf];

                                            foreach ($segmentsAssocPdf as $seg) {
                                                $seg = trim($seg);
                                                if (empty($seg)) continue;

                                                $presence = 'N/A';
                                                $purity = 'N/A';

                                                if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $seg, $mPres)) {
                                                    $presence = trim($mPres[1]);
                                                } elseif (preg_match('/^\[\d+\]\s*([^\-\;\)]+)/i', $seg, $mPres)) {
                                                    $presence = trim($mPres[1]);
                                                } elseif (!empty($seg) && !str_contains($seg, 'Purity')) {
                                                    $presence = trim($seg);
                                                }

                                                if ($presence === 'YES' || $presence === 'NO' || $presence === 'POS' || $presence === 'NEG') {
                                                    $presence = 'N/A';
                                                }

                                                if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $seg, $mPur)) {
                                                    $purity = trim($mPur[1]);
                                                }

                                                if ($presence !== 'N/A' || $purity !== 'N/A' || count($segmentsAssocPdf) === 1) {
                                                    $pRowsAssocPdf[] = [
                                                        'result'   => $overallResultAssocPdf,
                                                        'presence' => $presence,
                                                        'purity'   => $purity,
                                                    ];
                                                }
                                            }
                                        }
                                        if (empty($pRowsAssocPdf)) {
                                            $pRowsAssocPdf[] = ['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A'];
                                        }
                                    @endphp
                                    <table class="w-full text-[10.5px] border border-amber-200 rounded overflow-hidden">
                                        <thead class="bg-amber-100 text-amber-950 font-mono-code uppercase font-black text-[9px]">
                                            <tr class="border-b border-amber-200">
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Result</th>
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Presence of</th>
                                                <th class="px-2 py-1 text-left">Purity/Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($pRowsAssocPdf as $pRowAssocPdf)
                                            <tr class="border-b border-amber-100 last:border-b-0">
                                                <td class="px-2 py-1 border-r border-amber-200 font-extrabold {{ $pRowAssocPdf['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pRowAssocPdf['result'] }}</td>
                                                <td class="px-2 py-1 border-r border-amber-200 font-bold text-slate-900">{{ $pRowAssocPdf['presence'] }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">{{ $pRowAssocPdf['purity'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                <td class="pdf-label-cell bg-amber-50">Confirmatory Test:</td>
                                <td class="pdf-value-cell p-1.5">
                                    @php
                                        $cRowsAssocPdf = \App\Models\ForensicCase::parseConfirmatoryRows(
                                            $assocConfirmatoryVal ?? null,
                                            $assocDetSubs ?? ($dbAssocExam->detected_substances ?? []),
                                            $assocDetPcts ?? ($dbAssocExam->detected_subtances_percentage ?? ($dbAssocExam->detected_substances_percentage ?? []))
                                        );
                                    @endphp
                                    <table class="w-full text-[10.5px] border border-amber-200 rounded overflow-hidden">
                                        <thead class="bg-amber-100 text-amber-950 font-mono-code uppercase font-black text-[9px]">
                                            <tr class="border-b border-amber-200">
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Result</th>
                                                <th class="px-2 py-1 border-r border-amber-200 text-left">Substance Detected</th>
                                                <th class="px-2 py-1 text-left">Purity/Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($cRowsAssocPdf as $cRowAssocPdf)
                                            <tr class="border-b border-amber-100 last:border-b-0">
                                                <td class="px-2 py-1 border-r border-amber-200 font-extrabold {{ $cRowAssocPdf['result'] === 'YES' ? 'text-emerald-800' : ($cRowAssocPdf['result'] === 'NO' ? 'text-red-700' : 'text-slate-900') }}">{{ $cRowAssocPdf['result'] }}</td>
                                                <td class="px-2 py-1 border-r border-amber-200 font-bold text-slate-900">{{ $cRowAssocPdf['presence'] }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">{{ $cRowAssocPdf['purity'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Sample Metrology:</td>
                                <td class="pdf-value-cell p-2.5" colspan="3">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div class="col-span-2 sm:col-span-4 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-amber-900 uppercase tracking-wider">Description</div>
                                            <div class="font-bold text-slate-950 text-xs mt-0.5">{{ $assocDrugName }} (Associate Seizure — {{ $aSpName }})</div>
                                        </div>
                                        <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-amber-900 uppercase tracking-wider">Number of Units</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocUnits }}</div>
                                        </div>
                                        <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-amber-900 uppercase tracking-wider">Gross Weight</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocGross }}</div>
                                        </div>
                                        <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-amber-900 uppercase tracking-wider">Sample Taken</div>
                                            <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5">{{ $assocTaken }}</div>
                                        </div>
                                        <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-amber-900 uppercase tracking-wider">Reserved Samples</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocReserved }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Test Specifications:</td>
                                <td class="pdf-value-cell font-bold" colspan="3">{{ $assocSpecsVal }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell bg-amber-50">Test Method(s):</td>
                                <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $assocMethodsVal ?: 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            @endforeach
        </div>

        <!-- SECTION: DIGITAL & PHYSICAL FORENSICS EXAMINATION RESULT -->
        @php
            $digData = $unitProgress['Digital & Physical Forensics'] ?? [];
            $digStatus = strtoupper($digData['status'] ?? 'PENDING');
            $digOfficer = $digData['officer'] ?? 'N/A';
            $digNotes = $digData['notes'] ?? null;
            $hasDigitalForensics = !empty($digNotes) || $digStatus === 'COMPLETED' || !empty($docketData['digital_forensics']);
        @endphp
        @if($hasDigitalForensics)
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>DIGITAL &amp; PHYSICAL FORENSICS EXAMINATION RESULT</span>
                <span class="text-[10px] font-mono-code font-black {{ $digStatus === 'COMPLETED' ? 'text-emerald-900' : 'text-amber-900' }} uppercase">
                    {{ $digStatus === 'COMPLETED' ? '✓ DIGITAL FORENSICS COMPLETED' : ($digStatus === 'IN_PROGRESS' ? '⏳ DIGITAL FORENSICS IN PROGRESS' : '⭕ DIGITAL FORENSICS PENDING') }}
                </span>
            </div>
            <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <tbody>
                    <tr>
                        <td class="pdf-label-cell w-1/5">Forensics Examiner:</td>
                        <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $digOfficer }}</td>
                        <td class="pdf-label-cell w-1/5">Unit Status:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-blue-900 w-3/10">{{ $digStatus }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Digital Findings Summary:</td>
                        <td class="pdf-value-cell font-medium text-slate-900 leading-relaxed" colspan="3">
                            {{ $digNotes ?: 'Digital media extraction and physical forensics findings recorded.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- SECTION: CONFISCATED COMMODITIES & VAULT INVENTORY -->
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1">
                <span>CONFISCATED COMMODITIES &amp; VAULT INVENTORY (PRIMARY &amp; ASSOCIATE SUSPECTS)</span>
            </div>

            <!-- COMBINED INVENTORY TABLE FOR ALL SUSPECTS (PRIMARY & ASSOCIATE) -->
            @php
                $allSeizuresListPdf = $docketData['seizures'] ?? [];
            @endphp

            @if(!empty($allSeizuresListPdf) && count($allSeizuresListPdf) > 0)
            <div class="pt-2">
                <div class="text-[10.5px] font-mono-code font-extrabold text-slate-900 uppercase pb-1 flex items-center justify-between">
                    <span>ALL LOGGED SEIZURES &amp; EXHIBITS DOSSIER ({{ count($allSeizuresListPdf) }} ITEMS LOGGED)</span>
                    <span class="text-[9px] font-mono-code font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded border border-emerald-300">VERIFIED INVENTORY</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9.5px]">
                        <tr class="border-b border-slate-300">
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Item #</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Suspect / Owner</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Category</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Type</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Class</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Exhibit Description</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Quantity</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Weight / Unit</th>
                            <th class="px-2 py-1.5 border-r border-slate-300 text-left">Packaging</th>
                            <th class="px-2 py-1.5 text-left">Est. Valuation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white font-bold text-[10px] text-slate-900">
                        @foreach($allSeizuresListPdf as $sIdx => $szItem)
                            @php
                                $rawOwner = $szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? null));
                                $szId = $szItem['associate_id'] ?? ($szItem['suspect_id'] ?? null);

                                $matchedOwner = null;
                                $matchedRole = null;
                                if (!empty($docketData['suspects'])) {
                                    foreach ($docketData['suspects'] as $sp) {
                                        $spId = $sp['associate_id'] ?? ($sp['suspect_id'] ?? ($sp['id'] ?? null));
                                        $spName = $sp['suspect_name'] ?? null;
                                        $idMatch = !empty($szId) && !empty($spId) && strtolower(trim((string)$spId)) === strtolower(trim((string)$szId));
                                        $nameMatch = !empty($rawOwner) && !empty($spName) && strtolower(trim($spName)) === strtolower(trim($rawOwner));
                                        if ($idMatch || $nameMatch) {
                                            $matchedOwner = $spName;
                                            $matchedRole = $sp['association_role'] ?? ($sp['role'] ?? null);
                                            break;
                                        }
                                    }
                                }

                                if (empty($matchedRole) && !empty($case->suspects)) {
                                    foreach ($case->suspects as $spModel) {
                                        $spName = $spModel->suspect_name ?? null;
                                        if ((!empty($szId) && (string)$spModel->id === (string)$szId) ||
                                            (!empty($rawOwner) && !empty($spName) && strtolower(trim($spName)) === strtolower(trim($rawOwner)))) {
                                            $matchedOwner = $spName;
                                            $matchedRole = $spModel->role;
                                            break;
                                        }
                                    }
                                }

                                if (empty($matchedRole) && !empty($rawOwner)) {
                                    $rawLower = strtolower(trim($rawOwner));
                                    if (!empty($associateSuspectNamesList)) {
                                        foreach ($associateSuspectNamesList as $aName) {
                                            if (strtolower(trim($aName)) === $rawLower || str_contains($rawLower, strtolower(trim($aName)))) {
                                                $matchedRole = 'Associate Suspect';
                                                break;
                                            }
                                        }
                                    }
                                    if (empty($matchedRole) && !empty($coSuspectNamesList)) {
                                        foreach ($coSuspectNamesList as $cName) {
                                            if (strtolower(trim($cName)) === $rawLower || str_contains($rawLower, strtolower(trim($cName)))) {
                                                $matchedRole = 'Co-Suspect';
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (!empty($rawOwner) && strtolower(trim($rawOwner)) !== 'associate suspect' && strtolower(trim($rawOwner)) !== 'primary suspect') {
                                    $ownerName = $rawOwner;
                                } elseif (!empty($matchedOwner)) {
                                    $ownerName = $matchedOwner;
                                } else {
                                    $ownerName = !empty($primarySuspectsCombinedNames) ? $primarySuspectsCombinedNames : 'Primary Suspect';
                                }

                                $ownerLower = strtolower(trim($ownerName));
                                $isJoint = str_contains($ownerLower, ',') || str_contains($ownerLower, '&') || str_contains($ownerLower, 'all suspect') || str_contains($ownerLower, 'all suspects');
                                if (!$isJoint && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
                                    $matchCount = 0;
                                    foreach ($docketData['suspects'] as $sp) {
                                        if (!empty($sp['suspect_name']) && str_contains($ownerLower, strtolower(trim($sp['suspect_name'])))) {
                                            $matchCount++;
                                        }
                                    }
                                    if ($matchCount > 1) {
                                        $isJoint = true;
                                    }
                                }

                                if ($isJoint) {
                                    $badgeLabel = 'PRIMARY & CO-SUSPECT';
                                    $badgeClass = 'text-indigo-900 bg-indigo-100 border-indigo-300';
                                } elseif ($matchedRole && str_contains(strtolower($matchedRole), 'co-suspect')) {
                                    $badgeLabel = 'CO-SUSPECT';
                                    $badgeClass = 'text-blue-900 bg-blue-100 border-blue-300';
                                } elseif (!empty($szItem['is_associate']) || ($matchedRole && strtolower(trim($matchedRole)) !== 'primary suspect' && strtolower(trim($matchedRole)) !== 'primary') || (!empty($ownerLower) && !empty($primarySuspectName) && strtolower(trim($ownerLower)) !== strtolower(trim($primarySuspectName)) && !str_contains($ownerLower, ','))) {
                                    $badgeLabel = 'ASSOCIATE';
                                    $badgeClass = 'text-amber-900 bg-amber-100 border-amber-300';
                                } else {
                                    $badgeLabel = 'PRIMARY';
                                    $badgeClass = 'text-slate-900 bg-slate-200 border-slate-300';
                                }
                                $exCat = $szItem['category'] ?? ($szItem['exhibit_category'] ?? 'General Exhibit');
                                $exType = $szItem['exhibit_type'] ?? (strtolower($exCat) === 'drugs' ? 'Drug Exhibit' : 'Non-Drug Exhibit');
                                $exClass = $szItem['drug_classification'] ?? 'N/A';
                                $exName = $szItem['seizure_name'] ?? ($szItem['name'] ?? 'N/A');
                                $exQty = $szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? 'N/A');
                                $exWt = $szItem['seizure_weight'] ?? ($szItem['weight'] ?? null);
                                $exUnit = $szItem['seizure_unit'] ?? ($szItem['unit'] ?? '');
                                $exPkg = $szItem['seizure_packaging'] ?? ($szItem['packaging'] ?? 'N/A');
                                $exVal = $szItem['seizure_value'] ?? ($szItem['value'] ?? 'N/A');
                            @endphp
                            <tr class="border-b border-slate-200 {{ $badgeLabel === 'ASSOCIATE' ? 'bg-amber-50/40' : ($isJoint ? 'bg-indigo-50/30' : '') }}">
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code text-slate-600">#{{ $sIdx + 1 }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code font-black text-slate-950">
                                    {{ $ownerName }}
                                    <span class="text-[8px] font-mono-code font-extrabold px-1 py-0.2 rounded border ml-0.5 {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code font-bold text-amber-900">{{ $exCat }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code text-[9px] text-slate-800">{{ $exType }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code font-bold text-[9px] text-purple-900">{{ $exClass }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-black text-slate-950">{{ $exName }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code text-slate-900">{{ $exQty }}</td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code text-slate-900">
                                    @if(!empty($exWt))
                                        {{ $exWt }} {{ $exUnit }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 border-r border-slate-200 font-mono-code text-slate-900">{{ $exPkg }}</td>
                                <td class="px-2 py-1.5 font-mono-code font-black text-emerald-800">{{ $exVal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- SEIZED FIREARM / WEAPON DETAILS RECORD -->
            @if(!empty($case->wpn_model) || !empty($case->wpn_serial_no) || !empty($docketData['weapon']['model']))
            <div class="pt-2 avoid-break">
                <div class="text-[10.5px] font-mono-code font-extrabold text-slate-900 uppercase pb-1 flex items-center justify-between">
                    <span>SEIZED FIREARM / WEAPON DETAILS RECORD</span>
                    <span class="text-[9px] font-mono-code font-black text-red-900 bg-red-100 px-1.5 py-0.5 rounded border border-red-300">FIREARM LOG</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Model / Firearm:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $case->wpn_model ?: ($docketData['weapon']['model'] ?? 'N/A') }}</td>
                            <td class="pdf-label-cell w-1/5">Serial Number:</td>
                            <td class="pdf-value-cell font-mono-code font-black text-emerald-800 w-3/10">{{ $case->wpn_serial_no ?: ($docketData['weapon']['serial_no'] ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Calibre / Gauge:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $case->wpn_calibre ?: ($docketData['weapon']['calibre'] ?? 'N/A') }}</td>
                            <td class="pdf-label-cell">Colour / Size:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $case->wpn_colour ?: ($docketData['weapon']['colour'] ?? 'N/A') }} {{ $case->wpn_size ? "({$case->wpn_size})" : '' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Loaded Status:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $case->wpn_loaded_status ?: ($docketData['weapon']['loaded_status'] ?? 'N/A') }}</td>
                            <td class="pdf-label-cell">Recovery Condition:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $case->wpn_condition ?: ($docketData['weapon']['condition'] ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Location Found:</td>
                            <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $case->wpn_location_found ?: ($docketData['weapon']['location_found'] ?? 'N/A') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            <!-- SEIZED AMMUNITION DETAILS RECORD -->
            @php
                $pdfMainAmmo = $case->ammunition_details ?: ($docketData['ammunition_details'] ?? ($docketData['seizures'][0]['ammunition_details'] ?? null));
                if (is_string($pdfMainAmmo)) {
                    $pdfMainAmmo = json_decode($pdfMainAmmo, true) ?: [];
                }
            @endphp
            @if(!empty($pdfMainAmmo) && is_array($pdfMainAmmo) && array_filter($pdfMainAmmo))
            <div class="pt-2 avoid-break">
                <div class="text-[10.5px] font-mono-code font-extrabold text-slate-900 uppercase pb-1 flex items-center justify-between">
                    <span>SEIZED AMMUNITION DETAILS RECORD</span>
                    <span class="text-[9px] font-mono-code font-black text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded border border-amber-300">AMMUNITION LOG</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Cartridge Appearance:</td>
                            <td class="pdf-value-cell font-bold text-slate-900 w-3/10">{{ $pdfMainAmmo['cartridge_appearance'] ?? 'N/A' }}</td>
                            <td class="pdf-label-cell w-1/5">Projectile / Bullet Type:</td>
                            <td class="pdf-value-cell font-bold text-slate-900 w-3/10">{{ $pdfMainAmmo['projectile_bullet_type'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Case Material:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $pdfMainAmmo['cartridge_case_material'] ?? 'N/A' }}</td>
                            <td class="pdf-label-cell">Colour / Markings:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $pdfMainAmmo['colour_markings'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Condition:</td>
                            <td class="pdf-value-cell font-bold text-emerald-800">{{ $pdfMainAmmo['condition'] ?? 'N/A' }} {{ !empty($pdfMainAmmo['other_condition']) ? "({$pdfMainAmmo['other_condition']})" : '' }}</td>
                            <td class="pdf-label-cell">Packaging Type:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $pdfMainAmmo['packaging_type'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Seal / Tamper Condition:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $pdfMainAmmo['seal_tamper_evident_condition'] ?? ($pdfMainAmmo['seal_condition'] ?? 'N/A') }}</td>
                            <td class="pdf-label-cell">Package / Ref Number:</td>
                            <td class="pdf-value-cell font-mono-code font-extrabold text-amber-900">{{ $pdfMainAmmo['package_reference_number'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Condition When Received:</td>
                            <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $pdfMainAmmo['condition_when_received'] ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- SECTION: EVIDENCE PHOTO GALLERY -->
        @if(!empty($evidenceGalleryItems) && count($evidenceGalleryItems) > 0)
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>SEIZURE &amp; EXHIBIT EVIDENCE GALLERY (PRIMARY &amp; ASSOCIATE SUSPECTS)</span>
                <span class="text-[10px] font-mono-code font-bold text-slate-700">{{ count($evidenceGalleryItems) }} ATTACHED EXHIBITS</span>
            </div>
            <div class="grid grid-cols-4 gap-3 pt-1">
                @foreach($evidenceGalleryItems as $idx => $gItem)
                    @php
                        $photoUrl = $gItem['url'];
                        $photoLabel = $gItem['label'];
                        $isAssoc = ($gItem['type'] === 'associate');
                    @endphp
                    @if(!empty($photoUrl))
                    <div class="p-1.5 {{ $isAssoc ? 'bg-amber-50/70 border-amber-300' : 'bg-slate-50 border-slate-300' }} border rounded-xl space-y-1 text-center">
                        <div class="w-full h-36 bg-slate-100 rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center p-1">
                            <img src="{{ $getImgSrc($photoUrl) }}" class="max-w-full max-h-full object-contain" alt="{{ $photoLabel }} Photo {{ $idx + 1 }}">
                        </div>
                        <div class="text-[8px] font-mono-code font-bold text-slate-700 uppercase">
                            Exhibit Photo #{{ $idx + 1 }}
                            <div class="text-[7.5px] font-mono-code font-extrabold {{ $isAssoc ? 'text-amber-900 font-black' : 'text-emerald-900 font-black' }} mt-0.5">{{ $photoLabel }}</div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif


        



        <!-- FOOTER SECURITY BAR -->
        <div class="pt-2 text-center border-t border-slate-300 text-[9px] font-mono-code text-slate-950 font-black flex items-center justify-between relative z-10">
            <div>NACOC FORENSIC DEPARTMENT • CONFIDENTIAL LAW ENFORCEMENT RECORD</div>
            <div>PAGE 1 OF 1 • A4 OFFICIAL FORMAT</div>
        </div>

    </div>

</body>
</html>
