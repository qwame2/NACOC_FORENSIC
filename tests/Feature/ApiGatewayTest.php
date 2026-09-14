<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiGatewayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function gateway_validates_json_headers_and_response_format()
    {
        $response = $this->getJson('/api/check-user-approval?login_id=SD-101');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['exists']);
    }

    #[Test]
    public function gateway_blocks_unauthenticated_protected_api_requests()
    {
        $response = $this->getJson('/api/head-stats');

        $response->assertStatus(401);
    }

    #[Test]
    public function gateway_blocks_unauthorized_non_head_api_requests()
    {
        $staffUser = User::factory()->create([
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);

        $response = $this->actingAs($staffUser)->getJson('/api/head-stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function gateway_allows_authorized_head_officer_api_access()
    {
        $headUser = User::factory()->head()->create();

        $response = $this->actingAs($headUser)
            ->getJson('/api/head-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'pendingCasesCount',
                'pendingUsersCount',
                'activeUsersCount',
                'totalUsersCount',
                'totalAlerts'
            ]);
    }

    #[Test]
    public function gateway_serves_cases_json_list_for_authenticated_users()
    {
        $user = User::factory()->create(['is_approved' => true]);
        ForensicCase::create([
            'case_number' => 'CSS-2026-0001',
            'title' => 'Test Case File',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/cases');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonCount(1);
    }
}
