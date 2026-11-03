<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeizureItemSectionActivationToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = User::factory()->create([
            'unit' => 'Forensic',
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function seizure_item_section_has_activation_checkbox_and_is_hidden_by_default_across_all_four_units(): void
    {
        $units = [
            'Lab Analysis',
            'Evidence Management',
            'Crime Scene Support',
            'Digital Forensics',
        ];

        foreach ($units as $unitName) {
            $case = MultipleSuspectCase::create([
                'case_number' => 'CASE-' . strtoupper(substr(str_replace(' ', '', $unitName), 0, 4)) . '-001',
                'title' => 'Republic v Suspect ' . $unitName,
                'unit_name' => $unitName,
                'status' => 'Pending Approval',
                'user_id' => $this->officer->id,
                'total_suspects_count' => 1,
            ]);

            $suspect = CaseSuspect::create([
                'multiple_suspect_case_id' => $case->id,
                'suspect_number' => 1,
                'role' => 'Primary Suspect',
                'suspect_name' => 'Suspect ' . $unitName,
            ]);

            $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?unit=' . urlencode($unitName) . '&suspect=' . urlencode($suspect->suspect_name));
            $response->assertStatus(200);

            $html = $response->getContent();

            // 1. Activation toggle card exists
            $this->assertStringContainsString('id="seizureItemActivationCard"', $html, "seizureItemActivationCard must exist for unit: {$unitName}");
            $this->assertStringContainsString('Seizure Item Section', $html, "Header must be present for unit: {$unitName}");
            $this->assertStringContainsString('INACTIVE / HIDDEN', $html, "Initial badge must show INACTIVE / HIDDEN for unit: {$unitName}");

            // 2. Checkbox is rendered and default is unchecked
            $this->assertStringContainsString('id="toggleSeizureItemSectionCheckbox"', $html, "Checkbox must exist for unit: {$unitName}");
            $this->assertDoesNotMatchRegularExpression('/id="toggleSeizureItemSectionCheckbox"[^>]*\schecked\b/', $html, "Checkbox must NOT be checked by default for unit: {$unitName}");

            // 3. Section 4 container has the hidden class by default
            $this->assertMatchesRegularExpression('/id="section4Seizures"[^>]*class="[^"]*hidden[^"]*"/', $html, "section4Seizures must have hidden class by default for unit: {$unitName}");

            // 4. JS toggle handler is present
            $this->assertStringContainsString('function toggleSeizureItemSection(', $html, "toggleSeizureItemSection function must be present for unit: {$unitName}");
        }
    }
}
