<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Handles the Forensic Department Personnel Details directory showing all system users
 * with unit/role/status filtering, AJAX skeleton-loader partial responses, and real-time polling updates.
 */
class PersonnelRosterController extends Controller
{
    /**
     * Render the full Personnel Details view, AJAX table partial, or JSON polling payload.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $unitFilter = trim((string) $request->input('unit', 'all'));
        $roleFilter = trim((string) $request->input('role', 'all'));
        $statusFilter = trim((string) $request->input('status', 'all'));

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $allSystemUsers = $this->getAllSystemUsers();
        $stats = $this->computeRosterStats($allSystemUsers);

        $filtered = $this->applyFilters($allSystemUsers, $search, $unitFilter, $roleFilter, $statusFilter);
        $sorted = $this->sortUsers($filtered);

        $page = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $users = new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

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
     * Retrieve all distinct users across User and StaffAccount models.
     */
    private function getAllSystemUsers(): Collection
    {
        $headsAndUsers = User::query()->get();
        $staffAccounts = StaffAccount::query()->get();

        $seenServiceIds = [];
        $seenUsernames = [];
        $seenUserIds = [];

        $unified = collect();

        foreach ($headsAndUsers as $user) {
            $unified->push($user);
            if (!empty($user->service_id)) {
                $seenServiceIds[strtolower(trim($user->service_id))] = true;
            }
            if (!empty($user->username)) {
                $seenUsernames[strtolower(trim($user->username))] = true;
            }
            if (!empty($user->id)) {
                $seenUserIds[$user->id] = true;
            }
        }

        foreach ($staffAccounts as $staff) {
            if (!empty($staff->user_id) && isset($seenUserIds[$staff->user_id])) {
                continue;
            }
            $sId = strtolower(trim((string) $staff->service_id));
            if ($sId !== '' && isset($seenServiceIds[$sId])) {
                continue;
            }
            $uName = strtolower(trim((string) $staff->username));
            if ($uName !== '' && isset($seenUsernames[$uName])) {
                continue;
            }

            $staff->is_head = false;
            $unified->push($staff);

            if ($sId !== '') {
                $seenServiceIds[$sId] = true;
            }
            if ($uName !== '') {
                $seenUsernames[$uName] = true;
            }
        }

        return $unified;
    }

    /**
     * Apply search, unit, role, and status filters to the collection.
     */
    private function applyFilters(Collection $users, string $search, string $unitFilter, string $roleFilter, string $statusFilter): Collection
    {
        if ($search !== '') {
            $like = strtolower($search);
            $users = $users->filter(function ($user) use ($like) {
                return str_contains(strtolower((string) $user->fullname), $like)
                    || str_contains(strtolower((string) $user->username), $like)
                    || str_contains(strtolower((string) $user->service_id), $like)
                    || str_contains(strtolower((string) $user->unit), $like)
                    || str_contains(strtolower((string) $user->email), $like)
                    || str_contains(strtolower((string) $user->phone_number), $like);
            });
        }

        if ($unitFilter !== '' && strcasecmp($unitFilter, 'all') !== 0) {
            $resolved = ForensicUnit::resolve($unitFilter);
            $terms = match ($resolved) {
                ForensicUnit::CRIME_SCENE_SUPPORT => ['crime', 'scene', 'cssu'],
                ForensicUnit::EVIDENCE_MANAGEMENT => ['evidence', 'vault', 'emu'],
                ForensicUnit::LAB_ANALYSIS => ['lab', 'chemical', 'lau', 'cflu'],
                ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => ['digital', 'physical', 'dpfu', 'cyber'],
                ForensicUnit::FORENSIC_DIRECTORATE => ['directorate', 'department', 'head', 'fdir', 'fdept'],
                null => strtolower($unitFilter) === 'dg'
                    ? ['director general', 'director-general', 'dg']
                    : [strtolower($unitFilter)],
            };

            $users = $users->filter(function ($user) use ($terms) {
                $unitStr = strtolower((string) $user->unit);
                foreach ($terms as $term) {
                    if (str_contains($unitStr, $term)) {
                        return true;
                    }
                }
                return false;
            });
        }

        $normRole = strtolower($roleFilter);
        if ($normRole === 'head') {
            $users = $users->filter(fn($u) => !empty($u->is_head));
        } elseif ($normRole === 'staff') {
            $users = $users->filter(fn($u) => empty($u->is_head));
        } elseif ($normRole === 'dg') {
            $users = $users->filter(fn($u) => str_contains(strtolower((string)$u->unit), 'director general') || str_contains(strtolower((string)$u->unit), 'director-general'));
        }

        $normStatus = strtolower($statusFilter);
        if ($normStatus === 'approved') {
            $users = $users->filter(fn($u) => !empty($u->is_approved));
        } elseif ($normStatus === 'pending') {
            $users = $users->filter(fn($u) => empty($u->is_approved));
        }

        return $users;
    }

    /**
     * Sort users: Heads/DG first, then Unit, then Fullname.
     */
    private function sortUsers(Collection $users): Collection
    {
        return $users->sort(function ($a, $b) {
            $aHead = !empty($a->is_head) ? 1 : 0;
            $bHead = !empty($b->is_head) ? 1 : 0;
            if ($aHead !== $bHead) {
                return $bHead <=> $aHead;
            }

            $cmpUnit = strcasecmp((string) $a->unit, (string) $b->unit);
            if ($cmpUnit !== 0) {
                return $cmpUnit;
            }

            return strcasecmp((string) $a->fullname, (string) $b->fullname);
        })->values();
    }

    /**
     * Compute aggregate roster counts and per-unit totals across all system users.
     *
     * @return array<string, mixed>
     */
    private function computeRosterStats(Collection $allUsers): array
    {
        $units = [
            'css' => 0,
            'em' => 0,
            'lab' => 0,
            'dpf' => 0,
            'directorate' => 0,
            'dg' => 0,
        ];

        $heads = 0;
        $approved = 0;
        $pending = 0;

        foreach ($allUsers as $u) {
            $isDG = ($u->unit === 'Office of the Director-General' || $u->unit === 'Director General' || str_contains(strtolower((string) $u->unit), 'director general') || str_contains(strtolower((string) $u->unit), 'director-general'));

            if (!empty($u->is_head) || $isDG) {
                $heads++;
            }
            if (!empty($u->is_approved)) {
                $approved++;
            } else {
                $pending++;
            }

            if ($isDG) {
                $units['dg']++;
                continue;
            }

            $unitSlug = match (ForensicUnit::resolve($u->unit)) {
                ForensicUnit::CRIME_SCENE_SUPPORT => 'css',
                ForensicUnit::EVIDENCE_MANAGEMENT => 'em',
                ForensicUnit::LAB_ANALYSIS => 'lab',
                ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'dpf',
                ForensicUnit::FORENSIC_DIRECTORATE => 'directorate',
                null => null,
            };

            if ($unitSlug !== null && isset($units[$unitSlug])) {
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
