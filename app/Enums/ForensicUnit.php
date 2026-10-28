<?php

namespace App\Enums;

enum ForensicUnit: string
{
    case CRIME_SCENE_SUPPORT = 'Crime Scene Support';
    case LAB_ANALYSIS = 'Lab Analysis';
    case EVIDENCE_MANAGEMENT = 'Evidence Management';
    case DIGITAL_PHYSICAL_FORENSICS = 'Digital & Physical Forensics';
    case FORENSIC_DIRECTORATE = 'Forensic Directorate';

    /**
     * Get all unit names as string values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get official unit abbreviation / code.
     */
    public function code(): string
    {
        return match ($this) {
            self::CRIME_SCENE_SUPPORT => 'CSSU',
            self::LAB_ANALYSIS => 'CFLU',
            self::EVIDENCE_MANAGEMENT => 'EMU',
            self::DIGITAL_PHYSICAL_FORENSICS => 'DPFU',
            self::FORENSIC_DIRECTORATE => 'FDIR',
        };
    }

    /**
     * Resolve unit from code or substring name.
     */
    public static function resolve(?string $input): ?self
    {
        if (empty($input)) {
            return null;
        }

        $normalized = strtolower(trim($input));

        foreach (self::cases() as $case) {
            if (strtolower($case->value) === $normalized || strtolower($case->code()) === $normalized) {
                return $case;
            }
        }

        if (str_contains($normalized, 'crime') || str_contains($normalized, 'field') || str_contains($normalized, 'cssu')) {
            return self::CRIME_SCENE_SUPPORT;
        }
        if (str_contains($normalized, 'lab') || str_contains($normalized, 'cflu') || str_contains($normalized, 'chemical')) {
            return self::LAB_ANALYSIS;
        }
        if (str_contains($normalized, 'evidence') || str_contains($normalized, 'emu') || str_contains($normalized, 'vault')) {
            return self::EVIDENCE_MANAGEMENT;
        }
        if (str_contains($normalized, 'digital') || str_contains($normalized, 'physical') || str_contains($normalized, 'dpfu') || str_contains($normalized, 'cyber')) {
            return self::DIGITAL_PHYSICAL_FORENSICS;
        }
        if (str_contains($normalized, 'directorate') || str_contains($normalized, 'head')) {
            return self::FORENSIC_DIRECTORATE;
        }

        return null;
    }
}
