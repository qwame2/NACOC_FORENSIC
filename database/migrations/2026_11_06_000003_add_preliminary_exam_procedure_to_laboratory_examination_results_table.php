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
        Schema::table('laboratory_examination_results', function (Blueprint $table) {
            if (!Schema::hasColumn('laboratory_examination_results', 'preliminary_exam_procedure')) {
                $table->text('preliminary_exam_procedure')->nullable()->after('purpose_of_examination');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_examination_results', function (Blueprint $table) {
            if (Schema::hasColumn('laboratory_examination_results', 'preliminary_exam_procedure')) {
                $table->dropColumn('preliminary_exam_procedure');
            }
        });
    }
};

