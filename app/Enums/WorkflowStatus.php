<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';

    /**
     * Get all values for this enum.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
