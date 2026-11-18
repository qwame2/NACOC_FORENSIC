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
        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('single_suspect_cases', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('raid_location')->index();
                }
                if (!Schema::hasColumn('single_suspect_cases', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('multiple_suspect_cases', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('raid_location')->index();
                }
                if (!Schema::hasColumn('multiple_suspect_cases', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }

        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                if (!Schema::hasColumn('case_suspects', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('raid_location')->index();
                }
                if (!Schema::hasColumn('case_suspects', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('single_suspect_cases', 'latitude')) {
                    $table->dropIndex(['latitude']);
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('single_suspect_cases', 'longitude')) {
                    $table->dropColumn('longitude');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('multiple_suspect_cases', 'latitude')) {
                    $table->dropIndex(['latitude']);
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('multiple_suspect_cases', 'longitude')) {
                    $table->dropColumn('longitude');
                }
            });
        }

        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                if (Schema::hasColumn('case_suspects', 'latitude')) {
                    $table->dropIndex(['latitude']);
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('case_suspects', 'longitude')) {
                    $table->dropColumn('longitude');
                }
            });
        }
    }
};

