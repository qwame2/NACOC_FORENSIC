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
        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (Schema::hasColumn('laboratory_examination_results', 'detected_substances_percentage')) {
                    $table->dropColumn('detected_substances_percentage');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (!Schema::hasColumn('laboratory_examination_results', 'detected_substances_percentage')) {
                    $table->text('detected_substances_percentage')->nullable()->after('detected_subtances_percentage');
                }
            });
        }
    }
};

