<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemStressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function high_concurrency_api_approval_check_throughput_benchmark()
    {
        User::factory()->create(['service_id' => 'SD-999', 'is_approved' => true]);

        $startTime = microtime(true);
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->getJson('/api/check-user-approval?login_id=SD-999');
            $response->assertStatus(200);
        }

        $elapsedTime = microtime(true) - $startTime;
        $avgMsPerRequest = ($elapsedTime / $iterations) * 1000;

        // Assert average API execution time is below 50ms per request in testing environment
        $this->assertLessThan(50, $avgMsPerRequest, "Average request latency ({$avgMsPerRequest}ms) exceeded 50ms threshold.");
    }

    #[Test]
    public function bulk_database_case_docket_insertion_stress_test()
    {
        $startTime = microtime(true);
        $caseCount = 500;
        $casesData = [];

        for ($i = 1; $i <= $caseCount; $i++) {
            $casesData[] = [
                'case_number' => 'STRESS-' . sprintf('%04d', $i),
                'title' => 'Bulk Insert Stress Test Docket #' . $i,
                'unit_name' => 'Crime Scene Support',
                'status' => 'Pending Approval',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert 500 records in a single batch query
        ForensicCase::insert($casesData);

        $elapsedTime = microtime(true) - $startTime;

        $this->assertDatabaseCount('cases', $caseCount);
        $this->assertLessThan(2.0, $elapsedTime, "Bulk insert of 500 cases took too long: {$elapsedTime}s.");
    }

    #[Test]
    public function batch_approval_query_stress_test()
    {
        $headUser = User::factory()->head()->create();

        // Seed 100 pending cases
        $casesData = [];
        for ($i = 1; $i <= 100; $i++) {
            $casesData[] = [
                'case_number' => 'BATCH-' . sprintf('%04d', $i),
                'title' => 'Pending Approval Case #' . $i,
                'unit_name' => 'Forensic Directorate',
                'status' => 'Awaiting Sign-off',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ForensicCase::insert($casesData);

        $startTime = microtime(true);

        $response = $this->actingAs($headUser)
            ->post('/cases/batch-approve');

        $elapsedTime = microtime(true) - $startTime;

        $response->assertStatus(302);
        $this->assertDatabaseMissing('cases', ['status' => 'Awaiting Sign-off']);
        $this->assertLessThan(1.0, $elapsedTime, "Batch approval took {$elapsedTime}s.");
    }
}
