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
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'height')) {
                $table->string('height')->nullable()->after('age');
            }
            if (!Schema::hasColumn('cases', 'eye_color')) {
                $table->string('eye_color')->nullable()->after('height');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (Schema::hasColumn('cases', 'height')) {
                $table->dropColumn('height');
            }
            if (Schema::hasColumn('cases', 'eye_color')) {
                $table->dropColumn('eye_color');
            }
        });
    }
};
