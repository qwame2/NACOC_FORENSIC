<?php

namespace App\Enums;

enum ScreeningResult: string
{
    case POSITIVE = 'Positive';
    case NEGATIVE = 'Negative';
    case PENDING = 'Pending';

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
     * Get display badge styling class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::POSITIVE => 'bg-rose-500/15 text-rose-600 border border-rose-500/30',
            self::NEGATIVE => 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/30',
            self::PENDING => 'bg-amber-500/15 text-amber-600 border border-amber-500/30',
        };
    }
}
