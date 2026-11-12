<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerUnitShowReviewTest extends TestCase
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

    protected function assertHtmlNotContains(string $needle, string $html, string $message = ''): void
    {
        $this->assertFalse(str_contains($html, $needle), $message ?: "Failed asserting that HTML does not contain [{$needle}].");
    }

    #[Test]
    public function lab_analysis_view_renders_saved_vs_unsaved_lab_results_and_joint_vs_primary_ownership(): void
    {
        $labOfficer = $this->createOfficer('Lab Analysis');

        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-P3-001',
            'title' => 'Republic v Primary & Associate',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $labOfficer->id,
            'total_suspects_count' => 2,
            'docket_data' => [
                'suspects' => [
                    ['id' => 1, 'suspect_name' => 'Kwame Primary', 'association_role' => 'Primary Suspect'],
                    ['id' => 2, 'suspect_name' => 'Kofi Associate', 'association_role' => 'Associate Suspect'],
                ],
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH/LAB/001',
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Cocaine Hydrochloride',
                        'associated_person' => 'Kwame Primary',
                        'seizure_quantity' => 3,
                        'seizure_weight' => 100,
                        'seizure_unit' => 'g',
                    ],
                    [
                        'exhibit_number' => 'EXH/LAB/002',
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Heroin Base',
                        'associated_person' => 'Joint Exhibit',
                        'seizure_quantity' => 1,
                        'seizure_weight' => 50,
                        'seizure_unit' => 'g',
                    ],
                ],
            ],
        ]);

        $primary = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwame Primary',
        ]);

        $associate = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Kofi Associate',
        ]);

        $primaryExhibit = Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $primary->id,
            'associated_person' => 'Kwame Primary',
            'exhibit_number' => 'EXH/LAB/001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Hydrochloride',
            'quantity' => 3,
            'weight' => 100,
            'weight_unit' => 'g',
        ]);

        Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $associate->id,
            'associated_person' => 'Joint Exhibit',
            'exhibit_number' => 'EXH/LAB/002',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin Base',
            'quantity' => 1,
            'weight' => 50,
            'weight_unit' => 'g',
        ]);

        // 1. Unsaved Lab Result state for Primary Suspect
        $unsavedResp = $this->actingAs($labOfficer)
            ->get("/Lab_Analysis/cases/{$case->id}/review?suspect=Kwame+Primary");

        $unsavedResp->assertStatus(200);
        $unsavedResp->assertViewIs('Lab_Analysis.show_review');
        $unsavedHtml = $unsavedResp->getContent();

        $this->assertHtmlContains('id="section5LabExamination"', $unsavedHtml);
        $this->assertHtmlContains('id="gsaUploadSection"', $unsavedHtml);
        $this->assertHtmlContains('Report &amp; Laboratory Examination Modules', $unsavedHtml);
        $this->assertHtmlContains('data-active-has-saved-result="0"', $unsavedHtml);
        $this->assertHtmlContains('Cocaine Hydrochloride', $unsavedHtml);
        $this->assertHtmlContains('Heroin Base', $unsavedHtml);
        $this->assertHtmlContains('Joint Exhibit', $unsavedHtml);

        // 2. Saved Lab Result state for Primary Suspect
        LaboratoryExaminationResult::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case->id,
            'suspect_id' => $primary->id,
            'exhibit_id' => $primaryExhibit->id,
            'laboratory_number' => 'LAB/2026/777',
            'exam_reference' => 'NACOC/LAB/REF/777',
            'analyst' => 'Lab Analysis Officer',
            'date_received' => '2026-03-10',
            'date_started' => '2026-03-11',
            'date_completed' => '2026-03-12',
            'presumptive_result' => 'YES (Scott Test Positive)',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Cocaine Hydrochloride',
            'detected_substances' => [['substance' => 'Cocaine', 'percentage' => '90']],
            'findings' => 'Confirmed Cocaine Hydrochloride.',
            'has_saved' => true,
        ]);

        $savedResp = $this->actingAs($labOfficer)
            ->get("/Lab_Analysis/cases/{$case->id}/review?suspect=Kwame+Primary");

        $savedResp->assertStatus(200);
        $savedHtml = $savedResp->getContent();

        $this->assertHtmlContains('data-active-has-saved-result="1"', $savedHtml);
        $this->assertHtmlContains('LAB COMPLETED', $savedHtml);
        $this->assertHtmlContains('NACOC/LAB/REF/777', $savedHtml);
    }

    #[Test]
    public function digital_forensics_view_renders_digital_categories_and_register_navigation(): void
    {
        $dpfOfficer = $this->createOfficer('Digital & Physical Forensics');

        $case = ForensicCase::create([
            'case_number' => 'DPF-P3-002',
            'title' => 'Cyber Forensics Case',
            'unit_name' => 'Digital & Physical Forensics',
            'suspect_name' => 'Adwoa Cyber',
            'exhibit_category' => 'Electronic Devices',
            'status' => 'Pending Approval',
            'user_id' => $dpfOfficer->id,
        ]);

        // Standard DPF view
        $resp = $this->actingAs($dpfOfficer)
            ->get("/Digital_Forensics/cases/{$case->id}/review");

        $resp->assertStatus(200);
        $resp->assertViewIs('Digital_Forensics.show_review');
        $html = $resp->getContent();

        $this->assertHtmlContains('Back to Case Directory', $html);
        $this->assertHtmlContains("selectExhibitCategory('Electronic Devices', event)", $html);
        $this->assertHtmlContains("selectExhibitCategory('Storage Media', event)", $html);
        $this->assertHtmlContains("selectExhibitCategory('Communication Hardware', event)", $html);
        $this->assertHtmlNotContains('id="section5LabExamination"', $html);
        $this->assertHtmlNotContains('id="gsaUploadSection"', $html);
        $this->assertHtmlNotContains('Classify Drugs', $html);

        // DPF Register navigation context
        $regResp = $this->actingAs($dpfOfficer)
            ->get("/Digital_Forensics/cases/{$case->id}/review?from=Digital_Forensics_Register");

        $regResp->assertStatus(200);
        $regResp->assertSee('Back to Digital Forensics Case Register');
    }

    #[Test]
    public function evidence_management_view_renders_exhibit_classification_controls_without_lab_modules(): void
    {
        $emOfficer = $this->createOfficer('Evidence Management');

        $case = ForensicCase::create([
            'case_number' => 'EM-P3-003',
            'title' => 'Vault Custody Case',
            'unit_name' => 'Evidence Management',
            'suspect_name' => 'Yaw Vault',
            'status' => 'Pending Approval',
            'user_id' => $emOfficer->id,
        ]);

        $resp = $this->actingAs($emOfficer)
            ->get("/Evidence_Management/cases/{$case->id}/review");

        $resp->assertStatus(200);
        $resp->assertViewIs('Evidence_Management.show_review');
        $html = $resp->getContent();

        $this->assertHtmlContains('exhibit-type-select', $html);
        $this->assertHtmlContains('drug-classification-select', $html);
        $this->assertHtmlContains('Classify Drugs', $html);
        $this->assertHtmlNotContains('id="section5LabExamination"', $html);
        $this->assertHtmlNotContains('id="gsaUploadSection"', $html);
    }

    #[Test]
    public function crime_scene_support_view_renders_field_seizure_workspace_cleanly(): void
    {
        $cssOfficer = $this->createOfficer('Crime Scene Support');

        $case = ForensicCase::create([
            'case_number' => 'CSS-P3-004',
            'title' => 'Field Raid Case',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Kojo Field',
            'status' => 'Pending Approval',
            'user_id' => $cssOfficer->id,
        ]);

        $resp = $this->actingAs($cssOfficer)
            ->get("/Crime_Scene_Support/cases/{$case->id}/review");

        $resp->assertStatus(200);
        $resp->assertViewIs('Crime_Scene_Support.show_review');
        $html = $resp->getContent();

        $this->assertHtmlContains('CRIME SCENE SUPPORT UNIT', $html);
        $this->assertHtmlContains('Save Seizure / Exhibit', $html);
        $this->assertHtmlNotContains('id="section5LabExamination"', $html);
        $this->assertHtmlNotContains('id="gsaUploadSection"', $html);
        $this->assertHtmlNotContains('Classify Drugs', $html);
    }

    #[Test]
    public function forensic_head_view_enforces_authorization_and_renders_unit_progress_3_of_4_vs_4_of_4(): void
    {
        $hofOfficer = $this->createOfficer('Forensic Directorate', isHead: true, isApproved: true);
        $regularOfficer = $this->createOfficer('Crime Scene Support', isHead: false, isApproved: true);

        // Case with 3/4 units completed (CSS, Lab Analysis, Evidence Management completed; Digital & Physical Forensics pending)
        $case3of4 = MultipleSuspectCase::create([
            'case_number' => 'CSS-P3-3OF4',
            'title' => 'Directorate Review 3 of 4',
            'unit_name' => 'Evidence Management',
            'status' => 'Awaiting Sign-off',
            'user_id' => $regularOfficer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case3of4->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Mensah Three',
        ]);

        $drugExhibit = Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case3of4->id,
            'suspect_id' => $suspect->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Mensah Three',
            'exhibit_number' => 'EXH-1',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
        ]);

        Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case3of4->id,
            'suspect_id' => $suspect->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Mensah Three',
            'exhibit_number' => 'EXH-2',
            'exhibit_category' => 'Weapons',
            'commodity_name' => 'Pistol 9mm',
        ]);

        $elecExhibit = Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case3of4->id,
            'suspect_id' => $suspect->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Mensah Three',
            'exhibit_number' => 'EXH-3',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'iPhone',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case3of4->id,
            'suspect_id' => $suspect->id,
            'exhibit_id' => $drugExhibit->id,
            'laboratory_number' => 'LAB-01',
            'exam_reference' => 'REF-01',
            'analyst' => 'Lab Off',
            'findings' => 'Confirmed Cocaine',
            'has_saved' => true,
        ]);

        // Non-head officer is blocked from Forensic Head route
        $unauthResp = $this->actingAs($regularOfficer)
            ->get("/Forensic_Head/cases/{$case3of4->id}/review");
        $this->assertTrue(in_array($unauthResp->getStatusCode(), [302, 403], true));

        // Approved Forensic Head sees 3/4 units completed
        $resp3of4 = $this->actingAs($hofOfficer)
            ->get("/Forensic_Head/cases/{$case3of4->id}/review");

        $resp3of4->assertStatus(200);
        $resp3of4->assertViewIs('Forensic_Head.show_review');
        $html3of4 = $resp3of4->getContent();

        $this->assertHtmlContains('Back to Case Register', $html3of4);
        $this->assertHtmlContains('3/4 Units Completed', $html3of4);
        $this->assertStringNotContainsString('Approve &amp; Sign-Off Case', $html3of4);
        $this->assertStringNotContainsString('Decline Docket', $html3of4);

        // Remove pending electronic exhibit so all 3 remaining involved units are completed
        $elecExhibit->delete();

        $respAllDone = $this->actingAs($hofOfficer)
            ->get("/Forensic_Head/cases/{$case3of4->id}/review");

        $respAllDone->assertStatus(200);
        $htmlAllDone = $respAllDone->getContent();

        $this->assertHtmlContains('3/3 Units Completed', $htmlAllDone);
        $this->assertHtmlContains('ALL UNITS COMPLETED', $htmlAllDone);
        $this->assertStringNotContainsString('Approve &amp; Sign-Off Case', $htmlAllDone);
        $this->assertStringNotContainsString('Decline Docket', $htmlAllDone);
    }

    #[Test]
    public function legacy_review_urls_redirect_to_per_unit_routes_preserving_query_params(): void
    {
        $this->followLegacyReviewRedirects = false;

        $officer = $this->createOfficer('Crime Scene Support');

        $case = ForensicCase::create([
            'case_number' => 'LEG-P3-005',
            'title' => 'Legacy Redirect Test',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Ama Legacy',
            'status' => 'Pending Approval',
            'user_id' => $officer->id,
        ]);

        $this->actingAs($officer)
            ->get("/cases/{$case->id}/review?from=Crime_Scene_Support")
            ->assertRedirect("/Crime_Scene_Support/cases/{$case->id}/review?from=Crime_Scene_Support");

        $this->actingAs($officer)
            ->get("/cases/{$case->id}/review?from=Lab_Analysis&suspect=Ama+Legacy")
            ->assertRedirect("/Lab_Analysis/cases/{$case->id}/review?from=Lab_Analysis&suspect=Ama%20Legacy");

        $this->actingAs($officer)
            ->get("/cases/{$case->id}/review?from=Digital_Forensics")
            ->assertRedirect("/Digital_Forensics/cases/{$case->id}/review?from=Digital_Forensics");

        $this->actingAs($officer)
            ->get("/cases/{$case->id}/review?from=Evidence_Management")
            ->assertRedirect("/Evidence_Management/cases/{$case->id}/review?from=Evidence_Management");

        // Kebab-case aliases redirect to canonical per-unit routes
        $this->actingAs($officer)
            ->get("/lab-analysis/cases/{$case->id}/review?suspect=Ama")
            ->assertRedirect("/Lab_Analysis/cases/{$case->id}/review?suspect=Ama");

        $this->actingAs($officer)
            ->get("/digital-forensics/cases/{$case->id}/review")
            ->assertRedirect("/Digital_Forensics/cases/{$case->id}/review");

        $this->actingAs($officer)
            ->get("/evidence-management/cases/{$case->id}/review")
            ->assertRedirect("/Evidence_Management/cases/{$case->id}/review");

        $this->actingAs($officer)
            ->get("/crime-scene-support/cases/{$case->id}/review")
            ->assertRedirect("/Crime_Scene_Support/cases/{$case->id}/review");
    }
}
