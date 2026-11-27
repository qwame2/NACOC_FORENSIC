<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\StaffPasswordResetRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manages staff password reset approval requests for each Head of Unit.
 * Approving a pending request dispatches a 5-minute 6-digit OTP code to the staff member's registered Gmail.
 */
class HeadPasswordRequestsController extends Controller
{
    /**
     * Render the Unit Head Password Reset Approvals page, AJAX table partial, or live JSON poll payload.
     */
    public function index(Request $request)
    {
        $viewer = Auth::user();
        $viewerUnit = $viewer ? ForensicUnit::resolve($viewer->unit) : null;
        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($viewer?->unit);
        $isUnitScoped = $scopedUnitSlug !== null;

        if ($isUnitScoped) {
            $unitFilter = $scopedUnitSlug;
        } else {
            $unitFilter = trim((string) $request->input('unit', 'all'));
            if ($unitFilter === '') {
                $unitFilter = 'all';
            }
        }

        $statusFilter = strtolower(trim((string) $request->input('status', 'all')));
        if (!in_array($statusFilter, ['all', 'pending', 'approved', 'completed', 'declined'], true)) {
            $statusFilter = 'all';
        }

        $search = trim((string) $request->input('search', ''));
        $perPage = max(5, min(50, (int) $request->input('per_page', 10)));

        $stats = $this->computeStats($scopedUnitSlug, $viewer);

        if (!Schema::hasTable('staff_password_reset_requests')) {
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => $request->except('poll'),
            ]);
            $emptySignature = $this->buildPollSignature($emptyPaginator, $stats, $statusFilter, $unitFilter, $search);

            $emptyViewData = [
                'requests' => $emptyPaginator,
                'stats' => $stats,
                'statusFilter' => $statusFilter,
                'unitFilter' => $unitFilter,
                'isUnitScoped' => $isUnitScoped,
                'scopedUnitSlug' => $scopedUnitSlug,
                'search' => $search,
                'perPage' => $perPage,
                'viewer' => $viewer,
                'viewerUnit' => $viewerUnit,
                'currentEndpoint' => '/' . ltrim($request->path(), '/'),
                'initialSignature' => $emptySignature,
            ];

            if ($request->boolean('poll')) {
                return response()->json([
                    'success' => true,
                    'html' => view('head.partials.password_requests_table', $emptyViewData)->render(),
                    'stats' => $stats,
                    'signature' => $emptySignature,
                ]);
            }

