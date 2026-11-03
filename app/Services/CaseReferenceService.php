<?php

namespace App\Services;

use App\Models\AssociateLabExamination;
use App\Models\DpfReport;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\NlrReport;
use App\Models\ReferenceRegistry;
use App\Models\ReferenceSequence;
use App\Models\SingleSuspectCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service for central, atomic, and concurrency-safe generation of forensic examination references,
 * laboratory numbers, and exhibit references.
 * Ensures references follow appropriate existing prefixes (e.g., NLR-FM-T) with dynamically and randomly
 * generated identifiers that are guaranteed unique across all cases and simultaneous creations.
 */
class CaseReferenceService
{
    /**
     * Map a unit name or examination type to its standardized prefix.
     *
     * @param string|null $unit
     * @param string|null $type
     * @return string
     */
    public function resolvePrefixForTypeOrUnit(?string $unit = null, ?string $type = null): string
    {
        $candidate = strtolower(trim(($type ?: '') . ' ' . ($unit ?: '')));

        if (str_contains($candidate, 'digital') || str_contains($candidate, 'dpf') || str_contains($candidate, 'device')) {
            return 'DPF';
        }

        if (str_contains($candidate, 'ballistics') || str_contains($candidate, 'weapon') || str_contains($candidate, 'firearm')) {
            return 'BAL';
        }

        if (str_contains($candidate, 'toxicology')) {
            return 'TOX';
        }

        // Analysis / Examination Reference prefix is ALWAYS NLA (National Laboratory Analysis / NACOC Laboratory Analysis)
        // Never EMU or CSS
        return 'NLA';
    }

    /**
     * Generate a unique, dynamic analysis/examination reference number.
     * Preserves the appropriate prefix (such as NLA-FM-T) while dynamically and randomly
     * generating the identifier portion (e.g., NLA-FM-T2345-F, DPF-FM-T8192-F).
     *
     * @param string|null $unit
     * @param string|null $type
     * @param int|null $year
     * @param string|null $prefix
     * @return string
     */
    protected static ?bool $hasRefRegistryTable = null;
    protected static ?bool $hasNlrReportsTable = null;
    protected static ?bool $hasDpfReportTable = null;

