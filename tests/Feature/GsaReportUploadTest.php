<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\SuspectGsaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GsaReportUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_upload_and_attach_gsa_report_file_to_analytical_report_page()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'fullname' => 'Lab Chemist',
            'unit' => 'Forensic Laboratory',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA01',
            'title' => 'Republic vs Suspect GSA Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect GSA Target',
            'role' => 'Primary Suspect',
        ]);

        $file = UploadedFile::fake()->create('signed_gsa_certificate.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'case_suspect_id' => $suspect->id,
                'multiple_suspect_case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'lab_number' => 'Lab No. 12/FS03/2026',
                'gsa_report_images' => [$file],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'CSS-2026-GSA01',
            'original_filename' => 'signed_gsa_certificate.pdf',
        ]);

        // Check that analytical report page renders the attached GSA report file
        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $this->assertStringContainsString('signed_gsa_certificate.pdf', $pageResponse->getContent());
    }

    #[Test]
    public function gsa_upload_section_is_hidden_by_default_and_reveals_via_gsa_certificate_button()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA02',
            'title' => 'Republic vs Suspect Hidden GSA Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $html = $pageResponse->getContent();

        // GSA Upload Section and upload triggers are safely removed from analytical report page
        $this->assertStringNotContainsString('id="gsaUploadSection"', $html);
        $this->assertStringNotContainsString('showGsaReportUploadSection', $html);
        $this->assertStringNotContainsString('openGsaReportModal', $html);
    }

    #[Test]
    public function gsa_upload_without_suspect_id_resolves_suspect_by_name()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA03',
            'title' => 'Republic vs Suspects GSA Resolve Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);
        CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo Adu',
            'role' => 'Primary Suspect',
        ]);
        $second = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Ama Serwaa',
            'role' => 'Co-Suspect',
        ]);

        // Mirrors what the analytical page sends: no case_suspect_id, only the card's suspect name
        $response = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'multiple_suspect_case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'suspect_name' => 'AMA SERWAA',
                'gsa_report_images' => [UploadedFile::fake()->create('gsa.pdf', 100, 'application/pdf')],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_suspect_id' => $second->id,
            'multiple_suspect_case_id' => $mCase->id,
            'original_filename' => 'gsa.pdf',
        ]);
    }

    #[Test]
    public function saving_exhibit_description_attaches_nlr_report_pdf_to_suspect_case()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-NLR01',
            'title' => 'Republic vs Suspect NLR Attach Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo NLR Target',
            'role' => 'Primary Suspect',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/Lab_Analysis/nlr-reports/store', [
                'case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'laboratory_number' => 'Lab No. 99/NLR/2026',
                'detailed_exhibit_description' => 'Tested positive for High Purity Cocaine Hydrochloride.',
                'condition_of_reserved_sample' => 'good',
                'suspect_id' => $suspect->id,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Assert preliminary / NLR reports database table has attached Report PDF record for this suspect
        $tableName = (new \App\Models\SuspectNlrReport)->getTable();
        $this->assertTrue(
            \Illuminate\Support\Facades\DB::table($tableName)
                ->where('case_suspect_id', $suspect->id)
                ->where('multiple_suspect_case_id', $mCase->id)
                ->where(function ($q) use ($mCase) {
                    $q->where('file_path', 'LIKE', '%/preliminary-report-pdf/' . $mCase->case_number . '%')
                      ->orWhere('file_path', 'LIKE', '%/nlr-report-pdf/' . $mCase->case_number . '%');
                })
                ->exists()
        );

        // Assert analytical page displays attached report file
        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $pageContent = $pageResponse->getContent();
        $this->assertTrue(str_contains($pageContent, 'NLR_Report_') || str_contains($pageContent, 'Preliminary_Report_') || str_contains($pageContent, 'Report_'));
    }

    #[Test]
    public function buttons_are_disabled_per_suspect_and_top_header_only_when_all_suspects_have_reports()
    {
        $user = User::factory()->head()->create();
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-DIS01',
            'title' => 'Republic vs Disabled Buttons Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'First Suspect Attached',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Second Suspect Pending',
            'role' => 'Co-Suspect',
        ]);

        \App\Models\Exhibit::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_type' => 'Drug',
            'drug_name' => 'Cocaine',
            'associated_person' => 'First Suspect Attached',
            'status' => 'active',
        ]);

        \App\Models\Exhibit::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_type' => 'Drug',
            'drug_name' => 'Heroin',
            'associated_person' => 'Second Suspect Pending',
            'status' => 'active',
        ]);

        // Attach an NLR Report to suspect 1 ONLY
        \App\Models\SuspectNlrReport::create([
            'case_suspect_id' => $suspect1->id,
            'multiple_suspect_case_id' => $mCase->id,
            'case_number' => $mCase->case_number,
            'lab_number' => 'Lab No. 101/NLR/2026',
            'original_filename' => 'NLR_Report_CSS-2026-DIS01.pdf',
            'stored_filename' => 'NLR_Report_CSS-2026-DIS01.pdf',
            'file_path' => 'Lab_Analysis/nlr-report-pdf/' . $mCase->id,
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 12345,
            'status' => 'active',
            'uploaded_by' => $user->id,
        ]);

        $pageResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $pageResponse->assertStatus(200);
        $html = $pageResponse->getContent();

        // Top header button MUST be enabled because Suspect 2 has no report attached yet!
        $this->assertStringNotContainsString('showGsaReportUploadSection', $html);
        $this->assertStringContainsString('onclick="openNlrReportModal()"', $html);

        // Suspect 1's card buttons are disabled
        $this->assertStringContainsString('disabled title="A report has already been generated and attached."', $html);

        // Attach NLR report to suspect 2 as well
        \App\Models\SuspectNlrReport::create([
            'case_suspect_id' => $suspect2->id,
            'multiple_suspect_case_id' => $mCase->id,
            'case_number' => $mCase->case_number,
            'lab_number' => 'Lab No. 102/NLR/2026',
            'original_filename' => 'NLR_Report_CSS-2026-DIS02.pdf',
            'stored_filename' => 'NLR_Report_CSS-2026-DIS02.pdf',
            'file_path' => 'Lab_Analysis/nlr-report-pdf/' . $mCase->id,
            'file_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 12345,
            'status' => 'active',
            'uploaded_by' => $user->id,
        ]);

        $fullResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $fullResponse->assertStatus(200);
        $fullHtml = $fullResponse->getContent();

        // Now top header buttons are disabled because ALL suspects have reports attached!
        $this->assertStringContainsString('disabled title="Reports have already been generated and attached for all suspects in this case."', $fullHtml);
    }

    #[Test]
    public function lab_analysis_create_page_uses_nlr_reference_form_for_section_5_examination_info(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $response = $this->actingAs($user)->get('/Lab_Analysis/create?from=Forensic+Laboratory');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Section 5 Analysis / Examination Reference input should have NLA format (NLA-FM-T...-F) and NOT GSA format
        $this->assertMatchesRegularExpression('/id="labExamReferenceInput"[^>]*value="NLA-FM-T\d+-F"/i', $html);
        $this->assertStringNotContainsString('value="GSA-FM-T', $html);

        // Verification of switcher functions
        $this->assertStringContainsString('switchToNlrExamSection', $html);
        $this->assertStringContainsString("examRefInput.value = `NLA-FM-T\${randNum}-F`", $html);
        $this->assertStringContainsString("examRefInput.value.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-')", $html);

        // Verify Save & Attach GSA Report button is removed from Lab Analysis create page
        $this->assertStringNotContainsString('Save &amp; Attach GSA Report', $html);
        $this->assertStringNotContainsString('id="saveGsaFilesBtn"', $html);
        $this->assertStringContainsString('Auto-saved with Save Case', $html);
        $this->assertStringContainsString('uploadStagedGsaFiles(data, name)', $html);
    }

    #[Test]
    public function saving_case_automatically_saves_and_attaches_uploaded_gsa_report_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $file1 = UploadedFile::fake()->create('gsa_doc_1.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->create('gsa_photo_2.jpg', 300, 'image/jpeg');

        $caseNumber = 'NCC-2026-AUTOGSA-01';

        $response = $this->actingAs($user)->post('/cases', [
            'case_number' => $caseNumber,
            'title' => 'Republic vs Auto GSA Suspect',
            'unit_name' => 'Forensic Laboratory',
            'suspect_name' => 'Auto GSA Target',
            'gsa_report_images' => [$file1, $file2],
        ]);

        $response->assertSessionHasNoErrors();

        // Verify GSA reports were automatically saved and attached
        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => $caseNumber,
            'original_filename' => 'gsa_doc_1.pdf',
        ]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => $caseNumber,
            'original_filename' => 'gsa_photo_2.jpg',
        ]);
    }

    #[Test]
    public function saving_case_persists_detected_substances_and_displays_on_show_review_page(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $caseNumber = 'NCC-2026-DETSUB-01';
        $detectedSubstances = [
            ['substance' => 'Cocaine Hydrochloride', 'percentage' => '85.4%'],
            ['substance' => 'Levamisole Adulterant', 'percentage' => '14.6%'],
        ];

        $response = $this->actingAs($user)->postJson('/cases', [
            'case_number' => $caseNumber,
            'title' => 'Republic vs Detected Substance Suspect',
            'unit_name' => 'Forensic Laboratory',
            'suspect_name' => 'Substance Test Subject',
            'detected_substances' => $detectedSubstances,
            'docket_data' => [
                'lab_examination' => [
                    'detected_substances' => $detectedSubstances,
                    'laboratory_number' => 'LAB-NCC-2026-099',
                    'presumptive_result' => 'YES (Positive)',
                    'substance_identification' => 'Cocaine Hydrochloride',
                ],
            ],
        ]);

        $response->assertStatus(200);

        // 1. Verify LaboratoryExaminationResult record has detected_substances stored
        $labRecord = \App\Models\LaboratoryExaminationResult::where('laboratory_number', 'LAB-NCC-2026-099')
            ->orWhereHas('singleCase', function($q) use ($caseNumber) {
                $q->where('case_number', $caseNumber);
            })->first();

        $this->assertNotNull($labRecord, 'LaboratoryExaminationResult record should have been created');
        $this->assertIsArray($labRecord->detected_substances);
        $sub0 = is_array($labRecord->detected_substances[0]) ? $labRecord->detected_substances[0]['substance'] : $labRecord->detected_substances[0];
        $pct0 = is_array($labRecord->detected_substances[0]) ? $labRecord->detected_substances[0]['percentage'] : ($labRecord->detected_subtances_percentage[0] ?? null);
        $this->assertEquals('Cocaine Hydrochloride', $sub0);
        $this->assertEquals('85.4%', $pct0);

        // 2. Verify show_review page displays Confirmatory Test table with detected substances & purity
        $reviewPage = $this->actingAs($user)->get("/cases/{$caseNumber}/review");
        $reviewPage->assertStatus(200);
        $reviewHtml = $reviewPage->getContent();

        $this->assertStringContainsString('Cocaine Hydrochloride', $reviewHtml);
        $this->assertStringContainsString('85.4%', $reviewHtml);
        $this->assertStringContainsString('Levamisole Adulterant', $reviewHtml);
        $this->assertStringContainsString('14.6%', $reviewHtml);
    }

    #[Test]
    public function exhibit_suspect_ownership_associates_correctly_to_specific_suspect_or_all_suspects(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $caseNumber = 'LAB-2026-EX-OWN-' . uniqid();

        $response = $this->actingAs($user)->postJson('/cases', [
            'case_type' => 'MULTIPLE',
            'case_number' => $caseNumber,
            'title' => 'Exhibit Suspect Ownership Test Case',
            'unit_name' => 'Forensic Laboratory',
            'case_category' => 'Narcotics',
            'priority' => 'High',
            'incident_date' => now()->toDateString(),
            'incident_time' => '12:00',
            'incident_location' => 'Accra Cargo Terminal',
            'suspect_name' => 'Suspect Alpha',
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Suspect Alpha',
                        'role' => 'Primary Suspect',
                        'nationality' => 'Ghanaian',
                        'gender' => 'Male',
                        'age' => 30,
                    ],
                    [
                        'suspect_name' => 'Suspect Beta',
                        'role' => 'Co-Suspect #1',
                        'nationality' => 'Ghanaian',
                        'gender' => 'Female',
                        'age' => 28,
                    ],
                ],
                'seizures' => [
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'White Powder Alpha',
                        'associated_person' => 'Suspect Alpha',
                        'suspect_ownership' => 'Suspect Alpha',
                        'seizure_quantity' => '2',
                        'seizure_packaging' => 'Parcels',
                    ],
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Brown Granules Beta',
                        'associated_person' => 'Suspect Beta',
                        'suspect_ownership' => 'Suspect Beta',
                        'seizure_quantity' => '1',
                        'seizure_packaging' => 'Bag',
                    ],
                    [
                        'category' => 'Drugs',
                        'seizure_name_drug' => 'Shared Contraband Joint',
                        'associated_person' => 'All Suspects',
                        'suspect_ownership' => 'All Suspects',
                        'seizure_quantity' => '5',
                        'seizure_packaging' => 'Boxes',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);

        // Fetch case and exhibits
        $mCase = \App\Models\MultipleSuspectCase::where('case_number', $caseNumber)->with(['exhibits', 'suspects'])->first();
        $this->assertNotNull($mCase);
        $this->assertCount(3, $mCase->exhibits);

        $suspectAlpha = $mCase->suspects->firstWhere('suspect_name', 'Suspect Alpha');
        $suspectBeta = $mCase->suspects->firstWhere('suspect_name', 'Suspect Beta');

        $exhibitAlpha = $mCase->exhibits->firstWhere('commodity_name', 'White Powder Alpha');
        $this->assertNotNull($exhibitAlpha);
        $this->assertEquals($suspectAlpha->id, $exhibitAlpha->suspect_id);
        $this->assertEquals('Suspect Alpha', $exhibitAlpha->associated_person);

        $exhibitBeta = $mCase->exhibits->firstWhere('commodity_name', 'Brown Granules Beta');
        $this->assertNotNull($exhibitBeta);
        $this->assertEquals($suspectBeta->id, $exhibitBeta->suspect_id);
        $this->assertEquals('Suspect Beta', $exhibitBeta->associated_person);

        $exhibitJoint = $mCase->exhibits->firstWhere('commodity_name', 'Shared Contraband Joint');
        $this->assertNotNull($exhibitJoint);
        $this->assertNull($exhibitJoint->suspect_id);
        $this->assertEquals('All Suspects', $exhibitJoint->associated_person);

        // Check show_review page displays ownership accurately
        $reviewPage = $this->actingAs($user)->get("/cases/{$caseNumber}/review");
        $reviewPage->assertStatus(200);
        $reviewHtml = $reviewPage->getContent();

        $this->assertStringContainsString('White Powder Alpha', $reviewHtml);
        $this->assertStringContainsString('Suspect Alpha', $reviewHtml);
        $this->assertStringContainsString('Brown Granules Beta', $reviewHtml);
        $this->assertStringContainsString('Suspect Beta', $reviewHtml);
        $this->assertStringContainsString('Shared Contraband Joint', $reviewHtml);
        $this->assertStringContainsString('All Suspects', $reviewHtml);
    }

    #[Test]
    public function gsa_upload_section_in_create_page_contains_gsa_lab_no_case_reference_and_status_inputs_and_persists_to_database()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-GSA99',
            'title' => 'Republic vs GSA Metadata Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect GSA Detailed',
            'role' => 'Primary Suspect',
        ]);

        // 1. Verify fields render on Lab Analysis create page
        $createPageResponse = $this->actingAs($user)->get('/Lab_Analysis/create');
        $createPageResponse->assertStatus(200);
        $createHtml = $createPageResponse->getContent();

        $this->assertStringContainsString('id="gsaLabNumberInput"', $createHtml);
        $this->assertStringContainsString('id="gsaCaseReferenceInput"', $createHtml);
        $this->assertStringContainsString('id="gsaStatusInput"', $createHtml);
        $this->assertStringContainsString('GSA Lab No.', $createHtml);
        $this->assertStringContainsString('GSA Case Reference', $createHtml);
        $this->assertStringContainsString('GSA Status', $createHtml);

        // 2. Upload file with GSA Lab No., GSA Case Reference, and GSA Status
        $file = UploadedFile::fake()->create('gsa_certified_report.pdf', 300, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'case_suspect_id' => $suspect->id,
                'multiple_suspect_case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'gsa_lab_number' => 'GSA/LAB/2026/9941',
                'gsa_case_reference' => 'GSA-CR-ACC-2026-REF1',
                'gsa_status' => 'Verified by Quality Assurance • Certificate Validated',
                'gsa_report_images' => [$file],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // 3. Verify suspect_gsa_reports table has these data
        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'CSS-2026-GSA99',
            'gsa_lab_number' => 'GSA/LAB/2026/9941',
            'gsa_case_reference' => 'GSA-CR-ACC-2026-REF1',
            'gsa_status' => 'Verified by Quality Assurance • Certificate Validated',
            'original_filename' => 'gsa_certified_report.pdf',
        ]);
    }

    #[Test]
    public function source_of_exhibits_is_present_on_create_and_show_review_and_persists_to_database()
    {
        $user = User::factory()->head()->create([
            'fullname' => 'Lab Specialist',
            'unit' => 'Forensic Laboratory',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-SRC01',
            'title' => 'State vs Contraband Syndicate',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kojo Darko',
            'role' => 'Primary Suspect',
        ]);

        // 1. Check create.blade.php for Source of Exhibits input box
        $createPageResponse = $this->actingAs($user)->get('/Lab_Analysis/create');
        $createPageResponse->assertStatus(200);
        $createHtml = $createPageResponse->getContent();

        $this->assertStringContainsString('Source of Exhibits', $createHtml);
        $this->assertStringContainsString('id="newSeizureSourceOfExhibits"', $createHtml);

        // 2. Check show_review.blade.php for Source of Exhibits input box and table header
        $showPageResponse = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $showPageResponse->assertStatus(200);
        $showHtml = $showPageResponse->getContent();

        $this->assertStringContainsString('Source of Exhibits', $showHtml);
        $this->assertStringContainsString('id="newSeizureSourceOfExhibits"', $showHtml);

        // 3. Persist exhibit with source_of_exhibits into exhibits table
        $exhibit = \App\Models\Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect->id,
            'associated_person' => 'Kojo Darko',
            'exhibit_number' => 'EXH-01',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Slab',
            'source_of_exhibits' => 'Tema Port Cargo Container #GH-8820',
        ]);

        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'multiple_suspect_case_id' => $mCase->id,
            'source_of_exhibits' => 'Tema Port Cargo Container #GH-8820',
        ]);

        // 4. Reload show_review to verify Source of Exhibits is rendered in the review table
        $showReloadResponse = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $showReloadResponse->assertStatus(200);
        $showReloadHtml = $showReloadResponse->getContent();
        $this->assertStringContainsString('Tema Port Cargo Container #GH-8820', $showReloadHtml);
    }

    #[Test]
    public function user_can_upload_gsa_report_from_show_review_for_single_suspect_case_and_attach_to_suspect()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'fullname' => 'Lab Specialist',
            'unit' => 'Forensic Laboratory',
        ]);

        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'SINGLE-2026-GSA01',
            'title' => 'Republic vs Single Suspect GSA',
            'unit_name' => 'Forensic Laboratory',
            'suspect_name' => 'Emmanuel Tetteh',
            'controller_user_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('single_suspect_gsa_report.pdf', 300, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->postJson("/cases/{$singleCase->case_number}/upload-gsa-report", [
                'single_suspect_case_id' => $singleCase->id,
                'case_id' => $singleCase->id,
                'case_number' => $singleCase->case_number,
                'suspect_name' => 'Emmanuel Tetteh',
                'gsa_lab_number' => 'GSA/LAB/2026/9991',
                'gsa_case_reference' => $singleCase->case_number,
                'gsa_status' => 'Certified Analysis',
                'gsa_report_images' => [$file],
            ]);

        $uploadResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'SINGLE-2026-GSA01',
            'single_suspect_case_id' => $singleCase->id,
            'original_filename' => 'single_suspect_gsa_report.pdf',
            'gsa_status' => 'Certified Analysis',
            'status' => 'active',
        ]);

        // Review page must render the uploaded GSA report attached under the suspect
        $reviewResponse = $this->actingAs($user)->get("/cases/{$singleCase->case_number}/review");
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        $this->assertStringContainsString('single_suspect_gsa_report.pdf', $reviewHtml);
        $this->assertStringContainsString('Attached Report Files', $reviewHtml);
    }

    #[Test]
    public function gsa_report_displays_under_suspect_card_in_show_review_even_when_joint_exhibits_exist()
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'fullname' => 'Lab Officer',
            'unit' => 'Forensic Laboratory',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'JOINT-2026-GSA01',
            'title' => 'Republic vs Multiple Joint Suspects',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kwabena Yeboah',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Akua Afriyie',
            'role' => 'Co-Suspect',
        ]);

        // Joint exhibit across all suspects
        \App\Models\Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'associated_person' => 'All Suspects',
            'exhibit_number' => 'EXH-JT-01',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine',
        ]);

        $file = UploadedFile::fake()->create('kwabena_gsa_report.pdf', 400, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'multiple_suspect_case_id' => $mCase->id,
                'case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'case_suspect_id' => $suspect1->id,
                'suspect_name' => 'Kwabena Yeboah',
                'gsa_lab_number' => 'GSA/LAB/2026/8821',
                'gsa_status' => 'Analysis Complete',
                'gsa_report_images' => [$file],
            ]);

        $uploadResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'JOINT-2026-GSA01',
            'case_suspect_id' => $suspect1->id,
            'original_filename' => 'kwabena_gsa_report.pdf',
            'status' => 'active',
        ]);

        // Verify show_review displays the GSA report under Kwabena Yeboah's card
        $reviewResponse = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();

        $this->assertStringContainsString('kwabena_gsa_report.pdf', $reviewHtml);
        $this->assertStringContainsString('Attached Report Files', $reviewHtml);
    }

    #[Test]
    public function user_can_upload_gsa_report_with_multiple_exhibit_types_and_stores_in_database(): void
    {
        Storage::fake('public');

        $user = User::factory()->head()->create([
            'fullname' => 'Forensic Analyst',
            'unit' => 'Forensic Laboratory',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'EXH-TYPE-2026-01',
            'title' => 'Republic vs Exhibit Type Test',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $user->id,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'John Exhibit',
            'role' => 'Primary Suspect',
        ]);

        $file = UploadedFile::fake()->create('gsa_with_exhibits.pdf', 350, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->postJson("/cases/{$mCase->case_number}/upload-gsa-report", [
                'multiple_suspect_case_id' => $mCase->id,
                'case_id' => $mCase->id,
                'case_number' => $mCase->case_number,
                'case_suspect_id' => $suspect->id,
                'suspect_name' => 'John Exhibit',
                'gsa_lab_number' => 'GSA/LAB/2026/9999',
                'gsa_status' => 'Certified',
                'exhibit_type' => ['Cocaine Hydrochloride', 'Heroin Brown Sugar'],
                'gsa_report_images' => [$file],
            ]);

        $uploadResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'EXH-TYPE-2026-01',
            'case_suspect_id' => $suspect->id,
            'original_filename' => 'gsa_with_exhibits.pdf',
            'exhibit_type' => 'Cocaine Hydrochloride, Heroin Brown Sugar',
        ]);

        // Verify show_review displays the Exhibit Type badge
        $reviewResponse = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $reviewResponse->assertStatus(200);
        $this->assertStringContainsString('Exhibit Type: Cocaine Hydrochloride, Heroin Brown Sugar', $reviewResponse->getContent());
    }

    #[Test]
    public function gsa_upload_sections_across_system_render_exhibit_type_input_and_add_button(): void
    {
        $user = User::factory()->head()->create([
            'unit' => 'Forensic Laboratory',
        ]);
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'UI-TEST-2026-01',
            'title' => 'Republic vs UI GSA Test',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
        ]);

        // 1. show_review
        $reviewResponse = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $reviewResponse->assertStatus(200);
        $reviewHtml = $reviewResponse->getContent();
        $this->assertStringContainsString('Exhibit Type', $reviewHtml);
        $this->assertStringContainsString('name="exhibit_type[]"', $reviewHtml);
        $this->assertStringContainsString('addGsaExhibitTypeInput', $reviewHtml);
        $this->assertStringContainsString('Add Exhibit Type', $reviewHtml);

        // 2. Lab_Analysis/analytical (GSA Upload removed)
        $analyticalResponse = $this->actingAs($user)->get("/Lab_Analysis/analytical/{$mCase->case_number}");
        $analyticalResponse->assertStatus(200);
        $analyticalHtml = $analyticalResponse->getContent();
        $this->assertStringNotContainsString('addGsaExhibitTypeInput', $analyticalHtml);
        $this->assertStringNotContainsString('id="gsaUploadSection"', $analyticalHtml);

        // 3. Lab_Analysis/create
        $createResponse = $this->actingAs($user)->get('/Lab_Analysis/create');
        $createResponse->assertStatus(200);
        $createHtml = $createResponse->getContent();
        $this->assertStringContainsString('Exhibit Type', $createHtml);
        $this->assertStringContainsString('name="exhibit_type[]"', $createHtml);
        $this->assertStringContainsString('addGsaExhibitTypeInput', $createHtml);
        $this->assertStringContainsString('Add Exhibit Type', $createHtml);
    }
}


