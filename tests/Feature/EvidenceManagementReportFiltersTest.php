<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceManagementReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);
    }

    public function test_report_page_renders_with_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/Evidence_Management/report');
        $response->assertStatus(200);
        $response->assertSee('Audit Report Filter Parameters');
        $response->assertSee('Search Exhibit / Case / Suspect');
        $response->assertSee('Category');
        $response->assertSee('Vault Status');
        $response->assertSee('From:');
        $response->assertSee('To:');
        $response->assertSee('Sort:');
        $response->assertSee('Show:');
    }

    public function test_search_filter_finds_exhibit_without_sql_error(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'CASE-EMU-9901',
            'suspect_name' => 'Kwame Mensah',
            'created_at' => now(),
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-SPEC-7788',
            'commodity_name' => 'Suspected High-Grade Cocaine',
            'exhibit_category' => 'Drugs',
            'seizure_packaging' => 'Heat-Sealed Transparent Plastic',
            'property_id' => 'NACOC/EMU/99911',
        ]);

        Exhibit::create([
            'exhibit_number' => 'EXH-OTHER-0001',
            'commodity_name' => 'Samsung Galaxy Phone',
            'exhibit_category' => 'Electronic Devices',
            'property_id' => null,
        ]);

        // Search by exhibit number
        $resp1 = $this->actingAs($this->user)->get('/Evidence_Management/report?search=7788');
        $resp1->assertStatus(200);
        $resp1->assertSee('EXH-SPEC-7788');
        $resp1->assertDontSee('EXH-OTHER-0001');

        // Search by commodity name
        $resp2 = $this->actingAs($this->user)->get('/Evidence_Management/report?search=Cocaine');
        $resp2->assertStatus(200);
        $resp2->assertSee('EXH-SPEC-7788');
        $resp2->assertDontSee('EXH-OTHER-0001');

        // Search by property ID
        $resp3 = $this->actingAs($this->user)->get('/Evidence_Management/report?search=99911');
        $resp3->assertStatus(200);
        $resp3->assertSee('EXH-SPEC-7788');
        $resp3->assertDontSee('EXH-OTHER-0001');

        // Search by packaging (seizure_packaging)
        $resp4 = $this->actingAs($this->user)->get('/Evidence_Management/report?search=Heat-Sealed');
        $resp4->assertStatus(200);
        $resp4->assertSee('EXH-SPEC-7788');
        $resp4->assertDontSee('EXH-OTHER-0001');

        // Search by suspect name
        $resp5 = $this->actingAs($this->user)->get('/Evidence_Management/report?search=Kwame');
        $resp5->assertStatus(200);
        $resp5->assertSee('EXH-SPEC-7788');
        $resp5->assertDontSee('EXH-OTHER-0001');
    }

    public function test_category_filter_restricts_results(): void
    {
        Exhibit::create([
            'exhibit_number' => 'EXH-DRUG-01',
            'commodity_name' => 'Cannabis Sativa',
            'exhibit_category' => 'Drugs',
        ]);

        Exhibit::create([
            'exhibit_number' => 'EXH-DEV-01',
            'commodity_name' => 'Apple iPhone 15 Pro',
            'exhibit_category' => 'Electronic Devices',
            'dev_model' => 'iPhone 15 Pro',
        ]);

        Exhibit::create([
            'exhibit_number' => 'EXH-VEH-01',
            'commodity_name' => 'Toyota Land Cruiser',
            'exhibit_category' => 'Vehicles',
            'veh_make' => 'Toyota',
        ]);

        // Filter by drug
        $respDrug = $this->actingAs($this->user)->get('/Evidence_Management/report?category=drug');
        $respDrug->assertStatus(200);
        $respDrug->assertSee('EXH-DRUG-01');
        $respDrug->assertDontSee('EXH-DEV-01');
        $respDrug->assertDontSee('EXH-VEH-01');

        // Filter by device
        $respDev = $this->actingAs($this->user)->get('/Evidence_Management/report?category=device');
        $respDev->assertStatus(200);
        $respDev->assertSee('EXH-DEV-01');
        $respDev->assertDontSee('EXH-DRUG-01');
        $respDev->assertDontSee('EXH-VEH-01');

        // Filter by vehicle
        $respVeh = $this->actingAs($this->user)->get('/Evidence_Management/report?category=vehicle');
        $respVeh->assertStatus(200);
        $respVeh->assertSee('EXH-VEH-01');
        $respVeh->assertDontSee('EXH-DRUG-01');
        $respVeh->assertDontSee('EXH-DEV-01');
    }

    public function test_action_status_filter_restricts_results(): void
    {
        // Completed: has property_id and classification
        Exhibit::create([
            'exhibit_number' => 'EXH-COMPLETED-01',
            'commodity_name' => 'Classified Item',
            'property_id' => 'NACOC/EMU/11111',
            'exhibit_type' => 'Narcotic Exhibit',
            'drug_classification' => 'Narcotic Schedule 1',
        ]);

        // Pending: missing property_id
        Exhibit::create([
            'exhibit_number' => 'EXH-PENDING-01',
            'commodity_name' => 'Pending Item',
            'property_id' => null,
            'drug_classification' => null,
            'exhibit_type' => null,
        ]);

        // Filter completed
        $respComp = $this->actingAs($this->user)->get('/Evidence_Management/report?action_status=completed');
        $respComp->assertStatus(200);
        $respComp->assertSee('EXH-COMPLETED-01');
        $respComp->assertDontSee('EXH-PENDING-01');

        // Filter pending
        $respPend = $this->actingAs($this->user)->get('/Evidence_Management/report?action_status=pending');
        $respPend->assertStatus(200);
        $respPend->assertSee('EXH-PENDING-01');
        $respPend->assertDontSee('EXH-COMPLETED-01');
    }

    public function test_date_range_and_sort_filters(): void
    {
        Exhibit::create([
            'exhibit_number' => 'EXH-DATE-OCT',
            'commodity_name' => 'October Exhibit',
            'seizure_date' => '2026-10-15',
            'seizure_value' => '1000.00',
        ]);

        Exhibit::create([
            'exhibit_number' => 'EXH-DATE-NOV',
            'commodity_name' => 'November Exhibit',
            'seizure_date' => '2026-11-05',
            'seizure_value' => '50000.00',
        ]);

        // Filter date range strictly October
        $respOct = $this->actingAs($this->user)->get('/Evidence_Management/report?from_date=2026-10-01&to_date=2026-10-31');
        $respOct->assertStatus(200);
        $respOct->assertSee('EXH-DATE-OCT');
        $respOct->assertDontSee('EXH-DATE-NOV');

        // Sort by value descending
        $respSort = $this->actingAs($this->user)->get('/Evidence_Management/report?sort=value_desc');
        $respSort->assertStatus(200);
        $respSort->assertSee('EXH-DATE-NOV');
        $respSort->assertSee('EXH-DATE-OCT');
    }
}
