<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\CaseHandover;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Unit Head & Directorate Notifications center powered strictly by real database records:
 * cross-unit exhibit/suspect alerts, pending case handovers, pending case sign-offs,
 * and pending staff account approvals.
 */
class NotificationsController extends Controller
{
    private const PENDING_CASE_STATUSES = [
        'Awaiting Sign-off',
        'Pending Approval',
        'Pending',
    ];

    /**
     * Render the notifications directory, partial feed (AJAX), or polling JSON payload.
     */
    public function index(Request $request)
    {
        $viewer = Auth::user();
        $context = $this->resolveContext($request, $viewer);

        $category = trim((string) $request->input('category', 'all'));
        $statusFilter = trim((string) $request->input('status', 'all'));
        $search = trim((string) $request->input('search', ''));
        $perPage = max(5, min(50, (int) $request->input('per_page', 15)));

        $allNotifications = static::collectNotificationsForContext($context['unit_key'], $viewer);
        $readIds = collect((array) session('read_notification_ids', []))->map(fn ($id) => (string) $id)->flip();

        $allNotifications = $allNotifications->map(function (array $item) use ($readIds) {
            $item['is_read'] = $readIds->has((string) $item['id']);
            return $item;
        });

        $stats = [
            'total'          => $allNotifications->count(),
            'unread'         => $allNotifications->where('is_read', false)->count(),
            'cross_unit'     => $allNotifications->where('category', 'cross_unit')->count(),
            'handover'       => $allNotifications->where('category', 'handover')->count(),
            'case_approval'  => $allNotifications->where('category', 'case_approval')->count(),
            'staff_approval' => $allNotifications->where('category', 'staff_approval')->count(),
        ];

        $filtered = $allNotifications;

        if ($category !== '' && $category !== 'all') {
            $filtered = $filtered->where('category', $category);
        }

        if ($statusFilter === 'unread') {
            $filtered = $filtered->where('is_read', false);
        } elseif ($statusFilter === 'read') {
            $filtered = $filtered->where('is_read', true);
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $filtered = $filtered->filter(function (array $item) use ($needle) {
                $haystack = strtolower(implode(' ', array_filter([
                    $item['title'] ?? '',
                    $item['message'] ?? '',
                    $item['source_unit'] ?? '',
                    $item['case_number'] ?? '',
                    $item['property_id'] ?? '',
                    $item['actor_name'] ?? '',
                    $item['suspect_name'] ?? '',
                ])));
                return str_contains($haystack, $needle);
            });
        }

        $filtered = $filtered->values();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $sigRows = [];
        foreach ($paginated->items() as $nItem) {
            $sigRows[] = [
                $nItem['id'] ?? '',
                !empty($nItem['is_read']) ? 1 : 0,
                (int) ($nItem['timestamp'] ?? 0),
            ];
        }
        $initialSignature = md5((string) json_encode([
            'stats'   => $stats,
            'filters' => [
                $category !== '' ? $category : 'all',
                $statusFilter !== '' ? $statusFilter : 'all',
                $search,
                $perPage,
                $paginated->currentPage(),
                $paginated->total(),
            ],
            'rows'    => $sigRows,
        ]));

        $viewData = [
            'notifications'    => $paginated,
            'stats'            => $stats,
            'category'         => $category !== '' ? $category : 'all',
            'statusFilter'     => $statusFilter !== '' ? $statusFilter : 'all',
            'search'           => $search,
            'perPage'          => $perPage,
            'context'          => $context,
            'viewer'           => $viewer,
            'currentEndpoint'  => '/' . ltrim($request->path(), '/'),
            'fieldOfficersUrl' => $this->resolveFieldOfficersUrl($context['unit_key']),
            'initialSignature' => $initialSignature,
        ];

        if ($request->boolean('poll') || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'html'      => view('partials.notifications_feed', $viewData)->render(),
                'stats'     => $stats,
                'signature' => $initialSignature,
            ]);
        }

        return view('notifications.index', $viewData);
    }

    /**
     * Mark one or all current notifications as read in the user's session.
     */
    public function markRead(Request $request)
    {
        $viewer = Auth::user();
        $existing = collect((array) session('read_notification_ids', []))->map(fn ($id) => (string) $id);

        if ($request->boolean('all')) {
            $context = $this->resolveContext($request, $viewer);
            $currentIds = static::collectNotificationsForContext($context['unit_key'], $viewer)->pluck('id')->map(fn ($id) => (string) $id);
            $merged = $existing->merge($currentIds)->unique()->values()->all();
            session(['read_notification_ids' => $merged]);

            return response()->json([
                'success'     => true,
                'unreadCount' => 0,
            ]);
        }

        $id = trim((string) $request->input('id', ''));
        if ($id === '') {
            return response()->json(['success' => false, 'message' => 'Notification ID is required.'], 422);
        }

        $merged = $existing->push($id)->unique()->values()->all();
        session(['read_notification_ids' => $merged]);

        $context = $this->resolveContext($request, $viewer);
        $unreadCount = static::countActiveForContext($context['unit_label'], $viewer, $context['unit_key']);

        return response()->json([
            'success'     => true,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Acknowledge a pending CaseHandover record in the database.
     */
    public function acknowledgeHandover(Request $request, int $id)
    {
        $handover = CaseHandover::find($id);
        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Handover record not found.'], 404);
        }

        $viewer = Auth::user();
        if ($viewer && !$viewer->is_head) {
            abort(403, 'Only Unit Heads or Directorate Heads may acknowledge handovers here.');
        }

        if (is_null($handover->acknowledged_at)) {
            $handover->acknowledged_at = now();
            $handover->status = 'Acknowledged';
            $notes = trim((string) $request->input('response_notes', ''));
            if ($notes !== '') {
                $handover->response_notes = $notes;
            }
            $handover->save();
        }

        return response()->json([
            'success'  => true,
            'message'  => "Handover for {$handover->case_number} acknowledged.",
            'handover' => $handover,
        ]);
    }

    /**
     * Return live JSON payload of all sidebar badge counts for real-time background sync without page refreshes.
     */
    public function badgeCounts(Request $request)
    {
        $viewer = Auth::user();
        if (!$viewer) {
            return response()->json([
                'success' => false,
                'notifications' => 0,
                'reviews_approvals' => 0,
                'field_officers' => 0,
            ], 401);
        }

        $context = $this->resolveContext($request, $viewer);
        $unitKey = $context['unit_key'] ?? 'css';
        $unitLabel = $context['unit_label'] ?? ($viewer->unit ?? 'Crime Scene Support');

        $notificationsCount = static::countActiveForContext($unitLabel, $viewer, $unitKey);

        $approvalsCount = \App\Models\StaffCaseSubmission::forUnit((string) $unitLabel)
            ->where('status', 'Pending Approval')
            ->count();

        $fieldOfficersCount = \App\Http\Controllers\FieldOfficersController::countPendingApprovalsForContext((string) $unitLabel, $viewer);

        $passwordRequestsCount = \App\Models\StaffPasswordResetRequest::countPendingForContext((string) $unitLabel, $viewer);

        $rollbackCount = \App\Models\StaffCaseSubmission::countRollbacksForContext((string) $unitLabel, $viewer);

        return response()->json([
            'success' => true,
            'notifications' => $notificationsCount,
            'reviews_approvals' => $approvalsCount,
            'field_officers' => $fieldOfficersCount,
            'password_requests' => $passwordRequestsCount,
            'case_rollback' => $rollbackCount,
            'staff_rollback' => $rollbackCount,
            'vault_pending' => \App\Models\Exhibit::countPendingQrOrClassification(),
            'unit' => $unitLabel,
        ]);
    }

    /**
     * Count active unread notifications for sidebar badge rendering.
     */
    public static function countActiveForContext(?string $unitName, ?Authenticatable $viewer = null, ?string $explicitKey = null): int
    {
        $unitKey = $explicitKey ?: static::resolveUnitKey($unitName, $viewer);
        $all = static::collectNotificationsForContext($unitKey, $viewer);
        $readIds = collect((array) session('read_notification_ids', []))->map(fn ($id) => (string) $id)->flip();

        return $all->reject(fn (array $item) => $readIds->has((string) $item['id']))->count();
    }

    /**
     * Collect real database notifications across cross-unit exhibits, handovers,
     * pending case sign-offs, and pending staff account approvals.
     */
    public static function collectNotificationsForContext(string $unitKey, ?Authenticatable $viewer = null): Collection
    {
        $items = collect();

        // 1. Cross-unit domain exhibit & suspect intake notifications
        if (in_array($unitKey, ['lab', 'digital'], true)) {
            $domainAlerts = EvidenceManagementController::buildUnitExhibitAlerts($unitKey);
            foreach ($domainAlerts as $alert) {
                $isLab = $unitKey === 'lab';
                $sourceUnit = (string) ($alert['source_unit'] ?? '');
                $isUnassigned = ($alert['alert_type'] ?? '') === 'unassigned_exhibit';

                if ($isUnassigned) {
                    $title = $isLab
                        ? 'New Drug-Related Exhibit Without Suspect'
                        : 'New Electronic-Device Exhibit Without Suspect';
                    $commodity = trim(implode(' • ', array_filter([
                        $alert['commodity_name'] ?? '',
                        $alert['exhibit_category'] ?? '',
                    ])));
                    $message = ($sourceUnit !== '' ? "{$sourceUnit} entered a " : 'Entered a ')
                        . ($isLab ? 'drug-related exhibit (without a suspect)' : 'electronic-device-related exhibit (without a suspect)')
                        . ($commodity !== '' ? ": {$commodity}" : '.');
                    $actionUrl = $isLab ? url('/Lab_Analysis/create') : url('/Digital_Forensics/create');
                    $actionLabel = 'Open Case Intake';
                } else {
                    $title = $isLab
                        ? 'Suspect Associated With Drug Exhibits'
                        : 'Suspect Associated With Electronic Devices';
                    $suspectName = (string) ($alert['suspect_name'] ?? '');
                    $summary = implode(', ', (array) ($alert['exhibits_summary'] ?? []));
                    $message = ($sourceUnit !== '' ? "{$sourceUnit} entered suspect " : 'Suspect ')
                        . $suspectName
                        . ($isLab ? ' associated with drugs' : ' associated with electronic devices')
                        . ($summary !== '' ? " ({$summary})" : '.');
                    $actionUrl = (string) ($alert['review_url'] ?? '');
                    $actionLabel = 'Review Case';
                }

                $items->push([
                    'id'           => 'cross-' . ($alert['id'] ?? ''),
                    'category'     => 'cross_unit',
                    'severity'     => $isUnassigned ? 'amber' : 'emerald',
                    'title'        => $title,
                    'message'      => $message,
                    'source_unit'  => $sourceUnit,
                    'case_number'  => $alert['case_number'] ?? '',
                    'property_id'  => $alert['property_id'] ?? '',
                    'suspect_name' => $alert['suspect_name'] ?? '',
                    'actor_name'   => $alert['entered_by_name'] ?? '',
                    'action_url'   => $actionUrl,
                    'action_label' => $actionLabel,
                    'handover_id'  => null,
                    'time_human'   => $alert['created_at_human'] ?? '',
                    'timestamp'    => (int) ($alert['timestamp'] ?? 0),
                ]);
            }
        } else {
            // Evidence Management, Crime Scene Support, and Forensic Directorate see recent unassigned exhibits without suspects
            $unassignedQuery = Exhibit::with(['singleSuspectCase.user', 'multipleSuspectCase.user'])
                ->unassignedOrAwaitingSuspect()
                ->orderByDesc('id')
                ->limit(25)
                ->get();

            foreach ($unassignedQuery as $exhibit) {
                if ($unitKey !== 'forensic_head' && ($exhibit->isDrugRelated() || $exhibit->isElectronicDeviceRelated())) {
                    continue;
                }

                $sourceUnit = $exhibit->enteredByUnitName();
                $creator = $exhibit->singleSuspectCase?->user ?? $exhibit->multipleSuspectCase?->user;
                $commodity = trim(implode(' • ', array_filter([
                    $exhibit->commodity_name ?? '',
                    $exhibit->exhibit_category ?? '',
                ])));
                $targetUrl = match ($unitKey) {
                    'evidence' => url('/Evidence_Management/vault'),
                    'forensic_head' => url('/Forensic_Head/cases'),
                    default => url('/Crime_Scene_Support/create'),
                };

                $items->push([
                    'id'           => 'exhibit-' . $exhibit->id,
                    'category'     => 'cross_unit',
                    'severity'     => 'amber',
                    'title'        => 'Exhibit Entered Without Suspect',
                    'message'      => ($sourceUnit !== '' ? "{$sourceUnit} logged exhibit" : 'Logged exhibit')
                        . ($commodity !== '' ? ": {$commodity}" : '')
                        . ' awaiting suspect linkage.',
                    'source_unit'  => $sourceUnit,
                    'case_number'  => $exhibit->singleSuspectCase?->case_number ?? '',
                    'property_id'  => $exhibit->property_id ?? '',
                    'suspect_name' => '',
                    'actor_name'   => $creator?->fullname ?? ($creator?->name ?? ''),
                    'action_url'   => $targetUrl,
                    'action_label' => $unitKey === 'evidence' ? 'Open Exhibit Vault' : 'Open Intake',
                    'handover_id'  => null,
                    'time_human'   => optional($exhibit->created_at)->diffForHumans() ?? '',
                    'timestamp'    => optional($exhibit->created_at)->timestamp ?? 0,
                ]);
            }
        }

        // 2. Unacknowledged Case Handovers directed to this unit or viewer
        $handovers = CaseHandover::with(['fromUser', 'toUser', 'singleSuspectCase', 'multipleSuspectCase'])
            ->whereNull('acknowledged_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($handovers as $handover) {
            $toKey = static::resolveUnitKey($handover->to_unit);
            $matchesRecipient = ($unitKey === 'forensic_head')
                || ($toKey === $unitKey)
                || ($viewer && (int) $handover->to_user_id === (int) $viewer->id);

            if (!$matchesRecipient) {
                continue;
            }

            $caseId = $handover->single_suspect_case_id ?? $handover->multiple_suspect_case_id;
            $items->push([
                'id'           => 'handover-' . $handover->id,
                'category'     => 'handover',
                'severity'     => 'blue',
                'title'        => 'Pending Case Handover Acknowledgement',
                'message'      => trim("Case {$handover->case_number} transferred from {$handover->from_unit} to {$handover->to_unit}" . ($handover->request_notes ? " — {$handover->request_notes}" : '')),
                'source_unit'  => $handover->from_unit ?? '',
                'case_number'  => $handover->case_number ?? '',
                'property_id'  => '',
                'suspect_name' => '',
                'actor_name'   => $handover->fromUser?->fullname ?? '',
                'action_url'   => $caseId ? static::resolveCaseReviewUrl($unitKey, $caseId) : '',
                'action_label' => $caseId ? 'Inspect Case' : '',
                'handover_id'  => $handover->id,
                'time_human'   => optional($handover->created_at)->diffForHumans() ?? '',
                'timestamp'    => optional($handover->created_at)->timestamp ?? 0,
            ]);
        }

        // 3. Pending Case Sign-offs / Approvals for the unit
        $singlePending = SingleSuspectCase::with('user')
            ->whereIn('status', self::PENDING_CASE_STATUSES)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($singlePending as $case) {
            $caseUnitKey = static::resolveUnitKey($case->unit_name);
            if ($unitKey !== 'forensic_head' && $caseUnitKey !== $unitKey) {
                continue;
            }

            $items->push([
                'id'           => 'case-single-' . $case->id,
                'category'     => 'case_approval',
                'severity'     => 'purple',
                'title'        => 'Case Docket Awaiting Review / Sign-Off',
                'message'      => trim("{$case->case_number}: {$case->title} ({$case->status})"),
                'source_unit'  => $case->unit_name ?? '',
                'case_number'  => $case->case_number ?? '',
                'property_id'  => '',
                'suspect_name' => $case->suspect_name ?? '',
                'actor_name'   => $case->user?->fullname ?? ($case->arresting_officer ?? ''),
                'action_url'   => static::resolveCaseReviewUrl($unitKey, $case->id),
                'action_label' => 'Review Docket',
                'handover_id'  => null,
                'time_human'   => optional($case->created_at)->diffForHumans() ?? '',
                'timestamp'    => optional($case->created_at)->timestamp ?? 0,
            ]);
        }

        $multiPending = MultipleSuspectCase::with('user')
            ->whereIn('status', self::PENDING_CASE_STATUSES)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($multiPending as $case) {
            $caseUnitKey = static::resolveUnitKey($case->unit_name);
            if ($unitKey !== 'forensic_head' && $caseUnitKey !== $unitKey) {
                continue;
            }

            $items->push([
                'id'           => 'case-multi-' . $case->id,
                'category'     => 'case_approval',
                'severity'     => 'purple',
                'title'        => 'Multi-Suspect Case Awaiting Review / Sign-Off',
                'message'      => trim("{$case->case_number}: {$case->title} ({$case->status})"),
                'source_unit'  => $case->unit_name ?? '',
                'case_number'  => $case->case_number ?? '',
                'property_id'  => '',
                'suspect_name' => '',
                'actor_name'   => $case->user?->fullname ?? ($case->arresting_officer ?? ''),
                'action_url'   => static::resolveCaseReviewUrl($unitKey, $case->id),
                'action_label' => 'Review Docket',
                'handover_id'  => null,
                'time_human'   => optional($case->created_at)->diffForHumans() ?? '',
                'timestamp'    => optional($case->created_at)->timestamp ?? 0,
            ]);
        }

        // 4. Pending Staff Officer Account Approvals within the unit
        $pendingStaff = StaffAccount::query()
            ->where('is_approved', false)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($pendingStaff as $staff) {
            $staffUnitKey = static::resolveUnitKey($staff->unit);
            if ($unitKey !== 'forensic_head' && $staffUnitKey !== $unitKey) {
                continue;
            }

            $items->push([
                'id'           => 'staff-pending-' . $staff->id,
                'category'     => 'staff_approval',
                'severity'     => 'rose',
                'title'        => 'Staff Officer Awaiting Account Approval',
                'message'      => trim("{$staff->fullname}" . ($staff->service_id ? " ({$staff->service_id})" : '') . " in {$staff->unit} requires Head of Unit activation."),
                'source_unit'  => $staff->unit ?? '',
                'case_number'  => '',
                'property_id'  => '',
                'suspect_name' => '',
                'actor_name'   => $staff->fullname ?? '',
                'action_url'   => static::resolveStaffApprovalUrl($unitKey),
                'action_label' => $unitKey === 'forensic_head' ? 'Open Personnel Roster' : 'Manage Field Officers',
                'handover_id'  => null,
                'time_human'   => optional($staff->created_at)->diffForHumans() ?? '',
                'timestamp'    => optional($staff->created_at)->timestamp ?? 0,
            ]);
        }

        return $items->sortByDesc('timestamp')->values();
    }

    /**
     * Resolve active page context from route prefix, query params, or viewer unit.
     *
     * @return array{unit_key: string, unit_label: string, is_forensic_head: bool}
     */
    private function resolveContext(Request $request, ?Authenticatable $viewer = null): array
    {
        $path = strtolower($request->path());
        $paramUnit = (string) ($request->query('from') ?? $request->query('unit') ?? '');

        if ($path === 'dg' || str_starts_with($path, 'dg/')) {
            return [
                'unit_key'         => 'forensic_head',
                'unit_label'       => 'Office of the Director-General',
                'is_forensic_head' => false,
                'is_dg'            => true,
            ];
        }

        if (str_contains($path, 'forensic_head')) {
            return [
                'unit_key'         => 'forensic_head',
                'unit_label'       => ForensicUnit::FORENSIC_DIRECTORATE->value,
                'is_forensic_head' => true,
                'is_dg'            => false,
            ];
        }

        if ($paramUnit !== '') {
            $unitKey = static::resolveUnitKey($paramUnit, $viewer);
        } elseif (str_contains($path, 'lab_analysis') || str_contains($path, 'lab-analysis')) {
            $unitKey = 'lab';
        } elseif (str_contains($path, 'evidence_management') || str_contains($path, 'evidence-management')) {
            $unitKey = 'evidence';
        } elseif (str_contains($path, 'digital_forensics') || str_contains($path, 'digital-forensics')) {
            $unitKey = 'digital';
        } elseif (str_contains($path, 'crime_scene_support') || str_contains($path, 'crime-scene-support')) {
            $unitKey = 'crime_scene';
        } else {
            $unitKey = static::resolveUnitKey($viewer?->unit, $viewer);
        }

        $unitLabel = match ($unitKey) {
            'lab'           => ForensicUnit::LAB_ANALYSIS->value,
            'evidence'      => ForensicUnit::EVIDENCE_MANAGEMENT->value,
            'digital'       => ForensicUnit::DIGITAL_PHYSICAL_FORENSICS->value,
            'forensic_head' => ForensicUnit::FORENSIC_DIRECTORATE->value,
            default         => ForensicUnit::CRIME_SCENE_SUPPORT->value,
        };

        return [
            'unit_key'         => $unitKey,
            'unit_label'       => $unitLabel,
            'is_forensic_head' => $unitKey === 'forensic_head',
        ];
    }

    /**
     * Normalize any unit string into canonical controller key.
     */
    public static function resolveUnitKey(?string $unitName, ?Authenticatable $viewer = null): string
    {
        $resolved = ForensicUnit::resolve($unitName);
        if ($resolved === ForensicUnit::FORENSIC_DIRECTORATE) {
            return 'forensic_head';
        }
        if ($resolved === ForensicUnit::LAB_ANALYSIS) {
            return 'lab';
        }
        if ($resolved === ForensicUnit::DIGITAL_PHYSICAL_FORENSICS) {
            return 'digital';
        }
        if ($resolved === ForensicUnit::EVIDENCE_MANAGEMENT) {
            return 'evidence';
        }
        if ($resolved === ForensicUnit::CRIME_SCENE_SUPPORT) {
            return 'crime_scene';
        }

        if ($viewer && !empty($viewer->unit)) {
            $viewerResolved = ForensicUnit::resolve($viewer->unit);
            return match ($viewerResolved) {
                ForensicUnit::FORENSIC_DIRECTORATE => 'forensic_head',
                ForensicUnit::LAB_ANALYSIS => 'lab',
                ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'digital',
                ForensicUnit::EVIDENCE_MANAGEMENT => 'evidence',
                default => 'crime_scene',
            };
        }

        return 'crime_scene';
    }

    private static function resolveCaseReviewUrl(string $unitKey, int|string $caseId): string
    {
        return match ($unitKey) {
            'lab'           => route('lab.cases.review', ['id' => $caseId]),
            'digital'       => route('digital.cases.review', ['id' => $caseId]),
            'evidence'      => route('evidence.cases.review', ['id' => $caseId]),
            'forensic_head' => route('forensic_head.cases.review', ['id' => $caseId]),
            default         => route('crime_scene.cases.review', ['id' => $caseId]),
        };
    }

    private static function resolveStaffApprovalUrl(string $unitKey): string
    {
        return match ($unitKey) {
            'lab'           => route('lab_analysis.field_officers'),
            'digital'       => route('digital_forensics.field_officers'),
            'evidence'      => route('evidence_management.field_officers'),
            'forensic_head' => url('/Forensic_Head/personnel-roster'),
            default         => route('crime_scene.field_officers'),
        };
    }

    private function resolveFieldOfficersUrl(string $unitKey): string
    {
        return static::resolveStaffApprovalUrl($unitKey);
    }
}
