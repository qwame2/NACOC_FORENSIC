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
}

