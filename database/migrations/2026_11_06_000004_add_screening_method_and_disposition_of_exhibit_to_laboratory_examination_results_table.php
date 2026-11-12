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
            if (!Schema::hasColumn('laboratory_examination_results', 'screening_method')) {
                $table->text('screening_method')->nullable()->after('preliminary_exam_procedure');
            }
            if (!Schema::hasColumn('laboratory_examination_results', 'disposition_of_exhibit')) {
                $table->text('disposition_of_exhibit')->nullable()->after('remark');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_examination_results', function (Blueprint $table) {
            if (Schema::hasColumn('laboratory_examination_results', 'screening_method')) {
                $table->dropColumn('screening_method');
            }
            if (Schema::hasColumn('laboratory_examination_results', 'disposition_of_exhibit')) {
                $table->dropColumn('disposition_of_exhibit');
            }
        });
    }
};

