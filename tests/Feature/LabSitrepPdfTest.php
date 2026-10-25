<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\DrugScreening;
use App\Models\SuspectGsaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabSitrepPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_lab_analysis_report(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/report');
        $response->assertStatus(200);

        $pdfResponse = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $pdfResponse->assertStatus(200);
    }

    public function test_lab_report_has_cover_page_matching_multi_dpf_pdf(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. A4 Portrait Media & Styling
        $this->assertStringContainsString('size: A4 portrait;', $content);
        $this->assertStringContainsString('cover-page', $content);
        $this->assertStringContainsString('width: 210mm', $content);
        $this->assertStringContainsString('height: 297mm', $content);

        // 2. Cover Page Header & Crest
        $this->assertStringContainsString('REPUBLIC OF GHANA', $content);
        $this->assertStringContainsString('ISO/IEC 17025', $content);
        $this->assertStringContainsString('NARCOTICS CONTROL COMMISSION', $content);
        $this->assertStringContainsString('DEPARTMENT OF FORENSIC', $content);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $content);
        $this->assertStringContainsString('NACOC_LOGO.PNG', $content);

        // 3. Cover Page Title & Notice
        $this->assertStringContainsString('OFFICIAL FORENSIC DRUG ANALYSIS', $content);
        $this->assertStringContainsString('CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD', $content);
        $this->assertStringContainsString('ALL DRUG-RELATED CASES &amp; SUSPECT REGISTRY', $content);
    }

    public function test_lab_report_contains_all_uploaded_file_information_and_suspects(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Seizures Table / Preliminary Forensic Examination
        $this->assertStringContainsString('Post Office', $content);
        $this->assertStringContainsString('Emmanuel Agyare', $content);
        $this->assertStringContainsString('Heston Adjei', $content);
        $this->assertStringContainsString('At Large', $content);
        $this->assertStringContainsString('2 bottles', $content);
        $this->assertStringContainsString('Preliminary analysis was inconclusive', $content);
        $this->assertStringContainsString('Prohibited items', $content);

        $this->assertStringContainsString('Aviance (DHL)', $content);
        $this->assertStringContainsString('Suspected Hashish', $content);
        $this->assertStringContainsString('detected the presence of THC', $content);

        $this->assertStringContainsString('George Ayisi', $content);
        $this->assertStringContainsString('Lois Ayisi', $content);

        $this->assertStringContainsString('Swissport', $content);
        $this->assertStringContainsString('Suspected Apeta Trust Sirop', $content);
        $this->assertStringContainsString('3 bottles', $content);

        $this->assertStringContainsString('Tema Port Unit', $content);
        $this->assertStringContainsString('detected the presence of Codeine', $content);

        // 2. Section 02: Drug Screening of Suspects
        $this->assertStringContainsString('Drug Screening of Suspects (Urine Toxicology)', $content);
        $this->assertStringContainsString('Hah tabulu', $content);
        $this->assertStringContainsString('Investigative', $content);
        $this->assertStringContainsString('No drugs detected', $content);

        // 3. Section 03: GSA Submissions
        $this->assertStringContainsString('Ghana Standards Authority (GSA) Submissions', $content);
        $this->assertStringContainsString('Exhibits earmarked for destruction', $content);
        $this->assertStringContainsString('Suspected Cannabis, and tapentadol', $content);
        $this->assertStringContainsString('879/FS5/2026', $content);
        $this->assertStringContainsString('Exhibits duly submitted awaiting laboratory report', $content);
    }

    public function test_dual_official_signatures_rendered_on_dossier(): void
    {
        $head = User::factory()->create([
            'fullname' => 'John Mensah',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $deptHead = User::factory()->create([
            'fullname' => 'Anita Addo',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($head)->get('/Lab_Analysis/lab_sitrep_pdf');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Head of Laboratory Analysis Unit', $content);
        $this->assertStringContainsString('John Mensah', $content);
        $this->assertStringContainsString('Head of Forensic Department', $content);
        $this->assertStringContainsString('Anita Addo', $content);
        $this->assertStringContainsString('Electronically Signed &amp; Validated', $content);
    }

    public function test_general_report_url_routes_to_lab_report_for_lab_officer(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Report');
        $response->assertStatus(200);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $response->getContent());
        $this->assertStringContainsString('Preliminary Forensic Examination &amp; Drug Testing Log', $response->getContent());
    }

    public function test_filtering_by_search_query(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        // Search for 'Hashish'
        $response = $this->actingAs($user)->get('/Lab_Analysis/report?search=Hashish');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Suspected Hashish', $content);
        $this->assertStringNotContainsString('Apeta Trust', $content);
    }
}
