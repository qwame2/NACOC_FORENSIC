<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_routes_are_accessible_unauthenticated()
    {
        $this->get('/')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
        $this->get('/api/check-user-approval?login_id=SD-101')->assertStatus(200);
    }

    #[Test]
    public function protected_case_directory_redirects_unauthenticated_users()
    {
        $response = $this->get('/cases');
        $response->assertRedirect('/');
    }

    #[Test]
    public function protected_examination_log_redirects_unauthenticated_users()
    {
        $response = $this->get('/Lab_Analysis/examination');
        $response->assertRedirect('/');
    }

    #[Test]
    public function authenticated_officer_can_access_case_directory()
    {
        $user = User::factory()->create([
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);

        $response = $this->actingAs($user)->get('/cases');
        $response->assertStatus(200);
    }

    #[Test]
    public function non_head_officer_cannot_access_directorate_command_dashboard()
    {
        $staffUser = User::factory()->create([
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);

        $response = $this->actingAs($staffUser)->get('/Forensic_Head');
        $response->assertRedirect('/cases');
    }

    #[Test]
    public function forensic_head_can_access_command_dashboard()
    {
        $headUser = User::factory()->head()->create();

        $response = $this->actingAs($headUser)->get('/Forensic_Head');
        $response->assertStatus(200);
    }
}
