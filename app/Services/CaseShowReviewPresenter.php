<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseHandover;
use App\Models\DrugScreening;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use App\Policies\ForensicCasePolicy;
use Illuminate\Http\Request;

class CaseShowReviewPresenter
{
    protected array $viewData = [];

    public function __construct(
        protected DrugExhibitClassifier $drugClassifier,
        protected CasePhotoResolver $photoResolver,
        protected ExhibitOwnershipResolver $ownershipResolver,
        protected CaseExhibitResolver $exhibitResolver,
        protected CaseUnitProgressService $unitProgressService,
        protected CaseLabExamResolver $labExamResolver,
        protected ?ForensicCasePolicy $policy = null,
    ) {
        $this->policy = $this->policy ?? new ForensicCasePolicy();
    }

    /**
     * Build the presentation view-model for an explicit forensic unit without route/header/session guessing.
     *
     * Supported $unitKey values: 'css', 'lab', 'dpf', 'em', 'hof' (or their full unit names).
     */
    public function presentForUnit(mixed $case, string $unitKey, ?Request $request = null, mixed $authUser = null): self
    {
        $request = $request ?? request();
        $authUser = $authUser ?? auth()->user();

        $normalized = $this->normalizeUnitKey($unitKey);

        $reqFrom =
            $request->input('from') ??
            ($request->input('unit') ??
                ($request->input('source') ??
                    ($request->query('from') ?? ($request->query('unit') ?? $request->query('source')))));
        $userUnit = $authUser->unit ?? (session('user_unit') ?? '');
        $referer = $request->header('referer') ?? '';

        $isDigitalForensicsRegister =
            str_contains(strtolower($reqFrom ?? ''), 'digital_forensics_register') ||
            str_contains(strtolower($reqFrom ?? ''), 'digital_register') ||
            $reqFrom === 'Digital_Forensics_Register';

        $isForensicHeadContext = $normalized === 'hof' || $normalized === 'dg';
        $isLabAnalysis = $normalized === 'lab';
        $isLabContext = $normalized === 'lab';
        $isDigitalForensics = $normalized === 'dpf';
        $isEvidenceManagement = $normalized === 'em';

        $isHof = $isForensicHeadContext || $this->policy->isForensicHead($authUser instanceof User ? $authUser : null);
        $isCrimeSceneContext = $normalized === 'css' && !$isHof;

        $activeUnitContext = match ($normalized) {
            'lab' => 'Lab Analysis',
            'dpf' => 'Digital & Physical Forensics',
            'em' => 'Evidence Management',
            'dg' => 'Office of the Director-General',
            'hof' => !empty($userUnit) ? $userUnit : ($case->unit_name ?? 'Forensic Directorate'),
            default => (!empty($reqFrom) && !str_contains(strtolower($reqFrom), 'crime') && !str_contains(strtolower($reqFrom), 'field'))
                ? $reqFrom
                : 'Crime Scene Support',
        };

        return $this->buildViewData(
            $case,
            $request,
            $authUser,
            [
                'isHof' => $isHof,
                'reqFrom' => $reqFrom,
                'userUnit' => $userUnit,
                'referer' => $referer,
                'activeUnitContext' => $activeUnitContext,
                'isDigitalForensicsRegister' => $isDigitalForensicsRegister,
                'isDigitalForensics' => $isDigitalForensics,
                'isEvidenceManagement' => $isEvidenceManagement,
                'isLabContext' => $isLabContext,
                'isLabAnalysis' => $isLabAnalysis,
                'isCrimeSceneContext' => $isCrimeSceneContext,
                'isForensicHeadContext' => $isForensicHeadContext,
            ],
        );
    }

    /**
     * Resolve the target per-unit route name for legacy /cases/{id}/review requests.
     */
    public function resolveLegacyRedirectRoute(mixed $case, ?Request $request = null, mixed $authUser = null): string
    {
        $request = $request ?? request();
        $authUser = $authUser ?? auth()->user();

        $reqFrom = $request->query('from') ?? ($request->query('unit') ?? $request->query('source'));
        $referer = $request->header('referer') ?? '';

        if (
            $request->is('DG*') ||
            $request->is('dg*') ||
            ($referer && str_contains($referer, 'DG'))
        ) {
            return 'dg.cases.review';
        }

        if (
            $request->is('Forensic_Head*') ||
            $request->is('forensic-head*') ||
            ($referer && str_contains($referer, 'Forensic_Head'))
        ) {
            return 'forensic_head.cases.review';
        }

        if (!empty($reqFrom)) {
            $fromLower = strtolower((string) $reqFrom);
            if ($fromLower === 'dg' || str_contains($fromLower, 'director-general') || str_contains($fromLower, 'director general')) {
                return 'dg.cases.review';
            }
            if (str_contains($fromLower, 'head') || str_contains($fromLower, 'forensic_head') || str_contains($fromLower, 'forensic head') || $fromLower === 'hof') {
                return 'forensic_head.cases.review';
            }
            if (str_contains($fromLower, 'digital') || str_contains($fromLower, 'physical')) {
                return 'digital.cases.review';
            }
            if (str_contains($fromLower, 'evidence')) {
                return 'evidence.cases.review';
            }
            if (str_contains($fromLower, 'lab')) {
                return 'lab.cases.review';
            }
            if (str_contains($fromLower, 'crime') || str_contains($fromLower, 'field')) {
                return 'crime_scene.cases.review';
            }
        }

        $userRoleLower = strtolower((string) ($authUser->role ?? ''));
        if (str_contains($userRoleLower, 'directorate head') || str_contains($userRoleLower, 'forensic head') || $userRoleLower === 'head') {
            return 'forensic_head.cases.review';
        }

        $userUnitLower = strtolower((string) ($authUser->unit ?? (session('user_unit') ?? '')));
        if (str_contains($userUnitLower, 'digital') || str_contains($userUnitLower, 'physical')) {
            return 'digital.cases.review';
        }
        if (str_contains($userUnitLower, 'evidence')) {
            return 'evidence.cases.review';
        }
        if (str_contains($userUnitLower, 'lab')) {
            return 'lab.cases.review';
        }
        if (str_contains($userUnitLower, 'crime') || str_contains($userUnitLower, 'field')) {
            return 'crime_scene.cases.review';
        }

        $caseUnitLower = strtolower((string) ($case->unit_name ?? ''));
        if (str_contains($caseUnitLower, 'digital') || str_contains($caseUnitLower, 'physical')) {
            return 'digital.cases.review';
        }
        if (str_contains($caseUnitLower, 'evidence')) {
            return 'evidence.cases.review';
        }
        if (str_contains($caseUnitLower, 'lab')) {
            return 'lab.cases.review';
        }

        return 'crime_scene.cases.review';
    }

    protected function normalizeUnitKey(string $unitKey): string
    {
        $lower = strtolower(trim($unitKey));

        return match (true) {
            $lower === 'lab' || str_contains($lower, 'lab') => 'lab',
            $lower === 'dpf' || str_contains($lower, 'digital') || str_contains($lower, 'physical') => 'dpf',
            $lower === 'em' || str_contains($lower, 'evidence') => 'em',
            $lower === 'dg' || str_contains($lower, 'director-general') || str_contains($lower, 'director general') => 'dg',
            $lower === 'hof' || str_contains($lower, 'forensic head') || str_contains($lower, 'forensic directorate') || str_contains($lower, 'directorate head') => 'hof',
            default => 'css',
        };
    }

