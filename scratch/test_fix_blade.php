<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

$suspectsList = $docketData['suspects'] ?? [];
$primarySuspectNamesList = [];
$coSuspectNamesList = [];
$associateSuspectNamesList = [];

foreach ($suspectsList as $sp) {
    $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect');
    $spIsCo = $spIsAssoc && (
        (!empty($sp['association_role']) && (str_contains(strtolower($sp['association_role']), 'co-suspect') || str_contains(strtolower($sp['association_role']), 'co suspect')))
        || (!empty($sp['role']) && (str_contains(strtolower($sp['role']), 'co-suspect') || str_contains(strtolower($sp['role']), 'co suspect')))
        || (!empty($sp['suspect_role']) && (str_contains(strtolower($sp['suspect_role']), 'co-suspect') || str_contains(strtolower($sp['suspect_role']), 'co suspect')))
    );
    if (!$spIsAssoc && !empty($sp['suspect_name'])) {
        $primarySuspectNamesList[] = $sp['suspect_name'];
    } elseif ($spIsCo && !empty($sp['suspect_name'])) {
        $coSuspectNamesList[] = $sp['suspect_name'];
    } elseif ($spIsAssoc && !empty($sp['suspect_name'])) {
        $associateSuspectNamesList[] = $sp['suspect_name'];
    }
}
$primaryAndCoNamesList = array_unique(array_merge($primarySuspectNamesList, $coSuspectNamesList));

$labExam = $docketData['lab_examination'] ?? [];

if (empty($labExam['has_saved']) && empty($labExam['presumptive_result'])) {
    if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
        foreach ($docketData['lab_examinations_list'] as $lItem) {
            if (!empty($lItem['has_saved']) || !empty($lItem['presumptive_result'])) {
                $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
                $isPrimaryOrCo = false;
                if (empty($lOwner) || $lOwner === 'primary suspect' || str_contains($lOwner, 'primary')) {
                    $isPrimaryOrCo = true;
                } elseif (!empty($primaryAndCoNamesList)) {
                    foreach ($primaryAndCoNamesList as $pn) {
                        $pnLow = strtolower(trim($pn));
                        if ($lOwner === $pnLow || str_contains($lOwner, $pnLow) || str_contains($pnLow, $lOwner)) {
                            $isPrimaryOrCo = true;
                            break;
                        }
                    }
                } else {
                    $isPrimaryOrCo = true;
                }

                if ($isPrimaryOrCo) {
                    $labExam = array_merge($labExam, $lItem);
                    break;
                }
            }
        }
    }
}

$dbPrimaryLabExamPdf = \App\Models\AssociateLabExamination::where('case_id', $case->id)
    ->where('has_saved', true)
    ->get()
    ->first(function($aRec) use ($primaryAndCoNamesList) {
        $aName = strtolower(trim($aRec->suspect_name ?? ''));
        if (empty($aName) || $aName === 'primary suspect' || str_contains($aName, 'primary')) {
            return true;
        }
        if (!empty($primaryAndCoNamesList)) {
            foreach ($primaryAndCoNamesList as $pn) {
                $pnLow = strtolower(trim($pn));
                if ($aName === $pnLow || str_contains($aName, $pnLow) || str_contains($pnLow, $aName)) {
                    return true;
                }
            }
        }
        return false;
    });

if ($dbPrimaryLabExamPdf) {
    $labExam = array_merge($labExam, [
        'exhibit_owner_name'  => $dbPrimaryLabExamPdf->suspect_name,
        'laboratory_number'   => $dbPrimaryLabExamPdf->laboratory_number,
        'exam_reference'      => $dbPrimaryLabExamPdf->exam_reference,
        'exhibit_number'      => $dbPrimaryLabExamPdf->exhibit_number,
        'analyst'             => $dbPrimaryLabExamPdf->analyst,
        'date_received'       => is_object($dbPrimaryLabExamPdf->date_received) ? $dbPrimaryLabExamPdf->date_received->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_received, 0, 10),
        'date_started'        => is_object($dbPrimaryLabExamPdf->date_started) ? $dbPrimaryLabExamPdf->date_started->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_started, 0, 10),
        'date_completed'      => is_object($dbPrimaryLabExamPdf->date_completed) ? $dbPrimaryLabExamPdf->date_completed->format('Y-m-d') : substr((string)$dbPrimaryLabExamPdf->date_completed, 0, 10),
        'presumptive_result'  => $dbPrimaryLabExamPdf->presumptive_result,
        'confirmatory_result' => $dbPrimaryLabExamPdf->confirmatory_result,
        'sample_weight'       => [
            'description'      => $dbPrimaryLabExamPdf->sample_weight_description,
            'units_packaging'  => $dbPrimaryLabExamPdf->sample_weight_units,
            'gross_weight'     => $dbPrimaryLabExamPdf->sample_weight_gross,
            'sample_taken'     => $dbPrimaryLabExamPdf->sample_weight_taken,
            'reserved_samples' => $dbPrimaryLabExamPdf->sample_weight_reserved,
        ],
        'substance_identification' => $dbPrimaryLabExamPdf->substance_identification,
        'detected_substances'      => $dbPrimaryLabExamPdf->detected_substances,
        'test_methods'             => $dbPrimaryLabExamPdf->test_methods,
        'specifications'           => $dbPrimaryLabExamPdf->specifications,
        'findings'                 => $dbPrimaryLabExamPdf->findings,
        'has_saved'                => true,
    ]);
}

echo "Primary/Co Lab Exam Owner: " . ($labExam['exhibit_owner_name'] ?? 'N/A') . "\n";
echo "Primary/Co Presumptive: " . ($labExam['presumptive_result'] ?? 'N/A') . "\n";
echo "Primary/Co Substance ID / Description: " . ($labExam['sample_weight']['description'] ?? ($labExam['substance_identification'] ?? 'N/A')) . "\n";
