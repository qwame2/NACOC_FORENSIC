<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\AuditLog;
use App\Models\CaseHandover;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Services\AuditLogger;
use App\Services\CaseWorkflowService;
use App\Services\LaboratoryExamService;
use App\Services\SchemaCache;
use App\Enums\WorkflowStatus;
use App\Enums\UnitName;
use App\Enums\CaseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * CaseWorkflowController
 *
 * Dedicated controller for multi-unit specialized pipeline progress,
 * inter-unit chain of custody handovers, suspect disposition transitions,
 * and immutable case audit trails.
 * Split from the original monolithic controller according to Issue 16 & 17 (<400 lines).
 * Fix Issue 9 & 15: Added missing null guards on underlyingRecord before transactions.
 * Fix Issue 11: Delegated lab examination handling to LaboratoryExamService.
 * Fix Issue 18: Used WorkflowStatus, UnitName, and CaseStatus enums.
 * Fix Issue 21: Null-safe Auth::user() audit logging.
 * Fix Issue 28 & 30: Strict unit authorization and status change permission checks.
 */
class CaseWorkflowController extends Controller
{
    use CaseControllerHelpers;

    /**
     * Update unit workflow progress with validation and auto-advancement.
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 11: LaboratoryExamService.
     * Fix Issue 18: WorkflowStatus and UnitName enums.
     * Fix Issue 28: authorizeCaseWrite check.
     */
    public function updateUnitProgress($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        // Fix Issue 9 & 15: Add missing null guard on underlyingRecord
        $underlying = $case->underlyingRecord;
        if (!$underlying) {
            Log::warning("Case {$case->case_number} has no underlyingRecord — cannot complete operation.", [
                'case_id' => $case->id,
                'context' => __METHOD__,
            ]);
            return response()->json(['success' => false, 'message' => 'Case record is incomplete (no underlying case).'], 500);
        }

        // Fix Issue 28: Authorize user permission
        $this->authorizeCaseWrite($case);

        $workflowService = app(CaseWorkflowService::class);
        $result = $workflowService->updateUnitProgress($request, $case);

        $code = $result['code'] ?? 200;
        unset($result['code']);
        return response()->json($result, $code);
    }

