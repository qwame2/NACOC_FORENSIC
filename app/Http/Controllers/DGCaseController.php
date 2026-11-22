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
                } else {
                    $matchStatus = ($cStat === strtolower($status) || $stat === strtolower($status) || str_contains($cStat, $sLower) || str_contains($stat, $sLower));
                }
            }

            if ($unit !== '' && strtolower($unit) !== 'all') {
                $cUnit = strtolower((string) ($case->unit_name ?? ''));
                $matchUnit = str_contains($cUnit, strtolower($unit));
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

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) $perPage = 15;
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
                } else {
                    $matchStatus = ($cStat === strtolower($status) || $stat === strtolower($status) || str_contains($cStat, $sLower) || str_contains($stat, $sLower));
                }
            }

            if ($unit !== '' && strtolower($unit) !== 'all') {
                $cUnit = strtolower((string) ($case->unit_name ?? ''));
                $matchUnit = str_contains($cUnit, strtolower($unit));
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

        foreach ($filtered as $case) {
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

                $matchingExhibits = [];
                if (count($suspects) === 1) {
                    $matchingExhibits = $exhibits;
                } else {
                    foreach ($exhibits as $ex) {
                        $exSuspect = strtolower(trim((string)($ex['suspect_name'] ?? '')));
                        if ($exSuspect === '' || str_contains($exSuspect, strtolower(trim($sName))) || str_contains(strtolower(trim($sName)), $exSuspect)) {
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

                $frontPic = $sItem['mugshot_front'] ?? null;
                if (!$frontPic && $sIdx === 0 && !empty($case->evidence_photos)) {
                    $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                    if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                }
                if ($frontPic) {
                    $frontPic = $photoResolver->formatPhotoUrl($frontPic);
                }

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
                    'exhibits'          => $matchingExhibits,
                ];
            }
        }

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

        return view('DG.suspect_report_preview', compact('compiledSuspects', 'dateFrom', 'dateTo', 'dateRangeDisplay', 'isSingleCase', 'caseNumber', 'search', 'backUrl'));
    }
}

