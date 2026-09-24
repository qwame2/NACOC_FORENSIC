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
        // 1. Update case_suspects table
        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                if (!Schema::hasColumn('case_suspects', 'suspect_disposition')) {
                    $table->string('suspect_disposition')->default('Active — Under Investigation')->after('case_status');
                }
                if (!Schema::hasColumn('case_suspects', 'disposition_reason')) {
                    $table->text('disposition_reason')->nullable()->after('suspect_disposition');
                }
                if (!Schema::hasColumn('case_suspects', 'disposition_updated_at')) {
                    $table->timestamp('disposition_updated_at')->nullable()->after('disposition_reason');
                }
                if (!Schema::hasColumn('case_suspects', 'disposition_updated_by')) {
                    $table->string('disposition_updated_by')->nullable()->after('disposition_updated_at');
                }
            });
        }

        // 2. Update single_suspect_cases table
        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('single_suspect_cases', 'suspect_disposition')) {
                    $table->string('suspect_disposition')->default('Active — Under Investigation')->after('case_status');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'disposition_reason')) {
                    $table->text('disposition_reason')->nullable()->after('suspect_disposition');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'disposition_updated_at')) {
                    $table->timestamp('disposition_updated_at')->nullable()->after('disposition_reason');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'disposition_updated_by')) {
                    $table->string('disposition_updated_by')->nullable()->after('disposition_updated_at');
                }
            });
        }

        // 3. Update multiple_suspect_cases table
        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('multiple_suspect_cases', 'suspect_disposition')) {
                    $table->string('suspect_disposition')->default('Active — Under Investigation')->after('case_status');
                }
                if (!Schema::hasColumn('multiple_suspect_cases', 'disposition_reason')) {
                    $table->text('disposition_reason')->nullable()->after('suspect_disposition');
                }
                if (!Schema::hasColumn('multiple_suspect_cases', 'disposition_updated_at')) {
                    $table->timestamp('disposition_updated_at')->nullable()->after('disposition_reason');
                }
                if (!Schema::hasColumn('multiple_suspect_cases', 'disposition_updated_by')) {
                    $table->string('disposition_updated_by')->nullable()->after('disposition_updated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                $table->dropColumn(['suspect_disposition', 'disposition_reason', 'disposition_updated_at', 'disposition_updated_by']);
            });
        }

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->dropColumn(['suspect_disposition', 'disposition_reason', 'disposition_updated_at', 'disposition_updated_by']);
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                $table->dropColumn(['suspect_disposition', 'disposition_reason', 'disposition_updated_at', 'disposition_updated_by']);
            });
        }
    }
};
