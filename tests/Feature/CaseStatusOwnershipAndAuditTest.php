<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\CaseHandover;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseStatusOwnershipAndAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case_creation_sets_controller_user_id_and_creates_audit_log()
    {
        $headUser = User::factory()->head()->create([
            'fullname' => 'Commanding Officer A',
            'unit' => 'Crime Scene Support',
        ]);

        $caseData = [
            'case_number' => 'CSS-2026-STATUS1',
            'title' => 'Republic vs John Doe',
            'unit_name' => 'Crime Scene Support',
            'commanding_officer' => $headUser->fullname,
            'submitting_officer' => $headUser->fullname,
            'suspect_name' => 'John Doe',
            'priority' => 'HIGH',
            'status' => 'Under Investigation',
            'location' => 'Accra Port',
            'description' => 'Suspected drug smuggling case.',
        ];

        $response = $this->actingAs($headUser)
            ->post('/cases', $caseData);

        $response->assertStatus(302);

        $case = ForensicCase::where('case_number', 'CSS-2026-STATUS1')->first();
        $this->assertNotNull($case);
        $this->assertEquals($headUser->id, $case->controller_user_id);
        $this->assertEquals('Under Investigation', $case->case_status);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-STATUS1',
            'action' => 'CASE_CREATED',
            'user_id' => $headUser->id,
        ]);
    }

    #[Test]
    public function authorized_controller_can_update_case_status()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Head Officer Alpha',
            'unit' => 'Crime Scene Support',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-STATUS2',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
            'case_status' => 'Under Investigation',
        ]);

        $response = $this->actingAs($controllerUser)
            ->postJson("/cases/{$case->case_number}/case-status", [
                'case_status' => 'In Court',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'case_status' => 'In Court',
            ]);

        $this->assertEquals('In Court', $case->fresh()->case_status);

        // Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-STATUS2',
            'action' => 'CASE_STATUS_CHANGED',
            'user_id' => $controllerUser->id,
        ]);
    }

    #[Test]
    public function unauthorized_user_cannot_update_case_status()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Controller Officer',
            'unit' => 'Crime Scene Support',
        ]);

        $unauthorizedUser = User::factory()->head()->create([
            'fullname' => 'Other Unit Head',
            'unit' => 'Lab Analysis',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-STATUS3',
            'title' => 'Republic vs Akwasi Addo',
            'suspect_name' => 'Akwasi Addo',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
            'case_status' => 'Under Investigation',
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson("/cases/{$case->case_number}/case-status", [
                'case_status' => 'Case Dismissed',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized: You are not the current Case Status Controller for this case.',
            ]);

        $this->assertEquals('Under Investigation', $case->fresh()->case_status);
    }

    #[Test]
    public function handover_workflow_initiation_and_acceptance_transfers_control()
    {
        $initiator = User::factory()->head()->create([
            'fullname' => 'Head CSS',
            'unit' => 'Crime Scene Support',
        ]);

        $receiver = User::factory()->head()->create([
            'fullname' => 'Head Lab',
            'unit' => 'Lab Analysis',
        ]);

        $otherUser = User::factory()->head()->create([
            'fullname' => 'Head Evidence',
            'unit' => 'Evidence Management',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-HO1',
            'title' => 'Republic vs Kofi Annan',
            'suspect_name' => 'Kofi Annan',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $initiator->id,
            'case_status' => 'Under Investigation',
        ]);

        // Step 1: Initiate Handover
        $initiateRes = $this->actingAs($initiator)
            ->postJson("/cases/{$case->case_number}/handover/initiate", [
                'to_user_id' => $receiver->id,
                'request_notes' => 'Transferring case status control for lab analysis phase.',
            ]);

        $initiateRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('case_handovers', [
            'case_number' => 'CSS-2026-HO1',
            'from_user_id' => $initiator->id,
            'to_user_id' => $receiver->id,
            'status' => 'Pending Acknowledgement',
        ]);

        // Controller remains initiator while pending
        $this->assertEquals($initiator->id, $case->fresh()->controller_user_id);

        // Step 2: Other user (neither initiator nor receiver) cannot accept
        $unauthAccept = $this->actingAs($otherUser)
            ->postJson("/cases/{$case->case_number}/handover/respond", [
                'action' => 'accept',
            ]);
        $unauthAccept->assertStatus(403);

        // Step 3: Receiver accepts handover
        $acceptRes = $this->actingAs($receiver)
            ->postJson("/cases/{$case->case_number}/handover/respond", [
                'action' => 'accept',
                'response_notes' => 'Control accepted.',
            ]);

        $acceptRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert control transferred
        $this->assertEquals($receiver->id, $case->fresh()->controller_user_id);

        $this->assertDatabaseHas('case_handovers', [
            'case_number' => 'CSS-2026-HO1',
            'status' => 'Accepted',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-HO1',
            'action' => 'HANDOVER_ACCEPTED',
            'user_id' => $receiver->id,
        ]);

        // Receiver can now update status
        $statusRes = $this->actingAs($receiver)
            ->postJson("/cases/{$case->case_number}/case-status", [
                'case_status' => 'In Court',
            ]);
        $statusRes->assertStatus(200);
        $this->assertEquals('In Court', $case->fresh()->case_status);
    }

    #[Test]
    public function handover_workflow_decline_and_cancel()
    {
        $initiator = User::factory()->head()->create([
            'fullname' => 'Head CSS',
            'unit' => 'Crime Scene Support',
        ]);

        $receiver = User::factory()->head()->create([
            'fullname' => 'Head Lab',
            'unit' => 'Lab Analysis',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-HO2',
            'title' => 'Republic vs Yaw Mensah',
            'suspect_name' => 'Yaw Mensah',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $initiator->id,
            'case_status' => 'Under Investigation',
        ]);

        // 1. Test Decline
        $this->actingAs($initiator)
            ->postJson("/cases/{$case->case_number}/handover/initiate", [
                'to_user_id' => $receiver->id,
            ]);

        $declineRes = $this->actingAs($receiver)
            ->postJson("/cases/{$case->case_number}/handover/respond", [
                'action' => 'decline',
                'response_notes' => 'Case documentation incomplete.',
            ]);

        $declineRes->assertStatus(200);

        $this->assertDatabaseHas('case_handovers', [
            'case_number' => 'CSS-2026-HO2',
            'status' => 'Declined',
        ]);

        // Controller remains initiator
        $this->assertEquals($initiator->id, $case->fresh()->controller_user_id);

        // 2. Test Cancel by initiator
        $this->actingAs($initiator)
            ->postJson("/cases/{$case->case_number}/handover/initiate", [
                'to_user_id' => $receiver->id,
            ]);

        $cancelRes = $this->actingAs($initiator)
            ->postJson("/cases/{$case->case_number}/handover/respond", [
                'action' => 'cancel',
            ]);

        $cancelRes->assertStatus(200);

        $this->assertDatabaseHas('case_handovers', [
            'case_number' => 'CSS-2026-HO2',
            'status' => 'Cancelled',
        ]);
        $this->assertEquals($initiator->id, $case->fresh()->controller_user_id);
    }

    #[Test]
    public function unit_progression_does_not_transfer_case_status_ownership()
    {
        $cssHead = User::factory()->head()->create([
            'fullname' => 'CSS Officer',
            'unit' => 'Crime Scene Support',
        ]);

        $labOfficer = User::factory()->create([
            'fullname' => 'Lab Officer',
            'unit' => 'Lab Analysis',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-HO3',
            'title' => 'Republic vs Ama Boateng',
            'suspect_name' => 'Ama Boateng',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $cssHead->id,
            'case_status' => 'Under Investigation',
        ]);

        // Lab officer updates unit progress
        $updateResponse = $this->actingAs($labOfficer)
            ->postJson("/cases/{$case->case_number}/update-unit-progress", [
                'unit_name' => 'Lab Analysis',
                'status' => 'COMPLETED',
                'officer' => $labOfficer->fullname,
                'notes' => 'Examination completed.',
            ]);

        $updateResponse->assertStatus(200);

        // Verify controller_user_id has NOT changed
        $this->assertEquals($cssHead->id, $case->fresh()->controller_user_id);

        // Verify WORKFLOW_UNIT_COMPLETED audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-HO3',
            'action' => 'WORKFLOW_UNIT_COMPLETED',
            'user_id' => $labOfficer->id,
        ]);
    }

    #[Test]
    public function audit_trail_endpoint_returns_case_audit_logs()
    {
        $user = User::factory()->head()->create();

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-AUDIT1',
            'title' => 'Republic vs Test Suspect',
            'suspect_name' => 'Test Suspect',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        AuditLog::create([
            'case_number' => 'CSS-2026-AUDIT1',
            'auditable_type' => get_class($case->underlyingRecord ?? $case),
            'auditable_id' => $case->id,
            'action' => 'CASE_CREATED',
            'user_id' => $user->id,
            'user_name' => $user->fullname,
            'user_unit' => $user->unit,
            'description' => 'Test creation log',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/cases/{$case->case_number}/audit-trail");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['action' => 'CASE_CREATED', 'description' => 'Test creation log']);
    }
}
