<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseStatusOwnershipAndAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case_creation_sets_case_status_and_creates_audit_log()
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
        $this->assertEquals('Under Investigation', $case->case_status);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-STATUS1',
            'action' => 'CASE_CREATED',
            'user_id' => $headUser->id,
        ]);
    }

    #[Test]
    public function any_unit_head_can_update_case_status()
    {
        $cssHead = User::factory()->head()->create([
            'fullname' => 'Head CSS',
            'unit' => 'Crime Scene Support',
        ]);

        $labHead = User::factory()->head()->create([
            'fullname' => 'Head Lab',
            'unit' => 'Lab Analysis',
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-STATUS2',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'unit_name' => 'Crime Scene Support',
            'case_status' => 'Under Investigation',
        ]);

        // Lab Head (from another unit) updates status
        $response = $this->actingAs($labHead)
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
            'user_id' => $labHead->id,
        ]);
    }

    #[Test]
    public function regular_staff_cannot_update_case_status()
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Staff Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-STATUS3',
            'title' => 'Republic vs Akwasi Addo',
            'suspect_name' => 'Akwasi Addo',
            'unit_name' => 'Crime Scene Support',
            'case_status' => 'Under Investigation',
        ]);

        $response = $this->actingAs($staffUser)
            ->postJson("/cases/{$case->case_number}/case-status", [
                'case_status' => 'Case Dismissed',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized: Only a Head of Unit or Admin can change the case status.',
            ]);

        $this->assertEquals('Under Investigation', $case->fresh()->case_status);
    }

    #[Test]
    public function unit_progression_saves_audit_log()
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
