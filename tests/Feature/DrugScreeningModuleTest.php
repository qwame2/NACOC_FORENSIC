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
            'civilian_name',
            'purpose',
            'screening_date',
            'result',
            'substances_found',
            'notes',
            'on_medication',
            'medication_summary',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(
                Schema::hasColumn('drug_screening', $col),
                "Column '{$col}' should exist in drug_screening table."
            );
        }

        $this->assertTrue(Schema::hasTable('drug_screening_medications'), 'Table drug_screening_medications should exist.');
        $expectedMedColumns = [
            'id',
            'drug_screening_id',
            'medication_name',
            'dosage',
            'form',
            'frequency',
            'route',
            'prescription_source',
            'date_started',
            'duration_ongoing',
            'last_dose_taken',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedMedColumns as $mCol) {
            $this->assertTrue(
                Schema::hasColumn('drug_screening_medications', $mCol),
                "Column '{$mCol}' should exist in drug_screening_medications table."
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

        // 2. Options must show their name (labeled as Suspect)
        $this->assertMatchesRegularExpression('/>\s*Kojo Darko Multi A(\s*\(Suspect\))?\s*<\/option>/', $html);
        $this->assertMatchesRegularExpression('/>\s*Kwame Mensah Multi B(\s*\(Suspect\))?\s*<\/option>/', $html);
        $this->assertMatchesRegularExpression('/>\s*Ama Solo Suspect C(\s*\(Suspect\))?\s*<\/option>/', $html);
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
        $this->assertStringContainsString('No drug(s) detected', $formHtml);

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

        // 3. Submit Negative record - automatically sets No drug(s) detected
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
            'substances_found' => 'No drug(s) detected',
        ]);

        // 4. Verify Dossiers table view shows both the positive substances and negative label
        $listResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $listResponse->assertStatus(200);
        $listHtml = $listResponse->getContent();

        $this->assertStringContainsString('Cocaine, Methamphetamine', $listHtml);
        $this->assertStringContainsString('No drug(s) detected', $listHtml);
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
        $this->assertEquals('No drug(s) detected', $finalRecord->substances_found);
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

    #[Test]
    public function civilian_name_column_exists_and_suspect_id_is_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('drug_screening', 'civilian_name'), 'Column civilian_name must exist.');
        $this->assertTrue(Schema::hasColumn('drug_screening', 'suspect_id'), 'Column suspect_id must exist.');

        // Test inserting a row with null suspect_id and set civilian_name
        $screening = DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Kwame Civilian Tester',
            'purpose'        => 'Volunteer Toxicology Check',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        $this->assertNotNull($screening->id);
        $this->assertNull($screening->suspect_id);
        $this->assertEquals('Kwame Civilian Tester', $screening->civilian_name);
        $this->assertTrue($screening->isCivilian());
        $this->assertFalse($screening->isSuspect());
        $this->assertEquals('Kwame Civilian Tester', $screening->person_name);
    }

    #[Test]
    public function user_can_submit_screening_for_civilian_with_civilian_name_persisted_and_suspect_id_null(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        // 1. Submit civilian screening via civilian_name input
        $civData = [
            'civilian_name'  => 'Akosua Mensah Civilian',
            'purpose'        => 'Pre-Employment Toxicological Assessment',
            'screening_date' => '2026-11-04',
            'result'         => 'Positive',
            'substances_found' => 'Cannabis (THC)',
            'notes'          => 'Client walked in for voluntary job screening.',
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $civData);
        $response->assertRedirect('/Lab_Analysis/drug-screening');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('drug_screening', [
            'civilian_name'    => 'Akosua Mensah Civilian',
            'suspect_id'       => null,
            'result'           => 'Positive',
            'substances_found' => 'Cannabis (THC)',
        ]);

        // 2. Submit civilian screening where select2 tag passes civilian:... in suspect_id
        $tagData = [
            'suspect_id'     => 'civilian:Kojo Walkin Tagged',
            'purpose'        => 'Routine Health Check',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
        ];

        $response2 = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $tagData);
        $response2->assertRedirect('/Lab_Analysis/drug-screening');

        $this->assertDatabaseHas('drug_screening', [
            'civilian_name'    => 'Kojo Walkin Tagged',
            'suspect_id'       => null,
            'result'           => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);
    }

    #[Test]
    public function validation_fails_if_neither_suspect_nor_civilian_name_is_provided(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $invalidData = [
            'suspect_id'     => null,
            'civilian_name'  => null,
            'purpose'        => 'Test screening',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $invalidData);
        $response->assertSessionHasErrors(['suspect_id']);
    }

    #[Test]
    public function drug_screening_index_displays_both_suspect_and_civilian_with_badges(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-CIV-' . uniqid(),
            'title' => 'Operation Hybrid',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Yaw Suspect Row',
            'role' => 'Primary Suspect',
        ]);

        DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Custody Screening',
            'screening_date' => '2026-11-04',
            'result'         => 'Positive',
            'substances_found' => 'Cocaine',
        ]);

        DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Abena Civilian Row',
            'purpose'        => 'Community Screening',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Check Suspect row
        $this->assertStringContainsString('Yaw Suspect Row', $html);
        $this->assertStringContainsString('SUSPECT', $html);

        // Check Civilian row
        $this->assertStringContainsString('Abena Civilian Row', $html);
        $this->assertStringContainsString('CIVILIAN', $html);
        $this->assertStringContainsString('Civilian Walk-in Screening', $html);

        // Verify delete button is removed from Actions column
        $this->assertStringNotContainsString('title="Delete Record"', $html);
    }

    #[Test]
    public function drug_screening_filter_by_type_works_for_all_suspects_and_civilians(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-FLT-' . uniqid(),
            'title' => 'Filter Test',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect Person Alpha',
            'role' => 'Primary Suspect',
        ]);

        DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Suspect Filter Test',
            'screening_date' => '2026-11-04',
            'result'         => 'Positive',
            'substances_found' => 'Heroin',
        ]);

        DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Civilian Person Beta',
            'purpose'        => 'Civilian Filter Test',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        // 1. All (default) shows both
        $allRes = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $allRes->assertStatus(200);
        $allHtml = $allRes->getContent();
        $this->assertStringContainsString('Suspect Person Alpha', $allHtml);
        $this->assertStringContainsString('Civilian Person Beta', $allHtml);

        // 2. Filter by suspects
        $suspectsRes = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?type=suspects');
        $suspectsRes->assertStatus(200);
        $suspectsHtml = $suspectsRes->getContent();
        $this->assertStringContainsString('Suspect Person Alpha', $suspectsHtml);
        $this->assertStringNotContainsString('Civilian Person Beta', $suspectsHtml);

        // 3. Filter by civilians
        $civiliansRes = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?type=civilians');
        $civiliansRes->assertStatus(200);
        $civiliansHtml = $civiliansRes->getContent();
        $this->assertStringNotContainsString('Suspect Person Alpha', $civiliansHtml);
        $this->assertStringContainsString('Civilian Person Beta', $civiliansHtml);

        // 4. Composing filters: civilians + positive result
        DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Civilian Person Gamma',
            'purpose'        => 'Civilian Positive Test',
            'screening_date' => '2026-11-04',
            'result'         => 'Positive',
            'substances_found' => 'Cocaine',
        ]);

        $composedRes = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?type=civilians&result=positive');
        $composedRes->assertStatus(200);
        $composedHtml = $composedRes->getContent();
        $this->assertStringContainsString('Civilian Person Gamma', $composedHtml);
        $this->assertStringNotContainsString('Civilian Person Beta', $composedHtml);
        $this->assertStringNotContainsString('Suspect Person Alpha', $composedHtml);
    }

    #[Test]
    public function user_can_update_and_delete_civilian_screening(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screening = DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Kwame Updatable Civilian',
            'purpose'        => 'Initial Voluntary Check',
            'screening_date' => '2026-11-04',
            'result'         => 'Pending',
        ]);

        // Update via PUT route
        $updateData = [
            'civilian_name'    => 'Kwame Updatable Civilian',
            'purpose'          => 'Completed Voluntary Assay',
            'screening_date'   => '2026-11-04',
            'result'           => 'Negative',
            'notes'            => 'All strips clear.',
        ];

        $putRes = $this->actingAs($user)->put("/Lab_Analysis/drug-screening/{$screening->id}", $updateData);
        $putRes->assertRedirect('/Lab_Analysis/drug-screening');

        $fresh = $screening->fresh();
        $this->assertEquals('Negative', $fresh->result);
        $this->assertEquals('Completed Voluntary Assay', $fresh->purpose);
        $this->assertEquals('No drug(s) detected', $fresh->substances_found);
        $this->assertNull($fresh->suspect_id);

        // Delete
        $delRes = $this->actingAs($user)->delete("/Lab_Analysis/drug-screening/{$screening->id}");
        $delRes->assertRedirect('/Lab_Analysis/drug-screening');
        $this->assertDatabaseMissing('drug_screening', ['id' => $screening->id]);
    }

    #[Test]
    public function intake_form_initializes_select2_with_insert_tag_and_civilian_tagging_support(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/create');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Check Select2 assets and custom styles
        $this->assertStringContainsString('vendor/select2/select2.min.js', $html);
        $this->assertStringContainsString('vendor/select2/select2.min.css', $html);
        $this->assertStringContainsString('.select2-container', $html);

        // 2. Check hidden civilian_name input
        $this->assertStringContainsString('id="civilianNameInput"', $html);

        // 3. Check insertTag callback putting civilian tag at the top of results
        $this->assertStringContainsString('insertTag: function (data, tag)', $html);
        $this->assertStringContainsString('data.unshift(tag)', $html);

        // 4. Check custom matcher and templateResult / templateSelection
        $this->assertStringContainsString('+ NEW CIVILIAN', $html);
        $this->assertStringContainsString('Press Enter ↵', $html);
        $this->assertStringContainsString('SUSPECT', $html);
    }

    #[Test]
    public function user_can_submit_screening_with_medications_for_suspect_and_civilian(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $suspect = CaseSuspect::create([
            'suspect_number' => 1,
            'suspect_name' => 'Kofi Medicated Suspect',
            'role' => 'Primary Suspect',
        ]);

        // 1. Submit for suspect with multiple medications
        $postData = [
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Clinical Toxicology Screening',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'on_medication'  => '1',
            'medications'    => [
                [
                    'medication_name'     => 'Paracetamol',
                    'dosage'              => '500 mg',
                    'form'                => 'Tablet',
                    'frequency'           => 'Twice daily',
                    'route'               => 'Oral',
                    'prescription_source' => 'doctor',
                    'date_started'        => '2026-10-15',
                    'duration_ongoing'    => '3 weeks',
                    'last_dose_taken'     => '2026-11-04 08:00:00',
                ],
                [
                    'medication_name'     => 'Amlodipine',
                    'dosage'              => '10 mg',
                    'form'                => 'Tablet',
                    'frequency'           => 'Once daily',
                    'route'               => 'Oral',
                    'prescription_source' => 'doctor',
                    'date_started'        => '2026-09-01',
                    'duration_ongoing'    => 'Ongoing',
                    'last_dose_taken'     => '2026-11-04 07:30:00',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $postData);
        $response->assertRedirect('/Lab_Analysis/drug-screening');

        $screening = DrugScreening::where('suspect_id', $suspect->id)->first();
        $this->assertNotNull($screening);
        $this->assertTrue((bool) $screening->on_medication);
        $this->assertEquals('Paracetamol 500 mg; Amlodipine 10 mg', $screening->medication_summary);

        $this->assertCount(2, $screening->medications);
        $this->assertDatabaseHas('drug_screening_medications', [
            'drug_screening_id'   => $screening->id,
            'medication_name'     => 'Paracetamol',
            'dosage'              => '500 mg',
            'form'                => 'Tablet',
            'prescription_source' => 'doctor',
        ]);
        $this->assertDatabaseHas('drug_screening_medications', [
            'drug_screening_id'   => $screening->id,
            'medication_name'     => 'Amlodipine',
            'dosage'              => '10 mg',
            'form'                => 'Tablet',
            'prescription_source' => 'doctor',
        ]);

        // 2. Submit for Civilian with medications
        $civPostData = [
            'civilian_name'  => 'Akosua Medicated Civilian',
            'purpose'        => 'Voluntary Pre-Employment Screening',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'on_medication'  => '1',
            'medications'    => [
                [
                    'medication_name'     => 'Salbutamol',
                    'dosage'              => '100 mcg',
                    'form'                => 'Inhaler',
                    'frequency'           => 'As needed',
                    'route'               => 'Inhaled',
                    'prescription_source' => 'otc',
                ],
            ],
        ];

        $civResponse = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $civPostData);
        $civResponse->assertRedirect('/Lab_Analysis/drug-screening');

        $civScreening = DrugScreening::where('civilian_name', 'Akosua Medicated Civilian')->first();
        $this->assertNotNull($civScreening);
        $this->assertNull($civScreening->suspect_id);
        $this->assertTrue((bool) $civScreening->on_medication);
        $this->assertEquals('Salbutamol 100 mcg', $civScreening->medication_summary);
        $this->assertCount(1, $civScreening->medications);
    }

    #[Test]
    public function submitting_on_medication_false_clears_medications_and_nullifies_summary(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screening = DrugScreening::create([
            'civilian_name'      => 'Civilian Previously On Meds',
            'purpose'            => 'Follow-up Check',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'on_medication'      => true,
            'medication_summary' => 'Ibuprofen 400 mg',
        ]);

        $screening->medications()->create([
            'medication_name' => 'Ibuprofen',
            'dosage'          => '400 mg',
        ]);

        $this->assertCount(1, $screening->medications);

        // Update screening setting on_medication to false (0)
        $updateData = [
            'civilian_name'  => 'Civilian Previously On Meds',
            'purpose'        => 'Follow-up Check Completed',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'on_medication'  => '0',
            // Even if leftover array sent, it must be ignored/cleared
            'medications'    => [
                ['medication_name' => 'Leftover Med'],
            ],
        ];

        $res = $this->actingAs($user)->put("/Lab_Analysis/drug-screening/{$screening->id}", $updateData);
        $res->assertRedirect('/Lab_Analysis/drug-screening');

        $fresh = $screening->fresh();
        $this->assertFalse((bool) $fresh->on_medication);
        $this->assertNull($fresh->medication_summary);
        $this->assertCount(0, $fresh->medications);
        $this->assertDatabaseMissing('drug_screening_medications', [
            'drug_screening_id' => $screening->id,
        ]);
    }

    #[Test]
    public function validation_fails_if_on_medication_is_true_without_valid_medications(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $suspect = CaseSuspect::create([
            'suspect_number' => 1,
            'suspect_name' => 'Suspect Validation Test',
            'role' => 'Primary Suspect',
        ]);

        // 1. Missing medications array when on_medication is true
        $res = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', [
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Test',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'on_medication'  => '1',
            // No medications array
        ]);
        $res->assertSessionHasErrors(['medications']);

        // 2. Medications array has entry but medication_name is empty
        $res2 = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', [
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Test',
            'screening_date' => '2026-11-04',
            'result'         => 'Negative',
            'on_medication'  => '1',
            'medications'    => [
                [
                    'medication_name' => '',
                    'dosage'          => '500 mg',
                ],
            ],
        ]);
        $res2->assertSessionHasErrors(['medications.0.medication_name']);
    }

    #[Test]
    public function index_and_dossier_views_display_medication_badges_and_table(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screening = DrugScreening::create([
            'civilian_name'      => 'Amina View Test',
            'purpose'            => 'Court Assessment',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'on_medication'      => true,
            'medication_summary' => 'Metformin 500 mg; Losartan 50 mg',
        ]);

        $screening->medications()->create([
            'medication_name'     => 'Metformin',
            'dosage'              => '500 mg',
            'form'                => 'Tablet',
            'frequency'           => 'Twice daily',
            'route'               => 'Oral',
            'prescription_source' => 'doctor',
            'duration_ongoing'    => 'Ongoing',
        ]);

        $indexRes = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $indexRes->assertStatus(200);
        $indexHtml = $indexRes->getContent();

        $this->assertStringContainsString('On Meds', $indexHtml);
        $this->assertStringContainsString('Amina View Test', $indexHtml);
        $this->assertStringContainsString('Metformin 500 mg; Losartan 50 mg', $indexHtml);

        // Check suspect_drug_screening_card partial rendering
        $cardView = view('partials.suspect_drug_screening_card', [
            'screening'   => $screening,
            'suspectName' => 'Amina View Test',
        ])->render();

        $this->assertStringContainsString('Clinical Medications &amp; Prescriptions', $cardView);
        $this->assertStringContainsString('Metformin', $cardView);
        $this->assertStringContainsString('500 mg', $cardView);
        $this->assertStringContainsString('Doctor Prescribed', $cardView);
    }

    #[Test]
    public function api_and_show_return_medication_details_in_json(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $suspect = CaseSuspect::create([
            'suspect_number' => 1,
            'suspect_name' => 'Suspect Json Med Test',
            'role' => 'Primary Suspect',
        ]);

        $screening = DrugScreening::create([
            'suspect_id'         => $suspect->id,
            'purpose'            => 'JSON Api Check',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'on_medication'      => true,
            'medication_summary' => 'Diazepam 5 mg',
        ]);

        $screening->medications()->create([
            'medication_name'     => 'Diazepam',
            'dosage'              => '5 mg',
            'form'                => 'Tablet',
            'frequency'           => 'At bedtime',
            'prescription_source' => 'doctor',
        ]);

        $res = $this->actingAs($user)->getJson("/Lab_Analysis/drug-screening/{$screening->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('screening.on_medication', true);
        $res->assertJsonPath('screening.medication_summary', 'Diazepam 5 mg');
        $res->assertJsonCount(1, 'screening.medications');
        $res->assertJsonPath('screening.medications.0.medication_name', 'Diazepam');
        $res->assertJsonPath('screening.medications.0.dosage', '5 mg');
        $res->assertJsonPath('screening.medications.0.source_label', 'Doctor Prescribed');
    }

    #[Test]
    public function intake_form_renders_sections_in_correct_order(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/create');
        $response->assertStatus(200);

        $html = $response->getContent();

        $pos1 = strpos($html, '1. Suspect Name &amp; Profile');
        $pos2 = strpos($html, '2. On Medication / Clinical Prescriptions');
        $pos3 = strpos($html, '3. Purpose of Screening');
        $pos4 = strpos($html, '4. Date of Screening');
        $pos5 = strpos($html, '5. Screening Outcome / Results');
        $pos6 = strpos($html, '6. Notes / Clinical Remarks');

        $this->assertNotFalse($pos1, 'Section 1 should be present');
        $this->assertNotFalse($pos2, 'Section 2 should be present');
        $this->assertNotFalse($pos3, 'Section 3 should be present');
        $this->assertNotFalse($pos4, 'Section 4 should be present');
        $this->assertNotFalse($pos5, 'Section 5 should be present');
        $this->assertNotFalse($pos6, 'Section 6 should be present');

        $this->assertTrue($pos1 < $pos2, 'Section 1 must appear before Section 2');
        $this->assertTrue($pos2 < $pos3, 'Section 2 must appear before Section 3');
        $this->assertTrue($pos3 < $pos4, 'Section 3 must appear before Section 4');
        $this->assertTrue($pos4 < $pos5, 'Section 4 must appear before Section 5');
        $this->assertTrue($pos5 < $pos6, 'Section 5 must appear before Section 6');
    }

    #[Test]
    public function screening_profiles_table_renders_search_input(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('id="tableSearchInput"', $html);
        $this->assertStringContainsString('id="tableSearchForm"', $html);

        // Test with search query
        $resSearch = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?search=VolunteerToxicology');
        $resSearch->assertStatus(200);
        $this->assertStringContainsString('value="VolunteerToxicology"', $resSearch->getContent());
        $this->assertStringContainsString('title="Clear search filter"', $resSearch->getContent());
    }

    #[Test]
    public function civilian_screening_submission_triggers_auto_download_pdf_and_report_ready_session(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screeningData = [
            'civilian_name'    => 'Akua Mensah',
            'purpose'          => 'Pre-Employment Toxicological Clearance',
            'screening_date'   => '2026-11-04',
            'result'           => 'Negative',
            'notes'            => 'Volunteer civilian sample examined at headquarters laboratory.',
            'on_medication'    => '1',
            'medications'      => [
                [
                    'medication_name'     => 'Amoxicillin',
                    'dosage'              => '500mg',
                    'form'                => 'Capsule',
                    'frequency'           => 'Three times daily',
                    'route'               => 'Oral',
                    'prescription_source' => 'doctor',
                    'date_started'        => '2026-11-01',
                    'duration_ongoing'    => '5 days',
                    'last_dose_taken'     => '2026-11-04 08:00:00',
                ]
            ],
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $screeningData);

        $response->assertRedirect('/Lab_Analysis/drug-screening');
        $response->assertSessionHas('success');
        $response->assertSessionHas('auto_download_pdf');
        $response->assertSessionHas('civilian_pdf_id');
        $response->assertSessionHas('civilian_name', 'Akua Mensah');

        $screening = DrugScreening::where('civilian_name', 'Akua Mensah')->first();
        $this->assertNotNull($screening);

        $pdfUrl = session('auto_download_pdf');
        $this->assertStringContainsString("/Lab_Analysis/drug-screening/{$screening->id}/pdf", $pdfUrl);

        // Follow redirect to index and assert auto-open script & popover modal render
        $indexResponse = $this->actingAs($user)->get('/Lab_Analysis/drug-screening');
        $indexHtml = $indexResponse->getContent();
        $this->assertStringContainsString('id="civilianPdfPopoverModal"', $indexHtml);
        $this->assertStringContainsString('Civilian PDF Report Ready', $indexHtml);
        $this->assertStringContainsString('Official Medical Screening PDF Report generated for', $indexHtml);
        $this->assertStringContainsString('Akua Mensah', $indexHtml);
        $this->assertStringContainsString('Cancel', $indexHtml);
        $this->assertStringContainsString('Print / Save', $indexHtml);
    }

    #[Test]
    public function suspect_screening_submission_does_not_trigger_civilian_pdf_auto_download(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $case = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-SUSP-PDF-001',
            'title' => 'PDF Suspect Case',
            'crime_type' => 'Possession',
            'date_reported' => '2026-11-04',
            'suspect_name' => 'Suspect John Doe',
        ]);

        $suspect = CaseSuspect::where('single_suspect_case_id', $case->id)->first();
        if (!$suspect) {
            $suspect = CaseSuspect::create([
                'single_suspect_case_id' => $case->id,
                'suspect_name' => 'Suspect John Doe',
                'suspect_number' => 1,
            ]);
        }

        $screeningData = [
            'suspect_id'       => $suspect->id,
            'purpose'          => 'Intake Custody Screening',
            'screening_date'   => '2026-11-04',
            'result'           => 'Negative',
            'notes'            => 'Routine suspect intake.',
        ];

        $response = $this->actingAs($user)->post('/Lab_Analysis/drug-screening', $screeningData);

        $response->assertRedirect('/Lab_Analysis/drug-screening');
        $response->assertSessionHas('success');
        $response->assertSessionMissing('auto_download_pdf');
    }

    #[Test]
    public function civilian_pdf_route_renders_formal_cover_page_and_clinical_medical_body_with_watermark(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screening = DrugScreening::create([
            'civilian_name'    => 'Kwame Nkrumah Boateng',
            'suspect_id'       => null,
            'purpose'          => 'National Aviation Licensing Clearance',
            'screening_date'   => '2026-11-04',
            'result'           => 'Negative',
            'substances_found' => 'No drug(s) detected',
            'notes'            => 'Subject verified negative for all 10 panel narcotic substances.',
            'on_medication'    => true,
            'medication_summary' => 'Paracetamol 500mg; Vitamin C 1000mg',
        ]);

        $screening->medications()->create([
            'medication_name'     => 'Paracetamol',
            'dosage'              => '500mg',
            'form'                => 'Tablet',
            'frequency'           => 'As needed',
            'route'               => 'Oral',
            'prescription_source' => 'otc',
            'date_started'        => '2026-11-01',
            'duration_ongoing'    => '3 days',
        ]);

        $response = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/{$screening->id}/pdf");
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Cover Page Requirements
        $this->assertStringContainsString('REPUBLIC OF GHANA', $html);
        $this->assertStringContainsString('NARCOTICS CONTROL COMMISSION', $html);
        $this->assertStringContainsString('CIVILIAN DRUG SCREENING REPORT', $html);
        $this->assertStringContainsString('Kwame Nkrumah Boateng', $html);
        $this->assertStringContainsString('NACOC-CIV-2026-', $html);
        $this->assertStringContainsString('OFFICIAL BIOLOGICAL SCREENING REPORT', $html);
        $this->assertStringContainsString('National Aviation Licensing Clearance', $html);

        // 2. Watermark Requirements (10% - 15% opacity)
        $this->assertStringContainsString('watermark-container', $html);
        $this->assertStringContainsString('opacity: 0.12', $html);
        $this->assertStringContainsString('NACOC_LOGO.png', $html);

        // 3. Clinical Medical Presentation (Pure white, black text, clean dividers)
        $this->assertStringContainsString('Toxicological Screening Findings', $html);
        $this->assertStringContainsString('NEGATIVE', $html);
        $this->assertStringContainsString('No drug(s) detected', $html);
        $this->assertStringContainsString('Declared Clinical Medications', $html);
        $this->assertStringContainsString('Paracetamol', $html);
        $this->assertStringContainsString('Over The Counter (OTC)', $html);
        $this->assertStringContainsString('Clinical Notes &amp; Observations', $html);

        // 4. Signatory removed from civilian report
        $this->assertStringNotContainsString('TESTING OFFICER / ANALYST', $html);
        $this->assertStringNotContainsString('VALIDATING AUTHORITY / HEAD OF LAB', $html);

        // 5. Also verify alternate alias routes
        $resAlias = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/civilian-report/{$screening->id}");
        $resAlias->assertStatus(200);
        $resShort = $this->actingAs($user)->get("/drug-screening/{$screening->id}/pdf");
        $resShort->assertStatus(200);
    }

    #[Test]
    public function report_pdf_route_renders_consolidated_general_sitrep_with_cover_page_and_kpis(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-REP-' . uniqid(),
            'title' => 'State vs Multi Suspect Syndicate',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'General Register Suspect',
            'role' => 'Primary Suspect',
        ]);

        DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Custody Preliminary Screening',
            'screening_date' => now()->toDateString(),
            'result'         => 'Positive',
            'substances_found' => 'Cocaine, THC',
        ]);

        DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'General Register Civilian',
            'purpose'        => 'Driver Licensing Verification',
            'screening_date' => now()->toDateString(),
            'result'         => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. SITREP Cover Page & Structure
        $this->assertStringContainsString('SITREP COVER', $html);
        $this->assertStringContainsString('GENERAL DRUG FORENSIC REPORT', $html);
        $this->assertStringContainsString('PERIOD: ALL REGISTERED CASES (CUMULATIVE)', $html);
        $this->assertStringContainsString('LABORATORY ANALYSIS UNIT', $html);
        $this->assertStringContainsString('NARCOTICS CONTROL COMMISSION', $html);

        // 2. Watermark with 12% opacity
        $this->assertStringContainsString('opacity: 0.12', $html);
        $this->assertStringContainsString('NACOC_LOGO.png', $html);

        // 3. Statically locked footers
        $this->assertStringContainsString('bottom: 8mm !important;', $html);
        $this->assertStringContainsString('NACOC / LAU UNIT', $html);
        $this->assertStringContainsString('Executive Directory Cover Page', $html);

        // 4. Register KPIs and Data Table
        $this->assertStringContainsString('Total Tests', $html);
        $this->assertStringContainsString('Positive', $html);
        $this->assertStringContainsString('Negative', $html);
        $this->assertStringContainsString('General Register Suspect', $html);
        $this->assertStringContainsString('General Register Civilian', $html);
        $this->assertStringContainsString('Subject Details', $html);
        $this->assertStringContainsString('TOXICOLOGY REGISTER', $html);

        // 5. Short and Alias Routes
        $resGen = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/generate-report');
        $resGen->assertStatus(200);
        $resShort = $this->actingAs($user)->get('/drug-screening/report/pdf');
        $resShort->assertStatus(200);
    }

    #[Test]
    public function report_pdf_route_supports_weekly_monthly_and_annually_periods(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Weekly Test Person',
            'purpose'        => 'Weekly Assessment',
            'screening_date' => now()->toDateString(),
            'result'         => 'Negative',
        ]);

        // Weekly Report
        $resWeekly = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf?period=weekly');
        $resWeekly->assertStatus(200);
        $this->assertStringContainsString('WEEKLY DRUG SCREENING REPORT', $resWeekly->getContent());

        // Monthly Report
        $resMonthly = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf?period=monthly');
        $resMonthly->assertStatus(200);
        $this->assertStringContainsString('MONTHLY DRUG SCREENING REPORT', $resMonthly->getContent());

        // Annual Report
        $resAnnual = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf?period=annually');
        $resAnnual->assertStatus(200);
        $this->assertStringContainsString('ANNUAL DRUG SCREENING REPORT', $resAnnual->getContent());
    }

    #[Test]
    public function report_pdf_route_supports_personal_report_mode_for_suspect_and_civilian(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-PERS-' . uniqid(),
            'title' => 'Personal Mode Test Docket',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect Personal Target',
            'role' => 'Primary Suspect',
        ]);

        $suspectScreening = DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Suspect Personal Profile Test',
            'screening_date' => now()->toDateString(),
            'result'         => 'Positive',
            'substances_found' => 'Tramadol',
        ]);

        $civilianScreening = DrugScreening::create([
            'suspect_id'     => null,
            'civilian_name'  => 'Civilian Personal Target',
            'purpose'        => 'Civilian Personal Profile Test',
            'screening_date' => now()->toDateString(),
            'result'         => 'Negative',
            'substances_found' => 'No drug(s) detected',
        ]);

        // 1. Personal report on Civilian via report/pdf endpoint
        $resCiv = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf?report_mode=personal&person=Civilian%20Personal%20Target');
        $resCiv->assertStatus(200);
        $civHtml = $resCiv->getContent();
        $this->assertStringContainsString('Civilian Personal Target', $civHtml);
        $this->assertStringContainsString('CIVILIAN DRUG SCREENING REPORT', $civHtml);

        // 2. Personal report on Suspect via report/pdf endpoint
        $resSusp = $this->actingAs($user)->get('/Lab_Analysis/drug-screening/report/pdf?report_mode=personal&person=Suspect%20Personal%20Target');
        $resSusp->assertStatus(200);
        $suspHtml = $resSusp->getContent();
        $this->assertStringContainsString('Suspect Personal Target', $suspHtml);
        $this->assertStringContainsString('SUSPECT DRUG SCREENING REPORT', $suspHtml);

        // 3. Suspect direct personal PDF route
        $resSuspDirect = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/{$suspectScreening->id}/pdf");
        $resSuspDirect->assertStatus(200);
        $suspDirectHtml = $resSuspDirect->getContent();
        $this->assertStringContainsString('SUSPECT DRUG SCREENING REPORT', $suspDirectHtml);
        $this->assertStringContainsString('NACOC-TOX-', $suspDirectHtml);
        $this->assertStringContainsString('bottom: 8mm !important;', $suspDirectHtml);
    }

    #[Test]
    public function index_filters_by_period_weekly_monthly_and_annually(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        // Today screening
        DrugScreening::create([
            'civilian_name'  => 'Today Person Screened',
            'purpose'        => 'Today Screening',
            'screening_date' => now()->toDateString(),
            'result'         => 'Negative',
        ]);

        // 20 days ago screening
        DrugScreening::create([
            'civilian_name'  => 'Twenty Days Ago Person',
            'purpose'        => 'Past Screening',
            'screening_date' => now()->subDays(20)->toDateString(),
            'result'         => 'Positive',
        ]);

        // 100 days ago screening
        DrugScreening::create([
            'civilian_name'  => 'Hundred Days Ago Person',
            'purpose'        => 'Ancient Screening',
            'screening_date' => now()->subDays(100)->toDateString(),
            'result'         => 'Negative',
        ]);

        // Weekly filter (last 7 days): shows Today, not 20 or 100 days ago
        $resWeekly = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?period=weekly');
        $resWeekly->assertStatus(200);
        $wHtml = $resWeekly->getContent();
        $this->assertStringContainsString('Today Person Screened', $wHtml);
        $this->assertStringNotContainsString('Twenty Days Ago Person', $wHtml);
        $this->assertStringNotContainsString('Hundred Days Ago Person', $wHtml);

        // Monthly filter (last 30 days): shows Today and 20 days ago, not 100
        $resMonthly = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?period=monthly');
        $resMonthly->assertStatus(200);
        $mHtml = $resMonthly->getContent();
        $this->assertStringContainsString('Today Person Screened', $mHtml);
        $this->assertStringContainsString('Twenty Days Ago Person', $mHtml);
        $this->assertStringNotContainsString('Hundred Days Ago Person', $mHtml);

        // Annually filter: shows all 3
        $resAnnual = $this->actingAs($user)->get('/Lab_Analysis/drug-screening?period=annually');
        $resAnnual->assertStatus(200);
        $aHtml = $resAnnual->getContent();
        $this->assertStringContainsString('Today Person Screened', $aHtml);
        $this->assertStringContainsString('Twenty Days Ago Person', $aHtml);
        $this->assertStringContainsString('Hundred Days Ago Person', $aHtml);
    }

    #[Test]
    public function civilian_pdf_renders_complete_medication_schedule_with_all_nine_fields_and_no_suppression(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $screening = DrugScreening::create([
            'civilian_name'      => 'John Adu Full Meds Test',
            'suspect_id'         => null,
            'purpose'            => 'Rehabilitation & Detox Intake Evaluation',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'substances_found'   => 'No drug(s) detected',
            'notes'              => 'Clinical observation note text.',
            'on_medication'      => true,
            'medication_summary' => 'GFGJKFGF G78B',
        ]);

        $screening->medications()->create([
            'medication_name'     => 'GFGJKFGF',
            'dosage'              => 'G78B',
            'form'                => 'Capsule',
            'frequency'           => 'Twice daily',
            'route'               => 'Topical',
            'prescription_source' => 'doctor',
            'date_started'        => '2026-11-04',
            'duration_ongoing'    => 'UYUYY',
            'last_dose_taken'     => '2026-11-04 10:20:00',
        ]);

        $response = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/{$screening->id}/pdf");
        $response->assertStatus(200);

        $html = $response->getContent();

        // Check that every single field from On Medication / Clinical Prescriptions is rendered
        $this->assertStringContainsString('GFGJKFGF', $html);
        $this->assertStringContainsString('G78B', $html);
        $this->assertStringContainsString('Capsule', $html);
        $this->assertStringContainsString('Route: Topical', $html);
        $this->assertStringContainsString('Twice daily', $html);
        $this->assertStringContainsString('Doctor Prescribed', $html);
        $this->assertStringContainsString('Started:', $html);
        $this->assertStringContainsString('04/11/2026', $html);
        $this->assertStringContainsString('Duration:', $html);
        $this->assertStringContainsString('UYUYY', $html);
        $this->assertStringContainsString('04/11/2026 10:20', $html);

        // Test fallback when on_medication is true but no detail rows exist
        $screeningSummaryOnly = DrugScreening::create([
            'civilian_name'      => 'Summary Only Person',
            'suspect_id'         => null,
            'purpose'            => 'Detox Evaluation',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'on_medication'      => true,
            'medication_summary' => 'Amoxicillin 250mg oral capsules',
        ]);

        $responseSummary = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/{$screeningSummaryOnly->id}/pdf");
        $responseSummary->assertStatus(200);
        $sumHtml = $responseSummary->getContent();
        $this->assertStringContainsString('Amoxicillin 250mg oral capsules', $sumHtml);
        $this->assertStringNotContainsString('officially declared NO active prescription', $sumHtml);
    }

    #[Test]
    public function suspect_drug_screening_card_renders_both_started_date_and_duration(): void
    {
        $suspect = CaseSuspect::create([
            'suspect_number' => 2,
            'suspect_name'   => 'Card Suspect Test',
            'role'           => 'Primary Suspect',
        ]);

        $screening = DrugScreening::create([
            'suspect_id'         => $suspect->id,
            'purpose'            => 'Card View Test',
            'screening_date'     => '2026-11-04',
            'result'             => 'Negative',
            'on_medication'      => true,
            'medication_summary' => 'Morphine 10mg',
        ]);

        $screening->medications()->create([
            'medication_name'     => 'Morphine',
            'dosage'              => '10mg',
            'form'                => 'Tablet',
            'frequency'           => 'Daily',
            'route'               => 'Oral',
            'prescription_source' => 'doctor',
            'date_started'        => '2026-10-15',
            'duration_ongoing'    => '30 days ongoing',
            'last_dose_taken'     => '2026-11-04 06:00:00',
        ]);

        $rendered = view('partials.suspect_drug_screening_card', [
            'screening'   => $screening->load('medications'),
            'suspectName' => 'Card Suspect Test',
        ])->render();

        $this->assertStringContainsString('Morphine', $rendered);
        $this->assertStringContainsString('Started:', $rendered);
        $this->assertStringContainsString('15 Oct 2026', $rendered);
        $this->assertStringContainsString('Duration:', $rendered);
        $this->assertStringContainsString('30 days ongoing', $rendered);
    }

    #[Test]
    public function classification_in_drug_screening_report_fetches_from_suspect_case_status_column(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-STATUS-' . uniqid(),
            'title' => 'Status Column Test',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kweku Status Suspect',
            'role' => 'Primary Suspect',
            'case_status' => 'Remanded in Custody',
        ]);

        $screening = DrugScreening::create([
            'suspect_id'     => $suspect->id,
            'purpose'        => 'Intake Status Check',
            'screening_date' => now()->toDateString(),
            'result'         => 'Negative',
        ]);

        // 1. Direct PDF
        $resDirect = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/{$screening->id}/pdf");
        $resDirect->assertStatus(200);
        $this->assertStringContainsString('Remanded in Custody', $resDirect->getContent());

        // 2. Register SITREP PDF
        $resSitrep = $this->actingAs($user)->get("/Lab_Analysis/drug-screening/report/pdf");
        $resSitrep->assertStatus(200);
        $this->assertStringContainsString('Remanded in Custody', $resSitrep->getContent());
    }
}


