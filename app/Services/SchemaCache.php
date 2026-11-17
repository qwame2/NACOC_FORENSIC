<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Service to cache table schema column listings.
 * WHY: Schema::hasColumn() runs a SHOW COLUMNS query every call (~5-20ms each).
 * CaseController calls it dozens of times per request across store(), updateCase(),
 * and updateUnitProgress(). Caching columns per table drops 100-500ms per request.
 */
class SchemaCache
{
    /**
     * Check if table has a column using cached column listing.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function hasColumn(string $table, string $column): bool
    {
        try {
            $columns = Cache::remember("schema.columns.{$table}", 3600, function () use ($table) {
                return Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
            });
        } catch (\Throwable $e) {
            $columns = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return in_array($column, $columns, true);
    }

    /**
     * Flush cached schema listings.
     *
     * @param string|null $table
     * @return void
     */
    public static function flush(?string $table = null): void
    {
        if ($table) {
            Cache::forget("schema.columns.{$table}");
        } else {
            $knownTables = [
                'cases',
                'multiple_suspect_cases',
                'single_suspect_cases',
                'exhibits',
                'case_suspects',
                'suspect_gsa_reports',
                'suspect_nlr_reports',
                'suspect_preliminary_reports',
                'users',
                'units',
                'case_handovers',
                'audit_logs',
                'drug_screenings',
            ];
            foreach ($knownTables as $tbl) {
                Cache::forget("schema.columns.{$tbl}");
            }
        }
    }

    /**
     * Flush all schema caches.
     *
     * @return void
     */
    public static function flushAll(): void
    {
        self::flush();
    }
}
