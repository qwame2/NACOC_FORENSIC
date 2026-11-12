<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SuspectNlrReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachedReportPlacementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function attached_report_files_container_appears_under_primary_and_co_suspect_seizures_container()
    {
        $officer = User::factory()->create([
            'unit' => 'Laboratory Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-TEST-4001',
            'title' => 'Republic vs John, Jane',
            'unit_name' => 'Laboratory Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'John Doe',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Jane Smith',
        ]);

        // Joint exhibit owned by all/joint suspects
        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => null,
            'associated_person' => 'All Suspects',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin (Brown Sugar)',
            'seizure_quantity' => 2,
            'seizure_unit' => 'Blocks',
        ]);

        // Attached NLR report file for the case
        SuspectNlrReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_number' => 'NCC-TEST-4001',
            'original_filename' => 'NLR_Report_Test.pdf',
            'stored_filename' => 'test.pdf',
            'file_path' => 'nlr_reports/test.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'user_id' => $officer->id,
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$multiCase->id}/review");

        $response->assertStatus(200);

        $content = $response->getContent();

        $dossierPos = strpos($content, 'Primary &amp; Co-Suspect Seizures &amp; Exhibits');
        if ($dossierPos === false) {
            $dossierPos = strpos($content, 'Primary & Co-Suspect Seizures & Exhibits');
        }

        $attachedPos = strpos($content, 'Attached Report Files (1)', $dossierPos);

        $this->assertNotFalse($dossierPos, 'Primary & Co-Suspect Seizures & Exhibits container must be present in show_review');
        $this->assertNotFalse($attachedPos, 'Attached Report Files (1) container must be present in show_review');
        $this->assertGreaterThan($dossierPos, $attachedPos, 'Attached Report Files (1) must be placed under Primary & Co-Suspect Seizures & Exhibits');
        $this->assertSame(1, substr_count($content, 'Attached Report Files (1)'), 'Joint Attached Report Files container must render strictly once on show_review');
    }

    #[Test]
    public function suspect_attached_report_files_render_once_for_single_suspect_and_multi_suspect_cases()
    {
        $officer = User::factory()->create([
            'unit' => 'Laboratory Analysis',
            'is_approved' => true,
        ]);

        // 1. Single-suspect case with an exhibit and preliminary report
        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-ONCE-SINGLE-01',
            'title' => 'Single Suspect Once Check',
            'unit_name' => 'Laboratory Analysis',
            'suspect_name' => 'Kofi Mensah',
            'exhibit_category' => 'Drugs',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'associated_person' => 'Kofi Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Parcel',
        ]);

        \App\Models\SuspectPreliminaryReport::create([
            'single_suspect_case_id' => $singleCase->id,
            'case_number' => 'NCC-ONCE-SINGLE-01',
            'original_filename' => 'Preliminary_Report_Single.pdf',
            'stored_filename' => 'prelim_single.pdf',
            'file_path' => '/Lab_Analysis/preliminary-report-pdf/NCC-ONCE-SINGLE-01',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'status' => 'active',
        ]);

        foreach ([
            "/Lab_Analysis/cases/{$singleCase->case_number}/review",
            "/cases/{$singleCase->case_number}/review",
        ] as $url) {
            $resp = $this->actingAs($officer)->get($url);
            if ($resp->isRedirection()) {
                $resp = $this->actingAs($officer)->get($resp->headers->get('Location'));
            }
            $resp->assertOk();
            $html = $resp->getContent();
            $this->assertSame(1, substr_count($html, 'gsa-attached-files'), "Expected strictly 1 Attached Report Files container on {$url}");
            $this->assertSame(1, substr_count($html, 'Attached Report Files (1)'), "Expected Attached Report Files (1) heading once on {$url}");
            $this->assertSame(1, substr_count($html, 'title="Preliminary_Report_Single.pdf"'), "Expected strictly 1 report item on {$url}");
        }

        // 2. Multi-suspect case where suspect 1 owns a suspect-specific report and joint exhibits exist
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-ONCE-MULTI-02',
            'title' => 'Republic vs Kwaku, Ama',
            'unit_name' => 'Laboratory Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwaku Osei',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Co-Suspect',
            'suspect_name' => 'Ama Serwaa',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'associated_person' => 'All Suspects',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Shared Encrypted Drive',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Unit',
        ]);

        \App\Models\SuspectGsaReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => $suspect1->id,
            'case_number' => 'NCC-ONCE-MULTI-02',
            'original_filename' => 'kwaku_only_gsa.pdf',
            'stored_filename' => 'kwaku_only_gsa.pdf',
            'file_path' => 'gsa_reports/kwaku_only_gsa.pdf',
            'file_extension' => 'pdf',
            'file_size' => 2048,
            'status' => 'active',
        ]);

        $multiHtml = $this->actingAs($officer)->get("/Lab_Analysis/cases/{$multiCase->case_number}/review")->assertOk()->getContent();
        $this->assertSame(1, substr_count($multiHtml, 'gsa-attached-files'), 'Suspect-specific report must appear strictly once (under its suspect dossier, not duplicated in joint section)');
        $this->assertSame(1, substr_count($multiHtml, 'Attached Report Files (1)'));
        $this->assertSame(1, substr_count($multiHtml, 'title="kwaku_only_gsa.pdf"'));
    }
}
