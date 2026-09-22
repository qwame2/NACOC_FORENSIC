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
        if (Schema::hasTable('laboratory_examination_results') && Schema::hasColumn('laboratory_examination_results', 'lab_unit')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                $table->dropColumn('lab_unit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laboratory_examination_results') && !Schema::hasColumn('laboratory_examination_results', 'lab_unit')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                $table->string('lab_unit')->nullable()->default('Chemistry & Narcotics Unit');
            });
        }
    }
};
