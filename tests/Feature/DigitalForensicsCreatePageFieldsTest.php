<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalForensicsCreatePageFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function digital_forensics_create_page_does_not_contain_investigating_officer_or_source_requesting_unit_inputs(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->get('/Digital_Forensics/create');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Ensure "Investigating Officer" and "Source/Requesting Unit" input elements are removed from DOM
        $this->assertStringNotContainsString('id="newSeizureInvestigatingOfficer"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceRequestingUnit"', $html);
        $this->assertStringNotContainsString('class="seizure-investigating-officer-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-requesting-unit-input', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."', $html);

        // Ensure seizure reason and seizing officers remain intact
        $this->assertStringContainsString('id="newSeizureReason"', $html);
        $this->assertStringContainsString('id="seizureOfficersContainer"', $html);
    }
}
