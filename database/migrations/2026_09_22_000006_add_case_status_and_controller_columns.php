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
        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                if (!Schema::hasColumn('case_suspects', 'case_status')) {
                    $table->string('case_status')->default('Under Investigation')->after('associate_id');
                }
            });
        }

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('single_suspect_cases', 'case_status')) {
                    $table->string('case_status')->default('Under Investigation')->after('status');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'controller_user_id')) {
                    $table->foreignId('controller_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('multiple_suspect_cases', 'case_status')) {
                    $table->string('case_status')->default('Under Investigation')->after('status');
                }
                if (!Schema::hasColumn('multiple_suspect_cases', 'controller_user_id')) {
                    $table->foreignId('controller_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
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
                if (Schema::hasColumn('case_suspects', 'case_status')) {
                    $table->dropColumn('case_status');
                }
            });
        }

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('single_suspect_cases', 'controller_user_id')) {
                    $table->dropForeign(['controller_user_id']);
                    $table->dropColumn('controller_user_id');
                }
                if (Schema::hasColumn('single_suspect_cases', 'case_status')) {
                    $table->dropColumn('case_status');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('multiple_suspect_cases', 'controller_user_id')) {
                    $table->dropForeign(['controller_user_id']);
                    $table->dropColumn('controller_user_id');
                }
                if (Schema::hasColumn('multiple_suspect_cases', 'case_status')) {
                    $table->dropColumn('case_status');
                }
            });
        }
    }
};
