<?php

namespace App\Services;

use App\Enums\CaseStatus;
use App\Enums\UnitName;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\AuditLog;
use App\Models\CaseHandover;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * CaseWorkflowService
 *
 * Dedicated service for inter-unit handovers, suspect disposition transitions,
 * unit workflow progress updates, and high-level case status management.
 */
class CaseWorkflowService
{
    use CaseControllerHelpers;

    /**
     * Update unit workflow progress with validation and auto-advancement.
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 11: LaboratoryExamService.
     * Fix Issue 18: WorkflowStatus and UnitName enums.
     */
    public function updateUnitProgress(Request $request, ForensicCase $case): array
    {
        $user = Auth::user();
        $unitName = $request->input('unit_name') ?? ($user ? $user->unit : UnitName::CRIME_SCENE_SUPPORT->value);
        $unitNotes = $request->input('unit_notes') ?? $request->input('notes');

        $reqStatus = strtoupper((string)$request->input('unit_status', 'COMPLETED'));
        if (!in_array($reqStatus, [WorkflowStatus::COMPLETED->value, WorkflowStatus::IN_PROGRESS->value, WorkflowStatus::PENDING->value], true)) {
            $unitStatus = $request->boolean('mark_completed', true) ? WorkflowStatus::COMPLETED->value : WorkflowStatus::IN_PROGRESS->value;
        } else {
            $unitStatus = $reqStatus;
        }

        $officer = $request->input('officer') ?? ($user ? $user->fullname : 'Unit Officer');

        // Normalize unit name
        if (str_contains(strtolower($unitName), 'lab')) {
            $unitNameKey = UnitName::LAB_ANALYSIS->value;
        } elseif (str_contains(strtolower($unitName), 'evidence')) {
            $unitNameKey = UnitName::EVIDENCE_MANAGEMENT->value;
        } elseif (str_contains(strtolower($unitName), 'digital') || str_contains(strtolower($unitName), 'cyber')) {
            $unitNameKey = UnitName::DIGITAL_PHYSICAL_FORENSICS->value;
        } else {
            $unitNameKey = UnitName::CRIME_SCENE_SUPPORT->value;
        }

        $docketData = $this->decodeDocket($case);
        $exOwnerName = $request->input('exhibit_owner_name');

        if ($unitNameKey === UnitName::LAB_ANALYSIS->value || $request->has('lab_unit') || $request->has('presumptive_result')) {
            $unexaminedJointSuspects = $this->getUnexaminedLabSuspects($case, $docketData, $exOwnerName);
            if (!empty($unexaminedJointSuspects)) {
                $unitStatus = WorkflowStatus::IN_PROGRESS->value;
            }
        }

        if (!isset($docketData['unit_progress'])) {
            $reqUnits = $case->getWorkflowUnits();
            $initProg = [];
            foreach ($reqUnits as $rU) {
                $initProg[$rU] = ['status' => WorkflowStatus::PENDING->value, 'officer' => null, 'updated_at' => null];
            }
            if (isset($initProg[UnitName::CRIME_SCENE_SUPPORT->value])) {
                $initProg[UnitName::CRIME_SCENE_SUPPORT->value] = [
                    'status'     => WorkflowStatus::COMPLETED->value,
                    'officer'    => $case->user?->fullname ?? ($case->user_id ? \App\Models\User::find($case->user_id)?->fullname : ($case->submitting_officer ?? 'Initial Field Officer')),
                    'updated_at' => $case->created_at ? $case->created_at->toDateTimeString() : now()->toDateTimeString(),
                ];
            }
            $docketData['unit_progress'] = $initProg;
        }

        $docketData['unit_progress'][$unitNameKey] = [
            'status'     => $unitStatus,
            'officer'    => $officer,
            'notes'      => $unitNotes,
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($unitNameKey === UnitName::LAB_ANALYSIS->value) {
            $case->lab_status = $unitStatus;
        }

        if ($unitNameKey === UnitName::LAB_ANALYSIS->value || $request->has('lab_unit') || $request->has('presumptive_result')) {
            $submittedExhibitId = $request->input('exhibit_id') ?: ($request->input('lab_exhibit_number') ?: $request->input('exhibit_number'));
            $exOwnerName         = $request->input('exhibit_owner_name') ?: ($request->input('owner_name') ?: ($request->input('suspect_name') ?: ''));

            if (!empty($submittedExhibitId)) {
                $exhibitModel = null;
                $exhibitDataInDocket = null;

                if (is_numeric($submittedExhibitId)) {
                    $exhibitModel = Exhibit::find((int)$submittedExhibitId);
                }

                if (!$exhibitModel && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                    foreach ($docketData['seizures'] as $sIdx => $sz) {
                        if ((isset($sz['id']) && (string)$sz['id'] === (string)$submittedExhibitId)
                            || (isset($sz['exhibit_number']) && (string)$sz['exhibit_number'] === (string)$submittedExhibitId)
                            || (string)($sIdx + 1) === (string)$submittedExhibitId) {
                            $exhibitDataInDocket = $sz;
                            break;
                        }
                    }
                }

                if (!$exhibitModel && !$exhibitDataInDocket) {
                    return [
                        'success' => false,
                        'message' => 'Backend validation failed: The selected exhibit does not exist.',
                        'code'    => 422,
                    ];
                }

                if ($exhibitModel) {
                    $cTypeUpper = strtoupper($exhibitModel->case_type ?? '');
                    $belongsToCase = ($cTypeUpper === 'SINGLE' && (int)$exhibitModel->single_suspect_case_id === (int)$case->id)
                        || ($cTypeUpper === 'MULTIPLE' && (int)$exhibitModel->multiple_suspect_case_id === (int)$case->id);
                    if (!$belongsToCase) {
                        return [
                            'success' => false,
                            'message' => 'Backend validation failed: Selected exhibit does not belong to the current case.',
                            'code'    => 422,
                        ];
                    }
                }

                if (!$this->isDrugExhibit($exhibitModel ?: $exhibitDataInDocket)) {
                    return [
                        'success' => false,
                        'message' => 'Backend validation failed: The selected exhibit is not a drug exhibit.',
                        'code'    => 422,
                    ];
                }
            }

            $primaryName = $case->suspect_name ?? '';
            $canonicalOwnerName = trim($exOwnerName ?? '');
            if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                $cleanInput = mb_strtolower(preg_replace('/[^a-z0-9]/', '', $canonicalOwnerName));
                foreach ($docketData['suspects'] as $sp) {
                    $spClean = mb_strtolower(preg_replace('/[^a-z0-9]/', '', $sp['suspect_name'] ?? ''));
                    if ($spClean === $cleanInput || str_contains($spClean, $cleanInput) || str_contains($cleanInput, $spClean)) {
                        $canonicalOwnerName = trim($sp['suspect_name']);
                        break;
                    }
                }
            }

            $isAssoc = $request->boolean('exhibit_is_associate')
                || (!empty($primaryName) && !empty($canonicalOwnerName) && strtolower(trim($primaryName)) !== strtolower(trim($canonicalOwnerName)));

            // Fix Issue 11: Save via centralized LaboratoryExamService
            $labService = app(LaboratoryExamService::class);
            $docketData = $labService->saveExam($case, $request->all(), $canonicalOwnerName, $isAssoc, $officer);
        }

        if (!empty($unitNotes)) {
            if (!isset($docketData['unit_notes'])) {
                $docketData['unit_notes'] = [];
            }
            $docketData['unit_notes'][$unitNameKey] = [
                'notes'      => $unitNotes,
                'officer'    => $officer,
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        $units = $case->getWorkflowUnits();
        $totalUnits = count($units);
        $completedCount = 0;
        foreach ($units as $u) {
            if (isset($docketData['unit_progress'][$u]) && strtoupper($docketData['unit_progress'][$u]['status'] ?? '') === WorkflowStatus::COMPLETED->value) {
                $completedCount++;
            }
        }

        if ($completedCount >= $totalUnits) {
            $case->status = CaseStatus::AWAITING_SIGN_OFF->value;
            $msg = "All {$totalUnits} specialized units have completed processing! Case {$case->case_number} has been forwarded to Head of Forensic Directorate for final sign-off.";
        } else {
            $case->status = CaseStatus::UNDER_MULTI_UNIT_PROCESSING->value;
            $msg = "{$unitNameKey} progress updated for Case {$case->case_number}. Currently {$completedCount}/{$totalUnits} units completed.";
        }

        $case->docket_data = $docketData;
        $case->save();
        if ($case->underlyingRecord && SchemaCache::hasColumn($case->underlyingRecord->getTable(), 'docket_data')) {
            $case->underlyingRecord->docket_data = $docketData;
            $case->underlyingRecord->status = $case->status;
            $case->underlyingRecord->save();
        }

        if ($user) {
            AuditLogger::log(
                'WORKFLOW_UNIT_COMPLETED',
                "{$unitNameKey} unit progress updated to '{$unitStatus}' by " . $user->fullname . " ({$unitNameKey}) for Case {$case->case_number}",
                [
                    'case_number'    => $case->case_number,
                    'auditable_type' => get_class($case->underlyingRecord ?? $case),
                    'auditable_id'   => $case->id,
                    'new_values'     => [
                        'unit_name'   => $unitNameKey,
                        'unit_status' => $unitStatus,
                        'officer'     => $officer,
                    ],
                    'user'           => $user,
                ]
            );
        }

        return [
            'success'         => true,
            'message'         => $msg,
            'status'          => $case->status,
            'unit_name'       => $unitNameKey,
            'unit_status'     => $unitStatus,
            'completed_units' => $completedCount,
            'completed_count' => $completedCount,
            'total_units'     => $totalUnits,
            'case'            => $case,
        ];
    }

    /**
     * Update high-level case status (e.g. In Court, On Remand, Case Closed).
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     */
    public function updateCaseStatus(Request $request, ForensicCase $case, string $newStatus): array
    {
        $user = Auth::user();
        $underlying = $case->underlyingRecord;
        $oldStatus = $case->case_status ?? CaseStatus::UNDER_INVESTIGATION->value;

        DB::transaction(function () use ($case, $underlying, $oldStatus, $newStatus, $user) {
            $case->case_status = $newStatus;
            $case->save();

            if (SchemaCache::hasColumn($underlying->getTable(), 'case_status')) {
                $underlying->case_status = $newStatus;
            }

            // Cascade legal status to suspect dispositions
            if ($underlying instanceof SingleSuspectCase) {
                if (SchemaCache::hasColumn($underlying->getTable(), 'suspect_disposition')) {
                    $underlying->suspect_disposition = $newStatus;
                }
            } elseif ($underlying instanceof MultipleSuspectCase) {
                $exemptDispositions = [
                    'Released from Case',
                    'Cleared / No Further Action',
                    'Verdict Passed — Acquitted',
                    'Acquitted',
                    'Case Dismissed',
                ];
                CaseSuspect::where('multiple_suspect_case_id', $underlying->id)
                    ->where(function ($q) use ($exemptDispositions) {
                        $q->whereNull('suspect_disposition')
                          ->orWhereNotIn('suspect_disposition', $exemptDispositions);
                    })
                    ->update(['suspect_disposition' => $newStatus]);
            }

            $underlying->save();

            if ($user) {
                AuditLogger::log(
                    'CASE_STATUS_UPDATED',
                    "Case {$case->case_number} legal status updated from '{$oldStatus}' to '{$newStatus}' by " . $user->fullname,
                    [
                        'case_number'    => $case->case_number,
                        'auditable_type' => get_class($underlying),
                        'auditable_id'   => $case->id,
                        'old_values'     => ['case_status' => $oldStatus],
                        'new_values'     => ['case_status' => $newStatus],
                        'user'           => $user,
                    ]
                );
            }
        });

        return [
            'success'     => true,
            'message'     => "Case status successfully updated to '{$newStatus}'.",
            'case_status' => $newStatus,
            'case'        => $case,
        ];
    }
    /**
     * Update suspect disposition and sync across docket & relational tables.
     */
    public function updateSuspectDisposition(Request $request, ForensicCase $case): array
    {
        $validated = Validator::make($request->all(), [
            'suspect_id'          => 'nullable',
            'suspect_name'        => 'nullable|string',
            'suspect_disposition' => 'required|string',
            'disposition_reason'  => 'nullable|string',
        ])->validate();

        $suspectId   = $request->input('suspect_id') ?: $request->input('suspect_name');
        $newDisp     = ForensicCase::normalizeSuspectDisposition($validated['suspect_disposition']);
        $reason      = trim((string)($request->input('disposition_reason') ?? ''));

        $isActive = in_array(strtolower(trim($newDisp)), ['active', 'active — under investigation', 'under investigation'], true);
        if (!$isActive && empty($reason)) {
            return [
                'success' => false,
                'message' => 'The disposition reason field is required when suspect disposition is not active.',
                'errors'  => [
                    'disposition_reason' => ['A valid justification reason is required when updating a suspect to an inactive or cleared disposition.']
                ],
                'code'    => 422,
            ];
        }

        $user        = Auth::user();
        $officerName = $user ? $user->fullname : 'Authorized Officer';

        $underlying = $case->underlyingRecord;
        $isMultiple = $underlying instanceof MultipleSuspectCase;

        $targetSuspect = null;
        if ($isMultiple) {
            if (is_numeric($suspectId)) {
                $targetSuspect = CaseSuspect::where('multiple_suspect_case_id', $underlying->id)
                    ->where('id', (int)$suspectId)
                    ->first();
            }
            if (!$targetSuspect) {
                $targetSuspect = CaseSuspect::where('multiple_suspect_case_id', $underlying->id)
                    ->where(function ($q) use ($suspectId) {
                        $q->where('associate_id', $suspectId)
                          ->orWhere('suspect_name', $suspectId)
                          ->orWhereRaw('LOWER(TRIM(suspect_name)) = ?', [strtolower(trim($suspectId))]);
                    })
                    ->first();
            }
        } else {
            $targetSuspect = CaseSuspect::where('single_suspect_case_id', $underlying->id)->first();
        }

        $oldDisp = $targetSuspect ? $targetSuspect->suspect_disposition : 'In Custody';

        if ($targetSuspect) {
            $targetSuspect->update([
                'suspect_disposition'    => $newDisp,
                'disposition_reason'     => $reason ?: null,
                'disposition_updated_at' => now(),
                'disposition_updated_by' => $officerName,
            ]);
        }

        // Sync with Case docket data
        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
        if (isset($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as &$s) {
                $sName = $s['suspect_name'] ?? '';
                $sAssoc = $s['associate_id'] ?? ($s['suspect_id'] ?? '');
                $sId = $s['id'] ?? '';

                if ((string)$sId === (string)$suspectId || (string)$sAssoc === (string)$suspectId || strtolower(trim($sName)) === strtolower(trim($suspectId))) {
                    $s['suspect_disposition']    = $newDisp;
                    $s['disposition_reason']     = $reason ?: null;
                    $s['disposition_updated_at'] = now()->toDateTimeString();
                    $s['disposition_updated_by'] = $officerName;
                }
            }
            unset($s);
            $case->docket_data = $docketData;
            $case->save();
            if ($case->underlyingRecord && SchemaCache::hasColumn($case->underlyingRecord->getTable(), 'docket_data')) {
                $case->underlyingRecord->docket_data = $docketData;
                $case->underlyingRecord->save();
            }
        }

        if ($user) {
            AuditLogger::log('SUSPECT_DISPOSITION_CHANGED', "Suspect disposition updated to {$newDisp} for {$targetSuspect?->suspect_name} on case {$case->case_number}", [
                'case_number' => $case->case_number,
                'suspect_id'  => $targetSuspect?->id,
                'old_status'  => $oldDisp,
                'new_status'  => $newDisp,
                'user'        => $user,
            ]);
        }

        return [
            'success'     => true,
            'message'     => "Suspect disposition updated to {$newDisp}.",
            'disposition' => $newDisp,
        ];
    }

    /**
     * Initiate an official chain-of-custody case handover to another unit.
     */
    public function initiateHandover(Request $request, ForensicCase $case): array
    {
        $validated = Validator::make($request->all(), [
            'to_unit_id'      => 'required',
            'handover_notes'  => 'required|string',
            'exhibit_ids'     => 'nullable|array',
            'seizure_indexes' => 'nullable|array',
        ])->validate();

        $user = Auth::user();
        $toUnit = Unit::find($validated['to_unit_id']) ?: Unit::where('name', $validated['to_unit_id'])->first();
        if (!$toUnit) {
            return ['success' => false, 'message' => 'Destination unit not found.', 'code' => 404];
        }

        $fromUnitId = $case->unit_id ?: ($user?->unit_id ?: 1);

        $handover = CaseHandover::create([
            'case_number'           => $case->case_number,
            'case_id'               => $case->id,
            'from_unit_id'          => $fromUnitId,
            'to_unit_id'            => $toUnit->id,
            'initiated_by_user_id'  => $user ? $user->id : 1,
            'status'                => 'PENDING_ACCEPTANCE',
            'notes'                 => $validated['handover_notes'],
            'exhibits_manifest'     => $validated['exhibit_ids'] ?? ($validated['seizure_indexes'] ?? []),
            'initiated_at'          => now(),
        ]);

        if ($user) {
            AuditLogger::log('CASE_HANDOVER_INITIATED', "Handover initiated for Case {$case->case_number} to {$toUnit->name}", [
                'case_number' => $case->case_number,
                'handover_id' => $handover->id,
                'to_unit'     => $toUnit->name,
                'user'        => $user,
            ]);
        }

        return [
            'success'  => true,
            'message'  => "Handover to {$toUnit->name} initiated successfully.",
            'handover' => $handover,
        ];
    }

    /**
     * Accept or decline a pending case handover.
     */
    public function respondHandover(Request $request, ForensicCase $case): array
    {
        $validated = Validator::make($request->all(), [
            'handover_id' => 'required',
            'action'      => 'required|in:ACCEPT,DECLINE',
            'remarks'     => 'nullable|string',
        ])->validate();

        $user = Auth::user();
        $handover = CaseHandover::where('id', $validated['handover_id'])
            ->where('case_number', $case->case_number)
            ->first();

        if (!$handover) {
            return ['success' => false, 'message' => 'Handover record not found.', 'code' => 404];
        }

        $action = strtoupper($validated['action']);
        if ($action === 'ACCEPT') {
            $handover->update([
                'status'               => 'ACCEPTED',
                'responded_by_user_id' => $user ? $user->id : 1,
                'response_remarks'     => $validated['remarks'] ?? 'Handover accepted.',
                'responded_at'         => now(),
            ]);

            $case->unit_id = $handover->to_unit_id;
            $toUnit = Unit::find($handover->to_unit_id);
            if ($toUnit) {
                $case->unit_name = $toUnit->name;
            }
            $case->save();
            if ($case->underlyingRecord) {
                $case->underlyingRecord->unit_id = $case->unit_id;
                if ($toUnit) {
                    $case->underlyingRecord->unit_name = $toUnit->name;
                }
                $case->underlyingRecord->save();
            }

            if ($user) {
                AuditLogger::log('CASE_HANDOVER_ACCEPTED', "Handover accepted for Case {$case->case_number}", [
                    'case_number' => $case->case_number,
                    'handover_id' => $handover->id,
                    'user'        => $user,
                ]);
            }

            return ['success' => true, 'message' => "Handover for Case {$case->case_number} accepted successfully."];
        }

        $handover->update([
            'status'               => 'DECLINED',
            'responded_by_user_id' => $user ? $user->id : 1,
            'response_remarks'     => $validated['remarks'] ?? 'Handover declined.',
            'responded_at'         => now(),
        ]);

        if ($user) {
            AuditLogger::log('CASE_HANDOVER_DECLINED', "Handover declined for Case {$case->case_number}", [
                'case_number' => $case->case_number,
                'handover_id' => $handover->id,
                'user'        => $user,
            ]);
        }

        return ['success' => true, 'message' => "Handover for Case {$case->case_number} has been declined."];
    }
}
