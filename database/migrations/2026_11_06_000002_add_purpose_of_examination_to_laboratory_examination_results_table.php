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
            if (!Schema::hasColumn('laboratory_examination_results', 'purpose_of_examination')) {
                $table->text('purpose_of_examination')->nullable()->after('exam_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_examination_results', function (Blueprint $table) {
            if (Schema::hasColumn('laboratory_examination_results', 'purpose_of_examination')) {
                $table->dropColumn('purpose_of_examination');
            }
        });
    }
};

