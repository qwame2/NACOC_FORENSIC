<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function field_officers_page_title_displays_unit_field_officers_and_staff_details(): void
    {
        $labHead = User::factory()->head()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $resp = $this->actingAs($labHead)->get('/Lab_Analysis/field-officers');
        $resp->assertOk()
            ->assertSee('Unit Field Officers &amp; Staff Details', false)
            ->assertDontSee('Unit Field Officers &amp; Staff Roster', false)
            ->assertDontSee('Unit Field Officers & Staff Roster');
    }

    #[Test]
    public function unit_head_and_directorate_sidebars_include_permissions_button(): void
    {
        $head = User::factory()->head()->create([
            'unit' => 'Forensic Directorate',
            'is_approved' => true,
        ]);

        $this->actingAs($head)->get('/Lab_Analysis/create')
            ->assertOk()
            ->assertSee('/Lab_Analysis/permissions', false)
            ->assertSee('Permissions')
            ->assertDontSee('Case Rollback')
            ->assertSee('Staff Audit Logs');

        $this->actingAs($head)->get('/Evidence_Management/create')
            ->assertOk()
            ->assertSee('/Evidence_Management/permissions', false)
            ->assertDontSee('Case Rollback')
            ->assertSee('Staff Audit Logs');

        $this->actingAs($head)->get('/Digital_Forensics/create')
            ->assertOk()
            ->assertSee('/Digital_Forensics/permissions', false)
            ->assertDontSee('Case Rollback')
            ->assertSee('Staff Audit Logs');

        $this->actingAs($head)->get('/Crime_Scene_Support/create')
            ->assertOk()
            ->assertSee('/Crime_Scene_Support/permissions', false)
            ->assertDontSee('Case Rollback')
            ->assertSee('Staff Audit Logs');

        $this->actingAs($head)->get('/Forensic_Head/cases')
            ->assertOk()
            ->assertSee('/Forensic_Head/permissions', false)
            ->assertDontSee('Case Rollback')
            ->assertSee('Staff Audit Logs');
    }

    #[Test]
    public function permissions_page_is_restricted_to_unit_heads_and_enforces_unit_boundaries(): void
    {
        $labHead = User::factory()->head()->create([
            'fullname' => 'Lab Unit Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $cssHead = User::factory()->head()->create([
            'fullname' => 'CSS Unit Head',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $labStaff = StaffAccount::create([
            'fullname' => 'Kofi Chemist',
            'username' => 'kofi_chemist',
            'service_id' => 'SD-901',
            'phone_number' => '0244000901',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $cssStaff = StaffAccount::create([
            'fullname' => 'Ama Scene Officer',
            'username' => 'ama_scene',
            'service_id' => 'SD-902',
            'phone_number' => '0244000902',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Non-head staff cannot access permissions page or mutate permissions
        $this->actingAs($labStaff)->get('/Lab_Analysis/permissions')->assertRedirect('/cases');
        $this->actingAs($labStaff)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'grant_all',
        ])->assertStatus(403);

        // 2. Lab Head can load Lab permissions page and sees ONLY Lab unit staff (never other units' staff)
        $pageResp = $this->actingAs($labHead)->get('/Lab_Analysis/permissions');
        $pageResp->assertOk()
            ->assertSee('Unit Staff Permissions &amp; Access Control', false)
            ->assertSee('Kofi Chemist')
            ->assertDontSee('Ama Scene Officer')
            ->assertDontSee('Assigned Unit')
            ->assertDontSee('Quick Actions')
            ->assertDontSee('Save Policy')
            ->assertDontSee('Grant All')
            ->assertDontSee('Restrict All')
            ->assertDontSee('Unit Scope:')
            ->assertSee('Search Staff:')
            ->assertSee('id="permissionsSearchInput"', false)
            ->assertDontSee('<th class="py-3.5 px-4 whitespace-nowrap">Account</th>', false)
            ->assertSee('role="switch"', false)
            ->assertDontSee('View Case Directory')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Manage Exhibit Vault')
            ->assertSee('Lab &amp; Drug Screening', false)
            ->assertSee('Digital Forensics Intake')
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertSee('Notifications &amp; Handovers', false);

        // Even with tampered ?unit=all or ?unit=css query parameters, Lab Head cannot view CSS staff
        $this->actingAs($labHead)->get('/Lab_Analysis/permissions?unit=all')
            ->assertOk()
            ->assertSee('Kofi Chemist')
            ->assertDontSee('Ama Scene Officer');

        $this->actingAs($labHead)->get('/Lab_Analysis/permissions?unit=css&poll=1')
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('pagination.total', 1);

        // 3. Cross-unit Head cannot modify permissions for Lab staff
        $this->actingAs($cssHead)->postJson("/permissions/{$labStaff->id}/update", [
            'permissions' => ['create_cases' => false],
        ])->assertStatus(403);

        // 4. Lab Head updates granular permissions for Lab staff
        $updateResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'permissions' => [
                'create_cases' => false,
                'edit_cases' => false,
                'manage_exhibits' => true,
                'record_lab_analysis' => true,
                'record_digital_forensics' => false,
                'generate_reports' => true,
                'view_notifications' => true,
            ],
        ]);
        $updateResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.enabled', 4)
            ->assertJsonPath('officer.total', 7);

        $freshStaff = $labStaff->fresh();
        $this->assertTrue($freshStaff->hasPermission('manage_exhibits'));
        $this->assertTrue($freshStaff->hasPermission('record_lab_analysis'));
        $this->assertFalse($freshStaff->hasPermission('create_cases'));
        $this->assertFalse($freshStaff->hasPermission('edit_cases'));

        // 5. Restrict All preset sets every capability to false cleanly
        $restrictResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'restrict_all',
        ]);
        $restrictResp->assertOk()
            ->assertJsonPath('officer.enabled', 0);
        $this->assertFalse($labStaff->fresh()->hasPermission('manage_exhibits'));

        // 6. Grant All preset enables all 7 capabilities
        $grantResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'grant_all',
        ]);
        $grantResp->assertOk()
            ->assertJsonPath('officer.enabled', 7);
        $this->assertTrue($labStaff->fresh()->hasPermission('create_cases'));

        // 7. CSS Head can load CSS permissions page ONLY for their own unit staff and sees only the 3 streamlined permissions
        $this->actingAs($cssHead)->get('/Crime_Scene_Support/permissions')
            ->assertOk()
            ->assertSee('Ama Scene Officer')
            ->assertDontSee('Kofi Chemist')
            ->assertDontSee('View Case Directory')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Lab &amp; Drug Screening', false)
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertSee('3/3 active');

        // 8. Digital Forensics Head sees only the 3 streamlined permissions on /Digital_Forensics/permissions
        $dpfHead = User::factory()->head()->create([
            'fullname' => 'DPF Unit Head',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);
        $dpfStaff = StaffAccount::create([
            'fullname' => 'Yaw Cyber Officer',
            'username' => 'yaw_cyber',
            'service_id' => 'SD-903',
            'phone_number' => '0244000903',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($dpfHead)->get('/Digital_Forensics/permissions')
            ->assertOk()
            ->assertSee('Yaw Cyber Officer')
            ->assertDontSee('View Case Directory')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Lab &amp; Drug Screening', false)
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertSee('3/3 active');
    }

    #[Test]
    public function toggling_off_create_new_cases_greys_out_staff_create_button_and_blocks_create_page_until_reenabled(): void
    {
        $cssHead = User::factory()->head()->create([
            'fullname' => 'CSS Unit Head',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $restrictedStaff = StaffAccount::create([
            'fullname' => 'Restricted Scene Staff',
            'username' => 'restricted_scene',
            'service_id' => 'SD-910',
            'phone_number' => '0244000910',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $enabledStaff = StaffAccount::create([
            'fullname' => 'Enabled Scene Staff',
            'username' => 'enabled_scene',
            'service_id' => 'SD-911',
            'phone_number' => '0244000911',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Unit Head toggles off Create New Cases for $restrictedStaff
        $this->actingAs($cssHead)->postJson("/permissions/{$restrictedStaff->id}/update", [
            'permissions' => [
                'view_cases' => true,
                'create_cases' => false,
                'edit_cases' => true,
                'generate_reports' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 2. In $restrictedStaff's account, the Create New Case button is greyed out, disabled, and has a locked tooltip
        $casePage = $this->actingAs($restrictedStaff->fresh())->get('/Crime_Scene_Support/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-create-btn="sidebar"', false)
            ->assertSee('data-staff-create-btn="header"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Create New Cases permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Create New Cases permission is disabled by your Head of Unit"', false)
            ->assertSee('nacocLockedBtnTooltip', false)
            ->assertDontSee('href="' . url('/Crime_Scene_Support/staff/create') . '"', false);

        // 3. Live account-status endpoint reports can_create_cases = false for $restrictedStaff
        $this->actingAs($restrictedStaff->fresh())
            ->getJson('/api/auth/account-status')
            ->assertOk()
            ->assertJsonPath('can_create_cases', false)
            ->assertJsonPath('permissions.create_cases', false);

        // 4. Attempting to open the create page directly or submit a case is blocked for $restrictedStaff
        $this->actingAs($restrictedStaff->fresh())
            ->get('/Crime_Scene_Support/staff/create')
            ->assertRedirect('/Crime_Scene_Support/staff/case');

        $this->actingAs($restrictedStaff->fresh())
            ->postJson('/cases', ['title' => 'Blocked Case'])
            ->assertStatus(403)
            ->assertJsonPath('permission_denied', 'create_cases');

        // 5. Other staff in the unit whose Create New Cases permission is still ON remain unaffected
        $this->actingAs($enabledStaff->fresh())
            ->get('/Crime_Scene_Support/staff/create')
            ->assertOk();

        // 6. Unit Head turns Create New Cases back ON for $restrictedStaff
        $this->actingAs($cssHead)->postJson("/permissions/{$restrictedStaff->id}/update", [
            'permissions' => [
                'view_cases' => true,
                'create_cases' => true,
                'edit_cases' => true,
                'generate_reports' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 7. Create New Case button is restored and $restrictedStaff can open the create page again
        $restoredCasePage = $this->actingAs($restrictedStaff->fresh())->get('/Crime_Scene_Support/staff/case');
        $restoredCasePage->assertOk()
            ->assertSee('aria-disabled="false"', false)
            ->assertSee('href="' . url('/Crime_Scene_Support/staff/create') . '"', false);

        $this->actingAs($restrictedStaff->fresh())
            ->get('/Crime_Scene_Support/staff/create')
            ->assertOk();

        // 8. When Digital Forensics Unit Head toggles off Create New Cases for their unit's staff,
        // it strictly affects Digital Forensics staff and NEVER restricts staff in other units
        // (nor does it grey out other units' Create New Case buttons).
        $dpfHead = User::factory()->head()->create([
            'fullname' => 'DPF Unit Head',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $dpfStaff = StaffAccount::create([
            'fullname' => 'Isaac DPF Staff',
            'username' => 'isaac_dpf',
            'service_id' => 'SD-912',
            'phone_number' => '0244000912',
            'unit' => 'Digital & Physical Forensic Unit',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($dpfHead)->postJson("/permissions/{$dpfStaff->id}/update", [
            'permissions' => [
                'view_cases' => true,
                'create_cases' => false,
                'edit_cases' => true,
                'record_digital_forensics' => true,
                'generate_reports' => true,
                'view_notifications' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // Digital Forensics staff is restricted in their own unit
        $this->actingAs($dpfStaff->fresh())
            ->get('/Digital_Forensics/staff/case')
            ->assertOk()
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('href="' . url('/Digital_Forensics/staff/create') . '"', false);

        $this->actingAs($dpfStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Digital_Forensics/staff/case')
            ->assertOk()
            ->assertJsonPath('can_create_cases', false);

        // Crime Scene Support staff remains completely unrestricted
        $this->actingAs($enabledStaff->fresh())
            ->get('/Crime_Scene_Support/staff/case')
            ->assertOk()
            ->assertSee('aria-disabled="false"', false)
            ->assertSee('href="' . url('/Crime_Scene_Support/staff/create') . '"', false);

        // Even if the Digital Forensics session visits another unit's page, that other unit's button is NOT greyed out by DPF's toggle
        $this->actingAs($dpfStaff->fresh())
            ->get('/Crime_Scene_Support/staff/case')
            ->assertOk()
            ->assertSee('aria-disabled="false"', false)
            ->assertSee('href="' . url('/Crime_Scene_Support/staff/create') . '"', false);

        $this->actingAs($dpfStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Crime_Scene_Support/staff/case')
            ->assertOk()
            ->assertJsonPath('can_create_cases', true);
    }
}

