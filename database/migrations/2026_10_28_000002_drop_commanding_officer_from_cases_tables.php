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
        if (Schema::hasColumn('single_suspect_cases', 'commanding_officer')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('commanding_officer');
            });
        }

        if (Schema::hasColumn('multiple_suspect_cases', 'commanding_officer')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('commanding_officer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('single_suspect_cases', 'commanding_officer')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->string('commanding_officer')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('multiple_suspect_cases', 'commanding_officer')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                $table->string('commanding_officer')->nullable()->after('user_id');
            });
        }
    }
};
