<?php

namespace App\Services;

use App\Models\ForensicCase;

class CaseReferenceService
{
    /**
     * WHY: Prior code used rand(10,99) for lab number suffixes, giving only
     * 90 possible values per month — collisions at scale. Uses crypto-random
     * 4-hex suffix with DB uniqueness check, matching CaseNumberService.
     */
    public function generateLabNumber(): string
    {
        $month = date('m');
        $year  = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $suffix = strtoupper(bin2hex(random_bytes(2))); // 4 hex chars
            $candidate = "Lab No. {$suffix}/FS{$month}/{$year}";
            $exists = ForensicCase::where('laboratory_number', $candidate)->exists();
            if (!$exists) return $candidate;
        }
        return 'Lab No. ' . now()->format('Hisv') . "/FS{$month}/{$year}";
    }

    /**
     * Resolve the exam reference for a case, preferring saved data.
     * WHY: Prior code hardcoded 'NLR-FM-T35-F' as the exam reference,
     * ignoring any saved exam_reference in docket_data. This overwrote
     * real references on existing cases.
     */
    public function resolveExamReference(?ForensicCase $case, array $docketData): string
    {
        return $docketData['lab_examination']['exam_reference']
            ?? $case?->lab_exam_reference
            ?? 'NLR-FM-T35-F'; // Form template code — acceptable fallback
    }

    /**
     * Compute the next exhibit reference number for a case.
     * WHY: Prior code always used '-01', resetting display to the first
     * exhibit even when earlier exhibits existed. This caused users to
     * edit the wrong exhibit number.
     */
    public function nextExhibitReference(string $caseNumber, int $existingExhibitCount): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $caseNumber);
        $next  = max(1, $existingExhibitCount + 1);
        return 'EXH-' . $clean . '-' . str_pad((string)$next, 2, '0', STR_PAD_LEFT);
    }
}
