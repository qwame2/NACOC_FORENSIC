<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DynamicExhibitIdentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function createLabOfficer(): User
    {
        return User::factory()->create([
            'fullname' => 'Officer Lab Examiner',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => true,
        ]);
    }

    #[Test]
    public function exhibits_table_has_lab_exhibit_no_column_and_unique_exhibit_number(): void
    {
        $this->assertTrue(Schema::hasColumn('exhibits', 'lab_exhibit_no'));
        $this->assertTrue(Schema::hasColumn('exhibits', 'exhibit_number'));

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC/2026/0001',
            'title' => 'Republic vs Test One',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Test One',
        ]);

        $ex1 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Slabs',
            'lab_exhibit_no' => 'A1',
        ]);

        $this->assertEquals('EXH-00001', $ex1->exhibit_number);
        $this->assertEquals('A1', $ex1->lab_exhibit_no);

        // Database-level unique constraint rejects duplicate raw SQL exhibit_number insert
        $this->expectException(QueryException::class);
        DB::table('exhibits')->insert([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-00001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Duplicate Raw Insert',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function system_generated_exhibit_id_follows_exh_00000_format_and_increments_sequentially(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC/2026/0002',
            'title' => 'Republic vs Sequential Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Sequential Suspect',
        ]);

        $ex1 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Sample 1',
        ]);
        $ex2 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Sample 2',
        ]);
        $ex3 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Sample 3',
        ]);

        $this->assertEquals('EXH-00001', $ex1->exhibit_number);
        $this->assertEquals('EXH-00002', $ex2->exhibit_number);
        $this->assertEquals('EXH-00003', $ex3->exhibit_number);

        // Verify higher sequence formatting (EXH-00010, EXH-00125, EXH-12345)
        $this->assertEquals('EXH-00010', Exhibit::formatSystemExhibitNumber(10));
        $this->assertEquals('EXH-00125', Exhibit::formatSystemExhibitNumber(125));
        $this->assertEquals('EXH-12345', Exhibit::formatSystemExhibitNumber(12345));

        // Seed an exhibit with EXH-00124 and verify next auto-generated ID is EXH-00125
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-00124',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Sample 124',
        ]);

        $ex125 = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Sample 125',
        ]);

        $this->assertEquals('EXH-00125', $ex125->exhibit_number);
    }

    #[Test]
    public function updating_exhibit_never_changes_system_generated_exhibit_number_and_preserves_lab_exhibit_no(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC/2026/0003',
            'title' => 'Republic vs Immutable ID Test',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Immutable Suspect',
        ]);

        $exhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cannabis Sativa',
            'lab_exhibit_no' => 'Ai',
        ]);

        $originalSystemId = $exhibit->exhibit_number;
        $this->assertEquals('EXH-00001', $originalSystemId);
        $this->assertEquals('Ai', $exhibit->lab_exhibit_no);

        // Attempt to update exhibit including a different exhibit_number and new lab_exhibit_no
        $exhibit->update([
            'exhibit_number' => 'OVERWRITE-ATTEMPT',
            'lab_exhibit_no' => 'Aii',
            'commodity_name' => 'Cannabis Sativa (Compressed)',
        ]);

        $exhibit->refresh();
        $this->assertEquals('EXH-00001', $exhibit->exhibit_number, 'System exhibit_number must never change on update.');
        $this->assertEquals('Aii', $exhibit->lab_exhibit_no, 'Manually entered lab_exhibit_no must update independently.');
        $this->assertEquals('Cannabis Sativa (Compressed)', $exhibit->commodity_name);
    }

    #[Test]
    public function case_creation_and_lab_exam_save_map_manual_exhibit_no_to_lab_exhibit_no_and_keep_system_id(): void
    {
        $officer = $this->createLabOfficer();

        $payload = [
            'title' => 'Republic vs Kwesi Appiah',
            'suspect_name' => 'Kwesi Appiah',
            'unit_name' => 'Lab Analysis',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name' => 'Heroin Base',
            'seizure_name_drug' => 'Heroin Base',
            'lab_exhibit_no' => 'A1',
            'lab_exhibit_nos' => ['A1', 'B1'],
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kwesi Appiah', 'role' => 'Primary Suspect'],
                ],
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin Base',
                        'seizure_quantity' => '3',
                        'seizure_packaging' => 'Parcels',
                        'seizure_weight' => '150',
                        'seizure_unit' => 'g',
                        'contents' => 'Brown powder in foil',
                        'lab_exhibit_no' => 'A1',
                    ],
                    [
                        'exhibit_category' => 'Drugs',
                        'seizure_name_drug' => 'Cocaine Hydrochloride',
                        'seizure_quantity' => '2',
                        'seizure_packaging' => 'Slabs',
                        'seizure_weight' => '200',
                        'seizure_unit' => 'g',
                        'contents' => 'White compressed blocks',
                        'lab_exhibit_no' => 'B1',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibits = Exhibit::orderBy('id')->get();
        $this->assertCount(2, $exhibits);

        $this->assertEquals('EXH-00001', $exhibits[0]->exhibit_number);
        $this->assertEquals('A1', $exhibits[0]->lab_exhibit_no);

        $this->assertEquals('EXH-00002', $exhibits[1]->exhibit_number);
        $this->assertEquals('B1', $exhibits[1]->lab_exhibit_no);

        // Reopen in show_review and verify both system Exhibit ID badge and manual lab_exhibit_no are rendered
        $singleCase = SingleSuspectCase::first();
        $this->assertNotNull($singleCase);
        $case = ForensicCase::find($singleCase->id);
        $reviewResp = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Lab+Analysis");
        $reviewResp->assertStatus(200);
        $html = $reviewResp->getContent();

        $this->assertStringContainsString('data-system-exhibit-number="EXH-00001"', $html);
        $this->assertStringContainsString('data-system-exhibit-number="EXH-00002"', $html);
        $this->assertStringContainsString('value="A1"', $html);
        $this->assertStringContainsString('value="B1"', $html);

        // Update lab examination with a modified manual Exhibit No. and verify system exhibit_number is untouched
        $updateResp = $this->actingAs($officer)->postJson("/cases/{$case->id}/update-unit-progress", [
            'unit_name' => 'Lab Analysis',
            'unit_status' => 'COMPLETED',
            'exhibit_id' => $exhibits[0]->id,
            'exhibit_owner_name' => 'Kwesi Appiah',
            'exhibit_is_associate' => false,
            'lab_exhibit_no' => 'A1-REV',
            'laboratory_number' => 'LAB/2026/0099',
            'presumptive_result' => 'YES (Positive Marquis)',
            'confirmatory_test_result' => 'YES',
            'confirmatory_substance_identification' => 'Heroin Base',
        ]);
        $updateResp->assertStatus(200);

        $exhibits[0]->refresh();
        $this->assertEquals('EXH-00001', $exhibits[0]->exhibit_number);
        $this->assertEquals('A1-REV', $exhibits[0]->lab_exhibit_no);
    }

    #[Test]
    public function lab_analysis_create_page_renders_system_exhibit_id_badge_in_top_right_and_separate_lab_exhibit_no_input(): void
    {
        $officer = $this->createLabOfficer();

        $response = $this->actingAs($officer)->get('/Lab_Analysis/create');
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('lab-card-system-exhibit-id', $html);
        $this->assertStringContainsString('data-system-exhibit-number="${systemExhibitId}"', $html);
        $this->assertStringContainsString('name="lab_exhibit_nos[]"', $html);
        $this->assertStringContainsString('data-field="lab_exhibit_no"', $html);
    }
}