    /**
     * Build the complete presentation view-model for resources/views/cases/show_review.blade.php.
     */
    public function present(mixed $case, ?Request $request = null, mixed $authUser = null, ?bool $presetIsLabAnalysis = null): self
    {
        $request = $request ?? request();
        $authUser = $authUser ?? auth()->user();

        $isHof =
            $this->policy->isForensicHead($authUser instanceof User ? $authUser : null) ||
            $request->is('Forensic_Head*') ||
            session('user_role') === 'HOF';

        $reqFrom = $request->query('from') ?? ($request->query('unit') ?? $request->query('source'));
        $userUnit = $authUser->unit ?? (session('user_unit') ?? '');
        $referer = $request->header('referer') ?? '';

        if (!empty($reqFrom)) {
            if (str_contains(strtolower($reqFrom), 'crime') || str_contains(strtolower($reqFrom), 'field')) {
                $activeUnitContext = 'Crime Scene Support';
            } elseif (str_contains(strtolower($reqFrom), 'lab')) {
                $activeUnitContext = 'Lab Analysis';
            } elseif (str_contains(strtolower($reqFrom), 'evidence')) {
                $activeUnitContext = 'Evidence Management';
            } elseif (str_contains(strtolower($reqFrom), 'digital') || str_contains(strtolower($reqFrom), 'physical')) {
                $activeUnitContext = 'Digital & Physical Forensics';
            } else {
                $activeUnitContext = $reqFrom;
            }
        } elseif ($request->is('Lab_Analysis*') || $request->is('lab-analysis*') || $request->is('lab-result*')) {
            $activeUnitContext = 'Lab Analysis';
        } elseif ($request->is('Crime_Scene_Support*') || $request->is('crime-scene-support*')) {
            $activeUnitContext = 'Crime Scene Support';
        } elseif ($request->is('Digital_Forensics*') || $request->is('digital-forensics*')) {
            $activeUnitContext = 'Digital & Physical Forensics';
        } elseif ($request->is('Evidence_Management*') || $request->is('evidence-management*')) {
            $activeUnitContext = 'Evidence Management';
        } elseif (!empty($userUnit)) {
            if (str_contains(strtolower($userUnit), 'digital') || str_contains(strtolower($userUnit), 'physical')) {
                $activeUnitContext = 'Digital & Physical Forensics';
            } elseif (str_contains(strtolower($userUnit), 'evidence')) {
                $activeUnitContext = 'Evidence Management';
            } elseif (str_contains(strtolower($userUnit), 'lab')) {
                $activeUnitContext = 'Lab Analysis';
            } elseif (str_contains(strtolower($userUnit), 'crime') || str_contains(strtolower($userUnit), 'field')) {
                $activeUnitContext = 'Crime Scene Support';
            } else {
                $activeUnitContext = $userUnit;
            }
        } else {
            if (
                str_contains(strtolower($case->unit_name ?? ''), 'digital') ||
                str_contains(strtolower($case->unit_name ?? ''), 'physical')
            ) {
                $activeUnitContext = 'Digital & Physical Forensics';
            } elseif (str_contains(strtolower($case->unit_name ?? ''), 'evidence')) {
                $activeUnitContext = 'Evidence Management';
            } elseif (str_contains(strtolower($case->unit_name ?? ''), 'lab')) {
                $activeUnitContext = 'Lab Analysis';
            } else {
                $activeUnitContext = $case->unit_name ?? 'Crime Scene Support';
            }
        }

        $reqFrom =
            $request->input('from') ??
            ($request->input('unit') ??
                ($request->input('source') ??
                    ($request->query('from') ?? ($request->query('unit') ?? $request->query('source')))));

        $isDigitalForensicsRegister =
            str_contains(strtolower($reqFrom ?? ''), 'digital_forensics_register') ||
            str_contains(strtolower($reqFrom ?? ''), 'digital_register') ||
            $reqFrom === 'Digital_Forensics_Register';

        $isDigitalForensics =
            str_contains(strtolower($activeUnitContext ?? ''), 'digital') ||
            str_contains(strtolower($activeUnitContext ?? ''), 'physical') ||
            str_contains(strtolower($reqFrom ?? ''), 'digital') ||
            str_contains(strtolower($reqFrom ?? ''), 'physical') ||
            $request->is('Digital_Forensics*') ||
            $request->is('digital-forensics*') ||
            (isset($authUser) &&
                (str_contains(strtolower($authUser->unit ?? ''), 'digital') ||
                    str_contains(strtolower($authUser->unit ?? ''), 'physical')));

        $isEvidenceManagement =
            str_contains(strtolower($activeUnitContext ?? ''), 'evidence') ||
            str_contains(strtolower($reqFrom ?? ''), 'evidence') ||
            $request->is('Evidence_Management*') ||
            $request->is('evidence-management*') ||
            (isset($authUser) && str_contains(strtolower($authUser->unit ?? ''), 'evidence'));

        $isLabContext =
            $request->is('Lab_Analysis*') ||
            $request->is('lab-analysis*') ||
            $request->is('lab-result*') ||
            $request->query('unit') === 'Lab Analysis' ||
            $request->query('from') === 'lab' ||
            (str_contains(strtolower($activeUnitContext ?? ''), 'lab') &&
                !$isEvidenceManagement &&
                !$isDigitalForensics &&
                !$request->is('Crime_Scene_Support*') &&
                !$request->is('Evidence_Management*') &&
                !$request->is('Digital_Forensics*'));

        $isLabAnalysis = $presetIsLabAnalysis !== null
            ? $presetIsLabAnalysis
            : ($isLabContext && !$isDigitalForensics && !$isEvidenceManagement);

        $isCrimeSceneContext =
            (str_contains(strtolower($activeUnitContext), 'crime') ||
                str_contains(strtolower($reqFrom ?? ''), 'crime') ||
                $request->is('Crime_Scene_Support*') ||
                $request->is('crime-scene-support*')) &&
            !$isHof &&
            !$isLabContext &&
            !$isDigitalForensics &&
            !$isEvidenceManagement;

        $isForensicHeadContext =
            $request->is('Forensic_Head*') ||
            ($request->header('referer') && str_contains($request->header('referer'), 'Forensic_Head'));

        return $this->buildViewData(
            $case,
            $request,
            $authUser,
            [
                'isHof' => $isHof,
                'reqFrom' => $reqFrom,
                'userUnit' => $userUnit,
                'referer' => $referer,
                'activeUnitContext' => $activeUnitContext,
                'isDigitalForensicsRegister' => $isDigitalForensicsRegister,
                'isDigitalForensics' => $isDigitalForensics,
                'isEvidenceManagement' => $isEvidenceManagement,
                'isLabContext' => $isLabContext,
                'isLabAnalysis' => $isLabAnalysis,
                'isCrimeSceneContext' => $isCrimeSceneContext,
                'isForensicHeadContext' => $isForensicHeadContext,
            ],
        );
    }

