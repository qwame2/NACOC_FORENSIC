<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectExpellingReport;
use App\Models\SuspectPreliminaryReport;
use App\Services\CaseAnalyticsService;
use App\Services\LaboratoryExamService;
use App\Services\LabSitrepReportService;
use Illuminate\Http\Request;

/**
 * CaseReportController
 *
 * Controller handling real-time analytical dashboards, lab result summaries,
 * situational reports (SITREP), and official dossier exports.
 * Split from the original monolithic controller according to Issue 16 & 17 (<400 lines).
 * Fix Issue 5: Centralized drug keywords via config('forensic.*').
 * Fix Issue 6 & 8 & 26: Removed all fabricated metrics from dashboardRealtime().
 * Fix Issue 7b: Removed fabricated baseline cases & screenings from SITREP.
 * Fix Issue 7c: Removed fabricated drug fallback records from labResultPage().
 * Fix Issue 20: Removed dead code in SITREP return array.
 * Fix Issue 22: Neutral label in resolveDrugName() fallback.
 */
class CaseReportController extends Controller
{
    use CaseControllerHelpers;

    /**
     * Display Lab Analysis Results page showing suspects linked strictly to drugs only.
     * Generates exactly one card per case entity with all suspects linked to that drug case.
     * Fix Issue 5: Centralized drug keywords via config('forensic.*').
     * Fix Issue 7c: No fallback fabricated drug records.
     * Fix Issue 22: Neutral label fallback.
     */
    public function labResultPage(Request $request)
    {
        // WHY Fix Issue 7c & 16: Centralized and delegated to LabSitrepReportService without fabricated fallbacks.
        $drugRecords = app(LabSitrepReportService::class)->buildLabResultRecords($request);
        return view('Lab_Analysis.lab_result', compact('drugRecords'));
    }

    /**
     * Display Lab Analysis Report page.
     * Fix Issue 7b: Truthful empty state when no cases exist.
     */
    public function labReportPage(Request $request)
    {
        $sitrepService = app(LabSitrepReportService::class);
        $data = $sitrepService->buildLabSitrepDataset($request);
        $viewName = $request->is('*/staff/report*')
            ? 'Lab_Analysis.staff.report'
            : 'Lab_Analysis.report';

        return view($viewName, $data);
    }

