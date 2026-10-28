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

        $attachedPos = strpos($content, 'Attached Report Files (1)');

        $this->assertNotFalse($dossierPos, 'Primary & Co-Suspect Seizures & Exhibits container must be present in show_review');
        $this->assertNotFalse($attachedPos, 'Attached Report Files (1) container must be present in show_review');
        $this->assertGreaterThan($dossierPos, $attachedPos, 'Attached Report Files (1) must be placed under Primary & Co-Suspect Seizures & Exhibits');
    }
}
