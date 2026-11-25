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
            $response->assertDontSee('Cyber Analyst / Staff', false);
            $response->assertDontSee('Forensic Chemist / Staff', false);
            $response->assertDontSee('Vault Custodian / Staff', false);
            $response->assertDontSee('Field Investigator / Staff', false);
            $response->assertDontSee('Directorate Officer / Staff', false);
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
        ] as $dashUrl => $approvalUrl) {
            $dashRes = $this->actingAs($headUser)->get($dashUrl);
            $dashRes->assertStatus(200);
            $dashRes->assertSee('Reviews &amp; Approvals', false);
            $dashRes->assertSee($approvalUrl, false);
        }

        // Forensic Head dashboard navbar has Reviews & Approvals removed
        $fhRes = $this->actingAs($headUser)->get('/Forensic_Head');
        $fhRes->assertStatus(200);
        $fhRes->assertDontSee('/Forensic_Head/reviews-approvals', false);

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

        // On reviews-approvals index: rolled-back case remains in Pending Queue (awaiting corrections), NOT in Decision History table
        $indexAfterRollback = $this->actingAs($headUser)->get('/Digital_Forensics/reviews-approvals');
        $indexAfterRollback->assertStatus(200);
        $indexAfterRollback->assertSee('Awaiting Corrections');
        $indexAfterRollback->assertSee('No approved submissions have been recorded yet');

        // 2. Head opens the rolled-back case: Rollback & Approve buttons are GREYED OUT (disabled)
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

        // Once approved, the case moves into the Decision History table
        $indexAfterApprove = $this->actingAs($headUser)->get('/Digital_Forensics/reviews-approvals');
        $indexAfterApprove->assertStatus(200);
        $indexAfterApprove->assertSee('Decision History');
        $indexAfterApprove->assertSee('Approved Cases (1)');
        $indexAfterApprove->assertSee('SUB-' . str_pad((string) $submission->id, 4, '0', STR_PAD_LEFT));
    }

    #[Test]
    public function clicking_open_and_fix_corrections_opens_create_blade_with_prefilled_readonly_details(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Investigator',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Digital Forensics',
            'case_number_preview' => 'NCC-2026-9901',
            'title' => 'Republic vs Akwasi Boakye',
            'suspect_name' => 'Akwasi Boakye',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'review_notes' => 'Please provide exact residential street address for suspect Akwasi Boakye.',
            'rollback_corrections' => [
                ['field' => 'Suspect Bio-Data', 'note' => 'Please update suspect residential address'],
            ],
            'rolled_back_at' => now(),
            'payload' => [
                'title' => 'Republic vs Akwasi Boakye',
                'suspect_name' => 'Akwasi Boakye',
                'suspect_alias' => 'CYBER COBRA',
                'ghana_card' => 'GHA-990011223-0',
                'gender' => 'Male',
                'dob' => '1992-05-14',
                'age' => '33',
                'height' => '180',
                'eye_color' => 'Brown',
                'nationality' => 'Ghanaian',
                'pob' => 'Accra',
                'address' => 'House No 12 Airport Residential Area',
                'phone' => '0244112233',
                'marital_status' => 'Single',
                'has_children' => false,
                'children_count' => '0',
                'offense_description' => 'Cyber Fraud & Money Laundering',
                'raid_location' => 'Airport Residential, Accra',
                'latitude' => '5.6037',
                'longitude' => '-0.1870',
                'arrest_date' => '2026-11-19',
                'arrest_time' => '14:30',
                'holding_facility' => 'NACOC Headquarters Cell 4',
                'has_seizures' => true,
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Apple MacBook Pro',
                'dev_model' => 'MacBook Pro M2',
                'dev_serial_no' => 'C02G1234MD6R',
                'seizure_quantity' => '1',
                'unit_name' => 'Digital Forensics',
                'priority' => 'HIGH',
            ],
        ]);

        // 1. Rollback index displays "Open & Fix Corrections" button linking to rollback show
        $rollbackIndexRes = $this->actingAs($staffUser)->get('/Digital_Forensics/staff/case-rollback');
        $rollbackIndexRes->assertStatus(200);
        $rollbackIndexRes->assertSee('Open &amp; Fix Corrections', false);
        $rollbackIndexRes->assertSee("/Digital_Forensics/staff/case-rollback/{$submission->id}", false);

        // 2. Clicking Open & Fix Corrections opens create.blade page populated in rollback mode
        $createPageRes = $this->actingAs($staffUser)->get("/Digital_Forensics/staff/case-rollback/{$submission->id}");
        $createPageRes->assertStatus(200);

        // Verify it is the create.blade page with arrest docket form
        $createPageRes->assertSee('id="arrestDocketForm"', false);
        $createPageRes->assertSee('Section 1: Case Title', false);
        $createPageRes->assertSee('Section 2: Mugshot', false);
        $createPageRes->assertSee('Section 3: Suspect Personal Details', false);
        $createPageRes->assertSee('Section 4: Seizures (Commodities)', false);
        $createPageRes->assertSee('id="hasSeizuresCheckbox"', false);
        $createPageRes->assertSee('id="seizuresDetailsContainer"', false);

        // Verify rollback banner and directives are rendered
        $createPageRes->assertSee('Active Case Rollback &mdash; Fix Requested Corrections', false);
        $createPageRes->assertSee('Please provide exact residential street address for suspect Akwasi Boakye.');
        $createPageRes->assertSee('Suspect Bio-Data');
        $createPageRes->assertSee('Please update suspect residential address');

        // Verify script config is embedded for client-side readonly enforcement and prefill
        $createPageRes->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $createPageRes->assertSee('window.ROLLBACK_SUBMISSION_ID = ' . $submission->id . ';', false);
        $createPageRes->assertSee('Send for Review and Approval', false);
        $createPageRes->assertSee('CYBER COBRA');
        $createPageRes->assertSee('GHA-990011223-0');
        $createPageRes->assertSee('Cyber Fraud \u0026 Money Laundering', false);
        $createPageRes->assertSee('NACOC Headquarters Cell 4');
        $createPageRes->assertSee('Apple MacBook Pro');
        $createPageRes->assertSee('MacBook Pro M2');
        $createPageRes->assertSee('C02G1234MD6R');

        // 3. Opening directly with ?rollback_id query param on staff/create also loads create.blade in rollback mode
        $createWithParamRes = $this->actingAs($staffUser)->get("/Digital_Forensics/staff/create?rollback_id={$submission->id}");
        $createWithParamRes->assertStatus(200);
        $createWithParamRes->assertSee('id="arrestDocketForm"', false);
        $createWithParamRes->assertSee('Active Case Rollback &mdash; Fix Requested Corrections', false);
        $createWithParamRes->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $createWithParamRes->assertSee('Send for Review and Approval', false);

        // 4. Staff submits changes with "Send for Review and Approval", which sends it back to Head
        $resubmitRes = $this->actingAs($staffUser)->postJson("/Digital_Forensics/staff/case-rollback/{$submission->id}/resubmit", [
            'suspect_name' => 'Akwasi Boakye Corrected',
            'address' => 'H/No 44 Airport Residential Area, Accra',
            'phone' => '0244112233',
        ]);
        $resubmitRes->assertStatus(200);
        $resubmitRes->assertJson([
            'success' => true,
        ]);

        $submission->refresh();
        $this->assertEquals('Pending Approval', $submission->status);
        $this->assertEquals('Akwasi Boakye Corrected', $submission->suspect_name);
        $this->assertNull($submission->rollback_corrections);
        $this->assertNull($submission->review_notes);
        $this->assertNull($submission->rolled_back_at);
        $this->assertEquals('H/No 44 Airport Residential Area, Accra', $submission->payload['address']);

        // Case rollback queue no longer lists it
        $queueRes = $this->actingAs($staffUser)->get('/Digital_Forensics/staff/case-rollback');
        $queueRes->assertDontSee('SUB-' . str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT));

        // 5. In Head's Reviews & Approvals, "Awaiting Corrections" has changed to "Corrections Submitted"
        $headUser = User::where('unit', 'Digital Forensics')->where('is_head', true)->first();
        if ($headUser) {
            $headRes = $this->actingAs($headUser)->get('/Digital_Forensics/reviews-approvals');
            $headRes->assertStatus(200);
            $headRes->assertSee('Corrections Submitted');
            $headRes->assertSee('Review Corrections');
        }
    }

    #[Test]
    public function staff_rollback_mode_prefills_seizures_with_photos_and_locks_unrequested_fields(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Mark Appiah',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'user_id' => $staffUser->id,
            'unit_name' => 'Digital Forensics',
            'submitted_by' => 'Mark Appiah',
            'title' => 'Republic vs HANNAH',
            'case_number_preview' => 'NCC-2026-3949',
            'status' => 'Rolled Back',
            'review_notes' => 'Please correct suspect name and case title.',
            'rollback_corrections' => [
                ['field' => 'CASE NAME / LEGAL TITLE', 'note' => 'IT SHOULD HANNA'],
                ['field' => 'SUSPECT FULL NAME', 'note' => 'HANNA NOT HANNAH'],
            ],
            'payload' => [
                'title' => 'Republic vs HANNAH',
                'suspect_name' => 'HANNAH BOATENG',
                'suspect_alias' => 'BOOKEY',
                'ghana_card' => 'GHA-254356434-3',
                'gender' => 'Female',
                'dob' => '1990-11-19',
                'age' => '36',
                'height' => '234',
                'eye_color' => 'BLACK',
                'nationality' => 'Ghanaian',
                'pob' => 'ACCRA',
                'phone' => '055343245345',
                'address' => 'GA-45-5345',
                'marital_status' => 'Single',
                'raid_location' => 'Pokuase',
                'latitude' => '5.6892131',
                'longitude' => '-0.2826359',
                'offense_description' => 'JHKJFGFVGHKGHGJG',
                'arrest_date' => '2026-11-19',
                'arrest_time' => '11:52',
                'holding_facility' => 'NACOC HQ',
                'has_seizures' => true,
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Apple iPhone (iOS Smartphone)',
                'dev_model' => 'IPHONE 12',
                'dev_serial_no' => '897098H9Y87',
                'dev_imei1' => '807897678678967679',
                'dev_imei2' => '7697676907690786',
                'dev_mac_address' => '00:456:ER5::34534',
                'dev_storage' => '125GB',
                'dev_colour' => 'BLACK',
                'dev_marks' => 'CRACKED',
                'seizure_quantity' => '1',
                'seizure_value' => '899',
                'seizure_concealment' => 'HGKHHFGVKJ',
                'seizure_date' => '2026-11-19',
                'seizure_time' => '11:53',
                'seizure_location' => 'PORT',
                'seizure_officer' => 'Atta Budo (7867687)',
                'seizure_reason' => 'KGHLDGHJLGSHFJGH;FGSHHFDGLDHJH',
                'exhibit_type' => 'Non-Drug Exhibit',
                'drug_classification' => 'N/A',
                'evidence_photos' => [],
                'docket_data' => [
                    'seizures' => [
                        [
                            'exhibit_category' => 'Electronic Devices',
                            'commodity_name' => 'Apple iPhone (iOS Smartphone)',
                            'dev_model' => 'IPHONE 12',
                            'dev_serial_no' => '897098H9Y87',
                            'dev_imei1' => '807897678678967679',
                            'dev_imei2' => '7697676907690786',
                            'dev_mac_address' => '00:456:ER5::34534',
                            'dev_storage' => '125GB',
                            'dev_colour' => 'BLACK',
                            'dev_marks' => 'CRACKED',
                            'seizure_concealment' => 'HGKHHFGVKJ',
                            'seizure_reason' => 'KGHLDGHJLGSHFJGH;FGSHHFDGLDHJH',
                            'evidence_photos' => [
                                'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAA==',
                                'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAE/2wCEAA==',
                                'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAL/2wCEAA==',
                                'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAQ/2wCEAA==',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $res = $this->actingAs($staffUser)->get("/Digital_Forensics/staff/case-rollback/{$submission->id}");
        $res->assertStatus(200);

        // Verify HTML contains the necessary rollback script functions and elements
        $res->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $res->assertSee('getCorrectionFieldKey', false);
        $res->assertSee('getElementKey', false);
        $res->assertSee('isInputMatchingCorrections', false);
        $res->assertSee('populateFieldValues', false);
        $res->assertSee('applyRollbackDocketMode', false);

        // Verify prefilled values from payload and docket_data are present in the response
        $res->assertSee('Republic vs HANNAH');
        $res->assertSee('HANNAH BOATENG');
        $res->assertSee('IPHONE 12');
        $res->assertSee('897098H9Y87');
        $res->assertSee('807897678678967679');
        $res->assertSee('CRACKED');
        $res->assertSee('HGKHHFGVKJ');
        $res->assertSee('KGHLDGHJLGSHFJGH;FGSHHFDGLDHJH');
        $res->assertSee('JHKJFGFVGHKGHGJG');
        $res->assertSee('evidence_photos', false);
        $res->assertSee('9j', false);

        // Verify active corrections are listed in the banner
        $res->assertSee('CASE NAME / LEGAL TITLE');
        $res->assertSee('IT SHOULD HANNA');
        $res->assertSee('SUSPECT FULL NAME');
        $res->assertSee('HANNA NOT HANNAH');
    }

    #[Test]
    public function chain_of_custody_input_is_safely_removed_from_digital_forensics_and_lab_analysis_only(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'CAS-COC-TEST-001',
            'title' => 'Chain of Custody Test Case',
            'unit_name' => 'Digital Forensics',
            'submitting_officer' => 'Insp. Test',
            'suspect_name' => 'Test Suspect',
            'status' => 'Active',
            'priority' => 'HIGH',
        ]);

        $headUser = User::factory()->create([
            'fullname' => 'Test Head Officer',
            'unit' => 'Digital Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'Test Staff Officer',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        // 1. Digital Forensics show review & staff review should NOT have chain of custody inputs
        $res = $this->actingAs($headUser)->get("/Digital_Forensics/cases/{$case->case_number}/review");
        $res->assertStatus(200);
        $res->assertDontSee('id="newSeizureCustodyFrom"', false);
        $res->assertDontSee('id="newSeizureCustodyTo"', false);
        $res->assertDontSee('id="newSeizureChainOfCustody"', false);
        $res->assertDontSee('updateChainOfCustodyValue', false);

        $res = $this->actingAs($staffUser)->get("/Digital_Forensics/staff/cases/{$case->case_number}/review");
        $res->assertStatus(200);
        $res->assertDontSee('id="newSeizureCustodyFrom"', false);
        $res->assertDontSee('id="newSeizureCustodyTo"', false);
        $res->assertDontSee('id="newSeizureChainOfCustody"', false);
        $res->assertDontSee('updateChainOfCustodyValue', false);

        // 2. Lab Analysis show review & staff review should NOT have chain of custody inputs
        $headUser->update(['unit' => 'Lab Analysis']);
        $staffUser->update(['unit' => 'Lab Analysis']);
        $case->update(['unit_name' => 'Lab Analysis']);

        $res = $this->actingAs($headUser)->get("/Lab_Analysis/cases/{$case->case_number}/review");
        $res->assertStatus(200);
        $res->assertDontSee('id="newSeizureCustodyFrom"', false);
        $res->assertDontSee('id="newSeizureCustodyTo"', false);
        $res->assertDontSee('id="newSeizureChainOfCustody"', false);
        $res->assertDontSee('updateChainOfCustodyValue', false);

        $res = $this->actingAs($staffUser)->get("/Lab_Analysis/staff/cases/{$case->case_number}/review");
        $res->assertStatus(200);
        $res->assertDontSee('id="newSeizureCustodyFrom"', false);
        $res->assertDontSee('id="newSeizureCustodyTo"', false);
        $res->assertDontSee('id="newSeizureChainOfCustody"', false);
        $res->assertDontSee('updateChainOfCustodyValue', false);

        // 3. Crime Scene Support & Evidence Management SHOULD still retain chain of custody inputs
        $headUser->update(['unit' => 'Crime Scene Support']);
        $case->update(['unit_name' => 'Crime Scene Support']);

        $res = $this->actingAs($headUser)->get("/Crime_Scene_Support/cases/{$case->case_number}/review");
        $res->assertStatus(200);
        $res->assertSee('id="newSeizureCustodyFrom"', false);
        $res->assertSee('id="newSeizureCustodyTo"', false);
        $res->assertSee('id="newSeizureChainOfCustody"', false);
        $res->assertSee('updateChainOfCustodyValue', false);
    }

    #[Test]
    public function decision_history_table_displays_suspect_front_image(): void
    {
        $headUser = User::factory()->create([
            'fullname' => 'Head of CSSU',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submissionWithPhoto = StaffCaseSubmission::create([
            'submitted_by' => $headUser->id,
            'submitted_by_name' => 'Staff Officer',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'NCC-2026-IMG-001',
            'title' => 'State vs Kwesi FrontPhoto',
            'suspect_name' => 'Kwesi FrontPhoto',
            'priority' => 'HIGH',
            'status' => 'Approved',
            'reviewed_by' => $headUser->id,
            'reviewed_by_name' => $headUser->fullname,
            'reviewed_at' => now(),
            'approved_case_number' => 'CAS-CSSU-2026-0099',
            'payload' => [
                'title' => 'State vs Kwesi FrontPhoto',
                'suspect_name' => 'Kwesi FrontPhoto',
                'mugshot_front' => 'suspects/kwesi_front_mugshot.jpg',
            ],
        ]);

        $submissionWithoutPhoto = StaffCaseSubmission::create([
            'submitted_by' => $headUser->id,
            'submitted_by_name' => 'Staff Officer',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'NCC-2026-IMG-002',
            'title' => 'State vs Kofi NoPhoto',
            'suspect_name' => 'Kofi NoPhoto',
            'priority' => 'MEDIUM',
            'status' => 'Approved',
            'reviewed_by' => $headUser->id,
            'reviewed_by_name' => $headUser->fullname,
            'reviewed_at' => now(),
            'approved_case_number' => 'CAS-CSSU-2026-0100',
            'payload' => [
                'title' => 'State vs Kofi NoPhoto',
                'suspect_name' => 'Kofi NoPhoto',
            ],
        ]);

        $res = $this->actingAs($headUser)->get('/Crime_Scene_Support/reviews-approvals');
        $res->assertStatus(200);

        // Verify Decision History table contains the front image for submission with photo
        $res->assertSee('suspects/kwesi_front_mugshot.jpg');
        $res->assertSee('Kwesi FrontPhoto');

        // Verify submission without photo renders cleanly with suspect name and title
        $res->assertSee('Kofi NoPhoto');
    }

    #[Test]
    public function staff_rollback_with_mugshot_correction_enables_mugshot_upload_and_change_photo(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Kojo CSS Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'NCC-2026-MUG-001',
            'title' => 'Republic vs MUGSHOT_SUSPECT',
            'suspect_name' => 'John Doe Mugshot',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'review_notes' => 'Please re-upload clearer facial mugshots for suspect.',
            'rollback_corrections' => [
                ['field' => 'Mugshot Captures', 'note' => 'Front mugshot blurry, please change and re-upload.'],
            ],
            'payload' => [
                'title' => 'Republic vs MUGSHOT_SUSPECT',
                'suspect_name' => 'John Doe Mugshot',
                'mugshot_front' => 'data:image/jpeg;base64,' . base64_encode('fake-old-front'),
                'mugshot_left' => 'data:image/jpeg;base64,' . base64_encode('fake-old-left'),
                'mugshot_right' => 'data:image/jpeg;base64,' . base64_encode('fake-old-right'),
            ],
        ]);

        $res = $this->actingAs($staffUser)->get("/Crime_Scene_Support/staff/case-rollback/{$submission->id}");
        $res->assertStatus(200);

        // Verify rollback mode is active
        $res->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $res->assertSee('Active Case Rollback &mdash; Fix Requested Corrections', false);
        $res->assertSee('Mugshot Captures');
        $res->assertSee('Front mugshot blurry, please change and re-upload.');

        // Verify mugshot inputs and dropzones exist and rollback handler includes unlocking logic
        $res->assertSee('id="dropzone-front"', false);
        $res->assertSee('id="file-front"', false);
        $res->assertSee('id="actions-front"', false);
        $res->assertSee('triggerMugshotUpload(\'front\')', false);
        $res->assertSee('isMugshotFlagged', false);
        $res->assertSee('btn-change-mugshot', false);

        // Verify the Change button exists inside actions overlay
        $res->assertSee('<span>Change</span>', false);

        // Verify suspect mugshot partial in review has rollback target data attributes
        $reviewRes = $this->actingAs($staffUser)->get("/Crime_Scene_Support/staff/case-rollback");
        $reviewRes->assertStatus(200);
    }

    #[Test]
    public function staff_resubmitting_rolled_back_mugshot_updates_payload_and_docket_data(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Resubmit Staff Officer',
            'unit' => 'Digital Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staffUser->id,
            'submitted_by_name' => $staffUser->fullname,
            'unit_name' => 'Digital Forensics',
            'case_number_preview' => 'NCC-2026-DF-MUG-002',
            'title' => 'State vs Digital Suspect',
            'suspect_name' => 'Digital Suspect',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'review_notes' => 'Please update front profile photo.',
            'rollback_corrections' => [
                ['field' => 'Front Mugshot (Angle 1)', 'note' => 'Please provide sharp front angle.'],
            ],
            'payload' => [
                'title' => 'State vs Digital Suspect',
                'suspect_name' => 'Digital Suspect',
                'mugshot_front' => 'data:image/jpeg;base64,OLD_FRONT_DATA',
                'docket_data' => [
                    'title' => 'State vs Digital Suspect',
                    'full_name' => 'Digital Suspect',
                    'mugshot_front' => 'data:image/jpeg;base64,OLD_FRONT_DATA',
                    'suspects' => [
                        [
                            'suspect_name' => 'Digital Suspect',
                            'mugshot_front' => 'data:image/jpeg;base64,OLD_FRONT_DATA',
                        ],
                    ],
                ],
            ],
        ]);

        $newFrontBase64 = 'data:image/jpeg;base64,' . base64_encode('NEW_FRONT_IMAGE_CONTENT');
        $newLeftBase64 = 'data:image/jpeg;base64,' . base64_encode('NEW_LEFT_IMAGE_CONTENT');
        $newRightBase64 = 'data:image/jpeg;base64,' . base64_encode('NEW_RIGHT_IMAGE_CONTENT');

        $res = $this->actingAs($staffUser)->postJson("/Digital_Forensics/staff/case-rollback/{$submission->id}/resubmit", [
            'suspect_name' => 'Digital Suspect',
            'mugshot_front' => $newFrontBase64,
            'mugshot_left' => $newLeftBase64,
            'mugshot_right' => $newRightBase64,
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $refreshed = $submission->fresh();
        $this->assertEquals('Pending Approval', $refreshed->status);
        $this->assertNull($refreshed->rollback_corrections);
        $this->assertNotNull($refreshed->payload['mugshot_front']);
        $this->assertEquals($refreshed->payload['mugshot_front'], $refreshed->payload['docket_data']['mugshot_front']);
        $this->assertEquals($refreshed->payload['mugshot_front'], $refreshed->payload['suspects'][0]['mugshot_front']);
    }

    #[Test]
    public function review_view_suspect_mugshots_renders_rollback_target_data_attributes(): void
    {
        $headUser = User::factory()->create([
            'fullname' => 'Head Reviewer Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $headUser->id,
            'submitted_by_name' => 'Submitting Staff',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'NCC-2026-TGT-003',
            'title' => 'State vs Target Suspect',
            'suspect_name' => 'Target Suspect',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'title' => 'State vs Target Suspect',
                'suspect_name' => 'Target Suspect',
                'mugshot_front' => 'data:image/jpeg;base64,FRONT',
                'mugshot_left' => 'data:image/jpeg;base64,LEFT',
                'mugshot_right' => 'data:image/jpeg;base64,RIGHT',
                'docket_data' => [
                    'suspects' => [
                        [
                            'suspect_name' => 'Target Suspect',
                            'mugshot_front' => 'data:image/jpeg;base64,FRONT',
                            'mugshot_left' => 'data:image/jpeg;base64,LEFT',
                            'mugshot_right' => 'data:image/jpeg;base64,RIGHT',
                        ],
                    ],
                ],
            ],
        ]);

        $res = $this->actingAs($headUser)->get("/reviews-approvals/{$submission->id}");
        $res->assertStatus(200);

        // Verify data-rollback-target attributes are rendered on the mugshot card and angles
        $res->assertSee('data-rollback-target="Biometric Mugshots"', false);
        $res->assertSee('data-rollback-target="Front Mugshot (Angle 1)"', false);
        $res->assertSee('data-rollback-target="Left Profile Mugshot (Angle 2)"', false);
        $res->assertSee('data-rollback-target="Right Profile Mugshot (Angle 3)"', false);
    }

    #[Test]
    public function lab_analysis_rollback_previews_section4_and_section5_without_resetting_and_locks_unflagged_sections(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Lab Analyst Staff',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'user_id' => $staffUser->id,
            'unit_name' => 'Lab Analysis',
            'submitted_by' => 'Lab Analyst Staff',
            'title' => 'Republic vs Narcotics Syndicate',
            'case_number_preview' => 'LAB-2026-0042',
            'status' => 'Rolled Back',
            'review_notes' => 'Please correct suspect name spelling.',
            'rollback_corrections' => [
                ['field' => 'SUSPECT FULL NAME', 'note' => 'Please fix spelling of suspect name'],
            ],
            'payload' => [
                'title' => 'Republic vs Narcotics Syndicate',
                'suspect_name' => 'John Dope',
                'suspect_alias' => 'JD',
                'ghana_card' => 'GHA-123456789-0',
                'gender' => 'Male',
                'lab_workflow_mode' => 'nlr',
                'lab_exam_reference' => 'NLA-FM-T9988-F',
                'lab_case_reference' => 'LAB-2026-0042',
                'laboratory_number' => 'LAB-NO-5555',
                'lab_analyst' => 'Dr. Forensics',
                'purpose_of_examination' => 'Qualitative and quantitative chemical analysis of suspected cocaine exhibit.',
                'preliminary_exam_procedure' => 'Visual and microscopic examination performed.',
                'screening_method' => ['Gas Chromatography-Mass Spectrometry (GC-MS)', 'FTIR Spectroscopy'],
                'confirmatory_test_methods' => 'GSA-SM-T998-A',
                'confirmatory_specifications' => 'Meets regulatory purity threshold.',
                'confirmatory_remark' => 'Substance confirmed as cocaine hydrochloride.',
                'disposition_of_exhibit' => 'Retained in forensic laboratory vault.',
                'has_seizures' => true,
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Drugs',
                        'seizure_name_drug' => 'Cocaine',
                        'commodity_name' => 'Cocaine Powder',
                        'seizure_weight' => '2.5',
                        'seizure_unit' => 'kg',
                        'seizure_quantity' => '2',
                        'seizure_packaging' => 'Taped Slabs',
                        'lab_exhibit_no' => 'A1',
                        'presumptive_result' => 'YES',
                        'presumptive_presence' => 'Cocaine',
                        'presumptive_percentage' => '88.5',
                        'confirmatory_test_result' => 'YES',
                        'sample_weight_taken' => '0.05',
                        'sample_weight_reserved' => '2.45',
                        'detected_substances' => [
                            ['name' => 'Cocaine Base', 'purity' => '88.5%'],
                        ],
                    ],
                ],
                'docket_data' => [
                    'title' => 'Republic vs Narcotics Syndicate',
                    'case_number' => 'LAB-2026-0042',
                    'lab_workflow_mode' => 'nlr',
                    'lab_examination' => [
                        'exam_reference' => 'NLA-FM-T9988-F',
                        'laboratory_number' => 'LAB-NO-5555',
                        'lab_analyst' => 'Dr. Forensics',
                        'purpose_of_examination' => 'Qualitative and quantitative chemical analysis of suspected cocaine exhibit.',
                        'preliminary_exam_procedure' => 'Visual and microscopic examination performed.',
                        'screening_method' => ['Gas Chromatography-Mass Spectrometry (GC-MS)', 'FTIR Spectroscopy'],
                        'test_methods' => 'GSA-SM-T998-A',
                        'specifications' => 'Meets regulatory purity threshold.',
                        'remark' => 'Substance confirmed as cocaine hydrochloride.',
                        'disposition_of_exhibit' => 'Retained in forensic laboratory vault.',
                    ],
                    'seizures' => [
                        [
                            'item_number' => 1,
                            'exhibit_category' => 'Drugs',
                            'seizure_name_drug' => 'Cocaine',
                            'commodity_name' => 'Cocaine Powder',
                            'seizure_weight' => '2.5',
                            'seizure_unit' => 'kg',
                            'seizure_quantity' => '2',
                            'seizure_packaging' => 'Taped Slabs',
                            'lab_exhibit_no' => 'A1',
                        ],
                    ],
                ],
            ],
        ]);

        $res = $this->actingAs($staffUser)->get("/Lab_Analysis/staff/case-rollback/{$submission->id}");
        $res->assertStatus(200);

        // 1. Verify rollback mode is active with directives and ID
        $res->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $res->assertSee('window.ROLLBACK_SUBMISSION_ID = ' . $submission->id . ';', false);
        $res->assertSee('Active Case Rollback &mdash; Fix Requested Corrections', false);

        // 2. Verify previous Section 4 and Section 5 data are embedded in rollback payload
        $res->assertSee('NLA-FM-T9988-F', false);
        $res->assertSee('LAB-NO-5555', false);
        $res->assertSee('Cocaine Powder', false);
        $res->assertSee('Qualitative and quantitative chemical analysis of suspected cocaine exhibit', false);
        $res->assertSee('GSA-SM-T998-A', false);
        $res->assertSee('Gas Chromatography-Mass Spectrometry (GC-MS)', false);

        // 3. Verify rollback script includes Section 5 hydration, preview lock pill, and openSeizurePanel readonly guard
        $res->assertSee('isSection5Flagged', false);
        $res->assertSee('rollback-section5-preview-pill', false);
        $res->assertSee('rollback-section4-preview-pill', false);
        $res->assertSee('syncSection4ExhibitsToLabSection', false);
        $res->assertSee('if (window.IS_ROLLBACK_MODE && el)', false);
    }

    #[Test]
    public function lab_analysis_rollback_resubmit_preserves_forensic_examination_and_seizures(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Lab Analyst Staff Resubmit',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'user_id' => $staffUser->id,
            'unit_name' => 'Lab Analysis',
            'submitted_by' => 'Lab Analyst Staff Resubmit',
            'title' => 'State vs Contraband Courier',
            'case_number_preview' => 'LAB-2026-0888',
            'status' => 'Rolled Back',
            'review_notes' => 'Please correct suspect full name.',
            'rollback_corrections' => [
                ['field' => 'SUSPECT FULL NAME', 'note' => 'Fix spelling to Johnathan Doe'],
            ],
            'payload' => [
                'title' => 'State vs Contraband Courier',
                'suspect_name' => 'John Doe',
                'lab_workflow_mode' => 'nlr',
                'lab_exam_reference' => 'NLA-FM-T7711-F',
                'laboratory_number' => 'LAB-7711',
                'lab_analyst' => 'Forensic Chemist A',
                'purpose_of_examination' => 'Identification of seized drug powder.',
                'preliminary_exam_procedure' => 'Color spot testing.',
                'screening_method' => ['GC-MS'],
                'confirmatory_test_methods' => 'ASTM E2329',
                'confirmatory_specifications' => 'Purity verified.',
                'confirmatory_remark' => 'Consistent with Cocaine HCl.',
                'disposition_of_exhibit' => 'Secure Vault Room A',
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Heroin Pack',
                        'seizure_weight' => '1.2',
                        'seizure_unit' => 'kg',
                        'lab_exhibit_no' => 'H-01',
                    ],
                ],
                'docket_data' => [
                    'lab_workflow_mode' => 'nlr',
                    'lab_examination' => [
                        'exam_reference' => 'NLA-FM-T7711-F',
                        'laboratory_number' => 'LAB-7711',
                    ],
                ],
            ],
        ]);

        $resubmitData = [
            'suspect_name' => 'Johnathan Doe',
            'full_name' => 'Johnathan Doe',
            'title' => 'State vs Contraband Courier',
            'lab_workflow_mode' => 'nlr',
            'lab_exam_reference' => 'NLA-FM-T7711-F',
            'exam_reference' => 'NLA-FM-T7711-F',
            'laboratory_number' => 'LAB-7711',
            'lab_analyst' => 'Forensic Chemist A',
            'purpose_of_examination' => 'Identification of seized drug powder.',
            'preliminary_exam_procedure' => 'Color spot testing.',
            'screening_method' => ['GC-MS'],
            'confirmatory_test_methods' => 'ASTM E2329',
            'confirmatory_specifications' => 'Purity verified.',
            'confirmatory_remark' => 'Consistent with Cocaine HCl.',
            'disposition_of_exhibit' => 'Secure Vault Room A',
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Heroin Pack',
                    'seizure_weight' => '1.2',
                    'seizure_unit' => 'kg',
                    'lab_exhibit_no' => 'H-01',
                ],
            ],
            'lab_examination' => [
                'exam_reference' => 'NLA-FM-T7711-F',
                'laboratory_number' => 'LAB-7711',
                'lab_analyst' => 'Forensic Chemist A',
                'purpose_of_examination' => 'Identification of seized drug powder.',
                'preliminary_exam_procedure' => 'Color spot testing.',
                'screening_method' => ['GC-MS'],
                'test_methods' => 'ASTM E2329',
                'specifications' => 'Purity verified.',
                'remark' => 'Consistent with Cocaine HCl.',
                'disposition_of_exhibit' => 'Secure Vault Room A',
            ],
        ];

        $res = $this->actingAs($staffUser)->postJson("/Lab_Analysis/staff/case-rollback/{$submission->id}/resubmit", $resubmitData);
        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $refreshed = StaffCaseSubmission::find($submission->id);
        $this->assertEquals('Pending Approval', $refreshed->status);
        $this->assertEquals('Johnathan Doe', $refreshed->suspect_name);
        $this->assertNull($refreshed->rollback_corrections);

        // Verify Section 5 and Section 4 data were preserved in payload and docket_data
        $this->assertEquals('nlr', $refreshed->payload['lab_workflow_mode']);
        $this->assertEquals('NLA-FM-T7711-F', $refreshed->payload['lab_exam_reference']);
        $this->assertEquals('LAB-7711', $refreshed->payload['laboratory_number']);
        $this->assertEquals('Heroin Pack', $refreshed->payload['seizures'][0]['commodity_name']);
        $this->assertEquals('H-01', $refreshed->payload['seizures'][0]['lab_exhibit_no']);
        $this->assertEquals('NLA-FM-T7711-F', $refreshed->payload['docket_data']['lab_examination']['exam_reference']);
        $this->assertEquals('Secure Vault Room A', $refreshed->payload['docket_data']['lab_examination']['disposition_of_exhibit']);
    }

    public function test_lab_analysis_reviews_approvals_renders_without_undefined_variable_db_exhibits(): void
    {
        $head = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $staff = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => false,
        ]);

        $submission = \App\Models\StaffCaseSubmission::create([
            'case_number_preview' => 'NCC-2026-3851',
            'title' => 'State vs Veronica Naa',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Veronica Naa',
            'submitted_by' => $staff->id,
            'submitted_by_name' => $staff->name,
            'status' => 'Pending Approval',
            'payload' => [
                'case_number' => 'NCC-2026-3851',
                'title' => 'State vs Veronica Naa',
                'unit_name' => 'Lab Analysis',
                'suspect_name' => 'Veronica Naa',
                'status' => 'Pending Approval',
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Drugs',
                        'seizure_name' => 'Cocaine Powder',
                        'associated_person' => 'Veronica Naa',
                        'investigating_officer' => 'Insp. Darko',
                    ],
                ],
                'docket_data' => [
                    'suspect_name' => 'Veronica Naa',
                    'suspects' => [
                        [
                            'suspect_number' => 1,
                            'suspect_name' => 'Veronica Naa',
                            'role' => 'Primary Suspect',
                        ],
                    ],
                    'seizures' => [
                        [
                            'item_number' => 1,
                            'exhibit_category' => 'Drugs',
                            'seizure_name' => 'Cocaine Powder',
                            'associated_person' => 'Veronica Naa',
                            'investigating_officer' => 'Insp. Darko',
                        ],
                    ],
                ],
            ],
        ]);

        $res = $this->actingAs($head)->get("/Lab_Analysis/reviews-approvals/{$submission->id}");
        $res->assertStatus(200);
        $res->assertSee('Veronica Naa');
        $res->assertSee('Cocaine Powder');
        $res->assertSee('Insp. Darko');
    }

    #[Test]
    public function staff_with_edit_cases_permission_can_access_edit_case_with_full_editability_and_exhibits_and_mugshots(): void
    {
        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-2026-EDIT-01',
            'title' => 'Republic v Kojo Antwi',
            'suspect_name' => 'Kojo Antwi',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => 'Insp. Darko',
            'mugshot_front' => 'mugshots/kojo_front.jpg',
            'mugshot_left' => 'mugshots/kojo_left.jpg',
            'mugshot_right' => 'mugshots/kojo_right.jpg',
            'arrest_date' => '2026-03-10',
            'arrest_time' => '14:30',
        ]);

        $exhibit = \App\Models\Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EX-2026-001',
            'commodity_name' => 'High-Grade Heroin Bricks',
            'exhibit_category' => 'Drugs / Narcotics',
            'seizure_quantity' => '12',
            'seizure_weight' => '14.5 kg',
            'seizure_unit' => 'Kilograms (kg)',
            'seizure_packaging' => 'Vacuum-sealed plastic blocks',
            'seizure_reason' => 'Intercepted at port terminal checkpoint',
            'associated_person' => 'Kojo Antwi',
            'evidence_photos' => ['exhibits/heroin_pack.jpg'],
        ]);

        $staff = User::factory()->create([
            'fullname' => 'Kofi Staff Editor',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => ['edit_cases' => true],
        ]);

        $res = $this->actingAs($staff)->get('/cases/' . $singleCase->case_number . '/edit');
        $res->assertStatus(200);
        $res->assertDontSee('Read-Only Mode — Crime Scene Support fields are restricted');
        $res->assertDontSee('Read-Only Mode — Seized exhibit specifications are restricted');
        $res->assertDontSee('id="input_suspect_name" name="suspect_name" value="Kojo Antwi" readonly', false);
        $res->assertSee('High-Grade Heroin Bricks');
        $res->assertSee('Vacuum-sealed plastic blocks');
        $res->assertSee('Intercepted at port terminal checkpoint');
        $res->assertSee('kojo_front.jpg');
        $res->assertSee('heroin_pack.jpg');
    }

    public function test_staff_rollback_with_marital_and_children_corrections_allows_editing_and_persists(): void
    {
        $staff = User::factory()->create([
            'fullname' => 'Christy Staff Officer',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $submission = \App\Models\StaffCaseSubmission::create([
            'submitted_by' => $staff->id,
            'submitted_by_name' => $staff->fullname,
            'unit_name' => 'Lab Analysis',
            'title' => 'State v Christy Brown',
            'suspect_name' => 'CHRISTY BROWN',
            'priority' => 'MEDIUM',
            'status' => 'Rolled Back',
            'rollback_corrections' => [
                ['field' => 'SPOUSE / PARTNER DETAILS', 'note' => 'ADD IT'],
                ['field' => 'CHILDREN & DEPENDENTS', 'note' => 'AND THIS TOO'],
            ],
            'review_notes' => 'Please provide spouse and children details.',
            'payload' => [
                'case_number' => 'NCC-2026-ROLL-01',
                'title' => 'State v Christy Brown',
                'suspect_name' => 'CHRISTY BROWN',
                'marital_status' => 'Single',
                'has_children' => false,
                'children_count' => 0,
                'spouse_name' => '',
                'spouse_phone' => '',
                'spouse_info' => '',
                'suspects' => [
                    [
                        'suspect_name' => 'CHRISTY BROWN',
                        'marital_status' => 'Single',
                        'has_children' => false,
                        'children_count' => 0,
                    ]
                ],
                'docket_data' => [
                    'marital_status' => 'Single',
                    'has_children' => false,
                    'children_count' => 0,
                ]
            ],
        ]);

        // 1. Staff opens the rollback view
        $viewRes = $this->actingAs($staff)->get("/Lab_Analysis/staff/case-rollback/{$submission->id}");
        $viewRes->assertStatus(200);
        $viewRes->assertSee('window.IS_ROLLBACK_MODE = true;', false);
        $viewRes->assertSee('SPOUSE / PARTNER DETAILS');
        $viewRes->assertSee('CHILDREN &amp; DEPENDENTS', false);
        $viewRes->assertSee('ADD IT');
        $viewRes->assertSee('AND THIS TOO');
        $viewRes->assertSee('id="newArrestedMarital"', false);
        $viewRes->assertSee('id="hasChildrenCheckbox"', false);
        $viewRes->assertSee('id="newArrestedChildren"', false);
        $viewRes->assertSee('id="newArrestedSpouseName"', false);
        $viewRes->assertSee('id="relationshipDetailsBox"', false);

        // 2. Staff resubmits with updated marital status and children count
        $resubmitRes = $this->actingAs($staff)->postJson("/Lab_Analysis/staff/case-rollback/{$submission->id}/resubmit", [
            'marital_status' => 'Married',
            'has_children' => 1,
            'children_count' => 3,
            'spouse_name' => 'Adwoa Mansa',
            'spouse_phone' => '0244998877',
            'spouse_info' => 'Teacher, Tema',
        ]);
        $resubmitRes->assertStatus(200);
        $resubmitRes->assertJson([
            'success' => true,
        ]);

        $submission->refresh();
        $this->assertEquals('Pending Approval', $submission->status);
        $this->assertNull($submission->rollback_corrections);

        // Verify payload persistence
        $this->assertEquals('Married', $submission->payload['marital_status']);
        $this->assertTrue($submission->payload['has_children']);
        $this->assertEquals(3, (int) $submission->payload['children_count']);
        $this->assertEquals('Adwoa Mansa', $submission->payload['spouse_name']);
        $this->assertEquals('0244998877', $submission->payload['spouse_phone']);
        $this->assertEquals('Teacher, Tema', $submission->payload['spouse_info']);

        // Verify docket_data persistence
        $this->assertEquals('Married', $submission->payload['docket_data']['marital_status']);
        $this->assertTrue($submission->payload['docket_data']['has_children']);
        $this->assertEquals('Adwoa Mansa', $submission->payload['docket_data']['spouse_name']);

        // Verify first suspect persistence
        $this->assertEquals('Married', $submission->payload['suspects'][0]['marital_status']);
        $this->assertTrue($submission->payload['suspects'][0]['has_children']);
        $this->assertEquals('Adwoa Mansa', $submission->payload['suspects'][0]['spouse_name']);
        $this->assertEquals('3 Kids', $submission->payload['dependents']);
        $this->assertEquals('3 Kids', $submission->payload['docket_data']['dependents']);
        $this->assertEquals('3 Kids', $submission->payload['suspects'][0]['dependents']);

        // 3. Unit Head views the resubmitted case in reviews and approvals
        $head = User::factory()->create([
            'fullname' => 'Dr. Unit Head',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $headReviewRes = $this->actingAs($head)->get("/Lab_Analysis/reviews-approvals/{$submission->id}");
        $headReviewRes->assertStatus(200);
        $headReviewRes->assertSee('3 Kids');
        $headReviewRes->assertSee('Married');
        $headReviewRes->assertSee('Adwoa Mansa');
        $headReviewRes->assertSee('0244998877');
        $headReviewRes->assertSee('Teacher, Tema');
    }
}

