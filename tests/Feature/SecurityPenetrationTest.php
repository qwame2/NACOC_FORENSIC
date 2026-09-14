<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityPenetrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sql_injection_attempt_in_login_id_is_safely_parameterized()
    {
        // Attackers typical SQL injection bypass attempt
        $sqlPayload = "' OR '1'='1' --";

        $response = $this->post('/login', [
            'login_id' => $sqlPayload,
            'password' => 'arbitraryPasscode123',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();
    }

    #[Test]
    public function sql_injection_attempt_in_approval_api_query_fails_safely()
    {
        $sqlPayload = "SD-101' UNION SELECT 1, 'admin', 'password' --";

        $response = $this->getJson('/api/check-user-approval?login_id=' . urlencode($sqlPayload));

        $response->assertStatus(200)
            ->assertJson(['exists' => false]);
    }

    #[Test]
    public function xss_payload_in_case_title_is_safely_escaped_in_blade()
    {
        $officer = User::factory()->create(['is_approved' => true]);
        $xssPayload = "<script>alert('MALICIOUS_XSS')</script>";

        ForensicCase::create([
            'case_number' => 'CSS-2026-XSS01',
            'title' => $xssPayload,
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($officer)->get('/cases');

        // Asserts that raw HTML/JS tags are escaped into &lt;script&gt; in Blade output
        $response->assertDontSee("<script>alert('MALICIOUS_XSS')</script>", false);
        $response->assertSee("&lt;script&gt;", false);
    }

    #[Test]
    public function unapproved_user_account_cannot_bypass_login()
    {
        $unapprovedUser = User::factory()->unapproved()->create([
            'username' => 'unapproved_officer',
            'password' => bcrypt('secret123'),
            'is_head' => true,
        ]);

        $response = $this->post('/login', [
            'login_id' => 'unapproved_officer',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();
    }

    #[Test]
    public function user_session_is_invalidated_upon_logout()
    {
        $user = User::factory()->create(['is_approved' => true]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
