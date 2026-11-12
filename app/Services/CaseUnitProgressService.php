<?php

namespace App\Services;

use App\Enums\UnitName;
use Closure;

class CaseUnitProgressService
{
    /**
     * Determine whether a unit has active (non-pending) progress in docket_data['unit_progress'].
     */
    public function unitHasActiveProgress(array $docketData, string $unitKey): bool
    {
        if (empty($docketData['unit_progress'][$unitKey]['status'])) {
            return false;
        }
        $st = strtoupper(trim((string) $docketData['unit_progress'][$unitKey]['status']));
        return $st !== '' && $st !== 'PENDING';
    }

    /**
     * Determine whether a unit has a recorded officer in docket_data['unit_progress'].
     */
    public function unitHasRecordedOfficer(array $docketData, string $unitKey): bool
    {
        return !empty($docketData['unit_progress'][$unitKey]['officer'])
            && trim((string) $docketData['unit_progress'][$unitKey]['officer']) !== '';
    }

    public function getUnitHasActiveProgressClosure(array $docketData): Closure
    {
        return fn(string $unitKey): bool => $this->unitHasActiveProgress($docketData, $unitKey);
    }

    public function getUnitHasRecordedOfficerClosure(array $docketData): Closure
    {
        return fn(string $unitKey): bool => $this->unitHasRecordedOfficer($docketData, $unitKey);
    }

