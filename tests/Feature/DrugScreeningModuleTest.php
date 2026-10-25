<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugScreeningModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function drug_screening_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('drug_screening'), 'Table drug_screening should exist.');

        $expectedColumns = [
            'id',
            'suspect_id',
            'purpose',
            'screening_date',
            'result',
            'substances_found',
            'notes',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(
                Schema::hasColumn('drug_screening', $col),
                "Column '{$col}' should exist in drug_screening table."
            );
        }
    }

    #[Test]
    public function sidebar_contains_drug_screening_button_in_lab_analysis_section(): void
    {
        $user = User::factory()->create([
            'fullname' => 'Lab Chemist',
            'unit' => 'Forensic Laboratory',
            'is_head' => false,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/lab-result');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('DRUG SCREENING', $html);
        $this->assertStringContainsString('/Lab_Analysis/drug-screening', $html);
    }

    #[Test]
    public function suspect_dropdown_displays_all_suspects_in_database_as_single_entries_with_names_only(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-MULTI-' . uniqid(),
            'title' => 'State vs Multi Suspect Syndicate',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        // Multiple suspects in the same case - each should be treated as single
        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo Darko Multi A',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Kwame Mensah Multi B',
            'role' => 'Associate Suspect',
        ]);

        // Single suspect case in single_suspect_cases table
        $sCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'LAB-SNGL-' . uniqid(),
            'title' => 'State vs Solo Suspect',
            'unit_name' => 'Forensic Laboratory',
            'suspect_name' => 'Ama Solo Suspect C',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/create');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. All suspects must be present
        $this->assertStringContainsString('Kojo Darko Multi A', $html);
        $this->assertStringContainsString('Kwame Mensah Multi B', $html);
        $this->assertStringContainsString('Ama Solo Suspect C', $html);

        // 2. Options must show their name only (not cluttered with case number or eligibility tags)
        $this->assertMatchesRegularExpression('/>\s*Kojo Darko Multi A\s*<\/option>/', $html);
        $this->assertMatchesRegularExpression('/>\s*Kwame Mensah Multi B\s*<\/option>/', $html);
        $this->assertMatchesRegularExpression('/>\s*Ama Solo Suspect C\s*<\/option>/', $html);
    }

    #[Test]
    public function user_can_submit_drug_screening_form_and_persist_to_database(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-SCR-' . uniqid(),
            'title' => 'Operation Clean Street',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Emmanuel Osei',
            'role' => 'Primary Suspect',
        ]);

        // Submit Entry Form
        $postData = [
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Court-Ordered Pre-Trial Chemical Assessment',
            'screening_date' => '2026-10-21',
            'result'         => 'Positive',
            'notes'          => 'Rapid 10-panel urine assay tested positive for Cocaine Metabolites (BZE) and Opiates.',
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $postData);
        $response->assertRedirect('/Lab_Analysis/drug-screening');

        // Verify database entry
        $this->assertDatabaseHas('drug_screening', [
            'suspect_id' => $suspect->id,
            'purpose'    => 'Court-Ordered Pre-Trial Chemical Assessment',
            'result'     => 'Positive',
        ]);

        $screeningRecord = DrugScreening::where('suspect_id', $suspect->id)->first();
        $this->assertNotNull($screeningRecord);
        $this->assertEquals('2026-10-21', $screeningRecord->screening_date->format('Y-m-d'));

        // Verify record is rendered in list view
        $listResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $listResponse->assertStatus(200);
        $listHtml = $listResponse->getContent();

        $this->assertStringContainsString('Emmanuel Osei', $listHtml);
        $this->assertStringContainsString('Court-Ordered Pre-Trial Chemical Assessment', $listHtml);
        $this->assertStringContainsString('POSITIVE', $listHtml);

        // Verify top-right flash toast with white background
        $flashResponse = $this->actingAs($user)
            ->withSession(['success' => 'Drug screening record for COMFORT KINGS recorded successfully (Result: Negative).'])
            ->get('/Lab_Analysis/drug-screening');
        $flashResponse->assertStatus(200);
        $flashHtml = $flashResponse->getContent();

        $this->assertStringContainsString('id="topRightFlashToast"', $flashHtml);
        $this->assertStringContainsString('fixed top-5 right-5', $flashHtml);
        $this->assertStringContainsString('bg-white', $flashHtml);
        $this->assertStringContainsString('Drug screening record for COMFORT KINGS recorded successfully (Result: Negative).', $flashHtml);
    }

    #[Test]
    public function user_can_delete_drug_screening_record(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-DEL-' . uniqid(),
            'title' => 'State vs Delete Test',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Franklyn Mensah',
            'role' => 'Primary Suspect',
        ]);

        $screening = DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Routine Custody Screening',
            'screening_date' => '2026-10-21',
            'result'         => 'Negative',
            'notes'          => 'Clear results across all panels.',
        ]);

        $this->assertDatabaseHas('drug_screening', ['id' => $screening->id]);

        $response = $this->actingAs($user)->delete("/Lab_Analysis/drug-screening/{$screening->id}");
        $response->assertRedirect('/Lab_Analysis/drug-screening');

        $this->assertDatabaseMissing('drug_screening', ['id' => $screening->id]);
    }

    #[Test]
    public function screening_dossiers_table_displays_suspect_image_correctly_for_data_uri_and_fallback(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-IMG-' . uniqid(),
            'title' => 'Image Test Dossier',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        // Suspect 1 with base64 data URI image
        $dataUri = 'data:image/avif;base64,AAAAHGZ0eXBhdmlmAAAAAG1pZjEAAAEAA';
        $suspectWithImage = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo Image Tester',
            'role' => 'Primary Suspect',
            'mugshot_front' => $dataUri,
        ]);

        // Suspect 2 without image
        $suspectWithoutImage = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Ama Fallback Tester',
            'role' => 'Associate Suspect',
            'mugshot_front' => null,
        ]);

        DrugScreening::create([
            'suspect_id'     => $suspectWithImage->id,
            'purpose'        => 'Verification of Image Rendering',
            'screening_date' => '2026-10-21',
            'result'         => 'Positive',
        ]);

        DrugScreening::create([
            'suspect_id'     => $suspectWithoutImage->id,
            'purpose'        => 'Verification of Fallback Avatar',
            'screening_date' => '2026-10-21',
            'result'         => 'Negative',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Data URI must NOT be prepended by asset URL (http://.../data:image)
        $this->assertStringNotContainsString('http://localhost/data:image', $html);
        $this->assertStringNotContainsString('/data:image/avif;base64', $html);

        // 2. Data URI must be directly present as image src
        $this->assertStringContainsString('src="' . $dataUri . '"', $html);

        // 3. Suspect without image must display their initials (AM)
        $this->assertStringContainsString('AM', $html);
    }

    #[Test]
    public function selecting_negative_displays_label_and_positive_provides_substances_input(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-SUB-' . uniqid(),
            'title' => 'Substance Test Case',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kofi Toxic Sample',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Abena Clean Sample',
            'role' => 'Associate Suspect',
        ]);

        // 1. Verify Entry Form UI has the positive input box and negative label elements
        $formResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/create');
        $formResponse->assertStatus(200);
        $formHtml = $formResponse->getContent();

        $this->assertStringContainsString('id="positiveSubstanceBox"', $formHtml);
        $this->assertStringContainsString('id="substancesFoundInput"', $formHtml);
        $this->assertStringContainsString('Name of Substance(s) Found After Screening', $formHtml);
        $this->assertStringContainsString('id="negativeSubstanceLabel"', $formHtml);
        $this->assertStringContainsString('No substance(s) found', $formHtml);

        // 2. Submit Positive record with entered substances
        $posResponse = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', [
            'suspect_id'       => $suspect1->id,
            'purpose'          => 'Pre-Trial Substance Toxicology Assessment',
            'screening_date'   => '2026-10-21',
            'result'           => 'Positive',
            'substances_found' => 'Cocaine, Methamphetamine',
            'notes'            => 'Strong confirmation on chemical strips.',
        ]);
        $posResponse->assertRedirect('/Lab_Analysis/drug-screening');

        $this->assertDatabaseHas('drug_screening', [
            'suspect_id'       => $suspect1->id,
            'result'           => 'Positive',
            'substances_found' => 'Cocaine, Methamphetamine',
        ]);

        // 3. Submit Negative record - automatically sets No substance(s) found
        $negResponse = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', [
            'suspect_id'       => $suspect2->id,
            'purpose'          => 'Follow-up Clearance Screening',
            'screening_date'   => '2026-10-21',
            'result'           => 'Negative',
        ]);
        $negResponse->assertRedirect('/Lab_Analysis/drug-screening');

        $this->assertDatabaseHas('drug_screening', [
            'suspect_id'       => $suspect2->id,
            'result'           => 'Negative',
            'substances_found' => 'No substance(s) found',
        ]);

        // 4. Verify Dossiers table view shows both the positive substances and negative label
        $listResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $listResponse->assertStatus(200);
        $listHtml = $listResponse->getContent();

        $this->assertStringContainsString('Cocaine, Methamphetamine', $listHtml);
        $this->assertStringContainsString('No substance(s) found', $listHtml);
    }

    #[Test]
    public function already_screened_suspect_triggers_popup_modal_and_updates_database_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-UPD-' . uniqid(),
            'title' => 'Update Screening Case',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Akwesi Repeat Suspect',
            'role' => 'Primary Suspect',
        ]);

        // Create initial screening record in drug_screening table
        $initialRecord = DrugScreening::create([
            'suspect_id'       => $suspect->id,
            'purpose'          => 'Initial Arrest Screening',
            'screening_date'   => '2026-10-15',
            'result'           => 'Pending',
            'substances_found' => null,
            'notes'            => 'Initial intake sample taken.',
        ]);

        $this->assertDatabaseHas('drug_screening', [
            'id'               => $initialRecord->id,
            'suspect_id'       => $suspect->id,
            'result'           => 'Pending',
        ]);

        // 1. Verify create form includes the Already Screened modal popup and buttons
        $createResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/create');
        $createResponse->assertStatus(200);
        $createHtml = $createResponse->getContent();

        $this->assertStringContainsString('id="alreadyScreenedModal"', $createHtml);
        $this->assertStringContainsString('This suspect has already been screened.', $createHtml);
        $this->assertStringContainsString('Do you want to update/edit or cancel?', $createHtml);
        $this->assertStringContainsString('cancelAlreadyScreenedModal()', $createHtml);
        $this->assertStringContainsString('confirmUpdateScreening()', $createHtml);
        $this->assertStringContainsString('Update / Edit', $createHtml);
        $this->assertStringContainsString('Cancel', $createHtml);
        $this->assertStringContainsString('const screenedSuspects =', $createHtml);
        $this->assertStringContainsString('Akwesi Repeat Suspect', $createHtml);

        // 2. Click Update / Edit and submit update via form
        $updateData = [
            'screening_id'     => $initialRecord->id,
            'suspect_id'       => $suspect->id,
            'purpose'          => 'Updated Post-Lab Chemical Assay',
            'screening_date'   => '2026-10-21',
            'result'           => 'Positive',
            'substances_found' => 'Cannabis (THC), Cocaine',
            'notes'            => 'Upgraded to positive after mass spectrometry verification.',
        ];

        $postUpdateResponse = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $updateData);
        $postUpdateResponse->assertRedirect('/Lab_Analysis/drug-screening');
        $postUpdateResponse->assertSessionHas('success');

        // 3. Verify drug_screening database table was updated (NOT duplicated)
        $this->assertEquals(1, DrugScreening::where('suspect_id', $suspect->id)->count());

        $updatedRecord = DrugScreening::find($initialRecord->id);
        $this->assertEquals('Updated Post-Lab Chemical Assay', $updatedRecord->purpose);
        $this->assertEquals('Positive', $updatedRecord->result);
        $this->assertEquals('Cannabis (THC), Cocaine', $updatedRecord->substances_found);
        $this->assertEquals('2026-10-21', $updatedRecord->screening_date->format('Y-m-d'));
        $this->assertEquals('Upgraded to positive after mass spectrometry verification.', $updatedRecord->notes);

        // 4. Also verify direct PUT route /Lab_Analysis/drug-screening/{id}
        $putData = [
            'suspect_id'       => $suspect->id,
            'purpose'          => 'Re-screened Negative on Detox',
            'screening_date'   => '2026-10-22',
            'result'           => 'Negative',
            'notes'            => 'Clean sample.',
        ];
        $putResponse = $this->actingAs($user)->put("/Lab_Analysis/drug-screening/{$initialRecord->id}", $putData);
        $putResponse->assertRedirect('/Lab_Analysis/drug-screening');

        $finalRecord = DrugScreening::find($initialRecord->id);
        $this->assertEquals('Negative', $finalRecord->result);
        $this->assertEquals('No substance(s) found', $finalRecord->substances_found);
        $this->assertEquals('Re-screened Negative on Detox', $finalRecord->purpose);
    }

    #[Test]
    public function screening_result_is_attached_to_specific_suspect_docket_and_viewable_across_system(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $caseNumber = 'NCC-LAB-' . strtoupper(uniqid());
        $mCase = MultipleSuspectCase::create([
            'case_number' => $caseNumber,
            'title' => 'Operation Golden Shield',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'status' => 'Under Investigation',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kofi Docket Screening Test',
            'role' => 'Primary Suspect',
            'holding_facility' => 'NACOC Safe Center 3',
        ]);

        // Submit and save drug screening result for this suspect
        $postData = [
            'suspect_id'       => $suspect->id,
            'purpose'          => 'Pre-Trial Toxicological Assessment',
            'screening_date'   => '2026-10-21',
            'result'           => 'Positive',
            'substances_found' => 'Cocaine, Heroin',
            'notes'            => 'Multipanel chemical strip confirmed high concentrations.',
        ];

        $saveResponse = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $postData);
        $saveResponse->assertRedirect('/Lab_Analysis/drug-screening');

        // 1. Verify model level attachment
        $screening = DrugScreening::forSuspect($suspect->id);
        $this->assertNotNull($screening);
        $this->assertEquals('Positive', $screening->result);
        $this->assertEquals(['Cocaine', 'Heroin'], $screening->substance_list);
        $this->assertEquals('Kofi Docket Screening Test', $suspect->fresh()->latestDrugScreening->suspect->suspect_name);

        // 2. Verify Case Docket Review page (/cases/{id}/review) has attached screening report as a TABLE
        // Lab Analysis user should see table AND the buttons [View Screening Register] & [Update / Re-Screen]
        $docketResponse = $this->actingAs($user)->get("/cases/{$mCase->id}/review?unit=Lab+Analysis");
        $docketResponse->assertStatus(200);
        $docketHtml = $docketResponse->getContent();

        $this->assertStringContainsString('DRUG SCREENING: POSITIVE', $docketHtml);
        $this->assertStringContainsString('Drug Screening Report', $docketHtml);
        $this->assertStringContainsString('Kofi Docket Screening Test', $docketHtml);
        $this->assertStringContainsString('Screening Dossier #', $docketHtml);
        $this->assertStringContainsString('Date of Screening', $docketHtml);
        $this->assertStringContainsString('Purpose of Screening', $docketHtml);
        $this->assertStringContainsString('Screening Outcome', $docketHtml);
        $this->assertStringContainsString('Substance(s) Found', $docketHtml);
        $this->assertStringContainsString('Remarks / Notes', $docketHtml);
        $this->assertStringContainsString('Cocaine', $docketHtml);
        $this->assertStringContainsString('Heroin', $docketHtml);
        $this->assertStringContainsString('Pre-Trial Toxicological Assessment', $docketHtml);

        // Lab Analysis MUST show the action buttons
        $this->assertStringContainsString('View Screening Register', $docketHtml);
        $this->assertStringContainsString('Update / Re-Screen', $docketHtml);

        // 3. Verify other units (e.g. Crime Scene Support / Evidence Management) show the table but DO NOT show the buttons
        $crimeSceneUser = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
        ]);

        $otherUnitResponse = $this->actingAs($crimeSceneUser)->get("/cases/{$mCase->id}/review?unit=Crime+Scene+Support");
        $otherUnitResponse->assertStatus(200);
        $otherUnitHtml = $otherUnitResponse->getContent();

        // Must still show the report table
        $this->assertStringContainsString('Drug Screening Report', $otherUnitHtml);
        $this->assertStringContainsString('Screening Dossier #', $otherUnitHtml);
        $this->assertStringContainsString('Remarks / Notes', $otherUnitHtml);
        $this->assertStringContainsString('Cocaine', $otherUnitHtml);
        // Must NOT show the action buttons
        $this->assertStringNotContainsString('View Screening Register', $otherUnitHtml);
        $this->assertStringNotContainsString('Update / Re-Screen', $otherUnitHtml);

        // 4. Verify Case PDF (/cases/{id}/pdf) has attached clinical drug screening report table
        $pdfResponse = $this->actingAs($user)->get("/cases/{$mCase->id}/pdf");
        $pdfResponse->assertStatus(200);
        $pdfHtml = $pdfResponse->getContent();

        $this->assertStringContainsString('DRUG SCREENING REPORT', $pdfHtml);
        $this->assertStringContainsString('KOFI DOCKET SCREENING TEST', $pdfHtml);
        $this->assertStringContainsString('SCREENING OUTCOME: POSITIVE', $pdfHtml);
        $this->assertStringContainsString('Cocaine, Heroin', $pdfHtml);

        // 5. Verify API endpoint returns suspect screening record JSON (/api/suspect/{id}/drug-screening)
        $apiResponse = $this->actingAs($user)->getJson("/api/suspect/{$suspect->id}/drug-screening");
        $apiResponse->assertStatus(200);
        $apiResponse->assertJson([
            'success'       => true,
            'has_screening' => true,
            'screening'     => [
                'suspect_id'       => $suspect->id,
                'suspect_name'     => 'Kofi Docket Screening Test',
                'result'           => 'Positive',
                'substances_found' => 'Cocaine, Heroin',
                'substance_list'   => ['Cocaine', 'Heroin'],
            ],
        ]);
    }
}


