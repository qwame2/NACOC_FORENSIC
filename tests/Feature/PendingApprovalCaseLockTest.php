<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\SingleSuspectCase;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingApprovalCaseLockTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $headUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headUser = User::factory()->create([
            'fullname' => 'Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $this->staffUser = User::factory()->create([
            'fullname' => 'Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => ['edit_cases' => true, 'create_cases' => true],
        ]);
    }

    #[Test]
    public function pending_approval_case_disables_edit_case_and_print_case_pdf_buttons(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-PENDING-' . uniqid(),
            'title' => 'Pending Approval Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Pending Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'suspect_name' => 'Pending Suspect',
            'role' => 'Primary Suspect',
        ]);

        $reviewUrls = [
            "/Crime_Scene_Support/staff/cases/{$case->case_number}/review",
            "/Crime_Scene_Support/cases/{$case->case_number}/review",
            "/Lab_Analysis/cases/{$case->case_number}/review",
            "/Digital_Forensics/cases/{$case->case_number}/review",
            "/Evidence_Management/cases/{$case->case_number}/review",
            "/Forensic_Head/cases/{$case->case_number}/review",
            "/DG/cases/{$case->case_number}/review",
        ];

        foreach ($reviewUrls as $url) {
            $response = $this->actingAs($this->headUser)->get($url);
            $response->assertOk();

            $html = $response->getContent();

            // Units with edit_case_button: Edit Case/Update Case Records button must be disabled
            if (str_contains($html, 'Edit Case/Update Case Records')) {
                $this->assertStringContainsString('data-staff-edit-btn="review"', $html);
                $this->assertStringContainsString('data-case-pending="true"', $html);
                $this->assertStringContainsString('Locked: This case is currently awaiting review and approval from the Head of Unit', $html);
                $this->assertStringNotContainsString("href=\"/cases/{$case->case_number}/edit", $html);
            }

            // Print Case PDF button must be disabled across all units
            $this->assertStringContainsString('Print Case PDF', $html);
            $this->assertStringContainsString('Locked: This case is currently awaiting review and approval from the Head of Unit. Printing Case PDF is disabled until approved.', $html);
            $this->assertStringNotContainsString("href=\"/cases/{$case->case_number}/pdf\"", $html);
            $this->assertStringNotContainsString("href=\"/Forensic_Head/cases/{$case->case_number}/pdf\"", $html);
            $this->assertStringNotContainsString("href=\"/DG/cases/{$case->case_number}/pdf\"", $html);
        }
    }

    #[Test]
    public function case_with_active_staff_submission_pending_approval_disables_buttons(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-ACTIVE-SUB-' . uniqid(),
            'title' => 'Active Staff Submission Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Active Sub Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'suspect_name' => 'Active Sub Suspect',
            'role' => 'Primary Suspect',
        ]);

        // Attach an active pending staff submission
        StaffCaseSubmission::create([
            'submitted_by' => $this->staffUser->id,
            'submitted_by_name' => $this->staffUser->fullname,
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => $case->title,
            'suspect_name' => 'Active Sub Suspect',
            'priority' => 'MEDIUM',
            'status' => 'Pending Approval',
            'payload' => ['case_number' => $case->case_number],
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get("/Crime_Scene_Support/staff/cases/{$case->case_number}/review");
        $response->assertOk();

        $html = $response->getContent();

        // Must be locked due to pending submission
        $this->assertStringContainsString('data-case-pending="true"', $html);
        $this->assertStringNotContainsString("href=\"/cases/{$case->case_number}/edit", $html);
        $this->assertStringNotContainsString("href=\"/cases/{$case->case_number}/pdf\"", $html);
    }

    #[Test]
    public function visiting_edit_route_for_pending_case_redirects_with_error(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-EDIT-LOCK-' . uniqid(),
            'title' => 'Edit Lock Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Locked Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get("/cases/{$case->case_number}/edit?from=Crime_Scene_Support");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This case is currently awaiting review and approval from the Head of Unit. Case editing is locked.');
    }

    #[Test]
    public function updating_pending_case_is_blocked(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-UPDATE-LOCK-' . uniqid(),
            'title' => 'Update Lock Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Locked Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->post("/cases/{$case->case_number}/update", [
                'title' => 'Attempted Edit',
            ]);

        $response->assertSessionHas('error', 'This case is currently awaiting review and approval from the Head of Unit. Case editing is locked.');
    }

    #[Test]
    public function visiting_pdf_route_for_pending_case_redirects_with_error(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-PDF-LOCK-' . uniqid(),
            'title' => 'PDF Lock Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Locked Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->from("/Crime_Scene_Support/cases/{$case->case_number}/review")
            ->get("/cases/{$case->case_number}/pdf");

        $response->assertRedirect("/Crime_Scene_Support/cases/{$case->case_number}/review");
        $response->assertSessionHas('error', 'This case is currently awaiting review and approval from the Head of Unit. Printing Case PDF is disabled until approved.');
    }

    #[Test]
    public function approved_case_keeps_edit_and_print_pdf_buttons_enabled(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-APPROVED-' . uniqid(),
            'title' => 'Approved Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Approved Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'suspect_name' => 'Approved Suspect',
            'role' => 'Primary Suspect',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get("/Crime_Scene_Support/staff/cases/{$case->case_number}/review");
        $response->assertOk();

        $html = $response->getContent();

        // 1. Edit Case button is enabled
        $this->assertStringContainsString('Edit Case/Update Case Records', $html);
        $this->assertStringContainsString('aria-disabled="false"', $html);
        $this->assertStringContainsString("href=\"/cases/{$case->case_number}/edit?from=Crime_Scene_Support\"", $html);

        // 2. Print Case PDF button is enabled
        $this->assertStringContainsString('Print Case PDF', $html);
        $this->assertStringContainsString("href=\"/cases/{$case->case_number}/pdf\"", $html);

        // 3. Direct route access works
        $pdfResponse = $this->actingAs($this->staffUser)->get("/cases/{$case->case_number}/pdf");
        $pdfResponse->assertOk();

        $editResponse = $this->actingAs($this->staffUser)->get("/cases/{$case->case_number}/edit?from=Crime_Scene_Support");
        $editResponse->assertOk();
    }

    #[Test]
    public function case_list_table_action_menu_locks_edit_case_for_pending_approval_case(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-MENU-LOCK-' . uniqid(),
            'title' => 'Menu Lock Case Test',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'case_status' => 'Under Investigation',
            'suspect_name' => 'Menu Suspect',
            'user_id' => $this->staffUser->id,
        ]);

        CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'suspect_name' => 'Menu Suspect',
            'role' => 'Primary Suspect',
        ]);

        $response = $this->actingAs($this->staffUser)->get('/cases?unit=Crime_Scene_Support');
        $response->assertOk();

        $html = $response->getContent();

        // Must display Locked Edit Case in table
        $this->assertStringContainsString('Awaiting Head approval', $html);
        $this->assertStringContainsString('LOCKED', $html);
        $this->assertStringNotContainsString("href=\"/cases/{$case->case_number}/review?edit=true", $html);
    }
}
