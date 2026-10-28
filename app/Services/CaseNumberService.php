<?php

namespace App\Services;

use App\Models\ForensicCase;
use Illuminate\Database\QueryException;

/**
 * Service for atomic and collision-resistant case number generation.
 * WHY: The previous rand(1000,9999) loop could infinite-loop after ~9,000 cases
 * in a year, and concurrent requests could both pass the exists() check and
 * insert the same case_number (race condition).
 * Uses crypto-random suffix + DB UNIQUE constraint retry to guarantee uniqueness.
 */
class CaseNumberService
{
    /**
     * Generate a unique case number.
     *
     * @param string $prefix
     * @return string
     */
    public function generate(string $prefix = 'NCC'): string
    {
        $year = date('Y');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars = 4.3B values
            $candidate = "{$prefix}-{$year}-{$suffix}";
            if (!ForensicCase::where('case_number', $candidate)->exists()) {
                return $candidate;
            }
        }
        // Fallback: timestamp + random for astronomically rare collisions
        return "{$prefix}-{$year}-" . now()->format('Hisv') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Attempt to insert with a case_number, retrying on unique violation.
     *
     * @param callable $insert
     * @param string $prefix
     * @param int $maxAttempts
     * @param string|null $preferredNumber
     * @return mixed
     */
    public function withUniqueRetry(callable $insert, string $prefix = 'NCC', int $maxAttempts = 3, ?string $preferredNumber = null)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $caseNumber = ($attempt === 1 && !empty($preferredNumber))
                    ? $preferredNumber
                    : $this->generate($prefix);

                return $insert($caseNumber);
            } catch (QueryException $e) {
                $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'unique')
                    || str_contains(strtolower($e->getMessage()), 'duplicate')
                    || str_contains(strtolower($e->getMessage()), 'integrity constraint violation');

                if (!$isUniqueViolation || $attempt === $maxAttempts) {
                    throw $e;
                }
                usleep(50_000 * $attempt); // 50ms, 100ms backoff
            }
        }
    }
}
