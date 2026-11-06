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
        Schema::create('drug_screening_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_screening_id')->constrained('drug_screening')->cascadeOnDelete();
            $table->string('medication_name', 255);
            $table->string('dosage', 100)->nullable();
            $table->string('form', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('route', 100)->nullable();
            $table->enum('prescription_source', ['doctor', 'otc', 'self_administered'])->nullable();
            $table->date('date_started')->nullable();
            $table->string('duration_ongoing', 100)->nullable();
            $table->dateTime('last_dose_taken')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_screening_medications');
    }
};

