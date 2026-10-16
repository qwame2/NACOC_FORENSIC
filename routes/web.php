<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use App\Models\ForensicCase;
use App\Http\Middleware\EnsureHeadOfficer;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC / UNAUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/api/check-user-approval', function (\Illuminate\Http\Request $request) {
    $loginId = trim($request->query('login_id', ''));
    if (!$loginId) {
        return response()->json(['exists' => false]);
    }

    $user = User::where('service_id', $loginId)
        ->orWhere('username', $loginId)
        ->orWhere('email', $loginId)
        ->first();

    if (!$user) {
        return response()->json(['exists' => false]);
    }

    return response()->json([
        'exists' => true,
        'is_approved' => (bool) $user->is_approved,
        'is_head' => (bool) $user->is_head,
        'fullname' => $user->fullname,
        'service_id' => $user->service_id,
        'username' => $user->username,
    ]);
});
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout']);


/*
|--------------------------------------------------------------------------
| 2. AUTHENTICATED OFFICER ROUTES (AUTH MIDDLEWARE)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // AUTHENTICATION LOGOUT
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // OFFICER PROFILE & SETTINGS
    Route::get('/profile/settings', [ProfileController::class, 'edit'])->name('profile.settings');
    Route::get('/profile', function () {
        return redirect()->route('profile.settings');
    })->name('profile');
    Route::match(['put', 'post'], '/profile/settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::match(['put', 'post'], '/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::match(['delete', 'post'], '/profile/avatar/remove', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::match(['delete', 'post'], '/profile/signature/remove', [ProfileController::class, 'removeSignature'])->name('profile.signature.remove');

    // MASTER CASE DIRECTORY & HISTORY ROUTES
    Route::get('/history', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $cases = ForensicCase::allCases();
        $unitFilter = $request->query('unit') ?? $request->query('from') ?? ($user ? $user->unit : 'All Units');
        return view('history', compact('cases', 'unitFilter'));
    })->name('history');

    Route::get('/History', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $cases = ForensicCase::allCases();
        $unitFilter = $request->query('unit') ?? $request->query('from') ?? ($user ? $user->unit : 'All Units');
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/Crime_Scene_Support/history', function (\Illuminate\Http\Request $request) {
        $cases = ForensicCase::allCases();
        $unitFilter = 'Crime Scene Support';
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/Lab_Analysis/history', function (\Illuminate\Http\Request $request) {
        $cases = ForensicCase::allCases();
        $unitFilter = 'Lab Analysis';
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/Evidence_Management/history', function (\Illuminate\Http\Request $request) {
        $cases = ForensicCase::allCases();
        $unitFilter = 'Evidence Management';
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/Digital_Forensics/history', function (\Illuminate\Http\Request $request) {
        $cases = ForensicCase::allCases();
        $unitFilter = 'Digital & Physical Forensics';
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/Forensic_Head/history', function (\Illuminate\Http\Request $request) {
        $cases = ForensicCase::allCases();
        $unitFilter = 'All Units';
        return view('history', compact('cases', 'unitFilter'));
    });

    Route::get('/cases', function () {
        $cases = ForensicCase::allCases();
        return view('cases.landing', compact('cases'));
    });

    Route::get('/cases/landing', function () {
        $cases = ForensicCase::allCases();
        return view('cases.landing', compact('cases'));
    });

    Route::get('/Crime_Scene_Support/cases', function () {
        $cases = ForensicCase::allCases();
        return view('cases.landing', compact('cases'));
    });

    Route::get('/api/cases', [CaseController::class, 'index']);
    Route::post('/api/cases/check-duplicate-suspect', [CaseController::class, 'checkDuplicateSuspect']);
    Route::post('/api/cases/check-duplicate-case', [CaseController::class, 'checkDuplicateCase']);
    Route::get('/cases/{id}/review', [CaseController::class, 'showReview'])->name('cases.review');
    Route::get('/cases/{id}/edit', [CaseController::class, 'showEdit'])->name('cases.edit');
    Route::get('/cases/{id}/pdf', [CaseController::class, 'showPdf'])->name('cases.pdf');
    Route::get('/cases/{id}/associate', [CaseController::class, 'showAssociate'])->name('cases.associate');

    // CASE CREATION & UNIT PROGRESS UPDATES
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::post('/cases/{id}/update', [CaseController::class, 'updateCase'])->name('cases.update');
    Route::post('/cases/{id}/update-unit-progress', [CaseController::class, 'updateUnitProgress']);
    Route::post('/cases/{id}/associate', [CaseController::class, 'associate']);
    Route::post('/cases/upload-evidence-photos', [CaseController::class, 'uploadEvidencePhotos']);
    Route::post('/cases/{id}/reassign-exhibit', [CaseController::class, 'reassignExhibit']);
    Route::post('/cases/{id}/case-status', [CaseController::class, 'updateCaseStatus']);
    Route::post('/cases/{id}/suspect-disposition', [CaseController::class, 'updateSuspectDisposition']);
    Route::post('/cases/{id}/handover/initiate', [CaseController::class, 'initiateHandover']);
    Route::post('/cases/{id}/handover/respond', [CaseController::class, 'respondHandover']);
    Route::post('/cases/{id}/upload-gsa-report', [CaseController::class, 'storeGsaReports']);
    Route::post('/Lab_Analysis/gsa-reports/store', [CaseController::class, 'storeGsaReports']);
    Route::get('/cases/{id}/audit-trail', [CaseController::class, 'getAuditTrail']);

    // GENERAL DOCKET & EVENT ROUTES
    Route::get('/cases/create', function () {
        return view('cases.create');
    })->name('cases.create');

    Route::get('/arrest-person', function (\Illuminate\Http\Request $request) {
        $authUser = auth()->user();
        $unit = $request->query('unit') ?? $request->query('from') ?? ($authUser ? $authUser->unit : session('user_unit'));
        if (!$unit || str_contains(strtolower($unit), 'crime') || str_contains(strtolower($unit), 'field')) {
            return view('Crime_Scene_Support.head.CrimeSceneCreate');
        } elseif (str_contains(strtolower($unit), 'lab')) {
            return view('Lab_Analysis.create');
        } elseif (str_contains(strtolower($unit), 'evidence')) {
            return view('Evidence_Management.create');
        } elseif (str_contains(strtolower($unit), 'digital') || str_contains(strtolower($unit), 'physical')) {
            return view('Digital_Forensics.create');
        }
        return view('cases.create');
    })->name('arrest_person');

    Route::get('/Crime_Scene_Support/create', function () {
        return view('Crime_Scene_Support.head.CrimeSceneCreate');
    })->name('crime_scene.create');

    Route::get('/Crime_Scene_Support/head/CrimeSceneCreate', function () {
        return view('Crime_Scene_Support.head.CrimeSceneCreate');
    });

    Route::get('/Crime_Scene_Support/head/create', function () {
        return view('Crime_Scene_Support.head.CrimeSceneCreate');
    });

    Route::get('/Crime_Scene_Support/arrest-person', function () {
        return view('Crime_Scene_Support.head.CrimeSceneCreate');
    });

    Route::get('/cargo-parcel-interception', function () {
        return view('Crime_Scene_Support.staff.events.cargo_parcel_interception');
    });

    Route::get('/raid', function () {
        return view('Crime_Scene_Support.staff.events.raid');
    });

    Route::get('/search-warrant', function () {
        return view('Crime_Scene_Support.staff.events.search_warrant');
    });

    Route::get('/intel-led-ops', function () {
        return view('Crime_Scene_Support.staff.events.intel_led_ops');
    });

    Route::get('/transferred-case', function () {
        return view('Crime_Scene_Support.staff.events.transferred_case');
    });

    // CRIME SCENE SUPPORT DEDICATED EVENT PAGES
    Route::get('/Crime_Scene_Support/staff/cargo-parcel-interception', function () {
        return view('Crime_Scene_Support.staff.events.cargo_parcel_interception');
    });

    Route::get('/Crime_Scene_Support/staff/raid', function () {
        return view('Crime_Scene_Support.staff.events.raid');
    });

    Route::get('/Crime_Scene_Support/staff/search-warrant', function () {
        return view('Crime_Scene_Support.staff.events.search_warrant');
    });

    Route::get('/Crime_Scene_Support/staff/intel-led-ops', function () {
        return view('Crime_Scene_Support.staff.events.intel_led_ops');
    });

    Route::get('/Crime_Scene_Support/staff/transferred-case', function () {
        return view('Crime_Scene_Support.staff.events.transferred_case');
    });

    Route::get('/Crime_Scene_Support/staff/arrest-person', function () {
        return view('Crime_Scene_Support.head.CrimeSceneCreate');
    });

    Route::get('/Crime_Scene_Support', function () {
        return view('Crime_Scene_Support.head.head');
    });

    Route::get('/Crime_Scene_Support/head', function () {
        return view('Crime_Scene_Support.head.head');
    });

    Route::get('/Crime_Scene_Support/staff', function () {
        return view('Crime_Scene_Support.staff.staff');
    });

    Route::get('/crime-scene-support', function () {
        return view('Crime_Scene_Support.head.head');
    });

    // LAB ANALYSIS UNIT DEDICATED ROUTES
    Route::get('/Lab_Analysis', function () {
        return view('Lab_Analysis.lab_analysis');
    });

    Route::get('/Lab_Analysis/head', function () {
        return view('Lab_Analysis.lab_analysis');
    });

    Route::get('/Lab_Analysis/staff', function () {
        return view('Lab_Analysis.staff.staff');
    });

    Route::get('/lab-analysis', function () {
        return view('Lab_Analysis.lab_analysis');
    });

    Route::get('/lab-result', [CaseController::class, 'labResultPage'])->name('lab.result');
    Route::get('/Lab_Analysis/lab-result', [CaseController::class, 'labResultPage']);
    Route::get('/Lab_Analysis/certificate/{id}', [CaseController::class, 'showLabCertificate'])->name('lab.certificate');
    Route::get('/lab-certificate/{id}', [CaseController::class, 'showLabCertificate']);
    Route::get('/Lab_Analysis/analytical/{id}', [CaseController::class, 'showLabAnalytical'])->name('lab.analytical');
    Route::get('/lab-analytical/{id}', [CaseController::class, 'showLabAnalytical']);
    Route::get('/Lab_Analysis/nlr-report-pdf/{id}', [CaseController::class, 'showNlrReportPdf'])->name('lab.nlr.pdf');
    Route::get('/lab-nlr-pdf/{id}', [CaseController::class, 'showNlrReportPdf']);
    Route::post('/Lab_Analysis/nlr-reports/store', [CaseController::class, 'storeNlrReport'])->name('lab.nlr.store');

    Route::get('/Lab_Analysis/examination', function () {
        $case = \App\Models\ForensicCase::find(request('case_id'));
        return view('Lab_Analysis.examination', compact('case'));
    });

    Route::get('/Lab_Analysis/create', function () {
        return view('Lab_Analysis.create');
    })->name('lab_analysis.create');

    // GSA Report Upload — store files linked to a suspect
    Route::post('/Lab_Analysis/gsa-reports/store', [CaseController::class, 'storeGsaReports'])
         ->name('lab.gsa.store');

    // GSA Report Fetch — get all active GSA files for a suspect (used in docket view)
    Route::get('/Lab_Analysis/gsa-reports/{suspectId}', function ($suspectId) {
        $reports = \App\Models\SuspectGsaReport::where('case_suspect_id', $suspectId)
                    ->where('status', 'active')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn($r) => [
                        'id'                => $r->id,
                        'original_filename' => $r->original_filename,
                        'file_extension'    => $r->file_extension,
                        'file_size_label'   => $r->file_size_label,
                        'url'               => $r->url,
                        'is_image'          => $r->is_image,
                        'uploaded_at'       => $r->created_at->format('d M Y, H:i'),
                    ]);
        return response()->json(['success' => true, 'reports' => $reports]);
    })->name('lab.gsa.fetch');


    // EVIDENCE MANAGEMENT UNIT DEDICATED ROUTES
    Route::get('/Evidence_Management', function () {
        return view('Evidence_Management.evidence_management');
    });

    Route::get('/Evidence_Management/create', function () {
        return view('Evidence_Management.create');
    })->name('evidence_management.create');

    Route::get('/Evidence_Management/head', function () {
        return view('Evidence_Management.evidence_management');
    });

    Route::get('/Evidence_Management/staff', function () {
        return view('Evidence_Management.staff.staff');
    });

    Route::get('/evidence-management', function () {
        return view('Evidence_Management.evidence_management');
    });

    // DIGITAL & PHYSICAL FORENSICS UNIT DEDICATED ROUTES
    Route::get('/Digital_Forensics', function () {
        return view('Digital_Forensics.digital_forensics');
    });

    Route::get('/Digital_Forensics/forensic-status', function () {
        return view('Digital_Forensics.forensic_status');
    });

    Route::get('/digital-forensics/forensic-status', function () {
        return view('Digital_Forensics.forensic_status');
    });

    Route::get('/Digital_Forensics/dpf-report-pdf/{id?}', function ($id = null) {
        $authUser = auth()->user();
        $report = null;
        $case = null;
        $rawSuspects = [];
        $deviceSpecsList = [];
        $attachedFiles = [];
        $exhibitTypeDisplay = null;
        $suspectReports = [];

        $cleanStatusDisplay = function ($val, $default = 'NO') {
            $str = trim((string)($val ?: $default));
            if ($str === '') return $default;
            if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.*)$/is', $str, $matches)) {
                $status = strtoupper(trim($matches[1]));
                $msg = trim($matches[2]);
                $msg = preg_replace('/^' . preg_quote($status, '/') . '\s*[-–—:]\s*/i', '', $msg);
                $msg = trim($msg);
                if ($msg === '' || strtoupper($msg) === $status || in_array(strtoupper($msg), ['N/A', 'NONE', 'NIL', 'NULL', '-', 'NO', 'YES'])) {
                    return $status;
                }
                return $status . ' - ' . $msg;
            }
            if (in_array(strtoupper($str), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                return strtoupper($str);
            }
            return $str;
        };

        $buildSuspectReportItem = function ($cItem, $rItem, $sObj, $idx, $cleanStatusDisplay) {
            $cNo = $cItem->case_number ?? ($rItem->case_number ?? 'NCC-2026');
            $sName = strtoupper($sObj['suspect_name'] ?? ($sObj['name'] ?? 'SUSPECT'));
            $sRole = strtoupper($sObj['role'] ?? ($sObj['suspect_role'] ?? ($idx === 0 ? 'PRIMARY SUSPECT' : 'CO-SUSPECT')));
            $sMug = $sObj['mugshot_front'] ?? ($sObj['mugshots']['front'] ?? ($cItem->mugshot_front ?? null));
            if (!empty($sMug) && !str_starts_with($sMug, 'data:image') && !str_starts_with($sMug, 'http://') && !str_starts_with($sMug, 'https://') && !str_starts_with($sMug, '/')) {
                $sMug = asset($sMug);
            }
            $sNationality = $sObj['nationality'] ?? ($cItem->nationality ?? 'Ghanaian');
            $sGender = !empty($sObj['gender']) ? ucfirst($sObj['gender']) : null;
            $sAge = !empty($sObj['age']) ? $sObj['age'] . ' yrs' : null;
            $sMeta = implode(' &bull; ', array_filter([$sGender, $sAge, $sNationality])) ?: 'Ghanaian';

            $underlying = $cItem->underlyingRecord ?? $cItem;
            $cExhibits = collect();
            if ($underlying instanceof \App\Models\MultipleSuspectCase) {
                $cExhibits = \App\Models\Exhibit::where('multiple_suspect_case_id', $underlying->id)->get();
            } elseif ($underlying instanceof \App\Models\SingleSuspectCase) {
                $cExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $underlying->id)->get();
            }
            $caseSpecs = [];
            $exParts = [];
            if ($cExhibits->isNotEmpty()) {
                foreach ($cExhibits as $ex) {
                    $devName = $ex->dev_model ?: ($ex->commodity_name ?: ($ex->exhibit_category ?: ($ex->exhibit_type ?: 'Electronic Exhibit')));
                    $qty = $ex->seizure_quantity ?: '1';
                    $caseSpecs[] = [
                        'exhibit_no'  => $ex->exhibit_number ?: null,
                        'device_name' => $devName,
                        'quantity'    => $qty,
                        'model'       => $ex->dev_model ?: 'N/A',
                        'serial'      => $ex->dev_serial_no ?: 'N/A',
                        'imei1'       => $ex->dev_imei1 ?: 'N/A',
                        'imei2'       => $ex->dev_imei2 ?: 'N/A',
                        'mac'         => $ex->dev_mac_address ?: 'N/A',
                        'storage'     => $ex->dev_storage ?: 'N/A',
                        'colour'      => $ex->dev_colour ?: 'N/A',
                        'marks'       => $ex->dev_marks ?: ($ex->other_exhibit_description ?: 'N/A'),
                    ];
                    if (!empty($devName) && strtoupper($devName) !== 'N/A') {
                        if (!empty($qty) && !preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName)) {
                            $exParts[] = $qty . ' ' . $devName;
                        } else {
                            $exParts[] = $devName;
                        }
                    }
                }
            }
            if (empty($caseSpecs)) {
                $dData = is_array($cItem->docket_data ?? null) ? $cItem->docket_data : (json_decode($cItem->docket_data ?? '[]', true) ?: []);
                $sPhones = $cItem->phones ?? ($cItem->underlyingRecord->phones ?? ($dData['phones'] ?? ($sObj['phones'] ?? [])));
                if (is_string($sPhones)) $sPhones = json_decode($sPhones, true) ?: [];
                if (is_array($sPhones) && !empty($sPhones)) {
                    foreach ($sPhones as $pIdx => $ph) {
                        if (is_array($ph) && !empty($ph['number']) && empty($ph['model']) && empty($ph['brand']) && empty($ph['name'])) continue;
                        $pModel = is_array($ph) ? ($ph['model'] ?? ($ph['name'] ?? ($ph['brand'] ?? null))) : (is_string($ph) && !preg_match('/^\+?[0-9\s\-()]{5,}$/', trim($ph)) ? trim($ph) : null);
                        if (empty($pModel) || in_array($pModel, ['Mobile Phone Device', 'N/A'])) continue;
                        $caseSpecs[] = [
                            'exhibit_no' => 'PH-EX-' . ($pIdx + 1),
                            'device_name' => $pModel,
                            'quantity' => '1',
                            'model' => $pModel,
                            'serial' => is_array($ph) ? ($ph['serial'] ?? 'N/A') : 'N/A',
                            'imei1' => is_array($ph) ? ($ph['imei'] ?? ($ph['imei1'] ?? 'N/A')) : 'N/A',
                            'imei2' => is_array($ph) ? ($ph['imei2'] ?? 'N/A') : 'N/A',
                            'mac' => is_array($ph) ? ($ph['mac'] ?? 'N/A') : 'N/A',
                            'storage' => is_array($ph) ? ($ph['storage'] ?? 'N/A') : 'N/A',
                            'colour' => is_array($ph) ? ($ph['colour'] ?? 'N/A') : 'N/A',
                            'marks' => 'Recovered from suspect possession',
                        ];
                        $exParts[] = '1 ' . $pModel;
                    }
                }
            }
            $caseExhibitDisplay = !empty($exParts) ? implode(', ', array_unique($exParts)) : ($rItem->devices_retrieved ?? ($cItem->dev_model ?? 'Mobile Devices & Exhibits'));

            $cBrief = $rItem->brief_case_description ?? ($cItem->description ?? 'Target intercepted with encrypted mobile hardware. Write-blocked forensic imaging executed under judicial authorization.');
            $cHasDrug = false;
            $cHasElec = false;
            if ($cItem) {
                if (method_exists($cItem, 'classifyExhibits')) {
                    $cFlags = $cItem->classifyExhibits();
                    $cHasDrug = !empty($cFlags['has_drug']);
                    $cHasElec = !empty($cFlags['has_electronic']);
                }
                if (method_exists($cItem, 'isDrugCase') && $cItem->isDrugCase()) $cHasDrug = true;
                if (method_exists($cItem, 'isDigitalCase') && $cItem->isDigitalCase()) $cHasElec = true;
                if (!empty($cItem->phones) || !empty($cItem->underlyingRecord->phones)) $cHasElec = true;
            }
            if ($cHasElec && !$cHasDrug && str_contains($cBrief, 'narcotic drugs')) {
                $cBrief = str_replace(
                    [
                        'to curb the use and sale of narcotic drugs within the area',
                        'to curb the use and sale of narcotic drugs',
                        'use and sale of narcotic drugs',
                        'sale of narcotic drugs',
                        'narcotic drugs',
                    ],
                    [
                        'to intercept and seize illicit electronic devices and digital evidence within the area',
                        'to intercept and seize illicit electronic devices and digital evidence within the area',
                        'illicit digital evidence and mobile exhibits',
                        'illicit electronic hardware',
                        'electronic devices',
                    ],
                    $cBrief
                );
            }

            $cCourtReq = strtoupper($rItem->court_order_requested ?? 'NO');
            $cCourtGrant = strtoupper($rItem->court_order_granted ?? 'NO');
            $cExt = $cleanStatusDisplay($rItem->extraction_analysis_message ?? ($rItem->extraction_analysis_conducted ?? 'NO'));
            $cWitness = $cleanStatusDisplay($rItem->report_witness_statement_message ?? ($rItem->report_witness_statement_sent ?? 'NO'));
            $cIntel = strtoupper($rItem->intel_shared ?? 'NO');
            $cRemark = trim((string)($rItem->remark ?? ''));
            if (empty($cRemark) || in_array($cRemark, ['Registry intake logged. Electronic exhibits pending forensic write-block extraction and court order verification.', 'Forensic acquisition completed cleanly. Hardware hash checksums match baseline acquisition.', 'N/A', 'NONE', 'NIL', 'null'])) {
                $cRemark = 'N/A';
            }

            $caseAttachments = [];
            $coFiles = $rItem->court_order_files ?? [];
            if (is_array($coFiles)) {
                foreach ($coFiles as $cIdx => $cPath) {
                    if (!empty($cPath) && is_string($cPath)) {
                        $caseAttachments[] = [
                            'id' => 'attachment-co-' . ($rItem->id ?? rand()) . '-' . $cIdx,
                            'path' => $cPath,
                            'section' => 'Court Order Warrant',
                            'title' => 'Judicial Court Order Warrant',
                            'badge' => 'COURT ORDER WARRANT',
                        ];
                    }
                }
            }
            $rwFiles = $rItem->report_witness_statement_files ?? [];
            if (is_array($rwFiles)) {
                foreach ($rwFiles as $rIdx => $rPath) {
                    if (!empty($rPath) && is_string($rPath)) {
                        $caseAttachments[] = [
                            'id' => 'attachment-rw-' . ($rItem->id ?? rand()) . '-' . $rIdx,
                            'path' => $rPath,
                            'section' => 'Report, Witness Statement & Digital Evidence',
                            'title' => 'Report, Witness Statement & Digital Evidence',
                            'badge' => 'REPORT, WITNESS STATEMENT & DIGITAL EVIDENCE',
                        ];
                    }
                }
            }

            return [
                'case_number' => $cNo,
                'dpf_code' => $rItem->dpf_code ?? ('DPF-' . $cNo),
                'suspect_name' => $sName,
                'role' => $sRole,
                'mugshot_front' => $sMug,
                'meta' => $sMeta,
                'nationality' => $sNationality,
                'gender' => $sGender,
                'age' => $sAge,
                'ghana_card' => $sObj['ghana_card'] ?? ($cItem->ghana_card ?? null),
                'investigation_date' => $rItem->investigation_date ? (string)$rItem->investigation_date : ($cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d')),
                'report_date' => $rItem->created_at ? $rItem->created_at->format('Y-m-d') : date('Y-m-d'),
                'location_post' => $rItem->location_post ?? ($cItem->location ?? 'Kotoka International Airport (Cargo Wing)'),
                'raid_location' => $cItem->raid_location ?? ($cItem->seizure_location ?? ($rItem->location_post ?? 'Kotoka International Airport (Cargo Wing)')),
                'submitting_officer' => $cItem->submitting_officer ?? 'Det. Insp. K. Mensah',
                'exhibit_type_display' => $caseExhibitDisplay,
                'device_specs' => $caseSpecs,
                'brief_description' => $cBrief,
                'court_requested' => $cCourtReq,
                'court_granted' => $cCourtGrant,
                'extraction_status' => $cExt,
                'report_witness_status' => $cWitness,
                'intel_shared' => $cIntel,
                'remark' => $cRemark,
                'case_section_label' => ($cCourtGrant === 'YES') ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)' : 'CURRENT/NEW CASES',
                'attached_files' => $caseAttachments,
            ];
        };

        // Check if multiple cases are requested via query string 'cases'
        $caseParam = request('cases');
        $requestedCaseNumbers = [];
        if (!empty($caseParam)) {
            $requestedCaseNumbers = array_filter(array_map('trim', explode(',', $caseParam)));
        }

        if (!empty($requestedCaseNumbers)) {
            $allCases = \App\Models\ForensicCase::allCases();
            $matchedCases = collect();
            $matchedReports = collect();
            $suspectReports = [];

            foreach ($requestedCaseNumbers as $reqCaseNo) {
                $cleanReq = strtolower(trim($reqCaseNo));
                $foundCase = $allCases->first(function($c) use ($cleanReq) {
                    $cNo = strtolower(trim($c->case_number ?? ''));
                    $labNo = strtolower(trim($c->laboratory_number ?? ''));
                    $cId = (string)($c->id ?? '');
                    return $cNo === $cleanReq || $labNo === $cleanReq || $cId === $cleanReq;
                });

                if (!$foundCase) {
                    $foundCase = \App\Models\SingleSuspectCase::where('case_number', $reqCaseNo)
                        ->orWhereRaw('LOWER(case_number) = ?', [$cleanReq])
                        ->first()
                        ?? \App\Models\MultipleSuspectCase::where('case_number', $reqCaseNo)
                        ->orWhereRaw('LOWER(case_number) = ?', [$cleanReq])
                        ->first();
                }

                $foundReport = \App\Models\DpfReport::where(function($q) use ($reqCaseNo, $cleanReq) {
                    $q->where('case_number', $reqCaseNo)
                      ->orWhere('dpf_code', $reqCaseNo)
                      ->orWhereRaw('LOWER(case_number) = ?', [$cleanReq])
                      ->orWhereRaw('LOWER(dpf_code) = ?', [$cleanReq]);
                })->latest('id')->first();

                if ($foundCase) {
                    $matchedCases->push($foundCase);
                }
                if ($foundReport) {
                    $matchedReports->push($foundReport);
                }

                // Get suspects for this specific case
                $dData = is_array($foundCase->docket_data ?? null) ? $foundCase->docket_data : (json_decode($foundCase->docket_data ?? '[]', true) ?: []);
                $cSuspects = [];
                if (!empty($dData['suspects']) && is_array($dData['suspects'])) {
                    $cSuspects = $dData['suspects'];
                } elseif (isset($foundCase) && method_exists($foundCase, 'getSuspectsList')) {
                    $cSuspects = $foundCase->getSuspectsList();
                } elseif (!empty($foundCase->suspects_list) && is_array($foundCase->suspects_list)) {
                    $cSuspects = $foundCase->suspects_list;
                } elseif (isset($foundCase->suspects) && count($foundCase->suspects) > 0) {
                    $cSuspects = $foundCase->suspects->toArray();
                } elseif (isset($foundCase->underlyingRecord) && isset($foundCase->underlyingRecord->suspects) && count($foundCase->underlyingRecord->suspects) > 0) {
                    $cSuspects = $foundCase->underlyingRecord->suspects->toArray();
                }

                if (empty($cSuspects)) {
                    $cSuspects[] = [
                        'suspect_name'  => $foundCase->suspect_name ?? ($foundReport->report_data['suspect_name'] ?? 'SUSPECT'),
                        'role'          => 'PRIMARY SUSPECT',
                        'mugshot_front' => $foundCase->mugshot_front ?? null,
                        'nationality'   => $foundCase->nationality ?? 'Ghanaian',
                        'gender'        => $foundCase->gender ?? null,
                        'age'           => $foundCase->age ?? null,
                        'ghana_card'    => $foundCase->ghana_card ?? null,
                    ];
                }

                // If foundReport doesn't exist, generate a fallback object for this case
                if (!$foundReport) {
                    $foundReport = new \App\Models\DpfReport([
                        'case_number' => $foundCase ? $foundCase->case_number : $reqCaseNo,
                        'dpf_code' => 'DPF-' . ($foundCase ? $foundCase->case_number : $reqCaseNo),
                        'location_post' => $foundCase ? ($foundCase->location ?? 'Kotoka International Airport (Cargo Wing)') : 'Kotoka International Airport (Cargo Wing)',
                        'investigation_date' => $foundCase && $foundCase->created_at ? $foundCase->created_at->format('Y-m-d') : date('Y-m-d'),
                        'devices_retrieved' => 'Electronic Exhibits',
                        'quantity' => '1',
                        'brief_case_description' => $foundCase ? ($foundCase->description ?? 'Target intercepted with encrypted mobile hardware.') : 'Target intercepted with encrypted mobile hardware.',
                        'court_order_requested' => 'NO',
                        'court_order_granted' => 'NO',
                        'extraction_analysis_conducted' => 'NO',
                        'extraction_analysis_message' => 'NO',
                        'report_witness_statement_sent' => 'NO',
                        'report_witness_statement_message' => 'NO',
                        'intel_shared' => 'NO',
                        'remark' => 'N/A',
                        'created_at' => now(),
                    ]);
                }

                // Treat each case as one report item, linking primary and co-suspects
                $primarySuspect = $cSuspects[0] ?? [
                    'suspect_name'  => $foundCase->suspect_name ?? ($foundReport->report_data['suspect_name'] ?? 'SUSPECT'),
                    'role'          => 'PRIMARY SUSPECT',
                    'mugshot_front' => $foundCase->mugshot_front ?? null,
                    'nationality'   => $foundCase->nationality ?? 'Ghanaian',
                    'gender'        => $foundCase->gender ?? null,
                    'age'           => $foundCase->age ?? null,
                    'ghana_card'    => $foundCase->ghana_card ?? null,
                ];
                $coSuspects = array_slice($cSuspects, 1);

                $sItem = $buildSuspectReportItem($foundCase, $foundReport, $primarySuspect, 0, $cleanStatusDisplay);
                if (!empty($coSuspects)) {
                    $sItem['co_suspects'] = $coSuspects;
                    $coNames = array_map(fn($cs) => strtoupper($cs['suspect_name'] ?? ($cs['name'] ?? '')), $coSuspects);
                    $sItem['co_suspects_display'] = implode(', ', array_filter($coNames));
                } else {
                    $sItem['co_suspects'] = [];
                    $sItem['co_suspects_display'] = '';
                }
                $sItem['all_suspects'] = $cSuspects;
                $suspectReports[] = $sItem;

                foreach ($cSuspects as $sObj) {
                    $rawSuspects[] = [
                        'suspect_name' => $sObj['suspect_name'] ?? ($sObj['name'] ?? 'SUSPECT'),
                        'role' => $sObj['role'] ?? 'PRIMARY SUSPECT',
                        'mugshot_front' => $sObj['mugshot_front'] ?? null,
                        'nationality' => $sObj['nationality'] ?? 'Ghanaian',
                        'gender' => $sObj['gender'] ?? null,
                        'age' => $sObj['age'] ?? null,
                        'case_number' => $sItem['case_number'],
                    ];
                }
            }

            // Always ensure CURRENT/NEW CASES come first before ONGOING CASES
            if (!empty($suspectReports)) {
                $indexed = [];
                foreach ($suspectReports as $i => $sr) {
                    $indexed[] = ['idx' => $i, 'item' => $sr];
                }
                usort($indexed, function($a, $b) {
                    $aIsOngoing = (strtoupper($a['item']['court_granted'] ?? 'NO') === 'YES' || (isset($a['item']['case_section_label']) && str_contains(strtoupper($a['item']['case_section_label']), 'ONGOING')));
                    $bIsOngoing = (strtoupper($b['item']['court_granted'] ?? 'NO') === 'YES' || (isset($b['item']['case_section_label']) && str_contains(strtoupper($b['item']['case_section_label']), 'ONGOING')));

                    $aRank = $aIsOngoing ? 1 : 0;
                    $bRank = $bIsOngoing ? 1 : 0;

                    if ($aRank !== $bRank) {
                        return $aRank <=> $bRank;
                    }
                    return $a['idx'] <=> $b['idx'];
                });
                $suspectReports = array_values(array_column($indexed, 'item'));
                $rawSuspects = array_map(function($sItem) {
                    return [
                        'suspect_name' => $sItem['suspect_name'],
                        'role' => $sItem['role'],
                        'mugshot_front' => $sItem['mugshot_front'],
                        'nationality' => $sItem['nationality'],
                        'gender' => $sItem['gender'],
                        'age' => $sItem['age'],
                        'case_number' => $sItem['case_number'],
                    ];
                }, $suspectReports);
            }

            // Summary objects for cover page
            $allExhibits = array_filter(array_map(fn($sr) => $sr['exhibit_type_display'], $suspectReports));
            $exhibitTypeDisplay = implode(', ', array_unique($allExhibits));
            $report = $matchedReports->first() ?? ($suspectReports[0] ?? null);
            $case = $matchedCases->first() ?? null;

            return view('Digital_Forensics.multi_dpf_pdf', compact('suspectReports', 'report', 'case', 'rawSuspects', 'exhibitTypeDisplay'));
        }

        // 1. If an ID / Case Ref is specified, first check for an existing saved DpfReport
        if (!empty($id)) {
            $cleanId = trim($id);
            $report = \App\Models\DpfReport::where(function($q) use ($cleanId) {
                $q->where('dpf_code', $cleanId)
                  ->orWhere('case_number', $cleanId)
                  ->orWhereRaw('LOWER(case_number) = ?', [strtolower($cleanId)])
                  ->orWhereRaw('LOWER(dpf_code) = ?', [strtolower($cleanId)])
                  ->orWhere('id', $cleanId);
            })->latest('id')->first();

            // 2. Also locate the specific case matching the requested $id
            $case = \App\Models\ForensicCase::allCases()->first(function($c) use ($cleanId) {
                $cNo = strtolower(trim($c->case_number ?? ''));
                $labNo = strtolower(trim($c->laboratory_number ?? ''));
                $cId = (string)($c->id ?? '');
                $target = strtolower($cleanId);
                return $cNo === $target || $labNo === $target || $cId === $target;
            });

            if (!$case) {
                $case = \App\Models\SingleSuspectCase::where('case_number', $cleanId)
                    ->orWhereRaw('LOWER(case_number) = ?', [strtolower($cleanId)])
                    ->first()
                    ?? \App\Models\MultipleSuspectCase::where('case_number', $cleanId)
                    ->orWhereRaw('LOWER(case_number) = ?', [strtolower($cleanId)])
                    ->first();
            }
        }

        // 3. If $report was found, make sure $case matches the report's case_number if not already found
        if ($report && !$case) {
            $case = \App\Models\ForensicCase::allCases()->first(function($c) use ($report) {
                return strtolower(trim($c->case_number ?? '')) === strtolower(trim($report->case_number ?? ''));
            });

            if (!$case) {
                $case = \App\Models\SingleSuspectCase::where('case_number', $report->case_number)
                    ->orWhereRaw('LOWER(case_number) = ?', [strtolower(trim($report->case_number))])
                    ->first()
                    ?? \App\Models\MultipleSuspectCase::where('case_number', $report->case_number)
                    ->orWhereRaw('LOWER(case_number) = ?', [strtolower(trim($report->case_number))])
                    ->first();
            }
        }

        // 4. CRITICAL: If an $id was requested, DO NOT fall back to an old saved report from another suspect!
        // Instead, dynamically generate a DpfReport instance for THIS SPECIFIC REQUESTED CASE / SUSPECT.
        if (!empty($id) && !$report && $case) {
            $cNo = $case->case_number ?? $id;
            $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);

            $loc = $case->raid_location 
                ?? ($case->seizure_location 
                ?? ($dData['raid_location'] 
                ?? ($dData['seizure_location'] 
                ?? ($dData['seizures'][0]['seizure_location'] 
                ?? ($case->location ?? 'Kotoka International Airport (Cargo Wing)')))));

            $exList = [];
            $underlying = $case->underlyingRecord ?? $case;
            if (isset($underlying->exhibits) && count($underlying->exhibits) > 0) {
                foreach ($underlying->exhibits as $ex) {
                    $itemStr = '';
                    $devName = !empty($ex->dev_model) ? $ex->dev_model : ($ex->commodity_name ?: ($ex->exhibit_category ?: ''));
                    $qty = !empty($ex->seizure_quantity) ? trim((string)$ex->seizure_quantity) : '';
                    if (!empty($qty) && !empty($devName)) {
                        if (preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName)) {
                            $itemStr = $devName;
                        } else {
                            $itemStr = $qty . ' ' . $devName;
                        }
                    } else {
                        $itemStr = $devName ?: $qty;
                    }
                    $itemStr = trim($itemStr);
                    if (!empty($itemStr)) {
                        $exList[] = $itemStr;
                    }
                }
            }
            if (empty($exList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
                foreach ($dData['seizures'] as $sz) {
                    $devName = !empty($sz['dev_model']) ? $sz['dev_model'] : ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? '')));
                    $qty = !empty($sz['seizure_quantity']) ? trim((string)$sz['seizure_quantity']) : '';
                    if (!empty($qty) && !empty($devName)) {
                        if (preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName)) {
                            $itemStr = $devName;
                        } else {
                            $itemStr = $qty . ' ' . $devName;
                        }
                    } else {
                        $itemStr = $devName ?: $qty;
                    }
                    $itemStr = trim($itemStr);
                    if (!empty($itemStr)) $exList[] = $itemStr;
                }
            }
            if (empty($exList)) {
                $mainName = $case->dev_model ?? ($case->seizure_name ?? ($case->seizure_name_drug ?? ($case->exhibit_category ?? null)));
                if (!empty($mainName)) {
                    $qty = !empty($case->seizure_quantity) ? trim((string)$case->seizure_quantity) : '';
                    $exList[] = !empty($qty) && !preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $mainName) ? $qty . ' ' . $mainName : $mainName;
                }
            }
            $devicesStr = !empty($exList) ? implode(', ', array_unique($exList)) : 'Electronic Devices / Mobile Exhibits';

            $narrative = method_exists($case, 'generateBriefCaseDescription') ? $case->generateBriefCaseDescription() : null;
            if (empty($narrative)) {
                $suspectOp = $case->operation_code_name 
                    ?? ($dData['operation_code_name'] 
                    ?? ($dData['suspects'][0]['operation_code_name'] 
                    ?? ($case->operation_code ?? 'intelligence-led')));
                $suspectOpType = !str_contains(strtolower($suspectOp), 'operation') ? strtolower($suspectOp) . ' operation' : strtolower($suspectOp);
                $art = preg_match('/^[aeiou]/i', $suspectOpType) ? 'an' : 'a';
                $sName = $case->suspect_name ?? ($dData['suspects'][0]['suspect_name'] ?? 'The suspect');

                $cFlags = method_exists($case, 'classifyExhibits') ? $case->classifyExhibits() : [];
                $cHasDrug = !empty($cFlags['has_drug']) || (method_exists($case, 'isDrugCase') && $case->isDrugCase());
                $cHasElectronic = !empty($cFlags['has_electronic']) || (method_exists($case, 'isDigitalCase') && $case->isDigitalCase()) || !empty($case->phones);

                if ($cHasElectronic && !$cHasDrug) {
                    $narrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at {$loc} to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$sName} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to NACOC Headquarters for digital forensic examination and further investigation.";
                } elseif ($cHasElectronic && $cHasDrug) {
                    $narrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at {$loc} to curb the trafficking of narcotic drugs and seize electronic devices used in illicit activities within the area. During the operation, {$sName} was arrested in possession of narcotic substances and electronic exhibits, and subsequently conveyed to NACOC Headquarters for chemical laboratory analysis and digital forensic investigation.";
                } else {
                    $narrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at {$loc} to curb the use and sale of narcotic drugs within the area. During the operation, {$sName} was arrested and subsequently conveyed to NACOC Headquarters for further investigation.";
                }
            }

            $report = new \App\Models\DpfReport([
                'dpf_code' => 'DF-STATUS-' . date('Ymd') . '-' . substr(md5($cNo), 0, 4),
                'case_number' => $cNo,
                'laboratory_number' => $case->laboratory_number ?? null,
                'single_suspect_case_id' => ($case instanceof \App\Models\SingleSuspectCase || !isset($case->suspects_list)) ? ($case->id ?? null) : null,
                'multiple_suspect_case_id' => ($case instanceof \App\Models\MultipleSuspectCase || isset($case->suspects_list)) ? ($case->id ?? null) : null,
                'location_post' => $loc,
                'investigation_date' => $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d'),
                'devices_retrieved' => $devicesStr,
                'quantity' => count($exList) > 0 ? (string)count($exList) : '1',
                'brief_case_description' => $narrative,
                'court_order_requested' => 'NO',
                'court_order_granted' => 'NO',
                'extraction_analysis_conducted' => 'NO',
                'extraction_analysis_message' => 'NO',
                'report_witness_statement_sent' => 'NO',
                'report_witness_statement_message' => 'NO',
                'intel_shared' => 'NO',
                'remark' => null,
                'created_at' => $case->created_at ?? now(),
                'report_data' => [
                    'case_section_label' => 'CURRENT/NEW CASES',
                ],
            ]);
        }

        // 5. Only if NO id was requested at all and no report exists, pick latest report as default
        if (empty($id) && !$report) {
            $report = \App\Models\DpfReport::latest('id')->first();
            if ($report) {
                $case = \App\Models\ForensicCase::allCases()->first(function($c) use ($report) {
                    return strtolower(trim($c->case_number ?? '')) === strtolower(trim($report->case_number ?? ''));
                });
            }
        }

        // 6. Absolute fallback if system has zero cases and zero reports
        if (!$report) {
            $fallbackCaseNo = $id ?: 'CSS-2026-6730';
            $report = new \App\Models\DpfReport([
                'dpf_code' => 'DF-STATUS-' . date('Ymd') . '-001',
                'case_number' => $fallbackCaseNo,
                'location_post' => 'Kotoka International Airport (Cargo Wing)',
                'investigation_date' => date('Y-m-d'),
                'devices_retrieved' => 'Mobile Devices & Hardware Exhibits',
                'quantity' => '1',
                'brief_case_description' => 'Target intercepted with encrypted mobile hardware. Write-blocked forensic imaging executed under judicial authorization.',
                'court_order_requested' => 'NO',
                'court_order_granted' => 'NO',
                'extraction_analysis_conducted' => 'NO',
                'extraction_analysis_message' => 'NO',
                'report_witness_statement_sent' => 'NO',
                'report_witness_statement_message' => 'NO',
                'intel_shared' => 'NO',
                'remark' => null,
                'created_at' => now(),
                'report_data' => [
                    'case_section_label' => 'CURRENT/NEW CASES',
                ],
            ]);
        }

        if (!$case && $report) {
            $case = \App\Models\ForensicCase::allCases()->first(function($c) use ($report) {
                return strtolower(trim($c->case_number ?? '')) === strtolower(trim($report->case_number ?? ''));
            });
        }

        $isMultiSuspect = false;
        if (request()->is('*multi*')) {
            $isMultiSuspect = true;
        }

        if ($isMultiSuspect && empty($suspectReports)) {
            $suspectReports = [];
            $dData = is_array($case->docket_data ?? null) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $cSuspects = [];
            if (!empty($dData['suspects']) && is_array($dData['suspects'])) {
                $cSuspects = $dData['suspects'];
            } elseif (isset($case) && method_exists($case, 'getSuspectsList')) {
                $cSuspects = $case->getSuspectsList();
            } elseif (!empty($case->suspects_list) && is_array($case->suspects_list)) {
                $cSuspects = $case->suspects_list;
            } elseif (isset($case->suspects) && count($case->suspects) > 0) {
                $cSuspects = $case->suspects->toArray();
            } elseif (isset($case->underlyingRecord) && isset($case->underlyingRecord->suspects) && count($case->underlyingRecord->suspects) > 0) {
                $cSuspects = $case->underlyingRecord->suspects->toArray();
            }
            if (empty($cSuspects)) {
                $cSuspects[] = [
                    'suspect_name'  => $case->suspect_name ?? 'SUSPECT',
                    'role'          => 'PRIMARY SUSPECT',
                    'mugshot_front' => $case->mugshot_front ?? null,
                    'nationality'   => $case->nationality ?? 'Ghanaian',
                    'gender'        => $case->gender ?? null,
                    'age'           => $case->age ?? null,
                    'ghana_card'    => $case->ghana_card ?? null,
                ];
            }
            foreach ($cSuspects as $idx => $sObj) {
                $suspectReports[] = $buildSuspectReportItem($case, $report, $sObj, $idx, $cleanStatusDisplay);
            }

            // Always ensure CURRENT/NEW CASES come first before ONGOING CASES
            if (!empty($suspectReports)) {
                $indexed = [];
                foreach ($suspectReports as $i => $sr) {
                    $indexed[] = ['idx' => $i, 'item' => $sr];
                }
                usort($indexed, function($a, $b) {
                    $aIsOngoing = (strtoupper($a['item']['court_granted'] ?? 'NO') === 'YES' || (isset($a['item']['case_section_label']) && str_contains(strtoupper($a['item']['case_section_label']), 'ONGOING')));
                    $bIsOngoing = (strtoupper($b['item']['court_granted'] ?? 'NO') === 'YES' || (isset($b['item']['case_section_label']) && str_contains(strtoupper($b['item']['case_section_label']), 'ONGOING')));

                    $aRank = $aIsOngoing ? 1 : 0;
                    $bRank = $bIsOngoing ? 1 : 0;

                    if ($aRank !== $bRank) {
                        return $aRank <=> $bRank;
                    }
                    return $a['idx'] <=> $b['idx'];
                });
                $suspectReports = array_values(array_column($indexed, 'item'));
            }
        }

        $viewName = $isMultiSuspect ? 'Digital_Forensics.multi_dpf_pdf' : 'Digital_Forensics.dpf_pdf';
        return view($viewName, compact('report', 'case', 'suspectReports'));
    })->name('digital.dpf.pdf');

    Route::get('/Digital_Forensics/multi_dpf_pdf/{id?}', function ($id = null) {
        return app()->call(Route::getRoutes()->getByName('digital.dpf.pdf')->getAction('uses'), ['id' => $id]);
    })->name('digital.multi.dpf.pdf.view');

    Route::get('/digital-forensics/multi_dpf_pdf/{id?}', function ($id = null) {
        return app()->call(Route::getRoutes()->getByName('digital.dpf.pdf')->getAction('uses'), ['id' => $id]);
    });

    Route::get('/digital-forensics/multi-dpf-report-pdf/{id?}', function ($id = null) {
        return app()->call(Route::getRoutes()->getByName('digital.dpf.pdf')->getAction('uses'), ['id' => $id]);
    });

    Route::get('/Digital_Forensics/multi-dpf-report-pdf/{id?}', function ($id = null) {
        // Alias route that forces the multi_dpf_pdf view
        return app()->call(Route::getRoutes()->getByName('digital.dpf.pdf')->getAction('uses'), ['id' => $id]);
    })->name('digital.multi.dpf.pdf');

    Route::get('/digital-forensics/dpf-report-pdf/{id?}', function ($id = null) {
        return redirect()->to('/Digital_Forensics/dpf-report-pdf/' . ($id ?? ''));
    });

    Route::get('/Digital_Forensics/dpf_pdf/{id?}', function ($id = null) {
        return redirect()->to('/Digital_Forensics/dpf-report-pdf/' . ($id ?? ''));
    });

    Route::get('/digital-forensics/dpf_pdf/{id?}', function ($id = null) {
        return redirect()->to('/Digital_Forensics/dpf-report-pdf/' . ($id ?? ''));
    });

    Route::post('/Digital_Forensics/forensic-status', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'case_number' => 'required|string',
            'location_post' => 'nullable|string',
            'investigation_date' => 'nullable|string',
            'devices_retrieved' => 'nullable|string',
            'quantity' => 'nullable|string',
            'brief_case_description' => 'nullable|string',
            'court_order_requested' => 'nullable|string',
            'court_order_granted' => 'nullable|string',
            'extraction_analysis_conducted' => 'nullable|string',
            'extraction_analysis_status' => 'nullable|string',
            'extraction_analysis_notes' => 'nullable|string',
            'extraction_analysis_message' => 'nullable|string',
            'ongoing_extraction_analysis_status' => 'nullable|string',
            'ongoing_extraction_analysis_notes' => 'nullable|string',
            'report_witness_statement_sent' => 'nullable|string',
            'report_witness_statement_status' => 'nullable|string',
            'report_witness_statement_notes' => 'nullable|string',
            'report_witness_statement_message' => 'nullable|string',
            'intel_shared' => 'nullable|string',
            'remark' => 'nullable|string',
            'case_section_label' => 'nullable|string',
            'court_order_files' => 'nullable',
            'court_order_files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'court_order_file' => 'nullable',
            'court_order_file.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'report_witness_statement_files' => 'nullable',
            'report_witness_statement_files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'report_witness_statement_file' => 'nullable',
            'report_witness_statement_file.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $cNo = $validated['case_number'];
        $singleCase = \App\Models\SingleSuspectCase::where('case_number', $cNo)->first();
        $multiCase = \App\Models\MultipleSuspectCase::where('case_number', $cNo)->first();

        $isTesting = app()->environment('testing');
        $destinationPath = public_path('uploads/forensic_documents');
        if (!$isTesting && !file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $courtOrderFilePaths = [];
        $coFiles = $request->file('court_order_files') ?? $request->file('court_order_file');
        if ($coFiles) {
            if (!is_array($coFiles)) {
                $coFiles = [$coFiles];
            }
            foreach ($coFiles as $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . uniqid() . '_court_order_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    if (!$isTesting) {
                        $file->move($destinationPath, $filename);
                    }
                    $courtOrderFilePaths[] = 'uploads/forensic_documents/' . $filename;
                }
            }
        }

        $reportWitnessFilePaths = [];
        $rwFiles = $request->file('report_witness_statement_files') ?? $request->file('report_witness_statement_file');
        if ($rwFiles) {
            if (!is_array($rwFiles)) {
                $rwFiles = [$rwFiles];
            }
            foreach ($rwFiles as $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . uniqid() . '_report_witness_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    if (!$isTesting) {
                        $file->move($destinationPath, $filename);
                    }
                    $reportWitnessFilePaths[] = 'uploads/forensic_documents/' . $filename;
                }
            }
        }

        $courtOrderReqInput = strtoupper(trim($request->input('court_order_requested') ?? ''));
        $courtOrderGrantedInput = strtoupper(trim($request->input('court_order_granted') ?? 'NO'));
        if ($courtOrderReqInput === 'NO') {
            $courtOrderGrantedInput = 'NO';
        }
        $isCourtOrderYes = ($courtOrderGrantedInput === 'YES');
        $curExtStatus = trim($request->input('extraction_analysis_status') ?? '');
        $curExtNotes = trim($request->input('extraction_analysis_notes') ?? '');
        $ongExtStatus = trim($request->input('ongoing_extraction_analysis_status') ?? '');
        $ongExtNotes = trim($request->input('ongoing_extraction_analysis_notes') ?? '');

        if ($isCourtOrderYes && !empty($ongExtStatus)) {
            $extStatus = $ongExtStatus;
            $extNotes = $ongExtNotes;
        } else {
            $extStatus = !empty($curExtStatus) ? $curExtStatus : ($ongExtStatus ?: '');
            $extNotes = !empty($curExtNotes) ? $curExtNotes : $ongExtNotes;
        }

        if (empty($extStatus)) {
            $candidate = trim($request->input('extraction_analysis_conducted') ?? '');
            if (in_array(strtoupper($candidate), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                $extStatus = strtoupper($candidate);
            }
        }

        if (empty($extNotes) && !empty($request->input('extraction_analysis_conducted'))) {
            $rawExt = trim($request->input('extraction_analysis_conducted'));
            if (!in_array(strtoupper($rawExt), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                $extNotes = $rawExt;
            }
        }

        if (!empty($extNotes)) {
            if (!empty($extStatus)) {
                $cleanExtNotes = preg_replace('/^' . preg_quote($extStatus, '/') . '\s*[-–—:]\s*/i', '', $extNotes);
                $cleanExtNotes = trim($cleanExtNotes);
            } else {
                $cleanExtNotes = $extNotes;
            }

            if ($cleanExtNotes === '' || (!empty($extStatus) && strtoupper($cleanExtNotes) === strtoupper($extStatus)) || in_array(strtoupper($cleanExtNotes), ['YES', 'NO', 'IN PROGRESS', 'PENDING', 'N/A', 'NONE', 'NIL'])) {
                if (empty($extStatus) && in_array(strtoupper($cleanExtNotes), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                    $extStatus = strtoupper($cleanExtNotes);
                }
                $extNotes = '';
            } else {
                $extNotes = $cleanExtNotes;
            }
        }

        if (!empty($extStatus) && !empty($extNotes)) {
            $extractionCombined = $extStatus . ' - ' . $extNotes;
        } elseif (!empty($extNotes)) {
            $extractionCombined = $extNotes;
        } elseif (!empty($extStatus)) {
            $extractionCombined = $extStatus;
        } else {
            $extractionCombined = 'NO';
        }

        $rwStatus = trim($request->input('report_witness_statement_status') ?? '');
        if (empty($rwStatus)) {
            $candidateRw = trim($request->input('report_witness_statement_sent') ?? '');
            if (in_array(strtoupper($candidateRw), ['YES', 'NO', 'PENDING'])) {
                $rwStatus = strtoupper($candidateRw);
            }
        }

        $rwNotes = trim($request->input('report_witness_statement_notes') ?? $request->input('report_witness_statement_message') ?? '');
        if (empty($rwNotes) && !empty($request->input('report_witness_statement_sent'))) {
            $rawRw = trim($request->input('report_witness_statement_sent'));
            if (!in_array(strtoupper($rawRw), ['YES', 'NO', 'PENDING'])) {
                $rwNotes = $rawRw;
            }
        }

        if (!empty($rwNotes)) {
            if (!empty($rwStatus)) {
                $cleanRwNotes = preg_replace('/^' . preg_quote($rwStatus, '/') . '\s*[-–—:]\s*/i', '', $rwNotes);
                $cleanRwNotes = trim($cleanRwNotes);
            } else {
                $cleanRwNotes = $rwNotes;
            }

            if ($cleanRwNotes === '' || (!empty($rwStatus) && strtoupper($cleanRwNotes) === strtoupper($rwStatus)) || in_array(strtoupper($cleanRwNotes), ['YES', 'NO', 'PENDING', 'IN PROGRESS', 'N/A', 'NONE', 'NIL'])) {
                if (empty($rwStatus) && in_array(strtoupper($cleanRwNotes), ['YES', 'NO', 'PENDING', 'IN PROGRESS'])) {
                    $rwStatus = strtoupper($cleanRwNotes);
                }
                $rwNotes = '';
            } else {
                $rwNotes = $cleanRwNotes;
            }
        }

        if (!empty($rwStatus) && !empty($rwNotes)) {
            $rwCombined = $rwStatus . ' - ' . $rwNotes;
        } elseif (!empty($rwNotes)) {
            $rwCombined = $rwNotes;
        } elseif (!empty($rwStatus)) {
            $rwCombined = $rwStatus;
        } else {
            $rwCombined = 'NO';
        }

        $caseSectionLabel = $request->input('case_section_label')
            ?: (($validated['court_order_granted'] ?? 'NO') === 'YES'
                ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)'
                : 'CURRENT/NEW CASES');

        $existingReport = \App\Models\DpfReport::where('case_number', $cNo)->latest('id')->first();
        if ($existingReport) {
            $existingCoFiles = $existingReport->court_order_files;
            if (empty($courtOrderFilePaths) && !empty($existingCoFiles)) {
                $courtOrderFilePaths = $existingCoFiles;
            } elseif (!empty($courtOrderFilePaths) && !empty($existingCoFiles)) {
                $courtOrderFilePaths = array_values(array_unique(array_merge($existingCoFiles, $courtOrderFilePaths)));
            }

            $existingRwFiles = $existingReport->report_witness_statement_files;
            if (empty($reportWitnessFilePaths) && !empty($existingRwFiles)) {
                $reportWitnessFilePaths = $existingRwFiles;
            } elseif (!empty($reportWitnessFilePaths) && !empty($existingRwFiles)) {
                $reportWitnessFilePaths = array_values(array_unique(array_merge($existingRwFiles, $reportWitnessFilePaths)));
            }
        }

        $dpfCode = ($existingReport && !empty($existingReport->dpf_code))
            ? $existingReport->dpf_code
            : ('DF-STATUS-' . date('Y-md') . '-' . rand(100, 999));

        $reportAttributes = [
            'dpf_code' => $dpfCode,
            'case_number' => $cNo,
            'laboratory_number' => $singleCase->laboratory_number ?? ($multiCase->laboratory_number ?? ($existingReport->laboratory_number ?? null)),
            'single_suspect_case_id' => $singleCase ? $singleCase->id : ($existingReport->single_suspect_case_id ?? null),
            'multiple_suspect_case_id' => $multiCase ? $multiCase->id : ($existingReport->multiple_suspect_case_id ?? null),
            'user_id' => auth()->id() ?? ($existingReport->user_id ?? null),
            'location_post' => $validated['location_post'] ?? ($existingReport->location_post ?? null),
            'investigation_date' => $validated['investigation_date'] ?? ($existingReport->investigation_date ?? date('Y-m-d')),
            'devices_retrieved' => $validated['devices_retrieved'] ?? ($existingReport->devices_retrieved ?? null),
            'quantity' => $validated['quantity'] ?? ($existingReport->quantity ?? '1'),
            'brief_case_description' => $validated['brief_case_description'] ?? ($existingReport->brief_case_description ?? null),
            'court_order_requested' => $finalCourtReq = ($validated['court_order_requested'] ?? ($existingReport->court_order_requested ?? 'NO')),
            'court_order_granted' => (strtoupper(trim($finalCourtReq)) === 'NO') ? 'NO' : ($validated['court_order_granted'] ?? ($existingReport->court_order_granted ?? 'NO')),
            'court_order_file' => !empty($courtOrderFilePaths) ? json_encode($courtOrderFilePaths) : ($existingReport->court_order_file ?? null),
            'extraction_analysis_conducted' => $extractionCombined,
            'extraction_analysis_message' => $extractionCombined,
            'report_witness_statement_sent' => $rwCombined,
            'report_witness_statement_message' => $rwCombined,
            'report_witness_statement_file' => !empty($reportWitnessFilePaths) ? json_encode($reportWitnessFilePaths) : ($existingReport->report_witness_statement_file ?? null),
            'intel_shared' => $validated['intel_shared'] ?? ($existingReport->intel_shared ?? 'NO'),
            'remark' => !empty(trim((string)($validated['remark'] ?? ''))) ? trim((string)$validated['remark']) : null,
            'report_data' => [
                'case_section_label' => $caseSectionLabel,
                'court_order_files' => $courtOrderFilePaths,
                'report_witness_statement_files' => $reportWitnessFilePaths,
                'extraction_analysis_status' => !empty($curExtStatus) ? $curExtStatus : (!$isCourtOrderYes ? $extStatus : ($existingReport->report_data['extraction_analysis_status'] ?? '')),
                'extraction_analysis_notes' => !empty($curExtNotes) ? $curExtNotes : (!$isCourtOrderYes ? $extNotes : ($existingReport->report_data['extraction_analysis_notes'] ?? '')),
                'extraction_analysis_message' => $extractionCombined,
                'ongoing_extraction_analysis_status' => !empty($ongExtStatus) ? $ongExtStatus : ($isCourtOrderYes ? $extStatus : ($existingReport->report_data['ongoing_extraction_analysis_status'] ?? '')),
                'ongoing_extraction_analysis_notes' => !empty($ongExtNotes) ? $ongExtNotes : ($isCourtOrderYes ? $extNotes : ($existingReport->report_data['ongoing_extraction_analysis_notes'] ?? '')),
                'report_witness_statement_status' => $rwStatus,
                'report_witness_statement_notes' => $rwNotes,
                'report_witness_statement_message' => $rwCombined,
            ],
            'status' => 'active',
        ];

        if ($existingReport) {
            $existingReport->update($reportAttributes);
            $report = $existingReport->fresh();
            // Consolidate legacy duplicate records so only one canonical report exists per case
            \App\Models\DpfReport::where('case_number', $cNo)->where('id', '!=', $report->id)->delete();
        } else {
            $report = \App\Models\DpfReport::create($reportAttributes);
        }

        $reportData = $report->toArray();
        $reportData['case_section_label'] = $caseSectionLabel;
        $reportData['court_order_files'] = $courtOrderFilePaths;
        $reportData['report_witness_statement_files'] = $reportWitnessFilePaths;
        $reportData['extraction_analysis_conducted'] = $extractionCombined;
        $reportData['extraction_analysis_message'] = $extractionCombined;
        $reportData['report_witness_statement_sent'] = $rwCombined;
        $reportData['report_witness_statement_message'] = $rwCombined;

        return response()->json([
            'success' => true,
            'message' => 'Forensic & Investigation Status updated and logged successfully!',
            'data'    => $report,
            'report'  => $reportData,
            'court_order_files' => $courtOrderFilePaths,
            'report_witness_statement_files' => $reportWitnessFilePaths,
        ]);
    });

    Route::post('/digital-forensics/forensic-status', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/Digital_Forensics/forensic-status');
    });

    Route::get('/Digital_Forensics/create', function () {
        return view('Digital_Forensics.create');
    })->name('digital_forensics.create');

    Route::get('/Digital_Forensics/head', function () {
        return view('Digital_Forensics.digital_forensics');
    });

    Route::get('/Digital_Forensics/staff', function () {
        return view('Digital_Forensics.staff.staff');
    });

    Route::get('/digital-forensics', function () {
        return view('Digital_Forensics.digital_forensics');
    });

    /*
    |--------------------------------------------------------------------------
    | 3. COMMAND & ADMINISTRATIVE COMMAND ROUTES (AUTH + HEAD AUTHORIZATION)
    |--------------------------------------------------------------------------
    */
    Route::middleware([EnsureHeadOfficer::class])->group(function () {

        Route::get('/Forensic_Head', function () {
            $cases = ForensicCase::allCases();
            $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
            $pendingCount = User::where('is_approved', false)->count();
            return view('Forensic_Head.head', compact('cases', 'pendingCasesCount', 'pendingCount'));
        });

        Route::get('/Forensic_Head/cases', function () {
            $cases = ForensicCase::allCases();
            $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
            $pendingCount = User::where('is_approved', false)->count();
            return view('Forensic_Head.cases', compact('cases', 'pendingCasesCount', 'pendingCount'));
        });

        Route::get('/Forensic_Head/cases/{id}/review', [CaseController::class, 'showReview']);
        Route::get('/Forensic_Head/cases/{id}/pdf', [CaseController::class, 'showPdf']);

        Route::get('/api/head-stats', function () {
            $allC = ForensicCase::allCases();
            $pendingCasesCount = $allC->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
            $pendingUsersCount = User::where('is_approved', false)->count();
            $activeUsersCount = User::where('is_approved', true)->count();
            $totalUsersCount = User::count();

            return response()->json([
                'pendingCasesCount' => $pendingCasesCount,
                'pendingUsersCount' => $pendingUsersCount,
                'activeUsersCount' => $activeUsersCount,
                'totalUsersCount' => $totalUsersCount,
                'totalAlerts' => $pendingCasesCount + $pendingUsersCount + 1,
            ]);
        });

        Route::post('/cases/batch-approve', [CaseController::class, 'batchApprove']);
        Route::post('/cases/{id}/approve', [CaseController::class, 'approve']);
        Route::post('/cases/{id}/decline', [CaseController::class, 'decline']);

        Route::get('/Forensic_Head/approvals', function (\Illuminate\Http\Request $request) {
            $users = User::orderBy('created_at', 'desc')->get();

            $pendingCount = User::where('is_approved', false)->count();
            $activeCount = User::where('is_approved', true)->count();
            $headsCount = User::where('is_head', true)->count();
            $totalCount = User::count();

            $allC = ForensicCase::allCases();
            $pendingCasesCount = $allC->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();

            if ($request->wantsJson()) {
                return response()->json(compact('users', 'pendingCount', 'activeCount', 'headsCount', 'totalCount', 'pendingCasesCount'));
            }

            return view('Forensic_Head.approvals', compact(
                'users',
                'pendingCount',
                'activeCount',
                'headsCount',
                'totalCount',
                'pendingCasesCount'
            ));
        });

        Route::post('/Forensic_Head/users/{id}/approve', [AuthController::class, 'approveUser']);
        Route::post('/Forensic_Head/users/{id}/deactivate', [AuthController::class, 'deactivateUser']);
    });
});
