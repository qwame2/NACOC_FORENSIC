<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize all case priority values across FIMS tables strictly to 'HIGH' and 'LOW'.
     */
    public function up(): void
    {
        $tables = [
            'single_suspect_cases',
            'multiple_suspect_cases',
            'staff_case_submissions',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'priority')) {
                continue;
            }

            DB::table($table)
                ->whereIn(DB::raw("UPPER(TRIM(COALESCE(priority, '')))"), ['HIGH', 'CRITICAL', 'URGENT'])
                ->update(['priority' => 'HIGH']);

            DB::table($table)
                ->whereNotIn(DB::raw("UPPER(TRIM(COALESCE(priority, '')))"), ['HIGH', 'LOW'])
                ->update(['priority' => 'LOW']);

            DB::table($table)
                ->whereRaw("UPPER(TRIM(COALESCE(priority, ''))) = 'LOW'")
                ->where('priority', '!=', 'LOW')
                ->update(['priority' => 'LOW']);

            DB::table($table)
                ->whereRaw("UPPER(TRIM(COALESCE(priority, ''))) = 'HIGH'")
                ->where('priority', '!=', 'HIGH')
                ->update(['priority' => 'HIGH']);
        }

        if (Schema::hasTable('crime_scene_report_notes') && Schema::hasColumn('crime_scene_report_notes', 'priority')) {
            DB::table('crime_scene_report_notes')
                ->whereNotNull('priority')
                ->whereIn(DB::raw("UPPER(TRIM(priority))"), ['HIGH', 'CRITICAL', 'URGENT'])
                ->update(['priority' => 'HIGH']);

            DB::table('crime_scene_report_notes')
                ->whereNotNull('priority')
                ->whereNotIn(DB::raw("UPPER(TRIM(priority))"), ['HIGH', 'LOW'])
                ->update(['priority' => 'LOW']);
        }
    }

    public function down(): void
    {
        // Irreversible data normalization
    }
};

