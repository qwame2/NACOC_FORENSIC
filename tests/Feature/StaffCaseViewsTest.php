<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
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

        // 3. Head views Reviews & Approvals page and approves -> saves to database
        $reviewPage = $this->actingAs($headUser)->get('/Crime_Scene_Support/reviews-approvals');
        $reviewPage->assertStatus(200);
        $reviewPage->assertSee('Kofi Mensah');
        $reviewPage->assertSee('Approve &amp; Save', false);

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
}

