<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForensicHeadApprovalAndCaseRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed a Forensic Directorate Head user
        $this->headUser = User::factory()->create([
            'fullname' => 'Director Kwame Mensah',
            'is_head' => true,
            'is_approved' => true,
            'unit' => 'Forensic Directorate',
        ]);
    }

    #[Test]
    public function assigned_unit_and_handling_officer_columns_are_removed_from_case_register_table(): void
    {
        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $resp->assertStatus(200);

        // Verify the removed columns are NOT present
        $resp->assertDontSee('Assigned Unit &amp; Head', false);
        $resp->assertDontSee('Assigned Unit & Head', false);
        $resp->assertDontSee('Handling Officer</th>', false);
        $resp->assertDontSee('<th class="py-3.5 px-5 font-extrabold">Status</th>', false);

        // Verify title is Forensic Department Case Register, not Directorate
        $resp->assertSee('Forensic Department Case Register');
        $resp->assertDontSee('Forensic Directorate Case Register');

        // Verify the remaining required columns ARE present
        $resp->assertSee('Case Reference #');
        $resp->assertSee('Evidence Details');
        $resp->assertSee('Suspect');
        $resp->assertSee('Suspect Disposition');
        $resp->assertSee('Case Stat');
        $resp->assertSee('Actions');
    }

    #[Test]
    public function table_actions_do_not_contain_edit_docket_or_associate_data(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TEST-2026-001',
            'title' => 'Cocaine Seizure at Harbour',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'submitting_officer' => 'Officer Evans',
            'docket_data' => ['suspects' => []],
        ]);

        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $resp->assertStatus(200);

        // Ensure "Edit Docket" and "Associate Data" are removed
        $resp->assertDontSee('Edit Docket');
        $resp->assertDontSee('edit=true&from=Forensic+Head');
        $resp->assertDontSee('Associate Data');
        $resp->assertDontSee('/associate');

        // Verify case approval and sign off buttons are removed from table actions
        $resp->assertDontSee('Authorize Docket');
        $resp->assertDontSee('Direct directorate sign-off');

        // Actions column renders only a direct view icon link to the suspect docket
        $resp->assertSee('/Forensic_Head/cases/' . $case->case_number . '/review', false);
        $resp->assertSee('title="View Suspect Docket"', false);
        $resp->assertDontSee('Review Docket');
        $resp->assertDontSee('Inspect Summary');
        $resp->assertDontSee('Court Report PDF');
        $resp->assertDontSee('case-action-dropdown-wrapper');
    }

    #[Test]
    public function forensic_head_cannot_create_or_edit_cases(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TEST-2026-002',
            'title' => 'Suspected Narcotic Trafficking',
            'unit_name' => 'Evidence Management',
            'status' => 'Under Multi-Unit Processing',
            'priority' => 'Critical',
        ]);

        // 1. Direct GET to /cases/create with from=Forensic_Head should redirect to head cases
        $createResp = $this->actingAs($this->headUser)->get('/cases/create?from=Forensic_Head');
        $createResp->assertRedirect('/Forensic_Head/cases');
        $createResp->assertSessionHas('error');

        // 2. Direct GET to /Forensic_Head/cases/{id}/edit should redirect to review
        $headEditResp = $this->actingAs($this->headUser)->get("/Forensic_Head/cases/{$case->case_number}/edit");
        $headEditResp->assertRedirect(route('forensic_head.cases.review', ['id' => $case->case_number]));

        // 3. GET /cases/{id}/edit with from=Forensic_Head should redirect to review
        $editResp = $this->actingAs($this->headUser)->get("/cases/{$case->case_number}/edit?from=Forensic_Head");
        $editResp->assertRedirect(route('forensic_head.cases.review', ['id' => $case->case_number]));

        // 4. POST /cases from Forensic_Head should be blocked
        $storeResp = $this->actingAs($this->headUser)->post('/cases', [
            'case_number' => 'NACOC-TEST-2026-999',
            'title' => 'Unauthorized Head Case',
            'from' => 'Forensic_Head',
        ]);
        $storeResp->assertRedirect('/Forensic_Head/cases');
        $storeResp->assertSessionHas('error');

        // 5. POST /cases/{id}/update from Forensic_Head should be blocked
        $updateResp = $this->actingAs($this->headUser)->post("/cases/{$case->case_number}/update", [
            'title' => 'Modified Title By Head',
            'from' => 'Forensic_Head',
        ]);
        $updateResp->assertRedirect(route('forensic_head.cases.review', ['id' => $case->case_number]));
        $updateResp->assertSessionHas('error');
    }

    #[Test]
    public function unit_and_status_filter_buttons_exist_and_function_via_ajax(): void
    {
        $caseCSS = ForensicCase::create([
            'case_number' => 'NACOC-CSS-001',
            'title' => 'Crime Scene Vehicle Search',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Awaiting Sign-off',
            'priority' => 'High',
            'docket_data' => ['suspects' => []],
        ]);

        $caseLab = ForensicCase::create([
            'case_number' => 'NACOC-LAB-002',
            'title' => 'Lab Chemical Testing',
            'unit_name' => 'LAB Analysis',
            'status' => 'Authorized',
            'priority' => 'High',
            'docket_data' => ['suspects' => []],
        ]);

        $caseDPF = ForensicCase::create([
            'case_number' => 'NACOC-DPF-003',
            'title' => 'Digital Phone Extraction',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Under Multi-Unit Processing',
            'priority' => 'High',
            'docket_data' => ['suspects' => []],
        ]);

        // 1. Verify buttons on full page load
        $pageResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $pageResp->assertStatus(200);
        $pageResp->assertSee("filterUnit('ALL', this)", false);
        $pageResp->assertSee("filterUnit('Crime Scene Support', this)", false);
        $pageResp->assertSee("filterUnit('Evidence Management', this)", false);
        $pageResp->assertSee("filterUnit('LAB Analysis', this)", false);
        $pageResp->assertSee("filterUnit('Digital & Physical Forensics', this)", false);
        $pageResp->assertSee("filterStatus('ALL', this)", false);
        $pageResp->assertSee("filterStatus('Under Investigation', this)", false);
        $pageResp->assertDontSee("filterStatus('Court Ready', this)", false);
        $pageResp->assertSee("filterStatus('Vault Storage', this)", false);
        $pageResp->assertSee("filterStatus('Lab Analysis', this)", false);

        // 2. Filter by Unit via AJAX: Crime Scene Support
        $cssFilterResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?unit=Crime+Scene+Support', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $cssFilterResp->assertStatus(200);
        $cssFilterResp->assertSee('NACOC-CSS-001');
        $cssFilterResp->assertDontSee('NACOC-LAB-002');
        $cssFilterResp->assertDontSee('NACOC-DPF-003');

        // 3. Filter by Unit via AJAX: Digital & Physical Forensics
        $dpfFilterResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?unit=Digital+%26+Physical+Forensics', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $dpfFilterResp->assertStatus(200);
        $dpfFilterResp->assertSee('NACOC-DPF-003');
        $dpfFilterResp->assertDontSee('NACOC-CSS-001');
        $dpfFilterResp->assertDontSee('NACOC-LAB-002');

        // 4. Filter by Status via AJAX: Authorized
        $authFilterResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?status=Authorized', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $authFilterResp->assertStatus(200);
        $authFilterResp->assertSee('NACOC-LAB-002');
        $authFilterResp->assertDontSee('NACOC-CSS-001');
    }

    #[Test]
    public function review_page_renders_dossier_review_without_approval_buttons_and_not_seizure_item_activation_card(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-REV-2026-001',
            'title' => 'Cross-Border Narcotics Smuggling',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Awaiting Sign-off',
            'priority' => 'Critical',
            'submitting_officer' => 'Insp. Kofi Boateng',
            'docket_data' => ['suspects' => []],
        ]);

        // 1. Table view links directly to Forensic_Head review
        $tableResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $tableResp->assertStatus(200);
        $tableResp->assertSee("/Forensic_Head/cases/{$case->case_number}/review");

        // 2. Visiting the review page shows dossier review and NO approval buttons and NO seizure item activation card
        $reviewResp = $this->actingAs($this->headUser)->get("/Forensic_Head/cases/{$case->case_number}/review");
        $reviewResp->assertStatus(200);
        $reviewResp->assertDontSee('Approve &amp; Sign-Off Case', false);
        $reviewResp->assertDontSee('Decline Docket');
        $reviewResp->assertDontSee('approveCaseAction');
        $reviewResp->assertDontSee('declineCaseAction');
        $reviewResp->assertDontSee('Executive Directorate Dossier Review');
        $reviewResp->assertDontSee('Check the box to display and activate the Seizure Item section to record new exhibits or commodities for this case.');
    }

    #[Test]
    public function cases_page_has_no_case_approval_or_signoff_logic(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TEST-2026-NO-SIGNOFF',
            'title' => 'Suspicious Goods Inspection',
            'unit_name' => 'Evidence Management',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'docket_data' => ['suspects' => []],
        ]);

        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $resp->assertStatus(200);

        // Header and titles must be Case Register, not Department Approvals
        $resp->assertDontSee('Case Management &amp; Department Approvals', false);
        $resp->assertSee('Case Management &amp; Case Register', false);

        // Stat cards: no "Awaiting Head Sign-off"
        $resp->assertDontSee('Awaiting Head Sign-off');
        $resp->assertDontSee('statPendingApprovals');
        $resp->assertSee('Active Investigations');
        $resp->assertSee('statActiveCases');

        // Table actions: no direct sign-off / authorize docket / dropdown items; direct view icon link only
        $resp->assertDontSee('Authorize Docket');
        $resp->assertDontSee('Direct directorate sign-off');
        $resp->assertDontSee('Review &amp; Sign-Off', false);
        $resp->assertDontSee('Review Docket');
        $resp->assertSee('/Forensic_Head/cases/' . $case->case_number . '/review', false);

        // Modal: no approve / decline buttons
        $resp->assertDontSee('approveFromModal');
        $resp->assertDontSee('declineFromModal');
        $resp->assertDontSee('modalApproveBtn');
        $resp->assertDontSee('Decline Case');

        // JavaScript: no approve/batch-approve logic
        $resp->assertDontSee('function approveCase(');
        $resp->assertDontSee('function batchApprove(');
        $resp->assertDontSee('function declineCase(');
    }

    #[Test]
    public function unit_directorate_progress_pipeline_does_not_contain_head_of_forensic_directorate(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TRACKER-2026-001',
            'title' => 'Cross-Unit Investigation Case',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Multi-Unit Processing',
            'priority' => 'Critical',
            'docket_data' => [
                'unit_progress' => [
                    'Crime Scene Support' => ['status' => 'COMPLETED', 'officer' => 'Officer A'],
                    'Evidence Management' => ['status' => 'PENDING', 'officer' => 'Officer B'],
                ],
            ],
        ]);

        // Review view rendering partials/unit-progress-pipeline
        $resp = $this->actingAs($this->headUser)->get("/Forensic_Head/cases/{$case->case_number}/review");
        $resp->assertStatus(200);

        // Verify the tracker title exists
        $resp->assertSee('Unit Directorate Progress &amp; Inter-Unit Workflow', false);

        // Verify Head of Forensic Directorate is NOT in the pipeline cards
        $resp->assertDontSee('FINAL STEP');
        $resp->assertDontSee('AWAITING HOF SIGN-OFF');
        $resp->assertDontSee('Docket is submitted for HOF executive sign-off');
        $resp->assertDontSee('HOF approval triggers once all');
    }

    #[Test]
    public function case_register_table_renders_date_filters_and_pagination_controls(): void
    {
        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $resp->assertStatus(200);

        // Date filter inputs
        $resp->assertSee('id="caseDateFrom"', false);
        $resp->assertSee('name="date_from"', false);
        $resp->assertSee('id="caseDateTo"', false);
        $resp->assertSee('name="date_to"', false);
        $resp->assertSee('id="clearDateFilterBtn"', false);
        $resp->assertSee('onclick="clearDateFilter()"', false);

        // Pagination and per page selector
        $resp->assertSee('id="perPageSelect"', false);
        $resp->assertSee('onchange="changePerPage(this.value)"', false);
        $resp->assertSee('Showing', false);
        $resp->assertSee('case register dockets', false);
    }

    #[Test]
    public function case_register_filters_by_date_range(): void
    {
        // Case 1: Early 2026
        $caseEarly = ForensicCase::create([
            'case_number' => 'NACOC-DATE-2026-EARLY',
            'title' => 'Early Seizure Case',
            'unit_name' => 'Evidence Management',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'created_at' => '2026-01-10 10:00:00',
            'docket_data' => ['suspects' => []],
        ]);

        // Case 2: Mid 2026
        $caseMid = ForensicCase::create([
            'case_number' => 'NACOC-DATE-2026-MID',
            'title' => 'Mid Year Inspection',
            'unit_name' => 'LAB Analysis',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'created_at' => '2026-06-15 14:00:00',
            'docket_data' => ['suspects' => []],
        ]);

        // Case 3: Late 2026
        $caseLate = ForensicCase::create([
            'case_number' => 'NACOC-DATE-2026-LATE',
            'title' => 'Late Border Interception',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'created_at' => '2026-11-20 18:00:00',
            'docket_data' => ['suspects' => []],
        ]);

        // Filter date_from=2026-05-01 & date_to=2026-07-01 via AJAX
        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?date_from=2026-05-01&date_to=2026-07-01', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $resp->assertStatus(200);
        $resp->assertSee('NACOC-DATE-2026-MID');
        $resp->assertDontSee('NACOC-DATE-2026-EARLY');
        $resp->assertDontSee('NACOC-DATE-2026-LATE');

        // Filter date_from=2026-10-01 (from only)
        $respFromOnly = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?date_from=2026-10-01', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $respFromOnly->assertStatus(200);
        $respFromOnly->assertSee('NACOC-DATE-2026-LATE');
        $respFromOnly->assertDontSee('NACOC-DATE-2026-EARLY');
        $respFromOnly->assertDontSee('NACOC-DATE-2026-MID');
    }

    #[Test]
    public function case_register_paginates_results_correctly(): void
    {
        // Create 22 cases
        for ($i = 1; $i <= 22; $i++) {
            $num = str_pad($i, 3, '0', STR_PAD_LEFT);
            ForensicCase::create([
                'case_number' => "NACOC-PAGE-{$num}",
                'title' => "Pagination Case {$i}",
                'unit_name' => 'Evidence Management',
                'status' => 'Under Investigation',
                'priority' => 'Normal',
                'created_at' => '2026-02-01 12:00:00',
                'docket_data' => ['suspects' => []],
            ]);
        }

        // Request page 1 with per_page=10
        $respPage1 = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?per_page=10&page=1', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $respPage1->assertStatus(200);
        $respPage1->assertSee('NACOC-PAGE-001');
        $respPage1->assertSee('NACOC-PAGE-010');
        $respPage1->assertDontSee('NACOC-PAGE-011');
        $respPage1->assertSee('Showing <span class="font-bold text-slate-900">1</span> to <span class="font-bold text-slate-900">10</span> of <span class="font-bold text-slate-900">22</span>', false);

        // Request page 2 with per_page=10
        $respPage2 = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?per_page=10&page=2', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $respPage2->assertStatus(200);
        $respPage2->assertSee('NACOC-PAGE-011');
        $respPage2->assertSee('NACOC-PAGE-020');
        $respPage2->assertDontSee('NACOC-PAGE-001');
        $respPage2->assertSee('Showing <span class="font-bold text-slate-900">11</span> to <span class="font-bold text-slate-900">20</span> of <span class="font-bold text-slate-900">22</span>', false);
    }

    #[Test]
    public function forensic_head_can_access_vault_storage_exhibit_page_with_full_details(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-VAULT-PAGE-001',
            'title' => 'Vault Evidence Custody Docket',
            'unit_name' => 'Evidence Management',
            'status' => 'Under Investigation',
            'priority' => 'Critical',
            'suspect_name' => 'Ibrahim Tanko',
            'suspect_alias' => 'Scorpion',
            'raid_location' => 'Tema Harbour Terminal 3',
            'holding_facility' => 'NACOC Secure Vault Alpha',
            'docket_data' => [
                'suspects' => [
                    [
                        'name' => 'Ibrahim Tanko',
                        'alias' => 'Scorpion',
                        'disposition' => 'Remanded in Custody',
                    ],
                ],
                'seizures' => [
                    [
                        'seizure_name_drug' => 'White Cocaine Bricks',
                        'exhibit_number' => 'EX-VAULT-001',
                        'category' => 'Narcotics',
                        'weight' => '12.5',
                        'unit' => 'KG',
                        'packaging' => 'Hydraulically Pressed Bricks',
                        'concealment' => 'Refrigerated Container Double Wall',
                        'location' => 'Tema Harbour Berth 2',
                        'seizure_officer' => 'Capt. Boateng',
                        'investigating_officer' => 'Insp. Owusu',
                        'value' => 'GHS 4,500,000.00',
                        'photos' => [
                            '/storage/evidence/brick1.jpg',
                        ],
                    ],
                ],
            ],
        ]);

        // Request dedicated Vault Exhibits page
        $resp = $this->actingAs($this->headUser)->get("/Forensic_Head/cases/{$case->case_number}/vault-exhibits");
        $resp->assertStatus(200);
        $resp->assertSee('Vault Storage');
        $resp->assertSee('NACOC-VAULT-PAGE-001');
        $resp->assertSee('Ibrahim Tanko');
        $resp->assertSee('Scorpion');
        $resp->assertSee('White Cocaine Bricks');
        $resp->assertSee('Hydraulically Pressed Bricks');
        $resp->assertSee('Refrigerated Container Double Wall');
        $resp->assertSee('NACOC Secure Vault Alpha');
        $resp->assertSee('Back to Case Register');
        $resp->assertDontSee('Print Manifest');
        $resp->assertDontSee('Printable View');
        $resp->assertSee('OFFICIAL EVIDENCE CUSTODY &amp; VAULT STORAGE MANIFEST', false);
        $resp->assertDontSee('CUSTODIAL INTEGRITY CERTIFICATION &amp; OFFICIAL ENDORSEMENT', false);
        $resp->assertDontSee('EVIDENCE VAULT CUSTODIAN');
        $resp->assertDontSee('DIRECTORATE ENDORSEMENT &amp; STAMP', false);
        $resp->assertSee('Open Case Review');

        // Eye icon on table has data-vault-url
        $tableResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?status=Vault+Storage', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $tableResp->assertStatus(200);
        $tableResp->assertSee("data-vault-url=\"" . url("/Forensic_Head/cases/{$case->case_number}/vault-exhibits") . "\"", false);
        $tableResp->assertSee("View Vault Storage Exhibit Dossier");
    }
}
