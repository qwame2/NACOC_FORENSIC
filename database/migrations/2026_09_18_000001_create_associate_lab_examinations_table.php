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
        Schema::create('associate_lab_examinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->string('suspect_name');
            $table->string('associate_id')->nullable();
            $table->string('exhibit_number')->nullable();
            $table->string('laboratory_number')->nullable();
            $table->string('exam_reference')->nullable();
            $table->string('lab_unit')->nullable();
            $table->string('analyst')->nullable();
            $table->date('date_received')->nullable();
            $table->date('date_started')->nullable();
            $table->date('date_completed')->nullable();
            $table->text('presumptive_result')->nullable();
            $table->string('confirmatory_result')->nullable();
            $table->text('sample_weight_description')->nullable();
            $table->string('sample_weight_units')->nullable();
            $table->string('sample_weight_gross')->nullable();
            $table->string('sample_weight_taken')->nullable();
            $table->string('sample_weight_reserved')->nullable();
            $table->string('substance_identification')->nullable();
            $table->json('detected_substances')->nullable();
            $table->text('test_methods')->nullable();
            $table->text('specifications')->nullable();
            $table->text('findings')->nullable();
            $table->boolean('has_saved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('associate_lab_examinations');
    }
};