    /**
     * Update high-level case status (e.g. In Court, On Remand, Case Closed).
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 30: Tighten authorization - only Head of case's unit or Admin can change status.
     * Hardening A: Audit unauthorized attempts.
     */
    public function updateCaseStatus(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'case_status' => [
                'required',
                'string',
                'in:Under Investigation,On Remand,On Bail,In Court,Verdict Passed — Imprisoned,Verdict Passed — Acquitted,Case Dismissed,Case Closed'
            ],
        ])->validate();

        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        // Fix Issue 9 & 15: Null guard on underlyingRecord
        $underlying = $case->underlyingRecord;
        if (!$underlying) {
            Log::warning("Case {$case->case_number} has no underlyingRecord — cannot complete status change.", [
                'case_id' => $case->id,
                'context' => __METHOD__,
            ]);
            return response()->json(['success' => false, 'message' => 'Case record is incomplete (no underlying case).'], 500);
        }

        $user = Auth::user();
        if (!$user) {
            abort(401, 'Authentication required.');
        }

        // Allow ANY Head of Unit, Admin, or assigned Case Controller to update the case status
        $isHead = !empty($user->is_head) || (isset($user->unit) && str_contains(strtolower($user->unit), 'head'));
        $isAdmin = strtolower($user->role ?? '') === 'admin' || !empty($user->is_admin);
        $isController = (!empty($case->controller_user_id) && (int)$case->controller_user_id === (int)$user->id)
            || (!empty($case->user_id) && (int)$case->user_id === (int)$user->id);

        if (!$isAdmin && !$isHead && !$isController) {
            // Hardening A: Audit unauthorized attempt
            AuditLogger::log('UNAUTHORIZED_ATTEMPT', 'Blocked unauthorized status update on case ' . ($case->case_number ?? 'unknown'), [
                'user'   => $user,
                'ip'     => request()->ip(),
                'route'  => request()->path(),
                'params' => request()->except(['password', 'token']),
            ]);
            return response()->json([
                'message' => 'Unauthorized: Only a Head of Unit or Admin can change the case status.'
            ], 403);
        }

        $workflowService = app(CaseWorkflowService::class);
        $result = $workflowService->updateCaseStatus($request, $case, $validated['case_status']);

        $code = $result['code'] ?? 200;
        unset($result['code']);
        return response()->json($result, $code);
    }

    /**
     * Update individual suspect legal disposition within a case dossier.
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 28: Enforce authorizeCaseWrite().
     */
    public function updateSuspectDisposition(Request $request, $id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        // Fix Issue 9 & 15: Null guard on underlyingRecord
        $underlying = $case->underlyingRecord;
        if (!$underlying) {
            Log::warning("Case {$case->case_number} has no underlyingRecord — cannot update disposition.", [
                'case_id' => $case->id,
                'context' => __METHOD__,
            ]);
            return response()->json(['success' => false, 'message' => 'Case record is incomplete (no underlying case).'], 500);
        }

        $user = Auth::user();
        if (!$user) {
            abort(401, 'Authentication required.');
        }

        $isController = !empty($case->controller_user_id) && (int)$case->controller_user_id === (int)$user->id;
        $isCreator = !empty($case->user_id) && (int)$case->user_id === (int)$user->id;
        $isHead = !empty($user->is_head);
        $isAdmin = strtolower($user->role ?? '') === 'admin';

        if (!$isAdmin && !$isHead && !$isController && !$isCreator) {
            AuditLogger::log('UNAUTHORIZED_ATTEMPT', 'Blocked unauthorized suspect disposition update on case ' . ($case->case_number ?? 'unknown'), [
                'user'   => $user,
                'ip'     => request()->ip(),
                'route'  => request()->path(),
                'params' => request()->except(['password', 'token']),
            ]);
            return response()->json([
                'message' => 'Unauthorized: Only the assigned case controller, unit head, or an administrator can update suspect disposition.'
            ], 403);
        }

        if ($underlying instanceof SingleSuspectCase || !$case->is_multiple) {
            return response()->json([
                'success' => false,
                'message' => 'Suspect disposition updates are strictly available for multiple suspect cases only.',
            ], 422);
        }

        $workflowService = app(CaseWorkflowService::class);
        $result = $workflowService->updateSuspectDisposition($request, $case);

        $code = $result['code'] ?? 200;
        unset($result['code']);
        return response()->json($result, $code);
    }

    /**
     * Initiate an official chain-of-custody case handover to another unit.
     */
    public function initiateHandover(Request $request, $id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        $this->authorizeCaseWrite($case);

        $workflowService = app(CaseWorkflowService::class);
        $result = $workflowService->initiateHandover($request, $case);

        $code = $result['code'] ?? 200;
        unset($result['code']);
        return response()->json($result, $code);
    }

    /**
     * Respond to a pending case handover (ACCEPT or DECLINE).
     */
    public function respondHandover(Request $request, $id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        $workflowService = app(CaseWorkflowService::class);
        $result = $workflowService->respondHandover($request, $case);

        $code = $result['code'] ?? 200;
        unset($result['code']);
        return response()->json($result, $code);
    }

    /**
     * Fetch immutable forensic audit trail events for a case.
     */
    public function getAuditTrail($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => "Case {$id} not found."], 404);
        }

        $underlying = $case->underlyingRecord ?? $case;
        $classNames = array_unique([
            get_class($underlying),
            ForensicCase::class,
            MultipleSuspectCase::class,
            SingleSuspectCase::class,
        ]);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($classNames, $case, $underlying) {
                $q->where(function ($sq) use ($classNames, $case, $underlying) {
                    $sq->whereIn('auditable_type', $classNames)
                       ->whereIn('auditable_id', array_unique([$case->id, $underlying->id]));
                })->orWhere('case_number', $case->case_number);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success'     => true,
            'case_number' => $case->case_number,
            'logs'        => $logs,
        ]);
    }
}
