<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the `suspect_gsa_reports` table.
     * Links each uploaded GSA certificate file to a specific suspect (case_suspects)
     * and its parent case (multiple_suspect_cases).
     */
    public function up(): void
    {
        Schema::create('suspect_gsa_reports', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('case_suspect_id')
                  ->constrained('case_suspects')
                  ->cascadeOnDelete()
                  ->comment('Suspect this GSA report belongs to');

            $table->foreignId('multiple_suspect_case_id')
                  ->constrained('multiple_suspect_cases')
                  ->cascadeOnDelete()
                  ->comment('Parent case dossier');

            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Lab officer who uploaded the file');

            // File Metadata
            $table->string('original_filename')->comment('Original filename from the user');
            $table->string('stored_filename')->comment('Hashed unique filename on disk');
            $table->string('file_path')->comment('Relative storage path');
            $table->string('file_type')->nullable()->comment('MIME type');
            $table->string('file_extension', 10)->nullable()->comment('jpg|png|webp|pdf|docx|csv');
            $table->unsignedBigInteger('file_size')->nullable()->comment('Size in bytes');

            // Report Context
            $table->string('lab_number')->nullable();
            $table->string('case_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->comment('active|archived|deleted');

            $table->timestamps();

            // Indexes
            $table->index('case_suspect_id');
            $table->index('multiple_suspect_case_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspect_gsa_reports');
    }
};
