<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseWorkflowController;
use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\CaseReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DrugScreeningController;
use App\Http\Controllers\EvidenceManagementController;
use App\Http\Controllers\LabAnalysisCaseController;
use App\Http\Controllers\DigitalForensicsCaseController;
use App\Http\Controllers\EvidenceManagementCaseController;
use App\Http\Controllers\CrimeSceneSupportCaseController;
use App\Http\Controllers\ForensicHeadCaseController;
use App\Http\Controllers\CourtEvidenceController;
use App\Http\Controllers\PersonnelRosterController;
use App\Http\Controllers\FieldOfficersController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\UnitPermissionsController;
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

// Permanent Live Exhibit Information & Tamper-Evident Verification Tag Route
Route::get('/exhibits/tag/{token}', [EvidenceManagementController::class, 'showLiveTag'])->name('exhibits.live-tag');
Route::get('/evidence/tag/{token}', [EvidenceManagementController::class, 'showLiveTag']);

Route::get('/api/check-user-approval', function (\Illuminate\Http\Request $request) {
    $loginId = trim($request->query('login_id', ''));
    $since = (int) $request->query('since', 0);

    if (!$loginId) {
        if ($since > 0) {
            $recentApproval = \Illuminate\Support\Facades\Cache::get('nacoc_latest_staff_approval');
            if (is_array($recentApproval) && (int) ($recentApproval['approved_at'] ?? 0) >= $since) {
                return response()->json($recentApproval);
            }
        }
        return response()->json(['exists' => false]);
    }

    $lowerLoginId = strtolower($loginId);
    $user = User::whereRaw('LOWER(service_id) = ?', [$lowerLoginId])
        ->orWhereRaw('LOWER(username) = ?', [$lowerLoginId])
        ->orWhereRaw('LOWER(COALESCE(email, "")) = ?', [$lowerLoginId])
        ->first();

    if (!$user) {
        $user = \App\Models\StaffAccount::whereRaw('LOWER(service_id) = ?', [$lowerLoginId])
            ->orWhereRaw('LOWER(username) = ?', [$lowerLoginId])
            ->orWhereRaw('LOWER(COALESCE(email, "")) = ?', [$lowerLoginId])
            ->first();
    }

    if (!$user) {
        return response()->json(['exists' => false]);
    }

    return response()->json([
        'exists' => true,
        'is_approved' => (bool) $user->is_approved,
        'is_head' => (bool) $user->is_head,
        'is_staff' => (bool) $user->is_staff,
        'unit' => $user->unit,
        'fullname' => $user->fullname,
        'service_id' => $user->service_id,
        'username' => $user->username,
    ]);
});
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout']);
Route::get('/api/dashboard/realtime', [CaseReportController::class, 'dashboardRealtime']);
Route::get('/dashboard', function () {
    $cases = ForensicCase::allCases();
    $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
    $pendingCount = User::where('is_approved', false)->count();
    $mapConfig = app(\App\Services\CaseAnalyticsService::class)->buildLeafletMapPayload($cases);
    return view('Forensic_Head.head', compact('cases', 'pendingCasesCount', 'pendingCount', 'mapConfig'));
});
Route::get('/finnova-dashboard', function () {
    $cases = ForensicCase::allCases();
    $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
    $pendingCount = User::where('is_approved', false)->count();
    $mapConfig = app(\App\Services\CaseAnalyticsService::class)->buildLeafletMapPayload($cases);
    return view('Forensic_Head.head', compact('cases', 'pendingCasesCount', 'pendingCount', 'mapConfig'));
});


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

    // FIELD OFFICERS & UNIT-HEAD STAFF APPROVAL WORKFLOW
    Route::get('/field-officers', [FieldOfficersController::class, 'index'])->name('field_officers.index');
    Route::get('/Crime_Scene_Support/field-officers', [FieldOfficersController::class, 'index'])->name('crime_scene.field_officers');
    Route::get('/Lab_Analysis/field-officers', [FieldOfficersController::class, 'index'])->name('lab_analysis.field_officers');
    Route::get('/Evidence_Management/field-officers', [FieldOfficersController::class, 'index'])->name('evidence_management.field_officers');
    Route::get('/Digital_Forensics/field-officers', [FieldOfficersController::class, 'index'])->name('digital_forensics.field_officers');
    Route::post('/field-officers/{id}/approve', [FieldOfficersController::class, 'approve'])->name('field_officers.approve');
    Route::post('/field-officers/{id}/revoke', [FieldOfficersController::class, 'revoke'])->name('field_officers.revoke');
    Route::get('/api/auth/account-status', [FieldOfficersController::class, 'accountStatus'])->name('api.auth.account_status');

    // UNIT HEAD STAFF PERMISSIONS MANAGEMENT
    Route::middleware([EnsureHeadOfficer::class])->group(function () {
        Route::get('/permissions', [UnitPermissionsController::class, 'index'])->name('permissions.index');
        Route::get('/Crime_Scene_Support/permissions', [UnitPermissionsController::class, 'index'])->name('crime_scene.permissions');
        Route::get('/Lab_Analysis/permissions', [UnitPermissionsController::class, 'index'])->name('lab_analysis.permissions');
        Route::get('/Evidence_Management/permissions', [UnitPermissionsController::class, 'index'])->name('evidence_management.permissions');
        Route::get('/Digital_Forensics/permissions', [UnitPermissionsController::class, 'index'])->name('digital_forensics.permissions');
        Route::get('/Forensic_Head/permissions', [UnitPermissionsController::class, 'index'])->name('forensic_head.permissions');
        Route::post('/permissions/{id}/update', [UnitPermissionsController::class, 'update'])->name('permissions.update');
    });

    // UNIT HEAD & DIRECTORATE NOTIFICATIONS
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::get('/Crime_Scene_Support/notifications', [NotificationsController::class, 'index'])->name('crime_scene.notifications');
    Route::get('/Lab_Analysis/notifications', [NotificationsController::class, 'index'])->name('lab_analysis.notifications');
    Route::get('/Evidence_Management/notifications', [NotificationsController::class, 'index'])->name('evidence_management.notifications');
    Route::get('/Digital_Forensics/notifications', [NotificationsController::class, 'index'])->name('digital_forensics.notifications');
    Route::get('/Forensic_Head/notifications', [NotificationsController::class, 'index'])->name('forensic_head.notifications');
    Route::post('/notifications/mark-read', [NotificationsController::class, 'markRead'])->name('notifications.mark_read');
    Route::post('/notifications/handovers/{id}/acknowledge', [NotificationsController::class, 'acknowledgeHandover'])->name('notifications.handovers.acknowledge');

    // MASTER CASE DIRECTORY & HISTORY ROUTES
    Route::get('/history', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $allCases = ForensicCase::allCases();
        $unitFilter = $request->query('unit') ?? $request->query('from') ?? ($user ? $user->unit : 'All Units');

        $search = trim($request->input('search', ''));
        $status = trim($request->input('status', ''));

        $filtered = ($unitFilter && $unitFilter !== 'All Units')
            ? $allCases->filter(fn($c) => strtolower($c->unit_name) === strtolower($unitFilter))->values()
            : $allCases;

        if ($search !== '') {
            $sLower = strtolower($search);
            $filtered = $filtered->filter(function ($c) use ($sLower) {
                $suspectNames = '';
                if (!empty($c->docket_data['suspects']) && is_array($c->docket_data['suspects'])) {
                    $suspectNames = collect($c->docket_data['suspects'])->pluck('suspect_name')->filter()->implode(' ');
                }
                return str_contains(strtolower($c->case_number ?? ''), $sLower) ||
                       str_contains(strtolower($c->title ?? ''), $sLower) ||
                       str_contains(strtolower($c->suspect_name ?? ''), $sLower) ||
                       str_contains(strtolower($suspectNames), $sLower) ||
                       str_contains(strtolower($c->submitting_officer ?? ''), $sLower) ||
                       str_contains(strtolower($c->incident_scene ?? ''), $sLower) ||
                       str_contains(strtolower($c->exhibits_summary ?? ''), $sLower);
            })->values();
        }

        if ($status !== '' && strtoupper($status) !== 'ALL') {
            $filtered = $filtered->filter(function ($c) use ($status) {
                return strcasecmp($c->status ?? '', $status) === 0;
            })->values();
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;
        $cases = ForensicCase::paginateCases($filtered, $perPage);

        if ($request->ajax()) {
            return view('partials.history_table', compact('cases', 'unitFilter', 'allCases'));
        }

        return view('history', compact('cases', 'unitFilter', 'allCases'));
    })->name('history');

    Route::get('/History', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?' . http_build_query($request->all()));
    });

    Route::get('/Crime_Scene_Support/history', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?unit=Crime+Scene+Support&' . http_build_query($request->except('unit')));
    });

    Route::get('/Lab_Analysis/history', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?unit=Lab+Analysis&' . http_build_query($request->except('unit')));
    });

    Route::get('/Evidence_Management/history', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?unit=Evidence+Management&' . http_build_query($request->except('unit')));
    });

    Route::get('/Digital_Forensics/history', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?unit=Digital+%26+Physical+Forensics&' . http_build_query($request->except('unit')));
    });

    Route::get('/Forensic_Head/history', function (\Illuminate\Http\Request $request) {
        return redirect()->to('/history?unit=All+Units&' . http_build_query($request->except('unit')));
    });

    $handleCasesLanding = function (\Illuminate\Http\Request $request) {
        $allCases = ForensicCase::allCases();

        $search = trim($request->input('search', ''));
        $status = trim($request->input('status', ''));
        $unit = trim($request->input('unit', ''));

        $filtered = $allCases;

        if ($search !== '') {
            $sLower = strtolower($search);
            $filtered = $filtered->filter(function ($c) use ($sLower) {
                $suspectNames = '';
                if (!empty($c->docket_data['suspects']) && is_array($c->docket_data['suspects'])) {
                    $suspectNames = collect($c->docket_data['suspects'])->pluck('suspect_name')->filter()->implode(' ');
                }
                return str_contains(strtolower($c->case_number ?? ''), $sLower) ||
                       str_contains(strtolower($c->title ?? ''), $sLower) ||
                       str_contains(strtolower($c->suspect_name ?? ''), $sLower) ||
                       str_contains(strtolower($suspectNames), $sLower) ||
                       str_contains(strtolower($c->submitting_officer ?? ''), $sLower) ||
                       str_contains(strtolower($c->incident_scene ?? ''), $sLower) ||
                       str_contains(strtolower($c->exhibits_summary ?? ''), $sLower);
            })->values();
        }

        if ($status !== '' && strtoupper($status) !== 'ALL') {
            $filtered = $filtered->filter(function ($c) use ($status) {
                $statusTab = match ($c->status) {
                    'Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized' => 'Court Ready',
                    'Lab Testing', 'Lab Analysis', 'Under Review' => 'Lab Analysis',
                    default => 'Active Investigation',
                };
                return strcasecmp($statusTab, $status) === 0 || strcasecmp($c->status ?? '', $status) === 0;
            })->values();
        }

        if ($unit !== '' && $unit !== 'All Units') {
            $uLower = strtolower($unit);
            $filtered = $filtered->filter(function ($c) use ($uLower) {
                return str_contains(strtolower($c->unit_name ?? ''), $uLower);
            })->values();
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;

        $cases = ForensicCase::paginateCases($filtered, $perPage);

        if ($request->ajax()) {
            return view('cases.partials.table', compact('cases', 'allCases'));
        }

        return view('cases.landing', compact('cases', 'allCases'));
    };

    Route::get('/cases', $handleCasesLanding);
    Route::get('/cases/landing', $handleCasesLanding);
    Route::get('/Crime_Scene_Support/cases', $handleCasesLanding);

    Route::get('/api/cases', [CaseController::class, 'index']);
    Route::post('/api/cases/check-duplicate-suspect', [CaseController::class, 'checkDuplicateSuspect']);
    Route::post('/api/cases/check-duplicate-case', [CaseController::class, 'checkDuplicateCase']);
    Route::get('/cases/{id}/review', [CaseController::class, 'showReview'])->name('cases.review');
    Route::get('/Lab_Analysis/cases/{id}/review', [LabAnalysisCaseController::class, 'showReview'])->name('lab.cases.review');
    Route::get('/Digital_Forensics/cases/{id}/review', [DigitalForensicsCaseController::class, 'showReview'])->name('digital.cases.review');
    Route::get('/Evidence_Management/cases/{id}/review', [EvidenceManagementCaseController::class, 'showReview'])->name('evidence.cases.review');
    Route::get('/Crime_Scene_Support/cases/{id}/review', [CrimeSceneSupportCaseController::class, 'showReview'])->name('crime_scene.cases.review');
    Route::get('/lab-analysis/cases/{id}/review', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('lab.cases.review', array_merge(['id' => $id], $request->query()));
    });
    Route::get('/digital-forensics/cases/{id}/review', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('digital.cases.review', array_merge(['id' => $id], $request->query()));
    });
    Route::get('/evidence-management/cases/{id}/review', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('evidence.cases.review', array_merge(['id' => $id], $request->query()));
    });
    Route::get('/crime-scene-support/cases/{id}/review', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('crime_scene.cases.review', array_merge(['id' => $id], $request->query()));
    });
    Route::get('/forensic-head/cases/{id}/review', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('forensic_head.cases.review', array_merge(['id' => $id], $request->query()));
    });
    Route::get('/cases/{id}/edit', [CaseController::class, 'showEdit'])->name('cases.edit');
    Route::get('/cases/{id}/pdf', [CaseController::class, 'showPdf'])->name('cases.pdf');
    Route::get('/cases/{id}/associate', [CaseController::class, 'showAssociate'])->name('cases.associate');
    Route::get('/Lab_Analysis/cases/{id}/associate', function ($id) {
        return redirect()->route('cases.associate', ['id' => $id, 'from' => 'Lab_Analysis']);
    })->name('lab.cases.associate');
    Route::get('/Digital_Forensics/cases/{id}/associate', function ($id) {
        return redirect()->route('cases.associate', ['id' => $id, 'from' => 'Digital_Forensics']);
    })->name('digital.cases.associate');
    Route::get('/Crime_Scene_Support/cases/{id}/associate', function ($id) {
        return redirect()->route('cases.associate', ['id' => $id, 'from' => 'Crime_Scene_Support']);
    })->name('crime_scene.cases.associate');
    Route::get('/Evidence_Management/cases/{id}/associate', function ($id) {
        return redirect()->route('cases.associate', ['id' => $id, 'from' => 'Evidence_Management']);
    })->name('evidence.cases.associate');
    Route::get('/Lab_Analysis/cases/{id}/edit', function ($id) {
        return redirect()->route('cases.edit', ['id' => $id, 'from' => 'Lab_Analysis']);
    })->name('lab.cases.edit');
    Route::get('/Digital_Forensics/cases/{id}/edit', function ($id) {
        return redirect()->route('cases.edit', ['id' => $id, 'from' => 'Digital_Forensics']);
    })->name('digital.cases.edit');
    Route::get('/Crime_Scene_Support/cases/{id}/edit', function ($id) {
        return redirect()->route('cases.edit', ['id' => $id, 'from' => 'Crime_Scene_Support']);
    })->name('crime_scene.cases.edit');
    Route::get('/Evidence_Management/cases/{id}/edit', function ($id) {
        return redirect()->route('cases.edit', ['id' => $id, 'from' => 'Evidence_Management']);
    })->name('evidence.cases.edit');
    Route::get('/Forensic_Head/cases/{id}/edit', function ($id) {
        return redirect()->route('forensic_head.cases.review', ['id' => $id])->with('error', 'Directorate Head is not permitted to edit cases. Directorate Heads only review and approve cases.');
    })->name('forensic_head.cases.edit');

    // CASE CREATION & UNIT PROGRESS UPDATES
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::post('/cases/{id}/update', [CaseController::class, 'updateCase'])->name('cases.update');
    Route::post('/cases/{id}/update-unit-progress', [CaseWorkflowController::class, 'updateUnitProgress']);
    Route::post('/cases/{id}/associate', [CaseController::class, 'associate']);
    Route::post('/cases/upload-evidence-photos', [CaseDocumentController::class, 'uploadEvidencePhotos']);
    Route::post('/upload-evidence-photos', [CaseDocumentController::class, 'uploadEvidencePhotos']);
    Route::post('/exhibit-tags', [EvidenceManagementController::class, 'storeExhibitTags'])->name('exhibit.tags.store');
    Route::get('/exhibit-property-ids', [EvidenceManagementController::class, 'propertyIds'])->name('exhibit.property_ids');
    Route::post('/cases/{id}/reassign-exhibit', [CaseController::class, 'reassignExhibit']);
    Route::post('/cases/{id}/case-status', [CaseWorkflowController::class, 'updateCaseStatus']);
    Route::post('/cases/{id}/suspect-disposition', [CaseWorkflowController::class, 'updateSuspectDisposition']);
    Route::post('/cases/{id}/handover/initiate', [CaseWorkflowController::class, 'initiateHandover']);
    Route::post('/cases/{id}/handover/respond', [CaseWorkflowController::class, 'respondHandover']);
    Route::post('/cases/{id}/upload-gsa-report', [CaseDocumentController::class, 'storeGsaReports']);
    Route::post('/cases/{id}/upload-search-warrant', [CaseDocumentController::class, 'storeSearchWarrantDocument']);
    Route::post('/Lab_Analysis/gsa-reports/store', [CaseDocumentController::class, 'storeGsaReports']);
    Route::get('/cases/{id}/audit-trail', [CaseWorkflowController::class, 'getAuditTrail']);

    // GENERAL DOCKET & EVENT ROUTES
    Route::get('/cases/create', function (\Illuminate\Http\Request $request) {
        if ($request->query('from') === 'Forensic_Head' || $request->query('from') === 'Forensic Head') {
            return redirect('/Forensic_Head/cases')->with('error', 'Directorate Head is not permitted to create cases. Cases originate from operational units.');
        }
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

    Route::get('/Crime_Scene_Support', function (\Illuminate\Http\Request $request) {
        $from = strtolower($request->query('from', '') . ' ' . $request->query('unit', ''));
        if (str_contains($from, 'lab')) {
            return redirect('/Lab_Analysis');
        } elseif (str_contains($from, 'digital') || str_contains($from, 'physical')) {
            return redirect('/Digital_Forensics');
        } elseif (str_contains($from, 'evidence')) {
            return redirect('/Evidence_Management');
        }
        return view('Crime_Scene_Support.head.head');
    });

    Route::get('/Crime_Scene_Support/head', function (\Illuminate\Http\Request $request) {
        $from = strtolower($request->query('from', '') . ' ' . $request->query('unit', ''));
        if (str_contains($from, 'lab')) {
            return redirect('/Lab_Analysis');
        } elseif (str_contains($from, 'digital') || str_contains($from, 'physical')) {
            return redirect('/Digital_Forensics');
        } elseif (str_contains($from, 'evidence')) {
            return redirect('/Evidence_Management');
        }
        return view('Crime_Scene_Support.head.head');
    });

    Route::get('/Crime_Scene_Support/reviews-approvals', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'index'])->name('crime_scene.reviews_approvals');
    Route::get('/Lab_Analysis/reviews-approvals', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'index'])->name('lab.reviews_approvals');
    Route::get('/Evidence_Management/reviews-approvals', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'index'])->name('evidence.reviews_approvals');
    Route::get('/Digital_Forensics/reviews-approvals', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'index'])->name('digital.reviews_approvals');
    Route::get('/Forensic_Head/reviews-approvals', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'index'])->name('forensic_head.reviews_approvals');
    Route::post('/reviews-approvals/{id}/approve', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'approve'])->name('reviews_approvals.approve');
    Route::post('/reviews-approvals/{id}/decline', [\App\Http\Controllers\StaffCaseSubmissionController::class, 'decline'])->name('reviews_approvals.decline');

    Route::get('/Crime_Scene_Support/staff', function () {
        return view('Crime_Scene_Support.staff.staff');
    });

    Route::get('/Crime_Scene_Support/staff/dashboard', function () {
        return view('Crime_Scene_Support.staff.dashboard');
    })->name('crime_scene.staff.dashboard');

    Route::get('/Crime_Scene_Support/staff/case', function () {
        return view('Crime_Scene_Support.staff.case');
    });

    Route::get('/Crime_Scene_Support/staff/cases', function () {
        return view('Crime_Scene_Support.staff.case');
    });

    Route::get('/Crime_Scene_Support/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('crime_scene.staff.cases.review');

    Route::get('/Crime_Scene_Support/staff/create', function () {
        return view('Crime_Scene_Support.staff.create');
    })->name('crime_scene.staff.create');

    Route::get('/Crime_Scene_Support/staff/report', [CrimeSceneSupportCaseController::class, 'report'])->name('crime_scene.staff.report');

    Route::get('/crime-scene-support', function () {
        return view('Crime_Scene_Support.head.head');
    });

    // CRIME SCENE SUPPORT UNIT DEDICATED REPORT ROUTES
    Route::get('/Crime_Scene_Support/report', [CrimeSceneSupportCaseController::class, 'report'])->name('crime_scene.report');
    Route::get('/crime-scene-support/report', [CrimeSceneSupportCaseController::class, 'report']);
    Route::post('/Crime_Scene_Support/report/notes', [CrimeSceneSupportCaseController::class, 'saveReportNotes'])->name('crime_scene.report_notes.store');
    Route::post('/crime-scene-support/report/notes', [CrimeSceneSupportCaseController::class, 'saveReportNotes']);
    Route::get('/Crime_Scene_Support/report_pdf', [CrimeSceneSupportCaseController::class, 'reportPdf'])->name('crime_scene.report_pdf');
    Route::get('/crime-scene-support/report_pdf', [CrimeSceneSupportCaseController::class, 'reportPdf']);

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

    Route::get('/Lab_Analysis/staff/dashboard', function () {
        return view('Lab_Analysis.staff.dashboard');
    })->name('lab.staff.dashboard');

    Route::get('/Lab_Analysis/staff/case', function () {
        return view('Lab_Analysis.staff.case');
    });

    Route::get('/Lab_Analysis/staff/cases', function () {
        return view('Lab_Analysis.staff.case');
    });

    Route::get('/Lab_Analysis/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('lab.staff.cases.review');

    Route::get('/Lab_Analysis/staff/create', function () {
        return view('Lab_Analysis.staff.create');
    })->name('lab.staff.create');

    Route::get('/Lab_Analysis/staff/report', [CaseReportController::class, 'labReportPage'])->name('lab.staff.report');

    Route::get('/lab-analysis', function () {
        return view('Lab_Analysis.lab_analysis');
    });

    Route::get('/lab-result', [CaseReportController::class, 'labResultPage'])->name('lab.result');
    Route::get('/Lab_Analysis/lab-result', [CaseReportController::class, 'labResultPage']);
    Route::get('/Lab_Analysis/certificate/{id}', [CaseDocumentController::class, 'showLabCertificate'])->name('lab.certificate');
    Route::get('/lab-certificate/{id}', [CaseDocumentController::class, 'showLabCertificate']);
    Route::get('/Lab_Analysis/analytical/{id}', [CaseDocumentController::class, 'showLabAnalytical'])->name('lab.analytical');
    Route::get('/lab-analytical/{id}', [CaseDocumentController::class, 'showLabAnalytical']);
    Route::get('/Lab_Analysis/nlr-report-pdf/{id}', [CaseDocumentController::class, 'showNlrReportPdf'])->name('lab.nlr.pdf');
    Route::get('/lab-nlr-pdf/{id}', [CaseDocumentController::class, 'showNlrReportPdf']);
    Route::post('/Lab_Analysis/nlr-reports/store', [CaseDocumentController::class, 'storeNlrReport'])->name('lab.nlr.store');

    // Dedicated Preliminary Exhibit Analysis Report PDF / Printable View
    Route::get('/Lab_Analysis/preliminary-report-pdf/{id}', [CaseReportController::class, 'preliminaryReportPdf'])->name('lab.preliminary_report.pdf');
    Route::get('/lab-preliminary-report-pdf/{id}', [CaseReportController::class, 'preliminaryReportPdf']);

    // Dedicated Laboratory Analysis Report & Drug Suspect Register Management Page
    Route::get('/Lab_Analysis/report', [CaseReportController::class, 'labReportPage'])->name('lab.report');
    Route::get('/lab-analysis/report', [CaseReportController::class, 'labReportPage']);

    // Generated PDF Situational Report & Dossier
    Route::get('/Lab_Analysis/lab_sitrep_pdf', [CaseReportController::class, 'labSitrepReport'])->name('lab.sitrep.pdf');
    Route::get('/lab-analysis/lab_sitrep_pdf', [CaseReportController::class, 'labSitrepReport']);

    // General / Aside-bar Report Route Handler
    Route::get('/Report', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $unit = $request->query('unit') ?: ($user ? $user->unit : '');
        $unitLower = strtolower($unit);

        if (str_contains($unitLower, 'lab')) {
            return app(\App\Http\Controllers\CaseReportController::class)->labReportPage($request);
        } elseif (str_contains($unitLower, 'digital') || str_contains($unitLower, 'physical')) {
            return redirect()->to('/Digital_Forensics/multi_dpf_pdf');
        } elseif (str_contains($unitLower, 'evidence')) {
            return redirect()->to('/Evidence_Management/report');
        } elseif (str_contains($unitLower, 'crime') || str_contains($unitLower, 'css') || str_contains($unitLower, 'scene') || str_contains($unitLower, 'field')) {
            return redirect()->to('/Crime_Scene_Support/report');
        }

        // Default to Crime Scene Support Report Page
        return redirect()->to('/Crime_Scene_Support/report');
    })->name('general.report');

    Route::get('/Lab_Analysis/examination', function () {
        $case = \App\Models\ForensicCase::find(request('case_id'));
        return view('Lab_Analysis.examination', compact('case'));
    });

    Route::get('/Lab_Analysis/create', function () {
        return view('Lab_Analysis.create');
    })->name('lab_analysis.create');

    // GSA Report Upload — store files linked to a suspect
    Route::post('/Lab_Analysis/gsa-reports/store', [CaseDocumentController::class, 'storeGsaReports'])
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

    // DRUG SCREENING MODULE ROUTES (LAB ANALYSIS)
    Route::get('/Lab_Analysis/drug-screening', [DrugScreeningController::class, 'index'])->name('lab.drug_screening.index');
    Route::get('/Lab_Analysis/drug-screening/create', [DrugScreeningController::class, 'create'])->name('lab.drug_screening.create');
    Route::post('/Lab_Analysis/drug-screening', [DrugScreeningController::class, 'store'])->name('lab.drug_screening.store');
    Route::get('/Lab_Analysis/drug-screening/generate-report', [DrugScreeningController::class, 'generateReportPdf'])->name('lab.drug_screening.generate_report');
    Route::get('/Lab_Analysis/drug-screening/report/pdf', [DrugScreeningController::class, 'generateReportPdf'])->name('lab.drug_screening.report_pdf');
    Route::get('/Lab_Analysis/drug-screening/{id}/pdf', [DrugScreeningController::class, 'showPdf'])->name('lab.drug_screening.pdf');
    Route::get('/Lab_Analysis/drug-screening/civilian-report/{id}', [DrugScreeningController::class, 'showPdf'])->name('lab.drug_screening.civilian_pdf');
    Route::get('/Lab_Analysis/drug-screening/suspect/{id}', [DrugScreeningController::class, 'getBySuspect'])->name('lab.drug_screening.suspect');
    Route::get('/Lab_Analysis/drug-screening/{id}', [DrugScreeningController::class, 'show'])->name('lab.drug_screening.show');
    Route::put('/Lab_Analysis/drug-screening/{id}', [DrugScreeningController::class, 'update'])->name('lab.drug_screening.update');
    Route::delete('/Lab_Analysis/drug-screening/{id}', [DrugScreeningController::class, 'destroy'])->name('lab.drug_screening.destroy');

    // DEDICATED COURT EVIDENCE & SITREP REPOSITORY (ALL UNITS PDF REGISTRY)
    Route::get('/court-evidence', [CourtEvidenceController::class, 'index'])->name('court_evidence.index');
    Route::get('/court-evidence-sitrep', [CourtEvidenceController::class, 'index']);
    Route::get('/court-evidence/download', [CourtEvidenceController::class, 'download'])->name('court_evidence.download');
    Route::get('/Forensic_Head/court-evidence', [CourtEvidenceController::class, 'index'])->name('forensic_head.court_evidence');

    // Route shortcuts & API
    Route::get('/api/suspect/{id}/drug-screening', [DrugScreeningController::class, 'getBySuspect'])->name('api.suspect.drug_screening');
    Route::get('/drug-screening', [DrugScreeningController::class, 'index']);
    Route::get('/drug-screening/create', [DrugScreeningController::class, 'create']);
    Route::post('/drug-screening', [DrugScreeningController::class, 'store']);
    Route::get('/drug-screening/generate-report', [DrugScreeningController::class, 'generateReportPdf']);
    Route::get('/drug-screening/report/pdf', [DrugScreeningController::class, 'generateReportPdf']);
    Route::put('/drug-screening/{id}', [DrugScreeningController::class, 'update']);
    Route::get('/drug-screening/{id}/pdf', [DrugScreeningController::class, 'showPdf']);

    // EVIDENCE MANAGEMENT UNIT DEDICATED ROUTES
    Route::get('/Evidence_Management/report', [EvidenceManagementController::class, 'report'])->name('evidence_management.report');
    Route::get('/evidence-management/report', [EvidenceManagementController::class, 'report']);
    Route::post('/Evidence_Management/report/notes', [EvidenceManagementController::class, 'saveReportNotes'])->name('evidence_management.report_notes.store');
    Route::post('/evidence-management/report/notes', [EvidenceManagementController::class, 'saveReportNotes']);
    Route::get('/Evidence_Management/report_pdf', [EvidenceManagementController::class, 'reportPdf'])->name('evidence_management.report_pdf');
    Route::get('/evidence-management/report_pdf', [EvidenceManagementController::class, 'reportPdf']);
    Route::get('/Evidence_Management/vault', [EvidenceManagementController::class, 'vault'])->name('evidence_management.vault');
    Route::get('/evidence-management/vault', [EvidenceManagementController::class, 'vault']);
    Route::get('/exhibit-vault', [EvidenceManagementController::class, 'vault'])->name('exhibit.vault');
    Route::get('/Evidence_Management/add-exhibit', [EvidenceManagementController::class, 'createUnassignedExhibit'])->name('evidence_management.add_exhibit');
    Route::get('/evidence-management/add-exhibit', [EvidenceManagementController::class, 'createUnassignedExhibit']);
    Route::post('/Evidence_Management/exhibits/store-unassigned', [EvidenceManagementController::class, 'storeUnassignedExhibits'])->name('evidence_management.exhibits.store_unassigned');
    Route::post('/evidence-management/exhibits/store-unassigned', [EvidenceManagementController::class, 'storeUnassignedExhibits']);
    Route::get('/api/exhibits/unassigned', [EvidenceManagementController::class, 'getUnassignedExhibits'])->name('api.exhibits.unassigned');
    Route::get('/api/unit-exhibit-alerts', [EvidenceManagementController::class, 'getUnitExhibitAlerts'])->name('api.unit.exhibit_alerts');
    Route::post('/Evidence_Management/exhibits/{id}/update', [EvidenceManagementController::class, 'updateExhibit'])->name('evidence_management.exhibit.update');
    Route::post('/evidence-management/exhibits/{id}/update', [EvidenceManagementController::class, 'updateExhibit']);
    Route::post('/exhibits/{id}/vault-update', [EvidenceManagementController::class, 'updateExhibit'])->name('exhibits.vault.update');
    Route::post('/Evidence_Management/exhibits/{id}/custodial-status', [EvidenceManagementController::class, 'updateCustodialStatus'])->name('evidence_management.exhibits.custodial_status');
    Route::post('/exhibits/{id}/custodial-status', [EvidenceManagementController::class, 'updateCustodialStatus']);

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

    Route::get('/Evidence_Management/staff/dashboard', function () {
        return view('Evidence_Management.staff.dashboard');
    })->name('evidence.staff.dashboard');

    Route::get('/Evidence_Management/staff/case', function () {
        return view('Evidence_Management.staff.case');
    });

    Route::get('/Evidence_Management/staff/cases', function () {
        return view('Evidence_Management.staff.case');
    });

    Route::get('/Evidence_Management/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('evidence.staff.cases.review');

    Route::get('/Evidence_Management/staff/create', function () {
        return view('Evidence_Management.staff.create');
    })->name('evidence.staff.create');

    Route::get('/Evidence_Management/staff/report', [EvidenceManagementController::class, 'report'])->name('evidence.staff.report');

    Route::get('/evidence-management', function () {
        return view('Evidence_Management.evidence_management');
    });

    // DIGITAL & PHYSICAL FORENSICS UNIT DEDICATED ROUTES
    Route::get('/Digital_Forensics', function () {
        return view('Digital_Forensics.digital_forensics');
    });

    Route::get('/Digital_Forensics/cases', function (\Illuminate\Http\Request $request) {
        $authUser = auth()->user();
        $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
                   ?? \App\Models\User::where('is_head', true)->first();
        $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
        $allCases = ForensicCase::allCases();
        $digitalCases = $allCases->filter(function ($c) {
            if (!$c->isDigitalCase()) {
                return false;
            }
            $elecSuspects = $c->getElectronicDeviceSuspects();
            return !empty($elecSuspects);
        })->values();

        $search = trim($request->input('search', ''));
        $from = trim($request->input('from', ''));
        $to = trim($request->input('to', ''));

        $filtered = $digitalCases;

        if ($search !== '') {
            $sLower = strtolower($search);
            $filtered = $filtered->filter(function ($c) use ($sLower) {
                $elecSuspects = $c->getElectronicDeviceSuspects();
                $userNames = implode(' ', array_filter(array_column($elecSuspects, 'suspect_name')));
                $userAliases = implode(' ', array_filter(array_column($elecSuspects, 'suspect_alias')));

                return str_contains(strtolower($c->case_number ?? ''), $sLower) ||
                       str_contains(strtolower($c->title ?? ''), $sLower) ||
                       str_contains(strtolower($c->suspect_name ?? ''), $sLower) ||
                       str_contains(strtolower($userNames), $sLower) ||
                       str_contains(strtolower($userAliases), $sLower) ||
                       str_contains(strtolower($c->submitting_officer ?? ''), $sLower) ||
                       str_contains(strtolower($c->incident_scene ?? ''), $sLower) ||
                       str_contains(strtolower($c->exhibits_summary ?? ''), $sLower);
            })->values();
        }

        if ($from !== '') {
            $filtered = $filtered->filter(function ($c) use ($from) {
                $d = $c->created_at ? substr((string)$c->created_at, 0, 10) : '';
                return $d >= $from;
            })->values();
        }

        if ($to !== '') {
            $filtered = $filtered->filter(function ($c) use ($to) {
                $d = $c->created_at ? substr((string)$c->created_at, 0, 10) : '';
                return $d <= $to;
            })->values();
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;
        $cases = ForensicCase::paginateCases($filtered, $perPage);

        if ($request->ajax()) {
            return view('Digital_Forensics.partials.cases_table', compact('cases', 'digitalCases', 'allCases'));
        }

        return view('Digital_Forensics.cases', compact('cases', 'headName', 'allCases', 'digitalCases'));
    })->name('digital_forensics.cases');

    Route::get('/Digital_Forensics/case-register', function () {
        return redirect('/Digital_Forensics/cases');
    });

    Route::get('/digital-forensics/cases', function () {
        return redirect('/Digital_Forensics/cases');
    });

    Route::get('/digital-forensics/case-register', function () {
        return redirect('/Digital_Forensics/cases');
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
                'submitting_officer' => $cItem->submitting_officer ?? 'N/A',
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

        $rawCNo = $validated['case_number'];
        $cNo = str_contains($rawCNo, '|') ? explode('|', $rawCNo)[0] : $rawCNo;
        $suspectIdFromVal = str_contains($rawCNo, '|') ? explode('|', $rawCNo)[1] : ($request->input('suspect_id') ?: null);
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
            : ('DF-STATUS-' . date('Y-md') . '-' . random_int(100, 999));

        $reportAttributes = [
            'dpf_code' => $dpfCode,
            'case_number' => $cNo,
            'laboratory_number' => $singleCase->laboratory_number ?? ($multiCase->laboratory_number ?? ($existingReport->laboratory_number ?? null)),
            'single_suspect_case_id' => $singleCase ? $singleCase->id : ($existingReport->single_suspect_case_id ?? null),
            'multiple_suspect_case_id' => $multiCase ? $multiCase->id : ($existingReport->multiple_suspect_case_id ?? null),
            'suspect_id' => $suspectIdFromVal ?? ($existingReport->suspect_id ?? null),
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

        // Locate case instance if not already resolved
        if (!$singleCase && !$multiCase) {
            $fCase = \App\Models\ForensicCase::allCases()->first(function($c) use ($cNo) {
                return strtolower(trim($c->case_number ?? '')) === strtolower(trim($cNo))
                    || strtolower(trim($c->laboratory_number ?? '')) === strtolower(trim($cNo));
            });
            if ($fCase) {
                if ($fCase->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
                    $multiCase = $fCase->underlyingRecord;
                } elseif ($fCase->underlyingRecord instanceof \App\Models\SingleSuspectCase) {
                    $singleCase = $fCase->underlyingRecord;
                }
            }
        }

        // Attach or detach Extraction & Investigation Status Log Report for the suspect case
        $isRwYes = (strtoupper(trim($rwStatus)) === 'YES')
            || (str_starts_with(strtoupper(trim($rwCombined)), 'YES'))
            || (strtoupper(trim($request->input('report_witness_statement_status') ?? '')) === 'YES')
            || (strtoupper(trim($request->input('report_witness_statement_sent') ?? '')) === 'YES');

        if ($isRwYes) {
            $targetSuspects = [];
            if (!empty($suspectIdFromVal)) {
                $specificSuspect = \App\Models\CaseSuspect::find((int)$suspectIdFromVal);
                if ($specificSuspect) {
                    $targetSuspects = [$specificSuspect];
                }
            }
            if (empty($targetSuspects)) {
                if ($multiCase) {
                    $targetSuspects = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->get()->all();
                } elseif ($singleCase) {
                    $sSuspect = \App\Models\CaseSuspect::firstOrCreate(
                        ['single_suspect_case_id' => $singleCase->id],
                        [
                            'suspect_name'   => $singleCase->suspect_name ?: 'Primary Suspect',
                            'role'           => 'Primary Suspect',
                            'suspect_number' => 1,
                        ]
                    );
                    $targetSuspects = [$sSuspect];
                }
            }
            if ($multiCase && empty($targetSuspects)) {
                $placeholder = \App\Models\CaseSuspect::create([
                    'multiple_suspect_case_id' => $multiCase->id,
                    'suspect_name'             => $multiCase->title ?: 'Primary Suspect',
                    'role'                     => 'Primary Suspect',
                    'suspect_number'           => 1,
                ]);
                $targetSuspects = [$placeholder];
            }

            $dpfReportUrl = '/Digital_Forensics/dpf-report-pdf/' . ($report->dpf_code ?: $cNo);
            $dpfFilename  = 'Extraction & Investigation Status Log Report (' . ($report->dpf_code ?: $cNo) . ').pdf';
            $dpfStored    = 'dpf_report_' . $report->id . '.pdf';

            if (!empty($targetSuspects)) {
                foreach ($targetSuspects as $targetSuspect) {
                    $existingDpf = \App\Models\SuspectGsaReport::where('case_suspect_id', $targetSuspect->id)
                        ->where(function ($q) {
                            $q->where('file_path', 'LIKE', '%dpf-report-pdf%')
                              ->orWhere('original_filename', 'LIKE', '%Extraction & Investigation Status Log Report%')
                              ->orWhere('original_filename', 'LIKE', '%Status Log Report%');
                        })
                        ->first();

                    $attachPayload = [
                        'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                        'single_suspect_case_id'   => $singleCase ? $singleCase->id : null,
                        'case_suspect_id'          => $targetSuspect->id,
                        'case_number'              => $cNo,
                        'original_filename'        => $dpfFilename,
                        'stored_filename'          => $dpfStored,
                        'file_path'                => $dpfReportUrl,
                        'file_type'                => 'application/pdf',
                        'file_extension'           => 'pdf',
                        'file_size'                => 0,
                        'exhibit_type'             => 'Digital Forensics',
                        'notes'                    => 'Attached Extraction & Investigation Status Log Report (' . ($report->dpf_code ?: $cNo) . ')',
                        'uploaded_by'              => auth()->id() ?? $report->user_id,
                        'status'                   => 'active',
                    ];

                    if ($existingDpf) {
                        $existingDpf->update($attachPayload);
                    } else {
                        \App\Models\SuspectGsaReport::create($attachPayload);
                    }
                }
            } else {
                $existingDpf = \App\Models\SuspectGsaReport::where(function ($q) use ($multiCase, $singleCase, $cNo) {
                    if ($multiCase) $q->orWhere('multiple_suspect_case_id', $multiCase->id);
                    if ($singleCase) $q->orWhere('single_suspect_case_id', $singleCase->id);
                    if ($cNo) $q->orWhere('case_number', $cNo);
                })
                ->where(function ($q) {
                    $q->where('file_path', 'LIKE', '%dpf-report-pdf%')
                      ->orWhere('original_filename', 'LIKE', '%Extraction & Investigation Status Log Report%')
                      ->orWhere('original_filename', 'LIKE', '%Status Log Report%');
                })
                ->first();

                $attachPayload = [
                    'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                    'single_suspect_case_id'   => $singleCase ? $singleCase->id : null,
                    'case_suspect_id'          => null,
                    'case_number'              => $cNo,
                    'original_filename'        => $dpfFilename,
                    'stored_filename'          => $dpfStored,
                    'file_path'                => $dpfReportUrl,
                    'file_type'                => 'application/pdf',
                    'file_extension'           => 'pdf',
                    'file_size'                => 0,
                    'exhibit_type'             => 'Digital Forensics',
                    'notes'                    => 'Attached Extraction & Investigation Status Log Report (' . ($report->dpf_code ?: $cNo) . ')',
                    'uploaded_by'              => auth()->id() ?? $report->user_id,
                    'status'                   => 'active',
                ];

                if ($existingDpf) {
                    $existingDpf->update($attachPayload);
                } else {
                    \App\Models\SuspectGsaReport::create($attachPayload);
                }
            }
        } else {
            // If the status is NOT YES, detach any previously attached Extraction & Investigation Status Log Report
            \App\Models\SuspectGsaReport::where(function ($q) use ($multiCase, $singleCase, $cNo) {
                if ($multiCase) $q->orWhere('multiple_suspect_case_id', $multiCase->id);
                if ($singleCase) $q->orWhere('single_suspect_case_id', $singleCase->id);
                if ($cNo) $q->orWhere('case_number', $cNo);
            })
            ->where(function ($q) {
                $q->where('file_path', 'LIKE', '%dpf-report-pdf%')
                  ->orWhere('original_filename', 'LIKE', '%Extraction & Investigation Status Log Report%')
                  ->orWhere('original_filename', 'LIKE', '%Status Log Report%');
            })
            ->delete();
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
        $authUser = auth()->user();
        $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
                   ?? \App\Models\User::where('is_head', true)->first();
        $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
        $allCases = ForensicCase::allCases();
        $cases = $allCases->filter(function ($c) {
            if (!$c->isDigitalCase()) {
                return false;
            }
            $elecSuspects = $c->getElectronicDeviceSuspects();
            return !empty($elecSuspects);
        })->values();
        return view('Digital_Forensics.cases', compact('cases', 'headName'));
    });

    Route::get('/Digital_Forensics/staff', function () {
        return view('Digital_Forensics.staff.staff');
    });

    Route::get('/Digital_Forensics/staff/dashboard', function () {
        return view('Digital_Forensics.staff.dashboard');
    })->name('digital.staff.dashboard');

    Route::get('/Digital_Forensics/staff/case', function () {
        return view('Digital_Forensics.staff.case');
    });

    Route::get('/Digital_Forensics/staff/cases', function () {
        return view('Digital_Forensics.staff.case');
    });

    Route::get('/Digital_Forensics/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('digital.staff.cases.review');

    Route::get('/Digital_Forensics/staff/create', function () {
        return view('Digital_Forensics.staff.create');
    })->name('digital.staff.create');

    Route::get('/Digital_Forensics/staff/forensic-status', function () {
        return view('Digital_Forensics.staff.forensic_status');
    })->name('digital.staff.forensic_status');

    Route::get('/Digital_Forensics/staff/forensic_status', function () {
        return view('Digital_Forensics.staff.forensic_status');
    });

    Route::get('/digital-forensics/staff/forensic-status', function () {
        return view('Digital_Forensics.staff.forensic_status');
    });

    Route::get('/Digital_Forensics/staff/report', [CrimeSceneSupportCaseController::class, 'report'])->name('digital.staff.report');

    Route::get('/Forensic_Head/staff/dashboard', function () {
        return view('Forensic_Head.staff.dashboard');
    })->name('forensic_head.staff.dashboard');

    Route::get('/Forensic_Head/staff/case', function () {
        return view('Forensic_Head.staff.case');
    });

    Route::get('/Forensic_Head/staff/cases', function () {
        return view('Forensic_Head.staff.case');
    });

    Route::get('/Forensic_Head/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('forensic_head.staff.cases.review');

    Route::get('/Forensic_Head/staff/create', function () {
        return view('Forensic_Head.staff.create');
    })->name('forensic_head.staff.create');

    Route::get('/Forensic_Head/staff/report', [CrimeSceneSupportCaseController::class, 'report'])->name('forensic_head.staff.report');

    Route::get('/staff/dashboard', function () {
        return view('staff.dashboard');
    })->name('staff.dashboard');

    Route::get('/staff/case', function () {
        return view('staff.case');
    });

    Route::get('/staff/cases', function () {
        return view('staff.case');
    });

    Route::get('/staff/cases/{id}/review', [CaseController::class, 'staffReview'])->name('staff.cases.review');

    Route::get('/staff/create', function () {
        return view('staff.create');
    })->name('staff.create');

    Route::get('/staff/report', function (\Illuminate\Http\Request $request) {
        $u = strtolower(auth()->user()->unit ?? ($request->query('from') ?? 'crime'));
        if (str_contains($u, 'lab')) {
            return redirect('/Lab_Analysis/staff/report');
        } elseif (str_contains($u, 'evidence')) {
            return redirect('/Evidence_Management/staff/report');
        } elseif (str_contains($u, 'digital') || str_contains($u, 'physical')) {
            return redirect('/Digital_Forensics/staff/report');
        } elseif (str_contains($u, 'directorate') || str_contains($u, 'head')) {
            return redirect('/Forensic_Head/staff/report');
        }
        return redirect('/Crime_Scene_Support/staff/report');
    })->name('staff.report');

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
            $mapConfig = app(\App\Services\CaseAnalyticsService::class)->buildLeafletMapPayload($cases);
            return view('Forensic_Head.head', compact('cases', 'pendingCasesCount', 'pendingCount', 'mapConfig'));
        });

        Route::get('/Forensic_Head/cases', function (\Illuminate\Http\Request $request) {
            $allCases = ForensicCase::allCases();

            $search = trim($request->input('search', ''));
            $status = trim($request->input('status', ''));
            $unit = trim($request->input('unit', ''));

            $filtered = $allCases;

            if ($search !== '') {
                $sLower = strtolower($search);
                $filtered = $filtered->filter(function ($c) use ($sLower) {
                    return str_contains(strtolower($c->case_number ?? ''), $sLower) ||
                           str_contains(strtolower($c->title ?? ''), $sLower) ||
                           str_contains(strtolower($c->suspect_name ?? ''), $sLower) ||
                           str_contains(strtolower($c->submitting_officer ?? ''), $sLower) ||
                           str_contains(strtolower($c->unit_name ?? ''), $sLower) ||
                           str_contains(strtolower($c->status ?? ''), $sLower);
                })->values();
            }

            if ($status !== '' && strtoupper($status) !== 'ALL') {
                $sLower = strtolower($status);
                $filtered = $filtered->filter(function ($c) use ($sLower) {
                    $cStat = strtolower($c->status ?? '');
                    if (str_contains($sLower, 'await') || str_contains($sLower, 'pending')) {
                        return str_contains($cStat, 'await') || str_contains($cStat, 'pending') || str_contains($cStat, 'multi-unit');
                    }
                    if (str_contains($sLower, 'authoriz') || str_contains($sLower, 'approv') || str_contains($sLower, 'court')) {
                        return str_contains($cStat, 'approv') || str_contains($cStat, 'authoriz') || str_contains($cStat, 'court') || str_contains($cStat, 'completed') || str_contains($cStat, 'certified');
                    }
                    if (str_contains($sLower, 'vault')) {
                        return str_contains($cStat, 'vault') || str_contains(strtolower($c->unit_name ?? ''), 'evidence');
                    }
                    if (str_contains($sLower, 'lab')) {
                        return str_contains($cStat, 'lab') || str_contains(strtolower($c->unit_name ?? ''), 'lab');
                    }
                    return strcasecmp($c->status ?? '', $sLower) === 0 || str_contains($cStat, $sLower);
                })->values();
            }

            if ($unit !== '' && strtoupper($unit) !== 'ALL') {
                $uLower = strtolower($unit);
                $filtered = $filtered->filter(function ($c) use ($uLower) {
                    $cUnit = strtolower($c->unit_name ?? '');
                    if (str_contains($uLower, 'crime') || str_contains($uLower, 'scene') || $uLower === 'css') {
                        return str_contains($cUnit, 'crime') || str_contains($cUnit, 'scene') || $cUnit === 'css';
                    }
                    if (str_contains($uLower, 'evidence')) {
                        return str_contains($cUnit, 'evidence');
                    }
                    if (str_contains($uLower, 'lab')) {
                        return str_contains($cUnit, 'lab');
                    }
                    if (str_contains($uLower, 'digital') || str_contains($uLower, 'physical')) {
                        return str_contains($cUnit, 'digital') || str_contains($cUnit, 'physical') || str_contains($cUnit, 'dpf');
                    }
                    return str_contains($cUnit, $uLower);
                })->values();
            }

            $pendingCasesCount = $allCases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
            $pendingCount = User::where('is_approved', false)->count();
            $perPage = (int) $request->input('per_page', 15);
            if ($perPage < 1) $perPage = 15;
            if ($perPage > 100) $perPage = 100;
            $cases = ForensicCase::paginateCases($filtered, $perPage);

            if ($request->ajax()) {
                return view('Forensic_Head.partials.cases_table', compact('cases', 'allCases'));
            }

            return view('Forensic_Head.cases', compact('cases', 'pendingCasesCount', 'pendingCount', 'allCases'));
        });

        Route::get('/Forensic_Head/cases/{id}/review', [ForensicHeadCaseController::class, 'showReview'])->name('forensic_head.cases.review');
        Route::get('/Forensic_Head/cases/{id}/pdf', [CaseController::class, 'showPdf']);
        Route::get('/Forensic_Head/personnel-roster', [PersonnelRosterController::class, 'index'])->name('forensic_head.personnel_roster');

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
        Route::post('/Forensic_Head/cases/{id}/approve', [CaseController::class, 'approve']);
        Route::post('/Forensic_Head/cases/{id}/decline', [CaseController::class, 'decline']);
    });
});
