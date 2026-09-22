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
        Schema::create('laboratory_examination_results', function (Blueprint $table) {
            $table->id();

            // Relational Foreign Keys
            $table->string('case_type')->default('single'); // 'single' or 'multiple'
            $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->cascadeOnDelete();
            $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->cascadeOnDelete();
            $table->foreignId('suspect_id')->nullable()->constrained('case_suspects')->nullOnDelete();
            $table->foreignId('exhibit_id')->nullable()->constrained('exhibits')->nullOnDelete();

            // Lab Docket References & Registration
            $table->string('laboratory_number')->nullable();
            $table->string('exam_reference')->nullable();
            $table->text('analyst')->nullable();
            $table->date('date_received')->nullable();
            $table->date('date_started')->nullable();
            $table->date('date_completed')->nullable();

            // Presumptive & Confirmatory Results
            $table->text('presumptive_result')->nullable();
            $table->string('confirmatory_result')->default('YES');
            
            // Metrology & Mass Measurements
            $table->text('sample_weight_description')->nullable();
            $table->text('sample_weight_units')->nullable();
            $table->text('sample_weight_gross')->nullable();
            $table->text('sample_weight_taken')->nullable();
            $table->text('sample_weight_reserved')->nullable();

            // Confirmatory Spectrometry & Substance Findings
            $table->text('substance_identification')->nullable();
            $table->longText('detected_substances')->nullable();
            $table->text('test_methods')->nullable();
            $table->text('specifications')->nullable();
            $table->text('findings')->nullable();

            $table->boolean('has_saved')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_examination_results');
    }
};
