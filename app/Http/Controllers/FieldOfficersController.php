<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays staff officers across all operational units with unit/status filtering,
 * AJAX skeleton-loader partials, interval polling, and Head-of-Unit account approval.
 */
class FieldOfficersController extends Controller
{
    /**
     * Render the Field Officers page, AJAX partial table, or JSON polling payload.
     */
    public function index(Request $request)
    {
        $viewer = Auth::user();
        $viewerUnit = $viewer ? ForensicUnit::resolve($viewer->unit) : null;
        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($viewer?->unit);
        $isUnitScoped = $scopedUnitSlug !== null;

        $search = trim((string) $request->input('search', ''));
        $unitFilter = $isUnitScoped
            ? $scopedUnitSlug
            : trim((string) $request->input('unit', 'all'));
        if ($unitFilter === '') {
            $unitFilter = $isUnitScoped ? $scopedUnitSlug : 'all';
        }
        $statusFilter = trim((string) $request->input('status', 'all'));

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = $this->baseStaffQuery($scopedUnitSlug);

        if ($search !== '') {
            $like = '%' . strtolower($search) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw('LOWER(fullname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(service_id) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(unit) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(phone_number, "")) LIKE ?', [$like]);
            });
        }

        $this->applyUnitFilter($query, $unitFilter);
        $this->applyStatusFilter($query, $statusFilter);

        $officers = $query
            ->orderBy('is_approved') // Pending verification surfaces first for Unit Heads
            ->orderBy('unit')
            ->orderBy('fullname')
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->computeStaffStats($viewerUnit, $scopedUnitSlug);

        $viewData = [
            'officers' => $officers,
            'search' => $search,
            'unitFilter' => $unitFilter,
            'defaultUnitFilter' => $scopedUnitSlug ?? 'all',
            'scopedUnitSlug' => $scopedUnitSlug,
            'isUnitScoped' => $isUnitScoped,
            'statusFilter' => $statusFilter !== '' ? $statusFilter : 'all',
            'perPage' => $perPage,
            'totalStaffCount' => $stats['total'],
            'approvedStaffCount' => $stats['approved'],
            'pendingStaffCount' => $stats['pending'],
            'myUnitPendingCount' => $stats['my_unit_pending'],
            'unitCounts' => $stats['units'],
            'viewer' => $viewer,
            'viewerUnit' => $viewerUnit,
            'currentEndpoint' => '/' . ltrim($request->path(), '/'),
        ];

        if ($request->boolean('poll')) {
            return response()->json([
                'html' => view('partials.field_officers_table', $viewData)->render(),
                'stats' => $stats,
                'pagination' => [
                    'first_item' => $officers->firstItem() ?? 0,
                    'last_item' => $officers->lastItem() ?? 0,
                    'total' => $officers->total(),
                ],
            ]);
        }

        if ($request->ajax()) {
            return view('partials.field_officers_table', $viewData);
        }

        return view('field_officers.index', $viewData);
    }

    /**
     * Approve a pending staff officer account (strictly restricted to that staff's Head of Unit).
     */
    public function approve(Request $request, int $id)
    {
        return $this->updateStaffApprovalStatus($request, $id, true);
    }

    /**
     * Revoke/suspend a staff officer account (strictly restricted to that staff's Head of Unit).
     */
    public function revoke(Request $request, int $id)
    {
        return $this->updateStaffApprovalStatus($request, $id, false);
    }

    /**
     * Return live account approval / revocation status for the authenticated user watchdog.
     */
    public function accountStatus()
    {
        $user = Auth::user()?->fresh();
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }

        $resolvedUnit = ForensicUnit::resolve($user->unit);
        $unitHead = ($resolvedUnit && $resolvedUnit !== ForensicUnit::FORENSIC_DIRECTORATE)
            ? User::where('is_head', true)
                ->where('is_approved', true)
                ->get()
                ->first(fn (User $u) => ForensicUnit::resolve($u->unit) === $resolvedUnit)
            : null;

        return response()->json([
            'authenticated' => true,
            'is_approved' => (bool) $user->is_approved,
            'is_revoked' => !$user->is_head && !$user->is_approved,
            'is_head' => (bool) $user->is_head,
            'unit' => $resolvedUnit?->value ?? ($user->unit ?? 'Operational Unit'),
            'unit_head_name' => $unitHead?->fullname,
        ]);
    }

    /**
     * Shared approval status mutator enforcing Head-of-Unit unit-matching authorization.
     */
    private function updateStaffApprovalStatus(Request $request, int $id, bool $approve)
    {
        $actor = Auth::user();
        $target = User::findOrFail($id);
        $isJson = $request->wantsJson() || $request->ajax();

        if (!$this->canApproveStaff($actor, $target)) {
            $errorMsg = 'Unauthorized: Only an approved Head of Unit from the officer\'s own unit can manage this account.';
            if ($isJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return back()->with('error', $errorMsg);
        }

        $target->is_approved = $approve;
        $target->save();

        $actionMsg = $approve
            ? "Staff officer {$target->fullname} has been approved and can now log in."
            : "Staff officer {$target->fullname}'s access has been revoked.";

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => $actionMsg,
                'user' => [
                    'id' => $target->id,
                    'fullname' => $target->fullname,
                    'is_approved' => (bool) $target->is_approved,
                ],
            ], 200);
        }

        return back()->with('status', $actionMsg);
    }

    /**
     * Determine whether `$actor` is an approved Head of Unit for `$target`'s operational unit.
     */
    public static function canApproveStaff(?User $actor, User $target): bool
    {
        if (!$actor || !$actor->is_head || !$actor->is_approved) {
            return false;
        }

        if ($target->is_head) {
            return false;
        }

        $actorUnit = ForensicUnit::resolve($actor->unit);
        $targetUnit = ForensicUnit::resolve($target->unit);

        if ($actorUnit === null || $targetUnit === null || $actorUnit === ForensicUnit::FORENSIC_DIRECTORATE) {
            return false;
        }

        return $actorUnit === $targetUnit;
    }

    /**
     * Base query matching staff officers across all operational units or restricted to a single unit slug.
     */
    private function baseStaffQuery(?string $scopedUnitSlug = null): Builder
    {
        $terms = ForensicUnit::operationalSearchTerms($scopedUnitSlug);

        return User::query()
            ->where('is_head', false)
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                }
            });
    }

    /**
     * Narrow query by a specific operational unit slug (`css`, `em`, `lab`, `dpf`).
     */
    private function applyUnitFilter(Builder $query, string $unitFilter): void
    {
        if ($unitFilter === '' || strcasecmp($unitFilter, 'all') === 0) {
            return;
        }

        $slug = ForensicUnit::resolveOperationalSlug($unitFilter);
        $terms = $slug !== null
            ? ForensicUnit::operationalSearchTerms($slug)
            : [strtolower($unitFilter)];

        $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
            }
        });
    }

    /**
     * Filter query by approval status (`approved` or `pending`).
     */
    private function applyStatusFilter(Builder $query, string $statusFilter): void
    {
        $normalized = strtolower($statusFilter);
        if ($normalized === 'approved') {
            $query->where('is_approved', true);
        } elseif ($normalized === 'pending') {
            $query->where('is_approved', false);
        }
    }

    /**
     * Compute aggregate staff officer stats scoped to `$scopedUnitSlug` (or all operational units when null).
     *
     * @return array<string, mixed>
     */
    private function computeStaffStats(?ForensicUnit $viewerUnit, ?string $scopedUnitSlug = null): array
    {
        $staffMembers = $this->baseStaffQuery($scopedUnitSlug)->select(['is_approved', 'unit'])->get();

        $units = [
            'css' => 0,
            'em' => 0,
            'lab' => 0,
            'dpf' => 0,
        ];

        $approved = 0;
        $pending = 0;
        $myUnitPending = 0;

        foreach ($staffMembers as $member) {
            if ($member->is_approved) {
                $approved++;
            } else {
                $pending++;
                if ($viewerUnit !== null && ForensicUnit::resolve($member->unit) === $viewerUnit) {
                    $myUnitPending++;
                }
            }

            $slug = ForensicUnit::resolveOperationalSlug($member->unit);
            if ($slug !== null && isset($units[$slug])) {
                $units[$slug]++;
            }
        }

        return [
            'total' => $staffMembers->count(),
            'approved' => $approved,
            'pending' => $pending,
            'my_unit_pending' => $myUnitPending,
            'units' => $units,
        ];
    }
}

