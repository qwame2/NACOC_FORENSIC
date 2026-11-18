<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAuditController extends Controller
{
    /**
     * Display the Staff Audit and Activity Logs console for Unit Heads.
     */
    public function index(Request $request)
    {
        $viewer = Auth::user();
        $unitCtx = $this->resolveUnitContext($request, $viewer);
        $sidebarUnit = $unitCtx['sidebar'];
        $scopedUnit = $unitCtx['scopedUnit'];
        $isDirectorate = $unitCtx['isDirectorate'];

        $search = trim((string) $request->input('search', ''));
        $actionFilter = strtoupper(trim((string) $request->input('action', 'ALL')));
        $dateRange = trim((string) $request->input('range', 'all'));
        $unitFilter = $request->input('unit');

        // Operational unit heads are locked to their own unit
        if (!$isDirectorate) {
            $effectiveUnit = $scopedUnit;
        } else {
            $effectiveUnit = (!empty($unitFilter) && strtoupper($unitFilter) !== 'ALL') ? $unitFilter : null;
        }

        $query = StaffAudit::query()
            ->when(!empty($effectiveUnit), fn($q) => $q->forUnit($effectiveUnit))
            ->when(!empty($search), fn($q) => $q->search($search))
            ->when(!empty($actionFilter) && $actionFilter !== 'ALL', fn($q) => $q->action($actionFilter))
            ->when(!empty($dateRange) && $dateRange !== 'all', fn($q) => $q->dateRange($dateRange))
            ->orderByDesc('created_at');

        // Calculate summary metrics
        $statsBaseQuery = StaffAudit::query()
            ->when(!empty($effectiveUnit), fn($q) => $q->forUnit($effectiveUnit));

        $stats = [
            'total'          => (clone $statsBaseQuery)->count(),
            'unique_staff'   => (clone $statsBaseQuery)->whereNotNull('service_id')->distinct('service_id')->count('service_id'),
            'mutations'      => (clone $statsBaseQuery)->whereIn('method', ['POST', 'PUT', 'DELETE', 'PATCH'])->count(),
            'today'          => (clone $statsBaseQuery)->whereDate('created_at', today())->count(),
        ];

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(5, min(100, $perPage));

        $logs = $query->paginate($perPage)->withQueryString();

        $viewData = [
            'logs'          => $logs,
            'stats'         => $stats,
            'unitContext'   => $unitCtx,
            'sidebarUnit'   => $sidebarUnit,
            'isDirectorate' => $isDirectorate,
            'effectiveUnit' => $effectiveUnit,
            'search'        => $search,
            'actionFilter'  => $actionFilter,
            'dateRange'     => $dateRange,
            'perPage'       => $perPage,
        ];

        if ($request->ajax()) {
            return response()
                ->view('partials.staff_audit_table', $viewData)
                ->header('X-Audit-Total', (string) ($stats['total'] ?? 0))
                ->header('X-Audit-Staff', (string) ($stats['unique_staff'] ?? 0))
                ->header('X-Audit-Mutations', (string) ($stats['mutations'] ?? 0))
                ->header('X-Audit-Today', (string) ($stats['today'] ?? 0))
                ->header('X-Audit-Current-Page', (string) $logs->currentPage())
                ->header('X-Audit-Last-Page', (string) $logs->lastPage());
        }

        // Return the unit-specific view if available, or fall back to head/staff_audit or staff_audit.index
        $unitViewKey = match ($unitCtx['key']) {
            'lab_analysis'        => 'Lab_Analysis.staff_audit',
            'evidence_management' => 'Evidence_Management.staff_audit',
            'digital_forensics'   => 'Digital_Forensics.staff_audit',
            'forensic_head'       => 'Forensic_Head.staff_audit',
            default               => 'Crime_Scene_Support.staff_audit',
        };

        if (view()->exists($unitViewKey)) {
            return view($unitViewKey, $viewData);
        }

        if (view()->exists('head.staff_audit')) {
            return view('head.staff_audit', $viewData);
        }

        return view('staff_audit.index', $viewData);
    }

    /**
     * Resolve unit context for the request.
     */
    protected function resolveUnitContext(Request $request, ?User $user): array
    {
        $path = $request->path();

        if (str_contains($path, 'Lab_Analysis') || str_contains($path, 'lab-analysis')) {
            return [
                'key'           => 'lab_analysis',
                'label'         => 'Forensic Chemistry & Laboratory Analysis',
                'sidebar'       => 'Lab Analysis',
                'scopedUnit'    => 'Lab Analysis',
                'isDirectorate' => false,
            ];
        }

        if (str_contains($path, 'Evidence_Management') || str_contains($path, 'evidence-management')) {
            return [
                'key'           => 'evidence_management',
                'label'         => 'Evidence Lodgement & Vault Custody',
                'sidebar'       => 'Evidence Management',
                'scopedUnit'    => 'Evidence Management',
                'isDirectorate' => false,
            ];
        }

        if (str_contains($path, 'Digital_Forensics') || str_contains($path, 'digital-forensics')) {
            return [
                'key'           => 'digital_forensics',
                'label'         => 'Digital Forensics & Cyber Investigation',
                'sidebar'       => 'Digital Forensics',
                'scopedUnit'    => 'Digital Forensics',
                'isDirectorate' => false,
            ];
        }

        if (str_contains($path, 'Forensic_Head') || str_contains($path, 'forensic-head')) {
            return [
                'key'           => 'forensic_head',
                'label'         => 'Forensic Directorate Command & Oversight',
                'sidebar'       => 'Forensic Directorate',
                'scopedUnit'    => null,
                'isDirectorate' => true,
            ];
        }

        if (str_contains($path, 'Crime_Scene_Support') || str_contains($path, 'crime-scene-support')) {
            return [
                'key'           => 'crime_scene_support',
                'label'         => 'Crime Scene Support & Documentation Unit',
                'sidebar'       => 'Crime Scene Support',
                'scopedUnit'    => 'Crime Scene Support',
                'isDirectorate' => false,
            ];
        }

        // Fallback from authenticated user's unit
        $u = strtolower((string) ($user?->unit ?? ''));
        if (str_contains($u, 'directorate') || str_contains($u, 'head of forensic') || ($user?->is_head && str_contains($u, 'forensic'))) {
            return [
                'key'           => 'forensic_head',
                'label'         => 'Forensic Directorate Command & Oversight',
                'sidebar'       => 'Forensic Directorate',
                'scopedUnit'    => null,
                'isDirectorate' => true,
            ];
        }

        if (str_contains($u, 'lab')) {
            return [
                'key'           => 'lab_analysis',
                'label'         => 'Forensic Chemistry & Laboratory Analysis',
                'sidebar'       => 'Lab Analysis',
                'scopedUnit'    => 'Lab Analysis',
                'isDirectorate' => false,
            ];
        }

        if (str_contains($u, 'evidence')) {
            return [
                'key'           => 'evidence_management',
                'label'         => 'Evidence Lodgement & Vault Custody',
                'sidebar'       => 'Evidence Management',
                'scopedUnit'    => 'Evidence Management',
                'isDirectorate' => false,
            ];
        }

        if (str_contains($u, 'digital') || str_contains($u, 'physical')) {
            return [
                'key'           => 'digital_forensics',
                'label'         => 'Digital Forensics & Cyber Investigation',
                'sidebar'       => 'Digital Forensics',
                'scopedUnit'    => 'Digital Forensics',
                'isDirectorate' => false,
            ];
        }

        return [
            'key'           => 'crime_scene_support',
            'label'         => 'Crime Scene Support & Documentation Unit',
            'sidebar'       => 'Crime Scene Support',
            'scopedUnit'    => 'Crime Scene Support',
            'isDirectorate' => false,
        ];
    }
}

