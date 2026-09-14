<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function full_end_to_end_forensic_case_lifecycle()
    {
        // 1. Setup Head of Forensic and Field Officer
        $headUser = User::factory()->head()->create();
        $analystUser = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // 2. Step 1: Officer submits a new arrest docket
        $caseData = [
            'case_number' => 'CSS-2026-9901',
            'title' => 'Seizure of Suspected Narcotic Substance',
            'unit_name' => 'Crime Scene Support',
            'commanding_officer' => $headUser->fullname,
            'submitting_officer' => $analystUser->fullname,
            'suspect_name' => 'Kofi Mensah',
            'priority' => 'HIGH',
            'status' => 'Awaiting Sign-off',
            'location' => 'Accra Airport Cargo Center',
            'description' => 'Suspected cocaine bricks intercepted.',
            'exhibits_summary' => '25kg White Powder Bricks',
        ];

        $response = $this->actingAs($analystUser)
            ->post('/cases', $caseData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cases', [
            'case_number' => 'CSS-2026-9901',
            'title' => 'Seizure of Suspected Narcotic Substance'
        ]);

        // 3. Step 2: Analyst updates Laboratory Analysis examination progress
        $progressData = [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'officer' => $analystUser->fullname,
            'notes' => 'Presumptive colorimetric test positive for Cocaine HCl.',
            'lab_unit' => 'Chemistry & Narcotics Unit',
            'presumptive_result' => 'YES (Positive Colorimetric Reaction)',
            'lab_date_received' => date('Y-m-d'),
            'lab_date_started' => date('Y-m-d'),
            'lab_date_completed' => date('Y-m-d'),
        ];

        $updateResponse = $this->actingAs($analystUser)
            ->postJson('/cases/CSS-2026-9901/update-unit-progress', $progressData);

        $updateResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // 4. Step 3: Head of Forensic reviews and approves docket
        $approveResponse = $this->actingAs($headUser)
            ->post('/cases/CSS-2026-9901/approve');

        $approveResponse->assertStatus(302);
        $this->assertDatabaseHas('cases', [
            'case_number' => 'CSS-2026-9901',
            'status' => 'Approved',
        ]);
    }
}
