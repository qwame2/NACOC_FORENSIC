<?php

namespace App\Services;

use App\Models\ForensicCase;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;

/**
 * Service handling laboratory examination persistence, exhibit linkage, and docket synchronization.
 * WHY: The lab-exam save logic was triplicated across store(), updateCase(),
 * and updateUnitProgress(), each with subtly different bugs. Centralizing
 * it here ensures consistency and makes future fixes one-line changes.
 */
class LaboratoryExamService
{
    /**
     * Build standard lab examination record array.
     *
     * @param array $payload
     * @param string|null $ownerName
     * @param bool $isAssoc
     * @param string|null $officer
     * @return array
     */
    public function buildExamRecord(array $payload, ?string $ownerName, bool $isAssoc, ?string $officer): array
    {
        $rawExamRef = $payload['lab_exam_reference'] ?? ($payload['exam_reference'] ?? null);
        if (!empty($rawExamRef)) {
            $labExamRef = str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $rawExamRef);
        } else {
            $labExamRef = 'NLR-FM-T35-F';
        }

        $detSubs = $payload['detected_substances'] ?? [];
        if (is_string($detSubs)) {
            $detSubs = json_decode($detSubs, true) ?: [];
        }
        if (!is_array($detSubs)) {
            $detSubs = [];
        }
        foreach ($detSubs as &$ds) {
            if (is_array($ds) && isset($ds['percentage']) && $ds['percentage'] !== '') {
                $pctVal = trim((string)$ds['percentage']);
                if (!str_ends_with($pctVal, '%')) {
                    $ds['percentage'] = $pctVal . '%';
                }
            }
        }
        unset($ds);

        $presumptive = $payload['presumptive_result']
            ?? ($payload['presumptive_test_result']
            ?? ($payload['presumptive_reaction_result_input'] ?? null));

        $confirmatory = $payload['confirmatory_test_result']
            ?? ($payload['confirmatory_result'] ?? 'YES');

        $sampleWeight = [
            'description'      => $payload['sample_weight_description'] ?? ($payload['sample_weight']['description'] ?? ($payload['sample_description'] ?? null)),
            'units_packaging'  => $payload['sample_weight_units'] ?? ($payload['sample_weight']['units_packaging'] ?? ($payload['sample_units'] ?? null)),
            'gross_weight'     => $payload['sample_weight_gross'] ?? ($payload['sample_weight']['gross_weight'] ?? ($payload['sample_gross'] ?? null)),
            'sample_taken'     => $payload['sample_weight_taken'] ?? ($payload['sample_taken'] ?? ($payload['sample_weight']['sample_taken'] ?? null)),
            'reserved_samples' => $payload['sample_weight_reserved'] ?? ($payload['sample_reserved'] ?? ($payload['sample_weight']['reserved_samples'] ?? null)),
        ];

