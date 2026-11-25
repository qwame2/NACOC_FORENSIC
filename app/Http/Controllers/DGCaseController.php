<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\ForensicCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Models\User;
use App\Policies\ForensicCasePolicy;
use App\Services\CaseAnalyticsService;
use App\Services\CaseShowReviewPresenter;
use Illuminate\Http\Request;

class DGCaseController extends Controller
{
    use CaseControllerHelpers;

    public function dashboard(Request $request, CaseAnalyticsService $analyticsService)
    {
        $cases = ForensicCase::allCases();
        $pendingCasesCount = $cases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
        $pendingCount = User::where('is_approved', false)->count();
        $mapConfig = $analyticsService->buildLeafletMapPayload($cases);

        return view('DG.dashboard', compact('cases', 'pendingCasesCount', 'pendingCount', 'mapConfig'));
    }

    public function cases(Request $request)
    {
        $allCases = ForensicCase::allCases();

        $search   = trim((string) $request->input('search', ''));
        $status   = trim((string) $request->input('status', ''));
        $unit     = trim((string) $request->input('unit', ''));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo   = trim((string) $request->input('date_to', ''));

        $fromCarbon = null;
        $toCarbon = null;
        if ($dateFrom !== '') {
            try { $fromCarbon = \Carbon\Carbon::parse($dateFrom)->startOfDay(); } catch (\Throwable $e) {}
        }
        if ($dateTo !== '') {
            try { $toCarbon = \Carbon\Carbon::parse($dateTo)->endOfDay(); } catch (\Throwable $e) {}
        }

        $filtered = $allCases->filter(function ($case) use ($search, $status, $unit, $fromCarbon, $toCarbon) {
            $matchSearch = true;
            $matchStatus = true;
            $matchUnit = true;
            $matchDate = true;

            if ($search !== '') {
                $q = strtolower($search);
                $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                $suspects = !empty($dData['suspects']) && is_array($dData['suspects']) ? $dData['suspects'] : [];
                $suspectNames = array_column($suspects, 'suspect_name');
                $suspectAliases = array_column($suspects, 'suspect_alias');

                $haystack = strtolower(implode(' ', array_filter([
                    $case->case_number ?? '',
                    $case->title ?? '',
                    $case->suspect_name ?? '',
                    $case->suspect_alias ?? '',
                    $case->submitting_officer ?? '',
                    $case->unit_name ?? '',
                    $case->priority ?? '',
                    $case->status ?? '',
                    $case->case_status ?? '',
                    implode(' ', $suspectNames),
                    implode(' ', $suspectAliases),
                ])));
                $matchSearch = str_contains($haystack, $q);
            }

            if ($status !== '' && strtolower($status) !== 'all') {
                $cStat = strtolower((string) ($case->case_status ?? ''));
                $stat = strtolower((string) ($case->status ?? ''));
                $sLower = strtolower($status);
                if (str_contains($sLower, 'await') || str_contains($sLower, 'pending')) {
                    $matchStatus = (str_contains($cStat, 'await') || str_contains($cStat, 'pending') || str_contains($stat, 'await') || str_contains($stat, 'pending'));
                } elseif (str_contains($sLower, 'court') || str_contains($sLower, 'approv') || str_contains($sLower, 'authoriz')) {
                    $matchStatus = (str_contains($cStat, 'court') || str_contains($cStat, 'approv') || str_contains($cStat, 'completed') || str_contains($stat, 'court') || str_contains($stat, 'approv'));
                } elseif (str_contains($sLower, 'vault')) {
                    $hasEx = (method_exists($case, 'hasExhibits') && $case->hasExhibits());
                    $matchStatus = (str_contains($cStat, 'vault') || str_contains(strtolower($case->unit_name ?? ''), 'evidence') || str_contains($stat, 'vault') || $hasEx);
                } elseif (str_contains($sLower, 'lab')) {
                    $isDrug = (method_exists($case, 'isDrugCase') && $case->isDrugCase());
                    if (!$isDrug && method_exists($case, 'classifyExhibits')) {
                        $flags = $case->classifyExhibits();
                        $isDrug = !empty($flags['has_drug']);
                    }
                    if (!$isDrug) {
                        $txtCheck = strtolower(($case->title ?? '') . ' ' . ($case->exhibits_summary ?? '') . ' ' . ($case->commodity_name ?? '') . ' ' . ($case->substance_name ?? ''));
                        foreach (['cocaine', 'heroin', 'cannabis', 'weed', 'meth', 'substance', 'narcotic', 'drug', 'powder', 'organic powder', 'white powder', 'crystal'] as $kw) {
                            if (str_contains($txtCheck, $kw)) {
                                $isDrug = true;
                                break;
                            }
                        }
                    }
                    $hasReport = false;
                    try {
                        $hasReport = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id)->exists();
                    } catch (\Throwable $e) {
                        $hasReport = false;
                    }
                    $matchStatus = (str_contains($cStat, 'lab') || str_contains($stat, 'lab') || str_contains(strtolower($case->unit_name ?? ''), 'lab') || $isDrug || $hasReport);
                } elseif (str_contains($sLower, 'investigat')) {
                    $matchStatus = (str_contains($cStat, 'investigat') || str_contains($stat, 'investigat') || str_contains($cStat, 'progress') || str_contains($stat, 'progress') || str_contains($cStat, 'multi-unit') || str_contains($stat, 'multi-unit') || str_contains($cStat, 'active') || str_contains($stat, 'active'));
                } else {
                    $matchStatus = ($cStat === strtolower($status) || $stat === strtolower($status) || str_contains($cStat, $sLower) || str_contains($stat, $sLower));
                }
            }

            if ($unit !== '' && strtolower($unit) !== 'all') {
                $uLower = strtolower($unit);
                $cUnit = strtolower((string) ($case->unit_name ?? ''));
                if (str_contains($uLower, 'digital') || str_contains($uLower, 'physical') || str_contains($uLower, 'dpf')) {
                    $isDigital = (method_exists($case, 'isDigitalCase') && $case->isDigitalCase());
                    if (!$isDigital && method_exists($case, 'classifyExhibits')) {
                        $flags = $case->classifyExhibits();
                        $isDigital = !empty($flags['has_electronic']);
                    }
                    if (!$isDigital) {
                        try {
                            $isDigital = \App\Models\DpfReport::forCase($case->id, $case->case_number) !== null;
                        } catch (\Throwable $e) {}
                    }
                    $matchUnit = str_contains($cUnit, 'digital') || str_contains($cUnit, 'physical') || str_contains($cUnit, 'dpf') || $isDigital;
                } elseif (str_contains($uLower, 'crime') || str_contains($uLower, 'scene') || $uLower === 'css') {
                    $matchUnit = str_contains($cUnit, 'crime') || str_contains($cUnit, 'scene') || $uLower === 'css';
                } elseif (str_contains($uLower, 'evidence')) {
                    $matchUnit = str_contains($cUnit, 'evidence');
                } elseif (str_contains($uLower, 'lab')) {
                    $isDrug = method_exists($case, 'isDrugCase') && $case->isDrugCase();
                    $matchUnit = str_contains($cUnit, 'lab') || $isDrug;
                } else {
                    $matchUnit = str_contains($cUnit, $uLower);
                }
            }

            if ($fromCarbon || $toCarbon) {
                $candidateDates = [];
                if (!empty($case->created_at)) {
                    try { $candidateDates[] = $case->created_at instanceof \Carbon\Carbon ? $case->created_at : \Carbon\Carbon::parse($case->created_at); } catch (\Throwable $e) {}
                }
                if (!empty($case->incident_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->incident_date); } catch (\Throwable $e) {}
                }
                if (!empty($case->arrest_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->arrest_date); } catch (\Throwable $e) {}
                }
                if (!empty($case->seizure_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->seizure_date); } catch (\Throwable $e) {}
                }

