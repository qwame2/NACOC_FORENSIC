<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function all_unit_and_executive_dashboards_render_empty_map_container_and_forensic_workspace(): void
    {
        $director = User::factory()->create([
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $routes = [
            '/Forensic_Head'          => 'Forensic Cases',
            '/Crime_Scene_Support/head' => 'Field Seizures',
            '/Digital_Forensics'      => 'Cyber &amp; Device Cases',
            '/Evidence_Management'    => 'Custody &amp; Vault Cases',
            '/Lab_Analysis'           => 'Lab Analysis Cases',
        ];

        foreach ($routes as $uri => $locationNoun) {
            $resp = $this->actingAs($director)->get($uri);
            $resp->assertOk();
            $resp->assertSee('id="forensicMapContainer"', false);
            $resp->assertSee('data-map-api-slot="pending"', false);
            $resp->assertSee($locationNoun, false);
            $resp->assertSee('by Location');
            $resp->assertSee('Avg Turnaround');
            $resp->assertSee('Exhibits Seized');
            $resp->assertSee('Forensic');
            $resp->assertSee('Stats');

            // Old SVG map & mockup finance copy must not be present
            $resp->assertDontSee('ghanaMapSvg');
            $resp->assertDontSee('tacGhanaMapSvg');
            $resp->assertDontSee('Trishul');
            $resp->assertDontSee('Day of Supply');
            $resp->assertDontSee('Service Level');
            $resp->assertDontSee('—', false);
        }
    }

    #[Test]
    public function realtime_telemetry_endpoint_returns_real_forensic_metrics_and_breakdowns(): void
    {
        $officer = User::factory()->create([
            'unit'        => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'   => 'NCC-DASH-2026-01',
            'title'         => 'Tema Port Intercept',
            'unit_name'     => 'Lab Analysis',
            'suspect_name'  => 'Kwame Mensah',
            'status'        => 'Under Investigation',
            'case_status'   => 'Under Investigation',
            'priority'      => 'CRITICAL',
            'raid_location' => 'Tema Harbour Sector',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'case_type'              => 'single',
            'exhibit_number'         => 'EXH-DASH-01',
            'commodity_name'         => 'Cannabis Resin',
            'exhibit_category'       => 'Drug',
        ]);

        $jsonResp = $this->actingAs($officer)->getJson('/api/dashboard/realtime');
        $jsonResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('metrics.total_cases', 1)
            ->assertJsonPath('metrics.in_progress_cases', 1)
            ->assertJsonPath('metrics.total_exhibits', 1)
            ->assertJsonPath('breakdowns.severity.high', 1)
            ->assertJsonPath('cases.0.case_number', 'NCC-DASH-2026-01')
            ->assertJsonPath('cases.0.region', 'Greater Accra')
            ->assertJsonPath('cases.0.exhibits_count', 1);

        $data = $jsonResp->json();
        $this->assertCount(6, $data['chart_data']['months']);
        $this->assertCount(6, $data['chart_data']['monthly_bars']);
        $this->assertSame(1, end($data['chart_data']['monthly_bars']));
        $this->assertSame(1, end($data['chart_data']['monthly_exhibits']));
    }
}

