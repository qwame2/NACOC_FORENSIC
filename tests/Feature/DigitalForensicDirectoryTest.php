<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DpfReport;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalForensicDirectoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function directory_table_displays_suspect_name_and_front_mugshot()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        // Create a single suspect case with a front mugshot
        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-DF-2026-001',
            'title' => 'Operation Cyber Trace',
            'suspect_name' => 'Kofi Mensah',
            'suspect_alias' => 'Shadow',
            'mugshot_front' => 'uploads/mugshots/kofi_front.jpg',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'exhibit_category' => 'Electronic Devices / Mobile Exhibits',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'iPhone 15 Pro Max',
            'dev_model' => 'iPhone 15 Pro Max',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Pieces',
        ]);

        // Create a multiple suspect case with co-suspects
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NACOC-DF-2026-002',
            'title' => 'Operation Golden Gate',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'NORMAL',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Ama Serwaa',
            'suspect_alias' => 'Queen',
            'mugshot_front' => 'uploads/mugshots/ama_front.png',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Yaw Boateng',
            'suspect_alias' => 'Fox',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'MacBook Pro',
            'dev_model' => 'MacBook Pro',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Pieces',
        ]);

        // Create a DPF report for the first case
        DpfReport::create([
            'case_number' => 'NACOC-DF-2026-001',
            'dpf_code' => 'DPF-2026-001',
            'single_suspect_case_id' => $singleCase->id,
            'user_id' => $officer->id,
            'investigation_date' => now()->toDateString(),
            'court_order_granted' => 'YES',
            'extraction_analysis_conducted' => 'YES',
            'report_witness_statement_sent' => 'NO',
            'intel_shared' => 'YES',
        ]);

        $response = $this->actingAs($officer)->get('/Digital_Forensics/forensic-status');

        $response->assertStatus(200);

        // Verify Suspect column header is present
        $response->assertSee('<th class="p-3">Suspect</th>', false);

        // Verify search input placeholder includes Suspect
        $response->assertSee('placeholder="Filter by Suspect, Case Ref, Location, or Status..."', false);

        // Verify Primary Suspect Names appear in the table
        $response->assertSee('Kofi Mensah');
        $response->assertSee('Ama Serwaa');

        // Verify Aliases appear
        $response->assertSee('a.k.a. Shadow');
        $response->assertSee('a.k.a. Queen');

        // Verify Mugshots appear with asset paths and openMugshotModal click handler
        $response->assertSee('kofi_front.jpg');
        $response->assertSee('ama_front.png');
        $response->assertSee('openMugshotModal');

        // Verify Co-Suspect badge appears for multi-suspect case
        $response->assertSee('+1 Co-Suspect');

        // Verify Enlarged Mugshot Modal is present
        $response->assertSee('id="mugshotModal"', false);
        $response->assertSee('id="modalMugshotImg"', false);
        $response->assertSee('id="modalSuspectName"', false);
        $response->assertSee('Custodial Front Portrait');
    }

    #[Test]
    public function digital_forensics_case_register_table_displays_suspect_front_image_and_name()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-DF-REG-001',
            'title' => 'Digital Evidence Seizure',
            'suspect_name' => 'Michael Essien',
            'suspect_alias' => 'Bison',
            'mugshot_front' => 'uploads/mugshots/essien_front.jpg',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'exhibit_category' => 'Electronic Devices / Mobile Exhibits',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Samsung Galaxy S24 Ultra',
            'dev_model' => 'Samsung Galaxy S24 Ultra',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Pieces',
        ]);

        $response = $this->actingAs($officer)->get('/Digital_Forensics');

        $response->assertStatus(200);

        // Verify Title column header is present and separate Suspect column is removed
        $response->assertSee('<th class="py-3.5 px-5">Title</th>', false);
        $response->assertDontSee('<th class="py-3.5 px-5">Suspect</th>', false);

        // Verify Suspect Name & Alias
        $response->assertSee('Michael Essien');
        $response->assertSee('Bison');

        // Verify Suspect Front Mugshot
        $response->assertSee('essien_front.jpg');
        $response->assertSee('openMugshotModal');

        // Verify modal exists
        $response->assertSee('id="mugshotModal"', false);
        $response->assertSee('Custodial Front Portrait');

        // Verify Digital & Mobile Cyber Forensics HQ is visible in the table
        $response->assertSee('Digital &amp; Mobile Cyber Forensics HQ', false);
    }
}
