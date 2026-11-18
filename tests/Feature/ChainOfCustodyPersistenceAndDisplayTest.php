<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChainOfCustodyPersistenceAndDisplayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function chain_of_custody_column_exists_in_database_and_is_fillable_on_model()
    {
        $this->assertTrue(Schema::hasColumn('exhibits', 'chain_of_custody'), 'Column chain_of_custody should exist on exhibits table');

        $exhibit = new Exhibit();
        $this->assertContains('chain_of_custody', $exhibit->getFillable(), 'chain_of_custody must be fillable on Exhibit model');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/COC01',
            'title' => 'Chain of Custody Test Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kofi Annan',
            'status' => 'Under Investigation',
        ]);

        $created = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-COC-001',
            'commodity_name' => 'Suspected Heroin Parcel',
            'exhibit_category' => 'Drugs',
            'custodial_status' => 'Seized',
            'chain_of_custody' => 'Seized by Officer Mensah at Tema Port -> Transferred to Central Vault Custodian Sgt. Boateng on 16/11/2026',
        ]);

        $this->assertDatabaseHas('exhibits', [
            'id' => $created->id,
            'chain_of_custody' => 'Seized by Officer Mensah at Tema Port -> Transferred to Central Vault Custodian Sgt. Boateng on 16/11/2026',
        ]);
    }

    #[Test]
    public function create_blade_views_contain_chain_of_custody_input_in_seized_item_container()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        // Evidence Management Create View
        $response = $this->actingAs($officer)->get('/Evidence_Management/create');
        $response->assertStatus(200);
        $response->assertSee('Chain of Custody', false);
        $response->assertSee('newSeizureChainOfCustody', false);
        $response->assertSee('seizure-chain-of-custody-input', false);
        $response->assertSee('name="chain_of_custody"', false);

        // Evidence Management Staff Create View
        $staffResponse = $this->actingAs($officer)->get('/Evidence_Management/staff/create');
        $staffResponse->assertStatus(200);
        $staffResponse->assertSee('Chain of Custody', false);
        $staffResponse->assertSee('newSeizureChainOfCustody', false);
        $staffResponse->assertSee('seizure-chain-of-custody-input', false);
        $staffResponse->assertSee('name="chain_of_custody"', false);

        // Crime Scene Support Head Create View
        $cssOfficer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'is_head' => true,
        ]);
        $cssResponse = $this->actingAs($cssOfficer)->get('/Crime_Scene_Support/create');
        $cssResponse->assertStatus(200);
        $cssResponse->assertSee('Chain of Custody', false);
        $cssResponse->assertSee('newSeizureChainOfCustody', false);
        $cssResponse->assertSee('seizure-chain-of-custody-input', false);
        $cssResponse->assertSee('name="chain_of_custody"', false);

        // Crime Scene Support Staff Create View
        $cssStaffResponse = $this->actingAs($cssOfficer)->get('/Crime_Scene_Support/staff/create');
        $cssStaffResponse->assertStatus(200);
        $cssStaffResponse->assertSee('Chain of Custody', false);
        $cssStaffResponse->assertSee('newSeizureChainOfCustody', false);
        $cssStaffResponse->assertSee('seizure-chain-of-custody-input', false);
        $cssStaffResponse->assertSee('name="chain_of_custody"', false);
    }

    #[Test]
    public function vault_view_contains_chain_of_custody_input_inside_vault_custodial_state_section()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $response->assertStatus(200);
        $response->assertSee('Vault Custodial State', false);
        $response->assertSee('inspectCustodyFrom', false);
        $response->assertSee('inspectCustodyTo', false);
        $response->assertSee('inspectChainOfCustody', false);
        $response->assertSee('inspectSaveChainOfCustodyBtn', false);
        $response->assertSee('saveInspectChainOfCustody()', false);
        $response->assertSee('updateInspectChainOfCustodyValue', false);
        $response->assertSee('flex items-center gap-2 sm:gap-2.5 w-full', false);
        $response->assertSee('Custody Transfer / Exchange', false);
    }

    #[Test]
    public function case_intake_persists_chain_of_custody_for_seized_items()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $custodyTrail = 'Scene Seizure -> Station Locker -> Central Evidence Vault Transfer Log #9981';

        $postData = [
            'case_number' => 'NACOC/EMU/2026/COC02',
            'title' => 'Intake With Chain of Custody',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'John Doe',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine Brick',
            'commodity_name' => 'Cocaine Brick',
            'seizure_quantity' => '2',
            'seizure_weight' => '2.5',
            'seizure_unit' => 'kg',
            'chain_of_custody' => $custodyTrail,
            'has_seizures' => true,
            'seizures' => [
                [
                    'item_number' => 1,
                    'name' => 'Cocaine Brick',
                    'seizure_name' => 'Cocaine Brick',
                    'seizure_name_drug' => 'Cocaine Brick',
                    'commodity_name' => 'Cocaine Brick',
                    'exhibit_category' => 'Drugs',
                    'seizure_quantity' => '2',
                    'seizure_weight' => '2.5',
                    'seizure_unit' => 'kg',
                    'seizure_reason' => 'Suspected narcotics smuggling',
                    'chain_of_custody' => $custodyTrail,
                ]
            ],
            'docket_data' => [
                'seizures' => [
                    [
                        'item_number' => 1,
                        'name' => 'Cocaine Brick',
                        'seizure_name' => 'Cocaine Brick',
                        'seizure_name_drug' => 'Cocaine Brick',
                        'commodity_name' => 'Cocaine Brick',
                        'exhibit_category' => 'Drugs',
                        'seizure_quantity' => '2',
                        'seizure_weight' => '2.5',
                        'seizure_unit' => 'kg',
                        'chain_of_custody' => $custodyTrail,
                    ]
                ]
            ]
        ];

        $resp = $this->actingAs($officer)->postJson('/cases', $postData);
        $resp->assertSuccessful();

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Cocaine Brick',
            'chain_of_custody' => $custodyTrail,
        ]);
    }

    #[Test]
    public function crime_scene_support_intake_persists_chain_of_custody_for_seized_items()
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $custodyTrail = 'Crime Scene Support -> Central Vault';

        $postData = [
            'case_number' => 'NACOC/CSS/2026/COC01',
            'title' => 'CSS Intake With Chain of Custody',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Jane Smith',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Heroin Brown Powder',
            'commodity_name' => 'Heroin Brown Powder',
            'seizure_quantity' => '1',
            'seizure_weight' => '1.2',
            'seizure_unit' => 'kg',
            'chain_of_custody' => $custodyTrail,
            'has_seizures' => true,
            'seizures' => [
                [
                    'item_number' => 1,
                    'name' => 'Heroin Brown Powder',
                    'seizure_name' => 'Heroin Brown Powder',
                    'seizure_name_drug' => 'Heroin Brown Powder',
                    'commodity_name' => 'Heroin Brown Powder',
                    'exhibit_category' => 'Drugs',
                    'seizure_quantity' => '1',
                    'seizure_weight' => '1.2',
                    'seizure_unit' => 'kg',
                    'seizure_reason' => 'Recovered at crime scene perimeter',
                    'chain_of_custody' => $custodyTrail,
                ]
            ],
            'docket_data' => [
                'seizures' => [
                    [
                        'item_number' => 1,
                        'name' => 'Heroin Brown Powder',
                        'seizure_name' => 'Heroin Brown Powder',
                        'seizure_name_drug' => 'Heroin Brown Powder',
                        'commodity_name' => 'Heroin Brown Powder',
                        'exhibit_category' => 'Drugs',
                        'seizure_quantity' => '1',
                        'seizure_weight' => '1.2',
                        'seizure_unit' => 'kg',
                        'chain_of_custody' => $custodyTrail,
                    ]
                ]
            ]
        ];

        $resp = $this->actingAs($officer)->postJson('/cases', $postData);
        $resp->assertSuccessful();

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Heroin Brown Powder',
            'chain_of_custody' => $custodyTrail,
        ]);
    }

    #[Test]
    public function unassigned_exhibit_store_persists_chain_of_custody()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $payload = [
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Electronics',
                    'commodity_name' => 'Encrypted Satellite Phone',
                    'seizure_quantity' => '1',
                    'chain_of_custody' => 'Recovered at checkpoint by Customs -> Logged by Evidence Management Staff SD-099',
                ]
            ]
        ];

        $resp = $this->actingAs($officer)->postJson('/Evidence_Management/exhibits/store-unassigned', $payload);
        $resp->assertStatus(200);

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Encrypted Satellite Phone',
            'chain_of_custody' => 'Recovered at checkpoint by Customs -> Logged by Evidence Management Staff SD-099',
        ]);
    }

    #[Test]
    public function vault_update_endpoint_persists_chain_of_custody_and_returns_updated_exhibit()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/COC03',
            'title' => 'Vault Update Test Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Jane Smith',
            'status' => 'Under Investigation',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-COC-003',
            'commodity_name' => 'Seized Cash Bundle',
            'exhibit_category' => 'Money',
            'custodial_status' => 'Seized',
            'chain_of_custody' => 'Initial custody log',
        ]);

        $newChainOfCustody = "2026-11-16 10:00: Handed over to Lab for chemical trace analysis\n2026-11-16 14:00: Returned to Vault Safe #4";

        $response = $this->actingAs($officer)->postJson("/Evidence_Management/exhibits/{$exhibit->id}/update", [
            'chain_of_custody' => $newChainOfCustody,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals($newChainOfCustody, $response->json('exhibit.chain_of_custody'));

        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'chain_of_custody' => $newChainOfCustody,
        ]);
    }

    #[Test]
    public function vault_custodial_status_endpoint_can_also_update_chain_of_custody()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/COC04',
            'title' => 'Custodial Status Endpoint Test Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Robert Mensah',
            'status' => 'Under Investigation',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-COC-004',
            'commodity_name' => 'Gold Bullion Bar',
            'exhibit_category' => 'Valuables',
            'custodial_status' => 'Seized',
            'chain_of_custody' => 'Old custody trail',
        ]);

        $newTrail = 'Transferred to Bank of Ghana vault under armed police escort';

        $response = $this->actingAs($officer)->postJson("/Evidence_Management/exhibits/{$exhibit->id}/custodial-status", [
            'custodial_status' => 'Seized',
            'status_remark' => 'Transferred offsite',
            'chain_of_custody' => $newTrail,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'chain_of_custody' => $newTrail,
        ]);
    }

    #[Test]
    public function chain_of_custody_inputs_render_in_single_horizontal_line_across_views()
    {
        $viewsToCheck = [
            'Evidence_Management/create.blade.php',
            'Evidence_Management/staff/create.blade.php',
            'cases/create.blade.php',
            'Crime_Scene_Support/head/CrimeSceneCreate.blade.php',
            'Crime_Scene_Support/staff/create.blade.php',
            'Evidence_Management/show_review.blade.php',
            'Evidence_Management/staff/review.blade.php',
            'Crime_Scene_Support/show_review.blade.php',
            'Crime_Scene_Support/staff/review.blade.php',
            'Digital_Forensics/show_review.blade.php',
            'Digital_Forensics/staff/review.blade.php',
            'Lab_Analysis/show_review.blade.php',
            'Lab_Analysis/staff/review.blade.php',
            'Forensic_Head/show_review.blade.php',
            'Forensic_Head/staff/review.blade.php',
        ];

        foreach ($viewsToCheck as $viewFile) {
            $content = file_get_contents(resource_path("views/{$viewFile}"));
            $this->assertStringContainsString('flex items-center gap-2 sm:gap-2.5 w-full', $content, "View {$viewFile} must use horizontal flex container for Chain of Custody");
            $this->assertStringContainsString('newSeizureCustodyFrom', $content, "View {$viewFile} must contain custody from input");
            $this->assertStringContainsString('newSeizureCustodyTo', $content, "View {$viewFile} must contain custody to input");
            $this->assertStringContainsString('newSeizureChainOfCustody', $content, "View {$viewFile} must contain synchronized chain of custody textarea");
            $this->assertStringContainsString('updateChainOfCustodyValue', $content, "View {$viewFile} must contain updateChainOfCustodyValue handler");
        }
    }
}
