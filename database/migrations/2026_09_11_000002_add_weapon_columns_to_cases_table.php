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
            $table->string('wpn_make')->nullable()->after('veh_features');
            $table->string('wpn_model')->nullable()->after('wpn_make');
            $table->string('wpn_serial_no')->nullable()->after('wpn_model');
            $table->string('wpn_calibre')->nullable()->after('wpn_serial_no');
            $table->string('wpn_colour')->nullable()->after('wpn_calibre');
            $table->string('wpn_size')->nullable()->after('wpn_colour');
            $table->text('wpn_marks')->nullable()->after('wpn_size');
            $table->string('wpn_location_found')->nullable()->after('wpn_marks');
            $table->string('wpn_loaded_status')->nullable()->after('wpn_location_found');
            $table->string('wpn_condition')->nullable()->after('wpn_loaded_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'wpn_make',
                'wpn_model',
                'wpn_serial_no',
                'wpn_calibre',
                'wpn_colour',
                'wpn_size',
                'wpn_marks',
                'wpn_location_found',
                'wpn_loaded_status',
                'wpn_condition',
            ]);
        });
    }
};