    /**
     * Resolve dynamic units list, unit involvement flags, and unit progress statuses for the active suspect.
     */
    public function resolveUnitProgress(
        mixed $case,
        array $docketData,
        string $activeSuspectNameLower,
        bool $isActiveSuspectPrimary,
        bool $suspectLabExamSaved,
        bool $suspectHasDrug,
        bool $activeSuspectHasDrugExhibit,
        bool $suspectHasElectronic,
        bool $suspectHasCrimeScene,
        string $defaultLabHeadName = 'N/A',
        ?string $labAnalyst = null,
        ?bool $hasGsaReports = null,
        bool $suspectHasWeaponOrVehicle = false,
    ): array {
        $cssUnit = enum_exists(UnitName::class)
            ? UnitName::CRIME_SCENE_SUPPORT->value
            : 'Crime Scene Support';
        $labUnit = enum_exists(UnitName::class)
            ? UnitName::LAB_ANALYSIS->value
            : 'Lab Analysis';
        $dpfUnit = enum_exists(UnitName::class)
            ? UnitName::DIGITAL_PHYSICAL_FORENSICS->value
            : 'Digital & Physical Forensics';
        $emUnit = enum_exists(UnitName::class)
            ? UnitName::EVIDENCE_MANAGEMENT->value
            : 'Evidence Management';

        $caseUnitRaw = trim($case->unit_name ?? ($case->unit?->name ?? ($case->user?->unit ?? ($docketData['created_by_unit'] ?? ''))));
        $caseUnitLower = strtolower($caseUnitRaw);
        $caseNumberUpper = strtoupper($case->case_number ?? '');

        $isCreatorEvidence = ($caseUnitLower === strtolower($emUnit))
            || str_contains($caseUnitLower, 'evidence')
            || str_starts_with($caseNumberUpper, 'EM-')
            || str_starts_with($caseNumberUpper, 'EVD-');

        $isCreatorLab = ($caseUnitLower === strtolower($labUnit))
            || str_contains($caseUnitLower, 'lab')
            || str_starts_with($caseNumberUpper, 'LAB-')
            || str_starts_with($caseNumberUpper, 'FSL-');

        $isCreatorDpf = ($caseUnitLower === strtolower($dpfUnit))
            || str_contains($caseUnitLower, 'digital')
            || str_contains($caseUnitLower, 'physical')
            || str_starts_with($caseNumberUpper, 'DPF-')
            || str_starts_with($caseNumberUpper, 'DIG-');

        $isCreatorCss = !$isCreatorEvidence && !$isCreatorLab && !$isCreatorDpf && (
            ($caseUnitLower === strtolower($cssUnit))
            || str_contains($caseUnitLower, 'crime')
            || str_contains($caseUnitLower, 'scene')
            || str_starts_with($caseNumberUpper, 'CSS-')
            || empty($caseUnitLower)
        );

        if ($hasGsaReports === null) {
            $hasGsaReports = false;
            if (!empty($docketData['gsa_reports']) && is_array($docketData['gsa_reports']) && count($docketData['gsa_reports']) > 0) {
                $hasGsaReports = true;
            } elseif (!empty($docketData['gsa_files']) && is_array($docketData['gsa_files']) && count($docketData['gsa_files']) > 0) {
                $hasGsaReports = true;
            } elseif (!empty($docketData['unit_progress']['Lab Analysis']['status']) && strtoupper(trim((string)$docketData['unit_progress']['Lab Analysis']['status'])) === 'COMPLETED' && !$suspectLabExamSaved) {
                $hasGsaReports = true;
            } else {
                try {
                    if (class_exists(\App\Models\SuspectGsaReport::class) && !empty($case->id)) {
                        $hasGsaReports = \App\Models\SuspectGsaReport::forCase($case->case_number ?? $case->id, $case->id)->count() > 0;
                    }
                } catch (\Throwable $e) {
                    $hasGsaReports = false;
                }
            }
        }

        $hasLabRecord = (bool) (
            $hasGsaReports
            ||
            $suspectLabExamSaved
            || !empty($docketData['lab_examination']['has_saved'])
            || (!empty($docketData['lab_examinations_list'])
                && is_array($docketData['lab_examinations_list'])
                && collect($docketData['lab_examinations_list'])->contains(fn($l) => !empty($l['has_saved'])))
            || (!empty($docketData['associate_lab_examinations'])
                && is_array($docketData['associate_lab_examinations'])
                && collect($docketData['associate_lab_examinations'])->contains(fn($a) => !empty($a['has_saved'])))
        );

        $suspectHasDigitalRecord = false;
        $suspectDigitalSavedRecord = null;
        if (!empty($docketData['digital_forensics']) && is_array($docketData['digital_forensics'])) {
            if (isset($docketData['digital_forensics'][$activeSuspectNameLower]) && is_array($docketData['digital_forensics'][$activeSuspectNameLower])) {
                $candidate = $docketData['digital_forensics'][$activeSuspectNameLower];
                if (!empty($candidate['has_saved']) || !empty($candidate['analyst']) || !empty($candidate['report']) || !empty($candidate['extraction_data']) || !empty($candidate['forensic_status'])) {
                    $suspectHasDigitalRecord = true;
                    if (!empty($candidate['has_saved'])) {
                        $suspectDigitalSavedRecord = $candidate;
                    }
                }
            }
            if (!$suspectHasDigitalRecord) {
                foreach ($docketData['digital_forensics'] as $dfK => $dfEntry) {
                    if (is_array($dfEntry)) {
                        $dfSuspect = strtolower(trim((string) ($dfEntry['suspect_name'] ?? ($dfEntry['owner_name'] ?? $dfK))));
                        $isMatch = ($dfSuspect === $activeSuspectNameLower)
                            || str_contains($dfSuspect, $activeSuspectNameLower)
                            || str_contains($activeSuspectNameLower, $dfSuspect)
                            || ($isActiveSuspectPrimary && in_array($dfSuspect, ['primary', 'primary suspect', ''], true));
                        if ($isMatch && (!empty($dfEntry['has_saved']) || !empty($dfEntry['analyst']) || !empty($dfEntry['report']) || !empty($dfEntry['extraction_data']) || !empty($dfEntry['forensic_status']))) {
                            $suspectHasDigitalRecord = true;
                            if (!empty($dfEntry['has_saved'])) {
                                $suspectDigitalSavedRecord = $dfEntry;
                            }
                            break;
                        }
                    }
                }
            }
        }
        if (!$suspectHasDigitalRecord && $isActiveSuspectPrimary && (!empty($docketData['digital_court_orders']) || !empty($docketData['digital_reports']))) {
            $suspectHasDigitalRecord = true;
        }

        $suspectHasDrugExhibit = (bool) ($suspectHasDrug || $activeSuspectHasDrugExhibit);
        $suspectHasElectronicExhibit = (bool) $suspectHasElectronic;
        $suspectHasCrimeSceneExhibit = (bool) $suspectHasCrimeScene;

        $cssInvolved = $isCreatorCss
            || $this->unitHasActiveProgress($docketData, $cssUnit)
            || $this->unitHasRecordedOfficer($docketData, $cssUnit)
            || $suspectHasWeaponOrVehicle
            || (!$isCreatorEvidence && $suspectHasCrimeSceneExhibit);

        $labInvolved = $isCreatorLab
            || $this->unitHasActiveProgress($docketData, $labUnit)
            || $this->unitHasRecordedOfficer($docketData, $labUnit)
            || $suspectLabExamSaved
            || $hasGsaReports
            || ($isActiveSuspectPrimary && $hasLabRecord)
            || $suspectHasDrugExhibit;

        $dpfInvolved = $isCreatorDpf
            || $this->unitHasActiveProgress($docketData, $dpfUnit)
            || $this->unitHasRecordedOfficer($docketData, $dpfUnit)
            || $suspectHasDigitalRecord
            || $suspectHasElectronicExhibit;

        $creatorUnit = null;
        if ($isCreatorLab) {
            $creatorUnit = $labUnit;
            $labInvolved = true;
        } elseif ($isCreatorDpf) {
            $creatorUnit = $dpfUnit;
            $dpfInvolved = true;
        } elseif ($isCreatorEvidence) {
            $creatorUnit = $emUnit;
        } else {
            $creatorUnit = $cssUnit;
            $isCreatorCss = true;
            $cssInvolved = true;
        }

        $unitsList = [];
        if (!empty($creatorUnit)) {
            $unitsList[] = $creatorUnit;
        }

        $canonicalOperationalUnits = [$cssUnit, $labUnit, $dpfUnit];
        $involvedMap = [
            $cssUnit => $cssInvolved,
            $labUnit => $labInvolved,
            $dpfUnit => $dpfInvolved,
        ];

        foreach ($canonicalOperationalUnits as $opUnit) {
            if (!empty($involvedMap[$opUnit]) && !in_array($opUnit, $unitsList, true)) {
                $unitsList[] = $opUnit;
            }
        }

        if (!$isCreatorEvidence && !in_array($emUnit, $unitsList, true)) {
            $unitsList[] = $emUnit;
        }

        $unitsList = array_values(array_unique($unitsList));
        $totalUnitsCount = count($unitsList);

        $unitProgress = [];
        foreach ($unitsList as $uKey) {
            if ($uKey === 'Lab Analysis') {
                $isLabDone = (bool) ($suspectLabExamSaved || $hasGsaReports);

                $labOfficerResolved = null;
                if (!empty($docketData['lab_examination']['analyst'])) {
                    $labOfficerResolved = $docketData['lab_examination']['analyst'];
                } elseif (!empty($docketData['unit_progress']['Lab Analysis']['officer'])) {
                    $labOfficerResolved = $docketData['unit_progress']['Lab Analysis']['officer'];
                } elseif (!empty($labAnalyst)) {
                    $labOfficerResolved = $labAnalyst;
                } else {
                    $labOfficerResolved = $case->submitting_officer ?? ($defaultLabHeadName ?? 'N/A');
                }

                $unitProgress[$uKey] = [
                    'status' => $isLabDone ? 'COMPLETED' : 'PENDING',
                    'officer' => $labOfficerResolved,
                    'updated_at' => $isLabDone ? (optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s')) : null,
                    'notes' => $isLabDone
                        ? ($suspectLabExamSaved
                            ? 'Laboratory Examination Information completed and signed off'
                            : 'GSA Report uploaded and attached')
                        : ($suspectHasDrugExhibit
                            ? 'Drug exhibit registered; awaiting Laboratory Examination Information entry'
                            : 'Awaiting lab testing'),
                ];
            } elseif ($uKey === 'Digital & Physical Forensics') {
                $dpfWorkflowCompleted = !empty($docketData['unit_progress']['Digital & Physical Forensics']['status'])
                    && strtoupper($docketData['unit_progress']['Digital & Physical Forensics']['status']) === 'COMPLETED';

                $dpfCreatorOutputSaved = $isCreatorDpf && (
                    !empty($suspectDigitalSavedRecord)
                    || !empty($docketData['digital_reports'])
                    || !empty($docketData['digital_court_orders'])
                    || $dpfWorkflowCompleted
                );

                $isDigDone = (!empty($suspectDigitalSavedRecord) && !empty($suspectDigitalSavedRecord['has_saved']))
                    || $dpfCreatorOutputSaved
                    || $dpfWorkflowCompleted;

                $digitalOfficerResolved = $suspectDigitalSavedRecord['analyst'] ?? (
                    $docketData['digital_forensics'][$activeSuspectNameLower]['analyst'] ??
                    ($docketData['unit_progress']['Digital & Physical Forensics']['officer'] ??
                        ($isCreatorDpf ? $case->submitting_officer ?? 'N/A' : 'N/A'))
                );

                $unitProgress[$uKey] = [
                    'status' => $isDigDone ? 'COMPLETED' : 'PENDING',
                    'officer' => $digitalOfficerResolved,
                    'updated_at' => $isDigDone
                        ? (optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s'))
                        : null,
                    'notes' => $isDigDone
                        ? 'Device extraction & digital forensic examination completed'
                        : ($suspectHasElectronicExhibit
                            ? 'Electronic device registered; awaiting digital forensic extraction'
                            : 'Awaiting device extraction'),
                ];
            } elseif ($uKey === 'Crime Scene Support') {
                $cssProgressStatus = strtoupper(trim((string) ($docketData['unit_progress']['Crime Scene Support']['status'] ?? '')));
                $cssCompleted = ($cssProgressStatus === 'COMPLETED')
                    || ($isCreatorCss && empty($cssProgressStatus) && !empty($case->id));

                $unitProgress[$uKey] = [
                    'status' => $cssCompleted ? 'COMPLETED' : 'PENDING',
                    'officer' => $docketData['unit_progress']['Crime Scene Support']['officer'] ?? ($case->user?->fullname ?? ($case->submitting_officer ?? null)),
                    'updated_at' => $cssCompleted ? (optional($case->created_at)->toDateTimeString() ?? date('Y-m-d H:i:s')) : null,
                    'notes' => $cssCompleted
                        ? 'Submitted via Crime Scene Support & Field Operations Workspace'
                        : 'Awaiting crime scene and field operations submission',
                ];
            } elseif ($uKey === 'Evidence Management') {
                $evProgressStatus = strtoupper(trim((string) ($docketData['unit_progress']['Evidence Management']['status'] ?? '')));
                $evCompleted = ($evProgressStatus === 'COMPLETED')
                    || ($isCreatorEvidence && !empty($case->id));

                $unitProgress[$uKey] = [
                    'status' => $evCompleted ? 'COMPLETED' : 'PENDING',
                    'officer' => $docketData['unit_progress']['Evidence Management']['officer']
                        ?? ($case->user?->fullname ?? ($case->submitting_officer ?? 'Evidence Custodian')),
                    'updated_at' => $evCompleted
                        ? ($docketData['unit_progress']['Evidence Management']['updated_at'] ?? (optional($case->created_at)->toDateTimeString() ?? date('Y-m-d H:i:s')))
                        : null,
                    'notes' => $evCompleted
                        ? 'Processed via Evidence Management & Custody Vault Workspace'
                        : 'Awaiting evidence receipt and vault custody intake',
                ];
            }
        }

        $completedUnitsCount = 0;
        foreach ($unitsList as $uKey) {
            if (isset($unitProgress[$uKey]['status']) && strtoupper($unitProgress[$uKey]['status']) === 'COMPLETED') {
                $completedUnitsCount++;
            }
        }

        return [
            'cssUnit' => $cssUnit,
            'labUnit' => $labUnit,
            'dpfUnit' => $dpfUnit,
            'emUnit' => $emUnit,
            'caseUnitRaw' => $caseUnitRaw,
            'caseUnitLower' => $caseUnitLower,
            'caseNumberUpper' => $caseNumberUpper,
            'isCreatorEvidence' => $isCreatorEvidence,
            'isCreatorLab' => $isCreatorLab,
            'isCreatorDpf' => $isCreatorDpf,
            'isCreatorCss' => $isCreatorCss,
            'unitHasActiveProgress' => $this->getUnitHasActiveProgressClosure($docketData),
            'unitHasRecordedOfficer' => $this->getUnitHasRecordedOfficerClosure($docketData),
            'hasLabRecord' => $hasLabRecord,
            'suspectHasDigitalRecord' => $suspectHasDigitalRecord,
            'suspectDigitalSavedRecord' => $suspectDigitalSavedRecord,
            'suspectHasDrugExhibit' => $suspectHasDrugExhibit,
            'suspectHasElectronicExhibit' => $suspectHasElectronicExhibit,
            'suspectHasCrimeSceneExhibit' => $suspectHasCrimeSceneExhibit,
            'cssInvolved' => $cssInvolved,
            'labInvolved' => $labInvolved,
            'dpfInvolved' => $dpfInvolved,
            'creatorUnit' => $creatorUnit,
            'canonicalOperationalUnits' => $canonicalOperationalUnits,
            'involvedMap' => $involvedMap,
            'unitsList' => $unitsList,
            'totalUnitsCount' => $totalUnitsCount,
            'unitProgress' => $unitProgress,
            'completedUnitsCount' => $completedUnitsCount,
            'hofDone' => $completedUnitsCount >= $totalUnitsCount,
        ];
    }

    /**
     * Synchronize Lab Analysis and DPF officer/status fields after lab analyst resolution (lines 1727-1775).
     */
    public function finalizeUnitProgressAfterLabResolution(
        array $unitProgress,
        array $unitsList,
        mixed $case,
        array $docketData,
        bool $suspectLabExamSaved,
        bool $suspectHasDrugExhibit,
        string $activeSuspectNameLower,
        bool $isCreatorDpf,
        ?string $labAnalyst,
        string $defaultLabHeadName = 'N/A',
        ?bool $hasGsaReports = null,
        bool $suspectHasWeaponOrVehicle = false,
    ): array {
        if (isset($unitProgress['Lab Analysis'])) {
            if ($hasGsaReports === null) {
                $hasGsaReports = false;
                if (!empty($docketData['gsa_reports']) && is_array($docketData['gsa_reports']) && count($docketData['gsa_reports']) > 0) {
                    $hasGsaReports = true;
                } elseif (!empty($docketData['gsa_files']) && is_array($docketData['gsa_files']) && count($docketData['gsa_files']) > 0) {
                    $hasGsaReports = true;
                } elseif (!empty($docketData['unit_progress']['Lab Analysis']['status']) && strtoupper(trim((string)$docketData['unit_progress']['Lab Analysis']['status'])) === 'COMPLETED' && !$suspectLabExamSaved) {
                    $hasGsaReports = true;
                } else {
                    try {
                        if (class_exists(\App\Models\SuspectGsaReport::class) && !empty($case->id)) {
                            $hasGsaReports = \App\Models\SuspectGsaReport::forCase($case->case_number ?? $case->id, $case->id)->count() > 0;
                        }
                    } catch (\Throwable $e) {
                        $hasGsaReports = false;
                    }
                }
            }

            $isLabDone = (bool) ($suspectLabExamSaved || $hasGsaReports);
            $activeSuspectLabStatus = $isLabDone ? 'COMPLETED' : 'PENDING';
            $unitProgress['Lab Analysis']['status'] = $activeSuspectLabStatus;

            $resolvedLabOfficer = $unitProgress['Lab Analysis']['officer'] ?? null;
            if (empty($resolvedLabOfficer)) {
                if (!empty($docketData['lab_examination']['analyst'])) {
                    $resolvedLabOfficer = $docketData['lab_examination']['analyst'];
                } elseif (!empty($docketData['unit_progress']['Lab Analysis']['officer'])) {
                    $resolvedLabOfficer = $docketData['unit_progress']['Lab Analysis']['officer'];
                } elseif (!empty($labAnalyst)) {
                    $resolvedLabOfficer = $labAnalyst;
                } else {
                    $resolvedLabOfficer = $case->submitting_officer ?? ($defaultLabHeadName ?? 'N/A');
                }
            }

            $unitProgress['Lab Analysis']['officer'] = $resolvedLabOfficer;

            if ($activeSuspectLabStatus === 'COMPLETED') {
                $unitProgress['Lab Analysis']['notes'] = $suspectLabExamSaved
                    ? 'Laboratory Examination Information completed and signed off'
                    : 'GSA Report uploaded and attached';
                if (empty($unitProgress['Lab Analysis']['updated_at'])) {
                    $unitProgress['Lab Analysis']['updated_at'] =
                        optional($case->updated_at)->toDateTimeString() ?? date('Y-m-d H:i:s');
                }
            } else {
                $unitProgress['Lab Analysis']['notes'] =
                    $suspectHasDrugExhibit
                        ? 'Drug exhibit registered; awaiting Laboratory Examination Information entry'
                        : 'Awaiting lab testing';
            }
        }

        if (isset($unitProgress['Digital & Physical Forensics'])) {
            if (empty($unitProgress['Digital & Physical Forensics']['officer'])) {
                $unitProgress['Digital & Physical Forensics']['officer'] =
                    $docketData['digital_forensics'][$activeSuspectNameLower]['analyst'] ??
                    ($docketData['unit_progress']['Digital & Physical Forensics']['officer'] ??
                        ($isCreatorDpf ? $case->submitting_officer ?? 'N/A' : 'N/A'));
            }
        }

        $totalUnitsCount = count($unitsList);
        $completedUnitsCount = 0;
        foreach ($unitsList as $uKey) {
            if (isset($unitProgress[$uKey]['status']) && strtoupper($unitProgress[$uKey]['status']) === 'COMPLETED') {
                $completedUnitsCount++;
            }
        }

        return [
            'unitProgress' => $unitProgress,
            'totalUnitsCount' => $totalUnitsCount,
            'completedUnitsCount' => $completedUnitsCount,
            'hofDone' => $completedUnitsCount >= $totalUnitsCount,
        ];
    }
}
