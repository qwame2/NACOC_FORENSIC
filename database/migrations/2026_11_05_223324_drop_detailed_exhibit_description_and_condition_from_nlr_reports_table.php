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
        Schema::table('nlr_reports', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('nlr_reports', 'detailed_exhibit_description')) {
                $dropCols[] = 'detailed_exhibit_description';
            }
            if (Schema::hasColumn('nlr_reports', 'condition_of_reserved_sample')) {
                $dropCols[] = 'condition_of_reserved_sample';
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nlr_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('nlr_reports', 'detailed_exhibit_description')) {
                $table->longText('detailed_exhibit_description')->nullable();
            }
            if (!Schema::hasColumn('nlr_reports', 'condition_of_reserved_sample')) {
                $table->string('condition_of_reserved_sample')->nullable();
            }
        });
    }
};
