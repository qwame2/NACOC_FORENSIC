<?php

namespace App\Enums;

enum UnitProgressStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case NOT_APPLICABLE = 'N/A';

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
     * Determine if stage is completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Get badge styling class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::COMPLETED => 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/30',
            self::IN_PROGRESS => 'bg-blue-500/15 text-blue-600 border border-blue-500/30',
            self::PENDING => 'bg-amber-500/15 text-amber-600 border border-amber-500/30',
            self::NOT_APPLICABLE => 'bg-slate-500/15 text-slate-500 border border-slate-500/20',
        };
    }
}