                if (empty($candidateDates)) {
                    $matchDate = false;
                } else {
                    $anyMatched = false;
                    foreach ($candidateDates as $cDate) {
                        $inRange = true;
                        if ($fromCarbon && $cDate->lt($fromCarbon)) $inRange = false;
                        if ($toCarbon && $cDate->gt($toCarbon)) $inRange = false;
                        if ($inRange) {
                            $anyMatched = true;
                            break;
                        }
                    }
                    $matchDate = $anyMatched;
                }
            }

            return $matchSearch && $matchStatus && $matchUnit && $matchDate;
        });

        $pendingCasesCount = $allCases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();
        $pendingCount = User::where('is_approved', false)->count();

        $perPage = (int) $request->input('per_page', 25);
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 100) $perPage = 100;
        $cases = ForensicCase::paginateCases($filtered->values(), $perPage);

        if ($request->ajax()) {
            return view('DG.partials.cases_table', compact('cases', 'allCases', 'status', 'dateFrom', 'dateTo'));
        }

        return view('DG.cases', compact('cases', 'pendingCasesCount', 'pendingCount', 'allCases', 'status', 'dateFrom', 'dateTo'));
    }

    public function showReview(Request $request, $id, CaseShowReviewPresenter $presenter, ForensicCasePolicy $policy)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        if (!$policy->viewDGReview($request->user(), $case) && !$policy->viewForensicHeadReview($request->user(), $case)) {
            abort(403, 'Unauthorized access: Restricted to Supreme Command & Directorate Heads.');
        }

        $this->repairExhibitAssociations($case);
        $gsaReports = SuspectGsaReport::forCase($case->case_number ?: $id, $case->id);
        $nlrReports = SuspectNlrReport::forCase($case->case_number ?: $id, $case->id);
        $presenter = $presenter->presentForUnit($case, 'dg', $request, $request->user());

        return view('DG.show_review', compact('case', 'gsaReports', 'nlrReports', 'presenter'));
    }

    public function showVaultExhibits(Request $request, $id, CaseShowReviewPresenter $presenter, ForensicCasePolicy $policy)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        if (!$policy->viewDGReview($request->user(), $case) && !$policy->viewForensicHeadReview($request->user(), $case)) {
            abort(403, 'Unauthorized access: Restricted to Supreme Command & Directorate Heads.');
        }

        $this->repairExhibitAssociations($case);
        $detailedExhibits = method_exists($case, 'getDetailedSuspectExhibits') ? $case->getDetailedSuspectExhibits() : [];
        $caseExhibits = method_exists($case, 'getAllCaseExhibits') ? $case->getAllCaseExhibits() : [];

        $isDrugCase = method_exists($case, 'isDrugCase') && $case->isDrugCase();
        $hasPrelimReport = false;
        try {
            $hasPrelimReport = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id)->exists();
        } catch (\Throwable $e) {}

        $backUrl = url('/DG/cases?status=Vault+Storage');
        $reviewUrl = url('/DG/cases/' . $case->case_number . '/review');
        $role = 'dg';

        return view('vault_storage.show', compact('case', 'detailedExhibits', 'caseExhibits', 'isDrugCase', 'hasPrelimReport', 'backUrl', 'reviewUrl', 'role'));
    }

    public function suspectReportPreview(Request $request)
    {
        $allCases = ForensicCase::allCases();

        $search       = trim((string) $request->input('search', ''));
        $status       = trim((string) $request->input('status', ''));
        $unit         = trim((string) $request->input('unit', ''));
        $dateFrom     = trim((string) $request->input('date_from', ''));
        $dateTo       = trim((string) $request->input('date_to', ''));
        $caseNumber   = trim((string) $request->input('case_number', ''));

        $fromCarbon = null;
        $toCarbon = null;
        if ($dateFrom !== '') {
            try { $fromCarbon = \Carbon\Carbon::parse($dateFrom)->startOfDay(); } catch (\Throwable $e) {}
        }
        if ($dateTo !== '') {
            try { $toCarbon = \Carbon\Carbon::parse($dateTo)->endOfDay(); } catch (\Throwable $e) {}
        }

        $filtered = $allCases->filter(function ($case) use ($search, $status, $unit, $fromCarbon, $toCarbon, $caseNumber) {
            if ($caseNumber !== '') {
                return strcasecmp((string) $case->case_number, $caseNumber) === 0;
            }

            $matchSearch = true;
            $matchStatus = true;
            $matchUnit = true;
            $matchDate = true;

            if ($search !== '') {
                $q = strtolower($search);
                $cData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                $suspects = !empty($cData['suspects']) && is_array($cData['suspects']) ? $cData['suspects'] : [];
                $suspectNames = array_column($suspects, 'suspect_name');
                $suspectAliases = array_column($suspects, 'suspect_alias');

                $haystack = strtolower(implode(' ', array_filter([
                    $case->case_number ?? '',
                    $case->title ?? '',
                    $case->suspect_name ?? '',
                    $case->suspect_alias ?? '',
                    $case->submitting_officer ?? '',
                    $case->unit_name ?? '',
                    $case->priority ?? '',
                    $case->status ?? '',
                    $case->case_status ?? '',
                    implode(' ', $suspectNames),
                    implode(' ', $suspectAliases),
                ])));
                $matchSearch = str_contains($haystack, $q);
            }

            if ($status !== '' && strtolower($status) !== 'all') {
                $cStat = strtolower((string) ($case->case_status ?? ''));
                $stat = strtolower((string) ($case->status ?? ''));
                $sLower = strtolower($status);
                if (str_contains($sLower, 'await') || str_contains($sLower, 'pending')) {
                    $matchStatus = (str_contains($cStat, 'await') || str_contains($cStat, 'pending') || str_contains($stat, 'await') || str_contains($stat, 'pending'));
                } elseif (str_contains($sLower, 'court') || str_contains($sLower, 'approv') || str_contains($sLower, 'authoriz')) {
                    $matchStatus = (str_contains($cStat, 'court') || str_contains($cStat, 'approv') || str_contains($cStat, 'completed') || str_contains($stat, 'court') || str_contains($stat, 'approv'));
                } elseif (str_contains($sLower, 'vault')) {
                    $hasEx = (method_exists($case, 'hasExhibits') && $case->hasExhibits());
                    $matchStatus = (str_contains($cStat, 'vault') || str_contains(strtolower($case->unit_name ?? ''), 'evidence') || str_contains($stat, 'vault') || $hasEx);
                } elseif (str_contains($sLower, 'lab')) {
                    $isDrug = (method_exists($case, 'isDrugCase') && $case->isDrugCase());
                    if (!$isDrug && method_exists($case, 'classifyExhibits')) {
                        $flags = $case->classifyExhibits();
                        $isDrug = !empty($flags['has_drug']);
                    }
                    if (!$isDrug) {
                        $txtCheck = strtolower(($case->title ?? '') . ' ' . ($case->exhibits_summary ?? '') . ' ' . ($case->commodity_name ?? '') . ' ' . ($case->substance_name ?? ''));
                        foreach (['cocaine', 'heroin', 'cannabis', 'weed', 'meth', 'substance', 'narcotic', 'drug', 'powder', 'organic powder', 'white powder', 'crystal'] as $kw) {
                            if (str_contains($txtCheck, $kw)) {
                                $isDrug = true;
                                break;
                            }
                        }
                    }
                    $matchStatus = (str_contains($cStat, 'lab') || str_contains($stat, 'lab') || str_contains(strtolower($case->unit_name ?? ''), 'lab') || $isDrug);
                } elseif (str_contains($sLower, 'investigat')) {
                    $matchStatus = (str_contains($cStat, 'investigat') || str_contains($stat, 'investigat') || str_contains($cStat, 'progress') || str_contains($stat, 'progress') || str_contains($cStat, 'multi-unit') || str_contains($stat, 'multi-unit') || str_contains($cStat, 'active') || str_contains($stat, 'active'));
                } else {
                    $matchStatus = ($cStat === strtolower($status) || $stat === strtolower($status) || str_contains($cStat, $sLower) || str_contains($stat, $sLower));
                }
            }

            if ($unit !== '' && strtolower($unit) !== 'all') {
                $uLower = strtolower($unit);
                $cUnit = strtolower((string) ($case->unit_name ?? ''));
                if (str_contains($uLower, 'digital') || str_contains($uLower, 'physical') || str_contains($uLower, 'dpf')) {
                    $isDigital = (method_exists($case, 'isDigitalCase') && $case->isDigitalCase());
                    if (!$isDigital && method_exists($case, 'classifyExhibits')) {
                        $flags = $case->classifyExhibits();
                        $isDigital = !empty($flags['has_electronic']);
                    }
                    if (!$isDigital) {
                        try {
                            $isDigital = \App\Models\DpfReport::forCase($case->id, $case->case_number) !== null;
                        } catch (\Throwable $e) {}
                    }
                    $matchUnit = str_contains($cUnit, 'digital') || str_contains($cUnit, 'physical') || str_contains($cUnit, 'dpf') || $isDigital;
                } elseif (str_contains($uLower, 'crime') || str_contains($uLower, 'scene') || $uLower === 'css') {
                    $matchUnit = str_contains($cUnit, 'crime') || str_contains($cUnit, 'scene') || $uLower === 'css';
                } elseif (str_contains($uLower, 'evidence')) {
                    $matchUnit = str_contains($cUnit, 'evidence');
                } elseif (str_contains($uLower, 'lab')) {
                    $isDrug = method_exists($case, 'isDrugCase') && $case->isDrugCase();
                    $matchUnit = str_contains($cUnit, 'lab') || $isDrug;
                } else {
                    $matchUnit = str_contains($cUnit, $uLower);
                }
            }

            if ($fromCarbon || $toCarbon) {
                $candidateDates = [];
                if (!empty($case->created_at)) {
                    try { $candidateDates[] = $case->created_at instanceof \Carbon\Carbon ? $case->created_at : \Carbon\Carbon::parse($case->created_at); } catch (\Throwable $e) {}
                }
                if (!empty($case->incident_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->incident_date); } catch (\Throwable $e) {}
                }
                if (!empty($case->arrest_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->arrest_date); } catch (\Throwable $e) {}
                }
                if (!empty($case->seizure_date)) {
                    try { $candidateDates[] = \Carbon\Carbon::parse($case->seizure_date); } catch (\Throwable $e) {}
                }

                if (empty($candidateDates)) {
                    $matchDate = false;
                } else {
                    $anyMatched = false;
                    foreach ($candidateDates as $cDate) {
                        $inRange = true;
                        if ($fromCarbon && $cDate->lt($fromCarbon)) $inRange = false;
                        if ($toCarbon && $cDate->gt($toCarbon)) $inRange = false;
                        if ($inRange) {
                            $anyMatched = true;
                            break;
                        }
                    }
                    $matchDate = $anyMatched;
                }
            }

            return $matchSearch && $matchStatus && $matchUnit && $matchDate;
        });

        $compiledSuspects = [];
        $photoResolver = app(\App\Services\CasePhotoResolver::class);

        $cleanStatusDisplay = function ($val, $default = 'NO') {
            $str = trim((string)($val ?: $default));
            if ($str === '') {
                return $default;
            }

            if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.*)$/is', $str, $matches)) {
                $status = strtoupper(trim($matches[1]));
                $msg = trim($matches[2]);

                $msg = preg_replace('/^' . preg_quote($status, '/') . '\s*[-–—:]\s*/i', '', $msg);
                $msg = trim($msg);

                if ($msg === '' || strtoupper($msg) === $status || in_array(strtoupper($msg), ['N/A', 'NONE', 'NIL', 'NULL', '-', 'NO', 'YES'])) {
                    return $status;
                }

                return $status . ' - ' . $msg;
            }

            if (in_array(strtoupper($str), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                return strtoupper($str);
            }

            if (preg_match('/^(YES|NO)\s+\1$/i', $str, $matches)) {
                return strtoupper($matches[1]);
            }

            return $str;
        };

        foreach ($filtered as $case) {
            $isDrugCase = (method_exists($case, 'isDrugCase') && $case->isDrugCase())
                || str_contains(strtolower($case->unit_name ?? ''), 'lab');
            if (!$isDrugCase && method_exists($case, 'classifyExhibits')) {
                $flags = $case->classifyExhibits();
                $isDrugCase = !empty($flags['has_drug']);
            }

            $isCaseElectronic = (method_exists($case, 'isDigitalCase') && $case->isDigitalCase())
                || str_contains(strtolower($case->unit_name ?? ''), 'digital')
                || str_contains(strtolower($case->unit_name ?? ''), 'physical forensic')
                || str_contains(strtolower($case->unit_name ?? ''), 'dpf');
            if (!$isCaseElectronic && method_exists($case, 'classifyExhibits')) {
                $cFlags = $case->classifyExhibits();
                $isCaseElectronic = !empty($cFlags['has_electronic']);
            }

            $suspects = method_exists($case, 'getSuspectsList') ? $case->getSuspectsList() : [];
            $exhibits = method_exists($case, 'getDetailedSuspectExhibits') ? $case->getDetailedSuspectExhibits() : [];
            $dbOffenseDescription = $case->offense_description
                ?? ($case->underlyingRecord->offense_description ?? null)
                ?? ($case->description ?? null);

            if (empty($suspects)) {
                $suspects = [[
                    'suspect_name'        => $case->suspect_name ?? 'Unassigned Target',
                    'suspect_alias'       => $case->suspect_alias ?? 'N/A',
                    'gender'              => $case->gender ?? 'N/A',
                    'age'                 => $case->age ?? null,
                    'nationality'         => $case->nationality ?? 'Ghanaian',
                    'ghana_card'          => $case->ghana_card ?? null,
                    'phone'               => $case->phone ?? null,
                    'status'              => $case->status ?? ($case->case_status ?? 'Active'),
                    'suspect_disposition' => $case->status ?? ($case->case_status ?? 'Active'),
                    'holding_facility'    => $case->holding_facility ?? 'NACOC Custody Facility',
                    'raid_location'       => $case->raid_location ?? ($case->location ?? 'N/A'),
                    'date_of_arrest'      => $case->arrest_date ?? ($case->incident_date ?? null),
                    'arrest_date'         => $case->arrest_date ?? ($case->incident_date ?? null),
                    'offense_description' => $dbOffenseDescription,
                    'mugshot_front'       => $case->mugshot_front ?? null,
                    'is_associate'        => false,
                ]];
            }

            foreach ($suspects as $sIdx => $sItem) {
                $sName = $sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1));
                $sBriefFacts = !empty($sItem['offense_description']) ? $sItem['offense_description'] : ($dbOffenseDescription ?: 'N/A');

                $suspectId = $sItem['id'] ?? ($sItem['suspect_id'] ?? null);
                $caseId = $case->id ?? null;
                $caseNo = $case->case_number ?? null;

                $suspectDpfReport = null;
                if (!empty($suspectId)) {
                    $suspectDpfReport = \App\Models\DpfReport::where(function ($q) use ($caseId, $caseNo) {
                        if ($caseId) {
                            $q->where('single_suspect_case_id', $caseId)
                              ->orWhere('multiple_suspect_case_id', $caseId);
                        }
                        if ($caseNo) {
                            $q->orWhere('case_number', $caseNo);
                        }
                    })->where('suspect_id', $suspectId)->orderByDesc('id')->first();
                }

                if (!$suspectDpfReport && (!empty($caseId) || !empty($caseNo))) {
                    $suspectDpfReport = \App\Models\DpfReport::forCase($caseId, $caseNo);
                }

                $suspectDpfSpecs = null;
                if ($suspectDpfReport && ($suspectDpfReport->status === 'active' || empty($suspectDpfReport->status))) {
                    $hasProcessData = !empty($suspectDpfReport->court_order_requested)
                        || !empty($suspectDpfReport->court_order_granted)
                        || !empty($suspectDpfReport->extraction_analysis_conducted)
                        || !empty($suspectDpfReport->report_witness_statement_sent)
                        || !empty($suspectDpfReport->intel_shared)
                        || !empty($suspectDpfReport->dpf_code);

                    if ($hasProcessData) {
                        $suspectDpfSpecs = [
                            'dpf_code' => $suspectDpfReport->dpf_code ?: ('DPF-' . ($caseNo ?: 'N/A')),
                            'court_order_requested' => strtoupper(trim((string)($suspectDpfReport->court_order_requested ?: 'NO'))),
                            'court_order_granted' => strtoupper(trim((string)($suspectDpfReport->court_order_granted ?: 'NO'))),
                            'court_order_files_count' => count($suspectDpfReport->court_order_files ?? []),
                            'extraction_analysis_conducted' => $cleanStatusDisplay($suspectDpfReport->extraction_analysis_message ?: ($suspectDpfReport->extraction_analysis_conducted ?: 'NO')),
                            'report_witness_statement_sent' => $cleanStatusDisplay($suspectDpfReport->report_witness_statement_message ?: ($suspectDpfReport->report_witness_statement_sent ?: 'NO')),
                            'report_witness_files_count' => count($suspectDpfReport->report_witness_statement_files ?? []),
                            'intel_shared' => strtoupper(trim((string)($suspectDpfReport->intel_shared ?: 'NO'))),
                            'remark' => !empty(trim((string)($suspectDpfReport->remark ?? ''))) ? trim((string)$suspectDpfReport->remark) : null,
                        ];
                    }
                }

                $matchingExhibits = [];
                if (count($suspects) === 1) {
                    $matchingExhibits = $exhibits;
                } else {
                    foreach ($exhibits as $ex) {
                        $exSuspect = strtolower(trim((string)($ex['suspect_name'] ?? '')));
                        $isAll = in_array($exSuspect, ['', 'all', 'all suspects', 'all targets', 'joint', 'shared', 'case exhibit']);
                        if ($isAll || str_contains($exSuspect, strtolower(trim($sName))) || str_contains(strtolower(trim($sName)), $exSuspect)) {
                            $matchingExhibits[] = $ex;
                        }
                    }
                    if (empty($matchingExhibits) && $sIdx === 0) {
                        $matchingExhibits = $exhibits;
                    }
                }

                // Ensure commodity_name and details are accurately fetched from the exhibits table
                $cDocketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                $docketSeizures = $cDocketData['seizures'] ?? [];
                foreach ($matchingExhibits as &$mEx) {
                    if (!empty($mEx['id'])) {
                        $dbEx = \App\Models\Exhibit::find($mEx['id']);
                        if ($dbEx) {
                            if (!empty($dbEx->commodity_name)) $mEx['commodity_name'] = $dbEx->commodity_name;
                            if (empty($mEx['packaging']) || $mEx['packaging'] === 'N/A') $mEx['packaging'] = $dbEx->seizure_packaging ?: 'Official Sealed Evidence Bag';
                            if (empty($mEx['concealment']) || $mEx['concealment'] === 'N/A') $mEx['concealment'] = $dbEx->seizure_concealment ?: 'N/A';
                            if (empty($mEx['estimated_value']) || $mEx['estimated_value'] === 'N/A') $mEx['estimated_value'] = $dbEx->seizure_value ?: 'N/A';
                            if (empty($mEx['quantity_or_weight']) || $mEx['quantity_or_weight'] === 'N/A' || $mEx['quantity_or_weight'] === '1 Unit') {
                                $dbQty = $dbEx->seizure_quantity ?: ($dbEx->amount ?: '');
                                $dbWt = $dbEx->seizure_weight ?: '';
                                $dbUn = $dbEx->seizure_unit ?: '';
                                $calcDbQty = $dbWt ? trim($dbWt . ' ' . $dbUn) : ($dbQty ?: null);
                                if ($calcDbQty) {
                                    $mEx['quantity_or_weight'] = $calcDbQty;
                                }
                            }
                        }
                    } elseif (!empty($mEx['exhibit_number'])) {
                        $dbEx = \App\Models\Exhibit::where('exhibit_number', $mEx['exhibit_number'])->first();
                        if ($dbEx) {
                            if (!empty($dbEx->commodity_name)) $mEx['commodity_name'] = $dbEx->commodity_name;
                            if (empty($mEx['packaging']) || $mEx['packaging'] === 'N/A') $mEx['packaging'] = $dbEx->seizure_packaging ?: 'Official Sealed Evidence Bag';
                            if (empty($mEx['concealment']) || $mEx['concealment'] === 'N/A') $mEx['concealment'] = $dbEx->seizure_concealment ?: 'N/A';
                            if (empty($mEx['estimated_value']) || $mEx['estimated_value'] === 'N/A') $mEx['estimated_value'] = $dbEx->seizure_value ?: 'N/A';
                        }
                    }

                    if (empty($mEx['quantity_or_weight']) || $mEx['quantity_or_weight'] === 'N/A' || $mEx['quantity_or_weight'] === '1 Unit') {
                        foreach ($docketSeizures as $ds) {
                            $dsName = $ds['commodity_name'] ?? ($ds['seizure_name_drug'] ?? ($ds['name'] ?? ''));
                            $mExName = $mEx['commodity_name'] ?? ($mEx['name'] ?? '');
                            if (strtolower(trim($dsName)) === strtolower(trim($mExName)) || empty($mExName)) {
                                $rawQty = $ds['quantity'] ?? ($ds['seizure_quantity'] ?? null);
                                $rawWt = $ds['seizure_weight'] ?? ($ds['weight'] ?? null);
                                $rawUnit = $ds['seizure_unit'] ?? ($ds['unit'] ?? '');
                                $calcQty = $rawQty ?: ($rawWt ? trim($rawWt . ' ' . $rawUnit) : null);
                                if ($calcQty) {
                                    $mEx['quantity_or_weight'] = $calcQty;
                                    $mEx['quantity'] = $calcQty;
                                }
                                if ((empty($mEx['estimated_value']) || $mEx['estimated_value'] === 'N/A') && !empty($ds['estimated_value'])) {
                                    $mEx['estimated_value'] = $ds['estimated_value'];
                                }
                                if ((empty($mEx['packaging']) || $mEx['packaging'] === 'N/A') && !empty($ds['packaging'])) {
                                    $mEx['packaging'] = $ds['packaging'];
                                }
                                if ((empty($mEx['concealment']) || $mEx['concealment'] === 'N/A') && !empty($ds['concealment'])) {
                                    $mEx['concealment'] = $ds['concealment'];
                                }
                                break;
                            }
                        }
                    }

                    // If case is not drug-related, completely strip laboratory findings & lab numbers
                    if (!$isDrugCase) {
                        $mEx['lab'] = [];
                        $mEx['lab_specs'] = [];
                        $mEx['laboratory_number'] = null;
                        $mEx['lab_exhibit_no'] = null;
                    }

                    // Check if this individual exhibit is electronic-related
                    $isExElectronic = false;
                    $exCat = strtolower((string)($mEx['category'] ?? ($mEx['exhibit_category'] ?? ($mEx['exhibit_type'] ?? ''))));
                    if (str_contains($exCat, 'electronic') || str_contains($exCat, 'device') || !empty($mEx['device']) || !empty($mEx['electronic_specs'])) {
                        $isExElectronic = true;
                    }
                    if (!$isExElectronic && !empty($mEx['id'])) {
                        $chkDbEx = \App\Models\Exhibit::find($mEx['id']);
                        if ($chkDbEx && method_exists($chkDbEx, 'isElectronicDeviceRelated') && $chkDbEx->isElectronicDeviceRelated()) {
                            $isExElectronic = true;
                        }
                    }
                    if ($isExElectronic) {
                        $isCaseElectronic = true;
                    }

                    // Attach Legal & Operational Process Status if completed by DPF Unit
                    if ($isExElectronic || $isCaseElectronic) {
                        $exDpfSpecs = $suspectDpfSpecs;
                        if (!$exDpfSpecs && !empty($mEx['id'])) {
                            $dbExItem = \App\Models\Exhibit::find($mEx['id']);
                            if ($dbExItem && method_exists($dbExItem, 'resolveAttachedDpfReport')) {
                                $attachedDpf = $dbExItem->resolveAttachedDpfReport();
                                if (!empty($attachedDpf['id'])) {
                                    $exDpfModel = \App\Models\DpfReport::find($attachedDpf['id']);
                                    if ($exDpfModel && ($exDpfModel->status === 'active' || empty($exDpfModel->status))) {
                                        $exDpfSpecs = [
                                            'dpf_code' => $exDpfModel->dpf_code ?: ('DPF-' . ($caseNo ?: 'N/A')),
                                            'court_order_requested' => strtoupper(trim((string)($exDpfModel->court_order_requested ?: 'NO'))),
                                            'court_order_granted' => strtoupper(trim((string)($exDpfModel->court_order_granted ?: 'NO'))),
                                            'court_order_files_count' => count($exDpfModel->court_order_files ?? []),
                                            'extraction_analysis_conducted' => $cleanStatusDisplay($exDpfModel->extraction_analysis_message ?: ($exDpfModel->extraction_analysis_conducted ?: 'NO')),
                                            'report_witness_statement_sent' => $cleanStatusDisplay($exDpfModel->report_witness_statement_message ?: ($exDpfModel->report_witness_statement_sent ?: 'NO')),
                                            'report_witness_files_count' => count($exDpfModel->report_witness_statement_files ?? []),
                                            'intel_shared' => strtoupper(trim((string)($exDpfModel->intel_shared ?: 'NO'))),
                                            'remark' => !empty(trim((string)($exDpfModel->remark ?? ''))) ? trim((string)$exDpfModel->remark) : null,
                                        ];
                                    }
                                }
                            }
                        }
                        $mEx['dpf_process_status'] = $exDpfSpecs;
                    } else {
                        $mEx['dpf_process_status'] = null;
                    }

                    // Sanitize nested spec arrays to strings to prevent htmlspecialchars() array errors
                    foreach (['lab', 'vehicle', 'device', 'weapon', 'details'] as $specGroup) {
                        if (!empty($mEx[$specGroup]) && is_array($mEx[$specGroup])) {
                            foreach ($mEx[$specGroup] as $sk => $sv) {
                                if (is_array($sv)) {
                                    $mEx[$specGroup][$sk] = implode(', ', array_filter(array_map('strval', $sv)));
                                }
                            }
                        }
                    }
                }
                unset($mEx);

                $cDocketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                $frontPic = $sItem['mugshot_front']
                    ?? ($sItem['photo']
                    ?? ($sItem['mugshots']['front']
                    ?? ($sItem['mugshots']['front-profile']
                    ?? ($sIdx === 0 ? ($case->mugshot_front ?? ($case->photo ?? ($cDocketData['mugshot_front'] ?? ($cDocketData['photo'] ?? ($cDocketData['mugshots']['front'] ?? null))))) : null))));
                if (!$frontPic && $sIdx === 0 && !empty($case->evidence_photos)) {
                    $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                    if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                }
                $frontPic = $photoResolver->formatMugshotUrl($frontPic);

                $compiledSuspects[] = [
                    'suspect'           => $sItem,
                    'suspect_name'      => $sName,
                    'suspect_alias'     => !empty($sItem['suspect_alias']) ? $sItem['suspect_alias'] : 'N/A',
                    'gender'            => !empty($sItem['gender']) ? $sItem['gender'] : 'N/A',
                    'age'               => !empty($sItem['age']) ? $sItem['age'] : 'N/A',
                    'nationality'       => !empty($sItem['nationality']) ? $sItem['nationality'] : 'Ghanaian',
                    'ghana_card'        => !empty($sItem['ghana_card']) ? $sItem['ghana_card'] : 'N/A',
                    'phone'             => !empty($sItem['phone']) ? $sItem['phone'] : 'N/A',
                    'disposition'       => !empty($sItem['suspect_disposition']) ? $sItem['suspect_disposition'] : (!empty($sItem['status']) ? $sItem['status'] : 'Active Custody'),
                    'facility'          => !empty($sItem['holding_facility']) ? $sItem['holding_facility'] : ($case->holding_facility ?? 'NACOC Custodial Facility'),
                    'location'          => !empty($sItem['raid_location']) ? $sItem['raid_location'] : ($case->raid_location ?? ($case->location ?? 'N/A')),
                    'arrest_date'       => !empty($sItem['arrest_date']) ? $sItem['arrest_date'] : (!empty($sItem['date_of_arrest']) ? $sItem['date_of_arrest'] : ($case->arrest_date ?? ($case->incident_date ?? 'N/A'))),
                    'mugshot_front'     => $frontPic,
                    'case_number'       => $case->case_number ?? 'N/A',
                    'case_title'        => $case->title ?? 'N/A',
                    'priority'          => $case->priority ?? 'HIGH',
                    'unit_name'         => $case->unit_name ?? 'Forensic Department',
                    'submitting_officer' => $case->submitting_officer ?? 'N/A',
                    'brief_facts'       => $sBriefFacts,
                    'is_drug_case'      => $isDrugCase,
                    'laboratory_number' => $isDrugCase ? ($case->laboratory_number ?? null) : null,
                    'is_electronic_case'=> $isCaseElectronic,
                    'dpf_process_status'=> $isCaseElectronic ? $suspectDpfSpecs : null,
                    'dpf_code'          => $isCaseElectronic ? ($suspectDpfSpecs['dpf_code'] ?? null) : null,
                    'exhibits'          => $matchingExhibits,
                ];
            }
        }

        // --------------------------------------------------------------------
        // EXECUTIVE ANALYTICAL SUMMARY DATA (FOR EXECUTIVE ESSAY SECTION)
        // --------------------------------------------------------------------
        $totalCases = $filtered->count();
        $totalSuspects = count($compiledSuspects);

        $singleSuspectCases = 0;
        $singleSuspectCount = 0;
        $multiSuspectCases = 0;
        $multiSuspectCount = 0;

        $monthStats = [];
        $locationStats = [];
        $uniqueExhibits = [];
        $commodityCounts = [];
        $currencyTotals = [];

        foreach ($filtered as $case) {
            $cSuspects = method_exists($case, 'getSuspectsList') ? $case->getSuspectsList() : [];
            $cCount = !empty($cSuspects) ? count($cSuspects) : 1;
            if ($cCount > 1) {
                $multiSuspectCases++;
                $multiSuspectCount += $cCount;
            } else {
                $singleSuspectCases++;
                $singleSuspectCount += 1;
            }

            // Month & Priority Breakdown
            $cDate = $case->incident_date ?: ($case->arrest_date ?: ($case->seizure_date ?: $case->created_at));
            $mLabel = $cDate ? \Carbon\Carbon::parse($cDate)->format('F Y') : 'Unspecified Period';
            if (!isset($monthStats[$mLabel])) {
                $monthStats[$mLabel] = ['high' => 0, 'low' => 0, 'medium' => 0, 'total' => 0];
            }
            $p = strtoupper(trim((string)($case->priority ?? 'MEDIUM')));
            if (in_array($p, ['HIGH', 'CRITICAL', 'URGENT'])) {
                $monthStats[$mLabel]['high']++;
            } elseif (in_array($p, ['LOW', 'ROUTINE', 'MINOR'])) {
                $monthStats[$mLabel]['low']++;
            } else {
                $monthStats[$mLabel]['medium']++;
            }
            $monthStats[$mLabel]['total']++;

            // Incident Scene / Raid Location Breakdown
            $loc = trim((string)($case->raid_location ?: ($case->location ?: ($case->offense_location ?: ''))));
            if (empty($loc) || in_array(strtolower($loc), ['n/a', 'none', 'unknown', 'null'])) {
                $loc = 'Unspecified Location';
            }
            $locationStats[$loc] = ($locationStats[$loc] ?? 0) + 1;
        }

        // Gender breakdown & Exhibit aggregation from compiled suspects
        $maleCount = 0;
        $femaleCount = 0;
        $unspecifiedGenderCount = 0;

        foreach ($compiledSuspects as $cs) {
            $g = strtolower(trim((string)($cs['gender'] ?? '')));
            if (str_starts_with($g, 'm')) {
                $maleCount++;
            } elseif (str_starts_with($g, 'f')) {
                $femaleCount++;
            } else {
                $unspecifiedGenderCount++;
            }
        }

        foreach ($filtered as $case) {
            $caseExhibits = method_exists($case, 'getAllCaseExhibits') ? $case->getAllCaseExhibits() : (method_exists($case, 'getDetailedSuspectExhibits') ? $case->getDetailedSuspectExhibits() : []);
            foreach ($caseExhibits as $exItem) {
                $exKey = !empty($exItem['id']) ? ('id_' . $exItem['id']) : (!empty($exItem['exhibit_number']) ? ('num_' . $exItem['exhibit_number']) : ('name_' . ($exItem['commodity_name'] ?? ($exItem['name'] ?? 'exhibit')) . '_' . ($case->case_number ?? '')));
                if (!isset($uniqueExhibits[$exKey])) {
                    $uniqueExhibits[$exKey] = $exItem;
                    $commName = !empty($exItem['commodity_name']) && $exItem['commodity_name'] !== 'Unnamed Property' ? $exItem['commodity_name'] : (!empty($exItem['name']) && $exItem['name'] !== 'Unnamed Property' ? $exItem['name'] : ($exItem['category'] ?? 'General Seized Property'));
                    $commodityCounts[$commName] = ($commodityCounts[$commName] ?? 0) + 1;

                    // Check for monetary seizures directly from the exhibit amount column in database
                    $dbExhibit = !empty($exItem['id']) ? \App\Models\Exhibit::find($exItem['id']) : (!empty($exItem['exhibit_number']) ? \App\Models\Exhibit::where('exhibit_number', $exItem['exhibit_number'])->first() : null);

                    $rawAmt = !empty($dbExhibit->amount) ? trim((string)$dbExhibit->amount) : (!empty($exItem['amount']) ? trim((string)$exItem['amount']) : '');

                    if ($rawAmt !== '') {
                        $numVal = floatval(preg_replace('/[^\d.]/', '', $rawAmt));
                        if ($numVal > 0) {
                            $fullExText = ($commName . ' ' .
                                ($exItem['category'] ?? '') . ' ' .
                                ($dbExhibit->commodity_name ?? '') . ' ' .
                                ($dbExhibit->exhibit_category ?? '') . ' ' .
                                ($dbExhibit->exhibit_type ?? '') . ' ' .
                                $rawAmt);

                            $cur = 'GHS';
                            if (stripos($rawAmt, 'USD') !== false || stripos($rawAmt, '$') !== false || stripos($fullExText, 'usd') !== false || stripos($fullExText, 'dollar') !== false) {
                                $cur = 'USD';
                            } elseif (stripos($rawAmt, 'EUR') !== false || stripos($rawAmt, '€') !== false || stripos($fullExText, 'euro') !== false) {
                                $cur = 'EUR';
                            } elseif (stripos($rawAmt, 'GBP') !== false || stripos($rawAmt, '£') !== false || stripos($fullExText, 'pound') !== false) {
                                $cur = 'GBP';
                            }
                            $currencyTotals[$cur] = ($currencyTotals[$cur] ?? 0) + $numVal;
                        }
                    }
                }
            }
        }

        // Determine Peak and Low priority months
        $hasMultipleMonths = count($monthStats) > 1;
        $highestPriorityMonth = null;
        $highestPriorityCount = 0;
        $lowestPriorityMonth = null;
        $lowestPriorityCount = 0;

        $totalHighPriorityCases = 0;
        $totalLowPriorityCases = 0;
        $totalMediumPriorityCases = 0;
        foreach ($monthStats as $mCounts) {
            $totalHighPriorityCases += $mCounts['high'];
            $totalLowPriorityCases += $mCounts['low'];
            $totalMediumPriorityCases += $mCounts['medium'];
        }

        if ($hasMultipleMonths) {
            foreach ($monthStats as $mName => $mCounts) {
                if ($highestPriorityMonth === null || $mCounts['high'] > $highestPriorityCount) {
                    $highestPriorityCount = $mCounts['high'];
                    $highestPriorityMonth = $mName;
                }
                if ($lowestPriorityMonth === null || $mCounts['low'] < $lowestPriorityCount) {
                    $lowestPriorityCount = $mCounts['low'];
                    $lowestPriorityMonth = $mName;
                }
            }
        } else {
            $onlyMonth = !empty($monthStats) ? array_key_first($monthStats) : 'Current Period';
            $highestPriorityMonth = $onlyMonth;
            $highestPriorityCount = !empty($monthStats) ? ($monthStats[$onlyMonth]['high'] ?? 0) : 0;
            $lowestPriorityMonth = $onlyMonth;
            $lowestPriorityCount = !empty($monthStats) ? ($monthStats[$onlyMonth]['low'] ?? 0) : 0;
        }

        // Determine top incident location
        arsort($locationStats);
        $topLocation = !empty($locationStats) ? array_key_first($locationStats) : 'Not Specified';
        $topLocationCount = !empty($locationStats) ? current($locationStats) : 0;
        $secondaryLocations = array_slice(array_keys($locationStats), 1, 4);

        // Determine most seized exhibit
        arsort($commodityCounts);
        $mostSeizedExhibit = !empty($commodityCounts) ? array_key_first($commodityCounts) : 'None Cataloged';
        $mostSeizedExhibitCount = !empty($commodityCounts) ? current($commodityCounts) : 0;
        $totalSeizedExhibitsCount = count($uniqueExhibits);

        // Calculate gender percentages
        $malePct = $totalSuspects > 0 ? round(($maleCount / $totalSuspects) * 100, 1) : 0;
        $femalePct = $totalSuspects > 0 ? round(($femaleCount / $totalSuspects) * 100, 1) : 0;

        $summaryData = [
            'totalCases'                 => $totalCases,
            'totalSuspects'              => $totalSuspects,
            'singleSuspectCases'         => $singleSuspectCases,
            'singleSuspectCount'         => $singleSuspectCount,
            'multiSuspectCases'          => $multiSuspectCases,
            'multiSuspectCount'          => $multiSuspectCount,
            'maleCount'                  => $maleCount,
            'femaleCount'                => $femaleCount,
            'unspecifiedGenderCount'     => $unspecifiedGenderCount,
            'malePct'                    => $malePct,
            'femalePct'                  => $femalePct,
            'hasMultipleMonths'          => $hasMultipleMonths,
            'totalHighPriorityCases'     => $totalHighPriorityCases,
            'totalLowPriorityCases'      => $totalLowPriorityCases,
            'totalMediumPriorityCases'   => $totalMediumPriorityCases,
            'monthStats'                 => $monthStats,
            'highestPriorityMonth'       => $highestPriorityMonth,
            'highestPriorityCount'       => $highestPriorityCount,
            'lowestPriorityMonth'        => $lowestPriorityMonth,
            'lowestPriorityCount'        => $lowestPriorityCount,
            'topLocation'                => $topLocation,
            'topLocationCount'           => $topLocationCount,
            'secondaryLocations'        => $secondaryLocations,
            'totalSeizedExhibitsCount'   => $totalSeizedExhibitsCount,
            'mostSeizedExhibit'          => $mostSeizedExhibit,
            'mostSeizedExhibitCount'     => $mostSeizedExhibitCount,
            'commodityCounts'            => $commodityCounts,
            'currencyTotals'             => $currencyTotals,
        ];

        $dateRangeDisplay = 'COMPLETE REGISTER &bull; ALL RECORDED DATES';
        if ($dateFrom !== '' && $dateTo !== '') {
            $dateRangeDisplay = 'PERIOD: ' . date('d M Y', strtotime($dateFrom)) . ' — ' . date('d M Y', strtotime($dateTo));
        } elseif ($dateFrom !== '') {
            $dateRangeDisplay = 'FROM: ' . date('d M Y', strtotime($dateFrom)) . ' TO PRESENT';
        } elseif ($dateTo !== '') {
            $dateRangeDisplay = 'UP TO: ' . date('d M Y', strtotime($dateTo));
        } elseif ($caseNumber !== '') {
            $dateRangeDisplay = 'INDIVIDUAL CASE REFERENCE: ' . $caseNumber;
        }

        $isSingleCase = $caseNumber !== '';
        $backUrl = url('/DG/cases');

        return view('DG.suspect_report_preview', compact(
            'compiledSuspects',
            'summaryData',
            'dateFrom',
            'dateTo',
            'dateRangeDisplay',
            'isSingleCase',
            'caseNumber',
            'search',
            'backUrl'
        ));
    }
}

