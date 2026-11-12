<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnassignedExhibitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $unit = 'Evidence Management'): User
    {
        return User::factory()->create([
            'unit'        => $unit,
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function vault_page_add_exhibit_navbar_button_links_to_dedicated_add_exhibit_page()
    {
        $officer = $this->createOfficer('Evidence Management');

        $response = $this->actingAs($officer)->get('/Evidence_Management/vault');

        $response->assertStatus(200);
        $response->assertSee('/Evidence_Management/add-exhibit');
    }

    #[Test]
    public function dedicated_add_exhibit_page_renders_only_section_4_and_hides_sections_1_to_3()
    {
        $officer = $this->createOfficer('Evidence Management');

        $response = $this->actingAs($officer)->get('/Evidence_Management/add-exhibit');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must display Section 4: Seizures (Commodities) heading and Save Exhibit button
        $this->assertStringContainsString('Section 4: Seizures (Commodities)', $content);
        $this->assertStringContainsString('Save Exhibit', $content);

        // Sections 1 to 3 wrapper must be hidden
        $this->assertStringContainsString('id="sections1To3Wrapper" class="hidden"', $content);

        // Does not show cargo parcel form
        $this->assertStringNotContainsString('Official Cargo &amp; Parcel Registration', $content);

        // Contains Section 4 commodity input fields and QR modal
        $this->assertStringContainsString('id="seizuresDetailsContainer"', $content);
        $this->assertStringContainsString('id="seizuresListContainer"', $content);
        $this->assertStringContainsString('id="exhibitSearchInput"', $content);
        $this->assertStringContainsString('id="newSeizureQuantity"', $content);
        $this->assertStringContainsString('id="newSeizureWeight"', $content);
        $this->assertStringContainsString('id="seizedPropertyQrDownloadModal"', $content);
        $this->assertStringContainsString('processAddExhibit', $content);
    }

    #[Test]
    public function store_unassigned_exhibits_saves_exhibit_without_suspect_to_vault()
    {
        $officer = $this->createOfficer('Evidence Management');

        $payload = [
            'seizures' => [
                [
                    'item_number'         => 1,
                    'property_id'         => 'NACOC/EMU/99101',
                    'exhibit_category'    => 'Drugs',
                    'commodity_name'      => 'Suspected Heroin Blocks',
                    'seizure_quantity'    => '10 Blocks',
                    'seizure_weight'      => '12.5',
                    'seizure_unit'        => 'kg',
                    'seizure_location'    => 'Tema Port Container Terminal 3',
                    'seizure_date'        => '2026-11-09',
                    'seizure_officer'     => $officer->fullname,
                    'exhibit_remark'      => 'Intercepted in shipping container without consignee present.',
                ]
            ],
            'evidence_photos' => []
        ];

        $response = $this->actingAs($officer)
            ->postJson('/Evidence_Management/exhibits/store-unassigned', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $caseId = $response->json('case_id');
        $caseNumber = $response->json('case_number');
        $this->assertNotEmpty($caseId);
        $this->assertNotEmpty($caseNumber);

        $this->assertDatabaseHas('single_suspect_cases', [
            'id'           => $caseId,
            'case_number'  => $caseNumber,
            'suspect_name' => 'Unassigned (No Suspect)',
        ]);

        $this->assertDatabaseHas('exhibits', [
            'property_id'              => 'NACOC/EMU/99101',
            'commodity_name'           => 'Suspected Heroin Blocks',
            'exhibit_category'         => 'Drugs',
            'single_suspect_case_id'   => $caseId,
            'multiple_suspect_case_id' => null,
            'suspect_id'               => null,
            'associated_person'        => 'Unassigned (No Suspect)',
            'custodial_status'         => 'Seized',
        ]);

        // Assert that the case and exhibit display in show_review page
        $reviewResponse = $this->actingAs($officer)->get("/Evidence_Management/cases/{$caseNumber}/review");
        $reviewResponse->assertStatus(200);
        $reviewContent = $reviewResponse->getContent();
        $this->assertStringContainsString('Suspected Heroin Blocks', $reviewContent);
        $this->assertStringContainsString('12.5', $reviewContent);
    }

    #[Test]
    public function get_unassigned_exhibits_api_returns_only_exhibits_without_suspects()
    {
        $officer = $this->createOfficer('Evidence Management');

        // Create an unassigned exhibit
        $unassigned = Exhibit::create([
            'property_id'      => 'NACOC/EMU/88111',
            'commodity_name'   => 'Unclaimed Cocaine Slab',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '5 Slabs',
            'seizure_weight'   => '5',
            'seizure_unit'     => 'kg',
        ]);

        // Create an exhibit with an associated person
        $assigned = Exhibit::create([
            'property_id'       => 'NACOC/EMU/77222',
            'commodity_name'    => 'Assigned Cannabis Sacks',
            'exhibit_category'  => 'Drugs',
            'associated_person' => 'Kofi Mensah',
        ]);

        $response = $this->actingAs($officer)->getJson('/api/exhibits/unassigned');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('exhibits');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($unassigned->id, $ids);
        $this->assertNotContains($assigned->id, $ids);
    }

    #[Test]
    public function all_units_create_pages_contain_unassigned_exhibits_select2_dropdown()
    {
        // 1. Evidence Management
        $emOfficer = $this->createOfficer('Evidence Management');
        $emResponse = $this->actingAs($emOfficer)->get('/Evidence_Management/create');
        $emResponse->assertStatus(200);
        $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $emResponse->getContent());
        $this->assertStringContainsString('Link Existing Seized Exhibit(s)', $emResponse->getContent());

        // 2. Crime Scene Support
        $cssOfficer = $this->createOfficer('Crime Scene Support');
        $cssResponse = $this->actingAs($cssOfficer)->get('/Crime_Scene_Support/head/CrimeSceneCreate');
        $cssResponse->assertStatus(200);
        $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $cssResponse->getContent());
        $this->assertStringContainsString('Link Existing Seized Exhibit(s)', $cssResponse->getContent());

        // 3. Digital Forensics
        $dpfOfficer = $this->createOfficer('Digital & Physical Forensics');
        $dpfResponse = $this->actingAs($dpfOfficer)->get('/Digital_Forensics/create');
        $dpfResponse->assertStatus(200);
        $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $dpfResponse->getContent());
        $this->assertStringContainsString('Link Existing Seized Exhibit(s)', $dpfResponse->getContent());

        // 4. Lab Analysis
        $labOfficer = $this->createOfficer('Lab Analysis');
        $labResponse = $this->actingAs($labOfficer)->get('/Lab_Analysis/create');
        $labResponse->assertStatus(200);
        $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $labResponse->getContent());
        $this->assertStringContainsString('Link Existing Seized Exhibit(s)', $labResponse->getContent());

        // 5. Cases general create
        $genResponse = $this->actingAs($cssOfficer)->get('/cases/create');
        $genResponse->assertStatus(200);
        $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $genResponse->getContent());
        $this->assertStringContainsString('Link Existing Seized Exhibit(s)', $genResponse->getContent());
    }

    #[Test]
    public function creating_case_with_linked_exhibit_binds_unassigned_exhibit_to_suspect()
    {
        $officer = $this->createOfficer('Evidence Management');

        // 1. Create an unassigned exhibit in the vault
        $unassignedExhibit = Exhibit::create([
            'property_id'              => 'NACOC/EMU/55333',
            'commodity_name'           => 'Confiscated Methamphetamine',
            'exhibit_category'         => 'Drugs',
            'seizure_quantity'         => '15 Packages',
            'seizure_weight'           => '7.5',
            'seizure_unit'             => 'kg',
            'seizure_location'         => 'Kotoka Airport Baggage Carousel',
            'custodial_status'         => 'Seized',
            'single_suspect_case_id'   => null,
            'multiple_suspect_case_id' => null,
            'suspect_id'               => null,
            'associated_person'        => null,
        ]);

        // 2. Later, suspect is arrested and officer creates case docket linking this exhibit
        $casePayload = [
            'case_number'         => 'NCC-TEST-LINK-001',
            'title'               => 'State v. Samuel Mensah - Narcotics Trafficking',
            'unit_name'           => 'Evidence Management',
            'suspect_name'        => 'Samuel Mensah',
            'suspect_alias'       => 'Sammy K',
            'ghana_card'          => 'GHA-99228811-0',
            'gender'              => 'Male',
            'dob'                 => '1990-05-15',
            'age'                 => 36,
            'arrest_date'         => '2026-11-09',
            'raid_location'       => 'Accra Central',
            'linked_exhibit_ids'  => [$unassignedExhibit->id],
            'docket_data'         => [
                'suspects' => [
                    ['suspect_name' => 'Samuel Mensah', 'ghana_card' => 'GHA-99228811-0']
                ],
                'seizures' => [],
            ]
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $casePayload);

        $response->assertStatus(200);

        // 3. Verify exhibit is now linked to this suspect and case
        $freshExhibit = $unassignedExhibit->fresh();
        $this->assertNotNull($freshExhibit->single_suspect_case_id);
        $this->assertEquals('Samuel Mensah', $freshExhibit->associated_person);
        $this->assertEquals('SINGLE', $freshExhibit->case_type);

        // Verify it no longer appears in unassigned API
        $unassignedCheck = $this->actingAs($officer)->getJson('/api/exhibits/unassigned');
        $unassignedIds = collect($unassignedCheck->json('exhibits'))->pluck('id')->all();
        $this->assertNotContains($freshExhibit->id, $unassignedIds);
    }

    #[Test]
    public function added_unassigned_exhibits_display_across_all_units_show_review_pages()
    {
        $emOfficer = $this->createOfficer('Evidence Management');
        $headOfficer = User::factory()->create([
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $payload = [
            'seizures' => [
                [
                    'item_number'         => 1,
                    'property_id'         => 'NACOC/EMU/54321',
                    'exhibit_category'    => 'Drugs',
                    'commodity_name'      => 'High Grade Cocaine Bricks',
                    'seizure_quantity'    => '20 Bricks',
                    'seizure_weight'      => '22.4',
                    'seizure_unit'        => 'kg',
                    'seizure_location'    => 'Kotoka International Airport Cargo Bay',
                    'seizure_date'        => '2026-11-09',
                    'seizure_officer'     => $emOfficer->fullname,
                    'exhibit_remark'      => 'Found concealed inside hollow machine parts.',
                ]
            ],
            'evidence_photos' => []
        ];

        $storeResponse = $this->actingAs($emOfficer)
            ->postJson('/Evidence_Management/exhibits/store-unassigned', $payload);

        $storeResponse->assertStatus(200);
        $caseNumber = $storeResponse->json('case_number');
        $this->assertNotEmpty($caseNumber);

        // 1. Evidence Management show_review
        $emReview = $this->actingAs($emOfficer)->get("/Evidence_Management/cases/{$caseNumber}/review");
        $emReview->assertStatus(200);
        $emContent = $emReview->getContent();
        $this->assertStringContainsString('High Grade Cocaine Bricks', $emContent);
        $this->assertStringContainsString('22.4', $emContent);

        // 2. Cases general show_review
        $generalReview = $this->actingAs($emOfficer)->get("/cases/{$caseNumber}/review");
        $generalReview->assertStatus(200);
        $generalContent = $generalReview->getContent();
        $this->assertStringContainsString('High Grade Cocaine Bricks', $generalContent);

        // 3. Directorate Head show_review
        $headReview = $this->actingAs($headOfficer)->get("/Forensic_Head/cases/{$caseNumber}/review");
        $headReview->assertStatus(200);
        $headContent = $headReview->getContent();
        $this->assertStringContainsString('High Grade Cocaine Bricks', $headContent);
    }

    #[Test]
    public function double_encoded_exhibit_photos_decode_cleanly_and_appear_in_other_unit_selector_and_api()
    {
        $emOfficer = $this->createOfficer('Evidence Management');
        $labOfficer = $this->createOfficer('Lab Analysis');

        $sampleDataUrl = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/';

        $singleCase = SingleSuspectCase::create([
            'case_number'  => 'NCC-PHOTO-TEST-001',
            'title'        => 'Unassigned Drug Exhibit With Legacy Photos',
            'unit_name'    => 'Evidence Management',
            'user_id'      => $emOfficer->id,
            'suspect_name' => 'Unknown',
            'status'       => 'Awaiting Suspect Assignment',
        ]);

        $exhibit = Exhibit::create([
            'case_type'              => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'property_id'            => 'NACOC/EMU/77889',
            'exhibit_category'       => 'Drugs',
            'commodity_name'         => 'Fentanyl Patches',
            'associated_person'      => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Evidence Management',
            'custodial_status'       => 'Seized',
        ]);

        // Simulate legacy double-encoded JSON stored directly in the exhibits table
        DB::table('exhibits')->where('id', $exhibit->id)->update([
            'evidence_photos' => json_encode(json_encode([$sampleDataUrl])),
        ]);

        $fresh = $exhibit->fresh();
        $this->assertIsArray($fresh->evidence_photos);
        $this->assertSame([$sampleDataUrl], $fresh->evidence_photos);

        $payload = $fresh->toManifestPayloadArray();
        $this->assertSame([$sampleDataUrl], $payload['evidence_photos']);

        // Unassigned exhibits API returns resolved photos for Lab Analysis
        $apiResponse = $this->actingAs($labOfficer)->getJson('/api/exhibits/unassigned?unit=Lab+Analysis');
        $apiResponse->assertStatus(200);
        $apiItem = collect($apiResponse->json('exhibits'))->firstWhere('id', $fresh->id);
        $this->assertNotNull($apiItem);
        $this->assertSame([$sampleDataUrl], $apiItem['evidence_photos']);

        // Other unit create page renders the exhibit with its photo in All Exhibits Without Suspect(s) and selector script
        $labCreate = $this->actingAs($labOfficer)->get('/Lab_Analysis/create');
        $labCreate->assertStatus(200);
        $html = $labCreate->getContent();
        $this->assertStringContainsString('NACOC/EMU/77889', $html);
        $this->assertStringContainsString($sampleDataUrl, $html);
        $this->assertStringContainsString('renderExhibitPhotoStrip', $html);
        $this->assertStringContainsString('flushPendingLinkedExhibitPhotos', $html);
    }

    #[Test]
    public function linking_unassigned_exhibit_preserves_evidence_photos_in_case_docket_seizures()
    {
        $emOfficer = $this->createOfficer('Evidence Management');
        $labOfficer = $this->createOfficer('Lab Analysis');

        $sampleDataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $storeResp = $this->actingAs($emOfficer)->postJson('/Evidence_Management/exhibits/store-unassigned', [
            'seizures' => [
                [
                    'item_number'      => 1,
                    'property_id'      => 'NACOC/EMU/88112',
                    'exhibit_category' => 'Drugs',
                    'commodity_name'   => 'Methamphetamine Crystal',
                    'seizure_quantity' => '2 Packs',
                ],
            ],
            'evidence_photos' => [$sampleDataUrl],
        ]);
        $storeResp->assertStatus(200);

        $exhibitId = (int) ($storeResp->json('exhibits.0.id') ?? Exhibit::where('property_id', 'NACOC/EMU/88112')->value('id'));
        $this->assertGreaterThan(0, $exhibitId);

        $caseResp = $this->actingAs($labOfficer)->postJson('/cases', [
            'case_number'        => 'NCC-LINK-PHOTO-002',
            'title'              => 'State v. Kwame Osei - Linked Photo Test',
            'unit_name'          => 'Lab Analysis',
            'suspect_name'       => 'Kwame Osei',
            'ghana_card'         => 'GHA-11223344-5',
            'gender'             => 'Male',
            'dob'                => '1991-04-10',
            'age'                => 35,
            'arrest_date'        => '2026-11-10',
            'raid_location'      => 'Kumasi',
            'linked_exhibit_ids' => [$exhibitId],
            'docket_data'        => [
                'suspects' => [
                    ['suspect_name' => 'Kwame Osei', 'ghana_card' => 'GHA-11223344-5'],
                ],
                'seizures' => [],
            ],
        ]);
        $caseResp->assertStatus(200);

        $linkedCase = ForensicCase::where('case_number', 'NCC-LINK-PHOTO-002')->first();
        $this->assertNotNull($linkedCase);
        $seizures = $linkedCase->docket_data['seizures'] ?? [];
        $this->assertNotEmpty($seizures);
        $this->assertSame([$sampleDataUrl], $seizures[0]['evidence_photos'] ?? []);
        $this->assertSame([$sampleDataUrl], Exhibit::find($exhibitId)?->evidence_photos);

        $reviewResp = $this->actingAs($labOfficer)->get('/cases/NCC-LINK-PHOTO-002/review');
        $reviewResp->assertStatus(200);
        $this->assertStringContainsString($sampleDataUrl, $reviewResp->getContent());
    }
}
