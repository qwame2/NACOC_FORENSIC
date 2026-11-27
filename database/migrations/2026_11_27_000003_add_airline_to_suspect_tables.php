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
        if (Schema::hasTable('single_suspect_cases') && !Schema::hasColumn('single_suspect_cases', 'airline')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->string('airline')->nullable()->after('flight_number');
            });
        }

        if (Schema::hasTable('case_suspects') && !Schema::hasColumn('case_suspects', 'airline')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                $table->string('airline')->nullable()->after('flight_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('single_suspect_cases') && Schema::hasColumn('single_suspect_cases', 'airline')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('airline');
            });
        }

        if (Schema::hasTable('case_suspects') && Schema::hasColumn('case_suspects', 'airline')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                $table->dropColumn('airline');
            });
        }
    }
};

