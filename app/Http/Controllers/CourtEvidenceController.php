<?php

namespace App\Http\Controllers;

use App\Models\CrimeSceneReportNote;
use App\Models\DpfReport;
use App\Models\DrugScreening;
use App\Models\EvidenceReportNote;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CourtEvidenceController extends Controller
{
    /**
     * Display the Court Evidence & SITREP Repository page.
     * Shows all generated PDFs across units and all officer-uploaded PDFs.
     */
    public function index(Request $request)
    {
        $allDocuments = $this->collectAllDocuments($request);

        // 1. Calculate KPI Metrics across all documents (before filtering)
        $totalCount = $allDocuments->count();
        $generatedCount = $allDocuments->where('origin_type', 'generated')->count();
        $uploadedCount = $allDocuments->where('origin_type', 'uploaded')->count();

        $unitCounts = [
            'all'   => $totalCount,
            'css'   => $allDocuments->where('unit_code', 'css')->count(),
            'em'    => $allDocuments->where('unit_code', 'em')->count(),
            'lab'   => $allDocuments->where('unit_code', 'lab')->count(),
            'dpf'   => $allDocuments->where('unit_code', 'dpf')->count(),
            'hof'   => $allDocuments->where('unit_code', 'hof')->count(),
        ];

        $categoryCounts = $allDocuments->groupBy('category')->map->count();

        // 2. Apply Filters
        $filtered = $allDocuments;

        // Search Query
        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $filtered = $filtered->filter(function ($doc) use ($searchLower) {
                return str_contains(mb_strtolower($doc['title'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['original_filename'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['case_number'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['officer_name'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['category'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['reference_no'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['unit_name'] ?? ''), $searchLower)
                    || str_contains(mb_strtolower($doc['notes'] ?? ''), $searchLower);
            });
        }

        // Unit Filter
        $unitFilter = strtolower(trim((string) $request->input('unit', 'all')));
        if ($unitFilter !== '' && $unitFilter !== 'all') {
            $filtered = $filtered->filter(function ($doc) use ($unitFilter) {
                return ($doc['unit_code'] ?? '') === $unitFilter
                    || str_contains(strtolower($doc['unit_name'] ?? ''), $unitFilter);
            });
        }

        // Origin Filter (Uploaded vs System Generated)
        $originFilter = strtolower(trim((string) $request->input('origin', 'all')));
        if ($originFilter !== '' && $originFilter !== 'all') {
            $filtered = $filtered->filter(function ($doc) use ($originFilter) {
                return ($doc['origin_type'] ?? '') === $originFilter;
            });
        }

        // Category Filter
        $categoryFilter = trim((string) $request->input('category', 'all'));
        if ($categoryFilter !== '' && $categoryFilter !== 'all') {
            $filtered = $filtered->filter(function ($doc) use ($categoryFilter) {
                return ($doc['category'] ?? '') === $categoryFilter;
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        $filtered = match ($sort) {
            'oldest'    => $filtered->sortBy('created_at_timestamp'),
            'case_asc'  => $filtered->sortBy('case_number'),
            'case_desc' => $filtered->sortByDesc('case_number'),
            'title_asc' => $filtered->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            default     => $filtered->sortByDesc('created_at_timestamp'),
        };

        // 3. Paginate
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage < 5) $perPage = 20;
        if ($perPage > 200) $perPage = 200;

        $page = (int) $request->input('page', 1);
        $totalFiltered = $filtered->count();
        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $documents = new LengthAwarePaginator(
            $items,
            $totalFiltered,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $pendingCount = User::where('is_approved', false)->count();

        if ($request->ajax()) {
            return view('Forensic_Head.partials.court_evidence_table', compact('documents'));
        }

        return view('Forensic_Head.court_evidence', compact(
            'documents',
            'totalCount',
            'generatedCount',
            'uploadedCount',
            'unitCounts',
            'categoryCounts',
            'search',
            'unitFilter',
            'originFilter',
            'categoryFilter',
            'sort',
            'perPage',
            'pendingCount'
        ));
    }

    /**
     * Download or stream court evidence document securely.
     */
    public function download(Request $request)
    {
        $docId = $request->input('id');
        $filePath = $request->input('path');
        $filename = $request->input('filename', 'Court_Evidence_Document.pdf');

        if (!empty($filePath)) {
            // Clean up path
            $filePath = ltrim(str_replace(['../', '..\\'], '', $filePath), '/\\');
            
            // Check public path
            $publicFile = public_path($filePath);
            if (File::exists($publicFile) && is_file($publicFile)) {
                return response()->download($publicFile, $filename);
            }

            // Check storage public path
            $storageFile = storage_path('app/public/' . $filePath);
            if (File::exists($storageFile) && is_file($storageFile)) {
                return response()->download($storageFile, $filename);
            }

            // If it starts with storage/
            if (str_starts_with($filePath, 'storage/')) {
                $trimmed = substr($filePath, 8);
                $storageFile2 = storage_path('app/public/' . $trimmed);
                if (File::exists($storageFile2) && is_file($storageFile2)) {
                    return response()->download($storageFile2, $filename);
                }
            }
        }

        // If not found as physical file, redirect to URL
        $targetUrl = $request->input('url');
        if (!empty($targetUrl)) {
            return redirect()->away($targetUrl);
        }

        abort(404, 'Document not found.');
    }

    /**
     * Compile master list of all uploaded and generated PDF documents across all units.
     */
    protected function collectAllDocuments(?Request $request = null): Collection
    {
        $docs = collect();

        // Resolve requested timeframe preset (monthly, weekly, annually, customize, general)
        $periodParam = strtolower(trim((string)($request?->input('period') ?: ($request?->input('preset') ?: ''))));
        $fromDate = trim((string)($request?->input('from') ?: ($request?->input('from_date') ?: '')));
        $toDate   = trim((string)($request?->input('to') ?: ($request?->input('to_date') ?: '')));
        $hasCustomRange = (!empty($fromDate) || !empty($toDate));

        if (in_array($periodParam, ['weekly', 'week'])) {
            $timeframeLabel = 'Weekly Report';
            $timeframeSlug  = 'weekly';
        } elseif (in_array($periodParam, ['monthly', 'month'])) {
            $timeframeLabel = 'Monthly Report';
            $timeframeSlug  = 'monthly';
        } elseif (in_array($periodParam, ['annual', 'annually', 'yearly', 'year'])) {
            $timeframeLabel = 'Annual Report';
            $timeframeSlug  = 'annually';
        } elseif ($hasCustomRange || in_array($periodParam, ['custom', 'customize', 'date_range', 'range'])) {
            $timeframeLabel = 'Customize Date Range Report';
            $timeframeSlug  = 'custom_range';
        } else {
            $timeframeLabel = 'General Report';
            $timeframeSlug  = 'general';
        }

        // Query parameters to propagate to generated report routes
        $reportQueryParams = array_filter([
            'period'    => in_array($periodParam, ['weekly', 'week', 'monthly', 'month', 'annual', 'annually', 'yearly', 'year']) ? $periodParam : ($hasCustomRange ? 'range' : 'general'),
            'from'      => $fromDate ?: null,
            'to'        => $toDate ?: null,
            'from_date' => $fromDate ?: null,
            'to_date'   => $toDate ?: null,
        ]);
        $reportQueryStr = !empty($reportQueryParams) ? ('?' . http_build_query($reportQueryParams)) : '';

        // =========================================================================
        // A. SYSTEM-GENERATED UNIT REPORTS (LAB, CRIME SCENE, EVIDENCE, DIGITAL)
        // =========================================================================

        // 1. Laboratory Analysis Report
        $labReportUrl = url('/Lab_Analysis/lab_sitrep_pdf' . $reportQueryStr);
        $labPreviewUrl = url('/Lab_Analysis/lab_sitrep_pdf' . ($reportQueryStr ? ($reportQueryStr . '&preview=1&from=court-evidence') : '?preview=1&from=court-evidence'));
        $docs->push([
            'id'                   => 'sys-report-lab',
            'title'                => 'Lab ' . $timeframeLabel,
            'original_filename'    => 'Lab_' . ucfirst($timeframeSlug) . '_Report.pdf',
            'category'             => 'Lab Report',
            'category_badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            'unit_name'            => 'Laboratory Analysis',
            'unit_code'            => 'lab',
            'unit_badge_class'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'origin_type'          => 'generated',
            'origin_label'         => 'System Generated',
            'case_number'          => null,
            'case_review_url'      => url('/Lab_Analysis/report'),
            'officer_name'         => 'Laboratory Analysis Unit',
            'officer_service_id'   => 'SYS-LAB',
            'created_at'           => Carbon::now()->format('d M Y, H:i'),
            'created_at_timestamp' => Carbon::now()->timestamp + 100, // keep at top
            'file_size'            => 'Dynamic PDF Dossier',
            'file_bytes'           => 1048576,
            'url'                  => $labReportUrl,
            'preview_url'          => $labPreviewUrl,
            'download_url'         => $labReportUrl,
            'reference_no'         => 'LAB-' . strtoupper($timeframeSlug) . '-' . date('Y-m-d'),
            'notes'                => '',
        ]);

        // 2. Crime Scene Support Report
        $cssReportUrl = url('/Crime_Scene_Support/report_pdf' . $reportQueryStr);
        $docs->push([
            'id'                   => 'sys-report-css',
            'title'                => 'Crime Scene ' . $timeframeLabel,
            'original_filename'    => 'Crime_Scene_' . ucfirst($timeframeSlug) . '_Report.pdf',
            'category'             => 'Crime Scene Report',
            'category_badge_class' => 'bg-amber-50 text-amber-800 border-amber-300',
            'unit_name'            => 'Crime Scene Support',
            'unit_code'            => 'css',
            'unit_badge_class'     => 'bg-amber-100 text-amber-900 border-amber-300',
            'origin_type'          => 'generated',
            'origin_label'         => 'System Generated',
            'case_number'          => null,
            'case_review_url'      => url('/Crime_Scene_Support'),
            'officer_name'         => 'Crime Scene Support Unit',
            'officer_service_id'   => 'SYS-CSS',
            'created_at'           => Carbon::now()->format('d M Y, H:i'),
            'created_at_timestamp' => Carbon::now()->timestamp + 90,
            'file_size'            => 'Dynamic PDF Dossier',
            'file_bytes'           => 524288,
            'url'                  => $cssReportUrl,
            'preview_url'          => $cssReportUrl,
            'download_url'         => $cssReportUrl,
            'reference_no'         => 'CSS-' . strtoupper($timeframeSlug) . '-' . date('Y-m-d'),
            'notes'                => 'Crime scene recovery logs, field officer observations, and scene inspection records for the selected ' . strtolower($timeframeLabel) . '.',
        ]);

        // 3. Evidence Management Report
        $emReportUrl = url('/Evidence_Management/report_pdf' . $reportQueryStr);
        $docs->push([
            'id'                   => 'sys-report-em',
            'title'                => 'Evidence ' . $timeframeLabel,
            'original_filename'    => 'Evidence_' . ucfirst($timeframeSlug) . '_Report.pdf',
            'category'             => 'Evidence Report',
            'category_badge_class' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
            'unit_name'            => 'Evidence Management',
            'unit_code'            => 'em',
            'unit_badge_class'     => 'bg-indigo-100 text-indigo-900 border-indigo-300',
            'origin_type'          => 'generated',
            'origin_label'         => 'System Generated',
            'case_number'          => null,
            'case_review_url'      => url('/Evidence_Management/vault'),
            'officer_name'         => 'Evidence Management Unit',
            'officer_service_id'   => 'SYS-EMU',
            'created_at'           => Carbon::now()->format('d M Y, H:i'),
            'created_at_timestamp' => Carbon::now()->timestamp + 80,
            'file_size'            => 'Dynamic PDF Dossier',
            'file_bytes'           => 524288,
            'url'                  => $emReportUrl,
            'preview_url'          => $emReportUrl,
            'download_url'         => $emReportUrl,
            'reference_no'         => 'EMU-' . strtoupper($timeframeSlug) . '-' . date('Y-m-d'),
            'notes'                => 'Seized exhibits property inventory, vault custodian movements, and chain-of-custody for the selected ' . strtolower($timeframeLabel) . '.',
        ]);

        // 4. Digital Forensics Report
        $dpfReportUrl = url('/Digital_Forensics/multi_dpf_pdf' . $reportQueryStr);
        $docs->push([
            'id'                   => 'sys-report-dpf',
            'title'                => 'Digital ' . $timeframeLabel,
            'original_filename'    => 'Digital_' . ucfirst($timeframeSlug) . '_Report.pdf',
            'category'             => 'Digital Forensics Report',
            'category_badge_class' => 'bg-cyan-50 text-cyan-800 border-cyan-300',
            'unit_name'            => 'Digital & Physical Forensics',
            'unit_code'            => 'dpf',
            'unit_badge_class'     => 'bg-cyan-100 text-cyan-900 border-cyan-300',
            'origin_type'          => 'generated',
            'origin_label'         => 'System Generated',
            'case_number'          => null,
            'case_review_url'      => url('/Digital_Forensics/cases'),
            'officer_name'         => 'Digital & Physical Forensics Unit',
            'officer_service_id'   => 'SYS-DPF',
            'created_at'           => Carbon::now()->format('d M Y, H:i'),
            'created_at_timestamp' => Carbon::now()->timestamp + 60,
            'file_size'            => 'Dynamic PDF Dossier',
            'file_bytes'           => 524288,
            'url'                  => $dpfReportUrl,
            'preview_url'          => $dpfReportUrl,
            'download_url'         => $dpfReportUrl,
            'reference_no'         => 'DPF-' . strtoupper($timeframeSlug) . '-' . date('Y-m-d'),
            'notes'                => 'Digital device extractions, forensic acquisitions, and hardware analysis records for the selected ' . strtolower($timeframeLabel) . '.',
        ]);

        // 5. Substance Screening & Toxicology Summary
        $docs->push([
            'id'                   => 'sys-drug-screening-master',
            'title'                => 'Lab Screening ' . $timeframeLabel,
            'original_filename'    => 'Lab_Screening_' . ucfirst($timeframeSlug) . '_Report.pdf',
            'category'             => 'Toxicology Screening',
            'category_badge_class' => 'bg-teal-50 text-teal-800 border-teal-300',
            'unit_name'            => 'Laboratory Analysis',
            'unit_code'            => 'lab',
            'unit_badge_class'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'origin_type'          => 'generated',
            'origin_label'         => 'System Generated',
            'case_number'          => null,
            'case_review_url'      => url('/Lab_Analysis/report'),
            'officer_name'         => 'Forensic Toxicology Unit',
            'officer_service_id'   => 'SYS-LAB-TOX',
            'created_at'           => Carbon::now()->format('d M Y, H:i'),
            'created_at_timestamp' => Carbon::now()->timestamp + 70,
            'file_size'            => 'Dynamic PDF Dossier',
            'file_bytes'           => 524288,
            'url'                  => url('/Lab_Analysis/drug-screening/report/pdf' . $reportQueryStr),
            'preview_url'          => url('/Lab_Analysis/drug-screening/report/pdf' . $reportQueryStr),
            'download_url'         => url('/Lab_Analysis/drug-screening/report/pdf' . $reportQueryStr),
            'reference_no'         => 'TOX-' . strtoupper($timeframeSlug) . '-' . date('Y-m-d'),
            'notes'                => 'Summary of civilian drug screening assessments and multi-panel assay results for the selected ' . strtolower($timeframeLabel) . '.',
        ]);

        // =========================================================================
        // B. OFFICER-UPLOADED PDFS (ALL UNITS)
        // =========================================================================

        // 1. Ghana Standards Authority (GSA) Confirmatory Test Reports (Uploaded by Lab Officers)
        try {
            $gsaReports = SuspectGsaReport::with(['suspect', 'uploader'])->active()->latest('created_at')->get();
            foreach ($gsaReports as $gsa) {
                $ext = strtolower(pathinfo($gsa->original_filename ?: $gsa->file_path, PATHINFO_EXTENSION));
                $isPdf = $ext === 'pdf'
                    || str_contains(strtolower($gsa->file_type ?? ''), 'pdf')
                    || str_ends_with(strtolower($gsa->file_path ?? ''), '.pdf');

                if ($isPdf) {
                    $cAt = $gsa->created_at ?: Carbon::now();
                    $fileBytes = (int) ($gsa->file_size ?? 0);
                    $fileLabel = $gsa->file_size_label ?: $this->formatBytes($fileBytes);
                    
                    $docs->push([
                        'id'                   => 'gsa-' . $gsa->id,
                        'title'                => $gsa->original_filename ?: ('GSA Confirmatory Report — ' . $gsa->case_number),
                        'original_filename'    => $gsa->original_filename ?: basename($gsa->file_path),
                        'category'             => 'GSA Confirmatory Test',
                        'category_badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                        'unit_name'            => 'Laboratory Analysis',
                        'unit_code'            => 'lab',
                        'unit_badge_class'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                        'origin_type'          => 'uploaded',
                        'origin_label'         => 'Uploaded by Officer',
                        'case_number'          => $gsa->case_number ?: 'N/A',
                        'case_review_url'      => $gsa->case_number ? url('/Forensic_Head/cases/' . $gsa->case_number . '/review') : '#',
                        'officer_name'         => $gsa->uploader->fullname ?? 'Forensic Lab Officer',
                        'officer_service_id'   => $gsa->uploader->service_id ?? 'LAB-OFFICER',
                        'created_at'           => $cAt->format('d M Y, H:i'),
                        'created_at_timestamp' => $cAt->timestamp,
                        'file_size'            => $fileLabel,
                        'file_bytes'           => $fileBytes,
                        'url'                  => $gsa->url,
                        'preview_url'          => $gsa->url,
                        'download_url'         => route('court_evidence.download', ['path' => $gsa->file_path, 'filename' => $gsa->original_filename ?: 'GSA_Report.pdf']),
                        'reference_no'         => $gsa->gsa_lab_number ?: ($gsa->gsa_case_reference ?: 'GSA Ref'),
                        'notes'                => 'Official GSA confirmatory chemical report. ' . ($gsa->gsa_status ? 'Status: ' . $gsa->gsa_status : ''),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Log or continue gracefully
        }

        // 2. Preliminary Laboratory / Screening Reports (Uploaded by Lab Officers)
        try {
            $prelimReports = SuspectPreliminaryReport::with(['suspect', 'uploader'])->active()->latest('created_at')->get();
            foreach ($prelimReports as $prelim) {
                $ext = strtolower(pathinfo($prelim->original_filename ?: $prelim->file_path, PATHINFO_EXTENSION));
                $isPdf = $ext === 'pdf'
                    || str_contains(strtolower($prelim->file_type ?? ''), 'pdf')
                    || str_ends_with(strtolower($prelim->file_path ?? ''), '.pdf');

                if ($isPdf) {
                    $cAt = $prelim->created_at ?: Carbon::now();
                    $fileBytes = (int) ($prelim->file_size ?? 0);
                    $fileLabel = $prelim->file_size_label ?: $this->formatBytes($fileBytes);

                    $docs->push([
                        'id'                   => 'prelim-' . $prelim->id,
                        'title'                => $prelim->original_filename ?: ('Preliminary Analysis Report — ' . $prelim->case_number),
                        'original_filename'    => $prelim->original_filename ?: basename($prelim->file_path),
                        'category'             => 'Preliminary Lab Report',
                        'category_badge_class' => 'bg-teal-50 text-teal-800 border-teal-300',
                        'unit_name'            => 'Laboratory Analysis',
                        'unit_code'            => 'lab',
                        'unit_badge_class'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                        'origin_type'          => 'uploaded',
                        'origin_label'         => 'Uploaded by Officer',
                        'case_number'          => $prelim->case_number ?: 'N/A',
                        'case_review_url'      => $prelim->case_number ? url('/Forensic_Head/cases/' . $prelim->case_number . '/review') : '#',
                        'officer_name'         => $prelim->uploader->fullname ?? 'Field / Lab Officer',
                        'officer_service_id'   => $prelim->uploader->service_id ?? 'LAB-SCREENING',
                        'created_at'           => $cAt->format('d M Y, H:i'),
                        'created_at_timestamp' => $cAt->timestamp,
                        'file_size'            => $fileLabel,
                        'file_bytes'           => $fileBytes,
                        'url'                  => $prelim->url,
                        'preview_url'          => $prelim->url,
                        'download_url'         => route('court_evidence.download', ['path' => $prelim->file_path, 'filename' => $prelim->original_filename ?: 'Preliminary_Report.pdf']),
                        'reference_no'         => $prelim->lab_number ?: 'Prelim Lab No',
                        'notes'                => 'Preliminary colorimetric & narcotic presumptive analysis filed for case docket.',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Log or continue gracefully
        }

        // 3. Digital & Physical Forensics Court Orders and Witness Statements (Uploaded by DPF Officers)
        try {
            $dpfReports = DpfReport::with(['user'])->latest('created_at')->get();
            foreach ($dpfReports as $dpf) {
                $cAt = $dpf->created_at ?: Carbon::now();
                $analystName = $dpf->user->fullname ?? 'DPF Forensic Examiner';
                $analystId = $dpf->user->service_id ?? 'DPF-ANALYST';

                // Court Orders & Search Warrants
                foreach ($dpf->court_order_files as $idx => $filePath) {
                    if (empty($filePath)) continue;
                    $filename = basename($filePath);
                    $fullPath = public_path($filePath);
                    $fileBytes = File::exists($fullPath) ? File::size($fullPath) : 0;
                    $cleanTitle = preg_replace('/^\d+_[a-f0-9]+_court_order_/i', '', $filename);
                    $cleanTitle = str_replace('_', ' ', $cleanTitle);

                    $docs->push([
                        'id'                   => 'dpf-co-' . $dpf->id . '-' . $idx,
                        'title'                => 'Court Order & Search Warrant: ' . $cleanTitle,
                        'original_filename'    => $filename,
                        'category'             => 'Court Order & Warrant',
                        'category_badge_class' => 'bg-purple-50 text-purple-800 border-purple-300',
                        'unit_name'            => 'Digital & Physical Forensics',
                        'unit_code'            => 'dpf',
                        'unit_badge_class'     => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                        'origin_type'          => 'uploaded',
                        'origin_label'         => 'Uploaded by Officer',
                        'case_number'          => $dpf->case_number ?: 'N/A',
                        'case_review_url'      => $dpf->case_number ? url('/Forensic_Head/cases/' . $dpf->case_number . '/review') : '#',
                        'officer_name'         => $analystName,
                        'officer_service_id'   => $analystId,
                        'created_at'           => $cAt->format('d M Y, H:i'),
                        'created_at_timestamp' => $cAt->timestamp,
                        'file_size'            => $this->formatBytes($fileBytes),
                        'file_bytes'           => $fileBytes,
                        'url'                  => asset($filePath),
                        'preview_url'          => asset($filePath),
                        'download_url'         => route('court_evidence.download', ['path' => $filePath, 'filename' => $filename]),
                        'reference_no'         => $dpf->dpf_code ?: 'Court Warrant',
                        'notes'                => 'High Court search and seizure authorization decree granting forensic digital device acquisition.',
                    ]);
                }

                // Witness Statements & Deposition Statements
                foreach ($dpf->report_witness_statement_files as $idx => $filePath) {
                    if (empty($filePath)) continue;
                    $filename = basename($filePath);
                    $fullPath = public_path($filePath);
                    $fileBytes = File::exists($fullPath) ? File::size($fullPath) : 0;
                    $cleanTitle = preg_replace('/^\d+_[a-f0-9]+_report_witness_/i', '', $filename);
                    $cleanTitle = str_replace('_', ' ', $cleanTitle);

                    $docs->push([
                        'id'                   => 'dpf-ws-' . $dpf->id . '-' . $idx,
                        'title'                => 'Witness Statement & Deposition: ' . $cleanTitle,
                        'original_filename'    => $filename,
                        'category'             => 'Witness Statement',
                        'category_badge_class' => 'bg-blue-50 text-blue-800 border-blue-300',
                        'unit_name'            => 'Digital & Physical Forensics',
                        'unit_code'            => 'dpf',
                        'unit_badge_class'     => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                        'origin_type'          => 'uploaded',
                        'origin_label'         => 'Uploaded by Officer',
                        'case_number'          => $dpf->case_number ?: 'N/A',
                        'case_review_url'      => $dpf->case_number ? url('/Forensic_Head/cases/' . $dpf->case_number . '/review') : '#',
                        'officer_name'         => $analystName,
                        'officer_service_id'   => $analystId,
                        'created_at'           => $cAt->format('d M Y, H:i'),
                        'created_at_timestamp' => $cAt->timestamp,
                        'file_size'            => $this->formatBytes($fileBytes),
                        'file_bytes'           => $fileBytes,
                        'url'                  => asset($filePath),
                        'preview_url'          => asset($filePath),
                        'download_url'         => route('court_evidence.download', ['path' => $filePath, 'filename' => $filename]),
                        'reference_no'         => $dpf->dpf_code ?: 'Witness Dossier',
                        'notes'                => 'Sworn testimony statement, suspect interrogation notes, or formal witness deposition.',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Log or continue gracefully
        }

        // =========================================================================
        // C. CASE-LEVEL GENERATED COURT EVIDENCE PDFS (ALL ACTIVE CASES)
        // =========================================================================
        try {
            $cases = ForensicCase::allCases();
            foreach ($cases as $case) {
                $cAt = $case->created_at ? Carbon::parse($case->created_at) : Carbon::now();
                $cNo = $case->case_number;
                $unitName = $case->unit_name ?: 'Forensic Science Services';
                $unitCode = $this->resolveUnitCode($unitName);
                $unitBadge = $this->resolveUnitBadge($unitCode);
                $reviewUrl = url('/Forensic_Head/cases/' . $cNo . '/review');

                // Digital Forensics Device Extraction Dossier
                if (str_contains(strtolower($unitName), 'digital') || str_contains(strtolower($unitName), 'physical') || str_contains(strtolower($unitName), 'dpf')) {
                    $docs->push([
                        'id'                   => 'sys-dpf-' . $cNo,
                        'title'                => 'Digital Forensics Extraction Dossier — ' . $cNo,
                        'original_filename'    => 'DPF_Extraction_' . $cNo . '.pdf',
                        'category'             => 'DPF Extraction Dossier',
                        'category_badge_class' => 'bg-cyan-50 text-cyan-800 border-cyan-300',
                        'unit_name'            => 'Digital & Physical Forensics',
                        'unit_code'            => 'dpf',
                        'unit_badge_class'     => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                        'origin_type'          => 'generated',
                        'origin_label'         => 'System Generated',
                        'case_number'          => $cNo,
                        'case_review_url'      => $reviewUrl,
                        'officer_name'         => 'Digital Forensics Analyst',
                        'officer_service_id'   => 'DPF-EXAMINER',
                        'created_at'           => $cAt->format('d M Y, H:i'),
                        'created_at_timestamp' => $cAt->timestamp - 30,
                        'file_size'            => 'Extraction Dossier PDF',
                        'file_bytes'           => 195000,
                        'url'                  => url('/Digital_Forensics/dpf-report-pdf/' . $cNo),
                        'preview_url'          => url('/Digital_Forensics/dpf-report-pdf/' . $cNo),
                        'download_url'         => url('/Digital_Forensics/dpf-report-pdf/' . $cNo),
                        'reference_no'         => 'DPF-' . $cNo,
                        'notes'                => 'Digital hardware extraction log, UFED report, SHA-256 acquisition hashes, and telecommunications chat dump.',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Log or continue gracefully
        }

        // 5. Individual Civilian Drug Screening PDF Reports
        try {
            $screenings = DrugScreening::latest('created_at')->get();
            foreach ($screenings as $ds) {
                $cAt = $ds->created_at ?: Carbon::now();
                $name = $ds->civilian_name ?: ('Civilian #' . $ds->id);

                $docs->push([
                    'id'                   => 'sys-ds-' . $ds->id,
                    'title'                => 'Civilian Drug Screening Report (' . $name . ')',
                    'original_filename'    => 'Screening_Report_' . $ds->id . '.pdf',
                    'category'             => 'Civilian Screening Report',
                    'category_badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                    'unit_name'            => 'Laboratory Analysis',
                    'unit_code'            => 'lab',
                    'unit_badge_class'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                    'origin_type'          => 'generated',
                    'origin_label'         => 'System Generated',
                    'case_number'          => 'CIV-SCR-' . $ds->id,
                    'case_review_url'      => url('/Lab_Analysis/report'),
                    'officer_name'         => 'Lab Screening Officer',
                    'officer_service_id'   => 'LAB-TOXICOLOGY',
                    'created_at'           => $cAt->format('d M Y, H:i'),
                    'created_at_timestamp' => $cAt->timestamp,
                    'file_size'            => 'Toxicology PDF',
                    'file_bytes'           => 115000,
                    'url'                  => url('/Lab_Analysis/drug-screening/' . $ds->id . '/pdf'),
                    'preview_url'          => url('/Lab_Analysis/drug-screening/' . $ds->id . '/pdf'),
                    'download_url'         => url('/Lab_Analysis/drug-screening/' . $ds->id . '/pdf'),
                    'reference_no'         => 'DS-' . $ds->id,
                    'notes'                => 'Result: ' . ($ds->result ?: 'Completed') . ' • Purpose: ' . ($ds->purpose ?: 'Screening'),
                ]);
            }
        } catch (\Throwable $e) {
            // Log or continue gracefully
        }

        return $docs;
    }

    /**
     * Resolve unit code slug.
     */
    protected function resolveUnitCode(?string $unitName): string
    {
        $u = strtolower(trim((string) $unitName));
        if (str_contains($u, 'crime') || str_contains($u, 'scene') || str_contains($u, 'css')) return 'css';
        if (str_contains($u, 'evidence') || str_contains($u, 'management') || str_contains($u, 'vault') || str_contains($u, 'emu')) return 'em';
        if (str_contains($u, 'lab') || str_contains($u, 'chemical') || str_contains($u, 'toxic')) return 'lab';
        if (str_contains($u, 'digital') || str_contains($u, 'physical') || str_contains($u, 'dpf')) return 'dpf';
        return 'hof';
    }

    /**
     * Resolve badge styling for unit.
     */
    protected function resolveUnitBadge(string $unitCode): string
    {
        return match ($unitCode) {
            'css'   => 'bg-amber-100 text-amber-900 border-amber-300',
            'em'    => 'bg-indigo-100 text-indigo-900 border-indigo-300',
            'lab'   => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'dpf'   => 'bg-cyan-100 text-cyan-900 border-cyan-300',
            default => 'bg-slate-900 text-white border-slate-700',
        };
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return number_format($bytes / 1024, 1)    . ' KB';
        if ($bytes > 0)        return $bytes . ' B';
        return 'System Generated';
    }
}

