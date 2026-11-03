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
        // 1. Rename on single_suspect_cases table
        if (Schema::hasTable('single_suspect_cases')) {
            if (Schema::hasColumn('single_suspect_cases', 'submitting_officer') && !Schema::hasColumn('single_suspect_cases', 'arresting_officer')) {
                Schema::table('single_suspect_cases', function (Blueprint $table) {
                    $table->renameColumn('submitting_officer', 'arresting_officer');
                });
            } elseif (!Schema::hasColumn('single_suspect_cases', 'arresting_officer')) {
                Schema::table('single_suspect_cases', function (Blueprint $table) {
                    $table->string('arresting_officer')->nullable()->after('user_id');
                });
            }
        }

        // 2. Rename on multiple_suspect_cases table
        if (Schema::hasTable('multiple_suspect_cases')) {
            if (Schema::hasColumn('multiple_suspect_cases', 'submitting_officer') && !Schema::hasColumn('multiple_suspect_cases', 'arresting_officer')) {
                Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                    $table->renameColumn('submitting_officer', 'arresting_officer');
                });
            } elseif (!Schema::hasColumn('multiple_suspect_cases', 'arresting_officer')) {
                Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                    $table->string('arresting_officer')->nullable()->after('user_id');
                });
            }
        }

        // 3. Rename on legacy cases table if it still exists in any environment
        if (Schema::hasTable('cases')) {
            if (Schema::hasColumn('cases', 'submitting_officer') && !Schema::hasColumn('cases', 'arresting_officer')) {
                Schema::table('cases', function (Blueprint $table) {
                    $table->renameColumn('submitting_officer', 'arresting_officer');
                });
            } elseif (!Schema::hasColumn('cases', 'arresting_officer')) {
                Schema::table('cases', function (Blueprint $table) {
                    $table->string('arresting_officer')->nullable()->after('user_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('single_suspect_cases')) {
            if (Schema::hasColumn('single_suspect_cases', 'arresting_officer') && !Schema::hasColumn('single_suspect_cases', 'submitting_officer')) {
                Schema::table('single_suspect_cases', function (Blueprint $table) {
                    $table->renameColumn('arresting_officer', 'submitting_officer');
                });
            }
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            if (Schema::hasColumn('multiple_suspect_cases', 'arresting_officer') && !Schema::hasColumn('multiple_suspect_cases', 'submitting_officer')) {
                Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                    $table->renameColumn('arresting_officer', 'submitting_officer');
                });
            }
        }

        if (Schema::hasTable('cases')) {
            if (Schema::hasColumn('cases', 'arresting_officer') && !Schema::hasColumn('cases', 'submitting_officer')) {
                Schema::table('cases', function (Blueprint $table) {
                    $table->renameColumn('arresting_officer', 'submitting_officer');
                });
            }
        }
    }
};
