<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record a server-side audit event.
     */
    public static function log(string $action, string $description, array $options = []): AuditLog
    {
        $user = $options['user'] ?? Auth::user();

        return AuditLog::create([
            'user_id'        => $user->id ?? null,
            'user_name'      => $options['user_name'] ?? ($user->fullname ?? ($user->username ?? 'System')),
            'user_unit'      => $options['user_unit'] ?? ($user->unit ?? 'System'),
            'action'         => $action,
            'description'    => $description,
            'auditable_type' => $options['auditable_type'] ?? null,
            'auditable_id'   => isset($options['auditable_id']) ? (string)$options['auditable_id'] : null,
            'case_number'    => $options['case_number'] ?? null,
            'old_values'     => $options['old_values'] ?? null,
            'new_values'     => $options['new_values'] ?? null,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }
}
