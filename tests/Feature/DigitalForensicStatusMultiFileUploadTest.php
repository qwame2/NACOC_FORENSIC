<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\DpfReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DigitalForensicStatusMultiFileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_multiple_court_order_and_witness_statement_files(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-TEST01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $courtFiles = [
            UploadedFile::fake()->create('warrant_01.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('order_02.png', 200, 'image/png'),
        ];

        $witnessFiles = [
            UploadedFile::fake()->create('report_01.pdf', 150, 'application/pdf'),
            UploadedFile::fake()->create('statement_02.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('memo_03.jpg', 80, 'image/jpeg'),
        ];

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-TEST01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES',
            'report_witness_statement_sent' => 'YES',
            'intel_shared' => 'YES',
            'court_order_files' => $courtFiles,
            'report_witness_statement_files' => $witnessFiles,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $report = DpfReport::where('case_number', 'CSS-2026-TEST01')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertCount(2, $report->court_order_files);
        $this->assertCount(3, $report->report_witness_statement_files);
    }

    public function test_forensic_status_page_renders_with_nested_upload_containers(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-TEST02',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);
        $response->assertSee('id="courtOrderFileWrapper" class="hidden pt-1.5"', false);
        $response->assertSee('id="reportWitnessFileWrapper" class="hidden pt-1.5"', false);
        $response->assertSee('id="courtOrderFileList"', false);
        $response->assertSee('id="reportWitnessFileList"', false);
        $response->assertSee('id="courtOrderGranted"', false);
        $response->assertSee('id="extractionAnalysisConducted"', false);
        $response->assertSee('id="ongoingExtractionAnalysisConducted"', false);
        $response->assertSee('id="reportWitnessSent"', false);
        $response->assertSee('id="intelShared"', false);
        $response->assertSee('id="currentCasesSectionContainer"', false);
        $response->assertSee('id="ongoingCasesSectionContainer"', false);
        $response->assertSee('CURRENT/NEW CASES', false);
        $response->assertSee('ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)', false);
        $this->assertEquals(6, substr_count($response->getContent(), '<option value="NO" selected>NO</option>'));
        $response->assertDontSee('id="courtOrderFileWrapper" class="col-span-full"', false);
        $response->assertDontSee('id="reportWitnessFileWrapper" class="col-span-full"', false);
        $response->assertSee('name="extraction_analysis_status"', false);
        $response->assertSee('name="extraction_analysis_notes"', false);
        $response->assertSee('name="ongoing_extraction_analysis_status"', false);
        $response->assertSee('name="ongoing_extraction_analysis_notes"', false);
        $response->assertSee('name="report_witness_statement_status"', false);
        $response->assertSee('name="report_witness_statement_notes"', false);
    }

    public function test_extraction_analysis_combines_status_and_message(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-EXT01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $msg = 'Preliminary forensic analysis completed; full extraction pending grant of court order';

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-EXT01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'extraction_analysis_notes' => $msg,
            'report_witness_statement_sent' => 'NO',
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-EXT01')->latest('id')->first();
        $this->assertNotNull($report);
        $expected = 'YES - ' . $msg;
        $this->assertEquals($expected, $report->extraction_analysis_message);
        $this->assertEquals($expected, $report->extraction_analysis_conducted);
    }

    public function test_extraction_analysis_supports_message_only_without_status(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-EXT02',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $msg = 'Preliminary forensic analysis completed; full extraction pending grant of court order';

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-EXT02',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => '',
            'extraction_analysis_notes' => $msg,
            'report_witness_statement_sent' => 'NO',
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-EXT02')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals($msg, $report->extraction_analysis_message);
        $this->assertEquals($msg, $report->extraction_analysis_conducted);
    }

    public function test_extraction_analysis_supports_status_only_without_message(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-EXT03',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-EXT03',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'extraction_analysis_notes' => '',
            'report_witness_statement_sent' => 'NO',
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-EXT03')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('YES', $report->extraction_analysis_message);
        $this->assertEquals('YES', $report->extraction_analysis_conducted);
    }

    public function test_report_witness_statement_combines_status_and_message(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-RW01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $msg = 'Full forensic report and investigator witness statements dispatched to Directorate';

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-RW01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'NO',
            'extraction_analysis_notes' => '',
            'report_witness_statement_status' => 'YES',
            'report_witness_statement_notes' => $msg,
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-RW01')->latest('id')->first();
        $this->assertNotNull($report);
        $expected = 'YES - ' . $msg;
        $this->assertEquals($expected, $report->report_witness_statement_message);
        $this->assertEquals($expected, $report->report_witness_statement_sent);
    }

    public function test_report_witness_statement_supports_message_only_without_status(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-RW02',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $msg = 'Witness statement awaiting lead investigator counter-signature';

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-RW02',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'NO',
            'extraction_analysis_notes' => '',
            'report_witness_statement_status' => '',
            'report_witness_statement_notes' => $msg,
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-RW02')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals($msg, $report->report_witness_statement_message);
        $this->assertEquals($msg, $report->report_witness_statement_sent);
    }

    public function test_report_witness_statement_supports_status_only_without_message(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-RW03',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-RW03',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'NO',
            'extraction_analysis_notes' => '',
            'report_witness_statement_status' => 'PENDING',
            'report_witness_statement_notes' => '',
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-RW03')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('PENDING', $report->report_witness_statement_message);
        $this->assertEquals('PENDING', $report->report_witness_statement_sent);
    }

    public function test_ongoing_cases_section_container_supports_court_order_and_ongoing_fields(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-ONG01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $extMsg = 'Full extraction and chip-off forensic imaging complete under granted court order';
        $rwMsg = 'Dispatched comprehensive forensic report and lead analyst witness statements';
        $remarkMsg = 'Court order received; forensic analysis concluded with clean hash verification.';

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-ONG01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'ongoing_extraction_analysis_status' => 'YES',
            'ongoing_extraction_analysis_notes' => $extMsg,
            'report_witness_statement_status' => 'YES',
            'report_witness_statement_notes' => $rwMsg,
            'intel_shared' => 'YES',
            'remark' => $remarkMsg,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $report = DpfReport::where('case_number', 'CSS-2026-ONG01')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('YES', $report->court_order_granted);
        $this->assertEquals('YES - ' . $extMsg, $report->extraction_analysis_conducted);
        $this->assertEquals('YES - ' . $extMsg, $report->extraction_analysis_message);
        $this->assertEquals('YES - ' . $rwMsg, $report->report_witness_statement_sent);
        $this->assertEquals('YES - ' . $rwMsg, $report->report_witness_statement_message);
        $this->assertEquals('YES', $report->intel_shared);
        $this->assertEquals($remarkMsg, $report->remark);
    }

    public function test_extraction_and_report_witness_status_do_not_duplicate_when_no_message(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-CLEAN01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        // Scenario 1: Both are NO, notes is empty or just NO
        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-CLEAN01',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_status' => 'NO',
            'extraction_analysis_notes' => 'NO',
            'report_witness_statement_status' => 'NO',
            'report_witness_statement_notes' => 'NO',
            'intel_shared' => 'NO',
        ]);

        $response->assertStatus(200);
        $report1 = DpfReport::where('case_number', 'CSS-2026-CLEAN01')->latest('id')->first();
        $this->assertNotNull($report1);
        $this->assertEquals('NO', $report1->extraction_analysis_conducted);
        $this->assertEquals('NO', $report1->extraction_analysis_message);
        $this->assertEquals('NO', $report1->report_witness_statement_sent);
        $this->assertEquals('NO', $report1->report_witness_statement_message);

        // Scenario 2: Both are YES, notes is empty or just YES
        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-CLEAN02',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response2 = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-CLEAN02',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'extraction_analysis_notes' => 'YES',
            'report_witness_statement_status' => 'YES',
            'report_witness_statement_notes' => 'YES',
            'intel_shared' => 'YES',
        ]);

        $response2->assertStatus(200);
        $report2 = DpfReport::where('case_number', 'CSS-2026-CLEAN02')->latest('id')->first();
        $this->assertNotNull($report2);
        $this->assertEquals('YES', $report2->extraction_analysis_conducted);
        $this->assertEquals('YES', $report2->extraction_analysis_message);
        $this->assertEquals('YES', $report2->report_witness_statement_sent);
        $this->assertEquals('YES', $report2->report_witness_statement_message);
    }

    public function test_dpf_pdf_cleans_legacy_repeated_status_and_displays_only_yes_or_no(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-LEGACY01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $report = DpfReport::create([
            'dpf_code' => 'DF-STATUS-2026-0099',
            'case_number' => 'CSS-2026-LEGACY01',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO - NO',
            'extraction_analysis_message' => 'NO - NO',
            'report_witness_statement_sent' => 'YES - YES',
            'report_witness_statement_message' => 'YES - YES',
            'intel_shared' => 'NO',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/' . $report->dpf_code);
        $response->assertStatus(200);

        // Verify PDF does NOT show "NO - NO" or "YES - YES" in the process status table
        $content = $response->getContent();
        $this->assertStringNotContainsString('NO - NO', $content);
        $this->assertStringNotContainsString('YES - YES', $content);
        $this->assertStringContainsString('Extraction &amp; Forensic Analysis Conducted', $content);
        $this->assertStringContainsString('Report, Witness Statement &amp; Digital Evidence Sent to Investigations', $content);
        $this->assertMatchesRegularExpression('/>\s*NO\s*<\/td>/', $content);
        $this->assertMatchesRegularExpression('/>\s*YES\s*<\/td>/', $content);
    }

    public function test_brief_case_description_fetches_operation_type_from_suspect_operation_code_name(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $caseRaid = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-OPRAID01',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'Kweku Mensah',
            'operation_code_name' => 'Raid',
            'raid_location' => 'Tema Harbour Point',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        // Verify narrative for this case uses "a raid operation" instead of hardcoded "an intelligence-led operation"
        $content = $response->getContent();
        $this->assertStringContainsString('conducted a raid operation at Tema Harbour Point', $content);

        // Verify with another operation type e.g. Controlled Delivery
        $caseCD = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-OPCD02',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'John Doe',
            'operation_code_name' => 'Controlled Delivery',
            'raid_location' => 'Kotoka International Airport',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response2 = $this->actingAs($user)->get('/Digital_Forensics/forensic-status');
        $response2->assertStatus(200);
        $content2 = $response2->getContent();
        $this->assertStringContainsString('conducted a controlled delivery operation at Kotoka International Airport', $content2);
    }

    public function test_dpf_report_pdf_replaces_high_court_label_with_current_new_cases_or_ongoing_cases_label(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $caseCurrent = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-SECT01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        // 1. Submit status while in CURRENT/NEW CASES section (court_order_granted = NO)
        $response1 = $this->actingAs($user)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-SECT01',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_status' => 'NO',
            'intel_shared' => 'NO',
        ]);
        $response1->assertStatus(200);

        $report1 = DpfReport::where('case_number', 'CSS-2026-SECT01')->latest('id')->first();
        $this->assertNotNull($report1);
        $this->assertEquals('CURRENT/NEW CASES', $report1->report_data['case_section_label'] ?? null);

        // Fetch PDF for Current/New Case
        $pdfResponse1 = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/' . $report1->dpf_code);
        $pdfResponse1->assertStatus(200);
        $pdfContent1 = $pdfResponse1->getContent();

        // Must display CURRENT/NEW CASES and NOT HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER
        $this->assertStringContainsString('CURRENT/NEW CASES', $pdfContent1);
        $this->assertStringNotContainsString('HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER', $pdfContent1);
        $this->assertStringNotContainsString('ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)', $pdfContent1);

        // 2. Now court order is granted -> update case in ONGOING CASES section (court_order_granted = YES)
        $response2 = $this->actingAs($user)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-SECT01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'report_witness_statement_status' => 'YES',
            'intel_shared' => 'YES',
        ]);
        $response2->assertStatus(200);

        $report2 = DpfReport::where('case_number', 'CSS-2026-SECT01')->latest('id')->first();
        $this->assertNotNull($report2);
        $this->assertEquals('ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)', $report2->report_data['case_section_label'] ?? null);

        // Fetch PDF for Ongoing Case
        $pdfResponse2 = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/' . $report2->dpf_code);
        $pdfResponse2->assertStatus(200);
        $pdfContent2 = $pdfResponse2->getContent();

        // Must display ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED) and NOT HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER
        $this->assertStringContainsString('ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)', $pdfContent2);
        $this->assertStringNotContainsString('HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER', $pdfContent2);
    }

    public function test_updating_investigation_status_updates_existing_record_without_duplication(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-NODUP01',
            'status' => 'active',
            'created_at' => now(),
        ]);

        // First save
        $response1 = $this->actingAs($user)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-NODUP01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'NO',
            'extraction_analysis_status' => 'NO',
            'report_witness_statement_status' => 'NO',
            'intel_shared' => 'NO',
            'remark' => 'Initial notes',
        ]);
        $response1->assertStatus(200);

        $initialReport = DpfReport::where('case_number', 'CSS-2026-NODUP01')->first();
        $this->assertNotNull($initialReport);
        $initialDpfCode = $initialReport->dpf_code;
        $this->assertEquals(1, DpfReport::where('case_number', 'CSS-2026-NODUP01')->count());

        // Second save (updating status)
        $response2 = $this->actingAs($user)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-NODUP01',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_status' => 'YES',
            'report_witness_statement_status' => 'YES',
            'intel_shared' => 'YES',
            'remark' => 'Updated notes after analysis',
        ]);
        $response2->assertStatus(200);

        // Must still have exactly 1 record for this case number
        $this->assertEquals(1, DpfReport::where('case_number', 'CSS-2026-NODUP01')->count());
        $updatedReport = DpfReport::where('case_number', 'CSS-2026-NODUP01')->first();
        $this->assertEquals($initialDpfCode, $updatedReport->dpf_code);
        $this->assertEquals('Updated notes after analysis', $updatedReport->remark);
        $this->assertEquals('YES', $updatedReport->extraction_analysis_conducted);
        $this->assertEquals('YES', $updatedReport->report_witness_statement_sent);

        // Check PDF uses the updated report data
        $pdfResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/' . $updatedReport->dpf_code);
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('Updated notes after analysis', $pdfResponse->getContent());
    }

    public function test_preview_button_on_new_suspect_previews_new_suspect_report_not_old_suspect(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        // Old suspect with saved DpfReport
        $oldCase = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-OLD999',
            'suspect_name' => 'OLD SUSPECT KOFI',
            'status' => 'active',
            'created_at' => now()->subDays(5),
        ]);

        DpfReport::create([
            'dpf_code' => 'DF-STATUS-OLD-01',
            'case_number' => 'CSS-2026-OLD999',
            'location_post' => 'Old Post Location',
            'investigation_date' => now()->subDays(5)->format('Y-m-d'),
            'devices_retrieved' => 'Old Hardware Item',
            'quantity' => '1',
            'brief_case_description' => 'Old case narrative for Kofi.',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO',
            'extraction_analysis_message' => 'NO',
            'report_witness_statement_sent' => 'NO',
            'report_witness_statement_message' => 'NO',
            'intel_shared' => 'NO',
            'remark' => 'Old suspect notes',
            'status' => 'active',
        ]);

        // New suspect case linked to electronics (NO DpfReport row yet)
        $newCase = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-NEW111',
            'suspect_name' => 'NEW SUSPECT KWAME ELECTRONICS',
            'status' => 'active',
            'created_at' => now(),
            'phones' => [
                [
                    'brand' => 'Apple',
                    'model' => 'iPhone 15 Pro Max',
                    'serial' => 'IPH15-SN-9988',
                    'imei' => '358999123456789',
                ]
            ],
        ]);

        // Preview the new suspect case by case_number
        $response = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/CSS-2026-NEW111');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must display the new suspect's case number and name
        $this->assertStringContainsString('CSS-2026-NEW111', $content);
        $this->assertStringContainsString('NEW SUSPECT KWAME ELECTRONICS', $content);

        // Must NOT display the old suspect's name or notes
        $this->assertStringNotContainsString('OLD SUSPECT KOFI', $content);
        $this->assertStringNotContainsString('Old case narrative for Kofi.', $content);
    }

    public function test_brief_case_description_omits_drugs_when_suspect_only_linked_to_electronics(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        // 1. Suspect linked ONLY to electronics (no drugs)
        $onlyElecCase = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-ELEC01',
            'suspect_name' => 'ELECTRONICS ONLY SUSPECT',
            'status' => 'active',
            'created_at' => now(),
            'phones' => [
                ['model' => 'MacBook Pro M3', 'serial' => 'MBP-9922']
            ],
        ]);

        $wrappedElecCase = \App\Models\ForensicCase::allCases()->firstWhere('case_number', 'CSS-2026-ELEC01');
        $this->assertNotNull($wrappedElecCase);
        $elecNarrative = $wrappedElecCase->generateBriefCaseDescription();

        // Must NOT mention drugs
        $this->assertStringNotContainsString('narcotic drugs', strtolower($elecNarrative));
        $this->assertStringNotContainsString('drugs', strtolower($elecNarrative));
        // Must mention electronic devices / digital evidence
        $this->assertStringContainsString('electronic devices', $elecNarrative);

        // Check PDF preview for only electronics
        $responseElec = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/CSS-2026-ELEC01');
        $responseElec->assertStatus(200);
        $contentElec = $responseElec->getContent();
        $this->assertStringNotContainsString('use and sale of narcotic drugs', $contentElec);
        $this->assertStringContainsString('electronic devices and digital evidence', $contentElec);

        // 2. Suspect linked to BOTH drugs AND electronics
        $bothCase = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-BOTH01',
            'suspect_name' => 'DUAL NARCO-CYBER SUSPECT',
            'status' => 'active',
            'created_at' => now(),
            'phones' => [
                ['model' => 'Samsung S24 Ultra', 'serial' => 'SM-9900']
            ],
        ]);

        \App\Models\Exhibit::create([
            'single_suspect_case_id' => $bothCase->id,
            'exhibit_number' => 'EX-01',
            'commodity_name' => 'Cocaine Powder',
            'exhibit_category' => 'Narcotic Drugs & Psychotropic Substances',
            'seizure_name_drug' => 'Cocaine Powder',
        ]);

        $wrappedBothCase = \App\Models\ForensicCase::allCases()->firstWhere('case_number', 'CSS-2026-BOTH01');
        $this->assertNotNull($wrappedBothCase);
        $bothNarrative = $wrappedBothCase->generateBriefCaseDescription();

        // Must mention BOTH drugs and electronics
        $this->assertStringContainsString('narcotic drugs', strtolower($bothNarrative));
        $this->assertStringContainsString('electronic devices', strtolower($bothNarrative));
    }

    public function test_remarks_displays_na_when_user_has_not_entered_anything(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-NOREMARK',
            'suspect_name' => 'SUSPECT NO REMARK',
            'status' => 'active',
            'created_at' => now(),
            'phones' => [
                ['model' => 'Pixel 9 Pro', 'serial' => 'PX9-0011']
            ],
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/CSS-2026-NOREMARK');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must NOT contain the automatic default sentence
        $this->assertStringNotContainsString('Registry intake logged. Electronic exhibits pending forensic write-block extraction and court order verification.', $content);
        $this->assertStringNotContainsString('Forensic acquisition completed cleanly. Hardware hash checksums match baseline acquisition.', $content);
    }

    public function test_exhibit_type_displays_qty_and_device_model_name(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-EXHIBIT-MODEL',
            'suspect_name' => 'SUSPECT EXHIBIT MODEL TEST',
            'status' => 'active',
            'created_at' => now(),
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Apple iPhone (iOS Smartphone)',
            'dev_model' => 'iPhone 18 Pro Max',
            'seizure_quantity' => '3',
            'seizure_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/CSS-2026-EXHIBIT-MODEL');
        $response->assertStatus(200);
        $content = $response->getContent();

        // The Exhibit Type should display the quantity and device model name (e.g. "3 iPhone 18 Pro Max")
        $this->assertStringContainsString('3 iPhone 18 Pro Max', $content);

        // The Logged Date should display date only (Y-m-d) without time (H:i:s)
        $this->assertStringContainsString('Report Date', $content);
        $this->assertStringContainsString(now()->format('Y-m-d'), $content);
        $this->assertStringNotContainsString(now()->format('Y-m-d H:i:s'), $content);
    }

    public function test_multi_dpf_pdf_renders_and_displays_multiple_suspects(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $multiCase = \App\Models\MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-MULTI-DPF',
            'status' => 'active',
            'created_at' => now(),
            'total_suspects_count' => 3,
        ]);

        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'KWAME MENSAH ALPHA',
            'role' => 'PRIMARY SUSPECT',
            'nationality' => 'Ghanaian',
            'gender' => 'Male',
            'age' => 34,
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'KOJO ASANTE BRAVO',
            'role' => 'CO-SUSPECT',
            'nationality' => 'Ghanaian',
            'gender' => 'Male',
            'age' => 28,
        ]);
        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 3,
            'suspect_name' => 'YAA AMPONSAH CHARLIE',
            'role' => 'ASSOCIATE',
            'nationality' => 'Ghanaian',
            'gender' => 'Female',
            'age' => 25,
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Electronic Devices',
            'dev_model' => 'SAMSUNG Galaxy S24 Ultra',
            'seizure_quantity' => '3',
            'seizure_date' => now()->format('Y-m-d'),
        ]);

        // Request via multi route
        $response = $this->actingAs($user)->get('/Digital_Forensics/multi-dpf-report-pdf/CSS-2026-MULTI-DPF');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must display all multiple suspects and badges
        $this->assertStringContainsString('KWAME MENSAH ALPHA', $content);
        $this->assertStringContainsString('KOJO ASANTE BRAVO', $content);
        $this->assertStringContainsString('YAA AMPONSAH CHARLIE', $content);
        $this->assertTrue(str_contains($content, 'NACOC_LOGO.PNG') || str_contains($content, 'NACOC LOGO.jfif'));
        $this->assertStringContainsString('3 SAMSUNG Galaxy S24 Ultra', $content);

        // Standard dpf-report-pdf on a case with multiple suspects treats it as one case using dpf_pdf
        $autoResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/CSS-2026-MULTI-DPF');
        $autoResponse->assertStatus(200);
        $this->assertStringContainsString('OFFICIAL FORENSIC DOSSIER', $autoResponse->getContent());
        $this->assertStringContainsString('KWAME MENSAH ALPHA', $autoResponse->getContent());
        $this->assertStringContainsString('KOJO ASANTE BRAVO', $autoResponse->getContent());
        $this->assertStringContainsString('YAA AMPONSAH CHARLIE', $autoResponse->getContent());
    }

    public function test_generate_filter_prints_all_suspects_in_table_using_multi_dpf_pdf(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        // Case 1 with suspect Linda Asare
        $case1 = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-8687',
            'suspect_name' => 'LINDA ASARE',
            'nationality' => 'Ghanaian',
            'gender' => 'Female',
            'age' => 31,
            'status' => 'active',
            'created_at' => now(),
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case1->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Electronic Devices',
            'dev_model' => 'SAMSUNG S24',
            'seizure_quantity' => '3',
            'seizure_date' => now()->format('Y-m-d'),
        ]);

        // Case 2 with suspect Victor Ozyo
        $case2 = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-1530',
            'suspect_name' => 'VICTOR OZYO',
            'nationality' => 'Nigerian',
            'gender' => 'Male',
            'age' => 36,
            'status' => 'active',
            'created_at' => now(),
        ]);
        \App\Models\Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case2->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Electronic Devices',
            'dev_model' => 'iPhone 18',
            'seizure_quantity' => '3',
            'seizure_date' => now()->format('Y-m-d'),
        ]);

        // Request multi_dpf_pdf with the filtered case references and print=1
        $response = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-8687,NCC-2026-1530&print=1');
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Must use the multi_dpf_pdf view template with clean official cover page
        $this->assertStringContainsString('OFFICIAL SUSPECT FORENSIC CASE FILE', $content);
        $this->assertStringNotContainsString('Consolidated Suspect Investigation', $content);
        $this->assertStringNotContainsString('Dossier Compilation Overview', $content);

        // 2. Must display all suspects in their individual reports
        $this->assertStringContainsString('LINDA ASARE', $content);
        $this->assertStringContainsString('VICTOR OZYO', $content);
        $this->assertTrue(str_contains($content, 'NACOC_LOGO.PNG') || str_contains($content, 'NACOC LOGO.jfif'));
        $this->assertStringNotContainsString('OFFICIAL FORENSIC DOSSIER COMPILATION', $content);

        // 3. Must display their respective case tags
        $this->assertStringContainsString('NCC-2026-8687', $content);
        $this->assertStringContainsString('NCC-2026-1530', $content);

        // 4. Must display formatted exhibits across filtered suspects
        $this->assertStringContainsString('3 SAMSUNG S24', $content);
        $this->assertStringContainsString('3 iPhone 18', $content);

        // 5. Must display individual suspect report pages with suspect particulars & photo placement
        $this->assertStringContainsString('Suspect Identification &amp; Case Particulars', $content);

        // 6. When no filters are selected, it must show GENERAL REPORT
        $this->assertStringContainsString('GENERAL REPORT', $content);
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $content);
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $content);

        // 7. Must include the automatic window.print() trigger because print=1 was set
        $this->assertStringContainsString('window.print()', $content);
    }

    public function test_multi_dpf_pdf_displays_correct_report_period_labels(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-PERIOD-TEST',
            'suspect_name' => 'SAMUEL QUAYE',
            'nationality' => 'Ghanaian',
            'gender' => 'Male',
            'age' => 30,
            'status' => 'active',
            'created_at' => now(),
        ]);

        // Weekly
        $resWeekly = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-PERIOD-TEST&period=weekly');
        $resWeekly->assertStatus(200);
        $this->assertTrue(str_contains($resWeekly->getContent(), 'WEEKLY SITUATIONAL REPORT') || str_contains($resWeekly->getContent(), 'WEEKLY REPORT'));
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $resWeekly->getContent());
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $resWeekly->getContent());

        // Monthly
        $resMonthly = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-PERIOD-TEST&period=monthly');
        $resMonthly->assertStatus(200);
        $this->assertTrue(str_contains($resMonthly->getContent(), 'MONTHLY SITUATIONAL REPORT') || str_contains($resMonthly->getContent(), 'MONTHLY REPORT'));
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $resMonthly->getContent());
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $resMonthly->getContent());

        // Annual
        $resAnnual = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-PERIOD-TEST&period=annual');
        $resAnnual->assertStatus(200);
        $this->assertTrue(str_contains($resAnnual->getContent(), 'ANNUAL SITUATIONAL REPORT') || str_contains($resAnnual->getContent(), 'ANNUAL REPORT'));
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $resAnnual->getContent());
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $resAnnual->getContent());

        // Date Range
        $resDateRange = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-PERIOD-TEST&from=2026-06-01&to=2026-06-20');
        $resDateRange->assertStatus(200);
        $this->assertStringContainsString('DATE RANGE REPORT', $resDateRange->getContent());
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $resDateRange->getContent());
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $resDateRange->getContent());

        // General report without filters
        $resGeneral = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-PERIOD-TEST');
        $resGeneral->assertStatus(200);
        $this->assertStringContainsString('GENERAL REPORT', $resGeneral->getContent());
        $this->assertStringNotContainsString('DIRECTORY COMPILATION', $resGeneral->getContent());
        $this->assertStringNotContainsString('CURRENT/NEW CASES &bull;', $resGeneral->getContent());
    }

    public function test_multi_dpf_pdf_always_places_current_new_cases_first(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        // Case 1 is an ONGOING case (court order granted = YES)
        $ongoingCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-ONGOING-FIRST',
            'suspect_name' => 'ONGOING SUSPECT',
            'status' => 'active',
            'created_at' => now(),
        ]);
        \App\Models\DpfReport::create([
            'case_number' => 'NCC-2026-ONGOING-FIRST',
            'dpf_code' => 'DPF-NCC-2026-ONGOING-FIRST',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES',
        ]);

        // Case 2 is a CURRENT/NEW case (court order granted = NO)
        $newCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-CURRENT-SECOND',
            'suspect_name' => 'CURRENT SUSPECT',
            'status' => 'active',
            'created_at' => now(),
        ]);
        \App\Models\DpfReport::create([
            'case_number' => 'NCC-2026-CURRENT-SECOND',
            'dpf_code' => 'DPF-NCC-2026-CURRENT-SECOND',
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO',
        ]);

        // Request multi_dpf_pdf with the ongoing case first in the query parameter
        $response = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-ONGOING-FIRST,NCC-2026-CURRENT-SECOND');
        $response->assertStatus(200);

        $content = $response->getContent();

        // The CURRENT SUSPECT must appear before the ONGOING SUSPECT in the rendered report pages
        $currentPos = strpos($content, 'REPUBLIC VS CURRENT SUSPECT');
        $ongoingPos = strpos($content, 'REPUBLIC VS ONGOING SUSPECT');

        $this->assertNotFalse($currentPos, 'CURRENT SUSPECT should be present in multi_dpf_pdf');
        $this->assertNotFalse($ongoingPos, 'ONGOING SUSPECT should be present in multi_dpf_pdf');
        $this->assertLessThan($ongoingPos, $currentPos, 'CURRENT/NEW CASES must always appear before ONGOING CASES');
    }

    public function test_court_order_granted_dropdown_is_disabled_when_court_order_requested_is_no_in_view(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-DIS01',
            'suspect_name' => 'Disabled Dropdown Test',
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('id="courtOrderRequested"', $html);
        $this->assertStringContainsString('onchange="handleCourtOrderRequestedChange(this.value)"', $html);
        $this->assertStringContainsString('id="courtOrderGranted"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('handleCourtOrderRequestedChange', $html);
    }

    public function test_saving_status_forces_court_order_granted_to_no_when_court_order_requested_is_no(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        SingleSuspectCase::create([
            'case_number' => 'CSS-2026-FORCE-NO',
            'suspect_name' => 'Court Order Override Test',
        ]);

        $response = $this->actingAs($user)->post('/Digital_Forensics/forensic-status', [
            'case_number' => 'CSS-2026-FORCE-NO',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'NO',
            'report_witness_statement_sent' => 'NO',
        ]);

        $response->assertStatus(200);
        $report = DpfReport::where('case_number', 'CSS-2026-FORCE-NO')->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('NO', $report->court_order_requested);
        $this->assertEquals('NO', $report->court_order_granted);
    }

    public function test_case_with_primary_and_co_suspect_uses_dpf_pdf_and_reports_use_multi_dpf_pdf(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $multiCase = \App\Models\MultipleSuspectCase::create([
            'case_number' => 'NCC-2026-3423',
            'location' => 'ACCRA',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $multiCase->suspects()->createMany([
            [
                'suspect_name' => 'CURRY SEIDA',
                'role' => 'Primary Suspect',
                'nationality' => 'Ghanaian',
                'gender' => 'Male',
                'age' => 29,
                'is_associate' => false,
            ],
            [
                'suspect_name' => 'COMFORT KINGS',
                'role' => 'Co-Suspect',
                'nationality' => 'Ghanaian',
                'gender' => 'Female',
                'age' => 26,
                'is_associate' => false,
            ],
        ]);

        \App\Models\Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Electronic Devices',
            'dev_model' => 'iPHONE 17',
            'seizure_quantity' => '4',
            'seizure_date' => now()->format('Y-m-d'),
        ]);

        // 1. Single Case viewing must use dpf_pdf (not multi_dpf_pdf)
        $singleResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/NCC-2026-3423');
        $singleResponse->assertStatus(200);
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString('OFFICIAL FORENSIC DOSSIER', $singleContent);
        $this->assertStringNotContainsString('OFFICIAL SUSPECT FORENSIC CASE FILE', $singleContent);
        $this->assertStringContainsString('CURRY SEIDA', $singleContent);
        $this->assertStringContainsString('COMFORT KINGS', $singleContent);
        $this->assertStringContainsString('4 iPHONE 17', $singleContent);

        // 2. Generating a report (e.g. general, weekly, monthly, annual) must use multi_dpf_pdf
        $reportResponse = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-3423&period=general');
        $reportResponse->assertStatus(200);
        $reportContent = $reportResponse->getContent();

        $this->assertStringContainsString('OFFICIAL SUSPECT FORENSIC CASE FILE', $reportContent);
        $this->assertStringContainsString('GENERAL REPORT', $reportContent);
        $this->assertStringContainsString('CURRY SEIDA', $reportContent);
        $this->assertStringContainsString('COMFORT KINGS', $reportContent);
    }
}





