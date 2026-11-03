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
            if (!Schema::hasColumn('exhibits', 'property_id')) {
                $table->string('property_id', 50)->nullable()->unique()->after('exhibit_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (Schema::hasColumn('exhibits', 'property_id')) {
                $table->dropUnique(['property_id']);
                $table->dropColumn('property_id');
            }
        });
    }
};
