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
        Schema::create('drug_screening', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suspect_id')->constrained('case_suspects')->cascadeOnDelete();
            $table->string('purpose');
            $table->date('screening_date');
            $table->string('result')->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_screening');
    }
};
