<?php

namespace App\Http\Middleware;

use App\Models\StaffAccount;
use App\Models\StaffAudit;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogStaffActivity
{
    /**
     * Paths excluded from staff activity logging.
     * Excludes background AJAX polling, notification timers, health checks, and audit pages.
     */
    protected array $excludedPatterns = [
        '*staff-audit-logs*',
        'staff-audit-logs*',
        '*staff-audit*',
        '*api/dashboard/realtime*',
        '*dashboard/realtime*',
        '*api/sidebar/badge-counts*',
        '*badge-counts*',
        '*api/notifications/count*',
        '*notifications/count*',
        '*api/auth/account-status*',
        '*account-status*',
        '*api/check-user-approval*',
        '*check-user-approval*',
        '*api/cases/check-duplicate*',
        '*check-duplicate*',
        '*api/unit-exhibit-alerts*',
        '*unit-exhibit-alerts*',
        '*api/head-stats*',
        '*head-stats*',
        'livewire/*',
        '_debugbar/*',
        'sanctum/*',
        'up',
    ];

    /**
     * Handle an incoming request and log activities performed strictly by staff members only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $user = Auth::user();
            if (!$user) {
                return $response;
            }

            // STRICT EXCLUSION: Heads of Unit and Directorate Heads MUST NEVER be logged in staff_audit.
            if ($this->isHeadOfficer($user)) {
                return $response;
            }

            // STRICT INCLUSION: Only authentic staff members.
            if (!$this->isStaffOfficer($user)) {
                return $response;
            }

            // Exclude noise, polling, health endpoints, and staff-audit-logs pages
            foreach ($this->excludedPatterns as $pattern) {
                if ($request->is($pattern)) {
                    return $response;
                }
            }

            // Avoid logging asset or favicon requests
            $path = $request->path();
            if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|map)$/i', $path)) {
                return $response;
            }

            $this->recordStaffAudit($request, $response, $user);
        } catch (Throwable $e) {
            // Never break user requests if logging encounters an issue
            report($e);
        }

        return $response;
    }

    /**
     * Determine if user is a Unit Head or Directorate Head.
     */
    protected function isHeadOfficer($user): bool
    {
        if (!$user) {
            return false;
        }

        // Explicit is_head flag on user
        if (!empty($user->is_head)) {
            return true;
        }

        // StaffAccount instances are never Heads
        if ($user instanceof StaffAccount) {
            return false;
        }

        $unitLower = strtolower($user->unit ?? '');
        $roleLower = strtolower($user->role ?? '');

        if ($roleLower === 'head' || str_contains($roleLower, 'head') || $roleLower === 'admin') {
            return true;
        }

        if (str_contains($unitLower, 'directorate') && empty($user->is_staff)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user is strictly a staff member.
     */
    protected function isStaffOfficer($user): bool
    {
        if ($this->isHeadOfficer($user)) {
            return false;
        }

        if ($user instanceof StaffAccount) {
            return true;
        }

        if ($user instanceof User) {
            return !$user->is_head;
        }

        return false;
    }

    /**
     * Record the activity entry in staff_audit table.
     */
    protected function recordStaffAudit(Request $request, Response $response, $user): void
    {
        $method = strtoupper($request->method());
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Resolve unit
        $unit = $this->resolveUnit($request, $user);

        // Derive Action and Description
        [$action, $description] = $this->resolveActionAndDescription($request, $method, $path, $unit);

        // Sanitize Payload
        $payload = $this->sanitizePayload($request, $method);

        StaffAudit::create([
            'user_id'          => ($user instanceof User) ? $user->id : ($user->user_id ?? null),
            'staff_account_id' => ($user instanceof StaffAccount) ? (string) $user->id : null,
            'staff_name'       => $user->fullname ?? ($user->name ?? 'Staff Officer'),
            'service_id'       => $user->service_id ?? null,
            'staff_email'      => $user->email ?? null,
            'unit'             => $unit,
            'role'             => 'Staff Officer',
            'action'           => $action,
            'description'      => $description,
            'route'            => $path,
            'url'              => $request->fullUrl(),
            'method'           => $method,
            'status_code'      => $statusCode,
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'payload'          => $payload,
        ]);
    }

    /**
     * Determine forensic unit context.
     */
    protected function resolveUnit(Request $request, $user): string
    {
        $from = $request->query('from') ?? $request->query('unit');
        if (!empty($from)) {
            if (str_contains(strtolower($from), 'lab')) return 'Lab Analysis';
            if (str_contains(strtolower($from), 'evidence')) return 'Evidence Management';
            if (str_contains(strtolower($from), 'digital') || str_contains(strtolower($from), 'physical')) return 'Digital Forensics';
            if (str_contains(strtolower($from), 'crime')) return 'Crime Scene Support';
            return trim($from);
        }

        if ($request->is('Lab_Analysis*') || $request->is('lab-analysis*')) return 'Lab Analysis';
        if ($request->is('Evidence_Management*') || $request->is('evidence-management*')) return 'Evidence Management';
        if ($request->is('Digital_Forensics*') || $request->is('digital-forensics*')) return 'Digital Forensics';
        if ($request->is('Crime_Scene_Support*') || $request->is('crime-scene-support*')) return 'Crime Scene Support';
        if ($request->is('Forensic_Head*')) return 'Forensic Directorate';

        return $user->unit ?? 'Operational Unit';
    }

    /**
     * Extract rich contextual case details from request parameters or path.
     */
    protected function extractCaseContext(Request $request, string $path): array
    {
        $caseNumber = $request->input('case_number')
            ?? $request->input('case_ref')
            ?? $request->input('reference_number')
            ?? $request->input('docket_number')
            ?? $request->input('case_id');

        $suspectName = $request->input('suspect_name')
            ?? $request->input('fullname')
            ?? $request->input('name');

        // Look for case identifier in URL path, e.g. /Lab_Analysis/staff/cases/NCC-2026-5752/review
        // or /cases/15/review or /cases/NCC-2026-5752
        if (empty($caseNumber)) {
            if (preg_match('#/(?:cases|case|case-rollback)/([^/]+)(?:/review|/resubmit)?#i', '/' . $path, $matches)) {
                $candidate = trim($matches[1]);
                $reserved = ['create', 'review', 'dashboard', 'report', 'rollback', 'search', 'filter', 'events', 'drug-screening', 'forensic-status', 'vault'];
                if (!in_array(strtolower($candidate), $reserved, true)) {
                    $caseNumber = $candidate;
                }
            }
        }

        $caseModel = null;
        if (!empty($caseNumber)) {
            try {
                $caseModel = \App\Models\ForensicCase::where('case_number', $caseNumber)
                    ->orWhere('id', $caseNumber)
                    ->first();

                if ($caseModel) {
                    $caseNumber = $caseModel->case_number ?? $caseNumber;
                    if (empty($suspectName) && !empty($caseModel->suspect_name)) {
                        $suspectName = $caseModel->suspect_name;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DB query errors in middleware
            }

            if (empty($suspectName)) {
                try {
                    if (class_exists(\App\Models\SingleSuspectCase::class)) {
                        $sc = \App\Models\SingleSuspectCase::where('case_number', $caseNumber)
                            ->orWhere('id', $caseNumber)
                            ->first();
                        if ($sc && !empty($sc->suspect_name)) {
                            $suspectName = $sc->suspect_name;
                            $caseNumber = $sc->case_number ?? $caseNumber;
                        }
                    }
                    if (empty($suspectName) && class_exists(\App\Models\MultipleSuspectCase::class)) {
                        $mc = \App\Models\MultipleSuspectCase::where('case_number', $caseNumber)
                            ->orWhere('id', $caseNumber)
                            ->first();
                        if ($mc) {
                            $suspectName = $mc->title ?? null;
                            $caseNumber = $mc->case_number ?? $caseNumber;
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore DB query errors in middleware
                }
            }
        }

        return [$caseNumber, $suspectName, $caseModel];
    }

    /**
     * Determine human-readable action and detailed description in simple English.
     */
    protected function resolveActionAndDescription(Request $request, string $method, string $path, string $unit): array
    {
        [$caseRef, $suspect, $caseModel] = $this->extractCaseContext($request, $path);

        $exhibitNumber = $request->input('exhibit_number')
            ?? $request->input('property_id')
            ?? $request->input('barcode');

        $exhibitCategory = $request->input('exhibit_category')
            ?? $request->input('category');

        $exhibitCount = is_array($request->input('seizures'))
            ? count($request->input('seizures'))
            : ($request->input('exhibit_count') ?? null);

        $transferTo = $request->input('transfer_to')
            ?? $request->input('custody_to')
            ?? $request->input('new_location');

        $location = $request->input('location_seized')
            ?? $request->input('place_of_incident')
            ?? $request->input('raid_location');

        if ($method === 'GET') {
            // Case Intake / Seizure Creation
            if ($request->is('*/create*') || $request->is('cases/create*') || $request->is('*arrest-person*')) {
                if ($request->query('mode') === 'seizures_only' || $request->query('section') === 'seizures') {
                    return ['VIEW', "Opened the exhibit registration form to add newly seized items without creating a new case docket in {$unit}."];
                }
                if ($request->is('*cargo-parcel*')) {
                    return ['VIEW', "Opened the Cargo & Parcel Interception registry to record newly intercepted contraband packages in {$unit}."];
                }
                if ($request->is('*raid*')) {
                    return ['VIEW', "Opened the Field Operation registry to log a new raid scene and seized evidence in {$unit}."];
                }
                if ($request->is('*search-warrant*')) {
                    return ['VIEW', "Opened the Search Warrant registry to record premises searched and exhibits seized under warrant in {$unit}."];
                }
                if ($request->is('*intel-led-ops*')) {
                    return ['VIEW', "Opened the Intelligence-Led Operations form to log operational surveillance and seized evidence in {$unit}."];
                }
                if ($request->is('*transferred-case*')) {
                    return ['VIEW', "Opened the Transferred Case intake form to log an incoming case from an external agency in {$unit}."];
                }
                if ($request->is('*arrest-person*')) {
                    return ['VIEW', "Opened the Suspect Arrest Lodgement form to record suspect personal biodata and seized property in {$unit}."];
                }

                return ['VIEW', "Opened the case entry form to register a new criminal investigation, suspect arrest, and seized evidence in {$unit}."];
            }

            // Case Review / Detailed Examination of a Specific Case
            if ($request->is('*/review*') || $request->is('cases/*/review*') || (!empty($caseRef) && ($request->is('*/cases/*') || $request->is('cases/*')))) {
                $suspectPart = $suspect ? " (Suspect: {$suspect})" : "";
                if ($unit === 'Lab Analysis') {
                    return ['VIEW', "Reviewed case file #{$caseRef}{$suspectPart} in Lab Analysis to inspect substance identification and laboratory findings."];
                }
                if ($unit === 'Digital Forensics') {
                    return ['VIEW', "Reviewed case file #{$caseRef}{$suspectPart} in Digital Forensics to inspect digital evidence, forensic device extractions, and hardware examination findings."];
                }
                if ($unit === 'Evidence Management') {
                    return ['VIEW', "Reviewed case file #{$caseRef}{$suspectPart} in Evidence Management to inspect exhibit registry records, custodial property tags, and vault storage locations."];
                }
                return ['VIEW', "Reviewed case file #{$caseRef}{$suspectPart} in {$unit} to inspect crime scene photographs, incident details, and evidence logs."];
            }

            // Case Register / Directory List
            if ($request->is('*/staff/case*') || $request->is('staff/case*') || $request->is('*/staff/cases*') || $request->is('staff/cases*') || $request->is('cases')) {
                $search = $request->query('search') ?? $request->query('q');
                $status = $request->query('status');
                if (!empty($search)) {
                    return ['VIEW', "Searched the {$unit} case register for '{$search}'."];
                }
                if (!empty($status) && strtolower($status) !== 'all') {
                    return ['VIEW', "Filtered the {$unit} case register by status '{$status}'."];
                }

                if ($unit === 'Lab Analysis') {
                    return ['VIEW', "Browsed the Lab Analysis case register to check incoming laboratory exhibits and laboratory analysis statuses."];
                }
                if ($unit === 'Digital Forensics') {
                    return ['VIEW', "Browsed the Digital Forensics case register to monitor digital device intake and extraction queue statuses."];
                }
                if ($unit === 'Evidence Management') {
                    return ['VIEW', "Browsed the Evidence Management case register to view registered exhibits, vault lodgements, and custodial transfers."];
                }
                return ['VIEW', "Browsed the {$unit} case register to check active case files and current investigation statuses."];
            }

            // Periodic Reports / SITREP
            if ($request->is('*/report*') || $request->is('*reports*') || $request->is('*sitrep*')) {
                return ['VIEW', "Opened the {$unit} periodic reports workspace to review situation reports (SITREP) and unit analytics."];
            }

            // Rollback / Corrections Workspace
            if ($request->is('*/rollback*') || $request->is('*case_rollback*') || $request->is('*case-rollback*')) {
                if ($caseRef) {
                    return ['VIEW', "Opened returned Case #{$caseRef} to review correction notes from the Unit Head and prepare revisions."];
                }
                return ['VIEW', "Viewed the returned case submissions workspace to check dockets flagged for correction by the Unit Head."];
            }

            // Drug Screening
            if ($request->is('*/drug-screening*') || $request->is('*screening*')) {
                return ['VIEW', "Accessed the Drug Screening Register to review rapid drug test results and preliminary screening records in Lab Analysis."];
            }

            // Digital Forensics Status Board
            if ($request->is('*/forensic-status*') || $request->is('*investigation-status*')) {
                return ['VIEW', "Checked the Digital Forensics device tracking board to monitor chip extractions, forensic imaging, and data analysis progress."];
            }

            // Evidence Vault
            if ($request->is('*/vault*') || $request->is('*evidence-vault*')) {
                return ['VIEW', "Accessed the Central Evidence Vault registry to inspect custody lockers, security seals, and stored exhibit bins."];
            }

            // Staff Operational Dashboard
            if ($request->is('*/dashboard*') || $request->is('*/staff') || $request->is('staff')) {
                return ['VIEW', "Opened the {$unit} Staff Operational Dashboard to check active case assignments, pending queues, and priority notices."];
            }

            $cleanPath = ltrim($path, '/');
            return ['VIEW', "Viewed the {$unit} workspace page (/{$cleanPath})."];
        }

        if ($method === 'POST') {
            // Authentication
            if ($request->is('login') || $request->is('*/login')) {
                return ['LOGIN', "Signed in to the forensic system and started an active work session in {$unit}."];
            }
            if ($request->is('logout') || $request->is('*/logout')) {
                return ['LOGOUT', "Logged out of the forensic system and ended the work session in {$unit}."];
            }

            // Rollback Resubmission
            if ($request->is('*resubmit*')) {
                $desc = "Resubmitted corrected case docket";
                if ($caseRef) {
                    $desc .= " #{$caseRef}";
                }
                $desc .= " to the Unit Head after completing requested revisions.";
                return ['RESUBMIT', $desc];
            }

            // Submit Case to Head for Formal Review
            if ($request->is('*/submit*') || $request->is('*submission*')) {
                $desc = "Submitted completed case docket";
                if ($caseRef) {
                    $desc .= " #{$caseRef}";
                }
                if ($suspect) {
                    $desc .= " (Suspect: {$suspect})";
                }
                $desc .= " to the Unit Head for formal review, verification, and sign-off.";
                return ['SUBMIT', $desc];
            }

            // Case Registration / Lodgement
            if ($request->is('cases') || $request->is('*/cases') || $request->is('*/create*') || $request->is('*arrest-person*')) {
                $desc = "Created and registered a new case docket in {$unit}";
                if ($caseRef) {
                    $desc .= " (Case #{$caseRef})";
                }
                if ($suspect) {
                    $desc .= " involving suspect {$suspect}";
                }
                if ($location) {
                    $desc .= " seized at {$location}";
                }
                if ($exhibitCount) {
                    $desc .= " with {$exhibitCount} seized exhibit(s) recorded";
                } elseif ($exhibitCategory) {
                    $desc .= " under category '{$exhibitCategory}'";
                }
                $desc .= ".";
                return ['CREATE', $desc];
            }

            // Vault / Chain of Custody Update
            if ($request->is('*/vault*') || $request->is('*custod*')) {
                $desc = "Updated the chain of custody in the Central Evidence Vault";
                if ($exhibitNumber) {
                    $desc .= " for exhibit #{$exhibitNumber}";
                }
                if ($transferTo) {
                    $desc .= ", transferring custodial location to '{$transferTo}'";
                }
                $desc .= ".";
                return ['UPDATE', $desc];
            }

            // Upload examination document / GSA report
            if ($request->is('*/gsa-upload*') || $request->is('*upload*') || $request->is('*report-file*')) {
                $desc = "Uploaded an official analytical examination report document";
                if ($caseRef) {
                    $desc .= " for Case #{$caseRef}";
                }
                $desc .= " to the forensic case file in {$unit}.";
                return ['UPLOAD', $desc];
            }

            // Associate exhibit to suspect
            if ($request->is('*/associate*')) {
                $desc = "Associated seized exhibit";
                if ($exhibitNumber) {
                    $desc .= " #{$exhibitNumber}";
                }
                if ($suspect) {
                    $desc .= " to suspect profile {$suspect}";
                }
                if ($caseRef) {
                    $desc .= " under Case #{$caseRef}";
                }
                $desc .= " in {$unit}.";
                return ['UPDATE', $desc];
            }

            // Rollback request
            if ($request->is('*/rollback*')) {
                $desc = "Submitted a case rollback and correction request";
                if ($caseRef) {
                    $desc .= " for Case #{$caseRef}";
                }
                $desc .= " to enable required updates to the case file.";
                return ['ROLLBACK', $desc];
            }

            // General updates
            if ($request->is('*/update*') || $request->is('cases/*/update*')) {
                $desc = "Saved modifications and updated record details";
                if ($caseRef) {
                    $desc .= " on Case #{$caseRef}";
                }
                $desc .= " in {$unit}.";
                return ['UPDATE', $desc];
            }

            $cleanPath = ltrim($path, '/');
            return ['SUBMIT', "Saved form submission data on /{$cleanPath} in {$unit}."];
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            $desc = "Updated and saved changes";
            if ($caseRef) {
                $desc .= " on Case #{$caseRef}";
            }
            $cleanPath = ltrim($path, '/');
            $desc .= " via /{$cleanPath} in {$unit}.";
            return ['UPDATE', $desc];
        }

        if ($method === 'DELETE') {
            $desc = "Deleted record entry";
            if ($caseRef) {
                $desc .= " associated with Case #{$caseRef}";
            }
            $cleanPath = ltrim($path, '/');
            $desc .= " via /{$cleanPath} in {$unit}.";
            return ['DELETE', $desc];
        }

        $cleanPath = ltrim($path, '/');
        return [strtoupper($method), "Performed {$method} operation on /{$cleanPath} in {$unit}."];
    }

    /**
     * Clean and sanitize request input data for audit payload storage.
     */
    protected function sanitizePayload(Request $request, string $method): ?array
    {
        if ($method === 'GET') {
            $query = $request->query();
            return !empty($query) ? ['query_params' => $query] : null;
        }

        $hiddenFields = [
            'password',
            'password_confirmation',
            '_token',
            '_method',
            'tag_image',
            'signature',
            'evidence_photos',
            'photos',
        ];

        $data = $request->except($hiddenFields);

        // Sanitize string lengths and arrays to keep payload reasonable
        $sanitized = [];
        foreach ($data as $key => $val) {
            if (is_string($val)) {
                if (str_starts_with($val, 'data:image')) {
                    $sanitized[$key] = '[Base64 Image Data Omitted]';
                } else {
                    $sanitized[$key] = mb_substr($val, 0, 500);
                }
            } elseif (is_array($val)) {
                $sanitized[$key] = count($val) > 10 ? array_slice($val, 0, 10) : $val;
            } else {
                $sanitized[$key] = $val;
            }
        }

        return !empty($sanitized) ? $sanitized : null;
    }
}

