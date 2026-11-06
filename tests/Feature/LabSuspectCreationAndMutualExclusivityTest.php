<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\User;
use App\Services\CaseDocketService;
use App\Services\LaboratoryExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabSuspectCreationAndMutualExclusivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[Test]
    public function adding_new_suspect_does_not_automatically_generate_or_attach_drug_screening_for_new_suspect(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        // 1. Create existing case with primary suspect and existing drug screening
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'TEST-LAB-NO-AUTODOC-01',
            'title' => 'State vs Target Alpha',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'laboratory_number' => 'Lab-01/2026',
        ]);

        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Alpha Prime',
            'role' => 'Primary Suspect',
            'ghana_card' => 'GHA-000000001-1',
            'gender' => 'Male',
        ]);

        // Primary suspect has genuine screening
        DrugScreening::create([
            'suspect_id' => $primarySuspect->id,
            'purpose' => 'Custody Intake Screening',
            'screening_date' => now(),
            'result' => 'Positive',
            'substances_found' => 'Cocaine',
        ]);

        // 2. Add a new associate suspect to the case
        $docketService = app(CaseDocketService::class);
        $forensicCase = ForensicCase::find($mCase->id);

        $request = new Request([
            'associated_persons' => [
                [
                    'suspect_name' => 'Bravo Secondary',
                    'role' => 'Co-Suspect',
                    'ghana_card' => 'GHA-000000002-2',
                    'gender' => 'Female',
                ],
            ],
        ]);

        $result = $docketService->associateCase($forensicCase, $request);
        $this->assertTrue($result['success'] ?? false);

        // Fetch the new suspect
        $newSuspect = CaseSuspect::where('multiple_suspect_case_id', $mCase->id)
            ->where('suspect_name', 'Bravo Secondary')
            ->first();

        $this->assertNotNull($newSuspect);

        // Verify no DrugScreening was created in DB for new suspect
        $newSuspectScreeningsCount = DrugScreening::where('suspect_id', $newSuspect->id)->count();
        $this->assertEquals(0, $newSuspectScreeningsCount, 'New suspect must NOT have any DrugScreening record created.');

        // Verify DrugScreening::forSuspect does not resolve any screening for new suspect
        $resolvedScreening = DrugScreening::forSuspect(
            $newSuspect->id,
            $newSuspect->suspect_name,
            $mCase->id,
            $mCase->case_number
        );
        $this->assertNull($resolvedScreening, 'DrugScreening::forSuspect must return null for newly added suspect.');

        // Verify primary suspect's screening is strictly intact
        $primaryScreening = DrugScreening::forSuspect(
            $primarySuspect->id,
            $primarySuspect->suspect_name,
            $mCase->id,
            $mCase->case_number
        );
        $this->assertNotNull($primaryScreening, 'Primary suspect screening must remain intact.');
        $this->assertEquals('Positive', $primaryScreening->result);
    }

    #[Test]
    public function adding_new_suspect_does_not_resolve_or_auto_display_lab_examination_result(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        // Create case with primary suspect and primary lab examination
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'TEST-LAB-NO-AUTODOC-02',
            'title' => 'State vs Primary Target',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'laboratory_number' => 'Lab-02/2026',
        ]);

        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Charlie Primary',
            'role' => 'Primary Suspect',
            'ghana_card' => 'GHA-000000003-3',
            'gender' => 'Male',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $primarySuspect->id,
            'laboratory_number' => 'Lab-02/2026',
            'presumptive_result' => 'YES (Presence of: COCA - Purity: 75%)',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Cocaine',
            'has_saved' => 1,
        ]);

        // Add a new associate suspect
        $newSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Delta Associate',
            'role' => 'Co-Suspect',
            'associate_id' => 'ASC-002',
            'ghana_card' => 'GHA-000000004-4',
            'gender' => 'Female',
        ]);

        $forensicCase = ForensicCase::find($mCase->id);

        // Resolve lab exam for primary suspect
        $primaryExam = $forensicCase->resolveLabExamForSuspect(
            $primarySuspect->suspect_name,
            $primarySuspect->id,
            null,
            $primarySuspect->role,
            0
        );
        $this->assertNotNull($primaryExam, 'Primary suspect must have their exam resolved.');
        $substance = is_array($primaryExam) ? ($primaryExam['substance_identification'] ?? null) : ($primaryExam->substance_identification ?? null);
        $this->assertEquals('Cocaine', $substance);

        // Resolve lab exam for secondary suspect - MUST return null (no fallback to primary)
        $newSuspectExam = $forensicCase->resolveLabExamForSuspect(
            $newSuspect->suspect_name,
            $newSuspect->id,
            $newSuspect->associate_id,
            $newSuspect->role,
            1
        );
        $this->assertNull($newSuspectExam, 'Secondary suspect must NOT resolve or fall back to primary suspect exam.');

        // Test show_review page rendering: the new suspect must NOT display the lab examination result card
        $response = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $response->assertStatus(200);

        $html = $response->getContent();
        // The page must contain the suspects
        $this->assertStringContainsString('Charlie Primary', $html);
        $this->assertStringContainsString('Delta Associate', $html);
    }

    #[Test]
    public function backend_enforces_gsa_and_nlr_mutual_exclusivity_when_gsa_files_are_uploaded(): void
    {
        $user = User::factory()->create([
            'fullname' => 'Lab Officer Smith',
            'unit' => 'Forensic Laboratory',
            'is_head' => false,
        ]);

        $gsaFile = UploadedFile::fake()->create('gsa_certificate.pdf', 500, 'application/pdf');

        $caseNumber = 'NCC-2026-MUTUAL-01';

        $payload = [
            'case_number' => $caseNumber,
            'title' => 'Republic vs Echo Trafficker',
            'unit_name' => 'Forensic Laboratory',
            'case_category' => 'DRUGS / NARCOTICS',
            'case_type' => 'MULTIPLE',
            'submitting_unit' => 'Forensic Laboratory',
            'operation_code' => 'OPS-TEST-001',
            'incident_date' => now()->toDateString(),
            'raid_location' => 'Tema Port Warehouse',
            'offense_description' => 'Suspected narcotics trafficking',
            'suspect_name' => 'Echo Trafficker',
            'laboratory_number' => 'Lab-GSA-TEST-99',
            'exhibit_type' => 'Cocaine Hydrochloride',
            'gsa_report_images' => [$gsaFile],
            // NLR form fields submitted concurrently
            'presumptive_result' => 'YES (Presence of: COCA)',
            'presumptive_test_result' => 'YES',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Cocaine',
            'sample_weight_taken' => '2.5',
            'sample_weight_reserved' => '1.5',
        ];

        // Perform case store request with GSA file uploaded
        $response = $this->actingAs($user)->post('/cases', $payload);
        $response->assertSessionHasNoErrors();

        $createdCase = MultipleSuspectCase::where('case_number', $caseNumber)->first();
        if (!$createdCase) {
            $createdCase = SingleSuspectCase::where('case_number', $caseNumber)->first();
        }
        $this->assertNotNull($createdCase, 'Case should have been created in database.');

        // Verify GSA report was saved
        $gsaReportsCount = SuspectGsaReport::where('case_number', $caseNumber)->count();
        $this->assertGreaterThan(0, $gsaReportsCount, 'GSA report should be saved and attached.');

        // Verify NLR examination was NOT created due to backend mutual exclusivity
        $nlrRecordsCount = LaboratoryExaminationResult::where('case_number', $caseNumber)
            ->orWhere('laboratory_number', 'Lab-GSA-TEST-99')
            ->count();
        $this->assertEquals(0, $nlrRecordsCount, 'Conflicting NLR LaboratoryExaminationResult must NOT be created when GSA file is uploaded.');

        $forensicCase = ForensicCase::find($caseNumber);
        $this->assertNotNull($forensicCase, 'ForensicCase model must be resolvable.');
        $docket = $forensicCase->docket_data;
        $hasSavedLabExam = !empty($docket['lab_examination']['has_saved']);
        $this->assertFalse($hasSavedLabExam, 'docket_data lab_examination has_saved must NOT be set when GSA file is uploaded.');
    }

    #[Test]
    public function backend_persists_nlr_examination_normally_when_no_gsa_file_is_uploaded(): void
    {
        $user = User::factory()->create([
            'fullname' => 'Lab Officer Smith',
            'unit' => 'Forensic Laboratory',
            'is_head' => false,
        ]);

        $caseNumber = 'NCC-2026-NLR-ONLY-02';

        $payload = [
            'case_number' => $caseNumber,
            'title' => 'Republic vs Foxtrot Dealer',
            'unit_name' => 'Forensic Laboratory',
            'case_category' => 'DRUGS / NARCOTICS',
            'case_type' => 'SINGLE',
            'submitting_unit' => 'Forensic Laboratory',
            'operation_code' => 'OPS-TEST-002',
            'incident_date' => now()->toDateString(),
            'raid_location' => 'Accra Central',
            'offense_description' => 'Suspected narcotics trafficking',
            'suspect_name' => 'Foxtrot Dealer',
            'laboratory_number' => 'Lab-NLR-ONLY-88',
            // Genuine NLR form data with NO GSA file
            'presumptive_result' => 'YES (Presence of: HERO)',
            'presumptive_test_result' => 'YES',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Heroin',
            'sample_weight_taken' => '1.0',
            'sample_weight_reserved' => '0.5',
        ];

        $response = $this->actingAs($user)->post('/cases', $payload);
        $response->assertSessionHasNoErrors();

        $createdCase = SingleSuspectCase::where('case_number', $caseNumber)->first();
        if (!$createdCase) {
            $createdCase = MultipleSuspectCase::where('case_number', $caseNumber)->first();
        }
        $this->assertNotNull($createdCase, 'Case should have been created in database.');

        // Verify NO GSA reports exist
        $gsaReportsCount = SuspectGsaReport::where('case_number', $caseNumber)->count();
        $this->assertEquals(0, $gsaReportsCount, 'No GSA reports should exist.');

        // Verify NLR examination WAS created normally and can be resolved
        $forensicCase = ForensicCase::find($caseNumber);
        $this->assertNotNull($forensicCase, 'ForensicCase model must be resolvable.');
        $labExam = $forensicCase->resolveLabExamForSuspect($forensicCase->suspect_name, 0);
        $this->assertNotNull($labExam, 'Lab exam should be resolvable for primary suspect.');
        $this->assertTrue(!empty($labExam['has_saved']), 'Lab exam has_saved must be true.');
    }

    #[Test]
    public function show_review_page_renders_mutual_exclusivity_javascript_guards(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Laboratory',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'TEST-LAB-EXCL-JS-01',
            'title' => 'State vs Suspect Exclusivity',
            'unit_name' => 'Forensic Laboratory',
            'controller_user_id' => $user->id,
            'laboratory_number' => 'Lab-EXCL-01',
        ]);

        $response = $this->actingAs($user)->get("/cases/{$mCase->case_number}/review");
        $response->assertStatus(200);

        $html = $response->getContent();
        // Check that applyGsaNlrMutualExclusivity is present in the review page
        $this->assertStringContainsString('applyGsaNlrMutualExclusivity', $html);
        $this->assertStringContainsString('toggleNlrReportBtn', $html);
    }

    #[Test]
    public function gsa_upload_with_commodities_does_not_create_or_display_laboratory_examination_result(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $gsaFile = UploadedFile::fake()->create('gsa_report_scan.pdf', 300, 'application/pdf');
        $caseNumber = 'NCC-2026-GSA-SECT4-01';

        $payload = [
            'case_number' => $caseNumber,
            'title' => 'State vs Warehouse Smuggler',
            'unit_name' => 'Lab Analysis',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Warehouse Smuggler',
            'has_seizures' => 1,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Cocaine',
            'seizure_name_drug' => 'Cocaine',
            'seizure_quantity' => '2',
            'seizure_packaging' => 'Blocks',
            'seizure_weight' => '1.5',
            'seizure_unit' => 'Kilograms (kg)',
            'gsa_lab_number' => 'GSA/LAB/2026/099',
            'gsa_case_reference' => 'REF-GSA-99',
            'gsa_status' => 'Certified Analysis',
            'gsa_report_images' => [$gsaFile],
            'gsa_files_uploaded' => 1,
            'lab_workflow_mode' => 'gsa',
            'section5_completed' => 0,
        ];

        $response = $this->actingAs($user)->post('/cases', $payload);
        $response->assertSessionHasNoErrors();

        // 1. Assert no LaboratoryExaminationResult was created in the database
        $this->assertEquals(
            0,
            LaboratoryExaminationResult::where('case_type', 'SINGLE')->orWhere('laboratory_number', 'GSA/LAB/2026/099')->count(),
            'GSA workflow upload must NOT create a LaboratoryExaminationResult.'
        );

        // 2. Assert GSA report is saved
        $this->assertGreaterThan(
            0,
            SuspectGsaReport::where('case_number', $caseNumber)->count(),
            'GSA report should be saved and attached.'
        );

        // 3. Request show_review page and assert no Laboratory Examination Result form is displayed
        $reviewResponse = $this->actingAs($user)->get("/cases/{$caseNumber}/review");
        $reviewResponse->assertStatus(200);

        $html = $reviewResponse->getContent();
        // The page must contain the suspect and exhibit information
        $this->assertStringContainsString('Warehouse Smuggler', $html);
        $this->assertStringContainsString('Cocaine', $html);
        // The page must NOT display a verified Laboratory Examination Result form
        $this->assertStringNotContainsString('✓ VERIFIED RESULT LOGGED', $html);
        $this->assertStringNotContainsString('LABORATORY EXAMINATION RESULT — WAREHOUSE SMUGGLER', $html);
    }

    #[Test]
    public function uploading_gsa_report_via_store_endpoint_does_not_create_laboratory_examination_result(): void
    {
        $user = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'TEST-GSA-STORE-01',
            'title' => 'State vs Isolated Suspect',
            'unit_name' => 'Lab Analysis',
            'controller_user_id' => $user->id,
            'laboratory_number' => 'Lab-STORE-01',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Isolated Suspect',
            'role' => 'Primary Suspect',
        ]);

        $gsaFile = UploadedFile::fake()->create('signed_gsa.pdf', 250, 'application/pdf');

        $response = $this->actingAs($user)->post('/Lab_Analysis/gsa-reports/store', [
            'case_number' => $mCase->case_number,
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_name' => $suspect->suspect_name,
            'gsa_lab_number' => 'GSA-OFFICIAL-77',
            'gsa_case_reference' => 'GSA-CR-77',
            'gsa_status' => 'Pending Verification',
            'gsa_report_images' => [$gsaFile],
        ]);

        $response->assertJson(['success' => true]);

        // Verify SuspectGsaReport was created
        $this->assertEquals(1, SuspectGsaReport::where('case_number', $mCase->case_number)->count());

        // Verify LaboratoryExaminationResult was NOT created or modified
        $this->assertEquals(
            0,
            LaboratoryExaminationResult::where('multiple_suspect_case_id', $mCase->id)->count(),
            'Uploading GSA report must NOT create a LaboratoryExaminationResult.'
        );

        // Verify Lab Analysis container in Unit Directorate Progress turns from pending to completed
        $reviewResp = $this->actingAs($user)->get('/cases/' . $mCase->case_number . '/review');
        $reviewResp->assertStatus(200);
        $html = $reviewResp->getContent();
        $this->assertStringContainsString('unitCard-labanalysis', $html);
        $this->assertStringContainsString('unitBadge-labanalysis', $html);
        $this->assertMatchesRegularExpression('/id="unitBadge-labanalysis"[^>]*>\s*COMPLETED/i', $html);
    }
}
