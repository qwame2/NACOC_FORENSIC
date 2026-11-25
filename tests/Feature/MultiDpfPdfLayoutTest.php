<?php

namespace Tests\Feature;

use App\Models\DpfReport;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDpfPdfLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_dpf_pdf_includes_word_like_paged_media_css_and_static_cover_page(): void
    {
        $user = User::factory()->create();
        $case = ForensicCase::create([
            'case_number' => 'NCC-2026-LAYOUT-TEST',
            'suspect_name' => 'KOBBY MENSAH',
            'exhibit_name' => 'iPhone 13',
            'status' => 'active',
            'case_type' => 'digital',
            'evidence_type' => 'Mobile Device',
            'submitting_officer' => 'Insp. John Doe',
            'date_received' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-LAYOUT-TEST');
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. A4 Page Dimensions & Margin Rules
        $this->assertStringContainsString('size: A4 portrait;', $content);
        $this->assertStringContainsString('@page', $content);

        // 2. Cover Page is strictly static 1-page container
        $this->assertStringContainsString('cover-page', $content);
        $this->assertStringContainsString('width: 210mm', $content);
        $this->assertStringContainsString('height: 297mm', $content);
        $this->assertStringContainsString('overflow: hidden', $content);

        // 3. Dynamic Dossier Pages use Word-like dynamic auto-pagination across A4 sheets
        $this->assertStringContainsString('dynamic-dossier-page', $content);
        $this->assertStringContainsString('auto-paginated-root', $content);
        $this->assertStringContainsString('autoPaginateAllReports', $content);
        $this->assertStringContainsString('renumberAllPages', $content);

        // 4. Intelligent Page Break Rules
        $this->assertStringContainsString('avoid-break', $content);
        $this->assertStringContainsString('avoid-break-after', $content);
        $this->assertStringContainsString('page-break', $content);

        // 5. NACOC Watermark
        $this->assertStringContainsString('dossier-watermark', $content);
    }

    public function test_long_remarks_pagination_preserves_all_text_including_final_sentence(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'unit' => 'Digital Forensics',
        ]);

        $firstSentence = 'First sentence of technical extraction notes regarding mobile evidence.';
        $midParagraph = 'Intermediate paragraph detailing recovered WhatsApp chats and maritime transport manifests shared with Intel.';
        $finalSentence = 'Final concluding sentence confirms zero integrity tampering and evidence is stored in NACOC vault.';

        $longRemarks = "{$firstSentence}\n\n{$midParagraph}\n\n{$finalSentence}";

        $case = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-2026-LONG-REMARKS',
            'unit_name' => 'Digital Forensics',
            'suspect_name' => 'KWAME ADDO',
            'status' => 'active',
            'created_at' => now(),
        ]);

        \App\Models\DpfReport::create([
            'dpf_code' => 'DF-STATUS-2026-LONG-TEST',
            'case_number' => 'NCC-2026-LONG-REMARKS',
            'single_suspect_case_id' => $case->id,
            'user_id' => $user->id,
            'remark' => $longRemarks,
            'report_data' => [
                'suspect_name' => 'KWAME ADDO',
                'case_section_label' => 'CURRENT/NEW CASES',
            ],
        ]);

        // 1. Verify multi_dpf_pdf
        $multiResponse = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-LONG-REMARKS');
        $multiResponse->assertStatus(200);
        $multiContent = $multiResponse->getContent();

        $this->assertStringContainsString($firstSentence, $multiContent);
        $this->assertStringContainsString($midParagraph, $multiContent);
        $this->assertStringContainsString($finalSentence, $multiContent);
        $this->assertStringNotContainsString('report-flow-body flex-1 min-h-0 flex flex-col justify-between overflow-hidden', $multiContent);

        // 2. Verify dpf_pdf
        $singleResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/DF-STATUS-2026-LONG-TEST');
        $singleResponse->assertStatus(200);
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString($firstSentence, $singleContent);
        $this->assertStringContainsString($midParagraph, $singleContent);
        $this->assertStringContainsString($finalSentence, $singleContent);
        $this->assertStringNotContainsString('report-flow-body flex-1 min-h-0 flex flex-col justify-between overflow-hidden', $singleContent);
    }

    public function test_uploaded_signature_of_unit_head_shows_in_generated_report(): void
    {
        $head = User::factory()->create([
            'fullname' => 'Ofori Graham',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $case = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-2026-SIG-TEST',
            'unit_name' => 'Digital & Physical Forensics',
            'suspect_name' => 'KOBBY MENSAH',
            'status' => 'active',
            'created_at' => now(),
        ]);

        \App\Models\DpfReport::create([
            'dpf_code' => 'DF-STATUS-2026-SIG-001',
            'case_number' => 'NCC-2026-SIG-TEST',
            'single_suspect_case_id' => $case->id,
            'user_id' => $head->id,
            'investigation_date' => now()->toDateString(),
            'court_order_granted' => 'YES',
        ]);

        // 1. Single report (dpf_pdf)
        $singleResponse = $this->actingAs($head)->get('/Digital_Forensics/dpf-report-pdf/DF-STATUS-2026-SIG-001');
        $singleResponse->assertStatus(200);
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString('Head of Digital &amp; Physical Forensics', $singleContent);
        $this->assertStringContainsString('Electronically Signed &amp; Validated', $singleContent);
        $this->assertStringContainsString('Ofori Graham', $singleContent);
        $this->assertStringContainsString('Head of Digital &amp; Physical Forensics Unit', $singleContent);
        $this->assertStringContainsString('Digital &amp; Physical Forensics Unit, NACOC', $singleContent);
        $this->assertStringContainsString('alt="Official Digital Signature - Ofori Graham"', $singleContent);
        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', $singleContent);

        // 2. Multi report (multi_dpf_pdf)
        $multiResponse = $this->actingAs($head)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-SIG-TEST');
        $multiResponse->assertStatus(200);
        $multiContent = $multiResponse->getContent();

        $this->assertStringContainsString('Head of Digital &amp; Physical Forensics', $multiContent);
        $this->assertStringContainsString('Electronically Signed &amp; Validated', $multiContent);
        $this->assertStringContainsString('Ofori Graham', $multiContent);
        $this->assertStringContainsString('Head of Digital &amp; Physical Forensics Unit, NACOC', $multiContent);
        $this->assertStringContainsString('alt="Official Digital Signature - Ofori Graham"', $multiContent);
        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', $multiContent);
    }

    public function test_location_post_appears_in_full_without_truncate_on_dpf_report_pdf(): void
    {
        $user = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $fullLocation = 'Kumasi Adum Commercial Area';

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-LOC-TEST',
            'title' => 'Location Full Display Test',
            'suspect_name' => 'Kwabena Darko',
            'location' => $fullLocation,
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'priority' => 'NORMAL',
            'user_id' => $user->id,
        ]);

        DpfReport::create([
            'case_number' => 'NCC-2026-LOC-TEST',
            'dpf_code' => 'DF-STATUS-2026-LOC-01',
            'single_suspect_case_id' => $case->id,
            'user_id' => $user->id,
            'location_post' => $fullLocation,
            'investigation_date' => now()->toDateString(),
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO',
            'report_witness_statement_sent' => 'NO',
            'intel_shared' => 'NO',
        ]);

        $response = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/DF-STATUS-2026-LOC-01');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Location / Post header must be present
        $this->assertStringContainsString('Location / Post', $content);

        // The full location string must be rendered
        $this->assertStringContainsString($fullLocation, $content);

        // Must not contain truncate on Location / Post
        $this->assertStringNotContainsString('Location / Post</p>' . "\n" . '                                <p class="mt-1 text-sm font-bold text-slate-900 truncate">', $content);
        $this->assertStringContainsString('leading-snug break-words">' . $fullLocation . '</p>', $content);
    }

    public function test_summary_table_renders_in_dpf_pdf_and_multi_dpf_pdf(): void
    {
        $user = User::factory()->create([
            'unit' => 'Digital Forensics',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-SUMMARY-TEST',
            'title' => 'Republic vs Test Suspect',
            'suspect_name' => 'Test Suspect',
            'location' => 'Accra Cargo Terminal',
            'unit_name' => 'Digital Forensics',
            'status' => 'active',
            'priority' => 'HIGH',
            'dev_model' => 'Samsung Galaxy S22 Ultra',
            'user_id' => $user->id,
        ]);

        \App\Models\Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'dev_model' => 'Samsung Galaxy S22 Ultra',
            'commodity_name' => 'Smartphone',
            'exhibit_type' => 'Electronic Devices',
            'exhibit_category' => 'Electronic Hardware',
            'seizure_quantity' => '1',
            'user_id' => $user->id,
        ]);

        $report = DpfReport::create([
            'case_number' => 'NCC-2026-SUMMARY-TEST',
            'dpf_code' => 'DF-STATUS-2026-SUMM-01',
            'single_suspect_case_id' => $case->id,
            'user_id' => $user->id,
            'location_post' => 'Accra Cargo Terminal',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES - Logical Acquisition Completed',
            'remark' => 'Summary test remarks.',
            'report_data' => [
                'case_section_label' => 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)',
            ],
        ]);

        // 1. Single DPF PDF
        $singleResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/DF-STATUS-2026-SUMM-01');
        $singleResponse->assertStatus(200);
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString('section-summary', $singleContent);
        $this->assertStringContainsString('Summary', $singleContent);
        $this->assertStringContainsString('No.', $singleContent);
        $this->assertStringContainsString('Case Name', $singleContent);
        $this->assertStringContainsString('Status', $singleContent);
        $this->assertStringContainsString('Device Model', $singleContent);
        $this->assertStringContainsString('Court Order Warrant Requested', $singleContent);
        $this->assertStringContainsString('Court Order Warrant Granted', $singleContent);
        $this->assertStringContainsString('Extraction &amp; Forensic Analysis Conducted', $singleContent);
        $this->assertStringContainsString('Republic vs Test Suspect', $singleContent);
        $this->assertStringContainsString('Ongoing', $singleContent);
        $this->assertStringContainsString('Samsung Galaxy S22 Ultra', $singleContent);

        // 2. Multi DPF PDF (with multiple cases)
        $case2 = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-SUMMARY-TEST-2',
            'title' => 'Republic vs Second Suspect',
            'suspect_name' => 'Second Suspect',
            'location' => 'Tema Port',
            'unit_name' => 'Digital Forensics',
            'status' => 'active',
            'priority' => 'HIGH',
            'user_id' => $user->id,
        ]);

        \App\Models\Exhibit::create([
            'single_suspect_case_id' => $case2->id,
            'dev_model' => 'iPhone 14 Pro Max',
            'commodity_name' => 'Smartphone',
            'exhibit_type' => 'Electronic Devices',
            'exhibit_category' => 'Electronic Hardware',
            'seizure_quantity' => '1',
            'user_id' => $user->id,
        ]);

        $report2 = DpfReport::create([
            'case_number' => 'NCC-2026-SUMMARY-TEST-2',
            'dpf_code' => 'DF-STATUS-2026-SUMM-02',
            'single_suspect_case_id' => $case2->id,
            'user_id' => $user->id,
            'location_post' => 'Tema Port',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO',
            'remark' => 'Second suspect remarks.',
            'report_data' => [
                'case_section_label' => 'CURRENT/NEW CASES',
            ],
        ]);

        $multiResponse = $this->actingAs($user)->get('/Digital_Forensics/multi_dpf_pdf?cases=NCC-2026-SUMMARY-TEST,NCC-2026-SUMMARY-TEST-2');
        $multiResponse->assertStatus(200);
        $multiContent = $multiResponse->getContent();

        // Must have the dedicated summary page under the last case
        $this->assertStringContainsString('section-summary-page', $multiContent);
        $this->assertStringContainsString('Consolidated Forensic &amp; Warrant Status Log', $multiContent);

        // Individual suspect dossier pages should NOT have section-summary embedded (not dpf-report-pdf style)
        $firstSuspectDossierEnd = strpos($multiContent, 'Suspect 1 &bull; Page');
        $this->assertNotFalse($firstSuspectDossierEnd);
        $summaryPagePos = strpos($multiContent, 'section-summary-page');
        $this->assertNotFalse($summaryPagePos);
        $this->assertTrue($summaryPagePos > $firstSuspectDossierEnd, 'Summary page must be placed under the cases');

        // All cases must be summarized in the single summary table
        $this->assertStringContainsString('Republic vs Test Suspect', $multiContent);
        $this->assertStringContainsString('Republic vs Second Suspect', $multiContent);
        $this->assertStringContainsString('Samsung Galaxy S22 Ultra', $multiContent);
        $this->assertStringContainsString('iPhone 14 Pro Max', $multiContent);

        // Table headers must be present
        $this->assertStringContainsString('No.', $multiContent);
        $this->assertStringContainsString('Case Name', $multiContent);
        $this->assertStringContainsString('Status', $multiContent);
        $this->assertStringContainsString('Device Model', $multiContent);
        $this->assertStringContainsString('Court Order Warrant Requested', $multiContent);
        $this->assertStringContainsString('Court Order Warrant Granted', $multiContent);
        $this->assertStringContainsString('Extraction &amp; Forensic Analysis Conducted', $multiContent);

        // Status values: one is Ongoing, one is Current
        $this->assertStringContainsString('Ongoing', $multiContent);
        $this->assertStringContainsString('Current', $multiContent);

        // In the summary page, section-summary appears on top of Conditions:
        $summaryTableInSummaryPage = strpos($multiContent, 'flow-block section-summary overflow-hidden');
        $conditionsInSummaryPage = strpos($multiContent, 'Note: The result(s) relate only to the item(s) / digital exhibit(s) examined across all referenced forensic cases.');
        $this->assertNotFalse($summaryTableInSummaryPage);
        $this->assertNotFalse($conditionsInSummaryPage);
        $this->assertTrue($summaryTableInSummaryPage < $conditionsInSummaryPage, 'In summary page, Summary table must be on top of Conditions');
    }

    public function test_nine_cases_consolidated_summary_table_has_pagination_and_all_rows(): void
    {
        $user = User::factory()->create([
            'unit' => 'Digital Forensics',
            'is_approved' => true,
        ]);

        $caseNumbers = [];
        for ($i = 1; $i <= 9; $i++) {
            $caseNo = "NCC-2026-MULTI-{$i}";
            $caseNumbers[] = $caseNo;

            $case = SingleSuspectCase::create([
                'case_number' => $caseNo,
                'title' => "Republic vs Suspect {$i} (Operation Coast Sentinel)",
                'suspect_name' => "Suspect {$i}",
                'location' => 'Coast Guard Station',
                'unit_name' => 'Digital Forensics',
                'status' => 'active',
                'priority' => 'HIGH',
                'user_id' => $user->id,
            ]);

            \App\Models\Exhibit::create([
                'single_suspect_case_id' => $case->id,
                'dev_model' => "Garmin inReach Explorer+ Model {$i}",
                'commodity_name' => 'GPS Tracker',
                'exhibit_type' => 'Electronic Devices',
                'exhibit_category' => 'Electronic Hardware',
                'seizure_quantity' => '1',
                'user_id' => $user->id,
            ]);

            DpfReport::create([
                'case_number' => $caseNo,
                'dpf_code' => "DF-STATUS-2026-NINE-0{$i}",
                'single_suspect_case_id' => $case->id,
                'user_id' => $user->id,
                'location_post' => 'Coast Guard Station',
                'court_order_requested' => 'YES',
                'court_order_granted' => 'YES',
                'extraction_analysis_conducted' => 'Decoded satellite ping intervals and historical GPS track logs using Cellebrite UFED ISP analysis and Garmin BaseCamp.',
                'remark' => "Technical extraction log for Case {$i}.",
                'report_data' => [
                    'case_section_label' => 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)',
                ],
            ]);
        }

        $casesQuery = implode(',', $caseNumbers);
        $response = $this->actingAs($user)->get("/Digital_Forensics/multi_dpf_pdf?cases={$casesQuery}");
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Consolidated Summary Register exists
        $this->assertStringContainsString('Consolidated Summary Register', $content);
        $this->assertStringContainsString('9 Cases Recorded', $content);

        // 2. All 9 rows are generated in the summary table
        for ($i = 1; $i <= 9; $i++) {
            $this->assertStringContainsString("Republic vs Suspect {$i} (Operation Coast Sentinel)", $content);
            $this->assertStringContainsString("Garmin inReach Explorer+ Model {$i}", $content);
        }

        // 3. Client-side pagination logic for summary register is included
        $this->assertStringContainsString('paginateSummaryRegisterPage', $content);
        $this->assertStringContainsString('generated-summary-page', $content);
        $this->assertStringContainsString('dpfSummaryPristineSnapshots', $content);
        $this->assertStringContainsString('getOrCreateCurrentTbody', $content);
        $this->assertStringContainsString('activeTbody.removeChild(rowEl)', $content);
        $this->assertStringContainsString('createNextSummaryPage()', $content);

        // 4. Single DPF PDF pagination also contains row splitting for summary tables
        $singleResponse = $this->actingAs($user)->get('/Digital_Forensics/dpf-report-pdf/DF-STATUS-2026-NINE-01');
        $singleResponse->assertStatus(200);
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString('createSummaryTableBlock', $singleContent);
        $this->assertStringContainsString('Summary (Cont.)', $singleContent);
        $this->assertStringContainsString('curTableObj.tbody.removeChild(row)', $singleContent);
    }
}