            return view('head.password_requests', $emptyViewData);
        }

        $query = StaffPasswordResetRequest::query()
            ->forUnitContext($scopedUnitSlug ?? $unitFilter, $viewer);

        if (!$isUnitScoped && $unitFilter !== 'all') {
            $query->forUnitContext($unitFilter, $viewer);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $like = '%' . strtolower($search) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw('LOWER(fullname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(username, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(service_id, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', [$like]);
            });
        }

        // Show pending requests at the very top, ordered newest first
        $orderedQuery = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END ASC")
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $requests = (clone $orderedQuery)
            ->paginate($perPage)
            ->appends($request->except('poll'));

        if ($requests->isEmpty() && $requests->total() > 0 && $requests->currentPage() > 1) {
            $requests = $orderedQuery
                ->paginate($perPage, ['*'], 'page', $requests->lastPage())
                ->appends($request->except('poll'));
        }

        $signature = $this->buildPollSignature($requests, $stats, $statusFilter, $unitFilter, $search);

        $viewData = [
            'requests' => $requests,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'unitFilter' => $unitFilter,
            'isUnitScoped' => $isUnitScoped,
            'scopedUnitSlug' => $scopedUnitSlug,
            'search' => $search,
            'perPage' => $perPage,
            'viewer' => $viewer,
            'viewerUnit' => $viewerUnit,
            'currentEndpoint' => '/' . ltrim($request->path(), '/'),
            'initialSignature' => $signature,
        ];

        if ($request->boolean('poll')) {
            return response()->json([
                'success' => true,
                'html' => view('head.partials.password_requests_table', $viewData)->render(),
                'stats' => $stats,
                'signature' => $signature,
            ]);
        }

        if ($request->ajax()) {
            return view('head.partials.password_requests_table', $viewData);
        }

        return view('head.password_requests', $viewData);
    }

    /**
     * Approve a staff password reset request and immediately dispatch the 5-minute 6-digit OTP to their Gmail.
     */
    public function approve(Request $request, int $id)
    {
        $actor = Auth::user();
        $isJson = $request->wantsJson() || $request->expectsJson() || $request->ajax();

        $resetRequest = StaffPasswordResetRequest::find($id);
        if (!$resetRequest) {
            $msg = 'Password reset request record was not found.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : back()->with('error', $msg);
        }

        if (!$this->canReviewRequest($actor, $resetRequest)) {
            $msg = 'Unauthorized: Only the approved Head of Unit for this staff member\'s unit can approve this password reset request.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 403)
                : back()->with('error', $msg);
        }

        if ($resetRequest->status === StaffPasswordResetRequest::STATUS_COMPLETED) {
            $msg = 'This password reset request has already been completed by the officer.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $officer = $this->resolveTargetOfficer($resetRequest);
        if (!$officer) {
            $msg = 'The staff officer account linked to this request could not be found.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : back()->with('error', $msg);
        }

        $recipientEmail = trim((string) ($resetRequest->email ?: PasswordResetController::resolveAccountEmailStatic($officer)));
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $msg = 'No valid Gmail address is registered on this staff member\'s account.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        try {
            PasswordResetController::dispatchOtpEmailToAccount($officer, $recipientEmail, $request->ip());
        } catch (\Throwable $e) {
            $mailErr = 'Unable to dispatch the 6-digit OTP email right now. Please verify mail connectivity and try again.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $mailErr], 500)
                : back()->with('error', $mailErr);
        }

        $note = trim((string) $request->input('review_note', ''));
        $wasAlreadyApproved = $resetRequest->status === StaffPasswordResetRequest::STATUS_APPROVED;

        $resetRequest->status = StaffPasswordResetRequest::STATUS_APPROVED;
        $resetRequest->reviewed_by_id = $actor?->id;
        $resetRequest->reviewed_by_name = $actor?->fullname ?? 'Head of Unit';
        $resetRequest->reviewed_at = now();
        $resetRequest->otp_sent_at = now();
        if ($note !== '') {
            $resetRequest->review_note = $note;
        }
        $resetRequest->save();

        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($actor?->unit);
        $stats = $this->computeStats($scopedUnitSlug, $actor);

        $actionVerb = $wasAlreadyApproved ? 'Re-sent a fresh 6-digit OTP code' : 'Approved password reset & sent 6-digit OTP code';
        $message = "{$actionVerb} to {$officer->fullname}'s Gmail ({$recipientEmail}). Valid for " . PasswordResetController::OTP_EXPIRY_MINUTES . ' minutes.';

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => $stats,
            ]);
        }

        return back()->with('status', $message);
    }

    /**
     * Decline a pending or active staff password reset request and revoke any active OTP token.
     */
    public function decline(Request $request, int $id)
    {
        $actor = Auth::user();
        $isJson = $request->wantsJson() || $request->expectsJson() || $request->ajax();

        $resetRequest = StaffPasswordResetRequest::find($id);
        if (!$resetRequest) {
            $msg = 'Password reset request record was not found.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : back()->with('error', $msg);
        }

        if (!$this->canReviewRequest($actor, $resetRequest)) {
            $msg = 'Unauthorized: Only the approved Head of Unit for this staff member\'s unit can decline this password reset request.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 403)
                : back()->with('error', $msg);
        }

        if ($resetRequest->status === StaffPasswordResetRequest::STATUS_COMPLETED) {
            $msg = 'This password reset request has already been completed and cannot be declined.';
            return $isJson
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $officer = $this->resolveTargetOfficer($resetRequest);
        if ($officer) {
            $acctKey = PasswordResetController::buildAccountKeyStatic($officer);
            DB::table('password_reset_tokens')->where('email', $acctKey)->delete();
        }

        $note = trim((string) $request->input('review_note', ''));

        $resetRequest->status = StaffPasswordResetRequest::STATUS_DECLINED;
        $resetRequest->reviewed_by_id = $actor?->id;
        $resetRequest->reviewed_by_name = $actor?->fullname ?? 'Head of Unit';
        $resetRequest->reviewed_at = now();
        if ($note !== '') {
            $resetRequest->review_note = $note;
        }
        $resetRequest->save();

        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($actor?->unit);
        $stats = $this->computeStats($scopedUnitSlug, $actor);

        $message = "Declined password reset request for {$resetRequest->fullname}.";

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => $stats,
            ]);
        }

        return back()->with('status', $message);
    }

    private function resolveTargetOfficer(StaffPasswordResetRequest $resetRequest): StaffAccount|User|null
    {
        if ($resetRequest->staff_account_id && Schema::hasTable('staff_accounts')) {
            $staff = StaffAccount::find($resetRequest->staff_account_id);
            if ($staff) {
                return $staff;
            }
        }

        if ($resetRequest->user_id) {
            return User::find($resetRequest->user_id);
        }

        return null;
    }

    private function canReviewRequest($actor, StaffPasswordResetRequest $resetRequest): bool
    {
        if (!$actor || !$actor->is_head || !$actor->is_approved) {
            return false;
        }

        $actorResolved = ForensicUnit::resolve($actor->unit);
        if ($actorResolved === ForensicUnit::FORENSIC_DIRECTORATE) {
            return true;
        }

        $actorSlug = ForensicUnit::resolveOperationalSlug($actor->unit);
        $targetSlug = $resetRequest->unit_slug ?: StaffPasswordResetRequest::normalizeUnitSlug($resetRequest->unit);

        return $actorSlug !== null && $actorSlug === $targetSlug;
    }

    /**
     * @return array{total:int, pending:int, approved:int, completed:int, declined:int}
     */
    private function computeStats(?string $scopedUnitSlug, $viewer): array
    {
        if (!Schema::hasTable('staff_password_reset_requests')) {
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'declined' => 0,
            ];
        }

        $base = StaffPasswordResetRequest::query()->forUnitContext($scopedUnitSlug, $viewer);

        return [
            'total'     => (clone $base)->count(),
            'pending'   => (clone $base)->where('status', StaffPasswordResetRequest::STATUS_PENDING)->count(),
            'approved'  => (clone $base)->where('status', StaffPasswordResetRequest::STATUS_APPROVED)->count(),
            'completed' => (clone $base)->where('status', StaffPasswordResetRequest::STATUS_COMPLETED)->count(),
            'declined'  => (clone $base)->where('status', StaffPasswordResetRequest::STATUS_DECLINED)->count(),
        ];
    }

    /**
     * Build a deterministic state signature so silent polling only swaps table DOM when rows or OTP expiry state change.
     */
    private function buildPollSignature($paginator, array $stats, string $statusFilter, string $unitFilter, string $search): string
    {
        $rowsState = [];
        foreach ($paginator->items() as $req) {
            $otpWindowActive = $req->status === StaffPasswordResetRequest::STATUS_APPROVED
                && $req->otp_sent_at
                && $req->otp_sent_at->copy()->addMinutes(PasswordResetController::OTP_EXPIRY_MINUTES)->isFuture();

            $rowsState[] = [
                $req->id,
                $req->status,
                $req->email,
                $req->reviewed_by_name,
                optional($req->updated_at)->timestamp,
                optional($req->otp_sent_at)->timestamp,
                optional($req->completed_at)->timestamp,
                $otpWindowActive ? 1 : 0,
            ];
        }

        return md5((string) json_encode([
            'stats' => $stats,
            'filter' => [$statusFilter, $unitFilter, $search, $paginator->currentPage(), $paginator->perPage(), $paginator->total()],
            'rows' => $rowsState,
        ]));
    }
}

