<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseRegistryPriorityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case_registry_table_displays_actual_database_priorities()
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        // 1. Single suspect case with HIGH priority
        $singleHigh = SingleSuspectCase::create([
            'case_number' => 'TEST-PRIORITY-001',
            'title' => 'High Priority Operation',
            'suspect_name' => 'Suspect One',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
        ]);

        // 2. Single suspect case with CRITICAL priority
        $singleCritical = SingleSuspectCase::create([
            'case_number' => 'TEST-PRIORITY-002',
            'title' => 'Critical Priority Operation',
            'suspect_name' => 'Suspect Two',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'CRITICAL',
            'user_id' => $officer->id,
        ]);

        // 3. Multiple suspect case with LOW priority
        $multiLow = MultipleSuspectCase::create([
            'case_number' => 'TEST-PRIORITY-003',
            'title' => 'Low Priority Joint Operation',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'LOW',
            'user_id' => $officer->id,
            'total_suspects_count' => 1,
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiLow->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Suspect Three',
        ]);

        // Request Case Registry & Directory
        $response = $this->actingAs($officer)->get('/cases');

        $response->assertStatus(200);

        // Verify that database priorities are displayed and NOT replaced by NORMAL
        $response->assertSee('HIGH');
        $response->assertSee('CRITICAL');
        $response->assertSee('LOW');

        // Check badge classes match defined priority styling
        $response->assertSee('bg-amber-100 text-amber-800 border-amber-200'); // HIGH
        $response->assertSee('bg-red-100 text-red-700 border-red-200');       // CRITICAL
        $response->assertSee('bg-slate-100 text-slate-700 border-slate-300'); // LOW
    }

    #[Test]
    public function promoted_or_mirrored_cases_preserve_single_case_priority()
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $single = SingleSuspectCase::create([
            'case_number' => 'TEST-SYNC-001',
            'title' => 'Single High Operation',
            'suspect_name' => 'Primary Suspect',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
        ]);

        // Mimic legacy multi mirror created with NORMAL
        $multi = MultipleSuspectCase::create([
            'case_number' => 'TEST-SYNC-001',
            'title' => 'Single High Operation',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'NORMAL',
            'user_id' => $officer->id,
            'total_suspects_count' => 1,
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multi->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Primary Suspect',
        ]);

        $wrapped = ForensicCase::wrapRecord($multi);
        $this->assertEquals('HIGH', $wrapped->priority);
    }
}
