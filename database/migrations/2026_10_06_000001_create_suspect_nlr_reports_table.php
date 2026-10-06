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
        Schema::create('suspect_nlr_reports', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
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

            // File Metadata
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('file_extension', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // Report Context
            $table->string('lab_number')->nullable();
            $table->string('case_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            // Indexes
            $table->index('case_suspect_id');
            $table->index('multiple_suspect_case_id');
            $table->index('status');
        });

        // Migrate any existing NLR records from suspect_gsa_reports to suspect_nlr_reports
        if (Schema::hasTable('suspect_gsa_reports')) {
            $existingNlrs = DB::table('suspect_gsa_reports')
                ->where('original_filename', 'LIKE', 'NLR_Report%')
                ->orWhere('file_path', 'LIKE', '%nlr-report-pdf%')
                ->orWhere('notes', 'LIKE', '%NLR%')
                ->get();

            foreach ($existingNlrs as $row) {
                DB::table('suspect_nlr_reports')->insert([
                    'case_suspect_id'          => $row->case_suspect_id,
                    'multiple_suspect_case_id' => $row->multiple_suspect_case_id,
                    'uploaded_by'              => $row->uploaded_by,
                    'original_filename'        => $row->original_filename,
                    'stored_filename'          => $row->stored_filename,
                    'file_path'                => $row->file_path,
                    'file_type'                => $row->file_type,
                    'file_extension'           => $row->file_extension,
                    'file_size'                => $row->file_size,
                    'lab_number'               => $row->lab_number,
                    'case_number'              => $row->case_number,
                    'notes'                    => $row->notes,
                    'status'                   => $row->status ?? 'active',
                    'created_at'               => $row->created_at ?? now(),
                    'updated_at'               => $row->updated_at ?? now(),
                ]);
            }

            DB::table('suspect_gsa_reports')
                ->where('original_filename', 'LIKE', 'NLR_Report%')
                ->orWhere('file_path', 'LIKE', '%nlr-report-pdf%')
                ->orWhere('notes', 'LIKE', '%NLR%')
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspect_nlr_reports');
    }
};
