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
        // 1. Create reference_registry table for guaranteed central uniqueness and reservation
        if (!Schema::hasTable('reference_registry')) {
            Schema::create('reference_registry', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 128)->unique();
                $table->string('category', 64)->index();
                $table->string('prefix', 32)->nullable()->index();
                $table->integer('year')->nullable()->index();
                $table->timestamps();
            });
        }

        // 2. Ensure unique constraint on nlr_reports.nlr_code
        if (Schema::hasTable('nlr_reports') && Schema::hasColumn('nlr_reports', 'nlr_code')) {
            DB::statement("UPDATE nlr_reports SET nlr_code = NULL WHERE nlr_code IS NOT NULL AND TRIM(nlr_code) = ''");

            $duplicates = DB::table('nlr_reports')
                ->select('nlr_code', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('nlr_code')
                ->groupBy('nlr_code')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                $ids = DB::table('nlr_reports')
                    ->where('nlr_code', $dup->nlr_code)
                    ->orderBy('id')
                    ->pluck('id');

                $first = true;
                $counter = 1;
                foreach ($ids as $id) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $newRef = $dup->nlr_code . '-DUP' . ($counter++);
                    DB::table('nlr_reports')
                        ->where('id', $id)
                        ->update(['nlr_code' => $newRef]);
                }
            }

            if (!Schema::hasIndex('nlr_reports', 'nlr_reports_nlr_code_unique')) {
                Schema::table('nlr_reports', function (Blueprint $table) {
                    $table->unique('nlr_code', 'nlr_reports_nlr_code_unique');
                });
            }
        }

        // 3. Ensure unique constraint on laboratory_examination_results.exam_reference
        if (Schema::hasTable('laboratory_examination_results') && Schema::hasColumn('laboratory_examination_results', 'exam_reference')) {
            DB::statement("UPDATE laboratory_examination_results SET exam_reference = NULL WHERE exam_reference IS NOT NULL AND TRIM(exam_reference) = ''");
            if (!Schema::hasIndex('laboratory_examination_results', 'lab_exam_results_exam_reference_unique')) {
                Schema::table('laboratory_examination_results', function (Blueprint $table) {
                    $table->unique('exam_reference', 'lab_exam_results_exam_reference_unique');
                });
            }
        }

        // 4. Ensure unique constraint on associate_lab_examinations.exam_reference
        if (Schema::hasTable('associate_lab_examinations') && Schema::hasColumn('associate_lab_examinations', 'exam_reference')) {
            DB::statement("UPDATE associate_lab_examinations SET exam_reference = NULL WHERE exam_reference IS NOT NULL AND TRIM(exam_reference) = ''");
            if (!Schema::hasIndex('associate_lab_examinations', 'assoc_lab_exam_reference_unique')) {
                Schema::table('associate_lab_examinations', function (Blueprint $table) {
                    $table->unique('exam_reference', 'assoc_lab_exam_reference_unique');
                });
            }
        }

        // 5. Backfill existing references into reference_registry to prevent future collisions
        if (Schema::hasTable('reference_registry')) {
            // Backfill Single Suspect Case numbers
            if (Schema::hasTable('single_suspect_cases') && Schema::hasColumn('single_suspect_cases', 'case_number')) {
                $singleCaseNumbers = DB::table('single_suspect_cases')
                    ->whereNotNull('case_number')
                    ->where('case_number', '!=', '')
                    ->pluck('case_number');

                foreach ($singleCaseNumbers as $cn) {
                    $cn = trim((string)$cn);
                    if ($cn === '') continue;
                    DB::table('reference_registry')->insertOrIgnore([
                        'reference'  => $cn,
                        'category'   => 'case_number',
                        'prefix'     => str_contains($cn, '-') ? explode('-', $cn)[0] : 'NCC',
                        'year'       => preg_match('/-(\d{4})-/', $cn, $m) ? (int)$m[1] : (int)date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Backfill Multiple Suspect Case numbers
            if (Schema::hasTable('multiple_suspect_cases') && Schema::hasColumn('multiple_suspect_cases', 'case_number')) {
                $multiCaseNumbers = DB::table('multiple_suspect_cases')
                    ->whereNotNull('case_number')
                    ->where('case_number', '!=', '')
                    ->pluck('case_number');

                foreach ($multiCaseNumbers as $cn) {
                    $cn = trim((string)$cn);
                    if ($cn === '') continue;
                    DB::table('reference_registry')->insertOrIgnore([
                        'reference'  => $cn,
                        'category'   => 'case_number',
                        'prefix'     => str_contains($cn, '-') ? explode('-', $cn)[0] : 'NCC',
                        'year'       => preg_match('/-(\d{4})-/', $cn, $m) ? (int)$m[1] : (int)date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Backfill Laboratory Examination Results exam_reference
            if (Schema::hasTable('laboratory_examination_results') && Schema::hasColumn('laboratory_examination_results', 'exam_reference')) {
                $examRefs = DB::table('laboratory_examination_results')
                    ->whereNotNull('exam_reference')
                    ->where('exam_reference', '!=', '')
                    ->pluck('exam_reference');

                foreach ($examRefs as $er) {
                    $er = trim((string)$er);
                    if ($er === '') continue;
                    DB::table('reference_registry')->insertOrIgnore([
                        'reference'  => $er,
                        'category'   => 'exam_reference',
                        'prefix'     => str_contains($er, '-') ? explode('-', $er)[0] : 'NLR',
                        'year'       => (int)date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Backfill Associate Lab Examinations exam_reference
            if (Schema::hasTable('associate_lab_examinations') && Schema::hasColumn('associate_lab_examinations', 'exam_reference')) {
                $assocExamRefs = DB::table('associate_lab_examinations')
                    ->whereNotNull('exam_reference')
                    ->where('exam_reference', '!=', '')
                    ->pluck('exam_reference');

                foreach ($assocExamRefs as $er) {
                    $er = trim((string)$er);
                    if ($er === '') continue;
                    DB::table('reference_registry')->insertOrIgnore([
                        'reference'  => $er,
                        'category'   => 'exam_reference',
                        'prefix'     => str_contains($er, '-') ? explode('-', $er)[0] : 'NLR',
                        'year'       => (int)date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Backfill NLR Reports nlr_code
            if (Schema::hasTable('nlr_reports') && Schema::hasColumn('nlr_reports', 'nlr_code')) {
                $nlrCodes = DB::table('nlr_reports')
                    ->whereNotNull('nlr_code')
                    ->where('nlr_code', '!=', '')
                    ->pluck('nlr_code');

                foreach ($nlrCodes as $nc) {
                    $nc = trim((string)$nc);
                    if ($nc === '') continue;
                    DB::table('reference_registry')->insertOrIgnore([
                        'reference'  => $nc,
                        'category'   => 'exam_reference',
                        'prefix'     => str_contains($nc, '-') ? explode('-', $nc)[0] : 'NLR',
                        'year'       => (int)date('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nlr_reports') && Schema::hasIndex('nlr_reports', 'nlr_reports_nlr_code_unique')) {
            Schema::table('nlr_reports', function (Blueprint $table) {
                $table->dropUnique('nlr_reports_nlr_code_unique');
            });
        }

        Schema::dropIfExists('reference_registry');
    }
};
