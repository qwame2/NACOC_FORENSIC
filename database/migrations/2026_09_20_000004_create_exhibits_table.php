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
        Schema::create('exhibits', function (Blueprint $table) {
            $table->id();
            
            // Relational Foreign Keys
            $table->string('case_type')->default('single'); // 'single' or 'multiple'
            $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->cascadeOnDelete();
            $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->cascadeOnDelete();
            $table->foreignId('suspect_id')->nullable()->constrained('case_suspects')->nullOnDelete();

            $table->string('exhibit_number')->nullable();
            $table->string('exhibit_category')->default('Drugs'); // Drugs, Vehicles, Firearms, Currency, Electronics, General
            $table->text('commodity_name')->nullable();
            
            // Quantity & Metrology
            $table->text('seizure_quantity')->nullable();
            $table->text('seizure_packaging')->nullable();
            $table->text('seizure_weight')->nullable();
            $table->text('seizure_unit')->nullable();
            $table->text('seizure_value')->nullable();
            $table->text('seizure_concealment')->nullable();
            
            // Seizure Action Metadata
            $table->string('seizure_date')->nullable();
            $table->string('seizure_time')->nullable();
            $table->text('seizure_location')->nullable();
            $table->text('seizure_officer')->nullable();
            $table->text('seizure_reason')->nullable();

            // Vehicle Category Specific Specs
            $table->text('veh_make')->nullable();
            $table->text('veh_model')->nullable();
            $table->text('veh_type')->nullable();
            $table->text('veh_year')->nullable();
            $table->text('veh_colour')->nullable();
            $table->text('veh_reg_no')->nullable();
            $table->text('veh_vin')->nullable();
            $table->text('veh_engine_no')->nullable();
            $table->text('veh_country_reg')->nullable();
            $table->text('veh_owner')->nullable();
            $table->text('veh_driver')->nullable();

            // Weapon Category Specific Specs
            $table->text('wpn_model')->nullable();
            $table->text('wpn_serial_no')->nullable();
            $table->text('wpn_calibre')->nullable();
            $table->text('wpn_colour')->nullable();
            $table->text('wpn_size')->nullable();

            // Device Category Specific Specs
            $table->text('dev_model')->nullable();
            $table->text('dev_serial_no')->nullable();
            $table->text('dev_imei1')->nullable();
            $table->text('dev_imei2')->nullable();
            $table->text('dev_mac_address')->nullable();

            // Media Evidence Photos (base64 images can be very large)
            $table->longText('evidence_photos')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibits');
    }
};
