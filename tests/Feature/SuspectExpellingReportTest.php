<?php

namespace Tests\Feature;

use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuspectExpellingReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lab_sidebars_and_create_pages_include_expelling_report_and_inputs(): void
    {
        $head = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staff = StaffAccount::create([
            'fullname' => 'Lab Staff Officer',
            'username' => 'labstaff_' . uniqid(),
            'service_id' => 'NACOC-LAB-' . rand(100, 999),
            'email' => 'labstaff_' . uniqid() . '@nacoc.gov.gh',
            'password' => bcrypt('password'),
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);

        $headCreate = $this->actingAs($head)->get('/Lab_Analysis/create');
        $headCreate->assertStatus(200)
            ->assertSee('Suspect Expelling Report')
            ->assertSee('/Lab_Analysis/expelling-report')
            ->assertSee('Passport Number')
            ->assertSee('Flight Number')
            ->assertSee('Destination')
            ->assertSee('Substance Expelled');

        $staffCreate = $this->actingAs($staff)->get('/Lab_Analysis/staff/create');
        $staffCreate->assertStatus(200)
            ->assertSee('Suspect Expelling Report')
            ->assertSee('/Lab_Analysis/staff/expelling-report')
            ->assertSee('Passport Number')
            ->assertSee('Flight Number')
            ->assertSee('Destination')
            ->assertSee('Substance Expelled');
    }

    #[Test]
    public function storing_case_persists_expelling_fields_and_report_renders_them(): void
    {
        $head = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $resp = $this->actingAs($head)->postJson('/cases', [
            'title' => 'Kotoka Airport Ingestion Case',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kwame Mensah',
            'passport_number' => 'G9988776',
            'flight_number' => 'BA 078',
            'destination' => 'London Heathrow (LHR)',
            'substance_expelled' => '38 Pellets Cocaine',
            'raid_location' => 'Kotoka International Airport',
            'arrest_date' => '2026-11-27',
            'holding_facility' => 'NACOC Airport Holding Cell',
        ]);

        $resp->assertStatus(200)
            ->assertJsonPath('success', true);

        $expellingUrl = $resp->json('expelling_report.url');
        $this->assertNotEmpty($expellingUrl);
        $this->assertStringContainsString('/Lab_Analysis/expelling-report/', $expellingUrl);

        $this->assertDatabaseHas('single_suspect_cases', [
            'suspect_name' => 'Kwame Mensah',
            'passport_number' => 'G9988776',
            'flight_number' => 'BA 078',
            'destination' => 'London Heathrow (LHR)',
            'substance_expelled' => '38 Pellets Cocaine',
        ]);

        $this->assertDatabaseHas('suspect_expelling_reports', [
            'suspect_name' => 'Kwame Mensah',
            'passport_number' => 'G9988776',
            'flight_number' => 'BA 078',
            'destination' => 'London Heathrow (LHR)',
            'substance_expelled' => '38 Pellets Cocaine',
            'status' => 'Generated',
        ]);

        $reportResp = $this->actingAs($head)->get('/Lab_Analysis/expelling-report');
        $reportResp->assertStatus(200)
            ->assertSee('Kwame Mensah')
            ->assertSee('G9988776')
            ->assertSee('BA 078')
            ->assertSee('London Heathrow (LHR)')
            ->assertSee('38 Pellets Cocaine');

        $pdfResp = $this->actingAs($head)->get($expellingUrl);
        $pdfResp->assertStatus(200)
            ->assertSee('NARCOTICS CONTROL COMMISSION (NACOC)')
            ->assertSee('FORENSICS DEPARTMENT')
            ->assertSee('LABORATORY ANALYSIS UNIT')
            ->assertSee('SUSPECT EXPELLING REPORT')
            ->assertSee('NACOC Watermark')
            ->assertSee('Kwame Mensah')
            ->assertSee('G9988776')
            ->assertSee('BA 078')
            ->assertSee('London Heathrow (LHR)')
            ->assertSee('38 Pellets Cocaine')
            ->assertSee('PELLETS')
            ->assertSee('SIGN. OF')
            ->assertSee('NAME OF SUPERVISING')
            ->assertSee('Total No. Expelled:')
            ->assertSee('Submitted by:')
            ->assertSee('Received by:');

        $noExpelResp = $this->actingAs($head)->postJson('/cases', [
            'title' => 'Regular Lab Case',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Yaw Boateng',
            'raid_location' => 'Tema',
            'arrest_date' => '2026-11-27',
            'holding_facility' => 'NACOC Holding Cell',
        ]);
        $noExpelResp->assertStatus(200)
            ->assertJsonPath('expelling_report', null);
    }
}