    /**
     * Assemble the presentation payload for the resolved unit context.
     */
    protected function buildViewData(mixed $case, Request $request, mixed $authUser, array $unitFlags): self
    {
        $isHof = $unitFlags['isHof'];
        $reqFrom = $unitFlags['reqFrom'];
        $userUnit = $unitFlags['userUnit'];
        $referer = $unitFlags['referer'];
        $activeUnitContext = $unitFlags['activeUnitContext'];
        $isDigitalForensicsRegister = $unitFlags['isDigitalForensicsRegister'];
        $isDigitalForensics = $unitFlags['isDigitalForensics'];
        $isEvidenceManagement = $unitFlags['isEvidenceManagement'];
        $isLabContext = $unitFlags['isLabContext'];
        $isLabAnalysis = $unitFlags['isLabAnalysis'];
        $isCrimeSceneContext = $unitFlags['isCrimeSceneContext'];
        $isForensicHeadContext = $unitFlags['isForensicHeadContext'];

        $extractAllPhotosHelper = $this->photoResolver->getExtractAllPhotosClosure();
        $formatPhotoUrlHelper = $this->photoResolver->getFormatPhotoUrlClosure();
        $formatMugshotUrl = $this->photoResolver->getFormatMugshotUrlClosure();
        $getImgSrc = $this->photoResolver->getImgSrcClosure();
        $checkIsDrugExhibit = $this->drugClassifier->getClosure();

        $isMultiCase = ($case instanceof ForensicCase)
            ? $case->isMultipleSuspect()
            : (($case->underlyingRecord ?? null) instanceof MultipleSuspectCase
                || ($case instanceof MultipleSuspectCase)
                || !empty($case->total_suspects_count)
                || !empty($case->is_multiple));

        $activeSuspectHasSavedResult = false;
        $activeSuspectHasDrugExhibit = false;

        $dbLabHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'N/A';
        $officerName = $authUser->fullname ?? (session('user_name') ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : 'N/A')));
        $officerRole = $isHof ? 'Forensic Head' : 'Commanding Officer';

        $status = $case->status ?? 'Awaiting Sign-off';
        $priority = strtoupper($case->priority ?? 'HIGH');

        $docketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        // 1. Photos & Drug Classification
        $photoData = $this->photoResolver->resolveCasePhotos($case, $docketData);
        $drugCaseData = $this->drugClassifier->classifyCase($case, $docketData, $isDigitalForensics);

        // 2. Controller, Handover, Creator, and AuditLog Status Updater
        $controllerUser = is_object($case) && method_exists($case, 'getControllerUser') ? $case->getControllerUser() : null;
        $isCaseController = $this->policy->controlCaseStatus($authUser instanceof User ? $authUser : null, $case, $isForensicHeadContext)
            || ($authUser && $isHof);
        $activeHandover = is_object($case) && method_exists($case, 'getActiveHandover') ? $case->getActiveHandover() : null;
        $currentCaseStatus = $case->case_status ?? 'Under Investigation';

        $caseCreatorName = $case->user?->fullname
            ?? ($case->user?->username ?? ($docketData['created_by_name'] ?? 'Investigating Officer'));
        $caseCreatorUnit = $case->user?->unit ?? ($case->unit_name ?? 'NACOC Forensic');
        $caseCreatedAt = $case->created_at ? $case->created_at->format('d M Y, H:i') : null;
        $arrestingOfficerName = $case->arresting_officer ?? ($case->submitting_officer ?? 'N/A');

        $statusAudit = AuditLog::where('case_number', $case->case_number)
            ->whereIn('action', ['CASE_STATUS_CHANGED', 'CASE_STATUS_UPDATED'])
            ->latest('id')
            ->first();
        $statusUpdatedBy = $statusAudit->user_name ?? ($docketData['case_status_updated_by'] ?? null);
        $statusUpdatedByUnit = $statusAudit->user_unit ?? ($docketData['case_status_updated_by_unit'] ?? null);
        $statusUpdatedAt = $statusAudit
            ? $statusAudit->created_at->format('d M Y, H:i')
            : ($docketData['case_status_updated_at'] ?? null);

        // 3. Active Suspect & Linked Exhibits
        $activeSuspectData = $this->ownershipResolver->resolveActiveSuspect($case, $docketData, $request->query('suspect'));
        $linkedExhibitsData = $this->exhibitResolver->resolveSuspectLinkedExhibits(
            $case,
            $docketData,
            $activeSuspectData['activeSuspectNameLower'],
            $activeSuspectData['isActiveSuspectPrimary'],
        );
        $suspectExhibitFlags = $this->drugClassifier->classifySuspectExhibits($linkedExhibitsData['suspectLinkedExhibits']);
        $activeSuspectHasDrugExhibit = $suspectExhibitFlags['activeSuspectHasDrugExhibit'];
        $initialActiveSuspectHasDrugExhibit = $activeSuspectHasDrugExhibit;

        // 4. Saved Lab State & Unit Progress
        $reportCaseNo = $case->case_number ?? null;
        $reportCaseId = (!empty($case->is_staff_submission) || empty($case->id)) ? null : $case->id;
        $reportExtraId = (!empty($case->is_staff_submission) || empty($case->underlyingRecord?->id)) ? null : $case->underlyingRecord?->id;

        $allGsaForReview = SuspectGsaReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allPreliminaryForReview = SuspectPreliminaryReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allNlrForReview = SuspectNlrReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allNlrForReview = $allPreliminaryForReview->merge($allNlrForReview)->unique('id')->values();
        $this->mergeDpfReportAttachments($case, $allGsaForReview);
        $hasGsaReports = $allGsaForReview->count() > 0;

        $savedLabFlags = $this->labExamResolver->resolveSuspectLabExamSaved(
            $case,
            $docketData,
            $activeSuspectHasDrugExhibit,
            $activeSuspectData['activeSuspectNameLower'],
            $activeSuspectData['isActiveSuspectPrimary'],
            $isMultiCase,
            $linkedExhibitsData['suspectLinkedExhibits'],
        );

        $unitProgressData = $this->unitProgressService->resolveUnitProgress(
            $case,
            $docketData,
            $activeSuspectData['activeSuspectNameLower'],
            $activeSuspectData['isActiveSuspectPrimary'],
            $savedLabFlags['suspectLabExamSaved'],
            $suspectExhibitFlags['suspectHasDrug'],
            $activeSuspectHasDrugExhibit,
            $suspectExhibitFlags['suspectHasElectronic'],
            $suspectExhibitFlags['suspectHasCrimeScene'],
            $defaultLabHeadName,
            null,
            $hasGsaReports,
            $suspectExhibitFlags['suspectHasWeaponOrVehicle'] ?? false,
        );

        $initialNamesData = $this->ownershipResolver->resolveInitialSuspectNameLists($docketData);
        $currentLoggedInName = $authUser->fullname ?? (session('user_name') ?? ($authUser->fullname ?? ''));

        $topLevelLabData = $this->labExamResolver->resolveTopLevelLabExam(
            $case,
            $docketData,
            $isMultiCase,
            $initialNamesData['primaryAndCoNamesList'],
            $drugCaseData['topDrugExhibits'],
            $drugCaseData['isDrugCase'],
            $savedLabFlags['suspectLabExamSaved'],
            $unitProgressData['unitProgress'],
            $currentLoggedInName,
            $defaultLabHeadName,
        );

        $finalizedUnitProgress = $this->unitProgressService->finalizeUnitProgressAfterLabResolution(
            $unitProgressData['unitProgress'],
            $unitProgressData['unitsList'],
            $case,
            $docketData,
            $savedLabFlags['suspectLabExamSaved'],
            $unitProgressData['suspectHasDrugExhibit'],
            $activeSuspectData['activeSuspectNameLower'],
            $unitProgressData['isCreatorDpf'],
            $topLevelLabData['labAnalyst'],
            $defaultLabHeadName,
            $hasGsaReports,
            $suspectExhibitFlags['suspectHasWeaponOrVehicle'] ?? false,
        );

        $unitColors = [
            'Crime Scene Support' => 'text-blue-600 bg-blue-50 border-blue-200',
            'Evidence Management' => 'text-indigo-600 bg-indigo-50 border-indigo-200',
            'LAB Analysis' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
            'Lab Analysis' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
            'Digital & Physical Forensics' => 'text-amber-600 bg-amber-50 border-amber-200',
            'Digital & Physical Forensic Unit' => 'text-amber-600 bg-amber-50 border-amber-200',
        ];
        $unitBadgeColor = $unitColors[$case->unit_name] ?? 'text-blue-600 bg-blue-50 border-blue-200';

        // 5. Notification Bell Counts (lines 2071-2080)
        $pendingCasesNotif = (int) ForensicCase::whereIn('status', [
            'Awaiting Sign-off',
            'Pending Approval',
            'Pending',
        ])->count();
        $pendingHandoversNotif = (int) CaseHandover::whereNull('acknowledged_at')->count();
        $totalAlertsCount = ($pendingCasesNotif > 0 ? 1 : 0)
            + ($pendingHandoversNotif > 0 ? 1 : 0);

        // 6. Suspects List, Ownership & Case Exhibits (lines 2779-5568)
        $ownershipData = $this->ownershipResolver->resolveSuspectsListAndOwnership($case, $docketData);
        $caseExhibitsData = $this->exhibitResolver->resolveAllCaseExhibits(
            $case,
            $docketData,
            $isMultiCase,
            $ownershipData['primaryAndCoNamesList'],
            $ownershipData['primarySuspectsCombinedNames'],
        );

        // Auto-attach / sync system-generated Preliminary Exhibit Analysis Report exclusively for drug exhibits.
        // Preliminary_Report applies exclusively to drug exhibits. Do not generate if suspect(s) are not associated with any drug exhibits.
        $hasDrugExhibits = collect($caseExhibitsData['allCaseExhibits'] ?? [])->filter(function ($e) {
            return $this->drugClassifier->isDrugExhibit($e);
        })->isNotEmpty();

        $caseHasDrugExhibits = $hasDrugExhibits
            || !empty($drugCaseData['isDrugCase'])
            || !empty($suspectExhibitFlags['suspectHasDrug'])
            || !empty($activeSuspectHasDrugExhibit);

        if ($caseHasDrugExhibits && empty($case->is_staff_submission)) {
            try {
                app(\App\Http\Controllers\CaseReportController::class)->storeOrSyncPreliminaryReport(
                    $case,
                    $topLevelLabData['dbPrimaryLabExam'] ?? null,
                    $docketData
                );
            } catch (\Throwable $e) {
                // Fail gracefully without interrupting show_review view rendering
            }
        }

        $allGsaForReview = SuspectGsaReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allPreliminaryForReview = SuspectPreliminaryReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allNlrForReview = SuspectNlrReport::forCase($reportCaseNo, $reportCaseId, $reportExtraId);
        $allNlrForReview = $allPreliminaryForReview->merge($allNlrForReview)->unique('id')->values();
        $this->mergeDpfReportAttachments($case, $allGsaForReview);

        $suspectsList = $ownershipData['suspectsList'];
        $capturedJointExhibits = [];
        $savedJointLabExam = null;
        $savedJointLabHeadingName = null;
        $renderedSuspectReportKeys = [];

        foreach ($suspectsList as $sIdx => $sItem) {
            $resolvedView = $this->resolveSingleSuspectDossierView(
                $case,
                $docketData,
                $suspectsList,
                $sItem,
                $sIdx,
                $ownershipData['primarySuspectNamesList'],
                $ownershipData['primaryAndCoNamesList'],
                $ownershipData['primarySuspectsCombinedNames'],
                $ownershipData['hasCoSuspectsInCase'],
                $allGsaForReview,
                $allNlrForReview,
                $capturedJointExhibits,
                $savedJointLabExam,
                $savedJointLabHeadingName,
                [
                    'caseCreatorName' => $caseCreatorName,
                    'caseCreatorUnit' => $caseCreatorUnit,
                    'caseCreatedAt' => $caseCreatedAt,
                    'arrestingOfficerName' => $arrestingOfficerName,
                    'statusUpdatedBy' => $statusUpdatedBy,
                    'statusUpdatedByUnit' => $statusUpdatedByUnit,
                    'statusUpdatedAt' => $statusUpdatedAt,
                    'isCaseController' => $isCaseController,
                    'authUserIsHead' => (bool) ($authUser->is_head ?? false),
                    'isLabContext' => (bool) $isLabContext,
                ],
            );
            foreach (($resolvedView['sGsaFiles'] ?? []) as $rf) {
                $renderedSuspectReportKeys[get_class($rf) . '_' . ($rf->id ?? spl_object_id($rf))] = true;
                if (!empty($rf->file_path)) {
                    $renderedSuspectReportKeys['path:' . (string) $rf->file_path] = true;
                }
            }
            $suspectsList[$sIdx]['resolved_view'] = $resolvedView;
        }

        $finalJointSummary = $this->exhibitResolver->resolveFinalJointExhibitsSummary(
            $case,
            $caseExhibitsData['allCaseExhibits'],
            $capturedJointExhibits,
            $ownershipData['primaryAndCoNamesList'],
            $ownershipData['primarySuspectsCombinedNames'],
            $isMultiCase,
            $ownershipData['hasCoSuspectsInCase'],
            $suspectsList,
            $ownershipData['rawSuspects'],
        );

        $savedJointLabExamCard = !empty($savedJointLabExam)
            ? $this->labExamResolver->formatLabExamForCard(
                $savedJointLabExam,
                $case,
                $savedJointLabHeadingName ?? $finalJointSummary['jointDossierHeadingName'],
            )
            : null;

        $savedJointLabExamCards = [];
        $shouldRenderJointLabCards = !empty($savedJointLabExam) || empty($suspectsList);
        if ($shouldRenderJointLabCards) {
            $allJointExams = is_object($case) && method_exists($case, 'resolveAllLabExamsForSuspect')
                ? $case->resolveAllLabExamsForSuspect()
                : (!empty($savedJointLabExam) ? [$savedJointLabExam] : []);

            if (!empty($allJointExams)) {
                foreach ($allJointExams as $jExam) {
                    $savedJointLabExamCards[] = $this->labExamResolver->formatLabExamForCard(
                        $jExam,
                        $case,
                        $savedJointLabHeadingName ?? $finalJointSummary['jointDossierHeadingName'],
                    );
                }
            }
            if (empty($savedJointLabExamCards) && !empty($savedJointLabExamCard)) {
                $savedJointLabExamCards[] = $savedJointLabExamCard;
            }
        }

        $jointCaseReportFiles = $allGsaForReview;
        if (!empty($caseHasDrugExhibits)) {
            $jointCaseReportFiles = $jointCaseReportFiles->merge($allNlrForReview);
        }
        $jointCaseReportFiles = $jointCaseReportFiles
            ->unique(function ($item) {
                return !empty($item->file_path) ? $item->file_path : (get_class($item) . '_' . $item->id);
            })
            ->reject(function ($item) use ($renderedSuspectReportKeys) {
                $idKey = get_class($item) . '_' . ($item->id ?? spl_object_id($item));
                $pathKey = !empty($item->file_path) ? ('path:' . (string) $item->file_path) : null;
                return isset($renderedSuspectReportKeys[$idKey])
                    || ($pathKey !== null && isset($renderedSuspectReportKeys[$pathKey]));
            })
            ->values();

        // 7. Section 5 Lab Analysis Data (resolved for all units so lab examination variables are available everywhere)
        $section5Data = $this->labExamResolver->resolveSection5LabAnalysisData(
            $case,
            $docketData,
            $activeSuspectData['activeSuspectItem'],
            $activeSuspectData['activeSuspectName'],
            $activeSuspectData['activeSuspectNameLower'],
            $activeSuspectHasDrugExhibit,
            $drugCaseData['isDrugCase'],
            $ownershipData['primaryAndCoNamesList'],
            $ownershipData['primarySuspectsCombinedNames'],
            $topLevelLabData,
        );

        // 8. Associate Suspect Lab Dossiers (lines 9138-10092)
        $effectiveLabContext = array_merge($topLevelLabData, $section5Data);
        $associateLabDossiersData = $this->labExamResolver->resolveAssociateLabDossiers(
            $case,
            $docketData,
            $suspectsList,
            $ownershipData['primaryAndCoNamesList'],
            $ownershipData['primarySuspectsCombinedNames'],
            $section5Data['allDrugExhibitsList'] ?? [],
            $effectiveLabContext,
        );

        // 9. Debug script active suspect dossier lab exam (lines 15166-15169)
        $activeSuspectDossierExam = is_object($case) && method_exists($case, 'resolveLabExamForSuspect')
            ? $case->resolveLabExamForSuspect($activeSuspectData['activeSuspectName'] ?? null)
            : [];
        $activeSuspectDossierHasSaved = !empty($activeSuspectDossierExam['has_saved']);

        $this->viewData = array_merge(
            [
                'extractAllPhotosHelper' => $extractAllPhotosHelper,
                'formatPhotoUrlHelper' => $formatPhotoUrlHelper,
                'formatMugshotUrl' => $formatMugshotUrl,
                'getImgSrc' => $getImgSrc,
                'checkIsDrugExhibit' => $checkIsDrugExhibit,
                'authUser' => $authUser,
                'isMultiCase' => $isMultiCase,
                'activeSuspectHasSavedResult' => $activeSuspectHasSavedResult,
                'activeSuspectHasDrugExhibit' => $activeSuspectHasDrugExhibit,
                'initialActiveSuspectHasDrugExhibit' => $initialActiveSuspectHasDrugExhibit,
                'isHof' => $isHof,
                'dbLabHead' => $dbLabHead,
                'defaultLabHeadName' => $defaultLabHeadName,
                'officerName' => $officerName,
                'officerRole' => $officerRole,
                'reqFrom' => $reqFrom,
                'userUnit' => $userUnit,
                'referer' => $referer,
                'activeUnitContext' => $activeUnitContext,
                'isDigitalForensicsRegister' => $isDigitalForensicsRegister,
                'isDigitalForensics' => $isDigitalForensics,
                'isEvidenceManagement' => $isEvidenceManagement,
                'isLabContext' => $isLabContext,
                'isLabAnalysis' => $isLabAnalysis,
                'isCrimeSceneContext' => $isCrimeSceneContext,
                'isForensicHeadContext' => $isForensicHeadContext,
                'status' => $status,
                'priority' => $priority,
                'docketData' => $docketData,
                'controllerUser' => $controllerUser,
                'isCaseController' => $isCaseController,
                'activeHandover' => $activeHandover,
                'currentCaseStatus' => $currentCaseStatus,
                'caseCreatorName' => $caseCreatorName,
                'caseCreatorUnit' => $caseCreatorUnit,
                'caseCreatedAt' => $caseCreatedAt,
                'arrestingOfficerName' => $arrestingOfficerName,
                'statusAudit' => $statusAudit,
                'statusUpdatedBy' => $statusUpdatedBy,
                'statusUpdatedByUnit' => $statusUpdatedByUnit,
                'statusUpdatedAt' => $statusUpdatedAt,
                'currentLoggedInName' => $currentLoggedInName,
                'unitColors' => $unitColors,
                'unitBadgeColor' => $unitBadgeColor,
                'pendingCasesNotif' => $pendingCasesNotif,
                'pendingHandoversNotif' => $pendingHandoversNotif,
                'totalAlertsCount' => $totalAlertsCount,
                'capturedJointExhibits' => $capturedJointExhibits,
                'savedJointLabExam' => $savedJointLabExam,
                'savedJointLabExamCard' => $savedJointLabExamCard,
                'savedJointLabExamCards' => $savedJointLabExamCards,
                'savedJointLabHeadingName' => $savedJointLabHeadingName,
                'allGsaForReview' => $allGsaForReview,
                'allNlrForReview' => $allNlrForReview,
                'jointCaseReportFiles' => $jointCaseReportFiles,
                'activeSuspectDossierExam' => $activeSuspectDossierExam,
                'activeSuspectDossierHasSaved' => $activeSuspectDossierHasSaved,
            ],
            $photoData,
            $drugCaseData,
            $activeSuspectData,
            $linkedExhibitsData,
            $suspectExhibitFlags,
            $savedLabFlags,
            $unitProgressData,
            $initialNamesData,
            $topLevelLabData,
            $finalizedUnitProgress,
            $ownershipData,
            ['suspectsList' => $suspectsList],
            [
                'allCaseExhibits' => $caseExhibitsData['allCaseExhibits'],
                'seenExhibitKeys' => $caseExhibitsData['seenExhibitKeys'],
                'allCaseExhibitPhotos' => $caseExhibitsData['allCaseExhibitPhotos'],
            ],
            $finalJointSummary,
            $section5Data,
            $associateLabDossiersData,
        );

        return $this;
    }

    /**
     * Pre-resolve all suspect-level variables for a single suspect entry in @foreach ($suspectsList as $sIdx => $sItem).
     */
    protected function resolveSingleSuspectDossierView(
        mixed $case,
        array $docketData,
        array $suspectsList,
        array $sItem,
        int $sIdx,
        array $primarySuspectNamesList,
        array $primaryAndCoNamesList,
        string $primarySuspectsCombinedNames,
        bool $hasCoSuspectsInCase,
        mixed $allGsaForReview,
        mixed $allNlrForReview,
        array &$capturedJointExhibits,
        ?array &$savedJointLabExam,
        ?string &$savedJointLabHeadingName,
        array $caseContext = [],
    ): array {
        $sNum = $sItem['suspect_number'] ?? $sIdx + 1;
        $rawSName = $sItem['suspect_name'] ?? 'Suspect #' . $sNum;
        if (str_contains($rawSName, ',')) {
            $sNameParts = array_map('trim', explode(',', $rawSName));
            $sName = $sNameParts[$sIdx] ?? ($sNameParts[0] ?? $rawSName);
        } else {
            $sName = $rawSName;
        }
        $isUnassignedSuspect = ForensicCase::isUnassignedSuspectName($sName)
            || !empty($case->no_suspect)
            || !empty($docketData['no_suspect']);
        $sDocketSuspect = [];
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            if (isset($docketData['suspects'][$sIdx]) && is_array($docketData['suspects'][$sIdx])) {
                $sDocketSuspect = $docketData['suspects'][$sIdx];
            } elseif (isset($docketData['suspects'][$sNum - 1]) && is_array($docketData['suspects'][$sNum - 1])) {
                $sDocketSuspect = $docketData['suspects'][$sNum - 1];
            }
            if (empty($sDocketSuspect)) {
                foreach ($docketData['suspects'] as $ds) {
                    if (
                        is_array($ds) &&
                        !empty($ds['suspect_name']) &&
                        !empty($sName) &&
                        strtolower(trim($ds['suspect_name'])) === strtolower(trim($sName))
                    ) {
                        $sDocketSuspect = $ds;
                        break;
                    }
                }
            }
        }

        $sAlias =
            $sItem['suspect_alias'] ?:
            ($sDocketSuspect['suspect_alias'] ??
                ($sDocketSuspect['alias'] ??
                    ($sNum == 1 ? ($case->suspect_alias ?: ($docketData['alias'] ?? 'N/A')) : 'N/A')));
        if (empty($sAlias)) {
            $sAlias = 'N/A';
        }
        $sCard =
            $sItem['ghana_card'] ?:
            ($sDocketSuspect['ghana_card'] ??
                ($sDocketSuspect['id_card'] ??
                    ($sNum == 1 ? ($case->ghana_card ?: ($docketData['id_card'] ?? 'N/A')) : 'N/A')));
        if (empty($sCard)) {
            $sCard = 'N/A';
        }
        $sGender = $isUnassignedSuspect
            ? ((!empty($sDocketSuspect['gender']) && $sDocketSuspect['gender'] !== 'Male')
                ? $sDocketSuspect['gender']
                : ((!empty($docketData['gender']) && $docketData['gender'] !== 'Male') ? $docketData['gender'] : 'N/A'))
            : ($sItem['gender'] ??
                ($sDocketSuspect['gender'] ??
                    ($sNum == 1 ? ($case->gender ?? ($docketData['gender'] ?? 'N/A')) : 'N/A')));
        if (empty($sGender)) {
            $sGender = 'N/A';
        }
        $sAge = $sItem['age'] ?? ($sDocketSuspect['age'] ?? ($sNum == 1 ? $case->age ?? ($docketData['age'] ?? null) : null));
        $sDOB = $sItem['dob'] ?? ($sDocketSuspect['dob'] ?? ($sNum == 1 ? $case->dob ?? ($docketData['dob'] ?? null) : null));
        $sHeight =
            $sItem['height'] ?? ($sDocketSuspect['height'] ?? ($sNum == 1 ? $case->height ?? ($docketData['height'] ?? null) : null));
        $sEye =
            $sItem['eye_color'] ??
            ($sDocketSuspect['eye_color'] ??
                ($sNum == 1 ? $case->eye_color ?? ($docketData['eye_color'] ?? null) : null));
        $sNat = $isUnassignedSuspect
            ? ((!empty($sDocketSuspect['nationality']) && $sDocketSuspect['nationality'] !== 'Ghanaian')
                ? $sDocketSuspect['nationality']
                : ((!empty($docketData['nationality']) && $docketData['nationality'] !== 'Ghanaian') ? $docketData['nationality'] : 'N/A'))
            : ($sItem['nationality'] ??
                ($sDocketSuspect['nationality'] ??
                    ($sNum == 1
                        ? $case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian')
                        : 'Ghanaian')));
        if (empty($sNat)) {
            $sNat = 'N/A';
        }
        $sPOB =
            $sItem['place_of_birth'] ??
            ($sItem['hometown'] ??
                ($sItem['pob'] ??
                    ($sDocketSuspect['place_of_birth'] ??
                        ($sDocketSuspect['hometown'] ??
                            ($sDocketSuspect['pob'] ??
                                ($sNum == 1 ? $case->pob ?? ($docketData['pob'] ?? 'N/A') : 'N/A'))))));
        if (empty($sPOB)) {
            $sPOB = 'N/A';
        }
        $sPhone =
            $sItem['phone_number'] ??
            ($sItem['contact_phone'] ??
                ($sItem['phone'] ??
                    ($sDocketSuspect['phone_number'] ??
                        ($sDocketSuspect['contact_phone'] ??
                            ($sDocketSuspect['phone'] ??
                                ($sNum == 1 ? $case->phone ?? ($docketData['phone'] ?? 'N/A') : 'N/A'))))));
        $rawPhones = $sItem['phones'] ?? ($sItem['additional_phones'] ?? ($sDocketSuspect['phones'] ?? ($sDocketSuspect['additional_phones'] ?? ($sNum == 1 ? ($case->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? []))) : []))));
        if (is_string($rawPhones)) {
            $rawPhones = json_decode($rawPhones, true) ?: [];
        }
        $sPhones = [];
        if (is_array($rawPhones)) {
            foreach ($rawPhones as $rItem) {
                if (is_array($rItem)) {
                    $rNum = trim((string) ($rItem['number'] ?? ($rItem['phone'] ?? '')));
                    if ($rNum !== '') {
                        $sPhones[] = [
                            'label' => trim((string) ($rItem['label'] ?? 'Alternate')) ?: 'Alternate',
                            'number' => $rNum,
                        ];
                    }
                } elseif (is_string($rItem) && trim($rItem) !== '') {
                    $sPhones[] = [
                        'label' => 'Alternate',
                        'number' => trim($rItem),
                    ];
                }
            }
        }
        if (empty($sPhones) && !empty($sPhone) && $sPhone !== 'N/A' && (str_contains($sPhone, ',') || str_contains($sPhone, '/'))) {
            $parts = array_filter(array_map('trim', preg_split('/[,\/]+/', $sPhone)));
            foreach ($parts as $pIdx => $partNum) {
                $sPhones[] = [
                    'label' => $pIdx === 0 ? 'Primary' : 'Alternate',
                    'number' => $partNum,
                ];
            }
        }
        if (!empty($sPhone) && $sPhone !== 'N/A') {
            $hasPrim = false;
            foreach ($sPhones as $spEntry) {
                if ($spEntry['number'] === $sPhone) {
                    $hasPrim = true;
                    break;
                }
            }
            if (!$hasPrim) {
                array_unshift($sPhones, [
                    'label' => 'Primary',
                    'number' => $sPhone,
                ]);
            }
        }
        $sAddr =
            $sItem['residential_address'] ??
            ($sItem['address'] ??
                ($sItem['gps'] ??
                    ($sItem['residential_address_gps'] ??
                        ($sDocketSuspect['residential_address'] ??
                            ($sDocketSuspect['address'] ??
                                ($sDocketSuspect['gps'] ??
                                    ($sDocketSuspect['residential_address_gps'] ??
                                        ($sNum == 1 ? $case->address ?? ($docketData['address'] ?? 'N/A') : 'N/A'))))))));
        if (empty($sAddr)) {
            $sAddr = 'N/A';
        }
        $sMarital = $sItem['marital_status'] ?? ($sDocketSuspect['marital_status'] ?? ($sNum == 1 ? $case->marital_status ?? ($docketData['marital_status'] ?? null) : null));
        $sSpouseN = $sItem['spouse_name'] ?? ($sDocketSuspect['spouse_name'] ?? ($sNum == 1 ? $case->spouse_name ?? ($docketData['spouse_name'] ?? null) : null));
        $sSpouseP = $sItem['spouse_phone'] ?? ($sDocketSuspect['spouse_phone'] ?? ($sNum == 1 ? $case->spouse_phone ?? ($docketData['spouse_phone'] ?? null) : null));
        $sSpouseI = $sItem['spouse_info'] ?? ($sDocketSuspect['spouse_info'] ?? ($sNum == 1 ? $case->spouse_info ?? ($docketData['spouse_info'] ?? null) : null));
        $sPartnerN = $sItem['partner_name'] ?? ($sDocketSuspect['partner_name'] ?? ($sNum == 1 ? $case->partner_name ?? ($docketData['partner_name'] ?? null) : null));
        $sPartnerP = $sItem['partner_phone'] ?? ($sDocketSuspect['partner_phone'] ?? ($sNum == 1 ? $case->partner_phone ?? ($docketData['partner_phone'] ?? null) : null));
        $sPartnerI = $sItem['partner_info'] ?? ($sDocketSuspect['partner_info'] ?? ($sNum == 1 ? $case->partner_info ?? ($docketData['partner_info'] ?? null) : null));

        $rawDepCandidate = $sItem['dependents']
            ?? ($sDocketSuspect['dependents']
                ?? ($sNum == 1
                    ? ($case->dependents ?? ($docketData['dependents'] ?? ($docketData['suspects'][0]['dependents'] ?? null)))
                    : null));

        $childCountCandidate = $sItem['children_count']
            ?? ($sDocketSuspect['children_count']
                ?? ($sNum == 1
                    ? ($case->children_count ?? ($docketData['children_count'] ?? ($docketData['suspects'][0]['children_count'] ?? null)))
                    : null));

        $hasChildrenCandidate = $sItem['has_children']
            ?? ($sDocketSuspect['has_children']
                ?? ($sNum == 1
                    ? ($case->has_children ?? ($docketData['has_children'] ?? ($docketData['suspects'][0]['has_children'] ?? null)))
                    : null));

        $cleanDep = trim((string) ($rawDepCandidate ?? ''));
        $isDepExplicitKids = !empty($cleanDep) && !in_array(strtoupper($cleanDep), ['N/A', 'NA', '-', 'NULL', 'NONE'], true);

        if ($childCountCandidate !== null && $childCountCandidate !== '' && is_numeric($childCountCandidate)) {
            $numKids = (int) $childCountCandidate;
            if ($numKids > 0) {
                $sDependents = $numKids . ($numKids === 1 ? ' Kid' : ' Kids');
            } elseif (!empty($hasChildrenCandidate) && $hasChildrenCandidate !== false && $hasChildrenCandidate !== 'false') {
                $sDependents = '1 Kid';
            } else {
                $sDependents = $isUnassignedSuspect ? 'N/A' : 'No Children';
            }
        } elseif ($isDepExplicitKids) {
            $sDependents = $cleanDep;
        } elseif (!empty($hasChildrenCandidate) && $hasChildrenCandidate !== false && $hasChildrenCandidate !== 'false') {
            $sDependents = '1 Kid';
        } else {
            $sDependents = $isUnassignedSuspect ? 'N/A' : 'No Children';
        }
        $rawRaidCandidate =
            $sItem['raid_location'] ??
            ($sItem['location'] ??
                ($sDocketSuspect['raid_location'] ??
                    ($sDocketSuspect['location'] ??
                        ($case->raid_location ?: ($case->location ?: ($docketData['raid_location'] ?? null))))));
        $sRaid = ForensicCase::resolveRaidLocationForDisplay(
            is_string($rawRaidCandidate) || is_numeric($rawRaidCandidate) ? (string) $rawRaidCandidate : null,
            $isUnassignedSuspect,
            ForensicCase::extractSeizureLocations($case, $docketData, $sItem)
        );
        $pickArrestValue = static function (array $candidates): ?string {
            foreach ($candidates as $candidate) {
                $val = trim((string) ($candidate ?? ''));
                if ($val !== '' && !in_array(strtoupper($val), ['N/A', 'NA', '-', 'NULL', 'UNSPECIFIED'], true)) {
                    return $val;
                }
            }
            return null;
        };

        $sArrestDate = $pickArrestValue([
            $sItem['date_of_arrest'] ?? null,
            $sItem['arrest_date'] ?? null,
            $sDocketSuspect['date_of_arrest'] ?? null,
            $sDocketSuspect['arrest_date'] ?? null,
            $case->arrest_date ?? null,
            $docketData['arrest_date'] ?? null,
            $docketData['date_of_arrest'] ?? null,
        ]);
        $sArrestTime = $pickArrestValue([
            $sItem['time_of_arrest'] ?? null,
            $sItem['arrest_time'] ?? null,
            $sDocketSuspect['time_of_arrest'] ?? null,
            $sDocketSuspect['arrest_time'] ?? null,
            $case->arrest_time ?? null,
            $docketData['arrest_time'] ?? null,
            $docketData['time_of_arrest'] ?? null,
        ]);
        $sFacility =
            $sItem['holding_facility'] ??
            ($sItem['facility'] ??
                ($sDocketSuspect['holding_facility'] ??
                    ($sDocketSuspect['facility'] ??
                        ($case->holding_facility ?? ($docketData['holding_facility'] ?? null)))));
        if (empty($sFacility) || $sFacility === 'N/A') {
            $sFacility = !empty($case->holding_facility)
                ? $case->holding_facility
                : (!empty($docketData['holding_facility'])
                    ? $docketData['holding_facility']
                    : ($isUnassignedSuspect ? 'N/A' : 'NACOC Custody & Holding Facility'));
        }
        $sOffense =
            $sItem['offense'] ??
            ($sItem['offense_description'] ??
                ($sDocketSuspect['offense'] ??
                    ($sDocketSuspect['offense_description'] ??
                        ($case->offense_description ?:
                            ($case->description ?:
                            ($docketData['offense_description'] ?? 'N/A'))))));
        if (empty($sOffense) || trim((string) $sOffense) === 'Directorate Authorized Forensic Case File') {
            $sOffense = is_object($case) && method_exists($case, 'generateBriefCaseDescription')
                ? $case->generateBriefCaseDescription()
                : 'N/A';
        }

        $pickMugshot = function (...$candidates) {
            foreach ($candidates as $c) {
                if (!empty($c) && is_string($c)) {
                    $cTrim = trim($c);
                    if ($cTrim !== '' && $cTrim !== 'null' && $cTrim !== 'undefined' && !str_ends_with($cTrim, '#') && !str_contains($cTrim, 'blank')) {
                        return $cTrim;
                    }
                }
            }
            return null;
        };

        $rawFront = $pickMugshot(
            $sItem['mugshot_front'] ?? null,
            $sItem['photo'] ?? null,
            $sItem['mugshots']['front'] ?? null,
            $sItem['mugshots']['front-profile'] ?? null,
            $sDocketSuspect['mugshot_front'] ?? null,
            $sDocketSuspect['photo'] ?? null,
            $sDocketSuspect['mugshots']['front'] ?? null,
            $sDocketSuspect['mugshots']['front-profile'] ?? null,
            ($sNum == 1 ? ($case->mugshot_front ?? ($case->photo ?? ($docketData['mugshot_front'] ?? ($docketData['photo'] ?? null)))) : null)
        );
        $sFront = $this->photoResolver->formatMugshotUrl($rawFront);

        $rawLeft = $pickMugshot(
            $sItem['mugshot_left'] ?? null,
            $sItem['mugshots']['left'] ?? null,
            $sItem['mugshots']['left-profile'] ?? null,
            $sDocketSuspect['mugshot_left'] ?? null,
            $sDocketSuspect['mugshots']['left'] ?? null,
            $sDocketSuspect['mugshots']['left-profile'] ?? null,
            ($sNum == 1 ? ($case->mugshot_left ?? ($docketData['mugshot_left'] ?? null)) : null)
        );
        $sLeft = $this->photoResolver->formatMugshotUrl($rawLeft);

        $rawRight = $pickMugshot(
            $sItem['mugshot_right'] ?? null,
            $sItem['mugshots']['right'] ?? null,
            $sItem['mugshots']['right-profile'] ?? null,
            $sDocketSuspect['mugshot_right'] ?? null,
            $sDocketSuspect['mugshots']['right'] ?? null,
            $sDocketSuspect['mugshots']['right-profile'] ?? null,
            ($sNum == 1 ? ($case->mugshot_right ?? ($docketData['mugshot_right'] ?? null)) : null)
        );
        $sRight = $this->photoResolver->formatMugshotUrl($rawRight);

        $isAssociate =
            !empty($sItem['is_associate']) ||
            (!empty($sItem['association_role']) &&
                strtolower($sItem['association_role']) !== 'primary suspect') ||
            (!empty($sItem['role']) && strtolower($sItem['role']) !== 'primary suspect');
        $isCoSuspect =
            $isAssociate &&
            ((!empty($sItem['association_role']) &&
                (str_contains(strtolower($sItem['association_role']), 'co-suspect') ||
                    str_contains(strtolower($sItem['association_role']), 'co suspect'))) ||
                (!empty($sItem['role']) &&
                    (str_contains(strtolower($sItem['role']), 'co-suspect') ||
                        str_contains(strtolower($sItem['role']), 'co suspect'))) ||
                (!empty($sItem['suspect_role']) &&
                    (str_contains(strtolower($sItem['suspect_role']), 'co-suspect') ||
                        str_contains(strtolower($sItem['suspect_role']), 'co suspect'))));
        $sRoleLabel =
            $sItem['association_role'] ??
            ($sItem['role'] ??
                ($isCoSuspect
                    ? 'CO-SUSPECT'
                    : ($isAssociate
                        ? 'ASSOCIATE SUSPECT'
                        : 'PRIMARY SUSPECT')));
        $assocNum = 0;
        if ($isAssociate) {
            foreach ($suspectsList as $cIdx => $cSp) {
                $cAssoc =
                    !empty($cSp['is_associate']) ||
                    (!empty($cSp['association_role']) &&
                        strtolower($cSp['association_role']) !== 'primary suspect');
                if ($cAssoc) {
                    $assocNum++;
                    if ($cIdx === $sIdx) {
                        break;
                    }
                }
            }
        }
        $sId =
            $sItem['associate_id'] ??
            ($sItem['suspect_id'] ??
                ($isAssociate ? 'ASC-' . str_pad($assocNum ?: 1, 3, '0', STR_PAD_LEFT) : 'PRIM-001'));

        $sDispRaw = $sItem['suspect_disposition'] ?? ($case->suspect_disposition ?? ($case->underlyingRecord->suspect_disposition ?? 'Active — Under Investigation'));
        $sDisp = ForensicCase::normalizeSuspectDisposition($sDispRaw);
        $isClearedDisp = $sDisp !== 'Active — Under Investigation';
        $dispBadgeCls = match ($sDisp) {
            'Cleared / No Further Action' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'Released from Case' => 'bg-purple-100 text-purple-800 border-purple-300',
            'Reclassified' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            'Referred for Further Review' => 'bg-blue-100 text-blue-800 border-blue-300',
            default => 'bg-amber-100 text-amber-800 border-amber-300',
        };
        $isMultipleCase = (($case->underlyingRecord ?? null) instanceof MultipleSuspectCase) || count($suspectsList) > 1;

        $sHeaderDrugScreening = DrugScreening::forSuspect(
            $sItem['id'] ?? ($sId ?? null),
            $sName,
            $case->id ?? null,
            $case->case_number ?? null,
        );
        $sScreeningTextCls = $sHeaderDrugScreening
            ? ($sHeaderDrugScreening->isPositive()
                ? 'text-red-600'
                : ($sHeaderDrugScreening->isNegative() ? 'text-emerald-600' : 'text-amber-600'))
            : '';

        $assocExhibits = [];
        $primaryExhibits = [];
        if ($isAssociate) {
            $assocExhibits = is_object($case) && method_exists($case, 'getExhibitsForSuspect')
                ? $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel)
                : [];
            if (empty($assocExhibits)) {
                $directEx =
                    $sItem['exhibits'] ??
                    ($sItem['seizures'] ??
                        ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                if (!empty($directEx) && is_array($directEx)) {
                    $assocExhibits = $directEx;
                }
            }
            if (!empty($assocExhibits)) {
                $assocExhibits = array_values(
                    array_filter($assocExhibits, function ($ex) use ($primarySuspectNamesList) {
                        $owner = '';
                        if (is_array($ex)) {
                            $owner = $ex['associated_person'] ??
                                ($ex['owner_name'] ??
                                    ($ex['owner'] ??
                                        ($ex['suspect_name'] ?? ($ex['associated_suspect'] ?? ''))));
                        } elseif (is_object($ex)) {
                            $owner = $ex->associated_person ??
                                ($ex->owner_name ??
                                    ($ex->owner ??
                                        ($ex->suspect_name ?? ($ex->associated_suspect ?? ''))));
                        }
                        $cleanOwner = strtolower(trim((string) $owner));
                        if (empty($cleanOwner)) {
                            return false;
                        }
                        $isJoint =
                            str_contains($cleanOwner, 'all suspect') ||
                            str_contains($cleanOwner, 'both') ||
                            str_contains($cleanOwner, 'joint') ||
                            str_contains($cleanOwner, ',') ||
                            str_contains($cleanOwner, ';') ||
                            str_contains($cleanOwner, '&') ||
                            str_contains($cleanOwner, ' and ');
                        if (!$isJoint && !empty($primarySuspectNamesList)) {
                            foreach ($primarySuspectNamesList as $pName) {
                                $pNameClean = strtolower(trim($pName));
                                if (!empty($pNameClean) && (str_contains($cleanOwner, $pNameClean) || $cleanOwner === $pNameClean)) {
                                    $isJoint = true;
                                    break;
                                }
                            }
                        }
                        return !$isJoint;
                    }),
                );
            }
        } else {
            $primaryExhibits = is_object($case) && method_exists($case, 'getExhibitsForSuspect')
                ? $case->getExhibitsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel)
                : [];
            if (empty($primaryExhibits)) {
                $directEx =
                    $sItem['exhibits'] ??
                    ($sItem['seizures'] ??
                        ($sItem['associated_seizures'] ?? ($sItem['associate_exhibits'] ?? [])));
                if (!empty($directEx) && is_array($directEx)) {
                    $primaryExhibits = $directEx;
                }
            }
            if (!empty($primaryExhibits) && is_array($primaryExhibits)) {
                foreach ($primaryExhibits as $pEx) {
                    if ($this->ownershipResolver->isJointExhibit($pEx, $primaryAndCoNamesList)) {
                        $capturedJointExhibits[] = $pEx;
                    }
                }
            }
        }

        $currSuspectExhibits = $isAssociate ? $assocExhibits : $primaryExhibits;
        $sDossierLabExam = is_object($case) && method_exists($case, 'resolveLabExamForSuspect')
            ? $case->resolveLabExamForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel, $sIdx)
            : [];
        $sDossierLabExams = is_object($case) && method_exists($case, 'resolveAllLabExamsForSuspect')
            ? $case->resolveAllLabExamsForSuspect($sName, $sItem['id'] ?? null, $sId, $sRoleLabel, $sIdx)
            : (!empty($sDossierLabExam) ? [$sDossierLabExam] : []);
        $sDossierHasSavedLab = !empty($sDossierLabExam['has_saved']) || (!empty($sDossierLabExams) && count($sDossierLabExams) > 0);

        $currSuspectHasDrug = false;
        if (!empty($currSuspectExhibits)) {
            foreach ($currSuspectExhibits as $eCh) {
                if ($this->drugClassifier->isDrugExhibit($eCh)) {
                    $currSuspectHasDrug = true;
                    break;
                }
            }
        }

        if (!$currSuspectHasDrug) {
            $sDossierHasSavedLab = false;
            $sDossierLabExam = null;
            $sDossierLabExams = [];
        }

        $sDossierIsJointLab = false;
        $labExamHeadingName = $sName;
        if ($sDossierHasSavedLab) {
            if (!empty($sDossierLabExam)) {
                $examOwner = strtolower(trim($sDossierLabExam['exhibit_owner_name'] ?? ''));
                if (
                    str_contains($examOwner, ',') ||
                    str_contains($examOwner, '&') ||
                    str_contains($examOwner, 'all suspect') ||
                    str_contains($examOwner, 'both')
                ) {
                    $sDossierIsJointLab = true;
                }
            }
            if (!$isAssociate && !$sDossierIsJointLab && $hasCoSuspectsInCase && !empty($primarySuspectsCombinedNames)) {
                $sDossierIsJointLab = true;
            }
            if (!$isAssociate && !$sDossierIsJointLab && !empty($capturedJointExhibits)) {
                $sDossierIsJointLab = true;
            }
            $labExamHeadingName = $sDossierIsJointLab && !empty($primarySuspectsCombinedNames)
                ? $primarySuspectsCombinedNames
                : $sName;
            if ($sDossierIsJointLab && $sDossierHasSavedLab) {
                $savedJointLabExam = $sDossierLabExam;
                $savedJointLabHeadingName = $labExamHeadingName;
            }
        }

        $sGsaFiles = SuspectGsaReport::filterForSuspect(
            $allGsaForReview,
            $sItem['id'] ?? null,
            $sName,
        );

        // Preliminary_Report applies exclusively to drug exhibits.
        // Only attach if this suspect is associated with drug exhibits.
        if ($currSuspectHasDrug) {
            $sGsaFiles = $sGsaFiles->merge(
                SuspectPreliminaryReport::filterForSuspect(
                    $allNlrForReview,
                    $sItem['id'] ?? null,
                    $sName,
                ),
            );
        }

        $sGsaFiles = $sGsaFiles->unique(function ($item) {
            return !empty($item->file_path) ? $item->file_path : (get_class($item) . '_' . $item->id);
        })->values();

        $sDrugScreening = $sHeaderDrugScreening;

        $assocPhotos = [];
        foreach ($assocExhibits as $aIdx => $aEx) {
            $extractedA = $this->photoResolver->extractAllPhotos($aEx);
            foreach ($extractedA as $ap) {
                if ($ap && !in_array($ap, $assocPhotos)) {
                    $assocPhotos[] = $ap;
                }
            }
            $assocExhibits[$aIdx]['resolved_assoc_view'] = $this->resolveAssociateExhibitView($aEx, $case);
        }
        if (!empty($sItem['evidence_photos'])) {
            $sEvPhotos = is_array($sItem['evidence_photos'])
                ? $sItem['evidence_photos']
                : (json_decode($sItem['evidence_photos'], true) ?: []);
            if (is_array($sEvPhotos)) {
                foreach ($sEvPhotos as $sp) {
                    if ($sp && !in_array($sp, $assocPhotos)) {
                        $assocPhotos[] = $sp;
                    }
                }
            }
        }
        $assocPhotosFormatted = array_map(
            fn($apUrl) => $this->photoResolver->formatPhotoUrl($apUrl),
            $assocPhotos,
        );

        $sDossierLabExamCard = !empty($sDossierLabExam)
            ? $this->labExamResolver->formatLabExamForCard($sDossierLabExam, $case, $labExamHeadingName)
            : [];

        $sDossierLabExamCards = [];
        if (!empty($sDossierLabExams)) {
            foreach ($sDossierLabExams as $exm) {
                $sDossierLabExamCards[] = $this->labExamResolver->formatLabExamForCard($exm, $case, $labExamHeadingName);
            }
        }
        if (empty($sDossierLabExamCards) && !empty($sDossierLabExamCard)) {
            $sDossierLabExamCards[] = $sDossierLabExamCard;
        }

        return array_merge($caseContext, [
            'sDossierLabExams' => $sDossierLabExams,
            'sDossierLabExamCards' => $sDossierLabExamCards,
            'sNum' => $sNum,
            'rawSName' => $rawSName,
            'sName' => $sName,
            'sDocketSuspect' => $sDocketSuspect,
            'sAlias' => $sAlias,
            'sCard' => $sCard,
            'sGender' => $sGender,
            'sAge' => $sAge,
            'sDOB' => $sDOB,
            'sHeight' => $sHeight,
            'sEye' => $sEye,
            'sNat' => $sNat,
            'sPOB' => $sPOB,
            'sPhone' => $sPhone,
            'sPhones' => $sPhones,
            'sAddr' => $sAddr,
            'sMarital' => $sMarital,
            'sSpouseN' => $sSpouseN,
            'sSpouseP' => $sSpouseP,
            'sSpouseI' => $sSpouseI,
            'sPartnerN' => $sPartnerN,
            'sPartnerP' => $sPartnerP,
            'sPartnerI' => $sPartnerI,
            'sDependents' => $sDependents,
            'sRaid' => $sRaid,
            'sArrestDate' => $sArrestDate,
            'sArrestTime' => $sArrestTime,
            'sFacility' => $sFacility,
            'sOffense' => $sOffense,
            'rawFront' => $rawFront,
            'sFront' => $sFront,
            'rawLeft' => $rawLeft,
            'sLeft' => $sLeft,
            'rawRight' => $rawRight,
            'sRight' => $sRight,
            'isAssociate' => $isAssociate,
            'isCoSuspect' => $isCoSuspect,
            'sRoleLabel' => $sRoleLabel,
            'assocNum' => $assocNum,
            'sId' => $sId,
            'sDispRaw' => $sDispRaw,
            'sDisp' => $sDisp,
            'isClearedDisp' => $isClearedDisp,
            'dispBadgeCls' => $dispBadgeCls,
            'isMultipleCase' => $isMultipleCase,
            'sHeaderDrugScreening' => $sHeaderDrugScreening,
            'sScreeningTextCls' => $sScreeningTextCls,
            'assocExhibits' => $assocExhibits,
            'assocPhotos' => $assocPhotos,
            'assocPhotosFormatted' => $assocPhotosFormatted,
            'primaryExhibits' => $primaryExhibits,
            'currSuspectExhibits' => $currSuspectExhibits,
            'sDossierLabExam' => $sDossierLabExam,
            'sDossierLabExamCard' => $sDossierLabExamCard,
            'sDossierHasSavedLab' => $sDossierHasSavedLab,
            'currSuspectHasDrug' => $currSuspectHasDrug,
            'sDossierIsJointLab' => $sDossierIsJointLab,
            'labExamHeadingName' => $labExamHeadingName,
            'sGsaFiles' => $sGsaFiles,
            'sDrugScreening' => $sDrugScreening,
        ]);
    }

    /**
     * Merge Extraction & Investigation Status Log Report attachments if Report & Witness Statement Sent is YES
     */
    protected function mergeDpfReportAttachments(mixed $case, mixed &$allGsaForReview): void
    {
        try {
            $caseNo = $case->case_number ?? null;
            $caseId = $case->id ?? null;
            $dpf = \App\Models\DpfReport::forCase($caseId, $caseNo);
            if ($dpf) {
                $rwSent = strtoupper(trim((string)$dpf->report_witness_statement_sent));
                $rwStatus = strtoupper(trim((string)($dpf->report_data['report_witness_statement_status'] ?? '')));
                $isRwYes = str_starts_with($rwSent, 'YES') || $rwStatus === 'YES';
                if ($isRwYes) {
                    $hasDpfInGsa = $allGsaForReview->contains(function ($item) {
                        return str_contains($item->file_path ?? '', 'dpf-report-pdf')
                            || str_contains($item->original_filename ?? '', 'Extraction & Investigation Status Log Report')
                            || str_contains($item->original_filename ?? '', 'Status Log Report');
                    });
                    if (!$hasDpfInGsa) {
                        $dpfCode = $dpf->dpf_code ?: ($caseNo ?: 'DPF');
                        $syntheticGsa = new \App\Models\SuspectGsaReport([
                            'id'                       => 999000 + (int)$dpf->id,
                            'case_suspect_id'          => $dpf->suspect_id,
                            'single_suspect_case_id'   => $dpf->single_suspect_case_id ?: (($case instanceof \App\Models\SingleSuspectCase || ($case->underlyingRecord ?? null) instanceof \App\Models\SingleSuspectCase) ? ($case->id ?? null) : null),
                            'multiple_suspect_case_id' => $dpf->multiple_suspect_case_id ?: (($case instanceof \App\Models\MultipleSuspectCase || ($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase) ? ($case->id ?? null) : null),
                            'original_filename'        => 'Extraction & Investigation Status Log Report (' . $dpfCode . ').pdf',
                            'stored_filename'          => 'dpf_report_' . $dpf->id . '.pdf',
                            'file_path'                => '/Digital_Forensics/dpf-report-pdf/' . $dpfCode,
                            'file_type'                => 'application/pdf',
                            'file_extension'           => 'pdf',
                            'file_size'                => 0,
                            'exhibit_type'             => 'Digital Forensics',
                            'case_number'              => $dpf->case_number ?: $caseNo,
                            'notes'                    => 'Attached Extraction & Investigation Status Log Report (' . $dpfCode . ')',
                            'status'                   => 'active',
                        ]);
                        $syntheticGsa->created_at = $dpf->created_at ?? now();
                        if ($dpf->suspect_id && $dpf->suspect) {
                            $syntheticGsa->setRelation('suspect', $dpf->suspect);
                        }
                        $allGsaForReview->push($syntheticGsa);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail safely without interrupting presenter
        }
    }

    /**
     * Pre-resolve display fields for a single associate exhibit item.
     */
    protected function resolveAssociateExhibitView(array $aEx, mixed $case): array
    {
        $rawValA = $aEx['seizure_value'] ?? ($aEx['value'] ?? null);
        $cleanValA = !empty($rawValA) && !str_contains($rawValA, 'Item #') && !str_contains($rawValA, "\n")
            ? trim($rawValA)
            : 'N/A';

        $exPhotos = $this->photoResolver->extractAllPhotos($aEx);
        $exPhotosFormatted = array_map(fn($u) => $this->photoResolver->formatPhotoUrl($u), $exPhotos);

        $catLower = strtolower($aEx['category'] ?? ($aEx['exhibit_category'] ?? ''));
        $hasWeaponData =
            !empty($aEx['weapon_model']) ||
            !empty($aEx['weapon_serial_number']) ||
            !empty($aEx['weapon_calibre']) ||
            !empty($aEx['weapon_loaded_status']) ||
            !empty($aEx['weapon_condition_at_recovery']) ||
            !empty($aEx['weapon_location_found']) ||
            !empty($aEx['weapon_distinguishing_marks']);
        $isWeaponEx = str_contains($catLower, 'weapon') || str_contains($catLower, 'firearm') || $hasWeaponData;

        $hasVehicleData =
            !empty($aEx['veh_make']) ||
            !empty($aEx['veh_model']) ||
            !empty($aEx['veh_reg_no']) ||
            !empty($aEx['veh_vin']) ||
            !empty($aEx['veh_colour']) ||
            !empty($aEx['veh_type']) ||
            !empty($aEx['vehicle_make_model']) ||
            !empty($aEx['vehicle_registration']) ||
            !empty($aEx['vehicle_chassis_vin']);
        $isVehEx = str_contains($catLower, 'vehicle') || str_contains($catLower, 'vessel') || str_contains($catLower, 'automobile') || $hasVehicleData;

        $hasDeviceData =
            !empty($aEx['dev_model']) ||
            !empty($aEx['dev']['model']) ||
            !empty($aEx['dev_serial_no']) ||
            !empty($aEx['dev']['serial_no']) ||
            !empty($aEx['dev_imei1']) ||
            !empty($aEx['dev_imei_1']) ||
            !empty($aEx['dev']['imei1']) ||
            !empty($aEx['dev_imei2']) ||
            !empty($aEx['dev_imei_2']) ||
            !empty($aEx['dev']['imei2']) ||
            !empty($aEx['dev_mac_address']) ||
            !empty($aEx['dev']['mac_address']) ||
            !empty($aEx['dev_storage']) ||
            !empty($aEx['dev']['storage']) ||
            !empty($aEx['storage_capacity']) ||
            !empty($aEx['dev_storage_capacity']) ||
            !empty($aEx['storage']) ||
            !empty($aEx['dev_colour']) ||
            !empty($aEx['dev']['colour']) ||
            !empty($aEx['dev_color']) ||
            !empty($aEx['colour']) ||
            !empty($aEx['color']) ||
            !empty($aEx['device_color']) ||
            !empty($aEx['dev_marks']) ||
            !empty($aEx['dev']['marks']) ||
            !empty($aEx['dev_other_marks']) ||
            !empty($aEx['marks']) ||
            !empty($aEx['other_marks']) ||
            !empty($aEx['distinguishing_marks']) ||
            !empty($aEx['device_model']) ||
            !empty($aEx['device_serial']) ||
            !empty($case->dev_model) ||
            !empty($case->dev_serial_no) ||
            !empty($case->dev_imei1) ||
            !empty($case->dev_imei2) ||
            !empty($case->dev_mac_address) ||
            !empty($case->dev_storage) ||
            !empty($case->dev_colour) ||
            !empty($case->dev_marks);
        $isDevEx = str_contains($catLower, 'device') || str_contains($catLower, 'electronic') || str_contains($catLower, 'digital') || $hasDeviceData;

        $vMake = !empty($aEx['veh_make']) ? $aEx['veh_make'] : null;
        $vModel = !empty($aEx['veh_model']) ? $aEx['veh_model'] : null;
        $vMakeModelFull = implode(' ', array_filter([$vMake, $vModel]));
        if (empty($vMakeModelFull)) {
            $vMakeModelFull = $aEx['vehicle_make_model'] ?? ($aEx['seizure_name'] ?? ($aEx['commodity_name'] ?? ($aEx['name'] ?? 'N/A')));
        }
        $vDate = !empty($aEx['seizure_date']) ? $aEx['seizure_date'] : null;
        $vTime = !empty($aEx['seizure_time']) ? "@ {$aEx['seizure_time']}" : '';

        $aAmmo = $aEx['ammunition_details'] ?? null;
        if (is_string($aAmmo)) {
            $aAmmo = json_decode($aAmmo, true) ?: [];
        }
        $isAmmoEx = str_contains($catLower, 'ammo') || str_contains($catLower, 'ammunition') || (!empty($aAmmo) && is_array($aAmmo) && array_filter($aAmmo));

        $resolveDevField = function(...$vals) {
            foreach ($vals as $v) {
                if ($v !== null && !is_array($v) && !is_object($v) && trim((string)$v) !== '') return trim((string)$v);
            }
            return 'N/A';
        };

        return [
            'cleanValA' => $cleanValA,
            'exPhotos' => $exPhotos,
            'exPhotosFormatted' => $exPhotosFormatted,
            'isWeaponEx' => $isWeaponEx,
            'isVehEx' => $isVehEx,
            'isDevEx' => $isDevEx,
            'isAmmoEx' => $isAmmoEx,
            'aAmmo' => $aAmmo,
            'vMakeModelFull' => $vMakeModelFull,
            'vRegNo' => $aEx['veh_reg_no'] ?? ($aEx['vehicle_registration'] ?? ($aEx['registration_number'] ?? 'N/A')),
            'vVin' => $aEx['veh_vin'] ?? ($aEx['vehicle_chassis_vin'] ?? ($aEx['chassis_number'] ?? 'N/A')),
            'vType' => $aEx['veh_type'] ?? ($aEx['vehicle_type'] ?? 'Vehicle Exhibit'),
            'vColor' => $aEx['veh_colour'] ?? ($aEx['vehicle_color_type'] ?? 'N/A'),
            'vYear' => $aEx['veh_year'] ?? ($aEx['vehicle_year'] ?? 'N/A'),
            'vEngine' => $aEx['veh_engine_no'] ?? ($aEx['engine_number'] ?? 'N/A'),
            'vCountry' => $aEx['veh_country_reg'] ?? ($aEx['country_of_registration'] ?? 'N/A'),
            'vOwner' => $aEx['veh_owner'] ?? ($aEx['vehicle_owner'] ?? 'N/A'),
            'vDriver' => $aEx['veh_driver'] ?? ($aEx['vehicle_driver'] ?? 'N/A'),
            'vOwnerContact' => $aEx['veh_owner_contact'] ?? ($aEx['veh']['owner_contact'] ?? ($aEx['owner_contact'] ?? null)),
            'vDocsAvailable' => $aEx['veh_docs_available'] ?? ($aEx['veh']['docs_available'] ?? ($aEx['docs_available'] ?? null)),
            'vDocRef' => $aEx['veh_doc_ref'] ?? ($aEx['veh']['doc_ref'] ?? ($aEx['doc_ref'] ?? null)),
            'vCondition' => (isset($aEx['veh_condition']) && is_string($aEx['veh_condition'])) ? $aEx['veh_condition'] : ((isset($aEx['veh']['condition']) && is_string($aEx['veh']['condition'])) ? $aEx['veh']['condition'] : ((isset($aEx['overall_condition']) && is_string($aEx['overall_condition'])) ? $aEx['overall_condition'] : null)),
            'vFuel' => (isset($aEx['veh_fuel']) && is_string($aEx['veh_fuel'])) ? $aEx['veh_fuel'] : ((isset($aEx['veh']['fuel']) && is_string($aEx['veh']['fuel'])) ? $aEx['veh']['fuel'] : ((isset($aEx['fuel_level']) && is_string($aEx['fuel_level'])) ? $aEx['fuel_level'] : null)),
            'vKeysRecv' => (isset($aEx['veh_keys_recv']) && is_string($aEx['veh_keys_recv'])) ? $aEx['veh_keys_recv'] : ((isset($aEx['veh']['keys_recv']) && is_string($aEx['veh']['keys_recv'])) ? $aEx['veh']['keys_recv'] : ((isset($aEx['keys_received']) && is_string($aEx['keys_received'])) ? $aEx['keys_received'] : null)),
            'vKeyCount' => (isset($aEx['veh_key_count']) && is_string($aEx['veh_key_count'])) ? $aEx['veh_key_count'] : ((isset($aEx['veh']['key_count']) && is_string($aEx['veh']['key_count'])) ? $aEx['veh']['key_count'] : ((isset($aEx['key_count']) && is_string($aEx['key_count'])) ? $aEx['key_count'] : null)),
            'vDamage' => (isset($aEx['veh_damage']) && is_string($aEx['veh_damage'])) ? $aEx['veh_damage'] : ((isset($aEx['veh']['damage']) && is_string($aEx['veh']['damage'])) ? $aEx['veh']['damage'] : ((isset($aEx['existing_damage']) && is_string($aEx['existing_damage'])) ? $aEx['existing_damage'] : null)),
            'vMods' => (isset($aEx['veh_mods']) && is_string($aEx['veh_mods'])) ? $aEx['veh_mods'] : ((isset($aEx['veh']['mods']) && is_string($aEx['veh']['mods'])) ? $aEx['veh']['mods'] : ((isset($aEx['modifications']) && is_string($aEx['modifications'])) ? $aEx['modifications'] : null)),
            'vFeatures' => (isset($aEx['veh_features']) && is_string($aEx['veh_features'])) ? $aEx['veh_features'] : ((isset($aEx['veh']['features']) && is_string($aEx['veh']['features'])) ? $aEx['veh']['features'] : ((isset($aEx['notable_features']) && is_string($aEx['notable_features'])) ? $aEx['notable_features'] : null)),
            'vLoc' => (isset($aEx['seizure_location']) && is_string($aEx['seizure_location'])) ? $aEx['seizure_location'] : ((isset($aEx['location']) && is_string($aEx['location'])) ? $aEx['location'] : 'N/A'),
            'vOfficer' => (isset($aEx['seizure_officer']) && is_string($aEx['seizure_officer'])) ? $aEx['seizure_officer'] : ((isset($aEx['officer']) && is_string($aEx['officer'])) ? $aEx['officer'] : 'N/A'),
            'vDateTime' => !empty($vDate) ? "{$vDate} {$vTime}" : 'N/A',
            'vReason' => (isset($aEx['seizure_reason']) && is_string($aEx['seizure_reason'])) ? $aEx['seizure_reason'] : 'N/A',
            'vPhotos' => $exPhotos,
            'vPhotosFormatted' => $exPhotosFormatted,
            'wModel' => (isset($aEx['weapon_model']) && is_string($aEx['weapon_model'])) ? $aEx['weapon_model'] : ((isset($aEx['wpn_model']) && is_string($aEx['wpn_model'])) ? $aEx['wpn_model'] : ((isset($aEx['seizure_name']) && is_string($aEx['seizure_name'])) ? $aEx['seizure_name'] : 'Firearm / Weapon Exhibit')),
            'wSerial' => (isset($aEx['weapon_serial_number']) && is_string($aEx['weapon_serial_number'])) ? $aEx['weapon_serial_number'] : ((isset($aEx['wpn_serial_no']) && is_string($aEx['wpn_serial_no'])) ? $aEx['wpn_serial_no'] : ((isset($aEx['weapon_serial']) && is_string($aEx['weapon_serial'])) ? $aEx['weapon_serial'] : null)),
            'wCalibre' => (isset($aEx['weapon_calibre']) && is_string($aEx['weapon_calibre'])) ? $aEx['weapon_calibre'] : ((isset($aEx['wpn_calibre']) && is_string($aEx['wpn_calibre'])) ? $aEx['wpn_calibre'] : null),
            'wColour' => (isset($aEx['weapon_color']) && is_string($aEx['weapon_color'])) ? $aEx['weapon_color'] : ((isset($aEx['wpn_colour']) && is_string($aEx['wpn_colour'])) ? $aEx['wpn_colour'] : null),
            'wSize' => (isset($aEx['weapon_size_length']) && is_string($aEx['weapon_size_length'])) ? $aEx['weapon_size_length'] : ((isset($aEx['wpn_size']) && is_string($aEx['wpn_size'])) ? $aEx['wpn_size'] : null),
            'wLoaded' => (isset($aEx['weapon_loaded_status']) && is_string($aEx['weapon_loaded_status'])) ? $aEx['weapon_loaded_status'] : ((isset($aEx['wpn_loaded_status']) && is_string($aEx['wpn_loaded_status'])) ? $aEx['wpn_loaded_status'] : null),
            'wCondition' => (isset($aEx['weapon_condition_at_recovery']) && is_string($aEx['weapon_condition_at_recovery'])) ? $aEx['weapon_condition_at_recovery'] : ((isset($aEx['wpn_condition']) && is_string($aEx['wpn_condition'])) ? $aEx['wpn_condition'] : null),
            'wLocation' => (isset($aEx['weapon_location_found']) && is_string($aEx['weapon_location_found'])) ? $aEx['weapon_location_found'] : ((isset($aEx['wpn_location_found']) && is_string($aEx['wpn_location_found'])) ? $aEx['wpn_location_found'] : null),
            'wMarks' => (isset($aEx['weapon_distinguishing_marks']) && is_string($aEx['weapon_distinguishing_marks'])) ? $aEx['weapon_distinguishing_marks'] : ((isset($aEx['wpn_marks']) && is_string($aEx['wpn_marks'])) ? $aEx['wpn_marks'] : null),
            'wPhotos' => $exPhotos,
            'wPhotosFormatted' => $exPhotosFormatted,
            'dModel' => $resolveDevField(
                $aEx['dev_model'] ?? null,
                $aEx['dev']['model'] ?? null,
                $aEx['device_model'] ?? null,
                $aEx['model'] ?? null,
                $case->dev_model ?? null,
                $aEx['seizure_name'] ?? null,
                $aEx['commodity_name'] ?? null
            ),
            'dSerial' => $resolveDevField(
                $aEx['dev_serial_no'] ?? null,
                $aEx['dev']['serial_no'] ?? null,
                $aEx['device_serial'] ?? null,
                $aEx['serial_no'] ?? null,
                $aEx['serial_number'] ?? null,
                $case->dev_serial_no ?? null
            ),
            'dImei1' => $resolveDevField(
                $aEx['dev_imei1'] ?? null,
                $aEx['dev']['imei1'] ?? null,
                $aEx['dev_imei_1'] ?? null,
                $aEx['imei1'] ?? null,
                $aEx['imei_1'] ?? null,
                $case->dev_imei1 ?? null
            ),
            'dImei2' => $resolveDevField(
                $aEx['dev_imei2'] ?? null,
                $aEx['dev']['imei2'] ?? null,
                $aEx['dev_imei_2'] ?? null,
                $aEx['imei2'] ?? null,
                $aEx['imei_2'] ?? null,
                $case->dev_imei2 ?? null
            ),
            'dMac' => $resolveDevField(
                $aEx['dev_mac_address'] ?? null,
                $aEx['dev']['mac_address'] ?? null,
                $aEx['mac_address'] ?? null,
                $case->dev_mac_address ?? null
            ),
            'dStorage' => $resolveDevField(
                $aEx['dev_storage'] ?? null,
                $aEx['dev']['storage'] ?? null,
                $aEx['storage_capacity'] ?? null,
                $aEx['dev_storage_capacity'] ?? null,
                $aEx['storage'] ?? null,
                $case->dev_storage ?? null
            ),
            'dColor' => $resolveDevField(
                $aEx['dev_colour'] ?? null,
                $aEx['dev']['colour'] ?? null,
                $aEx['dev_color'] ?? null,
                $aEx['colour'] ?? null,
                $aEx['color'] ?? null,
                $aEx['device_color'] ?? null,
                $case->dev_colour ?? null
            ),
            'dMarks' => $resolveDevField(
                $aEx['dev_marks'] ?? null,
                $aEx['dev']['marks'] ?? null,
                $aEx['dev_other_marks'] ?? null,
                $aEx['marks'] ?? null,
                $aEx['other_marks'] ?? null,
                $aEx['distinguishing_marks'] ?? null,
                $case->dev_marks ?? null
            ),
        ];
    }

    public function toViewData(): array
    {
        return $this->viewData;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->viewData[$key] ?? $default;
    }

    public function __get(string $name): mixed
    {
        return $this->viewData[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->viewData[$name]);
    }
}

