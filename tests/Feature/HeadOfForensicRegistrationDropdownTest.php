<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadOfForensicRegistrationDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_of_forensic_is_in_dropdown_when_not_registered()
    {
        // No head of forensic in database
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Head of Forensic');
    }

    public function test_head_of_forensic_can_register_via_dropdown_selection()
    {
        $response = $this->post('/register', [
            'fullname' => 'Director General',
            'username' => 'directorhof',
            'service_prefix' => 'SD',
            'service_number' => '101',
            'phone_number' => '0240000101',
            'unit' => 'Head of Forensic',
            'role' => 'head',
            'password' => 'KwameSecPass2026!',
            'password_confirmation' => 'KwameSecPass2026!',
            'terms' => '1',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'username' => 'directorhof',
            'unit' => 'Forensic Directorate',
            'is_head' => 1,
        ]);
    }

    public function test_head_of_forensic_is_removed_from_dropdown_when_on_the_system()
    {
        // Create Head of Forensic on the system
        User::factory()->create([
            'username' => 'existinghead',
            'service_id' => 'SD-999',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertDontSee('selectUnitOption(\'Head of Forensic\')', false);
        $response->assertDontSee('selectUnitOption(\'Forensic Directorate\')', false);
    }

    public function test_cannot_register_as_head_of_forensic_if_already_on_system()
    {
        User::factory()->create([
            'username' => 'existinghead',
            'service_id' => 'SD-999',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'fullname' => 'Imposter Head',
            'username' => 'imposterhof',
            'service_prefix' => 'SD',
            'service_number' => '102',
            'phone_number' => '0240000102',
            'unit' => 'Head of Forensic',
            'role' => 'head',
            'password' => 'KwameSecPass2026!',
            'password_confirmation' => 'KwameSecPass2026!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['unit']);
        $this->assertDatabaseMissing('users', [
            'username' => 'imposterhof',
        ]);
    }
}
