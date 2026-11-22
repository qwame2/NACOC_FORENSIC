<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DirectorGeneralRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_general_is_in_dropdown_when_not_registered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Director General');
        $response->assertSee('SUPREME COMMAND');
    }

    public function test_director_general_can_register_via_dropdown_selection(): void
    {
        $response = $this->post('/register', [
            'fullname' => 'Director General Justice Samuel Addo',
            'username' => 'directorgeneral',
            'service_prefix' => 'SD',
            'service_number' => '001',
            'phone_number' => '0240000001',
            'unit' => 'Director General',
            'role' => 'head',
            'password' => 'KwameSecPass2026!',
            'password_confirmation' => 'KwameSecPass2026!',
            'terms' => '1',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'username' => 'directorgeneral',
            'unit' => 'Office of the Director-General',
            'is_head' => 1,
            'is_staff' => 0,
            'is_approved' => 1,
        ]);
    }

    public function test_director_general_is_removed_from_dropdown_after_registered(): void
    {
        // Create Director General on the system
        User::factory()->create([
            'username' => 'existingdg',
            'service_id' => 'SD-001',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertDontSee("selectUnitOption('Director General')", false);
        $response->assertDontSee("selectUnitOption('Office of the Director-General')", false);
    }

    public function test_cannot_register_as_director_general_if_already_on_system(): void
    {
        User::factory()->create([
            'username' => 'existingdg',
            'service_id' => 'SD-001',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'fullname' => 'Imposter DG',
            'username' => 'imposterdg',
            'service_prefix' => 'SD',
            'service_number' => '002',
            'phone_number' => '0240000002',
            'unit' => 'Director General',
            'role' => 'head',
            'password' => 'KwameSecPass2026!',
            'password_confirmation' => 'KwameSecPass2026!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['unit']);
        $this->assertDatabaseMissing('users', [
            'username' => 'imposterdg',
        ]);
    }

    public function test_director_general_logs_in_and_redirects_to_dg_page(): void
    {
        $dg = User::factory()->create([
            'username' => 'supremecmd',
            'password' => Hash::make('KwameSecPass2026!'),
            'service_id' => 'SD-001',
            'unit' => 'Office of the Director-General',
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => false,
        ]);

        $response = $this->post('/login', [
            'login_id' => 'supremecmd',
            'password' => 'KwameSecPass2026!',
        ]);

        $response->assertRedirect('/DG');
    }
}
