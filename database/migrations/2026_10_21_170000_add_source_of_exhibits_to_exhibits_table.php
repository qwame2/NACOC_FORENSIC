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
        Schema::table('exhibits', function (Blueprint $table) {
            if (!Schema::hasColumn('exhibits', 'source_of_exhibits')) {
                $table->text('source_of_exhibits')->nullable()->after('seizure_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (Schema::hasColumn('exhibits', 'source_of_exhibits')) {
                $table->dropColumn('source_of_exhibits');
            }
        });
    }
};