    /**
     * Display Suspect Expelling Report covering Passport Number, Flight Number, Destination, and Substance Expelled.
     */
    public function labExpellingReport(Request $request)
    {
        $isStaffExpellingReport = $request->is('*/staff/expelling-report*');
        $search = trim((string) $request->input('search', ''));
        $fromDate = trim((string) $request->input('from', ''));
        $toDate = trim((string) $request->input('to', ''));

        $records = collect();

        $storedReports = SuspectExpellingReport::with(['suspect', 'singleCase', 'multipleCase'])
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();

        foreach ($storedReports as $rep) {
            $parentCase = $rep->multipleCase ?? $rep->singleCase;
            $caseNum = $rep->case_number ?: ($parentCase?->case_number ?? 'N/A');
            $mugshot = $rep->suspect?->mugshot_front_url
                ?? ($rep->singleCase?->mugshot_front ? \App\Services\CasePhotoResolver::resolveMugshotUrl($rep->singleCase->mugshot_front) : null);

            $records->push([
                'case_id'            => $parentCase?->id ?? $rep->id,
                'case_number'        => $caseNum,
                'suspect_name'       => $rep->suspect_name ?: 'Unknown Suspect',
                'suspect_alias'      => $rep->suspect?->suspect_alias ?? ($rep->singleCase?->suspect_alias ?? null),
                'ghana_card'         => $rep->suspect?->ghana_card ?? ($rep->singleCase?->ghana_card ?? null),
                'nationality'        => $rep->suspect?->nationality ?? ($rep->singleCase?->nationality ?? null),
                'passport_number'    => $rep->passport_number ?: '—',
                'flight_number'      => $rep->flight_number ?: '—',
                'destination'        => $rep->destination ?: '—',
                'substance_expelled' => $rep->substance_expelled ?: '—',
                'arrest_date'        => $rep->date_of_arrest ?: optional($rep->generated_at ?? $rep->created_at)->format('Y-m-d'),
                'holding_facility'   => $rep->suspect?->holding_facility ?? ($rep->singleCase?->holding_facility ?? 'NACOC Custody'),
                'mugshot_url'        => $mugshot,
                'pdf_url'            => $isStaffExpellingReport
                    ? route('lab.staff.expelling_report.pdf', array_filter(['id' => ($caseNum !== 'N/A' ? $caseNum : ($parentCase?->id ?? $rep->id)), 'suspect_id' => $rep->case_suspect_id]))
                    : route('lab.expelling_report.pdf', array_filter(['id' => ($caseNum !== 'N/A' ? $caseNum : ($parentCase?->id ?? $rep->id)), 'suspect_id' => $rep->case_suspect_id])),
                'review_url'         => $parentCase
                    ? ($isStaffExpellingReport
                        ? url('/Lab_Analysis/staff/cases/' . ($caseNum !== 'N/A' ? $caseNum : $parentCase->id) . '/review')
                        : route('lab.cases.review', ['id' => $caseNum !== 'N/A' ? $caseNum : $parentCase->id]))
                    : null,
                'sort_ts'            => optional($rep->generated_at ?? $rep->created_at)->timestamp ?? 0,
            ]);
        }

        $singleCases = SingleSuspectCase::query()
            ->where(function ($q) {
                $q->whereNotNull('passport_number')->where('passport_number', '!=', '')
                  ->orWhere(fn ($sq) => $sq->whereNotNull('flight_number')->where('flight_number', '!=', ''))
                  ->orWhere(fn ($sq) => $sq->whereNotNull('destination')->where('destination', '!=', ''))
                  ->orWhere(fn ($sq) => $sq->whereNotNull('substance_expelled')->where('substance_expelled', '!=', ''));
            })
            ->orderByDesc('id')
            ->get();

        foreach ($singleCases as $sc) {
            $caseNumSc = $sc->case_number ?? 'N/A';
            $dupKeySc = strtolower($caseNumSc . '|' . trim((string) $sc->suspect_name));
            if ($records->contains(fn ($r) => strtolower($r['case_number'] . '|' . trim((string) $r['suspect_name'])) === $dupKeySc)) {
                continue;
            }
            $records->push([
                'case_id'            => $sc->id,
                'case_number'        => $sc->case_number ?? 'N/A',
                'suspect_name'       => $sc->suspect_name ?? 'Unknown Suspect',
                'suspect_alias'      => $sc->suspect_alias ?? null,
                'ghana_card'         => $sc->ghana_card ?? null,
                'nationality'        => $sc->nationality ?? null,
                'passport_number'    => $sc->passport_number ?: '—',
                'flight_number'      => $sc->flight_number ?: '—',
                'destination'        => $sc->destination ?: '—',
                'substance_expelled' => $sc->substance_expelled ?: '—',
                'arrest_date'        => $sc->arrest_date ?: optional($sc->created_at)->format('Y-m-d'),
                'holding_facility'   => $sc->holding_facility ?: 'NACOC Custody',
                'mugshot_url'        => \App\Services\CasePhotoResolver::resolveMugshotUrl($sc->mugshot_front),
                'pdf_url'            => $isStaffExpellingReport
                    ? route('lab.staff.expelling_report.pdf', ['id' => $sc->case_number ?: $sc->id])
                    : route('lab.expelling_report.pdf', ['id' => $sc->case_number ?: $sc->id]),
                'review_url'         => $isStaffExpellingReport
                    ? url('/Lab_Analysis/staff/cases/' . ($sc->case_number ?: $sc->id) . '/review')
                    : route('lab.cases.review', ['id' => $sc->case_number ?: $sc->id]),
                'sort_ts'            => optional($sc->created_at)->timestamp ?? 0,
            ]);
        }

        $caseSuspects = CaseSuspect::with(['singleCase', 'multipleCase'])
            ->where(function ($q) {
                $q->whereNotNull('passport_number')->where('passport_number', '!=', '')
                  ->orWhere(fn ($sq) => $sq->whereNotNull('flight_number')->where('flight_number', '!=', ''))
                  ->orWhere(fn ($sq) => $sq->whereNotNull('destination')->where('destination', '!=', ''))
                  ->orWhere(fn ($sq) => $sq->whereNotNull('substance_expelled')->where('substance_expelled', '!=', ''));
            })
            ->orderByDesc('id')
            ->get();

        foreach ($caseSuspects as $cs) {
            $parentCase = $cs->multipleCase ?? $cs->singleCase;
            $caseNum = $parentCase?->case_number ?? 'N/A';
            $dupKey = strtolower($caseNum . '|' . trim((string) $cs->suspect_name));
            if ($records->contains(fn ($r) => strtolower($r['case_number'] . '|' . trim((string) $r['suspect_name'])) === $dupKey)) {
                continue;
            }

            $records->push([
                'case_id'            => $parentCase?->id ?? $cs->id,
                'case_number'        => $caseNum,
                'suspect_name'       => $cs->suspect_name ?? 'Unknown Suspect',
                'suspect_alias'      => $cs->suspect_alias ?? null,
                'ghana_card'         => $cs->ghana_card ?? null,
                'nationality'        => $cs->nationality ?? null,
                'passport_number'    => $cs->passport_number ?: '—',
                'flight_number'      => $cs->flight_number ?: '—',
                'destination'        => $cs->destination ?: '—',
                'substance_expelled' => $cs->substance_expelled ?: '—',
                'arrest_date'        => $cs->arrest_date ?: optional($cs->created_at)->format('Y-m-d'),
                'holding_facility'   => $cs->holding_facility ?: 'NACOC Custody',
                'mugshot_url'        => $cs->mugshot_front_url,
                'pdf_url'            => $isStaffExpellingReport
                    ? route('lab.staff.expelling_report.pdf', ['id' => ($caseNum !== 'N/A' ? $caseNum : ($parentCase?->id ?? $cs->id)), 'suspect_id' => $cs->id])
                    : route('lab.expelling_report.pdf', ['id' => ($caseNum !== 'N/A' ? $caseNum : ($parentCase?->id ?? $cs->id)), 'suspect_id' => $cs->id]),
                'review_url'         => $parentCase
                    ? ($isStaffExpellingReport
                        ? url('/Lab_Analysis/staff/cases/' . ($caseNum !== 'N/A' ? $caseNum : $parentCase->id) . '/review')
                        : route('lab.cases.review', ['id' => $caseNum !== 'N/A' ? $caseNum : $parentCase->id]))
                    : null,
                'sort_ts'            => optional($cs->created_at)->timestamp ?? 0,
            ]);
        }

        $records = $records->sortByDesc('sort_ts')->values();

        if ($search !== '') {
            $needle = strtolower($search);
            $records = $records->filter(function (array $row) use ($needle) {
                $haystack = strtolower(implode(' ', [
                    $row['case_number'],
                    $row['suspect_name'],
                    $row['suspect_alias'] ?? '',
                    $row['passport_number'],
                    $row['flight_number'],
                    $row['destination'],
                    $row['substance_expelled'],
                ]));
                return str_contains($haystack, $needle);
            })->values();
        }

        if ($fromDate !== '') {
            $records = $records->filter(fn (array $r) => !empty($r['arrest_date']) && $r['arrest_date'] >= $fromDate)->values();
        }
        if ($toDate !== '') {
            $records = $records->filter(fn (array $r) => !empty($r['arrest_date']) && $r['arrest_date'] <= $toDate)->values();
        }

        $stats = [
            'total'        => $records->count(),
            'passports'    => $records->filter(fn ($r) => $r['passport_number'] !== '—')->count(),
            'flights'      => $records->filter(fn ($r) => $r['flight_number'] !== '—')->count(),
            'destinations' => $records->pluck('destination')->filter(fn ($d) => $d !== '—')->unique()->count(),
        ];

        return view('Lab_Analysis.expelling_report', compact(
            'records',
            'stats',
            'isStaffExpellingReport',
            'search',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Render printable Suspect Expelling Report form for a specific case or suspect.
     */
    public function labExpellingReportPdf(Request $request, $id)
    {
        $suspectId = $request->query('suspect_id');
        $suspectModel = $suspectId ? CaseSuspect::with(['singleCase', 'multipleCase'])->find($suspectId) : null;

        $case = $this->findCase($id);
        if (!$case && is_numeric($id)) {
            $single = SingleSuspectCase::find((int) $id);
            if ($single) {
                $case = ForensicCase::wrapRecord($single);
            } else {
                $multi = MultipleSuspectCase::find((int) $id);
                if ($multi) {
                    $case = ForensicCase::wrapRecord($multi);
                }
            }
        }
        if (!$case && $suspectModel) {
            $parent = $suspectModel->multipleCase ?? $suspectModel->singleCase;
            if ($parent) {
                $case = ForensicCase::wrapRecord($parent);
            }
        }

        if (!$case && !$suspectModel) {
            abort(404, 'Case or suspect expelling record not found.');
        }

        $underlying = $case?->underlyingRecord ?? null;
        if (!$suspectModel && $underlying instanceof MultipleSuspectCase) {
            $suspectModel = $underlying->suspects()
                ->where(function ($q) {
                    $q->whereNotNull('passport_number')->where('passport_number', '!=', '')
                      ->orWhere(fn ($sq) => $sq->whereNotNull('flight_number')->where('flight_number', '!=', ''))
                      ->orWhere(fn ($sq) => $sq->whereNotNull('destination')->where('destination', '!=', ''))
                      ->orWhere(fn ($sq) => $sq->whereNotNull('substance_expelled')->where('substance_expelled', '!=', ''));
                })
                ->first() ?? $underlying->suspects()->first();
        } elseif (!$suspectModel && $underlying instanceof SingleSuspectCase) {
            $suspectModel = $underlying->suspect()->first();
        }

        $docketData = ($case && is_array($case->docket_data))
            ? $case->docket_data
            : (json_decode((string) ($case->docket_data ?? '[]'), true) ?: []);
        $docketSuspect0 = $docketData['suspects'][0] ?? [];

        $expellingData = [
            'case_number'        => $case->case_number ?? ($underlying?->case_number ?? ($suspectModel?->case_number ?? '')),
            'suspect_name'       => $suspectModel?->suspect_name ?? ($underlying?->suspect_name ?? ($case->suspect_name ?? ($docketSuspect0['suspect_name'] ?? ''))),
            'passport_number'    => $suspectModel?->passport_number ?? ($underlying?->passport_number ?? ($case->passport_number ?? ($docketSuspect0['passport_number'] ?? ''))),
            'place_of_arrest'    => $suspectModel?->raid_location ?? ($underlying?->raid_location ?? ($case->raid_location ?? ($case->location ?? ($docketSuspect0['raid_location'] ?? '')))),
            'date_of_arrest'     => $suspectModel?->arrest_date ?? ($underlying?->arrest_date ?? ($case->arrest_date ?? ($docketSuspect0['arrest_date'] ?? ''))),
            'arrest_officers'    => $underlying?->arresting_officer ?? ($case->arresting_officer ?? ($case->submitting_officer ?? ($docketData['arresting_officer'] ?? ''))),
            'flight_number'      => $suspectModel?->flight_number ?? ($underlying?->flight_number ?? ($case->flight_number ?? ($docketSuspect0['flight_number'] ?? ''))),
            'airline'            => $docketSuspect0['airline'] ?? ($docketData['airline'] ?? ''),
            'destination'        => $suspectModel?->destination ?? ($underlying?->destination ?? ($case->destination ?? ($docketSuspect0['destination'] ?? ''))),
            'substance_expelled' => $suspectModel?->substance_expelled ?? ($underlying?->substance_expelled ?? ($case->substance_expelled ?? ($docketSuspect0['substance_expelled'] ?? ''))),
        ];

        $hasAnyExpelling = collect([
            $expellingData['passport_number'],
            $expellingData['flight_number'],
            $expellingData['destination'],
            $expellingData['substance_expelled'],
        ])->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty();

        if ($hasAnyExpelling && !empty($expellingData['suspect_name'])) {
            $singleCaseIdVal = ($underlying instanceof SingleSuspectCase) ? $underlying->id : ($suspectModel?->single_suspect_case_id ?? null);
            $multiCaseIdVal = ($underlying instanceof MultipleSuspectCase) ? $underlying->id : ($suspectModel?->multiple_suspect_case_id ?? null);

            SuspectExpellingReport::updateOrCreate(
                $suspectModel
                    ? ['case_number' => $expellingData['case_number'], 'case_suspect_id' => $suspectModel->id]
                    : ['case_number' => $expellingData['case_number'], 'suspect_name' => $expellingData['suspect_name']],
                [
                    'case_suspect_id'          => $suspectModel?->id,
                    'single_suspect_case_id'   => $singleCaseIdVal,
                    'multiple_suspect_case_id' => $multiCaseIdVal,
                    'generated_by'             => auth()->id(),
                    'suspect_name'             => $expellingData['suspect_name'],
                    'passport_number'          => $expellingData['passport_number'] ?: null,
                    'place_of_arrest'          => $expellingData['place_of_arrest'] ?: null,
                    'date_of_arrest'           => $expellingData['date_of_arrest'] ?: null,
                    'arrest_officers'          => $expellingData['arrest_officers'] ?: null,
                    'flight_number'            => $expellingData['flight_number'] ?: null,
                    'airline'                  => $expellingData['airline'] ?: null,
                    'destination'              => $expellingData['destination'] ?: null,
                    'substance_expelled'       => $expellingData['substance_expelled'] ?: null,
                    'report_url'               => $request->fullUrl(),
                    'status'                   => 'Generated',
                    'generated_at'             => now(),
                ]
            );
        }

        return view('Lab_Analysis.expelling_report_pdf', compact('expellingData', 'case', 'suspectModel'));
    }

    /**
     * Display Laboratory Situation Report (SITREP) view and printable PDF dossier.
     * Fix Issue 7b & 20: Delegated to LabSitrepReportService without fabricated data.
     */
    public function labSitrepReport(Request $request)
    {
        $sitrepService = app(LabSitrepReportService::class);
        $data = $sitrepService->buildLabSitrepDataset($request);
        return view('Lab_Analysis.lab_sitrep_pdf', $data);
    }

    /**
     * Realtime analytics API feed for Directorate and Unit Head dashboard overview.
     * Fix Issue 6 & 8 & 26: Compute strictly from real data or return 'N/A'/0. No fabricated values.
     */
    public function dashboardRealtime(Request $request)
    {
        $data = app(\App\Services\CaseAnalyticsService::class)->buildDashboardRealtimeData($request);
        return response()->json($data);
    }

    /**
     * Dedicated Preliminary Exhibit Analysis Report PDF / Printable View.
     * Generates official preliminary exhibit analysis dossier with restricted institutional layout.
     */
    public function preliminaryReportPdf($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case && is_numeric($id)) {
            $single = SingleSuspectCase::find((int)$id);
            if ($single) {
                $case = ForensicCase::wrapRecord($single);
            } else {
                $multi = MultipleSuspectCase::find((int)$id);
                if ($multi) {
                    $case = ForensicCase::wrapRecord($multi);
                }
            }
        }

        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);

        $docketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        $isMulti = method_exists($case, 'isMultipleSuspect') ? $case->isMultipleSuspect() : !empty($case->is_multiple);
        $caseId = $case->id;

        // Suspect information
        $suspects = method_exists($case, 'getSuspectsList') ? $case->getSuspectsList() : [];
        if (empty($suspects) && !empty($docketData['suspects'])) {
            $suspects = $docketData['suspects'];
        }
        $suspectNames = [];
        if (!empty($suspects)) {
            foreach ($suspects as $s) {
                $sName = trim((string)($s['suspect_name'] ?? ($s['name'] ?? '')));
                if (!empty($sName) && !ForensicCase::isUnassignedSuspectName($sName)) {
                    $suspectNames[] = strtoupper($sName);
                }
            }
        }
        if (empty($suspectNames) && !empty($case->suspect_name) && !ForensicCase::isUnassignedSuspectName($case->suspect_name)) {
            $suspectNames[] = strtoupper(trim($case->suspect_name));
        }
        $isNoSuspect = (method_exists($case, 'isNoSuspectCase') && $case->isNoSuspectCase())
            || !empty($case->no_suspect)
            || !empty($docketData['no_suspect'])
            || empty($suspectNames);
        $suspectDisplayName = $isNoSuspect
            ? 'NO SUSPECT(S)'
            : implode(', ', array_unique($suspectNames));

        // Fetch all exhibits
        $exhibitsQuery = Exhibit::query();
        if ($isMulti) {
            $exhibitsQuery->where('multiple_suspect_case_id', $caseId);
        } else {
            $exhibitsQuery->where('single_suspect_case_id', $caseId);
        }
        $exhibits = $exhibitsQuery->orderBy('id', 'asc')->get();

        if ($exhibits->isEmpty() && method_exists($case, 'exhibits')) {
            $exhibits = $case->exhibits()->get();
        }

        // Fetch all laboratory examination results (by case ID or linked exhibit_id, saved first)
        $exhibitIds = $exhibits->pluck('id')->filter()->values()->all();
        $labResults = LaboratoryExaminationResult::where(function ($q) use ($caseId, $isMulti, $exhibitIds) {
            if ($isMulti) {
                $q->where('multiple_suspect_case_id', $caseId);
            } else {
                $q->where('single_suspect_case_id', $caseId);
            }
            if (!empty($exhibitIds)) {
                $q->orWhereIn('exhibit_id', $exhibitIds);
            }
        })
            ->orderByDesc('has_saved')
            ->orderBy('id', 'asc')
            ->get();

        $primaryLabResult = $labResults->first();
        $labDataFromDocket = $docketData['lab_examination'] ?? [];

        // Filter strictly drug exhibits
        $labExamService = app(LaboratoryExamService::class);
        $drugExhibits = $exhibits->filter(function ($ex) use ($labExamService) {
            return $labExamService->isDrugExhibit($ex);
        })->values();

        // Fallback: if no drug exhibits matched explicitly but exhibits exist and this is a drug case
        if ($drugExhibits->isEmpty() && $exhibits->isNotEmpty() && method_exists($case, 'isDrugCase') && $case->isDrugCase()) {
            $drugExhibits = $exhibits->values();
        }

        $fallbackDocketSource = $exhibits->pluck('source_of_exhibits')
            ->first(fn($val) => $this->isMeaningfulText($val));

        // Merge any drug exhibits from docket_data['seizures'] if not yet represented
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            $existingNames = $drugExhibits->map(fn($e) => strtolower(trim((string)($e->commodity_name ?? ($e->seizure_name ?? '')))))->filter()->all();
            $existingLabExNos = $drugExhibits->map(fn($e) => strtolower(trim((string)($e->lab_exhibit_no ?? ''))))->filter()->all();
            $docketDrugSeizures = array_filter($docketData['seizures'], fn($sz) => $labExamService->isDrugExhibit($sz));

            foreach ($docketData['seizures'] as $sIdx => $sz) {
                if (!$labExamService->isDrugExhibit($sz)) {
                    continue;
                }
                $szLabExNo = strtolower(trim((string)($sz['lab_exhibit_no'] ?? '')));
                $szName = strtolower(trim((string)($sz['commodity_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '')))));

                $alreadyPresent = false;
                if (!empty($szLabExNo) && in_array($szLabExNo, $existingLabExNos, true)) {
                    $alreadyPresent = true;
                } elseif (!empty($szName) && in_array($szName, $existingNames, true) && $drugExhibits->count() >= count($docketDrugSeizures)) {
                    $alreadyPresent = true;
                }

                if (!$alreadyPresent) {
                    $drugExhibits->push((object)[
                        'id' => $sz['id'] ?? (1000 + $sIdx),
                        'lab_exhibit_no' => $sz['lab_exhibit_no'] ?? ($sz['exhibit_number'] ?? chr(65 + $drugExhibits->count())),
                        'exhibit_number' => $sz['exhibit_number'] ?? ('EXH-' . str_pad($drugExhibits->count() + 1, 5, '0', STR_PAD_LEFT)),
                        'commodity_name' => $sz['commodity_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null)),
                        'contents' => $sz['contents'] ?? ($sz['seizure_contents'] ?? null),
                        'seizure_quantity' => $sz['seizure_quantity'] ?? '1',
                        'seizure_weight' => $sz['seizure_weight'] ?? null,
                        'seizure_unit' => $sz['seizure_unit'] ?? null,
                        'source_of_exhibits' => $this->isMeaningfulText($sz['source_of_exhibits'] ?? null)
                            ? trim((string)$sz['source_of_exhibits'])
                            : $fallbackDocketSource,
                        'source_requesting_unit' => $this->isMeaningfulText($sz['source_requesting_unit'] ?? null)
                            ? trim((string)$sz['source_requesting_unit'])
                            : $exhibits->pluck('source_requesting_unit')->first(fn($v) => $this->isMeaningfulText($v)),
                        'investigating_officer' => $this->isMeaningfulText($sz['investigating_officer'] ?? null)
                            ? trim((string)$sz['investigating_officer'])
                            : $exhibits->pluck('investigating_officer')->first(fn($v) => $this->isMeaningfulText($v)),
                        'description_of_exhibits' => $this->isMeaningfulText($sz['description_of_exhibits'] ?? null)
                            ? trim((string)$sz['description_of_exhibits'])
                            : $exhibits->pluck('description_of_exhibits')->first(fn($v) => $this->isMeaningfulText($v)),
                        'seizure_date' => $sz['seizure_date'] ?? null,
                        'seizure_time' => $sz['seizure_time'] ?? null,
                    ]);
                    if (!empty($szName)) $existingNames[] = $szName;
                    if (!empty($szLabExNo)) $existingLabExNos[] = $szLabExNo;
                }
            }
        }

        $primaryExhibit = $drugExhibits->first() ?? $exhibits->first();
        $exhibitSourceOfExhibits = $this->resolveExhibitColumn($case, $exhibits, $drugExhibits, $primaryLabResult, 'source_of_exhibits');
        $exhibitSourceRequestingUnit = $this->resolveExhibitColumn($case, $exhibits, $drugExhibits, $primaryLabResult, 'source_requesting_unit');
        $exhibitInvestigatingOfficer = $this->resolveExhibitColumn($case, $exhibits, $drugExhibits, $primaryLabResult, 'investigating_officer');

        // Ensure labResults has corresponding entries for each drug exhibit
        if ($labResults->count() < $drugExhibits->count()) {
            $allResolvedExams = method_exists($case, 'resolveAllLabExamsForSuspect')
                ? $case->resolveAllLabExamsForSuspect()
                : [];

            foreach ($drugExhibits as $dIdx => $dEx) {
                $hasLab = $labResults->firstWhere('exhibit_id', $dEx->id)
                    ?? (!empty($dEx->lab_exhibit_no) ? $labResults->firstWhere('lab_exhibit_no', $dEx->lab_exhibit_no) : null)
                    ?? $labResults->get($dIdx);

                if (!$hasLab) {
                    $matchedResolved = $allResolvedExams[$dIdx] ?? null;
                    if (!$matchedResolved && !empty($docketData['seizures'][$dIdx])) {
                        $szItem = $docketData['seizures'][$dIdx];
                        $matchedResolved = [
                            'id' => $dEx->id ?? (2000 + $dIdx),
                            'exhibit_id' => $dEx->id ?? null,
                            'lab_exhibit_no' => $dEx->lab_exhibit_no ?? chr(65 + $dIdx),
                            'exam_reference' => $szItem['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null),
                            'purpose_of_examination' => $szItem['purpose_of_examination'] ?? ($labDataFromDocket['purpose_of_examination'] ?? null),
                            'preliminary_exam_procedure' => $szItem['preliminary_exam_procedure'] ?? ($labDataFromDocket['preliminary_exam_procedure'] ?? null),
                            'screening_method' => $szItem['screening_method'] ?? ($labDataFromDocket['screening_method'] ?? null),
                            'test_methods' => $szItem['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null),
                            'detected_substances' => $szItem['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? []),
                            'detected_subtances_percentage' => $szItem['detected_subtances_percentage'] ?? ($szItem['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? null))),
                            'detected_substances_percentage' => $szItem['detected_substances_percentage'] ?? ($szItem['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? null))),
                            'presumptive_result' => $szItem['presumptive_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null),
                            'presumptive_presence' => $szItem['presumptive_presence'] ?? ($labDataFromDocket['presumptive_presence'] ?? null),
                            'presumptive_percentage' => $szItem['presumptive_percentage'] ?? ($labDataFromDocket['presumptive_percentage'] ?? null),
                            'substance_identification' => $szItem['substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? null),
                            'specifications' => $szItem['specifications'] ?? ($labDataFromDocket['specifications'] ?? null),
                            'remark' => $szItem['remark'] ?? ($labDataFromDocket['remark'] ?? null),
                            'disposition_of_exhibit' => $szItem['disposition_of_exhibit'] ?? ($labDataFromDocket['disposition_of_exhibit'] ?? null),
                            'date_received' => $szItem['date_received'] ?? ($labDataFromDocket['date_received'] ?? null),
                            'date_started' => $szItem['date_started'] ?? ($labDataFromDocket['date_started'] ?? null),
                            'sample_weight_description' => $szItem['sample_description'] ?? ($szItem['sample_weight_description'] ?? ($szItem['sample_weight']['description'] ?? null)),
                            'sample_weight_gross' => $szItem['sample_gross'] ?? ($szItem['sample_weight_gross'] ?? ($szItem['sample_weight']['gross_weight'] ?? null)),
                            'sample_weight_reserved' => $szItem['sample_reserved'] ?? ($szItem['sample_weight_reserved'] ?? ($szItem['sample_weight']['reserved_samples'] ?? null)),
                            'sample_weight_taken' => $szItem['sample_taken'] ?? ($szItem['sample_weight_taken'] ?? ($szItem['sample_weight']['sample_taken'] ?? null)),
                        ];
                    }

                    if ($matchedResolved) {
                        $labResults->push(is_object($matchedResolved) ? $matchedResolved : (object)$matchedResolved);
                    }
                }
            }
        }

        // Auto-store/sync generated preliminary report in suspect_preliminary_reports
        try {
            $this->storeOrSyncPreliminaryReport($case, $primaryLabResult, $docketData);
        } catch (\Throwable $e) {
            // Fail gracefully without interrupting report view rendering
        }

        return view('Lab_Analysis.preliminary_report_pdf', compact(
            'case',
            'docketData',
            'isNoSuspect',
            'suspectDisplayName',
            'exhibits',
            'drugExhibits',
            'labResults',
            'primaryLabResult',
            'labDataFromDocket',
            'primaryExhibit',
            'exhibitSourceOfExhibits',
            'exhibitSourceRequestingUnit',
            'exhibitInvestigatingOfficer'
        ));
    }

    private function isMeaningfulText($value): bool
    {
        $trimmed = trim((string) ($value ?? ''));
        return $trimmed !== '' && !in_array(strtoupper($trimmed), ['N/A', 'NA', 'NONE', 'NULL', '-', 'UNSPECIFIED'], true);
    }

    /**
     * Resolve an exhibit metadata column directly from the exhibits table.
     */
    private function resolveExhibitColumn($case, $exhibits, $drugExhibits, $primaryLabResult = null, string $column = 'source_of_exhibits'): ?string
    {
        if (!empty($primaryLabResult?->exhibit_id)) {
            $linked = $exhibits->firstWhere('id', $primaryLabResult->exhibit_id)
                ?? Exhibit::find($primaryLabResult->exhibit_id);
            if ($linked && $this->isMeaningfulText($linked->{$column} ?? null)) {
                return trim((string) $linked->{$column});
            }
        }

        foreach ([$drugExhibits, $exhibits] as $collection) {
            if ($collection) {
                foreach ($collection as $item) {
                    if ($item instanceof Exhibit && $this->isMeaningfulText($item->{$column} ?? null)) {
                        return trim((string) $item->{$column});
                    }
                }
            }
        }

        $caseId = $case->underlyingRecord?->id ?? $case->id;
        $isMulti = method_exists($case, 'isMultipleSuspect') ? $case->isMultipleSuspect() : !empty($case->is_multiple);
        $dbValue = Exhibit::query()
            ->where(function ($q) use ($caseId, $isMulti) {
                if ($isMulti) {
                    $q->where('multiple_suspect_case_id', $caseId);
                } else {
                    $q->where('single_suspect_case_id', $caseId);
                }
            })
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id', 'asc')
            ->pluck($column)
            ->first(fn($val) => $this->isMeaningfulText($val));

        if ($dbValue) {
            return trim((string) $dbValue);
        }

        foreach ([$drugExhibits, $exhibits] as $collection) {
            if ($collection) {
                foreach ($collection as $item) {
                    if ($this->isMeaningfulText($item->{$column} ?? null)) {
                        return trim((string) $item->{$column});
                    }
                }
            }
        }

        return null;
    }

    /**
     * Store or sync generated Preliminary Exhibit Analysis Report in suspect_preliminary_reports table.
     */
    public function storeOrSyncPreliminaryReport($case, $primaryLabResult = null, array $docketData = []): void
    {
        if (!$case) {
            return;
        }
        if (!$case instanceof ForensicCase) {
            $case = ForensicCase::wrapRecord($case);
        }

        $isMultiple = ($case instanceof MultipleSuspectCase)
            || ($case->underlyingRecord instanceof MultipleSuspectCase)
            || (method_exists($case, 'isMultipleSuspect') && $case->isMultipleSuspect())
            || !empty($case->is_multiple);

        $singleCaseId = $isMultiple ? null : ($case->underlyingRecord?->id ?? $case->id);
        $multiCaseId  = $isMultiple ? ($case->underlyingRecord?->id ?? $case->id) : null;
        $finalCaseNo  = $case->case_number ?: ($case->id ? 'NCC-' . date('Y') . '-' . $case->id : null);

        $pdfUrlPath = 'Lab_Analysis/preliminary-report-pdf/' . ($finalCaseNo ?: $case->id);

        if (!$primaryLabResult && $case->id) {
            $primaryLabResult = LaboratoryExaminationResult::where(function ($q) use ($case, $singleCaseId, $multiCaseId, $isMultiple) {
                if ($isMultiple) {
                    if ($multiCaseId) $q->orWhere('multiple_suspect_case_id', $multiCaseId);
                    $q->orWhere('multiple_suspect_case_id', $case->id);
                } else {
                    if ($singleCaseId) $q->orWhere('single_suspect_case_id', $singleCaseId);
                    $q->orWhere('single_suspect_case_id', $case->id);
                }
            })->first();
        }

        $examRef = $primaryLabResult?->exam_reference 
            ?? ($case->exam_reference 
            ?? ($docketData['lab_examination']['exam_reference'] 
            ?? ($case->laboratory_number 
            ?? ('NACOC/FSD/LAU/' . ($finalCaseNo ? preg_replace('/[^0-9]/', '', $finalCaseNo) : date('Y')) . '/' . date('Y')))));

        $cleanRef = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$examRef);
        $filename = 'Preliminary_Report_' . $cleanRef . '.pdf';

        $labExamService = app(LaboratoryExamService::class);

        // Fetch all exhibits for the case to strictly identify drug exhibits
        $exhibitsQuery = Exhibit::query();
        if ($isMultiple) {
            $exhibitsQuery->where(function ($q) use ($multiCaseId, $case) {
                if ($multiCaseId) {
                    $q->orWhere('multiple_suspect_case_id', $multiCaseId);
                }
                if ($case->id) {
                    $q->orWhere('multiple_suspect_case_id', $case->id);
                }
            });
        } else {
            $exhibitsQuery->where(function ($q) use ($singleCaseId, $case) {
                if ($singleCaseId) {
                    $q->orWhere('single_suspect_case_id', $singleCaseId);
                }
                if ($case->id) {
                    $q->orWhere('single_suspect_case_id', $case->id);
                }
            });
        }
        $caseExhibits = $exhibitsQuery->orderBy('id', 'asc')->get();
        if ($caseExhibits->isEmpty() && method_exists($case, 'exhibits')) {
            $caseExhibits = $case->exhibits()->get();
        }

        // Filter strictly drug exhibits
        $drugExhibits = $caseExhibits->filter(fn($ex) => $labExamService->isDrugExhibit($ex))->values();

        // Also check docket seizures
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                if ($labExamService->isDrugExhibit($sz)) {
                    $drugExhibits->push(is_object($sz) ? $sz : (object)$sz);
                }
            }
        }

        // Fallback: check case-level drug attributes
        if ($drugExhibits->isEmpty()) {
            $cat = strtolower((string)($case->exhibit_category ?? ''));
            $drugName = strtolower(trim((string)($case->seizure_name_drug ?? '')));
            if (!empty($drugName) && !in_array($drugName, ['n/a', 'none', 'null', '-', 'unknown'], true)) {
                $drugExhibits->push((object)[
                    'category' => 'Drugs',
                    'commodity_name' => $case->seizure_name_drug,
                    'seizure_name_drug' => $case->seizure_name_drug,
                ]);
            } elseif (!str_contains($cat, 'non-drug') && (str_contains($cat, 'drug') || str_contains($cat, 'narcotic'))) {
                $drugExhibits->push((object)[
                    'category' => 'Drugs',
                    'commodity_name' => $case->commodity_name ?? 'Suspected Narcotic Substance',
                ]);
            } elseif (method_exists($case, 'isDrugCase') && $case->isDrugCase() && $caseExhibits->isNotEmpty()) {
                $drugExhibits = $caseExhibits->values();
            }
        }

        // Preliminary_Report applies exclusively to drug exhibits.
        // Do not generate if suspect(s) are not associated with any drug exhibits.
        if ($drugExhibits->isEmpty()) {
            return;
        }

        $targetSuspects = [];
        if ($isMultiple) {
            $caseSuspects = \App\Models\CaseSuspect::where(function ($q) use ($multiCaseId, $case) {
                if ($multiCaseId) {
                    $q->orWhere('multiple_suspect_case_id', $multiCaseId);
                }
                if ($case->id) {
                    $q->orWhere('multiple_suspect_case_id', $case->id);
                }
            })->orderBy('suspect_number')->get();
            if ($caseSuspects->isNotEmpty()) {
                $targetSuspects = $caseSuspects->all();
            }
        } else {
            $checkId = $singleCaseId ?: $case->id;
            if ($checkId) {
                $singleSuspects = \App\Models\CaseSuspect::where('single_suspect_case_id', $checkId)->get();
                if ($singleSuspects->isNotEmpty()) {
                    $targetSuspects = $singleSuspects->all();
                }
            }
        }

        $validSingleId = null;
        if ($singleCaseId && \App\Models\SingleSuspectCase::where('id', $singleCaseId)->exists()) {
            $validSingleId = $singleCaseId;
        }

        $validMultiId = null;
        if ($multiCaseId && \App\Models\MultipleSuspectCase::where('id', $multiCaseId)->exists()) {
            $validMultiId = $multiCaseId;
        }

        $validUserId = null;
        $currentAuthId = auth()->id();
        if ($currentAuthId && \App\Models\User::where('id', $currentAuthId)->exists()) {
            $validUserId = $currentAuthId;
        }

        $basePayload = [
            'multiple_suspect_case_id' => $validMultiId,
            'single_suspect_case_id'   => $validSingleId,
            'lab_number'               => $case->laboratory_number ?: $finalCaseNo,
            'case_number'              => $finalCaseNo,
            'original_filename'        => $filename,
            'file_path'                => $pdfUrlPath,
            'notes'                    => 'Preliminary Exhibit Analysis Report (' . $examRef . ')',
            'status'                   => 'active',
            'file_type'                => 'application/pdf',
            'file_extension'           => 'pdf',
            'file_size'                => 0,
        ];

        // If an NLR / Preliminary report already exists for this case (e.g. manually uploaded or previous run),
        // sync and retain its identity rather than creating duplicate report attachments.
        $existingCaseReport = SuspectPreliminaryReport::where(function ($q) use ($multiCaseId, $singleCaseId, $finalCaseNo, $case, $isMultiple) {
            if ($isMultiple) {
                if ($multiCaseId) $q->orWhere('multiple_suspect_case_id', $multiCaseId);
                if ($case->id) $q->orWhere('multiple_suspect_case_id', $case->id);
            } else {
                if ($singleCaseId) $q->orWhere('single_suspect_case_id', $singleCaseId);
                if ($case->id) $q->orWhere('single_suspect_case_id', $case->id);
            }
            if ($finalCaseNo) $q->orWhere('case_number', $finalCaseNo);
        })
        ->where(function ($q) use ($pdfUrlPath) {
            $q->where('file_path', $pdfUrlPath)
              ->orWhere('file_path', '/' . ltrim($pdfUrlPath, '/'))
              ->orWhere('original_filename', 'LIKE', 'Preliminary_Report%')
              ->orWhere('original_filename', 'LIKE', 'NLR_Report%');
        })
        ->first();

        if ($existingCaseReport) {
            $existingCaseReport->update(array_merge($basePayload, [
                'original_filename' => $existingCaseReport->original_filename ?: $filename,
                'file_path'         => $existingCaseReport->file_path ?: $pdfUrlPath,
            ]));
            return;
        }

        $isNoSuspectCase = method_exists($case, 'isNoSuspectCase') && $case->isNoSuspectCase();

        $suspectMatchesDrugExhibit = function ($suspect, $drugExhibitsList) use ($isNoSuspectCase) {
            $sId = $suspect->id ?? null;
            $rawSName = trim((string)($suspect->suspect_name ?? ($suspect->name ?? '')));
            $sName = strtolower(preg_replace('/\s+/', ' ', $rawSName));
            $sIsUnassigned = $isNoSuspectCase || \App\Models\ForensicCase::isUnassignedSuspectName($rawSName);

            foreach ($drugExhibitsList as $ex) {
                $exSuspectId = is_array($ex) ? ($ex['case_suspect_id'] ?? ($ex['suspect_id'] ?? null)) : ($ex->case_suspect_id ?? ($ex->suspect_id ?? null));
                $rawOwner = trim((string)(is_array($ex) ? ($ex['associated_person'] ?? ($ex['owner_name'] ?? ($ex['suspect_name'] ?? ''))) : ($ex->associated_person ?? ($ex->owner_name ?? ($ex->suspect_name ?? '')))));
                $owner = strtolower(preg_replace('/\s+/', ' ', $rawOwner));

                // Direct ID match
                if ($sId && $exSuspectId && (int)$exSuspectId === (int)$sId) {
                    return true;
                }

                // If exhibit has no owner or is unassigned/shared/joint, match it to unassigned/all suspects
                if (empty($owner)
                    || \App\Models\ForensicCase::isUnassignedSuspectName($rawOwner)
                    || in_array($owner, ['all suspects', 'all', 'both', 'joint', 'all suspect'], true)
                    || str_contains($owner, 'all suspect') || str_contains($owner, 'both') || str_contains($owner, 'joint')) {
                    return true;
                }

                // In no-suspect cases, placeholder suspect rows always match case drug exhibits
                if ($sIsUnassigned) {
                    return true;
                }

                // Name match
                if ($sName !== '' && ($owner === $sName || str_contains($owner, $sName) || str_contains($sName, $owner))) {
                    return true;
                }
            }

            return false;
        };

        $reportsCreatedCount = 0;
        foreach ($targetSuspects as $s) {
            // Preliminary_Report applies exclusively to drug exhibits.
            // Do not generate if suspect is not associated with any drug exhibits.
            if (!$suspectMatchesDrugExhibit($s, $drugExhibits)) {
                continue;
            }

            $validSuspectId = ($s->id && \App\Models\CaseSuspect::where('id', $s->id)->exists()) ? $s->id : null;
            SuspectPreliminaryReport::create(array_merge($basePayload, [
                'case_suspect_id' => $validSuspectId,
                'uploaded_by'     => $validUserId,
                'stored_filename' => 'preliminary_report_' . $case->id . '_' . ($s->id ?? 'all') . '.pdf',
            ]));
            $reportsCreatedCount++;
        }

        if ($reportsCreatedCount === 0) {
            SuspectPreliminaryReport::create(array_merge($basePayload, [
                'case_suspect_id' => null,
                'uploaded_by'     => $validUserId,
                'stored_filename' => 'preliminary_report_' . $case->id . '.pdf',
            ]));
        }
    }
}
