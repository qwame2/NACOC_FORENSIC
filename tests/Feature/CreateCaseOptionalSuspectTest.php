<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCaseOptionalSuspectTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $unit): User
    {
        return User::factory()->create([
            'unit'        => $unit,
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function crime_scene_create_page_renders_optional_sections_1_to_3_toggle_checkbox(): void
    {
        $officer = $this->createOfficer('Crime Scene Support');

        $response = $this->actingAs($officer)->get('/Crime_Scene_Support/create');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('id="noSuspectToggleCheckbox"', $content);
        $this->assertStringContainsString('Seizure Without Suspect (Disable Sections 1 &ndash; 3)', $content);
        $this->assertStringContainsString('id="sections1To3Wrapper"', $content);
        $this->assertStringContainsString('toggleSections1To3Optional', $content);
        $this->assertStringContainsString('Custody / Ownership Status', $content);
        $this->assertStringContainsString('Unassigned (Vault Lodgement)', $content);
        $this->assertStringContainsString('NO SUSPECT', $content);
        $this->assertStringContainsString('seizure-ownership-unassigned', $content);
        $this->assertStringContainsString('syncSeizureOwnershipBoxes', $content);
    }

    #[Test]
    public function evidence_management_create_page_renders_optional_sections_1_to_3_toggle_checkbox(): void
    {
        $officer = $this->createOfficer('Evidence Management');

        $response = $this->actingAs($officer)->get('/Evidence_Management/create');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('id="noSuspectToggleCheckbox"', $content);
        $this->assertStringContainsString('Seizure Without Suspect (Disable Sections 1 &ndash; 3)', $content);
        $this->assertStringContainsString('id="sections1To3Wrapper"', $content);
        $this->assertStringContainsString('toggleSections1To3Optional', $content);
        $this->assertStringContainsString('Custody / Ownership Status', $content);
        $this->assertStringContainsString('Unassigned (Vault Lodgement)', $content);
        $this->assertStringContainsString('NO SUSPECT', $content);
        $this->assertStringContainsString('seizure-ownership-unassigned', $content);
        $this->assertStringContainsString('syncSeizureOwnershipBoxes', $content);
    }

    #[Test]
    public function evidence_management_add_exhibit_page_does_not_render_optional_toggle_and_keeps_seizures_only_mode(): void
    {
        $officer = $this->createOfficer('Evidence Management');

        $response = $this->actingAs($officer)->get('/Evidence_Management/add-exhibit');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('id="noSuspectToggleCheckbox"', $content);
        $this->assertStringContainsString('id="sections1To3Wrapper" class="hidden"', $content);
    }

    #[Test]
    public function crime_scene_officer_can_submit_seizure_without_suspect(): void
    {
        $officer = $this->createOfficer('Crime Scene Support');

        $payload = [
            'no_suspect'  => 1,
            'unit_name'   => 'Crime Scene Support',
            'title'       => '',
            'suspect_name'=> '',
            'has_seizures'=> true,
            'seizures'    => [
                [
                    'exhibit_category' => 'Drugs',
                    'seizure_name'     => 'Cannabis Sativa Parcels',
                    'commodity_name'   => 'Cannabis Sativa Parcels',
                    'seizure_quantity' => '50',
                    'seizure_weight'   => '24.5',
                    'seizure_unit'     => 'kg',
                    'seizure_location' => 'Tema Harbour Shed 4',
                    'seizure_date'     => '2026-11-10',
                    'seizure_officer'  => $officer->fullname,
                ],
            ],
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'suspects_count' => 0]);

        $caseNumber = $response->json('case.case_number');
        $this->assertNotEmpty($caseNumber);

        $case = SingleSuspectCase::where('case_number', $caseNumber)->first();
        $this->assertNotNull($case);
        $this->assertSame('Crime Scene Support', $case->unit_name);
        $this->assertSame('Unassigned (No Suspect)', $case->suspect_name);
        $this->assertSame('Seized Exhibit: Cannabis Sativa Parcels (Awaiting Suspect)', $case->title);

        $exhibit = Exhibit::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertNull($exhibit->suspect_id);
        $this->assertSame('Unassigned (No Suspect)', $exhibit->associated_person);
        $this->assertSame('Cannabis Sativa Parcels', $exhibit->commodity_name);

        // Verify the created dossier renders cleanly on review page
        $reviewResponse = $this->actingAs($officer)->get('/cases/' . urlencode($case->case_number) . '/review');
        $reviewResponse->assertStatus(200);
    }

    #[Test]
    public function evidence_management_officer_can_submit_multiple_sequential_seizures_without_suspect_without_duplicate_blocks(): void
    {
        $officer = $this->createOfficer('Evidence Management');

        $firstResponse = $this->actingAs($officer)->postJson('/cases', [
            'no_suspect'   => 1,
            'unit_name'    => 'Evidence Management',
            'title'        => 'Republic vs',
            'suspect_name' => '',
            'has_seizures' => true,
            'seizures'     => [
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name'   => 'Cocaine Bricks',
                    'seizure_quantity' => '4',
                    'seizure_weight'   => '4.2',
                    'seizure_unit'     => 'kg',
                    'seizure_location' => 'Kotoka Air Cargo Shed',
                ],
            ],
        ]);

        $firstResponse->assertStatus(200);
        $firstCaseNumber = $firstResponse->json('case.case_number');

        $secondResponse = $this->actingAs($officer)->postJson('/cases', [
            'no_suspect'   => 1,
            'unit_name'    => 'Evidence Management',
            'title'        => '',
            'suspect_name' => '',
            'has_seizures' => true,
            'seizures'     => [
                [
                    'exhibit_category' => 'Money',
                    'commodity_name'   => 'USD Currency Bundle',
                    'seizure_quantity' => '15000',
                    'amount'           => '15000',
                    'seizure_location' => 'Takoradi Port Inspection Bay',
                ],
            ],
        ]);

        $secondResponse->assertStatus(200);
        $secondCaseNumber = $secondResponse->json('case.case_number');

        $this->assertNotSame($firstCaseNumber, $secondCaseNumber);
        $this->assertDatabaseHas('single_suspect_cases', [
            'case_number'  => $secondCaseNumber,
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Unassigned (No Suspect)',
            'title'        => 'Seized Exhibit: USD Currency Bundle (Awaiting Suspect)',
        ]);
    }

    #[Test]
    public function submitting_without_suspect_fails_validation_when_no_seizure_is_provided(): void
    {
        $officer = $this->createOfficer('Crime Scene Support');

        $response = $this->actingAs($officer)->postJson('/cases', [
            'no_suspect'   => 1,
            'unit_name'    => 'Crime Scene Support',
            'title'        => '',
            'suspect_name' => '',
            'seizures'     => [],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function normal_submission_without_no_suspect_still_requires_title_and_suspect_name(): void
    {
        $officer = $this->createOfficer('Crime Scene Support');

        $response = $this->actingAs($officer)->postJson('/cases', [
            'no_suspect'   => 0,
            'unit_name'    => 'Crime Scene Support',
            'title'        => '',
            'suspect_name' => '',
            'seizures'     => [
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name'   => 'Tramadol',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'suspect_name']);
    }
}
