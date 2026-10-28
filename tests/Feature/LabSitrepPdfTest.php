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

        $this->assertStringContainsString('Tema Port Unit', $content);
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
        $this->assertStringContainsString('Signature &amp; Date:', $content);
        $this->assertStringNotContainsString('Electronically Signed &amp; Validated', $content);
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
        $this->assertStringContainsString('Case Reference', $content);
        $this->assertStringNotContainsString('Case Reference / Docket', $content);
        $this->assertStringNotContainsString('Submission Date', $content);
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
        $this->assertStringContainsString('Official Situational Dossier', $content);
        $this->assertStringContainsString('STRICTLY CONFIDENTIAL', $content);
        $this->assertStringContainsString('Page 2 of', $content);

        // 3. Server-side pre-rendered pagination (no JS paginator)
        $this->assertStringNotContainsString('autoPaginateSitrepReport', $content);
        $this->assertStringNotContainsString('renumberAllPages', $content);
        $this->assertStringNotContainsString('sitrepPristineSnapshot', $content);
        $this->assertStringContainsString('window.print()', $content);
    }
}


