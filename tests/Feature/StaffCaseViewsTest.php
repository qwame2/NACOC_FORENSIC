<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffCaseViewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_unit_staff_case_view_renders_successfully(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        ForensicCase::create([
            'case_number' => 'CAS-CSSU-ALL-001',
            'title' => 'Harbor Cargo Inspection',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => 'Insp. Boateng',
            'suspect_name' => 'Yaw Mensah',
            'status' => 'Active',
            'priority' => 'HIGH',
        ]);

        ForensicCase::create([
            'case_number' => 'CAS-DFU-ALL-002',
            'title' => 'Encrypted Phone Extraction',
            'unit_name' => 'Digital Forensics',
            'submitting_officer' => 'Insp. Owusu',
            'suspect_name' => 'Kofi Asante',
            'status' => 'Under Analysis',
            'priority' => 'MEDIUM',
        ]);

        $routes = [
            '/Crime_Scene_Support/staff/case',
            '/Crime_Scene_Support/staff/cases',
            '/Lab_Analysis/staff/case',
            '/Lab_Analysis/staff/cases',
            '/Evidence_Management/staff/case',
            '/Evidence_Management/staff/cases',
            '/Digital_Forensics/staff/case',
            '/Digital_Forensics/staff/cases',
            '/Forensic_Head/staff/case',
            '/Forensic_Head/staff/cases',
            '/staff/case',
            '/staff/cases',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('STAFF WORKSTATION');
            $response->assertSee('Case Register');
            $response->assertSee('CAS-CSSU-ALL-001');
            $response->assertSee('CAS-DFU-ALL-002');
            $response->assertSee('Suspect Disposition');
            $response->assertSee('Case Approval Stat');
            $response->assertSee('toggleStaffActionDropdown', false);
            $response->assertDontSee('Unit Permissions');
            $response->assertDontSee('/Forensic_Head/cases"', false);
            $response->assertDontSee('href="/Digital_Forensics/cases"', false);
        }
    }

    #[Test]
    public function every_unit_staff_case_review_page_renders_suspect_dossier_with_staff_sidebar(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-STAFF-REV-001',
            'title' => 'Suspect Contraband Review',
            'unit_name' => 'Lab Analysis',
            'submitting_officer' => 'Insp. Staff Officer',
            'suspect_name' => 'Kwame Mensah',
            'status' => 'Under Analysis',
            'priority' => 'HIGH',
        ]);

        $reviewRoutes = [
            "/Crime_Scene_Support/staff/cases/{$case->case_number}/review",
            "/Lab_Analysis/staff/cases/{$case->case_number}/review",
            "/Evidence_Management/staff/cases/{$case->case_number}/review",
            "/Digital_Forensics/staff/cases/{$case->case_number}/review",
            "/Forensic_Head/staff/cases/{$case->case_number}/review",
            "/staff/cases/{$case->case_number}/review",
        ];

        foreach ($reviewRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('STAFF WORKSTATION');
            $response->assertSee('Kwame Mensah');
            $response->assertSee('CAS-STAFF-REV-001');
            $response->assertDontSee('Unit Permissions');
            $response->assertDontSee('/Forensic_Head/cases"', false);
            $response->assertDontSee('/Forensic_Head/cases?search=', false);
            $response->assertDontSee('href="/cases?from=', false);
            $response->assertDontSee('href="/Digital_Forensics/cases"', false);
        }
    }

    #[Test]
    public function every_unit_staff_create_page_renders_with_staff_sidebar_and_no_head_links(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $createRoutes = [
            '/Crime_Scene_Support/staff/create',
            '/Lab_Analysis/staff/create',
            '/Evidence_Management/staff/create',
            '/Digital_Forensics/staff/create',
            '/Forensic_Head/staff/create',
            '/staff/create',
        ];

        foreach ($createRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('STAFF WORKSTATION');
            $response->assertSee('Case Rollback');
            $response->assertSee('Back to Case Register');
            $response->assertDontSee('Unit Permissions');
            $response->assertDontSee('href="http://localhost/cases"', false);
        }
    }

    #[Test]
    public function every_unit_staff_page_removes_blinking_dots_and_indicator_dots(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Dot Check Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $case = \App\Models\SingleSuspectCase::create([
            'case_number' => 'FX-2026-DOT-001',
            'title' => 'Staff No Dots Verification',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Kwaku Botwe',
            'exhibit_category' => 'Drugs',
            'status' => 'Submitted',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $staffUser->id,
        ]);

        $dashboardRoutes = [
            '/Crime_Scene_Support/staff/dashboard',
            '/Lab_Analysis/staff/dashboard',
            '/Evidence_Management/staff/dashboard',
            '/Digital_Forensics/staff/dashboard',
            '/Forensic_Head/staff/dashboard',
        ];

        foreach ($dashboardRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertDontSee('<span class="status-dot"></span>', false);
            $response->assertSee('.icon-btn .dot-indicator', false);
            $response->assertDontSee('w-1.5 h-1.5 rounded-full', false);
            $response->assertDontSee('w-2 h-2 rounded-full', false);
            $response->assertDontSee('w-2.5 h-2.5 rounded-full', false);
            $response->assertDontSee('rounded-full animate-pulse', false);
        }

        $unitStaffRoutes = [
            '/Crime_Scene_Support/staff/case',
            '/Lab_Analysis/staff/case',
            '/Evidence_Management/staff/case',
            '/Digital_Forensics/staff/case',
            '/Forensic_Head/staff/case',
            '/Crime_Scene_Support/staff/create',
            '/Lab_Analysis/staff/create',
            '/Evidence_Management/staff/create',
            '/Digital_Forensics/staff/create',
            '/Forensic_Head/staff/create',
            '/Crime_Scene_Support/staff/report',
            '/Lab_Analysis/staff/report',
            '/Evidence_Management/staff/report',
            '/Digital_Forensics/staff/report',
            '/Forensic_Head/staff/report',
            '/Digital_Forensics/staff/forensic-status',
            "/Crime_Scene_Support/staff/cases/{$case->case_number}/review",
            "/Lab_Analysis/staff/cases/{$case->case_number}/review",
            "/Evidence_Management/staff/cases/{$case->case_number}/review",
            "/Digital_Forensics/staff/cases/{$case->case_number}/review",
            "/Forensic_Head/staff/cases/{$case->case_number}/review",
        ];

        foreach ($unitStaffRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertDontSee('w-1.5 h-1.5 rounded-full', false);
            $response->assertDontSee('w-2 h-2 rounded-full', false);
            $response->assertDontSee('w-2.5 h-2.5 rounded-full', false);
            $response->assertDontSee('rounded-full animate-pulse', false);
            $response->assertDontSee('Access Suspended &bull; Account Revoked', false);
        }
    }

    #[Test]
    public function evidence_management_staff_case_renders_green_action_buttons(): void
    {
        User::factory()->create([
            'fullname' => 'Chief Vault Custodian',
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $emStaff = User::factory()->create([
            'fullname' => 'EM Vault Custodian',
            'unit' => 'Evidence Management',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $case = \App\Models\SingleSuspectCase::create([
            'case_number' => 'EMU-2026-BTN-01',
            'title' => 'Green Button Custody Register',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Ama Serwaa',
            'exhibit_category' => 'Drugs',
            'status' => 'Vaulted',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $emStaff->id,
        ]);

        $response = $this->actingAs($emStaff)->get('/Evidence_Management/staff/case');
        $response->assertStatus(200);
        $response->assertSee('Create New Case');
        $response->assertSee('Exhibit Vault');
        $this->assertTrue(
            str_contains($response->getContent(), 'Ledger Report') || str_contains($response->getContent(), 'Sitrep Reports')
        );
        $response->assertSee('bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/25', false);
        $this->assertTrue(
            str_contains($response->getContent(), 'border-emerald-600') && str_contains($response->getContent(), 'bg-emerald-600 hover:bg-emerald-700 text-white')
        );
        $this->assertTrue(
            str_contains($response->getContent(), 'border-emerald-200') && str_contains($response->getContent(), 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800')
        );
        $response->assertSee('hover:bg-emerald-50 hover:text-emerald-700', false);
        $response->assertSee('background: #059669;', false);
        $response->assertDontSee('bg-amber-600 hover:bg-amber-700', false);
        $response->assertSee("/Evidence_Management/staff/cases/{$case->case_number}/review", false);
    }

    #[Test]
    public function every_unit_staff_case_renders_dropdown_filters_instead_of_tab_buttons(): void
    {
        $units = [
            'Crime Scene Support' => '/Crime_Scene_Support/staff/case',
            'Digital Forensics' => '/Digital_Forensics/staff/case',
            'Evidence Management' => '/Evidence_Management/staff/case',
            'Forensic Head' => '/Forensic_Head/staff/case',
            'Lab Analysis' => '/Lab_Analysis/staff/case',
        ];

        foreach ($units as $unitName => $route) {
            User::factory()->create([
                'fullname' => "{$unitName} Unit Head",
                'unit' => $unitName,
                'is_head' => true,
                'is_approved' => true,
            ]);

            $staff = User::factory()->create([
                'fullname' => "{$unitName} Staff Officer",
                'unit' => $unitName,
                'is_head' => false,
                'is_approved' => true,
            ]);

            $response = $this->actingAs($staff)->get($route);
            $response->assertStatus(200);

            // Assert buttons are removed
            $response->assertDontSee('id="statusFilterTabs"', false);
            $response->assertDontSee("onclick=\"setStaffStatusFilter('all', this)\"", false);
            $response->assertDontSee("onclick=\"setStaffStatusFilter('active', this)\"", false);
            $response->assertDontSee("onclick=\"setStaffStatusFilter('evidence', this)\"", false);
            $response->assertDontSee("onclick=\"setStaffStatusFilter('completed', this)\"", false);

            // Assert Scene Status Filter is removed
            $response->assertDontSee('id="staffStatusFilter"', false);
            $response->assertDontSee('<option value="evidence">', false);
            $response->assertDontSee('All Scenes (', false);

            // Assert Date From and To filters are present
            $response->assertSee('id="staffDateFrom"', false);
            $response->assertSee('id="staffDateTo"', false);
            $response->assertSee('From:', false);
            $response->assertSee('To:', false);

            // Assert Priority filter with All Priorities, High, Low
            $response->assertSee('id="staffPriorityFilter"', false);
            $response->assertSee('All Priorities', false);
            $response->assertSee('<option value="high">High</option>', false);
            $response->assertSee('<option value="low">Low</option>', false);
            $response->assertDontSee('<option value="critical">Critical</option>', false);

            // Assert Approval filter with All Approval States, Pending Approval, Approved, Head Created
            $response->assertSee('id="staffApprovalFilter"', false);
            $response->assertSee('All Approval States', false);
            $response->assertSee('Pending Approval', false);
            $response->assertSee('<option value="approved">Approved</option>', false);
            $response->assertSee('Head Created', false);
            $response->assertDontSee('<option value="declined">Declined</option>', false);

            $response->assertSee('resetStaffCaseFilters()', false);
        }
    }

    #[Test]
    public function every_unit_staff_report_page_renders_with_staff_sidebar_and_no_head_links(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $reportRoutes = [
            '/Crime_Scene_Support/staff/report',
            '/Lab_Analysis/staff/report',
            '/Evidence_Management/staff/report',
            '/Digital_Forensics/staff/report',
            '/Forensic_Head/staff/report',
        ];

        foreach ($reportRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('STAFF WORKSTATION');
            $response->assertDontSee('Unit Permissions');
            $response->assertDontSee('background-color: #f1f5f9 !important;', false);
        }

        $this->actingAs($staffUser)
            ->get('/staff/report')
            ->assertRedirect('/Crime_Scene_Support/staff/report');
    }

    #[Test]
    public function staff_generated_reports_display_staff_officers_name_instead_of_unit_head(): void
    {
        User::factory()->create([
            'fullname' => 'Forensic Directorate Head',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        User::factory()->create([
            'fullname' => 'CSSU Unit Head Only',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        User::factory()->create([
            'fullname' => 'EMU Unit Head Only',
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);

        User::factory()->create([
            'fullname' => 'Lab Unit Head Only',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'Staff Report Author',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $cssPdf = $this->actingAs($staffUser)->get('/Crime_Scene_Support/report_pdf');
        $cssPdf->assertStatus(200);
        $cssPdf->assertSee('Staff Report Author');
        $cssPdf->assertSee('PREPARED BY (STAFF OFFICER)');
        $cssPdf->assertDontSee('HEAD OF CRIME SCENE SUPPORT');

        $emuPdf = $this->actingAs($staffUser)->get('/Evidence_Management/report_pdf');
        $emuPdf->assertStatus(200);
        $emuPdf->assertSee('Staff Report Author');
        $emuPdf->assertSee('PREPARED BY (STAFF OFFICER)');
        $emuPdf->assertDontSee('EMU Unit Head Only');
        $emuPdf->assertDontSee('Head of Evidence Management Unit, NACOC');

        $labPdf = $this->actingAs($staffUser)->get('/Lab_Analysis/lab_sitrep_pdf');
        $labPdf->assertStatus(200);
        $labPdf->assertSee('Staff Report Author');
        $labPdf->assertSee('Prepared By (Staff Officer)');
        $labPdf->assertDontSee('Lab Unit Head Only');
        $labPdf->assertDontSee('Head of Laboratory Analysis Unit, NACOC');

        $emuReportPage = $this->actingAs($staffUser)->get('/Evidence_Management/staff/report');
        $emuReportPage->assertStatus(200);
        $emuReportPage->assertSee('Officer: Staff Report Author');
    }

    #[Test]
    public function every_unit_staff_dashboard_renders_with_staff_routes_and_no_head_links(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $dashboardRoutes = [
            '/Crime_Scene_Support/staff/dashboard',
            '/Lab_Analysis/staff/dashboard',
            '/Evidence_Management/staff/dashboard',
            '/Digital_Forensics/staff/dashboard',
            '/Forensic_Head/staff/dashboard',
            '/staff/dashboard',
        ];

        foreach ($dashboardRoutes as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('Staff Dashboard');
            $response->assertSee('NACOC_LOGO.PNG', false);
            $response->assertSee('/staff/case', false);
            $response->assertDontSee('/notifications"', false);
            $response->assertDontSee('/permissions"', false);
        }
    }

    #[Test]
    public function head_navbar_shows_reviews_and_approvals_and_staff_cases_require_head_approval_before_saving(): void
    {
        $headUser = User::factory()->create([
            'fullname' => 'CSSU Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'CSSU Staff Submitter',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        // 1. Head dashboards show Reviews & Approvals button in navbar
        foreach ([
            '/Crime_Scene_Support/head' => '/Crime_Scene_Support/reviews-approvals',
            '/Lab_Analysis' => '/Lab_Analysis/reviews-approvals',
            '/Evidence_Management' => '/Evidence_Management/reviews-approvals',
            '/Digital_Forensics' => '/Digital_Forensics/reviews-approvals',
            '/Forensic_Head' => '/Forensic_Head/reviews-approvals',
        ] as $dashUrl => $approvalUrl) {
            $dashRes = $this->actingAs($headUser)->get($dashUrl);
            $dashRes->assertStatus(200);
            $dashRes->assertSee('Reviews &amp; Approvals', false);
            $dashRes->assertSee($approvalUrl, false);
        }

        // 2. Staff submits a case: staged in staff_case_submissions, NOT yet in main cases tables
        $payload = [
            'title' => 'Staged Harbor Seizure',
            'suspect_name' => 'Kofi Mensah',
            'suspect_gender' => 'Male',
            'suspect_age' => 32,
            'unit_name' => 'Crime Scene Support',
            'priority' => 'HIGH',
            'offense_type' => 'Narcotics Trafficking',
            'submitting_officer' => 'CSSU Staff Submitter',
        ];

        $storeRes = $this->actingAs($staffUser)->postJson('/cases', $payload);
        $storeRes->assertStatus(201);
        $storeRes->assertJson([
            'success' => true,
            'pending_approval' => true,
        ]);

        $this->assertDatabaseCount('single_suspect_cases', 0);
        $this->assertDatabaseHas('staff_case_submissions', [
            'suspect_name' => 'Kofi Mensah',
            'status' => 'Pending Approval',
        ]);

        $submission = \App\Models\StaffCaseSubmission::firstOrFail();

        // 3. Head views Reviews & Approvals page, inspects dossier, and approves -> saves to database
        $reviewPage = $this->actingAs($headUser)->get('/Crime_Scene_Support/reviews-approvals');
        $reviewPage->assertStatus(200);
        $reviewPage->assertSee('Kofi Mensah');
        $reviewPage->assertSee('Review Dossier');
        $reviewPage->assertSee("/Crime_Scene_Support/reviews-approvals/{$submission->id}", false);
        $reviewPage->assertDontSee('Quick Inspect');

        $dossierPage = $this->actingAs($headUser)->get("/Crime_Scene_Support/reviews-approvals/{$submission->id}");
        $dossierPage->assertStatus(200);
        $dossierPage->assertSee('Kofi Mensah');
        $dossierPage->assertSee('Approve &amp; Save', false);

        $approveRes = $this->actingAs($headUser)->post("/reviews-approvals/{$submission->id}/approve");
        $approveRes->assertRedirect();

        $this->assertDatabaseHas('staff_case_submissions', [
            'id' => $submission->id,
            'status' => 'Approved',
        ]);
        $this->assertDatabaseHas('single_suspect_cases', [
            'suspect_name' => 'Kofi Mensah',
        ]);

        // 4. Second staff submission declined -> NOT saved to database
        $declineStoreRes = $this->actingAs($staffUser)->postJson('/cases', [
            'title' => 'Declined Test Case',
            'suspect_name' => 'Rejected Suspect',
            'unit_name' => 'Crime Scene Support',
            'priority' => 'MEDIUM',
        ]);
        $declineStoreRes->assertStatus(201);
        $declinedId = $declineStoreRes->json('submission_id');

        $this->actingAs($headUser)->post("/reviews-approvals/{$declinedId}/decline")->assertRedirect();
        $this->assertDatabaseHas('staff_case_submissions', [
            'id' => $declinedId,
            'status' => 'Declined',
        ]);
        $this->assertDatabaseMissing('single_suspect_cases', [
            'suspect_name' => 'Rejected Suspect',
        ]);

        // 5. Head of another unit cannot view, approve, or decline a staff submission from Crime Scene Support
        $otherUnitHead = User::create([
            'service_id' => 'LAU-HEAD-900',
            'fullname' => 'Lab Unit Head',
            'username' => 'lau_head_900',
            'email' => 'lau_head_900@nacoc.gov.gh',
            'phone_number' => '0244000900',
            'password' => bcrypt('password'),
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $crossStoreRes = $this->actingAs($staffUser)->postJson('/cases', [
            'title' => 'Cross Unit Guard Case',
            'suspect_name' => 'Kwame Boateng',
            'unit_name' => 'Crime Scene Support',
            'priority' => 'HIGH',
        ]);
        $crossStoreRes->assertStatus(201);
        $crossId = $crossStoreRes->json('submission_id');

        $this->actingAs($otherUnitHead)->get('/Lab_Analysis/reviews-approvals')
            ->assertStatus(200)
            ->assertDontSee('Kwame Boateng');
        $this->actingAs($otherUnitHead)->get('/Crime_Scene_Support/reviews-approvals')
            ->assertStatus(403);
        $this->actingAs($otherUnitHead)->post("/reviews-approvals/{$crossId}/approve")
            ->assertStatus(403);
        $this->actingAs($otherUnitHead)->post("/reviews-approvals/{$crossId}/decline")
            ->assertStatus(403);

        $this->assertDatabaseHas('staff_case_submissions', [
            'id' => $crossId,
            'status' => 'Pending Approval',
        ]);
    }

    #[Test]
    public function digital_forensics_staff_forensic_status_page_renders_with_staff_sidebar_and_status_form(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Digital Staff Examiner',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        foreach (['/Digital_Forensics/staff/forensic-status', '/Digital_Forensics/staff/forensic_status'] as $route) {
            $response = $this->actingAs($staffUser)->get($route);
            $response->assertStatus(200);
            $response->assertSee('STAFF WORKSTATION');
            $response->assertSee('Digital Staff Examiner');
            $response->assertSee('Update Forensic Investigation Status');
            $response->assertSee('Digital Forensic &amp; Investigation Directory', false);
            $response->assertSee('/Digital_Forensics/staff/forensic-status', false);
            $response->assertDontSee('Unit Sitrep &amp; Reports', false);
            $response->assertDontSee('Unit Permissions');
        }
    }

    #[Test]
    public function every_unit_staff_case_rollback_page_renders_with_correction_queue(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'CSSU Rollback Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        // Empty state for another unit with no rolled-back submissions
        $this->actingAs($staffUser)
            ->get('/Lab_Analysis/staff/case-rollback')
            ->assertStatus(200)
            ->assertSee('Case Rollback &amp; Corrections', false)
            ->assertSee('No Rolled Back Cases for Lab Analysis')
            ->assertDontSee('CORRECTION &amp; RESUBMISSION WORKSTATION', false);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'NCC-2026-8841',
            'title' => 'Republic vs Kwaku Mensah',
            'suspect_name' => 'Kwaku Mensah',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'reviewed_by_name' => 'Commander Mensah',
            'review_notes' => 'Re-upload clearer front mugshot and verify suspect nationality.',
            'rollback_corrections' => [
                ['field' => 'Suspect Bio-Data', 'note' => 'Verify nationality field'],
                ['field' => 'Mugshot Captures', 'note' => 'Blurry front capture'],
            ],
            'rolled_back_at' => now(),
            'reviewed_at' => now(),
            'payload' => ['suspect_name' => 'Kwaku Mensah'],
        ]);

        foreach ([
            '/Crime_Scene_Support/staff/case-rollback',
            '/Lab_Analysis/staff/case-rollback',
            '/Evidence_Management/staff/case-rollback',
            '/Digital_Forensics/staff/case-rollback',
            '/Forensic_Head/staff/case-rollback',
        ] as $prefixRoute) {
            $editPath = $prefixRoute . '/' . $submission->id;
            $response = $this->actingAs($staffUser)->get($prefixRoute);
            $response->assertStatus(200);
            $response->assertSee('Returned Submissions Requiring Corrections');
            $response->assertSee('Kwaku Mensah');
            $response->assertSee('Suspect Bio-Data');
            $response->assertSee('Mugshot Captures');
            $response->assertSee('2 Sections Flagged');
            $response->assertSee('Open &amp; Fix Corrections', false);
            $response->assertSee($editPath, false);
            $response->assertDontSee('CORRECTION &amp; RESUBMISSION WORKSTATION', false);
        }
    }

    #[Test]
    public function staff_submission_review_renders_safely_even_if_weapon_condition_is_array_or_corrupted(): void
    {
        $headUser = User::factory()->create([
            'fullname' => 'Digital Forensics Unit Head',
            'unit' => 'Digital Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'Digital Forensics Staff Officer',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Digital Forensics',
            'case_number_preview' => 'NCC-2026-9999',
            'title' => 'Republic vs Test Phone Case',
            'suspect_name' => 'John Doe',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'title' => 'Republic vs Test Phone Case',
                'suspect_name' => 'John Doe',
                'suspect_gender' => 'Male',
                'suspect_age' => 30,
                'unit_name' => 'Digital Forensics',
                'priority' => 'HIGH',
                'offense_type' => 'Cyber Fraud',
                'submitting_officer' => 'Digital Forensics Staff Officer',
                'docket_data' => [
                    'seizures' => [
                        [
                            'category' => 'Electronic Devices',
                            'seizure_name' => 'Apple iPhone 13 Pro Max',
                            'commodity_name' => 'Apple iPhone 13 Pro Max',
                            'dev_model' => 'Apple iPhone 13 Pro Max',
                            'dev_serial_no' => 'F17GC0XXXXXX',
                            'dev_imei1' => '354892019283741',
                            'dev_storage' => '128 GB',
                            // Simulate DOM elements or corrupted serialization:
                            'wpn_condition' => [[], [], [], []],
                            'wpn' => [
                                'condition' => [[], []],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($headUser)->get("/Digital_Forensics/reviews-approvals/{$submission->id}");
        $response->assertStatus(200);
        $response->assertSee('Apple iPhone 13 Pro Max');
        $response->assertSee('354892019283741');
        $response->assertSee('F17GC0XXXXXX');
        $response->assertSee('128 GB');
    }

    #[Test]
    public function unit_head_can_rollback_case_without_403_and_returns_correct_unit_redirect_and_popover_message(): void
    {
        $headUser = User::factory()->create([
            'fullname' => 'Digital Forensics Unit Head',
            'unit' => 'Digital Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'Digital Forensics Staff Officer',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Digital Forensics',
            'case_number_preview' => 'NCC-2026-7777',
            'title' => 'Republic vs Kwame Digital',
            'suspect_name' => 'Kwame Digital',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'title' => 'Republic vs Kwame Digital',
                'suspect_name' => 'Kwame Digital',
                'unit_name' => 'Digital Forensics',
                'priority' => 'HIGH',
            ],
        ]);

        // Staff sidebar has NO red badge before rollback (count is 0)
        $staffPreCheck = $this->actingAs($staffUser)->get('/Digital_Forensics/staff/case');
        $staffPreCheck->assertStatus(200);
        $staffPreCheck->assertDontSee('bg-red-600 rounded-full', false);

        // 1. AJAX rollback request returns JSON with success and unit-specific redirect URL
        $ajaxRes = $this->actingAs($headUser)->postJson("/Digital_Forensics/reviews-approvals/{$submission->id}/rollback", [
            'corrections' => [
                ['field' => 'Suspect Bio-Data', 'note' => 'Please provide suspect residential address'],
            ],
            'review_notes' => 'Please update requested corrections before end of day.',
        ]);

        $ajaxRes->assertStatus(200);
        $ajaxRes->assertJson([
            'success' => true,
            'redirect' => url('/Digital_Forensics/reviews-approvals'),
        ]);

        $this->assertDatabaseHas('staff_case_submissions', [
            'id' => $submission->id,
            'status' => 'Rolled Back',
            'reviewed_by' => $headUser->id,
        ]);

        // Staff sidebar now shows red count badge for the rolled-back case
        $staffPostCheck = $this->actingAs($staffUser)->get('/Digital_Forensics/staff/case');
        $staffPostCheck->assertStatus(200);
        $staffPostCheck->assertSee('bg-red-600 rounded-full animate-pulse', false);
        $staffPostCheck->assertSee('1 Case(s) awaiting corrections', false);

        // 2. Head opens the rolled-back case in Decision History: Rollback & Approve buttons are GREYED OUT (disabled)
        $historyViewRes = $this->actingAs($headUser)->get("/Digital_Forensics/reviews-approvals/{$submission->id}");
        $historyViewRes->assertStatus(200);
        $historyViewRes->assertSee('Awaiting Staff Corrections');
        $historyViewRes->assertSee('disabled', false);
        $historyViewRes->assertSee('cursor-not-allowed', false);

        // While rolled back, approve attempt is blocked
        $blockedApproveRes = $this->actingAs($headUser)->post("/reviews-approvals/{$submission->id}/approve");
        $blockedApproveRes->assertSessionHas('error');

        // 3. Staff officer corrects and resubmits the case docket
        $resubmitRes = $this->actingAs($staffUser)->post("/Digital_Forensics/staff/case-rollback/{$submission->id}/resubmit", [
            'suspect_name' => 'Kwame Digital Corrected',
            'resubmit_notes' => 'Address details have been added.',
        ]);
        $resubmitRes->assertRedirect();

        $submission->refresh();
        $this->assertEquals('Pending Approval', $submission->status);

        // Once resubmitted, the staff sidebar count returns to 0 and the red badge is removed
        $staffAfterResubmit = $this->actingAs($staffUser)->get('/Digital_Forensics/staff/case');
        $staffAfterResubmit->assertStatus(200);
        $staffAfterResubmit->assertDontSee('bg-red-600 rounded-full', false);

        // 4. Head opens the resubmitted case: buttons are UNLOCKED / ACTIVE again
        $activeViewRes = $this->actingAs($headUser)->get("/Digital_Forensics/reviews-approvals/{$submission->id}");
        $activeViewRes->assertStatus(200);
        $activeViewRes->assertSee('Approve &amp; Save Case', false);
        $activeViewRes->assertDontSee('Awaiting Staff Corrections');

        // Head can now successfully approve the resubmitted case
        $finalApproveRes = $this->actingAs($headUser)->post("/Digital_Forensics/reviews-approvals/{$submission->id}/approve");
        $finalApproveRes->assertRedirect(url('/Digital_Forensics/reviews-approvals'));

        $submission->refresh();
        $this->assertEquals('Approved', $submission->status);
    }
}

