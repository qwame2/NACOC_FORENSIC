<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ForensicCase;
use App\Models\StaffCaseSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CaseEditHistoryService
{
    /**
     * Record a case edit audit event in the system audit logs.
     */
    public static function recordEditAudit(mixed $case, array $options): ?AuditLog
    {
        $caseNumber = is_object($case)
            ? ($case->case_number ?? ($case->approved_case_number ?? null))
            : ($case['case_number'] ?? null);

        if (!$caseNumber) {
            return null;
        }

        $editMode = $options['edit_mode'] ?? 'head_direct';
        $user = $options['user'] ?? Auth::user();
        $editorName = $options['editor_name'] ?? ($user?->fullname ?? ($user?->name ?? 'User'));
        $headName = $options['head_name'] ?? null;
        $changes = $options['changes'] ?? [];

        if ($editMode === 'staff_approved') {
            $staffName = $options['staff_name'] ?? $editorName;
            $approverHead = $headName ?: ($user?->fullname ?? ($user?->name ?? 'Unit Head'));
            $desc = "This case was edited by {$staffName} and approved by {$approverHead}.";
            $oldValues = [
                'edit_mode'             => 'staff_approved',
                'staff_editor_name'     => $staffName,
                'approved_by_head_name' => $approverHead,
                'edited_at'             => now()->toDateTimeString(),
                'changes'               => $changes,
            ];
        } else {
            $desc = "This case has been edited by {$editorName}.";
            $oldValues = [
                'edit_mode'   => 'head_direct',
                'editor_name' => $editorName,
                'edited_at'   => now()->toDateTimeString(),
                'changes'     => $changes,
            ];
        }

        $auditableType = is_object($case) ? get_class($case->underlyingRecord ?? $case) : ForensicCase::class;
        $auditableId = is_object($case) ? ($case->id ?? null) : null;

        return AuditLog::create([
            'user_id'        => $user?->id ?? null,
            'user_name'      => $editorName,
            'user_unit'      => $user?->unit ?? (is_object($case) ? ($case->unit_name ?? 'Forensic') : 'Forensic'),
            'action'         => 'CASE_EDITED',
            'description'    => $desc,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId ? (string) $auditableId : null,
            'case_number'    => $caseNumber,
            'old_values'     => $oldValues,
            'new_values'     => ['status' => 'UPDATED'],
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }

    /**
     * Resolve the latest edit metadata, wording sentence, and changes diff for a case.
     */
    public static function resolveLatestEdit(mixed $case): ?array
    {
        if (!$case) {
            return null;
        }

        $caseNumber = is_object($case)
            ? ($case->case_number ?? ($case->approved_case_number ?? null))
            : ($case['case_number'] ?? null);

        if (!$caseNumber) {
            return null;
        }

        // 1. Look for the latest CASE_EDITED audit log for this case
        $auditLog = AuditLog::where('case_number', $caseNumber)
            ->where('action', 'CASE_EDITED')
            ->latest('id')
            ->first();

        if ($auditLog && !empty($auditLog->old_values)) {
            $data = is_array($auditLog->old_values)
                ? $auditLog->old_values
                : (json_decode($auditLog->old_values, true) ?: []);

            $mode = $data['edit_mode'] ?? 'head_direct';
            $changes = $data['changes'] ?? [];

            if ($mode === 'staff_approved') {
                $staffName = $data['staff_editor_name'] ?? 'Staff Member';
                $headName = $data['approved_by_head_name'] ?? ($auditLog->user_name ?? 'Unit Head');
                $sentence = "This case was edited by {$staffName} and approved by {$headName}.";

                return [
                    'has_edit'    => true,
                    'mode'        => 'staff_approved',
                    'sentence'    => $sentence,
                    'staff_name'  => $staffName,
                    'head_name'   => $headName,
                    'editor_name' => $staffName,
                    'edited_at'   => $data['edited_at'] ?? $auditLog->created_at?->format('d M Y, H:i'),
                    'changes'     => $changes,
                ];
            }

            $editorName = $data['editor_name'] ?? ($auditLog->user_name ?? 'User');
            $sentence = "This case has been edited by {$editorName}.";

            return [
                'has_edit'    => true,
                'mode'        => 'head_direct',
                'sentence'    => $sentence,
                'editor_name' => $editorName,
                'head_name'   => null,
                'edited_at'   => $data['edited_at'] ?? $auditLog->created_at?->format('d M Y, H:i'),
                'changes'     => $changes,
            ];
        }

        // 2. Fallback: check if there is an approved edit submission in staff_case_submissions
        $editSub = StaffCaseSubmission::where(function ($q) use ($caseNumber) {
                $q->where('approved_case_number', $caseNumber)
                  ->orWhere('case_number_preview', $caseNumber);
            })
            ->where('status', 'Approved')
            ->where(function ($q) {
                $q->where('payload->is_edit_submission', true)
                  ->orWhere('payload->submission_type', 'edit');
            })
            ->latest('id')
            ->first();

        if ($editSub) {
            $payload = is_array($editSub->payload) ? $editSub->payload : [];
            $staffName = $editSub->submitted_by_name ?? ($payload['staff_editor_name'] ?? 'Staff Member');
            $headName = $editSub->reviewed_by_name ?? 'Unit Head';
            $changes = $payload['changes'] ?? [];

            // If changes array was empty (older submission), build from edited_field_labels
            if (empty($changes) && !empty($payload['edited_field_labels'])) {
                foreach ($payload['edited_field_labels'] as $idx => $label) {
                    $fKey = $payload['edited_fields'][$idx] ?? 'field';
                    $changes[] = [
                        'field'    => $fKey,
                        'label'    => $label,
                        'previous' => 'Original Record',
                        'current'  => 'Updated by ' . $staffName,
                    ];
                }
            }

            return [
                'has_edit'    => true,
                'mode'        => 'staff_approved',
                'sentence'    => "This case was edited by {$staffName} and approved by {$headName}.",
                'staff_name'  => $staffName,
                'head_name'   => $headName,
                'editor_name' => $staffName,
                'edited_at'   => $editSub->reviewed_at?->format('d M Y, H:i') ?? $editSub->updated_at?->format('d M Y, H:i'),
                'changes'     => $changes,
            ];
        }

        return null;
    }
}

