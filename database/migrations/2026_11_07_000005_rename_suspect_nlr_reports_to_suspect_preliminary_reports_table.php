<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename suspect_nlr_reports to suspect_preliminary_reports if needed
        if (Schema::hasTable('suspect_nlr_reports') && !Schema::hasTable('suspect_preliminary_reports')) {
            Schema::rename('suspect_nlr_reports', 'suspect_preliminary_reports');
        } elseif (!Schema::hasTable('suspect_preliminary_reports')) {
            // In case suspect_nlr_reports doesn't exist, create suspect_preliminary_reports
            Schema::create('suspect_preliminary_reports', function (Blueprint $table) {
                $table->id();

                $table->foreignId('case_suspect_id')
                      ->nullable()
                      ->constrained('case_suspects')
                      ->cascadeOnDelete();

                $table->foreignId('multiple_suspect_case_id')
                      ->nullable()
                      ->constrained('multiple_suspect_cases')
                      ->cascadeOnDelete();

                $table->foreignId('single_suspect_case_id')
                      ->nullable()
                      ->constrained('single_suspect_cases')
                      ->cascadeOnDelete();

                $table->foreignId('uploaded_by')
                      ->nullable()
                      ->constrained('users')
                      ->nullOnDelete();

                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->string('file_extension', 10)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();

                $table->string('lab_number')->nullable();
                $table->string('case_number')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('active');

                $table->timestamps();

                $table->index('case_suspect_id');
                $table->index('multiple_suspect_case_id');
                $table->index('status');
            });
        }

        // 2. Create view suspect_nlr_reports -> suspect_preliminary_reports for seamless backward compatibility
        try {
            DB::statement("CREATE OR REPLACE VIEW suspect_nlr_reports AS SELECT * FROM suspect_preliminary_reports");
        } catch (\Throwable $e) {
            // View creation can fail on SQLite memory in some unit tests, which is fine
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("DROP VIEW IF EXISTS suspect_nlr_reports");
        } catch (\Throwable $e) {}

        if (Schema::hasTable('suspect_preliminary_reports') && !Schema::hasTable('suspect_nlr_reports')) {
            Schema::rename('suspect_preliminary_reports', 'suspect_nlr_reports');
        }
    }
};

