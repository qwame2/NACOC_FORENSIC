<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlackBoxWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function black_box_registration_fails_with_invalid_service_id_format()
    {
        $invalidRegistrationData = [
            'fullname' => 'John Officer',
            'username' => 'johnofficer',
            'service_prefix' => 'INVALID',
            'service_number' => 'ABC',
            'phone_number' => '123', // invalid phone length
            'unit' => 'Lab Analysis',
            'password' => 'Passcode123!',
            'password_confirmation' => 'Passcode123!',
            'terms' => 'on',
        ];

        $response = $this->post('/register', $invalidRegistrationData);

        $response->assertSessionHasErrors(['service_id', 'phone_number']);
        $this->assertDatabaseMissing('users', ['username' => 'johnofficer']);
    }

    #[Test]
    public function black_box_registration_fails_when_password_contains_username()
    {
        $invalidRegistrationData = [
            'fullname' => 'John Officer',
            'username' => 'johndoe',
            'service_prefix' => 'SD',
            'service_number' => '999',
            'phone_number' => '0200000000',
            'unit' => 'Lab Analysis',
            'password' => 'johndoe123',
            'password_confirmation' => 'johndoe123',
            'terms' => 'on',
        ];

        $response = $this->post('/register', $invalidRegistrationData);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'johndoe']);
    }

    #[Test]
    public function black_box_valid_registration_creates_user_record()
    {
        // First seed a Forensic Head so standard unit registration is permitted
        User::factory()->head()->create();

        $validRegistrationData = [
            'fullname' => 'Inspector Kwame Mensah',
            'username' => 'kwamemensah',
            'service_prefix' => 'SD',
            'service_number' => '888',
            'service_id' => 'SD-888',
            'phone_number' => '0244123456',
            'unit' => 'Lab Analysis',
            'is_staff' => 'on',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $validRegistrationData);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('staff_accounts', [
            'username' => 'kwamemensah',
            'service_id' => 'SD-888',
        ]);
    }
}
