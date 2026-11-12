<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitHeadAutoVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Establish the initial Head of Forensic Directorate
        User::create([
            'fullname' => 'Director Kwame Mensah',
            'username' => 'director_mensah',
            'email' => 'director@nacoc.gov.gh',
            'service_id' => 'SD-100',
            'phone_number' => '0241000001',
            'is_head' => true,
            'is_staff' => false,
            'is_approved' => true,
            'unit' => 'Forensic Directorate',
            'password' => Hash::make('DirectorP@ss123'),
        ]);
    }

    #[Test]
    public function newly_registered_unit_head_is_automatically_approved_and_verified(): void
    {
        $response = $this->post('/register', [
            'fullname' => 'Unit Head John Doe',
            'username' => 'unithead_john',
            'service_prefix' => 'SD',
            'service_number' => '201',
            'phone_number' => '0241234567',
            'unit' => 'Crime Scene Support',
            'role' => 'head',
            'password' => 'SecureP@ssword123',
            'password_confirmation' => 'SecureP@ssword123',
            'terms' => 'on',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Registration successful! Please sign in with your Service ID or Username.');
        $response->assertSessionMissing('pending_login_id');

        $unitHead = User::where('username', 'unithead_john')->first();
        $this->assertNotNull($unitHead);
        $this->assertTrue((bool) $unitHead->is_head);
        $this->assertFalse((bool) $unitHead->is_staff);
        $this->assertTrue((bool) $unitHead->is_approved, 'Unit Head must be automatically approved without requiring Head of Forensics sign-off.');
    }

    #[Test]
    public function newly_registered_unit_head_can_log_in_immediately_without_approval(): void
    {
        // Register a unit head
        $this->post('/register', [
            'fullname' => 'Unit Head Jane Smith',
            'username' => 'unithead_jane',
            'service_prefix' => 'SD',
            'service_number' => '202',
            'phone_number' => '0241234568',
            'unit' => 'Evidence Management',
            'role' => 'head',
            'password' => 'SecureP@ssword123',
            'password_confirmation' => 'SecureP@ssword123',
            'terms' => 'on',
        ]);

        // Attempt login immediately
        $loginResponse = $this->post('/login', [
            'login_id' => 'SD-202',
            'password' => 'SecureP@ssword123',
        ]);

        // Must succeed and not be blocked by pending approval
        $loginResponse->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('unithead_jane', $user->username);
        $this->assertTrue((bool) $user->is_head);
        $this->assertTrue((bool) $user->is_approved);
    }

    #[Test]
    public function json_login_for_unit_head_succeeds_immediately(): void
    {
        $unitHead = User::create([
            'fullname' => 'Unit Head Lab Leader',
            'username' => 'unithead_lab',
            'service_id' => 'SD-203',
            'phone_number' => '0241234569',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_staff' => false,
            'is_approved' => true,
            'password' => Hash::make('SecureP@ssword123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login_id' => 'SD-203',
            'password' => 'SecureP@ssword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user' => [
                'username' => 'unithead_lab',
                'is_head' => true,
                'is_approved' => true,
            ],
        ]);
    }

    #[Test]
    public function explicitly_deactivated_account_is_blocked_with_deactivated_message(): void
    {
        $deactivatedHead = User::create([
            'fullname' => 'Suspended Unit Head',
            'username' => 'suspended_head',
            'service_id' => 'SD-299',
            'phone_number' => '0241999999',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_staff' => false,
            'is_approved' => false, // Deactivated
            'password' => Hash::make('SecureP@ssword123'),
        ]);

        $response = $this->post('/login', [
            'login_id' => 'SD-299',
            'password' => 'SecureP@ssword123',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();

        // JSON check
        $jsonResponse = $this->postJson('/api/login', [
            'login_id' => 'SD-299',
            'password' => 'SecureP@ssword123',
        ]);
        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJson([
            'success' => false,
            'message' => 'Account Deactivated: Your account access has been suspended. Please contact the administrator.',
        ]);
    }
}

