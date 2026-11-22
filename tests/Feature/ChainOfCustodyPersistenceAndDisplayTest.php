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

        // Digital Forensics and Lab Analysis views have Chain of Custody safely removed completely
        $viewsWithoutCustody = [
            'Digital_Forensics/show_review.blade.php',
            'Digital_Forensics/staff/review.blade.php',
            'Lab_Analysis/show_review.blade.php',
            'Lab_Analysis/staff/review.blade.php',
        ];

        foreach ($viewsWithoutCustody as $viewFile) {
            $content = file_get_contents(resource_path("views/{$viewFile}"));
            $this->assertStringNotContainsString('newSeizureCustodyFrom', $content, "View {$viewFile} should not contain custody from input");
            $this->assertStringNotContainsString('newSeizureCustodyTo', $content, "View {$viewFile} should not contain custody to input");
        }
    }

    #[Test]
    public function evidence_head_asidebar_contains_chain_of_custody_button()
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Commander Evidence Custodian',
            'unit'        => 'Evidence Management',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        // 1. Evidence Management Command Dashboard
        $response = $this->actingAs($headOfficer)->get('/Evidence_Management');
        $response->assertStatus(200);
        $response->assertSee('Chain of Custody', false);
        $response->assertSee('/Evidence_Management/chain-of-custody', false);

        // 2. Evidence Management Asidebar view (e.g. Exhibit Vault)
        $vaultResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/vault');
        $vaultResponse->assertStatus(200);
        $vaultResponse->assertSee('Chain of Custody', false);
        $vaultResponse->assertSee('/Evidence_Management/chain-of-custody', false);

        // 3. Evidence Management Staff Audit Logs view (with asidebar)
        $auditResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/staff-audit-logs');
        $auditResponse->assertStatus(200);
        $auditResponse->assertSee('Chain of Custody', false);
        $auditResponse->assertSee('/Evidence_Management/chain-of-custody', false);

        // 4. Verify route loads the dedicated Chain of Custody register
        $routeResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody');
        $routeResponse->assertStatus(200);
        $routeResponse->assertSee('Chain of Custody Master Register', false);

        // 5. Ensure non-evidence head asidebar (e.g., Lab Analysis) does not display Chain of Custody
        $labHead = User::factory()->create([
            'fullname'    => 'Lab Analysis Head',
            'unit'        => 'Lab Analysis',
            'is_head'     => true,
            'is_approved' => true,
        ]);
        $labResponse = $this->actingAs($labHead)->get('/Lab_Analysis/staff-audit-logs');
        $labResponse->assertStatus(200);
        $labResponse->assertDontSee('/Evidence_Management/chain-of-custody', false);
    }

    #[Test]
    public function chain_of_custody_page_fetches_and_displays_database_custody_records()
    {
        $headOfficer = User::factory()->create([
            'fullname'    => 'Commander Evidence Custodian',
            'unit'        => 'Evidence Management',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/COC-DB-TEST',
            'title' => 'Operation Deep Custody Seizure',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kwame Mensah',
            'status' => 'Under Investigation',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-COC-9999',
            'property_id' => 'PROP-COC-2026-99',
            'commodity_name' => 'High Purity Heroin Parcels',
            'exhibit_category' => 'Drugs',
            'custodial_status' => 'Seized',
            'chain_of_custody' => 'Seized at Kotoka Airport Cargo Terminal -> Handed over to Central Vault Custodian Capt. Mensah',
            'seizure_officer' => 'Inspector K. Boateng',
            'source_of_exhibits' => 'Kotoka Cargo Terminal',
            'seizure_weight' => '450',
            'seizure_unit' => 'Grams (g)',
        ]);

        // 1. Direct page GET request
        $response = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody');
        $response->assertStatus(200);
        $response->assertSee('Chain of Custody Master Register', false);
        $response->assertDontSee('Handling Officer</th>', false);
        $response->assertDontSee('>Status</th>', false);
        $response->assertSee('EXH-COC-9999', false);
        $response->assertSee('PROP-COC-2026-99', false);
        $response->assertSee('High Purity Heroin Parcels', false);
        $response->assertSee('Seized at Kotoka Airport Cargo Terminal', false);
        $response->assertSee('Handed over to Central Vault Custodian Capt. Mensah', false);
        $response->assertSee('Inspector K. Boateng', false);
        $response->assertSee('NACOC/EMU/2026/COC-DB-TEST', false);
        $response->assertSee('Kwame Mensah', false);
        $response->assertSee('coc-action-dropdown-wrapper', false);
        $response->assertSee('toggleCocActionDropdown', false);
        $response->assertSee('Custody Details', false);

        // 2. AJAX request (returns partial table)
        $ajaxResponse = $this->actingAs($headOfficer)->getJson('/Evidence_Management/chain-of-custody', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('chainOfCustodyTable', false);
        $ajaxResponse->assertDontSee('Handling Officer</th>', false);
        $ajaxResponse->assertDontSee('>Status</th>', false);
        $ajaxResponse->assertSee('EXH-COC-9999', false);
        $ajaxResponse->assertSee('Seized at Kotoka Airport Cargo Terminal', false);
        $ajaxResponse->assertSee('Handed over to Central Vault Custodian Capt. Mensah', false);

        // 2b. Explicit ?ajax=1 parameter returns partial table without full page shell
        $ajaxParamResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody?ajax=1');
        $ajaxParamResponse->assertStatus(200);
        $ajaxParamResponse->assertSee('chainOfCustodyTable', false);
        $ajaxParamResponse->assertDontSee('<!DOCTYPE html>', false);
        $ajaxParamResponse->assertSee('EXH-COC-9999', false);

        // 3. Search filter by custody pathway text
        $searchResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody?search=Kotoka+Airport');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('EXH-COC-9999', false);

        // 4. Search filter non-matching
        $nonMatchResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody?search=NonExistentKeywordXYZ');
        $nonMatchResponse->assertStatus(200);
        $nonMatchResponse->assertDontSee('EXH-COC-9999', false);
        $nonMatchResponse->assertSee('No Chain of Custody Records Found', false);

        // 5. Custodial Status filter
        $matchingStatusResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody?custodial_status=Seized');
        $matchingStatusResponse->assertStatus(200);
        $matchingStatusResponse->assertSee('EXH-COC-9999', false);

        $mismatchStatusResponse = $this->actingAs($headOfficer)->get('/Evidence_Management/chain-of-custody?custodial_status=Court+Evidence');
        $mismatchStatusResponse->assertStatus(200);
        $mismatchStatusResponse->assertDontSee('EXH-COC-9999', false);
    }

    #[Test]
    public function evidence_management_history_route_redirects_to_chain_of_custody_page()
    {
        $headOfficer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => true,
        ]);

        $response = $this->actingAs($headOfficer)->get('/Evidence_Management/history');
        $response->assertRedirect('/Evidence_Management/chain-of-custody');

        $responseWithParams = $this->actingAs($headOfficer)->get('/Evidence_Management/history?search=coc');
        $responseWithParams->assertRedirect('/Evidence_Management/chain-of-custody?search=coc');
    }
}
