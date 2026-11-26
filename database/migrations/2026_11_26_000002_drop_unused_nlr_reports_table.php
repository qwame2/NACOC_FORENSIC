<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unused legacy `nlr_reports` table.
     * Active laboratory report attachments and examination results are stored in
     * `suspect_preliminary_reports`, `laboratory_examination_results`, and `reference_registry`.
     */
    public function up(): void
    {
        Schema::dropIfExists('nlr_reports');
    }

    /**
     * Recreate `nlr_reports` for rollback compatibility.
     */
    public function down(): void
    {
        if (!Schema::hasTable('nlr_reports')) {
            Schema::create('nlr_reports', function (Blueprint $table) {
                $table->id();
                $table->string('case_number')->nullable()->index();
                $table->string('laboratory_number')->nullable()->index();
                $table->string('nlr_code')->nullable()->unique();
                $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->nullOnDelete();
                $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->nullOnDelete();
                $table->foreignId('suspect_id')->nullable()->constrained('case_suspects')->nullOnDelete();
                $table->string('analyst')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }
};

