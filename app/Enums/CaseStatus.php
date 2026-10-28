<?php

namespace App\Enums;

enum CaseStatus: string
{
    case APPROVED = 'Approved';
    case AUTHORIZED = 'Authorized';
    case DECLINED = 'Declined';
    case PENDING = 'Pending';
    case PENDING_APPROVAL = 'Pending Approval';
    case PENDING_ACKNOWLEDGEMENT = 'Pending Acknowledgement';
    case UNDER_INVESTIGATION = 'Under Investigation';
    case UNDER_MULTI_UNIT_PROCESSING = 'Under Multi-Unit Processing';
    case AWAITING_SIGN_OFF = 'Awaiting Sign-off';
    case CLOSED = 'Closed';

    /**
     * Get all enum string values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status indicates approval.
     */
    public function isApproved(): bool
    {
        return in_array($this, [self::APPROVED, self::AUTHORIZED]);
    }

    /**
     * Get badge color styling class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::APPROVED, self::AUTHORIZED => 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/30',
            self::DECLINED => 'bg-rose-500/15 text-rose-600 border border-rose-500/30',
            self::PENDING, self::PENDING_APPROVAL, self::PENDING_ACKNOWLEDGEMENT, self::AWAITING_SIGN_OFF => 'bg-amber-500/15 text-amber-600 border border-amber-500/30',
            self::UNDER_MULTI_UNIT_PROCESSING, self::UNDER_INVESTIGATION => 'bg-indigo-500/15 text-indigo-600 border border-indigo-500/30',
            self::CLOSED => 'bg-slate-500/15 text-slate-600 border border-slate-500/30',
        };
    }
}
