<?php

namespace App\Services;

use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\NlrReport;
use App\Models\SuspectNlrReport;
use App\Models\SuspectGsaReport;
use App\Models\LaboratoryExaminationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CaseDocumentService
 *
 * Encapsulates report persistence and file attachment processing for NLR and GSA reports.
 */
class CaseDocumentService
{
    /**
     * Store or update NLR Report and attach PDF references to suspects.
     *
     * @param Request $request
     * @param ForensicCase|null $case
     * @return array
     */
    public function processNlrReport(Request $request, ?ForensicCase $case): array
    {
        $caseId = $request->input('case_id');
        $caseNo = $request->input('case_number');
        $labNo  = $request->input('laboratory_number');

        $singleId = null;
        $multipleId = null;

        if ($case) {
            if ($case->underlyingRecord instanceof MultipleSuspectCase) {
                $multipleId = $case->underlyingRecord->id;
            } elseif ($case->underlyingRecord instanceof SingleSuspectCase) {
                $singleId = $case->underlyingRecord->id;
            } elseif ($case->is_multiple) {
                $multipleId = $case->id;
            } else {
                $singleId = $case->id;
            }
        } elseif (!empty($caseId) && is_numeric($caseId)) {
            $multi = MultipleSuspectCase::find((int)$caseId);
            if ($multi) {
                $multipleId = $multi->id;
            } else {
                $single = SingleSuspectCase::find((int)$caseId);
                if ($single) {
                    $singleId = $single->id;
                }
            }
        }

        $exhibitDesc = trim((string)$request->input('detailed_exhibit_description'));
        $condition   = trim((string)$request->input('condition_of_reserved_sample'));

        $report = NlrReport::where(function ($q) use ($caseId, $caseNo, $labNo, $singleId, $multipleId) {
            if (!empty($caseNo)) $q->orWhere('case_number', $caseNo);
            if (!empty($labNo)) $q->orWhere('laboratory_number', $labNo);
            if (!empty($multipleId)) $q->orWhere('multiple_suspect_case_id', $multipleId);
            if (!empty($singleId)) $q->orWhere('single_suspect_case_id', $singleId);
            if (!empty($caseId) && is_numeric($caseId)) {
                $q->orWhere('single_suspect_case_id', (int)$caseId)
                  ->orWhere('multiple_suspect_case_id', (int)$caseId);
            }
        })->latest('id')->first();

        $nlrCode = $request->input('nlr_code');
        if (empty($nlrCode) && $report && !empty($report->nlr_code)) {
            $nlrCode = $report->nlr_code;
        }
        if (empty($nlrCode)) {
            $nlrCode = 'NLR-' . strtoupper(substr(md5(uniqid()), 0, 8));
        }

        $multiCase = $multipleId ? MultipleSuspectCase::find($multipleId) : null;
        $singleCase = $singleId ? SingleSuspectCase::find($singleId) : null;

        if ($report) {
            $report->update(array_filter([
                'multiple_suspect_case_id'     => $multipleId ?: $report->multiple_suspect_case_id,
                'single_suspect_case_id'       => $singleId ?: $report->single_suspect_case_id,
                'case_number'                  => $caseNo ?: ($report->case_number ?: ($case ? $case->case_number : null)),
                'laboratory_number'            => $labNo ?: $report->laboratory_number,
                'detailed_exhibit_description' => $exhibitDesc,
                'condition_of_reserved_sample' => $condition ?: $report->condition_of_reserved_sample,
                'nlr_code'                     => $nlrCode,
            ], fn($v) => !is_null($v)));
        } else {
            $report = NlrReport::create([
                'multiple_suspect_case_id'     => $multipleId,
                'single_suspect_case_id'       => $singleId,
                'case_number'                  => $caseNo ?: ($case ? $case->case_number : ($multiCase ? $multiCase->case_number : null)),
                'laboratory_number'            => $labNo,
                'detailed_exhibit_description' => $exhibitDesc,
                'condition_of_reserved_sample' => $condition,
                'nlr_code'                     => $nlrCode,
            ]);
        }

        // Sync with Case docket data
        if ($case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            if (!isset($docketData['lab_examination'])) {
                $docketData['lab_examination'] = [];
            }
            $docketData['lab_examination']['detailed_exhibit_description'] = $exhibitDesc;
            $docketData['lab_examination']['condition_of_reserved_sample'] = $condition;
            $docketData['lab_examination']['nlr_code'] = $nlrCode;
            $case->docket_data = $docketData;
            $case->save();
            if ($case->underlyingRecord && \App\Services\SchemaCache::hasColumn($case->underlyingRecord->getTable(), 'docket_data')) {
                $case->underlyingRecord->docket_data = $docketData;
                $case->underlyingRecord->save();
            }
        }

        // Attach NLR report files to suspects
        $this->attachNlrReportToSuspects($request, $case, $report, $multiCase, $singleCase, $singleId, $multipleId, $caseNo, $caseId);

        return [
            'success' => true,
            'message' => 'NLR Report details saved successfully.',
            'report'  => $report,
        ];
    }

