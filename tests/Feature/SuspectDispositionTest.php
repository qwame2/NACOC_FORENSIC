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

        $this->assertEquals('Active — Under Investigation', $suspect->suspect_disposition);
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
}
