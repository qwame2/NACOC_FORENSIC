<?php

namespace App\Http\Controllers;

use App\Models\StaffAccount;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use App\Services\CaseDocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffCaseSubmissionController extends Controller
{
    protected function resolveUnitContext(Request $request): array
    {
        $path = strtolower($request->path());
        if (str_contains($path, 'lab_analysis')) {
            return ['key' => 'lab_analysis', 'label' => 'Lab Analysis Unit', 'sidebar' => 'Lab Analysis'];
        }
        if (str_contains($path, 'evidence_management')) {
            return ['key' => 'evidence_management', 'label' => 'Evidence Management Unit', 'sidebar' => 'Evidence Management'];
        }
        if (str_contains($path, 'digital_forensics')) {
            return ['key' => 'digital_forensics', 'label' => 'Digital & Physical Forensics Unit', 'sidebar' => 'Digital Forensics'];
        }
        if (str_contains($path, 'forensic_head')) {
            return ['key' => 'forensic_head', 'label' => 'Forensic Directorate (All Units)', 'sidebar' => 'Forensic Directorate'];
        }
        return ['key' => 'crime_scene_support', 'label' => 'Crime Scene Support Unit', 'sidebar' => 'Crime Scene Support'];
    }

    protected function normalizeUnitKey(?string $unit): ?string
    {
        $u = strtolower(trim((string) $unit));
        if ($u === '') {
            return null;
        }
        if (str_contains($u, 'directorate') || str_contains($u, 'forensic_head') || str_contains($u, 'forensic head') || str_contains($u, 'head of forensic')) {
            return 'forensic_head';
        }
        if (str_contains($u, 'lab')) {
            return 'lab_analysis';
        }
        if (str_contains($u, 'evidence') || str_contains($u, 'vault')) {
            return 'evidence_management';
        }
        if (str_contains($u, 'digital') || str_contains($u, 'physical') || str_contains($u, 'cyber')) {
            return 'digital_forensics';
        }
        if (str_contains($u, 'crime')) {
            return 'crime_scene_support';
        }
        return null;
    }

    protected function headCanAccessSubmission(User $head, StaffCaseSubmission $submission, Request $request): bool
    {
        $headUnitKey = $this->normalizeUnitKey($head->unit);
        $submissionUnitKey = $this->normalizeUnitKey($submission->unit_name);

        if (!$headUnitKey || !$submissionUnitKey) {
            return false;
        }

        if ($headUnitKey !== 'forensic_head' && $headUnitKey !== $submissionUnitKey) {
            return false;
        }

        $path = strtolower($request->path());
        if (
            str_contains($path, 'lab_analysis') ||
            str_contains($path, 'evidence_management') ||
            str_contains($path, 'digital_forensics') ||
            str_contains($path, 'crime_scene_support')
        ) {
            $routeCtx = $this->resolveUnitContext($request);
            if ($routeCtx['key'] !== $submissionUnitKey) {
                return false;
            }
        }

        return true;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can access Reviews & Approvals.');
        }

        $unitCtx = $this->resolveUnitContext($request);
        $headUnitKey = $this->normalizeUnitKey($user->unit);
        if ($headUnitKey && $headUnitKey !== 'forensic_head' && $headUnitKey !== $unitCtx['key']) {
            abort(403, 'You are not authorized to review submissions for another unit.');
        }

        $scopeUnit = ($headUnitKey && $headUnitKey !== 'forensic_head')
            ? ($user->unit ?? $unitCtx['sidebar'])
            : $unitCtx['sidebar'];
        $baseQuery = StaffCaseSubmission::forUnit($scopeUnit);

        $pendingSubmissions = (clone $baseQuery)
            ->where('status', 'Pending Approval')
            ->orderByDesc('created_at')
            ->get();

        $reviewedSubmissions = (clone $baseQuery)
            ->whereIn('status', ['Approved', 'Declined'])
            ->orderByDesc('reviewed_at')
            ->limit(25)
            ->get();

        return view('head.reviews_approvals', [
            'unitContext' => $unitCtx,
            'sidebarUnit' => $unitCtx['sidebar'],
            'pendingSubmissions' => $pendingSubmissions,
            'reviewedSubmissions' => $reviewedSubmissions,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $head = Auth::user();
        if (!$head || !$head->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can approve staff case submissions.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($head, $submission, $request)) {
            abort(403, 'You are not authorized to review or approve cases created by staff from another unit.');
        }
        if ($submission->status !== 'Pending Approval') {
            return back()->with('error', "Submission #{$submission->id} has already been {$submission->status}.");
        }

        $payload = is_array($submission->payload) ? $submission->payload : [];
        $payload['approved_by_head'] = true;
        $payload['head_reviewer_id'] = $head->id;

        $replayRequest = Request::create('/cases', 'POST', $payload);
        $submitter = $submission->submitted_by
            ? (StaffAccount::find($submission->submitted_by) ?? User::find($submission->submitted_by))
            : $head;
        $replayRequest->setUserResolver(fn () => $submitter ?: $head);

        $result = app(CaseDocketService::class)->storeCase($replayRequest);
        if (empty($result['success'])) {
            return back()->with('error', $result['message'] ?? 'Failed to save approved case to database.');
        }

        $caseNumber = $result['case']->case_number ?? null;

        $submission->update([
            'status' => 'Approved',
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
            'approved_case_number' => $caseNumber,
        ]);

        return back()->with('status', "Submission #{$submission->id} approved and saved to database as Case {$caseNumber}.");
    }

    public function decline(Request $request, $id)
    {
        $head = Auth::user();
        if (!$head || !$head->is_head) {
            return redirect('/staff/dashboard')->with('error', 'Only Unit Heads can decline staff case submissions.');
        }

        $submission = StaffCaseSubmission::findOrFail($id);
        if (!$this->headCanAccessSubmission($head, $submission, $request)) {
            abort(403, 'You are not authorized to review or approve cases created by staff from another unit.');
        }
        if ($submission->status !== 'Pending Approval') {
            return back()->with('error', "Submission #{$submission->id} has already been {$submission->status}.");
        }

        $submission->update([
            'status' => 'Declined',
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname ?? $head->name ?? 'Unit Head',
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes') ?: 'Declined by Unit Head during review.',
        ]);

        return back()->with('status', "Submission #{$submission->id} has been declined and was not saved to the database.");
    }
}

