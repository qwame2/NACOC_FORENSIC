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
            if (!Schema::hasColumn('exhibits', 'dev_storage')) {
                $table->text('dev_storage')->nullable();
            }
            if (!Schema::hasColumn('exhibits', 'dev_colour')) {
                $table->text('dev_colour')->nullable();
            }
            if (!Schema::hasColumn('exhibits', 'dev_marks')) {
                $table->text('dev_marks')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (Schema::hasColumn('exhibits', 'dev_storage')) {
                $table->dropColumn('dev_storage');
            }
            if (Schema::hasColumn('exhibits', 'dev_colour')) {
                $table->dropColumn('dev_colour');
            }
            if (Schema::hasColumn('exhibits', 'dev_marks')) {
                $table->dropColumn('dev_marks');
            }
        });
    }
};
