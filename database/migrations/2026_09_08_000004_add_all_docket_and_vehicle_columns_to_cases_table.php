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
        Schema::table('cases', function (Blueprint $table) {
            // Master Case Info
            $table->text('operation_code_name')->nullable()->after('operation_code');
            $table->text('suspect_docket_mode')->nullable()->after('operation_code_name');

            // Suspect Personal Details (3A)
            $table->integer('age')->nullable()->after('dob');
            $table->text('nationality')->nullable()->after('age');
            $table->text('pob')->nullable()->after('nationality');
            $table->text('phone')->nullable()->after('pob');
            $table->text('address')->nullable()->after('phone');

            // Family & Marital Records (3B)
            $table->boolean('has_children')->default(false)->after('marital_status');
            $table->integer('children_count')->nullable()->after('has_children');
            $table->text('spouse_name')->nullable()->after('spouse_info');
            $table->text('spouse_phone')->nullable()->after('spouse_name');
            $table->text('partner_name')->nullable()->after('spouse_phone');
            $table->text('partner_phone')->nullable()->after('partner_name');
            $table->text('partner_info')->nullable()->after('partner_phone');

            // Offence Charges & Custody (3C)
            $table->text('raid_location')->nullable()->after('location');
            $table->text('offense_description')->nullable()->after('description');
            $table->date('arrest_date')->nullable()->after('incident_date');
            $table->text('arrest_time')->nullable()->after('arrest_date');

            // Seizures & Commodities (Section 4)
            $table->boolean('has_seizures')->default(false)->after('exhibits_summary');
            $table->text('seizure_name')->nullable()->after('has_seizures');
            $table->text('seizure_name_drug')->nullable()->after('seizure_name');
            $table->text('seizure_name_currency')->nullable()->after('seizure_name_drug');
            $table->text('seizure_quantity')->nullable()->after('seizure_name_currency');
            $table->text('seizure_unit')->nullable()->after('seizure_quantity');
            $table->text('seizure_value')->nullable()->after('seizure_unit');
            $table->text('seizure_vault')->nullable()->after('seizure_value');
            $table->text('seizure_concealment')->nullable()->after('seizure_vault');
            $table->date('seizure_date')->nullable()->after('seizure_concealment');
            $table->text('seizure_time')->nullable()->after('seizure_date');
            $table->text('seizure_location')->nullable()->after('seizure_time');
            $table->text('seizure_officer')->nullable()->after('seizure_location');
            $table->text('seizure_reason')->nullable()->after('seizure_officer');

            // Vehicle Specification Fields (Section 5)
            $table->text('veh_make')->nullable()->after('seizure_reason');
            $table->text('veh_model')->nullable()->after('veh_make');
            $table->text('veh_type')->nullable()->after('veh_model');
            $table->text('veh_year')->nullable()->after('veh_type');
            $table->text('veh_colour')->nullable()->after('veh_year');
            $table->text('veh_reg_no')->nullable()->after('veh_colour');
            $table->text('veh_vin')->nullable()->after('veh_reg_no');
            $table->text('veh_engine_no')->nullable()->after('veh_vin');
            $table->text('veh_country_reg')->nullable()->after('veh_engine_no');
            $table->text('veh_owner')->nullable()->after('veh_country_reg');
            $table->text('veh_driver')->nullable()->after('veh_owner');
            $table->text('veh_owner_contact')->nullable()->after('veh_driver');
            $table->text('veh_docs_available')->nullable()->after('veh_owner_contact');
            $table->text('veh_doc_ref')->nullable()->after('veh_docs_available');
            $table->text('veh_condition')->nullable()->after('veh_doc_ref');
            $table->text('veh_fuel')->nullable()->after('veh_condition');
            $table->text('veh_keys_recv')->nullable()->after('veh_fuel');
            $table->integer('veh_key_count')->nullable()->after('veh_keys_recv');
            $table->text('veh_damage')->nullable()->after('veh_key_count');
            $table->text('veh_mods')->nullable()->after('veh_damage');
            $table->text('veh_features')->nullable()->after('veh_mods');

            // Evidence Photos
            $table->json('evidence_photos')->nullable()->after('veh_features');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'operation_code_name',
                'suspect_docket_mode',
                'age',
                'nationality',
                'pob',
                'phone',
                'address',
                'has_children',
                'children_count',
                'spouse_name',
                'spouse_phone',
                'partner_name',
                'partner_phone',
                'partner_info',
                'raid_location',
                'offense_description',
                'arrest_date',
                'arrest_time',
                'has_seizures',
                'seizure_name',
                'seizure_name_drug',
                'seizure_name_currency',
                'seizure_quantity',
                'seizure_unit',
                'seizure_value',
                'seizure_vault',
                'seizure_concealment',
                'seizure_date',
                'seizure_time',
                'seizure_location',
                'seizure_officer',
                'seizure_reason',
                'veh_make',
                'veh_model',
                'veh_type',
                'veh_year',
                'veh_colour',
                'veh_reg_no',
                'veh_vin',
                'veh_engine_no',
                'veh_country_reg',
                'veh_owner',
                'veh_driver',
                'veh_owner_contact',
                'veh_docs_available',
                'veh_doc_ref',
                'veh_condition',
                'veh_fuel',
                'veh_keys_recv',
                'veh_key_count',
                'veh_damage',
                'veh_mods',
                'veh_features',
                'evidence_photos'
            ]);
        });
    }
};
