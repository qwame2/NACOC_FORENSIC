<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultipleDrugExhibitsLabExaminationTest extends TestCase
{
    use RefreshDatabase;

    protected function createLabOfficer(): User
    {
        return User::factory()->create([
            'fullname' => 'Inspector John Mensah',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => true,
        ]);
    }

    #[Test]
    public function multiple_drug_exhibits_create_distinct_lab_results_and_render_all_cards_on_show_review_and_preliminary_pdf(): void
    {
        $officer = $this->createLabOfficer();

        $docketData = [
            'suspects' => [
                [
                    'suspect_name' => 'BABA ADAM MOSATO',
                    'gender' => 'Male',
                    'nationality' => 'Ghanaian',
                ],
            ],
            'seizures' => [
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Taramaking Tapentadol 250mg',
                    'contents' => 'Blisters of suspected Taramaking Tapentadol 250mg',
                    'seizure_quantity' => '1500000',
                    'lab_exhibit_no' => 'A',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                    'source_requesting_unit' => 'Investigations Department',
                    'investigating_officer' => 'OFFICER TOLO ROCCO',
                    'has_saved_lab' => true,
                    'presumptive_result' => 'YES: Presence of Tapentadol (90%)',
                    'confirmatory_result' => 'YES',
                    'substance_identification' => 'TAPENTADOL',
                    'sample_gross' => '90 kg',
                    'sample_taken' => '7 kg',
                    'sample_reserved' => '83 kg',
                    'detected_substances' => [['substance' => 'Tapentadol', 'percentage' => '90']],
                    'test_methods' => 'Raman Spectrometry (Agilent Resolve)',
                ],
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Timaking 250mg tablets',
                    'contents' => 'Tablets Blisters of suspected Timaking 250mg tablets',
                    'seizure_quantity' => '3000000',
                    'lab_exhibit_no' => 'B',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                    'source_requesting_unit' => 'Investigations Department',
                    'investigating_officer' => 'OFFICER TOLO ROCCO',
                    'has_saved_lab' => true,
                    'presumptive_result' => 'YES: Presence of Tramadol (85%)',
                    'confirmatory_result' => 'YES',
                    'substance_identification' => 'TRAMADOL',
                    'sample_gross' => '120 kg',
                    'sample_taken' => '5 kg',
                    'sample_reserved' => '115 kg',
                    'detected_substances' => [['substance' => 'Tramadol', 'percentage' => '85']],
                    'test_methods' => 'FTIR / Colorimetric Spot Test',
                ],
            ],
            'lab_examination' => [
                'has_saved' => true,
                'exam_reference' => 'NLA-FM-T9901-F',
                'laboratory_number' => 'Lab No. 3101/FS11/2026',
            ],
        ];

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS BABA ADAM MOSATO',
            'suspect_name' => 'BABA ADAM MOSATO',
            'unit_name' => 'Lab Analysis',
            'docket_data' => $docketData,
            'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
            'source_requesting_unit' => 'Investigations Department',
            'investigating_officer' => 'OFFICER TOLO ROCCO',
        ]);

        $this->assertNotNull($case);

        // Verify that 2 exhibits were created
        $exhibits = Exhibit::where('single_suspect_case_id', $case->id)->get();
        $this->assertCount(2, $exhibits);

        // Verify that 2 distinct LaboratoryExaminationResult records were created
        $labResults = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)->get();
        $this->assertCount(2, $labResults);

        // Check that each exhibit has unique exam_reference
        $this->assertNotEquals($labResults[0]->exam_reference, $labResults[1]->exam_reference);

        // Check show_review view
        $showResponse = $this->actingAs($officer)->get('/Lab_Analysis/cases/' . $case->id . '/review');
        $showResponse->assertStatus(200);

        // Verify both laboratory examination cards are rendered on show_review
        $showResponse->assertSee('Tapentadol');
        $showResponse->assertSee('Tramadol');
        $showResponse->assertSee('Raman Spectrometry (Agilent Resolve)');
        $showResponse->assertSee('FTIR / Colorimetric Spot Test');

        // Check preliminary-report-pdf view
        $pdfResponse = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);
        $pdfResponse->assertStatus(200);

        // Page 1: Switched Case Name and Case Reference
        $pdfResponse->assertSee('Case Name');
        $pdfResponse->assertSee('REPUBLIC VRS BABA ADAM MOSATO');
        $pdfResponse->assertSee('Case Reference');
        $pdfResponse->assertSee('Tema Harbour Arrest and Seizure');
        $pdfResponse->assertSee('Investigations Department');
        $pdfResponse->assertSee('OFFICER TOLO ROCCO');

        // Page 2: Table 3 with both exhibits
        $pdfResponse->assertSee('Taramaking Tapentadol 250mg');
        $pdfResponse->assertSee('Blisters of suspected Taramaking Tapentadol 250mg');
        $pdfResponse->assertSee('90 kg');
        $pdfResponse->assertSee('83 kg');

        $pdfResponse->assertSee('Timaking 250mg tablets');
        $pdfResponse->assertSee('Tablets Blisters of suspected Timaking 250mg tablets');
        $pdfResponse->assertSee('120 kg');
        $pdfResponse->assertSee('115 kg');

        // Page 3: Table 5 with both exhibits results
        $pdfResponse->assertSee('Tapentadol detected');
        $pdfResponse->assertSee('Tapentadol');
        $pdfResponse->assertSee('Tramadol detected');
        $pdfResponse->assertSee('Tramadol');
    }

    #[Test]
    public function preliminary_report_pdf_and_show_review_show_all_exhibits_when_docket_has_more_seizures_than_db_exhibits(): void
    {
        $officer = $this->createLabOfficer();

        $docketData = [
            'suspects' => [
                [
                    'suspect_name' => 'BABA ADAM MOSATO & BATU SAMPANA',
                    'gender' => 'Male',
                    'nationality' => 'Ghanaian',
                ],
            ],
            'seizures' => [
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'A BLUE cardboard boxes containing Taramaking Tapentadol 250mg',
                    'contents' => 'Blisters of suspected Taramaking Tapentadol 250mg',
                    'seizure_quantity' => '1500000',
                    'lab_exhibit_no' => 'A',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                    'source_requesting_unit' => 'Investigations Department',
                    'investigating_officer' => 'OFFICER TOLO ROCCO',
                    'has_saved_lab' => true,
                    'presumptive_result' => 'YES: Presence of Tapentadol (90%)',
                    'sample_gross' => '90 kg',
                    'sample_reserved' => '83 kg',
                    'detected_substances' => [['substance' => 'Tapentadol', 'percentage' => '90']],
                    'test_methods' => 'Agilent Resolve Raman Analyzer',
                ],
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'RED AND WHITE cardboard boxes containing Timaking 250mg',
                    'contents' => 'Tablets Blisters of suspected Timaking 250mg tablets.',
                    'seizure_quantity' => '3000000',
                    'lab_exhibit_no' => 'B',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                    'source_requesting_unit' => 'Investigations Department',
                    'investigating_officer' => 'OFFICER TOLO ROCCO',
                    'has_saved_lab' => true,
                    'presumptive_result' => 'YES: Presence of Timaking (92%)',
                    'sample_gross' => '180 kg',
                    'sample_reserved' => '175 kg',
                    'detected_substances' => [['substance' => 'Timaking', 'percentage' => '92']],
                    'test_methods' => 'Colorimetric Chemical Spot Test',
                ],
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Brown cardboard boxes containing Rayol Tapentadol 250mg',
                    'contents' => 'Blisters of suspected Rayol Tapentadol 250mg',
                    'seizure_quantity' => '3000000',
                    'lab_exhibit_no' => 'C',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                    'source_requesting_unit' => 'Investigations Department',
                    'investigating_officer' => 'OFFICER TOLO ROCCO',
                    'has_saved_lab' => true,
                    'presumptive_result' => 'YES: Presence of Rayol (88%)',
                    'sample_gross' => '195 kg',
                    'sample_reserved' => '190 kg',
                    'detected_substances' => [['substance' => 'Rayol Tapentadol', 'percentage' => '88']],
                    'test_methods' => 'FTIR Spectrometry',
                ],
            ],
            'lab_examination' => [
                'has_saved' => true,
                'exam_reference' => 'NACOC/FSD/LAU/201/2026',
                'screening_method' => 'Agilent Resolve Raman Analyzer',
            ],
        ];

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS BABA ADAM MOSATO & BATU SAMPANA',
            'suspect_name' => 'BABA ADAM MOSATO & BATU SAMPANA',
            'unit_name' => 'Lab Analysis',
            'docket_data' => $docketData,
            'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
            'source_requesting_unit' => 'Investigations Department',
            'investigating_officer' => 'OFFICER TOLO ROCCO',
        ]);

        // PDF Response check
        $pdfResponse = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);
        $pdfResponse->assertStatus(200);

        // Page 2: Table 3 must show ALL THREE exhibits
        $pdfResponse->assertSee('A BLUE cardboard boxes containing Taramaking Tapentadol 250mg');
        $pdfResponse->assertSee('RED AND WHITE cardboard boxes containing Timaking 250mg');
        $pdfResponse->assertSee('Brown cardboard boxes containing Rayol Tapentadol 250mg');

        $pdfResponse->assertSee('Blisters of suspected Taramaking Tapentadol 250mg');
        $pdfResponse->assertSee('Tablets Blisters of suspected Timaking 250mg tablets.');
        $pdfResponse->assertSee('Blisters of suspected Rayol Tapentadol 250mg');

        // Page 3: Table 5 must show results for all 3 exhibits
        $pdfResponse->assertSee('Tapentadol detected');
        $pdfResponse->assertSee('Tapentadol');
        $pdfResponse->assertSee('Timaking detected');
        $pdfResponse->assertSee('Timaking');
        $pdfResponse->assertSee('Rayol Tapentadol detected');
        $pdfResponse->assertSee('Rayol');

        // show_review must also show cards for all exhibits
        $showResponse = $this->actingAs($officer)->get('/Lab_Analysis/cases/' . $case->id . '/review');
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Agilent Resolve Raman Analyzer');
        $showResponse->assertSee('Colorimetric Chemical Spot Test');
        $showResponse->assertSee('FTIR Spectrometry');
    }

    #[Test]
    public function multiple_drug_exhibits_associated_to_suspect_all_show_in_drug_seizures_and_suspects_table(): void
    {
        $officer = $this->createLabOfficer();

        $docketData = [
            'suspects' => [
                [
                    'suspect_name' => 'BABA ADAM MOSATO',
                    'gender' => 'Male',
                    'nationality' => 'Ghanaian',
                    'status' => 'In Custody',
                ],
            ],
            'seizures' => [
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Taramaking Tapentadol 250mg',
                    'contents' => 'Blisters of suspected Taramaking Tapentadol 250mg',
                    'seizure_quantity' => '1500000',
                    'seizure_packaging' => 'blisters',
                    'sample_gross' => '90 kg',
                    'lab_exhibit_no' => 'A',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                ],
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Timaking 250mg tablets',
                    'contents' => 'Tablets Blisters of suspected Timaking 250mg tablets',
                    'seizure_quantity' => '3000000',
                    'seizure_packaging' => 'tablets',
                    'sample_gross' => '120 kg',
                    'lab_exhibit_no' => 'B',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                ],
                [
                    'exhibit_category' => 'Drugs',
                    'commodity_name' => 'Rayol Tapentadol 250mg',
                    'contents' => 'Blisters of suspected Rayol Tapentadol 250mg',
                    'seizure_quantity' => '500000',
                    'seizure_packaging' => 'capsules',
                    'sample_gross' => '45 kg',
                    'lab_exhibit_no' => 'C',
                    'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
                ],
            ],
        ];

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS BABA ADAM MOSATO',
            'suspect_name' => 'BABA ADAM MOSATO',
            'unit_name' => 'Lab Analysis',
            'docket_data' => $docketData,
            'source_of_exhibits' => 'Tema Harbour Arrest and Seizure',
        ]);

        // GET /Lab_Analysis/report (Drug Seizures & Suspects Registry)
        $response = $this->actingAs($officer)->get('/Lab_Analysis/report');
        $response->assertStatus(200);

        // Verify ALL THREE drug exhibits associated with suspect BABA ADAM MOSATO appear in the table
        $response->assertSee('Taramaking Tapentadol 250mg');
        $response->assertSee('Timaking 250mg tablets');
        $response->assertSee('Rayol Tapentadol 250mg');

        // Verify quantities and weights for each exhibit
        $response->assertSee('1500000 blisters');
        $response->assertSee('90 kg');
        $response->assertSee('3000000 tablets');
        $response->assertSee('120 kg');
        $response->assertSee('500000 capsules');
        $response->assertSee('45 kg');

        // Verify lab exhibit badges
        $response->assertSee('Exhibit A');
        $response->assertSee('Exhibit B');
        $response->assertSee('Exhibit C');

        // Verify suspect name is associated with the records
        $response->assertSee('BABA ADAM MOSATO');

        // GET /Lab_Analysis/lab_sitrep_pdf (SITREP PDF dossier)
        $pdfResponse = $this->actingAs($officer)->get('/Lab_Analysis/lab_sitrep_pdf');
        $pdfResponse->assertStatus(200);

        // All three exhibits must also be present in the PDF dossier table
        $pdfResponse->assertSee('Taramaking Tapentadol 250mg');
        $pdfResponse->assertSee('Timaking 250mg tablets');
        $pdfResponse->assertSee('Rayol Tapentadol 250mg');
    }
}
