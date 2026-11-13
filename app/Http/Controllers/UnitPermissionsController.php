<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manages per-officer operational forensic permissions governed by each Unit Head.
 */
class UnitPermissionsController extends Controller
{
    /**
     * Display the Unit Head Permissions Console, AJAX table partial, or live JSON poll payload.
     */
    public function index(Request $request)
    {
        $viewer = Auth::user();
        $viewerUnit = $viewer ? ForensicUnit::resolve($viewer->unit) : null;
        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($viewer?->unit);
        $isUnitScoped = $scopedUnitSlug !== null;
        $defaultUnitFilter = $scopedUnitSlug ?? 'all';

        // Operational Unit Heads are strictly restricted to their own unit's staff;
        // only Forensic Directorate Command may inspect or filter across units.
        if ($isUnitScoped) {
            $unitFilter = $scopedUnitSlug;
        } else {
            $unitFilter = $request->has('unit')
                ? trim((string) $request->input('unit', 'all'))
                : 'all';
            if ($unitFilter === '') {
                $unitFilter = 'all';
            }
        }

        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', 'all'));

        $perPage = (int) $request->input('per_page', 12);
        $perPage = max(1, min(100, $perPage));

        $query = $this->baseStaffQuery($scopedUnitSlug);

        if ($search !== '') {
            $like = '%' . strtolower($search) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw('LOWER(fullname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(service_id) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(unit) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$like]);
            });
        }

        $this->applyUnitFilter($query, $unitFilter);
        $this->applyStatusFilter($query, $statusFilter);

        $officers = $query
            ->orderByDesc('is_approved')
            ->orderBy('unit')
            ->orderBy('fullname')
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->computeStats($viewerUnit, $scopedUnitSlug);

        $permissionDefs = User::permissionsForUnit(
            $unitFilter !== 'all' ? $unitFilter : $viewer?->unit,
            $request->path()
        );

        $viewData = [
            'officers'          => $officers,
            'permissionDefs'    => $permissionDefs,
            'search'            => $search,
            'unitFilter'        => $unitFilter,
            'defaultUnitFilter' => $defaultUnitFilter,
            'scopedUnitSlug'    => $scopedUnitSlug,
            'isUnitScoped'      => $isUnitScoped,
            'statusFilter'      => $statusFilter !== '' ? $statusFilter : 'all',
            'perPage'           => $perPage,
            'totalStaffCount'   => $stats['total'],
            'approvedCount'     => $stats['approved'],
            'customizedCount'   => $stats['customized'],
            'myUnitCount'       => $stats['my_unit_total'],
            'unitCounts'        => $stats['units'],
            'viewer'            => $viewer,
            'viewerUnit'        => $viewerUnit,
            'currentEndpoint'   => '/' . ltrim($request->path(), '/'),
        ];

        if ($request->boolean('poll')) {
            return response()->json([
                'html'       => view('partials.permissions_table', $viewData)->render(),
                'stats'      => $stats,
                'pagination' => [
                    'first_item' => $officers->firstItem() ?? 0,
                    'last_item'  => $officers->lastItem() ?? 0,
                    'total'      => $officers->total(),
                ],
            ]);
        }

        if ($request->ajax()) {
            return view('partials.permissions_table', $viewData);
        }

        return view('permissions.index', $viewData);
    }

    /**
     * Persist updated granular permissions for a staff officer within the actor Unit Head's own unit.
     */
    public function update(Request $request, int $id)
    {
        $actor = Auth::user();
        $target = StaffAccount::find($id) ?? User::where('is_head', false)->findOrFail($id);
        $isJson = $request->wantsJson() || $request->ajax();

        if (!FieldOfficersController::canApproveStaff($actor, $target)) {
            $errorMsg = 'Unauthorized: Only an approved Head of Unit from the officer\'s own unit can modify these permissions.';
            if ($isJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return back()->with('error', $errorMsg);
        }

        $permDefs = User::permissionsForUnit($target->unit ?? $actor?->unit, $request->path());
        $keys = array_keys($permDefs);
        $preset = strtolower(trim((string) $request->input('preset', '')));

        if ($preset === 'grant_all') {
            $normalized = array_fill_keys($keys, true);
        } elseif ($preset === 'restrict_all') {
            $normalized = array_fill_keys($keys, false);
        } else {
            $incoming = $request->input('permissions', []);
            if (!is_array($incoming)) {
                $incoming = [];
            }
            $normalized = [];
            foreach ($keys as $key) {
                $val = $incoming[$key] ?? false;
                $normalized[$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $enabledCount = count(array_filter($normalized));
        $totalCount = count($keys);

        $actorUnitSlug = ForensicUnit::resolveOperationalSlug($actor?->unit)
            ?? ForensicUnit::resolveOperationalSlug($target->unit);
        $storedPayload = $normalized;
        if ($actorUnitSlug !== null) {
            $storedPayload['_unit_slug'] = $actorUnitSlug;
            $storedPayload['_updated_by_head_id'] = $actor?->id;
        }

        $target->permissions = $storedPayload;
        $target->save();

        $message = "Updated permissions for {$target->fullname} ({$enabledCount}/{$totalCount} capabilities enabled).";

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'officer' => [
                    'id'          => $target->id,
                    'fullname'    => $target->fullname,
                    'permissions' => $target->effectivePermissions(),
                    'enabled'     => $enabledCount,
                    'total'       => $totalCount,
                ],
            ]);
        }

        return back()->with('status', $message);
    }

    private function baseStaffQuery(?string $scopedUnitSlug = null): Builder
    {
        $terms = ForensicUnit::operationalSearchTerms($scopedUnitSlug);

        return StaffAccount::query()
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                }
            });
    }

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

    private function applyStatusFilter(Builder $query, string $statusFilter): void
    {
        $normalized = strtolower($statusFilter);
        if ($normalized === 'approved') {
            $query->where('is_approved', true);
        } elseif ($normalized === 'pending') {
            $query->where('is_approved', false);
        } elseif ($normalized === 'customized') {
            $query->whereNotNull('permissions');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function computeStats(?ForensicUnit $viewerUnit, ?string $scopedUnitSlug = null): array
    {
        $staffMembers = $this->baseStaffQuery($scopedUnitSlug)->select(['is_approved', 'unit', 'permissions'])->get();

        $units = ['css' => 0, 'em' => 0, 'lab' => 0, 'dpf' => 0];
        $approved = 0;
        $customized = 0;
        $myUnitTotal = 0;

        foreach ($staffMembers as $member) {
            if ($member->is_approved) {
                $approved++;
            }
            if ($member->permissions !== null) {
                $customized++;
            }
            if ($viewerUnit !== null && ForensicUnit::resolve($member->unit) === $viewerUnit) {
                $myUnitTotal++;
            }
            $slug = ForensicUnit::resolveOperationalSlug($member->unit);
            if ($slug !== null && isset($units[$slug])) {
                $units[$slug]++;
            }
        }

        return [
            'total'         => $staffMembers->count(),
            'approved'      => $approved,
            'customized'    => $customized,
            'my_unit_total' => $myUnitTotal,
            'units'         => $units,
        ];
    }
}

