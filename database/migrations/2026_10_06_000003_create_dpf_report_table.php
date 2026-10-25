<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for the `dpf_report` table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('dpf_report')) {
            Schema::create('dpf_report', function (Blueprint $table) {
                $table->id();

                // Identifiers & References
                $table->string('dpf_code')->nullable()->index();
                $table->string('case_number')->nullable()->index();
                $table->string('laboratory_number')->nullable()->index();
                $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->cascadeOnDelete();
                $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->cascadeOnDelete();
                $table->foreignId('suspect_id')->nullable()->constrained('case_suspects')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // Location, Dates & Exhibits Info
                $table->string('location_post')->nullable();
                $table->date('investigation_date')->nullable();
                $table->text('devices_retrieved')->nullable();
                $table->string('quantity')->nullable();
                $table->longText('brief_case_description')->nullable();

                // Digital Forensic Investigation Status & Controls
                $table->string('court_order_requested')->default('YES');
                $table->string('court_order_granted')->default('YES');
                $table->text('court_order_file')->nullable();
                $table->text('extraction_analysis_conducted')->nullable();
                $table->text('extraction_analysis_message')->nullable();
                $table->text('report_witness_statement_sent')->nullable();
                $table->text('report_witness_statement_message')->nullable();
                $table->text('report_witness_statement_file')->nullable();
                $table->string('intel_shared')->default('YES');

                // Technical Extraction & Evidence Attributes
                $table->string('write_blocker_used')->nullable();
                $table->string('hash_checksum')->nullable();
                $table->string('extraction_tools_used')->nullable();
                $table->longText('findings_summary')->nullable();
                $table->text('remark')->nullable();
                $table->json('report_data')->nullable();

                $table->string('status')->default('active')->index();
                $table->timestamps();
            });
        }

        // Also support dpf_reports plural alias if queried directly
        try {
            DB::statement('DROP VIEW IF EXISTS dpf_reports');
            DB::statement('CREATE VIEW dpf_reports AS SELECT * FROM dpf_report');
        } catch (\Throwable $e) {
            // If views aren't supported or fail, create table mirror only if it does not already exist
            try {
                if (!Schema::hasTable('dpf_reports')) {
                    Schema::create('dpf_reports', function (Blueprint $table) {
                    $table->id();
                    $table->string('dpf_code')->nullable()->index();
                    $table->string('case_number')->nullable()->index();
                    $table->string('laboratory_number')->nullable()->index();
                    $table->foreignId('single_suspect_case_id')->nullable();
                    $table->foreignId('multiple_suspect_case_id')->nullable();
                    $table->foreignId('suspect_id')->nullable();
                    $table->foreignId('user_id')->nullable();
                    $table->string('location_post')->nullable();
                    $table->date('investigation_date')->nullable();
                    $table->text('devices_retrieved')->nullable();
                    $table->string('quantity')->nullable();
                    $table->longText('brief_case_description')->nullable();
                    $table->string('court_order_requested')->default('YES');
                    $table->string('court_order_granted')->default('YES');
                    $table->text('court_order_file')->nullable();
                    $table->text('extraction_analysis_conducted')->nullable();
                    $table->text('extraction_analysis_message')->nullable();
                    $table->text('report_witness_statement_sent')->nullable();
                    $table->text('report_witness_statement_message')->nullable();
                    $table->text('report_witness_statement_file')->nullable();
                    $table->string('intel_shared')->default('YES');
                    $table->string('write_blocker_used')->nullable();
                    $table->string('hash_checksum')->nullable();
                    $table->string('extraction_tools_used')->nullable();
                    $table->longText('findings_summary')->nullable();
                    $table->text('remark')->nullable();
                    $table->json('report_data')->nullable();
                    $table->string('status')->default('active')->index();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $inner) {}
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP VIEW IF EXISTS dpf_reports');
        } catch (\Throwable $e) {}

        Schema::dropIfExists('dpf_reports');
        Schema::dropIfExists('dpf_report');
    }
};
