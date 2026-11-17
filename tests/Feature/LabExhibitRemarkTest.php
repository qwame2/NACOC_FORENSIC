<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LabExhibitRemarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhibits_table_has_exhibit_remark_column(): void
    {
        $this->assertTrue(Schema::hasColumn('exhibits', 'exhibit_remark'));
    }

    public function test_lab_analysis_create_page_contains_exhibit_remark_input(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/create');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('Seized Commodities &amp; Contraband Manifest', $content);
        $this->assertStringContainsString('newSeizureExhibitRemark', $content);
        $this->assertStringContainsString('name="exhibit_remark"', $content);
    }

    public function test_case_creation_persists_exhibit_remark_to_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'LAB/EXH/REM/001',
            'suspect_name' => 'Kojo Antwi',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Suspected Heroin',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-REM-001',
            'commodity_name' => 'Suspected Heroin Bricks',
            'source_of_exhibits' => 'Kotoka Cargo Shed',
            'exhibit_remark' => 'Package seal intact, custody transferred for confirmatory assay.',
            'exhibit_category' => 'Narcotic Drug',
        ]);

        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'commodity_name' => 'Suspected Heroin Bricks',
            'exhibit_remark' => 'Package seal intact, custody transferred for confirmatory assay.',
        ]);
    }

    public function test_exhibits_table_has_lab_remark_column(): void
    {
        $this->assertTrue(Schema::hasColumn('exhibits', 'lab_remark'));
    }

    public function test_case_creation_persists_confirmatory_remark_to_lab_remark_in_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $payload = [
            'case_number' => 'LAB/EXH/CR/2026/001',
            'title' => 'High Grade Heroin Seizure Case',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kwame Mensah',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Heroin Hydrochloride Powder',
            'seizure_quantity' => '5 Packages',
            'seizure_weight' => '5.2',
            'seizure_unit' => 'kg',
            'source_of_exhibits' => 'Tema Port Container Terminal',
            'exhibit_remark' => 'Outer vacuum heat-seal untampered upon seizure receipt.',
            'confirmatory_substance_identification' => 'Heroin Hydrochloride',
            'confirmatory_test_methods' => 'GC-MS / FTIR Spectrometry',
            'confirmatory_specifications' => 'Certified Reference Standard Purity Grade A',
            'confirmatory_remark' => 'Confirmatory analytical assay verified 88.4% diacetylmorphine base purity with no synthetic adulterants.',
            'docket_data' => [
                'seizures' => [
                    [
                        'name' => 'Heroin Hydrochloride Powder',
                        'seizure_name_drug' => 'Heroin Hydrochloride Powder',
                        'exhibit_category' => 'Drugs',
                        'seizure_quantity' => '5 Packages',
                        'seizure_weight' => '5.2',
                        'seizure_unit' => 'kg',
                        'source_of_exhibits' => 'Tema Port Container Terminal',
                        'exhibit_remark' => 'Outer vacuum heat-seal untampered upon seizure receipt.',
                    ]
                ],
                'lab_examination' => [
                    'remark' => 'Confirmatory analytical assay verified 88.4% diacetylmorphine base purity with no synthetic adulterants.',
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'Heroin Hydrochloride Powder')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Outer vacuum heat-seal untampered upon seizure receipt.', $exhibit->exhibit_remark);
        $this->assertEquals('Confirmatory analytical assay verified 88.4% diacetylmorphine base purity with no synthetic adulterants.', $exhibit->lab_remark);
    }

    public function test_update_unit_progress_persists_lab_remark_to_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'LAB/PROG/001',
            'suspect_name' => 'Yaw Boateng',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'White Cocaine Powder',
            'exhibit_category' => 'Drugs',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-01',
            'commodity_name' => 'White Cocaine Powder',
            'exhibit_category' => 'Drugs',
            'exhibit_remark' => 'Intact brown tape packaging.',
        ]);

        $progressPayload = [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'officer' => 'Dr. Analytical Chemist',
            'exhibit_id' => $exhibit->id,
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
            'confirmatory_test_methods' => 'HPLC / GC-MS Method Standard',
            'confirmatory_remark' => 'Confirmatory analysis confirms pure Cocaine HCl. No cutting agent identified.',
        ];

        $response = $this->actingAs($user)->postJson("/cases/{$case->id}/update-unit-progress", $progressPayload);
        $response->assertStatus(200);

        $exhibit->refresh();
        $this->assertEquals('Intact brown tape packaging.', $exhibit->exhibit_remark);
        $this->assertEquals('Confirmatory analysis confirms pure Cocaine HCl. No cutting agent identified.', $exhibit->lab_remark);
    }
}
