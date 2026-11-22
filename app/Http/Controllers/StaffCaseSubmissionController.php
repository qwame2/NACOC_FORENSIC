<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\StaffAccount;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use App\Services\CaseDocketService;
use App\Services\CaseShowReviewPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffCaseSubmissionController extends Controller
{
    protected function resolveUnitContext(Request $request, ?StaffCaseSubmission $submission = null): array
    {
        $path = strtolower($request->path());
        if (str_contains($path, 'lab_analysis') || str_contains($path, 'lab')) {
            return ['key' => 'lab_analysis', 'label' => 'Lab Analysis Unit', 'sidebar' => 'Lab Analysis', 'sidebar_slug' => 'Lab_Analysis'];
        }
        if (str_contains($path, 'evidence_management') || str_contains($path, 'evidence')) {
            return ['key' => 'evidence_management', 'label' => 'Evidence Management Unit', 'sidebar' => 'Evidence Management', 'sidebar_slug' => 'Evidence_Management'];
        }
        if (str_contains($path, 'digital_forensics') || str_contains($path, 'digital')) {
            return ['key' => 'digital_forensics', 'label' => 'Digital & Physical Forensics Unit', 'sidebar' => 'Digital Forensics', 'sidebar_slug' => 'Digital_Forensics'];
        }
        if (str_contains($path, 'forensic_head')) {
            return ['key' => 'forensic_head', 'label' => 'Forensic Directorate (All Units)', 'sidebar' => 'Forensic Directorate', 'sidebar_slug' => 'Forensic_Head'];
        }
        if (str_contains($path, 'crime_scene_support') || str_contains($path, 'crime_scene')) {
            return ['key' => 'crime_scene_support', 'label' => 'Crime Scene Support Unit', 'sidebar' => 'Crime Scene Support', 'sidebar_slug' => 'Crime_Scene_Support'];
        }

        // Fallback when URL does not contain a unit slug (e.g. /reviews-approvals/1/rollback)
        $fallbackUnit = $submission?->unit_name ?? Auth::user()?->unit;
        $normKey = $this->normalizeUnitKey($fallbackUnit);

        return match ($normKey) {
            'lab_analysis' => ['key' => 'lab_analysis', 'label' => 'Lab Analysis Unit', 'sidebar' => 'Lab Analysis', 'sidebar_slug' => 'Lab_Analysis'],
            'evidence_management' => ['key' => 'evidence_management', 'label' => 'Evidence Management Unit', 'sidebar' => 'Evidence Management', 'sidebar_slug' => 'Evidence_Management'],
            'digital_forensics' => ['key' => 'digital_forensics', 'label' => 'Digital & Physical Forensics Unit', 'sidebar' => 'Digital Forensics', 'sidebar_slug' => 'Digital_Forensics'],
            'forensic_head' => ['key' => 'forensic_head', 'label' => 'Forensic Directorate (All Units)', 'sidebar' => 'Forensic Directorate', 'sidebar_slug' => 'Forensic_Head'],
            default => ['key' => 'crime_scene_support', 'label' => 'Crime Scene Support Unit', 'sidebar' => 'Crime Scene Support', 'sidebar_slug' => 'Crime_Scene_Support'],
        };
    }

    protected function normalizeUnitKey(?string $unit): ?string
    {
        $u = strtolower(trim((string) $unit));
        if ($u === '') {
            return null;
        }
        if (str_contains($u, 'directorate') || str_contains($u, 'forensic_head') || str_contains($u, 'forensic head') || str_contains($u, 'head of forensic')) {
            return 'forensic_head';
        }
        if (str_contains($u, 'lab')) {
            return 'lab_analysis';
        }
        if (str_contains($u, 'evidence') || str_contains($u, 'vault')) {
            return 'evidence_management';
        }
        if (str_contains($u, 'digital') || str_contains($u, 'physical') || str_contains($u, 'cyber')) {
            return 'digital_forensics';
        }
        if (str_contains($u, 'crime')) {
            return 'crime_scene_support';
        }
        return null;
    }

    protected function headCanAccessSubmission(User $head, StaffCaseSubmission $submission, Request $request): bool
    {
        $headUnitKey = $this->normalizeUnitKey($head->unit);
        $submissionUnitKey = $this->normalizeUnitKey($submission->unit_name);

        if (!$headUnitKey || !$submissionUnitKey) {
            return false;
        }

        if ($headUnitKey !== 'forensic_head' && $headUnitKey !== $submissionUnitKey) {
            return false;
        }

        $path = strtolower($request->path());
        if (
            str_contains($path, 'lab_analysis') ||
            str_contains($path, 'evidence_management') ||
            str_contains($path, 'digital_forensics') ||
            str_contains($path, 'crime_scene_support')
        ) {
            $routeCtx = $this->resolveUnitContext($request);
            if ($routeCtx['key'] !== $submissionUnitKey) {
                return false;
            }
        }

        return true;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can access Reviews & Approvals.');
        }

        $unitCtx = $this->resolveUnitContext($request);
        $headUnitKey = $this->normalizeUnitKey($user->unit);
        if ($headUnitKey && $headUnitKey !== 'forensic_head' && $headUnitKey !== $unitCtx['key']) {
            abort(403, 'You are not authorized to review submissions for another unit.');
        }

        $scopeUnit = ($headUnitKey && $headUnitKey !== 'forensic_head')
            ? ($user->unit ?? $unitCtx['sidebar'])
            : $unitCtx['sidebar'];
        $baseQuery = StaffCaseSubmission::forUnit($scopeUnit);

        $pendingSubmissions = (clone $baseQuery)
            ->whereIn('status', ['Pending Approval', 'Rolled Back'])
            ->orderByRaw("CASE WHEN status = 'Pending Approval' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();

        $reviewedSubmissions = (clone $baseQuery)
            ->where('status', 'Approved')
            ->orderByDesc('reviewed_at')
            ->limit(25)
            ->get();

        return view('head.reviews_approvals', [
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'pendingSubmissions' => $pendingSubmissions,
            'reviewedSubmissions' => $reviewedSubmissions,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can access Reviews & Approvals.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($user, $submission, $request)) {
            abort(403, 'You are not authorized to review submissions for another unit.');
        }

        $unitCtx = $this->resolveUnitContext($request);
        $payload = is_array($submission->payload) ? $submission->payload : [];

        $submitterUser = null;
        if (!empty($submission->submitted_by)) {
            $submitterUser = StaffAccount::find($submission->submitted_by) ?? User::find($submission->submitted_by);
        }
        if (!$submitterUser && !empty($submission->submitted_by_name)) {
            $submitterUser = new User([
                'fullname' => $submission->submitted_by_name,
                'name' => $submission->submitted_by_name,
                'unit' => $submission->unit_name ?? $unitCtx['sidebar'],
            ]);
        }

        // Build a virtual ForensicCase instance populated from submission payload
        $case = new ForensicCase();
        $case->id = $submission->id;
        $case->user_id = $submission->submitted_by;
        if ($submitterUser) {
            $case->setRelation('user', $submitterUser);
        }
        $case->case_number = $submission->case_number_preview ?? ($payload['case_number'] ?? ('SUB-' . $submission->id));
        $case->title = $submission->title ?? ($payload['title'] ?? 'Staff Case Submission');
        $case->unit_name = $submission->unit_name ?? ($payload['unit_name'] ?? $unitCtx['sidebar']);
        $case->operation_code_name = $payload['operation_code_name'] ?? ($payload['operation_code'] ?? null);
        $case->operation_code = $payload['operation_code'] ?? null;
        $case->status = $submission->status ?? ($payload['status'] ?? 'Pending Approval');
        $case->priority = $payload['priority'] ?? 'Medium';
        $case->suspect_name = $submission->suspect_name ?? ($payload['suspect_name'] ?? null);
        $case->ghana_card = $payload['ghana_card'] ?? null;
        $case->location = $payload['location'] ?? ($payload['raid_location'] ?? null);
        $case->description = $payload['description'] ?? ($payload['offense_description'] ?? null);
        $case->arresting_officer = $payload['arresting_officer'] ?? null;
        $case->investigating_officer = $payload['investigating_officer'] ?? null;
        $case->submitting_officer = $payload['submitting_officer'] ?? ($submission->submitted_by_name ?? null);
        $case->created_at = $submission->created_at ?? now();
        $case->updated_at = $submission->updated_at ?? now();

        $docketData = is_array($payload['docket_data'] ?? null) ? $payload['docket_data'] : [];
        $docketData['created_by_name'] = $submission->submitted_by_name ?? ($submitterUser?->fullname ?? 'Staff Officer');
        $docketData['created_by_unit'] = $submission->unit_name ?? $unitCtx['sidebar'];
        $case->docket_data = $docketData;

        $case->seizures = is_array($payload['seizures'] ?? null) ? $payload['seizures'] : [];
        $case->search_warrant_document = $payload['search_warrant_document'] ?? null;
        $case->description_of_exhibits = $payload['description_of_exhibits'] ?? null;
        $case->laboratory_number = $payload['laboratory_number'] ?? null;

        // Map unit key for CaseShowReviewPresenter: css, lab, dpf, em, hof
        $presenterKey = match ($unitCtx['key']) {
            'lab_analysis' => 'lab',
            'digital_forensics' => 'dpf',
            'evidence_management' => 'em',
            'forensic_head' => 'hof',
            default => 'css',
        };

        $presenter = app(CaseShowReviewPresenter::class)->presentForUnit($case, $presenterKey, $request, $user);

        return view('head.submission_review', array_merge($presenter->toViewData(), [
            'submission'  => $submission,
            'case'        => $case,
            'presenter'   => $presenter,
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'payload'     => $payload,
        ]));
    }

    public function approve(Request $request, $id)
    {
        $head = Auth::user();
        if (!$head || !$head->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can approve staff case submissions.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($head, $submission, $request)) {
            abort(403, 'You are not authorized to review or approve cases created by staff from another unit.');
        }
        if ($submission->status === 'Rolled Back') {
            return back()->with('error', "Submission #{$submission->id} is currently rolled back to staff for corrections.");
        }
        if ($submission->status !== 'Pending Approval') {
            return back()->with('error', "Submission #{$submission->id} has already been {$submission->status}.");
        }

        $payload = is_array($submission->payload) ? $submission->payload : [];
        $payload['approved_by_head'] = true;
        $payload['head_reviewer_id'] = $head->id;

        $replayRequest = Request::create('/cases', 'POST', $payload);
        $submitter = $submission->submitted_by
            ? (StaffAccount::find($submission->submitted_by) ?? User::find($submission->submitted_by))
            : $head;
        $replayRequest->setUserResolver(fn () => $submitter ?: $head);

        $result = app(CaseDocketService::class)->storeCase($replayRequest);
        if (empty($result['success'])) {
            return back()->with('error', $result['message'] ?? 'Failed to save approved case to database.');
        }

        $caseNumber = $result['case']->case_number ?? null;

        $submission->update([
            'status' => 'Approved',
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
            'approved_case_number' => $caseNumber,
        ]);

        $targetUnitKey = $this->normalizeUnitKey($submission->unit_name) ?: ($this->normalizeUnitKey($head->unit) ?: 'crime_scene_support');
        $redirectUrl = match ($targetUnitKey) {
            'lab_analysis' => url('/Lab_Analysis/reviews-approvals'),
            'evidence_management' => url('/Evidence_Management/reviews-approvals'),
            'digital_forensics' => url('/Digital_Forensics/reviews-approvals'),
            'forensic_head' => url('/Forensic_Head/reviews-approvals'),
            default => url('/Crime_Scene_Support/reviews-approvals'),
        };

        $msg = "Submission #{$submission->id} approved and saved to database as Case {$caseNumber}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', $msg);
    }

    public function decline(Request $request, $id)
    {
        $head = Auth::user();
        if (!$head || !$head->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can decline staff case submissions.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($head, $submission, $request)) {
            abort(403, 'You are not authorized to review or approve cases created by staff from another unit.');
        }
        if ($submission->status === 'Rolled Back') {
            return back()->with('error', "Submission #{$submission->id} is currently rolled back to staff for corrections.");
        }
        if ($submission->status !== 'Pending Approval') {
            return back()->with('error', "Submission #{$submission->id} has already been {$submission->status}.");
        }

        $submission->update([
            'status' => 'Declined',
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes') ?: 'Declined by Unit Head during review.',
        ]);

        $targetUnitKey = $this->normalizeUnitKey($submission->unit_name) ?: ($this->normalizeUnitKey($head->unit) ?: 'crime_scene_support');
        $redirectUrl = match ($targetUnitKey) {
            'lab_analysis' => url('/Lab_Analysis/reviews-approvals'),
            'evidence_management' => url('/Evidence_Management/reviews-approvals'),
            'digital_forensics' => url('/Digital_Forensics/reviews-approvals'),
            'forensic_head' => url('/Forensic_Head/reviews-approvals'),
            default => url('/Crime_Scene_Support/reviews-approvals'),
        };

        $msg = "Submission #{$submission->id} has been declined and was not saved to the database.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', $msg);
    }

    public function rollback(Request $request, $id)
    {
        $head = Auth::user();
        if (!$head || !$head->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can request case rollbacks.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($head, $submission, $request)) {
            abort(403, 'You are not authorized to review or rollback cases created by staff from another unit.');
        }

        if ($submission->status === 'Approved') {
            return back()->with('error', "Submission #{$submission->id} has already been approved and saved to database.");
        }
        if ($submission->status === 'Rolled Back') {
            return back()->with('error', "Submission #{$submission->id} is already rolled back to staff. Please wait for staff to resubmit.");
        }

        // Parse corrections payload: can be array or JSON string
        $rawCorrections = $request->input('corrections');
        $corrections = [];
        if (is_string($rawCorrections)) {
            $decoded = json_decode($rawCorrections, true);
            if (is_array($decoded)) {
                $corrections = $decoded;
            }
        } elseif (is_array($rawCorrections)) {
            $corrections = $rawCorrections;
        }

        $reviewNotes = trim((string) $request->input('review_notes'));
        if (empty($reviewNotes) && empty($corrections)) {
            $reviewNotes = 'Case rolled back for requested corrections. Please review the highlighted suspect details.';
        }

        $submission->update([
            'status' => 'Rolled Back',
            'rollback_corrections' => $corrections,
            'review_notes' => $reviewNotes,
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
            'reviewed_at' => now(),
            'rolled_back_at' => now(),
        ]);

        $targetUnitKey = $this->normalizeUnitKey($submission->unit_name) ?: ($this->normalizeUnitKey($head->unit) ?: 'crime_scene_support');
        $redirectUrl = match ($targetUnitKey) {
            'lab_analysis' => url('/Lab_Analysis/reviews-approvals'),
            'evidence_management' => url('/Evidence_Management/reviews-approvals'),
            'digital_forensics' => url('/Digital_Forensics/reviews-approvals'),
            'forensic_head' => url('/Forensic_Head/reviews-approvals'),
            default => url('/Crime_Scene_Support/reviews-approvals'),
        };

        $msg = "Submission #{$submission->id} has been rolled back to staff officer for requested corrections.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', $msg);
    }

    public function staffRollbackIndex(Request $request)
    {
        $authUser = Auth::user();
        $unitCtx = $this->resolveUnitContext($request);

        $query = StaffCaseSubmission::where('status', 'Rolled Back');

        if ($authUser && !$authUser->is_head) {
            $query->where(function ($q) use ($authUser, $unitCtx) {
                $q->where('submitted_by', $authUser->id)
                  ->orWhere('submitted_by_name', $authUser->fullname ?? $authUser->name)
                  ->orWhere('unit_name', 'LIKE', '%' . $unitCtx['sidebar'] . '%');
            });
        } else {
            $query->forUnit($unitCtx['sidebar']);
        }

        $submissions = $query->orderByDesc('rolled_back_at')->orderByDesc('updated_at')->get();

        return view('staff.case_rollback', [
            'submissions' => $submissions,
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'authUser' => $authUser,
        ]);
    }

    public function staffRollbackShow(Request $request, $id)
    {
        $authUser = Auth::user();
        $submission = StaffCaseSubmission::findOrFail($id);

        $unitCtx = $this->resolveUnitContext($request);
        $payload = is_array($submission->payload) ? $submission->payload : [];
        $corrections = is_array($submission->rollback_corrections) ? $submission->rollback_corrections : [];

        $createView = match ($unitCtx['key']) {
            'lab_analysis' => 'Lab_Analysis.staff.create',
            'evidence_management' => 'Evidence_Management.staff.create',
            'digital_forensics' => 'Digital_Forensics.staff.create',
            'forensic_head' => 'Forensic_Head.staff.create',
            default => 'Crime_Scene_Support.staff.create',
        };

        return view($createView, [
            'rollbackSubmission' => $submission,
            'isRollbackMode' => true,
            'submission' => $submission,
            'payload' => $payload,
            'corrections' => $corrections,
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'authUser' => $authUser,
        ]);
    }

    public function staffRollbackResubmit(Request $request, $id)
    {
        $authUser = Auth::user();
        $submission = StaffCaseSubmission::findOrFail($id);

        $payload = is_array($submission->payload) ? $submission->payload : [];
        $docketData = is_array($payload['docket_data'] ?? null) ? $payload['docket_data'] : [];
        $suspects = is_array($payload['suspects'] ?? ($docketData['suspects'] ?? null)) ? ($payload['suspects'] ?? $docketData['suspects']) : [];
        $firstSuspect = is_array($suspects[0] ?? null) ? $suspects[0] : [];

        // Collect inputs from the form except token and internal fields
        $updates = $request->except(['_token', 'resubmit_notes', 'corrections_acknowledged']);
        foreach ($updates as $k => $v) {
            if ($v !== null && $v !== '') {
                $payload[$k] = $v;
            }
        }

        if ($request->filled('suspect_name') || $request->filled('full_name')) {
            $nameVal = trim((string) ($request->input('suspect_name') ?: $request->input('full_name')));
            $submission->suspect_name = $nameVal;
            $payload['suspect_name'] = $nameVal;
            $payload['full_name'] = $nameVal;
            $docketData['full_name'] = $nameVal;
            $firstSuspect['full_name'] = $nameVal;
            $firstSuspect['name'] = $nameVal;
            $firstSuspect['suspect_name'] = $nameVal;
        }

        if ($request->filled('suspect_alias') || $request->filled('alias')) {
            $aliasVal = trim((string) ($request->input('suspect_alias') ?: $request->input('alias')));
            $payload['suspect_alias'] = $aliasVal;
            $payload['alias'] = $aliasVal;
            $docketData['alias'] = $aliasVal;
            $firstSuspect['alias'] = $aliasVal;
            $firstSuspect['suspect_alias'] = $aliasVal;
        }

        if ($request->filled('ghana_card') || $request->filled('id_card')) {
            $cardVal = trim((string) ($request->input('ghana_card') ?: $request->input('id_card')));
            $payload['ghana_card'] = $cardVal;
            $payload['id_card'] = $cardVal;
            $docketData['id_card'] = $cardVal;
            $firstSuspect['id_card'] = $cardVal;
            $firstSuspect['ghana_card'] = $cardVal;
        }

        foreach (['gender', 'dob', 'age', 'height', 'eye_color', 'nationality', 'pob', 'phone', 'address', 'marital_status', 'offense_description', 'raid_location', 'holding_facility', 'arrest_date', 'arrest_time', 'arresting_officer', 'supervising_officer', 'investigating_officer'] as $field) {
            if ($request->filled($field)) {
                $fVal = $request->input($field);
                $payload[$field] = $fVal;
                $docketData[$field] = $fVal;
                $firstSuspect[$field] = $fVal;
            }
        }

        if ($request->has('seizures') && is_array($request->input('seizures'))) {
            $payload['seizures'] = $request->input('seizures');
            $docketData['seizures'] = $request->input('seizures');
        }

        if (!empty($firstSuspect)) {
            $suspects[0] = $firstSuspect;
            $docketData['suspects'] = $suspects;
            $payload['suspects'] = $suspects;
        }

        $payload['docket_data'] = $docketData;

        if ($request->filled('title')) {
            $submission->title = trim((string) $request->input('title'));
            $payload['title'] = $submission->title;
        }
        if ($request->filled('priority')) {
            $submission->priority = strtoupper(trim((string) $request->input('priority')));
            $payload['priority'] = $submission->priority;
        }

        $payload['was_corrected'] = true;
        $payload['resubmitted_at'] = now()->toDateTimeString();
        $submission->payload = $payload;
        $submission->status = 'Pending Approval';
        $submission->rollback_corrections = null;
        $submission->rolled_back_at = null;
        $submission->review_notes = null;
        $submission->save();

        $unitCtx = $this->resolveUnitContext($request);
        $redirectUrl = match ($unitCtx['key']) {
            'lab_analysis' => url('/Lab_Analysis/staff/case-rollback'),
            'evidence_management' => url('/Evidence_Management/staff/case-rollback'),
            'digital_forensics' => url('/Digital_Forensics/staff/case-rollback'),
            'forensic_head' => url('/Forensic_Head/staff/case-rollback'),
            default => url('/Crime_Scene_Support/staff/case-rollback'),
        };

        $successMsg = "Case #{$submission->id} has been updated and sent to your Unit Head for review and approval.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', $successMsg);
    }
}

