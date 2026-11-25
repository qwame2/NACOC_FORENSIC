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

        $response = $this->actingAs($officer)->get('/Digital_Forensics/cases');

        $response->assertStatus(200);

        // Verify Title column header is present and separate Suspect and Status columns are removed
        $response->assertSee('<th class="py-3.5 px-5">Title</th>', false);
        $response->assertDontSee('<th class="py-3.5 px-5">Suspect</th>', false);
        $response->assertDontSee('<th class="py-3.5 px-5">Status</th>', false);

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

    #[Test]
    public function digital_forensics_case_register_table_strictly_displays_user_associated_to_electronic_devices()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        // 1. Multi-suspect case: Suspect Alpha has drug exhibit, Suspect Beta has iPhone exhibit
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NACOC-DF-MULTI-001',
            'title' => 'Operation Cyber Syndicate',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspectAlpha = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwame Alpha',
            'suspect_alias' => 'Kingpin',
            'mugshot_front' => 'uploads/mugshots/alpha_front.jpg',
            'suspect_disposition' => 'Under Investigation',
        ]);

        $suspectBeta = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Kofi Beta',
            'suspect_alias' => 'TechUser',
            'mugshot_front' => 'uploads/mugshots/beta_front.jpg',
            'suspect_disposition' => 'Cleared — Released from Custody',
        ]);

        // Exhibit 1: Narcotic Drug associated with Suspect Alpha
        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectAlpha->id,
            'associated_person' => 'Kwame Alpha',
            'exhibit_category' => 'Narcotic Drugs',
            'commodity_name' => 'Cocaine Brick',
            'seizure_quantity' => 1,
            'seizure_unit' => 'kg',
        ]);

        // Exhibit 2: Electronic Device associated with Suspect Beta
        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectBeta->id,
            'associated_person' => 'Kofi Beta',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Apple iPhone 15 Pro Max',
            'dev_model' => 'Apple iPhone 15 Pro Max',
            'dev_imei1' => '359876543210987',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Pieces',
        ]);

        // 2. Pure Drug case: only has drugs, zero electronic devices
        $pureDrugCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-DRUG-ONLY-999',
            'title' => 'Pure Narcotic Seizure',
            'suspect_name' => 'Pure Drug Suspect',
            'unit_name' => 'Narcotics Unit',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'exhibit_category' => 'Narcotic Drugs',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $pureDrugCase->id,
            'exhibit_category' => 'Narcotic Drugs',
            'commodity_name' => 'Heroin Brown Sugar',
            'seizure_quantity' => 5,
            'seizure_unit' => 'kg',
        ]);

        // Access the Digital Forensics Case Register
        $response = $this->actingAs($officer)->get('/Digital_Forensics/cases');

        $response->assertStatus(200);

        // Header check: User Disposition header is displayed
        $response->assertSee('User Disposition');

        // Multi-suspect case is visible
        $response->assertSee('NACOC-DF-MULTI-001');

        // STRICTLY DISPLAY USER ASSOCIATED TO ELECTRONIC DEVICES:
        // Kofi Beta (TechUser) owns the iPhone: MUST be visible as User
        $response->assertSee('User:');
        $response->assertSee('Kofi Beta');
        $response->assertSee('TechUser');
        $response->assertSee('beta_front.jpg');
        $response->assertSee('Cleared');

        // Kwame Alpha only has drugs: MUST NOT appear as User in the register table
        $response->assertDontSee('Kwame Alpha');
        $response->assertDontSee('alpha_front.jpg');

        // Pure drug case has no electronic devices: MUST NOT appear in the Digital Forensics Case Register
        $response->assertDontSee('NACOC-DRUG-ONLY-999');
        $response->assertDontSee('Pure Drug Suspect');
    }

    #[Test]
    public function sidebar_does_not_render_report_button_on_digital_forensics_pages(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        // Digital Forensics pages MUST NOT render the Report button in the sidebar
        $respCases = $this->actingAs($officer)->get('/Digital_Forensics/cases');
        $respCases->assertStatus(200);
        $respCases->assertDontSee('<span class="truncate">Report</span>', false);

        $respCreate = $this->actingAs($officer)->get('/Digital_Forensics/create');
        $respCreate->assertStatus(200);
        $respCreate->assertDontSee('<span class="truncate">Report</span>', false);

        $respStatus = $this->actingAs($officer)->get('/Digital_Forensics/forensic-status');
        $respStatus->assertStatus(200);
        $respStatus->assertDontSee('<span class="truncate">Report</span>', false);

        // Other units MUST still retain their sidebar Report button
        $respLab = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $respLab->assertStatus(200);
        $respLab->assertSee('<span class="truncate">Report</span>', false);

        $respCss = $this->actingAs($officer)->get('/Crime_Scene_Support/head/create');
        $respCss->assertStatus(200);
        $respCss->assertSee('<span class="truncate">Report</span>', false);
    }

    #[Test]
    public function sidebar_renders_investigation_stat_and_report_for_digital_forensics(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $resp = $this->actingAs($officer)->get('/Digital_Forensics/cases');
        $resp->assertStatus(200);
        $resp->assertSee('Investigation Stat &amp; Report', false);
        $resp->assertDontSee('Forensic &amp; Investigation Stat', false);
    }
}
