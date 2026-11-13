<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabAssociateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lab_officer_associate_page_renders_lab_seizure_fields_and_report_modules(): void
    {
        $labUser = User::factory()->create([
            'unit' => 'Lab Analysis',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-91001',
            'title' => 'Republic vs Primary Test Suspect',
            'suspect_name' => 'Primary Test Suspect',
            'unit_name' => 'Lab Analysis',
            'user_id' => $labUser->id,
        ]);

        $response = $this->actingAs($labUser)->get("/cases/{$case->case_number}/associate?unit=Lab+Analysis");

        $response->assertOk();
        $response->assertSee('Section 4: Seizures', false);
        $response->assertSee('hasSeizuresCheckbox');
        $response->assertDontSee('Section 3: Seized Commodities &amp; Contraband Manifest', false);
        $response->assertDontSee('Section 3: Add Associated Drug Exhibits &amp; Commodities', false);
        $response->assertDontSee('Link Existing Seized Exhibit(s)');
        $response->assertSee('Report &amp; Laboratory Examination Modules', false);
        $response->assertSee('Upload GSA File');
        $response->assertSee('Examination Information (NLR)');
        $response->assertSee('Select Seized Exhibit (Manifest Sync)');
        $response->assertSee('Packaging as Received');
        $response->assertSee("ACTIVE_UNIT_KEY === 'lab'", false);
        $response->assertDontSee("ACTIVE_UNIT_KEY === 'lab_analysis'", false);
        $response->assertSee('newSeizureConcealment');
        $response->assertSee('newSeizureDate');
        $response->assertSee('newSeizureTime');
        $response->assertSee('source_of_exhibits');
    }

    #[Test]
    public function associate_case_persists_lab_seizure_fields_linked_exhibits_and_gsa_report(): void
    {
        Storage::fake('public');

        $labUser = User::factory()->create([
            'unit' => 'Lab Analysis',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-91002',
            'title' => 'Republic vs Primary Suspect',
            'suspect_name' => 'Primary Suspect',
            'unit_name' => 'Lab Analysis',
            'user_id' => $labUser->id,
        ]);

        $unassignedExhibit = Exhibit::create([
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Hydrochloride',
            'seizure_quantity' => '4',
            'seizure_packaging' => 'Slabs',
            'seizure_weight' => '4.2',
            'seizure_unit' => 'kg',
        ]);

        $response = $this->actingAs($labUser)->post("/cases/{$case->case_number}/associate", [
            'from_unit' => 'Lab Analysis',
            'lab_workflow_mode' => 'gsa',
            'gsa_lab_number' => 'GSA-LAB-2026-77',
            'gsa_status' => 'active',
            'exhibit_type' => ['Cocaine'],
            'gsa_report_images' => [
                UploadedFile::fake()->create('gsa_report.pdf', 120, 'application/pdf'),
            ],
            'linked_exhibit_ids' => [$unassignedExhibit->id],
            'associated_persons' => [
                [
                    'associate_id' => 'ASC-001',
                    'suspect_name' => 'Kwame Associate',
                    'role' => 'Co-Suspect',
                ],
            ],
            'associated_exhibits' => [
                [
                    'category' => 'Drugs',
                    'commodity_name' => 'Brown Parcel Wrapped in Foil',
                    'seizure_contents' => 'Compressed Cannabis',
                    'seizure_concealment' => 'False bottom suitcase',
                    'seizure_date' => '2026-11-10',
                    'seizure_time' => '14:30',
                    'seizure_officer' => 'DSP Mensah',
                    'seizure_reason' => 'Airport screening',
                    'source_of_exhibits' => 'KIA Arrival Hall',
                    'investigating_officer' => 'Insp. Boateng',
                    'source_requesting_unit' => 'KIA Command',
                    'exhibit_remark' => 'Intact seal',
                    'associated_person' => 'Kwame Associate',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('exhibits', [
            'commodity_name' => 'Brown Parcel Wrapped in Foil',
            'contents' => 'Compressed Cannabis',
            'seizure_concealment' => 'False bottom suitcase',
            'seizure_date' => '2026-11-10',
            'seizure_time' => '14:30',
        ]);

        $unassignedExhibit->refresh();
        $this->assertNotNull($unassignedExhibit->multiple_suspect_case_id);
        $this->assertSame('Kwame Associate', $unassignedExhibit->associated_person);

        $this->assertDatabaseHas('suspect_gsa_reports', [
            'case_number' => 'NCC-2026-91002',
            'lab_number' => 'GSA-LAB-2026-77',
            'exhibit_type' => 'Cocaine',
            'original_filename' => 'gsa_report.pdf',
        ]);
        $this->assertSame(1, SuspectGsaReport::where('case_number', 'NCC-2026-91002')->count());
    }

    #[Test]
    public function associate_case_persists_nlr_examination_result_when_nlr_mode_selected(): void
    {
        $labUser = User::factory()->create([
            'unit' => 'Lab Analysis',
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-91003',
            'title' => 'Republic vs Primary Suspect Three',
            'suspect_name' => 'Primary Suspect Three',
            'unit_name' => 'Lab Analysis',
            'user_id' => $labUser->id,
        ]);

        $response = $this->actingAs($labUser)->post("/cases/{$case->case_number}/associate", [
            'from_unit' => 'Lab Analysis',
            'lab_workflow_mode' => 'nlr',
            'associated_persons' => [
                [
                    'associate_id' => 'ASC-001',
                    'suspect_name' => 'Ama Associate',
                    'role' => 'Co-Suspect',
                ],
            ],
            'associated_exhibits' => [
                [
                    'category' => 'Drugs',
                    'commodity_name' => 'White Powder Sachet',
                    'seizure_contents' => 'White crystalline powder',
                    'seizure_date' => '2026-11-11',
                    'seizure_officer' => 'Officer Kofi',
                    'associated_person' => 'Ama Associate',
                ],
            ],
            'purpose_of_examination' => 'Confirmatory identification of narcotic substance',
            'presumptive_test_result' => 'Positive',
            'confirmatory_test_result' => 'Positive',
            'confirmatory_substance_identification' => 'Cocaine',
            'findings' => 'Sample tested positive for Cocaine hydrochloride.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertGreaterThan(
            0,
            LaboratoryExaminationResult::count()
        );
    }

    #[Test]
    public function every_unit_has_its_own_associate_view_and_renders_successfully(): void
    {
        $user = User::factory()->create(['unit' => 'Directorate', 'is_head' => true]);
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-91004',
            'title' => 'Republic vs Multi Unit Case',
            'suspect_name' => 'Multi Unit Suspect',
            'unit_name' => 'Directorate',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get("/cases/{$case->case_number}/associate?unit=Lab+Analysis")
            ->assertOk()
            ->assertSee('LABORATORY ANALYSIS');

        $this->actingAs($user)->get("/cases/{$case->case_number}/associate?unit=Digital+Forensics")
            ->assertOk()
            ->assertSee('DIGITAL &amp; PHYSICAL FORENSICS', false);

        $this->actingAs($user)->get("/cases/{$case->case_number}/associate?unit=Crime+Scene+Support")
            ->assertOk()
            ->assertSee('CRIME SCENE SUPPORT');

        $this->actingAs($user)->get("/cases/{$case->case_number}/associate?unit=Evidence+Management")
            ->assertOk()
            ->assertSee('EVIDENCE MANAGEMENT');

        $this->actingAs($user)->get("/cases/{$case->case_number}/associate?unit=Directorate")
            ->assertOk()
            ->assertSee('DIRECTORATE');
    }
}
