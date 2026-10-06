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
        if (Schema::hasTable('nlr_reports') && !Schema::hasColumn('nlr_reports', 'nlr_code')) {
            Schema::table('nlr_reports', function (Blueprint $table) {
                $table->string('nlr_code')->nullable()->after('laboratory_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nlr_reports') && Schema::hasColumn('nlr_reports', 'nlr_code')) {
            Schema::table('nlr_reports', function (Blueprint $table) {
                $table->dropColumn('nlr_code');
            });
        }
    }
};
