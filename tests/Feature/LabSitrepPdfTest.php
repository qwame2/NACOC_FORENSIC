<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\DrugScreening;
use App\Models\SuspectGsaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabSitrepPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_lab_analysis_report(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/report');
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $pdfResponse->assertStatus(200);
    }

    public function test_lab_report_has_cover_page_matching_multi_dpf_pdf(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. A4 Portrait Media & Styling
        $this->assertStringContainsString('size: A4 portrait;', $content);
        $this->assertStringContainsString('cover-page', $content);
        $this->assertStringContainsString('width: 210mm', $content);
        $this->assertStringContainsString('height: 297mm', $content);

        // 2. Cover Page Header & Crest
        $this->assertStringContainsString('REPUBLIC OF GHANA', $content);
        $this->assertStringContainsString('ISO/IEC 17025', $content);
        $this->assertStringContainsString('NARCOTICS CONTROL COMMISSION', $content);
        $this->assertStringContainsString('FORENSIC DEPARTMENT', $content);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $content);
        $this->assertStringContainsString('NACOC_LOGO.PNG', $content);

        // 3. Cover Page Title & Notice
        $this->assertStringContainsString('OFFICIAL FORENSIC DRUG ANALYSIS', $content);
        $this->assertStringContainsString('CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD', $content);
        $this->assertStringContainsString('ALL DRUG-RELATED CASES &amp; SUSPECT REGISTRY', $content);
    }

    public function test_lab_report_contains_all_uploaded_file_information_and_suspects(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case1 = MultipleSuspectCase::create([
            'case_number' => 'POST-OFFICE-001',
            'user_id' => $user->id,
            'raid_location' => 'Post Office',
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $case1->id,
            'suspect_name' => 'Emmanuel Agyare',
            'status' => 'At Large',
            'role' => 'Primary Suspect',
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $case1->id,
            'suspect_name' => 'Heston Adjei',
            'status' => 'At Large',
            'role' => 'Co-Suspect',
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'Multiple',
            'multiple_suspect_case_id' => $case1->id,
            'exhibit_number' => 'EX-PO-001',
            'commodity_name' => 'Prohibited items',
            'seizure_quantity' => '2 bottles',
            'source_of_exhibits' => 'Post Office',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'Preliminary analysis was inconclusive',
        ]);
        \App\Models\LaboratoryExaminationResult::create([
            'multiple_suspect_case_id' => $case1->id,
            'findings' => 'Preliminary analysis was inconclusive',
        ]);

        $case2 = MultipleSuspectCase::create([
            'case_number' => 'AV-DHL-002',
            'user_id' => $user->id,
            'raid_location' => 'Aviance (DHL)',
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $case2->id,
            'suspect_name' => 'George Ayisi',
            'role' => 'Primary Suspect',
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $case2->id,
            'suspect_name' => 'Lois Ayisi',
            'role' => 'Co-Suspect',
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'Multiple',
            'multiple_suspect_case_id' => $case2->id,
            'exhibit_number' => 'EX-AV-002',
            'commodity_name' => 'Suspected Hashish',
            'source_of_exhibits' => 'Aviance (DHL)',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'detected the presence of THC',
        ]);
        \App\Models\LaboratoryExaminationResult::create([
            'multiple_suspect_case_id' => $case2->id,
            'findings' => 'detected the presence of THC',
        ]);

        $case3 = SingleSuspectCase::create([
            'case_number' => 'SWISS-003',
            'user_id' => $user->id,
            'suspect_name' => 'Swissport Suspect',
            'raid_location' => 'Swissport',
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case3->id,
            'exhibit_number' => 'EX-SW-003',
            'commodity_name' => 'Suspected Apeta Trust Sirop',
            'seizure_quantity' => '3 bottles',
            'source_of_exhibits' => 'Swissport',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'detected the presence of Codeine in Tema Port Unit sample',
        ]);
        \App\Models\LaboratoryExaminationResult::create([
            'single_suspect_case_id' => $case3->id,
            'findings' => 'detected the presence of Codeine in Tema Port Unit sample',
        ]);

        $suspectScreen = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $case1->id,
            'suspect_name' => 'Hah tabulu',
            'role' => 'Suspect',
        ]);
        \App\Models\DrugScreening::create([
            'suspect_id' => $suspectScreen->id,
            'screening_date' => now(),
            'purpose' => 'Investigative',
            'result' => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Seizures Table / Preliminary Forensic Examination
        $this->assertStringContainsString('Post Office', $content);
        $this->assertStringContainsString('Emmanuel Agyare', $content);
        $this->assertStringContainsString('Heston Adjei', $content);
        $this->assertStringContainsString('At Large', $content);
        $this->assertStringContainsString('2 bottles', $content);
        $this->assertStringContainsString('Preliminary analysis was inconclusive', $content);
        $this->assertStringContainsString('Prohibited items', $content);

        $this->assertStringContainsString('Aviance (DHL)', $content);
        $this->assertStringContainsString('Suspected Hashish', $content);
        $this->assertStringContainsString('detected the presence of THC', $content);

        $this->assertStringContainsString('George Ayisi', $content);
        $this->assertStringContainsString('Lois Ayisi', $content);

        $this->assertStringContainsString('Swissport', $content);
        $this->assertStringContainsString('Suspected Apeta Trust Sirop', $content);
        $this->assertStringContainsString('3 bottles', $content);

        $this->assertStringContainsString('detected the presence of Codeine', $content);

        // 2. Section 02: Drug Screening of Suspects
        $this->assertStringContainsString('Drug Screening of Suspect(s)', $content);
        $this->assertStringContainsString('Hah tabulu', $content);
        $this->assertStringContainsString('Investigative', $content);
        $this->assertStringContainsString('No drug(s) detected', $content);

        // 3. Section 03: GSA Submissions
        $this->assertStringContainsString('Ghana Standards Authority (GSA) Submissions', $content);
        $this->assertStringContainsString('No Ghana Standards Authority (GSA) submissions recorded', $content);
    }

    public function test_dual_official_signatures_rendered_on_dossier(): void
    {
        $head = User::factory()->create([
            'fullname' => 'John Mensah',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $deptHead = User::factory()->create([
            'fullname' => 'Anita Addo',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($head)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('John Mensah', $content);
        $this->assertStringContainsString('Head of Forensic Department', $content);
        $this->assertStringContainsString('Anita Addo', $content);
        $this->assertStringContainsString('Director of Intel &amp; Investigation', $content);
        $this->assertStringContainsString('Name: ____________________', $content);
        $this->assertStringContainsString('Date:', $content);
        $this->assertStringContainsString(now()->format('d F Y'), $content);
        $this->assertStringNotContainsString('Signature &amp; Date:', $content);
        $this->assertStringNotContainsString('Electronically Signed &amp; Validated', $content);
        $this->assertStringNotContainsString('alt="Signature"', $content);
    }

    public function test_general_report_url_routes_to_lab_report_for_lab_officer(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Report');
        $response->assertStatus(200);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $response->getContent());
        $this->assertStringContainsString('Preliminary Forensic Examination &amp; Drug Testing Log', $response->getContent());
    }

    public function test_filtering_by_search_query(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case1 = SingleSuspectCase::create([
            'case_number' => 'TEST/HASH/001',
            'suspect_name' => 'Hashish Suspect',
            'status' => 'In Custody',
            'user_id' => $user->id,
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case1->id,
            'exhibit_number' => 'EX-HASH-001',
            'commodity_name' => 'Suspected Hashish',
            'exhibit_category' => 'Narcotic Drug',
        ]);

        $case2 = SingleSuspectCase::create([
            'case_number' => 'TEST/APETA/002',
            'suspect_name' => 'Apeta Suspect',
            'status' => 'In Custody',
            'user_id' => $user->id,
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case2->id,
            'exhibit_number' => 'EX-APETA-002',
            'commodity_name' => 'Apeta Trust',
            'exhibit_category' => 'Narcotic Drug',
        ]);

        // Search for 'Hashish'
        $response = $this->actingAs($user)->get('/Lab_Analysis/report?search=Hashish');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Suspected Hashish', $content);
        $this->assertStringNotContainsString('Apeta Trust', $content);
    }

    public function test_source_of_exhibits_fetched_from_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'TEST/LAB/2026/099',
            'suspect_name' => 'Kofi Boateng',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Suspected Cocaine',
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EX-2026-999',
            'commodity_name' => 'Suspected Cocaine Slabs',
            'source_of_exhibits' => 'Kotoka Cargo Shed Bay 4',
            'exhibit_category' => 'Narcotic Drug',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Kotoka Cargo Shed Bay 4', $content);
        $this->assertStringContainsString('Kofi Boateng', $content);
    }

    public function test_gsa_submissions_fetch_gsa_columns_from_suspect_gsa_reports_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'MC-GSA-99',
            'user_id' => $user->id,
        ]);

        $suspect = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Kweku GSA',
            'role' => 'Primary Suspect',
        ]);

        SuspectGsaReport::create([
            'case_suspect_id'          => $suspect->id,
            'multiple_suspect_case_id' => $multiCase->id,
            'gsa_case_reference'       => 'GSA-DKT-9988',
            'gsa_lab_number'           => 'GSA-LAB-7766',
            'gsa_status'               => 'Certified Analysis In-Progress',
            'original_filename'        => 'Heroin Sample Packet A',
            'stored_filename'          => 'test_sample.pdf',
            'file_path'                => 'reports/test_sample.pdf',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('GSA-DKT-9988', $content);
        $this->assertStringContainsString('GSA-LAB-7766', $content);
        $this->assertStringContainsString('Certified Analysis In-Progress', $content);
        $this->assertStringNotContainsString('bg-amber-100', $content);
        $this->assertStringNotContainsString('Heroin Sample Packet A', $content);
        $this->assertStringContainsString('Case Reference', $content);
        $this->assertStringNotContainsString('Case Reference / Docket', $content);
        $this->assertStringNotContainsString('Submission Date', $content);
    }

    public function test_gsa_submissions_renders_exhibit_type_column_from_database_and_not_original_filename(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'MC-GSA-EXH-01',
            'user_id' => $user->id,
        ]);

        $suspect = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'GSA Exhibit Suspect',
            'role' => 'Primary Suspect',
        ]);

        SuspectGsaReport::create([
            'case_suspect_id'          => $suspect->id,
            'multiple_suspect_case_id' => $multiCase->id,
            'gsa_case_reference'       => 'GSA-DKT-1122',
            'gsa_lab_number'           => 'GSA-LAB-3344',
            'exhibit_type'             => 'Cocaine Hydrochloride and Heroin',
            'original_filename'        => 'scanned_cert_file_1234.pdf',
            'stored_filename'          => 'stored_1234.pdf',
            'file_path'                => 'reports/stored_1234.pdf',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Cocaine Hydrochloride and Heroin', $content);
        $this->assertStringNotContainsString('scanned_cert_file_1234.pdf', $content);

        // Check report page also displays exhibit_type and no bg-amber-100 container for GSA status
        $reportResponse = $this->actingAs($user)->get('/Lab_Analysis/report');
        $reportResponse->assertStatus(200);
        $reportContent = $reportResponse->getContent();
        $this->assertStringContainsString('Cocaine Hydrochloride and Heroin', $reportContent);
        $this->assertStringNotContainsString('bg-amber-100', $reportContent);
    }

    public function test_drug_screening_results_column_displays_negative_and_positive_without_border_container(): void
    {
        $user = User::factory()->create([
            'fullname' => 'Test Lab Officer 2',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'MC-DS-55',
            'user_id' => $user->id,
        ]);

        $suspect1 = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect Neg Test',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect Pos Test',
            'role' => 'Co-Suspect',
        ]);

        \App\Models\DrugScreening::create([
            'suspect_id' => $suspect1->id,
            'purpose' => 'Detox Intake',
            'screening_date' => now(),
            'result' => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        \App\Models\DrugScreening::create([
            'suspect_id' => $suspect2->id,
            'purpose' => 'Detox Intake',
            'screening_date' => now(),
            'result' => 'Positive',
            'substances_found' => 'Heroin, Cocaine',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('No drug(s) detected (NEGATIVE)', $content);
        $this->assertStringContainsString('Heroin, Cocaine (POSITIVE)', $content);
        $this->assertStringNotContainsString('rounded-full text-[8px] font-black uppercase tracking-wider bg-rose-100', $content);
    }

    public function test_preliminary_examination_log_displays_quantity_with_package_type(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'TEST/QTY/PKG/001',
            'suspect_name' => 'Akosua Mensah',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Suspected Cough Syrup',
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EX-PKG-001',
            'commodity_name' => 'Codeine Syrup Bottles',
            'seizure_quantity' => '3',
            'seizure_packaging' => 'bottles',
            'source_of_exhibits' => 'Aflao Border Checkpoint',
            'exhibit_category' => 'Narcotic Drug',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('3 bottles', $content);
        $this->assertStringContainsString('Aflao Border Checkpoint', $content);
    }

    public function test_preliminary_examination_log_status_remarks_fetches_from_exhibit_remark_in_exhibit_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'TEST/REMARK/001',
            'suspect_name' => 'Kojo Darko',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Suspected Cannabis Slabs',
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EX-REM-001',
            'commodity_name' => 'Compressed Cannabis Slabs',
            'seizure_quantity' => '4',
            'seizure_packaging' => 'slabs',
            'source_of_exhibits' => 'Kotoka Cargo Shed',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'Colorimetric Fast Blue B salt reagent confirmed strong positive THC reaction.',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must display the exhibit_remark from the exhibit table in the database
        $this->assertStringContainsString('Colorimetric Fast Blue B salt reagent confirmed strong positive THC reaction.', $content);

        // Container and Lab Code / Verified row must not be rendered in the status/remarks column
        $this->assertStringNotContainsString('Lab Code: ', $content);
        $this->assertStringNotContainsString('>Verified</span>', $content);
    }

    public function test_running_header_and_footer_rendered_on_dossier_pages(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Header
        $this->assertStringContainsString('NACOC FORENSIC', $content);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $content);
        $this->assertStringContainsString('SITREP', $content);
        $this->assertStringContainsString('CONFIDENTIAL', $content);
        $this->assertStringContainsString('PAGE 2 OF', $content);

        // 2. Footer
        $this->assertStringContainsString('NACOC / LABORATORY ANALYSIS UNIT', $content);
        $this->assertTrue(
            str_contains($content, 'Official Forensic Dossier') || str_contains($content, 'Official Situational Dossier'),
            'Expected footer to contain Official Forensic Dossier'
        );
        $this->assertStringContainsString('STRICTLY CONFIDENTIAL', $content);
        $this->assertStringContainsString('Page 2 of', $content);

        // 3. Server-side pre-rendered pagination (no JS paginator)
        $this->assertStringNotContainsString('autoPaginateSitrepReport', $content);
        $this->assertStringNotContainsString('renumberAllPages', $content);
        $this->assertStringNotContainsString('sitrepPristineSnapshot', $content);
        $this->assertStringContainsString('window.print()', $content);
    }

    public function test_lab_report_is_not_restricted_to_two_pages_when_content_expands(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // Create cases with exhibits to exceed single-page capacity
        for ($i = 1; $i <= 18; $i++) {
            $case = SingleSuspectCase::create([
                'case_number' => "TEST/SITREP/MULTI/{$i}",
                'suspect_name' => "Suspect {$i}",
                'status' => 'In Custody',
                'user_id' => $user->id,
                'seizure_name_drug' => 'Suspected Narcotic',
            ]);

            \App\Models\Exhibit::create([
                'case_type' => 'Single',
                'single_suspect_case_id' => $case->id,
                'exhibit_number' => "EX-MULTI-{$i}",
                'commodity_name' => "Compressed Slab {$i}",
                'seizure_quantity' => "{$i} slabs",
                'source_of_exhibits' => 'Border Checkpoint',
                'exhibit_category' => 'Narcotic Drug',
            ]);
        }

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must dynamically generate at least 3 pages (Page 1 Cover, Page 2 Seizures, Page 3+ Continuation/Screening/Signatures)
        $this->assertStringContainsString('PAGE 3 OF', $content);
        $this->assertStringContainsString('Page 3 of', $content);
        $this->assertStringNotContainsString('PAGE 2 OF 2', $content);
        $this->assertStringNotContainsString('Page 1 of 2', $content);
    }

    public function test_report_type_label_shows_period_type_without_situational(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // 1. Weekly report
        $resWeekly = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf?period=weekly');
        $resWeekly->assertStatus(200);
        $this->assertStringContainsString('WEEKLY DRUG FORENSIC REPORT', $resWeekly->getContent());
        $this->assertStringNotContainsString('SITUATIONAL DRUG FORENSIC REPORT', $resWeekly->getContent());

        // 2. Monthly report
        $resMonthly = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf?period=monthly');
        $resMonthly->assertStatus(200);
        $this->assertStringContainsString('MONTHLY DRUG FORENSIC REPORT', $resMonthly->getContent());
        $this->assertStringNotContainsString('SITUATIONAL DRUG FORENSIC REPORT', $resMonthly->getContent());

        // 3. Annual report
        $resAnnual = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf?period=annual');
        $resAnnual->assertStatus(200);
        $this->assertStringContainsString('ANNUAL DRUG FORENSIC REPORT', $resAnnual->getContent());
        $this->assertStringNotContainsString('ANNUAL SITUATIONAL REPORT', $resAnnual->getContent());

        // 4. Annually report alias
        $resAnnually = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf?period=annually');
        $resAnnually->assertStatus(200);
        $this->assertStringContainsString('ANNUAL DRUG FORENSIC REPORT', $resAnnually->getContent());

        // 5. General report
        $resGeneral = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf?period=general');
        $resGeneral->assertStatus(200);
        $this->assertStringContainsString('GENERAL DRUG FORENSIC REPORT', $resGeneral->getContent());
        $this->assertStringNotContainsString('GENERAL SITUATIONAL FORENSIC REPORT', $resGeneral->getContent());

        // 6. Default (no params) report shows general
        $resDefault = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $resDefault->assertStatus(200);
        $this->assertStringContainsString('GENERAL DRUG FORENSIC REPORT', $resDefault->getContent());
        $this->assertStringNotContainsString('SITUATIONAL DRUG FORENSIC REPORT', $resDefault->getContent());
    }

    public function test_subsequent_sections_share_same_page_when_space_available(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // 2 Seizures
        for ($i = 1; $i <= 2; $i++) {
            $case = SingleSuspectCase::create([
                'case_number' => "TEST/SHARED/{$i}",
                'suspect_name' => "Shared Suspect {$i}",
                'status' => 'In Custody',
                'user_id' => $user->id,
                'seizure_name_drug' => 'Suspected Narcotic',
            ]);
            \App\Models\Exhibit::create([
                'case_type' => 'Single',
                'single_suspect_case_id' => $case->id,
                'exhibit_number' => "EX-SHARED-{$i}",
                'commodity_name' => "Slab {$i}",
                'seizure_quantity' => '1',
                'source_of_exhibits' => 'Harbour',
                'exhibit_category' => 'Narcotic Drug',
            ]);
        }

        // 1 Drug Screening
        $suspectScreen = \App\Models\CaseSuspect::create([
            'case_type' => 'Single',
            'case_id' => 9991,
            'suspect_name' => 'Screened Suspect',
            'role' => 'Suspect',
        ]);
        \App\Models\DrugScreening::create([
            'suspect_id' => $suspectScreen->id,
            'screening_date' => now(),
            'purpose' => 'Investigative',
            'result' => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        // 1 GSA submission
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'MC-SHARED-1',
            'user_id' => $user->id,
        ]);
        $suspectGsa = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Kweku GSA',
            'role' => 'Primary Suspect',
        ]);
        \App\Models\SuspectGsaReport::create([
            'case_suspect_id' => $suspectGsa->id,
            'multiple_suspect_case_id' => $multiCase->id,
            'gsa_case_reference' => 'MC-SHARED-1',
            'exhibit_type' => 'White Powder',
            'gsa_lab_number' => 'GSA-LAB-999',
            'gsa_status' => 'Testing in progress',
            'original_filename' => 'gsa.pdf',
            'stored_filename' => 'gsa.pdf',
            'file_path' => 'reports/gsa.pdf',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // All 3 sections and signatures should comfortably fit on Page 2 (2 pages total: Cover + Dossier)
        $this->assertStringContainsString('Page 1 of <span class="doc-total-pages">2</span>', $content);
        $this->assertStringContainsString('PAGE 2 OF 2', $content);
        $this->assertStringContainsString('Page 2 of 2', $content);
        $this->assertStringContainsString('Preliminary Forensic Examination &amp; Drug Testing Log', $content);
        $this->assertStringContainsString('Drug Screening of Suspect(s)', $content);
        $this->assertStringContainsString('Ghana Standards Authority (GSA) Submissions', $content);
        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('Head of Forensic Department', $content);

        // Continuation headers should not appear because all fit on Page 2
        $this->assertStringNotContainsString('(Continued)', $content);
    }

    public function test_table_continuation_repeats_headers_and_labels_across_pages(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // 25 Seizures with long remarks to force continuation onto subsequent pages
        for ($i = 1; $i <= 25; $i++) {
            $case = SingleSuspectCase::create([
                'case_number' => "TEST/CONT/{$i}",
                'suspect_name' => "Continuation Suspect {$i}",
                'status' => 'In Custody',
                'user_id' => $user->id,
                'seizure_name_drug' => 'Suspected Cannabis',
            ]);
            \App\Models\Exhibit::create([
                'case_type' => 'Single',
                'single_suspect_case_id' => $case->id,
                'exhibit_number' => "EX-CONT-{$i}",
                'commodity_name' => "Compressed Cannabis Slab {$i}",
                'seizure_quantity' => "{$i} packs",
                'source_of_exhibits' => 'Border Point',
                'exhibit_category' => 'Narcotic Drug',
                'exhibit_remark' => "Detailed laboratory preliminary screening remark for record {$i} confirming positive chemical reaction.",
            ]);
        }

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Verifies continuation label on subsequent page
        $this->assertStringContainsString('Preliminary Forensic Examination &amp; Drug Testing Log (Continued)', $content);

        // Verifies column headings repeat
        $headerCount = substr_count($content, '<th class="py-2 px-2 w-[11%] border-r border-slate-200">Date Of Seizure</th>');
        $this->assertGreaterThanOrEqual(2, $headerCount, 'Expected Date Of Seizure header to repeat on continuation pages');

        // Total page count must be accurate (Cover + at least 2 dossier pages)
        $this->assertStringContainsString('PAGE 3 OF', $content);
        $this->assertStringContainsString('Page 3 of', $content);

        // Signatures must appear on the final page
        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('Head of Forensic Department', $content);
    }

    public function test_consistent_a4_dimensions_and_bottom_anchored_footer_across_all_pages(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // Create a single case with short content to test short content page
        $case = SingleSuspectCase::create([
            'case_number' => 'SITREP/SHORT/001',
            'suspect_name' => 'Single Suspect',
            'status' => 'In Custody',
            'user_id' => $user->id,
            'seizure_name_drug' => 'Heroin',
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-SHORT-001',
            'commodity_name' => 'Brown Powder',
            'seizure_quantity' => '1 sachet',
            'source_of_exhibits' => 'Airport',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'Presumptive positive.',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Every page container enforces A4 portrait height
        $this->assertStringContainsString('height: 297mm;', $content);
        $this->assertStringContainsString('max-height: 297mm;', $content);
        $this->assertStringContainsString('min-height: 297mm;', $content);
        $this->assertStringContainsString('overflow: hidden;', $content);
        $this->assertStringContainsString('box-sizing: border-box;', $content);

        // 2. Full-height stretching inner wrapper
        $this->assertStringContainsString('page-content-wrapper', $content);

        // 3. Bottom-anchored footer with margin-top auto
        $this->assertStringContainsString('page-footer-block', $content);
        $this->assertStringContainsString('margin-top: auto', $content);

        // 4. Print media rules enforce matching A4 297mm height & margin-top auto
        $this->assertStringContainsString('@media print', $content);
        $this->assertStringContainsString('height: 297mm !important;', $content);
        $this->assertStringContainsString('max-height: 297mm !important;', $content);
        $this->assertStringContainsString('margin-top: auto !important;', $content);
    }

    public function test_signatories_consistently_anchored_above_footer_on_final_page_with_stable_alignment(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // Scenario A: Short content (only 1 case)
        $case = SingleSuspectCase::create([
            'case_number' => 'SIG-TEST-001',
            'user_id' => $user->id,
            'suspect_name' => 'Kofi Boateng',
            'status' => 'In Custody',
            'exhibit_type' => 'Cannabis',
            'quantity' => '1 slab',
            'weight' => '500g',
            'exhibit_remark' => 'Presumptive positive for THC.',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Signatories CSS: auto margin pushes to bottom, flex-shrink 0, avoid-break
        $this->assertStringContainsString('.section-signatures {', $content);
        $this->assertStringContainsString('margin-top: auto;', $content);
        $this->assertStringContainsString('page-break-inside: avoid !important;', $content);
        $this->assertStringContainsString('break-inside: avoid !important;', $content);

        // 2. Proximity to footer: footer following signatures resets margin-top to 0 to maintain consistent distance
        $this->assertStringContainsString('.section-signatures + .page-footer-block {', $content);
        $this->assertStringContainsString('margin-top: 0 !important;', $content);

        // 3. Print media rules also lock signatories above footer
        $this->assertStringContainsString('.section-signatures {', $content);
        $this->assertStringContainsString('margin-top: auto !important;', $content);

        // 4. Complete signatory block details present
        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('Head of Forensic Department', $content);
        $this->assertStringContainsString('Director of Intel &amp; Investigation', $content);
        $this->assertStringContainsString('Date:', $content);
        $this->assertStringContainsString(now()->format('d F Y'), $content);
        $this->assertStringNotContainsString('Signature &amp; Date:', $content);

        // 5. Signatories only rendered once in document (on the final page)
        $sigDivMatches = substr_count($content, '<div class="section-signatures');
        $this->assertEquals(1, $sigDivMatches, 'Signatories HTML element should only be rendered once in the entire document');

        // Scenario B: Larger dataset spanning multiple pages
        for ($i = 2; $i <= 18; $i++) {
            SingleSuspectCase::create([
                'case_number' => "SIG-TEST-00{$i}",
                'user_id' => $user->id,
                'suspect_name' => "Suspect {$i}",
                'status' => 'In Custody',
                'exhibit_type' => 'Cocaine',
                'quantity' => '2 packets',
                'weight' => '200g',
                'exhibit_remark' => 'Sample analyzed with FTIR spectroscopy.',
            ]);
        }

        $multiResponse = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $multiResponse->assertStatus(200);
        $multiContent = $multiResponse->getContent();

        // Signatures still rendered exactly once on the multi-page report
        $multiSigDivMatches = substr_count($multiContent, '<div class="section-signatures');
        $this->assertEquals(1, $multiSigDivMatches, 'Multi-page document should still have signatories only once on the final page');
    }

    public function test_director_of_intelligence_and_investigation_signatory_rendered(): void
    {
        $labHead = User::factory()->create([
            'fullname' => 'John Mensah',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $forensicHead = User::factory()->create([
            'fullname' => 'Anita Addo',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $intelDirector = User::factory()->create([
            'fullname' => 'DCOP K. Osei-Bonsu',
            'unit' => 'Intelligence & Investigation Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($labHead)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. All 3 officials rendered in the 3-column signature block
        $this->assertStringContainsString('grid-cols-3', $content);
        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('John Mensah', $content);

        $this->assertStringContainsString('Head of Forensic Department', $content);
        $this->assertStringContainsString('Anita Addo', $content);

        $this->assertStringContainsString('Director of Intel &amp; Investigation', $content);
        $this->assertStringContainsString('DCOP K. Osei-Bonsu', $content);
        $this->assertStringContainsString('Director of Intelligence &amp; Investigation, NACOC', $content);
        $this->assertStringNotContainsString('alt="Signature"', $content);
    }

    public function test_remarks_in_lab_sitrep_pdf_fetches_from_exhibit_remark_column_in_exhibits_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'EX-REMARK-TEST-001',
            'user_id' => $user->id,
            'suspect_name' => 'Kofi Boateng',
            'raid_location' => 'Aflao Border Post',
            'seizure_name_drug' => 'Suspected Heroin',
        ]);

        $customRemark = 'Secured in sealed evidence bag No. 4921; chemical colorimetry test positive for diamorphine.';

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_number' => 'EX-AF-001',
            'commodity_name' => 'Suspected Heroin',
            'seizure_quantity' => '5 packets',
            'source_of_exhibits' => 'Aflao Border Post',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => $customRemark,
        ]);

        // Even with a separate laboratory exam result, the exhibit_remark from exhibits table should be fetched and shown
        \App\Models\LaboratoryExaminationResult::create([
            'single_suspect_case_id' => $singleCase->id,
            'presumptive_result' => 'Other generic lab result',
            'findings' => 'General lab finding',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify the Remarks column displays the exact exhibit_remark from the exhibits table
        $this->assertStringContainsString($customRemark, $content);
        $this->assertStringContainsString('Kofi Boateng', $content);
        $this->assertStringContainsString('Aflao Border Post', $content);
    }

    public function test_multiple_case_remarks_in_lab_sitrep_pdf_fetches_from_exhibit_remark_column(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'EX-REMARK-MULTI-002',
            'user_id' => $user->id,
            'raid_location' => 'Kotoka International Airport',
        ]);

        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Kwame Mensah',
            'role' => 'Primary Suspect',
        ]);

        $customMultiRemark = 'Exhibit retrieved from false bottom suitcase; vacuum-sealed and logged in Evidence Vault.';

        \App\Models\Exhibit::create([
            'case_type' => 'Multiple',
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_number' => 'EX-KIA-002',
            'commodity_name' => 'Compressed White Powder',
            'seizure_quantity' => '10 slabs',
            'source_of_exhibits' => 'Kotoka International Airport',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => $customMultiRemark,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString($customMultiRemark, $content);
        $this->assertStringContainsString('Kwame Mensah', $content);
        $this->assertStringContainsString('Kotoka International Airport', $content);
    }

    public function test_lab_sitrep_pdf_with_weekly_filter_and_unloaded_exhibits_resolves_successfully(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $case = SingleSuspectCase::create([
            'case_number' => 'WEEKLY-FILTER-001',
            'user_id' => $user->id,
            'suspect_name' => 'Weekly Suspect',
            'raid_location' => 'Tema Port',
            'seizure_name_drug' => 'Suspected Cocaine',
            'incident_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-TP-001',
            'commodity_name' => 'Suspected Cocaine',
            'source_of_exhibits' => 'Tema Port',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => 'Discovered inside container wall lining.',
        ]);

        $response = $this->actingAs($user)->get("/Lab_Analysis/lab_sitrep_pdf?period=weekly&from={$from}&to={$to}");
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Discovered inside container wall lining.', $content);
        $this->assertStringContainsString('Weekly Suspect', $content);
    }

    public function test_remark_falls_back_to_unspecified_when_exhibit_has_no_remark(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'FALLBACK-UNSPECIFIED-001',
            'user_id' => $user->id,
            'suspect_name' => 'Yaw Mensah',
            'raid_location' => 'Elubo Border',
            'seizure_name_drug' => 'Suspected Cannabis',
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'Single',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-EL-001',
            'commodity_name' => 'Suspected Cannabis',
            'source_of_exhibits' => 'Elubo Border',
            'exhibit_category' => 'Narcotic Drug',
            'exhibit_remark' => null,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Unspecified', $content);
        $this->assertStringNotContainsString('Preliminary examination conducted; sample secured for confirmatory assay.', $content);
    }

    public function test_table_content_does_not_overlap_footer_and_has_clean_footer_margins(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // Seed 30 cases with varying text lengths matching the user's multi-entry situation
        for ($i = 1; $i <= 30; $i++) {
            $case = SingleSuspectCase::create([
                'case_number' => "MARGIN-TEST-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'suspect_name' => "Suspect Number {$i} with Extended Full Name",
                'raid_location' => 'Tema Port Command Station',
                'seizure_name_drug' => 'Heroin (Brown Sugar / Diacetylmorphine)',
            ]);

            \App\Models\Exhibit::create([
                'case_type' => 'Single',
                'single_suspect_case_id' => $case->id,
                'exhibit_number' => "EX-MARGIN-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'commodity_name' => 'Heroin (Brown Sugar / Diacetylmorphine)',
                'source_of_exhibits' => 'Tema Port Command Station',
                'exhibit_category' => 'Narcotic Drug',
                'seizure_quantity' => '50 Slabs',
                'seizure_weight' => '75 kg',
                'exhibit_remark' => 'Submitted via Laboratory Analysis & Case Registration Workspace with extended custody remarks.',
            ]);
        }

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Check CSS protection properties ensuring footer clearance
        $this->assertStringContainsString('margin-bottom: 3mm;', $content);
        $this->assertStringContainsString('z-index: 20', $content);
        $this->assertStringContainsString('background-color: #ffffff', $content);

        // Verify all 30 entries rendered without loss
        for ($i = 1; $i <= 30; $i++) {
            $this->assertStringContainsString("Suspect Number {$i}", $content);
        }
    }
}



