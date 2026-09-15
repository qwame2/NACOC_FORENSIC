<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseValidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case_creation_fails_without_suspect_name()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $invalidCaseData = [
            'case_number' => 'CSS-2026-9999',
            'title' => 'Test Case Without Suspect',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => '', // Empty suspect name
        ];

        $response = $this->actingAs($user)
            ->postJson('/cases', $invalidCaseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['suspect_name']);
    }

    #[Test]
    public function case_creation_fails_with_dummy_unnamed_suspect_placeholder()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $invalidCaseData = [
            'case_number' => 'CSS-2026-9998',
            'title' => 'Test Case With Dummy Suspect',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Unnamed Suspect',
            'docket_data' => json_encode(['suspects' => []])
        ];

        $response = $this->actingAs($user)
            ->postJson('/cases', $invalidCaseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['suspect_name']);
    }

    #[Test]
    public function case_creation_succeeds_with_valid_suspect_details()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $validCaseData = [
            'case_number' => 'CSS-2026-8888',
            'title' => 'Valid Case With Suspect Details',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Kwame Mensah',
            'ghana_card' => 'GHA-8840291-3',
            'gender' => 'Male',
            'dob' => '1990-05-12',
            'nationality' => 'Ghanaian',
            'pob' => 'Kumasi',
            'phone' => '+233 24 555 0192',
            'address' => 'East Legon, Accra',
        ];

        $response = $this->actingAs($user)
            ->post('/cases', $validCaseData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cases', [
            'case_number' => 'CSS-2026-8888',
            'suspect_name' => 'Kwame Mensah',
        ]);
    }
}
