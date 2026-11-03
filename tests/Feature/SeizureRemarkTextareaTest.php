<?php

namespace Tests\Feature;

use App\Models\CaseExhibit;
use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeizureRemarkTextareaTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_management_create_contains_seizure_remark_textarea(): void
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Evidence_Management/create');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('id="newSeizureRemark"', $content);
        $this->assertStringContainsString('name="exhibit_remark"', $content);
        // Verify it is a textarea
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="newSeizureRemark"[^>]*name="exhibit_remark"/i', $content);
        $this->assertStringContainsString('seizure-remark-input', $content);
    }

    public function test_crime_scene_create_contains_seizure_remark_textarea(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Crime_Scene_Support/create');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('id="newSeizureRemark"', $content);
        $this->assertStringContainsString('name="exhibit_remark"', $content);
        // Verify it is a textarea
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="newSeizureRemark"[^>]*name="exhibit_remark"/i', $content);
        $this->assertStringContainsString('seizure-remark-input', $content);
    }

    public function test_show_review_contains_seizure_remark_textarea_in_section4(): void
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CR-REM-TEST-001',
            'suspect_name' => 'Kweku Manu',
            'status' => 'Pending Review',
            'user_id' => $user->id,
            'seizure_name_drug' => 'White Powder Substance',
        ]);

        $response = $this->actingAs($user)->get("/cases/{$case->case_number}/review");
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('id="newSeizureRemark"', $content);
        $this->assertStringContainsString('name="exhibit_remark"', $content);
        // Verify it is a textarea
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="newSeizureRemark"[^>]*name="exhibit_remark"/i', $content);
        $this->assertStringContainsString('seizure-remark-input', $content);
    }

    public function test_show_review_displays_exhibit_remark_in_dossier_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CR-REM-TEST-002',
            'suspect_name' => 'Adwoa Mansa',
            'status' => 'Pending Review',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Compressed Cannabis',
        ]);

        Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-DOSSIER-001',
            'commodity_name' => 'Compressed Cannabis Slab',
            'exhibit_category' => 'Drugs',
            'exhibit_remark' => 'Packaging found with distinct double-layered heat-sealed wrap and yellow tape marking.',
        ]);

        $response = $this->actingAs($user)->get("/cases/{$case->case_number}/review");
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('Packaging found with distinct double-layered heat-sealed wrap and yellow tape marking.', $content);
        $this->assertStringContainsString('Remark:', $content);
    }

    public function test_case_creation_stores_exhibit_remark_in_database(): void
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $payload = [
            'case_number' => 'EM/EXH/REM/2026/001',
            'title' => 'Cocaine Brick Interception Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kofi Amoah',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine Brick',
            'seizure_quantity' => '2 Slabs',
            'seizure_weight' => '2.5',
            'seizure_unit' => 'kg',
            'source_of_exhibits' => 'Kotoka Cargo Area',
            'exhibit_remark' => 'Tamper evident seal #9842 intact upon custody handover to evidence vault.',
            'seizures' => [
                [
                    'item_number' => 1,
                    'name' => 'Cocaine Brick',
                    'seizure_name' => 'Cocaine Brick',
                    'seizure_name_drug' => 'Cocaine Brick',
                    'exhibit_category' => 'Drugs',
                    'seizure_quantity' => '2 Slabs',
                    'seizure_weight' => '2.5',
                    'seizure_unit' => 'kg',
                    'source_of_exhibits' => 'Kotoka Cargo Area',
                    'exhibit_remark' => 'Tamper evident seal #9842 intact upon custody handover to evidence vault.',
                ]
            ],
            'docket_data' => [
                'seizures' => [
                    [
                        'name' => 'Cocaine Brick',
                        'seizure_name' => 'Cocaine Brick',
                        'seizure_name_drug' => 'Cocaine Brick',
                        'exhibit_category' => 'Drugs',
                        'seizure_quantity' => '2 Slabs',
                        'seizure_weight' => '2.5',
                        'seizure_unit' => 'kg',
                        'source_of_exhibits' => 'Kotoka Cargo Area',
                        'exhibit_remark' => 'Tamper evident seal #9842 intact upon custody handover to evidence vault.',
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'Cocaine Brick')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Tamper evident seal #9842 intact upon custody handover to evidence vault.', $exhibit->exhibit_remark);
    }

    public function test_store_exhibits_from_show_review_stores_exhibit_remark_in_database(): void
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CR-REV-EXH-001',
            'suspect_name' => 'Ama Serwaa',
            'status' => 'Pending Review',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Methamphetamine Crystals',
        ]);

        $payload = [
            'associated_exhibits' => [
                [
                    'commodity_name' => 'Methamphetamine Crystal Ice',
                    'seizure_name' => 'Methamphetamine Crystal Ice',
                    'category' => 'Drugs',
                    'exhibit_category' => 'Drugs',
                    'quantity' => '1 Bag',
                    'weight' => '1.2',
                    'unit' => 'kg',
                    'source_of_exhibits' => 'Luggage Checkpoint 2',
                    'exhibit_remark' => 'Blue crystal tint observed; heat sealed foil intact.',
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson("/cases/{$case->case_number}/associate", $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'Methamphetamine Crystal Ice')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Blue crystal tint observed; heat sealed foil intact.', $exhibit->exhibit_remark);
    }
}
