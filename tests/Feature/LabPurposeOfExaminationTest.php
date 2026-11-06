<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\User;
use App\Services\LaboratoryExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabPurposeOfExaminationTest extends TestCase
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
    public function laboratory_examination_results_table_has_purpose_of_examination_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('laboratory_examination_results', 'purpose_of_examination'),
            'Expected purpose_of_examination column in laboratory_examination_results table.'
        );

        $labResult = new LaboratoryExaminationResult();
        $this->assertContains('purpose_of_examination', $labResult->getFillable());
    }

    #[Test]
    public function lab_analysis_create_page_renders_purpose_of_examination_textarea(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Purpose of Examination', $html);
        $this->assertStringContainsString('id="labPurposeOfExaminationInput"', $html);
        $this->assertStringContainsString('name="purpose_of_examination"', $html);

        $purposePos = strpos($html, 'id="labPurposeOfExaminationInput"');
        $presumptivePos = strpos($html, 'Presumptive Test (Screening Analysis)');
        $this->assertNotFalse($purposePos);
        $this->assertNotFalse($presumptivePos);
        $this->assertLessThan($presumptivePos, $purposePos, 'Purpose of Examination container must be placed above Presumptive Test container');
    }

    #[Test]
    public function cases_create_page_renders_purpose_of_examination_textarea(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/cases/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Purpose of Examination', $html);
        $this->assertStringContainsString('id="labPurposeOfExaminationInput"', $html);
        $this->assertStringContainsString('name="purpose_of_examination"', $html);

        $purposePos = strpos($html, 'id="labPurposeOfExaminationInput"');
        $presumptivePos = strpos($html, 'Presumptive Test Reaction Result');
        $this->assertNotFalse($purposePos);
        $this->assertNotFalse($presumptivePos);
        $this->assertLessThan($presumptivePos, $purposePos, 'Purpose of Examination container must be placed above Presumptive Test container');
    }

    #[Test]
    public function case_creation_stores_purpose_of_examination_in_laboratory_examination_results(): void
    {
        $officer = $this->createLabOfficer();

        $casePayload = [
            'case_number' => 'CASE-PURPOSE-TEST-001',
            'title' => 'Purpose of Examination Forensic Test Case',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'suspect_name' => 'John Doe',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'arrest_date' => '2026-11-06',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Heroin',
            'seizure_quantity' => '10',
            'seizure_packaging' => 'Sachets',
            'seizure_weight' => '100.00',
            'seizure_unit' => 'g',
            'presumptive_result' => 'YES (Marquis Positive)',
            'confirmatory_test_result' => 'YES',
            'purpose_of_examination' => 'Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage.',
            'confirmatory_substance_identification' => 'Heroin Hydrochloride',
            'section5_completed' => '1',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'John Doe', 'gender' => 'Male', 'nationality' => 'Ghanaian']
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin',
                        'seizure_quantity' => '10',
                        'seizure_packaging' => 'Sachets',
                        'seizure_weight' => '100.00',
                        'seizure_unit' => 'g',
                        'has_saved_lab' => true,
                        'purpose_of_examination' => 'Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage.',
                        'presumptive_result' => 'YES (Marquis Positive)',
                        'confirmatory_result' => 'YES',
                        'substance_identification' => 'Heroin Hydrochloride',
                    ]
                ],
                'lab_examination' => [
                    'has_saved' => true,
                    'purpose_of_examination' => 'Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage.',
                    'presumptive_result' => 'YES (Marquis Positive)',
                    'confirmatory_result' => 'YES',
                ]
            ]
        ];

        $case = ForensicCase::create($casePayload);
        $this->assertNotNull($case);

        $labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->first();

        $this->assertNotNull($labResult, 'Expected LaboratoryExaminationResult record to be created.');
        $this->assertEquals(
            'Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage.',
            $labResult->purpose_of_examination
        );
    }

    #[Test]
    public function laboratory_exam_service_persists_purpose_of_examination(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'CASE-LAB-SVC-PURPOSE-001',
            'title' => 'Lab Service Purpose Test',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'suspect_name' => 'Jane Smith',
            'gender' => 'Female',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine',
            'seizure_quantity' => '5',
            'seizure_packaging' => 'Parcels',
            'seizure_weight' => '250.00',
            'seizure_unit' => 'g',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name_drug' => 'Cocaine',
                        'seizure_quantity' => '5',
                        'seizure_packaging' => 'Parcels',
                        'seizure_weight' => '250.00',
                        'seizure_unit' => 'g',
                    ]
                ]
            ]
        ]);

        $labService = app(LaboratoryExamService::class);
        $payload = [
            'laboratory_number' => 'LAB-2026-999',
            'exam_reference' => 'NLA-FM-T9999-F',
            'purpose_of_examination' => 'Specific forensic chemical analysis for court presentation.',
            'presumptive_result' => 'YES (Cobalt Thiocyanate Positive)',
            'confirmatory_result' => 'YES',
            'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
            'sample_weight_description' => 'Cocaine',
            'sample_weight_units' => '5 Parcels',
            'sample_weight_gross' => '250.00 g',
            'sample_weight_taken' => '5.00',
            'sample_weight_reserved' => '245.00',
        ];

        $labService->saveExam($case, $payload, 'Jane Smith', false, 'Lab Analyst');

        $labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->first();

        $this->assertNotNull($labResult);
        $this->assertEquals(
            'Specific forensic chemical analysis for court presentation.',
            $labResult->purpose_of_examination
        );
    }

    #[Test]
    public function show_review_page_renders_purpose_of_examination_field_and_saved_data(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'CASE-SHOW-REV-PURPOSE-001',
            'title' => 'Show Review Purpose Test',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'suspect_name' => 'Michael Boateng',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Methamphetamine',
            'seizure_quantity' => '2',
            'seizure_packaging' => 'Bags',
            'seizure_weight' => '50.00',
            'seizure_unit' => 'g',
            'section5_completed' => '1',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name_drug' => 'Methamphetamine',
                        'seizure_quantity' => '2',
                        'seizure_packaging' => 'Bags',
                        'seizure_weight' => '50.00',
                        'seizure_unit' => 'g',
                        'has_saved_lab' => true,
                        'purpose_of_examination' => 'Court evidence characterization and purity determination.',
                        'presumptive_result' => 'YES',
                        'confirmatory_result' => 'YES',
                    ]
                ],
                'lab_examination' => [
                    'has_saved' => true,
                    'purpose_of_examination' => 'Court evidence characterization and purity determination.',
                    'presumptive_result' => 'YES',
                    'confirmatory_result' => 'YES',
                ]
            ]
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Lab_Analysis");
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Purpose of Examination', $html);
        $this->assertStringContainsString('id="labPurposeOfExaminationInput"', $html);
        $this->assertStringContainsString('id="displayLabPurposeOfExamination"', $html);
        $this->assertStringContainsString('Court evidence characterization and purity determination.', $html);
    }
}

