<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowReviewSeizureSectionSingleItemAndResetTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit, bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => "{$unit} Officer",
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function show_review_for_multi_suspect_case_renders_single_seizure_item_and_reset_button_across_all_units(): void
    {
        $units = [
            'Lab Analysis' => [
                'head_url' => '/Lab_Analysis/cases/CASE-MULTI-001/review',
                'staff_url' => '/Lab_Analysis/staff/cases/CASE-MULTI-001/review',
            ],
            'Crime Scene Support' => [
                'head_url' => '/Crime_Scene_Support/cases/CASE-MULTI-001/review',
                'staff_url' => '/Crime_Scene_Support/staff/cases/CASE-MULTI-001/review',
            ],
            'Digital Forensics' => [
                'head_url' => '/Digital_Forensics/cases/CASE-MULTI-001/review',
                'staff_url' => '/Digital_Forensics/staff/cases/CASE-MULTI-001/review',
            ],
            'Evidence Management' => [
                'head_url' => '/Evidence_Management/cases/CASE-MULTI-001/review',
                'staff_url' => '/Evidence_Management/staff/cases/CASE-MULTI-001/review',
            ],
            'Forensic Directorate' => [
                'head_url' => '/Forensic_Head/cases/CASE-MULTI-001/review',
                'staff_url' => '/Forensic_Head/staff/cases/CASE-MULTI-001/review',
            ],
        ];

        // Create a case with 3 suspects and 3 exhibits
        $case = MultipleSuspectCase::create([
            'case_number' => 'CASE-MULTI-001',
            'title' => 'Republic v 3 Suspects',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'total_suspects_count' => 3,
            'docket_data' => [
                'suspects' => [
                    ['id' => 1, 'suspect_name' => 'Suspect Alpha', 'association_role' => 'Primary Suspect'],
                    ['id' => 2, 'suspect_name' => 'Suspect Beta', 'association_role' => 'Associate Suspect'],
                    ['id' => 3, 'suspect_name' => 'Suspect Gamma', 'association_role' => 'Associate Suspect'],
                ],
                'seizures' => [
                    ['exhibit_number' => 'EXH-001', 'exhibit_category' => 'Drugs', 'seizure_name' => 'Cocaine', 'owner_name' => 'Suspect Alpha'],
                    ['exhibit_number' => 'EXH-002', 'exhibit_category' => 'Drugs', 'seizure_name' => 'Heroin', 'owner_name' => 'Suspect Beta'],
                    ['exhibit_number' => 'EXH-003', 'exhibit_category' => 'Electronic Devices', 'seizure_name' => 'Phone', 'owner_name' => 'Suspect Gamma'],
                ],
            ],
        ]);

        foreach ($units as $unitName => $routes) {
            // Test Head Review
            $headUser = $this->createOfficer($unitName, isHead: true);
            $headResponse = $this->actingAs($headUser)->get($routes['head_url']);
            $headResponse->assertStatus(200);
            $headHtml = $headResponse->getContent();

            // 1. Must contain "Reset Section" button with resetSeizureItemSection(true)
            $this->assertStringContainsString('onclick="resetSeizureItemSection(true)"', $headHtml, "Head view {$unitName} missing Reset Section button.");
            $this->assertStringContainsString('Reset Section', $headHtml, "Head view {$unitName} missing Reset Section text.");

            // 2. Must define function resetSeizureItemSection
            $this->assertStringContainsString('function resetSeizureItemSection(', $headHtml, "Head view {$unitName} missing resetSeizureItemSection function.");

            // 3. Must NOT call populateExistingManifestSections(window._savedCaseExhibitsData) on DOMContentLoaded
            $this->assertStringNotContainsString('populateExistingManifestSections(window._savedCaseExhibitsData)', $headHtml, "Head view {$unitName} still populating existing exhibits on DOMContentLoaded.");

            // 4. Seizure count input value should be 1
            $this->assertStringContainsString('id="seizureCountInput" min="1" max="100" value="1"', $headHtml, "Head view {$unitName} seizureCountInput not defaulting to 1.");

            // 5. Section 4 toggle calls resetSeizureItemSection(false) when hidden
            $this->assertStringContainsString('resetSeizureItemSection(false)', $headHtml, "Head view {$unitName} toggleSeizureItemSection not calling resetSeizureItemSection on deactivate.");

            // Test Staff Review
            $staffUser = $this->createOfficer($unitName, isHead: false);
            $staffResponse = $this->actingAs($staffUser)->get($routes['staff_url']);
            $staffResponse->assertStatus(200);
            $staffHtml = $staffResponse->getContent();

            $this->assertStringContainsString('onclick="resetSeizureItemSection(true)"', $staffHtml, "Staff view {$unitName} missing Reset Section button.");
            $this->assertStringContainsString('Reset Section', $staffHtml, "Staff view {$unitName} missing Reset Section text.");
            $this->assertStringContainsString('function resetSeizureItemSection(', $staffHtml, "Staff view {$unitName} missing resetSeizureItemSection function.");
            $this->assertStringNotContainsString('populateExistingManifestSections(window._savedCaseExhibitsData)', $staffHtml, "Staff view {$unitName} still populating existing exhibits on DOMContentLoaded.");
            $this->assertStringContainsString('id="seizureCountInput" min="1" max="100" value="1"', $staffHtml, "Staff view {$unitName} seizureCountInput not defaulting to 1.");
            $this->assertStringContainsString('resetSeizureItemSection(false)', $staffHtml, "Staff view {$unitName} toggleSeizureItemSection not calling resetSeizureItemSection on deactivate.");
        }
    }
}

