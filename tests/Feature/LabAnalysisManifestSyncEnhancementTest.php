<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabAnalysisManifestSyncEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function createLabOfficer(bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => 'Lab Analyst',
            'unit' => 'Laboratory Analysis (LAU)',
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function lab_analysis_create_page_renders_manifest_and_section5_sync_elements(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Manifest Enhancements
        $this->assertStringContainsString('Packaging as Received / Commodity Name', $html, 'Expected "Packaging as Received / Commodity Name" label.');
        $this->assertStringContainsString('Contents (Visual)', $html, 'Expected "Contents (Visual)" label.');
        $this->assertStringContainsString('id="newSeizureContents"', $html, 'Expected "newSeizureContents" input id.');
        $this->assertStringContainsString('id="manifestDescriptionOfExhibits"', $html, 'Expected "manifestDescriptionOfExhibits" textarea id.');
        $this->assertStringContainsString('Description of Exhibit(s)', $html, 'Expected "Description of Exhibit(s)" heading.');

        // 2. Section 5 Dynamic Containers
        $this->assertStringContainsString('id="labExhibitCardsContainer"', $html, 'Expected "labExhibitCardsContainer" element.');
        $this->assertStringContainsString('id="presumptiveTestExhibitsContainer"', $html, 'Expected "presumptiveTestExhibitsContainer" element.');
        $this->assertStringContainsString('id="confirmatoryTestExhibitsContainer"', $html, 'Expected "confirmatoryTestExhibitsContainer" element.');

        // 3. Shared Confirmatory Test Info (Positioned outside the Confirmatory Test container section)
        $this->assertStringContainsString('Shared Confirmatory Test Information', $html, 'Expected shared confirmatory section.');
        $this->assertStringContainsString('id="confirmatoryTestMethods"', $html, 'Expected shared test methods input.');
        $this->assertStringContainsString('id="confirmatorySpecifications"', $html, 'Expected shared specifications input.');
        $this->assertStringContainsString('id="confirmatoryRemark"', $html, 'Expected shared remark input.');

        $confirmatoryPos = strpos($html, 'Confirmatory Test (Laboratory Verification)');
        $sharedConfPos = strpos($html, 'Shared Confirmatory Test Information');
        $confContainerPos = strpos($html, 'id="confirmatoryTestExhibitsContainer"');
        $this->assertNotFalse($confirmatoryPos);
        $this->assertNotFalse($sharedConfPos);
        $this->assertNotFalse($confContainerPos);
        $this->assertGreaterThan($confirmatoryPos, $sharedConfPos, 'Shared Confirmatory section should follow Confirmatory Test section.');
        $this->assertGreaterThan($confContainerPos, $sharedConfPos, 'Shared Confirmatory section should be outside and after confirmatoryTestExhibitsContainer.');

        // 4. JS functions
        $this->assertStringContainsString('function syncSection4ExhibitsToLabSection', $html);
        $this->assertStringContainsString('function selectLabExhibitCard', $html);
        $this->assertStringContainsString('function syncPresumptiveContainerForExhibit', $html);
        $this->assertStringContainsString('function syncConfirmatoryContainerForExhibit', $html);
        $this->assertStringContainsString('function addPresumptiveSpecimenRow', $html);
        $this->assertStringContainsString('function addConfirmatoryDetectedSubstanceRow', $html);
        $this->assertStringContainsString('function updateConfirmatorySampleReserved', $html);
    }

    #[Test]
    public function lab_analysis_show_review_page_renders_manifest_and_shared_description(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-REV01',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'status' => 'Pending Approval',
            'description_of_exhibits' => 'Two sealed brown packages with tamper proof seals',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kwame Mensah', 'association_role' => 'Primary Suspect']
                ],
                'seizures' => [
                    [
                        'name' => 'Cocaine Powder',
                        'category' => 'Drugs',
                        'quantity' => '2',
                        'packaging' => 'Slabs',
                        'weight' => '2.5',
                        'unit' => 'kg',
                        'contents' => 'White compact crystalline powder',
                        'description_of_exhibits' => 'Two sealed brown packages with tamper proof seals'
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Laboratory+Analysis+(LAU)");
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Packaging as Received / Commodity Name', $html);
        $this->assertStringContainsString('Contents (Visual)', $html);
        $this->assertStringContainsString('id="newSeizureContents"', $html);
        $this->assertStringContainsString('id="manifestDescriptionOfExhibits"', $html);
        $this->assertStringContainsString('Description of Exhibit(s)', $html);
    }

    #[Test]
    public function case_creation_saves_contents_and_description_of_exhibits_in_db(): void
    {
        $officer = $this->createLabOfficer();

        $payload = [
            'case_number' => 'NFC-2026-STORE-EX01',
            'title' => 'Republic vs Test Suspect',
            'suspect_name' => 'Test Suspect',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'unit' => 'Laboratory Analysis (LAU)',
            'arresting_officer' => 'Officer Test',
            'submitting_officer' => 'Officer Test',
            'raid_location' => 'Airport Terminal',
            'offense_description' => 'Suspected narcotics trafficking',
            'arrest_date' => now()->toDateString(),
            'description_of_exhibits' => 'Shared consignment description: 3 cartons of sealed packages',
            'suspects' => [
                [
                    'suspect_name' => 'Test Suspect',
                    'role' => 'Primary Suspect',
                ]
            ],
            'seizures' => [
                [
                    'seizure_name' => 'Heroin (Brown Sugar)',
                    'commodity_name' => 'Heroin (Brown Sugar)',
                    'category' => 'Drugs',
                    'quantity' => '10',
                    'packaging' => 'Ziploc Bags',
                    'weight' => '500',
                    'unit' => 'g',
                    'contents' => 'Brown granules inside clear ziploc bags labelled A1-A10',
                    'associated_person' => 'Test Suspect',
                ],
                [
                    'seizure_name' => 'Cocaine (High Purity Slabs)',
                    'commodity_name' => 'Cocaine (High Purity Slabs)',
                    'category' => 'Drugs',
                    'quantity' => '5',
                    'packaging' => 'Brick Wraps',
                    'weight' => '5',
                    'unit' => 'kg',
                    'contents' => 'White pressed bricks with scorpion insignia stamp',
                    'associated_person' => 'Test Suspect',
                ]
            ]
        ];

        $response = $this->actingAs($officer)
            ->postJson('/cases', $payload);

        $response->assertStatus(200);

        // Check single suspect case table has description_of_exhibits
        $this->assertDatabaseHas('single_suspect_cases', [
            'case_number' => 'NFC-2026-STORE-EX01',
            'description_of_exhibits' => 'Shared consignment description: 3 cartons of sealed packages',
        ]);

        // Check exhibits have contents and description_of_exhibits
        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Heroin (Brown Sugar)',
            'contents' => 'Brown granules inside clear ziploc bags labelled A1-A10',
            'description_of_exhibits' => 'Shared consignment description: 3 cartons of sealed packages',
        ]);

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Cocaine (High Purity Slabs)',
            'contents' => 'White pressed bricks with scorpion insignia stamp',
            'description_of_exhibits' => 'Shared consignment description: 3 cartons of sealed packages',
        ]);
    }

    #[Test]
    public function associate_exhibits_endpoint_persists_contents_and_description(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-ASSOC-EX01',
            'title' => 'Republic vs Associate Suspect',
            'suspect_name' => 'Associate Suspect',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Associate Suspect', 'role' => 'Primary Suspect']
                ],
                'seizures' => []
            ]
        ]);

        $payload = [
            'description_of_exhibits' => 'Consignment examined under chain of custody ref #9988',
            'associated_exhibits' => [
                [
                    'seizure_name' => 'Methamphetamine Crystals',
                    'category' => 'Drugs',
                    'quantity' => '1',
                    'packaging' => 'Sealed Pouch',
                    'weight' => '1.2',
                    'unit' => 'kg',
                    'contents' => 'Translucent crystalline rocks',
                    'associated_person' => 'Associate Suspect',
                    'description_of_exhibits' => 'Consignment examined under chain of custody ref #9988'
                ]
            ]
        ];

        $response = $this->actingAs($officer)
            ->postJson("/cases/{$case->id}/associate", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Methamphetamine Crystals',
            'contents' => 'Translucent crystalline rocks',
            'description_of_exhibits' => 'Consignment examined under chain of custody ref #9988',
        ]);

        $this->assertDatabaseHas('single_suspect_cases', [
            'case_number' => 'NFC-2026-ASSOC-EX01',
            'description_of_exhibits' => 'Consignment examined under chain of custody ref #9988',
        ]);
    }
}
