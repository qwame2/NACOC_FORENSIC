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

class LabPreliminaryExamProcedureTest extends TestCase
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
    public function laboratory_examination_results_table_has_preliminary_exam_procedure_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('laboratory_examination_results', 'preliminary_exam_procedure'),
            'Expected preliminary_exam_procedure column in laboratory_examination_results table.'
        );

        $labResult = new LaboratoryExaminationResult();
        $this->assertContains('preliminary_exam_procedure', $labResult->getFillable());
    }

    #[Test]
    public function lab_analysis_create_page_renders_preliminary_exam_procedure_textarea_on_top_of_shared_confirmatory(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('PRELIMINARY EXAMINATION PROCEDURE', $html);
        $this->assertStringContainsString('id="labPreliminaryExamProcedureInput"', $html);
        $this->assertStringContainsString('name="preliminary_exam_procedure"', $html);

        $procedurePos = strpos($html, 'id="labPreliminaryExamProcedureInput"');
        $sharedConfPos = strpos($html, 'Shared Confirmatory Test Information');
        $this->assertNotFalse($procedurePos, 'Preliminary examination procedure input must exist');
        $this->assertNotFalse($sharedConfPos, 'Shared Confirmatory Test Information container must exist');
        $this->assertLessThan(
            $sharedConfPos,
            $procedurePos,
            'PRELIMINARY EXAMINATION PROCEDURE container must be placed on top of Shared Confirmatory Test Information container'
        );
    }

    #[Test]
    public function show_review_page_renders_preliminary_exam_procedure_textarea_on_top_of_shared_confirmatory(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-2026-TEST-001',
            'title' => 'Test Procedure Case',
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

        $this->assertStringContainsString('PRELIMINARY EXAMINATION PROCEDURE', $html);
        $this->assertStringContainsString('id="labPreliminaryExamProcedureInput"', $html);
        $this->assertStringContainsString('name="preliminary_exam_procedure"', $html);

        $procedurePos = strpos($html, 'id="labPreliminaryExamProcedureInput"');
        $sharedConfPos = strpos($html, 'Shared Confirmatory Test Information');
        $this->assertNotFalse($procedurePos, 'Preliminary examination procedure input must exist on show_review');
        $this->assertNotFalse($sharedConfPos, 'Shared Confirmatory Test Information container must exist on show_review');
        $this->assertLessThan(
            $sharedConfPos,
            $procedurePos,
            'PRELIMINARY EXAMINATION PROCEDURE container must be placed on top of Shared Confirmatory Test Information container on show_review'
        );
    }

    #[Test]
    public function case_creation_stores_preliminary_exam_procedure_in_laboratory_examination_results(): void
    {
        $officer = $this->createLabOfficer();

        $procedureText = 'Visual physical inspection of packaging: 2 heat-sealed transparent polyethene sachets containing compressed off-white substance.';

        $casePayload = [
            'case_number' => 'LAB-PROC-2026-001',
            'title' => 'State vs Test Suspect',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'suspect_name' => 'Test Suspect',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'Heroin Brown',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Packet',
            'seizure_weight' => '250',
            'seizure_unit' => 'g',
            'presumptive_result' => 'YES (Presumptive Positive)',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Heroin Hydrochloride',
            'confirmatory_test_methods' => 'GSA-SM-T01-A (GC-MS)',
            'preliminary_exam_procedure' => $procedureText,
            'section5_completed' => '1',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Test Suspect',
                        'gender' => 'Male',
                        'nationality' => 'Ghanaian',
                    ],
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin Brown',
                        'seizure_quantity' => '1',
                        'seizure_packaging' => 'Packet',
                        'seizure_weight' => '250',
                        'seizure_unit' => 'g',
                        'preliminary_exam_procedure' => $procedureText,
                        'has_saved_lab' => true,
                        'presumptive_result' => 'YES (Presumptive Positive)',
                        'confirmatory_result' => 'YES',
                        'substance_identification' => 'Heroin Hydrochloride',
                    ],
                ],
                'lab_examination' => [
                    'preliminary_exam_procedure' => $procedureText,
                    'has_saved' => true,
                    'presumptive_result' => 'YES (Presumptive Positive)',
                    'confirmatory_result' => 'YES',
                ],
            ],
        ];

        $case = ForensicCase::create($casePayload);
        $this->assertNotNull($case);

        $labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->first();

        $this->assertNotNull($labResult, 'Expected LaboratoryExaminationResult record to be created.');
        $this->assertEquals(
            $procedureText,
            $labResult->preliminary_exam_procedure
        );
    }

    #[Test]
    public function laboratory_exam_service_persists_preliminary_exam_procedure(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_number' => 'LAB-PERSIST-2026-001',
            'title' => 'Persistence Procedure Test',
            'suspect_name' => 'Suspect Persistence',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'Cannabis Sativa',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Parcel',
            'seizure_weight' => '1000',
            'seizure_unit' => 'g',
            'unit_name' => 'Laboratory Analysis (LAU)',
        ]);

        $procedureText = 'Intake physical examination: dried plant material with leaves and seeds in brown cello-tape wrapping.';

        $payload = [
            'laboratory_number' => 'LAB-EXAM-2026-999',
            'lab_exam_reference' => 'NLR-2026-PROC-001',
            'lab_exhibit_number' => 'EXH-2026-001-NFC',
            'preliminary_exam_procedure' => $procedureText,
            'presumptive_result' => 'YES (Presumptive Positive)',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Delta-9-Tetrahydrocannabinol (THC)',
            'confirmatory_test_methods' => 'Fast Blue B salt reagent test and GC-MS',
            'notes' => 'Exam completed successfully.',
        ];

        $service = app(LaboratoryExamService::class);
        $result = $service->saveExam($case, $payload, 'Suspect Persistence', false, 'Lab Analyst');

        $this->assertIsArray($result);

        $labResult = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->first();

        $this->assertNotNull($labResult);
        $this->assertEquals(
            $procedureText,
            $labResult->preliminary_exam_procedure
        );
    }

    #[Test]
    public function show_review_page_renders_saved_preliminary_exam_procedure_data(): void
    {
        $officer = $this->createLabOfficer();

        $procedureText = 'Preserved physical appearance examination of compressed herbal material in cellophane.';

        $case = ForensicCase::create([
            'case_number' => 'LAB-SHOW-2026-001',
            'title' => 'Saved Data Procedure Test',
            'suspect_name' => 'Saved Data Suspect',
            'gender' => 'Male',
            'nationality' => 'Ghanaian',
            'has_seizures' => true,
            'seizure_name_drug' => 'Compressed Cannabis Block',
            'exhibit_category' => 'Drugs',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Block',
            'seizure_weight' => '500',
            'seizure_unit' => 'g',
            'unit_name' => 'Laboratory Analysis (LAU)',
            'section5_completed' => '1',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Saved Data Suspect',
                        'gender' => 'Male',
                        'nationality' => 'Ghanaian',
                    ],
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Compressed Cannabis Block',
                        'seizure_quantity' => '1',
                        'seizure_packaging' => 'Block',
                        'seizure_weight' => '500',
                        'seizure_unit' => 'g',
                        'preliminary_exam_procedure' => $procedureText,
                        'has_saved_lab' => true,
                        'presumptive_result' => 'YES (Presumptive Positive)',
                        'confirmatory_result' => 'YES',
                    ],
                ],
                'lab_examination' => [
                    'preliminary_exam_procedure' => $procedureText,
                    'has_saved' => true,
                    'presumptive_result' => 'YES (Presumptive Positive)',
                    'confirmatory_result' => 'YES',
                ],
            ],
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'laboratory_number' => 'LAB-SR-2026-001',
            'exam_reference' => 'NLR-2026-SR-001',
            'preliminary_exam_procedure' => $procedureText,
            'presumptive_result' => 'YES (Presumptive Positive)',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Cannabis Sativa',
            'test_methods' => 'Standard Duquenois-Levine Color Test',
            'has_saved' => true,
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Lab_Analysis");
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('PRELIMINARY EXAMINATION PROCEDURE', $html);
        $this->assertStringContainsString(e($procedureText), $html);
    }
}
