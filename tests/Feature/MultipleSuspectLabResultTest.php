<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleSuspectLabResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a multiple-suspect case renders exactly one card on the lab-result page,
     * grouping all co-suspects together, even when screening result / multiple exhibits exist.
     */
    public function test_multiple_suspect_case_renders_single_card_for_case_entity_without_duplicates()
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'TEST-LAB-2026-MULTI',
            'title' => 'State vs Multi Suspects',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'laboratory_number' => 'Lab No. 99/TEST/2026',
        ]);

        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Primary Test Suspect',
            'role' => 'Primary Suspect',
            'ghana_card' => 'GHA-111111111-1',
            'gender' => 'Male',
        ]);

        $coSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Associate Test Suspect',
            'role' => 'Co-Suspect',
            'ghana_card' => 'GHA-222222222-2',
            'gender' => 'Female',
        ]);

        // Exhibit 1: Linked to all suspects
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => null,
            'associated_person' => 'All Suspects',
            'exhibit_number' => 'EXH-01',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin (Brown Sugar / Diacetylmorphine)',
            'seizure_weight' => 50,
            'seizure_unit' => 'kg',
        ]);

        // Exhibit 2: Linked only to primary suspect (e.g. from screening)
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $primarySuspect->id,
            'exhibit_number' => 'EXH-02',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Drugs',
        ]);

        // Drug screening generated for primary suspect
        DrugScreening::create([
            'suspect_id' => $primarySuspect->id,
            'purpose' => 'Custody Intake Screening',
            'screening_date' => now(),
            'result' => 'Positive',
            'substances_found' => 'Heroin',
        ]);

        // Lab examination result
        LaboratoryExaminationResult::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $primarySuspect->id,
            'laboratory_number' => 'Lab No. 99/TEST/2026',
            'presumptive_result' => 'YES (Presence of: HERO - Purity/Percentage: 50%)',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Heroin (Brown Sugar / Diacetylmorphine)',
            'has_saved' => 1,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab-result');
        $response->assertStatus(200);

        // Check view data directly from controller
        $drugRecords = $response->viewData('drugRecords');

        // Filter records for our test case
        $matching = $drugRecords->filter(fn($r) => $r['case_number'] === 'TEST-LAB-2026-MULTI');

        // MUST BE EXACTLY ONE CARD
        $this->assertCount(1, $matching, 'There must be exactly one card for the multiple-suspect case entity.');

        $card = $matching->first();
        $this->assertEquals('TEST-LAB-2026-MULTI', $card['case_number']);
        $this->assertEquals('Lab No. 99/TEST/2026', $card['laboratory_number']);
        $this->assertEquals('Heroin (Brown Sugar / Diacetylmorphine)', $card['drug_name']);

        // Both co-suspects must be present in this single card
        $this->assertCount(2, $card['suspects'], 'Both co-suspects must be grouped in this single card.');
        $suspectNames = collect($card['suspects'])->pluck('suspect_name')->all();
        $this->assertContains('Primary Test Suspect', $suspectNames);
        $this->assertContains('Associate Test Suspect', $suspectNames);

        // Check HTML content
        $content = $response->getContent();
        $this->assertStringContainsString('2 CO-SUSPECTS (GROUPED)', $content);
        $this->assertStringContainsString('Primary Test Suspect', $content);
        $this->assertStringContainsString('Associate Test Suspect', $content);

        // Ensure "SINGLE SUSPECT" badge does NOT appear for this test case
        // The case number should only appear once in Case Reference
        $caseMatches = substr_count($content, 'TEST-LAB-2026-MULTI');
        // Case number appears in data-search, case reference, actions
        // But there should only be 1 card header with Lab No. 99/TEST/2026
        $labNoMatches = substr_count($content, 'Lab No. 99/TEST/2026');
        $this->assertEquals(1, $labNoMatches, 'Lab reference number should appear in exactly one card.');
    }
}
