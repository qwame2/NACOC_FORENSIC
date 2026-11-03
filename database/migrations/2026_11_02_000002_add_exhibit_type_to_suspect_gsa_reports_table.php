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
                if (!Schema::hasColumn('suspect_gsa_reports', 'exhibit_type')) {
                    $table->text('exhibit_type')->nullable()->after('gsa_status');
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
                if (Schema::hasColumn('suspect_gsa_reports', 'exhibit_type')) {
                    $table->dropColumn('exhibit_type');
                }
            });
        }
    }
};
