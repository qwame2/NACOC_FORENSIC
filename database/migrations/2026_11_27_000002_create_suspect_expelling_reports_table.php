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
        if (!Schema::hasTable('suspect_expelling_reports')) {
            Schema::create('suspect_expelling_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('case_suspect_id')->nullable()->index();
                $table->unsignedBigInteger('single_suspect_case_id')->nullable()->index();
                $table->unsignedBigInteger('multiple_suspect_case_id')->nullable()->index();
                $table->unsignedBigInteger('generated_by')->nullable();
                $table->string('case_number')->nullable()->index();
                $table->string('suspect_name');
                $table->string('passport_number')->nullable();
                $table->string('place_of_arrest')->nullable();
                $table->string('date_of_arrest')->nullable();
                $table->string('arrest_officers')->nullable();
                $table->string('flight_number')->nullable();
                $table->string('airline')->nullable();
                $table->string('destination')->nullable();
                $table->string('substance_expelled')->nullable();
                $table->string('report_url')->nullable();
                $table->string('status')->default('Generated');
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspect_expelling_reports');
    }
};

