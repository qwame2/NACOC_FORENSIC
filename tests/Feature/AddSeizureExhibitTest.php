<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddSeizureExhibitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);
    }

    public function test_add_drug_exhibit_links_to_suspect_and_sets_lab_analysis_pending()
    {
        $this->actingAs($this->user);

        // Create a case with a suspect who has no drug exhibits
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NFC-2026-9901',
            'title' => 'Republic vs Kwame Boateng',
            'unit_name' => 'Forensic Directorate',
            'status' => 'Pending',
            'priority' => 'HIGH',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwame Boateng',
        ]);

        $wrappedCase = ForensicCase::wrapRecord($multiCase);

        // Call associate endpoint to add a Drug exhibit for Kwame Boateng
        $response = $this->postJson("/cases/{$wrappedCase->id}/associate", [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Kwame Boateng',
                    'category' => 'Drugs',
                    'seizure_name' => 'Cocaine (Pure Powder)',
                    'seizure_quantity' => '10',
                    'seizure_weight' => '1.5',
                    'seizure_unit' => 'kg',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Exhibit is linked to Kwame Boateng's suspect ID
        $exhibit = Exhibit::where('multiple_suspect_case_id', $multiCase->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals($suspect->id, $exhibit->suspect_id);
        $this->assertEquals('Drugs', $exhibit->exhibit_category);
        $this->assertEquals('Cocaine (Pure Powder)', $exhibit->commodity_name);

        // Verify required workflow units include Lab Analysis
        $freshWrapped = ForensicCase::wrapRecord($multiCase->fresh(['suspects', 'exhibits', 'labResults']));
        $reqUnits = $freshWrapped->getWorkflowUnits();
        $this->assertContains('Lab Analysis', $reqUnits);
    }

    public function test_add_electronic_device_exhibit_sets_digital_forensics_pending()
    {
        $this->actingAs($this->user);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NFC-2026-9902',
            'title' => 'Republic vs John Mensah',
            'unit_name' => 'Forensic Directorate',
            'status' => 'Pending',
            'priority' => 'HIGH',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'John Mensah',
        ]);

        $wrappedCase = ForensicCase::wrapRecord($multiCase);

        // Call associate endpoint to add an Electronic Devices exhibit
        $response = $this->postJson("/cases/{$wrappedCase->id}/associate", [
            'associated_exhibits' => [
                [
                    'associated_person' => 'John Mensah',
                    'category' => 'Electronic Devices',
                    'seizure_name' => 'Encrypted MacBook Pro',
                    'seizure_quantity' => '1',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Exhibit is linked to John Mensah
        $exhibit = Exhibit::where('multiple_suspect_case_id', $multiCase->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals($suspect->id, $exhibit->suspect_id);
        $this->assertEquals('Electronic Devices', $exhibit->exhibit_category);

        // Verify required workflow units include Digital & Physical Forensics
        $freshWrapped = ForensicCase::wrapRecord($multiCase->fresh(['suspects', 'exhibits', 'labResults']));
        $reqUnits = $freshWrapped->getWorkflowUnits();
        $this->assertContains('Digital & Physical Forensics', $reqUnits);
    }
}
