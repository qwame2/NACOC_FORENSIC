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
        Schema::create('case_suspects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('multiple_suspect_case_id')->constrained('multiple_suspect_cases')->cascadeOnDelete();
            
            $table->integer('suspect_number')->default(1);
            $table->string('role')->default('Primary Suspect'); // Primary Suspect, Co-Suspect, Associate Suspect
            $table->string('associate_id')->nullable();

            // Suspect Biodata
            $table->string('suspect_name');
            $table->string('suspect_alias')->nullable();
            $table->string('ghana_card')->nullable();
            $table->string('gender')->nullable()->default('Male');
            $table->string('dob')->nullable();
            $table->string('age')->nullable();
            $table->string('height')->nullable();
            $table->string('height_unit')->nullable()->default('CM');
            $table->string('eye_color')->nullable();
            $table->string('nationality')->nullable()->default('Ghanaian');
            $table->string('pob')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('marital_status')->nullable();
            $table->boolean('has_children')->default(false);
            $table->string('children_count')->nullable()->default('0');
            $table->text('spouse_info')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_phone')->nullable();
            $table->string('partner_name')->nullable();
            $table->string('partner_phone')->nullable();
            $table->text('partner_info')->nullable();
            $table->text('dependents')->nullable();
            $table->string('holding_facility')->nullable();

            // Arrest & Operation Details
            $table->text('raid_location')->nullable();
            $table->text('offense_description')->nullable();
            $table->string('arrest_date')->nullable();
            $table->string('arrest_time')->nullable();

            // Biometric Mugshots (Separate columns with JSON fallback for extra angles)
            $table->text('mugshot_front')->nullable();
            $table->text('mugshot_left')->nullable();
            $table->text('mugshot_right')->nullable();
            $table->json('extra_mugshots')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_suspects');
    }
};
