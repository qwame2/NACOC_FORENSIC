<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\User;
use App\Services\LaboratoryExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabScreeningMethodAndDispositionTest extends TestCase
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
    public function laboratory_examination_results_table_has_screening_method_and_disposition_of_exhibit_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumn('laboratory_examination_results', 'screening_method'),
            'Expected screening_method column in laboratory_examination_results table.'
        );

        $this->assertTrue(
            Schema::hasColumn('laboratory_examination_results', 'disposition_of_exhibit'),
            'Expected disposition_of_exhibit column in laboratory_examination_results table.'
        );

        $labResult = new LaboratoryExaminationResult();
        $this->assertContains('screening_method', $labResult->getFillable());
        $this->assertContains('disposition_of_exhibit', $labResult->getFillable());
    }

    #[Test]
    public function lab_analysis_create_page_renders_screening_method_above_test_methods_and_disposition_below_remark(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Screening method/Instrument Used checks
        $this->assertStringContainsString('Screening method/Instrument Used', $html);
        $this->assertStringContainsString('addScreeningMethodRow()', $html);
        $this->assertStringContainsString('name="screening_method[]"', $html);

        $screeningPos = strpos($html, 'Screening method/Instrument Used');
        $testMethodsPos = strpos($html, 'id="confirmatoryTestMethods"');
        $this->assertNotFalse($screeningPos, 'Screening method input must exist');
        $this->assertNotFalse($testMethodsPos, 'Test Methods input must exist');
        $this->assertLessThan(
            $testMethodsPos,
            $screeningPos,
            'Screening method/Instrument Used must be placed on top of Test Methods * input'
        );

        // 2. Disposition Of Exhibit(s) checks
        $this->assertStringContainsString('Disposition Of Exhibit(s)', $html);
        $this->assertStringContainsString('id="confirmatoryDispositionOfExhibit"', $html);
        $this->assertStringContainsString('name="disposition_of_exhibit"', $html);

        $remarkPos = strpos($html, 'id="confirmatoryRemark"');
        $dispositionPos = strpos($html, 'id="confirmatoryDispositionOfExhibit"');
        $this->assertNotFalse($remarkPos, 'Remark textarea must exist');
        $this->assertNotFalse($dispositionPos, 'Disposition Of Exhibit(s) textarea must exist');
        $this->assertGreaterThan(
            $remarkPos,
            $dispositionPos,
            'Disposition Of Exhibit(s) textarea must be placed underneath Remark textarea'
        );
    }

    #[Test]
    public function show_review_page_renders_screening_method_above_test_methods_and_disposition_below_remark(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-2026-TEST-002',
            'title' => 'Test Shared Confirmatory Case',
            'suspect_name' => 'John Doe',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'White Powder',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Package',
            'seizure_weight' => '500',
            'seizure_unit' => 'g',
            'unit_name' => 'Laboratory Analysis (LAU)',
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Lab_Analysis");
        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Screening method/Instrument Used checks on review
        $this->assertStringContainsString('Screening method/Instrument Used', $html);
        $this->assertStringContainsString('addScreeningMethodRow()', $html);
        $this->assertStringContainsString('name="screening_method[]"', $html);

        $screeningPos = strpos($html, 'Screening method/Instrument Used');
        $testMethodsPos = strpos($html, 'id="confirmatoryTestMethods"');
        $this->assertNotFalse($screeningPos, 'Screening method input must exist on show_review');
        $this->assertNotFalse($testMethodsPos, 'Test Methods input must exist on show_review');
        $this->assertLessThan(
            $testMethodsPos,
            $screeningPos,
            'Screening method/Instrument Used must be placed on top of Test Methods * on show_review'
        );

        // 2. Disposition Of Exhibit(s) checks on review
        $this->assertStringContainsString('Disposition Of Exhibit(s)', $html);
        $this->assertStringContainsString('id="confirmatoryDispositionOfExhibit"', $html);
        $this->assertStringContainsString('name="disposition_of_exhibit"', $html);

        $remarkPos = strpos($html, 'id="confirmatoryRemark"');
        $dispositionPos = strpos($html, 'id="confirmatoryDispositionOfExhibit"');
        $this->assertNotFalse($remarkPos, 'Remark textarea must exist on show_review');
        $this->assertNotFalse($dispositionPos, 'Disposition Of Exhibit(s) textarea must exist on show_review');
        $this->assertGreaterThan(
            $remarkPos,
            $dispositionPos,
            'Disposition Of Exhibit(s) textarea must be placed underneath Remark on show_review'
        );
    }

    #[Test]
    public function laboratory_exam_service_persists_screening_method_and_disposition_of_exhibit(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-2026-TEST-003',
            'title' => 'Test Service Persistence',
            'suspect_name' => 'Jane Smith',
            'gender' => 'Female',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'Heroin Bricks',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '2',
            'seizure_packaging' => 'Tape',
            'seizure_weight' => '1000',
            'seizure_unit' => 'g',
            'unit_name' => 'Laboratory Analysis (LAU)',
        ]);

        $service = app(LaboratoryExamService::class);

        $saveData = [
            'presumptive_result' => 'Positive for Opioids',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Heroin Hydrochloride',
            'confirmatory_test_methods' => 'GC-MS, FTIR',
            'screening_method' => ['Colorimetry (Marquis)', 'FTIR Spectrometry'],
            'disposition_of_exhibit' => 'Secured in Forensic Evidence Vault Room 4B pending court order',
            'sample_weight_taken' => '10',
            'sample_weight_reserved' => '990',
        ];

        $result = $service->saveExam($case, $saveData, $officer->fullname, false, $officer->fullname);

        $this->assertIsArray($result);

        // Verify database persistence
        $this->assertDatabaseHas('laboratory_examination_results', [
            'single_suspect_case_id' => $case->id,
            'disposition_of_exhibit' => 'Secured in Forensic Evidence Vault Room 4B pending court order',
        ]);

        $labRecord = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($labRecord);
        $this->assertStringContainsString('Colorimetry (Marquis)', $labRecord->screening_method);
        $this->assertStringContainsString('FTIR Spectrometry', $labRecord->screening_method);
        $this->assertEquals(
            'Secured in Forensic Evidence Vault Room 4B pending court order',
            $labRecord->disposition_of_exhibit
        );
    }

    #[Test]
    public function show_review_page_prepopulates_saved_screening_method_and_disposition_of_exhibit(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-2026-TEST-004',
            'title' => 'Test Prepopulation Case',
            'suspect_name' => 'Kofi Annan',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'Methamphetamine',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Bag',
            'seizure_weight' => '250',
            'seizure_unit' => 'g',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'docket_data' => [
                'lab_examination' => [
                    'screening_method' => ['Raman Spectroscopy', 'LC-MS'],
                    'disposition_of_exhibit' => 'Retained in custody of Chief Forensic Chemist',
                ]
            ]
        ]);

        LaboratoryExaminationResult::create([
            'case_id' => $case->id,
            'single_suspect_case_id' => $case->id,
            'officer_id' => $officer->id,
            'analyst' => 'Lab Analyst',
            'has_saved' => true,
            'exhibit_name' => 'Methamphetamine',
            'presumptive_result' => 'Positive',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Methamphetamine HCl',
            'test_methods' => 'GC-MS',
            'screening_method' => json_encode(['Raman Spectroscopy', 'LC-MS']),
            'disposition_of_exhibit' => 'Retained in custody of Chief Forensic Chemist',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Lab_Analysis");
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Raman Spectroscopy', $html);
        $this->assertStringContainsString('Retained in custody of Chief Forensic Chemist', $html);
    }

    #[Test]
    public function case_creation_with_array_screening_method_and_saved_lab_persists_cleanly_without_array_to_string_error(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-2026-ARRAY-TEST',
            'title' => 'Republic vs Test Suspect',
            'suspect_name' => 'Kojo Mensah',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'date_of_birth' => '1990-05-15',
            'place_of_birth' => 'Accra',
            'contact_phone' => '0240000000',
            'residential_address' => 'East Legon, Accra',
            'has_seizures' => true,
            'has_saved_lab' => true,
            'unit_name' => 'Laboratory Analysis (LAU)',
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Drugs',
                    'seizure_name_drug' => 'Tapentadol',
                    'seizure_quantity' => 150000,
                    'seizure_packaging' => 'Slabs',
                    'seizure_weight' => 90,
                    'seizure_unit' => 'kg',
                    'has_saved_lab' => true,
                    'purpose_of_examination' => 'Substance testing',
                    'preliminary_exam_procedure' => 'Standard protocol',
                    'screening_method' => ['GC-MS', 'FTIR', 'Raman'],
                    'test_methods' => ['Marquis Reagent'],
                    'detected_substances' => [['substance' => 'Tapentadol', 'percentage' => '90']],
                    'presumptive_result' => 'YES: Test #1: Presence of Tapentadol (90%)',
                    'confirmatory_result' => 'YES',
                    'substance_identification' => 'TAPENTADOL',
                    'sample_gross' => '90 kg',
                    'sample_taken' => '7 g',
                    'sample_reserved' => '83 kg',
                    'specifications' => 'Batch #991',
                    'remark' => 'Consistent with Controlled Substance',
                    'disposition_of_exhibit' => 'Secure Vault',
                    'analyst' => 'Officer Atta',
                ]
            ],
            'docket_data' => [
                'lab_examination' => [
                    'has_saved' => true,
                    'screening_method' => ['GC-MS', 'FTIR', 'Raman'],
                    'disposition_of_exhibit' => 'Secure Vault',
                ]
            ]
        ]);

        $this->assertNotNull($case);
        $labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($labResult, 'Expected laboratory examination result to be created.');
        $this->assertStringContainsString('GC-MS', $labResult->screening_method);
        $this->assertStringContainsString('FTIR', $labResult->screening_method);
        $this->assertEquals('Secure Vault', $labResult->disposition_of_exhibit);
    }
}
