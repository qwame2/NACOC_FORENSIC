<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Policies\ForensicCasePolicy;
use App\Services\CaseShowReviewPresenter;
use Illuminate\Http\Request;

class ForensicHeadCaseController extends Controller
{
    use CaseControllerHelpers;

    public function showReview(Request $request, $id, CaseShowReviewPresenter $presenter, ForensicCasePolicy $policy)
    {
        $case = $this->findCase($id);
        if (!$case) {
            abort(404, "Case {$id} not found.");
        }

        if (!$policy->viewForensicHeadReview($request->user(), $case)) {
            abort(403, 'Unauthorized access: Restricted to Unit Command & Directorate Heads.');
        }

        $this->repairExhibitAssociations($case);
        $gsaReports = SuspectGsaReport::forCase($case->case_number ?: $id, $case->id);
        $nlrReports = SuspectNlrReport::forCase($case->case_number ?: $id, $case->id);
        $presenter = $presenter->presentForUnit($case, 'hof', $request, $request->user());

        return view('Forensic_Head.show_review', compact('case', 'gsaReports', 'nlrReports', 'presenter'));
    }
}

