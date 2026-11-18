<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmatoryTestColumnDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function createLabOfficer(): User
    {
        return User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);
    }

    #[Test]
    public function confirmatory_test_column_renders_identical_sub_table_design_as_presumptive_reaction(): void
    {
        $officer = $this->createLabOfficer();

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-CONF-DESIGN-01',
            'title' => 'Confirmatory Test Column Design Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Michael Darko',
        ]);

        $exhibit = Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'Michael Darko',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'White Powder',
            'lab_exhibit_no' => 'LAU-EX-01',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_id' => $exhibit->id,
            'exam_reference' => 'NACOC/FSD/LAU/CONF/2026',
            'presumptive_result' => 'YES',
            'presumptive_presence' => 'Cocaine Sativa',
            'presumptive_percentage' => '85%',
            'confirmatory_result' => 'YES',
            'detected_substances' => ['Cocaine Hydrochloride', 'Levamisole Adulterant'],
            'detected_subtances_percentage' => ['84.6%', '12.4%'],
            'has_saved' => true,
        ]);

        $response = $this->actingAs($officer)
            ->get('/Lab_Analysis/cases/' . $case->case_number . '/review')
            ->assertOk();

        $html = $response->getContent();

        // 1. Verify Confirmatory Test header matches Presumptive Reaction
        $this->assertStringContainsString('Presumptive Reaction:', $html);
        $this->assertStringContainsString('Confirmatory Test:', $html);

        // 2. Presumptive Reaction renders: Result, Presence of, Purity/Percentage
        $this->assertMatchesRegularExpression(
            '/Presumptive Reaction:.*?<th[^>]*>\s*Result\s*<\/th>\s*<th[^>]*>\s*Presence of\s*<\/th>\s*<th[^>]*>\s*Purity\/Percentage\s*<\/th>/si',
            $html,
            'Presumptive Reaction must render Result, Presence of, and Purity/Percentage headers'
        );

        // Confirmatory Test strictly renders: Result, Substance Detected, Purity/Percentage
        $this->assertMatchesRegularExpression(
            '/Confirmatory Test:.*?<th[^>]*>\s*Result\s*<\/th>\s*<th[^>]*>\s*Substance Detected\s*<\/th>\s*<th[^>]*>\s*Purity\/Percentage\s*<\/th>/si',
            $html,
            'Confirmatory Test must render Result, Substance Detected, and Purity/Percentage headers'
        );

        // 3. Verify removed rows are absent from the table
        $this->assertStringNotContainsString('Substance Identified:', $html);
        $this->assertStringNotContainsString('Result (Detected Substances & Purity %):', $html);
        $this->assertStringNotContainsString('Detected Compounds:', $html);
        $this->assertStringNotContainsString('Analytical Findings:', $html);

        // 4. Verify Confirmatory Test rows render detected substances and percentages in the sub-table
        $this->assertMatchesRegularExpression(
            '/Confirmatory Test:.*?<td[^>]*>\s*YES\s*<\/td>\s*<td[^>]*>\s*Cocaine Hydrochloride\s*<\/td>\s*<td[^>]*>\s*84\.6%\s*<\/td>/si',
            $html,
            'Confirmatory Test row 1 must render YES, Cocaine Hydrochloride, and 84.6%'
        );

        $this->assertMatchesRegularExpression(
            '/Confirmatory Test:.*?<td[^>]*>\s*YES\s*<\/td>\s*<td[^>]*>\s*Levamisole Adulterant\s*<\/td>\s*<td[^>]*>\s*12\.4%\s*<\/td>/si',
            $html,
            'Confirmatory Test row 2 must render YES, Levamisole Adulterant, and 12.4%'
        );
    }
}
