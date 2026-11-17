<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
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

        // Ensure "Investigating Officer", "Source/Requesting Unit", and "Source of Exhibits" input elements are removed from DOM
        $this->assertStringNotContainsString('id="newSeizureInvestigatingOfficer"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceRequestingUnit"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceOfExhibits"', $html);
        $this->assertStringNotContainsString('class="seizure-investigating-officer-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-requesting-unit-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-of-exhibits-input', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..."', $html);

        // Ensure seizure location, reason and seizing officers remain intact
        $this->assertStringContainsString('id="newSeizureLocation"', $html);
        $this->assertStringContainsString('id="newSeizureReason"', $html);
        $this->assertStringContainsString('id="seizureOfficersContainer"', $html);

        // Ensure Exhibit (Commodity Category) and Primary Commodity Name match forensic device configuration
        $this->assertStringContainsString('id="exhibitSearchInput"', $html);
        $this->assertStringContainsString('placeholder="Select or type exhibit category..."', $html);
        $this->assertStringContainsString('id="newSeizureCategory" value="Electronic Devices"', $html);
        $this->assertStringContainsString('id="deviceSelectWrapper"', $html);
        $this->assertStringContainsString('id="deviceSearchInput"', $html);
        $this->assertStringContainsString('id="newSeizureNameDevice"', $html);
        $this->assertStringContainsString('id="deviceListPanel"', $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Storage Media'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Communication Hardware'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Digital Documents'", $html);
    }

    #[Test]
    public function digital_forensics_show_review_page_matches_create_page_seized_item_fields(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'DF-2026-REV-001',
            'title' => 'Digital Forensics Review Test',
            'unit_name' => 'Digital & Physical Forensics',
        ]);

        $response = $this->actingAs($officer)->get("/Digital_Forensics/cases/{$case->id}/review");
        $response->assertStatus(200);
        $response->assertViewIs('Digital_Forensics.show_review');

        $html = $response->getContent();

        // Ensure "Investigating Officer", "Source/Requesting Unit", and "Source of Exhibits" input elements are removed from DOM
        $this->assertStringNotContainsString('id="newSeizureInvestigatingOfficer"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceRequestingUnit"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceOfExhibits"', $html);
        $this->assertStringNotContainsString('class="seizure-investigating-officer-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-requesting-unit-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-of-exhibits-input', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..."', $html);

        // Ensure seizure date, time, location, reason and seizing officers match create page structure
        $this->assertStringContainsString('id="newSeizureDate"', $html);
        $this->assertStringContainsString('id="newSeizureTime"', $html);
        $this->assertStringContainsString('id="newSeizureLocation"', $html);
        $this->assertStringContainsString('id="newSeizureReason"', $html);
        $this->assertStringContainsString('id="seizureOfficersContainer"', $html);

        // Ensure Exhibit (Commodity Category) and Primary Commodity Name match create page
        $this->assertStringContainsString('id="exhibitSearchInput"', $html);
        $this->assertStringContainsString('placeholder="Select or type exhibit category..."', $html);
        $this->assertStringContainsString('id="newSeizureCategory" value="Electronic Devices"', $html);
        $this->assertStringContainsString('id="deviceSelectWrapper"', $html);
        $this->assertStringContainsString('id="deviceSearchInput"', $html);
        $this->assertStringContainsString('id="newSeizureNameDevice"', $html);
        $this->assertStringContainsString('id="deviceListPanel"', $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Storage Media'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Communication Hardware'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Digital Documents'", $html);
    }

    #[Test]
    public function digital_forensics_staff_review_page_matches_create_page_seized_item_fields(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
            'is_staff' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'DF-2026-STF-001',
            'title' => 'Digital Forensics Staff Review Test',
            'unit_name' => 'Digital & Physical Forensics',
        ]);

        $response = $this->actingAs($officer)->get("/Digital_Forensics/staff/cases/{$case->id}/review");
        $response->assertStatus(200);
        $response->assertViewIs('Digital_Forensics.staff.review');

        $html = $response->getContent();

        // Ensure "Investigating Officer", "Source/Requesting Unit", and "Source of Exhibits" input elements are removed from DOM
        $this->assertStringNotContainsString('id="newSeizureInvestigatingOfficer"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceRequestingUnit"', $html);
        $this->assertStringNotContainsString('id="newSeizureSourceOfExhibits"', $html);
        $this->assertStringNotContainsString('class="seizure-investigating-officer-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-requesting-unit-input', $html);
        $this->assertStringNotContainsString('class="seizure-source-of-exhibits-input', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."', $html);
        $this->assertStringNotContainsString('placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..."', $html);

        // Ensure seizure date, time, location, reason and seizing officers match create page structure
        $this->assertStringContainsString('id="newSeizureDate"', $html);
        $this->assertStringContainsString('id="newSeizureTime"', $html);
        $this->assertStringContainsString('id="newSeizureLocation"', $html);
        $this->assertStringContainsString('id="newSeizureReason"', $html);
        $this->assertStringContainsString('id="seizureOfficersContainer"', $html);

        // Ensure Exhibit (Commodity Category) and Primary Commodity Name match create page
        $this->assertStringContainsString('id="exhibitSearchInput"', $html);
        $this->assertStringContainsString('placeholder="Select or type exhibit category..."', $html);
        $this->assertStringContainsString('id="newSeizureCategory" value="Electronic Devices"', $html);
        $this->assertStringContainsString('id="deviceSelectWrapper"', $html);
        $this->assertStringContainsString('id="deviceSearchInput"', $html);
        $this->assertStringContainsString('id="newSeizureNameDevice"', $html);
        $this->assertStringContainsString('id="deviceListPanel"', $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Storage Media'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Communication Hardware'", $html);
        $this->assertStringNotContainsString("selectExhibitCategory('Digital Documents'", $html);
    }
}
