<?php

namespace Tests\Feature;

use App\Models\CrimeSceneReportNote;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrimeSceneSupportReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);
    }

    public function test_crime_scene_support_report_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee('Crime Scene Support');
        $response->assertSee('Field Operations &amp; Case Report', false);
        $response->assertSee('Field Cases Logged');
        $response->assertSee('Active Investigations');
        $response->assertSee('Suspects Apprehended');
        $response->assertSee('Exhibits &amp; Seizures', false);
        $response->assertSee('Critical / High Priority');
        $response->assertSee('Crime Scene Cases Audit Filter Parameters');
    }

    public function test_crime_scene_support_report_displays_css_cases(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-7788',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Raid on Illicit Depot',
            'suspect_name' => 'Kofi Boateng',
            'arresting_officer' => 'Officer Mensah',
            'operation_code' => 'Raid',
            'operation_code_name' => 'Depot Raid',
            'raid_location' => 'Tema Harbour Zone',
            'priority' => 'HIGH',
            'status' => 'Active Investigation',
            'created_at' => now(),
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-CSS-001',
            'commodity_name' => 'Seized Tramadol Cartons',
            'exhibit_category' => 'Drugs',
        ]);

        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee('Field Cases Log');
        $response->assertSee('CSS-2026-7788');
        $response->assertSee('Raid on Illicit Depot');
        $response->assertSee('Kofi Boateng');
        $response->assertSee('Tema Harbour Zone');
        $response->assertSee('Seized Tramadol Cartons');
        $response->assertDontSee('Arresting Officer');
        $response->assertDontSee('Priority &amp; Status', false);
        $response->assertDontSee('Edit Case Data');
        $response->assertSee('Review');
    }

    public function test_crime_scene_support_report_filters_by_search_and_status(): void
    {
        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-ALPHA',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Search Warrant at Legon',
            'suspect_name' => 'Kwame Legon',
            'status' => 'Draft',
            'priority' => 'NORMAL',
            'created_at' => now(),
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-BETA',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Cargo Interception at Kotoka',
            'suspect_name' => 'Ama Airport',
            'status' => 'Approved',
            'priority' => 'CRITICAL',
            'created_at' => now(),
        ]);

        // Search for 'Legon'
        $searchResponse = $this->actingAs($this->user)->get('/Crime_Scene_Support/report?search=Legon');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('CSS-2026-ALPHA');
        $searchResponse->assertDontSee('CSS-2026-BETA');

        // Filter by status 'Approved'
        $statusResponse = $this->actingAs($this->user)->get('/Crime_Scene_Support/report?status=Approved');
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('CSS-2026-BETA');
        $statusResponse->assertDontSee('CSS-2026-ALPHA');
    }

    public function test_crime_scene_support_report_pdf_renders_successfully(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-PDF-01',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Field Apprehension Operation',
            'suspect_name' => 'Bingo Isaac',
            'operation_code' => 'Search Warrant operation',
            'raid_location' => 'Alavanyo',
            'offense_description' => 'Officers conducted a search warrant operation at Alavanyo and recovered illicit ammunition.',
            'status' => 'Active Investigation',
            'priority' => 'HIGH',
            'created_at' => now(),
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-PDF-001',
            'commodity_name' => 'Blazer-branded ammunition',
            'seizure_quantity' => '900 rounds',
            'exhibit_category' => 'Weapons and Ammunition',
        ]);

        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report_pdf');

        $response->assertStatus(200);
        // Cover page assertions
        $response->assertSee('OFFICIAL FORENSIC SITUATION REPORT');
        $response->assertSee('SITREP COVER');
        $response->assertSee('Executive Directory Cover Page');
        $response->assertSee('CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD', false);

        // Dossier assertions
        $response->assertSee('Crime Scene Support Unit');
        $response->assertDontSee('Field Operations');
        $response->assertDontSee('field operations');
        $response->assertSee('CASE ONE');
        $response->assertSee('CSS-2026-PDF-01');
        $response->assertSee('Date');
        $response->assertSee('Operation Type');
        $response->assertSee('Search Warrant operation');
        $response->assertSee('Location');
        $response->assertSee('Alavanyo');
        $response->assertSee('Case briefing');
        $response->assertSee('Officers conducted a search warrant operation at Alavanyo and recovered illicit ammunition.');
        $response->assertSee('Suspects');
        $response->assertSee('Bingo Isaac');
        $response->assertSee('Exhibits Retrieved');
        $response->assertSee('Blazer-branded ammunition');
        $response->assertDontSee('<td class="td-label">Status</td>', false);
        $response->assertDontSee('Reporting Crime Scene Officer');
        $response->assertSee('HEAD OF CRIME SCENE SUPPORT');
        $response->assertSee('Head, Crime Scene Support Unit');
        $response->assertSee('HEAD OF FORENSIC DEPARTMENT');
        $response->assertSee('Director, Forensic Science Directorate');
    }

    public function test_general_report_route_redirects_for_crime_scene_support(): void
    {
        $response = $this->actingAs($this->user)->get('/Report?unit=Crime+Scene+Support');

        $response->assertRedirect('/Crime_Scene_Support/report');
    }

    public function test_sidebar_contains_crime_scene_report_link(): void
    {
        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee(url('/Crime_Scene_Support/report'));
    }

    public function test_pdf_report_uses_unspecify_fallback_and_no_mock_values_when_case_has_null_fields(): void
    {
        // Create a case with null offense_description, null location, null exhibits, etc.
        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-EMPTY',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Minimal Test Case',
            'status' => 'Pending Verification',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report_pdf');

        $response->assertStatus(200);
        $response->assertSee('CSS-2026-EMPTY');
        $response->assertSee('Unspecify');
        $response->assertDontSee('<td class="td-label">Status</td>', false);

        // Assert strictly no mock strings appear
        $response->assertDontSee('Victor Kofi');
        $response->assertDontSee('Agbogbloshie');
        $response->assertDontSee('All exhibits were properly documented, photographed, packaged, secured');
        $response->assertDontSee('Field Apprehension operation');
        $response->assertDontSee('Republic vs Primary Suspect &amp; Associates', false);
        $response->assertDontSee('console.log');
    }

    public function test_report_page_renders_print_export_pdf_button_and_popover_modal_with_textareas(): void
    {
        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee('id="printExportPdfBtn"', false);
        $response->assertSee('openReportNotesModal()', false);
        $response->assertSee('id="reportNotesModal"', false);
        $response->assertSee('Crime Scene Report Notes');

        // Check the 3 textarea inputs and labels
        $response->assertSee('STATUS');
        $response->assertSee('RECOMMENDATION');
        $response->assertSee('CONCLUSION');
        $response->assertSee('id="reportStatusInput"', false);
        $response->assertSee('id="reportRecommendationInput"', false);
        $response->assertSee('id="reportConclusionInput"', false);
        $response->assertDontSee('Dossier Details');
    }

    public function test_save_report_notes_stores_details_in_database_table(): void
    {
        $payload = [
            'status' => 'Ongoing active surveillance and field raid operations concluded.',
            'recommendation' => 'Forward chemical exhibits directly to Lab Analysis for FTIR spectrometry.',
            'conclusion' => 'Suspect apprehend successfully with all exhibits catalogued in tamper-evident packaging.',
            'period' => 'monthly',
            'from_date' => '2026-03-01',
            'to_date' => '2026-03-31',
            'search' => 'Tema Raid',
            'priority' => 'HIGH',
            'status_filter' => 'Active Investigation',
            'total_cases_count' => 12,
        ];

        $response = $this->actingAs($this->user)->postJson('/Crime_Scene_Support/report/notes', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('crime_scene_report_notes', [
            'user_id' => $this->user->id,
            'status' => $payload['status'],
            'recommendation' => $payload['recommendation'],
            'conclusion' => $payload['conclusion'],
            'period' => 'monthly',
            'search' => 'Tema Raid',
            'priority' => 'HIGH',
            'total_cases_count' => 12,
        ]);

        $savedNote = CrimeSceneReportNote::latest()->first();
        $this->assertNotNull($savedNote->batch_id);
    }

    public function test_pdf_report_renders_saved_status_recommendation_and_conclusion(): void
    {
        $note = CrimeSceneReportNote::create([
            'user_id' => $this->user->id,
            'officer_name' => $this->user->fullname,
            'status' => 'FIELD SEARCH OPERATION VERIFIED AND ACTIVE',
            'recommendation' => 'EXPEDITE DIGITAL FORENSIC EXTRACTION ON SEIZED PHONES',
            'conclusion' => 'OPERATION COMPLETED WITH ZERO CHAIN OF CUSTODY BREAKS',
            'batch_id' => 'CSS-TEST-BATCH-001',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-NOTE-TEST',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Sample Note Case',
            'status' => 'Active Investigation',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report_pdf?batch_id=' . $note->batch_id);

        $response->assertStatus(200);
        $response->assertSee('STATUS:');
        $response->assertSee('FIELD SEARCH OPERATION VERIFIED AND ACTIVE');
        $response->assertSee('RECOMMENDATION:');
        $response->assertSee('EXPEDITE DIGITAL FORENSIC EXTRACTION ON SEIZED PHONES');
        $response->assertSee('CONCLUSION:');
        $response->assertSee('OPERATION COMPLETED WITH ZERO CHAIN OF CUSTODY BREAKS');
        $response->assertDontSee('bg-blue-600 inline-block');
        $response->assertDontSee('bg-slate-50 border border-slate-300');
    }

    public function test_field_cases_log_has_no_pulsing_dot_at_side(): void
    {
        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee('Field Cases Log');
        // Ensure pulsing dot is removed from the side of Field Cases Log
        $response->assertDontSee('w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse', false);
    }

    public function test_field_cases_log_renders_ajax_table_skeleton_container(): void
    {
        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report');

        $response->assertStatus(200);
        $response->assertSee('js/ajax-table.js');
        $response->assertSee('id="fieldCasesTableContainer"', false);
        $response->assertSee('initFieldCasesTable', false);
    }

    public function test_field_cases_log_returns_partial_view_on_ajax_filter(): void
    {
        SingleSuspectCase::create([
            'case_number' => 'CSS-AJAX-001',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Ajax Skeleton Loading Case',
            'status' => 'Active Investigation',
            'created_at' => now(),
        ]);

        $ajaxResponse = $this->actingAs($this->user)->get('/Crime_Scene_Support/report', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
        $ajaxResponse->assertDontSee('<body', false);
        $ajaxResponse->assertSee('CSS-AJAX-001');
        $ajaxResponse->assertSee('id="fieldCasesTableMeta"', false);
    }

    public function test_field_cases_log_ajax_filter_query_handling(): void
    {
        SingleSuspectCase::create([
            'case_number' => 'CSS-FILTER-AIRPORT',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Airport Smuggling Incident',
            'status' => 'Active Investigation',
            'created_at' => now(),
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-FILTER-DEPOT',
            'unit_name' => 'Crime Scene Support',
            'title' => 'Harbour Depot Raid',
            'status' => 'Approved',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/Crime_Scene_Support/report?search=Airport', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('CSS-FILTER-AIRPORT');
        $response->assertDontSee('CSS-FILTER-DEPOT');
    }
}

