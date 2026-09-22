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
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
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

    // MASTER CASE DIRECTORY & CASES API
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
    Route::post('/cases/{id}/reassign-exhibit', [CaseController::class, 'reassignExhibit']);

    // GENERAL DOCKET & EVENT ROUTES
    Route::get('/arrest-person', function () {
        return view('Crime_Scene_Support.staff.events.arrest_person');
    })->name('arrest_person');

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
        return view('Crime_Scene_Support.staff.events.arrest_person');
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
    Route::get('/lab-result', function () {
        $cases = ForensicCase::allCases();
        return view('cases.landing', compact('cases'));
    })->name('lab.result');

    Route::get('/Lab_Analysis/lab-result', function () {
        $cases = ForensicCase::allCases();
        return view('cases.landing', compact('cases'));
    });

    Route::get('/Lab_Analysis/examination', function () {
        return view('Lab_Analysis.examination');
    });

    Route::get('/lab-analysis/add-case', function () {
        return view('Lab_Analysis.examination');
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