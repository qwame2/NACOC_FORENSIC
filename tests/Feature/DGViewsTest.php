<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DGViewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_dg_portal(): void
    {
        $response = $this->get('/DG');
        $response->assertRedirect('/login');

        $responseCases = $this->get('/DG/cases');
        $responseCases->assertRedirect('/login');
    }

    #[Test]
    public function unapproved_or_non_head_user_cannot_access_dg_portal(): void
    {
        $staffUser = User::factory()->create([
            'fullname' => 'Field Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staffUser)->get('/DG');
        $response->assertRedirect('/cases');
    }

    #[Test]
    public function authorized_dg_head_can_access_dashboard_and_cases(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-DG-2026-001',
            'title' => 'National Interdiction Operation',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => 'Insp. Boateng',
            'suspect_name' => 'Yaw Mensah',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
        ]);

        // Dashboard
        $responseDash = $this->actingAs($dgUser)->get('/DG');
        $responseDash->assertStatus(200);
        $responseDash->assertViewIs('DG.dashboard');
        $responseDash->assertSee('Office of the Director-General Command Dashboard');

        // Dashboard redirect route
        $responseDashRedir = $this->actingAs($dgUser)->get('/DG/dashboard');
        $responseDashRedir->assertRedirect('/DG');

        // Cases Register
        $responseCases = $this->actingAs($dgUser)->get('/DG/cases');
        $responseCases->assertStatus(200);
        $responseCases->assertViewIs('DG.cases');
        $responseCases->assertSee('CAS-DG-2026-001');

        // Ajax table search
        $responseAjax = $this->actingAs($dgUser)->get('/DG/cases?search=CAS-DG-2026-001', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $responseAjax->assertStatus(200);
        $responseAjax->assertViewIs('DG.partials.cases_table');
        $responseAjax->assertSee('CAS-DG-2026-001');

        // Review file
        $responseReview = $this->actingAs($dgUser)->get('/DG/cases/' . $case->case_number . '/review');
        $responseReview->assertStatus(200);
        $responseReview->assertViewIs('DG.show_review');
        $responseReview->assertSee('CAS-DG-2026-001');

        // Approve via DG endpoint
        $responseApprove = $this->actingAs($dgUser)->post('/DG/cases/' . $case->case_number . '/approve');
        $responseApprove->assertStatus(302);
        $this->assertEquals('Approved', $case->fresh()->status);

        // Notifications
        $responseNotif = $this->actingAs($dgUser)->get('/DG/notifications');
        $responseNotif->assertStatus(200);

        // Personnel Roster
        $responseRoster = $this->actingAs($dgUser)->get('/DG/personnel-roster');
        $responseRoster->assertStatus(200);

        // Court Evidence
        $responseCourt = $this->actingAs($dgUser)->get('/DG/court-evidence');
        $responseCourt->assertStatus(200);
    }

    #[Test]
    public function dg_sidebar_and_dashboard_omit_forbidden_actions_and_cannot_create_cases(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        // 1. DG Dashboard does not show Create Case, Report, Reviews & Approvals, Notifications, Permissions, Staff Audit Logs
        $response = $this->actingAs($dgUser)->get('/DG');
        $response->assertStatus(200);
        $response->assertDontSee('Create New Case');
        $response->assertDontSee('Reviews &amp; Approvals', false);
        $response->assertDontSee('Reviews & Approvals', false);
        $response->assertDontSee('Permissions');
        $response->assertDontSee('Staff Audit Logs');
        $response->assertDontSee('National Audit Logs');

        // Check sidebar directly
        $sidebarView = view('DG.partials.sidebar', ['authUser' => $dgUser, 'isDGUser' => true])->render();
        $this->assertStringNotContainsString('Create New Case', $sidebarView);
        $this->assertStringNotContainsString('Reviews & Approvals', $sidebarView);
        $this->assertStringNotContainsString('Reviews &amp; Approvals', $sidebarView);
        $this->assertStringNotContainsString('Permissions', $sidebarView);
        $this->assertStringNotContainsString('Staff Audit Logs', $sidebarView);
        $this->assertStringNotContainsString('National Audit Logs', $sidebarView);
        $this->assertStringNotContainsString('Notifications', $sidebarView);
        $this->assertStringNotContainsString('Court Evidence & SITREP', $sidebarView);
        $this->assertStringNotContainsString('Master Directory', $sidebarView);
        $this->assertStringNotContainsString('Supreme Dashboard', $sidebarView);
        $this->assertStringNotContainsString('National Case Register', $sidebarView);
        $this->assertStringContainsString('Dashboard', $sidebarView);
        $this->assertStringContainsString('Case Register', $sidebarView);

        // 2. Shared sidebar fallback renders DG sidebar when DG visits shared views
        $sharedSidebarView = view('Crime_Scene_Support.partials.sidebar', ['authUser' => $dgUser, 'isDGUser' => true])->render();
        $this->assertStringNotContainsString('Create New Case', $sharedSidebarView);
        $this->assertStringNotContainsString('Reviews & Approvals', $sharedSidebarView);
        $this->assertStringNotContainsString('Staff Audit Logs', $sharedSidebarView);
        $this->assertStringNotContainsString('National Audit Logs', $sharedSidebarView);
        $this->assertStringNotContainsString('Notifications', $sharedSidebarView);

        // 3. DG cannot navigate to /cases/create
        $createPageResponse = $this->actingAs($dgUser)->get('/cases/create');
        $createPageResponse->assertRedirect('/DG/cases');
        $createPageResponse->assertSessionHas('error');

        // 4. DG cannot store a case via POST /cases
        $storeResponse = $this->actingAs($dgUser)->post('/cases', [
            'case_number' => 'CAS-ILLEGAL-001',
            'title' => 'Test Illegal Case',
        ]);
        $storeResponse->assertRedirect('/DG/cases');
        $storeResponse->assertSessionHas('error');
    }

    #[Test]
    public function dg_vault_storage_table_has_exhibit_modal_and_payload_for_suspect_exhibits(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-VAULT-2026-999',
            'title' => 'High Value Heroin Interdiction',
            'unit_name' => 'Evidence Management',
            'submitting_officer' => 'Insp. Boateng',
            'suspect_name' => 'Kwame Brown',
            'suspect_alias' => 'El Brown',
            'suspect_disposition' => 'In Custody',
            'raid_location' => 'Tema Port Terminal 3',
            'status' => 'Vault Storage',
            'priority' => 'CRITICAL',
            'docket_data' => [
                'suspect' => [
                    'name' => 'Kwame Brown',
                    'alias' => 'El Brown',
                    'disposition' => 'In Custody',
                ],
                'seizures' => [
                    [
                        'name' => 'Brown Heroin Cakes',
                        'quantity' => '12.5 kg',
                        'packaging' => 'Vacuum Sealed Plastic',
                        'concealment' => 'Vehicle Fuel Tank False Compartment',
                        'custody_location' => 'Main Vault Safe 4B',
                        'seizing_officer' => 'Insp. Boateng',
                        'date' => '2026-11-20',
                        'photos' => [
                            '/storage/evidence/heroin_pack.jpg',
                            '/storage/evidence/false_tank.jpg',
                        ],
                    ],
                ],
            ],
        ]);

        // Full page view includes vault modal container
        $response = $this->actingAs($dgUser)->get('/DG/cases?status=Vault+Storage');
        $response->assertStatus(200);
        $response->assertSee('id="vaultExhibitModal"', false);
        $response->assertSee('id="vaultPhotoLightboxModal"', false);
        $response->assertSee('openVaultPhotoLightbox', false);
        $response->assertSee('data-vault-payload', false);
        $response->assertSee('case-review-btn', false);
        $response->assertSee('handleCaseEyeClick', false);
        $response->assertSee('CAS-VAULT-2026-999');
        $response->assertSee('Brown Heroin Cakes');

        // Ajax table render also contains payload and eye handler
        $responseAjax = $this->actingAs($dgUser)->get('/DG/cases?status=Vault+Storage', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $responseAjax->assertStatus(200);
        $responseAjax->assertSee('data-vault-payload', false);
        $responseAjax->assertSee('handleCaseEyeClick', false);
        $responseAjax->assertSee('Brown Heroin Cakes');

        // Verify that base64 data-vault-payload can be decoded and contains full exhibit details
        preg_match('/data-vault-payload="([^"]+)"/', $responseAjax->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);
        $decoded = json_decode(base64_decode($matches[1]), true);
        $this->assertIsArray($decoded);
        $this->assertEquals('CAS-VAULT-2026-999', $decoded['case_number']);
        $this->assertEquals('Kwame Brown', $decoded['suspect_name']);
        $this->assertNotEmpty($decoded['exhibits']);
        $firstExhibit = $decoded['exhibits'][0];
        $this->assertEquals('Brown Heroin Cakes', $firstExhibit['commodity_name']);
        $this->assertEquals('Vacuum Sealed Plastic', $firstExhibit['packaging']);
        $this->assertEquals('Vehicle Fuel Tank False Compartment', $firstExhibit['concealment']);
        $this->assertArrayHasKey('details', $firstExhibit);
        $this->assertArrayHasKey('lab', $firstExhibit);
        $this->assertArrayHasKey('vehicle', $firstExhibit);
        $this->assertArrayHasKey('weapon', $firstExhibit);
        $this->assertArrayHasKey('device', $firstExhibit);
        $this->assertArrayHasKey('chain_of_custody', $firstExhibit);
        $this->assertNotEmpty($firstExhibit['photos']);
        $this->assertCount(2, $firstExhibit['photos']);

        // Dedicated Vault Storage Exhibit Page for DG
        $responsePage = $this->actingAs($dgUser)->get('/DG/cases/' . $case->case_number . '/vault-exhibits');
        $responsePage->assertStatus(200);
        $responsePage->assertSee('Vault Storage');
        $responsePage->assertSee('Complete Exhibit Dossier');
        $responsePage->assertSee('CAS-VAULT-2026-999');
        $responsePage->assertSee('Kwame Brown');
        $responsePage->assertSee('Brown Heroin Cakes');
        $responsePage->assertSee('Vacuum Sealed Plastic');
        $responsePage->assertSee('Vehicle Fuel Tank False Compartment');
        $responsePage->assertSee('Back to Case Register');
        $responsePage->assertDontSee('Print Manifest');
        $responsePage->assertDontSee('Printable View');
        $responsePage->assertSee('Open Case Review');
    }

    #[Test]
    public function dg_can_filter_and_preview_suspect_report_with_cover_page_and_details(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-REPORT-2026-001',
            'title' => 'Cross-Border Narcotics Interdiction',
            'unit_name' => 'Evidence Management',
            'submitting_officer' => 'Chief Insp. Mensah',
            'suspect_name' => 'Emmanuel Osei',
            'suspect_alias' => 'Scorpion',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
            'gender' => 'Male',
            'age' => 38,
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-789012345-1',
            'offense_description' => 'Unlawful Possession of Narcotic Drugs Contrary to Section 37 of Act 1019',
            'holding_facility' => 'NACOC Central Holding Facility',
            'raid_location' => 'Aflao Border Crossing, Volta Region',
            'incident_date' => '2026-02-15 09:30:00',
            'docket_data' => [
                'brief_facts' => 'On Sunday, 15 February 2026, NACOC operatives intercepted the suspect attempting to smuggle contraband.',
                'seizures' => [
                    [
                        'exhibit_number' => 'EX-REP-01',
                        'commodity_name' => 'High Grade Cocaine Slabs',
                        'category' => 'Narcotics',
                        'seizure_quantity' => '12 Compressed Bricks',
                        'seizure_weight' => '14.5',
                        'seizure_unit' => 'kg',
                        'quantity' => '12 Compressed Bricks (14.5 kg)',
                        'seizure_value' => '4500000.00',
                        'estimated_value' => 'GHS 4,500,000.00',
                        'packaging' => 'Waterproof Cellophane',
                        'concealment' => 'False Bottom Suitcase',
                        'holding_facility' => 'Forensic Central Vault',
                    ]
                ]
            ]
        ]);

        // 1. Case Register page displays the report button and trigger function
        $responseCases = $this->actingAs($dgUser)->get('/DG/cases');
        $responseCases->assertStatus(200);
        $responseCases->assertSee('Print Suspect Report');
        $responseCases->assertSee('openSuspectReportPreview');

        // 2. Suspect Report Preview page (Default full register)
        $responsePreview = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview');
        $responsePreview->assertStatus(200);
        $responsePreview->assertViewIs('DG.suspect_report_preview');

        // Cover Page assertions (identical styling to other unit report cover pages)
        $responsePreview->assertSee('REPUBLIC OF GHANA');
        $responsePreview->assertSee('NARCOTICS CONTROL COMMISSION');
        $responsePreview->assertSee('FORENSIC DEPARTMENT');
        $responsePreview->assertSee('OFFICE OF THE DIRECTOR-GENERAL');
        $responsePreview->assertSee('EXECUTIVE CASE REGISTER');
        $responsePreview->assertSee('OFFICIAL SUSPECT &amp; SEIZED PROPERTY REPORT', false);
        $responsePreview->assertSee('Brief Facts &amp; Custodial Property Inventory', false);
        $responsePreview->assertSee('Print / Save PDF');
        $responsePreview->assertSee('Back to Case Register');
        $responsePreview->assertDontSee('Executive Suspect &amp; Exhibit Report Preview', false);
        $responsePreview->assertDontSee('bg-emerald-400');

        // Details Page assertions (suspect info, brief statement of facts, exhibits table)
        $responsePreview->assertSee('Suspect Important Details');
        $responsePreview->assertSee('suspect-details-row');
        $responsePreview->assertSee('suspect-biodata-grid');
        $responsePreview->assertSee('grid-cols-2');
        $responsePreview->assertDontSee('grid-cols-1 md:grid-cols-2');
        $responsePreview->assertDontSee('flex-col sm:flex-row');
        $responsePreview->assertSee('Emmanuel Osei');
        $responsePreview->assertSee('Scorpion');
        $responsePreview->assertSee('GHA-789012345-1');
        $responsePreview->assertSee('Brief Statement of Facts');
        $responsePreview->assertDontSee('Offence / Charge Description:');
        $responsePreview->assertSee('Unlawful Possession of Narcotic Drugs Contrary to Section 37 of Act 1019');
        $responsePreview->assertSee('Associated Seized Exhibits &amp; Custodial Property', false);
        $responsePreview->assertSee('High Grade Cocaine Slabs');
        $responsePreview->assertSee('14.5 kg');
        $responsePreview->assertSee('Quantity / Weight');
        $responsePreview->assertSee('Estimated Value');
        $responsePreview->assertSee('Packaging');
        $responsePreview->assertSee('Concealment Method');

        // Strict vocabulary constraints: no dossier, docket, Directorate in the preview view
        $responsePreview->assertDontSee('dossier');
        $responsePreview->assertDontSee('docket');
        $responsePreview->assertDontSee('Directorate');

        // 3. Filter by date range
        $responseDateFilter = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?date_from=2026-02-01&date_to=2026-02-28');
        $responseDateFilter->assertStatus(200);
        $responseDateFilter->assertSee('PERIOD: 01 Feb 2026 — 28 Feb 2026');
        $responseDateFilter->assertSee('Emmanuel Osei');

        // 4. Filter by single case number
        $responseSingleCase = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-REPORT-2026-001');
        $responseSingleCase->assertStatus(200);
        $responseSingleCase->assertSee('INDIVIDUAL CASE REFERENCE: CAS-REPORT-2026-001');
        $responseSingleCase->assertSee('Emmanuel Osei');
    }
}
