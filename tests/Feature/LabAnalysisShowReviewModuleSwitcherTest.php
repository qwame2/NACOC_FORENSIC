<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabAnalysisShowReviewModuleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = User::factory()->create([
            'unit' => 'Laboratory Analysis',
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function lab_analysis_show_review_displays_report_and_laboratory_examination_modules(): void
    {
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-999',
            'title' => 'Republic v Test Suspect',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kofi Amoah',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kofi Amoah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin',
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?unit=Lab+Analysis&suspect=Kofi+Amoah');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Module Header and Subtitle
        $this->assertStringContainsString('Report &amp; Laboratory Examination Modules', $html);
        $this->assertStringContainsString('Switch between uploading GSA Report Files and filling Section 5 Examination Information (NLR)', $html);

        // 2. Action Buttons
        $this->assertStringContainsString('id="toggleGsaReportBtn"', $html);
        $this->assertStringContainsString('Upload GSA File', $html);
        $this->assertStringContainsString('id="toggleNlrReportBtn"', $html);
        $this->assertStringContainsString('Examination Information (NLR)', $html);

        // 3. Sections for GSA Upload and Section 5 NLR Examination
        $this->assertStringContainsString('id="gsaUploadSection"', $html);
        $this->assertStringContainsString('id="section5LabExamination"', $html);
        $this->assertStringNotContainsString('id="saveGsaFilesBtn"', $html);
        $this->assertStringContainsString('id="saveSection4SeizureBtn"', $html);
        $this->assertStringContainsString('id="section4ActionButtons"', $html);
        $this->assertStringContainsString('id="gsaActionButtons"', $html);
        $this->assertStringContainsString('id="saveGsaDetailsBtn"', $html);
        $this->assertStringContainsString('Save Seizure Item &amp; GSA Details', $html);

        // 4. Container Placement: Report & Laboratory Examination Modules is under Seizure Item container (#section4Seizures)
        $posSection4 = strpos($html, 'id="section4Seizures"');
        $posModuleNav = strpos($html, 'Report &amp; Laboratory Examination Modules');
        $posGsaSection = strpos($html, 'id="gsaUploadSection"');
        $posSection5Form = strpos($html, 'id="section5FormContainer"');

        $this->assertNotFalse($posSection4, 'section4Seizures container should exist');
        $this->assertNotFalse($posModuleNav, 'Report & Laboratory Examination Modules should exist');
        $this->assertNotFalse($posGsaSection, 'gsaUploadSection should exist');
        $this->assertNotFalse($posSection5Form, 'section5FormContainer should exist');

        $this->assertTrue($posSection4 < $posModuleNav, 'Report & Laboratory Examination Modules must appear under Section 4 Seizures container');
        $this->assertTrue($posModuleNav < $posGsaSection, 'gsaUploadSection must follow module navigation');
        $this->assertTrue($posGsaSection < $posSection5Form, 'Section 5 form container must follow gsaUploadSection');

        // 5. Client-side Switcher and Staging Handlers
        $this->assertStringContainsString('function switchToGsaUploadSection()', $html);
        $this->assertStringContainsString('function switchToNlrExamSection()', $html);
        $this->assertStringContainsString('function saveGsaReportFiles()', $html);

        // 6. Confirmatory and Presumptive Test default states are neutral and empty
        $this->assertDoesNotMatchRegularExpression('/id="confirmatoryTestYesReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="confirmatoryTestNoReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="presumptiveTestYesReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="presumptiveTestNoReview"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightContainerReview"[^>]*class="[^"]*hidden[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightDescriptionReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="confirmatorySubstanceIdentificationReview"[^>]*value=""/', $html);

        // 7. Neutral state on initial page load / refresh
        $this->assertStringContainsString('id="resetLabModulesBtn"', $html);
        $this->assertStringContainsString('function resetLabModulesToNeutral()', $html);
        $this->assertMatchesRegularExpression('/id="section5FormContainer"[^>]*class="[^"]*hidden[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/id="gsaUploadSection"[^>]*class="[^"]*hidden[^"]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="toggleNlrReportBtn"[^>]*class="[^"]*ring-2[^"]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="toggleGsaReportBtn"[^>]*class="[^"]*ring-2[^"]*"/', $html);

        // 8. Exhibit No. input in Select Seized Exhibit and removal of Auto-Linked Exhibit Number from Examination Information
        $this->assertStringContainsString('Exhibit No.', $html);
        $this->assertStringContainsString('id="labExhibitNumberInput"', $html);
        $this->assertDoesNotMatchRegularExpression('/<span>Exhibit Number<\/span>\s*<span[^>]*>Auto-Linked<\/span>/', $html);
    }

    #[Test]
    public function lab_analysis_create_page_defaults_to_neutral_state_with_reset_button(): void
    {
        $response = $this->actingAs($this->officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertStringContainsString('Report &amp; Laboratory Examination Modules', $html);
        $this->assertStringContainsString('id="toggleGsaReportBtn"', $html);
        $this->assertStringContainsString('id="toggleNlrReportBtn"', $html);
        $this->assertStringContainsString('id="resetLabModulesBtn"', $html);
        $this->assertStringContainsString('function resetLabModulesToNeutral()', $html);
        $this->assertMatchesRegularExpression('/id="labAnalysisExclusiveSections"[^>]*class="[^"]*hidden[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/id="gsaUploadSection"[^>]*class="[^"]*hidden[^"]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="toggleNlrReportBtn"[^>]*class="[^"]*ring-2[^"]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="toggleGsaReportBtn"[^>]*class="[^"]*ring-2[^"]*"/', $html);

        // Exhibit No. input in Select Seized Exhibit cards and removal of Auto-Linked Exhibit Number from Examination Information
        $this->assertStringContainsString('Exhibit No.', $html);
        $this->assertDoesNotMatchRegularExpression('/<span>Exhibit Number<\/span>\s*<span[^>]*>Auto-Linked<\/span>/', $html);
    }

    #[Test]
    public function lab_examination_form_inputs_are_empty_when_examination_information_is_lab_completed(): void
    {
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-COMPLETED',
            'title' => 'Republic v Completed Suspect',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwame Mensah',
        ]);

        $exhibit = Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kwame Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Powder',
            'quantity' => 5,
            'packaging' => 'Sachets',
            'weight' => 250,
            'weight_unit' => 'Grams (g)',
        ]);

        \App\Models\LaboratoryExaminationResult::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case->id,
            'suspect_id' => $suspect->id,
            'exhibit_id' => $exhibit->id,
            'laboratory_number' => 'LAB-NUM-999',
            'exam_reference' => 'NLA-FM-T999-F',
            'analyst' => 'Forensic Chemist',
            'date_received' => '2026-10-15',
            'date_started' => '2026-10-16',
            'date_completed' => '2026-10-18',
            'presumptive_result' => 'YES (Cobalt Thiocyanate Positive)',
            'confirmatory_result' => 'YES',
            'sample_weight_description' => 'Cocaine Powder',
            'sample_weight_units' => '5 Sachets',
            'sample_weight_gross' => '250 Grams (g)',
            'sample_weight_taken' => '10',
            'sample_weight_reserved' => '240',
            'substance_identification' => 'Cocaine Hydrochloride',
            'detected_substances' => [['substance' => 'Cocaine', 'percentage' => '85.5']],
            'test_methods' => 'GC-MS / FTIR analysis',
            'specifications' => 'Purity verified against NIST standard library.',
            'remark' => 'High purity sample consistent with illicit cocaine shipment.',
            'findings' => 'Confirmed Cocaine purity 85.5%',
            'has_saved' => true,
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?unit=Lab+Analysis&suspect=Kwame+Mensah');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Verify badge indicates completed status
        $this->assertStringContainsString('LAB COMPLETED', $html);

        // Verify that Section 5 Form inputs are emptied
        $this->assertMatchesRegularExpression('/id="labExamReferenceInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="labCaseReferenceInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="labExhibitNumberInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="formLabNumber"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="labDateReceivedInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="labDateStartedInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="labDateCompletedInput"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightDescriptionReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightUnitsReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightGrossReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightTakenReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="sampleWeightReservedReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="confirmatorySubstanceIdentificationReview"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="confirmatoryTestMethodsReview"[^>]*value=""/', $html);

        // Verify textareas are empty inside the form
        $this->assertMatchesRegularExpression('/<textarea id="confirmatorySpecificationsReview"[^>]*>\s*<\/textarea>/', $html);
        $this->assertMatchesRegularExpression('/<textarea id="confirmatoryRemarkReview"[^>]*>\s*<\/textarea>/', $html);
        $this->assertMatchesRegularExpression('/<textarea id="formLabNotes"[^>]*>\s*<\/textarea>/', $html);

        // Verify radio buttons in Section 5 Form are NOT checked
        $this->assertDoesNotMatchRegularExpression('/id="presumptiveTestYesReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="presumptiveTestNoReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="confirmatoryTestYesReview"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="confirmatoryTestNoReview"[^>]*checked/', $html);
    }

    #[Test]
    public function gsa_and_nlr_complete_disabling_and_mutual_exclusivity_logic_in_both_create_and_show_review(): void
    {
        // 1. Verify create.blade.php implementation
        $createResponse = $this->actingAs($this->officer)->get('/Lab_Analysis/create');
        $createResponse->assertStatus(200);
        $createHtml = $createResponse->getContent();

        // GSA switch logic in create.blade.php
        $this->assertStringContainsString("window.activeLabWorkflowMode = 'gsa'", $createHtml);
        $this->assertStringContainsString("nlrBtn.disabled = true;", $createHtml);
        $this->assertStringContainsString("nlrBtn.setAttribute('aria-disabled', 'true')", $createHtml);
        $this->assertStringContainsString("nlrBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none')", $createHtml);
        $this->assertStringContainsString("el.setAttribute('data-gsa-disabled', 'true')", $createHtml);
        $this->assertStringContainsString("gsaBtn.disabled = true;", $createHtml);
        $this->assertStringContainsString("el.setAttribute('data-nlr-disabled', 'true')", $createHtml);

        // 2. Verify show_review.blade.php implementation
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-MUTUAL-TEST',
            'title' => 'Republic v Mutual Test',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);
        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kojo Antwi',
        ]);
        Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Kojo Antwi',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cannabis Sativa',
        ]);

        $reviewResponse = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?unit=Lab+Analysis&suspect=Kojo+Antwi');
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        // GSA switch logic in show_review.blade.php
        $this->assertStringContainsString("window.activeLabWorkflowMode = 'gsa'", $reviewHtml);
        $this->assertStringContainsString("nlrBtn.disabled = true;", $reviewHtml);
        $this->assertStringContainsString("nlrBtn.setAttribute('aria-disabled', 'true')", $reviewHtml);
        $this->assertStringContainsString("nlrBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none')", $reviewHtml);
        $this->assertStringContainsString("el.setAttribute('data-gsa-disabled', 'true')", $reviewHtml);
        $this->assertStringContainsString("gsaBtn.disabled = true;", $reviewHtml);
        $this->assertStringContainsString("el.setAttribute('data-nlr-disabled', 'true')", $reviewHtml);

        // Guard on onReviewLabExhibitChange
        $this->assertStringContainsString("if (window.activeLabWorkflowMode === 'nlr')", $reviewHtml);
        $this->assertStringContainsString("if (window.activeLabWorkflowMode === 'gsa')", $reviewHtml);
    }

    #[Test]
    public function nlr_examination_button_shows_form_and_removes_duplicate_laboratory_examination_result_table(): void
    {
        $case = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-NLR-DUPE-TEST',
            'title' => 'Republic v NLR Dupe Check',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $this->officer->id,
            'total_suspects_count' => 1,
        ]);
        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Ama Serwaa',
        ]);
        $exhibit = Exhibit::create([
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'Ama Serwaa',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine',
        ]);

        \App\Models\LaboratoryExaminationResult::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $case->id,
            'suspect_id' => $suspect->id,
            'exhibit_id' => $exhibit->id,
            'laboratory_number' => 'LAB-NUM-888',
            'exam_reference' => 'NLA-FM-T888-F',
            'analyst' => 'Forensic Chemist',
            'date_received' => '2026-10-15',
            'date_started' => '2026-10-16',
            'date_completed' => '2026-10-18',
            'presumptive_result' => 'YES (Marquis Positive)',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Methamphetamine',
            'has_saved' => true,
        ]);

        $response = $this->actingAs($this->officer)->get('/cases/' . $case->id . '/review?unit=Lab+Analysis&suspect=Ama+Serwaa');
        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Ensure duplicate read-only table is completely removed from section5ReadOnlyDisplay
        $this->assertStringNotContainsString('id="displayLabExamRef"', $html);
        $this->assertStringNotContainsString('id="section5RemarkDisplay"', $html);
        $this->assertStringNotContainsString('id="section5FindingsDisplay"', $html);
        $this->assertStringNotContainsString('STRUCTURED READ-ONLY LABORATORY EXAMINATION TABLE', $html);

        // 2. Ensure switchToNlrExamSection shows form container instead of readOnlyView
        $this->assertStringContainsString("nlrSec.classList.add('hidden')", $html);
        $this->assertStringContainsString("readOnlyView.classList.add('hidden')", $html);
        $this->assertStringContainsString("nlrForm.classList.remove('hidden')", $html);
        $this->assertStringContainsString("const targetSec = nlrForm;", $html);

        // 3. Ensure Examination Information Form is present
        $this->assertStringContainsString('id="section5FormContainer"', $html);
        $this->assertStringContainsString('id="section5LabForm"', $html);
    }
}