    /**
     * Attach NLR Report file records to CaseSuspect models.
     */
    protected function attachNlrReportToSuspects($request, $case, $report, $multiCase, $singleCase, $singleId, $multipleId, $caseNo, $caseId): void
    {
        $suspectId = $request->input('suspect_id');
        $targetSuspects = [];

        if (!empty($suspectId)) {
            $specificSuspect = CaseSuspect::find((int)$suspectId);
            if ($specificSuspect) {
                $targetSuspects = [$specificSuspect];
            }
        }

        if (empty($targetSuspects) && $multiCase) {
            $caseSuspects = CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->orderBy('suspect_number')->get();
            $primary = $caseSuspects->where('role', 'Primary Suspect')->first() ?: $caseSuspects->first();
            if ($primary) {
                $targetSuspects = [$primary];
            } else {
                $targetSuspects = $caseSuspects->all();
            }
        }

        $finalCaseNo  = $caseNo ?: ($case ? $case->case_number : ($multiCase ? $multiCase->case_number : null));
        $pdfUrlPath   = 'Lab_Analysis/nlr-report-pdf/' . ($finalCaseNo ?: ($caseId ?: ($multiCase ? $multiCase->id : '')));
        $filename     = 'NLR_Report_' . ($report->nlr_code ?: ($report->laboratory_number ?: 'NACOC')) . '.pdf';
        $singleCaseId = ($case && !($case->underlyingRecord instanceof MultipleSuspectCase)) ? $case->id : ($report->single_suspect_case_id ?: ($singleCase ? $singleCase->id : null));

        if (empty($targetSuspects)) {
            $existingReport = SuspectNlrReport::where(function ($q) use ($multiCase, $singleCaseId, $finalCaseNo) {
                if ($multiCase) $q->orWhere('multiple_suspect_case_id', $multiCase->id);
                if ($singleCaseId) $q->orWhere('single_suspect_case_id', $singleCaseId);
                if ($finalCaseNo) $q->orWhere('case_number', $finalCaseNo);
            })
            ->where(function ($q) use ($pdfUrlPath) {
                $q->where('file_path', $pdfUrlPath)
                  ->orWhere('file_path', '/' . ltrim($pdfUrlPath, '/'))
                  ->orWhere('original_filename', 'LIKE', 'NLR_Report%');
            })
            ->first();

            $payload = [
                'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                'single_suspect_case_id'   => $singleCaseId,
                'lab_number'               => $report->laboratory_number ?: $finalCaseNo,
                'case_number'              => $finalCaseNo,
                'original_filename'        => $filename,
                'file_path'                => $pdfUrlPath,
                'notes'                    => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T35-F') . ')',
                'status'                   => 'active',
            ];

            if ($existingReport) {
                $existingReport->update($payload);
            } else {
                SuspectNlrReport::create(array_merge($payload, [
                    'case_suspect_id' => null,
                    'uploaded_by'     => auth()->id(),
                    'stored_filename' => 'nlr_report_' . $report->id . '.pdf',
                    'file_type'       => 'application/pdf',
                    'file_extension'  => 'pdf',
                    'file_size'       => 0,
                ]));
            }
        } else {
            foreach ($targetSuspects as $s) {
                $existingReport = SuspectNlrReport::where('case_suspect_id', $s->id)
                    ->where(function ($q) use ($pdfUrlPath) {
                        $q->where('file_path', $pdfUrlPath)
                          ->orWhere('file_path', '/' . ltrim($pdfUrlPath, '/'))
                          ->orWhere('original_filename', 'LIKE', 'NLR_Report%');
                    })
                    ->first();

                $payload = [
                    'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                    'single_suspect_case_id'   => $singleCaseId,
                    'lab_number'               => $report->laboratory_number ?: $finalCaseNo,
                    'case_number'              => $finalCaseNo,
                    'original_filename'        => $filename,
                    'file_path'                => $pdfUrlPath,
                    'notes'                    => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T35-F') . ')',
                    'status'                   => 'active',
                ];

                if ($existingReport) {
                    $existingReport->update($payload);
                } else {
                    SuspectNlrReport::create(array_merge($payload, [
                        'case_suspect_id' => $s->id,
                        'uploaded_by'     => auth()->id(),
                        'stored_filename' => 'nlr_report_' . $report->id . '.pdf',
                        'file_type'       => 'application/pdf',
                        'file_extension'  => 'pdf',
                        'file_size'       => 0,
                    ]));
                }
            }
        }
    }

    /**
     * Store uploaded GSA report files on disk and create database records.
     *
     * @param Request $request
     * @param ForensicCase|null $case
     * @return array
     */
    public function processGsaReports(Request $request, ?ForensicCase $case): array
    {
        $suspectId   = $request->input('case_suspect_id');
        $suspectName = $request->input('suspect_name');
        $multiId     = $request->input('multiple_suspect_case_id');
        $singleId    = $request->input('single_suspect_case_id');

        $multiCase  = $multiId  ? MultipleSuspectCase::find($multiId)  : null;
        $singleCase = $singleId ? SingleSuspectCase::find($singleId) : null;

        if (!$multiCase && !$singleCase && $case) {
            if ($case->underlyingRecord instanceof MultipleSuspectCase) {
                $multiCase = $case->underlyingRecord;
            } elseif ($case->underlyingRecord instanceof SingleSuspectCase) {
                $singleCase = $case->underlyingRecord;
            } elseif ($case->is_multiple) {
                $multiCase = MultipleSuspectCase::find($case->id);
            } else {
                $singleCase = SingleSuspectCase::find($case->id);
            }
        }

        $caseNumber = $request->input('case_number')
            ?: ($request->input('gsa_case_reference')
            ?: ($multiCase ? $multiCase->case_number : ($singleCase ? $singleCase->case_number : ($case ? $case->case_number : null))));

        $suspect = null;
        if ($suspectId) {
            $suspect = CaseSuspect::find($suspectId);
        }

        if (!$suspect && $multiCase) {
            if ($suspectName) {
                $cleanName = strtolower(trim($suspectName));
                $suspect = CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)
                    ->where(function ($q) use ($suspectName, $cleanName) {
                        $q->where('suspect_name', $suspectName)
                          ->orWhereRaw('LOWER(TRIM(suspect_name)) = ?', [$cleanName]);
                    })
                    ->first();
            }
            if (!$suspect) {
                $suspect = CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)
                    ->where('role', 'Primary Suspect')
                    ->first()
                    ?: CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->first();
            }
        }

        if (!$suspect) {
            $sName = $suspectName ?: ($singleCase ? $singleCase->suspect_name : ($case ? $case->suspect_name : 'Primary Suspect'));
            $suspect = CaseSuspect::create([
                'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                'single_suspect_case_id'   => $singleCase ? $singleCase->id : null,
                'suspect_name'             => $sName,
                'suspect_number'           => 1,
                'role'                     => 'Primary Suspect',
            ]);
        }

        $files = $request->file('gsa_report_images');
        if (!is_array($files)) {
            $files = [$files];
        }

        $gsaLabNumber = $request->input('gsa_lab_number');
        $gsaStatus    = $request->input('gsa_status', 'active');
        $folder       = 'gsa_reports/' . now()->format('Y/m');
        $saved        = [];

        foreach ($files as $file) {
            if (!$file->isValid()) continue;

            $origName   = $file->getClientOriginalName();
            $ext        = strtolower($file->getClientOriginalExtension());
            $size       = $file->getSize();
            $mime       = $file->getMimeType();
            $storedName = Str::uuid() . '.' . $ext;
            $path       = $file->storeAs($folder, $storedName, 'public');

            $record = SuspectGsaReport::create([
                'case_suspect_id'          => $suspect ? $suspect->id : null,
                'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                'single_suspect_case_id'   => $singleCase ? $singleCase->id : null,
                'uploaded_by'              => auth()->id(),
                'original_filename'        => $origName,
                'stored_filename'          => $storedName,
                'file_path'                => $path,
                'file_type'                => $mime,
                'file_extension'           => $ext,
                'file_size'                => $size,
                'lab_number'               => $gsaLabNumber,
                'gsa_lab_number'           => $gsaLabNumber,
                'gsa_case_reference'       => $request->input('gsa_case_reference'),
                'gsa_status'               => $gsaStatus,
                'case_number'              => $caseNumber,
                'status'                   => $gsaStatus,
            ]);

            if (!empty($gsaLabNumber)) {
                $labQuery = $multiCase
                    ? LaboratoryExaminationResult::where('multiple_suspect_case_id', $multiCase->id)
                    : LaboratoryExaminationResult::where('single_suspect_case_id', $singleCase->id);
                $labQuery->where(function ($q) use ($suspect) {
                    $q->where('suspect_id', $suspect->id)->orWhereNull('suspect_id');
                })->update(['gsa_lab_number' => $gsaLabNumber]);
            }

            $saved[] = [
                'id'                 => $record->id,
                'original_filename'  => $record->original_filename,
                'file_extension'     => $record->file_extension,
                'file_size_label'    => $record->file_size_label,
                'url'                => $record->url,
                'gsa_lab_number'     => $record->gsa_lab_number,
                'gsa_case_reference' => $record->gsa_case_reference,
                'gsa_status'         => $record->gsa_status,
            ];
        }

        return [
            'success' => true,
            'message' => count($saved) . ' GSA report file(s) saved successfully.',
            'files'   => $saved,
        ];
    }
}
