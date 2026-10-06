<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\SuspectGsaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GsaReportUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_upload_and_attach_gsa_report_file_to_analytical_report_page()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'fullname' => 'Lab Chemist',
            'unit' => 'Forensic Laboratory',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA01',
            'title' => 'Republic vs Suspect GSA Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect GSA Target',
            'role' => 'Primary Suspect',
        ]);

        $file = UploadedFile::fake()->create('signed_gsa_certificate.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'case_suspect_id' => $suspect->id,
                'multiple_suspect_case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'lab_number' => 'Lab No. 12/FS03/2026',
                'gsa_report_images' => [$file],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'CSS-2026-GSA01',
            'original_filename' => 'signed_gsa_certificate.pdf',
        ]);

        // Check that analytical report page renders the attached GSA report file
        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $this->assertStringContainsString('signed_gsa_certificate.pdf', $pageResponse->getContent());
    }

    #[Test]
    public function gsa_upload_section_is_hidden_by_default_and_reveals_via_gsa_certificate_button()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA02',
            'title' => 'Republic vs Suspect Hidden GSA Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $html = $pageResponse->getContent();

        // GSA Upload Section container has hidden class by default
        $this->assertStringContainsString('id="gsaUploadSection"', $html);
        $this->assertStringContainsString('class="hidden bg-white', $html);

        // GSA Certificate button exists to trigger unhiding
        $this->assertStringContainsString('onclick="showGsaReportUploadSection()"', $html);
    }

    #[Test]
    public function gsa_upload_without_suspect_id_resolves_suspect_by_name()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA03',
            'title' => 'Republic vs Suspects GSA Resolve Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);
        CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo Adu',
            'role' => 'Primary Suspect',
        ]);
        $second = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Ama Serwaa',
            'role' => 'Co-Suspect',
        ]);

        // Mirrors what the analytical page sends: no case_suspect_id, only the card's suspect name
        $response = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'multiple_suspect_case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'suspect_name' => 'AMA SERWAA',
                'gsa_report_images' => [UploadedFile::fake()->create('gsa.pdf', 100, 'application/pdf')],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_suspect_id' => $second->id,
            'multiple_suspect_case_id' => $mCase->id,
            'original_filename' => 'gsa.pdf',
        ]);
    }

    #[Test]
    public function saving_exhibit_description_attaches_nlr_report_pdf_to_suspect_case()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-NLR01',
            'title' => 'Republic vs Suspect NLR Attach Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo NLR Target',
            'role' => 'Primary Suspect',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/Lab_Analysis/nlr-reports/store', [
                'case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'laboratory_number' => 'Lab No. 99/NLR/2026',
                'detailed_exhibit_description' => 'Tested positive for High Purity Cocaine Hydrochloride.',
                'condition_of_reserved_sample' => 'good',
                'suspect_id' => $suspect->id,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Assert suspect_nlr_reports database table has attached NLR Report PDF record for this suspect
        $this->assertDatabaseHas('suspect_nlr_reports', [
            'case_suspect_id' => $suspect->id,
            'multiple_suspect_case_id' => $mCase->id,
            'file_path' => 'Lab_Analysis/nlr-report-pdf/' . $mCase->id,
        ]);

        // Assert analytical page displays attached report file
        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $this->assertStringContainsString('NLR_Report_', $pageResponse->getContent());
    }

    #[Test]
    public function buttons_are_disabled_per_suspect_and_top_header_only_when_all_suspects_have_reports()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DIS01',
            'title' => 'Republic vs Disabled Buttons Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'First Suspect Attached',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Second Suspect Pending',
            'role' => 'Co-Suspect',
        ]);

        \App\Models\Exhibit::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_type' => 'Drug',
            'drug_name' => 'Cocaine',
            'associated_person' => 'First Suspect Attached',
            'status' => 'active',
        ]);

        \App\Models\Exhibit::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_type' => 'Drug',
            'drug_name' => 'Heroin',
            'associated_person' => 'Second Suspect Pending',
            'status' => 'active',
        ]);

        // Attach an NLR Report to suspect 1 ONLY
        \App\Models\SuspectNlrReport::create([
            'case_suspect_id' => $suspect1->id,
            'multiple_suspect_case_id' => $mCase->id,
            'case_number' => $mCase->case_number,
            'lab_number' => 'Lab No. 101/NLR/2026',
            'original_filename' => 'NLR_Report_CSS-2026-DIS01.pdf',
            'stored_filename' => 'NLR_Report_CSS-2026-DIS01.pdf',
            'file_path' => 'Lab_Analysis/nlr-report-pdf/' . $mCase->id,
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 12345,
            'status' => 'active',
            'uploaded_by' => $user->id,
        ]);

        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $html = $pageResponse->getContent();

        // Top header buttons MUST be enabled because Suspect 2 has no report attached yet!
        $this->assertStringContainsString('onclick="showGsaReportUploadSection()"', $html);
        $this->assertStringContainsString('onclick="openNlrReportModal()"', $html);

        // Suspect 1's card buttons are disabled
        $this->assertStringContainsString('disabled title="A report has already been generated and attached."', $html);

        // Attach NLR report to suspect 2 as well
        \App\Models\SuspectNlrReport::create([
            'case_suspect_id' => $suspect2->id,
            'multiple_suspect_case_id' => $mCase->id,
            'case_number' => $mCase->case_number,
            'lab_number' => 'Lab No. 102/NLR/2026',
            'original_filename' => 'NLR_Report_CSS-2026-DIS02.pdf',
            'stored_filename' => 'NLR_Report_CSS-2026-DIS02.pdf',
            'file_path' => 'Lab_Analysis/nlr-report-pdf/' . $mCase->id,
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 12345,
            'status' => 'active',
            'uploaded_by' => $user->id,
        ]);

        $fullResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $fullResponse->assertStatus(200);
        $fullHtml = $fullResponse->getContent();

        // Now top header buttons are disabled because ALL suspects have reports attached!
        $this->assertStringContainsString('disabled title="Reports have already been generated and attached for all suspects in this case."', $fullHtml);
    }
}


