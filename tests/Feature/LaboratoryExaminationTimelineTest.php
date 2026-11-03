<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryExaminationTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'fullname' => 'Dr. Jane Analyst',
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);
    }

    public function test_saving_laboratory_examination_persists_timeline_dates_and_displays_them_on_result_card()
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NACOC-LAB-2026-001',
            'title' => 'Republic v John Doe',
            'unit_name' => 'Lab Analysis',
            'status' => 'In Progress',
            'user_id' => $this->user->id,
            'suspect_name' => 'John Doe',
            'laboratory_number' => 'LAB/CHEM/2026/042',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'associated_person' => 'John Doe',
            'exhibit_number' => 'EXH-001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'White Powder Suspected Cocaine',
            'seizure_weight' => 500,
            'seizure_unit' => 'g',
        ]);

        $postData = [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'officer' => 'Dr. Jane Analyst',
            'notes' => 'Confirmed cocaine hydrochloride with 88.5% purity.',
            'exhibit_id' => $exhibit->id,
            'exhibit_owner_name' => 'John Doe',
            'exhibit_is_associate' => false,
            'lab_exam_reference' => 'NLR-FM-T42-F',
            'lab_exhibit_number' => 'EXH-001',
            'laboratory_number' => 'LAB/CHEM/2026/042',
            'lab_unit' => 'Chemistry & Narcotics Unit',
            'presumptive_result' => 'YES (Presence of: Cocaine - Purity/Percentage: 88.5%)',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'White Powder Suspected Cocaine',
            'sample_weight_units' => 'Grams',
            'sample_weight_gross' => '500g',
            'sample_weight_taken' => '10g',
            'sample_weight_reserved' => '490g',
            'confirmatory_substance_identification' => 'Cocaine Hydrochloride',
            'detected_substances' => [['substance' => 'Cocaine', 'percentage' => '88.5']],
            'confirmatory_test_methods' => 'GC-MS / FTIR',
            'confirmatory_specifications' => 'Meets forensic grade purity standard',
            'confirmatory_remark' => 'High purity illicit cocaine consignment',
            'lab_date_received' => '2026-10-15',
            'lab_date_started' => '2026-10-16',
            'lab_date_completed' => '2026-10-18',
            'date_received' => '2026-10-15',
            'date_started' => '2026-10-16',
            'date_completed' => '2026-10-18',
        ];

        $response = $this->actingAs($this->user)->postJson("/cases/{$case->case_number}/update-unit-progress", $postData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 1. Verify in DB table laboratory_examination_results
        $dbLab = LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)->first();
        $this->assertNotNull($dbLab, 'LaboratoryExaminationResult should be created in DB');
        $this->assertEquals('2026-10-15', $dbLab->date_received?->format('Y-m-d'));
        $this->assertEquals('2026-10-16', $dbLab->date_started?->format('Y-m-d'));
        $this->assertEquals('2026-10-18', $dbLab->date_completed?->format('Y-m-d'));
        $this->assertTrue((bool)$dbLab->has_saved);

        // 2. Verify in ForensicCase::resolveLabExamForSuspect
        $wrapped = ForensicCase::findCase($case->id);
        $resolved = $wrapped->resolveLabExamForSuspect('John Doe', null, null, 'Primary Suspect', 0);
        $this->assertNotNull($resolved);
        $this->assertEquals('2026-10-15', $resolved['date_received']);
        $this->assertEquals('2026-10-16', $resolved['date_started']);
        $this->assertEquals('2026-10-18', $resolved['date_completed']);

        // 3. Verify in review view HTML rendering
        $viewResponse = $this->actingAs($this->user)->get("/cases/{$case->id}/review?unit=Lab+Analysis&suspect=John+Doe");
        $viewResponse->assertStatus(200);
        $html = $viewResponse->getContent();

        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT', $html);
        $this->assertStringContainsString('Examination Timeline:', $html);
        $this->assertStringContainsString('Received: <strong class="text-blue-900">2026-10-15</strong>', $html);
        $this->assertStringContainsString('Started: <strong class="text-amber-900">2026-10-16</strong>', $html);
        $this->assertStringContainsString('Completed: <strong class="text-emerald-900">2026-10-18</strong>', $html);
        $this->assertDoesNotMatchRegularExpression('/Received:\s*<strong[^>]*>\s*N\/A\s*<\/strong>/', $html);
        $this->assertDoesNotMatchRegularExpression('/Started:\s*<strong[^>]*>\s*N\/A\s*<\/strong>/', $html);
        $this->assertDoesNotMatchRegularExpression('/Completed:\s*<strong[^>]*>\s*N\/A\s*<\/strong>/', $html);
    }

    public function test_multiple_suspect_associate_lab_exam_persists_timeline_dates_and_displays_them()
    {
        $case = MultipleSuspectCase::create([
            'case_number' => 'NACOC-LAB-2026-MULTI',
            'title' => 'Republic v Primary & Associate',
            'unit_name' => 'Lab Analysis',
            'status' => 'In Progress',
            'user_id' => $this->user->id,
            'total_suspects_count' => 2,
            'laboratory_number' => 'LAB/CHEM/2026/099',
        ]);

        $primary = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kofi Mensah',
        ]);

        $associate = CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Ama Serwaa',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $case->id,
            'case_suspect_id' => $associate->id,
            'associated_person' => 'Ama Serwaa',
            'exhibit_number' => 'EXH-002',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine Crystals',
            'seizure_weight' => 120,
            'seizure_unit' => 'g',
        ]);

        $postData = [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'officer' => 'Dr. Jane Analyst',
            'notes' => 'Confirmed methamphetamine crystals.',
            'exhibit_id' => $exhibit->id,
            'exhibit_owner_name' => 'Ama Serwaa',
            'exhibit_is_associate' => true,
            'associate_id' => $associate->id,
            'lab_exam_reference' => 'NLR-FM-T99-F',
            'lab_exhibit_number' => 'EXH-002',
            'laboratory_number' => 'LAB/CHEM/2026/099',
            'lab_unit' => 'Chemistry & Narcotics Unit',
            'presumptive_result' => 'YES (Presence of: Methamphetamine - Purity/Percentage: 92%)',
            'confirmatory_test_result' => 'YES',
            'sample_weight_description' => 'Methamphetamine Crystals',
            'sample_weight_units' => 'Grams',
            'sample_weight_gross' => '120g',
            'sample_weight_taken' => '5g',
            'sample_weight_reserved' => '115g',
            'confirmatory_substance_identification' => 'Methamphetamine Hydrochloride',
            'detected_substances' => [['substance' => 'Methamphetamine', 'percentage' => '92.0']],
            'confirmatory_test_methods' => 'GC-MS',
            'confirmatory_specifications' => 'Meets crystalline methamphetamine profile',
            'confirmatory_remark' => 'High purity crystal meth',
            'lab_date_received' => '2026-10-20',
            'lab_date_started' => '2026-10-21',
            'lab_date_completed' => '2026-10-22',
            'date_received' => '2026-10-20',
            'date_started' => '2026-10-21',
            'date_completed' => '2026-10-22',
        ];

        $response = $this->actingAs($this->user)->postJson("/cases/{$case->case_number}/update-unit-progress", $postData);
        $response->assertStatus(200);

        // Verify in DB table associate_lab_examinations
        $dbAssoc = AssociateLabExamination::where('case_id', $case->id)
            ->where('suspect_name', 'Ama Serwaa')
            ->first();
        $this->assertNotNull($dbAssoc, 'AssociateLabExamination should be created in DB');
        $this->assertEquals('2026-10-20', $dbAssoc->date_received?->format('Y-m-d'));
        $this->assertEquals('2026-10-21', $dbAssoc->date_started?->format('Y-m-d'));
        $this->assertEquals('2026-10-22', $dbAssoc->date_completed?->format('Y-m-d'));

        // Verify in resolveLabExamForSuspect
        $wrapped = ForensicCase::findCase($case->id);
        $resolved = $wrapped->resolveLabExamForSuspect('Ama Serwaa', $associate->id, $associate->id, 'Associate Suspect', 1);
        $this->assertNotNull($resolved);
        $this->assertEquals('2026-10-20', $resolved['date_received']);
        $this->assertEquals('2026-10-21', $resolved['date_started']);
        $this->assertEquals('2026-10-22', $resolved['date_completed']);

        // Verify in review view HTML rendering for associate
        $viewResponse = $this->actingAs($this->user)->get("/cases/{$case->id}/review?unit=Lab+Analysis&suspect=Ama+Serwaa");
        $viewResponse->assertStatus(200);
        $html = $viewResponse->getContent();

        $this->assertStringContainsString('LABORATORY EXAMINATION RESULT', $html);
        $this->assertStringContainsString('Examination Timeline:', $html);
        $this->assertStringContainsString('Received: <strong class="text-blue-900">2026-10-20</strong>', $html);
        $this->assertStringContainsString('Started: <strong class="text-amber-900">2026-10-21</strong>', $html);
        $this->assertStringContainsString('Completed: <strong class="text-emerald-900">2026-10-22</strong>', $html);
    }
}
