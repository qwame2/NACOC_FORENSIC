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
        if (Schema::hasTable('exhibits')) {
            Schema::table('exhibits', function (Blueprint $table) {
                if (!Schema::hasColumn('exhibits', 'investigating_officer')) {
                    $table->string('investigating_officer')->nullable()->after('seizure_officer');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exhibits')) {
            Schema::table('exhibits', function (Blueprint $table) {
                if (Schema::hasColumn('exhibits', 'investigating_officer')) {
                    $table->dropColumn('investigating_officer');
                }
            });
        }
    }
};
