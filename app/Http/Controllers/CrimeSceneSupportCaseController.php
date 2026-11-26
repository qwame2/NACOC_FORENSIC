<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\CrimeSceneReportNote;
use App\Models\ForensicCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Models\User;
use App\Policies\ForensicCasePolicy;
use App\Services\CaseShowReviewPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CrimeSceneSupportCaseController extends Controller
{
    use CaseControllerHelpers;

    public function showReview(Request $request, $id, CaseShowReviewPresenter $presenter, ForensicCasePolicy $policy)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        if (!$policy->viewUnitReview($request->user(), $case, 'Crime Scene Support')) {
            abort(403, 'Unauthorized access to Crime Scene Support case review.');
        }

        $this->repairExhibitAssociations($case);
        $gsaReports = SuspectGsaReport::forCase($case->case_number ?: $id, $case->id);
        $nlrReports = SuspectNlrReport::forCase($case->case_number ?: $id, $case->id);
        $presenter = $presenter->presentForUnit($case, 'css', $request, $request->user());

        return view('Crime_Scene_Support.show_review', compact('case', 'gsaReports', 'nlrReports', 'presenter'));
    }

    /**
     * Display the official Crime Scene Support Cases & Field Operations Report page.
     */
    public function report(Request $request)
    {
        $data = $this->buildCrimeSceneCasesData($request);

        $perPage = (int) $request->input('per_page', 20);
        if ($perPage < 5) $perPage = 20;
        if ($perPage > 250) $perPage = 250;

        $cases = ForensicCase::paginateCases($data['filtered'], $perPage);
        $data['cases'] = $cases;

        $latestReportNote = CrimeSceneReportNote::where('user_id', Auth::id())->latest()->first()
            ?? CrimeSceneReportNote::latest()->first();
        $data['latestReportNote'] = $latestReportNote;

        if ($request->ajax()) {
            if ($request->wantsJson() && !str_contains($request->header('Accept', ''), 'text/html')) {
                $html = view('Crime_Scene_Support.partials.report_table', $data)->render();
                return response()->json([
                    'success' => true,
                    'html' => $html,
                    'total' => $cases->total(),
                    'totalFilteredCount' => $data['totalFilteredCount'] ?? $cases->total(),
                    'firstItem' => $cases->firstItem() ?? 0,
                    'lastItem' => $cases->lastItem() ?? 0,
                ]);
            }

            return view('Crime_Scene_Support.partials.report_table', $data);
        }

        $viewName = 'Crime_Scene_Support.report';
        if ($request->is('Digital_Forensics/staff/report*')) {
            $viewName = 'Digital_Forensics.staff.report';
        } elseif ($request->is('Forensic_Head/staff/report*')) {
            $viewName = 'Forensic_Head.staff.report';
        } elseif ($request->is('*/staff/report*')) {
            $viewName = 'Crime_Scene_Support.staff.report';
        }

        return view($viewName, $data);
    }

    /**
     * Save Crime Scene Support Report Details (Status, Recommendation, Conclusion).
     */
    public function saveReportNotes(Request $request)
    {
        $user = Auth::user();
        $batchId = $request->input('batch_id') ?: ('CSS-REP-' . strtoupper(Str::random(10)));
        $status = trim((string) $request->input('status', ''));
        $recommendation = trim((string) $request->input('recommendation', ''));
        $conclusion = trim((string) $request->input('conclusion', ''));

        $record = CrimeSceneReportNote::create([
            'user_id'           => $user?->id,
            'officer_name'      => $user?->fullname ?: ($user?->name ?? 'Crime Scene Support Officer'),
            'status'            => $status !== '' ? $status : null,
            'recommendation'    => $recommendation !== '' ? $recommendation : null,
            'conclusion'        => $conclusion !== '' ? $conclusion : null,
            'batch_id'          => $batchId,
            'report_type'       => 'crime_scene_support',
            'period'            => $request->input('period'),
            'from_date'         => $request->filled('from_date') ? $request->input('from_date') : null,
            'to_date'           => $request->filled('to_date') ? $request->input('to_date') : null,
            'search'            => $request->input('search'),
            'priority'          => $request->input('priority'),
            'status_filter'     => $request->input('status_filter') ?: $request->input('action_status'),
            'total_cases_count' => $request->has('total_cases_count') ? (int) $request->input('total_cases_count') : null,
        ]);

        return response()->json([
            'success'   => true,
            'batch_id'  => $batchId,
            'record_id' => $record->id,
            'message'   => 'Crime scene report details saved successfully.',
        ]);
    }

    /**
     * Display printable / PDF export dossier of Crime Scene Support cases.
     */
    public function reportPdf(Request $request)
    {
        $data = $this->buildCrimeSceneCasesData($request);

        // Limit to 1000 records for the PDF dossier ledger
        $data['cases'] = $data['filtered']->take(1000);

        // Fetch report notes (Status, Recommendation, Conclusion)
        $batchId = $request->input('batch_id');
        $reportNote = null;
        if (!empty($batchId)) {
            $reportNote = CrimeSceneReportNote::where('batch_id', $batchId)->latest()->first();
        }
        if (!$reportNote && $request->filled('note_id')) {
            $reportNote = CrimeSceneReportNote::find($request->input('note_id'));
        }
        if (!$reportNote) {
            if ($request->filled('status') || $request->filled('recommendation') || $request->filled('conclusion')) {
                $reportNote = new CrimeSceneReportNote([
                    'status' => $request->input('status'),
                    'recommendation' => $request->input('recommendation'),
                    'conclusion' => $request->input('conclusion'),
                ]);
            } else {
                $reportNote = CrimeSceneReportNote::where('user_id', Auth::id())->latest()->first()
                    ?? CrimeSceneReportNote::latest()->first();
            }
        }
        $data['reportNote'] = $reportNote;
        $data['batchId'] = $batchId;

        // Signatories (Unit Head & Forensic Directorate Head)
        $data['dbCssHead'] = User::where(function ($q) {
            $q->where('unit', 'LIKE', '%Crime Scene%')
              ->orWhere('unit', 'LIKE', '%CSS%');
        })->where('is_head', true)->first()
        ?? User::where('unit', 'LIKE', '%Crime Scene%')->first();

        $data['dbDeptHead'] = User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
            ?? User::where('unit', 'LIKE', '%Forensic Head%')->first()
            ?? User::where('is_head', true)->first();

        return view('Crime_Scene_Support.report_pdf', $data);
    }

    /**
     * Build filtered Crime Scene Support cases collection and summary metrics.
     */
    protected function buildCrimeSceneCasesData(Request $request): array
    {
        $allCases = ForensicCase::allCases();

        // 1. Filter exclusively to cases generated by Crime Scene Support
        $cssCases = $allCases->filter(function ($c) {
            $u = strtolower(trim((string)($c->unit_name ?? '')));
            $num = strtoupper(trim((string)($c->case_number ?? '')));
            $userUnit = strtolower(trim((string)($c->user?->unit ?? '')));
            $sourceUnit = strtolower(trim((string)($c->source_requesting_unit ?? '')));

            return str_contains($u, 'crime')
                || str_contains($u, 'scene')
                || str_contains($u, 'css')
                || str_starts_with($num, 'CSS-')
                || str_contains($userUnit, 'crime')
                || str_contains($userUnit, 'css')
                || str_contains($sourceUnit, 'crime')
                || str_contains($sourceUnit, 'css');
        })->values();

        // High-level telemetry stats across all Crime Scene Support cases
        $totalCasesCount = $cssCases->count();
        $activeCasesCount = $cssCases->filter(function ($c) {
            $st = strtolower(trim((string)($c->status ?? $c->case_status ?? '')));
            return in_array($st, ['active investigation', 'under multi-unit processing', 'pending', 'draft', 'in progress', 'under investigation'])
                || !in_array($st, ['approved', 'court ready', 'completed', 'certified', 'closed']);
        })->count();

        $totalSuspectsCount = (int) $cssCases->sum(function ($c) {
            if (!empty($c->docket_data['suspects']) && is_array($c->docket_data['suspects'])) {
                return count($c->docket_data['suspects']);
            }
            return !empty($c->total_suspects_count) ? (int)$c->total_suspects_count : (!empty($c->suspect_name) ? 1 : 0);
        });

        $totalExhibitsCount = (int) $cssCases->sum(function ($c) {
            if ($c->relationLoaded('exhibits') && $c->exhibits->isNotEmpty()) {
                return $c->exhibits->count();
            }
            if (!empty($c->docket_data['seizures']) && is_array($c->docket_data['seizures'])) {
                return count($c->docket_data['seizures']);
            }
            return !empty($c->total_exhibits_count) ? (int)$c->total_exhibits_count : 0;
        });

        $criticalPriorityCount = $cssCases->filter(function ($c) {
            return \App\Models\ForensicCase::normalizePriority($c->priority ?? 'HIGH') === 'HIGH';
        })->count();

        // 2. Parse request filter parameters
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $operationType = trim((string) $request->input('operation_type', ''));
        $priority = trim((string) $request->input('priority', ''));
        $period = trim((string) $request->input('period', 'general'));
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $sort = trim((string) $request->input('sort', 'newest'));

        $filtered = $cssCases;

        // Apply Search filter
        if ($search !== '') {
            $sLower = strtolower($search);
            $filtered = $filtered->filter(function ($c) use ($sLower) {
                $suspectNames = collect($c->docket_data['suspects'] ?? [])->pluck('suspect_name')->filter()->implode(' ');
                $seizureNames = collect($c->docket_data['seizures'] ?? [])->pluck('seizure_name')->filter()->implode(' ');

                return str_contains(strtolower($c->case_number ?? ''), $sLower)
                    || str_contains(strtolower($c->title ?? ''), $sLower)
                    || str_contains(strtolower($c->suspect_name ?? ''), $sLower)
                    || str_contains(strtolower($suspectNames), $sLower)
                    || str_contains(strtolower($c->arresting_officer ?? ''), $sLower)
                    || str_contains(strtolower($c->submitting_officer ?? ''), $sLower)
                    || str_contains(strtolower($c->raid_location ?? ''), $sLower)
                    || str_contains(strtolower($c->incident_scene ?? ''), $sLower)
                    || str_contains(strtolower($c->operation_code ?? ''), $sLower)
                    || str_contains(strtolower($c->operation_code_name ?? ''), $sLower)
                    || str_contains(strtolower($c->exhibits_summary ?? ''), $sLower)
                    || str_contains(strtolower($seizureNames), $sLower);
            })->values();
        }

        // Apply Status filter
        if ($status !== '' && strtoupper($status) !== 'ALL') {
            $stLower = strtolower($status);
            $filtered = $filtered->filter(function ($c) use ($stLower) {
                $curStatus = strtolower(trim((string)($c->status ?? '')));
                $caseStatus = strtolower(trim((string)($c->case_status ?? '')));
                return $curStatus === $stLower || $caseStatus === $stLower || str_contains($curStatus, $stLower);
            })->values();
        }

        // Apply Operation / Event Type filter
        if ($operationType !== '' && strtoupper($operationType) !== 'ALL') {
            $opLower = strtolower($operationType);
            $filtered = $filtered->filter(function ($c) use ($opLower) {
                $opCode = strtolower(trim((string)($c->operation_code ?? '')));
                $opName = strtolower(trim((string)($c->operation_code_name ?? '')));
                $title = strtolower(trim((string)($c->title ?? '')));
                return str_contains($opCode, $opLower) || str_contains($opName, $opLower) || str_contains($title, $opLower);
            })->values();
        }

        // Apply Priority filter
        if ($priority !== '' && strtoupper($priority) !== 'ALL') {
            $pUpper = \App\Models\ForensicCase::normalizePriority($priority, 'HIGH');
            $filtered = $filtered->filter(function ($c) use ($pUpper) {
                return \App\Models\ForensicCase::normalizePriority($c->priority ?? 'HIGH') === $pUpper;
            })->values();
        }

        // Apply Date Range / Period filter
        $now = Carbon::now();
        $dateThreshold = null;

        if (!empty($fromDate) || !empty($toDate)) {
            $start = !empty($fromDate) ? Carbon::parse($fromDate)->startOfDay() : null;
            $end = !empty($toDate) ? Carbon::parse($toDate)->endOfDay() : null;

            $filtered = $filtered->filter(function ($c) use ($start, $end) {
                try {
                    $rawDate = $c->incident_date ?: ($c->arrest_date ?: $c->created_at);
                    if (!$rawDate) return true;
                    $d = Carbon::parse($rawDate);
                    if ($start && $d->lt($start)) return false;
                    if ($end && $d->gt($end)) return false;
                    return true;
                } catch (\Throwable) {
                    return true;
                }
            })->values();
        } elseif (in_array(strtolower($period), ['weekly', 'week'])) {
            $dateThreshold = $now->copy()->subDays(7)->startOfDay();
        } elseif (in_array(strtolower($period), ['monthly', 'month'])) {
            $dateThreshold = $now->copy()->subDays(30)->startOfDay();
        } elseif (in_array(strtolower($period), ['annual', 'annually', 'year', 'yearly'])) {
            $dateThreshold = $now->copy()->subYear()->startOfDay();
        }

        if ($dateThreshold) {
            $filtered = $filtered->filter(function ($c) use ($dateThreshold) {
                try {
                    $rawDate = $c->incident_date ?: ($c->arrest_date ?: $c->created_at);
                    if (!$rawDate) return false;
                    return Carbon::parse($rawDate)->gte($dateThreshold);
                } catch (\Throwable) {
                    return true;
                }
            })->values();
        }

        // Apply Sorting
        $filtered = match ($sort) {
            'oldest' => $filtered->sortBy(fn($c) => $c->created_at)->values(),
            'case_asc' => $filtered->sortBy(fn($c) => strtoupper(trim((string)$c->case_number)))->values(),
            'case_desc' => $filtered->sortByDesc(fn($c) => strtoupper(trim((string)$c->case_number)))->values(),
            'priority' => $filtered->sortBy(function ($c) {
                return \App\Models\ForensicCase::normalizePriority($c->priority ?? 'HIGH') === 'HIGH' ? 1 : 2;
            })->values(),
            default => $filtered->sortByDesc(fn($c) => $c->created_at)->values(),
        };

        $totalFilteredCount = $filtered->count();

        return compact(
            'cssCases',
            'filtered',
            'totalCasesCount',
            'activeCasesCount',
            'totalSuspectsCount',
            'totalExhibitsCount',
            'criticalPriorityCount',
            'totalFilteredCount',
            'search',
            'status',
            'operationType',
            'priority',
            'period',
            'fromDate',
            'toDate',
            'sort'
        );
    }
}


