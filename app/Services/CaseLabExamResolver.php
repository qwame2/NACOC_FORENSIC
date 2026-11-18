<?php

namespace App\Services;

use App\Models\AssociateLabExamination;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;

class CaseLabExamResolver
{
    public function __construct(
        protected DrugExhibitClassifier $drugClassifier,
        protected ExhibitOwnershipResolver $ownershipResolver,
        protected CaseReferenceService $referenceService,
    ) {}

    /**
     * Resolve whether the active suspect has a saved lab examination and whether the case has any saved lab data.
     */
    public function resolveSuspectLabExamSaved(
        mixed $case,
        array $docketData,
        bool $activeSuspectHasDrugExhibit,
        string $activeSuspectNameLower,
        bool $isActiveSuspectPrimary,
        bool $isMultiCase,
        array $suspectLinkedExhibits,
    ): array {
        $suspectLabExamSaved = false;

        if ($activeSuspectHasDrugExhibit) {
            if (!empty($case->id)) {
                $suspectLabExamSaved = AssociateLabExamination::where('case_id', $case->id)
                    ->where('has_saved', true)
                    ->get()
                    ->contains(function ($aRec) use ($activeSuspectNameLower) {
                        if (empty($aRec->has_saved)) {
                            return false;
                        }
                        if (
                            empty($aRec->analyst) &&
                            empty($aRec->exam_reference) &&
                            empty($aRec->substance_identification) &&
                            empty($aRec->date_completed)
                        ) {
                            return false;
                        }
                        $aName = strtolower(trim($aRec->suspect_name ?? ''));
                        return !empty($aName) &&
                            ($aName === $activeSuspectNameLower ||
                                str_contains($aName, $activeSuspectNameLower) ||
                                str_contains($activeSuspectNameLower, $aName));
                    });
            }

            if (!$suspectLabExamSaved && $isActiveSuspectPrimary && !empty($case->id)) {
                $suspectLabExamSaved = LaboratoryExaminationResult::where(function ($q) use ($case, $isMultiCase) {
                    if ($isMultiCase) {
                        $q->where('multiple_suspect_case_id', $case->id);
                    } else {
                        $q->where('single_suspect_case_id', $case->id);
                    }
                })
                    ->where('has_saved', true)
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNotNull('analyst')->where('analyst', '!=', '');
                        })->orWhere(function ($sub) {
                            $sub->whereNotNull('exam_reference')->where('exam_reference', '!=', '');
                        })->orWhere(function ($sub) {
                            $sub->whereNotNull('detected_substances')->where('detected_substances', '!=', '')->where('detected_substances', '!=', '[]');
                        })->orWhere(function ($sub) {
                            $sub->whereNotNull('date_completed')->where('date_completed', '!=', '');
                        });
                    })
                    ->exists();
            }

            if (
                !$suspectLabExamSaved &&
                !empty($docketData['lab_examinations_list']) &&
                is_array($docketData['lab_examinations_list'])
            ) {
                foreach ($docketData['lab_examinations_list'] as $lItem) {
                    $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
                    if (
                        !empty($lItem['has_saved']) &&
                        ($lOwner === $activeSuspectNameLower ||
                            str_contains($lOwner, $activeSuspectNameLower) ||
                            str_contains($activeSuspectNameLower, $lOwner))
                    ) {
                        $suspectLabExamSaved = true;
                        break;
                    }
                }
            }

            if (
                !$suspectLabExamSaved &&
                !empty($docketData['associate_lab_examinations']) &&
                is_array($docketData['associate_lab_examinations'])
            ) {
                foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                    $kLow = strtolower(trim($kAssoc));
                    if (
                        !empty($vAssoc['has_saved']) &&
                        ($kLow === $activeSuspectNameLower ||
                            str_contains($kLow, $activeSuspectNameLower) ||
                            str_contains($activeSuspectNameLower, $kLow))
                    ) {
                        $suspectLabExamSaved = true;
                        break;
                    }
                }
            }

            if (!$suspectLabExamSaved && $isActiveSuspectPrimary && !empty($docketData['lab_examination'])) {
                $hasLabData = !empty($docketData['lab_examination']['has_saved']);
                if ($hasLabData) {
                    $pOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? ''));
                    if (
                        empty($pOwner) ||
                        $pOwner === 'primary suspect' ||
                        $pOwner === $activeSuspectNameLower ||
                        str_contains($pOwner, $activeSuspectNameLower) ||
                        str_contains($activeSuspectNameLower, $pOwner)
                    ) {
                        $suspectLabExamSaved = true;
                    }
                }
            }

            if (!$suspectLabExamSaved) {
                foreach ($suspectLinkedExhibits as $szItem) {
                    if (!empty($szItem['has_saved_lab'])) {
                        $suspectLabExamSaved = true;
                        break;
                    }
                }
            }
        }

        $caseHasAnySavedLab = (bool) (
            $suspectLabExamSaved
            || !empty($docketData['lab_examination']['has_saved'])
            || (!empty($docketData['lab_examinations_list'])
                && is_array($docketData['lab_examinations_list'])
                && collect($docketData['lab_examinations_list'])->contains(fn($l) => !empty($l['has_saved'])))
            || (!empty($docketData['associate_lab_examinations'])
                && is_array($docketData['associate_lab_examinations'])
                && collect($docketData['associate_lab_examinations'])->contains(fn($a) => !empty($a['has_saved'])))
            || (!empty($docketData['seizures'])
                && is_array($docketData['seizures'])
                && collect($docketData['seizures'])->contains(fn($s) => !empty($s['has_saved_lab'])))
        );

        return [
            'suspectLabExamSaved' => $suspectLabExamSaved,
            'caseHasAnySavedLab' => $caseHasAnySavedLab,
        ];
    }

    /**
     * Resolve top-level laboratory examination fields and saved state (lines 1108-1725).
     */
    public function resolveTopLevelLabExam(
        mixed $case,
        array $docketData,
        bool $isMultiCase,
        array $primaryAndCoNamesList,
        array $topDrugExhibits,
        bool $isDrugCase,
        bool $suspectLabExamSaved,
        array $unitProgress,
        ?string $currentLoggedInName,
        string $defaultLabHeadName,
    ): array {
        $labExam = $docketData['lab_examination'] ?? [];
        if (empty($labExam['has_saved'])) {
            if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
                foreach ($docketData['lab_examinations_list'] as $lItem) {
                    if (!empty($lItem['has_saved'])) {
                        $labExam = array_merge($labExam, $lItem);
                        break;
                    }
                }
            }
        }
        if (empty($labExam['has_saved'])) {
            if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                foreach ($docketData['seizures'] as $szItem) {
                    if (!empty($szItem['has_saved_lab'])) {
                        $labExam = array_merge($labExam, [
                            'exhibit_owner_name' =>
                                $szItem['associated_person'] ??
                                ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? null)),
                            'laboratory_number' => $szItem['laboratory_number'] ?? null,
                            'exam_reference' => $szItem['exam_reference'] ?? null,
                            'exhibit_number' => $szItem['exhibit_number'] ?? null,
                            'analyst' => $szItem['analyst'] ?? null,
                            'date_received' => $szItem['date_received'] ?? null,
                            'date_started' => $szItem['date_started'] ?? null,
                            'date_completed' => $szItem['date_completed'] ?? null,
                            'presumptive_result' => $szItem['presumptive_result'] ?? null,
                            'confirmatory_result' => $szItem['confirmatory_result'] ?? null,
                            'sample_weight' => [
                                'description' => $szItem['sample_description'] ?? null,
                                'units_packaging' => $szItem['sample_units'] ?? null,
                                'gross_weight' => $szItem['sample_gross'] ?? null,
                                'sample_taken' => $szItem['sample_taken'] ?? null,
                                'reserved_samples' => $szItem['sample_reserved'] ?? null,
                            ],
                            'substance_identification' => $szItem['substance_identification'] ?? null,
                            'detected_substances' => $szItem['detected_substances'] ?? [],
                            'test_methods' => $szItem['test_methods'] ?? null,
                            'specifications' => $szItem['specifications'] ?? null,
                            'remark' => $szItem['remark'] ?? null,
                            'findings' => $szItem['findings'] ?? null,
                            'purpose_of_examination' => $szItem['purpose_of_examination'] ?? null,
                            'preliminary_exam_procedure' => $szItem['preliminary_exam_procedure'] ?? null,
                            'screening_method' => $szItem['screening_method'] ?? null,
                            'disposition_of_exhibit' => $szItem['disposition_of_exhibit'] ?? null,
                            'has_saved' => true,
                        ]);
                        break;
                    }
                }
            }
        }

        $dbPrimaryLabExam = !empty($case->id)
            ? LaboratoryExaminationResult::where(function ($q) use ($case, $isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $case->id);
                } else {
                    $q->where('single_suspect_case_id', $case->id);
                }
            })
                ->where('has_saved', true)
                ->latest('id')
                ->first()
            : null;

        if (!$dbPrimaryLabExam && !empty($case->id)) {
            $dbPrimaryLabExam = AssociateLabExamination::where('case_id', $case->id)
                ->where('has_saved', true)
                ->get()
                ->first(function ($aRec) use ($primaryAndCoNamesList) {
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
        }

        if ($dbPrimaryLabExam && $dbPrimaryLabExam->has_saved) {
            $labExam = array_merge($labExam, [
                'exhibit_owner_name' => $dbPrimaryLabExam->suspect_name,
                'laboratory_number' => $dbPrimaryLabExam->laboratory_number,
                'exam_reference' => $dbPrimaryLabExam->exam_reference,
                'exhibit_number' => $dbPrimaryLabExam->exhibit_number,
                'analyst' => $dbPrimaryLabExam->analyst,
                'date_received' => is_object($dbPrimaryLabExam->date_received)
                    ? $dbPrimaryLabExam->date_received->format('Y-m-d')
                    : substr((string) $dbPrimaryLabExam->date_received, 0, 10),
                'date_started' => is_object($dbPrimaryLabExam->date_started)
                    ? $dbPrimaryLabExam->date_started->format('Y-m-d')
                    : substr((string) $dbPrimaryLabExam->date_started, 0, 10),
                'date_completed' => is_object($dbPrimaryLabExam->date_completed)
                    ? $dbPrimaryLabExam->date_completed->format('Y-m-d')
                    : substr((string) $dbPrimaryLabExam->date_completed, 0, 10),
                'presumptive_result' => $dbPrimaryLabExam->presumptive_result,
                'presumptive_presence' => $dbPrimaryLabExam->presumptive_presence ?? null,
                'presumptive_percentage' => $dbPrimaryLabExam->presumptive_percentage ?? null,
                'confirmatory_result' => $dbPrimaryLabExam->confirmatory_result,
                'sample_weight' => [
                    'description' => $dbPrimaryLabExam->sample_weight_description,
                    'units_packaging' => $dbPrimaryLabExam->sample_weight_units,
                    'gross_weight' => $dbPrimaryLabExam->sample_weight_gross,
                    'sample_taken' => $dbPrimaryLabExam->sample_weight_taken,
                    'reserved_samples' => $dbPrimaryLabExam->sample_weight_reserved,
                ],
                'substance_identification' => $dbPrimaryLabExam->substance_identification,
                'detected_substances' => $dbPrimaryLabExam->detected_substances,
                'detected_subtances_percentage' => $dbPrimaryLabExam->detected_subtances_percentage ?? ($dbPrimaryLabExam->detected_substances_percentage ?? null),
                'detected_substances_percentage' => $dbPrimaryLabExam->detected_substances_percentage ?? ($dbPrimaryLabExam->detected_subtances_percentage ?? null),
                'test_methods' => $dbPrimaryLabExam->test_methods,
                'specifications' => $dbPrimaryLabExam->specifications,
                'remark' => $dbPrimaryLabExam->remark,
                'findings' => $dbPrimaryLabExam->findings,
                'purpose_of_examination' => $dbPrimaryLabExam->purpose_of_examination ?? null,
                'preliminary_exam_procedure' => $dbPrimaryLabExam->preliminary_exam_procedure ?? null,
                'screening_method' => $dbPrimaryLabExam->screening_method ?? null,
                'disposition_of_exhibit' => $dbPrimaryLabExam->disposition_of_exhibit ?? null,
                'has_saved' => true,
            ]);
        }

        $rawLabExamRef =
            $labExam['exam_reference'] ??
            ($labExam['lab_exam_reference'] ?? ($case->lab_exam_reference ?? ($case->exam_reference ?? null)));
        if (empty($rawLabExamRef) && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szRef) {
                if (!empty($szRef['exam_reference'])) {
                    $rawLabExamRef = $szRef['exam_reference'];
                    break;
                } elseif (!empty($szRef['lab_exam_reference'])) {
                    $rawLabExamRef = $szRef['lab_exam_reference'];
                    break;
                }
            }
        }
        $labExamRef = !empty($rawLabExamRef)
            ? $this->referenceService->normalizeExamReference($rawLabExamRef)
            : $this->referenceService->resolveExamReference($case, $docketData);

        $labCaseRef = $case->case_number;

        $rawLabExhibitNo =
            $labExam['exhibit_number'] ?? ($labExam['lab_exhibit_number'] ?? ($case->exhibit_number ?? null));
        if (empty($rawLabExhibitNo) && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szRef) {
                if (!empty($szRef['exhibit_number'])) {
                    $rawLabExhibitNo = $szRef['exhibit_number'];
                    break;
                } elseif (!empty($szRef['exhibit_no'])) {
                    $rawLabExhibitNo = $szRef['exhibit_no'];
                    break;
                }
            }
        }
        $safeCaseNumId = $case->id ?? 1;
        $labExhibitNo = !empty($rawLabExhibitNo)
            ? $rawLabExhibitNo
            : 'EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . $safeCaseNumId;

        $rawLabNumber = $case->laboratory_number ?? ($labExam['laboratory_number'] ?? ($labExam['lab_number'] ?? null));
        $labNumber = !empty($rawLabNumber)
            ? $rawLabNumber
            : ($case->laboratory_number ?: 'Pending Assignment');
        $labUnit = !empty($labExam['lab_unit'])
            ? $labExam['lab_unit']
            : (!empty($unitProgress['Lab Analysis']['unit'])
                ? $unitProgress['Lab Analysis']['unit']
                : 'Chemistry & Narcotics Unit');
        $labAnalyst = !empty($labExam['analyst'])
            ? $labExam['analyst']
            : (!empty($unitProgress['Lab Analysis']['officer'])
                ? $unitProgress['Lab Analysis']['officer']
                : (!empty($currentLoggedInName)
                    ? $currentLoggedInName
                    : $defaultLabHeadName));
        $labDateReceived =
            !empty($labExam['date_received']) && $labExam['date_received'] !== 'N/A'
                ? $labExam['date_received']
                : (!empty($docketData['lab_examination']['date_received']) &&
                $docketData['lab_examination']['date_received'] !== 'N/A'
                    ? $docketData['lab_examination']['date_received']
                    : (!empty($docketData['seizures'][0]['date_received']) &&
                    $docketData['seizures'][0]['date_received'] !== 'N/A'
                        ? $docketData['seizures'][0]['date_received']
                        : (!empty($docketData['seizures'][0]['seizure_date']) &&
                        $docketData['seizures'][0]['seizure_date'] !== 'N/A'
                            ? $docketData['seizures'][0]['seizure_date']
                            : (!empty($case->incident_date)
                                ? substr((string) $case->incident_date, 0, 10)
                                : (is_object($case->created_at)
                                    ? $case->created_at->format('Y-m-d')
                                    : substr((string) $case->created_at, 0, 10))))));
        $labDateStarted =
            !empty($labExam['date_started']) && $labExam['date_started'] !== 'N/A'
                ? $labExam['date_started']
                : (!empty($docketData['lab_examination']['date_started']) &&
                $docketData['lab_examination']['date_started'] !== 'N/A'
                    ? $docketData['lab_examination']['date_started']
                    : (!empty($docketData['seizures'][0]['date_started']) &&
                    $docketData['seizures'][0]['date_started'] !== 'N/A'
                        ? $docketData['seizures'][0]['date_started']
                        : $labDateReceived));
        $labDateCompleted =
            !empty($labExam['date_completed']) && $labExam['date_completed'] !== 'N/A'
                ? $labExam['date_completed']
                : (!empty($docketData['lab_examination']['date_completed']) &&
                $docketData['lab_examination']['date_completed'] !== 'N/A'
                    ? $docketData['lab_examination']['date_completed']
                    : (!empty($docketData['seizures'][0]['date_completed']) &&
                    $docketData['seizures'][0]['date_completed'] !== 'N/A'
                        ? $docketData['seizures'][0]['date_completed']
                        : $labDateStarted));
        $labPresumptive = !empty($labExam['presumptive_result'])
            ? $labExam['presumptive_result']
            : (!empty($case->presumptive_test_result)
                ? $case->presumptive_test_result
                : 'N/A');
        $rawConfVal = !empty($labExam['confirmatory_result'])
            ? $labExam['confirmatory_result']
            : (!empty($case->confirmatory_test_result)
                ? $case->confirmatory_test_result
                : null);
        $labConfirmatory = !empty($rawConfVal) ? (strtoupper(trim((string) $rawConfVal)) === 'NO' ? 'NO' : 'YES') : null;
        $labSampleWeight = $labExam['sample_weight'] ?? [];

        $firstDrugFound = !empty($topDrugExhibits) ? $topDrugExhibits[0]['name'] : null;
        $defaultDrugName =
            $firstDrugFound ?:
            ($case->seizure_name_drug ?:
            ($isDrugCase
                ? ($case->exhibits_summary ?:
                'Drug Exhibit')
                : 'Drug Exhibit'));

        $firstDrugQty = !empty($topDrugExhibits)
            ? $topDrugExhibits[0]['qty'] ?? ($topDrugExhibits[0]['seizure_quantity'] ?? null)
            : $case->seizure_quantity;
        $firstDrugPkg = !empty($topDrugExhibits)
            ? $topDrugExhibits[0]['pkg'] ?? ($topDrugExhibits[0]['seizure_packaging'] ?? null)
            : $case->seizure_packaging;
        $firstDrugWeight = !empty($topDrugExhibits)
            ? $topDrugExhibits[0]['weight'] ?? ($topDrugExhibits[0]['seizure_weight'] ?? null)
            : $case->seizure_weight;
        $firstDrugUnit = !empty($topDrugExhibits)
            ? $topDrugExhibits[0]['unit'] ?? ($topDrugExhibits[0]['seizure_unit'] ?? null)
            : $case->seizure_unit;

        $defaultUnits = !empty($firstDrugQty) ? trim($firstDrugQty . ' ' . ($firstDrugPkg ?? '')) : '';
        $defaultGrossWeight = !empty($firstDrugWeight)
            ? trim($firstDrugWeight . ' ' . ($firstDrugUnit ?? ''))
            : ($defaultUnits ?:
            $case->exhibits_summary ?? '');

        $rawSampleDesc = !empty($labSampleWeight['description'])
            ? $labSampleWeight['description']
            : (!empty($case->sample_weight_description)
                ? $case->sample_weight_description
                : '');
        $caseCat = strtolower($case->exhibit_category ?? '');
        $caseType = strtolower($case->exhibit_type ?? '');
        $isNonDrugDesc = !str_contains($caseCat, 'drug') && !str_contains($caseType, 'drug');
        $labSampleDesc = !empty($rawSampleDesc) && !$isNonDrugDesc ? $rawSampleDesc : $defaultDrugName;

        $rawSampleUnits = !empty($labSampleWeight['units_packaging'])
            ? $labSampleWeight['units_packaging']
            : (!empty($case->sample_weight_units)
                ? $case->sample_weight_units
                : '');
        $labSampleUnits = !empty($rawSampleUnits) ? $rawSampleUnits : $defaultUnits;

        $rawSampleGross = !empty($labSampleWeight['gross_weight'])
            ? $labSampleWeight['gross_weight']
            : (!empty($case->sample_weight_gross)
                ? $case->sample_weight_gross
                : '');
        $isNonDrugGross = false;
        if (!empty($rawSampleGross)) {
            if (
                !empty($case->seizure_quantity) &&
                strtolower($case->exhibit_category ?? '') !== 'drugs' &&
                (trim($rawSampleGross) === trim((string) $case->seizure_quantity) ||
                    str_starts_with(trim($rawSampleGross), trim((string) $case->seizure_quantity) . ' '))
            ) {
                $isNonDrugGross = true;
            }
        }
        $labSampleGross = !empty($rawSampleGross) && !$isNonDrugGross ? $rawSampleGross : $defaultGrossWeight;
        $labSampleTaken = !empty($labSampleWeight['sample_taken'])
            ? $labSampleWeight['sample_taken']
            : (!empty($labSampleWeight['sample_weight_taken'])
                ? $labSampleWeight['sample_weight_taken']
                : (!empty($labExam['sample_weight_taken'])
                    ? $labExam['sample_weight_taken']
                    : (!empty($labExam['sample_taken'])
                        ? $labExam['sample_taken']
                        : (!empty($case->sample_weight_taken)
                            ? $case->sample_weight_taken
                            : (!empty($docketData['seizures'][0]['sample_taken'])
                                ? $docketData['seizures'][0]['sample_taken']
                                : (!empty($docketData['seizures'][0]['sample_weight_taken'])
                                    ? $docketData['seizures'][0]['sample_weight_taken']
                                    : (!empty($dbPrimaryLabExam->sample_weight_taken)
                                        ? $dbPrimaryLabExam->sample_weight_taken
                                        : '')))))));
        $labSampleReserved = !empty($labSampleWeight['reserved_samples'])
            ? $labSampleWeight['reserved_samples']
            : (!empty($labSampleWeight['sample_weight_reserved'])
                ? $labSampleWeight['sample_weight_reserved']
                : (!empty($labExam['sample_weight_reserved'])
                    ? $labExam['sample_weight_reserved']
                    : (!empty($labExam['reserved_samples'])
                        ? $labExam['reserved_samples']
                        : (!empty($case->sample_weight_reserved)
                            ? $case->sample_weight_reserved
                            : (!empty($docketData['seizures'][0]['sample_reserved'])
                                ? $docketData['seizures'][0]['sample_reserved']
                                : (!empty($docketData['seizures'][0]['sample_weight_reserved'])
                                    ? $docketData['seizures'][0]['sample_weight_reserved']
                                    : (!empty($dbPrimaryLabExam->sample_weight_reserved)
                                        ? $dbPrimaryLabExam->sample_weight_reserved
                                        : '')))))));
        $labSampleUnit = !empty($firstDrugUnit)
            ? $firstDrugUnit
            : (!empty($case->seizure_unit)
                ? $case->seizure_unit
                : 'Kilograms (kg)');

        $rawSubstanceId = !empty($labExam['substance_identification'])
            ? $labExam['substance_identification']
            : (!empty($case->confirmatory_substance_identification)
                ? $case->confirmatory_substance_identification
                : '');
        $isNonDrugSub = !str_contains($caseCat, 'drug') && !str_contains($caseType, 'drug');
        $labSubstanceId = !empty($rawSubstanceId) && !$isNonDrugSub ? $rawSubstanceId : $defaultDrugName;
        $labDetectedSubstances = !empty($labExam['detected_substances'])
            ? $labExam['detected_substances']
            : (!empty($case->detected_substances)
                ? $case->detected_substances
                : (!empty($dbPrimaryLabExam->detected_substances)
                    ? $dbPrimaryLabExam->detected_substances
                    : (!empty($docketData['confirmatory_detected_substances'])
                        ? $docketData['confirmatory_detected_substances']
                        : (!empty($docketData['lab_examination']['detected_substances'])
                            ? $docketData['lab_examination']['detected_substances']
                            : []))));
        if (is_string($labDetectedSubstances)) {
            $labDetectedSubstances = json_decode($labDetectedSubstances, true) ?: [];
        }
        $labTestMethods = !empty($labExam['test_methods'])
            ? $labExam['test_methods']
            : (!empty($case->confirmatory_test_methods)
                ? $case->confirmatory_test_methods
                : '');
        $labSpecifications = !empty($labExam['specifications'])
            ? $labExam['specifications']
            : (!empty($case->confirmatory_specifications)
                ? $case->confirmatory_specifications
                : '');
        $labRemark = !empty($labExam['remark'])
            ? $labExam['remark']
            : (!empty($case->confirmatory_remark)
                ? $case->confirmatory_remark
                : '');
        if (
            !empty($labSampleGross) &&
            preg_match('/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i', $labSampleGross, $uMatch)
        ) {
            $labSampleUnit = $uMatch[1];
        }
        $labFindings = !empty($labExam['findings'])
            ? $labExam['findings']
            : (!empty($unitProgress['Lab Analysis']['notes'])
                ? $unitProgress['Lab Analysis']['notes']
                : (!empty($case->laboratory_analytical_findings)
                    ? $case->laboratory_analytical_findings
                    : ''));
        $hasAnySavedLabData = (bool) (
            !empty($labExam['has_saved'])
            || $suspectLabExamSaved
            || !empty($docketData['lab_examination']['has_saved'])
        );

        if ($hasAnySavedLabData && !empty($labExam['has_saved'])) {
            $savedLabExamRef = !empty($labExam['exam_reference']) ? $labExam['exam_reference'] : $labExamRef;
            $savedLabExhibitNo = !empty($labExam['exhibit_number']) ? $labExam['exhibit_number'] : $labExhibitNo;
            $savedLabCaseRef = $labCaseRef;
            $savedLabNumber = !empty($labExam['laboratory_number']) ? $labExam['laboratory_number'] : null;
            $savedLabUnit = !empty($labExam['lab_unit']) ? $labExam['lab_unit'] : null;
            $savedLabAnalyst = !empty($labExam['analyst']) ? $labExam['analyst'] : 'N/A';
            $savedLabPresumptive = !empty($labExam['presumptive_result']) ? $labExam['presumptive_result'] : 'N/A';
            $savedLabPresumptivePresence = $labExam['presumptive_presence'] ?? null;
            $savedLabPresumptivePercentage = $labExam['presumptive_percentage'] ?? null;
            $caseIncReview = !empty($case->incident_date)
                ? (is_object($case->incident_date) ? $case->incident_date->format('Y-m-d') : substr((string) $case->incident_date, 0, 10))
                : null;
            $caseCreReview = isset($case) && $case->created_at
                ? (is_object($case->created_at) ? $case->created_at->format('Y-m-d') : substr((string) $case->created_at, 0, 10))
                : date('Y-m-d');
            $caseUpReview = isset($case) && $case->updated_at
                ? (is_object($case->updated_at) ? $case->updated_at->format('Y-m-d') : substr((string) $case->updated_at, 0, 10))
                : date('Y-m-d');

            $savedLabDateReceived = !empty($labExam['date_received']) && $labExam['date_received'] !== 'N/A'
                ? $labExam['date_received']
                : ($caseIncReview ?: $caseCreReview);
            $savedLabDateStarted = !empty($labExam['date_started']) && $labExam['date_started'] !== 'N/A'
                ? $labExam['date_started']
                : $savedLabDateReceived;
            $savedLabDateCompleted = !empty($labExam['date_completed']) && $labExam['date_completed'] !== 'N/A'
                ? $labExam['date_completed']
                : ($caseUpReview ?: $savedLabDateStarted);
            $savedLabConfirmatory = !empty($labExam['confirmatory_result'])
                ? (strtoupper(trim((string) $labExam['confirmatory_result'])) === 'NO' ? 'NO' : 'YES')
                : null;
            $savedLabExhibitDesc = !empty($labExam['exhibit_description'])
                ? $labExam['exhibit_description']
                : (!empty($labExam['description'])
                    ? $labExam['description']
                    : null);
            $savedLabSampleDesc = !empty($labExam['sample_weight']['description']) ? $labExam['sample_weight']['description'] : null;
            $savedLabSampleUnits = !empty($labExam['sample_weight']['units_packaging']) ? $labExam['sample_weight']['units_packaging'] : null;
            $savedLabSampleGross = !empty($labExam['sample_weight']['gross_weight']) ? $labExam['sample_weight']['gross_weight'] : null;
            $savedLabSampleTaken = !empty($labExam['sample_weight']['sample_taken']) ? $labExam['sample_weight']['sample_taken'] : null;
            $savedLabSampleReserved = !empty($labExam['sample_weight']['reserved_samples']) ? $labExam['sample_weight']['reserved_samples'] : null;
            $savedLabSampleUnit = !empty($labSampleUnit) && $labSampleUnit !== 'Kilograms (kg)' ? $labSampleUnit : null;
            $savedLabSubstanceId = !empty($labExam['substance_identification']) ? $labExam['substance_identification'] : null;
            $savedLabDetectedSubstances = !empty($labExam['detected_substances']) ? $labExam['detected_substances'] : [];
            $savedLabTestMethods = !empty($labExam['test_methods']) ? $labExam['test_methods'] : null;
            $savedLabSpecifications = !empty($labExam['specifications']) ? $labExam['specifications'] : null;
            $savedLabRemark = !empty($labExam['remark']) ? $labExam['remark'] : null;
            $savedLabFindings = !empty($labExam['findings']) ? $labExam['findings'] : null;
            $savedLabPurposeOfExamination = !empty($labExam['purpose_of_examination']) ? $labExam['purpose_of_examination'] : null;
            $savedLabPreliminaryExamProcedure = !empty($labExam['preliminary_exam_procedure']) ? $labExam['preliminary_exam_procedure'] : null;
            $savedLabScreeningMethod = !empty($labExam['screening_method']) ? $labExam['screening_method'] : null;
            $savedLabDispositionOfExhibit = !empty($labExam['disposition_of_exhibit']) ? $labExam['disposition_of_exhibit'] : null;
        } else {
            $savedLabExamRef = 'N/A';
            $savedLabExhibitNo = 'N/A';
            $savedLabCaseRef = $case->case_number;
            $savedLabNumber = 'N/A';
            $savedLabUnit = 'N/A';
            $savedLabAnalyst = 'N/A';
            $savedLabDateReceived = 'N/A';
            $savedLabDateStarted = 'N/A';
            $savedLabDateCompleted = 'N/A';
            $savedLabPresumptive = 'N/A';
            $savedLabPresumptivePresence = null;
            $savedLabPresumptivePercentage = null;
            $savedLabConfirmatory = 'N/A';
            $savedLabExhibitDesc = 'N/A';
            $savedLabSampleDesc = 'N/A';
            $savedLabSampleUnits = 'N/A';
            $savedLabSampleGross = 'N/A';
            $savedLabSampleTaken = 'N/A';
            $savedLabSampleReserved = 'N/A';
            $savedLabSampleUnit = 'N/A';
            $savedLabSubstanceId = 'N/A';
            $savedLabDetectedSubstances = [];
            $savedLabTestMethods = 'N/A';
            $savedLabSpecifications = 'N/A';
            $savedLabRemark = 'N/A';
            $savedLabFindings = 'N/A';
            $savedLabPurposeOfExamination = 'N/A';
            $savedLabPreliminaryExamProcedure = 'N/A';
            $savedLabScreeningMethod = 'N/A';
            $savedLabDispositionOfExhibit = 'N/A';
        }

        $hasUnexaminedDrugExhibit = false;
        $firstPendingSeizure = null;
        $hasSavedLabResult = !empty($labExam['has_saved']) || $suspectLabExamSaved;
        $hasAnySavedLabExam =
            !empty($docketData['lab_examination']['has_saved']) ||
            (!empty($case->id) && AssociateLabExamination::where('case_id', $case->id)->where('has_saved', true)->exists()) ||
            (!empty($docketData['associate_lab_examinations']) &&
                count(array_filter($docketData['associate_lab_examinations'], fn($v) => !empty($v['has_saved']))) > 0) ||
            (!empty($docketData['lab_examinations_list']) &&
                count(array_filter($docketData['lab_examinations_list'], fn($v) => !empty($v['has_saved']))) > 0) ||
            (!empty($docketData['seizures']) &&
                count(array_filter($docketData['seizures'], fn($s) => !empty($s['has_saved_lab']))) > 0) ||
            (!empty($case->id) && LaboratoryExaminationResult::where(function ($q) use ($case, $isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $case->id);
                } else {
                    $q->where('single_suspect_case_id', $case->id);
                }
            })
                ->where('has_saved', true)
                ->exists());

        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $idxCheck => $szItemCheck) {
                $isDrugCheck = $this->drugClassifier->isDrugExhibit($szItemCheck);
                if ($isDrugCheck) {
                    $sNameCheck = $szItemCheck['seizure_name_drug'] ?? ($szItemCheck['seizure_name'] ?? '');
                    $exNoCheck = $szItemCheck['exhibit_number'] ?? '';
                    $assocPersonNameCheck =
                        $szItemCheck['associated_person'] ??
                        ($szItemCheck['suspect_name'] ?? ($szItemCheck['owner_name'] ?? ''));
                    $assocDbSaved = false;
                    if (!empty($assocPersonNameCheck) && strtolower(trim($assocPersonNameCheck)) !== 'primary suspect') {
                        if (!empty($case->id)) {
                            $assocDbSaved = AssociateLabExamination::where('case_id', $case->id)
                                ->whereRaw('LOWER(trim(suspect_name)) = ?', [strtolower(trim($assocPersonNameCheck))])
                                ->where('has_saved', true)
                                ->exists();
                        }
                        if (!$assocDbSaved && !empty($docketData['associate_lab_examinations'])) {
                            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                                if (
                                    strtolower(trim($kAssoc)) === strtolower(trim($assocPersonNameCheck)) &&
                                    !empty($vAssoc['has_saved'])
                                ) {
                                    $assocDbSaved = true;
                                    break;
                                }
                            }
                        }
                    }

                    $hasSavedForThisCheck =
                        !empty($szItemCheck['has_saved_lab']) ||
                        $assocDbSaved ||
                        (!empty($docketData['lab_examination']['has_saved']) &&
                            ((!empty($savedLabExhibitNo) && $exNoCheck === $savedLabExhibitNo) ||
                                (!empty($savedLabSampleDesc) &&
                                    strtolower(trim($sNameCheck)) === strtolower(trim($savedLabSampleDesc))) ||
                                (!empty($savedLabSubstanceId) &&
                                    strtolower(trim($sNameCheck)) === strtolower(trim($savedLabSubstanceId)))));
                    if ($hasSavedForThisCheck) {
                        $hasAnySavedLabExam = true;
                    } else {
                        $hasUnexaminedDrugExhibit = true;
                        if (empty($firstPendingSeizure)) {
                            $firstPendingSeizure = [
                                'index' => $idxCheck + 1,
                                'name' => $sNameCheck ?: 'Drug Exhibit',
                                'qty' => $szItemCheck['seizure_quantity'] ?? null,
                                'weight' => $szItemCheck['seizure_weight'] ?? null,
                                'unit' => $szItemCheck['seizure_unit'] ?? null,
                                'pkg' => $szItemCheck['seizure_packaging'] ?? null,
                                'ex_no' => !empty($exNoCheck) ? $exNoCheck : 'EXH-0' . ($idxCheck + 1),
                            ];
                        }
                    }
                }
            }
        }

        $savedLabStatus = strtoupper(
            $unitProgress['Lab Analysis']['status'] ??
                ($case->lab_status ?? ($docketData['lab_examination']['status'] ?? 'PENDING')),
        );

        if ($hasAnySavedLabExam || $hasSavedLabResult) {
            $labStatus = 'COMPLETED';
            $hasSavedLabData = true;
        } elseif ($hasUnexaminedDrugExhibit) {
            $labStatus = $hasAnySavedLabExam ? 'IN_PROGRESS' : 'PENDING';
            $hasSavedLabData = $hasAnySavedLabExam;

            $pendName = !empty($firstPendingSeizure) ? $firstPendingSeizure['name'] : $defaultDrugName;
            $pendQty = !empty($firstPendingSeizure) ? $firstPendingSeizure['qty'] ?? null : null;
            $pendPkg = !empty($firstPendingSeizure) ? $firstPendingSeizure['pkg'] ?? null : null;
            $pendWeight = !empty($firstPendingSeizure) ? $firstPendingSeizure['weight'] ?? null : null;
            $pendUnit = !empty($firstPendingSeizure) ? $firstPendingSeizure['unit'] ?? null : null;
            if (!empty($firstPendingSeizure['ex_no']) && !$hasAnySavedLabExam) {
                $labExhibitNo = $firstPendingSeizure['ex_no'];
            }

            if (!$hasAnySavedLabExam) {
                $labSampleDesc = $pendName;
                $labSubstanceId = !empty($savedLabSubstanceId)
                    ? $savedLabSubstanceId
                    : (!empty($case->confirmatory_substance_identification)
                        ? $case->confirmatory_substance_identification
                        : $pendName);
                $labSampleUnits = !empty($pendQty) ? trim($pendQty . ' ' . ($pendPkg ?? '')) : '';
                $labSampleGross = !empty($pendWeight)
                    ? trim($pendWeight . ' ' . ($pendUnit ?? ''))
                    : ($labSampleUnits ?: '');
                if (!empty($pendUnit)) {
                    $labSampleUnit = $pendUnit;
                }

                $labPresumptive = !empty($savedLabPresumptive)
                    ? $savedLabPresumptive
                    : (!empty($case->presumptive_test_result)
                        ? $case->presumptive_test_result
                        : 'N/A');
                $labConfirmatory = !empty($savedLabConfirmatory)
                    ? $savedLabConfirmatory
                    : (!empty($case->confirmatory_test_result)
                        ? $case->confirmatory_test_result
                        : 'YES');
                $labSampleTaken = !empty($savedLabSampleTaken) ? $savedLabSampleTaken : '';
                $labSampleReserved = !empty($savedLabSampleReserved) ? $savedLabSampleReserved : '';
                $labFindings = !empty($savedLabFindings) ? $savedLabFindings : '';
                $labTestMethods = !empty($savedLabTestMethods)
                    ? $savedLabTestMethods
                    : (!empty($case->confirmatory_test_methods)
                        ? $case->confirmatory_test_methods
                        : '');
                $labSpecifications = !empty($savedLabSpecifications)
                    ? $savedLabSpecifications
                    : (!empty($case->confirmatory_specifications)
                        ? $case->confirmatory_specifications
                        : '');
                $labDetectedSubstances = !empty($savedLabDetectedSubstances) ? $savedLabDetectedSubstances : [];
                $labDateReceived = date('Y-m-d');
                $labDateStarted = date('Y-m-d');
                $labDateCompleted = date('Y-m-d');
            }
        } else {
            $labStatus = $hasAnySavedLabExam ? 'COMPLETED' : 'PENDING';
            $hasSavedLabData = $hasAnySavedLabExam;
        }

        return [
            'labExam' => $labExam,
            'dbPrimaryLabExam' => $dbPrimaryLabExam,
            'rawLabExamRef' => $rawLabExamRef,
            'labExamRef' => $labExamRef,
            'labCaseRef' => $labCaseRef,
            'rawLabExhibitNo' => $rawLabExhibitNo,
            'safeCaseNumId' => $safeCaseNumId,
            'labExhibitNo' => $labExhibitNo,
            'rawLabNumber' => $rawLabNumber,
            'labNumber' => $labNumber,
            'labUnit' => $labUnit,
            'labAnalyst' => $labAnalyst,
            'labDateReceived' => $labDateReceived,
            'labDateStarted' => $labDateStarted,
            'labDateCompleted' => $labDateCompleted,
            'labPresumptive' => $labPresumptive,
            'rawConfVal' => $rawConfVal,
            'labConfirmatory' => $labConfirmatory,
            'labSampleWeight' => $labSampleWeight,
            'firstDrugFound' => $firstDrugFound,
            'defaultDrugName' => $defaultDrugName,
            'firstDrugQty' => $firstDrugQty,
            'firstDrugPkg' => $firstDrugPkg,
            'firstDrugWeight' => $firstDrugWeight,
            'firstDrugUnit' => $firstDrugUnit,
            'defaultUnits' => $defaultUnits,
            'defaultGrossWeight' => $defaultGrossWeight,
            'rawSampleDesc' => $rawSampleDesc,
            'caseCat' => $caseCat,
            'caseType' => $caseType,
            'isNonDrugDesc' => $isNonDrugDesc,
            'labSampleDesc' => $labSampleDesc,
            'rawSampleUnits' => $rawSampleUnits,
            'labSampleUnits' => $labSampleUnits,
            'rawSampleGross' => $rawSampleGross,
            'isNonDrugGross' => $isNonDrugGross,
            'labSampleGross' => $labSampleGross,
            'labSampleTaken' => $labSampleTaken,
            'labSampleReserved' => $labSampleReserved,
            'labSampleUnit' => $labSampleUnit,
            'rawSubstanceId' => $rawSubstanceId,
            'isNonDrugSub' => $isNonDrugSub,
            'labSubstanceId' => $labSubstanceId,
            'labDetectedSubstances' => $labDetectedSubstances,
            'labTestMethods' => $labTestMethods,
            'labSpecifications' => $labSpecifications,
            'labRemark' => $labRemark,
            'labFindings' => $labFindings,
            'hasAnySavedLabData' => $hasAnySavedLabData,
            'savedLabExamRef' => $savedLabExamRef,
            'savedLabExhibitNo' => $savedLabExhibitNo,
            'savedLabCaseRef' => $savedLabCaseRef,
            'savedLabNumber' => $savedLabNumber,
            'savedLabUnit' => $savedLabUnit,
            'savedLabAnalyst' => $savedLabAnalyst,
            'savedLabPresumptive' => $savedLabPresumptive,
            'savedLabPresumptivePresence' => $savedLabPresumptivePresence,
            'savedLabPresumptivePercentage' => $savedLabPresumptivePercentage,
            'savedLabDateReceived' => $savedLabDateReceived,
            'savedLabDateStarted' => $savedLabDateStarted,
            'savedLabDateCompleted' => $savedLabDateCompleted,
            'savedLabConfirmatory' => $savedLabConfirmatory,
            'savedLabExhibitDesc' => $savedLabExhibitDesc,
            'savedLabSampleDesc' => $savedLabSampleDesc,
            'savedLabSampleUnits' => $savedLabSampleUnits,
            'savedLabSampleGross' => $savedLabSampleGross,
            'savedLabSampleTaken' => $savedLabSampleTaken,
            'savedLabSampleReserved' => $savedLabSampleReserved,
            'savedLabSampleUnit' => $savedLabSampleUnit,
            'savedLabSubstanceId' => $savedLabSubstanceId,
            'savedLabDetectedSubstances' => $savedLabDetectedSubstances,
            'savedLabTestMethods' => $savedLabTestMethods,
            'savedLabSpecifications' => $savedLabSpecifications,
            'savedLabRemark' => $savedLabRemark,
            'savedLabFindings' => $savedLabFindings,
            'savedLabPurposeOfExamination' => $savedLabPurposeOfExamination,
            'savedLabPreliminaryExamProcedure' => $savedLabPreliminaryExamProcedure,
            'savedLabScreeningMethod' => $savedLabScreeningMethod,
            'savedLabDispositionOfExhibit' => $savedLabDispositionOfExhibit,
            'hasUnexaminedDrugExhibit' => $hasUnexaminedDrugExhibit,
            'firstPendingSeizure' => $firstPendingSeizure,
            'hasSavedLabResult' => $hasSavedLabResult,
            'hasAnySavedLabExam' => $hasAnySavedLabExam,
            'savedLabStatus' => $savedLabStatus,
            'labStatus' => $labStatus,
            'hasSavedLabData' => $hasSavedLabData,
        ];
    }

    /**
     * Resolve per-suspect lab examination in the suspect dossier loop (lines 5181-5220).
     */
    public function resolveSuspectDossierLabExam(
        mixed $case,
        string $sName,
        array $sItem,
        int $sIdx,
        bool $isAssociate,
        bool $isCoSuspect,
        bool $hasCoSuspectsInCase,
        string $primarySuspectsCombinedNames,
        bool $suspectHasDrugEx,
        ?array &$savedJointLabExam = null,
        ?string &$savedJointLabHeadingName = null,
    ): array {
        $suspectLabExam = is_object($case) && method_exists($case, 'resolveLabExamForSuspect')
            ? $case->resolveLabExamForSuspect(
                $sName,
                $sItem['associate_id'] ?? ($sItem['suspect_id'] ?? null),
                $sIdx,
                $isAssociate,
                $isCoSuspect,
            )
            : [];

        $labHeadingName = $sName;
        if (!$isAssociate && !empty($suspectLabExam['is_joint']) && $hasCoSuspectsInCase) {
            $labHeadingName = $primarySuspectsCombinedNames;
        } elseif (!empty($suspectLabExam['owner_name'])) {
            $oLower = strtolower(trim($suspectLabExam['owner_name']));
            if (
                str_contains($oLower, 'all suspect') ||
                str_contains($oLower, 'both') ||
                str_contains($oLower, ',') ||
                str_contains($oLower, '&')
            ) {
                $labHeadingName = $primarySuspectsCombinedNames;
            }
        }

        if (!$suspectHasDrugEx) {
            $suspectLabExam = ['has_saved' => false];
        }

        $shouldRenderSuspectLabCard = false;
        if (!empty($suspectLabExam['has_saved']) && $suspectHasDrugEx) {
            if (!$isAssociate || $isCoSuspect) {
                if (empty($savedJointLabExam)) {
                    $savedJointLabExam = $suspectLabExam;
                    $savedJointLabHeadingName = $labHeadingName;
                }
            } else {
                $shouldRenderSuspectLabCard = true;
            }
        }

        return [
            'suspectLabExam' => $suspectLabExam,
            'labHeadingName' => $labHeadingName,
            'shouldRenderSuspectLabCard' => $shouldRenderSuspectLabCard,
        ];
    }

    /**
     * Parse presumptive test string or structured columns into structured rows (Result, Presence of, Purity/Percentage).
     */
    public function parsePresumptiveRows(?string $pRaw, bool $isAssocFormat = false, mixed $presence = null, mixed $percentage = null): array
    {
        return ForensicCase::parsePresumptiveData($pRaw, $presence, $percentage)['rows'];
    }

    /**
     * Parse confirmatory test result into structured rows (Result, Presence of, Purity/Percentage).
     */
    public function parseConfirmatoryRows(mixed $confResult = null, mixed $detectedSubstances = null, mixed $percentages = null): array
    {
        return ForensicCase::parseConfirmatoryRows($confResult, $detectedSubstances, $percentages);
    }

    /**
     * Resolve Section 5 Laboratory Analysis variables for the active suspect (lines 5836-6889).
     */
    public function resolveSection5LabAnalysisData(
        mixed $case,
        array $docketData,
        array $activeSuspectItem,
        string $activeSuspectName,
        string $activeSuspectNameLower,
        bool $activeSuspectHasDrugExhibit,
        bool $isDrugCase,
        array $primaryAndCoNamesList,
        string $primarySuspectsCombinedNames,
        array $topLevelLabVars,
    ): array {
        $dbPrimaryLabExam = $topLevelLabVars['dbPrimaryLabExam'] ?? null;
        $savedLabExamRef = $topLevelLabVars['savedLabExamRef'];
        $savedLabExhibitNo = $topLevelLabVars['savedLabExhibitNo'];
        $savedLabCaseRef = $topLevelLabVars['savedLabCaseRef'];
        $savedLabNumber = $topLevelLabVars['savedLabNumber'];
        $savedLabUnit = $topLevelLabVars['savedLabUnit'];
        $savedLabAnalyst = $topLevelLabVars['savedLabAnalyst'];
        $savedLabDateReceived = $topLevelLabVars['savedLabDateReceived'];
        $savedLabDateStarted = $topLevelLabVars['savedLabDateStarted'];
        $savedLabDateCompleted = $topLevelLabVars['savedLabDateCompleted'];
        $savedLabPresumptive = $topLevelLabVars['savedLabPresumptive'];
        $savedLabConfirmatory = $topLevelLabVars['savedLabConfirmatory'];
        $savedLabExhibitDesc = $topLevelLabVars['savedLabExhibitDesc'];
        $savedLabSampleDesc = $topLevelLabVars['savedLabSampleDesc'];
        $savedLabSampleUnits = $topLevelLabVars['savedLabSampleUnits'];
        $savedLabSampleGross = $topLevelLabVars['savedLabSampleGross'];
        $savedLabSampleTaken = $topLevelLabVars['savedLabSampleTaken'];
        $savedLabSampleReserved = $topLevelLabVars['savedLabSampleReserved'];
        $savedLabSampleUnit = $topLevelLabVars['savedLabSampleUnit'];
        $savedLabSubstanceId = $topLevelLabVars['savedLabSubstanceId'];
        $savedLabDetectedSubstances = $topLevelLabVars['savedLabDetectedSubstances'];
        $savedLabTestMethods = $topLevelLabVars['savedLabTestMethods'];
        $savedLabSpecifications = $topLevelLabVars['savedLabSpecifications'];
        $savedLabRemark = $topLevelLabVars['savedLabRemark'];
        $savedLabFindings = $topLevelLabVars['savedLabFindings'];
        $savedLabPurposeOfExamination = $topLevelLabVars['savedLabPurposeOfExamination'] ?? null;
        $savedLabPreliminaryExamProcedure = $topLevelLabVars['savedLabPreliminaryExamProcedure'] ?? null;
        $savedLabScreeningMethod = $topLevelLabVars['savedLabScreeningMethod'] ?? null;
        $savedLabDispositionOfExhibit = $topLevelLabVars['savedLabDispositionOfExhibit'] ?? null;
        $labDetectedSubstances = $topLevelLabVars['labDetectedSubstances'] ?? [];

        $activeSuspectLabExam = null;

        $dbActiveLabExam = !empty($case->id)
            ? AssociateLabExamination::where('case_id', $case->id)
                ->where('has_saved', true)
                ->get()
                ->first(function ($aRec) use ($activeSuspectNameLower) {
                    $aName = strtolower(trim($aRec->suspect_name ?? ''));
                    return !empty($aName) &&
                        ($aName === $activeSuspectNameLower ||
                            str_contains($aName, $activeSuspectNameLower) ||
                            str_contains($activeSuspectNameLower, $aName));
                })
            : null;

        if ($dbActiveLabExam) {
            $activeSuspectLabExam = [
                'exhibit_owner_name' => $dbActiveLabExam->suspect_name,
                'laboratory_number' => $dbActiveLabExam->laboratory_number,
                'exam_reference' => $dbActiveLabExam->exam_reference,
                'exhibit_number' => $dbActiveLabExam->exhibit_number,
                'analyst' => $dbActiveLabExam->analyst,
                'date_received' => is_object($dbActiveLabExam->date_received)
                    ? $dbActiveLabExam->date_received->format('Y-m-d')
                    : substr((string) $dbActiveLabExam->date_received, 0, 10),
                'date_started' => is_object($dbActiveLabExam->date_started)
                    ? $dbActiveLabExam->date_started->format('Y-m-d')
                    : substr((string) $dbActiveLabExam->date_started, 0, 10),
                'date_completed' => is_object($dbActiveLabExam->date_completed)
                    ? $dbActiveLabExam->date_completed->format('Y-m-d')
                    : substr((string) $dbActiveLabExam->date_completed, 0, 10),
                'presumptive_result' => $dbActiveLabExam->presumptive_result,
                'presumptive_presence' => $dbActiveLabExam->presumptive_presence ?? null,
                'presumptive_percentage' => $dbActiveLabExam->presumptive_percentage ?? null,
                'confirmatory_result' => $dbActiveLabExam->confirmatory_result,
                'sample_weight' => [
                    'description' => $dbActiveLabExam->sample_weight_description,
                    'units_packaging' => $dbActiveLabExam->sample_weight_units,
                    'gross_weight' => $dbActiveLabExam->sample_weight_gross,
                    'sample_taken' => $dbActiveLabExam->sample_weight_taken,
                    'reserved_samples' => $dbActiveLabExam->sample_weight_reserved,
                ],
                'substance_identification' => $dbActiveLabExam->substance_identification,
                'detected_substances' => $dbActiveLabExam->detected_substances,
                'detected_subtances_percentage' => $dbActiveLabExam->detected_subtances_percentage ?? ($dbActiveLabExam->detected_substances_percentage ?? null),
                'detected_substances_percentage' => $dbActiveLabExam->detected_substances_percentage ?? ($dbActiveLabExam->detected_subtances_percentage ?? null),
                'test_methods' => $dbActiveLabExam->test_methods,
                'specifications' => $dbActiveLabExam->specifications,
                'remark' => $dbActiveLabExam->remark,
                'findings' => $dbActiveLabExam->findings,
                'purpose_of_examination' => $dbActiveLabExam->purpose_of_examination ?? null,
                'preliminary_exam_procedure' => $dbActiveLabExam->preliminary_exam_procedure ?? null,
                'screening_method' => $dbActiveLabExam->screening_method ?? null,
                'disposition_of_exhibit' => $dbActiveLabExam->disposition_of_exhibit ?? null,
                'has_saved' => true,
            ];
        }

        if (
            !$activeSuspectLabExam &&
            !empty($docketData['lab_examinations_list']) &&
            is_array($docketData['lab_examinations_list'])
        ) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ''));
                if (
                    !empty($lItem['has_saved']) &&
                    ($lOwner === $activeSuspectNameLower ||
                        str_contains($lOwner, $activeSuspectNameLower) ||
                        str_contains($activeSuspectNameLower, $lOwner))
                ) {
                    $activeSuspectLabExam = $lItem;
                    break;
                }
            }
        }

        if (
            !$activeSuspectLabExam &&
            !empty($docketData['seizures']) &&
            is_array($docketData['seizures'])
        ) {
            foreach ($docketData['seizures'] as $szItem) {
                $szOwner = strtolower(
                    trim(
                        $szItem['associated_person'] ??
                            ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? '')),
                    ),
                );
                if (
                    !empty($szItem['has_saved_lab']) &&
                    ($szOwner === $activeSuspectNameLower ||
                        str_contains($szOwner, $activeSuspectNameLower) ||
                        str_contains($activeSuspectNameLower, $szOwner))
                ) {
                    $activeSuspectLabExam = [
                        'exhibit_owner_name' => $activeSuspectName,
                        'laboratory_number' => $szItem['laboratory_number'] ?? null,
                        'exam_reference' => $szItem['exam_reference'] ?? null,
                        'exhibit_number' => $szItem['exhibit_number'] ?? null,
                        'analyst' => $szItem['analyst'] ?? null,
                        'date_received' => $szItem['date_received'] ?? null,
                        'date_started' => $szItem['date_started'] ?? null,
                        'date_completed' => $szItem['date_completed'] ?? null,
                        'presumptive_result' => $szItem['presumptive_result'] ?? null,
                        'confirmatory_result' => $szItem['confirmatory_result'] ?? null,
                        'sample_weight' => [
                            'description' => $szItem['sample_description'] ?? null,
                            'units_packaging' => $szItem['sample_units'] ?? null,
                            'gross_weight' => $szItem['sample_gross'] ?? null,
                            'sample_taken' => $szItem['sample_taken'] ?? null,
                            'reserved_samples' => $szItem['sample_reserved'] ?? null,
                        ],
                        'substance_identification' => $szItem['substance_identification'] ?? null,
                        'detected_substances' => $szItem['detected_substances'] ?? [],
                        'test_methods' => $szItem['test_methods'] ?? null,
                        'specifications' => $szItem['specifications'] ?? null,
                        'remark' => $szItem['remark'] ?? null,
                        'findings' => $szItem['findings'] ?? null,
                        'has_saved' => true,
                    ];
                    break;
                }
            }
        }

        if (!$activeSuspectLabExam && !empty($activeSuspectItem['is_primary'])) {
            if (!empty($dbPrimaryLabExam) && $dbPrimaryLabExam->has_saved) {
                $activeSuspectLabExam = [
                    'exhibit_owner_name' => $dbPrimaryLabExam->suspect_name ?: $activeSuspectName,
                    'laboratory_number' =>
                        $dbPrimaryLabExam->laboratory_number ?: $case->laboratory_number ?? null,
                    'exam_reference' => !empty($dbPrimaryLabExam->exam_reference)
                        ? $this->referenceService->normalizeExamReference($dbPrimaryLabExam->exam_reference)
                        : $this->referenceService->resolveExamReference($case, $docketData),
                    'exhibit_number' =>
                        $dbPrimaryLabExam->exhibit_number ?:
                        'EXH-' . date('Y', strtotime($case->created_at ?? 'now')) . '-' . sprintf('%03d', $case->id),
                    'analyst' =>
                        $dbPrimaryLabExam->analyst ?: ($case->submitting_officer ?: 'Lab Analyst'),
                    'date_received' => is_object($dbPrimaryLabExam->date_received)
                        ? $dbPrimaryLabExam->date_received->format('Y-m-d')
                        : (substr((string) $dbPrimaryLabExam->date_received, 0, 10) ?: date('Y-m-d')),
                    'date_started' => is_object($dbPrimaryLabExam->date_started)
                        ? $dbPrimaryLabExam->date_started->format('Y-m-d')
                        : (substr((string) $dbPrimaryLabExam->date_started, 0, 10) ?: date('Y-m-d')),
                    'date_completed' => is_object($dbPrimaryLabExam->date_completed)
                        ? $dbPrimaryLabExam->date_completed->format('Y-m-d')
                        : (substr((string) $dbPrimaryLabExam->date_completed, 0, 10) ?: date('Y-m-d')),
                    'presumptive_result' =>
                        $dbPrimaryLabExam->presumptive_result ?: $case->presumptive_test_result ?? null,
                    'presumptive_presence' => $dbPrimaryLabExam->presumptive_presence ?? null,
                    'presumptive_percentage' => $dbPrimaryLabExam->presumptive_percentage ?? null,
                    'confirmatory_result' =>
                        $dbPrimaryLabExam->confirmatory_result ?: ($case->confirmatory_test_result ?: 'YES'),
                    'sample_weight' => [
                        'description' =>
                            $dbPrimaryLabExam->sample_weight_description ?: $case->sample_weight_description ?? null,
                        'units_packaging' =>
                            $dbPrimaryLabExam->sample_weight_units ?: $case->sample_weight_units ?? null,
                        'gross_weight' =>
                            $dbPrimaryLabExam->sample_weight_gross ?: $case->sample_weight_gross ?? null,
                        'sample_taken' =>
                            $dbPrimaryLabExam->sample_weight_taken ?: $case->sample_weight_taken ?? null,
                        'reserved_samples' =>
                            $dbPrimaryLabExam->sample_weight_reserved ?: $case->sample_weight_reserved ?? null,
                    ],
                    'substance_identification' =>
                        $dbPrimaryLabExam->substance_identification ?: $case->confirmatory_substance_identification ?? null,
                    'detected_substances' =>
                        $dbPrimaryLabExam->detected_substances ?: $case->detected_substances ?? [],
                    'test_methods' =>
                        $dbPrimaryLabExam->test_methods ?: $case->confirmatory_test_methods ?? null,
                    'specifications' =>
                        $dbPrimaryLabExam->specifications ?: $case->confirmatory_specifications ?? null,
                    'remark' => $dbPrimaryLabExam->remark ?: $case->confirmatory_remark ?? null,
                    'findings' =>
                        $dbPrimaryLabExam->findings ?: $case->laboratory_analytical_findings ?? null,
                    'has_saved' => true,
                ];
            } elseif (!empty($docketData['lab_examination']['has_saved'])) {
                $pOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? ''));
                if (
                    empty($pOwner) ||
                    $pOwner === 'primary suspect' ||
                    $pOwner === $activeSuspectNameLower ||
                    str_contains($pOwner, $activeSuspectNameLower) ||
                    str_contains($activeSuspectNameLower, $pOwner)
                ) {
                    $activeSuspectLabExam = $docketData['lab_examination'];
                }
            }
        }

        $activeSuspectHasSavedResult =
            !empty($activeSuspectLabExam) &&
            !empty($activeSuspectLabExam['has_saved']);

        $activeSuspectExhibits = is_object($case) && method_exists($case, 'getExhibitsForSuspect')
            ? $case->getExhibitsForSuspect(
                $activeSuspectName,
                $activeSuspectItem['id'] ?? null,
                $activeSuspectItem['associate_id'] ?? null,
                $activeSuspectItem['role'] ?? null,
            )
            : [];
        $activeSuspectDrugExhibits = array_values(
            array_filter($activeSuspectExhibits, fn($ex) => $this->drugClassifier->isDrugExhibit($ex)),
        );

        if (empty($activeSuspectDrugExhibits) || !$activeSuspectHasDrugExhibit) {
            $activeSuspectHasSavedResult = false;
            $activeSuspectLabExam = null;
        }

        if ($activeSuspectHasSavedResult && !empty($activeSuspectLabExam)) {
            $labOwnerName = $activeSuspectLabExam['exhibit_owner_name'] ?? $activeSuspectName;
            $savedLabExamRef = !empty($activeSuspectLabExam['exam_reference']) ? $activeSuspectLabExam['exam_reference'] : ($savedLabExamRef ?? 'N/A');
            $savedLabExhibitNo = !empty($activeSuspectLabExam['exhibit_number']) ? $activeSuspectLabExam['exhibit_number'] : ($savedLabExhibitNo ?? 'N/A');
            $savedLabNumber = !empty($activeSuspectLabExam['laboratory_number']) ? $activeSuspectLabExam['laboratory_number'] : ($savedLabNumber ?? 'N/A');
            $savedLabAnalyst = !empty($activeSuspectLabExam['analyst']) ? $activeSuspectLabExam['analyst'] : ($savedLabAnalyst ?? 'N/A');
            $savedLabDateReceived = !empty($activeSuspectLabExam['date_received']) ? $activeSuspectLabExam['date_received'] : ($savedLabDateReceived ?? 'N/A');
            $savedLabDateStarted = !empty($activeSuspectLabExam['date_started']) ? $activeSuspectLabExam['date_started'] : ($savedLabDateStarted ?? 'N/A');
            $savedLabDateCompleted = !empty($activeSuspectLabExam['date_completed']) ? $activeSuspectLabExam['date_completed'] : ($savedLabDateCompleted ?? 'N/A');
            $savedLabPresumptive = !empty($activeSuspectLabExam['presumptive_result']) ? $activeSuspectLabExam['presumptive_result'] : ($savedLabPresumptive ?? 'N/A');
            $savedLabPresumptivePresence = $activeSuspectLabExam['presumptive_presence'] ?? ($topLevelLabVars['savedLabPresumptivePresence'] ?? null);
            $savedLabPresumptivePercentage = $activeSuspectLabExam['presumptive_percentage'] ?? ($topLevelLabVars['savedLabPresumptivePercentage'] ?? null);
            if (!empty($activeSuspectLabExam['confirmatory_result'])) {
                $cRes = strtoupper(trim((string) $activeSuspectLabExam['confirmatory_result']));
                $savedLabConfirmatory = $cRes === 'NO' ? 'NO' : ($cRes === 'YES' ? 'YES' : null);
            } else {
                $savedLabConfirmatory = $savedLabConfirmatory ?? 'N/A';
            }

            $swActive = $activeSuspectLabExam['sample_weight'] ?? [];
            if (!empty($activeSuspectLabExam['exhibit_description'])) {
                $savedLabExhibitDesc = $activeSuspectLabExam['exhibit_description'];
            } elseif (!empty($activeSuspectLabExam['description'])) {
                $savedLabExhibitDesc = $activeSuspectLabExam['description'];
            }
            if (isset($swActive['description']) && $swActive['description'] !== null && $swActive['description'] !== '') {
                $savedLabSampleDesc = $swActive['description'];
            }
            if (isset($swActive['units_packaging']) && $swActive['units_packaging'] !== null && $swActive['units_packaging'] !== '') {
                $savedLabSampleUnits = $swActive['units_packaging'];
            }
            if (isset($swActive['gross_weight']) && $swActive['gross_weight'] !== null && $swActive['gross_weight'] !== '') {
                $savedLabSampleGross = $swActive['gross_weight'];
            }

            $activeTaken =
                $swActive['sample_taken'] ??
                ($swActive['sample_weight_taken'] ??
                    ($activeSuspectLabExam['sample_taken'] ??
                        ($activeSuspectLabExam['sample_weight_taken'] ?? null)));
            if (isset($activeTaken) && $activeTaken !== null && $activeTaken !== '') {
                $savedLabSampleTaken = $activeTaken;
            }

            $activeReserved =
                $swActive['reserved_samples'] ??
                ($swActive['sample_weight_reserved'] ??
                    ($activeSuspectLabExam['sample_reserved'] ??
                        ($activeSuspectLabExam['sample_weight_reserved'] ?? null)));
            if (isset($activeReserved) && $activeReserved !== null && $activeReserved !== '') {
                $savedLabSampleReserved = $activeReserved;
            }

            if (!empty($activeSuspectLabExam['substance_identification'])) {
                $savedLabSubstanceId = $activeSuspectLabExam['substance_identification'];
            }
            if (!empty($activeSuspectLabExam['detected_substances'])) {
                $savedLabDetectedSubstances = $activeSuspectLabExam['detected_substances'];
            }
            if (!empty($activeSuspectLabExam['test_methods'])) {
                $savedLabTestMethods = $activeSuspectLabExam['test_methods'];
            }
            if (!empty($activeSuspectLabExam['specifications'])) {
                $savedLabSpecifications = $activeSuspectLabExam['specifications'];
            }
            if (!empty($activeSuspectLabExam['remark'])) {
                $savedLabRemark = $activeSuspectLabExam['remark'];
            }
            if (!empty($activeSuspectLabExam['findings'])) {
                $savedLabFindings = $activeSuspectLabExam['findings'];
            }
            if (!empty($activeSuspectLabExam['purpose_of_examination'])) {
                $savedLabPurposeOfExamination = $activeSuspectLabExam['purpose_of_examination'];
            }
            if (!empty($activeSuspectLabExam['preliminary_exam_procedure'])) {
                $savedLabPreliminaryExamProcedure = $activeSuspectLabExam['preliminary_exam_procedure'];
            }
            if (!empty($activeSuspectLabExam['screening_method'])) {
                $savedLabScreeningMethod = $activeSuspectLabExam['screening_method'];
            }
            if (!empty($activeSuspectLabExam['disposition_of_exhibit'])) {
                $savedLabDispositionOfExhibit = $activeSuspectLabExam['disposition_of_exhibit'];
            }
        } else {
            $labOwnerName = $activeSuspectName;
            $savedLabExamRef = 'N/A';
            $savedLabExhibitNo = 'N/A';
            $savedLabNumber = 'N/A';
            $savedLabAnalyst = 'N/A';
            $savedLabDateReceived = 'N/A';
            $savedLabDateStarted = 'N/A';
            $savedLabDateCompleted = 'N/A';
            $savedLabPresumptive = 'N/A';
            $savedLabPresumptivePresence = null;
            $savedLabPresumptivePercentage = null;
            $savedLabConfirmatory = 'N/A';
            $savedLabExhibitDesc = 'N/A';
            $savedLabSampleDesc = 'N/A';
            $savedLabSampleUnits = 'N/A';
            $savedLabSampleGross = 'N/A';
            $savedLabSampleTaken = 'N/A';
            $savedLabSampleReserved = 'N/A';
            $savedLabSampleUnit = 'N/A';
            $savedLabSubstanceId = 'N/A';
            $savedLabDetectedSubstances = [];
            $savedLabTestMethods = 'N/A';
            $savedLabSpecifications = 'N/A';
            $savedLabRemark = 'N/A';
            $savedLabFindings = 'N/A';
            $savedLabPurposeOfExamination = 'N/A';
            $savedLabPreliminaryExamProcedure = 'N/A';
            $savedLabScreeningMethod = 'N/A';
            $savedLabDispositionOfExhibit = 'N/A';
        }

        $dbExhibitsList = Exhibit::where(function ($q) use ($case) {
            $uId = isset($case->underlyingRecord) ? $case->underlyingRecord->id : $case->id;
            $q->where('single_suspect_case_id', $case->id)
                ->orWhere('multiple_suspect_case_id', $case->id)
                ->orWhere('single_suspect_case_id', $uId)
                ->orWhere('multiple_suspect_case_id', $uId);
        })->get();

        $allDrugExhibitsList = [];
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $idx => $sz) {
                $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                $szIsAssoc =
                    !empty($sz['is_associate']) ||
                    (!empty($szOwner) && strtolower(trim($szOwner)) !== 'primary suspect') ||
                    (!empty($sz['association_role']) && str_contains(strtolower($sz['association_role']), 'associate'));
                $exOwner = !empty($szOwner)
                    ? $szOwner
                    : ($szIsAssoc ? 'Associate Suspect' : $case->suspect_name ?? 'Primary Suspect');

                if ($this->drugClassifier->isDrugExhibit($sz)) {
                    $matchedDbEx = $dbExhibitsList->first(function ($dbEx) use ($idx, $sName, $sz) {
                        if (!empty($sz['id']) && $dbEx->id == $sz['id']) {
                            return true;
                        }
                        if (!empty($sz['exhibit_number']) && $dbEx->exhibit_number === $sz['exhibit_number']) {
                            return true;
                        }
                        return ($dbEx->exhibit_number == ($idx + 1)) || ($dbEx->commodity_name === $sName);
                    }) ?? ($dbExhibitsList[$idx] ?? null);
                    $dbExId = $matchedDbEx ? $matchedDbEx->id : ($sz['id'] ?? null);
                    $dbLabRemark = ($matchedDbEx && !empty(trim((string) $matchedDbEx->lab_remark))) ? trim((string) $matchedDbEx->lab_remark) : null;
                    $resolvedSystemExNo = ($matchedDbEx && !empty($matchedDbEx->exhibit_number))
                        ? $matchedDbEx->exhibit_number
                        : (!empty($sz['exhibit_number']) ? $sz['exhibit_number'] : sprintf('EXH-%05d', $idx + 1));
                    $resolvedLabExNo = ($matchedDbEx && $matchedDbEx->lab_exhibit_no !== null && $matchedDbEx->lab_exhibit_no !== '')
                        ? $matchedDbEx->lab_exhibit_no
                        : ($sz['lab_exhibit_no'] ?? '');

                    $allDrugExhibitsList[] = [
                        'id' => $dbExId,
                        'index' => $idx + 1,
                        'exhibit_number' => $resolvedSystemExNo,
                        'lab_exhibit_no' => $resolvedLabExNo,
                        'name' => $sName ?: 'Drug Exhibit',
                        'cat' => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                        'qty' => $sz['seizure_quantity'] ?? null,
                        'weight' => $sz['seizure_weight'] ?? null,
                        'unit' => $sz['seizure_unit'] ?? null,
                        'contents' => $sz['contents'] ?? ($matchedDbEx ? $matchedDbEx->contents : null),
                        'owner' => $exOwner,
                        'is_associate' => $szIsAssoc,
                        'has_saved_lab' => !empty($sz['has_saved_lab']),
                        'confirmatory_result' =>
                            $sz['confirmatory_result'] ?? ($case->confirmatory_test_result ?? null),
                        'substance_identification' =>
                            $sz['substance_identification'] ?? ($case->confirmatory_substance_identification ?? $sName),
                        'test_methods' =>
                            $sz['test_methods'] ?? ($case->confirmatory_test_methods ?? ''),
                        'specifications' =>
                            $sz['specifications'] ?? ($case->confirmatory_specifications ?? ''),
                        'remark' =>
                            $dbLabRemark ?:
                            ($sz['lab_remark'] ?? ($sz['confirmatory_remark'] ?? ($sz['remark'] ?? ($case->confirmatory_remark ?? '')))),
                        'sample_taken' =>
                            $sz['sample_taken'] ?? ($case->sample_weight_taken ?? ''),
                        'sample_reserved' =>
                            $sz['sample_reserved'] ?? ($case->sample_weight_reserved ?? ''),
                        'findings' =>
                            $sz['findings'] ?? ($case->laboratory_analytical_findings ?? ''),
                        'purpose_of_examination' =>
                            $sz['purpose_of_examination'] ?? null,
                        'preliminary_exam_procedure' =>
                            $sz['preliminary_exam_procedure'] ?? null,
                        'screening_method' =>
                            $sz['screening_method'] ?? null,
                        'disposition_of_exhibit' =>
                            $sz['disposition_of_exhibit'] ?? null,
                    ];
                }
            }
        }
        if (empty($allDrugExhibitsList) && $isDrugCase) {
            $firstDbEx = $dbExhibitsList->first();
            $firstDbLabRemark = ($firstDbEx && !empty(trim((string) $firstDbEx->lab_remark))) ? trim((string) $firstDbEx->lab_remark) : null;

            $allDrugExhibitsList[] = [
                'id' => $firstDbEx ? $firstDbEx->id : 1,
                'index' => 1,
                'exhibit_number' => ($firstDbEx && !empty($firstDbEx->exhibit_number)) ? $firstDbEx->exhibit_number : 'EXH-00001',
                'lab_exhibit_no' => ($firstDbEx && $firstDbEx->lab_exhibit_no !== null) ? $firstDbEx->lab_exhibit_no : '',
                'name' =>
                    $case->seizure_name_drug ?: ($case->seizure_name ?: ($case->exhibits_summary ?: 'Drug Exhibit')),
                'cat' => $case->exhibit_category ?: 'Drugs',
                'qty' => $case->seizure_quantity,
                'weight' => $case->seizure_weight,
                'unit' => $case->seizure_unit,
                'contents' => $firstDbEx ? $firstDbEx->contents : ($case->contents ?? null),
                'owner' => $case->suspect_name ?? 'Primary Suspect',
                'is_associate' => false,
                'has_saved_lab' => $activeSuspectHasSavedResult,
                'confirmatory_result' =>
                    $savedLabConfirmatory ?: ($case->confirmatory_test_result ?: null),
                'substance_identification' =>
                    $savedLabSubstanceId ?: ($case->confirmatory_substance_identification ?: null),
                'test_methods' =>
                    $savedLabTestMethods ?: ($case->confirmatory_test_methods ?: null),
                'specifications' =>
                    $savedLabSpecifications ?: ($case->confirmatory_specifications ?: null),
                'remark' => $firstDbLabRemark ?: ($savedLabRemark ?: ($case->confirmatory_remark ?: null)),
                'sample_taken' => $savedLabSampleTaken ?: ($case->sample_weight_taken ?: null),
                'sample_reserved' =>
                    $savedLabSampleReserved ?: ($case->sample_weight_reserved ?: null),
                'findings' => $savedLabFindings ?: ($case->laboratory_analytical_findings ?: null),
                'purpose_of_examination' => ($savedLabPurposeOfExamination !== 'N/A' ? $savedLabPurposeOfExamination : null),
                'preliminary_exam_procedure' => ($savedLabPreliminaryExamProcedure !== 'N/A' ? $savedLabPreliminaryExamProcedure : null),
                'screening_method' => ($savedLabScreeningMethod !== 'N/A' ? $savedLabScreeningMethod : null),
                'disposition_of_exhibit' => ($savedLabDispositionOfExhibit !== 'N/A' ? $savedLabDispositionOfExhibit : null),
            ];
        }

        $suspectDrugExhibitsList = [];
        if (!empty($activeSuspectDrugExhibits)) {
            foreach ($activeSuspectDrugExhibits as $sIdx => $sz) {
                if (!$this->drugClassifier->isDrugExhibit($sz)) {
                    continue;
                }

                $sName =
                    $sz['seizure_name_drug'] ??
                    ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? '')));
                $szOwner =
                    $sz['associated_person'] ??
                    ($sz['suspect_name'] ??
                        ($sz['owner_name'] ??
                            ($sz['veh_owner'] ??
                                ($sz['wpn_owner'] ?? ($sz['dev_owner'] ?? $activeSuspectName)))));
                $szIsAssoc = !empty($activeSuspectItem['is_associate']);
                $exOwner = !empty($szOwner) ? $szOwner : $activeSuspectName;

                $matchedDbExSuspect = $dbExhibitsList->first(function ($dbEx) use ($sIdx, $sName, $sz) {
                    if (!empty($sz['id']) && $dbEx->id == $sz['id']) {
                        return true;
                    }
                    if (!empty($sz['exhibit_number']) && $dbEx->exhibit_number === $sz['exhibit_number']) {
                        return true;
                    }
                    return ($dbEx->exhibit_number == ($sIdx + 1)) || ($dbEx->commodity_name === $sName);
                }) ?? ($dbExhibitsList[$sIdx] ?? null);
                $dbExIdSuspect = $matchedDbExSuspect ? $matchedDbExSuspect->id : ($sz['id'] ?? ($sz['exhibit_id'] ?? null));
                $dbLabRemarkSuspect = ($matchedDbExSuspect && !empty(trim((string) $matchedDbExSuspect->lab_remark))) ? trim((string) $matchedDbExSuspect->lab_remark) : null;
                $resolvedSystemExNoSuspect = ($matchedDbExSuspect && !empty($matchedDbExSuspect->exhibit_number))
                    ? $matchedDbExSuspect->exhibit_number
                    : (!empty($sz['exhibit_number']) ? $sz['exhibit_number'] : sprintf('EXH-%05d', count($suspectDrugExhibitsList) + 1));
                $resolvedLabExNoSuspect = ($matchedDbExSuspect && $matchedDbExSuspect->lab_exhibit_no !== null && $matchedDbExSuspect->lab_exhibit_no !== '')
                    ? $matchedDbExSuspect->lab_exhibit_no
                    : ($sz['lab_exhibit_no'] ?? '');

                $suspectDrugExhibitsList[] = [
                    'id' => $dbExIdSuspect,
                    'index' => count($suspectDrugExhibitsList) + 1,
                    'exhibit_number' => $resolvedSystemExNoSuspect,
                    'lab_exhibit_no' => $resolvedLabExNoSuspect,
                    'name' => $sName ?: 'Drug Exhibit',
                    'cat' => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                    'qty' => $sz['seizure_quantity'] ?? ($sz['quantity'] ?? null),
                    'weight' => $sz['seizure_weight'] ?? ($sz['weight'] ?? null),
                    'unit' => $sz['seizure_unit'] ?? ($sz['unit'] ?? null),
                    'pkg' => $sz['seizure_packaging'] ?? ($sz['packaging'] ?? null),
                    'contents' => $sz['contents'] ?? ($matchedDbExSuspect ? $matchedDbExSuspect->contents : null),
                    'description' => $sz['seizure_description'] ?? ($sz['description'] ?? null),
                    'owner' => $exOwner,
                    'is_associate' => $szIsAssoc,
                    'has_saved_lab' => !empty($sz['has_saved_lab']),
                    'confirmatory_result' =>
                        $sz['confirmatory_result'] ?? ($case->confirmatory_test_result ?? null),
                    'substance_identification' =>
                        $sz['substance_identification'] ?? ($case->confirmatory_substance_identification ?? $sName),
                    'test_methods' =>
                        $sz['test_methods'] ?? ($case->confirmatory_test_methods ?? ''),
                    'specifications' =>
                        $sz['specifications'] ?? ($case->confirmatory_specifications ?? ''),
                    'remark' =>
                        $dbLabRemarkSuspect ?:
                        ($sz['lab_remark'] ?? ($sz['confirmatory_remark'] ?? ($sz['remark'] ?? ($case->confirmatory_remark ?? '')))),
                    'sample_taken' =>
                        $sz['sample_taken'] ?? ($case->sample_weight_taken ?? ''),
                    'sample_reserved' =>
                        $sz['sample_reserved'] ?? ($case->sample_weight_reserved ?? ''),
                    'findings' =>
                        $sz['findings'] ?? ($case->laboratory_analytical_findings ?? ''),
                    'purpose_of_examination' =>
                        $sz['purpose_of_examination'] ?? null,
                    'preliminary_exam_procedure' =>
                        $sz['preliminary_exam_procedure'] ?? null,
                    'screening_method' =>
                        $sz['screening_method'] ?? null,
                    'disposition_of_exhibit' =>
                        $sz['disposition_of_exhibit'] ?? null,
                ];
            }
        }

        $pendingDrugExhibits = array_values(
            array_filter($suspectDrugExhibitsList, fn($e) => empty($e['has_saved_lab'])),
        );
        $drugExhibits = !empty($pendingDrugExhibits)
            ? $pendingDrugExhibits
            : $suspectDrugExhibitsList;
        $initialSelectedEx = !empty($drugExhibits) ? $drugExhibits[0] : null;
        $initialHasSaved = $activeSuspectHasSavedResult;
        $initialLabDesc = !empty($initialSelectedEx)
            ? $initialSelectedEx['name']
            : 'No drug exhibit linked';
        $initialPurposeOfExamination = !empty($initialSelectedEx['purpose_of_examination'])
            ? $initialSelectedEx['purpose_of_examination']
            : ($savedLabPurposeOfExamination !== 'N/A' ? ($savedLabPurposeOfExamination ?? '') : '');
        $initialPreliminaryExamProcedure = !empty($initialSelectedEx['preliminary_exam_procedure'])
            ? $initialSelectedEx['preliminary_exam_procedure']
            : ($savedLabPreliminaryExamProcedure !== 'N/A' ? ($savedLabPreliminaryExamProcedure ?? '') : '');
        $initialScreeningMethod = !empty($initialSelectedEx['screening_method'])
            ? $initialSelectedEx['screening_method']
            : ($savedLabScreeningMethod !== 'N/A' ? ($savedLabScreeningMethod ?? '') : '');
        $initialDispositionOfExhibit = !empty($initialSelectedEx['disposition_of_exhibit'])
            ? $initialSelectedEx['disposition_of_exhibit']
            : ($savedLabDispositionOfExhibit !== 'N/A' ? ($savedLabDispositionOfExhibit ?? '') : '');

        $activeSuspectHasDrugExhibit =
            !empty($activeSuspectDrugExhibits) || !empty($dbActiveLabExam);
        if (empty($activeSuspectDrugExhibits) && empty($dbActiveLabExam)) {
            $activeSuspectHasSavedResult = false;
            $activeSuspectLabExam = null;
        }

        $labIsAssoc = false;
        if ($activeSuspectHasSavedResult && !empty($activeSuspectLabExam)) {
            $savedLabOwnerName = $activeSuspectLabExam['exhibit_owner_name'] ?? null;
            $firstDrugExOwner = $allDrugExhibitsList[0]['owner'] ?? null;
            $isExOwnerJoint = !empty($firstDrugExOwner) && (
                str_contains(strtolower($firstDrugExOwner), ',') ||
                str_contains(strtolower($firstDrugExOwner), '&') ||
                str_contains(strtolower($firstDrugExOwner), 'all suspect') ||
                str_contains(strtolower($firstDrugExOwner), 'both')
            );

            if ($isExOwnerJoint || (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1)) {
                $labOwnerName = !empty($primarySuspectsCombinedNames)
                    ? $primarySuspectsCombinedNames
                    : ($firstDrugExOwner ?: ($savedLabOwnerName ?: $activeSuspectName));
            } elseif (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
                $labOwnerName = $savedLabOwnerName;
            } elseif (!empty($firstDrugExOwner) && strtoupper(trim($firstDrugExOwner)) !== 'PRIMARY SUSPECT') {
                $labOwnerName = $firstDrugExOwner;
            } else {
                $labOwnerName = $activeSuspectName;
            }
        } else {
            $labOwnerName = $activeSuspectName;
        }

        $labOwnerLower = strtolower(trim($labOwnerName));
        $isJointLabOwner =
            str_contains($labOwnerLower, ',') ||
            str_contains($labOwnerLower, '&') ||
            str_contains($labOwnerLower, 'all suspect') ||
            str_contains($labOwnerLower, 'all suspects') ||
            (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1);
        if (!$isJointLabOwner && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
            $mCount = 0;
            foreach ($docketData['suspects'] as $sp) {
                if (
                    !empty($sp['suspect_name']) &&
                    str_contains($labOwnerLower, strtolower(trim($sp['suspect_name'])))
                ) {
                    $mCount++;
                }
            }
            if ($mCount > 1) {
                $isJointLabOwner = true;
            }
        }

        $pRows = $this->parsePresumptiveRows(
            $savedLabPresumptive,
            false,
            $savedLabPresumptivePresence ?? ($topLevelLabVars['savedLabPresumptivePresence'] ?? null),
            $savedLabPresumptivePercentage ?? ($topLevelLabVars['savedLabPresumptivePercentage'] ?? null)
        );

        $rawSavedConf = !empty($savedLabConfirmatory) && $savedLabConfirmatory !== 'N/A'
            ? strtoupper(trim((string) $savedLabConfirmatory))
            : null;
        $displayConfText = $rawSavedConf === 'NO' ? 'NO' : ($rawSavedConf === 'YES' ? 'YES' : 'N/A');
        $isDisplayConfNo = $displayConfText === 'NO';

        if (!empty($savedLabSampleTaken) && $savedLabSampleTaken !== 'N/A') {
            if (preg_match('/(kg|g|mg|lbs|grams|kilograms)/i', (string) $savedLabSampleTaken)) {
                $displaySampleTaken = $savedLabSampleTaken;
            } else {
                $displaySampleTaken = trim($savedLabSampleTaken . ' ' . ($savedLabSampleUnit ?? ''));
            }
            $displaySampleTakenLabel = null;
        } else {
            $displaySampleTaken = '—';
            $displaySampleTakenLabel = 'Not Recorded';
        }

        $detList = [];
        $rawDetList = $savedLabDetectedSubstances ?? [];
        if (empty($rawDetList) && !empty($labDetectedSubstances)) {
            $rawDetList = $labDetectedSubstances;
        }
        if (empty($rawDetList) && !empty($dbPrimaryLabExam->detected_substances)) {
            $rawDetList = $dbPrimaryLabExam->detected_substances;
        }
        if (empty($rawDetList) && !empty($case->detected_substances)) {
            $rawDetList = $case->detected_substances;
        }
        if (empty($rawDetList) && !empty($docketData['confirmatory_detected_substances'])) {
            $rawDetList = $docketData['confirmatory_detected_substances'];
        }
        if (empty($rawDetList) && !empty($docketData['lab_examination']['detected_substances'])) {
            $rawDetList = $docketData['lab_examination']['detected_substances'];
        }
        if (is_string($rawDetList)) {
            $rawDetList = json_decode($rawDetList, true) ?: [];
        }
        if (is_array($rawDetList)) {
            foreach ($rawDetList as $subItem) {
                if (is_array($subItem)) {
                    $sName =
                        $subItem['substance'] ??
                        ($subItem['name'] ?? ($subItem['compound'] ?? ($subItem['drug_name'] ?? '')));
                    $sPct =
                        $subItem['percentage'] ?? ($subItem['purity'] ?? ($subItem['pct'] ?? ''));
                    if (!empty($sName)) {
                        $detList[] = $sName . (!empty($sPct) ? ' (' . rtrim(trim($sPct), '%') . '%)' : '');
                    }
                } elseif (is_string($subItem) && trim($subItem) !== '') {
                    $detList[] = trim($subItem);
                }
            }
        }
        if (empty($detList) && !empty($savedLabSubstanceId) && $savedLabSubstanceId !== 'N/A') {
            $detList[] = $savedLabSubstanceId;
        }

        return [
            'activeSuspectLabExam' => $activeSuspectLabExam,
            'dbActiveLabExam' => $dbActiveLabExam,
            'activeSuspectHasSavedResult' => $activeSuspectHasSavedResult,
            'activeSuspectExhibits' => $activeSuspectExhibits,
            'activeSuspectDrugExhibits' => $activeSuspectDrugExhibits,
            'activeSuspectHasDrugExhibit' => $activeSuspectHasDrugExhibit,
            'labOwnerName' => $labOwnerName,
            'labOwnerLower' => $labOwnerLower,
            'isJointLabOwner' => $isJointLabOwner,
            'labIsAssoc' => $labIsAssoc,
            'savedLabExamRef' => $savedLabExamRef,
            'savedLabExhibitNo' => $savedLabExhibitNo,
            'savedLabCaseRef' => $savedLabCaseRef,
            'savedLabNumber' => $savedLabNumber,
            'savedLabUnit' => $savedLabUnit,
            'savedLabAnalyst' => $savedLabAnalyst,
            'savedLabDateReceived' => $savedLabDateReceived,
            'savedLabDateStarted' => $savedLabDateStarted,
            'savedLabDateCompleted' => $savedLabDateCompleted,
            'savedLabPresumptive' => $savedLabPresumptive,
            'savedLabConfirmatory' => $savedLabConfirmatory,
            'savedLabExhibitDesc' => $savedLabExhibitDesc,
            'savedLabSampleDesc' => $savedLabSampleDesc,
            'savedLabSampleUnits' => $savedLabSampleUnits,
            'savedLabSampleGross' => $savedLabSampleGross,
            'savedLabSampleTaken' => $savedLabSampleTaken,
            'savedLabSampleReserved' => $savedLabSampleReserved,
            'savedLabSampleUnit' => $savedLabSampleUnit,
            'savedLabSubstanceId' => $savedLabSubstanceId,
            'savedLabDetectedSubstances' => $savedLabDetectedSubstances,
            'savedLabTestMethods' => $savedLabTestMethods,
            'savedLabSpecifications' => $savedLabSpecifications,
            'savedLabRemark' => $savedLabRemark,
            'savedLabFindings' => $savedLabFindings,
            'savedLabPurposeOfExamination' => $savedLabPurposeOfExamination,
            'savedLabPreliminaryExamProcedure' => $savedLabPreliminaryExamProcedure,
            'savedLabScreeningMethod' => $savedLabScreeningMethod,
            'savedLabDispositionOfExhibit' => $savedLabDispositionOfExhibit,
            'checkIsDrugExhibitReview' => $this->drugClassifier->getClosure(),
            'dbExhibitsList' => $dbExhibitsList,
            'allDrugExhibitsList' => $allDrugExhibitsList,
            'suspectDrugExhibitsList' => $suspectDrugExhibitsList,
            'pendingDrugExhibits' => $pendingDrugExhibits,
            'drugExhibits' => $drugExhibits,
            'initialSelectedEx' => $initialSelectedEx,
            'initialHasSaved' => $initialHasSaved,
            'initialLabDesc' => $initialLabDesc,
            'initialPurposeOfExamination' => $initialPurposeOfExamination,
            'initialPreliminaryExamProcedure' => $initialPreliminaryExamProcedure,
            'initialScreeningMethod' => $initialScreeningMethod,
            'initialDispositionOfExhibit' => $initialDispositionOfExhibit,
            'pRaw' => trim($savedLabPresumptive ?? ''),
            'pRows' => $pRows,
            'rawSavedConf' => $rawSavedConf,
            'displayConfText' => $displayConfText,
            'isDisplayConfNo' => $isDisplayConfNo,
            'displaySampleTaken' => $displaySampleTaken,
            'displaySampleTakenLabel' => $displaySampleTakenLabel,
            'detList' => $detList,
        ];
    }

    /**
     * Resolve associate suspect laboratory examination dossiers (lines 9138-10092).
     */
    public function resolveAssociateLabDossiers(
        mixed $case,
        array $docketData,
        array $suspectsList,
        array $primaryAndCoNamesList,
        string $primarySuspectsCombinedNames,
        array $allDrugExhibitsList,
        array $labContextVars,
    ): array {
        $savedLabExhibitNo = $labContextVars['savedLabExhibitNo'] ?? 'N/A';
        $savedLabSampleDesc = $labContextVars['savedLabSampleDesc'] ?? 'N/A';
        $savedLabSubstanceId = $labContextVars['savedLabSubstanceId'] ?? 'N/A';
        $labDateReceived = $labContextVars['labDateReceived'] ?? date('Y-m-d');
        $labDateStarted = $labContextVars['labDateStarted'] ?? date('Y-m-d');
        $labDateCompleted = $labContextVars['labDateCompleted'] ?? date('Y-m-d');
        $firstDrugFound = $labContextVars['firstDrugFound'] ?? null;

        $labOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
        $labIsAssoc = !empty($docketData['lab_examination']['exhibit_is_associate']);

        if (
            empty($labOwnerName) ||
            strtoupper(trim($labOwnerName)) === 'ASSOCIATE SUSPECT' ||
            strtoupper(trim($labOwnerName)) === 'PRIMARY SUSPECT'
        ) {
            if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                foreach ($docketData['seizures'] as $sz) {
                    $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                    $szIsAssoc =
                        !empty($sz['is_associate']) ||
                        !empty($szOwner) ||
                        (!empty($sz['association_role']) &&
                            str_contains(strtolower($sz['association_role']), 'associate'));
                    $szName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                    $szNo = $sz['exhibit_number'] ?? '';
                    $szId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);

                    if (
                        (!empty($savedLabExhibitNo) && $szNo === $savedLabExhibitNo) ||
                        (!empty($savedLabSampleDesc) &&
                            strtolower(trim($szName)) === strtolower(trim($savedLabSampleDesc))) ||
                        (!empty($savedLabSubstanceId) &&
                            strtolower(trim($szName)) === strtolower(trim($savedLabSubstanceId)))
                    ) {
                        if ($szIsAssoc) {
                            $labIsAssoc = true;
                            $matchedOwner = null;
                            if (!empty($szId) && !empty($docketData['suspects'])) {
                                foreach ($docketData['suspects'] as $sp) {
                                    $spId = $sp['associate_id'] ?? ($sp['suspect_id'] ?? null);
                                    if (!empty($spId) && strtolower(trim($spId)) === strtolower(trim($szId))) {
                                        $matchedOwner = $sp['suspect_name'] ?? null;
                                        break;
                                    }
                                }
                            }
                            $labOwnerName =
                                !empty($szOwner) && strtoupper(trim($szOwner)) !== 'ASSOCIATE SUSPECT'
                                    ? $szOwner
                                    : $matchedOwner ?? null;
                        } else {
                            $labIsAssoc = false;
                            $labOwnerName = !empty($primarySuspectsCombinedNames)
                                ? $primarySuspectsCombinedNames
                                : 'Primary Suspect';
                        }
                        break;
                    }
                }
            }
        }

        $labIsAssoc = false;
        $savedLabOwnerName = $docketData['lab_examination']['exhibit_owner_name'] ?? null;
        $firstDrugExOwner = $allDrugExhibitsList[0]['owner'] ?? null;
        $isExOwnerJoint = !empty($firstDrugExOwner) && (
            str_contains(strtolower($firstDrugExOwner), ',') ||
            str_contains(strtolower($firstDrugExOwner), '&') ||
            str_contains(strtolower($firstDrugExOwner), 'all suspect') ||
            str_contains(strtolower($firstDrugExOwner), 'both')
        );

        if ($isExOwnerJoint || (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1)) {
            $labOwnerName = !empty($primarySuspectsCombinedNames)
                ? $primarySuspectsCombinedNames
                : ($firstDrugExOwner ?: ($savedLabOwnerName ?: ($case->suspect_name ?? 'Primary Suspect')));
        } elseif (!empty($savedLabOwnerName) && strtoupper(trim($savedLabOwnerName)) !== 'PRIMARY SUSPECT') {
            $labOwnerName = $savedLabOwnerName;
        } elseif (!empty($firstDrugExOwner) && strtoupper(trim($firstDrugExOwner)) !== 'PRIMARY SUSPECT') {
            $labOwnerName = $firstDrugExOwner;
        } else {
            $labOwnerName = !empty($primarySuspectsCombinedNames)
                ? $primarySuspectsCombinedNames
                : $case->suspect_name ?? 'Primary Suspect';
        }

        $labOwnerLower = strtolower(trim($labOwnerName));
        $isJointLabOwner =
            str_contains($labOwnerLower, ',') ||
            str_contains($labOwnerLower, '&') ||
            str_contains($labOwnerLower, 'all suspect') ||
            str_contains($labOwnerLower, 'all suspects') ||
            (!empty($primaryAndCoNamesList) && count($primaryAndCoNamesList) > 1);
        if (!$isJointLabOwner && !empty($docketData['suspects']) && count($docketData['suspects']) > 1) {
            $mCount = 0;
            foreach ($docketData['suspects'] as $sp) {
                if (
                    !empty($sp['suspect_name']) &&
                    str_contains($labOwnerLower, strtolower(trim($sp['suspect_name'])))
                ) {
                    $mCount++;
                }
            }
            if ($mCount > 1) {
                $isJointLabOwner = true;
            }
        }

        $primaryNameStr = strtolower(trim($primarySuspectsCombinedNames ?: ($case->suspect_name ?? '')));
        $primarySuspectNamesArray = [];
        if (!empty($primaryAndCoNamesList) && is_array($primaryAndCoNamesList)) {
            foreach ($primaryAndCoNamesList as $pn) {
                if (!empty($pn)) {
                    $primarySuspectNamesArray[] = strtolower(trim($pn));
                }
            }
        }
        if (!empty($suspectsList) && is_array($suspectsList)) {
            foreach ($suspectsList as $sItem) {
                $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                $isAssoc =
                    !empty($sItem['is_associate']) ||
                    (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                if (!$isAssoc && !empty($sItem['suspect_name'])) {
                    $spNameClean = strtolower(trim($sItem['suspect_name']));
                    if (!in_array($spNameClean, $primarySuspectNamesArray)) {
                        $primarySuspectNamesArray[] = $spNameClean;
                    }
                }
            }
        }

        $isPrimaryOrCoSuspectName = $this->ownershipResolver->getIsPrimaryOrCoSuspectNameClosure($primarySuspectNamesArray);

        $associateSuspectsForLab = [];
        if (!empty($suspectsList) && is_array($suspectsList)) {
            foreach ($suspectsList as $sIdx => $sItem) {
                $role = strtolower($sItem['association_role'] ?? ($sItem['role'] ?? ''));
                $spIsAssoc =
                    !empty($sItem['is_associate']) ||
                    (!empty($role) && $role !== 'primary suspect' && !str_contains($role, 'co-suspect'));
                $spName = $sItem['suspect_name'] ?? null;
                if ($spIsAssoc && !empty($spName) && !$this->ownershipResolver->isPrimaryOrCoSuspectName($spName, $primarySuspectNamesArray)) {
                    $associateSuspectsForLab[] = [
                        'index' => $sItem['suspect_number'] ?? $sIdx + 1,
                        'name' => $spName,
                        'item' => $sItem,
                    ];
                }
            }
        }
        if (!empty($docketData['associated_suspects']) && is_array($docketData['associated_suspects'])) {
            foreach ($docketData['associated_suspects'] as $aIdx => $aSp) {
                $aName = $aSp['suspect_name'] ?? ($aSp['name'] ?? null);
                if ($aName && !$this->ownershipResolver->isPrimaryOrCoSuspectName($aName, $primarySuspectNamesArray)) {
                    $aNameLower = strtolower(trim($aName));
                    $alreadyAdded = false;
                    foreach ($associateSuspectsForLab as $existingAssoc) {
                        if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (!$alreadyAdded) {
                        $associateSuspectsForLab[] = [
                            'index' => count($suspectsList) + $aIdx + 1,
                            'name' => $aName,
                            'item' => $aSp,
                        ];
                    }
                }
            }
        }
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                $assocP = $szItem['associated_person'] ?? null;
                if (
                    !empty($assocP) &&
                    !$this->ownershipResolver->isPrimaryOrCoSuspectName($assocP, $primarySuspectNamesArray) &&
                    $this->drugClassifier->isDrugExhibit($szItem)
                ) {
                    $aNameLower = strtolower(trim($assocP));
                    $alreadyAdded = false;
                    foreach ($associateSuspectsForLab as $existingAssoc) {
                        if (strtolower(trim($existingAssoc['name'])) === $aNameLower) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (!$alreadyAdded) {
                        $associateSuspectsForLab[] = [
                            'index' => count($associateSuspectsForLab) + 2,
                            'name' => $assocP,
                            'item' => ['suspect_name' => $assocP, 'is_associate' => true],
                        ];
                    }
                }
            }
        }

        $savedAssocDbRecords = !empty($case->id)
            ? AssociateLabExamination::where('case_id', $case->id)->where('has_saved', true)->get()
            : collect();

        foreach ($associateSuspectsForLab as $idx => $assocLabInfo) {
            $aSpName = $assocLabInfo['name'];
            $aSpIndex = $assocLabInfo['index'];
            $aSpId =
                $assocLabInfo['item']['associate_id'] ??
                ($assocLabInfo['item']['suspect_id'] ??
                    ($assocLabInfo['associate_id'] ?? ($assocLabInfo['suspect_id'] ?? null)));

            $dbAssocExam = $case->associateLabExaminations
                ? $case->associateLabExaminations->first(
                    fn($a) => strtolower(trim($a->suspect_name)) === strtolower(trim($aSpName)),
                )
                : null;
            $docketAssocExam = null;
            if (
                !empty($docketData['associate_lab_examinations']) &&
                is_array($docketData['associate_lab_examinations'])
            ) {
                foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                    if (strtolower(trim($kAssoc)) === strtolower(trim($aSpName))) {
                        $docketAssocExam = $vAssoc;
                        break;
                    }
                }
            }

            $assocExhibitsList = is_object($case) && method_exists($case, 'getExhibitsForSuspect')
                ? array_values(
                    array_filter(
                        $case->getExhibitsForSuspect(
                            $aSpName,
                            $assocLabInfo['item']['id'] ?? null,
                            $aSpId,
                            'Associate Suspect',
                        ),
                        fn($item) => $this->drugClassifier->isDrugExhibit($item),
                    ),
                )
                : [];
            $firstAssocEx = !empty($assocExhibitsList) ? $assocExhibitsList[0] : null;

            $assocLabExam = null;
            if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
                foreach ($docketData['lab_examinations_list'] as $lItem) {
                    if (
                        !empty($lItem['exhibit_owner_name']) &&
                        strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($aSpName))
                    ) {
                        $assocLabExam = $lItem;
                        break;
                    }
                }
            }

            $assocExamRef =
                $dbAssocExam && !empty($dbAssocExam->exam_reference)
                    ? $dbAssocExam->exam_reference
                    : (!empty($docketAssocExam['exam_reference'])
                        ? $docketAssocExam['exam_reference']
                        : (!empty($assocLabExam['exam_reference'])
                            ? $assocLabExam['exam_reference']
                            : (!empty($firstAssocEx['exam_reference'])
                                ? $firstAssocEx['exam_reference']
                                : (!empty($case->lab_exam_reference)
                                    ? $this->referenceService->normalizeExamReference($case->lab_exam_reference)
                                    : $this->referenceService->generateExamReference('Lab Analysis')))));

            $assocExhibitNo =
                $dbAssocExam && !empty($dbAssocExam->exhibit_number)
                    ? $dbAssocExam->exhibit_number
                    : (!empty($docketAssocExam['exhibit_number'])
                        ? $docketAssocExam['exhibit_number']
                        : (!empty($assocLabExam['exhibit_number'])
                            ? $assocLabExam['exhibit_number']
                            : (!empty($firstAssocEx['exhibit_number'])
                                ? 'EXH-0' . $firstAssocEx['exhibit_number']
                                : 'EXH-0' . ($aSpIndex ?? 2))));

            $assocLabNumber =
                $dbAssocExam && !empty($dbAssocExam->laboratory_number)
                    ? $dbAssocExam->laboratory_number
                    : (!empty($docketAssocExam['laboratory_number'])
                        ? $docketAssocExam['laboratory_number']
                        : (!empty($assocLabExam['laboratory_number'])
                            ? $assocLabExam['laboratory_number']
                            : (!empty($firstAssocEx['laboratory_number'])
                                ? $firstAssocEx['laboratory_number']
                                : 'N/A')));

            $assocAnalyst =
                $dbAssocExam && !empty($dbAssocExam->analyst)
                    ? $dbAssocExam->analyst
                    : (!empty($docketAssocExam['analyst'])
                        ? $docketAssocExam['analyst']
                        : (!empty($assocLabExam['analyst'])
                            ? $assocLabExam['analyst']
                            : (!empty($firstAssocEx['analyst'])
                                ? $firstAssocEx['analyst']
                                : 'N/A')));

            $assocDateReceived =
                $dbAssocExam && !empty($dbAssocExam->date_received) && $dbAssocExam->date_received !== 'N/A'
                    ? (is_object($dbAssocExam->date_received)
                        ? $dbAssocExam->date_received->format('Y-m-d')
                        : substr((string) $dbAssocExam->date_received, 0, 10))
                    : (!empty($docketAssocExam['date_received']) && $docketAssocExam['date_received'] !== 'N/A'
                        ? $docketAssocExam['date_received']
                        : (!empty($assocLabExam['date_received']) && $assocLabExam['date_received'] !== 'N/A'
                            ? $assocLabExam['date_received']
                            : (!empty($firstAssocEx['date_received']) && $firstAssocEx['date_received'] !== 'N/A'
                                ? $firstAssocEx['date_received']
                                : (!empty($firstAssocEx['seizure_date']) && $firstAssocEx['seizure_date'] !== 'N/A'
                                    ? $firstAssocEx['seizure_date']
                                    : $labDateReceived))));

            $assocDateStarted =
                $dbAssocExam && !empty($dbAssocExam->date_started) && $dbAssocExam->date_started !== 'N/A'
                    ? (is_object($dbAssocExam->date_started)
                        ? $dbAssocExam->date_started->format('Y-m-d')
                        : substr((string) $dbAssocExam->date_started, 0, 10))
                    : (!empty($docketAssocExam['date_started']) && $docketAssocExam['date_started'] !== 'N/A'
                        ? $docketAssocExam['date_started']
                        : (!empty($assocLabExam['date_started']) && $assocLabExam['date_started'] !== 'N/A'
                            ? $assocLabExam['date_started']
                            : (!empty($firstAssocEx['date_started']) && $firstAssocEx['date_started'] !== 'N/A'
                                ? $firstAssocEx['date_started']
                                : (!empty($labDateStarted) && $labDateStarted !== 'N/A'
                                    ? $labDateStarted
                                    : $assocDateReceived))));

            $assocDateCompleted =
                $dbAssocExam && !empty($dbAssocExam->date_completed) && $dbAssocExam->date_completed !== 'N/A'
                    ? (is_object($dbAssocExam->date_completed)
                        ? $dbAssocExam->date_completed->format('Y-m-d')
                        : substr((string) $dbAssocExam->date_completed, 0, 10))
                    : (!empty($docketAssocExam['date_completed']) && $docketAssocExam['date_completed'] !== 'N/A'
                        ? $docketAssocExam['date_completed']
                        : (!empty($assocLabExam['date_completed']) && $assocLabExam['date_completed'] !== 'N/A'
                            ? $assocLabExam['date_completed']
                            : (!empty($firstAssocEx['date_completed']) && $firstAssocEx['date_completed'] !== 'N/A'
                                ? $firstAssocEx['date_completed']
                                : (!empty($labDateCompleted) && $labDateCompleted !== 'N/A'
                                    ? $labDateCompleted
                                    : $assocDateStarted))));

            $assocDrugName = $dbAssocExam
                ? $dbAssocExam->sample_weight_description
                : (!empty($docketAssocExam['sample_weight']['description'])
                    ? $docketAssocExam['sample_weight']['description']
                    : (!empty($firstAssocEx['seizure_name_drug'])
                        ? $firstAssocEx['seizure_name_drug']
                        : (!empty($firstAssocEx['seizure_name'])
                            ? $firstAssocEx['seizure_name']
                            : (!empty($assocLabExam['substance_identification'])
                                ? $assocLabExam['substance_identification']
                                : (!empty($assocLabExam['sample_weight']['description'])
                                    ? $assocLabExam['sample_weight']['description']
                                    : ($firstDrugFound ?: 'Drug Exhibit'))))));

            $assocUnits = $dbAssocExam
                ? $dbAssocExam->sample_weight_units
                : (!empty($docketAssocExam['sample_weight']['units_packaging'])
                    ? $docketAssocExam['sample_weight']['units_packaging']
                    : (!empty($firstAssocEx['sample_units'])
                        ? $firstAssocEx['sample_units']
                        : (!empty($firstAssocEx['seizure_quantity'])
                            ? trim($firstAssocEx['seizure_quantity'] . ' ' . ($firstAssocEx['seizure_packaging'] ?? ''))
                            : (!empty($assocLabExam['sample_weight']['units_packaging'])
                                ? $assocLabExam['sample_weight']['units_packaging']
                                : 'N/A'))));

            $assocGross = $dbAssocExam
                ? $dbAssocExam->sample_weight_gross
                : (!empty($docketAssocExam['sample_weight']['gross_weight'])
                    ? $docketAssocExam['sample_weight']['gross_weight']
                    : (!empty($firstAssocEx['sample_gross'])
                        ? $firstAssocEx['sample_gross']
                        : (!empty($firstAssocEx['seizure_weight'])
                            ? trim($firstAssocEx['seizure_weight'] . ' ' . ($firstAssocEx['seizure_unit'] ?? ''))
                            : (!empty($assocLabExam['sample_weight']['gross_weight'])
                                ? $assocLabExam['sample_weight']['gross_weight']
                                : 'N/A'))));

            $assocTaken = $dbAssocExam
                ? $dbAssocExam->sample_weight_taken
                : (!empty($docketAssocExam['sample_weight']['sample_taken'])
                    ? $docketAssocExam['sample_weight']['sample_taken']
                    : (!empty($firstAssocEx['sample_taken'])
                        ? $firstAssocEx['sample_taken']
                        : (!empty($assocLabExam['sample_weight']['sample_taken'])
                            ? $assocLabExam['sample_weight']['sample_taken']
                            : 'N/A')));

            $assocReserved = $dbAssocExam
                ? $dbAssocExam->sample_weight_reserved
                : (!empty($docketAssocExam['sample_weight']['reserved_samples'])
                    ? $docketAssocExam['sample_weight']['reserved_samples']
                    : (!empty($firstAssocEx['sample_reserved'])
                        ? $firstAssocEx['sample_reserved']
                        : (!empty($assocLabExam['sample_weight']['reserved_samples'])
                            ? $assocLabExam['sample_weight']['reserved_samples']
                            : 'N/A')));

            $assocPresumptiveText = $dbAssocExam
                ? $dbAssocExam->presumptive_result
                : (!empty($docketAssocExam['presumptive_result'])
                    ? $docketAssocExam['presumptive_result']
                    : (!empty($firstAssocEx['presumptive_result'])
                        ? $firstAssocEx['presumptive_result']
                        : (!empty($assocLabExam['presumptive_result'])
                            ? $assocLabExam['presumptive_result']
                            : 'N/A')));

            $assocConfirmatoryVal = $dbAssocExam
                ? $dbAssocExam->confirmatory_result
                : (!empty($docketAssocExam['confirmatory_result'])
                    ? $docketAssocExam['confirmatory_result']
                    : (!empty($firstAssocEx['confirmatory_result'])
                        ? $firstAssocEx['confirmatory_result']
                        : (!empty($assocLabExam['confirmatory_result'])
                            ? $assocLabExam['confirmatory_result']
                            : 'N/A')));

            $assocSubstanceIdVal = $dbAssocExam
                ? $dbAssocExam->substance_identification
                : (!empty($docketAssocExam['substance_identification'])
                    ? $docketAssocExam['substance_identification']
                    : (!empty($firstAssocEx['substance_identification'])
                        ? $firstAssocEx['substance_identification']
                        : (!empty($assocLabExam['substance_identification'])
                            ? $assocLabExam['substance_identification']
                            : 'N/A')));

            $assocSpecsVal = $dbAssocExam
                ? $dbAssocExam->specifications
                : (!empty($docketAssocExam['specifications'])
                    ? $docketAssocExam['specifications']
                    : (!empty($firstAssocEx['specifications'])
                        ? $firstAssocEx['specifications']
                        : (!empty($assocLabExam['specifications'])
                            ? $assocLabExam['specifications']
                            : 'N/A')));

            $assocMethodsVal = $dbAssocExam
                ? $dbAssocExam->test_methods
                : (!empty($docketAssocExam['test_methods'])
                    ? $docketAssocExam['test_methods']
                    : (!empty($firstAssocEx['test_methods'])
                        ? $firstAssocEx['test_methods']
                        : (!empty($assocLabExam['test_methods'])
                            ? $assocLabExam['test_methods']
                            : 'N/A')));

            $assocFindingsVal = $dbAssocExam
                ? $dbAssocExam->findings
                : (!empty($docketAssocExam['findings'])
                    ? $docketAssocExam['findings']
                    : (!empty($firstAssocEx['findings'])
                        ? $firstAssocEx['findings']
                        : (!empty($firstAssocEx['notes'])
                            ? $firstAssocEx['notes']
                            : (!empty($assocLabExam['findings'])
                                ? $assocLabExam['findings']
                                : 'No specific laboratory analytical findings logged.'))));

            $isCoveredByMainReport = false;
            if (!empty($labOwnerName)) {
                $lOwnerLow = strtolower(trim($labOwnerName));
                $aSpNameLow = strtolower(trim($aSpName));
                if (str_contains($lOwnerLow, 'all suspect') || str_contains($lOwnerLow, $aSpNameLow)) {
                    $isCoveredByMainReport = true;
                }
            }

            $isCoSuspect = false;
            if (!empty($docketData['suspects'])) {
                foreach ($docketData['suspects'] as $sp) {
                    if (
                        !empty($sp['suspect_name']) &&
                        strtolower(trim($sp['suspect_name'])) === strtolower(trim($aSpName))
                    ) {
                        if (
                            !empty($sp['association_role']) &&
                            str_contains(strtolower($sp['association_role']), 'co-suspect')
                        ) {
                            $isCoSuspect = true;
                        }
                        break;
                    }
                }
            }

            $hasThisAssocDrug = false;
            if (!empty($assocExhibitsList)) {
                foreach ($assocExhibitsList as $aEx) {
                    $aCatLower = strtolower(trim($aEx['exhibit_category'] ?? ($aEx['category'] ?? '')));
                    $aTypeLower = strtolower(trim($aEx['exhibit_type'] ?? ''));
                    if (
                        str_contains($aCatLower, 'drug') ||
                        str_contains($aCatLower, 'narcotic') ||
                        str_contains($aTypeLower, 'drug')
                    ) {
                        $hasThisAssocDrug = true;
                        break;
                    }
                }
            }

            $assocHasRealLabRecord = $savedAssocDbRecords->contains(function ($aRec) use ($aSpName) {
                $aName = strtolower(trim($aRec->suspect_name ?? ''));
                $target = strtolower(trim($aSpName));
                return !empty($aName) && $aName === $target;
            });

            $hasThisAssocSavedLab = $assocHasRealLabRecord;
            if (!$hasThisAssocDrug) {
                $hasThisAssocSavedLab = false;
            }

            $secondaryBadge = $isCoSuspect ? 'CO-SUSPECT DOSSIER' : 'ASSOCIATE DOSSIER';
            $secondaryBadgeBg = $isCoSuspect ? 'bg-blue-600' : 'bg-amber-600';

            $pRowsAssoc = $this->parsePresumptiveRows($assocPresumptiveText, true);

            $assocDetPcts =
                $dbAssocExam && (!empty($dbAssocExam->detected_subtances_percentage) || !empty($dbAssocExam->detected_substances_percentage))
                    ? ($dbAssocExam->detected_subtances_percentage ?? $dbAssocExam->detected_substances_percentage)
                    : (!empty($docketAssocExam['detected_subtances_percentage'])
                        ? $docketAssocExam['detected_subtances_percentage']
                        : (!empty($docketAssocExam['detected_substances_percentage'])
                            ? $docketAssocExam['detected_substances_percentage']
                            : (!empty($assocLabExam['detected_subtances_percentage'])
                                ? $assocLabExam['detected_subtances_percentage']
                                : (!empty($assocLabExam['detected_substances_percentage'])
                                    ? $assocLabExam['detected_substances_percentage']
                                    : []))));
            $cRowsAssoc = $this->parseConfirmatoryRows($assocConfirmatoryVal, $assocDetSubs ?? [], $assocDetPcts ?? []);

            $detListAssoc = [];
            $assocDetSubs =
                $dbAssocExam && !empty($dbAssocExam->detected_substances)
                    ? $dbAssocExam->detected_substances
                    : (!empty($docketAssocExam['detected_substances'])
                        ? $docketAssocExam['detected_substances']
                        : (!empty($assocLabExam['detected_substances'])
                            ? $assocLabExam['detected_substances']
                            : (!empty($firstAssocEx['detected_substances'])
                                ? $firstAssocEx['detected_substances']
                                : [])));
            if (is_string($assocDetSubs)) {
                $assocDetSubs = json_decode($assocDetSubs, true) ?: [];
            }
            if (is_array($assocDetSubs)) {
                foreach ($assocDetSubs as $subItem) {
                    if (is_array($subItem)) {
                        $sName =
                            $subItem['substance'] ??
                            ($subItem['name'] ?? ($subItem['compound'] ?? ($subItem['drug_name'] ?? '')));
                        $sPct =
                            $subItem['percentage'] ?? ($subItem['purity'] ?? ($subItem['pct'] ?? ''));
                        if (!empty($sName)) {
                            $detListAssoc[] = $sName . (!empty($sPct) ? ' (' . rtrim(trim($sPct), '%') . '%)' : '');
                        }
                    }
                }
            }
            if (empty($detListAssoc)) {
                $pText = $assocPresumptiveText ?? '';
                if (!empty($pText) && (str_contains($pText, 'Presence of:') || str_contains($pText, 'YES'))) {
                    $cleanP = preg_replace('/^YES\s*\((.*)\)$/i', '$1', trim($pText));
                    $pSegs = explode(';', $cleanP);
                    foreach ($pSegs as $pSeg) {
                        $pSeg = trim($pSeg);
                        $pName = '';
                        $pPur = '';
                        if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $pSeg, $mP)) {
                            $pName = trim($mP[1]);
                        }
                        if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $pSeg, $mPur)) {
                            $pPur = trim($mPur[1]);
                        }
                        if (!empty($pName)) {
                            $detListAssoc[] = $pName . (!empty($pPur) ? ' (' . rtrim(trim($pPur), '%') . '%)' : '');
                        }
                    }
                }
            }
            if (empty($detListAssoc) && !empty($assocSubstanceIdVal) && $assocSubstanceIdVal !== 'N/A') {
                $detListAssoc[] = $assocSubstanceIdVal;
            }

            $associateSuspectsForLab[$idx]['resolved_dossier'] = [
                'aSpName' => $aSpName,
                'aSpIndex' => $aSpIndex,
                'aSpId' => $aSpId,
                'dbAssocExam' => $dbAssocExam,
                'docketAssocExam' => $docketAssocExam,
                'assocExhibitsList' => $assocExhibitsList,
                'firstAssocEx' => $firstAssocEx,
                'assocLabExam' => $assocLabExam,
                'hasThisAssocSavedLab' => $hasThisAssocSavedLab,
                'assocExamRef' => $assocExamRef,
                'assocExhibitNo' => $assocExhibitNo,
                'assocLabNumber' => $assocLabNumber,
                'assocAnalyst' => $assocAnalyst,
                'assocDateReceived' => $assocDateReceived,
                'assocDateStarted' => $assocDateStarted,
                'assocDateCompleted' => $assocDateCompleted,
                'assocDrugName' => $assocDrugName,
                'assocUnits' => $assocUnits,
                'assocGross' => $assocGross,
                'assocTaken' => $assocTaken,
                'assocReserved' => $assocReserved,
                'assocPresumptiveText' => $assocPresumptiveText,
                'assocConfirmatoryVal' => $assocConfirmatoryVal,
                'assocSubstanceIdVal' => $assocSubstanceIdVal,
                'assocSpecsVal' => $assocSpecsVal,
                'assocMethodsVal' => $assocMethodsVal,
                'assocFindingsVal' => $assocFindingsVal,
                'isCoveredByMainReport' => $isCoveredByMainReport,
                'isCoSuspect' => $isCoSuspect,
                'hasThisAssocDrug' => $hasThisAssocDrug,
                'assocHasRealLabRecord' => $assocHasRealLabRecord,
                'secondaryBadge' => $secondaryBadge,
                'secondaryBadgeBg' => $secondaryBadgeBg,
                'pRowsAssoc' => $pRowsAssoc,
                'cRowsAssoc' => $cRowsAssoc,
                'detListAssoc' => $detListAssoc,
            ];
        }

        return [
            'assocMainLabOwnerName' => $labOwnerName,
            'primaryNameStr' => $primaryNameStr,
            'primarySuspectNamesArray' => $primarySuspectNamesArray,
            'isPrimaryOrCoSuspectName' => $isPrimaryOrCoSuspectName,
            'associateSuspectsForLab' => $associateSuspectsForLab,
        ];
    }

    /**
     * Prepare a normalized lab examination array for the lab-examination-card partial.
     */
    public function formatLabExamForCard(
        array $sDossierLabExam,
        mixed $case = null,
        string $headingName = 'Primary Suspect',
    ): array {
        $hasLabData = !empty($sDossierLabExam) && (
            !empty($sDossierLabExam['has_saved']) ||
            !empty($sDossierLabExam['has_saved_lab'])
        );

        $resolvedExamRef = !empty($sDossierLabExam['exam_reference'])
            ? (str_starts_with(trim($sDossierLabExam['exam_reference']), 'GSA-')
                ? str_replace('GSA-', 'NLR-', $sDossierLabExam['exam_reference'])
                : $sDossierLabExam['exam_reference'])
            : $this->referenceService->resolveExamReference($case, $sDossierLabExam);

        $caseInc = !empty($case->incident_date)
            ? (is_object($case->incident_date) ? $case->incident_date->format('Y-m-d') : substr((string) $case->incident_date, 0, 10))
            : null;
        $caseCre = isset($case) && !empty($case->created_at)
            ? (is_object($case->created_at) ? $case->created_at->format('Y-m-d') : substr((string) $case->created_at, 0, 10))
            : date('Y-m-d');
        $caseUp = isset($case) && !empty($case->updated_at)
            ? (is_object($case->updated_at) ? $case->updated_at->format('Y-m-d') : substr((string) $case->updated_at, 0, 10))
            : date('Y-m-d');

        $rawRec = $sDossierLabExam['date_received'] ?? ($sDossierLabExam['lab_date_received'] ?? null);
        $timelineReceived = (!empty($rawRec) && $rawRec !== 'N/A')
            ? (is_object($rawRec) ? $rawRec->format('Y-m-d') : substr((string) $rawRec, 0, 10))
            : ($caseInc ?: $caseCre);

        $rawStart = $sDossierLabExam['date_started'] ?? ($sDossierLabExam['lab_date_started'] ?? null);
        $timelineStarted = (!empty($rawStart) && $rawStart !== 'N/A')
            ? (is_object($rawStart) ? $rawStart->format('Y-m-d') : substr((string) $rawStart, 0, 10))
            : $timelineReceived;

        $rawComp = $sDossierLabExam['date_completed'] ?? ($sDossierLabExam['lab_date_completed'] ?? null);
        $timelineCompleted = (!empty($rawComp) && $rawComp !== 'N/A')
            ? (is_object($rawComp) ? $rawComp->format('Y-m-d') : substr((string) $rawComp, 0, 10))
            : ($caseUp ?: $timelineStarted);

        $pRowsDos = $this->parsePresumptiveRows(
            $sDossierLabExam['presumptive_result'] ?? '',
            true,
            $sDossierLabExam['presumptive_presence'] ?? null,
            $sDossierLabExam['presumptive_percentage'] ?? null
        );

        $rawConf = $sDossierLabExam['confirmatory_result'] ?? ($sDossierLabExam['confirmatory'] ?? null);
        $confVal = !empty($rawConf) && $rawConf !== 'N/A' ? strtoupper(trim((string) $rawConf)) : null;

        $detSubs = $sDossierLabExam['detected_substances'] ?? [];
        if (is_string($detSubs)) {
            $detSubs = json_decode($detSubs, true) ?: [];
        }
        $detPcts = $sDossierLabExam['detected_subtances_percentage'] ?? ($sDossierLabExam['detected_substances_percentage'] ?? []);
        if (is_string($detPcts)) {
            $detPcts = json_decode($detPcts, true) ?: preg_split('/\s*[;,]\s*/', $detPcts);
        }
        $pctArr = is_array($detPcts) ? array_values($detPcts) : [];

        $normalizedDetSubs = [];
        if (is_array($detSubs)) {
            foreach (array_values($detSubs) as $idx => $cSub) {
                $cName = is_array($cSub)
                    ? $cSub['compound'] ?? ($cSub['name'] ?? ($cSub['substance'] ?? 'Unknown'))
                    : $cSub;
                $cPct = is_array($cSub)
                    ? $cSub['purity'] ?? ($cSub['percentage'] ?? ($cSub['concentration'] ?? ($pctArr[$idx] ?? null)))
                    : ($pctArr[$idx] ?? null);
                $cMeth = is_array($cSub) ? ($cSub['method'] ?? null) : null;
                $normalizedDetSubs[] = [
                    'name' => $cName,
                    'pct' => $cPct ? rtrim((string) $cPct, '%') : null,
                    'method' => $cMeth,
                ];
            }
        }

        $cRowsDos = $this->parseConfirmatoryRows(
            $sDossierLabExam['confirmatory_result'] ?? ($sDossierLabExam['confirmatory'] ?? null),
            $detSubs,
            $pctArr
        );

        return array_merge($sDossierLabExam, [
            'card_has_lab_data' => $hasLabData,
            'card_slug' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $headingName)),
            'card_exam_reference' => $resolvedExamRef,
            'card_timeline_received' => $timelineReceived,
            'card_timeline_started' => $timelineStarted,
            'card_timeline_completed' => $timelineCompleted,
            'card_presumptive_rows' => $pRowsDos,
            'card_confirmatory_val' => $confVal,
            'card_confirmatory_rows' => $cRowsDos,
            'card_detected_substances' => $normalizedDetSubs,
        ]);
    }
}
