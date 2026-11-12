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

class EvidenceManagementShowReviewMoneyExhibitTest extends TestCase
{
    use RefreshDatabase;

    private User $emOfficer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emOfficer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function evidence_management_case_with_money_exhibit_has_no_crime_scene_and_displays_currency_and_amount(): void
    {
        // 1. Evidence Management creates a single-suspect case with Money exhibit
        $case = SingleSuspectCase::create([
            'case_number' => 'EM-2026-001',
            'title' => 'State v John Doe',
            'unit_name' => 'Evidence Management',
            'status' => 'Pending Approval',
            'user_id' => $this->emOfficer->id,
            'suspect_name' => 'John Doe',
            'exhibit_category' => 'Money',
            'commodity_name' => 'Ghana Cedi (GHS ₵)',
            'seizure_quantity' => '50,000',
            'amount' => '50,000',
            'docket_data' => [
                'created_by_unit' => 'Evidence Management',
                'seizures' => [
                    [
                        'category' => 'Money',
                        'exhibit_category' => 'Money',
                        'commodity_name' => 'Ghana Cedi (GHS ₵)',
                        'seizure_name_currency' => 'Ghana Cedi (GHS ₵)',
                        'seizure_name' => 'Ghana Cedi (GHS ₵)',
                        'name' => 'Ghana Cedi (GHS ₵)',
                        'seizure_quantity' => '50,000',
                        'amount' => '50,000',
                        'associated_person' => 'John Doe',
                    ],
                ],
                'unit_progress' => [
                    'Evidence Management' => [
                        'status' => 'COMPLETED',
                        'officer' => $this->emOfficer->fullname,
                        'updated_at' => now()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $suspect = CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'John Doe',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'suspect_id' => $suspect->id,
            'associated_person' => 'John Doe',
            'exhibit_number' => 'EXH-01',
            'exhibit_category' => 'Money',
            'commodity_name' => 'Ghana Cedi (GHS ₵)',
            'seizure_quantity' => '50,000',
            'amount' => '50,000',
        ]);

        // Verify Exhibit model attributes
        $this->assertEquals('Money', $exhibit->exhibit_category);
        $this->assertEquals('Ghana Cedi (GHS ₵)', $exhibit->commodity_name);
        $this->assertEquals('50,000', $exhibit->amount);

        // 2. Request show_review page in Evidence Management
        $response = $this->actingAs($this->emOfficer)->get("/cases/{$case->id}/review?suspect=John+Doe&unit=Evidence+Management");
        $response->assertStatus(200);

        $html = $response->getContent();

        // 3. Expected Behavior 1: Workflow only shows Evidence Management (NO phantom Crime Scene Support)
        $this->assertStringContainsString('id="unitCard-evidencemanagement"', $html);
        $this->assertStringNotContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringNotContainsString('id="unitCard-labanalysis"', $html);
        $this->assertStringNotContainsString('id="unitCard-digitalphysicalforensics"', $html);
        $this->assertStringContainsString('1-Unit Directorate Progress', $html);

        // 4. Expected Behavior 2: Seizure / Commodity Name displays actual currency and does NOT fall back to "Exhibit"
        $this->assertStringContainsString('Ghana Cedi (GHS ₵)', $html);

        // 5. Expected Behavior 3: Amount is displayed
        $this->assertStringContainsString('50,000', $html);
    }

    #[Test]
    public function adding_money_exhibit_via_associate_stores_amount_and_displays_on_review(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'EM-2026-002',
            'title' => 'State v Jane Doe',
            'unit_name' => 'Evidence Management',
            'status' => 'Pending Approval',
            'user_id' => $this->emOfficer->id,
            'suspect_name' => 'Jane Doe',
            'docket_data' => [
                'created_by_unit' => 'Evidence Management',
                'seizures' => [],
                'unit_progress' => [
                    'Evidence Management' => [
                        'status' => 'COMPLETED',
                        'officer' => $this->emOfficer->fullname,
                        'updated_at' => now()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Jane Doe',
        ]);

        // Post new Money exhibit via associate endpoint
        $payload = [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Jane Doe',
                    'suspect_name' => 'Jane Doe',
                    'category' => 'Money',
                    'exhibit_category' => 'Money',
                    'seizure_name' => 'United States Dollar (USD $)',
                    'commodity_name' => 'United States Dollar (USD $)',
                    'name' => 'United States Dollar (USD $)',
                    'seizure_quantity' => '100,000',
                    'quantity' => '100,000',
                    'amount' => '100,000',
                ],
            ],
        ];

        $postRes = $this->actingAs($this->emOfficer)->postJson("/cases/{$case->id}/associate", $payload);
        $postRes->assertStatus(200);

        // Check exhibit was created in DB with amount and commodity_name
        $savedExhibit = Exhibit::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($savedExhibit);
        $this->assertEquals('Money', $savedExhibit->exhibit_category);
        $this->assertEquals('United States Dollar (USD $)', $savedExhibit->commodity_name);
        $this->assertEquals('100,000', $savedExhibit->amount);

        // Review page
        $response = $this->actingAs($this->emOfficer)->get("/cases/{$case->id}/review?suspect=Jane+Doe&unit=Evidence+Management");
        $response->assertStatus(200);

        $html = $response->getContent();

        // No Crime Scene Support
        $this->assertStringNotContainsString('id="unitCard-crimescenesupport"', $html);
        $this->assertStringContainsString('United States Dollar (USD $)', $html);
        $this->assertStringContainsString('100,000', $html);
    }
}

