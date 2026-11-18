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
                if (Schema::hasColumn('laboratory_examination_results', 'substance_identification')) {
                    $table->dropColumn('substance_identification');
                }
                if (!Schema::hasColumn('laboratory_examination_results', 'detected_subtances_percentage')) {
                    $table->text('detected_subtances_percentage')->nullable()->after('detected_substances');
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
                if (Schema::hasColumn('laboratory_examination_results', 'detected_subtances_percentage')) {
                    $table->dropColumn('detected_subtances_percentage');
                }
                if (!Schema::hasColumn('laboratory_examination_results', 'substance_identification')) {
                    $table->text('substance_identification')->nullable()->after('sample_weight_reserved');
                }
            });
        }
    }
};

