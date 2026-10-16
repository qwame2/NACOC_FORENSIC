<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unauthenticated_user_cannot_access_profile_settings()
    {
        $response = $this->get('/profile/settings');
        $response->assertRedirect('/login');

        $responseAlt = $this->get('/profile');
        $responseAlt->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_officer_can_view_profile_settings_page()
    {
        $officer = User::factory()->create([
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu',
            'service_id' => 'NACOC-CS-044',
            'unit' => 'Crime Scene Support',
            'phone_number' => '0244123456',
            'email' => 'kmanu@nacoc.gov.gh',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $response = $this->actingAs($officer)->get('/profile/settings');

        $response->assertStatus(200);
        $response->assertSee('Inspector Kwesi Manu');
        $response->assertSee('NACOC-CS-044');
        $response->assertSee('Crime Scene Support');
        $response->assertSee('kmanu');
        $response->assertSee('0244123456');
        $response->assertSee('Profile Settings');
    }

    #[Test]
    public function authenticated_forensic_head_can_view_profile_settings_with_head_sidebar()
    {
        $head = User::factory()->create([
            'fullname' => 'Anita Addo',
            'username' => 'anna',
            'service_id' => 'SVC-001',
            'unit' => 'Forensic Directorate',
            'phone_number' => '0200000001',
            'email' => 'anna@nacoc.gov.gh',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $response = $this->actingAs($head)->get('/profile/settings');

        $response->assertStatus(200);
        $response->assertSee('Anita Addo');
        $response->assertSee('DEPT HEAD PORTAL');
        $response->assertSee('Profile Settings');
    }

    #[Test]
    public function officer_can_update_profile_biodata()
    {
        $officer = User::factory()->create([
            'fullname' => 'Eric Mensah',
            'username' => 'ericm',
            'phone_number' => '0241112233',
            'email' => 'eric.old@nacoc.gov.gh',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->put('/profile/settings', [
            'fullname' => 'Supt. Eric Mensah',
            'username' => 'emensah_lead',
            'phone_number' => '0249998877',
            'email' => 'eric.mensah@nacoc.gov.gh',
        ]);

        $response->assertSessionHas('status', 'Officer profile information successfully updated.');

        $officer->refresh();
        $this->assertEquals('Supt. Eric Mensah', $officer->fullname);
        $this->assertEquals('emensah_lead', $officer->username);
        $this->assertEquals('0249998877', $officer->phone_number);
        $this->assertEquals('eric.mensah@nacoc.gov.gh', $officer->email);
    }

    #[Test]
    public function officer_can_update_avatar_via_file_upload()
    {
        $officer = User::factory()->create([
            'fullname' => 'Samuel Atta',
            'username' => 'samatta',
            'phone_number' => '0201234567',
            'is_approved' => true,
            'avatar' => null,
        ]);

        $fakeImage = UploadedFile::fake()->create('portrait.jpg', 50, 'image/jpeg');

        $response = $this->actingAs($officer)->put('/profile/settings', [
            'fullname' => 'Samuel Atta',
            'username' => 'samatta',
            'phone_number' => '0201234567',
            'avatar_file' => $fakeImage,
        ]);

        $officer->refresh();
        $this->assertNotNull($officer->avatar);
        $this->assertStringStartsWith('data:image', $officer->avatar);
    }

    #[Test]
    public function officer_can_remove_avatar()
    {
        $officer = User::factory()->create([
            'is_approved' => true,
            'avatar' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $response = $this->actingAs($officer)->post('/profile/avatar/remove');

        $response->assertSessionHas('status', 'Profile portrait cleared. Default initials badge restored.');

        $officer->refresh();
        $this->assertNull($officer->avatar);
    }

    #[Test]
    public function officer_can_update_password_with_correct_current_password()
    {
        $officer = User::factory()->create([
            'fullname' => 'John Adu',
            'username' => 'jadu',
            'password' => Hash::make('CurrentSecret99#'),
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->put('/profile/password', [
            'current_password' => 'CurrentSecret99#',
            'password' => 'SafeNewPassword88!',
            'password_confirmation' => 'SafeNewPassword88!',
        ]);

        $response->assertSessionHas('status', 'Password credentials successfully changed.');

        $officer->refresh();
        $this->assertTrue(Hash::check('SafeNewPassword88!', $officer->password));
    }

    #[Test]
    public function password_update_fails_if_password_contains_username()
    {
        $officer = User::factory()->create([
            'fullname' => 'John Adu',
            'username' => 'jadu',
            'password' => Hash::make('CurrentSecret99#'),
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->put('/profile/password', [
            'current_password' => 'CurrentSecret99#',
            'password' => 'Secret_jadu_Pass123',
            'password_confirmation' => 'Secret_jadu_Pass123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    #[Test]
    public function password_update_fails_if_current_password_is_wrong()
    {
        $officer = User::factory()->create([
            'password' => Hash::make('CorrectPassword123!'),
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)->put('/profile/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'AnotherNewPass123$',
            'password_confirmation' => 'AnotherNewPass123$',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    #[Test]
    public function officer_can_upload_signature_image_file()
    {
        $officer = User::factory()->create([
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_sig',
            'phone_number' => '0244123456',
            'is_approved' => true,
            'signature' => null,
        ]);

        $fakeSignature = UploadedFile::fake()->create('signature.png', 40, 'image/png');

        $response = $this->actingAs($officer)->put('/profile/settings', [
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_sig',
            'phone_number' => '0244123456',
            'signature_file' => $fakeSignature,
        ]);

        $response->assertSessionHas('status', 'Officer profile information successfully updated.');

        $officer->refresh();
        $this->assertNotNull($officer->signature);
        $this->assertStringStartsWith('data:image', $officer->signature);
    }

    #[Test]
    public function officer_can_submit_drawn_signature_data_uri()
    {
        $officer = User::factory()->create([
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_draw',
            'phone_number' => '0244123456',
            'is_approved' => true,
            'signature' => null,
        ]);

        $signatureData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($officer)->put('/profile/settings', [
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_draw',
            'phone_number' => '0244123456',
            'signature_data' => $signatureData,
        ]);

        $response->assertSessionHas('status', 'Officer profile information successfully updated.');

        $officer->refresh();
        $this->assertEquals($signatureData, $officer->signature);
    }

    #[Test]
    public function officer_can_remove_signature()
    {
        $officer = User::factory()->create([
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_clear',
            'phone_number' => '0244123456',
            'is_approved' => true,
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $response = $this->actingAs($officer)->post('/profile/signature/remove');

        $response->assertSessionHas('status', 'Official digital signature removed successfully.');

        $officer->refresh();
        $this->assertNull($officer->signature);
    }

    #[Test]
    public function signature_upload_fails_if_file_is_not_an_image()
    {
        $officer = User::factory()->create([
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_pdf',
            'phone_number' => '0244123456',
            'is_approved' => true,
        ]);

        $fakeDoc = UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf');

        $response = $this->actingAs($officer)->put('/profile/settings', [
            'fullname' => 'Inspector Kwesi Manu',
            'username' => 'kmanu_pdf',
            'phone_number' => '0244123456',
            'signature_file' => $fakeDoc,
        ]);

        $response->assertSessionHasErrors(['signature_file']);
    }

    #[Test]
    public function flash_message_is_displayed_as_toast_at_top_right_corner_of_screen()
    {
        $officer = User::factory()->create([
            'is_approved' => true,
        ]);

        $response = $this->actingAs($officer)
            ->withSession(['status' => 'Officer profile information successfully updated.'])
            ->get('/profile/settings');

        $response->assertStatus(200);
        $response->assertSee('id="topRightFlashContainer"', false);
        $response->assertSee('id="topRightFlashToast"', false);
        $response->assertSee('top-5 right-5', false);
        $response->assertSee('Changes Saved Successfully');
        $response->assertSee('Officer profile information successfully updated.');
    }
}

