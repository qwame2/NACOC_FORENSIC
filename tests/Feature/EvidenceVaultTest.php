<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EvidenceVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function exhibit_vault_button_is_rendered_in_the_aside_bar_of_evidence_management_unit()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        // When accessing Evidence Management unit pages, the sidebar should display the "Exhibit Log" button
        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);
        $resp->assertSee('Exhibit Log');
        $resp->assertSee('/Evidence_Management/vault');

        // Also check on Evidence Management dashboard
        $respDash = $this->actingAs($officer)->get('/Evidence_Management');
        $respDash->assertStatus(200);
        $respDash->assertSee('Exhibit Vault');
    }

    #[Test]
    public function exhibit_vault_displays_all_exhibits_and_their_associated_suspects()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        // 1. Create a Single Suspect Case with an exhibit
        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/001',
            'title' => 'Operation Ocean Breeze',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kwame Mensah',
            'suspect_alias' => 'Spider',
            'ghana_card' => 'GHA-123456789-0',
            'status' => 'Under Investigation',
        ]);

        $drugExhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-DRUG-001',
            'commodity_name' => 'High Grade Cocaine Slabs',
            'exhibit_category' => 'Drugs & Narcotics',
            'seizure_weight' => '25.50',
            'seizure_unit' => 'kg',
            'seizure_value' => '3500000.00',
            'seizure_packaging' => 'Brown masking tape blocks',
            'exhibit_remark' => 'Discovered under passenger seat',
        ]);

        // 2. Create a Multiple Suspect Case with multiple suspects and an exhibit
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/002',
            'title' => 'Operation Night Raid',
            'unit_name' => 'Evidence Management',
            'status' => 'Under Investigation',
        ]);

        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 'SUS-001',
            'role' => 'Primary Suspect',
            'suspect_name' => 'Alhaji Ibrahim',
            'suspect_alias' => 'Don',
            'ghana_card' => 'GHA-987654321-1',
        ]);

        $coSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 'SUS-002',
            'role' => 'Co-Suspect',
            'suspect_name' => 'Kojo Antwi',
            'ghana_card' => 'GHA-555555555-5',
        ]);

        $weaponExhibit = Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $primarySuspect->id,
            'exhibit_number' => 'EXH-WPN-002',
            'commodity_name' => 'Glock 19 Gen 5 Pistol',
            'exhibit_category' => 'Weapons & Firearms',
            'wpn_model' => 'Glock 19',
            'wpn_serial_no' => 'GLK-88392',
            'seizure_value' => '45000.00',
            'exhibit_remark' => 'Loaded with 15 rounds of 9mm ammo',
        ]);

        // Request vault page
        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);

        // Verify Exhibit 1 details and associated Single Suspect
        $resp->assertSee('EXH-DRUG-001');
        $resp->assertSee('High Grade Cocaine Slabs');
        $resp->assertSee('Kwame Mensah');
        $resp->assertSee('Spider');
        $resp->assertSee('GHA-123456789-0');
        $resp->assertSee('Discovered under passenger seat');

        // Verify Exhibit 2 details and associated Multiple Suspect Case suspect
        $resp->assertSee('EXH-WPN-002');
        $resp->assertSee('Glock 19 Gen 5 Pistol');
        $resp->assertSee('Alhaji Ibrahim');
        $resp->assertSee('Don');
        $resp->assertSee('GHA-987654321-1');
        $resp->assertSee('Loaded with 15 rounds of 9mm ammo');
    }

    #[Test]
    public function exhibit_vault_supports_filtering_searching_and_table_view()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/003',
            'title' => 'Vehicle Interception',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Samuel Osei',
        ]);

        $vehicleExhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-VEH-003',
            'commodity_name' => 'Toyota Land Cruiser V8',
            'exhibit_category' => 'Vehicles & Transport',
            'veh_make' => 'Toyota',
            'veh_model' => 'Land Cruiser',
            'veh_reg_no' => 'GE-4421-22',
            'seizure_value' => '850000.00',
            'exhibit_remark' => 'Impounded at border checkpoint',
        ]);

        // Search by suspect name
        $searchResp = $this->actingAs($officer)->get('/Evidence_Management/vault?search=Samuel');
        $searchResp->assertStatus(200);
        $searchResp->assertSee('EXH-VEH-003');
        $searchResp->assertSee('Samuel Osei');

        // Search by vehicle registration number
        $searchReg = $this->actingAs($officer)->get('/Evidence_Management/vault?search=GE-4421-22');
        $searchReg->assertStatus(200);
        $searchReg->assertSee('EXH-VEH-003');

        // Table view mode
        $tableResp = $this->actingAs($officer)->get('/Evidence_Management/vault?view=table');
        $tableResp->assertStatus(200);
        $tableResp->assertSee('EXH-VEH-003');
        $tableResp->assertSee('Toyota Land Cruiser V8');
        $tableResp->assertSee('Samuel Osei');
    }

    #[Test]
    public function suspect_image_is_rendered_in_the_exhibit_vault_table()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/009',
            'title' => 'Suspect Photo Test Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kofi Kingston',
            'mugshot_front' => 'uploads/mugshots/kofi_kingston.jpg',
            'status' => 'Under Investigation',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-PHOTO-009',
            'commodity_name' => 'Diamond Necklace',
            'exhibit_category' => 'Currency & Valuables',
        ]);

        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault?view=table');
        $resp->assertStatus(200);
        $resp->assertSee('EXH-PHOTO-009');
        $resp->assertSee('Kofi Kingston');
        // Assert suspect image is present in table view
        $resp->assertSee('uploads/mugshots/kofi_kingston.jpg');
        $resp->assertSee('alt="Kofi Kingston Photo"', false);
    }

    #[Test]
    public function inspect_modal_has_increased_dimensions_and_shows_full_exhibit_details_with_photos()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/010',
            'title' => 'High-Profile Seizure',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Marcus Garvey',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-EXP-010',
            'commodity_name' => 'Armored Cash Carrier',
            'exhibit_category' => 'Vehicles & Transport',
            'evidence_photos' => ['/storage/evidence/van_front.jpg', '/storage/evidence/van_rear.jpg'],
        ]);

        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);

        // Verify enlarged container classes (width and height increased)
        $resp->assertSee('max-w-7xl xl:max-w-[94vw] 2xl:max-w-[96vw] w-full h-[96vh]', false);
        
        // Verify evidence photos gallery section
        $resp->assertSee('Exhibit Evidence Photographs &amp; Media Gallery', false);
        $resp->assertSee('id="modalPhotosGallery"', false);

        // Verify Seized Property Badge Specification Preview in the inspect container
        $resp->assertSee('Seized Property Badge Specification Preview');
        $resp->assertSee('id="inspectBadgeCanvas"', false);
        $resp->assertSee('id="inspectDownloadBadgeBtn"', false);
        $resp->assertDontSee('CENTRAL CUSTODY PROPERTY ID');

        // Verify full detailed sections
        $resp->assertSee('Seizure &amp; Custody Details', false);
        $resp->assertSee('id="modalCategorySpecsContainer"', false);
        $resp->assertSee('Associated Suspect(s) Profile', false);
        $resp->assertSee('id="modalSuspectsCountBadge"', false);
        $resp->assertSee('Custodial Status', false);
        $resp->assertSee('Ghana Card (NIA)', false);
        $resp->assertSee('Nationality', false);
        $resp->assertSee('Remarks &amp; Custody Notes', false);
        $resp->assertSee('id="imageLightboxModal"', false);
    }

    #[Test]
    public function officer_can_update_exhibit_qr_property_id_drug_classification_and_exhibit_type()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/088',
            'title' => 'Cargo Inspection Seizure',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Emmanuel Darko',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-DRUG-088',
            'commodity_name' => 'Suspected White Powder',
            'exhibit_category' => 'Drugs',
            'seizure_weight' => '12.0',
            'seizure_unit' => 'kg',
        ]);

        $dummyPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($officer)->postJson("/Evidence_Management/exhibits/{$exhibit->id}/update", [
            'property_id' => 'NACOC/EMU/54321',
            'tag_image' => $dummyPng,
            'drug_classification' => 'Schedule II (High abuse, severe dependence - Cocaine, Fentanyl, Meth)',
            'exhibit_type' => 'Core Representative Sample',
            'commodity_name' => 'Suspected Pure Cocaine Hydrochloride',
            'exhibit_remark' => 'Official Seized Property Tag assigned and lodged into Vault Locker 4-B',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $exhibit->refresh();
        $this->assertEquals('NACOC/EMU/54321', $exhibit->property_id);
        $this->assertEquals('Schedule II (High abuse, severe dependence - Cocaine, Fentanyl, Meth)', $exhibit->drug_classification);
        $this->assertEquals('Core Representative Sample', $exhibit->exhibit_type);
        $this->assertEquals('Suspected Pure Cocaine Hydrochloride', $exhibit->commodity_name);
        $this->assertEquals('Official Seized Property Tag assigned and lodged into Vault Locker 4-B', $exhibit->exhibit_remark);
        $this->assertNotEmpty($exhibit->tag_url);
    }

    #[Test]
    public function classify_button_is_hidden_when_exhibit_already_has_both_drug_classification_and_exhibit_type()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/099',
            'title' => 'Vault Classify Visibility Test',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kweku Ananse',
        ]);

        // Exhibit without classification or type
        $unclassifiedExhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-UNCLS-001',
            'commodity_name' => 'Raw Brown Substance',
            'exhibit_category' => 'Drugs',
            'drug_classification' => null,
            'exhibit_type' => null,
        ]);

        // Exhibit with both drug classification and exhibit type
        $classifiedExhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EXH-CLS-002',
            'commodity_name' => 'Refined White Powder',
            'exhibit_category' => 'Drugs',
            'drug_classification' => 'Class A',
            'exhibit_type' => 'Drug Exhibit',
        ]);

        // 1. In Grid view
        $gridResp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $gridResp->assertStatus(200);

        // Unclassified exhibit card should render the Classify button
        $gridResp->assertSee('EXH-UNCLS-001');
        $gridResp->assertSee('<span>Classify</span>', false);

        // In Table view
        $tableResp = $this->actingAs($officer)->get('/Evidence_Management/vault?view=table');
        $tableResp->assertStatus(200);
        $tableResp->assertSee('EXH-CLS-002');
        $tableResp->assertSee('Class A');
        $tableResp->assertSee('Drug Exhibit');
    }

    #[Test]
    public function exhibits_entered_by_other_units_in_cases_are_synchronized_and_shown_in_vault()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        // Case and exhibit created by Crime Scene Support
        $cssCase = MultipleSuspectCase::create([
            'case_number' => 'NACOC/CSS/2026/099',
            'title' => 'Crime Scene Clandestine Lab Raid',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
        ]);

        $cssExhibit = Exhibit::create([
            'multiple_suspect_case_id' => $cssCase->id,
            'exhibit_number' => 'CSS-SEIZ-001',
            'commodity_name' => 'Precursor Chemical Ephedrine',
            'exhibit_category' => 'Drugs',
            'seizure_weight' => '50.0',
            'seizure_unit' => 'kg',
            'seizure_packaging' => 'Steel Drum',
            'seizure_location' => 'Tema Harbour Warehouse 7',
            'seizure_officer' => 'Detective Asare',
            'seizure_date' => '2026-11-01',
        ]);

        // When Evidence Management accesses the Vault, the other unit's exhibit is displayed
        $response = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $response->assertStatus(200);
        $response->assertSee('Precursor Chemical Ephedrine');
        $response->assertSee('NACOC/CSS/2026/099');

        // Officer assigns QR tag image, classifies drug, and sets exhibit type
        $dummyPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $updateResp = $this->actingAs($officer)->postJson("/Evidence_Management/exhibits/{$cssExhibit->id}/update", [
            'property_id' => 'NACOC/EMU/77112',
            'tag_image' => $dummyPng,
            'drug_classification' => 'Precursor Chemical (Table I / Table II)',
            'exhibit_type' => 'Bulk Consignment (Primary Mass)',
            'commodity_name' => 'Ephedrine Hydrochloride Crystal',
            'exhibit_remark' => 'Transferred from CSS to Central Vault Safekeeping',
        ]);

        $updateResp->assertStatus(200);
        $updateResp->assertJson(['success' => true]);

        $cssExhibit->refresh();
        $this->assertEquals('NACOC/EMU/77112', $cssExhibit->property_id);
        $this->assertEquals('Precursor Chemical (Table I / Table II)', $cssExhibit->drug_classification);
        $this->assertEquals('Bulk Consignment (Primary Mass)', $cssExhibit->exhibit_type);
        $this->assertEquals('Ephedrine Hydrochloride Crystal', $cssExhibit->commodity_name);
        $this->assertNotEmpty($cssExhibit->tag_url);
    }

    #[Test]
    public function vault_renders_locked_seized_property_badge_elements_when_already_generated()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/101',
            'title' => 'Vault QR Lock Test Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Kweku Mensah',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-SEAL-001',
            'commodity_name' => 'Sealed Methamphetamine',
            'exhibit_category' => 'Drugs',
            'property_id' => 'NACOC/EMU/88412',
            'tag_url' => 'storage/exhibits/tags/tag_88412.png',
        ]);

        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);

        // Verify Official Seized Property Tag modal elements exist in the page
        $resp->assertSee('Official Seized Property Tag');
        $resp->assertSee('vaultQrPropertyId');
        $resp->assertSee('vaultQrRandomizeBtn');
        $resp->assertSee('vaultQrLockedAlert');
        $resp->assertSee('vaultQrLockedBadgePill');
        $resp->assertSee('Officially Tagged &amp; Sealed', false);
        $resp->assertSee('Badge Already Assigned &amp; Locked', false);
        $resp->assertSee('Live Seized Property Badge Specification Preview');

        // Verify the exhibit's tag_url and property_id are present in the JSON exhibit payload on page
        $resp->assertSee('NACOC/EMU/88412');
        $resp->assertSee('tag_88412.png');
    }

    #[Test]
    public function exhibit_receives_permanent_qr_token_and_qr_url_on_creation()
    {
        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/201',
            'title'        => 'Permanent QR Token Test',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Akwasi Boakye',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-QR-001',
            'commodity_name'         => 'Heroin Brick',
            'exhibit_category'       => 'Drugs',
            'property_id'            => 'NACOC/EMU/91234',
        ]);

        $this->assertNotEmpty($exhibit->qr_token);
        $this->assertEquals(36, strlen($exhibit->qr_token)); // UUID format length
        $this->assertStringContainsString('/exhibits/tag/' . $exhibit->qr_token, $exhibit->qr_url);
        $this->assertEquals('Seized', $exhibit->custodial_status);
    }

    #[Test]
    public function scanning_permanent_qr_url_displays_live_exhibit_information_page_with_latest_database_status()
    {
        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/202',
            'title'        => 'Live Scan Page Verification',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Kofi Amoah',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-LIVE-001',
            'commodity_name'         => 'High Grade Ephedrine',
            'exhibit_category'       => 'Drugs & Narcotics',
            'property_id'            => 'NACOC/EMU/54321',
            'custodial_status'       => 'Seized',
            'seizure_weight'         => '10.5',
            'seizure_unit'           => 'kg',
        ]);

        // Scanning the permanent QR URL redirects safely to the central vault or returns structured JSON
        $scanUrl = '/exhibits/tag/' . $exhibit->qr_token;
        $response = $this->get($scanUrl);

        $response->assertStatus(302);
        $response->assertRedirect('/Evidence_Management/vault?search=' . urlencode('NACOC/EMU/54321'));

        // Test JSON endpoint for scanners/tablets
        $jsonResp = $this->getJson($scanUrl . '?format=json');
        $jsonResp->assertStatus(200);
        $jsonResp->assertJson([
            'success' => true,
            'exhibit' => [
                'property_id'      => 'NACOC/EMU/54321',
                'custodial_status' => 'Seized',
                'commodity_name'   => 'High Grade Ephedrine',
            ]
        ]);
    }

    #[Test]
    public function default_custodial_status_is_always_seized_and_user_facing_controls_are_removed()
    {
        $officer = User::factory()->create([
            'unit'        => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/203',
            'title'        => 'Default Seized Custody Test',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Emmanuel Osei',
        ]);

        // Exhibit registered without explicit status
        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-SEIZED-001',
            'commodity_name'         => 'Confiscated Diamonds',
            'exhibit_category'       => 'Valuables',
            'property_id'            => 'NACOC/EMU/33445',
        ]);

        // 1. Must default to 'Seized'
        $this->assertEquals('Seized', $exhibit->custodial_status);

        $scanUrl = '/exhibits/tag/' . $exhibit->qr_token;
        $resp1 = $this->get($scanUrl);
        $resp1->assertStatus(302);
        $resp1->assertRedirect('/Evidence_Management/vault?search=' . urlencode('NACOC/EMU/33445'));

        // 2. Vault page must NOT have interactive custodial status dropdown
        $vaultResp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $vaultResp->assertStatus(200);
        $vaultResp->assertDontSee('id="inspectQuickStatusSelect"', false);

        // 3. Attempting to update or confirm status sets it to 'Seized'
        $updateResp = $this->actingAs($officer)->postJson("/Evidence_Management/exhibits/{$exhibit->id}/custodial-status", [
            'status_remark' => 'Inspected in central evidence vault',
        ]);

        $updateResp->assertStatus(200);
        $updateResp->assertJson([
            'success'          => true,
            'custodial_status' => 'Seized',
        ]);

        $exhibit->refresh();
        $this->assertEquals('Seized', $exhibit->custodial_status);

        // Scanning always redirects to the central vault
        $resp2 = $this->get($scanUrl);
        $resp2->assertStatus(302);
        $resp2->assertRedirect('/Evidence_Management/vault?search=' . urlencode('NACOC/EMU/33445'));
    }

    #[Test]
    public function unauthenticated_or_unauthorized_users_cannot_modify_custodial_status()
    {
        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/204',
            'title'        => 'Security Access Control Test',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Yaw Mensah',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-SEC-001',
            'commodity_name'         => 'Pure Gold Dust',
            'exhibit_category'       => 'Precious Minerals',
            'property_id'            => 'NACOC/EMU/77889',
            'custodial_status'       => 'Seized',
        ]);

        // Unauthenticated visitor scanning tag attempts to tamper with custodial status
        $tamperResp = $this->postJson("/Evidence_Management/exhibits/{$exhibit->id}/custodial-status", [
            'custodial_status' => 'Released',
        ]);

        // Unauthenticated request must be rejected (302 redirect to login or 401 Unauthorized)
        $this->assertTrue(in_array($tamperResp->status(), [302, 401]));

        $exhibit->refresh();
        $this->assertEquals('Seized', $exhibit->custodial_status);
    }

    #[Test]
    public function inspect_container_renders_badge_preview_and_unassigned_qr_state_structures()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);

        // Preview card and unassigned card structures must exist for dynamic toggling
        $resp->assertSee('id="inspectBadgePreviewCard"', false);
        $resp->assertSee('id="inspectBadgeUnassignedCard"', false);
        $resp->assertSee('Tag Not Assigned');
        $resp->assertSee('id="inspectUnassignedAssignQrBtn"', false);
        $resp->assertDontSee('Provisional (Seized)');

        // Custodial status user dropdowns must be removed from vault modal and inspect container
        $resp->assertDontSee('<select id="vaultQrStatus"', false);
        $resp->assertSee('<input type="hidden" id="vaultQrStatus" value="Seized">', false);
        $resp->assertDontSee('id="inspectQuickStatusSelect"', false);
    }

    #[Test]
    public function badge_count_only_displays_exhibits_that_have_not_been_assigned_qr_code_or_classified()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/BDG1',
            'title' => 'Badge Count Test',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Badge Suspect',
        ]);

        // 1. Fully completed exhibit (has QR code AND exhibit type AND drug classification)
        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number'         => 'EXH-DONE-001',
            'commodity_name'         => 'Completed Drug Consignment',
            'exhibit_category'       => 'Drugs',
            'property_id'            => 'NACOC/EMU/11111',
            'tag_url'                => 'storage/exhibits/tags/tag_1.png',
            'exhibit_type'           => 'Drug Exhibit',
            'drug_classification'    => 'Class A',
        ]);

        // When only fully completed exhibit exists, pending badge count is 0
        $this->assertEquals(0, Exhibit::countPendingQrOrClassification());

        // 2. Exhibit missing QR code (has no property_id and no tag_url)
        $noQr = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number'         => 'EXH-NOQR-002',
            'commodity_name'         => 'Unassigned QR Exhibit',
            'exhibit_category'       => 'Electronic Devices',
            'exhibit_type'           => 'Mobile Phone',
            'property_id'            => null,
            'tag_url'                => null,
        ]);

        // Badge count should now be 1
        $this->assertEquals(1, Exhibit::countPendingQrOrClassification());

        // 3. Exhibit missing classification (missing exhibit_type)
        $unclassified = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number'         => 'EXH-UNCLS-003',
            'commodity_name'         => 'Unclassified Weapon',
            'exhibit_category'       => 'Weapons',
            'property_id'            => 'NACOC/EMU/22222',
            'tag_url'                => 'storage/exhibits/tags/tag_2.png',
            'exhibit_type'           => null,
        ]);

        // Badge count should now be 2 (no QR exhibit + unclassified exhibit)
        $this->assertEquals(2, Exhibit::countPendingQrOrClassification());

        // 4. Check that sidebar renders this badge count (2)
        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $resp->assertStatus(200);
        $resp->assertSee('2 exhibit(s) pending QR code or classification');
    }

    #[Test]
    public function exhibits_needing_qr_code_or_classification_are_placed_on_top()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/ORDER1',
            'title' => 'Vault Ordering Priority Test',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Order Suspect',
        ]);

        // Fully completed exhibit created first
        $completedExhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number'         => 'EXH-COMPLETED-999',
            'commodity_name'         => 'Fully Processed Diamond',
            'exhibit_category'       => 'General',
            'property_id'            => 'NACOC/EMU/99999',
            'tag_url'                => 'storage/exhibits/tags/tag_999.png',
            'exhibit_type'           => 'Valuables',
            'created_at'             => now()->subDays(1),
        ]);

        // Exhibit needing QR created with older date
        $pendingExhibit = Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number'         => 'EXH-PENDING-001',
            'commodity_name'         => 'Pending Intake Sachet',
            'exhibit_category'       => 'Drugs',
            'property_id'            => null,
            'tag_url'                => null,
            'exhibit_type'           => null,
            'drug_classification'    => null,
            'created_at'             => now()->subDays(5),
        ]);

        $resp = $this->actingAs($officer)->get('/Evidence_Management/vault?view=table');
        $resp->assertStatus(200);

        // Assert pending exhibit appears on top (before completed exhibit) in the response HTML
        $content = $resp->getContent();
        $posPending = strpos($content, 'EXH-PENDING-001');
        $posCompleted = strpos($content, 'EXH-COMPLETED-999');

        $this->assertNotFalse($posPending, 'Pending exhibit not found on page');
        $this->assertNotFalse($posCompleted, 'Completed exhibit not found on page');
        $this->assertLessThan($posCompleted, $posPending, 'Pending exhibit should appear ON TOP before completed exhibit');
    }

    #[Test]
    public function vault_action_status_filter_filters_unassigned_and_unclassified_exhibits()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NACOC/EMU/2026/FLT1',
            'title' => 'Filter Test Case',
            'unit_name' => 'Evidence Management',
        ]);

        // Exhibit 1: No QR code, but classified
        $exNoQr = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-NOQR-TEST',
            'commodity_name'         => 'No QR Sample',
            'exhibit_category'       => 'General',
            'property_id'            => null,
            'tag_url'                => null,
            'exhibit_type'           => 'Standard Package',
        ]);

        // Exhibit 2: Has QR code, but unclassified
        $exUncls = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-UNCLS-TEST',
            'commodity_name'         => 'Unclassified Sample',
            'exhibit_category'       => 'Drugs',
            'property_id'            => 'NACOC/EMU/33333',
            'tag_url'                => 'storage/exhibits/tags/tag_3.png',
            'exhibit_type'           => null,
            'drug_classification'    => null,
        ]);

        // Exhibit 3: Fully completed (both QR and classification)
        $exDone = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-DONE-TEST',
            'commodity_name'         => 'Done Sample',
            'exhibit_category'       => 'General',
            'property_id'            => 'NACOC/EMU/44444',
            'tag_url'                => 'storage/exhibits/tags/tag_4.png',
            'exhibit_type'           => 'Standard Package',
        ]);

        // 1. Filter: pending (needs QR code or classification) -> should show Exhibit 1 & 2, but NOT 3
        $respPending = $this->actingAs($officer)->get('/Evidence_Management/vault?action_status=pending');
        $respPending->assertStatus(200);
        $respPending->assertSee('EXH-NOQR-TEST');
        $respPending->assertSee('EXH-UNCLS-TEST');
        $respPending->assertDontSee('EXH-DONE-TEST');

        // 2. Filter: unassigned_qr -> should show Exhibit 1, but NOT Exhibit 2 or 3
        $respNoQr = $this->actingAs($officer)->get('/Evidence_Management/vault?action_status=unassigned_qr');
        $respNoQr->assertStatus(200);
        $respNoQr->assertSee('EXH-NOQR-TEST');
        $respNoQr->assertDontSee('EXH-UNCLS-TEST');
        $respNoQr->assertDontSee('EXH-DONE-TEST');

        // 3. Filter: unclassified -> should show Exhibit 2, but NOT Exhibit 1 or 3
        $respUncls = $this->actingAs($officer)->get('/Evidence_Management/vault?action_status=unclassified');
        $respUncls->assertStatus(200);
        $respUncls->assertSee('EXH-UNCLS-TEST');
        $respUncls->assertDontSee('EXH-NOQR-TEST');
        $respUncls->assertDontSee('EXH-DONE-TEST');

        // 4. Filter: completed -> should show Exhibit 3, but NOT Exhibit 1 or 2
        $respCompleted = $this->actingAs($officer)->get('/Evidence_Management/vault?action_status=completed');
        $respCompleted->assertStatus(200);
        $respCompleted->assertSee('EXH-DONE-TEST');
        $respCompleted->assertDontSee('EXH-NOQR-TEST');
        $respCompleted->assertDontSee('EXH-UNCLS-TEST');
    }

    #[Test]
    public function vault_filters_work_for_property_id_category_and_suspects()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $caseWithSuspect = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/PROP1',
            'title'        => 'Property Search Test Case',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Akosua Serwaa',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $caseWithSuspect->id,
            'exhibit_number'         => 'EXH-SEARCH-99',
            'commodity_name'         => 'High Purity Heroin',
            'exhibit_category'       => 'Drugs & Narcotics',
            'property_id'            => 'NACOC/EMU/98765',
            'exhibit_type'           => 'Chemical Exhibit',
            'drug_classification'    => 'Schedule I',
        ]);

        // 1. Search by property_id
        $respSearch = $this->actingAs($officer)->get('/Evidence_Management/vault?search=98765');
        $respSearch->assertStatus(200);
        $respSearch->assertSee('EXH-SEARCH-99');
        $respSearch->assertSee('NACOC/EMU/98765');

        // 2. Filter by category=drug
        $respCat = $this->actingAs($officer)->get('/Evidence_Management/vault?category=drug');
        $respCat->assertStatus(200);
        $respCat->assertSee('EXH-SEARCH-99');

        // 3. Filter by suspect_filter=with_suspects
        $respSus = $this->actingAs($officer)->get('/Evidence_Management/vault?suspect_filter=with_suspects');
        $respSus->assertStatus(200);
        $respSus->assertSee('EXH-SEARCH-99');
        $respSus->assertSee('Akosua Serwaa');
    }

    #[Test]
    public function vault_ajax_request_returns_rendered_html_and_metrics_for_skeleton_loading()
    {
        $officer = User::factory()->create([
            'unit'        => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/AJAX1',
            'title'        => 'AJAX Skeleton Loading Test Case',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Kofi AJAX',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-AJAX-001',
            'commodity_name'         => 'Sample Narcotics Package',
            'exhibit_category'       => 'Drugs & Narcotics',
            'property_id'            => 'NACOC/EMU/AJAX01',
            'drug_classification'    => 'Class A',
            'exhibit_type'           => 'Powder',
        ]);

        // 1. Full page GET returns skeleton loader markup
        $pageResp = $this->actingAs($officer)->get('/Evidence_Management/vault');
        $pageResp->assertStatus(200);
        $pageResp->assertSee('id="vaultSkeletonContainer"', false);
        $pageResp->assertSee('id="vaultContentWrapper"', false);
        $pageResp->assertSee('id="skeletonGridView"', false);
        $pageResp->assertSee('id="skeletonTableView"', false);

        // 2. AJAX GET returns JSON with partial HTML and telemetry metrics
        $ajaxResp = $this->actingAs($officer)->getJson('/Evidence_Management/vault?category=drug', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $ajaxResp->assertStatus(200);
        $ajaxResp->assertJson([
            'success' => true,
        ]);
        $ajaxResp->assertJsonStructure([
            'success',
            'html',
            'metrics' => [
                'total',
                'pending',
                'unassigned_qr',
                'unclassified',
                'completed',
                'drugs',
            ],
        ]);

        $responseData = $ajaxResp->json();
        $this->assertStringContainsString('EXH-AJAX-001', $responseData['html']);
        $this->assertStringContainsString('Sample Narcotics Package', $responseData['html']);
        $this->assertGreaterThanOrEqual(1, $responseData['metrics']['total']);
        $this->assertGreaterThanOrEqual(1, $responseData['metrics']['drugs']);
    }

    #[Test]
    public function evidence_management_report_page_loads_with_filters_and_exhibits_ledger()
    {
        $officer = User::factory()->create([
            'unit'        => 'Evidence Management',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'  => 'NACOC/EMU/2026/REP1',
            'title'        => 'Report Page Test Case',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Kwame Report',
        ]);

        $drugExhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-REPORT-777',
            'commodity_name'         => 'High Grade Methamphetamine',
            'exhibit_category'       => 'Drugs & Narcotics',
            'property_id'            => 'NACOC/EMU/REP777',
            'drug_classification'    => 'Schedule II',
            'seizure_value'          => 45000.00,
            'exhibit_remark'         => 'High purity sample in heat-sealed vacuum pack',
        ]);

        $assetExhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-VEH-888',
            'commodity_name'         => 'Toyota Land Cruiser V8',
            'exhibit_category'       => 'Vehicles',
            'property_id'            => 'NACOC/EMU/VEH888',
            'veh_reg_no'             => 'GE-8888-26',
            'seizure_value'          => 280000.00,
        ]);

        $moneyExhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-MONEY-999',
            'commodity_name'         => 'Bundles of 200 Ghana Cedi Notes',
            'exhibit_category'       => 'Currency',
            'property_id'            => 'NACOC/EMU/CASH999',
            'seizure_reason'         => 'Suspected Narcotic Proceeds',
            'seizure_value'          => 50000.00,
        ]);

        // 1. Direct GET to /Evidence_Management/report
        $resp = $this->actingAs($officer)->get('/Evidence_Management/report');
        $resp->assertStatus(200);
        $resp->assertSee('Central Exhibits &amp; Seized Property Report', false);
        $resp->assertSee('EXH-REPORT-777');
        $resp->assertSee('High Grade Methamphetamine');
        $resp->assertSee('NACOC/EMU/REP777');
        $resp->assertSee('Kwame Report');

        // Verify newly added filter controls: Weekly, Monthly, Annual, General, From, To, Reset, Generate Filtered PDF Report
        $resp->assertSee('Weekly');
        $resp->assertSee('Monthly');
        $resp->assertSee('Annual');
        $resp->assertSee('General');
        $resp->assertSee('From:');
        $resp->assertSee('To:');
        $resp->assertSee('Reset');
        $resp->assertSee('Generate Filtered PDF Report');

        // Verify Weight / Quantity and Page Total Valuation footer were removed from the table
        $resp->assertDontSee('Weight / Quantity');
        $resp->assertDontSee('Page Total Valuation');

        // 2. Filter search on report page
        $filterResp = $this->actingAs($officer)->get('/Evidence_Management/report?search=REP777&category=drug&period=weekly');
        $filterResp->assertStatus(200);
        $filterResp->assertSee('EXH-REPORT-777');

        // 3. /Report redirect for Evidence Management unit
        $redirectResp = $this->actingAs($officer)->get('/Report?unit=Evidence+Management');
        $redirectResp->assertRedirect('/Evidence_Management/report');

        // 4. Test Dedicated PDF Master Dossier Generation with Cover Page & 3 Tables
        $pdfResp = $this->actingAs($officer)->get('/Evidence_Management/report_pdf?period=weekly');
        $pdfResp->assertStatus(200);
        
        // Cover Page Assertions (matching other units cover page)
        $pdfResp->assertSee('SITREP COVER', false);
        $pdfResp->assertSee('NARCOTICS CONTROL COMMISSION', false);
        $pdfResp->assertSee('EVIDENCE MANAGEMENT UNIT (EMU)', false);
        $pdfResp->assertSee('OFFICIAL FORENSIC SITUATION REPORT', false);
        $pdfResp->assertSee(now()->subDays(7)->format('d F Y') . ' – ' . now()->format('d F Y'), false);
        $pdfResp->assertDontSee('Weekly (Last 7 Days)');
        $pdfResp->assertSee('CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD', false);
        $pdfResp->assertSee('COMPILING UNIT:', false);
        $pdfResp->assertSee('VALIDATION AUTHORITY:', false);

        // Body Table 1: Drug Exhibits
        $pdfResp->assertSee('Drug exhibit Log', false);
        $pdfResp->assertSee('High Grade Methamphetamine');
        $pdfResp->assertSee('High purity sample in heat-sealed vacuum pack');
        $pdfResp->assertDontSee('Vault ID: NACOC/EMU/REP777');
        $pdfResp->assertSee('REGISTERED SEIZURE', false);

        // Body Table 2: Physical Assets (Non-drug exhibits)
        $pdfResp->assertSee('Physical Assets &amp; Non-Drug Seized Property Log', false);
        $pdfResp->assertSee('Date Of Seizure');
        $pdfResp->assertSee('Suspect(s)');
        $pdfResp->assertSee('Asset / Exhibit Type');
        $pdfResp->assertSee('Chassis Number');
        $pdfResp->assertSee('Registration Number');
        $pdfResp->assertSee('Toyota Land Cruiser V8');
        $pdfResp->assertSee('REGISTERED ASSETS', false);

        // Body Table 3: Money Received
        $pdfResp->assertSee('Seized Currency &amp; Money Received Register', false);
        $pdfResp->assertSee('Date');
        $pdfResp->assertSee('Suspect');
        $pdfResp->assertSee('Amount');
        $pdfResp->assertSee('Location');
        $pdfResp->assertSee('GH₵ 50,000.00');
        $pdfResp->assertSee('Bundles of 200 Ghana Cedi Notes');
        $pdfResp->assertSee('MONETARY FUNDS', false);

        // 3-Column Physical Signatories Block
        $pdfResp->assertSee('HEAD OF EVIDENCE MANAGEMENT UNIT', false);
        $pdfResp->assertSee('HEAD OF FORENSIC DEPARTMENT', false);
        $pdfResp->assertSee('DIRECTOR OF INTEL &amp; INVESTIGATION', false);

        // 5. Test report_pdf with custom from_date and to_date query parameters
        $customDatePdfResp = $this->actingAs($officer)->get('/Evidence_Management/report_pdf?period=weekly&from_date=2026-11-01&to_date=2026-11-02');
        $customDatePdfResp->assertStatus(200);
        $customDatePdfResp->assertSee('OFFICIAL FORENSIC SITUATION REPORT', false);
        $customDatePdfResp->assertSee('Period: 01 November 2026 – 02 November 2026', false);
    }
}


