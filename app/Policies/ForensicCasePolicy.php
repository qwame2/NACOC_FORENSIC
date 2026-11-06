<?php

namespace App\Policies;

use App\Models\User;

class ForensicCasePolicy
{
    /**
     * Determine whether the user can view a forensic unit case review screen.
     */
    public function viewUnitReview(?User $user, mixed $case = null, ?string $unit = null): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the Head of Forensic Directorate (HOF) review screen.
     */
    public function viewForensicHeadReview(?User $user, mixed $case = null): bool
    {
        return $user !== null && (bool) $user->is_head && (bool) $user->is_approved;
    }

    /**
     * Determine whether the user holds Head of Forensic Directorate authority.
     */
    public function isForensicHead(?User $user): bool
    {
        if (!$user || !$user->is_head) {
            return false;
        }

        $unit = (string) ($user->unit ?? '');

        return $unit === 'Forensic Directorate'
            || $unit === 'Forensic Head'
            || $unit === 'Directorate Head'
            || str_contains($unit, 'Forensic');
    }

    /**
     * Determine whether the user has case controller / command authority over the case status and handover.
     */
    public function controlCaseStatus(?User $user, mixed $case = null, bool $isForensicHeadContext = false): bool
    {
        if (!$user) {
            return false;
        }

        return !empty($user->is_head)
            || $isForensicHeadContext
            || $this->isForensicHead($user)
            || strtolower((string) ($user->role ?? '')) === 'admin'
            || (isset($user->unit) && str_contains(strtolower((string) $user->unit), 'head'));
    }
}

