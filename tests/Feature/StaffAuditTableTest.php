<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
use App\Models\StaffAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffAuditTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_audit_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('staff_audit'), 'The [staff_audit] database table does not exist.');

        $expectedColumns = [
            'id',
            'user_id',
            'staff_account_id',
            'staff_name',
            'service_id',
            'staff_email',
            'unit',
            'role',
            'action',
            'description',
            'route',
            'url',
            'method',
            'status_code',
            'ip_address',
            'user_agent',
            'payload',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('staff_audit', $column),
                "Column [{$column}] is missing from [staff_audit] table."
            );
        }
    }

    public function test_staff_audit_model_can_log_and_retrieve_activities(): void
    {
        $staffUser = User::factory()->create([
            'fullname'    => 'Officer Kwame Mensah',
            'service_id'  => 'SD-9988',
            'email'       => 'kwame.mensah@nacoc.gov.gh',
            'unit'        => 'Evidence Management',
            'is_head'     => false,
            'is_staff'    => true,
            'is_approved' => true,
        ]);

        $audit = StaffAudit::create([
            'user_id'          => $staffUser->id,
            'staff_name'       => $staffUser->fullname,
            'service_id'       => $staffUser->service_id,
            'staff_email'      => $staffUser->email,
            'unit'             => 'Evidence Management',
            'role'             => 'Staff Officer',
            'action'           => 'LODGE_EXHIBIT',
            'description'      => 'Logged Exhibit EXH-001 into Central Evidence Vault',
            'route'            => 'Evidence_Management/create',
            'url'              => 'http://localhost/Evidence_Management/create',
            'method'           => 'POST',
            'status_code'      => 200,
            'ip_address'       => '127.0.0.1',
            'user_agent'       => 'Mozilla/5.0 PHPUnit Test',
            'payload'          => ['exhibit_number' => 'EXH-001', 'weight' => '1.5kg'],
        ]);

        $this->assertDatabaseHas('staff_audit', [
            'id'          => $audit->id,
            'staff_name'  => 'Officer Kwame Mensah',
            'service_id'  => 'SD-9988',
            'unit'        => 'Evidence Management',
            'action'      => 'LODGE_EXHIBIT',
        ]);

        $found = StaffAudit::forUnit('Evidence Management')->search('Kwame Mensah')->first();
        $this->assertNotNull($found);
        $this->assertSame('LODGE_EXHIBIT', $found->action);
        $this->assertSame(['exhibit_number' => 'EXH-001', 'weight' => '1.5kg'], $found->payload);
    }

    public function test_middleware_automatically_logs_staff_activity_on_staff_routes(): void
    {
        $staffUser = User::factory()->create([
            'fullname'    => 'Staff Officer Doe',
            'service_id'  => 'CSS-0042',
            'email'       => 'doe@nacoc.gov.gh',
            'unit'        => 'Crime Scene Support',
            'is_head'     => false,
            'is_staff'    => true,
            'is_approved' => true,
        ]);

        $this->actingAs($staffUser)->get('/Crime_Scene_Support/staff/create');

        $this->assertDatabaseHas('staff_audit', [
            'user_id'     => $staffUser->id,
            'staff_name'  => 'Staff Officer Doe',
            'unit'        => 'Crime Scene Support',
            'role'        => 'Staff Officer',
            'action'      => 'VIEW',
            'description' => 'Opened the case entry form to register a new criminal investigation, suspect arrest, and seized evidence in Crime Scene Support.',
        ]);
    }

    public function test_head_officer_can_view_staff_audit_page_across_all_unit_routes(): void
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Commander Mensah',
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $routes = [
            '/Crime_Scene_Support/staff-audit-logs',
            '/Lab_Analysis/staff-audit-logs',
            '/Evidence_Management/staff-audit-logs',
            '/Digital_Forensics/staff-audit-logs',
            '/Forensic_Head/staff-audit-logs',
            '/staff-audit-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($headOfficer)->get($route);
            $response->assertStatus(200);
            $response->assertSee('Staff Activity &amp; Audit Logs', false);
            $response->assertSee('staffAuditTableContainer', false);
            $response->assertSee('auditInspectModal', false);
            // Assert that the executive Head sidebar is rendered and staff sidebar is NOT rendered
            if (!in_array($route, ['/Forensic_Head/staff-audit-logs', '/staff-audit-logs'], true)) {
                $response->assertSee('Staff Audit Logs', false);
            } else {
                $response->assertDontSee('/Forensic_Head/staff-audit-logs', false);
            }
            $response->assertDontSee('Cyber Analyst / Staff', false);
            $response->assertDontSee('Forensic Chemist / Staff', false);
            $response->assertDontSee('STAFF OPERATIONAL DESK', false);
        }
    }

    public function test_staff_audit_ajax_request_returns_table_partial(): void
    {
        $headOfficer = User::factory()->create([
            'unit'        => 'Evidence Management',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        StaffAudit::create([
            'staff_name'  => 'Officer Test',
            'unit'        => 'Evidence Management',
            'action'      => 'LODGE_EXHIBIT',
            'description' => 'Test exhibit lodging',
            'route'       => 'Evidence_Management/create',
        ]);

        $response = $this->actingAs($headOfficer)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/Evidence_Management/staff-audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Officer Test', false);
        $response->assertSee('LODGE_EXHIBIT', false);
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_middleware_strictly_does_not_log_head_officer_activities(): void
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Commander John Mensah',
            'service_id'  => 'HD-0001',
            'email'       => 'head@nacoc.gov.gh',
            'unit'        => 'Lab Analysis',
            'is_head'     => true,
            'is_staff'    => false,
            'is_approved' => true,
        ]);

        // When a Head officer accesses head pages, audit logs, or even staff routes
        $this->actingAs($headOfficer)->get('/Lab_Analysis');
        $this->actingAs($headOfficer)->get('/Lab_Analysis/staff-audit-logs');
        $this->actingAs($headOfficer)->get('/Lab_Analysis/staff/dashboard');
        $this->actingAs($headOfficer)->get('/Crime_Scene_Support/staff-audit-logs');

        // Heads must NEVER be logged in staff_audit
        $this->assertDatabaseMissing('staff_audit', [
            'staff_name' => 'Commander John Mensah',
        ]);
        $this->assertDatabaseMissing('staff_audit', [
            'role' => 'Unit Head',
        ]);
        $this->assertSame(0, StaffAudit::count());

        // Now an actual staff member accesses a page
        $staffMember = User::factory()->create([
            'fullname'    => 'Analyst Mark Adu',
            'service_id'  => 'SD-9900',
            'email'       => 'mark@nacoc.gov.gh',
            'unit'        => 'Lab Analysis',
            'is_head'     => false,
            'is_staff'    => true,
            'is_approved' => true,
        ]);

        $this->actingAs($staffMember)->get('/Lab_Analysis/staff/create');

        // Only the staff member is logged
        $this->assertSame(1, StaffAudit::count());
        $this->assertDatabaseHas('staff_audit', [
            'staff_name' => 'Analyst Mark Adu',
            'role'       => 'Staff Officer',
            'unit'       => 'Lab Analysis',
        ]);
    }

    public function test_detailed_and_accurate_simple_english_descriptions_without_dossier(): void
    {
        $staffMember = User::factory()->create([
            'fullname'    => 'Officer Peter Oku',
            'service_id'  => 'LAB-5541',
            'email'       => 'peter.oku@nacoc.gov.gh',
            'unit'        => 'Lab Analysis',
            'is_head'     => false,
            'is_staff'    => true,
            'is_approved' => true,
        ]);

        // 1. Browsing case register
        $this->actingAs($staffMember)->get('/Lab_Analysis/staff/case');

        $browseAudit = StaffAudit::where('staff_name', 'Officer Peter Oku')
            ->where('route', 'Lab_Analysis/staff/case')
            ->first();

        $this->assertNotNull($browseAudit);
        $this->assertSame('VIEW', $browseAudit->action);
        $this->assertSame(
            'Browsed the Lab Analysis case register to check incoming laboratory exhibits and laboratory analysis statuses.',
            $browseAudit->description
        );
        $this->assertStringNotContainsStringIgnoringCase('dossier', $browseAudit->description);

        // 2. Specific case review
        $this->actingAs($staffMember)->get('/Lab_Analysis/staff/cases/NCC-2026-5752/review');

        $reviewAudit = StaffAudit::where('staff_name', 'Officer Peter Oku')
            ->where('route', 'Lab_Analysis/staff/cases/NCC-2026-5752/review')
            ->first();

        $this->assertNotNull($reviewAudit);
        $this->assertSame('VIEW', $reviewAudit->action);
        $this->assertStringContainsString('Reviewed case file #NCC-2026-5752', $reviewAudit->description);
        $this->assertStringContainsString('in Lab Analysis to inspect substance identification and laboratory findings', $reviewAudit->description);
        $this->assertStringNotContainsStringIgnoringCase('chemical testing results', $reviewAudit->description);
        $this->assertStringNotContainsStringIgnoringCase('dossier', $reviewAudit->description);

        // 3. Drug screening workspace
        $this->actingAs($staffMember)->get('/Lab_Analysis/staff/drug-screening');

        $screeningAudit = StaffAudit::where('staff_name', 'Officer Peter Oku')
            ->where('route', 'Lab_Analysis/staff/drug-screening')
            ->first();

        $this->assertNotNull($screeningAudit);
        $this->assertSame('VIEW', $screeningAudit->action);
        $this->assertSame(
            'Accessed the Drug Screening Register to review rapid drug test results and preliminary screening records in Lab Analysis.',
            $screeningAudit->description
        );
        $this->assertStringNotContainsStringIgnoringCase('chemical reagent', $screeningAudit->description);
        $this->assertStringNotContainsStringIgnoringCase('colorimetric', $screeningAudit->description);

        // 4. Background realtime dashboard polling must NOT be logged
        $this->actingAs($staffMember)->get('/api/dashboard/realtime');

        $this->assertDatabaseMissing('staff_audit', [
            'route' => 'api/dashboard/realtime',
        ]);

        // 5. Verify no audit record in database contains the word "dossier", "chemical reagent", or "colorimetric"
        $allAudits = StaffAudit::all();
        foreach ($allAudits as $audit) {
            $this->assertStringNotContainsStringIgnoringCase('dossier', $audit->description);
            $this->assertStringNotContainsStringIgnoringCase('chemical reagent', $audit->description);
            $this->assertStringNotContainsStringIgnoringCase('colorimetric', $audit->description);
        }
    }

    public function test_pagination_polling_and_skeleton_loader_features(): void
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Director General Mensah',
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        // Create 25 mock staff audit entries
        for ($i = 1; $i <= 25; $i++) {
            StaffAudit::create([
                'staff_name'  => "Staff Analyst {$i}",
                'service_id'  => "SA-" . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'unit'        => 'Lab Analysis',
                'role'        => 'Staff Officer',
                'action'      => ($i % 2 === 0) ? 'CREATE' : 'VIEW',
                'description' => "Operational test task {$i} executed in laboratory.",
                'route'       => 'Lab_Analysis/staff/case',
                'method'      => ($i % 2 === 0) ? 'POST' : 'GET',
                'status_code' => 200,
            ]);
        }

        // 1. Full page view contains polling controls, skeleton loader script, and initial page items
        $pageResponse = $this->actingAs($headOfficer)->get('/Lab_Analysis/staff-audit-logs?per_page=10');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('id="auditPollingPill"', false);
        $pageResponse->assertSee('id="auditPollingToggleBtn"', false);
        $pageResponse->assertSee('id="auditSyncStatus"', false);
        $pageResponse->assertSee('showAuditSkeleton', false);
        $pageResponse->assertSee('getAuditSkeletonHtml', false);
        $pageResponse->assertSee('animate-pulse', false);
        $pageResponse->assertSee('goToAuditPage', false);
        $pageResponse->assertSee('Showing <strong class="text-slate-900 tnum font-extrabold">1</strong> to <strong class="text-slate-900 tnum font-extrabold">10</strong> of <strong class="text-slate-900 tnum font-extrabold">25</strong>', false);
        $pageResponse->assertSee('Page 1 of 3', false);

        // 2. AJAX pagination request to Page 2
        $ajaxPage2 = $this->actingAs($headOfficer)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/Lab_Analysis/staff-audit-logs?page=2&per_page=10');

        $ajaxPage2->assertStatus(200);
        $ajaxPage2->assertHeader('X-Audit-Current-Page', '2');
        $ajaxPage2->assertHeader('X-Audit-Last-Page', '3');
        $ajaxPage2->assertHeader('X-Audit-Total', '25');
        $ajaxPage2->assertHeader('X-Audit-Today', '25');
        $ajaxPage2->assertSee('Showing <strong class="text-slate-900 tnum font-extrabold">11</strong> to <strong class="text-slate-900 tnum font-extrabold">20</strong> of <strong class="text-slate-900 tnum font-extrabold">25</strong>', false);
        $ajaxPage2->assertSee('Page 2 of 3', false);
        $ajaxPage2->assertDontSee('<!DOCTYPE html>', false);

        // 3. AJAX pagination request to Page 3 (Final page)
        $ajaxPage3 = $this->actingAs($headOfficer)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/Lab_Analysis/staff-audit-logs?page=3&per_page=10');

        $ajaxPage3->assertStatus(200);
        $ajaxPage3->assertHeader('X-Audit-Current-Page', '3');
        $ajaxPage3->assertSee('Showing <strong class="text-slate-900 tnum font-extrabold">21</strong> to <strong class="text-slate-900 tnum font-extrabold">25</strong> of <strong class="text-slate-900 tnum font-extrabold">25</strong>', false);
        $ajaxPage3->assertSee('Page 3 of 3', false);
    }

    public function test_staff_audit_print_layout_includes_nacoc_logo_header_footer_and_readable_context(): void
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Director General Kwame Asante',
            'service_id'  => 'HQ-0001',
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        StaffAudit::create([
            'staff_name'  => 'Detective Serwaa Mensah',
            'service_id'  => 'DF-1092',
            'unit'        => 'Digital Forensics',
            'role'        => 'Staff Officer',
            'action'      => 'DEVICE_EXTRACTION',
            'description' => 'Completed physical image acquisition of suspect mobile device.',
            'route'       => 'Digital_Forensics/staff/case',
            'method'      => 'POST',
            'status_code' => 200,
            'payload'     => [
                'case_number'    => 'CR-ACC-2026-8801',
                'suspect_name'   => 'Kofi Boateng',
                'exhibit_number' => 'EXH-MOB-091',
                'transfer_unit'  => 'Lab Analysis',
            ],
        ]);

        $routesToTest = [
            '/Digital_Forensics/staff-audit-logs',
            '/Crime_Scene_Support/staff-audit-logs',
            '/Evidence_Management/staff-audit-logs',
            '/Lab_Analysis/staff-audit-logs',
            '/Forensic_Head/staff-audit-logs',
        ];

        foreach ($routesToTest as $route) {
            $response = $this->actingAs($headOfficer)->get($route);
            $response->assertStatus(200);

            // Export CSV and Print buttons are removed
            $response->assertDontSee('Export CSV', false);
            $response->assertDontSee('title="Print Log"', false);

            // Official NACOC executive cover page elements
            $response->assertSee('cover-page', false);
            $response->assertSee('STAFF AUDIT DOSSIER &bull; COVER PAGE', false);
            $response->assertSee('REPUBLIC OF GHANA', false);
            $response->assertSee('NARCOTICS CONTROL COMMISSION', false);
            $response->assertSee('FORENSIC SERVICES DIRECTORATE', false);
            $response->assertSee('STAFF AUDIT &amp; ACTIVITY REGISTER', false);
            $response->assertSee('Total Logged Records', false);
            $response->assertSee('Monitored Staff', false);
            $response->assertSee('Mutations / Writes', false);
            $response->assertSee('RESTRICTED', false);
            $response->assertSee('alt="NACOC Logo"', false);
            $response->assertSee('alt="NACOC Watermark"', false);
            $response->assertSee('CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD', false);
            $response->assertSee('COMPILING UNIT:', false);
            $response->assertSee('OVERSIGHT AUTHORITY:', false);

            // Continuation header for body pages
            $response->assertSee('print-continuation-header', false);

            // Official NACOC print-only footer elements
            $response->assertSee('print-footer-container', false);
            $response->assertSee('NARCOTICS CONTROL COMMISSION ACT, 2020 (ACT 1019)', false);
            $response->assertSee('IMMUTABLE SYSTEM AUDIT TRAIL', false);
            $response->assertSee('Official Document Seal', false);

            // Body table must strictly have Timestamp, Staff Officer, and Activity Description
            $response->assertSee('col-timestamp">Timestamp</th>', false);
            $response->assertSee('col-officer">Staff Officer</th>', false);
            $response->assertSee('col-description">Activity Description</th>', false);
            $response->assertDontSee('col-unit">Unit &amp; Role</th>', false);
            $response->assertDontSee('col-action">Action &amp; Method</th>', false);
            $response->assertDontSee('col-route">Route &amp; Status</th>', false);

            // Print layout rules
            $response->assertSee('@page {', false);
            $response->assertSee('size: A4 portrait;', false);
            $response->assertSee('-webkit-print-color-adjust: exact', false);
        }

        // Test that context tags are present and readable for the logged case
        $dfResponse = $this->actingAs($headOfficer)->get('/Digital_Forensics/staff-audit-logs');
        $dfResponse->assertSee('audit-context-tag', false);
        $dfResponse->assertSee('Case: #CR-ACC-2026-8801', false);
        $dfResponse->assertSee('Suspect: Kofi Boateng', false);
        $dfResponse->assertSee('Exhibit: EXH-MOB-091', false);
        $dfResponse->assertSee('Transfer To: Lab Analysis', false);
    }
}
