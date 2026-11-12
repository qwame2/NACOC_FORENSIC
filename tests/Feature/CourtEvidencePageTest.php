<?php

namespace Tests\Feature;

use App\Models\DpfReport;
use App\Models\DrugScreening;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourtEvidencePageTest extends TestCase
{
    use RefreshDatabase;

    protected User $headUser;
    protected User $labOfficer;
    protected User $dpfOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headUser = User::factory()->create([
            'fullname'    => 'Director Kwame Mensah',
            'is_head'     => true,
            'is_approved' => true,
            'unit'        => 'Forensic Directorate',
        ]);

        $this->labOfficer = User::factory()->create([
            'fullname'    => 'Analyst Kofi Boateng',
            'is_head'     => false,
            'is_approved' => true,
            'unit'        => 'Laboratory Analysis',
            'service_id'  => 'LAB-2026-001',
        ]);

        $this->dpfOfficer = User::factory()->create([
            'fullname'    => 'Specialist Ama Serwaa',
            'is_head'     => false,
            'is_approved' => true,
            'unit'        => 'Digital & Physical Forensics',
            'service_id'  => 'DPF-2026-009',
        ]);
    }

    #[Test]
    public function court_evidence_page_loads_successfully_for_authenticated_head(): void
    {
        $response = $this->actingAs($this->headUser)->get('/court-evidence');
        $response->assertStatus(200);
        $response->assertSee('Court Evidence &amp; SITREP Repository', false);
        $response->assertSee('Lab General Report');
        $response->assertSee('Crime Scene General Report');
        $response->assertSee('Evidence General Report');
        $response->assertSee('Digital General Report');
        $response->assertDontSee('National Forensic Directorate Master Situational Report (SITREP)');
        $response->assertDontSee('Crime Scene Support Field Operations &amp; Incident Audit Report', false);
        $response->assertDontSee('Forensic_Directorate_Master_SITREP.pdf');
        $response->assertDontSee('CSS_Field_Operations_Audit.pdf');
        $response->assertDontSee('Case: DIRECTORATE-SITREP');
        $response->assertDontSee('By: Directorate SITREP Engine');
        $response->assertDontSee('Live Master SITREP');
    }

    #[Test]
    public function forensic_head_sidebar_links_to_court_evidence_page(): void
    {
        $response = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $response->assertStatus(200);
        $response->assertSee('/court-evidence');
        $response->assertSee('Court Evidence &amp; SITREP', false);
    }

    #[Test]
    public function forensic_head_prefixed_route_also_loads_court_evidence(): void
    {
        $response = $this->actingAs($this->headUser)->get('/Forensic_Head/court-evidence');
        $response->assertStatus(200);
        $response->assertSee('Court Evidence &amp; SITREP Repository', false);
    }

    #[Test]
    public function master_sitrep_preview_from_court_evidence_hides_filter_bar(): void
    {
        $response = $this->actingAs($this->headUser)->get('/Lab_Analysis/lab_sitrep_pdf?preview=1&from=court-evidence');
        $response->assertStatus(200);
        $response->assertDontSee('id="sitrepFilterBar"', false);
        $response->assertDontSee('data-preset="weekly"', false);

        // Standard direct route still includes the filter controls
        $standardResp = $this->actingAs($this->headUser)->get('/Lab_Analysis/lab_sitrep_pdf');
        $standardResp->assertStatus(200);
        $standardResp->assertSee('id="sitrepFilterBar"', false);
    }

    #[Test]
    public function officer_uploaded_gsa_and_preliminary_reports_are_displayed(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-9999',
            'title'       => 'High Profile Narcotics Interception',
            'unit_name'   => 'Laboratory Analysis',
            'status'      => 'Active',
        ]);

        $suspect = \App\Models\CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_name'           => 'John Mensah',
            'suspect_number'         => 1,
        ]);

        // GSA Report uploaded by officer
        SuspectGsaReport::create([
            'case_suspect_id'        => $suspect->id,
            'single_suspect_case_id' => $case->id,
            'case_number'            => 'NCC-2026-9999',
            'original_filename'      => 'Official_GSA_Laboratory_Confirmation.pdf',
            'stored_filename'        => 'gsa_test_sample.pdf',
            'file_path'              => 'gsa_reports/2026/11/gsa_test_sample.pdf',
            'file_type'              => 'application/pdf',
            'file_size'              => 245000,
            'uploaded_by'            => $this->labOfficer->id,
            'gsa_lab_number'         => 'GSA-LAB-888',
            'gsa_status'             => 'Confirmed Positive Cocaine HCl',
            'status'                 => 'active',
        ]);

        // Preliminary report uploaded by officer
        SuspectPreliminaryReport::create([
            'case_suspect_id'        => $suspect->id,
            'single_suspect_case_id' => $case->id,
            'case_number'            => 'NCC-2026-9999',
            'original_filename'      => 'Preliminary_Narcotic_Assay_NLR.pdf',
            'stored_filename'        => 'prelim_test_sample.pdf',
            'file_path'              => 'Lab_Analysis/nlr-report-pdf/NCC-2026-9999',
            'file_type'              => 'application/pdf',
            'file_size'              => 120000,
            'uploaded_by'            => $this->labOfficer->id,
            'lab_number'             => 'LAB-PRELIM-777',
            'status'                 => 'active',
        ]);

        $response = $this->actingAs($this->headUser)->get('/court-evidence');
        $response->assertStatus(200);
        $response->assertSee('Official_GSA_Laboratory_Confirmation.pdf');
        $response->assertSee('Preliminary_Narcotic_Assay_NLR.pdf');
        $response->assertSee('GSA-LAB-888');
        $response->assertSee('NCC-2026-9999');
        $response->assertDontSee('High Court Examination Certificate');
        $response->assertDontSee('Laboratory Chemical Analytical Report');
        $response->assertDontSee('By: Laboratory Analysis Unit');
        $response->assertDontSee('Laboratory analysis findings, narcotic test results, and substance profi');
    }

    #[Test]
    public function dpf_uploaded_court_orders_and_witness_statements_are_displayed(): void
    {
        DpfReport::create([
            'case_number'                   => 'NCC-2026-8888',
            'dpf_code'                      => 'DPF-CODE-2026-777',
            'court_order_file'              => json_encode(['uploads/forensic_documents/court_order_Judicial_Warrant_High_Court.pdf']),
            'report_witness_statement_file' => json_encode(['uploads/forensic_documents/report_witness_Key_Informant_Testimony.pdf']),
            'user_id'                       => $this->dpfOfficer->id,
        ]);

        $response = $this->actingAs($this->headUser)->get('/court-evidence');
        $response->assertStatus(200);
        $response->assertSee('court_order_Judicial_Warrant_High_Court.pdf');
        $response->assertSee('report_witness_Key_Informant_Testimony.pdf');
        $response->assertSee('DPF-CODE-2026-777');
        $response->assertSee('Digital &amp; Physical Forensics', false);
    }

    #[Test]
    public function filtering_by_origin_and_unit_works(): void
    {
        // 1. Filter by origin=uploaded
        $uploadedResp = $this->actingAs($this->headUser)->get('/court-evidence?origin=uploaded');
        $uploadedResp->assertStatus(200);
        $uploadedResp->assertDontSee('Lab General Report');

        // 2. Filter by origin=generated
        $generatedResp = $this->actingAs($this->headUser)->get('/court-evidence?origin=generated');
        $generatedResp->assertStatus(200);
        $generatedResp->assertSee('Lab General Report');

        // 3. Filter by unit=css
        $cssResp = $this->actingAs($this->headUser)->get('/court-evidence?unit=css');
        $cssResp->assertStatus(200);
        $cssResp->assertSee('Crime Scene General Report');
    }

    #[Test]
    public function timeframe_parameter_dynamically_names_unit_reports(): void
    {
        // Monthly report test
        $monthlyResp = $this->actingAs($this->headUser)->get('/court-evidence?period=monthly');
        $monthlyResp->assertStatus(200);
        $monthlyResp->assertSee('Lab Monthly Report');
        $monthlyResp->assertSee('Crime Scene Monthly Report');
        $monthlyResp->assertSee('Evidence Monthly Report');
        $monthlyResp->assertSee('Digital Monthly Report');

        // Weekly report test
        $weeklyResp = $this->actingAs($this->headUser)->get('/court-evidence?period=weekly');
        $weeklyResp->assertStatus(200);
        $weeklyResp->assertSee('Lab Weekly Report');
        $weeklyResp->assertSee('Crime Scene Weekly Report');

        // Annually report test
        $annualResp = $this->actingAs($this->headUser)->get('/court-evidence?period=annually');
        $annualResp->assertStatus(200);
        $annualResp->assertSee('Lab Annual Report');
        $annualResp->assertSee('Evidence Annual Report');

        // Custom range report test
        $customResp = $this->actingAs($this->headUser)->get('/court-evidence?from=2026-01-01&to=2026-03-31');
        $customResp->assertStatus(200);
        $customResp->assertSee('Lab Customize Date Range Report');
        $customResp->assertSee('Digital Customize Date Range Report');
    }

    #[Test]
    public function search_filters_documents_accurately(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-SEARCH-TARGET-001',
            'title'       => 'Alpha Searchable Narco Dossier',
            'unit_name'   => 'Crime Scene Support',
            'status'      => 'Active',
        ]);

        $suspect = \App\Models\CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_name'           => 'Searchable Suspect',
            'suspect_number'         => 1,
        ]);

        SuspectGsaReport::create([
            'case_suspect_id'        => $suspect->id,
            'single_suspect_case_id' => $case->id,
            'case_number'            => 'NCC-SEARCH-TARGET-001',
            'original_filename'      => 'Searchable_GSA_Confirmation_Report.pdf',
            'stored_filename'        => 'searchable_gsa.pdf',
            'file_path'              => 'gsa_reports/2026/11/searchable_gsa.pdf',
            'file_type'              => 'application/pdf',
            'file_size'              => 150000,
            'uploaded_by'            => $this->labOfficer->id,
            'gsa_lab_number'         => 'GSA-SEARCH-001',
            'status'                 => 'active',
        ]);

        $response = $this->actingAs($this->headUser)->get('/court-evidence?search=NCC-SEARCH-TARGET-001');
        $response->assertStatus(200);
        $response->assertSee('NCC-SEARCH-TARGET-001');
        $response->assertSee('Searchable_GSA_Confirmation_Report.pdf');
    }

    #[Test]
    public function master_case_docket_is_not_displayed_in_document_and_file_details_table(): void
    {
        SingleSuspectCase::create([
            'case_number' => 'NCC-2026-DOCKET-CHECK',
            'title'       => 'Confidential Narcotics Registry',
            'unit_name'   => 'Crime Scene Support',
            'status'      => 'Active',
        ]);

        $response = $this->actingAs($this->headUser)->get('/court-evidence');
        $response->assertStatus(200);
        $response->assertDontSee('Master Case Docket:');
        $response->assertDontSee('Official_Docket_NCC-2026-DOCKET-CHECK.pdf');
    }

    #[Test]
    public function court_evidence_table_supports_ajax_and_skeleton_loader(): void
    {
        // 1. Initial page contains skeleton loader script, container, and skeleton renderer function
        $pageResponse = $this->actingAs($this->headUser)->get('/court-evidence');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('id="courtEvidenceTableContainer"', false);
        $pageResponse->assertSee('renderCourtEvidenceSkeletonRows', false);
        $pageResponse->assertSee('skeleton-pulse', false);
        $pageResponse->assertSee('ajax-table.js', false);

        // 2. AJAX request returns partial table with metadata for skeleton hydration
        $ajaxResponse = $this->actingAs($this->headUser)->get('/court-evidence', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('id="courtEvidenceTable"', false);
        $ajaxResponse->assertSee('id="courtEvidenceTableMeta"', false);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>');
    }

    #[Test]
    public function court_evidence_table_and_filters_adhere_to_latest_ui_specification(): void
    {
        $response = $this->actingAs($this->headUser)->get('/court-evidence');
        $response->assertStatus(200);

        // 1. Case Reference & Officer / Submitter columns are removed from <thead>
        $response->assertDontSee('<th class="py-3 px-4 font-bold">Case Reference</th>', false);
        $response->assertDontSee('<th class="py-3 px-4 font-bold">Officer / Submitter</th>', false);

        // 2. Directorate SITREPs tab button is removed
        $response->assertDontSee('Directorate SITREPs', false);

        // 3. Document Origin dropdown is removed
        $response->assertDontSee('Document Origin', false);
        $response->assertDontSee('<select name="origin"', false);

        // 4. Directorate Governance is removed from unitSelectFilter
        $response->assertDontSee('<option value="hof"', false);
    }
}
