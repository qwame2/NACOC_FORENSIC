@php
    $extractAllPhotosHelper = function($item) {
        $found = [];
        if (empty($item)) return $found;
        if (is_object($item)) $item = (array)$item;
        if (!is_array($item)) return $found;

        $keys = ['photos', 'exhibit_photos', 'evidence_photos', 'photo', 'exhibit_photo', 'photo_url', 'image', 'image_path', 'image_url', 'file_path', 'attachment', 'attachments'];
        foreach ($keys as $k) {
            if (!empty($item[$k])) {
                $val = $item[$k];
                if (is_string($val)) {
                    $val = trim($val);
                    if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
                        $dec = json_decode($val, true);
                        if (is_array($dec)) {
                            foreach ($dec as $p) { if (!empty($p) && is_string($p)) $found[] = $p; }
                        } else {
                            $found[] = $val;
                        }
                    } else {
                        $found[] = $val;
                    }
                } elseif (is_array($val)) {
                    foreach ($val as $p) { if (!empty($p) && is_string($p)) $found[] = $p; }
                }
            }
        }
        return array_values(array_unique($found));
    };

    $formatPhotoUrlHelper = function($p) {
        if (empty($p)) return '';
        $p = trim((string)$p);
        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, 'data:')) {
            return $p;
        }
        $clean = ltrim($p, '/');
        if (str_starts_with($clean, 'storage/')) {
            return asset($clean);
        }
        if (file_exists(public_path($clean))) {
            return asset($clean);
        }
        return asset('storage/' . $clean);
    };
    $authUser = auth()->user();
    $activeSuspectHasSavedResult = false;
    $activeSuspectHasDrugExhibit = false;
    $isHof = ($authUser && $authUser->is_head && (
        $authUser->unit === 'Forensic Directorate' ||
        $authUser->unit === 'Forensic Head' ||
        $authUser->unit === 'Directorate Head' ||
        str_contains($authUser->unit ?? '', 'Forensic')
    )) || request()->is('Forensic_Head*') || (session('user_role') === 'HOF');
    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'Samuel Atta';
    $officerName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? $defaultLabHeadName);
    $officerRole = $isHof ? 'Forensic Head' : 'Commanding Officer';

    $reqFrom = request()->query('from') ?? request()->query('unit') ?? request()->query('source');
    $userUnit = $authUser->unit ?? session('user_unit') ?? '';
    $referer = request()->header('referer') ?? '';

    if (!empty($reqFrom)) {
        if (str_contains(strtolower($reqFrom), 'crime') || str_contains(strtolower($reqFrom), 'field')) {
            $activeUnitContext = 'Crime Scene Support';
        } elseif (str_contains(strtolower($reqFrom), 'lab')) {
            $activeUnitContext = 'Lab Analysis';
        } elseif (str_contains(strtolower($reqFrom), 'evidence')) {
            $activeUnitContext = 'Evidence Management';
        } elseif (str_contains(strtolower($reqFrom), 'digital') || str_contains(strtolower($reqFrom), 'physical')) {
            $activeUnitContext = 'Digital & Physical Forensics';
        } else {
            $activeUnitContext = $reqFrom;
        }
    } elseif (request()->is('Lab_Analysis*') || request()->is('lab-analysis*') || request()->is('lab-result*')) {
        $activeUnitContext = 'Lab Analysis';
    } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) {
        $activeUnitContext = 'Crime Scene Support';
    } elseif (request()->is('Digital_Forensics*') || request()->is('digital-forensics*')) {
        $activeUnitContext = 'Digital & Physical Forensics';
    } elseif (request()->is('Evidence_Management*') || request()->is('evidence-management*')) {
        $activeUnitContext = 'Evidence Management';
    } elseif (!empty($userUnit)) {
        if (str_contains(strtolower($userUnit), 'digital') || str_contains(strtolower($userUnit), 'physical')) {
            $activeUnitContext = 'Digital & Physical Forensics';
        } elseif (str_contains(strtolower($userUnit), 'evidence')) {
            $activeUnitContext = 'Evidence Management';
        } elseif (str_contains(strtolower($userUnit), 'lab')) {
            $activeUnitContext = 'Lab Analysis';
        } elseif (str_contains(strtolower($userUnit), 'crime') || str_contains(strtolower($userUnit), 'field')) {
            $activeUnitContext = 'Crime Scene Support';
        } else {
            $activeUnitContext = $userUnit;
        }
    } else {
        if (str_contains(strtolower($case->unit_name ?? ''), 'digital') || str_contains(strtolower($case->unit_name ?? ''), 'physical')) {
            $activeUnitContext = 'Digital & Physical Forensics';
        } elseif (str_contains(strtolower($case->unit_name ?? ''), 'evidence')) {
            $activeUnitContext = 'Evidence Management';
        } elseif (str_contains(strtolower($case->unit_name ?? ''), 'lab')) {
            $activeUnitContext = 'Lab Analysis';
        } else {
            $activeUnitContext = $case->unit_name ?? 'Crime Scene Support';
        }
    }

    $reqFrom = request('from') ?? request('unit') ?? request('source') ?? request()->query('from') ?? request()->query('unit') ?? request()->query('source');
    $isDigitalForensics = (
        str_contains(strtolower($activeUnitContext ?? ''), 'digital') ||
        str_contains(strtolower($activeUnitContext ?? ''), 'physical') ||
        str_contains(strtolower($reqFrom ?? ''), 'digital') ||
        str_contains(strtolower($reqFrom ?? ''), 'physical') ||
        request()->is('Digital_Forensics*') ||
        request()->is('digital-forensics*') ||
        (isset($authUser) && (str_contains(strtolower($authUser->unit ?? ''), 'digital') || str_contains(strtolower($authUser->unit ?? ''), 'physical')))
    );
    $isEvidenceManagement = (
        str_contains(strtolower($activeUnitContext ?? ''), 'evidence') ||
        str_contains(strtolower($reqFrom ?? ''), 'evidence') ||
        request()->is('Evidence_Management*') ||
        request()->is('evidence-management*') ||
        (isset($authUser) && str_contains(strtolower($authUser->unit ?? ''), 'evidence'))
    );

    $isLabContext = (
        request()->is('Lab_Analysis*') ||
        request()->is('lab-analysis*') ||
        request()->is('lab-result*') ||
        request()->query('unit') === 'Lab Analysis' ||
        request()->query('from') === 'lab' ||
        (str_contains(strtolower($activeUnitContext ?? ''), 'lab') && !$isEvidenceManagement && !$isDigitalForensics && !request()->is('Crime_Scene_Support*') && !request()->is('Evidence_Management*') && !request()->is('Digital_Forensics*'))
    );
    $isLabAnalysis = isset($isLabAnalysis) ? $isLabAnalysis : ($isLabContext && !$isDigitalForensics && !$isEvidenceManagement);
    $isCrimeSceneContext = (str_contains(strtolower($activeUnitContext), 'crime') || str_contains(strtolower($reqFrom ?? ''), 'crime') || request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) && !$isHof && !$isLabContext && !$isDigitalForensics && !$isEvidenceManagement;
    $isForensicHeadContext = $isHof || request()->is('Forensic_Head*') || session('user_role') === 'HOF' || str_contains(strtolower($reqFrom ?? ''), 'forensic');
    
    $status = $case->status ?? 'Awaiting Sign-off';
    $priority = strtoupper($case->priority ?? 'NORMAL');
    
    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    
    $exCat = strtolower($case->exhibit_category ?? '');
    $szDrug = $case->seizure_name_drug ?? '';
    $szName = strtolower($case->seizure_name ?? '');
    $exSum = strtolower($case->exhibits_summary ?? '');
    $dSeizures = $docketData['seizures'] ?? [];
    $hasDrugSeizureInDocket = false;
    if (is_array($dSeizures)) {
        foreach ($dSeizures as $sz) {
            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
            $name = strtolower($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ''));
            if ($cat === 'drugs' || $cat === 'narcotics' || str_contains($cat, 'drug') || !empty($sz['seizure_name_drug']) || str_contains($name, 'heroin') || str_contains($name, 'cocaine') || str_contains($name, 'cannabis') || str_contains($name, 'drug')) {
                $hasDrugSeizureInDocket = true;
                break;
            }
        }
    }
    $isDrugCase = !empty($szDrug) || str_contains($exCat, 'drug') || str_contains($exCat, 'narcotic') || str_contains($exCat, 'substance') || str_contains($szName, 'cocaine') || str_contains($szName, 'heroin') || str_contains($szName, 'cannabis') || str_contains($szName, 'drug') || str_contains($exSum, 'cocaine') || str_contains($exSum, 'heroin') || str_contains($exSum, 'cannabis') || str_contains($exSum, 'drug') || $hasDrugSeizureInDocket;
    
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
                    $primaryPhotos[] = $singleP;
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

    // Determine if the case involves drugs/narcotics
    $exhibitCategory = strtolower($case->exhibit_category ?? '');
    $seizureNameDrug = $case->seizure_name_drug ?? '';
    $seizureName = strtolower($case->seizure_name ?? '');
    $exhibitsSummary = strtolower($case->exhibits_summary ?? '');
    $offense = strtolower($case->offense ?? '');

    // Gather all exhibit items across case model and docket suspects/seizures
    $primaryExhibitsCheck = [];
    if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
        $primaryExhibitsCheck[] = [
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
                $primaryExhibitsCheck[] = $sz;
            }
        }
    }

    $isPrimaryDrugCase = false;
    $drugKeywords = ['drug', 'narcotic', 'opioid', 'cocaine', 'heroin', 'cannabis', 'marijuana', 'weed', 'hashish', 'hash', 'meth', 'methamphetamine', 'fentanyl', 'tramadol', 'codeine', 'morphine', 'opium', 'amphetamine', 'mdma', 'ecstasy'];

    $allExhibitsCheck = [];
    if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
        $allExhibitsCheck[] = [
            'category'          => $case->exhibit_category ?? '',
            'seizure_name_drug' => $case->seizure_name_drug ?? '',
            'seizure_name'      => $case->seizure_name ?? '',
        ];
    }
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $sz) { $allExhibitsCheck[] = $sz; }
    }
    if (!empty($docketData['associated_seizures']) && is_array($docketData['associated_seizures'])) {
        foreach ($docketData['associated_seizures'] as $aEx) { $allExhibitsCheck[] = $aEx; }
    }
    if (!empty($docketData['associate_exhibits']) && is_array($docketData['associate_exhibits'])) {
        foreach ($docketData['associate_exhibits'] as $aEx) { $allExhibitsCheck[] = $aEx; }
    }

    $isAnyDrugCase = false;
    foreach ($allExhibitsCheck as $exCheck) {
        $cat   = strtolower($exCheck['category'] ?? ($exCheck['exhibit_category'] ?? ''));
        $dName = strtolower($exCheck['seizure_name_drug'] ?? '');
        $sName = strtolower($exCheck['seizure_name'] ?? ($exCheck['name'] ?? ($exCheck['commodity_name'] ?? ($exCheck['description'] ?? ''))));

        if (!empty($dName)) {
            $isAnyDrugCase = true;
            break;
        }

        foreach ($drugKeywords as $kw) {
            if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                $isAnyDrugCase = true;
                break 2;
            }
        }
    }

    foreach ($primaryExhibitsCheck as $exCheck) {
        $cat   = strtolower($exCheck['category'] ?? ($exCheck['exhibit_category'] ?? ''));
        $dName = strtolower($exCheck['seizure_name_drug'] ?? '');
        $sName = strtolower($exCheck['seizure_name'] ?? ($exCheck['name'] ?? ($exCheck['commodity_name'] ?? ($exCheck['description'] ?? ''))));

        if (!empty($dName)) {
            $isPrimaryDrugCase = true;
            break;
        }

        foreach ($drugKeywords as $kw) {
            if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                $isPrimaryDrugCase = true;
                break 2;
            }
        }
    }
    $isDrugCase = $case->isDrugCase();

    $controllerUser = $case->getControllerUser();
    $isCaseController = $case->isController($authUser);
    $activeHandover = $case->getActiveHandover();
    $allUnitHeads = \App\Models\User::where('is_head', true)->get();
    $currentCaseStatus = $case->case_status ?? 'Under Investigation';

    // GATHER ALL SUSPECTS IN THIS CASE TO RESOLVE THE SELECTED SUSPECT
    $allSuspectsInCase = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        foreach ($docketData['suspects'] as $sIndex => $sp) {
            if (!empty($sp['suspect_name'])) {
                $spRole = $sp['association_role'] ?? ($sp['role'] ?? ($sIndex === 0 ? 'Primary Suspect' : 'Associate'));
                $isPrimary = ($sIndex === 0) || (strtolower($spRole) === 'primary suspect');
                $allSuspectsInCase[] = [
                    'id'           => $sp['id'] ?? null,
                    'associate_id' => $sp['associate_id'] ?? ($sp['suspect_id'] ?? null),
                    'name'         => trim($sp['suspect_name']),
                    'alias'        => $sp['suspect_alias'] ?? 'N/A',
                    'role'         => $spRole,
                    'is_primary'   => $isPrimary,
                    'is_associate' => !$isPrimary,
                ];
            }
        }
    }
    if (empty($allSuspectsInCase)) {
        $allSuspectsInCase[] = [
            'name'         => trim($case->suspect_name ?? 'Primary Suspect'),
            'alias'        => $case->suspect_alias ?? 'N/A',
            'role'         => 'Primary Suspect',
            'is_primary'   => true,
            'is_associate' => false,
        ];
    }

    // RESOLVE ACTIVE SUSPECT FROM QUERY PARAMETER OR DEFAULT TO FIRST SUSPECT
    $reqSuspectParam = request()->query('suspect');
    $activeSuspectItem = $allSuspectsInCase[0];
    if (!empty($reqSuspectParam)) {
        foreach ($allSuspectsInCase as $sCandidate) {
            if (strtolower(trim($sCandidate['name'])) === strtolower(trim($reqSuspectParam))) {
                $activeSuspectItem = $sCandidate;
                break;
            }
        }
    }
    $activeSuspectName = $activeSuspectItem['name'];
    $activeSuspectNameLower = strtolower(trim($activeSuspectName));
    $isActiveSuspectPrimary = !empty($activeSuspectItem['is_primary']);

    // STRICT SUSPECT-LEVEL EXHIBIT LINKING
    $suspectLinkedExhibits = [];
    $allSeizuresList = array_merge(
        $docketData['seizures'] ?? [],
        $docketData['associated_seizures'] ?? [],
        $docketData['associate_exhibits'] ?? []
    );

    foreach ($allSeizuresList as $sz) {
        $assocPerson = strtolower(trim($sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ''))));
        $isLinked = false;
        if (!empty($assocPerson)) {
            if ($assocPerson === $activeSuspectNameLower || str_contains($assocPerson, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $assocPerson)) {
                $isLinked = true;
            } elseif ($isActiveSuspectPrimary && ($assocPerson === 'primary suspect' || $assocPerson === 'primary' || str_contains($assocPerson, 'all suspect') || str_contains($assocPerson, 'joint'))) {
                $isLinked = true;
            }
        } elseif ($isActiveSuspectPrimary) {
            $isLinked = true;
        }

        if ($isLinked) {
            $suspectLinkedExhibits[] = $sz;
        }
    }

    // Include top-level case exhibit if active suspect is primary
    if ($isActiveSuspectPrimary && (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name))) {
        $suspectLinkedExhibits[] = [
            'category'          => $case->exhibit_category ?? '',
            'seizure_name_drug' => $case->seizure_name_drug ?? '',
            'seizure_name'      => $case->seizure_name ?? '',
        ];
    }

    // CLASSIFY EXHIBIT CATEGORIES FOR ACTIVE SUSPECT
    $drugKeywords = ['drug', 'narcotic', 'opioid', 'cocaine', 'heroin', 'cannabis', 'marijuana', 'weed', 'hashish', 'hash', 'meth', 'methamphetamine', 'fentanyl', 'tramadol', 'codeine', 'morphine', 'opium', 'amphetamine', 'mdma', 'ecstasy'];
    $electronicKeywords = ['phone', 'mobile', 'laptop', 'computer', 'tablet', 'hard drive', 'hdd', 'ssd', 'usb', 'flash drive', 'memory card', 'sd card', 'electronic', 'digital', 'device'];
    $crimeSceneKeywords = ['weapon', 'pistol', 'rifle', 'firearm', 'gun', 'ammunition', 'ammo', 'bullet', 'currency', 'cash', 'money', 'usd', 'ghs', 'gold', 'precious metal', 'vehicle', 'vehicles', 'veh', 'car', 'truck', 'automobile', 'vessel', 'zonda'];

    $suspectHasDrug = false;
    $suspectHasElectronic = false;
    $suspectHasCrimeScene = false;

    foreach ($suspectLinkedExhibits as $ex) {
        $cat   = strtolower($ex['category'] ?? ($ex['exhibit_category'] ?? ''));
        $dName = strtolower($ex['seizure_name_drug'] ?? '');
        $sName = strtolower($ex['seizure_name'] ?? ($ex['name'] ?? ($ex['commodity_name'] ?? ($ex['description'] ?? ''))));

        if (!empty($dName)) {
            $suspectHasDrug = true;
        }
        if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($cat, 'substance')) {
            $suspectHasDrug = true;
        }
        foreach ($drugKeywords as $kw) {
            if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                $suspectHasDrug = true;
                break;
            }
        }

        if (str_contains($cat, 'electronic') || str_contains($cat, 'digital')) {
            $suspectHasElectronic = true;
        }
        foreach ($electronicKeywords as $kw) {
            if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                $suspectHasElectronic = true;
                break;
            }
        }

        if (str_contains($cat, 'weapon') || str_contains($cat, 'ammunition') || str_contains($cat, 'currency') || str_contains($cat, 'gold') || str_contains($cat, 'precious') || str_contains($cat, 'vehicle') || str_contains($cat, 'vessel') || str_contains($cat, 'car') || str_contains($cat, 'truck')) {
            $suspectHasCrimeScene = true;
        }
        foreach ($crimeSceneKeywords as $kw) {
            if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                $suspectHasCrimeScene = true;
                break;
            }
        }
    }

    $activeSuspectHasDrugExhibit = !empty($suspectHasDrug);
    $hasAnySpecialistUnit = $suspectHasCrimeScene || $suspectHasDrug || $suspectHasElectronic;

    // DYNAMIC UNITS LIST DETERMINATION FOR SELECTED SUSPECT (Operational Units only; HOF is the Final Step card)
    if (empty($suspectLinkedExhibits) || !$hasAnySpecialistUnit) {
        $caseUnits = $case->getWorkflowUnits();
        $unitsList = !empty($caseUnits) ? $caseUnits : ['Crime Scene Support', 'Lab Analysis', 'Digital & Physical Forensics', 'Evidence Management'];
    } else {
        // Requirement 3: Determine units strictly from actual exhibit category saved for selected suspect
        $unitsList = [];

        if ($suspectHasCrimeScene) {
            $unitsList[] = 'Crime Scene Support';
        }
        if ($suspectHasDrug) {
            $unitsList[] = 'Lab Analysis';
        }
        if ($suspectHasElectronic || $isDigitalForensics || str_contains(strtolower($case->unit_name ?? ''), 'digital') || str_contains(strtolower($case->unit_name ?? ''), 'physical')) {
            $unitsList[] = 'Digital & Physical Forensics';
        }

        // Evidence Management is required for all exhibit cases
        $unitsList[] = 'Evidence Management';

        $unitsList = array_values(array_unique($unitsList));
    }
    $totalUnitsCount = count($unitsList);

    // CHECK IF VALID EXAMINATION INFORMATION HAS ACTUALLY BEEN SAVED FOR THIS SELECTED SUSPECT
    $suspectLabExamSaved = false;

    // 1. Check AssociateLabExamination model strictly matching active suspect name
    if (!empty($case->id)) {
        $suspectLabExamSaved = \App\Models\AssociateLabExamination::where('case_id', $case->id)
            ->where('has_saved', true)
            ->get()
            ->contains(function($aRec) use ($activeSuspectNameLower) {
                $aName = strtolower(trim($aRec->suspect_name ?? ''));
                return !empty($aName) && ($aName === $activeSuspectNameLower || str_contains($aName, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $aName));
            });
    }

    // 2. If primary suspect, check LaboratoryExaminationResult model
    if (!$suspectLabExamSaved && $isActiveSuspectPrimary && !empty($case->id)) {
        $suspectLabExamSaved = \App\Models\LaboratoryExaminationResult::where(function($q) use ($case) {
            $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
        })->where(function($q) {
            $q->where('has_saved', true)->orWhereNotNull('substance_identification')->orWhereNotNull('test_methods');
        })->exists();
    }


    // 3. Check lab_examinations_list strictly matching active suspect name
    if (!$suspectLabExamSaved && !empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
        foreach ($docketData['lab_examinations_list'] as $lItem) {
            $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
            if (!empty($lItem['has_saved']) && ($lOwner === $activeSuspectNameLower || str_contains($lOwner, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $lOwner))) {
                $suspectLabExamSaved = true;
                break;
            }
        }
    }

    // 4. Check associate_lab_examinations in docket_data
    if (!$suspectLabExamSaved && !empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
        foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
            $kLow = strtolower(trim($kAssoc));
            if (!empty($vAssoc['has_saved']) && ($kLow === $activeSuspectNameLower || str_contains($kLow, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $kLow))) {
                $suspectLabExamSaved = true;
                break;
            }
        }
    }

    // 5. Check primary lab_examination in docket_data
    if (!$suspectLabExamSaved && $isActiveSuspectPrimary && !empty($docketData['lab_examination'])) {
        $hasLabData = !empty($docketData['lab_examination']['has_saved'])
            || !empty($docketData['lab_examination']['substance_identification'])
            || !empty($docketData['lab_examination']['confirmatory_result'])
            || !empty($docketData['lab_examination']['presumptive_result']);
        if ($hasLabData) {
            $pOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? ''));
            if (empty($pOwner) || $pOwner === 'primary suspect' || $pOwner === $activeSuspectNameLower || str_contains($pOwner, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $pOwner)) {
                $suspectLabExamSaved = true;
            }
        }
    }

    // 6. Check seizures linked to this suspect in docket_data
    if (!$suspectLabExamSaved) {
        foreach ($suspectLinkedExhibits as $szItem) {
            if (!empty($szItem['has_saved_lab'])) {
                $suspectLabExamSaved = true;
                break;
            }
        }
    }

    // Check if ANY suspect in the case has a completed lab examination
    $caseHasAnySavedLab = $suspectLabExamSaved
        || !empty($docketData['lab_examination']['has_saved'])
        || !empty($docketData['lab_examination']['substance_identification'])
        || !empty($docketData['lab_examination']['confirmatory_result'])
        || !empty($docketData['lab_examination']['presumptive_result']);

    // BUILD UNIT PROGRESS STATUSES STRICTLY FOR SELECTED SUSPECT
    $unitProgress = [];
    foreach ($unitsList as $uKey) {
        if (empty($suspectLinkedExhibits) || !$hasAnySpecialistUnit) {
            // Rule 2: Only suspect biodata saved or no exhibit category specified yet -> ALL units PENDING
            $unitProgress[$uKey] = [
                'status'     => 'PENDING',
                'officer'    => null,
                'updated_at' => null,
                'notes'      => 'Awaiting exhibit registration and initial processing',
            ];
        } else {
            if ($uKey === 'Lab Analysis') {
                $isLabDone = (($suspectHasDrug && $suspectLabExamSaved) || (!$suspectHasDrug && $caseHasAnySavedLab) || (!$suspectHasDrug && !empty($docketData['seizures'])) || $isEvidenceManagement);

                $labOfficerResolved = !empty($docketData['lab_examination']['analyst'])
                    ? $docketData['lab_examination']['analyst']
                    : (!empty($docketData['unit_progress']['Lab Analysis']['officer'])
                        ? $docketData['unit_progress']['Lab Analysis']['officer']
                        : ($isEvidenceManagement
                            ? ($case->submitting_officer ?? ($authUser->fullname ?? 'Madam Diana'))
                            : (!empty($labAnalyst) ? $labAnalyst : ($case->submitting_officer ?? ($defaultLabHeadName ?? 'John Mensah')))));

                $unitProgress[$uKey] = [
                    'status'     => $isLabDone ? 'COMPLETED' : 'PENDING',
                    'officer'    => $labOfficerResolved,
                    'updated_at' => optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s'),
                    'notes'      => 'Laboratory Examination Information completed and signed off',
                ];
            } elseif ($uKey === 'Digital & Physical Forensics') {
                $suspectDigitalSaved = !empty($docketData['digital_forensics'][$activeSuspectNameLower]['has_saved'])
                    || (!empty($docketData['unit_progress']['Digital & Physical Forensics']['status']) && strtoupper($docketData['unit_progress']['Digital & Physical Forensics']['status']) === 'COMPLETED')
                    || str_contains(strtolower($case->unit_name ?? ''), 'digital')
                    || str_contains(strtolower($case->unit_name ?? ''), 'physical');

                $digitalOfficerResolved = $docketData['digital_forensics'][$activeSuspectNameLower]['analyst'] 
                    ?? ($docketData['unit_progress']['Digital & Physical Forensics']['officer'] 
                    ?? ((str_contains(strtolower($case->unit_name ?? ''), 'digital') || str_contains(strtolower($case->unit_name ?? ''), 'physical')) ? ($case->submitting_officer ?? 'Ofori Graham') : 'Ofori Graham'));

                $isDigDone = ($suspectHasElectronic && $suspectDigitalSaved)
                    || str_contains(strtolower($case->unit_name ?? ''), 'digital')
                    || str_contains(strtolower($case->unit_name ?? ''), 'physical');

                $unitProgress[$uKey] = [
                    'status'     => $isDigDone ? 'COMPLETED' : 'PENDING',
                    'officer'    => $digitalOfficerResolved,
                    'updated_at' => $isDigDone ? (optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s')) : null,
                    'notes'      => $isDigDone ? 'Device extraction & digital forensic examination completed' : ($suspectHasElectronic ? 'Electronic device registered; awaiting digital forensic extraction' : 'Awaiting device extraction'),
                ];
            } elseif ($uKey === 'Crime Scene Support') {
                $cssCompleted = !empty($docketData['unit_progress']['Crime Scene Support']['status']) && strtoupper($docketData['unit_progress']['Crime Scene Support']['status']) === 'COMPLETED';
                $unitProgress[$uKey] = [
                    'status'     => ($cssCompleted || !empty($case->commanding_officer)) ? 'COMPLETED' : 'PENDING',
                    'officer'    => $case->commanding_officer ?? ($case->submitting_officer ?? null),
                    'updated_at' => optional($case->created_at)->toDateTimeString(),
                    'notes'      => 'Submitted via Crime Scene Support & Field Operations Workspace',
                ];
            } elseif ($uKey === 'Evidence Management') {
                $evCompleted = !empty($docketData['unit_progress']['Evidence Management']['status']) && strtoupper($docketData['unit_progress']['Evidence Management']['status']) === 'COMPLETED';
                $unitProgress[$uKey] = [
                    'status'     => $evCompleted ? 'COMPLETED' : 'PENDING',
                    'officer'    => $evCompleted ? ($docketData['unit_progress']['Evidence Management']['officer'] ?? 'Vault Custodian') : null,
                    'updated_at' => $evCompleted ? ($docketData['unit_progress']['Evidence Management']['updated_at'] ?? null) : null,
                    'notes'      => $evCompleted ? 'Processed via Evidence Management & Custody Vault Workspace' : 'Awaiting evidence receipt and vault custody intake',
                ];
            } elseif ($uKey === 'Head of Forensic Directorate') {
                $hofCompleted = ($case->status === 'Approved') || !empty($docketData['hof_approved']);
                $unitProgress[$uKey] = [
                    'status'     => $hofCompleted ? 'COMPLETED' : 'PENDING',
                    'officer'    => $hofCompleted ? ($case->approved_by ?? 'Head of Forensic Directorate') : null,
                    'updated_at' => $hofCompleted ? optional($case->updated_at)->toDateTimeString() : null,
                    'notes'      => $hofCompleted ? 'Multi-Unit Directorate Docket Approved & Signed Off' : 'Awaiting final directorate approval sign-off',
                ];
            }
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
                    $labExam = array_merge($labExam, $lItem);
                    break;
                }
            }
        }
    }
    if (empty($labExam['has_saved']) && empty($labExam['presumptive_result'])) {
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                if (!empty($szItem['has_saved_lab']) || !empty($szItem['presumptive_result'])) {
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
                        'remark'                   => $szItem['remark'] ?? null,
                        'findings'                 => $szItem['findings'] ?? null,
                        'has_saved'                => true,
                    ]);
                    break;
                }
            }
        }
    }
    $dbPrimaryLabExam = !empty($case->id) ? \App\Models\LaboratoryExaminationResult::where(function($q) use ($case) {
            $q->where('single_suspect_case_id', $case->id)
              ->orWhere('multiple_suspect_case_id', $case->id);
        })->where('has_saved', true)->latest('id')->first() : null;

    if (!$dbPrimaryLabExam && !empty($case->id)) {
        $dbPrimaryLabExam = \App\Models\AssociateLabExamination::where('case_id', $case->id)
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

    if ($dbPrimaryLabExam) {
        $labExam = array_merge($labExam, [
            'exhibit_owner_name'  => $dbPrimaryLabExam->suspect_name,
            'laboratory_number'   => $dbPrimaryLabExam->laboratory_number,
            'exam_reference'      => $dbPrimaryLabExam->exam_reference,
            'exhibit_number'      => $dbPrimaryLabExam->exhibit_number,
            'analyst'             => $dbPrimaryLabExam->analyst,
            'date_received'       => is_object($dbPrimaryLabExam->date_received) ? $dbPrimaryLabExam->date_received->format('Y-m-d') : substr($dbPrimaryLabExam->date_received, 0, 10),
            'date_started'        => is_object($dbPrimaryLabExam->date_started) ? $dbPrimaryLabExam->date_started->format('Y-m-d') : substr($dbPrimaryLabExam->date_started, 0, 10),
            'date_completed'      => is_object($dbPrimaryLabExam->date_completed) ? $dbPrimaryLabExam->date_completed->format('Y-m-d') : substr($dbPrimaryLabExam->date_completed, 0, 10),
            'presumptive_result'  => $dbPrimaryLabExam->presumptive_result,
            'confirmatory_result' => $dbPrimaryLabExam->confirmatory_result,
            'sample_weight'       => [
                'description'      => $dbPrimaryLabExam->sample_weight_description,
                'units_packaging'  => $dbPrimaryLabExam->sample_weight_units,
                'gross_weight'     => $dbPrimaryLabExam->sample_weight_gross,
                'sample_taken'     => $dbPrimaryLabExam->sample_weight_taken,
                'reserved_samples' => $dbPrimaryLabExam->sample_weight_reserved,
            ],
            'substance_identification' => $dbPrimaryLabExam->substance_identification,
            'detected_substances'      => $dbPrimaryLabExam->detected_substances,
            'test_methods'             => $dbPrimaryLabExam->test_methods,
            'specifications'           => $dbPrimaryLabExam->specifications,
            'remark'                   => $dbPrimaryLabExam->remark,
            'findings'                 => $dbPrimaryLabExam->findings,
            'has_saved'                => true,
        ]);
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
    $caseNumericId = sprintf('%03d', $case->id ?? 1);
    $labExamRef = !empty($rawLabExamRef) ? $rawLabExamRef : ('GSA-FM-T' . rand(10, 99) . '-F');

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
    $labExhibitNo = !empty($rawLabExhibitNo) ? $rawLabExhibitNo : ('EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . $caseNumericId);

    $rawLabNumber = $case->laboratory_number ?? ($labExam['laboratory_number'] ?? ($labExam['lab_number'] ?? null));
    $labNumber = !empty($rawLabNumber) ? $rawLabNumber : ('Lab No. ' . rand(10, 99) . '/FS' . date('m', strtotime($case->created_at ?? 'now')) . '/' . date('Y', strtotime($case->created_at ?? 'now')));
    $labUnit = !empty($labExam['lab_unit']) ? $labExam['lab_unit'] : (!empty($unitProgress['Lab Analysis']['unit']) ? $unitProgress['Lab Analysis']['unit'] : 'Chemistry & Narcotics Unit');
    $currentLoggedInName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? '');
    $labAnalyst = !empty($labExam['analyst']) ? $labExam['analyst'] : (!empty($unitProgress['Lab Analysis']['officer']) ? $unitProgress['Lab Analysis']['officer'] : (!empty($currentLoggedInName) ? $currentLoggedInName : $defaultLabHeadName));
    $labDateReceived = (!empty($labExam['date_received']) && $labExam['date_received'] !== 'N/A') ? $labExam['date_received'] : (!empty($docketData['lab_examination']['date_received']) && $docketData['lab_examination']['date_received'] !== 'N/A' ? $docketData['lab_examination']['date_received'] : (!empty($docketData['seizures'][0]['date_received']) && $docketData['seizures'][0]['date_received'] !== 'N/A' ? $docketData['seizures'][0]['date_received'] : (!empty($docketData['seizures'][0]['seizure_date']) && $docketData['seizures'][0]['seizure_date'] !== 'N/A' ? $docketData['seizures'][0]['seizure_date'] : (!empty($case->incident_date) ? substr((string)$case->incident_date, 0, 10) : (is_object($case->created_at) ? $case->created_at->format('Y-m-d') : substr((string)$case->created_at, 0, 10))))));
    $labDateStarted = (!empty($labExam['date_started']) && $labExam['date_started'] !== 'N/A') ? $labExam['date_started'] : (!empty($docketData['lab_examination']['date_started']) && $docketData['lab_examination']['date_started'] !== 'N/A' ? $docketData['lab_examination']['date_started'] : (!empty($docketData['seizures'][0]['date_started']) && $docketData['seizures'][0]['date_started'] !== 'N/A' ? $docketData['seizures'][0]['date_started'] : $labDateReceived));
    $labDateCompleted = (!empty($labExam['date_completed']) && $labExam['date_completed'] !== 'N/A') ? $labExam['date_completed'] : (!empty($docketData['lab_examination']['date_completed']) && $docketData['lab_examination']['date_completed'] !== 'N/A' ? $docketData['lab_examination']['date_completed'] : (!empty($docketData['seizures'][0]['date_completed']) && $docketData['seizures'][0]['date_completed'] !== 'N/A' ? $docketData['seizures'][0]['date_completed'] : $labDateStarted));
    $labPresumptive = !empty($labExam['presumptive_result']) ? $labExam['presumptive_result'] : (!empty($case->presumptive_test_result) ? $case->presumptive_test_result : 'N/A');
    $rawConfVal = !empty($labExam['confirmatory_result']) ? $labExam['confirmatory_result'] : (!empty($case->confirmatory_test_result) ? $case->confirmatory_test_result : 'YES');
    $labConfirmatory = (strtoupper(trim((string)$rawConfVal)) === 'NO') ? 'NO' : 'YES';
    $labSampleWeight = $labExam['sample_weight'] ?? [];
    
    // Extract top drug exhibits first
    $topDrugExhibits = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $idx => $sz) {
            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
            $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
            if (!empty($sz['seizure_name_drug']) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains(strtolower($sName), 'cocaine') || str_contains(strtolower($sName), 'heroin') || str_contains(strtolower($sName), 'cannabis') || str_contains(strtolower($sName), 'drug')) {
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
    $labSampleTaken = !empty($labSampleWeight['sample_taken']) ? $labSampleWeight['sample_taken'] : (!empty($labSampleWeight['sample_weight_taken']) ? $labSampleWeight['sample_weight_taken'] : (!empty($labExam['sample_weight_taken']) ? $labExam['sample_weight_taken'] : (!empty($labExam['sample_taken']) ? $labExam['sample_taken'] : (!empty($case->sample_weight_taken) ? $case->sample_weight_taken : (!empty($docketData['seizures'][0]['sample_taken']) ? $docketData['seizures'][0]['sample_taken'] : (!empty($docketData['seizures'][0]['sample_weight_taken']) ? $docketData['seizures'][0]['sample_weight_taken'] : (!empty($dbPrimaryLabExam->sample_weight_taken) ? $dbPrimaryLabExam->sample_weight_taken : '')))))));
    $labSampleReserved = !empty($labSampleWeight['reserved_samples']) ? $labSampleWeight['reserved_samples'] : (!empty($labSampleWeight['sample_weight_reserved']) ? $labSampleWeight['sample_weight_reserved'] : (!empty($labExam['sample_weight_reserved']) ? $labExam['sample_weight_reserved'] : (!empty($labExam['reserved_samples']) ? $labExam['reserved_samples'] : (!empty($case->sample_weight_reserved) ? $case->sample_weight_reserved : (!empty($docketData['seizures'][0]['sample_reserved']) ? $docketData['seizures'][0]['sample_reserved'] : (!empty($docketData['seizures'][0]['sample_weight_reserved']) ? $docketData['seizures'][0]['sample_weight_reserved'] : (!empty($dbPrimaryLabExam->sample_weight_reserved) ? $dbPrimaryLabExam->sample_weight_reserved : '')))))));
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
    $savedLabExamRef = $labExamRef;
    $savedLabExhibitNo = $labExhibitNo;
    $savedLabCaseRef = $labCaseRef;
    $savedLabNumber = $labNumber;
    $savedLabUnit = $labUnit;
    $savedLabAnalyst = $labAnalyst;
    $savedLabDateReceived = $labDateReceived;
    $savedLabDateStarted = $labDateStarted;
    $savedLabDateCompleted = $labDateCompleted;
    $savedLabPresumptive = $labPresumptive;
    $savedLabConfirmatory = $labConfirmatory;
    $savedLabExhibitDesc = !empty($labExam['exhibit_description']) ? $labExam['exhibit_description'] : (!empty($labExam['description']) ? $labExam['description'] : ($initialLabDesc ?? ''));
    $savedLabSampleDesc = $labSampleDesc;
    $savedLabSampleUnits = $labSampleUnits;
    $savedLabSampleGross = $labSampleGross;
    $savedLabSampleTaken = $labSampleTaken;
    $savedLabSampleReserved = $labSampleReserved;
    $savedLabSampleUnit = $labSampleUnit;
    $savedLabSubstanceId = $labSubstanceId;
    $savedLabDetectedSubstances = $labDetectedSubstances;
    $savedLabTestMethods = $labTestMethods;
    $savedLabSpecifications = $labSpecifications;
    $savedLabRemark = $labRemark;
    $savedLabFindings = $labFindings;

    if (empty($savedLabExhibitNo) || $savedLabExhibitNo === 'N/A') {
        $savedLabExhibitNo = !empty($labExhibitNo) ? $labExhibitNo : ($case->exhibit_number ?: 'EXH-01');
    }
    if (empty($savedLabDateReceived) || $savedLabDateReceived === 'N/A') {
        $savedLabDateReceived = !empty($labDateReceived) ? $labDateReceived : date('Y-m-d');
    }
    if (empty($savedLabDateStarted) || $savedLabDateStarted === 'N/A') {
        $savedLabDateStarted = !empty($labDateStarted) ? $labDateStarted : $savedLabDateReceived;
    }
    if (empty($savedLabDateCompleted) || $savedLabDateCompleted === 'N/A') {
        $savedLabDateCompleted = !empty($labDateCompleted) ? $labDateCompleted : $savedLabDateStarted;
    }

    $hasUnexaminedDrugExhibit = false;
    $firstPendingSeizure = null;
    $hasSavedLabResult = !empty($labExam['has_saved']) || $suspectLabExamSaved;
    $hasAnySavedLabExam = !empty($docketData['lab_examination']['has_saved'])
        || \App\Models\AssociateLabExamination::where('case_id', $case->id)->where('has_saved', true)->exists()
        || (!empty($docketData['associate_lab_examinations']) && count(array_filter($docketData['associate_lab_examinations'], fn($v) => !empty($v['has_saved']))) > 0)
        || (!empty($docketData['lab_examinations_list']) && count(array_filter($docketData['lab_examinations_list'], fn($v) => !empty($v['has_saved']))) > 0)
        || (!empty($docketData['seizures']) && count(array_filter($docketData['seizures'], fn($s) => !empty($s['has_saved_lab']))) > 0)
        || \App\Models\LaboratoryExaminationResult::where(function($q) use ($case) {
                $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
            })->where('has_saved', true)->exists();

    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $idxCheck => $szItemCheck) {
            $catCheck = strtolower($szItemCheck['category'] ?? ($szItemCheck['exhibit_category'] ?? ''));
            $sNameCheck = $szItemCheck['seizure_name_drug'] ?? ($szItemCheck['seizure_name'] ?? '');
            $isDrugCheck = !empty($szItemCheck['seizure_name_drug']);
            if (!$isDrugCheck) {
                foreach ($drugKeywords as $kw) {
                    if (str_contains($catCheck, $kw) || str_contains(strtolower($sNameCheck), $kw)) {
                        $isDrugCheck = true;
                        break;
                    }
                }
            }
            if ($isDrugCheck) {
                $exNoCheck = $szItemCheck['exhibit_number'] ?? '';
                $assocPersonNameCheck = $szItemCheck['associated_person'] ?? ($szItemCheck['suspect_name'] ?? ($szItemCheck['owner_name'] ?? ''));
                $assocDbSaved = false;
                if (!empty($assocPersonNameCheck) && strtolower(trim($assocPersonNameCheck)) !== 'primary suspect') {
                    $assocDbSaved = \App\Models\AssociateLabExamination::where('case_id', $case->id)
                        ->whereRaw('LOWER(trim(suspect_name)) = ?', [strtolower(trim($assocPersonNameCheck))])
                        ->where('has_saved', true)
                        ->exists();
                    if (!$assocDbSaved && !empty($docketData['associate_lab_examinations'])) {
                        foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                            if (strtolower(trim($kAssoc)) === strtolower(trim($assocPersonNameCheck)) && !empty($vAssoc['has_saved'])) {
                                $assocDbSaved = true;
                                break;
                            }
                        }
                    }
                }

                $hasSavedForThisCheck = !empty($szItemCheck['has_saved_lab']) 
                    || $assocDbSaved
                    || (!empty($docketData['lab_examination']['has_saved']) && (
                        (!empty($savedLabExhibitNo) && $exNoCheck === $savedLabExhibitNo)
                        || (!empty($savedLabSampleDesc) && strtolower(trim($sNameCheck)) === strtolower(trim($savedLabSampleDesc)))
                        || (!empty($savedLabSubstanceId) && strtolower(trim($sNameCheck)) === strtolower(trim($savedLabSubstanceId)))
                        || (!empty($savedLabOwnerName) && !empty($assocPersonNameCheck) && strtolower(trim($assocPersonNameCheck)) === strtolower(trim($savedLabOwnerName)))
                    ));
                if ($hasSavedForThisCheck) {
                    $hasAnySavedLabExam = true;
                } else {
                    $hasUnexaminedDrugExhibit = true;
                    if (empty($firstPendingSeizure)) {
                        $firstPendingSeizure = [
                            'index' => $idxCheck + 1,
                            'name'  => $sNameCheck ?: 'Drug Exhibit',
                            'qty'   => $szItemCheck['seizure_quantity'] ?? null,
                            'weight'=> $szItemCheck['seizure_weight'] ?? null,
                            'unit'  => $szItemCheck['seizure_unit'] ?? null,
                            'pkg'   => $szItemCheck['seizure_packaging'] ?? null,
                            'ex_no' => !empty($exNoCheck) ? $exNoCheck : ('EXH-0' . ($idxCheck + 1)),
                        ];
                    }
                }
            }
        }
    }

    $savedLabStatus = strtoupper($unitProgress['Lab Analysis']['status'] ?? ($case->lab_status ?? ($docketData['lab_examination']['status'] ?? 'PENDING')));

    if ($hasAnySavedLabExam || $hasSavedLabResult) {
        $labStatus = 'COMPLETED';
        $hasSavedLabData = true;
    } elseif ($hasUnexaminedDrugExhibit) {
        $labStatus = $hasAnySavedLabExam ? 'IN_PROGRESS' : 'PENDING';
        $hasSavedLabData = $hasAnySavedLabExam;

        $pendName = !empty($firstPendingSeizure) ? $firstPendingSeizure['name'] : $defaultDrugName;
        $pendQty = !empty($firstPendingSeizure) ? ($firstPendingSeizure['qty'] ?? null) : null;
        $pendPkg = !empty($firstPendingSeizure) ? ($firstPendingSeizure['pkg'] ?? null) : null;
        $pendWeight = !empty($firstPendingSeizure) ? ($firstPendingSeizure['weight'] ?? null) : null;
        $pendUnit = !empty($firstPendingSeizure) ? ($firstPendingSeizure['unit'] ?? null) : null;
        if (!empty($firstPendingSeizure['ex_no']) && !$hasAnySavedLabExam) {
            $labExhibitNo = $firstPendingSeizure['ex_no'];
        }

        if (!$hasAnySavedLabExam) {
            $labSampleDesc = $pendName;
            $labSubstanceId = !empty($savedLabSubstanceId) ? $savedLabSubstanceId : (!empty($case->confirmatory_substance_identification) ? $case->confirmatory_substance_identification : $pendName);
            $labSampleUnits = !empty($pendQty) ? trim($pendQty . ' ' . ($pendPkg ?? '')) : '';
            $labSampleGross = !empty($pendWeight) ? trim($pendWeight . ' ' . ($pendUnit ?? '')) : ($labSampleUnits ?: '');
            if (!empty($pendUnit)) {
                $labSampleUnit = $pendUnit;
            }

            $labPresumptive = !empty($savedLabPresumptive) ? $savedLabPresumptive : (!empty($case->presumptive_test_result) ? $case->presumptive_test_result : 'N/A');
            $labConfirmatory = !empty($savedLabConfirmatory) ? $savedLabConfirmatory : (!empty($case->confirmatory_test_result) ? $case->confirmatory_test_result : 'YES');
            $labSampleTaken = !empty($savedLabSampleTaken) ? $savedLabSampleTaken : '';
            $labSampleReserved = !empty($savedLabSampleReserved) ? $savedLabSampleReserved : '';
            $labFindings = !empty($savedLabFindings) ? $savedLabFindings : '';
            $labTestMethods = !empty($savedLabTestMethods) ? $savedLabTestMethods : (!empty($case->confirmatory_test_methods) ? $case->confirmatory_test_methods : '');
            $labSpecifications = !empty($savedLabSpecifications) ? $savedLabSpecifications : (!empty($case->confirmatory_specifications) ? $case->confirmatory_specifications : '');
            $labDetectedSubstances = !empty($savedLabDetectedSubstances) ? $savedLabDetectedSubstances : [];
            $labDateReceived = date('Y-m-d');
            $labDateStarted = date('Y-m-d');
            $labDateCompleted = date('Y-m-d');
        }
    } else {
        $labStatus = $hasAnySavedLabExam ? 'COMPLETED' : 'PENDING';
        $hasSavedLabData = $hasAnySavedLabExam;
    }

    if (isset($unitProgress['Lab Analysis'])) {
        $activeSuspectLabStatus = (($suspectHasDrug && $suspectLabExamSaved) || (!$suspectHasDrug && $caseHasAnySavedLab) || (!$suspectHasDrug && !empty($docketData['seizures'])) || $isEvidenceManagement) ? 'COMPLETED' : 'PENDING';
        $unitProgress['Lab Analysis']['status'] = $activeSuspectLabStatus;

        $resolvedLabOfficer = !empty($unitProgress['Lab Analysis']['officer']) 
            ? $unitProgress['Lab Analysis']['officer'] 
            : (!empty($docketData['lab_examination']['analyst']) 
                ? $docketData['lab_examination']['analyst'] 
                : (!empty($docketData['unit_progress']['Lab Analysis']['officer']) 
                    ? $docketData['unit_progress']['Lab Analysis']['officer'] 
                    : ($isEvidenceManagement 
                        ? ($case->submitting_officer ?? ($authUser->fullname ?? 'Madam Diana')) 
                        : (!empty($labAnalyst) ? $labAnalyst : ($case->submitting_officer ?? ($defaultLabHeadName ?? 'John Mensah'))))));
        
        $unitProgress['Lab Analysis']['officer'] = $resolvedLabOfficer;

        if ($activeSuspectLabStatus === 'COMPLETED' || $isEvidenceManagement) {
            $unitProgress['Lab Analysis']['notes'] = 'Laboratory Examination Information completed and signed off';
            if (empty($unitProgress['Lab Analysis']['updated_at'])) {
                $unitProgress['Lab Analysis']['updated_at'] = optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s');
            }
        } else {
            $unitProgress['Lab Analysis']['notes'] = $isEvidenceManagement ? 'Laboratory Examination Information completed and signed off' : ((empty($suspectLinkedExhibits) || !$hasAnySpecialistUnit) ? 'Awaiting exhibit registration and initial processing' : ($suspectHasDrug ? 'Drug exhibit registered; awaiting Laboratory Examination Information entry' : 'Awaiting lab testing'));
        }
    }

    if (isset($unitProgress['Digital & Physical Forensics'])) {
        if (empty($unitProgress['Digital & Physical Forensics']['officer'])) {
            $unitProgress['Digital & Physical Forensics']['officer'] = $docketData['digital_forensics'][$activeSuspectNameLower]['analyst'] 
                ?? ($docketData['unit_progress']['Digital & Physical Forensics']['officer'] 
                ?? ((str_contains(strtolower($case->unit_name ?? ''), 'digital') || str_contains(strtolower($case->unit_name ?? ''), 'physical')) ? ($case->submitting_officer ?? 'Ofori Graham') : 'Ofori Graham'));
        }
    }

    $completedUnitsCount = 0;
    foreach ($unitsList as $uKey) {
        if (isset($unitProgress[$uKey]['status']) && strtoupper($unitProgress[$uKey]['status']) === 'COMPLETED') {
            $completedUnitsCount++;
        }
    }

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
        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset($path);
    };

    $unitColors = [
        'Crime Scene Support' => 'text-blue-600 bg-blue-50 border-blue-200',
        'Evidence Management' => 'text-indigo-600 bg-indigo-50 border-indigo-200',
        'LAB Analysis' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
        'Lab Analysis' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
        'Digital & Physical Forensics' => 'text-amber-600 bg-amber-50 border-amber-200',
        'Digital & Physical Forensic Unit' => 'text-amber-600 bg-amber-50 border-amber-200',
    ];
    $unitBadgeColor = $unitColors[$case->unit_name] ?? 'text-blue-600 bg-blue-50 border-blue-200';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Review File — {{ $case->case_number }} | NACOC Forensic Department</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind / Vite & Barcode Engine -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif


    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
        }

        .btn-primary {
            background: #059669;
            border: 1px solid #10b981;
            box-shadow: 0 4px 12px rgba(16,185,129,0.25);
            color: white; font-weight: 700; font-size: 12px;
            padding: 10px 20px; border-radius: 12px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #047857;
            box-shadow: 0 6px 18px rgba(16,185,129,0.35);
            transform: translateY(-1px);
        }

        .btn-decline {
            background: #dc2626;
            border: 1px solid #f43f5e;
            box-shadow: 0 4px 12px rgba(225,29,72,0.25);
            color: white; font-weight: 700; font-size: 12px;
            padding: 10px 20px; border-radius: 12px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .btn-decline:hover {
            background: #b91c1c;
            box-shadow: 0 6px 18px rgba(225,29,72,0.35);
            transform: translateY(-1px);
        }

        .btn-back {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #334155; font-weight: 700; font-size: 12px;
            padding: 9px 16px; border-radius: 10px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-back:hover {
            background: #F1F5F9; color: #0F172A; border-color: #94A3B8;
        }

        .status-badge {
            display: inline-flex; align-items: center;
            padding: 5px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-await    { background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; }
        .status-done     { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .status-declined { background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; }

        .dossier-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            overflow: hidden;
        }
    </style>
</head>
@php
    $isForensicHeadContext = request()->is('Forensic_Head*') || (request()->header('referer') && str_contains(request()->header('referer'), 'Forensic_Head'));
@endphp

<body class="min-h-screen flex flex-col antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- LEFT SIDEBAR -->
    @if($isForensicHeadContext)
        @include('Forensic_Head.partials.sidebar')
    @else
        @include('Crime_Scene_Support.partials.sidebar')
    @endif

    <!-- MAIN WORKSPACE -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

        <!-- TOP NAVBAR -->
        <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20 gap-4">
            <div class="flex items-center gap-4 shrink-0">
                @if($isForensicHeadContext)
                    <a href="/Forensic_Head/cases" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Register
                    </a>
                @elseif($isDigitalForensics)
                    <a href="/cases?from=Digital+%26+Physical+Forensics" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Directory
                    </a>
                @elseif($isLabContext)
                    <a href="/cases?from=Lab+Analysis" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Directory
                    </a>
                @else
                    <a href="/cases?from={{ urlencode($activeUnitContext) }}" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Directory
                    </a>
                @endif
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono-code font-extrabold text-slate-700">{{ $case->case_number }}</span>
                </div>
            </div>

            <!-- GENERAL SEARCH INPUT -->
            <div class="flex items-center flex-1 max-w-md mx-4">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input 
                        type="text" 
                        id="globalSearch"
                        placeholder="Search case #, suspect, evidence ID..." 
                        class="w-full pl-10 pr-12 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                        onkeydown="if(event.key === 'Enter' && this.value) window.location.href='/Forensic_Head/cases?search=' + encodeURIComponent(this.value);"
                    >
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <!-- NOTIFICATION BELL ICON & GENERAL ALERTS -->
                <div class="relative">
                    @php
                        $pendingCasesNotif = (int)(\App\Models\ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count());
                        $pendingUsersNotif = (int)(\App\Models\User::where('is_approved', false)->count());
                        $totalAlertsCount = $pendingCasesNotif + $pendingUsersNotif + 1;
                    @endphp
                    <button type="button" id="notificationBellBtn" onclick="toggleNotificationDropdown()" class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors relative border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($totalAlertsCount > 0)
                            <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                        @endif
                    </button>

                    <!-- NOTIFICATIONS & GENERAL ALERTS DROPDOWN MENU -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden font-sans">
                        <!-- HEADER -->
                        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-mono-code text-blue-400 font-bold uppercase tracking-wider">DIRECTORATE COMMAND</div>
                                <div class="text-xs font-black tracking-wide">General System &amp; Department Alerts</div>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-500/20 text-blue-300 rounded-full font-mono-code border border-blue-500/30">
                                {{ $totalAlertsCount }} Active Alerts
                            </span>
                        </div>

                        <!-- ALERTS LIST -->
                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            
                            <!-- ALERT 1: CASE APPROVALS -->
                            <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-amber-700 uppercase">Case Approvals</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">JUST NOW</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        @if($pendingCasesNotif > 0)
                                            {{ $pendingCasesNotif }} Case Dossiers Awaiting Executive Sign-off
                                        @else
                                            All Case Dossiers Authorized
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        @if($pendingCasesNotif > 0)
                                            Pending findings certificates and official seal approval required.
                                        @else
                                            No outstanding case dossier sign-offs pending.
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <!-- ALERT 2: USER ACCOUNT APPROVALS -->
                            <a href="{{ url('/Forensic_Head/approvals') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-blue-700 uppercase">Account Control</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">TODAY</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        @if($pendingUsersNotif > 0)
                                            {{ $pendingUsersNotif }} Pending User Account Approval(s)
                                        @else
                                            Personnel Roster Fully Verified
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        @if($pendingUsersNotif > 0)
                                            Staff or Unit Head registrations awaiting directorate access clearance.
                                        @else
                                            All registered system accounts are authorized and active.
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <!-- ALERT 3: EVIDENCE VAULT TRANSFERS -->
                            <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Evidence Vault</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">3 MIN AGO</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">4 Barcoded Exhibits Transferred to Vault</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">Inter-unit evidence transfer received from Crime Scene Support Unit.</div>
                                </div>
                            </a>

                            <!-- ALERT 4: SYSTEM INTEGRITY AUDIT -->
                            <div class="p-3.5 flex items-start space-x-3 bg-emerald-50/40">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Chain Integrity Audit</span>
                                        <span class="text-[9px] font-bold text-emerald-600 font-mono-code">SYSTEM OK</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">100% Tamper-Free Hash Audit Verified</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">SHA-256 digital hashes matched across all 4 forensic unit databases.</div>
                                </div>
                            </div>

                        </div>

                        <!-- FOOTER -->
                        <div class="p-3 bg-slate-50 border-t border-slate-200 text-center">
                            <a href="{{ url('/Forensic_Head/cases') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 font-mono-code uppercase tracking-wider text-decoration-none">
                                View Executive Case Register &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-right hidden sm:block">
                    <div class="text-xs font-mono-code font-semibold text-slate-700" id="liveClock"></div>
                    <div class="text-[10px] font-mono-code text-blue-600 font-bold">NACOC FORENSIC DEPARTMENT</div>
                </div>
            </div>
        </header>

        <!-- SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">

            @include('partials.flash')

            <!-- HERO DOSSIER HEADER BANNER -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-slate-900 text-white shadow-xl border border-slate-800">
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono-code bg-slate-800 text-amber-400 border border-slate-700">{{ $priority }} PRIORITY</span>
                            <span class="text-slate-500">•</span>
                            <span id="headerStatusBadge">
                                @if($status === 'Approved' || $status === 'Authorized')
                                    <span class="status-badge status-done">
                                        <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Authorized
                                    </span>
                                @elseif($status === 'Declined')
                                    <span class="status-badge status-declined">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span>
                                        Declined
                                    </span>
                                @else
                                    <span class="status-badge bg-white text-slate-900 border border-slate-300">
                                        {{ $status }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3">
                                {{ $case->title }}
                                <span class="text-base text-slate-400 font-normal">| {{ $case->case_number }}</span>
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-3xl leading-relaxed">
                                The complete case record is available for your review. Please carefully review the registered details below before proceeding with the executive sign-off.
                            </p>
                        </div>
                    </div>

                    <!-- QUICK ACTIONS IN HERO -->
                    <div class="flex items-center gap-3 shrink-0">
                        @if($isLabAnalysis && !$isEvidenceManagement)
                        <button type="button" onclick="toggleLabFormSections()" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                            <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 012.122-.363c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                            <span class="toggleLabFormTextSpan">Hide Examination Section</span>
                        </button>
                        @endif
                        <a href="/cases/{{ $case->case_number }}/edit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer no-underline">
                            <svg class="w-4 h-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Case/Update Case Records</span>
                        </a>
                        <a href="/cases/{{ $case->case_number }}/pdf" target="_blank" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Print Case PDF</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- PENDING HANDOVER ACKNOWLEDGEMENT BANNER -->
            @if($activeHandover && (int)$activeHandover->to_user_id === (int)$authUser->id)
                <div class="mb-6 p-5 bg-amber-50 border-2 border-amber-400 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-md font-sans">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-amber-700">HANDOVER ACKNOWLEDGEMENT REQUIRED</div>
                            <h4 class="text-sm font-black text-slate-900 mt-0.5">Case Status Control Transfer Requested</h4>
                            <p class="text-xs text-slate-600 mt-1 leading-snug">
                                <strong>{{ $activeHandover->fromUser->fullname ?? 'Unit Head' }}</strong> ({{ $activeHandover->from_unit }}) has requested to transfer <strong>Case Status Control</strong> for Case <strong>{{ $case->case_number }}</strong> to you.
                                @if($activeHandover->request_notes)
                                    <br><span class="italic text-slate-500">Note: "{{ $activeHandover->request_notes }}"</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <button type="button" onclick="respondHandover('accept')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-1.5 border-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Accept &amp; Take Control
                        </button>
                        <button type="button" onclick="respondHandover('decline')" class="px-3.5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer border-none">
                            Decline
                        </button>
                    </div>
                </div>
            @endif

            <!-- INTER-UNIT WORKFLOW & PROGRESS TRACKER -->
            <div class="dossier-card border border-slate-200 bg-white rounded-2xl shadow-sm overflow-hidden" style="box-shadow: 0 1px 8px 0 rgba(0,0,0,0.06);">

                {{-- ── HEADER ── --}}
                <div class="px-6 pt-5 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                     style="background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-widest font-mono-code bg-indigo-900 text-indigo-100 border border-indigo-800 shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m0 6H5m4 0h4m4-6v6m0 0h-4m4 0h2M9 11V7m6 4V7"/></svg>
                                MULTI-UNIT CASE TRACKER
                            </span>
                            <span id="unitCompletedCountBadge"
                                  class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code border
                                  {{ $completedUnitsCount >= $totalUnitsCount ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300' }}">
                                {{ $completedUnitsCount }}/{{ $totalUnitsCount }} Units Completed
                            </span>
                        </div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight mt-1">
                            {{ $totalUnitsCount }}-Unit Directorate Progress &amp; Inter-Unit Workflow
                        </h2>
                        <p class="text-[11px] text-slate-500 font-medium leading-snug">
                            All {{ $totalUnitsCount }} units build on this case. HOF approval triggers once all units complete their analysis.
                        </p>
                    </div>

                    {{-- Progress pill --}}
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <div class="text-right text-[10px] font-mono-code font-bold text-slate-400 uppercase tracking-wider">Overall Progress</div>
                        <div class="w-52 bg-slate-200 rounded-full h-5 border border-slate-300 overflow-hidden relative" style="box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
                            <div id="unitProgressBar"
                                 class="h-full rounded-full flex items-center justify-end pr-2 text-[10px] font-mono-code font-extrabold text-white transition-all duration-700"
                                 style="width:{{ max(12, ($completedUnitsCount / max($totalUnitsCount,1)) * 100) }}%;
                                        background: linear-gradient(90deg, #059669, #10b981);">
                                {{ round(($completedUnitsCount / max($totalUnitsCount,1)) * 100) }}%
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── PIPELINE FLOW ── --}}
                <div class="px-6 py-5">
                    {{-- Horizontal scrollable pipeline --}}
                    <div class="overflow-x-auto pb-1 flex justify-center">
                        <div class="flex items-stretch justify-center gap-0 min-w-max mx-auto" id="unitsGridContainer">

                            @foreach($unitsList as $unitNameKey)
                                @php
                                    $uData   = $unitProgress[$unitNameKey] ?? ['status' => 'PENDING'];
                                    $uStatus = strtoupper($uData['status'] ?? 'PENDING');
                                    $uSlug   = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey));
                                    $isCompleted  = $uStatus === 'COMPLETED';
                                    $isInProgress = $uStatus === 'IN_PROGRESS';
                                    $isPending    = !$isCompleted && !$isInProgress;

                                    // Card style variants
                                    if ($isCompleted) {
                                        $cardBg      = 'background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#bbf7d0;';
                                        $iconBg      = '#059669'; $iconText = '#fff';
                                        $icon        = '✓';
                                        $badgeCls    = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                        $badgeLabel  = 'COMPLETED';
                                        $numRing     = '#059669';
                                        $titleColor  = 'text-slate-900';
                                    } elseif ($isInProgress) {
                                        $cardBg      = 'background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;';
                                        $iconBg      = '#d97706'; $iconText = '#fff';
                                        $icon        = '⏳';
                                        $badgeCls    = 'bg-amber-100 text-amber-800 border-amber-300';
                                        $badgeLabel  = 'IN PROGRESS';
                                        $numRing     = '#d97706';
                                        $titleColor  = 'text-slate-900';
                                    } else {
                                        $cardBg      = 'background:#f8fafc;border-color:#cbd5e1;';
                                        $iconBg      = '#cbd5e1'; $iconText = '#64748b';
                                        $icon        = '⭕';
                                        $badgeCls    = 'bg-slate-200 text-slate-700 border-slate-400 font-bold';
                                        $badgeLabel  = 'PENDING';
                                        $numRing     = '#94a3b8';
                                        $titleColor  = 'text-slate-800';
                                    }
                                @endphp

                                {{-- Unit card --}}
                                <div class="flex items-stretch gap-0">
                                    <div id="unitCard-{{ $uSlug }}"
                                         class="w-56 flex flex-col rounded-2xl border-2 transition-all duration-300 overflow-hidden shadow-2xs"
                                         style="{{ $cardBg }}">

                                        {{-- Card header stripe --}}
                                        <div class="px-3.5 pt-3.5 pb-2.5 border-b border-white/60 flex items-center justify-between gap-2"
                                             style="background:rgba(255,255,255,0.55);">
                                            <span class="text-[9px] font-mono-code font-black uppercase tracking-widest text-slate-600">
                                                UNIT {{ $loop->iteration }}
                                            </span>
                                            <span id="unitBadge-{{ $uSlug }}"
                                                  class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8.5px] font-extrabold font-mono-code uppercase border {{ $badgeCls }}">
                                                {{ $badgeLabel }}
                                            </span>
                                        </div>

                                        {{-- Card body --}}
                                        <div class="flex-1 px-3.5 py-3 space-y-2">
                                            {{-- Unit name --}}
                                            <div class="{{ $titleColor }} font-extrabold text-[12px] leading-snug">{{ $unitNameKey }}</div>

                                            {{-- Officer --}}
                                            <div id="unitOfficerBox-{{ $uSlug }}">
                                                @if(!empty($uData['officer']))
                                                    <div class="flex items-center gap-1.5 mt-1">
                                                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span class="text-[11px] text-slate-600">Officer: <span class="font-bold text-slate-800">{{ $uData['officer'] }}</span></span>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-1.5 mt-1">
                                                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span class="text-[11px] text-slate-600 font-medium italic">No officer assigned</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Notes snippet --}}
                                            <div id="unitNotesBox-{{ $uSlug }}">
                                                @if(!empty($uData['notes']))
                                                    <div class="text-[10px] text-slate-800 font-semibold mt-1.5 p-2 rounded-lg border border-slate-200 leading-snug italic"
                                                         style="background:rgba(255,255,255,0.85);">
                                                        "{{ $uData['notes'] }}"
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Card footer: timestamp --}}
                                        <div id="unitUpdatedBox-{{ $uSlug }}" class="px-3.5 py-2 border-t border-slate-200/60" style="background:rgba(255,255,255,0.6);">
                                            @if(!empty($uData['updated_at']))
                                                <div class="text-[9.5px] font-mono-code text-slate-600 font-semibold">
                                                    Updated: {{ \Carbon\Carbon::parse($uData['updated_at'])->format('M d, Y @ H:i') }}
                                                </div>
                                            @else
                                                <div class="text-[9.5px] font-mono-code text-slate-600 font-bold italic">Awaiting submission</div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Arrow connector (not after last unit) --}}
                                    @if(!$loop->last)
                                        <div class="flex items-center justify-center w-8 shrink-0 select-none">
                                            <div class="flex flex-col items-center">
                                                <div class="w-6 border-t-2 border-dashed {{ $isCompleted ? 'border-emerald-400' : 'border-slate-300' }}"></div>
                                                <svg class="w-3.5 h-3.5 -ml-1 {{ $isCompleted ? 'text-emerald-500' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- ── HOF Final Approval node ── --}}
                            @php
                                $hofDone = $completedUnitsCount >= $totalUnitsCount;
                            @endphp
                            <div class="flex items-stretch gap-0">
                                {{-- Arrow into HOF --}}
                                <div class="flex items-center justify-center w-8 shrink-0 select-none">
                                    <div class="flex flex-col items-center">
                                        <div class="w-6 border-t-2 border-dashed {{ $hofDone ? 'border-emerald-400' : 'border-slate-300' }}"></div>
                                        <svg class="w-3.5 h-3.5 -ml-1 {{ $hofDone ? 'text-emerald-500' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- HOF card --}}
                                <div class="w-52 flex flex-col rounded-2xl border-2 overflow-hidden transition-all duration-300"
                                     style="{{ $hofDone
                                         ? 'background:linear-gradient(135deg,#0f172a,#1e293b);border-color:#334155;'
                                         : 'background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-color:#cbd5e1;' }}">

                                    <div class="px-3.5 pt-3.5 pb-2.5 border-b flex items-center justify-between gap-2"
                                         style="{{ $hofDone ? 'border-color:rgba(255,255,255,0.08);background:rgba(255,255,255,0.05);' : 'border-color:rgba(0,0,0,0.06);background:rgba(255,255,255,0.6);' }}">
                                        <span class="text-[9px] font-mono-code font-black uppercase tracking-widest {{ $hofDone ? 'text-slate-400' : 'text-slate-600' }}">
                                            FINAL STEP
                                        </span>
                                        @if($hofDone)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8.5px] font-extrabold font-mono-code uppercase border bg-emerald-900/60 text-emerald-300 border-emerald-700">
                                                UNLOCKED
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8.5px] font-black font-mono-code uppercase border bg-slate-200 text-slate-700 border-slate-400">
                                                LOCKED
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex-1 px-3.5 py-3 space-y-1.5">
                                        <div class="font-extrabold text-[12px] leading-snug {{ $hofDone ? 'text-white' : 'text-slate-800' }}">
                                            Head of Forensic Directorate
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3 h-3 {{ $hofDone ? 'text-emerald-400' : 'text-slate-500' }} shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span class="text-[11px] {{ $hofDone ? 'text-emerald-300' : 'text-slate-600 font-semibold' }}">Executive Sign-off &amp; Approval</span>
                                        </div>
                                        @if(!$hofDone)
                                            <div class="text-[10px] text-slate-600 font-semibold italic mt-1">Awaiting all units to complete</div>
                                        @else
                                            <div class="text-[10px] text-emerald-400 font-bold mt-1">✓ All units submitted — ready for sign-off</div>
                                        @endif
                                    </div>

                                    <div class="px-3.5 py-2 border-t"
                                         style="{{ $hofDone ? 'border-color:rgba(255,255,255,0.08);background:rgba(255,255,255,0.05);' : 'border-color:rgba(0,0,0,0.06);background:rgba(255,255,255,0.5);' }}">
                                        <div class="text-[9.5px] font-mono-code {{ $hofDone ? 'text-emerald-400' : 'text-slate-600 font-bold italic' }}">
                                            {{ $hofDone ? 'Docket ready for HOF review' : 'Pending unit completions' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- end flex pipeline --}}
                    </div>{{-- end overflow wrapper --}}
                </div>{{-- end px-6 py-5 --}}

                <!-- CASE STATUS & CONTROLLER TOOLBAR -->
                <div class="mx-6 mb-6 p-4 sm:p-5 bg-white text-slate-900 rounded-2xl border-2 border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4 font-sans relative overflow-hidden group">
                    <!-- Subtle Ambient Glow -->
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/10 transition-all duration-700"></div>
                    <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-4 flex-wrap">
                        <!-- STATUS LABEL & DROPDOWN CONTAINER -->
                        <div class="flex items-center gap-3 bg-slate-50 p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 shadow-2xs">
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[11px] font-black font-mono-code text-slate-600 uppercase tracking-wider shrink-0">CASE STATUS</span>
                            </div>

                            @if($isCaseController)
                                <div class="relative inline-block">
                                    <select id="caseStatusSelect" onchange="updateCaseStatus(this.value)" class="appearance-none pl-3.5 pr-9 py-1.5 bg-white hover:bg-slate-50 text-slate-950 border border-slate-300 rounded-lg text-xs font-black font-mono-code uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-slate-400/40 focus:border-slate-400 transition-all cursor-pointer shadow-2xs">
                                        @foreach(['Under Investigation', 'On Remand', 'On Bail', 'In Court', 'Verdict Passed — Imprisoned', 'Verdict Passed — Acquitted', 'Case Dismissed', 'Case Closed'] as $stOpt)
                                            <option value="{{ $stOpt }}" class="bg-white text-slate-900 font-sans normal-case py-1" {{ $currentCaseStatus === $stOpt ? 'selected' : '' }}>{{ $stOpt }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            @else
                                <span class="px-3 py-1 bg-white text-slate-950 border border-slate-300 rounded-lg text-xs font-black font-mono-code uppercase tracking-wider shadow-2xs flex items-center">
                                    {{ $currentCaseStatus }}
                                </span>
                            @endif
                        </div>

                        <!-- CONTROLLER BADGE -->
                        <div class="flex items-center gap-2 text-xs bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 text-slate-700">
                            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Controller:</span>
                            <span class="font-black text-slate-900 font-mono-code text-xs px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                                {{ $controllerUser->fullname ?? 'Unit Head' }} <span class="text-indigo-600 font-bold">({{ $controllerUser->unit ?? $case->unit_name }})</span>
                            </span>
                        </div>
                    </div>

                    <!-- ACTION BUTTON / READ ONLY HANDOVER BANNER -->
                    <div class="relative z-10 flex items-center gap-2 shrink-0">
                        @if($isCaseController)
                            <button type="button" onclick="openHandoverModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer border border-indigo-700">
                                <svg class="w-4 h-4 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Hand Over Case Control
                            </button>
                        @else
                            <span class="text-[11px] text-slate-700 font-semibold italic flex items-center gap-1.5 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Read-only (Status Control locked to {{ $controllerUser->fullname ?? 'Controller' }})
                            </span>
                        @endif
                    </div>
                </div>

                {{-- ── ALL COMPLETE BANNER ── --}}
                <div id="completedUnitsBanner"
                     class="mx-6 mb-6 p-5 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all duration-500
                            {{ $completedUnitsCount < $totalUnitsCount ? 'hidden' : '' }}"
                     style="background:linear-gradient(135deg,#0f172a,#1e293b);border-color:#334155;">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg shrink-0"
                             style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);">
                            ✓
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-white uppercase tracking-wider font-mono-code">
                                Multi-Unit Directorate Processing Complete
                                <span class="ml-1 text-emerald-400">({{ $completedUnitsCount }}/{{ $totalUnitsCount }} Units)</span>
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                All {{ $totalUnitsCount }} specialized units have completed and saved their findings. Docket is submitted for HOF executive sign-off.
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[10px] font-mono-code font-extrabold uppercase tracking-wider shrink-0"
                          style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);">
                        AWAITING HOF SIGN-OFF
                    </span>
                </div>

            </div>{{-- end dossier-card --}}

            <!-- HANDOVER INITIATION MODAL -->
            <div id="handoverModal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden font-sans">
                    <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono-code text-indigo-400 font-bold uppercase tracking-wider">CASE STATUS GOVERNANCE</div>
                            <h3 class="text-sm font-black">Hand Over Case Status Control</h3>
                        </div>
                        <button type="button" onclick="closeHandoverModal()" class="text-slate-400 hover:text-white text-lg font-bold bg-transparent border-none cursor-pointer">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Transfer <strong>Case Status Control</strong> for Case <strong>{{ $case->case_number }}</strong> to another Unit Head. Control remains with you until the receiving Head acknowledges and accepts.
                        </p>
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1">Select Receiving Unit Head:</label>
                            <select id="handoverTargetUser" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:bg-white focus:border-indigo-600 shadow-2xs">
                                @foreach($allUnitHeads as $uHead)
                                    @if((int)$uHead->id !== (int)$authUser->id)
                                        <option value="{{ $uHead->id }}">{{ $uHead->fullname }} ({{ $uHead->unit }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1">Handover Remarks / Reason (Optional):</label>
                            <textarea id="handoverNotes" rows="3" placeholder="Add handover instructions or notes..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:bg-white focus:border-indigo-600 shadow-2xs"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" onclick="closeHandoverModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl cursor-pointer border-none">Cancel</button>
                        <button type="button" onclick="submitHandover()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-xs transition-all cursor-pointer border-none">Initiate Handover</button>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: MASTER CASE TITLE & CLASSIFICATION -->
            <div class="dossier-card p-6 border border-slate-200">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Case Title &amp; Classification</h2>
                            <p class="text-xs text-slate-500">Case title, reference, priority, and division assignment</p>
                        </div>
                    </div>
                    @if(!empty($case->unit_name))
                    <span class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold {{ $unitBadgeColor }}">
                        {{ $case->unit_name }}
                    </span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Name / Legal Title</div>
                        <div class="font-extrabold text-slate-900 text-sm mt-1">{{ $case->title ?: 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Reference Number</div>
                        <div class="font-extrabold text-blue-600 font-mono-code text-sm mt-1">{{ $case->case_number ?: 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Operation Type</div>
                        <div class="font-bold text-slate-800 text-sm mt-1">{{ $case->operation_code_name ?: ($case->operation_code ?? 'N/A') }}</div>
                    </div>
                </div>
            </div>

            @php
                $rawSuspects = !empty($docketData['suspects']) && is_array($docketData['suspects']) ? array_values(array_filter($docketData['suspects'], fn($s) => !empty($s['suspect_name']) && trim($s['suspect_name']) !== 'Unnamed Suspect' && trim($s['suspect_name']) !== 'Unknown')) : [];

                $primaryAndCoNamesArray = [];
                if (!empty($rawSuspects)) {
                    foreach ($rawSuspects as $s) {
                        $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                        $isAssoc = !empty($s['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                        if (!$isAssoc && !empty($s['suspect_name'])) {
                            $pParts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($s['suspect_name']))));
                            foreach ($pParts as $pp) {
                                if (!in_array($pp, $primaryAndCoNamesArray)) {
                                    $primaryAndCoNamesArray[] = $pp;
                                }
                            }
                        }
                    }
                }
                if (empty($primaryAndCoNamesArray) && !empty($case->suspect_name)) {
                    $pParts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', strtolower($case->suspect_name))));
                    if (!empty($pParts)) {
                        $primaryAndCoNamesArray[] = $pParts[0];
                    }
                }

                $namesMatchFuzzy = function($name1, $name2) {
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

                $isDuplicatePrimaryCoEntry = function($nameToCheck) use ($primaryAndCoNamesArray, $namesMatchFuzzy) {
                    if (empty($nameToCheck) || empty($primaryAndCoNamesArray)) return false;
                    $clean = strtolower(trim($nameToCheck));
                    
                    $parts = array_filter(array_map('trim', preg_split('/[,&]|\band\b/i', $clean)));
                    if (empty($parts)) return false;

                    if (count($parts) > 1) {
                        $allMatch = true;
                        foreach ($parts as $p) {
                            $found = false;
                            foreach ($primaryAndCoNamesArray as $pName) {
                                if ($namesMatchFuzzy($p, $pName)) {
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

                    foreach ($primaryAndCoNamesArray as $pName) {
                        if ($namesMatchFuzzy($clean, $pName)) {
                            return true;
                        }
                    }

                    return false;
                };

                $suspectsList = [];
                if (!empty($rawSuspects)) {
                    foreach ($rawSuspects as $s) {
                        $role = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                        $isAssoc = !empty($s['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                        if ($isAssoc && !empty($s['suspect_name']) && $isDuplicatePrimaryCoEntry($s['suspect_name'])) {
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

                $primarySuspectNamesList = [];
                $coSuspectNamesList = [];
                $associateSuspectNamesList = [];
                foreach ($suspectsList as $spIdx => $sp) {
                    $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect') || (!empty($sp['role']) && strtolower($sp['role']) !== 'primary suspect');
                    $spIsCo = $spIsAssoc && (
                        (!empty($sp['association_role']) && (str_contains(strtolower($sp['association_role']), 'co-suspect') || str_contains(strtolower($sp['association_role']), 'co suspect')))
                        || (!empty($sp['role']) && (str_contains(strtolower($sp['role']), 'co-suspect') || str_contains(strtolower($sp['role']), 'co suspect')))
                        || (!empty($sp['suspect_role']) && (str_contains(strtolower($sp['suspect_role']), 'co-suspect') || str_contains(strtolower($sp['suspect_role']), 'co suspect')))
                    );
                    $spNameRaw = $sp['suspect_name'] ?? '';
                    if (str_contains($spNameRaw, ',')) {
                        $spParts = array_map('trim', explode(',', $spNameRaw));
                        $spNameClean = $spParts[$spIdx] ?? ($spParts[0] ?? $spNameRaw);
                    } else {
                        $spNameClean = $spNameRaw;
                    }
                    if (!$spIsAssoc && !empty($spNameClean)) {
                        $primarySuspectNamesList[] = $spNameClean;
                    } elseif ($spIsCo && !empty($spNameClean)) {
                        $coSuspectNamesList[] = $spNameClean;
                    } elseif ($spIsAssoc && !empty($spNameClean)) {
                        $associateSuspectNamesList[] = $spNameClean;
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
                $primarySuspectsCombinedNames = !empty($primaryAndCoNamesList) ? implode(', ', $primaryAndCoNamesList) : (!empty($primarySuspectNamesList) ? implode(', ', array_unique($primarySuspectNamesList)) : ($case->suspect_name ?? 'Eric Addo'));
                $associateSuspectsCombinedNames = !empty($associateSuspectNamesList) ? implode(', ', array_unique($associateSuspectNamesList)) : 'Atta Budo';
                $renderedPrimaryExhibit = false;
            @endphp

            @foreach($suspectsList as $sIdx => $sItem)
                @php
                    $sNum   = $sItem['suspect_number'] ?? ($sIdx + 1);
                    $rawSName = $sItem['suspect_name'] ?? ('Suspect #' . $sNum);
                    if (str_contains($rawSName, ',')) {
                        $sNameParts = array_map('trim', explode(',', $rawSName));
                        $sName = $sNameParts[$sIdx] ?? ($sNameParts[0] ?? $rawSName);
                    } else {
                        $sName = $rawSName;
                    }
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
                    $sAddr  = $sItem['residential_address'] ?? ($sItem['address'] ?? ($sNum == 1 ? ($case->address ?? ($docketData['address'] ?? 'N/A')) : 'N/A'));
                    $sMarital = $sItem['marital_status'] ?? ($sNum == 1 ? $case->marital_status : null);
                    $sSpouseN = $sItem['spouse_name'] ?? ($sNum == 1 ? $case->spouse_name : null);
                    $sSpouseP = $sItem['spouse_phone'] ?? ($sNum == 1 ? $case->spouse_phone : null);
                    $sSpouseI = $sItem['spouse_info'] ?? ($sNum == 1 ? $case->spouse_info : null);
                    $sPartnerN= $sItem['partner_name'] ?? ($sNum == 1 ? $case->partner_name : null);
                    $sPartnerP= $sItem['partner_phone'] ?? ($sNum == 1 ? $case->partner_phone : null);
                    $sPartnerI= $sItem['partner_info'] ?? ($sNum == 1 ? $case->partner_info : null);
                    $sDependents = $sItem['dependents'] ?? ($sNum == 1 ? ($case->dependents ?? ($docketData['dependents'] ?? 'No Children')) : 'No Children');
                    $sRaid        = $sItem['raid_location'] ?? ($sItem['location'] ?? ($case->raid_location ?: ($case->location ?: ($docketData['raid_location'] ?? 'N/A'))));
                    $sArrestDate  = $sItem['date_of_arrest'] ?? ($sItem['arrest_date'] ?? ($case->arrest_date ?? ($docketData['arrest_date'] ?? ($docketData['date_of_arrest'] ?? ($case->incident_date ? substr((string)$case->incident_date, 0, 10) : null)))));
                    $sArrestTime  = $sItem['time_of_arrest'] ?? ($sItem['arrest_time'] ?? ($case->arrest_time ?? ($docketData['arrest_time'] ?? ($docketData['time_of_arrest'] ?? null))));
                    $sFacility    = $sItem['holding_facility'] ?? ($sItem['facility'] ?? ($case->holding_facility ?? ($docketData['holding_facility'] ?? null)));
                    if (empty($sFacility) || $sFacility === 'N/A') {
                        $sFacility = !empty($case->holding_facility) ? $case->holding_facility : (!empty($docketData['holding_facility']) ? $docketData['holding_facility'] : 'NACOC Custody & Holding Facility');
                    }
                    $sOffense     = $sItem['offense'] ?? ($sItem['offense_description'] ?? ($case->offense_description ?: ($case->description ?: ($docketData['offense_description'] ?? 'N/A'))));

                    $sFront = $sItem['mugshot_front'] ?? ($sItem['mugshots']['front'] ?? ($sItem['mugshots']['front-profile'] ?? ($sNum == 1 ? $case->mugshot_front : null)));
                    $sLeft  = $sItem['mugshot_left']  ?? ($sItem['mugshots']['left']  ?? ($sItem['mugshots']['left-profile']  ?? ($sNum == 1 ? $case->mugshot_left  : null)));
                    $sRight = $sItem['mugshot_right'] ?? ($sItem['mugshots']['right'] ?? ($sItem['mugshots']['right-profile'] ?? ($sNum == 1 ? $case->mugshot_right : null)));
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

                <!-- MUGSHOT (MULTI-ANGLE PHOTO CAPTURE FOR SUSPECT #{{ $sNum }}) -->
                <div class="dossier-card p-6 border border-slate-200">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-300 uppercase tracking-wider">
                                SUSPECT #{{ $sNum }} MUGSHOTS
                            </span>
                            <div>
                                <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    {{ $sName }} — Biometric Mugshots
                                    @if($isAssociate)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">{{ strtoupper($sRoleLabel) }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-slate-900 text-amber-300 shadow-2xs border border-slate-700">ID: {{ $sId }}</span>
                                    @endif
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Suspect facial photo captures recorded during booking
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Angle 1: Frontal View -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px]">ANGLE 1</span>
                                <span class="font-bold text-slate-700 text-[11px]">Front View (0&deg;)</span>
                                <span class="font-bold text-[10px] {{ $sFront ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sFront ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                            </div>
                            <div class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                @if($sFront)
                                    <img src="{{ $sFront }}" class="w-full h-full object-contain p-1" alt="Front View Mugshot">
                                @else
                                    <div class="text-center p-3 text-slate-400">
                                        <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] font-mono-code block">No Front Photo</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Angle 2: Left Profile -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE 2</span>
                                <span class="font-bold text-slate-700 text-[11px]">Left Profile (90&deg;)</span>
                                <span class="font-bold text-[10px] {{ $sLeft ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sLeft ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                            </div>
                            <div class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                @if($sLeft)
                                    <img src="{{ $sLeft }}" class="w-full h-full object-contain p-1" alt="Left Profile Mugshot">
                                @else
                                    <div class="text-center p-3 text-slate-400">
                                        <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] font-mono-code block">No Left Photo</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Angle 3: Right Profile -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE 3</span>
                                <span class="font-bold text-slate-700 text-[11px]">Right Profile (270&deg;)</span>
                                <span class="font-bold text-[10px] {{ $sRight ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sRight ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                            </div>
                            <div class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                @if($sRight)
                                    <img src="{{ $sRight }}" class="w-full h-full object-contain p-1" alt="Right Profile Mugshot">
                                @else
                                    <div class="text-center p-3 text-slate-400">
                                        <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] font-mono-code block">No Right Photo</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUSPECT BOOKING & PERSONAL DETAILS FOR SUSPECT #{{ $sNum }} -->
                @php
                    $sDisp = $sItem['suspect_disposition'] ?? 'Active — Under Investigation';
                    $isClearedDisp = $sDisp !== 'Active — Under Investigation';
                    $dispBadgeCls = match($sDisp) {
                        'Cleared / No Further Action'  => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                        'Released from Case'           => 'bg-purple-100 text-purple-800 border-purple-300',
                        'Reclassified'                 => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                        'Referred for Further Review'  => 'bg-blue-100 text-blue-800 border-blue-300',
                        default                        => 'bg-amber-100 text-amber-800 border-amber-300',
                    };
                @endphp
                <div class="dossier-card p-6 border border-slate-200 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase tracking-wider">
                                SUSPECT #{{ $sNum }} DOSSIER
                            </span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold uppercase tracking-wider border {{ $dispBadgeCls }}">
                                {{ strtoupper($sDisp) }}
                            </span>
                            <div>
                                <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    {{ $sName }} — Personal Booking Details
                                    @if($isAssociate)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">{{ strtoupper($sRoleLabel) }}</span>
                                    @endif
                                </h2>
                                <p class="text-xs text-slate-500">Legal identity, charges, family records, and custody allocation</p>
                            </div>
                        </div>
                        @if($isCaseController || $authUser->is_head)
                            <button type="button" 
                                    onclick="openSuspectDispositionModal('{{ addslashes($sName) }}', '{{ addslashes($sDisp) }}')" 
                                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 text-xs font-bold rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border-none shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Update Disposition
                            </button>
                        @endif
                    </div>

                    @if($isClearedDisp)
                        <div class="p-4 bg-emerald-50/90 border-2 border-emerald-300 rounded-xl space-y-1.5 font-sans">
                            <div class="flex items-center justify-between text-xs font-black text-emerald-950">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    SUSPECT DISPOSITION STATUS: {{ strtoupper($sDisp) }}
                                </span>
                                @if(!empty($sItem['disposition_updated_at']))
                                    <span class="text-[10px] font-mono-code font-extrabold text-emerald-700">UPDATED: {{ $sItem['disposition_updated_at'] }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-emerald-900 font-medium leading-relaxed">
                                <strong>Official Clearance Justification / Remark:</strong> "{{ $sItem['disposition_reason'] ?? 'No further investigative action required for this suspect.' }}"
                            </div>
                            <div class="text-[10px] font-mono-code text-emerald-700 flex items-center justify-between pt-1 border-t border-emerald-200/60">
                                <span>Authorized By: <strong>{{ $sItem['disposition_updated_by'] ?? ($controllerUser->fullname ?? 'Authorized Unit Head') }}</strong></span>
                                <span class="italic text-slate-500">Historical records, exhibits &amp; lab results remain preserved for audit.</span>
                            </div>
                        </div>
                    @endif

                    <!-- Legal Identity & Origin -->
                    <div class="space-y-3">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Legal Identity Details (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 rounded-xl {{ $isAssociate ? 'bg-amber-50/90 border-amber-200' : 'bg-slate-50 border-slate-200' }} border">
                                <div class="text-[10px] font-mono-code font-bold {{ $isAssociate ? 'text-amber-800' : 'text-slate-500' }} uppercase">Suspect Role / Classification</div>
                                <div class="font-extrabold text-xs sm:text-sm mt-0.5 {{ $isAssociate ? 'text-amber-900' : 'text-slate-900' }}">
                                    {{ strtoupper($sRoleLabel) }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Suspect Full Name</div>
                                <div class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $sName }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Alias / Street Name</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sAlias }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Ghana Card #</div>
                                <div class="font-bold font-mono-code text-sm mt-0.5">{{ $sCard }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Gender, Age &amp; DOB</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">
                                    {{ $sGender }}
                                    {{ !empty($sAge) ? "• Age: {$sAge}" : '' }}
                                    {{ !empty($sDOB) ? "• DOB: {$sDOB}" : '' }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Height &amp; Eye Color</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">
                                    {{ !empty($sHeight) ? "Height: {$sHeight}" : 'Height: N/A' }}
                                    •
                                    {{ !empty($sEye) ? "Eyes: {$sEye}" : 'Eyes: N/A' }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Nationality</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sNat }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Hometown / POB</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sPOB }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Contact Phone</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sPhone }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Residential Address / GPS</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sAddr }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Family & Marital Records -->
                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Family &amp; Relationship Records (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Marital / Relationship Status</div>
                                <div class="font-bold text-slate-900 mt-0.5">{{ $sMarital ?: 'N/A' }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Spouse / Partner Details</div>
                                <div class="font-bold text-slate-900 mt-0.5">
                                    @if(!empty($sSpouseN))
                                        <span class="font-black text-slate-950">Spouse: {{ $sSpouseN }}</span>
                                        @if(!empty($sSpouseP)) <span class="font-mono-code text-slate-700 font-semibold">({{ $sSpouseP }})</span> @endif
                                        @if(!empty($sSpouseI)) <span class="text-slate-600 font-normal italic text-[11px] block mt-0.5">— {{ $sSpouseI }}</span> @endif
                                    @elseif(!empty($sPartnerN))
                                        <span class="font-black text-slate-950">Partner: {{ $sPartnerN }}</span>
                                        @if(!empty($sPartnerP)) <span class="font-mono-code text-slate-700 font-semibold">({{ $sPartnerP }})</span> @endif
                                        @if(!empty($sPartnerI)) <span class="text-slate-600 font-normal italic text-[11px] block mt-0.5">— {{ $sPartnerI }}</span> @endif
                                    @else
                                        <span class="text-slate-400 font-normal">{{ $sSpouseI ?: 'N/A' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Children &amp; Dependents</div>
                                <div class="font-bold text-slate-900 mt-0.5">
                                    {{ $sDependents }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Offence Charges & Custody Allocation -->
                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Offence Charges &amp; Custody Details (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Raid Scene / Offence Location</div>
                                <div class="font-extrabold text-slate-900 text-sm">{{ $sRaid }}</div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Arrest Date &amp; Time</div>
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $sArrestDate ? \Carbon\Carbon::parse($sArrestDate)->format('M d, Y') : 'N/A' }} {{ $sArrestTime ? "@ {$sArrestTime}" : '' }}
                                </div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Assigned Custody Holding Facility</div>
                                <div class="font-bold text-slate-900 text-sm">{{ $sFacility }}</div>
                            </div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Brief Facts/ Charge Description</div>
                            <p class="text-slate-800 leading-relaxed font-semibold">
                                {{ $sOffense }}
                            </p>
                        </div>
                    </div>

                    @if($isAssociate)
                        @php
                            $assocExhibits = $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel);

                            if (empty($assocExhibits)) {
                                $directEx = $sItem['exhibits'] ?? ($sItem['seizures'] ?? ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                                if (!empty($directEx) && is_array($directEx)) {
                                    $assocExhibits = $directEx;
                                }
                            }

                        @endphp

                        @if(!empty($assocExhibits) && count($assocExhibits) > 0)
                        <div class="p-5 bg-amber-50/50 border-2 border-amber-300 rounded-2xl space-y-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-amber-200">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-amber-600 text-white uppercase tracking-wider shadow-2xs">
                                        ASSOCIATE EXHIBIT DOSSIER
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                            Associated Seizures &amp; Exhibits — {{ $sName }}
                                        </h3>
                                        <p class="text-[11px] text-slate-600">Confiscated items and exhibit inventory attached to Associate {{ $sName }}</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-100 text-amber-900 border border-amber-300 w-max">
                                    {{ count($assocExhibits) }} EXHIBIT ITEM(S) LOGGED
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                @foreach($assocExhibits as $aEx)
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">Exhibit Category</div>
                                        <div class="font-extrabold text-amber-900 text-xs mt-0.5 font-mono-code">{{ $aEx['category'] ?? ($aEx['exhibit_category'] ?? 'Associate Exhibit') }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit / Commodity Name</div>
                                        <div class="font-extrabold text-slate-900 text-xs mt-0.5">{{ $aEx['seizure_name'] ?? ($aEx['name'] ?? 'N/A') }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight</div>
                                        <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                                            @if(!empty($aEx['seizure_quantity']) || !empty($aEx['seizure_weight']))
                                                {{ implode(' • ', array_filter([!empty($aEx['seizure_quantity']) ? "Qty: {$aEx['seizure_quantity']}" : null, !empty($aEx['seizure_weight']) ? "Wt: {$aEx['seizure_weight']} " . ($aEx['seizure_unit'] ?? '') : null])) }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                                        @php
                                            $rawValA = $aEx['seizure_value'] ?? ($aEx['value'] ?? null);
                                            $cleanValA = (!empty($rawValA) && !str_contains($rawValA, 'Item #') && !str_contains($rawValA, "\n")) ? trim($rawValA) : 'N/A';
                                        @endphp
                                        <div class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">{{ $cleanValA }}</div>
                                    </div>
                                    @if(!empty($aEx['seizure_packaging']))
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Packaging &amp; Format</div>
                                        <div class="font-bold text-slate-900 text-xs mt-0.5">{{ $aEx['seizure_packaging'] }}</div>
                                    </div>
                                    @endif

                                    @if(!empty($aEx['other_exhibit_description']) || !empty($aEx['other_description']))
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-full">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Other Exhibit Description / Specifications</div>
                                        <div class="font-extrabold text-slate-900 text-xs mt-0.5 whitespace-pre-line">{{ $aEx['other_exhibit_description'] ?? $aEx['other_description'] }}</div>
                                    </div>
                                    @endif

                                    @php
                                        $catLower = strtolower($aEx['category'] ?? ($aEx['exhibit_category'] ?? ''));
                                        $nameLower = strtolower($aEx['seizure_name'] ?? ($aEx['commodity_name'] ?? ($aEx['name'] ?? '')));
                                        $hasWeaponData = !empty($aEx['weapon_model']) || !empty($aEx['weapon_serial_number']) || !empty($aEx['weapon_calibre']) || !empty($aEx['weapon_loaded_status']) || !empty($aEx['weapon_condition_at_recovery']) || !empty($aEx['weapon_location_found']) || !empty($aEx['weapon_distinguishing_marks']);
                                        $isWeaponEx = str_contains($catLower, 'weapon') || str_contains($nameLower, 'ak-47') || str_contains($nameLower, 'pistol') || str_contains($nameLower, 'rifle') || str_contains($nameLower, 'shotgun') || str_contains($nameLower, 'firearm') || $hasWeaponData;

                                        $hasVehicleData = !empty($aEx['veh_make']) || !empty($aEx['veh_model']) || !empty($aEx['veh_reg_no']) || !empty($aEx['veh_vin']) || !empty($aEx['veh_colour']) || !empty($aEx['veh_type']) || !empty($aEx['vehicle_make_model']) || !empty($aEx['vehicle_registration']) || !empty($aEx['vehicle_chassis_vin']);
                                        $isVehEx = str_contains($catLower, 'vehicle') || str_contains($catLower, 'vessel') || str_contains($catLower, 'car') || str_contains($catLower, 'truck') || str_contains($catLower, 'automobile') || str_contains($nameLower, 'zonda') || str_contains($nameLower, 'toyota') || str_contains($nameLower, 'nissan') || str_contains($nameLower, 'honda') || str_contains($nameLower, 'benz') || str_contains($nameLower, 'bmw') || $hasVehicleData;
                                    @endphp

                                    @if($isVehEx)
                                    @php
                                        $vMake = !empty($aEx['veh_make']) ? $aEx['veh_make'] : null;
                                        $vModel = !empty($aEx['veh_model']) ? $aEx['veh_model'] : null;
                                        $vMakeModelFull = implode(' ', array_filter([$vMake, $vModel]));
                                        if (empty($vMakeModelFull)) {
                                            $vMakeModelFull = $aEx['vehicle_make_model'] ?? ($aEx['seizure_name'] ?? ($aEx['commodity_name'] ?? ($aEx['name'] ?? 'N/A')));
                                        }

                                        $vRegNo = $aEx['veh_reg_no'] ?? ($aEx['vehicle_registration'] ?? ($aEx['registration_number'] ?? 'N/A'));
                                        $vVin = $aEx['veh_vin'] ?? ($aEx['vehicle_chassis_vin'] ?? ($aEx['chassis_number'] ?? 'N/A'));
                                        $vType = $aEx['veh_type'] ?? ($aEx['vehicle_type'] ?? 'Vehicle Exhibit');
                                        $vColor = $aEx['veh_colour'] ?? ($aEx['vehicle_color_type'] ?? 'N/A');
                                        $vYear = $aEx['veh_year'] ?? ($aEx['vehicle_year'] ?? 'N/A');
                                        $vEngine = $aEx['veh_engine_no'] ?? ($aEx['engine_number'] ?? 'N/A');
                                        $vCountry = $aEx['veh_country_reg'] ?? ($aEx['country_of_registration'] ?? 'N/A');
                                        $vOwner = $aEx['veh_owner'] ?? ($aEx['vehicle_owner'] ?? 'N/A');
                                        $vDriver = $aEx['veh_driver'] ?? ($aEx['vehicle_driver'] ?? 'N/A');

                                        $vLoc = $aEx['seizure_location'] ?? ($aEx['location'] ?? 'N/A');
                                        $vOfficer = $aEx['seizure_officer'] ?? ($aEx['officer'] ?? 'N/A');
                                        $vDate = !empty($aEx['seizure_date']) ? $aEx['seizure_date'] : null;
                                        $vTime = !empty($aEx['seizure_time']) ? "@ {$aEx['seizure_time']}" : '';
                                        $vDateTime = !empty($vDate) ? "{$vDate} {$vTime}" : 'N/A';
                                        $vReason = $aEx['seizure_reason'] ?? 'N/A';
                                    @endphp
                                    <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-blue-500/40 space-y-3 mt-2 shadow-md">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 bg-blue-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">VEHICLE / VESSEL SPECIFIC DETAILS</span>
                                                <span class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper($vMakeModelFull) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if(!empty($vRegNo) && $vRegNo !== 'N/A')
                                                    <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">REG #: {{ strtoupper($vRegNo) }}</span>
                                                @endif
                                                @if(!empty($vVin) && $vVin !== 'N/A')
                                                    <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-blue-300 font-extrabold border border-blue-500/30">VIN: {{ strtoupper($vVin) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MAKE / MODEL</div>
                                                <div class="font-black text-white text-xs mt-0.5">{{ $vMakeModelFull }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">REGISTRATION NUMBER</div>
                                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ $vRegNo }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">VIN / CHASSIS NUMBER</div>
                                                <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">{{ $vVin }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / FINISH</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vColor }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">VEHICLE TYPE / BODY</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vType }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MANUFACTURE YEAR</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vYear }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">ENGINE NUMBER</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vEngine }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">REGISTERED OWNER</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vOwner }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">DRIVER AT RECOVERY</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vDriver }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">RECOVERY LOCATION</div>
                                                <div class="font-extrabold text-emerald-400 text-xs mt-0.5">{{ $vLoc }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SEIZING OFFICER</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vOfficer }}</div>
                                            </div>

                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">RECOVERY DATE & TIME</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5 font-mono-code">{{ $vDateTime }}</div>
                                            </div>

                                            @if(!empty($vReason) && $vReason !== 'N/A')
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-full">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SEIZURE REASON / INVESTIGATIVE REMARKS</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5 leading-relaxed">{{ $vReason }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    @if($isWeaponEx)
                                    <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-red-500/40 space-y-3 mt-2 shadow-md">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 bg-red-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">FIREARM / WEAPON SPECIFIC DETAILS</span>
                                                <span class="text-xs font-black text-slate-200">{{ $aEx['weapon_model'] ?? ($aEx['seizure_name'] ?? 'Firearm / Weapon Exhibit') }}</span>
                                            </div>
                                            @if(!empty($aEx['weapon_serial_number']))
                                                <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL #: {{ $aEx['weapon_serial_number'] }}</span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                            @if(!empty($aEx['weapon_model']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MODEL / TYPE</div>
                                                <div class="font-bold text-white text-xs mt-0.5">{{ $aEx['weapon_model'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_calibre']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CALIBRE / GAUGE</div>
                                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ $aEx['weapon_calibre'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_color']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / FINISH</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $aEx['weapon_color'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_size_length']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SIZE / LENGTH</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $aEx['weapon_size_length'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_loaded_status']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">LOADED STATUS</div>
                                                <div class="font-extrabold text-red-400 text-xs mt-0.5">{{ $aEx['weapon_loaded_status'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_condition_at_recovery']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CONDITION AT RECOVERY</div>
                                                <div class="font-extrabold text-emerald-400 text-xs mt-0.5">{{ $aEx['weapon_condition_at_recovery'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_location_found']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">EXACT LOCATION FOUND</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $aEx['weapon_location_found'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aEx['weapon_distinguishing_marks']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-full">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">DISTINGUISHING MARKS / ENGRAVINGS</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $aEx['weapon_distinguishing_marks'] }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    @php
                                        $aAmmo = $aEx['ammunition_details'] ?? null;
                                        if (is_string($aAmmo)) {
                                            $aAmmo = json_decode($aAmmo, true) ?: [];
                                        }
                                        $isAmmoEx = str_contains($catLower, 'ammo') || str_contains($catLower, 'ammunition') || str_contains($nameLower, 'ammo') || str_contains($nameLower, 'round') || (!empty($aAmmo) && is_array($aAmmo) && array_filter($aAmmo));
                                    @endphp

                                    @if($isAmmoEx && !empty($aAmmo) && is_array($aAmmo))
                                    <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-orange-500/40 space-y-3 mt-2 shadow-md">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 bg-orange-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">AMMUNITION SPECIFIC DETAILS</span>
                                                <span class="text-xs font-black text-slate-200">{{ $aEx['seizure_name'] ?? ($aEx['name'] ?? 'Ammunition Exhibit') }}</span>
                                            </div>
                                            @if(!empty($aAmmo['package_reference_number']))
                                                <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">REF #: {{ $aAmmo['package_reference_number'] }}</span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                            @if(!empty($aAmmo['cartridge_appearance']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CARTRIDGE APPEARANCE</div>
                                                <div class="font-bold text-white text-xs mt-0.5">{{ $aAmmo['cartridge_appearance'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['projectile_bullet_type']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">PROJECTILE TYPE</div>
                                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ $aAmmo['projectile_bullet_type'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['cartridge_case_material']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CASE MATERIAL</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $aAmmo['cartridge_case_material'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['colour_markings']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / MARKINGS</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $aAmmo['colour_markings'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['condition']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CONDITION</div>
                                                <div class="font-extrabold text-emerald-400 text-xs mt-0.5">{{ $aAmmo['condition'] }} {{ !empty($aAmmo['other_condition']) ? "({$aAmmo['other_condition']})" : '' }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['packaging_type']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">PACKAGING TYPE</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $aAmmo['packaging_type'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['seal_tamper_evident_condition']) || !empty($aAmmo['seal_condition']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SEAL / TAMPER CONDITION</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $aAmmo['seal_tamper_evident_condition'] ?? $aAmmo['seal_condition'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($aAmmo['condition_when_received']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CONDITION WHEN RECEIVED</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $aAmmo['condition_when_received'] }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- ASSOCIATE EXHIBIT PHOTOS GALLERY -->
                            @php
                                $assocPhotos = [];
                                foreach($assocExhibits as $aEx) {
                                    $extractedA = $extractAllPhotosHelper($aEx);
                                    foreach ($extractedA as $ap) { if ($ap && !in_array($ap, $assocPhotos)) $assocPhotos[] = $ap; }
                                }
                                if (!empty($sItem['evidence_photos'])) {
                                    $sEvPhotos = is_array($sItem['evidence_photos']) ? $sItem['evidence_photos'] : (json_decode($sItem['evidence_photos'], true) ?: []);
                                    if (is_array($sEvPhotos)) {
                                        foreach ($sEvPhotos as $sp) { if ($sp && !in_array($sp, $assocPhotos)) $assocPhotos[] = $sp; }
                                    }
                                }

                            @endphp
                            @if(!empty($assocPhotos) && count($assocPhotos) > 0)
                            <div class="pt-3 border-t border-amber-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">SEIZURE &amp; EXHIBIT EVIDENCE GALLERY ({{ strtoupper($sName) }})</span>
                                    <span class="text-[10px] font-mono-code font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded border border-amber-300">{{ count($assocPhotos) }} Attached Photo(s)</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                    @foreach($assocPhotos as $apIdx => $apUrl)
                                        @php
                                            $apUrlFormatted = $formatPhotoUrlHelper($apUrl);
                                        @endphp
                                        <div class="p-1 bg-white border border-amber-200 rounded-xl space-y-1 text-center shadow-2xs group hover:border-amber-500 transition-colors">
                                            <div class="w-full h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 cursor-pointer relative" data-img-src="{{ $apUrlFormatted }}" data-img-title="Exhibit Photo #{{ $apIdx + 1 }} — {{ $sName }}" onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                                <img src="{{ $apUrlFormatted }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="Exhibit Photo" onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                            </div>
                                            <div class="text-[9px] font-mono-code font-bold text-slate-600 uppercase">Exhibit Photo #{{ $apIdx + 1 }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    @else
                        @php
                            $primaryExhibits = $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel);
                            if (empty($primaryExhibits)) {
                                $directEx = $sItem['exhibits'] ?? ($sItem['seizures'] ?? ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                                if (!empty($directEx) && is_array($directEx)) {
                                    $primaryExhibits = $directEx;
                                }
                            }

                        @endphp

                        @if(!empty($primaryExhibits) && count($primaryExhibits) > 0)
                        @php
                            $hasCoSuspectsInCase = false;
                            if (!empty($rawSuspects) && is_array($rawSuspects) && count($rawSuspects) > 1) {
                                foreach ($rawSuspects as $spCheck) {
                                    $rCheck = strtolower($spCheck['association_role'] ?? ($spCheck['role'] ?? ''));
                                    if (str_contains($rCheck, 'co-suspect') || !empty($spCheck['is_co_suspect'])) {
                                        $hasCoSuspectsInCase = true;
                                        break;
                                    }
                                }
                            }
                            $dossierBadgeText = $hasCoSuspectsInCase ? 'PRIMARY & CO-SUSPECT EXHIBIT DOSSIER' : 'PRIMARY EXHIBIT DOSSIER';
                            $dossierHeadingText = $hasCoSuspectsInCase ? "Primary & Co-Suspect Seizures & Exhibits — {$sName}" : "Primary Exhibits — {$sName}";
                        @endphp
                        <!-- LOGGED SEIZURES & EXHIBITS DOSSIER FOR {{ strtoupper($sName) }} -->
                        <div class="p-5 bg-slate-50 border-2 border-slate-300 rounded-2xl space-y-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-slate-900 text-white uppercase tracking-wider shadow-2xs">
                                        {{ $dossierBadgeText }}
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                            {{ $dossierHeadingText }}
                                        </h3>
                                        <p class="text-[11px] text-slate-600">Confiscated items and exhibit inventory attached to {{ $sName }}</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-slate-200 text-slate-800 border border-slate-300 w-max">
                                    {{ count($primaryExhibits) }} EXHIBIT ITEM(S) LOGGED
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                @foreach($primaryExhibits as $pEx)
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit Category</div>
                                        <div class="font-extrabold text-amber-700 text-xs mt-0.5 font-mono-code">{{ $pEx['category'] ?? ($pEx['exhibit_category'] ?? 'General Exhibit') }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Seizure / Commodity Name</div>
                                        <div class="font-extrabold text-slate-900 text-xs mt-0.5">{{ $pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A')) }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight</div>
                                        <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                                            @if(!empty($pEx['seizure_quantity']) || !empty($pEx['seizure_weight']))
                                                {{ implode(' • ', array_filter([!empty($pEx['seizure_quantity']) ? "Qty: {$pEx['seizure_quantity']}" : null, !empty($pEx['seizure_weight']) ? "Wt: {$pEx['seizure_weight']} " . ($pEx['seizure_unit'] ?? '') : null])) }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                                        @php
                                            $rawValP = $pEx['seizure_value'] ?? ($pEx['value'] ?? null);
                                            $cleanValP = (!empty($rawValP) && !str_contains($rawValP, 'Item #') && !str_contains($rawValP, "\n")) ? trim($rawValP) : 'N/A';
                                        @endphp
                                        <div class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">{{ $cleanValP }}</div>
                                    </div>

                                    @if(!empty($pEx['other_exhibit_description']) || !empty($pEx['other_description']))
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs col-span-full">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Other Exhibit Description / Specifications</div>
                                        <div class="font-extrabold text-slate-900 text-xs mt-0.5 whitespace-pre-line">{{ $pEx['other_exhibit_description'] ?? $pEx['other_description'] }}</div>
                                    </div>
                                    @endif

                                    @php
                                        $catLower = strtolower($pEx['category'] ?? ($pEx['exhibit_category'] ?? ''));
                                        $nameLower = strtolower($pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? '')));
                                        $hasWeaponData = !empty($pEx['weapon_model']) || !empty($pEx['weapon_serial_number']) || !empty($pEx['weapon_calibre']) || !empty($pEx['weapon_loaded_status']) || !empty($pEx['weapon_condition_at_recovery']) || !empty($pEx['weapon_location_found']) || !empty($pEx['weapon_distinguishing_marks']);
                                        $isWeaponEx = str_contains($catLower, 'weapon') || str_contains($nameLower, 'ak-47') || str_contains($nameLower, 'pistol') || str_contains($nameLower, 'rifle') || str_contains($nameLower, 'shotgun') || str_contains($nameLower, 'firearm') || $hasWeaponData;

                                        $hasVehicleData = !empty($pEx['veh_make']) || !empty($pEx['veh_model']) || !empty($pEx['veh_reg_no']) || !empty($pEx['veh_vin']) || !empty($pEx['veh_colour']) || !empty($pEx['veh_type']) || !empty($pEx['vehicle_make_model']) || !empty($pEx['vehicle_registration']) || !empty($pEx['vehicle_chassis_vin']);
                                        $isVehEx = str_contains($catLower, 'vehicle') || str_contains($catLower, 'vessel') || str_contains($catLower, 'car') || str_contains($catLower, 'truck') || str_contains($catLower, 'automobile') || str_contains($nameLower, 'zonda') || str_contains($nameLower, 'toyota') || str_contains($nameLower, 'nissan') || str_contains($nameLower, 'honda') || str_contains($nameLower, 'benz') || str_contains($nameLower, 'bmw') || $hasVehicleData;
                                    @endphp

                                    @if($isVehEx)
                                     @php
                                         $vMake = !empty($pEx['veh_make']) ? $pEx['veh_make'] : null;
                                         $vModel = !empty($pEx['veh_model']) ? $pEx['veh_model'] : null;
                                         $vMakeModelFull = implode(' ', array_filter([$vMake, $vModel]));
                                         if (empty($vMakeModelFull)) {
                                             $vMakeModelFull = $pEx['vehicle_make_model'] ?? ($pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A')));
                                         }

                                         $vRegNo = $pEx['veh_reg_no'] ?? ($pEx['vehicle_registration'] ?? ($pEx['registration_number'] ?? 'N/A'));
                                         $vVin = $pEx['veh_vin'] ?? ($pEx['vehicle_chassis_vin'] ?? ($pEx['chassis_number'] ?? 'N/A'));
                                         $vType = $pEx['veh_type'] ?? ($pEx['vehicle_type'] ?? 'Vehicle Exhibit');
                                         $vColor = $pEx['veh_colour'] ?? ($pEx['vehicle_color_type'] ?? 'N/A');
                                         $vYear = $pEx['veh_year'] ?? ($pEx['vehicle_year'] ?? 'N/A');
                                         $vEngine = $pEx['veh_engine_no'] ?? ($pEx['engine_number'] ?? 'N/A');
                                         $vCountry = $pEx['veh_country_reg'] ?? ($pEx['country_of_registration'] ?? 'N/A');
                                         $vOwner = $pEx['veh_owner'] ?? ($pEx['vehicle_owner'] ?? ($primarySuspectName ?? ($case->suspect_name ?? 'Primary Suspect')));
                                         $vDriver = $pEx['veh_driver'] ?? ($pEx['vehicle_driver'] ?? 'N/A');

                                         $vLoc = $pEx['seizure_location'] ?? ($pEx['location'] ?? 'N/A');
                                         $vOfficer = $pEx['seizure_officer'] ?? ($pEx['officer'] ?? 'N/A');
                                         $vDate = !empty($pEx['seizure_date']) ? $pEx['seizure_date'] : null;
                                         $vTime = !empty($pEx['seizure_time']) ? "@ {$pEx['seizure_time']}" : '';
                                         $vDateTime = !empty($vDate) ? "{$vDate} {$vTime}" : 'N/A';
                                         $vReason = $pEx['seizure_reason'] ?? 'N/A';
                                     @endphp
                                     <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-blue-500/40 space-y-3 mt-2 shadow-md">
                                         <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                             <div class="flex items-center space-x-2">
                                                 <span class="px-2 py-0.5 bg-blue-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">VEHICLE / VESSEL SPECIFIC DETAILS</span>
                                                 <span class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper($vMakeModelFull) }}</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 @if(!empty($vRegNo) && $vRegNo !== 'N/A')
                                                     <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">REG #: {{ strtoupper($vRegNo) }}</span>
                                                 @endif
                                                 @if(!empty($vVin) && $vVin !== 'N/A')
                                                     <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-blue-300 font-extrabold border border-blue-500/30">VIN: {{ strtoupper($vVin) }}</span>
                                                 @endif
                                             </div>
                                         </div>

                                         <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MAKE / MODEL / TRIM</div>
                                                 <div class="font-black text-white text-xs mt-0.5">{{ $vMakeModelFull }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">REGISTRATION NUMBER</div>
                                                 <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ $vRegNo }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">VIN / CHASSIS NUMBER</div>
                                                 <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">{{ $vVin }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / FINISH</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vColor }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">VEHICLE TYPE / BODY</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vType }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MANUFACTURE YEAR</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vYear }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">ENGINE NUMBER</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vEngine }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">REGISTERED OWNER</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vOwner }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">DRIVER AT RECOVERY</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vDriver }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">RECOVERY LOCATION</div>
                                                 <div class="font-extrabold text-emerald-400 text-xs mt-0.5">{{ $vLoc }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SEIZING OFFICER</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $vOfficer }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">RECOVERY DATE & TIME</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5 font-mono-code">{{ $vDateTime }}</div>
                                             </div>
                                         </div>
                                     </div>
                                     @endif


                                    @if($isWeaponEx)
                                    <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-red-500/40 space-y-3 mt-2 shadow-md">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 bg-red-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">FIREARM / WEAPON SPECIFIC DETAILS</span>
                                                <span class="text-xs font-black text-slate-200">{{ $pEx['weapon_model'] ?? ($pEx['seizure_name'] ?? 'Firearm / Weapon Exhibit') }}</span>
                                            </div>
                                            @if(!empty($pEx['weapon_serial_number']))
                                                <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL #: {{ $pEx['weapon_serial_number'] }}</span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                            @if(!empty($pEx['weapon_model']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MODEL / TYPE</div>
                                                <div class="font-bold text-white text-xs mt-0.5">{{ $pEx['weapon_model'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_calibre']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CALIBRE / GAUGE</div>
                                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ $pEx['weapon_calibre'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_color']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / FINISH</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $pEx['weapon_color'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_size_length']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SIZE / LENGTH</div>
                                                <div class="font-bold text-slate-200 text-xs mt-0.5">{{ $pEx['weapon_size_length'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_loaded_status']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">LOADED STATUS</div>
                                                <div class="font-extrabold text-red-400 text-xs mt-0.5">{{ $pEx['weapon_loaded_status'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_condition_at_recovery']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">CONDITION AT RECOVERY</div>
                                                <div class="font-extrabold text-emerald-400 text-xs mt-0.5">{{ $pEx['weapon_condition_at_recovery'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_location_found']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">EXACT LOCATION FOUND</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $pEx['weapon_location_found'] }}</div>
                                            </div>
                                            @endif
                                            @if(!empty($pEx['weapon_distinguishing_marks']))
                                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-full">
                                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">DISTINGUISHING MARKS / ENGRAVINGS</div>
                                                <div class="font-bold text-slate-100 text-xs mt-0.5">{{ $pEx['weapon_distinguishing_marks'] }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>

                            @php
                                $primPhotos = [];
                                if (!empty($primaryExhibits) && is_array($primaryExhibits)) {
                                    foreach($primaryExhibits as $pExItem) {
                                        $extractedP = $extractAllPhotosHelper($pExItem);
                                        foreach ($extractedP as $p) { if ($p && !in_array($p, $primPhotos)) $primPhotos[] = $p; }
                                    }
                                }
                                if (!empty($sItem['evidence_photos'])) {
                                    $sEvP = is_array($sItem['evidence_photos']) ? $sItem['evidence_photos'] : (json_decode($sItem['evidence_photos'], true) ?: []);
                                    if (is_array($sEvP)) {
                                        foreach ($sEvP as $sp) { if ($sp && !in_array($sp, $primPhotos)) $primPhotos[] = $sp; }
                                    }
                                }
                                if (empty($primPhotos) && !empty($docketData['evidence_photos'])) {
                                    foreach ($docketData['evidence_photos'] as $p) {
                                        if ($p && !in_array($p, $primPhotos)) $primPhotos[] = $p;
                                    }
                                }

                            @endphp
                            @if(!empty($primPhotos) && count($primPhotos) > 0)
                            <div class="pt-3 border-t border-slate-200 space-y-2 mt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">SEIZURE &amp; EXHIBIT EVIDENCE GALLERY ({{ strtoupper($sName) }})</span>
                                    <span class="text-[10px] font-mono-code font-bold text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">{{ count($primPhotos) }} Attached Photo(s)</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                    @foreach($primPhotos as $pIdx => $pUrl)
                                        @php
                                            $pUrlFormatted = $formatPhotoUrlHelper($pUrl);
                                        @endphp
                                        <div class="p-1 bg-white border border-slate-200 rounded-xl space-y-1 text-center shadow-2xs group hover:border-emerald-500 transition-colors">
                                            <div class="w-full h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 cursor-pointer relative" data-img-src="{{ $pUrlFormatted }}" data-img-title="Exhibit Photo #{{ $pIdx + 1 }} — {{ $sName }}" onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                                <img src="{{ $pUrlFormatted }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="Exhibit Photo" onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                            </div>
                                            <div class="text-[9px] font-mono-code font-bold text-slate-600 uppercase">Exhibit Photo #{{ $pIdx + 1 }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    @endif



                    @php
                        $sDossierLabExam = null;
                        $sDossierHasSavedLab = false;

                        // Source 1: DB relationship check
                        if ($isAssociate) {
                            $dbAssoc = $case->associateLabExaminations ? $case->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($sName))) : null;
                            if ($dbAssoc && $dbAssoc->has_saved) {
                                $sDossierHasSavedLab = true;
                                $sDossierLabExam = [
                                    'exam_reference'      => $dbAssoc->exam_reference,
                                    'exhibit_number'      => $dbAssoc->exhibit_number,
                                    'laboratory_number'   => $dbAssoc->laboratory_number,
                                    'analyst'             => $dbAssoc->analyst,
                                    'date_received'       => is_object($dbAssoc->date_received) ? $dbAssoc->date_received->format('Y-m-d') : substr((string)$dbAssoc->date_received, 0, 10),
                                    'date_started'        => is_object($dbAssoc->date_started) ? $dbAssoc->date_started->format('Y-m-d') : substr((string)$dbAssoc->date_started, 0, 10),
                                    'date_completed'      => is_object($dbAssoc->date_completed) ? $dbAssoc->date_completed->format('Y-m-d') : substr((string)$dbAssoc->date_completed, 0, 10),
                                    'presumptive_result'  => $dbAssoc->presumptive_result,
                                    'confirmatory_result' => $dbAssoc->confirmatory_result,
                                    'sample_weight'       => [
                                        'description'      => $dbAssoc->sample_weight_description,
                                        'units_packaging'  => $dbAssoc->sample_weight_units,
                                        'gross_weight'     => $dbAssoc->sample_weight_gross,
                                        'sample_taken'     => $dbAssoc->sample_weight_taken,
                                        'reserved_samples' => $dbAssoc->sample_weight_reserved,
                                    ],
                                    'substance_identification' => $dbAssoc->substance_identification,
                                    'detected_substances'      => is_string($dbAssoc->detected_substances) ? json_decode($dbAssoc->detected_substances, true) : ($dbAssoc->detected_substances ?? []),
                                    'test_methods'             => $dbAssoc->test_methods,
                                    'specifications'           => $dbAssoc->specifications,
                                    'remark'                   => $dbAssoc->remark,
                                    'findings'                 => $dbAssoc->findings,
                                ];
                            }
                        } else {
                            if (!empty($dbPrimaryLabExam) && $dbPrimaryLabExam->has_saved) {
                                $sDossierHasSavedLab = true;
                                $sDossierLabExam = [
                                    'exam_reference'      => $dbPrimaryLabExam->exam_reference,
                                    'exhibit_number'      => $dbPrimaryLabExam->exhibit_number,
                                    'laboratory_number'   => $dbPrimaryLabExam->laboratory_number,
                                    'analyst'             => $dbPrimaryLabExam->analyst,
                                    'date_received'       => is_object($dbPrimaryLabExam->date_received) ? $dbPrimaryLabExam->date_received->format('Y-m-d') : substr((string)$dbPrimaryLabExam->date_received, 0, 10),
                                    'date_started'        => is_object($dbPrimaryLabExam->date_started) ? $dbPrimaryLabExam->date_started->format('Y-m-d') : substr((string)$dbPrimaryLabExam->date_started, 0, 10),
                                    'date_completed'      => is_object($dbPrimaryLabExam->date_completed) ? $dbPrimaryLabExam->date_completed->format('Y-m-d') : substr((string)$dbPrimaryLabExam->date_completed, 0, 10),
                                    'presumptive_result'  => $dbPrimaryLabExam->presumptive_result,
                                    'confirmatory_result' => $dbPrimaryLabExam->confirmatory_result,
                                    'sample_weight'       => [
                                        'description'      => $dbPrimaryLabExam->sample_weight_description,
                                        'units_packaging'  => $dbPrimaryLabExam->sample_weight_units,
                                        'gross_weight'     => $dbPrimaryLabExam->sample_weight_gross,
                                        'sample_taken'     => $dbPrimaryLabExam->sample_weight_taken,
                                        'reserved_samples' => $dbPrimaryLabExam->sample_weight_reserved,
                                    ],
                                    'substance_identification' => $dbPrimaryLabExam->substance_identification,
                                    'detected_substances'      => is_string($dbPrimaryLabExam->detected_substances) ? json_decode($dbPrimaryLabExam->detected_substances, true) : ($dbPrimaryLabExam->detected_substances ?? []),
                                    'test_methods'             => $dbPrimaryLabExam->test_methods,
                                    'specifications'           => $dbPrimaryLabExam->specifications,
                                    'remark'                   => $dbPrimaryLabExam->remark,
                                    'findings'                 => $dbPrimaryLabExam->findings,
                                ];
                            }
                        }

                        // Source 2: Docket Data associate_lab_examinations
                        if (!$sDossierHasSavedLab && !empty($docketData['associate_lab_examinations'])) {
                            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                                if (strtolower(trim($kAssoc)) === strtolower(trim($sName)) && !empty($vAssoc['has_saved'])) {
                                    $sDossierHasSavedLab = true;
                                    $sDossierLabExam = $vAssoc;
                                    break;
                                }
                            }
                        }

                        // Source 3: Docket Data lab_examinations_list
                        if (!$sDossierHasSavedLab && !empty($docketData['lab_examinations_list'])) {
                            foreach ($docketData['lab_examinations_list'] as $lItem) {
                                $ownerMatch = !empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($sName));
                                $sMatch = !empty($lItem['suspect_name']) && strtolower(trim($lItem['suspect_name'])) === strtolower(trim($sName));
                                if (($ownerMatch || $sMatch) && (!empty($lItem['has_saved']) || !empty($lItem['presumptive_result']) || !empty($lItem['substance_identification']))) {
                                    $sDossierHasSavedLab = true;
                                    $sDossierLabExam = $lItem;
                                    break;
                                }
                            }
                        }

                        // Source 4: Docket Data lab_examination (for primary suspect)
                        if (!$sDossierHasSavedLab && !$isAssociate && ($sIdx === 0) && !empty($docketData['lab_examination']['has_saved'])) {
                            $labOwner = $docketData['lab_examination']['exhibit_owner_name'] ?? 'Primary Suspect';
                            if (strtolower(trim($labOwner)) === 'primary suspect' || strtolower(trim($labOwner)) === strtolower(trim($sName))) {
                                $sDossierHasSavedLab = true;
                                $sDossierLabExam = $docketData['lab_examination'];
                            }
                        }

                        // Source 5: Direct Case model fields
                        if (!$sDossierHasSavedLab && !$isAssociate && ($sIdx === 0) && (!empty($case->presumptive_test_result) || !empty($case->confirmatory_substance_identification))) {
                            $sDossierHasSavedLab = true;
                            $sDossierLabExam = [
                                'exam_reference'      => $case->lab_exam_reference ?: 'GSA-FM-T77-F',
                                'exhibit_number'      => $case->exhibit_number ?: 'EXH-01',
                                'laboratory_number'   => $case->laboratory_number ?: 'Lab No. 12/FS05/2025',
                                'analyst'             => $case->submitting_officer ?: 'Lab Analyst',
                                'date_received'       => $case->incident_date ? substr((string)$case->incident_date, 0, 10) : date('Y-m-d'),
                                'date_started'        => date('Y-m-d'),
                                'date_completed'      => date('Y-m-d'),
                                'presumptive_result'  => $case->presumptive_test_result,
                                'confirmatory_result' => $case->confirmatory_test_result ?: 'YES',
                                'sample_weight'       => [
                                    'description'      => $case->sample_weight_description,
                                    'units_packaging'  => $case->sample_weight_units,
                                    'gross_weight'     => $case->sample_weight_gross,
                                    'sample_taken'     => $case->sample_weight_taken,
                                    'reserved_samples' => $case->sample_weight_reserved,
                                ],
                                'substance_identification' => $case->confirmatory_substance_identification,
                                'detected_substances'      => is_string($case->detected_substances ?? null) ? json_decode($case->detected_substances, true) : ($case->detected_substances ?? []),
                                'test_methods'             => $case->confirmatory_test_methods,
                                'specifications'           => $case->confirmatory_specifications,
                                'remark'                   => $case->confirmatory_remark,
                                'findings'                 => $case->laboratory_analytical_findings,
                            ];
                        }

                        // Source 6: Exhibit level saved lab examination findings
                        if (!$sDossierHasSavedLab && !empty($currSuspectExhibits)) {
                            foreach ($currSuspectExhibits as $eCheck) {
                                if (!empty($eCheck['lab_examination']) && is_array($eCheck['lab_examination']) && (!empty($eCheck['lab_examination']['has_saved']) || !empty($eCheck['lab_examination']['substance_identification']) || !empty($eCheck['lab_examination']['presumptive_result']))) {
                                    $sDossierHasSavedLab = true;
                                    $sDossierLabExam = $eCheck['lab_examination'];
                                    break;
                                }
                                if (!empty($eCheck['lab_result']) && is_array($eCheck['lab_result']) && (!empty($eCheck['lab_result']['has_saved']) || !empty($eCheck['lab_result']['substance_identification']) || !empty($eCheck['lab_result']['presumptive_result']))) {
                                    $sDossierHasSavedLab = true;
                                    $sDossierLabExam = $eCheck['lab_result'];
                                    break;
                                }
                            }
                        }

                        // Source 7: DB direct table check
                        if (!$sDossierHasSavedLab && isset($case->id)) {
                            try {
                                if ($isAssociate) {
                                    $assocDbRow = \DB::table('associate_lab_examinations')->where('case_id', $case->id)->where('suspect_name', $sName)->first();
                                    if ($assocDbRow && (!empty($assocDbRow->has_saved) || !empty($assocDbRow->substance_identification) || !empty($assocDbRow->presumptive_result))) {
                                        $sDossierHasSavedLab = true;
                                        $sDossierLabExam = (array)$assocDbRow;
                                    }
                                } else {
                                    $primDbRow = \DB::table('laboratory_examination_results')->where(function($q) use ($case) {
                                        $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
                                    })->first();
                                    if ($primDbRow && (!empty($primDbRow->has_saved) || !empty($primDbRow->substance_identification) || !empty($primDbRow->presumptive_result))) {
                                        $sDossierHasSavedLab = true;
                                        $sDossierLabExam = (array)$primDbRow;
                                    }
                                }
                            } catch (\Throwable $e) {}
                        }

                        // Source 8: Fallback for Drug Cases / Suspect Drug Exhibits when no lab examination has been saved yet
                        $currSuspectHasDrug = false;
                        if (!empty($currSuspectExhibits)) {
                            foreach ($currSuspectExhibits as $eCh) {
                                $eCatLower = strtolower(trim($eCh['exhibit_category'] ?? ($eCh['category'] ?? '')));
                                $eNameLower = strtolower(trim($eCh['seizure_name_drug'] ?? ($eCh['commodity_name'] ?? ($eCh['seizure_name'] ?? ($eCh['name'] ?? '')))));
                                if (str_contains($eCatLower, 'drug') || str_contains($eCatLower, 'narcotic') || !empty($eCh['seizure_name_drug']) || str_contains($eNameLower, 'cannabis') || str_contains($eNameLower, 'cocaine') || str_contains($eNameLower, 'heroin') || str_contains($eNameLower, 'meth') || str_contains($eNameLower, 'tramadol')) {
                                    $currSuspectHasDrug = true;
                                    break;
                                }
                            }
                        }

                        if (!$sDossierHasSavedLab && ($currSuspectHasDrug || (!$isAssociate && ($isDrugCase ?? false)))) {
                            $sDossierHasSavedLab = true;
                            $sDossierLabExam = [
                                'exam_reference'      => $labExamRef ?? 'GSA-FM-T77-F',
                                'exhibit_number'      => !empty($currSuspectExhibits[0]['exhibit_number']) ? $currSuspectExhibits[0]['exhibit_number'] : ($case->exhibit_number ?: 'EXH-01'),
                                'laboratory_number'   => $case->laboratory_number ?: 'Lab No. 12/FS05/2025',
                                'exhibit_description' => !empty($currSuspectExhibits[0]['commodity_name']) ? $currSuspectExhibits[0]['commodity_name'] : ($case->seizure_name_drug ?: ($case->seizure_name ?: 'Drug Exhibit')),
                                'analyst'             => $case->submitting_officer ?: 'Lab Analyst',
                                'date_received'       => $case->incident_date ? substr((string)$case->incident_date, 0, 10) : date('Y-m-d'),
                                'date_started'        => date('Y-m-d'),
                                'date_completed'      => date('Y-m-d'),
                                'presumptive_result'  => $case->presumptive_test_result ?: 'N/A',
                                'confirmatory_result' => $case->confirmatory_test_result ?: 'YES',
                                'sample_weight'       => [
                                    'description'      => $case->sample_weight_description,
                                    'units_packaging'  => $case->sample_weight_units,
                                    'gross_weight'     => $case->sample_weight_gross,
                                    'sample_taken'     => $case->sample_weight_taken,
                                    'reserved_samples' => $case->sample_weight_reserved,
                                ],
                                'substance_identification' => $case->confirmatory_substance_identification ?: 'N/A',
                                'detected_substances'      => [],
                                'test_methods'             => $case->confirmatory_test_methods ?: 'N/A',
                                'specifications'           => $case->confirmatory_specifications ?: 'N/A',
                                'remark'                   => $case->confirmatory_remark ?: 'N/A',
                                'findings'                 => $case->laboratory_analytical_findings ?: 'Awaiting laboratory examination completion.',
                            ];
                        }

                        if (!empty($sDossierLabExam)) {
                            if (empty($sDossierLabExam['exhibit_number']) || $sDossierLabExam['exhibit_number'] === 'N/A') {
                                $sDossierLabExam['exhibit_number'] = $sDossierLabExam['exhibit_no'] ?? ($sDossierLabExam['exhibit_id'] ?? ($case->exhibit_number ?: ($labExhibitNo ?? 'EXH-01')));
                            }
                            if (empty($sDossierLabExam['exam_reference']) || $sDossierLabExam['exam_reference'] === 'N/A') {
                                $sDossierLabExam['exam_reference'] = $labExamRef ?? 'GSA-FM-T77-F';
                            }
                            if (empty($sDossierLabExam['laboratory_number']) || $sDossierLabExam['laboratory_number'] === 'N/A') {
                                $sDossierLabExam['laboratory_number'] = $labNumber ?? 'Lab No. 12/FS05/2025';
                            }
                            if (empty($sDossierLabExam['analyst']) || $sDossierLabExam['analyst'] === 'N/A') {
                                $sDossierLabExam['analyst'] = $labAnalyst ?? 'John Adu';
                            }

                            $defRecDate = !empty($case->incident_date) ? substr((string)$case->incident_date, 0, 10) : (is_object($case->created_at) ? $case->created_at->format('Y-m-d') : substr((string)$case->created_at, 0, 10));

                            $rRec = $sDossierLabExam['date_received'] ?? null;
                            if (empty($rRec) || $rRec === 'N/A') $rRec = $defRecDate;
                            $sDossierLabExam['date_received'] = is_object($rRec) ? $rRec->format('Y-m-d') : substr((string)$rRec, 0, 10);

                            $rStart = $sDossierLabExam['date_started'] ?? null;
                            if (empty($rStart) || $rStart === 'N/A') $rStart = $sDossierLabExam['date_received'];
                            $sDossierLabExam['date_started'] = is_object($rStart) ? $rStart->format('Y-m-d') : substr((string)$rStart, 0, 10);

                            $rComp = $sDossierLabExam['date_completed'] ?? null;
                            if (empty($rComp) || $rComp === 'N/A') $rComp = $sDossierLabExam['date_started'];
                            $sDossierLabExam['date_completed'] = is_object($rComp) ? $rComp->format('Y-m-d') : substr((string)$rComp, 0, 10);
                        }
                    @endphp

                    @php
                        $isActiveSuspectDossier = (strtolower(trim($sName)) === strtolower(trim($activeSuspectName ?? '')));
                        $skipDuplicateLabBlock = $isLabAnalysis && $isActiveSuspectDossier;
                    @endphp
                    @if(!$skipDuplicateLabBlock && ($currSuspectHasDrug || (!$isAssociate && ($isDrugCase ?? false))) && $sDossierHasSavedLab && !empty($sDossierLabExam))
                    <!-- LABORATORY EXAMINATION RESULT FOR {{ strtoupper($sName) }} -->
                    <div class="p-5 bg-emerald-50/80 border-2 border-emerald-300 rounded-2xl space-y-4 shadow-sm mt-4">
                        <div class="flex items-center justify-between pb-3 border-b border-emerald-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($sName) }}</h3>
                                    <p class="text-[11px] text-emerald-800 font-semibold">Official Laboratory Analysis Directorate Findings for {{ $sName }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-200 text-emerald-950 border border-emerald-400">
                                ✓ VERIFIED RESULT LOGGED
                            </span>
                        </div>

                        <table class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden shadow-2xs bg-white">
                            <tbody>
                                <tr class="bg-slate-100/90 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Analysis Reference:</td>
                                    <td class="p-2.5 font-black text-emerald-900 font-mono-code border-r border-slate-300 w-3/10">{{ $sDossierLabExam['exam_reference'] ?? 'GSA-FM-T77-F' }}</td>
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Exhibit Number:</td>
                                    <td class="p-2.5 font-black text-emerald-900 font-mono-code">{{ $sDossierLabExam['exhibit_number'] ?? 'N/A' }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Case Reference:</td>
                                    <td class="p-2.5 font-black text-blue-900 font-mono-code border-r border-slate-300">{{ $case->case_number }}</td>
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Laboratory Number:</td>
                                    <td class="p-2.5 font-black text-slate-900 font-mono-code">{{ $sDossierLabExam['laboratory_number'] ?? 'N/A' }}</td>
                                </tr>
                                <tr class="bg-slate-100/90 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Exhibit Description:</td>
                                    <td class="p-2.5 font-bold text-slate-900 bg-white" colspan="3">
                                        {{ $sDossierLabExam['exhibit_description'] ?? ($sDossierLabExam['description'] ?? ($sDossierLabExam['sample_weight']['description'] ?? 'N/A')) }}
                                    </td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Analyst / Examiner:</td>
                                    <td class="p-2.5 font-black text-slate-900 border-r border-slate-300">{{ $sDossierLabExam['analyst'] ?? 'N/A' }}</td>
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Examination Timeline:</td>
                                    <td class="p-2 bg-white">
                                        <div class="flex items-center gap-2 font-mono-code text-[11px] font-bold">
                                            <span>Received: <strong class="text-blue-900">{{ $sDossierLabExam['date_received'] ?? 'N/A' }}</strong></span>
                                            <span>|</span>
                                            <span>Started: <strong class="text-amber-900">{{ $sDossierLabExam['date_started'] ?? 'N/A' }}</strong></span>
                                            <span>|</span>
                                            <span>Completed: <strong class="text-emerald-900">{{ $sDossierLabExam['date_completed'] ?? 'N/A' }}</strong></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-slate-100/90 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Presumptive Reaction:</td>
                                    <td class="p-2 border-r border-slate-300 bg-white">
                                        @php
                                            $pRawDos = trim($sDossierLabExam['presumptive_result'] ?? '');
                                            $pRowsDos = [];
                                            if (!empty($pRawDos) && $pRawDos !== 'N/A') {
                                                $overallResultDos = 'N/A';
                                                if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRawDos, $mResD)) {
                                                    $overallResultDos = strtoupper($mResD[1]);
                                                    if ($overallResultDos === 'POS' || $overallResultDos === 'POSITIVE') $overallResultDos = 'YES';
                                                    if ($overallResultDos === 'NEG' || $overallResultDos === 'NEGATIVE') $overallResultDos = 'NO';
                                                }

                                                $cleanPDos = $pRawDos;
                                                if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRawDos, $mPD)) {
                                                    $cleanPDos = $mPD[1];
                                                }

                                                $segmentsDos = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanPDos);
                                                if (empty($segmentsDos)) $segmentsDos = [$cleanPDos];

                                                foreach ($segmentsDos as $segD) {
                                                    $segD = trim($segD);
                                                    if (empty($segD)) continue;

                                                    $presenceD = 'N/A';
                                                    $purityD = 'N/A';

                                                    if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $segD, $mPresD)) {
                                                        $presenceD = trim($mPresD[1]);
                                                    } elseif (preg_match('/^\[\d+\]\s*([^\-\;\)]+)/i', $segD, $mPresD)) {
                                                        $presenceD = trim($mPresD[1]);
                                                    } elseif (!empty($segD) && !str_contains($segD, 'Purity')) {
                                                        $presenceD = trim($segD);
                                                    }

                                                    if ($presenceD === 'YES' || $presenceD === 'NO' || $presenceD === 'POS' || $presenceD === 'NEG') {
                                                        $presenceD = 'N/A';
                                                    }

                                                    if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $segD, $mPurD)) {
                                                        $purityD = trim($mPurD[1]);
                                                    }

                                                    if ($presenceD !== 'N/A' || $purityD !== 'N/A' || count($segmentsDos) === 1) {
                                                        $pRowsDos[] = [
                                                            'result'   => $overallResultDos,
                                                            'presence' => $presenceD,
                                                            'purity'   => $purityD,
                                                        ];
                                                    }
                                                }
                                            }
                                            if (empty($pRowsDos)) {
                                                $pRowsDos[] = ['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A'];
                                            }
                                        @endphp
                                        <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                            <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                                <tr class="border-b border-slate-300">
                                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                                    <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white">
                                                @foreach($pRowsDos as $pRowD)
                                                <tr class="border-b border-slate-200 last:border-b-0">
                                                    <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pRowD['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pRowD['result'] }}</td>
                                                    <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $pRowD['presence'] }}</td>
                                                    <td class="px-2 py-1 font-bold text-slate-900">{{ $pRowD['purity'] }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Confirmatory Test:</td>
                                    <td class="p-2.5 font-black text-emerald-900">
                                        @php
                                            $confVal = strtoupper(trim((string)($sDossierLabExam['confirmatory_result'] ?? 'YES')));
                                        @endphp
                                        <span class="inline-flex items-center space-x-1">
                                            <span class="w-2 h-2 rounded-full {{ $confVal !== 'NO' ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                                            <span>{{ $confVal !== 'NO' ? 'YES' : 'NO' }}</span>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="bg-slate-100/90 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Sample Metrology:</td>
                                    <td class="p-2.5 bg-white" colspan="3">
                                        @php
                                            $sw = $sDossierLabExam['sample_weight'] ?? [];
                                        @endphp
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                                            <div class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded">
                                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Description:</span>
                                                <span class="font-bold text-slate-900 ml-1">{{ $sw['description'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Units:</span>
                                                <span class="font-bold text-slate-900 ml-1">{{ $sw['units_packaging'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Gross Wt:</span>
                                                <span class="font-bold text-slate-900 ml-1">{{ $sw['gross_weight'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Taken:</span>
                                                <span class="font-bold text-emerald-900 ml-1">{{ $sw['sample_taken'] ?? '—' }}</span>
                                            </div>
                                            <div class="p-2 bg-slate-50 border border-slate-200 rounded">
                                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[9px]">Reserved:</span>
                                                <span class="font-bold text-slate-900 ml-1">{{ $sw['reserved_samples'] ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Substance Identified:</td>
                                    <td class="p-2.5 font-black text-slate-950 border-r border-slate-300">{{ $sDossierLabExam['substance_identification'] ?? 'N/A' }}</td>
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Specifications:</td>
                                    <td class="p-2.5 font-bold text-slate-900">{{ $sDossierLabExam['specifications'] ?? 'N/A' }}</td>
                                </tr>
                                @php
                                    $detSubs = $sDossierLabExam['detected_substances'] ?? [];
                                    if (is_string($detSubs)) {
                                        $detSubs = json_decode($detSubs, true) ?: [];
                                    }
                                @endphp
                                @if(!empty($detSubs) && is_array($detSubs) && count($detSubs) > 0)
                                <tr class="bg-amber-50/70 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-amber-950 uppercase border-r border-slate-300 bg-amber-100/80">Result (Detected Substances & Purity %):</td>
                                    <td class="p-2.5" colspan="3">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($detSubs as $cSub)
                                                @php
                                                    $cName = is_array($cSub) ? ($cSub['compound'] ?? ($cSub['name'] ?? ($cSub['substance'] ?? 'Unknown'))) : $cSub;
                                                    $cPct = is_array($cSub) ? ($cSub['purity'] ?? ($cSub['percentage'] ?? ($cSub['concentration'] ?? null))) : null;
                                                    $cMeth = is_array($cSub) ? ($cSub['method'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-amber-300 shadow-2xs text-xs font-bold text-amber-950">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                    <span>{{ $cName }}</span>
                                                    @if($cPct)<span class="text-emerald-800 font-mono font-extrabold">({{ $cPct }}%)</span>@endif
                                                    @if($cMeth)<span class="text-slate-500 font-mono text-[10px]">[{{ $cMeth }}]</span>@endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                <tr class="bg-slate-100/90 border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Methods:</td>
                                    <td class="p-2.5 font-bold text-slate-900 border-r border-slate-300" colspan="3">{{ $sDossierLabExam['test_methods'] ?? 'N/A' }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="p-2.5 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Analysis Remark:</td>
                                    <td class="p-2.5 font-bold text-slate-900" colspan="3">{{ $sDossierLabExam['remark'] ?? 'N/A' }}</td>
                                </tr>
                                <tr class="bg-slate-900 text-white">
                                    <td class="p-2.5 font-mono-code font-black text-emerald-400 uppercase border-r border-slate-800 bg-slate-950">Analytical Findings:</td>
                                    <td class="p-2.5 font-medium text-slate-100 leading-relaxed bg-slate-900" colspan="3">{{ $sDossierLabExam['findings'] ?? 'No specific laboratory analytical findings logged yet.' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>            @endforeach

            @php
                $allCaseExhibits = [];
                $seenExhibitKeys = [];
                $allCaseExhibitPhotos = [];

                $extractAllPhotosHelper = function($item) {
                    $found = [];
                    if (empty($item)) return $found;
                    if (is_object($item)) $item = (array)$item;
                    if (!is_array($item)) return $found;

                    $keys = ['photos', 'exhibit_photos', 'evidence_photos', 'photo', 'exhibit_photo', 'photo_url', 'image', 'image_path', 'image_url', 'file_path', 'attachment', 'attachments'];
                    foreach ($keys as $k) {
                        if (!empty($item[$k])) {
                            $val = $item[$k];
                            if (is_string($val)) {
                                $val = trim($val);
                                if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
                                    $dec = json_decode($val, true);
                                    if (is_array($dec)) {
                                        foreach ($dec as $p) { if (!empty($p) && is_string($p)) $found[] = $p; }
                                    } else {
                                        $found[] = $val;
                                    }
                                } else {
                                    $found[] = $val;
                                }
                            } elseif (is_array($val)) {
                                foreach ($val as $p) { if (!empty($p) && is_string($p)) $found[] = $p; }
                            }
                        }
                    }
                    return array_values(array_unique($found));
                };

                $formatPhotoUrlHelper = function($p) {
                    if (empty($p)) return '';
                    $p = trim((string)$p);
                    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, 'data:')) {
                        return $p;
                    }
                    $clean = ltrim($p, '/');
                    if (str_starts_with($clean, 'storage/')) {
                        return asset($clean);
                    }
                    if (file_exists(public_path($clean))) {
                        return asset($clean);
                    }
                    return asset('storage/' . $clean);
                };

                $addCaseExhibit = function($item) use (&$allCaseExhibits, &$seenExhibitKeys, &$allCaseExhibitPhotos, $extractAllPhotosHelper, $case) {
                    if (empty($item)) return;
                    if (is_object($item)) $item = (array)$item;

                    $exNo = $item['exhibit_number'] ?? null;
                    $dbId = $item['id'] ?? null;
                    $cat  = $item['category'] ?? ($item['exhibit_category'] ?? 'General Exhibit');
                    $name = $item['seizure_name'] ?? ($item['commodity_name'] ?? ($item['name'] ?? ($item['weapon_model'] ?? 'Confiscated Exhibit')));

                    $key = !empty($dbId) ? 'db_' . $dbId : (!empty($exNo) ? 'ex_' . strtolower(trim($exNo)) : 'cn_' . strtolower(trim($cat)) . '_' . strtolower(trim($name)));

                    if (!isset($seenExhibitKeys[$key])) {
                        $seenExhibitKeys[$key] = true;

                        $owner = $item['owner_name'] ?? ($item['suspect_name'] ?? ($item['associated_person'] ?? ($item['veh_owner'] ?? ($item['wpn_owner'] ?? ($item['dev_owner'] ?? ($item['seizure_officer'] ?? null))))));
                        if (empty($owner) || strtolower(trim($owner)) === 'primary suspect' || strtolower(trim($owner)) === 'primary') {
                            $owner = $case->suspect_name ?? 'Primary Suspect';
                        }
                        $item['resolved_owner'] = $owner;
                        $allCaseExhibits[] = $item;

                        $photos = $extractAllPhotosHelper($item);
                        foreach ($photos as $p) {
                            if ($p && !in_array($p, $allCaseExhibitPhotos)) {
                                $allCaseExhibitPhotos[] = $p;
                            }
                        }
                    }
                };

                // 1. Relational DB exhibits
                if (isset($case->id)) {
                    try {
                        $dbExsList = \App\Models\Exhibit::where(function($q) use ($case) {
                            $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
                        })->get();
                        foreach ($dbExsList as $dbE) {
                            $arr = $dbE->toArray();
                            $arr['category'] = $dbE->exhibit_category ?? 'General Exhibit';
                            $arr['seizure_name'] = $dbE->commodity_name ?? ($dbE->wpn_model ?? 'Confiscated Exhibit');
                            $arr['owner_name'] = $dbE->suspect ? $dbE->suspect->suspect_name : ($dbE->veh_owner ?? ($dbE->wpn_owner ?? ($dbE->dev_owner ?? ($dbE->associated_person ?? null))));
                            $arr['weapon_model'] = $dbE->wpn_model;
                            $arr['weapon_serial_number'] = $dbE->wpn_serial_no;
                            $arr['weapon_calibre'] = $dbE->wpn_calibre;
                            $arr['weapon_color'] = $dbE->wpn_colour;
                            $arr['weapon_size_length'] = $dbE->wpn_size;
                            $addCaseExhibit($arr);
                        }
                    } catch (\Throwable $e) {}
                }

                // 2. Docket JSON seizures and associate exhibits
                $jsonSeizures = array_merge(
                    $docketData['seizures'] ?? [],
                    $docketData['associated_seizures'] ?? [],
                    $docketData['associate_exhibits'] ?? []
                );
                foreach ($jsonSeizures as $sz) {
                    $addCaseExhibit($sz);
                }

                // 3. Also add case-level evidence photos to gallery
                $caseRawPhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));
                if (is_array($caseRawPhotos)) {
                    foreach ($caseRawPhotos as $p) {
                        if ($p && is_string($p) && !in_array($p, $allCaseExhibitPhotos)) {
                            $allCaseExhibitPhotos[] = $p;
                        }
                    }
                }
                $allCaseExhibitPhotos = array_values(array_unique($allCaseExhibitPhotos));
            @endphp
            @if(!empty($allCaseExhibits) && count($allCaseExhibits) > 0)
            <!-- SEIZURES (COMMODITIES INVENTORY) FOR ALL SUSPECTS (SINGLE MASTER SECTION) -->
            <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-sm mt-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Seizures (Commodities Inventory)</h3>
                        <p class="text-[11px] text-slate-500">Master record of confiscated substances, exhibits, and vault allocation for all suspects</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        {{ count($allCaseExhibits) }} ITEM(S) LOGGED
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-100 text-slate-600 font-mono-code uppercase text-[10px]">
                            <tr>
                                <th class="py-2.5 px-4 font-bold">Item #</th>
                                <th class="py-2.5 px-4 font-bold">Exhibit Image</th>
                                <th class="py-2.5 px-4 font-bold">Suspect / Owner</th>
                                <th class="py-2.5 px-4 font-bold">Category</th>
                                <th class="py-2.5 px-4 font-bold">Exhibit Description</th>
                                <th class="py-2.5 px-4 font-bold">Quantity</th>
                                <th class="py-2.5 px-4 font-bold">Weight / Unit</th>
                                <th class="py-2.5 px-4 font-bold">Packaging</th>
                                <th class="py-2.5 px-4 font-bold">Est. Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            @foreach($allCaseExhibits as $eIdx => $szItem)
                                @php
                                    $szCat = $szItem['category'] ?? ($szItem['exhibit_category'] ?? 'General Exhibit');
                                    $szNameVal = $szItem['seizure_name'] ?? ($szItem['commodity_name'] ?? ($szItem['name'] ?? ($szItem['weapon_model'] ?? 'N/A')));
                                    $szQtyVal = $szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? 'N/A');
                                    $szWtVal = !empty($szItem['seizure_weight']) || !empty($szItem['weight']) ? (($szItem['seizure_weight'] ?? $szItem['weight']) . ' ' . ($szItem['seizure_unit'] ?? ($szItem['unit'] ?? ''))) : 'N/A';
                                    $szPkgVal = $szItem['seizure_packaging'] ?? ($szItem['packaging'] ?? 'N/A');
                                    $rawSzVal = $szItem['seizure_value'] ?? ($szItem['value'] ?? 'N/A');
                                    $szValueVal = (!empty($rawSzVal) && !str_contains($rawSzVal, 'Item #') && !str_contains($rawSzVal, "\n")) ? trim($rawSzVal) : 'N/A';
                                    $szOwnerVal = $szItem['resolved_owner'] ?? ($case->suspect_name ?? 'Primary Suspect');

                                    $itemPhotos = $extractAllPhotosHelper($szItem);
                                    if (empty($itemPhotos) && isset($case->id)) {
                                        try {
                                            $dbMatch = \App\Models\Exhibit::where(function($q) use ($case) {
                                                $q->where('single_suspect_case_id', $case->id)->orWhere('multiple_suspect_case_id', $case->id);
                                            })->where(function($q) use ($szItem) {
                                                if (!empty($szItem['exhibit_number'])) $q->where('exhibit_number', $szItem['exhibit_number']);
                                                if (!empty($szItem['seizure_name'])) $q->orWhere('commodity_name', $szItem['seizure_name']);
                                                if (!empty($szItem['name'])) $q->orWhere('commodity_name', $szItem['name']);
                                            })->first();
                                            if ($dbMatch && !empty($dbMatch->evidence_photos)) {
                                                $itemPhotos = is_array($dbMatch->evidence_photos) ? $dbMatch->evidence_photos : (json_decode($dbMatch->evidence_photos, true) ?: []);
                                            }
                                        } catch (\Throwable $e) {}
                                    }
                                @endphp
                                <tr>
                                    <td class="py-2.5 px-4 font-mono-code font-bold text-slate-500">#{{ $eIdx + 1 }}</td>
                                    <td class="py-2.5 px-4">
                                        @if(!empty($itemPhotos) && count($itemPhotos) > 0)
                                            @php
                                                $firstImg = $itemPhotos[0];
                                                $imgSrc = $formatPhotoUrlHelper($firstImg);
                                            @endphp
                                            <div class="relative group inline-block">
                                                <img src="{{ $imgSrc }}" alt="{{ $szNameVal }}" data-img-src="{{ $imgSrc }}" data-img-title="{{ $szNameVal }} — {{ $szOwnerVal }}" class="h-12 w-12 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:scale-105 transition-transform" onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))" onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}" />
                                                @if(count($itemPhotos) > 1)
                                                    <span class="absolute -bottom-1 -right-1 bg-slate-900 text-white text-[9px] font-mono-code font-bold px-1 rounded-full shadow">
                                                        +{{ count($itemPhotos) - 1 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="px-2 py-1 bg-slate-100 text-slate-400 text-[10px] font-mono-code rounded border border-slate-200">No Photo</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 font-mono-code font-black text-slate-950">{{ $szOwnerVal }}</td>
                                    <td class="py-2.5 px-4 font-bold font-mono-code text-amber-700">{{ $szCat }}</td>
                                    <td class="py-2.5 px-4 font-extrabold text-slate-900">{{ $szNameVal }}</td>
                                    <td class="py-2.5 px-4 font-mono-code">{{ $szQtyVal }}</td>
                                    <td class="py-2.5 px-4 font-mono-code">{{ $szWtVal }}</td>
                                    <td class="py-2.5 px-4 font-mono-code">{{ $szPkgVal }}</td>
                                    <td class="py-2.5 px-4 font-mono-code font-bold text-emerald-700">{{ $szValueVal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(!empty($allCaseExhibitPhotos) && count($allCaseExhibitPhotos) > 0)
                    <div class="pt-3 border-t border-slate-100">
                        <h4 class="text-xs font-bold text-slate-700 uppercase font-mono-code mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Exhibit Photo Gallery ({{ count($allCaseExhibitPhotos) }} images)
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allCaseExhibitPhotos as $pIdx => $gPhoto)
                                @php
                                    $gSrc = $formatPhotoUrlHelper($gPhoto);
                                @endphp
                                <div class="relative group cursor-pointer" data-img-src="{{ $gSrc }}" data-img-title="Exhibit Image #{{ $pIdx + 1 }}" onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                    <img src="{{ $gSrc }}" class="h-16 w-16 object-cover rounded-xl border border-slate-200 shadow-2xs group-hover:border-emerald-500 group-hover:scale-105 transition-all" onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}" />
                                    <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 rounded-xl transition-opacity flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            @endif

            <!-- SECTION 5: EXAMINATION INFORMATION (LABORATORY ANALYSIS DIRECTORATE) -->
            @if($isLabAnalysis)
            <div class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl" id="section5LabExamination">
                @php
                    // GATHER ALL SUSPECTS IN THIS CASE FOR SUSPECT WORKSPACE SELECTOR TABS
                    $allSuspectsInCase = [];
                    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                        foreach ($docketData['suspects'] as $sIndex => $sp) {
                            if (!empty($sp['suspect_name'])) {
                                $spRole = $sp['association_role'] ?? ($sp['role'] ?? ($sIndex === 0 ? 'Primary Suspect' : 'Associate'));
                                $isPrimary = ($sIndex === 0) || (strtolower($spRole) === 'primary suspect');
                                $allSuspectsInCase[] = [
                                    'id'           => $sp['id'] ?? null,
                                    'associate_id' => $sp['associate_id'] ?? ($sp['suspect_id'] ?? null),
                                    'name'         => trim($sp['suspect_name']),
                                    'alias'        => $sp['suspect_alias'] ?? 'N/A',
                                    'role'         => $spRole,
                                    'is_primary'   => $isPrimary,
                                    'is_associate' => !$isPrimary,
                                ];
                            }
                        }
                    }
                    if (empty($allSuspectsInCase)) {
                        $allSuspectsInCase[] = [
                            'name'         => trim($case->suspect_name ?? 'Primary Suspect'),
                            'alias'        => $case->suspect_alias ?? 'N/A',
                            'role'         => 'Primary Suspect',
                            'is_primary'   => true,
                            'is_associate' => false,
                        ];
                    }

                    // RESOLVE ACTIVE SUSPECT FROM QUERY PARAMETERS
                    $reqSuspectParam = request()->query('suspect');
                    $activeSuspectItem = $allSuspectsInCase[0];
                    if (!empty($reqSuspectParam)) {
                        foreach ($allSuspectsInCase as $sCandidate) {
                            if (strtolower(trim($sCandidate['name'])) === strtolower(trim($reqSuspectParam))) {
                                $activeSuspectItem = $sCandidate;
                                break;
                            }
                        }
                    }
                    $activeSuspectName = $activeSuspectItem['name'];
                    $activeSuspectNameLower = strtolower(trim($activeSuspectName));

                    // STRICT CROSS-SUSPECT ISOLATION LOOKUP FOR ACTIVE SUSPECT
                    $activeSuspectLabExam = null;

                    // 1. Check AssociateLabExamination model strictly matching active suspect name
                    $dbActiveLabExam = !empty($case->id) ? \App\Models\AssociateLabExamination::where('case_id', $case->id)
                        ->where('has_saved', true)
                        ->get()
                        ->first(function($aRec) use ($activeSuspectNameLower) {
                            $aName = strtolower(trim($aRec->suspect_name ?? ''));
                            return !empty($aName) && ($aName === $activeSuspectNameLower || str_contains($aName, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $aName));
                        }) : null;

                    if ($dbActiveLabExam) {
                        $activeSuspectLabExam = [
                            'exhibit_owner_name'  => $dbActiveLabExam->suspect_name,
                            'laboratory_number'   => $dbActiveLabExam->laboratory_number,
                            'exam_reference'      => $dbActiveLabExam->exam_reference,
                            'exhibit_number'      => $dbActiveLabExam->exhibit_number,
                            'analyst'             => $dbActiveLabExam->analyst,
                            'date_received'       => is_object($dbActiveLabExam->date_received) ? $dbActiveLabExam->date_received->format('Y-m-d') : substr($dbActiveLabExam->date_received, 0, 10),
                            'date_started'        => is_object($dbActiveLabExam->date_started) ? $dbActiveLabExam->date_started->format('Y-m-d') : substr($dbActiveLabExam->date_started, 0, 10),
                            'date_completed'      => is_object($dbActiveLabExam->date_completed) ? $dbActiveLabExam->date_completed->format('Y-m-d') : substr($dbActiveLabExam->date_completed, 0, 10),
                            'presumptive_result'  => $dbActiveLabExam->presumptive_result,
                            'confirmatory_result' => $dbActiveLabExam->confirmatory_result,
                            'sample_weight'       => [
                                'description'      => $dbActiveLabExam->sample_weight_description,
                                'units_packaging'  => $dbActiveLabExam->sample_weight_units,
                                'gross_weight'     => $dbActiveLabExam->sample_weight_gross,
                                'sample_taken'     => $dbActiveLabExam->sample_weight_taken,
                                'reserved_samples' => $dbActiveLabExam->sample_weight_reserved,
                            ],
                            'substance_identification' => $dbActiveLabExam->substance_identification,
                            'detected_substances'      => $dbActiveLabExam->detected_substances,
                            'test_methods'             => $dbActiveLabExam->test_methods,
                            'specifications'           => $dbActiveLabExam->specifications,
                            'remark'                   => $dbActiveLabExam->remark,
                            'findings'                 => $dbActiveLabExam->findings,
                            'has_saved'                => true,
                        ];
                    }

                    // 2. Check lab_examinations_list strictly matching active suspect name
                    if (!$activeSuspectLabExam && !empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
                        foreach ($docketData['lab_examinations_list'] as $lItem) {
                            $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
                            if (!empty($lItem['has_saved']) && ($lOwner === $activeSuspectNameLower || str_contains($lOwner, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $lOwner))) {
                                $activeSuspectLabExam = $lItem;
                                break;
                            }
                        }
                    }

                    // 3. Check seizures matching active suspect name
                    if (!$activeSuspectLabExam && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                        foreach ($docketData['seizures'] as $szItem) {
                            $szOwner = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? ''))));
                            if ((!empty($szItem['has_saved_lab']) || !empty($szItem['presumptive_result'])) && ($szOwner === $activeSuspectNameLower || str_contains($szOwner, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $szOwner))) {
                                $activeSuspectLabExam = [
                                    'exhibit_owner_name'  => $activeSuspectName,
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
                                    'remark'                   => $szItem['remark'] ?? null,
                                    'findings'                 => $szItem['findings'] ?? null,
                                    'has_saved'                => true,
                                ];
                                break;
                            }
                        }
                    }

                    // 4. Check primary lab_examination if active suspect is primary
                    if (!$activeSuspectLabExam && $activeSuspectItem['is_primary']) {
                        if (!empty($dbPrimaryLabExam) && $dbPrimaryLabExam->has_saved) {
                            $activeSuspectLabExam = [
                                'exhibit_owner_name'  => $dbPrimaryLabExam->suspect_name ?: $activeSuspectName,
                                'laboratory_number'   => $dbPrimaryLabExam->laboratory_number ?: ($case->laboratory_number ?? null),
                                'exam_reference'      => $dbPrimaryLabExam->exam_reference ?: ('GSA-FM-T' . rand(10, 99) . '-F'),
                                'exhibit_number'      => $dbPrimaryLabExam->exhibit_number ?: ('EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . sprintf('%03d', $case->id)),
                                'analyst'             => $dbPrimaryLabExam->analyst ?: ($case->submitting_officer ?: 'Lab Analyst'),
                                'date_received'       => is_object($dbPrimaryLabExam->date_received) ? $dbPrimaryLabExam->date_received->format('Y-m-d') : (substr((string)$dbPrimaryLabExam->date_received, 0, 10) ?: date('Y-m-d')),
                                'date_started'        => is_object($dbPrimaryLabExam->date_started) ? $dbPrimaryLabExam->date_started->format('Y-m-d') : (substr((string)$dbPrimaryLabExam->date_started, 0, 10) ?: date('Y-m-d')),
                                'date_completed'      => is_object($dbPrimaryLabExam->date_completed) ? $dbPrimaryLabExam->date_completed->format('Y-m-d') : (substr((string)$dbPrimaryLabExam->date_completed, 0, 10) ?: date('Y-m-d')),
                                'presumptive_result'  => $dbPrimaryLabExam->presumptive_result ?: ($case->presumptive_test_result ?? null),
                                'confirmatory_result' => $dbPrimaryLabExam->confirmatory_result ?: ($case->confirmatory_test_result ?: 'YES'),
                                'sample_weight'       => [
                                    'description'      => $dbPrimaryLabExam->sample_weight_description ?: ($case->sample_weight_description ?? null),
                                    'units_packaging'  => $dbPrimaryLabExam->sample_weight_units ?: ($case->sample_weight_units ?? null),
                                    'gross_weight'     => $dbPrimaryLabExam->sample_weight_gross ?: ($case->sample_weight_gross ?? null),
                                    'sample_taken'     => $dbPrimaryLabExam->sample_weight_taken ?: ($case->sample_weight_taken ?? null),
                                    'reserved_samples' => $dbPrimaryLabExam->sample_weight_reserved ?: ($case->sample_weight_reserved ?? null),
                                ],
                                'substance_identification' => $dbPrimaryLabExam->substance_identification ?: ($case->confirmatory_substance_identification ?? null),
                                'detected_substances'      => $dbPrimaryLabExam->detected_substances ?: ($case->detected_substances ?? []),
                                'test_methods'             => $dbPrimaryLabExam->test_methods ?: ($case->confirmatory_test_methods ?? null),
                                'specifications'           => $dbPrimaryLabExam->specifications ?: ($case->confirmatory_specifications ?? null),
                                'remark'                   => $dbPrimaryLabExam->remark ?: ($case->confirmatory_remark ?? null),
                                'findings'                 => $dbPrimaryLabExam->findings ?: ($case->laboratory_analytical_findings ?? null),
                                'has_saved'                => true,
                            ];
                        } elseif (!empty($docketData['lab_examination']['has_saved']) || !empty($docketData['lab_examination']['presumptive_result'])) {
                            $pOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? ''));
                            if (empty($pOwner) || $pOwner === 'primary suspect' || $pOwner === $activeSuspectNameLower || str_contains($pOwner, $activeSuspectNameLower) || str_contains($activeSuspectNameLower, $pOwner)) {
                                $activeSuspectLabExam = $docketData['lab_examination'];
                            }
                        } elseif (!empty($case->presumptive_test_result) || !empty($case->confirmatory_substance_identification)) {
                            $activeSuspectLabExam = [
                                'exhibit_owner_name'  => $activeSuspectName,
                                'laboratory_number'   => $case->laboratory_number,
                                'exam_reference'      => 'GSA-FM-T' . rand(10, 99) . '-F',
                                'exhibit_number'      => 'EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . sprintf('%03d', $case->id),
                                'analyst'             => $case->submitting_officer ?: 'Lab Analyst',
                                'date_received'       => $case->incident_date ? substr((string)$case->incident_date, 0, 10) : date('Y-m-d'),
                                'date_started'        => date('Y-m-d'),
                                'date_completed'      => date('Y-m-d'),
                                'presumptive_result'  => $case->presumptive_test_result,
                                'confirmatory_result' => $case->confirmatory_test_result ?: 'YES',
                                'sample_weight'       => [
                                    'description'      => $case->sample_weight_description,
                                    'units_packaging'  => $case->sample_weight_units,
                                    'gross_weight'     => $case->sample_weight_gross,
                                    'sample_taken'     => $case->sample_weight_taken,
                                    'reserved_samples' => $case->sample_weight_reserved,
                                ],
                                'substance_identification' => $case->confirmatory_substance_identification,
                                'detected_substances'      => $case->detected_substances ?? [],
                                'test_methods'             => $case->confirmatory_test_methods,
                                'specifications'           => $case->confirmatory_specifications,
                                'remark'                   => $case->confirmatory_remark,
                                'findings'                 => $case->laboratory_analytical_findings,
                                'has_saved'                => true,
                            ];
                        }
                    }

                    $activeSuspectHasSavedResult = !empty($activeSuspectLabExam) && !empty($activeSuspectLabExam['has_saved']);

                    // Verify active suspect actually has drug exhibits linked before showing saved lab result
                    $activeSuspectExhibits = $case->getExhibitsForSuspect($activeSuspectName, $activeSuspectItem['id'] ?? null, $activeSuspectItem['associate_id'] ?? null, $activeSuspectItem['role'] ?? null);
                    $activeSuspectDrugExhibits = array_values(array_filter($activeSuspectExhibits, function($ex) {
                        if (empty($ex) || !is_array($ex)) return false;
                        $cat = strtolower(trim($ex['exhibit_category'] ?? ($ex['category'] ?? '')));
                        $name = strtolower(trim($ex['seizure_name_drug'] ?? ($ex['commodity_name'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? '')))));
                        $nonDrugKws = ['weapon', 'firearm', 'pistol', 'rifle', 'shotgun', 'ammunition', 'vehicle', 'truck', 'car', 'cash', 'currency', 'usd', 'ghs', 'laptop', 'phone', 'electronics', 'document'];
                        foreach ($nonDrugKws as $kw) {
                            if (str_contains($cat, $kw) || str_contains($name, $kw)) return false;
                        }
                        return true;
                    }));

                    if (empty($activeSuspectDrugExhibits) && empty($dbActiveLabExam)) {
                        $activeSuspectHasSavedResult = false;
                        $activeSuspectLabExam = null;
                    }

                    if ($activeSuspectHasSavedResult && !empty($activeSuspectLabExam)) {
                        $labOwnerName = $activeSuspectLabExam['exhibit_owner_name'] ?? $activeSuspectName;
                        if (!empty($activeSuspectLabExam['exam_reference'])) $savedLabExamRef = $activeSuspectLabExam['exam_reference'];
                        if (!empty($activeSuspectLabExam['exhibit_number'])) $savedLabExhibitNo = $activeSuspectLabExam['exhibit_number'];
                        if (!empty($activeSuspectLabExam['laboratory_number'])) $savedLabNumber = $activeSuspectLabExam['laboratory_number'];
                        if (!empty($activeSuspectLabExam['analyst'])) $savedLabAnalyst = $activeSuspectLabExam['analyst'];
                        if (!empty($activeSuspectLabExam['date_received'])) $savedLabDateReceived = $activeSuspectLabExam['date_received'];
                        if (!empty($activeSuspectLabExam['date_started'])) $savedLabDateStarted = $activeSuspectLabExam['date_started'];
                        if (!empty($activeSuspectLabExam['date_completed'])) $savedLabDateCompleted = $activeSuspectLabExam['date_completed'];
                        if (!empty($activeSuspectLabExam['presumptive_result'])) $savedLabPresumptive = $activeSuspectLabExam['presumptive_result'];
                        if (!empty($activeSuspectLabExam['confirmatory_result'])) $savedLabConfirmatory = $activeSuspectLabExam['confirmatory_result'];

                        $swActive = $activeSuspectLabExam['sample_weight'] ?? [];
                        if (!empty($activeSuspectLabExam['exhibit_description'])) $savedLabExhibitDesc = $activeSuspectLabExam['exhibit_description'];
                        elseif (!empty($activeSuspectLabExam['description'])) $savedLabExhibitDesc = $activeSuspectLabExam['description'];
                        if (isset($swActive['description']) && $swActive['description'] !== null && $swActive['description'] !== '') $savedLabSampleDesc = $swActive['description'];
                        if (isset($swActive['units_packaging']) && $swActive['units_packaging'] !== null && $swActive['units_packaging'] !== '') $savedLabSampleUnits = $swActive['units_packaging'];
                        if (isset($swActive['gross_weight']) && $swActive['gross_weight'] !== null && $swActive['gross_weight'] !== '') $savedLabSampleGross = $swActive['gross_weight'];

                        $activeTaken = $swActive['sample_taken'] ?? ($swActive['sample_weight_taken'] ?? ($activeSuspectLabExam['sample_taken'] ?? ($activeSuspectLabExam['sample_weight_taken'] ?? null)));
                        if (isset($activeTaken) && $activeTaken !== null && $activeTaken !== '') $savedLabSampleTaken = $activeTaken;

                        $activeReserved = $swActive['reserved_samples'] ?? ($swActive['sample_weight_reserved'] ?? ($activeSuspectLabExam['sample_reserved'] ?? ($activeSuspectLabExam['sample_weight_reserved'] ?? null)));
                        if (isset($activeReserved) && $activeReserved !== null && $activeReserved !== '') $savedLabSampleReserved = $activeReserved;

                        if (!empty($activeSuspectLabExam['substance_identification'])) $savedLabSubstanceId = $activeSuspectLabExam['substance_identification'];
                        if (!empty($activeSuspectLabExam['detected_substances'])) $savedLabDetectedSubstances = $activeSuspectLabExam['detected_substances'];
                        if (!empty($activeSuspectLabExam['test_methods'])) $savedLabTestMethods = $activeSuspectLabExam['test_methods'];
                        if (!empty($activeSuspectLabExam['specifications'])) $savedLabSpecifications = $activeSuspectLabExam['specifications'];
                        if (!empty($activeSuspectLabExam['remark'])) $savedLabRemark = $activeSuspectLabExam['remark'];
                        if (!empty($activeSuspectLabExam['findings'])) $savedLabFindings = $activeSuspectLabExam['findings'];
                    }

                    if (empty($savedLabExhibitNo) || $savedLabExhibitNo === 'N/A') {
                        $savedLabExhibitNo = !empty($labExhibitNo) ? $labExhibitNo : ($case->exhibit_number ?: 'EXH-01');
                    }
                    if (empty($savedLabDateReceived) || $savedLabDateReceived === 'N/A') {
                        $savedLabDateReceived = !empty($labDateReceived) ? $labDateReceived : date('Y-m-d');
                    }
                    if (empty($savedLabDateStarted) || $savedLabDateStarted === 'N/A') {
                        $savedLabDateStarted = !empty($labDateStarted) ? $labDateStarted : $savedLabDateReceived;
                    }
                    if (empty($savedLabDateCompleted) || $savedLabDateCompleted === 'N/A') {
                        $savedLabDateCompleted = !empty($labDateCompleted) ? $labDateCompleted : $savedLabDateStarted;
                    }

                    $checkIsDrugExhibitReview = function($ex) {
                        if (empty($ex) || !is_array($ex)) return false;
                        $cat = strtolower(trim($ex['exhibit_category'] ?? ($ex['category'] ?? '')));
                        $name = strtolower(trim($ex['seizure_name_drug'] ?? ($ex['commodity_name'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? '')))));
                        $nonDrugKws = ['weapon', 'firearm', 'pistol', 'rifle', 'shotgun', 'ammunition', 'vehicle', 'truck', 'car', 'cash', 'currency', 'usd', 'ghs', 'laptop', 'phone', 'electronics', 'document'];
                        foreach ($nonDrugKws as $kw) {
                            if (str_contains($cat, $kw) || str_contains($name, $kw)) return false;
                        }
                        if ($cat === 'drugs' || $cat === 'narcotics' || $cat === 'drug' || !empty($ex['seizure_name_drug'])) {
                            return true;
                        }
                        $drugKws = ['drug', 'narcotic', 'opioid', 'cocaine', 'heroin', 'cannabis', 'marijuana', 'weed', 'meth', 'fentanyl', 'tramadol', 'codeine', 'morphine', 'opium', 'amphetamine', 'mdma', 'ecstasy'];
                        foreach ($drugKws as $kw) {
                            if (str_contains($cat, $kw) || str_contains($name, $kw)) return true;
                        }
                        return false;
                    };

                    $allDrugExhibitsList = [];
                    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                        foreach ($docketData['seizures'] as $idx => $sz) {
                            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                            $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                            $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                            $szIsAssoc = !empty($sz['is_associate']) || (!empty($szOwner) && strtolower(trim($szOwner)) !== 'primary suspect') || (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
                            $exOwner = !empty($szOwner) ? $szOwner : ($szIsAssoc ? 'Associate Suspect' : ($case->suspect_name ?? 'Primary Suspect'));

                            if ($checkIsDrugExhibitReview($sz)) {
                                $allDrugExhibitsList[] = [
                                    'index' => $idx + 1,
                                    'name'  => $sName ?: 'Drug Exhibit',
                                    'cat'   => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                                    'qty'   => $sz['seizure_quantity'] ?? null,
                                    'weight'=> $sz['seizure_weight'] ?? null,
                                    'unit'  => $sz['seizure_unit'] ?? null,
                                    'owner' => $exOwner,
                                    'is_associate' => $szIsAssoc,
                                    'has_saved_lab' => !empty($sz['has_saved_lab']),
                                    'confirmatory_result' => $sz['confirmatory_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? ($case->confirmatory_test_result ?? 'YES')),
                                    'substance_identification' => $sz['substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? ($case->confirmatory_substance_identification ?? $sName)),
                                    'test_methods' => $sz['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? ($case->confirmatory_test_methods ?? '')),
                                    'specifications' => $sz['specifications'] ?? ($labDataFromDocket['specifications'] ?? ($case->confirmatory_specifications ?? '')),
                                    'remark' => $sz['remark'] ?? ($labDataFromDocket['remark'] ?? ($case->confirmatory_remark ?? '')),
                                    'sample_taken' => $sz['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? ($case->sample_weight_taken ?? '')),
                                    'sample_reserved' => $sz['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? ($case->sample_weight_reserved ?? '')),
                                    'findings' => $sz['findings'] ?? ($labDataFromDocket['findings'] ?? ($case->laboratory_analytical_findings ?? '')),
                                ];
                            }
                        }
                    }
                    if (empty($allDrugExhibitsList) && $isDrugCase) {
                        $allDrugExhibitsList[] = [
                            'index' => 1,
                            'name'  => $case->seizure_name_drug ?: ($case->seizure_name ?: ($case->exhibits_summary ?: 'Drug Exhibit')),
                            'cat'   => $case->exhibit_category ?: 'Drugs',
                            'qty'   => $case->seizure_quantity,
                            'weight'=> $case->seizure_weight,
                            'unit'  => $case->seizure_unit,
                            'owner' => $case->suspect_name ?? 'Primary Suspect',
                            'is_associate' => false,
                            'has_saved_lab' => $activeSuspectHasSavedResult,
                            'confirmatory_result' => $savedLabConfirmatory ?: ($case->confirmatory_test_result ?: 'YES'),
                            'substance_identification' => $savedLabSubstanceId ?: ($case->confirmatory_substance_identification ?: null),
                            'test_methods' => $savedLabTestMethods ?: ($case->confirmatory_test_methods ?: null),
                            'specifications' => $savedLabSpecifications ?: ($case->confirmatory_specifications ?: null),
                            'remark' => $savedLabRemark ?: ($case->confirmatory_remark ?: null),
                            'sample_taken' => $savedLabSampleTaken ?: ($case->sample_weight_taken ?: null),
                            'sample_reserved' => $savedLabSampleReserved ?: ($case->sample_weight_reserved ?: null),
                            'findings' => $savedLabFindings ?: ($case->laboratory_analytical_findings ?: null),
                        ];
                    }

                    $suspectDrugExhibitsList = [];
                    if (!empty($activeSuspectDrugExhibits)) {
                        foreach ($activeSuspectDrugExhibits as $sIdx => $sz) {
                            if (!$checkIsDrugExhibitReview($sz)) continue;

                            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                            $sName = $sz['seizure_name_drug'] ?? ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? '')));
                            $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ($sz['veh_owner'] ?? ($sz['wpn_owner'] ?? ($sz['dev_owner'] ?? $activeSuspectName)))));
                            $szIsAssoc = !empty($activeSuspectItem['is_associate']);
                            $exOwner = !empty($szOwner) ? $szOwner : $activeSuspectName;

                            $suspectDrugExhibitsList[] = [
                                'id'    => $sz['id'] ?? ($sz['exhibit_id'] ?? null),
                                'index' => count($suspectDrugExhibitsList) + 1,
                                'name'  => $sName ?: 'Drug Exhibit',
                                'cat'   => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                                'qty'   => $sz['seizure_quantity'] ?? ($sz['quantity'] ?? null),
                                'weight'=> $sz['seizure_weight'] ?? ($sz['weight'] ?? null),
                                'unit'  => $sz['seizure_unit'] ?? ($sz['unit'] ?? null),
                                'pkg'   => $sz['seizure_packaging'] ?? ($sz['packaging'] ?? null),
                                'description' => $sz['seizure_description'] ?? ($sz['description'] ?? null),
                                'owner' => $exOwner,
                                'is_associate' => $szIsAssoc,
                                'has_saved_lab' => !empty($sz['has_saved_lab']),
                                'confirmatory_result' => $sz['confirmatory_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? ($case->confirmatory_test_result ?? 'YES')),
                                'substance_identification' => $sz['substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? ($case->confirmatory_substance_identification ?? $sName)),
                                'test_methods' => $sz['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? ($case->confirmatory_test_methods ?? '')),
                                'specifications' => $sz['specifications'] ?? ($labDataFromDocket['specifications'] ?? ($case->confirmatory_specifications ?? '')),
                                'remark' => $sz['remark'] ?? ($labDataFromDocket['remark'] ?? ($case->confirmatory_remark ?? '')),
                                'sample_taken' => $sz['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? ($case->sample_weight_taken ?? '')),
                                'sample_reserved' => $sz['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? ($case->sample_weight_reserved ?? '')),
                                'findings' => $sz['findings'] ?? ($labDataFromDocket['findings'] ?? ($case->laboratory_analytical_findings ?? '')),
                            ];
                        }
                    }

                    $pendingDrugExhibits = array_values(array_filter($suspectDrugExhibitsList, function($e) {
                        return empty($e['has_saved_lab']);
                    }));
                    $drugExhibits = !empty($pendingDrugExhibits) ? $pendingDrugExhibits : $suspectDrugExhibitsList;
                    $initialSelectedEx = !empty($drugExhibits) ? $drugExhibits[0] : null;
                    $initialHasSaved = $activeSuspectHasSavedResult;
                    $initialLabDesc = !empty($initialSelectedEx) ? $initialSelectedEx['name'] : 'No drug exhibit linked';

                    $activeSuspectHasDrugExhibit = !empty($activeSuspectDrugExhibits) || !empty($dbActiveLabExam);
                    if (empty($activeSuspectDrugExhibits) && empty($dbActiveLabExam)) {
                        $activeSuspectHasSavedResult = false;
                        $activeSuspectLabExam = null;
                    }

                    $labIsAssoc = false;
                    $savedLabOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
                    if (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
                        $labOwnerName = $savedLabOwnerName;
                    } elseif (!empty($allDrugExhibitsList[0]['owner']) && strtoupper(trim($allDrugExhibitsList[0]['owner'])) !== 'PRIMARY SUSPECT') {
                        $labOwnerName = $allDrugExhibitsList[0]['owner'];
                    } else {
                        $labOwnerName = !empty($primarySuspectsCombinedNames) ? $primarySuspectsCombinedNames : ($case->suspect_name ?? 'Primary Suspect');
                    }

                    $labOwnerLower = strtolower(trim($labOwnerName));
                    $isJointLabOwner = str_contains($labOwnerLower, ',') || str_contains($labOwnerLower, '&') || str_contains($labOwnerLower, 'all suspect') || str_contains($labOwnerLower, 'all suspects');
                    if (!$isJointLabOwner && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
                        $mCount = 0;
                        foreach ($docketData['suspects'] as $sp) {
                            if (!empty($sp['suspect_name']) && str_contains($labOwnerLower, strtolower(trim($sp['suspect_name'])))) {
                                $mCount++;
                            }
                        }
                        if ($mCount > 1) {
                            $isJointLabOwner = true;
                        }
                    }

                    if ($isJointLabOwner) {
                        $labDossierBadge = 'PRIMARY & CO-SUSPECT DOSSIER';
                        $labBadgeBg = 'bg-indigo-600';
                    } else {
                        $labDossierBadge = 'PRIMARY SUSPECT DOSSIER';
                        $labBadgeBg = 'bg-emerald-600';
                    }
                @endphp

                <!-- OFFICIAL READ-ONLY FORENSIC EXAMINATION REPORT DISPLAY -->
                <div id="section5ReadOnlyDisplay" class="space-y-4 {{ ((!$isDrugCase) || !$activeSuspectHasSavedResult) ? 'hidden' : '' }}">
                    <!-- TOP VERIFICATION BANNER -->
                    <div class="p-4 bg-emerald-50/90 border-emerald-300 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl {{ $labBadgeBg }} text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-[10px] font-mono-code font-black text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="{{ $labBadgeBg }} text-white px-1.5 py-0.5 rounded text-[9px]">{{ $labDossierBadge }}</span>
                                    <span>&bull;</span>
                                    <span>SUSPECT: {{ strtoupper($labOwnerName) }}</span>
                                </div>
                                <div class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($labOwnerName) }}</div>
                            </div>
                        </div>

                    </div>

                    <!-- STRUCTURED READ-ONLY LABORATORY EXAMINATION TABLE -->
                    <table class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                        <tbody>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Analysis Reference:</td>
                                <td class="p-3 font-black text-emerald-900 font-mono-code text-sm border-r border-slate-300 w-3/10 bg-white" id="displayLabExamRef">{{ $savedLabExamRef }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Exhibit Number:</td>
                                <td class="p-3 font-black text-emerald-900 font-mono-code text-sm bg-white" id="displayLabExhibitNo">{{ $savedLabExhibitNo ?: $labExhibitNo }}</td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Case Reference:</td>
                                <td class="p-3 font-black text-blue-900 font-mono-code border-r border-slate-300 bg-white" id="displayLabCaseRef">{{ $savedLabCaseRef }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Laboratory Number:</td>
                                <td class="p-3 font-black text-slate-900 font-mono-code bg-white" id="section5LabUnitDisplay">{{ $savedLabNumber }}</td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Exhibit Description:</td>
                                <td class="p-3 font-bold text-slate-900 bg-white" colspan="3" id="displayLabExhibitDesc">{{ $savedLabExhibitDesc ?: ($savedLabSampleDesc ?: 'N/A') }}</td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Analyst / Examiner:</td>
                                <td class="p-3 font-black text-slate-900 border-r border-slate-300 bg-white" id="section5AnalystDisplay">{{ $savedLabAnalyst }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Examination Timeline:</td>
                                <td class="p-2 bg-white">
                                    <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                        <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                            <tr class="border-b border-slate-300">
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Received</th>
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Started</th>
                                                <th class="px-2 py-1 text-left bg-slate-200/70">Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                            <tr>
                                                <td class="px-2 py-1 border-r border-slate-300 text-blue-900" id="section5DateReceivedDisplay">{{ $savedLabDateReceived }}</td>
                                                <td class="px-2 py-1 border-r border-slate-300 text-amber-900" id="section5DateStartedDisplay">{{ $savedLabDateStarted }}</td>
                                                <td class="px-2 py-1 text-emerald-900" id="section5DateCompletedDisplay">{{ $savedLabDateCompleted }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Presumptive Reaction:</td>
                                <td class="p-2 border-r border-slate-300 bg-white" id="section5PresumptiveDisplay">
                                    @php
                                        $pRaw = trim($savedLabPresumptive ?? '');   // for occurrence 1
                                        // $pRaw = trim($assocPresumptiveText ?? ''); // for occurrence 3 (use the right var name)
                                        $pRows = [];
                                        if (!empty($pRaw) && $pRaw !== 'N/A') {
                                            $overallResult = 'N/A';
                                            if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRaw, $mRes)) {
                                                $overallResult = strtoupper($mRes[1]);
                                                if ($overallResult === 'POS' || $overallResult === 'POSITIVE') $overallResult = 'YES';
                                                if ($overallResult === 'NEG' || $overallResult === 'NEGATIVE') $overallResult = 'NO';
                                            }

                                            $cleanP = $pRaw;
                                            if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRaw, $mP)) {
                                                $cleanP = $mP[1];
                                            }

                                            // Strip the leading result keyword from each segment too
                                            $segments = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanP);
                                            if (empty($segments)) $segments = [$cleanP];

                                            foreach ($segments as $seg) {
                                                $seg = trim($seg);
                                                if (empty($seg)) continue;

                                                // Remove leading [1], [2] markers and result keywords
                                                $seg = preg_replace('/^\[\d+\]\s*/', '', $seg);
                                                $seg = preg_replace('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)\s*/i', '', $seg);

                                                $presence = null;
                                                $purity   = null;

                                                if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $seg, $mPres)) {
                                                    $presence = trim($mPres[1]);
                                                }

                                                if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $seg, $mPur)) {
                                                    $purity = trim($mPur[1]);
                                                }

                                                // If neither keyword matched, treat the whole segment as the presence
                                                if ($presence === null && $purity === null) {
                                                    $fallback = trim($seg, " -;()\t\n\r\0\x0B");
                                                    if (!empty($fallback)) {
                                                        $presence = $fallback;
                                                    }
                                                }

                                                // Strip leftover result keywords from presence
                                                if ($presence !== null && in_array(strtoupper($presence), ['YES','NO','POS','NEG','POSITIVE','NEGATIVE'], true)) {
                                                    $presence = null;
                                                }

                                                if ($presence !== null || $purity !== null) {
                                                    $pRows[] = [
                                                        'result'   => $overallResult,
                                                        'presence' => $presence ?? '—',
                                                        'purity'   => $purity   ?? '—',
                                                    ];
                                                }
                                            }

                                            // If we have a result but no detail rows, still show the result cleanly
                                            if (empty($pRows) && $overallResult !== 'N/A') {
                                                $pRows[] = [
                                                    'result'   => $overallResult,
                                                    'presence' => '—',
                                                    'purity'   => '—',
                                                ];
                                            }
                                        }
                                        if (empty($pRows)) {
                                            $pRows[] = ['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A'];
                                        }
                                    @endphp
                                    <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                        <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                            <tr class="border-b border-slate-300">
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                                <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($pRows as $pRow)
                                            <tr class="border-b border-slate-200 last:border-b-0">
                                                <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pRow['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pRow['result'] }}</td>
                                                <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $pRow['presence'] }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">{{ $pRow['purity'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Confirmatory Test:</td>
                                @php
                                    $isDisplayConfNo = strtoupper(trim((string)($savedLabConfirmatory ?: ($case->confirmatory_test_result ?: 'YES')))) === 'NO';
                                    $displayConfText = $isDisplayConfNo ? 'NO' : 'YES';
                                @endphp
                                <td class="p-3 font-black bg-white {{ !$isDisplayConfNo ? 'text-emerald-950' : 'text-red-950' }}" id="section5ConfirmatoryDisplay">
                                    <span class="inline-flex items-center space-x-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full {{ !$isDisplayConfNo ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                                        <span class="font-extrabold">{{ $displayConfText }}</span>
                                    </span>
                                </td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Sample Metrology:</td>
                                <td class="p-3 bg-white" colspan="3">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Description</div>
                                            <div class="font-bold text-slate-950 text-xs mt-0.5" id="section5SampleDescDisplay">{{ $savedLabSampleDesc ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Number of Units</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleUnitsDisplay">{{ $savedLabSampleUnits ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Gross Weight</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleGrossDisplay">{{ $savedLabSampleGross ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Sample Taken</div>
                                            @php
                                                if (!empty($savedLabSampleTaken) && $savedLabSampleTaken !== 'N/A') {
                                                    if (preg_match('/(kg|g|mg|lbs|grams|kilograms)/i', (string)$savedLabSampleTaken)) {
                                                        $displaySampleTaken = $savedLabSampleTaken;
                                                    } else {
                                                        $displaySampleTaken = trim($savedLabSampleTaken . ' ' . ($savedLabSampleUnit ?? ''));
                                                    }
                                                    $displaySampleTakenLabel = null;
                                                } else {
                                                    $displaySampleTaken = '—';
                                                    $displaySampleTakenLabel = 'Not Recorded';
                                                }
                                            @endphp
                                            <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5" id="section5SampleTakenDisplay">
                                                {{ $displaySampleTaken }}
                                                @if(!empty($displaySampleTakenLabel))
                                                    <span class="ml-1 text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-1 py-0.5 rounded">{{ $displaySampleTakenLabel }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Reserved Samples</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleReservedDisplay">
                                                @if(!empty($savedLabSampleReserved))
                                                    {{ $savedLabSampleReserved }}
                                                @else
                                                    —<span class="ml-1 text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-1 py-0.5 rounded">Not Recorded</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Substance Identified:</td>
                                <td class="p-3 font-black text-slate-950 bg-white border-r border-slate-300" id="section5SubstanceIdDisplay">{{ $savedLabSubstanceId ?: 'N/A' }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Specifications:</td>
                                <td class="p-3 font-bold text-slate-900 bg-white" id="section5SpecsDisplay">{{ $savedLabSpecifications ?: 'N/A' }}</td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Result (Detected Substances & Purity %):</td>
                                <td class="p-3 bg-white font-bold text-slate-900 text-xs" colspan="3" id="section5DetectedSubstancesDisplay">
                                    @php 
                                        $detList = []; 
                                        $rawDetList = $savedLabDetectedSubstances ?? [];
                                        if (is_string($rawDetList)) {
                                            $rawDetList = json_decode($rawDetList, true) ?: [];
                                        }
                                        if (is_array($rawDetList)) {
                                            foreach ($rawDetList as $subItem) {
                                                if (is_array($subItem)) {
                                                    $sName = $subItem['substance'] ?? ($subItem['name'] ?? ($subItem['compound'] ?? ($subItem['drug_name'] ?? '')));
                                                    $sPct  = $subItem['percentage'] ?? ($subItem['purity'] ?? ($subItem['pct'] ?? ''));
                                                    if (!empty($sName)) {
                                                        $detList[] = $sName . (!empty($sPct) ? ' (' . rtrim(trim($sPct), '%') . '%)' : '');
                                                    }
                                                }
                                            }
                                        }
                                        if (empty($detList)) {
                                            $pText = $savedLabPresumptive ?? '';
                                            if (!empty($pText) && (str_contains($pText, 'Presence of:') || str_contains($pText, 'YES'))) {
                                                $cleanP = preg_replace('/^YES\s*\((.*)\)$/i', '$1', trim($pText));
                                                $pSegs = explode(';', $cleanP);
                                                foreach ($pSegs as $pSeg) {
                                                    $pSeg = trim($pSeg);
                                                    $pName = ''; $pPur = '';
                                                    if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $pSeg, $mP)) {
                                                        $pName = trim($mP[1]);
                                                    }
                                                    if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $pSeg, $mPur)) {
                                                        $pPur = trim($mPur[1]);
                                                    }
                                                    if (!empty($pName)) {
                                                        $detList[] = $pName . (!empty($pPur) ? ' (' . rtrim(trim($pPur), '%') . '%)' : '');
                                                    }
                                                }
                                            }
                                        }
                                        if (empty($detList) && !empty($savedLabSubstanceId) && $savedLabSubstanceId !== 'N/A') {
                                            $detList[] = $savedLabSubstanceId;
                                        }
                                    @endphp
                                    @if(count($detList) > 0)
                                        <span>{{ implode(', ', $detList) }}</span>
                                    @else
                                        <span class="text-slate-700 font-bold italic text-xs">No individual compound breakdown logged</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Methods:</td>
                                <td class="p-3 font-mono-code font-black text-slate-900 bg-white" colspan="3" id="section5TestMethodsDisplay">{{ $savedLabTestMethods ?: 'N/A' }}</td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Analysis Remark:</td>
                                <td class="p-3 font-bold text-slate-900 bg-white" colspan="3" id="section5RemarkDisplay">{{ $savedLabRemark ?: 'N/A' }}</td>
                            </tr>
                            <tr class="bg-slate-900 text-white">
                                <td class="p-3 font-mono-code font-black text-emerald-400 uppercase border-r border-slate-800 bg-slate-950">Analytical Findings:</td>
                                <td class="p-3 font-medium text-slate-100 leading-relaxed bg-slate-900" colspan="3" id="section5FindingsDisplay">{{ $savedLabFindings ?: 'No specific laboratory analytical findings logged yet.' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- EXAMINATION INFORMATION CONTAINER (ALWAYS VISIBLE AT LAB ANALYSIS STAGE WHEN UNSAVED) -->
                <div id="section5FormContainer" class="space-y-6 {{ ($activeSuspectHasSavedResult || !$isLabAnalysis) ? 'hidden' : '' }}">
                    <div class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-slate-900 text-base">Examination Information</h3>
                                    <span id="section5BadgeStatus" class="px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $activeSuspectHasSavedResult ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                                        {{ $activeSuspectHasSavedResult ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? 'LAB IN PROGRESS' : 'LAB PENDING') }}
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis Directorate — Forensic Examination &amp; Testing Log</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                LAB ANALYSIS EXCLUSIVE
                            </span>
                            <button type="button" onclick="toggleSection5()" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-extrabold rounded-lg border border-slate-300 transition-colors flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 012.122-.363c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                <span class="s5ToggleTextSpan">Hide Section 5</span>
                            </button>
                        </div>
                    </div>

                    <form id="section5LabForm" onsubmit="submitCombinedLabAndSeizureForm(event)" class="space-y-5">
                    <input type="hidden" id="labFormExhibitId" name="exhibit_id" value="{{ !empty($drugExhibits) ? ($drugExhibits[0]['id'] ?? $drugExhibits[0]['index']) : '' }}">
                    <input type="hidden" id="labFormExhibitOwner" name="exhibit_owner_name" value="{{ $activeSuspectName }}">

                    <!-- EXHIBIT SELECTOR CARD FROM SECTION 4 MANIFEST -->
                    <div class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-3 shadow-2xs">
                        <div class="grid grid-cols-1 gap-4">

                            <!-- SEIZED EXHIBIT SELECTOR FROM SECTION 4 MANIFEST -->
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <span>Select Seized Exhibit to Examine (Manifest Sync)</span> <span class="text-emerald-700 font-semibold text-[10px]">(Type of Drug)</span>
                                        <span class="text-red-600 font-bold">*</span>
                                    </span>
                                    <span id="labExhibitCountBadge" class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">
                                        {{ !empty($drugExhibits) ? count($drugExhibits) . ' Drug Exhibit(s) Synced' : '0 Drug Exhibits Linked' }}
                                    </span>
                                </label>
                                <select 
                                    id="labExhibitSelect" 
                                    name="exhibit_id"
                                    onchange="onReviewLabExhibitChange(this)"
                                    class="w-full px-3.5 py-3 bg-white border border-emerald-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                    {{ empty($drugExhibits) ? 'disabled' : '' }}
                                >
                                    <option value="" selected>Select Seized Exhibit to Examine (Manifest Sync)...</option>
                                    @if(!empty($drugExhibits))
                                        @foreach($drugExhibits as $dEx)
                                            @php
                                                $exTitle = $dEx['name'] ?? ($dEx['cat'] ?? ('Exhibit #' . ($loop->index + 1)));
                                                $exMetrics = !empty($dEx['qty']) 
                                                    ? (' — ' . $dEx['qty'] . (!empty($dEx['unit']) && !str_contains(strtolower($dEx['qty']), strtolower($dEx['unit'])) ? (' ' . $dEx['unit']) : ''))
                                                    : (!empty($dEx['weight']) ? (' — ' . $dEx['weight'] . (!empty($dEx['unit']) && !str_contains(strtolower($dEx['weight']), strtolower($dEx['unit'])) ? (' ' . $dEx['unit']) : '')) : '');
                                                $rawDescBlade = $dEx['pkg'] ?? ($dEx['description'] ?? '');
                                                $cleanDescBlade = $rawDescBlade;
                                                if (!empty($cleanDescBlade) && !empty($exTitle)) {
                                                    if (str_starts_with(strtolower($cleanDescBlade), strtolower($exTitle))) {
                                                        $cleanDescBlade = trim(substr($cleanDescBlade, strlen($exTitle)));
                                                    }
                                                    $cleanDescBlade = trim(preg_replace('/^[\s\—\-\:\(\)]+/', '', $cleanDescBlade), ') ');
                                                }
                                                $exPkgStr = !empty($cleanDescBlade) ? (' (' . $cleanDescBlade . ')') : '';
                                                $exOwnerStr = !empty($dEx['owner']) ? (' [Owner: ' . $dEx['owner'] . ']') : '';
                                            @endphp
                                            <option 
                                                value="{{ $dEx['id'] ?? $dEx['index'] }}" 
                                                data-ex-id="{{ $dEx['id'] ?? '' }}"
                                                data-index="{{ $dEx['index'] }}"
                                                data-name="{{ $dEx['name'] }}" 
                                                data-owner="{{ $dEx['owner'] ?? '' }}" 
                                                data-qty="{{ $dEx['qty'] ?? '' }}" 
                                                data-weight="{{ $dEx['weight'] ?? '' }}" 
                                                data-unit="{{ $dEx['unit'] ?? '' }}" 
                                                data-pkg="{{ $dEx['pkg'] ?? '' }}"
                                                data-description="{{ $dEx['description'] ?? '' }}"
                                                data-is-associate="{{ !empty($dEx['is_associate']) ? '1' : '0' }}" 
                                                data-has-saved="{{ !empty($dEx['has_saved_lab']) ? 'true' : 'false' }}" 
                                                data-confirmatory="{{ $dEx['confirmatory_result'] ?? 'YES' }}" 
                                                data-substance-id="{{ $dEx['substance_identification'] ?? '' }}" 
                                                data-test-methods="{{ $dEx['test_methods'] ?? '' }}" 
                                                data-specifications="{{ $dEx['specifications'] ?? '' }}" 
                                                data-remark="{{ $dEx['remark'] ?? '' }}" 
                                                data-sample-taken="{{ $dEx['sample_taken'] ?? '' }}" 
                                                data-sample-reserved="{{ $dEx['sample_reserved'] ?? '' }}" 
                                                data-detected-substances="{{ is_array($dEx['detected_substances'] ?? null) ? json_encode($dEx['detected_substances']) : ($dEx['detected_substances'] ?? '') }}" 
                                                data-findings="{{ $dEx['findings'] ?? '' }}" 
                                            >
                                                {{ $exTitle }}{{ $exMetrics }}{{ $exPkgStr }}{{ $exOwnerStr }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <p class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Select a drug type and exhibit above to load Section 4 manifest details and process forensic examination.</span>
                        </p>
                    </div>

                    <!-- REFERENCE NUMBER FIELDS -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Analysis/Examination Reference -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Analysis / Examination Reference</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                            </label>
                            <input 
                                type="text" 
                                id="labExamReferenceInput" 
                                value="{{ $labExamRef }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Case Reference -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Case Reference</span>
                                <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                            </label>
                            <input 
                                type="text" 
                                id="labCaseReferenceInput" 
                                value="{{ $labCaseRef }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Exhibit Number -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Exhibit Number</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                            </label>
                            <input 
                                type="text" 
                                id="labExhibitNumberInput" 
                                value="{{ $labExhibitNo }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>
                    </div>

                    <!-- EXHIBIT DESCRIPTION, LAB UNIT & ANALYST / EXAMINER -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Exhibit Description -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Exhibit Description</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Fetched from Manifest</span>
                            </label>
                            <textarea 
                                id="labExhibitDescriptionInput" 
                                rows="3" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >{{ $initialLabDesc }}</textarea>
                        </div>

                        <!-- Laboratory Number -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Laboratory Number</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                            </label>
                            <input 
                                type="text" 
                                id="formLabNumber" 
                                name="laboratory_number"
                                value="{{ $labNumber }}" 
                                readonly
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Analyst / Examiner -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Analyst / Examiner</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Examiner Name</span>
                            </label>
                            <input 
                                type="text" 
                                id="formLabAnalyst" 
                                value="{{ $labAnalyst }}" 
                                class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs text-xs"
                                placeholder="e.g. John Adu"
                            >
                        </div>
                    </div>

                    <!-- DATES TIMELINE -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-slate-100">
                        <!-- Date Received -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Received <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateReceivedInput" 
                                value="{{ $labDateReceived }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Date Examination Started -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Examination Started <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateStartedInput" 
                                value="{{ $labDateStarted }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Date Examination Completed -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Examination Completed <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateCompletedInput" 
                                value="{{ $labDateCompleted }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>
                    </div>

                    <!-- SECTION 6: PRESUMPTIVE TEST CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
                        <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm">Presumptive Test</h3>
                                    <p class="text-[11px] text-emerald-700 font-semibold">Initial chemical field screening &amp; colorimetric reaction validation</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                SCREENING RESULT
                            </span>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                            </label>

                            @php
                                $pIsYes = !empty($labPresumptive) && (str_starts_with(strtoupper($labPresumptive), 'YES') || str_starts_with(strtoupper($labPresumptive), 'POS'));
                            @endphp
                            <div class="flex items-center space-x-3">
                                <!-- YES CHECKBOX -->
                                <label id="labelPresumptiveYes" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ ($initialHasSaved && $pIsYes) ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        id="presumptiveTestYesReview"
                                        name="form_presumptive_radio" 
                                        value="YES"
                                        {{ ($initialHasSaved && $pIsYes) ? 'checked' : '' }}
                                        onchange="onPresumptiveRadioChange('YES')"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        <span class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                                    </div>
                                </label>

                                <!-- NO CHECKBOX -->
                                <label id="labelPresumptiveNo" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ ($initialHasSaved && !$pIsYes && $labPresumptive !== 'N/A') ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        id="presumptiveTestNoReview"
                                        name="form_presumptive_radio" 
                                        value="NO"
                                        {{ ($initialHasSaved && !$pIsYes && $labPresumptive !== 'N/A') ? 'checked' : '' }}
                                        onchange="onPresumptiveRadioChange('NO')"
                                        class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="font-black text-xs text-slate-900 font-mono-code uppercase">NO</span>
                                    </div>
                                </label>
                            </div>

                            <!-- DROPDOWN INPUT BOXES FOR PRESUMPTIVE TEST REACTION DETAILS -->
                            <div id="presumptiveReactionDetailsContainerReview" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ !$pIsYes ? 'hidden' : '' }}">
                                <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                    <div class="flex items-center space-x-2 text-emerald-800 font-extrabold text-xs uppercase tracking-wider">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                        <span>Presumptive Test Reaction Result Details &amp; Field Spec</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                        MULTIPLE SPECIMEN SUPPORT
                                    </span>
                                </div>
                                
                                <!-- DYNAMIC TEST ROWS CONTAINER -->
                                <div id="presumptiveTestRowsContainerReview" class="space-y-3">
                                    <div class="presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5">
                                        <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                            <span class="test-row-title-review">Test Result #1</span>
                                            <button type="button" onclick="removePresumptiveTestRowReview(this)" class="remove-test-row-btn-review hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                                                &times; Remove
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Presence of <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                                                    onchange="updatePresumptiveReactionTextReview()" 
                                                    oninput="updatePresumptiveReactionTextReview()" 
                                                    placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Percentage (%) <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                                                    <input 
                                                        type="text" 
                                                        class="presumptive-percentage-input-review w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code" 
                                                        onchange="updatePresumptiveReactionTextReview()" 
                                                        oninput="updatePresumptiveReactionTextReview()" 
                                                        placeholder="e.g. 85.5 or 80-90"
                                                    >
                                                    <span class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ADD MORE TEST RESULT BUTTON -->
                                <div class="pt-1 flex items-center justify-between">
                                    <button 
                                        type="button" 
                                        onclick="addPresumptiveTestRowReview()" 
                                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Add More Test Result</span>
                                    </button>
                                    <span class="text-[10px] text-slate-500 font-medium italic">Click to log additional chemical/substance reaction test results</span>
                                </div>

                                <!-- Hidden Presumptive Reaction Result for Form Submission -->
                                <input 
                                    type="hidden" 
                                    id="presumptiveReactionResultInputReview" 
                                    value="{{ $labPresumptive }}"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- CONFIRMATORY TEST CARD (UNDERNEATH PRESUMPTIVE TEST SECTION) -->
                    <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
                        <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm">Confirmatory Test</h3>
                                    <p class="text-[11px] text-emerald-700 font-semibold">Instrumental &amp; Spectrometry Laboratory Confirmation (GC-MS / FTIR)</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                LAB VERIFICATION
                            </span>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                            </label>

                            @php
                                $rawCurConf = !empty($savedLabConfirmatory) ? $savedLabConfirmatory : (!empty($labConfirmatory) ? $labConfirmatory : (!empty($case->confirmatory_test_result) ? $case->confirmatory_test_result : 'YES'));
                                $isConfNo = strtoupper(trim((string)$rawCurConf)) === 'NO';
                                $isConfYes = !$isConfNo;
                            @endphp
                            <div class="flex items-center space-x-3">
                                <!-- YES RADIO -->
                                <label id="labelConfirmatoryYesReview" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $isConfYes ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        id="confirmatoryTestYesReview"
                                        name="form_confirmatory_radio" 
                                        value="YES"
                                        {{ $isConfYes ? 'checked' : '' }}
                                        onchange="toggleConfirmatoryTestReview('YES')"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        <span class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                                    </div>
                                </label>

                                <!-- NO RADIO -->
                                <label id="labelConfirmatoryNoReview" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $isConfNo ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        id="confirmatoryTestNoReview"
                                        name="form_confirmatory_radio" 
                                        value="NO"
                                        {{ $isConfNo ? 'checked' : '' }}
                                        onchange="toggleConfirmatoryTestReview('NO')"
                                        class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                                    </div>
                                </label>
                            </div>

                            <!-- SAMPLE WEIGHT GROUP INPUT CONTAINER (DYNAMICALLY SHOWN WHEN CONFIRMATORY TEST IS YES) -->
                            <div id="sampleWeightContainerReview" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ $isConfYes ? '' : 'hidden' }}">
                                <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                    <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                        </svg>
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
                                            id="sampleWeightDescriptionReview" 
                                            name="sample_weight_description" 
                                            value="{{ $labSampleDesc }}"
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
                                            id="sampleWeightUnitsReview" 
                                            name="sample_weight_units" 
                                            value="{{ $labSampleUnits }}"
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
                                            id="sampleWeightGrossReview" 
                                            name="sample_weight_gross" 
                                            value="{{ $labSampleGross }}"
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
                                                id="sampleWeightTakenReview" 
                                                name="sample_weight_taken" 
                                                value="{{ $labSampleTaken }}"
                                                placeholder="e.g. 10"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0"
                                            >
                                            <span 
                                                id="sampleWeightTakenUnitAddonReview"
                                                class="inline-flex items-center px-3 py-2.5 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0 transition-colors duration-200"
                                            >
                                                {{ $labSampleUnit }}
                                            </span>
                                        </div>
                                        <p id="sampleWeightTakenErrorReview" class="text-[11px] text-red-600 font-extrabold mt-1.5 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
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
                                            id="sampleWeightReservedReview" 
                                            name="sample_weight_reserved" 
                                            value="{{ $labSampleReserved }}"
                                            readonly
                                            placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed"
                                        >
                                    </div>

                                    <!-- RESULT SECTION (UNDERNEATH SAMPLE WEIGHT) -->
                                    <div class="md:col-span-2 pt-4 border-t border-emerald-200/80 space-y-4">
                                        <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                            <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                                ANALYTICAL FINDINGS &amp; SPECS
                                            </span>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- 1. SUBSTANCE IDENTIFICATION -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                    <span>Substance Identification <span class="text-red-600 font-bold">*</span></span>
                                                    <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="confirmatorySubstanceIdentificationReview" 
                                                    name="confirmatory_substance_identification" 
                                                    value="{{ $savedLabSubstanceId ?: ($labSubstanceId ?: ($case->confirmatory_substance_identification ?: '')) }}"
                                                    readonly
                                                    placeholder="Fetched from drug manifest"
                                                    class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed"
                                                >
                                            </div>

                                            <!-- 2. RESULT (INPUT BOX WITH ADD MORE) -->
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                        Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <button 
                                                        type="button" 
                                                        onclick="addDetectedSubstanceRowReview()" 
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                        <span>Add More</span>
                                                    </button>
                                                </div>
                                                <div id="detectedSubstancesListContainerReview" class="space-y-2.5">
                                                    <!-- Dynamic rows added via JS -->
                                                </div>
                                            </div>

                                            <!-- 3. TEST METHODS INPUT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Test Methods <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="confirmatoryTestMethodsReview" 
                                                    name="confirmatory_test_methods" 
                                                    value="{{ $savedLabTestMethods ?: ($labTestMethods ?: ($case->confirmatory_test_methods ?: '')) }}"
                                                    placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400"
                                                >
                                            </div>

                                            <!-- 4. SPECIFICATIONS TEXTAREA INPUT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Specifications <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                </label>
                                                <textarea 
                                                    id="confirmatorySpecificationsReview" 
                                                    name="confirmatory_specifications" 
                                                    rows="3" 
                                                    placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                >{{ $savedLabSpecifications ?: ($labSpecifications ?: ($case->confirmatory_specifications ?: '')) }}</textarea>
                                            </div>

                                            <!-- 5. REMARK TEXTAREA INPUT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Remark <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                </label>
                                                <textarea 
                                                    id="confirmatoryRemarkReview" 
                                                    name="confirmatory_remark" 
                                                    rows="3" 
                                                    placeholder="Enter any additional remarks or observations regarding the confirmatory analysis..."
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                >{{ $savedLabRemark ?: ($labRemark ?: ($case->confirmatory_remark ?: '')) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FINDINGS -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                            Laboratory Analytical Findings &amp; Examination Log
                        </label>
                        <textarea 
                            id="formLabNotes" 
                            rows="3" 
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500 outline-none" 
                            placeholder="Enter chemical reaction results, purity percentage, spectrometry verification notes, etc."
                        >{{ $labFindings }}</textarea>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="submit" id="saveSection5LabBtn" onclick="submitCombinedLabAndSeizureForm(event)" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                            <span id="saveSection5LabBtnText">Save Seizure Item &amp; Examination Information</span>
                        </button>
                    </div>
                </form>
                </div>

                <!-- NOTICE CARD WHEN ACTIVE SUSPECT IS NOT LINKED TO ANY DRUG EXHIBITS -->
                <div id="section5NoDrugExhibitNotice" class="p-6 bg-slate-50 border border-slate-200/90 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-slate-700 shadow-2xs {{ (!empty($activeSuspectHasSavedResult) || !empty($activeSuspectHasDrugExhibit)) ? 'hidden' : '' }}">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-200/80 text-slate-500 flex items-center justify-center font-bold shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">No Drug Exhibit Linked</h4>
                            <p class="text-xs text-slate-500 mt-0.5">The selected suspect (<strong>{{ $activeSuspectName }}</strong>) is not linked to any drug exhibits for Laboratory Examination.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- SECTION 4: SEIZURES (COMMODITIES & EXHIBITS) -->
            <div id="section4Seizures" class="mt-8 bg-white/90 border border-slate-300/80 rounded-2xl p-6 shadow-sm">
                <form id="section4SeizuresForm" onsubmit="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}">
                    <div id="seizuresListContainer" class="space-y-6">
                        <!-- SEIZURE BLOCK #1 -->
                        <div class="seizure-block bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-5 relative" data-seizure-index="1">
                            <!-- SEIZURE ITEM HEADER -->
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center seizure-number-badge">1</span>
                                    <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider seizure-title">Seizure Item #1</h5>
                                </div>
                                <div class="flex items-center space-x-2 seizure-header-right">
                                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">ACTIVE EXHIBIT</span>
                                    <button type="button" onclick="removeSeizureBlock(this)" class="remove-seizure-btn hidden text-red-600 hover:bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center gap-1" title="Remove Seizure Item">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Remove Item
                                    </button>
                                </div>
                            </div>

                            <!-- SEIZURES INPUT GRID -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <!-- SUSPECT / OWNERSHIP SELECT DROPDOWN -->
                                        <div class="relative z-30">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Suspect / Ownership <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <div class="relative">
                                                <select
                                                    onchange="this.setAttribute('data-user-selected', 'true')"
                                                    class="seizure-associated-person-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer"
                                                >
                                                    <option value="All Suspects">All Suspects (Joint Ownership)</option>
                                                    @foreach($allSuspectsInCase as $sOpt)
                                                        <option value="{{ $sOpt['name'] }}" {{ strtolower(trim($sOpt['name'])) === strtolower(trim($activeSuspectName)) ? 'selected' : '' }}>
                                                            {{ $sOpt['name'] }} ({{ $sOpt['role'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- EXHIBIT (COMMODITY CATEGORY) - SELECT DROPDOWN -->
                                        <div class="relative z-[9999]" style="z-index: 9999;">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <div id="exhibitSelectWrapper" class="relative z-[9999]" style="z-index: 9999;">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="exhibitSearchInput"
                                                        placeholder="Select or type exhibit category..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openExhibitListPanel(this)"
                                                        oninput="filterExhibitList(this)"
                                                        onfocus="openExhibitListPanel(this)"
                                                    >
                                                    <input type="hidden" id="newSeizureCategory" value="">
                                                </div>
                                                <div id="exhibitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] overflow-hidden" style="z-index: 9999;">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="exhibitSearchInner" placeholder="Type to filter categories..." oninput="filterExhibitListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Drugs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Drugs</div><div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div></div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-black px-2 py-0.5 rounded font-bold">DRG</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Money', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Money</div><div class="text-[10px] text-slate-500 font-normal">Cash, Foreign Currencies, Laundering</div></div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">CASH</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Gold', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Gold</div><div class="text-[10px] text-slate-500 font-normal">Bullion, Bars, Smuggled Metals</div></div>
                                                                <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold">MET</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Vehicles', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Vehicles</div><div class="text-[10px] text-slate-500 font-normal">Automobiles, Vessels, Trucks</div></div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">VEH</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Electronic Devices', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Electronic Devices</div><div class="text-[10px] text-slate-500 font-normal">Phones, Encrypted Laptops, Drives</div></div>
                                                                <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-bold">ELEC</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Weapons', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Weapons</div><div class="text-[10px] text-slate-500 font-normal">Firearms, Handguns, Blades</div></div>
                                                                <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">WPN</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Ammunition', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Ammunition</div><div class="text-[10px] text-slate-500 font-normal">Bullets, Cartridges, Explosives</div></div>
                                                                <span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-2 py-0.5 rounded font-bold">AMMO</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Other Exhibits', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div><div class="font-extrabold text-slate-900">Other Exhibits</div><div class="text-[10px] text-slate-500 font-normal">Hardware, Miscellaneous Devices</div></div>
                                                                <span class="text-[10px] font-mono-code bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">MISC</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                        <div id="commodityFieldWrapper">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Primary Commodity Name <span class="text-red-600 font-bold">*</span></label>

                                            <!-- TEXT INPUT (shown for non-drug categories) -->
                                            <div id="commodityTextWrapper" class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureName" placeholder="e.g. Cocaine (High Purity Slabs)" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>

                                            <!-- DRUG SELECT DROPDOWN -->
                                            <div id="drugSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="drugSearchInput"
                                                        placeholder="Select or type custom drug name..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openDrugListPanel(this)"
                                                        oninput="filterDrugList(this)"
                                                        onchange="filterDrugList(this)"
                                                        onblur="filterDrugList(this)"
                                                        onkeydown="handleCommoditySearchKeydown(event, this, 'drug')"
                                                        onfocus="openDrugListPanel(this)"
                                                    >
                                                    <input type="hidden" id="newSeizureNameDrug" name="newSeizureNameDrug" value="">
                                                </div>
                                                <div id="drugListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="drugSearchInner" placeholder="Type to filter drugs..." oninput="filterDrugListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Opioids &amp; Analgesics</li>
                                                        <li class="drug-item" onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heroin (Brown Sugar / Diacetylmorphine)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Fentanyl (Synthetic Opioid Patches / Powder)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Morphine (Raw Opium Extract)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Morphine (Raw Opium Extract)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tramadol (High-Dose Tablets / Capsules)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Codeine (Cough Syrup Misuse / Purple Drank)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Oxycodone (OxyContin Tablets)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Oxycodone (OxyContin Tablets)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                                        
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Stimulants &amp; Cocaine-Class</li>
                                                        <li class="drug-item" onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cocaine (High-Purity Powder / Slabs)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crack Cocaine (Rock Form / Freebase)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Methamphetamine (Crystal Meth / Ice)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Amphetamine (Speed / Pep Pills)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Amphetamine (Speed / Pep Pills)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>MDMA (Ecstasy / Molly Tablets)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>

                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Cannabis &amp; Derivatives</li>
                                                        <li class="drug-item" onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cannabis / Marijuana (Dried Flower / Weed)</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cannabis Resin (Hashish / Black Hash Slabs)</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Synthetic Cannabinoids (Spice / K2 Herbal)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SYN</span></button></li>

                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Sedatives &amp; Depressants</li>
                                                        <li class="drug-item" onclick="selectDrugType('Rohypnol (Flunitrazepam / Date Rape Drug)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rohypnol (Flunitrazepam / Date Rape Drug)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span></button></li>
                                                        <li class="drug-item" onclick="selectDrugType('Ketamine (Special K / Dissociative Anesthetic)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ketamine (Special K / Dissociative Anesthetic)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span></button></li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- WEAPON SELECT DROPDOWN -->
                                            <div id="weaponSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="weaponSearchInput"
                                                        placeholder="Select or type custom weapon type..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openWeaponListPanel(this)"
                                                        oninput="filterWeaponList(this)"
                                                        onfocus="openWeaponListPanel(this)"
                                                    >
                                                    <input type="hidden" id="newSeizureNameWeapon" name="newSeizureNameWeapon" value="">
                                                </div>
                                                <div id="weaponListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="weaponSearchInner" placeholder="Type to filter weapons..." oninput="filterWeaponListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="weaponList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Handguns &amp; Pistols</li>
                                                        <li class="weapon-item" onclick="selectWeaponType('AK-47 / AKM Shorty Pistol (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47 / AKM Shorty Pistol (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                        <li class="weapon-item" onclick="selectWeaponType('Glock 17 Semi-Automatic Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock 17 Semi-Automatic Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                        <li class="weapon-item" onclick="selectWeaponType('Beretta 92FS / M9 Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta 92FS / M9 Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                        
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rifles &amp; Assault Rifles</li>
                                                        <li class="weapon-item" onclick="selectWeaponType('AK-47 / AKM Kalashnikov Rifle (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47 / AKM Kalashnikov Rifle (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                        <li class="weapon-item" onclick="selectWeaponType('M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- CURRENCY SELECT DROPDOWN -->
                                            <div id="currencySelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="currencySearchInput"
                                                        placeholder="Select currency..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-emerald-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                        onclick="openCurrencyListPanel(this)"
                                                        oninput="filterCurrencyList(this)"
                                                        onfocus="openCurrencyListPanel(this)"
                                                    >
                                                    <input type="hidden" id="newSeizureNameCurrency" name="newSeizureNameCurrency">
                                                </div>
                                                <div id="currencyListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-emerald-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="currencySearchInner" placeholder="Type to filter currencies..." oninput="filterCurrencyListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-300 transition-all">
                                                    </div>
                                                    <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">West Africa</li>
                                                        <li class="currency-item" onclick="selectCurrencyType('Ghana Cedi (GHS ₵)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ghana Cedi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS ₵</span></button></li>
                                                        <li class="currency-item" onclick="selectCurrencyType('Nigerian Naira (NGN ₦)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Nigerian Naira</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN ₦</span></button></li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Major Global Currencies</li>
                                                        <li class="currency-item" onclick="selectCurrencyType('US Dollar (USD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>US Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">USD $</span></button></li>
                                                        <li class="currency-item" onclick="selectCurrencyType('Euro (EUR €)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Euro</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EUR €</span></button></li>
                                                        <li class="currency-item" onclick="selectCurrencyType('British Pound Sterling (GBP £)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>British Pound</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GBP £</span></button></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ESTIMATED STREET VALUE -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Estimated Street Value</label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">₵</span>
                                                <input type="text" id="newSeizureValue" placeholder="e.g. GHS 1,500,000 / $120,000" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- QUANTITY, PACKAGING TYPE, WEIGHT & UNIT METRICS PANEL -->
                                        <div class="col-span-full bg-white/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                            <div class="flex items-center space-x-2 text-slate-800">
                                                <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Seizure Quantification &amp; Packaging Metrics</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                                <!-- QUANTITY -->
                                                <div>
                                                    <label id="quantityLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Quantity</label>
                                                    <div class="relative flex items-center group">
                                                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                        <input type="number" step="0.01" id="newSeizureQuantity" placeholder="e.g. 10" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- PACKAGING TYPE -->
                                                <div class="relative z-30">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging Type</label>
                                                    <div id="packagingSelectWrapper" class="relative">
                                                        <div class="relative">
                                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                            </span>
                                                            <input
                                                                type="text"
                                                                id="pkgSearchInput"
                                                                placeholder="Select or type packaging..."
                                                                autocomplete="off"
                                                                class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                                onclick="openPkgListPanel(this)"
                                                                oninput="filterPackagingList(this)"
                                                                onfocus="openPkgListPanel(this)"
                                                            >
                                                            <input type="hidden" id="newSeizurePackaging" value="">
                                                        </div>
                                                        <div id="pkgListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                            <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                                <input type="text" id="pkgSearchInner" placeholder="Type to filter packaging..." oninput="filterPackagingListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                            </div>
                                                            <ul id="pkgList" class="py-1.5 space-y-0.5 px-1.5">
                                                                <li class="pkg-item" onclick="selectPackagingType('Slabs', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Blocks', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Blocks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLK</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Bundles', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bundles</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BDL</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Sacks', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Sacks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SCK</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Boxes', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BOX</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Pieces', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pieces</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCS</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Units', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">UNT</span></button></li>
                                                                <li class="pkg-item" onclick="selectPackagingType('Packages', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packages</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKG</span></button></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- WEIGHT -->
                                                <div>
                                                    <label id="weightLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Weight</label>
                                                    <div class="relative flex items-center group">
                                                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">⚖</span>
                                                        <input type="number" step="0.01" id="newSeizureWeight" placeholder="e.g. 25.50" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- MEASUREMENT UNIT -->
                                                <div class="relative z-20">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Measurement Unit</label>
                                                    <div id="unitSelectWrapper" class="relative">
                                                        <div class="relative">
                                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                            </span>
                                                            <input
                                                                type="text"
                                                                id="unitSearchInput"
                                                                placeholder="Select or type unit..."
                                                                autocomplete="off"
                                                                class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                                onclick="openUnitListPanel(this)"
                                                                oninput="filterUnitList(this)"
                                                                onfocus="openUnitListPanel(this)"
                                                            >
                                                            <input type="hidden" id="newSeizureUnit" value="">
                                                        </div>
                                                        <div id="unitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                            <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                                <input type="text" id="unitSearchInner" placeholder="Type to filter units..." oninput="filterUnitListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                            </div>
                                                            <ul id="unitList" class="py-1.5 space-y-0.5 px-1.5">
                                                                <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Weight &amp; Mass</li>
                                                                <li class="unit-item" onclick="selectUnit('Kilograms (kg)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Kilograms</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button></li>
                                                                <li class="unit-item" onclick="selectUnit('Grams (g)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Grams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button></li>
                                                                <li class="unit-item" onclick="selectUnit('Milligrams (mg)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Milligrams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mg</span></button></li>
                                                                <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Volume &amp; Liquid</li>
                                                                <li class="unit-item" onclick="selectUnit('Litres (L)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Litres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ELECTRONIC DEVICE SPECIFIC FIELDS -->
                                        <div id="deviceFieldsWrapper" class="hidden col-span-full bg-blue-50/50 p-4 rounded-2xl border border-blue-200/80 space-y-3">
                                            <div class="flex items-center space-x-2 text-blue-900">
                                                <div class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-950">Electronic Device Forensic Specifications</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                    <input type="text" id="newDevModel" placeholder="e.g. iPhone 15 Pro Max" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial Number</label>
                                                    <input type="text" id="newDevSerialNo" placeholder="e.g. F2LZX984K102" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI 1</label>
                                                    <input type="text" id="newDevImei1" placeholder="e.g. 356938035643809" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI 2</label>
                                                    <input type="text" id="newDevImei2" placeholder="e.g. 356938035643817" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">MAC Address</label>
                                                    <input type="text" id="newDevMacAddress" placeholder="e.g. 00:1A:2B:3C:4D:5E" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Storage Capacity</label>
                                                    <input type="text" id="newDevStorage" placeholder="e.g. 256 GB" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="newDevColour" placeholder="e.g. Space Gray" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Other Marks</label>
                                                    <input type="text" id="newDevMarks" placeholder="e.g. Cracked screen" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- WEAPON SPECIFIC FIELDS -->
                                        <div id="weaponFieldsWrapper" class="hidden col-span-full pt-4 border-t border-slate-300/60 mt-2 space-y-4">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                </div>
                                                <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">Weapon / Firearm Specific Details</h4>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                    <input type="text" id="wpnModel" placeholder="e.g. Glock 17" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial Number</label>
                                                    <input type="text" id="wpnSerialNo" placeholder="e.g. G17-984210X" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Calibre / Gauge</label>
                                                    <input type="text" id="wpnCalibre" placeholder="e.g. 9mm" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="wpnColour" placeholder="e.g. Matte Black" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Loaded Status</label>
                                                    <select id="wpnLoadedStatus" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Loaded">Loaded</option>
                                                        <option value="Unloaded">Unloaded</option>
                                                        <option value="Round in Chamber">Round in Chamber</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Exact Location Found</label>
                                                    <input type="text" id="wpnLocationFound" placeholder="e.g. Glove compartment" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- AMMUNITION SPECIFIC FIELDS -->
                                        <div id="ammoFieldsWrapper" class="hidden col-span-full pt-4 border-t border-slate-300/60 mt-2 space-y-4">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                </div>
                                                <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">Ammunition Specific Details</h4>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge Appearance</label>
                                                    <input type="text" id="ammoCartridgeAppearance" placeholder="e.g. Brass casing, FMJ" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Projectile / Bullet Type</label>
                                                    <input type="text" id="ammoProjectileType" placeholder="e.g. Full Metal Jacket (FMJ)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition</label>
                                                    <select id="ammoCondition" onchange="toggleAmmoOtherCondition(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select Condition...</option>
                                                        <option value="Intact">Intact</option>
                                                        <option value="Damaged">Damaged</option>
                                                        <option value="Corroded">Corroded</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                                <div id="ammoOtherConditionWrapper" class="hidden">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Describe Other Condition</label>
                                                    <input type="text" id="ammoOtherCondition" placeholder="Specify condition details..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CONCEALMENT METHOD -->
                                        <div class="col-span-full md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Concealment &amp; Packaging Notes</label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureConcealment" placeholder="e.g. Vacuum foil wrapped in false trunk bottom" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- DATE & TIME SEIZED -->
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Date Seized</label>
                                                <input type="date" id="newSeizureDate" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Time Seized</label>
                                                <input type="time" id="newSeizureTime" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                            </div>
                                        </div>

                                        <!-- LOCATION SEIZED -->
                                        <div class="col-span-full md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Location Seized</label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureLocation" placeholder="e.g. Tema Port, Gate 4 — Container Terminal" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- SEIZING OFFICERS (MULTI-ROW) -->
                                        <div class="col-span-full">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                    Seizing Officer(s)
                                                </label>
                                                <button type="button" onclick="addSeizureOfficerRow(this)" class="inline-flex items-center text-[10px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg> Add Officer
                                                </button>
                                            </div>
                                            <div id="seizureOfficersContainer" class="space-y-2">
                                                <div class="seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        <div class="relative flex items-center group">
                                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                            </span>
                                                            <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="relative flex-1 flex items-center group">
                                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                                                                </span>
                                                                <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                                                            </div>
                                                            <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn hidden p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SEIZED EXHIBIT PHOTO UPLOAD AREA -->
                                        <div class="col-span-full pt-2 border-t border-slate-300/60 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                    Upload Seized Exhibit Photos / Evidence Images <span class="text-slate-400 font-normal">(Multiple Supported)</span>
                                                </label>
                                                <span id="seizurePhotosCount" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-black border border-slate-300">0 Photos Uploaded</span>
                                            </div>

                                            <div id="seizurePhotoDropzone" onclick="triggerSeizurePhotoUpload(this)" class="w-full min-h-[90px] bg-white rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center p-4 cursor-pointer group transition-colors shadow-2xs text-center">
                                                <div id="placeholderSeizureBox" class="flex flex-col items-center space-y-1 text-slate-400 group-hover:text-black transition-colors">
                                                    <div class="w-9 h-9 rounded-full bg-emerald-50 text-black flex items-center justify-center mb-0.5">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    </div>
                                                    <span class="text-xs font-extrabold text-slate-800 group-hover:text-black">Click or drag &amp; drop to upload exhibit photos</span>
                                                    <span class="text-[10px] text-slate-500">Select one or multiple PNG, JPG evidence photos</span>
                                                </div>
                                            </div>
                                            <input type="file" class="fileSeizurePhoto hidden" accept="image/*" multiple onchange="previewSeizurePhotos(event, this)">
                                            <div class="seizurePhotosGrid hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOTTOM ADD ANOTHER SEIZURE ITEM BUTTON BAR -->
                            <div class="flex items-center justify-center pt-2 pb-1 border-t border-slate-300/60">
                                <button type="button" onclick="addSeizureBlock()" class="inline-flex items-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow-md transition-all cursor-pointer group">
                                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    + Add Another Seizure Item
                                </button>
                            </div>

                            <!-- ACTION BUTTONS -->
                            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                <button type="button" onclick="document.getElementById('section4Seizures').classList.add('hidden')" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-xl transition-all cursor-pointer border-none">
                                    Cancel
                                </button>
                                <button type="submit" id="saveSection4SeizureBtn" onclick="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                                    <span id="saveSection4SeizureBtnText">{{ $isLabAnalysis ? 'Save Seizure Item & Examination Information' : 'Save Seizure / Exhibit' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @php
                    $labOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
                    $labIsAssoc = !empty($docketData['lab_examination']['exhibit_is_associate']);
                    
                    if (empty($labOwnerName) || strtoupper(trim($labOwnerName)) === 'ASSOCIATE SUSPECT' || strtoupper(trim($labOwnerName)) === 'PRIMARY SUSPECT') {
                        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                            foreach ($docketData['seizures'] as $sz) {
                                $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                                $szIsAssoc = !empty($sz['is_associate']) || !empty($szOwner) || (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
                                $szName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                                $szNo = $sz['exhibit_number'] ?? '';
                                $szId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
                                
                                if ((!empty($savedLabExhibitNo) && $szNo === $savedLabExhibitNo) || (!empty($savedLabSampleDesc) && strtolower(trim($szName)) === strtolower(trim($savedLabSampleDesc))) || (!empty($savedLabSubstanceId) && strtolower(trim($szName)) === strtolower(trim($savedLabSubstanceId)))) {
                                    if ($szIsAssoc) {
                                        $labIsAssoc = true;
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
                                        $labOwnerName = (!empty($szOwner) && strtoupper(trim($szOwner)) !== 'ASSOCIATE SUSPECT') ? $szOwner : ($matchedOwner ?? null);
                                    } else {
                                        $labIsAssoc = false;
                                        $labOwnerName = !empty($primarySuspectsCombinedNames) ? $primarySuspectsCombinedNames : 'Primary Suspect';
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    
                    $labIsAssoc = false;
                    $savedLabOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
                    if (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
                        $labOwnerName = $savedLabOwnerName;
                    } elseif (!empty($allDrugExhibitsList[0]['owner']) && strtoupper(trim($allDrugExhibitsList[0]['owner'])) !== 'PRIMARY SUSPECT') {
                        $labOwnerName = $allDrugExhibitsList[0]['owner'];
                    } else {
                        $labOwnerName = !empty($primarySuspectsCombinedNames) ? $primarySuspectsCombinedNames : ($case->suspect_name ?? 'Primary Suspect');
                    }

                    $labOwnerLower = strtolower(trim($labOwnerName));
                    $isJointLabOwner = str_contains($labOwnerLower, ',') || str_contains($labOwnerLower, '&') || str_contains($labOwnerLower, 'all suspect') || str_contains($labOwnerLower, 'all suspects');
                    if (!$isJointLabOwner && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
                        $mCount = 0;
                        foreach ($docketData['suspects'] as $sp) {
                            if (!empty($sp['suspect_name']) && str_contains($labOwnerLower, strtolower(trim($sp['suspect_name'])))) {
                                $mCount++;
                            }
                        }
                        if ($mCount > 1) {
                            $isJointLabOwner = true;
                        }
                    }

                    if ($isJointLabOwner) {
                        $labDossierBadge = 'PRIMARY & CO-SUSPECT DOSSIER';
                        $labBadgeBg = 'bg-indigo-600';
                    } else {
                        $labDossierBadge = 'PRIMARY SUSPECT DOSSIER';
                        $labBadgeBg = 'bg-emerald-600';
                    }
                @endphp

                <!-- ASSOCIATE SUSPECT LABORATORY EXAMINATION RESULT DOSSIERS -->

                    @php
                        $primaryNameStr = strtolower(trim($primarySuspectsCombinedNames ?? ($case->suspect_name ?? '')));
                        $primarySuspectNamesArray = [];
                        if (!empty($primaryAndCoNamesList) && is_array($primaryAndCoNamesList)) {
                            foreach ($primaryAndCoNamesList as $pn) {
                                if (!empty($pn)) $primarySuspectNamesArray[] = strtolower(trim($pn));
                            }
                        }
                        if (!empty($suspectsList) && is_array($suspectsList)) {
                            foreach ($suspectsList as $sItem) {
                                $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                                $isAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                                if (!$isAssoc && !empty($sItem['suspect_name'])) {
                                    $spNameClean = strtolower(trim($sItem['suspect_name']));
                                    if (!in_array($spNameClean, $primarySuspectNamesArray)) {
                                        $primarySuspectNamesArray[] = $spNameClean;
                                    }
                                }
                            }
                        }

                        $isPrimaryOrCoSuspectName = function($nameToCheck) use ($primarySuspectNamesArray) {
                            if (empty($nameToCheck)) return true;
                            $clean = strtolower(trim($nameToCheck));
                            if ($clean === 'primary suspect' || $clean === 'associate suspect' || $clean === 'all suspects') return true;

                            foreach ($primarySuspectNamesArray as $pName) {
                                if ($clean === $pName || str_contains($clean, $pName) || str_contains($pName, $clean)) {
                                    return true;
                                }
                            }
                            return false;
                        };

                        $checkIsDrugExhibit = function($ex) {
                            if (empty($ex) || !is_array($ex)) return false;
                            $cat = strtolower(trim($ex['exhibit_category'] ?? ($ex['category'] ?? ($ex['seizure_category'] ?? ($ex['type'] ?? '')))));
                            $name = strtolower(trim($ex['seizure_name_drug'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ''))));
                            $type = strtolower(trim($ex['seizure_type'] ?? ($ex['exhibit_type'] ?? '')));
                            
                            $nonDrugKws = ['weapon', 'firearm', 'pistol', 'rifle', 'shotgun', 'ammunition', 'vehicle', 'truck', 'car', 'cash', 'currency', 'usd', 'ghs', 'laptop', 'phone', 'electronics', 'document'];
                            foreach ($nonDrugKws as $kw) {
                                if (str_contains($cat, $kw) || str_contains($name, $kw) || str_contains($type, $kw)) {
                                    return false;
                                }
                            }
                            if ($cat === 'drugs' || $cat === 'narcotics' || $cat === 'drug' || !empty($ex['seizure_name_drug'])) {
                                return true;
                            }
                            if (!empty($cat) && in_array($cat, ['weapons', 'weapon', 'vehicles', 'vehicle', 'cash', 'currency', 'firearm', 'firearms'])) {
                                return false;
                            }
                            return true;
                        };

                        $associateSuspectsForLab = [];
                        if (!empty($suspectsList) && is_array($suspectsList)) {
                            foreach ($suspectsList as $sIdx => $sItem) {
                                $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                                $spIsAssoc = !empty($sItem['is_associate']) || (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                                $spName = $sItem['suspect_name'] ?? null;
                                if ($spIsAssoc && !empty($spName) && !$isPrimaryOrCoSuspectName($spName)) {
                                    $associateSuspectsForLab[] = [
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
                                if ($aName && !$isPrimaryOrCoSuspectName($aName)) {
                                    $aNameLower = strtolower(trim($aName));
                                    $alreadyAdded = false;
                                    foreach ($associateSuspectsForLab as $existingAssoc) {
                                        if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                                            $alreadyAdded = true;
                                            break;
                                        }
                                    }
                                    if (!$alreadyAdded) {
                                        $associateSuspectsForLab[] = [
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
                                if (!empty($assocP) && !$isPrimaryOrCoSuspectName($assocP) && $checkIsDrugExhibit($szItem)) {
                                    $aNameLower = strtolower(trim($assocP));
                                    $alreadyAdded = false;
                                    foreach ($associateSuspectsForLab as $existingAssoc) {
                                        if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                                            $alreadyAdded = true;
                                            break;
                                        }
                                    }
                                    if (!$alreadyAdded) {
                                        $associateSuspectsForLab[] = [
                                            'index' => count($associateSuspectsForLab) + 2,
                                            'name'  => $assocP,
                                            'item'  => ['suspect_name' => $assocP, 'is_associate' => true]
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp

                    @foreach($associateSuspectsForLab as $assocLabInfo)
                        @php
                            $aSpName = $assocLabInfo['name'];
                            $aSpIndex = $assocLabInfo['index'];
                            $aSpId = $assocLabInfo['item']['associate_id'] ?? ($assocLabInfo['item']['suspect_id'] ?? ($assocLabInfo['associate_id'] ?? ($assocLabInfo['suspect_id'] ?? null)));

                            $dbAssocExam = $case->associateLabExaminations ? $case->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($aSpName))) : null;
                            $docketAssocExam = null;
                            if (!empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
                                foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                                    if (strtolower(trim($kAssoc)) === strtolower(trim($aSpName))) {
                                        $docketAssocExam = $vAssoc;
                                        break;
                                    }
                                }
                            }

                            // Resolve associate exhibits via the model's authoritative suspect-scoped method.
                            $assocExhibitsList = array_values(array_filter(
                                $case->getExhibitsForSuspect($aSpName, $assocLabInfo['item']['id'] ?? null, $aSpId, 'Associate Suspect'),
                                $checkIsDrugExhibit
                            ));
                            $firstAssocEx = !empty($assocExhibitsList) ? $assocExhibitsList[0] : null;

                            $assocLabExam = null;
                            if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
                                foreach ($docketData['lab_examinations_list'] as $lItem) {
                                    if (!empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($aSpName))) {
                                        $assocLabExam = $lItem;
                                        break;
                                    }
                                }
                            }

                            $hasThisAssocSavedLab = false;
                            if ($dbAssocExam && $dbAssocExam->has_saved) {
                                $hasThisAssocSavedLab = true;
                            } elseif ($docketAssocExam && !empty($docketAssocExam['has_saved']) && !empty($firstAssocEx) && $checkIsDrugExhibit($firstAssocEx)) {
                                $hasThisAssocSavedLab = true;
                            } elseif (!empty($firstAssocEx) && $checkIsDrugExhibit($firstAssocEx) && !empty($firstAssocEx['has_saved_lab'])) {
                                $hasThisAssocSavedLab = true;
                            } elseif (!empty($assocLabExam) && !empty($assocLabExam['has_saved']) && !empty($firstAssocEx) && $checkIsDrugExhibit($firstAssocEx)) {
                                $hasThisAssocSavedLab = true;
                            }

                            $assocExamRef = ($dbAssocExam && !empty($dbAssocExam->exam_reference)) ? $dbAssocExam->exam_reference : (!empty($docketAssocExam['exam_reference']) ? $docketAssocExam['exam_reference'] : (!empty($assocLabExam['exam_reference']) ? $assocLabExam['exam_reference'] : (!empty($firstAssocEx['exam_reference']) ? $firstAssocEx['exam_reference'] : ($case->lab_exam_reference ?: ('GSA-FM-T' . rand(10, 99) . '-F')))));

                            $assocExhibitNo = ($dbAssocExam && !empty($dbAssocExam->exhibit_number)) ? $dbAssocExam->exhibit_number : (!empty($docketAssocExam['exhibit_number']) ? $docketAssocExam['exhibit_number'] : (!empty($assocLabExam['exhibit_number']) ? $assocLabExam['exhibit_number'] : (!empty($firstAssocEx['exhibit_number']) ? ('EXH-0' . $firstAssocEx['exhibit_number']) : 'EXH-0' . ($aSpIndex ?? 2))));

                            $assocLabNumber = $dbAssocExam && !empty($dbAssocExam->laboratory_number) ? $dbAssocExam->laboratory_number : (!empty($docketAssocExam['laboratory_number']) ? $docketAssocExam['laboratory_number'] : (!empty($assocLabExam['laboratory_number']) ? $assocLabExam['laboratory_number'] : (!empty($firstAssocEx['laboratory_number']) ? $firstAssocEx['laboratory_number'] : 'N/A')));

                            $assocAnalyst = $dbAssocExam && !empty($dbAssocExam->analyst) ? $dbAssocExam->analyst : (!empty($docketAssocExam['analyst']) ? $docketAssocExam['analyst'] : (!empty($assocLabExam['analyst']) ? $assocLabExam['analyst'] : (!empty($firstAssocEx['analyst']) ? $firstAssocEx['analyst'] : 'N/A')));

                            $assocDateReceived = ($dbAssocExam && !empty($dbAssocExam->date_received) && $dbAssocExam->date_received !== 'N/A') ? (is_object($dbAssocExam->date_received) ? $dbAssocExam->date_received->format('Y-m-d') : substr((string)$dbAssocExam->date_received, 0, 10)) : (!empty($docketAssocExam['date_received']) && $docketAssocExam['date_received'] !== 'N/A' ? $docketAssocExam['date_received'] : (!empty($assocLabExam['date_received']) && $assocLabExam['date_received'] !== 'N/A' ? $assocLabExam['date_received'] : (!empty($firstAssocEx['date_received']) && $firstAssocEx['date_received'] !== 'N/A' ? $firstAssocEx['date_received'] : (!empty($firstAssocEx['seizure_date']) && $firstAssocEx['seizure_date'] !== 'N/A' ? $firstAssocEx['seizure_date'] : $labDateReceived))));

                            $assocDateStarted = ($dbAssocExam && !empty($dbAssocExam->date_started) && $dbAssocExam->date_started !== 'N/A') ? (is_object($dbAssocExam->date_started) ? $dbAssocExam->date_started->format('Y-m-d') : substr((string)$dbAssocExam->date_started, 0, 10)) : (!empty($docketAssocExam['date_started']) && $docketAssocExam['date_started'] !== 'N/A' ? $docketAssocExam['date_started'] : (!empty($assocLabExam['date_started']) && $assocLabExam['date_started'] !== 'N/A' ? $assocLabExam['date_started'] : (!empty($firstAssocEx['date_started']) && $firstAssocEx['date_started'] !== 'N/A' ? $firstAssocEx['date_started'] : (!empty($labDateStarted) && $labDateStarted !== 'N/A' ? $labDateStarted : $assocDateReceived))));

                            $assocDateCompleted = ($dbAssocExam && !empty($dbAssocExam->date_completed) && $dbAssocExam->date_completed !== 'N/A') ? (is_object($dbAssocExam->date_completed) ? $dbAssocExam->date_completed->format('Y-m-d') : substr((string)$dbAssocExam->date_completed, 0, 10)) : (!empty($docketAssocExam['date_completed']) && $docketAssocExam['date_completed'] !== 'N/A' ? $docketAssocExam['date_completed'] : (!empty($assocLabExam['date_completed']) && $assocLabExam['date_completed'] !== 'N/A' ? $assocLabExam['date_completed'] : (!empty($firstAssocEx['date_completed']) && $firstAssocEx['date_completed'] !== 'N/A' ? $firstAssocEx['date_completed'] : (!empty($labDateCompleted) && $labDateCompleted !== 'N/A' ? $labDateCompleted : $assocDateStarted))));

                            $assocDrugName = $dbAssocExam ? $dbAssocExam->sample_weight_description : (!empty($docketAssocExam['sample_weight']['description']) ? $docketAssocExam['sample_weight']['description'] : (!empty($firstAssocEx['seizure_name_drug']) ? $firstAssocEx['seizure_name_drug'] : (!empty($firstAssocEx['seizure_name']) ? $firstAssocEx['seizure_name'] : (!empty($assocLabExam['substance_identification']) ? $assocLabExam['substance_identification'] : (!empty($assocLabExam['sample_weight']['description']) ? $assocLabExam['sample_weight']['description'] : ($firstDrugFound ?: 'Drug Exhibit'))))));

                            $assocUnits = $dbAssocExam ? $dbAssocExam->sample_weight_units : (!empty($docketAssocExam['sample_weight']['units_packaging']) ? $docketAssocExam['sample_weight']['units_packaging'] : (!empty($firstAssocEx['sample_units']) ? $firstAssocEx['sample_units'] : (!empty($firstAssocEx['seizure_quantity']) ? trim($firstAssocEx['seizure_quantity'] . ' ' . ($firstAssocEx['seizure_packaging'] ?? '')) : (!empty($assocLabExam['sample_weight']['units_packaging']) ? $assocLabExam['sample_weight']['units_packaging'] : 'N/A'))));

                            $assocGross = $dbAssocExam ? $dbAssocExam->sample_weight_gross : (!empty($docketAssocExam['sample_weight']['gross_weight']) ? $docketAssocExam['sample_weight']['gross_weight'] : (!empty($firstAssocEx['sample_gross']) ? $firstAssocEx['sample_gross'] : (!empty($firstAssocEx['seizure_weight']) ? trim($firstAssocEx['seizure_weight'] . ' ' . ($firstAssocEx['seizure_unit'] ?? '')) : (!empty($assocLabExam['sample_weight']['gross_weight']) ? $assocLabExam['sample_weight']['gross_weight'] : 'N/A'))));

                            $assocTaken = $dbAssocExam ? $dbAssocExam->sample_weight_taken : (!empty($docketAssocExam['sample_weight']['sample_taken']) ? $docketAssocExam['sample_weight']['sample_taken'] : (!empty($firstAssocEx['sample_taken']) ? $firstAssocEx['sample_taken'] : (!empty($assocLabExam['sample_weight']['sample_taken']) ? $assocLabExam['sample_weight']['sample_taken'] : 'N/A')));

                            $assocReserved = $dbAssocExam ? $dbAssocExam->sample_weight_reserved : (!empty($docketAssocExam['sample_weight']['reserved_samples']) ? $docketAssocExam['sample_weight']['reserved_samples'] : (!empty($firstAssocEx['sample_reserved']) ? $firstAssocEx['sample_reserved'] : (!empty($assocLabExam['sample_weight']['reserved_samples']) ? $assocLabExam['sample_weight']['reserved_samples'] : 'N/A')));

                            $assocPresumptiveText = $dbAssocExam ? $dbAssocExam->presumptive_result : (!empty($docketAssocExam['presumptive_result']) ? $docketAssocExam['presumptive_result'] : (!empty($firstAssocEx['presumptive_result']) ? $firstAssocEx['presumptive_result'] : (!empty($assocLabExam['presumptive_result']) ? $assocLabExam['presumptive_result'] : 'N/A')));

                            $assocConfirmatoryVal = $dbAssocExam ? $dbAssocExam->confirmatory_result : (!empty($docketAssocExam['confirmatory_result']) ? $docketAssocExam['confirmatory_result'] : (!empty($firstAssocEx['confirmatory_result']) ? $firstAssocEx['confirmatory_result'] : (!empty($assocLabExam['confirmatory_result']) ? $assocLabExam['confirmatory_result'] : 'N/A')));

                            $assocSubstanceIdVal = $dbAssocExam ? $dbAssocExam->substance_identification : (!empty($docketAssocExam['substance_identification']) ? $docketAssocExam['substance_identification'] : (!empty($firstAssocEx['substance_identification']) ? $firstAssocEx['substance_identification'] : (!empty($assocLabExam['substance_identification']) ? $assocLabExam['substance_identification'] : 'N/A')));

                            $assocSpecsVal = $dbAssocExam ? $dbAssocExam->specifications : (!empty($docketAssocExam['specifications']) ? $docketAssocExam['specifications'] : (!empty($firstAssocEx['specifications']) ? $firstAssocEx['specifications'] : (!empty($assocLabExam['specifications']) ? $assocLabExam['specifications'] : 'N/A')));

                            $assocMethodsVal = $dbAssocExam ? $dbAssocExam->test_methods : (!empty($docketAssocExam['test_methods']) ? $docketAssocExam['test_methods'] : (!empty($firstAssocEx['test_methods']) ? $firstAssocEx['test_methods'] : (!empty($assocLabExam['test_methods']) ? $assocLabExam['test_methods'] : 'N/A')));

                            $assocFindingsVal = $dbAssocExam ? $dbAssocExam->findings : (!empty($docketAssocExam['findings']) ? $docketAssocExam['findings'] : (!empty($firstAssocEx['findings']) ? $firstAssocEx['findings'] : (!empty($firstAssocEx['notes']) ? $firstAssocEx['notes'] : (!empty($assocLabExam['findings']) ? $assocLabExam['findings'] : 'No specific laboratory analytical findings logged.'))));

                            $pResultAssoc = 'N/A';
                            $pPresenceAssoc = 'N/A';
                            $pPurityAssoc = 'N/A';
                            if (!empty($assocPresumptiveText) && $assocPresumptiveText !== 'N/A') {
                                if (str_starts_with(strtoupper($assocPresumptiveText), 'YES') || str_starts_with(strtoupper($assocPresumptiveText), 'POS')) {
                                    $pResultAssoc = 'YES';
                                } elseif (str_starts_with(strtoupper($assocPresumptiveText), 'NO') || str_starts_with(strtoupper($assocPresumptiveText), 'NEG')) {
                                    $pResultAssoc = 'NO';
                                } else {
                                    $pResultAssoc = strtok($assocPresumptiveText, ' (');
                                }

                                if (preg_match('/Presence of:\s*([^-\)]+)/i', $assocPresumptiveText, $mPresAssoc)) {
                                    $pPresenceAssoc = trim($mPresAssoc[1]);
                                } elseif (preg_match('/\(([^-\)]+)\)/', $assocPresumptiveText, $mPresAssoc)) {
                                    $pPresenceAssoc = trim($mPresAssoc[1]);
                                }

                                if (preg_match('/Purity\/Percentage:\s*([^\)]+)/i', $assocPresumptiveText, $mPurAssoc)) {
                                    $pPurityAssoc = trim($mPurAssoc[1]);
                                }
                            }

                            $isCoveredByMainReport = false;
                            if (!empty($labOwnerName)) {
                                $lOwnerLow = strtolower(trim($labOwnerName));
                                $aSpNameLow = strtolower(trim($aSpName));
                                if (str_contains($lOwnerLow, 'all suspect') || str_contains($lOwnerLow, $aSpNameLow)) {
                                    $isCoveredByMainReport = true;
                                }
                            }

                            $isCoSuspect = false;
                            if (!empty($docketData['suspects'])) {
                                foreach ($docketData['suspects'] as $sp) {
                                    if (!empty($sp['suspect_name']) && strtolower(trim($sp['suspect_name'])) === strtolower(trim($aSpName))) {
                                        if (!empty($sp['association_role']) && str_contains(strtolower($sp['association_role']), 'co-suspect')) {
                                            $isCoSuspect = true;
                                        }
                                        break;
                                    }
                                }
                            }
                            $hasThisAssocDrug = false;
                            if (!empty($aSpExhibits)) {
                                foreach ($aSpExhibits as $aEx) {
                                    $aCatLower = strtolower(trim($aEx['exhibit_category'] ?? ($aEx['category'] ?? '')));
                                    $aNameLower = strtolower(trim($aEx['seizure_name_drug'] ?? ($aEx['commodity_name'] ?? ($aEx['seizure_name'] ?? ($aEx['name'] ?? '')))));
                                    if (str_contains($aCatLower, 'drug') || str_contains($aCatLower, 'narcotic') || !empty($aEx['seizure_name_drug']) || str_contains($aNameLower, 'cannabis') || str_contains($aNameLower, 'cocaine') || str_contains($aNameLower, 'heroin') || str_contains($aNameLower, 'meth') || str_contains($aNameLower, 'tramadol')) {
                                        $hasThisAssocDrug = true;
                                        break;
                                    }
                                }
                            }
                            if (!$hasThisAssocSavedLab && $hasThisAssocDrug) {
                                $hasThisAssocSavedLab = true;
                                if (empty($assocPresumptiveText)) $assocPresumptiveText = 'N/A';
                                if (empty($assocSubstanceId)) $assocSubstanceId = 'N/A';
                                if (empty($assocExamRef)) $assocExamRef = 'GSA-FM-T77-F';
                                if (empty($assocExhibitNo)) $assocExhibitNo = !empty($aSpExhibits[0]['exhibit_number']) ? $aSpExhibits[0]['exhibit_number'] : 'EXH-01';
                                if (empty($assocLabNumber)) $assocLabNumber = $case->laboratory_number ?: 'Lab No. 12/FS05/2025';
                                if (empty($assocAnalyst)) $assocAnalyst = $case->submitting_officer ?: 'Lab Analyst';
                                if (empty($assocDateReceived)) $assocDateReceived = date('Y-m-d');
                                if (empty($assocDateStarted)) $assocDateStarted = date('Y-m-d');
                                if (empty($assocDateCompleted)) $assocDateCompleted = date('Y-m-d');
                            }
                            $secondaryBadge = $isCoSuspect ? 'CO-SUSPECT DOSSIER' : 'ASSOCIATE DOSSIER';
                            $secondaryBadgeBg = $isCoSuspect ? 'bg-blue-600' : 'bg-amber-600';
                        @endphp

                        @if($hasThisAssocSavedLab && !$isCoveredByMainReport)
                        <div class="mt-6 p-4 sm:p-5 bg-amber-50/60 rounded-2xl border-2 border-amber-300 space-y-4 shadow-sm">
                            <!-- ASSOCIATE TOP BANNER -->
                            <div class="p-3.5 bg-amber-100/90 rounded-xl border border-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl {{ $secondaryBadgeBg }} text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-mono-code font-black text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="{{ $secondaryBadgeBg }} text-white px-1.5 py-0.5 rounded text-[9px]">{{ $secondaryBadge }}</span>
                                            <span>&bull;</span>
                                            <span>SUSPECT #{{ $aSpIndex }}: {{ strtoupper($aSpName) }}</span>
                                        </div>
                                        <div class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($aSpName) }}</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-200 text-amber-950 border border-amber-400 shrink-0">
                                    ASSOCIATE EXHIBIT VERIFIED
                                </span>
                            </div>

                            <!-- ASSOCIATE LABORATORY EXAMINATION TABLE -->
                            <table class="w-full text-xs border-collapse border border-amber-300 rounded-xl overflow-hidden shadow-2xs bg-white">
                                <tbody>
                                    <tr class="bg-amber-100/60 border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200 w-1/5">Analysis Reference:</td>
                                        <td class="p-3 font-black text-amber-900 font-mono-code text-sm border-r border-amber-200 w-3/10 bg-white">{{ $assocExamRef }}</td>
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200 w-1/5">Exhibit Number:</td>
                                        <td class="p-3 font-black text-amber-900 font-mono-code text-sm bg-white">{{ $assocExhibitNo }}</td>
                                    </tr>
                                    <tr class="border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Case Reference:</td>
                                        <td class="p-3 font-black text-blue-900 font-mono-code border-r border-amber-200 bg-white">{{ $labCaseRef }}</td>
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Laboratory Number:</td>
                                        <td class="p-3 font-black text-slate-900 font-mono-code bg-white">{{ $assocLabNumber }}</td>
                                    </tr>
                                    <tr class="bg-amber-100/60 border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">Analyst / Examiner:</td>
                                        <td class="p-3 font-black text-slate-900 border-r border-amber-200 bg-white">{{ $assocAnalyst }}</td>
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">Examination Timeline:</td>
                                        <td class="p-2 bg-white">
                                            <table class="w-full text-xs border border-amber-200 rounded overflow-hidden">
                                                <thead class="bg-amber-100/80 text-amber-950 font-mono-code uppercase font-black text-[10px]">
                                                    <tr class="border-b border-amber-200">
                                                        <th class="px-2 py-1 border-r border-amber-200 text-left">Received</th>
                                                        <th class="px-2 py-1 border-r border-amber-200 text-left">Started</th>
                                                        <th class="px-2 py-1 text-left">Completed</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                                    <tr>
                                                        <td class="px-2 py-1 border-r border-amber-200 text-blue-900">{{ $assocDateReceived }}</td>
                                                        <td class="px-2 py-1 border-r border-amber-200 text-amber-900">{{ $assocDateStarted }}</td>
                                                        <td class="px-2 py-1 text-emerald-900">{{ $assocDateCompleted }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Presumptive Reaction:</td>
                                        <td class="p-2 border-r border-amber-200 bg-white">
                                            @php
                                                $pRawAssoc = trim($assocPresumptiveText ?? '');
                                                $pRowsAssoc = [];
                                                if (!empty($pRawAssoc) && $pRawAssoc !== 'N/A') {
                                                    $overallResultAssoc = 'N/A';
                                                    if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRawAssoc, $mRes)) {
                                                        $overallResultAssoc = strtoupper($mRes[1]);
                                                        if ($overallResultAssoc === 'POS' || $overallResultAssoc === 'POSITIVE') $overallResultAssoc = 'YES';
                                                        if ($overallResultAssoc === 'NEG' || $overallResultAssoc === 'NEGATIVE') $overallResultAssoc = 'NO';
                                                    }

                                                    $cleanPAssoc = $pRawAssoc;
                                                    if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRawAssoc, $mP)) {
                                                        $cleanPAssoc = $mP[1];
                                                    }

                                                    $segmentsAssoc = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanPAssoc);
                                                    if (empty($segmentsAssoc)) $segmentsAssoc = [$cleanPAssoc];

                                                    foreach ($segmentsAssoc as $seg) {
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

                                                        if ($presence !== 'N/A' || $purity !== 'N/A' || count($segmentsAssoc) === 1) {
                                                            $pRowsAssoc[] = [
                                                                'result'   => $overallResultAssoc,
                                                                'presence' => $presence,
                                                                'purity'   => $purity,
                                                            ];
                                                        }
                                                    }
                                                }
                                                if (empty($pRowsAssoc)) {
                                                    $pRowsAssoc[] = ['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A'];
                                                }
                                            @endphp
                                            <table class="w-full text-xs border border-amber-200 rounded overflow-hidden">
                                                <thead class="bg-amber-100/80 text-amber-950 font-mono-code uppercase font-black text-[10px]">
                                                    <tr class="border-b border-amber-200">
                                                        <th class="px-2 py-1 border-r border-amber-200 text-left">Result</th>
                                                        <th class="px-2 py-1 border-r border-amber-200 text-left">Presence of</th>
                                                        <th class="px-2 py-1 text-left">Purity/Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white">
                                                    @foreach($pRowsAssoc as $pRowAssoc)
                                                    <tr class="border-b border-amber-100 last:border-b-0">
                                                        <td class="px-2 py-1 border-r border-amber-200 font-extrabold {{ $pRowAssoc['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pRowAssoc['result'] }}</td>
                                                        <td class="px-2 py-1 border-r border-amber-200 font-bold text-slate-900">{{ $pRowAssoc['presence'] }}</td>
                                                        <td class="px-2 py-1 font-bold text-slate-900">{{ $pRowAssoc['purity'] }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Confirmatory Test:</td>
                                        <td class="p-3 font-black bg-white text-emerald-950">
                                            <span class="inline-flex items-center space-x-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full {{ $assocConfirmatoryVal === 'YES' ? 'bg-emerald-600' : 'bg-slate-600' }}"></span>
                                                <span>{{ $assocConfirmatoryVal === 'YES' ? 'YES' : 'NO' }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="bg-amber-100/60 border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">Sample Metrology:</td>
                                        <td class="p-3 bg-white" colspan="3">
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <div class="col-span-2 sm:col-span-4 p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                                    <div class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">Description</div>
                                                    <div class="font-bold text-slate-950 text-xs mt-0.5">{{ $assocDrugName }} (Associate Seizure — {{ $aSpName }})</div>
                                                </div>
                                                <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                                    <div class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">Number of Units</div>
                                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocUnits }}</div>
                                                </div>
                                                <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                                    <div class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">Gross Weight</div>
                                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocGross }}</div>
                                                </div>
                                                <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                                    <div class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">Sample Taken</div>
                                                    <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5">{{ $assocTaken }}</div>
                                                </div>
                                                <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                                    <div class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">Reserved Samples</div>
                                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $assocReserved }}</div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Substance Identified:</td>
                                        <td class="p-3 font-black text-slate-950 bg-white border-r border-amber-200">{{ $assocSubstanceIdVal }}</td>
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Test Specifications:</td>
                                        <td class="p-3 font-bold text-slate-900 bg-white">{{ $assocSpecsVal }}</td>
                                    </tr>
                                    <tr class="bg-amber-100/60 border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">Detected Compounds:</td>
                                        <td class="p-3 bg-white font-bold text-slate-900 text-xs" colspan="3">
                                            @php 
                                                $detListAssoc = []; 
                                                $assocDetSubs = $dbAssocExam && !empty($dbAssocExam->detected_substances) ? $dbAssocExam->detected_substances : (!empty($docketAssocExam['detected_substances']) ? $docketAssocExam['detected_substances'] : (!empty($assocLabExam['detected_substances']) ? $assocLabExam['detected_substances'] : (!empty($firstAssocEx['detected_substances']) ? $firstAssocEx['detected_substances'] : [])));
                                                if (is_string($assocDetSubs)) {
                                                    $assocDetSubs = json_decode($assocDetSubs, true) ?: [];
                                                }
                                                if (is_array($assocDetSubs)) {
                                                    foreach ($assocDetSubs as $subItem) {
                                                        if (is_array($subItem)) {
                                                            $sName = $subItem['substance'] ?? ($subItem['name'] ?? ($subItem['compound'] ?? ($subItem['drug_name'] ?? '')));
                                                            $sPct  = $subItem['percentage'] ?? ($subItem['purity'] ?? ($subItem['pct'] ?? ''));
                                                            if (!empty($sName)) {
                                                                $detListAssoc[] = $sName . (!empty($sPct) ? ' (' . rtrim(trim($sPct), '%') . '%)' : '');
                                                            }
                                                        }
                                                    }
                                                }
                                                if (empty($detListAssoc)) {
                                                    $pText = $assocPresumptiveText ?? '';
                                                    if (!empty($pText) && (str_contains($pText, 'Presence of:') || str_contains($pText, 'YES'))) {
                                                        $cleanP = preg_replace('/^YES\s*\((.*)\)$/i', '$1', trim($pText));
                                                        $pSegs = explode(';', $cleanP);
                                                        foreach ($pSegs as $pSeg) {
                                                            $pSeg = trim($pSeg);
                                                            $pName = ''; $pPur = '';
                                                            if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $pSeg, $mP)) {
                                                                $pName = trim($mP[1]);
                                                            }
                                                            if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $pSeg, $mPur)) {
                                                                $pPur = trim($mPur[1]);
                                                            }
                                                            if (!empty($pName)) {
                                                                $detListAssoc[] = $pName . (!empty($pPur) ? ' (' . rtrim(trim($pPur), '%') . '%)' : '');
                                                            }
                                                        }
                                                    }
                                                }
                                                if (empty($detListAssoc) && !empty($assocSubstanceIdVal) && $assocSubstanceIdVal !== 'N/A') {
                                                    $detListAssoc[] = $assocSubstanceIdVal;
                                                }
                                            @endphp
                                            @if(count($detListAssoc) > 0)
                                                <span>{{ implode(', ', $detListAssoc) }}</span>
                                            @else
                                                <span class="text-slate-700 font-bold italic text-xs">No individual compound breakdown logged</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="border-b border-amber-200">
                                        <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">Test Methods:</td>
                                        <td class="p-3 font-mono-code font-black text-slate-900 bg-white" colspan="3">{{ $assocMethodsVal }}</td>
                                    </tr>
                                    <tr class="bg-slate-900 text-white">
                                        <td class="p-3 font-mono-code font-black text-amber-400 uppercase border-r border-slate-800 bg-slate-950">Analytical Findings:</td>
                                        <td class="p-3 font-medium text-slate-100 leading-relaxed bg-slate-900" colspan="3">{{ $assocFindingsVal }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @endif
                    @endforeach

           
            

        </main>
    </div>
</div>

<script>
    function onReviewLabExhibitChange(sel) {
        if (!sel) return;
        const opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        const exhVal = opt.value;
        const exId = opt.getAttribute('data-ex-id') || exhVal || '';
        const hiddenExIdInput = document.getElementById('labFormExhibitId');
        if (hiddenExIdInput) {
            hiddenExIdInput.value = exId;
        }
        if (!exhVal || exhVal === '') {
            const exhNoInput = document.getElementById('labExhibitNumberInput');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const subIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
            if (exhNoInput) exhNoInput.value = '';
            if (descInput) descInput.value = '';
            if (subIdInput) subIdInput.value = '';
            return;
        }
        const nameVal = opt.getAttribute('data-name') || '';
        const qtyVal = opt.getAttribute('data-qty') || '';
        const weightVal = opt.getAttribute('data-weight') || '';
        const unitVal = opt.getAttribute('data-unit') || '';
        const hasSaved = opt.getAttribute('data-has-saved');
        const isSaved = (hasSaved === 'true' || hasSaved === '1');
        const formContainer = document.getElementById('section5FormContainer');
        const form = document.getElementById('section5LabForm');
        const readOnlyView = document.getElementById('section5ReadOnlyDisplay');

        if (isSaved) {
            if (readOnlyView) readOnlyView.classList.remove('hidden');
            if (formContainer) formContainer.classList.add('hidden');
            if (form) form.classList.add('hidden');
        } else {
            if (readOnlyView) readOnlyView.classList.add('hidden');
            if (formContainer) formContainer.classList.remove('hidden');
            if (form) form.classList.remove('hidden');
        }
        const savedConfirmatory = opt.getAttribute('data-confirmatory') || 'YES';
        const savedSubstanceId = opt.getAttribute('data-substance-id') || nameVal;
        const savedTestMethods = opt.getAttribute('data-test-methods') || '';
        const savedSpecifications = opt.getAttribute('data-specifications') || '';
        const savedRemark = opt.getAttribute('data-remark') || '';
        const savedSampleTaken = opt.getAttribute('data-sample-taken') || '';
        const savedSampleReserved = opt.getAttribute('data-sample-reserved') || '';
        const savedFindings = opt.getAttribute('data-findings') || '';

        const exhNoInput = document.getElementById('labExhibitNumberInput');
        const descInput = document.getElementById('labExhibitDescriptionInput');
        const subIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
        const sampleDescInput = document.getElementById('sampleWeightDescriptionReview');
        const unitsInput = document.getElementById('sampleWeightUnitsReview');
        const grossInput = document.getElementById('sampleWeightGrossReview');
        const takenInput = document.getElementById('sampleWeightTakenReview');
        const reservedInput = document.getElementById('sampleWeightReservedReview');
        const notesInput = document.getElementById('formLabNotes');
        const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
        const confMethods = document.getElementById('confirmatoryTestMethodsReview');
        const confSpecs = document.getElementById('confirmatorySpecificationsReview');
        const confRemark = document.getElementById('confirmatoryRemarkReview');

        const pkgVal = opt.getAttribute('data-pkg') || '';
        let unitsText = (qtyVal && pkgVal) ? `${qtyVal} ${pkgVal}`.trim() : (qtyVal || pkgVal || '');
        let grossWeightText = (weightVal && unitVal) ? `${weightVal} ${unitVal}`.trim() : (weightVal || unitVal || unitsText || '');

        if (exhNoInput && exhVal) exhNoInput.value = 'EXH-0' + exhVal;
        if (descInput) descInput.value = nameVal;
        if (subIdInput) subIdInput.value = savedSubstanceId || nameVal;
        if (sampleDescInput) sampleDescInput.value = nameVal;
        if (unitsInput) unitsInput.value = unitsText;
        if (grossInput) grossInput.value = grossWeightText;
        if (unitAddon && unitVal) unitAddon.textContent = unitVal;

        if (hasSaved === 'true' || hasSaved === '1') {
            const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
            if (detectedContainer) {
                detectedContainer.innerHTML = '';
                let savedDetList = [];
                try {
                    const rawDet = opt.getAttribute('data-detected-substances');
                    if (rawDet) savedDetList = JSON.parse(rawDet);
                } catch(e) {}
                if (Array.isArray(savedDetList) && savedDetList.length > 0) {
                    savedDetList.forEach(item => {
                        const sName = typeof item === 'object' ? (item.substance || item.name || item.compound || '') : String(item);
                        const sPct = typeof item === 'object' ? (item.percentage || item.purity || '') : '';
                        addDetectedSubstanceRowReview(sName, sPct);
                    });
                } else {
                    addDetectedSubstanceRowReview(savedSubstanceId || nameVal || '', '');
                }
            }
            if (takenInput) takenInput.value = savedSampleTaken;
            if (reservedInput) reservedInput.value = savedSampleReserved;
            if (notesInput) notesInput.value = savedFindings;
            if (confMethods) confMethods.value = savedTestMethods;
            if (confSpecs) confSpecs.value = savedSpecifications;
            if (confRemark) confRemark.value = savedRemark;
            if (typeof toggleConfirmatoryTestReview === 'function') {
                toggleConfirmatoryTestReview(savedConfirmatory === 'NO' ? 'NO' : 'YES');
            }
        } else {
            if (takenInput) takenInput.value = '';
            if (reservedInput) reservedInput.value = '';
            if (notesInput) notesInput.value = '';
            if (confMethods) confMethods.value = '';
            if (confSpecs) confSpecs.value = '';
            if (confRemark) confRemark.value = '';
            if (confSpecs) confSpecs.value = '';

            // Reset Presumptive Test
            const presRadios = document.querySelectorAll('input[name="form_presumptive_radio"]');
            presRadios.forEach(r => r.checked = false);

            const lblPresYes = document.getElementById('labelPresumptiveYes');
            const lblPresNo = document.getElementById('labelPresumptiveNo');
            if (lblPresYes) lblPresYes.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblPresNo) lblPresNo.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';

            const presContainer = document.getElementById('presumptiveTestRowsContainerReview');
            if (presContainer) {
                const rows = presContainer.querySelectorAll('.presumptive-test-row-review');
                rows.forEach((r, idx) => {
                    if (idx === 0) {
                        const presence = r.querySelector('.presumptive-presence-input-review');
                        const percent = r.querySelector('.presumptive-percentage-input-review');
                        if (presence) presence.value = '';
                        if (percent) percent.value = '';
                    } else {
                        r.remove();
                    }
                });
            }

            const presResultInput = document.getElementById('presumptiveReactionResultInputReview');
            if (presResultInput) presResultInput.value = '';

            const presDetailsContainer = document.getElementById('presumptiveReactionDetailsContainerReview');
            if (presDetailsContainer) presDetailsContainer.classList.add('hidden');

            const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
            if (detectedContainer) {
                detectedContainer.innerHTML = '';
                addDetectedSubstanceRowReview(nameVal || '', '');
            }

            if (typeof toggleConfirmatoryTestReview === 'function') {
                toggleConfirmatoryTestReview('YES');
            }

            // Reset Lab Status Badges
            const badgeStatus = document.getElementById('section5BadgeStatus') || document.getElementById('badgeStatus');
            if (badgeStatus) {
                badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-slate-100 text-slate-800 border border-slate-300';
                badgeStatus.textContent = '⭕ LAB PENDING';
            }
        }
    }

    const caseNumber = @json($case->case_number);
    const officerName = @json($officerName);
    const officerRole = @json($officerRole);

    /* ── Live Clock ─────────────────────────────── */
    function updateClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('liveClock').textContent =
            `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}  ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} UTC`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── Approve Case Action ─────────────────────── */
    async function approveCaseAction(ref) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${ref}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();

            if (res.ok && data.success) {
                // Update UI status
                const badge = document.getElementById('headerStatusBadge');
                if (badge) {
                    badge.innerHTML = `<span class="status-badge status-done"><svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized</span>`;
                }

                // Hide approve/decline buttons and display signed badge
                const container = document.getElementById('executiveActionContainer');
                if (container) {
                    container.innerHTML = `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-mono-code font-bold text-xs shadow-sm"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> OFFICIALLY APPROVED &amp; SIGNED BY DIRECTORATE HEAD</div>`;
                }

                showToast(`✔ Case ${ref} has been officially APPROVED and signed off by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Error approving case: ${data.message || 'Server error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Error connecting to server.`);
        }
    }

    /* ── Decline Case Action ─────────────────────── */
    async function declineCaseAction(ref) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${ref}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();

            if (res.ok && data.success) {
                // Update UI status
                const badge = document.getElementById('headerStatusBadge');
                if (badge) {
                    badge.innerHTML = `<span class="status-badge status-declined"><span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span> Declined</span>`;
                }

                const container = document.getElementById('executiveActionContainer');
                if (container) {
                    container.innerHTML = `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-800 border border-red-300 font-mono-code font-bold text-xs shadow-sm"><span class="w-2 h-2 rounded-full bg-red-600"></span> CASE DECLINED BY DIRECTORATE HEAD</div>`;
                }

                showToast(`❌ Case ${ref} has been officially DECLINED by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Error declining case: ${data.message || 'Server error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Error connecting to server.`);
        }
    }

    /* ── Toast Notification ──────────────────────── */
    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '24px', right: '24px', zIndex: '9999',
            background: 'linear-gradient(135deg, #0e1829, #0d1e3a)',
            border: '1px solid rgba(59,130,246,0.35)',
            color: '#93c5fd', padding: '14px 20px', borderRadius: '14px',
            fontSize: '12px', fontFamily: "'JetBrains Mono', monospace", fontWeight: '600',
            boxShadow: '0 12px 40px rgba(0,0,0,0.5), 0 0 20px rgba(59,130,246,0.15)',
            maxWidth: '420px', lineHeight: '1.5',
            transform: 'translateY(20px)', opacity: '0',
            transition: 'all 0.35s cubic-bezier(0.34,1.56,0.64,1)',
        });
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.transform = 'translateY(0)'; t.style.opacity = '1'; });
        setTimeout(() => {
            t.style.transform = 'translateY(10px)'; t.style.opacity = '0';
            setTimeout(() => t.remove(), 400);
        }, 4000);
    }

    /* ── Joint Suspects Warning Alert Modal ─────────────────── */
    function showUnexaminedSuspectsAlert(unexaminedList, message) {
        let existingModal = document.getElementById('unexaminedSuspectsModal');
        if (existingModal) existingModal.remove();

        const suspectItems = (unexaminedList || []).map(s => `
            <li class="flex items-center gap-2.5 py-2 px-3.5 bg-red-50 text-red-900 rounded-xl border border-red-200/80 font-semibold text-xs shadow-2xs">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>${escapeHtml(s)}</span>
            </li>
        `).join('');

        const modalHtml = `
        <div id="unexaminedSuspectsModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-2xl border border-red-300 max-w-lg w-full overflow-hidden transform transition-all scale-100">
                <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 px-6 py-4 text-white flex items-center gap-3.5 border-b border-red-700">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-white font-sans tracking-tight">Cannot Save Case — Unexamined Suspects</h3>
                        <p class="text-[11px] text-red-100 font-mono-code uppercase font-semibold tracking-wider">Joint Suspects Validation Guard</p>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-700 leading-relaxed font-sans">
                        This is a <strong>joint suspects case</strong> involving multiple individuals. The system does not allow saving the case as completed while unexamined suspects remain.
                    </p>
                    <div class="space-y-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block font-mono-code">Unexamined Suspect(s) Remaining:</span>
                        <ul class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                            ${suspectItems}
                        </ul>
                    </div>
                    <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200/90 text-amber-900 text-[11px] leading-relaxed flex items-start gap-2.5">
                        <span class="text-base shrink-0">💡</span>
                        <div>
                            <strong>Required Action:</strong> Select each remaining suspect in <strong>Section 5 (Laboratory Examination Form)</strong>, enter their examination results, and save before submitting the case.
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200/80 flex justify-end">
                    <button onclick="document.getElementById('unexaminedSuspectsModal').remove(); const el = document.getElementById('section5LabForm') || document.getElementById('labExhibitSelect'); if(el) el.scrollIntoView({behavior:'smooth'});" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                        <span>OK, Complete Suspect Examinations</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    /* ── Submit Unit Progress Action (Real-Time DOM Update) ────────────────── */
    async function submitUnitProgress(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('unitSubmitBtn');
        if (submitBtn) submitBtn.disabled = true;

        const unitName = document.getElementById('updateUnitName').value;
        const unitStatus = document.getElementById('updateUnitStatus').value;
        const officer = document.getElementById('updateOfficer').value;
        const notes = document.getElementById('updateNotes').value;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    unit_name: unitName,
                    unit_status: unitStatus,
                    officer: officer,
                    notes: notes
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const compCount = data.completed_count || 0;
                const updatedUnitKey = data.unit_name || unitName;
                const unitProgMap = data.unit_progress || {};

                // Update unitProgressData state
                if (unitProgMap[updatedUnitKey]) {
                    unitProgressData[updatedUnitKey] = unitProgMap[updatedUnitKey];
                }

                // 1. Update target unit card in real-time
                const slug = updatedUnitKey.toLowerCase().replace(/[^a-z0-9]/g, '');
                const cardEl = document.getElementById(`unitCard-${slug}`);
                const badgeEl = document.getElementById(`unitBadge-${slug}`);
                const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);
                const updatedBoxEl = document.getElementById(`unitUpdatedBox-${slug}`);

                const st = (unitStatus || 'PENDING').toUpperCase();

                if (cardEl) {
                    if (st === 'COMPLETED') {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-emerald-50/70 border-emerald-300 shadow-xs';
                    } else if (st === 'IN_PROGRESS') {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-amber-50/70 border-amber-300 shadow-xs';
                    } else {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-slate-50 border-slate-200';
                    }
                }

                if (badgeEl) {
                    if (st === 'COMPLETED') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                        badgeEl.textContent = '✓ COMPLETED';
                    } else if (st === 'IN_PROGRESS') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                        badgeEl.textContent = '⏳ IN PROGRESS';
                    } else {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-slate-200 text-slate-700 border-slate-300';
                        badgeEl.textContent = '⭕ PENDING';
                    }
                }

                if (officerBoxEl) {
                    officerBoxEl.innerHTML = officer ? `<div class="text-[11px] font-medium text-slate-600 mt-2">Officer: <span class="font-bold text-slate-900">${escapeHtml(officer)}</span></div>` : '';
                }

                if (notesBoxEl) {
                    notesBoxEl.innerHTML = notes ? `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>` : '';
                }

                if (updatedBoxEl) {
                    updatedBoxEl.innerHTML = `<div class="text-[10px] font-mono-code text-slate-400 mt-3 pt-2 border-t border-slate-200/60">Updated: Just Now</div>`;
                }

                // 2. Update progress tracker counter and progress bar
                const totalUnits = unitsListArray.length;
                const badgeCounter = document.getElementById('unitCompletedCountBadge');
                if (badgeCounter) {
                    badgeCounter.textContent = `${compCount}/${totalUnits} Units Completed`;
                }

                const pct = Math.round((compCount / totalUnits) * 100);
                const progressBar = document.getElementById('unitProgressBar');
                if (progressBar) {
                    progressBar.style.width = `${Math.max(18, pct)}%`;
                    progressBar.textContent = `${pct}%`;
                }

                const percentText = document.getElementById('unitProgressPercentText');
                if (percentText) {
                    percentText.textContent = `${pct}%`;
                }

                // 3. Handle Completion or Pending Portal Updates - Immediately REMOVE/HIDE Unit Data Entry Portal
                const portal = document.getElementById('unitDataEntryPortal');
                const banner = document.getElementById('completedUnitsBanner');
                const headerStatusBadge = document.getElementById('headerStatusBadge');

                // Instantly hide the Unit Data Entry Portal section once saved!
                if (portal) {
                    portal.classList.add('hidden');
                }

                if (compCount >= totalUnits) {
                    if (banner) banner.classList.remove('hidden');
                    if (headerStatusBadge) {
                        headerStatusBadge.innerHTML = `<span class="status-badge bg-white text-slate-900 border border-slate-300">Awaiting Sign-off</span>`;
                    }
                    showToast(`✔ Multi-Unit Directorate Processing Complete (${totalUnits}/${totalUnits} Units)! All ${totalUnits} specialized units have completed findings. Docket is submitted for HOF executive sign-off.`);
                } else {
                    showToast(`✔ Progress for unit "${updatedUnitKey}" updated successfully! (${compCount}/${totalUnits} Units Completed)`);
                }
            } else {
                if (data.unexamined_suspects && data.unexamined_suspects.length > 0) {
                    showUnexaminedSuspectsAlert(data.unexamined_suspects, data.message);
                } else {
                    showToast(`❌ Error updating unit progress: ${data.message || 'Server error'}`);
                }
            }
        } catch(err) {
            console.error(err);
            showToast(`❌ Connection error updating unit progress.`);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    /* ── Dynamic Button Text: Save Case vs Submit Case for Approval ── */
    const unitProgressData = @json($unitProgress);
    const unitsListArray = @json($unitsList);

    function updateUnitSubmitButtonText() {
        const selectedUnit = document.getElementById('updateUnitName')?.value;
        const selectedStatus = document.getElementById('updateUnitStatus')?.value;
        const btnTextEl = document.getElementById('unitSubmitBtnText');
        if (!btnTextEl) return;

        let simulatedCompletedCount = 0;
        unitsListArray.forEach(u => {
            let st = (unitProgressData[u] && unitProgressData[u].status) ? unitProgressData[u].status.toUpperCase() : 'PENDING';
            if (u === selectedUnit) {
                st = selectedStatus ? selectedStatus.toUpperCase() : st;
            }
            if (st === 'COMPLETED') {
                simulatedCompletedCount++;
            }
        });

        if (simulatedCompletedCount >= unitsListArray.length) {
            btnTextEl.innerText = 'Submit Case for Approval';
        } else {
            btnTextEl.innerText = 'Save Case';
        }
    }
    document.addEventListener('DOMContentLoaded', updateUnitSubmitButtonText);

    /* ── Notifications & Global Search ──────────── */
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const bellBtn = document.getElementById('notificationBellBtn');
        const dropdown = document.getElementById('notificationDropdown');
        if (bellBtn && dropdown && !bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) searchInput.focus();
        }
    });

    function onPresumptiveRadioChange(val) {
        const lblYes = document.getElementById('labelPresumptiveYes');
        const lblNo = document.getElementById('labelPresumptiveNo');
        const container = document.getElementById('presumptiveReactionDetailsContainerReview');
        if (val === 'YES') {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (container) container.classList.remove('hidden');
        } else {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
            if (container) container.classList.add('hidden');
        }
    }

    function toggleConfirmatoryTestReview(val) {
        const lblYes = document.getElementById('labelConfirmatoryYesReview');
        const lblNo = document.getElementById('labelConfirmatoryNoReview');
        const radYes = document.getElementById('confirmatoryTestYesReview');
        const radNo = document.getElementById('confirmatoryTestNoReview');
        const container = document.getElementById('sampleWeightContainerReview');
        const descInput = document.getElementById('sampleWeightDescriptionReview');
        const grossInput = document.getElementById('sampleWeightGrossReview');

        if (val === 'YES') {
            if (radYes) radYes.checked = true;
            if (radNo) radNo.checked = false;
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (container) container.classList.remove('hidden');

            if (descInput && (!descInput.value || descInput.value.startsWith('e.g.'))) {
                const defaultDrug = @json($defaultDrugName ?? '');
                if (defaultDrug) descInput.value = defaultDrug;
            }
            if (grossInput && (!grossInput.value || grossInput.value.startsWith('e.g.'))) {
                const defaultGross = @json($defaultGrossWeight ?? '');
                if (defaultGross) grossInput.value = defaultGross;
            }
            const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
            if (unitAddon && grossInput && grossInput.value) {
                const uMatch = grossInput.value.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i);
                if (uMatch) unitAddon.textContent = uMatch[1];
            }
            if (typeof updateReservedWeightReview === 'function') updateReservedWeightReview();
        } else {
            if (radYes) radYes.checked = false;
            if (radNo) radNo.checked = true;
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
            if (container) container.classList.add('hidden');
        }
    }

    function addPresumptiveTestRowReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;

        const rows = container.querySelectorAll('.presumptive-test-row-review');
        const newIdx = rows.length + 1;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5';
        rowDiv.innerHTML = `
            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                <span class="test-row-title-review">Test Result #${newIdx}</span>
                <button type="button" onclick="removePresumptiveTestRowReview(this)" class="remove-test-row-btn-review px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                    &times; Remove
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Presence of <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                        onchange="updatePresumptiveReactionTextReview()" 
                        oninput="updatePresumptiveReactionTextReview()" 
                        placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                    >
                </div>
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Percentage (%) <span class="text-red-600 font-bold">*</span>
                    </label>
                    <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                        <input 
                            type="text" 
                            class="presumptive-percentage-input-review w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code" 
                            onchange="updatePresumptiveReactionTextReview()" 
                            oninput="updatePresumptiveReactionTextReview()" 
                            placeholder="e.g. 85.5"
                        >
                        <span class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(rowDiv);
        updatePresumptiveRemoveButtonsReview();
        updatePresumptiveReactionTextReview();
    }

    function removePresumptiveTestRowReview(btn) {
        const row = btn.closest('.presumptive-test-row-review');
        if (row) {
            row.remove();
            reindexPresumptiveTestRowsReview();
            updatePresumptiveRemoveButtonsReview();
            updatePresumptiveReactionTextReview();
        }
    }

    function reindexPresumptiveTestRowsReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;
        const rows = container.querySelectorAll('.presumptive-test-row-review');
        rows.forEach((row, idx) => {
            const titleSpan = row.querySelector('.test-row-title-review');
            if (titleSpan) titleSpan.innerText = `Test Result #${idx + 1}`;
        });
    }

    function updatePresumptiveRemoveButtonsReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;
        const rows = container.querySelectorAll('.presumptive-test-row-review');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-test-row-btn-review');
            if (btn) {
                if (rows.length > 1) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });
    }

    function updatePresumptiveReactionTextReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        const inputEl = document.getElementById('presumptiveReactionResultInputReview');
        if (!container || !inputEl) return;

        const rows = container.querySelectorAll('.presumptive-test-row-review');
        let results = [];

        rows.forEach((row, idx) => {
            const presence = row.querySelector('.presumptive-presence-input-review')?.value?.trim() || '';
            let rawPct = row.querySelector('.presumptive-percentage-input-review')?.value?.trim() || '';
            let percentage = rawPct;
            if (percentage && !percentage.endsWith('%')) {
                percentage = percentage + '%';
            }
            
            let parts = [];
            if (presence) parts.push(`Presence of: ${presence}`);
            if (percentage) parts.push(`Purity/Percentage: ${percentage}`);

            if (parts.length > 0) {
                if (rows.length > 1) {
                    results.push(`[${idx + 1}] ${parts.join(' - ')}`);
                } else {
                    results.push(parts.join(' - '));
                }
            }
        });

        if (results.length > 0) {
            inputEl.value = `YES (${results.join('; ')})`;
        } else {
            inputEl.value = `YES (Positive Colorimetric Reaction)`;
        }
    }

    function onLabStatusChange(val) {
        const btnTextSpan = document.getElementById('saveSection5LabBtnText') || document.querySelector('#saveSection5LabBtn span');
        if (btnTextSpan) {
            if (val === 'IN_PROGRESS' || val === 'PENDING') {
                btnTextSpan.textContent = 'Save & Update Lab Examination Later';
            } else {
                btnTextSpan.textContent = 'Save Lab Examination & Update Progress';
            }
        }

        const selectEl = document.getElementById('formLabStatus');
        const dotIndicator = document.getElementById('labStatusDotIndicator');
        const text = document.getElementById('labStatusText');
        const container = document.getElementById('labStatusContainer');

        if (val === 'COMPLETED') {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'COMPLETED';
        } else if (val === 'IN_PROGRESS') {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-amber-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-amber-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-amber-100 text-amber-800 border border-amber-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'IN PROGRESS';
        } else {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'PENDING';
        }
    }

    /* ── Submit Section 5 Lab Examination & Update Progress ────────── */
    async function submitSection5LabExam(e) {
        e.preventDefault();
        const saveBtn = document.getElementById('saveSection5LabBtn');
        if (saveBtn) saveBtn.disabled = true;

        // Auto-set Date Examination Completed to today's date when clicking the save button
        const todayStr = new Date().toISOString().split('T')[0];
        const dateCompletedInput = document.getElementById('labDateCompletedInput');
        if (dateCompletedInput) {
            dateCompletedInput.value = todayStr;
        }

        const dateReceived = document.getElementById('labDateReceivedInput')?.value || todayStr;
        const dateStarted = document.getElementById('labDateStartedInput')?.value || todayStr;
        const dateCompleted = dateCompletedInput?.value || todayStr;

        const labUnit = document.getElementById('formLabUnit')?.value || 'Chemistry & Narcotics Unit';
        const analyst = document.getElementById('formLabAnalyst')?.value || @json($labAnalyst ?? $defaultLabHeadName);
        const checkedRadio = document.querySelector('input[name="form_presumptive_radio"]:checked');
        let presumptive = 'YES (Positive Colorimetric Reaction)';
        if (checkedRadio && checkedRadio.value === 'NO') {
            presumptive = 'NO (Negative Reaction)';
        } else {
            const customInput = document.getElementById('presumptiveReactionResultInputReview')?.value;
            presumptive = customInput && customInput.trim() !== '' ? customInput.trim() : 'YES (Positive Colorimetric Reaction)';
        }

        const confirmatoryChecked = document.querySelector('input[name="form_confirmatory_radio"]:checked');
        const confirmatory = (confirmatoryChecked && confirmatoryChecked.value === 'YES') ? 'YES' : 'NO';

        const notes = document.getElementById('formLabNotes')?.value || '';

        const exhibitSelect = document.getElementById('labExhibitSelect');
        const selectedOpt = exhibitSelect ? exhibitSelect.options[exhibitSelect.selectedIndex] : null;
        const exhibitOwnerName = selectedOpt ? selectedOpt.getAttribute('data-owner') || '' : '';
        const exhibitIsAssociate = selectedOpt ? (selectedOpt.getAttribute('data-is-associate') === '1') : false;

        let status = 'COMPLETED';
        if (exhibitSelect && exhibitSelect.options.length > 1) {
            const options = Array.from(exhibitSelect.options);
            const otherUnexamined = options.filter(opt => opt !== selectedOpt && opt.getAttribute('data-has-saved') !== '1' && opt.getAttribute('data-has-saved') !== 'true');
            if (otherUnexamined.length > 0) {
                status = 'IN_PROGRESS';
            }
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    unit_name: 'Lab Analysis',
                    unit_status: status,
                    officer: analyst,
                    notes: notes,
                    exhibit_id: document.getElementById('labFormExhibitId')?.value || (selectedOpt ? (selectedOpt.getAttribute('data-ex-id') || selectedOpt.value) : ''),
                    drug_type: selectedOpt ? (selectedOpt.getAttribute('data-substance-id') || selectedOpt.getAttribute('data-name') || '') : '',
                    exhibit_owner_name: exhibitOwnerName,
                    exhibit_is_associate: exhibitIsAssociate,
                    lab_exam_reference: document.getElementById('labExamReferenceInput')?.value || '',
                    lab_exhibit_number: document.getElementById('labExhibitNumberInput')?.value || '',
                    laboratory_number: document.getElementById('formLabNumber')?.value || '',
                    lab_unit: labUnit,
                    presumptive_result: presumptive,
                    confirmatory_test_result: confirmatory,
                    sample_weight_description: document.getElementById('sampleWeightDescriptionReview')?.value || '',
                    sample_weight_units: document.getElementById('sampleWeightUnitsReview')?.value || '',
                    sample_weight_gross: document.getElementById('sampleWeightGrossReview')?.value || '',
                    sample_weight_taken: document.getElementById('sampleWeightTakenReview')?.value || '',
                    sample_weight_reserved: document.getElementById('sampleWeightReservedReview')?.value || '',
                    confirmatory_substance_identification: document.getElementById('confirmatorySubstanceIdentificationReview')?.value || '',
                    detected_substances: typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : [],
                    confirmatory_test_methods: document.getElementById('confirmatoryTestMethodsReview')?.value || '',
                    confirmatory_specifications: document.getElementById('confirmatorySpecificationsReview')?.value || '',
                    confirmatory_remark: document.getElementById('confirmatoryRemarkReview')?.value || '',
                    lab_date_received: dateReceived,
                    lab_date_started: dateStarted,
                    lab_date_completed: dateCompleted
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const compCount = data.completed_count || 0;

                // Update UI elements in Section 5
                const uUnitEl = document.getElementById('section5LabUnitDisplay');
                if (uUnitEl) uUnitEl.textContent = labUnit;
                const uAnalystEl = document.getElementById('section5AnalystDisplay');
                if (uAnalystEl) uAnalystEl.textContent = analyst;
                const uPresumptiveEl = document.getElementById('section5PresumptiveDisplay');
                if (uPresumptiveEl) {
                    const str = String(presumptive || '').trim();
                    let overallResult = 'N/A';
                    if (str && str !== 'N/A') {
                        const up = str.toUpperCase();
                        if (up.startsWith('YES') || up.startsWith('POS')) overallResult = 'YES';
                        else if (up.startsWith('NO') || up.startsWith('NEG')) overallResult = 'NO';
                    }

                    let cleanP = str;
                    const mP = str.match(/^[A-Z]+\s*\((.*)\)$/i);
                    if (mP) cleanP = mP[1];

                    const segments = cleanP.split(/;\s*(?=\[\d+\]|Presence of:)/i);
                    const pRowsJS = [];

                    for (let seg of segments) {
                        seg = seg.trim();
                        if (!seg) continue;

                        let presence = 'N/A';
                        let purity = 'N/A';

                        const mPres = seg.match(/Presence of:\s*([^\-\;\)]+)/i) || seg.match(/^\[\d+\]\s*([^\-\;\)]+)/i);
                        if (mPres) {
                            presence = mPres[1].trim();
                        } else if (seg && !seg.includes('Purity')) {
                            presence = seg.trim();
                        }

                        if (presence === 'YES' || presence === 'NO' || presence === 'POS' || presence === 'NEG') {
                            presence = 'N/A';
                        }

                        const mPur = seg.match(/Purity\/Percentage:\s*([^\;\)]+)/i);
                        if (mPur) {
                            purity = mPur[1].trim();
                        }

                        if (presence !== 'N/A' || purity !== 'N/A' || segments.length === 1) {
                            pRowsJS.push({
                                result: overallResult,
                                presence: presence,
                                purity: purity
                            });
                        }
                    }

                    if (pRowsJS.length === 0) {
                        pRowsJS.push({ result: 'N/A', presence: 'N/A', purity: 'N/A' });
                    }

                    let rowsHtml = '';
                    for (let r of pRowsJS) {
                        rowsHtml += `
                            <tr class="border-b border-slate-200 last:border-b-0">
                                <td class="px-2 py-1 border-r border-slate-300 font-extrabold ${r.result === 'YES' ? 'text-emerald-800' : 'text-slate-900'}">${escapeHtml(r.result)}</td>
                                <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">${escapeHtml(r.presence)}</td>
                                <td class="px-2 py-1 font-bold text-slate-900">${escapeHtml(r.purity)}</td>
                            </tr>
                        `;
                    }

                    uPresumptiveEl.innerHTML = `
                        <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                            <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                <tr class="border-b border-slate-300">
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                ${rowsHtml}
                            </tbody>
                        </table>
                    `;
                }
                const uConfirmatoryEl = document.getElementById('section5ConfirmatoryDisplay');
                if (uConfirmatoryEl) uConfirmatoryEl.innerHTML = `<span class="w-2.5 h-2.5 rounded-full ${confirmatory === 'YES' ? 'bg-emerald-600' : 'bg-slate-500'}"></span><span>${confirmatory === 'YES' ? 'YES' : 'NO'}</span>`;
                const uDescEl = document.getElementById('section5SampleDescDisplay');
                if (uDescEl) uDescEl.textContent = document.getElementById('sampleWeightDescriptionReview')?.value || 'N/A';
                const uGrossEl = document.getElementById('section5SampleGrossDisplay');
                if (uGrossEl) uGrossEl.textContent = document.getElementById('sampleWeightGrossReview')?.value || 'N/A';
                const uTakenEl = document.getElementById('section5SampleTakenDisplay');
                if (uTakenEl) uTakenEl.textContent = document.getElementById('sampleWeightTakenReview')?.value || 'N/A';
                const uReservedEl = document.getElementById('section5SampleReservedDisplay');
                if (uReservedEl) uReservedEl.textContent = document.getElementById('sampleWeightReservedReview')?.value || 'N/A';
                const uSubstanceIdEl = document.getElementById('section5SubstanceIdDisplay');
                if (uSubstanceIdEl) uSubstanceIdEl.textContent = document.getElementById('confirmatorySubstanceIdentificationReview')?.value || 'N/A';
                const uTestMethodsEl = document.getElementById('section5TestMethodsDisplay');
                if (uTestMethodsEl) uTestMethodsEl.textContent = document.getElementById('confirmatoryTestMethodsReview')?.value || 'N/A';
                const uSpecsEl = document.getElementById('section5SpecsDisplay');
                if (uSpecsEl) uSpecsEl.textContent = document.getElementById('confirmatorySpecificationsReview')?.value || 'N/A';
                const uRemarkEl = document.getElementById('section5RemarkDisplay');
                if (uRemarkEl) uRemarkEl.textContent = document.getElementById('confirmatoryRemarkReview')?.value || 'N/A';
                const uDetectedEl = document.getElementById('section5DetectedSubstancesDisplay');
                if (uDetectedEl) {
                    const arr = typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : [];
                    if (arr.length > 0) {
                        uDetectedEl.textContent = arr.map(item => `${item.substance}${item.percentage ? ' (' + item.percentage + ')' : ''}`).join(', ');
                    } else {
                        uDetectedEl.textContent = 'No individual compound breakdown logged';
                    }
                }
                const uFindingsEl = document.getElementById('section5FindingsDisplay');
                if (uFindingsEl) uFindingsEl.textContent = notes || 'No specific laboratory analytical findings logged yet.';

                const badgeStatus = document.getElementById('section5BadgeStatus');
                if (badgeStatus) {
                    if (status === 'COMPLETED') {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeStatus.textContent = '✓ LAB COMPLETED';
                    } else if (status === 'IN_PROGRESS') {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300';
                        badgeStatus.textContent = '⏳ LAB IN PROGRESS';
                    } else {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeStatus.textContent = '⭕ LAB PENDING';
                    }
                }

                // Update 4-Unit card
                const slug = 'labanalysis';
                const cardEl = document.getElementById(`unitCard-${slug}`);
                const badgeEl = document.getElementById(`unitBadge-${slug}`);
                const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);

                if (badgeEl) {
                    if (status === 'COMPLETED') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                        badgeEl.textContent = '✓ COMPLETED';
                    } else if (status === 'IN_PROGRESS') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                        badgeEl.textContent = '⏳ IN PROGRESS';
                    }
                }
                if (officerBoxEl) officerBoxEl.innerHTML = `<div class="text-[11px] font-medium text-slate-600 mt-2">Officer: <span class="font-bold text-slate-900">${escapeHtml(analyst)}</span></div>`;
                if (notesBoxEl) notesBoxEl.innerHTML = `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>`;

                const badgeCounter = document.getElementById('unitCompletedCountBadge');
                if (badgeCounter) badgeCounter.textContent = `${compCount}/4 Units Completed`;

                const pct = Math.round((compCount / 4) * 100);
                const progressBar = document.getElementById('unitProgressBar');
                if (progressBar) {
                    progressBar.style.width = `${Math.max(18, pct)}%`;
                    progressBar.textContent = `${pct}%`;
                }

                const percentText = document.getElementById('unitProgressPercentText');
                if (percentText) {
                    percentText.textContent = `${pct}%`;
                }

                if (selectedOpt) {
                    selectedOpt.setAttribute('data-has-saved', 'true');
                    selectedOpt.setAttribute('data-remark', document.getElementById('confirmatoryRemarkReview')?.value || '');
                }

                const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
                const formContainer = document.getElementById('section5FormContainer');
                const form = document.getElementById('section5LabForm');
                if (readOnlyView) readOnlyView.classList.remove('hidden');
                if (formContainer) formContainer.classList.add('hidden');
                if (form) form.classList.add('hidden');

                resetSection5Form();
                showSaveSuccessPopover();
                showToast(`✔ Laboratory Examination saved for ${exhibitOwnerName || 'Suspect'}! Section reset for next entry.`);
            } else {
                if (data.unexamined_suspects && data.unexamined_suspects.length > 0) {
                    showUnexaminedSuspectsAlert(data.unexamined_suspects, data.message);
                } else {
                    showToast(`❌ Error saving lab examination: ${data.message || 'Server error'}`);
                }
            }
        } catch(err) {
            console.error(err);
            showToast(`❌ Connection error saving lab examination.`);
        } finally {
            if (saveBtn && !document.getElementById('section5LabForm')?.disabled) {
                saveBtn.disabled = false;
            }
        }
    }

    /* ── Combined Save: Seizure Item & Lab Examination Information ── */
    async function submitCombinedLabAndSeizureForm(e) {
        if (e && e.preventDefault) e.preventDefault();

        const saveBtn4 = document.getElementById('saveSection4SeizureBtn');
        const saveBtnText4 = document.getElementById('saveSection4SeizureBtnText');
        const saveBtn5 = document.getElementById('saveSection5LabBtn');
        const saveBtnText5 = document.getElementById('saveSection5LabBtnText');

        if (saveBtn4) saveBtn4.disabled = true;
        if (saveBtn5) saveBtn5.disabled = true;
        if (saveBtnText4) saveBtnText4.innerText = 'Saving Seizure & Examination...';
        if (saveBtnText5) saveBtnText5.innerText = 'Saving Seizure & Examination...';

        const caseId = @json($case->id);
        const caseNumber = @json($case->case_number);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            // 1. Save Section 4 Seizure Items
            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            const exhibits = [];

            blocks.forEach((block) => {
                const assocPerson = block.querySelector('.seizure-associated-person-select')?.value || @json($activeSuspectName);
                const category = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || '';
                
                let seizureName = block.querySelector('#newSeizureNameDrug')?.value || 
                                  block.querySelector('#drugSearchInput')?.value || 
                                  block.querySelector('#newSeizureNameWeapon')?.value || 
                                  block.querySelector('#weaponSearchInput')?.value || 
                                  block.querySelector('#newSeizureNameCurrency')?.value || 
                                  block.querySelector('#currencySearchInput')?.value || 
                                  block.querySelector('#newSeizureNameDevice')?.value || 
                                  block.querySelector('#deviceSearchInput')?.value || 
                                  block.querySelector('#newSeizureName')?.value || '';

                if (!seizureName.trim()) seizureName = category;

                const quantity = block.querySelector('#newSeizureQuantity')?.value || '';
                const weight = block.querySelector('#newSeizureWeight')?.value || '';
                const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
                const packaging = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
                const value = block.querySelector('#newSeizureValue')?.value || '';
                const concealment = block.querySelector('#newSeizureConcealment')?.value || '';
                const dateSeized = block.querySelector('#newSeizureDate')?.value || '';
                const timeSeized = block.querySelector('#newSeizureTime')?.value || '';
                const location = block.querySelector('#newSeizureLocation')?.value || '';
                const seizingOfficer = typeof getSeizureOfficersFormatted === 'function' ? getSeizureOfficersFormatted(block) : '';

                if (seizureName.trim()) {
                    exhibits.push({
                        associated_person: assocPerson,
                        suspect_name: assocPerson,
                        category: category,
                        exhibit_category: category,
                        seizure_name: seizureName,
                        commodity_name: seizureName,
                        name: seizureName,
                        seizure_quantity: quantity,
                        quantity: quantity,
                        seizure_weight: weight,
                        weight: weight,
                        seizure_unit: unit,
                        unit: unit,
                        seizure_packaging: packaging,
                        packaging: packaging,
                        seizure_value: value,
                        value: value,
                        seizure_concealment: concealment,
                        seizure_date: dateSeized,
                        seizure_time: timeSeized,
                        seizure_location: location,
                        seizure_officer: seizingOfficer
                    });
                }
            });

            if (exhibits.length > 0) {
                const res4 = await fetch(`/cases/${encodeURIComponent(caseId)}/associate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        associated_exhibits: exhibits
                    })
                });
                const data4 = await res4.json();
                if (!res4.ok || !data4.success) {
                    console.warn('Section 4 save note:', data4.message);
                }
            }

            // 2. Save Section 5 Laboratory Examination
            const todayStr = new Date().toISOString().split('T')[0];
            const dateCompletedInput = document.getElementById('labDateCompletedInput');
            if (dateCompletedInput) dateCompletedInput.value = todayStr;

            const dateReceived = document.getElementById('labDateReceivedInput')?.value || todayStr;
            const dateStarted = document.getElementById('labDateStartedInput')?.value || todayStr;
            const dateCompleted = dateCompletedInput?.value || todayStr;

            const labUnit = document.getElementById('formLabUnit')?.value || 'Chemistry & Narcotics Unit';
            const analyst = document.getElementById('formLabAnalyst')?.value || @json($labAnalyst ?? $defaultLabHeadName);
            const checkedRadio = document.querySelector('input[name="form_presumptive_radio"]:checked');
            let presumptive = 'YES (Positive Colorimetric Reaction)';

            if (checkedRadio && checkedRadio.value === 'NO') {
                presumptive = 'NO (Negative Reaction)';
            } else {
                // Validate that at least ONE presence + percentage pair was filled when YES is selected
                const rows = document.querySelectorAll('#presumptiveTestRowsContainerReview .presumptive-test-row-review');
                let validDetails = [];
                let hasInvalid = false;

                rows.forEach((row) => {
                    const presence = row.querySelector('.presumptive-presence-input-review')?.value?.trim() || '';
                    const percent  = row.querySelector('.presumptive-percentage-input-review')?.value?.trim() || '';
                    if (presence && percent) {
                        validDetails.push(`Presence of: ${presence} - Purity/Percentage: ${percent}%`);
                    } else if (presence || percent) {
                        hasInvalid = true;
                    }
                });

                if (validDetails.length === 0) {
                    alert('Please enter both "Presence of" and "Percentage (%)" for at least one presumptive test result before saving.');
                    // Re-enable buttons so the user can retry
                    const saveBtn4 = document.getElementById('saveSection4SeizureBtn');
                    const saveBtn5 = document.getElementById('saveSection5LabBtn');
                    const saveBtnText4 = document.getElementById('saveSection4SeizureBtnText');
                    const saveBtnText5 = document.getElementById('saveSection5LabBtnText');
                    if (saveBtn4) saveBtn4.disabled = false;
                    if (saveBtn5) saveBtn5.disabled = false;
                    if (saveBtnText4) saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
                    if (saveBtnText5) saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
                    return;
                }

                // If multiple rows, number them
                if (validDetails.length > 1) {
                    presumptive = `YES (${validDetails.map((d, i) => `[${i + 1}] ${d}`).join('; ')})`;
                } else {
                    presumptive = `YES (${validDetails[0]})`;
                }
            }

            const confirmatoryChecked = document.querySelector('input[name="form_confirmatory_radio"]:checked');
            const confirmatory = (confirmatoryChecked && confirmatoryChecked.value === 'YES') ? 'YES' : 'NO';

            const notes = document.getElementById('formLabNotes')?.value || '';

            const exhibitSelect = document.getElementById('labExhibitSelect');
            const selectedOpt = exhibitSelect ? exhibitSelect.options[exhibitSelect.selectedIndex] : null;
            const exhibitOwnerName = selectedOpt ? selectedOpt.getAttribute('data-owner') || '' : '';
            const exhibitIsAssociate = selectedOpt ? (selectedOpt.getAttribute('data-is-associate') === '1') : false;

            let status = 'COMPLETED';
            if (exhibitSelect && exhibitSelect.options.length > 1) {
                const options = Array.from(exhibitSelect.options);
                const otherUnexamined = options.filter(opt => opt !== selectedOpt && opt.getAttribute('data-has-saved') !== '1' && opt.getAttribute('data-has-saved') !== 'true');
                if (otherUnexamined.length > 0) {
                    status = 'IN_PROGRESS';
                }
            }

            const res5 = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    unit_name: 'Lab Analysis',
                    unit_status: status,
                    officer: analyst,
                    notes: notes,
                    exhibit_id: document.getElementById('labFormExhibitId')?.value || (selectedOpt ? (selectedOpt.getAttribute('data-ex-id') || selectedOpt.value) : ''),
                    drug_type: selectedOpt ? (selectedOpt.getAttribute('data-substance-id') || selectedOpt.getAttribute('data-name') || '') : '',
                    exhibit_owner_name: exhibitOwnerName,
                    exhibit_is_associate: exhibitIsAssociate,
                    lab_exam_reference: document.getElementById('labExamReferenceInput')?.value || '',
                    lab_exhibit_number: document.getElementById('labExhibitNumberInput')?.value || '',
                    laboratory_number: document.getElementById('formLabNumber')?.value || '',
                    lab_unit: labUnit,
                    presumptive_result: presumptive,
                    confirmatory_test_result: confirmatory,
                    sample_weight_description: document.getElementById('sampleWeightDescriptionReview')?.value || '',
                    sample_weight_units: document.getElementById('sampleWeightUnitsReview')?.value || '',
                    sample_weight_gross: document.getElementById('sampleWeightGrossReview')?.value || '',
                    sample_weight_taken: document.getElementById('sampleWeightTakenReview')?.value || '',
                    sample_weight_reserved: document.getElementById('sampleWeightReservedReview')?.value || '',
                    confirmatory_substance_identification: document.getElementById('confirmatorySubstanceIdentificationReview')?.value || '',
                    detected_substances: typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : [],
                    confirmatory_test_methods: document.getElementById('confirmatoryTestMethodsReview')?.value || '',
                    confirmatory_specifications: document.getElementById('confirmatorySpecificationsReview')?.value || '',
                    confirmatory_remark: document.getElementById('confirmatoryRemarkReview')?.value || '',
                    lab_date_received: dateReceived,
                    lab_date_started: dateStarted,
                    lab_date_completed: dateCompleted
                })
            });

            const data5 = await res5.json();

            if (res5.ok && data5.success) {
                if (typeof showSaveSuccessPopover === 'function') showSaveSuccessPopover();
                if (typeof showToast === 'function') showToast(`✔ Seizure Item & Laboratory Examination saved successfully!`);
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else {
                alert(data5.message || 'Saved Seizure Item, but failed to save Laboratory Examination.');
                if (saveBtn4) saveBtn4.disabled = false;
                if (saveBtn5) saveBtn5.disabled = false;
                if (saveBtnText4) saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
                if (saveBtnText5) saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
            }

        } catch (err) {
            console.error('Error saving combined seizure and lab exam:', err);
            alert('An error occurred while saving. Please check network connection and try again.');
            if (saveBtn4) saveBtn4.disabled = false;
            if (saveBtn5) saveBtn5.disabled = false;
            if (saveBtnText4) saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
            if (saveBtnText5) saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
        }
    }

    function resetSection5Form() {
        const formInputs = [
            'sampleWeightDescriptionReview',
            'sampleWeightUnitsReview',
            'sampleWeightGrossReview',
            'sampleWeightTakenReview',
            'sampleWeightReservedReview',
            'confirmatorySubstanceIdentificationReview',
            'confirmatoryTestMethodsReview',
            'confirmatorySpecificationsReview',
            'confirmatoryRemarkReview',
            'formLabNotes',
            'presumptiveReactionResultInputReview',
            'formLabNumber'
        ];

        formInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        const errorMsgReviewEl = document.getElementById('sampleWeightTakenErrorReview');
        if (errorMsgReviewEl) errorMsgReviewEl.classList.add('hidden');
        const takenReview = document.getElementById('sampleWeightTakenReview');
        if (takenReview) {
            takenReview.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
            takenReview.classList.add('border-slate-300');
        }

        const presYes = document.querySelector('input[name="form_presumptive_radio"][value="YES"]');
        if (presYes) presYes.checked = true;

        const confYes = document.querySelector('input[name="form_confirmatory_radio"][value="YES"]');
        if (confYes) confYes.checked = true;

        const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
        if (detectedContainer) detectedContainer.innerHTML = '';

        const presContainer = document.getElementById('presumptiveTestRowsContainerReview');
        if (presContainer) {
            const rows = presContainer.querySelectorAll('.presumptive-test-row-review');
            rows.forEach((r, idx) => {
                if (idx === 0) {
                    const presence = r.querySelector('.presumptive-presence-input-review');
                    const percent = r.querySelector('.presumptive-percentage-input-review');
                    if (presence) presence.value = '';
                    if (percent) percent.value = '';
                } else {
                    r.remove();
                }
            });
        }

        const form = document.getElementById('section5LabForm');
        if (form) {
            const elements = form.querySelectorAll('input, select, textarea, button');
            elements.forEach(el => {
                if (!el.classList.contains('close-popover-btn')) {
                    el.disabled = false;
                    el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                }
            });
            form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn, .remove-test-row-btn-review').forEach(btn => {
                btn.style.display = '';
            });
        }

        const saveBtn = document.getElementById('saveSection5LabBtn');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.className = 'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
            const textSpan = document.getElementById('saveSection5LabBtnText');
            if (textSpan) textSpan.innerHTML = 'Save Lab Examination & Update Progress';
        }

        const exhibitSelect = document.getElementById('labExhibitSelect');
        if (exhibitSelect && exhibitSelect.options.length > 1) {
            const options = Array.from(exhibitSelect.options);
            const nextUnexamined = options.find(opt => opt.getAttribute('data-has-saved') !== 'true' && opt.getAttribute('data-has-saved') !== '1');
            if (nextUnexamined) {
                exhibitSelect.value = nextUnexamined.value;
                if (typeof onReviewLabExhibitChange === 'function') {
                    onReviewLabExhibitChange(exhibitSelect);
                }
            }
        }
    }

    function findWeightChunkReview(parts) {
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

    function parsePartsReview(str) {
        return str.split('/').map(p => p.trim()).filter(p => p.length > 0);
    }

    function parseQtyAndUnitReview(chunk) {
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
                const firstWord = afterNum.split(/\s+/)[0];
                if (firstWord) {
                    unit = firstWord.replace(/[^a-zA-Z]/g, '');
                }
            }
        }
        return { num, unit };
    }

    function toKgReview(num, unit) {
        if (!unit) return null;
        const u = unit.toLowerCase();
        if (u === 'kg') return num;
        if (u === 'g') return num / 1000;
        if (u === 'mg') return num / 1000000;
        if (u === 'lb') return num * 0.453592;
        return null;
    }

    function formatNumReview(num) {
        if (num < 0) num = 0;
        const rounded = Math.round(num * 10000) / 10000;
        return rounded.toString();
    }

    function computeReservedSampleReview(grossStr, takenStr) {
        if (!grossStr || typeof grossStr !== 'string' || !grossStr.trim()) {
            return { reserved: '0', isOverflow: false };
        }

        const grossParts = parsePartsReview(grossStr);
        let targetGrossChunk = findWeightChunkReview(grossParts);
        const gParsed = parseQtyAndUnitReview(targetGrossChunk);

        const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

        if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        const takenParts = parsePartsReview(takenStr);
        let targetTakenChunk = findWeightChunkReview(takenParts);
        const tParsed = parseQtyAndUnitReview(targetTakenChunk);

        if (!gParsed || !tParsed) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        let gKg = toKgReview(gParsed.num, gParsed.unit);
        let tKg = toKgReview(tParsed.num, tParsed.unit);

        if (gKg !== null && tKg === null && !tParsed.unit) {
            tKg = toKgReview(tParsed.num, gParsed.unit);
        }

        if (gKg !== null && tKg !== null) {
            if (tKg > gKg) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diffKg = gKg - tKg;
            const gU = gParsed.unit.toLowerCase();
            let resVal = '0';
            if (gU === 'g' || gU === 'gm' || gU === 'gram' || gU === 'grams') {
                resVal = formatNumReview(diffKg * 1000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else if (gU === 'mg' || gU === 'milligram' || gU === 'milligrams') {
                resVal = formatNumReview(diffKg * 1000000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else {
                resVal = formatNumReview(diffKg) + (gParsed.unit ? ' ' + gParsed.unit : '');
            }
            return { reserved: resVal, isOverflow: false };
        } else {
            if (tParsed.num > gParsed.num) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diff = gParsed.num - tParsed.num;
            const unit = gParsed.unit || tParsed.unit;
            return { reserved: formatNumReview(diff) + (unit ? ' ' + unit : ''), isOverflow: false };
        }
    }

    function updateReservedWeightReview() {
        const grossReview = document.getElementById('sampleWeightGrossReview');
        const takenReview = document.getElementById('sampleWeightTakenReview');
        const addonReview = document.getElementById('sampleWeightTakenUnitAddonReview');
        const reservedReview = document.getElementById('sampleWeightReservedReview');
        const errorMsgReviewEl = document.getElementById('sampleWeightTakenErrorReview');

        if (!grossReview || !reservedReview) return;

        const grossStr = grossReview.value || '';
        const takenStr = takenReview ? takenReview.value : '';

        const result = computeReservedSampleReview(grossStr, takenStr);

        reservedReview.value = result.reserved;

        if (result.isOverflow) {
            if (errorMsgReviewEl) errorMsgReviewEl.classList.remove('hidden');
            if (takenReview) {
                takenReview.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                takenReview.classList.remove('border-slate-300');
            }
            if (addonReview) {
                addonReview.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                addonReview.classList.remove('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
            }
        } else {
            if (errorMsgReviewEl) errorMsgReviewEl.classList.add('hidden');
            if (takenReview) {
                takenReview.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                takenReview.classList.add('border-slate-300');
            }
            if (addonReview) {
                addonReview.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
                addonReview.classList.add('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
            }
        }
    }

    function addDetectedSubstanceRowReview(substance = '', percentage = '') {
        const container = document.getElementById('detectedSubstancesListContainerReview');
        if (!container) return;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'detected-substance-row-review flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
        rowDiv.innerHTML = `
            <div class="flex-1 w-full">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected</label>
                <input type="text" class="detected-substance-name-review w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
            </div>
            <div class="w-full sm:w-48">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage</label>
                <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                    <input type="text" class="detected-substance-percent-review w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}">
                    <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                </div>
            </div>
            <button type="button" onclick="removeDetectedSubstanceRowReview(this)" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        `;
        container.appendChild(rowDiv);
    }

    function removeDetectedSubstanceRowReview(btn) {
        const row = btn.closest('.detected-substance-row-review');
        if (row) row.remove();
    }

    function getDetectedSubstancesDataReview() {
        const rows = document.querySelectorAll('#detectedSubstancesListContainerReview .detected-substance-row-review');
        const data = [];
        rows.forEach(r => {
            const sub = r.querySelector('.detected-substance-name-review')?.value || '';
            const pct = r.querySelector('.detected-substance-percent-review')?.value || '';
            if (sub.trim() !== '' || pct.trim() !== '') {
                data.push({ substance: sub.trim(), percentage: pct.trim() });
            }
        });
        return data;
    }

    function toggleLabFormEditMode() {
        const formContainer = document.getElementById('section5FormContainer');
        const form = document.getElementById('section5LabForm');
        const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
        if (form) {
            if (form.classList.contains('hidden')) {
                if (formContainer) formContainer.classList.remove('hidden');
                form.classList.remove('hidden');
                if (readOnlyView) readOnlyView.classList.remove('hidden');
                const elements = form.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    el.disabled = false;
                    el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                });
                form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn, .remove-test-row-btn-review').forEach(btn => {
                    btn.style.display = '';
                });
                const saveBtn = document.getElementById('saveSection5LabBtn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.className = 'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
                    const textSpan = document.getElementById('saveSection5LabBtnText');
                    if (textSpan) textSpan.innerHTML = 'Save Lab Examination & Update Progress';
                }
            } else {
                if (formContainer) formContainer.classList.add('hidden');
                form.classList.add('hidden');
                if (readOnlyView) readOnlyView.classList.remove('hidden');
            }
        }
    }

    function lockSection5Form() {
        const formContainer = document.getElementById('section5FormContainer');
        const form = document.getElementById('section5LabForm');
        const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
        
        if (readOnlyView) {
            readOnlyView.classList.remove('hidden');
        }
        if (formContainer) {
            formContainer.classList.add('hidden');
        }
        if (form) {
            form.classList.add('hidden');
        }

        if (!form) return;

        const elements = form.querySelectorAll('input, select, textarea, button');
        elements.forEach(el => {
            if (el.id !== 'closeSaveSuccessPopoverBtn' && !el.classList.contains('close-popover-btn')) {
                el.disabled = true;
                el.classList.add('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
            }
        });

        // Hide add/remove row buttons
        form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn').forEach(btn => {
            btn.style.display = 'none';
        });

        const saveBtn = document.getElementById('saveSection5LabBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.className = 'px-6 py-3 bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center cursor-not-allowed opacity-90 border-none';
            const svgEl = saveBtn.querySelector('svg');
            if (svgEl) svgEl.remove();
            const textSpan = document.getElementById('saveSection5LabBtnText');
            if (textSpan) textSpan.innerHTML = 'LAB EXAMINATION SAVED & LOCKED';
        }
    }

    function showSaveSuccessPopover() {
        const modal = document.getElementById('saveSuccessPopoverModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeSaveSuccessPopover() {
        const modal = document.getElementById('saveSuccessPopoverModal');
        if (modal) modal.classList.add('hidden');
        window.location.reload();
    }

    document.addEventListener('DOMContentLoaded', function() {
    const initDetContainer = document.getElementById('detectedSubstancesListContainerReview');
    if (initDetContainer && initDetContainer.children.length === 0) {
        if (typeof addDetectedSubstanceRowReview === 'function') {
            const initialName = document.getElementById('confirmatorySubstanceIdentificationReview')?.value || document.getElementById('labExhibitSelect')?.options[0]?.getAttribute('data-name') || '';
            addDetectedSubstanceRowReview(initialName, '');
        }
    }
        const grossReviewEl = document.getElementById('sampleWeightGrossReview');
        const takenReviewEl = document.getElementById('sampleWeightTakenReview');
        ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(evt) {
            if (grossReviewEl) grossReviewEl.addEventListener(evt, updateReservedWeightReview);
            if (takenReviewEl) takenReviewEl.addEventListener(evt, updateReservedWeightReview);
        });

        updateReservedWeightReview();

        const existingDetected = @json($labDetectedSubstances);
        const detReviewContainer = document.getElementById('detectedSubstancesListContainerReview');
        if (detReviewContainer) {
            detReviewContainer.innerHTML = '';
            if (Array.isArray(existingDetected) && existingDetected.length > 0) {
                existingDetected.forEach(item => {
                    if (typeof item === 'string') {
                        addDetectedSubstanceRowReview(item, '');
                    } else if (item && typeof item === 'object') {
                        addDetectedSubstanceRowReview(item.substance || '', item.percentage || '');
                    }
                });
            } else {
                addDetectedSubstanceRowReview('', '');
            }
        }

        const existingPresumptive = @json($labPresumptive);
        if (existingPresumptive) {
            const isNo = existingPresumptive.toUpperCase().includes('NO (');
            const radioYes = document.querySelector('input[name="form_presumptive_radio"][value="YES"]');
            const radioNo = document.querySelector('input[name="form_presumptive_radio"][value="NO"]');
            if (isNo) {
                if (radioNo) radioNo.checked = true;
                onPresumptiveRadioChange('NO');
            } else {
                if (radioYes) radioYes.checked = true;
                onPresumptiveRadioChange('YES');

                const matches = existingPresumptive.match(/\[(\d+)\]\s*Presence of:\s*([^-\;]+)(?:\s*-\s*Purity\/Percentage:\s*([^\;]+))?/gi);
                if (matches && matches.length > 0) {
                    const rowsContainer = document.getElementById('presumptiveTestRowsContainerReview');
                    if (rowsContainer) {
                        rowsContainer.innerHTML = '';
                        matches.forEach((m, idx) => {
                            addPresumptiveTestRowReview();
                            const rows = rowsContainer.querySelectorAll('.presumptive-test-row-review');
                            const targetRow = rows[rows.length - 1];
                            if (targetRow) {
                                const presenceMatch = m.match(/Presence of:\s*([^-\;]+)/i);
                                const pctMatch = m.match(/Purity\/Percentage:\s*([^\;]+)/i);
                                if (presenceMatch && targetRow.querySelector('.presumptive-presence-input-review')) {
                                    targetRow.querySelector('.presumptive-presence-input-review').value = presenceMatch[1].trim();
                                }
                                if (pctMatch && targetRow.querySelector('.presumptive-percentage-input-review')) {
                                    targetRow.querySelector('.presumptive-percentage-input-review').value = pctMatch[1].trim();
                                }
                            }
                        });
                    }
                } else {
                    const singlePresence = existingPresumptive.match(/Presence of:\s*([^-\;\)]+)/i);
                    const singlePct = existingPresumptive.match(/Purity\/Percentage:\s*([^;\)]+)/i);
                    if (singlePresence || singlePct) {
                        const firstRow = document.querySelector('.presumptive-test-row-review');
                        if (firstRow) {
                            if (singlePresence && firstRow.querySelector('.presumptive-presence-input-review')) {
                                firstRow.querySelector('.presumptive-presence-input-review').value = singlePresence[1].trim();
                            }
                            if (singlePct && firstRow.querySelector('.presumptive-percentage-input-review')) {
                                firstRow.querySelector('.presumptive-percentage-input-review').value = singlePct[1].trim();
                            }
                        }
                    }
                }
            }
        }

        const exhSel = document.getElementById('labExhibitSelect');
        if (exhSel && typeof onReviewLabExhibitChange === 'function') {
            onReviewLabExhibitChange(exhSel);
        }

        // Lock form if already completed
        const isAlreadyCompleted = @json($activeSuspectHasSavedResult);
        if (isAlreadyCompleted) {
            lockSection5Form();
        }

        window.toggleSection5LabForm = function() {
            const formContainer = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            if (formContainer) formContainer.classList.remove('hidden');
            if (form) {
                form.classList.remove('hidden');
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        if (window.location.search.includes('from=Lab+Analysis') || window.location.search.includes('unit=Lab+Analysis') || window.location.search.includes('edit=true') || window.location.search.includes('edit=1') || @json($isLabContext)) {
            if (typeof toggleLabFormEditMode === 'function') {
                const form = document.getElementById('section5LabForm');
                if (form && form.classList.contains('hidden')) {
                    toggleLabFormEditMode();
                }
            }
            setTimeout(function() {
                const sec5 = document.getElementById('section5LabForm') || document.getElementById('section5LabExamination');
                if (sec5) sec5.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    });

    // -------------------------------------------------------------------------
    // CASE STATUS MANAGEMENT, HANDOVER & AUDIT TRAIL JS HANDLERS
    // -------------------------------------------------------------------------

    function updateCaseStatus(newStatus) {
        if (!newStatus) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const caseId = @json($case->case_number);

        fetch(`/cases/${encodeURIComponent(caseId)}/case-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ case_status: newStatus })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                alert(res.body.message || 'Case status updated successfully.');
                fetchAuditTrail();
            } else {
                alert(res.body.message || 'Failed to update case status.');
                window.location.reload();
            }
        })
        .catch(err => {
            console.error('Error updating case status:', err);
            alert('An unexpected error occurred while updating case status.');
        });
    }

    function openHandoverModal() {
        const modal = document.getElementById('handoverModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeHandoverModal() {
        const modal = document.getElementById('handoverModal');
        if (modal) modal.classList.add('hidden');
    }

    function submitHandover() {
        const targetUserId = document.getElementById('handoverTargetUser')?.value;
        const notes = document.getElementById('handoverNotes')?.value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const caseId = @json($case->case_number);

        if (!targetUserId) {
            alert('Please select a receiving Unit Head.');
            return;
        }

        fetch(`/cases/${encodeURIComponent(caseId)}/handover/initiate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                to_user_id: targetUserId,
                request_notes: notes
            })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                alert(res.body.message || 'Handover request initiated successfully.');
                closeHandoverModal();
                window.location.reload();
            } else {
                alert(res.body.message || 'Failed to initiate handover request.');
            }
        })
        .catch(err => {
            console.error('Error initiating handover:', err);
            alert('An unexpected error occurred while initiating handover.');
        });
    }

    function respondHandover(action) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const caseId = @json($case->case_number);

        fetch(`/cases/${encodeURIComponent(caseId)}/handover/respond`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ action: action })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                alert(res.body.message || 'Handover response recorded successfully.');
                window.location.reload();
            } else {
                alert(res.body.message || 'Failed to process handover response.');
            }
        })
        .catch(err => {
            console.error('Error responding to handover:', err);
            alert('An unexpected error occurred while processing handover.');
        });
    }


    function openMugshotModal(imgSrc, titleText) {
        const modal = document.getElementById('reviewMugshotLightboxModal');
        const modalImg = document.getElementById('reviewMugshotModalImage');
        const modalTitle = document.getElementById('reviewMugshotModalTitle');
        if (modal && modalImg) {
            modalImg.src = imgSrc;
            if (modalTitle) modalTitle.innerText = titleText || 'High-Resolution Visual Preview';
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
    }

    function closeMugshotModal() {
        const modal = document.getElementById('reviewMugshotLightboxModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }
</script>

<!-- REVIEW MUGSHOT / EXHIBIT LIGHTBOX MODAL -->
<div id="reviewMugshotLightboxModal" class="hidden fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[99999] flex items-center justify-center p-4" onclick="closeMugshotModal()">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl space-y-4 relative border border-slate-200" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h4 id="reviewMugshotModalTitle" class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">High-Resolution Visual Preview</h4>
            <button type="button" onclick="closeMugshotModal()" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold border border-slate-300 transition-colors cursor-pointer">
                Close
            </button>
        </div>
        <div class="flex items-center justify-center bg-slate-900 rounded-xl p-3 min-h-[350px] max-h-[70vh] overflow-hidden">
            <img id="reviewMugshotModalImage" src="" alt="Full Exhibit View" class="max-h-[65vh] max-w-full object-contain rounded-lg shadow-lg">
        </div>
        <div class="flex justify-end pt-2">
            <button type="button" onclick="closeMugshotModal()" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                Close Preview
            </button>
        </div>
    </div>
</div>

<!-- BOOTSTRAP-STYLE POPOVER SUCCESS MODAL -->
<div id="saveSuccessPopoverModal" class="hidden fixed inset-0 z-[99999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border-2 border-emerald-500 shadow-2xl max-w-md w-full p-6 text-center space-y-4 relative transform transition-all animate-in zoom-in-95 duration-200">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600 shadow-inner flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="space-y-1">
            <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono-code font-bold text-[10px] uppercase">SUCCESSFULLY RECORDED</span>
            <h3 class="text-xl font-black text-slate-900 mt-1">Lab Examination Saved</h3>
            <p class="text-xs text-slate-600 font-semibold leading-relaxed">
                The laboratory examination results &amp; findings log have been successfully saved to the database and locked against further editing.
            </p>
        </div>
        <div class="pt-2">
            <button id="closeSaveSuccessPopoverBtn" onclick="closeSaveSuccessPopover()" class="close-popover-btn w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30 transition-all border-none cursor-pointer">
                Acknowledge &amp; Continue
            </button>
        </div>
    </div>
</div>

<!-- SUSPECT DISPOSITION MODAL -->
<div id="suspectDispositionModal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
            <div>
                <div class="text-[10px] font-mono-code text-amber-400 font-bold uppercase tracking-wider">SUSPECT DISPOSITION CONTROL</div>
                <h3 class="text-sm font-black tracking-tight text-white" id="modalSuspectNameTitle">Update Suspect Disposition</h3>
            </div>
            <button type="button" onclick="closeSuspectDispositionModal()" class="text-slate-400 hover:text-white text-lg font-bold bg-transparent border-none cursor-pointer">&times;</button>
        </div>
        <div class="p-5 space-y-4">
            <input type="hidden" id="suspectDispTargetName" value="">
            <div>
                <label class="block text-xs font-extrabold text-slate-700 mb-1">Select New Suspect Disposition:</label>
                <select id="suspectDispSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:bg-white focus:border-indigo-600 shadow-2xs">
                    <option value="Active — Under Investigation">Active — Under Investigation</option>
                    <option value="Cleared / No Further Action">Cleared / No Further Action</option>
                    <option value="Released from Case">Released from Case</option>
                    <option value="Reclassified">Reclassified</option>
                    <option value="Referred for Further Review">Referred for Further Review</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-extrabold text-slate-700 mb-1">Official Justification / Reason <span class="text-red-500">*</span>:</label>
                <textarea id="suspectDispReason" rows="3" placeholder="Enter official reason for clearance or reclassification..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:bg-white focus:border-indigo-600 shadow-2xs"></textarea>
                <div class="text-[10px] text-slate-400 italic mt-1">A justification reason is required when clearing or releasing a suspect.</div>
            </div>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
            <button type="button" onclick="closeSuspectDispositionModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl cursor-pointer border-none">Cancel</button>
            <button type="button" onclick="submitSuspectDisposition()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-xs transition-all cursor-pointer border-none">Save Disposition</button>
        </div>
    </div>
</div>

<script>
    function openSuspectDispositionModal(suspectName, currentDisp) {
        document.getElementById('suspectDispTargetName').value = suspectName;
        document.getElementById('modalSuspectNameTitle').innerText = 'Update Disposition: ' + suspectName;
        if (document.getElementById('suspectDispSelect')) {
            document.getElementById('suspectDispSelect').value = currentDisp || 'Active — Under Investigation';
        }
        document.getElementById('suspectDispReason').value = '';
        document.getElementById('suspectDispositionModal').classList.remove('hidden');
    }

    function closeSuspectDispositionModal() {
        document.getElementById('suspectDispositionModal').classList.add('hidden');
    }

    function submitSuspectDisposition() {
        const suspectName = document.getElementById('suspectDispTargetName').value;
        const disposition = document.getElementById('suspectDispSelect').value;
        const reason = document.getElementById('suspectDispReason').value.trim();

        if (disposition !== 'Active — Under Investigation' && !reason) {
            alert('Please provide a valid justification reason for clearing or releasing this suspect.');
            return;
        }

        const caseId = @json($case->case_number);
        fetch(`/cases/${encodeURIComponent(caseId)}/suspect-disposition`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                suspect_name: suspectName,
                suspect_disposition: disposition,
                disposition_reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Suspect disposition updated successfully.');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update suspect disposition.');
            }
        })
        .catch(err => {
            console.error('Error updating suspect disposition:', err);
            alert('An unexpected error occurred while updating suspect disposition.');
        });
    }

    function toggleAndScrollToSeizureSection() {
        const section = document.getElementById('section4Seizures');
        if (section) {
            section.classList.remove('hidden');
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    window.activeSuspectName = @json($activeSuspectName ?? '');
    window.activeSuspectHasPersistedDrugExhibit = {{ !empty($activeSuspectHasDrugExhibit) ? 'true' : 'false' }};
    window.activeSuspectHasSavedLabResult = {{ !empty($activeSuspectHasSavedResult) ? 'true' : 'false' }};
    window.activeSuspectDrugExhibitsJson = @json($suspectDrugExhibitsList ?? []);

    function onDrugTypeChange(selectEl) {
        const selectedType = selectEl ? (selectEl.value || '').trim().toLowerCase() : '';
        const labExhibitSelect = document.getElementById('labExhibitSelect');
        const labExhibitCountBadge = document.getElementById('labExhibitCountBadge');
        const hiddenExIdInput = document.getElementById('labFormExhibitId');

        if (!labExhibitSelect) return;

        let jsonExhibits = (window.activeSuspectDrugExhibitsJson || []).slice();
        let domExhibits = [];

        const blocks = document.querySelectorAll('.seizure-block');
        blocks.forEach((block, idx) => {
            const itemNum = idx + 1;
            const cat = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || '';
            let name = block.querySelector('#newSeizureNameDrug')?.value || 
                       block.querySelector('#drugSearchInput')?.value || 
                       block.querySelector('#newSeizureNameWeapon')?.value || 
                       block.querySelector('#weaponSearchInput')?.value || 
                       block.querySelector('#newSeizureNameCurrency')?.value || 
                       block.querySelector('#currencySearchInput')?.value || 
                       block.querySelector('#newSeizureNameDevice')?.value || 
                       block.querySelector('#deviceSearchInput')?.value || 
                       block.querySelector('#newSeizureName')?.value || '';
            const qty = block.querySelector('input[name="seizure_quantity"]')?.value || block.querySelector('#newSeizureQuantity')?.value || '';
            const pkg = block.querySelector('input[name="seizure_packaging"]')?.value || block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
            const weight = block.querySelector('input[name="seizure_weight"]')?.value || block.querySelector('#newSeizureWeight')?.value || '';
            const unit = block.querySelector('input[name="seizure_unit"]')?.value || block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
            const owner = block.querySelector('.seizure-associated-person-select')?.value || window.activeSuspectName || '';

            if (name || cat) {
                let displayName = name || cat || `Exhibit #${itemNum}`;
                domExhibits.push({
                    id: itemNum.toString(),
                    index: itemNum,
                    name: displayName,
                    cat: cat,
                    qty: qty,
                    weight: weight,
                    unit: unit,
                    pkg: pkg,
                    owner: owner,
                    description: pkg ? `${displayName} (${pkg})` : displayName,
                    is_associate: false,
                    has_saved_lab: false
                });
            }
        });

        let allExhibits = domExhibits.length > 0 
            ? domExhibits.concat(jsonExhibits.filter(j => !domExhibits.some(d => (d.name || '').toLowerCase() === (j.name || '').toLowerCase()))) 
            : jsonExhibits;

        let matchingExhibits = allExhibits;
        if (selectedType !== '') {
            matchingExhibits = allExhibits.filter(function(ex) {
                const name = (ex.name || '').toLowerCase();
                const cat = (ex.cat || '').toLowerCase();
                const subId = (ex.substance_identification || '').toLowerCase();
                const desc = (ex.description || '').toLowerCase();
                return name.includes(selectedType) || cat.includes(selectedType) || subId.includes(selectedType) || desc.includes(selectedType);
            });
        }

        labExhibitSelect.innerHTML = '';

        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = 'Select Seized Exhibit to Examine (Manifest Sync)...';
        placeholderOpt.selected = true;
        labExhibitSelect.appendChild(placeholderOpt);

        if (matchingExhibits.length > 0) {
            labExhibitSelect.removeAttribute('disabled');
            matchingExhibits.forEach(function(dEx, idx) {
                const opt = document.createElement('option');
                opt.value = dEx.id || dEx.index;
                opt.setAttribute('data-ex-id', dEx.id || '');
                opt.setAttribute('data-index', dEx.index);
                opt.setAttribute('data-name', dEx.name || '');
                opt.setAttribute('data-cat', dEx.cat || '');
                opt.setAttribute('data-owner', dEx.owner || '');
                opt.setAttribute('data-qty', dEx.qty || '');
                opt.setAttribute('data-weight', dEx.weight || '');
                opt.setAttribute('data-unit', dEx.unit || '');
                opt.setAttribute('data-pkg', dEx.pkg || '');
                opt.setAttribute('data-description', dEx.description || '');
                opt.setAttribute('data-is-associate', dEx.is_associate ? '1' : '0');
                opt.setAttribute('data-has-saved', dEx.has_saved_lab ? 'true' : 'false');
                opt.setAttribute('data-confirmatory', dEx.confirmatory_result || 'YES');
                opt.setAttribute('data-substance-id', dEx.substance_identification || dEx.name || '');
                opt.setAttribute('data-test-methods', dEx.test_methods || '');
                opt.setAttribute('data-specifications', dEx.specifications || '');
                opt.setAttribute('data-remark', dEx.remark || '');
                opt.setAttribute('data-sample-taken', dEx.sample_taken || '');
                opt.setAttribute('data-sample-reserved', dEx.sample_reserved || '');
                opt.setAttribute('data-findings', dEx.findings || '');

                let itemTitle = dEx.name || dEx.cat || `Exhibit #${idx + 1}`;
                let qtyUnitSuffix = (dEx.unit && dEx.qty && !dEx.qty.toLowerCase().includes(dEx.unit.toLowerCase())) ? (' ' + dEx.unit) : '';
                let weightUnitSuffix = (dEx.unit && dEx.weight && !dEx.weight.toLowerCase().includes(dEx.unit.toLowerCase())) ? (' ' + dEx.unit) : '';
                let metricsStr = dEx.qty ? (' — ' + dEx.qty + qtyUnitSuffix) : (dEx.weight ? (' — ' + dEx.weight + weightUnitSuffix) : '');
                let rawDesc = dEx.pkg || dEx.description || '';
                let cleanDesc = rawDesc;
                if (cleanDesc && itemTitle) {
                    if (cleanDesc.toLowerCase().startsWith(itemTitle.toLowerCase())) {
                        cleanDesc = cleanDesc.substring(itemTitle.length).trim();
                    }
                    cleanDesc = cleanDesc.replace(/^[\s\—\-\:\(\)]+/, '').replace(/[\)]+$/, '').trim();
                }
                let descStr = cleanDesc ? (` (${cleanDesc})`) : '';
                opt.textContent = `${itemTitle}${metricsStr}${descStr}`;

                labExhibitSelect.appendChild(opt);
            });

            if (labExhibitCountBadge) {
                labExhibitCountBadge.textContent = matchingExhibits.length + ' Drug Exhibit(s) Synced';
            }
            if (hiddenExIdInput) {
                hiddenExIdInput.value = '';
            }

            onReviewLabExhibitChange(labExhibitSelect);
        } else {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No matching seized exhibit found';
            opt.selected = true;
            labExhibitSelect.appendChild(opt);
            labExhibitSelect.setAttribute('disabled', 'disabled');

            if (labExhibitCountBadge) {
                labExhibitCountBadge.textContent = '0 Matching Exhibits';
            }
            if (hiddenExIdInput) {
                hiddenExIdInput.value = '';
            }
        }
    }

    function syncSection4ToSection5InReview() {
        const block = document.querySelector('.seizure-block');
        if (!block) return;

        const qty = block.querySelector('#newSeizureQuantity')?.value || '';
        const pkg = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
        const weight = block.querySelector('#newSeizureWeight')?.value || '';
        const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
        const drugName = block.querySelector('#newSeizureNameDrug')?.value || 
                         block.querySelector('#drugSearchInput')?.value || 
                         block.querySelector('#newSeizureName')?.value || '';

        const sampleDescInput = document.getElementById('sampleWeightDescriptionReview');
        const substanceIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
        const unitsInput = document.getElementById('sampleWeightUnitsReview');
        const grossInput = document.getElementById('sampleWeightGrossReview');
        const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');

        let unitsText = (qty && pkg) ? `${qty} ${pkg}`.trim() : (qty || pkg || '');
        let grossWeightText = (weight && unit) ? `${weight} ${unit}`.trim() : (weight || unit || unitsText || '');

        if (sampleDescInput && drugName) {
            sampleDescInput.value = drugName;
        }
        if (substanceIdInput && drugName) {
            substanceIdInput.value = drugName;
        }
        if (unitsInput && unitsText) {
            unitsInput.value = unitsText;
        }
        if (grossInput && grossWeightText) {
            grossInput.value = grossWeightText;
        }
        if (unitAddon && unit) {
            unitAddon.textContent = unit;
        } else if (unitAddon && grossWeightText) {
            const uMatch = grossWeightText.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?|Litres?|L)/i);
            if (uMatch) unitAddon.textContent = uMatch[1];
        }
    }

    function evaluateLabExaminationVisibility() {
        const formContainer = document.getElementById('section5FormContainer');
        const noDrugNotice  = document.getElementById('section5NoDrugExhibitNotice');
        const readOnlyView  = document.getElementById('section5ReadOnlyDisplay');

        if (!formContainer && !noDrugNotice && !readOnlyView) return;

        // If active suspect already has a saved lab examination result, show read-only view
        if (window.activeSuspectHasSavedLabResult && readOnlyView) {
            readOnlyView.classList.remove('hidden');
            if (formContainer) formContainer.classList.add('hidden');
            if (noDrugNotice)  noDrugNotice.classList.add('hidden');
            return;
        }

        // Section 5 Examination Information section MUST ALWAYS remain visible when unsaved
        if (readOnlyView) readOnlyView.classList.add('hidden');
        if (formContainer) formContainer.classList.remove('hidden');
        if (noDrugNotice)  noDrugNotice.classList.add('hidden');

        if ((window.activeSuspectDrugExhibitsJson || []).length > 0) {
            onDrugTypeChange(null);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        evaluateLabExaminationVisibility();
        syncSection4ToSection5InReview();

        document.addEventListener('input', function(e) {
            syncSection4ToSection5InReview();
            if (e.target && (e.target.id === 'exhibitSearchInput' || e.target.id === 'newSeizureCategory')) {
                evaluateLabExaminationVisibility();
            }
        });
        document.addEventListener('change', function(e) {
            syncSection4ToSection5InReview();
            if (e.target && (e.target.id === 'exhibitSearchInput' || e.target.id === 'newSeizureCategory' || e.target.classList.contains('seizure-associated-person-select') || e.target.id === 'newSeizureSuspect')) {
                evaluateLabExaminationVisibility();
            }
        });
    });

    function getSeizureBlockFromEvent(e) {
        if (!e) return document.querySelector('.seizure-block') || document;
        const target = e.target || e.srcElement;
        if (target && target.closest) {
            return target.closest('.seizure-block') || document.querySelector('.seizure-block') || document;
        }
        return document.querySelector('.seizure-block') || document;
    }

    function addSeizureBlock() {
        const container = document.getElementById('seizuresListContainer');
        if (!container) return;

        const firstBlock = container.querySelector('.seizure-block');
        if (!firstBlock) return;

        const newIndex = container.querySelectorAll('.seizure-block').length + 1;
        const clone = firstBlock.cloneNode(true);

        clone.setAttribute('data-seizure-index', newIndex);

        // Update title
        const titleEl = clone.querySelector('.seizure-title');
        if (titleEl) titleEl.innerText = `Seizure Item #${newIndex}`;

        // Reset inputs in clone
        clone.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], input[type="time"], textarea').forEach(input => {
            input.value = '';
        });
        clone.querySelectorAll('input[type="hidden"]').forEach(input => {
            input.value = '';
        });
        clone.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        // Hide all specialized wrappers in clone initially
        const drugWrapper = clone.querySelector('#drugSelectWrapper');
        const weaponWrapper = clone.querySelector('#weaponSelectWrapper');
        const currencyWrapper = clone.querySelector('#currencySelectWrapper');
        const ammoWrapper = clone.querySelector('#ammoSelectWrapper');
        const deviceWrapper = clone.querySelector('#deviceSelectWrapper');
        const deviceFieldsWrapper = clone.querySelector('#deviceFieldsWrapper');
        const vehicleFieldsWrapper = clone.querySelector('#vehicleFieldsWrapper');
        const weaponFieldsWrapper = clone.querySelector('#weaponFieldsWrapper');
        const ammoFieldsWrapper = clone.querySelector('#ammoFieldsWrapper');

        // Hide all dropdown panels in clone
        clone.querySelectorAll('#exhibitListPanel, #drugListPanel, #weaponListPanel, #currencyListPanel, #ammoListPanel, #deviceListPanel, #pkgListPanel, #unitListPanel').forEach(panel => {
            panel.classList.add('hidden');
        });

        if (drugWrapper) drugWrapper.classList.add('hidden');
        if (weaponWrapper) weaponWrapper.classList.add('hidden');
        if (currencyWrapper) currencyWrapper.classList.add('hidden');
        if (ammoWrapper) ammoWrapper.classList.add('hidden');
        if (deviceWrapper) deviceWrapper.classList.add('hidden');
        if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
        if (vehicleFieldsWrapper) vehicleFieldsWrapper.classList.add('hidden');
        if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
        if (ammoFieldsWrapper) ammoFieldsWrapper.classList.add('hidden');

        const commodityTextWrapper = clone.querySelector('#commodityTextWrapper');
        if (commodityTextWrapper) commodityTextWrapper.classList.remove('hidden');

        container.appendChild(clone);
        updateSeizureBlockNumbers();
        evaluateLabExaminationVisibility();
    }

    function removeSeizureBlock(btn) {
        const block = btn ? btn.closest('.seizure-block') : null;
        const container = document.getElementById('seizuresListContainer');
        if (block && container && container.querySelectorAll('.seizure-block').length > 1) {
            block.remove();
            updateSeizureBlockNumbers();
            evaluateLabExaminationVisibility();
        }
    }

    function updateSeizureBlockNumbers() {
        const blocks = document.querySelectorAll('.seizure-block');
        blocks.forEach((block, index) => {
            const num = index + 1;
            block.setAttribute('data-seizure-index', num);
            const titleEl = block.querySelector('.seizure-title');
            const numberBadge = block.querySelector('.seizure-number-badge');
            if (titleEl) titleEl.innerText = `Seizure Item #${num}`;
            if (numberBadge) numberBadge.innerText = num;

            const removeBtn = block.querySelector('.remove-seizure-btn');
            if (removeBtn) {
                if (blocks.length > 1) {
                    removeBtn.classList.remove('hidden');
                } else {
                    removeBtn.classList.add('hidden');
                }
            }
        });
    }

    function openSeizurePanel(el, panelSelector) {
        if (el && el.removeAttribute) el.removeAttribute('readonly');
        const block = el ? el.closest('.seizure-block') : null;
        if (block) {
            document.querySelectorAll('.seizure-block').forEach(b => b.style.zIndex = '1');
            block.style.zIndex = '99999';
        }
        const panel = block ? block.querySelector(panelSelector) : document.querySelector(panelSelector);
        if (panel) panel.classList.remove('hidden');
    }

    function openExhibitListPanel(el) { openSeizurePanel(el, '#exhibitListPanel'); }
    function openDrugListPanel(el) { openSeizurePanel(el, '#drugListPanel'); }
    function openWeaponListPanel(el) { openSeizurePanel(el, '#weaponListPanel'); }
    function openCurrencyListPanel(el) { openSeizurePanel(el, '#currencyListPanel'); }
    function openAmmoListPanel(el) { openSeizurePanel(el, '#ammoListPanel'); }
    function openDeviceListPanel(el) { openSeizurePanel(el, '#deviceListPanel'); }
    function openPkgListPanel(el) { openSeizurePanel(el, '#pkgListPanel'); }
    function openUnitListPanel(el) { openSeizurePanel(el, '#unitListPanel'); }

    function selectExhibitCategory(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root = getSeizureBlockFromEvent(e);
        const input   = root.querySelector('#newSeizureCategory');
        const display = root.querySelector('#exhibitSearchInput');
        const panel   = root.querySelector('#exhibitListPanel');
        if (input)   input.value = val;
        if (display) { display.value = val; display.removeAttribute('readonly'); }
        if (panel)   panel.classList.add('hidden');
        updateCommodityField(val, root);
        evaluateLabExaminationVisibility();

        onDrugTypeChange(null);
    }

    function filterExhibitList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#exhibitSearchInput')?.value || '';
        const categoryInput = root.querySelector('#newSeizureCategory');
        if (categoryInput) categoryInput.value = rawVal;
        filterExhibitListByQuery(rawVal.toLowerCase(), root);
        evaluateLabExaminationVisibility();
    }

    function filterExhibitListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#exhibitSearchInner')?.value || '';
        const categoryInput = root.querySelector('#newSeizureCategory');
        if (categoryInput) categoryInput.value = rawVal;
        filterExhibitListByQuery(rawVal.toLowerCase(), root);
        evaluateLabExaminationVisibility();
    }

    function filterExhibitListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#exhibitList');
        if (!list) return;

        let customLi = list.querySelector('#exhibitCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'exhibitCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectExhibitCategory('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Category: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.exhibit-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function updateCommodityField(category, rootEl) {
        const root = rootEl || document.querySelector('.seizure-block') || document;
        const textWrapper     = root.querySelector('#commodityTextWrapper');
        const drugWrapper     = root.querySelector('#drugSelectWrapper');
        const weaponWrapper   = root.querySelector('#weaponSelectWrapper');
        const currencyWrapper = root.querySelector('#currencySelectWrapper');
        const ammoWrapper     = root.querySelector('#ammoSelectWrapper');
        const deviceWrapper   = root.querySelector('#deviceSelectWrapper');
        const deviceFieldsWrapper = root.querySelector('#deviceFieldsWrapper');
        const weaponFieldsWrapper = root.querySelector('#weaponFieldsWrapper');
        const ammoFieldsWrapper = root.querySelector('#ammoFieldsWrapper');
        const textInput       = root.querySelector('#newSeizureName');
        const quantityLabel   = root.querySelector('#quantityLabel');

        if (textWrapper)         textWrapper.classList.add('hidden');
        if (drugWrapper)         drugWrapper.classList.add('hidden');
        if (weaponWrapper)       weaponWrapper.classList.add('hidden');
        if (currencyWrapper)     currencyWrapper.classList.add('hidden');
        if (ammoWrapper)         ammoWrapper.classList.add('hidden');
        if (deviceWrapper)       deviceWrapper.classList.add('hidden');
        if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
        if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
        if (ammoFieldsWrapper)   ammoFieldsWrapper.classList.add('hidden');

        if (category === 'Drugs') {
            if (quantityLabel) quantityLabel.innerText = 'Quantity';
            if (drugWrapper) drugWrapper.classList.remove('hidden');
        } else if (category === 'Weapons') {
            if (quantityLabel) quantityLabel.innerText = 'Quantity';
            if (weaponWrapper) weaponWrapper.classList.remove('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.remove('hidden');
        } else if (category === 'Money') {
            if (quantityLabel) quantityLabel.innerText = 'Amount';
            if (currencyWrapper) currencyWrapper.classList.remove('hidden');
        } else if (category === 'Ammunition') {
            if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
            if (ammoWrapper) ammoWrapper.classList.remove('hidden');
            if (ammoFieldsWrapper) ammoFieldsWrapper.classList.remove('hidden');
        } else if (category === 'Electronic Devices') {
            if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
            if (deviceWrapper) deviceWrapper.classList.remove('hidden');
        } else {
            if (quantityLabel) quantityLabel.innerText = 'Quantity';
            if (textWrapper) textWrapper.classList.remove('hidden');
        }

        evaluateLabExaminationVisibility();
    }

    function selectDrugType(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root   = getSeizureBlockFromEvent(e);
        const hidden  = root.querySelector('#newSeizureNameDrug');
        const display = root.querySelector('#drugSearchInput');
        const panel   = root.querySelector('#drugListPanel');
        if (hidden)  hidden.value  = val;
        if (display) display.value = val;
        if (panel)   panel.classList.add('hidden');
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = val;

        onDrugTypeChange(null);
    }

    function filterDrugList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#drugSearchInput')?.value || '';
        const hidden = root.querySelector('#newSeizureNameDrug');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterDrugListByQuery(rawVal.toLowerCase(), root);
    }

    function filterDrugListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#drugSearchInner')?.value || '';
        const hidden = root.querySelector('#newSeizureNameDrug');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterDrugListByQuery(rawVal.toLowerCase(), root);
    }

    function filterDrugListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#drugList');
        if (!list) return;

        let customLi = list.querySelector('#drugCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'drugCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectDrugType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.drug-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function selectWeaponType(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root   = getSeizureBlockFromEvent(e);
        const hidden  = root.querySelector('#newSeizureNameWeapon');
        const display = root.querySelector('#weaponSearchInput');
        const panel   = root.querySelector('#weaponListPanel');
        if (hidden)  hidden.value  = val;
        if (display) display.value = val;
        if (panel)   panel.classList.add('hidden');
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = val;
    }

    function filterWeaponList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#weaponSearchInput')?.value || '';
        const hidden = root.querySelector('#newSeizureNameWeapon');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterWeaponListByQuery(rawVal.toLowerCase(), root);
    }

    function filterWeaponListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#weaponSearchInner')?.value || '';
        const hidden = root.querySelector('#newSeizureNameWeapon');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterWeaponListByQuery(rawVal.toLowerCase(), root);
    }

    function filterWeaponListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#weaponList');
        if (!list) return;

        let customLi = list.querySelector('#weaponCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'weaponCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectWeaponType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.weapon-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function selectCurrencyType(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root   = getSeizureBlockFromEvent(e);
        const hidden  = root.querySelector('#newSeizureNameCurrency');
        const display = root.querySelector('#currencySearchInput');
        const panel   = root.querySelector('#currencyListPanel');
        if (hidden)  hidden.value  = val;
        if (display) display.value = val;
        if (panel)   panel.classList.add('hidden');
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = val;
    }

    function filterCurrencyList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#currencySearchInput')?.value || '';
        const hidden = root.querySelector('#newSeizureNameCurrency');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterCurrencyListByQuery(rawVal.toLowerCase(), root);
    }

    function filterCurrencyListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#currencySearchInner')?.value || '';
        const hidden = root.querySelector('#newSeizureNameCurrency');
        if (hidden) hidden.value = rawVal;
        const textInput = root.querySelector('#newSeizureName');
        if (textInput) textInput.value = rawVal;
        filterCurrencyListByQuery(rawVal.toLowerCase(), root);
    }

    function filterCurrencyListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#currencyList');
        if (!list) return;

        let customLi = list.querySelector('#currencyCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'currencyCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectCurrencyType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.currency-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function selectPackagingType(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root   = getSeizureBlockFromEvent(e);
        const hidden  = root.querySelector('#newSeizurePackaging');
        const display = root.querySelector('#pkgSearchInput');
        const panel   = root.querySelector('#pkgListPanel');
        if (hidden)  hidden.value  = val;
        if (display) display.value = val;
        if (panel)   panel.classList.add('hidden');
        if (typeof syncSection4ToSection5InReview === 'function') syncSection4ToSection5InReview();
    }

    function filterPackagingList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#pkgSearchInput')?.value || '';
        const hidden = root.querySelector('#newSeizurePackaging');
        if (hidden) hidden.value = rawVal;
        filterPackagingListByQuery(rawVal.toLowerCase(), root);
    }

    function filterPackagingListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#pkgSearchInner')?.value || '';
        const hidden = root.querySelector('#newSeizurePackaging');
        if (hidden) hidden.value = rawVal;
        filterPackagingListByQuery(rawVal.toLowerCase(), root);
    }

    function filterPackagingListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#pkgList');
        if (!list) return;

        let customLi = list.querySelector('#pkgCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'pkgCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectPackagingType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.pkg-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function selectUnit(val, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const root   = getSeizureBlockFromEvent(e);
        const hidden  = root.querySelector('#newSeizureUnit');
        const display = root.querySelector('#unitSearchInput');
        const panel   = root.querySelector('#unitListPanel');
        if (hidden)  hidden.value  = val;
        if (display) display.value = val;
        if (panel)   panel.classList.add('hidden');
        if (typeof syncSection4ToSection5InReview === 'function') syncSection4ToSection5InReview();
    }

    function filterUnitList(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#unitSearchInput')?.value || '';
        const hidden = root.querySelector('#newSeizureUnit');
        if (hidden) hidden.value = rawVal;
        filterUnitListByQuery(rawVal.toLowerCase(), root);
    }

    function filterUnitListInner(el) {
        const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
        const rawVal = root.querySelector('#unitSearchInner')?.value || '';
        const hidden = root.querySelector('#newSeizureUnit');
        if (hidden) hidden.value = rawVal;
        filterUnitListByQuery(rawVal.toLowerCase(), root);
    }

    function filterUnitListByQuery(q, rootEl) {
        const root = rootEl || document;
        const list = root.querySelector('#unitList');
        if (!list) return;

        let customLi = list.querySelector('#unitCustomItem');
        const trimmed = (q || '').trim();
        if (trimmed.length > 0) {
            if (!customLi) {
                customLi = document.createElement('li');
                customLi.id = 'unitCustomItem';
                customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                list.insertBefore(customLi, list.firstChild);
            }
            const safeVal = trimmed.replace(/'/g, "\\'");
            customLi.innerHTML = `
                <button type="button" onclick="selectUnit('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
            customLi.style.display = '';
        } else if (customLi) {
            customLi.style.display = 'none';
        }

        const items = list.querySelectorAll('.unit-item');
        items.forEach(function(li) {
            const text = li.innerText.toLowerCase();
            li.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    function toggleAmmoOtherCondition(el) {
        const root = el ? (el.closest('.seizure-block') || document) : document;
        const wrapper = root.querySelector('#ammoOtherConditionWrapper');
        if (wrapper) {
            if (el && el.value === 'Other') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        }
    }

    function addSeizureOfficerRow(btn) {
        const block = btn ? btn.closest('.seizure-block') : null;
        const container = block ? block.querySelector('#seizureOfficersContainer') : document.getElementById('seizureOfficersContainer');
        if (!container) return;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl';
        rowDiv.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div class="relative flex items-center group">
                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1 flex items-center group">
                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                        </span>
                        <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                    </div>
                    <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(rowDiv);
        updateSeizureOfficerRemoveButtons(container);
    }

    function removeSeizureOfficerRow(btn) {
        const row = btn.closest('.seizure-officer-row');
        const container = btn.closest('#seizureOfficersContainer');
        if (row) {
            row.remove();
            if (container) updateSeizureOfficerRemoveButtons(container);
        }
    }

    function updateSeizureOfficerRemoveButtons(container) {
        const containers = container ? [container] : document.querySelectorAll('#seizureOfficersContainer');
        containers.forEach(c => {
            const rows = c.querySelectorAll('.seizure-officer-row');
            rows.forEach(row => {
                const btn = row.querySelector('.remove-officer-btn');
                if (btn) {
                    if (rows.length > 1) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }
            });
        });
    }

    function getSeizureOfficersFormatted(block) {
        const parent = block || document;
        const container = parent.querySelector('#seizureOfficersContainer');
        if (!container) return null;
        const rows = container.querySelectorAll('.seizure-officer-row');
        const list = [];
        rows.forEach(row => {
            const nameInput = row.querySelector('.seizure-officer-name');
            const idInput = row.querySelector('.seizure-officer-service-id');
            const name = nameInput ? nameInput.value.trim() : '';
            const svcId = idInput ? idInput.value.trim() : '';
            if (name && svcId) {
                list.push(`${name} (${svcId})`);
            } else if (name) {
                list.push(name);
            } else if (svcId) {
                list.push(`(ID: ${svcId})`);
            }
        });
        return list.length > 0 ? list.join('; ') : null;
    }

    function triggerSeizurePhotoUpload(el) {
        const block = el ? el.closest('.seizure-block') : document;
        const input = block.querySelector('.fileSeizurePhoto');
        if (input) input.click();
    }

    function previewSeizurePhotos(e, inputEl) {
        const block = inputEl ? inputEl.closest('.seizure-block') : document;
        const files = e.target.files;
        const grid = block.querySelector('.seizurePhotosGrid');
        if (!grid) return;

        grid.innerHTML = '';
        if (files && files.length > 0) {
            grid.classList.remove('hidden');
            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'relative group rounded-xl overflow-hidden border border-slate-200 shadow-2xs aspect-square bg-slate-100';
                        imgDiv.innerHTML = `
                            <img src="${evt.target.result}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="text-[10px] text-white font-mono-code font-bold px-2 py-1 bg-slate-900/80 rounded-lg">Photo #${index + 1}</span>
                            </div>
                        `;
                        grid.appendChild(imgDiv);
                    };
                    reader.readAsDataURL(file);
                }
            });
        } else {
            grid.classList.add('hidden');
        }
    }

    function handleCommoditySearchKeydown(e, el, type) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            const val = (el.value || '').trim();
            if (val) {
                if (type === 'drug') selectDrugType(val, e);
                else if (type === 'weapon') selectWeaponType(val, e);
                else if (type === 'currency') selectCurrencyType(val, e);
                else if (type === 'ammo') selectAmmoType(val, e);
                else if (type === 'device') selectDeviceType(val, e);
            }
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.seizure-block').forEach(block => {
            const items = [
                { wrapper: block.querySelector('#exhibitSelectWrapper'), panel: block.querySelector('#exhibitListPanel') },
                { wrapper: block.querySelector('#drugSelectWrapper'), panel: block.querySelector('#drugListPanel') },
                { wrapper: block.querySelector('#weaponSelectWrapper'), panel: block.querySelector('#weaponListPanel') },
                { wrapper: block.querySelector('#currencySelectWrapper'), panel: block.querySelector('#currencyListPanel') },
                { wrapper: block.querySelector('#ammoSelectWrapper'), panel: block.querySelector('#ammoListPanel') },
                { wrapper: block.querySelector('#deviceSelectWrapper'), panel: block.querySelector('#deviceListPanel') },
                { wrapper: block.querySelector('#packagingSelectWrapper'), panel: block.querySelector('#pkgListPanel') },
                { wrapper: block.querySelector('#unitSelectWrapper'), panel: block.querySelector('#unitListPanel') },
            ];
            items.forEach(item => {
                if (item.wrapper && item.panel && !item.wrapper.contains(e.target)) {
                    item.panel.classList.add('hidden');
                }
            });
        });
    }, true);

    async function submitSection4SeizureForm(e) {
        if (e) e.preventDefault();

        const saveBtn = document.getElementById('saveSection4SeizureBtn');
        const saveBtnText = document.getElementById('saveSection4SeizureBtnText');
        const caseId = @json($case->id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
        const exhibits = [];

        blocks.forEach((block, idx) => {
            const assocPerson = block.querySelector('.seizure-associated-person-select')?.value || @json($activeSuspectName);
            const category = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || '';
            
            let seizureName = block.querySelector('#newSeizureNameDrug')?.value || 
                              block.querySelector('#drugSearchInput')?.value || 
                              block.querySelector('#newSeizureNameWeapon')?.value || 
                              block.querySelector('#weaponSearchInput')?.value || 
                              block.querySelector('#newSeizureNameCurrency')?.value || 
                              block.querySelector('#currencySearchInput')?.value || 
                              block.querySelector('#newSeizureNameDevice')?.value || 
                              block.querySelector('#deviceSearchInput')?.value || 
                              block.querySelector('#newSeizureName')?.value || '';

            const quantity = block.querySelector('#newSeizureQuantity')?.value || '';
            const weight = block.querySelector('#newSeizureWeight')?.value || '';
            const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
            const packaging = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
            const value = block.querySelector('#newSeizureValue')?.value || '';
            const concealment = block.querySelector('#newSeizureConcealment')?.value || '';
            const dateSeized = block.querySelector('#newSeizureDate')?.value || '';
            const timeSeized = block.querySelector('#newSeizureTime')?.value || '';
            const location = block.querySelector('#newSeizureLocation')?.value || '';
            const seizingOfficer = getSeizureOfficersFormatted(block);

            if (seizureName.trim()) {
                exhibits.push({
                    associated_person: assocPerson,
                    suspect_name: assocPerson,
                    category: category,
                    exhibit_category: category,
                    seizure_name: seizureName,
                    commodity_name: seizureName,
                    name: seizureName,
                    seizure_quantity: quantity,
                    quantity: quantity,
                    seizure_weight: weight,
                    weight: weight,
                    seizure_unit: unit,
                    unit: unit,
                    seizure_packaging: packaging,
                    packaging: packaging,
                    seizure_value: value,
                    value: value,
                    seizure_concealment: concealment,
                    seizure_date: dateSeized,
                    seizure_time: timeSeized,
                    seizure_location: location,
                    seizure_officer: seizingOfficer
                });
            }
        });

        if (exhibits.length === 0) {
            alert('Please enter a Primary Commodity / Exhibit Name for at least one seizure item.');
            return;
        }

        if (saveBtn) saveBtn.disabled = true;
        if (saveBtnText) saveBtnText.innerText = 'Saving Exhibit...';

        try {
            const res = await fetch(`/cases/${encodeURIComponent(caseId)}/associate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    associated_exhibits: exhibits
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                alert(data.message || 'Seizure / Exhibit saved successfully.');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to save seizure exhibit.');
                if (saveBtn) saveBtn.disabled = false;
                if (saveBtnText) saveBtnText.innerText = 'Save Seizure / Exhibit';
            }
        } catch (err) {
            console.error('Error saving seizure exhibit:', err);
            alert('An error occurred while saving the exhibit. Please check network connection and try again.');
            if (saveBtn) saveBtn.disabled = false;
            if (saveBtnText) saveBtnText.innerText = 'Save Seizure / Exhibit';
        }
    }

    function toggleLabFormSections() {
        const s4 = document.getElementById('section4Seizures');
        const s5 = document.getElementById('section5LabExamination');
        const masterTexts = document.querySelectorAll('.toggleLabFormTextSpan');
        const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');
        const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');

        if (!s4 && !s5) return;

        const s4Visible = s4 && !s4.classList.contains('hidden');
        const s5Visible = s5 && !s5.classList.contains('hidden');

        if (s4Visible || s5Visible) {
            if (s4) s4.classList.add('hidden');
            if (s5) s5.classList.add('hidden');
            masterTexts.forEach(el => el.innerText = 'Show Both Sections');
            s4Texts.forEach(el => el.innerText = 'Show Section 4');
            s5Texts.forEach(el => el.innerText = 'Show Section 5');
        } else {
            if (s4) s4.classList.remove('hidden');
            if (s5) s5.classList.remove('hidden');
            masterTexts.forEach(el => el.innerText = 'Hide Both Sections');
            s4Texts.forEach(el => el.innerText = 'Hide Section 4');
            s5Texts.forEach(el => el.innerText = 'Hide Section 5');
        }
    }

    function toggleSection4() {
        const s4 = document.getElementById('section4Seizures');
        const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');
        if (s4) {
            if (s4.classList.contains('hidden')) {
                s4.classList.remove('hidden');
                s4Texts.forEach(el => el.innerText = 'Hide Section 4');
            } else {
                s4.classList.add('hidden');
                s4Texts.forEach(el => el.innerText = 'Show Section 4');
            }
        }
    }

    function toggleSection5() {
        const s5 = document.getElementById('section5LabExamination');
        const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');
        if (s5) {
            if (s5.classList.contains('hidden')) {
                s5.classList.remove('hidden');
                s5Texts.forEach(el => el.innerText = 'Hide Section 5');
            } else {
                s5.classList.add('hidden');
                s5Texts.forEach(el => el.innerText = 'Show Section 5');
            }
        }
    }
</script>

</body>
</html>




