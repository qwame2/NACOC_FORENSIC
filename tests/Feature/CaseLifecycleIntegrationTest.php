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

    #[Test]
    public function associate_suspects_and_exhibits_are_correctly_linked_per_suspect()
    {
        $user = User::factory()->head()->create();
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-9902',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Draft',
            'docket_data' => json_encode([
                'suspects' => [
                    ['suspect_name' => 'Kwame Mensah', 'association_role' => 'Primary Suspect', 'is_associate' => false]
                ],
                'seizures' => [
                    ['category' => 'Drugs', 'seizure_name' => 'Cocaine Slabs', 'is_associate' => false]
                ]
            ])
        ]);

        $assocData = [
            'associated_persons' => [
                ['suspect_name' => 'Princilla Addo', 'ghana_card' => 'GHA-111111111-1', 'gender' => 'Female', 'dob' => '1992-05-10', 'place_of_birth' => 'Accra', 'phone_number' => '0240000001', 'raid_location' => 'Accra', 'date_of_arrest' => '2026-09-18', 'time_of_arrest' => '10:00', 'arresting_officer' => 'Officer A', 'arresting_officer_id' => 'BADGE-1'],
                ['suspect_name' => 'Lydia Manes', 'ghana_card' => 'GHA-222222222-2', 'gender' => 'Female', 'dob' => '1994-08-15', 'place_of_birth' => 'Kumasi', 'phone_number' => '0240000002', 'raid_location' => 'Kumasi', 'date_of_arrest' => '2026-09-18', 'time_of_arrest' => '11:00', 'arresting_officer' => 'Officer B', 'arresting_officer_id' => 'BADGE-2'],
            ],
            'associated_exhibits' => [
                ['category' => 'Drugs', 'seizure_name' => 'Heroin Powder', 'associated_person' => 'Princilla Addo', 'seizure_officer' => 'Officer A'],
                ['category' => 'Weapons', 'seizure_name' => 'AK-47 Rifle', 'associated_person' => 'Lydia Manes', 'seizure_officer' => 'Officer B'],
            ]
        ];

        $response = $this->actingAs($user)
            ->post('/cases/' . $case->id . '/associate', $assocData);

        $response->assertStatus(302);

        $updatedCase = $case->fresh();
        $docketData = is_array($updatedCase->docket_data) ? $updatedCase->docket_data : json_decode($updatedCase->docket_data, true);

        $this->assertCount(3, $docketData['suspects']);
        $this->assertCount(3, $docketData['seizures']);

        $princillaDrug = array_values(array_filter($docketData['seizures'], fn($s) => ($s['associated_person'] ?? null) === 'Princilla Addo'));
        $this->assertCount(1, $princillaDrug);
        $this->assertEquals('Heroin Powder', $princillaDrug[0]['seizure_name']);

        $lydiaWeapon = array_values(array_filter($docketData['seizures'], fn($s) => ($s['associated_person'] ?? null) === 'Lydia Manes'));
        $this->assertCount(1, $lydiaWeapon);
        $this->assertEquals('AK-47 Rifle', $lydiaWeapon[0]['seizure_name']);

        $this->assertEquals('ASC-001', $docketData['suspects'][1]['associate_id'] ?? null);
        $this->assertEquals('ASC-002', $docketData['suspects'][2]['associate_id'] ?? null);

        // Assert review page renders only respective exhibit under each associate suspect
        $reviewPage = $this->actingAs($user)->get('/cases/' . $case->id . '/review');
        $reviewPage->assertStatus(200);
        $html = $reviewPage->getContent();

        // Check Princilla and Lydia sections display associate IDs
        $this->assertStringContainsString('Princilla Addo', $html);
        $this->assertStringContainsString('Lydia Manes', $html);
        $this->assertStringContainsString('ID: ASC-001', $html);
        $this->assertStringContainsString('ID: ASC-002', $html);
    }

    #[Test]
    public function lab_examination_dropdown_filters_completed_exhibits_and_preserves_read_only_certificate()
    {
        $user = User::factory()->head()->create();
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-9903',
            'title' => 'Republic vs Princilla Addo & Atta Budo',
            'suspect_name' => 'Princilla Addo',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)',
            'status' => 'Draft',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Princilla Addo', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                    ['suspect_name' => 'Atta Budo', 'association_role' => 'Mastermind', 'is_associate' => true, 'associate_id' => 'ASC-001'],
                ],

                'seizures' => [
                    ['category' => 'Drugs', 'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)', 'is_associate' => false, 'exhibit_number' => 'EXH-01', 'has_saved_lab' => true],
                    ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine (High-Purity Powder / Slabs)', 'associated_person' => 'Atta Budo', 'is_associate' => true, 'associate_id' => 'ASC-001', 'exhibit_number' => 'EXH-02'],
                ],
                'lab_examination' => [
                    'confirmatory_result' => 'YES',
                    'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)',
                    'substance_identification' => 'Tramadol (High-Dose Tablets / Capsules)',
                    'exhibit_number' => 'EXH-01',
                    'has_saved' => true,
                    'sample_weight' => [
                        'description' => 'Tramadol (High-Dose Tablets / Capsules)',
                        'gross_weight' => '30 Kilograms (kg)',
                        'sample_taken' => '20',
                        'reserved_samples' => '10 kg'
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review?from=Lab+Analysis');

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Lab badge status must show PENDING because Atta Budo's Cocaine is pending
        $this->assertStringContainsString('⭕ LAB PENDING', $html);

        // 2. Dropdown must contain pending exhibit (Cocaine / Atta Budo)
        $this->assertStringContainsString('Cocaine (High-Purity Powder / Slabs)', $html);
        $this->assertStringContainsString('Owner: Atta Budo', $html);

        // 3. Dropdown options must NOT include completed exhibit (Tramadol / Princilla Addo)
        $this->assertStringNotContainsString('data-name="Tramadol (High-Dose Tablets / Capsules)"', $html);

        // 4. Read-only report view MUST render Princilla Addo's completed certificate
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — PRINCILLA ADDO', $html);
        $this->assertStringContainsString('SUSPECT: PRINCILLA ADDO', $html);
        $this->assertStringContainsString('Tramadol (High-Dose Tablets / Capsules)', $html);
    }

    #[Test]
    public function saving_associate_lab_result_preserves_primary_suspect_lab_result_and_renders_both_certificates()
    {
        $user = User::factory()->head()->create();
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-9904',
            'title' => 'Republic vs Princilla Addo & Atta Budo',
            'suspect_name' => 'Princilla Addo',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Princilla Addo', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                    ['suspect_name' => 'Atta Budo', 'association_role' => 'Mastermind', 'is_associate' => true, 'associate_id' => 'ASC-001'],
                ],
                'seizures' => [
                    ['category' => 'Drugs', 'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)', 'is_associate' => false, 'exhibit_number' => 'EXH-01', 'has_saved_lab' => true, 'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)', 'confirmatory_result' => 'YES'],
                    ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine (High-Purity Powder / Slabs)', 'associated_person' => 'Atta Budo', 'is_associate' => true, 'associate_id' => 'ASC-001', 'exhibit_number' => 'EXH-02'],
                ],
                'lab_examination' => [
                    'exhibit_owner_name' => 'Princilla Addo',
                    'exhibit_is_associate' => false,
                    'confirmatory_result' => 'YES',
                    'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)',
                    'substance_identification' => 'Tramadol (High-Dose Tablets / Capsules)',
                    'exhibit_number' => 'EXH-01',
                    'has_saved' => true,
                    'sample_weight' => [
                        'description' => 'Tramadol (High-Dose Tablets / Capsules)',
                        'gross_weight' => '30 Kilograms (kg)',
                        'sample_taken' => '20',
                        'reserved_samples' => '10 kg'
                    ]
                ]
            ]
        ]);

        // Save Atta Budo's lab exam via unit progress POST endpoint
        $postResponse = $this->actingAs($user)->postJson('/cases/' . $case->id . '/update-unit-progress', [
            'unit_name' => 'Lab Analysis',
            'status' => 'COMPLETED',
            'exhibit_owner_name' => 'Atta Budo',
            'exhibit_is_associate' => 1,
            'lab_exhibit_number' => 'EXH-02',
            'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 85%)',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Cocaine (High-Purity Powder / Slabs)',
            'sample_weight_description' => 'Cocaine (High-Purity Powder / Slabs)',
            'sample_weight_gross' => '15 Kilograms (kg)',
            'sample_weight_taken' => '50',
            'sample_weight_reserved' => '500 Grams',
            'confirmatory_test_methods' => 'GC-MS Spectrometry',
            'confirmatory_specifications' => 'High Purity Spec',
            'notes' => 'Cocaine lab analysis completed for Atta Budo.'
        ]);

        $postResponse->assertStatus(200);

        // Fetch refreshed case
        $refreshedCase = ForensicCase::find($case->id);
        $docketData = $refreshedCase->docket_data;

        // 1. Primary suspect exam MUST remain Princilla Addo in docket_data['lab_examination']
        $this->assertEquals('Princilla Addo', $docketData['lab_examination']['exhibit_owner_name']);
        $this->assertEquals('Tramadol (High-Dose Tablets / Capsules)', $docketData['lab_examination']['substance_identification']);

        // 2. Associate suspect exam MUST be attached to seizure EXH-02
        $this->assertTrue($docketData['seizures'][1]['has_saved_lab']);
        $this->assertEquals('YES (Presence of: Cocaine - Purity/Percentage: 85%)', $docketData['seizures'][1]['presumptive_result']);

        // 3. Render review screen & assert BOTH certificates exist in HTML output
        $reviewResponse = $this->actingAs($user)->get('/cases/' . $refreshedCase->case_number . '/review?from=Lab+Analysis');
        $reviewResponse->assertStatus(200);
        $html = $reviewResponse->getContent();

        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — PRINCILLA ADDO', $html);
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — ATTA BUDO', $html);
        $this->assertStringContainsString('Tramadol (High-Dose Tablets / Capsules)', $html);
        $this->assertStringContainsString('Cocaine (High-Purity Powder / Slabs)', $html);
    }

    #[Test]
    public function joint_suspect_case_exhibit_tied_to_both_and_initial_lab_status_is_pending()
    {
        $user = User::factory()->head()->create();

        $docketPayload = [
            'suspects' => [
                ['suspect_name' => 'Kofi Mensah', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                ['suspect_name' => 'Kwame Nkrumah', 'association_role' => 'Co-Suspect', 'is_associate' => true, 'associate_id' => 'ASC-001'],
            ],
            'seizures' => [
                [
                    'category' => 'Drugs',
                    'seizure_name_drug' => 'High Purity Heroin',
                    'seizure_quantity' => '10 kg',
                ]
            ],
            'lab_examination' => [
                'presumptive_result' => 'YES (Positive Colorimetric Reaction)'
            ]
        ];

        $caseData = [
            'title' => 'Joint Suspect Narcotics Case',
            'suspect_name' => 'Kofi Mensah',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Drugs',
            'docket_data' => json_encode($docketPayload),
        ];

        $res = $this->actingAs($user)->postJson('/cases', $caseData);
        $res->assertStatus(200);

        $case = ForensicCase::where('title', 'Joint Suspect Narcotics Case')->first();
        $this->assertNotNull($case);

        $docketData = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);

        // 1. Assert exhibit is associated with both suspects (Kofi Mensah, Kwame Nkrumah)
        $this->assertStringContainsString('Kofi Mensah', $docketData['seizures'][0]['associated_person']);
        $this->assertStringContainsString('Kwame Nkrumah', $docketData['seizures'][0]['associated_person']);

        // 2. Assert initial review screen displays PENDING lab status badge
        $reviewRes = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review');
        $reviewRes->assertStatus(200);
        $html = $reviewRes->getContent();

        $this->assertStringContainsString('⭕ LAB PENDING', $html);
        $this->assertStringContainsString('id="section5BadgeStatus"', $html);
    }

    #[Test]
    public function exhibit_ownership_dropdown_selection_links_to_specific_suspect_or_all_suspects()
    {
        $user = User::factory()->head()->create();

        $docketPayload = [
            'suspects' => [
                ['suspect_name' => 'John Doe', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                ['suspect_name' => 'Michael Smith', 'association_role' => 'Co-Suspect', 'is_associate' => true, 'associate_id' => 'ASC-001'],
            ],
            'seizures' => [
                [
                    'category' => 'Drugs',
                    'seizure_name_drug' => 'Cocaine Powder',
                    'associated_person' => 'Michael Smith',
                    'suspect_ownership' => 'Michael Smith',
                ],
                [
                    'category' => 'Weapons',
                    'seizure_name_weapon' => 'Glock 19',
                    'associated_person' => 'John Doe, Michael Smith',
                    'suspect_ownership' => 'All Suspects',
                ]
            ],
        ];

        $caseData = [
            'title' => 'State vs. John Doe & Michael Smith',
            'suspect_name' => 'John Doe',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Drugs',
            'docket_data' => json_encode($docketPayload),
        ];

        $res = $this->actingAs($user)->postJson('/cases', $caseData);
        $res->assertStatus(200);

        $case = ForensicCase::where('title', 'State vs. John Doe & Michael Smith')->first();
        $this->assertNotNull($case);

        $docketData = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);

        // 1. Exhibit #1 (Cocaine) belongs specifically to Michael Smith
        $this->assertEquals('Michael Smith', $docketData['seizures'][0]['associated_person']);

        // 2. Exhibit #2 (Glock) belongs jointly to All Suspects (John Doe, Michael Smith)
        $this->assertStringContainsString('John Doe', $docketData['seizures'][1]['associated_person']);
        $this->assertStringContainsString('Michael Smith', $docketData['seizures'][1]['associated_person']);
    }
}
