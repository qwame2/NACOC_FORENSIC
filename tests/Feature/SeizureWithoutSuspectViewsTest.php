<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DpfReport;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeizureWithoutSuspectViewsTest extends TestCase
{
    use RefreshDatabase;

    private function makeApprovedOfficer(string $unit): User
    {
        return User::factory()->create([
            'fullname' => "{$unit} Test Officer",
            'unit' => $unit,
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => false,
        ]);
    }

    #[Test]
    public function digital_forensics_and_lab_analysis_create_views_render_seizure_without_suspect_logic(): void
    {
        $routes = [
            'Digital Forensics' => '/Digital_Forensics/create',
            'Laboratory Analysis' => '/Lab_Analysis/create',
        ];

        foreach ($routes as $unit => $url) {
            $officer = $this->makeApprovedOfficer($unit);
            $response = $this->actingAs($officer)->get($url);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('id="noSuspectToggleCheckbox"', $html, "Missing toggle checkbox on {$url}");
            $this->assertStringContainsString('Seizure Without Suspect (Disable Sections 1 &ndash; 3)', $html, "Missing toggle label on {$url}");
            $this->assertStringContainsString('<div id="sections1To3Wrapper"', $html, "Missing sections1To3Wrapper on {$url}");
            $this->assertStringContainsString('class="seizure-ownership-default"', $html, "Missing seizure-ownership-default on {$url}");
            $this->assertStringContainsString('class="seizure-ownership-unassigned hidden"', $html, "Missing seizure-ownership-unassigned on {$url}");
            $this->assertStringContainsString('window.isSections1To3OptionalMode()', $html, "Missing isSections1To3OptionalMode check on {$url}");
            $this->assertStringContainsString('no_suspect: noSuspectMode ? 1 : 0', $html, "Missing no_suspect payload field on {$url}");
            $this->assertStringContainsString('syncSections1To3OptionalState()', $html, "Missing syncSections1To3OptionalState call on {$url}");
            $this->assertStringContainsString('window.syncSeizureOwnershipBoxes()', $html, "Missing syncSeizureOwnershipBoxes call on {$url}");
        }
    }

    #[Test]
    public function digital_forensics_and_lab_analysis_can_store_no_suspect_seizure_docket(): void
    {
        $units = [
            'Digital Forensics' => [
                'unit_name' => 'Digital Forensics',
                'exhibit_category' => 'Electronic Device',
                'seizure_name' => 'Apple iPhone 15 Pro',
                'seizure_name_device' => 'Apple iPhone 15 Pro',
            ],
            'Laboratory Analysis' => [
                'unit_name' => 'Laboratory Analysis',
                'exhibit_category' => 'Drugs',
                'seizure_name' => 'Cocaine',
                'seizure_name_drug' => 'Cocaine',
            ],
        ];

        foreach ($units as $unit => $payload) {
            $officer = $this->makeApprovedOfficer($unit);

            $response = $this->actingAs($officer)->postJson('/cases', [
                'no_suspect' => 1,
                'title' => "Seized Exhibit: {$payload['seizure_name']} (Awaiting Suspect)",
                'unit_name' => $payload['unit_name'],
                'suspect_name' => 'Unassigned (No Suspect)',
                'has_seizures' => true,
                'exhibit_category' => $payload['exhibit_category'],
                'seizure_name' => $payload['seizure_name'],
                'seizure_name_drug' => $payload['seizure_name_drug'] ?? null,
                'seizure_name_device' => $payload['seizure_name_device'] ?? null,
                'seizures' => [[
                    'item_number' => 1,
                    'exhibit_category' => $payload['exhibit_category'],
                    'associated_person' => 'Unassigned (No Suspect)',
                    'seizure_name' => $payload['seizure_name'],
                    'seizure_name_drug' => $payload['seizure_name_drug'] ?? '',
                    'seizure_name_device' => $payload['seizure_name_device'] ?? '',
                ]],
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('suspects_count', 0);

            $stored = ForensicCase::allCases()->firstWhere('unit_name', $payload['unit_name']);
            $this->assertNotNull($stored);
            $this->assertSame('Unassigned (No Suspect)', $stored->suspect_name);
        }

        $this->assertSame(2, ForensicCase::allCases()->count());
    }

    #[Test]
    public function awaiting_suspect_exhibits_saved_by_another_head_appear_in_selector_and_api_across_all_unit_views(): void
    {
        $headA = $this->makeApprovedOfficer('Evidence Management');

        // Head A saves a Seizure Without Suspect docket with rich manifest metadata
        $storeRes = $this->actingAs($headA)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: Cocaine HCL (Awaiting Suspect)',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Unassigned (No Suspect)',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cocaine HCL',
            'seizure_name_drug' => 'Cocaine HCL',
            'seizure_quantity' => '12',
            'seizure_weight' => '14.5',
            'seizure_unit' => 'kg',
            'seizure_packaging' => 'Vacuum-sealed bricks',
            'seizure_value' => '450000',
            'seizure_concealment' => 'False floor inside shipping container',
            'seizure_date' => '2026-11-10',
            'seizure_time' => '14:30',
            'seizure_location' => 'Tema Harbour Gate 4',
            'seizure_reason' => 'Suspected illicit narcotics consignment',
            'source_of_exhibits' => 'Customs Joint Inspection',
            'investigating_officer' => 'Insp. Kofi Mensah',
            'source_requesting_unit' => 'Port Interdiction Unit',
            'exhibit_remark' => 'Initial field NIK test positive',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'Cocaine HCL',
                'seizure_name_drug' => 'Cocaine HCL',
                'seizure_quantity' => '12',
                'seizure_weight' => '14.5',
                'seizure_unit' => 'kg',
                'seizure_packaging' => 'Vacuum-sealed bricks',
                'seizure_value' => '450000',
                'seizure_concealment' => 'False floor inside shipping container',
                'seizure_date' => '2026-11-10',
                'seizure_time' => '14:30',
                'seizure_location' => 'Tema Harbour Gate 4',
                'seizure_reason' => 'Suspected illicit narcotics consignment',
                'source_of_exhibits' => 'Customs Joint Inspection',
                'investigating_officer' => 'Insp. Kofi Mensah',
                'source_requesting_unit' => 'Port Interdiction Unit',
                'exhibit_remark' => 'Initial field NIK test positive',
            ]],
        ]);

        $storeRes->assertOk()->assertJsonPath('success', true);

        $savedExhibit = Exhibit::where('commodity_name', 'Cocaine HCL')->first();
        $this->assertNotNull($savedExhibit);

        // Verify GET /api/exhibits/unassigned returns full manifest payload from Head A
        $headB = $this->makeApprovedOfficer('Laboratory Analysis');
        $apiRes = $this->actingAs($headB)->getJson('/api/exhibits/unassigned');
        $apiRes->assertOk();

        $exhibits = $apiRes->json('exhibits');
        $this->assertNotEmpty($exhibits);
        $matched = collect($exhibits)->firstWhere('id', $savedExhibit->id);
        $this->assertNotNull($matched);
        $this->assertSame('Cocaine HCL', $matched['commodity_name']);
        $this->assertSame('Drugs', $matched['exhibit_category']);
        $this->assertSame('14.5', (string) $matched['seizure_weight']);
        $this->assertSame('kg', $matched['seizure_unit']);
        $this->assertSame('Tema Harbour Gate 4', $matched['seizure_location']);
        $this->assertSame('Insp. Kofi Mensah', $matched['investigating_officer']);
        $this->assertSame('Port Interdiction Unit', $matched['source_requesting_unit']);
        $this->assertSame('Customs Joint Inspection', $matched['source_of_exhibits']);
        $this->assertSame('Initial field NIK test positive', $matched['exhibit_remark']);
        $this->assertSame('Evidence Management', $matched['entered_by_unit']);

        // Verify all 4 unit create pages include the selector hooks, and drug-related exhibits from another unit route strictly to /Lab_Analysis/create
        $unitViews = [
            'Digital Forensics' => '/Digital_Forensics/create',
            'Laboratory Analysis' => '/Lab_Analysis/create',
            'Evidence Management' => '/Evidence_Management/create',
            'Crime Scene Support' => '/Crime_Scene_Support/head/CrimeSceneCreate',
        ];

        foreach ($unitViews as $unit => $url) {
            $viewer = $unit === 'Laboratory Analysis' ? $headB : $this->makeApprovedOfficer($unit);
            $page = $this->actingAs($viewer)->get($url);
            $page->assertOk();
            $html = $page->getContent();

            $this->assertStringContainsString('id="linkedUnassignedExhibitsSelect"', $html, "Missing selector on {$url}");
            $this->assertStringContainsString('activateLinkExistingExhibitsForNoSuspect', $html, "Missing no-suspect activation hook on {$url}");
            $this->assertStringContainsString('autoFillManifestFromLinkedExhibits', $html, "Missing manifest auto-fill function on {$url}");
            $this->assertStringContainsString('linked_exhibit_id:', $html, "Missing linked_exhibit_id payload property on {$url}");
            $this->assertStringNotContainsString('linked_exhibit_ids: noSuspectMode ? [] :', $html, "linked_exhibit_ids still stripped in noSuspectMode on {$url}");

            if ($unit === 'Laboratory Analysis') {
                $this->assertStringContainsString('Cocaine HCL', $html, "Missing Head A drug exhibit in selector on {$url}");
            } else {
                $this->assertStringNotContainsString('Cocaine HCL', $html, "Drug exhibit leaked into non-Lab view {$url}");
            }
        }
    }

    #[Test]
    public function storing_no_suspect_docket_with_linked_exhibit_id_relinks_without_duplicate_exhibit_row(): void
    {
        $headA = $this->makeApprovedOfficer('Evidence Management');

        // Head A initially logs an exhibit without a suspect
        $this->actingAs($headA)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: MacBook Pro 16 (Awaiting Suspect)',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Unassigned (No Suspect)',
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Device',
            'seizure_name' => 'MacBook Pro 16',
            'seizure_name_device' => 'MacBook Pro 16',
            'seizure_location' => 'Kotoka Terminal 3',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Electronic Device',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'MacBook Pro 16',
                'seizure_name_device' => 'MacBook Pro 16',
                'seizure_location' => 'Kotoka Terminal 3',
            ]],
        ])->assertOk();

        $originalExhibit = Exhibit::where('commodity_name', 'MacBook Pro 16')->firstOrFail();
        $this->assertSame(1, Exhibit::where('commodity_name', 'MacBook Pro 16')->count());

        // Head B opens Seizure Without Suspect, auto-fills from Head A's linked exhibit, and submits
        $headB = $this->makeApprovedOfficer('Digital Forensics');
        $linkRes = $this->actingAs($headB)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: MacBook Pro 16 (Awaiting Suspect)',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'linked_exhibit_ids' => [$originalExhibit->id],
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Device',
            'seizure_name' => 'MacBook Pro 16',
            'seizure_name_device' => 'MacBook Pro 16',
            'seizure_location' => 'Kotoka Terminal 3',
            'seizures' => [[
                'item_number' => 1,
                'linked_exhibit_id' => $originalExhibit->id,
                'exhibit_category' => 'Electronic Device',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'MacBook Pro 16',
                'seizure_name_device' => 'MacBook Pro 16',
                'seizure_location' => 'Kotoka Terminal 3',
            ]],
        ]);

        $linkRes->assertOk()->assertJsonPath('success', true);

        // Must NOT duplicate the Exhibit row
        $this->assertSame(1, Exhibit::where('commodity_name', 'MacBook Pro 16')->count());

        $newCase = ForensicCase::allCases()->firstWhere('unit_name', 'Digital Forensics');
        $this->assertNotNull($newCase);
        $originalExhibit->refresh();
        $this->assertSame((int) $newCase->id, (int) $originalExhibit->single_suspect_case_id);
    }

    #[Test]
    public function link_existing_exhibits_displays_all_exhibits_without_suspects_including_legacy_placeholders_and_excludes_assigned(): void
    {
        $officer = $this->makeApprovedOfficer('Laboratory Analysis');

        // 1. Standalone exhibit with NULL associated_person
        $standaloneNull = Exhibit::create([
            'property_id' => 'NACOC/EMU/10001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Standalone Heroin Wrap',
            'associated_person' => null,
            'custodial_status' => 'Seized',
        ]);

        // 2. Awaiting-suspect case exhibit whose associated_person was stored as legacy 'All Suspects'
        $noSuspectCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-NOSUS-1002',
            'title' => 'Seized Exhibit: Fentanyl Vial (Awaiting Suspect)',
            'unit_name' => 'Evidence Management',
            'user_id' => $officer->id,
            'suspect_name' => 'Unassigned (No Suspect)',
            'status' => 'Pending Approval',
        ]);
        $awaitingLegacyAllSuspects = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $noSuspectCase->id,
            'property_id' => 'NACOC/EMU/10002',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Fentanyl Vial',
            'associated_person' => 'All Suspects',
            'custodial_status' => 'Seized',
        ]);

        // 3. New no_suspect docket even if client sends 'All Suspects' gets normalized & appears
        $this->actingAs($officer)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: Ketamine Powder (Awaiting Suspect)',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Unassigned (No Suspect)',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Ketamine Powder',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'All Suspects',
                'suspect_ownership' => 'All Suspects',
                'seizure_name' => 'Ketamine Powder',
            ]],
        ])->assertOk();
        $normalizedExhibit = Exhibit::where('commodity_name', 'Ketamine Powder')->firstOrFail();
        $this->assertSame('Unassigned (No Suspect)', $normalizedExhibit->associated_person);

        // 4. Assigned exhibit with a real suspect MUST NOT appear
        $realCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-REAL-1003',
            'title' => 'Republic vs Kwame Boateng',
            'unit_name' => 'Evidence Management',
            'user_id' => $officer->id,
            'suspect_name' => 'Kwame Boateng',
            'status' => 'Pending Approval',
        ]);
        $assignedExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $realCase->id,
            'property_id' => 'NACOC/EMU/10004',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Assigned Suspect Exhibit',
            'associated_person' => 'Kwame Boateng',
            'custodial_status' => 'Seized',
        ]);

        // Verify /api/exhibits/unassigned includes all 3 without-suspect exhibits and excludes the assigned one
        $apiRes = $this->actingAs($officer)->getJson('/api/exhibits/unassigned')->assertOk();
        $returnedIds = collect($apiRes->json('exhibits'))->pluck('id')->all();
        $this->assertContains($standaloneNull->id, $returnedIds);
        $this->assertContains($awaitingLegacyAllSuspects->id, $returnedIds);
        $this->assertContains($normalizedExhibit->id, $returnedIds);
        $this->assertNotContains($assignedExhibit->id, $returnedIds);

        // Verify the Link Existing Seized Exhibit(s) card visibly renders all 3 exhibits in #availableUnassignedExhibitsList
        $html = $this->actingAs($officer)->get('/Lab_Analysis/create')->assertOk()->getContent();
        $this->assertStringContainsString('id="availableUnassignedExhibitsList"', $html);
        $this->assertStringContainsString('3 Available in Vault', $html);
        $this->assertStringContainsString('Standalone Heroin Wrap', $html);
        $this->assertStringContainsString('Fentanyl Vial', $html);
        $this->assertStringContainsString('Ketamine Powder', $html);
        $this->assertStringNotContainsString('Assigned Suspect Exhibit', $html);
        $this->assertStringContainsString('fillSeizureOfficersRows', $html);
        $this->assertStringContainsString('applyEditableOwnershipOnBlock', $html);
        $this->assertStringContainsString("selectEl.value = 'Unknown'", $html);
        $this->assertStringContainsString('selectEl.disabled = false', $html);
        $this->assertStringContainsString('syncLinkedExhibitPhotos', $html);
        $this->assertStringContainsString('closeAllSeizurePanels', $html);
        $this->assertStringContainsString('_suppressAutoOpenPanels', $html);
        $this->assertStringContainsString('window.updateCommodityField(normalizedCat, block)', $html);
    }

    #[Test]
    public function lab_nlr_for_exhibit_without_suspect_generates_preliminary_report_renders_once_with_all_fields_and_na_fallbacks(): void
    {
        $labAnalyst = $this->makeApprovedOfficer('Laboratory Analysis');
        $headOfficer = User::factory()->create([
            'fullname' => 'Director Forensic Head',
            'unit' => 'Forensic Science',
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => false,
        ]);

        // Unassigned drug exhibit stored earlier without a suspect
        $orphanCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-2026-8801',
            'title' => 'Seized Exhibit: Cocaine HCL (Awaiting Suspect)',
            'suspect_name' => 'Unassigned (No Suspect)',
            'unit_name' => 'Evidence Management',
            'priority' => 'NORMAL',
            'status' => 'Under Multi-Unit Processing',
            'docket_data' => ['no_suspect' => true],
        ]);
        $unassignedExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $orphanCase->id,
            'property_id' => 'NACOC/EMU/8801',
            'exhibit_number' => 'EXH-2026-8801-NFC',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Hydrochloride Blocks',
            'seizure_quantity' => '2',
            'seizure_packaging' => 'Parcels',
            'seizure_weight' => '2.40',
            'seizure_unit' => 'kg',
            'associated_person' => 'Unassigned (No Suspect)',
            'custodial_status' => 'Seized',
        ]);

        // Verify Lab_Analysis/create form includes Section 5 NLR fields in JS submission payload
        $createHtml = $this->actingAs($labAnalyst)->get('/Lab_Analysis/create')->assertOk()->getContent();
        foreach (['lab_workflow_mode:', 'lab_date_received:', 'preliminary_exam_procedure:', 'disposition_of_exhibit:', 'confirmatory_test_result:'] as $needle) {
            $this->assertStringContainsString($needle, $createHtml);
        }

        // Lab analyst links the unassigned drug exhibit without suspect and fills full NLR Section 5 info
        $payload = [
            'no_suspect' => 1,
            'linked_exhibit_ids' => [$unassignedExhibit->id],
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cocaine Hydrochloride Blocks',
            'seizure_name_drug' => 'Cocaine Hydrochloride Blocks',
            'seizure_quantity' => '2',
            'seizure_packaging' => 'Parcels',
            'seizure_weight' => '2.40',
            'seizure_unit' => 'kg',
            'seizures' => [[
                'item_number' => 1,
                'linked_exhibit_id' => $unassignedExhibit->id,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'Cocaine Hydrochloride Blocks',
                'seizure_name_drug' => 'Cocaine Hydrochloride Blocks',
                'commodity_name' => 'Cocaine Hydrochloride Blocks',
                'seizure_quantity' => '2',
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => '2.40',
                'seizure_unit' => 'kg',
            ]],
            'title' => 'Seized Exhibit: Cocaine Hydrochloride Blocks (Awaiting Suspect)',
            'unit_name' => 'Laboratory Analysis',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'dependents' => 'N/A',
            'lab_workflow_mode' => 'nlr',
            'section5_completed' => '1',
            'lab_exam_reference' => 'NLA-FM-T7701-F',
            'laboratory_number' => 'LAB-2026-7701',
            'lab_exhibit_no' => 'A1',
            'lab_exhibit_description' => 'Two compressed white crystalline blocks',
            'lab_analyst' => 'Laboratory Analysis Test Officer',
            'lab_date_received' => '2026-07-10',
            'lab_date_started' => '2026-07-11',
            'lab_date_completed' => '2026-07-12',
            'purpose_of_examination' => 'Qualitative and quantitative chemical assay of suspected cocaine',
            'preliminary_exam_procedure' => 'Scott Cobalt Thiocyanate presumptive spot test and gross weighing',
            'screening_method' => ['GC-MS Agilent 8890', 'FTIR Spectrometer'],
            'presumptive_result' => 'YES | Presence of Cocaine | Purity: 82%',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'White compressed blocks',
            'sample_weight_units' => '2 Parcels',
            'sample_weight_gross' => '2.40 kg',
            'sample_weight_taken' => '0.05 kg',
            'sample_weight_reserved' => '2.35 kg',
            'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
            'detected_substances' => [
                ['substance' => 'Cocaine Hydrochloride', 'percentage' => '82.5'],
            ],
            'confirmatory_test_methods' => 'GSA-SM-T009-GCMS',
            'confirmatory_specifications' => 'Matched against NIST reference mass spectra',
            'confirmatory_remark' => 'Controlled narcotic substance confirmed',
            'disposition_of_exhibit' => 'Remaining 2.35 kg returned to Evidence Vault',
        ];

        $res = $this->actingAs($labAnalyst)->postJson('/cases', $payload);
        $res->assertOk()->assertJsonPath('success', true);
        $caseNumber = $res->json('case.case_number');
        $this->assertNotEmpty($caseNumber);

        // 1. Preliminary Exhibit Analysis Report must be generated immediately in suspect_preliminary_reports
        $this->assertDatabaseHas('suspect_preliminary_reports', [
            'case_number' => $caseNumber,
        ]);
        $prelimReport = SuspectPreliminaryReport::where('case_number', $caseNumber)->first();
        $this->assertNotNull($prelimReport);

        // 2, 3 & 4. Check show_review across units
        foreach ([
            [$headOfficer, "/Forensic_Head/cases/{$caseNumber}/review"],
            [$labAnalyst, "/Lab_Analysis/cases/{$caseNumber}/review"],
        ] as [$actor, $reviewUrl]) {
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();

            // Preliminary report attached on review page
            $this->assertStringContainsString("Lab_Analysis/preliminary-report-pdf/{$caseNumber}", $html);

            // LABORATORY EXAMINATION RESULT card renders strictly ONCE (no duplicate card)
            $this->assertSame(1, substr_count($html, 'data-lab-card="true"'), "Expected exactly 1 lab result card on {$reviewUrl}");

            // Every entered Section 5 NLR field renders on the card
            foreach ([
                'NLA-FM-T7701-F',
                'LAB-2026-7701',
                '2026-07-10',
                '2026-07-11',
                '2026-07-12',
                'Qualitative and quantitative chemical assay of suspected cocaine',
                'Scott Cobalt Thiocyanate presumptive spot test and gross weighing',
                'GC-MS Agilent 8890, FTIR Spectrometer',
                'Remaining 2.35 kg returned to Evidence Vault',
                'GSA-SM-T009-GCMS',
                'Matched against NIST reference mass spectra',
                'Controlled narcotic substance confirmed',
                'Cocaine Hydrochloride',
                '(82.5%)',
                '0.05 kg',
                '2.35 kg',
            ] as $expectedField) {
                $this->assertStringContainsString($expectedField, $html, "Missing '{$expectedField}' on {$reviewUrl}");
            }

            // No mock fallback values when no suspect was assigned
            foreach ([
                'Ghanaian',
                'No Children',
                'NACOC Custody &amp; Holding Facility',
                'NACOC Custody & Holding Facility',
                'Victor Kofi',
                'Agbogbloshie',
                'Thursday, 18 June 2026',
                'NACOC Headquarters',
                'NACOC Forensic Directorate Vault',
                'Directorate Authorized Forensic Case File',
            ] as $forbiddenMock) {
                $this->assertStringNotContainsString($forbiddenMock, $html, "Unexpected mock fallback '{$forbiddenMock}' found on {$reviewUrl}");
            }
        }
    }

    #[Test]
    public function saving_exam_info_on_show_review_for_exhibit_without_suspect_generates_preliminary_report_and_persists_all_fields(): void
    {
        $labAnalyst = $this->makeApprovedOfficer('Laboratory Analysis');
        $forensicHead = User::factory()->create([
            'fullname' => 'Director Forensic Head',
            'unit' => 'Forensic Science',
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => false,
        ]);

        // Pre-existing drug exhibit case without a suspect, initially without Section 5 NLR completed
        $case = ForensicCase::create([
            'case_number' => 'NCC-2026-8855',
            'title' => 'Seized Exhibit: Cannabis Blocks (Awaiting Suspect)',
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'SINGLE',
            'no_suspect' => 1,
            'suspect_name' => 'Awaiting Suspect',
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Compressed Cannabis Blocks',
            'seizure_name_drug' => 'Compressed Cannabis Blocks',
            'seizure_quantity' => '4',
            'seizure_packaging' => 'Slabs',
            'seizure_weight' => '4.20',
            'seizure_unit' => 'kg',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'All Suspects',
                'seizure_name' => 'Compressed Cannabis Blocks',
                'seizure_name_drug' => 'Compressed Cannabis Blocks',
                'commodity_name' => 'Compressed Cannabis Blocks',
                'seizure_quantity' => '4',
                'seizure_packaging' => 'Slabs',
                'seizure_weight' => '4.20',
                'seizure_unit' => 'kg',
            ]],
        ]);

        $exhibit = Exhibit::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($exhibit);

        // Verify Lab_Analysis/show_review JS payload includes all Section 5 NLR fields
        $initialReviewHtml = $this->actingAs($labAnalyst)->get("/Lab_Analysis/cases/{$case->case_number}/review")->assertOk()->getContent();
        foreach (['preliminary_exam_procedure:', 'screening_method:', 'disposition_of_exhibit:', 'purpose_of_examination:'] as $jsNeedle) {
            $this->assertStringContainsString($jsNeedle, $initialReviewHtml, "Missing '{$jsNeedle}' in Lab_Analysis/show_review JS payload");
        }

        // Save Examination Information via /cases/{caseNumber}/update-unit-progress (exact show_review path)
        $resp = $this->actingAs($labAnalyst)->postJson("/cases/{$case->case_number}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'Completed',
            'officer' => 'Laboratory Analysis Test Officer',
            'notes' => 'Cannabinoids confirmed via GC-MS and Duquenois-Levine spot test.',
            'exhibit_id' => $exhibit->id,
            'drug_type' => 'Compressed Cannabis Blocks',
            'exhibit_owner_name' => 'All Suspects',
            'exhibit_is_associate' => false,
            'lab_exam_reference' => 'NLA-FM-T8855-F',
            'lab_exhibit_no' => 'B1',
            'lab_exhibit_number' => 'B1',
            'laboratory_number' => 'LAB-2026-8855',
            'purpose_of_examination' => 'Botanical and chemical identification of compressed plant matter',
            'preliminary_exam_procedure' => 'Duquenois-Levine color test and botanical microscopy',
            'screening_method' => 'TLC, GC-MS Agilent 8890',
            'lab_unit' => 'Chemistry & Narcotics Unit',
            'presumptive_result' => 'YES | Presence of THC | Purity: 18%',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'Four compressed herbal slabs',
            'sample_weight_units' => '4 Slabs',
            'sample_weight_gross' => '4.20 kg',
            'sample_weight_taken' => '0.10 kg',
            'sample_weight_reserved' => '4.10 kg',
            'confirmatory_substance_identification' => 'Delta-9-Tetrahydrocannabinol (THC)',
            'detected_substances' => [
                ['substance' => 'Delta-9-Tetrahydrocannabinol (THC)', 'percentage' => '18.4'],
            ],
            'confirmatory_test_methods' => 'GSA-SM-T012-THC',
            'confirmatory_specifications' => 'Matched against certified THC standard',
            'confirmatory_remark' => 'Narcotic plant material confirmed',
            'disposition_of_exhibit' => '4.10 kg reserved returned to NACOC Exhibit Vault',
            'lab_date_received' => '2026-08-01',
            'lab_date_started' => '2026-08-02',
            'lab_date_completed' => '2026-08-03',
            'date_received' => '2026-08-01',
            'date_started' => '2026-08-02',
            'date_completed' => '2026-08-03',
        ]);

        $resp->assertOk()->assertJsonPath('success', true);

        // Must save to LaboratoryExaminationResult (not AssociateLabExamination) and generate Preliminary Report
        $this->assertSame(1, \App\Models\LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)->count());
        $this->assertSame(0, \App\Models\AssociateLabExamination::where('case_id', $case->id)->count());

        $prelimReports = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id);
        $this->assertGreaterThanOrEqual(1, $prelimReports->count(), 'Expected Preliminary Exhibit Analysis Report to be generated via update-unit-progress for exhibit without suspect');

        foreach ([
            "/Lab_Analysis/cases/{$case->case_number}/review",
            "/Forensic_Head/cases/{$case->case_number}/review",
        ] as $reviewUrl) {
            $actor = str_starts_with($reviewUrl, '/Forensic_Head') ? $forensicHead : $labAnalyst;
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();

            $this->assertStringContainsString("/Lab_Analysis/preliminary-report-pdf/{$case->case_number}", $html, "Missing Preliminary Report PDF link on {$reviewUrl}");
            $this->assertSame(1, substr_count($html, 'data-lab-card="true"'), "Expected exactly 1 lab result card on {$reviewUrl}");

            foreach ([
                'NLA-FM-T8855-F',
                'LAB-2026-8855',
                '2026-08-01',
                '2026-08-02',
                '2026-08-03',
                'Botanical and chemical identification of compressed plant matter',
                'Duquenois-Levine color test and botanical microscopy',
                'TLC, GC-MS Agilent 8890',
                '4.10 kg reserved returned to NACOC Exhibit Vault',
                'GSA-SM-T012-THC',
                'Matched against certified THC standard',
                'Narcotic plant material confirmed',
                'Delta-9-Tetrahydrocannabinol (THC)',
                '(18.4%)',
                '0.10 kg',
                '4.10 kg',
            ] as $expectedField) {
                $this->assertStringContainsString($expectedField, $html, "Missing '{$expectedField}' on {$reviewUrl}");
            }

            foreach ([
                'Ghanaian',
                'No Children',
                'NACOC Custody &amp; Holding Facility',
                'NACOC Custody & Holding Facility',
                'Victor Kofi',
                'Agbogbloshie',
                'Thursday, 18 June 2026',
                'NACOC Headquarters',
                'NACOC Forensic Directorate Vault',
                'Directorate Authorized Forensic Case File',
            ] as $forbiddenMock) {
                $this->assertStringNotContainsString($forbiddenMock, $html, "Unexpected mock fallback '{$forbiddenMock}' found on {$reviewUrl}");
            }
        }
    }

    #[Test]
    public function presumptive_result_presence_and_percentage_are_stored_separately_and_rendered_in_their_columns(): void
    {
        $labAnalyst = $this->makeApprovedOfficer('Laboratory Analysis');
        $forensicHead = User::factory()->create([
            'fullname' => 'Director Forensic Head',
            'unit' => 'Forensic Science',
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => false,
        ]);

        // Case A: legacy combined string format ("YES: Test #1: Presence of JGHFJGF (89%)") posted to POST /cases
        $postRes = $this->actingAs($labAnalyst)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: JGHFJGF (Awaiting Suspect)',
            'unit_name' => 'Laboratory Analysis',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'JGHFJGF Sample',
            'seizure_name_drug' => 'JGHFJGF Sample',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Parcel',
            'seizure_weight' => '1.00',
            'seizure_unit' => 'kg',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'JGHFJGF Sample',
                'seizure_name_drug' => 'JGHFJGF Sample',
                'commodity_name' => 'JGHFJGF Sample',
                'seizure_quantity' => '1',
                'seizure_packaging' => 'Parcel',
                'seizure_weight' => '1.00',
                'seizure_unit' => 'kg',
            ]],
            'lab_workflow_mode' => 'nlr',
            'section5_completed' => '1',
            'lab_exam_reference' => 'NLA-FM-PRES-01',
            'laboratory_number' => 'LAB-2026-PRES-01',
            'lab_exhibit_no' => 'P1',
            'lab_analyst' => 'Laboratory Analysis Test Officer',
            'presumptive_result' => 'YES: Test #1: Presence of JGHFJGF (89%)',
            'confirmatory_test_result' => 'YES',
        ]);

        $postRes->assertOk()->assertJsonPath('success', true);
        $caseA = ForensicCase::where('case_number', $postRes->json('case.case_number'))->first();
        $this->assertNotNull($caseA);

        $labRowA = \App\Models\LaboratoryExaminationResult::where('single_suspect_case_id', $caseA->id)->first();
        $this->assertNotNull($labRowA);
        $this->assertSame('YES', $labRowA->presumptive_result);
        $this->assertSame('JGHFJGF', $labRowA->presumptive_presence);
        $this->assertSame('89%', $labRowA->presumptive_percentage);

        foreach ([
            [$labAnalyst, "/Lab_Analysis/cases/{$caseA->case_number}/review"],
            [$labAnalyst, "/cases/{$caseA->case_number}/review?from=Laboratory%20Analysis%20%28LAU%29"],
            [$forensicHead, "/Forensic_Head/cases/{$caseA->case_number}/review"],
        ] as [$actor, $reviewUrl]) {
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();
            $this->assertStringNotContainsString('YES: Test #1: Presence of JGHFJGF (89%)', $html, "Raw combined presumptive string leaked into {$reviewUrl}");
            $this->assertMatchesRegularExpression('/PRESUMPTIVE REACTION:.*?<td[^>]*>\s*YES\s*<\/td>\s*<td[^>]*>\s*JGHFJGF\s*<\/td>\s*<td[^>]*>\s*89%\s*<\/td>/si', $html, "Expected normalized YES / JGHFJGF / 89% columns on {$reviewUrl}");
        }

        // Case B: explicit multi-row presumptive_presence and presumptive_percentage saved via update-unit-progress
        $caseB = ForensicCase::create([
            'case_number' => 'NCC-2026-PRES-02',
            'title' => 'Seized Exhibit: Multi Presumptive Test',
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'SINGLE',
            'no_suspect' => 1,
            'suspect_name' => 'Awaiting Suspect',
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Adulterated Cocaine',
            'seizure_name_drug' => 'Adulterated Cocaine',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Wrap',
            'seizure_weight' => '0.50',
            'seizure_unit' => 'kg',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'All Suspects',
                'seizure_name' => 'Adulterated Cocaine',
                'seizure_name_drug' => 'Adulterated Cocaine',
                'commodity_name' => 'Adulterated Cocaine',
                'seizure_quantity' => '1',
                'seizure_packaging' => 'Wrap',
                'seizure_weight' => '0.50',
                'seizure_unit' => 'kg',
            ]],
        ]);
        $exhibitB = Exhibit::where('single_suspect_case_id', $caseB->id)->firstOrFail();

        $updateRes = $this->actingAs($labAnalyst)->postJson("/cases/{$caseB->case_number}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'Completed',
            'officer' => 'Laboratory Analysis Test Officer',
            'exhibit_id' => $exhibitB->id,
            'drug_type' => 'Adulterated Cocaine',
            'exhibit_owner_name' => 'All Suspects',
            'exhibit_is_associate' => false,
            'lab_exam_reference' => 'NLA-FM-PRES-02',
            'laboratory_number' => 'LAB-2026-PRES-02',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cocaine; Levamisole',
            'presumptive_percentage' => '82; 12',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
        ]);
        $updateRes->assertOk()->assertJsonPath('success', true);

        $labRowB = \App\Models\LaboratoryExaminationResult::where('single_suspect_case_id', $caseB->id)->first();
        $this->assertNotNull($labRowB);
        $this->assertSame('YES', $labRowB->presumptive_result);
        $this->assertSame('Cocaine; Levamisole', $labRowB->presumptive_presence);
        $this->assertSame('82%; 12%', $labRowB->presumptive_percentage);

        foreach ([
            [$labAnalyst, "/Lab_Analysis/cases/{$caseB->case_number}/review"],
            [$labAnalyst, "/cases/{$caseB->case_number}/review?from=Laboratory%20Analysis%20%28LAU%29"],
            [$forensicHead, "/Forensic_Head/cases/{$caseB->case_number}/review"],
        ] as [$actor, $reviewUrl]) {
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/<td[^>]*>\s*Cocaine\s*<\/td>\s*<td[^>]*>\s*82%\s*<\/td>/si', $html, "Missing Cocaine / 82% row on {$reviewUrl}");
            $this->assertMatchesRegularExpression('/<td[^>]*>\s*Levamisole\s*<\/td>\s*<td[^>]*>\s*12%\s*<\/td>/si', $html, "Missing Levamisole / 12% row on {$reviewUrl}");
        }
    }

    #[Test]
    public function lab_analysis_create_renders_single_source_of_exhibits_and_source_requesting_unit_inputs(): void
    {
        $labAnalyst = $this->makeApprovedOfficer('Laboratory Analysis');
        $html = $this->actingAs($labAnalyst)->get('/Lab_Analysis/create')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="newSeizureSourceOfExhibits"'), 'Expected strictly 1 Source of Exhibits input in Seized Item section');
        $this->assertSame(1, substr_count($html, 'id="newSeizureInvestigatingOfficer"'), 'Expected strictly 1 Investigating Officer input in Seized Item section');
        $this->assertSame(1, substr_count($html, 'id="newSeizureSourceRequestingUnit"'), 'Expected 1 Source/Requesting Unit input in Seized Item section');
        $this->assertStringContainsString('Source/Requesting Unit', $html);
        $this->assertStringContainsString('Investigating Officer', $html);
        $this->assertStringContainsString('source_of_exhibits: seizureSourceOfExhibits', $html);
        $this->assertStringContainsString('investigating_officer: investigatingOfficer', $html);
        $this->assertStringContainsString('source_requesting_unit: seizureSourceRequestingUnit', $html);
        $this->assertStringContainsString("setVal('#newSeizureInvestigatingOfficer'", $html);
        $this->assertStringContainsString("setVal('#newSeizureSourceRequestingUnit'", $html);
    }

    #[Test]
    public function all_unit_create_and_add_exhibit_views_render_single_source_investigating_officer_and_requesting_unit_inputs(): void
    {
        $routes = [
            ['Laboratory Analysis', '/Lab_Analysis/create'],
            ['Digital Forensics', '/Digital_Forensics/create'],
            ['Evidence Management', '/Evidence_Management/create'],
            ['Crime Scene Support', '/Crime_Scene_Support/create'],
            ['Crime Scene Support', '/cases/create'],
        ];

        $unitRoutesWithSourceOfExhibits = [
            '/Lab_Analysis/create',
            '/cases/create',
        ];

        foreach ($routes as [$unit, $url]) {
            $officer = $this->makeApprovedOfficer($unit);
            $html = $this->actingAs($officer)->get($url)->assertOk()->getContent();

            $expectedSourceCount = in_array($url, $unitRoutesWithSourceOfExhibits, true) ? 1 : 0;
            $expectedCustodyOfficerCount = $url === '/Digital_Forensics/create' ? 0 : 1;
            $this->assertSame($expectedSourceCount, substr_count($html, 'id="newSeizureSourceOfExhibits"'), "Unexpected #newSeizureSourceOfExhibits count on {$url}");
            $this->assertSame($expectedCustodyOfficerCount, substr_count($html, 'id="newSeizureInvestigatingOfficer"'), "Expected strictly {$expectedCustodyOfficerCount} #newSeizureInvestigatingOfficer on {$url}");
            $this->assertSame($expectedCustodyOfficerCount, substr_count($html, 'id="newSeizureSourceRequestingUnit"'), "Expected strictly {$expectedCustodyOfficerCount} #newSeizureSourceRequestingUnit on {$url}");
            $this->assertStringContainsString('investigating_officer:', $html, "Missing investigating_officer JS serialization on {$url}");
            $this->assertStringContainsString('source_requesting_unit:', $html, "Missing source_requesting_unit JS serialization on {$url}");
        }

        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $storeRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'offense_category' => 'Possession',
            'no_suspect' => 1,
            'source_of_exhibits' => 'Tema Port Shed 4',
            'investigating_officer' => 'Insp. Kwame Boateng',
            'source_requesting_unit' => 'Enforcement and Operations Unit',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'seizure_name' => 'Compressed Cannabis',
                'seizure_quantity' => 3,
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => 150,
                'seizure_unit' => 'g',
                'seizure_location' => 'Tema Port',
                'seizure_officer' => 'Officer Test',
                'source_of_exhibits' => 'Tema Port Shed 4',
                'investigating_officer' => 'Insp. Kwame Boateng',
                'source_requesting_unit' => 'Enforcement and Operations Unit',
                'seizure_reason' => 'Intelligence led search',
            ]],
        ]);

        $storeRes->assertOk()->assertJsonPath('success', true);
        $case = ForensicCase::allCases()->last();
        $this->assertNotNull($case);
        $this->assertSame('Tema Port Shed 4', $case->source_of_exhibits);
        $this->assertSame('Insp. Kwame Boateng', $case->investigating_officer);
        $this->assertSame('Enforcement and Operations Unit', $case->source_requesting_unit);
        $this->assertSame('N/A', $case->generateBriefCaseDescription());

        $reviewHtml = $this->actingAs($labOfficer)
            ->get("/Lab_Analysis/cases/{$case->case_number}/review")
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('logged seized exhibits', $reviewHtml);
        $this->assertStringNotContainsString('without an assigned suspect', $reviewHtml);
    }

    #[Test]
    public function arrest_date_and_time_displays_na_when_not_entered_and_saved(): void
    {
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $forensicHead = $this->makeApprovedOfficer('Forensic Directorate', true);

        $storeRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'seizure_name' => 'Methamphetamine',
                'seizure_quantity' => 1,
                'seizure_packaging' => 'Packet',
                'seizure_weight' => 50,
                'seizure_unit' => 'g',
                'seizure_date' => '2026-10-05',
                'seizure_time' => '14:25',
                'seizure_location' => 'Tema Harbour',
            ]],
        ]);

        $storeRes->assertOk()->assertJsonPath('success', true);
        $caseNumber = $storeRes->json('case.case_number');

        foreach ([
            [$labOfficer, "/Lab_Analysis/cases/{$caseNumber}/review"],
            [$labOfficer, "/cases/{$caseNumber}/review"],
            [$forensicHead, "/Forensic_Head/cases/{$caseNumber}/review"],
        ] as [$actor, $reviewUrl]) {
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();
            $this->assertMatchesRegularExpression(
                '/Arrest\s*Date\s*&amp;\s*Time\s*<\/div>.*?<div[^>]*>\s*N\/A\s*<\/div>/si',
                $html,
                "Expected Arrest Date & Time to display N/A on {$reviewUrl}"
            );
        }

        // Verify create forms do not copy seizure_date into arrest_date when noSuspectMode is active
        foreach ([
            [$labOfficer, '/Lab_Analysis/create'],
            [$this->makeApprovedOfficer('Digital Forensics'), '/Digital_Forensics/create'],
            [$this->makeApprovedOfficer('Evidence Management'), '/Evidence_Management/create'],
        ] as [$actor, $createUrl]) {
            $createHtml = $this->actingAs($actor)->get($createUrl)->assertOk()->getContent();
            $this->assertStringContainsString('arrest_date: noSuspectMode ? null :', $createHtml, "Expected noSuspectMode arrest_date null on {$createUrl}");
            $this->assertStringContainsString('arrest_time: noSuspectMode ? null :', $createHtml, "Expected noSuspectMode arrest_time null on {$createUrl}");
        }
    }

    #[Test]
    public function completing_drug_exhibit_without_suspect_persists_exam_info_attaches_preliminary_pdf_and_locks_reselection(): void
    {
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');

        // 1. EMU logs a Drug Exhibit Without Suspect(s) (unassigned in Vault)
        $emuRes = $this->actingAs($emuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'source_of_exhibits' => 'Kotoka Airport Cargo Terminal',
            'investigating_officer' => 'Insp. Ama Mensah',
            'source_requesting_unit' => 'Aviation Security Unit',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Compressed Cannabis Blocks',
                'seizure_name' => 'Compressed Cannabis Blocks',
                'seizure_name_drug' => 'Compressed Cannabis Blocks',
                'seizure_quantity' => 5,
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => 4.5,
                'seizure_unit' => 'kg',
                'seizure_location' => 'KIA Cargo Shed',
                'seizure_officer' => 'Officer Kofi',
                'source_of_exhibits' => 'Kotoka Airport Cargo Terminal',
                'investigating_officer' => 'Insp. Ama Mensah',
                'source_requesting_unit' => 'Aviation Security Unit',
            ]],
        ]);
        $emuRes->assertOk()->assertJsonPath('success', true);
        $emuCaseNumber = $emuRes->json('case.case_number');
        $emuCase = ForensicCase::where('case_number', $emuCaseNumber)->first();
        $this->assertNotNull($emuCase);
        $emuExhibit = Exhibit::where('single_suspect_case_id', $emuCase->id)->firstOrFail();

        // Viewing an unexamined EMU exhibit in show_review must NOT prematurely lock it in the selector
        $this->actingAs($labOfficer)->get("/Lab_Analysis/cases/{$emuCaseNumber}/review")->assertOk();
        $apiBefore = $this->actingAs($labOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Laboratory Analysis (LAU)'))
            ->assertOk()
            ->json('exhibits');
        $matchedBefore = collect($apiBefore)->firstWhere('id', $emuExhibit->id);
        $this->assertNotNull($matchedBefore);
        $this->assertFalse($matchedBefore['has_preliminary_report']);
        $this->assertTrue($matchedBefore['is_selectable']);

        // 2. Lab Officer completes a Drug Exhibit Without Suspect(s) via /cases linking that exhibit
        //    with full Examination Information (even if section5_completed was submitted as '0' without mode)
        $labRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'linked_exhibit_ids' => [$emuExhibit->id],
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Compressed Cannabis Blocks',
            'seizure_name_drug' => 'Compressed Cannabis Blocks',
            'source_of_exhibits' => 'Kotoka Airport Cargo Terminal',
            'investigating_officer' => 'Insp. Ama Mensah',
            'source_requesting_unit' => 'Aviation Security Unit',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Drugs',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => 'Compressed Cannabis Blocks',
                'seizure_name_drug' => 'Compressed Cannabis Blocks',
                'commodity_name' => 'Compressed Cannabis Blocks',
                'seizure_quantity' => 5,
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => 4.5,
                'seizure_unit' => 'kg',
            ]],
            'section5_completed' => '0',
            'laboratory_number' => 'LAB-2026-NS-778',
            'lab_exam_reference' => 'NLA-2026-NS-778',
            'lab_exhibit_no' => 'LAB-EX-778',
            'purpose_of_examination' => 'To determine the identity and purity of seized plant material.',
            'preliminary_exam_procedure' => 'Duquenois-Levine color test performed on representative sample.',
            'screening_method' => ['Duquenois-Levine', 'TLC'],
            'lab_date_received' => '2026-11-11',
            'lab_date_started' => '2026-11-11',
            'lab_date_completed' => '2026-11-12',
            'presumptive_result' => 'YES: Test #1: Presence of THC (74%)',
            'presumptive_presence' => 'THC',
            'presumptive_percentage' => '74%',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'Compressed Cannabis Blocks',
            'sample_weight_units' => '5 Parcels',
            'sample_weight_gross' => '4.5 kg',
            'sample_weight_taken' => '15 g',
            'sample_weight_reserved' => '4.485 kg',
            'confirmatory_substance_identification' => 'Delta-9-Tetrahydrocannabinol',
            'detected_substances' => [
                ['substance' => 'Delta-9-THC', 'percentage' => '74%'],
            ],
            'confirmatory_test_methods' => 'GC-MS',
            'confirmatory_specifications' => 'Agilent 7890B GC / 5977B MSD',
            'confirmatory_remark' => 'Confirmed cannabis resin & plant material.',
            'disposition_of_exhibit' => 'Returned to Evidence Vault for retention.',
        ]);

        $labRes->assertOk()->assertJsonPath('success', true);
        $createdCaseNumber = $labRes->json('case.case_number');
        $createdCase = ForensicCase::where('case_number', $createdCaseNumber)->first();
        $this->assertNotNull($createdCase);

        // 3. Assert ALL Examination Information fields stored in laboratory_examination_results
        $labExam = LaboratoryExaminationResult::where('single_suspect_case_id', $createdCase->id)->first();
        $this->assertNotNull($labExam, 'Examination Information must be saved in laboratory_examination_results.');
        $this->assertTrue((bool) $labExam->has_saved);
        $this->assertSame($emuExhibit->id, (int) $labExam->exhibit_id);
        $this->assertSame('LAB-2026-NS-778', $labExam->laboratory_number);
        $this->assertSame('To determine the identity and purity of seized plant material.', $labExam->purpose_of_examination);
        $this->assertSame('Duquenois-Levine color test performed on representative sample.', $labExam->preliminary_exam_procedure);
        $this->assertStringContainsString('Duquenois-Levine', (string) $labExam->screening_method);
        $this->assertStringContainsString('TLC', (string) $labExam->screening_method);
        $this->assertSame('YES', $labExam->presumptive_result);
        $this->assertSame('THC', $labExam->presumptive_presence);
        $this->assertSame('74%', $labExam->presumptive_percentage);
        $this->assertSame('YES', $labExam->confirmatory_result);
        $this->assertSame('15 g', $labExam->sample_weight_taken);
        $this->assertSame('4.485 kg', $labExam->sample_weight_reserved);
        $this->assertSame('Delta-9-Tetrahydrocannabinol', $labExam->substance_identification);
        $this->assertSame('GC-MS', $labExam->test_methods);
        $this->assertSame('Agilent 7890B GC / 5977B MSD', $labExam->specifications);
        $this->assertSame('Confirmed cannabis resin & plant material.', $labExam->remark);
        $this->assertSame('Returned to Evidence Vault for retention.', $labExam->disposition_of_exhibit);
        $this->assertSame('LAB-EX-778', $emuExhibit->fresh()->lab_exhibit_no);

        // No duplicate exhibit row created
        $this->assertSame(1, Exhibit::where('single_suspect_case_id', $createdCase->id)->count());

        // Preliminary Exhibit Analysis Report PDF record must exist
        $prelimReport = SuspectPreliminaryReport::forCase($createdCase->case_number, $createdCase->id)->first();
        $this->assertNotNull($prelimReport);

        // 4. Verify Drug Exhibits Without Suspect(s) API & Blade view attach the PDF and lock re-selection
        $apiAfter = $this->actingAs($labOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Laboratory Analysis (LAU)'))
            ->assertOk()
            ->json('exhibits');
        $matchedAfter = collect($apiAfter)->firstWhere('id', $emuExhibit->id);
        $this->assertNotNull($matchedAfter);
        $this->assertTrue($matchedAfter['has_preliminary_report']);
        $this->assertFalse($matchedAfter['is_selectable']);
        $this->assertNotEmpty($matchedAfter['preliminary_report']['url'] ?? null);
        $this->assertStringContainsString('/Lab_Analysis/preliminary-report-pdf/' . $createdCase->case_number, $matchedAfter['preliminary_report']['url']);

        $alertsAfter = $this->actingAs($labOfficer)
            ->getJson('/api/unit-exhibit-alerts?domain=lab')
            ->assertOk()
            ->json('alerts');
        $this->assertNull(collect($alertsAfter)->firstWhere('exhibit_id', $emuExhibit->id), 'Locked exhibit must not appear in unassigned alerts.');

        $createHtml = $this->actingAs($labOfficer)->get('/Lab_Analysis/create')->assertOk()->getContent();
        $this->assertStringContainsString('Preliminary Exhibit Analysis Report (PDF)', $createHtml);
        $this->assertStringContainsString('/Lab_Analysis/preliminary-report-pdf/' . $createdCase->case_number, $createHtml);
        $this->assertStringContainsString('Locked • PDF Attached', $createHtml);
        $this->assertMatchesRegularExpression('/<option\s+value="' . $emuExhibit->id . '"\s+disabled\b/si', $createHtml);
        $this->assertMatchesRegularExpression('/data-unassigned-card-id="' . $emuExhibit->id . '"\s+data-locked="1"/si', $createHtml);
        $this->assertStringContainsString('evaluateLabExaminationVisibility', $createHtml);

        // 5. Re-selecting and submitting the same exhibit on backend must be rejected (HTTP 422)
        $reselectRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'linked_exhibit_ids' => [$emuExhibit->id],
            'seizure_name' => 'Compressed Cannabis Blocks',
        ]);
        $reselectRes->assertStatus(422)->assertJsonPath('success', false);
    }

    #[Test]
    public function completing_drug_exhibit_without_suspect_on_show_review_saves_exam_info_and_locks_exhibit(): void
    {
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');

        $emuRes = $this->actingAs($emuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'source_of_exhibits' => 'Tema Port Terminal 3',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Heroin Brick',
                'seizure_name' => 'Heroin Brick',
                'seizure_name_drug' => 'Heroin Brick',
                'seizure_quantity' => 2,
                'seizure_packaging' => 'Blocks',
                'seizure_weight' => 2.0,
                'seizure_unit' => 'kg',
            ]],
        ]);
        $emuRes->assertOk();
        $caseNumber = $emuRes->json('case.case_number');
        $case = ForensicCase::where('case_number', $caseNumber)->first();
        $this->assertNotNull($case);
        $exhibit = Exhibit::where('single_suspect_case_id', $case->id)->firstOrFail();

        $updRes = $this->actingAs($labOfficer)->postJson("/cases/{$caseNumber}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'officer' => 'Lab Reviewer Officer',
            'exhibit_id' => $exhibit->id,
            'drug_type' => 'Heroin Brick',
            'exhibit_owner_name' => 'Unassigned (No Suspect)',
            'lab_exam_reference' => 'NLA-REV-2026-09',
            'lab_exhibit_no' => 'LAB-REV-09',
            'laboratory_number' => 'LAB-2026-REV-09',
            'purpose_of_examination' => 'Confirmatory identification of opiate brick.',
            'preliminary_exam_procedure' => 'Marquis reagent screening.',
            'screening_method' => 'Marquis, GC-MS',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Diacetylmorphine',
            'presumptive_percentage' => '68%',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'Heroin Brick',
            'sample_weight_units' => '2 Blocks',
            'sample_weight_gross' => '2 kg',
            'sample_weight_taken' => '10 g',
            'sample_weight_reserved' => '1.99 kg',
            'confirmatory_substance_identification' => 'Diacetylmorphine',
            'detected_substances' => [['substance' => 'Diacetylmorphine', 'percentage' => '68%']],
            'confirmatory_test_methods' => 'GC-MS',
            'confirmatory_specifications' => 'ISO-17025 Validated Method',
            'confirmatory_remark' => 'Positive for Heroin.',
            'disposition_of_exhibit' => 'Sealed in Vault.',
            'date_received' => '2026-11-11',
            'date_started' => '2026-11-11',
            'date_completed' => '2026-11-11',
        ]);
        $updRes->assertOk()->assertJsonPath('success', true);

        $savedLab = LaboratoryExaminationResult::where('exhibit_id', $exhibit->id)->first();
        $this->assertNotNull($savedLab);
        $this->assertTrue((bool) $savedLab->has_saved);
        $this->assertSame('Confirmatory identification of opiate brick.', $savedLab->purpose_of_examination);
        $this->assertSame('Marquis reagent screening.', $savedLab->preliminary_exam_procedure);
        $this->assertSame('Marquis, GC-MS', $savedLab->screening_method);
        $this->assertSame('Sealed in Vault.', $savedLab->disposition_of_exhibit);
        $this->assertSame('LAB-2026-REV-09', $savedLab->laboratory_number);

        $apiAfter = $this->actingAs($labOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Laboratory Analysis (LAU)'))
            ->assertOk()
            ->json('exhibits');
        $matched = collect($apiAfter)->firstWhere('id', $exhibit->id);
        $this->assertNotNull($matched);
        $this->assertTrue($matched['has_preliminary_report']);
        $this->assertFalse($matched['is_selectable']);
        $this->assertStringContainsString('/Lab_Analysis/preliminary-report-pdf/' . $caseNumber, $matched['preliminary_report']['url']);
    }

    #[Test]
    public function locked_exhibit_without_suspect_appears_in_all_units_and_linking_suspect_reuses_case_ref_and_removes_from_create(): void
    {
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $dfuOfficer = $this->makeApprovedOfficer('Digital Forensics');
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $cssOfficer = $this->makeApprovedOfficer('Crime Scene Support');
        $fdOfficer = $this->makeApprovedOfficer('Forensic Directorate');

        // 1. Lab Officer logs a Drug Exhibit Without Suspect and completes Section 5 (generating Preliminary Report PDF & lock)
        $createRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Lab Analysis',
            'source_of_exhibits' => 'Tema Port Inspection Bay 2',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Locked Cocaine Block',
                'seizure_name' => 'Locked Cocaine Block',
                'seizure_name_drug' => 'Locked Cocaine Block',
                'seizure_quantity' => 5,
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => 5.0,
                'seizure_unit' => 'kg',
                'seizure_location' => 'Tema Port',
                'seizure_date' => '2026-11-11',
            ]],
            'section5_completed' => true,
            'lab_workflow_mode' => 'nlr',
            'purpose_of_examination' => 'To identify narcotic substance.',
            'preliminary_exam_procedure' => 'Cobalt thiocyanate spot test.',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cocaine',
            'presumptive_percentage' => '82%',
        ]);

        $createRes->assertOk()->assertJsonPath('success', true);
        $existingCaseNumber = $createRes->json('case.case_number');
        $existingCase = SingleSuspectCase::where('case_number', $existingCaseNumber)->firstOrFail();
        $exhibit = Exhibit::where('single_suspect_case_id', $existingCase->id)->firstOrFail();
        $this->assertTrue($exhibit->isLockedByPreliminaryReport());

        $initialSingleCaseCount = SingleSuspectCase::count();

        // 2. Verify this locked exhibit appears in EVERY unit's create.blade & /api/exhibits/unassigned?unit=...
        //    alongside the #linkSuspectToLockedExhibitCheckbox control
        $unitCreateEndpoints = [
            [$labOfficer, '/Lab_Analysis/create', 'Laboratory Analysis (LAU)'],
            [$dfuOfficer, '/Digital_Forensics/create', 'Digital & Physical Forensics (DPFU)'],
            [$emuOfficer, '/Evidence_Management/create', 'Evidence Management Unit (EMU)'],
            [$cssOfficer, '/Crime_Scene_Support/create', 'Crime Scene Support Unit (CSSU)'],
            [$fdOfficer, '/cases/create', 'Forensic Directorate'],
        ];

        foreach ($unitCreateEndpoints as [$actor, $createUrl, $unitQuery]) {
            $html = $this->actingAs($actor)->get($createUrl)->assertOk()->getContent();
            $this->assertStringContainsString('id="linkSuspectToLockedExhibitCheckbox"', $html, "Missing checkbox on {$createUrl}");
            $this->assertStringContainsString('Locked Cocaine Block', $html, "Locked exhibit missing on {$createUrl}");
            $this->assertStringContainsString($existingCaseNumber, $html, "Existing case ref missing on {$createUrl}");

            $apiExhibits = $this->actingAs($actor)
                ->getJson('/api/exhibits/unassigned?unit=' . urlencode($unitQuery))
                ->assertOk()
                ->json('exhibits');
            $found = collect($apiExhibits)->firstWhere('id', $exhibit->id);
            $this->assertNotNull($found, "Locked exhibit missing from API for {$unitQuery}");
            $this->assertTrue($found['has_preliminary_report']);
            $this->assertSame($existingCaseNumber, $found['case_number']);
        }

        // 3. Attempting to link WITHOUT checking link_suspect_to_locked_exhibit is still blocked (422)
        $blockedRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'title' => 'Republic vs Kwaku Mensah',
            'suspect_name' => 'Kwaku Mensah',
            'unit_name' => 'Digital & Physical Forensics',
            'linked_exhibit_ids' => [$exhibit->id],
        ]);
        $blockedRes->assertStatus(422);

        // 4. Checking link_suspect_to_locked_exhibit and submitting suspect biodata ONLY updates biodata,
        //    reuses the existing case_number, maintains existing Exhibit and LaboratoryExaminationResult records
        //    untouched (even if Section 4/5 payload is present), and removes the exhibit from create.blade
        $initialLabResult = LaboratoryExaminationResult::where('single_suspect_case_id', $existingCase->id)->firstOrFail();
        $initialExhibitCount = Exhibit::count();
        $initialLabCount = LaboratoryExaminationResult::count();

        $linkRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'link_suspect_to_locked_exhibit' => 1,
            'case_number' => $existingCaseNumber,
            'unit_name' => 'Digital & Physical Forensics',
            'linked_exhibit_ids' => [$exhibit->id],
            'title' => 'Republic vs Kwaku Mensah',
            'suspect_name' => 'Kwaku Mensah',
            'suspect_alias' => 'Commander',
            'ghana_card' => 'GHA-777888999-1',
            'gender' => 'Male',
            'age' => 38,
            'nationality' => 'Ghanaian',
            'phone' => '+233240001122',
            'address' => 'Community 11, Tema',
            'holding_facility' => 'NACOC Detention Facility',
            'raid_location' => 'Tema Harbour Gate 4',
            'offense_description' => 'Unlawful importation of cocaine',
            'seizures' => [[
                'id' => $exhibit->id,
                'linked_exhibit_id' => $exhibit->id,
                'property_id' => $exhibit->property_id,
                'exhibit_category' => 'Drugs',
                'commodity_name' => 'Tampered Commodity Name',
                'seizure_quantity' => 99,
                'seizure_packaging' => 'Crates',
                'seizure_weight' => 5.2,
                'seizure_unit' => 'kg',
                'source_of_exhibits' => 'Tampered Source',
                'exhibit_remark' => 'Updated weight after secondary verification',
            ]],
            'section5_completed' => true,
            'lab_workflow_mode' => 'nlr',
            'purpose_of_examination' => 'Tampered purpose.',
            'presumptive_presence' => 'Heroin',
        ]);

        $linkRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reused_existing_case', true)
            ->assertJsonPath('case.case_number', $existingCaseNumber);

        // No new SingleSuspectCase, Exhibit, or LaboratoryExaminationResult created; existing case biodata updated in place
        $this->assertSame($initialSingleCaseCount, SingleSuspectCase::count());
        $this->assertSame($initialExhibitCount, Exhibit::count());
        $this->assertSame($initialLabCount, LaboratoryExaminationResult::count());
        $existingCase->refresh();
        $exhibit->refresh();

        $this->assertSame($existingCaseNumber, $existingCase->case_number);
        $this->assertSame('Kwaku Mensah', $existingCase->suspect_name);
        $this->assertSame('Republic vs Kwaku Mensah', $existingCase->title);
        $this->assertSame('GHA-777888999-1', $existingCase->ghana_card);
        $this->assertSame('Community 11, Tema', $existingCase->address);
        $this->assertSame('Kwaku Mensah', $exhibit->associated_person);

        // Existing Exhibit fields MUST remain maintained untouched
        $this->assertSame('Locked Cocaine Block', $exhibit->commodity_name);
        $this->assertSame('5', (string) $exhibit->seizure_quantity);
        $this->assertSame('Parcels', $exhibit->seizure_packaging);
        $this->assertEquals(5.0, (float) $exhibit->seizure_weight);
        $this->assertSame('Tema Port Inspection Bay 2', $exhibit->source_of_exhibits);
        $this->assertNull($exhibit->exhibit_remark);

        // Existing LaboratoryExaminationResult fields MUST remain maintained untouched
        $labAfter = LaboratoryExaminationResult::where('single_suspect_case_id', $existingCase->id)->firstOrFail();
        $this->assertSame($initialLabResult->id, $labAfter->id);
        $this->assertSame($initialLabResult->exam_reference, $labAfter->exam_reference);
        $this->assertSame('To identify narcotic substance.', $labAfter->purpose_of_examination);
        $this->assertSame('Cobalt thiocyanate spot test.', $labAfter->preliminary_exam_procedure);
        $this->assertSame('YES', $labAfter->presumptive_result);
        $this->assertSame('Cocaine', $labAfter->presumptive_presence);
        $this->assertSame('82%', $labAfter->presumptive_percentage);
        $this->assertTrue((bool) $labAfter->has_saved);
        $this->assertTrue(SuspectPreliminaryReport::where('single_suspect_case_id', $existingCase->id)->exists());

        // 5. Once linked, the exhibit NO LONGER appears in ANY unit's create.blade or /api/exhibits/unassigned
        foreach ($unitCreateEndpoints as [$actor, $createUrl, $unitQuery]) {
            $htmlAfter = $this->actingAs($actor)->get($createUrl)->assertOk()->getContent();
            $this->assertStringNotContainsString('Locked Cocaine Block', $htmlAfter, "Linked exhibit still rendered on {$createUrl}");

            $apiAfter = $this->actingAs($actor)
                ->getJson('/api/exhibits/unassigned?unit=' . urlencode($unitQuery))
                ->assertOk()
                ->json('exhibits');
            $this->assertNull(collect($apiAfter)->firstWhere('id', $exhibit->id), "Linked exhibit still in API for {$unitQuery}");
        }
    }

    #[Test]
    public function linking_multiple_suspects_to_locked_exhibit_reuses_case_number_and_migrates_records(): void
    {
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');

        $createRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Lab Analysis',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Multi-Suspect Tramadol',
                'seizure_quantity' => 10,
                'seizure_packaging' => 'Boxes',
                'seizure_weight' => 12.0,
                'seizure_unit' => 'kg',
            ]],
            'section5_completed' => true,
            'lab_workflow_mode' => 'nlr',
            'purpose_of_examination' => 'Identify synthetic opioid.',
            'presumptive_result' => 'YES',
        ]);

        $createRes->assertOk();
        $existingCaseNumber = $createRes->json('case.case_number');
        $existingCase = SingleSuspectCase::where('case_number', $existingCaseNumber)->firstOrFail();
        $exhibit = Exhibit::where('single_suspect_case_id', $existingCase->id)->firstOrFail();
        $initialMultiLab = LaboratoryExaminationResult::where('single_suspect_case_id', $existingCase->id)->firstOrFail();
        $this->assertTrue($exhibit->isLockedByPreliminaryReport());

        $linkMultiRes = $this->actingAs($emuOfficer)->postJson('/cases', [
            'link_suspect_to_locked_exhibit' => 1,
            'case_number' => $existingCaseNumber,
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'MULTIPLE',
            'linked_exhibit_ids' => [$exhibit->id],
            'title' => 'Republic vs Ama Serwaa & Kojo Bonsu',
            'suspect_name' => 'Ama Serwaa',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Ama Serwaa',
                        'role' => 'Primary Suspect',
                        'gender' => 'Female',
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name' => 'Kojo Bonsu',
                        'role' => 'Co-Suspect',
                        'gender' => 'Male',
                    ],
                ],
            ],
            'seizures' => [[
                'id' => $exhibit->id,
                'linked_exhibit_id' => $exhibit->id,
                'commodity_name' => 'Altered Commodity',
                'seizure_weight' => 999,
            ]],
        ]);

        $linkMultiRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reused_existing_case', true)
            ->assertJsonPath('case.case_number', $existingCaseNumber)
            ->assertJsonPath('suspects_count', 2);

        $multiCase = MultipleSuspectCase::where('case_number', $existingCaseNumber)->first();
        $this->assertNotNull($multiCase);
        $this->assertSame(1, MultipleSuspectCase::where('case_number', $existingCaseNumber)->count());
        $this->assertNull(SingleSuspectCase::where('case_number', $existingCaseNumber)->first());

        $this->assertSame(2, CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->count());
        $exhibit->refresh();
        $this->assertSame($multiCase->id, $exhibit->multiple_suspect_case_id);
        $this->assertNull($exhibit->single_suspect_case_id);
        $this->assertSame('Multi-Suspect Tramadol', $exhibit->commodity_name);
        $this->assertEquals(12.0, (float) $exhibit->seizure_weight);

        $migratedLab = LaboratoryExaminationResult::where('multiple_suspect_case_id', $multiCase->id)->firstOrFail();
        $this->assertSame($initialMultiLab->id, $migratedLab->id);
        $this->assertSame($initialMultiLab->exam_reference, $migratedLab->exam_reference);
        $this->assertSame('Identify synthetic opioid.', $migratedLab->purpose_of_examination);
        $this->assertSame('YES', $migratedLab->presumptive_result);
        $this->assertTrue(SuspectPreliminaryReport::where('multiple_suspect_case_id', $multiCase->id)->exists());
        $this->assertFalse(Exhibit::unassignedOrAwaitingSuspect()->where('id', $exhibit->id)->exists());
    }

    #[Test]
    public function lab_analysis_no_suspect_stores_source_unit_and_investigating_officer_in_exhibits_for_fresh_and_linked_exhibits(): void
    {
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');

        // A. Fresh Lab Analysis drug exhibit without suspect submitted with custody/source fields in Section 4 seizures[0]
        $freshRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Lab Analysis',
            'seizures' => [[
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Compressed Cannabis Slabs',
                'seizure_quantity' => '6',
                'seizure_packaging' => 'Parcels',
                'seizure_weight' => '5.50',
                'seizure_unit' => 'kg',
                'source_requesting_unit' => 'Tema Harbour Enforcement Command',
                'investigating_officer' => 'Insp. Kofi Boateng',
                'source_of_exhibits' => 'Tema Port Terminal 3 Locker 9',
            ]],
            'section5_completed' => true,
            'lab_workflow_mode' => 'nlr',
            'purpose_of_examination' => 'Identify suspected cannabis consignment.',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cannabis',
        ])->assertOk();

        $freshCaseNumber = $freshRes->json('case.case_number');
        $freshCase = SingleSuspectCase::where('case_number', $freshCaseNumber)->firstOrFail();
        $freshExhibit = Exhibit::where('single_suspect_case_id', $freshCase->id)->firstOrFail();

        $this->assertSame('Tema Port Terminal 3 Locker 9', $freshExhibit->source_of_exhibits);
        $this->assertSame('Tema Harbour Enforcement Command', $freshExhibit->source_requesting_unit);
        $this->assertSame('Insp. Kofi Boateng', $freshExhibit->investigating_officer);

        $freshPdfHtml = $this->actingAs($labOfficer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $freshCaseNumber)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Reference<span[^>]*>Case Name<\/span>\s*<\/td>\s*<td>\s*Tema Port Terminal 3 Locker 9\s*<\/td>/si',
            $freshPdfHtml
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Source\/Requesting Unit\s*<\/td>\s*<td>\s*Tema Harbour Enforcement Command\s*<\/td>/si',
            $freshPdfHtml
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Investigating Officer\s*<\/td>\s*<td>\s*Insp\. Kofi Boateng\s*<\/td>/si',
            $freshPdfHtml
        );

        // B. Linked unassigned drug exhibit originally logged by Evidence Management Unit without source_of_exhibits
        $emuCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-EMU-9910',
            'title' => 'Seized Exhibit: Heroin Sachets (Awaiting Suspect)',
            'suspect_name' => 'Unassigned (No Suspect)',
            'unit_name' => 'Evidence Management',
            'no_suspect' => true,
            'user_id' => $emuOfficer->id,
            'status' => 'Pending Approval',
        ]);
        $emuExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $emuCase->id,
            'property_id' => 'NACOC/EMU/99101',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin Sachets',
            'seizure_quantity' => '12',
            'seizure_packaging' => 'Sachets',
            'source_requesting_unit' => 'Evidence Management',
            'source_of_exhibits' => null,
            'investigating_officer' => null,
            'associated_person' => 'Unassigned (No Suspect)',
            'custodial_status' => 'Seized',
        ]);

        // Lab Analysis officer enters Source/Requesting Unit, Investigating Officer & Source of Exhibits in Section 4 and saves
        $linkedRes = $this->actingAs($labOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Lab Analysis',
            'linked_exhibit_ids' => [$emuExhibit->id],
            'seizures' => [[
                'id' => $emuExhibit->id,
                'linked_exhibit_id' => $emuExhibit->id,
                'property_id' => 'NACOC/EMU/99101',
                'exhibit_category' => 'Drugs',
                'exhibit_type' => 'Drug Exhibit',
                'commodity_name' => 'Heroin Sachets',
                'seizure_quantity' => '12',
                'seizure_packaging' => 'Sachets',
                'source_requesting_unit' => 'KIA Cargo Enforcement Unit',
                'investigating_officer' => 'Det. Supt. Ama Mensah',
                'source_of_exhibits' => 'KIA Freight Bay 4 Consignment',
            ]],
            'section5_completed' => true,
            'lab_workflow_mode' => 'nlr',
            'purpose_of_examination' => 'Screen sachets for opiates.',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Heroin',
        ])->assertOk();

        $linkedCaseNumber = $linkedRes->json('case.case_number');
        $emuExhibit->refresh();

        $this->assertSame('KIA Freight Bay 4 Consignment', $emuExhibit->source_of_exhibits);
        $this->assertSame('KIA Cargo Enforcement Unit', $emuExhibit->source_requesting_unit);
        $this->assertSame('Det. Supt. Ama Mensah', $emuExhibit->investigating_officer);

        $linkedPdfHtml = $this->actingAs($labOfficer)
            ->get('/Lab_Analysis/preliminary-report-pdf/' . $linkedCaseNumber)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td>\s*Case Reference<span[^>]*>Case Name<\/span>\s*<\/td>\s*<td>\s*KIA Freight Bay 4 Consignment\s*<\/td>/si',
            $linkedPdfHtml
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Source\/Requesting Unit\s*<\/td>\s*<td>\s*KIA Cargo Enforcement Unit\s*<\/td>/si',
            $linkedPdfHtml
        );
        $this->assertMatchesRegularExpression(
            '/<td>\s*Investigating Officer\s*<\/td>\s*<td>\s*Det\. Supt\. Ama Mensah\s*<\/td>/si',
            $linkedPdfHtml
        );
    }

    #[Test]
    public function digital_forensics_create_exposes_electronic_device_without_suspect_details_for_autofill(): void
    {
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $dfuOfficer = $this->makeApprovedOfficer('Digital Forensics');

        $emuCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-DFU-AUTO1',
            'title' => 'Seized Exhibit: Apple iPhone 15 Pro Max (Awaiting Suspect)',
            'suspect_name' => 'Unassigned (No Suspect)',
            'unit_name' => 'Evidence Management',
            'no_suspect' => true,
            'user_id' => $emuOfficer->id,
            'status' => 'Pending Approval',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $emuCase->id,
            'property_id' => 'NACOC/EMU/77881',
            'exhibit_category' => 'Storage Media',
            'exhibit_type' => 'Non-Drug Exhibit',
            'commodity_name' => 'Apple iPhone (iOS Smartphone)',
            'seizure_quantity' => '2',
            'seizure_value' => 'GHS 36,000',
            'seizure_concealment' => 'Faraday pouch inside black backpack',
            'seizure_date' => '2026-11-11',
            'seizure_time' => '14:35',
            'seizure_location' => 'Tema Harbour Gate 4 Scanner Bay',
            'seizure_officer' => 'SPO Yaw Mensah (SD-0412)',
            'investigating_officer' => 'Insp. Abena Osei',
            'source_requesting_unit' => 'Enforcement and Operations Unit',
            'source_of_exhibits' => 'Tema Harbour Container MV-44',
            'seizure_reason' => 'Suspected encrypted communications linked to narcotic shipment',
            'dev_model' => 'iPhone 15 Pro Max',
            'dev_serial_no' => 'F2LZX984K102',
            'dev_imei1' => '356938035643809',
            'dev_imei2' => '356938035643817',
            'dev_mac_address' => '00:1A:2B:3C:4D:5E',
            'dev_storage' => '512 GB',
            'dev_colour' => 'Natural Titanium',
            'dev_marks' => 'Hairline crack on lower-left corner',
            'associated_person' => 'Unassigned (No Suspect)',
            'custodial_status' => 'Seized',
        ]);

        $html = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/create')
            ->assertOk()
            ->getContent();

        foreach ([
            'NACOC/EMU/77881',
            'Apple iPhone (iOS Smartphone)',
            'data-dev-model="iPhone 15 Pro Max"',
            'data-dev-serial-no="F2LZX984K102"',
            'data-dev-imei1="356938035643809"',
            'data-dev-imei2="356938035643817"',
            'data-dev-mac-address="00:1A:2B:3C:4D:5E"',
            'data-dev-storage="512 GB"',
            'data-dev-colour="Natural Titanium"',
            'data-dev-marks="Hairline crack on lower-left corner"',
            'data-location="Tema Harbour Gate 4 Scanner Bay"',
            'data-time="14:35"',
            'data-concealment="Faraday pouch inside black backpack"',
            'data-investigating-officer="Insp. Abena Osei"',
            'data-source-unit="Enforcement and Operations Unit"',
            'data-source-of-exhibits="Tema Harbour Container MV-44"',
            'hasElectronicDeviceSpecs(',
            "normalizedCat === 'Electronic Devices'",
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $apiRes = $this->actingAs($dfuOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Digital Forensics'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $matched = collect($apiRes->json('exhibits'))->firstWhere('id', $exhibit->id);
        $this->assertNotNull($matched);
        $this->assertSame('iPhone 15 Pro Max', $matched['dev_model']);
        $this->assertSame('F2LZX984K102', $matched['dev_serial_no']);
        $this->assertSame('356938035643809', $matched['dev_imei1']);
        $this->assertSame('Tema Harbour Gate 4 Scanner Bay', $matched['seizure_location']);
    }

    #[Test]
    public function electronic_device_no_suspect_case_does_not_fall_back_raid_scene_to_seizure_location(): void
    {
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $dfuOfficer = $this->makeApprovedOfficer('Digital Forensics');

        // 1. Create blade templates must not fall back raid_location to primarySeizure.seizure_location
        $dfuCreateHtml = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/create')
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('primarySeizure.seizure_location || \'Digital Forensics Unit\'', $dfuCreateHtml);
        $this->assertStringContainsString('const raidLocation = noSuspectMode ? (rawRaidLocation || null)', $dfuCreateHtml);

        // 2. Unassigned vault lodgement must not store seizure_location in raid_location
        $vaultRes = $this->actingAs($emuOfficer)->postJson('/Evidence_Management/exhibits/store-unassigned', [
            'seizures' => [[
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Laptop / Notebook Computer',
                'seizure_quantity' => '1',
                'seizure_location' => 'Kotoka Air Cargo Inspection Bay',
                'dev_model' => 'MacBook Pro 16',
                'dev_serial_no' => 'C02XYZ9988',
            ]],
        ])->assertOk();

        $vaultCase = SingleSuspectCase::where('case_number', $vaultRes->json('case_number'))->firstOrFail();
        $this->assertNull($vaultCase->raid_location);

        // 3. Electronic device case logged via /cases without suspect biodata and without Place of Incident
        $noIncidentRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: Encrypted Phone (Awaiting Suspect)',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'raid_location' => null,
            'location' => null,
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Devices',
            'seizure_name' => 'Apple iPhone (iOS Smartphone)',
            'seizure_name_device' => 'Apple iPhone (iOS Smartphone)',
            'dev_model' => 'iPhone 14 Pro',
            'seizure_location' => 'Kotoka International Airport Terminal 3',
            'seizures' => [[
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Apple iPhone (iOS Smartphone)',
                'seizure_quantity' => '1',
                'seizure_location' => 'Kotoka International Airport Terminal 3',
                'dev_model' => 'iPhone 14 Pro',
            ]],
        ])->assertOk();

        $noIncidentNumber = $noIncidentRes->json('case.case_number');
        $noIncidentShowHtml = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/cases/' . $noIncidentNumber . '/review')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/Raid\s+Scene \/ Offence Location<\/div>\s*<div[^>]*>\s*N\/A\s*<\/div>/si',
            $noIncidentShowHtml
        );
        $wrappedNoIncident = ForensicCase::wrapRecord(
            SingleSuspectCase::where('case_number', $noIncidentNumber)->firstOrFail()
        );
        $this->assertStringNotContainsString(
            'Raid Scene / Offence Location: **Kotoka International Airport Terminal 3**',
            (string) $wrappedNoIncident->narrative
        );

        $dpfPdfHtml = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/dpf-report-pdf/' . $noIncidentNumber)
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Location:</strong> Kotoka International Airport Terminal 3', $dpfPdfHtml);

        $multiDpfHtml = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/multi_dpf_pdf?cases=' . urlencode($noIncidentNumber))
            ->assertOk()
            ->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<tr[^>]*>.*?Kotoka International Airport Terminal 3.*?<\/tr>/si',
            $multiDpfHtml
        );

        // 4. When Place of Incident IS supplied without suspect biodata, Raid Scene / Offence Location uses Place of Incident
        $withIncidentRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'title' => 'Seized Exhibit: Router Hardware (Awaiting Suspect)',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'raid_location' => 'East Legon Sector 4 Warehouse',
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Devices',
            'seizure_name' => 'Wi-Fi Router',
            'seizure_name_device' => 'Wi-Fi Router',
            'dev_model' => 'MikroTik RB4011',
            'seizure_location' => 'Takoradi Container Yard',
            'seizures' => [[
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Wi-Fi Router',
                'seizure_quantity' => '1',
                'seizure_location' => 'Takoradi Container Yard',
                'dev_model' => 'MikroTik RB4011',
            ]],
        ])->assertOk();

        $withIncidentNumber = $withIncidentRes->json('case.case_number');
        $withIncidentShowHtml = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/cases/' . $withIncidentNumber . '/review')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/Raid\s+Scene \/ Offence Location<\/div>\s*<div[^>]*>\s*East Legon Sector 4 Warehouse\s*<\/div>/si',
            $withIncidentShowHtml
        );

        // 5. Legacy/already-saved no-suspect case where raid_location leaked from seizure_location ('port') still falls back to N/A on show_review
        $legacyLeakedCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-LEGACY-' . random_int(1000, 9999),
            'title' => 'Seized Exhibit: Apple iPhone (iOS Smartphone) (Awaiting Suspect)',
            'unit_name' => 'Digital & Physical Forensic Unit',
            'unit_id' => $dfuOfficer->unit_id,
            'user_id' => $dfuOfficer->id,
            'controller_user_id' => $dfuOfficer->id,
            'arresting_officer' => 'Atta Budo',
            'suspect_name' => 'Unassigned (No Suspect)',
            'raid_location' => 'port',
            'offense_description' => 'Seized exhibit logged without suspect.',
            'status' => 'Under Multi-Unit Processing',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
        ]);
        \App\Models\Exhibit::create([
            'single_suspect_case_id' => $legacyLeakedCase->id,
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Apple iPhone',
            'seizure_quantity' => 1,
            'seizure_location' => 'port',
            'associated_person' => 'Unassigned (No Suspect)',
            'no_suspect' => true,
        ]);

        foreach ([
            '/Digital_Forensics/cases/' . $legacyLeakedCase->case_number . '/review',
            '/Crime_Scene_Support/cases/' . $legacyLeakedCase->case_number . '/review',
            '/cases/' . $legacyLeakedCase->case_number . '/review',
        ] as $reviewUrl) {
            $legacyReviewHtml = $this->actingAs($dfuOfficer)
                ->get($reviewUrl)
                ->assertOk()
                ->getContent();
            $this->assertMatchesRegularExpression(
                '/Raid\s+Scene \/ Offence Location<\/div>\s*<div[^>]*>\s*N\/A\s*<\/div>/si',
                $legacyReviewHtml
            );
        }
    }

    #[Test]
    public function digital_forensics_create_saves_electronic_device_exhibits_without_suspect_into_exhibits_table(): void
    {
        $dfuOfficer = $this->makeApprovedOfficer('Digital Forensics');

        // 1. Verify Digital_Forensics/create renders device persistence & auto-detach safeguards
        $html = $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/create')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('bindAutoDetachOnManifestIdentityEdit()', $html);
        $this->assertStringContainsString('const hasDeviceSpecs = Boolean(devModel || devSerialNo || devImei1', $html);
        $this->assertStringContainsString('commodity_name: seizureName || devModel || exhibitCat', $html);
        $this->assertStringContainsString("updateCommodityField('Electronic Devices', clone)", $html);

        // 2. Saving a device exhibit without selecting Device Type (only dev_model & device specs filled)
        $modelOnlyRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Devices',
            'seizure_name' => '',
            'seizure_name_device' => '',
            'dev_model' => 'Samsung Galaxy S24 Ultra',
            'dev_serial_no' => 'R58W301ABCD',
            'dev_imei1' => '352991009876543',
            'dev_imei2' => '352991009876550',
            'dev_mac_address' => 'AA:BB:CC:DD:EE:FF',
            'dev_storage' => '512 GB',
            'dev_colour' => 'Titanium Gray',
            'dev_marks' => 'Scuff on bezel',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Electronic Devices',
                'associated_person' => 'Unassigned (No Suspect)',
                'seizure_name' => '',
                'seizure_name_device' => '',
                'dev_model' => 'Samsung Galaxy S24 Ultra',
                'dev_serial_no' => 'R58W301ABCD',
                'dev_imei1' => '352991009876543',
                'dev_imei2' => '352991009876550',
                'dev_mac_address' => 'AA:BB:CC:DD:EE:FF',
                'dev_storage' => '512 GB',
                'dev_colour' => 'Titanium Gray',
                'dev_marks' => 'Scuff on bezel',
                'seizure_quantity' => '1',
                'seizure_date' => '2026-11-11',
                'seizure_location' => 'Tamale Regional Office',
            ]],
        ]);

        $modelOnlyRes->assertOk()->assertJsonPath('success', true);
        $caseOne = SingleSuspectCase::where('case_number', $modelOnlyRes->json('case.case_number'))->firstOrFail();
        $exOne = Exhibit::where('single_suspect_case_id', $caseOne->id)->first();
        $this->assertNotNull($exOne, 'Expected Samsung Galaxy S24 Ultra exhibit row in exhibits table.');
        $this->assertSame('Electronic Devices', $exOne->exhibit_category);
        $this->assertSame('Samsung Galaxy S24 Ultra', $exOne->commodity_name);
        $this->assertSame('Samsung Galaxy S24 Ultra', $exOne->dev_model);
        $this->assertSame('R58W301ABCD', $exOne->dev_serial_no);
        $this->assertSame('352991009876543', $exOne->dev_imei1);
        $this->assertSame('352991009876550', $exOne->dev_imei2);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $exOne->dev_mac_address);
        $this->assertSame('512 GB', $exOne->dev_storage);
        $this->assertSame('Titanium Gray', $exOne->dev_colour);
        $this->assertSame('Scuff on bezel', $exOne->dev_marks);
        $this->assertSame('Unassigned (No Suspect)', $exOne->associated_person);

        // 3. Top-level electronic device payload without seizures[] array also persists to exhibits table
        $topLevelRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Devices',
            'seizure_name' => '',
            'seizure_name_device' => 'Apple iPhone (iOS Smartphone)',
            'dev_model' => 'iPhone 15 Pro',
            'dev_serial_no' => 'C39Z99887766',
            'dev_imei1' => '358881001122334',
        ]);

        $topLevelRes->assertOk()->assertJsonPath('success', true);
        $caseTwo = SingleSuspectCase::where('case_number', $topLevelRes->json('case.case_number'))->firstOrFail();
        $exTwo = Exhibit::where('single_suspect_case_id', $caseTwo->id)->first();
        $this->assertNotNull($exTwo, 'Expected top-level iPhone 15 Pro exhibit row in exhibits table.');
        $this->assertSame('Electronic Devices', $exTwo->exhibit_category);
        $this->assertSame('Apple iPhone (iOS Smartphone)', $exTwo->commodity_name);
        $this->assertSame('iPhone 15 Pro', $exTwo->dev_model);
        $this->assertSame('C39Z99887766', $exTwo->dev_serial_no);
        $this->assertSame('358881001122334', $exTwo->dev_imei1);
    }

    #[Test]
    public function generating_extraction_status_log_report_locks_electronic_exhibit_without_suspect_and_allows_linking_suspects(): void
    {
        $dfuOfficer = $this->makeApprovedOfficer('Digital Forensics');
        $emuOfficer = $this->makeApprovedOfficer('Evidence Management');
        $labOfficer = $this->makeApprovedOfficer('Laboratory Analysis');
        $cssOfficer = $this->makeApprovedOfficer('Crime Scene Support');
        $fdOfficer = $this->makeApprovedOfficer('Forensic Directorate');

        // 1. Log an Electronic Device Exhibit Without Suspect(s)
        $createRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Unassigned (No Suspect)',
            'suspect_docket_mode' => 'SINGLE',
            'has_seizures' => true,
            'exhibit_category' => 'Electronic Devices',
            'seizure_name' => 'Encrypted Forensic Smartphone',
            'seizure_name_device' => 'Encrypted Forensic Smartphone',
            'dev_model' => 'iPhone 16 Pro Max',
            'dev_serial_no' => 'F99DFU202601',
            'dev_imei1' => '351122334455667',
            'seizures' => [[
                'item_number' => 1,
                'exhibit_category' => 'Electronic Devices',
                'associated_person' => 'Unassigned (No Suspect)',
                'commodity_name' => 'Encrypted Forensic Smartphone',
                'seizure_name_device' => 'Encrypted Forensic Smartphone',
                'dev_model' => 'iPhone 16 Pro Max',
                'dev_serial_no' => 'F99DFU202601',
                'dev_imei1' => '351122334455667',
                'seizure_quantity' => '1',
                'seizure_location' => 'Tema Port Terminal 2',
                'seizure_date' => '2026-11-11',
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $caseNumber = $createRes->json('case.case_number');
        $singleCase = SingleSuspectCase::where('case_number', $caseNumber)->firstOrFail();
        $exhibit = Exhibit::where('single_suspect_case_id', $singleCase->id)->firstOrFail();

        // Unlocked before Extraction & Investigation Status Log Report is generated
        $this->assertFalse($exhibit->isLockedByDpfReport());
        $this->assertFalse($exhibit->isLockedByReport());

        $apiBefore = $this->actingAs($dfuOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Digital & Physical Forensics (DPFU)'))
            ->assertOk()
            ->json('exhibits');
        $foundBefore = collect($apiBefore)->firstWhere('id', $exhibit->id);
        $this->assertNotNull($foundBefore);
        $this->assertTrue($foundBefore['is_selectable']);
        $this->assertFalse($foundBefore['has_dpf_status_report']);

        // Also ensure it is selectable in /Digital_Forensics/forensic-status
        $statusHtml = $this->actingAs($dfuOfficer)->get('/Digital_Forensics/forensic-status')->assertOk()->getContent();
        $this->assertStringContainsString($caseNumber, $statusHtml);

        // 2. Officer generates Extraction & Investigation Status Log Report
        $statusRes = $this->actingAs($dfuOfficer)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => $caseNumber,
            'location_post' => 'Tema Port Terminal 2',
            'investigation_date' => '2026-11-11',
            'devices_retrieved' => '1 iPhone 16 Pro Max',
            'quantity' => '1',
            'brief_case_description' => 'Unattended encrypted smartphone seized at scanning bay.',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'extraction_analysis_notes' => 'Full file system acquisition completed.',
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_status' => 'YES',
            'intel_shared' => 'NO',
        ])->assertOk()->assertJsonPath('success', true);

        $dpfCode = $statusRes->json('data.dpf_code');
        $this->assertNotEmpty($dpfCode);

        $this->actingAs($dfuOfficer)
            ->get('/Digital_Forensics/dpf-report-pdf/' . urlencode($dpfCode))
            ->assertOk();

        // Reload exhibit and confirm lock state
        $exhibit->refresh();
        $this->assertTrue($exhibit->isLockedByDpfReport());
        $this->assertTrue($exhibit->isLockedByReport());
        $attachedDpf = $exhibit->resolveAttachedDpfReport();
        $this->assertNotNull($attachedDpf);
        $this->assertSame('dpf_status_log', $attachedDpf['report_type']);
        $this->assertSame('Extraction & Investigation Status Log Report (PDF)', $attachedDpf['report_label']);
        $this->assertStringContainsString('/Digital_Forensics/dpf-report-pdf/' . $dpfCode, $attachedDpf['url']);

        // 3. Verify locked state across Digital Forensics & general units' create views (never leaked into Lab Analysis)
        $labHtml = $this->actingAs($labOfficer)->get('/Lab_Analysis/create')->assertOk()->getContent();
        $this->assertStringNotContainsString('Encrypted Forensic Smartphone', $labHtml, 'Electronic-device exhibit must not appear on /Lab_Analysis/create');

        $labApiAfter = $this->actingAs($labOfficer)
            ->getJson('/api/exhibits/unassigned?unit=' . urlencode('Laboratory Analysis (LAU)'))
            ->assertOk()
            ->json('exhibits');
        $this->assertNull(collect($labApiAfter)->firstWhere('id', $exhibit->id), 'Electronic-device exhibit must not appear in Lab Analysis unassigned API');

        $unitCreateEndpoints = [
            [$dfuOfficer, '/Digital_Forensics/create', 'Digital & Physical Forensics (DPFU)'],
            [$emuOfficer, '/Evidence_Management/create', 'Evidence Management Unit (EMU)'],
            [$cssOfficer, '/Crime_Scene_Support/create', 'Crime Scene Support Unit (CSSU)'],
            [$fdOfficer, '/cases/create', 'Forensic Directorate'],
        ];

        foreach ($unitCreateEndpoints as [$actor, $createUrl, $unitQuery]) {
            $html = $this->actingAs($actor)->get($createUrl)->assertOk()->getContent();
            $this->assertStringContainsString('Encrypted Forensic Smartphone', $html, "Locked DF exhibit missing on {$createUrl}");
            $this->assertStringContainsString('Extraction &amp; Investigation Status Log Report (PDF)', $html, "Report label missing on {$createUrl}");
            $this->assertStringContainsString('/Digital_Forensics/dpf-report-pdf/' . $dpfCode, $html, "PDF URL missing on {$createUrl}");

            $apiAfter = $this->actingAs($actor)
                ->getJson('/api/exhibits/unassigned?unit=' . urlencode($unitQuery))
                ->assertOk()
                ->json('exhibits');
            $matched = collect($apiAfter)->firstWhere('id', $exhibit->id);
            $this->assertNotNull($matched, "Locked DF exhibit missing from API for {$unitQuery}");
            $this->assertTrue($matched['has_dpf_status_report']);
            $this->assertTrue($matched['has_preliminary_report']);
            $this->assertFalse($matched['is_selectable']);
            $this->assertSame('Extraction & Investigation Status Log Report (PDF)', $matched['preliminary_report']['report_label']);
        }

        // 4. Attempting to re-select this locked exhibit without link_suspect_to_locked_exhibit is blocked (422)
        $blockedRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'no_suspect' => 1,
            'unit_name' => 'Digital Forensics',
            'linked_exhibit_ids' => [$exhibit->id],
            'seizures' => [[
                'id' => $exhibit->id,
                'linked_exhibit_id' => $exhibit->id,
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Encrypted Forensic Smartphone',
                'dev_model' => 'iPhone 16 Pro Max',
            ]],
        ]);
        $blockedRes->assertStatus(422);
        $this->assertStringContainsString('Extraction & Investigation Status Log Report PDF', (string) $blockedRes->json('message'));

        $blockedNormalRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'title' => 'Republic vs Yao Mensah',
            'suspect_name' => 'Yao Mensah',
            'unit_name' => 'Digital Forensics',
            'linked_exhibit_ids' => [$exhibit->id],
        ]);
        $blockedNormalRes->assertStatus(422);
        $this->assertStringContainsString('Extraction & Investigation Status Log Report PDF', (string) $blockedNormalRes->json('message'));

        // 5. Linking suspect(s) via link_suspect_to_locked_exhibit=1 reuses Case Ref, migrates DpfReport, and removes exhibit from unassigned list
        $linkMultiRes = $this->actingAs($dfuOfficer)->postJson('/cases', [
            'link_suspect_to_locked_exhibit' => 1,
            'case_number' => $caseNumber,
            'unit_name' => 'Digital Forensics',
            'suspect_docket_mode' => 'MULTIPLE',
            'linked_exhibit_ids' => [$exhibit->id],
            'title' => 'Republic vs Yao Mensah & Esi Appiah',
            'suspect_name' => 'Yao Mensah',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Yao Mensah',
                        'role' => 'Primary Suspect',
                        'gender' => 'Male',
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name' => 'Esi Appiah',
                        'role' => 'Co-Suspect',
                        'gender' => 'Female',
                    ],
                ],
            ],
            'seizures' => [[
                'id' => $exhibit->id,
                'linked_exhibit_id' => $exhibit->id,
                'exhibit_category' => 'Electronic Devices',
                'commodity_name' => 'Encrypted Forensic Smartphone',
                'dev_model' => 'iPhone 16 Pro Max',
            ]],
        ]);

        $linkMultiRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reused_existing_case', true)
            ->assertJsonPath('case.case_number', $caseNumber);

        $multiCase = MultipleSuspectCase::where('case_number', $caseNumber)->firstOrFail();
        $primarySuspect = CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)
            ->where('suspect_name', 'Yao Mensah')
            ->firstOrFail();

        $migratedDpf = DpfReport::where('dpf_code', $dpfCode)->firstOrFail();
        $this->assertNull($migratedDpf->single_suspect_case_id);
        $this->assertSame($multiCase->id, $migratedDpf->multiple_suspect_case_id);
        $this->assertSame($primarySuspect->id, $migratedDpf->suspect_id);

        $exhibit->refresh();
        $this->assertSame($multiCase->id, $exhibit->multiple_suspect_case_id);
        $this->assertNull($exhibit->single_suspect_case_id);

        foreach ($unitCreateEndpoints as [$actor, $createUrl, $unitQuery]) {
            $htmlAfter = $this->actingAs($actor)->get($createUrl)->assertOk()->getContent();
            $this->assertStringNotContainsString('Encrypted Forensic Smartphone', $htmlAfter, "Linked DF exhibit still rendered on {$createUrl}");

            $apiFinal = $this->actingAs($actor)
                ->getJson('/api/exhibits/unassigned?unit=' . urlencode($unitQuery))
                ->assertOk()
                ->json('exhibits');
            $this->assertNull(collect($apiFinal)->firstWhere('id', $exhibit->id), "Linked DF exhibit still in API for {$unitQuery}");
        }
    }
}
