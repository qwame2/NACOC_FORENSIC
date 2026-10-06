<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
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
