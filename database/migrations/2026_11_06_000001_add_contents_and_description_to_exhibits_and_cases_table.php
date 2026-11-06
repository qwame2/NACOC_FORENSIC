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
        if (Schema::hasTable('exhibits')) {
            Schema::table('exhibits', function (Blueprint $table) {
                if (!Schema::hasColumn('exhibits', 'contents')) {
                    $table->text('contents')->nullable()->after('commodity_name');
                }
                if (!Schema::hasColumn('exhibits', 'description_of_exhibits')) {
                    $table->text('description_of_exhibits')->nullable()->after('contents');
                }
            });
        }

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('single_suspect_cases', 'description_of_exhibits')) {
                    $table->text('description_of_exhibits')->nullable()->after('offense_description');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('multiple_suspect_cases', 'description_of_exhibits')) {
                    $table->text('description_of_exhibits')->nullable()->after('offense_description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exhibits')) {
            Schema::table('exhibits', function (Blueprint $table) {
                if (Schema::hasColumn('exhibits', 'description_of_exhibits')) {
                    $table->dropColumn('description_of_exhibits');
                }
                if (Schema::hasColumn('exhibits', 'contents')) {
                    $table->dropColumn('contents');
                }
            });
        }

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('single_suspect_cases', 'description_of_exhibits')) {
                    $table->dropColumn('description_of_exhibits');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('multiple_suspect_cases', 'description_of_exhibits')) {
                    $table->dropColumn('description_of_exhibits');
                }
            });
        }
    }
};

