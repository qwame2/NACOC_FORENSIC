<?php

namespace App\Services;

use App\Models\ForensicCase;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Models\DrugScreening;
use App\Models\ReferenceRegistry;
use App\Services\DrugExhibitClassifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
        if (!empty($rawExamRef) && trim($rawExamRef) !== 'NLR-FM-T35-F' && trim($rawExamRef) !== 'NLA-FM-T35-F') {
            $labExamRef = app(CaseReferenceService::class)->normalizeExamReference($rawExamRef);
        } else {
            $labExamRef = app(CaseReferenceService::class)->generateExamReference('Lab Analysis');
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

        $rawScreening = $payload['screening_method'] ?? ($payload['confirmatory_screening_method'] ?? null);
        if (is_array($rawScreening)) {
            $filtered = array_values(array_filter(array_map('trim', $rawScreening), fn ($v) => $v !== ''));
            $screeningMethod = count($filtered) === 0 ? null : (count($filtered) === 1 ? $filtered[0] : json_encode($filtered));
        } elseif (is_string($rawScreening)) {
            $screeningMethod = trim($rawScreening) !== '' ? trim($rawScreening) : null;
        } else {
            $screeningMethod = null;
        }

        $dispositionOfExhibit = $payload['disposition_of_exhibit'] ?? ($payload['confirmatory_disposition_of_exhibit'] ?? null);

        $presumptive = $payload['presumptive_result']
            ?? ($payload['presumptive_test_result']
            ?? ($payload['presumptive_reaction_result_input'] ?? null));
        $rawPresPresence = $payload['presumptive_presence'] ?? null;
        $rawPresPercentage = $payload['presumptive_percentage'] ?? ($payload['presumptive_Percentage'] ?? null);
        $parsedPresumptive = ForensicCase::parsePresumptiveData(
            is_string($presumptive) ? $presumptive : null,
            $rawPresPresence,
            $rawPresPercentage
        );

        $confirmatory = $payload['confirmatory_test_result']
            ?? ($payload['confirmatory_result'] ?? 'YES');

        $sampleWeight = [
            'description'      => $payload['sample_weight_description'] ?? ($payload['sample_weight']['description'] ?? ($payload['sample_description'] ?? null)),
            'units_packaging'  => $payload['sample_weight_units'] ?? ($payload['sample_weight']['units_packaging'] ?? ($payload['sample_units'] ?? null)),
            'gross_weight'     => $payload['sample_weight_gross'] ?? ($payload['sample_weight']['gross_weight'] ?? ($payload['sample_gross'] ?? null)),
            'sample_taken'     => $payload['sample_weight_taken'] ?? ($payload['sample_taken'] ?? ($payload['sample_weight']['sample_taken'] ?? null)),
            'reserved_samples' => $payload['sample_weight_reserved'] ?? ($payload['sample_reserved'] ?? ($payload['sample_weight']['reserved_samples'] ?? null)),
        ];

        $labExhibitNo = $payload['lab_exhibit_no'] ?? ($payload['lab_exhibit_number'] ?? null);
        $systemExhibitNumber = $payload['system_exhibit_number'] ?? ($payload['exhibit_number'] ?? null);

        return [
            'exhibit_owner_name'       => $ownerName,
            'exhibit_is_associate'     => $isAssoc,
            'laboratory_number'        => $payload['laboratory_number'] ?? ($payload['lab_number'] ?? null),
            'exam_reference'           => $labExamRef,
            'purpose_of_examination'   => $payload['purpose_of_examination'] ?? ($payload['lab_purpose_of_examination'] ?? null),
            'preliminary_exam_procedure' => $payload['preliminary_exam_procedure'] ?? ($payload['lab_preliminary_exam_procedure'] ?? null),
            'screening_method'         => $screeningMethod,
            'exhibit_number'           => $systemExhibitNumber ?? $labExhibitNo,
            'lab_exhibit_no'           => $labExhibitNo,
            'exhibit_description'      => $payload['lab_exhibit_description'] ?? ($payload['exhibit_description'] ?? ($sampleWeight['description'] ?? null)),
            'lab_unit'                 => $payload['lab_unit'] ?? 'Chemistry & Narcotics Unit',
            'analyst'                  => $officer,
            'date_received'            => !empty($payload['lab_date_received']) ? $payload['lab_date_received'] : (!empty($payload['date_received']) ? $payload['date_received'] : date('Y-m-d')),
            'date_started'             => !empty($payload['lab_date_started']) ? $payload['lab_date_started'] : (!empty($payload['date_started']) ? $payload['date_started'] : date('Y-m-d')),
            'date_completed'           => !empty($payload['lab_date_completed']) ? $payload['lab_date_completed'] : (!empty($payload['date_completed']) ? $payload['date_completed'] : date('Y-m-d')),
            'presumptive_result'       => $presumptive ?: $parsedPresumptive['result'],
            'presumptive_db_result'    => $parsedPresumptive['result'],
            'presumptive_presence'     => $parsedPresumptive['presence'],
            'presumptive_percentage'   => $parsedPresumptive['percentage'],
            'confirmatory_result'      => $confirmatory,
            'sample_weight'            => $sampleWeight,
            'substance_identification' => $payload['confirmatory_substance_identification'] ?? ($payload['substance_identification'] ?? null),
            'detected_substances'      => $detSubs,
            'test_methods'             => $payload['confirmatory_test_methods'] ?? ($payload['test_methods'] ?? null),
            'specifications'           => $payload['confirmatory_specifications'] ?? ($payload['specifications'] ?? null),
            'remark'                   => $payload['confirmatory_remark'] ?? ($payload['remark'] ?? null),
            'disposition_of_exhibit'   => $dispositionOfExhibit,
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
            $primaryExhForWeight = (!empty($payload['exhibit_id']) ? Exhibit::find($payload['exhibit_id']) : null)
            ?? Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
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
            $szLabExhNo = $sz['lab_exhibit_no'] ?? null;
            $szDrug  = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null);

            if (!empty($payload['exhibit_id']) && (((int) ($sz['id'] ?? 0) === (int) $payload['exhibit_id']) || ((int) ($sz['linked_exhibit_id'] ?? 0) === (int) $payload['exhibit_id']))) {
                $matchedSeizureIdx = $sIdx;
                break;
            }
            if (!empty($examRecord['exhibit_number']) && !empty($szExhNo) && $szExhNo === $examRecord['exhibit_number']) {
                $matchedSeizureIdx = $sIdx;
                break;
            }
            if (!empty($examRecord['lab_exhibit_no']) && ((!empty($szLabExhNo) && $szLabExhNo === $examRecord['lab_exhibit_no']) || (!empty($szExhNo) && $szExhNo === $examRecord['lab_exhibit_no']))) {
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

        // Resolve matching Exhibit model in DB to persist lab_exhibit_no without overwriting exhibit_number
        $matchedExhibitModel = null;
        if (!empty($payload['exhibit_id'])) {
            $matchedExhibitModel = Exhibit::find($payload['exhibit_id']);
        }
        if (!$matchedExhibitModel) {
            $caseExhibits = Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            })->orderBy('id')->get();

            $targetSeizureExNo = ($matchedSeizureIdx !== null && !empty($docketData['seizures'][$matchedSeizureIdx]['exhibit_number']))
                ? $docketData['seizures'][$matchedSeizureIdx]['exhibit_number']
                : ($payload['system_exhibit_number'] ?? null);

            if ($targetSeizureExNo) {
                $matchedExhibitModel = $caseExhibits->first(fn ($ex) => $ex->exhibit_number === $targetSeizureExNo);
            }
            if (!$matchedExhibitModel && !empty($ownerName)) {
                $matchedExhibitModel = $caseExhibits->first(fn ($ex) => strtolower(trim((string) $ex->associated_person)) === strtolower(trim($ownerName)));
            }
            if (!$matchedExhibitModel && !empty($examRecord['sample_weight']['description'])) {
                $matchedExhibitModel = $caseExhibits->first(fn ($ex) => strtolower(trim((string) $ex->commodity_name)) === strtolower(trim((string) $examRecord['sample_weight']['description'])));
            }
            if (!$matchedExhibitModel && $matchedSeizureIdx !== null && isset($caseExhibits[$matchedSeizureIdx])) {
                $matchedExhibitModel = $caseExhibits[$matchedSeizureIdx];
            }
            if (!$matchedExhibitModel && !$isAssoc) {
                $matchedExhibitModel = $caseExhibits->first();
            }
        }

        if ($matchedExhibitModel) {
            $examRecord['exhibit_id'] = $matchedExhibitModel->id;
            $exUpdates = [];
            if ($examRecord['lab_exhibit_no'] !== null) {
                $exUpdates['lab_exhibit_no'] = $examRecord['lab_exhibit_no'];
            }
            if (!empty($examRecord['remark'])) {
                $exUpdates['lab_remark'] = $examRecord['remark'];
            }
            if (!empty($exUpdates)) {
                $matchedExhibitModel->update($exUpdates);
            }
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
            $docketData['seizures'][$matchedSeizureIdx]['purpose_of_examination'] = $examRecord['purpose_of_examination'];
            $docketData['seizures'][$matchedSeizureIdx]['preliminary_exam_procedure'] = $examRecord['preliminary_exam_procedure'] ?? null;
            $docketData['seizures'][$matchedSeizureIdx]['screening_method'] = $examRecord['screening_method'] ?? null;
            $docketData['seizures'][$matchedSeizureIdx]['disposition_of_exhibit'] = $examRecord['disposition_of_exhibit'] ?? null;
            if ($examRecord['lab_exhibit_no'] !== null) {
                $docketData['seizures'][$matchedSeizureIdx]['lab_exhibit_no']     = $examRecord['lab_exhibit_no'];
            }
            if (empty($docketData['seizures'][$matchedSeizureIdx]['exhibit_number'])) {
                $docketData['seizures'][$matchedSeizureIdx]['exhibit_number']     = $matchedExhibitModel ? $matchedExhibitModel->exhibit_number : $examRecord['exhibit_number'];
            }
            $docketData['seizures'][$matchedSeizureIdx]['date_received']          = $examRecord['date_received'];
            $docketData['seizures'][$matchedSeizureIdx]['date_started']           = $examRecord['date_started'];
            $docketData['seizures'][$matchedSeizureIdx]['date_completed']         = $examRecord['date_completed'];
        }

        // 2. Sync lab_examinations_list
        if (!isset($docketData['lab_examinations_list']) || !is_array($docketData['lab_examinations_list'])) {
            $docketData['lab_examinations_list'] = [];
        }

        $listMatchIdx = null;
        $targetExhibitId = (int) ($examRecord['exhibit_id'] ?? ($payload['exhibit_id'] ?? 0));
        foreach ($docketData['lab_examinations_list'] as $lIdx => $lItem) {
            $itemExhibitId = (int) ($lItem['exhibit_id'] ?? ($lItem['linked_exhibit_id'] ?? 0));
            if ($targetExhibitId > 0 && $itemExhibitId > 0) {
                if ($itemExhibitId === $targetExhibitId) {
                    $listMatchIdx = $lIdx;
                    break;
                }
                continue;
            }
            if (!empty($examRecord['exhibit_number']) && !empty($lItem['exhibit_number']) && $lItem['exhibit_number'] === $examRecord['exhibit_number']) {
                $listMatchIdx = $lIdx;
                break;
            }
            if (!empty($ownerName) && !empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($ownerName))) {
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

            if ($existingExam && !empty($existingExam->exam_reference)) {
                $examRecord['exam_reference'] = $existingExam->exam_reference;
            } else {
                if (empty($examRecord['exam_reference']) || $examRecord['exam_reference'] === 'NLR-FM-T35-F' || app(CaseReferenceService::class)->examReferenceExists($examRecord['exam_reference'])) {
                    $examRecord['exam_reference'] = app(CaseReferenceService::class)->generateExamReference($examRecord['lab_unit'] ?? null);
                }
            }

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    $savedAssocExam = AssociateLabExamination::updateOrCreate(
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
                            'disposition_of_exhibit'    => $examRecord['disposition_of_exhibit'] ?? ($payload['disposition_of_exhibit'] ?? ($payload['confirmatory_disposition_of_exhibit'] ?? null)),
                            'findings'                  => $examRecord['findings'],
                            'has_saved'                 => true,
                        ]
                    );

                    ReferenceRegistry::reserve($examRecord['exam_reference'], 'EXAM', AssociateLabExamination::class, $savedAssocExam->id);
                    break;
                } catch (QueryException $e) {
                    $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'duplicate')
                        || str_contains(strtolower($e->getMessage()), 'unique')
                        || ($e->errorInfo[1] ?? 0) === 1062;

                    if (!$isUniqueViolation || $attempt === 5) {
                        throw $e;
                    }
                    $examRecord['exam_reference'] = app(CaseReferenceService::class)->generateExamReference($examRecord['lab_unit'] ?? null);
                }
            }

            if (!isset($docketData['associate_lab_examinations']) || !is_array($docketData['associate_lab_examinations'])) {
                $docketData['associate_lab_examinations'] = [];
            }
            $docketData['associate_lab_examinations'][$canonicalName] = $examRecord;

            if (isset($docketData['lab_examination'])) {
                $docketData['lab_examination']['exhibit_is_associate'] = false;
            }
        } else {
            // Primary suspect examination
            $primaryExhModel = $matchedExhibitModel ?? Exhibit::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            })->first();

            $caseLabQuery = LaboratoryExaminationResult::where(function ($q) use ($singleCaseId, $multiCaseId) {
                if ($singleCaseId) {
                    $q->where('single_suspect_case_id', $singleCaseId);
                } else {
                    $q->where('multiple_suspect_case_id', $multiCaseId);
                }
            });

            // Prefer the row already tied to this exhibit, otherwise adopt any orphan unlinked row for this case
            $existingPrimaryLab = $primaryExhModel
                ? ((clone $caseLabQuery)->where('exhibit_id', $primaryExhModel->id)->first()
                    ?? (clone $caseLabQuery)->whereNull('exhibit_id')->first())
                : (clone $caseLabQuery)->first();

            if ($existingPrimaryLab && !empty($existingPrimaryLab->exam_reference)) {
                $examRecord['exam_reference'] = $existingPrimaryLab->exam_reference;
            } else {
                if (empty($examRecord['exam_reference']) || $examRecord['exam_reference'] === 'NLR-FM-T35-F' || app(CaseReferenceService::class)->examReferenceExists($examRecord['exam_reference'])) {
                    $examRecord['exam_reference'] = app(CaseReferenceService::class)->generateExamReference($examRecord['lab_unit'] ?? null);
                }
            }

            $docketData['lab_examination'] = $examRecord;
            $docketData['confirmatory_detected_substances'] = $examRecord['detected_substances'];

            if ($primaryExhModel) {
                $primaryExhUpdates = [];
                if (!empty($examRecord['remark'])) {
                    $primaryExhUpdates['lab_remark'] = $examRecord['remark'];
                }
                if ($examRecord['lab_exhibit_no'] !== null) {
                    $primaryExhUpdates['lab_exhibit_no'] = $examRecord['lab_exhibit_no'];
                }
                if (!empty($primaryExhUpdates)) {
                    $primaryExhModel->update($primaryExhUpdates);
                }
            }

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    if ($existingPrimaryLab) {
                        $matchCriteria = ['id' => $existingPrimaryLab->id];
                    } else {
                        $matchCriteria = [
                            'single_suspect_case_id'   => $singleCaseId,
                            'multiple_suspect_case_id' => $multiCaseId,
                        ];
                        if ($primaryExhModel) {
                            $matchCriteria['exhibit_id'] = $primaryExhModel->id;
                        }
                    }
                    $savedLabResult = LaboratoryExaminationResult::updateOrCreate(
                        $matchCriteria,
                        [
                            'case_type'                 => $isMultiple ? 'MULTIPLE' : 'SINGLE',
                            'single_suspect_case_id'    => $singleCaseId,
                            'multiple_suspect_case_id'  => $multiCaseId,
                            'exhibit_id'                => $primaryExhModel ? $primaryExhModel->id : null,
                            'laboratory_number'         => $examRecord['laboratory_number'],
                            'exam_reference'            => $examRecord['exam_reference'],
                            'purpose_of_examination'    => $examRecord['purpose_of_examination'] ?? ($payload['purpose_of_examination'] ?? ($payload['lab_purpose_of_examination'] ?? null)),
                            'preliminary_exam_procedure' => $examRecord['preliminary_exam_procedure'] ?? ($payload['preliminary_exam_procedure'] ?? ($payload['lab_preliminary_exam_procedure'] ?? null)),
                            'screening_method'          => $examRecord['screening_method'] ?? ($screeningMethod ?? null),
                            'gsa_lab_number'            => $payload['gsa_lab_number'] ?? ($docketData['lab_examination']['gsa_lab_number'] ?? null),
                            'date_received'             => $examRecord['date_received'],
                            'date_started'              => $examRecord['date_started'],
                            'date_completed'            => $examRecord['date_completed'],
                            'presumptive_result'        => $examRecord['presumptive_db_result'] ?? $examRecord['presumptive_result'],
                            'presumptive_presence'      => $examRecord['presumptive_presence'] ?? null,
                            'presumptive_percentage'    => $examRecord['presumptive_percentage'] ?? null,
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
                            'disposition_of_exhibit'    => $examRecord['disposition_of_exhibit'] ?? ($payload['disposition_of_exhibit'] ?? ($payload['confirmatory_disposition_of_exhibit'] ?? null)),
                            'findings'                  => $examRecord['findings'],
                            'analyst'                   => $officer,
                            'has_saved'                 => true,
                        ]
                    );

                    $docketData['lab_examination'] = array_merge($docketData['lab_examination'] ?? [], $examRecord);
                    ReferenceRegistry::reserve($examRecord['exam_reference'], 'EXAM', LaboratoryExaminationResult::class, $savedLabResult->id);
                    break;
                } catch (QueryException $e) {
                    $isUniqueViolation = str_contains(strtolower($e->getMessage()), 'duplicate')
                        || str_contains(strtolower($e->getMessage()), 'unique')
                        || ($e->errorInfo[1] ?? 0) === 1062;

                    if (!$isUniqueViolation || $attempt === 5) {
                        throw $e;
                    }
                    $examRecord['exam_reference'] = app(CaseReferenceService::class)->generateExamReference($examRecord['lab_unit'] ?? null);
                }
            }

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

        // Auto-sync Preliminary Report PDF record to suspect_preliminary_reports
        try {
            app(\App\Http\Controllers\CaseReportController::class)->storeOrSyncPreliminaryReport($case, $savedLabResult ?? null, $docketData);
        } catch (\Throwable $e) {
            // Fail gracefully without interrupting exam save
        }

        return $docketData;
    }

    /**
     * Determine whether an exhibit is strictly a drug or narcotic substance exhibit.
     * Excludes non-drug exhibits (e.g. vehicles, firearms, electronics, cash, documents, ammunition, gold).
     *
     * @param mixed $item
     * @return bool
     */
    public function isDrugExhibit($item): bool
    {
        if (!$item) return false;
        if ($item instanceof ForensicCase) return $item->isDrugCase();

        return app(DrugExhibitClassifier::class)->isDrugExhibit($item);
    }
}
