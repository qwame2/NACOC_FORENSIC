<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldOfficersPageAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function field_officers_routes_restrict_unit_heads_to_their_own_unit_and_allow_directorate_all_units(): void
    {
        $directorateHead = User::factory()->head()->create([
            'fullname' => 'Director HOF Only',
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        $cssHead = User::factory()->head()->create([
            'fullname' => 'CSS Head Commander',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $emHead = User::factory()->head()->create([
            'fullname' => 'EM Head Commander',
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $labHead = User::factory()->head()->create([
            'fullname' => 'LAU Head Commander',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $dpfHead = User::factory()->head()->create([
            'fullname' => 'DPF Head Commander',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $cssStaff = User::factory()->create([
            'fullname' => 'CSS Field Staff One',
            'service_id' => 'SD-101',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        $emStaff = User::factory()->create([
            'fullname' => 'EM Vault Staff Two',
            'service_id' => 'SD-102',
            'unit' => 'Evidence Management',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => false,
        ]);

        $labStaff = User::factory()->create([
            'fullname' => 'LAU Chemist Staff Three',
            'service_id' => 'SD-103',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        $dpfStaff = User::factory()->create([
            'fullname' => 'DPF Cyber Staff Four',
            'service_id' => 'SD-104',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => false,
        ]);

        $routes = [
            '/Crime_Scene_Support/field-officers',
            '/Lab_Analysis/field-officers',
            '/Evidence_Management/field-officers',
            '/Digital_Forensics/field-officers',
            '/field-officers',
        ];

        // Each operational Unit Head only sees their own unit's staff across all routes and tampered unit query params
        $headMatrix = [
            [$cssHead, 'CSS Field Staff One', ['EM Vault Staff Two', 'LAU Chemist Staff Three', 'DPF Cyber Staff Four'], 'lab'],
            [$emHead,  'EM Vault Staff Two',  ['CSS Field Staff One', 'LAU Chemist Staff Three', 'DPF Cyber Staff Four'], 'css'],
            [$labHead, 'LAU Chemist Staff Three', ['CSS Field Staff One', 'EM Vault Staff Two', 'DPF Cyber Staff Four'], 'dpf'],
            [$dpfHead, 'DPF Cyber Staff Four', ['CSS Field Staff One', 'EM Vault Staff Two', 'LAU Chemist Staff Three'], 'em'],
        ];

        foreach ($headMatrix as [$head, $expectedOwnStaff, $forbiddenOtherStaff, $tamperUnit]) {
            foreach ($routes as $uri) {
                $resp = $this->actingAs($head)->get($uri . '?unit=' . $tamperUnit);
                $resp->assertStatus(200);
                $resp->assertSee($expectedOwnStaff);
                foreach ($forbiddenOtherStaff as $otherName) {
                    $resp->assertDontSee($otherName);
                }
                $resp->assertDontSee('Director HOF Only');

                $tableHtml = $this->actingAs($head)
                    ->get($uri . '?unit=all', ['X-Requested-With' => 'XMLHttpRequest'])
                    ->assertStatus(200)
                    ->getContent();
                $this->assertStringContainsString($expectedOwnStaff, $tableHtml);
                $this->assertStringNotContainsString($head->fullname, $tableHtml);
                foreach ($forbiddenOtherStaff as $otherName) {
                    $this->assertStringNotContainsString($otherName, $tableHtml);
                }
            }
        }

        // Directorate Head retains multi-unit staff visibility across all 4 operational units
        $dirResp = $this->actingAs($directorateHead)->get('/field-officers');
        $dirResp->assertStatus(200);
        $dirResp->assertSee('CSS Field Staff One');
        $dirResp->assertSee('EM Vault Staff Two');
        $dirResp->assertSee('LAU Chemist Staff Three');
        $dirResp->assertSee('DPF Cyber Staff Four');

        $dirTableHtml = $this->actingAs($directorateHead)
            ->get('/field-officers', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(200)
            ->getContent();
        $this->assertStringNotContainsString('Director HOF Only', $dirTableHtml);
        $this->assertStringNotContainsString('CSS Head Commander', $dirTableHtml);

        // Verify skeleton loader, AJAX filtering hooks, and 10s interval polling
        $mainResp = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers');
        $mainResp->assertSee('/Lab_Analysis/field-officers', false);
        $mainResp->assertSee('initAjaxTable', false);
        $mainResp->assertSee('renderFieldOfficersSkeletonRows', false);
        $mainResp->assertSee('setInterval(pollFieldOfficers, 10000)', false);
        $mainResp->assertSee('data-table-filter="#fieldOfficersTableContainer"', false);
    }

    #[Test]
    public function field_officers_supports_ajax_filtering_polling_and_pagination(): void
    {
        $directorateHead = User::factory()->head()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        $labHead = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        User::factory()->create([
            'fullname' => 'Approved Chemist Akua',
            'service_id' => 'SD-201',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        User::factory()->create([
            'fullname' => 'Pending Chemist Kojo',
            'service_id' => 'SD-202',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => false,
        ]);

        User::factory()->create([
            'fullname' => 'Vault Officer Kwabena',
            'service_id' => 'SD-203',
            'unit' => 'Evidence Management',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        // Unit Head sees only their own unit even if passing another unit filter or ?unit=all
        $labAjax = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers?unit=em', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $labAjax->assertStatus(200);
        $labAjax->assertSee('Approved Chemist Akua');
        $labAjax->assertSee('Pending Chemist Kojo');
        $labAjax->assertDontSee('Vault Officer Kwabena');

        // Status filter via AJAX
        $pendingAjax = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers?status=pending', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $pendingAjax->assertStatus(200);
        $pendingAjax->assertSee('Pending Chemist Kojo');
        $pendingAjax->assertDontSee('Approved Chemist Akua');
        $pendingAjax->assertDontSee('Vault Officer Kwabena');

        // Polling endpoint returns unit-scoped KPI stats + rendered table HTML (excludes other units' counts)
        $pollResp = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers?poll=1&search=SD-202', [
            'Accept' => 'application/json',
        ]);
        $pollResp->assertStatus(200)
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.approved', 1)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('pagination.total', 1);

        $json = $pollResp->json();
        $this->assertStringContainsString('Pending Chemist Kojo', $json['html']);
        $this->assertStringNotContainsString('Vault Officer Kwabena', $json['html']);

        // Searching directly for another unit's service ID yields zero matches for Lab Head
        $crossSearchResp = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers?poll=1&search=SD-203', [
            'Accept' => 'application/json',
        ]);
        $crossSearchResp->assertStatus(200)
            ->assertJsonPath('pagination.total', 0);
        $this->assertStringNotContainsString('Vault Officer Kwabena', $crossSearchResp->json('html'));

        // Pagination check (Directorate across multiple units)
        User::factory()->count(4)->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        $page2 = $this->actingAs($directorateHead)->get('/field-officers?per_page=3&page=2');
        $page2->assertStatus(200);
        $page2->assertSee('per_page=3', false);
    }

    #[Test]
    public function staff_registration_requires_unit_head_approval_before_login(): void
    {
        // Seed Directorate Head & two Unit Heads
        User::factory()->head()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        $labHead = User::factory()->head()->create([
            'fullname' => 'Head of Lab Analysis',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $cssHead = User::factory()->head()->create([
            'fullname' => 'Head of CSS',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        // 1. New Staff Officer registers in Lab Analysis
        $registerResp = $this->post('/register', [
            'fullname' => 'Staff Chemist Abena',
            'username' => 'abena.chemist',
            'service_prefix' => 'SD',
            'service_number' => '777',
            'service_id' => 'SD-777',
            'phone_number' => '0244777777',
            'unit' => 'Lab Analysis',
            'role' => 'staff',
            'is_staff' => 'on',
            'password' => 'StrongPass2026!',
            'password_confirmation' => 'StrongPass2026!',
            'terms' => '1',
        ]);
        $registerResp->assertRedirect('/login');

        $staffUser = User::where('service_id', 'SD-777')->firstOrFail();
        $this->assertFalse((bool) $staffUser->is_approved);
        $this->assertTrue((bool) $staffUser->is_staff);
        $this->assertFalse((bool) $staffUser->is_head);

        // 2. Unapproved staff officer CANNOT log in yet
        $blockedLogin = $this->postJson('/api/login', [
            'login_id' => 'SD-777',
            'password' => 'StrongPass2026!',
        ]);
        $blockedLogin->assertStatus(403)
            ->assertJsonPath('success', false);
        $this->assertGuest();

        // 3. Another staff officer or Head of a DIFFERENT unit CANNOT approve Lab staff
        $otherStaff = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);
        $this->actingAs($otherStaff)
            ->postJson("/field-officers/{$staffUser->id}/approve")
            ->assertStatus(403);

        $this->actingAs($cssHead)
            ->postJson("/field-officers/{$staffUser->id}/approve")
            ->assertStatus(403);

        $this->assertFalse((bool) $staffUser->fresh()->is_approved);

        // 4. Head of Lab Analysis approves the Lab Analysis staff officer
        $this->actingAs($labHead)
            ->postJson("/field-officers/{$staffUser->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.is_approved', true);

        $this->assertTrue((bool) $staffUser->fresh()->is_approved);

        // 5. Staff officer can now log in successfully
        $allowedLogin = $this->postJson('/api/login', [
            'login_id' => 'SD-777',
            'password' => 'StrongPass2026!',
        ]);
        $allowedLogin->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.is_approved', true);

        // 6. Head of Lab Analysis can revoke access and block login again
        $this->actingAs($labHead)
            ->postJson("/field-officers/{$staffUser->id}/revoke")
            ->assertStatus(200)
            ->assertJsonPath('user.is_approved', false);

        auth()->logout();

        $this->postJson('/api/login', [
            'login_id' => 'SD-777',
            'password' => 'StrongPass2026!',
        ])->assertStatus(403);
    }

    #[Test]
    public function unit_sidebar_renders_field_officers_link_for_every_unit_context(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $this->actingAs($officer)
            ->get('/Lab_Analysis/create')
            ->assertStatus(200)
            ->assertSee('/Lab_Analysis/field-officers', false)
            ->assertSee('Field Officers');

        $this->actingAs($officer)
            ->get('/Evidence_Management/create')
            ->assertStatus(200)
            ->assertSee('/Evidence_Management/field-officers', false);

        $this->actingAs($officer)
            ->get('/Digital_Forensics/create')
            ->assertStatus(200)
            ->assertSee('/Digital_Forensics/field-officers', false);

        $this->actingAs($officer)
            ->get('/Crime_Scene_Support/create')
            ->assertStatus(200)
            ->assertSee('/Crime_Scene_Support/field-officers', false);
    }

    #[Test]
    public function revoked_staff_session_disables_page_with_blurred_popover_and_blocks_mutations_until_reactivated(): void
    {
        $labHead = User::factory()->head()->create([
            'fullname' => 'Capt. Owusu Lab Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'fullname' => 'Analyst Yaw Mensah',
            'service_id' => 'SD-555',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        // 1. Active staff page loads unlocked (overlay hidden, data-revoked="0", live status watchdog present)
        $activeResp = $this->actingAs($staffUser)->get('/Lab_Analysis/create');
        $activeResp->assertStatus(200);
        $activeResp->assertSee('id="staffAccountRevokedOverlay"', false);
        $activeResp->assertSee('data-revoked="0"', false);
        $activeResp->assertSee('/api/auth/account-status', false);

        // 2. Unit Head revokes staff account
        $this->actingAs($labHead)
            ->postJson("/field-officers/{$staffUser->id}/revoke")
            ->assertStatus(200)
            ->assertJsonPath('user.is_approved', false);

        // 3. Status polling endpoint immediately reports revoked state with Head of Unit name
        $this->actingAs($staffUser->fresh())
            ->getJson('/api/auth/account-status')
            ->assertStatus(200)
            ->assertJsonPath('is_approved', false)
            ->assertJsonPath('is_revoked', true)
            ->assertJsonPath('unit_head_name', 'Capt. Owusu Lab Head');

        // 4. Revoked staff member's page is disabled with visible blurred backdrop revocation popover instructing them to contact Head of Unit
        $revokedPage = $this->actingAs($staffUser->fresh())->get('/Lab_Analysis/create');
        $revokedPage->assertStatus(200);
        $revokedPage->assertSee('id="staffAccountRevokedOverlay"', false);
        $revokedPage->assertSee('data-revoked="1"', false);
        $revokedPage->assertSee('backdrop-blur-md', false);
        $revokedPage->assertSee('Staff Account Disabled');
        $revokedPage->assertSee('Capt. Owusu Lab Head');
        $revokedPage->assertSee('Please contact your Head of Unit');

        // 5. Mutations / AJAX calls from revoked staff session are blocked with 403
        $this->actingAs($staffUser->fresh())
            ->postJson('/field-officers/1/approve')
            ->assertStatus(403)
            ->assertJsonPath('revoked', true);

        // 6. Logout remains permitted
        $this->actingAs($staffUser->fresh())
            ->post('/logout')
            ->assertRedirect('/login');

        // 7. Head of Unit reactivates (re-approves) staff account -> status & pages unlock again
        $this->actingAs($labHead)
            ->postJson("/field-officers/{$staffUser->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('user.is_approved', true);

        $this->actingAs($staffUser->fresh())
            ->getJson('/api/auth/account-status')
            ->assertStatus(200)
            ->assertJsonPath('is_approved', true)
            ->assertJsonPath('is_revoked', false);

        $reEnabledPage = $this->actingAs($staffUser->fresh())->get('/Lab_Analysis/create');
        $reEnabledPage->assertStatus(200);
        $reEnabledPage->assertSee('data-revoked="0"', false);
    }
}

