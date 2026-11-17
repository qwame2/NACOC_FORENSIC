<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
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

        $cssStaff = StaffAccount::create([
            'fullname' => 'CSS Field Staff One',
            'username' => 'css_staff_101',
            'service_id' => 'SD-101',
            'phone_number' => '0244000101',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $emStaff = StaffAccount::create([
            'fullname' => 'EM Vault Staff Two',
            'username' => 'em_staff_102',
            'service_id' => 'SD-102',
            'phone_number' => '0244000102',
            'unit' => 'Evidence Management',
            'is_approved' => false,
            'password' => bcrypt('password'),
        ]);

        $labStaff = StaffAccount::create([
            'fullname' => 'LAU Chemist Staff Three',
            'username' => 'lab_staff_103',
            'service_id' => 'SD-103',
            'phone_number' => '0244000103',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $dpfStaff = StaffAccount::create([
            'fullname' => 'DPF Cyber Staff Four',
            'username' => 'dpf_staff_104',
            'service_id' => 'SD-104',
            'phone_number' => '0244000104',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => false,
            'password' => bcrypt('password'),
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
            'password' => bcrypt('HeadPass2026!'),
        ]);

        StaffAccount::create([
            'fullname' => 'Approved Chemist Akua',
            'username' => 'akua_201',
            'service_id' => 'SD-201',
            'phone_number' => '0244000201',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        StaffAccount::create([
            'fullname' => 'Pending Chemist Kojo',
            'username' => 'kojo_202',
            'service_id' => 'SD-202',
            'phone_number' => '0244000202',
            'unit' => 'Lab Analysis',
            'is_approved' => false,
            'password' => bcrypt('password'),
        ]);

        StaffAccount::create([
            'fullname' => 'Vault Officer Kwabena',
            'username' => 'kwabena_203',
            'service_id' => 'SD-203',
            'phone_number' => '0244000203',
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'password' => bcrypt('password'),
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
        for ($i = 1; $i <= 4; $i++) {
            StaffAccount::create([
                'fullname' => "CSS Paginate Staff {$i}",
                'username' => "css_page_{$i}",
                'service_id' => "SD-25{$i}",
                'phone_number' => "024400025{$i}",
                'unit' => 'Crime Scene Support',
                'is_approved' => true,
                'password' => bcrypt('password'),
            ]);
        }

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

        $staffUser = StaffAccount::where('service_id', 'SD-777')->firstOrFail();
        $this->assertDatabaseMissing('users', ['service_id' => 'SD-777']);
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
    public function login_page_tracks_pending_staff_approval_and_exposes_blurred_approved_popover(): void
    {
        $labHead = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('HeadPass2026!'),
        ]);

        $staffUser = StaffAccount::create([
            'fullname' => 'Officer Ama Boateng',
            'service_id' => 'SD-888',
            'username' => 'aboateng',
            'phone_number' => '0244888888',
            'unit' => 'Lab Analysis',
            'is_approved' => false,
            'password' => bcrypt('StrongPass2026!'),
        ]);

        // Pending login attempt returns pending_approval flag + login_id for client-side polling
        $this->postJson('/login', [
            'login_id' => 'sd-888',
            'password' => 'StrongPass2026!',
        ])->assertStatus(403)
            ->assertJsonPath('pending_approval', true)
            ->assertJsonPath('login_id', 'SD-888');

        // Login page renders blurred approved popover modal and embeds flashed pending_login_id
        $this->withSession(['pending_login_id' => 'SD-888'])
            ->get('/login')
            ->assertStatus(200)
            ->assertSee('id="loginAccountApprovedPopoverModal"', false)
            ->assertSee('backdrop-blur-md', false)
            ->assertSee('Account Approved by Head of Unit!', false)
            ->assertSee('const INITIAL_PENDING_LOGIN_ID = "SD-888";', false)
            ->assertSee('/api/check-user-approval', false);

        // Before approval, polling endpoint reports is_approved: false for staff_accounts row
        $this->getJson('/api/check-user-approval?login_id=sd-888')
            ->assertStatus(200)
            ->assertJsonPath('exists', true)
            ->assertJsonPath('is_approved', false)
            ->assertJsonPath('is_head', false);

        $beforeApproveTs = time() - 1;

        // Head of Unit approves staff account
        $this->actingAs($labHead)
            ->postJson("/field-officers/{$staffUser->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('user.is_approved', true);

        // After approval, polling endpoint reports is_approved: true with unit & fullname (both by login_id and by since)
        $this->getJson('/api/check-user-approval?login_id=sd-888')
            ->assertStatus(200)
            ->assertJsonPath('exists', true)
            ->assertJsonPath('is_approved', true)
            ->assertJsonPath('fullname', 'Officer Ama Boateng')
            ->assertJsonPath('unit', 'Lab Analysis');

        $this->getJson('/api/check-user-approval?since=' . $beforeApproveTs)
            ->assertStatus(200)
            ->assertJsonPath('exists', true)
            ->assertJsonPath('is_approved', true)
            ->assertJsonPath('service_id', 'SD-888');

        auth()->logout();

        // Approved staff account can now log in and redirects to unit staff dashboard first
        $this->withSession(['url.intended' => 'http://127.0.0.1/Lab_Analysis/staff/create'])
            ->postJson('/login', [
                'login_id' => 'sd-888',
                'password' => 'StrongPass2026!',
            ])->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect_url', '/Lab_Analysis/staff/dashboard')
            ->assertJsonPath('user.is_approved', true);

        auth()->logout();

        // Unit Head login also redirects to unit Head Dashboard first
        $this->withSession(['url.intended' => 'http://127.0.0.1/Lab_Analysis/create'])
            ->post('/login', [
                'login_id' => $labHead->service_id,
                'password' => 'HeadPass2026!',
            ])->assertRedirect('/Lab_Analysis');
    }

    #[Test]
    public function only_one_unit_head_account_is_allowed_per_unit(): void
    {
        // Establish Forensic Directorate head first
        User::factory()->head()->create([
            'fullname' => 'Directorate Commander',
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        // Existing Lab Analysis head and Digital & Physical Forensics head
        User::factory()->head()->create([
            'fullname' => 'Dr. Kwesi Lab Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);
        User::factory()->head()->create([
            'fullname' => 'Eng. Kojo Digital Head',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        // Attempt to register a second head for Lab Analysis -> rejected
        $this->from('/register')
            ->post('/register', [
                'fullname' => 'Second Lab Head',
                'username' => 'secondlabhead',
                'service_id' => 'SD-991',
                'phone_number' => '0240000991',
                'unit' => 'Lab Analysis',
                'role' => 'head',
                'password' => 'StrongPass2026!',
                'password_confirmation' => 'StrongPass2026!',
                'terms' => '1',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('unit');

        $this->assertDatabaseMissing('users', ['service_id' => 'SD-991']);

        // Attempt to register a second head for Digital & Physical Forensics using unit alias -> rejected
        $this->from('/register')
            ->post('/register', [
                'fullname' => 'Second Digital Head',
                'username' => 'seconddpfhead',
                'service_id' => 'SD-992',
                'phone_number' => '0240000992',
                'unit' => 'Digital & Physical Forensic Unit',
                'role' => 'head',
                'password' => 'StrongPass2026!',
                'password_confirmation' => 'StrongPass2026!',
                'terms' => '1',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('unit');

        $this->assertDatabaseMissing('users', ['service_id' => 'SD-992']);

        // Registering a head for a unit without one (Crime Scene Support) -> allowed
        $this->post('/register', [
            'fullname' => 'Capt. Afia CSS Head',
            'username' => 'afiacsshead',
            'service_id' => 'SD-993',
            'phone_number' => '0240000993',
            'unit' => 'Crime Scene Support',
            'role' => 'head',
            'password' => 'StrongPass2026!',
            'password_confirmation' => 'StrongPass2026!',
            'terms' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', [
            'service_id' => 'SD-993',
            'is_head' => true,
            'unit' => 'Crime Scene Support',
        ]);

        // Registering a staff member for Lab Analysis (which already has a head) -> still allowed
        $this->post('/register', [
            'fullname' => 'Analyst Kofi Staff',
            'username' => 'kofistaff',
            'service_id' => 'SD-994',
            'phone_number' => '0240000994',
            'unit' => 'Lab Analysis',
            'role' => 'staff',
            'password' => 'StrongPass2026!',
            'password_confirmation' => 'StrongPass2026!',
            'terms' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('staff_accounts', [
            'service_id' => 'SD-994',
            'is_approved' => false,
        ]);
        $this->assertDatabaseMissing('users', ['service_id' => 'SD-994']);
    }

    #[Test]
    public function field_officers_pages_do_not_render_status_indicator_dots_or_blinking_toast_dot(): void
    {
        $directorateHead = User::factory()->head()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        $labHead = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        StaffAccount::create([
            'fullname' => 'Online Approved Staff',
            'username' => 'online.staff',
            'service_id' => 'SD-881',
            'phone_number' => '0244000881',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'last_seen_at' => now(),
            'password' => bcrypt('password'),
        ]);

        StaffAccount::create([
            'fullname' => 'Pending Offline Staff',
            'username' => 'pending.staff',
            'service_id' => 'SD-882',
            'phone_number' => '0244000882',
            'unit' => 'Lab Analysis',
            'is_approved' => false,
            'last_seen_at' => null,
            'password' => bcrypt('password'),
        ]);

        foreach ([
            [$directorateHead, '/field-officers'],
            [$labHead, '/Lab_Analysis/field-officers'],
        ] as [$viewer, $uri]) {
            $resp = $this->actingAs($viewer)->get($uri);
            $resp->assertStatus(200);
            $resp->assertSee('Online Approved Staff');
            $resp->assertSee('Pending Offline Staff');
            $resp->assertDontSee('bg-emerald-500 rounded-full animate-pulse', false);
            $resp->assertDontSee('bg-amber-500 rounded-full animate-pulse', false);
            $resp->assertDontSee('w-1.5 h-1.5 rounded-full', false);
            $resp->assertDontSee('w-2 h-2 bg-emerald-500 rounded-full', false);
            $resp->assertSee('id="fieldOfficersToastDot"', false);
        }
    }

    #[Test]
    public function login_and_approval_check_do_not_throw_500_when_staff_accounts_table_is_missing(): void
    {
        $head = User::factory()->head()->create([
            'username' => 'samhead',
            'service_id' => 'SD-999',
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
            'password' => bcrypt('HeadPass2026!'),
        ]);

        \Illuminate\Support\Facades\Schema::dropIfExists('staff_accounts');

        $this->getJson('/api/check-user-approval?login_id=sam')
            ->assertStatus(200)
            ->assertJson(['exists' => false]);

        $this->postJson('/login', [
            'login_id' => 'sam',
            'password' => 'wrongpass',
        ])->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->postJson('/login', [
            'login_id' => $head->username,
            'password' => 'HeadPass2026!',
        ])->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect_url', '/Forensic_Head');
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
    public function heads_sidebar_field_officers_button_displays_red_badge_for_pending_staff_approvals(): void
    {
        $labHead = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('HeadPass2026!'),
        ]);
        $directorateHead = User::factory()->head()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        // No pending staff yet -> badge hidden
        $labZeroResp = $this->actingAs($labHead)
            ->get('/Lab_Analysis/field-officers');
        $labZeroResp->assertStatus(200);
        $this->assertMatchesRegularExpression('/id="sidebarFieldOfficersBadge"[^>]*class="[^"]*hidden/', $labZeroResp->getContent());

        // 2 pending Lab Analysis staff, 1 approved Lab Analysis staff, 1 pending CSS staff, 1 unapproved head
        StaffAccount::create(['fullname' => 'Lab Pending 1', 'username' => 'lp1', 'service_id' => 'SD-501', 'phone_number' => '0244000501', 'unit' => 'Lab Analysis', 'is_approved' => false, 'password' => bcrypt('password')]);
        StaffAccount::create(['fullname' => 'Lab Pending 2', 'username' => 'lp2', 'service_id' => 'SD-502', 'phone_number' => '0244000502', 'unit' => 'Lab Analysis', 'is_approved' => false, 'password' => bcrypt('password')]);
        StaffAccount::create(['fullname' => 'Lab Approved 3', 'username' => 'la3', 'service_id' => 'SD-503', 'phone_number' => '0244000503', 'unit' => 'Lab Analysis', 'is_approved' => true, 'password' => bcrypt('password')]);
        StaffAccount::create(['fullname' => 'CSS Pending 4', 'username' => 'cp4', 'service_id' => 'SD-504', 'phone_number' => '0244000504', 'unit' => 'Crime Scene Support', 'is_approved' => false, 'password' => bcrypt('password')]);
        User::factory()->head()->create(['unit' => 'Lab Analysis', 'is_approved' => false]);

        $labResp = $this->actingAs($labHead)
            ->get('/Lab_Analysis/field-officers')
            ->assertStatus(200)
            ->assertSee('id="sidebarFieldOfficersBadge"', false)
            ->assertSee('bg-red-600', false)
            ->assertSee('animate-pulse', false);
        $this->assertMatchesRegularExpression('/id="sidebarFieldOfficersBadge"[^>]*>\s*2\s*<\/span>/', $labResp->getContent());

        $dirResp = $this->actingAs($directorateHead)
            ->get('/field-officers')
            ->assertStatus(200)
            ->assertSee('id="sidebarFieldOfficersBadge"', false)
            ->assertSee('bg-red-600', false);
        $this->assertMatchesRegularExpression('/id="sidebarFieldOfficersBadge"[^>]*>\s*3\s*<\/span>/', $dirResp->getContent());
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

