<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\Exhibit;
use App\Models\AssociateLabExamination;

echo "=== STARTING LAB RESULTS SYNC & BACKFILL ===\n";

$singleCases = SingleSuspectCase::all();
foreach ($singleCases as $case) {
    echo "Processing Single Suspect Case #{$case->id} ({$case->case_number})...\n";
    $dd = $case->docket_data ?? [];
    $labData = $dd['lab_examination'] ?? [];
    
    // Check if there is an AssociateLabExamination marked for primary suspect
    $primaryAssoc = AssociateLabExamination::where('case_id', $case->id)
        ->where(function($q) use ($case) {
            $q->where('suspect_name', 'Primary Suspect')
              ->orWhere('suspect_name', $case->suspect_name);
        })->first();

    $matchedExh = Exhibit::where('single_suspect_case_id', $case->id)->first();

    $sampleTaken = $labData['sample_weight']['sample_taken'] 
        ?? ($labData['sample_weight_taken'] 
        ?? ($labData['sample_taken'] 
        ?? ($primaryAssoc->sample_weight_taken 
        ?? ($case->sample_weight_taken ?? null))));

    $sampleReserved = $labData['sample_weight']['reserved_samples'] 
        ?? ($labData['sample_weight_reserved'] 
        ?? ($labData['sample_reserved'] 
        ?? ($primaryAssoc->sample_weight_reserved 
        ?? ($case->sample_weight_reserved ?? null))));

    $confResult = $labData['confirmatory_result'] 
        ?? ($labData['confirmatory_test_result'] 
        ?? ($primaryAssoc->confirmatory_result 
        ?? ($case->confirmatory_test_result ?: 'YES')));

    $presResult = $labData['presumptive_result'] 
        ?? ($primaryAssoc->presumptive_result 
        ?? ($case->presumptive_test_result ?? null));

    $subId = $labData['substance_identification'] 
        ?? ($primaryAssoc->substance_identification 
        ?? ($case->confirmatory_substance_identification ?? null));

    $testMethods = $labData['test_methods'] 
        ?? ($primaryAssoc->test_methods 
        ?? ($case->confirmatory_test_methods ?? null));

    $specs = $labData['specifications'] 
        ?? ($primaryAssoc->specifications 
        ?? ($case->confirmatory_specifications ?? null));

    $findings = $labData['findings'] 
        ?? ($primaryAssoc->findings 
        ?? ($case->laboratory_analytical_findings ?? null));

    $labNum = $labData['laboratory_number'] 
        ?? ($primaryAssoc->laboratory_number 
        ?? ($case->laboratory_number ?? null));

    $analyst = $labData['analyst'] 
        ?? ($primaryAssoc->analyst 
        ?? ($case->submitting_officer ?? null));

    $hasSaved = !empty($labData['has_saved']) || !empty($primaryAssoc->has_saved) || !empty($presResult) || !empty($confResult);

    if ($hasSaved) {
        $labMatchKey = $matchedExh ? ['exhibit_id' => $matchedExh->id] : ['single_suspect_case_id' => $case->id];

        LaboratoryExaminationResult::updateOrCreate(
            $labMatchKey,
            [
                'case_type'               => 'SINGLE',
                'single_suspect_case_id'  => $case->id,
                'multiple_suspect_case_id'=> null,
                'suspect_id'              => $matchedExh ? $matchedExh->suspect_id : null,
                'exhibit_id'              => $matchedExh ? $matchedExh->id : null,
                'laboratory_number'       => $labNum,
                'analyst'                 => $analyst,
                'presumptive_result'      => $presResult,
                'confirmatory_result'     => $confResult ?: 'YES',
                'sample_weight_description'=> $labData['sample_weight']['description'] ?? ($primaryAssoc->sample_weight_description ?? ($case->sample_weight_description ?? null)),
                'sample_weight_units'      => $labData['sample_weight']['units_packaging'] ?? ($primaryAssoc->sample_weight_units ?? ($case->sample_weight_units ?? null)),
                'sample_weight_gross'      => $labData['sample_weight']['gross_weight'] ?? ($primaryAssoc->sample_weight_gross ?? ($case->sample_weight_gross ?? null)),
                'sample_weight_taken'      => $sampleTaken,
                'sample_weight_reserved'   => $sampleReserved,
                'substance_identification' => $subId,
                'detected_substances'      => $labData['detected_substances'] ?? ($primaryAssoc->detected_substances ?? ($case->detected_substances ?? [])),
                'test_methods'             => $testMethods,
                'specifications'           => $specs,
                'findings'                 => $findings,
                'has_saved'                => true,
            ]
        );

        $case->update([
            'sample_weight_taken' => $sampleTaken,
            'sample_weight_reserved' => $sampleReserved,
            'confirmatory_test_result' => $confResult ?: 'YES',
            'confirmatory_substance_identification' => $subId,
            'confirmatory_test_methods' => $testMethods,
            'confirmatory_specifications' => $specs,
        ]);

        if ($primaryAssoc) {
            $primaryAssoc->delete();
        }
        echo "  ✓ Synced LaboratoryExaminationResult for Single Case #{$case->id}.\n";
    }
}

