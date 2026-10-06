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
        Schema::create('nlr_reports', function (Blueprint $table) {
            $table->id();

            // Case / Lab Identifiers
            $table->string('case_number')->nullable()->index();
            $table->string('laboratory_number')->nullable()->index();
            $table->string('nlr_code')->nullable();
            $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->cascadeOnDelete();
            $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->cascadeOnDelete();
            $table->foreignId('suspect_id')->nullable()->constrained('case_suspects')->nullOnDelete();

            // NLR Report Form Data
            $table->longText('detailed_exhibit_description')->nullable();
            $table->string('condition_of_reserved_sample')->nullable();
            $table->string('analyst')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nlr_reports');
    }
};
