<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DynamicSeizedItemsManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit, bool $isHead = false, bool $isApproved = true): User
    {
        return User::factory()->create([
            'fullname' => "{$unit} Officer",
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => $isApproved,
            'is_staff' => true,
        ]);
    }

    protected function assertHtmlContains(string $needle, string $html, string $message = ''): void
    {
        $this->assertTrue(str_contains($html, $needle), $message ?: "Failed asserting that HTML contains [{$needle}].");
    }

    #[Test]
    public function test_all_create_pages_render_dynamic_seizure_count_input_and_manifest_controls(): void
    {
        $units = [
            'Forensics' => '/cases/create',
            'Digital Forensics' => '/Digital_Forensics/create',
            'Evidence Management' => '/Evidence_Management/create',
            'Laboratory Analysis (LAU)' => '/Lab_Analysis/create',
        ];

        foreach ($units as $unit => $url) {
            $officer = $this->createOfficer($unit);
            $response = $this->actingAs($officer)->get($url);

            $this->assertEquals(200, $response->getStatusCode(), "Failed loading create page for unit {$unit} at {$url}");
            $html = $response->getContent();

            $this->assertHtmlContains('Number of Seized Items:', $html, "Missing 'Number of Seized Items:' label in {$url}");
            $this->assertHtmlContains('id="seizureCountInput"', $html, "Missing 'seizureCountInput' in {$url}");
            $this->assertHtmlContains('setSeizureBlocksCount(this.value)', $html, "Missing 'setSeizureBlocksCount' invocation in {$url}");
            $this->assertHtmlContains('Seized Item 1', $html, "Missing sequential heading 'Seized Item 1' in {$url}");
            $this->assertHtmlContains('function setSeizureBlocksCount', $html, "Missing 'setSeizureBlocksCount' function definition in {$url}");
            $this->assertHtmlContains('function seizureBlockHasData', $html, "Missing 'seizureBlockHasData' function definition in {$url}");
            $this->assertHtmlContains('function populateExistingManifestSections', $html, "Missing 'populateExistingManifestSections' function definition in {$url}");
            $this->assertFalse(str_contains($html, 'Add Another Seizure Item'), "Unexpected bottom '+ Add Another Seizure Item' button found in {$url}");
            $this->assertFalse(str_contains($html, 'Add Another Seized Item'), "Unexpected bottom '+ Add Another Seized Item' button found in {$url}");
        }

        // Also test dispatching via /cases/create for each unit's authenticated officer
        foreach ($units as $unit => $directUrl) {
            $officer = $this->createOfficer($unit);
            $response = $this->actingAs($officer)->get('/cases/create');
            $this->assertEquals(200, $response->getStatusCode());
            $html = $response->getContent();
            $this->assertHtmlContains('id="seizureCountInput"', $html);
            $this->assertHtmlContains('Seized Item 1', $html);
        }
    }

    #[Test]
    public function test_all_show_review_pages_render_dynamic_seizure_count_input_and_manifest_controls(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TEST-2026-SEIZURE-001',
            'case_type' => 'Single Suspect',
            'suspect_name' => 'John Doe',
            'status' => 'Pending Review',
            'docket_data' => [
                'investigator_name' => 'Officer Smith',
                'suspect_details' => [
                    'suspect_name' => 'John Doe',
                    'nationality' => 'Ghanaian',
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Cocaine',
                        'quantity' => '5',
                        'packaging' => 'Slab',
                        'weight' => '5.2',
                        'unit' => 'Kilograms',
                    ],
                    [
                        'category' => 'Weapons',
                        'seizure_name' => 'Glock 19',
                        'quantity' => '1',
                        'weapon_serial_no' => 'GLK192837',
                    ],
                ],
            ],
        ]);

        $units = [
            'Forensics' => "/cases/{$case->id}/review",
            'Crime Scene Support' => "/Crime_Scene_Support/cases/{$case->id}/review",
            'Digital Forensics' => "/Digital_Forensics/cases/{$case->id}/review",
            'Evidence Management' => "/Evidence_Management/cases/{$case->id}/review",
            'Forensic Head' => "/Forensic_Head/cases/{$case->id}/review",
            'Laboratory Analysis (LAU)' => "/Lab_Analysis/cases/{$case->id}/review",
        ];

        foreach ($units as $unit => $url) {
            $officer = $this->createOfficer($unit, isHead: ($unit === 'Forensic Head'));
            $response = $this->actingAs($officer)->get($url);

            $this->assertEquals(200, $response->getStatusCode(), "Failed loading show_review page for unit {$unit}");
            $html = $response->getContent();

            $this->assertHtmlContains('Number of Seized Items:', $html, "Missing 'Number of Seized Items:' label in {$url}");
            $this->assertHtmlContains('id="seizureCountInput"', $html, "Missing 'seizureCountInput' in {$url}");
            $this->assertHtmlContains('setSeizureBlocksCount(this.value)', $html, "Missing 'setSeizureBlocksCount' invocation in {$url}");
            $this->assertHtmlContains('Seized Item 1', $html, "Missing sequential heading 'Seized Item 1' in {$url}");
            $this->assertHtmlContains('function setSeizureBlocksCount', $html, "Missing 'setSeizureBlocksCount' function definition in {$url}");
            $this->assertHtmlContains('function seizureBlockHasData', $html, "Missing 'seizureBlockHasData' function definition in {$url}");
            $this->assertHtmlContains('function populateExistingManifestSections', $html, "Missing 'populateExistingManifestSections' function definition in {$url}");
            $this->assertHtmlContains('window._savedCaseExhibitsData =', $html, "Missing '_savedCaseExhibitsData' payload in {$url}");
            $this->assertFalse(str_contains($html, 'Add Another Seizure Item'), "Unexpected bottom '+ Add Another Seizure Item' button found in {$url}");
            $this->assertFalse(str_contains($html, 'Add Another Seized Item'), "Unexpected bottom '+ Add Another Seized Item' button found in {$url}");
        }
    }

    #[Test]
    public function test_show_review_populates_manifest_data_for_existing_exhibits(): void
    {
        $case = ForensicCase::create([
            'case_number' => 'NACOC-TEST-2026-SEIZURE-002',
            'case_type' => 'Single Suspect',
            'suspect_name' => 'Jane Smith',
            'status' => 'Pending Review',
            'docket_data' => [
                'investigator_name' => 'Detective Jones',
                'suspect_details' => [
                    'suspect_name' => 'Jane Smith',
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin',
                        'quantity' => '2',
                        'packaging' => 'Package',
                        'weight' => '1.5',
                        'unit' => 'Kilograms',
                    ],
                    [
                        'category' => 'Money',
                        'seizure_name' => 'USD Currency',
                        'quantity' => '10000',
                        'value' => '10000',
                    ],
                    [
                        'category' => 'Electronic Devices',
                        'seizure_name' => 'iPhone 15 Pro',
                        'model' => 'A2848',
                        'serial_number' => 'DNQZ1982736',
                    ],
                ],
            ],
        ]);

        $officer = $this->createOfficer('Forensics');
        $response = $this->actingAs($officer)->get("/cases/{$case->id}/review");

        $response->assertStatus(200);
        $html = $response->getContent();

        // Exhibits data must contain all 3 commodities
        $this->assertHtmlContains('Heroin', $html);
        $this->assertHtmlContains('USD Currency', $html);
        $this->assertHtmlContains('iPhone 15 Pro', $html);
    }
}
