<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * DrugCaseWorkflowTest
 *
 * Verifies that getWorkflowUnits() is driven 100% by exhibit categories.
 * No unit_name, case title, or creator-based heuristics are allowed.
 *
 * Routing truth table:
 *   Drug / Narcotic    → Lab Analysis + Evidence Management
 *   Electronic Device  → Digital & Physical Forensics + Evidence Management
 *   Weapon / Ammo      → Crime Scene Support + Evidence Management
 *   Gold / Money       → Crime Scene Support + Evidence Management
 *   Mixed combos       → all required units combined, in pipeline order
 */
class DrugCaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    private function makeSingleCase(array $overrides = []): SingleSuspectCase
    {
        return SingleSuspectCase::create(array_merge([
            'case_number'    => 'CAS-TEST-' . rand(1000, 9999),
            'title'          => 'Test Case',
            'unit_name'      => 'Crime Scene Support', // should NOT affect routing
            'suspect_name'   => 'Test Suspect',
            'status'         => 'Approved',
        ], $overrides));
    }

    private function addExhibit(SingleSuspectCase $sc, string $category, string $name = 'Test Item'): Exhibit
    {
        return Exhibit::create([
            'single_suspect_case_id' => $sc->id,
            'exhibit_category'       => $category,
            'commodity_name'         => $name,
        ]);
    }

    private function wrap(SingleSuspectCase $sc): ForensicCase
    {
        return ForensicCase::wrapRecord($sc->fresh(['exhibits', 'labResults']));
    }

    // ─────────────────────────────────────────────────────────
    // DRUG CASES
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function drug_case_routes_to_lab_analysis_and_evidence_management()
    {
        $sc = $this->makeSingleCase(['unit_name' => 'Lab Analysis']); // unit_name must NOT matter
        $this->addExhibit($sc, 'Narcotics / Drugs', 'Cocaine Brick');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertTrue($case->isDrugCase());
        $this->assertEquals(['Lab Analysis', 'Evidence Management'], $units);
        $this->assertNotContains('Crime Scene Support', $units);
        $this->assertNotContains('Digital & Physical Forensics', $units);
    }

    #[Test]
    public function drug_case_via_commodity_name_matching_triggers_lab_analysis()
    {
        $sc = $this->makeSingleCase();
        // seizure_name_drug is a virtual computed field; the real signal comes from the Exhibit record
        $this->addExhibit($sc, 'General Contraband', 'Heroin Powder');
        // 'heroin' is in the drug keyword list → should trigger Lab Analysis

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertTrue($case->isDrugCase());
        $this->assertContains('Lab Analysis', $units);
        $this->assertNotContains('Crime Scene Support', $units);
    }

    #[Test]
    public function drug_case_excludes_crime_scene_and_digital_forensics_from_workflow_units()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Cannabis / Weed', 'Compressed Cannabis Sativa');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertTrue($case->isDrugCase());
        $this->assertEquals(['Lab Analysis', 'Evidence Management'], $units);
        $this->assertNotContains('Crime Scene Support', $units);
        $this->assertNotContains('Digital & Physical Forensics', $units);
    }

    // ─────────────────────────────────────────────────────────
    // ELECTRONIC DEVICE CASES
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function electronic_device_case_routes_to_digital_forensics()
    {
        $sc = $this->makeSingleCase(['unit_name' => 'Crime Scene Support']); // unit_name must NOT force CSS
        $this->addExhibit($sc, 'Electronics / Mobile Devices', 'iPhone 15 Pro');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertFalse($case->isDrugCase());
        $this->assertContains('Digital & Physical Forensics', $units);
        $this->assertContains('Evidence Management', $units);
        $this->assertNotContains('Crime Scene Support', $units);
        $this->assertNotContains('Lab Analysis', $units);
    }

    #[Test]
    public function laptop_exhibit_routes_to_digital_forensics_not_lab()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Electronic Devices', 'Dell Laptop');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertContains('Digital & Physical Forensics', $units);
        $this->assertNotContains('Lab Analysis', $units);
        $this->assertNotContains('Crime Scene Support', $units);
    }

    // ─────────────────────────────────────────────────────────
    // WEAPON / AMMUNITION CASES
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function weapon_case_routes_to_crime_scene_support_only_not_lab()
    {
        $sc = $this->makeSingleCase(['unit_name' => 'Evidence Management']); // unit_name must NOT matter
        $this->addExhibit($sc, 'Firearms / Weapons', 'Pistol — Beretta 9mm');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertFalse($case->isDrugCase());
        $this->assertContains('Crime Scene Support', $units);
        $this->assertContains('Evidence Management', $units);
        $this->assertNotContains('Lab Analysis', $units);
        $this->assertNotContains('Digital & Physical Forensics', $units);
    }

    #[Test]
    public function ammunition_case_routes_to_crime_scene_support_not_lab()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Ammunition', '9mm cartridges x 50');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertContains('Crime Scene Support', $units);
        $this->assertNotContains('Lab Analysis', $units);
    }

    // ─────────────────────────────────────────────────────────
    // GOLD / MONEY / PRECIOUS METALS
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function gold_case_routes_to_crime_scene_support_not_lab()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Precious Metals / Gold', 'Gold Bars — 500g');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertFalse($case->isDrugCase());
        $this->assertContains('Crime Scene Support', $units);
        $this->assertNotContains('Lab Analysis', $units);
        $this->assertNotContains('Digital & Physical Forensics', $units);
    }

    #[Test]
    public function money_case_routes_to_crime_scene_support_not_lab()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Cash / Currency', 'GHS 250,000 Banknotes');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        $this->assertContains('Crime Scene Support', $units);
        $this->assertNotContains('Lab Analysis', $units);
    }

    // ─────────────────────────────────────────────────────────
    // MIXED EXHIBIT CASES
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function drug_plus_weapon_case_includes_crime_scene_and_lab_not_digital_forensics()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Narcotics / Drugs', 'Cocaine Powder');
        $this->addExhibit($sc, 'Firearms / Weapons', 'AK-47 Rifle');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        // Pipeline order: CSS → Lab → EM
        $this->assertEquals(['Crime Scene Support', 'Lab Analysis', 'Evidence Management'], $units);
        $this->assertNotContains('Digital & Physical Forensics', $units);
    }

    #[Test]
    public function drug_plus_electronic_device_includes_lab_and_digital_forensics_no_crime_scene()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Narcotics / Drugs', 'MDMA Pills');
        $this->addExhibit($sc, 'Electronics / Mobile Devices', 'Samsung Galaxy');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        // Pipeline order: Lab → D&PF → EM  (no CSS — no mandatory exhibit)
        $this->assertContains('Lab Analysis', $units);
        $this->assertContains('Digital & Physical Forensics', $units);
        $this->assertContains('Evidence Management', $units);
        $this->assertNotContains('Crime Scene Support', $units);
        // Verify ordering: Lab before D&PF
        $this->assertLessThan(array_search('Digital & Physical Forensics', $units), array_search('Lab Analysis', $units));
    }

    #[Test]
    public function electronic_device_plus_weapon_includes_crime_scene_and_digital_forensics_not_lab()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Electronics / Mobile Devices', 'Laptop');
        $this->addExhibit($sc, 'Firearms / Weapons', 'Revolver');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        // Pipeline order: CSS → D&PF → EM
        $this->assertEquals(['Crime Scene Support', 'Digital & Physical Forensics', 'Evidence Management'], $units);
        $this->assertNotContains('Lab Analysis', $units);
    }

    #[Test]
    public function drug_plus_weapon_plus_electronic_includes_all_three_units()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Narcotics / Drugs', 'Heroin');
        $this->addExhibit($sc, 'Firearms / Weapons', 'Shotgun');
        $this->addExhibit($sc, 'Electronics / Mobile Devices', 'USB Drive');

        $case  = $this->wrap($sc);
        $units = $case->getWorkflowUnits();

        // Pipeline order: CSS → Lab → D&PF → EM
        $this->assertEquals([
            'Crime Scene Support',
            'Lab Analysis',
            'Digital & Physical Forensics',
            'Evidence Management',
        ], $units);
    }

    // ─────────────────────────────────────────────────────────
    // CRITICAL: Lab Analysis MUST NOT appear for non-drug exhibits
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function lab_analysis_never_appears_for_weapon_exhibit()
    {
        $sc = $this->makeSingleCase(['unit_name' => 'Lab Analysis']); // even if unit is Lab
        $this->addExhibit($sc, 'Firearms / Weapons', 'Pistol');

        $units = $this->wrap($sc)->getWorkflowUnits();

        $this->assertNotContains('Lab Analysis', $units,
            'Lab Analysis must never appear for a weapon exhibit — it is exclusively for drugs.');
    }

    #[Test]
    public function lab_analysis_never_appears_for_gold_exhibit()
    {
        $sc = $this->makeSingleCase();
        $this->addExhibit($sc, 'Gold / Precious Metals', 'Gold Nuggets');

        $units = $this->wrap($sc)->getWorkflowUnits();

        $this->assertNotContains('Lab Analysis', $units);
    }

    #[Test]
    public function lab_analysis_never_appears_for_electronic_exhibit()
    {
        $sc = $this->makeSingleCase(['unit_name' => 'Lab Analysis']); // unit_name ignored
        $this->addExhibit($sc, 'Electronics / Mobile Devices', 'Hard Drive');

        $units = $this->wrap($sc)->getWorkflowUnits();

        $this->assertNotContains('Lab Analysis', $units,
            'Lab Analysis must never appear for an electronic-device exhibit.');
    }

    // ─────────────────────────────────────────────────────────
    // UNIT PROGRESSION FLOW — drug case end-to-end
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function completing_drug_case_units_forwards_case_to_hof_sign_off()
    {
        $officer = User::factory()->create([
            'fullname'    => 'Insp. Lab Officer',
            'unit'        => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $sc = $this->makeSingleCase([
            'case_number' => 'CAS-DRUG-003',
            'status'      => 'Under Multi-Unit Processing',
        ]);

        $this->addExhibit($sc, 'Methamphetamine', 'Crystal Meth Sample');

        LaboratoryExaminationResult::create([
            'single_suspect_case_id' => $sc->id,
            'suspect_name'           => 'Test Suspect',
            'has_saved'              => true,
            'confirmatory_result'    => 'YES',
        ]);

        // Lab Analysis completes
        $this->actingAs($officer)->post("/cases/{$sc->id}/update-unit-progress", [
            'unit_name'   => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'notes'       => 'Lab analysis completed. Confirmatory result positive.',
            'officer'     => 'Insp. Lab Officer',
        ]);

        // Evidence Management completes — triggers HOF sign-off
        $response = $this->actingAs($officer)->post("/cases/{$sc->id}/update-unit-progress", [
            'unit_name'   => 'Evidence Management',
            'unit_status' => 'COMPLETED',
            'notes'       => 'Evidence verified, logged, and secured.',
            'officer'     => 'Officer Evidence',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'         => true,
            'completed_count' => 2,
            'total_units'     => 2,
            'status'          => 'Awaiting Sign-off',
        ]);

        $this->assertEquals('Awaiting Sign-off', SingleSuspectCase::find($sc->id)->status);
    }
}
