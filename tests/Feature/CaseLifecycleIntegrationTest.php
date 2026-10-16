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
        $this->assertDatabaseHas('single_suspect_cases', [
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
        $this->assertDatabaseHas('single_suspect_cases', [
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

        $response = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review?from=Lab+Analysis&suspect=Atta+Budo');

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Lab badge status must show PENDING because Atta Budo's Cocaine is pending
        $this->assertStringContainsString('⭕ LAB PENDING', $html);

        // 2. Dropdown for Atta Budo must contain Atta Budo's pending exhibit (Cocaine / Atta Budo)
        $this->assertStringContainsString('Cocaine (High-Purity Powder / Slabs)', $html);
        $this->assertStringContainsString('Owner: Atta Budo', $html);

        // 3. Dropdown options must NOT include Princilla Addo's exhibit when Atta Budo is selected
        $this->assertStringNotContainsString('data-name="Tramadol (High-Dose Tablets / Capsules)"', $html);

        // 4. Read-only report view for Princilla Addo MUST render Princilla Addo's completed certificate when Princilla Addo is selected
        $respPrincilla = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review?from=Lab+Analysis&suspect=Princilla+Addo');
        $htmlPrincilla = $respPrincilla->getContent();
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — PRINCILLA ADDO', $htmlPrincilla);
        $this->assertStringNotContainsString('PRIMARY SUSPECT DOSSIER', $htmlPrincilla);
        $this->assertStringContainsString('Tramadol (High-Dose Tablets / Capsules)', $htmlPrincilla);
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
                    ['category' => 'Drugs', 'seizure_name_drug' => 'Tramadol (High-Dose Tablets / Capsules)', 'associated_person' => 'Princilla Addo', 'is_associate' => false, 'exhibit_number' => 'EXH-01', 'has_saved_lab' => true, 'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 20%)', 'confirmatory_result' => 'YES'],
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

        // 3. Render review screen & assert respective certificates exist for each suspect when selected
        $reviewResponsePrimary = $this->actingAs($user)->get('/cases/' . $refreshedCase->case_number . '/review?from=Lab+Analysis&suspect=Princilla+Addo');
        $reviewResponsePrimary->assertStatus(200);
        $htmlPrimary = $reviewResponsePrimary->getContent();

        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — PRINCILLA ADDO', $htmlPrimary);
        $this->assertStringContainsString('Tramadol (High-Dose Tablets / Capsules)', $htmlPrimary);

        $reviewResponseAssoc = $this->actingAs($user)->get('/cases/' . $refreshedCase->case_number . '/review?from=Lab+Analysis&suspect=Atta+Budo');
        $reviewResponseAssoc->assertStatus(200);
        $htmlAssoc = $reviewResponseAssoc->getContent();

        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — ATTA BUDO', $htmlAssoc);
        $this->assertStringContainsString('Cocaine (High-Purity Powder / Slabs)', $htmlAssoc);
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
        $reviewRes = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review?from=Lab+Analysis');
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

    #[Test]
    public function case_title_synchronizes_when_suspect_name_is_updated()
    {
        $user = User::factory()->head()->create();

        $docketPayload = [
            'suspects' => [
                ['suspect_name' => 'Eric', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                ['suspect_name' => 'Linda', 'association_role' => 'Co-Suspect', 'is_associate' => true, 'associate_id' => 'ASC-001'],
                ['suspect_name' => 'Atta', 'association_role' => 'Co-Suspect', 'is_associate' => true, 'associate_id' => 'ASC-002'],
            ],
            'seizures' => [
                ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine Powder', 'associated_person' => 'Eric, Linda, Atta']
            ]
        ];

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-7701',
            'title' => 'Republic vs Eric, Linda, Atta',
            'suspect_name' => 'Eric',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => $docketPayload,
        ]);

        // 1. Update Suspect 1: Eric -> Ericoo
        $payload1 = [
            'suspects' => [
                ['suspect_name' => 'Ericoo', 'association_role' => 'Primary Suspect'],
                ['suspect_name' => 'Linda', 'association_role' => 'Co-Suspect'],
                ['suspect_name' => 'Atta', 'association_role' => 'Co-Suspect'],
            ]
        ];
        $res1 = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'suspects_data_json' => json_encode($payload1)
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('Republic vs Ericoo, Linda, Atta', $case->fresh()->title);

        // 2. Update Suspect 2: Linda -> Lindaah
        $payload2 = [
            'suspects' => [
                ['suspect_name' => 'Ericoo', 'association_role' => 'Primary Suspect'],
                ['suspect_name' => 'Lindaah', 'association_role' => 'Co-Suspect'],
                ['suspect_name' => 'Atta', 'association_role' => 'Co-Suspect'],
            ]
        ];
        $res2 = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'suspects_data_json' => json_encode($payload2)
        ]);
        $res2->assertStatus(200);
        $this->assertEquals('Republic vs Ericoo, Lindaah, Atta', $case->fresh()->title);

        // 3. Update Suspect 3: Atta -> Attab
        $payload3 = [
            'suspects' => [
                ['suspect_name' => 'Ericoo', 'association_role' => 'Primary Suspect'],
                ['suspect_name' => 'Lindaah', 'association_role' => 'Co-Suspect'],
                ['suspect_name' => 'Attab', 'association_role' => 'Co-Suspect'],
            ]
        ];
        $res3 = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'suspects_data_json' => json_encode($payload3)
        ]);
        $res3->assertStatus(200);
        $this->assertEquals('Republic vs Ericoo, Lindaah, Attab', $case->fresh()->title);

        // 4. Test Single Suspect Case Title synchronization
        $singleCase = ForensicCase::create([
            'case_number' => 'NFC-2026-7702',
            'title' => 'Republic vs Eric',
            'suspect_name' => 'Eric',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => ['suspects' => [['suspect_name' => 'Eric']]],
        ]);

        $singleRes = $this->actingAs($user)->postJson('/cases/' . $singleCase->case_number . '/update', [
            'suspect_name' => 'Ericoo'
        ]);
        $singleRes->assertStatus(200);
        $this->assertEquals('Republic vs Ericoo', $singleCase->fresh()->title);

        // 5. Test Case where suspect_name column holds combined suspect names string
        $combinedCase = ForensicCase::create([
            'case_number' => 'NFC-2026-7703',
            'title' => 'Republic vs Eric, Linda, Atta',
            'suspect_name' => 'Eric, Linda, Atta',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Eric, Linda, Atta'],
                    ['suspect_name' => 'Linda'],
                    ['suspect_name' => 'Atta'],
                ]
            ],
        ]);

        $combinedRes = $this->actingAs($user)->postJson('/cases/' . $combinedCase->case_number . '/update', [
            'title' => 'Republic vs Eric, Linda, Atta',
            'suspects_data_json' => json_encode([
                'suspects' => [
                    ['suspect_name' => 'Ericoo'],
                    ['suspect_name' => 'Linda'],
                    ['suspect_name' => 'Atta'],
                ]
            ])
        ]);
        $combinedRes->assertStatus(200);
        $this->assertEquals('Republic vs Ericoo, Linda, Atta', $combinedCase->fresh()->title);
    }

    #[Test]
    public function test_seized_exhibit_photos_persist_when_case_is_updated()
    {
        $user = User::factory()->head()->create();

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-7704',
            'title' => 'Republic vs John Doe',
            'suspect_name' => 'John Doe',
            'seizure_name_drug' => 'Suspected Cocaine',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => [
                'suspects' => [['suspect_name' => 'John Doe']],
                'seizures' => [['seizure_name_drug' => 'Suspected Cocaine', 'evidence_photos' => []]],
            ],
        ]);

        $testPhotos = [
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'https://example.com/exhibit_photo2.jpg'
        ];

        $updateResponse = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'title' => 'Republic vs John Doe',
            'suspects_data_json' => json_encode([
                'suspects' => [['suspect_name' => 'John Doe']],
                'exhibits' => [
                    [
                        'exhibit_index' => 0,
                        'seizure_name_drug' => 'Suspected Cocaine',
                        'evidence_photos' => $testPhotos,
                    ]
                ]
            ])
        ]);

        $updateResponse->assertStatus(200);

        $refreshedCase = $case->fresh();
        $this->assertEquals($testPhotos, $refreshedCase->evidence_photos);

        $docketData = $refreshedCase->docket_data;
        $this->assertEquals($testPhotos, $docketData['seizures'][0]['evidence_photos']);
        $this->assertEquals($testPhotos, $docketData['evidence_photos']);
    }

    #[Test]
    public function test_marital_record_and_spouse_info_displays_and_persists_in_edit_page()
    {
        $user = User::factory()->head()->create();

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-7705',
            'title' => 'Republic vs Kwadwo Mensah',
            'suspect_name' => 'Kwadwo Mensah',
            'marital_status' => 'Married',
            'children_count' => '2',
            'spouse_name' => 'Abena Mensah',
            'spouse_phone' => '+233208840129',
            'spouse_info' => 'Trader, Tema Sector 1',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Kwadwo Mensah',
                        'marital_status' => 'Married',
                        'children_count' => '2',
                        'spouse_name' => 'Abena Mensah',
                        'spouse_phone' => '+233208840129',
                        'spouse_info' => 'Trader, Tema Sector 1',
                    ]
                ]
            ],
        ]);

        // 1. Assert edit page displays previously saved spouse information & marital status
        $editResponse = $this->actingAs($user)->get('/cases/' . $case->case_number . '/edit');
        $editResponse->assertStatus(200);
        $editHtml = $editResponse->getContent();

        $this->assertStringContainsString('Abena Mensah', $editHtml);
        $this->assertStringContainsString('+233208840129', $editHtml);
        $this->assertStringContainsString('Trader, Tema Sector 1', $editHtml);
        $this->assertStringContainsString('Married', $editHtml);

        // 2. Perform update modifying spouse info and children count
        $updateResponse = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'title' => 'Republic vs Kwadwo Mensah',
            'suspects_data_json' => json_encode([
                'suspects' => [
                    [
                        'suspect_name' => 'Kwadwo Mensah',
                        'marital_status' => 'Married',
                        'children_count' => '3',
                        'spouse_name' => 'Abena Mensah-Updated',
                        'spouse_phone' => '+233208840999',
                        'spouse_info' => 'Senior Manager, Tema Sector 1',
                    ]
                ]
            ])
        ]);

        $updateResponse->assertStatus(200);

        $refreshedCase = $case->fresh();
        $this->assertEquals('Married', $refreshedCase->marital_status);
        $this->assertEquals('3', $refreshedCase->children_count);
        $this->assertEquals('Abena Mensah-Updated', $refreshedCase->spouse_name);
        $this->assertEquals('+233208840999', $refreshedCase->spouse_phone);
        $this->assertEquals('Senior Manager, Tema Sector 1', $refreshedCase->spouse_info);
    }

    #[Test]
    public function test_clearing_mugshot_and_exhibit_photos_persists_empty_state()
    {
        $user = User::factory()->head()->create();

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-7706',
            'title' => 'Republic vs Jane Doe',
            'suspect_name' => 'Jane Doe',
            'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'evidence_photos' => ['https://example.com/photo1.jpg'],
            'unit_name' => 'Crime Scene Support',
            'docket_data' => [
                'mugshots' => ['front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='],
                'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'evidence_photos' => ['https://example.com/photo1.jpg'],
                'suspects' => [
                    [
                        'suspect_name' => 'Jane Doe',
                        'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                        'mugshots' => ['front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==']
                    ]
                ],
                'seizures' => [
                    ['seizure_name_drug' => 'Heroin', 'evidence_photos' => ['https://example.com/photo1.jpg']]
                ],
            ],
        ]);

        $updateResponse = $this->actingAs($user)->postJson('/cases/' . $case->case_number . '/update', [
            'title' => 'Republic vs Jane Doe',
            'mugshot_front' => '',
            'mugshot_left' => '',
            'mugshot_right' => '',
            'suspects_data_json' => json_encode([
                'suspects' => [
                    [
                        'suspect_name' => 'Jane Doe',
                        'mugshot_front' => '',
                        'mugshot_left' => '',
                        'mugshot_right' => '',
                        'mugshots' => [
                            'front' => '',
                            'front-profile' => '',
                            'left' => '',
                            'left-profile' => '',
                            'right' => '',
                            'right-profile' => ''
                        ]
                    ]
                ],
                'exhibits' => [
                    [
                        'exhibit_index' => 0,
                        'seizure_name_drug' => 'Heroin',
                        'evidence_photos' => [],
                    ]
                ]
            ])
        ]);

        $updateResponse->assertStatus(200);

        $refreshedCase = $case->fresh();
        $this->assertEquals('', $refreshedCase->mugshot_front);
        $this->assertEquals([], $refreshedCase->evidence_photos);

        $docketData = $refreshedCase->docket_data;
        $this->assertEquals('', $docketData['mugshot_front'] ?? null);
        $this->assertEquals([], $docketData['evidence_photos'] ?? null);
        $this->assertEquals([], $docketData['seizures'][0]['evidence_photos'] ?? null);

        $editResponse = $this->actingAs($user)->get('/cases/' . $case->case_number . '/edit');
        $editResponse->assertStatus(200);
        $editHtml = $editResponse->getContent();

        $this->assertStringNotContainsString('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', $editHtml);
        $this->assertStringNotContainsString('https://example.com/photo1.jpg', $editHtml);
    }

    #[Test]
    public function associate_lab_dossier_filters_out_primary_and_co_suspect_names()
    {
        $user = User::factory()->head()->create();

        $docketData = [
            'suspects' => [
                ['suspect_name' => 'ERIC ADDO', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                ['suspect_name' => 'LINDA ATTA', 'association_role' => 'Co-Suspect', 'is_associate' => true],
            ],
            'seizures' => [
                ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine', 'associated_person' => 'ERIC ADDO, LINDA ATTA']
            ]
        ];

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-9901',
            'title' => 'State vs Eric Addo, Linda Atta',
            'suspect_name' => 'ERIC ADDO, LINDA ATTA',
            'unit_name' => 'Lab Analysis',
            'docket_data' => $docketData,
        ]);

        $reviewResponse = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review');
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        // The review page must not render an erroneous separate ASSOCIATE DOSSIER card
        $this->assertStringNotContainsString('ASSOCIATE DOSSIER', $reviewHtml);
        $this->assertStringNotContainsString('ASSOCIATE EXHIBIT VERIFIED', $reviewHtml);
    }

    #[Test]
    public function lab_result_section_omitted_when_no_completed_lab_exam_submitted_for_exhibit()
    {
        $user = User::factory()->head()->create();

        $docketData = [
            'suspects' => [
                ['suspect_name' => 'Primary Suspect', 'association_role' => 'Primary Suspect', 'is_associate' => false],
                ['suspect_name' => 'Atta Budo', 'association_role' => 'Mastermind', 'is_associate' => true, 'associate_id' => 'ASC-001'],
                ['suspect_name' => 'Wakanda Asiedu', 'association_role' => 'Accomplice', 'is_associate' => true, 'associate_id' => 'ASC-004'],
            ],
            'seizures' => [
                ['category' => 'Drugs', 'seizure_name_drug' => 'Cocaine Slabs', 'associated_person' => 'Primary Suspect', 'is_associate' => false, 'has_saved_lab' => true],
                ['category' => 'Drugs', 'seizure_name_drug' => 'Tramadol Tablets', 'associated_person' => 'Atta Budo', 'is_associate' => true, 'associate_id' => 'ASC-001', 'has_saved_lab' => true],
                ['category' => 'Weapons', 'seizure_name' => 'AK-47 Rifle', 'associated_person' => 'Wakanda Asiedu', 'is_associate' => true, 'associate_id' => 'ASC-004', 'has_saved_lab' => false],
            ],
            'associate_lab_examinations' => [
                'Atta Budo' => [
                    'exhibit_owner_name' => 'Atta Budo',
                    'substance_identification' => 'Tramadol Hydrochloride',
                    'presumptive_result' => 'YES (Presence of: Tramadol - Purity/Percentage: 100%)',
                    'confirmatory_result' => 'YES',
                    'has_saved' => true,
                ]
            ],
            'lab_examination' => [
                'has_saved' => true,
                'exhibit_owner_name' => 'Primary Suspect',
                'substance_identification' => 'Cocaine Hydrochloride',
                'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 85%)',
                'confirmatory_result' => 'YES',
            ]
        ];

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-9902',
            'title' => 'State vs Primary Suspect, Atta Budo & Wakanda Asiedu',
            'suspect_name' => 'Primary Suspect, Atta Budo',
            'unit_name' => 'Lab Analysis',
            'docket_data' => $docketData,
        ]);

        $reviewResponse = $this->actingAs($user)->get('/cases/' . $case->case_number . '/review');
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        // Atta Budo has a completed drug lab exam, so Atta Budo's lab result dossier MUST render in web review
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — ATTA BUDO', $reviewHtml);

        // Wakanda Asiedu has a weapon exhibit with no saved lab exam, so no lab result dossier should render for Wakanda in web review
        $this->assertStringNotContainsString('LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU', $reviewHtml);

        $pdfResponse = $this->actingAs($user)->get('/cases/' . $case->case_number . '/pdf');
        $pdfResponse->assertStatus(200);
        $pdfHtml = $pdfResponse->getContent();

        // Atta Budo's completed drug lab exam MUST render in PDF report
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — ATTA BUDO', $pdfHtml);

        // Wakanda Asiedu's weapon exhibit MUST be omitted in PDF report
        $this->assertStringNotContainsString('LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU', $pdfHtml);
    }

    #[Test]
    public function lab_result_page_displays_all_cases_in_unified_directory()
    {
        $user = User::factory()->head()->create();

        // 1. Create a drug-related case with suspect
        $drugCase = ForensicCase::create([
            'case_number' => 'CSS-2026-DRUG01',
            'title' => 'Republic vs Eric Mensah',
            'suspect_name' => 'Eric Mensah',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine Powder',
            'gross_weight' => '5.2 kg',
            'status' => 'Under Multi-Unit Processing',
        ]);

        // 2. Create a non-drug case (weapons)
        $weaponCase = ForensicCase::create([
            'case_number' => 'CSS-2026-GUN01',
            'title' => 'Republic vs James Bond',
            'suspect_name' => 'James Bond',
            'unit_name' => 'Crime Scene Support',
            'exhibit_category' => 'Weapons & Firearm',
            'seizure_name' => 'AK-47 Rifle',
            'status' => 'Under Multi-Unit Processing',
        ]);

        $response = $this->actingAs($user)->get('/lab-result');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Must display drug-related case reference & suspect
        $this->assertStringContainsString('CSS-2026-DRUG01', $html);
        $this->assertStringContainsString('Eric Mensah', $html);

        // Non-drug cases (weapons, digital forensics) are strictly excluded from drug directory
        $this->assertStringNotContainsString('CSS-2026-GUN01', $html);
        $this->assertStringNotContainsString('James Bond', $html);
    }

    #[Test]
    public function lab_analysis_new_suspect_creation_with_lab_result_updates_status_in_view_docket()
    {
        $analystUser = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // 1. Submit a new case with suspect biodata and lab examination information simultaneously
        $caseData = [
            'case_number'                           => 'LAB-2026-TEST01',
            'title'                                 => 'Republic vs Kwabena Darko',
            'unit_name'                             => 'Lab Analysis',
            'commanding_officer'                    => 'Samuel Atta',
            'submitting_officer'                    => $analystUser->fullname,
            'suspect_name'                          => 'Kwabena Darko',
            'ghana_card'                            => 'GHA-11223344-9',
            'arrest_date'                           => date('Y-m-d'),
            'arrest_time'                           => '10:30 AM',
            'offense_description'                   => 'Illicit Drug Trafficking',
            'exhibit_category'                      => 'Drugs',
            'seizure_name_drug'                     => 'Heroin White Powder',
            'seizure_weight'                        => '12.5',
            'seizure_unit'                          => 'kg',
            'laboratory_number'                     => 'LAB-REF-2026-88',
            'presumptive_reaction_result_input'     => 'YES (Positive Colorimetric Reaction)',
            'confirmatory_test_result'              => 'YES',
            'sample_weight_description'             => 'Heroin Powder',
            'sample_weight_gross'                   => '12.5 kg',
            'sample_weight_taken'                   => '150 g',
            'sample_weight_reserved'                => '12.35 kg',
            'confirmatory_substance_identification' => 'Diacetylmorphine (Heroin)',
            'confirmatory_test_methods'             => 'Gas Chromatography-Mass Spectrometry (GC-MS)',
        ];

        $response = $this->actingAs($analystUser)->post('/cases', $caseData);
        $response->assertStatus(302);

        $this->assertDatabaseHas('single_suspect_cases', [
            'case_number'  => 'LAB-2026-TEST01',
            'suspect_name' => 'Kwabena Darko',
        ]);
        $this->assertDatabaseHas('laboratory_examination_results', [
            'confirmatory_result' => 'YES',
        ]);

        // 2. Open View Docket for Kwabena Darko
        $reviewResponse = $this->actingAs($analystUser)->get('/cases/LAB-2026-TEST01/review?from=Lab+Analysis');
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        // Must display ✓ LAB COMPLETED badge instead of ⭕ LAB PENDING
        $this->assertStringContainsString('✓ LAB COMPLETED', $reviewHtml);
        $this->assertStringNotContainsString('LABORATORY EXAMINATION: PENDING', $reviewHtml);
        $this->assertStringContainsString('Diacetylmorphine (Heroin)', $reviewHtml);
        $this->assertStringContainsString('Gas Chromatography-Mass Spectrometry (GC-MS)', $reviewHtml);
    }

    #[Test]
    public function lab_certificate_page_shows_laboratory_examination_result_of_suspect()
    {
        $user = User::factory()->head()->create();
        $case = ForensicCase::create([
            'case_number' => 'CERT-2026-9001',
            'title' => 'Republic vs Ben Adu',
            'suspect_name' => 'Ben Adu',
            'unit_name' => 'Lab Analysis',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine Hydrochloride',
            'exhibits_summary' => '12.5 Kilograms (kg)',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'laboratory_number' => 'Lab No. 99/FS01/2026',
                'suspects' => [
                    ['suspect_name' => 'Ben Adu', 'suspect_alias' => 'Kofi Ghana', 'ghana_card' => 'GHA-78920194-2', 'gender' => 'Male', 'role' => 'Primary Suspect']
                ],
                'lab_examination' => [
                    'has_saved' => true,
                    'exam_reference' => 'GSA-FM-T99-F',
                    'exhibit_number' => 'EXH-01',
                    'laboratory_number' => 'Lab No. 99/FS01/2026',
                    'analyst' => 'Dr. E. Quaye',
                    'date_received' => '2026-10-01',
                    'date_started' => '2026-10-01',
                    'date_completed' => '2026-10-01',
                    'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 88%)',
                    'confirmatory_result' => 'YES',
                    'substance_identification' => 'Cocaine Hydrochloride',
                    'specifications' => 'Schedule II Controlled Substance',
                    'remark' => 'High purity sample verified under GC-MS analysis.',
                ]
            ]
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/certificate/' . $case->case_number);
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('3. LABORATORY EXAMINATION RESULT OF THE SUSPECT', $html);
        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT — BEN ADU', $html);
        $this->assertStringContainsString('GSA-FM-T99-F', $html);
        $this->assertStringContainsString('Cocaine Hydrochloride', $html);
        $this->assertStringContainsString('High purity sample verified under GC-MS analysis.', $html);
    }

    #[Test]
    public function lab_analytical_page_shows_laboratory_examination_result_and_report_dropdown()
    {
        $user = User::factory()->head()->create();
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-6730',
            'title' => 'Republic vs Kojo Adu',
            'suspect_name' => 'Kojo Adu',
            'unit_name' => 'Lab Analysis',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Heroin (Brown Sugar / Diacetylmorphine)',
            'exhibits_summary' => '90 Kilograms (kg)',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'laboratory_number' => 'Lab No. 41/FS09/2026',
                'suspects' => [
                    ['suspect_name' => 'Kojo Adu', 'suspect_alias' => 'Kofi Ghana', 'ghana_card' => 'GHA-78920194-2', 'gender' => 'Male', 'role' => 'Primary Suspect']
                ],
                'lab_examination' => [
                    'has_saved' => true,
                    'exam_reference' => 'GSA-FM-T51-F',
                    'exhibit_number' => '1',
                    'laboratory_number' => 'Lab No. 41/FS09/2026',
                    'analyst' => 'John Mensah',
                    'date_received' => '2026-09-29',
                    'date_started' => '2026-09-29',
                    'date_completed' => '2026-09-30',
                    'presumptive_result' => 'YES (Presence of: Colorimetric Reaction - Purity/Percentage: Heroin (Brown Sugar / Diacetylmorphine))',
                    'confirmatory_result' => 'YES',
                    'substance_identification' => 'Heroin (Brown Sugar / Diacetylmorphine)',
                    'specifications' => 'GFGJFGKFGFJ',
                    'remark' => 'GFJGFGJFKGHGKHGKYGYG',
                    'findings' => 'Submitted via Laboratory Analysis & Case Registration Workspace'
                ]
            ]
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/analytical/' . $case->case_number);
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Heroin (Brown Sugar / Diacetylmorphine)', $html);
        $this->assertStringContainsString('GSA Report', $html);
        $this->assertStringContainsString('nlr-report-pdf', $html);

        // Verify standalone NLR Report PDF route
        $pdfResponse = $this->actingAs($user)->get('/Lab_Analysis/nlr-report-pdf/' . $case->id);
        $pdfResponse->assertStatus(200);
        $pdfHtml = $pdfResponse->getContent();
        $this->assertStringNotContainsString('3. Laboratory Examination Result', $pdfHtml);
        $this->assertStringContainsString('NACOC LAB RESULT', $pdfHtml);
        $this->assertTrue(str_contains($pdfHtml, 'NACOC_LOGO.PNG') || str_contains($pdfHtml, 'NACOC LOGO.jfif'));
        $this->assertStringContainsString('nacoc.jfif', $pdfHtml);
        $this->assertStringContainsString('nlrReportSheet', $pdfHtml);
        $this->assertStringContainsString('NLR-FM-T51-F', $pdfHtml);
        $this->assertStringNotContainsString('GSA-FM-T51-F', $pdfHtml);
    }

    public function test_nlr_report_store_and_database_persistence(): void
    {
        $user = User::factory()->create([
            'unit'        => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = \App\Models\SingleSuspectCase::create([
            'case_number'        => 'CSS-NLR-TEST-' . rand(1000, 9999),
            'laboratory_number'  => '72/FS99/' . date('Y'),
            'title'              => 'NLR Report Storage Test',
            'suspect_name'       => 'KWAME OFORI',
            'submitting_officer' => 'Insp. Darko',
            'seizure_name_drug'  => 'Cocaine (Salt Form)',
            'status'             => 'Pending',
        ]);

        $storeResponse = $this->actingAs($user)->postJson('/Lab_Analysis/nlr-reports/store', [
            'case_id'                      => $case->id,
            'case_number'                  => $case->case_number,
            'laboratory_number'            => $case->laboratory_number,
            'detailed_exhibit_description' => 'Tested exhibit containing 50 wraps of fine white powder.',
            'condition_of_reserved_sample' => 'sealed',
        ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('nlr_reports', [
            'case_number'                  => $case->case_number,
            'detailed_exhibit_description' => 'Tested exhibit containing 50 wraps of fine white powder.',
            'condition_of_reserved_sample' => 'sealed',
        ]);

        // Verify that opening NLR report PDF renders the database-backed narrative
        $pdfResponse = $this->actingAs($user)->get('/Lab_Analysis/nlr-report-pdf/' . $case->id);
        $pdfResponse->assertStatus(200);
        $pdfHtml = $pdfResponse->getContent();
        $this->assertStringContainsString('Tested exhibit containing 50 wraps of fine white powder.', $pdfHtml);
    }
}


