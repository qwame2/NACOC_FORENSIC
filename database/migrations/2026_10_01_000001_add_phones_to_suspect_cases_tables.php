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
        if (Schema::hasTable('single_suspect_cases') && !Schema::hasColumn('single_suspect_cases', 'phones')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->json('phones')->nullable()->after('phone');
            });
        }

        if (Schema::hasTable('case_suspects') && !Schema::hasColumn('case_suspects', 'phones')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                $table->json('phones')->nullable()->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('single_suspect_cases') && Schema::hasColumn('single_suspect_cases', 'phones')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                $table->dropColumn('phones');
            });
        }

        if (Schema::hasTable('case_suspects') && Schema::hasColumn('case_suspects', 'phones')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                $table->dropColumn('phones');
            });
        }
    }
};
