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
        $authUser = auth()->user();
        $isDG = ($authUser && ($authUser->unit === 'Office of the Director-General' || $authUser->unit === 'Director General' || str_contains(strtolower((string) $authUser->unit), 'director general') || str_contains(strtolower((string) $authUser->unit), 'director-general')))
            || in_array(strtolower((string) $request->input('from')), ['dg', 'director general', 'director-general', 'office of the director-general']);

        if ($isDG) {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Director-General is not permitted to create cases. Cases originate from operational units.'], 403)
                : redirect('/DG/cases')->with('error', 'Director-General is not permitted to create cases. Cases originate from operational units.');
        }

        if ($request->input('from') === 'Forensic_Head' || $request->input('from') === 'Forensic Head') {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Directorate Head is not permitted to create cases.'], 403)
                : redirect('/Forensic_Head/cases')->with('error', 'Directorate Head is not permitted to create cases.');
        }

        if ($request->filled('rollback_id')) {
            return app(\App\Http\Controllers\StaffCaseSubmissionController::class)->staffRollbackResubmit($request, $request->input('rollback_id'));
        }
        if ($authUser && !$authUser->is_head && !$request->boolean('approved_by_head')) {
            if ($validationError = $this->validateStaffSubmissionPayload($request)) {
                return $validationError;
            }

            $rawInput = $request->except(['_token']);
            if ($request->hasFile('search_warrant_document')) {
                $warrantFile = $request->file('search_warrant_document');
                if ($warrantFile && $warrantFile->isValid()) {
                    $rawInput['search_warrant_document_path'] = $warrantFile->store('search_warrants', 'public');
                }
            }
            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'mugshot_back'] as $mugKey) {
                if ($request->hasFile($mugKey)) {
                    $mFile = $request->file($mugKey);
                    if ($mFile && $mFile->isValid()) {
                        $rawInput[$mugKey] = $mFile->store('suspects', 'public');
                    }
                }
            }

            $payload = $this->stripUploadedFiles($rawInput);
            $payload = app(\App\Services\MediaStorageService::class)->processPayloadImages(
                $payload,
                $request->input('case_number') ?: ('SUB-' . ($authUser->id ?? 'staff'))
            );
            $unitName = trim((string) ($authUser->unit ?: $request->input('unit_name') ?: $request->input('unit') ?: 'Crime Scene Support'));
            $suspectName = trim((string) ($request->input('suspect_name') ?: $request->input('full_name') ?: $request->input('title') ?: 'Pending Suspect'));
            $title = trim((string) ($request->input('title') ?: $request->input('offense_type') ?: $request->input('operation_type') ?: 'Staff Case Submission'));
            $priority = \App\Models\ForensicCase::normalizePriority($request->input('priority'), 'HIGH');

            $submission = \App\Models\StaffCaseSubmission::create([
                'submitted_by' => $authUser->id,
                'submitted_by_name' => $authUser->fullname ?? $authUser->name ?? 'Staff Officer',
                'unit_name' => $unitName,
                'case_number_preview' => $request->input('case_number') ?: null,
                'title' => $title,
                'suspect_name' => $suspectName,
                'priority' => $priority,
                'status' => 'Pending Approval',
                'payload' => $payload,
            ]);

            $msg = "Case submitted for Unit Head review and approval before saving to database (Submission #{$submission->id}).";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'pending_approval' => true,
                    'submission_id' => $submission->id,
                    'message' => $msg,
                    'case' => [
                        'id' => $submission->id,
                        'case_number' => 'PENDING-' . $submission->id,
                    ],
                ], 201);
            }

            return back()->with('status', $msg);
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

        if (method_exists($case, 'isPendingApproval') ? $case->isPendingApproval() : false) {
            $isRollbackAccess = false;
            if (request()->filled('rollback_id')) {
                $sub = \App\Models\StaffCaseSubmission::find(request()->input('rollback_id'));
                if ($sub && $sub->status === 'Rolled Back') {
                    $isRollbackAccess = true;
                }
            } elseif (!empty($case->case_number)) {
                $hasRollback = \App\Models\StaffCaseSubmission::where('case_number_preview', $case->case_number)
                    ->where('status', 'Rolled Back')
                    ->exists();
                if ($hasRollback) {
                    $isRollbackAccess = true;
                }
            }

            if (!$isRollbackAccess) {
                $from = request('from') ?: ($case->unit_name ?: '');
                $targetUrl = !empty($from) ? "/cases/{$id}/review?from=" . urlencode($from) : "/cases/{$id}/review";
                return redirect($targetUrl)
                    ->with('error', 'This case is currently awaiting review and approval from the Head of Unit. Case editing is locked.');
            }
        }

        $this->repairExhibitAssociations($case);
        return view('cases.edit', compact('case'));
    }

    /**
     * Detect fields edited in request compared against existing case values.
     */
    public function detectEditedFields(ForensicCase $case, Request $request): array
    {
        $edited = [];
        $labels = [];
        $changes = [];
        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
        $docket0 = $docketData['suspects'][0] ?? [];

        $fieldMappings = [
            'title' => [
                'label' => 'Case Legal Title',
                'current' => $case->title,
                'input' => $request->input('title'),
                'selector' => '[data-field-group="title"]',
            ],
            'priority' => [
                'label' => 'Case Priority',
                'current' => $case->priority,
                'input' => $request->input('priority'),
                'selector' => '[data-field-group="title"]',
            ],
            'suspect_name' => [
                'label' => 'Suspect Full Name',
                'current' => $case->suspect_name ?? ($docket0['suspect_name'] ?? null),
                'input' => $request->input('suspect_name') ?: $request->input('full_name'),
                'selector' => '[data-field-group="suspect_name"]',
            ],
            'suspect_alias' => [
                'label' => 'Suspect Alias / Street Name',
                'current' => $case->suspect_alias ?? ($docket0['alias'] ?? null),
                'input' => $request->input('suspect_alias') ?: $request->input('alias'),
                'selector' => '[data-field-group="suspect_alias"]',
            ],
            'ghana_card' => [
                'label' => 'Ghana Card Number',
                'current' => $case->ghana_card ?? ($docket0['ghana_card'] ?? null),
                'input' => $request->input('ghana_card') ?: $request->input('id_card'),
                'selector' => '[data-field-group="ghana_card"]',
            ],
            'gender' => [
                'label' => 'Gender',
                'current' => $case->gender ?? ($docket0['gender'] ?? null),
                'input' => $request->input('gender'),
                'selector' => '[data-field-group="gender"]',
            ],
            'dob' => [
                'label' => 'Date of Birth',
                'current' => $case->dob ?? ($docket0['dob'] ?? null),
                'input' => $request->input('dob'),
                'selector' => '[data-field-group="dob"]',
            ],
            'age' => [
                'label' => 'Age',
                'current' => $case->age ?? ($docket0['age'] ?? null),
                'input' => $request->input('age'),
                'selector' => '[data-field-group="age"]',
            ],
            'height' => [
                'label' => 'Height',
                'current' => $case->height ?? ($docket0['height'] ?? null),
                'input' => $request->input('height'),
                'selector' => '[data-field-group="height"]',
            ],
            'eye_color' => [
                'label' => 'Eye Color',
                'current' => $case->eye_color ?? ($docket0['eye_color'] ?? null),
                'input' => $request->input('eye_color'),
                'selector' => '[data-field-group="eye_color"]',
            ],
            'nationality' => [
                'label' => 'Nationality',
                'current' => $case->nationality ?? ($docket0['nationality'] ?? null),
                'input' => $request->input('nationality'),
                'selector' => '[data-field-group="nationality"]',
            ],
            'pob' => [
                'label' => 'Place of Birth',
                'current' => $case->pob ?? ($docket0['pob'] ?? null),
                'input' => $request->input('pob'),
                'selector' => '[data-field-group="pob"]',
            ],
            'phone' => [
                'label' => 'Phone Number',
                'current' => $case->phone ?? ($docket0['phone'] ?? null),
                'input' => $request->input('phone'),
                'selector' => '[data-field-group="phone"]',
            ],
            'address' => [
                'label' => 'Residential Address',
                'current' => $case->address ?? ($docket0['address'] ?? null),
                'input' => $request->input('address'),
                'selector' => '[data-field-group="address"]',
            ],
            'marital_status' => [
                'label' => 'Marital Status',
                'current' => $case->marital_status ?? ($docket0['marital_status'] ?? null),
                'input' => $request->input('marital_status'),
                'selector' => '[data-field-group="marital_status"]',
            ],
            'children' => [
                'label' => 'Children & Dependents',
                'current' => $case->children_count ?? ($docket0['children_count'] ?? null),
                'input' => $request->input('children_count') ?: ($request->has('has_children') ? $request->input('has_children') : null),
                'selector' => '[data-field-group="children"]',
            ],
            'spouse_name' => [
                'label' => 'Spouse / Partner Name',
                'current' => $case->spouse_name ?? ($docket0['spouse_name'] ?? null),
                'input' => $request->input('spouse_name') ?: $request->input('partner_name'),
                'selector' => '[data-field-group="spouse_name"]',
            ],
            'spouse_phone' => [
                'label' => 'Spouse / Partner Phone',
                'current' => $case->spouse_phone ?? ($docket0['spouse_phone'] ?? null),
                'input' => $request->input('spouse_phone') ?: $request->input('partner_phone'),
                'selector' => '[data-field-group="spouse_phone"]',
            ],
            'arrest_date' => [
                'label' => 'Arrest Date',
                'current' => $case->arrest_date ?? ($docket0['arrest_date'] ?? null),
                'input' => $request->input('arrest_date'),
                'selector' => '[data-field-group="arrest_date"]',
            ],
            'arrest_time' => [
                'label' => 'Arrest Time',
                'current' => $case->arrest_time ?? ($docket0['arrest_time'] ?? null),
                'input' => $request->input('arrest_time'),
                'selector' => '[data-field-group="arrest_time"]',
            ],
            'holding_facility' => [
                'label' => 'Holding Facility',
                'current' => $case->holding_facility ?? ($docket0['holding_facility'] ?? null),
                'input' => $request->input('holding_facility'),
                'selector' => '[data-field-group="holding_facility"]',
            ],
            'raid_location' => [
                'label' => 'Raid / Incident Location',
                'current' => $case->raid_location ?? ($case->location ?? null),
                'input' => $request->input('raid_location') ?: $request->input('location'),
                'selector' => '[data-field-group="raid_location"]',
            ],
            'offense_description' => [
                'label' => 'Offense Description',
                'current' => $case->description ?? ($case->offense_description ?? null),
                'input' => $request->input('offense_description') ?: $request->input('description'),
                'selector' => '[data-field-group="offense_description"]',
            ],
            'arresting_officer' => [
                'label' => 'Arresting Officer',
                'current' => $case->arresting_officer ?? null,
                'input' => $request->input('arresting_officer'),
                'selector' => '[data-field-group="arresting_officer"]',
            ],
        ];

        $firstSelector = null;
        $firstField = null;

        foreach ($fieldMappings as $key => $def) {
            if ($request->has($key) || ($def['input'] !== null)) {
                $curr = trim((string) ($def['current'] ?? ''));
                $new = trim((string) ($def['input'] ?? ''));
                if ($new !== '' && $curr !== $new) {
                    $edited[] = $key;
                    $labels[] = $def['label'];
                    $changes[] = [
                        'field' => $key,
                        'label' => $def['label'],
                        'previous' => $curr ?: 'None / Empty',
                        'current' => $new,
                    ];
                    if (!$firstSelector) {
                        $firstSelector = $def['selector'];
                        $firstField = $key;
                    }
                }
            }
        }

        // Check mugshots
        foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'mugshot_back'] as $mug) {
            if ($request->hasFile($mug) || ($request->filled($mug) && $request->input($mug) !== ($case->{$mug} ?? null))) {
                if (!in_array('mugshots', $edited, true)) {
                    $edited[] = 'mugshots';
                    $labels[] = 'Biometric Mugshots';
                    $changes[] = [
                        'field' => 'mugshots',
                        'label' => 'Biometric Mugshots',
                        'previous' => !empty($case->{$mug}) ? 'Existing photo' : 'None / Empty',
                        'current' => 'Updated photo',
                    ];
                    if (!$firstSelector) {
                        $firstSelector = '[data-field-group="mugshots"]';
                        $firstField = 'mugshots';
                    }
                }
            }
        }

        // Check exhibits / seizures
        if ($request->has('seizures') || $request->has('exhibits') || $request->has('suspectsDataJson') || $request->has('suspects_data_json')) {
            $edited[] = 'seizures';
            $labels[] = 'Associated Seized Exhibits';
            $changes[] = [
                'field' => 'seizures',
                'label' => 'Associated Seized Exhibits',
                'previous' => 'Original exhibits register',
                'current' => 'Updated exhibits register',
            ];
            if (!$firstSelector) {
                $firstSelector = '[data-field-group="seizures"]';
                $firstField = 'seizures';
            }
        }

        // Check lab examination
        if ($request->filled('laboratory_number') || $request->filled('purpose_of_examination') || $request->filled('lab_exam_reference')) {
            $edited[] = 'lab_examination';
            $labels[] = 'Laboratory Examination';
            $changes[] = [
                'field' => 'lab_examination',
                'label' => 'Laboratory Examination',
                'previous' => 'Original examination notes',
                'current' => 'Updated examination notes',
            ];
            if (!$firstSelector) {
                $firstSelector = '[data-field-group="lab_examination"]';
                $firstField = 'lab_examination';
            }
        }

        if (empty($edited)) {
            $edited[] = 'general_docket';
            $labels[] = 'Docket Information';
            $changes[] = [
                'field' => 'general_docket',
                'label' => 'Docket Information',
                'previous' => 'Original docket data',
                'current' => 'Updated docket data',
            ];
            $firstSelector = '[data-field-group="title"]';
            $firstField = 'title';
        }

        return [
            'edited_fields' => array_values(array_unique($edited)),
            'edited_field_labels' => array_values(array_unique($labels)),
            'changes' => $changes,
            'first_edited_selector' => $firstSelector,
            'first_edited_field' => $firstField,
        ];
    }

    /**
     * Update case data and correct mistakes with strict Unit RBAC permission guards.
     * When submitted by staff, sends an edit submission to their Unit Head for review & approval.
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

        $isRollbackSubmit = false;
        if ($request->filled('rollback_id')) {
            $sub = \App\Models\StaffCaseSubmission::find($request->input('rollback_id'));
            if ($sub && $sub->status === 'Rolled Back') {
                $isRollbackSubmit = true;
            }
        } elseif (!empty($case->case_number)) {
            $hasRollback = \App\Models\StaffCaseSubmission::where('case_number_preview', $case->case_number)
                ->where('status', 'Rolled Back')
                ->exists();
            if ($hasRollback) {
                $isRollbackSubmit = true;
            }
        }

        if (!$isRollbackSubmit && (method_exists($case, 'isPendingApproval') ? $case->isPendingApproval() : false)) {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => 'This case is currently awaiting review and approval from the Head of Unit. Case editing is locked.'], 403)
                : back()->with('error', 'This case is currently awaiting review and approval from the Head of Unit. Case editing is locked.');
        }

        // Authorize user permission to modify this case
        $this->authorizeCaseWrite($case);

        $authUser = auth()->user();
        $isStaff = $authUser && !$authUser->is_head && !$request->boolean('approved_by_head');
        $diff = $this->detectEditedFields($case, $request);

        if ($isStaff) {

            $rawInput = $request->except(['_token']);
            if ($request->hasFile('search_warrant_document')) {
                $warrantFile = $request->file('search_warrant_document');
                if ($warrantFile && $warrantFile->isValid()) {
                    $rawInput['search_warrant_document_path'] = $warrantFile->store('search_warrants', 'public');
                }
            }
            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'mugshot_back'] as $mugKey) {
                if ($request->hasFile($mugKey)) {
                    $mFile = $request->file($mugKey);
                    if ($mFile && $mFile->isValid()) {
                        $rawInput[$mugKey] = $mFile->store('suspects', 'public');
                    }
                }
            }

            $payload = $this->stripUploadedFiles($rawInput);
            $payload = app(\App\Services\MediaStorageService::class)->processPayloadImages(
                $payload,
                $case->case_number
            );

            $payload['submission_type'] = 'edit';
            $payload['is_edit_submission'] = true;
            $payload['original_case_number'] = $case->case_number;
            $payload['original_case_id'] = $case->id;
            $payload['edited_fields'] = $diff['edited_fields'];
            $payload['edited_field_labels'] = $diff['edited_field_labels'];
            $payload['changes'] = $diff['changes'] ?? [];
            $payload['staff_editor_name'] = $authUser->fullname ?? $authUser->name ?? 'Staff Officer';
            $payload['first_edited_selector'] = $diff['first_edited_selector'];
            $payload['first_edited_field'] = $diff['first_edited_field'];

            $unitName = trim((string) (($authUser && $authUser->unit) ? $authUser->unit : ($case->unit_name ?: 'Crime Scene Support')));
            $suspectName = trim((string) ($request->input('suspect_name') ?: $request->input('full_name') ?: ($case->suspect_name ?: 'Pending Suspect')));
            $title = trim((string) ($request->input('title') ?: ($case->title ?: 'Staff Case Edit Submission')));
            $priority = \App\Models\ForensicCase::normalizePriority($request->input('priority') ?: ($case->priority ?: 'HIGH'));

            if ($request->filled('rollback_id')) {
                $submission = \App\Models\StaffCaseSubmission::find($request->input('rollback_id'));
                if ($submission) {
                    $submission->payload = $payload;
                    $submission->status = 'Pending Approval';
                    $submission->title = $title;
                    $submission->suspect_name = $suspectName;
                    $submission->priority = $priority;
                    $submission->rollback_corrections = null;
                    $submission->rolled_back_at = null;
                    $submission->review_notes = null;
                    $submission->save();

                    $msg = "Case edit submission #{$submission->id} has been updated with corrections and returned to your Unit Head for review and approval.";
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'pending_approval' => true,
                            'submission_id' => $submission->id,
                            'message' => $msg,
                        ], 200);
                    }
                    return redirect('/cases/' . $case->case_number . '/review')->with('status', $msg);
                }
            }

            // Check if there is already an active pending or rolled back edit submission for this case
            $existingSub = \App\Models\StaffCaseSubmission::where('case_number_preview', $case->case_number)
                ->whereIn('status', ['Pending Approval', 'Rolled Back'])
                ->latest()
                ->first();

            if ($existingSub) {
                $existingSub->payload = $payload;
                $existingSub->status = 'Pending Approval';
                $existingSub->title = $title;
                $existingSub->suspect_name = $suspectName;
                $existingSub->priority = $priority;
                $existingSub->rollback_corrections = null;
                $existingSub->rolled_back_at = null;
                $existingSub->review_notes = null;
                $existingSub->save();
                $submission = $existingSub;
            } else {
                $submission = \App\Models\StaffCaseSubmission::create([
                    'submitted_by' => $authUser->id,
                    'submitted_by_name' => $authUser->fullname ?? $authUser->name ?? 'Staff Officer',
                    'unit_name' => $unitName,
                    'case_number_preview' => $case->case_number,
                    'title' => $title,
                    'suspect_name' => $suspectName,
                    'priority' => $priority,
                    'status' => 'Pending Approval',
                    'payload' => $payload,
                ]);
            }

            $msg = "Case edits submitted to your Unit Head for review and approval before changes take effect (Submission #{$submission->id}).";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'pending_approval' => true,
                    'submission_id' => $submission->id,
                    'message' => $msg,
                    'case' => [
                        'id' => $submission->id,
                        'case_number' => $case->case_number,
                    ],
                ], 200);
            }

            return redirect('/cases/' . $case->case_number . '/review')->with('status', $msg);
        }

        $result = app(CaseDocketService::class)->updateCase($case, $request);
        $code = $result['code'] ?? 200;
        unset($result['code']);

        if (!empty($result['success']) && !empty($diff['changes'])) {
            \App\Services\CaseEditHistoryService::recordEditAudit($case, [
                'edit_mode'   => 'head_direct',
                'user'        => $authUser,
                'editor_name' => $authUser->fullname ?? $authUser->name ?? 'Unit Head',
                'changes'     => $diff['changes'],
            ]);
        }

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
        if (str_contains($unitLower, 'directorate') || str_contains($unitLower, 'head') || !empty($user?->is_head)) {
            return view('Forensic_Head.associate', compact('case'));
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
     * Display dedicated staff officer case review page keeping the standalone staff sidebar.
     */
    public function staffReview(Request $request, $id, \App\Services\CaseShowReviewPresenter $presenter)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);

        $from = strtolower((string) ($request->query('from') ?? ''));
        if ($request->is('Lab_Analysis*') || str_contains($from, 'lab')) {
            $sidebarUnit = 'Lab Analysis';
        } elseif ($request->is('Digital_Forensics*') || str_contains($from, 'digital') || str_contains($from, 'physical')) {
            $sidebarUnit = 'Digital Forensics';
        } elseif ($request->is('Evidence_Management*') || str_contains($from, 'evidence')) {
            $sidebarUnit = 'Evidence Management';
        } elseif ($request->is('Forensic_Head*') || str_contains($from, 'directorate') || str_contains($from, 'head')) {
            $sidebarUnit = 'Forensic Directorate';
        } elseif ($request->is('Crime_Scene_Support*') || str_contains($from, 'crime')) {
            $sidebarUnit = 'Crime Scene Support';
        } else {
            $userUnit = strtolower((string) (auth()->user()->unit ?? ''));
            if (str_contains($userUnit, 'lab')) {
                $sidebarUnit = 'Lab Analysis';
            } elseif (str_contains($userUnit, 'digital') || str_contains($userUnit, 'physical')) {
                $sidebarUnit = 'Digital Forensics';
            } elseif (str_contains($userUnit, 'evidence')) {
                $sidebarUnit = 'Evidence Management';
            } elseif (str_contains($userUnit, 'directorate') || str_contains($userUnit, 'head')) {
                $sidebarUnit = 'Forensic Directorate';
            } else {
                $sidebarUnit = 'Crime Scene Support';
            }
        }

        $unitCode = match ($sidebarUnit) {
            'Lab Analysis'         => 'lab',
            'Digital Forensics'    => 'dpf',
            'Evidence Management'  => 'em',
            'Forensic Directorate' => 'hof',
            default                => 'css',
        };

        $viewName = match ($sidebarUnit) {
            'Lab Analysis'         => 'Lab_Analysis.staff.review',
            'Digital Forensics'    => 'Digital_Forensics.staff.review',
            'Evidence Management'  => 'Evidence_Management.staff.review',
            'Forensic Directorate' => 'Forensic_Head.staff.review',
            default                => 'Crime_Scene_Support.staff.review',
        };

        $gsaReports = SuspectGsaReport::forCase($case->case_number ?: $id, $case->id);
        $nlrReports = SuspectNlrReport::forCase($case->case_number ?: $id, $case->id);
        $presenter = $presenter->presentForUnit($case, $unitCode, $request, $request->user());

        return view($viewName, compact('case', 'gsaReports', 'nlrReports', 'presenter', 'sidebarUnit'));
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

        if (method_exists($case, 'isPendingApproval') ? $case->isPendingApproval() : false) {
            return back()->with('error', 'This case is currently awaiting review and approval from the Head of Unit. Printing Case PDF is disabled until approved.');
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

    /**
     * Validate core staff submission inputs before staging into staff_case_submissions.
     */
    private function validateStaffSubmissionPayload(Request $request)
    {
        $noSuspect = filter_var($request->input('no_suspect'), FILTER_VALIDATE_BOOLEAN);

        if ($noSuspect) {
            $inputDocket = $this->decodeDocket($request->input('docket_data'));
            $rawSeizuresInput = is_array($request->input('seizures')) ? $request->input('seizures') : ($inputDocket['seizures'] ?? []);
            if (!is_array($rawSeizuresInput)) {
                $rawSeizuresInput = [];
            }
            $hasExplicitTopSeizure = ForensicCase::hasTopLevelExhibitData($request->all());

            if (count($rawSeizuresInput) === 0 && !$hasExplicitTopSeizure) {
                $errors = ['seizures' => ['At least one seized commodity in Section 4 is required when Sections 1 to 3 (Suspect Details) are skipped.']];
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please record at least one seized exhibit or commodity in Section 4 when logging a case without a suspect.',
                        'errors' => $errors,
                    ], 422);
                }
                throw \Illuminate\Validation\ValidationException::withMessages($errors);
            }

            return null;
        }

        \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'suspect_name' => ['required', 'string', 'max:255'],
        ])->validate();

        $inputDocket = $this->decodeDocket($request->input('docket_data'));
        $validDocketSuspects = (isset($inputDocket['suspects']) && is_array($inputDocket['suspects']))
            ? array_values(array_filter($inputDocket['suspects'], function ($s) {
                $n = trim((string) ($s['suspect_name'] ?? ''));
                return $n !== '' && $n !== 'Unnamed Suspect' && $n !== 'Unknown';
            }))
            : [];

        $suspectName = trim((string) $request->input('suspect_name', ''));
        if (empty($validDocketSuspects) && ($suspectName === '' || $suspectName === 'Unnamed Suspect' || $suspectName === 'Unknown')) {
            $errors = ['suspect_name' => ['Suspect Full Legal Name is required to create a docket case.']];
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The suspect name field is required. You cannot save a case docket without entering suspect details.',
                    'errors' => $errors,
                ], 422);
            }
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return null;
    }

    /**
     * Recursively strip UploadedFile instances so staged staff submission payloads encode cleanly to JSON.
     */
    private function stripUploadedFiles(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if ($value instanceof \SplFileInfo) {
                continue;
            }
            if (is_array($value)) {
                $cleaned[$key] = $this->stripUploadedFiles($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }
}
