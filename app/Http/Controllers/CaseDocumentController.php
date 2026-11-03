<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\NlrReport;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Services\CaseDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * CaseDocumentController
 *
 * Dedicated controller for case documents, lab certificates, analytical reports,
 * NLR report dossiers, GSA reports, and secure evidence media uploads.
 * Split from the original monolithic controller according to Issue 16 & 17 (<400 lines).
 * Fix Issue 7a: Removed all fabricated fallback data (strict 404).
 * Fix Issue 27: Hardened photo upload with getimagesize + finfo MIME inspection.
 * Fix Issue 28: Authorization checks via authorizeCaseWrite().
 */
class CaseDocumentController extends Controller
{
    use CaseControllerHelpers;

    /**
     * Dedicated printable / PDF view for Official Laboratory Examination Certificate.
     * Fix Issue 7a: Strict 404 if case not found.
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showLabCertificate($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            // WHY: Never fabricate suspect names or exhibits for missing cases. Strict 404.
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);

        $gsaReports = SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );
        $nlrReports = SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );

        return view('Lab_Analysis.certificate', compact('case', 'gsaReports', 'nlrReports'));
    }

    /**
     * Dedicated printable / PDF view for Official Laboratory Analytical Report.
     * Fix Issue 7a: Strict 404 if case not found.
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showLabAnalytical($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            // WHY: Never fabricate suspect names or exhibits (KOJO ADU, 90kg Heroin) for missing cases.
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);

        $gsaReports = SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );
        $nlrReports = SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );

        return view('Lab_Analysis.analytical', compact('case', 'gsaReports', 'nlrReports'));
    }

    /**
     * Dedicated NACOC Lab Result (NLR) Report PDF / Printable View.
     * Fix Issue 7a: Strict 404 if case not found.
     * Fix Issue 19: repairExhibitAssociations with explicit save.
     */
    public function showNlrReportPdf($id)
    {
        $nlrRecord = NlrReport::forCase($id, $id);
        if (!$nlrRecord && is_numeric($id)) {
            $nlrRecord = NlrReport::where('multiple_suspect_case_id', (int)$id)
                ->orWhere('single_suspect_case_id', (int)$id)
                ->latest('id')
                ->first();
        }
        $targetRef = ($nlrRecord && !empty($nlrRecord->case_number)) ? $nlrRecord->case_number : $id;

        $case = $this->findCase($targetRef);

        if ($case && is_numeric($id)) {
            $isLabCase = str_contains(strtolower($case->unit_name ?? ''), 'lab') || $case->isDrugCase();
            if (!$isLabCase) {
                $multiLab = MultipleSuspectCase::find((int)$id);
                if ($multiLab && (str_contains(strtolower($multiLab->unit_name ?? ''), 'lab') || $multiLab->exhibits()->where('exhibit_category', 'LIKE', '%drug%')->exists())) {
                    $case = ForensicCase::wrapRecord($multiLab);
                } else {
                    $singleLab = SingleSuspectCase::find((int)$id);
                    if ($singleLab && (str_contains(strtolower($singleLab->unit_name ?? ''), 'lab') || $singleLab->exhibits()->where('exhibit_category', 'LIKE', '%drug%')->exists())) {
                        $case = ForensicCase::wrapRecord($singleLab);
                    }
                }
            }
        }

        if (!$case) {
            // WHY: Never fabricate fallback suspect/exhibit data (KOJO ADU, 90kg Heroin). Strict 404.
            abort(404, "Case {$id} not found.");
        }

        $this->repairExhibitAssociations($case);

        $gsaReports = SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );
        $nlrReports = SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );

        return view('Lab_Analysis.nlr_report_pdf', compact('case', 'gsaReports', 'nlrReports'));
    }

    /**
     * Store / Update NLR Report data (detailed_exhibit_description & condition_of_reserved_sample).
     * Fix Issue 28: Enforce authorizeCaseWrite() authorization check.
     */
    public function storeNlrReport(Request $request)
    {
        $validated = $request->validate([
            'case_id'                       => 'nullable',
            'case_number'                   => 'nullable|string',
            'laboratory_number'             => 'nullable|string',
            'detailed_exhibit_description'  => 'required|string',
            'condition_of_reserved_sample'  => 'nullable|string',
            'suspect_id'                    => 'nullable',
            'suspect_name'                  => 'nullable|string',
            'nlr_code'                      => 'nullable|string',
            'exhibit_id'                    => 'nullable',
            'exhibit_number'                => 'nullable|string',
            'lab_exhibit_number'            => 'nullable|string',
            'analyst'                       => 'nullable|string',
            'lab_date_received'             => 'nullable|string',
            'lab_date_started'              => 'nullable|string',
            'lab_date_completed'            => 'nullable|string',
            'presumptive_result'            => 'nullable|string',
            'confirmatory_test_result'      => 'nullable|string',
            'sample_weight_description'     => 'nullable|string',
            'sample_weight_units'           => 'nullable|string',
            'sample_weight_gross'           => 'nullable|string',
            'sample_weight_taken'           => 'nullable|string',
            'sample_weight_reserved'        => 'nullable|string',
            'confirmatory_substance_identification' => 'nullable|string',
            'detected_substances'           => 'nullable',
            'confirmatory_test_methods'     => 'nullable|string',
            'confirmatory_specifications'   => 'nullable|string',
            'confirmatory_remark'           => 'nullable|string',
            'findings'                      => 'nullable|string',
        ]);

        $caseId = $request->input('case_id');
        $caseNo = $request->input('case_number');

        $case = null;
        if (!empty($caseNo)) {
            $case = $this->findCase($caseNo);
        }
        if (!$case && !empty($caseId)) {
            $case = $this->findCase($caseId);
        }

        if ($case) {
            // Fix Issue 28: Enforce case modification authorization
            $this->authorizeCaseWrite($case);
        }

        $documentService = app(CaseDocumentService::class);
        $result = $documentService->processNlrReport($request, $case);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('status', $result['message']);
    }

    /**
     * Store GSA live report attachments.
     * Fix Issue 28: Enforce authorizeCaseWrite() authorization check.
     */
    public function storeGsaReports(Request $request, $id = null)
    {
        $request->validate([
            'case_suspect_id'          => 'nullable|integer',
            'suspect_name'             => 'nullable|string|max:255',
            'multiple_suspect_case_id' => 'nullable|integer',
            'single_suspect_case_id'   => 'nullable|integer',
            'gsa_lab_number'           => 'nullable|string|max:255',
            'gsa_case_reference'       => 'nullable|string',
            'gsa_status'               => 'nullable|string',
            'exhibit_type'             => 'nullable',
            'gsa_report_images'        => 'required|array|min:1',
            'gsa_report_images.*'      => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,pdf,docx,csv',
        ]);

        $targetId = $id ?: ($request->input('multiple_suspect_case_id') ?: ($request->input('single_suspect_case_id') ?: $request->input('gsa_case_reference')));
        $case = $targetId ? $this->findCase($targetId) : null;

        if ($case) {
            // Fix Issue 28: Enforce case modification authorization
            $this->authorizeCaseWrite($case);
        }

        $documentService = app(CaseDocumentService::class);
        $result = $documentService->processGsaReports($request, $case);

        return response()->json($result);
    }

    /**
     * Upload evidence photos with strict MIME and polyglot validation.
     * Fix Issue 27: Verify with getimagesize + finfo as defense-in-depth against malicious polyglots.
     */
    public function uploadEvidencePhotos(Request $request)
    {
        $request->validate([
            'photos'   => ['required', 'array', 'min:1'],
            'photos.*' => [
                'required', 'file', 'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240',
                function ($attribute, $file, $fail) {
                    // WHY Fix Issue 27: 'image' + 'mimes' checks MIME, but polyglot files can sneak through.
                    // Verify with getimagesize + finfo as defense-in-depth.
                    $info = @getimagesize($file->getRealPath());
                    $realMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
                    if (!$info || !in_array($realMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                        $fail("The {$attribute} file is not a valid image.");
                    }
                },
            ],
        ]);

        $stored = [];

        foreach ($request->file('photos') as $photo) {
            if (!$photo->isValid()) {
                continue;
            }

            $path = $photo->store('evidence_photos', 'public');

            if ($path) {
                $stored[] = Storage::url($path);
            }
        }

        if (empty($stored)) {
            return response()->json(['success' => false, 'message' => 'No valid photos were uploaded.'], 422);
        }

        return response()->json([
            'success' => true,
            'urls'    => $stored,
            'message' => count($stored) . ' photo(s) uploaded successfully.',
        ]);
    }
}
