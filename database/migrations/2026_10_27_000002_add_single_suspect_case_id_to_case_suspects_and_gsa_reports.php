<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update case_suspects table
        Schema::table('case_suspects', function (Blueprint $table) {
            if (!Schema::hasColumn('case_suspects', 'single_suspect_case_id')) {
                $table->foreignId('single_suspect_case_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('single_suspect_cases')
                      ->cascadeOnDelete();
            }
        });

        Schema::table('case_suspects', function (Blueprint $table) {
            $table->unsignedBigInteger('multiple_suspect_case_id')->nullable()->change();
        });

        // 2. Update suspect_gsa_reports table
        Schema::table('suspect_gsa_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('suspect_gsa_reports', 'single_suspect_case_id')) {
                $table->foreignId('single_suspect_case_id')
                      ->nullable()
                      ->after('case_suspect_id')
                      ->constrained('single_suspect_cases')
                      ->cascadeOnDelete();
            }
        });

        Schema::table('suspect_gsa_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('multiple_suspect_case_id')->nullable()->change();
        });

        // 3. Migrate and clean up existing data:
        // Any MultipleSuspectCase that is actually a SingleSuspectCase (1 suspect and matching SingleSuspectCase)
        $multiCases = DB::table('multiple_suspect_cases')->get();
        foreach ($multiCases as $m) {
            // Check if there is a matching single_suspect_cases row
            $single = DB::table('single_suspect_cases')->where('case_number', $m->case_number)->first();
            $suspectsCount = DB::table('case_suspects')->where('multiple_suspect_case_id', $m->id)->count();

            if ($single && $suspectsCount <= 1) {
                // Re-link case_suspects to the single_suspect_cases record
                DB::table('case_suspects')
                    ->where('multiple_suspect_case_id', $m->id)
                    ->update([
                        'single_suspect_case_id'   => $single->id,
                        'multiple_suspect_case_id' => null,
                    ]);

                // Re-link any suspect_gsa_reports
                if (Schema::hasTable('suspect_gsa_reports')) {
                    DB::table('suspect_gsa_reports')
                        ->where('multiple_suspect_case_id', $m->id)
                        ->update([
                            'single_suspect_case_id'   => $single->id,
                            'multiple_suspect_case_id' => null,
                        ]);
                }

                // Re-link any exhibits if any pointed to this multi case id
                if (Schema::hasTable('exhibits')) {
                    DB::table('exhibits')
                        ->where('multiple_suspect_case_id', $m->id)
                        ->update([
                            'case_type'                => 'SINGLE',
                            'single_suspect_case_id'   => $single->id,
                            'multiple_suspect_case_id' => null,
                        ]);
                }

                // Delete the duplicate single suspect record from multiple_suspect_cases
                DB::table('multiple_suspect_cases')->where('id', $m->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suspect_gsa_reports', function (Blueprint $table) {
            if (Schema::hasColumn('suspect_gsa_reports', 'single_suspect_case_id')) {
                $table->dropForeign(['single_suspect_case_id']);
                $table->dropColumn('single_suspect_case_id');
            }
        });

        Schema::table('case_suspects', function (Blueprint $table) {
            if (Schema::hasColumn('case_suspects', 'single_suspect_case_id')) {
                $table->dropForeign(['single_suspect_case_id']);
                $table->dropColumn('single_suspect_case_id');
            }
        });
    }
};
