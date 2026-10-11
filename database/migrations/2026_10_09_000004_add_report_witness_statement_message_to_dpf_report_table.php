<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('dpf_report')) {
            Schema::table('dpf_report', function (Blueprint $table) {
                if (!Schema::hasColumn('dpf_report', 'report_witness_statement_message')) {
                    $table->text('report_witness_statement_message')->nullable();
                }
            });

            if (DB::getDriverName() !== 'sqlite') {
                Schema::table('dpf_report', function (Blueprint $table) {
                    $table->text('report_witness_statement_sent')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dpf_report') && DB::getDriverName() !== 'sqlite') {
            Schema::table('dpf_report', function (Blueprint $table) {
                if (Schema::hasColumn('dpf_report', 'report_witness_statement_message')) {
                    $table->dropColumn('report_witness_statement_message');
                }
            });
        }
    }
};
