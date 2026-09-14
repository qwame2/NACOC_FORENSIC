<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function database_enforces_unique_service_id_constraint()
    {
        User::factory()->create(['service_id' => 'SD-777']);

        $this->expectException(QueryException::class);

        User::factory()->create(['service_id' => 'SD-777']);
    }

    #[Test]
    public function database_enforces_unique_username_constraint()
    {
        User::factory()->create(['username' => 'officer_mensah']);

        $this->expectException(QueryException::class);

        User::factory()->create(['username' => 'officer_mensah']);
    }

    #[Test]
    public function forensic_case_docket_data_json_column_stores_and_casts_array()
    {
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-JSON',
            'title' => 'Exhibit Test Case',
            'unit_name' => 'Lab Analysis',
            'docket_data' => [
                'lab_examination' => [
                    'analyst' => 'Dr. K. Appiah',
                    'presumptive_result' => 'YES'
                ]
            ]
        ]);

        $retrievedCase = ForensicCase::where('case_number', 'CSS-2026-JSON')->first();

        $this->assertIsArray($retrievedCase->docket_data);
        $this->assertEquals('Dr. K. Appiah', $retrievedCase->docket_data['lab_examination']['analyst']);
    }

    #[Test]
    public function forensic_case_belongs_to_user_relationship()
    {
        $officer = User::factory()->create();
        $case = ForensicCase::create([
            'case_number' => 'CSS-2026-REL01',
            'title' => 'Seizure Docket with User Relationship',
            'user_id' => $officer->id,
            'unit_name' => 'Crime Scene Support',
        ]);

        $this->assertInstanceOf(User::class, $case->user);
        $this->assertEquals($officer->id, $case->user->id);
    }
}
