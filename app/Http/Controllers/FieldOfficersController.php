<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
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

        $sigRows = [];
        foreach ($officers->items() as $oItem) {
            $revReason = !$oItem->is_approved
                ? \Illuminate\Support\Facades\Cache::get(AuthController::staffRevokedReasonCacheKey($oItem))
                : null;
            $sigRows[] = [
                $oItem->id,
                (int) ((bool) $oItem->is_approved),
                (int) (method_exists($oItem, 'isOnline') ? $oItem->isOnline() : false),
                (string) ($revReason ?? ''),
                optional($oItem->updated_at)->timestamp,
            ];
        }
        $initialSignature = md5((string) json_encode([
            'stats' => $stats,
            'filters' => [$search, $unitFilter, $statusFilter, $perPage, $officers->currentPage(), $officers->total()],
            'rows' => $sigRows,
        ]));

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
            'onlineStaffCount' => $stats['online'] ?? 0,
            'myUnitPendingCount' => $stats['my_unit_pending'],
            'unitCounts' => $stats['units'],
            'viewer' => $viewer,
            'viewerUnit' => $viewerUnit,
            'currentEndpoint' => '/' . ltrim($request->path(), '/'),
            'initialSignature' => $initialSignature,
        ];

        if ($request->boolean('poll')) {
            return response()->json([
                'html' => view('partials.field_officers_table', $viewData)->render(),
                'stats' => $stats,
                'signature' => $initialSignature,
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
    public function accountStatus(Request $request)
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

        $effectivePerms = method_exists($user, 'effectivePermissions')
            ? $user->effectivePermissions()
            : [];
        $pathContext = (string) ($request->query('path') ?: $request->query('unit') ?: '');
        $checkPerm = static fn (string $perm): bool => (bool) (
            $user->is_head
            || (method_exists($user, 'hasPermission')
                ? $user->hasPermission($perm, $pathContext !== '' ? $pathContext : null)
                : ($effectivePerms[$perm] ?? true))
        );

        return response()->json([
            'authenticated' => true,
            'is_approved' => (bool) $user->is_approved,
            'is_revoked' => !$user->is_head && !$user->is_approved,
            'is_head' => (bool) $user->is_head,
            'unit' => $resolvedUnit?->value ?? ($user->unit ?? 'Operational Unit'),
            'unit_slug' => ForensicUnit::resolveOperationalSlug($user->unit),
            'unit_head_name' => $unitHead?->fullname,
            'permissions' => $effectivePerms,
            'can_create_cases' => $checkPerm('create_cases'),
            'can_edit_cases' => $checkPerm('edit_cases'),
            'can_add_associate' => $checkPerm('add_associate'),
            'can_manage_exhibits' => $checkPerm('manage_exhibits'),
            'can_record_lab_analysis' => $checkPerm('record_lab_analysis'),
            'can_generate_reports' => $checkPerm('generate_reports'),
        ]);
    }

    /**
     * Shared approval status mutator enforcing Head-of-Unit unit-matching authorization.
     */
    private function updateStaffApprovalStatus(Request $request, int $id, bool $approve)
    {
        $actor = Auth::user();
        $target = StaffAccount::find($id) ?? User::where('is_head', false)->findOrFail($id);
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

        $attemptsKey = AuthController::staffAttemptsCacheKey($target);
        $revokedReasonKey = AuthController::staffRevokedReasonCacheKey($target);
        \Illuminate\Support\Facades\Cache::forget($attemptsKey);

        if ($approve) {
            \Illuminate\Support\Facades\Cache::forget($revokedReasonKey);
            $nowTs = time();
            \Illuminate\Support\Facades\Cache::put('nacoc_latest_staff_approval', [
                'exists' => true,
                'is_approved' => true,
                'is_head' => (bool) $target->is_head,
                'is_staff' => true,
                'unit' => $target->unit,
                'fullname' => $target->fullname,
                'service_id' => $target->service_id,
                'username' => $target->username,
                'approved_at' => $nowTs,
                'event_id' => $target->id . ':' . $nowTs,
            ], now()->addSeconds(60));
        } else {
            \Illuminate\Support\Facades\Cache::forever($revokedReasonKey, 'manual');
        }

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
                    'service_id' => $target->service_id,
                    'username' => $target->username,
                    'unit' => $target->unit,
                    'is_head' => (bool) $target->is_head,
                    'is_approved' => (bool) $target->is_approved,
                ],
            ], 200);
        }

        return back()->with('status', $actionMsg);
    }

    /**
     * Determine whether `$actor` is an approved Head of Unit for `$target`'s operational unit.
     */
    public static function canApproveStaff(?Authenticatable $actor, Authenticatable $target): bool
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
     * Count staff officer accounts awaiting head approval for the given viewer/sidebar context.
     */
    public static function countPendingApprovalsForContext(?string $contextUnit = null, ?Authenticatable $viewer = null): int
    {
        $scopedUnitSlug = $viewer
            ? ForensicUnit::resolveOperationalSlug($viewer->unit)
            : ForensicUnit::resolveOperationalSlug($contextUnit);

        return self::buildBaseStaffQuery($scopedUnitSlug)
            ->where('is_approved', false)
            ->count();
    }

    /**
     * Base query matching staff officers across all operational units or restricted to a single unit slug.
     */
    private function baseStaffQuery(?string $scopedUnitSlug = null): Builder
    {
        return self::buildBaseStaffQuery($scopedUnitSlug);
    }

    /**
     * Static base query builder for staff officers in operational units.
     */
    private static function buildBaseStaffQuery(?string $scopedUnitSlug = null): Builder
    {
        $terms = ForensicUnit::operationalSearchTerms($scopedUnitSlug);

        return StaffAccount::query()
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
        $selectCols = ['id', 'is_approved', 'unit'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('staff_accounts', 'last_seen_at')) {
            $selectCols[] = 'last_seen_at';
        }
        $staffMembers = $this->baseStaffQuery($scopedUnitSlug)->select($selectCols)->get();

        $units = [
            'css' => 0,
            'em' => 0,
            'lab' => 0,
            'dpf' => 0,
        ];

        $approved = 0;
        $pending = 0;
        $online = 0;
        $myUnitPending = 0;

        foreach ($staffMembers as $member) {
            if ($member->is_approved) {
                $approved++;
                if (method_exists($member, 'isOnline') && $member->isOnline()) {
                    $online++;
                }
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
            'online' => $online,
            'my_unit_pending' => $myUnitPending,
            'units' => $units,
        ];
    }
}

