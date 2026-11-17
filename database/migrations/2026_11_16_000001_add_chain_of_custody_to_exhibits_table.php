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
                if (!Schema::hasColumn('exhibits', 'chain_of_custody')) {
                    $table->text('chain_of_custody')->nullable()->after('custodial_status');
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
                if (Schema::hasColumn('exhibits', 'chain_of_custody')) {
                    $table->dropColumn('chain_of_custody');
                }
            });
        }
    }
};

