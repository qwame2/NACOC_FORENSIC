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
                if (!Schema::hasColumn('single_suspect_cases', 'search_warrant_document')) {
                    $afterCol = Schema::hasColumn('single_suspect_cases', 'operation_code_name')
                        ? 'operation_code_name'
                        : 'operation_code';
                    $table->string('search_warrant_document')->nullable()->after($afterCol);
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('multiple_suspect_cases', 'search_warrant_document')) {
                    $afterCol = Schema::hasColumn('multiple_suspect_cases', 'operation_code_name')
                        ? 'operation_code_name'
                        : 'operation_code';
                    $table->string('search_warrant_document')->nullable()->after($afterCol);
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
                if (Schema::hasColumn('single_suspect_cases', 'search_warrant_document')) {
                    $table->dropColumn('search_warrant_document');
                }
            });
        }

        if (Schema::hasTable('multiple_suspect_cases')) {
            Schema::table('multiple_suspect_cases', function (Blueprint $table) {
                if (Schema::hasColumn('multiple_suspect_cases', 'search_warrant_document')) {
                    $table->dropColumn('search_warrant_document');
                }
            });
        }
    }
};

