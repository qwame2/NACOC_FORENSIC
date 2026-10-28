<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'cases'                  => 'cases_case_number_unique',
            'multiple_suspect_cases' => 'multiple_suspect_cases_case_number_unique',
            'single_suspect_cases'   => 'single_suspect_cases_case_number_unique',
        ];

        foreach ($tables as $table => $indexName) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'case_number')) {
                if (!Schema::hasIndex($table, $indexName)) {
                    Schema::table($table, function (Blueprint $t) use ($indexName) {
                        $t->unique('case_number', $indexName);
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'cases'                  => 'cases_case_number_unique',
            'multiple_suspect_cases' => 'multiple_suspect_cases_case_number_unique',
            'single_suspect_cases'   => 'single_suspect_cases_case_number_unique',
        ];

        foreach ($tables as $table => $indexName) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'case_number')) {
                if (Schema::hasIndex($table, $indexName)) {
                    Schema::table($table, function (Blueprint $t) use ($indexName) {
                        $t->dropUnique($indexName);
                    });
                }
            }
        }
    }
};
