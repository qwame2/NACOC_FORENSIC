<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabAnalysisEditCaseTabTest extends TestCase
{
    use RefreshDatabase;

    private ForensicCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->case = ForensicCase::create([
            'case_number' => 'NFC-2026-8801',
            'title' => 'Republic vs Test Suspect',
            'suspect_name' => 'Test Suspect',
            'unit_name' => 'Crime Scene Support',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Test Suspect']
                ],
                'seizures' => [
                    ['seizure_name_drug' => 'Suspected Heroin', 'weight' => '5.2 kg']
                ],
                'lab_examination' => [
                    'laboratory_number' => 'LAB-2026-99',
                    'confirmatory_test_result' => 'YES',
                    'substance_identification' => 'Heroin Hydrochloride'
                ]
            ]
        ]);
    }

    #[Test]
    public function lab_chemical_results_tab_shows_when_accessed_with_lab_analysis_from_query(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_head' => true,
        ]);

        $response = $this->actingAs($user)->get("/cases/{$this->case->case_number}/edit?from=Lab_Analysis");

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('id="editTabBtn3"', $html);
        $this->assertStringContainsString('3. Laboratory Chemical Results', $html);
        $this->assertStringContainsString('id="editTabContent3"', $html);
    }

    #[Test]
    public function lab_chemical_results_tab_shows_for_lab_analysis_officer_without_query_param(): void
    {
        $labUser = User::factory()->create([
            'unit' => 'Laboratory Analysis (LAU)',
            'is_head' => false,
        ]);

        $response = $this->actingAs($labUser)->get("/cases/{$this->case->case_number}/edit");

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('id="editTabBtn3"', $html);
        $this->assertStringContainsString('3. Laboratory Chemical Results', $html);
        $this->assertStringContainsString('id="editTabContent3"', $html);
    }

    #[Test]
    public function lab_chemical_results_tab_is_hidden_when_accessed_from_crime_scene_support(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
        ]);

        $response = $this->actingAs($user)->get("/cases/{$this->case->case_number}/edit?from=Crime_Scene_Support");

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringNotContainsString('id="editTabBtn3"', $html);
        $this->assertStringNotContainsString('3. Laboratory Chemical Results', $html);
        $this->assertStringNotContainsString('id="editTabContent3"', $html);
    }

    #[Test]
    public function lab_chemical_results_tab_is_hidden_for_crime_scene_officer_without_query_param(): void
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
        ]);

        $response = $this->actingAs($user)->get("/cases/{$this->case->case_number}/edit");

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringNotContainsString('id="editTabBtn3"', $html);
        $this->assertStringNotContainsString('3. Laboratory Chemical Results', $html);
        $this->assertStringNotContainsString('id="editTabContent3"', $html);
    }

    #[Test]
    public function lab_chemical_results_tab_is_hidden_for_digital_forensics_and_evidence_management(): void
    {
        $dfUser = User::factory()->create([
            'unit' => 'Digital Forensics',
            'is_head' => false,
        ]);

        $dfResponse = $this->actingAs($dfUser)->get("/cases/{$this->case->case_number}/edit?from=Digital_Forensics");
        $dfResponse->assertStatus(200);
        $this->assertStringNotContainsString('id="editTabBtn3"', $dfResponse->getContent());
        $this->assertStringNotContainsString('id="editTabContent3"', $dfResponse->getContent());

        $emUser = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => false,
        ]);

        $emResponse = $this->actingAs($emUser)->get("/cases/{$this->case->case_number}/edit?from=Evidence_Management");
        $emResponse->assertStatus(200);
        $this->assertStringNotContainsString('id="editTabBtn3"', $emResponse->getContent());
        $this->assertStringNotContainsString('id="editTabContent3"', $emResponse->getContent());
    }

    #[Test]
    public function unit_edit_routes_redirect_with_appropriate_from_parameter(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_head' => true,
        ]);

        $labRedirect = $this->actingAs($user)->get("/Lab_Analysis/cases/{$this->case->case_number}/edit");
        $labRedirect->assertRedirect(route('cases.edit', ['id' => $this->case->case_number, 'from' => 'Lab_Analysis']));

        $cssRedirect = $this->actingAs($user)->get("/Crime_Scene_Support/cases/{$this->case->case_number}/edit");
        $cssRedirect->assertRedirect(route('cases.edit', ['id' => $this->case->case_number, 'from' => 'Crime_Scene_Support']));
    }
}

