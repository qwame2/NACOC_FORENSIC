<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TablePaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $unit = 'Crime Scene Support', bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => 'Test Officer ' . uniqid(),
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function master_cases_table_renders_pagination_links_and_preserves_query_string()
    {
        $officer = $this->createOfficer('Crime Scene Support');

        // Create 12 cases
        for ($i = 1; $i <= 12; $i++) {
            SingleSuspectCase::create([
                'case_number' => "CR-PAG-{$i}",
                'title' => "Case Title {$i}",
                'unit_name' => 'Crime Scene Support',
                'suspect_name' => "Suspect {$i}",
                'status' => 'Pending Review',
                'case_status' => 'Under Investigation',
                'priority' => 'MEDIUM',
                'user_id' => $officer->id,
            ]);
        }

        $response = $this->actingAs($officer)->get('/cases?page=1&per_page=5&filter=active');
        $response->assertStatus(200);

        // Pagination elements
        $response->assertSee('Showing');
        $response->assertSee('results');
        $response->assertSee('per_page=5', false);
        $response->assertSee('filter=active', false);
    }

    #[Test]
    public function forensic_head_cases_table_supports_pagination()
    {
        $head = $this->createOfficer('Forensic Head', true);

        for ($i = 1; $i <= 12; $i++) {
            SingleSuspectCase::create([
                'case_number' => "FH-PAG-{$i}",
                'title' => "FH Case {$i}",
                'unit_name' => 'Crime Scene Support',
                'suspect_name' => "Suspect {$i}",
                'status' => 'Pending Review',
                'case_status' => 'Under Investigation',
                'priority' => 'HIGH',
                'user_id' => $head->id,
            ]);
        }

        $response = $this->actingAs($head)->get('/Forensic_Head/cases?page=1&per_page=5');
        $response->assertStatus(200);
        $response->assertSee('Showing');
        $response->assertSee('per_page=5', false);
    }

    #[Test]
    public function forensic_head_approvals_table_supports_pagination()
    {
        $head = $this->createOfficer('Forensic Head', true);

        // Create 10 users needing approval
        for ($i = 1; $i <= 10; $i++) {
            User::factory()->create([
                'fullname' => "Candidate {$i}",
                'email' => "candidate{$i}@nacoc.gov.gh",
                'unit' => 'Lab Analysis',
                'is_approved' => false,
            ]);
        }

        $response = $this->actingAs($head)->get('/Forensic_Head/approvals?page=1&per_page=4');
        $response->assertStatus(200);
        $response->assertSee('Showing');
        $response->assertSee('per_page=4', false);
    }

    #[Test]
    public function digital_forensics_cases_table_supports_pagination()
    {
        $officer = $this->createOfficer('Digital & Physical Forensics');

        for ($i = 1; $i <= 8; $i++) {
            SingleSuspectCase::create([
                'case_number' => "DPF-PAG-{$i}",
                'title' => "DPF Case {$i}",
                'unit_name' => 'Digital & Physical Forensics',
                'suspect_name' => "Digital Suspect {$i}",
                'status' => 'Approved',
                'case_status' => 'Under Investigation',
                'priority' => 'HIGH',
                'user_id' => $officer->id,
            ]);
        }

        $response = $this->actingAs($officer)->get('/Digital_Forensics/cases?page=1&per_page=3');
        $response->assertStatus(200);
        $response->assertSee('Showing');
        $response->assertSee('per_page=3', false);
    }

    #[Test]
    public function drug_screening_table_supports_pagination_and_query_string()
    {
        $officer = $this->createOfficer('Lab Analysis');

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-SCR-' . uniqid(),
            'title' => 'Operation Screening Test',
            'unit_name' => 'Lab Analysis',
            'controller_user_id' => $officer->id,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            $suspect = CaseSuspect::create([
                'multiple_suspect_case_id' => $mCase->id,
                'suspect_number' => $i,
                'suspect_name' => "Donor Suspect {$i}",
                'role' => 'Suspect',
            ]);

            DrugScreening::create([
                'suspect_id' => $suspect->id,
                'purpose' => 'Court-Ordered Chemical Assessment',
                'screening_date' => now()->subDays($i)->format('Y-m-d'),
                'result' => 'Negative',
                'notes' => "Assay notes {$i}",
            ]);
        }

        $response = $this->actingAs($officer)->get('/Lab_Analysis/drug-screening?page=1&per_page=3&result=Negative');
        $response->assertStatus(200);
        $response->assertSee('Showing');
        $response->assertSee('per_page=3', false);
        $response->assertSee('result=Negative', false);
    }

    #[Test]
    public function history_table_supports_pagination()
    {
        $officer = $this->createOfficer('Crime Scene Support');

        for ($i = 1; $i <= 10; $i++) {
            SingleSuspectCase::create([
                'case_number' => "HIST-PAG-{$i}",
                'title' => "History Case {$i}",
                'unit_name' => 'Crime Scene Support',
                'suspect_name' => "Suspect {$i}",
                'status' => 'Completed',
                'case_status' => 'Closed',
                'priority' => 'LOW',
                'user_id' => $officer->id,
            ]);
        }

        $response = $this->actingAs($officer)->get('/history?unit=All+Units&page=1&per_page=5');
        $response->assertStatus(200);
        $response->assertSee('Showing');
        $response->assertSee('per_page=5', false);
    }

    #[Test]
    public function api_cases_supports_pagination_and_backward_compatibility()
    {
        $officer = $this->createOfficer('Crime Scene Support');

        for ($i = 1; $i <= 7; $i++) {
            SingleSuspectCase::create([
                'case_number' => "API-PAG-{$i}",
                'title' => "API Case {$i}",
                'unit_name' => 'Crime Scene Support',
                'suspect_name' => "Suspect {$i}",
                'status' => 'Pending Review',
                'case_status' => 'Under Investigation',
                'priority' => 'MEDIUM',
                'user_id' => $officer->id,
            ]);
        }

        // 1. Unpaginated request (backward compatibility) returns plain collection array
        $plainResponse = $this->actingAs($officer)->getJson('/api/cases');
        $plainResponse->assertStatus(200);
        $plainData = $plainResponse->json();
        $this->assertIsArray($plainData);
        $this->assertGreaterThanOrEqual(7, count($plainData));

        // 2. Paginated request returns LengthAwarePaginator JSON payload
        $paginatedResponse = $this->actingAs($officer)->getJson('/api/cases?page=1&per_page=3');
        $paginatedResponse->assertStatus(200);
        $paginatedResponse->assertJsonStructure([
            'current_page',
            'data',
            'per_page',
            'total',
        ]);
        $json = $paginatedResponse->json();
        $this->assertEquals(1, $json['current_page']);
        $this->assertEquals(3, $json['per_page']);
        $this->assertCount(3, $json['data']);
    }
}
