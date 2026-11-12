<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Services\CaseDocketService;
use App\Services\CaseDuplicateDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CaseController - Primary Case Docket Controller
 *
 * Handles case listing, creation, modification, association, approvals, and reviews.
 * Split from the original monolithic controller according to Issue 16 & 17:
 * - CaseWorkflowController handles multi-unit progress, status updates, handovers, and audit trails.
 * - CaseDocumentController handles lab certificates, analytical reports, NLR/GSA reports, and photos.
 * - CaseReportController handles lab results, sitrep reports, and realtime metrics.
 */
class CaseController extends Controller
{
    use CaseControllerHelpers;

    /**
     * Display a listing of all cases stored in the database.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 18: Use WorkflowStatus enum.
     * Fix Issue 24: Fix ?paginate handling so presence of the key is truthy.
     */
    public function index(Request $request)
    {
        $cases = app(CaseDocketService::class)->getCaseListing();

        // WHY Fix Issue 24: ?paginate (without value) previously returned false in $request->boolean().
        $wantsPagination = $request->has('page') || $request->has('per_page') || $request->has('paginate');
        if ($wantsPagination) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
            return response()->json(ForensicCase::paginateCases($cases, $perPage));
        }

        return response()->json($cases);
    }

    /**
     * Store a new case entered by a Unit Head into the database table.
     * Fix Issue 2: Uses CaseNumberService with crypto-random suffix & withUniqueRetry.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 11: Centralized lab exam handling via LaboratoryExamService.
     * Fix Issue 18: Use WorkflowStatus / UnitName enums.
     * Fix Issue 20: Dead code removed ($exCatStore / $isMultipleCasePdf).
     * Fix Issue 21: Null-safe Auth::user() audit logging.
     */
    public function store(Request $request)
    {
        if ($request->input('from') === 'Forensic_Head' || $request->input('from') === 'Forensic Head') {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Directorate Head is not permitted to create cases.'], 403)
                : redirect('/Forensic_Head/cases')->with('error', 'Directorate Head is not permitted to create cases.');
        }

        try {
            $result = app(CaseDocketService::class)->storeCase($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'An error occurred while creating the case docket: ' . $e->getMessage(), 'error' => $e->getMessage()], 500);
            }
            throw $e;
        }

        $code = $result['code'] ?? 200;
        unset($result['code']);

        if ($request->wantsJson()) {
            return response()->json($result, $code);
        }

        return !empty($result['success'])
            ? back()->with('status', "Case {$result['case']->case_number} created and saved to database successfully.")
            : back()->with('error', $result['message'] ?? 'Error creating case.');
    }

    /**
     * Display dedicated full-page editor for updating case record & correcting data.
     * Fix Issue 7a: Strict 404/redirect if case not found. No fabricated fallbacks.
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showEdit($id)
    {
        if (request('from') === 'Forensic_Head' || request('from') === 'Forensic Head') {
            return redirect()->route('forensic_head.cases.review', ['id' => $id])
                ->with('error', 'Directorate Head is not permitted to edit cases. Directorate Heads only review and approve cases.');
        }

        $case = $this->findCase($id);
        if (!$case) {
            return redirect('/cases')->with('error', "Case {$id} not found.");
        }
        $this->repairExhibitAssociations($case);
        return view('cases.edit', compact('case'));
    }

    /**
     * Update case data and correct mistakes with strict Unit RBAC permission guards.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 11: LaboratoryExamService.
     * Fix Issue 28: authorizeCaseWrite check.
     */
    public function updateCase($id, Request $request)
    {
        if ($request->input('from') === 'Forensic_Head' || $request->input('from') === 'Forensic Head') {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Directorate Head is not permitted to edit cases.'], 403)
                : redirect()->route('forensic_head.cases.review', ['id' => $id])->with('error', 'Directorate Head is not permitted to edit cases.');
        }

        $case = $this->findCase($id);
        if (!$case) {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => "Case {$id} not found."], 404)
                : redirect('/cases')->with('error', "Case {$id} not found.");
        }

        // Fix Issue 28: Authorize user permission to modify this case
        $this->authorizeCaseWrite($case);

        $result = app(CaseDocketService::class)->updateCase($case, $request);
        $code = $result['code'] ?? 200;
        unset($result['code']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result, $code);
        }

        return !empty($result['success'])
            ? redirect()->route('cases.review', $case->case_number)->with('status', $result['message'])
            : back()->with('error', $result['message'] ?? 'Failed to update case.');
    }

    /**
     * Display dedicated full page view for associating persons & exhibits to a case.
     */
    public function showAssociate($id, Request $request = null)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return redirect('/cases')->with('error', "Case {$id} not found.");
        }

        $request = $request ?? request();
        $user = auth()->user();
        $unitParam = $request->query('from') ?? $request->query('unit') ?? $request->route('from') ?? ($user->unit ?? (session('user_unit') ?? ''));
        $unitLower = strtolower((string)$unitParam);

        if (str_contains($unitLower, 'lab')) {
            return view('Lab_Analysis.associate', compact('case'));
        }
        if (str_contains($unitLower, 'digital') || str_contains($unitLower, 'physical')) {
            return view('Digital_Forensics.associate', compact('case'));
        }
        if (str_contains($unitLower, 'crime') || str_contains($unitLower, 'field')) {
            return view('Crime_Scene_Support.associate', compact('case'));
        }
        if (str_contains($unitLower, 'evidence')) {
            return view('Evidence_Management.associate', compact('case'));
        }

        return view('cases.associate', compact('case'));
    }

    /**
     * Associate additional persons/suspects and exhibits to an existing case file.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 28: authorizeCaseWrite check.
     * Hardening C: isDrugExhibit helper.
     */
    public function associate($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        // Fix Issue 28: Authorize user permission
        $this->authorizeCaseWrite($case);

        $result = app(CaseDocketService::class)->associateCase($case, $request);

        return $request->wantsJson() || $request->ajax()
            ? response()->json($result)
            : redirect($result['redirect'])->with('status', $result['message']);
    }

    /**
     * Re-assign an exhibit item to a specific associate or primary suspect.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 28: authorizeCaseWrite check.
     */
    public function reassignExhibit($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return back()->with('error', "Case {$id} not found.");
        }

        $this->authorizeCaseWrite($case);

        $result = app(CaseDocketService::class)->reassignExhibit($case, $request);
        $code = $result['code'] ?? 200;
        unset($result['code']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result, $code);
        }

        return !empty($result['success'])
            ? back()->with('status', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Approve or update case status in database.
     * Fix Issue 7a: Return strict 404 if case does not exist. Do not auto-create fake case!
     * Fix Issue 18: Use CaseStatus enum.
     */
    public function approve($id)
    {
        $case = $this->findCase($id);
        // WHY Fix Issue 7a: Prior code fabricated a ForensicCase when $case was null.
        if (!$case) {
            return request()->wantsJson() || request()->ajax()
                ? response()->json(['success' => false, 'message' => "Case {$id} not found."], 404)
                : back()->with('error', "Case {$id} not found.");
        }

        $case->status = CaseStatus::APPROVED->value;
        $case->save();

        return request()->wantsJson() || request()->ajax()
            ? response()->json(['success' => true, 'message' => "Case {$case->case_number} approved successfully.", 'case' => $case])
            : back()->with('status', "Case {$case->case_number} approved successfully.");
    }

    /**
     * Decline or reject case status in database.
     * Fix Issue 7a: Return strict 404 if case does not exist. Do not auto-create fake case!
     * Fix Issue 18: Use CaseStatus enum.
     */
    public function decline($id)
    {
        $case = $this->findCase($id);
        // WHY Fix Issue 7a: Prior code fabricated a ForensicCase when $case was null.
        if (!$case) {
            return request()->wantsJson() || request()->ajax()
                ? response()->json(['success' => false, 'message' => "Case {$id} not found."], 404)
                : back()->with('error', "Case {$id} not found.");
        }

        $case->status = CaseStatus::DECLINED->value;
        $case->save();

        return request()->wantsJson() || request()->ajax()
            ? response()->json(['success' => true, 'message' => "Case {$case->case_number} has been declined.", 'case' => $case])
            : back()->with('status', "Case {$case->case_number} has been declined.");
    }

    /**
     * Batch approve all pending cases in database.
     * Fix Issue 18: Use CaseStatus enum.
     */
    public function batchApprove()
    {
        $updatedCount = ForensicCase::whereIn('status', [
            CaseStatus::AWAITING_SIGN_OFF->value,
            'Pending Approval',
            'Pending',
            WorkflowStatus::PENDING->value,
        ])->update(['status' => CaseStatus::APPROVED->value]);

        return request()->wantsJson() || request()->ajax()
            ? response()->json(['success' => true, 'message' => "Successfully batch approved {$updatedCount} pending cases.", 'count' => $updatedCount])
            : back()->with('status', "Successfully batch approved {$updatedCount} pending cases.");
    }

    /**
     * Display dedicated full page case review.
     * Fix Issue 7a: Strict 404 if case not found. Never fabricate suspect or exhibit data!
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showReview($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        $targetRoute = app(\App\Services\CaseShowReviewPresenter::class)
            ->resolveLegacyRedirectRoute($case, request(), auth()->user());

        return redirect()->route($targetRoute, array_merge(['id' => $id], request()->query()));
    }

    /**
     * Display printable case dossier PDF view.
     * Fix Issue 7a: Strict 404 if case not found. Never fabricate dummy suspect or exhibit data!
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showPdf($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);
        return view('Forensic_Head.case_pdf', compact('case'));
    }

    /**
     * API endpoint to check suspect biodata duplicate before submission.
     */
    public function checkDuplicateSuspect(Request $request)
    {
        return response()->json((new CaseDuplicateDetector())->checkSuspectBiodata($request->all()));
    }

    /**
     * API endpoint to check case duplicate before submission.
     */
    public function checkDuplicateCase(Request $request)
    {
        $inputDocket = $this->decodeDocket($request->input('docket_data'));
        $allSuspects = (isset($inputDocket['suspects']) && is_array($inputDocket['suspects']))
            ? array_values(array_filter($inputDocket['suspects'], fn($s) => !empty(trim($s['suspect_name'] ?? '')) && !in_array(trim($s['suspect_name']), ['Unnamed Suspect', 'Unknown'])))
            : [];

        if (empty($allSuspects) && $request->filled('suspect_name')) {
            $allSuspects = [[
                'suspect_name' => $request->input('suspect_name'),
                'ghana_card'   => $request->input('ghana_card'),
                'dob'          => $request->input('dob'),
                'gender'       => $request->input('gender'),
                'nationality'  => $request->input('nationality'),
                'phone'        => $request->input('phone'),
                'address'      => $request->input('address'),
            ]];
        }

        return response()->json((new CaseDuplicateDetector())->checkCaseDuplicate($request->all(), $allSuspects));
    }

    // =========================================================================
    // DELEGATION PROXIES (Fix Issue 16 & 17)
    // Ensures 100% backward compatibility for direct controller invocations or
    // route bindings while controllers are split into focused domains.
    // =========================================================================

    public function updateUnitProgress($id, Request $request) { return app(CaseWorkflowController::class)->updateUnitProgress($id, $request); }
    public function updateCaseStatus(Request $request, $id) { return app(CaseWorkflowController::class)->updateCaseStatus($request, $id); }
    public function updateSuspectDisposition(Request $request, $id) { return app(CaseWorkflowController::class)->updateSuspectDisposition($request, $id); }
    public function initiateHandover(Request $request, $id) { return app(CaseWorkflowController::class)->initiateHandover($request, $id); }
    public function respondHandover(Request $request, $id) { return app(CaseWorkflowController::class)->respondHandover($request, $id); }
    public function getAuditTrail($id) { return app(CaseWorkflowController::class)->getAuditTrail($id); }
    public function showLabCertificate($id) { return app(CaseDocumentController::class)->showLabCertificate($id); }
    public function showLabAnalytical($id) { return app(CaseDocumentController::class)->showLabAnalytical($id); }
    public function showNlrReportPdf($id) { return app(CaseDocumentController::class)->showNlrReportPdf($id); }
    public function storeNlrReport(Request $request) { return app(CaseDocumentController::class)->storeNlrReport($request); }
    public function storeGsaReports(Request $request, $id = null) { return app(CaseDocumentController::class)->storeGsaReports($request, $id); }
    public function uploadEvidencePhotos(Request $request) { return app(CaseDocumentController::class)->uploadEvidencePhotos($request); }
    public function labResultPage(Request $request) { return app(CaseReportController::class)->labResultPage($request); }
    public function labReportPage(Request $request) { return app(CaseReportController::class)->labReportPage($request); }
    public function labSitrepReport(Request $request) { return app(CaseReportController::class)->labSitrepReport($request); }
    public function dashboardRealtime(Request $request) { return app(CaseReportController::class)->dashboardRealtime($request); }
}
