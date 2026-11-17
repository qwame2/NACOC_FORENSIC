<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowReviewUnitPipelineFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function scenario_a_lab_analysis_created_case_no_other_unit_contributed_shows_three_cards(): void
    {
        // Scenario A: Lab Analysis created the case, drug exhibit only, no other unit contributed.
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-001',
            'title' => 'Republic v Kwesi',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwesi Mensah',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kwesi Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?suspect=Kwesi+Mensah');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 2 cards: Lab Analysis, Evidence Management
        $this->assertStringContainsString('id="unitCard-labanalysis"', $html);
        $this->assertStringContainsString('id="unitCard-evidencemanagement"', $html);
        $this->assertStringNotContainsString('Head of Forensic Directorate', $html);

        // Excluded: Crime Scene Support and Digital & Physical Forensics
        $this->assertStringNotContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringNotContainsString('id="unitCard-digitalphysicalforensics"', $html);

        // Header: 2 operational units count
        $this->assertStringContainsString('2-Unit Directorate Progress', $html);
    }

    #[Test]
    public function scenario_b_crime_scene_support_created_digital_later_added_electronic_shows_four_cards(): void
    {
        // Scenario B: Crime Scene Support created the case, Digital added electronic exhibits later.
        $case = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-002',
            'title' => 'Republic v Kofi',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kofi Boateng',
        ]);

        // Electronic exhibit added
        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kofi Boateng',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'iPhone 13 Pro',
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?suspect=Kofi+Boateng');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 3 cards: Crime Scene Support, Digital & Physical Forensics, Evidence Management
        $this->assertStringContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringContainsString('id="unitCard-digitalphysicalforensics"', $html);
        $this->assertStringContainsString('id="unitCard-evidencemanagement"', $html);
        $this->assertStringNotContainsString('Head of Forensic Directorate', $html);

        // Excluded: Lab Analysis
        $this->assertStringNotContainsString('id="unitCard-labanalysis"', $html);

        // Header: 3 operational units count
        $this->assertStringContainsString('3-Unit Directorate Progress', $html);
    }

    #[Test]
    public function scenario_c_lab_analysis_created_all_other_units_contributed_shows_five_cards(): void
    {
        // Scenario C: Lab Analysis created case, CSS and DPF also contributed exhibits
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-003',
            'title' => 'Republic v Multi Contributor',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Yaw Asante',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Yaw Asante',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Yaw Asante',
            'exhibit_category' => 'Weapons',
            'commodity_name' => 'Pistol 9mm',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Yaw Asante',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Samsung Galaxy Phone',
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?suspect=Yaw+Asante');
        $response->assertStatus(200);

        $html = $response->getContent();

        // All 4 operational units: Lab Analysis -> Crime Scene Support -> Digital & Physical Forensics -> Evidence Management
        $this->assertStringContainsString('id="unitCard-labanalysis"', $html);
        $this->assertStringContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringContainsString('id="unitCard-digitalphysicalforensics"', $html);
        $this->assertStringContainsString('id="unitCard-evidencemanagement"', $html);
        $this->assertStringNotContainsString('Head of Forensic Directorate', $html);

        // Header: 4 operational units count
        $this->assertStringContainsString('4-Unit Directorate Progress', $html);
    }

    #[Test]
    public function scenario_d_crime_scene_support_created_only_evidence_management_processes_shows_three_cards(): void
    {
        // Scenario D: Crime Scene Support created case, only Evidence Management processes later
        $case = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-004',
            'title' => 'Republic v Solo CSS',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Ama Serwaa',
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?suspect=Ama+Serwaa');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 2 cards: Crime Scene Support -> Evidence Management
        $this->assertStringContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringContainsString('id="unitCard-evidencemanagement"', $html);
        $this->assertStringNotContainsString('Head of Forensic Directorate', $html);

        // Excluded: Lab Analysis and Digital & Physical Forensics
        $this->assertStringNotContainsString('id="unitCard-labanalysis"', $html);
        $this->assertStringNotContainsString('id="unitCard-digitalphysicalforensics"', $html);

        // Header: 2 operational units count
        $this->assertStringContainsString('2-Unit Directorate Progress', $html);
    }
}