    /**
     * Generate a unique, dynamic analysis/examination reference number.
     * Preserves the appropriate prefix (such as NLA-FM-T) while dynamically and randomly
     * generating the identifier portion (e.g., NLA-FM-T2345-F, DPF-FM-T8192-F).
     *
     * @param string|null $unit
     * @param string|null $type
     * @param int|null $year
     * @param string|null $prefix
     * @param int $recurseLevel
     * @return string
     */
    public function generateExamReference(?string $unit = null, ?string $type = null, ?int $year = null, ?string $prefix = null, int $recurseLevel = 0): string
    {
        $rawPrefix = $prefix ? strtoupper(trim($prefix)) : $this->resolvePrefixForTypeOrUnit($unit, $type);

        if (preg_match('/^([A-Z0-9]+)-FM-T/i', $rawPrefix, $matches)) {
            $cleanPrefix = strtoupper($matches[1]);
        } elseif (str_contains($rawPrefix, '-')) {
            $parts = explode('-', $rawPrefix);
            $cleanPrefix = strtoupper($parts[0]);
        } else {
            $cleanPrefix = $rawPrefix ?: 'NLA';
        }

        // The Analysis / Examination Reference prefix must ALWAYS be NLA-, never EMU- or CSS- (or legacy NLR-)
        if ($cleanPrefix === 'EMU' || $cleanPrefix === 'CSS' || $cleanPrefix === 'NLR' || $cleanPrefix === 'GSA') {
            $cleanPrefix = 'NLA';
        }

        $year = $year ?: (int)date('Y');

        return DB::transaction(function () use ($cleanPrefix, $year, $recurseLevel) {
            $seqName = "exam_reference:{$cleanPrefix}:{$year}";

            $seq = ReferenceSequence::where('name', $seqName)->lockForUpdate()->first();

            if (!$seq) {
                try {
                    $seq = ReferenceSequence::firstOrCreate(
                        ['name' => $seqName],
                        [
                            'category'       => 'exam_reference',
                            'prefix'         => $cleanPrefix,
                            'year'           => $year,
                            'current_number' => 0,
                        ]
                    );
                } catch (\Throwable $e) {
                    $seq = ReferenceSequence::where('name', $seqName)->first();
                }

                if ($seq) {
                    $seq = ReferenceSequence::where('id', $seq->id)->lockForUpdate()->first();
                }
            }

            // Strategy:
            // 1. Try up to 25 random 4-digit numbers (1000..9999).
            // 2. If 25 collisions occur (pool heavily populated), try up to 15 random 5-digit numbers (10000..99999).
            // 3. Fallback: Sequential generation using $seq->current_number + 1 until an unused reference is found.
            $attempt = 0;
            $candidate = '';
            $chosenNumber = 0;

            // Phase 1: 4-digit random
            while ($attempt < 25) {
                $attempt++;
                $chosenNumber = random_int(1000, 9999);
                $candidate = sprintf('%s-FM-T%d-F', $cleanPrefix, $chosenNumber);
                if (!$this->examReferenceExists($candidate)) {
                    break;
                }
                $candidate = '';
            }

            // Phase 2: 5-digit random
            if ($candidate === '') {
                while ($attempt < 40) {
                    $attempt++;
                    $chosenNumber = random_int(10000, 99999);
                    $candidate = sprintf('%s-FM-T%d-F', $cleanPrefix, $chosenNumber);
                    if (!$this->examReferenceExists($candidate)) {
                        break;
                    }
                    $candidate = '';
                }
            }

            // Phase 3: Guaranteed Sequential Fallback
            if ($candidate === '') {
                $baseSeq = $seq ? max((int)$seq->current_number, 1000) : 1000;
                do {
                    $baseSeq++;
                    $chosenNumber = $baseSeq;
                    $candidate = sprintf('%s-FM-T%d-F', $cleanPrefix, $chosenNumber);
                } while ($this->examReferenceExists($candidate));
            }

            // Reserve atomically in reference_registry with unique constraint
            if ($this->hasReferenceRegistryTable()) {
                try {
                    ReferenceRegistry::create([
                        'reference' => $candidate,
                        'category'  => 'exam_reference',
                        'prefix'    => $cleanPrefix,
                        'year'      => $year,
                    ]);
                } catch (QueryException $e) {
                    $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'unique')
                        || str_contains(strtolower($e->getMessage()), 'duplicate')
                        || str_contains(strtolower($e->getMessage()), 'integrity constraint violation')
                        || ($e->errorInfo[1] ?? 0) === 1062;

                    if ($isUniqueViolation && $recurseLevel < 5) {
                        return $this->generateExamReference(null, null, $year, $cleanPrefix, $recurseLevel + 1);
                    }
                    throw $e;
                }
            }

            if ($seq) {
                $seq->current_number = $chosenNumber;
                $seq->save();
            }

            return $candidate;
        });
    }

    /**
     * Check if an examination reference already exists in any table or in the reference registry.
     *
     * @param string $reference
     * @return bool
     */
    public function examReferenceExists(string $reference): bool
    {
        $reference = trim($reference);
        if ($reference === '') {
            return false;
        }

        if (LaboratoryExaminationResult::where('exam_reference', $reference)->exists()) {
            return true;
        }

        if (AssociateLabExamination::where('exam_reference', $reference)->exists()) {
            return true;
        }

        if ($this->hasReferenceRegistryTable()) {
            if (ReferenceRegistry::where('reference', $reference)->exists()) {
                return true;
            }
        }

        if ($this->hasNlrReportsTable()) {
            if (NlrReport::where('nlr_code', $reference)->exists()) {
                return true;
            }
        }

        if ($this->hasDpfReportTable()) {
            if (DpfReport::where('dpf_code', $reference)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function hasReferenceRegistryTable(): bool
    {
        if (static::$hasRefRegistryTable === null) {
            static::$hasRefRegistryTable = Schema::hasTable('reference_registry');
        }
        return static::$hasRefRegistryTable;
    }

    protected function hasNlrReportsTable(): bool
    {
        if (static::$hasNlrReportsTable === null) {
            static::$hasNlrReportsTable = Schema::hasTable('nlr_reports') && Schema::hasColumn('nlr_reports', 'nlr_code');
        }
        return static::$hasNlrReportsTable;
    }

    protected function hasDpfReportTable(): bool
    {
        if (static::$hasDpfReportTable === null) {
            static::$hasDpfReportTable = Schema::hasTable('dpf_report') && Schema::hasColumn('dpf_report', 'dpf_code');
        }
        return static::$hasDpfReportTable;
    }

    /**
     * Normalize an Analysis / Examination Reference so its prefix is always NLA- rather than EMU-, CSS-, NLR-, or GSA-.
     *
     * @param string|null $ref
     * @return string
     */
    public function normalizeExamReference(?string $ref): string
    {
        if (empty($ref) || trim($ref) === '' || trim($ref) === 'N/A') {
            return $this->generateExamReference();
        }

        $trimmed = trim($ref);
        if (preg_match('/^(EMU|CSS|GSA)-/i', $trimmed)) {
            return preg_replace('/^(EMU|CSS|GSA)-/i', 'NLA-', $trimmed);
        }

        return $trimmed;
    }

    /**
     * Resolve the exam reference for a case, strictly ensuring prefix is always NLA-
     * (converting historical EMU-, CSS-, NLR-, or GSA- prefixes) or generating a new dynamic reference.
     *
     * @param ForensicCase|null $case
     * @param array $docketData
     * @param string|null $unit
     * @return string
     */
    public function resolveExamReference(?ForensicCase $case, array $docketData = [], ?string $unit = null): string
    {
        $saved = $docketData['lab_examination']['exam_reference']
            ?? $case?->lab_exam_reference
            ?? $case?->exam_reference;

        if (!empty($saved) && trim($saved) !== '' && trim($saved) !== 'N/A') {
            return $this->normalizeExamReference(trim($saved));
        }

        return $this->generateExamReference($unit ?? $case?->unit_name);
    }

    /**
     * Concurrency-safe execution wrapper for examination creation, retrying on unique collision.
     *
     * @param callable $insert
     * @param string|null $unit
     * @param string|null $type
     * @param int $maxAttempts
     * @param string|null $preferredReference
     * @return mixed
     */
    public function withUniqueRetry(callable $insert, ?string $unit = null, ?string $type = null, int $maxAttempts = 10, ?string $preferredReference = null)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $ref = ($attempt === 1 && !empty($preferredReference) && !$this->examReferenceExists($preferredReference))
                    ? $preferredReference
                    : $this->generateExamReference($unit, $type);

                return $insert($ref);
            } catch (QueryException $e) {
                $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'unique')
                    || str_contains(strtolower($e->getMessage()), 'duplicate')
                    || str_contains(strtolower($e->getMessage()), 'integrity constraint violation')
                    || ($e->errorInfo[1] ?? 0) === 1062;

                if (!$isUniqueViolation || $attempt === $maxAttempts) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Generate a unique, sequential laboratory number.
     * Format: Lab No. {NNNN}/FS{MM}/{YYYY} (e.g. Lab No. 1001/FS10/2026)
     *
     * @param string|null $month
     * @param int|null $year
     * @return string
     */
    public function generateLabNumber(?string $month = null, ?int $year = null): string
    {
        $month = $month ?: date('m');
        $year  = $year ?: (int)date('Y');

        return DB::transaction(function () use ($month, $year) {
            $seqName = "lab_number:FS:{$year}";

            $seq = ReferenceSequence::where('name', $seqName)->lockForUpdate()->first();

            if (!$seq) {
                $base = config('forensic.lab_number_start', 1000);

                try {
                    $seq = ReferenceSequence::create([
                        'name'           => $seqName,
                        'category'       => 'lab_number',
                        'prefix'         => 'FS',
                        'year'           => $year,
                        'current_number' => $base - 1,
                    ]);
                } catch (\Throwable $e) {
                    $seq = ReferenceSequence::where('name', $seqName)->first();
                }

                if ($seq) {
                    $seq = ReferenceSequence::where('id', $seq->id)->lockForUpdate()->first();
                }
            }

            do {
                if ($seq) {
                    $seq->current_number++;
                    $candidate = sprintf('Lab No. %d/FS%s/%d', $seq->current_number, $month, $year);
                } else {
                    $candidate = sprintf('Lab No. %d/FS%s/%d', random_int(1000, 9999), $month, $year);
                }
            } while ($this->labNumberExists($candidate));

            if ($seq) {
                $seq->save();
            }

            return $candidate;
        });
    }

    /**
     * Check if a laboratory number already exists.
     *
     * @param string $labNumber
     * @return bool
     */
    public function labNumberExists(string $labNumber): bool
    {
        $labNumber = trim($labNumber);
        if ($labNumber === '') {
            return false;
        }

        if (SingleSuspectCase::where('laboratory_number', $labNumber)->exists()) {
            return true;
        }

        if (MultipleSuspectCase::where('laboratory_number', $labNumber)->exists()) {
            return true;
        }

        if (LaboratoryExaminationResult::where('laboratory_number', $labNumber)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Compute the next exhibit reference number for a case.
     *
     * @param string $caseNumber
     * @param int $existingExhibitCount
     * @return string
     */
    public function nextExhibitReference(string $caseNumber, int $existingExhibitCount): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $caseNumber);
        $next  = max(1, $existingExhibitCount + 1);
        return 'EXH-' . $clean . '-' . str_pad((string)$next, 2, '0', STR_PAD_LEFT);
    }
}
