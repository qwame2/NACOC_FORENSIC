<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\Exhibit;
use App\Services\CaseDocketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvestigatingOfficerPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function investigating_officer_is_persisted_in_exhibits_table_via_forensic_case_create()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Electronic Devices',
                'seizure_name' => 'iPhone 15 Pro Max',
                'seizure_officer' => 'Insp. John Mensah (SD-100)',
                'investigating_officer' => 'Det. Insp. Emmanuel Mensah / Service ID SD-0199',
                'seizure_location' => 'Tema Port Gate 4',
                'seizure_reason' => 'Suspected trafficking',
            ],
            [
                'exhibit_number' => '2',
                'category' => 'Drugs',
                'seizure_name' => 'Cocaine',
                'seizure_officer' => 'Insp. Kwame Boateng (SD-200)',
                'investigating_officer' => 'Chief Insp. Sarah Osei / SD-0344',
                'seizure_location' => 'Tema Port Gate 4',
                'seizure_reason' => 'Illicit substance',
            ]
        ];

        $case = ForensicCase::create([
            'unit_name' => 'Crime Scene Support',
            'title' => 'Republic vs Kofi Mensah',
            'suspect_name' => 'Kofi Mensah',
            'investigating_officer' => 'Det. Insp. Emmanuel Mensah / Service ID SD-0199',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kofi Mensah', 'role' => 'Primary Suspect'],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($case);

        // Check exhibits table in DB
        $exhibits = Exhibit::where('commodity_name', 'iPhone 15 Pro Max')->get();
        $this->assertNotEmpty($exhibits);
        $this->assertEquals('Det. Insp. Emmanuel Mensah / Service ID SD-0199', $exhibits->first()->investigating_officer);

        $drugExhibits = Exhibit::where('commodity_name', 'Cocaine')->get();
        $this->assertNotEmpty($drugExhibits);
        $this->assertEquals('Chief Insp. Sarah Osei / SD-0344', $drugExhibits->first()->investigating_officer);
    }

    #[Test]
    public function investigating_officer_is_persisted_in_exhibits_table_via_case_docket_service_store()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
        ]);
        $this->actingAs($user);

        $requestData = [
            'unit_name' => 'Crime Scene Support',
            'title' => 'Republic vs Akwasi Boakye',
            'suspect_name' => 'Akwasi Boakye',
            'arrest_date' => now()->toDateString(),
            'seizure_name' => 'Toyota Hilux',
            'exhibit_category' => 'Vehicles',
            'seizure_officer' => 'Officer Evans (SD-404)',
            'investigating_officer' => 'Insp. Nana Yaw / SD-0899',
            'seizure_reason' => 'Vehicle used in crime',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Akwasi Boakye', 'role' => 'Primary Suspect'],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Vehicles',
                        'seizure_name' => 'Toyota Hilux',
                        'seizure_officer' => 'Officer Evans (SD-404)',
                        'investigating_officer' => 'Insp. Nana Yaw / SD-0899',
                        'seizure_reason' => 'Vehicle used in crime',
                    ]
                ],
            ],
        ];

        $request = Request::create('/cases', 'POST', $requestData);
        $service = app(CaseDocketService::class);
        $result = $service->storeCase($request);

        $this->assertTrue($result['success']);
        $case = $result['case'];

        $exhibit = Exhibit::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->first();

        $this->assertNotNull($exhibit);
        $this->assertEquals('Insp. Nana Yaw / SD-0899', $exhibit->investigating_officer);
    }

    #[Test]
    public function create_and_review_views_contain_investigating_officer_input()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('cases.create'));
        $response->assertStatus(200);
        $response->assertSee('Investigating Officer');
        $response->assertSee('newSeizureInvestigatingOfficer');
    }
}