        return [
            'exhibit_owner_name'       => $ownerName,
            'exhibit_is_associate'     => $isAssoc,
            'laboratory_number'        => $payload['laboratory_number'] ?? ($payload['lab_number'] ?? null),
            'exam_reference'           => $labExamRef,
            'exhibit_number'           => $payload['lab_exhibit_number'] ?? ($payload['exhibit_number'] ?? null),
            'lab_unit'                 => $payload['lab_unit'] ?? 'Chemistry & Narcotics Unit',
            'analyst'                  => $officer,
            'date_received'            => $payload['lab_date_received'] ?? ($payload['date_received'] ?? date('Y-m-d')),
            'date_started'             => $payload['lab_date_started'] ?? ($payload['date_started'] ?? date('Y-m-d')),
            'date_completed'           => $payload['lab_date_completed'] ?? ($payload['date_completed'] ?? date('Y-m-d')),
            'presumptive_result'       => $presumptive,
            'confirmatory_result'      => $confirmatory,
            'sample_weight'            => $sampleWeight,
            'substance_identification' => $payload['confirmatory_substance_identification'] ?? ($payload['substance_identification'] ?? null),
            'detected_substances'      => $detSubs,
            'test_methods'             => $payload['confirmatory_test_methods'] ?? ($payload['test_methods'] ?? null),
            'specifications'           => $payload['confirmatory_specifications'] ?? ($payload['specifications'] ?? null),
            'remark'                   => $payload['confirmatory_remark'] ?? ($payload['remark'] ?? null),
            'findings'                 => $payload['findings'] ?? ($payload['notes'] ?? 'Submitted via Laboratory Analysis & Case Registration Workspace'),
            'has_saved'                => true,
        ];
    }

    /**
     * Save laboratory exam, sync relational models, and return updated docket data.
     *
     * @param ForensicCase $case
     * @param array $payload
     * @param string|null $ownerName
     * @param bool $isAssoc
     * @param string|null $officer
     * @return array
     */
    public function saveExam(ForensicCase $case, array $payload, ?string $ownerName = null, bool $isAssoc = false, ?string $officer = null): array
    {
        $docketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        $examRecord = $this->buildExamRecord($payload, $ownerName, $isAssoc, $officer);

        $isMultiple = ($case->underlyingRecord instanceof MultipleSuspectCase);
        $singleCaseId = $isMultiple ? null : $case->id;
        $multiCaseId  = $isMultiple ? $case->id : null;

        // Auto-derive weight fields from exhibit if missing
        if (empty($examRecord['sample_weight']['description']) || empty($examRecord['sample_weight']['gross_weight'])) {
            $primaryExhForWeight = Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            })->whereNotNull('seizure_weight')->first()
            ?? Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            })->first();

            if ($primaryExhForWeight) {
                if (empty($examRecord['sample_weight']['description'])) {
                    $examRecord['sample_weight']['description'] = $primaryExhForWeight->commodity_name;
                }
                if (empty($examRecord['sample_weight']['units_packaging'])) {
                    $examRecord['sample_weight']['units_packaging'] = trim(($primaryExhForWeight->seizure_quantity ?? '') . ' ' . ($primaryExhForWeight->seizure_packaging ?? '')) ?: null;
                }
                if (empty($examRecord['sample_weight']['gross_weight'])) {
                    $examRecord['sample_weight']['gross_weight'] = trim(($primaryExhForWeight->seizure_weight ?? '') . ' ' . ($primaryExhForWeight->seizure_unit ?? '')) ?: null;
                }
            }
        }

        // 1. Sync matching seizure in docketData
        if (!isset($docketData['seizures']) || !is_array($docketData['seizures'])) {
            $docketData['seizures'] = [];
        }

        $matchedSeizureIdx = null;
        foreach ($docketData['seizures'] as $sIdx => $sz) {
            $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
            $szExhNo = $sz['exhibit_number'] ?? null;
            $szDrug  = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null);

            if (!empty($examRecord['exhibit_number']) && !empty($szExhNo) && $szExhNo === $examRecord['exhibit_number']) {
                $matchedSeizureIdx = $sIdx;
                break;
            }
            if (!empty($ownerName) && !empty($szOwner) && strtolower(trim($szOwner)) === strtolower(trim($ownerName))) {
                $matchedSeizureIdx = $sIdx;
                break;
            }
            if (!empty($examRecord['sample_weight']['description']) && !empty($szDrug) && strtolower(trim($szDrug)) === strtolower(trim($examRecord['sample_weight']['description']))) {
                $matchedSeizureIdx = $sIdx;
                break;
            }
        }

        if ($matchedSeizureIdx === null && count($docketData['seizures']) > 0 && !$isAssoc) {
            $matchedSeizureIdx = 0;
        }

        if ($matchedSeizureIdx !== null && isset($docketData['seizures'][$matchedSeizureIdx])) {
            $docketData['seizures'][$matchedSeizureIdx]['has_saved_lab']          = true;
            $docketData['seizures'][$matchedSeizureIdx]['presumptive_result']     = $examRecord['presumptive_result'];
            $docketData['seizures'][$matchedSeizureIdx]['confirmatory_result']    = $examRecord['confirmatory_result'];
            $docketData['seizures'][$matchedSeizureIdx]['substance_identification'] = $examRecord['substance_identification'];
            $docketData['seizures'][$matchedSeizureIdx]['sample_description']     = $examRecord['sample_weight']['description'];
            $docketData['seizures'][$matchedSeizureIdx]['sample_units']           = $examRecord['sample_weight']['units_packaging'];
            $docketData['seizures'][$matchedSeizureIdx]['sample_gross']           = $examRecord['sample_weight']['gross_weight'];
            $docketData['seizures'][$matchedSeizureIdx]['sample_taken']           = $examRecord['sample_weight']['sample_taken'];
            $docketData['seizures'][$matchedSeizureIdx]['sample_reserved']        = $examRecord['sample_weight']['reserved_samples'];
            $docketData['seizures'][$matchedSeizureIdx]['detected_substances']    = $examRecord['detected_substances'];
            $docketData['seizures'][$matchedSeizureIdx]['test_methods']           = $examRecord['test_methods'];
            $docketData['seizures'][$matchedSeizureIdx]['specifications']         = $examRecord['specifications'];
            $docketData['seizures'][$matchedSeizureIdx]['remark']                 = $examRecord['remark'];
            $docketData['seizures'][$matchedSeizureIdx]['findings']               = $examRecord['findings'];
            $docketData['seizures'][$matchedSeizureIdx]['analyst']                = $officer;
            $docketData['seizures'][$matchedSeizureIdx]['laboratory_number']      = $examRecord['laboratory_number'];
            $docketData['seizures'][$matchedSeizureIdx]['exam_reference']         = $examRecord['exam_reference'];
            $docketData['seizures'][$matchedSeizureIdx]['exhibit_number']         = $examRecord['exhibit_number'];
            $docketData['seizures'][$matchedSeizureIdx]['date_received']          = $examRecord['date_received'];
            $docketData['seizures'][$matchedSeizureIdx]['date_started']           = $examRecord['date_started'];
            $docketData['seizures'][$matchedSeizureIdx]['date_completed']         = $examRecord['date_completed'];
        }

        // 2. Sync lab_examinations_list
        if (!isset($docketData['lab_examinations_list']) || !is_array($docketData['lab_examinations_list'])) {
            $docketData['lab_examinations_list'] = [];
        }

        $listMatchIdx = null;
        foreach ($docketData['lab_examinations_list'] as $lIdx => $lItem) {
            if (!empty($ownerName) && !empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($ownerName))) {
                $listMatchIdx = $lIdx;
                break;
            }
            if (!empty($examRecord['exhibit_number']) && !empty($lItem['exhibit_number']) && $lItem['exhibit_number'] === $examRecord['exhibit_number']) {
                $listMatchIdx = $lIdx;
                break;
            }
        }

        if ($listMatchIdx !== null) {
            $docketData['lab_examinations_list'][$listMatchIdx] = array_merge($docketData['lab_examinations_list'][$listMatchIdx], array_filter($examRecord));
        } else {
            $docketData['lab_examinations_list'][] = $examRecord;
        }

        // 3. Associate vs Primary Handling
        if ($isAssoc) {
            $canonicalName = $ownerName ?: 'Associate Suspect';

            $existingExam = AssociateLabExamination::where('case_id', $case->id)
                ->where(function ($q) use ($canonicalName) {
                    $q->where('suspect_name', $canonicalName)
                      ->orWhereRaw('LOWER(TRIM(suspect_name)) = ?', [strtolower(trim($canonicalName))]);
                })->first();

            AssociateLabExamination::updateOrCreate(
                [
                    'id' => $existingExam ? $existingExam->id : null,
                ],
                [
                    'case_id'                   => $case->id,
                    'suspect_name'              => $canonicalName,
                    'associate_id'              => $payload['associate_id'] ?? null,
                    'exhibit_number'            => $examRecord['exhibit_number'],
                    'laboratory_number'         => $examRecord['laboratory_number'],
                    'exam_reference'            => $examRecord['exam_reference'],
                    'lab_unit'                  => $examRecord['lab_unit'],
                    'analyst'                   => $officer,
                    'date_received'             => $examRecord['date_received'],
                    'date_started'              => $examRecord['date_started'],
                    'date_completed'            => $examRecord['date_completed'],
                    'presumptive_result'        => $examRecord['presumptive_result'],
                    'confirmatory_result'       => $examRecord['confirmatory_result'],
                    'sample_weight_description' => $examRecord['sample_weight']['description'],
                    'sample_weight_units'       => $examRecord['sample_weight']['units_packaging'],
                    'sample_weight_gross'       => $examRecord['sample_weight']['gross_weight'],
                    'sample_weight_taken'       => $examRecord['sample_weight']['sample_taken'],
                    'sample_weight_reserved'    => $examRecord['sample_weight']['reserved_samples'],
                    'substance_identification'  => $examRecord['substance_identification'],
                    'detected_substances'       => $examRecord['detected_substances'],
                    'test_methods'              => $examRecord['test_methods'],
                    'specifications'            => $examRecord['specifications'],
                    'remark'                    => $examRecord['remark'],
                    'findings'                  => $examRecord['findings'],
                    'has_saved'                 => true,
                ]
            );

            if (!isset($docketData['associate_lab_examinations']) || !is_array($docketData['associate_lab_examinations'])) {
                $docketData['associate_lab_examinations'] = [];
            }
            $docketData['associate_lab_examinations'][$canonicalName] = $examRecord;

            if (isset($docketData['lab_examination'])) {
                $docketData['lab_examination']['exhibit_is_associate'] = false;
            }
        } else {
            // Primary suspect examination
            $docketData['lab_examination'] = $examRecord;
            $docketData['confirmatory_detected_substances'] = $examRecord['detected_substances'];

            $primaryExhModel = Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            })->first();

            if ($primaryExhModel && !empty($examRecord['remark'])) {
                $primaryExhModel->update(['lab_remark' => $examRecord['remark']]);
            }

            LaboratoryExaminationResult::updateOrCreate(
                [
                    'single_suspect_case_id'   => $singleCaseId,
                    'multiple_suspect_case_id' => $multiCaseId,
                ],
                [
                    'case_type'                 => $isMultiple ? 'MULTIPLE' : 'SINGLE',
                    'single_suspect_case_id'    => $singleCaseId,
                    'multiple_suspect_case_id'  => $multiCaseId,
                    'exhibit_id'                => $primaryExhModel ? $primaryExhModel->id : null,
                    'laboratory_number'         => $examRecord['laboratory_number'],
                    'gsa_lab_number'            => $payload['gsa_lab_number'] ?? ($docketData['lab_examination']['gsa_lab_number'] ?? null),
                    'presumptive_result'        => $examRecord['presumptive_result'],
                    'confirmatory_result'       => $examRecord['confirmatory_result'],
                    'sample_weight_description' => $examRecord['sample_weight']['description'],
                    'sample_weight_units'       => $examRecord['sample_weight']['units_packaging'],
                    'sample_weight_gross'       => $examRecord['sample_weight']['gross_weight'],
                    'sample_weight_taken'       => $examRecord['sample_weight']['sample_taken'],
                    'sample_weight_reserved'    => $examRecord['sample_weight']['reserved_samples'],
                    'substance_identification'  => $examRecord['substance_identification'],
                    'detected_substances'       => $examRecord['detected_substances'],
                    'test_methods'              => $examRecord['test_methods'],
                    'specifications'            => $examRecord['specifications'],
                    'remark'                    => $examRecord['remark'],
                    'findings'                  => $examRecord['findings'],
                    'analyst'                   => $officer,
                    'has_saved'                 => true,
                ]
            );

            // Clean up any associate record that may have been created for primary suspect
            $cleanName = $ownerName ?: ($case->suspect_name ?? 'Primary Suspect');
            AssociateLabExamination::where('case_id', $case->id)
                ->where(function ($q) use ($cleanName) {
                    $q->where('suspect_name', $cleanName)
                      ->orWhere('suspect_name', 'Primary Suspect');
                })->delete();
        }

        // Synchronize case attributes
        if (!empty($examRecord['laboratory_number'])) {
            $case->laboratory_number = $examRecord['laboratory_number'];
        }
        if (!empty($examRecord['presumptive_result'])) {
            $case->presumptive_test_result = $examRecord['presumptive_result'];
        }
        if (!empty($examRecord['confirmatory_result'])) {
            $case->confirmatory_test_result = $examRecord['confirmatory_result'];
        }
        if (!empty($examRecord['sample_weight']['description'])) {
            $case->sample_weight_description = $examRecord['sample_weight']['description'];
        }
        if (!empty($examRecord['sample_weight']['gross_weight'])) {
            $case->sample_weight_gross = $examRecord['sample_weight']['gross_weight'];
        }
        if (!empty($examRecord['sample_weight']['sample_taken'])) {
            $case->sample_weight_taken = $examRecord['sample_weight']['sample_taken'];
        }
        if (!empty($examRecord['sample_weight']['reserved_samples'])) {
            $case->sample_weight_reserved = $examRecord['sample_weight']['reserved_samples'];
        }
        if (!empty($examRecord['substance_identification'])) {
            $case->confirmatory_substance_identification = $examRecord['substance_identification'];
        }
        if (!empty($examRecord['detected_substances'])) {
            $case->detected_substances = $examRecord['detected_substances'];
            if ($case->underlyingRecord && \App\Services\SchemaCache::hasColumn($case->underlyingRecord->getTable(), 'detected_substances')) {
                try {
                    $case->underlyingRecord->detected_substances = is_array($examRecord['detected_substances'])
                        ? json_encode($examRecord['detected_substances'])
                        : $examRecord['detected_substances'];
                    $case->underlyingRecord->save();
                } catch (\Throwable $e) {}
            }
        }
        if (!empty($examRecord['test_methods'])) {
            $case->confirmatory_test_methods = $examRecord['test_methods'];
        }
        if (!empty($examRecord['specifications'])) {
            $case->confirmatory_specifications = $examRecord['specifications'];
        }
        if (!empty($examRecord['remark'])) {
            $case->confirmatory_remark = $examRecord['remark'];
        }

        $case->docket_data = $docketData;
        $case->save();

        return $docketData;
    }
}
