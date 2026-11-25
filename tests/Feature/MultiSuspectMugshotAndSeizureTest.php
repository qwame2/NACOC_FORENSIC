<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiSuspectMugshotAndSeizureTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_suspect_case_saves_mugshots_and_renders_clean_seizure_valuations()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $payload = [
            'case_number' => 'CSS-2026-9999',
            'title' => 'Republic Vs Eric Atta and Linda Siebia',
            'unit_name' => 'Crime Scene Support',
            'suspect_docket_mode' => 'MULTIPLE',
            'suspect_name' => 'ERIC ATTA',
            'ghana_card' => 'GHA-001122334-1',
            'has_seizures' => 1,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cannabis Sativa',
            'seizure_value' => 'GHS 15,000',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'ERIC ATTA',
                        'role' => 'Primary Suspect',
                        'association_role' => 'Primary Suspect',
                        'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                        'mugshot_left' => null,
                        'mugshot_right' => null,
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name' => 'LINDA SIEBIA',
                        'role' => 'Co-Suspect',
                        'association_role' => 'Co-Suspect',
                        'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                        'mugshot_left' => null,
                        'mugshot_right' => null,
                    ],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Drugs',
                        'associated_person' => 'ERIC ATTA',
                        'seizure_name' => 'Cannabis Sativa',
                        'seizure_quantity' => '2 Bags',
                        'seizure_weight' => '5',
                        'seizure_unit' => 'KG',
                        'seizure_value' => 'GHS 15,000',
                    ],
                    [
                        'item_number' => 2,
                        'exhibit_category' => 'Weapons',
                        'associated_person' => 'LINDA SIEBIA',
                        'seizure_name' => 'Beretta 9mm Pistol',
                        'seizure_quantity' => '1',
                        'seizure_value' => 'GHS 8,000',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $case = ForensicCase::where('case_number', 'CSS-2026-9999')->first();
        $this->assertNotNull($case);

        $suspectsList = $case->getSuspectsList();
        $this->assertCount(2, $suspectsList);
        $this->assertNotNull($suspectsList[0]['mugshot_front']);
        $this->assertNotNull($suspectsList[1]['mugshot_front']);

        // Assert review page renders clean valuations without multiline Item # formatting errors
        $reviewResponse = $this->actingAs($user)->get("/cases/{$case->case_number}/review?from=Lab%20Analysis");
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('LINDA SIEBIA');
        $reviewResponse->assertDontSee('Item #1 [Drugs]:');
    }

    public function test_co_suspect_multi_contact_phone_numbers_persist_and_render_in_review()
    {
        $user = User::factory()->create([
            'unit' => 'Cases',
            'is_head' => true,
        ]);

        $payload = [
            'case_number' => 'CAS-2026-7788',
            'title' => 'Republic Vs Kwesi Mensah and Yaw Osei',
            'unit_name' => 'Cases',
            'suspect_docket_mode' => 'MULTIPLE',
            'suspect_name' => 'KWESI MENSAH',
            'phone' => '+233 24 111 2233',
            'ghana_card' => 'GHA-771122334-1',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'KWESI MENSAH',
                        'role' => 'Primary Suspect',
                        'association_role' => 'Primary Suspect',
                        'phone' => '+233 24 111 2233',
                        'phone_label' => 'Primary',
                        'phones' => [
                            ['label' => 'Primary', 'number' => '+233 24 111 2233', 'is_primary' => true],
                            ['label' => 'WhatsApp', 'number' => '+233 20 111 2233', 'is_primary' => false],
                        ],
                        'additional_phones' => ['+233 20 111 2233'],
                        'additional_phone_labels' => ['WhatsApp'],
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name' => 'YAW OSEI',
                        'role' => 'Co-Suspect',
                        'association_role' => 'Co-Suspect',
                        'phone' => '+233 24 555 6677',
                        'phone_label' => 'Primary',
                        'phones' => [
                            ['label' => 'Primary', 'number' => '+233 24 555 6677', 'is_primary' => true],
                            ['label' => 'WhatsApp', 'number' => '+233 50 888 9900', 'is_primary' => false],
                            ['label' => 'Emergency', 'number' => '+233 27 333 4455', 'is_primary' => false],
                        ],
                        'additional_phones' => ['+233 50 888 9900', '+233 27 333 4455'],
                        'additional_phone_labels' => ['WhatsApp', 'Emergency'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $case = ForensicCase::where('case_number', 'CAS-2026-7788')->first();
        $this->assertNotNull($case);

        $suspectsList = $case->getSuspectsList();
        $this->assertCount(2, $suspectsList);

        // Verify co-suspect has multiple phones preserved in docket
        $coSuspect = $suspectsList[1];
        $this->assertEquals('YAW OSEI', $coSuspect['suspect_name']);
        $this->assertNotEmpty($coSuspect['phones']);
        $this->assertCount(3, $coSuspect['phones']);
        $this->assertEquals('+233 24 555 6677', $coSuspect['phones'][0]['number']);
        $this->assertEquals('+233 50 888 9900', $coSuspect['phones'][1]['number']);
        $this->assertEquals('+233 27 333 4455', $coSuspect['phones'][2]['number']);

        // Verify review page renders co-suspect multi phones
        $reviewResponse = $this->actingAs($user)->get("/cases/{$case->case_number}/review?from=Cases");
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('YAW OSEI');
        $reviewResponse->assertSee('+233 24 555 6677');
        $reviewResponse->assertSee('+233 50 888 9900');
        $reviewResponse->assertSee('+233 27 333 4455');
        $reviewResponse->assertSee('3 numbers');
    }

    public function test_disk_stored_suspect_mugshots_resolve_with_storage_prefix_across_views(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $diskPath = 'suspects/2026/11/css_front_s0_test12345.jpg';

        // 1. Resolver unit verification
        $resolved = \App\Services\CasePhotoResolver::resolveMugshotUrl($diskPath);
        $this->assertNotNull($resolved);
        $this->assertStringContainsString('storage/suspects/2026/11/css_front_s0_test12345.jpg', $resolved);

        // Also test array resolution
        $resolvedFromArray = \App\Services\CasePhotoResolver::resolveMugshotUrl(['front' => $diskPath]);
        $this->assertEquals($resolved, $resolvedFromArray);

        // 2. Case creation with disk path in docket and top level
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-MUG01',
            'title' => 'Republic Vs Kofi Mensah',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'KOFI MENSAH',
            'status' => 'Pending Review',
            'mugshot_front' => $diskPath,
            'docket_data' => [
                'suspect_name' => 'KOFI MENSAH',
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'KOFI MENSAH',
                        'role' => 'Primary Suspect',
                        'mugshot_front' => $diskPath,
                    ],
                ],
            ],
        ]);

        $suspectsList = $case->getSuspectsList();
        $this->assertCount(1, $suspectsList);
        $this->assertStringContainsString('storage/suspects/2026/11/css_front_s0_test12345.jpg', $suspectsList[0]['mugshot_front']);
        $this->assertEquals($diskPath, $suspectsList[0]['raw_mugshot_front']);

        // 3. Review page renders valid img src
        $reviewResponse = $this->actingAs($user)->get("/cases/{$case->case_number}/review?from=Crime%20Scene%20Support");
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('KOFI MENSAH');
        $reviewResponse->assertSee('storage/suspects/2026/11/css_front_s0_test12345.jpg');
        $reviewResponse->assertDontSee('src="' . $diskPath . '"'); // Must never output relative path without storage

        // 4. Case partial table renders valid img src with storage prefix
        $tableResponse = $this->actingAs($user)->get('/cases');
        $tableResponse->assertStatus(200);
        $tableResponse->assertSee('storage/suspects/2026/11/css_front_s0_test12345.jpg');
        $tableResponse->assertDontSee('src="' . $diskPath . '"');
    }

    public function test_primary_suspect_inherits_top_level_mugshot_when_nested_is_empty(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $topLevelMugshot = 'suspects/2026/11/case_top_s0_primary.jpg';

        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-MUG02',
            'title' => 'Republic Vs Kwame Manu',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'KWAME MANU',
            'status' => 'Pending Review',
            'mugshot_front' => $topLevelMugshot,
            'docket_data' => [
                'suspect_name' => 'KWAME MANU',
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'KWAME MANU',
                        'role' => 'Primary Suspect',
                        'mugshot_front' => null, // Intentionally null in nested array
                    ],
                ],
            ],
        ]);

        $suspectsList = $case->getSuspectsList();
        $this->assertCount(1, $suspectsList);
        // Primary suspect inherits top-level mugshot and gets formatted URL
        $this->assertNotNull($suspectsList[0]['mugshot_front']);
        $this->assertStringContainsString('storage/suspects/2026/11/case_top_s0_primary.jpg', $suspectsList[0]['mugshot_front']);
    }

    public function test_exhibit_dossier_fetches_investigating_officer_from_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        // Case where investigating_officer is NULL on the case model
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-INV01',
            'title' => 'Republic Vs Kwame Mensah',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'KWAME MENSAH',
            'status' => 'Pending Review',
            'investigating_officer' => null,
            'docket_data' => [
                'suspect_name' => 'KWAME MENSAH',
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'KWAME MENSAH',
                        'role' => 'Primary Suspect',
                    ],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Electronics',
                        'associated_person' => 'KWAME MENSAH',
                        'seizure_name' => 'Samsung S21 Mobile Phone',
                    ],
                ],
            ],
        ]);

        // Case table has NULL investigating_officer
        $this->assertNull($case->investigating_officer);

        // Update the exhibit in the exhibits table to have investigating_officer set
        $exhibit = Exhibit::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($exhibit);
        $exhibit->update([
            'investigating_officer' => 'Det. Insp. Kofi Boateng',
        ]);

        // Refresh case instance and its loaded relations from DB
        $case = ForensicCase::where('case_number', 'CSS-2026-INV01')->first();

        // 1. Verify CaseExhibitResolver populates investigating_officer
        $resolved = app(\App\Services\CaseExhibitResolver::class)->resolveAllCaseExhibits(
            $case,
            is_array($case->docket_data) ? $case->docket_data : [],
            false,
            ['KWAME MENSAH']
        );
        $this->assertNotEmpty($resolved['allCaseExhibits']);
        $firstExhibit = $resolved['allCaseExhibits'][0];
        $this->assertEquals('Det. Insp. Kofi Boateng', $firstExhibit['investigating_officer'] ?? null);

        // 2. Verify ForensicCase::getExhibitsForSuspect populates investigating_officer
        $suspectExhibits = $case->getExhibitsForSuspect('KWAME MENSAH');
        $this->assertNotEmpty($suspectExhibits);
        $this->assertEquals('Det. Insp. Kofi Boateng', $suspectExhibits[0]['investigating_officer'] ?? null);

        // 3. Verify the review page renders the Exhibit Dossier with the investigating officer and not N/A
        $reviewResponse = $this->actingAs($user)->get("/cases/{$case->case_number}/review?from=Crime%20Scene%20Support");
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Det. Insp. Kofi Boateng');
    }
}
