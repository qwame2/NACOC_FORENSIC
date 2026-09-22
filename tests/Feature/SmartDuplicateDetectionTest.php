<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ForensicCase;

class SmartDuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'is_approved' => true,
            'is_head' => true,
        ]);

        // Create an initial existing case
        ForensicCase::create([
            'case_number'      => 'CSS-2026-1001',
            'title'            => 'High Priority Cocaine Seizure',
            'unit_name'        => 'Crime Scene Support',
            'commanding_officer'=> 'Insp. Ofori',
            'suspect_name'     => 'Eric Addo, Linda Atta',
            'ghana_card'       => 'GHA-99887766-1',
            'dob'              => '1990-05-12',
            'gender'           => 'Male',
            'nationality'      => 'Ghanaian',
            'phone'            => '0244123456',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug'=> 'Cocaine Powder',
            'location'         => 'Accra Central',
            'raid_location'    => 'Accra Central',
            'incident_date'    => '2026-09-18',
            'status'           => 'Approved',
            'docket_data'      => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name'   => 'Eric Addo',
                        'ghana_card'     => 'GHA-99887766-1',
                        'dob'            => '1990-05-12',
                        'gender'         => 'Male',
                        'nationality'    => 'Ghanaian',
                        'phone'          => '0244123456',
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name'   => 'Linda Atta',
                        'ghana_card'     => 'GHA-11223344-5',
                        'dob'            => '1992-08-20',
                        'gender'         => 'Female',
                        'nationality'    => 'Ghanaian',
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function exact_ghana_card_match_triggers_suspect_biodata_warning()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/cases/check-duplicate-suspect', [
                'suspect_name' => 'Eric Addo',
                'ghana_card'   => 'GHA998877661',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', true)
            ->assertJsonPath('matching_suspect.case_number', 'CSS-2026-1001');
    }

    /** @test */
    public function same_name_alone_does_not_trigger_suspect_duplicate_warning()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/cases/check-duplicate-suspect', [
                'suspect_name' => 'Eric Addo',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', false);
    }

    /** @test */
    public function same_suspect_in_different_case_context_does_not_block_with_high_confidence()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/cases/check-duplicate-case', [
                'title'            => 'New Unrelated Investigation',
                'suspect_name'     => 'Eric Addo',
                'dob'              => '1990-05-12',
                'incident_date'    => '2026-12-01', // Different date
                'raid_location'    => 'Kumasi Vault', // Different location
                'exhibit_category' => 'Weapons',
                'seizure_name'     => 'Shotgun',
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        // Should not be HIGH_CONFIDENCE because case details differ significantly
        $this->assertNotEquals('HIGH_CONFIDENCE', $data['level'] ?? '');
    }

    /** @test */
    public function matching_suspects_and_matching_case_details_returns_409_duplicate()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/cases', [
                'title'            => 'Seizure of Suspected Narcotics',
                'suspect_name'     => 'Eric Addo',
                'ghana_card'       => 'GHA-99887766-1',
                'dob'              => '1990-05-12',
                'incident_date'    => '2026-09-18',
                'raid_location'    => 'Accra Central',
                'exhibit_category' => 'Drugs',
                'seizure_name_drug'=> 'Cocaine Powder',
                'docket_data'      => json_encode([
                    'suspects' => [
                        [
                            'suspect_name' => 'Eric Addo',
                            'ghana_card'   => 'GHA-99887766-1',
                            'dob'          => '1990-05-12',
                        ],
                        [
                            'suspect_name' => 'Linda Atta',
                        ]
                    ]
                ])
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('duplicate_detected', true)
            ->assertJsonPath('existing_case.case_number', 'CSS-2026-1001');
    }

    /** @test */
    public function confirming_duplicate_bypasses_duplicate_warning()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/cases', [
                'title'            => 'Seizure of Suspected Narcotics',
                'suspect_name'     => 'Eric Addo',
                'ghana_card'       => 'GHA-99887766-1',
                'dob'              => '1990-05-12',
                'incident_date'    => '2026-09-18',
                'raid_location'    => 'Accra Central',
                'exhibit_category' => 'Drugs',
                'seizure_name_drug'=> 'Cocaine Powder',
                'confirm_duplicate'=> true, // Officer confirmed bypass
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
