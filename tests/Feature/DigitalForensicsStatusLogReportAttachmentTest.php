<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DpfReport;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalForensicsStatusLogReportAttachmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function selecting_yes_for_report_witness_sent_attaches_extraction_status_log_report_to_suspect_case()
    {
        $dpfOfficer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
            'is_head' => false,
        ]);

        $forensicHead = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-DF-2026-001',
            'title' => 'State vs Cyber Target',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $dpfOfficer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kofi Mensah',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kofi Mensah',
            'exhibit_category' => 'Electronic Evidence',
            'commodity_name' => 'iPhone 15 Pro Max',
            'dev_model' => 'iPhone 15 Pro Max',
            'seizure_quantity' => 1,
        ]);

        // Submit forensic status with report_witness_statement_status = YES
        $response = $this->actingAs($dpfOfficer)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => $multiCase->case_number . '|' . $suspect->id,
            'suspect_id' => $suspect->id,
            'location_post' => 'Kotoka International Airport',
            'investigation_date' => '2026-03-15',
            'devices_retrieved' => 'iPhone 15 Pro Max',
            'quantity' => '1',
            'brief_case_description' => 'Target device extracted successfully.',
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES - Logical and physical acquisition complete',
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_status' => 'YES',
            'report_witness_statement_notes' => 'Forensic report transmitted to CID',
            'intel_shared' => 'YES',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify that Extraction & Investigation Status Log Report was attached in SuspectGsaReport
        $attached = SuspectGsaReport::where('case_number', $multiCase->case_number)
            ->where('case_suspect_id', $suspect->id)
            ->first();

        $this->assertNotNull($attached, 'Extraction & Investigation Status Log Report must be attached to the suspect');
        $this->assertStringContainsString('Extraction & Investigation Status Log Report', $attached->original_filename);
        $this->assertStringContainsString('/Digital_Forensics/dpf-report-pdf/', $attached->file_path);

        // Verify show_review page displays the attached report for the Forensic Head
        $reviewRespHead = $this->actingAs($forensicHead)->get("/cases/{$multiCase->id}/review");
        $reviewRespHead->assertStatus(200);
        $reviewHtmlHead = $reviewRespHead->getContent();

        $this->assertStringContainsString('Attached Report Files', $reviewHtmlHead);
        $this->assertStringContainsString('Extraction &amp; Investigation Status Log Report', $reviewHtmlHead);
        $this->assertStringContainsString('/Digital_Forensics/dpf-report-pdf/', $reviewHtmlHead);

        // Verify show_review page displays the attached report for the Digital Forensics Head
        $dpfHead = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $reviewRespDpf = $this->actingAs($dpfHead)->get("/Digital_Forensics/cases/{$multiCase->id}/review");
        $reviewRespDpf->assertStatus(200);
        $reviewHtmlDpf = $reviewRespDpf->getContent();

        $this->assertStringContainsString('Attached Report Files', $reviewHtmlDpf);
        $this->assertStringContainsString('Extraction &amp; Investigation Status Log Report', $reviewHtmlDpf);
    }

    #[Test]
    public function selecting_no_detaches_extraction_status_log_report()
    {
        $dpfOfficer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NCC-DF-2026-002',
            'suspect_name' => 'Kwame Nkrumah',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $dpfOfficer->id,
        ]);

        // 1. Submit YES first
        $this->actingAs($dpfOfficer)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => $singleCase->case_number,
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_status' => 'YES',
        ]);

        $this->assertTrue(
            SuspectGsaReport::where('case_number', $singleCase->case_number)
                ->where('original_filename', 'LIKE', '%Extraction & Investigation Status Log Report%')
                ->exists()
        );

        // 2. Submit NO
        $this->actingAs($dpfOfficer)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => $singleCase->case_number,
            'report_witness_statement_sent' => 'NO',
            'report_witness_statement_status' => 'NO',
        ]);

        $this->assertFalse(
            SuspectGsaReport::where('case_number', $singleCase->case_number)
                ->where('original_filename', 'LIKE', '%Extraction & Investigation Status Log Report%')
                ->exists()
        );
    }

    #[Test]
    public function witness_statement_and_digital_evidence_files_are_excluded_from_attached_report_files_on_show_review(): void
    {
        $dpfOfficer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $cssOfficer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $forensicHead = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NCC-DF-2026-WS01',
            'title' => 'Republic vs Witness Filter Test',
            'suspect_name' => 'Ama Boateng',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $dpfOfficer->id,
        ]);

        $suspect = CaseSuspect::create([
            'single_suspect_case_id' => $singleCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Ama Boateng',
        ]);

        // 1. Uploading report_witness_statement_files via forensic-status must store on DpfReport only (not SuspectGsaReport)
        $uploadedWitness = UploadedFile::fake()->create('Forensic_Witness_Statement.pdf', 120, 'application/pdf');
        $resp = $this->actingAs($dpfOfficer)->postJson('/Digital_Forensics/forensic-status', [
            'case_number' => $singleCase->case_number . '|' . $suspect->id,
            'suspect_id' => $suspect->id,
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_status' => 'YES',
            'report_witness_statement_files' => [$uploadedWitness],
        ]);
        $resp->assertStatus(200)->assertJson(['success' => true]);

        $dpf = DpfReport::where('case_number', $singleCase->case_number)->firstOrFail();
        $this->assertNotEmpty($dpf->report_witness_statement_files);

        $this->assertFalse(
            SuspectGsaReport::where('case_number', $singleCase->case_number)
                ->where('original_filename', 'LIKE', '%Witness Statement & Digital Evidence%')
                ->exists()
        );

        // 2. Even if a legacy Witness Statement & Digital Evidence row already exists in suspect_gsa_reports, show_review hides it
        SuspectGsaReport::create([
            'single_suspect_case_id' => $singleCase->id,
            'case_suspect_id' => $suspect->id,
            'case_number' => $singleCase->case_number,
            'original_filename' => 'Witness Statement & Digital Evidence - legacy_witness.pdf',
            'stored_filename' => 'legacy_witness.pdf',
            'file_path' => '/uploads/forensic_documents/1794438926_6af4f70ee7de2_report_witness_legacy.pdf',
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'exhibit_type' => 'Witness Statement & Evidence',
            'notes' => 'Attached Report, Witness Statement & Digital Evidence file',
            'uploaded_by' => $dpfOfficer->id,
            'status' => 'active',
        ]);

        foreach ([
            [$forensicHead, "/cases/{$singleCase->id}/review"],
            [$cssOfficer, "/Crime_Scene_Support/cases/{$singleCase->id}/review"],
            [$dpfOfficer, "/Digital_Forensics/cases/{$singleCase->id}/review"],
        ] as [$actor, $reviewUrl]) {
            $html = $this->actingAs($actor)->get($reviewUrl)->assertOk()->getContent();
            $this->assertStringNotContainsString('Witness Statement &amp; Digital Evidence', $html);
            $this->assertStringNotContainsString('Witness Statement & Digital Evidence', $html);
            $this->assertStringNotContainsString('Witness Statement &amp; Evidence', $html);
        }
    }
}

