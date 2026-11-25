<?php

namespace Tests\Feature;

use App\Models\DpfReport;
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

        // Ajax unit filter: Digital & Physical Forensics
        $dpfCase = ForensicCase::create([
            'case_number' => 'CAS-DG-DPF-2026-002',
            'title' => 'Digital Phone Extraction',
            'unit_name' => 'Digital & Physical Forensic Unit',
            'status' => 'Pending Approval',
            'priority' => 'High',
            'docket_data' => ['suspects' => []],
        ]);
        $responseDpfAjax = $this->actingAs($dgUser)->get('/DG/cases?unit=Digital+%26+Physical+Forensics', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $responseDpfAjax->assertStatus(200);
        $responseDpfAjax->assertSee('CAS-DG-DPF-2026-002');
        $responseDpfAjax->assertDontSee('CAS-DG-2026-001');

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
        $this->assertStringNotContainsString('Personnel Details', $sidebarView);
        $this->assertStringContainsString('Dashboard', $sidebarView);
        $this->assertStringContainsString('Case Register', $sidebarView);

        // 2. Shared sidebar fallback renders DG sidebar when DG visits shared views
        $sharedSidebarView = view('Crime_Scene_Support.partials.sidebar', ['authUser' => $dgUser, 'isDGUser' => true])->render();
        $this->assertStringNotContainsString('Create New Case', $sharedSidebarView);
        $this->assertStringNotContainsString('Reviews & Approvals', $sharedSidebarView);
        $this->assertStringNotContainsString('Staff Audit Logs', $sharedSidebarView);
        $this->assertStringNotContainsString('National Audit Logs', $sharedSidebarView);
        $this->assertStringNotContainsString('Notifications', $sharedSidebarView);
        $this->assertStringNotContainsString('Personnel Details', $sharedSidebarView);

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
        $responseCases->assertSee('btnSuspectReportPreview');
        $responseCases->assertSee('bg-emerald-600');
        $responseCases->assertSee('text-white');
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

        // Executive Analytical Summary (Essay Form) assertions
        $responsePreview->assertSee('Executive Summary &amp; Operational Overview', false);
        $responsePreview->assertSee('Operational Scope, Temporal Pacing &amp; Priority Case Distribution', false);
        $responsePreview->assertSee('Suspect Population, Syndicate Structures &amp; Demographic Profile', false);
        $responsePreview->assertSee('Geographic Theatres &amp; High-Incident Operational Hotspots', false);
        $responsePreview->assertSee('Custodial Evidence Inventory, Contraband Profiling &amp; Monetary Asset Interdiction', false);
        $responsePreview->assertSee('Total Subjects');
        $responsePreview->assertSee('Gender Profile');
        $responsePreview->assertSee('Primary Hotspot');
        $responsePreview->assertSee('Custodial Exhibits');

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
        $responseSingleCase->assertSee('SUSPECT:');
        $responseSingleCase->assertSee('Emmanuel Osei');
        $responseSingleCase->assertSee('id="coverSuspectName"', false);
        $responseSingleCase->assertDontSee('Individual Case Analysis');
        $responseSingleCase->assertSee('This executive summary focuses specifically on the individual forensic investigation for');
    }

    #[Test]
    public function dg_suspect_report_preview_renders_valid_mugshot_url_with_storage_prefix(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $diskMug = 'suspects/2026/11/dg_test_mugshot.jpg';

        $case = ForensicCase::create([
            'case_number' => 'CAS-DG-MUG-001',
            'title' => 'Cocaine Smuggling Operation',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Samuel Mensah',
            'status' => 'Pending Approval',
            'mugshot_front' => $diskMug,
            'docket_data' => [
                'suspect_name' => 'Samuel Mensah',
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Samuel Mensah',
                        'role' => 'Primary Suspect',
                        'mugshot_front' => $diskMug,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-DG-MUG-001');
        $response->assertStatus(200);
        $response->assertSee('storage/suspects/2026/11/dg_test_mugshot.jpg');
        $response->assertDontSee('Photo Not Available');
        $response->assertDontSee('src="' . $diskMug . '"');
    }

    #[Test]
    public function dg_suspect_report_preview_supports_auto_pagination_structure_for_long_content_and_multiple_exhibits(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-MULTI-EXH-001',
            'title' => 'Syndicate Interdiction with Multiple Hardware Exhibits',
            'unit_name' => 'Digital Forensics',
            'submitting_officer' => 'Chief Insp. Mensah',
            'suspect_name' => 'Daniel Kodzo',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
            'offense_description' => 'Suspect apprehended in possession of cyber attack equipment and communication terminals.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Daniel Kodzo',
                        'role' => 'Principal Target',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-08799-01',
                        'commodity_name' => 'ASUS ROG Zephyrus G16 High-Performance Gaming Laptop',
                        'associated_person' => 'Daniel Kodzo',
                        'packaging' => 'Official Sealed Evidence Bag',
                        'quantity' => '1 Unit',
                    ],
                    [
                        'exhibit_number' => 'EXH-08799-02',
                        'commodity_name' => 'Alfa Network Long-Range High-Gain USB Wi-Fi Adapter',
                        'associated_person' => 'Daniel Kodzo',
                        'packaging' => 'Official Sealed Evidence Bag',
                        'quantity' => '1 Unit',
                    ],
                    [
                        'exhibit_number' => 'EXH-08799-03',
                        'commodity_name' => 'Apple iPhone 13 Mini (128GB, Midnight)',
                        'associated_person' => 'Daniel Kodzo',
                        'packaging' => 'Faraday Isolation Bag',
                        'quantity' => '1 Unit',
                    ],
                    [
                        'exhibit_number' => 'EXH-08799-04',
                        'commodity_name' => 'Crucial X8 1TB Portable USB 3.2 SSD',
                        'associated_person' => 'Daniel Kodzo',
                        'packaging' => 'Anti-Static Sealed Evidence Pouch',
                        'quantity' => '1 Unit',
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-MULTI-EXH-001');
        $response->assertStatus(200);

        // Assert semantic DOM anchors for client-side auto-pagination exist
        $response->assertSee('auto-paginated-suspect');
        $response->assertSee('page-inner-wrapper');
        $response->assertSee('page-flow-body');
        $response->assertSee('exhibits-list-container');
        $response->assertSee('exhibit-card');
        $response->assertSee('autoPaginateAllSuspects()');
        $response->assertSee('suspect-continuation-page');

        // Assert all 4 exhibits are cataloged cleanly in the response payload
        $response->assertSee('EXH-08799-01');
        $response->assertSee('ASUS ROG Zephyrus G16 High-Performance Gaming Laptop');
        $response->assertSee('EXH-08799-02');
        $response->assertSee('Alfa Network Long-Range High-Gain USB Wi-Fi Adapter');
        $response->assertSee('EXH-08799-03');
        $response->assertSee('Apple iPhone 13 Mini (128GB, Midnight)');
        $response->assertSee('EXH-08799-04');
        $response->assertSee('Crucial X8 1TB Portable USB 3.2 SSD');

        // Non-drug case must NOT display Laboratory Examination Findings or Laboratory #
        $response->assertDontSee('Laboratory Examination Findings');
        $response->assertDontSee('Laboratory #');
    }

    #[Test]
    public function non_drug_case_in_suspect_report_preview_does_not_have_lab_id_or_laboratory_examination_findings(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        // 1. Non-drug digital case
        $digitalCase = ForensicCase::create([
            'case_number' => 'CAS-CYBER-2026-701',
            'title' => 'Operation Cyber Shield Target Arrest',
            'unit_name' => 'Digital & Physical Forensic Unit',
            'submitting_officer' => 'Chief Insp. Mensah',
            'suspect_name' => 'Victor Agbenu',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
            'offense_description' => 'Cyber breach and unencrypted credential harvesting.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Victor Agbenu',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-CYBER-01',
                        'commodity_name' => 'Alfa AWUS036ACH Long-Range Wi-Fi Adapter',
                        'associated_person' => 'Victor Agbenu',
                        'packaging' => 'Official Sealed Evidence Bag',
                        'quantity' => '1 Unit',
                    ],
                ],
            ],
        ]);

        $this->assertFalse($digitalCase->isDrugCase());
        $this->assertNull($digitalCase->laboratory_number);

        $responseDigital = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-CYBER-2026-701');
        $responseDigital->assertStatus(200);
        $responseDigital->assertSee('Victor Agbenu');
        $responseDigital->assertSee('EXH-CYBER-01');
        $responseDigital->assertSee('Alfa AWUS036ACH Long-Range Wi-Fi Adapter');

        // Must NOT see Laboratory Examination Findings or Laboratory # for non-drug case
        $responseDigital->assertDontSee('Laboratory Examination Findings');
        $responseDigital->assertDontSee('Laboratory #');

        // 2. Drug case
        $drugCase = ForensicCase::create([
            'case_number' => 'CAS-DRUG-2026-802',
            'title' => 'Cocaine Slabs Interdiction',
            'unit_name' => 'Forensic Science Laboratory',
            'submitting_officer' => 'Chief Insp. Mensah',
            'suspect_name' => 'Kofi Boateng',
            'status' => 'Pending Approval',
            'priority' => 'CRITICAL',
            'offense_description' => 'Possession of narcotic substances.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Kofi Boateng',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-DRUG-01',
                        'commodity_name' => 'High Purity White Cocaine Slabs',
                        'seizure_name_drug' => 'Cocaine',
                        'associated_person' => 'Kofi Boateng',
                        'packaging' => 'Vacuum Sealed Plastic',
                        'quantity' => '5.2 kg',
                        'presumptive_result' => 'Cobalt Thiocyanate Positive for Cocaine',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($drugCase->isDrugCase());

        $responseDrug = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-DRUG-2026-802');
        $responseDrug->assertStatus(200);
        $responseDrug->assertSee('Kofi Boateng');
        $responseDrug->assertSee('High Purity White Cocaine Slabs');
        $responseDrug->assertSee('Laboratory Examination Findings');
        $responseDrug->assertSee('lab-findings-table');
        $responseDrug->assertSee('table-fixed');
        $responseDrug->assertSee('break-words');
        $responseDrug->assertSee('overflow-wrap');
    }

    #[Test]
    public function electronic_device_case_displays_legal_and_operational_process_status_only_when_completed_by_dpf_unit(): void
    {
        $dgUser = User::factory()->create([
            'fullname' => 'Director General Kofi Mensah',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        // 1. Electronic Case WITH completed DpfReport
        $dpfCase = ForensicCase::create([
            'case_number' => 'CAS-ELEC-2026-901',
            'title' => 'Digital Evidence Harvesting Operation',
            'unit_name' => 'Digital Forensics',
            'submitting_officer' => 'Chief Insp. Mensah',
            'suspect_name' => 'Daniel Kodzo',
            'status' => 'Pending Approval',
            'priority' => 'HIGH',
            'offense_description' => 'Suspect apprehended in possession of encrypted storage media and covert communications equipment.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Daniel Kodzo',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-ELEC-01',
                        'commodity_name' => 'Crucial X8 1TB Portable USB 3.2 SSD',
                        'category' => 'Electronic Devices',
                        'associated_person' => 'Daniel Kodzo',
                        'packaging' => 'Anti-Static Sealed Evidence Pouch',
                        'quantity' => '1 Unit',
                    ],
                ],
            ],
        ]);

        DpfReport::create([
            'dpf_code' => 'DPF-2026-901-A',
            'case_number' => 'CAS-ELEC-2026-901',
            'single_suspect_case_id' => $dpfCase->id,
            'user_id' => $dgUser->id,
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES',
            'extraction_analysis_message' => 'Bit-stream forensic image successfully acquired using Tableau write-blocker.',
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_message' => 'Formal technical expert witness statement submitted to investigations.',
            'intel_shared' => 'YES',
            'status' => 'active',
        ]);

        $responseCompleted = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-ELEC-2026-901');
        $responseCompleted->assertStatus(200);
        $responseCompleted->assertSee('Legal &amp; Operational Process Status', false);
        $responseCompleted->assertSee('DPF REF: DPF-2026-901-A');
        $responseCompleted->assertSee('Court Order Warrant Requested');
        $responseCompleted->assertSee('Court Order Warrant Granted');
        $responseCompleted->assertSee('Extraction &amp; Forensic Analysis Conducted', false);
        $responseCompleted->assertSee('Report, Witness Statement &amp; Digital Evidence Sent to Investigations', false);
        $responseCompleted->assertSee('Intelligence Shared to Intel Department for Action');
        // Non-drug case: must not show lab findings
        $responseCompleted->assertDontSee('Laboratory Examination Findings');

        // 2. Electronic Case WITHOUT DpfReport (not completed by DPF Unit)
        $noDpfCase = ForensicCase::create([
            'case_number' => 'CAS-ELEC-2026-902',
            'title' => 'Pending Hardware Seizure Registration',
            'unit_name' => 'Digital Forensics',
            'submitting_officer' => 'Insp. Darko',
            'suspect_name' => 'Kwesi Arthur',
            'status' => 'Pending Approval',
            'priority' => 'MEDIUM',
            'offense_description' => 'Suspect intercepted with encrypted mobile handset awaiting extraction.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Kwesi Arthur',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-ELEC-02',
                        'commodity_name' => 'Apple iPhone 15 Pro Max',
                        'category' => 'Electronic Devices',
                        'associated_person' => 'Kwesi Arthur',
                        'packaging' => 'Faraday Isolation Bag',
                        'quantity' => '1 Unit',
                    ],
                ],
            ],
        ]);

        $responsePending = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-ELEC-2026-902');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('Apple iPhone 15 Pro Max');
        // Must NOT see Legal & Operational Process Status because DPF Unit has not completed/reported it
        $responsePending->assertDontSee('Legal &amp; Operational Process Status', false);
        $responsePending->assertDontSee('Court Order Warrant Requested');
        $responsePending->assertDontSee('Extraction &amp; Forensic Analysis Conducted', false);

        // 3. Drug Case (should NOT display Legal & Operational Process Status)
        $pureDrugCase = ForensicCase::create([
            'case_number' => 'CAS-DRUG-2026-903',
            'title' => 'Heroin Bulk Seizure',
            'unit_name' => 'Forensic Science Laboratory',
            'submitting_officer' => 'Insp. Darko',
            'suspect_name' => 'Musa Haruna',
            'status' => 'Pending Approval',
            'priority' => 'CRITICAL',
            'offense_description' => 'Trafficking of heroin packages.',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Musa Haruna',
                    ],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-DRUG-03',
                        'commodity_name' => 'Brown Heroin Compressed Powder',
                        'seizure_name_drug' => 'Heroin',
                        'category' => 'Narcotic Drug Substance',
                        'associated_person' => 'Musa Haruna',
                        'packaging' => 'Heat-Sealed Mylar',
                        'quantity' => '3.5 kg',
                    ],
                ],
            ],
        ]);

        $responseDrug = $this->actingAs($dgUser)->get('/DG/cases/suspect-report-preview?case_number=CAS-DRUG-2026-903');
        $responseDrug->assertStatus(200);
        $responseDrug->assertSee('Brown Heroin Compressed Powder');
        $responseDrug->assertDontSee('Legal &amp; Operational Process Status', false);
        $responseDrug->assertDontSee('Court Order Warrant Requested');
    }
}
