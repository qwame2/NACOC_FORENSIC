<?php

namespace Tests\Feature;

use App\Models\SuspectGsaReport;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabPreliminaryReportAfterSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function createLabOfficer(bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => 'Kwame Lab Officer',
            'unit' => 'Laboratory Analysis (LAU)',
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function lab_create_page_contains_preliminary_report_download_container_hooked_to_save_case(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="preliminaryReportDownloadContainer"', $html);
        $this->assertStringContainsString('id="preliminaryReportOpenLink"', $html);
        $this->assertStringContainsString('id="preliminaryReportDownloadLink"', $html);
        $this->assertStringContainsString('id="preliminaryReportFilename"', $html);
        $this->assertStringContainsString('showSuccessPopover(finalRef, data && data.preliminary_report ? data.preliminary_report : null)', $html);

        // Substance Identification visible inputbox safely removed from RESULT / CONFIRMATORY ANALYSIS
        $this->assertStringNotContainsString('<span>Substance Identification', $html);
        $this->assertStringContainsString('type="hidden" id="confirmatorySubstanceIdentification_${idx}"', $html);

        $casesCreateHtml = $this->actingAs($officer)->get('/cases/create')->assertOk()->getContent();
        $this->assertStringNotContainsString('<span>Substance Identification', $casesCreateHtml);
        $this->assertStringContainsString('type="hidden" id="confirmatorySubstanceIdentification"', $casesCreateHtml);
    }

    #[Test]
    public function saving_lab_drug_case_returns_and_persists_preliminary_report_and_pdf_renders(): void
    {
        $officer = $this->createLabOfficer();

        $payload = [
            'case_number' => 'NCC-2026-PRELIM-01',
            'title' => 'Republic vs Kwaku Mensah',
            'unit_name' => 'Lab Analysis',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Kwaku Mensah',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cannabis Sativa',
            'seizure_name_drug' => 'Cannabis Sativa',
            'seizure_quantity' => '3',
            'seizure_packaging' => 'Compressed Parcels',
            'seizure_weight' => '4.5',
            'seizure_unit' => 'kg',
            'lab_workflow_mode' => 'nlr',
            'section5_completed' => '1',
            'laboratory_number' => 'LAB-2026-PRELIM-01',
            'lab_exam_reference' => 'NLA-FM-T8801-F',
            'presumptive_result' => 'YES | THC Positive',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Delta-9-THC',
            'confirmatory_test_methods' => 'GSA-SM-T009-GCMS',
            'preliminary_exam_procedure' => 'Duquenois-Levine color test and gross weighing.',
            'screening_method' => ['GC-MS Agilent 8890'],
            'docket_data' => [
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Cannabis Sativa',
                        'seizure_name_drug' => 'Cannabis Sativa',
                        'seizure_quantity' => '3',
                        'seizure_packaging' => 'Compressed Parcels',
                        'seizure_weight' => '4.5',
                        'seizure_unit' => 'kg',
                        'associated_person' => 'Kwaku Mensah',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $payload);
        $response->assertOk()->assertJsonPath('success', true);

        $caseNumber = $response->json('case.case_number');
        $prelim = $response->json('preliminary_report');

        $this->assertIsArray($prelim);
        $this->assertNotEmpty($prelim['id']);
        $this->assertStringStartsWith('Preliminary_Report_', $prelim['filename']);
        $this->assertStringContainsString("Lab_Analysis/preliminary-report-pdf/{$caseNumber}", $prelim['url']);
        $this->assertSame($caseNumber, $prelim['case_number']);

        $this->assertDatabaseHas('suspect_preliminary_reports', [
            'id' => $prelim['id'],
            'case_number' => $caseNumber,
            'original_filename' => $prelim['filename'],
        ]);

        // Visiting preview URL renders the report without autoprint script
        $previewRes = $this->actingAs($officer)->get("/Lab_Analysis/preliminary-report-pdf/{$caseNumber}");
        $previewRes->assertOk();
        $this->assertStringContainsString('PRELIMINARY EXHIBIT ANALYSIS REPORT', $previewRes->getContent());
        $this->assertStringNotContainsString("window.addEventListener('load', function ()", $previewRes->getContent());

        // Visiting ?download=1 adds the auto-print download trigger script
        $downloadRes = $this->actingAs($officer)->get("/Lab_Analysis/preliminary-report-pdf/{$caseNumber}?download=1");
        $downloadRes->assertOk();
        $this->assertStringContainsString("window.addEventListener('load', function ()", $downloadRes->getContent());
        $this->assertStringContainsString('window.print();', $downloadRes->getContent());
    }

    #[Test]
    public function saving_lab_drug_case_without_gsa_or_section5_still_generates_preliminary_report(): void
    {
        $officer = $this->createLabOfficer();

        $payload = [
            'case_number' => 'NCC-2026-PRELIM-02',
            'title' => 'Republic vs Yaw Boateng',
            'unit_name' => 'Lab Analysis',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Yaw Boateng',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cocaine',
            'seizure_name_drug' => 'Cocaine',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Sealed Wrap',
            'seizure_weight' => '1.2',
            'seizure_unit' => 'kg',
            'docket_data' => [
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Cocaine',
                        'seizure_name_drug' => 'Cocaine',
                        'seizure_quantity' => '1',
                        'seizure_packaging' => 'Sealed Wrap',
                        'seizure_weight' => '1.2',
                        'seizure_unit' => 'kg',
                        'associated_person' => 'Yaw Boateng',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $payload);
        $response->assertOk()->assertJsonPath('success', true);

        $prelim = $response->json('preliminary_report');
        $this->assertIsArray($prelim);
        $this->assertStringContainsString('Lab_Analysis/preliminary-report-pdf/NCC-2026-PRELIM-02', $prelim['url']);
        $this->assertEquals(1, SuspectPreliminaryReport::where('case_number', 'NCC-2026-PRELIM-02')->count());
    }

    #[Test]
    public function saving_lab_case_with_gsa_upload_does_not_activate_preliminary_report_download(): void
    {
        Storage::fake('public');
        $officer = $this->createLabOfficer();
        $gsaFile = UploadedFile::fake()->create('gsa_certificate.pdf', 120, 'application/pdf');

        $payload = [
            'case_number' => 'NCC-2026-PRELIM-GSA',
            'title' => 'Republic vs GSA Suspect',
            'unit_name' => 'Lab Analysis',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'GSA Suspect',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Heroin',
            'seizure_name_drug' => 'Heroin',
            'lab_workflow_mode' => 'gsa',
            'gsa_lab_number' => 'GSA-2026-501',
            'gsa_files_uploaded' => 1,
            'gsa_report_images' => [$gsaFile],
            'docket_data' => [
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Heroin',
                        'seizure_name_drug' => 'Heroin',
                        'associated_person' => 'GSA Suspect',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($officer)->post('/cases', $payload, ['Accept' => 'application/json']);
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNull($response->json('preliminary_report'));
        $this->assertEquals(1, SuspectGsaReport::where('case_number', 'NCC-2026-PRELIM-GSA')->count());
        $this->assertEquals(0, SuspectPreliminaryReport::where('case_number', 'NCC-2026-PRELIM-GSA')->count());
    }
}
