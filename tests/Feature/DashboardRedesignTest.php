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
    public function all_unit_and_executive_dashboards_render_leaflet_map_and_forensic_workspace(): void
    {
        $director = User::factory()->create([
            'unit'        => 'Forensic Directorate',
            'is_head'     => true,
            'is_approved' => true,
        ]);

        $routes = [
            '/Forensic_Head'            => 'Forensic Cases',
            '/Crime_Scene_Support/head' => 'Field Seizures',
            '/Digital_Forensics'        => 'Cases',
            '/Evidence_Management'      => 'Custody &amp; Vault Cases',
            '/Lab_Analysis'             => 'Lab Analysis Cases',
        ];

        foreach ($routes as $uri => $locationNoun) {
            $resp = $this->actingAs($director)->get($uri);
            $resp->assertOk();
            $resp->assertSee('id="forensicMapContainer"', false);
            $resp->assertSee('data-leaflet-map="true"', false);
            $resp->assertSee('vendor/leaflet/leaflet.css', false);
            $resp->assertSee('vendor/leaflet/leaflet.js', false);
            $resp->assertSee('vendor/chartjs/chart.umd.min.js', false);
            $resp->assertSee('vendor/lucide.min.js', false);
            $resp->assertDontSee('unpkg.com', false);
            $resp->assertDontSee('cdn.jsdelivr.net', false);
            $resp->assertSee('js/forensic-leaflet-map.js', false);
            $resp->assertSee('openstreetmap.org/copyright', false);
            $resp->assertSee($locationNoun, false);
            $resp->assertSee('geospatial view');
            $resp->assertSee('Registered Dockets');
            $resp->assertSee('Forensic');
            $resp->assertSee('stats');

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
    public function dashboard_passes_real_database_markers_to_leaflet_component_and_realtime_api(): void
    {
        $officer = User::factory()->head()->create([
            'unit'        => 'Forensic Directorate',
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

        // Controller-passed markers on the dashboard view
        $dashResp = $this->actingAs($officer)->get('/Forensic_Head');
        $dashResp->assertOk()
            ->assertSee('NCC-DASH-2026-01')
            ->assertSee('Tema Harbour Sector')
            ->assertSee('5.6037');

        // Realtime telemetry endpoint
        $jsonResp = $this->actingAs($officer)->getJson('/api/dashboard/realtime');
        $jsonResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('metrics.total_cases', 1)
            ->assertJsonPath('metrics.in_progress_cases', 1)
            ->assertJsonPath('metrics.total_exhibits', 1)
            ->assertJsonPath('breakdowns.severity.high', 1)
            ->assertJsonPath('cases.0.case_number', 'NCC-DASH-2026-01')
            ->assertJsonPath('cases.0.region', 'Greater Accra')
            ->assertJsonPath('cases.0.latitude', 5.6037)
            ->assertJsonPath('cases.0.longitude', -0.187)
            ->assertJsonPath('cases.0.exhibits_count', 1);

        $data = $jsonResp->json();
        $this->assertCount(6, $data['chart_data']['months']);
        $this->assertCount(6, $data['chart_data']['monthly_bars']);
        $this->assertSame(1, end($data['chart_data']['monthly_bars']));
        $this->assertSame(1, end($data['chart_data']['monthly_exhibits']));
    }

    #[Test]
    public function all_database_case_locations_are_passed_to_map_and_high_cases_use_red_marker(): void
    {
        $officer = User::factory()->head()->create([
            'unit'        => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        SingleSuspectCase::create([
            'case_number'   => 'NCC-LOC-01',
            'title'         => 'Kumasi Depot Seizure',
            'unit_name'     => 'Crime Scene Support',
            'suspect_name'  => 'Yaw Boateng',
            'status'        => 'Under Investigation',
            'case_status'   => 'Under Investigation',
            'priority'      => 'HIGH',
            'raid_location' => 'Kumasi Central Market',
        ]);

        SingleSuspectCase::create([
            'case_number'   => 'NCC-LOC-02',
            'title'         => 'Aflao Border Intercept',
            'unit_name'     => 'Evidence Management',
            'suspect_name'  => 'Kofi Mensah',
            'status'        => 'Under Investigation',
            'case_status'   => 'Under Investigation',
            'priority'      => 'LOW',
            'raid_location' => 'Aflao Border Post',
        ]);

        SingleSuspectCase::create([
            'case_number'   => 'NCC-UNMAPPED-03',
            'title'         => 'Unmapped Warehouse Intercept',
            'unit_name'     => 'Lab Analysis',
            'suspect_name'  => 'John Doe',
            'status'        => 'Under Investigation',
            'case_status'   => 'Under Investigation',
            'priority'      => 'MEDIUM',
            'raid_location' => 'Secret Warehouse Sector',
        ]);

        $routes = [
            '/Forensic_Head',
            '/Crime_Scene_Support/head',
            '/Digital_Forensics',
            '/Evidence_Management',
            '/Lab_Analysis',
        ];

        foreach ($routes as $uri) {
            $dashResp = $this->actingAs($officer)->get($uri);
            $dashResp->assertOk()
                ->assertSee('NCC-LOC-01')
                ->assertSee('NCC-LOC-02')
                ->assertDontSee('NCC-UNMAPPED-03')
                ->assertSee('6.6885')
                ->assertSee('6.119')
                ->assertSee('High / Critical &amp; High-Case Area', false)
                ->assertSee('ctrl.setMarkers(mappedCases.map(', false);
        }

        $jsonResp = $this->actingAs($officer)->getJson('/api/dashboard/realtime');
        $jsonResp->assertOk();
        $unmapped = collect($jsonResp->json('cases'))->firstWhere('case_number', 'NCC-UNMAPPED-03');
        $this->assertNotNull($unmapped);
        $this->assertNull($unmapped['latitude']);
        $this->assertNull($unmapped['longitude']);

        $js = file_get_contents(public_path('js/forensic-leaflet-map.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString("p === 'CRITICAL' || p === 'HIGH' || Number(locationCount || 0) >= 3", $js);
        $this->assertStringContainsString("return '#dc2626';", $js);
    }
}
