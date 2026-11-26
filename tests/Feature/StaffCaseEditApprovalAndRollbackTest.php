<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffCaseEditApprovalAndRollbackTest extends TestCase
{
    use RefreshDatabase;

    private function createApprovedCase(): SingleSuspectCase
    {
        return SingleSuspectCase::create([
            'case_number' => 'CAS-EDIT-TEST-001',
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => 'Insp. Darko',
            'marital_status' => 'Single',
            'children_count' => 0,
            'spouse_name' => null,
            'docket_data' => [
                'marital_status' => 'Single',
                'children_count' => 0,
                'spouse_name' => null,
                'suspects' => [
                    [
                        'suspect_name' => 'Kwaku Manu',
                        'marital_status' => 'Single',
                        'children_count' => 0,
                        'spouse_name' => null,
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function staff_with_edit_cases_permission_submitting_edit_creates_pending_approval_submission_and_detects_edited_fields(): void
    {
        $case = $this->createApprovedCase();

        $staff = User::factory()->create([
            'fullname' => 'Kojo Staff Editor',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => ['edit_cases' => true],
        ]);

        $res = $this->actingAs($staff)->post('/cases/' . $case->case_number . '/update', [
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'marital_status' => 'Married',
            'spouse_name' => 'Ama Serwaa',
            'spouse_phone' => '0244112233',
            'children_count' => 2,
            'has_children' => 1,
        ]);

        $res->assertRedirect('/cases/' . $case->case_number . '/review');
        $res->assertSessionHas('status');

        $this->assertDatabaseHas('staff_case_submissions', [
            'case_number_preview' => $case->case_number,
            'submitted_by' => $staff->id,
            'status' => 'Pending Approval',
        ]);

        $submission = StaffCaseSubmission::where('case_number_preview', $case->case_number)->first();
        $this->assertNotNull($submission);
        $this->assertTrue($submission->payload['is_edit_submission']);
        $this->assertEquals('edit', $submission->payload['submission_type']);
        $this->assertContains('marital_status', $submission->payload['edited_fields']);
        $this->assertContains('spouse_name', $submission->payload['edited_fields']);

        // Case should now be considered pending approval and locked
        $refreshedCase = ForensicCase::findCase($case->case_number);
        $this->assertTrue($refreshedCase->isPendingApproval());

        // Visiting review view displays locked Edit Case button
        $reviewRes = $this->actingAs($staff)->get('/cases/' . $case->case_number . '/review');
        $reviewRes->assertStatus(200);
        $reviewRes->assertSee('data-case-pending="true"', false);
    }

    #[Test]
    public function head_reviews_edited_case_and_system_displays_banner_and_scroll_to_edited_script(): void
    {
        $case = $this->createApprovedCase();

        $head = User::factory()->create([
            'fullname' => 'Dr. CSS Head',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => 999,
            'submitted_by_name' => 'Officer Mensah',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
                'case_number' => $case->case_number,
                'title' => 'Republic v Kwaku Manu',
                'suspect_name' => 'Kwaku Manu',
                'marital_status' => 'Married',
                'spouse_name' => 'Ama Serwaa',
                'edited_fields' => ['marital_status', 'spouse_name'],
                'edited_field_labels' => ['Marital Status', 'Spouse / Partner Name'],
                'first_edited_field' => 'marital_status',
                'first_edited_selector' => '[data-field-group="marital_status"]',
            ],
        ]);

        $res = $this->actingAs($head)->get('/Crime_Scene_Support/reviews-approvals/' . $submission->id);
        $res->assertStatus(200);

        // Verify Staff Case Edit Submission Banner
        $res->assertSee('Staff Case Edit Submission &mdash; Review &amp; Approval Required', false);
        $res->assertSee('Officer Mensah');
        $res->assertSee('2 Fields Edited');
        $res->assertSee('Marital Status');
        $res->assertSee('Spouse / Partner Name');

        // Verify scroll to edited element functionality and attributes
        $res->assertSee('data-field-group="marital_status"', false);
        $res->assertSee('scrollToEditedSection', false);
        $res->assertSee('Edited by Staff');
    }

    #[Test]
    public function head_can_rollback_edited_case_and_submission_becomes_rolled_back(): void
    {
        $case = $this->createApprovedCase();

        $head = User::factory()->create([
            'fullname' => 'Dr. CSS Head',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => 999,
            'submitted_by_name' => 'Officer Mensah',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
                'marital_status' => 'Married',
            ],
        ]);

        $rollbackRes = $this->actingAs($head)->post('/reviews-approvals/' . $submission->id . '/rollback', [
            'corrections' => [
                ['field' => 'Marital Status', 'note' => 'Please provide marriage certificate or official details.']
            ],
            'review_notes' => 'Please verify spouse details.',
        ]);

        $rollbackRes->assertRedirect();

        $submission->refresh();
        $this->assertEquals('Rolled Back', $submission->status);
        $this->assertNotNull($submission->rollback_corrections);
        $this->assertEquals('Marital Status', $submission->rollback_corrections[0]['field']);
    }

    #[Test]
    public function staff_opens_rolled_back_edited_case_and_is_redirected_to_edit_view_with_rollback_banner(): void
    {
        $case = $this->createApprovedCase();

        $staff = User::factory()->create([
            'fullname' => 'Kojo Staff Editor',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => ['edit_cases' => true],
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staff->id,
            'submitted_by_name' => $staff->fullname,
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'review_notes' => 'Spouse phone number must be verified.',
            'rollback_corrections' => [
                ['field' => 'Spouse Details', 'note' => 'Fix phone number format']
            ],
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
                'marital_status' => 'Married',
            ],
        ]);

        // 1. Staff clicks rollback item from staff rollback index -> redirects to edit route
        $routeRes = $this->actingAs($staff)->get('/Crime_Scene_Support/staff/case-rollback/' . $submission->id);
        $routeRes->assertRedirect('/cases/' . $case->case_number . '/edit?rollback_id=' . $submission->id);

        // 2. Staff views edit route with rollback_id -> renders active rollback banner and directives
        $editRes = $this->actingAs($staff)->get('/cases/' . $case->case_number . '/edit?rollback_id=' . $submission->id);
        $editRes->assertStatus(200);
        $editRes->assertSee('Active Case Rollback &mdash; Fix Requested Corrections', false);
        $editRes->assertSee('Spouse phone number must be verified.');
        $editRes->assertSee('Fix phone number format');
        $editRes->assertSee('Resubmit Corrections to Head');
        $editRes->assertSee('name="rollback_id"', false);
    }

    #[Test]
    public function staff_resubmits_corrections_and_submission_returns_to_pending_approval(): void
    {
        $case = $this->createApprovedCase();

        $staff = User::factory()->create([
            'fullname' => 'Kojo Staff Editor',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => ['edit_cases' => true],
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => $staff->id,
            'submitted_by_name' => $staff->fullname,
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Rolled Back',
            'rollback_corrections' => [
                ['field' => 'Spouse Details', 'note' => 'Fix phone number']
            ],
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
            ],
        ]);

        $res = $this->actingAs($staff)->post('/cases/' . $case->case_number . '/update', [
            'rollback_id' => $submission->id,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'marital_status' => 'Married',
            'spouse_name' => 'Ama Serwaa',
            'spouse_phone' => '0244998877',
        ]);

        $res->assertRedirect('/cases/' . $case->case_number . '/review');

        $submission->refresh();
        $this->assertEquals('Pending Approval', $submission->status);
        $this->assertNull($submission->rollback_corrections);
        $this->assertNull($submission->rolled_back_at);
        $this->assertNull($submission->review_notes);
        $this->assertEquals('0244998877', $submission->payload['spouse_phone']);
    }

    #[Test]
    public function head_approves_edit_submission_and_case_record_is_updated_in_database(): void
    {
        $case = $this->createApprovedCase();

        $head = User::factory()->create([
            'fullname' => 'Dr. CSS Head',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'submitted_by' => 888,
            'submitted_by_name' => 'Insp. Boateng',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
                'case_number' => $case->case_number,
                'title' => 'Republic v Kwaku Manu Updated',
                'suspect_name' => 'Kwaku Manu Updated',
                'marital_status' => 'Married',
                'spouse_name' => 'Akosua Mansa',
                'has_children' => 1,
                'children_count' => 3,
            ],
        ]);

        $approveRes = $this->actingAs($head)->post('/reviews-approvals/' . $submission->id . '/approve', [
            'review_notes' => 'All edits verified and sanctioned.',
        ]);

        $approveRes->assertRedirect();

        $submission->refresh();
        $this->assertEquals('Approved', $submission->status);
        $this->assertEquals($head->id, $submission->reviewed_by);

        // Verify the database record has been updated with the approved edits
        $case->refresh();
        $this->assertEquals('Married', $case->marital_status);
        $this->assertEquals('Akosua Mansa', $case->spouse_name);
        $this->assertEquals(3, $case->children_count);

        // Case should now be unlocked
        $this->assertFalse($case->isPendingApproval());
    }

    #[Test]
    public function head_editing_case_directly_saves_to_database_without_pending_submission(): void
    {
        $case = $this->createApprovedCase();

        $head = User::factory()->create([
            'fullname' => 'Dr. CSS Head',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $res = $this->actingAs($head)->post('/cases/' . $case->case_number . '/update', [
            'title' => 'Direct Head Edit Case',
            'suspect_name' => 'Kwaku Manu',
            'marital_status' => 'Divorced',
        ]);

        $res->assertRedirect('/cases/' . $case->case_number . '/review');

        // No pending staff submission should be created
        $this->assertDatabaseMissing('staff_case_submissions', [
            'case_number_preview' => $case->case_number,
        ]);

        $case->refresh();
        $this->assertEquals('Divorced', $case->marital_status);
    }

    #[Test]
    public function reviews_and_approvals_page_displays_edited_case_label_for_pending_and_approved_submissions(): void
    {
        $case = $this->createApprovedCase();

        $head = User::factory()->create([
            'fullname' => 'Dr. CSS Head Reviewer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $pendingSubmission = StaffCaseSubmission::create([
            'submitted_by' => 888,
            'submitted_by_name' => 'Insp. Boateng',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => $case->case_number,
            'title' => 'Republic v Kwaku Manu',
            'suspect_name' => 'Kwaku Manu',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => $case->case_number,
                'case_number' => $case->case_number,
                'title' => 'Republic v Kwaku Manu Pending Edit',
                'suspect_name' => 'Kwaku Manu',
                'marital_status' => 'Married',
            ],
        ]);

        $approvedSubmission = StaffCaseSubmission::create([
            'submitted_by' => 888,
            'submitted_by_name' => 'Insp. Boateng',
            'unit_name' => 'Crime Scene Support',
            'case_number_preview' => 'CAS-HIST-999',
            'approved_case_number' => 'CAS-HIST-999',
            'title' => 'Republic v Historical Suspect',
            'suspect_name' => 'Historical Suspect',
            'priority' => 'STANDARD',
            'status' => 'Approved',
            'reviewed_by' => $head->id,
            'reviewed_by_name' => $head->fullname,
            'reviewed_at' => now(),
            'payload' => [
                'submission_type' => 'edit',
                'is_edit_submission' => true,
                'original_case_number' => 'CAS-HIST-999',
                'case_number' => 'CAS-HIST-999',
                'title' => 'Republic v Historical Suspect',
                'suspect_name' => 'Historical Suspect',
            ],
        ]);

        $res = $this->actingAs($head)->get('/Crime_Scene_Support/reviews-approvals');
        $res->assertStatus(200);

        // Verify "Edited Case" label appears in both pending queue and decision history
        $res->assertSee('Edited Case', false);
        $res->assertSee('data-search="', false);

        // Verify the pending queue article has data-search containing 'edited case'
        $content = $res->getContent();
        $this->assertStringContainsString('SUB-' . str_pad((string) $pendingSubmission->id, 4, '0', STR_PAD_LEFT), $content);
        $this->assertStringContainsString('edited case', strtolower($content));
    }
}

