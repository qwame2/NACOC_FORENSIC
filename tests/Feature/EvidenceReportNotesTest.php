<?php

namespace Tests\Feature;

use App\Models\EvidenceReportNote;
use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceReportNotesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);
    }

    public function test_report_page_contains_notes_modal_and_trigger_buttons(): void
    {
        $response = $this->actingAs($this->user)->get('/Evidence_Management/report');
        $response->assertStatus(200);

        // Buttons trigger the notes modal
        $response->assertSee("openReportNotesModal('print')", false);
        $response->assertSee("openReportNotesModal('filtered')", false);

        // Modal container exists
        $response->assertSee('id="reportNotesModal"', false);
        $response->assertSee('Report Table Notes');

        // Required table note sections
        $response->assertSee('Physical Assets &amp; Non-Drug Seized Property Log', false);
        $response->assertSee('Seized Currency &amp; Money Received Register', false);
        $response->assertSee('id="modalNoteAssets"', false);
        $response->assertSee('id="modalNoteMoney"', false);

        // Add Note button for newly generated tables
        $response->assertSee('id="addNewTableNoteBtn"', false);
        $response->assertSee('Add Note');
        $response->assertSee('id="dynamicNotesContainer"', false);

        // Target Table automatically picks system-assigned table names
        $response->assertSee('window.systemAssignedTableNames', false);
        $response->assertSee('getNextSystemAssignedTableName', false);
        $response->assertSee('dyn-table-picked-badge', false);
        $response->assertSee('Precious Stones Exhibit Log');
        $response->assertSee('Weapons Exhibit Log');
        $response->assertSee('Ammunition Exhibit Log');
    }

    public function test_save_report_notes_api_persists_to_database(): void
    {
        $payload = [
            'batch_id' => 'ERN-TEST-12345',
            'period' => 'this_month',
            'from_date' => '2026-11-01',
            'to_date' => '2026-11-08',
            'notes' => [
                [
                    'table_key' => 'assets',
                    'table_title' => 'Physical Assets & Non-Drug Seized Property Log',
                    'note' => 'All vehicular assets inspected and garaged in secure bay 3.',
                ],
                [
                    'table_key' => 'money',
                    'table_title' => 'Seized Currency & Money Received Register',
                    'note' => 'Bank notes counted, verified, and sealed in central vault safe.',
                ],
                [
                    'table_key' => 'dyn_precious_stones',
                    'table_title' => 'Precious Stones Exhibit Log',
                    'note' => 'Rough diamonds assayed and secured under dual seal.',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/Evidence_Management/report/notes', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'batch_id' => 'ERN-TEST-12345',
            'count' => 3,
        ]);

        $this->assertDatabaseHas('evidence_report_notes', [
            'batch_id' => 'ERN-TEST-12345',
            'table_key' => 'assets',
            'note' => 'All vehicular assets inspected and garaged in secure bay 3.',
        ]);

        $this->assertDatabaseHas('evidence_report_notes', [
            'batch_id' => 'ERN-TEST-12345',
            'table_key' => 'money',
            'note' => 'Bank notes counted, verified, and sealed in central vault safe.',
        ]);

        $this->assertDatabaseHas('evidence_report_notes', [
            'batch_id' => 'ERN-TEST-12345',
            'table_key' => 'dyn_precious_stones',
            'note' => 'Rough diamonds assayed and secured under dual seal.',
        ]);
    }

    public function test_report_pdf_displays_saved_notes_beneath_tables(): void
    {
        $case = SingleSuspectCase::create([
            'case_number' => 'CASE-EMU-NOTES-01',
            'suspect_name' => 'Kojo Antwi',
            'title' => 'Seizure of Assets and Cash',
            'unit_name' => 'Evidence Management',
        ]);

        // 1. Asset exhibit
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-VEH-101',
            'commodity_name' => 'Toyota Land Cruiser V8',
            'exhibit_category' => 'Vehicles',
            'veh_make' => 'Toyota',
            'veh_reg_no' => 'GR-7788-26',
            'veh_vin' => 'JT111TESTVIN9988',
            'seizure_value' => 850000,
        ]);

        // 2. Money exhibit
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-CASH-202',
            'commodity_name' => 'Ghana Cedis Banknotes',
            'exhibit_category' => 'Money',
            'seizure_value' => 125000,
            'seizure_location' => 'Kotoka International Airport Cargo',
        ]);

        // 3. Precious stones exhibit
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EXH-STONE-303',
            'commodity_name' => 'Rough Diamond Stones',
            'exhibit_category' => 'Precious Stones',
            'seizure_value' => 340000,
            'seizure_weight' => '15 Carats',
        ]);

        $batchId = 'ERN-BATCH-NOTE-TEST';
        EvidenceReportNote::create([
            'user_id' => $this->user->id,
            'officer_name' => $this->user->name,
            'table_key' => 'assets',
            'table_title' => 'Physical Assets & Non-Drug Seized Property Log',
            'note' => 'TEST NOTE: Toyota Land Cruiser garaged under security seal A-19.',
            'batch_id' => $batchId,
        ]);

        EvidenceReportNote::create([
            'user_id' => $this->user->id,
            'officer_name' => $this->user->name,
            'table_key' => 'money',
            'table_title' => 'Seized Currency & Money Received Register',
            'note' => 'TEST NOTE: Cedis banknotes authenticated and deposited in Vault safe.',
            'batch_id' => $batchId,
        ]);

        EvidenceReportNote::create([
            'user_id' => $this->user->id,
            'officer_name' => $this->user->name,
            'table_key' => 'dyn_precious_stones',
            'table_title' => 'Precious Stones Exhibit Log',
            'note' => 'TEST NOTE: Diamond stones certified by Geological Survey Authority.',
            'batch_id' => $batchId,
        ]);

        $response = $this->actingAs($this->user)->get('/Evidence_Management/report_pdf?batch_id=' . $batchId);
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verify notes appear in the response
        $this->assertStringContainsString('TEST NOTE: Toyota Land Cruiser garaged under security seal A-19.', $content);
        $this->assertStringContainsString('Officer Footnote &bull; Physical Assets &amp; Non-Drug Seized Property Log:', $content);

        $this->assertStringContainsString('TEST NOTE: Cedis banknotes authenticated and deposited in Vault safe.', $content);
        $this->assertStringContainsString('Officer Footnote &bull; Seized Currency &amp; Money Received Register:', $content);

        $this->assertStringContainsString('TEST NOTE: Diamond stones certified by Geological Survey Authority.', $content);
        $this->assertStringContainsString('Officer Footnote &bull; Precious Stones Exhibit Log:', $content);
    }
}
