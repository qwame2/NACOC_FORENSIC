<?php

namespace App\Services;

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\ReferenceRegistry;
use App\Models\ReferenceSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service for central, atomic, randomly generated, collision-resistant case reference number generation.
 * Generates human-readable references in format: NCC-YYYY-NNNN (e.g., NCC-2026-2345).
 * Uses database transactions with exclusive row locks on `reference_sequences` and atomic reservation
 * on `reference_registry` to guarantee concurrency safety and reference uniqueness across simultaneous creations.
 */
class CaseNumberService
{
    protected static ?bool $hasRefRegistryTable = null;

    /**
     * Generate a unique, randomly generated case reference number.
     * Format: NCC-YYYY-NNNN (e.g. NCC-2026-2345)
     *
     * @param string $prefix Fixed prefix 'NCC'
     * @param int|null $year Dynamic current year if not provided
     * @param int $recurseLevel
     * @return string
     */
    public function generate(string $prefix = 'NCC', ?int $year = null, int $recurseLevel = 0): string
    {
        $year = $year ?: (int)date('Y');
        $cleanPrefix = 'NCC'; // NCC is the fixed prefix

        return DB::transaction(function () use ($cleanPrefix, $year, $recurseLevel) {
            $seqName = "case_number:{$cleanPrefix}:{$year}";

            // Acquire exclusive row lock to serialize concurrent case number requests
            $seq = ReferenceSequence::where('name', $seqName)->lockForUpdate()->first();

            if (!$seq) {
                try {
                    $seq = ReferenceSequence::firstOrCreate(
                        ['name' => $seqName],
                        [
                            'category'       => 'case_number',
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
            // 2. If 25 collisions occur, try up to 15 random 5-digit numbers (10000..99999).
            // 3. Fallback: Sequential generation using $seq->current_number + 1 until an unused reference is found.
            $attempt = 0;
            $candidate = '';
            $chosenDigits = 0;

            // Phase 1: 4-digit random
            while ($attempt < 25) {
                $attempt++;
                $chosenDigits = random_int(1000, 9999);
                $candidate = sprintf('%s-%04d-%04d', $cleanPrefix, $year, $chosenDigits);
                if (!$this->caseNumberExists($candidate)) {
                    break;
                }
                $candidate = '';
            }

            // Phase 2: 5-digit random
            if ($candidate === '') {
                while ($attempt < 40) {
                    $attempt++;
                    $chosenDigits = random_int(10000, 99999);
                    $candidate = sprintf('%s-%04d-%05d', $cleanPrefix, $year, $chosenDigits);
                    if (!$this->caseNumberExists($candidate)) {
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
                    $chosenDigits = $baseSeq;
                    $candidate = sprintf('%s-%04d-%04d', $cleanPrefix, $year, $chosenDigits);
                } while ($this->caseNumberExists($candidate));
            }

            // Reserve atomically in reference_registry with unique constraint
            if ($this->hasReferenceRegistryTable()) {
                try {
                    ReferenceRegistry::create([
                        'reference' => $candidate,
                        'category'  => 'case_number',
                        'prefix'    => $cleanPrefix,
                        'year'      => $year,
                    ]);
                } catch (QueryException $e) {
                    $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'unique')
                        || str_contains(strtolower($e->getMessage()), 'duplicate')
                        || str_contains(strtolower($e->getMessage()), 'integrity constraint violation')
                        || ($e->errorInfo[1] ?? 0) === 1062;

                    if ($isUniqueViolation && $recurseLevel < 5) {
                        return $this->generate($cleanPrefix, $year, $recurseLevel + 1);
                    }
                    throw $e;
                }
            }

            if ($seq) {
                $seq->current_number = $chosenDigits;
                $seq->save();
            }

            return $candidate;
        });
    }

    /**
     * Check if a case number exists in any case table or in the central reference registry.
     *
     * @param string $caseNumber
     * @return bool
     */
    public function caseNumberExists(string $caseNumber): bool
    {
        $caseNumber = trim($caseNumber);
        if ($caseNumber === '') {
            return false;
        }

        if (SingleSuspectCase::where('case_number', $caseNumber)->exists()) {
            return true;
        }

        if (MultipleSuspectCase::where('case_number', $caseNumber)->exists()) {
            return true;
        }

        if ($this->hasReferenceRegistryTable()) {
            if (ReferenceRegistry::where('reference', $caseNumber)->exists()) {
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

    /**
     * Attempt an insert callable with a case_number, retrying on unique violation.
     *
     * @param callable $insert
     * @param string $prefix
     * @param int $maxAttempts
     * @param string|null $preferredNumber
     * @return mixed
     */
    public function withUniqueRetry(callable $insert, string $prefix = 'NCC', int $maxAttempts = 10, ?string $preferredNumber = null)
    {
        $prefix = 'NCC';
        $currentYear = (int)date('Y');
        $validPattern = '/^NCC-' . $currentYear . '-\d{4}$/';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $caseNumber = ($attempt === 1 && !empty($preferredNumber) && !$this->caseNumberExists($preferredNumber))
                    ? $preferredNumber
                    : $this->generate($prefix);

                $result = $insert($caseNumber);
                ReferenceRegistry::reserve($caseNumber, 'CASE', SingleSuspectCase::class, null);
                return $result;
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
}
