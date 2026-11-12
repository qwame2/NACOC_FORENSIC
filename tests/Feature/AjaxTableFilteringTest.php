<?php

namespace Tests\Feature;

use App\Models\DrugScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AjaxTableFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate an active officer user
        $this->user = User::factory()->create([
            'is_approved' => true,
            'is_head' => true,
            'unit' => 'Forensic Directorate'
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Test Drug Screening table returns partial HTML on AJAX and full layout on non-AJAX.
     */
    #[Test]
    public function drug_screening_returns_partial_on_ajax_and_full_view_on_standard_get(): void
    {
        // 1. Non-AJAX request returns full view containing <html> and table container
        $response = $this->get('/Lab_Analysis/drug-screening');
        $response->assertStatus(200);
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('id="drugScreeningTableContainer"', false);

        // 2. AJAX request returns only the partial view table without <html> or <body>
        $ajaxResponse = $this->get('/Lab_Analysis/drug-screening', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResponse->assertSee('id="drugScreeningTable"', false);
    }

    /**
     * Test Drug Screening AJAX filter by result and type.
     */
    #[Test]
    public function drug_screening_ajax_filter_query_handling(): void
    {
        $civilian = DrugScreening::create([
            'person_type' => 'civilian',
            'civilian_name' => 'Kofi Mensah',
            'ghana_card_number' => 'GHA-123456789-1',
            'purpose' => 'Employment Pre-screening',
            'result' => 'Negative',
            'screening_date' => now()->toDateString(),
        ]);

        $ajaxResponse = $this->get('/Lab_Analysis/drug-screening?result=negative&type=civilians', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('Kofi Mensah');
        $ajaxResponse->assertSee('Civilian');
        $ajaxResponse->assertSee('Negative');
    }

    /**
     * Test Drug Screening table renders skeleton interceptor inputs and filters.
     */
    #[Test]
    public function drug_screening_index_renders_ajax_table_skeleton_controls_and_filters(): void
    {
        $resp = $this->get('/Lab_Analysis/drug-screening');
        $resp->assertStatus(200);

        // Assets and container
        $resp->assertSee('js/ajax-table.js');
        $resp->assertSee('id="drugScreeningTableContainer"', false);
        $resp->assertSee('id="tableRecordCountBadge"', false);

        // Unified search and hidden inputs
        $resp->assertSee('id="tableSearchForm"', false);
        $resp->assertSee('id="tableSearchInput"', false);
        $resp->assertSee('id="screeningFilterType"', false);
        $resp->assertSee('id="screeningFilterResult"', false);
        $resp->assertSee('id="clearTableSearchBtn"', false);

        // Filter pills
        $resp->assertSee('setScreeningTypeFilter');
        $resp->assertSee('setScreeningResultFilter');
        $resp->assertSee('filter-type-pill');
        $resp->assertSee('filter-result-pill');

        // Check AJAX search query handling
        DrugScreening::create([
            'person_type' => 'civilian',
            'civilian_name' => 'Akua Mensah',
            'ghana_card_number' => 'GHA-999888777-1',
            'purpose' => 'Court Order Test',
            'result' => 'Positive',
            'screening_date' => now()->toDateString(),
        ]);

        $searchResp = $this->get('/Lab_Analysis/drug-screening?search=Akua&type=civilians&result=positive', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $searchResp->assertStatus(200);
        $searchResp->assertSee('Akua Mensah');
        $searchResp->assertSee('id="screeningTableDataMeta"', false);
        $searchResp->assertSee('data-total="1"', false);
    }

    /**
     * Test Master Cases landing returns partial on AJAX and filters properly.
     */
    #[Test]
    public function master_cases_ajax_partial_and_filtering(): void
    {
        // Non-AJAX
        $resp = $this->get('/cases');
        $resp->assertStatus(200);
        $resp->assertSee('id="casesTableContainer"', false);
        $resp->assertSee('<!DOCTYPE html>', false);

        // AJAX
        $ajaxResp = $this->get('/cases', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResp->assertStatus(200);
        $ajaxResp->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResp->assertSee('id="casesTable"', false);

        // Alias /cases/landing
        $landingResp = $this->get('/cases/landing', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $landingResp->assertStatus(200);
        $landingResp->assertSee('id="casesTable"', false);
    }

    /**
     * Test Forensic Head cases returns partial on AJAX.
     */
    #[Test]
    public function forensic_head_cases_ajax_partial(): void
    {
        // Standard GET
        $resp = $this->get('/Forensic_Head/cases');
        $resp->assertStatus(200);
        $resp->assertSee('id="headCasesTableContainer"', false);

        // AJAX
        $ajaxResp = $this->get('/Forensic_Head/cases', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResp->assertStatus(200);
        $ajaxResp->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResp->assertSee('id="casesTable"', false);
    }

    /**
     * Test Digital Forensics cases returns partial on AJAX.
     */
    #[Test]
    public function digital_forensics_cases_ajax_partial(): void
    {
        // Standard GET
        $resp = $this->get('/Digital_Forensics/cases');
        $resp->assertStatus(200);
        $resp->assertSee('id="digitalCasesTableContainer"', false);

        // AJAX
        $ajaxResp = $this->get('/Digital_Forensics/cases', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResp->assertStatus(200);
        $ajaxResp->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResp->assertSee('id="regMasterCaseTable"', false);
    }

    /**
     * Test History / Audit log returns partial on AJAX.
     */
    #[Test]
    public function history_audit_log_ajax_partial(): void
    {
        // Standard GET
        $resp = $this->get('/history');
        $resp->assertStatus(200);
        $resp->assertSee('id="historyTableContainer"', false);

        // AJAX
        $ajaxResp = $this->get('/history', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResp->assertStatus(200);
        $ajaxResp->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResp->assertSee('id="historyTable"', false);
    }

    /**
     * Test Case Registry & Directory loads ajax-table.js properly and handles debounced search with skeleton loader.
     */
    #[Test]
    public function case_registry_and_directory_search_input_has_debounce_and_skeleton_loader(): void
    {
        $resp = $this->get('/cases?unit=Digital+%26+Physical+Forensics');
        $resp->assertStatus(200);

        // Verify ajax-table.js is loaded in the page head
        $resp->assertSee('js/ajax-table.js');
        $resp->assertSee('id="casesTableContainer"', false);
        $resp->assertSee('id="casesTable"', false);
        $resp->assertSee('id="caseSearchInput"', false);
        $resp->assertSee('setupCasesAjaxTable', false);
        $resp->assertSee('filterCases()', false);
        $resp->assertSee('searchDebounceTimer', false);

        // Test AJAX debounced search request
        $ajaxResp = $this->get('/cases?unit=Digital+%26+Physical+Forensics&search=NACOC', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResp->assertStatus(200);
        $ajaxResp->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResp->assertSee('id="casesTable"', false);
    }
}
