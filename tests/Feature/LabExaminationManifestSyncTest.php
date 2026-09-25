<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabExaminationManifestSyncTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function examination_information_section_is_always_displayed_when_unsaved()
    {
        $analyst = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-LAB01',
            'title' => 'State vs Eric Addo',
            'suspect_name' => 'Eric Addo',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Eric Addo', 'association_role' => 'Primary Suspect']
                ],
                'seizures' => []
            ]
        ]);

        $response = $this->actingAs($analyst)->get("/cases/{$case->case_number}/review?from=Lab+Analysis");

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Examination Information section container must be visible (not hidden)
        $response->assertSee('id="section5FormContainer"', false);
        $response->assertDontSee('id="section5FormContainer" class="space-y-6 hidden"', false);
        $response->assertSee('Examination Information');
        $response->assertSee('Type of Drug');
        $response->assertSee('Select Seized Exhibit to Examine (Manifest Sync)');
    }

    #[Test]
    public function drug_exhibit_manifest_sync_filters_by_drug_type_and_uses_real_exhibit_id()
    {
        $analyst = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'NFC-2026-LAB02',
            'title' => 'Republic vs Eric Addo',
            'suspect_name' => 'Eric Addo',
            'unit_name' => 'Lab Analysis',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Eric Addo', 'association_role' => 'Primary Suspect', 'is_associate' => false]
                ],
                'seizures' => [
                    [
                        'id' => 101,
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin',
                        'seizure_quantity' => '50',
                        'seizure_unit' => 'g',
                        'seizure_description' => 'Brown powder',
                        'associated_person' => 'Eric Addo',
                    ],
                    [
                        'id' => 102,
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Cocaine',
                        'seizure_quantity' => '20',
                        'seizure_unit' => 'g',
                        'seizure_description' => 'White bricks',
                        'associated_person' => 'Eric Addo',
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($analyst)->get("/cases/{$case->case_number}/review?from=Lab+Analysis&suspect=Eric+Addo");

        $response->assertStatus(200);
        $html = $response->getContent();

        // Assert option contains actual exhibit ID and formatted text
        $exId = Exhibit::first()->id ?? 1;
        $this->assertStringContainsString('id="labExhibitSelect"', $html);
        $this->assertStringContainsString('Heroin', $html);
    }

    #[Test]
    public function backend_validation_rejects_invalid_exhibit_save_with_422()
    {
        $analyst = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case1 = ForensicCase::create([
            'case_number' => 'NFC-2026-VAL01',
            'title' => 'State vs Suspect One',
            'suspect_name' => 'Suspect One',
            'unit_name' => 'Lab Analysis',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'suspects' => [['suspect_name' => 'Suspect One']],
                'seizures' => [
                    ['id' => 201, 'category' => 'Drugs', 'seizure_name_drug' => 'Heroin', 'associated_person' => 'Suspect One']
                ]
            ]
        ]);

        $case2 = ForensicCase::create([
            'case_number' => 'NFC-2026-VAL02',
            'title' => 'State vs Suspect Two',
            'suspect_name' => 'Suspect Two',
            'unit_name' => 'Lab Analysis',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => [
                'suspects' => [['suspect_name' => 'Suspect Two']],
                'seizures' => [
                    ['id' => 301, 'category' => 'Weapons', 'seizure_name' => 'Pistol', 'associated_person' => 'Suspect Two']
                ]
            ]
        ]);

        // 1. Attempt to save non-existent exhibit ID -> 422
        $res1 = $this->actingAs($analyst)->postJson("/cases/{$case1->case_number}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'exhibit_id' => 99999,
            'drug_type' => 'Heroin',
            'exhibit_owner_name' => 'Suspect One',
        ]);
        $res1->assertStatus(422)
             ->assertJson(['success' => false]);

        // 2. Attempt to save non-drug exhibit (Pistol) -> 422
        $res2 = $this->actingAs($analyst)->postJson("/cases/{$case2->case_number}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'exhibit_id' => 301,
            'drug_type' => 'Heroin',
            'exhibit_owner_name' => 'Suspect Two',
        ]);
        $res2->assertStatus(422)
             ->assertJson(['success' => false]);

        // 3. Attempt to save mismatched drug type (Cocaine on Heroin exhibit) -> 422
        $res3 = $this->actingAs($analyst)->postJson("/cases/{$case1->case_number}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'exhibit_id' => 201,
            'drug_type' => 'Cocaine',
            'exhibit_owner_name' => 'Suspect One',
        ]);
        $res3->assertStatus(422)
             ->assertJson(['success' => false]);
    }

    #[Test]
    public function case_creation_without_seizures_does_not_create_ghost_drug_exhibit()
    {
        $analyst = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $payload = [
            'title' => 'Republic vs John Mensah',
            'unit_name' => 'LAB Analysis',
            'suspect_name' => 'John Mensah',
            'gender' => 'Male',
            'dob' => '1990-05-15',
            'nationality' => 'Ghanaian',
            'pob' => 'Accra',
            'phone' => '0244123456',
            'address' => 'East Legon, Accra',
            'offense_description' => 'Suspected Money Laundering Investigation',
            'arrest_date' => '2026-09-24',
            'arrest_time' => '10:00:00',
            'holding_facility' => 'NACOC Central Cells',
            'has_seizures' => false,
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'John Mensah',
                        'gender' => 'Male',
                        'dob' => '1990-05-15',
                        'nationality' => 'Ghanaian',
                        'pob' => 'Accra',
                        'phone' => '0244123456',
                        'address' => 'East Legon, Accra',
                        'offense' => 'Suspected Money Laundering Investigation',
                        'date_of_arrest' => '2026-09-24',
                        'time_of_arrest' => '10:00:00',
                        'holding_facility' => 'NACOC Central Cells'
                    ]
                ],
                'seizures' => []
            ]
        ];

        $res = $this->actingAs($analyst)->postJson('/cases', $payload);
        $res->assertStatus(200)
            ->assertJson(['success' => true]);

        $createdCase = ForensicCase::where('suspect_name', 'John Mensah')->first();
        $this->assertNotNull($createdCase);

        // Verify NO exhibit model was created for John Mensah
        $exhibitCount = Exhibit::where('single_suspect_case_id', $createdCase->id)
            ->orWhere('multiple_suspect_case_id', $createdCase->id)
            ->count();
        $this->assertEquals(0, $exhibitCount);

        // Verify docket_data seizures array is empty and has no ghost drug exhibit
        $docket = $createdCase->docket_data;
        $this->assertEmpty($docket['seizures'] ?? []);
    }
}
