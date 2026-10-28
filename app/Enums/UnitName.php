<?php

namespace App\Enums;

enum UnitName: string
{
    case LAB_ANALYSIS = 'Lab Analysis';
    case EVIDENCE_MANAGEMENT = 'Evidence Management';
    case DIGITAL_PHYSICAL_FORENSICS = 'Digital & Physical Forensics';
    case CRIME_SCENE_SUPPORT = 'Crime Scene Support';

    /**
     * Get all unit name values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
