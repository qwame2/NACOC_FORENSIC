<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of table => [constraint_name => CHECK SQL expression]
     */
    protected array $constraints = [
        'exhibits' => [
            'chk_exhibits_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
            'chk_exhibits_case_type_alignment' => "case_type IS NULL OR (case_type = 'SINGLE' AND multiple_suspect_case_id IS NULL) OR (case_type = 'MULTIPLE' AND single_suspect_case_id IS NULL)",
        ],
        'laboratory_examination_results' => [
            'chk_lab_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
            'chk_lab_case_type_alignment' => "case_type IS NULL OR (case_type = 'SINGLE' AND multiple_suspect_case_id IS NULL) OR (case_type = 'MULTIPLE' AND single_suspect_case_id IS NULL)",
        ],
        'nlr_reports' => [
            'chk_nlr_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
        ],
        'suspect_nlr_reports' => [
            'chk_suspect_nlr_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
            'chk_suspect_nlr_file_size' => 'file_size IS NULL OR file_size >= 0',
        ],
        'case_handovers' => [
            'chk_handovers_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
        ],
        'dpf_report' => [
            'chk_dpf_case_exclusivity' => 'single_suspect_case_id IS NULL OR multiple_suspect_case_id IS NULL',
        ],
        'single_suspect_cases' => [
            'chk_single_cases_age' => 'age IS NULL OR (age >= 0 AND age <= 150)',
            'chk_single_cases_children' => 'children_count IS NULL OR children_count >= 0',
        ],
        'multiple_suspect_cases' => [
            'chk_multi_cases_counts' => '(total_suspects_count IS NULL OR total_suspects_count >= 0) AND (total_exhibits_count IS NULL OR total_exhibits_count >= 0)',
        ],
        'case_suspects' => [
            'chk_suspects_age' => 'age IS NULL OR (age >= 0 AND age <= 150)',
            'chk_suspects_number' => 'suspect_number IS NULL OR suspect_number > 0',
            'chk_suspects_children' => 'children_count IS NULL OR children_count >= 0',
        ],
        'suspect_gsa_reports' => [
            'chk_gsa_file_size' => 'file_size IS NULL OR file_size >= 0',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->constraints as $table => $rules) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($rules as $constraintName => $expression) {
                try {
                    DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` CHECK ({$expression})");
                } catch (\Throwable $e) {
                    // Log or ignore if constraint already exists on some engines
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->constraints as $table => $rules) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($rules) as $constraintName) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP CONSTRAINT `{$constraintName}`");
                } catch (\Throwable $e) {
                    // Ignore if constraint does not exist
                }
            }
        }
    }
};
