<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
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
}
