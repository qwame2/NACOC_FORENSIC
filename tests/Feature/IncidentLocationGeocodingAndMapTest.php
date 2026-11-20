<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentLocationGeocodingAndMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_geocoding_search_returns_real_coordinates_without_mocks(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Crime Scene Support',
            'is_head' => false,
        ]);
        $this->actingAs($officer);

        // Test East Legon
        $response = $this->getJson('/api/geocode?q=East+Legon');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertStringContainsString('East Legon', $first['name']);
        $this->assertEqualsWithDelta(5.6358, (float)$first['latitude'], 0.05);
        $this->assertEqualsWithDelta(-0.1558, (float)$first['longitude'], 0.05);

        // Test Kumasi
        $responseKumasi = $this->getJson('/api/geocode?q=Kumasi');
        $responseKumasi->assertStatus(200);
        $dataKumasi = $responseKumasi->json('data');
        $this->assertNotEmpty($dataKumasi);
        $this->assertEqualsWithDelta(6.6885, (float)$dataKumasi[0]['latitude'], 0.05);
        $this->assertEqualsWithDelta(-1.6244, (float)$dataKumasi[0]['longitude'], 0.05);

        // Test Aflao Border
        $responseAflao = $this->getJson('/api/geocode?q=Aflao');
        $responseAflao->assertStatus(200);
        $dataAflao = $responseAflao->json('data');
        $this->assertNotEmpty($dataAflao);
        $this->assertEqualsWithDelta(6.1190, (float)$dataAflao[0]['latitude'], 0.05);
        $this->assertEqualsWithDelta(1.1904, (float)$dataAflao[0]['longitude'], 0.05);
    }

    public function test_geocoding_reverse_endpoint_returns_real_location(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Crime Scene Support',
            'is_head' => false,
        ]);
        $this->actingAs($officer);

        $response = $this->getJson('/api/geocode/reverse?lat=5.6358&lng=-0.1558');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('display_name', $data);
        $this->assertEqualsWithDelta(5.6358, (float)$data['latitude'], 0.005);
        $this->assertEqualsWithDelta(-0.1558, (float)$data['longitude'], 0.005);
    }

    public function test_all_ten_create_pages_contain_incident_geocoding_and_coordinate_inputs(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Crime Scene Support',
            'is_head' => false,
        ]);
        $this->actingAs($officer);

        $views = [
            'cases.create' => ['user' => $officer],
            'Crime_Scene_Support.head.CrimeSceneCreate' => ['user' => $officer],
            'Crime_Scene_Support.staff.create' => ['user' => $officer],
            'Digital_Forensics.create' => ['user' => $officer],
            'Digital_Forensics.staff.create' => ['user' => $officer],
            'Evidence_Management.create' => ['user' => $officer],
            'Evidence_Management.staff.create' => ['user' => $officer],
            'Forensic_Head.staff.create' => ['user' => $officer],
            'Lab_Analysis.create' => ['user' => $officer],
            'Lab_Analysis.staff.create' => ['user' => $officer],
        ];

        foreach ($views as $viewName => $data) {
            $rendered = view($viewName, $data)->render();

            $this->assertStringContainsString('id="newArrestedRaidLocation"', $rendered, "Raid location input missing in {$viewName}");
            $this->assertStringNotContainsString('id="incidentLocationMap"', $rendered, "Bulky incident map canvas should be safely removed in {$viewName}");
            $this->assertStringContainsString('id="incidentLatitude"', $rendered, "Latitude input missing in {$viewName}");
            $this->assertStringContainsString('id="incidentLongitude"', $rendered, "Longitude input missing in {$viewName}");
            $this->assertStringContainsString('id="incidentGpsBadge"', $rendered, "GPS badge missing in {$viewName}");
            $this->assertStringContainsString('id="clearIncidentPinBtn"', $rendered, "Clear pin button missing in {$viewName}");
            $this->assertStringContainsString('PLACE OF ARREST', $rendered, "PLACE OF ARREST label missing in {$viewName}");
            $this->assertStringNotContainsString('Place of Incident', $rendered, "Place of Incident should be renamed to PLACE OF ARREST in {$viewName}");
            $this->assertStringContainsString('GPS Radar:', $rendered, "GPS Radar badge label missing in {$viewName}");
        }
    }

    public function test_storing_single_case_with_latitude_and_longitude_persists_in_database(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Crime Scene Support',
            'is_head' => true,
        ]);
        $this->actingAs($officer);

        $payload = [
            'case_number'         => 'NCC-GEO-SINGLE-' . rand(1000, 9999),
            'title'               => 'Republic vs Location Test Suspect',
            'unit_name'           => 'Crime Scene Support',
            'suspect_name'        => 'Kofi Mensah',
            'raid_location'       => 'East Legon Sector 4, Accra',
            'latitude'            => 5.6358000,
            'longitude'           => -0.1558000,
            'offense_description' => 'Possession of illicit narcotics',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '14:30',
            'holding_facility'    => 'NACOC Holding Facility',
            'suspect_docket_mode' => 'SINGLE',
            'has_seizures'        => false,
        ];

        $response = $this->postJson('/cases', $payload);
        $response->assertSuccessful();

        $createdCaseNumber = $response->json('case.case_number') ?? $payload['case_number'];
        $case = SingleSuspectCase::where('case_number', $createdCaseNumber)->first()
            ?? SingleSuspectCase::find($response->json('case.id'));

        $this->assertNotNull($case, 'Single suspect case should be created in database');
        $this->assertEquals('East Legon Sector 4, Accra', $case->raid_location);
        $this->assertNotNull($case->latitude);
        $this->assertNotNull($case->longitude);
        $this->assertEqualsWithDelta(5.6358, (float)$case->latitude, 0.0001);
        $this->assertEqualsWithDelta(-0.1558, (float)$case->longitude, 0.0001);
    }

    public function test_storing_multiple_suspects_case_with_latitude_and_longitude_persists(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Crime Scene Support',
            'is_head' => true,
        ]);
        $this->actingAs($officer);

        $caseNum = 'NCC-GEO-MULTI-' . rand(1000, 9999);
        $payload = [
            'case_number'         => $caseNum,
            'title'               => 'Republic vs Multiple Targets',
            'unit_name'           => 'Crime Scene Support',
            'suspect_name'        => 'Primary Target',
            'raid_location'       => 'Kumasi Central Market',
            'latitude'            => 6.6885000,
            'longitude'           => -1.6244000,
            'offense_description' => 'Joint distribution operation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '10:00',
            'holding_facility'    => 'Kumasi Central Station',
            'suspect_docket_mode' => 'MULTIPLE',
            'docket_data'         => [
                'suspects' => [
                    ['suspect_name' => 'Target Alpha', 'role' => 'Primary Suspect', 'raid_location' => 'Kumasi Central Market'],
                    ['suspect_name' => 'Target Beta', 'role' => 'Associate', 'raid_location' => 'Kumasi Central Market'],
                ]
            ],
            'has_seizures'        => false,
        ];

        $response = $this->postJson('/cases', $payload);
        $response->assertSuccessful();

        $createdCaseNumber = $response->json('case.case_number') ?? $caseNum;
        $multiCase = MultipleSuspectCase::where('case_number', $createdCaseNumber)->first()
            ?? MultipleSuspectCase::find($response->json('case.id'));

        $this->assertNotNull($multiCase, 'Multiple suspect case should be created in database');
        $this->assertEqualsWithDelta(6.6885, (float)$multiCase->latitude, 0.0001);
        $this->assertEqualsWithDelta(-1.6244, (float)$multiCase->longitude, 0.0001);

        $suspects = CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->get();
        $this->assertCount(2, $suspects);
        foreach ($suspects as $sp) {
            $this->assertEqualsWithDelta(6.6885, (float)$sp->latitude, 0.0001);
            $this->assertEqualsWithDelta(-1.6244, (float)$sp->longitude, 0.0001);
        }
    }

    public function test_dashboard_displays_incident_hotspots_scoreboard_and_chart(): void
    {
        $officer = User::factory()->create([
            'unit'     => 'Forensic Head',
            'is_head'  => true,
            'fullname' => 'Director General Forensic',
        ]);
        $this->actingAs($officer);

        // Seed 2 cases in East Legon and 1 in Kejetia Kumasi
        SingleSuspectCase::create([
            'case_number'         => 'NCC-TEST-001',
            'title'               => 'East Legon Seizure 1',
            'unit_name'           => 'Crime Scene Support',
            'user_id'             => $officer->id,
            'suspect_name'        => 'Target Alpha',
            'raid_location'       => 'East Legon Phase 1, Accra',
            'latitude'            => 5.6358,
            'longitude'           => -0.1558,
            'priority'            => 'CRITICAL',
            'status'              => 'Under Investigation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '10:00',
            'offense_description' => 'Test offense',
        ]);

        SingleSuspectCase::create([
            'case_number'         => 'NCC-TEST-002',
            'title'               => 'East Legon Seizure 2',
            'unit_name'           => 'Digital Forensics',
            'user_id'             => $officer->id,
            'suspect_name'        => 'Target Beta',
            'raid_location'       => 'East Legon Phase 2, Accra',
            'latitude'            => 5.6372,
            'longitude'           => -0.1542,
            'priority'            => 'HIGH',
            'status'              => 'Under Investigation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '11:00',
            'offense_description' => 'Test offense',
        ]);

        SingleSuspectCase::create([
            'case_number'         => 'NCC-TEST-003',
            'title'               => 'Kumasi Seizure',
            'unit_name'           => 'Lab Analysis',
            'user_id'             => $officer->id,
            'suspect_name'        => 'Target Gamma',
            'raid_location'       => 'Kejetia Market, Kumasi',
            'latitude'            => 6.6975,
            'longitude'           => -1.6225,
            'priority'            => 'HIGH',
            'status'              => 'Under Investigation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '12:00',
            'offense_description' => 'Test offense',
        ]);

        $response = $this->get('/Forensic_Head');
        $response->assertStatus(200);

        // Verify Scoreboard card and chart elements
        $response->assertSee('Incident Hotspots Scoreboard');
        $response->assertSee('incident-scoreboard-card');
        $response->assertSee('incidentLocationScoreboardChart');
        $response->assertDontSee('Case Concentration by Location');
        $response->assertSee('Click location to pinpoint');
        $response->assertSee('Click any location bar to pinpoint on map');

        // Verify removed sections
        $response->assertDontSee('Incident Leaderboard (Ranked)');
        $response->assertDontSee('Locations with highest case density & concentration');
        $response->assertDontSee('scoreboardHotspotsList');
    }

    public function test_realtime_api_returns_top_incident_locations_data(): void
    {
        $officer = User::factory()->create([
            'unit'     => 'Forensic Head',
            'is_head'  => true,
            'fullname' => 'Director General Forensic',
        ]);
        $this->actingAs($officer);

        SingleSuspectCase::create([
            'case_number'         => 'NCC-TEST-RT-01',
            'title'               => 'East Legon Raid 1',
            'unit_name'           => 'Crime Scene Support',
            'user_id'             => $officer->id,
            'suspect_name'        => 'Target Alpha',
            'raid_location'       => 'East Legon, Accra',
            'latitude'            => 5.6358,
            'longitude'           => -0.1558,
            'priority'            => 'CRITICAL',
            'status'              => 'Under Investigation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '10:00',
            'offense_description' => 'Test offense',
        ]);

        SingleSuspectCase::create([
            'case_number'         => 'NCC-TEST-RT-02',
            'title'               => 'East Legon Raid 2',
            'unit_name'           => 'Digital Forensics',
            'user_id'             => $officer->id,
            'suspect_name'        => 'Target Beta',
            'raid_location'       => 'East Legon Sector 4, Accra',
            'latitude'            => 5.6360,
            'longitude'           => -0.1550,
            'priority'            => 'HIGH',
            'status'              => 'Under Investigation',
            'arrest_date'         => now()->format('Y-m-d'),
            'arrest_time'         => '11:00',
            'offense_description' => 'Test offense',
        ]);

        $response = $this->getJson('/api/dashboard/realtime');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $topLocations = $response->json('top_incident_locations');
        $this->assertIsArray($topLocations);
        $this->assertNotEmpty($topLocations);

        $leader = $topLocations[0];
        $this->assertEquals('East Legon, Accra', $leader['name']);
        $this->assertEquals(1, $leader['rank']);
        $this->assertEquals(2, $leader['cases_count']);
        $this->assertEqualsWithDelta(5.6358, (float)$leader['latitude'], 0.05);
        $this->assertEqualsWithDelta(-0.1558, (float)$leader['longitude'], 0.05);
    }
}
