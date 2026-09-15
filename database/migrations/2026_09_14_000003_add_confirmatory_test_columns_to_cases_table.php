<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ForensicCase;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'sample_weight_description')) {
                $table->text('sample_weight_description')->nullable();
            }
            if (!Schema::hasColumn('cases', 'sample_weight_gross')) {
                $table->string('sample_weight_gross')->nullable();
            }
            if (!Schema::hasColumn('cases', 'sample_weight_taken')) {
                $table->string('sample_weight_taken')->nullable();
            }
            if (!Schema::hasColumn('cases', 'sample_weight_reserved')) {
                $table->string('sample_weight_reserved')->nullable();
            }
            if (!Schema::hasColumn('cases', 'confirmatory_substance_identification')) {
                $table->string('confirmatory_substance_identification')->nullable();
            }
            if (!Schema::hasColumn('cases', 'detected_substances')) {
                $table->json('detected_substances')->nullable();
            }
            if (!Schema::hasColumn('cases', 'confirmatory_test_methods')) {
                $table->string('confirmatory_test_methods')->nullable();
            }
            if (!Schema::hasColumn('cases', 'confirmatory_specifications')) {
                $table->text('confirmatory_specifications')->nullable();
            }
        });

        // Migrate data from docket_data->lab_examination for existing records
        $cases = ForensicCase::all();
        foreach ($cases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $labExam = $docketData['lab_examination'] ?? [];
            $sampleWeight = $labExam['sample_weight'] ?? [];

            $case->sample_weight_description = $sampleWeight['description'] ?? ($case->sample_weight_description ?? null);
            $case->sample_weight_gross = $sampleWeight['gross_weight'] ?? ($case->sample_weight_gross ?? null);
            $case->sample_weight_taken = $sampleWeight['sample_taken'] ?? ($case->sample_weight_taken ?? null);
            $case->sample_weight_reserved = $sampleWeight['reserved_samples'] ?? ($case->sample_weight_reserved ?? null);
            $case->confirmatory_substance_identification = $labExam['substance_identification'] ?? ($case->confirmatory_substance_identification ?? null);
            $case->detected_substances = $labExam['detected_substances'] ?? ($case->detected_substances ?? null);
            $case->confirmatory_test_methods = $labExam['test_methods'] ?? ($case->confirmatory_test_methods ?? null);
            $case->confirmatory_specifications = $labExam['specifications'] ?? ($case->confirmatory_specifications ?? null);
            $case->save();
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                Schema::hasColumn('cases', 'sample_weight_description') ? 'sample_weight_description' : null,
                Schema::hasColumn('cases', 'sample_weight_gross') ? 'sample_weight_gross' : null,
                Schema::hasColumn('cases', 'sample_weight_taken') ? 'sample_weight_taken' : null,
                Schema::hasColumn('cases', 'sample_weight_reserved') ? 'sample_weight_reserved' : null,
                Schema::hasColumn('cases', 'confirmatory_substance_identification') ? 'confirmatory_substance_identification' : null,
                Schema::hasColumn('cases', 'detected_substances') ? 'detected_substances' : null,
                Schema::hasColumn('cases', 'confirmatory_test_methods') ? 'confirmatory_test_methods' : null,
                Schema::hasColumn('cases', 'confirmatory_specifications') ? 'confirmatory_specifications' : null,
            ]);
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
