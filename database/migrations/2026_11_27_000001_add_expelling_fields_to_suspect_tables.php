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
        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) {
                if (!Schema::hasColumn('single_suspect_cases', 'passport_number')) {
                    $table->string('passport_number')->nullable()->after('ghana_card');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'flight_number')) {
                    $table->string('flight_number')->nullable()->after('passport_number');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'destination')) {
                    $table->string('destination')->nullable()->after('flight_number');
                }
                if (!Schema::hasColumn('single_suspect_cases', 'substance_expelled')) {
                    $table->string('substance_expelled')->nullable()->after('destination');
                }
            });
        }

        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) {
                if (!Schema::hasColumn('case_suspects', 'passport_number')) {
                    $table->string('passport_number')->nullable()->after('ghana_card');
                }
                if (!Schema::hasColumn('case_suspects', 'flight_number')) {
                    $table->string('flight_number')->nullable()->after('passport_number');
                }
                if (!Schema::hasColumn('case_suspects', 'destination')) {
                    $table->string('destination')->nullable()->after('flight_number');
                }
                if (!Schema::hasColumn('case_suspects', 'substance_expelled')) {
                    $table->string('substance_expelled')->nullable()->after('destination');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['passport_number', 'flight_number', 'destination', 'substance_expelled'];

        if (Schema::hasTable('single_suspect_cases')) {
            Schema::table('single_suspect_cases', function (Blueprint $table) use ($columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn('single_suspect_cases', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('case_suspects')) {
            Schema::table('case_suspects', function (Blueprint $table) use ($columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn('case_suspects', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

