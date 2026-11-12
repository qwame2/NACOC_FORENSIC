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
                if (!Schema::hasColumn('exhibits', 'source_requesting_unit')) {
                    $table->string('source_requesting_unit')->nullable()->after('investigating_officer');
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
                if (Schema::hasColumn('exhibits', 'source_requesting_unit')) {
                    $table->dropColumn('source_requesting_unit');
                }
            });
        }
    }
};
