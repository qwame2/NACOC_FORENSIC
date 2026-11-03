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
        // 1. Create central table for thread-safe, concurrency-safe reference sequences
        if (!Schema::hasTable('reference_sequences')) {
            Schema::create('reference_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('category', 64)->index();
                $table->string('prefix', 32)->index();
                $table->integer('year')->nullable()->index();
                $table->unsignedBigInteger('current_number')->default(0);
                $table->timestamps();

                $table->unique(['category', 'prefix', 'year'], 'ref_seq_cat_pfx_year_unique');
            });
        }

        // 2. Sanitize empty string values in laboratory_examination_results before adding unique index
        if (Schema::hasTable('laboratory_examination_results') && Schema::hasColumn('laboratory_examination_results', 'exam_reference')) {
            DB::statement("UPDATE laboratory_examination_results SET exam_reference = NULL WHERE exam_reference IS NOT NULL AND TRIM(exam_reference) = ''");

            // Deduplicate any existing non-null duplicates if present
            $duplicates = DB::table('laboratory_examination_results')
                ->select('exam_reference', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('exam_reference')
                ->groupBy('exam_reference')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                $ids = DB::table('laboratory_examination_results')
                    ->where('exam_reference', $dup->exam_reference)
                    ->orderBy('id')
                    ->pluck('id');

                // Keep the first, suffix subsequent
                $first = true;
                $counter = 1;
                foreach ($ids as $id) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $newRef = $dup->exam_reference . '-DUP' . ($counter++);
                    DB::table('laboratory_examination_results')
                        ->where('id', $id)
                        ->update(['exam_reference' => $newRef]);
                }
            }

            if (!Schema::hasIndex('laboratory_examination_results', 'lab_exam_results_exam_reference_unique')) {
                Schema::table('laboratory_examination_results', function (Blueprint $table) {
                    $table->unique('exam_reference', 'lab_exam_results_exam_reference_unique');
                });
            }
        }

        // 3. Sanitize empty string values in associate_lab_examinations before adding unique index
        if (Schema::hasTable('associate_lab_examinations') && Schema::hasColumn('associate_lab_examinations', 'exam_reference')) {
            DB::statement("UPDATE associate_lab_examinations SET exam_reference = NULL WHERE exam_reference IS NOT NULL AND TRIM(exam_reference) = ''");

            $assocDuplicates = DB::table('associate_lab_examinations')
                ->select('exam_reference', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('exam_reference')
                ->groupBy('exam_reference')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($assocDuplicates as $dup) {
                $ids = DB::table('associate_lab_examinations')
                    ->where('exam_reference', $dup->exam_reference)
                    ->orderBy('id')
                    ->pluck('id');

                $first = true;
                $counter = 1;
                foreach ($ids as $id) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $newRef = $dup->exam_reference . '-DUP' . ($counter++);
                    DB::table('associate_lab_examinations')
                        ->where('id', $id)
                        ->update(['exam_reference' => $newRef]);
                }
            }

            if (!Schema::hasIndex('associate_lab_examinations', 'assoc_lab_exam_reference_unique')) {
                Schema::table('associate_lab_examinations', function (Blueprint $table) {
                    $table->unique('exam_reference', 'assoc_lab_exam_reference_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('associate_lab_examinations') && Schema::hasIndex('associate_lab_examinations', 'assoc_lab_exam_reference_unique')) {
            Schema::table('associate_lab_examinations', function (Blueprint $table) {
                $table->dropUnique('assoc_lab_exam_reference_unique');
            });
        }

        if (Schema::hasTable('laboratory_examination_results') && Schema::hasIndex('laboratory_examination_results', 'lab_exam_results_exam_reference_unique')) {
            Schema::table('laboratory_examination_results', function (Blueprint $table) {
                $table->dropUnique('lab_exam_results_exam_reference_unique');
            });
        }

        Schema::dropIfExists('reference_sequences');
    }
};
