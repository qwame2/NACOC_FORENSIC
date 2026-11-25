<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreliminaryExhibitAnalysisReportTest extends TestCase
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
    public function preliminary_report_pdf_route_returns_200_with_all_sections_and_tables(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-0099',
            'title' => 'Operation Silent Wave',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kojo Antwi',
            'raid_location' => 'Tema Port Container Terminal',
            'arresting_officer' => 'Detective Chief Inspector Boateng',
            'submitting_officer' => 'Detective Inspector Mensah',
            'description_of_exhibits' => 'Two brown sealed cartons containing compressed plant substance wrapped in transparent polythene.',
        ]);

        $ex1 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Brown Compressed Slabs',
            'contents' => 'Dried brownish compressed plant material',
            'seizure_quantity' => '10',
            'lab_exhibit_no' => 'LAB-EXH-01',
            'source_of_exhibits' => 'Operation Silent Wave',
            'source_requesting_unit' => 'Enforcement & Operations Unit',
            'investigating_officer' => 'D/Inspector Boateng',
            'seizure_date' => '2026-11-04',
            'seizure_time' => '14:30',
            'description_of_exhibits' => 'Two brown sealed cartons containing compressed plant substance wrapped in transparent polythene.',
        ]);

        $lab = LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $ex1->id,
            'exam_reference' => 'NLA-FM-T0099-F',
            'purpose_of_examination' => 'To establish preliminary chemical identification of the suspected narcotic substance.',
            'preliminary_exam_procedure' => 'Sampling representative core portions and performing sequential Fast Blue B and Duquenois-Levine colorimetric spot tests.',
            'screening_method' => 'Fast Blue B Reagent & Duquenois-Levine Colorimetric Spot Test',
            'date_received' => '2026-11-04',
            'date_started' => '2026-11-05',
            'date_completed' => '2026-11-05',
            'presumptive_result' => 'POSITIVE (Cannabis Sativa)',
            'confirmatory_result' => 'Pending',
            'sample_weight_gross' => '12.4 kg',
            'sample_weight_reserved' => '12.35 kg',
            'sample_weight_taken' => '0.05 kg',
            'test_methods' => 'Fast Blue B Colorimetric Chemical Spot Test',
            'detected_substances' => [['substance' => 'Cannabis (THC)', 'purity' => 'Active']],
            'presumptive_presence' => 'Cannabis (THC)',
            'specifications' => 'Characteristic violet-blue coloration observed within 30 seconds.',
            'remark' => 'Packaging intact upon receipt; evidence chain verified.',
            'disposition_of_exhibit' => 'Securely stored in the NACOC Forensic Laboratory Vault.',
            'analyst' => 'Inspector John Mensah',
            'has_saved' => true,
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);

        $response->assertStatus(200);

        // Header check
        $response->assertSee('RESTRICTED');

        // Cover page logos & headings
        $response->assertSee('NARCOTICS CONTROL COMMISSION (NACOC)');
        $response->assertSee('FORENSICS DEPARTMENT');
        $response->assertSee('LABORATORY ANALYSIS UNIT');
        $response->assertSee('PRELIMINARY EXHIBIT ANALYSIS REPORT');

        // Table 1 checks
        $response->assertSee('Case Reference');
        $response->assertSee('NCC-2026-0099');
        $response->assertSee('Report Reference No.');
        $response->assertSee('NACOC/FSD/LAU/099/2026');
        $response->assertSee('Date of Report');

        // Subject Heading check
        $response->assertSee('SUBJECT: PRESUMPTIVE TEST OF EXHIBITS CONDUCTED IN THE PRESENCE OF KOJO ANTWI');

        // Table 2 checks
        $response->assertSee('Case Name');
        $response->assertSee('Operation Silent Wave');
        $response->assertSee('Source/Requesting Unit');
        $response->assertSee('Enforcement & Operations Unit');
        $response->assertSee('Investigating Officer');
        $response->assertSee('D/Inspector Boateng');
        $response->assertSee('Date and Time Received');
        $response->assertSee('Date of Preliminary Examination');
        $response->assertSee('PURPOSE OF EXAMINATION:');
        $response->assertSee('To establish preliminary chemical identification of the suspected narcotic substance.');

        // Main Body checks
        $response->assertSee('DESCRIPTION OF EXHIBIT(S)');
        $response->assertSee('Two brown sealed cartons containing compressed plant substance');

        // Table 3 checks
        $response->assertSee('Package as Received');
        $response->assertSee('Contents (Visual)');
        $response->assertSee('Gross Weight');
        $response->assertSee('Net Weight');
        $response->assertSee('LAB-EXH-01');
        $response->assertSee('Brown Compressed Slabs');
        $response->assertSee('Dried brownish compressed plant material');
        $response->assertSee('12.4 kg');
        $response->assertSee('12.35 kg');

        // Procedure & Table 4 checks
        $response->assertSee('PRELIMINARY EXAMINATION PROCEDURE');
        $response->assertSee('Screening Method/Instrument Used');
        $response->assertSee('Fast Blue B Reagent &amp; Duquenois-Levine Colorimetric Spot Test', false);

        // Table 5 checks
        $response->assertSee('RESULTS OF PRELIMINARY EXAMINATION');
        $response->assertSee('Test/Examination Conducted');
        $response->assertSee('Observation/Results');
        $response->assertSee('Interpretation');
        $response->assertSee('Cannabis (THC) detected');
        $response->assertSee('Cannabis (THC)');

        // Remarks & Disposition checks
        $response->assertSee('REMARKS');
        $response->assertSee('Specific Remarks');
        $response->assertSee('DISPOSITION OF EXHIBIT(S)');
        $response->assertSee('Packaging intact upon receipt; evidence chain verified.');
        $response->assertSee('Securely stored in the NACOC Forensic Laboratory Vault.');

        // Verify signatures and photographic evidence page are excluded per user layout specification
        $response->assertDontSee('REPORTING FORENSIC ANALYST:');
        $response->assertDontSee('HEAD OF LABORATORY ANALYSIS UNIT:');
        $response->assertDontSee('PHOTOGRAPHIC EVIDENCE / EXHIBIT DOCUMENTATION');
        $response->assertSee('Page 1 of 4');
        $response->assertSee('Page 2 of 4');
        $response->assertSee('Page 3 of 4');
        $response->assertSee('Page 4 of 4');

        // Signatory page tables checks
        $response->assertSee('PREPARED/REPORTED BY');
        $response->assertSee('APPROVED BY');
        $response->assertSee('AUTHORIZED BY');
        $response->assertSee('Rank/Designation');
        $response->assertSee('Signature');

        // Check alias route as well
        $aliasResponse = $this->actingAs($officer)->get('/lab-preliminary-report-pdf/' . $case->case_number);
        $aliasResponse->assertStatus(200);
    }

    #[Test]
    public function table_3_strictly_contains_drug_exhibits_and_excludes_non_drug_exhibits(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-DRUG-ONLY',
            'title' => 'Interception at Airport',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Akosua Darko',
        ]);

        // Drug Exhibit
        $drugEx = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Pellets Ingested',
            'contents' => 'White powdery pellets sealed in latex',
            'seizure_quantity' => '65',
            'lab_exhibit_no' => 'LAB-DRUG-001',
            'source_of_exhibits' => 'KIA Terminal 3',
        ]);

        // Vehicle Exhibit (Non-Drug)
        $vehEx = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Vehicles',
            'commodity_name' => 'Toyota Land Cruiser V8',
            'contents' => 'Automobile used for transportation',
            'seizure_quantity' => '1',
            'lab_exhibit_no' => 'LAB-VEH-002',
            'source_of_exhibits' => 'Airport Car Park',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);

        $response->assertStatus(200);
        $response->assertSee('LAB-DRUG-001');
        $response->assertSee('Cocaine Pellets Ingested');
        // Vehicle must NOT appear in Table 3 of strictly drug exhibits
        $response->assertDontSee('Toyota Land Cruiser V8');
        $response->assertDontSee('LAB-VEH-002');

        // Responsive styles check on Table 3 (DESCRIPTION OF EXHIBIT(S))
        $html = $response->getContent();
        $this->assertStringContainsString('doc-table-exhibits', $html);
        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertStringContainsString('word-break: break-word', $html);
        $this->assertStringContainsString('overflow-wrap: break-word', $html);
    }

    #[Test]
    public function table_5_auto_numbers_sequentially(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-MULTI-EXH',
            'title' => 'Multiple Exhibit Seizure',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kwame Nkrumah',
        ]);

        $ex1 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin Rock Slabs',
            'lab_exhibit_no' => 'HER-01',
        ]);

        $ex2 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine Crystals',
            'lab_exhibit_no' => 'METH-02',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must see both exhibits
        $this->assertStringContainsString('HER-01', $content);
        $this->assertStringContainsString('METH-02', $content);

        // Table 5 auto-numbering
        $this->assertMatchesRegularExpression('/<td[^>]*class="[^"]*text-center[^"]*">\s*1\s*<\/td>/', $content);
        $this->assertMatchesRegularExpression('/<td[^>]*class="[^"]*text-center[^"]*">\s*2\s*<\/td>/', $content);
    }

    #[Test]
    public function missing_case_returns_strict_404(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/NON-EXISTENT-CASE-999');

        $response->assertStatus(404);
    }

    #[Test]
    public function generated_preliminary_report_pdf_is_stored_in_suspect_preliminary_reports_table(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS KWAME TETTEH',
            'suspect_name' => 'KWAME TETTEH',
            'unit_name' => 'Lab Analysis',
            'source_of_exhibits' => 'Accra Central Post Office',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine Pellets',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);
        $response->assertStatus(200);

        // Verify stored in suspect_preliminary_reports table
        $reportRecord = \App\Models\SuspectPreliminaryReport::where('case_number', $case->case_number)->first();
        $this->assertNotNull($reportRecord);
        $this->assertStringContainsString('Lab_Analysis/preliminary-report-pdf/' . $case->case_number, $reportRecord->file_path);
        $this->assertStringStartsWith('Preliminary_Report_', $reportRecord->original_filename);

        // Also accessible via legacy SuspectNlrReport model pointing to the same table
        $legacyRecord = \App\Models\SuspectNlrReport::where('case_number', $case->case_number)->first();
        $this->assertNotNull($legacyRecord);
        $this->assertEquals($reportRecord->id, $legacyRecord->id);
    }

    #[Test]
    public function legacy_nlr_report_route_serves_preliminary_report_pdf(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS AMA SERWAA',
            'suspect_name' => 'AMA SERWAA',
            'unit_name' => 'Lab Analysis',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'White Powder',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/nlr-report-pdf/' . $case->case_number);
        $response->assertStatus(200);
        $response->assertSee('PRELIMINARY EXHIBIT ANALYSIS REPORT');
    }

    #[Test]
    public function signatory_page_renders_three_tables_with_headers_on_the_left(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS KOFI ANTWI',
            'suspect_name' => 'KOFI ANTWI',
            'unit_name' => 'Lab Analysis',
            'case_number' => 'NCC-2026-SIGNATORY-001',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cannabis Sativa',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Signatory page indicator
        $this->assertStringContainsString('Page 4 of 4', $html);

        // 2. Table 1: PREPARED/REPORTED BY with left headers
        $this->assertStringContainsString('PREPARED/REPORTED BY', $html);
        $this->assertMatchesRegularExpression('/PREPARED\/REPORTED BY[\s\S]*?<table[\s\S]*?Name[\s\S]*?Rank\/Designation[\s\S]*?Signature[\s\S]*?Date[\s\S]*?<\/table>/', $html);

        // 3. Table 2: APPROVED BY with left headers
        $this->assertStringContainsString('APPROVED BY', $html);
        $this->assertMatchesRegularExpression('/APPROVED BY[\s\S]*?<table[\s\S]*?Name[\s\S]*?Rank\/Designation[\s\S]*?Signature[\s\S]*?Date[\s\S]*?<\/table>/', $html);

        // 4. Table 3: AUTHORIZED BY with left headers
        $this->assertStringContainsString('AUTHORIZED BY', $html);
        $this->assertMatchesRegularExpression('/AUTHORIZED BY[\s\S]*?<table[\s\S]*?Name[\s\S]*?Rank\/Designation[\s\S]*?Signature[\s\S]*?Date[\s\S]*?<\/table>/', $html);

        // 5. Signatory value cells must be empty for manual entry
        $this->assertMatchesRegularExpression('/PREPARED\/REPORTED BY[\s\S]*?Name<\/td>\s*<td[^>]*><\/td>/', $html);
        $this->assertMatchesRegularExpression('/APPROVED BY[\s\S]*?Name<\/td>\s*<td[^>]*><\/td>/', $html);
        $this->assertMatchesRegularExpression('/AUTHORIZED BY[\s\S]*?Name<\/td>\s*<td[^>]*><\/td>/', $html);
    }

    #[Test]
    public function preliminary_report_is_not_stored_if_suspect_has_no_drug_exhibits(): void
    {
        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS KWASI MENSAH - ELECTRONICS ONLY',
            'suspect_name' => 'KWASI MENSAH',
            'unit_name' => 'Lab Analysis',
            'case_number' => 'NCC-2026-NON-DRUG-001',
            'exhibit_category' => 'Electronic Devices',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'case_type' => 'SINGLE',
            'case_number' => $case->case_number,
            'exhibit_number' => 'EXH-ELEC-001',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Apple iPhone 15 Pro Max',
            'contents' => 'Smartphone with broken screen',
        ]);

        // Attempt store/sync for non-drug case
        app(\App\Http\Controllers\CaseReportController::class)->storeOrSyncPreliminaryReport($case);

        // Assert no preliminary report was generated or stored
        $reportRecord = \App\Models\SuspectPreliminaryReport::where('case_number', $case->case_number)->first();
        $this->assertNull($reportRecord, 'Preliminary_Report must not be generated when suspect has no drug exhibits.');
    }

    #[Test]
    public function preliminary_report_renders_unspecified_for_missing_fields_without_mock_fallbacks(): void
    {
        $officer = $this->createLabOfficer();

        $case = ForensicCase::create([
            'case_type' => 'SINGLE',
            'title' => 'REPUBLIC VRS KWAME ADDO',
            'suspect_name' => 'KWAME ADDO',
            'unit_name' => 'Lab Analysis',
            'case_number' => 'NCC-2026-UNSPEC-001',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Unknown Herb',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'case_type' => 'SINGLE',
            'case_number' => $case->case_number,
            'exhibit_number' => 'EXH-DRUG-UNSPEC-01',
            'exhibit_category' => 'Drugs',
        ]);

        $response = $this->actingAs($officer)->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number);
        $response->assertStatus(200);

        $html = $response->getContent();

        // Must NOT see old mock fallback values
        $this->assertStringNotContainsString('Agilent Resolve Raman Analyzer', $html);
        $this->assertStringNotContainsString('Preliminary screening for suspected narcotic drugs or controlled substances', $html);
        $this->assertStringNotContainsString('The exhibit(s) submitted to the Laboratory Analysis Unit', $html);
        $this->assertStringNotContainsString('recording of the physical characteristics of the exhibit(s)', $html);
        $this->assertStringNotContainsString('Exhibits conform to manifest entries and were processed under verified chain of custody', $html);
        $this->assertStringNotContainsString('Preliminary presumptive test indicates characteristic reaction consistent with controlled substance', $html);
        $this->assertStringNotContainsString('Retained under secure custody at the NACOC Forensic Laboratory Evidence Vault', $html);
        $this->assertStringNotContainsString('Colorimetric Chemical Spot Test (Marquis / Cobalt Thiocyanate', $html);
        $this->assertStringNotContainsString('Drug Package', $html);
        $this->assertStringNotContainsString('Plant material / Powder', $html);

        // Must see Unspecified for unpopulated fields
        $this->assertStringContainsString('Unspecified', $html);

        // Must embed or link Arial-Narrow-Font.ttf in @font-face
        $this->assertStringContainsString('font-family: \'Arial Narrow\'', $html);
        $this->assertStringContainsString('Arial-Narrow-Font.ttf', $html);
    }

    #[Test]
    public function case_reference_fetches_from_source_of_exhibits_in_exhibits_table(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-EXH-SRC-01',
            'title' => 'Harbour Cargo Interception',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kofi Mensah',
            'source_of_exhibits' => 'Stale Case Header Source',
        ]);

        // First exhibit has blank/N/A source; second (linked to lab exam) holds exhibits.source_of_exhibits
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Mystery Packet A',
            'lab_exhibit_no' => 'A',
            'source_of_exhibits' => '',
        ]);

        $ex2 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cannabis Blocks',
            'lab_exhibit_no' => 'B',
            'source_of_exhibits' => 'Tema Harbour Shed 7 Arrest',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $ex2->id,
            'exam_reference' => 'NACOC/FSD/LAU/777/2026',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cannabis',
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Reference<span[^>]*>Case Name<\/span>\s*<\/td>\s*<td>\s*Tema Harbour Shed 7 Arrest\s*<\/td>/si',
            $html
        );

        // Also ensure a case whose exhibits all have null source_of_exhibits does not 500
        $caseNoSource = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-NO-SRC-02',
            'title' => 'Case Without Exhibit Source',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Ama Serwaa',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $caseNoSource->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Mystery Sample',
            'source_of_exhibits' => null,
        ]);

        $fallbackHtml = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $caseNoSource->case_number)
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Reference<span[^>]*>Case Name<\/span>\s*<\/td>\s*<td>\s*NCC-2026-NO-SRC-02\s*<\/td>/si',
            $fallbackHtml
        );
    }

    #[Test]
    public function drug_exhibit_without_suspect_preliminary_report_fetches_all_fields_from_db_columns(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-NOSUS-901',
            'title' => 'Unclaimed Narcotic Parcel at Tema Berth 11',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Unassigned (No Suspect)',
            'no_suspect' => true,
            'docket_data' => [
                'no_suspect' => 1,
                'seizures' => [[
                    'exhibit_category' => 'Drugs',
                    'seizure_name' => 'Wrapped Parcels',
                ]],
            ],
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Unassigned (No Suspect)',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Brown Masked Bricks',
            'contents' => 'Tightly compressed dried brownish vegetable matter',
            'description_of_exhibits' => 'Four rectangular parcels wrapped in brown masking tape.',
            'seizure_quantity' => '4',
            'lab_exhibit_no' => 'LAU-01',
            'source_of_exhibits' => 'Tema Berth 11 Container Inspection',
            'source_requesting_unit' => 'Enforcement and Operations Unit',
            'investigating_officer' => 'Insp. Yaw Asante',
            'seizure_date' => '2026-11-10',
            'seizure_time' => '09:45',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/901/2026',
            'purpose_of_examination' => 'Determine whether the seized plant material contains controlled narcotic substances.',
            'preliminary_exam_procedure' => 'Visual inspection, core sampling, and Duquenois-Levine color test.',
            'screening_method' => json_encode(['Duquenois-Levine Reagent', 'TruNarc Raman']),
            'date_received' => '2026-11-10',
            'date_started' => '2026-11-11',
            'date_completed' => '2026-11-11',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cannabis; THC',
            'presumptive_percentage' => '78%; 14%',
            'detected_substances' => [['substance' => 'Cannabis', 'percentage' => '78%'], ['substance' => 'THC', 'percentage' => '14%']],
            'confirmatory_result' => 'NO',
            'sample_weight_gross' => '4.80 kg',
            'sample_weight_reserved' => '4.75 kg',
            'sample_weight_taken' => '0.05 kg',
            'test_methods' => 'Duquenois-Levine Spot Test',
            'specifications' => 'Deep purple-violet organic layer observed.',
            'remark' => 'Exhibits received with seals intact.',
            'disposition_of_exhibit' => 'Retained in NACOC Forensic Evidence Vault.',
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Name<span[^>]*>Case Reference<\/span>\s*<\/td>\s*<td>\s*NO SUSPECT\(S\)\s*<\/td>/si',
            $html
        );
        $this->assertStringContainsString('SUBJECT: PRESUMPTIVE TEST OF EXHIBITS CONDUCTED IN THE PRESENCE OF NO SUSPECT(S)', $html);
        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Reference<span[^>]*>Case Name<\/span>\s*<\/td>\s*<td>\s*Tema Berth 11 Container Inspection\s*<\/td>/si',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Source\/Requesting Unit\s*<\/td>\s*<td>\s*Enforcement and Operations Unit\s*<\/td>/si',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Investigating Officer\s*<\/td>\s*<td>\s*Insp\. Yaw Asante\s*<\/td>/si',
            $html
        );
        $this->assertStringContainsString('NACOC/FSD/LAU/901/2026', $html);
        $this->assertStringContainsString('10 November 2026 0945 Hours', $html);
        $this->assertStringContainsString('11 November 2026', $html);
        $this->assertStringContainsString('Determine whether the seized plant material contains controlled narcotic substances.', $html);
        $this->assertStringContainsString('Four rectangular parcels wrapped in brown masking tape.', $html);
        $this->assertStringContainsString('LAU-01', $html);
        $this->assertStringContainsString('Brown Masked Bricks', $html);
        $this->assertStringContainsString('Tightly compressed dried brownish vegetable matter', $html);
        $this->assertStringContainsString('4.80 kg', $html);
        $this->assertStringContainsString('4.75 kg', $html);
        $this->assertStringContainsString('Visual inspection, core sampling, and Duquenois-Levine color test.', $html);
        $this->assertStringContainsString('Duquenois-Levine Reagent, TruNarc Raman', $html);
        $this->assertStringContainsString('Duquenois-Levine Spot Test', $html);
        $this->assertStringContainsString('Cannabis detected, THC detected', $html);
        $this->assertStringContainsString('Presumptive indication of the presence of Cannabis; THC', $html);
        $this->assertStringContainsString('Exhibits received with seals intact.', $html);
        $this->assertStringContainsString('Deep purple-violet organic layer observed.', $html);
        $this->assertStringContainsString('Retained in NACOC Forensic Evidence Vault.', $html);
    }

    #[Test]
    public function investigating_officer_column_does_not_display_arresting_officer(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-IO-TEST-01',
            'title' => 'Investigating Officer Separation Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kofi Mensah',
            'arresting_officer' => 'Detective Sgt. Kwesi Appiah (ID: NACOC-8821)',
            'submitting_officer' => 'Officer Evans Tetteh',
            'seizure_officer' => 'Officer Seizure Doe',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Kofi Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'White Powder',
            'lab_exhibit_no' => 'A',
            'source_of_exhibits' => 'Kotoka Terminal 3',
            'investigating_officer' => null, // Explicitly null to verify no fallback to arresting officer
            'seizure_officer' => 'Officer Seizure Doe',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/999/2026',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cocaine',
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        // Must display Unspecified, NOT the arresting officer
        $this->assertMatchesRegularExpression(
            '/<td>\s*Investigating Officer\s*<\/td>\s*<td>\s*Unspecified\s*<\/td>/si',
            $html
        );
        $this->assertStringNotContainsString('Detective Sgt. Kwesi Appiah', $html);
        $this->assertStringNotContainsString('NACOC-8821', $html);

        // Now set investigating_officer explicitly and verify it is displayed
        $exhibit->update(['investigating_officer' => 'D/Inspector Kwabena Darko (ID: NACOC-IO-101)']);

        $htmlWithIO = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td>\s*Investigating Officer\s*<\/td>\s*<td>\s*D\/Inspector Kwabena Darko \(ID: NACOC-IO-101\)\s*<\/td>/si',
            $htmlWithIO
        );
    }

    #[Test]
    public function table_5_strictly_maps_interpretation_to_presumptive_presence_and_observation_to_detected_substances(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-TABLE5-MAP-01',
            'title' => 'Table 5 Mapping Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Abena Osei',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Abena Osei',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Brownish Rock Substance',
            'lab_exhibit_no' => 'LAB-EX-99',
            'source_of_exhibits' => 'Aflao Border Post',
            'investigating_officer' => 'Officer Table5 IO',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/T5/2026',
            'test_methods' => 'Scott Reagent Chemical Spot Test & GC-MS',
            'detected_substances' => [
                ['substance' => 'Cocaine Hydrochloride', 'percentage' => '84.6%'],
                ['substance' => 'Levamisole Adulterant', 'percentage' => '12.4%'],
            ],
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Presumptive Positive: Cocaine HCl',
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        // Observation/Results must strictly format as '[drug] detected' without percentage
        $this->assertStringContainsString('Cocaine Hydrochloride detected, Levamisole Adulterant detected', $html);
        $this->assertStringNotContainsString('84.6%', $html);

        // Interpretation must strictly format as 'Presumptive indication of the presence of drug'
        $this->assertStringContainsString('Presumptive indication of the presence of Cocaine HCl', $html);

        // Responsive text-overflow styles check on Table 5
        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertStringContainsString('word-break: break-word', $html);
        $this->assertStringContainsString('overflow-wrap: break-word', $html);
    }

    #[Test]
    public function laboratory_examination_results_deletes_substance_identification_and_separates_detected_substances_and_percentage(): void
    {
        // 1. Column structure assertions on MySQL table
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('laboratory_examination_results', 'substance_identification'),
            'Column substance_identification must be deleted from laboratory_examination_results table'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('laboratory_examination_results', 'detected_subtances_percentage'),
            'Column detected_subtances_percentage must exist in laboratory_examination_results table'
        );
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('laboratory_examination_results', 'detected_substances_percentage'),
            'Column detected_substances_percentage must not exist as a duplicate column in laboratory_examination_results table'
        );

        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-SEP-TEST-01',
            'title' => 'Separated Substances Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kwame Mensah',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Kwame Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Compressed Powder',
            'lab_exhibit_no' => 'SEP-01',
            'source_of_exhibits' => 'Accra Central',
        ]);

        // Create with separated substances and percentage
        $lab = LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/SEP/2026',
            'detected_substances' => ['Heroin Hydrochloride', 'Paracetamol'],
            'detected_subtances_percentage' => ['75%', '25%'],
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Heroin',
            'has_saved' => true,
        ]);

        // Direct DB inspection to verify neither stores both
        $rawDb = \Illuminate\Support\Facades\DB::table('laboratory_examination_results')->where('id', $lab->id)->first();
        $this->assertNotNull($rawDb);
        $this->assertStringContainsString('Heroin Hydrochloride', $rawDb->detected_substances);
        $this->assertStringNotContainsString('75%', $rawDb->detected_substances);
        $this->assertStringContainsString('75%', $rawDb->detected_subtances_percentage);
        $this->assertStringNotContainsString('Heroin Hydrochloride', $rawDb->detected_subtances_percentage);

        // PDF Table 5 renders detected substances strictly as '[drug] detected' without percentage
        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Heroin Hydrochloride detected, Paracetamol detected', $html);
        $this->assertStringContainsString('Presumptive indication of the presence of Heroin', $html);

        // Review page must also load without Unknown column 'substance_identification' QueryException
        $reviewHtml = $this->actingAs($officer)
            ->get('/Lab_Analysis/cases/' . $case->case_number . '/review')
            ->assertOk()
            ->getContent();
        $this->assertNotEmpty($reviewHtml);
    }

    #[Test]
    public function observation_results_strictly_fetches_from_laboratory_examination_results_detected_substances(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-STRICT-DET-01',
            'title' => 'Strict Detected Substances Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kweku Duah',
            'docket_data' => [
                'seizures' => [
                    ['commodity_name' => 'Suspected Methamphetamine Crystals', 'detected_substances' => ['Methamphetamine']],
                ],
                'lab_examination' => ['detected_substances' => ['Methamphetamine']],
            ],
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Kweku Duah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Suspected Methamphetamine Crystals',
            'lab_exhibit_no' => 'METH-01',
            'source_of_exhibits' => 'Tema Harbour',
        ]);

        // LaboratoryExaminationResult has empty detected_substances
        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/STRICT/2026',
            'detected_substances' => [],
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Methamphetamine',
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        // Must NOT fallback to docket Methamphetamine in Observation/Results column
        $this->assertStringNotContainsString('Methamphetamine detected', $html);
        $this->assertStringContainsString('Unspecified', $html);
    }

    #[Test]
    public function remarks_specific_remarks_and_disposition_section_renders_responsive_classes_and_multiline_content(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-RESPONSIVE-REM',
            'title' => 'State vs Kwesi Responsive',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kwesi Responsive',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Kwesi Responsive',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'White Powder Slabs',
            'lab_exhibit_no' => 'RESP-01',
            'source_of_exhibits' => 'Kotoka International Airport',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/RESP/2026',
            'remark' => "Line 1: Evidence received intact.\nLine 2: Chemical spot tests verified.",
            'specifications' => "Standard specifications compliant with ISO/IEC 17025 protocols.",
            'disposition_of_exhibit' => "Transferred to central laboratory cold storage vault under custody receipt.",
            'has_saved' => true,
        ]);

        $html = $this->actingAs($officer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number)
            ->assertOk()
            ->getContent();

        // Responsive container & item classes
        $this->assertStringContainsString('doc-remarks-section', $html);
        $this->assertStringContainsString('doc-remark-item', $html);
        $this->assertStringContainsString('doc-remark-title', $html);
        $this->assertStringContainsString('doc-remark-body', $html);

        // Responsive CSS properties for word wrapping & overflow prevention
        $this->assertStringContainsString('word-break: break-word', $html);
        $this->assertStringContainsString('overflow-wrap: break-word', $html);
        $this->assertStringContainsString('white-space: pre-wrap', $html);

        // Section titles
        $this->assertStringContainsString('REMARKS', $html);
        $this->assertStringContainsString('Specific Remarks', $html);
        $this->assertStringContainsString('DISPOSITION OF EXHIBIT(S)', $html);

        // Content verification with line break handling
        $this->assertStringContainsString('Evidence received intact.', $html);
        $this->assertStringContainsString('Chemical spot tests verified.', $html);
        $this->assertStringContainsString('ISO/IEC 17025', $html);
        $this->assertStringContainsString('Transferred to central laboratory cold storage vault', $html);
    }
}

