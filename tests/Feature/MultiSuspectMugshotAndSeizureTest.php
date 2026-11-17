<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
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
}
