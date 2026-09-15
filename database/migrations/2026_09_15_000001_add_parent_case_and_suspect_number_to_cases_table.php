<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add parent_case_id and suspect_number to support multi-suspect joint case records.
     * Each suspect in a joint case gets their own row, linked via parent_case_id back
     * to the primary (Suspect #1) case row.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // ID of the primary/parent case row (Suspect #1). Null for single-suspect or primary record.
            $table->unsignedBigInteger('parent_case_id')->nullable()->after('id');
            // Sequential suspect number within a joint case (1 = primary, 2, 3, ... = additional)
            $table->unsignedSmallInteger('suspect_number')->default(1)->after('parent_case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['parent_case_id', 'suspect_number']);
        });
    }
};
