<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreciousStonesReportPdfColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_precious_stones_exhibit_log_table_does_not_contain_source_of_exhibits_column(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'EMU-CASE-2026-9001',
            'title' => 'State vs Gold Cartel',
            'suspect_name' => 'Kofi Stone',
            'unit_name' => 'Evidence Management',
            'created_by' => $officer->id,
            'status' => 'Pending Approval',
        ]);

        // Create a Precious Stones exhibit
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-STONE-001',
            'exhibit_category' => 'Precious Stones',
            'commodity_name' => 'Rough Diamonds 24 Carats',
            'seizure_quantity' => '12',
            'seizure_packaging' => 'Velvet Pouch',
            'seizure_weight' => '24',
            'seizure_unit' => 'carats',
            'source_of_exhibits' => 'Airport Customs Vault',
            'seizure_location' => 'KIA Terminal 3',
            'date_seized' => now()->toDateString(),
        ]);

        // Create a Drug exhibit to verify drug table retains Source of Exhibits
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number' => 'EX-DRUG-001',
            'exhibit_category' => 'drugs',
            'commodity_name' => 'Cocaine Powder',
            'seizure_quantity' => '1',
            'seizure_packaging' => 'Slab',
            'seizure_weight' => '1.5',
            'seizure_unit' => 'kg',
            'source_of_exhibits' => 'Border Checkpoint',
            'seizure_location' => 'Aflao Border',
            'date_seized' => now()->toDateString(),
        ]);

        $response = $this->actingAs($officer)->get('/Evidence_Management/report_pdf');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Ensure both tables exist
        $this->assertStringContainsString('Precious Stones Exhibit Log', $content);
        $this->assertStringContainsString('Drug exhibit Log', $content);

        // Drug table must retain "Source of Exhibits"
        $this->assertStringContainsString('Source of Exhibits', $content);

        // Extract Precious Stones section HTML
        $pos = strpos($content, 'Precious Stones Exhibit Log');
        $preciousStonesHtml = substr($content, $pos, 4000);

        // Assert commodity is present in overall response
        $this->assertStringContainsString('Rough Diamonds 24 Carats', $content);

        // Find table inside Precious Stones section
        $preciousStonesHtml = substr($content, $pos);
        $tableStart = strpos($preciousStonesHtml, '<table');
        $tableEnd = strpos($preciousStonesHtml, '</table>', $tableStart);
        $tableHtml = substr($preciousStonesHtml, $tableStart, ($tableEnd - $tableStart) + 8);

        // Assert Source of Exhibits header & value are NOT in Precious Stones table
        $this->assertStringNotContainsString('Source of Exhibits', $tableHtml);
        $this->assertStringNotContainsString('Airport Customs Vault', $tableHtml);

        // Assert other columns are properly present in the Precious Stones table
        $this->assertStringContainsString('Date Of Seizure', $tableHtml);
        $this->assertStringContainsString('Suspect(s)', $tableHtml);
        $this->assertStringContainsString('Item / Commodity', $tableHtml);
        $this->assertStringContainsString('Quantity', $tableHtml);
        $this->assertStringContainsString('Weight / Spec', $tableHtml);
        $this->assertStringContainsString('Remarks / Details', $tableHtml);
        $this->assertStringContainsString('Rough Diamonds 24 Carats', $tableHtml);
    }
}
