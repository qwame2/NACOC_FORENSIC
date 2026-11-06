<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Policies\ForensicCasePolicy;
use App\Services\CaseShowReviewPresenter;
use Illuminate\Http\Request;

class DigitalForensicsCaseController extends Controller
{
    use CaseControllerHelpers;

    public function showReview(Request $request, $id, CaseShowReviewPresenter $presenter, ForensicCasePolicy $policy)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        if (!$policy->viewUnitReview($request->user(), $case, 'Digital & Physical Forensics')) {
            abort(403, 'Unauthorized access to Digital & Physical Forensics case review.');
        }

        $this->repairExhibitAssociations($case);
        $gsaReports = SuspectGsaReport::forCase($case->case_number ?: $id, $case->id);
        $nlrReports = SuspectNlrReport::forCase($case->case_number ?: $id, $case->id);
        $presenter = $presenter->presentForUnit($case, 'dpf', $request, $request->user());

        return view('Digital_Forensics.show_review', compact('case', 'gsaReports', 'nlrReports', 'presenter'));
    }
}

