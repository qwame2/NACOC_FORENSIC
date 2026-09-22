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
        Schema::create('single_suspect_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('laboratory_number')->nullable();
            $table->text('title')->nullable();
            $table->string('unit_name')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('commanding_officer')->nullable();
            $table->string('submitting_officer')->nullable();
            
            // Suspect Biodata
            $table->string('suspect_name')->nullable();
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
            $table->string('operation_code')->nullable();
            $table->string('operation_code_name')->nullable();
            $table->text('raid_location')->nullable();
            $table->text('offense_description')->nullable();
            $table->string('arrest_date')->nullable();
            $table->string('arrest_time')->nullable();
            $table->string('incident_date')->nullable();

            // Biometric Mugshots
            $table->text('mugshot_front')->nullable();
            $table->text('mugshot_left')->nullable();
            $table->text('mugshot_right')->nullable();
            $table->json('extra_mugshots')->nullable();

            // Status & Priority
            $table->string('priority')->default('NORMAL');
            $table->string('status')->default('Approved');
            $table->text('hash_log')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('single_suspect_cases');
    }
};
