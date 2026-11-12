<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (!Schema::hasColumn('laboratory_examination_results', 'presumptive_presence')) {
                    $table->text('presumptive_presence')->nullable()->after('presumptive_result');
                }
                if (!Schema::hasColumn('laboratory_examination_results', 'presumptive_percentage')) {
                    $table->string('presumptive_percentage')->nullable()->after('presumptive_presence');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laboratory_examination_results')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                if (Schema::hasColumn('laboratory_examination_results', 'presumptive_percentage')) {
                    $table->dropColumn('presumptive_percentage');
                }
                if (Schema::hasColumn('laboratory_examination_results', 'presumptive_presence')) {
                    $table->dropColumn('presumptive_presence');
                }
            });
        }
    }
};

