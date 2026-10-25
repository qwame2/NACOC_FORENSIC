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
        if (Schema::hasTable('suspect_gsa_reports')) {
            Schema::table('suspect_gsa_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('suspect_gsa_reports', 'gsa_lab_number')) {
                    $table->string('gsa_lab_number')->nullable()->after('lab_number');
                }
                if (!Schema::hasColumn('suspect_gsa_reports', 'gsa_case_reference')) {
                    $table->text('gsa_case_reference')->nullable()->after('gsa_lab_number');
                }
                if (!Schema::hasColumn('suspect_gsa_reports', 'gsa_status')) {
                    $table->text('gsa_status')->nullable()->after('gsa_case_reference');
                }
            });
        }

        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (!Schema::hasColumn('laboratory_examination_results', 'gsa_lab_number')) {
                    $table->string('gsa_lab_number')->nullable()->after('laboratory_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('suspect_gsa_reports')) {
            Schema::table('suspect_gsa_reports', function (Blueprint $table) {
                if (Schema::hasColumn('suspect_gsa_reports', 'gsa_status')) {
                    $table->dropColumn('gsa_status');
                }
                if (Schema::hasColumn('suspect_gsa_reports', 'gsa_case_reference')) {
                    $table->dropColumn('gsa_case_reference');
                }
                if (Schema::hasColumn('suspect_gsa_reports', 'gsa_lab_number')) {
                    $table->dropColumn('gsa_lab_number');
                }
            });
        }

        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (Schema::hasColumn('laboratory_examination_results', 'gsa_lab_number')) {
                    $table->dropColumn('gsa_lab_number');
                }
            });
        }
    }
};
