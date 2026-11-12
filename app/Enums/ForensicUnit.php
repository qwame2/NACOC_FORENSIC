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
            self::LAB_ANALYSIS => 'LAU',
            self::EVIDENCE_MANAGEMENT => 'EMU',
            self::DIGITAL_PHYSICAL_FORENSICS => 'DPFU',
            self::FORENSIC_DIRECTORATE => 'FDept',
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
        if (str_contains($normalized, 'lab') || str_contains($normalized, 'lau') || str_contains($normalized, 'cflu') || str_contains($normalized, 'chemical')) {
            return self::LAB_ANALYSIS;
        }
        if (str_contains($normalized, 'evidence') || str_contains($normalized, 'emu') || str_contains($normalized, 'vault')) {
            return self::EVIDENCE_MANAGEMENT;
        }
        if (str_contains($normalized, 'digital') || str_contains($normalized, 'physical') || str_contains($normalized, 'dpfu') || str_contains($normalized, 'cyber')) {
            return self::DIGITAL_PHYSICAL_FORENSICS;
        }
        if (str_contains($normalized, 'directorate') || str_contains($normalized, 'department') || str_contains($normalized, 'fdept') || str_contains($normalized, 'fdir') || str_contains($normalized, 'head')) {
            return self::FORENSIC_DIRECTORATE;
        }

        return null;
    }

    /**
     * Normalized short route/filter slug for this unit.
     */
    public function slug(): string
    {
        return match ($this) {
            self::CRIME_SCENE_SUPPORT => 'css',
            self::EVIDENCE_MANAGEMENT => 'em',
            self::LAB_ANALYSIS => 'lab',
            self::DIGITAL_PHYSICAL_FORENSICS => 'dpf',
            self::FORENSIC_DIRECTORATE => 'directorate',
        };
    }

    /**
     * Resolve an operational unit slug ('css', 'em', 'lab', 'dpf') from user unit or filter input.
     * Returns null for Forensic Directorate or unrecognized input.
     */
    public static function resolveOperationalSlug(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $normalized = strtolower(trim($input));
        if (in_array($normalized, ['css', 'em', 'lab', 'dpf'], true)) {
            return $normalized;
        }

        $resolved = self::resolve($input);
        if ($resolved === null || $resolved === self::FORENSIC_DIRECTORATE) {
            return null;
        }

        return $resolved->slug();
    }

    /**
     * Keyword terms matching staff unit labels for each operational unit slug.
     *
     * @return array<string, list<string>>
     */
    public static function operationalTermMap(): array
    {
        return [
            'css' => ['crime', 'scene', 'cssu'],
            'em'  => ['evidence', 'vault', 'emu'],
            'lab' => ['lab', 'chemical', 'lau', 'cflu'],
            'dpf' => ['digital', 'physical', 'dpfu', 'cyber'],
        ];
    }

    /**
     * Keyword terms for a specific operational unit slug, or all operational units if null.
     *
     * @return list<string>
     */
    public static function operationalSearchTerms(?string $slug): array
    {
        $map = self::operationalTermMap();

        if ($slug !== null && isset($map[$slug])) {
            return $map[$slug];
        }

        return array_merge(...array_values($map));
    }
}
