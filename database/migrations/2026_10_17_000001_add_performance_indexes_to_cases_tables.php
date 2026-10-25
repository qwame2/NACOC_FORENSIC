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
        Schema::table('single_suspect_cases', function (Blueprint $table) {
            $table->index('created_at', 'idx_single_cases_created_at');
            $table->index('status', 'idx_single_cases_status');
            $table->index('suspect_name', 'idx_single_cases_suspect_name');
        });

        Schema::table('multiple_suspect_cases', function (Blueprint $table) {
            $table->index('created_at', 'idx_multi_cases_created_at');
            $table->index('status', 'idx_multi_cases_status');
        });

        Schema::table('case_suspects', function (Blueprint $table) {
            $table->index('suspect_name', 'idx_case_suspects_suspect_name');
        });

        Schema::table('exhibits', function (Blueprint $table) {
            $table->index('created_at', 'idx_exhibits_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('single_suspect_cases', function (Blueprint $table) {
            $table->dropIndex('idx_single_cases_created_at');
            $table->dropIndex('idx_single_cases_status');
            $table->dropIndex('idx_single_cases_suspect_name');
        });

        Schema::table('multiple_suspect_cases', function (Blueprint $table) {
            $table->dropIndex('idx_multi_cases_created_at');
            $table->dropIndex('idx_multi_cases_status');
        });

        Schema::table('case_suspects', function (Blueprint $table) {
            $table->dropIndex('idx_case_suspects_suspect_name');
        });

        Schema::table('exhibits', function (Blueprint $table) {
            $table->dropIndex('idx_exhibits_created_at');
        });
    }
};
