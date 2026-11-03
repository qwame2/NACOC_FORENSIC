<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Services\CaseAnalyticsService;
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
        return view('Lab_Analysis.report', $data);
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
}
