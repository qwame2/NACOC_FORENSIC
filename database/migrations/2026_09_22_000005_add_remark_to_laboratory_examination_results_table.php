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
        if (Schema::hasTable('laboratory_examination_results') && !Schema::hasColumn('laboratory_examination_results', 'remark')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                $table->text('remark')->nullable()->after('specifications');
            });
        }

        if (Schema::hasTable('associate_lab_examinations') && !Schema::hasColumn('associate_lab_examinations', 'remark')) {
            Schema::table('associate_lab_examinations', function (Blueprint $table) {
                $table->text('remark')->nullable()->after('specifications');
            });
        }

        if (Schema::hasTable('single_suspect_cases') && !Schema::hasColumn('single_suspect_cases', 'confirmatory_remark')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->text('confirmatory_remark')->nullable();
            });
        }

        if (Schema::hasTable('multiple_suspect_cases') && !Schema::hasColumn('multiple_suspect_cases', 'confirmatory_remark')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                $table->text('confirmatory_remark')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laboratory_examination_results') && Schema::hasColumn('laboratory_examination_results', 'remark')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                $table->dropColumn('remark');
            });
        }

        if (Schema::hasTable('associate_lab_examinations') && Schema::hasColumn('associate_lab_examinations', 'remark')) {
            Schema::table('associate_lab_examinations', function (Blueprint $table) {
                $table->dropColumn('remark');
            });
        }

        if (Schema::hasTable('single_suspect_cases') && Schema::hasColumn('single_suspect_cases', 'confirmatory_remark')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('confirmatory_remark');
            });
        }

        if (Schema::hasTable('multiple_suspect_cases') && Schema::hasColumn('multiple_suspect_cases', 'confirmatory_remark')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('confirmatory_remark');
            });
        }
    }
};
