<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Handles the Forensic Directorate Personnel Roster directory with unit/role/status filtering,
 * AJAX skeleton-loader partial responses, and real-time polling updates.
 */
class PersonnelRosterController extends Controller
{
    /**
     * Render the full Personnel Roster view, AJAX table partial, or JSON polling payload.
     */
    public function index(Request $request)
    {
        $viewer = $request->user();
        $scopedUnitSlug = ForensicUnit::resolveOperationalSlug($viewer?->unit);

        $search = trim((string) $request->input('search', ''));
        $unitFilter = $scopedUnitSlug ?? trim((string) $request->input('unit', 'all'));
        $roleFilter = trim((string) $request->input('role', 'all'));
        $statusFilter = trim((string) $request->input('status', 'all'));

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = User::query();

        if ($scopedUnitSlug !== null) {
            $terms = ForensicUnit::operationalSearchTerms($scopedUnitSlug);
            $query->where('is_head', false)->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                }
            });
        }

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
        $this->applyRoleFilter($query, $roleFilter);
        $this->applyStatusFilter($query, $statusFilter);

        $users = $query
            ->orderByDesc('is_head')
            ->orderBy('unit')
            ->orderBy('fullname')
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->computeRosterStats($scopedUnitSlug);

        $viewData = [
            'users' => $users,
            'search' => $search,
            'unitFilter' => $unitFilter !== '' ? $unitFilter : 'all',
            'roleFilter' => $roleFilter !== '' ? $roleFilter : 'all',
            'statusFilter' => $statusFilter !== '' ? $statusFilter : 'all',
            'perPage' => $perPage,
            'totalUsersCount' => $stats['total'],
            'unitHeadsCount' => $stats['heads'],
            'approvedUsersCount' => $stats['approved'],
            'pendingUsersCount' => $stats['pending'],
            'unitCounts' => $stats['units'],
        ];

        if ($request->boolean('poll')) {
            return response()->json([
                'html' => view('Forensic_Head.partials.personnel_table', $viewData)->render(),
                'stats' => $stats,
                'pagination' => [
                    'first_item' => $users->firstItem() ?? 0,
                    'last_item' => $users->lastItem() ?? 0,
                    'total' => $users->total(),
                ],
            ]);
        }

        if ($request->ajax()) {
            return view('Forensic_Head.partials.personnel_table', $viewData);
        }

        return view('Forensic_Head.personnel_roster', $viewData);
    }

    /**
     * Filter query by normalized operational unit or Forensic Directorate.
     */
    private function applyUnitFilter(Builder $query, string $unitFilter): void
    {
        if ($unitFilter === '' || strcasecmp($unitFilter, 'all') === 0) {
            return;
        }

        $resolved = ForensicUnit::resolve($unitFilter);
        $terms = match ($resolved) {
            ForensicUnit::CRIME_SCENE_SUPPORT => ['crime', 'scene', 'cssu'],
            ForensicUnit::EVIDENCE_MANAGEMENT => ['evidence', 'vault', 'emu'],
            ForensicUnit::LAB_ANALYSIS => ['lab', 'chemical', 'lau', 'cflu'],
            ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => ['digital', 'physical', 'dpfu', 'cyber'],
            ForensicUnit::FORENSIC_DIRECTORATE => ['directorate', 'department', 'head', 'fdir', 'fdept'],
            null => [strtolower($unitFilter)],
        };

        $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhereRaw('LOWER(unit) LIKE ?', ['%' . $term . '%']);
            }
        });
    }

    /**
     * Filter query by officer command role (`head` or `staff`).
     */
    private function applyRoleFilter(Builder $query, string $roleFilter): void
    {
        $normalized = strtolower($roleFilter);
        if ($normalized === 'head') {
            $query->where('is_head', true);
        } elseif ($normalized === 'staff') {
            $query->where('is_head', false);
        }
    }

    /**
     * Filter query by account approval state (`approved` or `pending`).
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
     * Compute aggregate roster counts and per-unit totals across all system users.
     *
     * @return array<string, mixed>
     */
    private function computeRosterStats(?string $scopedUnitSlug = null): array
    {
        $query = User::query()->select(['is_head', 'is_approved', 'unit']);
        if ($scopedUnitSlug !== null) {
            $terms = ForensicUnit::operationalSearchTerms($scopedUnitSlug);
            $query->where('is_head', false)->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                }
            });
        }
        $allUsers = $query->get();

        $units = [
            'css' => 0,
            'em' => 0,
            'lab' => 0,
            'dpf' => 0,
            'directorate' => 0,
        ];

        $heads = 0;
        $approved = 0;
        $pending = 0;

        foreach ($allUsers as $u) {
            if ($u->is_head) {
                $heads++;
            }
            if ($u->is_approved) {
                $approved++;
            } else {
                $pending++;
            }

            $unitSlug = match (ForensicUnit::resolve($u->unit)) {
                ForensicUnit::CRIME_SCENE_SUPPORT => 'css',
                ForensicUnit::EVIDENCE_MANAGEMENT => 'em',
                ForensicUnit::LAB_ANALYSIS => 'lab',
                ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'dpf',
                ForensicUnit::FORENSIC_DIRECTORATE => 'directorate',
                null => null,
            };

            if ($unitSlug !== null) {
                $units[$unitSlug]++;
            }
        }

        return [
            'total' => $allUsers->count(),
            'heads' => $heads,
            'approved' => $approved,
            'pending' => $pending,
            'units' => $units,
        ];
    }
}

