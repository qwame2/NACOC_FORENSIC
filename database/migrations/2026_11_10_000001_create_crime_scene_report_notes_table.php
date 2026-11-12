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
        Schema::create('crime_scene_report_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('officer_name')->nullable();
            $table->text('status')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('conclusion')->nullable();
            $table->string('batch_id')->nullable()->index();
            $table->string('report_type')->default('crime_scene_support')->index();
            $table->string('period')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('search')->nullable();
            $table->string('priority')->nullable();
            $table->string('status_filter')->nullable();
            $table->unsignedInteger('total_cases_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crime_scene_report_notes');
    }
};

