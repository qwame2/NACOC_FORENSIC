<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuspectDispositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function default_suspect_disposition_is_active_under_investigation()
    {
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DISP1',
            'title' => 'Republic vs Suspect A and Suspect B',
            'unit_name' => 'Crime Scene Support',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect B',
            'role' => 'Co-Suspect',
        ]);

        $this->assertStringContainsString('Active', $suspect->suspect_disposition);
        $this->assertStringContainsString('Under Investigation', $suspect->suspect_disposition);
    }

    #[Test]
    public function authorized_controller_can_update_suspect_disposition_with_reason()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Unit Head Alpha',
            'unit' => 'Crime Scene Support',
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DISP2',
            'title' => 'Republic vs Primary Suspect & Associate',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
            'case_status' => 'Under Investigation',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Associate Suspect',
            'role' => 'Associate Suspect',
            'suspect_disposition' => 'Active — Under Investigation',
        ]);

        $response = $this->actingAs($controllerUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Associate Suspect',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => 'Alibi verified through CCTV footage and witness testimony.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $freshSuspect = $suspect->fresh();
        $this->assertEquals('Cleared / No Further Action', $freshSuspect->suspect_disposition);
        $this->assertEquals('Alibi verified through CCTV footage and witness testimony.', $freshSuspect->disposition_reason);
        $this->assertEquals($controllerUser->fullname, $freshSuspect->disposition_updated_by);
        $this->assertNotNull($freshSuspect->disposition_updated_at);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'case_number' => 'CSS-2026-DISP2',
            'action' => 'SUSPECT_DISPOSITION_CHANGED',
            'user_id' => $controllerUser->id,
        ]);
    }

    #[Test]
    public function update_disposition_to_non_active_without_reason_fails_validation()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Unit Head Alpha',
            'unit' => 'Crime Scene Support',
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DISP3',
            'title' => 'Republic vs Kwame & Kofi',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Kofi',
            'role' => 'Co-Suspect',
        ]);

        $response = $this->actingAs($controllerUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Kofi',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => '',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function unauthorized_user_cannot_update_suspect_disposition()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Controller Officer',
            'unit' => 'Crime Scene Support',
        ]);

        $unauthorizedUser = User::factory()->create([
            'fullname' => 'Regular Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DISP4',
            'title' => 'Republic vs Yaw & Ama',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Ama',
            'role' => 'Co-Suspect',
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Ama',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => 'No evidence found.',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function clearing_suspect_preserves_historical_records_biodata_and_exhibits()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Unit Head Alpha',
            'unit' => 'Crime Scene Support',
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DISP5',
            'title' => 'Republic vs Suspect X & Cleared Suspect Y',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $controllerUser->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Cleared Suspect Y',
            'role' => 'Co-Suspect',
            'ghana_card' => 'GHA-123456789-0',
            'phone' => '+233200000000',
        ]);

        $exhibit = Exhibit::create([
            'case_number' => 'CSS-2026-DISP5',
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_number' => 'EXH-001',
            'description' => 'Mobile Phone found near Suspect Y',
            'type' => 'Digital Device',
            'suspect_id' => $suspect->id,
        ]);

        // Clear the suspect
        $this->actingAs($controllerUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Cleared Suspect Y',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => 'Fingerprint match cleared suspect.',
            ]);

        // Assert suspect still exists in database with all details intact
        $this->assertDatabaseHas('case_suspects', [
            'id' => $suspect->id,
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Cleared Suspect Y',
            'ghana_card' => 'GHA-123456789-0',
            'suspect_disposition' => 'Cleared / No Further Action',
        ]);

        // Assert exhibit remains linked and intact
        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_number' => 'EXH-001',
            'suspect_id' => $suspect->id,
        ]);
    }

    #[Test]
    public function updating_co_suspect_disposition_does_not_affect_primary_suspect()
    {
        $controllerUser = User::factory()->head()->create([
            'fullname' => 'Unit Head Beta',
            'unit' => 'Crime Scene Support',
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-MULTI1',
            'title' => 'Republic vs Primary Suspect A and Co-Suspect B',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Primary Suspect A',
            'suspect_disposition' => 'Active — Under Investigation',
            'controller_user_id' => $controllerUser->id,
        ]);

        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Primary Suspect A',
            'role' => 'Primary Suspect',
            'suspect_disposition' => 'Active — Under Investigation',
        ]);

        $coSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Co-Suspect B',
            'role' => 'Co-Suspect',
            'suspect_disposition' => 'Active — Under Investigation',
        ]);

        // Update ONLY Co-Suspect B to Cleared
        $response = $this->actingAs($controllerUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Co-Suspect B',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => 'No DNA match on crime scene evidence.',
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Assert Co-Suspect B is cleared
        $this->assertEquals('Cleared / No Further Action', $coSuspect->fresh()->suspect_disposition);

        // Assert Primary Suspect A is STILL Active
        $this->assertEquals('Active — Under Investigation', $primarySuspect->fresh()->suspect_disposition);

        // Assert Main Case table primary suspect disposition is STILL Active
        $this->assertEquals('Active — Under Investigation', $multiCase->fresh()->suspect_disposition);
    }

    #[Test]
    public function case_status_change_cascades_to_unreleased_suspect_dispositions_only()
    {
        $headUser = User::factory()->head()->create([
            'fullname' => 'Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-CASCADE',
            'title' => 'Republic vs Suspect A and Suspect B',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Suspect A',
            'case_status' => 'Under Investigation',
            'suspect_disposition' => 'Active — Under Investigation',
            'controller_user_id' => $headUser->id,
        ]);

        $suspectA = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect A',
            'role' => 'Primary Suspect',
            'suspect_disposition' => 'Active — Under Investigation',
        ]);

        $suspectB = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Suspect B',
            'role' => 'Co-Suspect',
            'suspect_disposition' => 'Active — Under Investigation',
        ]);

        // 1. Change Case Status to "In Court"
        $resp1 = $this->actingAs($headUser)
            ->postJson("/cases/{$multiCase->case_number}/case-status", [
                'case_status' => 'In Court',
            ]);
        $resp1->assertStatus(200)->assertJson(['success' => true]);

        // Both Suspect A and Suspect B disposition should be updated to "In Court"
        $this->assertEquals('In Court', $suspectA->fresh()->suspect_disposition);
        $this->assertEquals('In Court', $suspectB->fresh()->suspect_disposition);

        // 2. Manually change Suspect B status to "Released from Case"
        $resp2 = $this->actingAs($headUser)
            ->postJson("/cases/{$multiCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Suspect B',
                'suspect_disposition' => 'Released from Case',
                'disposition_reason' => 'Insufficient evidence to hold suspect B.',
            ]);
        $resp2->assertStatus(200)->assertJson(['success' => true]);

        // Suspect B disposition is now Released, while Suspect A remains "In Court"
        $this->assertEquals('Released from Case', $suspectB->fresh()->suspect_disposition);
        $this->assertEquals('In Court', $suspectA->fresh()->suspect_disposition);
        $this->assertEquals('In Court', $multiCase->fresh()->case_status);

        // 3. Update Case Status to "Case Closed"
        $resp3 = $this->actingAs($headUser)
            ->postJson("/cases/{$multiCase->case_number}/case-status", [
                'case_status' => 'Case Closed',
            ]);
        $resp3->assertStatus(200)->assertJson(['success' => true]);

        // Suspect A's disposition follows case status to "Case Closed", while Suspect B remains "Released from Case"
        $this->assertEquals('Case Closed', $suspectA->fresh()->suspect_disposition);
        $this->assertEquals('Released from Case', $suspectB->fresh()->suspect_disposition);
    }

    #[Test]
    public function update_disposition_is_forbidden_for_single_suspect_cases()
    {
        $headUser = User::factory()->head()->create([
            'fullname' => 'Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'CSS-2026-SINGLE1',
            'title' => 'Republic vs Single Suspect',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Single Suspect X',
            'case_status' => 'Under Investigation',
            'controller_user_id' => $headUser->id,
        ]);

        $response = $this->actingAs($headUser)
            ->postJson("/cases/{$singleCase->case_number}/suspect-disposition", [
                'suspect_name' => 'Single Suspect X',
                'suspect_disposition' => 'Cleared / No Further Action',
                'disposition_reason' => 'Should fail for single suspect case.',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Suspect disposition updates are strictly available for multiple suspect cases only.',
            ]);
    }

    #[Test]
    public function single_suspect_case_status_change_unconditionally_controls_disposition()
    {
        $headUser = User::factory()->head()->create([
            'fullname' => 'Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'CSS-2026-SINGLE2',
            'title' => 'Republic vs Single Suspect Y',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Single Suspect Y',
            'case_status' => 'Under Investigation',
            'suspect_disposition' => 'Active — Under Investigation',
            'controller_user_id' => $headUser->id,
        ]);

        // Change Case Status to "On Bail"
        $response = $this->actingAs($headUser)
            ->postJson("/cases/{$singleCase->case_number}/case-status", [
                'case_status' => 'On Bail',
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $fresh = $singleCase->fresh();
        $this->assertEquals('On Bail', $fresh->case_status);
        $this->assertEquals('On Bail', $fresh->suspect_disposition);

        // Change Case Status to "In Court"
        $response2 = $this->actingAs($headUser)
            ->postJson("/cases/{$singleCase->case_number}/case-status", [
                'case_status' => 'In Court',
            ]);

        $response2->assertStatus(200)->assertJson(['success' => true]);

        $fresh2 = $singleCase->fresh();
        $this->assertEquals('In Court', $fresh2->case_status);
        $this->assertEquals('In Court', $fresh2->suspect_disposition);
    }
}