$multiCases = MultipleSuspectCase::all();
foreach ($multiCases as $case) {
    echo "Processing Multiple Suspect Case #{$case->id} ({$case->case_number})...\n";
    $dd = $case->docket_data ?? [];
    $labData = $dd['lab_examination'] ?? [];
    
    $primaryAssoc = AssociateLabExamination::where('case_id', $case->id)
        ->where(function($q) use ($case) {
            $q->where('suspect_name', 'Primary Suspect')
              ->orWhere('suspect_name', $case->suspect_name);
        })->first();

    $matchedExh = Exhibit::where('multiple_suspect_case_id', $case->id)->first();

    $sampleTaken = $labData['sample_weight']['sample_taken'] 
        ?? ($labData['sample_weight_taken'] 
        ?? ($labData['sample_taken'] 
        ?? ($primaryAssoc->sample_weight_taken 
        ?? ($case->sample_weight_taken ?? null))));

    $sampleReserved = $labData['sample_weight']['reserved_samples'] 
        ?? ($labData['sample_weight_reserved'] 
        ?? ($labData['sample_reserved'] 
        ?? ($primaryAssoc->sample_weight_reserved 
        ?? ($case->sample_weight_reserved ?? null))));

    $confResult = $labData['confirmatory_result'] 
        ?? ($labData['confirmatory_test_result'] 
        ?? ($primaryAssoc->confirmatory_result 
        ?? ($case->confirmatory_test_result ?: 'YES')));

    $presResult = $labData['presumptive_result'] 
        ?? ($primaryAssoc->presumptive_result 
        ?? ($case->presumptive_test_result ?? null));

    $subId = $labData['substance_identification'] 
        ?? ($primaryAssoc->substance_identification 
        ?? ($case->confirmatory_substance_identification ?? null));

    $testMethods = $labData['test_methods'] 
        ?? ($primaryAssoc->test_methods 
        ?? ($case->confirmatory_test_methods ?? null));

    $specs = $labData['specifications'] 
        ?? ($primaryAssoc->specifications 
        ?? ($case->confirmatory_specifications ?? null));

    $findings = $labData['findings'] 
        ?? ($primaryAssoc->findings 
        ?? ($case->laboratory_analytical_findings ?? null));

    $labNum = $labData['laboratory_number'] 
        ?? ($primaryAssoc->laboratory_number 
        ?? ($case->laboratory_number ?? null));

    $analyst = $labData['analyst'] 
        ?? ($primaryAssoc->analyst 
        ?? ($case->submitting_officer ?? null));

    $hasSaved = !empty($labData['has_saved']) || !empty($primaryAssoc->has_saved) || !empty($presResult) || !empty($confResult);

    if ($hasSaved) {
        $labMatchKey = $matchedExh ? ['exhibit_id' => $matchedExh->id] : ['multiple_suspect_case_id' => $case->id];

        LaboratoryExaminationResult::updateOrCreate(
            $labMatchKey,
            [
                'case_type'               => 'MULTIPLE',
                'single_suspect_case_id'  => null,
                'multiple_suspect_case_id'=> $case->id,
                'suspect_id'              => $matchedExh ? $matchedExh->suspect_id : null,
                'exhibit_id'              => $matchedExh ? $matchedExh->id : null,
                'laboratory_number'       => $labNum,
                'analyst'                 => $analyst,
                'presumptive_result'      => $presResult,
                'confirmatory_result'     => $confResult ?: 'YES',
                'sample_weight_description'=> $labData['sample_weight']['description'] ?? ($primaryAssoc->sample_weight_description ?? ($case->sample_weight_description ?? null)),
                'sample_weight_units'      => $labData['sample_weight']['units_packaging'] ?? ($primaryAssoc->sample_weight_units ?? ($case->sample_weight_units ?? null)),
                'sample_weight_gross'      => $labData['sample_weight']['gross_weight'] ?? ($primaryAssoc->sample_weight_gross ?? ($case->sample_weight_gross ?? null)),
                'sample_weight_taken'      => $sampleTaken,
                'sample_weight_reserved'   => $sampleReserved,
                'substance_identification' => $subId,
                'detected_substances'      => $labData['detected_substances'] ?? ($primaryAssoc->detected_substances ?? ($case->detected_substances ?? [])),
                'test_methods'             => $testMethods,
                'specifications'           => $specs,
                'findings'                 => $findings,
                'has_saved'                => true,
            ]
        );

        $case->update([
            'sample_weight_taken' => $sampleTaken,
            'sample_weight_reserved' => $sampleReserved,
            'confirmatory_test_result' => $confResult ?: 'YES',
            'confirmatory_substance_identification' => $subId,
            'confirmatory_test_methods' => $testMethods,
            'confirmatory_specifications' => $specs,
        ]);

        if ($primaryAssoc) {
            $primaryAssoc->delete();
        }
        echo "  ✓ Synced LaboratoryExaminationResult for Multiple Case #{$case->id}.\n";
    }
}

echo "=== BACKFILL COMPLETE ===\n";
