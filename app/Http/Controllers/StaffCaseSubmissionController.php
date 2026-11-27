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
use Illuminate\Support\Str;

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
            if ($request->ajax() || $request->wantsJson() || $request->has('poll')) {
                return response()->json(['success' => false, 'message' => 'Only Unit Heads can access Reviews & Approvals.'], 403);
            }
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can access Reviews & Approvals.');
        }

        $unitCtx = $this->resolveUnitContext($request);
        $headUnitKey = $this->normalizeUnitKey($user->unit);
        if ($headUnitKey && $headUnitKey !== 'forensic_head' && $headUnitKey !== $unitCtx['key']) {
            if ($request->ajax() || $request->wantsJson() || $request->has('poll')) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to review submissions for another unit.'], 403);
            }
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
            ->get();

        $urgentPendingCount = $pendingSubmissions->filter(function ($s) {
            return \App\Models\ForensicCase::normalizePriority($s->priority, 'LOW') === 'HIGH';
        })->count();
        $awaitingReviewCount = $pendingSubmissions->where('status', 'Pending Approval')->count();
        $rolledBackCount = $pendingSubmissions->where('status', 'Rolled Back')->count();
        $approvedHistoryCount = $reviewedSubmissions->where('status', 'Approved')->count();

        $unitKey = $unitCtx['key'] ?? 'crime_scene_support';
        $reviewUrlFor = function ($id) use ($unitKey) {
            return match ($unitKey) {
                'lab_analysis' => url('/Lab_Analysis/reviews-approvals/' . $id),
                'evidence_management' => url('/Evidence_Management/reviews-approvals/' . $id),
                'digital_forensics' => url('/Digital_Forensics/reviews-approvals/' . $id),
                'forensic_head' => url('/Forensic_Head/reviews-approvals/' . $id),
                default => url('/Crime_Scene_Support/reviews-approvals/' . $id),
            };
        };
        $extractSubmissionMeta = fn($sub) => $this->extractSubmissionMeta($sub);

        if ($request->ajax() || $request->wantsJson() || $request->has('poll')) {
            $pendingHtml = view('head.partials.pending_queue', [
                'unitContext'           => $unitCtx,
                'unitKey'               => $unitKey,
                'unitLabel'             => $unitCtx['label'] ?? 'Crime Scene Support Unit',
                'pendingSubmissions'    => $pendingSubmissions,
                'reviewUrlFor'          => $reviewUrlFor,
                'extractSubmissionMeta' => $extractSubmissionMeta,
                'awaitingReviewCount'   => $awaitingReviewCount,
                'rolledBackCount'       => $rolledBackCount,
                'urgentPendingCount'    => $urgentPendingCount,
            ])->render();

            return response()->json([
                'success' => true,
                'counts' => [
                    'pending_total'     => $pendingSubmissions->count(),
                    'awaiting_review'   => $awaitingReviewCount,
                    'rolled_back'       => $rolledBackCount,
                    'urgent'            => $urgentPendingCount,
                    'approved_history'  => $approvedHistoryCount,
                    'edited_cases'      => $pendingSubmissions->filter(fn($s) => !empty($s->payload['is_edit_submission']) || (($s->payload['submission_type'] ?? '') === 'edit'))->count(),
                ],
                'html' => $pendingHtml,
                'signature' => md5($pendingHtml . '|' . $pendingSubmissions->count() . '|' . $rolledBackCount . '|' . $awaitingReviewCount),
            ]);
        }

        return view('head.reviews_approvals', [
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'pendingSubmissions' => $pendingSubmissions,
            'reviewedSubmissions' => $reviewedSubmissions,
            'reviewUrlFor' => $reviewUrlFor,
            'extractSubmissionMeta' => $extractSubmissionMeta,
        ]);
    }

    /**
     * Extract structured meta information for displaying this submission in queue cards and tables.
     */
    public function extractSubmissionMeta(StaffCaseSubmission $sub): array
    {
        return $sub->getSubmissionMeta();
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

        // If submission was already approved, link to its persisted case record
        $realApprovedCase = null;
        if (!empty($submission->approved_case_number)) {
            $realApprovedCase = \App\Models\SingleSuspectCase::where('case_number', $submission->approved_case_number)->first()
                ?? \App\Models\MultipleSuspectCase::where('case_number', $submission->approved_case_number)->first();
        }

        if ($realApprovedCase) {
            $case = ForensicCase::wrapRecord($realApprovedCase);
        } else {
            // Build a virtual ForensicCase instance populated strictly from submission payload
            $case = new ForensicCase();
            $case->is_staff_submission = true;
            $case->submission_id = $submission->id;
            $case->id = null; // Staged submission: Must remain null to prevent collision with SingleSuspectCase/MultipleSuspectCase IDs
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
            $case->priority = ForensicCase::normalizePriority($payload['priority'] ?? $submission->priority, 'LOW');
            $case->suspect_name = $submission->suspect_name ?? ($payload['suspect_name'] ?? null);
            $case->ghana_card = $payload['ghana_card'] ?? null;
            $case->passport_number = $payload['passport_number'] ?? null;
            $case->flight_number = $payload['flight_number'] ?? null;
            $case->destination = $payload['destination'] ?? null;
            $case->substance_expelled = $payload['substance_expelled'] ?? null;
            $case->location = $payload['location'] ?? ($payload['raid_location'] ?? null);
            $case->description = $payload['description'] ?? ($payload['offense_description'] ?? null);
            $case->arresting_officer = $payload['arresting_officer'] ?? null;
            $case->investigating_officer = $payload['investigating_officer'] ?? null;
            $case->submitting_officer = $payload['submitting_officer'] ?? ($submission->submitted_by_name ?? null);
            $case->created_at = $submission->created_at ?? now();
            $case->updated_at = $submission->updated_at ?? now();

            $docketData = is_array($payload['docket_data'] ?? null) ? $payload['docket_data'] : [];
            $docketSuspect0 = $docketData['suspects'][0] ?? ($payload['suspects'][0] ?? []);

            $case->gender = $payload['gender'] ?? ($docketSuspect0['gender'] ?? ($docketData['gender'] ?? null));
            $case->dob = $payload['dob'] ?? ($docketSuspect0['dob'] ?? ($docketData['dob'] ?? null));
            $case->age = $payload['age'] ?? ($docketSuspect0['age'] ?? ($docketData['age'] ?? null));
            $case->height = $payload['height'] ?? ($docketSuspect0['height'] ?? ($docketData['height'] ?? null));
            $case->height_unit = $payload['height_unit'] ?? ($docketSuspect0['height_unit'] ?? ($docketData['height_unit'] ?? null));
            $case->eye_color = $payload['eye_color'] ?? ($docketSuspect0['eye_color'] ?? ($docketData['eye_color'] ?? null));
            $case->nationality = $payload['nationality'] ?? ($docketSuspect0['nationality'] ?? ($docketData['nationality'] ?? null));
            $case->pob = $payload['pob'] ?? ($docketSuspect0['pob'] ?? ($docketData['pob'] ?? null));
            $case->phone = $payload['phone'] ?? ($docketSuspect0['phone'] ?? ($docketData['phone'] ?? null));
            $case->address = $payload['address'] ?? ($docketSuspect0['address'] ?? ($docketData['address'] ?? null));
            $case->marital_status = $payload['marital_status'] ?? ($docketSuspect0['marital_status'] ?? ($docketData['marital_status'] ?? null));
            $case->has_children = $payload['has_children'] ?? ($docketSuspect0['has_children'] ?? ($docketData['has_children'] ?? null));
            $case->children_count = $payload['children_count'] ?? ($docketSuspect0['children_count'] ?? ($docketData['children_count'] ?? null));
            $case->dependents = $payload['dependents'] ?? ($docketSuspect0['dependents'] ?? ($docketData['dependents'] ?? null));
            $case->spouse_name = $payload['spouse_name'] ?? ($docketSuspect0['spouse_name'] ?? ($docketData['spouse_name'] ?? null));
            $case->spouse_phone = $payload['spouse_phone'] ?? ($docketSuspect0['spouse_phone'] ?? ($docketData['spouse_phone'] ?? null));
            $case->spouse_info = $payload['spouse_info'] ?? ($docketSuspect0['spouse_info'] ?? ($docketData['spouse_info'] ?? null));
            $case->partner_name = $payload['partner_name'] ?? ($docketSuspect0['partner_name'] ?? ($docketData['partner_name'] ?? null));
            $case->partner_phone = $payload['partner_phone'] ?? ($docketSuspect0['partner_phone'] ?? ($docketData['partner_phone'] ?? null));
            $case->partner_info = $payload['partner_info'] ?? ($docketSuspect0['partner_info'] ?? ($docketData['partner_info'] ?? null));
            $case->arrest_date = $payload['arrest_date'] ?? ($docketSuspect0['arrest_date'] ?? ($docketData['arrest_date'] ?? null));
            $case->arrest_time = $payload['arrest_time'] ?? ($docketSuspect0['arrest_time'] ?? ($docketData['arrest_time'] ?? null));
            $case->holding_facility = $payload['holding_facility'] ?? ($docketSuspect0['holding_facility'] ?? ($docketData['holding_facility'] ?? null));
            $case->raid_location = $payload['raid_location'] ?? ($payload['location'] ?? ($docketSuspect0['raid_location'] ?? ($docketData['raid_location'] ?? null)));

            $caseNumCandidate = $submission->approved_case_number ?? ($submission->case_number_preview ?? ($payload['case_number'] ?? null));
            $matchedCase = null;
            if ($caseNumCandidate) {
                $matchedCase = \App\Models\SingleSuspectCase::where('case_number', $caseNumCandidate)->first()
                    ?? \App\Models\ForensicCase::where('case_number', $caseNumCandidate)->first();
            }

            $pickMug = static function (...$candidates): ?string {
                foreach ($candidates as $c) {
                    if ($c !== null && $c !== false) {
                        $s = trim((string) $c);
                        if ($s !== '' && $s !== 'null' && $s !== 'undefined' && $s !== '#' && !str_ends_with($s, '#') && !str_contains($s, 'blank')) {
                            return $s;
                        }
                    }
                }
                return null;
            };

            // Populate biometric mugshots from submission payload or docket data with fallback to matched case
            $case->mugshot_front = $pickMug(
                $payload['mugshot_front'] ?? null,
                $docketSuspect0['mugshot_front'] ?? null,
                $docketSuspect0['photo'] ?? null,
                $docketSuspect0['mugshots']['front'] ?? null,
                $docketSuspect0['mugshots']['front-profile'] ?? null,
                $payload['docket_data']['mugshot_front'] ?? null,
                $payload['docket_data']['photo'] ?? null,
                $payload['docket_data']['suspects'][0]['mugshot_front'] ?? null,
                $payload['docket_data']['suspects'][0]['photo'] ?? null,
                $matchedCase?->mugshot_front ?? null,
                $matchedCase?->photo ?? null
            );
            $case->mugshot_left = $pickMug(
                $payload['mugshot_left'] ?? null,
                $payload['mugshot_side'] ?? null,
                $docketSuspect0['mugshot_left'] ?? null,
                $docketSuspect0['mugshots']['left'] ?? null,
                $docketSuspect0['mugshots']['left-profile'] ?? null,
                $payload['docket_data']['mugshot_left'] ?? null,
                $payload['docket_data']['suspects'][0]['mugshot_left'] ?? null,
                $matchedCase?->mugshot_left ?? null
            );
            $case->mugshot_right = $pickMug(
                $payload['mugshot_right'] ?? null,
                $docketSuspect0['mugshot_right'] ?? null,
                $docketSuspect0['mugshots']['right'] ?? null,
                $docketSuspect0['mugshots']['right-profile'] ?? null,
                $payload['docket_data']['mugshot_right'] ?? null,
                $payload['docket_data']['suspects'][0]['mugshot_right'] ?? null,
                $matchedCase?->mugshot_right ?? null
            );
            $case->mugshot_back = $pickMug($payload['mugshot_back'] ?? null);

            $docketData = is_array($payload['docket_data'] ?? null) ? $payload['docket_data'] : [];
            $docketData['created_by_name'] = $submission->submitted_by_name ?? ($submitterUser?->fullname ?? 'Staff Officer');
            $docketData['created_by_unit'] = $submission->unit_name ?? $unitCtx['sidebar'];
            if (empty($docketData['mugshot_front']) && !empty($case->mugshot_front)) {
                $docketData['mugshot_front'] = $case->mugshot_front;
            }
            if (empty($docketData['mugshot_left']) && !empty($case->mugshot_left)) {
                $docketData['mugshot_left'] = $case->mugshot_left;
            }
            if (empty($docketData['mugshot_right']) && !empty($case->mugshot_right)) {
                $docketData['mugshot_right'] = $case->mugshot_right;
            }
            if (!empty($docketData['suspects'][0])) {
                if (empty($docketData['suspects'][0]['mugshot_front']) && !empty($case->mugshot_front)) {
                    $docketData['suspects'][0]['mugshot_front'] = $case->mugshot_front;
                    $docketData['suspects'][0]['mugshots']['front'] = $case->mugshot_front;
                }
                if (empty($docketData['suspects'][0]['mugshot_left']) && !empty($case->mugshot_left)) {
                    $docketData['suspects'][0]['mugshot_left'] = $case->mugshot_left;
                    $docketData['suspects'][0]['mugshots']['left'] = $case->mugshot_left;
                }
                if (empty($docketData['suspects'][0]['mugshot_right']) && !empty($case->mugshot_right)) {
                    $docketData['suspects'][0]['mugshot_right'] = $case->mugshot_right;
                    $docketData['suspects'][0]['mugshots']['right'] = $case->mugshot_right;
                }
            }
            $case->docket_data = $docketData;

            $case->seizures = (is_array($payload['seizures'] ?? null) && !empty($payload['seizures']))
                ? $payload['seizures']
                : ($docketData['seizures'] ?? []);
            $case->search_warrant_document = $payload['search_warrant_document'] ?? null;
            $case->description_of_exhibits = $payload['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null);
            $case->laboratory_number = $payload['laboratory_number'] ?? ($docketData['lab_examination']['laboratory_number'] ?? null);
            $case->lab_exam_reference = $payload['lab_exam_reference'] ?? ($payload['exam_reference'] ?? ($docketData['lab_examination']['exam_reference'] ?? null));
            $case->exhibit_category = $payload['exhibit_category'] ?? null;
            $case->seizure_name_drug = $payload['seizure_name_drug'] ?? null;
            $case->seizure_name = $payload['seizure_name'] ?? null;
        }

        // Map unit key for CaseShowReviewPresenter: css, lab, dpf, em, hof
        $presenterKey = match ($unitCtx['key']) {
            'lab_analysis' => 'lab',
            'digital_forensics' => 'dpf',
            'evidence_management' => 'em',
            'forensic_head' => 'hof',
            default => 'css',
        };

        $presenter = app(CaseShowReviewPresenter::class)->presentForUnit($case, $presenterKey, $request, $user);

        $isEditSubmission = (!empty($payload['submission_type']) && $payload['submission_type'] === 'edit')
            || !empty($payload['is_edit_submission']);

        return view('head.submission_review', array_merge($presenter->toViewData(), [
            'submission'            => $submission,
            'case'                  => $case,
            'presenter'             => $presenter,
            'unitContext'           => $unitCtx,
            'sidebarUnit'           => $unitCtx['sidebar'],
            'payload'               => $payload,
            'isEditSubmission'      => $isEditSubmission,
            'editedFields'          => $payload['edited_fields'] ?? [],
            'editedFieldLabels'     => $payload['edited_field_labels'] ?? [],
            'firstEditedSelector'   => $payload['first_edited_selector'] ?? null,
            'firstEditedField'      => $payload['first_edited_field'] ?? null,
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

        $isEditSubmission = (!empty($payload['submission_type']) && $payload['submission_type'] === 'edit')
            || !empty($payload['is_edit_submission']);

        if ($isEditSubmission) {
            $caseNumber = $submission->case_number_preview ?? ($payload['original_case_number'] ?? null);
            $targetCase = null;
            if ($caseNumber) {
                $targetCase = \App\Models\SingleSuspectCase::where('case_number', $caseNumber)->first()
                    ?? \App\Models\MultipleSuspectCase::where('case_number', $caseNumber)->first()
                    ?? \App\Models\ForensicCase::where('case_number', $caseNumber)->first();
            }

            if (!$targetCase) {
                return back()->with('error', "Original case {$caseNumber} could not be located to apply edits.");
            }

            $replayRequest = Request::create('/cases/' . $caseNumber . '/update', 'POST', $payload);
            $replayRequest->setUserResolver(fn () => $head);

            $wrappedCase = $targetCase instanceof \App\Models\ForensicCase
                ? $targetCase
                : \App\Models\ForensicCase::wrapRecord($targetCase);

            $result = app(CaseDocketService::class)->updateCase($wrappedCase, $replayRequest);
            if (empty($result['success'])) {
                return back()->with('error', $result['message'] ?? 'Failed to apply approved edits to case.');
            }

            $submission->update([
                'status' => 'Approved',
                'reviewed_by' => $head->id,
                'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
                'reviewed_at' => now(),
                'review_notes' => $request->input('review_notes'),
                'approved_case_number' => $caseNumber,
            ]);

            \App\Services\CaseEditHistoryService::recordEditAudit($wrappedCase, [
                'edit_mode'  => 'staff_approved',
                'user'       => $head,
                'staff_name' => $submission->submitted_by_name ?? ($payload['staff_editor_name'] ?? 'Staff Officer'),
                'head_name'  => $head->fullname ?? $head->name ?? 'Unit Head',
                'changes'    => !empty($payload['changes']) ? $payload['changes'] : [],
            ]);

            $targetUnitKey = $this->normalizeUnitKey($submission->unit_name) ?: ($this->normalizeUnitKey($head->unit) ?: 'crime_scene_support');
            $redirectUrl = match ($targetUnitKey) {
                'lab_analysis' => url('/Lab_Analysis/reviews-approvals'),
                'evidence_management' => url('/Evidence_Management/reviews-approvals'),
                'digital_forensics' => url('/Digital_Forensics/reviews-approvals'),
                'forensic_head' => url('/Forensic_Head/reviews-approvals'),
                default => url('/Crime_Scene_Support/reviews-approvals'),
            };

            $msg = "Edit submission #{$submission->id} for Case {$caseNumber} approved and saved to database.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('status', $msg);
        }

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

        if (empty($corrections)) {
            $validationMsg = 'Please select at least one section for correction before sending a rollback to staff.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validationMsg,
                ], 422);
            }
            return back()->with('warning', $validationMsg);
        }

        $reviewNotes = trim((string) $request->input('review_notes'));
        if (empty($reviewNotes)) {
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

        $isEditSubmission = (!empty($payload['submission_type']) && $payload['submission_type'] === 'edit')
            || !empty($payload['is_edit_submission']);

        if ($isEditSubmission && !empty($submission->case_number_preview)) {
            return redirect('/cases/' . $submission->case_number_preview . '/edit?rollback_id=' . $submission->id);
        }

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

        foreach ([
            'gender', 'dob', 'age', 'height', 'eye_color', 'nationality', 'pob', 'phone', 'address',
            'passport_number', 'flight_number', 'destination', 'substance_expelled',
            'marital_status', 'children_count',
            'spouse_name', 'spouse_phone', 'spouse_info',
            'partner_name', 'partner_phone', 'partner_info',
            'offense_description', 'raid_location', 'holding_facility',
            'arrest_date', 'arrest_time', 'arresting_officer', 'supervising_officer', 'investigating_officer'
        ] as $field) {
            if ($request->has($field) && $request->input($field) !== null && $request->input($field) !== '') {
                $fVal = $request->input($field);
                $payload[$field] = $fVal;
                $docketData[$field] = $fVal;
                $firstSuspect[$field] = $fVal;
            }
        }

        if ($request->has('has_children') || $request->has('children_count') || $request->has('dependents')) {
            $rawKids = $request->input('has_children');
            $rawCount = $request->input('children_count');
            $childCount = ($rawCount !== null && $rawCount !== '') ? (int) $rawCount : (int) ($payload['children_count'] ?? 0);
            $hasKids = filter_var($rawKids, FILTER_VALIDATE_BOOLEAN) || $rawKids === '1' || $rawKids === 1 || $childCount > 0;
            $payload['has_children'] = $hasKids;
            $docketData['has_children'] = $hasKids;
            $firstSuspect['has_children'] = $hasKids;
            $finalCount = $hasKids ? ($childCount > 0 ? $childCount : 1) : 0;
            $payload['children_count'] = $finalCount;
            $docketData['children_count'] = $finalCount;
            $firstSuspect['children_count'] = $finalCount;

            $depText = $hasKids ? ($finalCount . ($finalCount === 1 ? ' Kid' : ' Kids')) : 'No Children';
            $payload['dependents'] = $depText;
            $docketData['dependents'] = $depText;
            $firstSuspect['dependents'] = $depText;
        }

        foreach (['mugshot_front', 'mugshot_left', 'mugshot_right'] as $mugKey) {
            $mVal = null;
            if ($request->hasFile($mugKey)) {
                $file = $request->file($mugKey);
                if ($file && $file->isValid()) {
                    $yearMonth = date('Y/m');
                    $cNum = $submission->case_number_preview ?? ($payload['case_number'] ?? 'CASE');
                    $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $cNum) . '_' . $mugKey . '_s0_' . strtolower(Str::random(10)) . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
                    $stored = $file->storeAs("suspects/{$yearMonth}", $filename, 'public');
                    if ($stored) {
                        $mVal = "suspects/{$yearMonth}/{$filename}";
                    }
                }
            } elseif ($request->filled($mugKey)) {
                $rawInput = trim((string) $request->input($mugKey));
                if ($rawInput !== '' && $rawInput !== 'null' && $rawInput !== 'undefined' && !str_ends_with($rawInput, '#')) {
                    $mVal = $rawInput;
                }
            }

            if ($mVal !== null) {
                $payload[$mugKey] = $mVal;
                $docketData[$mugKey] = $mVal;
                $firstSuspect[$mugKey] = $mVal;
                $angle = str_replace('mugshot_', '', $mugKey);
                if (!isset($firstSuspect['mugshots']) || !is_array($firstSuspect['mugshots'])) {
                    $firstSuspect['mugshots'] = [];
                }
                $firstSuspect['mugshots'][$angle] = $mVal;
            }
        }

        if ($request->has('seizures') && is_array($request->input('seizures'))) {
            $payload['seizures'] = $request->input('seizures');
            $docketData['seizures'] = $request->input('seizures');
        }

        $labKeys = [
            'lab_workflow_mode', 'section5_completed', 'lab_exam_reference', 'exam_reference',
            'lab_case_reference', 'lab_exhibit_number', 'lab_exhibit_no', 'lab_exhibit_description',
            'exhibit_description', 'laboratory_number', 'lab_analyst', 'lab_date_received',
            'lab_date_started', 'lab_date_completed', 'purpose_of_examination', 'preliminary_exam_procedure',
            'screening_method', 'confirmatory_test_methods', 'test_methods', 'confirmatory_specifications',
            'specifications', 'confirmatory_remark', 'remark', 'disposition_of_exhibit',
            'gsa_lab_number', 'gsa_case_reference', 'gsa_status', 'exhibit_type', 'lab_exhibit_nos',
            'lab_examination'
        ];
        foreach ($labKeys as $lKey) {
            if ($request->has($lKey)) {
                $payload[$lKey] = $request->input($lKey);
                $docketData[$lKey] = $request->input($lKey);
            }
        }
        if (isset($payload['lab_examination']) && is_array($payload['lab_examination'])) {
            $docketData['lab_examination'] = $payload['lab_examination'];
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
            $submission->priority = ForensicCase::normalizePriority($request->input('priority'));
            $payload['priority'] = $submission->priority;
        }

        $payload['was_corrected'] = true;
        $payload['resubmitted_at'] = now()->toDateTimeString();
        $payload = app(\App\Services\MediaStorageService::class)->processPayloadImages(
            $payload,
            $submission->case_number_preview ?? ('SUB-' . $submission->id)
        );

        // Deduplicate and sync processed mugshot paths across docket_data and suspects
        if (isset($payload['docket_data']) && is_array($payload['docket_data'])) {
            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right'] as $mugKey) {
                if (!empty($payload[$mugKey])) {
                    $payload['docket_data'][$mugKey] = $payload[$mugKey];
                    if (isset($payload['docket_data']['suspects'][0]) && is_array($payload['docket_data']['suspects'][0])) {
                        $payload['docket_data']['suspects'][0][$mugKey] = $payload[$mugKey];
                        $angle = str_replace('mugshot_', '', $mugKey);
                        if (!isset($payload['docket_data']['suspects'][0]['mugshots']) || !is_array($payload['docket_data']['suspects'][0]['mugshots'])) {
                            $payload['docket_data']['suspects'][0]['mugshots'] = [];
                        }
                        $payload['docket_data']['suspects'][0]['mugshots'][$angle] = $payload[$mugKey];
                    }
                }
            }
        }
        if (isset($payload['suspects'][0]) && is_array($payload['suspects'][0])) {
            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right'] as $mugKey) {
                if (!empty($payload[$mugKey])) {
                    $payload['suspects'][0][$mugKey] = $payload[$mugKey];
                    $angle = str_replace('mugshot_', '', $mugKey);
                    if (!isset($payload['suspects'][0]['mugshots']) || !is_array($payload['suspects'][0]['mugshots'])) {
                        $payload['suspects'][0]['mugshots'] = [];
                    }
                    $payload['suspects'][0]['mugshots'][$angle] = $payload[$mugKey];
                }
            }
        }
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

