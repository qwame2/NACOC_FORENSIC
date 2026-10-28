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
        Schema::table('exhibits', function (Blueprint $table) {
            if (!Schema::hasColumn('exhibits', 'veh_owner_contact')) {
                $table->string('veh_owner_contact')->nullable()->after('veh_driver');
            }
            if (!Schema::hasColumn('exhibits', 'veh_docs_available')) {
                $table->string('veh_docs_available')->nullable()->after('veh_owner_contact');
            }
            if (!Schema::hasColumn('exhibits', 'veh_doc_ref')) {
                $table->string('veh_doc_ref')->nullable()->after('veh_docs_available');
            }
            if (!Schema::hasColumn('exhibits', 'veh_condition')) {
                $table->string('veh_condition')->nullable()->after('veh_doc_ref');
            }
            if (!Schema::hasColumn('exhibits', 'veh_fuel')) {
                $table->string('veh_fuel')->nullable()->after('veh_condition');
            }
            if (!Schema::hasColumn('exhibits', 'veh_keys_recv')) {
                $table->string('veh_keys_recv')->nullable()->after('veh_fuel');
            }
            if (!Schema::hasColumn('exhibits', 'veh_key_count')) {
                $table->string('veh_key_count')->nullable()->after('veh_keys_recv');
            }
            if (!Schema::hasColumn('exhibits', 'veh_damage')) {
                $table->text('veh_damage')->nullable()->after('veh_key_count');
            }
            if (!Schema::hasColumn('exhibits', 'veh_mods')) {
                $table->text('veh_mods')->nullable()->after('veh_damage');
            }
            if (!Schema::hasColumn('exhibits', 'veh_features')) {
                $table->text('veh_features')->nullable()->after('veh_mods');
            }

            if (!Schema::hasColumn('exhibits', 'wpn_marks')) {
                $table->text('wpn_marks')->nullable()->after('wpn_size');
            }
            if (!Schema::hasColumn('exhibits', 'wpn_location_found')) {
                $table->string('wpn_location_found')->nullable()->after('wpn_marks');
            }
            if (!Schema::hasColumn('exhibits', 'wpn_loaded_status')) {
                $table->string('wpn_loaded_status')->nullable()->after('wpn_location_found');
            }
            if (!Schema::hasColumn('exhibits', 'wpn_condition')) {
                $table->string('wpn_condition')->nullable()->after('wpn_loaded_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            $columnsToDrop = [
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
                'wpn_marks',
                'wpn_location_found',
                'wpn_loaded_status',
                'wpn_condition',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('exhibits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
