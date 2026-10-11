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
        if (Schema::hasTable('dpf_report')) {
            Schema::table('dpf_report', function (Blueprint $table) {
                if (!Schema::hasColumn('dpf_report', 'court_order_file')) {
                    $table->text('court_order_file')->nullable();
                }
                if (!Schema::hasColumn('dpf_report', 'report_witness_statement_file')) {
                    $table->text('report_witness_statement_file')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dpf_report') && DB::getDriverName() !== 'sqlite') {
            Schema::table('dpf_report', function (Blueprint $table) {
                if (Schema::hasColumn('dpf_report', 'court_order_file')) {
                    $table->dropColumn('court_order_file');
                }
                if (Schema::hasColumn('dpf_report', 'report_witness_statement_file')) {
                    $table->dropColumn('report_witness_statement_file');
                }
            });
        }
    }
};
